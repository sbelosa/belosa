<?php
/* Static guards for the read-only admin LOS · Moj Forever surface. */

$root = dirname(__DIR__);
$failures = [];
$checks = 0;

$read = static function(string $relative) use ($root, &$failures): string {
    $path = $root . '/' . $relative;
    if(!is_file($path)) {
        $failures[] = 'Missing file: ' . $relative;
        return '';
    }
    $contents = file_get_contents($path);
    if($contents === false) {
        $failures[] = 'Unreadable file: ' . $relative;
        return '';
    }
    return $contents;
};

$assert = static function(bool $condition, string $message) use (&$checks, &$failures): void {
    $checks++;
    if(!$condition) $failures[] = $message;
};

$controller = $read('app/controllers/admin/AdminLeaderOperatingSystemForever.php');
$helper = $read('app/helpers/forever_business_los.php');
$view = $read('themes/altum/views/admin/leader-operating-system/forever.php');
$nav = $read('themes/altum/views/admin/leader-operating-system/partials/section_nav.php');
$leader_view = $read('themes/altum/views/admin/leader-operating-system/index.php');
$sync_view = $read('themes/altum/views/admin/forever-business/index.php');
$sidebar = $read('themes/altum/views/admin/partials/admin_sidebar.php');
$router = $read('app/core/Router.php');
$init = $read('app/init.php');

$assert(strpos($router, "'leader-operating-system-forever'") !== false, 'Admin LOS Forever route is missing.');
$assert(strpos($router, "'controller' => 'AdminLeaderOperatingSystemForever'") !== false, 'Route does not target the read-only controller.');
$assert(strpos($controller, "REQUEST_METHOD") !== false && strpos($controller, "http_response_code(405)") !== false, 'Controller must reject non-GET requests.');
$assert(strpos($controller, '$_POST') === false, 'Read-only controller must not handle POST data.');
$assert(strpos($controller, 'forever_business_get_los_admin_analytics') !== false, 'Controller is not wired to the LOS read model.');
$assert(strpos($init, "helpers/forever_business_los.php") !== false, 'LOS helper is not bootstrapped.');

