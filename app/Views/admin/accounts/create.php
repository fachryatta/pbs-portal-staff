<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-heading">
    <div>
        <h1>Tambah Admin</h1>
        <p>Admin baru wajib memverifikasi email sebelum login.</p>
    </div>

    <a
        href="<?= site_url('admin/accounts') ?>"
        class="button-link secondary"
    >
        Kembali
    </a>
</div>

<section class="staff-panel">
    <form action="<?= site_url('admin/accounts') ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="field full-width">
                <label for="full_name">Nama Lengkap</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="200"
                    value="<?= esc($values['full_name'] ?? '', 'attr') ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    minlength="4"
                    maxlength="50"
                    autocomplete="off"
                    spellcheck="false"
                    value="<?= esc($values['username'] ?? '', 'attr') ?>"
                    required
                >
                <small>
                    Minimal 4 karakter: huruf, angka, tanda hubung,
                    atau underscore.
                </small>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="254"
                    value="<?= esc($values['email'] ?? '', 'attr') ?>"
                    required
                >
                <small>Gunakan email yang dapat diakses admin baru.</small>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="6"
                    maxlength="64"
                    autocomplete="new-password"
                    required
                >
                <small>Gunakan 6–64 karakter, maksimal 72 byte.</small>
            </div>

            <div class="field">
                <label for="password_confirmation">
                    Konfirmasi Password
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
        </div>

        <div class="staff-edit-actions">
            <a
                href="<?= site_url('admin/accounts') ?>"
                class="button-link secondary"
            >
                Batal
            </a>

            <button type="submit" class="btn-primary">
                Tambah Admin
            </button>
        </div>
    </form>
</section>

<?= $this->endSection() ?>