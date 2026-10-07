/* Custom code: FC-2026-10-07: Registration approval evidence boundary checks. */
import assert from 'node:assert/strict';
import {evaluateRegistration, validatePendingPayload, unwrapSingleRecord,
    collectRegistrationEvidence, summarizeDecisions, safeServerSummary} from './flp360_registration_check.mjs';
import {schemaOnly} from './probe_flp360_registration.mjs';

const now = new Date('2026-10-07T12:00:00Z');
const account = {user_id: 42, fbo_id: '360111222333', registered_at: '2026-10-07 10:00:00',
    email: 'person@example.test', name: 'Test Person', country_code: 'HR'};
const good = {source: 'flp360', checked_at: now.toISOString(), root_fbo_id: '360000760944',
    exact_fbo_id: account.fbo_id, id_exists: true, authoritative_not_found: false,
    authoritative_structure: true, in_root_structure: true, flp_name: account.name,
    flp_email: ' Person@Example.Test ', sponsor_fbo_id: '360444555666'};
const result = changes => evaluateRegistration(account, {...good, ...changes}, now).result;

assert.equal(result({}), 'approve');
assert.equal(result({id_exists: false, authoritative_not_found: true}), 'invalid_id');
assert.equal(result({id_exists: false, authoritative_not_found: false}), 'unconfirmed');
assert.equal(result({in_root_structure: false}), 'valid_id_not_team');
assert.equal(result({in_root_structure: false, authoritative_structure: false}), 'unconfirmed');
assert.equal(result({in_root_structure: null}), 'unconfirmed');
assert.equal(result({exact_fbo_id: '360999999999'}), 'unconfirmed');
assert.equal(result({source: 'cache'}), 'unconfirmed');
assert.equal(result({root_fbo_id: '360999999999'}), 'unconfirmed');
assert.equal(result({checked_at: '2026-10-07T11:29:00Z'}), 'unconfirmed');
assert.equal(result({checked_at: '2026-10-07T12:02:00Z'}), 'unconfirmed');
assert.equal(result({flp_email: null}), 'manual_review');
assert.equal(result({flp_email: 'another@example.test'}), 'manual_review');
assert.equal(result({sponsor_fbo_id: null}), 'manual_review');
assert.equal(result({sponsor_fbo_id: account.fbo_id}), 'manual_review');
assert.equal(result({sponsor_fbo_id: 'invalid'}), 'manual_review');
assert.equal(unwrapSingleRecord([{body: {distributorId: account.fbo_id}}]).distributorId, account.fbo_id);
assert.equal(unwrapSingleRecord([]), null);
assert.equal(unwrapSingleRecord([{}, {}]), null);
assert.equal(unwrapSingleRecord({data: {}, body: {}}), null);
assert.equal(unwrapSingleRecord({success: false, distributorId: account.fbo_id}), null);

const pending = {status: 'success', metric: 'registration_pending', root_fbo_id: '360000760944', accounts: [account]};
assert.equal(validatePendingPayload(pending).length, 1);
assert.throws(() => validatePendingPayload({...pending, root_fbo_id: 'another'}));
assert.throws(() => validatePendingPayload({...pending, accounts: [account, account]}));
assert.throws(() => validatePendingPayload({...pending, accounts: [{...account, registered_at: ''}]}));
const schema = JSON.stringify(schemaOnly({distributorId: account.fbo_id, email: account.email,
    name: account.name, ['360111222333']: {value: 'private'}, [account.email]: 1}));
assert.ok(!schema.includes(account.fbo_id) && !schema.includes(account.email) && !schema.includes(account.name));

function mockPage(payload, fails = false) {
    return {context: () => ({request: {get: async url => {
        if(fails) throw new Error('Private upstream error');
        const value = typeof payload === 'function' ? payload(url) : payload;
        return {ok: () => true, text: async () => JSON.stringify(value)};
    }}}), waitForTimeout: async () => {}};
}
const configuration = {operatingCountryCode: 'HUN', homeCountryCode: 'HRV',
    aesEncryptionKey: 'test-only', guestToken: 'test-only', reportBase: 'https://example.test/report'};
const exact = await collectRegistrationEvidence(mockPage({distributorId: account.fbo_id}), configuration, account, now);
assert.equal(exact.id_exists, true);
assert.equal(evaluateRegistration(account, exact, now).result, 'unconfirmed');
const missing = await collectRegistrationEvidence(mockPage(null), configuration, account, now);
assert.equal(missing.id_exists, null);
assert.equal(missing.authoritative_not_found, false);
const failure = await collectRegistrationEvidence(mockPage(null, true), configuration, account, now);
assert.equal(evaluateRegistration(account, failure, now).result, 'unconfirmed');
const rootDetail = {distributorId: '360000760944', generation: 0, sponsorDistributorId: '360987654321'};
const memberDetail = {distributorId: account.fbo_id, generation: 1, sponsorDistributorId: rootDetail.distributorId,
    firstname: 'Test', lastname: 'Person', email: account.email};
const chainPage = mockPage(url => url.includes(rootDetail.distributorId) ? rootDetail : memberDetail);
const chain = await collectRegistrationEvidence(chainPage, configuration, account, now);
assert.equal(chain.in_root_structure, true);
assert.equal(chain.flp_name, account.name);
assert.equal(chain.sponsor_fbo_id, rootDetail.distributorId);
assert.equal(evaluateRegistration(account, chain, now).result, 'approve');
const inconsistentPage = mockPage(url => url.includes(rootDetail.distributorId) ? rootDetail : {...memberDetail, generation: 3});
const inconsistent = await collectRegistrationEvidence(inconsistentPage, configuration, account, now);
assert.equal(inconsistent.authoritative_structure, false);
assert.equal(evaluateRegistration(account, inconsistent, now).result, 'unconfirmed');
const selfSponsor = await collectRegistrationEvidence(mockPage({...memberDetail,
    sponsorDistributorId: account.fbo_id}), configuration, account, now);
assert.equal(selfSponsor.authoritative_structure, false);
assert.equal(selfSponsor.in_root_structure, null);
const outsideDetail = {...memberDetail, sponsorDistributorId: rootDetail.sponsorDistributorId, generation: 0};
const ancestorDetail = {distributorId: rootDetail.sponsorDistributorId, generation: -1,
    sponsorDistributorId: '360666777888'};
const outsidePage = mockPage(url => url.includes(rootDetail.distributorId) ? rootDetail
    : url.includes(ancestorDetail.distributorId) ? ancestorDetail : outsideDetail);
const outside = await collectRegistrationEvidence(outsidePage, configuration, account, now);
assert.equal(outside.authoritative_structure, true);
assert.equal(outside.in_root_structure, false);
assert.equal(evaluateRegistration(account, outside, now).result, 'valid_id_not_team');
assert.deepEqual(summarizeDecisions([{result: 'approve'}, {result: 'unconfirmed'}]),
    {checked: 2, approve: 1, invalid_id: 0, valid_id_not_team: 0, manual_review: 0, unconfirmed: 1});
assert.deepEqual(safeServerSummary({processed: 2, user_id: 42, email: account.email,
    pending: [account], approved: 1}), {processed: 2, approved: 1});
console.log('FLP registration evidence boundary tests passed.');
/* /Custom code: FC-2026-10-07 */
