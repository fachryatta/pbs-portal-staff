(() => {
    'use strict';

    document.querySelectorAll('.salary-delete-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const staffName = form.dataset.staffName || 'staff ini';
            const isPublished = form.dataset.slipStatus === 'published';

            const reasonInput = form.querySelector(
                'input[name="delete_reason"]'
            );

            if (!reasonInput) {
                event.preventDefault();

                window.alert(
                    'Form hapus belum lengkap. Muat ulang halaman '
                    + 'dan periksa input delete_reason.'
                );

                return;
            }

            reasonInput.value = '';

            if (isPublished) {
                const answer = window.prompt(
                    `Alasan menghapus slip gaji milik ${staffName}:\n\n`
                    + 'Alasan ini akan dikirim kepada staff melalui '
                    + 'notifikasi. Maksimal 500 karakter.'
                );

                // Admin menekan Batal.
                if (answer === null) {
                    event.preventDefault();
                    return;
                }

                const reason = answer.trim();

                if (reason === '' || Array.from(reason).length > 500) {
                    event.preventDefault();

                    window.alert(
                        'Alasan wajib diisi dan maksimal 500 karakter.'
                    );

                    return;
                }

                const confirmed = window.confirm(
                    `Hapus slip gaji terbit milik ${staffName}?\n\n`
                    + `Alasan: ${reason}\n\n`
                    + 'Slip akan hilang dari akun staff dan PDF '
                    + 'di server akan dihapus. Staff akan menerima '
                    + 'notifikasi.\n\n'
                    + 'Tindakan ini tidak dapat dibatalkan.'
                );

                if (!confirmed) {
                    event.preventDefault();
                    return;
                }

                reasonInput.value = reason;
                return;
            }

            const confirmed = window.confirm(
                `Hapus draft slip gaji milik ${staffName}?\n\n`
                + 'Rincian pendapatan dan potongannya akan dihapus. '
                + 'Tindakan ini tidak dapat dibatalkan.'
            );

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
})();