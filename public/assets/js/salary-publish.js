(() => {
    'use strict';

    document.querySelectorAll('.salary-publish-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            const staffName = form.dataset.staffName || 'staff ini';

            const confirmed = window.confirm(
                `Terbitkan slip gaji untuk ${staffName}?\n\n`
                + 'Pastikan rincian dan nominal sudah benar. '
                + 'Setelah diterbitkan, slip dapat dilihat staff '
                + 'dan tidak dapat diedit melalui fitur draft.'
            );

            if (!confirmed) {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';

            const button = form.querySelector('button[type="submit"]');

            if (button) {
                button.disabled = true;
                button.textContent = 'Menerbitkan...';
            }
        });
    });
})();