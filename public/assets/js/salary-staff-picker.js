(() => {
    const picker = document.getElementById('salary-staff-picker');

    if (!picker) {
        return;
    }

    const search = document.getElementById('salary-staff-search');
    const selectAll = document.getElementById('salary-select-all');
    const clearSelection = document.getElementById('salary-clear-selection');
    const countLabel = document.getElementById('salary-selection-count');
    const emptyMessage = document.getElementById('salary-no-staff-results');

    const rows = Array.from(
        picker.querySelectorAll('.salary-staff-choice')
    );

    const checkboxes = rows.map((row) =>
        row.querySelector('input[type="checkbox"]')
    );

    const normalize = (text) =>
        text.toLocaleLowerCase('id-ID').trim();

    function updateCount() {
        const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
        const visible = rows.filter((row) => !row.hidden).length;

        countLabel.textContent =
            `${selected} dari ${checkboxes.length} staff dipilih`
            + ` · ${visible} ditampilkan`;

        emptyMessage.hidden = visible > 0;
    }

    search.addEventListener('input', () => {
        const keyword = normalize(search.value);

        rows.forEach((row) => {
            row.hidden = !normalize(row.dataset.search).includes(keyword);
        });

        updateCount();
    });

    selectAll.addEventListener('click', () => {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = true;
        });

        updateCount();
    });

    clearSelection.addEventListener('click', () => {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });

        updateCount();
    });

    picker.addEventListener('change', updateCount);

    updateCount();
})();