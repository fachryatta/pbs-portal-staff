<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$staffId = (int) $staff['id'];

$categories = [
    'academic' => 'Academic (Guru)',
    'academic_support' => 'Academic Support',
];

$genders = [
    'male' => 'Laki-laki',
    'female' => 'Perempuan',
];

$types = [
    'diploma' => 'Ijazah',
    'competency_certificate' => 'Sertifikat Kompetensi',
    'competition_certificate' => 'Sertifikat Kompetisi',
];

$details = [
    'Nama lengkap' => $staff['full_name'],
    'Username' => $staff['username'],
    'Email' => $staff['email'],
    'Kategori staff' => $categories[$profile['staff_category'] ?? ''] ?? '',
    'Jenis kelamin' => $genders[$profile['gender'] ?? ''] ?? '',
    'Agama / kepercayaan' => $profile['religion'] ?? '',
    'Kota lahir' => $profile['birth_city'] ?? '',
    'Tanggal lahir' => ! empty($profile['birth_date'])
        ? date('d-m-Y', strtotime($profile['birth_date']))
        : '',
    'Nomor telepon' => $profile['phone'] ?? '',
    'Terdaftar sejak' => ! empty($staff['created_at'])
        ? date('d-m-Y', strtotime($staff['created_at']))
        : '',
    'Alamat' => $profile['address'] ?? '',
];
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">ADMINISTRASI STAFF</p>
        <h1>Detail Staff</h1>
        <p>Profil dan arsip berkas <?= esc($staff['full_name']) ?>.</p>
    </div>

    <div class="preview-heading-actions">
    <a
        href="<?= site_url('admin/staff') ?>"
        class="button-link secondary"
    >
        Kembali
    </a>

    <a
        href="<?= site_url('admin/staff/' . (int) $staff['id'] . '/edit') ?>"
        class="button-link"
    >
        Edit Profil
    </a>
</div>
</div>

<div class="profile-layout">
    <aside class="form-card profile-summary">
        <?php if (! empty($staff['profile_photo'])): ?>
            <img
                class="profile-avatar"
                src="<?= site_url('admin/staff/' . $staffId . '/photo') ?>"
                alt="Foto <?= esc($staff['full_name'], 'attr') ?>"
                width="120"
                height="120"
            >
        <?php else: ?>
            <div class="profile-initial" aria-hidden="true">
                <?= esc(mb_strtoupper(mb_substr($staff['full_name'], 0, 1))) ?>
            </div>
        <?php endif; ?>

        <h2><?= esc($staff['full_name']) ?></h2>

        <p class="form-description">
            <?= esc($categories[$profile['staff_category'] ?? ''] ?? 'Kategori belum tersedia') ?>
        </p>

        <div class="staff-status-list">
            <span class="admin-badge <?= (int) $staff['is_active'] === 1 ? 'badge-green' : 'badge-gray' ?>">
                <?= (int) $staff['is_active'] === 1 ? 'Akun aktif' : 'Akun nonaktif' ?>
            </span>

            <span class="admin-badge <?= $staff['email_verified_at'] !== null ? 'badge-green' : 'badge-amber' ?>">
                <?= $staff['email_verified_at'] !== null
                    ? 'Email terverifikasi'
                    : 'Email belum terverifikasi' ?>
            </span>

            <span class="admin-badge <?= $isComplete ? 'badge-green' : 'badge-amber' ?>">
                <?= $isComplete ? 'Profil lengkap' : 'Profil belum lengkap' ?>
            </span>
        </div>

        <div class="profile-completeness">
            <strong><?= count($documents) ?> dokumen tersimpan</strong>
            <p>Ijazah dan sertifikat milik staff ini.</p>
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
    </section>
</div>

