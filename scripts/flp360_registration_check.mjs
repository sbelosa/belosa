/* Custom code: FC-2026-10-07: Verify only FCC registrations awaiting approval. */
import path from 'node:path';
import process from 'node:process';
import fs from 'node:fs/promises';
import {pathToFileURL} from 'node:url';
import {
    login, flpApiConfiguration, flpGetJson, reportV2Url,
    normalizeFboId, resolveFccAccountCountryCode, payloadHasExplicitError,
} from './flp360_cloud_sync.mjs';

const ROOT_FBO_ID = '360000760944';
const MAX_EVIDENCE_AGE_MS = 30 * 60 * 1000;
const SOURCE = 'flp360';
const MAX_SPONSOR_CHAIN_LENGTH = 128;

function normalizedEmail(value) {
    return String(value || '').trim().toLocaleLowerCase('en');
}

function freshEvidence(evidence, now = new Date()) {
    const at = new Date(evidence?.checked_at || '').getTime();
    const age = now.getTime() - at;
    return Number.isFinite(at) && age >= -60000 && age <= MAX_EVIDENCE_AGE_MS;
}

function evaluateRegistration(account, evidence, now = new Date()) {
    const result = (value, reason) => ({
        user_id: account.user_id,
        fbo_id: account.fbo_id,
        registered_at: account.registered_at,
        result: value,
        reason_code: reason,
        evidence,
    });
    if(evidence?.source !== SOURCE || !freshEvidence(evidence, now)
        || evidence.root_fbo_id !== ROOT_FBO_ID) {
        return result('unconfirmed', 'source_unconfirmed');
    }
    if(evidence.id_exists === false && evidence.authoritative_not_found === true) {
        return result('invalid_id', 'id_not_found');
    }
    if(evidence.id_exists !== true || evidence.exact_fbo_id !== account.fbo_id
        || !/^\d{12}$/.test(account.fbo_id)) {
        return result('unconfirmed', 'id_unconfirmed');
    }
    if(evidence.authoritative_structure === true && evidence.in_root_structure === false) {
        return result('valid_id_not_team', 'outside_root_structure');
    }
    if(evidence.authoritative_structure !== true || evidence.in_root_structure !== true) {
        return result('unconfirmed', 'structure_unconfirmed');
    }
    if(!normalizedEmail(evidence.flp_email)
        || normalizedEmail(evidence.flp_email) !== normalizedEmail(account.email)) {
        return result('manual_review', 'owner_unconfirmed');
    }
    if(!/^\d{12}$/.test(String(evidence.sponsor_fbo_id || ''))
        || evidence.sponsor_fbo_id === account.fbo_id) {
        return result('manual_review', 'sponsor_unconfirmed');
    }
    return result('approve', 'verified');
}

function validatePendingPayload(payload) {
    if(payload?.status !== 'success' || payload.metric !== 'registration_pending'
        || payload.root_fbo_id !== ROOT_FBO_ID || !Array.isArray(payload.accounts)) {
        throw new Error('FCC pending registration response is invalid.');
    }
    const ids = new Set();
    for(const account of payload.accounts) {
        if(!Number.isSafeInteger(Number(account.user_id)) || Number(account.user_id) < 1
            || ids.has(Number(account.user_id)) || typeof account.fbo_id !== 'string'
            || typeof account.registered_at !== 'string' || !account.registered_at
            || typeof account.email !== 'string') {
            throw new Error('FCC pending registration record is invalid.');
        }
        ids.add(Number(account.user_id));
    }
    return payload.accounts;
}

async function fccRequest(metric, syncUrl, syncKey, fields = {}) {
    const response = await fetch(syncUrl, {method: 'POST', headers: {
        'X-FCC-Forever-Sync-Key': syncKey,
        'Content-Type': 'application/x-www-form-urlencoded',
    }, body: new URLSearchParams({metric, ...fields}), signal: AbortSignal.timeout(120000)});
    const payload = await response.json();
    if(!response.ok || payload.status !== 'success' || payload.metric !== metric) {
        throw new Error('FCC registration endpoint failed.');
    }
    return payload;
}

