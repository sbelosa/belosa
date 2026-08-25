/* Custom code: FC-2026-08-13: Cloud FLP360 synchronization regression checks */

import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import {
    buildDownlineDownloadUrl,
    buildDownlineGenerationUrl,
    buildFourCcCsv,
    csvDocument,
    currentFlpMonthLabel,
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
    readyReportMessage,
    refreshDownlineCsv,
    reportV2Url,
    resolveLiveCcCountryCode,
    validateDownline,
    validateFourCcRows,
    validateXlsx,
    verifyFccStatus,
    zagrebPeriod,
    zagrebPeriodParts,
} from './flp360_cloud_sync.mjs';

const downlinePath = process.argv[2];
const focusPath = process.argv[3];
const fourCcPath = process.argv[4];
const testDate = new Date('2026-08-13T19:00:00Z');
const syncSource = await fs.readFile(new URL('./flp360_cloud_sync.mjs', import.meta.url), 'utf8');

assert.equal(zagrebPeriod(testDate), '2026-08');
assert.deepEqual(zagrebPeriodParts(testDate), {year: 2026, month: 8, monthLabel: 'AUG'});
assert.equal(currentFlpMonthLabel(testDate), '08/2026-Not Closed');
assert.equal(findCurrentFlpMonthLabel(['7/2026-Closed', '8/2026-Not Closed'], testDate), '8/2026-Not Closed');
assert.equal(findCurrentFlpMonthLabel(['Jul/2026-Closed', 'Aug/2026-Not Closed'], testDate), 'Aug/2026-Not Closed');
assert.equal(findCurrentFlpMonthLabel(['July 2026 - Closed', ' August 2026 - Not Closed '], testDate), ' August 2026 - Not Closed ');
assert.equal(findCurrentFlpMonthLabel(['07/2026-Closed'], testDate), '');
assert.equal(readyReportMessage('Your report generated on Aug 13, 2026 at 12:22pm is ready.\nClick here'), 'Your report generated on Aug 13, 2026 at 12:22pm is ready.');
assert.equal(readyReportMessage('Your report is being generated.'), '');
assert.deepEqual(parseCsvLine('1,2,"Prezime, Ime",4,5,HRV'), ['1', '2', 'Prezime, Ime', '4', '5', 'HRV']);
assert.equal(normalizeFboId('360-000-760-944'), '360000760944');
assert.equal(normalizeCountryCode(' deu '), 'DEU');
assert.equal(normalizeCountryCode('HRV'), 'HRV');
assert.equal(normalizeCountryCode('Germany'), '');
assert.equal(resolveLiveCcCountryCode('HRV', {homeCountryCode: 'HRV', operatingCountryCode: 'HUN'}), 'HUN');
assert.equal(resolveLiveCcCountryCode('DEU', {homeCountryCode: 'HRV', operatingCountryCode: 'HUN'}), 'DEU');
assert.equal(parseFlpTimestamp(1786616559000)?.toISOString(), '2026-08-13T10:22:39.000Z');
assert.equal(parseFlpTimestamp('1786616559000')?.toISOString(), '2026-08-13T10:22:39.000Z');
assert.equal(parseFlpTimestamp('not-a-date'), null);

const ccSummary = extractCurrentCcSummary([
    {processingYear: 2026, processingMonth: 0, valueType: 'Yearly', totalCC: 1406.925, globalTotalCC: 1505.697},
    {processingYear: 2026, processingMonth: 7, valueType: 'Monthly', totalCC: 202.077, globalTotalCC: 215.608},
    {processingYear: 2026, processingMonth: 8, valueType: 'Monthly', totalCC: 58.820, globalTotalCC: 63.684},
], testDate);
assert.deepEqual(ccSummary, {totalCc: 58.820, globalTotalCc: 63.684});
assert.throws(() => extractCurrentCcSummary([], testDate), /prazan/);
assert.throws(() => extractCurrentCcSummary([
    {processingYear: 2026, processingMonth: 7, valueType: 'Monthly', totalCC: 1, globalTotalCC: 2},
], testDate), /aktualno razdoblje/);

