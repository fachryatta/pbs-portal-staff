<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="admin-list-page notification-page">
    <div class="page-heading admin-list-hero">
        <div>
            <p class="eyebrow">AKTIVITAS AKUN</p>
            <h1>Notifikasi</h1>
            <p>
                Informasi terbaru mengenai akun, dokumen, slip gaji,
serta pengajuan cuti dan izin Anda.
            </p>
        </div>

        <a
            href="<?= site_url('dashboard') ?>"
            class="button-link button-secondary"
        >
            Kembali ke Dashboard
        </a>
    </div>

    <section class="staff-panel notification-panel">
        <div class="notification-toolbar">
            <div>
                <h2>Notifikasi Saya</h2>
                <p>
                    <strong><?= (int) $unreadCount ?></strong>
                    belum dibaca ·
                    <?= (int) $pager->getTotal() ?> total notifikasi
                </p>
            </div>

            <?php if ($unreadCount > 0): ?>
                <form
                    action="<?= site_url('notifications/read-all') ?>"
                    method="post"
                >
                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="notification-button notification-button-secondary"
                    >
                        Tandai Semua Dibaca
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="staff-empty">
                <h3>Belum ada notifikasi</h3>
                <p>
                    Pemberitahuan terkait akun Anda akan muncul di sini.
                </p>
            </div>
        <?php else: ?>
            <div class="notification-list">
                <?php foreach ($notifications as $notification): ?>
                    
<?php
$type = $notification['type'] ?? '';

// Menentukan apakah notifikasi belum dibaca.
    $isUnread = empty($notification['read_at']);

$notificationCategory = match (true) {
    str_starts_with($type, 'leave_request_') => 'Cuti & Izin',
    str_starts_with($type, 'salary_slip_') => 'Slip Gaji',
    default => 'Informasi Akun',
};
?>


                    <article
                        class="notification-item <?= $isUnread
                            ? 'notification-item-unread'
                            : '' ?>"
                    >
                        <div class="notification-item-top">
                            <div>
        <span class="notification-category">
            <?= esc($notificationCategory) ?>
        </span>

        <h3><?= esc($notification['title']) ?></h3>
    </div>

                            <span
                                class="notification-status <?= $isUnread
                                    ? 'notification-status-unread'
                                    : '' ?>"
                            >
                                <?= $isUnread ? 'Belum dibaca' : 'Sudah dibaca' ?>
                            </span>
                        </div>

                        <p class="notification-message">
                            <?= esc($notification['message']) ?>
                        </p>

                        <div class="notification-item-bottom">
                            <time>
                                <?= esc(date(
                                    'd-m-Y H:i',
                                    strtotime($notification['created_at'])
                                )) ?>
                            </time>

                            <div class="notification-item-actions">
                                <?php if ($isUnread): ?>
                                    <form
                                        action="<?= site_url(
                                            'notifications/'
                                            . (int) $notification['id']
                                            . '/read'
                                        ) ?>"
                                        method="post"
                                    >
                                        <?= csrf_field() ?>

                                        <button
                                            type="submit"
                                            class="notification-button notification-button-secondary"
                                        >
                                            Tandai Dibaca
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!empty($notification['target_path'])): ?>
                                    <form
                                        action="<?= site_url(
                                            'notifications/'
                                            . (int) $notification['id']
                                            . '/open'
                                        ) ?>"
                                        method="post"
                                    >
                                        <?= csrf_field() ?>

                                        <button
                                            type="submit"
                                            class="notification-button notification-button-primary"
                                        >
                                            Buka
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="document-list-footer">
                <p>
                    <?= count($notifications) ?> dari
                    <?= (int) $pager->getTotal() ?> notifikasi
                    · 10 per halaman
                </p>

                <?php if ($pager->getPageCount() > 1): ?>
                    <div class="document-pagination">
                        <?= $pager->only([])->links() ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?= $this->endSection() ?>