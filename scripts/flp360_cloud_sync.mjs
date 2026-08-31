/* Custom code: FC-2026-08-13: Cloud FLP360 to FCC synchronization */

import fs from 'node:fs/promises';
import crypto from 'node:crypto';
import path from 'node:path';
import process from 'node:process';
import {pathToFileURL} from 'node:url';

const FLP360_BASE_URL = 'https://flp360.foreverliving.com';
const ROOT_FBO_ID = '360-000-760-944';
const OUTPUT_DIRECTORY = process.env.RUNNER_TEMP || '/tmp';
const MAX_SAFE_DOWNLINE_MEMBERS = 1000;
const CONFIRMED_DOWNLINE_BASE_PATH = process.env.FLP360_CONFIRMED_DOWNLINE_BASE
    || path.join(process.cwd(), '.codex-state', 'flp360-confirmed-downline.csv');

function requiredEnvironment(name) {
    const value = String(process.env[name] || '').trim();
    if(!value) throw new Error(`Nedostaje sigurnosna varijabla ${name}.`);
    return value;
}

function zagrebPeriod(date = new Date()) {
    const parts = new Intl.DateTimeFormat('en', {
        timeZone: 'Europe/Zagreb',
        year: 'numeric',
        month: '2-digit',
    }).formatToParts(date);
    const year = parts.find(part => part.type === 'year')?.value;
    const month = parts.find(part => part.type === 'month')?.value;
    return `${year}-${month}`;
}

function syncRunDate(value, fallback = new Date()) {
    const period = String(value || '').trim();
    if(!period) return fallback;
    const match = period.match(/^(20\d{2})-(0[1-9]|1[0-2])$/);
    if(!match) throw new Error('FLP360_SYNC_PERIOD mora biti u obliku YYYY-MM.');
    return new Date(Date.UTC(Number(match[1]), Number(match[2]), 0, 12, 0, 0));
}

function zagrebPeriodParts(date = new Date()) {
    const [year, month] = zagrebPeriod(date).split('-').map(Number);
    const monthLabel = new Intl.DateTimeFormat('en', {month: 'short', timeZone: 'UTC'})
        .format(new Date(Date.UTC(year, month - 1, 1)))
        .toLocaleUpperCase('en');
    return {year, month, monthLabel};
}

function currentFlpMonthLabel(date = new Date()) {
    const parts = new Intl.DateTimeFormat('en', {
        timeZone: 'Europe/Zagreb',
        year: 'numeric',
        month: '2-digit',
    }).formatToParts(date);
    const year = parts.find(part => part.type === 'year')?.value;
    const month = parts.find(part => part.type === 'month')?.value;
    return `${month}/${year}-Not Closed`;
}

function findCurrentFlpMonthLabel(labels, date = new Date()) {
    const expected = currentFlpMonthLabel(date);
    const [expectedMonth, expectedYearAndStatus] = expected.split('/');
    const expectedYear = expectedYearAndStatus.split('-')[0];
    const monthDate = new Date(Date.UTC(Number(expectedYear), Number(expectedMonth) - 1, 1));
    const acceptedMonths = new Set([
        String(Number(expectedMonth)),
        expectedMonth,
        new Intl.DateTimeFormat('en', {month: 'short', timeZone: 'UTC'}).format(monthDate),
        new Intl.DateTimeFormat('en', {month: 'long', timeZone: 'UTC'}).format(monthDate),
    ].map(value => value.toLocaleLowerCase('en')));

    return labels.find(label => {
        const normalizedLabel = String(label).trim().toLocaleLowerCase('en');
        if(!normalizedLabel.includes(expectedYear) || !/not\s*closed/.test(normalizedLabel)) return false;
        return [...acceptedMonths].some(month => {
            if(/^\d+$/.test(month)) {
                return new RegExp(`(^|\\D)0?${Number(month)}(\\D|$)`).test(normalizedLabel);
            }
            return new RegExp(`(^|[^a-z])${month}([^a-z]|$)`).test(normalizedLabel);
        });
    }) || '';
}

function readyReportMessage(bodyText) {
    return bodyText.match(/Your report generated on[^\n]+is ready\./i)?.[0]?.trim() || '';
}

function parseCsvLine(line) {
    const fields = [];
    let field = '';
    let quoted = false;
    for(let index = 0; index < line.length; index++) {
        const character = line[index];
        if(character === '"') {
            if(quoted && line[index + 1] === '"') {
                field += '"';
                index++;
            } else {
                quoted = !quoted;
            }
        } else if(character === ',' && !quoted) {
            fields.push(field);
            field = '';
        } else {
            field += character;
        }
    }
    fields.push(field);
    return fields;
}

