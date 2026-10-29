<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-heading">
    <div>
        <p class="eyebrow">LAYANAN STAFF</p>
        <h1>Pengajuan Cuti &amp; Izin</h1>
        <p>
            Ajukan cuti atau izin dan pantau proses konfirmasinya
            melalui portal Permata Bangsa School.
        </p>
    </div>

    
<a
    class="button-link button-secondary"
    href="<?= site_url('staff/leave-requests/history') ?>"
>
    Riwayat Pengajuan
</a>

</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success" role="status">
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>

<?php $errors = session()->getFlashdata('errors') ?? []; ?>

<?php if (! empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <strong>Pengajuan belum dapat diproses.</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="staff-panel">
    <div class="staff-panel-heading">
        <div>
            <h2>Formulir pengajuan</h2>
            <p>
                Lengkapi informasi berikut sebelum mengirim
                permohonan kepada admin/HRD.
            </p>
        </div>
    </div>

    <form
        action="<?= site_url('staff/leave-requests') ?>"
        method="post"
    >
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="field">
                <label for="request_type">Jenis pengajuan</label>
                <select
                    id="request_type"
                    name="request_type"
                    required
                >
                    <option value="">Pilih jenis pengajuan</option>
                    <option
                        value="cuti"
                        <?= old('request_type', $values['request_type'] ?? '') === 'cuti'
                            ? 'selected' : '' ?>
                    >
                        Cuti
                    </option>
                    <option
                        value="izin"
                        <?= old('request_type', $values['request_type'] ?? '') === 'izin'
                            ? 'selected' : '' ?>
                    >
                        Izin
                    </option>
                </select>
                <small class="field-hint">
                    Cuti digunakan untuk kategori khusus yang
                    tersedia di bawah. Keperluan lainnya masuk
                    ke permohonan izin.
                </small>
            </div>

            <div
                class="field"
                id="leave-category-field"
                hidden
            >
                <label for="leave_category">Kategori cuti</label>
                <select
                    id="leave_category"
                    name="leave_category"
                >
                    <option value="">Pilih kategori cuti</option>

                    <?php foreach ($categories as $key => $label): ?>
                        <option
                            value="<?= esc($key, 'attr') ?>"
                            <?= old(
                                'leave_category',
                                $values['leave_category'] ?? ''
                            ) === $key ? 'selected' : '' ?>
                        >
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="start_date">Tanggal mulai</label>
                <input
                    type="date"
                    id="start_date"
                    name="start_date"
                    value="<?= esc(old('start_date', $values['start_date'] ?? ''), 'attr') ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="end_date">Tanggal selesai</label>
                <input
                    type="date"
                    id="end_date"
                    name="end_date"
                    value="<?= esc(old('end_date', $values['end_date'] ?? ''), 'attr') ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="reason">Alasan / keterangan</label>
                <textarea
                    id="reason"
                    name="reason"
                    rows="5"
                    minlength="10"
                    maxlength="2000"
                    required
                    placeholder="Jelaskan keperluan pengajuan Anda..."
                ><?= esc(old('reason', $values['reason'] ?? '')) ?></textarea>
                <small class="field-hint">
                    Minimal 10 karakter. Sertakan informasi
                    yang diperlukan agar HRD dapat meninjau
                    pengajuan dengan jelas.
                </small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                Kirim Pengajuan
            </button>

            <a
                href="<?= site_url('staff/dashboard') ?>"
                class="button-link button-secondary"
            >
                Batal
            </a>
        </div>
    </form>
</section>

<script>
(function () {
    const typeSelect = document.getElementById('request_type');
    const categoryField = document.getElementById('leave-category-field');
    const categorySelect = document.getElementById('leave_category');

    function updateCategoryField() {
        const isLeave = typeSelect.value === 'cuti';

        categoryField.hidden = !isLeave;
        categorySelect.required = isLeave;

        if (!isLeave) {
            categorySelect.value = '';
        }
    }

    typeSelect.addEventListener('change', updateCategoryField);
    updateCategoryField();
})();
</script>

<?= $this->endSection() ?>
