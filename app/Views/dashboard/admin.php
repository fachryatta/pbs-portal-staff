<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?= $this->include('dashboard/_switcher') ?>

<?php
$statistics = [
    [
        'label' => 'Total staff',
        'value' => $totalStaff,
        'note' => 'Akun dengan hak akses staff',
        'class' => 'stat-total',
    ],
    [
        'label' => 'Akun aktif',
        'value' => $activeStaff,
        'note' => 'Tidak sedang dinonaktifkan',
        'class' => 'stat-diploma',
    ],
    [
        'label' => 'Belum verifikasi',
        'value' => $unverifiedStaff,
        'note' => 'Email belum terverifikasi',
        'class' => 'stat-competency',
    ],
    [
        'label' => 'Dokumen staff',
        'value' => $totalDocuments,
        'note' => 'Ijazah dan seluruh sertifikat',
        'class' => 'stat-competition',
    ],
];

$adminMenus = [
    [
        'title' => 'Kelola Staff',
        'description' => 'Lihat data staff, perbarui informasi, dan kelola status akun.',
        'url' => 'admin/staff',
        'class' => 'staff',
        'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'
            . ' M16 3a4 4 0 0 1 0 8'
            . ' M22 21v-2a4 4 0 0 0-3-3.87',
        'circle' => true,
    ],
    [
        'title' => 'Kelola Admin',
        'description' => 'Kelola akun administrator dan hak akses pengelolaan portal.',
        'url' => 'admin/accounts',
        'class' => 'accounts',
        'icon' => 'M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z'
            . ' M9 12l2 2 4-4',
        'circle' => false,
    ],
    [
        'title' => 'Semua Dokumen',
        'description' => 'Telusuri ijazah dan sertifikat yang telah diunggah seluruh staff.',
        'url' => 'admin/documents',
        'class' => 'documents',
        'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12'
            . 'a2 2 0 0 0 2-2V8z'
            . ' M14 2v6h6 M8 13h8 M8 17h5',
        'circle' => false,
    ],
    [
        'title' => 'Slip Gaji',
        'description' => 'Buat rincian gaji, periksa draft, dan terbitkan slip untuk staff.',
        'url' => 'admin/salary-slips',
        'class' => 'salary',
        'icon' => 'M6 3h12a2 2 0 0 1 2 2v16l-4-2-4 2-4-2-4 2V5'
            . 'a2 2 0 0 1 2-2z'
            . ' M8 7h8 M8 11h8 M8 15h4',
        'circle' => false,
    ],
];
?>

<div class="staff-page-title">
    <div>
        <p class="eyebrow">PERMATA BANGSA SCHOOL</p>
        <h1>Dashboard Admin / HRD</h1>
    </div>

    <span class="staff-category">Administrator</span>
</div>

<section class="admin-welcome">
    <div>
        <p class="eyebrow">PORTAL ADMINISTRASI</p>

        <h2>Halo, <?= esc(session()->get('full_name')) ?></h2>

        <p>
            Pantau data staff, status akun, dan jumlah berkas sekolah
            melalui ringkasan berikut.
        </p>
    </div>

    <a class="button-link" href="#admin-features">
    Menu administrasi &darr;
</a>
</section>

<div class="staff-statistics">
    <?php foreach ($statistics as $stat): ?>
        <section class="staff-stat <?= esc($stat['class'], 'attr') ?>">
            <h2><?= esc($stat['label']) ?></h2>
            <p class="staff-stat-value"><?= (int) $stat['value'] ?></p>
            <p class="staff-stat-note"><?= esc($stat['note']) ?></p>
        </section>
    <?php endforeach; ?>
</div>

<section
    id="admin-features"
    class="admin-feature-section"
    aria-labelledby="admin-features-title"
>
    <div class="admin-feature-heading">
        <div>
            <p class="eyebrow">AKSES CEPAT</p>
            <h2 id="admin-features-title">Menu Administrasi</h2>
            <p>Kelola data, dokumen, dan penggajian sekolah.</p>
        </div>
    </div>

    <div class="admin-feature-grid">
        <?php foreach ($adminMenus as $menu): ?>
            <a
                href="<?= site_url($menu['url']) ?>"
                class="admin-feature-card admin-feature-<?= esc(
                    $menu['class'],
                    'attr'
                ) ?>"
            >
                <span class="admin-feature-icon" aria-hidden="true">
                    <svg
                        viewBox="0 0 24 24"
                        width="26"
                        height="26"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <?php if ($menu['circle']): ?>
                            <circle cx="9" cy="7" r="4"></circle>
                        <?php endif; ?>

                        <path d="<?= esc($menu['icon'], 'attr') ?>"></path>
                    </svg>
                </span>

                <h3><?= esc($menu['title']) ?></h3>

                <p><?= esc($menu['description']) ?></p>

                <span class="admin-feature-link">
                    Buka menu
                    <span aria-hidden="true">&rarr;</span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="staff-panel">
    <div class="staff-panel-heading">
        <div>
            <h2>Pemantauan akun staff</h2>
            <p>Periksa akun yang masih memerlukan verifikasi email.</p>
        </div>
    </div>

    <?php if ($unverifiedStaff > 0): ?>
        <p class="admin-panel-copy">
            Ada <strong><?= (int) $unverifiedStaff ?> akun staff</strong>
            yang belum memverifikasi email dan belum bisa login.
        </p>

        <a
            class="button-link button-secondary"
            href="<?= site_url('admin/staff') ?>?status=unverified"
        >
            Lihat akun belum terverifikasi
        </a>
    <?php elseif ($totalStaff > 0): ?>
        <p class="admin-panel-copy">
            Seluruh akun staff yang terdaftar sudah memverifikasi email.
        </p>
    <?php else: ?>
        <p class="admin-panel-copy">
            Belum ada akun staff yang terdaftar.
        </p>
    <?php endif; ?>
</section>

<?= $this->endSection() ?>