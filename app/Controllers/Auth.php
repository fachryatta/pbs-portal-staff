<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\StaffProfileModel;
use App\Libraries\EmailVerification;
use Config\Database;
use RuntimeException;
use Throwable;

class Auth extends BaseController
{
    public function login()
{
    helper(['url', 'form']);

    if (session()->get('logged_in')) {
        return redirect()->to(site_url('dashboard'));
    }

    return view('auth/login', [
        'title'      => 'Masuk',
        'activePage' => 'login',
    ]);
}

    public function register()
    {
        helper(['url', 'form']);

        return view('auth/register', [
            'title'      => 'Daftar Staff',
            'activePage' => 'register',
            'values'    => session()->getFlashdata('form_values') ?? [],
        ]);
    }

    public function store()
    {
        helper('url');

        // Ambil hanya input teks yang memang kita perlukan.
        $read = function (string $field): string {
            $value = $this->request->getPost($field);

            return is_string($value) ? $value : '';
        };

        $data = [
            'full_name'        => trim($read('full_name')),
            'username'         => strtolower(trim($read('username'))),
            'email'            => strtolower(trim($read('email'))),
            'staff_category'   => $read('staff_category'),
            'password'         => $read('password'),
            'password_confirm' => $read('password_confirm'),
        ];

        // Hanya data non-password yang disimpan untuk mengisi ulang form.
        $values = [
            'full_name'      => $data['full_name'],
            'username'       => $data['username'],
            'email'          => $data['email'],
            'staff_category' => $data['staff_category'],
        ];

        $rules = [
            'full_name' => [
                'label' => 'Nama lengkap',
                'rules' => 'required|max_length[200]',
            ],
            'username' => [
                'label' => 'Username',
                'rules' => 'required|min_length[4]|max_length[50]'
                    . '|alpha_dash|is_unique[users.username]',
                'errors' => [
                    'is_unique'  => 'Username sudah digunakan.',
                    'alpha_dash' => 'Username hanya boleh berisi huruf, angka, underscore, dan tanda hubung.',
                ],
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[254]'
                    . '|is_unique[users.email]',
                'errors' => [
                    'is_unique' => 'Email sudah digunakan.',
                ],
            ],
            'staff_category' => [
                'label' => 'Kategori staff',
                'rules' => 'required|in_list[academic,academic_support]',
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[6]|max_length[64]',
                'errors' => [
                    'min_length' => 'Password minimal 6 karakter.',
                    'max_length' => 'Password maksimal 64 karakter.',
                ],
            ],
            'password_confirm' => [
                'label' => 'Konfirmasi password',
                'rules' => 'required|matches[password]',
                'errors' => [
                    'matches' => 'Konfirmasi password tidak sama.',
                ],
            ],
        ];

        if (! $this->validateData($data, $rules)) {
            return redirect()->to(site_url('register'))
                ->with('errors', $this->validator->getErrors())
                ->with('form_values', $values);
        }

        // Batas byte untuk menghindari pemotongan password oleh bcrypt.
        if (
            strlen($data['password']) > 72
            || str_contains($data['password'], "\0")
        ) {
            return redirect()->to(site_url('register'))
                ->with('errors', [
                    'password' => 'Password mengandung karakter yang tidak didukung atau terlalu panjang dalam byte.',
                ])
                ->with('form_values', $values);
        }

        $db = Database::connect();
        $userModel = new UserModel($db);
        $profileModel = new StaffProfileModel($db);

        try {
            $passwordHash = password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            );

            if (! $db->transBegin()) {
                throw new RuntimeException('Transaksi gagal dimulai.');
            }

            $userId = $userModel->insert([
                'full_name'         => $data['full_name'],
                'username'          => $data['username'],
                'email'             => $data['email'],
                'password_hash'     => $passwordHash,
                'access_role'       => 'user',
                'email_verified_at' => null,
                'is_active'         => 1,
            ]);

            if ($userId === false) {
                throw new RuntimeException('Akun gagal disimpan.');
            }

            $profileId = $profileModel->insert([
                'user_id'        => $userId,
                'staff_category' => $data['staff_category'],
            ]);

            if ($profileId === false || $db->transStatus() === false) {
                throw new RuntimeException('Profil gagal disimpan.');
            }

            if (! $db->transCommit()) {
                throw new RuntimeException('Transaksi gagal diselesaikan.');
            }
        } catch (Throwable $exception) {
            $db->transRollback();

            // Jangan mencatat password atau seluruh input formulir.
            log_message(
                'error',
                'Penyimpanan registrasi gagal. Jenis: '
                    . get_class($exception)
            );

            return redirect()->to(site_url('register'))
                ->with('errors', [
                    'database' => 'Akun belum berhasil dibuat. Coba lagi; jika baru saja mendaftar, periksa apakah akun sudah tersimpan.',
                ])
                ->with('form_values', $values);
        }

