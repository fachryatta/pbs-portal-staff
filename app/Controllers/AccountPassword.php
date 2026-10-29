<?php

namespace App\Controllers;

class AccountPassword extends BaseController
{
    public function edit()
    {
        helper(['url', 'form']);

        return $this->response
            ->setHeader('Cache-Control', 'no-store, private')
            ->setBody(view('profile/password', [
                'title'      => 'Ganti Password',
                'activePage' => 'profile',
            ]));
    }

    public function update()
    {
        helper('url');

        $userId = (int) session()->get('user_id');
        $formUrl = site_url('profile/password');

        if (! service('throttler')->check(
            'change-password-user-' . $userId,
            5,
            300
        )) {
            return redirect()->to($formUrl)
                ->with(
                    'error',
                    'Terlalu banyak percobaan. Tunggu lima menit lalu coba lagi.'
                );
        }

        $data = [];

        foreach ([
            'current_password',
            'password',
            'password_confirmation',
        ] as $field) {
            $value = $this->request->getPost($field);

            // Password tidak di-trim.
            $data[$field] = is_string($value) ? $value : '';
        }

        $rules = [
            'current_password' => [
                'label' => 'Password saat ini',
                'rules' => 'required|max_length[64]',
            ],
            'password' => [
                'label' => 'Password baru',
                'rules' => 'required|min_length[6]|max_length[64]',
            ],
            'password_confirmation' => [
                'label' => 'Konfirmasi password baru',
                'rules' => 'required|matches[password]',
            ],
        ];

        $errors = [];

        if (! $this->validateData($data, $rules)) {
            $errors = $this->validator->getErrors();
        }

        foreach (['current_password', 'password'] as $field) {
            if (strlen($data[$field]) > 72
                || strpos($data[$field], "\0") !== false
            ) {
                $errors[$field] =
                    'Password maksimal 72 byte dan tidak boleh mengandung karakter null.';
            }
        }

        if ($errors !== []) {
            return redirect()->to($formUrl)
                ->with('errors', $errors);
        }

        $db = \Config\Database::connect();

        try {
            if (! $db->transBegin()) {
                throw new \RuntimeException();
            }

            $user = $db->query(
                'SELECT * FROM users WHERE id = ? FOR UPDATE',
                [$userId]
            )->getRowArray();

            // Periksa ulang akun dan versi sesi saat perubahan dilakukan.
            if (! $user
                || (int) $user['is_active'] !== 1
                || empty($user['email_verified_at'])
                || ! in_array($user['access_role'], ['admin', 'user'], true)
                || (int) $user['auth_version']
                    !== (int) session()->get('auth_version')
            ) {
                throw new \DomainException(
                    'Sesi akun sudah berubah. Silakan login kembali.'
                );
            }

            if (! password_verify(
                $data['current_password'],
                $user['password_hash']
            )) {
                throw new \DomainException(
                    'Password saat ini tidak sesuai.'
                );
            }

            if (password_verify(
                $data['password'],
                $user['password_hash']
            )) {
                throw new \DomainException(
                    'Password baru harus berbeda dari password saat ini.'
                );
            }

            $updated = $db->table('users')
                ->where('id', $userId)
                ->update([
                    'password_hash' => password_hash(
                        $data['password'],
                        PASSWORD_DEFAULT
                    ),
                    'auth_version' => (int) $user['auth_version'] + 1,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

            // Batalkan tautan lupa password yang masih tersimpan.
            $deleted = $db->table('password_reset_tokens')
                ->where('user_id', $userId)
                ->delete();

            if (! $updated
                || ! $deleted
                || $db->transStatus() === false
            ) {
                throw new \RuntimeException();
            }

            if (! $db->transCommit()) {
                throw new \RuntimeException();
            }
        } catch (\DomainException $e) {
            $db->transRollback();

            return redirect()->to($formUrl)
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            $db->transRollback();

            log_message(
                'error',
                'Perubahan password gagal untuk akun ID ' . $userId
            );

            return redirect()->to($formUrl)
                ->with(
                    'error',
                    'Password gagal diperbarui. Silakan coba kembali.'
                );
        }

        // Akhiri sesi login sekarang.
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
                'Password berhasil diganti. Silakan login dengan password baru.'
            );
    }
}