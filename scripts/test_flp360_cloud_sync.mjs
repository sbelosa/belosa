/* Custom code: FC-2026-08-13: Cloud FLP360 synchronization regression checks */

import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import {
    buildDownlineDownloadUrl,
    buildDownlineGenerationUrl,
    buildFourCcCsv,
    canAcceptEmptyFourCc,
    csvDocument,
    currentFlpMonthLabel,
    downlineMemberCount,
    encryptFlpAuthorization,
    applyRegisteredAccountSafetyFloor,
    extractCurrentCcSummary,
    extractFourCcRows,
    extractLiveCcRecord,
    extractLiveMemberReferences,
    extractLiveZeroFallback,
    extractRegisteredStoredMetrics,
    fetchLiveCcSummary,
    fetchLiveCcForMembers,
    findCurrentFlpMonthLabel,
    isClosedReportPeriod,
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
    resolveFccAccountCountryCode,
    resolveLiveCcCountryCode,
    syncRunDate,
    summarizeRegisteredReconciliation,
    validateClosedFourCcNonRegression,
    validateDownline,
    validateFourCcRows,
    validateXlsx,
    verifyFccAccounts,
    verifyFccStatus,
    zagrebPeriod,
    zagrebPeriodParts,
} from './flp360_cloud_sync.mjs';

const downlinePath = process.argv[2];
const focusPath = process.argv[3];
const fourCcPath = process.argv[4];
const testDate = new Date('2026-08-13T19:00:00Z');
const syncSource = await fs.readFile(new URL('./flp360_cloud_sync.mjs', import.meta.url), 'utf8');
const syncWorkflowSource = await fs.readFile(new URL('../.github/workflows/sync-flp360-to-fcc.yml', import.meta.url), 'utf8');

