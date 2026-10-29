<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Database;
use Config\Services;
use RuntimeException;
use Throwable;

class ProfilePhoto extends BaseController
{
    public function show()
    {
        $user = (new UserModel())->find(
            (int) session()->get('user_id')
        );

        $filename = $user['profile_photo'] ?? '';

        // Hanya menerima nama file yang dibuat oleh aplikasi.
        if (! preg_match('/^[a-f0-9]{32}\.jpg$/', $filename)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $path = WRITEPATH . 'uploads/avatars/' . $filename;

        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setContentType('image/jpeg')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'no-store, private')
            ->setBody(file_get_contents($path));
    }

    public function upload()
    {
        helper('url');

        $rules = [
            'photo' => [
                'label' => 'Foto profil',
                'rules' => [
                    'uploaded[photo]',
                    'is_image[photo]',
                    'mime_in[photo,image/jpeg,image/png]',
                    'ext_in[photo,jpg,jpeg,png]',
                    'max_size[photo,2048]',
                    'max_dims[photo,2048,2048]',
                ],
                'errors' => [
                    'uploaded' => 'Pilih foto terlebih dahulu.',
                    'is_image' => 'File harus berupa gambar yang valid.',
                    'mime_in'  => 'Foto harus berformat JPG atau PNG.',
                    'ext_in'   => 'Ekstensi foto harus jpg, jpeg, atau png.',
                    'max_size' => 'Ukuran foto maksimal 2 MB.',
                    'max_dims' => 'Dimensi foto maksimal 2048 × 2048 piksel.',
                ],
            ],
        ];

        if (! $this->validateData([], $rules)) {
            return redirect()->to(site_url('profile'))
                ->with('errors', $this->validator->getErrors());
        }

        if (! extension_loaded('gd')) {
            return redirect()->to(site_url('profile'))
                ->with('error', 'Pemrosesan foto belum tersedia. Hubungi admin.');
        }

        $file = $this->request->getFile('photo');
        $userId = (int) session()->get('user_id');

        $directory = WRITEPATH . 'uploads/avatars/';
        $newPath = null;
        $oldFilename = null;
        $transactionStarted = false;

        $db = Database::connect();
        $users = new UserModel($db);

        try {
            if (
                ! is_dir($directory)
                && ! mkdir($directory, 0750, true)
                && ! is_dir($directory)
            ) {
                throw new RuntimeException('Folder foto gagal dibuat.');
            }

            $filename = bin2hex(random_bytes(16)) . '.jpg';
            $newPath = $directory . $filename;

            $image = Services::image('gd');

            $image->withFile($file->getTempName());

            // Sesuaikan orientasi foto jika dukungan EXIF tersedia.
            if (function_exists('exif_read_data')) {
                $image->reorient();
            }

            $image->fit(400, 400, 'center')
                ->convert(IMAGETYPE_JPEG)
                ->save($newPath, 85);

            if (! is_file($newPath)) {
                throw new RuntimeException('Foto hasil pemrosesan tidak ditemukan.');
            }

            if (! $db->transBegin()) {
                throw new RuntimeException('Transaksi gagal dimulai.');
            }

            $transactionStarted = true;

            // Ambil foto terbaru sambil mengunci akun.
            $user = $db->query(
                'SELECT profile_photo FROM users WHERE id = ? FOR UPDATE',
                [$userId]
            )->getRowArray();

            if (! $user) {
                throw new RuntimeException('Akun tidak ditemukan.');
            }

            $oldFilename = $user['profile_photo'];

            $saved = $users->update($userId, [
                'profile_photo' => $filename,
            ]);

            if ($saved === false || $db->transStatus() === false) {
                throw new RuntimeException('Data foto gagal disimpan.');
            }

            if (! $db->transCommit()) {
                throw new RuntimeException('Transaksi gagal diselesaikan.');
            }

            $transactionStarted = false;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                $db->transRollback();
            }

            // Bersihkan foto baru jika proses penyimpanannya gagal.
            if ($newPath !== null && is_file($newPath)) {
                if (! @unlink($newPath)) {
                    log_message('error', 'Foto baru yang gagal disimpan belum dapat dibersihkan.');
                }
            }

            log_message(
                'error',
                'Upload foto profil gagal: ' . get_class($exception)
            );

            return redirect()->to(site_url('profile'))
                ->with('error', 'Foto belum berhasil disimpan. Silakan coba lagi.');
        }

        // Foto lama dihapus setelah foto baru berhasil disimpan.
        if (
            is_string($oldFilename)
            && preg_match('/^[a-f0-9]{32}\.jpg$/', $oldFilename)
        ) {
            $oldPath = $directory . $oldFilename;

            if (is_file($oldPath) && ! @unlink($oldPath)) {
                log_message('error', 'Foto profil lama belum dapat dibersihkan.');
            }
        }

        return redirect()->to(site_url('profile'))
            ->with('success', 'Foto profil berhasil diperbarui.');
    }
}