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
    'Focus Group remains diagnostic and never overwrites authoritative monthly metrics' => str_contains($helper, 'forever_business_optional_number') && str_contains($helper, "if(\$report['kind'] === 'focus_group') continue;") && str_contains($helper, 'Focus Group stays in its dedicated diagnostic table'),
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
    'completed VIP task view shows a server-synchronized midnight countdown and refreshes at unlock' => str_contains($view, 'data-fb-next-unlock-at') && str_contains($view, 'data-fb-next-unlock-server-now') && str_contains($view, 'Sljedeći zadatak otključava se za') && str_contains($view, "window.location.reload()") && str_contains($view, "document.addEventListener('visibilitychange'") && str_contains($view, "window.addEventListener('pageshow'"),
    'VIP education path is discreet, expandable and distinct from official Forever positions' => str_contains($view, 'Tvoj put kroz edukaciju') && str_contains($view, 'Kako napreduju edukacijski smjerovi?') && str_contains($view, 'Edukacijski smjer:') && str_contains($view, 'Ne mijenjaju tvoju službenu Forever poziciju') && str_contains($user, "'preview_education_path'"),
    'verified education promotions survive monthly reset without allowing historical metrics to create Leader' => str_contains($helper, 'vip_verified_highest_track_rank') && str_contains($helper, 'vip_verified.period_month >= COALESCE(vip_enrollment.qualifying_period') && str_contains($helper, "vip_verified.period_month <= '{\$vip_current_period}'") && str_contains($helper, "(int) \$definitions['builder']['rank']"),
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
    'runtime schema verifies every launch table, starting-track field and per-level sequence while preserving legacy help flags' => str_contains($helper, 'forever_business_runtime_schema_v20260901_3') && str_contains($helper, '$required_tables') && str_contains($helper, "'starting_track_key'") && str_contains($helper, "'starting_track_reason'") && str_contains($helper, "'starting_track_decided_at'") && str_contains($helper, "'sequence_position'") && str_contains($helper, 'forever_business_outcome_user_track_idx') && str_contains($helper, "WHEN `action_key` = 'vip26_activator_d01' THEN NULL") && str_contains($helper, 'Legacy VIP help requests could not be preserved.') && str_contains($helper, '`outcome`.`needs_help` = 1'),
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