assert.equal(zagrebPeriod(testDate), '2026-08');
assert.equal(syncRunDate('2026-08').toISOString(), '2026-08-31T12:00:00.000Z');
assert.equal(syncRunDate('', testDate), testDate);
assert.throws(() => syncRunDate('08/2026'), /YYYY-MM/);
assert.equal(isClosedReportPeriod('2026-08', new Date('2026-09-01T10:00:00Z')), true);
assert.equal(isClosedReportPeriod('2026-09', new Date('2026-09-01T10:00:00Z')), false);
assert.equal(isClosedReportPeriod('invalid', new Date('2026-09-01T10:00:00Z')), false);
assert.equal(canAcceptEmptyFourCc(testDate, testDate), true);
assert.equal(canAcceptEmptyFourCc(testDate, new Date('2026-09-01T10:00:00Z')), false);
assert.equal(payloadHasExplicitError({body: []}), false);
assert.equal(payloadHasExplicitError({status: 'error', body: []}), true);
assert.equal(payloadHasExplicitError([{success: false, body: []}]), true);
assert.match(syncSource, /FCC_SYNC_REGISTERED_ONLY/);
assert.match(syncSource, /prije upisa ništa nije promijenjeno/);
assert.match(syncSource, /is_closed: isClosed \? 'true' : 'false'/);
assert.match(syncSource, /uploadGlobalTotalCc\(ccSummary\.globalTotalCc, period, syncUrl, syncKey, periodIsClosed\)/);
assert.match(syncWorkflowSource, /reconcile_previous:/);
assert.match(syncWorkflowSource, /FLP360_SYNC_PERIOD: \$\{\{ steps\.periods\.outputs\.previous_period \}\}/);
assert.match(syncWorkflowSource, /FCC_SYNC_HISTORICAL_RECONCILE: '1'/);
assert.equal((syncWorkflowSource.match(/FCC_SYNC_DRY_RUN: \$\{\{ github\.event_name == 'workflow_dispatch' && inputs\.dry_run && '1' \|\| '0' \}\}/g) || []).length, 2);
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
assert.equal(resolveFccAccountCountryCode('BA', {operatingCountryCode: 'HUN'}), 'BIH');
assert.equal(resolveFccAccountCountryCode('HR', {homeCountryCode: 'HRV', operatingCountryCode: 'HUN'}), 'HUN');
assert.equal(resolveFccAccountCountryCode('HRV', {homeCountryCode: 'HRV', operatingCountryCode: 'HUN'}), 'HUN');
assert.equal(resolveFccAccountCountryCode('', {operatingCountryCode: 'HUN'}), 'HUN');
const registeredAccounts = prepareRegisteredFccAccounts({
    status: 'success',
    metric: 'fcc_accounts',
    period: '2026-08-01',
    summary: {unique_forever_ids: 3},
    accounts: [
        {
            fbo_id: '360-001-651-915', country_code: 'BA', active_link_count: 1,
            total_active_cc_ytd: 81.125, non_manager_cc_ytd: 42.5, leadership_cc_ytd: 7.25,
            is_vip_enrolled: true,
        },
        {fbo_id: '360000000002', country_code: 'DE', active_link_count: 2, is_vip_enrolled: false},
        {fbo_id: '360000000099', country_code: 'HRV', active_link_count: 1, is_vip_enrolled: false},
    ],
}, {homeCountryCode: 'HRV', operatingCountryCode: 'HUN'}, '2026-08');
assert.deepEqual(registeredAccounts, [
    {fboId: '360001651915', countryCode: 'BIH', activeLinkCount: 1, totalActiveCcYtd: 81.125, nonManagerCcYtd: 42.5, leadershipCcYtd: 7.25, isVipEnrolled: true},
    {fboId: '360000000002', countryCode: 'DEU', activeLinkCount: 2, totalActiveCcYtd: null, nonManagerCcYtd: null, leadershipCcYtd: null, isVipEnrolled: false},
    {fboId: '360000000099', countryCode: 'HUN', activeLinkCount: 1, totalActiveCcYtd: null, nonManagerCcYtd: null, leadershipCcYtd: null, isVipEnrolled: false},
]);
const storedMetrics = extractRegisteredStoredMetrics({
    status: 'success', metric: 'fcc_accounts', period: '2026-08-01',
    accounts: [
        {
            fbo_id: '360000000099', metric_period: '2026-08-01', personal_cc: 0.287,
            total_cc: 0.287, total_active_cc: 0.287, non_manager_cc: 0, leadership_cc: 0,
            is_4cc_active: false, is_vip_enrolled: false,
        },
        {
            fbo_id: '360000000100', metric_period: '2026-08-01', personal_cc: 1,
            total_cc: 4, total_active_cc: 4, non_manager_cc: 0, leadership_cc: 0,
            is_4cc_active: true, is_vip_enrolled: true,
        },
    ],
}, '2026-08');
const reconciliationExpected = new Map([
    ['360000000099', {
        personalCc: 0.738, totalCc: 0.738, totalActiveCc: 0.738,
        nonManagerCc: 0, leadershipCc: 0, isFourCcActive: false,
    }],
    ['360000000100', {
        personalCc: 1, totalCc: 4, totalActiveCc: 4,
        nonManagerCc: 0, leadershipCc: 0, isFourCcActive: true,
    }],
]);
assert.deepEqual(summarizeRegisteredReconciliation(storedMetrics, reconciliationExpected, '2026-08'), {
    targets: 2,
    exactStoredPeriod: 2,
    missingStoredPeriod: 0,
    metricMismatches: 1,
    personalCcMismatches: 1,
    personalCcIncreases: 1,
    personalCcDecreases: 0,
    fourCcMismatches: 0,
    qualifiedButNotEnrolled: 1,
});
assert.deepEqual(validateClosedFourCcNonRegression(storedMetrics, new Set(['360000000100']), '2026-08'), {
    storedActive: 1,
    liveActive: 1,
});
assert.throws(
    () => validateClosedFourCcNonRegression(storedMetrics, new Set(), '2026-08'),
    /izgubio je 1 prethodno potvrđenih/
);
assert.throws(() => prepareRegisteredFccAccounts({
    status: 'success', metric: 'fcc_accounts', period: '2026-08-01', summary: {unique_forever_ids: 2},
    accounts: [
        {fbo_id: '360000000001', country_code: 'HR', active_link_count: 1, is_vip_enrolled: false},
        {fbo_id: '360000000001', country_code: 'HR', active_link_count: 1, is_vip_enrolled: false},
    ],
}, {operatingCountryCode: 'HUN'}, '2026-08'), /dupliciran/);
assert.throws(() => prepareRegisteredFccAccounts({
    status: 'success', metric: 'fcc_accounts', period: '2026-08-01', summary: {unique_forever_ids: 1},
    accounts: [{fbo_id: '000000360790', country_code: 'RS', active_link_count: 1}],
}, {operatingCountryCode: 'HUN'}, '2026-08'), /neispravan/);
assert.throws(() => prepareRegisteredFccAccounts({
    status: 'success', metric: 'fcc_accounts', period: '2026-08-01', summary: {unique_forever_ids: 1},
    accounts: [{fbo_id: '360000000003', country_code: 'HR', active_link_count: 1, is_vip_enrolled: null}],
}, {operatingCountryCode: 'HUN'}, '2026-08'), /VIP status/);
assert.throws(() => prepareRegisteredFccAccounts({
    status: 'success', metric: 'fcc_accounts', period: '2025-12-01', summary: {unique_forever_ids: 0}, accounts: [],
}, {operatingCountryCode: 'HUN'}, '2026-01'), /izvještajnom razdoblju/);
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
assert.throws(() => extractCurrentCcSummary([
    {processingYear: 2026, processingMonth: 8, valueType: 'Monthly', totalCC: 1, globalTotalCC: 2},
    {processingYear: 2026, processingMonth: 8, valueType: 'monthly', totalCC: 1, globalTotalCC: 2},
], testDate), /duplicirano aktualno razdoblje/);
assert.throws(() => extractCurrentCcSummary({
    status: 'error', body: [
        {processingYear: 2026, processingMonth: 8, valueType: 'Monthly', totalCC: 1, globalTotalCC: 2},
    ],
}, testDate), /nije jednoznačan/);
assert.throws(
    () => extractCurrentCcSummary({status: 'error'}, testDate),
    error => error?.ccSummaryReasonCode === 'summary_upstream_error',
);
const sensitiveSummaryDiagnosticValue = 'must-never-appear-in-logs';
const sensitiveSummaryStatusCode = 360001651915;
assert.throws(
    () => extractCurrentCcSummary({
        status: 'success',
        statusCode: sensitiveSummaryStatusCode,
        error: {message: sensitiveSummaryDiagnosticValue},
        message: sensitiveSummaryDiagnosticValue,
    }, testDate),
    error => error?.message.includes('Sanitizirana struktura:')
        && !error.message.includes(sensitiveSummaryDiagnosticValue)
        && !error.message.includes(String(sensitiveSummaryStatusCode)),
);
assert.throws(
    () => extractCurrentCcSummary({
        status: 'success',
        statusCode: {[sensitiveSummaryDiagnosticValue]: sensitiveSummaryDiagnosticValue},
        error: true,
    }, testDate),
    error => error?.message.includes('"statusCode":"object:nonempty"')
        && !error.message.includes(sensitiveSummaryDiagnosticValue),
);
assert.throws(
    () => extractCurrentCcSummary([
        {foo: 'a'}, {foo: 'b'}, {foo: 'c'}, {status: 'error'}, {foo: 'd'}, {foo: 'e'},
    ], testDate),
    error => error?.message.includes('Sanitizirana struktura:')
        && error.message.includes('"index":3'),
);
const validSummaryRow = {
    processingYear: 2026, processingMonth: 8, valueType: 'Monthly', totalCC: 0, globalTotalCC: 0,
};
const neutralSummaryRowMetadata = {statusCode: null, success: false, error: null};
assert.deepEqual(extractCurrentCcSummary([
    {
        processingYear: 2026, processingMonth: 0, valueType: 'Yearly', totalCC: 10, globalTotalCC: 20,
        ...neutralSummaryRowMetadata,
    },
    {...validSummaryRow, ...neutralSummaryRowMetadata, statusCode: {}},
], testDate), {totalCc: 0, globalTotalCc: 0});
for(const invalidNeutralMetadata of [
    {statusCode: {code: 500}, success: false, error: null},
    {statusCode: [], success: false, error: null},
    {statusCode: 200, success: false, error: null},
    {statusCode: null, success: true, error: null},
    {statusCode: null, success: false, error: {message: 'failure'}},
    {statusCode: null, success: false, error: null, status: 'error'},
    {statusCode: null, success: false, error: null, errors: []},
    {statusCode: null, success: false, error: null, message: 'failure'},
]) {
    assert.throws(
        () => extractCurrentCcSummary([{...validSummaryRow, ...invalidNeutralMetadata}], testDate),
        /nije jednoznačan/,
    );
}
assert.throws(() => extractCurrentCcSummary([
    {...validSummaryRow, ...neutralSummaryRowMetadata},
    {...validSummaryRow, processingMonth: 7},
], testDate), /nije jednoznačan/);
for(const ambiguousSummaryPayload of [
    [{body: [validSummaryRow]}, {status: 'error'}],
    [validSummaryRow, {status: 'error'}],
    {body: [validSummaryRow], data: [{status: 'error'}]},
    [validSummaryRow, {foo: 'bar'}],
    [validSummaryRow, []],
    [validSummaryRow, {processingYear: 'bad', processingMonth: 8, valueType: 'Monthly'}],
    {body: [validSummaryRow], processingYear: 2026},
    [{body: [validSummaryRow], totalCC: 0}],
    [{...validSummaryRow, valueType: ['Monthly']}],
    {body: [validSummaryRow], errors: 'upstream failure'},
    {body: [validSummaryRow], statusCode: 500},
]) {
    assert.throws(() => extractCurrentCcSummary(ambiguousSummaryPayload, testDate), /jednoznačan|poruku o pogrešci/);
}
for(const invalidSummaryValue of [undefined, false, '', ' ', -1]) {
    assert.throws(() => extractCurrentCcSummary([{...validSummaryRow, totalCC: invalidSummaryValue}], testDate), /nema valjano polje/);
    assert.throws(() => extractCurrentCcSummary([{...validSummaryRow, globalTotalCC: invalidSummaryValue}], testDate), /nema valjano polje/);
}
assert.throws(() => extractCurrentCcSummary([{...validSummaryRow, totalCC: null}], testDate), /localni Total CC nije dostupan/);
assert.throws(() => extractCurrentCcSummary([{...validSummaryRow, globalTotalCC: null}], testDate), /Global Total CC nije dostupan/);
const summaryRolloverDate = new Date('2026-09-01T12:00:00Z');
const verifiedSummaryRootZero = {
    personalCc: 0, totalCc: 0, totalActiveCc: 0, nonManagerCc: 0, leadershipCc: 0,
    usedVerifiedCurrentZero: true,
};
const unavailableLocalSummaryRow = {
    processingYear: 2026, processingMonth: 9, valueType: 'Monthly', totalCC: null, globalTotalCC: 0,
};
assert.deepEqual(extractCurrentCcSummary([unavailableLocalSummaryRow], summaryRolloverDate, {
    rootRecord: verifiedSummaryRootZero, currentDate: summaryRolloverDate,
}), {totalCc: 0, globalTotalCc: 0, usedRootCorroboratedLocalTotal: true});
assert.deepEqual(extractCurrentCcSummary([{
    ...unavailableLocalSummaryRow,
    ...neutralSummaryRowMetadata,
}], summaryRolloverDate, {
    rootRecord: verifiedSummaryRootZero, currentDate: summaryRolloverDate,
}), {totalCc: 0, globalTotalCc: 0, usedRootCorroboratedLocalTotal: true});
assert.deepEqual(extractCurrentCcSummary([{...unavailableLocalSummaryRow, globalTotalCC: null}], summaryRolloverDate, {
    rootRecord: verifiedSummaryRootZero, currentDate: summaryRolloverDate,
}), {
    totalCc: 0, globalTotalCc: null,
    usedRootCorroboratedLocalTotal: true, globalTotalUnavailable: true,
});
for(const unsafeRootRecord of [
    {...verifiedSummaryRootZero, usedVerifiedCurrentZero: false},
    {...verifiedSummaryRootZero, totalCc: 0.001},
    {...verifiedSummaryRootZero, leadershipCc: null},
]) {
    assert.throws(() => extractCurrentCcSummary([unavailableLocalSummaryRow], summaryRolloverDate, {
        rootRecord: unsafeRootRecord, currentDate: summaryRolloverDate,
    }), /nije dostupan/);
}
assert.throws(() => extractCurrentCcSummary([unavailableLocalSummaryRow], summaryRolloverDate, {
    rootRecord: verifiedSummaryRootZero, currentDate: new Date('2026-10-01T12:00:00Z'),
}), /nije dostupan/);
assert.throws(() => extractCurrentCcSummary([{...unavailableLocalSummaryRow, totalCC: 0, globalTotalCC: null}], summaryRolloverDate, {
    rootRecord: verifiedSummaryRootZero, currentDate: summaryRolloverDate,
}), /Global Total CC nije dostupan/);

