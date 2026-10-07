<?php defined('ALTUMCODE') || die() ?>
<?php /* Custom code: FC-2026-10-07: Owner browser permission and private FCC push subscription. */ ?>
<div class="mb-4">
    <h1 class="h3 mb-2"><i class="fas fa-fw fa-bell mr-2"></i>FCC obavijesti</h1>
    <p class="text-muted">Primajte obavijesti o automatski odobrenim FCC suradnicima na ovom uređaju.</p>
</div>
<div class="card"><div class="card-body">
    <p>Za prvi početak uključite obavijesti i dopustite ih u pregledniku. Nakon toga obavijesti stižu i kada ova stranica nije otvorena.</p>
    <p id="fcc-push-status" role="status" aria-live="polite">Provjeravam ovaj uređaj.</p>
    <div class="d-flex flex-wrap" style="gap:12px">
        <button class="btn btn-primary" id="fcc-push-enable" type="button">Uključi na ovom uređaju</button>
        <button class="btn btn-outline-primary" id="fcc-push-test" type="button" disabled>Pošalji testnu obavijest</button>
        <button class="btn btn-light" id="fcc-push-disable" type="button" disabled>Isključi na ovom uređaju</button>
    </div>
    <p class="text-muted mt-3 mb-0">Prijavljenih uređaja: <span id="fcc-push-count"><?= (int) $data->subscriber_count ?></span></p>
</div></div>
<?php ob_start() ?>
<script>
/* Custom code: FC-2026-10-07: Use only the dedicated FCC owner service worker. */
(() => {
    'use strict';
    const publicKey = <?= json_encode($data->public_key, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const endpoint = <?= json_encode(url('admin/fcc-push/'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const workerUrl = <?= json_encode(url('fcc-admin-push-sw.js'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const workerScope = <?= json_encode(url('admin/fcc-push/'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const token = <?= json_encode(\Altum\Csrf::get(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const status = document.getElementById('fcc-push-status');
    const enable = document.getElementById('fcc-push-enable');
    const test = document.getElementById('fcc-push-test');
    const disable = document.getElementById('fcc-push-disable');
    let testRequestId = null;
    const compatible = window.isSecureContext && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && publicKey;
    if(!compatible) {
        status.textContent = 'Push obavijesti trenutačno nisu dostupne u ovom pregledniku.';
        enable.disabled = true;
        return;
    }
    const server = async (action, fields = {}) => {
        const body = new URLSearchParams({action, token, ...fields});
        const response = await fetch(endpoint, {method: 'POST', credentials: 'same-origin', body});
        const result = await response.json();
        if(!response.ok || result.status !== 'success') throw new Error('Spremanje obavijesti nije uspjelo. Osvježite stranicu i pokušajte ponovno.');
        return result.details;
    };
    const current = async () => {
        const registrations = await navigator.serviceWorker.getRegistrations();
        return registrations.find(registration => registration.scope === workerScope
            && (registration.active || registration.waiting || registration.installing)?.scriptURL === workerUrl) || null;
    };
    const activated = async registration => {
        if(registration.active?.state === 'activated') return registration;
        const worker = registration.installing || registration.waiting || registration.active;
        if(!worker) throw new Error('Uključivanje obavijesti nije uspjelo. Pokušajte ponovno.');
        await new Promise((resolve, reject) => {
            const timer = setTimeout(() => reject(new Error('Uključivanje obavijesti traje dulje. Pokušajte ponovno.')), 15000);
            const changed = () => {
                if(worker.state === 'activated') { clearTimeout(timer); worker.removeEventListener('statechange', changed); resolve(); }
                if(worker.state === 'redundant') { clearTimeout(timer); worker.removeEventListener('statechange', changed); reject(new Error('Uključivanje obavijesti nije uspjelo.')); }
            };
            worker.addEventListener('statechange', changed);
            changed();
        });
        return registration;
    };
    const update = async () => {
        const registration = await current();
        const subscription = registration ? await registration.pushManager.getSubscription() : null;
        const saved = subscription ? await server('status', {endpoint: subscription.endpoint}) : null;
        const enabled = Notification.permission === 'granted' && saved?.status === 'subscribed';
        if(saved) document.getElementById('fcc-push-count').textContent = saved.active_admin_subscribers;
        enable.disabled = enabled;
        test.disabled = !enabled;
        disable.disabled = !subscription;
        status.textContent = enabled ? 'Obavijesti su uključene na ovom uređaju.'
            : Notification.permission === 'denied' ? 'Obavijesti su blokirane. Dopustite ih u postavkama preglednika, zatim ponovno uključite ovaj uređaj.'
            : 'Ovaj uređaj nije prijavljen za FCC obavijesti.';
        return {registration, subscription};
    };
    enable.addEventListener('click', async () => {
        enable.disabled = true;
        try {
            /* Request browser consent directly from the owner's button click. */
            const permission = await Notification.requestPermission();
            if(permission !== 'granted') { await update(); return; }
            const all = await navigator.serviceWorker.getRegistrations();
            if(all.some(registration => registration.scope === workerScope
                && (registration.active || registration.waiting || registration.installing)?.scriptURL !== workerUrl)) {
                throw new Error('Postojeće obavijesti na ovoj stranici trebaju provjeru administratora.');
            }
            const registration = await activated(await navigator.serviceWorker.register(workerUrl, {scope: workerScope}));
            const padded = publicKey + '='.repeat((4 - publicKey.length % 4) % 4);
            const applicationServerKey = Uint8Array.from(atob(padded.replace(/-/g, '+').replace(/_/g, '/')), character => character.charCodeAt(0));
            const subscription = await registration.pushManager.getSubscription()
                || await registration.pushManager.subscribe({userVisibleOnly: true, applicationServerKey});
            const result = await server('subscribe', {subscription: JSON.stringify(subscription.toJSON())});
            document.getElementById('fcc-push-count').textContent = result.active_admin_subscribers;
            await update();
        } catch(error) { status.textContent = error.message || 'Uključivanje obavijesti nije uspjelo.'; enable.disabled = false; }
    });
    test.addEventListener('click', async () => {
        test.disabled = true;
        status.textContent = 'Šaljem testnu obavijest.';
        try {
            testRequestId ||= 'browser_' + crypto.randomUUID().replace(/-/g, '_');
            const result = await server('test', {request_id: testRequestId});
            status.textContent = result.test.status === 'delivered'
                ? 'Testna obavijest je poslana. Provjerite obavijesti ovog uređaja.'
                : 'Testna obavijest spremljena je za ponovni pokušaj.';
            if(result.test.status === 'delivered') testRequestId = null;
        } catch(error) { status.textContent = error.message; }
        test.disabled = false;
    });
    disable.addEventListener('click', async () => {
        disable.disabled = true;
        try {
            const {subscription} = await update();
            if(subscription) { await server('unsubscribe', {endpoint: subscription.endpoint}); await subscription.unsubscribe(); }
            await update();
            status.textContent = 'FCC obavijesti isključene su na ovom uređaju.';
        } catch(error) { status.textContent = error.message; disable.disabled = false; }
    });
    update().catch(() => { status.textContent = 'Provjera uređaja nije uspjela. Osvježite stranicu.'; });
})();
/* /Custom code: FC-2026-10-07 */
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php /* /Custom code: FC-2026-10-07 */ ?>