const generationUrl = new URL(buildDownlineGenerationUrl('https://example.test/api/reporttdmpro', '360000760944'));
assert.equal(generationUrl.pathname, '/api/reporttdmpro/V2/distributors/360000760944/generate/rewire-downline-excel-query');
assert.equal(generationUrl.searchParams.get('country'), 'HUN');
assert.equal(generationUrl.searchParams.get('homeCountryCode'), 'HRV');
assert.equal(generationUrl.searchParams.get('showNonZero'), 'false');
assert.equal(generationUrl.searchParams.get('memberLevel'), '0');
assert.equal(reportV2Url({reportBase: 'https://example.test/api/reporttdmpro/V2/'}, '/distributors/1/treeview-cc'), 'https://example.test/api/reporttdmpro/V2/distributors/1/treeview-cc');
assert.equal(buildDownlineDownloadUrl('https://cdn.example.test', '/CustomerReports/Downline/abcdef.csv'), 'https://cdn.example.test/CustomerReports/Downline/abcdef.csv');
assert.ok(Buffer.from(encryptFlpAuthorization('request||Bearer token&&3', '0123456789abcdef'), 'base64').length > 92);

const fourCcPayload = [{body: [
    /* Total Active CC is inclusive: both 4 personal + 0 remainder and
       2 personal + 2 eligible remainder are represented as totalActiveCC=4. */
    {fboID: '360000760944', fboName: 'Root', level: 'Manager', homeCountry: 'HRV', personalCC: 4, totalActiveCC: 4, processingYear: 2026, processingMonth: 8},
    {fboID: '360000000001', fboName: 'Member', level: 'Supervisor', homeCountry: 'HRV', personalCC: 2, totalActiveCC: 4, processingYear: 2026, processingMonth: 8},
]}];
const fourCcRows = extractFourCcRows(fourCcPayload);
assert.equal(validateFourCcRows(fourCcRows, testDate).rows, 2);
const fourCcCsv = buildFourCcCsv(fourCcRows, testDate);
assert.match(fourCcCsv, /SELECTED MONTH\/YEAR/);
assert.match(fourCcCsv, /AUG 2026/);
assert.throws(() => validateFourCcRows([], testDate), /prazan/);

const currentRecord = extractLiveCcRecord([{
    fboId: '360000000001',
    processingYear: 2026,
    totalActiveCC: 20,
    nonManagerCC: 30,
    leaderCC: 40,
    monthlyCCValues: [{
        processingYear: 2026,
        processingMonth: 8,
        personalCCMTD: 1,
        totalCCMTD: 2,
        totalActiveCCMTD: 3,
        nonManagerCCMTD: 4,
        leaderCC: 5,
    }],
}], '360000000001', testDate);
assert.deepEqual(currentRecord, {
    fboId: '360000000001', personalCc: 1, totalCc: 2, totalActiveCc: 3,
    nonManagerCc: 4, leadershipCc: 5, totalActiveCcYtd: 20,
    nonManagerCcYtd: 30, leadershipCcYtd: 40,
});
assert.equal(extractLiveCcRecord([{
    fboId: '360000000002', processingYear: 0, totalActiveCC: 0, nonManagerCC: 0, leaderCC: 0,
    monthlyCCValues: [{processingYear: 0, processingMonth: 0}],
}], '360000000002', testDate).totalCc, 0);
const fallbackRecord = extractLiveZeroFallback([{
    fboId: '', processingYear: 0, monthlyCCValues: [{processingYear: 0, processingMonth: 0}],
}], {
    distributorId: '360000000003', personalCCCurMonth: 0, totalCCCurMonth: 0,
    totalActiveCCCurMonth: 0, totalActiveCCYTD: 1.024,
}, '360000000003');
assert.equal(fallbackRecord.totalActiveCcYtd, 1.024);
assert.equal(fallbackRecord.nonManagerCc, null);
assert.equal(fallbackRecord.nonManagerCcYtd, null);
const internationalFallback = extractLiveZeroFallback([{
    fboId: '', processingYear: 0, monthlyCCValues: [{processingYear: 0, processingMonth: 0}],
}], {
    distributorId: '360000000003', personalCCCurMonth: 0.792, totalCCCurMonth: 0.792,
    totalActiveCCCurMonth: 0.792, totalActiveCCYTD: 0.792,
}, '360000000003');
assert.equal(internationalFallback.personalCc, 0.792);
assert.equal(internationalFallback.totalCc, 0.792);
assert.equal(internationalFallback.totalActiveCc, 0.792);
assert.equal(internationalFallback.nonManagerCc, null);

