<?php

namespace App\Controllers;

use App\Models\SalarySlipModel;
use App\Models\SalarySlipItemModel;
use App\Models\UserModel;
use App\Libraries\StaffNotification;

class AdminSalarySlips extends BaseController
{
    public function index()
{
    helper(['url', 'form']);

    $readQuery = function (string $key): string {
        $value = $this->request->getGet($key);

        return is_string($value) ? trim($value) : '';
    };

    $search = mb_substr($readQuery('search'), 0, 200);
    $month = $readQuery('month');
    $year = $readQuery('year');
    $status = $readQuery('status');

    // Normalisasi filter bulan.
    if (
        !preg_match('/\A[0-9]{1,2}\z/', $month)
        || (int) $month < 1
        || (int) $month > 12
    ) {
        $month = '';
    } else {
        $month = (string) (int) $month;
    }

    // Rentang tahun sesuai form pembuatan slip.
    if (
        !preg_match('/\A[0-9]{4}\z/', $year)
        || (int) $year < 2000
        || (int) $year > 2100
    ) {
        $year = '';
    }

    if (!in_array($status, ['draft', 'published'], true)) {
        $status = '';
    }

    $model = new SalarySlipModel();

    if ($search !== '') {
        $model->like('staff_name', $search);
    }

    if ($month !== '') {
        $model->where('period_month', (int) $month);
    }

    if ($year !== '') {
        $model->where('period_year', (int) $year);
    }

    if ($status !== '') {
        $model->where('status', $status);
    }

    $slips = $model
        ->orderBy('period_year', 'DESC')
        ->orderBy('period_month', 'DESC')
        ->orderBy('id', 'DESC')
        ->paginate(10);

    return view('admin/salary_slips/index', [
        'title' => 'Kelola Slip Gaji',
        'activePage' => 'salary-management',
        'slips' => $slips,
        'pager' => $model->pager,
        'filters' => [
            'search' => $search,
            'month' => $month,
            'year' => $year,
            'status' => $status,
        ],
        'hasFilters' => $search !== ''
            || $month !== ''
            || $year !== ''
            || $status !== '',
    ]);
}

