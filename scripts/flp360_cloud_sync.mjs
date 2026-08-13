/* Custom code: FC-2026-08-13: Cloud FLP360 to FCC synchronization */

import fs from 'node:fs/promises';
import crypto from 'node:crypto';
import path from 'node:path';
import process from 'node:process';
import {pathToFileURL} from 'node:url';

const FLP360_BASE_URL = 'https://flp360.foreverliving.com';
const ROOT_FBO_ID = '360-000-760-944';
const OUTPUT_DIRECTORY = process.env.RUNNER_TEMP || '/tmp';

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

function encryptFlpAuthorization(value, encryptionKey) {
    const salt = crypto.randomBytes(64);
    const initializationVector = crypto.randomBytes(12);
    const key = crypto.pbkdf2Sync(encryptionKey, salt, 2145, 32, 'sha512');
    const cipher = crypto.createCipheriv('aes-256-gcm', key, initializationVector);
    const encrypted = Buffer.concat([cipher.update(value, 'utf8'), cipher.final()]);
    return Buffer.concat([salt, initializationVector, encrypted, cipher.getAuthTag()]).toString('base64');
}

function buildDownlineGenerationUrl(reportBase, distributorId) {
    const url = new URL(`V2/distributors/${distributorId}/generate/rewire-downline-excel-query`, `${String(reportBase).replace(/\/+$/, '')}/`);
    const parameters = {
        year: 0,
        month: 0,
        expandingLevel: 0,
        pageNumber: 1,
        numberOfRecords: 15,
        showNonZero: false,
        memberLevel: 0,
        country: 'HRV',
        generationValue: 0,
        sponsorDistID: distributorId,
        isExcelView: true,
        downlineGenValue: 0,
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
        const storedReportBase = parseJson(localStorage.getItem('appflp360.reportApicategory'), '');
        const reportBase = /^https:\/\//i.test(String(storedReportBase || ''))
            ? storedReportBase
            : api.reportProApi || api.reportLiteApi || `${api.apiGatewayURL}/${api.reportApi || 'reporttdm'}`;
        return {
            aesEncryptionKey: api.aesEncryptionKey || '',
            apiGatewayUrl: api.apiGatewayURL || '',
            cdnUrl: api.cdnURL || '',
            guestToken: parseJson(localStorage.getItem('appflp360.guestToken'), api.guestToken || ''),
            reportBase,
        };
    });

    for(const name of ['aesEncryptionKey', 'apiGatewayUrl', 'cdnUrl', 'guestToken', 'reportBase']) {
        if(!String(configuration[name] || '').trim()) throw new Error(`FLP360 konfiguracija nema ${name}.`);
    }
    return configuration;
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

async function flpGetJson(page, requestUrl, configuration) {
    const response = await page.context().request.get(requestUrl, {
        headers: flpApiHeaders(configuration, requestUrl),
        timeout: 120000,
    });
    const responseText = await response.text();
    if(!response.ok()) throw new Error(`FLP360 podatkovni poziv nije uspio (HTTP ${response.status()}).`);
    try {
        return JSON.parse(responseText);
    } catch {
        throw new Error('FLP360 podatkovni poziv nije vratio valjan JSON odgovor.');
    }
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

        const submit = page.locator('#kc-login:visible, button[type="submit"]:visible, input[type="submit"]:visible').first();
        await Promise.all([
            page.waitForURL(url => url.origin === FLP360_BASE_URL && url.pathname === '/dashboard', {timeout: 120000}),
            submit.click(),
        ]);
    }

    if(new URL(page.url()).pathname !== '/dashboard') {
        await page.goto(`${FLP360_BASE_URL}/dashboard`, {waitUntil: 'domcontentloaded', timeout: 60000});
    }
    await page.getByText(ROOT_FBO_ID, {exact: true}).waitFor({state: 'visible', timeout: 120000});
}

async function requestDownline(page) {
    const configuration = await flpApiConfiguration(page);
    const distributorId = ROOT_FBO_ID.replaceAll('-', '');
    const queueUrl = `${String(configuration.apiGatewayUrl).replace(/\/+$/, '')}/flp360/v1/distributors/${distributorId}/report/Downline/report-extract-queue`;
    const initialQueue = await flpGetJson(page, queueUrl, configuration);
    const generationUrl = buildDownlineGenerationUrl(configuration.reportBase, distributorId);
    console.log('Downline filtri: Croatia (HRV), All Levels, Suppress 0CC=Off.');
    const generationResult = await flpGetJson(page, generationUrl, configuration);
    const generationMessage = String(generationResult?.message || '');
    const previousRequestIsRunning = /previous request is being processed/i.test(generationMessage);
    if(!generationResult?.isSuccess && !previousRequestIsRunning) {
        throw new Error(generationMessage || 'FLP360 nije prihvatio Downline izvoz.');
    }
    console.log(previousRequestIsRunning
        ? 'FLP360 još obrađuje prethodni Downline zahtjev; prati se postojeći red.'
        : 'Downline izvoz je poslan u FLP360 red za generiranje.');

    return {
        configuration,
        initialProcessedTime: String(initialQueue?.requestProcessedTime || ''),
        queueUrl,
    };
}

