
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-heading">
    <div>
        <p class="eyebrow">LAYANAN STAFF</p>
        <h1>Riwayat Cuti &amp; Izin</h1>
        <p>
            Pantau status pengajuan dan baca tanggapan
            admin/HRD melalui halaman ini.
        </p>
    </div>

    <a
        class="button-link"
        href="<?= site_url('staff/leave-requests') ?>"
    >
        + Ajukan Cuti / Izin
    </a>
</div>

<section class="staff-panel">
    <div class="staff-panel-heading">
        <div>
            <h2>Daftar pengajuan saya</h2>
            <p>
                Pengajuan terbaru ditampilkan terlebih dahulu.
            </p>
        </div>

        <span class="staff-count">
            <?= (int) $pager->getTotal() ?> pengajuan
        </span>
    </div>

    <?php if (empty($requests)): ?>
        <div class="staff-empty">
            <h3>Belum ada pengajuan</h3>
            <p>
                Pengajuan cuti atau izin yang kamu kirim
                akan muncul di halaman ini.
            </p>

            <a
                class="button-link"
                href="<?= site_url('staff/leave-requests') ?>"
            >
                Buat pengajuan pertama
            </a>
        </div>
    <?php else: ?>
        <div class="leave-request-list">
            <?php foreach ($requests as $request): ?>
                <?php
                $status = $request['status'] ?? 'pending';

                $statusLabels = [
                    'pending' => 'Menunggu Konfirmasi',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                ];

                $statusClasses = [
                    'pending' => 'leave-status-pending',
                    'approved' => 'leave-status-approved',
                    'rejected' => 'leave-status-rejected',
                ];

                $type = $request['request_type'] ?? '';

                $typeLabel = $type === 'cuti'
                    ? 'Cuti'
                    : 'Izin';

                $category = $request['leave_category'] ?? '';

                $categoryLabel = $categories[$category] ?? '';

                $start = ! empty($request['start_date'])
                    ? date('d/m/Y', strtotime($request['start_date']))
                    : '-';

                $end = ! empty($request['end_date'])
                    ? date('d/m/Y', strtotime($request['end_date']))
                    : '-';

                $created = ! empty($request['created_at'])
                    ? date('d/m/Y H:i', strtotime($request['created_at']))
                    : '-';
                ?>

                <article class="leave-request-card">
                    <div class="leave-request-card-header">
                        <div>
                            <span class="leave-request-type">
                                <?= esc($typeLabel) ?>
                            </span>

                            <h3>
                                <?= esc(
                                    $categoryLabel !== ''
                                        ? $categoryLabel
                                        : ($type === 'izin'
                                            ? 'Permohonan Izin'
                                            : 'Pengajuan Cuti')
                                ) ?>
                            </h3>
                        </div>

                        <span class="leave-status <?= esc(
                            $statusClasses[$status]
                                ?? 'leave-status-pending',
                            'attr'
                        ) ?>">
                            <?= esc(
                                $statusLabels[$status]
                                    ?? 'Status Tidak Dikenal'
                            ) ?>
                        </span>
                    </div>

                    <div class="leave-request-meta">
                        <div>
                            <span>Periode pengajuan</span>
                            <strong>
                                <?= esc($start) ?> – <?= esc($end) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Dikirim pada</span>
                            <strong><?= esc($created) ?></strong>
                        </div>
                    </div>

                    <div class="leave-request-detail">
                        <h4>Alasan / keterangan</h4>
                        <p><?= nl2br(esc($request['reason'] ?? '')) ?></p>
                    </div>

                    <?php if ($status === 'approved'): ?>
                        <div class="leave-review leave-review-approved">
                            <h4>Catatan admin / HRD</h4>
                            <p>
                                <?= ! empty($request['admin_note'])
                                    ? nl2br(esc($request['admin_note']))
                                    : 'Pengajuan disetujui tanpa catatan tambahan.' ?>
                            </p>
                        </div>
                    <?php elseif ($status === 'rejected'): ?>
                        <div class="leave-review leave-review-rejected">
                            <h4>Alasan penolakan</h4>
                            <p>
                                <?= ! empty($request['rejection_reason'])
                                    ? nl2br(esc($request['rejection_reason']))
                                    : 'Alasan penolakan belum tersedia.' ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="leave-review leave-review-pending">
                            Pengajuan sedang ditinjau admin/HRD.
                            Hasil konfirmasi akan ditampilkan di sini.
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="leave-request-pagination">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</section>

<?= $this->endSection() ?>
