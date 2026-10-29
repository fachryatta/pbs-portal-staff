<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-heading">
    <div>
        <p class="eyebrow">ARSIP PRIBADI</p>
        <h1>Upload Dokumen</h1>
        <p>Tambahkan ijazah atau sertifikat ke arsip Anda.</p>
    </div>
</div>

<section class="form-card profile-editor">
    <form
        action="<?= site_url('documents') ?>"
        method="post"
        enctype="multipart/form-data"
    >
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="field full-width">
                <label for="document_type">Kategori dokumen</label>

                <select id="document_type" name="document_type" required>
                    <option value="">Pilih kategori dokumen</option>

                    <?php foreach ($types as $value => $label): ?>
                        <option
                            value="<?= esc($value, 'attr') ?>"
                            <?= ($values['document_type'] ?? '') === $value ? 'selected' : '' ?>
                        >
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field full-width">
                <label for="title">Judul dokumen</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?= esc($values['title'] ?? '', 'attr') ?>"
                    placeholder="Contoh: Ijazah S1 Pendidikan Bahasa Inggris"
                    maxlength="200"
                    required
                >
            </div>

            <div class="field">
                <label for="issuer">Instansi penerbit — opsional</label>

                <input
                    type="text"
                    id="issuer"
                    name="issuer"
                    value="<?= esc($values['issuer'] ?? '', 'attr') ?>"
                    placeholder="Nama sekolah, kampus, atau penyelenggara"
                    maxlength="200"
                >
            </div>

            <div class="field">
                <label for="issue_year">Tahun terbit — opsional</label>

                <input
                    type="number"
                    id="issue_year"
                    name="issue_year"
                    value="<?= esc($values['issue_year'] ?? '', 'attr') ?>"
                    min="1900"
                    max="<?= date('Y') ?>"
                    step="1"
                    placeholder="<?= date('Y') ?>"
                >
            </div>

            <div class="field full-width">
                <label for="document">File dokumen</label>

                <input
                    type="file"
                    id="document"
                    name="document"
                    accept=".pdf,.jpg,.jpeg,.png"
                    aria-describedby="document-help"
                    required
                >

                <small class="field-hint" id="document-help">
                    PDF, JPG, atau PNG. Maksimal 5 MB per file.
                    Pastikan isi dokumen terbaca dengan jelas.
                </small>
            </div>
        </div>

        <div class="form-actions">
            <a class="button-link button-secondary" href="<?= site_url('documents') ?>">
                Kembali
            </a>

            <button class="btn-primary" type="submit">
                Upload dokumen
            </button>
        </div>
    </form>
</section>

<?= $this->endSection() ?>