async function prepareFourCcCountryState(page) {
    const countryState = await page.evaluate(() => {
        const parseJson = (value, fallback) => {
            try {
                return JSON.parse(value || '');
            } catch {
                return fallback;
            }
        };
        const userInfoText = localStorage.getItem('appflp360.userInfo') || localStorage.getItem('userInfo') || '{}';
        const userInfo = parseJson(userInfoText, {});
        const profileCodes = [userInfo.preferredCountryCode, userInfo.homeCountryCode].filter(Boolean);

        const countriesText = sessionStorage.getItem('countries') || '[]';
        let countries = parseJson(countriesText, []);
        if(!Array.isArray(countries) || !countries.length) {
            const countrySelect = document.querySelector('select');
            countries = [...(countrySelect?.options || [])]
                .filter(option => option.value && option.value !== 'Global')
                .map(option => ({
                    countryName: String(option.textContent || '').replace(/\s*\([^)]*\)\s*$/, '').trim(),
                    operatingCompany: option.value,
                    homeCountry: false,
                }));
        }
        if(!Array.isArray(countries)) countries = [];

        let croatia = countries.find(country => country.operatingCompany === 'HRV');
        countries.forEach(country => {
            country.homeCountry = country.operatingCompany === 'HRV';
        });
        if(!croatia) {
            croatia = {countryName: 'Croatia', operatingCompany: 'HRV', homeCountry: true};
            countries.push(croatia);
        }
        sessionStorage.setItem('countries', JSON.stringify(countries));

        return {profileCodes, countryCount: countries.length, repaired: true, valid: true};
    });

    if(!countryState.valid) {
        throw new Error('FLP360 popis država nije moguće pripremiti za hrvatski međunarodni izvještaj.');
    }
    console.log(`FLP360 država Croatia (HRV) je pripremljena (${countryState.countryCount} dostupnih opcija).`);
}

async function downloadDownline(page, requestDetails) {
    const deadline = Date.now() + 20 * 60 * 1000;
    let latestQueue = {};
    while(Date.now() < deadline) {
        await page.waitForTimeout(15000);
        latestQueue = await flpGetJson(page, requestDetails.queueUrl, requestDetails.configuration);
        const processedTime = String(latestQueue?.requestProcessedTime || '');
        if(latestQueue?.processedFilePath && processedTime && processedTime !== requestDetails.initialProcessedTime) break;
    }
    if(!latestQueue?.processedFilePath || String(latestQueue?.requestProcessedTime || '') === requestDetails.initialProcessedTime) {
        throw new Error('Svježi Downline izvještaj nije generiran unutar 20 minuta.');
    }

    const downloadUrl = buildDownlineDownloadUrl(requestDetails.configuration.cdnUrl, latestQueue.processedFilePath);
    const response = await page.context().request.get(downloadUrl, {headers: {Accept: 'text/csv'}, timeout: 120000});
    if(!response.ok()) throw new Error(`FLP360 Downline CSV nije moguće preuzeti (HTTP ${response.status()}).`);
    const targetPath = path.join(OUTPUT_DIRECTORY, `flp360-downline-${zagrebPeriod()}.csv`);
    await fs.writeFile(targetPath, await response.body());
    const stat = await fs.stat(targetPath);
    if(stat.size < 1000) throw new Error('FLP360 Downline CSV je premalen za siguran uvoz.');
    return targetPath;
}

async function downloadFocusGroup(page) {
    let lastError;
    for(let attempt = 1; attempt <= 2; attempt++) {
        try {
            await page.goto(`${FLP360_BASE_URL}/focus-group`, {waitUntil: 'domcontentloaded', timeout: 60000});
            const downloadLink = page.getByText('Download Data to Excel', {exact: true});
            await downloadLink.waitFor({state: 'visible', timeout: 120000});
            const downloadPromise = page.waitForEvent('download', {timeout: 75000});
            await downloadLink.click();
            return saveDownload(await downloadPromise, `flp360-focus-group-${zagrebPeriod()}.xlsx`);
        } catch(error) {
            lastError = error;
        }
    }
    throw lastError;
}

