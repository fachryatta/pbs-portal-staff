<?php

namespace App\Libraries;

use App\Models\EmailVerificationTokenModel;
use Config\Database;
use RuntimeException;
use Throwable;

class EmailVerification
{
    public function send(int $userId): string
    {
        helper('url');

        $db = Database::connect();
        $tokens = new EmailVerificationTokenModel($db);

        try {
            if (! $db->transBegin()) {
                throw new RuntimeException('Transaksi gagal dimulai.');
            }

            // Kunci akun agar permintaan bersamaan tidak membuat dua token.
            $user = $db->query(
                'SELECT * FROM users WHERE id = ? FOR UPDATE',
                [$userId]
            )->getRowArray();

            if (
                ! $user
                || (int) $user['is_active'] !== 1
                || $user['email_verified_at'] !== null
            ) {
                $db->transRollback();
                return 'skip';
            }

            $existing = $tokens->where('user_id', $userId)->first();

            // Beri jeda minimal 60 detik sebelum membuat token baru.
            if (
                $existing
                && strtotime($existing['updated_at']) > time() - 60
            ) {
                $db->transRollback();
                return 'wait';
            }

            $token = bin2hex(random_bytes(32));

            $data = [
                'user_id'    => $userId,
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 86400),
            ];

            if ($existing) {
                $saved = $tokens->update($existing['id'], $data);
            } else {
                $saved = $tokens->insert($data);
            }

            if ($saved === false || $db->transStatus() === false) {
                throw new RuntimeException('Token gagal disimpan.');
            }

            if (! $db->transCommit()) {
                throw new RuntimeException('Transaksi gagal diselesaikan.');
            }
        } catch (Throwable $exception) {
            $db->transRollback();

            log_message(
                'error',
                'Pembuatan token verifikasi gagal: '
                    . get_class($exception)
            );

            return 'failed';
        }

        // Kirim melalui Microsoft setelah transaksi database selesai.
try {
    $link = site_url('verify-email/' . $token);

    $html = view('emails/verification', [
        'name' => $user['full_name'],
        'link' => $link,
    ]);

    (new MicrosoftGraphMailer())->send(
        $user['email'],
        'Verifikasi Email — Permata Bangsa School',
        $html
    );

    return 'sent';
} catch (Throwable $exception) {
    log_message(
        'error',
        'Pengiriman verifikasi melalui Microsoft gagal untuk akun ID '
            . $userId
            . '. Jenis kesalahan: '
            . get_class($exception)
    );

    return 'failed';
}
    }

    public function verify(string $token): bool
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        $db = Database::connect();
        $hash = hash('sha256', $token);

        $candidate = $db->table('email_verification_tokens')
            ->where('token_hash', $hash)
            ->get()
            ->getRowArray();

        if (! $candidate) {
            return false;
        }

        try {
            if (! $db->transBegin()) {
                throw new RuntimeException('Transaksi gagal dimulai.');
            }

            // Urutan penguncian sama dengan proses pengiriman token.
            $user = $db->query(
                'SELECT * FROM users WHERE id = ? FOR UPDATE',
                [$candidate['user_id']]
            )->getRowArray();

            $record = $db->query(
                'SELECT * FROM email_verification_tokens
                 WHERE user_id = ? AND token_hash = ? FOR UPDATE',
                [$candidate['user_id'], $hash]
            )->getRowArray();

            if (
                ! $user
                || ! $record
                || (int) $user['is_active'] !== 1
                || $user['email_verified_at'] !== null
                || $record['expires_at'] <= date('Y-m-d H:i:s')
            ) {
                $db->transRollback();
                return false;
            }

            $now = date('Y-m-d H:i:s');

            $updated = $db->table('users')
                ->where('id', $user['id'])
                ->update([
                    'email_verified_at' => $now,
                    'updated_at'        => $now,
                ]);

            $deleted = $db->table('email_verification_tokens')
                ->where('id', $record['id'])
                ->delete();

            if (
                ! $updated
                || ! $deleted
                || $db->transStatus() === false
            ) {
                throw new RuntimeException('Verifikasi gagal disimpan.');
            }

            if (! $db->transCommit()) {
                throw new RuntimeException('Transaksi gagal diselesaikan.');
            }

            return true;
        } catch (Throwable $exception) {
            $db->transRollback();

            log_message(
                'error',
                'Penyimpanan verifikasi email gagal: '
                    . get_class($exception)
            );

            return false;
        }
    }
}