function unwrapSingleRecord(payload) {
    let candidate = payload;
    for(let depth = 0; depth < 6; depth++) {
        if(payloadHasExplicitError(candidate)) return null;
        if(Array.isArray(candidate)) {
            if(candidate.length !== 1) return null;
            candidate = candidate[0];
        } else if(candidate && typeof candidate === 'object') {
            const data = Object.hasOwn(candidate, 'data');
            const body = Object.hasOwn(candidate, 'body');
            if(data && body) return null;
            if(!data && !body) return candidate;
            candidate = data ? candidate.data : candidate.body;
        } else return null;
    }
    return null;
}

async function collectRegistrationEvidence(page, configuration, account, now = new Date()) {
    const evidence = {source: SOURCE, checked_at: now.toISOString(), exact_fbo_id: null,
        root_fbo_id: ROOT_FBO_ID, id_exists: null, authoritative_not_found: false,
        authoritative_structure: false, in_root_structure: null, flp_name: null,
        flp_email: null, sponsor_fbo_id: null};
    const fboId = normalizeFboId(account.fbo_id);
    if(!fboId || fboId !== account.fbo_id) return evidence;
    const preferredCountry = resolveFccAccountCountryCode(account.country_code, configuration);
    const countryCandidates = [...new Set([preferredCountry, configuration.operatingCountryCode,
        ...(({'389': ['BGR'], '410': ['CHE'], '490': ['DEU']})[fboId.slice(0, 3)] || [])])].filter(Boolean);
    const detail = await fetchExactDistributorDetail(page, configuration, fboId, countryCandidates);
    if(!detail) return evidence;
    evidence.id_exists = true;
    evidence.exact_fbo_id = fboId;
    evidence.flp_name = [detail.firstname, detail.lastname].filter(value => typeof value === 'string').join(' ').trim() || null;
    evidence.flp_email = typeof detail.email === 'string' ? detail.email.trim() || null : null;
    evidence.sponsor_fbo_id = normalizeFboId(detail.sponsorDistributorId) || null;
    const rootDetail = fboId === ROOT_FBO_ID ? detail
        : await fetchExactDistributorDetail(page, configuration, ROOT_FBO_ID, [configuration.operatingCountryCode]);
    const rootSponsorId = rootDetail && Number.isSafeInteger(rootDetail.generation) && rootDetail.generation === 0
        ? normalizeFboId(rootDetail.sponsorDistributorId) : '';
    const chain = await verifySponsorChain(page, configuration, fboId, detail, countryCandidates, {rootSponsorId});
    const rootConfirmed = chain.reachedRoot === true && chain.generationConsistent === true;
    evidence.authoritative_structure = rootConfirmed || chain.reachedOutsideBoundary === true;
    evidence.in_root_structure = rootConfirmed ? true : chain.reachedOutsideBoundary === true ? false : null;
    evidence.chain_checked_count = chain.checkedCount;
    return evidence;
}

async function fetchExactDistributorDetail(page, configuration, fboId, countryCandidates) {
    for(const country of countryCandidates) {
        try {
            const payload = await flpGetJson(page, reportV2Url(configuration,
                `downlineLoggedInDetails/fboId/${fboId}/country/${encodeURIComponent(country)}`), configuration);
            const detail = unwrapSingleRecord(payload);
            if(detail && normalizeFboId(detail.distributorId) === fboId) return detail;
        } catch {}
    }
    return null;
}

async function verifySponsorChain(page, configuration, firstFboId, firstDetail, countryCandidates, options = {}) {
    let detail = firstDetail;
    let fboId = firstFboId;
    let previousGeneration = null;
    let generationConsistent = true;
    const visited = new Set();
    for(let hop = 0; hop < MAX_SPONSOR_CHAIN_LENGTH; hop++) {
        if(visited.has(fboId) || !detail || normalizeFboId(detail.distributorId) !== fboId) break;
        visited.add(fboId);
        const generation = detail.generation;
        if(!Number.isSafeInteger(generation) || generation < 0
            || (previousGeneration !== null && generation !== previousGeneration - 1)) {
            generationConsistent = false;
        }
        if(fboId === ROOT_FBO_ID) return {reachedRoot: true,
            reachedOutsideBoundary: false,
            generationConsistent: generationConsistent && generation === 0, checkedCount: visited.size};
        // A freshly verified direct sponsor of the root is an ancestor above
        // this team. Reaching that ancestor without first reaching the root
        // positively proves a different branch; absence alone proves nothing.
        if(options.rootSponsorId && options.rootSponsorId !== ROOT_FBO_ID
            && fboId === options.rootSponsorId) {
            return {reachedRoot: false, reachedOutsideBoundary: true, generationConsistent,
                checkedCount: visited.size};
        }
        const sponsorId = normalizeFboId(detail.sponsorDistributorId);
        if(!sponsorId || sponsorId === fboId || visited.has(sponsorId)) break;
        previousGeneration = Number.isSafeInteger(generation) ? generation : null;
        fboId = sponsorId;
        detail = await fetchExactDistributorDetail(page, configuration, fboId, countryCandidates);
    }
    return {reachedRoot: false, reachedOutsideBoundary: false, generationConsistent, checkedCount: visited.size};
}

