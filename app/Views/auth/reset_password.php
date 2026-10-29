<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="form-card password-recovery-card">
    <h1>Buat Password Baru</h1>
    <p>
        Gunakan 6–64 karakter, maksimal 72 byte.
        Setelah berhasil, silakan login kembali.
    </p>

    <form action="<?= site_url('reset-password') ?>" method="post">
        <?= csrf_field() ?>

        <input
            type="hidden"
            name="token"
            value="<?= esc($token, 'attr') ?>"
        >

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
</section>

<?= $this->endSection() ?>