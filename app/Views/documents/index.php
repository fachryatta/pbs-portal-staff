<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-heading">
    <div>
        <p class="eyebrow">ARSIP PRIBADI</p>
        <h1>Dokumen Saya</h1>
        <p>Kelola ijazah dan sertifikat yang menjadi berkas sekolah.</p>
    </div>

    <a
        class="button-link"
        href="<?= site_url('documents/create') ?>"
    >
        + Upload dokumen
    </a>
</div>

<section class="staff-panel">

    <!-- Judul panel dan jumlah hasil -->
    <div class="staff-panel-heading">
        <div>
            <h2>Daftar dokumen</h2>
            <p>Berkas yang tersimpan pada akun Anda.</p>
        </div>

        <span class="staff-count">
            <?= (int) $pager->getTotal() ?> berkas
        </span>
    </div>

    <!-- Pencarian dan filter -->
    <form
        action="<?= site_url('documents') ?>"
        method="get"
        class="document-filter"
    >
        <div class="field">
            <label for="document-search">Cari Dokumen</label>
            <input
                type="search"
                id="document-search"
                name="q"
                maxlength="100"
                placeholder="Judul atau nama file..."
                value="<?= esc($search, 'attr') ?>"
            >
        </div>

        <div class="field">
            <label for="document-category">Kategori</label>

            <select id="document-category" name="category">
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
            <label for="document-year">Tahun</label>

            <select id="document-year" name="year">
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
                href="<?= site_url('documents') ?>"
                class="button-link button-secondary"
            >
                Reset
            </a>
        </div>
    </form>

    <p class="document-page-size">10 dokumen per halaman</p>

    <!-- Hasil kosong atau tabel dokumen -->
    <?php if (empty($documents)): ?>
        <div class="staff-empty">
            <?php if ($search !== '' || $category !== '' || $year !== ''): ?>
                <p>
                    Tidak ada dokumen yang sesuai.
                    Coba ubah pencarian atau reset filter.
                </p>

                <a
                    class="button-link button-secondary"
                    href="<?= site_url('documents') ?>"
                >
                    Reset Filter
                </a>
            <?php else: ?>
                <p>
                    Belum ada dokumen.
                    Mulai dengan mengunggah ijazah atau sertifikat Anda.
                </p>

                <a
                    class="button-link"
                    href="<?= site_url('documents/create') ?>"
                >
                    Upload Dokumen Pertama
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div
            class="staff-table-wrap"
            role="region"
            aria-label="Daftar dokumen saya"
            tabindex="0"
        >
            <table class="staff-document-table">
                <thead>
                    <tr>
                        <th scope="col">Dokumen</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Tahun</th>
                        <th scope="col">Ukuran</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($documents as $document): ?>
                        <?php $documentId = (int) $document['id']; ?>

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
                                        href="<?= site_url(
                                            'documents/' . $documentId . '/preview'
                                        ) ?>"
                                        aria-label="Lihat <?= esc(
                                            $document['title'],
                                            'attr'
                                        ) ?>"
                                    >
                                        Lihat
                                    </a>

                                    <a
                                        class="button-link button-secondary"
                                        href="<?= site_url(
                                            'documents/' . $documentId . '/download'
                                        ) ?>"
                                        aria-label="Unduh <?= esc(
                                            $document['title'],
                                            'attr'
                                        ) ?>"
                                    >
                                        Unduh
                                    </a>

                                    <a
                                        class="button-link button-secondary"
                                        href="<?= site_url(
                                            'documents/' . $documentId . '/edit'
                                        ) ?>"
                                        aria-label="Edit <?= esc(
                                            $document['title'],
                                            'attr'
                                        ) ?>"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        class="document-delete-form"
                                        action="<?= site_url(
                                            'documents/' . $documentId . '/delete'
                                        ) ?>"
                                        method="post"
                                        onsubmit="return confirm('Hapus dokumen ini beserta filenya secara permanen? Tindakan ini tidak dapat dibatalkan.');"
                                    >
                                        <?= csrf_field() ?>

                                        <button
                                            class="button-danger"
                                            type="submit"
                                            aria-label="Hapus <?= esc(
                                                $document['title'],
                                                'attr'
                                            ) ?>"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Ringkasan hasil dan pagination -->
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
                <?= $pager->only(['q', 'category', 'year'])->links() ?>
            </div>
        <?php endif; ?>
    </div>

</section>

<?= $this->endSection() ?>