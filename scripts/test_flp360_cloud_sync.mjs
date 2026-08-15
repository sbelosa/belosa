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
    extractFourCcRows,
    extractLiveCcRecord,
    extractLiveZeroFallback,
    findCurrentFlpMonthLabel,
    normalizeFboId,
    officialFourCoreSnapshots,
    parseCsvLine,
    parseFlpTimestamp,
    readyReportMessage,
    refreshDownlineCsv,
    reportV2Url,
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
assert.equal(parseFlpTimestamp(1786616559000)?.toISOString(), '2026-08-13T10:22:39.000Z');
assert.equal(parseFlpTimestamp('1786616559000')?.toISOString(), '2026-08-13T10:22:39.000Z');
assert.equal(parseFlpTimestamp('not-a-date'), null);

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
    {fboID: '360000760944', fboName: 'Root', level: 'Manager', homeCountry: 'HRV', personalCC: 4.25, totalActiveCC: 5.5, processingYear: 2026, processingMonth: 8},
    {fboID: '360000000001', fboName: 'Member', level: 'Supervisor', homeCountry: 'HRV', personalCC: 1, totalActiveCC: 4, processingYear: 2026, processingMonth: 8},
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
assert.equal(fallbackRecord.nonManagerCcYtd, null);
assert.throws(() => extractLiveZeroFallback([{
    fboId: '', processingYear: 0, monthlyCCValues: [{processingYear: 0, processingMonth: 0}],
}], {
    distributorId: '360000000003', personalCCCurMonth: 1, totalCCCurMonth: 1, totalActiveCCCurMonth: 1,
}, '360000000003'), /nije sigurna/);

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

const verified = verifyFccStatus({summary: {members: 401, personal_active: 20, zero_cc: 381, active_4cc: 2, personal_cc: 404.25}, last_data_import_at: '2026-08-15 10:00:00'}, {members: 401, activeFourCc: 2, personalCc: 404.25});
assert.equal(verified.members, 401);
assert.throws(() => verifyFccStatus({summary: {}}, {members: 401, activeFourCc: 2, personalCc: 404.25}), /zavr\u0161na kontrola/);

const fourCoreSnapshots = officialFourCoreSnapshots();
assert.equal(fourCoreSnapshots.length, 2);
assert.equal(fourCoreSnapshots.find(snapshot => snapshot.period === '2026-07')?.values.downline.ytd.recruitment, 174);
assert.equal(fourCoreSnapshots.find(snapshot => snapshot.period === '2025-07')?.values.open.month.recruitment, 1);

assert.match(syncSource, /appflp360\.ReportApicategory/);
assert.match(syncSource, /appflp360\.homeCountryCode/);
assert.match(syncSource, /appflp360\.operatingCountryCode/);
assert.match(syncSource, /treeview-cc\?countryCode=/);
assert.match(syncSource, /metric: 'member_cc'/);
assert.match(syncSource, /uploadRootLiveCc\(rootRecord/);
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
