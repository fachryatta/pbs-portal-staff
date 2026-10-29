<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\EmailVerification;
use App\Libraries\StaffNotification;

class AdminAccounts extends BaseController
{
    public function index()
{
    helper(['url', 'form']);

    $model = new UserModel();

    $admins = $model
        ->select(
            'users.id, users.full_name, users.username, users.email,
             users.is_active, users.email_verified_at, users.created_at,
             staff_profiles.id AS staff_profile_id'
        )
        ->join(
            'staff_profiles',
            'staff_profiles.user_id = users.id',
            'left'
        )
        ->where('users.access_role', 'admin')
        ->orderBy('users.full_name', 'ASC')
        ->paginate(15);

    return view('admin/accounts/index', [
        'title'      => 'Kelola Admin',
        'activePage' => 'admin-management',
        'admins'     => $admins,
        'pager'      => $model->pager,
    ]);
}

    public function create()
    {
        helper(['url', 'form']);

        return view('admin/accounts/create', [
            'title'      => 'Tambah Admin',
            'activePage' => 'admin-management',
            'values'    => session()->getFlashdata('admin_account_values')
                ?? [],
        ]);
    }

    public function store()
    {
        $values = [];

        foreach (['full_name', 'username', 'email'] as $field) {
            $input = $this->request->getPost($field);
            $values[$field] = is_string($input) ? trim($input) : '';
        }

        $values['username'] = strtolower($values['username']);
        $values['email'] = strtolower($values['email']);

        $password = $this->request->getPost('password');
        $confirmation = $this->request->getPost('password_confirmation');

        $password = is_string($password) ? $password : '';
        $confirmation = is_string($confirmation) ? $confirmation : '';

        $data = array_merge($values, [
            'password'              => $password,
            'password_confirmation' => $confirmation,
        ]);

        $rules = [
            'full_name' => [
                'label' => 'Nama lengkap',
                'rules' => 'required|max_length[200]',
            ],
            'username' => [
                'label' => 'Username',
                'rules' => 'required|min_length[4]|max_length[50]'
                    . '|alpha_dash|is_unique[users.username]',
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[254]'
                    . '|is_unique[users.email]',
            ],
            'password' => [
                'label' => 'Password',
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
                'Password terlalu panjang dalam byte atau mengandung karakter tidak valid.';
        }

        if ($errors !== []) {
            return redirect()
                ->to(site_url('admin/accounts/create'))
                ->with('errors', $errors)
                ->with('admin_account_values', $values);
        }

        try {
            $model = new UserModel();

            $adminId = $model->insert([
                'full_name'         => $values['full_name'],
                'username'          => $values['username'],
                'email'             => $values['email'],
                'password_hash'     => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
                'access_role'       => 'admin',
                'email_verified_at' => null,
                'is_active'         => 1,
            ]);

            if ($adminId === false) {
                throw new \RuntimeException('Akun admin gagal disimpan.');
            }
        } catch (\Throwable $e) {
            log_message(
                'error',
                'Gagal menambahkan admin: ' . $e->getMessage()
            );

            return redirect()
                ->to(site_url('admin/accounts/create'))
                ->with(
                    'error',
                    'Akun gagal disimpan. Pastikan username dan email belum digunakan.'
                )
                ->with('admin_account_values', $values);
        }

        // Akun sudah tersimpan. Kegagalan email tidak membatalkan akun.
        try {
            $result = (new EmailVerification())->send((int) $adminId);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'Gagal mengirim verifikasi admin: ' . $e->getMessage()
            );

            $result = 'failed';
        }

        if ($result !== 'sent') {
            return redirect()
                ->to(site_url('admin/accounts'))
                ->with(
                    'success',
                    'Akun admin berhasil dibuat.'
                )
                ->with(
                    'error',
                    'Email verifikasi belum terkirim. Admin baru dapat meminta '
                    . 'pengiriman ulang melalui halaman verifikasi email.'
                );
        }

        return redirect()
            ->to(site_url('admin/accounts'))
            ->with(
                'success',
                'Admin berhasil ditambahkan. Silakan cek email admin baru '
                . 'untuk verifikasi sebelum login.'
            );
    }

