<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="admin-accounts-page">
    <section class="aa-heading">
        <div>
            <span class="aa-eyebrow">PENGATURAN AKSES</span>
            <h1>Kelola Admin</h1>
            <p>Kelola akun administrator portal Permata Bangsa School.</p>
        </div>

        <a
            href="<?= site_url('admin/accounts/create') ?>"
            class="button-link aa-add-button"
        >
            <span aria-hidden="true">+</span>
            Tambah Admin
        </a>
    </section>

    <section class="aa-card">
        <div class="aa-card-heading">
            <div class="aa-card-title">
                <h2>Daftar Administrator</h2>
                <span class="aa-count">
                    <?= (int) $pager->getTotal() ?> akun
                </span>
            </div>

            <p>Akun dengan akses pengelolaan data dan berkas staff.</p>
        </div>

        <div class="aa-table-wrap">
            <table class="aa-table">
                <thead>
                    <tr>
                        <th scope="col">Administrator</th>
                        <th scope="col">Email</th>
                        <th scope="col">Status Akun</th>
                        <th scope="col">Verifikasi Email</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <?php
                        $isCurrent = (int) $admin['id']
                            === (int) session()->get('user_id');

                        $isActive = (int) $admin['is_active'] === 1;
                        $isVerified = ! empty($admin['email_verified_at']);

                        $name = trim($admin['full_name']);
                        $initial = mb_strtoupper(
                            mb_substr($name !== '' ? $name : 'A', 0, 1)
                        );
                        ?>

                        <tr class="<?= $isCurrent ? 'aa-current-row' : '' ?>">
                            <td>
                                <div class="aa-person">
                                    <div class="aa-avatar" aria-hidden="true">
                                        <?= esc($initial) ?>
                                    </div>

                                    <div class="aa-person-info">
                                        <div class="aa-person-name">
                                            <strong>
                                                <?= esc($admin['full_name']) ?>
                                            </strong>

                                            <?php if ($isCurrent): ?>
                                                <span class="aa-you">Anda</span>
                                            <?php endif; ?>
                                        </div>

                                        <span class="aa-username">
                                            @<?= esc($admin['username']) ?>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="aa-email">
                                <?= esc($admin['email']) ?>
                            </td>

                            <td>
                                <span class="aa-badge <?= $isActive
                                    ? 'aa-badge-green'
                                    : 'aa-badge-gray' ?>">
                                    <span class="aa-dot" aria-hidden="true"></span>
                                    <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>

                            <td>
                                <span class="aa-badge <?= $isVerified
                                    ? 'aa-badge-blue'
                                    : 'aa-badge-amber' ?>">
                                    <?= $isVerified
                                        ? 'Terverifikasi'
                                        : 'Belum terverifikasi' ?>
                                </span>
                            </td>

                            <td>
    <?php if ($isCurrent): ?>
        <span class="aa-self-note">Akun Anda</span>
    <?php else: ?>
        <div class="aa-account-actions">
            <form
                action="<?= site_url(
                    'admin/accounts/' . (int) $admin['id'] . '/manage'
                ) ?>"
                method="post"
                onsubmit="return confirm('Ubah status akses akun admin ini?')"
            >
                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action"
                    value="<?= $isActive ? 'deactivate' : 'activate' ?>"
                >

                <button type="submit" class="aa-action-button">
                    <?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>
                </button>
            </form>

            <?php if (! empty($admin['staff_profile_id'])): ?>
                <form
                    action="<?= site_url(
                        'admin/accounts/' . (int) $admin['id'] . '/manage'
                    ) ?>"
                    method="post"
                    onsubmit="return confirm(
                        'Cabut hak admin dan kembalikan akun menjadi staff? Profil dan dokumen tetap tersimpan.'
                    )"
                >
                    <?= csrf_field() ?>

                    <input type="hidden" name="action" value="demote">

                    <button
                        type="submit"
                        class="aa-action-button aa-action-warning"
                    >
                        Turunkan Jadi Staff
                    </button>
                </form>
            <?php else: ?>
                <form
                    action="<?= site_url(
                        'admin/accounts/' . (int) $admin['id'] . '/manage'
                    ) ?>"
                    method="post"
                    onsubmit="return confirm(
                        'Hapus permanen akun admin ini? Akun tidak dapat dipulihkan melalui portal.'
                    )"
                >
                    <?= csrf_field() ?>

                    <input type="hidden" name="action" value="delete">

                    <button
                        type="submit"
                        class="aa-action-button aa-action-danger"
                    >
                        Hapus Admin
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</td>

                        </tr>
                    <?php endforeach; ?>

                    <?php if ($admins === []): ?>
                        <tr>
                            <td colspan="5">
                                <div class="aa-empty">
                                    <strong>Belum ada akun admin</strong>
                                    <p>
                                        Tambahkan administrator melalui
                                        tombol Tambah Admin.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="aa-card-footer">
            <p>
                Menampilkan
                <strong><?= count($admins) ?></strong>
                dari
                <strong><?= (int) $pager->getTotal() ?></strong>
                akun admin
            </p>

            <?php if ($pager->getPageCount() > 1): ?>
                <div class="aa-pagination">
                    <?= $pager->links() ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="aa-note">
        <span class="aa-note-icon" aria-hidden="true">i</span>
        <p>
            Admin baru wajib memverifikasi email sebelum dapat
            masuk dan mengakses portal.
        </p>
    </div>
</div>

<?= $this->endSection() ?>