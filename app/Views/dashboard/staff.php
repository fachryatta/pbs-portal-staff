<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?= $this->include('dashboard/_switcher') ?>

<?php
$categories = [
    'academic' => 'Academic',
    'academic_support' => 'Academic Support',
];

$documentLabels = [
    'diploma' => 'Ijazah',
    'competency_certificate' => 'Sertifikat Kompetensi',
    'competition_certificate' => 'Sertifikat Kompetisi',
];

$category = $categories[$profile['staff_category'] ?? ''] ?? 'Staff';

$statistics = [
    [
        'label' => 'Total dokumen',
        'value' => $totalDocuments,
        'note' => 'Seluruh berkas pribadi',
        'class' => 'stat-total',
    ],
    [
        'label' => 'Ijazah',
        'value' => $counts['diploma'],
        'note' => 'Dokumen pendidikan',
        'class' => 'stat-diploma',
    ],
    [
        'label' => 'Kompetensi',
        'value' => $counts['competency_certificate'],
        'note' => 'Sertifikat kompetensi',
        'class' => 'stat-competency',
    ],
    [
        'label' => 'Kompetisi',
        'value' => $counts['competition_certificate'],
        'note' => 'Sertifikat kompetisi',
        'class' => 'stat-competition',
    ],
];
?>

<div class="staff-page-title">
    <div>
        <p class="eyebrow">PORTAL STAFF</p>
        <h1>Dashboard Staff</h1>
    </div>

    <span class="staff-category"><?= esc($category) ?></span>
</div>

<section class="staff-welcome">
    <div class="staff-welcome-copy">
        <p class="eyebrow">PERMATA BANGSA SCHOOL</p>

        <h2>Selamat datang,<br><?= esc($user['full_name']) ?></h2>

        <p class="staff-welcome-description">
            Kelola informasi pribadi dan pantau arsip dokumen Anda
            dalam satu tempat.
        </p>

        <div class="staff-welcome-actions">
            <a class="button-link" href="<?= site_url('profile') ?>">
                Lihat profil saya
            </a>

            <a class="button-link button-secondary" href="<?= site_url('documents/create') ?>">
    + Upload dokumen
</a>

            <a class="staff-text-link" href="<?= site_url('profile/edit') ?>">
                Perbarui data pribadi &rarr;
            </a>
        </div>
    </div>

    <div class="staff-identity">
        <?php if (! empty($user['profile_photo'])): ?>
            <img
                class="staff-avatar"
                src="<?= site_url('profile/photo') ?>"
                alt="Foto profil <?= esc($user['full_name'], 'attr') ?>"
                width="104"
                height="104"
            >
        <?php else: ?>
            <div class="staff-avatar staff-avatar-initial" aria-hidden="true">
                <?= esc(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?>
            </div>
        <?php endif; ?>

        <strong><?= esc($user['full_name']) ?></strong>
        <span><?= esc($category) ?></span>
        <span class="status-badge">Email terverifikasi</span>
    </div>
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

<div class="staff-content-grid staff-dashboard-layout">
    <div class="staff-dashboard-main">
        <section class="staff-panel">
        <div class="staff-panel-heading">
            <div>
                <h2>Dokumen terbaru</h2>
                <p>Lima berkas terakhir yang Anda unggah.</p>
            </div>

            <span class="staff-count">
                <?= (int) $totalDocuments ?> berkas
            </span>
        </div>

        <?php if (empty($recentDocuments)): ?>
            <div class="staff-empty">
                <div class="staff-empty-symbol" aria-hidden="true">
                    <svg
                        viewBox="0 0 24 24"
                        width="34"
                        height="34"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <path d="M14 2v6h6M8 13h8M8 17h5"/>
                    </svg>
                </div>

                <h3>Belum ada dokumen</h3>

                <p>
                    Ijazah dan sertifikat Anda akan tampil di sini
                    setelah diunggah.
                </p>
            </div>
        <?php else: ?>
            <div
                class="staff-table-wrap"
                role="region"
                aria-label="Daftar dokumen terbaru"
                tabindex="0"
            >
                <table class="staff-document-table">
                    <thead>
                        <tr>
                            <th scope="col">Dokumen</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Diunggah</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($recentDocuments as $document): ?>
                            <tr>
                                <td>
                                    <a
    class="document-title-link"
    href="<?= site_url('documents/' . (int) $document['id'] . '/preview') ?>"
>
    <?= esc($document['title']) ?>
</a>
                                    <small>
                                        <?= esc($document['issuer'] ?: 'Penerbit belum dicantumkan') ?>
                                    </small>
                                </td>

                                <td>
                                    <?= esc($documentLabels[$document['document_type']] ?? 'Dokumen') ?>
                                </td>

                                <td>
                                    <?= ! empty($document['created_at'])
                                        ? esc(date('d-m-Y', strtotime($document['created_at'])))
                                        : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="staff-recent-documents-footer">
    <a class="staff-text-link" href="<?= site_url('documents') ?>">
        Lihat semua dokumen &rarr;
    </a>
</div>
    </section>

            <section class="staff-panel salary-dashboard-card">
            <div>
                <h2>Slip Gaji</h2>
                <p>
                    Lihat dan unduh slip gaji yang sudah diterbitkan
                    oleh admin sekolah.
                </p>
            </div>

            <a
                href="<?= site_url('staff/salary-slips') ?>"
                class="button-link"
            >
                Lihat Slip Gaji
            </a>
        </section>
    </div>

    <aside class="staff-panel">
        <div class="staff-panel-heading">
            <div>
                <h2>Informasi akun</h2>
                <p>Identitas akun yang sedang digunakan.</p>
            </div>
        </div>

        <dl class="staff-account">
            <div>
                <dt>Username</dt>
                <dd><?= esc($user['username']) ?></dd>
            </div>

            <div>
                <dt>Email</dt>
                <dd><?= esc($user['email']) ?></dd>
            </div>

            <div>
                <dt>Nomor telepon</dt>
                <dd><?= esc($profile['phone'] ?? 'Belum diisi') ?></dd>
            </div>

            <div>
                <dt>Kelengkapan profil</dt>
                <dd><span class="status-badge">Lengkap</span></dd>
            </div>
        </dl>

        <div class="staff-account-tip">
            <strong>Data berubah?</strong>
            <p>
                Perbarui profil agar informasi yang digunakan sekolah
                tetap sesuai.
            </p>

            <a href="<?= site_url('profile/edit') ?>">Edit profil &rarr;</a>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>