    private function staffQuery(): UserModel
{
    return (new UserModel())
        ->select(
            'users.id, users.full_name, users.username,
             users.access_role, staff_profiles.staff_category'
        )
        ->join(
            'staff_profiles',
            'staff_profiles.user_id = users.id',
            'left'
        )
        ->groupStart()
            ->where('users.access_role', 'user')
            ->orGroupStart()
                ->where('users.access_role', 'admin')
                ->where(
                    'staff_profiles.user_id IS NOT NULL',
                    null,
                    false
                )
            ->groupEnd()
        ->groupEnd();
}

public function create()
{
    helper(['url', 'form']);

    $defaultValues = [
        'staff_ids' => [],
        'period_month' => date('n'),
        'period_year' => date('Y'),
        'basic_salary' => '0',
        'notes' => '',
        'items' => [
            [
                'item_type' => 'earning',
                'label' => 'Tunjangan',
                'amount' => '0',
            ],
            [
                'item_type' => 'earning',
                'label' => 'Tunjangan lain-lain',
                'amount' => '0',
            ],
            [
                'item_type' => 'earning',
                'label' => 'Overtime',
                'amount' => '0',
            ],
            [
                'item_type' => 'deduction',
                'label' => 'BPJS Tenagakerja',
                'amount' => '0',
            ],
            [
                'item_type' => 'deduction',
                'label' => 'BPJS Kesehatan',
                'amount' => '0',
            ],
        ],
    ];

    $previousValues = session()->getFlashdata('salary_values');

    // Jika validasi gagal, pertahankan isian admin sebelumnya.
    $values = is_array($previousValues)
        ? $previousValues
        : $defaultValues;

    $values['staff_ids'] = $values['staff_ids'] ?? [];
    $values['items'] = $values['items'] ?? [];

    return view('admin/salary_slips/create', [
        'title' => 'Buat Slip Gaji',
        'activePage' => 'salary-management',
        'isEdit' => false,
        'staff' => $this->staffQuery()
            ->orderBy('users.full_name', 'ASC')
            ->findAll(),
        'values' => $values,
    ]);
}

// Nominal berupa rupiah bulat, maksimal Rp100 juta per komponen.
private function parseAmount(string $value): ?int
{
    if (! preg_match('/\A[0-9]{1,9}\z/', $value)) {
        return null;
    }

    $amount = (int) $value;

    return $amount <= 100000000 ? $amount : null;
}

public function store()
{
    helper('url');

    $read = function (string $name): string {
        $value = $this->request->getPost($name);

        return is_string($value) ? trim($value) : '';
    };

    $errors = [];
    $submittedIds = $this->request->getPost('staff_ids');
    $staffIds = [];

    if (
        ! is_array($submittedIds)
        || count($submittedIds) === 0
        || count($submittedIds) > 500
    ) {
        $errors['staff_ids'] = 'Pilih minimal satu staff, maksimal 500 staff.';
    } else {
        foreach ($submittedIds as $id) {
            if (
                ! is_string($id)
                || ! preg_match('/\A[1-9][0-9]{0,9}\z/', $id)
            ) {
                $errors['staff_ids'] = 'Pilihan staff tidak valid.';
                continue;
            }

            $staffIds[] = $id;
        }
    }

    $staffIds = array_values(array_unique($staffIds));

    $values = [
        'staff_ids'    => $staffIds,
        'period_month' => $read('period_month'),
        'period_year'  => $read('period_year'),
        'basic_salary' => $read('basic_salary'),
        'notes'        => $read('notes'),
        'items'        => [],
    ];

    if (! $this->validateData($values, [
        'period_month' => 'required|integer|greater_than_equal_to[1]'
            . '|less_than_equal_to[12]',
        'period_year'  => 'required|integer|greater_than_equal_to[2000]'
            . '|less_than_equal_to[2100]',
        'notes'        => 'permit_empty|max_length[2000]',
    ])) {
        $errors = array_merge($errors, $this->validator->getErrors());
    }

    $basicSalary = $this->parseAmount($values['basic_salary']);

    if ($basicSalary === null) {
        $errors['basic_salary'] =
            'Gaji pokok harus berupa angka bulat 0–100000000, tanpa titik atau koma.';
    }

    $submittedItems = $this->request->getPost('items') ?? [];

    if (! is_array($submittedItems) || count($submittedItems) > 20) {
        $errors['items'] = 'Maksimal 20 baris komponen tambahan.';
        $submittedItems = [];
    }

    $items = [];
    $totalEarnings = $basicSalary ?? 0;
    $totalDeductions = 0;

    foreach ($submittedItems as $row) {
        if (! is_array($row)) {
            $errors['items'] = 'Format komponen tidak valid.';
            continue;
        }

        $type = is_string($row['item_type'] ?? null)
            ? $row['item_type']
            : '';

        $label = is_string($row['label'] ?? null)
            ? trim($row['label'])
            : '';

        $amountText = is_string($row['amount'] ?? null)
            ? trim($row['amount'])
            : '';

        $values['items'][] = [
            'item_type' => in_array($type, ['earning', 'deduction'], true)
                ? $type
                : 'earning',
            'label'  => mb_substr($label, 0, 100),
            'amount' => mb_substr($amountText, 0, 30),
        ];

        $amount = $this->parseAmount($amountText);

        if (
            ! in_array($type, ['earning', 'deduction'], true)
            || $label === ''
            || mb_strlen($label) > 100
            || $amount === null
        ) {
            $errors['items'] =
                'Lengkapi nama komponen maksimal 100 karakter dan '
                . 'nominal bulat 0–100000000. Hapus baris yang tidak dipakai.';

            continue;
        }

        $items[] = [
            'item_type' => $type,
            'label'      => $label,
            'amount'     => $amount,
            'sort_order' => count($items),
        ];

        if ($type === 'earning') {
            $totalEarnings += $amount;
        } else {
            $totalDeductions += $amount;
        }
    }

    if ($totalDeductions > $totalEarnings) {
        $errors['total'] = 'Total potongan tidak boleh melebihi total pendapatan.';
    }

    if ($errors !== []) {
        return redirect()->to(site_url('admin/salary-slips/create'))
            ->with('errors', $errors)
            ->with('salary_values', $values);
    }

    $db = \Config\Database::connect();
    $savedCount = 0;

    try {
        if (! $db->transBegin()) {
            throw new \RuntimeException('Transaksi gagal dimulai.');
        }

        // Kunci penerima dengan urutan tetap selama proses penyimpanan.
        // Nilai ID dikirim sebagai parameter query, bukan ditempel ke SQL.
        $placeholders = implode(',', array_fill(0, count($staffIds), '?'));

        $db->query(
            'SELECT id FROM users WHERE id IN (' . $placeholders . ')
             ORDER BY id FOR UPDATE',
            $staffIds
        );

        $recipients = $this->staffQuery()
            ->whereIn('users.id', $staffIds)
            ->orderBy('users.id', 'ASC')
            ->findAll();

        if (count($recipients) !== count($staffIds)) {
            throw new \DomainException(
                'Sebagian staff tidak ditemukan atau status aksesnya berubah. '
                . 'Muat ulang form dan pilih staff kembali.'
            );
        }

        $slipModel = new SalarySlipModel($db);
        $itemModel = new SalarySlipItemModel($db);

        // Periksa semua penerima sebelum membuat slip pertama.
        $existing = $slipModel
            ->whereIn('user_id', $staffIds)
            ->where('period_month', (int) $values['period_month'])
            ->where('period_year', (int) $values['period_year'])
            ->findAll();

        if ($existing !== []) {
            $names = array_map(
                static fn ($slip) => $slip['staff_name'],
                array_slice($existing, 0, 5)
            );

            $extra = count($existing) > 5
                ? ' dan ' . (count($existing) - 5) . ' staff lainnya'
                : '';

            throw new \DomainException(
                'Belum ada draft baru yang disimpan. Slip periode ini sudah ada untuk: '
                . implode(', ', $names)
                . $extra
                . '. Hapus centang staff tersebut atau ubah periode.'
            );
        }

        foreach ($recipients as $person) {
            $slipId = $slipModel->insert([
                'user_id'        => (int) $person['id'],
                'period_month'   => (int) $values['period_month'],
                'period_year'    => (int) $values['period_year'],
                'staff_name'     => $person['full_name'],
                'staff_username' => $person['username'],
                'staff_category' => $person['staff_category'],
                'basic_salary'   => $basicSalary,
                'notes'          => $values['notes'] !== ''
                    ? $values['notes']
                    : null,
                'status'         => 'draft',
                'created_by'     => (int) session()->get('user_id'),
            ]);

            if ($slipId === false) {
                throw new \RuntimeException('Slip gagal disimpan.');
            }

            foreach ($items as $item) {
                $item['salary_slip_id'] = (int) $slipId;

                if ($itemModel->insert($item) === false) {
                    throw new \RuntimeException('Komponen gagal disimpan.');
                }
            }

            $savedCount++;
        }

        if ($db->transStatus() === false || ! $db->transCommit()) {
            throw new \RuntimeException('Transaksi gagal diselesaikan.');
        }
    } catch (\DomainException $exception) {
        $db->transRollback();

        return redirect()->to(site_url('admin/salary-slips/create'))
            ->with('error', $exception->getMessage())
            ->with('salary_values', $values);
    } catch (\Throwable $exception) {
        $db->transRollback();

        log_message(
            'error',
            'Penyimpanan draft slip beberapa staff gagal: '
                . get_class($exception)
        );

        return redirect()->to(site_url('admin/salary-slips/create'))
            ->with(
                'error',
                'Penyimpanan belum dapat dipastikan berhasil. '
                . 'Periksa daftar slip sebelum mencoba kembali.'
            )
            ->with('salary_values', $values);
    }

    $netSalary = $totalEarnings - $totalDeductions;

    return redirect()->to(site_url('admin/salary-slips'))
        ->with(
            'success',
            $savedCount . ' draft slip berhasil disimpan. '
                . 'Gaji bersih setiap staff: Rp '
                . number_format($netSalary, 0, ',', '.')
                . '. Slip belum diterbitkan.'
        );
}

public function show($id)
{
    helper(['url', 'form']);

    $slip = (new SalarySlipModel())->find($id);

    if (! $slip) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
            'Slip gaji tidak ditemukan.'
        );
    }

    $items = (new SalarySlipItemModel())
        ->where('salary_slip_id', $slip['id'])
        ->orderBy('sort_order', 'ASC')
        ->orderBy('id', 'ASC')
        ->findAll();

    $earnings = [];
    $deductions = [];

    $totalEarnings = (int) $slip['basic_salary'];
    $totalDeductions = 0;

    foreach ($items as $item) {
        $amount = (int) $item['amount'];

        if ($item['item_type'] === 'earning') {
            $earnings[] = $item;
            $totalEarnings += $amount;
        } elseif ($item['item_type'] === 'deduction') {
            $deductions[] = $item;
            $totalDeductions += $amount;
        }
    }

    return view('admin/salary_slips/show', [
        'title'           => 'Rincian Slip Gaji',
        'activePage'      => 'salary-management',
        'slip'            => $slip,
        'earnings'        => $earnings,
        'deductions'      => $deductions,
        'totalEarnings'   => $totalEarnings,
        'totalDeductions' => $totalDeductions,
        'netSalary'       => $totalEarnings - $totalDeductions,
    ]);
}