$focus_missing_personal_path = tempnam(sys_get_temp_dir(), 'fcc-focus-missing-');
$focus_blank_personal_path = tempnam(sys_get_temp_dir(), 'fcc-focus-blank-');
if(!$focus_missing_personal_path || !$focus_blank_personal_path) {
    fwrite(STDERR, "Forever business regression check could not create temporary Focus fixtures.\n");
    exit(1);
}
try {
    file_put_contents($focus_missing_personal_path,
        "FBO ID,FBO NAME,CURRENT LEVEL,NEXT LEVEL,LAST PURCHASE DATE,NEEDED CC FOR NEXT LEVEL\n"
        . "360000000991,Focus Missing,Supervisor,Assistant Manager,15-06-2026,10\n");
    file_put_contents($focus_blank_personal_path,
        "FBO ID,FBO NAME,CURRENT LEVEL,NEXT LEVEL,LAST PURCHASE DATE,NEEDED CC FOR NEXT LEVEL,PERSONAL CC\n"
        . "360000000992,Focus Blank,Supervisor,Assistant Manager,15-06-2026,10,\n");
    $focus_missing_personal_report = forever_business_parse_report($focus_missing_personal_path, 'focus-missing.csv', '', '', '2026-08-01');
    $focus_blank_personal_report = forever_business_parse_report($focus_blank_personal_path, 'focus-blank.csv', '', '', '2026-08-01');
} finally {
    @unlink($focus_missing_personal_path);
    @unlink($focus_blank_personal_path);
}

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
$sunday_completed = new DateTimeImmutable('2026-09-06 20:00:00', new DateTimeZone('Europe/Zagreb'));
$weekday_morning = new DateTimeImmutable('2026-09-07 10:00:00', new DateTimeZone('Europe/Zagreb'));
$weekday_late = new DateTimeImmutable('2026-09-07 23:59:30', new DateTimeZone('Europe/Zagreb'));
$weekday_late_same_instant_utc = new DateTimeImmutable('2026-09-07 21:59:30', new DateTimeZone('UTC'));
$next_weekday_midnight = new DateTimeImmutable('2026-09-08 00:00:00', new DateTimeZone('Europe/Zagreb'));
$saturday_completed = new DateTimeImmutable('2026-09-05 20:00:00', new DateTimeZone('Europe/Zagreb'));
$first_sunday_midnight = new DateTimeImmutable('2026-09-06 00:00:00', new DateTimeZone('Europe/Zagreb'));
$first_monday_midnight = new DateTimeImmutable('2026-09-07 00:00:00', new DateTimeZone('Europe/Zagreb'));
$spring_dst_night = new DateTimeImmutable('2026-03-29 00:30:00', new DateTimeZone('Europe/Zagreb'));
$autumn_dst_night = new DateTimeImmutable('2026-10-25 00:30:00', new DateTimeZone('Europe/Zagreb'));
$sunday_action = forever_business_get_action($starter_member, null, 0, false, $sunday_morning);
$sunday_live_action = forever_business_get_action($starter_member, null, 0, false, $sunday_live);
$sunday_before_completion_action = forever_business_get_action($starter_member, null, 0, false, $sunday_before_completion);
$sunday_after_completion_action = forever_business_get_action($starter_member, null, 0, false, $sunday_after_completion);
$after_sunday_action = forever_business_get_action($starter_member, null, 0, true, $sunday_completed, true);
$after_weekday_action = forever_business_get_action($starter_member, null, 0, false, $weekday_morning, true);
$after_weekday_late_action = forever_business_get_action($starter_member, null, 1, false, $weekday_late, true);
$after_weekday_late_utc_action = forever_business_get_action($starter_member, null, 1, false, $weekday_late_same_instant_utc, true);
$at_next_midnight_action = forever_business_get_action($starter_member, null, 1, false, $next_weekday_midnight, false);
$after_saturday_action = forever_business_get_action($starter_member, null, 0, false, $saturday_completed, true);
$at_sunday_midnight_action = forever_business_get_action($starter_member, null, 0, false, $first_sunday_midnight, false);
$at_monday_midnight_action = forever_business_get_action($starter_member, null, 0, false, $first_monday_midnight, false);
$spring_daily_complete_action = forever_business_get_action($starter_member, null, 1, true, $spring_dst_night, true);
$autumn_daily_complete_action = forever_business_get_action($starter_member, null, 1, true, $autumn_dst_night, true);
$completed_program = forever_business_get_action($starter_member, null, 30, true, $weekday_morning);
$confirmed_reactivation_member = array_merge($starter_member, [
    'vip_base_personal_cc' => .5,
    'vip_base_is_4cc_active' => 0,
    'vip_current_personal_cc' => .5,
    'vip_current_is_4cc_active' => 0,
    'vip_qualifying_period' => '2026-09-01',
    'vip_known_enrollment_date' => '2025-01-15',
    'vip_has_established_prior_activity' => 1,
    'vip_has_prior_activity_before_pause' => 1,
    'vip_previous_month_has_activity' => 0,
    'vip_previous_month_confirmed_inactive' => 1,
    'vip_starting_track_key' => 'reactivation',
    'vip_starting_track_reason' => 'return_after_pause',
]);
$timer_track_members = [
    $starter_member,
    array_merge($starter_member, ['vip_base_personal_cc' => 1.2, 'vip_base_is_4cc_active' => 0, 'vip_current_personal_cc' => 1.2, 'vip_current_is_4cc_active' => 0]),
    array_merge($starter_member, ['vip_base_personal_cc' => 4, 'vip_base_is_4cc_active' => 1, 'vip_current_personal_cc' => 4, 'vip_current_is_4cc_active' => 1]),
    array_merge($starter_member, ['force_vip_leader' => true]),
    $confirmed_reactivation_member,
];
$timer_track_unlocks = array_map(
    static fn(array $member): string => (string) (forever_business_get_action($member, null, 1, false, $weekday_late, true)['next_unlock_at_iso'] ?? ''),
    $timer_track_members
);
$fixed_builder_track = forever_business_get_vip_track(array_merge($starter_member, ['personal_cc' => 0, 'vip_base_personal_cc' => 4, 'vip_base_is_4cc_active' => 1, 'vip_current_personal_cc' => 0, 'vip_current_is_4cc_active' => 0]));
$upgraded_activator_track = forever_business_get_vip_track(array_merge($starter_member, ['personal_cc' => 0, 'vip_base_personal_cc' => .5, 'vip_base_is_4cc_active' => 0, 'vip_current_personal_cc' => 1.2, 'vip_current_is_4cc_active' => 0]));
$confirmed_reactivation_track = forever_business_get_vip_track($confirmed_reactivation_member);
$previous_cc_only_starter_track = forever_business_get_vip_track(array_merge($starter_member, ['vip_base_personal_cc' => .5, 'vip_base_is_4cc_active' => 0, 'vip_base_previous_personal_cc' => .4, 'vip_current_personal_cc' => .5, 'vip_current_is_4cc_active' => 0]));
$rolling_activator_after_drop = forever_business_get_vip_track(array_merge($starter_member, ['vip_base_personal_cc' => 1.2, 'vip_base_is_4cc_active' => 0, 'vip_august_is_4cc_active' => 0, 'vip_current_personal_cc' => 0, 'vip_current_is_4cc_active' => 0]));
$activator_first_action = forever_business_get_action(array_merge($starter_member, ['vip_base_personal_cc' => 1.2, 'vip_base_is_4cc_active' => 0, 'vip_august_is_4cc_active' => 0, 'vip_current_personal_cc' => 0, 'vip_current_is_4cc_active' => 0]), null, 0, false, $weekday_morning);
$promoted_activator_member = array_merge($starter_member, [
    'vip_base_personal_cc' => .5,
    'vip_base_is_4cc_active' => 0,
    'vip_current_personal_cc' => 1.2,
    'vip_current_is_4cc_active' => 0,
    'vip_highest_track_rank' => 1,
    'vip_starter_sequence_position' => 12,
    'vip_activator_sequence_position' => 0,
]);
$promoted_activator_first_action = forever_business_get_action($promoted_activator_member, null, 12, false, $weekday_morning);
$promoted_activator_after_first_action = forever_business_get_action(array_merge($promoted_activator_member, [
    'vip_activator_sequence_position' => 1,
]), null, 13, false, $next_weekday_midnight);
$legacy_activator_continuation = forever_business_get_action(array_merge($promoted_activator_member, [
    'vip_activator_sequence_position' => 12,
]), null, 12, false, $weekday_morning);
$promoted_builder_member = array_merge($starter_member, [
    'vip_base_personal_cc' => 1.2,
    'vip_base_is_4cc_active' => 0,
    'vip_current_personal_cc' => 1.2,
    'vip_current_total_active_cc' => 4.0,
    'vip_current_is_4cc_active' => 1,
    'vip_highest_track_rank' => 2,
    'vip_activator_sequence_position' => 29,
    'vip_builder_sequence_position' => 0,
]);
$promoted_builder_first_action = forever_business_get_action($promoted_builder_member, null, 29, false, $weekday_morning);
$completed_starter_then_promoted = forever_business_get_action(array_merge($promoted_activator_member, [
    'vip_starter_sequence_position' => 30,
]), null, 30, false, $weekday_morning);
$completed_current_level = forever_business_get_action(array_merge($promoted_activator_member, [
    'vip_activator_sequence_position' => 30,
]), null, 42, false, $weekday_morning);
$promotion_same_day_waits = forever_business_get_action($promoted_activator_member, null, 12, false, $weekday_late, true);
$promotion_sunday_override = forever_business_get_action($promoted_activator_member, null, 12, false, $sunday_after_completion);
$promotion_monday_first_action = forever_business_get_action($promoted_activator_member, null, 12, false, $first_monday_midnight);
$history_only_starter_track = forever_business_get_vip_track(array_merge($starter_member, ['vip_base_personal_cc' => .5, 'vip_base_is_4cc_active' => 0, 'vip_base_previous_personal_cc' => 0, 'vip_base_focus_previous_active' => 0, 'vip_base_had_previous_activity_12m' => 1, 'vip_current_personal_cc' => .5, 'vip_current_is_4cc_active' => 0]));
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
$starter_path = forever_business_get_vip_education_path(array_merge($starter_member, [
    'vip_base_personal_cc' => .5,
    'vip_base_is_4cc_active' => 0,
    'vip_current_personal_cc' => .4,
    'vip_current_total_active_cc' => .8,
    'vip_current_is_4cc_active' => 0,
]));
$reactivation_path = forever_business_get_vip_education_path(array_merge($confirmed_reactivation_member, [
    'vip_current_personal_cc' => .25,
    'vip_current_total_active_cc' => .5,
    'vip_current_is_4cc_active' => 0,
]));
$activator_path = forever_business_get_vip_education_path(array_merge($starter_member, [
    'vip_base_personal_cc' => 1.2,
    'vip_base_is_4cc_active' => 0,
    'vip_august_is_4cc_active' => 0,
    'vip_current_personal_cc' => .6,
    'vip_current_total_active_cc' => 2,
    'vip_current_is_4cc_active' => 0,
]));
$activator_official_blocked_path = forever_business_get_vip_education_path(array_merge($starter_member, [
    'vip_base_personal_cc' => 1.2,
    'vip_base_is_4cc_active' => 0,
    'vip_august_is_4cc_active' => 0,
    'vip_current_personal_cc' => 1.2,
    'vip_current_total_active_cc' => 4,
    'vip_current_is_4cc_active' => 0,
]));
$fallback_builder_path = forever_business_get_vip_education_path(array_merge($starter_member, [
    'vip_base_personal_cc' => 1.2,
    'vip_base_is_4cc_active' => 0,
    'vip_august_is_4cc_active' => 0,
    'vip_current_personal_cc' => 1,
    'vip_current_total_active_cc' => 4,
    'vip_current_is_4cc_active' => null,
]));
$official_positive_builder_path = forever_business_get_vip_education_path(array_merge($starter_member, [
    'vip_base_personal_cc' => 1.2,
    'vip_base_is_4cc_active' => 0,
    'vip_august_is_4cc_active' => 0,
    'vip_current_personal_cc' => 0,
    'vip_current_total_active_cc' => 0,
    'vip_current_is_4cc_active' => 1,
]));
$builder_zero_path = forever_business_get_vip_education_path(array_merge($starter_member, [
    'vip_base_personal_cc' => 4,
    'vip_base_total_active_cc' => 4,
    'vip_base_is_4cc_active' => 1,
    'vip_august_is_4cc_active' => 1,
    'vip_current_personal_cc' => 0,
    'vip_current_total_active_cc' => 0,
    'vip_current_is_4cc_active' => 0,
]));
$leader_path = forever_business_get_vip_education_path(array_merge($make_track_member('Recognized Manager', 1), ['force_vip_leader' => true]));
$assistant_manager_path = forever_business_get_vip_education_path($make_track_member('Assistant Manager', 1));
$historical_unqualified_leader_path = forever_business_get_vip_education_path($make_track_member('Recognized Manager', 0, 4, 3));
$persisted_dynamic_activator = forever_business_get_vip_track(array_merge($starter_member, [
    'vip_base_personal_cc' => .5,
    'vip_base_is_4cc_active' => 0,
    'vip_current_personal_cc' => 0,
    'vip_current_total_active_cc' => 0,
    'vip_current_is_4cc_active' => 0,
    'vip_verified_highest_track_rank' => 2,
]));
$persisted_dynamic_builder = forever_business_get_vip_track(array_merge($starter_member, [
    'vip_base_personal_cc' => .5,
    'vip_base_is_4cc_active' => 0,
    'vip_current_personal_cc' => 0,
    'vip_current_total_active_cc' => 0,
    'vip_current_is_4cc_active' => 0,
    'vip_verified_highest_track_rank' => 3,
]));
$verified_metrics_cannot_create_leader = forever_business_get_vip_track(array_merge($starter_member, [
    'title' => 'Recognized Manager',
    'vip_base_personal_cc' => .5,
    'vip_base_is_4cc_active' => 0,
    'vip_august_is_4cc_active' => 0,
    'vip_current_personal_cc' => 0,
    'vip_current_total_active_cc' => 0,
    'vip_current_is_4cc_active' => 0,
    'vip_verified_highest_track_rank' => 4,
    'verified_progress' => forever_business_get_verified_progress(array_merge($base, ['title' => 'Recognized Manager', 'is_4cc_active' => 0])),
]));

