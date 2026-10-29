<?php if (! empty($canSwitchDashboard)): ?>
    <nav class="dashboard-switcher" aria-label="Pilihan dashboard">
        <a
            href="<?= site_url('admin/dashboard') ?>"
            class="dashboard-switcher-link <?= ($dashboardMode ?? '') === 'admin' ? 'is-active' : '' ?>"
            <?php if (($dashboardMode ?? '') === 'admin'): ?>
                aria-current="page"
            <?php endif; ?>
        >
            Dashboard Admin
        </a>

        <a
            href="<?= site_url('staff/dashboard') ?>"
            class="dashboard-switcher-link <?= ($dashboardMode ?? '') === 'staff' ? 'is-active' : '' ?>"
            <?php if (($dashboardMode ?? '') === 'staff'): ?>
                aria-current="page"
            <?php endif; ?>
        >
            Dashboard Staff
        </a>
    </nav>
<?php endif; ?>