public function edit($id)
{
    helper(['url', 'form']);

    $slip = (new SalarySlipModel())->find($id);

    if (! $slip) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    if ($slip['status'] !== 'draft') {
        return redirect()
            ->to(site_url('admin/salary-slips/' . (int) $slip['id']))
            ->with('error', 'Hanya slip berstatus draft yang dapat diedit.');
    }

    $items = (new SalarySlipItemModel())
        ->select('item_type, label, amount')
        ->where('salary_slip_id', $slip['id'])
        ->orderBy('sort_order', 'ASC')
        ->orderBy('id', 'ASC')
        ->findAll();

    $values = [
        'basic_salary' => $slip['basic_salary'],
        'notes'        => $slip['notes'] ?? '',
        'items'        => $items,
    ];

    $previous = session()->getFlashdata('salary_edit_values');

    if (
        is_array($previous)
        && (int) ($previous['slip_id'] ?? 0) === (int) $slip['id']
    ) {
        $values = array_merge($values, $previous);
    }

    return view('admin/salary_slips/create', [
        'title'      => 'Edit Draft Slip Gaji',
        'activePage' => 'salary-management',
        'isEdit'     => true,
        'slip'       => $slip,
        'values'     => $values,
        'staff'      => [],
    ]);
}

public function update($id)
{
    helper('url');

    $slip = (new SalarySlipModel())->find($id);

    if (! $slip) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    $slipId = (int) $slip['id'];
    $detailUrl = site_url('admin/salary-slips/' . $slipId);
    $editUrl = site_url('admin/salary-slips/' . $slipId . '/edit');

    if ($slip['status'] !== 'draft') {
        return redirect()->to($detailUrl)
            ->with('error', 'Hanya slip berstatus draft yang dapat diedit.');
    }

    $read = function (string $name): string {
        $value = $this->request->getPost($name);

        return is_string($value) ? trim($value) : '';
    };

    $values = [
        'slip_id'      => $slipId,
        'basic_salary' => $read('basic_salary'),
        'notes'        => $read('notes'),
        'items'        => [],
    ];

    $errors = [];

    if (mb_strlen($values['notes']) > 2000) {
        $errors['notes'] = 'Catatan maksimal 2000 karakter.';
    }

    $basicSalary = $this->parseAmount($values['basic_salary']);

    if ($basicSalary === null) {
        $errors['basic_salary'] =
            'Gaji pokok harus berupa angka bulat 0–100000000, tanpa titik atau koma.';
    }

    $submittedItems = $this->request->getPost('items') ?? [];

    if (! is_array($submittedItems) || count($submittedItems) > 20) {
        $errors['items'] = 'Maksimal 20 baris komponen tambahan.';
        $submittedItems = [];
    }

    $items = [];
    $totalEarnings = $basicSalary ?? 0;
    $totalDeductions = 0;

    foreach ($submittedItems as $row) {
        if (! is_array($row)) {
            $errors['items'] = 'Format komponen tidak valid.';
            continue;
        }

        $type = is_string($row['item_type'] ?? null)
            ? $row['item_type']
            : '';

        $label = is_string($row['label'] ?? null)
            ? trim($row['label'])
            : '';

        $amountText = is_string($row['amount'] ?? null)
            ? trim($row['amount'])
            : '';

        $values['items'][] = [
            'item_type' => in_array($type, ['earning', 'deduction'], true)
                ? $type
                : 'earning',
            'label'  => mb_substr($label, 0, 100),
            'amount' => mb_substr($amountText, 0, 30),
        ];

        $amount = $this->parseAmount($amountText);

        if (
            ! in_array($type, ['earning', 'deduction'], true)
            || $label === ''
            || mb_strlen($label) > 100
            || $amount === null
        ) {
            $errors['items'] =
                'Lengkapi nama komponen maksimal 100 karakter dan '
                . 'nominal bulat 0–100000000. Hapus baris yang tidak dipakai.';

            continue;
        }

        $items[] = [
            'salary_slip_id' => $slipId,
            'item_type'      => $type,
            'label'          => $label,
            'amount'         => $amount,
            'sort_order'     => count($items),
        ];

        if ($type === 'earning') {
            $totalEarnings += $amount;
        } else {
            $totalDeductions += $amount;
        }
    }

    if ($totalDeductions > $totalEarnings) {
        $errors['total'] = 'Total potongan tidak boleh melebihi total pendapatan.';
    }

    if ($errors !== []) {
        return redirect()->to($editUrl)
            ->with('errors', $errors)
            ->with('salary_edit_values', $values);
    }

    $db = \Config\Database::connect();

    try {
        if (! $db->transBegin()) {
            throw new \RuntimeException('Transaksi gagal dimulai.');
        }

        // Urutan penguncian sama dengan proses pembuatan slip:
        // akun penerima terlebih dahulu, lalu slip.
        $db->query(
            'SELECT id FROM users WHERE id = ? FOR UPDATE',
            [$slip['user_id']]
        );

        $current = $db->query(
            'SELECT id, user_id, status
             FROM salary_slips
             WHERE id = ? FOR UPDATE',
            [$slipId]
        )->getRowArray();

        if (
            ! $current
            || $current['status'] !== 'draft'
            || (int) $current['user_id'] !== (int) $slip['user_id']
        ) {
            throw new \DomainException(
                'Slip sudah berubah atau bukan draft lagi. Muat ulang halaman.'
            );
        }

        $slipModel = new SalarySlipModel($db);
        $itemModel = new SalarySlipItemModel($db);

        // Hanya nominal dasar dan catatan yang diperbarui.
        $updated = $slipModel->update($slipId, [
            'basic_salary' => $basicSalary,
            'notes'        => $values['notes'] !== ''
                ? $values['notes']
                : null,
        ]);

        if ($updated === false) {
            throw new \RuntimeException('Draft gagal diperbarui.');
        }

        // Ganti komponen milik slip ini dengan hasil edit.
        $deleted = $itemModel
            ->where('salary_slip_id', $slipId)
            ->delete();

        if ($deleted === false) {
            throw new \RuntimeException('Komponen lama gagal diperbarui.');
        }

        foreach ($items as $item) {
            if ($itemModel->insert($item) === false) {
                throw new \RuntimeException('Komponen baru gagal disimpan.');
            }
        }

        if ($db->transStatus() === false || ! $db->transCommit()) {
            throw new \RuntimeException('Transaksi gagal diselesaikan.');
        }
    } catch (\DomainException $exception) {
        $db->transRollback();

        return redirect()->to($detailUrl)
            ->with('error', $exception->getMessage());
    } catch (\Throwable $exception) {
        $db->transRollback();

        log_message(
            'error',
            'Pembaruan draft slip gagal: ' . get_class($exception)
        );

        return redirect()->to($editUrl)
            ->with(
                'error',
                'Perubahan belum dapat dipastikan berhasil. '
                . 'Periksa rincian slip sebelum mencoba kembali.'
            )
            ->with('salary_edit_values', $values);
    }

    return redirect()->to($detailUrl)
        ->with('success', 'Draft slip gaji berhasil diperbarui.');
}