        $result = (new EmailVerification())->send((int) $userId);

if ($result === 'sent') {
    return redirect()->to(site_url('verification'))
        ->with(
            'success',
            'Akun berhasil dibuat dan email verifikasi telah dikirim. Periksa inbox atau spam.'
        );
}

return redirect()->to(site_url('verification'))
    ->with(
        'error',
        'Akun sudah tersimpan, tetapi pengiriman email belum berhasil. Tunggu minimal 60 detik, lalu gunakan kirim ulang.'
    );
    }

    public function attemptLogin()
{
    helper('url');

    $usernameInput = $this->request->getPost('username');
    $passwordInput = $this->request->getPost('password');

    $username = is_string($usernameInput)
        ? strtolower(trim($usernameInput))
        : '';

    $password = is_string($passwordInput)
        ? $passwordInput
        : '';

    // Batasi percobaan login berdasarkan IP dan username.
    $throttler = service('throttler');

    $ipAllowed = $throttler->check(
        'login-ip-' . hash('sha256', $this->request->getIPAddress()),
        10,
        60
    );

    $accountAllowed = $throttler->check(
        'login-account-' . hash('sha256', $username),
        5,
        300
    );

    if (! $ipAllowed || ! $accountAllowed) {
        return redirect()->to(site_url('login'))
            ->with(
                'error',
                'Terlalu banyak percobaan login. Tunggu hingga 5 menit lalu coba lagi.'
            );
    }

    if (! $this->validateData(
        [
            'username' => $username,
            'password' => $password,
        ],
        [
            'username' => 'required|max_length[50]',
            'password' => 'required|max_length[64]',
        ]
    )) {
        return redirect()->to(site_url('login'))
            ->with('error', 'Isi username dan password dengan benar.');
    }

    if (strlen($password) > 72 || str_contains($password, "\0")) {
        return redirect()->to(site_url('login'))
            ->with('error', 'Username atau password tidak sesuai.');
    }

    $user = (new UserModel())
        ->where('username', $username)
        ->first();

    if (! $user || ! password_verify($password, $user['password_hash'])) {
        return redirect()->to(site_url('login'))
            ->with('error', 'Username atau password tidak sesuai.');
    }

    if (
        (int) $user['is_active'] !== 1
        || ! in_array($user['access_role'], ['admin', 'user'], true)
    ) {
        return redirect()->to(site_url('login'))
            ->with('error', 'Akun tidak dapat digunakan. Hubungi admin/HRD.');
    }

    if ($user['email_verified_at'] === null) {
        return redirect()->to(site_url('verification'))
            ->with('error', 'Verifikasi email terlebih dahulu sebelum login.');
    }

    // Ganti ID session setelah autentikasi berhasil.
    session()->regenerate(true);

    session()->set([
        'logged_in'  => true,
        'user_id'    => (int) $user['id'],
        'full_name'  => $user['full_name'],
        'email'      => $user['email'],
        'access_role' => $user['access_role'],
        'auth_version' => (int) $user['auth_version'],
    ]);

    return redirect()->to(site_url('dashboard'));
}

public function logout()
{
    helper('url');

    session()->destroy();

    return redirect()->to(site_url('login'));
}
}