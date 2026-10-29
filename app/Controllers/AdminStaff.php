<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\StaffProfileModel;
use App\Models\DocumentModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use App\Libraries\StaffNotification;

class AdminStaff extends BaseController
{
    private function staffQuery(): UserModel
    {
        return (new UserModel())
            ->join('staff_profiles', 'staff_profiles.user_id = users.id', 'left')
            ->groupStart()
                ->where('users.access_role', 'user')
                ->orGroupStart()
                    ->where('users.access_role', 'admin')
                    ->where('staff_profiles.user_id IS NOT NULL', null, false)
                ->groupEnd()
            ->groupEnd();
    }

    public function index()
    {
        helper(['url', 'form']);

        // Query ringkasan berdiri sendiri: tidak dipengaruhi filter/pagination.
        $summaryRows = $this->staffQuery()
            ->select('staff_profiles.staff_category, COUNT(users.id) AS total')
            ->groupBy('staff_profiles.staff_category')
            ->findAll();

        $staffSummary = [
            'total' => 0,
            'academic' => 0,
            'academic_support' => 0,
            'uncategorized' => 0,
        ];

        foreach ($summaryRows as $row) {
            $total = (int) $row['total'];
            $staffSummary['total'] += $total;
            $key = $row['staff_category'] ?? '';

            if (in_array($key, ['academic', 'academic_support'], true)) {
                $staffSummary[$key] += $total;
            } else {
                $staffSummary['uncategorized'] += $total;
            }
        }

        $read = function (string $field): string {
            $value = $this->request->getGet($field);
            return is_string($value) ? trim($value) : '';
        };

        $search = mb_substr($read('q'), 0, 100);
        $category = $read('category');
        $status = $read('status');

        if (! in_array($category, ['', 'academic', 'academic_support'], true)) {
            $category = '';
        }

        if (! in_array($status, ['', 'active', 'inactive', 'unverified'], true)) {
            $status = '';
        }

        $model = $this->staffQuery()->select(
            'users.id, users.full_name, users.username, users.email,
             users.access_role, users.is_active, users.email_verified_at,
             users.created_at, staff_profiles.staff_category'
        );

        if ($search !== '') {
            $model->groupStart()
                ->like('users.full_name', $search)
                ->orLike('users.username', $search)
                ->orLike('users.email', $search)
                ->groupEnd();
        }

        if ($category !== '') {
            $model->where('staff_profiles.staff_category', $category);
        }

        if ($status === 'active') {
            $model->where('users.is_active', 1);
        } elseif ($status === 'inactive') {
            $model->where('users.is_active', 0);
        } elseif ($status === 'unverified') {
            $model->where('users.email_verified_at', null);
        }

        $staff = $model
            ->orderBy('users.full_name', 'ASC')
            ->orderBy('users.id', 'ASC')
            ->paginate(10);

        return view('admin/staff/index', [
            'title'        => 'Kelola Staff',
            'activePage'   => 'staff-management',
            'staff'        => $staff,
            'pager'        => $model->pager,
            'search'       => $search,
            'category'     => $category,
            'status'       => $status,
            'staffSummary' => $staffSummary,
        ]);
    }

    private function findStaff($id): array
{
    $staff = (new UserModel())
        ->select(
            'users.id, users.full_name, users.username,
             users.email, users.profile_photo, users.access_role,
             users.is_active, users.email_verified_at,
             users.created_at'
        )
        ->join(
            'staff_profiles',
            'staff_profiles.user_id = users.id',
            'left'
        )
        ->where('users.id', $id)
        ->groupStart()
            ->where('users.access_role', 'user')
            ->orGroupStart()
                ->where('users.access_role', 'admin')
                ->where('staff_profiles.user_id IS NOT NULL', null, false)
            ->groupEnd()
        ->groupEnd()
        ->first();

    if (! $staff) {
        throw PageNotFoundException::forPageNotFound();
    }

    return $staff;
}

private function findStaffDocument($staffId, $documentId): array
{
    $this->findStaff($staffId);

    $document = (new DocumentModel())
        ->where('id', $documentId)
        ->where('user_id', $staffId)
        ->first();

    if (! $document) {
        throw PageNotFoundException::forPageNotFound();
    }

    return $document;
}

private function documentPath(array $document): string
{
    $filename = $document['stored_name'];

    if (! preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $filename)) {
        throw PageNotFoundException::forPageNotFound();
    }

