/* Custom code: FC-2026-10-07: Offline FCC push worker behavior and route isolation checks. */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const listeners = {};
const shown = [];
const opened = [];
let claimed = 0;
let installed = 0;
const self = {
    location: {origin: 'https://fcc.invalid'},
    registration: {scope: 'https://fcc.invalid/admin/fcc-push/', showNotification: async (title, options) => shown.push({title, ...options})},
    clients: {claim: async () => claimed++, openWindow: async url => opened.push(url)},
    skipWaiting: async () => installed++,
    addEventListener: (name, callback) => { listeners[name] = callback; },
};
vm.runInNewContext(fs.readFileSync(new URL('../fcc-admin-push-sw.js', import.meta.url), 'utf8'), {self, URL});
const fire = async (name, fields = {}) => {
    const pending = [];
    listeners[name]({...fields, waitUntil: promise => pending.push(promise)});
    await Promise.all(pending);
};
await fire('install'); await fire('activate');
assert.equal(installed, 1); assert.equal(claimed, 1);
assert.equal(listeners.fetch, undefined, 'The dedicated push worker must not intercept or cache FCC page requests.');
const push = data => fire('push', {data: {json: () => data}});
await push({title: 'Testna FCC obavijest', description: 'Provjera', tag: 'fcc-registration-17', url: 'https://fcc.invalid/admin/user-view/99'});
assert.equal(shown[0].title, 'Testna FCC obavijest');
assert.equal(shown[0].tag, 'fcc-registration-17');
assert.equal(shown[0].data.url, 'https://fcc.invalid/admin/user-view/99');
let closed = false;
await fire('notificationclick', {notification: {data: shown[0].data, close: () => { closed = true; }}});
assert.equal(closed, true); assert.equal(opened[0], shown[0].data.url);
await push({url: 'https://attacker.invalid/admin/user-view/99'});
await push({url: 'https://fcc.invalid/account'});
assert.equal(shown[1].data.url, self.registration.scope);
assert.equal(shown[2].data.url, self.registration.scope);
await push(null); await push({url: 'https://['});
await fire('push', {data: {json: () => { throw Error('invalid JSON'); }}});
assert.equal(shown.length, 3, 'Malformed payloads must not display misleading notifications.');
console.log('FCC admin push worker checks passed.');
/* /Custom code: FC-2026-10-07 */
