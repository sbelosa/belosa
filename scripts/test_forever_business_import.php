<?php
/* Custom code: FC-2026-08-13: Static regression checks for Forever import/access safety */

$root = dirname(__DIR__);
$helper = file_get_contents($root . '/app/helpers/forever_business.php');
$admin = file_get_contents($root . '/app/controllers/admin/AdminForeverBusiness.php');
$user = file_get_contents($root . '/app/controllers/ForeverBusiness.php');
$sync = file_get_contents($root . '/app/controllers/ForeverBusinessSync.php');

$assertions = [
    'source hash prevents duplicate imports' => str_contains($helper, 'UNIQUE KEY `forever_business_import_sha_uq`'),
    'Focus Group duplicate key includes confirmed period' => str_contains($helper, '$file_sha256 . \'|\' . implode(\',\', $report[\'periods\'])'),
    'Focus Group report is supported' => str_contains($helper, "'focus_group'"),
    'admin subtree scope uses hierarchy closure' => str_contains($helper, "where('ancestor_fbo_id', \$requested_root"),
    'non-admin scope is permanently self-only' => str_contains($helper, 'legacy manager') && str_contains($helper, 'return $own_fbo_id !== \'\' ? [$own_fbo_id] : []'),
    'contact details are hashed' => str_contains($helper, "hash_hmac('sha256'"),
    'PDF cannot bypass preview' => str_contains($admin, 'PDF se prvo mora ručno provjeriti'),
    'preview hash is verified before apply' => str_contains($admin, "hash_equals((string) \$preview['file_sha256']"),
    'daily outcomes are limited to visible scope' => str_contains($user, 'forever_business_record_daily_outcome') && str_contains($helper, 'in_array($fbo_id, $scope_ids, true)'),
    'team CC uses additive personal CC' => str_contains($helper, "SUM(personal_cc)"),
    'official 4 Core snapshots stay separate from operational signals' => str_contains($helper, 'forever_business_four_core_snapshots'),
    '1000 CC goal uses exact FLP Total CC when available' => str_contains($helper, "goal_metric_source") && str_contains($helper, 'forever_business_total_cc_snapshots'),
    '4 Core page adoption is measured from launch' => str_contains($helper, 'forever_business_page_visits') && str_contains($user, 'forever_business_record_page_visit'),
    'bulk manager grant accepts exact Forever ID matches only' => str_contains($helper, 'forever_business_grant_exact_manager_accesses') && str_contains($helper, "m.fbo_id = REPLACE(JSON_UNQUOTE(JSON_EXTRACT(u.preferences"),
    'machine sync compares only a SHA-256 secret hash' => str_contains($sync, 'SYNC_KEY_SHA256') && str_contains($sync, 'hash(\'sha256\', $key)'),
    'machine sync accepts only bounded CSV and XLSX uploads' => str_contains($sync, 'MAX_FILE_BYTES') && str_contains($sync, "['csv', 'xlsx']"),
    'machine sync is pinned to the admin root FBO' => str_contains($sync, "ROOT_FBO_ID = '360000760944'"),
    'all active FCC Forever IDs receive self-only placeholders' => str_contains($helper, 'forever_business_provision_fcc_members') && str_contains($helper, "'FCC suradnik'") && str_contains($helper, 'is_in_current_structure, email_hash'),
];

$failed = array_keys(array_filter($assertions, static fn($passed) => !$passed));
if($failed) {
    fwrite(STDERR, "Forever business regression check failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

defined('ALTUMCODE') || define('ALTUMCODE', 66);
require_once $root . '/app/helpers/forever_business.php';

$base = [
    'title' => 'Assistant Supervisor',
    'personal_cc' => 1.0,
    'total_active_cc' => 4.0,
    'total_cc' => 9.0,
    'previous_total_cc' => 0.0,
    'two_months_ago_total_cc' => 0.0,
    'three_months_ago_total_cc' => 0.0,
    'non_manager_cc' => 0.0,
    'is_4cc_active' => 1,
];

$activity = forever_business_get_verified_progress($base);
$personal_gate = forever_business_get_verified_progress(array_merge($base, ['personal_cc' => .999]));
$supervisor = forever_business_get_verified_progress(array_merge($base, ['title' => 'Supervisor', 'total_cc' => 20, 'previous_total_cc' => 25]));
$assistant_manager = forever_business_get_verified_progress(array_merge($base, ['title' => 'Assistant Manager', 'total_cc' => 30, 'previous_total_cc' => 40, 'two_months_ago_total_cc' => 30, 'three_months_ago_total_cc' => 20]));
$unrecognized_manager = forever_business_get_verified_progress(array_merge($base, ['title' => 'Unrecognized Manager', 'total_cc' => 30, 'previous_total_cc' => 40, 'two_months_ago_total_cc' => 30, 'three_months_ago_total_cc' => 20]));
$manager_60 = forever_business_get_verified_progress(array_merge($base, ['title' => 'Recognized Manager', 'non_manager_cc' => 59]));
$manager_100 = forever_business_get_verified_progress(array_merge($base, ['title' => 'Recognized Manager', 'non_manager_cc' => 60]));
$first_action = forever_business_get_action(array_merge($base, ['verified_progress' => $activity]), $base, 0);
$second_action = forever_business_get_action(array_merge($base, ['verified_progress' => $activity]), $base, 1);

$rule_assertions = [
    '4 CC activity requires both 1 personal and 4 regional active CC' => $activity['is_officially_active'] && !$personal_gate['is_officially_active'],
    'Assistant Supervisor path is 10 Total CC in one month' => $activity['rank']['windows'][0]['target'] === 10.0 && $activity['rank']['windows'][0]['gap'] === 1.0,
    'Supervisor path is 60 Total CC in two months' => $supervisor['rank']['windows'][0]['target'] === 60.0 && $supervisor['rank']['windows'][0]['gap'] === 15.0,
    'Assistant Manager paths are 120 in two or 150 in four months' => $assistant_manager['rank']['windows'][0]['gap'] === 50.0 && $assistant_manager['rank']['windows'][1]['gap'] === 30.0,
    'Unrecognized Manager remains on the recognized Manager qualification path' => $unrecognized_manager['rank']['mode'] === 'rank' && $unrecognized_manager['rank']['next_title'] === 'Recognized Manager',
    'Manager target advances from 60 to 100 Non-Manager CC' => $manager_60['rank']['windows'][0]['target'] === 60.0 && $manager_100['rank']['windows'][0]['target'] === 100.0,
    'completed action advances to a new detailed next step' => $first_action['key'] !== $second_action['key'] && count($first_action['checklist']) === 3 && !empty($first_action['success_definition']),
];

$failed_rules = array_keys(array_filter($rule_assertions, static fn($passed) => !$passed));
if($failed_rules) {
    fwrite(STDERR, "Forever business rule check failed:\n- " . implode("\n- ", $failed_rules) . "\n");
    exit(1);
}

echo "Forever business safety checks passed.\n";

/* /Custom code: FC-2026-08-13 */