$assert(stripos($helper, 'forever_business_page_visits') === false, 'Read model must not read or record page visits.');
$assert(stripos($helper, 'forever_business_ensure_tables') === false, 'Read model must not invoke schema provisioning.');
$assert(!preg_match('/\b(?:INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE)\s+/i', $helper), 'Read model contains a database write statement.');
$assert(strpos($helper, '[7, 14, 30, 60]') !== false, 'Window whitelist 7/14/30/60 is missing.');
$assert(strpos($helper, '$previous_start') !== false && strpos($helper, '$previous_end') !== false, 'Previous equal-window comparison is missing.');
$assert(strpos($helper, "'closed_sample_count' => count(\$closed_values)") !== false, 'Global average must disclose how many of six closed months are available.');
$assert(strpos($helper, "'personal_cc'] >= 0.33") !== false, 'Current 0.33 CC qualification guard is missing.');
$assert(strpos($helper, "['is_4cc_active'] !== null") !== false, 'Official tri-state 4 CC handling is missing.');
$assert(strpos($helper, "source_import.`report_kind` NOT IN ('downline', 'four_cc_active')") !== false, 'Only positively trusted imports may provide historical official 4 CC flags.');
$assert(strpos($helper, "['personal_cc'] ?? 0) >= 1.0") !== false && strpos($helper, "['total_active_cc'] ?? 0) >= 4.0") !== false, 'Effective 1 + 4 CC fallback is missing.');
$assert(strpos($helper, 'qualification_source') !== false, 'Enrollment qualification source is not audited.');
$assert(strpos($helper, "['qualifying_period']") !== false && strpos($helper, "['last_verified_period']") !== false, 'Immutable qualification and latest diagnostics must remain separate.');
$assert(strpos($helper, "'is_in_current_structure' => !empty") !== false && strpos($helper, '$current_structure_metrics') !== false, 'Current FLP metrics must stay separate from the permanent project cohort.');
$assert(strpos($helper, "|| \$member['started_at'] !== null") !== false && strpos($helper, "'started_without_enrollment'") !== false, 'Permanent project outcomes must remain visible outside the current structure and expose migration gaps.');
$assert(strpos($helper, "['linked_accounts'] === 1") !== false && strpos($helper, "`status` = 1") !== false && strpos($helper, '$.meta.forever_id') !== false && strpos($helper, '$.meta.foreverID') !== false, 'LOS linkage must match the exact active-account access gate.');
$assert(strpos($helper, 'result_type') !== false && strpos($helper, 'difficulty') !== false && strpos($helper, 'needs_help') !== false, 'Structured outcome fields are incomplete.');
$assert(strpos($helper, "`newer`.`updated_at` > `request`.`updated_at`") !== false && strpos($helper, "['help_requested_at'] = \$row['updated_at']") !== false, 'Repeated help requests must be selected and sorted by their latest update rather than their first creation.');
$assert(strpos($helper, "'2026-09-01'") !== false && strpos($helper, "modify('+3 days')") !== false && strpos($helper, ">= 7") !== false, 'Launch/grace and stalled thresholds are incomplete.');
$assert(strpos($helper, "'needs_help' => 0") !== false, 'Needs-help signal must win attention ordering.');
$assert(strpos($helper, "'daily'") !== false && strpos($helper, "'cc'") !== false && strpos($helper, "'result_type'") !== false && strpos($helper, "'core'") !== false && strpos($helper, "'track'") !== false, 'Required chart payloads are incomplete.');
$assert(strpos($helper, "'top_results'") !== false && strpos($helper, "'data_quality'") !== false && strpos($helper, "'completed'") !== false, 'Result ranking, data quality, or program completion analytics are missing.');
$assert(strpos($helper, "AS `completion_at`") !== false && strpos($helper, "['completed_at'] >= \$current_start_string") !== false, 'Completion deltas must use the exact 30th non-Sunday program step date.');
$assert(strpos($helper, 'array_slice($member_table') === false && strpos($helper, 'array_slice($attention_queue') === false, 'Member and attention search must cover the complete structure.');
$assert(strpos($helper, 'u.email') === false && strpos($helper, '`email`') === false && strpos($helper, "['email']") === false, 'Read model must not select or expose email data.');

$assert(strpos($view, "views/partials/js_chart_defaults.php") !== false, 'Moj Forever view must use shared chart defaults.');
$assert(!preg_match('/<form[^>]+method=["\']post["\']/i', $view), 'Analytics view must not contain POST forms.');
$assert(strpos($view, 'results_not_cc') !== false, 'Results must be explicitly labeled as non-CC.');
$assert(strpos($view, 'global_metric_fallback') !== false && strpos($view, 'cc_chart_fallback') !== false, 'Fallback personal-CC values must never be labeled as official Global Total CC.');
$assert(strpos($view, 'latest_result_type') !== false && strpos($view, 'last_task_track') !== false, 'Aggregates must label latest-result and latest-task dimensions honestly.');
$assert(strpos($view, 'JSON_HEX_TAG') !== false, 'Inline chart JSON must be safe against script termination.');
$assert(strpos($view, 'attention_queue') !== false && strpos($view, 'top_performers') !== false, 'Operational queues are missing from the view.');
$assert(strpos($view, "['email']") === false && strpos($view, '->email') === false, 'Analytics view must not render email data.');
$assert(strpos($nav, "['active' => 'forever']") === false, 'Shared navigation must not hard-code the Forever section.');
$assert(strpos($leader_view, 'leader-operating-system/partials/section_nav.php') !== false, 'Leader LOS view does not include the shared section navigation.');
$assert(strpos($sidebar, 'AdminLeaderOperatingSystemForever') !== false && strpos($sidebar, 'AdminForeverBusiness') !== false, 'Sidebar does not group LOS analytics and data synchronization.');
$assert(strpos($sync_view, "url('admin/leader-operating-system-forever')") !== false, 'Import page does not link back to LOS analytics.');
$assert(strpos($sync_view, 'FLP360 GLOBAL TOTAL CC') === false && strpos($sync_view, 'VIP POLAZNICI') === false, 'Legacy analytics were not removed from the import page.');

if($failures) {
    fwrite(STDERR, "LOS Forever admin guard failures:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "LOS Forever admin guards passed ({$checks} checks).\n");
