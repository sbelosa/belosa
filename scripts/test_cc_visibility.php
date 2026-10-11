<?php
/* Custom code: FC-2026-10-11: Offline checks for CC-based public visibility and 4CC group continuity. No database or network. */
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('ALTUMCODE', true);
define('APP_PATH', dirname(__DIR__) . '/app/');

$GLOBALS['fixture_metrics'] = [];
$GLOBALS['fixture_config'] = [];
function fcc_t($source, $replace = [], $locale = null) { return strtr((string) $source, (array) $replace); }
function fcc_partner_rows(string $sql, array $params = []): array {
    if(str_contains($sql, 'period_month IN')) {
        [$current, $previous] = $params;
        $ids = array_slice($params, 2);
        return array_values(array_filter($GLOBALS['fixture_metrics'], static fn(array $row): bool => in_array($row['period_month'], [$current, $previous], true) && in_array($row['fbo_id'], $ids, true)));
    }
    if(str_contains($sql, 'is_4cc_active = 1 AND period_month >=')) {
        [$fbo, $from, $to] = $params;
        return array_values(array_filter($GLOBALS['fixture_metrics'], static fn(array $row): bool => $row['fbo_id'] === $fbo && (int) $row['is_4cc_active'] === 1 && $row['period_month'] >= $from && $row['period_month'] <= $to));
    }
    throw new RuntimeException('Unexpected query in fixture: ' . $sql);
}
function fcc_partner_notification_config(string $key, $default = null) { return $GLOBALS['fixture_config'][$key] ?? $default; }
function fcc_partner_notification_config_save(string $key, $value): void { $GLOBALS['fixture_config'][$key] = $value; }
require APP_PATH . 'helpers/fcc_cc_visibility.php';

$failures = [];
$assert = static function(bool $condition, string $message) use (&$failures) { if(!$condition) $failures[] = $message; };
$zagreb = static fn(string $at) => new DateTimeImmutable($at, new DateTimeZone('Europe/Zagreb'));

/* Levels */
$assert(fcc_ccv_level_from_cc(null) === 'none', 'Missing CC data is level none.');
$assert(fcc_ccv_level_from_cc(['personal_cc' => 0.99, 'is_4cc' => false]) === 'none', 'Less than 1 personal CC is level none.');
$assert(fcc_ccv_level_from_cc(['personal_cc' => 1.0, 'is_4cc' => false]) === 'featured', '1 personal CC is the featured level.');
$assert(fcc_ccv_level_from_cc(['personal_cc' => 0.2, 'is_4cc' => true]) === '4cc', 'Confirmed 4CC is the 4cc level regardless of personal CC.');

/* Months use Europe/Zagreb, including the UTC evening before the 1st */
$assert(fcc_ccv_months($zagreb('2026-10-11 12:00')) === ['2026-10-01', '2026-09-01'], 'Mid-month uses current and previous month.');
$assert(fcc_ccv_months(new DateTimeImmutable('2026-10-31 23:30', new DateTimeZone('UTC'))) === ['2026-11-01', '2026-10-01'], 'Business month follows Europe/Zagreb.');
$assert(fcc_ccv_months($zagreb('2027-01-01 00:10')) === ['2027-01-01', '2026-12-01'], 'January keeps December as previous month.');

/* Best value of current and previous month, malformed IDs ignored */
$GLOBALS['fixture_metrics'] = [
    ['fbo_id' => '111111111111', 'period_month' => '2026-09-01', 'personal_cc' => '1.5', 'is_4cc_active' => 0],
    ['fbo_id' => '111111111111', 'period_month' => '2026-10-01', 'personal_cc' => '0.0', 'is_4cc_active' => 0],
    ['fbo_id' => '222222222222', 'period_month' => '2026-08-01', 'personal_cc' => '9.0', 'is_4cc_active' => 1],
    ['fbo_id' => '333333333333', 'period_month' => '2026-10-01', 'personal_cc' => '0.5', 'is_4cc_active' => 1],
];
$cc = fcc_ccv_cc_by_forever_id(['111111111111', '222222222222', '333333333333', 'abc', ''], $zagreb('2026-10-11 12:00'));
$assert(fcc_ccv_level_from_cc($cc['111111111111'] ?? null) === 'featured', 'Previous month CC keeps the featured level at the start of a new month.');
$assert(!isset($cc['222222222222']), 'Older months do not count.');
$assert(fcc_ccv_level_from_cc($cc['333333333333'] ?? null) === '4cc', 'Current month 4CC counts immediately.');
$assert(!isset($cc['abc']), 'Malformed Forever IDs are ignored.');

/* 4CC group continuity: each month qualifies with itself or the previous month */
$GLOBALS['fixture_metrics'] = [['fbo_id' => '444444444444', 'period_month' => '2026-10-01', 'personal_cc' => '2', 'is_4cc_active' => 1]];
$assert(fcc_ccv_group_continuous('444444444444', '2026-10-05 10:00:00', $zagreb('2026-11-20 12:00')), 'Joining in October stays valid in November through October activity.');
$assert(!fcc_ccv_group_continuous('444444444444', '2026-10-05 10:00:00', $zagreb('2026-12-20 12:00')), 'December without November or December activity breaks continuity.');
$GLOBALS['fixture_metrics'][] = ['fbo_id' => '444444444444', 'period_month' => '2027-01-01', 'personal_cc' => '2', 'is_4cc_active' => 1];
$assert(!fcc_ccv_group_continuous('444444444444', '2026-10-05 10:00:00', $zagreb('2027-01-20 12:00')), 'A gap stays a gap after requalifying, so the invitation shows again.');
$assert(!fcc_ccv_group_continuous('', '2026-10-05 10:00:00', $zagreb('2026-10-20 12:00')), 'No Forever ID is never a continuous member.');

/* Group link validation */
fcc_ccv_group_save_url('https://chat.whatsapp.com/AbC123xyz?mode=ac_t');
$assert(fcc_ccv_group_url() === 'https://chat.whatsapp.com/AbC123xyz?mode=ac_t', 'A WhatsApp invitation link is stored.');
foreach(['http://chat.whatsapp.com/AbC', 'https://example.com/AbC', 'https://chat.whatsapp.com/AbC"><script>'] as $bad) {
    try { fcc_ccv_group_save_url($bad); $failures[] = 'Rejected link was accepted: ' . $bad; } catch(InvalidArgumentException $exception) {}
}
fcc_ccv_group_save_url('');
$assert(fcc_ccv_group_url() === '', 'An empty link hides the invitation.');
$assert(count(fcc_ccv_rules_points()) === 4, 'Public rules list has four points.');

if($failures) { fwrite(STDERR, "CC visibility failures:\n- " . implode("\n- ", $failures) . "\n"); exit(1); }
echo 'CC visibility checks passed (' . 20 . ' checks).' . PHP_EOL;
/* /Custom code: FC-2026-10-11 */
