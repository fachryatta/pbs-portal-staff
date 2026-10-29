(() => {
    'use strict';

    const eyeIcon = `
        <svg viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true" focusable="false">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    `;

    const eyeOffIcon = `
        <svg viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true" focusable="false">
            <path d="m3 3 18 18"/>
            <path d="M10.6 5.1A12 12 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.1 4"/>
            <path d="M6.2 6.2A19 19 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.8-1.8"/>
            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
        </svg>
    `;

    function initializePasswordToggles() {
        document.querySelectorAll('input[type="password"]').forEach((input) => {
            // Mencegah tombol dibuat dua kali.
            if (input.dataset.passwordToggle === 'ready') {
                return;
            }

            input.dataset.passwordToggle = 'ready';

            const wrapper = document.createElement('div');
            wrapper.className = 'password-input-wrap';

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'password-toggle';
            button.setAttribute('aria-pressed', 'false');

            const fieldName = input.labels?.[0]?.textContent
                .trim()
                .replace(/\s+/g, ' ') || 'password';

            if (input.id) {
                button.setAttribute('aria-controls', input.id);
            }

            function setVisible(visible) {
                input.type = visible ? 'text' : 'password';

                const action = visible ? 'Sembunyikan' : 'Tampilkan';
                const label = `${action} ${fieldName}`;

                button.innerHTML = visible ? eyeOffIcon : eyeIcon;
                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
                button.setAttribute('aria-pressed', String(visible));
            }

            input.parentNode.insertBefore(wrapper, input);
            wrapper.append(input, button);

            setVisible(false);

            button.addEventListener('click', () => {
                setVisible(input.type === 'password');
            });

            // Sembunyikan kembali saat form dikirim atau di-reset.
            input.form?.addEventListener('submit', () => {
                setVisible(false);
            });

            input.form?.addEventListener('reset', () => {
                setVisible(false);
            });

            window.addEventListener('pagehide', () => {
                setVisible(false);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initializePasswordToggles
        );
    } else {
        initializePasswordToggles();
    }
})();