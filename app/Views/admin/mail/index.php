<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-heading">
    <div>
        <h1>Email Pengirim</h1>
        <p>Hubungkan akun Microsoft 365 untuk email portal sekolah.</p>
    </div>

    <a
        href="<?= site_url('admin/dashboard') ?>"
        class="button-link secondary"
    >
        Kembali
    </a>
</div>

<section class="staff-panel">
    <h2>Akun Microsoft 365</h2>

    <p>
        Email pengirim:
        <strong><?= esc($senderEmail) ?></strong>
    </p>

    <?php if ($connection): ?>
        <div class="mail-connection-status">
            <strong>Koneksi tersimpan</strong>

            <p>
                Akun:
                <?= esc($connection['sender_email']) ?>
            </p>

            <p>
                Terakhir disimpan:
                <?= esc($connection['updated_at']) ?>
            </p>
        </div>
    <?php else: ?>
        <div class="mail-connection-status">
            <strong>Belum terhubung</strong>
            <p>
                Hubungkan akun pengirim dan berikan izin
                melalui halaman Microsoft.
            </p>
        </div>
    <?php endif; ?>

    <p>
        Gunakan akun Microsoft yang tercantum di atas.
        Password akun dimasukkan hanya pada halaman Microsoft.
    </p>

    <form
        action="<?= site_url('admin/mail/connect') ?>"
        method="post"
        class="staff-edit-actions"
    >
        <?= csrf_field() ?>

        <button type="submit" class="btn-primary">
            <?= $connection
                ? 'Hubungkan Ulang Microsoft'
                : 'Hubungkan Microsoft' ?>
        </button>
    </form>

    <?php if ($connection): ?>
    <form
        action="<?= site_url('admin/mail/test') ?>"
        method="post"
        class="staff-edit-actions"
    >
        <?= csrf_field() ?>

        <button type="submit" class="btn-primary">
            Kirim Email Tes
        </button>
    </form>

    <p>
        Email percobaan dikirim ke
        <strong><?= esc($senderEmail) ?></strong>.
    </p>
<?php endif; ?>
</section>

<?= $this->endSection() ?>