function csvField(value) {
    const text = String(value ?? '');
    return /[",\r\n]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
}

function csvDocument(rows) {
    return `${rows.map(row => row.map(csvField).join(',')).join('\r\n')}\r\n`;
}

function normalizeFboId(value) {
    const digits = String(value ?? '').replace(/\D+/g, '');
    return digits.length === 12 ? digits : '';
}

function normalizeCountryCode(value) {
    const code = String(value ?? '').trim().toLocaleUpperCase('en');
    return /^[A-Z]{2,3}$/.test(code) ? code : '';
}

function resolveFccAccountCountryCode(value, configuration) {
    const accountCode = normalizeCountryCode(value);
    const iso2ToFlp = {
        AL: 'ALB', AT: 'AUT', BA: 'BIH', BE: 'BEL', BG: 'BGR', CH: 'CHE', CZ: 'CZE',
        DE: 'DEU', DK: 'DNK', ES: 'ESP', FI: 'FIN', FR: 'FRA', GB: 'GBR', GR: 'GRC',
        HR: 'HRV', HU: 'HUN', IE: 'IRL', IT: 'ITA', ME: 'MNE', MK: 'MKD', NL: 'NLD',
        NO: 'NOR', PL: 'POL', PT: 'PRT', RO: 'ROU', RS: 'SRB', SE: 'SWE', SI: 'SVN',
        SK: 'SVK', US: 'USA', XK: 'XKX',
    };
    return iso2ToFlp[accountCode]
        || accountCode
        || normalizeCountryCode(configuration?.operatingCountryCode);
}

function prepareRegisteredFccAccounts(payload, configuration) {
    if(payload?.status !== 'success' || payload?.metric !== 'fcc_accounts' || !Array.isArray(payload.accounts)) {
        throw new Error('FCC nije vratio valjan popis aktivnih računa s Forever ID-om.');
    }
    const seen = new Set();
    const accounts = payload.accounts.map(account => {
        const fboId = normalizeFboId(account?.fbo_id);
        if(!/^360\d{9}$/.test(fboId) || seen.has(fboId) || Number(account?.active_link_count) < 1) {
            throw new Error('FCC popis računa sadrži neispravan ili dupliciran Forever ID.');
        }
        seen.add(fboId);
        return {
            fboId,
            countryCode: resolveFccAccountCountryCode(account?.country_code, configuration),
            activeLinkCount: Number(account.active_link_count),
        };
    });
    if(Number(payload?.summary?.unique_forever_ids) !== accounts.length) {
        throw new Error('FCC broj aktivnih Forever ID-jeva ne odgovara vraćenom popisu.');
    }
    return accounts;
}

function resolveLiveCcCountryCode(homeCountryCode, configuration) {
    const homeCountry = normalizeCountryCode(homeCountryCode);
    const rootHomeCountry = normalizeCountryCode(configuration?.homeCountryCode);
    const operatingCountry = normalizeCountryCode(configuration?.operatingCountryCode);
    if(!homeCountry || !rootHomeCountry || !operatingCountry) return '';
    /* Croatia is administered through the current HUN operating market in this
     * FLP360 account. Preserve that verified regional context for the home team;
     * only members outside the home region use their own market code. */
    return homeCountry === rootHomeCountry ? operatingCountry : homeCountry;
}

function parseFlpTimestamp(value) {
    const input = typeof value === 'number' || /^\d{13}$/.test(String(value ?? '').trim())
        ? Number(value)
        : String(value ?? '');
    const date = new Date(input);
    return Number.isNaN(date.getTime()) ? null : date;
}

function encryptFlpAuthorization(value, encryptionKey) {
    const salt = crypto.randomBytes(64);
    const initializationVector = crypto.randomBytes(12);
    const key = crypto.pbkdf2Sync(encryptionKey, salt, 2145, 32, 'sha512');
    const cipher = crypto.createCipheriv('aes-256-gcm', key, initializationVector);
    const encrypted = Buffer.concat([cipher.update(value, 'utf8'), cipher.final()]);
    return Buffer.concat([salt, initializationVector, encrypted, cipher.getAuthTag()]).toString('base64');
}

function buildDownlineGenerationUrl(reportBase, distributorId, operatingCountry = 'HUN', homeCountry = 'HRV') {
    const url = new URL(`V2/distributors/${distributorId}/generate/rewire-downline-excel-query`, `${String(reportBase).replace(/\/+$/, '')}/`);
    const parameters = {
        year: 0,
        month: 0,
        expandingLevel: 0,
        pageNumber: 1,
        numberOfRecords: 15,
        showNonZero: false,
        memberLevel: 0,
        country: operatingCountry,
        generationValue: 0,
        sponsorDistID: distributorId,
        isExcelView: true,
        downlineGenValue: 0,
        homeCountryCode: homeCountry,
    };
    for(const [name, value] of Object.entries(parameters)) url.searchParams.set(name, String(value));
    return url.toString();
}

function buildDownlineDownloadUrl(cdnBase, processedFilePath) {
    const match = String(processedFilePath || '').match(/\/CustomerReports\/(Downline(?: Mobile)?)\/([^/?#]+\.csv)/i);
    if(!match) throw new Error('FLP360 red za izvoz nije vratio očekivanu Downline CSV putanju.');
    return new URL(`CustomerReports/${match[1]}/${match[2]}`, `${String(cdnBase).replace(/\/+$/, '')}/`).toString();
}

async function flpApiConfiguration(page) {
    const configuration = await page.evaluate(() => {
        const parseJson = (value, fallback) => {
            try {
                return JSON.parse(value || '');
            } catch {
                return fallback;
            }
        };
        const applicationConfiguration = parseJson(localStorage.getItem('appflp360.Configuration'), {});
        const api = applicationConfiguration.apiConfiguration || window.apiConfig || {};
        const storedReportBase = parseJson(
            localStorage.getItem('appflp360.ReportApicategory'),
            parseJson(localStorage.getItem('appflp360.reportApicategory'), '')
        );
        const reportBase = /^https:\/\//i.test(String(storedReportBase || ''))
            ? storedReportBase
            : api.reportProApi || api.reportLiteApi || `${api.apiGatewayURL}/${api.reportApi || 'reporttdm'}`;
        return {
            aesEncryptionKey: api.aesEncryptionKey || '',
            apiGatewayUrl: api.apiGatewayURL || '',
            cdnUrl: api.cdnURL || '',
            guestToken: parseJson(localStorage.getItem('appflp360.guestToken'), api.guestToken || ''),
            homeCountryCode: parseJson(localStorage.getItem('appflp360.homeCountryCode'), 'HRV'),
            operatingCountryCode: parseJson(localStorage.getItem('appflp360.operatingCountryCode'), ''),
            reportBase,
        };
    });

    for(const name of ['aesEncryptionKey', 'apiGatewayUrl', 'cdnUrl', 'guestToken', 'homeCountryCode', 'operatingCountryCode', 'reportBase']) {
        if(!String(configuration[name] || '').trim()) throw new Error(`FLP360 konfiguracija nema ${name}.`);
    }
    return configuration;
}

function reportV2Url(configuration, relativePath) {
    const base = String(configuration.reportBase).replace(/\/+$/, '').replace(/\/V2$/i, '');
    return new URL(`V2/${String(relativePath).replace(/^\/+/, '')}`, `${base}/`).toString();
}

function flpApiHeaders(configuration, requestUrl) {
    const requestId = crypto.randomUUID();
    const authorization = encryptFlpAuthorization(`${requestId}||Bearer ${configuration.guestToken}&&3`, configuration.aesEncryptionKey);
    const headers = {
        Authorization: authorization,
        UUID: requestId,
        'Content-Type': 'application/json',
        isOfflineFlow: 'true',
    };
    if(String(requestUrl).includes('/reporttdm')) headers['cache-control'] = 'no-cache';
    return headers;
}

async function flpGetJson(page, requestUrl, configuration, attempts = 2) {
    let lastError;
    for(let attempt = 1; attempt <= attempts; attempt++) {
        try {
            const response = await page.context().request.get(requestUrl, {
                headers: flpApiHeaders(configuration, requestUrl),
                timeout: 120000,
            });
            const responseText = await response.text();
            if(!response.ok()) throw new Error(`HTTP ${response.status()}`);
            if(!responseText.trim()) return null;
            return JSON.parse(responseText);
        } catch(error) {
            lastError = error;
            if(attempt < attempts) await page.waitForTimeout(attempt * 750);
        }
    }
    throw new Error(`FLP360 podatkovni poziv nije uspio (${lastError?.message || 'nepoznata pogreška'}).`);
}

function officialFourCoreSnapshots() {
    /* Verified directly against the attached FLP360 4 Core Summary. Closed
       periods are immutable and can be safely upserted on every cloud run. */
    return [
        {
            period: '2025-07',
            values: {
                open: {
                    month: {recruitment: 1, retention: 46, productivity: 1.374, development: 8.51},
                    ytd: {recruitment: 72, retention: 155, productivity: 1.385, development: 9.23},
                },
                downline: {
                    month: {recruitment: 30, retention: 281, productivity: 1.492, development: 14.74},
                    ytd: {recruitment: 462, retention: 947, productivity: 1.49, development: 8.68},
                },
            },
        },
        {
            period: '2026-07',
            values: {
                open: {
                    month: {recruitment: 9, retention: 41, productivity: 1.238, development: 8},
                    ytd: {recruitment: 53, retention: 116, productivity: 1.38, development: 6.21},
                },
                downline: {
                    month: {recruitment: 30, retention: 246, productivity: 1.4, development: 12.68},
                    ytd: {recruitment: 174, retention: 695, productivity: 1.376, development: 8.25},
                },
            },
        },
    ];
}

async function saveDownload(download, targetName) {
    const targetPath = path.join(OUTPUT_DIRECTORY, targetName);
    await download.saveAs(targetPath);
    const stat = await fs.stat(targetPath);
    if(stat.size < 1000) throw new Error(`${targetName} je premalen za siguran uvoz.`);
    return targetPath;
}

async function validateDownline(filePath) {
    const contents = await fs.readFile(filePath, 'utf8');
    const lines = contents.split(/\r?\n/).filter(Boolean);
    const header = lines[0] || '';
    const requiredHeaders = ['FBO ID', 'TREESEQUENCE', 'NAME', 'TITLE', 'GENERATION', 'COUNTRY', 'PERSONAL CC', 'TOTAL CC', 'TOTAL ACTIVE CC', 'NON MANAGER CC', '4CC ACTIVE'];
    const missingHeaders = requiredHeaders.filter(headerName => !header.includes(headerName));

    if(missingHeaders.length) throw new Error(`Downline nema očekivana polja: ${missingHeaders.join(', ')}.`);
    if(lines.length < 401) throw new Error(`Downline ima samo ${Math.max(0, lines.length - 1)} redaka; uvoz je zaustavljen.`);

    const countryCodes = lines.slice(1)
        .map(line => String(parseCsvLine(line)[5] || '').trim())
        .filter(Boolean);
    const countries = [...new Set(countryCodes)].sort();
    const hrvRows = countryCodes.filter(country => country === 'HRV').length;
    if(hrvRows < 250) throw new Error(`Downline nema očekivani HRV opseg (${hrvRows} redaka).`);
    if(countries.length < 2) throw new Error(`Downline nije međunarodni izvještaj (${countries.join(', ') || 'bez država'}).`);

    return {rows: lines.length - 1, hrvRows, countries};
}

async function validateXlsx(filePath, label) {
    const data = await fs.readFile(filePath);
    if(data.length < 5000 || data[0] !== 0x50 || data[1] !== 0x4b) {
        throw new Error(`${label} nije valjana XLSX datoteka.`);
    }
    return {bytes: data.length};
}

async function login(page, username, password) {
    await page.goto(`${FLP360_BASE_URL}/auth/login`, {waitUntil: 'domcontentloaded', timeout: 60000});
    await page.waitForURL(url => url.origin !== FLP360_BASE_URL || url.pathname === '/dashboard', {timeout: 60000}).catch(() => {});

    if(new URL(page.url()).origin !== FLP360_BASE_URL) {
        /* FLP360 currently renders a hidden legacy username field together with the
         * visible SSO form. Restrict every fallback to visible controls so the cloud
         * runner cannot wait on the wrong Angular input. */
        const usernameInput = page.locator('#user-input-login-id:visible, #username:visible, input[name="username"]:visible, input[type="email"]:visible').first();
        const passwordInput = page.locator('#password:visible, input[name="password"]:visible, input[type="password"]:visible').first();
        await usernameInput.waitFor({state: 'visible', timeout: 30000});
        await usernameInput.fill(username);
        await passwordInput.fill(password);

        const submit = page.locator('#kc-login:visible, button[name="login"]:visible, button[type="submit"]:visible, input[type="submit"]:visible').first();
        await submit.click({force: true, noWaitAfter: true});
        await page.waitForURL(url => url.origin === FLP360_BASE_URL && url.pathname === '/dashboard', {timeout: 120000});
    }

    if(new URL(page.url()).pathname !== '/dashboard') {
        await page.goto(`${FLP360_BASE_URL}/dashboard`, {waitUntil: 'domcontentloaded', timeout: 60000});
    }
    await page.getByText(ROOT_FBO_ID, {exact: true}).waitFor({state: 'visible', timeout: 120000});
}

function extractFourCcRows(payload) {
    if(Array.isArray(payload?.[0]?.body)) return payload[0].body;
    if(Array.isArray(payload?.body)) return payload.body;
    return Array.isArray(payload) ? payload : [];
}

function extractCurrentCcSummary(payload, date = new Date()) {
    const {year, month} = zagrebPeriodParts(date);
    const rows = Array.isArray(payload?.[0]?.body)
        ? payload[0].body
        : (Array.isArray(payload?.body) ? payload.body : payload);
    if(!Array.isArray(rows) || !rows.length) {
        throw new Error('FLP360 CC Summary live odgovor je prazan.');
    }
    const current = rows.find(row =>
        Number(row?.processingYear) === year
        && Number(row?.processingMonth) === month
        && String(row?.valueType || '').toLocaleLowerCase('en') === 'monthly'
    );
    if(!current) throw new Error(`FLP360 CC Summary nema aktualno razdoblje ${zagrebPeriod(date)}.`);
    return {
        totalCc: nonNegativeCc(current.totalCC, 'CC Summary totalCC'),
        globalTotalCc: nonNegativeCc(current.globalTotalCC, 'CC Summary globalTotalCC'),
    };
}

function validateFourCcRows(rows, date = new Date()) {
    const {year, month} = zagrebPeriodParts(date);
    if(!Array.isArray(rows) || !rows.length) throw new Error('4 CC Active live odgovor je prazan; postojeći FCC podaci ostaju sačuvani.');
    const ids = rows.map(row => normalizeFboId(row?.fboID));
    if(ids.some(id => !id) || new Set(ids).size !== ids.length) throw new Error('4 CC Active sadrži neispravne ili duplicirane FBO ID-eve.');
    if(rows.some(row => Number(row?.processingYear) !== year || Number(row?.processingMonth) !== month)) {
        throw new Error(`4 CC Active nije za aktualno razdoblje ${zagrebPeriod(date)}.`);
    }
    for(const row of rows) {
        for(const field of ['personalCC', 'totalActiveCC']) {
            if(!Number.isFinite(Number(row?.[field])) || Number(row[field]) < 0) throw new Error(`4 CC Active nema valjano polje ${field}.`);
        }
    }
    return {rows: rows.length, ids: new Set(ids)};
}

function buildFourCcCsv(rows, date = new Date()) {
    const {year, monthLabel} = zagrebPeriodParts(date);
    return csvDocument([
        ['FBO ID', 'FBO NAME', 'LEVEL', 'HOME COUNTRY', 'PERSONAL CC', 'TOTAL ACTIVE CC', 'SELECTED MONTH/YEAR'],
        ...rows.map(row => [
            normalizeFboId(row.fboID),
            row.fboName || 'Bez imena',
            row.level || '',
            row.homeCountry || '',
            Number(row.personalCC).toFixed(3),
            Number(row.totalActiveCC).toFixed(3),
            `${monthLabel} ${year}`,
        ]),
    ]);
}

async function downloadLiveFourCc(page, configuration, date = new Date()) {
    const {year, month} = zagrebPeriodParts(date);
    const distributorId = normalizeFboId(ROOT_FBO_ID);
    const requestUrl = reportV2Url(configuration, `distributors/${distributorId}/year/${year}/month/${month}/rewire-currentMonth-4CC-Active`);
    const rows = extractFourCcRows(await flpGetJson(page, requestUrl, configuration));
    const validation = validateFourCcRows(rows, date);
    const targetPath = path.join(OUTPUT_DIRECTORY, `flp360-4cc-active-live-${zagrebPeriod(date)}.csv`);
    await fs.writeFile(targetPath, buildFourCcCsv(rows, date), {mode: 0o600});
    return {path: targetPath, records: rows, rowCount: validation.rows, ids: validation.ids};
}

async function fetchLiveCcSummary(page, configuration, date = new Date()) {
    const {year, month} = zagrebPeriodParts(date);
    const distributorId = normalizeFboId(ROOT_FBO_ID);
    const requestUrl = new URL(reportV2Url(
        configuration,
        `fboId/${distributorId}/country/${encodeURIComponent(configuration.operatingCountryCode)}/year/${year}/month/${month}/rewire-earnings-CC-summary`
    ));
    requestUrl.searchParams.set('isVolume', 'true');
    requestUrl.searchParams.set('comparisionYear', String(year - 1));
    return extractCurrentCcSummary(await flpGetJson(page, requestUrl.toString(), configuration), date);
}

async function downloadLatestDownlineBase(page, configuration, date = new Date()) {
    const distributorId = normalizeFboId(ROOT_FBO_ID);
    const queueUrl = `${String(configuration.apiGatewayUrl).replace(/\/+$/, '')}/flp360/v1/distributors/${distributorId}/report/Downline/report-extract-queue`;
    const payload = await flpGetJson(page, queueUrl, configuration);
    const queue = Array.isArray(payload) ? payload[0] : payload;
    if(!queue?.processedFilePath) throw new Error('FLP360 nema posljednji valjani Downline izvoz za baznu hijerarhiju.');

    const processedAt = parseFlpTimestamp(queue.requestProcessedTime);
    if(!processedAt) throw new Error('FLP360 nije vratio valjano vrijeme baznog Downline izvoza.');
    const ageDays = (date.getTime() - processedAt.getTime()) / 86400000;
    if(ageDays < -1 || ageDays > 14) throw new Error(`Bazni Downline izvoz star je ${Math.max(0, Math.floor(ageDays))} dana; uvoz je zaustavljen.`);

    const downloadUrl = buildDownlineDownloadUrl(configuration.cdnUrl, queue.processedFilePath);
    const response = await page.context().request.get(downloadUrl, {headers: {Accept: 'text/csv'}, timeout: 120000});
    if(!response.ok()) throw new Error(`FLP360 Downline CSV nije moguće preuzeti (HTTP ${response.status()}).`);
    const contents = (await response.body()).toString('utf8');
    if(Buffer.byteLength(contents) < 1000) throw new Error('FLP360 Downline CSV je premalen za siguran uvoz.');
    const candidateMemberCount = downlineMemberCount(contents);
    if(candidateMemberCount < 400 || candidateMemberCount > MAX_SAFE_DOWNLINE_MEMBERS) {
        let confirmedContents;
        let confirmedStat;
        try {
            [confirmedContents, confirmedStat] = await Promise.all([
                fs.readFile(CONFIRMED_DOWNLINE_BASE_PATH, 'utf8'),
                fs.stat(CONFIRMED_DOWNLINE_BASE_PATH),
            ]);
        } catch {
            throw new Error(`FLP360 Downline ima neočekivanih ${candidateMemberCount} članova, a zadnja potvrđena lokalna hijerarhija nije dostupna.`);
        }
        const confirmedAgeDays = (date.getTime() - confirmedStat.mtimeMs) / 86400000;
        const confirmedMemberCount = downlineMemberCount(confirmedContents);
        if(confirmedAgeDays < -1 || confirmedAgeDays > 14
            || confirmedMemberCount < 400 || confirmedMemberCount > MAX_SAFE_DOWNLINE_MEMBERS) {
            throw new Error(`FLP360 Downline ima neočekivanih ${candidateMemberCount} članova, a lokalna potvrđena hijerarhija nije sigurna za fallback.`);
        }
        console.warn(`FLP360 Downline kandidat ima neočekivanih ${candidateMemberCount} članova; koristi se zadnja potvrđena hijerarhija od ${confirmedMemberCount} članova.`);
        return {contents: confirmedContents, lookupContents: contents, processedAt: confirmedStat.mtime, ageDays: confirmedAgeDays};
    }
    return {contents, lookupContents: contents, processedAt, ageDays};
}

async function persistConfirmedDownline(filePath) {
    const contents = await fs.readFile(filePath);
    await fs.mkdir(path.dirname(CONFIRMED_DOWNLINE_BASE_PATH), {recursive: true, mode: 0o700});
    await fs.writeFile(CONFIRMED_DOWNLINE_BASE_PATH, contents, {mode: 0o600});
    await fs.chmod(CONFIRMED_DOWNLINE_BASE_PATH, 0o600);
}

function nonNegativeCc(value, fieldName) {
    const number = Number(value);
    if(!Number.isFinite(number) || number < 0) throw new Error(`FLP360 live CC nema valjano polje ${fieldName}.`);
    return number;
}

function optionalNonNegativeCc(value, fieldName) {
    return value === null || value === undefined || String(value).trim() === ''
        ? null
        : nonNegativeCc(value, fieldName);
}

function extractLiveCcRecord(payload, expectedFboId, date = new Date()) {
    const {year, month} = zagrebPeriodParts(date);
    const record = Array.isArray(payload) ? payload[0] : payload?.data?.[0];
    if(!record || normalizeFboId(record.fboId) !== normalizeFboId(expectedFboId)) {
        throw new Error(`FLP360 live CC nije potvrdio FBO ID ${expectedFboId}.`);
    }
    const monthlyValues = Array.isArray(record.monthlyCCValues) ? record.monthlyCCValues : [];
    const current = monthlyValues.find(value => Number(value?.processingYear) === year && Number(value?.processingMonth) === month);
    const confirmedZero = !current
        && Number(record.processingYear) === 0
        && monthlyValues.length > 0
        && monthlyValues.every(value => Number(value?.processingYear) === 0 && Number(value?.processingMonth) === 0);
    if(!current && !confirmedZero) throw new Error(`FLP360 live CC za ${expectedFboId} nema potvrđeno razdoblje ${zagrebPeriod(date)}.`);
    const source = current || {};
    return {
        fboId: normalizeFboId(expectedFboId),
        personalCc: confirmedZero ? 0 : nonNegativeCc(source.personalCCMTD, 'personalCCMTD'),
        totalCc: confirmedZero ? 0 : nonNegativeCc(source.totalCCMTD, 'totalCCMTD'),
        totalActiveCc: confirmedZero ? 0 : nonNegativeCc(source.totalActiveCCMTD, 'totalActiveCCMTD'),
        nonManagerCc: confirmedZero ? 0 : nonNegativeCc(source.nonManagerCCMTD, 'nonManagerCCMTD'),
        leadershipCc: confirmedZero ? 0 : nonNegativeCc(source.leaderCC, 'leaderCC'),
        totalActiveCcYtd: confirmedZero ? 0 : nonNegativeCc(record.totalActiveCC, 'totalActiveCC'),
        nonManagerCcYtd: confirmedZero ? 0 : nonNegativeCc(record.nonManagerCC, 'nonManagerCC'),
        leadershipCcYtd: confirmedZero ? 0 : nonNegativeCc(record.leaderCC, 'leaderCC'),
    };
}

function extractLiveZeroFallback(treePayload, detailPayload, expectedFboId) {
    const tree = Array.isArray(treePayload) ? treePayload[0] : treePayload?.data?.[0];
    const detail = Array.isArray(detailPayload) ? detailPayload[0] : detailPayload;
    const monthlyValues = Array.isArray(tree?.monthlyCCValues) ? tree.monthlyCCValues : [];
    const isEmptyTreeSentinel = tree
        && !normalizeFboId(tree.fboId)
        && Number(tree.processingYear) === 0
        && monthlyValues.length > 0
        && monthlyValues.every(value => Number(value?.processingYear) === 0 && Number(value?.processingMonth) === 0);
    if(!isEmptyTreeSentinel
        || normalizeFboId(detail?.distributorId) !== normalizeFboId(expectedFboId)) {
        throw new Error(`FLP360 rezervna live potvrda nije sigurna za ${expectedFboId}.`);
    }
    return {
        fboId: normalizeFboId(expectedFboId),
        personalCc: nonNegativeCc(detail.personalCCCurMonth, 'personalCCCurMonth'),
        totalCc: nonNegativeCc(detail.totalCCCurMonth, 'totalCCCurMonth'),
        totalActiveCc: nonNegativeCc(detail.totalActiveCCCurMonth, 'totalActiveCCCurMonth'),
        /* The detail fallback does not consistently expose these manager-only
         * fields. Null intentionally preserves the last verified Downline value
         * instead of turning missing international data into a false zero. */
        nonManagerCc: optionalNonNegativeCc(detail.nonManagerCCCurMonth, 'nonManagerCCCurMonth'),
        leadershipCc: optionalNonNegativeCc(detail.leaderCCCurMonth ?? detail.leadershipCCCurMonth, 'leaderCCCurMonth'),
        totalActiveCcYtd: optionalNonNegativeCc(detail.totalActiveCCYTD, 'totalActiveCCYTD'),
        nonManagerCcYtd: null,
        leadershipCcYtd: null,
        usedFallback: true,
    };
}

async function mapWithConcurrency(items, concurrency, mapper) {
    const results = new Array(items.length);
    let nextIndex = 0;
    async function worker() {
        while(nextIndex < items.length) {
            const index = nextIndex++;
            results[index] = await mapper(items[index], index);
        }
    }
    await Promise.all(Array.from({length: Math.min(concurrency, items.length)}, worker));
    return results;
}

function extractLiveMemberReferences(baseContents) {
    const rows = String(baseContents).split(/\r?\n/).filter(Boolean).map(parseCsvLine);
    const headers = rows[0] || [];
    const indexByHeader = new Map(headers.map((header, index) => [String(header).trim().toLocaleUpperCase('en'), index]));
    const fboIndex = indexByHeader.get('FBO ID');
    const countryIndex = indexByHeader.get('COUNTRY');
    if(fboIndex === undefined || countryIndex === undefined) {
        throw new Error('Bazni Downline nema FBO ID ili COUNTRY za siguran live CC dohvat.');
    }

    const seen = new Set();
    return rows.slice(1).map(row => {
        const fboId = normalizeFboId(row[fboIndex]);
        const homeCountryCode = normalizeCountryCode(row[countryIndex]);
        if(!fboId || seen.has(fboId)) throw new Error('Bazni Downline sadrži neispravan ili dupliciran FBO ID.');
        if(!homeCountryCode) throw new Error(`Bazni Downline nema valjanu matičnu zemlju za ${fboId}.`);
        seen.add(fboId);
        return {fboId, homeCountryCode};
    });
}

function downlineMemberCount(contents) {
    return extractLiveMemberReferences(contents).length;
}

async function fetchLiveCcForMembers(page, configuration, members, date = new Date(), options = {}) {
    let completed = 0;
    let fallbackCount = 0;
    let operatingMarketFallbackCount = 0;
    const countryCounts = new Map();
    const unconfirmed = [];
    const records = await mapWithConcurrency(members, 8, async member => {
        const fboId = normalizeFboId(member?.fboId);
        const preferredCountryCode = normalizeCountryCode(member?.countryCode);
        const operatingCountryCode = normalizeCountryCode(configuration?.operatingCountryCode);
        if(!fboId || !preferredCountryCode || !operatingCountryCode) {
            throw new Error('Live CC zahtjev nema valjan FBO ID ili matičnu zemlju.');
        }

        const additionalCountryCandidates = Array.isArray(member?.countryCandidates)
            ? member.countryCandidates.map(normalizeCountryCode).filter(Boolean)
            : [];
        const countryCandidates = [...new Set([preferredCountryCode, ...additionalCountryCandidates, operatingCountryCode])];
        let record;
        let confirmedCountryCode = '';
        let usedDetailFallback = false;
        const candidateErrors = [];
        for(const countryCode of countryCandidates) {
            try {
                const requestUrl = reportV2Url(configuration, `distributors/${fboId}/treeview-cc?countryCode=${encodeURIComponent(countryCode)}`);
                const treePayload = await flpGetJson(page, requestUrl, configuration);
                const treeRecord = Array.isArray(treePayload) ? treePayload[0] : treePayload?.data?.[0];
                if(treeRecord && !normalizeFboId(treeRecord.fboId) && Number(treeRecord.processingYear) === 0) {
                    const detailUrl = reportV2Url(configuration, `downlineLoggedInDetails/fboId/${fboId}/country/${encodeURIComponent(countryCode)}`);
                    record = extractLiveZeroFallback(treePayload, await flpGetJson(page, detailUrl, configuration), fboId);
                    usedDetailFallback = true;
                } else {
                    record = extractLiveCcRecord(treePayload, fboId, date);
                }
                confirmedCountryCode = countryCode;
                break;
            } catch(error) {
                candidateErrors.push(`${countryCode}: ${error.message}`);
            }
        }
        if(!record || !confirmedCountryCode) {
            if(options.allowUnconfirmed) {
                unconfirmed.push({fboId, candidateErrors});
                completed++;
                if(completed % 100 === 0 || completed === members.length) console.log(`Live CC provjeren: ${completed}/${members.length}.`);
                return null;
            }
            throw new Error(`FLP360 nije potvrdio live CC za ${fboId} ni u jednom dopuštenom tržištu (${candidateErrors.join(' | ')}).`);
        }
        if(usedDetailFallback) fallbackCount++;
        if(confirmedCountryCode !== preferredCountryCode) {
            operatingMarketFallbackCount++;
        }
        countryCounts.set(confirmedCountryCode, (countryCounts.get(confirmedCountryCode) || 0) + 1);
        completed++;
        if(completed % 100 === 0 || completed === members.length) console.log(`Live CC potvrđen: ${completed}/${members.length}.`);
        return record;
    });
    const confirmedRecords = records.filter(Boolean);
    return {
        records: new Map(confirmedRecords.map(record => [record.fboId, record])),
        unconfirmed,
        fallbackCount,
        operatingMarketFallbackCount,
        countryCounts: Object.fromEntries([...countryCounts.entries()].sort(([left], [right]) => left.localeCompare(right))),
    };
}

function refreshDownlineCsv(baseContents, liveCcByFboId, activeFourCcIds, date = new Date()) {
    const lines = String(baseContents).split(/\r?\n/).filter(Boolean);
    const rows = lines.map(parseCsvLine);
    const headers = rows[0] || [];
    const dataRows = rows.slice(1);
    const {year, monthLabel} = zagrebPeriodParts(date);
    const indexByHeader = new Map(headers.map((header, index) => [String(header).trim().toLocaleUpperCase('en'), index]));
    const requiredStaticHeaders = ['FBO ID', 'TREESEQUENCE', 'NAME', 'TITLE', 'GENERATION', 'COUNTRY'];
    const metricHeaders = {
        fourCc: `4CC ACTIVE - ${monthLabel} - ${year}`,
        personal: `PERSONAL CC - ${monthLabel} - ${year}`,
        total: `TOTAL CC - ${monthLabel} - ${year}`,
        totalActive: `TOTAL ACTIVE CC - ${monthLabel} - ${year}`,
        nonManager: `NON MANAGER CC - ${monthLabel} - ${year}`,
        leadership: `LEADERSHIP CC - ${monthLabel} - ${year}`,
        totalActiveYtd: `TOTAL ACTIVE CC YTD - ${year}`,
        nonManagerYtd: `NON MANAGER CC YTD - ${year}`,
        leadershipYtd: `LEADERSHIP CC YTD - ${year}`,
    };
    const missing = [...requiredStaticHeaders, ...Object.values(metricHeaders)].filter(header => !indexByHeader.has(header));
    if(missing.length) throw new Error(`Bazni Downline nema potrebna polja: ${missing.join(', ')}.`);
    if(dataRows.length < 400) throw new Error(`Bazni Downline ima samo ${dataRows.length} redaka.`);

    const seen = new Set();
    const sums = {personalCc: 0, totalCc: 0, totalActiveCc: 0, nonManagerCc: 0, leadershipCc: 0};
    for(const row of dataRows) {
        const fboId = normalizeFboId(row[indexByHeader.get('FBO ID')]);
        if(!fboId || seen.has(fboId)) throw new Error('Bazni Downline sadrži neispravan ili dupliciran FBO ID.');
        seen.add(fboId);
        const live = liveCcByFboId.get(fboId);
        if(!live) throw new Error(`Nedostaje live CC potvrda za ${fboId}; uvoz je zaustavljen.`);
        const resolvedCurrent = {
            personalCc: live.personalCc,
            totalCc: live.totalCc,
            totalActiveCc: live.totalActiveCc,
            nonManagerCc: live.nonManagerCc === null
                ? nonNegativeCc(row[indexByHeader.get(metricHeaders.nonManager)], 'bazni NON MANAGER CC')
                : live.nonManagerCc,
            leadershipCc: live.leadershipCc === null
                ? nonNegativeCc(row[indexByHeader.get(metricHeaders.leadership)], 'bazni LEADERSHIP CC')
                : live.leadershipCc,
        };
        const values = {
            fourCc: activeFourCcIds.has(fboId) ? 'Y' : 'N',
            personal: resolvedCurrent.personalCc.toFixed(3),
            total: resolvedCurrent.totalCc.toFixed(3),
            totalActive: resolvedCurrent.totalActiveCc.toFixed(3),
            nonManager: resolvedCurrent.nonManagerCc.toFixed(3),
            leadership: resolvedCurrent.leadershipCc.toFixed(3),
            totalActiveYtd: live.totalActiveCcYtd === null ? row[indexByHeader.get(metricHeaders.totalActiveYtd)] : live.totalActiveCcYtd.toFixed(3),
            nonManagerYtd: live.nonManagerCcYtd === null ? row[indexByHeader.get(metricHeaders.nonManagerYtd)] : live.nonManagerCcYtd.toFixed(3),
            leadershipYtd: live.leadershipCcYtd === null ? row[indexByHeader.get(metricHeaders.leadershipYtd)] : live.leadershipCcYtd.toFixed(3),
        };
        for(const [metric, value] of Object.entries(values)) row[indexByHeader.get(metricHeaders[metric])] = value;
        for(const metric of Object.keys(sums)) sums[metric] += resolvedCurrent[metric];
    }
    if(liveCcByFboId.size !== dataRows.length) throw new Error('Broj live CC zapisa ne odgovara potvrđenoj Downline strukturi.');
    return {
        contents: csvDocument([headers, ...dataRows]),
        summary: {
            rows: dataRows.length,
            liveConfirmed: liveCcByFboId.size,
            activeFourCc: [...activeFourCcIds].filter(id => seen.has(id)).length,
            ...Object.fromEntries(Object.entries(sums).map(([key, value]) => [key, Number(value.toFixed(3))])),
        },
    };
}

async function buildLiveDownline(page, configuration, activeFourCcIds, date = new Date()) {
    const base = await downloadLatestDownlineBase(page, configuration, date);
    const members = extractLiveMemberReferences(base.contents).map(member => ({
        ...member,
        countryCode: resolveLiveCcCountryCode(member.homeCountryCode, configuration),
    }));
    if(members.some(member => !member.countryCode)) {
        throw new Error('Downline matičnu zemlju nije moguće povezati s FLP360 operativnom regijom.');
    }
    const fboIds = members.map(member => member.fboId);
    const lookupCountryByFboId = new Map(extractLiveMemberReferences(base.lookupContents || base.contents)
        .map(member => [member.fboId, member.homeCountryCode]));
    const liveCc = await fetchLiveCcForMembers(page, configuration, members, date);
    const refreshed = refreshDownlineCsv(base.contents, liveCc.records, activeFourCcIds, date);
    const targetPath = path.join(OUTPUT_DIRECTORY, `flp360-downline-live-${zagrebPeriod(date)}.csv`);
    await fs.writeFile(targetPath, refreshed.contents, {mode: 0o600});
    return {
        path: targetPath,
        baseProcessedAt: base.processedAt,
        memberIds: new Set(fboIds),
        liveCcRecords: liveCc.records,
        lookupCountryByFboId,
        fallbackCount: liveCc.fallbackCount,
        operatingMarketFallbackCount: liveCc.operatingMarketFallbackCount,
        countryCounts: liveCc.countryCounts,
        ...refreshed.summary,
    };
}

async function uploadReport(filePath, period, syncUrl, syncKey) {
    const fileContents = await fs.readFile(filePath);
    const form = new FormData();
    form.append('report_period', period);
    form.append('report_file', new Blob([fileContents]), path.basename(filePath));

    const response = await fetch(syncUrl, {
        method: 'POST',
        headers: {'X-FCC-Forever-Sync-Key': syncKey},
        body: form,
    });
    const responseText = await response.text();
    let payload;
    try {
        payload = JSON.parse(responseText);
    } catch {
        throw new Error(`FCC je vratio neispravan odgovor za ${path.basename(filePath)}.`);
    }
    if(!response.ok || payload.status !== 'success') {
        throw new Error(payload?.error?.message || `FCC sinkronizacija nije uspjela za ${path.basename(filePath)}.`);
    }
    return payload;
}

async function uploadFourCoreSnapshot(snapshot, syncUrl, syncKey) {
    const form = new URLSearchParams({metric: 'four_core', report_period: snapshot.period});
    for(const [scope, timeframes] of Object.entries(snapshot.values)) {
        for(const [timeframe, metrics] of Object.entries(timeframes)) {
            for(const [metric, value] of Object.entries(metrics)) {
                form.set(`${scope}_${timeframe}_${metric}`, String(value));
            }
        }
    }

    const response = await fetch(syncUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-FCC-Forever-Sync-Key': syncKey,
        },
        body: form,
    });
    const responseText = await response.text();
    let payload;
    try {
        payload = JSON.parse(responseText);
    } catch {
        throw new Error(`FCC je vratio neispravan odgovor za službeni 4 Core ${snapshot.period}.`);
    }
    if(!response.ok || payload.status !== 'success') {
        throw new Error(payload?.error?.message || `FCC sinkronizacija službenog 4 Core zapisa ${snapshot.period} nije uspjela.`);
    }
    return payload;
}

async function uploadMemberLiveCc(record, isFourCcActive, period, syncUrl, syncKey) {
    const form = new URLSearchParams({
        metric: 'member_cc',
        report_period: period,
        fbo_id: record.fboId,
        personal_cc: String(record.personalCc),
        total_cc: String(record.totalCc),
        total_active_cc: String(record.totalActiveCc),
        is_4cc_active: String(Boolean(isFourCcActive)),
    });
    for(const [key, value] of Object.entries({
        non_manager_cc: record.nonManagerCc,
        leadership_cc: record.leadershipCc,
        total_active_cc_ytd: record.totalActiveCcYtd,
        non_manager_cc_ytd: record.nonManagerCcYtd,
        leadership_cc_ytd: record.leadershipCcYtd,
    })) {
        if(value !== null && value !== undefined) form.set(key, String(value));
    }
    const response = await fetch(syncUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-FCC-Forever-Sync-Key': syncKey,
        },
        body: form,
    });
    const responseText = await response.text();
    let payload;
    try {
        payload = JSON.parse(responseText);
    } catch {
        throw new Error(`FCC je vratio neispravan odgovor za live CC zapis ${record.fboId}.`);
    }
    if(!response.ok || payload.status !== 'success' || payload.metric !== 'member_cc') {
        throw new Error(payload?.error?.message || `FCC sinkronizacija live CC zapisa ${record.fboId} nije uspjela.`);
    }
    return payload;
}

