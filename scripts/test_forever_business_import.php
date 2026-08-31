<?php
/* Custom code: FC-2026-08-13: Static regression checks for Forever import/access safety */

$root = dirname(__DIR__);
$helper = file_get_contents($root . '/app/helpers/forever_business.php');
$admin = file_get_contents($root . '/app/controllers/admin/AdminForeverBusiness.php');
$user = file_get_contents($root . '/app/controllers/ForeverBusiness.php');
$sync = file_get_contents($root . '/app/controllers/ForeverBusinessSync.php');
$dashboard_controller = file_get_contents($root . '/app/controllers/Dashboard.php');
$dashboard_view = file_get_contents($root . '/themes/altum/views/dashboard/index.php');
$view = file_get_contents($root . '/themes/altum/views/forever-business/index.php');
$admin_view = file_get_contents($root . '/themes/altum/views/admin/forever-business/index.php');
$vip_tasks = file_get_contents($root . '/app/config/forever_business_vip_tasks.php');
$vip_task_meta_source = file_get_contents($root . '/app/config/forever_business_vip_task_meta.php');
$hr_language = file_get_contents($root . '/app/languages/Hrvatski#hr.php');
$hr_language_cache = file_get_contents($root . '/app/languages/cache/Hrvatski#hr.php');
$en_language = file_get_contents($root . '/app/languages/english#en.php');
$en_language_cache = file_get_contents($root . '/app/languages/cache/english#en.php');

