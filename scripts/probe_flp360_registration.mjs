/* Custom code: FC-2026-10-07: Read-only discovery of FLP registration evidence. */
import path from 'node:path';
import process from 'node:process';
import {pathToFileURL} from 'node:url';
import {
    login, flpApiConfiguration, flpGetJson, reportV2Url,
    normalizeFboId, resolveFccAccountCountryCode, zagrebPeriod,
} from './flp360_cloud_sync.mjs';
import {unwrapSingleRecord, verifySponsorChain} from './flp360_registration_check.mjs';

const ROOT_FBO_ID = '360000760944';

function schemaOnly(value, depth = 0) {
    if(value === null) return 'null';
    if(Array.isArray(value)) return {type: 'array', length: value.length,
        items: depth < 4 ? value.slice(0, 1).map(item => schemaOnly(item, depth + 1)) : []};
    if(typeof value !== 'object') return typeof value;
    if(depth > 4) return 'object';
    return Object.fromEntries(Object.entries(value).slice(0, 100).map(([key, item]) => [
        /\d{8}|@/.test(key) ? '[dynamic-key]' : key,
        schemaOnly(item, depth + 1),
    ]));
}

async function fccRead(metric, syncUrl, syncKey) {
    const response = await fetch(syncUrl, {method: 'POST', headers: {
        'X-FCC-Forever-Sync-Key': syncKey,
        'Content-Type': 'application/x-www-form-urlencoded',
    }, body: new URLSearchParams({metric, report_period: zagrebPeriod()})});
    const payload = await response.json();
    if(!response.ok || payload.status !== 'success' || payload.metric !== metric) {
        throw new Error('FCC read endpoint unavailable.');
    }
    return payload;
}

async function main() {
    const syncUrl = String(process.env.FCC_FOREVER_SYNC_URL || '').trim();
    const syncKey = String(process.env.FCC_FOREVER_SYNC_KEY || '').trim();
    const username = String(process.env.FLP360_USERNAME || process.env.FLP_USERNAME || '').trim();
    const password = String(process.env.FLP360_PASSWORD || process.env.FLP_PASSWORD || '').trim();
    if(!syncUrl || !syncKey || !username || !password) throw new Error('Required existing credentials unavailable.');
    const {chromium} = await import(process.env.PLAYWRIGHT_MODULE_URL || 'playwright');
    const browser = await chromium.launch({headless: true, args: ['--disable-gpu']});
    const context = await browser.newContext({locale: 'en-US'});
    try {
        const page = await context.newPage();
        await login(page, username, password);
        const configuration = await flpApiConfiguration(page);
        const targets = [{kind: 'root', fboId: ROOT_FBO_ID, countryCode: configuration.operatingCountryCode}];
        let verifiedRootSponsorId = '';
        for(const [metric, kind, limit] of [['fcc_accounts', 'active', 2], ['registration_pending', 'pending', 3]]) {
            try {
                const payload = await fccRead(metric, syncUrl, syncKey);
                console.log(JSON.stringify({kind, endpoint_available: true, account_count: payload.accounts?.length || 0}));
                for(const account of (payload.accounts || []).filter(item => normalizeFboId(item.fbo_id)).slice(0, limit)) {
                    targets.push({kind, fboId: normalizeFboId(account.fbo_id),
                        countryCode: resolveFccAccountCountryCode(account.country_code, configuration)});
                }
            } catch {
                console.log(JSON.stringify({kind, endpoint_available: false}));
            }
        }
        for(const [index, target] of targets.entries()) {
            for(const [source, relativePath] of [
                ['detail', `downlineLoggedInDetails/fboId/${target.fboId}/country/${encodeURIComponent(target.countryCode)}`],
                ['tree', `distributors/${target.fboId}/treeview-cc?countryCode=${encodeURIComponent(target.countryCode)}`],
            ]) {
                try {
                    const payload = await flpGetJson(page, reportV2Url(configuration, relativePath), configuration);
                    console.log(JSON.stringify({sample: index + 1, kind: target.kind, source, schema: schemaOnly(payload)}));
                    if(source === 'detail') {
                        const detail = unwrapSingleRecord(payload);
                        const exact = detail && normalizeFboId(detail.distributorId) === target.fboId;
                        console.log(JSON.stringify({sample: index + 1, kind: target.kind,
                            exact_identity: Boolean(exact), generation_zero: detail?.generation === 0,
                            generation_negative: typeof detail?.generation === 'number' && detail.generation < 0,
                            generation_nonnegative_integer: Number.isSafeInteger(detail?.generation) && detail.generation >= 0,
                            sponsor_present: Boolean(normalizeFboId(detail?.sponsorDistributorId)),
                            sponsor_is_root: normalizeFboId(detail?.sponsorDistributorId) === ROOT_FBO_ID,
                            email_present: typeof detail?.email === 'string' && detail.email.trim() !== ''}));
                        if(exact && target.kind !== 'root') {
                            const chain = await verifySponsorChain(page, configuration, target.fboId, detail,
                                [...new Set([target.countryCode, configuration.operatingCountryCode])],
                                {rootSponsorId: verifiedRootSponsorId});
                            console.log(JSON.stringify({sample: index + 1, kind: target.kind,
                                chain_reached_root: chain.reachedRoot,
                                outside_root_boundary_confirmed: chain.reachedOutsideBoundary,
                                generation_consistent: chain.generationConsistent, checked_count: chain.checkedCount}));
                        }
                        if(exact && target.kind === 'root') {
                            const sponsorId = normalizeFboId(detail.sponsorDistributorId);
                            if(detail.generation === 0) verifiedRootSponsorId = sponsorId;
                            if(sponsorId && sponsorId !== ROOT_FBO_ID) {
                                targets.push({kind: 'root_sponsor_control', fboId: sponsorId,
                                    countryCode: configuration.operatingCountryCode});
                            }
                        }
                    }
                } catch {
                    console.log(JSON.stringify({sample: index + 1, kind: target.kind, source, available: false}));
                }
            }
        }
        try {
            const payload = await flpGetJson(page, reportV2Url(configuration,
                `downlineLoggedInDetails/fboId/000000000000/country/${encodeURIComponent(configuration.operatingCountryCode)}`), configuration);
            console.log(JSON.stringify({kind: 'empty_id_control', schema: schemaOnly(payload)}));
        } catch {console.log(JSON.stringify({kind: 'empty_id_control', available: false}));}
        const scriptUrls = await page.evaluate(() => [...document.scripts].map(script => script.src).filter(Boolean));
        const hints = new Set();
        for(const url of scriptUrls.filter(url => /main|scripts/.test(url)).slice(0, 4)) {
            try {
                const response = await context.request.get(url, {timeout: 60000});
                if(!response.ok()) continue;
                const source = await response.text();
                for(const match of source.matchAll(/["']([^"'\r\n]{1,160}(?:sponsor|upline|downline|treeview)[^"'\r\n]{0,160})["']/gi)) {
                    const hint = match[1];
                    if(!/\d{8}|@|Bearer|token|password/i.test(hint)) hints.add(hint);
                    if(hints.size >= 100) break;
                }
            } catch {}
        }
        console.log(JSON.stringify({application_source_hints: [...hints]}));
        console.log('Read-only discovery completed; no FCC writes or messages were sent.');
    } finally {
        await context.close();
        await browser.close();
    }
}

if(process.argv[1] && import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href) {
    main().catch(() => {console.error('FLP registration discovery failed; no FCC writes were performed.'); process.exitCode = 1;});
}

export {schemaOnly};
/* /Custom code: FC-2026-10-07 */
