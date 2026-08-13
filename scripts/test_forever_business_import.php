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

echo "Forever business safety checks passed.\n";

/* /Custom code: FC-2026-08-13 */
