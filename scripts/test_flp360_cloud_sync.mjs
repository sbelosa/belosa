/* Custom code: FC-2026-08-13: Cloud FLP360 synchronization regression checks */

import assert from 'node:assert/strict';
import {
    currentFlpMonthLabel,
    readyReportMessage,
    validateDownline,
    validateXlsx,
    zagrebPeriod,
} from './flp360_cloud_sync.mjs';

const downlinePath = process.argv[2];
const focusPath = process.argv[3];
const fourCcPath = process.argv[4];

assert.equal(zagrebPeriod(new Date('2026-08-13T19:00:00Z')), '2026-08');
assert.equal(currentFlpMonthLabel(new Date('2026-08-13T19:00:00Z')), '08/2026-Not Closed');
assert.equal(readyReportMessage('Your report generated on Aug 13, 2026 at 12:22pm is ready.\nClick here'), 'Your report generated on Aug 13, 2026 at 12:22pm is ready.');
assert.equal(readyReportMessage('Your report is being generated.'), '');

if(downlinePath) {
    const result = await validateDownline(downlinePath);
    assert.ok(result.rows >= 400);
    assert.ok(result.hrvRows >= 250);
}
if(focusPath) assert.ok((await validateXlsx(focusPath, 'Focus Group')).bytes > 5000);
if(fourCcPath) assert.ok((await validateXlsx(fourCcPath, '4 CC Active')).bytes > 5000);

console.log('FLP360 cloud sync checks passed.');

/* /Custom code: FC-2026-08-13 */