const ccSummaryRequestUrls = [];
const ccSummaryResponses = [{status: 'error'}, [unavailableLocalSummaryRow]];
const ccSummaryPage = {
    context: () => ({request: {get: async requestUrl => {
        ccSummaryRequestUrls.push(String(requestUrl));
        const payload = ccSummaryResponses.shift();
        return {ok: () => true, text: async () => JSON.stringify(payload)};
    }}}),
    waitForTimeout: async () => {},
};
assert.deepEqual(await fetchLiveCcSummary(ccSummaryPage, {
    reportBase: 'https://example.test/reporttdm', operatingCountryCode: 'HUN',
    guestToken: 'token', aesEncryptionKey: '0123456789abcdef',
}, summaryRolloverDate, verifiedSummaryRootZero, {currentDate: summaryRolloverDate}), {
    totalCc: 0, globalTotalCc: 0, usedRootCorroboratedLocalTotal: true,
});
assert.match(ccSummaryRequestUrls[0], /month\/1\/rewire-earnings-CC-summary/);
assert.match(ccSummaryRequestUrls[1], /month\/9\/rewire-earnings-CC-summary/);
for(const invalidAnnualSummary of [
    {body: [validSummaryRow], data: [{status: 'error'}]},
    [{body: [validSummaryRow]}, {status: 'error'}],
    {body: [validSummaryRow], statusCode: 'malformed'},
    {body: [validSummaryRow], errors: false},
    {statusCode: 500, status: 'success'},
    {statusCode: 200, status: 'error'},
    {status: 'error', success: true},
    {statusCode: 500, errors: {malformed: true}},
    {body: [{...validSummaryRow, ...neutralSummaryRowMetadata}]},
]) {
    const invalidAnnualUrls = [];
    const invalidAnnualPage = {
        context: () => ({request: {get: async requestUrl => {
            invalidAnnualUrls.push(String(requestUrl));
            return {ok: () => true, text: async () => JSON.stringify(invalidAnnualSummary)};
        }}}),
        waitForTimeout: async () => {},
    };
    await assert.rejects(fetchLiveCcSummary(invalidAnnualPage, {
        reportBase: 'https://example.test/reporttdm', operatingCountryCode: 'HUN',
        guestToken: 'token', aesEncryptionKey: '0123456789abcdef',
    }, summaryRolloverDate, verifiedSummaryRootZero, {currentDate: summaryRolloverDate}), /nije jednoznačan/);
    assert.equal(invalidAnnualUrls.length, 1);
    assert.match(invalidAnnualUrls[0], /month\/1\/rewire-earnings-CC-summary/);
}

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
const emptyFourCc = validateFourCcRows([], testDate, {allowEmpty: true});
assert.equal(emptyFourCc.rows, 0);
assert.equal(emptyFourCc.ids.size, 0);
for(const invalidFourCcValue of [null, '', ' ', false]) {
    assert.throws(() => validateFourCcRows([{
        ...fourCcRows[0], personalCC: invalidFourCcValue,
    }], testDate), /nema valjano polje/);
}
assert.throws(() => validateFourCcRows([{
    ...fourCcRows[0], processingMonth: true,
}], new Date('2026-01-15T12:00:00Z')), /aktualno razdoblje/);

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
assert.equal(extractLiveCcRecord({
    fboId: '360000000001', processingYear: 2026, totalActiveCC: 20, nonManagerCC: 30, leaderCC: 40,
    monthlyCCValues: [{processingYear: 2026, processingMonth: 8, personalCCMTD: 1, totalCCMTD: 2, totalActiveCCMTD: 3, nonManagerCCMTD: 4, leaderCC: 5}],
}, '360000000001', testDate).totalCc, 2);
for(const invalidCurrentValue of [null, '', ' ', false]) {
    assert.throws(() => extractLiveCcRecord([{
        fboId: '360000000001', processingYear: 2026, totalActiveCC: 20, nonManagerCC: 30, leaderCC: 40,
        monthlyCCValues: [{
            processingYear: 2026, processingMonth: 8, personalCCMTD: invalidCurrentValue,
            totalCCMTD: 2, totalActiveCCMTD: 3, nonManagerCCMTD: 4, leaderCC: 5,
        }],
    }], '360000000001', testDate), /nema valjano polje/);
}
assert.throws(() => extractLiveCcRecord([
    {
        fboId: '360000000001', processingYear: 2026, totalActiveCC: 20, nonManagerCC: 30, leaderCC: 40,
        monthlyCCValues: [{processingYear: 2026, processingMonth: 8, personalCCMTD: 1, totalCCMTD: 2, totalActiveCCMTD: 3, nonManagerCCMTD: 4, leaderCC: 5}],
    },
    {
        fboId: '360000000001', processingYear: 2026, totalActiveCC: 20, nonManagerCC: 30, leaderCC: 40,
        monthlyCCValues: [{processingYear: 2026, processingMonth: 8, personalCCMTD: 1, totalCCMTD: 2, totalActiveCCMTD: 3, nonManagerCCMTD: 4, leaderCC: 5}],
    },
], '360000000001', testDate), /nije potvrdio FBO ID/);
assert.throws(() => extractLiveCcRecord({success: false, data: [{
    fboId: '360000000001', processingYear: 2026, totalActiveCC: 20, nonManagerCC: 30, leaderCC: 40,
    monthlyCCValues: [{processingYear: 2026, processingMonth: 8, personalCCMTD: 1, totalCCMTD: 2, totalActiveCCMTD: 3, nonManagerCCMTD: 4, leaderCC: 5}],
}]}, '360000000001', testDate), /poruku o pogrešci/);
assert.throws(() => extractLiveCcRecord({
    status: 'error', fboId: '360000000001', processingYear: 2026,
}, '360000000001', testDate), /poruku o pogrešci/);
assert.throws(() => extractLiveCcRecord({
    data: [{
        fboId: '360000000001', processingYear: 2026, totalActiveCC: 20, nonManagerCC: 30, leaderCC: 40,
        monthlyCCValues: [{processingYear: 2026, processingMonth: 8, personalCCMTD: 1, totalCCMTD: 2, totalActiveCCMTD: 3, nonManagerCCMTD: 4, leaderCC: 5}],
    }],
    body: [{status: 'error'}],
}, '360000000001', testDate), /nije potvrdio FBO ID/);
const newMonthZeroRecord = extractLiveCcRecord([{
    fboId: '360000000002', processingYear: 0, totalActiveCC: 81.125, nonManagerCC: 42.5, leaderCC: 7.25,
    monthlyCCValues: [{processingYear: 0, processingMonth: 0}],
}], '360000000002', testDate);
assert.equal(newMonthZeroRecord.totalCc, 0);
assert.equal(newMonthZeroRecord.totalActiveCcYtd, 81.125);
assert.equal(newMonthZeroRecord.nonManagerCcYtd, 42.5);
assert.equal(newMonthZeroRecord.leadershipCcYtd, 7.25);
assert.throws(() => extractLiveCcRecord([{
    fboId: '360000000002', processingYear: null, totalActiveCC: 81.125, nonManagerCC: 42.5, leaderCC: 7.25,
    monthlyCCValues: [{processingYear: null, processingMonth: null}],
}], '360000000002', testDate), /nema potvrđeno razdoblje/);
const fallbackRecord = extractLiveZeroFallback([{
    fboId: '', processingYear: 0, monthlyCCValues: [{processingYear: 0, processingMonth: 0}],
}], {
    distributorId: '360000000003', personalCCCurMonth: 0, totalCCCurMonth: 0,
    totalActiveCCCurMonth: 0, totalActiveCCYTD: 1.024,
}, '360000000003');
assert.equal(fallbackRecord.totalActiveCcYtd, 1.024);
assert.equal(fallbackRecord.nonManagerCc, 0);
assert.equal(fallbackRecord.leadershipCc, 0);
assert.equal(fallbackRecord.nonManagerCcYtd, null);
assert.equal(fallbackRecord.usedVerifiedCurrentZero, true);
assert.throws(() => extractLiveZeroFallback([{
    fboId: '', processingYear: 0, monthlyCCValues: [{processingYear: 0, processingMonth: 0}],
}], {
    distributorId: '360000000003', personalCCCurMonth: 0, totalCCCurMonth: 0,
    totalActiveCCCurMonth: 0, totalActiveCCYTD: 1.024,
}, '360000000003', new Date('2026-08-31T12:00:00Z'), new Date('2026-09-01T12:00:00Z')), /povijesno razdoblje/);
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
assert.equal(internationalFallback.leadershipCc, null);
assert.equal(internationalFallback.usedVerifiedCurrentZero, false);

