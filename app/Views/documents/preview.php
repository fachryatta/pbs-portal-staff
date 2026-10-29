<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$documentId = (int) $document['id'];
$isPdf = $document['mime_type'] === 'application/pdf';

$fileUrl = $fileUrl
    ?? site_url('documents/' . $documentId . '/view');

$downloadUrl = $downloadUrl
    ?? site_url('documents/' . $documentId . '/download');

$backUrl = $backUrl ?? site_url('documents');
$canEdit = $canEdit ?? true;
?>

<div class="page-heading">
    <div class="document-preview-heading">
        <p class="eyebrow">PRATINJAU DOKUMEN</p>
        <h1><?= esc($document['title']) ?></h1>

        <p>
            <?= esc($types[$document['document_type']] ?? 'Dokumen') ?>
            ·
            <?= esc($document['issuer'] ?: 'Penerbit belum dicantumkan') ?>
        </p>
    </div>

   <div class="preview-heading-actions">
    <?php if ($canEdit): ?>
        <a
            href="<?= site_url('documents/' . $documentId . '/edit') ?>"
            class="button-link secondary"
        >
            Edit Informasi
        </a>
    <?php endif; ?>

    <a
        href="<?= esc($backUrl, 'attr') ?>"
        class="button-link secondary"
    >
        Kembali
    </a>

    <a
        href="<?= esc($downloadUrl, 'attr') ?>"
        class="button-link"
    >
        Unduh
    </a>
</div>
    </div>


<section class="staff-panel">
    <div class="document-preview-info">
        <span><?= esc($document['original_name']) ?></span>

        <span>
            <?= number_format(
                $document['file_size'] / 1024 / 1024,
                2,
                ',',
                '.'
            ) ?> MB
        </span>
    </div>

    <?php if ($isPdf): ?>
        <iframe
            class="document-pdf"
            src="<?= $fileUrl ?>"
            title="Pratinjau <?= esc($document['title'], 'attr') ?>"
        ></iframe>

        <p class="document-preview-help">
            Jika PDF tidak tampil,
            <a href="<?= $fileUrl ?>" target="_blank" rel="noopener noreferrer">
                buka di tab baru
            </a>
            atau
            <a href="<?= $downloadUrl ?>">unduh file</a>.
        </p>
    <?php else: ?>
        <div class="document-image-container">
            <img
                class="document-image"
                src="<?= $fileUrl ?>"
                alt="<?= esc($document['title'], 'attr') ?>"
            >
        </div>

        <p class="document-preview-help">
            <a href="<?= $fileUrl ?>" target="_blank" rel="noopener noreferrer">
                Buka gambar di tab baru untuk memperbesar
            </a>
        </p>
    <?php endif; ?>
</section>

<?= $this->endSection() ?>