async function uploadRootLiveCc(record, isFourCcActive, period, syncUrl, syncKey) {
    return uploadMemberLiveCc(record, isFourCcActive, period, syncUrl, syncKey);
}

async function uploadGlobalTotalCc(globalTotalCc, period, syncUrl, syncKey) {
    const form = new URLSearchParams({
        metric: 'total_cc',
        report_period: period,
        total_cc: String(globalTotalCc),
        is_closed: 'false',
    });
    const response = await fetch(syncUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-FCC-Forever-Sync-Key': syncKey,
        },
        body: form,
    });
    const responseText = await response.text();
    let payload;
    try {
        payload = JSON.parse(responseText);
    } catch {
        throw new Error('FCC je vratio neispravan odgovor za Global Total CC.');
    }
    if(!response.ok || payload.status !== 'success' || payload.metric !== 'total_cc') {
        throw new Error(payload?.error?.message || 'FCC sinkronizacija Global Total CC zapisa nije uspjela.');
    }
    return payload;
}

async function fetchFccAccounts(period, syncUrl, syncKey) {
    const response = await fetch(syncUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-FCC-Forever-Sync-Key': syncKey,
        },
        body: new URLSearchParams({metric: 'fcc_accounts', report_period: period}),
    });
    const responseText = await response.text();
    let payload;
    try {
        payload = JSON.parse(responseText);
    } catch {
        throw new Error('FCC popis aktivnih Forever ID-jeva nije vratio valjan JSON odgovor.');
    }
    if(!response.ok || payload.status !== 'success' || payload.metric !== 'fcc_accounts') {
        throw new Error(payload?.error?.message || 'FCC popis aktivnih Forever ID-jeva nije dostupan.');
    }
    return payload;
}