const septemberDate = new Date('2026-09-01T12:00:00Z');
const emptyTreeSentinel = [{
    fboId: '', processingYear: 0, processingMonth: 0,
    monthlyCCValues: [{processingYear: 0, processingMonth: 0}],
}];
const priorOnlyTree = [{
    fboId: '360000000005', processingYear: 2026,
    monthlyCCValues: [{processingYear: 2026, processingMonth: 8}],
}];
const priorOnlyDetail = [{
    distributorId: '360000000005', personalCCCurMonth: 0.125, totalCCCurMonth: 0.25,
    totalActiveCCCurMonth: 0.5,
}];
const exactPriorOnlyFallback = extractLiveZeroFallback(
    priorOnlyTree, priorOnlyDetail, '360000000005', septemberDate, septemberDate
);
assert.equal(exactPriorOnlyFallback.personalCc, 0.125);
assert.equal(exactPriorOnlyFallback.totalCc, 0.25);
assert.equal(exactPriorOnlyFallback.totalActiveCc, 0.5);
assert.equal(exactPriorOnlyFallback.totalActiveCcYtd, null);
assert.equal(exactPriorOnlyFallback.nonManagerCcYtd, null);
const allNullCurrentFallback = extractLiveZeroFallback(priorOnlyTree, [{
    distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null, nonManagerCCCurMonth: 0, leaderCCCurMonth: 0,
    totalActiveCCYTD: 81.125,
}], '360000000005', septemberDate, septemberDate);
assert.equal(allNullCurrentFallback.personalCc, 0);
assert.equal(allNullCurrentFallback.totalCc, 0);
assert.equal(allNullCurrentFallback.totalActiveCc, 0);
assert.equal(allNullCurrentFallback.nonManagerCc, 0);
assert.equal(allNullCurrentFallback.leadershipCc, 0);
assert.equal(allNullCurrentFallback.totalActiveCcYtd, 81.125);
assert.equal(allNullCurrentFallback.usedNullCurrentSentinel, true);
assert.equal(allNullCurrentFallback.usedVerifiedCurrentZero, true);
const allNullWithTreeYtd = extractLiveZeroFallback([{
    ...priorOnlyTree[0], totalActiveCC: 70, nonManagerCC: 40, leaderCC: 6,
}], [{
    distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null,
}], '360000000005', septemberDate, septemberDate);
assert.equal(allNullWithTreeYtd.totalActiveCcYtd, 70);
assert.equal(allNullWithTreeYtd.nonManagerCcYtd, 40);
assert.equal(allNullWithTreeYtd.leadershipCcYtd, 6);
const explicitZeroWithTreeYtd = extractLiveZeroFallback([{
    ...priorOnlyTree[0], totalActiveCC: 70, nonManagerCC: 40, leaderCC: 6,
}], [{
    distributorId: '360000000005', personalCCCurMonth: 0, totalCCCurMonth: 0,
    totalActiveCCCurMonth: 0, totalActiveCCYTD: 70,
}], '360000000005', septemberDate, septemberDate);
assert.equal(explicitZeroWithTreeYtd.nonManagerCc, 0);
assert.equal(explicitZeroWithTreeYtd.leadershipCc, 0);
assert.equal(explicitZeroWithTreeYtd.totalActiveCcYtd, 70);
assert.equal(explicitZeroWithTreeYtd.nonManagerCcYtd, 40);
assert.equal(explicitZeroWithTreeYtd.leadershipCcYtd, 6);
assert.equal(explicitZeroWithTreeYtd.usedVerifiedCurrentZero, true);
assert.equal(extractLiveZeroFallback([{
    ...priorOnlyTree[0], totalActiveCC: 100, nonManagerCC: 40, leaderCC: 6,
}], [{
    distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null, totalActiveCCYTD: 100,
}], '360000000005', septemberDate, septemberDate).totalActiveCcYtd, 100);
assert.throws(() => extractLiveZeroFallback([{
    ...priorOnlyTree[0], totalActiveCC: 70, nonManagerCC: 40, leaderCC: 6,
}], [{
    distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null, totalActiveCCYTD: 81.125,
}], '360000000005', septemberDate, septemberDate), error => error?.liveCcReasonCode === 'detail_tree_ytd_mismatch');
assert.throws(() => extractLiveZeroFallback([{
    ...priorOnlyTree[0], totalActiveCC: 70, nonManagerCC: 40, leaderCC: 6,
}], [{
    distributorId: '360000000005', personalCCCurMonth: 0, totalCCCurMonth: 0,
    totalActiveCCCurMonth: 0, totalActiveCCYTD: 70.002,
}], '360000000005', septemberDate, septemberDate), error => error?.liveCcReasonCode === 'detail_tree_ytd_mismatch');
assert.equal(extractLiveZeroFallback([{
    ...priorOnlyTree[0], totalActiveCC: 70, nonManagerCC: 40, leaderCC: 6,
}], [{
    ...priorOnlyDetail[0], totalActiveCCYTD: 81.125,
}], '360000000005', septemberDate, septemberDate).totalActiveCcYtd, 81.125);
const wrongParentYearYtd = extractLiveZeroFallback([{
    ...priorOnlyTree[0], processingYear: 2025, totalActiveCC: 70, nonManagerCC: 40, leaderCC: 6,
}], [{
    distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null,
}], '360000000005', septemberDate, septemberDate);
assert.equal(wrongParentYearYtd.totalActiveCcYtd, null);
assert.equal(wrongParentYearYtd.nonManagerCcYtd, null);
assert.equal(wrongParentYearYtd.leadershipCcYtd, null);
assert.throws(() => extractLiveZeroFallback([{
    ...priorOnlyTree[0], totalActiveCC: ' ', nonManagerCC: 40, leaderCC: 6,
}], [{
    distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null,
}], '360000000005', septemberDate, septemberDate), /nema valjano polje totalActiveCC/);
for(const invalidParentYtd of [-1, false]) {
    assert.throws(() => extractLiveZeroFallback([{
        ...priorOnlyTree[0], totalActiveCC: 70, nonManagerCC: invalidParentYtd, leaderCC: 6,
    }], [{
        distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
        totalActiveCCCurMonth: null,
    }], '360000000005', septemberDate, septemberDate), /nema valjano polje nonManagerCC/);
}
for(const invalidManagerCurrent of [0.5, ' ', false, -1]) {
    assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{
        distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
        totalActiveCCCurMonth: null, nonManagerCCCurMonth: invalidManagerCurrent,
    }], '360000000005', septemberDate, septemberDate));
}
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{
    distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null, leaderCCCurMonth: 0, leadershipCCCurMonth: 1,
}], '360000000005', septemberDate, septemberDate), error => error?.liveCcReasonCode === 'detail_current_cc_submetric_conflict');
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{
    distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null, leaderCCCurMonth: 0, leadershipCCCurMonth: 0.001,
}], '360000000005', septemberDate, septemberDate), error => error?.liveCcReasonCode === 'detail_current_cc_submetric_conflict');
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{
    distributorId: '360000000005', personalCCCurMonth: 0, totalCCCurMonth: 0,
    totalActiveCCCurMonth: 0, nonManagerCCCurMonth: 0.001,
}], '360000000005', septemberDate, septemberDate), error => error?.liveCcReasonCode === 'detail_current_cc_submetric_conflict');
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{
    distributorId: '360000999999', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null,
}], '360000000005', septemberDate, septemberDate), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{
    distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null,
}], '360000000005', new Date('2026-08-31T12:00:00Z'), septemberDate), /povijesno razdoblje/);
assert.equal(extractLiveZeroFallback(emptyTreeSentinel, [{
    distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
    totalActiveCCCurMonth: null,
}], '360000000005', septemberDate, septemberDate).totalCc, 0);
for(const nullFields of [
    ['personalCCCurMonth'],
    ['totalCCCurMonth'],
    ['totalActiveCCCurMonth'],
    ['personalCCCurMonth', 'totalCCCurMonth'],
]) {
    const mixedDetail = {
        distributorId: '360000000005', personalCCCurMonth: 0, totalCCCurMonth: 0,
        totalActiveCCCurMonth: 0,
    };
    for(const field of nullFields) mixedDetail[field] = null;
    assert.throws(
        () => extractLiveZeroFallback(priorOnlyTree, [mixedDetail], '360000000005', septemberDate, septemberDate),
        error => error?.liveCcReasonCode === 'detail_current_cc_mixed_null'
    );
}
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{
    distributorId: '360000000005', personalCCCurMonth: 0, totalCCCurMonth: 0,
}], '360000000005', septemberDate, septemberDate), error => error?.liveCcReasonCode === 'detail_current_cc_missing');
assert.throws(() => extractLiveZeroFallback([{
    fboId: '', processingYear: null, processingMonth: null,
    monthlyCCValues: [{processingYear: null, processingMonth: null}],
}], priorOnlyDetail, '360000000005', septemberDate, septemberDate), /nije sigurna/);
assert.equal(extractLiveZeroFallback(
    priorOnlyTree[0], {data: priorOnlyDetail[0]}, '360000000005', septemberDate, septemberDate
).personalCc, 0.125);
assert.equal(extractLiveZeroFallback(
    {data: priorOnlyTree[0]}, priorOnlyDetail[0], '360000000005', septemberDate, septemberDate
).totalCc, 0.25);
assert.equal(extractLiveZeroFallback(
    {body: priorOnlyTree[0]}, {body: priorOnlyDetail[0]}, '360000000005', septemberDate, septemberDate
).totalActiveCc, 0.5);
assert.equal(extractLiveZeroFallback(
    [{body: [priorOnlyTree[0]]}], [{data: [priorOnlyDetail[0]]}], '360000000005', septemberDate, septemberDate
).totalCc, 0.25);
assert.throws(() => extractLiveZeroFallback(emptyTreeSentinel, [], '360000000005', septemberDate, septemberDate), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{status: 'error'}], '360000000005', septemberDate, septemberDate), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{message: 'not found'}], '360000000005', septemberDate, septemberDate), /nije sigurna/);
for(const invalidCurrentField of ['personalCCCurMonth', 'totalCCCurMonth', 'totalActiveCCCurMonth']) {
    for(const invalidCurrentValue of [' ', false, -1]) {
        assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{
            ...priorOnlyDetail[0], [invalidCurrentField]: invalidCurrentValue,
        }], '360000000005', septemberDate, septemberDate), /nema valjano polje/);
    }
}
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [priorOnlyDetail[0], priorOnlyDetail[0]], '360000000005', septemberDate, septemberDate), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback(
    [{body: []}], priorOnlyDetail, '360000000005', septemberDate, septemberDate
), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback(
    [{data: [priorOnlyTree[0], priorOnlyTree[0]]}], priorOnlyDetail,
    '360000000005', septemberDate, septemberDate
), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, {
    data: {}, ...priorOnlyDetail[0],
}, '360000000005', septemberDate, septemberDate), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, {
    data: priorOnlyDetail[0], distributorId: '360000000005',
}, '360000000005', septemberDate, septemberDate), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback({
    data: priorOnlyTree[0], fboId: '360000000005',
}, priorOnlyDetail, '360000000005', septemberDate, septemberDate), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback(
    [{body: [{status: 'error', ...priorOnlyTree[0]}]}], priorOnlyDetail,
    '360000000005', septemberDate, septemberDate
), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback({
    data: priorOnlyTree, body: [{status: 'error'}],
}, priorOnlyDetail, '360000000005', septemberDate, septemberDate), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback(priorOnlyTree, [{
    distributorId: '360000999999', personalCCCurMonth: 0, totalCCCurMonth: 0, totalActiveCCCurMonth: 0,
}], '360000000005', septemberDate, septemberDate), /nije sigurna/);
assert.throws(() => extractLiveZeroFallback([{
    fboId: '360000000005', processingYear: 2026,
    monthlyCCValues: [{processingYear: 2026, processingMonth: 7}],
}], priorOnlyDetail, '360000000005', septemberDate, septemberDate), /nije sigurna/);

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
assert.equal(downlineMemberCount(csvDocument([downlineHeaders, ...baseRows])), 400);
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
    ['360000000099', {countryCode: 'HUN', personalCc: 0.738, totalCc: 0.738}],
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
    registeredAccounts[2],
], testDate);
assert.equal(liveByHomeCountry.records.get('360000000002').totalCc, 0.792);
assert.equal(liveByHomeCountry.records.get('360000000099').personalCc, 0.738);
assert.deepEqual(liveByHomeCountry.countryCounts, {DEU: 1, HRV: 1, HUN: 1});
assert.equal(requestedLiveUrls.length, 3);
const registeredHrvRequest = new URL(requestedLiveUrls.find(url => url.includes('360000000099')));
assert.equal(registeredHrvRequest.searchParams.get('countryCode'), 'HUN');
assert.equal(requestedLiveUrls.some(url => url.includes('360000000099') && url.includes('countryCode=HRV')), false);
await assert.rejects(() => fetchLiveCcForMembers(fakeLivePage, {
    reportBase: 'https://example.test/api/reporttdmpro',
    aesEncryptionKey: '0123456789abcdef',
    guestToken: 'test-token',
    operatingCountryCode: 'HUN',
}, [{fboId: '360000000003', countryCode: ''}], testDate), /matičnu zemlju/);
const toleratedUnconfirmed = await fetchLiveCcForMembers(fakeLivePage, {
    reportBase: 'https://example.test/api/reporttdmpro',
    aesEncryptionKey: '0123456789abcdef',
    guestToken: 'test-token',
    operatingCountryCode: 'HUN',
}, [{fboId: '360000000003', countryCode: 'HRV', countryCandidates: ['DEU']}], testDate, {allowUnconfirmed: true});
assert.equal(toleratedUnconfirmed.records.size, 0);
assert.equal(toleratedUnconfirmed.unconfirmed.length, 1);

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

