<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$categoryLabels = [
    'academic' => 'Academic (Guru)',
    'academic_support' => 'Academic Support',
];

$genderLabels = [
    'male' => 'Laki-laki',
    'female' => 'Perempuan',
];

$details = [
    'Nama lengkap' => $user['full_name'],
    'Username' => $user['username'],
    'Email' => $user['email'],
    'Kategori staff' => $categoryLabels[$profile['staff_category'] ?? ''] ?? '',
    'Jenis kelamin' => $genderLabels[$profile['gender'] ?? ''] ?? '',
    'Agama / kepercayaan' => $profile['religion'] ?? '',
    'Kota lahir' => $profile['birth_city'] ?? '',
    'Tanggal lahir' => ! empty($profile['birth_date'])
        ? date('d-m-Y', strtotime($profile['birth_date']))
        : '',
    'Nomor telepon' => $profile['phone'] ?? '',
    'Alamat' => $profile['address'] ?? '',
];
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">DATA PRIBADI</p>
        <h1>Profil Saya</h1>
        <p>Informasi akun dan data staff Anda.</p>
    </div>

    <a class="button-link" href="<?= site_url('profile/edit') ?>">
        Edit profil
    </a>
</div>

<div class="profile-layout">
    <aside class="form-card profile-summary">
        <?php if (! empty($user['profile_photo'])): ?>
    <img
        class="profile-avatar"
        src="<?= site_url('profile/photo') ?>"
        alt="Foto profil <?= esc($user['full_name'], 'attr') ?>"
        width="120"
        height="120"
    >
<?php else: ?>
    <div class="profile-initial" aria-hidden="true">
        <?= esc(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?>
    </div>
<?php endif; ?>

        <h2><?= esc($user['full_name']) ?></h2>

        <p class="form-description">
            <?= esc($categoryLabels[$profile['staff_category'] ?? ''] ?? 'Staff') ?>
        </p>

        <span class="status-badge">Email terverifikasi</span>

        <form
    id="photo-upload-form"
    class="photo-form"
    action="<?= site_url('profile/photo') ?>"
    method="post"
    enctype="multipart/form-data"
>
    <?= csrf_field() ?>

    <label for="photo-source">Pilih foto profil</label>

    <!-- Foto asli hanya dibaca di browser. -->
    <input
        type="file"
        id="photo-source"
        accept=".jpg,.jpeg,.png,image/jpeg,image/png"
        aria-describedby="photo-help"
    >

    <!-- File hasil crop yang dikirim ke server. -->
    <input type="file" id="photo-output" name="photo" hidden>

    <small class="field-hint" id="photo-help">
        JPG/PNG, maksimal 2 MB dan 2048 × 2048 piksel.
        Setelah memilih foto, atur bagian yang ingin ditampilkan.
    </small>

    <p id="photo-message" class="photo-error" role="alert"></p>

    <noscript>
        Aktifkan JavaScript untuk mengatur dan mengunggah foto.
    </noscript>
</form>

<dialog
    id="photo-crop-dialog"
    class="crop-dialog"
    aria-labelledby="crop-title"
>
    <div class="crop-dialog-heading">
        <h2 id="crop-title">Sesuaikan foto profil</h2>
        <p>Geser foto atau bingkai crop, lalu atur zoom sesuai kebutuhan.</p>
    </div>

    <div class="crop-workspace">
        <div class="crop-stage">
            <img id="crop-image" alt="Foto yang sedang disesuaikan">
        </div>

        <div class="crop-preview-panel">
            <p>Pratinjau profil</p>
            <div class="crop-preview"></div>
        </div>
    </div>

    <div class="crop-tools">
        <button type="button" id="crop-zoom-out" class="crop-tool">
            − Perkecil
        </button>

        <button type="button" id="crop-zoom-in" class="crop-tool">
            + Perbesar
        </button>

        <button type="button" id="crop-reset" class="crop-tool">
            Atur ulang
        </button>
    </div>

    <p id="crop-message" class="photo-error" role="alert"></p>

    <div class="form-actions">
        <button type="button" id="crop-cancel" class="crop-tool">
            Batal
        </button>

        <button type="button" id="crop-save" class="btn-primary" disabled>
            Simpan foto
        </button>
    </div>
</dialog>

        <div class="profile-completeness">
            <strong><?= $isComplete ? 'Profil lengkap' : 'Profil belum lengkap' ?></strong>

            <p>
                <?= $isComplete
                    ? 'Pastikan data Anda selalu diperbarui.'
                    : 'Lengkapi data pribadi untuk melanjutkan ke dashboard.' ?>
            </p>
        </div>
    </aside>

    <section class="form-card">
        <h2>Informasi staff</h2>

        <dl class="profile-details">
            <?php foreach ($details as $label => $value): ?>
                <div>
                    <dt><?= esc($label) ?></dt>
                    <dd><?= esc($value !== '' ? $value : 'Belum diisi') ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <?php if ($isComplete): ?>
            <a class="button-link button-secondary" href="<?= site_url('dashboard') ?>">
                Buka dashboard
            </a>
        <?php else: ?>
            <a class="button-link" href="<?= site_url('profile/edit') ?>">
                Lengkapi profil
            </a>
        <?php endif; ?>
    </section>
</div>

<section class="staff-panel account-status-panel">
    <div>
        <h2>Keamanan Akun</h2>
        <p>
            Ganti password untuk menjaga keamanan akun.
            Kamu perlu memasukkan password saat ini.
        </p>
    </div>

    <a
        href="<?= site_url('profile/password') ?>"
        class="button-link secondary"
    >
        Ganti Password
    </a>
</section>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>

<link
    rel="stylesheet"
    href="<?= base_url('assets/vendor/cropper/cropper.min.css') ?>"
>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script src="<?= base_url('assets/vendor/cropper/cropper.min.js') ?>"></script>
<script src="<?= base_url('assets/js/profile-photo.js') ?>"></script>

<?= $this->endSection() ?>