<?php

namespace App\Libraries;

class MicrosoftGraphMailer
{
    private function request(
        string $method,
        string $url,
        array $options = []
    ) {
        return \Config\Services::curlrequest(
            [],
            null,
            null,
            false
        )->request($method, $url, array_merge([
            'http_errors'     => false,
            'allow_redirects' => false,
            'connect_timeout' => 10,
            'timeout'         => 30,
        ], $options));
    }

    private function accessToken(): string
    {
        $settings = config('MicrosoftMail');
        $db = \Config\Database::connect();

        try {
            if ($db->transBegin() === false) {
                throw new \RuntimeException('Transaksi gagal dimulai.');
            }

            // Hindari dua permintaan memperbarui token bersamaan.
            $connection = $db->query(
                'SELECT * FROM microsoft_mail_connections
                 WHERE id = ? FOR UPDATE',
                [1]
            )->getRowArray();

            if (! $connection) {
                throw new \RuntimeException(
                    'Akun Microsoft belum dihubungkan.'
                );
            }

            if ($connection['tenant_id'] !== $settings->tenantId
                || $connection['client_id'] !== $settings->clientId
                || strtolower($connection['sender_email'])
                    !== $settings->senderEmail
            ) {
                throw new \RuntimeException(
                    'Konfigurasi berubah. Hubungkan ulang akun Microsoft.'
                );
            }

            $encrypted = base64_decode(
                $connection['token_payload'],
                true
            );

            if ($encrypted === false) {
                throw new \RuntimeException('Data koneksi tidak valid.');
            }

            $payload = json_decode(
                service('encrypter')->decrypt($encrypted),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (! is_array($payload)
                || empty($payload['access_token'])
                || empty($payload['refresh_token'])
            ) {
                throw new \RuntimeException('Token koneksi tidak lengkap.');
            }

            // Perbarui jika token akan kedaluwarsa dalam 120 detik.
            if ((int) ($payload['expires_at'] ?? 0) <= time() + 120) {
                $response = $this->request(
                    'POST',
                    'https://login.microsoftonline.com/'
                        . $settings->tenantId
                        . '/oauth2/v2.0/token',
                    [
                        'form_params' => [
                            'client_id'     => $settings->clientId,
                            'client_secret' => $settings->clientSecret,
                            'grant_type'    => 'refresh_token',
                            'refresh_token' => $payload['refresh_token'],
                            'scope'         => $settings->scopes,
                        ],
                    ]
                );

                if ($response->getStatusCode() !== 200) {
                    throw new \RuntimeException(
                        'Token tidak dapat diperbarui. Hubungkan ulang Microsoft.'
                    );
                }

                $renewed = json_decode(
                    $response->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                if (empty($renewed['access_token'])
                    || (int) ($renewed['expires_in'] ?? 0) <= 0
                ) {
                    throw new \RuntimeException(
                        'Respons pembaruan token tidak lengkap.'
                    );
                }

                $payload = [
                    'access_token' => $renewed['access_token'],
                    'refresh_token' => ! empty($renewed['refresh_token'])
                        ? $renewed['refresh_token']
                        : $payload['refresh_token'],
                    'expires_at' => time() + (int) $renewed['expires_in'],
                ];

                $saved = $db->table('microsoft_mail_connections')
                    ->where('id', 1)
                    ->update([
                        'token_payload' => base64_encode(
                            service('encrypter')->encrypt(
                                json_encode(
                                    $payload,
                                    JSON_THROW_ON_ERROR
                                )
                            )
                        ),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                if (! $saved) {
                    throw new \RuntimeException(
                        'Token baru gagal disimpan.'
                    );
                }
            }

            if ($db->transStatus() === false
                || $db->transCommit() === false
            ) {
                throw new \RuntimeException(
                    'Transaksi koneksi gagal diselesaikan.'
                );
            }

            return $payload['access_token'];
        } catch (\Throwable $e) {
            $db->transRollback();

            // Jangan teruskan respons mentah yang mungkin berisi token.
            throw new \RuntimeException(
                'Koneksi Microsoft belum siap. '
                . 'Periksa konfigurasi atau hubungkan ulang akun.'
            );
        }
    }

    public function send(
        string $recipient,
        string $subject,
        string $html
    ): void {
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                'Alamat penerima tidak valid.'
            );
        }

        $token = $this->accessToken();

        try {
            $response = $this->request(
                'POST',
                'https://graph.microsoft.com/v1.0/me/sendMail',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept'        => 'application/json',
                    ],
                    'json' => [
                        'message' => [
                            'subject' => $subject,
                            'body' => [
                                'contentType' => 'HTML',
                                'content'     => $html,
                            ],
                            'toRecipients' => [
                                [
                                    'emailAddress' => [
                                        'address' => $recipient,
                                    ],
                                ],
                            ],
                        ],
                        'saveToSentItems' => true,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Respons pengiriman tidak diterima. '
                . 'Periksa folder Terkirim sebelum mencoba lagi.'
            );
        }

        if ($response->getStatusCode() !== 202) {
            throw new \RuntimeException(
                'Microsoft menolak pengiriman email. HTTP '
                . $response->getStatusCode()
                . '. Periksa izin Mail.Send atau hubungkan ulang akun.'
            );
        }
    }
}