async function downloadFourCcActive(page) {
    await prepareFourCcCountryState(page);
    await page.goto(`${FLP360_BASE_URL}/four-cc-consecutive-month`, {waitUntil: 'domcontentloaded', timeout: 60000});
    const selects = page.locator('select');
    await selects.first().waitFor({state: 'visible', timeout: 60000});
    await selects.nth(0).selectOption({label: 'Global'});
    const periodOptions = selects.nth(1).locator('option');
    await periodOptions.first().waitFor({state: 'attached', timeout: 60000});
    const periodLabels = await periodOptions.allTextContents();
    const currentPeriodLabel = findCurrentFlpMonthLabel(periodLabels);
    if(!currentPeriodLabel) throw new Error(`4 CC Active nema očekivano otvoreno razdoblje ${currentFlpMonthLabel()}. Dostupno: ${periodLabels.map(label => String(label).trim()).filter(Boolean).join(' | ') || 'bez razdoblja'}.`);
    await selects.nth(1).selectOption({index: periodLabels.indexOf(currentPeriodLabel)});
    await selects.nth(2).selectOption({label: 'Active That Period'});
    await page.getByRole('button', {name: 'Run Report', exact: true}).click();

    const downloadLink = page.getByText('Click here to download the data to excel', {exact: true});
    await downloadLink.waitFor({state: 'visible', timeout: 120000});
    const downloadPromise = page.waitForEvent('download', {timeout: 60000});
    await downloadLink.click();
    return saveDownload(await downloadPromise, `flp360-4cc-active-${zagrebPeriod()}.xlsx`);
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

async function main() {
    const username = requiredEnvironment('FLP360_USERNAME');
    const password = requiredEnvironment('FLP360_PASSWORD');
    const syncUrl = requiredEnvironment('FCC_FOREVER_SYNC_URL');
    const syncKey = requiredEnvironment('FCC_FOREVER_SYNC_KEY');
    const playwrightModule = process.env.PLAYWRIGHT_MODULE_URL || 'playwright';
    const {chromium} = await import(playwrightModule);
    const browser = await chromium.launch({
        headless: true,
        ...(process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE ? {executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE} : {}),
    });
    const context = await browser.newContext({acceptDownloads: true, locale: 'en-US'});
    const page = await context.newPage();
    const period = zagrebPeriod();

    try {
        await login(page, username, password);

        for(const snapshot of officialFourCoreSnapshots()) {
            await uploadFourCoreSnapshot(snapshot, syncUrl, syncKey);
            console.log(`Službeni 4 Core: ${snapshot.period} je potvrđen na FCC-u.`);
        }

        /* Request the slow asynchronous Downline first, then synchronize the two
         * immediately available reports while FLP360 prepares it in the background. */
        const failures = [];
        const warnings = [];
        let downlineRequest = null;
        let downlineRequested = false;
        try {
            downlineRequest = await requestDownline(page);
            downlineRequested = true;
        } catch(error) {
            failures.push(`Downline zahtjev: ${error.message}`);
        }

        try {
            const focusPath = await downloadFocusGroup(page);
            const focusValidation = await validateXlsx(focusPath, 'Focus Group');
            const focusResult = await uploadReport(focusPath, period, syncUrl, syncKey);
            console.log(`Focus Group: ${focusValidation.bytes} bajtova, duplicate=${Boolean(focusResult.duplicate)}.`);
        } catch(error) {
            warnings.push(`Focus Group: ${error.message}`);
        }

        try {
            const fourCcPath = await downloadFourCcActive(page);
            const fourCcValidation = await validateXlsx(fourCcPath, '4 CC Active');
            const fourCcResult = await uploadReport(fourCcPath, period, syncUrl, syncKey);
            console.log(`4 CC Active: ${fourCcValidation.bytes} bajtova, duplicate=${Boolean(fourCcResult.duplicate)}.`);
        } catch(error) {
            failures.push(`4 CC Active: ${error.message}`);
        }

        if(downlineRequested) {
            try {
                const downlinePath = await downloadDownline(page, downlineRequest);
                const downlineValidation = await validateDownline(downlinePath);
                const downlineResult = await uploadReport(downlinePath, period, syncUrl, syncKey);
                console.log(`Downline: ${downlineValidation.rows} redaka (${downlineValidation.hrvRows} HRV; ${downlineValidation.countries.join(', ')}), duplicate=${Boolean(downlineResult.duplicate)}.`);
            } catch(error) {
                failures.push(`Downline: ${error.message}`);
            }
        }

        if(failures.length) {
            throw new Error(`Djelomična sinkronizacija: ${failures.join(' | ')}`);
        }

        if(warnings.length) {
            console.warn(`Sinkronizacija je dovršena uz upozorenje: ${warnings.join(' | ')}`);
        }

        console.log(`FLP360 → FCC sinkronizacija za ${period} završena je uspješno.`);
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

export {buildDownlineDownloadUrl, buildDownlineGenerationUrl, currentFlpMonthLabel, encryptFlpAuthorization, findCurrentFlpMonthLabel, officialFourCoreSnapshots, parseCsvLine, readyReportMessage, validateDownline, validateXlsx, zagrebPeriod};

/* /Custom code: FC-2026-08-13 */
