<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="form-card password-recovery-card">
    <h1>Ganti Password</h1>

    <p>
        Masukkan password saat ini, lalu tentukan password baru.
        Setelah berhasil, kamu perlu login kembali.
    </p>

    <form action="<?= site_url('profile/password') ?>" method="post">
        <?= csrf_field() ?>

        <div class="field">
            <label for="current_password">Password Saat Ini</label>
            <input
                type="password"
                id="current_password"
                name="current_password"
                maxlength="64"
                autocomplete="current-password"
                required
            >
        </div>

        <div class="field">
            <label for="password">Password Baru</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="6"
                maxlength="64"
                autocomplete="new-password"
                required
            >
            <small>
                Gunakan 6–64 karakter, maksimal 72 byte,
                dan berbeda dari password saat ini.
            </small>
        </div>

        <div class="field">
            <label for="password_confirmation">
                Konfirmasi Password Baru
            </label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                minlength="6"
                maxlength="64"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit" class="btn-primary">
            Simpan Password Baru
        </button>
    </form>

    <p class="password-recovery-back">
        <a href="<?= site_url('profile') ?>">
            Kembali ke Profil
        </a>
    </p>
</section>

<?= $this->endSection() ?>