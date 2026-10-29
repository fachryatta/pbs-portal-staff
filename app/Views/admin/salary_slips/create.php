<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
    4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September',
    10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

$categories = [
    'academic' => 'Academic',
    'academic_support' => 'Academic Support',
];

$componentLabels = [
    'earning' => 'Pendapatan tambahan',
    'deduction' => 'Potongan',
];

$isEdit = $isEdit ?? false;

$formPath = $isEdit
    ? 'admin/salary-slips/' . (int) $slip['id'] . '/update'
    : 'admin/salary-slips';

$backPath = $isEdit
    ? 'admin/salary-slips/' . (int) $slip['id']
    : 'admin/salary-slips';

$schoolComponentLabels = [
    'earning' => [
        'Tunjangan',
        'Tunjangan lain-lain',
        'Overtime',
    ],
    'deduction' => [
        'BPJS Tenagakerja',
        'BPJS Kesehatan',
    ],
];
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">ADMINISTRASI PENGGAJIAN</p>
        <h1><?= $isEdit ? 'Edit Draft Slip Gaji' : 'Buat Slip Gaji' ?></h1>

<p>
    <?= $isEdit
        ? 'Perbarui rincian gaji untuk staff pada slip ini.'
        : 'Isi rincian gaji untuk disimpan sebagai draft.' ?>
</p>
    </div>

    <a
        class="button-link button-secondary"
        href="<?= site_url($backPath) ?>"
    >
        Kembali
    </a>
</div>

<form
    id="salary-form"
    class="staff-panel salary-form"
    action="<?= site_url($formPath) ?>"
    method="post"
>
    <?= csrf_field() ?>

    <?php if ($isEdit): ?>
    <div class="salary-list-description">
        <strong><?= esc($slip['staff_name']) ?></strong>

        <p>
            @<?= esc($slip['staff_username']) ?>
            ·
            <?= esc(
                $categories[$slip['staff_category'] ?? ''] ?? 'Staff'
            ) ?>
        </p>

        <p>
            Periode:
            <strong>
                <?= esc(
                    ($months[(int) $slip['period_month']] ?? '—')
                    . ' ' . $slip['period_year']
                ) ?>
            </strong>
        </p>

        <p>Perubahan hanya berlaku untuk draft staff ini.</p>
    </div>
<?php else: ?>

    <div class="salary-form-grid">
        <div class="salary-staff-picker" id="salary-staff-picker">
    <?php
    $selectedStaffIds = array_map(
        'strval',
        $values['staff_ids'] ?? []
    );
    ?>

    <div class="salary-picker-heading">
        <div>
            <h2>Staff penerima</h2>
            <p>Pilih satu atau beberapa staff dengan rincian gaji yang sama.</p>
        </div>

        <div class="salary-form-actions">
            <button
                type="button"
                class="button-link button-secondary"
                id="salary-select-all"
            >
                Pilih Semua Staff
            </button>

            <button
                type="button"
                class="button-link button-secondary"
                id="salary-clear-selection"
            >
                Kosongkan Pilihan
            </button>
        </div>
    </div>

    <label for="salary-staff-search">Cari staff</label>
    <input
        id="salary-staff-search"
        type="search"
        placeholder="Ketik nama, username, atau kategori staff..."
        autocomplete="off"
    >

    <p
        id="salary-selection-count"
        class="salary-picker-description"
        aria-live="polite"
    ></p>

    <div class="salary-staff-options">
        <?php foreach ($staff as $person): ?>
            <?php
            $categoryName = $categories[
                $person['staff_category'] ?? ''
            ] ?? 'Staff';

            $searchText = $person['full_name']
                . ' ' . $person['username']
                . ' ' . $categoryName;
            ?>

            <label
                class="salary-staff-choice"
                data-search="<?= esc($searchText, 'attr') ?>"
            >
                <input
                    type="checkbox"
                    name="staff_ids[]"
                    value="<?= (int) $person['id'] ?>"
                    <?= in_array(
                        (string) $person['id'],
                        $selectedStaffIds,
                        true
                    ) ? 'checked' : '' ?>
                >

                <span class="salary-staff-choice-copy">
                    <strong><?= esc($person['full_name']) ?></strong>

                    <small>
                        @<?= esc($person['username']) ?>
                        · <?= esc($categoryName) ?>
                    </small>
                </span>
            </label>
        <?php endforeach; ?>

        <p id="salary-no-staff-results" hidden>
            Tidak ada staff yang sesuai dengan pencarian.
        </p>
    </div>

    <p class="salary-picker-description">
        Pilih Semua Staff mencentang seluruh daftar, termasuk nama yang
        sedang tersembunyi oleh pencarian. Pencarian tidak menghapus pilihan.
    </p>
