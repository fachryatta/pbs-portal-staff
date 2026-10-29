<?php

namespace App\Controllers;

use App\Models\StaffLeaveRequestModel;
use App\Libraries\StaffNotification;
use CodeIgniter\Database\BaseConnection;

class StaffLeaveRequests extends BaseController
{
    public function index()
    {
        helper(['url', 'form']);

        return view('staff_leave_requests/create', [
            'title' => 'Pengajuan Cuti & Izin',
            'activePage' => 'leave-requests',
            'categories' => StaffLeaveRequestModel::LEAVE_CATEGORIES,
            'values' => session()->getFlashdata('leave_values') ?? [],
        ]);
    }

    public function store()
    {
        helper('url');

        $requestType = $this->request->getPost('request_type');
        $category = $this->request->getPost('leave_category');

        $requestType = is_string($requestType)
            ? trim($requestType)
            : '';

        $category = is_string($category)
            ? trim($category)
            : '';

        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');
        $reason = $this->request->getPost('reason');

        $startDate = is_string($startDate) ? trim($startDate) : '';
        $endDate = is_string($endDate) ? trim($endDate) : '';
        $reason = is_string($reason) ? trim($reason) : '';

        $values = [
            'request_type' => $requestType,
            'leave_category' => $category,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
        ];

        $rules = [
            'request_type' => [
                'label' => 'Jenis pengajuan',
                'rules' => 'required|in_list[cuti,izin]',
            ],
            'leave_category' => [
                'label' => 'Kategori cuti',
                'rules' => 'permit_empty|in_list[hamil,menikah,menikahkan_anak,duka]',
            ],
            'start_date' => [
                'label' => 'Tanggal mulai',
                'rules' => 'required|valid_date[Y-m-d]',
            ],
            'end_date' => [
                'label' => 'Tanggal selesai',
                'rules' => 'required|valid_date[Y-m-d]',
            ],
            'reason' => [
                'label' => 'Alasan pengajuan',
                'rules' => 'required|min_length[10]|max_length[2000]',
            ],
        ];

        if (! $this->validateData($values, $rules)) {
            return redirect()->to(site_url('staff/leave-requests'))
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('leave_values', $values);
        }
 
$db = db_connect();
$model = new StaffLeaveRequestModel($db);

try {
    if ($db->transBegin() === false) {
        throw new \RuntimeException(
            'Transaksi pengajuan gagal dimulai.'
        );
    }

    // Simpan pengajuan terlebih dahulu di dalam transaksi.
    $requestId = $model->insert([
        'user_id' => (int) session()->get('user_id'),
        'request_type' => $requestType,
        'leave_category' => $requestType === 'cuti'
            ? $category
            : null,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'reason' => $reason,
        'status' => StaffLeaveRequestModel::STATUS_PENDING,
    ]);

    if ($requestId === false) {
        throw new \RuntimeException(
            'Data pengajuan gagal disimpan.'
        );
    }

    // Cari semua admin aktif yang emailnya telah diverifikasi.
    $admins = $db->table('users')
        ->select('id')
        ->where('access_role', 'admin')
        ->where('is_active', 1)
        ->where('email_verified_at IS NOT NULL', null, false)
        ->orderBy('id', 'ASC')
        ->get()
        ->getResultArray();

    if ($admins === []) {
        throw new \RuntimeException(
            'Tidak ditemukan admin aktif yang terverifikasi.'
        );
    }

    $staffName = trim((string) session()->get('full_name'));

    if ($staffName === '') {
        $staffName = 'Seorang staf';
    }

    $typeLabel = $requestType === 'cuti'
        ? (StaffLeaveRequestModel::LEAVE_CATEGORIES[$category]
            ?? 'Cuti')
        : 'Permohonan Izin';

    $period = date('d/m/Y', strtotime($startDate))
        . ' - '
        . date('d/m/Y', strtotime($endDate));

    $notification = new StaffNotification();

    foreach ($admins as $admin) {
        $recipientId = (int) $admin['id'];

        // Hindari mengirim notifikasi kepada akun yang mengajukan
        // jika suatu saat admin juga menggunakan fitur staf.
        if ($recipientId === (int) session()->get('user_id')) {
            continue;
        }

        $notification->send(
            $db,
            $recipientId,
            (int) session()->get('user_id'),
            'leave_request_submitted',
            'Pengajuan Cuti & Izin Baru',
            $staffName . ' mengajukan '
                . $typeLabel . ' untuk periode ' . $period . '.',
            'admin/leave-requests'
        );
    }

    if ($db->transStatus() === false) {
        throw new \RuntimeException(
            'Transaksi pengajuan atau notifikasi gagal.'
        );
    }

    if ($db->transCommit() === false) {
        throw new \RuntimeException(
            'Transaksi pengajuan gagal diselesaikan.'
        );
    }
} catch (\Throwable $e) {
    $db->transRollback();

    log_message(
        'error',
        'Gagal menyimpan pengajuan cuti/izin dan notifikasi: {error}',
        ['error' => $e->getMessage()]
    );

    return redirect()
        ->to(site_url('staff/leave-requests'))
        ->withInput()
        ->with('error',
            'Pengajuan belum berhasil dikirim. Silakan coba lagi.'
        )
        ->with('leave_values', $values);
}

return redirect()
    ->to(site_url('staff/leave-requests'))
    ->with(
        'success',
        'Pengajuan berhasil dikirim. Admin/HRD telah diberi notifikasi.'
    );

    }

public function history()
{
    helper('url');

    $userId = (int) session()->get('user_id');

    $model = new \App\Models\StaffLeaveRequestModel();

    $requests = $model
        ->where('user_id', $userId)
        ->orderBy('created_at', 'DESC')
        ->paginate(5);

    return view('staff_leave_requests/history', [
        'title' => 'Riwayat Cuti & Izin',
        'activePage' => 'leave-requests',
        'requests' => $requests,
        'pager' => $model->pager,
        'categories' =>
            \App\Models\StaffLeaveRequestModel::LEAVE_CATEGORIES,
    ]);
}

}
