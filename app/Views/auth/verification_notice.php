<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="form-card" style="max-width:580px;margin:auto;">
    <p class="eyebrow">VERIFIKASI EMAIL</p>

    <h2>Periksa email Anda</h2>

    <p class="form-description">
        Buka tautan pada email verifikasi yang Anda terima.
        Periksa juga folder spam. Jika belum menerima email,
        masukkan alamat yang digunakan saat registrasi.
    </p>

    <form action="<?= site_url('verification/resend') ?>" method="post">
        <?= csrf_field() ?>

        <div class="field">
            <label for="email">Email terdaftar</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                placeholder="nama@gmail.com"
                maxlength="254"
                required
            >
        </div>

        <button class="btn-primary" type="submit">
            Kirim ulang email verifikasi
        </button>
    </form>

    <p class="form-bottom">
        Sudah terverifikasi?
        <a href="<?= site_url('login') ?>">Kembali ke login</a>
    </p>
</section>

<?= $this->endSection() ?>