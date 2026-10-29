<?php

namespace App\Controllers;

use App\Libraries\EmailVerification;
use App\Models\UserModel;

class Verification extends BaseController
{
    public function notice()
    {
        helper(['url', 'form']);

        return view('auth/verification_notice', [
            'title'      => 'Verifikasi Email',
            'activePage' => '',
        ]);
    }

    public function resend()
    {
        helper('url');

        // Batasi permintaan berdasarkan IP.
        $key = 'verification-resend-'
            . hash('sha256', $this->request->getIPAddress());

        if (! service('throttler')->check($key, 5, 60)) {
            return redirect()->to(site_url('verification'))
                ->with('error', 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.');
        }

        $input = $this->request->getPost('email');
        $email = is_string($input) ? strtolower(trim($input)) : '';

        if (! $this->validateData(
            ['email' => $email],
            ['email' => 'required|valid_email|max_length[254]']
        )) {
            return redirect()->to(site_url('verification'))
                ->with('error', 'Masukkan alamat email yang valid.');
        }

        $user = (new UserModel())->where('email', $email)->first();

        if ($user) {
            (new EmailVerification())->send((int) $user['id']);
        }

        // Pesan sama agar tidak mengumumkan akun mana yang terdaftar.
        return redirect()->to(site_url('verification'))
            ->with(
                'success',
                'Jika email terdaftar pada akun aktif yang belum terverifikasi, tautan akan dikirim. Periksa inbox dan spam. Beri jeda minimal 60 detik sebelum meminta lagi.'
            );
    }

    public function confirm(string $token)
    {
        helper(['url', 'form']);

        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return redirect()->to(site_url('verification'))
                ->with('error', 'Format tautan verifikasi tidak valid.');
        }

        $this->response->setHeader('Referrer-Policy', 'no-referrer');
        $this->response->setHeader('Cache-Control', 'no-store');

        return view('auth/verify_email', [
            'title'      => 'Konfirmasi Email',
            'activePage' => '',
            'token'      => $token,
        ]);
    }

    public function complete()
    {
        helper('url');

        $input = $this->request->getPost('token');
        $token = is_string($input) ? $input : '';

        if (! (new EmailVerification())->verify($token)) {
            return redirect()->to(site_url('verification'))
                ->with(
                    'error',
                    'Verifikasi tidak berhasil. Tautan mungkin kedaluwarsa, sudah digunakan, atau telah diganti. Jika email belum terverifikasi, minta tautan baru.'
                );
        }

        return redirect()->to(site_url('login'))
            ->with('success', 'Email berhasil diverifikasi.');
    }
}