const priorOnlyFallbackUrls = [];
const priorOnlyFallbackPage = {
    context: () => ({request: {get: async requestUrl => {
        priorOnlyFallbackUrls.push(requestUrl);
        const isDetail = new URL(requestUrl).pathname.includes('downlineLoggedInDetails');
        return {
            ok: () => true,
            status: () => 200,
            text: async () => JSON.stringify(isDetail ? priorOnlyDetail : priorOnlyTree),
        };
    }}}),
    waitForTimeout: async () => {},
};
const priorOnlyLive = await fetchLiveCcForMembers(priorOnlyFallbackPage, {
    reportBase: 'https://example.test/api/reporttdmpro',
    aesEncryptionKey: '0123456789abcdef',
    guestToken: 'test-token',
    operatingCountryCode: 'HUN',
}, [{
    fboId: '360000000005', countryCode: 'HUN', totalActiveCcYtd: 81.125,
    nonManagerCcYtd: 42.5, leadershipCcYtd: 7.25, isVipEnrolled: true,
}], septemberDate, {currentDate: septemberDate});
assert.equal(priorOnlyLive.records.get('360000000005').personalCc, 0.125);
assert.equal(priorOnlyLive.records.get('360000000005').totalActiveCcYtd, 81.125);
assert.equal(priorOnlyLive.records.get('360000000005').mustRemainVipEnrolled, true);
assert.equal(priorOnlyLive.fallbackCount, 1);
assert.equal(priorOnlyLive.nullCurrentMonthCount, 0);
assert.equal(priorOnlyLive.zeroCurrentMonthCount, 0);
assert.equal(priorOnlyLive.ytdFloorAccountCount, 1);
assert.equal(priorOnlyLive.ytdFloorFieldCount, 3);
assert.equal(priorOnlyFallbackUrls.length, 2);

