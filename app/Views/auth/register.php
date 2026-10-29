<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="auth-layout">
    <section class="intro-panel">
        <p class="eyebrow">BERGABUNG DI PORTAL STAFF</p>

        <h1>Mulai dari<br>profil Anda.</h1>

        <p class="intro-copy">
            Buat akun untuk mengelola data pribadi, ijazah,
            serta sertifikat kompetensi dan kompetisi.
        </p>

        <ul class="feature-list">
            <li>Daftarkan akun dengan email aktif</li>
            <li>Verifikasi email dan lengkapi profil</li>
            <li>Unggah dokumen sesuai kategorinya</li>
        </ul>
    </section>

    <section class="form-card" aria-labelledby="register-heading">
        <h2 id="register-heading">Buat akun staff</h2>

        <p class="form-description">
            Isi data awal berikut untuk mendaftar.
        </p>

        <!-- Tampilan formulir; proses registrasi disambungkan berikutnya. -->
        <form action="<?= site_url('register') ?>" method="post">
    <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field full-width">
                    <label for="full_name">Nama lengkap</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="Nama lengkap beserta gelar"
                        autocomplete="name"
                        maxlength="200"
                        aria-describedby="name-hint"
                        required
                    >
                    <small class="field-hint" id="name-hint">
                        Cantumkan gelar jika ada.
                    </small>
                </div>

                <div class="field">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Username untuk login"
                        autocomplete="username"
                        maxlength="50"
                        required
                    >
                </div>

                <div class="field">
                    <label for="staff_category">Kategori staff</label>
                    <select
                        id="staff_category"
                        name="staff_category"
                        required
                    >
                        <option value="">Pilih kategori</option>

<option
    value="academic"
    <?= ($values['staff_category'] ?? '') === 'academic' ? 'selected' : '' ?>
>
    Academic (Guru)
</option>

<option
    value="academic_support"
    <?= ($values['staff_category'] ?? '') === 'academic_support' ? 'selected' : '' ?>
>
    Academic Support (Selain Guru)
</option>
                    </select>
                </div>

                <div class="field full-width">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="nama@email.com"
                        autocomplete="email"
                        maxlength="254"
                        aria-describedby="email-hint"
                        required
                    >
                    <small class="field-hint" id="email-hint">
                        Gunakan email aktif untuk menerima tautan verifikasi.
                    </small>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Buat password"
                        autocomplete="new-password"
                        required
                        minlength="6"
                        maxlength="64"
                    >
                    <small class="field-hint">
    Gunakan 6–64 karakter. Pilih password yang tidak digunakan di akun lain.
</small>
                </div>

                <div class="field">
                    <label for="password_confirm">Konfirmasi password</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        placeholder="Ulangi password"
                        autocomplete="new-password"
                        required
                    >
                </div>
            </div>

            <button class="btn-primary" type="submit">
    Buat akun
</button>
        </form>

        <p class="form-bottom">
            Sudah memiliki akun?
            <a href="<?= site_url('login') ?>">Masuk di sini</a>
        </p>
    </section>
</div>

<?= $this->endSection() ?>