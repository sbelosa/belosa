(function (root, factory) {
    'use strict';
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else api.start(root);
})(typeof window !== 'undefined' ? window : null, function () {
    'use strict';
    const plain = value => value !== null && typeof value === 'object' && !Array.isArray(value);
    const sensitiveRoot = /^(?:admin(?:-|$)|login$|logout$|register$|.*activation.*|.*password.*|pay(?:-|$)|checkout$|api$|ajax$|cron$|webhook$|oauth$)/i;
    const sensitiveSegment = /^(?:logout|delete|remove|export|download|ajax|callback|google-callback|impersonate|delegate|feed)$/i;
    const sensitiveQuery = /^(?:token|access_token|refresh_token|code|state|password|csrf(?:_token)?|redirect|return_url|admin_impersonate_user|team|workspace|delete|remove|action|export|download|asset|key|email|phone)$/i;

    function safeUrl(value, config) {
        if (typeof value !== 'string' || value.length > 2048 || /[\\\x00-\x20]/.test(value) || !plain(config)) return null;
        try {
            const base = new URL(config.base);
            const target = new URL(value, base);
            if (!/^https?:$/.test(target.protocol) || target.origin !== base.origin || target.username || target.password) return null;
            const basePath = base.pathname.endsWith('/') ? base.pathname : base.pathname + '/';
            if (!target.pathname.startsWith(basePath)) return null;
            const parts = target.pathname.slice(basePath.length).split('/').filter(Boolean).map(part => decodeURIComponent(part));
            if (parts.some(part => /[\\/\x00-\x20]/.test(part) || part === '.' || part === '..')) return null;
            if ((config.languages || []).includes(parts[0])) parts.shift();
            const first = parts[0] || '';
            const confirmedCard = (config.trustedPaths || []).includes(target.pathname);
            if (sensitiveRoot.test(first) || (!(config.roots || []).includes(first) && !confirmedCard)) return null;
            if (parts.slice(1).some(part => sensitiveSegment.test(part))) return null;
            if (first === 'partner' && ['admin', 'preview', 'curriculum'].includes(parts[1])) return null;
            if ([...target.searchParams.keys()].some(key => sensitiveQuery.test(key))) return null;
            return target.href;
        } catch (_) { return null; }
    }

    function id() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
        return Date.now().toString(36) + Math.random().toString(36).slice(2);
    }
    function validEntry(entry, url, config) {
        return plain(entry) && typeof entry.url === 'string' && typeof entry.id === 'string' && entry.id.length > 0 &&
            entry.scope === config.scope && entry.method === 'GET' && entry.url === url && safeUrl(entry.url, config) === url;
    }
    function withoutHash(value) {
        try { const url = new URL(value); url.hash = ''; return url.href; } catch (_) { return null; }
    }
    function nextEntry(config, currentURL, historyState, pending, referrer, navigationType, now) {
        const url = safeUrl(currentURL, config);
        const fresh = {id: id(), url: currentURL, scope: config.scope, method: config.method, previous: null};
        if (!url || config.method !== 'GET' || config.historyAllowed !== true) return fresh;
        const stored = plain(historyState) ? historyState.fccMobileNav : null;
        if (['reload', 'back_forward'].includes(navigationType) && validEntry(stored, url, config)) return stored;
        if (navigationType !== 'navigate' || !plain(pending) || !Number.isFinite(pending.at) || now < pending.at || now - pending.at > 30000) return fresh;
        const source = pending.source;
        if (safeUrl(pending.target, config) !== url || !plain(source) || !validEntry(source, source.url, config) || source.url === url) return fresh;
        if (!referrer || withoutHash(referrer) !== withoutHash(source.url)) return fresh;
        fresh.previous = {id: source.id, url: source.url};
        return fresh;
    }
    function canGoBack(entry, historyState, currentURL, config) {
        if (config.historyAllowed !== true || config.method !== 'GET' || !plain(historyState) || !validEntry(entry, currentURL, config)) return false;
        const stored = historyState.fccMobileNav;
        if (!validEntry(stored, currentURL, config) || stored.id !== entry.id) return false;
        const previous = entry.previous;
        return plain(previous) && typeof previous.url === 'string' && typeof previous.id === 'string' && previous.id.length > 0 &&
            previous.url !== currentURL && safeUrl(previous.url, config) === previous.url;
    }

    function start(win) {
        if (!win || !win.document) return;
        const doc = win.document;
        const nav = doc.querySelector('[data-fcc-mobile-nav]');
        if (!nav) return;
        let config;
        try { config = JSON.parse(nav.dataset.fccNavConfig); } catch (_) { return; }
        const button = nav.querySelector('[data-fcc-mobile-back]');
        const destination = nav.querySelector('[data-fcc-back-destination]');
        const pendingKey = 'fcc.mobile-navigation.pending:' + config.scope;
        const searchKey = 'fcc.mobile-navigation.search:' + config.scope + ':' + win.location.pathname + win.location.search;
        const storage = {
            get(key) { try { return JSON.parse(win.sessionStorage.getItem(key)); } catch (_) { return null; } },
            set(key, value) { try { win.sessionStorage.setItem(key, JSON.stringify(value)); } catch (_) {} },
            remove(key) { try { win.sessionStorage.removeItem(key); } catch (_) {} }
        };
        const navigation = win.performance.getEntriesByType('navigation')[0];
        let entry = nextEntry(config, win.location.href, win.history.state, storage.get(pendingKey), doc.referrer, navigation ? navigation.type : 'navigate', Date.now());
        storage.remove(pendingKey);
        function mark() {
            // Keep other page state intact; unknown state belongs to its own controller.
            if (win.history.state === null || plain(win.history.state)) {
                try { win.history.replaceState(Object.assign({}, win.history.state, {fccMobileNav: entry}), '', win.location.href); } catch (_) {}
            }
        }
        function refresh() {
            const verified = canGoBack(entry, win.history.state, win.location.href, config);
            if (destination) destination.textContent = verified ? config.copy.previous : config.parentLabel;
            nav.dataset.fccBackMode = verified ? 'history' : 'parent';
        }
        mark(); refresh();
        doc.documentElement.classList.add('fcc-mobile-navigation-ready');

        if (button) button.addEventListener('click', function (event) {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (canGoBack(entry, win.history.state, win.location.href, config)) {
                event.preventDefault(); storage.remove(pendingKey); win.history.back();
            }
        });
        doc.addEventListener('click', function (event) {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            const anchor = event.target.closest && event.target.closest('a[href]');
            if (!anchor || anchor.hasAttribute('download') || (anchor.target && anchor.target !== '_self') || anchor === button) return;
            const target = safeUrl(anchor.href, config);
            if (!target || config.method !== 'GET' || !config.historyAllowed || !validEntry(entry, win.location.href, config) || target === entry.url) return;
            // Let existing click handlers cancel modal, editor and AJAX actions first.
            Promise.resolve().then(function () {
                if (!event.defaultPrevented) storage.set(pendingKey, {source: entry, target: target, at: Date.now()});
            });
        }, true);
        doc.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form || form.tagName !== 'FORM' || form.method.toUpperCase() !== 'GET' || (form.target && form.target !== '_self')) return;
            if (config.method !== 'GET' || !config.historyAllowed || !validEntry(entry, win.location.href, config)) return;
            try {
                const target = new URL(form.action || win.location.href);
                const values = new FormData(form);
                if (event.submitter && event.submitter.name) values.append(event.submitter.name, event.submitter.value);
                if ([...values.values()].some(value => typeof value !== 'string')) return;
                target.search = new URLSearchParams(values).toString();
                const safe = safeUrl(target.href, config);
                if (!safe || safe === entry.url) return;
                Promise.resolve().then(function () {
                    if (!event.defaultPrevented) storage.set(pendingKey, {source: entry, target: safe, at: Date.now()});
                });
            } catch (_) {}
        }, true);
        win.addEventListener('popstate', function () {
            const stored = plain(win.history.state) ? win.history.state.fccMobileNav : null;
            if (validEntry(stored, win.location.href, config)) entry = stored;
            refresh();
        });
        win.addEventListener('hashchange', function () {
            const pending = storage.get(pendingKey);
            if (plain(pending) && pending.target === win.location.href) {
                entry = nextEntry(config, win.location.href, null, pending, withoutHash(pending.source.url), 'navigate', Date.now());
                storage.remove(pendingKey); mark();
            }
            refresh();
        });
        win.addEventListener('fcc:editor-view-changed', function () {
            const stored = plain(win.history.state) ? win.history.state.fccMobileNav : null;
            if (validEntry(stored, win.location.href, config)) entry = stored;
            refresh();
        });
        // Restore only explicitly named list filters, never personal form drafts.
        const searches = ['fpe-product-search', 'fp-tool-search'].map(name => doc.getElementById(name)).filter(Boolean);
        function restoreSearch() {
            searches.forEach(function (search) {
                const term = storage.get(searchKey + ':' + search.id);
                if (typeof term === 'string' && term.length <= 160) {
                    search.value = term; search.dispatchEvent(new Event('input', {bubbles: true}));
                }
            });
        }
        searches.forEach(function (search) {
            search.addEventListener('input', function () { storage.set(searchKey + ':' + search.id, search.value.slice(0, 160)); });
        });
        restoreSearch();
        win.addEventListener('pageshow', function () { refresh(); restoreSearch(); });
        const contactForm = doc.querySelector('[data-contact-edit]');
        if (contactForm) {
            // Keep the native browser protection for an edited, unsaved contact.
            // Values remain in memory only and are never placed in navigation storage.
            const fingerprint = function () {
                return JSON.stringify([...contactForm.elements].filter(field =>
                    field.name && !['hidden', 'submit', 'button', 'file'].includes(field.type)
                ).map(field => [field.name, field.value, field.checked]));
            };
            const initial = fingerprint();
            let submitting = false;
            contactForm.addEventListener('submit', function (event) {
                Promise.resolve().then(function () { submitting = !event.defaultPrevented; });
            });
            win.addEventListener('beforeunload', function (event) {
                if (!submitting && fingerprint() !== initial) {
                    event.preventDefault(); event.returnValue = '';
                }
            });
        }
    }
    return {safeUrl: safeUrl, nextEntry: nextEntry, canGoBack: canGoBack, start: start};
});
