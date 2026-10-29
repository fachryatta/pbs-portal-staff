<?php

namespace App\Libraries;

use App\Models\PasswordResetTokenModel;
use Config\Database;

class PasswordRecovery
{
    public function send(string $email): void
    {
        helper('url');

        $db = Database::connect();
        $tokens = new PasswordResetTokenModel($db);

        try {
            if (! $db->transBegin()) {
                throw new \RuntimeException();
            }

            $user = $db->query(
                'SELECT * FROM users WHERE email = ? FOR UPDATE',
                [$email]
            )->getRowArray();

            if (! $user
                || (int) $user['is_active'] !== 1
                || empty($user['email_verified_at'])
                || ! in_array($user['access_role'], ['admin', 'user'], true)
            ) {
                $db->transRollback();
                return;
            }

            $existing = $tokens
                ->where('user_id', $user['id'])
                ->first();

            if ($existing
                && strtotime($existing['updated_at']) > time() - 60
            ) {
                $db->transRollback();
                return;
            }

            $token = bin2hex(random_bytes(32));

            $data = [
                'user_id'    => $user['id'],
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 1800),
            ];

            $saved = $existing
                ? $tokens->update($existing['id'], $data)
                : $tokens->insert($data);

            if ($saved === false || $db->transStatus() === false) {
                throw new \RuntimeException();
            }

            if (! $db->transCommit()) {
                throw new \RuntimeException();
            }
        } catch (\Throwable $e) {
            $db->transRollback();

            log_message(
                'error',
                'Pembuatan token reset password gagal.'
            );

            return;
        }

        // Kirim setelah transaksi selesai.
        try {
            $html = view('emails/password_reset', [
                'name' => $user['full_name'],
                'link' => site_url('reset-password/' . $token),
            ]);

            (new MicrosoftGraphMailer())->send(
                $user['email'],
                'Reset Password — Permata Bangsa School',
                $html
            );
        } catch (\Throwable $e) {
            log_message(
                'error',
                'Pengiriman reset password gagal untuk akun ID '
                    . (int) $user['id']
            );
        }
    }

    public function reset(string $token, string $password): bool
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        $db = Database::connect();
        $hash = hash('sha256', $token);

        $candidate = $db->table('password_reset_tokens')
            ->where('token_hash', $hash)
            ->get()
            ->getRowArray();

        if (! $candidate) {
            return false;
        }

        try {
            // Hitung hash sebelum mengunci baris database.
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            if (! $db->transBegin()) {
                throw new \RuntimeException();
            }

            // Urutan kunci sama dengan proses pembuatan token.
            $user = $db->query(
                'SELECT * FROM users WHERE id = ? FOR UPDATE',
                [$candidate['user_id']]
            )->getRowArray();

            $record = $db->query(
                'SELECT * FROM password_reset_tokens
                 WHERE user_id = ? AND token_hash = ? FOR UPDATE',
                [$candidate['user_id'], $hash]
            )->getRowArray();

            if (! $user
                || ! $record
                || (int) $user['is_active'] !== 1
                || empty($user['email_verified_at'])
                || ! in_array($user['access_role'], ['admin', 'user'], true)
                || $record['expires_at'] <= date('Y-m-d H:i:s')
            ) {
                $db->transRollback();
                return false;
            }

            $updated = $db->table('users')
                ->where('id', $user['id'])
                ->update([
                    'password_hash' => $passwordHash,
                    'auth_version'  => (int) $user['auth_version'] + 1,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);

            $deleted = $db->table('password_reset_tokens')
                ->where('user_id', $user['id'])
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

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();

            log_message(
                'error',
                'Penyimpanan reset password gagal.'
            );

            return false;
        }
    }
}