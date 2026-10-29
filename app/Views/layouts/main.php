<?php
$isLoggedIn = (bool) session()->get('logged_in');
$isAdmin = session()->get('access_role') === 'admin';
$currentPage = $activePage ?? '';
$headerUnreadCount = 0;

if ($isLoggedIn) {
    $headerUnreadCount = (new \App\Models\NotificationModel())
        ->where('user_id', (int) session()->get('user_id'))
        ->where('read_at', null)
        ->countAllResults();
}


if ($isLoggedIn) {
    // Dashboard yang sedang aktif.
    // Admin menjadi mode default untuk akun admin.
    $dashboardMode = $isAdmin
        ? (string) session()->get('dashboard_mode') ?: 'admin'
        : 'staff';

    // Pastikan hanya dua mode yang valid.
    if (! in_array($dashboardMode, ['admin', 'staff'], true)) {
        $dashboardMode = $isAdmin ? 'admin' : 'staff';
    }

    $navigation = [
        [
            'url' => 'dashboard',
            'label' => 'Dashboard',
            'key' => 'dashboard',
        ],
    ];

    if ($isAdmin && $dashboardMode === 'admin') {
        // MENU DASHBOARD ADMIN
        $navigation[] = [
            'url' => 'admin/staff',
            'label' => 'Kelola Staff',
            'key' => 'staff-management',
        ];

        $navigation[] = [
            'url' => 'admin/accounts',
            'label' => 'Kelola Admin',
            'key' => 'admin-management',
        ];

        $navigation[] = [
            'url' => 'admin/documents',
            'label' => 'Semua Dokumen',
            'key' => 'all-documents',
        ];

        $navigation[] = [
            'url' => 'admin/leave-requests',
            'label' => 'Cuti & Izin',
            'key' => 'leave-management',
        ];

        $navigation[] = [
            'url' => 'admin/salary-slips',
            'label' => 'Slip Gaji',
            'key' => 'salary-management',
        ];
    } else {
        // MENU DASHBOARD STAFF
        $navigation[] = [
            'url' => 'profile',
            'label' => 'Profile',
            'key' => 'profile',
        ];

        $navigation[] = [
            'url' => 'documents',
            'label' => 'Dokumen Saya',
            'key' => 'documents',
        ];

        $navigation[] = [
            'url' => 'staff/leave-requests',
            'label' => 'Cuti & Izin',
            'key' => 'leave-requests',
        ];

        $navigation[] = [
            'url' => 'staff/salary-slips',
            'label' => $isAdmin ? 'Slip Saya' : 'Slip Gaji',
            'key' => 'my-salary-slips',
        ];
    }
} else {
    $navigation = [
        [
            'url' => 'login',
            'label' => 'Masuk',
            'key' => 'login',
        ],
        [
            'url' => 'register',
            'label' => 'Daftar',
            'key' => 'register',
        ],
    ];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= esc($title ?? 'Portal Staff') ?> | Permata Bangsa School</title>

    <link
    rel="icon"
    type="image/gif"
    href="<?= base_url('assets/images/logo110.gif') ?>"
>

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/style.css') ?>"
    >

    <?= $this->renderSection('styles') ?>
</head>
<body>

<header class="site-header pbs-header">
    <div class="container header-inner">
        <a
            class="brand"
            href="<?= site_url($isLoggedIn ? 'dashboard' : 'login') ?>"
        >
            <img
                src="<?= base_url('assets/images/logo110.gif') ?>"
                alt="Logo Permata Bangsa School"
                width="46"
                height="46"
            >

            <span>
                <strong>Permata Bangsa School</strong>
                <small>Portal Data &amp; Dokumen Staff</small>
            </span>
        </a>

        <nav class="nav-links" aria-label="Navigasi utama">
            <?php foreach ($navigation as $item): ?>
                <a
                    href="<?= site_url($item['url']) ?>"
                    class="<?= $currentPage === $item['key'] ? 'active' : '' ?>"
                    <?php if ($currentPage === $item['key']): ?>
                        aria-current="page"
                    <?php endif; ?>
                >
                    <?= esc($item['label']) ?>
                </a>
            <?php endforeach; ?>

            <?php if ($isLoggedIn): ?>
    <a
        href="<?= site_url('notifications') ?>"
        class="notification-bell <?= $currentPage === 'notifications'
            ? 'active'
            : '' ?>"
        aria-label="<?= esc(
            'Notifikasi, ' . $headerUnreadCount . ' belum dibaca',
            'attr'
        ) ?>"
        title="Notifikasi"
        <?php if ($currentPage === 'notifications'): ?>
            aria-current="page"
        <?php endif; ?>
    >
        <svg
            viewBox="0 0 24 24"
            width="23"
            height="23"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
            <path d="M10 21h4"></path>
        </svg>

        <?php if ($headerUnreadCount > 0): ?>
            <span class="notification-bell-count" aria-hidden="true">
                <?= $headerUnreadCount > 99
                    ? '99+'
                    : (int) $headerUnreadCount ?>
            </span>
        <?php endif; ?>
    </a>

    <form action="<?= site_url('logout') ?>" method="post">
        <?= csrf_field() ?>

        <button class="nav-logout" type="submit">
            Keluar
        </button>
    </form>
<?php endif; ?>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">
        <?php
        $success = session()->getFlashdata('success');
        $errors = session()->getFlashdata('errors');
        $error = session()->getFlashdata('error');
        ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="status">
                <?= esc($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error" role="alert">
                <?= esc($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error" role="alert">
                <strong>Periksa kembali data berikut:</strong>

                <ul>
                    <?php foreach ($errors as $message): ?>
                        <li><?= esc($message) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </div>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <span>
            &copy; <?= date('Y') ?> Permata Bangsa School.
        </span>

        <span>
            Portal Internal Staff · Academic &amp; Academic Support
        </span>
    </div>
</footer>

<?= $this->renderSection('scripts') ?>

<script
    src="<?= base_url('assets/js/password-toggle.js') ?>"
    defer
></script>

</body>
</html>