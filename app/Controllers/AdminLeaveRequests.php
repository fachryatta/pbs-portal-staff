<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\StaffNotification;
use App\Models\StaffLeaveRequestModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class AdminLeaveRequests extends BaseController
{
    public function index()
    {
        helper(['url', 'form']);

        $read = function (string $key): string {
            $value = $this->request->getGet($key);
            return is_string($value) ? trim($value) : '';
        };

        $search = mb_substr($read('q'), 0, 100);

// Jika parameter status belum dikirim, tampilkan pengajuan pending.
// Jika admin memilih "Semua status", nilai kosong tetap diperbolehkan.
$rawStatus = $this->request->getGet('status');

$status = is_string($rawStatus)
    ? trim($rawStatus)
    : 'pending';

        if (! in_array(
            $status,
            ['', 'pending', 'approved', 'rejected'],
            true
        )) {
            $status = '';
        }

        $model = new StaffLeaveRequestModel();

        $model->select(
            'staff_leave_requests.*,
             users.full_name,
             users.email,
             staff_profiles.staff_category'
        )
            ->join(
                'users',
                'users.id = staff_leave_requests.user_id'
            )
            ->join(
                'staff_profiles',
                'staff_profiles.user_id = users.id',
                'left'
            );

        if ($search !== '') {
            $model->groupStart()
                ->like('users.full_name', $search)
                ->orLike('users.email', $search)
                ->groupEnd();
        }

        if ($status !== '') {
            $model->where('staff_leave_requests.status', $status);
        }

        $requests = $model
            ->orderBy('staff_leave_requests.created_at', 'DESC')
            ->paginate(10);

        $summaryModel = new StaffLeaveRequestModel();

        $summary = [
            'total' => (clone $summaryModel)->countAllResults(),
            'pending' => (clone $summaryModel)
                ->where('status', 'pending')
                ->countAllResults(),
            'approved' => (clone $summaryModel)
                ->where('status', 'approved')
                ->countAllResults(),
            'rejected' => (clone $summaryModel)
                ->where('status', 'rejected')
                ->countAllResults(),
        ];

        return view('admin/leave_requests/index', [
            'title' => 'Pengajuan Cuti & Izin',
            'activePage' => 'leave-management',
            'requests' => $requests,
            'pager' => $model->pager,
            'summary' => $summary,
            'search' => $search,
            'status' => $status,
            'categories' => StaffLeaveRequestModel::LEAVE_CATEGORIES,
        ]);
    }

    public function review($id)
    {
        helper('url');

        $id = filter_var($id, FILTER_VALIDATE_INT);

        if (! $id || $id < 1) {
            throw PageNotFoundException::forPageNotFound();
        }

        $decision = $this->request->getPost('decision');
        $note = $this->request->getPost('admin_note');
        $rejectionReason = $this->request->getPost('rejection_reason');

        $decision = is_string($decision) ? trim($decision) : '';
        $note = is_string($note) ? trim($note) : '';
        $rejectionReason = is_string($rejectionReason)
            ? trim($rejectionReason)
            : '';

        if (! in_array($decision, ['approved', 'rejected'], true)) {
            return redirect()->back()->with(
                'error',
                'Keputusan pengajuan tidak valid.'
            );
        }

        if (mb_strlen($note) > 2000) {
            return redirect()->back()->with(
                'error',
                'Catatan admin maksimal 2.000 karakter.'
            );
        }

        if (
            $decision === 'rejected'
            && (
                mb_strlen($rejectionReason) < 5
                || mb_strlen($rejectionReason) > 2000
            )
        ) {
            return redirect()->back()->with(
                'error',
                'Alasan penolakan wajib diisi, minimal 5 karakter.'
            );
        }

        if ($decision === 'approved') {
            $rejectionReason = '';
        } else {
            $note = '';
        }

        $db = db_connect();
        $model = new StaffLeaveRequestModel();

        $request = $model->find($id);

        if (! $request) {
            throw PageNotFoundException::forPageNotFound(
                'Pengajuan tidak ditemukan.'
            );
        }

        if ($request['status'] !== 'pending') {
            return redirect()->back()->with(
                'error',
                'Pengajuan ini sudah diproses sebelumnya.'
            );
        }

        $db->transBegin();

        try {
            $updated = $db->table('staff_leave_requests')
                ->where('id', $id)
                ->where('status', 'pending')
                ->update([
                    'status' => $decision,
                    'admin_note' => $decision === 'approved'
                        ? ($note !== '' ? $note : null)
                        : null,
                    'rejection_reason' => $decision === 'rejected'
                        ? $rejectionReason
                        : null,
                    'reviewed_by' => (int) session()->get('user_id'),
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if (! $updated || $db->affectedRows() !== 1) {
                throw new \RuntimeException(
                    'Pengajuan sudah diproses atau gagal diperbarui.'
                );
            }

            $statusLabel = $decision === 'approved'
                ? 'disetujui'
                : 'ditolak';

            $message = $decision === 'approved'
                ? 'Pengajuan cuti/izin Anda telah disetujui.'
                : 'Pengajuan cuti/izin Anda ditolak. Alasan: '
                    . $rejectionReason;

            (new StaffNotification())->send(
                $db,
                (int) $request['user_id'],
                (int) session()->get('user_id'),
                'leave_request_' . $decision,
                'Pengajuan Cuti & Izin ' . ucfirst($statusLabel),
                $message,
                'staff/leave-requests/history'
            );

            if ($db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi pengajuan tidak berhasil.'
                );
            }

            $db->transCommit();

            return redirect()->to(site_url('admin/leave-requests'))
                ->with(
                    'success',
                    'Pengajuan berhasil '
                        . $statusLabel
                        . ' dan staf telah diberi notifikasi.'
                );
        } catch (\Throwable $e) {
            $db->transRollback();

            log_message(
                'error',
                'Gagal memproses pengajuan cuti/izin ID {id}: {error}',
                [
                    'id' => $id,
                    'error' => $e->getMessage(),
                ]
            );

            return redirect()->back()->with(
                'error',
                'Pengajuan belum berhasil diproses. Periksa kembali dan coba lagi.'
            );
        }
    }
}