const allNullCurrentPage = {
    context: () => ({request: {get: async requestUrl => ({
        ok: () => true,
        status: () => 200,
        text: async () => JSON.stringify(new URL(requestUrl).pathname.includes('downlineLoggedInDetails')
            ? [{
                distributorId: '360000000005', personalCCCurMonth: null, totalCCCurMonth: null,
                totalActiveCCCurMonth: null, totalActiveCCYTD: 70,
            }]
            : [{
                ...priorOnlyTree[0], totalActiveCC: 70, nonManagerCC: 40, leaderCC: 6,
            }]),
    })}}),
    waitForTimeout: async () => {},
};
const allNullCurrentLive = await fetchLiveCcForMembers(allNullCurrentPage, {
    reportBase: 'https://example.test/api/reporttdmpro',
    aesEncryptionKey: '0123456789abcdef',
    guestToken: 'test-token',
    operatingCountryCode: 'HUN',
}, [{
    fboId: '360000000005', countryCode: 'HUN', totalActiveCcYtd: 81.125,
    nonManagerCcYtd: 42.5, leadershipCcYtd: 7.25, isVipEnrolled: true,
}], septemberDate, {currentDate: septemberDate});
const allNullLiveRecord = allNullCurrentLive.records.get('360000000005');
assert.equal(allNullLiveRecord.personalCc, 0);
assert.equal(allNullLiveRecord.totalCc, 0);
assert.equal(allNullLiveRecord.totalActiveCc, 0);
assert.equal(allNullLiveRecord.nonManagerCc, 0);
assert.equal(allNullLiveRecord.leadershipCc, 0);
assert.equal(allNullLiveRecord.totalActiveCcYtd, 81.125);
assert.equal(allNullLiveRecord.nonManagerCcYtd, 42.5);
assert.equal(allNullLiveRecord.leadershipCcYtd, 7.25);
assert.equal(allNullLiveRecord.mustRemainVipEnrolled, true);
assert.equal(allNullCurrentLive.fallbackCount, 1);
assert.equal(allNullCurrentLive.nullCurrentMonthCount, 1);
assert.equal(allNullCurrentLive.zeroCurrentMonthCount, 1);
assert.equal(allNullCurrentLive.ytdFloorAccountCount, 1);
assert.equal(allNullCurrentLive.ytdFloorFieldCount, 3);

