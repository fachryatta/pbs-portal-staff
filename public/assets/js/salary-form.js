(() => {
    const form = document.getElementById('salary-form');
    const container = document.getElementById('salary-items');
    const template = document.getElementById('salary-item-template');
    const message = document.getElementById('salary-component-message');

    if (!form || !container || !template || !message) {
        return;
    }

    let nextIndex = container.querySelectorAll('.salary-item-row').length;

    form.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-add-type]');
        const removeButton = event.target.closest('[data-remove-item]');

        if (addButton) {
            const count = container.querySelectorAll('.salary-item-row').length;

            if (count >= 20) {
                message.textContent = 'Maksimal 20 baris komponen tambahan.';
                return;
            }

            const type = addButton.dataset.addType;

            if (!['earning', 'deduction'].includes(type)) {
                return;
            }

            const fragment = template.content.cloneNode(true);

            fragment.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace('__INDEX__', String(nextIndex));
            });

            fragment.querySelector('[data-item-type]').value = type;

            fragment.querySelector('[data-item-label]').textContent =
                type === 'earning' ? 'Pendapatan tambahan' : 'Potongan';

            const nameInput = fragment.querySelector('input[type="text"]');

            container.appendChild(fragment);
            nextIndex += 1;

            message.textContent = '';
            nameInput.focus();
        }

        if (removeButton) {
            removeButton.closest('.salary-item-row').remove();
            message.textContent = 'Baris komponen dihapus.';
        }
    });
})();