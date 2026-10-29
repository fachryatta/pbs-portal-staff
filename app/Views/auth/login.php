<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="auth-layout">
    <section class="intro-panel">
        <p class="eyebrow">STAFF PORTAL</p>

        <h1>Data tertata.<br>Dokumen terjaga.</h1>

        <p class="intro-copy">
            Kelola profil dan berkas sekolah melalui satu tempat
            untuk mendukung kegiatan Academic dan Academic Support.
        </p>

        <ul class="feature-list">
            <li>Lengkapi dan perbarui profil pribadi</li>
            <li>Simpan ijazah dan sertifikat Anda</li>
            <li>Akses kembali berkas saat dibutuhkan</li>
        </ul>
    </section>

    <section class="form-card" aria-labelledby="login-heading">
        <h2 id="login-heading">Selamat datang</h2>

        <p class="form-description">
            Masuk menggunakan akun staff Anda.
        </p>

        <!-- Tampilan formulir; proses login disambungkan berikutnya. -->
        <form action="<?= site_url('login') ?>" method="post">
    <?= csrf_field() ?>
            <div class="field">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Masukkan username"
                    autocomplete="username"
                    maxlength="50"
                    required
                >
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button class="btn-primary" type="submit">
    Masuk
</button>
        </form>

        <p class="form-bottom">
            Belum memiliki akun?
            <a href="<?= site_url('register') ?>">Daftar sekarang</a>
        </p>
        <p class="form-bottom">
    Belum verifikasi email?
    <a href="<?= site_url('verification') ?>">Kirim ulang tautan</a>
</p>

<p class="password-recovery-back">
    <a href="<?= site_url('forgot-password') ?>">
        Lupa Password?
    </a>
</p>
    </section>
</div>

<?= $this->endSection() ?>