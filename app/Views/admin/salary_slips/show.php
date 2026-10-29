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

$period = ($months[(int) $slip['period_month']] ?? '—')
    . ' ' . $slip['period_year'];

$isPublished = $slip['status'] === 'published';

$rupiah = static fn ($amount) =>
    'Rp ' . number_format((int) $amount, 0, ',', '.');
?>

<div class="admin-list-page">
    <div class="page-heading admin-list-hero">
        <div>
            <p class="eyebrow">ADMINISTRASI PENGGAJIAN</p>
            <h1>Rincian Slip Gaji</h1>
            <p>Periode <?= esc($period) ?></p>
        </div>

        <div class="salary-form-actions">
    <?php if ($slip['status'] === 'draft'): ?>
        <a
            href="<?= site_url(
                'admin/salary-slips/' . (int) $slip['id'] . '/edit'
            ) ?>"
            class="button-link"
        >
            Edit Draft
        </a>
    <?php endif; ?>

    <form
        action="<?= site_url(
            'admin/salary-slips/' . (int) $slip['id'] . '/delete'
        ) ?>"
        method="post"
        class="salary-delete-form"
        data-staff-name="<?= esc($slip['staff_name'], 'attr') ?>"
        data-slip-status="<?= esc($slip['status'], 'attr') ?>"
    >
        <?= csrf_field() ?>

        <input type="hidden" name="delete_reason" value="">

        <button type="submit" class="salary-delete-button">
            Hapus Slip
        </button>
    </form>

    <a
        href="<?= site_url('admin/salary-slips') ?>"
        class="button-link button-secondary"
    >
        Kembali
    </a>
</div>
    </div>

    <section class="staff-panel">
        <div class="staff-panel-heading">
            <div>
                <h2><?= esc($slip['staff_name']) ?></h2>
                <p>
                    @<?= esc($slip['staff_username']) ?>
                    ·
                    <?= esc(
                        $categories[$slip['staff_category'] ?? '']
                            ?? 'Staff'
                    ) ?>
                </p>
            </div>

            <span
                class="admin-badge <?= $isPublished
                    ? 'badge-green'
                    : 'badge-amber' ?>"
            >
                <?= $isPublished ? 'Terbit' : 'Draft' ?>
            </span>
        </div>

        <dl class="salary-detail-meta">
            <div>
                <dt>Nomor slip</dt>
                <dd>
                    <?= esc($slip['slip_number'] ?: 'Belum diterbitkan') ?>
                </dd>
            </div>

            <div>
                <dt>Periode gaji</dt>
                <dd><?= esc($period) ?></dd>
            </div>

            <div>
                <dt>Dibuat</dt>
                <dd>
                    <?= ! empty($slip['created_at'])
                        ? esc(date('d-m-Y H:i', strtotime($slip['created_at'])))
                        : '—' ?>
                </dd>
            </div>
        </dl>

        <?php if (! $isPublished): ?>
            <p class="salary-list-description">
                Slip ini masih berstatus draft.
                Periksa penerima dan seluruh nominal sebelum diterbitkan.
                Draft belum tersedia di akun staff.
            </p>
        <?php endif; ?>
    </section>

    <div class="salary-detail-columns">
        <section class="staff-panel">
            <div class="staff-panel-heading">
                <div>
                    <h2>Pendapatan</h2>
                    <p>Gaji pokok dan pendapatan tambahan.</p>
                </div>
            </div>

            <div class="staff-table-wrap">
                <table class="staff-document-table salary-detail-table">
                    <thead>
                        <tr>
                            <th scope="col">Komponen</th>
                            <th scope="col">Nominal</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>Gaji pokok</td>
                            <td><?= esc($rupiah($slip['basic_salary'])) ?></td>
                        </tr>

                        <?php foreach ($earnings as $item): ?>
                            <tr>
                                <td><?= esc($item['label']) ?></td>
                                <td><?= esc($rupiah($item['amount'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <tfoot>
                        <tr>
                            <th scope="row">Total pendapatan</th>
                            <td><?= esc($rupiah($totalEarnings)) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <section class="staff-panel">
            <div class="staff-panel-heading">
                <div>
                    <h2>Potongan</h2>
                    <p>Rincian pengurang pendapatan.</p>
                </div>
            </div>

            <div class="staff-table-wrap">
                <table class="staff-document-table salary-detail-table">
                    <thead>
                        <tr>
                            <th scope="col">Komponen</th>
                            <th scope="col">Nominal</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($deductions)): ?>
                            <tr>
                                <td>Tidak ada potongan</td>
                                <td><?= esc($rupiah(0)) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($deductions as $item): ?>
                                <tr>
                                    <td><?= esc($item['label']) ?></td>
                                    <td><?= esc($rupiah($item['amount'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                    <tfoot>
                        <tr>
                            <th scope="row">Total potongan</th>
                            <td><?= esc($rupiah($totalDeductions)) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>

    <section class="salary-net-panel">
        <div>
            <h2>Gaji Bersih</h2>
            <p>Total pendapatan dikurangi total potongan.</p>
        </div>

        <strong><?= esc($rupiah($netSalary)) ?></strong>
    </section>

    <div class="salary-pdf-actions">
    <a
        href="<?= site_url(
            'salary-slips/' . (int) $slip['id'] . '/pdf'
        ) ?>"
        class="salary-action-button salary-action-preview"
        target="_blank"
        rel="noopener"
    >
        <?= $slip['status'] === 'draft' ? 'Preview PDF' : 'Lihat PDF' ?>
    </a>

    <?php if ($slip['status'] === 'draft'): ?>
        <form
            action="<?= site_url(
                'admin/salary-slips/' . (int) $slip['id'] . '/publish'
            ) ?>"
            method="post"
            class="salary-publish-form"
            data-staff-name="<?= esc($slip['staff_name'], 'attr') ?>"
        >
            <?= csrf_field() ?>

            <button
                type="submit"
                class="salary-action-button salary-action-publish"
            >
                Terbitkan Slip
            </button>
        </form>
    <?php else: ?>
        <a
            href="<?= site_url(
                'salary-slips/' . (int) $slip['id'] . '/download'
            ) ?>"
            class="salary-action-button salary-action-publish"
        >
            Unduh PDF
        </a>
    <?php endif; ?>
</div>

    <?php if (! empty($slip['notes'])): ?>
        <section class="staff-panel salary-detail-notes">
            <h2>Catatan</h2>
            <p><?= esc($slip['notes']) ?></p>
        </section>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script
    src="<?= base_url('assets/js/salary-delete.js') ?>"
    defer
></script>
<script
    src="<?= base_url('assets/js/salary-publish.js') ?>"
    defer
></script>
<?= $this->endSection() ?>