async function fetchFccStatus(period, syncUrl, syncKey) {
    const response = await fetch(syncUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-FCC-Forever-Sync-Key': syncKey,
        },
        body: new URLSearchParams({metric: 'status', report_period: period}),
    });
    const responseText = await response.text();
    let payload;
    try {
        payload = JSON.parse(responseText);
    } catch {
        throw new Error('FCC status provjera nije vratila valjan JSON odgovor.');
    }
    if(!response.ok || payload.status !== 'success') throw new Error(payload?.error?.message || 'FCC status provjera nije uspjela.');
    return payload;
}

function verifyFccStatus(payload, expected) {
    const summary = payload?.summary || {};
    const checks = {
        members: Number(summary.members) === expected.members,
        latestCc: Number(summary.personal_active) + Number(summary.zero_cc) === expected.members,
        activeFourCc: Number(summary.active_4cc) === expected.activeFourCc,
        personalCc: Math.abs(Number(summary.personal_cc) - expected.personalCc) < 0.002,
        globalTotalCc: Math.abs(Number(summary.goal_current_cc) - expected.globalTotalCc) < 0.002,
        globalSource: String(summary.goal_metric_source || '').includes('GLOBAL'),
    };
    const failed = Object.entries(checks).filter(([, passed]) => !passed).map(([name]) => name);
    if(failed.length) throw new Error(`FCC završna kontrola nije prošla: ${failed.join(', ')}.`);
    return {
        members: Number(summary.members),
        activeFourCc: Number(summary.active_4cc),
        personalCc: Number(summary.personal_cc),
        globalTotalCc: Number(summary.goal_current_cc),
        lastDataImportAt: payload.last_data_import_at,
    };
}

