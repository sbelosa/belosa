/* Custom code: FC-2026-10-15: refresh read-only current lists at the first stored UTC end. */
(() => {
    'use strict';
    const list = document.querySelector('[data-current-events-now]');
    if (!list) return;
    const serverNow = Date.parse(list.dataset.currentEventsNow);
    const nextEnd = Math.min(...Array.from(list.querySelectorAll('[data-event-end]'), row => Date.parse(row.dataset.eventEnd)).filter(Number.isFinite));
    if (!Number.isFinite(serverNow) || !Number.isFinite(nextEnd)) return;
    const offset = serverNow - Date.now();
    let timer;
    const check = () => {
        clearTimeout(timer);
        const remaining = nextEnd - (Date.now() + offset);
        if (remaining <= 0) { window.location.reload(); return; }
        timer = setTimeout(check, Math.min(remaining + 250, 86400000));
    };
    document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
    window.addEventListener('pageshow', check);
    check();
})();
/* /Custom code: FC-2026-10-15 */
