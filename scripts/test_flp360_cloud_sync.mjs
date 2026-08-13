/* Custom code: FC-2026-08-13: Cloud FLP360 synchronization regression checks */

import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import {
    currentFlpMonthLabel,
    findCurrentFlpMonthLabel,
    officialFourCoreSnapshots,
    parseCsvLine,
    readyReportMessage,
    validateDownline,
    validateXlsx,
    zagrebPeriod,
} from './flp360_cloud_sync.mjs';

const downlinePath = process.argv[2];
const focusPath = process.argv[3];
const fourCcPath = process.argv[4];
const syncSource = await fs.readFile(new URL('./flp360_cloud_sync.mjs', import.meta.url), 'utf8');

assert.equal(zagrebPeriod(new Date('2026-08-13T19:00:00Z')), '2026-08');
assert.equal(currentFlpMonthLabel(new Date('2026-08-13T19:00:00Z')), '08/2026-Not Closed');
assert.equal(findCurrentFlpMonthLabel(['7/2026-Closed', '8/2026-Not Closed'], new Date('2026-08-13T19:00:00Z')), '8/2026-Not Closed');
assert.equal(findCurrentFlpMonthLabel(['Jul/2026-Closed', 'Aug/2026-Not Closed'], new Date('2026-08-13T19:00:00Z')), 'Aug/2026-Not Closed');
assert.equal(findCurrentFlpMonthLabel(['July 2026 - Closed', ' August 2026 - Not Closed '], new Date('2026-08-13T19:00:00Z')), ' August 2026 - Not Closed ');
assert.equal(findCurrentFlpMonthLabel(['07/2026-Closed'], new Date('2026-08-13T19:00:00Z')), '');
assert.equal(readyReportMessage('Your report generated on Aug 13, 2026 at 12:22pm is ready.\nClick here'), 'Your report generated on Aug 13, 2026 at 12:22pm is ready.');
assert.equal(readyReportMessage('Your report is being generated.'), '');
assert.deepEqual(parseCsvLine('1,2,"Prezime, Ime",4,5,HRV'), ['1', '2', 'Prezime, Ime', '4', '5', 'HRV']);
const fourCoreSnapshots = officialFourCoreSnapshots();
assert.equal(fourCoreSnapshots.length, 2);
assert.equal(fourCoreSnapshots.find(snapshot => snapshot.period === '2026-07')?.values.downline.ytd.recruitment, 174);
assert.equal(fourCoreSnapshots.find(snapshot => snapshot.period === '2025-07')?.values.open.month.recruitment, 1);
assert.match(syncSource, /#user-input-login-id:visible/);
assert.match(syncSource, /input\[name="password"\]:visible/);
assert.match(syncSource, /#kc-login:visible/);
assert.match(syncSource, /url\.pathname === '\/dashboard'/);
assert.match(syncSource, /Date\.now\(\) \+ 20 \* 60 \* 1000/);
assert.match(syncSource, /getByText\(ROOT_FBO_ID, \{exact: false\}\)\.waitFor/);
assert.match(syncSource, /sessionStorage\.setItem\('countries', JSON\.stringify\(countries\)\)/);
assert.match(syncSource, /operatingCompany === 'HRV'/);
assert.match(syncSource, /countrySelect\?\.options/);
assert.match(syncSource, /selectOption\(\{value: 'HRV'\}\)/);
assert.match(syncSource, /label => label\.click\(\)/);
assert.match(syncSource, /Suppress 0CC=Off/);
assert.match(syncSource, /attempt <= 2/);
assert.match(syncSource, /Downline nije međunarodni izvještaj/);
assert.match(syncSource, /excel-download'\)\.click\(\{force: true\}\)/);
assert.match(syncSource, /Downline izvoz je poslan u FLP360 red za generiranje/);
assert.match(syncSource, /Sinkronizacija je dovršena uz upozorenje/);
assert.ok(syncSource.indexOf('await requestDownline(page)') < syncSource.indexOf('await downloadFocusGroup(page)'));
assert.ok(syncSource.indexOf('await downloadFourCcActive(page)') < syncSource.indexOf('await downloadDownline(page, initialDownlineMessage)'));
assert.match(syncSource, /Djelomična sinkronizacija/);

if(downlinePath) {
    const result = await validateDownline(downlinePath);
    assert.ok(result.rows >= 400);
    assert.ok(result.hrvRows >= 250);
}
if(focusPath) assert.ok((await validateXlsx(focusPath, 'Focus Group')).bytes > 5000);
if(fourCcPath) assert.ok((await validateXlsx(fourCcPath, '4 CC Active')).bytes > 5000);

console.log('FLP360 cloud sync checks passed.');

/* /Custom code: FC-2026-08-13 */
