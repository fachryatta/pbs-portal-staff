<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="form-card" style="max-width:580px;margin:auto;">
    <p class="eyebrow">KONFIRMASI EMAIL</p>

    <h2>Verifikasi alamat email</h2>

    <p class="form-description">
        Tekan tombol berikut untuk mengonfirmasi alamat email
        akun Portal Staff Anda.
    </p>

    <form action="<?= site_url('verify-email') ?>" method="post">
        <?= csrf_field() ?>

        <input
            type="hidden"
            name="token"
            value="<?= esc($token, 'attr') ?>"
        >

        <button class="btn-primary" type="submit">
            Konfirmasi verifikasi
        </button>
    </form>
</section>

<?= $this->endSection() ?>