const flooredRecord = applyRegisteredAccountSafetyFloor({
    fboId: '360000000005', totalActiveCcYtd: 10, nonManagerCcYtd: null, leadershipCcYtd: 9,
}, {
    totalActiveCcYtd: 11, nonManagerCcYtd: 8, leadershipCcYtd: 7, isVipEnrolled: true,
});
assert.equal(flooredRecord.record.totalActiveCcYtd, 11);
assert.equal(flooredRecord.record.nonManagerCcYtd, 8);
assert.equal(flooredRecord.record.leadershipCcYtd, 9);
assert.equal(flooredRecord.record.mustRemainVipEnrolled, true);
assert.equal(flooredRecord.ytdFloorFields, 2);

const malformedDetailPage = {
    context: () => ({request: {get: async requestUrl => ({
        ok: () => true,
        status: () => 200,
        text: async () => JSON.stringify(new URL(requestUrl).pathname.includes('downlineLoggedInDetails')
            ? [{message: 'not found'}]
            : priorOnlyTree),
    })}}),
    waitForTimeout: async () => {},
};
const rejectedMalformedDetail = await fetchLiveCcForMembers(malformedDetailPage, {
    reportBase: 'https://example.test/api/reporttdmpro',
    aesEncryptionKey: '0123456789abcdef',
    guestToken: 'test-token',
    operatingCountryCode: 'HUN',
}, [{fboId: '360000000005', countryCode: 'HUN'}], septemberDate, {allowUnconfirmed: true, currentDate: septemberDate});
assert.equal(rejectedMalformedDetail.records.size, 0);
assert.equal(rejectedMalformedDetail.unconfirmed.length, 1);
assert.deepEqual(rejectedMalformedDetail.unconfirmedReasonCounts, {detail_identity_missing: 1});

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
const zeroStatusSummary = {
    members: 1, personal_active: 0, zero_cc: 1, active_4cc: 0,
    personal_cc: 0, goal_current_cc: 0, goal_metric_source: 'FLP360 Global Total CC · GLOBAL',
};
for(const invalidZeroLikeValue of [null, undefined, false, '', ' ', Number.NaN, Number.POSITIVE_INFINITY, -0.001]) {
    assert.throws(() => verifyFccStatus({
        summary: {...zeroStatusSummary, personal_cc: invalidZeroLikeValue},
    }, {members: 1, activeFourCc: 0, personalCc: 0, globalTotalCc: 0}), /zavr\u0161na kontrola/);
    assert.throws(() => verifyFccStatus({
        summary: {...zeroStatusSummary, goal_current_cc: invalidZeroLikeValue},
    }, {members: 1, activeFourCc: 0, personalCc: 0, globalTotalCc: 0}), /zavr\u0161na kontrola/);
}