const downlineHeaders = [
    'FBO ID', 'TREESEQUENCE', 'NAME', 'TITLE', 'GENERATION', 'COUNTRY',
    '4CC ACTIVE - AUG - 2026', 'PERSONAL CC - AUG - 2026', 'TOTAL CC - AUG - 2026',
    'TOTAL ACTIVE CC - AUG - 2026', 'NON MANAGER CC - AUG - 2026', 'LEADERSHIP CC - AUG - 2026',
    'TOTAL ACTIVE CC YTD - 2026', 'NON MANAGER CC YTD - 2026', 'LEADERSHIP CC YTD - 2026',
];
const baseRows = Array.from({length: 400}, (_, index) => {
    const fboId = String(360000000001 + index);
    return [fboId, String(index + 1), `Member ${index + 1}`, 'Distributor', '1', index < 300 ? 'HRV' : 'HUN', 'N', '0', '0', '0', '0', '0', '0', '0', '0'];
});
const memberReferences = extractLiveMemberReferences(csvDocument([downlineHeaders, ...baseRows]));
assert.deepEqual(memberReferences[0], {fboId: baseRows[0][0], homeCountryCode: 'HRV'});
assert.deepEqual(memberReferences[399], {fboId: baseRows[399][0], homeCountryCode: 'HUN'});
assert.throws(() => extractLiveMemberReferences(csvDocument([
    downlineHeaders,
    [baseRows[0][0], '1', 'Invalid country', 'Distributor', '1', '', 'N', '0', '0', '0', '0', '0', '0', '0', '0'],
])), /matičnu zemlju/);

const requestedLiveUrls = [];
const livePayloadByFboId = new Map([
    ['360000000001', {countryCode: 'HRV', personalCc: 0.125, totalCc: 0.125}],
    ['360000000002', {countryCode: 'DEU', personalCc: 0.792, totalCc: 0.792}],
]);
const fakeLivePage = {
    context: () => ({request: {get: async requestUrl => {
        requestedLiveUrls.push(requestUrl);
        const url = new URL(requestUrl);
        const fboId = url.pathname.match(/distributors\/(\d+)\/treeview-cc/)?.[1];
        const expected = livePayloadByFboId.get(fboId);
        assert.equal(url.searchParams.get('countryCode'), expected.countryCode);
        return {
            ok: () => true,
            status: () => 200,
            text: async () => JSON.stringify([{
                fboId,
                processingYear: 2026,
                totalActiveCC: expected.totalCc,
                nonManagerCC: 0,
                leaderCC: 0,
                monthlyCCValues: [{
                    processingYear: 2026,
                    processingMonth: 8,
                    personalCCMTD: expected.personalCc,
                    totalCCMTD: expected.totalCc,
                    totalActiveCCMTD: expected.totalCc,
                    nonManagerCCMTD: 0,
                    leaderCC: 0,
                }],
            }]),
        };
    }}}),
    waitForTimeout: async () => {},
};
const liveByHomeCountry = await fetchLiveCcForMembers(fakeLivePage, {
    reportBase: 'https://example.test/api/reporttdmpro',
    aesEncryptionKey: '0123456789abcdef',
    guestToken: 'test-token',
    operatingCountryCode: 'HUN',
}, [
    {fboId: '360000000001', countryCode: 'HRV'},
    {fboId: '360000000002', countryCode: 'DEU'},
], testDate);
assert.equal(liveByHomeCountry.records.get('360000000002').totalCc, 0.792);
assert.deepEqual(liveByHomeCountry.countryCounts, {DEU: 1, HRV: 1});
assert.equal(requestedLiveUrls.length, 2);
await assert.rejects(() => fetchLiveCcForMembers(fakeLivePage, {
    reportBase: 'https://example.test/api/reporttdmpro',
    aesEncryptionKey: '0123456789abcdef',
    guestToken: 'test-token',
    operatingCountryCode: 'HUN',
}, [{fboId: '360000000003', countryCode: ''}], testDate), /matičnu zemlju/);