    $path = WRITEPATH . 'uploads/documents/' . $filename;

    if (! is_file($path) || ! is_readable($path)) {
        throw PageNotFoundException::forPageNotFound();
    }

    return $path;
}

public function show($id)
{
    helper(['url', 'form']);

    $staff = $this->findStaff($id);
    $profileModel = new StaffProfileModel();

    $profile = $profileModel
        ->where('user_id', $staff['id'])
        ->first();

    $documents = (new DocumentModel())
        ->where('user_id', $staff['id'])
        ->orderBy('created_at', 'DESC')
        ->orderBy('id', 'DESC')
        ->findAll();

    return view('admin/staff/show', [
        'title'      => 'Detail Staff',
        'activePage' => 'staff-management',
        'staff'      => $staff,
        'profile'    => $profile ?? [],
        'isComplete' => $profileModel->isComplete($profile),
        'documents'  => $documents,
    ]);
}

public function photo($id)
{
    $staff = $this->findStaff($id);
    $filename = $staff['profile_photo'] ?? '';

    if (! preg_match('/^[a-f0-9]{32}\.jpg$/', $filename)) {
        throw PageNotFoundException::forPageNotFound();
    }

    $path = WRITEPATH . 'uploads/avatars/' . $filename;

    if (! is_file($path) || ! is_readable($path)) {
        throw PageNotFoundException::forPageNotFound();
    }

    return $this->response
        ->setContentType('image/jpeg')
        ->setHeader('X-Content-Type-Options', 'nosniff')
        ->setHeader('Cache-Control', 'no-store, private')
        ->setBody(file_get_contents($path));
}

public function previewDocument($staffId, $documentId)
{
    helper(['url', 'form']);

    $document = $this->findStaffDocument($staffId, $documentId);

    $base = 'admin/staff/' . (int) $staffId
        . '/documents/' . (int) $documentId;

    $fromAllDocuments =
    $this->request->getGet('source') === 'all-documents';
    // Gunakan kembali tampilan pratinjau dokumen yang sudah ada.
    return view('documents/preview', [
        'title'       => 'Pratinjau Dokumen Staff',
        'activePage' => $fromAllDocuments
    ? 'all-documents'
    : 'staff-management',
        'document'    => $document,
        'types'       => [
            'diploma' => 'Ijazah',
            'competency_certificate' => 'Sertifikat Kompetensi',
            'competition_certificate' => 'Sertifikat Kompetisi',
        ],
        'backUrl' => $fromAllDocuments
    ? site_url('admin/documents')
    : site_url('admin/staff/' . (int) $staffId),
        'fileUrl'     => site_url($base . '/view'),
        'downloadUrl' => site_url($base . '/download'),
        'canEdit'     => false,
    ]);
}

public function viewDocument($staffId, $documentId)
{
    $document = $this->findStaffDocument($staffId, $documentId);
    $path = $this->documentPath($document);

    return $this->response
        ->download($path, null, true)
        ->inline()
        ->setHeader('X-Content-Type-Options', 'nosniff')
        ->setHeader('Cache-Control', 'no-store, private');
}

public function downloadDocument($staffId, $documentId)
{
    $document = $this->findStaffDocument($staffId, $documentId);
    $path = $this->documentPath($document);

    return $this->response
        ->download($path, null)
        ->setFileName($document['original_name'])
        ->setHeader('X-Content-Type-Options', 'nosniff')
        ->setHeader('Cache-Control', 'no-store, private');
}