function verifyFccAccounts(payload, expectedRecords, period, expectedAccountCount = expectedRecords.size) {
    const accounts = Array.isArray(payload?.accounts) ? payload.accounts : [];
    const expectedPeriod = /^\d{4}-\d{2}$/.test(period) ? `${period}-01` : period;
    const actualById = new Map(accounts.map(account => [normalizeFboId(account?.fbo_id), account]));
    const failures = [];

    if(payload?.status !== 'success' || payload?.metric !== 'fcc_accounts') failures.push('response');
    if(accounts.length !== expectedAccountCount || actualById.size !== accounts.length) failures.push('account_count');
    if(Number(payload?.summary?.unique_forever_ids) !== expectedAccountCount) failures.push('summary_count');
    if(Number(payload?.summary?.current_cc_confirmed) < expectedRecords.size) failures.push('current_cc_count');

    for(const [fboId, expected] of expectedRecords) {
        const actual = actualById.get(fboId);
        if(!actual) {
            failures.push(`missing_${fboId}`);
            continue;
        }
        if(String(actual.metric_period || '') !== expectedPeriod) failures.push(`period_${fboId}`);
        if(actual.personal_cc === null || Math.abs(Number(actual.personal_cc) - expected.personalCc) >= 0.002) {
            failures.push(`personal_cc_${fboId}`);
        }
        if(expected.personalCc >= .330 && !actual.is_vip_enrolled) failures.push(`vip_${fboId}`);
    }
    if(failures.length) {
        throw new Error(`FCC završna kontrola registriranih Forever ID-jeva nije prošla: ${failures.slice(0, 8).join(', ')}${failures.length > 8 ? ` (+${failures.length - 8})` : ''}.`);
    }
    return {
        uniqueForeverIds: accounts.length,
        activeAccountLinks: Number(payload?.summary?.active_account_links),
        vipEnrolled: Number(payload?.summary?.vip_enrolled),
        currentCcConfirmed: Number(payload?.summary?.current_cc_confirmed),
    };
}