const regionalFallbackUrls = [];
const regionalFallbackPage = {
    context: () => ({request: {get: async requestUrl => {
        regionalFallbackUrls.push(requestUrl);
        const url = new URL(requestUrl);
        const countryCode = url.searchParams.get('countryCode') || url.pathname.match(/\/country\/([A-Z]{2,3})/)?.[1];
        const isDetail = url.pathname.includes('downlineLoggedInDetails');
        const payload = countryCode === 'BIH'
            ? (isDetail
                ? [{distributorId: '360000999999', personalCCCurMonth: 0, totalCCCurMonth: 0, totalActiveCCCurMonth: 0}]
                : [{fboId: '', processingYear: 0, monthlyCCValues: [{processingYear: 0, processingMonth: 0}]}])
            : [{
                fboId: '360000000004', processingYear: 2026, totalActiveCC: 0.5, nonManagerCC: 0, leaderCC: 0,
                monthlyCCValues: [{processingYear: 2026, processingMonth: 8, personalCCMTD: 0.5, totalCCMTD: 0.5, totalActiveCCMTD: 0.5, nonManagerCCMTD: 0, leaderCC: 0}],
            }];
        return {ok: () => true, status: () => 200, text: async () => JSON.stringify(payload)};
    }}}),
    waitForTimeout: async () => {},
};
const regionalFallback = await fetchLiveCcForMembers(regionalFallbackPage, {
    reportBase: 'https://example.test/api/reporttdmpro',
    aesEncryptionKey: '0123456789abcdef',
    guestToken: 'test-token',
    operatingCountryCode: 'HUN',
}, [{fboId: '360000000004', countryCode: 'BIH'}], testDate);
assert.equal(regionalFallback.records.get('360000000004').totalCc, 0.5);
assert.equal(regionalFallback.operatingMarketFallbackCount, 1);
assert.deepEqual(regionalFallback.countryCounts, {HUN: 1});
assert.equal(regionalFallbackUrls.length, 3);

const liveMap = new Map(baseRows.map((row, index) => [row[0], {
    fboId: row[0], personalCc: 1, totalCc: 2, totalActiveCc: 3, nonManagerCc: 4, leadershipCc: 5,
    totalActiveCcYtd: 6, nonManagerCcYtd: 7, leadershipCcYtd: 8 + index * 0,
}]));
const refreshed = refreshDownlineCsv(csvDocument([downlineHeaders, ...baseRows]), liveMap, new Set([baseRows[0][0], baseRows[1][0]]), testDate);
assert.equal(refreshed.summary.rows, 400);
assert.equal(refreshed.summary.liveConfirmed, 400);
assert.equal(refreshed.summary.activeFourCc, 2);
assert.equal(refreshed.summary.personalCc, 400);
const refreshedFirst = parseCsvLine(refreshed.contents.split(/\r?\n/)[1]);
assert.equal(refreshedFirst[6], 'Y');
assert.equal(refreshedFirst[7], '1.000');
assert.equal(refreshedFirst[14], '8.000');
const preservedManagerFieldsMap = new Map(liveMap);
preservedManagerFieldsMap.set(baseRows[0][0], {
    ...preservedManagerFieldsMap.get(baseRows[0][0]),
    personalCc: 0.792,
    totalCc: 0.792,
    totalActiveCc: 0.792,
    nonManagerCc: null,
    leadershipCc: null,
});
const preservedManagerFields = refreshDownlineCsv(csvDocument([downlineHeaders, ...baseRows]), preservedManagerFieldsMap, new Set(), testDate);
const preservedFirst = parseCsvLine(preservedManagerFields.contents.split(/\r?\n/)[1]);
assert.equal(preservedFirst[7], '0.792');
assert.equal(preservedFirst[8], '0.792');
assert.equal(preservedFirst[10], '0.000');
assert.equal(preservedFirst[11], '0.000');