public function delete($id)
{
    helper('url');

    $slipModel = new SalarySlipModel();
    $slip = $slipModel->find($id);

    if (!$slip) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
            'Slip gaji tidak ditemukan.'
        );
    }

    $slipId = (int) $slip['id'];
    $listUrl = site_url('admin/salary-slips');
    $reasonInput = $this->request->getPost('delete_reason');

$deleteReason = is_string($reasonInput)
    ? trim($reasonInput)
    : '';

if (
    $slip['status'] === 'published'
    && (
        $deleteReason === ''
        || mb_strlen($deleteReason) > 500
    )
) {
    return redirect()->to(
        site_url('admin/salary-slips/' . $slipId)
    )->with(
        'error',
        'Alasan penghapusan wajib diisi, maksimal 500 karakter.'
    );
}

    $db = db_connect();
    $pdfFilename = null;

    try {
        if ($db->transBegin() === false) {
            throw new \RuntimeException('Tidak dapat memulai transaksi.');
        }

        // Gunakan urutan penguncian yang sama dengan edit dan terbitkan.
        $db->query(
            'SELECT id FROM users WHERE id = ? FOR UPDATE',
            [(int) $slip['user_id']]
        );

        $lockedSlip = $db->query(
    'SELECT id, user_id, status, pdf_filename,
            period_month, period_year
     FROM salary_slips
     WHERE id = ?
     FOR UPDATE',
    [$slipId]
)->getRowArray();

        if (!$lockedSlip) {
            throw new \DomainException(
                'Slip sudah dihapus. Silakan periksa kembali daftar slip.'
            );
        }

        if (
            (int) $lockedSlip['user_id'] !== (int) $slip['user_id']
            || $lockedSlip['status'] !== $slip['status']
        ) {
            throw new \DomainException(
                'Data atau status slip berubah. '
                    . 'Buka kembali slip sebelum menghapusnya.'
            );
        }

        if (!in_array($lockedSlip['status'], ['draft', 'published'], true)) {
            throw new \DomainException(
                'Status slip tidak mendukung penghapusan.'
            );
        }

        $pdfFilename = $lockedSlip['pdf_filename'];

        // Rincian pendapatan/potongan ikut terhapus melalui CASCADE.
        $transactionModel = new SalarySlipModel($db);

        if ($transactionModel->delete($slipId) === false) {
    throw new \RuntimeException('Penghapusan slip gagal.');
}

// Hanya penghapusan slip terbit yang diberitahukan kepada staff.
if ($lockedSlip['status'] === 'published') {
    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $period = $months[(int) $lockedSlip['period_month']]
        . ' ' . $lockedSlip['period_year'];

    (new StaffNotification())->send(
        $db,
        (int) $lockedSlip['user_id'],
        (int) session()->get('user_id'),
        'salary_deleted',
        'Slip gaji dihapus oleh admin',
        'Slip gaji ' . $period
            . ' dihapus oleh admin. Alasan: ' . $deleteReason,
        'staff/salary-slips'
    );
}

if ($db->transStatus() === false) {
    throw new \RuntimeException('Transaksi penghapusan gagal.');
}

        if ($db->transCommit() === false) {
            throw new \RuntimeException('Commit penghapusan gagal.');
        }
    } catch (\DomainException $exception) {
        $db->transRollback();

        return redirect()->to($listUrl)->with(
            'error',
            $exception->getMessage()
        );
    } catch (\Throwable $exception) {
        $db->transRollback();

        log_message(
            'error',
            'Penghapusan slip gagal. Jenis error: '
                . get_class($exception)
        );

        return redirect()->to($listUrl)->with(
            'error',
            'Penghapusan belum dapat dipastikan berhasil. '
                . 'Periksa daftar slip sebelum mencoba lagi.'
        );
    }

    // Hapus PDF hanya setelah penghapusan database berhasil.
    $fileCleanupFailed = false;

    if (!empty($pdfFilename)) {
        try {
            if (
                !is_string($pdfFilename)
                || !preg_match('/\A[a-f0-9]{32}\.pdf\z/', $pdfFilename)
            ) {
                throw new \RuntimeException('Nama file PDF tidak valid.');
            }

            $path = WRITEPATH . 'uploads/salary_slips/' . $pdfFilename;

            if (is_file($path) && !unlink($path)) {
                throw new \RuntimeException('File PDF tidak dapat dihapus.');
            }
        } catch (\Throwable $exception) {
            $fileCleanupFailed = true;

            log_message(
                'error',
                'Pembersihan PDF slip ID ' . $slipId
                    . ' gagal. Jenis error: '
                    . get_class($exception)
            );
        }
    }

    if ($fileCleanupFailed) {
        return redirect()->to($listUrl)->with(
            'error',
            'Data slip sudah dihapus dan tidak dapat diakses staff. '
                . 'Namun, file PDF di server belum berhasil dibersihkan. '
                . 'Periksa log aplikasi.'
        );
    }

    return redirect()->to($listUrl)->with(
        'success',
        'Slip gaji beserta rincian dan file PDF-nya berhasil dihapus.'
    );
}
}