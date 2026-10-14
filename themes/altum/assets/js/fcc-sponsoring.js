/* Custom code: FC-2026-10-14: progressive enhancement for the personal presentation. */
(function () {
    'use strict';
    var form = document.querySelector('[data-sp-form]');
    if (form) {
        var submitButton = form.querySelector('button[type="submit"]');
        var initialButtonHtml = submitButton ? submitButton.innerHTML : '';
        var radios = Array.from(form.querySelectorAll('input[name="channel"]'));
        var syncChannel = function () {
            var selected = form.querySelector('input[name="channel"]:checked');
            var channel = selected ? selected.value : 'email';
            form.querySelectorAll('[data-sp-contact]').forEach(function (field) {
                var active = field.getAttribute('data-sp-contact') === channel;
                field.hidden = !active;
                var input = field.querySelector('input');
                if (input) { input.required = active; input.disabled = !active; }
            });
        };
        radios.forEach(function (radio) { radio.addEventListener('change', syncChannel); });
        syncChannel();
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) return;
            var button = form.querySelector('button[type="submit"]');
            if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); button.textContent = form.getAttribute('data-sp-sending') || button.textContent; }
        });
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) { var button = form.querySelector('button[type="submit"]'); if (button) { button.disabled = false; button.removeAttribute('aria-busy'); button.innerHTML = initialButtonHtml; } }
        });
    }
    var error = document.querySelector('[data-sp-error]');
    if (error) { error.focus({ preventScroll: true }); error.scrollIntoView({ block: 'center' }); }
    var dock = document.querySelector('[data-sp-dock]');
    var hero = document.querySelector('.sp-hero h1');
    var contact = document.getElementById('razgovor');
    if (dock && hero && contact && 'IntersectionObserver' in window) {
        var heroVisible = true, contactVisible = false;
        var updateDock = function () { dock.classList.toggle('is-visible', !heroVisible && !contactVisible); dock.tabIndex = !heroVisible && !contactVisible ? 0 : -1; };
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) { if (entry.target === hero) heroVisible = entry.isIntersecting; if (entry.target === contact) contactVisible = entry.isIntersecting; });
            updateDock();
        }, { threshold: 0 });
        observer.observe(hero); observer.observe(contact); updateDock();
    }
    document.addEventListener('click', function (event) {
        var language = document.querySelector('.sp-language');
        if (language && !language.contains(event.target)) language.open = false;
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { var language = document.querySelector('.sp-language'); if (language && language.open) { language.open = false; language.querySelector('summary').focus(); } }
    });
}());
/* /Custom code: FC-2026-10-14 */
