/* Custom code: FC-2026-10-07: Dedicated FCC owner push worker, without page caching. */
'use strict';
self.addEventListener('install', event => event.waitUntil(self.skipWaiting()));
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
self.addEventListener('push', event => {
    if(!event.data) return;
    let notification;
    try { notification = event.data.json(); } catch { return; }
    if(!notification || typeof notification !== 'object') return;
    let target;
    try { target = new URL(notification.url || './', self.location.origin); } catch { return; }
    const adminBase = new URL('../', self.registration.scope);
    const url = target.origin === self.location.origin && target.pathname.startsWith(adminBase.pathname)
        ? target.href : new URL('./', self.registration.scope).href;
    event.waitUntil(self.registration.showNotification(notification.title || 'FCC obavijest', {
        body: notification.description || '', tag: notification.tag || 'fcc-owner-notification',
        data: {url}, icon: notification.icon || undefined,
    }));
});
self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(self.clients.openWindow(event.notification.data?.url || self.registration.scope));
});
/* /Custom code: FC-2026-10-07 */