async function main() {
    const username = requiredEnvironment('FLP360_USERNAME');
    const password = requiredEnvironment('FLP360_PASSWORD');
    const syncUrl = requiredEnvironment('FCC_FOREVER_SYNC_URL');
    const syncKey = requiredEnvironment('FCC_FOREVER_SYNC_KEY');
    const dryRun = String(process.env.FCC_SYNC_DRY_RUN || '').trim() === '1';
    const registeredAccountsOnly = String(process.env.FCC_SYNC_REGISTERED_ONLY || '').trim() === '1';
    const playwrightModule = process.env.PLAYWRIGHT_MODULE_URL || 'playwright';
    const {chromium} = await import(playwrightModule);
    const browser = await chromium.launch({
        headless: true,
        args: ['--disable-gpu'],
        ...(process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE ? {executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE} : {}),
    });
    const context = await browser.newContext({locale: 'en-US'});
    const page = await context.newPage();
    const runDate = syncRunDate(process.env.FLP360_SYNC_PERIOD);
    const period = zagrebPeriod(runDate);

    try {
        await login(page, username, password);
        const configuration = await flpApiConfiguration(page);
        console.log(`FLP360 kontekst: home=${configuration.homeCountryCode}, operating=${configuration.operatingCountryCode}.`);
        const registeredAccounts = prepareRegisteredFccAccounts(
            await fetchFccAccounts(period, syncUrl, syncKey),
            configuration
        );
        console.log(`FCC računi za live CC: ${registeredAccounts.length} jedinstvenih aktivnih Forever ID-jeva.`);

        /* Build and validate every current-period artifact before the first FCC data
         * write. The last complete export remains the hierarchy authority, while CC
         * values are confirmed live for every individual member. */
        const fourCc = await downloadLiveFourCc(page, configuration, runDate);
        console.log(`4 CC Active live: potvrđena ${fourCc.rowCount} retka.`);

        /* Month-end launch reconciliation can safely refresh every active FCC
         * account without replacing the confirmed hierarchy when FLP360's
         * queued Downline export unexpectedly widens its scope. All 630-style
         * account lookups are verified before the first FCC write. */
        if(registeredAccountsOnly) {
            const rootFboId = normalizeFboId(ROOT_FBO_ID);
            const rootIsRegistered = registeredAccounts.some(account => account.fboId === rootFboId);
            const liveTargets = rootIsRegistered
                ? registeredAccounts
                : [{fboId: rootFboId, countryCode: configuration.operatingCountryCode, activeLinkCount: 0}, ...registeredAccounts];
            const liveCc = await fetchLiveCcForMembers(page, configuration, liveTargets, runDate, {allowUnconfirmed: true});
            if(liveCc.unconfirmed.length > 0 || liveCc.records.size !== liveTargets.length) {
                throw new Error(`Registrirani FCC sync nije potvrdio ${liveCc.unconfirmed.length} od ${liveTargets.length} Forever ID-jeva; prije upisa ništa nije promijenjeno.`);
            }

            const rootRecord = liveCc.records.get(rootFboId);
            if(!rootRecord || [
                rootRecord.personalCc,
                rootRecord.totalCc,
                rootRecord.totalActiveCc,
                rootRecord.nonManagerCc,
                rootRecord.leadershipCc,
                rootRecord.totalActiveCcYtd,
                rootRecord.nonManagerCcYtd,
                rootRecord.leadershipCcYtd,
            ].some(value => !Number.isFinite(value))) {
                throw new Error('Glavni FBO nema potpuni potvrđeni live CC zapis za odabrano razdoblje.');
            }

            const ccSummary = await fetchLiveCcSummary(page, configuration, runDate);
            if(Math.abs(ccSummary.totalCc - rootRecord.totalCc) >= 0.002) {
                throw new Error(`FLP360 CC Summary Total CC (${ccSummary.totalCc.toFixed(3)}) ne odgovara glavnom ${configuration.operatingCountryCode} live Total CC-u (${rootRecord.totalCc.toFixed(3)}).`);
            }
            const expectedRecords = new Map(registeredAccounts.map(account => [account.fboId, liveCc.records.get(account.fboId)]));
            if([...expectedRecords.values()].some(record => !record)) {
                throw new Error('Registrirani FCC sync nema pripremljen zapis za svaki aktivni Forever ID.');
            }
            console.log(`Registrirani FCC način: potvrđeno ${expectedRecords.size} računa za ${period}; tržišta ${JSON.stringify(liveCc.countryCounts)}.`);

            if(dryRun) {
                console.log('Kontrolni registrirani FCC način završen je bez upisa.');
                return;
            }

            const fourCcResult = await uploadReport(fourCc.path, period, syncUrl, syncKey);
            console.log(`FCC 4 CC Active: duplicate=${Boolean(fourCcResult.duplicate)}.`);
            await mapWithConcurrency(registeredAccounts, 4, async account => {
                const record = expectedRecords.get(account.fboId);
                return uploadMemberLiveCc(record, fourCc.ids.has(account.fboId), period, syncUrl, syncKey);
            });
            if(!rootIsRegistered) {
                await uploadRootLiveCc(rootRecord, fourCc.ids.has(rootFboId), period, syncUrl, syncKey);
            }
            await uploadGlobalTotalCc(ccSummary.globalTotalCc, period, syncUrl, syncKey);
            for(const snapshot of officialFourCoreSnapshots()) {
                await uploadFourCoreSnapshot(snapshot, syncUrl, syncKey);
            }

            const registeredVerified = verifyFccAccounts(
                await fetchFccAccounts(period, syncUrl, syncKey),
                expectedRecords,
                period,
                registeredAccounts.length
            );
            const status = await fetchFccStatus(period, syncUrl, syncKey);
            console.log(`FCC account provjera: ${registeredVerified.uniqueForeverIds} jedinstvenih Forever ID-jeva (${registeredVerified.activeAccountLinks} aktivnih računa), VIP upis potvrđen za ${registeredVerified.vipEnrolled}.`);
            console.log(`FLP360 → FCC registrirani sync za ${period} završen je uspješno; zadnji FCC podatak ${status.last_data_import_at || status.last_sync_at || 'potvrđen'}.`);
            return;
        }

        const downline = await buildLiveDownline(page, configuration, fourCc.ids, runDate);
        const downlineValidation = await validateDownline(downline.path);
        console.log(`Downline live: ${downlineValidation.rows} redaka, svih ${downline.liveConfirmed} CC zapisa potvrđeno (${downline.fallbackCount} potvrđenih detaljnih odgovora; ${downline.operatingMarketFallbackCount} regionalnih fallbacka; tržišta ${JSON.stringify(downline.countryCounts)}).`);
        const rootLiveCc = await fetchLiveCcForMembers(page, configuration, [{
            fboId: normalizeFboId(ROOT_FBO_ID),
            countryCode: configuration.operatingCountryCode,
        }], runDate);
        const rootRecord = rootLiveCc.records.get(normalizeFboId(ROOT_FBO_ID));
        if(!rootRecord || !Number.isFinite(rootRecord.personalCc)) throw new Error('Glavni FBO nema potvrđen live Personal CC.');
        const registeredOnlyAccounts = registeredAccounts.filter(account =>
            account.fboId !== rootRecord.fboId && !downline.memberIds.has(account.fboId)
        );
        const lookupCountryCandidates = [...new Set([...downline.lookupCountryByFboId.values()]
            .map(countryCode => resolveLiveCcCountryCode(countryCode, configuration))
            .filter(Boolean))];
        const probedRegisteredOnlyAccounts = registeredOnlyAccounts.map(account => ({
            ...account,
            countryCode: downline.lookupCountryByFboId.has(account.fboId)
                ? (resolveLiveCcCountryCode(downline.lookupCountryByFboId.get(account.fboId), configuration) || account.countryCode)
                : account.countryCode,
            countryCandidates: downline.lookupCountryByFboId.has(account.fboId) ? [] : lookupCountryCandidates,
        }));
        const registeredOnlyLiveCc = await fetchLiveCcForMembers(
            page,
            configuration,
            probedRegisteredOnlyAccounts,
            runDate,
            {allowUnconfirmed: true}
        );
        const confirmedRegisteredOnlyAccounts = probedRegisteredOnlyAccounts
            .filter(account => registeredOnlyLiveCc.records.has(account.fboId));
        const unconfirmedRegisteredAccountCount = registeredOnlyLiveCc.unconfirmed.length;
        const unconfirmedAuditPath = path.join(process.cwd(), '.codex-state', 'flp360-unconfirmed-fcc-ids.json');
        await fs.mkdir(path.dirname(unconfirmedAuditPath), {recursive: true, mode: 0o700});
        await fs.writeFile(unconfirmedAuditPath, JSON.stringify({
            period,
            checkedAt: new Date().toISOString(),
            unconfirmed: registeredOnlyLiveCc.unconfirmed,
        }, null, 2), {mode: 0o600});
        await fs.chmod(unconfirmedAuditPath, 0o600);
        const registeredExpectedRecords = new Map();
        for(const account of registeredAccounts) {
            const record = account.fboId === rootRecord.fboId
                ? rootRecord
                : (downline.liveCcRecords.get(account.fboId) || registeredOnlyLiveCc.records.get(account.fboId));
            if(!record) continue;
            registeredExpectedRecords.set(account.fboId, record);
        }
        console.log(`FCC Forever ID-jevi izvan potvrđene hijerarhije: ${registeredOnlyAccounts.length}; FLP360 je potvrdio ${confirmedRegisteredOnlyAccounts.length}, nepotvrđeno je ${unconfirmedRegisteredAccountCount}.`);
        const ccSummary = await fetchLiveCcSummary(page, configuration, runDate);
        if(Math.abs(ccSummary.totalCc - rootRecord.totalCc) >= 0.002) {
            throw new Error(`FLP360 CC Summary Total CC (${ccSummary.totalCc.toFixed(3)}) ne odgovara glavnom ${configuration.operatingCountryCode} live Total CC-u (${rootRecord.totalCc.toFixed(3)}).`);
        }
        console.log(`FLP360 CC Summary: ${configuration.operatingCountryCode} Total CC=${ccSummary.totalCc.toFixed(3)}, Global Total CC=${ccSummary.globalTotalCc.toFixed(3)}.`);

        if(dryRun) {
            console.log(`Kontrolni način rada završen je bez FCC upisa. Provjereni Downline: ${downline.path}.`);
            return;
        }

        const downlineResult = await uploadReport(downline.path, period, syncUrl, syncKey);
        console.log(`FCC Downline: duplicate=${Boolean(downlineResult.duplicate)}.`);
        await uploadRootLiveCc(rootRecord, fourCc.ids.has(rootRecord.fboId), period, syncUrl, syncKey);
        console.log(`FCC glavni FBO live CC: Personal CC=${rootRecord.personalCc.toFixed(3)}.`);
        await uploadGlobalTotalCc(ccSummary.globalTotalCc, period, syncUrl, syncKey);
        console.log(`FCC Global Total CC: ${ccSummary.globalTotalCc.toFixed(3)}.`);
        const fourCcResult = await uploadReport(fourCc.path, period, syncUrl, syncKey);
        console.log(`FCC 4 CC Active: duplicate=${Boolean(fourCcResult.duplicate)}.`);

        await mapWithConcurrency(confirmedRegisteredOnlyAccounts, 4, async account => {
            const record = registeredOnlyLiveCc.records.get(account.fboId);
            if(!record) throw new Error(`Nedostaje pripremljen live CC zapis za FCC Forever ID ${account.fboId}.`);
            return uploadMemberLiveCc(record, fourCc.ids.has(account.fboId), period, syncUrl, syncKey);
        });
        console.log(`FCC zasebni računi: sinkronizirano ${confirmedRegisteredOnlyAccounts.length} Forever ID-jeva izvan potvrđene hijerarhije.`);

        for(const snapshot of officialFourCoreSnapshots()) {
            await uploadFourCoreSnapshot(snapshot, syncUrl, syncKey);
            console.log(`Službeni 4 Core: ${snapshot.period} je potvrđen na FCC-u.`);
        }

        const verified = verifyFccStatus(await fetchFccStatus(period, syncUrl, syncKey), {
            members: downline.rows + 1,
            activeFourCc: fourCc.rowCount,
            personalCc: Number((downline.personalCc + rootRecord.personalCc).toFixed(3)),
            globalTotalCc: ccSummary.globalTotalCc,
        });
        const registeredVerified = verifyFccAccounts(
            await fetchFccAccounts(period, syncUrl, syncKey),
            registeredExpectedRecords,
            period,
            registeredAccounts.length
        );
        await persistConfirmedDownline(downline.path);
        console.log(`FCC provjera: ${verified.members} članova, ${verified.activeFourCc} aktivna 4CC, Personal CC=${verified.personalCc.toFixed(3)}, Global Total CC=${verified.globalTotalCc.toFixed(3)}.`);
        console.log(`FCC account provjera: ${registeredVerified.uniqueForeverIds} jedinstvenih Forever ID-jeva (${registeredVerified.activeAccountLinks} aktivnih računa), VIP upis potvrđen za ${registeredVerified.vipEnrolled}.`);
        console.warn('Focus Group live izvor trenutno vraća prazan skup uz nenulti broj zapisa; zadnji valjani FCC Focus Group namjerno je sačuvan.');
        if(unconfirmedRegisteredAccountCount > 0) {
            throw new Error(`Sinkronizacija je djelomična: ${unconfirmedRegisteredAccountCount} aktivnih valjanih FCC Forever ID-jeva nema potvrđen aktualni FLP360 CC.`);
        }
        console.log(`FLP360 → FCC live sinkronizacija za ${period} završena je uspješno.`);
    } finally {
        await context.close();
        await browser.close();
    }
}