const registeredExpected = new Map([
    ['360001651915', {fboId: '360001651915', personalCc: 0.367, totalCc: 1.5, totalActiveCc: 2.5, nonManagerCc: 3.5, leadershipCc: 4.5, totalActiveCcYtd: 20, nonManagerCcYtd: 21, leadershipCcYtd: 22, isFourCcActive: true}],
    ['360000000002', {fboId: '360000000002', personalCc: 0, totalCc: 0, totalActiveCc: 0, nonManagerCc: null, leadershipCc: null, totalActiveCcYtd: 81.125, nonManagerCcYtd: null, leadershipCcYtd: null, isFourCcActive: false}],
]);
const registeredVerified = verifyFccAccounts({
    status: 'success',
    metric: 'fcc_accounts',
    summary: {unique_forever_ids: 2, active_account_links: 3, current_cc_confirmed: 2, current_active_4cc: 1, vip_enrolled: 1},
    accounts: [
        {fbo_id: '360001651915', metric_period: '2026-08-01', personal_cc: 0.367, total_cc: 1.5, total_active_cc: 2.5, non_manager_cc: 3.5, leadership_cc: 4.5, total_active_cc_ytd: 20, non_manager_cc_ytd: 21, leadership_cc_ytd: 22, is_4cc_active: true, is_vip_enrolled: true},
        {fbo_id: '360000000002', metric_period: '2026-08-01', personal_cc: 0, total_cc: 0, total_active_cc: 0, non_manager_cc: 9, leadership_cc: 8, total_active_cc_ytd: 81.125, non_manager_cc_ytd: 70, leadership_cc_ytd: 10, is_4cc_active: false, is_vip_enrolled: false},
    ],
}, registeredExpected, '2026-08');
assert.equal(registeredVerified.uniqueForeverIds, 2);
assert.equal(registeredVerified.activeAccountLinks, 3);
for(const invalidZeroLikeValue of [null, undefined, false, '', ' ', Number.NaN, Number.POSITIVE_INFINITY, -0.001]) {
    assert.throws(() => verifyFccAccounts({
        status: 'success', metric: 'fcc_accounts',
        summary: {unique_forever_ids: 1, active_account_links: 1, current_cc_confirmed: 1, current_active_4cc: 0, vip_enrolled: 0},
        accounts: [{
            fbo_id: '360000000002', metric_period: '2026-08-01',
            personal_cc: invalidZeroLikeValue, total_cc: 0, total_active_cc: 0,
            is_4cc_active: false, is_vip_enrolled: false,
        }],
    }, new Map([['360000000002', {
        fboId: '360000000002', personalCc: 0, totalCc: 0, totalActiveCc: 0,
        isFourCcActive: false,
    }]]), '2026-08'), /registriranih Forever ID-jeva/);
}
assert.throws(() => verifyFccAccounts({
    status: 'success', metric: 'fcc_accounts',
    summary: {unique_forever_ids: 1, active_account_links: 1, current_cc_confirmed: 1, current_active_4cc: 0, vip_enrolled: 0},
    accounts: [{
        fbo_id: '360000000002', metric_period: '2026-08-01', personal_cc: 0, total_cc: 0,
        total_active_cc: 0, total_active_cc_ytd: 81.125, is_4cc_active: null, is_vip_enrolled: false,
    }],
}, new Map([['360000000002', {
    fboId: '360000000002', personalCc: 0, totalCc: 0, totalActiveCc: 0,
    totalActiveCcYtd: 81.125, isFourCcActive: false,
}]]), '2026-08'), /active_4cc/);
assert.throws(() => verifyFccAccounts({
    status: 'success', metric: 'fcc_accounts',
    summary: {unique_forever_ids: 1, active_account_links: 1, current_cc_confirmed: 1, current_active_4cc: 0, vip_enrolled: 0},
    accounts: [{
        fbo_id: '360000000002', metric_period: '2026-08-01', personal_cc: 0, total_cc: 0,
        total_active_cc: 0, total_active_cc_ytd: 81.125, is_4cc_active: false, is_vip_enrolled: false,
    }],
}, new Map([['360000000002', {
    fboId: '360000000002', personalCc: 0, totalCc: 0, totalActiveCc: 0,
    totalActiveCcYtd: 81.125, isFourCcActive: false, mustRemainVipEnrolled: true,
}]]), '2026-08'), /vip_preserved/);
assert.throws(() => verifyFccAccounts({
    status: 'success',
    metric: 'fcc_accounts',
    summary: {unique_forever_ids: 2, active_account_links: 3, current_cc_confirmed: 2, current_active_4cc: 1, vip_enrolled: 0},
    accounts: [
        {fbo_id: '360001651915', metric_period: '2026-08-01', personal_cc: 0.367, total_cc: 1.5, total_active_cc: 2.5, non_manager_cc: 3.5, leadership_cc: 4.5, total_active_cc_ytd: 20, non_manager_cc_ytd: 21, leadership_cc_ytd: 22, is_4cc_active: true, is_vip_enrolled: false},
        {fbo_id: '360000000002', metric_period: '2026-08-01', personal_cc: 0, total_cc: 0, total_active_cc: 0, total_active_cc_ytd: 81.125, is_4cc_active: false, is_vip_enrolled: false},
    ],
}, registeredExpected, '2026-08'), /registriranih Forever ID-jeva/);

const fourCoreSnapshots = officialFourCoreSnapshots();
assert.equal(fourCoreSnapshots.length, 2);
assert.equal(fourCoreSnapshots.find(snapshot => snapshot.period === '2026-07')?.values.downline.ytd.recruitment, 174);
assert.equal(fourCoreSnapshots.find(snapshot => snapshot.period === '2025-07')?.values.open.month.recruitment, 1);

assert.match(syncSource, /appflp360\.ReportApicategory/);
assert.match(syncSource, /appflp360\.homeCountryCode/);
assert.match(syncSource, /appflp360\.operatingCountryCode/);
assert.match(syncSource, /treeview-cc\?countryCode=/);
assert.match(syncSource, /metric: 'member_cc'/);
assert.match(syncSource, /metric: 'fcc_accounts'/);
assert.match(syncSource, /metric: 'total_cc'/);
assert.match(syncSource, /rewire-earnings-CC-summary/);
assert.match(syncSource, /requestSummary\(1\)/);
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
assert.match(syncSource, /candidateMemberCount > MAX_SAFE_DOWNLINE_MEMBERS/);
assert.match(syncSource, /persistConfirmedDownline\(downline\.path\)/);
assert.match(syncSource, /registeredOnlyAccounts/);
assert.match(syncSource, /lookupCountryByFboId/);
assert.match(syncSource, /countryCandidates/);
assert.match(syncSource, /Sinkronizacija je djelomična/);
assert.match(syncSource, /verifyFccAccounts/);
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
