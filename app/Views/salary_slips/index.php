<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

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
?>

<div class="admin-list-page">
    <div class="page-heading admin-list-hero">
        <div>
            <h1>Slip Gaji Saya</h1>
            <p>
                Lihat dan unduh slip gaji yang telah diterbitkan
                oleh admin sekolah.
            </p>
        </div>

        <a
            href="<?= site_url('dashboard') ?>"
            class="button-link button-secondary"
        >
            Kembali ke Dashboard
        </a>
    </div>

    <section class="staff-panel">
        <div class="staff-panel-heading">
            <h2>Riwayat Slip Gaji</h2>

            <span class="staff-count">
                <?= (int) $pager->getTotal() ?> slip
            </span>
        </div>

        <p class="salary-list-description">
            Menampilkan maksimal 10 slip per halaman.
            Hanya slip yang sudah diterbitkan yang muncul di sini.
        </p>

        <?php if ($slips === []): ?>
            <div class="staff-empty">
                <h3>Belum ada slip gaji</h3>
                <p>
                    Slip akan muncul setelah admin menerbitkannya
                    untuk akun kamu.
                </p>
            </div>
        <?php else: ?>
            <div class="staff-table-wrap">
                <table class="staff-document-table">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Nomor Slip</th>
                            <th>Diterbitkan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($slips as $slip): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= esc(
                                            $months[(int) $slip['period_month']]
                                                ?? '-'
                                        ) ?>
                                        <?= (int) $slip['period_year'] ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= esc($slip['slip_number']) ?>
                                </td>

                                <td>
                                    <?= !empty($slip['published_at'])
                                        ? esc(date(
                                            'd-m-Y H:i',
                                            strtotime($slip['published_at'])
                                        ))
                                        : '-' ?>
                                </td>

                                <td>
                                    <span class="admin-badge badge-green">
                                        Terbit
                                    </span>
                                </td>

                                <td>
                                    <div class="salary-staff-actions">
                                        <a
                                            href="<?= site_url(
                                                'salary-slips/'
                                                . (int) $slip['id']
                                                . '/pdf'
                                            ) ?>"
                                            class="button-link button-secondary"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            Lihat PDF
                                        </a>

                                        <a
                                            href="<?= site_url(
                                                'salary-slips/'
                                                . (int) $slip['id']
                                                . '/download'
                                            ) ?>"
                                            class="button-link"
                                        >
                                            Unduh
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="document-list-footer">
                <p>
                    <?= count($slips) ?> dari
                    <?= (int) $pager->getTotal() ?> slip
                </p>

                <?php if ($pager->getPageCount() > 1): ?>
                    <div class="document-pagination">
                        <?= $pager->only([])->links() ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?= $this->endSection() ?>