public function updateStatus($id)
{
    // Ambil akun staff, kemudian periksa hak aksesnya.
    $staff = $this->findStaff($id);

    if ($staff['access_role'] === 'admin') {
    return redirect()
        ->to(site_url('admin/staff/' . (int) $staff['id']))
        ->with(
            'error',
            'Status akun admin tidak dapat diubah melalui Kelola Staff.'
        );
}

    $status = $this->request->getPost('is_active');

    // Hanya menerima nilai status yang kita tentukan.
    if (! is_string($status)
        || ! in_array($status, ['0', '1'], true)
    ) {
        return redirect()
            ->to(site_url('admin/staff/' . (int) $staff['id']))
            ->with('error', 'Status akun tidak valid.');
    }

    $userModel = new UserModel();

    try {
        $updated = $userModel
            ->where('access_role', 'user')
            ->update((int) $staff['id'], [
                'is_active' => (int) $status,
            ]);

        if (! $updated) {
            return redirect()
                ->to(site_url('admin/staff/' . (int) $staff['id']))
                ->with('error', 'Status akun gagal diperbarui.');
        }
    } catch (\Throwable $e) {
        log_message(
            'error',
            'Gagal memperbarui status staff: ' . $e->getMessage()
        );

        return redirect()
            ->to(site_url('admin/staff/' . (int) $staff['id']))
            ->with('error', 'Terjadi kesalahan saat memperbarui status akun.');
    }

    $message = $status === '1'
        ? 'Akun staff berhasil diaktifkan kembali.'
        : 'Akun staff berhasil dinonaktifkan. Profil dan dokumen tetap tersimpan.';

    return redirect()
        ->to(site_url('admin/staff/' . (int) $staff['id']))
        ->with('success', $message);
}

public function edit($id)
{
    helper(['url', 'form']);

    $staff = $this->findStaff($id);

    $profile = (new StaffProfileModel())
        ->where('user_id', $staff['id'])
        ->first();

    $values = [
        'full_name'      => $staff['full_name'],
        'staff_category' => $profile['staff_category'] ?? '',
        'gender'         => $profile['gender'] ?? '',
        'religion'       => $profile['religion'] ?? '',
        'birth_city'     => $profile['birth_city'] ?? '',
        'birth_date'     => $profile['birth_date'] ?? '',
        'address'        => $profile['address'] ?? '',
        'phone'          => $profile['phone'] ?? '',
    ];

    // Kembalikan isian jika sebelumnya gagal validasi.
    $oldValues = session()->getFlashdata('admin_staff_values');

    if (is_array($oldValues)
        && (int) ($oldValues['staff_id'] ?? 0) === (int) $staff['id']
    ) {
        foreach ($values as $field => $value) {
            if (isset($oldValues[$field])
                && is_string($oldValues[$field])
            ) {
                $values[$field] = $oldValues[$field];
            }
        }
    }

    return view('admin/staff/edit', [
        'title'      => 'Edit Profil Staff',
        'activePage' => 'staff-management',
        'staff'      => $staff,
        'values'     => $values,
    ]);
}