if(process.argv[1] && import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href) {
    main().catch(error => {
        console.error(`Sinkronizacija je zaustavljena: ${error.message}`);
        process.exitCode = 1;
    });
}

export {
    buildDownlineDownloadUrl,
    buildDownlineGenerationUrl,
    buildFourCcCsv,
    csvDocument,
    currentFlpMonthLabel,
    downlineMemberCount,
    encryptFlpAuthorization,
    extractCurrentCcSummary,
    extractFourCcRows,
    extractLiveCcRecord,
    extractLiveMemberReferences,
    extractLiveZeroFallback,
    fetchLiveCcForMembers,
    findCurrentFlpMonthLabel,
    normalizeFboId,
    normalizeCountryCode,
    officialFourCoreSnapshots,
    parseCsvLine,
    parseFlpTimestamp,
    prepareRegisteredFccAccounts,
    readyReportMessage,
    refreshDownlineCsv,
    reportV2Url,
    resolveLiveCcCountryCode,
    resolveFccAccountCountryCode,
    validateDownline,
    validateFourCcRows,
    validateXlsx,
    verifyFccStatus,
    verifyFccAccounts,
    syncRunDate,
    zagrebPeriod,
    zagrebPeriodParts,
};

/* /Custom code: FC-2026-08-13 */