</div>

        <div class="field">
            <label for="salary-month">Bulan</label>
            <select id="salary-month" name="period_month" required>
                <?php foreach ($months as $number => $name): ?>
                    <option
                        value="<?= $number ?>"
                        <?= (int) $values['period_month'] === $number
                            ? 'selected'
                            : '' ?>
                    >
                        <?= esc($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="salary-year">Tahun</label>
            <input
                id="salary-year"
                type="number"
                name="period_year"
                min="2000"
                max="2100"
                step="1"
                value="<?= esc($values['period_year'], 'attr') ?>"
                required
            >
        </div>
    </div>

    <?php endif; ?>

    <div class="field">
        <label for="basic-salary">Gaji pokok (Rp)</label>
        <input
            id="basic-salary"
            type="number"
            name="basic_salary"
            min="0"
            max="100000000"
            step="1"
            value="<?= esc($values['basic_salary'], 'attr') ?>"
            required
        >
    </div>

    <p class="salary-list-description">
        Masukkan rupiah bulat tanpa titik atau koma.
        Contoh: 3500000 untuk Rp3.500.000.
        Pendapatan tambahan dan potongan boleh dikosongkan.
    </p>

    <div class="salary-component-heading">
        <h2>Komponen tambahan</h2>

        <div class="salary-form-actions">
            <button
                type="button"
                class="button-link button-secondary"
                data-add-type="earning"
            >
                + Pendapatan
            </button>

            <button
                type="button"
                class="button-link button-secondary"
                data-add-type="deduction"
            >
                + Potongan
            </button>
        </div>
    </div>

    <p class="salary-template-description">
    Isi nominal komponen sesuai rincian sekolah.
    Biarkan 0 jika tidak digunakan.
    Gunakan tombol tambah untuk pendapatan atau potongan lainnya.
</p>

    <div id="salary-items">
    <?php foreach (($values['items'] ?? []) as $index => $item): ?>
        <?php
        $itemType = ($item['item_type'] ?? '') === 'deduction'
            ? 'deduction'
            : 'earning';

        $itemLabel = (string) ($item['label'] ?? '');

        $isSchoolComponent = in_array(
            $itemLabel,
            $schoolComponentLabels[$itemType],
            true
        );

        $componentTitle = $itemType === 'earning'
            ? 'Pendapatan'
            : 'Potongan';
        ?>

        <div class="salary-item-row">
            <input
                type="hidden"
                name="items[<?= (int) $index ?>][item_type]"
                value="<?= esc($itemType, 'attr') ?>"
            >

            <label class="field">
                <span><?= esc($componentTitle) ?></span>

                <input
                    type="text"
                    name="items[<?= (int) $index ?>][label]"
                    value="<?= esc($itemLabel, 'attr') ?>"
                    maxlength="100"
                    required
                    <?= $isSchoolComponent ? 'readonly' : '' ?>
                >
            </label>

            <label class="field">
                <span>Nominal (Rp)</span>

                <input
                    type="number"
                    name="items[<?= (int) $index ?>][amount]"
                    value="<?= esc(
                        (string) ($item['amount'] ?? '0'),
                        'attr'
                    ) ?>"
                    min="0"
                    max="100000000"
                    step="1"
                    required
                >
            </label>

            <?php if ($isSchoolComponent): ?>
                <span class="salary-template-label">
                    Template sekolah
                </span>
            <?php else: ?>
                <button
                    type="button"
                    class="salary-remove"
                    data-remove-item
                >
                    Hapus
                </button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

    <p id="salary-component-message" role="status"></p>

    <div class="field">
        <label for="salary-notes">Catatan (opsional)</label>
        <textarea
            id="salary-notes"
            name="notes"
            rows="3"
            maxlength="2000"
        ><?= esc($values['notes']) ?></textarea>
    </div>

    <div class="salary-form-actions">
        <button type="submit" class="btn-primary">
            <?= $isEdit
    ? 'Simpan Perubahan'
    : 'Simpan Draft untuk Staff Terpilih' ?>
        </button>

        <a
            class="button-link button-secondary"
            href="<?= site_url($backPath) ?>"
        >
            Batal
        </a>
    </div>
</form>

<template id="salary-item-template">
    <div class="salary-item-row">
        <input
            type="hidden"
            name="items[__INDEX__][item_type]"
            data-item-type
        >

        <label class="field">
            <span data-item-label></span>
            <input
                type="text"
                name="items[__INDEX__][label]"
                maxlength="100"
                placeholder="Nama komponen"
                required
            >
        </label>

        <label class="field">
            <span>Nominal (Rp)</span>
            <input
                type="number"
                name="items[__INDEX__][amount]"
                min="0"
                max="100000000"
                step="1"
                value="0"
                required
            >
        </label>

        <button
            type="button"
            class="salary-remove"
            data-remove-item
        >
            Hapus baris
        </button>
    </div>
</template>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script
    src="<?= base_url('assets/js/salary-form.js') ?>"
    defer
></script>

<script
    src="<?= base_url('assets/js/salary-staff-picker.js') ?>"
    defer
></script>

<?= $this->endSection() ?>