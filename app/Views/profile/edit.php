<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-heading">
    <div>
        <p class="eyebrow">DATA PRIBADI</p>

        <h1><?= $isComplete ? 'Edit Profil' : 'Lengkapi Profil Anda' ?></h1>

        <p>Isi seluruh data berikut sesuai identitas Anda.</p>
    </div>
</div>

<section class="form-card profile-editor">
    <p class="form-description">
        Akun: <strong><?= esc($user['username']) ?></strong><br>
        Email terverifikasi: <?= esc($user['email']) ?>
    </p>

    <form action="<?= site_url('profile') ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="field full-width">
                <label for="full_name">Nama lengkap beserta gelar</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= esc($values['full_name'] ?? '', 'attr') ?>"
                    maxlength="200"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="field">
                <label for="staff_category">Kategori staff</label>
                <select id="staff_category" name="staff_category" required>
                    <option value="">Pilih kategori</option>

                    <?php foreach ([
                        'academic' => 'Academic (Guru)',
                        'academic_support' => 'Academic Support',
                    ] as $value => $label): ?>
                        <option
                            value="<?= esc($value, 'attr') ?>"
                            <?= ($values['staff_category'] ?? '') === $value ? 'selected' : '' ?>
                        >
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="gender">Jenis kelamin</label>
                <select id="gender" name="gender" required>
                    <option value="">Pilih jenis kelamin</option>

                    <option
                        value="male"
                        <?= ($values['gender'] ?? '') === 'male' ? 'selected' : '' ?>
                    >
                        Laki-laki
                    </option>

                    <option
                        value="female"
                        <?= ($values['gender'] ?? '') === 'female' ? 'selected' : '' ?>
                    >
                        Perempuan
                    </option>
                </select>
            </div>

            <div class="field">
                <label for="religion">Agama / kepercayaan</label>
                <input
                    type="text"
                    id="religion"
                    name="religion"
                    value="<?= esc($values['religion'] ?? '', 'attr') ?>"
                    maxlength="50"
                    required
                >
            </div>

            <div class="field">
                <label for="phone">Nomor telepon</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?= esc($values['phone'] ?? '', 'attr') ?>"
                    placeholder="08xxxxxxxxxx"
                    maxlength="25"
                    autocomplete="tel"
                    aria-describedby="phone-hint"
                    required
                >
                <small class="field-hint" id="phone-hint">
                    Tanpa spasi atau tanda hubung. Boleh diawali +62.
                </small>
            </div>

            <div class="field">
                <label for="birth_city">Kota lahir</label>
                <input
                    type="text"
                    id="birth_city"
                    name="birth_city"
                    value="<?= esc($values['birth_city'] ?? '', 'attr') ?>"
                    maxlength="100"
                    required
                >
            </div>

            <div class="field">
                <label for="birth_date">Tanggal lahir</label>
                <input
                    type="date"
                    id="birth_date"
                    name="birth_date"
                    value="<?= esc($values['birth_date'] ?? '', 'attr') ?>"
                    max="<?= date('Y-m-d') ?>"
                    autocomplete="bday"
                    required
                >
            </div>

            <div class="field full-width">
                <label for="address">Alamat lengkap</label>
                <textarea
                    id="address"
                    name="address"
                    rows="4"
                    maxlength="2000"
                    autocomplete="street-address"
                    required
                ><?= esc($values['address'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button-link button-secondary" href="<?= site_url('profile') ?>">
                Kembali
            </a>

            <button class="btn-primary" type="submit">
                Simpan profil
            </button>
        </div>
    </form>
</section>

<?= $this->endSection() ?>