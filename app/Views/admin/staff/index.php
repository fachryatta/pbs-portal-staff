<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="admin-list-page">

<?php
$categoryLabels = [
    'academic' => 'Academic',
    'academic_support' => 'Academic Support',
];

$hasFilter = $search !== '' || $category !== '' || $status !== '';

$summaryCards = [
    [
        'label' => 'Total Staff',
        'value' => $staffSummary['total'],
        'note' => 'Seluruh akun staff terdaftar',
        'class' => 'stat-total',
    ],
    [
        'label' => 'Academic',
        'value' => $staffSummary['academic'],
        'note' => 'Staff pengajar / guru',
        'class' => 'stat-diploma',
    ],
    [
        'label' => 'Academic Support',
        'value' => $staffSummary['academic_support'],
        'note' => 'Staff pendukung akademik',
        'class' => 'stat-competency',
    ],
];

$statusOptions = [
    '' => 'Semua status',
    'active' => 'Akun aktif',
    'inactive' => 'Akun nonaktif',
    'unverified' => 'Email belum terverifikasi',
];
?>

<div class="page-heading admin-list-hero">
    <div>
        <p class="eyebrow">ADMINISTRASI STAFF</p>
        <h1>Kelola Staff</h1>
        <p>Daftar akun Academic dan Academic Support.</p>
    </div>

    <a class="button-link button-secondary" href="<?= site_url('admin/dashboard') ?>">
        Dashboard
    </a>
</div>

<div class="staff-statistics admin-summary-grid admin-summary-three">
    <?php foreach ($summaryCards as $card): ?>
        <section class="staff-stat <?= esc($card['class'], 'attr') ?>">
            <h2><?= esc($card['label']) ?></h2>
            <p class="staff-stat-value"><?= number_format((int) $card['value'], 0, ',', '.') ?></p>
            <p class="staff-stat-note"><?= esc($card['note']) ?></p>
        </section>
    <?php endforeach; ?>
</div>

<p class="admin-summary-note">
    Ringkasan seluruh staff, termasuk akun nonaktif dan belum terverifikasi.
    Admin dengan profil staff juga dihitung. Angka tidak mengikuti filter.
    <?php if ($staffSummary['uncategorized'] > 0): ?>
        Ada <?= (int) $staffSummary['uncategorized'] ?> staff yang kategorinya belum tersedia.
    <?php endif; ?>
</p>

<section class="staff-panel admin-list-panel">
    <div class="staff-panel-heading">
        <div>
            <h2>Daftar staff</h2>
            <p>Hasil sesuai pencarian dan filter yang dipilih.</p>
        </div>

        <span class="staff-count">
            <?= (int) $pager->getTotal() ?> akun
        </span>
    </div>

    <form
        class="document-filter admin-staff-filter"
        action="<?= site_url('admin/staff') ?>"
        method="get"
    >
        <div class="field">
            <label for="q">Cari staff</label>

            <input
                type="search"
                id="q"
                name="q"
                value="<?= esc($search, 'attr') ?>"
                placeholder="Nama, username, atau email"
                maxlength="100"
            >
        </div>

        <div class="field">
            <label for="category">Kategori staff</label>

            <select id="category" name="category">
                <option value="">Semua kategori</option>

                <?php foreach ($categoryLabels as $value => $label): ?>
                    <option
                        value="<?= esc($value, 'attr') ?>"
                        <?= $category === $value ? 'selected' : '' ?>
                    >
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="status">Status</label>

            <select id="status" name="status">
                <?php foreach ($statusOptions as $value => $label): ?>
                    <option
                        value="<?= esc($value, 'attr') ?>"
                        <?= $status === $value ? 'selected' : '' ?>
                    >
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="document-filter-actions">
            <button class="btn-primary" type="submit">
                Terapkan
            </button>

            <a class="button-link button-secondary" href="<?= site_url('admin/staff') ?>">
                Reset
            </a>
        </div>
    </form>

    <p class="document-page-size">10 staff per halaman</p>

    <?php if (empty($staff)): ?>
        <div class="staff-empty">
            <h3><?= $hasFilter ? 'Staff tidak ditemukan' : 'Belum ada staff terdaftar' ?></h3>
            <p><?= $hasFilter ? 'Coba ubah pencarian atau reset filter.' : 'Akun staff akan tampil setelah registrasi.' ?></p>
            <?php if ($hasFilter): ?>
                <a class="button-link button-secondary" href="<?= site_url('admin/staff') ?>">Reset Filter</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div
            class="staff-table-wrap"
            role="region"
            aria-label="Daftar staff"
            tabindex="0"
        >
            <table class="staff-document-table admin-staff-table">
                <thead>
                    <tr>
                        <th scope="col">Staff</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Status akun</th>
                        <th scope="col">Verifikasi email</th>
                        <th scope="col">Terdaftar</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($staff as $person): ?>
                        <tr>
                            <td>
                                <div class="admin-staff-identity">
                                    <span class="admin-staff-initial" aria-hidden="true">
                                        <?= esc(mb_strtoupper(mb_substr($person['full_name'], 0, 1))) ?>
                                    </span>
                                    <div class="admin-staff-identity-copy">
                                        <a class="document-owner-link" href="<?= site_url('admin/staff/' . (int) $person['id']) ?>">
                                            <?= esc($person['full_name']) ?>
                                        </a>
                                        <small>@<?= esc($person['username']) ?></small>
                                        <small><?= esc($person['email']) ?></small>
                                        <?php if ($person['access_role'] === 'admin'): ?>
                                            <span class="admin-staff-role">Admin &amp; Staff</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="admin-staff-category"><?= esc(
                                    $categoryLabels[$person['staff_category'] ?? '']
                                    ?? 'Belum tersedia'
                                ) ?></span>
                            </td>

                            <td>
                                <span class="admin-badge <?= (int) $person['is_active'] === 1 ? 'badge-green' : 'badge-gray' ?>">
                                    <?= (int) $person['is_active'] === 1
                                        ? 'Aktif'
                                        : 'Nonaktif' ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-badge <?= $person['email_verified_at'] !== null ? 'badge-green' : 'badge-amber' ?>">
                                    <?= $person['email_verified_at'] !== null
                                        ? 'Terverifikasi'
                                        : 'Belum verifikasi' ?>
                                </span>
                            </td>

                            <td>
                                <?= ! empty($person['created_at'])
                                    ? esc(date('d-m-Y', strtotime($person['created_at'])))
                                    : '—' ?>
                            </td>

                            <td>
                                <a
                                    class="button-link button-secondary"
                                    href="<?= site_url('admin/staff/' . (int) $person['id']) ?>"
                                    aria-label="Detail <?= esc($person['full_name'], 'attr') ?>"
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
            Menampilkan <strong><?= count($staff) ?></strong>
            dari <strong><?= (int) $pager->getTotal() ?></strong> staff
        </p>
        <?php if ($pager->getPageCount() > 1): ?>
            <div class="document-pagination">
                <?= $pager->only(['q', 'category', 'status'])->links() ?>
            </div>
        <?php endif; ?>
    </div>
</section>

</div>

<?= $this->endSection() ?>