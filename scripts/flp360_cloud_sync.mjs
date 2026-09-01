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

function normalizePeriod(value) {
    const match = String(value ?? '').trim().match(/^(20\d{2})-(0[1-9]|1[0-2])(?:-01)?$/);
    return match ? `${match[1]}-${match[2]}` : '';
}

function previousPeriod(value) {
    const period = normalizePeriod(value);
    if(!period) return '';
    const [year, month] = period.split('-').map(Number);
    const previous = new Date(Date.UTC(year, month - 2, 1));
    return `${previous.getUTCFullYear()}-${String(previous.getUTCMonth() + 1).padStart(2, '0')}`;
}

function prepareRegisteredFccAccounts(payload, configuration, expectedPeriod) {
    if(payload?.status !== 'success' || payload?.metric !== 'fcc_accounts' || !Array.isArray(payload.accounts)) {
        throw new Error('FCC nije vratio valjan popis aktivnih računa s Forever ID-om.');
    }
    const normalizedExpectedPeriod = normalizePeriod(expectedPeriod);
    if(!normalizedExpectedPeriod || normalizePeriod(payload?.period) !== normalizedExpectedPeriod) {
        throw new Error('FCC popis računa ne pripada traženom izvještajnom razdoblju.');
    }
    const seen = new Set();
    const accounts = payload.accounts.map(account => {
        const fboId = normalizeFboId(account?.fbo_id);
        if(!/^360\d{9}$/.test(fboId) || seen.has(fboId) || Number(account?.active_link_count) < 1) {
            throw new Error('FCC popis računa sadrži neispravan ili dupliciran Forever ID.');
        }
        const isVipEnrolled = normalizedBooleanFlag(account?.is_vip_enrolled);
        if(isVipEnrolled === null) throw new Error('FCC popis računa nema valjan trajni VIP status.');
        seen.add(fboId);
        return {
            fboId,
            countryCode: resolveFccAccountCountryCode(account?.country_code, configuration),
            activeLinkCount: Number(account.active_link_count),
            totalActiveCcYtd: optionalNonNegativeCc(account?.total_active_cc_ytd, 'FCC total_active_cc_ytd'),
            nonManagerCcYtd: optionalNonNegativeCc(account?.non_manager_cc_ytd, 'FCC non_manager_cc_ytd'),
            leadershipCcYtd: optionalNonNegativeCc(account?.leadership_cc_ytd, 'FCC leadership_cc_ytd'),
            isVipEnrolled,
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

const CC_SUMMARY_ROW_SIGNATURE_KEYS = [
    'processingYear', 'processingMonth', 'valueType', 'personalCC', 'nonManagerCC',
    'leadershipCC', 'passthruCC', 'newcc', 'totalCC', 'globalTotalCC',
    'currentMonthActive', 'leadershipQualified',
];

function ccSummaryObjectHasError(value) {
    if(payloadHasExplicitError(value)) return true;
    if(Object.hasOwn(value, 'errors')) {
        const errors = value.errors;
        if(Array.isArray(errors) ? errors.length > 0 : (errors !== null && String(errors).trim() !== '')) return true;
    }
    if(Object.hasOwn(value, 'statusCode')) {
        const statusCode = explicitInteger(value.statusCode);
        if(statusCode === null || statusCode < 100 || statusCode >= 400) return true;
    }
    return false;
}

function ccSummaryPayloadHasExplicitError(value, depth = 0) {
    if(depth > 5) return true;
    if(Array.isArray(value)) {
        return value.some(item => ccSummaryPayloadHasExplicitError(item, depth + 1));
    }
    if(!value || typeof value !== 'object') return false;
    if(ccSummaryObjectHasError(value)) return true;
    return ['body', 'data'].some(key => Object.hasOwn(value, key)
        && ccSummaryPayloadHasExplicitError(value[key], depth + 1));
}

function isUnambiguousCcSummaryErrorEnvelope(payload) {
    let envelope = payload;
    if(Array.isArray(envelope)) {
        if(envelope.length !== 1) return false;
        envelope = envelope[0];
    }
    if(!envelope || typeof envelope !== 'object' || Array.isArray(envelope)) return false;
    if(CC_SUMMARY_ROW_SIGNATURE_KEYS.some(key => Object.hasOwn(envelope, key))) return false;
    if(Object.hasOwn(envelope, 'body') && Object.hasOwn(envelope, 'data')) return false;
    for(const key of ['body', 'data']) {
        if(!Object.hasOwn(envelope, key)) continue;
        const nested = envelope[key];
        if(nested !== null && !(Array.isArray(nested) && nested.length === 0)) return false;
    }

    let hasErrorSignal = false;
    let hasSuccessSignal = false;
    if(Object.hasOwn(envelope, 'statusCode')) {
        const statusCode = explicitInteger(envelope.statusCode);
        if(statusCode === null || statusCode < 100 || statusCode > 599) return false;
        if(statusCode >= 400) hasErrorSignal = true;
        else hasSuccessSignal = true;
    }
    if(Object.hasOwn(envelope, 'status')) {
        if(typeof envelope.status !== 'string') return false;
        const status = envelope.status.trim().toLocaleLowerCase('en');
        if(['error', 'failed', 'failure'].includes(status)) hasErrorSignal = true;
        else if(['ok', 'success', 'succeeded', 'complete', 'completed'].includes(status)) hasSuccessSignal = true;
        else return false;
    }
    if(Object.hasOwn(envelope, 'success')) {
        if(typeof envelope.success !== 'boolean') return false;
        if(envelope.success === false) hasErrorSignal = true;
        else hasSuccessSignal = true;
    }
    if(Object.hasOwn(envelope, 'error')) {
        const errorValue = envelope.error;
        if(errorValue === true || (typeof errorValue === 'string' && errorValue.trim() !== '')) {
            hasErrorSignal = true;
        } else if(errorValue === false || errorValue === null || errorValue === '') {
            hasSuccessSignal = true;
        } else if(errorValue && typeof errorValue === 'object' && !Array.isArray(errorValue)
            && Object.keys(errorValue).length > 0) {
            hasErrorSignal = true;
        } else {
            return false;
        }
    }
    if(Object.hasOwn(envelope, 'errors')) {
        const errors = envelope.errors;
        if(Array.isArray(errors)) {
            if(errors.length > 0) hasErrorSignal = true;
            else hasSuccessSignal = true;
        } else if(typeof errors === 'string') {
            if(errors.trim() !== '') hasErrorSignal = true;
            else hasSuccessSignal = true;
        } else if(errors === null) {
            hasSuccessSignal = true;
        } else {
            return false;
        }
    }
    return hasErrorSignal && !hasSuccessSignal;
}

function ccSummaryPayloadShape(value, depth = 0) {
    if(value === null) return {type: 'null'};
    if(Array.isArray(value)) {
        const baseIndexes = value.length <= 4
            ? value.map((_, index) => index)
            : [0, 1, value.length - 1];
        const firstErrorIndex = value.findIndex(item => ccSummaryPayloadHasExplicitError(item));
        const indexes = [...new Set([
            ...baseIndexes,
            ...(firstErrorIndex >= 0 ? [firstErrorIndex] : []),
        ])].sort((left, right) => left - right);
        return {
            type: 'array',
            length: value.length,
            ...(depth < 3 ? {items: indexes.map(index => ({index, shape: ccSummaryPayloadShape(value[index], depth + 1)}))} : {}),
        };
    }
    if(typeof value !== 'object') return {type: typeof value};

    const knownKeys = [
        'statusCode', 'status', 'success', 'error', 'errors', 'body', 'data',
        ...CC_SUMMARY_ROW_SIGNATURE_KEYS,
    ].filter(key => Object.hasOwn(value, key));
    const shape = {
        type: 'object',
        keyCount: Object.keys(value).length,
        knownKeys,
    };
    if(Object.hasOwn(value, 'statusCode')) {
        const statusCode = explicitInteger(value.statusCode);
        shape.statusCode = statusCode === null || statusCode < 100 || statusCode > 599
            ? `invalid:${typeof value.statusCode}`
            : `${Math.floor(statusCode / 100)}xx`;
    }
    if(Object.hasOwn(value, 'status')) {
        const normalizedStatus = typeof value.status === 'string'
            ? value.status.trim().toLocaleLowerCase('en')
            : '';
        shape.status = ['ok', 'success', 'succeeded', 'complete', 'completed', 'error', 'failed', 'failure']
            .includes(normalizedStatus) ? normalizedStatus : `invalid:${typeof value.status}`;
    }
    if(Object.hasOwn(value, 'success')) {
        shape.success = typeof value.success === 'boolean' ? value.success : `invalid:${typeof value.success}`;
    }
    for(const key of ['error', 'errors']) {
        if(!Object.hasOwn(value, key)) continue;
        const field = value[key];
        shape[key] = Array.isArray(field)
            ? {type: 'array', length: field.length}
            : field === null
                ? {type: 'null'}
                : {type: typeof field, nonEmpty: typeof field === 'string' ? field.trim() !== '' : Boolean(field)};
    }
    if(depth < 3) {
        for(const key of ['body', 'data']) {
            if(Object.hasOwn(value, key)) shape[key] = ccSummaryPayloadShape(value[key], depth + 1);
        }
    }
    return shape;
}

function ccSummaryValidationError(reasonCode, message) {
    const error = new Error(message);
    error.ccSummaryReasonCode = reasonCode;
    return error;
}

function isVerifiedRootCurrentZero(record) {
    return record?.usedVerifiedCurrentZero === true
        && ['personalCc', 'totalCc', 'totalActiveCc', 'nonManagerCc', 'leadershipCc']
            .every(field => Number.isFinite(record[field]) && record[field] === 0);
}

function isValidCcSummaryRow(row) {
    if(!row || typeof row !== 'object' || Array.isArray(row) || ccSummaryObjectHasError(row)) return false;
    if(Object.hasOwn(row, 'body') || Object.hasOwn(row, 'data')) return false;
    const year = explicitInteger(row.processingYear);
    const month = explicitInteger(row.processingMonth);
    const valueType = typeof row.valueType === 'string'
        ? row.valueType.trim().toLocaleLowerCase('en')
        : '';
    if(year === null || year < 2000 || year > 2100) return false;
    if(valueType === 'monthly') return month !== null && month >= 1 && month <= 12;
    return valueType === 'yearly' && month === 0;
}

function extractCcSummaryRows(payload) {
    let rows;
    if(Array.isArray(payload)) {
        const wrapped = payload.length === 1
            && payload[0]
            && typeof payload[0] === 'object'
            && Array.isArray(payload[0].body);
        if(wrapped) {
            const envelope = payload[0];
            if(Object.hasOwn(envelope, 'data')
                || CC_SUMMARY_ROW_SIGNATURE_KEYS.some(key => Object.hasOwn(envelope, key))
                || ccSummaryObjectHasError(envelope)) return null;
            rows = envelope.body;
        } else {
            if(payload.some(row => row && typeof row === 'object'
                && (Object.hasOwn(row, 'body') || Object.hasOwn(row, 'data')))) return null;
            rows = payload;
        }
    } else if(payload && typeof payload === 'object' && Array.isArray(payload.body)) {
        if(Object.hasOwn(payload, 'data')
            || CC_SUMMARY_ROW_SIGNATURE_KEYS.some(key => Object.hasOwn(payload, key))
            || ccSummaryObjectHasError(payload)) return null;
        rows = payload.body;
    } else {
        return null;
    }
    if(rows.some(row => !isValidCcSummaryRow(row))) return null;
    return rows;
}

function extractCurrentCcSummary(payload, date = new Date(), options = {}) {
    const {year, month} = zagrebPeriodParts(date);
    if(isUnambiguousCcSummaryErrorEnvelope(payload)) {
        throw ccSummaryValidationError('summary_upstream_error', 'FLP360 CC Summary vratio je poruku o pogrešci.');
    }
    if(ccSummaryPayloadHasExplicitError(payload)) {
        throw new Error(`FLP360 CC Summary odgovor s pogreškom nije jednoznačan. Sanitizirana struktura: ${JSON.stringify(ccSummaryPayloadShape(payload))}`);
    }
    const rows = extractCcSummaryRows(payload);
    if(!Array.isArray(rows) || !rows.length) {
        throw new Error(payloadHasExplicitError(payload)
            ? 'FLP360 CC Summary vratio je poruku o pogrešci.'
            : 'FLP360 CC Summary live odgovor nije jednoznačan ili je prazan.');
    }
    const currentRows = rows.filter(row =>
        explicitInteger(row?.processingYear) === year
        && explicitInteger(row?.processingMonth) === month
        && row.valueType.trim().toLocaleLowerCase('en') === 'monthly'
    );
    if(currentRows.length !== 1) {
        throw new Error(currentRows.length > 1
            ? `FLP360 CC Summary ima duplicirano aktualno razdoblje ${zagrebPeriod(date)}.`
            : `FLP360 CC Summary nema aktualno razdoblje ${zagrebPeriod(date)}.`);
    }
    const current = currentRows[0];
    const localTotalUnavailable = Object.hasOwn(current, 'totalCC') && current.totalCC === null;
    let totalCc;
    if(localTotalUnavailable) {
        const currentDate = options.currentDate instanceof Date ? options.currentDate : new Date();
        if(zagrebPeriod(date) !== zagrebPeriod(currentDate) || !isVerifiedRootCurrentZero(options.rootRecord)) {
            throw ccSummaryValidationError(
                'summary_local_total_unavailable',
                'FLP360 CC Summary localni Total CC nije dostupan niti ga je moguće potvrditi neovisnim root zapisom.'
            );
        }
        totalCc = options.rootRecord.totalCc;
    } else {
        totalCc = nonNegativeCc(current.totalCC, 'CC Summary totalCC');
    }

    const globalTotalUnavailable = Object.hasOwn(current, 'globalTotalCC') && current.globalTotalCC === null;
    if(globalTotalUnavailable && !localTotalUnavailable) {
        throw ccSummaryValidationError(
            'summary_global_total_unavailable',
            'FLP360 CC Summary Global Total CC nije dostupan.'
        );
    }
    const globalTotalCc = globalTotalUnavailable
        ? null
        : nonNegativeCc(current.globalTotalCC, 'CC Summary globalTotalCC');
    return {
        totalCc,
        globalTotalCc,
        ...(localTotalUnavailable ? {usedRootCorroboratedLocalTotal: true} : {}),
        ...(globalTotalUnavailable ? {globalTotalUnavailable: true} : {}),
    };
}

function validateFourCcRows(rows, date = new Date(), options = {}) {
    const {year, month} = zagrebPeriodParts(date);
    if(!Array.isArray(rows)) throw new Error('4 CC Active live odgovor nije valjan niz.');
    if(!rows.length) {
        if(options.allowEmpty) return {rows: 0, ids: new Set()};
        throw new Error('4 CC Active live odgovor je prazan; postojeći FCC podaci ostaju sačuvani.');
    }
    const ids = rows.map(row => normalizeFboId(row?.fboID));
    if(ids.some(id => !id) || new Set(ids).size !== ids.length) throw new Error('4 CC Active sadrži neispravne ili duplicirane FBO ID-eve.');
    if(rows.some(row => explicitInteger(row?.processingYear) !== year || explicitInteger(row?.processingMonth) !== month)) {
        throw new Error(`4 CC Active nije za aktualno razdoblje ${zagrebPeriod(date)}.`);
    }
    for(const row of rows) {
        for(const field of ['personalCC', 'totalActiveCC']) {
            nonNegativeCc(row?.[field], `4 CC Active ${field}`);
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
    const payload = await flpGetJson(page, requestUrl, configuration);
    if(payloadHasExplicitError(payload)) {
        throw new Error('4 CC Active vratio je poruku o pogrešci; postojeći FCC podaci ostaju sačuvani.');
    }
    const hasVerifiedArrayEnvelope = Array.isArray(payload)
        || Array.isArray(payload?.body)
        || Array.isArray(payload?.[0]?.body);
    if(!hasVerifiedArrayEnvelope) {
        throw new Error('4 CC Active nije vratio provjerljiv JSON skup; postojeći FCC podaci ostaju sačuvani.');
    }
    const rows = extractFourCcRows(payload);
    /* A structurally valid empty array at the start of a new month means zero
     * officially active 4 CC accounts. Network/null/error responses still fail
     * closed above and can never clear an existing signal. */
    const validation = validateFourCcRows(rows, date, {allowEmpty: true});
    const targetPath = path.join(OUTPUT_DIRECTORY, `flp360-4cc-active-live-${zagrebPeriod(date)}.csv`);
    await fs.writeFile(targetPath, buildFourCcCsv(rows, date), {mode: 0o600});
    return {path: targetPath, records: rows, rowCount: validation.rows, ids: validation.ids};
}

async function fetchLiveCcSummary(page, configuration, date = new Date(), rootRecord = null, options = {}) {
    const {year, month} = zagrebPeriodParts(date);
    const distributorId = normalizeFboId(ROOT_FBO_ID);
    const requestSummary = async requestedMonth => {
        const requestUrl = new URL(reportV2Url(
            configuration,
            `fboId/${distributorId}/country/${encodeURIComponent(configuration.operatingCountryCode)}/year/${year}/month/${requestedMonth}/rewire-earnings-CC-summary`
        ));
        requestUrl.searchParams.set('isVolume', 'true');
        requestUrl.searchParams.set('comparisionYear', String(year - 1));
        return flpGetJson(page, requestUrl.toString(), configuration);
    };
    const parseOptions = {
        rootRecord,
        currentDate: options.currentDate instanceof Date ? options.currentDate : new Date(),
    };
    /* B.4711 normally uses month/1 as its annual-series endpoint. This account
     * can return an explicit upstream error there during rollover, while the
     * current-month form remains a structurally valid annual series. */
    let annualPayload;
    try {
        annualPayload = await requestSummary(1);
        return extractCurrentCcSummary(annualPayload, date, parseOptions);
    } catch(error) {
        if(month === 1 || (annualPayload !== undefined && error?.ccSummaryReasonCode !== 'summary_upstream_error')) {
            throw error;
        }
        console.warn(`FLP360 službeni CC Summary annual endpoint nije dostupan; provjerava se aktualni month/${month} oblik.`);
    }
    return extractCurrentCcSummary(await requestSummary(month), date, parseOptions);
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
    if(value === null || value === undefined || typeof value === 'boolean'
        || (typeof value !== 'number' && typeof value !== 'string')
        || (typeof value === 'string' && value.trim() === '')) {
        throw new Error(`FLP360 live CC nema valjano polje ${fieldName}.`);
    }
    const number = Number(value);
    if(!Number.isFinite(number) || number < 0) throw new Error(`FLP360 live CC nema valjano polje ${fieldName}.`);
    return number;
}

function explicitInteger(value) {
    if(value === null || value === undefined || typeof value === 'boolean'
        || (typeof value !== 'number' && typeof value !== 'string')
        || (typeof value === 'string' && value.trim() === '')) return null;
    const number = Number(value);
    return Number.isInteger(number) ? number : null;
}

function isExplicitZero(value) {
    return explicitInteger(value) === 0;
}

function normalizedBooleanFlag(value) {
    if(value === true || value === 1 || value === '1') return true;
    if(value === false || value === 0 || value === '0') return false;
    return null;
}

function optionalNonNegativeCc(value, fieldName) {
    return value === null || value === undefined || String(value).trim() === ''
        ? null
        : nonNegativeCc(value, fieldName);
}

function optionalOwnNonNegativeCc(record, key, fieldName = key) {
    if(!record || !Object.hasOwn(record, key) || record[key] === null || record[key] === undefined) return null;
    return nonNegativeCc(record[key], fieldName);
}

function ccValuesDiffer(left, right) {
    const invalidComparable = value => value === null
        || value === undefined
        || typeof value === 'boolean'
        || (typeof value !== 'number' && typeof value !== 'string')
        || (typeof value === 'string' && value.trim() === '');
    if(invalidComparable(left) || invalidComparable(right)) return true;
    const leftNumber = Number(left);
    const rightNumber = Number(right);
    if(!Number.isFinite(leftNumber) || leftNumber < 0
        || !Number.isFinite(rightNumber) || rightNumber < 0) return true;
    /* FLP360 and FCC store CC to three decimals. Compare integer thousandths
     * so an exact 0.002 boundary cannot slip through binary floating point. */
    return Math.abs(Math.round(leftNumber * 1000) - Math.round(rightNumber * 1000)) >= 2;
}

function payloadHasExplicitError(payload) {
    const envelope = Array.isArray(payload) ? payload[0] : payload;
    if(!envelope || typeof envelope !== 'object') return false;
    const status = String(envelope.status || '').trim().toLocaleLowerCase('en');
    return Boolean(envelope.error)
        || (Array.isArray(envelope.errors) && envelope.errors.length > 0)
        || envelope.success === false
        || status === 'error'
        || status === 'failed'
        || status === 'failure';
}

function exactSingleEnvelopeRecord(payload) {
    let candidate = payload;
    /* FLP360 normally returns a single object, but its gateways can wrap that
     * object in one-record arrays and data/body envelopes. Unwrap only when
     * every layer is unambiguous; empty, multi-record, dual-envelope and error
     * payloads remain rejected. The depth bound also fails closed on malformed
     * recursive objects that cannot occur in JSON. */
    for(let depth = 0; depth < 6; depth++) {
        if(payloadHasExplicitError(candidate)) return null;
        if(Array.isArray(candidate)) {
            if(candidate.length !== 1) return null;
            candidate = candidate[0];
            continue;
        }
        if(!candidate || typeof candidate !== 'object') return null;
        const hasDataEnvelope = Object.hasOwn(candidate, 'data');
        const hasBodyEnvelope = Object.hasOwn(candidate, 'body');
        if(hasDataEnvelope && hasBodyEnvelope) return null;
        if(!hasDataEnvelope && !hasBodyEnvelope) return candidate;
        if([
            'fboId', 'fboID', 'fbo_id', 'foreverFboId',
            'distributorId', 'distributorID', 'distributor_id', 'processingYear', 'monthlyCCValues',
            'personalCCCurMonth', 'totalCCCurMonth', 'totalActiveCCCurMonth',
        ].some(key => Object.hasOwn(candidate, key))) return null;
        candidate = hasDataEnvelope ? candidate.data : candidate.body;
    }
    return null;
}

function liveCcValidationError(reasonCode, message) {
    const error = new Error(message);
    error.liveCcReasonCode = reasonCode;
    return error;
}

function liveCcEmptyTreeSentinel(payload) {
    const tree = exactSingleEnvelopeRecord(payload);
    const monthlyValues = Array.isArray(tree?.monthlyCCValues) ? tree.monthlyCCValues : [];
    return tree
        && typeof tree === 'object'
        && !normalizeFboId(tree.fboId)
        && isExplicitZero(tree.processingYear)
        && (!Object.hasOwn(tree, 'processingMonth') || isExplicitZero(tree.processingMonth))
        && monthlyValues.length > 0
        && monthlyValues.every(value => value
            && typeof value === 'object'
            && isExplicitZero(value.processingYear)
            && isExplicitZero(value.processingMonth))
        ? tree
        : null;
}

function liveCcPriorOnlyTreeRecord(payload, expectedFboId, date = new Date(), currentDate = new Date()) {
    if(zagrebPeriod(date) !== zagrebPeriod(currentDate)) return null;
    const tree = exactSingleEnvelopeRecord(payload);
    if(!tree) return null;
    const normalizedExpectedFboId = normalizeFboId(expectedFboId);
    if(!normalizedExpectedFboId || normalizeFboId(tree?.fboId) !== normalizedExpectedFboId) return null;

    const requiredPriorPeriod = previousPeriod(zagrebPeriod(date));
    const targetPeriod = zagrebPeriod(date);
    const monthlyValues = Array.isArray(tree?.monthlyCCValues) ? tree.monthlyCCValues : [];
    const periods = monthlyValues.map(value => {
        if(!value || typeof value !== 'object') return '';
        const year = explicitInteger(value.processingYear);
        const month = explicitInteger(value.processingMonth);
        return Number.isInteger(year) && year >= 2000 && Number.isInteger(month) && month >= 1 && month <= 12
            ? `${year}-${String(month).padStart(2, '0')}`
            : '';
    });
    if(!periods.length || periods.some(period => !period || period >= targetPeriod)) return null;
    return periods.sort().at(-1) === requiredPriorPeriod ? tree : null;
}

function extractDetailCurrentCc(detail) {
    const fields = ['personalCCCurMonth', 'totalCCCurMonth', 'totalActiveCCCurMonth'];
    if(fields.some(field => !Object.hasOwn(detail, field))) {
        throw liveCcValidationError(
            'detail_current_cc_missing',
            'FLP360 rezervna live potvrda nema sva tri current-month CC polja.'
        );
    }
    const nullFields = fields.filter(field => detail[field] === null);
    if(nullFields.length === fields.length) {
        /* At a new-month rollover FLP360 returns literal null for all three
         * current-month fields until the first order. Its own B.4711 Downline
         * UI renders that exact monthly triad as 0.000. Accept it only as an
         * all-or-nothing sentinel; partial nulls remain inconsistent and fail. */
        return {personalCc: 0, totalCc: 0, totalActiveCc: 0, usedNullCurrentSentinel: true};
    }
    if(nullFields.length > 0) {
        throw liveCcValidationError(
            'detail_current_cc_mixed_null',
            'FLP360 rezervna live potvrda vratila je mješovita current-month CC polja.'
        );
    }
    return {
        personalCc: nonNegativeCc(detail.personalCCCurMonth, 'personalCCCurMonth'),
        totalCc: nonNegativeCc(detail.totalCCCurMonth, 'totalCCCurMonth'),
        totalActiveCc: nonNegativeCc(detail.totalActiveCCCurMonth, 'totalActiveCCCurMonth'),
        usedNullCurrentSentinel: false,
    };
}

function classifyLiveCcFailure(message) {
    const explicitCode = String(message?.liveCcReasonCode || '').trim();
    if(/^[a-z_]+$/.test(explicitCode)) return explicitCode;
    const text = String(message?.message || message || '').toLocaleLowerCase('hr');
    if(text.includes('podatkovni poziv') || text.includes('http ')) return 'request_failed';
    if(text.includes('poruku o pogrešci') || (text.includes('status') && text.includes('error'))) return 'upstream_error';
    if(text.includes('fbo id') || text.includes('identitet') || text.includes('nije sigurna')) return 'identity_unconfirmed';
    if(text.includes('razdoblje') || text.includes('prethodn')) return 'period_unconfirmed';
    if(text.includes('valjano polje') || text.includes('live cc nema')) return 'metric_invalid';
    return 'invalid_response';
}

function extractLiveCcRecord(payload, expectedFboId, date = new Date()) {
    if(payloadHasExplicitError(payload)) {
        throw liveCcValidationError('tree_upstream_error', `FLP360 live CC vratio je poruku o pogrešci za ${expectedFboId}.`);
    }
    const {year, month} = zagrebPeriodParts(date);
    const record = exactSingleEnvelopeRecord(payload);
    if(!record) {
        throw liveCcValidationError('tree_record_unconfirmed', `FLP360 live CC nije potvrdio FBO ID ${expectedFboId}.`);
    }
    if(!normalizeFboId(record.fboId)) {
        throw liveCcValidationError('tree_identity_missing', `FLP360 live CC nije potvrdio FBO ID ${expectedFboId}.`);
    }
    if(normalizeFboId(record.fboId) !== normalizeFboId(expectedFboId)) {
        throw liveCcValidationError('tree_identity_mismatch', `FLP360 live CC nije potvrdio FBO ID ${expectedFboId}.`);
    }
    const monthlyValues = Array.isArray(record.monthlyCCValues) ? record.monthlyCCValues : [];
    const current = monthlyValues.find(value => explicitInteger(value?.processingYear) === year && explicitInteger(value?.processingMonth) === month);
    const confirmedZero = !current
        && isExplicitZero(record.processingYear)
        && (!Object.hasOwn(record, 'processingMonth') || isExplicitZero(record.processingMonth))
        && monthlyValues.length > 0
        && monthlyValues.every(value => isExplicitZero(value?.processingYear) && isExplicitZero(value?.processingMonth));
    if(!current && !confirmedZero) {
        throw liveCcValidationError('tree_period_unconfirmed', `FLP360 live CC za ${expectedFboId} nema potvrđeno razdoblje ${zagrebPeriod(date)}.`);
    }
    const source = current || {};
    return {
        fboId: normalizeFboId(expectedFboId),
        personalCc: confirmedZero ? 0 : nonNegativeCc(source.personalCCMTD, 'personalCCMTD'),
        totalCc: confirmedZero ? 0 : nonNegativeCc(source.totalCCMTD, 'totalCCMTD'),
        totalActiveCc: confirmedZero ? 0 : nonNegativeCc(source.totalActiveCCMTD, 'totalActiveCCMTD'),
        nonManagerCc: confirmedZero ? 0 : nonNegativeCc(source.nonManagerCCMTD, 'nonManagerCCMTD'),
        leadershipCc: confirmedZero ? 0 : nonNegativeCc(source.leaderCC, 'leaderCC'),
        /* A new-month sentinel proves only that monthly values are zero. FLP360
         * can still return valid cumulative values on the parent record. Null
         * preserves the last verified YTD value when a cumulative field is not
         * present; it must never be replaced with an invented zero. */
        totalActiveCcYtd: confirmedZero
            ? optionalNonNegativeCc(record.totalActiveCC, 'totalActiveCC')
            : nonNegativeCc(record.totalActiveCC, 'totalActiveCC'),
        nonManagerCcYtd: confirmedZero
            ? optionalNonNegativeCc(record.nonManagerCC, 'nonManagerCC')
            : nonNegativeCc(record.nonManagerCC, 'nonManagerCC'),
        leadershipCcYtd: confirmedZero
            ? optionalNonNegativeCc(record.leaderCC, 'leaderCC')
            : nonNegativeCc(record.leaderCC, 'leaderCC'),
    };
}

function extractLiveZeroFallback(treePayload, detailPayload, expectedFboId, date = new Date(), currentDate = new Date()) {
    const tree = liveCcEmptyTreeSentinel(treePayload)
        || liveCcPriorOnlyTreeRecord(treePayload, expectedFboId, date, currentDate);
    const detail = exactSingleEnvelopeRecord(detailPayload);
    /* downlineLoggedInDetails exposes current-month fields only. It is valid
     * solely when the requested Zagreb period is also the real current period;
     * a historical reconciliation must fail closed instead of misdating data. */
    if(zagrebPeriod(date) !== zagrebPeriod(currentDate)) {
        throw liveCcValidationError('fallback_historical_period', `FLP360 rezervna live potvrda nije dostupna za povijesno razdoblje ${zagrebPeriod(date)}.`);
    }
    if(!tree) {
        throw liveCcValidationError('fallback_tree_unconfirmed', `FLP360 rezervna live potvrda nije sigurna za ${expectedFboId}.`);
    }
    if(payloadHasExplicitError(detailPayload)) {
        throw liveCcValidationError('detail_upstream_error', `FLP360 rezervna live potvrda nije sigurna za ${expectedFboId}.`);
    }
    if(!detail || typeof detail !== 'object') {
        throw liveCcValidationError('detail_record_unconfirmed', `FLP360 rezervna live potvrda nije sigurna za ${expectedFboId}.`);
    }
    if(!normalizeFboId(detail?.distributorId)) {
        throw liveCcValidationError('detail_identity_missing', `FLP360 rezervna live potvrda nije sigurna za ${expectedFboId}.`);
    }
    if(normalizeFboId(detail?.distributorId) !== normalizeFboId(expectedFboId)) {
        throw liveCcValidationError('detail_identity_mismatch', `FLP360 rezervna live potvrda nije sigurna za ${expectedFboId}.`);
    }
    const currentCc = extractDetailCurrentCc(detail);
    const {year} = zagrebPeriodParts(date);
    const treeHasCurrentYearYtd = explicitInteger(tree.processingYear) === year;
    const detailTotalActiveYtd = optionalOwnNonNegativeCc(detail, 'totalActiveCCYTD');
    const treeTotalActiveYtd = treeHasCurrentYearYtd
        ? optionalOwnNonNegativeCc(tree, 'totalActiveCC')
        : null;
    const nonManagerYtd = treeHasCurrentYearYtd
        ? optionalOwnNonNegativeCc(tree, 'nonManagerCC')
        : null;
    const leadershipYtd = treeHasCurrentYearYtd
        ? optionalOwnNonNegativeCc(tree, 'leaderCC')
        : null;
    const nonManagerCurrent = optionalOwnNonNegativeCc(detail, 'nonManagerCCCurMonth');
    const leaderCurrent = optionalOwnNonNegativeCc(detail, 'leaderCCCurMonth');
    const leadershipCurrentAlias = optionalOwnNonNegativeCc(detail, 'leadershipCCCurMonth');
    if(leaderCurrent !== null && leadershipCurrentAlias !== null
        && ccValuesDiffer(leaderCurrent, leadershipCurrentAlias)) {
        throw liveCcValidationError(
            'detail_current_cc_submetric_conflict',
            'FLP360 rezervna live potvrda vratila je proturječne Leadership CC vrijednosti.'
        );
    }
    const leadershipCurrent = leaderCurrent ?? leadershipCurrentAlias;
    const zeroCurrentCore = [currentCc.personalCc, currentCc.totalCc, currentCc.totalActiveCc]
        .every(value => value === 0);
    if(zeroCurrentCore
        && [nonManagerCurrent, leaderCurrent, leadershipCurrentAlias]
            .some(value => value !== null && value !== 0)) {
        throw liveCcValidationError(
            'detail_current_cc_submetric_conflict',
            'FLP360 rezervna live potvrda ima manager CC različit od nule uz nulti current-month total.'
        );
    }
    if(zeroCurrentCore
        && treeTotalActiveYtd !== null
        && detailTotalActiveYtd !== null
        && ccValuesDiffer(treeTotalActiveYtd, detailTotalActiveYtd)) {
        throw liveCcValidationError(
            'detail_tree_ytd_mismatch',
            'FLP360 rezervna live potvrda ima proturječan Total Active CC YTD.'
        );
    }
    const totalActiveCcYtd = zeroCurrentCore
        ? (treeTotalActiveYtd ?? detailTotalActiveYtd)
        : (detailTotalActiveYtd ?? treeTotalActiveYtd);
    return {
        fboId: normalizeFboId(expectedFboId),
        personalCc: currentCc.personalCc,
        totalCc: currentCc.totalCc,
        totalActiveCc: currentCc.totalActiveCc,
        /* The detail fallback does not consistently expose these manager-only
         * fields. A verified all-zero core triad proves there is no current-month
         * CC, so its nonnegative manager submetrics are also zero. Otherwise null
         * preserves the last verified value instead of inventing missing data. */
        nonManagerCc: zeroCurrentCore
            ? 0
            : nonManagerCurrent,
        leadershipCc: zeroCurrentCore
            ? 0
            : leadershipCurrent,
        /* The exact-ID prior-only tree still carries the current year's
         * cumulative parent metrics. Reuse only those YTD fields; never reuse
         * its previous-month MTD row as current-month data. */
        totalActiveCcYtd,
        nonManagerCcYtd: nonManagerYtd,
        leadershipCcYtd: leadershipYtd,
        usedFallback: true,
        usedNullCurrentSentinel: currentCc.usedNullCurrentSentinel,
        usedVerifiedCurrentZero: zeroCurrentCore,
    };
}

function applyRegisteredAccountSafetyFloor(record, account) {
    const safeRecord = {...record};
    let ytdFloorFields = 0;
    for(const field of ['totalActiveCcYtd', 'nonManagerCcYtd', 'leadershipCcYtd']) {
        const stored = optionalNonNegativeCc(account?.[field], `FCC ${field}`);
        const live = optionalNonNegativeCc(safeRecord[field], `FLP360 ${field}`);
        if(stored !== null && (live === null || live < stored)) {
            safeRecord[field] = stored;
            ytdFloorFields++;
        } else {
            safeRecord[field] = live;
        }
    }
    safeRecord.mustRemainVipEnrolled = account?.isVipEnrolled === true;
    return {record: safeRecord, ytdFloorFields};
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
    const currentDate = options.currentDate instanceof Date ? options.currentDate : new Date();
    let completed = 0;
    let fallbackCount = 0;
    let nullCurrentMonthCount = 0;
    let zeroCurrentMonthCount = 0;
    let ytdFloorAccountCount = 0;
    let ytdFloorFieldCount = 0;
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
        const candidateReasonCodes = [];
        for(const countryCode of countryCandidates) {
            try {
                const requestUrl = reportV2Url(configuration, `distributors/${fboId}/treeview-cc?countryCode=${encodeURIComponent(countryCode)}`);
                const treePayload = await flpGetJson(page, requestUrl, configuration);
                if(liveCcEmptyTreeSentinel(treePayload)
                    || liveCcPriorOnlyTreeRecord(treePayload, fboId, date, currentDate)) {
                    const detailUrl = reportV2Url(configuration, `downlineLoggedInDetails/fboId/${fboId}/country/${encodeURIComponent(countryCode)}`);
                    const detailPayload = await flpGetJson(page, detailUrl, configuration);
                    record = extractLiveZeroFallback(treePayload, detailPayload, fboId, date, currentDate);
                    usedDetailFallback = true;
                } else {
                    record = extractLiveCcRecord(treePayload, fboId, date);
                }
                confirmedCountryCode = countryCode;
                break;
            } catch(error) {
                candidateErrors.push(`${countryCode}: ${error.message}`);
                candidateReasonCodes.push(classifyLiveCcFailure(error));
            }
        }
        if(!record || !confirmedCountryCode) {
            if(options.allowUnconfirmed) {
                unconfirmed.push({fboId, candidateErrors, reasonCodes: [...new Set(candidateReasonCodes)]});
                completed++;
                if(completed % 100 === 0 || completed === members.length) console.log(`Live CC provjeren: ${completed}/${members.length}.`);
                return null;
            }
            throw new Error(`FLP360 nije potvrdio live CC za ${fboId} ni u jednom dopuštenom tržištu (${candidateErrors.join(' | ')}).`);
        }
        const safetyFloor = applyRegisteredAccountSafetyFloor(record, member);
        record = safetyFloor.record;
        if(safetyFloor.ytdFloorFields > 0) {
            ytdFloorAccountCount++;
            ytdFloorFieldCount += safetyFloor.ytdFloorFields;
        }
        if(usedDetailFallback) fallbackCount++;
        if(record.usedNullCurrentSentinel === true) nullCurrentMonthCount++;
        if(record.usedVerifiedCurrentZero === true) zeroCurrentMonthCount++;
        if(confirmedCountryCode !== preferredCountryCode) {
            operatingMarketFallbackCount++;
        }
        countryCounts.set(confirmedCountryCode, (countryCounts.get(confirmedCountryCode) || 0) + 1);
        completed++;
        if(completed % 100 === 0 || completed === members.length) console.log(`Live CC potvrđen: ${completed}/${members.length}.`);
        return record;
    });
    const confirmedRecords = records.filter(Boolean);
    const unconfirmedReasonCounts = {};
    for(const entry of unconfirmed) {
        for(const reasonCode of entry.reasonCodes) {
            unconfirmedReasonCounts[reasonCode] = (unconfirmedReasonCounts[reasonCode] || 0) + 1;
        }
    }
    return {
        records: new Map(confirmedRecords.map(record => [record.fboId, record])),
        unconfirmed,
        fallbackCount,
        nullCurrentMonthCount,
        zeroCurrentMonthCount,
        ytdFloorAccountCount,
        ytdFloorFieldCount,
        unconfirmedReasonCounts,
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
        personalCc: !ccValuesDiffer(summary.personal_cc, expected.personalCc),
        globalTotalCc: !ccValuesDiffer(summary.goal_current_cc, expected.globalTotalCc),
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
    const expectedActiveFourCc = [...expectedRecords.values()].filter(expected => expected?.isFourCcActive === true).length;
    if(Number(payload?.summary?.current_active_4cc) !== expectedActiveFourCc) failures.push('active_4cc_count');

    for(const [fboId, expected] of expectedRecords) {
        const actual = actualById.get(fboId);
        if(!actual) {
            failures.push(`missing_${fboId}`);
            continue;
        }
        if(String(actual.metric_period || '') !== expectedPeriod) failures.push(`period_${fboId}`);
        const numericFields = {
            personal_cc: 'personalCc',
            total_cc: 'totalCc',
            total_active_cc: 'totalActiveCc',
            non_manager_cc: 'nonManagerCc',
            leadership_cc: 'leadershipCc',
            total_active_cc_ytd: 'totalActiveCcYtd',
            non_manager_cc_ytd: 'nonManagerCcYtd',
            leadership_cc_ytd: 'leadershipCcYtd',
        };
        for(const [actualField, expectedField] of Object.entries(numericFields)) {
            if(expected[expectedField] === null || expected[expectedField] === undefined) continue;
            if(actual[actualField] === null
                || actual[actualField] === undefined
                || ccValuesDiffer(actual[actualField], expected[expectedField])) {
                failures.push(`${actualField}_${fboId}`);
            }
        }
        const actualFourCc = normalizedBooleanFlag(actual.is_4cc_active);
        const actualVipEnrolled = normalizedBooleanFlag(actual.is_vip_enrolled);
        if(expected.isFourCcActive !== undefined
            && (actualFourCc === null || actualFourCc !== Boolean(expected.isFourCcActive))) failures.push(`active_4cc_${fboId}`);
        if(expected.mustRemainVipEnrolled === true && actualVipEnrolled !== true) failures.push(`vip_preserved_${fboId}`);
        if(expected.personalCc >= .330 && actualVipEnrolled !== true) failures.push(`vip_${fboId}`);
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
            configuration,
            period
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
            const registeredRootAccount = rootIsRegistered
                ? registeredAccounts.find(account => account.fboId === rootFboId)
                : null;
            const rootTarget = {
                ...(registeredRootAccount || {fboId: rootFboId, activeLinkCount: 0}),
                countryCode: configuration.operatingCountryCode,
                countryCandidates: [],
            };
            /* Probe the exact root immediately before Summary parsing. This is
             * the only independent evidence allowed to resolve a rollover-only
             * local totalCC:null; Global Total CC is never inferred from it. */
            const rootProbe = await fetchLiveCcForMembers(page, configuration, [rootTarget], runDate);
            const rootProbeRecord = rootProbe.records.get(rootFboId);
            if(!rootProbeRecord) throw new Error('Glavni FBO nema potvrđen live CC zapis za CC Summary kontrolu.');
            const ccSummary = await fetchLiveCcSummary(page, configuration, runDate, rootProbeRecord);
            const liveTargets = rootIsRegistered
                ? registeredAccounts.map(account => account.fboId === rootFboId ? rootTarget : account)
                : [rootTarget, ...registeredAccounts];
            const liveCc = await fetchLiveCcForMembers(page, configuration, liveTargets, runDate, {allowUnconfirmed: true});
            if(liveCc.unconfirmed.length > 0 || liveCc.records.size !== liveTargets.length) {
                throw new Error(`Registrirani FCC sync nije potvrdio ${liveCc.unconfirmed.length} od ${liveTargets.length} Forever ID-jeva; razlozi ${JSON.stringify(liveCc.unconfirmedReasonCounts)}; prije upisa ništa nije promijenjeno.`);
            }

            const rootRecord = liveCc.records.get(rootFboId);
            const requiredRootFields = [
                'personalCc', 'totalCc', 'totalActiveCc', 'nonManagerCc', 'leadershipCc',
                'totalActiveCcYtd', 'nonManagerCcYtd', 'leadershipCcYtd',
            ];
            const incompleteRootFields = rootRecord
                ? requiredRootFields.filter(field => !Number.isFinite(rootRecord[field]))
                : requiredRootFields;
            if(incompleteRootFields.length > 0) {
                throw new Error(`Glavni FBO nema potpuni potvrđeni live CC zapis za odabrano razdoblje; nedostaje ${incompleteRootFields.join(', ')}.`);
            }
            if(ccSummary.usedRootCorroboratedLocalTotal === true && !isVerifiedRootCurrentZero(rootRecord)) {
                throw new Error('Glavni FBO više ne potvrđuje nulti current-month CC nakon CC Summary kontrole.');
            }

            if(ccValuesDiffer(ccSummary.totalCc, rootRecord.totalCc)) {
                throw new Error(`FLP360 CC Summary Total CC (${ccSummary.totalCc.toFixed(3)}) ne odgovara glavnom ${configuration.operatingCountryCode} live Total CC-u (${rootRecord.totalCc.toFixed(3)}).`);
            }
            const expectedRecords = new Map(registeredAccounts.map(account => {
                const record = liveCc.records.get(account.fboId);
                return [account.fboId, record ? {...record, isFourCcActive: fourCc.ids.has(account.fboId)} : record];
            }));
            if([...expectedRecords.values()].some(record => !record)) {
                throw new Error('Registrirani FCC sync nema pripremljen zapis za svaki aktivni Forever ID.');
            }
            console.log(`Registrirani FCC način: potvrđeno ${expectedRecords.size} računa za ${period}; ${liveCc.fallbackCount} detail potvrda; ${liveCc.zeroCurrentMonthCount} potvrđenih current-month nula (${liveCc.nullCurrentMonthCount} all-null); tržišta ${JSON.stringify(liveCc.countryCounts)}.`);
            console.log(`YTD zaštita: podignuto ${liveCc.ytdFloorFieldCount} kumulativnih polja na ${liveCc.ytdFloorAccountCount} računa prema potpisanom FCC stanju.`);
            if(ccSummary.usedRootCorroboratedLocalTotal === true) {
                console.log('CC Summary localni Total CC nije bio objavljen; korišten je neposredno i ponovno potvrđen nulti root live zapis.');
            }
            if(ccSummary.globalTotalUnavailable === true) {
                console.warn('CC Summary Global Total CC još nije objavljen; aktualno razdoblje ostaje bez izmišljenog globalnog upisa.');
            }

            if(dryRun) {
                console.log('Kontrolni registrirani FCC način završen je bez upisa.');
                return;
            }

            if(fourCc.rowCount > 0) {
                const fourCcResult = await uploadReport(fourCc.path, period, syncUrl, syncKey);
                console.log(`FCC 4 CC Active: duplicate=${Boolean(fourCcResult.duplicate)}.`);
            } else {
                console.log('FCC 4 CC Active: službeni skup je valjano prazan; svaki registrirani račun dobiva eksplicitni status 0 kroz live CC zapis.');
            }
            await mapWithConcurrency(registeredAccounts, 4, async account => {
                const record = expectedRecords.get(account.fboId);
                return uploadMemberLiveCc(record, fourCc.ids.has(account.fboId), period, syncUrl, syncKey);
            });
            if(!rootIsRegistered) {
                await uploadRootLiveCc(rootRecord, fourCc.ids.has(rootFboId), period, syncUrl, syncKey);
            }
            if(ccSummary.globalTotalCc !== null) {
                await uploadGlobalTotalCc(ccSummary.globalTotalCc, period, syncUrl, syncKey);
            }
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
            if(Number(status?.summary?.active_4cc) !== fourCc.rowCount) {
                throw new Error(`FCC završna 4 CC kontrola nije prošla: očekivano ${fourCc.rowCount}, zapisano ${Number(status?.summary?.active_4cc)}.`);
            }
            console.log(`FCC account provjera: ${registeredVerified.uniqueForeverIds} jedinstvenih Forever ID-jeva (${registeredVerified.activeAccountLinks} aktivnih računa), VIP upis potvrđen za ${registeredVerified.vipEnrolled}.`);
            console.log(`FLP360 → FCC registrirani sync za ${period} završen je uspješno; zadnji FCC podatak ${status.last_data_import_at || status.last_sync_at || 'potvrđen'}.`);
            return;
        }

        const downline = await buildLiveDownline(page, configuration, fourCc.ids, runDate);
        const downlineValidation = await validateDownline(downline.path);
        console.log(`Downline live: ${downlineValidation.rows} redaka, svih ${downline.liveConfirmed} CC zapisa potvrđeno (${downline.fallbackCount} potvrđenih detaljnih odgovora; ${downline.operatingMarketFallbackCount} regionalnih fallbacka; tržišta ${JSON.stringify(downline.countryCounts)}).`);
        const fullSyncRootTarget = {
            fboId: normalizeFboId(ROOT_FBO_ID),
            countryCode: configuration.operatingCountryCode,
            countryCandidates: [],
        };
        const rootLiveCc = await fetchLiveCcForMembers(page, configuration, [fullSyncRootTarget], runDate);
        let rootRecord = rootLiveCc.records.get(normalizeFboId(ROOT_FBO_ID));
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
            registeredExpectedRecords.set(account.fboId, {...record, isFourCcActive: fourCc.ids.has(account.fboId)});
        }
        console.log(`FCC Forever ID-jevi izvan potvrđene hijerarhije: ${registeredOnlyAccounts.length}; FLP360 je potvrdio ${confirmedRegisteredOnlyAccounts.length}, nepotvrđeno je ${unconfirmedRegisteredAccountCount}.`);
        const ccSummary = await fetchLiveCcSummary(page, configuration, runDate, rootRecord);
        if(ccSummary.usedRootCorroboratedLocalTotal === true) {
            const rootRecheck = await fetchLiveCcForMembers(page, configuration, [fullSyncRootTarget], runDate);
            const recheckedRootRecord = rootRecheck.records.get(normalizeFboId(ROOT_FBO_ID));
            if(!isVerifiedRootCurrentZero(recheckedRootRecord)) {
                throw new Error('Glavni FBO više ne potvrđuje nulti current-month CC nakon CC Summary kontrole.');
            }
            rootRecord = recheckedRootRecord;
            if(registeredExpectedRecords.has(rootRecord.fboId)) {
                registeredExpectedRecords.set(rootRecord.fboId, {
                    ...rootRecord,
                    isFourCcActive: fourCc.ids.has(rootRecord.fboId),
                });
            }
        }
        if(ccValuesDiffer(ccSummary.totalCc, rootRecord.totalCc)) {
            throw new Error(`FLP360 CC Summary Total CC (${ccSummary.totalCc.toFixed(3)}) ne odgovara glavnom ${configuration.operatingCountryCode} live Total CC-u (${rootRecord.totalCc.toFixed(3)}).`);
        }
        if(ccSummary.globalTotalCc === null) {
            throw new Error('FLP360 CC Summary nema objavljen Global Total CC za potpuni hijerarhijski sync.');
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
        if(fourCc.rowCount > 0) {
            const fourCcResult = await uploadReport(fourCc.path, period, syncUrl, syncKey);
            console.log(`FCC 4 CC Active: duplicate=${Boolean(fourCcResult.duplicate)}.`);
        } else {
            console.log('FCC 4 CC Active: službeni skup je valjano prazan; Downline live zapisuje eksplicitni status 0.');
        }

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
    applyRegisteredAccountSafetyFloor,
    fetchLiveCcSummary,
    fetchLiveCcForMembers,
    findCurrentFlpMonthLabel,
    normalizeFboId,
    normalizeCountryCode,
    officialFourCoreSnapshots,
    parseCsvLine,
    parseFlpTimestamp,
    payloadHasExplicitError,
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
