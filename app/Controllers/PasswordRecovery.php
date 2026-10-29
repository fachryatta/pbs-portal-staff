<?php

namespace App\Controllers;

use App\Libraries\PasswordRecovery as RecoveryService;

class PasswordRecovery extends BaseController
{
    private function page(string $view, array $data)
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store, private')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->setBody(view($view, $data));
    }

    public function forgot()
    {
        helper(['url', 'form']);

        return $this->page('auth/forgot_password', [
            'title'      => 'Lupa Password',
            'activePage' => 'login',
        ]);
    }

    public function requestReset()
    {
        helper('url');

        $input = $this->request->getPost('email');
        $email = is_string($input)
            ? strtolower(trim($input))
            : '';

        if (! $this->validateData(
            ['email' => $email],
            ['email' => 'required|valid_email|max_length[254]']
        )) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with('error', 'Masukkan alamat email yang valid.');
        }

        $throttler = service('throttler');

        $ipAllowed = $throttler->check(
            'password-reset-ip-'
                . hash('sha256', $this->request->getIPAddress()),
            5,
            60
        );

        if (! $ipAllowed) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with(
                    'error',
                    'Terlalu banyak permintaan. Tunggu satu menit lalu coba lagi.'
                );
        }

        $emailAllowed = $throttler->check(
            'password-reset-email-' . hash('sha256', $email),
            3,
            900
        );

        if ($emailAllowed) {
            (new RecoveryService())->send($email);
        }

        // Respons sama agar tidak mengungkap akun yang terdaftar.
        return redirect()
            ->to(site_url('forgot-password'))
            ->with(
                'success',
                'Jika email terdaftar pada akun aktif dan terverifikasi, '
                . 'tautan reset akan dikirim. Periksa Kotak Masuk atau Email Sampah. '
                . 'Jika baru meminta tautan, tunggu sebelum mencoba kembali.'
            );
    }

    public function form($token)
    {
        helper(['url', 'form']);

        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with('error', 'Tautan reset tidak valid.');
        }

        // GET hanya menampilkan form, tidak memakai/menghapus token.
        return $this->page('auth/reset_password', [
            'title'      => 'Reset Password',
            'activePage' => 'login',
            'token'      => $token,
        ]);
    }

    public function complete()
    {
        helper('url');

        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');
        $confirmation = $this->request->getPost('password_confirmation');

        $token = is_string($token) ? $token : '';
        $password = is_string($password) ? $password : '';
        $confirmation = is_string($confirmation) ? $confirmation : '';

        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with('error', 'Tautan reset tidak valid.');
        }

        if (! service('throttler')->check(
            'password-reset-complete-'
                . hash('sha256', $this->request->getIPAddress()),
            10,
            60
        )) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with('error', 'Terlalu banyak percobaan. Tunggu satu menit.');
        }

        $data = [
            'password'              => $password,
            'password_confirmation' => $confirmation,
        ];

        $rules = [
            'password' => [
                'label' => 'Password baru',
                'rules' => 'required|min_length[6]|max_length[64]',
            ],
            'password_confirmation' => [
                'label' => 'Konfirmasi password',
                'rules' => 'required|matches[password]',
            ],
        ];

        $errors = [];

        if (! $this->validateData($data, $rules)) {
            $errors = $this->validator->getErrors();
        }

        if (strlen($password) > 72 || strpos($password, "\0") !== false) {
            $errors['password'] =
                'Password maksimal 72 byte dan tidak boleh mengandung karakter null.';
        }

        if ($errors !== []) {
            // Jangan memakai withInput(): password tidak disimpan ke sesi.
            return redirect()
                ->to(site_url('reset-password/' . $token))
                ->setHeader('Referrer-Policy', 'no-referrer')
                ->setHeader('Cache-Control', 'no-store, private')
                ->with('errors', $errors);
        }

        $success = (new RecoveryService())->reset($token, $password);

        if (! $success) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with(
                    'error',
                    'Reset tidak berhasil. Tautan mungkin kedaluwarsa, '
                    . 'sudah digunakan, atau akun tidak memenuhi syarat. '
                    . 'Silakan minta tautan baru.'
                );
        }

        // Akhiri sesi browser ini dan mulai sesi tamu baru.
        session()->regenerate(true);
        session()->remove([
            'logged_in',
            'user_id',
            'full_name',
            'email',
            'access_role',
            'auth_version',
            'microsoft_mail_flow',
        ]);

        return redirect()
            ->to(site_url('login'))
            ->with(
                'success',
                'Password berhasil diubah. Silakan login dengan password baru.'
            );
    }
}