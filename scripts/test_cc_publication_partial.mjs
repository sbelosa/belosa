/* Custom code: FC-2026-10-14: Unconfirmed values never enter a fresh CC publication. */
import assert from 'node:assert/strict';
import {ccPublicationPayload} from './flp360_cloud_sync.mjs';
const confirmed = new Map([['111111111111',{fboId:'111111111111',personalCc:4.054,totalCc:4.054,totalActiveCc:4.054,isFourCcActive:true}]]);
const unavailable = new Map([['222222222222',{personalCc:999,totalCc:999,totalActiveCc:999}]]);
const partial = ccPublicationPayload(confirmed,'2026-10',unavailable,'2026-10-14T12:00:00.000Z');
assert.deepEqual(JSON.parse(partial.unconfirmed_ids),['222222222222']);
assert.equal(JSON.parse(partial.records).length,1);
assert.equal(JSON.parse(partial.records)[0].personal_cc,4.054);
assert.ok(!partial.records.includes('999'));
assert.match(partial.batch_key,/^[a-f0-9]{64}$/);
const complete = ccPublicationPayload(confirmed,'2026-10',new Map(),'2026-10-14T12:00:00.000Z');
assert.deepEqual(JSON.parse(complete.unconfirmed_ids),[]);
assert.notEqual(partial.batch_key,complete.batch_key);
assert.throws(()=>ccPublicationPayload(confirmed,'2026-10',confirmed),/partition/);
assert.throws(()=>ccPublicationPayload(new Map(),'2026-10',unavailable),/partition/);
console.log(JSON.stringify({status:'PASS',checks:9,network:false}));
/* /Custom code: FC-2026-10-14 */