    public function manage($id)
{
    helper('url');

    $destination = site_url('admin/accounts');
    $targetId = (int) $id;
    $actorId = (int) session()->get('user_id');

    $action = $this->request->getPost('action');

    if (! is_string($action)
        || ! in_array(
            $action,
            ['activate', 'deactivate', 'demote', 'delete'],
            true
        )
    ) {
        return redirect()->to($destination)
            ->with('error', 'Tindakan tidak valid.');
    }

    if ($targetId === $actorId) {
        return redirect()->to($destination)
            ->with('error', 'Anda tidak dapat mengubah akses akun sendiri.');
    }

    $db = \Config\Database::connect();
    $photoToDelete = null;
    $message = '';

    try {
        if ($db->transBegin() === false) {
            throw new \RuntimeException('Transaksi gagal dimulai.');
        }

        // Kunci baris admin selama perubahan berlangsung.
        // Ini mencegah dua admin bersamaan menghapus
        // akses admin aktif terakhir.
        $admins = $db->query(
            "SELECT id, full_name, is_active,
                    email_verified_at, profile_photo
             FROM users
             WHERE access_role = ?
             ORDER BY id
             FOR UPDATE",
            ['admin']
        )->getResultArray();

        $actor = null;
        $target = null;
        $availableAdmins = 0;

        foreach ($admins as $admin) {
            if ((int) $admin['id'] === $actorId) {
                $actor = $admin;
            }

            if ((int) $admin['id'] === $targetId) {
                $target = $admin;
            }

            if ((int) $admin['is_active'] === 1
                && ! empty($admin['email_verified_at'])
            ) {
                $availableAdmins++;
            }
        }

        // Periksa kembali akses pelaksana setelah mendapat kunci.
        if (! $actor
            || (int) $actor['is_active'] !== 1
            || empty($actor['email_verified_at'])
        ) {
            throw new \DomainException(
                'Akses admin Anda sudah berubah. Silakan login kembali.'
            );
        }

        if (! $target) {
            throw new \DomainException(
                'Akun admin tidak ditemukan atau hak aksesnya sudah berubah.'
            );
        }

        $targetIsAvailable = (int) $target['is_active'] === 1
            && ! empty($target['email_verified_at']);

        if ($action !== 'activate'
            && $targetIsAvailable
            && $availableAdmins <= 1
        ) {
            throw new \DomainException(
                'Harus tetap tersedia minimal satu admin aktif '
                . 'yang sudah terverifikasi.'
            );
        }

        $profile = $db->table('staff_profiles')
            ->where('user_id', $targetId)
            ->get()
            ->getRowArray();

        $changes = [
            'updated_at' => date('Y-m-d'),
        ];

        // Timestamp lengkap untuk kolom DATETIME.
        $changes['updated_at'] = date('Y-m-d H:i:s');

        switch ($action) {
            case 'activate':
                $changes['is_active'] = 1;
                $message = 'Akun admin berhasil diaktifkan. '
                    . 'Verifikasi email tetap diperlukan untuk login.';
                break;

            case 'deactivate':
                $changes['is_active'] = 0;
                $message = 'Akun admin berhasil dinonaktifkan. '
                    . 'Profil dan dokumennya tetap tersimpan.';
                break;

            case 'demote':
                if (! $profile) {
                    throw new \DomainException(
                        'Akun ini tidak memiliki profil staff. '
                        . 'Gunakan pilihan hapus admin jika akun tidak diperlukan.'
                    );
                }

                $changes['access_role'] = 'user';
                $message = 'Hak akses admin berhasil dicabut. '
                    . 'Akun tetap menjadi staff dengan status aktif/nonaktif sebelumnya.';
                break;

            case 'delete':
                if ($profile) {
                    throw new \DomainException(
                        'Admin yang memiliki profil staff tidak dapat dihapus '
                        . 'melalui menu ini. Gunakan Turunkan Jadi Staff.'
                    );
                }

                $documentCount = $db->table('documents')
                    ->where('user_id', $targetId)
                    ->countAllResults();

                if ($documentCount > 0) {
                    throw new \DomainException(
                        'Akun masih memiliki dokumen. '
                        . 'Nonaktifkan akun agar arsip tetap tersimpan.'
                    );
                }

                // Bersihkan token verifikasi milik akun.
                $tokensDeleted = $db->table('email_verification_tokens')
                    ->where('user_id', $targetId)
                    ->delete();

                if (! $tokensDeleted) {
                    throw new \RuntimeException(
                        'Token verifikasi gagal dihapus.'
                    );
                }

                $deleted = $db->table('users')
                    ->where('id', $targetId)
                    ->where('access_role', 'admin')
                    ->delete();

                if (! $deleted || $db->affectedRows() !== 1) {
                    throw new \RuntimeException('Akun gagal dihapus.');
                }

                $photoToDelete = $target['profile_photo'] ?? null;
                $message = 'Akun admin berhasil dihapus permanen.';
                break;
        }

        if ($action !== 'delete') {
            $updated = $db->table('users')
                ->where('id', $targetId)
                ->where('access_role', 'admin')
                ->update($changes);

            if (! $updated) {
                throw new \RuntimeException('Akses akun gagal diperbarui.');
            }
        }

        if ($action === 'demote') {
    (new StaffNotification())->send(
        $db,
        $targetId,
        $actorId,
        'role_demoted',
        'Hak akses akun Anda berubah',
        'Hak akses administrator pada akun Anda telah dicabut. '
            . 'Akun Anda tetap terdaftar sebagai staff.',
        'staff/dashboard'
    );
}

        if ($db->transStatus() === false
            || $db->transCommit() === false
        ) {
            throw new \RuntimeException('Transaksi gagal diselesaikan.');
        }
    } catch (\DomainException $e) {
        $db->transRollback();

        return redirect()->to($destination)
            ->with('error', $e->getMessage());
    } catch (\Throwable $e) {
        $db->transRollback();

        log_message(
            'error',
            'Gagal mengelola akses admin: ' . $e->getMessage()
        );

        return redirect()->to($destination)
            ->with('error', 'Perubahan gagal disimpan. Silakan coba kembali.');
    }

    // File dibersihkan setelah penghapusan akun berhasil di database.
    if (is_string($photoToDelete)
        && preg_match('/^[a-f0-9]{32}\.jpg$/', $photoToDelete)
    ) {
        $path = WRITEPATH . 'uploads/avatars/' . $photoToDelete;

        if (is_file($path) && ! @unlink($path)) {
            log_message(
                'error',
                'Foto akun admin yang dihapus gagal dibersihkan: '
                    . $photoToDelete
            );

            return redirect()->to($destination)
                ->with('success', $message)
                ->with(
                    'error',
                    'File foto lama belum berhasil dibersihkan dari penyimpanan.'
                );
        }
    }

    return redirect()->to($destination)
        ->with('success', $message);
}
}