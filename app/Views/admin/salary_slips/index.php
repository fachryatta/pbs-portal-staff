<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$months = [
    1  => 'Januari',
    2  => 'Februari',
    3  => 'Maret',
    4  => 'April',
    5  => 'Mei',
    6  => 'Juni',
    7  => 'Juli',
    8  => 'Agustus',
    9  => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
];

$categories = [
    'academic' => 'Academic',
    'academic_support' => 'Academic Support',
];
?>

<div class="admin-list-page">
    <div class="page-heading admin-list-hero">
    <div>
        <p class="eyebrow">ADMINISTRASI PENGGAJIAN</p>
        <h1>Kelola Slip Gaji</h1>
        <p>
            Kelola draft dan slip gaji yang diterbitkan
            untuk staff Permata Bangsa School.
        </p>
    </div>

    <a
        class="button-link"
        href="<?= site_url('admin/salary-slips/create') ?>"
    >
        + Buat Slip Gaji
    </a>
</div>

    <section class="staff-panel admin-list-panel">
        <div class="staff-panel-heading">
            <div>
                <h2>Daftar Slip Gaji</h2>
                <p>Diurutkan berdasarkan periode terbaru.</p>
            </div>

            <span class="staff-count">
                <?= (int) $pager->getTotal() ?> slip
            </span>
        </div>

        <p class="salary-list-description">
            Draft hanya terlihat oleh admin.
            Slip yang telah diterbitkan tersedia untuk staff penerima.
        </p>

        <form
    action="<?= site_url('admin/salary-slips') ?>"
    method="get"
    class="salary-filter-form"
>
    <div class="salary-filter-field">
        <label for="salary-search">Cari Nama Staff</label>

        <input
            type="search"
            id="salary-search"
            name="search"
            value="<?= esc($filters['search'], 'attr') ?>"
            placeholder="Ketik nama staff..."
            maxlength="200"
        >
    </div>

    <div class="salary-filter-field">
        <label for="salary-month">Bulan</label>

        <select id="salary-month" name="month">
            <option value="">Semua bulan</option>

            <?php foreach ([
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
            ] as $monthNumber => $monthName): ?>
                <option
                    value="<?= $monthNumber ?>"
                    <?= $filters['month'] === (string) $monthNumber
                        ? 'selected'
                        : '' ?>
                >
                    <?= esc($monthName) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="salary-filter-field">
        <label for="salary-year">Tahun</label>

        <input
            type="number"
            id="salary-year"
            name="year"
            value="<?= esc($filters['year'], 'attr') ?>"
            placeholder="Semua tahun"
            min="2000"
            max="2100"
            step="1"
        >
    </div>

    <div class="salary-filter-field">
        <label for="salary-status">Status</label>

        <select id="salary-status" name="status">
            <option value="">Semua status</option>

            <option
                value="draft"
                <?= $filters['status'] === 'draft' ? 'selected' : '' ?>
            >
                Belum Terbit
            </option>

            <option
                value="published"
                <?= $filters['status'] === 'published' ? 'selected' : '' ?>
            >
                Sudah Terbit
            </option>
        </select>
    </div>

    <div class="salary-filter-actions">
        <button type="submit" class="salary-filter-submit">
            Terapkan
        </button>

        <a
            href="<?= site_url('admin/salary-slips') ?>"
            class="salary-filter-reset"
        >
            Reset
        </a>
    </div>
</form>

<p class="salary-filter-summary">
    <?= $hasFilters ? 'Hasil filter:' : 'Total:' ?>
    <strong><?= (int) $pager->getTotal() ?> slip</strong>
    · 10 slip per halaman
</p>

        <p class="document-page-size">
            10 slip per halaman
        </p>

        <?php if (empty($slips)): ?>
            <div class="staff-empty">
    <h3>
        <?= $hasFilters
            ? 'Tidak ada slip yang sesuai'
            : 'Belum ada slip gaji' ?>
    </h3>

    <p>
        <?= $hasFilters
            ? 'Coba ubah nama, periode, atau status yang dipilih.'
            : 'Klik Buat Slip Gaji untuk menambahkan slip pertama.' ?>
    </p>

    <?php if ($hasFilters): ?>
        <a
            href="<?= site_url('admin/salary-slips') ?>"
            class="button-link button-secondary"
        >
            Reset Filter
        </a>
    <?php endif; ?>
</div>
        <?php else: ?>
            <div
                class="staff-table-wrap"
                role="region"
                aria-label="Daftar slip gaji staff"
                tabindex="0"
            >
                <table class="staff-document-table salary-slip-table">
                    <thead>
                        <tr>
                            <th scope="col">Staff</th>
                            <th scope="col">Periode</th>
                            <th scope="col">Nomor Slip</th>
                            <th scope="col">Gaji Pokok</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($slips as $slip): ?>
                            <?php
                            $isPublished = $slip['status'] === 'published';

                            $period = (
                                $months[(int) $slip['period_month']] ?? '—'
                            ) . ' ' . $slip['period_year'];
                            ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?= esc($slip['staff_name']) ?>
                                    </strong>

                                    <small>
                                        @<?= esc($slip['staff_username']) ?>
                                    </small>

                                    <small>
                                        <?= esc(
                                            $categories[
                                                $slip['staff_category'] ?? ''
                                            ] ?? 'Kategori belum tersedia'
                                        ) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= esc($period) ?>
                                </td>

                                <td>
                                    <?= esc(
                                        $slip['slip_number']
                                            ?: 'Belum diterbitkan'
                                    ) ?>
                                </td>

                                <td class="salary-money">
                                    Rp <?= number_format(
                                        (int) $slip['basic_salary'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>
                                    <span
                                        class="admin-badge <?= $isPublished
                                            ? 'badge-green'
                                            : 'badge-amber' ?>"
                                    >
                                        <?= $isPublished
                                            ? 'Terbit'
                                            : 'Draft' ?>
                                    </span>
                                </td>

                                <td>
    <a
        class="button-link button-secondary"
        href="<?= site_url(
            'admin/salary-slips/' . (int) $slip['id']
        ) ?>"
        aria-label="Rincian slip <?= esc($slip['staff_name'], 'attr') ?>"
    >
        Detail
    </a>
</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="document-list-footer">
            <p>
                Menampilkan
                <strong><?= count($slips) ?></strong>
                dari
                <strong><?= (int) $pager->getTotal() ?></strong>
                slip gaji
            </p>

            <?php if ($pager->getPageCount() > 1): ?>
                <div class="document-pagination">
                    <?= $pager->only([
    'search',
    'month',
    'year',
    'status',
])->links() ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?= $this->endSection() ?>