public function update($id)
{
    $staff = $this->findStaff($id);
    $staffId = (int) $staff['id'];

    $fields = [
        'full_name',
        'staff_category',
        'gender',
        'religion',
        'birth_city',
        'birth_date',
        'address',
        'phone',
    ];

    $values = [];

    foreach ($fields as $field) {
        $input = $this->request->getPost($field);
        $values[$field] = is_string($input) ? trim($input) : '';
    }

    $rules = [
        'full_name' => [
            'label' => 'Nama lengkap',
            'rules' => 'required|max_length[200]',
        ],
        'staff_category' => [
            'label' => 'Kategori staff',
            'rules' => 'required|in_list[academic,academic_support]',
        ],
        'gender' => [
            'label' => 'Jenis kelamin',
            'rules' => 'permit_empty|in_list[male,female]',
        ],
        'religion' => [
            'label' => 'Agama',
            'rules' => 'permit_empty|max_length[50]',
        ],
        'birth_city' => [
            'label' => 'Kota lahir',
            'rules' => 'permit_empty|max_length[100]',
        ],
        'birth_date' => [
            'label' => 'Tanggal lahir',
            'rules' => 'permit_empty|valid_date[Y-m-d]',
        ],
        'address' => [
            'label' => 'Alamat',
            'rules' => 'permit_empty|max_length[2000]',
        ],
        'phone' => [
            'label' => 'Nomor telepon',
            'rules' => 'permit_empty|regex_match[/^\+?[0-9]{8,15}$/]',
        ],
    ];

    $errors = [];

    if (! $this->validateData($values, $rules)) {
        $errors = $this->validator->getErrors();
    }

    if (! isset($errors['birth_date'])
        && $values['birth_date'] !== ''
        && $values['birth_date'] > date('Y-m-d')
    ) {
        $errors['birth_date'] =
            'Tanggal lahir tidak boleh melebihi hari ini.';
    }

    if ($errors !== []) {
        return redirect()
            ->to(site_url('admin/staff/' . $staffId . '/edit'))
            ->with('errors', $errors)
            ->with('admin_staff_values', array_merge(
                $values,
                ['staff_id' => $staffId]
            ));
    }

    $db = \Config\Database::connect();
    $userModel = new UserModel($db);
    $profileModel = new StaffProfileModel($db);

    try {
        if ($db->transBegin() === false) {
            throw new \RuntimeException('Transaksi gagal dimulai.');
        }

        // Ambil data terkini sebelum perubahan disimpan.
$beforeUser = $db->query(
    'SELECT id, full_name
     FROM users
     WHERE id = ?
     FOR UPDATE',
    [$staffId]
)->getRowArray();

if (!$beforeUser) {
    throw new \RuntimeException('Akun staff tidak ditemukan.');
}

$beforeProfile = $db->query(
    'SELECT *
     FROM staff_profiles
     WHERE user_id = ?
     FOR UPDATE',
    [$staffId]
)->getRowArray() ?? [];

$fieldLabels = [
    'full_name' => 'nama lengkap',
    'staff_category' => 'kategori staff',
    'gender' => 'jenis kelamin',
    'religion' => 'agama',
    'birth_city' => 'kota lahir',
    'birth_date' => 'tanggal lahir',
    'address' => 'alamat',
    'phone' => 'nomor telepon',
];

$changedFields = [];

foreach ($fieldLabels as $field => $label) {
    $oldValue = $field === 'full_name'
        ? (string) $beforeUser['full_name']
        : (string) ($beforeProfile[$field] ?? '');

    $newValue = (string) ($values[$field] ?? '');

    if ($oldValue !== $newValue) {
        $changedFields[] = $label;
    }
}

        $updated = $userModel
    ->whereIn('access_role', ['user', 'admin'])
    ->update($staffId, [
        'full_name' => $values['full_name'],
    ]);

        if (! $updated) {
            throw new \RuntimeException('Nama staff gagal disimpan.');
        }

        $profileData = [
            'staff_category' => $values['staff_category'],
        ];

        foreach ([
            'gender',
            'religion',
            'birth_city',
            'birth_date',
            'address',
            'phone',
        ] as $field) {
            $profileData[$field] =
                $values[$field] === '' ? null : $values[$field];
        }

        $profile = $profileModel
            ->where('user_id', $staffId)
            ->first();

        if ($profile) {
            $saved = $profileModel->update(
                (int) $profile['id'],
                $profileData
            );
        } else {
            $profileData['user_id'] = $staffId;
            $saved = $profileModel->insert($profileData);
        }

        if ($saved === false || $db->transStatus() === false) {
    throw new \RuntimeException('Profil staff gagal disimpan.');
}

// Kirim hanya jika ada perubahan dan dilakukan admin lain.
if (
    $changedFields !== []
    && $staffId !== (int) session()->get('user_id')
) {
    (new StaffNotification())->send(
        $db,
        $staffId,
        (int) session()->get('user_id'),
        'profile_updated',
        'Profil Anda diperbarui',
        'Admin memperbarui ' . implode(', ', $changedFields)
            . ' pada profil Anda. Silakan periksa kembali.',
        'profile'
    );
}

if ($db->transStatus() === false) {
    throw new \RuntimeException('Penyimpanan notifikasi gagal.');
}

if ($db->transCommit() === false) {
    throw new \RuntimeException('Transaksi gagal diselesaikan.');
}
    } catch (\Throwable $e) {
        $db->transRollback();

        log_message(
            'error',
            'Gagal mengedit profil staff: ' . $e->getMessage()
        );

        return redirect()
            ->to(site_url('admin/staff/' . $staffId . '/edit'))
            ->with('error', 'Data gagal disimpan. Silakan coba kembali.')
            ->with('admin_staff_values', array_merge(
                $values,
                ['staff_id' => $staffId]
            ));
    }

    return redirect()
        ->to(site_url('admin/staff/' . $staffId))
        ->with('success', 'Profil staff berhasil diperbarui.');
}

