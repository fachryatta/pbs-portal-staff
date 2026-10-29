<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$staffId = (int) $staff['id'];

$selectFields = [
    'staff_category' => [
        'label' => 'Kategori Staff',
        'options' => [
            'academic' => 'Academic',
            'academic_support' => 'Academic Support',
        ],
    ],
    'gender' => [
        'label' => 'Jenis Kelamin',
        'options' => [
            'male' => 'Laki-laki',
            'female' => 'Perempuan',
        ],
    ],
];

$textFields = [
    'religion' => [
        'label' => 'Agama',
        'type' => 'text',
        'max' => 50,
    ],
    'birth_city' => [
        'label' => 'Kota Lahir',
        'type' => 'text',
        'max' => 100,
    ],
    'birth_date' => [
        'label' => 'Tanggal Lahir',
        'type' => 'date',
        'max' => null,
    ],
    'phone' => [
        'label' => 'Nomor Telepon',
        'type' => 'tel',
        'max' => 16,
    ],
];
?>

<div class="page-heading">
    <div>
        <h1>Edit Profil Staff</h1>
        <p><?= esc($staff['full_name']) ?></p>
    </div>

    <a
        href="<?= site_url('admin/staff/' . $staffId) ?>"
        class="button-link secondary"
    >
        Kembali
    </a>
</div>

<section class="staff-panel">
    <div class="staff-edit-account">
        <p>
            <strong>Username:</strong>
            <?= esc($staff['username']) ?>
        </p>
        <p>
            <strong>Email:</strong>
            <?= esc($staff['email']) ?>
        </p>
    </div>

    <form
        action="<?= site_url('admin/staff/' . $staffId . '/update') ?>"
        method="post"
    >
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="field full-width">
                <label for="full_name">Nama Lengkap *</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="200"
                    value="<?= esc($values['full_name'], 'attr') ?>"
                    required
                >
                <small>Cantumkan gelar jika ada.</small>
            </div>

            <?php foreach ($selectFields as $name => $field): ?>
                <div class="field">
                    <label for="<?= esc($name, 'attr') ?>">
                        <?= esc($field['label']) ?>
                        <?= $name === 'staff_category' ? '*' : '' ?>
                    </label>

                    <select
                        id="<?= esc($name, 'attr') ?>"
                        name="<?= esc($name, 'attr') ?>"
                        <?= $name === 'staff_category' ? 'required' : '' ?>
                    >
                        <option value="">Pilih...</option>

                        <?php foreach ($field['options'] as $key => $label): ?>
                            <option
                                value="<?= esc($key, 'attr') ?>"
                                <?= $values[$name] === $key ? 'selected' : '' ?>
                            >
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>

            <?php foreach ($textFields as $name => $field): ?>
                <div class="field">
                    <label for="<?= esc($name, 'attr') ?>">
                        <?= esc($field['label']) ?>
                    </label>

                    <input
                        type="<?= esc($field['type'], 'attr') ?>"
                        id="<?= esc($name, 'attr') ?>"
                        name="<?= esc($name, 'attr') ?>"
                        value="<?= esc($values[$name], 'attr') ?>"
                        <?php if ($field['max'] !== null): ?>
                            maxlength="<?= (int) $field['max'] ?>"
                        <?php endif; ?>
                        <?php if ($name === 'birth_date'): ?>
                            max="<?= date('Y-m-d') ?>"
                        <?php endif; ?>
                    >

                    <?php if ($name === 'phone'): ?>
                        <small>
                            Gunakan 8–15 digit tanpa spasi.
                            Boleh diawali +.
                        </small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="field full-width">
                <label for="address">Alamat</label>
                <textarea
                    id="address"
                    name="address"
                    rows="4"
                    maxlength="2000"
                ><?= esc($values['address']) ?></textarea>
            </div>
        </div>

        <div class="staff-edit-actions">
            <a
                href="<?= site_url('admin/staff/' . $staffId) ?>"
                class="button-link secondary"
            >
                Batal
            </a>

            <button type="submit" class="btn-primary">
                Simpan Perubahan
            </button>
        </div>
    </form>
</section>

<?= $this->endSection() ?>