<section class="staff-panel staff-documents-panel">
    <div class="staff-panel-heading">
        <div>
            <h2>Dokumen staff</h2>
            <p>Pratinjau dan unduh berkas yang telah diunggah.</p>
        </div>

        <span class="staff-count"><?= count($documents) ?> berkas</span>
    </div>

    <?php if (empty($documents)): ?>
        <div class="staff-empty">
            <h3>Belum ada dokumen</h3>
            <p>Staff ini belum mengunggah ijazah atau sertifikat.</p>
        </div>
    <?php else: ?>
        <div
            class="staff-table-wrap"
            role="region"
            aria-label="Dokumen staff"
            tabindex="0"
        >
            <table class="staff-document-table">
                <thead>
                    <tr>
                        <th scope="col">Dokumen</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Tahun</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($documents as $document): ?>
                        <?php
                        $base = 'admin/staff/' . $staffId
                            . '/documents/' . (int) $document['id'];
                        ?>

                        <tr>
                            <td>
                                <strong><?= esc($document['title']) ?></strong>

                                <small>
                                    <?= esc($document['issuer'] ?: 'Penerbit belum dicantumkan') ?>
                                </small>

                                <small><?= esc($document['original_name']) ?></small>
                            </td>

                            <td>
                                <?= esc($types[$document['document_type']] ?? 'Dokumen') ?>
                            </td>

                            <td><?= esc($document['issue_year'] ?? '—') ?></td>

                            <td>
                                <div class="document-actions">
                                    <a
                                        class="button-link"
                                        href="<?= site_url($base . '/preview') ?>"
                                        aria-label="Lihat <?= esc($document['title'], 'attr') ?>"
                                    >
                                        Lihat
                                    </a>

                                    <a
                                        class="button-link button-secondary"
                                        href="<?= site_url($base . '/download') ?>"
                                        aria-label="Unduh <?= esc($document['title'], 'attr') ?>"
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
    <?php endif; ?>
</section>

<?php if (($staff['access_role'] ?? '') === 'user'): ?>
<section class="staff-panel account-status-panel">
    <div>
        <h2>Akses Akun</h2>

        <?php if ((int) $staff['is_active'] === 1): ?>
            <p>
                Akun ini aktif. Menonaktifkan akun akan menghentikan
                akses staff tanpa menghapus profil dan dokumennya.
            </p>
        <?php else: ?>
            <p>
                Akun ini sedang nonaktif dan tidak dapat mengakses portal.
                Profil dan dokumennya tetap tersimpan.
            </p>
        <?php endif; ?>
    </div>

    <form
        action="<?= site_url('admin/staff/' . (int) $staff['id'] . '/status') ?>"
        method="post"
        class="account-status-form"
        onsubmit="return confirm('<?= (int) $staff['is_active'] === 1
            ? 'Nonaktifkan akses akun staff ini? Profil dan dokumen tetap tersimpan.'
            : 'Aktifkan kembali akun staff ini?' ?>')"
    >
        <?= csrf_field() ?>

        <input
            type="hidden"
            name="is_active"
            value="<?= (int) $staff['is_active'] === 1 ? '0' : '1' ?>"
        >

        <?php if ((int) $staff['is_active'] === 1): ?>
            <button type="submit" class="button-danger">
                Nonaktifkan Akun
            </button>
        <?php else: ?>
            <button type="submit" class="btn-primary">
                Aktifkan Akun
            </button>
        <?php endif; ?>
    </form>
</section>
<?php else: ?>

    <section class="staff-panel account-status-panel">
        <div>
            <h2>Akun Admin sekaligus Staff</h2>
            <p>
                Akun ini memiliki akses administrasi serta profil
                dan dokumen pribadi sebagai staff.
                Status akun admin tidak diubah melalui halaman ini.
            </p>
        </div>
    </section>

<?php endif; ?>

<?php if (($staff['access_role'] ?? '') === 'user'): ?>
    <?php
    $canPromote = (int) $staff['is_active'] === 1
        && ! empty($staff['email_verified_at']);
    ?>

    <section class="staff-panel promotion-panel">
        <div class="promotion-heading">
            <span class="promotion-label">HAK AKSES</span>
            <h2>Angkat Menjadi Admin</h2>
            <p>
                Berikan akses kepada staff ini untuk mengelola data
                seluruh staff dan menambahkan admin baru.
                Akun, email, profil, dan dokumen pribadinya tetap sama.
            </p>
        </div>

        <?php if ($canPromote): ?>
            <form
                action="<?= site_url(
                    'admin/staff/' . (int) $staff['id'] . '/promote'
                ) ?>"
                method="post"
                onsubmit="return confirm(
                    'Berikan hak akses admin kepada staff ini? Akun tersebut akan dapat mengakses data seluruh staff.'
                )"
            >
                <?= csrf_field() ?>

                <label class="promotion-confirm">
                    <input
                        type="checkbox"
                        name="confirm_promotion"
                        value="1"
                        required
                    >
                    <span>
                        Saya menyetujui pemberian hak akses admin
                        kepada
                        <strong><?= esc($staff['full_name']) ?></strong>.
                    </span>
                </label>

                <div class="promotion-actions">
                    <button type="submit" class="btn-primary">
                        Angkat Menjadi Admin
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="promotion-notice">
                Akun harus aktif dan email harus terverifikasi
                sebelum dapat diangkat menjadi admin.
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?= $this->endSection() ?>