const verified = verifyFccStatus({summary: {members: 401, personal_active: 20, zero_cc: 381, active_4cc: 2, personal_cc: 404.25, goal_current_cc: 63.684, goal_metric_source: 'FLP360 Global Total CC · GLOBAL'}, last_data_import_at: '2026-08-15 10:00:00'}, {members: 401, activeFourCc: 2, personalCc: 404.25, globalTotalCc: 63.684});
assert.equal(verified.members, 401);
assert.equal(verified.globalTotalCc, 63.684);
assert.throws(() => verifyFccStatus({summary: {}}, {members: 401, activeFourCc: 2, personalCc: 404.25, globalTotalCc: 63.684}), /zavr\u0161na kontrola/);

const fourCoreSnapshots = officialFourCoreSnapshots();
assert.equal(fourCoreSnapshots.length, 2);
assert.equal(fourCoreSnapshots.find(snapshot => snapshot.period === '2026-07')?.values.downline.ytd.recruitment, 174);
assert.equal(fourCoreSnapshots.find(snapshot => snapshot.period === '2025-07')?.values.open.month.recruitment, 1);

assert.match(syncSource, /appflp360\.ReportApicategory/);
assert.match(syncSource, /appflp360\.homeCountryCode/);
assert.match(syncSource, /appflp360\.operatingCountryCode/);
assert.match(syncSource, /treeview-cc\?countryCode=/);
assert.match(syncSource, /metric: 'member_cc'/);
assert.match(syncSource, /metric: 'total_cc'/);
assert.match(syncSource, /rewire-earnings-CC-summary/);
assert.match(syncSource, /FCC_SYNC_DRY_RUN/);
assert.match(syncSource, /Kontrolni način rada završen je bez FCC upisa/);
assert.match(syncSource, /uploadRootLiveCc\(rootRecord/);
assert.match(syncSource, /uploadGlobalTotalCc\(ccSummary\.globalTotalCc/);
assert.match(syncSource, /zadnji valjani FCC Focus Group/);
assert.match(syncSource, /args: \['--disable-gpu'\]/);
assert.match(syncSource, /button\[name="login"\]:visible/);
assert.match(syncSource, /force: true, noWaitAfter: true/);
assert.match(syncSource, /process\.env\.PLAYWRIGHT_MODULE_URL \|\| 'playwright'/);
assert.match(syncSource, /process\.env\.PLAYWRIGHT_CHROMIUM_EXECUTABLE/);
assert.doesNotMatch(syncSource, /await requestDownline\(page\)/);
assert.doesNotMatch(syncSource, /await downloadFocusGroup\(page\)/);

if(downlinePath) {
    const result = await validateDownline(downlinePath);
    assert.ok(result.rows >= 400);
    assert.ok(result.hrvRows >= 250);
}
if(focusPath) assert.ok((await validateXlsx(focusPath, 'Focus Group')).bytes > 5000);
if(fourCcPath) assert.ok((await validateXlsx(fourCcPath, '4 CC Active')).bytes > 5000);

console.log('FLP360 cloud sync checks passed.');

/* /Custom code: FC-2026-08-13 */
