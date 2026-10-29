<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="admin-list-page">

<?php
$summaryCards = [
    [
        'label' => 'Total Dokumen',
        'value' => $documentSummary['total'],
        'note' => 'Seluruh berkas staff tersimpan',
        'class' => 'stat-total',
    ],
    [
        'label' => 'Ijazah',
        'value' => $documentSummary['diploma'],
        'note' => 'Berkas pendidikan staff',
        'class' => 'stat-diploma',
    ],
    [
        'label' => 'Sertifikat Kompetensi',
        'value' => $documentSummary['competency_certificate'],
        'note' => 'Berkas kompetensi staff',
        'class' => 'stat-competency',
    ],
    [
        'label' => 'Sertifikat Kompetisi',
        'value' => $documentSummary['competition_certificate'],
        'note' => 'Berkas kompetisi staff',
        'class' => 'stat-competition',
    ],
];

$staffCategories = [
    'academic' => 'Academic',
    'academic_support' => 'Academic Support',
];

$hasFilter = $search !== ''
    || $category !== ''
    || $staffCategory !== ''
    || $year !== '';
?>

<div class="page-heading admin-list-hero">
    <div>
        <p class="eyebrow">ARSIP SEKOLAH</p>
        <h1>Semua Dokumen</h1>
        <p>Telusuri ijazah dan sertifikat yang diunggah seluruh staff.</p>
    </div>
</div>

<div class="staff-statistics admin-summary-grid">
    <?php foreach ($summaryCards as $card): ?>
        <section class="staff-stat <?= esc($card['class'], 'attr') ?>">
            <h2><?= esc($card['label']) ?></h2>
            <p class="staff-stat-value"><?= number_format((int) $card['value'], 0, ',', '.') ?></p>
            <p class="staff-stat-note"><?= esc($card['note']) ?></p>
        </section>
    <?php endforeach; ?>
</div>

<p class="admin-summary-note">
    Ringkasan seluruh berkas tersimpan, termasuk milik staff nonaktif dan admin yang memiliki profil staff.
    Angka tidak mengikuti filter.
</p>

<section class="staff-panel admin-list-panel">
    <div class="staff-panel-heading">
        <div>
            <h2>Daftar Dokumen Staff</h2>
            <p>Informasi berkas beserta pemiliknya, sesuai pencarian dan filter.</p>
        </div>

        <span class="staff-count">
            <?= (int) $pager->getTotal() ?> berkas
        </span>
    </div>

    <form
        action="<?= site_url('admin/documents') ?>"
        method="get"
        class="document-filter admin-document-filter"
    >
        <div class="field">
            <label for="all-document-search">Cari Dokumen / Staff</label>
            <input
                type="search"
                id="all-document-search"
                name="q"
                maxlength="100"
                placeholder="Judul, nama file, atau nama staff..."
                value="<?= esc($search, 'attr') ?>"
            >
        </div>

        <div class="field">
            <label for="all-document-category">Kategori Dokumen</label>
            <select id="all-document-category" name="category">
                <option value="">Semua kategori</option>

                <?php foreach ($types as $value => $label): ?>
                    <option
                        value="<?= esc($value, 'attr') ?>"
                        <?= $category === $value ? 'selected' : '' ?>
                    >
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="all-staff-category">Kategori Staff</label>
            <select id="all-staff-category" name="staff_category">
                <option value="">Semua staff</option>

                <?php foreach ($staffCategories as $value => $label): ?>
                    <option
                        value="<?= esc($value, 'attr') ?>"
                        <?= $staffCategory === $value ? 'selected' : '' ?>
                    >
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="all-document-year">Tahun Dokumen</label>
            <select id="all-document-year" name="year">
                <option value="">Semua tahun</option>

                <?php foreach ($years as $availableYear): ?>
                    <option
                        value="<?= esc($availableYear, 'attr') ?>"
                        <?= $year === $availableYear ? 'selected' : '' ?>
                    >
                        <?= esc($availableYear) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="document-filter-actions">
            <button type="submit" class="btn-primary">
                Terapkan
            </button>

            <a
                href="<?= site_url('admin/documents') ?>"
                class="button-link button-secondary"
            >
                Reset
            </a>
        </div>
    </form>

    <p class="document-page-size">10 dokumen per halaman</p>

    <?php if (empty($documents)): ?>
        <div class="staff-empty">
            <p>
                <?= $hasFilter
                    ? 'Tidak ada dokumen yang sesuai. Coba ubah atau reset filter.'
                    : 'Belum ada dokumen yang diunggah oleh staff.' ?>
            </p>

            <?php if ($hasFilter): ?>
                <a
                    href="<?= site_url('admin/documents') ?>"
                    class="button-link button-secondary"
                >
                    Reset Filter
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div
            class="staff-table-wrap"
            role="region"
            aria-label="Dokumen seluruh staff"
            tabindex="0"
        >
            <table class="staff-document-table admin-document-table">
                <thead>
                    <tr>
                        <th scope="col">Dokumen</th>
                        <th scope="col">Pemilik</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Tahun</th>
                        <th scope="col">Ukuran</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($documents as $document): ?>
                        <?php
                        $staffId = (int) $document['user_id'];
                        $documentId = (int) $document['id'];

                        $base = 'admin/staff/' . $staffId
                            . '/documents/' . $documentId;
                        ?>

                        <tr>
                            <td>
                                <strong>
                                    <?= esc($document['title']) ?>
                                </strong>

                                <small>
                                    <?= esc(
                                        $document['issuer']
                                            ?: 'Penerbit belum dicantumkan'
                                    ) ?>
                                </small>

                                <small>
                                    <?= esc($document['original_name']) ?>
                                </small>
                            </td>

                            <td>
                                <a
                                    class="document-owner-link"
                                    href="<?= site_url('admin/staff/' . $staffId) ?>"
                                >
                                    <?= esc($document['staff_name']) ?>
                                </a>

                                <small>
                                    <?= esc(
                                        $staffCategories[
                                            $document['staff_category'] ?? ''
                                        ] ?? 'Kategori belum diisi'
                                    ) ?>
                                </small>
                            </td>

                            <td>
                                <?= esc(
                                    $types[$document['document_type']]
                                        ?? 'Dokumen'
                                ) ?>
                            </td>

                            <td>
                                <?= esc($document['issue_year'] ?? '—') ?>
                            </td>

                            <td>
                                <?= number_format(
                                    $document['file_size'] / 1024 / 1024,
                                    2,
                                    ',',
                                    '.'
                                ) ?> MB
                            </td>

                            <td>
                                <div class="document-actions">
                                    <a
                                        class="button-link"
                                        href="<?= site_url($base . '/preview')
                                            . '?source=all-documents' ?>"
                                        aria-label="Lihat <?= esc(
                                            $document['title'],
                                            'attr'
                                        ) ?>"
                                    >
                                        Lihat
                                    </a>

                                    <a
                                        class="button-link button-secondary"
                                        href="<?= site_url($base . '/download') ?>"
                                        aria-label="Unduh <?= esc(
                                            $document['title'],
                                            'attr'
                                        ) ?>"
                                    >
                                        Unduh
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="document-list-footer">
        <p>
            Menampilkan
            <strong><?= count($documents) ?></strong>
            dari
            <strong><?= (int) $pager->getTotal() ?></strong>
            dokumen
        </p>

        <?php if ($pager->getPageCount() > 1): ?>
            <div class="document-pagination">
                <?= $pager
                    ->only(['q', 'category', 'staff_category', 'year'])
                    ->links() ?>
            </div>
        <?php endif; ?>
    </div>
</section>

</div>

<?= $this->endSection() ?>