<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-heading">
    <div>
        <p class="eyebrow">MANAJEMEN STAFF</p>
        <h1>Pengajuan Cuti &amp; Izin</h1>
        <p>
            Tinjau permohonan staf dan tetapkan keputusan
            berdasarkan pemeriksaan admin/HRD.
        </p>
    </div>
</div>

<div class="leave-admin-summary">
    <div class="leave-admin-stat">
        <span>Total Pengajuan</span>
        <strong><?= (int) $summary['total'] ?></strong>
    </div>

    <div class="leave-admin-stat is-pending">
        <span>Menunggu</span>
        <strong><?= (int) $summary['pending'] ?></strong>
    </div>

    <div class="leave-admin-stat is-approved">
        <span>Disetujui</span>
        <strong><?= (int) $summary['approved'] ?></strong>
    </div>

    <div class="leave-admin-stat is-rejected">
        <span>Ditolak</span>
        <strong><?= (int) $summary['rejected'] ?></strong>
    </div>
</div>

<section class="staff-panel">
    <div class="staff-panel-heading">
        <div>
            <h2>Daftar permohonan</h2>
            <p>Cari nama staf atau filter berdasarkan status.</p>
        </div>
    </div>

    <form
        action="<?= site_url('admin/leave-requests') ?>"
        method="get"
        class="leave-admin-filters"
    >
        <div class="field">
            <label for="leave-search">Nama atau email staf</label>
            <input
                id="leave-search"
                type="search"
                name="q"
                value="<?= esc($search, 'attr') ?>"
                placeholder="Cari staf..."
            >
        </div>

        <div class="field">
            <label for="leave-status-filter">Status</label>
            <select id="leave-status-filter" name="status">
                <option value="">Semua status</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>
                    Menunggu Konfirmasi
                </option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>
                    Disetujui
                </option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>
                    Ditolak
                </option>
            </select>
        </div>

        <div class="leave-admin-filter-actions">
            <button type="submit" class="btn-primary">Terapkan</button>
            <a
                class="button-link button-secondary"
                href="<?= site_url('admin/leave-requests') ?>"
            >
                Reset
            </a>
        </div>
    </form>

    <?php if (empty($requests)): ?>
        <div class="staff-empty">
            <h3>Belum ada pengajuan yang cocok</h3>
            <p>
                Pengajuan staf akan muncul di sini setelah
                dikirim melalui portal.
            </p>
        </div>
    <?php else: ?>
        <div class="leave-request-list">
            <?php foreach ($requests as $request): ?>
                <?php
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

                $type = $request['request_type'] ?? 'izin';
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

                $status = $request['status'] ?? 'pending';
                ?>

                <article class="leave-request-card">
                    <div class="leave-request-card-header">
                        <div>
                            <span class="leave-request-type">
                                <?= esc($type === 'cuti' ? 'Cuti' : 'Izin') ?>
                                <?php if (! empty($request['staff_category'])): ?>
                                    · <?= esc(
                                        $request['staff_category'] === 'academic'
                                            ? 'Academic'
                                            : ($request['staff_category'] === 'academic_support'
                                                ? 'Academic Support'
                                                : $request['staff_category'])
                                    ) ?>
                                <?php endif; ?>
                            </span>

                            <h3>
                                <?= esc(
                                    $categoryLabel !== ''
                                        ? $categoryLabel
                                        : ($type === 'cuti'
                                            ? 'Pengajuan Cuti'
                                            : 'Permohonan Izin')
                                ) ?>
                            </h3>

                            <p class="leave-admin-staff-name">
                                <?= esc($request['full_name'] ?? 'Nama tidak tersedia') ?>
                            </p>

                            <p class="leave-admin-email">
                                <?= esc($request['email'] ?? '') ?>
                            </p>
                        </div>

                        <span class="leave-status <?= esc(
                            $statusClasses[$status] ?? 'leave-status-pending',
                            'attr'
                        ) ?>">
                            <?= esc($statusLabels[$status] ?? 'Status tidak dikenal') ?>
                        </span>
                    </div>

                    <div class="leave-request-meta">
                        <div>
                            <span>Periode</span>
                            <strong><?= esc($start) ?> – <?= esc($end) ?></strong>
                        </div>
                        <div>
                            <span>Diajukan pada</span>
                            <strong><?= esc($created) ?></strong>
                        </div>
                    </div>

                    <div class="leave-request-detail">
                        <h4>Alasan / keterangan staf</h4>
                        <p><?= nl2br(esc($request['reason'] ?? '')) ?></p>
                    </div>

                    <?php if ($status === 'approved'): ?>
                        <div class="leave-review leave-review-approved">
                            <h4>Catatan persetujuan</h4>
                            <p>
                                <?= ! empty($request['admin_note'])
                                    ? nl2br(esc($request['admin_note']))
                                    : 'Disetujui tanpa catatan tambahan.' ?>
                            </p>
                        </div>
                    <?php elseif ($status === 'rejected'): ?>
                        <div class="leave-review leave-review-rejected">
                            <h4>Alasan penolakan</h4>
                            <p><?= nl2br(esc($request['rejection_reason'] ?? '')) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($status === 'pending'): ?>
                        <div class="leave-admin-decision">
                            <form
                                action="<?= site_url('admin/leave-requests/' . (int) $request['id'] . '/review') ?>"
                                method="post"
                                class="leave-decision-form"
                            >
                                <?= csrf_field() ?>

                                <h4>Setujui pengajuan</h4>

                                <div class="field">
                                    <label for="admin-note-<?= (int) $request['id'] ?>">
                                        Catatan admin (opsional)
                                    </label>
                                    <textarea
                                        id="admin-note-<?= (int) $request['id'] ?>"
                                        name="admin_note"
                                        rows="3"
                                        maxlength="2000"
                                        placeholder="Tambahkan catatan persetujuan..."
                                    ></textarea>
                                </div>

                                <input
                                    type="hidden"
                                    name="decision"
                                    value="approved"
                                >

                                <button
                                    type="submit"
                                    class="btn-primary"
                                    onclick="return confirm('Setujui pengajuan ini?')"
                                >
                                    Setujui
                                </button>
                            </form>

                            <form
                                action="<?= site_url('admin/leave-requests/' . (int) $request['id'] . '/review') ?>"
                                method="post"
                                class="leave-decision-form leave-rejection-form"
                            >
                                <?= csrf_field() ?>

                                <h4>Tolak pengajuan</h4>

                                <div class="field">
                                    <label for="rejection-<?= (int) $request['id'] ?>">
                                        Alasan penolakan (wajib)
                                    </label>
                                    <textarea
                                        id="rejection-<?= (int) $request['id'] ?>"
                                        name="rejection_reason"
                                        rows="3"
                                        minlength="5"
                                        maxlength="2000"
                                        required
                                        placeholder="Jelaskan alasan penolakan..."
                                    ></textarea>
                                </div>

                                <input
                                    type="hidden"
                                    name="decision"
                                    value="rejected"
                                >

                                <button
                                    type="submit"
                                    class="leave-reject-button"
                                    onclick="return confirm('Tolak pengajuan ini? Pastikan alasan penolakan sudah benar.')"
                                >
                                    Tolak
                                </button>
                            </form>
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
