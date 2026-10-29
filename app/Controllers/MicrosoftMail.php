<?php

namespace App\Controllers;

use Config\MicrosoftMail as MicrosoftMailConfig;

class MicrosoftMail extends BaseController
{
    private function settings(): MicrosoftMailConfig
    {
        $settings = config('MicrosoftMail');

        $guid = '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}'
            . '-[a-f0-9]{4}-[a-f0-9]{12}$/i';

        if (! preg_match($guid, $settings->tenantId)
            || ! preg_match($guid, $settings->clientId)
            || $settings->clientSecret === ''
            || ! filter_var($settings->senderEmail, FILTER_VALIDATE_EMAIL)
            || ! filter_var($settings->redirectUri, FILTER_VALIDATE_URL)
        ) {
            throw new \RuntimeException(
                'Konfigurasi Microsoft belum lengkap atau tidak valid.'
            );
        }

        return $settings;
    }

    private function result(string $type, string $message)
    {
        return redirect()
            ->to(site_url('admin/mail'))
            ->setHeader('Cache-Control', 'no-store, private')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->with($type, $message);
    }

    public function index()
    {
        helper(['url', 'form']);

        $connection = \Config\Database::connect()
            ->table('microsoft_mail_connections')
            ->select('sender_email, updated_at')
            ->where('id', 1)
            ->get()
            ->getRowArray();

        return view('admin/mail/index', [
            'title'        => 'Email Pengirim',
            'activePage'   => 'mail-settings',
            'senderEmail'  => config('MicrosoftMail')->senderEmail,
            'connection'   => $connection,
        ]);
    }

    public function connect()
    {
        helper('url');

        try {
            $settings = $this->settings();

            // Pastikan enkripsi siap sebelum meminta izin Microsoft.
            service('encrypter')->encrypt('connection-check');

            $state = bin2hex(random_bytes(32));
            $verifier = bin2hex(random_bytes(32));

            $challenge = rtrim(strtr(
                base64_encode(hash('sha256', $verifier, true)),
                '+/',
                '-_'
            ), '=');

            session()->set('microsoft_mail_flow', [
                'state'      => $state,
                'verifier'   => $verifier,
                'started_at' => time(),
                'user_id'    => (int) session()->get('user_id'),
            ]);

            $parameters = [
                'client_id'             => $settings->clientId,
                'response_type'         => 'code',
                'redirect_uri'          => $settings->redirectUri,
                'response_mode'         => 'query',
                'scope'                 => $settings->scopes,
                'state'                 => $state,
                'code_challenge'        => $challenge,
                'code_challenge_method' => 'S256',
                'login_hint'            => $settings->senderEmail,
                'prompt'                => 'select_account',
            ];

            $url = 'https://login.microsoftonline.com/'
                . $settings->tenantId
                . '/oauth2/v2.0/authorize?'
                . http_build_query(
                    $parameters,
                    '',
                    '&',
                    PHP_QUERY_RFC3986
                );

            // 303 agar browser melakukan GET ke Microsoft.
            return redirect()->to($url, 303);
        } catch (\Throwable $e) {
            // Jangan mencatat secret atau token ke log.
            return $this->result(
                'error',
                'Koneksi belum dapat dimulai. Periksa konfigurasi Microsoft '
                . 'dan encryption.key di .env.'
            );
        }
    }

