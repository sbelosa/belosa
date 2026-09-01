<?php
/* Local DB integration check for participant-scoped VIP progress.
 * Uses two unmistakable temporary accounts and removes every fixture in a
 * finally block. Never point this script at production. */

if(PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

defined('DEBUG') || define('DEBUG', 0);
defined('MYSQL_DEBUG') || define('MYSQL_DEBUG', 0);
defined('LOGGING') || define('LOGGING', 1);
defined('CACHE') || define('CACHE', 0);
defined('ALTUMCODE') || define('ALTUMCODE', 66);

$workspace_root = dirname(__DIR__);
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['HTTPS'] = '';

require_once $workspace_root . '/app/init.php';

$assert = static function(bool $condition, string $message): void {
    if(!$condition) throw new RuntimeException($message);
};

$fbo_id = '360999999991';
$tampered_fbo_id = '360999999992';
$fixture_emails = ['vip-launch-test-a@invalid.local', 'vip-launch-test-b@invalid.local'];
$fixture_user_ids = [];

try {
    forever_business_ensure_tables();

    $collision = database()->query("SELECT COUNT(*) AS total FROM users
        WHERE email IN ('{$fixture_emails[0]}', '{$fixture_emails[1]}')");
    $collision_row = $collision ? $collision->fetch_assoc() : null;
    $member_collision = database()->query("SELECT COUNT(*) AS total FROM forever_business_members WHERE fbo_id = '{$fbo_id}'");
    $member_collision_row = $member_collision ? $member_collision->fetch_assoc() : null;
    $assert((int) ($collision_row['total'] ?? 1) === 0 && (int) ($member_collision_row['total'] ?? 1) === 0, 'Temporary VIP fixture identifiers already exist; aborting without changes.');

    $columns_result = database()->query("SHOW COLUMNS FROM forever_business_daily_outcomes");
    $columns = [];
    while($columns_result && $row = $columns_result->fetch_assoc()) $columns[(string) $row['Field']] = true;
    $assert(isset($columns['recorded_by_user_id'], $columns['completion_mode']), 'Participant and completion-mode columns are required.');

    $indexes_result = database()->query("SHOW INDEX FROM forever_business_daily_outcomes");
    $indexes = [];
    while($indexes_result && $row = $indexes_result->fetch_assoc()) $indexes[(string) $row['Key_name']] = true;
    $assert(isset($indexes['forever_business_outcome_user_daily_uq'], $indexes['forever_business_outcome_user_progress_idx'], $indexes['forever_business_outcome_fbo_idx']), 'Participant-scoped outcome indexes are required.');
    $assert(!isset($indexes['forever_business_outcome_daily_uq']), 'Legacy FBO-scoped unique index must be removed.');

    $help_columns_result = database()->query("SHOW COLUMNS FROM forever_business_vip_help_requests");
    $help_columns = [];
    while($help_columns_result && $row = $help_columns_result->fetch_assoc()) $help_columns[(string) $row['Field']] = true;
    $assert(isset($help_columns['user_id'], $help_columns['action_key'], $help_columns['status'], $help_columns['resolved_at']), 'Help-request lifecycle columns are required.');
    $help_indexes_result = database()->query("SHOW INDEX FROM forever_business_vip_help_requests");
    $help_indexes = [];
    while($help_indexes_result && $row = $help_indexes_result->fetch_assoc()) $help_indexes[(string) $row['Key_name']] = true;
    $assert(isset($help_indexes['forever_business_vip_help_user_action_uq'], $help_indexes['forever_business_vip_help_status_idx']), 'Help-request uniqueness and status indexes are required.');

    $fixture_preferences = [
        ['meta' => ['foreverId' => $fbo_id]],
        ['meta' => ['forever_id' => $fbo_id]],
    ];
    $now = database()->real_escape_string(get_date());
    foreach($fixture_emails as $index => $email) {
        $name = 'VIP Launch Test ' . ($index + 1);
        $preferences = database()->real_escape_string(json_encode($fixture_preferences[$index], JSON_UNESCAPED_SLASHES));
        $inserted = database()->query("INSERT INTO users
            (email, name, type, status, plan_id, preferences, datetime)
            VALUES ('{$email}', '{$name}', 0, 1, '', '{$preferences}', '{$now}')");
        $assert((bool) $inserted, 'Temporary participant account could not be created.');
        $fixture_user_ids[] = (int) database()->insert_id;
    }

    /* Provision from the legacy snake_case alias first so an existing member
     * row cannot hide an alias regression in the INSERT SELECT. */
    forever_business_provision_fcc_members($fixture_user_ids[1]);
    $legacy_alias_member_result = database()->query("SELECT COUNT(*) AS total
        FROM forever_business_members WHERE fbo_id = '{$fbo_id}'");
    $legacy_alias_member = $legacy_alias_member_result ? $legacy_alias_member_result->fetch_assoc() : [];
    $assert((int) ($legacy_alias_member['total'] ?? 0) === 1, 'Legacy forever_id alias must provision the FCC member.');
    forever_business_provision_fcc_members($fixture_user_ids[0]);
    forever_business_upsert_registered_member_live_cc($fbo_id, '2026-08-01', [
        'personal_cc' => 1.25,
        'total_cc' => 42.0,
        'total_active_cc' => 9.0,
        'non_manager_cc' => 5.0,
        'leadership_cc' => 2.0,
        'total_active_cc_ytd' => 77.0,
        'non_manager_cc_ytd' => 55.0,
        'leadership_cc_ytd' => 22.0,
        'is_4cc_active' => 0,
    ]);
    forever_business_upsert_total_cc_snapshot($fbo_id, '2026-08-01', 99.0, true, 'GLOBAL', 'VIP month-boundary fixture');

    $zagreb_launch_boundary = new DateTimeImmutable('2026-09-01 00:01:00', new DateTimeZone('Europe/Zagreb'));
    forever_business_record_page_visit($fixture_user_ids[0], $zagreb_launch_boundary);
    $visit_result = database()->query("SELECT visit_date FROM forever_business_page_visits
        WHERE user_id = {$fixture_user_ids[0]} LIMIT 1");
    $visit = $visit_result ? $visit_result->fetch_assoc() : [];
    $assert(($visit['visit_date'] ?? '') === '2026-09-01', 'Launch visit must use the Zagreb calendar date rather than UTC.');

    $input = [
        'core_key' => 'Productivity',
        'action_key' => 'vip26_activator_d01_biolink',
        'outcome_type' => 'activator',
        'result_type' => 'content',
        'difficulty' => 'normal',
        'completion_mode' => 'standard',
        'needs_help' => false,
        'outcome_count' => 1,
        'sequence_position' => 1,
        'note' => '',
    ];

    $fixed_now = new DateTimeImmutable('2026-09-01 00:00:01', new DateTimeZone('Europe/Zagreb'));
    $legacy_timestamp = database()->real_escape_string(get_date());
    $legacy_outcome = database()->query("INSERT INTO forever_business_daily_outcomes
        (fbo_id, action_date, core_key, action_key, status, outcome_count, outcome_type, result_type,
         difficulty, needs_help, recorded_by_user_id, completion_mode, created_at, updated_at)
        VALUES ('{$fbo_id}', '2026-09-01', 'Development', 'vip26_activator_d01', 'done', 1, 'activator',
                'planning', 'normal', 0, {$fixture_user_ids[0]}, 'standard', '{$legacy_timestamp}', '{$legacy_timestamp}')");
    $assert((bool) $legacy_outcome, 'Superseded Activator day-one fixture could not be created.');
    $september_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $fixed_now);
    $september_member = $september_dashboard['members'][0] ?? [];
    $assert((int) ($september_member['vip_actions_done_total'] ?? -1) === 0
        && (int) ($september_member['actions_done_7d'] ?? -1) === 0
        && empty($september_member['vip_action_done_today'])
        && ($september_member['next_action']['key'] ?? '') === 'vip26_activator_d01_biolink',
        'The superseded CC-review outcome must not skip the new biolink task or enter current curriculum statistics.');
    $assert(($september_dashboard['period'] ?? '') === '2026-09-01', 'The first open September request must select September even without a September metric row.');
    $assert((float) ($september_member['personal_cc'] ?? -1) === 0.0
        && (float) ($september_member['total_cc'] ?? -1) === 0.0
        && (float) ($september_member['total_active_cc'] ?? -1) === 0.0,
        'Missing September monthly Personal, Total and Total Active CC must start at zero.');
    $assert((float) ($september_member['previous_total_cc'] ?? -1) === 42.0
        && ($september_member['vip_current_period_month'] ?? null) === null,
        'August must remain the previous month and must not be copied into VIP current-month fields.');
    $september_trend = (array) ($september_dashboard['trend'] ?? []);
    $september_trend_rows = array_values(array_filter($september_trend, static fn(array $row): bool => ($row['period_month'] ?? '') === '2026-09-01'));
    $assert(count($september_trend_rows) === 1 && (float) ($september_trend_rows[0]['total_cc'] ?? -1) === 0.0,
        'The collaborator CC graph must contain one September zero point before the first order.');
    $vip_state = forever_business_get_vip_program_state($fixture_user_ids[0], $fixed_now);
    $assert(($vip_state['current_period'] ?? '') === '2026-09-01'
        && (float) ($vip_state['current_personal_cc'] ?? -1) === 0.0
        && ($vip_state['qualifying_period'] ?? '') === '2026-08-01'
        && !empty($vip_state['can_access_education']),
        'Permanent August enrollment must stay active while visible September Personal CC resets to zero.');
    $yearly_result = database()->query("SELECT total_active_cc_ytd FROM forever_business_yearly_metrics
        WHERE fbo_id = '{$fbo_id}' AND period_year = 2026 LIMIT 1");
    $yearly = $yearly_result ? $yearly_result->fetch_assoc() : [];
    $assert((float) ($yearly['total_active_cc_ytd'] ?? -1) === 77.0, 'Rendering an empty September month must not rewrite the cumulative YTD metric.');
    forever_business_upsert_registered_member_live_cc($fbo_id, '2026-09-01', [
        'personal_cc' => 0.0,
        'total_cc' => 0.0,
        'total_active_cc' => 0.0,
        'non_manager_cc' => 0.0,
        'leadership_cc' => 0.0,
        'total_active_cc_ytd' => null,
        'non_manager_cc_ytd' => null,
        'leadership_cc_ytd' => null,
        'is_4cc_active' => 0,
    ]);
    $fallback_yearly_result = database()->query("SELECT total_active_cc_ytd, non_manager_cc_ytd, leadership_cc_ytd
        FROM forever_business_yearly_metrics WHERE fbo_id = '{$fbo_id}' AND period_year = 2026 LIMIT 1");
    $fallback_yearly = $fallback_yearly_result ? $fallback_yearly_result->fetch_assoc() : [];
    $fallback_vip_state = forever_business_get_vip_program_state($fixture_user_ids[0], $fixed_now);
    $assert((float) ($fallback_yearly['total_active_cc_ytd'] ?? -1) === 77.0
        && (float) ($fallback_yearly['non_manager_cc_ytd'] ?? -1) === 55.0
        && (float) ($fallback_yearly['leadership_cc_ytd'] ?? -1) === 22.0,
        'A confirmed opening-month zero must leave every existing YTD field unchanged when FLP360 omits cumulative values.');
    $assert(($fallback_vip_state['qualifying_period'] ?? '') === '2026-08-01'
        && (float) ($fallback_vip_state['current_personal_cc'] ?? -1) === 0.0
        && !empty($fallback_vip_state['can_access_education']),
        'A zero-month fallback must preserve permanent VIP education access earned in the verified prior month.');
    $los = forever_business_get_los_admin_analytics($fixture_user_ids[0], 30, '', $fixed_now);
    $assert(($los['period'] ?? '') === '2026-09-01'
        && ($los['global']['period'] ?? '') === '2026-09-01'
        && (float) ($los['global']['total_cc'] ?? -1) === 0.0
        && (float) ($los['global']['previous_total_cc'] ?? -1) === 99.0
        && empty($los['global']['is_official_snapshot']),
        'LOS must append a non-official September zero without relabeling the official August Global Total CC.');
    $los_fixture_members = array_values(array_filter((array) ($los['members'] ?? []), static fn(array $member): bool => ($member['fbo_id'] ?? '') === $fbo_id));
    $assert(count($los_fixture_members) === 2
        && count(array_filter($los_fixture_members, static fn(array $member): bool => (float) ($member['personal_cc'] ?? -1) === 0.0 && (float) ($member['total_active_cc'] ?? -1) === 0.0)) === 2,
        'LOS participant statistics must show current-month zeros for every linked collaborator sharing the fixture FBO.');
    $legacy_los_participant = array_values(array_filter($los_fixture_members, static fn(array $member): bool => (int) ($member['user_id'] ?? 0) === $fixture_user_ids[0]));
    $assert(count($legacy_los_participant) === 1
        && (int) ($legacy_los_participant[0]['vip_steps_completed'] ?? -1) === 0
        && (int) ($legacy_los_participant[0]['tasks'] ?? -1) === 0,
        'The superseded Activator day-one outcome must remain outside LOS progress and window statistics.');
    forever_business_upsert_registered_member_live_cc($fbo_id, '2026-09-01', [
        'personal_cc' => 1.25,
        'total_cc' => 1.25,
        'total_active_cc' => 1.25,
        'non_manager_cc' => 0.0,
        'leadership_cc' => 0.0,
        'total_active_cc_ytd' => 78.25,
        'non_manager_cc_ytd' => 55.0,
        'leadership_cc_ytd' => 22.0,
        'is_4cc_active' => 0,
    ]);
    $admin_current_dashboard = forever_business_get_dashboard($fixture_user_ids[0], true, $fbo_id, '', $fixed_now);
    $admin_current_rows = array_values(array_filter((array) ($admin_current_dashboard['trend'] ?? []), static fn(array $row): bool => ($row['period_month'] ?? '') === '2026-09-01'));
    $assert(count($admin_current_rows) === 1 && (float) ($admin_current_rows[0]['total_cc'] ?? -1) === 1.25,
        'Admin chart must use current FCC Personal CC after orders when the official current Global Total CC snapshot is not available yet.');
    $los_outside_structure_after_order = forever_business_get_los_admin_analytics($fixture_user_ids[0], 30, '', $fixed_now);
    $assert((float) ($los_outside_structure_after_order['global']['total_cc'] ?? -1) === 0.0,
        'LOS current-month fallback must exclude FCC-linked accounts outside the confirmed team structure.');
    $structure_update = database()->query("UPDATE forever_business_members SET is_in_current_structure = 1 WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $structure_update, 'Temporary member could not be placed in the confirmed structure for LOS fallback testing.');
    $los_after_order = forever_business_get_los_admin_analytics($fixture_user_ids[0], 30, '', $fixed_now);
    $assert((float) ($los_after_order['global']['total_cc'] ?? -1) === 1.25 && empty($los_after_order['global']['is_official_snapshot']),
        'LOS chart must replace the synthetic zero with current FCC Personal CC after an order, without claiming it is official Global Total CC.');
    $registered_sync_accounts = forever_business_get_registered_sync_accounts('2026-09-01');
    $registered_sync_fixture = array_values(array_filter($registered_sync_accounts, static fn(array $account): bool => ($account['fbo_id'] ?? '') === $fbo_id));
    $assert(count($registered_sync_fixture) === 1
        && (float) ($registered_sync_fixture[0]['personal_cc'] ?? -1) === 1.25
        && (float) ($registered_sync_fixture[0]['total_cc'] ?? -1) === 1.25
        && (float) ($registered_sync_fixture[0]['total_active_cc_ytd'] ?? -1) === 78.25
        && ($registered_sync_fixture[0]['is_4cc_active'] ?? null) === false,
        'Secret-protected registered account verification must expose monthly, official 4 CC and YTD values needed for exact post-write reconciliation.');
    database()->query("DELETE FROM forever_business_metrics WHERE fbo_id = '{$fbo_id}' AND period_month = '2026-09-01'");
    /* Restore the pre-existing fixture state so the later stale-qualification
     * queue test still exercises an account without permanent enrollment. */
    database()->query("DELETE FROM forever_business_vip_enrollments WHERE fbo_id = '{$fbo_id}'");

    $assert(forever_business_record_daily_outcome($fixture_user_ids[0], $fbo_id, [$fbo_id], $input, $fixed_now), 'First shared-FBO participant should save its own step.');
    $assert(!forever_business_record_daily_outcome($fixture_user_ids[0], $fbo_id, [$fbo_id], $input, $fixed_now), 'Same participant must not save the same/day step twice.');
    $assert(forever_business_record_daily_outcome($fixture_user_ids[1], $fbo_id, [$fbo_id], $input, $fixed_now), 'Second shared-FBO participant should save independently.');
    $assert(!forever_business_record_daily_outcome($fixture_user_ids[1], $tampered_fbo_id, [$tampered_fbo_id], $input, $fixed_now), 'Authenticated account must reject a tampered FBO ID.');

    $outcomes_result = database()->query("SELECT COUNT(*) AS total, COUNT(DISTINCT recorded_by_user_id) AS participants,
            MIN(completion_mode) AS completion_mode
        FROM forever_business_daily_outcomes
        WHERE fbo_id = '{$fbo_id}' AND action_key = 'vip26_activator_d01_biolink' AND action_date = '2026-09-01'");
    $outcomes = $outcomes_result ? $outcomes_result->fetch_assoc() : [];
    $assert((int) ($outcomes['total'] ?? 0) === 2 && (int) ($outcomes['participants'] ?? 0) === 2, 'Shared FBO must contain two separate participant outcomes.');
    $assert((string) ($outcomes['completion_mode'] ?? '') === 'standard', 'Completion mode must be stored.');

    $timer_same_day = new DateTimeImmutable('2026-09-01 21:15:30', new DateTimeZone('Europe/Zagreb'));
    $timer_next_day = new DateTimeImmutable('2026-09-02 00:00:00', new DateTimeZone('Europe/Zagreb'));
    $participant_unlocks = [];
    foreach($fixture_user_ids as $fixture_user_id) {
        $same_day_dashboard = forever_business_get_dashboard($fixture_user_id, false, '', '', $timer_same_day);
        $same_day_member = $same_day_dashboard['members'][0] ?? [];
        $same_day_action = $same_day_member['next_action'] ?? [];
        $assert(!empty($same_day_member['vip_action_done_today'])
            && !empty($same_day_action['is_daily_complete'])
            && empty($same_day_action['can_complete'])
            && ($same_day_action['next_unlock_at_iso'] ?? '') === '2026-09-02T00:00:00+02:00'
            && ($same_day_action['seconds_until_next_unlock'] ?? -1) === 9870,
            'Every linked FCC participant must receive the same server-derived countdown after completing the daily task.');
        $participant_unlocks[] = (string) ($same_day_action['next_unlock_at_iso'] ?? '');

        $next_day_dashboard = forever_business_get_dashboard($fixture_user_id, false, '', '', $timer_next_day);
        $next_day_member = $next_day_dashboard['members'][0] ?? [];
        $next_day_action = $next_day_member['next_action'] ?? [];
        $assert(empty($next_day_member['vip_action_done_today'])
            && (int) ($next_day_member['vip_actions_done_total'] ?? 0) === 1
            && empty($next_day_action['is_daily_complete'])
            && !empty($next_day_action['can_complete'])
            && ($next_day_action['key'] ?? '') === 'vip26_activator_d02'
            && empty($next_day_action['next_unlock_at_iso']),
            'At Zagreb midnight every participant must leave the completed state and receive the next regular task.');
    }
    $assert(count(array_unique($participant_unlocks)) === 1, 'Shared-FBO participant accounts must use one identical Zagreb unlock boundary.');

    /* A corrected account-to-FBO linkage must not detach that FCC account's
     * task history or make day 1 appear available again. */
    $moved_preferences = database()->real_escape_string(json_encode(['meta' => ['foreverID' => $tampered_fbo_id]], JSON_UNESCAPED_SLASHES));
    $moved = database()->query("UPDATE users SET preferences = '{$moved_preferences}'
        WHERE user_id = {$fixture_user_ids[0]}");
    $assert((bool) $moved, 'Temporary account FBO correction could not be prepared.');
    forever_business_provision_fcc_members($fixture_user_ids[0]);
    $moved_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $fixed_now);
    $moved_member = $moved_dashboard['members'][0] ?? [];
    $assert((string) ($moved_member['fbo_id'] ?? '') === $tampered_fbo_id
        && (int) ($moved_member['vip_actions_done_total'] ?? 0) === 1,
        'Participant progress must follow the FCC account after an approved FBO correction.');
    $restored_preferences = database()->real_escape_string(json_encode($fixture_preferences[0], JSON_UNESCAPED_SLASHES));
    $restored = database()->query("UPDATE users SET preferences = '{$restored_preferences}'
        WHERE user_id = {$fixture_user_ids[0]}");
    $assert((bool) $restored, 'Temporary account FBO link could not be restored.');
    $tampered_root_dashboard = forever_business_get_dashboard(
        $fixture_user_ids[0],
        false,
        $tampered_fbo_id,
        '',
        $fixed_now
    );
    $tampered_root_member = $tampered_root_dashboard['members'][0] ?? [];
    $assert((string) ($tampered_root_member['fbo_id'] ?? '') === $fbo_id
        && (int) ($tampered_root_member['vip_actions_done_total'] ?? 0) === 1,
        'An arbitrary non-admin root parameter must not expose shared-FBO progress or replace the participant sequence.');

    $help_day = new DateTimeImmutable('2026-09-02 08:00:00', new DateTimeZone('Europe/Zagreb'));
    $assert(forever_business_request_vip_help($fixture_user_ids[0], $fbo_id, [$fbo_id], [
        'action_key' => 'vip26_activator_d02',
        'track_key' => 'activator',
        'sequence_position' => 2,
        'difficulty' => 'hard',
        'note' => 'Trebam pomoć sa zamjenskom radnjom.',
    ], $help_day), 'Participant should be able to request help without a completion.');

    $help_result = database()->query("SELECT status, note FROM forever_business_vip_help_requests
        WHERE user_id = {$fixture_user_ids[0]} AND action_key = 'vip26_activator_d02'");
    $help = $help_result ? $help_result->fetch_assoc() : [];
    $assert(($help['status'] ?? '') === 'open' && str_contains((string) ($help['note'] ?? ''), 'zamjenskom'), 'Open help request and note must be visible to analytics.');

    $assert(forever_business_request_vip_help($fixture_user_ids[0], $fbo_id, [$fbo_id], [
        'action_key' => 'vip26_sunday_20260906',
        'track_key' => 'starter',
        'sequence_position' => 2,
        'difficulty' => 'hard',
        'note' => 'Stari nedjeljni zahtjev mora se zatvoriti nakon kasnijeg dovršetka.',
    ], $help_day), 'A dated Sunday help request should be stored for lifecycle testing.');

    $second_step_input = [
        'core_key' => 'Productivity',
        'action_key' => 'vip26_activator_d02',
        'outcome_type' => 'activator',
        'result_type' => 'planning',
        'difficulty' => 'hard',
        'completion_mode' => 'quick',
        'needs_help' => false,
        'outcome_count' => 5,
        'sequence_position' => 2,
        'note' => '',
    ];
    $second_day = new DateTimeImmutable('2026-09-03 08:00:00', new DateTimeZone('Europe/Zagreb'));
    $assert(forever_business_record_daily_outcome($fixture_user_ids[0], $fbo_id, [$fbo_id], $second_step_input, $second_day), 'Participant should complete the same step on a later day after requesting help.');
    $resolved_help_result = database()->query("SELECT status, resolved_at FROM forever_business_vip_help_requests
        WHERE user_id = {$fixture_user_ids[0]} AND action_key = 'vip26_activator_d02'");
    $resolved_help = $resolved_help_result ? $resolved_help_result->fetch_assoc() : [];
    $assert(($resolved_help['status'] ?? '') === 'resolved' && !empty($resolved_help['resolved_at']), 'Completion must resolve the open help request for the same participant and action.');
    $assert(!forever_business_request_vip_help($fixture_user_ids[0], $fbo_id, [$fbo_id], [
        'action_key' => 'vip26_activator_d02',
        'track_key' => 'activator',
        'sequence_position' => 2,
        'difficulty' => 'hard',
        'note' => 'Zastarjeli tab ne smije ponovno otvoriti dovršeni korak.',
    ], $second_day), 'A serialized stale help submission must not reopen an already completed VIP step.');
    $remaining_open_help_result = database()->query("SELECT COUNT(*) AS total FROM forever_business_vip_help_requests
        WHERE user_id = {$fixture_user_ids[0]} AND status = 'open'");
    $remaining_open_help = $remaining_open_help_result ? $remaining_open_help_result->fetch_assoc() : [];
    $assert((int) ($remaining_open_help['total'] ?? 1) === 0, 'A later completion must resolve stale dated or pre-transition help requests for that participant.');

    $email_timestamp = database()->real_escape_string(get_date());
    $email_fixture = database()->query("INSERT INTO forever_business_vip_email_deliveries
        (user_id, event_key, fbo_id, status, attempts, created_at, updated_at)
        VALUES ({$fixture_user_ids[1]}, 'approved_pending', '{$fbo_id}', 'pending', 0, '{$email_timestamp}', '{$email_timestamp}')");
    $assert((bool) $email_fixture, 'Temporary email-delivery fixture could not be created.');
    $deactivated = database()->query("UPDATE users SET status = 0 WHERE user_id = {$fixture_user_ids[1]}");
    $assert((bool) $deactivated, 'Temporary account could not be deactivated for queue lifecycle testing.');
    $assert(!forever_business_vip_send_notification_for_user($fixture_user_ids[1], 'approved'), 'An inactive recipient must not be sent a VIP email.');
    $unavailable_delivery_result = database()->query("SELECT status, last_error
        FROM forever_business_vip_email_deliveries
        WHERE user_id = {$fixture_user_ids[1]} AND event_key = 'approved_pending'");
    $unavailable_delivery = $unavailable_delivery_result ? $unavailable_delivery_result->fetch_assoc() : [];
    $assert(($unavailable_delivery['status'] ?? '') === 'superseded'
        && str_starts_with((string) ($unavailable_delivery['last_error'] ?? ''), 'recipient_unavailable:'),
        'An inactive or invalid recipient must leave the retryable queue without starving newer deliveries.');

    $stale_qualified_fixture = database()->query("INSERT INTO forever_business_vip_email_deliveries
        (user_id, event_key, fbo_id, status, attempts, created_at, updated_at)
        VALUES ({$fixture_user_ids[0]}, 'qualified', '{$fbo_id}', 'pending', 0, '{$email_timestamp}', '{$email_timestamp}')");
    $assert((bool) $stale_qualified_fixture, 'Temporary stale-qualified delivery fixture could not be created.');
    $assert(!forever_business_vip_send_notification_for_user($fixture_user_ids[0], 'qualified'), 'An account without current verified qualification must not receive a stale qualified email.');
    $stale_qualified_result = database()->query("SELECT status, last_error
        FROM forever_business_vip_email_deliveries
        WHERE user_id = {$fixture_user_ids[0]} AND event_key = 'qualified'");
    $stale_qualified = $stale_qualified_result ? $stale_qualified_result->fetch_assoc() : [];
    $assert(($stale_qualified['status'] ?? '') === 'superseded'
        && str_starts_with((string) ($stale_qualified['last_error'] ?? ''), 'qualification_unavailable:'),
        'A stale qualification delivery must leave the retryable queue without starving newly qualified accounts.');

    echo "Forever VIP DB integration checks passed.\n";
} catch(Throwable $exception) {
    fwrite(STDERR, 'FAILED: ' . $exception->getMessage() . "\n");
    $exit_code = 1;
} finally {
    if(!empty($fixture_user_ids)) {
        $id_list = implode(',', array_map('intval', $fixture_user_ids));
        database()->query("DELETE FROM forever_business_page_visits WHERE user_id IN ({$id_list})");
        database()->query("DELETE FROM forever_business_vip_email_deliveries WHERE user_id IN ({$id_list})");
        database()->query("DELETE FROM forever_business_vip_help_requests WHERE user_id IN ({$id_list})");
        database()->query("DELETE FROM forever_business_daily_outcomes WHERE recorded_by_user_id IN ({$id_list})");
        database()->query("DELETE FROM forever_business_vip_enrollments WHERE fbo_id IN ('{$fbo_id}', '{$tampered_fbo_id}')");
        database()->query("DELETE FROM forever_business_total_cc_snapshots WHERE fbo_id IN ('{$fbo_id}', '{$tampered_fbo_id}')");
        database()->query("DELETE FROM forever_business_yearly_metrics WHERE fbo_id IN ('{$fbo_id}', '{$tampered_fbo_id}')");
        database()->query("DELETE FROM forever_business_metrics WHERE fbo_id IN ('{$fbo_id}', '{$tampered_fbo_id}')");
        database()->query("DELETE FROM users WHERE user_id IN ({$id_list})");
    }
    database()->query("DELETE FROM forever_business_members WHERE fbo_id IN ('{$fbo_id}', '{$tampered_fbo_id}')
        AND name LIKE 'VIP Launch Test %'");
}

exit($exit_code ?? 0);