function summarizeDecisions(decisions) {
    const summary = {checked: decisions.length, approve: 0, invalid_id: 0,
        valid_id_not_team: 0, manual_review: 0, unconfirmed: 0};
    for(const decision of decisions) summary[decision.result]++;
    return summary;
}

function safeServerSummary(summary) {
    return Object.fromEntries(['checked', 'processed', 'approved', 'rejected', 'approve', 'invalid_id',
        'valid_id_not_team', 'manual_review', 'unconfirmed', 'skipped', 'pending']
        .filter(key => Number.isSafeInteger(summary?.[key]) && summary[key] >= 0)
        .map(key => [key, summary[key]]));
}

async function main() {
    const required = name => {
        const value = String(process.env[name] || '').trim();
        if(!value) throw new Error('Required existing registration credentials unavailable.');
        return value;
    };
    const syncUrl = required('FCC_FOREVER_SYNC_URL');
    const syncKey = required('FCC_FOREVER_SYNC_KEY');
    const dryRun = String(process.env.FCC_REGISTRATION_DRY_RUN || '').trim() === '1';
    const accounts = validatePendingPayload(await fccRequest('registration_pending', syncUrl, syncKey));
    if(process.argv.includes('--pending-count')) {
        if(accounts.length === 0 && !dryRun) {
            const response = await fccRequest('registration_decisions', syncUrl, syncKey,
                {decisions: '[]', dry_run: '0'});
            console.log(JSON.stringify({delivery_retry_processed: true, summary: safeServerSummary(response.summary)}));
        }
        console.log(JSON.stringify({pending_count: accounts.length}));
        if(process.env.GITHUB_OUTPUT) await fs.appendFile(process.env.GITHUB_OUTPUT, `pending_count=${accounts.length}\n`);
        return;
    }
    if(accounts.length === 0) {
        if(!dryRun) {
            const response = await fccRequest('registration_decisions', syncUrl, syncKey,
                {decisions: '[]', dry_run: '0'});
            console.log(JSON.stringify({delivery_retry_processed: true, summary: safeServerSummary(response.summary)}));
        }
        console.log(JSON.stringify({checked: 0, dry_run: dryRun}));
        return;
    }
    const username = required('FLP360_USERNAME');
    const password = required('FLP360_PASSWORD');
    const {chromium} = await import(process.env.PLAYWRIGHT_MODULE_URL || 'playwright');
    const browser = await chromium.launch({headless: true, args: ['--disable-gpu']});
    const context = await browser.newContext({locale: 'en-US'});
    try {
        const page = await context.newPage();
        await login(page, username, password);
        const configuration = await flpApiConfiguration(page);
        const decisions = [];
        for(const account of accounts) {
            const evidence = await collectRegistrationEvidence(page, configuration, account);
            decisions.push(evaluateRegistration(account, evidence));
        }
        console.log(JSON.stringify({...summarizeDecisions(decisions), dry_run: dryRun}));
        if(!dryRun) {
            for(let start = 0; start < decisions.length; start += 25) {
                const response = await fccRequest('registration_decisions', syncUrl, syncKey,
                    {decisions: JSON.stringify(decisions.slice(start, start + 25)), dry_run: '0'});
                console.log(JSON.stringify({batch_processed: true, summary: safeServerSummary(response.summary)}));
            }
        }
    } finally {
        await context.close();
        await browser.close();
    }
}

if(process.argv[1] && import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href) {
    main().catch(() => {console.error('Registration verification failed; unconfirmed registrations remain pending.'); process.exitCode = 1;});
}

export {evaluateRegistration, freshEvidence, validatePendingPayload, unwrapSingleRecord,
    collectRegistrationEvidence, summarizeDecisions, safeServerSummary,
    fetchExactDistributorDetail, verifySponsorChain};
/* /Custom code: FC-2026-10-07 */