    public function callback()
    {
        helper('url');

        $flow = session()->get('microsoft_mail_flow');
        $state = $this->request->getGet('state');

        if (! is_array($flow)
            || ! is_string($state)
            || ! isset($flow['state'], $flow['verifier'], $flow['started_at'])
            || ! hash_equals($flow['state'], $state)
            || time() - (int) $flow['started_at'] > 600
            || (int) ($flow['user_id'] ?? 0)
                !== (int) session()->get('user_id')
        ) {
            return $this->result(
                'error',
                'Sesi penghubungan tidak valid atau sudah kedaluwarsa. '
                . 'Silakan mulai kembali.'
            );
        }

        // Satu alur hanya diproses sekali.
        session()->remove('microsoft_mail_flow');

        if ($this->request->getGet('error') !== null) {
            return $this->result(
                'error',
                'Otorisasi Microsoft dibatalkan atau ditolak. '
                . 'Jika diperlukan persetujuan admin organisasi, '
                . 'hubungi pengelola Microsoft 365.'
            );
        }

        $code = $this->request->getGet('code');

        if (! is_string($code) || $code === '') {
            return $this->result(
                'error',
                'Microsoft tidak mengembalikan kode otorisasi.'
            );
        }

        // Menandai tahap kegagalan tanpa mencatat data rahasia.
        $stage = 'configuration';

        try {
            $settings = $this->settings();

            $stage = 'token_exchange';

            $tokenResponse = \Config\Services::curlrequest(
                [],
                null,
                null,
                false
            )->post(
                'https://login.microsoftonline.com/'
                    . $settings->tenantId
                    . '/oauth2/v2.0/token',
                [
                    'http_errors'     => false,
                    'allow_redirects' => false,
                    'connect_timeout' => 10,
                    'timeout'         => 30,
                    'form_params'     => [
                        'client_id'     => $settings->clientId,
                        'client_secret' => $settings->clientSecret,
                        'grant_type'    => 'authorization_code',
                        'code'          => $code,
                        'redirect_uri'  => $settings->redirectUri,
                        'code_verifier' => $flow['verifier'],
                        'scope'         => $settings->scopes,
                    ],
                ]
            );

            if ($tokenResponse->getStatusCode() !== 200) {
                throw new \RuntimeException('Token exchange failed.');
            }

            $tokens = json_decode(
                $tokenResponse->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (empty($tokens['access_token'])
                || empty($tokens['refresh_token'])
                || (int) ($tokens['expires_in'] ?? 0) <= 0
            ) {
                throw new \RuntimeException('Incomplete token response.');
            }

            $grantedScopes = preg_split(
                '/\s+/',
                strtolower(trim((string) ($tokens['scope'] ?? '')))
            );

            if (! in_array('mail.send', $grantedScopes, true)
                && ! in_array(
                    'https://graph.microsoft.com/mail.send',
                    $grantedScopes,
                    true
                )
            ) {
                throw new \RuntimeException('Mail.Send was not granted.');
            }

            $stage = 'sender_check';

            // Gunakan HTTP client baru agar body/headers tidak tercampur.
            $profileResponse = \Config\Services::curlrequest(
                [],
                null,
                null,
                false
            )->get(
                'https://graph.microsoft.com/v1.0/me'
                    . '?$select=id,mail,userPrincipalName',
                [
                    'http_errors'     => false,
                    'allow_redirects' => false,
                    'connect_timeout' => 10,
                    'timeout'         => 30,
                    'headers'         => [
                        'Authorization' => 'Bearer '
                            . $tokens['access_token'],
                        'Accept' => 'application/json',
                    ],
                ]
            );

            if ($profileResponse->getStatusCode() !== 200) {
                throw new \RuntimeException('Account lookup failed.');
            }

            $account = json_decode(
                $profileResponse->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $addresses = [
                strtolower(trim((string) ($account['mail'] ?? ''))),
                strtolower(trim(
                    (string) ($account['userPrincipalName'] ?? '')
                )),
            ];

            if (empty($account['id'])
                || ! in_array($settings->senderEmail, $addresses, true)
            ) {
                return $this->result(
                    'error',
                    'Akun Microsoft yang dipilih tidak sesuai. '
                    . 'Hubungkan akun ' . $settings->senderEmail . '.'
                );
            }

            $stage = 'encrypted_storage';

            $payload = json_encode([
                'access_token'  => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_at'    => time() + (int) $tokens['expires_in'],
            ], JSON_THROW_ON_ERROR);

            $encrypted = base64_encode(
                service('encrypter')->encrypt($payload)
            );

            $now = date('Y-m-d H:i:s');

            // Satu koneksi pengirim untuk seluruh portal.
            // Menghubungkan ulang mengganti koneksi pada ID yang sama.
            $saved = \Config\Database::connect()
                ->table('microsoft_mail_connections')
                ->replace([
                    'id'                => 1,
                    'tenant_id'         => $settings->tenantId,
                    'client_id'         => $settings->clientId,
                    'microsoft_user_id' => $account['id'],
                    'sender_email'      => $settings->senderEmail,
                    'token_payload'     => $encrypted,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);

            if (! $saved) {
                throw new \RuntimeException('Connection storage failed.');
            }

            return $this->result(
                'success',
                'Akun Microsoft berhasil dihubungkan dan token tersimpan. '
                . 'Pengiriman email belum dialihkan dari Gmail.'
            );
        } catch (\Throwable $e) {
            log_message(
                'error',
                'Microsoft mail connection failed at stage: ' . $stage
            );

            return $this->result(
                'error',
                'Koneksi Microsoft gagal pada tahap: ' . $stage
                    . '. Silakan periksa konfigurasi atau coba hubungkan ulang.'
            );
        }
    }

    public function test()
{
    helper('url');

    $key = 'microsoft-mail-test-'
        . (int) session()->get('user_id');

    if (! service('throttler')->check($key, 1, 60)) {
        return $this->result(
            'error',
            'Tunggu sekitar satu menit sebelum mengirim tes berikutnya.'
        );
    }

    try {
        $settings = $this->settings();

        $html = '
            <h2>Permata Bangsa School</h2>
            <p>Ini adalah email percobaan dari PBS Staff Portal.</p>
            <p>Jika email ini diterima, pengiriman melalui
            Microsoft 365 sudah berhasil.</p>
        ';

        (new \App\Libraries\MicrosoftGraphMailer())->send(
            $settings->senderEmail,
            'Tes Email — PBS Staff Portal',
            $html
        );

        return $this->result(
            'success',
            'Microsoft menerima permintaan pengiriman. '
            . 'Silakan cek Kotak Masuk dan folder Terkirim akun mentor.'
        );
    } catch (\Throwable $e) {
        // Pesan RuntimeException dari library sudah dibuat tanpa token.
        $message = $e instanceof \RuntimeException
            ? $e->getMessage()
            : 'Tes email gagal. Periksa konfigurasi koneksi Microsoft.';

        return $this->result('error', $message);
    }
}
}