$assertions = [
    'source hash prevents duplicate imports' => str_contains($helper, 'UNIQUE KEY `forever_business_import_sha_uq`'),
    'concurrent duplicate imports never delete active work or continue with import id zero' => str_contains($helper, "\$existing->status === 'processing'") && str_contains($helper, "where('status', 'failed')") && str_contains($helper, 'if(!$import_id || (int) $import_id <= 0)') && str_contains($helper, 'Never continue with import_id=0'),
    'successful duplicate checks are audited separately from data imports' => str_contains($helper, 'forever_business_sync_checks') && str_contains($helper, 'forever_business_record_sync_check($report, $dedupe_sha256, (int) $existing->import_id, true)'),
    'Focus Group duplicate key includes confirmed period' => str_contains($helper, '$file_sha256 . \'|\' . implode(\',\', $report[\'periods\'])'),
    'Focus Group report is supported' => str_contains($helper, "'focus_group'"),
    '4 CC official signal remains tri-state in schema and guarded migration' => str_contains($helper, '`is_4cc_active` TINYINT(1) NULL DEFAULT NULL') && substr_count($helper, "SHOW COLUMNS FROM `forever_business_metrics` LIKE 'is_4cc_active'") >= 2 && str_contains($helper, 'MODIFY `is_4cc_active` TINYINT(1) NULL DEFAULT NULL') && str_contains($helper, '4 CC signal column must allow NULL before imports can continue'),
    'Focus Group ACTIVE never overwrites the official 4 CC signal' => str_contains($helper, "'is_4cc_active' => null") && str_contains($helper, "? ['personal_cc', 'updated_at']") && str_contains($helper, "\$metric['is_4cc_active'] === null ? null : (int) \$metric['is_4cc_active']"),
    'only positively trusted imports can supply historical official 4 CC flags without destructive cleanup' => substr_count($helper, "report_kind NOT IN ('downline', 'four_cc_active')") >= 5 && !str_contains($helper, 'four_cc_focus_provenance_cleanup_v1'),
    'admin subtree scope uses hierarchy closure' => str_contains($helper, "where('ancestor_fbo_id', \$requested_root"),
    'non-admin scope is permanently self-only' => str_contains($helper, 'legacy manager') && str_contains($helper, 'return $own_fbo_id !== \'\' ? [$own_fbo_id] : []'),
    'contact details are hashed' => str_contains($helper, "hash_hmac('sha256'"),
    'PDF cannot bypass preview' => str_contains($admin, 'PDF se prvo mora ručno provjeriti'),
    'preview hash is verified before apply' => str_contains($admin, "hash_equals((string) \$preview['file_sha256']"),
    'daily outcomes are limited to visible scope' => str_contains($user, 'forever_business_record_daily_outcome') && str_contains($helper, 'in_array($fbo_id, $scope_ids, true)'),
    'VIP completion classification is derived from the verified server action' => str_contains($user, "'core_key' => (string) (\$expected_action['core'] ?? '')") && str_contains($user, "'action_key' => (string) (\$expected_action['key'] ?? '')") && str_contains($user, "'outcome_type' => (string) (\$expected_action['track_key'] ?? 'vip')"),
    'one VIP task per participant per day is enforced with a serialized server check' => str_contains($helper, 'vip_participant_lock_failed') && str_contains($helper, 'WHERE recorded_by_user_id = {$user_id}') && str_contains($helper, "action_date = '{\$action_date}'") && str_contains($helper, "action_key LIKE 'vip26\\\\_%'") && str_contains($helper, 'vip_action_done_today'),
    'VIP result form uses the verified quick target and strict integer ceiling' => str_contains($view, 'min="<?= max(1, (int) ($action[\'quick_target\'] ?? 1)) ?>" max="999" step="1" required name="outcome_count"') && str_contains($user, 'forever_business_vip_completion_mode_for_count'),
    'VIP result form captures bounded outcome data while help stays a separate open request' => str_contains($view, 'name="result_type"') && str_contains($view, 'name="difficulty"') && !str_contains($view, 'name="needs_help"') && str_contains($view, 'name="request_vip_help"') && str_contains($helper, 'forever_business_normalize_result_type') && str_contains($helper, 'forever_business_normalize_difficulty'),
    'one-unit lighter tasks are explicitly self-reported and remain server-bounded' => str_contains($view, 'name="completion_variant"') && str_contains($view, 'value="" selected disabled') && str_contains($view, 'Lakšu verziju ponuđenu uz zadatak') && str_contains($user, '$can_report_lighter_variant') && str_contains($user, 'forever_business_normalize_completion_mode($_POST[\'completion_variant\'] ?? null)') && str_contains($user, 'elseif($can_report_lighter_variant && $reported_completion_variant === null)'),
    'team CC uses additive personal CC' => str_contains($helper, "SUM(personal_cc)"),
    'official 4 Core snapshots stay separate from operational signals' => str_contains($helper, 'forever_business_four_core_snapshots'),
    'official 4 Core comparison uses the exact prior-year period' => str_contains($helper, "modify('-1 year')") && str_contains($helper, "\$result['previous']"),
    '1000 CC goal uses exact FLP Total CC when available' => str_contains($helper, "goal_metric_source") && str_contains($helper, 'forever_business_total_cc_snapshots'),
    'collaborator trend uses imported Total CC with provenance-verified monthly activity' => str_contains($helper, 'SELECT metric.period_month, metric.total_cc, metric.personal_cc, metric.total_active_cc') && str_contains($helper, 'LEFT JOIN forever_business_imports metric_source') && str_contains($helper, "metric_source.report_kind NOT IN ('downline', 'four_cc_active')") && str_contains($helper, "'has_activity_data' => \$has_activity_data") && str_contains($helper, "'is_4cc_active' => \$is_verified_active"),
    '4 Core page adoption is measured from launch' => str_contains($helper, 'forever_business_page_visits') && str_contains($user, 'forever_business_record_page_visit'),
    'self-only privacy audit exposes active team-access count' => str_contains($helper, 'active_team_access_records'),
    'readiness audit detects missing invalid and duplicate Forever IDs' => str_contains($helper, 'accounts_missing_fbo_id') && str_contains($helper, 'accounts_invalid_fbo_id') && str_contains($helper, 'duplicate_fbo_id_groups'),
    'readiness audit compares current FLP members with FCC accounts and CC rows' => str_contains($helper, 'current_members_without_fcc_account') && str_contains($helper, 'current_members_missing_latest_cc'),
    'readiness latest-period audit ignores any historical future-dated rows' => str_contains($helper, "MAX(period_month) FROM forever_business_metrics WHERE period_month <= '{\$current_zagreb_period}'"),
    'legacy team-access grants are disabled in self-only mode' => str_contains($helper, 'Self-only privacy mode') && str_contains($helper, 'forever_business_enforce_self_only_access'),
    'dashboard 4 CC notice resolves only the signed-in user' => str_contains($dashboard_controller, 'forever_business_get_user_activity_notice((int) $this->user->user_id)') && str_contains($helper, 'No request') && str_contains($helper, "where('member.fbo_id', \$fbo_id)"),
    'dashboard 4 CC notice opens the self-only Forever page' => str_contains($dashboard_view, "url('forever-business')") && str_contains($dashboard_view, 'dashboard-four-cc-notice'),
    'machine sync compares only a SHA-256 secret hash' => str_contains($sync, 'SYNC_KEY_SHA256') && str_contains($sync, 'hash(\'sha256\', $key)'),
    'machine sync accepts only bounded CSV and XLSX uploads' => str_contains($sync, 'MAX_FILE_BYTES') && str_contains($sync, "['csv', 'xlsx']"),
    'detailed account reconciliation requires the signed machine endpoint' => str_contains($sync, "metric === 'account_audit'") && str_contains($helper, 'forever_business_get_account_audit_rows'),
    'machine sync is pinned to the admin root FBO' => str_contains($sync, "ROOT_FBO_ID = '360000760944'"),
    'machine sync keeps root handling pinned while accepting only active FCC Forever IDs' => str_contains($sync, "metric === 'member_cc'") && str_contains($sync, "fbo_id === self::ROOT_FBO_ID") && str_contains($sync, 'forever_business_get_active_user_link_count_for_fbo($fbo_id) < 1') && str_contains($helper, 'forever_business_upsert_root_live_cc') && str_contains($helper, 'forever_business_upsert_registered_member_live_cc'),
    'machine sync exposes a secret-protected PII-free FCC Forever ID reconciliation list' => str_contains($sync, "metric === 'fcc_accounts'") && str_contains($helper, 'forever_business_get_registered_sync_accounts') && str_contains($helper, "account.fbo_id REGEXP '^360[0-9]{9}$'") && str_contains($helper, 'active_link_count') && str_contains($helper, 'is_vip_enrolled'),
    'machine sync verifies the Focus Group root inside the downloaded workbook' => str_contains($sync, "empty(\$report['members'][self::ROOT_FBO_ID])"),
    'machine sync enforces self-only privacy and never grants manager access' => str_contains($sync, 'forever_business_enforce_self_only_access()') && !str_contains($sync, 'forever_business_grant_exact_manager_accesses(1)'),
    'all active FCC Forever IDs receive self-only placeholders' => str_contains($helper, 'forever_business_provision_fcc_members') && str_contains($helper, "'FCC suradnik'") && str_contains($helper, 'is_in_current_structure, email_hash'),
    'team priorities include the complete imported list' => !str_contains($user, 'array_slice($priority_members, 0, 100)'),
    'team priorities support accessible client-side sorting' => str_contains($view, 'fb-sort-button') && str_contains($view, 'Intl.Collator') && str_contains($view, "setAttribute('aria-sort'"),
    'team status keeps Focus ACTIVE separate from effective and official 4 CC' => str_contains($view, "['is_4cc_active']") && str_contains($view, "\$activity_source === 'official'") && str_contains($view, '4 CC Active · službeno') && str_contains($view, '4 CC Active · pomoćni izračun') && str_contains($view, 'Focus Group: ACTIVE') && !str_contains($view, "!empty(\$member['focus_is_active']) ? '4 CC aktivan'"),
    'team table labels the seven-day metric as VIP task days' => str_contains($view, 'VIP dani · 7 dana'),
    'sync notice uses clear member-facing wording and Zagreb time' => str_contains($view, 'Podaci provjereni:') && str_contains($view, 'Trenutačno su prikazani najnoviji dostupni bodovi') && str_contains($view, 'last_sync_was_duplicate') && str_contains($helper, 'Europe/Zagreb'),
    'VIP education uses permanent rolling enrollment and September launch' => str_contains($helper, 'forever_business_vip_enrollments') && str_contains($helper, 'forever_business_vip_eligibility_period_is_open') && str_contains($helper, "new \\DateTimeImmutable('2026-09-01 00:00:00'") && str_contains($helper, "'can_access_education' => \$is_launched && \$is_enrolled && \$has_valid_linkage"),
    'only trusted FLP sources can create future enrollments' => str_contains($helper, "in_array(\$report['kind'], ['downline', 'four_cc_active'], true)") && str_contains($helper, "'member_cc'") && str_contains($helper, 'Focus remains diagnostic'),
    'legacy August cohort is preserved exactly once without serializing normal page opens' => str_contains($helper, "'legacy_august_backfill'") && str_contains($helper, "metric.period_month = '2026-08-01' AND metric.personal_cc >= 0.330") && str_contains($helper, "'vip_enrollment_august_gate_v1'") && str_contains($helper, 'forever_business_schema_migrations') && str_contains($helper, '$legacy_precheck') && str_contains($helper, 'common post-migration path stays read-only'),
    'VIP access accepts administrator-approved shared IDs while missing linkage stays closed' => str_contains($helper, 'forever_business_get_active_user_link_count_for_fbo') && str_contains($helper, '$active_link_count >= 1') && str_contains($helper, "'is_shared_linkage'") && str_contains($helper, "'duplicate_linkage'"),
    'VIP task submission and admin personal view are bound to the authenticated FBO' => str_contains($user, "empty(\$vip_program['can_access_education'])") && str_contains($user, "hash_equals(\$authenticated_fbo_id, \$submitted_fbo_id)") && str_contains($user, "[\$authenticated_fbo_id]") && str_contains($user, "\$requested_root = ''") && str_contains($user, 'if(!$is_admin && !$focus_member'),
    'VIP launch view includes countdown eligibility and a locked preview' => str_contains($view, 'data-fb-vip-countdown') && str_contains($view, 'fb-vip-conditions') && str_contains($view, 'fb-vip-preview') && str_contains($view, 'Prag od 0,330 CC uvjet je za ovu dodatnu edukaciju'),
    'VIP task content is centralized for later copy corrections' => str_contains($helper, 'forever_business_vip_tasks.php') && str_contains($vip_tasks, '# Razina 5 — Reaktivacija'),
    'VIP task targets quick targets and result types are machine-readable rather than parsed from Croatian prose' => str_contains($helper, 'forever_business_vip_task_meta.php') && str_contains($vip_task_meta_source, "'targets'") && str_contains($vip_task_meta_source, "'quick_targets'") && str_contains($vip_task_meta_source, "'result_types'") && str_contains($helper, "\$meta[\$track_key]['targets'][\$day - 1]") && str_contains($helper, "\$meta[\$track_key]['quick_targets'][\$day - 1]") && str_contains($helper, "\$meta[\$track_key]['result_types'][\$day - 1]"),
    'VIP task metadata fails closed if the separately deployed rules file is missing or incomplete' => str_contains($helper, 'VIP task metadata is missing; the curriculum cannot be opened safely.') && str_contains($helper, 'must contain exactly 30 reviewed rules') && str_contains($helper, 'contains an invalid checklist item') && str_contains($helper, 'contains an invalid allowed result type') && str_contains($helper, "foreach(['starter', 'activator', 'builder', 'leader', 'reactivation'] as \$track_key)"),
    'VIP message examples use explicit per-day mappings instead of prose heuristics' => str_contains($vip_task_meta_source, "'examples'") && str_contains($helper, "\$meta[\$track_key]['examples'][\$day]"),
    'VIP fallback heading also fits profile setup and other non-contact tasks' => str_contains($view, 'Lakša verzija ili druga mogućnost:') && !str_contains($view, 'Ako danas nemaš potrebnu osobu:'),
    'shared Forever IDs keep separate account progress and LOS participant identity' => str_contains($helper, 'forever_business_outcome_user_daily_uq') && str_contains($helper, 'recorded_by_user_id = {$user_id}') && str_contains($helper, 'vip_participant_lock_failed'),
    'participant progress follows the FCC account when its approved Forever ID is corrected' => str_contains($helper, 'GROUP BY recorded_by_user_id') && str_contains($helper, 'actor_outcomes.recorded_by_user_id = {$user_id}') && str_contains($helper, "m.fbo_id = '{\$escaped_dashboard_root}'"),
    'arbitrary non-admin root parameters cannot replace self-only participant progress' => str_contains($helper, '$dashboard_root = $is_admin') && str_contains($helper, ': $authenticated_dashboard_root;') && str_contains($user, "\$requested_root = '';"),
    'all supported Forever ID preference aliases provision members and qualified email recipients' => substr_count($helper, "$.meta.forever_id") >= 4 && substr_count($helper, "$.meta.foreverID") >= 4 && str_contains($helper, 'JSON_VALID(u.preferences)'),
    'admin usage and reconciliation audits use the same three Forever ID aliases as access' => str_contains($helper, 'function forever_business_user_fbo_sql_expression') && substr_count($helper, "forever_business_user_fbo_sql_expression('u.preferences')") >= 2 && substr_count($helper, "forever_business_user_fbo_sql_expression('preferences')") >= 2,
    'members can request mentor help without completing a task' => str_contains($helper, 'forever_business_vip_help_requests') && str_contains($helper, 'forever_business_request_vip_help') && str_contains($view, 'request_vip_help') && str_contains($view, 'Pošalji upit mentoru'),
    'runtime schema verifies every launch table and preserves legacy help flags before marking complete' => str_contains($helper, 'forever_business_runtime_schema_v20260831_4') && str_contains($helper, '$required_tables') && str_contains($helper, 'Legacy VIP help requests could not be preserved.') && str_contains($helper, '`outcome`.`needs_help` = 1'),
    'Leader program copy requires full Manager and official August 4 CC' => str_contains($vip_tasks, 'punim, priznatim statusom Managera') && str_contains($vip_tasks, 'službeno potvrđenim `4 CC Active` signalom za kolovoz 2026'),
    '4 CC member copy and runtime language caches explain the inclusive fallback' => str_contains($hr_language, 'Personal CC već je dio Total Active CC-a') && str_contains($hr_language_cache, 'Personal CC već je dio Total Active CC-a') && str_contains($en_language, 'Personal CC is already included in Total Active CC') && str_contains($en_language_cache, 'Personal CC is already included in Total Active CC'),
    'weekly Marketing plan is fixed to Sunday at 18:00 Zagreb time' => str_contains($helper, "'weekday' => 7") && str_contains($helper, "setTime(18, 0)") && str_contains($view, 'svake nedjelje u 18:00'),
    'qualified members receive the confirmed VIP WhatsApp link' => str_contains($helper, 'G0Mxgm8yXfrIDAOxNqPbmw') && str_contains($view, 'Pridruži se VIP grupi'),
    'Marketing plan starts on 6 September and exposes the confirmed webinar link' => str_contains($helper, "new \\DateTimeImmutable('2026-09-06 18:00:00'") && str_contains($helper, 'https://forevercard.club/vip-edukacija') && str_contains($view, 'Otvori Marketing plan'),
    'VIP email queue is idempotent, prevents invalid or stale recipients from starving the batch and is processed by cron' => str_contains($helper, 'forever_business_vip_email_deliveries') && str_contains($helper, 'recipient_unavailable: inactive account or invalid email') && str_contains($helper, 'qualification_unavailable: eligibility or linkage no longer valid') && str_contains($helper, "LEFT(COALESCE(`last_error`, ''), 22) = 'recipient_unavailable:'") && str_contains($helper, "LEFT(COALESCE(`last_error`, ''), 26) = 'qualification_unavailable:'") && str_contains($helper, 'forever_business_process_vip_email_notifications') && str_contains(file_get_contents($root . '/app/controllers/Cron.php'), 'forever_business_process_vip_email_notifications(25)'),
    'submitted completions must match the currently visible server action' => str_contains($user, '$matches_visible_action') && str_contains($user, "hash_equals((string) (\$expected_action['key']"),
    'collaborator view sends all project analytics to LOS while retaining personal education' => str_contains($view, "url('admin/leader-operating-system-forever')") && str_contains($view, 'Tvoj Leader program i današnji korak') && str_contains($view, 'is_admin_preview'),
    'legacy outcomes cannot skip new VIP steps or enter current curriculum statistics' => str_contains($helper, 'vip_actions_done_total') && str_contains($helper, "action_key NOT LIKE 'vip26\\\\_sunday") && substr_count($helper, "action_key <> 'vip26_activator_d01'") >= 8,
    'VIP level is anchored to permanent qualification while Leader stays August-official' => str_contains($helper, 'COALESCE(vip_enrollment.qualifying_personal_cc, vip_base.personal_cc) AS vip_base_personal_cc') && str_contains($helper, 'vip_enrollment.qualifying_period') && str_contains($helper, "vip_august.period_month = '2026-08-01'") && str_contains($helper, 'vip_current_period_month'),
    'open Zagreb month is explicit and never falls back to the latest historical CC row' => str_contains($helper, 'function forever_business_current_zagreb_period') && str_contains($helper, '$periods = [$current_zagreb_period]') && str_contains($helper, "vip_current.period_month = '{\$vip_current_period}'") && !str_contains($helper, 'MAX(vip_lookup.period_month)'),
    'missing current monthly values are normalized only in the read model' => str_contains($helper, 'function forever_business_normalize_current_month_metrics') && str_contains($helper, '$row = forever_business_normalize_current_month_metrics($row, $period, $zagreb_now)') && str_contains($helper, 'Historical selections') && str_contains($helper, 'cumulative/YTD fields remain untouched'),
    'all future FLP periods are rejected before deduplication and snapshot writes' => str_contains($helper, 'No report may create a future period') && str_contains($helper, 'forever_business_period_is_current_or_past') && substr_count($helper, 'Budući FLP360 mjesec') >= 4,
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
$official_active_formula_low = forever_business_get_verified_progress(array_merge($base, ['personal_cc' => .2, 'total_active_cc' => .2, 'is_4cc_active' => 1]));
$official_inactive_formula_high = forever_business_get_verified_progress(array_merge($base, ['personal_cc' => 4, 'total_active_cc' => 4, 'is_4cc_active' => 0]));
$fallback_two_plus_remainder_input = array_merge($base, ['personal_cc' => 2, 'total_active_cc' => 4]);
unset($fallback_two_plus_remainder_input['is_4cc_active']);
$fallback_four_personal_input = array_merge($base, ['personal_cc' => 4, 'total_active_cc' => 4, 'is_4cc_active' => null]);
$fallback_personal_below_input = array_merge($base, ['personal_cc' => .999, 'total_active_cc' => 4, 'is_4cc_active' => null]);
$fallback_total_below_input = array_merge($base, ['personal_cc' => 1, 'total_active_cc' => 3.999, 'is_4cc_active' => null]);
$fallback_two_plus_remainder = forever_business_get_verified_progress($fallback_two_plus_remainder_input);
$fallback_four_personal = forever_business_get_verified_progress($fallback_four_personal_input);
$fallback_personal_below = forever_business_get_verified_progress($fallback_personal_below_input);
$fallback_total_below = forever_business_get_verified_progress($fallback_total_below_input);
$supervisor = forever_business_get_verified_progress(array_merge($base, ['title' => 'Supervisor', 'total_cc' => 20, 'previous_total_cc' => 25]));
$assistant_manager = forever_business_get_verified_progress(array_merge($base, ['title' => 'Assistant Manager', 'total_cc' => 30, 'previous_total_cc' => 40, 'two_months_ago_total_cc' => 30, 'three_months_ago_total_cc' => 20]));
$unrecognized_manager = forever_business_get_verified_progress(array_merge($base, ['title' => 'Unrecognized Manager', 'total_cc' => 30, 'previous_total_cc' => 40, 'two_months_ago_total_cc' => 30, 'three_months_ago_total_cc' => 20]));
$manager_60 = forever_business_get_verified_progress(array_merge($base, ['title' => 'Recognized Manager', 'non_manager_cc' => 59]));
$manager_100 = forever_business_get_verified_progress(array_merge($base, ['title' => 'Recognized Manager', 'non_manager_cc' => 60]));
$september_boundary_utc = new DateTimeImmutable('2026-08-31 22:00:00', new DateTimeZone('UTC'));
$current_month_zero = forever_business_normalize_current_month_metrics([
    'personal_cc' => null,
    'total_cc' => null,
    'total_active_cc' => null,
    'total_active_cc_ytd' => 87.5,
], '2026-09-01', $september_boundary_utc);
$historical_month_unchanged = forever_business_normalize_current_month_metrics([
    'personal_cc' => null,
    'total_cc' => null,
    'total_active_cc' => null,
    'total_active_cc_ytd' => 87.5,
], '2026-08-01', $september_boundary_utc);
$supervisor_at_month_boundary = forever_business_get_verified_progress(array_merge($base, [
    'title' => 'Supervisor',
    'personal_cc' => 0.0,
    'total_active_cc' => 0.0,
    'total_cc' => 0.0,
    'previous_total_cc' => 45.0,
]));
$first_action = forever_business_get_action(array_merge($base, ['verified_progress' => $activity]), $base, 0);
$second_action = forever_business_get_action(array_merge($base, ['verified_progress' => $activity]), $base, 1);
$zagreb_time = forever_business_format_zagreb_datetime('2026-08-14 06:53:00');
$before_launch = new DateTimeImmutable('2026-08-31 23:59:59', new DateTimeZone('Europe/Zagreb'));
$august_sync_time = new DateTimeImmutable('2026-08-21 12:00:00', new DateTimeZone('Europe/Zagreb'));
$after_launch = new DateTimeImmutable('2026-09-01 00:00:00', new DateTimeZone('Europe/Zagreb'));
$october = new DateTimeImmutable('2026-10-10 12:00:00', new DateTimeZone('Europe/Zagreb'));
$persistent_enrollment = [
    'fbo_id' => '360000000001',
    'qualifying_period' => '2026-09-01',
    'qualifying_personal_cc' => .330,
    'qualification_source' => 'downline',
    'enrolled_at' => '2026-09-03 08:00:00',
];
$vip_below_threshold = forever_business_build_vip_program_state(.329, $before_launch);
$vip_qualified_waiting = forever_business_build_vip_program_state(.330, $before_launch);
$vip_active = forever_business_build_vip_program_state(.330, $after_launch);
$vip_persistent_after_drop = forever_business_build_vip_program_state(0.0, $october, $persistent_enrollment, true, 1, '2026-10-01');
$vip_unconfirmed_later_threshold = forever_business_build_vip_program_state(.330, $october, [], true, 1, '2026-10-01');
$vip_duplicate_link = forever_business_build_vip_program_state(.330, $october, $persistent_enrollment, false, 2, '2026-10-01');
$vip_approved_shared_link = forever_business_build_vip_program_state(.330, $october, $persistent_enrollment, true, 2, '2026-10-01');
$catalog = forever_business_get_vip_task_catalog();
$task_meta = forever_business_vip_task_meta();
$catalog_is_lossless = true;
$catalog_result_types_are_explicit = true;
$catalog_quick_targets_are_explicit = true;
$catalog_reduced_fallbacks_are_bounded = true;
$catalog_fallback_training_copy_is_not_duplicated = true;
foreach($catalog as $catalog_track_key => $catalog_tasks) {
    $configured_result_types = $task_meta[$catalog_track_key]['result_types'] ?? [];
    $configured_targets = $task_meta[$catalog_track_key]['targets'] ?? [];
    $configured_quick_targets = $task_meta[$catalog_track_key]['quick_targets'] ?? [];
    if(count($configured_result_types) !== 30) $catalog_result_types_are_explicit = false;
    if(count($configured_targets) !== 30 || count($configured_quick_targets) !== 30) $catalog_quick_targets_are_explicit = false;
    foreach($catalog_tasks as $catalog_day => $catalog_task) {
        $task_text = (string) ($catalog_task['task_text'] ?? '');
        $task_parts = preg_split('/(?<!\d\.)(?<=[.!?])\s+/u', $task_text, 2);
        $first_sentence = rtrim(trim((string) ($task_parts[0] ?? $task_text)), '.!?');
        $title = rtrim((string) ($catalog_task['title'] ?? ''), '….!?');
        if(mb_strlen($first_sentence) > mb_strlen($title) && (string) ($catalog_task['instruction'] ?? '') !== $task_text) {
            $catalog_is_lossless = false;
        }
        if((string) ($catalog_task['expected_result_type'] ?? '') !== (string) ($configured_result_types[$catalog_day - 1] ?? '')) {
            $catalog_result_types_are_explicit = false;
        }
        if((int) ($catalog_task['quick_target'] ?? 0) !== (int) ($configured_quick_targets[$catalog_day - 1] ?? 0)
            || (int) ($catalog_task['quick_target'] ?? 0) < 1
            || (int) ($catalog_task['quick_target'] ?? 0) > (int) ($configured_targets[$catalog_day - 1] ?? 0)) {
            $catalog_quick_targets_are_explicit = false;
        }
        $configured_fallback = (string) ($task_meta[$catalog_track_key]['fallbacks'][$catalog_day] ?? '');
        if($configured_fallback !== ''
            && (int) ($configured_targets[$catalog_day - 1] ?? 1) > (int) ($configured_quick_targets[$catalog_day - 1] ?? 1)
            && mb_stripos($configured_fallback, 'brzog cilja') === false) {
            $catalog_reduced_fallbacks_are_bounded = false;
        }
        if(substr_count((string) ($catalog_task['fallback'] ?? ''), 'Edukacija / trening') > 1) {
            $catalog_fallback_training_copy_is_not_duplicated = false;
        }
    }
}
$starter_member = array_merge($base, ['personal_cc' => .5, 'is_4cc_active' => 0, 'verified_progress' => forever_business_get_verified_progress(array_merge($base, ['personal_cc' => .5, 'is_4cc_active' => 0]))]);
$sunday_morning = new DateTimeImmutable('2026-09-06 10:00:00', new DateTimeZone('Europe/Zagreb'));
$sunday_live = new DateTimeImmutable('2026-09-06 18:30:00', new DateTimeZone('Europe/Zagreb'));
$sunday_before_completion = new DateTimeImmutable('2026-09-06 19:29:59', new DateTimeZone('Europe/Zagreb'));
$sunday_after_completion = new DateTimeImmutable('2026-09-06 19:30:00', new DateTimeZone('Europe/Zagreb'));
$weekday_morning = new DateTimeImmutable('2026-09-07 10:00:00', new DateTimeZone('Europe/Zagreb'));
$sunday_action = forever_business_get_action($starter_member, null, 0, false, $sunday_morning);
$sunday_live_action = forever_business_get_action($starter_member, null, 0, false, $sunday_live);
$sunday_before_completion_action = forever_business_get_action($starter_member, null, 0, false, $sunday_before_completion);
$sunday_after_completion_action = forever_business_get_action($starter_member, null, 0, false, $sunday_after_completion);
$after_sunday_action = forever_business_get_action($starter_member, null, 0, true, $sunday_morning, true);
$after_weekday_action = forever_business_get_action($starter_member, null, 0, false, $weekday_morning, true);
$completed_program = forever_business_get_action($starter_member, null, 30, true, $weekday_morning);
$fixed_builder_track = forever_business_get_vip_track(array_merge($starter_member, ['personal_cc' => 0, 'vip_base_personal_cc' => 4, 'vip_base_is_4cc_active' => 1, 'vip_current_personal_cc' => 0, 'vip_current_is_4cc_active' => 0]));
$upgraded_activator_track = forever_business_get_vip_track(array_merge($starter_member, ['personal_cc' => 0, 'vip_base_personal_cc' => .5, 'vip_base_is_4cc_active' => 0, 'vip_current_personal_cc' => 1.2, 'vip_current_is_4cc_active' => 0]));
$reactivation_track = forever_business_get_vip_track(array_merge($starter_member, ['vip_base_personal_cc' => .5, 'vip_base_is_4cc_active' => 0, 'vip_base_previous_personal_cc' => .4, 'vip_current_personal_cc' => .5, 'vip_current_is_4cc_active' => 0]));
$rolling_activator_after_drop = forever_business_get_vip_track(array_merge($starter_member, ['vip_base_personal_cc' => 1.2, 'vip_base_is_4cc_active' => 0, 'vip_august_is_4cc_active' => 0, 'vip_current_personal_cc' => 0, 'vip_current_is_4cc_active' => 0]));
$activator_first_action = forever_business_get_action(array_merge($starter_member, ['vip_base_personal_cc' => 1.2, 'vip_base_is_4cc_active' => 0, 'vip_august_is_4cc_active' => 0, 'vip_current_personal_cc' => 0, 'vip_current_is_4cc_active' => 0]), null, 0, false, $weekday_morning);
$reactivation_from_12m_history = forever_business_get_vip_track(array_merge($starter_member, ['vip_base_personal_cc' => .5, 'vip_base_is_4cc_active' => 0, 'vip_base_previous_personal_cc' => 0, 'vip_base_focus_previous_active' => 0, 'vip_base_had_previous_activity_12m' => 1, 'vip_current_personal_cc' => .5, 'vip_current_is_4cc_active' => 0]));
$adapted_builder_action = forever_business_get_action(array_merge($base, [
    'vip_base_personal_cc' => 4,
    'vip_base_is_4cc_active' => 1,
    'vip_current_personal_cc' => 4,
    'vip_current_is_4cc_active' => 1,
    'vip_last_difficulty' => 'hard',
    'verified_progress' => $activity,
]), null, 1, false, $weekday_morning);
$already_minimum_quick_action = forever_business_get_action(array_merge($starter_member, [
    'vip_last_difficulty' => 'hard',
]), null, 6, false, $weekday_morning);
$simulated_regular_steps = 0;
$simulated_total_tasks = 0;
$simulated_completion_date = '';
$simulated_day = new DateTimeImmutable('2026-09-01 20:00:00', new DateTimeZone('Europe/Zagreb'));
for($iteration = 0; $iteration < 60 && $simulated_regular_steps < 30; $iteration++, $simulated_day = $simulated_day->modify('+1 day')) {
    $simulated_action = forever_business_get_action($starter_member, null, $simulated_regular_steps, false, $simulated_day, false);
    if(empty($simulated_action['can_complete'])) continue;
    $simulated_total_tasks++;
    if(empty($simulated_action['is_weekly_plan'])) {
        $simulated_regular_steps++;
        if($simulated_regular_steps === 30) $simulated_completion_date = $simulated_day->format('Y-m-d');
    }
}

$make_track_member = static function(string $title, int $august_official, int $highest_rank = 0, int $highest_nonleader_rank = 0) use ($base): array {
    $member = array_merge($base, [
        'title' => $title,
        'is_4cc_active' => $august_official,
        'vip_base_personal_cc' => 1,
        'vip_base_is_4cc_active' => $august_official,
        'vip_current_personal_cc' => 1,
        'vip_current_is_4cc_active' => $august_official,
        'vip_highest_track_rank' => $highest_rank,
        'vip_highest_nonleader_track_rank' => $highest_nonleader_rank,
    ]);
    $member['verified_progress'] = forever_business_get_verified_progress($member);
    return $member;
};
$recognized_manager_active_track = forever_business_get_vip_track($make_track_member('Recognized Manager', 1));
$recognized_manager_inactive_track = forever_business_get_vip_track($make_track_member('Recognized Manager', 0));
$assistant_manager_active_track = forever_business_get_vip_track($make_track_member('Assistant Manager', 1));
$unrecognized_manager_active_track = forever_business_get_vip_track($make_track_member('Unrecognized Manager', 1));
$historical_leader_without_qualification = forever_business_get_vip_track($make_track_member('Recognized Manager', 0, 4, 3));
$forced_admin_leader = forever_business_get_vip_track(array_merge($make_track_member('Recognized Manager', 0), ['force_vip_leader' => true]));

$rule_assertions = [
    'official positive 4 CC signal wins even when supporting numbers are incomplete' => $official_active_formula_low['is_officially_active'] && $official_active_formula_low['is_4cc_active'] && $official_active_formula_low['official_activity_signal'] === 1 && $official_active_formula_low['activity_source'] === 'official',
    'official negative 4 CC signal wins even when the supporting formula is met' => !$official_inactive_formula_high['is_officially_active'] && !$official_inactive_formula_high['is_4cc_active'] && $official_inactive_formula_high['official_activity_signal'] === 0 && $official_inactive_formula_high['activity_source'] === 'official',
    'unknown official signal uses inclusive 1 Personal and 4 Total Active CC fallback' => $fallback_two_plus_remainder['is_4cc_active'] && $fallback_four_personal['is_4cc_active'] && !$fallback_personal_below['is_4cc_active'] && !$fallback_total_below['is_4cc_active'] && $fallback_two_plus_remainder['activity_source'] === 'formula' && $fallback_four_personal['activity_source'] === 'formula',
    'formula fallback is not mislabeled as an official positive signal' => !$fallback_two_plus_remainder['is_officially_active'] && $fallback_two_plus_remainder['official_activity_signal'] === null,
    'Assistant Supervisor path is 10 Total CC in one month' => $activity['rank']['windows'][0]['target'] === 10.0 && $activity['rank']['windows'][0]['gap'] === 1.0,
    'Supervisor path is 60 Total CC in two months' => $supervisor['rank']['windows'][0]['target'] === 60.0 && $supervisor['rank']['windows'][0]['gap'] === 15.0,
    'Assistant Manager paths are 120 in two or 150 in four months' => $assistant_manager['rank']['windows'][0]['gap'] === 50.0 && $assistant_manager['rank']['windows'][1]['gap'] === 30.0,
    'Unrecognized Manager remains on the recognized Manager qualification path' => $unrecognized_manager['rank']['mode'] === 'rank' && $unrecognized_manager['rank']['next_title'] === 'Recognized Manager',
    'Manager target advances from 60 to 100 Non-Manager CC' => $manager_60['rank']['windows'][0]['target'] === 60.0 && $manager_100['rank']['windows'][0]['target'] === 100.0,
    'Zagreb month changes at 22:00 UTC during summer time' => forever_business_current_zagreb_period($september_boundary_utc) === '2026-09-01',
    'missing September monthly CC starts at numeric zero without changing YTD' => $current_month_zero['personal_cc'] === 0.0 && $current_month_zero['total_cc'] === 0.0 && $current_month_zero['total_active_cc'] === 0.0 && $current_month_zero['total_active_cc_ytd'] === 87.5,
    'historical monthly values are never rewritten by open-month normalization' => $historical_month_unchanged['personal_cc'] === null && $historical_month_unchanged['total_cc'] === null && $historical_month_unchanged['total_active_cc'] === null && $historical_month_unchanged['total_active_cc_ytd'] === 87.5,
    'multi-month rank window keeps August while September correctly contributes zero' => $supervisor_at_month_boundary['rank']['windows'][0]['complete'] && $supervisor_at_month_boundary['rank']['windows'][0]['current'] === 45.0 && $supervisor_at_month_boundary['rank']['windows'][0]['gap'] === 15.0,
    'completed action advances to a new detailed next step' => $first_action['key'] !== $second_action['key'] && empty($first_action['checklist']) && !empty($first_action['success_definition']),
    'UTC synchronization time is displayed in Zagreb summer time' => $zagreb_time === '14.08.2026. 08:53',
    '0.329 personal CC does not unlock VIP education' => !$vip_below_threshold['is_eligible'] && !$vip_below_threshold['can_access_education'] && $vip_below_threshold['gap_cc'] === .001,
    'rolling enrollment accepts current and past program months but ignores future periods' => forever_business_vip_eligibility_period_is_open('2026-08-01', $august_sync_time) && !forever_business_vip_eligibility_period_is_open('2026-07-01', $august_sync_time) && !forever_business_vip_eligibility_period_is_open('2026-09-01', $august_sync_time),
    'general FLP period guard accepts past and current months but rejects future months' => forever_business_period_is_current_or_past('2026-07-01', $august_sync_time) && forever_business_period_is_current_or_past('2026-08-01', $august_sync_time) && !forever_business_period_is_current_or_past('2026-09-01', $august_sync_time),
    '0.330 personal CC qualifies but stays locked before September' => $vip_qualified_waiting['is_eligible'] && !$vip_qualified_waiting['is_launched'] && !$vip_qualified_waiting['can_access_education'] && $vip_qualified_waiting['seconds_remaining'] === 1,
    'qualified VIP education opens exactly at September launch' => $vip_active['is_eligible'] && $vip_active['is_launched'] && $vip_active['can_access_education'] && $vip_active['status'] === 'active',
    'permanent enrollment remains active after a later zero month' => $vip_persistent_after_drop['is_enrolled'] && $vip_persistent_after_drop['can_access_education'] && $vip_persistent_after_drop['qualifying_period'] === '2026-09-01' && (float) $vip_persistent_after_drop['current_personal_cc'] === 0.0,
    'a numeric threshold without persisted trusted enrollment cannot bypass the gate' => !$vip_unconfirmed_later_threshold['is_enrolled'] && !$vip_unconfirmed_later_threshold['can_access_education'] && $vip_unconfirmed_later_threshold['status'] === 'waiting_confirmation',
    'duplicate active FCC linkage fails closed without deleting enrollment' => $vip_duplicate_link['is_enrolled'] && !$vip_duplicate_link['has_valid_linkage'] && !$vip_duplicate_link['can_access_education'] && $vip_duplicate_link['status'] === 'duplicate_linkage',
    'administrator-approved shared FCC linkage keeps VIP access open' => $vip_approved_shared_link['is_enrolled'] && $vip_approved_shared_link['has_valid_linkage'] && $vip_approved_shared_link['is_shared_linkage'] && $vip_approved_shared_link['can_access_education'] && $vip_approved_shared_link['linkage_status'] === 'shared',
    'all five VIP levels contain exactly 30 reviewed tasks' => count($catalog) === 5 && count($catalog['starter'] ?? []) === 30 && count($catalog['activator'] ?? []) === 30 && count($catalog['builder'] ?? []) === 30 && count($catalog['leader'] ?? []) === 30 && count($catalog['reactivation'] ?? []) === 30,
    'long task titles never hide part of the original member instruction' => $catalog_is_lossless,
    'all 150 result categories come from explicit reviewed metadata' => $catalog_result_types_are_explicit,
    'all 150 quick targets are explicit positive and no larger than the full target' => $catalog_quick_targets_are_explicit,
    'every reduced fallback is bounded by the currently displayed quick target' => $catalog_reduced_fallbacks_are_bounded,
    'fallbacks do not repeat the mentor training instruction' => $catalog_fallback_training_copy_is_not_duplicated,
    'Sunday Marketing plan is visible for preparation but cannot be completed before its 19:30 end' => !empty($sunday_action['is_weekly_plan']) && str_starts_with($sunday_action['key'], 'vip26_sunday_') && !$sunday_action['can_complete'] && !$sunday_live_action['can_complete'] && !$sunday_before_completion_action['can_complete'] && $sunday_after_completion_action['can_complete'] && !empty($sunday_action['is_waiting_for_event_completion']),
    'a completed Sunday task keeps the regular step locked until tomorrow' => !empty($after_sunday_action['is_daily_complete']) && !$after_sunday_action['can_complete'] && $after_sunday_action['sequence_position'] === 1,
    'a completed weekday task also keeps the next VIP task locked until tomorrow' => !empty($after_weekday_action['is_daily_complete']) && !$after_weekday_action['can_complete'] && $after_weekday_action['sequence_position'] === 1,
    'program stops cleanly after 30 sequence steps' => !empty($completed_program['is_program_complete']) && !$completed_program['can_complete'] && $completed_program['sequence_position'] === 30,
    'daily September cohort completes 30 regular plus five Sunday tasks on 5 October' => $simulated_regular_steps === 30 && $simulated_total_tasks === 35 && $simulated_completion_date === '2026-10-05',
    'VIP group and first Marketing plan are available in the qualified program state' => str_contains($vip_active['whatsapp_group_url'], 'G0Mxgm8yXfrIDAOxNqPbmw') && ($vip_active['marketing_plan']['weekday'] ?? 0) === 7 && ($vip_active['marketing_plan']['time_label'] ?? '') === '18:00' && ($vip_active['marketing_plan']['next_at_display'] ?? '') === 'nedjelja, 06.09.2026. u 18:00' && ($vip_active['marketing_plan']['url'] ?? '') === 'https://forevercard.club/vip-edukacija',
    'August Builder level cannot drop when a later month starts at zero' => $fixed_builder_track['key'] === 'builder',
    'later qualifying Activator level cannot drop when the next month starts at zero' => $rolling_activator_after_drop['key'] === 'activator',
    'new synchronized results can raise Starter to Activator' => $upgraded_activator_track['key'] === 'activator',
    'August returners receive the Reaktivacija path' => $reactivation_track['key'] === 'reactivation',
    'twelve-month historical activity also receives the Reaktivacija path' => $reactivation_from_12m_history['key'] === 'reactivation',
    'Leader requires both full recognized Manager status and official August 4 CC' => $recognized_manager_active_track['key'] === 'leader' && $recognized_manager_inactive_track['key'] !== 'leader',
    'Assistant and Unrecognized Manager do not receive Leader solely from their title' => $assistant_manager_active_track['key'] === 'builder' && $unrecognized_manager_active_track['key'] === 'builder',
    'historical Leader completion cannot bypass the stricter current qualification' => $historical_leader_without_qualification['key'] === 'builder',
    'root administrator profile can be placed on the Leader curriculum only by a server flag' => $forced_admin_leader['key'] === 'leader',
    'outcome count accepts only strict integers from 1 through 999' => forever_business_normalize_outcome_count(1) === 1 && forever_business_normalize_outcome_count('999') === 999 && forever_business_normalize_outcome_count(0) === null && forever_business_normalize_outcome_count(-1) === null && forever_business_normalize_outcome_count('1.0') === null && forever_business_normalize_outcome_count(1000) === null && forever_business_normalize_outcome_count(null) === null,
    'structured result and difficulty vocabularies reject arbitrary values' => forever_business_normalize_result_type('conversation') === 'conversation' && forever_business_normalize_result_type('planning') === 'planning' && forever_business_normalize_result_type('made_up') === null && forever_business_normalize_difficulty('hard') === 'hard' && forever_business_normalize_difficulty('extreme') === null,
    'planning and preparation tasks do not inflate training or direct-action analytics' => ($catalog['starter'][1]['expected_result_type'] ?? '') === 'planning' && ($catalog['starter'][10]['expected_result_type'] ?? '') === 'planning' && ($catalog['activator'][24]['expected_result_type'] ?? '') === 'planning' && ($catalog['builder'][24]['expected_result_type'] ?? '') === 'planning' && ($catalog['leader'][3]['expected_result_type'] ?? '') === 'planning' && ($catalog['leader'][3]['target'] ?? 0) === 1 && ($catalog['leader'][6]['expected_result_type'] ?? '') === 'planning' && ($catalog['leader'][27]['expected_result_type'] ?? '') === 'planning' && ($catalog['reactivation'][17]['expected_result_type'] ?? '') === 'planning',
    'Activator opens with a measurable public FCC app-link setup and a real lighter version' => ($catalog['activator'][1]['core'] ?? '') === 'Productivity' && ($catalog['activator'][1]['target'] ?? 0) === 1 && ($catalog['activator'][1]['quick_target'] ?? 0) === 1 && ($catalog['activator'][1]['expected_result_type'] ?? '') === 'content' && ($catalog['activator'][1]['allowed_result_types'] ?? []) === ['content'] && count($catalog['activator'][1]['checklist'] ?? []) === 3 && mb_stripos((string) ($catalog['activator'][1]['task_text'] ?? ''), 'kopiraj link') !== false && str_contains((string) ($catalog['activator'][1]['success_definition'] ?? ''), 'spremljen i provjeren link') && str_contains((string) ($catalog['activator'][1]['fallback'] ?? ''), 'Za lakšu verziju') && empty($catalog['activator'][1]['message_example']) && ($activator_first_action['key'] ?? '') === 'vip26_activator_d01_biolink' && substr_count($helper, "action_key <> 'vip26_activator_d01'") >= 6 && !str_contains($vip_tasks, 'Provjeri osobni CC, Total Active CC i koliko ti još nedostaje do 4 CC'),
    'explicit task targets fix formerly ambiguous Croatian quantities' => ($catalog['starter'][23]['target'] ?? 0) === 2 && ($catalog['starter'][29]['target'] ?? 0) === 5 && ($catalog['starter'][30]['target'] ?? 0) === 1 && ($catalog['activator'][9]['target'] ?? 0) === 2 && ($catalog['activator'][11]['target'] ?? 0) === 5 && ($catalog['activator'][23]['target'] ?? 0) === 3 && ($catalog['builder'][4]['target'] ?? 0) === 5 && ($catalog['builder'][19]['target'] ?? 0) === 1 && ($catalog['builder'][23]['target'] ?? 0) === 3 && ($catalog['builder'][23]['quick_target'] ?? 0) === 1 && ($catalog['reactivation'][2]['quick_target'] ?? 0) === 1 && ($catalog['reactivation'][7]['target'] ?? 0) === 3 && ($catalog['reactivation'][23]['target'] ?? 0) === 2 && ($catalog['leader'][1]['quick_target'] ?? 0) === 1 && ($catalog['leader'][6]['target'] ?? 0) === 1 && ($catalog['leader'][27]['target'] ?? 0) === 1 && ($catalog['leader'][28]['target'] ?? 0) === 3,
    'message examples and resource fallbacks are connected only to reviewed relevant tasks' => !empty($catalog['starter'][4]['message_example']) && empty($catalog['starter'][13]['message_example']) && empty($catalog['starter'][27]['message_example']) && empty($catalog['activator'][1]['message_example']) && !empty($catalog['activator'][15]['message_example']) && empty($catalog['activator'][18]['message_example']) && empty($catalog['activator'][25]['message_example']) && !empty($catalog['builder'][15]['message_example']) && !empty($catalog['builder'][22]['message_example']) && empty($catalog['builder'][25]['message_example']) && empty($catalog['leader'][6]['message_example']) && empty($catalog['leader'][26]['message_example']) && empty($catalog['leader'][29]['message_example']) && !empty($catalog['reactivation'][4]['message_example']) && !empty($catalog['reactivation'][8]['message_example']) && empty($catalog['reactivation'][11]['message_example']) && empty($catalog['reactivation'][21]['message_example']) && empty($catalog['reactivation'][25]['message_example']) && empty($catalog['reactivation'][27]['message_example']) && !empty($catalog['starter'][7]['fallback']) && !empty($catalog['leader'][1]['fallback']),
    'neutral examples never assume event attendance or previous product use' => str_contains((string) ($catalog['builder'][11]['message_example'] ?? ''), 'našeg razgovora') && !str_contains((string) ($catalog['builder'][11]['message_example'] ?? ''), 'bio/la s nama') && !str_contains((string) ($catalog['reactivation'][4]['message_example'] ?? ''), 'ranije koristio/la') && !str_contains((string) ($catalog['reactivation'][8]['message_example'] ?? ''), 'ranije koristio/la'),
    'Reactivation day 3 records its actual invitation rather than misclassifying it as training' => ($catalog['reactivation'][3]['expected_result_type'] ?? '') === 'invitation' && in_array('invitation', $catalog['reactivation'][3]['allowed_result_types'] ?? [], true),
    'numeric day references do not cut the task title at 21.' => str_contains($catalog['builder'][14]['title'] ?? '', '30. dan'),
    'completion modes enforce quick and full thresholds' => forever_business_vip_completion_mode_for_count(['target' => 10, 'quick_target' => 5], 4) === null && forever_business_vip_completion_mode_for_count(['target' => 10, 'quick_target' => 5], 5) === 'quick' && forever_business_vip_completion_mode_for_count(['target' => 10, 'quick_target' => 5], 10) === 'standard',
    'a hard previous step automatically reduces the next explicit numeric quick target without misclassifying a mentor simulation' => !empty($adapted_builder_action['is_adaptively_simplified']) && ($adapted_builder_action['target'] ?? 0) === 20 && ($adapted_builder_action['quick_target'] ?? 0) === 3 && str_contains((string) ($adapted_builder_action['fallback'] ?? ''), 'Edukacija / trening'),
    'adaptive copy is not shown and a reviewed fallback is preserved when the quick target is already one' => empty($already_minimum_quick_action['is_adaptively_simplified']) && ($already_minimum_quick_action['quick_target'] ?? 0) === 1 && str_contains((string) ($already_minimum_quick_action['fallback'] ?? ''), 'Ako danas nemaš kupca'),
];

$failed_rules = array_keys(array_filter($rule_assertions, static fn($passed) => !$passed));
if($failed_rules) {
    fwrite(STDERR, "Forever business rule check failed:\n- " . implode("\n- ", $failed_rules) . "\n");
    exit(1);
}

echo "Forever business safety checks passed.\n";

/* /Custom code: FC-2026-08-13 */
