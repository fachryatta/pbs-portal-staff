<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Notifications extends BaseController
{
    public function index()
    {
        helper(['url', 'form']);

        $userId = (int) session()->get('user_id');

        $model = new NotificationModel();

        $notifications = $model
            ->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->paginate(10);

        $unreadCount = (new NotificationModel())
            ->where('user_id', $userId)
            ->where('read_at', null)
            ->countAllResults();

        return view('notifications/index', [
            'title' => 'Notifikasi',
            'activePage' => 'notifications',
            'notifications' => $notifications,
            'pager' => $model->pager,
            'unreadCount' => $unreadCount,
        ]);
    }

    
public function open($id)
{
    helper('url');

    $notification = $this->findOwnedNotification($id);

    $allowedPaths = [
        'staff/salary-slips',
        'staff/leave-requests/history',
        'admin/leave-requests',
        'profile',
        'dashboard',
        'admin/dashboard',
        'staff/dashboard',
    ];

    $target = (string) ($notification['target_path'] ?? '');

    // Hanya path internal yang terdaftar yang diizinkan.
    if (! in_array($target, $allowedPaths, true)) {
        $target = 'notifications';
    }

    $role = session()->get('access_role');

    // Batasi tujuan berdasarkan role yang sedang aktif.
    if (
        $target === 'admin/leave-requests'
        && $role !== 'admin'
    ) {
        $target = 'dashboard';
    }

    if (
        $target === 'staff/leave-requests/history'
        && $role !== 'user'
    ) {
        $target = 'dashboard';
    }

    if (
        $target === 'admin/dashboard'
        && $role !== 'admin'
    ) {
        $target = 'dashboard';
    }

    if (
        $target === 'staff/dashboard'
        && $role !== 'user'
    ) {
        $target = 'dashboard';
    }

    // Tandai sebagai dibaca, tetapi jangan menghalangi
    // pembukaan jika notifikasi sudah dibaca.
    $this->markAsRead((int) $notification['id']);

    return redirect()->to(site_url($target));
}


    public function read($id)
    {
        helper('url');

        $notification = $this->findOwnedNotification($id);

        if (!$this->markAsRead((int) $notification['id'])) {
            return redirect()->to(site_url('notifications'))->with(
                'error',
                'Notifikasi belum dapat ditandai sebagai dibaca.'
            );
        }

        return redirect()->to(site_url('notifications'))->with(
            'success',
            'Notifikasi ditandai sebagai dibaca.'
        );
    }

    public function readAll()
    {
        helper('url');

        $success = db_connect()
            ->table('notifications')
            ->where('user_id', (int) session()->get('user_id'))
            ->where('read_at', null)
            ->update([
                'read_at' => date('Y-m-d H:i:s'),
            ]);

        return redirect()->to(site_url('notifications'))->with(
            $success ? 'success' : 'error',
            $success
                ? 'Semua notifikasi ditandai sebagai dibaca.'
                : 'Notifikasi belum dapat diperbarui.'
        );
    }

    private function findOwnedNotification($id): array
    {
        $notification = (new NotificationModel())
            ->where('user_id', (int) session()->get('user_id'))
            ->find($id);

        if (!$notification) {
            throw PageNotFoundException::forPageNotFound(
                'Notifikasi tidak ditemukan.'
            );
        }

        return $notification;
    }

    
private function markAsRead(int $id): bool
{
    $db = db_connect();

    $notification = $db->table('notifications')
        ->select('id, read_at')
        ->where('id', $id)
        ->where('user_id', (int) session()->get('user_id'))
        ->get()
        ->getRowArray();

    if (! $notification) {
        return false;
    }

    // Sudah dibaca bukan berarti gagal.
    if (! empty($notification['read_at'])) {
        return true;
    }

    $db->table('notifications')
        ->where('id', $id)
        ->where('user_id', (int) session()->get('user_id'))
        ->where('read_at', null)
        ->update([
            'read_at' => date('Y-m-d H:i:s'),
        ]);

    // Verifikasi keadaan akhir untuk menangani permintaan bersamaan.
    $updated = $db->table('notifications')
        ->select('read_at')
        ->where('id', $id)
        ->where('user_id', (int) session()->get('user_id'))
        ->get()
        ->getRowArray();

    return $updated !== null && ! empty($updated['read_at']);
}

}