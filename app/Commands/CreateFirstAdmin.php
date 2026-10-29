<?php

namespace App\Commands;

use App\Libraries\EmailVerification;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use RuntimeException;
use Throwable;

class CreateFirstAdmin extends BaseCommand
{
    protected $group = 'PBS';
    protected $name = 'pbs:create-first-admin';
    protected $description = 'Membuat akun admin pertama Portal Staff PBS.';

    public function run(array $params)
    {
        $db = Database::connect();
        $lockAcquired = false;
        $adminId = null;

        try {
            // Hindari dua proses setup admin berjalan bersamaan.
            $lock = $db->query(
                'SELECT GET_LOCK(?, 5) AS acquired',
                ['pbs_staff_first_admin']
            )->getRowArray();

            if ((int) ($lock['acquired'] ?? 0) !== 1) {
                CLI::error('Setup sedang digunakan proses lain. Coba lagi.');
                return;
            }

            $lockAcquired = true;

            $users = new UserModel($db);

            if ($users->where('access_role', 'admin')->countAllResults() > 0) {
                CLI::error(
                    'Admin sudah tersedia. Penambahan admin berikutnya dilakukan melalui akun admin.'
                );
                return;
            }

            $data = [
                'full_name' => trim(
                    (string) env('bootstrapAdmin.fullName', '')
                ),
                'username' => strtolower(trim(
                    (string) env('bootstrapAdmin.username', '')
                )),
                'email' => strtolower(trim(
                    (string) env('bootstrapAdmin.email', '')
                )),
                'password' => (string) env('bootstrapAdmin.password', ''),
            ];

            $validation = service('validation');
            $validation->reset();

            $validation->setRules([
                'full_name' => [
                    'label' => 'Nama admin',
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
            ]);

            if (! $validation->run($data)) {
                foreach ($validation->getErrors() as $error) {
                    CLI::error($error);
                }

                return;
            }

            if (
                strlen($data['password']) > 72
                || str_contains($data['password'], "\0")
            ) {
                CLI::error('Password terlalu panjang dalam byte atau mengandung karakter yang tidak didukung.');
                return;
            }

            $adminId = $users->insert([
                'full_name' => $data['full_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => password_hash(
                    $data['password'],
                    PASSWORD_DEFAULT
                ),
                'access_role' => 'admin',
                'email_verified_at' => null,
                'is_active' => 1,
            ]);

            unset($data['password']);

            if ($adminId === false) {
                throw new RuntimeException('Akun admin gagal disimpan.');
            }
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Setup admin pertama gagal: ' . get_class($exception)
            );

            CLI::error('Setup gagal. Periksa koneksi database dan log aplikasi.');
            return;
        } finally {
            if ($lockAcquired) {
                $db->query(
                    'SELECT RELEASE_LOCK(?)',
                    ['pbs_staff_first_admin']
                );
            }
        }

        CLI::write('Akun admin pertama berhasil dibuat.', 'green');

        $result = (new EmailVerification())->send((int) $adminId);

        if ($result === 'sent') {
            CLI::write(
                'Email verifikasi telah dikirim. Periksa inbox dan spam.',
                'green'
            );
        } else {
            CLI::write(
                'Akun sudah tersimpan. Gunakan halaman kirim ulang verifikasi jika email belum diterima.',
                'yellow'
            );
        }

        CLI::write(
            'Hapus empat pengaturan bootstrapAdmin dari .env setelah ini.',
            'yellow'
        );
    }
}