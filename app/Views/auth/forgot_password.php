<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="form-card password-recovery-card">
    <h1>Lupa Password?</h1>
    <p>
        Masukkan email akunmu. Kami akan mengirimkan
        tautan untuk membuat password baru.
    </p>

    <form action="<?= site_url('forgot-password') ?>" method="post">
        <?= csrf_field() ?>

        <div class="field">
            <label for="email">Email Terdaftar</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="254"
                autocomplete="email"
                required
            >
        </div>

        <button type="submit" class="btn-primary">
            Kirim Tautan Reset
        </button>
    </form>

    <p class="password-recovery-back">
        <a href="<?= site_url('login') ?>">Kembali ke Login</a>
    </p>
</section>

<?= $this->endSection() ?>