public function promote($id)
{
    helper('url');

    $staff = $this->findStaff($id);
    $staffId = (int) $staff['id'];
    $detailUrl = site_url('admin/staff/' . $staffId);

    if ($staff['access_role'] !== 'user') {
        return redirect()
            ->to($detailUrl)
            ->with('error', 'Akun ini sudah memiliki hak akses admin.');
    }

    if ((int) $staff['is_active'] !== 1) {
        return redirect()
            ->to($detailUrl)
            ->with('error', 'Aktifkan akun staff sebelum mengangkatnya menjadi admin.');
    }

    if (empty($staff['email_verified_at'])) {
        return redirect()
            ->to($detailUrl)
            ->with('error', 'Staff harus memverifikasi email terlebih dahulu.');
    }

    if ($this->request->getPost('confirm_promotion') !== '1') {
        return redirect()
            ->to($detailUrl)
            ->with('error', 'Centang persetujuan pemberian hak akses admin.');
    }

    $db = \Config\Database::connect();
    $userModel = new UserModel($db);

    try {
    if ($db->transBegin() === false) {
        throw new \RuntimeException('Transaksi gagal dimulai.');
    }

    $updated = $userModel
        ->where('access_role', 'user')
        ->where('is_active', 1)
        ->where('email_verified_at IS NOT NULL', null, false)
        ->update($staffId, [
            'access_role' => 'admin',
        ]);

    if (!$updated || $db->affectedRows() !== 1) {
        throw new \DomainException(
            'Hak akses belum diubah. '
                . 'Muat ulang halaman dan periksa status akun.'
        );
    }

    (new StaffNotification())->send(
        $db,
        $staffId,
        (int) session()->get('user_id'),
        'role_promoted',
        'Anda mendapatkan akses administrator',
        'Hak akses akun Anda telah ditingkatkan menjadi administrator. '
            . 'Anda dapat mengakses menu administrasi sekolah.',
        'admin/dashboard'
    );

    if ($db->transStatus() === false) {
        throw new \RuntimeException('Penyimpanan perubahan gagal.');
    }

    if ($db->transCommit() === false) {
        throw new \RuntimeException('Transaksi gagal diselesaikan.');
    }
} catch (\DomainException $e) {
    $db->transRollback();

    return redirect()->to($detailUrl)->with(
        'error',
        $e->getMessage()
    );
} catch (\Throwable $e) {
    $db->transRollback();

    log_message(
        'error',
        'Pengangkatan admin gagal. Jenis error: ' . get_class($e)
    );

    return redirect()->to($detailUrl)->with(
        'error',
        'Perubahan hak akses belum dapat dipastikan berhasil. '
            . 'Muat ulang halaman untuk memeriksa status akun.'
    );
}

    return redirect()
        ->to($detailUrl)
        ->with(
            'success',
            $staff['full_name']
                . ' berhasil diangkat menjadi admin. '
                . 'Profil dan dokumen pribadinya tetap tersimpan.'
        );
}
}