<?php
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

$categories = [
    'academic' => 'Academic',
    'academic_support' => 'Academic Support',
];

$period = ($months[(int) $slip['period_month']] ?? '-')
    . ' ' . $slip['period_year'];

$isDraft = $slip['status'] !== 'published';

$rupiah = static fn ($amount): string =>
    'Rp ' . number_format((int) $amount, 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji</title>

    <style>
        <?= $css ?>
    </style>
</head>
<body>
    <table class="letterhead">
        <tr>
            <?php if ($logoData): ?>
                <td class="logo-cell">
                    <img src="<?= esc($logoData, 'attr') ?>" alt="Logo sekolah">
                </td>
            <?php endif; ?>

            <td>
                <h1>PERMATA BANGSA SCHOOL</h1>
                <p>SLIP GAJI STAFF</p>
            </td>

            <td class="status-cell">
                <?= $isDraft ? 'DRAFT' : 'DITERBITKAN' ?>
            </td>
        </tr>
    </table>

    <?php if ($isDraft): ?>
        <div class="draft-notice">
            PREVIEW DRAFT — Slip ini belum diterbitkan.
        </div>
    <?php endif; ?>

    <table class="identity">
        <tr>
            <td>Nama Staff</td>
            <td>: <?= esc($slip['staff_name']) ?></td>
        </tr>
        <tr>
            <td>Username</td>
            <td>: <?= esc($slip['staff_username']) ?></td>
        </tr>
        <tr>
            <td>Kategori Staff</td>
            <td>
                : <?= esc($categories[$slip['staff_category']] ?? '-') ?>
            </td>
        </tr>
        <tr>
            <td>Periode</td>
            <td>: <?= esc($period) ?></td>
        </tr>
        <tr>
            <td>Nomor Slip</td>
            <td>: <?= esc($slip['slip_number'] ?? 'Belum diterbitkan') ?></td>
        </tr>
    </table>

    <h2>Pendapatan</h2>

    <table class="details">
        <thead>
            <tr>
                <th>Keterangan</th>
                <th class="money">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gaji Pokok</td>
                <td class="money"><?= $rupiah($slip['basic_salary']) ?></td>
            </tr>

            <?php foreach ($earnings as $item): ?>
                <tr>
                    <td><?= esc($item['label']) ?></td>
                    <td class="money"><?= $rupiah($item['amount']) ?></td>
                </tr>
            <?php endforeach; ?>

            <tr class="total-row">
                <td>Total Pendapatan</td>
                <td class="money"><?= $rupiah($totalEarnings) ?></td>
            </tr>
        </tbody>
    </table>

    <h2>Potongan</h2>

    <table class="details">
        <thead>
            <tr>
                <th>Keterangan</th>
                <th class="money">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($deductions === []): ?>
                <tr>
                    <td>Tidak ada potongan</td>
                    <td class="money"><?= $rupiah(0) ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($deductions as $item): ?>
                    <tr>
                        <td><?= esc($item['label']) ?></td>
                        <td class="money"><?= $rupiah($item['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <tr class="total-row">
                <td>Total Potongan</td>
                <td class="money"><?= $rupiah($totalDeductions) ?></td>
            </tr>
        </tbody>
    </table>

    <table class="net-total">
        <tr>
            <td>GAJI BERSIH</td>
            <td class="money"><?= $rupiah($netSalary) ?></td>
        </tr>
    </table>

    <?php if (trim((string) ($slip['notes'] ?? '')) !== ''): ?>
        <div class="notes">
            <strong>Catatan</strong>
            <p><?= nl2br(esc($slip['notes'])) ?></p>
        </div>
    <?php endif; ?>

    <div class="document-footer">
        <?php if (!$isDraft && !empty($slip['published_at'])): ?>
            <p>
                Diterbitkan:
                <?= esc(date('d-m-Y H:i', strtotime($slip['published_at']))) ?>
            </p>
        <?php endif; ?>

        <p>
            Dokumen ini bersifat pribadi dan ditujukan kepada staff yang
            namanya tercantum di atas.
        </p>
    </div>
</body>
</html>