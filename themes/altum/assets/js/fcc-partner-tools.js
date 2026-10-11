/* Enhances existing tools without changing their forms, permissions or requests. */
(() => {
    'use strict';
    const search = document.getElementById('fp-tool-search');
    if(search) {
        const library = search.closest('.fp-tool-library');
        const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('hr');
        search.addEventListener('input', () => {
            const query = normalize(search.value.trim());
            library.querySelectorAll('[data-tool-item]').forEach(item => { item.hidden = !normalize(item.textContent).includes(query); });
            library.querySelectorAll('[data-tool-group]').forEach(group => { group.hidden = !group.querySelector('[data-tool-item]:not([hidden])'); });
            library.querySelector('[data-tool-empty]').hidden = !!library.querySelector('[data-tool-item]:not([hidden])');
        });
    }
    if(!document.body.classList.contains('fp-tool-page')) return;
    const main = document.querySelector('main');
    if(!main) return;
    // The title from the existing form also labels each icon action for touch and assistive technology.
    main.querySelectorAll('button, a.btn').forEach(button => {
        if(!button.textContent.trim() && !button.getAttribute('aria-label')) {
            const title = button.getAttribute('title') || button.getAttribute('data-original-title');
            if(title && !/<[^>]*>/.test(title)) button.setAttribute('aria-label', title);
        }
    });
    // Only simple resource lists become cards. Reports and tables with grouped cells keep their table layout.
    const lists = ['pixels','projects','domains','notification-handlers','splash-pages','qr-codes'];
    if(lists.includes(document.body.dataset.fccPage)) {
        main.querySelectorAll('table.table-custom').forEach(table => {
            const headers = Array.from(table.querySelectorAll('thead th, thead td')).map(cell => cell.textContent.trim());
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            if(!headers.length || rows.some(row => Array.from(row.cells).some(cell => cell.colSpan > 1 || cell.rowSpan > 1))) return;
            table.classList.add('fp-resource-table');
            table.setAttribute('role', 'table');
            rows.forEach(row => {
                row.setAttribute('role', 'row');
                Array.from(row.cells).forEach((cell, index) => {
                    cell.setAttribute('role', 'cell');
                    if(headers[index]) cell.dataset.label = headers[index];
                    if(cell.querySelector('input[type=checkbox][name="selected[]"]')) cell.classList.add('fp-resource-select');
                });
            });
        });
    }
})();
