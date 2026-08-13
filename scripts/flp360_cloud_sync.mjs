/* Custom code: FC-2026-08-13: Cloud FLP360 to FCC synchronization */

import fs from 'node:fs/promises';
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
    return labels.find(label => {
        const [month, yearAndStatus] = String(label).trim().split('/');
        return Number(month) === Number(expectedMonth) && yearAndStatus === expectedYearAndStatus;
    }) || '';
}

function readyReportMessage(bodyText) {
    return bodyText.match(/Your report generated on[^\n]+is ready\./i)?.[0]?.trim() || '';
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

    const hrvRows = lines.slice(1).filter(line => line.includes(',HRV,')).length;
    if(hrvRows < 250) throw new Error(`Downline nema očekivani HRV opseg (${hrvRows} redaka).`);

    return {rows: lines.length - 1, hrvRows};
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
    await page.goto(`${FLP360_BASE_URL}/downline`, {waitUntil: 'domcontentloaded', timeout: 60000});
    await page.locator('a.excel-download').waitFor({state: 'visible', timeout: 60000});
    await page.locator('a.download-link').waitFor({state: 'visible', timeout: 5000}).catch(() => {});

    const initialMessage = readyReportMessage(await page.locator('body').innerText());
    await page.locator('a.excel-download').click();
    const dialog = page.getByRole('dialog');
    await dialog.waitFor({state: 'visible', timeout: 15000});
    await dialog.getByText(ROOT_FBO_ID, {exact: false}).waitFor({state: 'visible', timeout: 30000}).catch(() => {
        throw new Error('Downline izvoz nije vezan uz očekivani glavni Forever ID.');
    });
    await dialog.getByRole('button', {name: 'Download Report', exact: true}).click();

    return initialMessage;
}

async function prepareFourCcCountryState(page) {
    const countryState = await page.evaluate(() => {
        const userInfoText = localStorage.getItem('appflp360.userInfo') || localStorage.getItem('userInfo') || '{}';
        const userInfo = JSON.parse(userInfoText);
        const profileCodes = [userInfo.preferredCountryCode, userInfo.homeCountryCode].filter(Boolean);
        if(profileCodes.length && !profileCodes.includes('HRV')) {
            return {profileCodes, repaired: false, valid: false};
        }

        const countriesText = sessionStorage.getItem('countries') || '[]';
        const countries = JSON.parse(countriesText);
        if(!Array.isArray(countries) || !countries.length) {
            return {profileCodes, repaired: false, valid: false};
        }

        let croatia = countries.find(country => country.operatingCompany === 'HRV');
        countries.forEach(country => {
            country.homeCountry = country.operatingCompany === 'HRV';
        });
        if(!croatia) {
            croatia = {countryName: 'Croatia', operatingCompany: 'HRV', homeCountry: true};
            countries.push(croatia);
        }
        sessionStorage.setItem('countries', JSON.stringify(countries));

        return {profileCodes, repaired: true, valid: true};
    });

    if(!countryState.valid) {
        throw new Error('FLP360 profil ili popis država nije moguće sigurno uskladiti s Hrvatskom.');
    }
}

async function downloadDownline(page, initialMessage) {
    await page.goto(`${FLP360_BASE_URL}/downline`, {waitUntil: 'domcontentloaded', timeout: 60000});
    await page.locator('a.excel-download').waitFor({state: 'visible', timeout: 60000});

    const deadline = Date.now() + 20 * 60 * 1000;
    let currentMessage = '';
    while(Date.now() < deadline) {
        await page.waitForTimeout(15000);
        await page.reload({waitUntil: 'domcontentloaded', timeout: 60000});
        await page.locator('a.excel-download').waitFor({state: 'visible', timeout: 60000});
        currentMessage = readyReportMessage(await page.locator('body').innerText());
        if(currentMessage && currentMessage !== initialMessage) break;
    }
    if(!currentMessage || currentMessage === initialMessage) throw new Error('Svježi Downline izvještaj nije generiran unutar 20 minuta.');

    const downloadPromise = page.waitForEvent('download', {timeout: 60000});
    await page.locator('a.download-link').click();
    return saveDownload(await downloadPromise, `flp360-downline-${zagrebPeriod()}.csv`);
}

async function downloadFocusGroup(page) {
    await page.goto(`${FLP360_BASE_URL}/focus-group`, {waitUntil: 'domcontentloaded', timeout: 60000});
    const downloadLink = page.getByText('Download Data to Excel', {exact: true});
    await downloadLink.waitFor({state: 'visible', timeout: 120000});
    const bodyText = await page.locator('body').innerText();
    if(!bodyText.includes(ROOT_FBO_ID)) throw new Error('Focus Group ne sadrži očekivani glavni Forever ID.');

    const downloadPromise = page.waitForEvent('download', {timeout: 60000});
    await downloadLink.click();
    return saveDownload(await downloadPromise, `flp360-focus-group-${zagrebPeriod()}.xlsx`);
}

async function downloadFourCcActive(page) {
    await prepareFourCcCountryState(page);
    await page.goto(`${FLP360_BASE_URL}/four-cc-consecutive-month`, {waitUntil: 'domcontentloaded', timeout: 60000});
    const selects = page.locator('select');
    await selects.first().waitFor({state: 'visible', timeout: 60000});
    await selects.nth(0).selectOption({label: 'Global'});
    const periodOptions = selects.nth(1).locator('option');
    await periodOptions.first().waitFor({state: 'attached', timeout: 60000});
    const currentPeriodLabel = findCurrentFlpMonthLabel(await periodOptions.allTextContents());
    if(!currentPeriodLabel) throw new Error(`4 CC Active nema očekivano otvoreno razdoblje ${currentFlpMonthLabel()}.`);
    await selects.nth(1).selectOption({label: currentPeriodLabel});
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

async function main() {
    const username = requiredEnvironment('FLP360_USERNAME');
    const password = requiredEnvironment('FLP360_PASSWORD');
    const syncUrl = requiredEnvironment('FCC_FOREVER_SYNC_URL');
    const syncKey = requiredEnvironment('FCC_FOREVER_SYNC_KEY');
    const {chromium} = await import('playwright');
    const browser = await chromium.launch({headless: true});
    const context = await browser.newContext({acceptDownloads: true, locale: 'en-US'});
    const page = await context.newPage();
    const period = zagrebPeriod();

    try {
        await login(page, username, password);

        /* Request the slow asynchronous Downline first, then synchronize the two
         * immediately available reports while FLP360 prepares it in the background. */
        const failures = [];
        let initialDownlineMessage = '';
        let downlineRequested = false;
        try {
            initialDownlineMessage = await requestDownline(page);
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
            failures.push(`Focus Group: ${error.message}`);
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
                const downlinePath = await downloadDownline(page, initialDownlineMessage);
                const downlineValidation = await validateDownline(downlinePath);
                const downlineResult = await uploadReport(downlinePath, period, syncUrl, syncKey);
                console.log(`Downline: ${downlineValidation.rows} redaka (${downlineValidation.hrvRows} HRV), duplicate=${Boolean(downlineResult.duplicate)}.`);
            } catch(error) {
                failures.push(`Downline: ${error.message}`);
            }
        }

        if(failures.length) {
            throw new Error(`Djelomična sinkronizacija: ${failures.join(' | ')}`);
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

export {currentFlpMonthLabel, findCurrentFlpMonthLabel, readyReportMessage, validateDownline, validateXlsx, zagrebPeriod};

/* /Custom code: FC-2026-08-13 */