$vip_start_classifier_base = [
    'vip_qualifying_period' => '2026-09-01',
    'vip_known_enrollment_date' => null,
    'vip_has_established_prior_activity' => false,
    'vip_has_prior_activity_before_pause' => false,
    'vip_previous_month_has_activity' => false,
    'vip_previous_month_confirmed_inactive' => false,
    'vip_starting_track_key' => null,
    'vip_starting_track_reason' => null,
];
$vip_start_recent_enrollment_q = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2026-09-01',
]));
$vip_start_recent_enrollment_q_minus_1 = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2026-08-15',
    'vip_previous_month_has_activity' => true,
]));
$vip_start_recent_enrollment_q_minus_3_boundary = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2026-06-01',
    'vip_has_established_prior_activity' => true,
    'vip_has_prior_activity_before_pause' => true,
    'vip_previous_month_confirmed_inactive' => true,
]));
$vip_start_return_before_recent_boundary = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2026-05-31',
    'vip_has_prior_activity_before_pause' => true,
    'vip_previous_month_confirmed_inactive' => true,
]));
$vip_start_return_without_known_enrollment_date = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_has_established_prior_activity' => true,
    'vip_has_prior_activity_before_pause' => true,
    'vip_previous_month_confirmed_inactive' => true,
]));
$vip_start_legacy_1999_returner = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '1999-12-31',
    'vip_has_prior_activity_before_pause' => true,
    'vip_previous_month_confirmed_inactive' => true,
]));
$vip_start_active_previous_month = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2025-01-15',
    'vip_has_established_prior_activity' => true,
    'vip_has_prior_activity_before_pause' => true,
    'vip_previous_month_has_activity' => true,
]));
$vip_start_missing_history = forever_business_classify_vip_start($vip_start_classifier_base);
$vip_start_missing_qualifying_period = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_qualifying_period' => null,
    'vip_known_enrollment_date' => '2025-01-15',
    'vip_has_established_prior_activity' => true,
    'vip_has_prior_activity_before_pause' => true,
    'vip_previous_month_confirmed_inactive' => true,
]));
$vip_start_invalid_enrollment_date = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2026-02-31',
    'vip_has_established_prior_activity' => true,
    'vip_has_prior_activity_before_pause' => true,
    'vip_previous_month_confirmed_inactive' => true,
]));
$vip_start_conflicting_previous_month_signals = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2025-01-15',
    'vip_has_established_prior_activity' => true,
    'vip_has_prior_activity_before_pause' => true,
    'vip_previous_month_has_activity' => true,
    'vip_previous_month_confirmed_inactive' => true,
]));
$vip_start_persisted_starter = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2025-01-15',
    'vip_has_established_prior_activity' => true,
    'vip_has_prior_activity_before_pause' => true,
    'vip_previous_month_confirmed_inactive' => true,
    'vip_starting_track_key' => 'starter',
    'vip_starting_track_reason' => 'recent_enrollment',
]));
$vip_start_persisted_reactivation = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2026-09-01',
    'vip_starting_track_key' => 'reactivation',
    'vip_starting_track_reason' => 'return_after_pause',
]));
$vip_start_unknown_persisted_reason = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2025-01-15',
    'vip_has_established_prior_activity' => true,
    'vip_has_prior_activity_before_pause' => true,
    'vip_previous_month_confirmed_inactive' => true,
    'vip_starting_track_key' => 'starter',
    'vip_starting_track_reason' => 'legacy_unknown_reason',
]));
$vip_start_mismatched_persisted_pair = forever_business_classify_vip_start(array_merge($vip_start_classifier_base, [
    'vip_known_enrollment_date' => '2026-09-01',
    'vip_starting_track_key' => 'reactivation',
    'vip_starting_track_reason' => 'recent_enrollment',
]));

