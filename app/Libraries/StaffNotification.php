<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use CodeIgniter\Database\BaseConnection;

class StaffNotification
{
    public function send(
        BaseConnection $db,
        int $recipientId,
        ?int $actorId,
        string $type,
        string $title,
        string $message,
        ?string $targetPath = null
    ): int {
        if ($recipientId < 1) {
            throw new \InvalidArgumentException(
                'Penerima notifikasi tidak valid.'
            );
        }

        if (
            trim($type) === ''
            || mb_strlen($type) > 50
            || trim($title) === ''
            || mb_strlen($title) > 150
            || trim($message) === ''
        ) {
            throw new \InvalidArgumentException(
                'Isi notifikasi tidak valid.'
            );
        }

        // Tujuan hanya berupa path internal aplikasi.
        if (
            $targetPath !== null
            && (
                strlen($targetPath) > 255
                || !preg_match(
                    '/\A[a-zA-Z0-9_-]+(?:\/[a-zA-Z0-9_-]+)*\z/',
                    $targetPath
                )
            )
        ) {
            throw new \InvalidArgumentException(
                'Tujuan notifikasi tidak valid.'
            );
        }

        $model = new NotificationModel($db);

        $id = $model->insert([
            'user_id' => $recipientId,
            'actor_id' => $actorId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'target_path' => $targetPath,
            'read_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ], true);

        if ($id === false) {
            throw new \RuntimeException(
                'Notifikasi tidak dapat disimpan.'
            );
        }

        return (int) $id;
    }
}