<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="dashboard-heading">
    <p class="eyebrow">PERMATA BANGSA SCHOOL</p>

    <h1><?= esc($heading) ?></h1>

    <p><?= esc($description) ?></p>
</div>

<div class="dashboard-grid">
    <section class="form-card">
        <h2>Halo, <?= esc(session()->get('full_name')) ?></h2>

        <p class="form-description">
            Selamat datang di Portal Staff Permata Bangsa School.
        </p>

        <dl class="account-details">
            <dt>Email</dt>
            <dd><?= esc(session()->get('email')) ?></dd>

            <dt>Hak akses</dt>
            <dd>
                <?= session()->get('access_role') === 'admin'
                    ? 'Admin / HRD'
                    : 'Staff' ?>
            </dd>

            <dt>Status email</dt>
            <dd><span class="status-badge">Terverifikasi</span></dd>
        </dl>
    </section>

    <section class="form-card">
        <p class="eyebrow">RINGKASAN</p>

        <div class="metric-value"><?= (int) $metricValue ?></div>

        <p class="form-description"><?= esc($metricLabel) ?></p>
    </section>
</div>

<?= $this->endSection() ?>