$rule_assertions = [
    'Focus reports with a missing or blank Personal CC keep the value unknown and never create authoritative monthly metrics' => empty($focus_missing_personal_report['metrics'])
        && empty($focus_blank_personal_report['metrics'])
        && array_key_exists('personal_cc', $focus_missing_personal_report['focus_metrics']['360000000991|2026-08-01'] ?? [])
        && array_key_exists('personal_cc', $focus_blank_personal_report['focus_metrics']['360000000992|2026-08-01'] ?? [])
        && $focus_missing_personal_report['focus_metrics']['360000000991|2026-08-01']['personal_cc'] === null
        && $focus_blank_personal_report['focus_metrics']['360000000992|2026-08-01']['personal_cc'] === null,
    'optional report numbers distinguish missing values from an explicit zero' => forever_business_optional_number('') === null
        && forever_business_optional_number('not-a-number') === null
        && forever_business_optional_number('0') === 0.0,
    'invalid imported calendar dates are rejected instead of being normalized into another month' => forever_business_parse_date('31-02-2026') === null && forever_business_parse_date('2026-02-31') === null && forever_business_parse_date('28-02-2026') === '2026-02-28',
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
    'reviewed Starter edits keep real beginner alternatives and official-account verification' => ($catalog['starter'][7]['target'] ?? 0) === 2
        && ($catalog['starter'][7]['quick_target'] ?? 0) === 1
        && str_contains((string) ($catalog['starter'][7]['task_text'] ?? ''), 'Ako ih još nemaš')
        && str_contains((string) ($catalog['starter'][17]['task_text'] ?? ''), 'pošalji ih jednom gostu')
        && ($catalog['starter'][17]['expected_result_type'] ?? '') === 'invitation'
        && str_contains((string) ($catalog['starter'][18]['task_text'] ?? ''), 'zajednički Zoom s mentorom')
        && str_contains((string) ($catalog['starter'][20]['task_text'] ?? ''), 'službenom Foreverliving.com računu')
        && str_contains((string) ($catalog['starter'][20]['task_text'] ?? ''), 'mjesečni CC ponovno kreće od nule') === false
        && str_contains((string) ($catalog['starter'][20]['task_text'] ?? ''), 'kreću od 0')
        && count((array) ($catalog['starter'][20]['checklist'] ?? [])) === 4,
    'reviewed Activator edits preserve natural outreach and measurable preparation' => str_contains((string) ($catalog['activator'][2]['task_text'] ?? ''), 'Na papir ili u svoje bilješke')
        && str_contains((string) ($catalog['activator'][5]['task_text'] ?? ''), 'praktičan savjet')
        && str_contains((string) ($catalog['activator'][13]['task_text'] ?? ''), 'doradi je svojim riječima')
        && str_contains((string) ($catalog['activator'][17]['success_definition'] ?? ''), 'Poruka dobrodošlice i pitanje'),
    'reviewed Builder edits connect ratios, guests and team follow-up to mentor support' => str_contains((string) ($catalog['builder'][13]['task_text'] ?? ''), 's mentorom dogovori')
        && str_contains((string) ($catalog['builder'][17]['task_text'] ?? ''), 'poruku dobrodošlice')
        && str_contains((string) ($catalog['builder'][24]['task_text'] ?? ''), 'suradnikom koji ga je pozvao')
        && str_contains((string) ($catalog['builder'][24]['fallback'] ?? ''), 'tvoja podrška'),
    'reviewed Leader edits match the Stjepan-led Marketing plan and develop independent leaders' => str_contains((string) ($catalog['leader'][3]['task_text'] ?? ''), 'početno pitanje') === false
        && str_contains((string) ($catalog['leader'][3]['success_definition'] ?? ''), 'početno pitanje')
        && str_contains((string) ($catalog['leader'][10]['task_text'] ?? ''), 'nakon prezentacije')
        && str_contains((string) ($catalog['leader'][17]['task_text'] ?? ''), 'individualni Zoom')
        && str_contains((string) ($catalog['leader'][22]['task_text'] ?? ''), 'Supervisorom ili Managerom')
        && str_contains((string) ($catalog['leader'][24]['task_text'] ?? ''), 'dogovori sa Stjepanom')
        && str_contains((string) ($catalog['leader'][25]['task_text'] ?? ''), 'statusa gostiju svojih suradnika')
        && str_contains((string) ($catalog['leader'][26]['task_text'] ?? ''), 'samostalno vodi dva kratka follow-up razgovora'),
    'reviewed Reaktivacija edits use the Tuesday webinar and warm mentor follow-up' => str_contains((string) ($catalog['reactivation'][10]['task_text'] ?? ''), 'sljedeći timski webinar utorkom')
        && str_contains((string) ($catalog['reactivation'][17]['task_text'] ?? ''), 'zajednički Zoom s tobom i mentorom')
        && str_contains((string) ($catalog['reactivation'][17]['message_example'] ?? ''), 'Baš mi je drago'),
    'member copy excludes rejected internal or hostile draft language' => !str_contains($vip_tasks, 'zatvoriti registraciju')
        && !str_contains($vip_tasks, 'atribucija gostiju')
        && !str_contains($vip_tasks, 'vlasnici follow-upa')
        && !str_contains($vip_tasks, 'nemoj biti naporan')
        && !str_contains($vip_tasks, 'bez kopiranja kontakata')
        && !str_contains($vip_tasks, 'komercijalni nastavak našeg razgovora'),
    'a confirmed promotion starts the new education level at its own first step' => ($promoted_activator_first_action['key'] ?? '') === 'vip26_activator_d01_biolink' && ($promoted_activator_first_action['sequence_position'] ?? 0) === 1,
    'current-level progress advances independently from lifetime task totals' => ($promoted_activator_after_first_action['key'] ?? '') === 'vip26_activator_d02' && ($legacy_activator_continuation['key'] ?? '') === 'vip26_activator_d13',
    'promotion to Builder starts Builder at step one even after 29 Activator steps' => ($promoted_builder_first_action['key'] ?? '') === 'vip26_builder_d01' && ($promoted_builder_first_action['sequence_position'] ?? 0) === 1,
    'thirty completed steps in an earlier level cannot falsely complete the promoted level' => ($completed_starter_then_promoted['key'] ?? '') === 'vip26_activator_d01_biolink' && empty($completed_starter_then_promoted['is_program_complete']),
    'only thirty steps in the current level mark that level complete' => !empty($completed_current_level['is_level_complete']) && ($completed_current_level['track_key'] ?? '') === 'activator',
    'a same-day promotion keeps the one-task-per-day countdown before new level step one' => !empty($promotion_same_day_waits['is_daily_complete']) && ($promotion_same_day_waits['next_unlock_at_iso'] ?? '') === '2026-09-08T00:00:00+02:00',
    'Sunday remains the calendar priority and Monday opens step one of the promoted level' => !empty($promotion_sunday_override['is_weekly_plan']) && ($promotion_monday_first_action['key'] ?? '') === 'vip26_activator_d01_biolink',
    'long task titles never hide part of the original member instruction' => $catalog_is_lossless,
    'all 150 result categories come from explicit reviewed metadata' => $catalog_result_types_are_explicit,
    'all 150 quick targets are explicit positive and no larger than the full target' => $catalog_quick_targets_are_explicit,
    'every reduced fallback is bounded by the currently displayed quick target' => $catalog_reduced_fallbacks_are_bounded,
    'fallbacks do not repeat the mentor training instruction' => $catalog_fallback_training_copy_is_not_duplicated,
    'Sunday Marketing plan is visible for preparation but cannot be completed before its 19:30 end' => !empty($sunday_action['is_weekly_plan']) && str_starts_with($sunday_action['key'], 'vip26_sunday_') && !$sunday_action['can_complete'] && !$sunday_live_action['can_complete'] && !$sunday_before_completion_action['can_complete'] && $sunday_after_completion_action['can_complete'] && !empty($sunday_action['is_waiting_for_event_completion']),
    'a completed Sunday task keeps the regular step locked until tomorrow' => !empty($after_sunday_action['is_daily_complete']) && !$after_sunday_action['can_complete'] && $after_sunday_action['sequence_position'] === 1,
    'a completed weekday task also keeps the next VIP task locked until tomorrow' => !empty($after_weekday_action['is_daily_complete']) && !$after_weekday_action['can_complete'] && $after_weekday_action['sequence_position'] === 1,
    'completed task countdown targets the next Zagreb midnight with exact server-relative seconds' => ($after_weekday_late_action['next_unlock_at_iso'] ?? '') === '2026-09-08T00:00:00+02:00' && ($after_weekday_late_action['server_now_iso'] ?? '') === '2026-09-07T23:59:30+02:00' && ($after_weekday_late_action['seconds_until_next_unlock'] ?? -1) === 30 && ($after_weekday_late_action['next_unlock_time_label'] ?? '') === '00:00',
    'the same instant produces the same Zagreb unlock regardless of caller timezone' => ($after_weekday_late_utc_action['next_unlock_at_iso'] ?? '') === ($after_weekday_late_action['next_unlock_at_iso'] ?? '') && ($after_weekday_late_utc_action['server_now_iso'] ?? '') === ($after_weekday_late_action['server_now_iso'] ?? '') && ($after_weekday_late_utc_action['seconds_until_next_unlock'] ?? -1) === 30,
    'all five curriculum tracks share the same midnight boundary' => count($timer_track_unlocks) === 5 && count(array_unique($timer_track_unlocks)) === 1 && $timer_track_unlocks[0] === '2026-09-08T00:00:00+02:00',
    'the next regular task is available exactly at the following Zagreb midnight without stale timer metadata' => empty($at_next_midnight_action['is_daily_complete']) && !empty($at_next_midnight_action['can_complete']) && ($at_next_midnight_action['key'] ?? '') === 'vip26_starter_d02' && empty($at_next_midnight_action['next_unlock_at_iso']),
    'Saturday completion unlocks the Sunday priority task exactly at midnight' => ($after_saturday_action['next_unlock_at_iso'] ?? '') === '2026-09-06T00:00:00+02:00' && !empty($at_sunday_midnight_action['is_weekly_plan']) && ($at_sunday_midnight_action['key'] ?? '') === 'vip26_sunday_20260906' && empty($at_sunday_midnight_action['next_unlock_at_iso']),
    'completed Sunday plan unlocks the waiting regular step on Monday at midnight' => ($after_sunday_action['next_unlock_at_iso'] ?? '') === '2026-09-07T00:00:00+02:00' && empty($at_monday_midnight_action['is_weekly_plan']) && !empty($at_monday_midnight_action['can_complete']) && ($at_monday_midnight_action['key'] ?? '') === 'vip26_starter_d01',
    'midnight calculation follows both Zagreb DST transitions rather than adding a fixed UTC day' => ($spring_daily_complete_action['next_unlock_at_iso'] ?? '') === '2026-03-30T00:00:00+02:00' && ($spring_daily_complete_action['seconds_until_next_unlock'] ?? -1) === 81000 && ($autumn_daily_complete_action['next_unlock_at_iso'] ?? '') === '2026-10-26T00:00:00+01:00' && ($autumn_daily_complete_action['seconds_until_next_unlock'] ?? -1) === 88200,
    'program stops cleanly after 30 sequence steps without showing another unlock countdown' => !empty($completed_program['is_program_complete']) && !$completed_program['can_complete'] && $completed_program['sequence_position'] === 30 && empty($completed_program['next_unlock_at_iso']),
    'daily September cohort completes 30 regular plus five Sunday tasks on 5 October' => $simulated_regular_steps === 30 && $simulated_total_tasks === 35 && $simulated_completion_date === '2026-10-05',
    'VIP group and first Marketing plan are available in the qualified program state' => str_contains($vip_active['whatsapp_group_url'], 'G0Mxgm8yXfrIDAOxNqPbmw') && ($vip_active['marketing_plan']['weekday'] ?? 0) === 7 && ($vip_active['marketing_plan']['time_label'] ?? '') === '18:00' && ($vip_active['marketing_plan']['next_at_display'] ?? '') === 'nedjelja, 06.09.2026. u 18:00' && ($vip_active['marketing_plan']['url'] ?? '') === 'https://forevercard.club/vip-edukacija',
    'August Builder level cannot drop when a later month starts at zero' => $fixed_builder_track['key'] === 'builder',
    'later qualifying Activator level cannot drop when the next month starts at zero' => $rolling_activator_after_drop['key'] === 'activator',
    'new synchronized results can raise Starter to Activator' => $upgraded_activator_track['key'] === 'activator',
    'a confirmed persisted returner receives the Reaktivacija path' => $confirmed_reactivation_track['key'] === 'reactivation',
    'previous-month Personal CC alone can no longer misclassify a new member as Reaktivacija' => $previous_cc_only_starter_track['key'] === 'starter',
    'generic twelve-month activity alone can no longer assign Reaktivacija' => $history_only_starter_track['key'] === 'starter',
    'same-month enrollment always starts in Starter' => ($vip_start_recent_enrollment_q['key'] ?? '') === 'starter' && ($vip_start_recent_enrollment_q['reason'] ?? '') === 'recent_enrollment',
    'previous-month enrollment remains Starter even after first activity' => ($vip_start_recent_enrollment_q_minus_1['key'] ?? '') === 'starter' && ($vip_start_recent_enrollment_q_minus_1['reason'] ?? '') === 'recent_enrollment',
    'the inclusive Q minus three-month boundary protects recent enrollees from Reaktivacija' => ($vip_start_recent_enrollment_q_minus_3_boundary['key'] ?? '') === 'starter' && ($vip_start_recent_enrollment_q_minus_3_boundary['reason'] ?? '') === 'recent_enrollment',
    'an established returner one day before the recent-enrollment boundary receives Reaktivacija' => ($vip_start_return_before_recent_boundary['key'] ?? '') === 'reactivation' && ($vip_start_return_before_recent_boundary['reason'] ?? '') === 'return_after_pause',
    'historical activity cannot assign Reaktivacija when the enrollment date is unavailable' => ($vip_start_return_without_known_enrollment_date['key'] ?? '') === 'starter' && ($vip_start_return_without_known_enrollment_date['reason'] ?? '') === 'insufficient_history',
    'a valid pre-2000 enrollment date can identify a true established returner' => ($vip_start_legacy_1999_returner['key'] ?? '') === 'reactivation' && ($vip_start_legacy_1999_returner['reason'] ?? '') === 'return_after_pause',
    'confirmed activity in the previous month blocks Reaktivacija when there was no pause' => ($vip_start_active_previous_month['key'] ?? '') === 'starter' && ($vip_start_active_previous_month['reason'] ?? '') === 'active_without_pause',
    'missing enrollment and activity history fail safely to Starter' => ($vip_start_missing_history['key'] ?? '') === 'starter' && ($vip_start_missing_history['reason'] ?? '') === 'insufficient_history',
    'missing qualifying period cannot manufacture a Reaktivacija decision from otherwise return-like evidence' => ($vip_start_missing_qualifying_period['key'] ?? '') === 'starter' && ($vip_start_missing_qualifying_period['reason'] ?? '') === 'insufficient_history',
    'an invalid calendar enrollment date fails safely as a data conflict' => ($vip_start_invalid_enrollment_date['key'] ?? '') === 'starter' && ($vip_start_invalid_enrollment_date['reason'] ?? '') === 'data_conflict',
    'conflicting previous-month activity signals fail safely to Starter' => ($vip_start_conflicting_previous_month_signals['key'] ?? '') === 'starter' && ($vip_start_conflicting_previous_month_signals['reason'] ?? '') === 'data_conflict',
    'a persisted Starter decision is not rewritten by later return-like evidence' => ($vip_start_persisted_starter['key'] ?? '') === 'starter' && ($vip_start_persisted_starter['reason'] ?? '') === 'recent_enrollment',
    'a persisted Reaktivacija decision is not rewritten by later recent-enrollment evidence' => ($vip_start_persisted_reactivation['key'] ?? '') === 'reactivation' && ($vip_start_persisted_reactivation['reason'] ?? '') === 'return_after_pause',
    'an unknown persisted reason is ignored and the starting track is recomputed from evidence' => ($vip_start_unknown_persisted_reason['key'] ?? '') === 'reactivation' && ($vip_start_unknown_persisted_reason['reason'] ?? '') === 'return_after_pause',
    'a logically mismatched persisted key and reason are ignored in favor of fresh evidence' => ($vip_start_mismatched_persisted_pair['key'] ?? '') === 'starter' && ($vip_start_mismatched_persisted_pair['reason'] ?? '') === 'recent_enrollment',
    'Leader requires both full recognized Manager status and official August 4 CC' => $recognized_manager_active_track['key'] === 'leader' && $recognized_manager_inactive_track['key'] !== 'leader',
    'Assistant and Unrecognized Manager do not receive Leader solely from their title' => $assistant_manager_active_track['key'] === 'builder' && $unrecognized_manager_active_track['key'] === 'builder',
    'historical Leader completion cannot bypass the stricter current qualification' => $historical_leader_without_qualification['key'] === 'builder',
    'root administrator profile can be placed on the Leader curriculum only by a server flag' => $forced_admin_leader['key'] === 'leader',
    'Starter and Reaktivacija are parallel starting paths toward Activator using only current-month Personal CC' => $starter_path['current_key'] === 'starter' && $starter_path['mode'] === 'personal' && $starter_path['next_key'] === 'activator' && $starter_path['personal_cc'] === .4 && $starter_path['personal_gap_cc'] === .6 && $reactivation_path['current_key'] === 'reactivation' && $reactivation_path['mode'] === 'personal' && $reactivation_path['next_key'] === 'activator',
    'Activator shows separate Personal Total Active and official-signal facts without a misleading combined percentage' => $activator_path['current_key'] === 'activator' && $activator_path['mode'] === 'four_cc' && $activator_path['next_key'] === 'builder' && $activator_path['personal_progress'] === 60.0 && $activator_path['total_active_progress'] === 50.0 && $activator_path['official_activity_signal'] === 0 && !array_key_exists('progress', $activator_path),
    'an explicit negative official status blocks Builder even when both numeric guides are complete' => $activator_official_blocked_path['current_key'] === 'activator' && $activator_official_blocked_path['personal_progress'] === 100.0 && $activator_official_blocked_path['total_active_progress'] === 100.0 && str_contains($activator_official_blocked_path['transition_note'], 'Builder se otvara čim FLP360 potvrdi 4 CC Active status'),
    'official positive and exact NULL-only fallback each open Builder with the same verified rule as the task engine' => $official_positive_builder_path['current_key'] === 'builder' && $fallback_builder_path['current_key'] === 'builder',
    'Builder and Leader cards present an accurate focus while Leader remains special' => $builder_zero_path['mode'] === 'builder_focus' && $builder_zero_path['personal_cc'] === 0.0 && $leader_path['mode'] === 'leader_focus' && $leader_path['current_key'] === 'leader' && $assistant_manager_path['current_key'] === 'builder' && $historical_unqualified_leader_path['current_key'] === 'builder',
    'the expandable guide keeps both parallel starting paths distinct and shows the exact fixed Leader qualification' => count($starter_path['guide']) === 5 && ($starter_path['guide'][0]['key'] ?? '') === 'starter' && ($starter_path['guide'][1]['key'] ?? '') === 'reactivation' && str_contains((string) ($starter_path['guide'][4]['requirement'] ?? ''), 'kolovoz 2026') && str_contains((string) ($starter_path['guide'][4]['requirement'] ?? ''), 'Ne otključava se automatski nakon Buildera'),
    'verified threshold promotions remain after a new-month zero even before a new-track task is completed' => $persisted_dynamic_activator['key'] === 'activator' && $persisted_dynamic_builder['key'] === 'builder',
    'historical verified metric rank is capped at Builder and can never manufacture Leader' => $verified_metrics_cannot_create_leader['key'] === 'builder',
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
    'adaptive copy is not shown and a reviewed fallback is preserved when the quick target is already one' => empty($already_minimum_quick_action['is_adaptively_simplified']) && ($already_minimum_quick_action['quick_target'] ?? 0) === 1 && str_contains((string) ($already_minimum_quick_action['fallback'] ?? ''), 'jedan check-in'),
];

$failed_rules = array_keys(array_filter($rule_assertions, static fn($passed) => !$passed));
if($failed_rules) {
    fwrite(STDERR, "Forever business rule check failed:\n- " . implode("\n- ", $failed_rules) . "\n");
    exit(1);
}

echo "Forever business safety checks passed.\n";

/* /Custom code: FC-2026-08-13 */
