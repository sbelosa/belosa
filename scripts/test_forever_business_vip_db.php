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
    $assert(isset($columns['recorded_by_user_id'], $columns['completion_mode'], $columns['sequence_position']), 'Participant, completion-mode and per-level sequence columns are required.');

    $indexes_result = database()->query("SHOW INDEX FROM forever_business_daily_outcomes");
    $indexes = [];
    while($indexes_result && $row = $indexes_result->fetch_assoc()) $indexes[(string) $row['Key_name']] = true;
    $assert(isset($indexes['forever_business_outcome_user_daily_uq'], $indexes['forever_business_outcome_user_progress_idx'], $indexes['forever_business_outcome_fbo_idx'], $indexes['forever_business_outcome_user_track_idx']), 'Participant-scoped outcome and per-level progress indexes are required.');
    $assert(!isset($indexes['forever_business_outcome_daily_uq']), 'Legacy FBO-scoped unique index must be removed.');

    $help_columns_result = database()->query("SHOW COLUMNS FROM forever_business_vip_help_requests");
    $help_columns = [];
    while($help_columns_result && $row = $help_columns_result->fetch_assoc()) $help_columns[(string) $row['Field']] = true;
    $assert(isset($help_columns['user_id'], $help_columns['action_key'], $help_columns['status'], $help_columns['resolved_at']), 'Help-request lifecycle columns are required.');
    $help_indexes_result = database()->query("SHOW INDEX FROM forever_business_vip_help_requests");
    $help_indexes = [];
    while($help_indexes_result && $row = $help_indexes_result->fetch_assoc()) $help_indexes[(string) $row['Key_name']] = true;
    $assert(isset($help_indexes['forever_business_vip_help_user_action_uq'], $help_indexes['forever_business_vip_help_status_idx']), 'Help-request uniqueness and status indexes are required.');

    $vip_enrollment_columns_result = database()->query("SHOW COLUMNS FROM forever_business_vip_enrollments");
    $vip_enrollment_columns = [];
    while($vip_enrollment_columns_result && $row = $vip_enrollment_columns_result->fetch_assoc()) {
        $vip_enrollment_columns[(string) $row['Field']] = $row;
    }
    $assert(isset(
        $vip_enrollment_columns['starting_track_key'],
        $vip_enrollment_columns['starting_track_reason'],
        $vip_enrollment_columns['starting_track_decided_at']
    ), 'Permanent VIP starting-track decision columns are required.');
    $assert(strtolower((string) ($vip_enrollment_columns['starting_track_key']['Type'] ?? '')) === 'varchar(24)'
        && strtolower((string) ($vip_enrollment_columns['starting_track_reason']['Type'] ?? '')) === 'varchar(32)'
        && strtolower((string) ($vip_enrollment_columns['starting_track_decided_at']['Type'] ?? '')) === 'datetime'
        && strtoupper((string) ($vip_enrollment_columns['starting_track_decided_at']['Null'] ?? 'NO')) === 'YES',
        'VIP starting-track decision columns must keep their bounded nullable schema.');

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
    $recent_sponsor_date = database()->query("UPDATE forever_business_members
        SET sponsor_date = '2026-07-15'
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $recent_sponsor_date, 'Recent-enrollment fixture date could not be prepared.');

    /* Focus stores a missing/blank PREVIOUS MONTH ACTIVE value as zero in the
     * legacy schema. That default must never be treated as a confirmed pause. */
    $focus_unknown_date_ready = database()->query("UPDATE forever_business_members
        SET sponsor_date = '2025-01-15'
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $focus_unknown_date_ready, 'Focus unknown-pause fixture date could not be prepared.');
    $focus_unknown_pause_ready = database()->query("INSERT INTO forever_business_focus_metrics
        (fbo_id, period_month, snapshot_date, enrollment_date, last_purchase_date, is_active,
         was_active_previous_month, personal_cc, new_recruits, source_import_id, updated_at)
        VALUES ('{$fbo_id}', '2026-08-01', '2026-08-31', '2025-01-15', '2026-06-15', 0,
                0, 0.000, 0, NULL, '{$now}')");
    $assert((bool) $focus_unknown_pause_ready, 'Focus unknown-pause fixture could not be created.');
    $focus_guard_now = new DateTimeImmutable('2026-09-01 00:00:01', new DateTimeZone('Europe/Zagreb'));
    $focus_guard_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $focus_guard_now);
    $focus_guard_member = $focus_guard_dashboard['members'][0] ?? [];
    $focus_guard_storage_result = database()->query("SELECT starting_track_key, starting_track_reason
        FROM forever_business_vip_enrollments WHERE fbo_id = '{$fbo_id}' LIMIT 1");
    $focus_guard_storage = $focus_guard_storage_result ? $focus_guard_storage_result->fetch_assoc() : [];
    $assert(($focus_guard_member['vip_starting_track_key'] ?? '') === 'starter'
        && ($focus_guard_member['vip_starting_track_reason'] ?? '') === 'insufficient_history'
        && empty($focus_guard_storage['starting_track_key'])
        && empty($focus_guard_storage['starting_track_reason']),
        'A Focus zero caused by a missing or blank previous-month field must not manufacture a confirmed pause or Reaktivacija. '
        . 'Dashboard=' . json_encode([
            'track' => $focus_guard_member['vip_starting_track_key'] ?? null,
            'reason' => $focus_guard_member['vip_starting_track_reason'] ?? null,
            'previous_activity' => $focus_guard_member['vip_previous_month_has_activity'] ?? null,
            'previous_inactive' => $focus_guard_member['vip_previous_month_confirmed_inactive'] ?? null,
            'prior_activity' => $focus_guard_member['vip_has_prior_activity_before_pause'] ?? null,
        ]) . '; storage=' . json_encode($focus_guard_storage));
    database()->query("DELETE FROM forever_business_focus_metrics
        WHERE fbo_id = '{$fbo_id}' AND period_month = '2026-08-01'");
    $focus_unknown_date_restored = database()->query("UPDATE forever_business_members
        SET sponsor_date = '2026-07-15'
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $focus_unknown_date_restored, 'Recent-enrollment fixture date could not be restored after Focus pause guard.');

    /* Quarantine historical rows that older code may have changed to zero via
     * Focus while retaining an earlier trusted source_import_id. A later real
     * monthly sync can explicitly reconfirm and release that zero. */
    $legacy_focus_zero_date_ready = database()->query("UPDATE forever_business_members
        SET sponsor_date = '2025-01-15'
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $legacy_focus_zero_date_ready, 'Legacy Focus-zero fixture date could not be prepared.');
    $legacy_focus_zero_metrics_ready = database()->query("INSERT INTO forever_business_metrics
        (fbo_id, period_month, personal_cc, total_cc, total_active_cc, non_manager_cc, leadership_cc,
         is_4cc_active, source_import_id, updated_at)
        VALUES
        ('{$fbo_id}', '2026-06-01', 0.500, 0.500, 0.500, 0.000, 0.000, 0, NULL, '2026-08-15 09:00:00'),
        ('{$fbo_id}', '2026-07-01', 0.000, 0.000, 0.000, 0.000, 0.000, 0, NULL, '2026-08-15 10:00:00')");
    $legacy_focus_zero_focus_ready = database()->query("INSERT INTO forever_business_focus_metrics
        (fbo_id, period_month, snapshot_date, enrollment_date, last_purchase_date, is_active,
         was_active_previous_month, personal_cc, new_recruits, source_import_id, updated_at)
        VALUES ('{$fbo_id}', '2026-07-01', '2026-08-16', '2025-01-15', '2026-06-15', 0,
                0, 0.000, 0, NULL, '2026-08-16 10:00:00')");
    $assert((bool) $legacy_focus_zero_metrics_ready && (bool) $legacy_focus_zero_focus_ready,
        'Legacy Focus-zero fixtures could not be created.');
    $legacy_focus_zero_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $focus_guard_now);
    $legacy_focus_zero_member = $legacy_focus_zero_dashboard['members'][0] ?? [];
    $legacy_focus_zero_storage_result = database()->query("SELECT starting_track_key, starting_track_reason
        FROM forever_business_vip_enrollments WHERE fbo_id = '{$fbo_id}' LIMIT 1");
    $legacy_focus_zero_storage = $legacy_focus_zero_storage_result ? $legacy_focus_zero_storage_result->fetch_assoc() : [];
    $assert(($legacy_focus_zero_member['vip_starting_track_key'] ?? '') === 'starter'
        && ($legacy_focus_zero_member['vip_starting_track_reason'] ?? '') === 'insufficient_history'
        && empty($legacy_focus_zero_storage['starting_track_key'])
        && empty($legacy_focus_zero_storage['starting_track_reason']),
        'A potentially Focus-overwritten historical zero must remain quarantined and cannot assign Reaktivacija.');
    $trusted_zero_reconfirmed = database()->query("UPDATE forever_business_metrics
        SET updated_at = '2026-08-17 10:00:00'
        WHERE fbo_id = '{$fbo_id}' AND period_month = '2026-07-01'");
    $assert((bool) $trusted_zero_reconfirmed, 'Trusted zero reconfirmation fixture could not be prepared.');
    $reconfirmed_zero_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $focus_guard_now);
    $reconfirmed_zero_member = $reconfirmed_zero_dashboard['members'][0] ?? [];
    $assert(($reconfirmed_zero_member['vip_starting_track_key'] ?? '') === 'reactivation'
        && ($reconfirmed_zero_member['vip_starting_track_reason'] ?? '') === 'return_after_pause',
        'A strictly later trusted monthly sync may reconfirm the zero and resolve a true returner as Reaktivacija.');
    database()->query("DELETE FROM forever_business_focus_metrics
        WHERE fbo_id = '{$fbo_id}' AND period_month = '2026-07-01'");
    database()->query("DELETE FROM forever_business_metrics
        WHERE fbo_id = '{$fbo_id}' AND period_month IN ('2026-06-01', '2026-07-01')");
    database()->query("UPDATE forever_business_vip_enrollments
        SET starting_track_key = NULL, starting_track_reason = NULL, starting_track_decided_at = NULL
        WHERE fbo_id = '{$fbo_id}'");
    $legacy_focus_zero_restored = database()->query("UPDATE forever_business_members
        SET sponsor_date = '2026-07-15'
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $legacy_focus_zero_restored, 'Recent-enrollment fixture date could not be restored after legacy Focus-zero guard.');

    /* An explicit official 4 CC positive signal always proves activity, even
     * when Personal CC in the same trusted row is anomalously zero. */
    $official_precedence_date_ready = database()->query("UPDATE forever_business_members
        SET sponsor_date = '1999-12-31'
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $official_precedence_date_ready, 'Official-precedence fixture date could not be prepared.');
    $official_precedence_metrics_ready = database()->query("INSERT INTO forever_business_metrics
        (fbo_id, period_month, personal_cc, total_cc, total_active_cc, non_manager_cc, leadership_cc,
         is_4cc_active, source_import_id, updated_at)
        VALUES
        ('{$fbo_id}', '2026-06-01', 0.500, 0.500, 0.500, 0.000, 0.000, 0, NULL, '{$now}'),
        ('{$fbo_id}', '2026-07-01', 0.000, 4.000, 4.000, 0.000, 0.000, 1, NULL, '{$now}')");
    $assert((bool) $official_precedence_metrics_ready, 'Official-precedence metrics could not be prepared.');
    $official_precedence_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $focus_guard_now);
    $official_precedence_member = $official_precedence_dashboard['members'][0] ?? [];
    $assert(($official_precedence_member['vip_starting_track_key'] ?? '') === 'starter'
        && ($official_precedence_member['vip_starting_track_reason'] ?? '') === 'active_without_pause',
        'Official Q-1 4 CC activity must block Reaktivacija even when Personal CC is zero.');
    database()->query("DELETE FROM forever_business_metrics
        WHERE fbo_id = '{$fbo_id}' AND period_month IN ('2026-06-01', '2026-07-01')");
    $official_precedence_restored = database()->query("UPDATE forever_business_members
        SET sponsor_date = '2026-07-15'
        WHERE fbo_id = '{$fbo_id}'");
    database()->query("UPDATE forever_business_vip_enrollments
        SET starting_track_key = NULL, starting_track_reason = NULL, starting_track_decided_at = NULL
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $official_precedence_restored, 'Recent-enrollment fixture date could not be restored after official precedence guard.');

    /* Incomplete same-cohort evidence is shown as fail-safe Starter but must
     * remain unfrozen so a later completed import can make the correct choice. */
    $incomplete_start = forever_business_persist_vip_start([
        'fbo_id' => $fbo_id,
        'vip_qualifying_period' => '2026-08-01',
        'vip_known_enrollment_date' => '2025-01-15',
    ]);
    $incomplete_storage_result = database()->query("SELECT starting_track_key, starting_track_reason, starting_track_decided_at
        FROM forever_business_vip_enrollments
        WHERE fbo_id = '{$fbo_id}'
        LIMIT 1");
    $incomplete_storage = $incomplete_storage_result ? $incomplete_storage_result->fetch_assoc() : [];
    $assert(($incomplete_start['key'] ?? '') === 'starter'
        && ($incomplete_start['reason'] ?? '') === 'insufficient_history'
        && empty($incomplete_storage['starting_track_key'])
        && empty($incomplete_storage['starting_track_reason'])
        && empty($incomplete_storage['starting_track_decided_at']),
        'Incomplete same-cohort evidence must not permanently freeze the fail-safe Starter response.');
    $completed_history_start = forever_business_persist_vip_start([
        'fbo_id' => $fbo_id,
        'vip_qualifying_period' => '2026-08-01',
        'vip_known_enrollment_date' => '2025-01-15',
        'vip_has_prior_activity_before_pause' => true,
        'vip_previous_month_confirmed_inactive' => true,
    ]);
    $assert(($completed_history_start['key'] ?? '') === 'reactivation'
        && ($completed_history_start['reason'] ?? '') === 'return_after_pause'
        && !empty($completed_history_start['is_persisted']),
        'Completed same-cohort evidence must still be able to resolve an unfrozen returner as Reaktivacija.');
    $same_cohort_reset = database()->query("UPDATE forever_business_vip_enrollments
        SET starting_track_key = NULL,
            starting_track_reason = NULL,
            starting_track_decided_at = NULL
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $same_cohort_reset, 'Same-cohort persistence fixture could not be reset.');

    $entry_lock_input = [
        'core_key' => 'Development',
        'action_key' => 'vip26_starter_d01',
        'outcome_type' => 'starter',
        'result_type' => 'planning',
        'difficulty' => 'normal',
        'completion_mode' => 'standard',
        'needs_help' => false,
        'outcome_count' => 1,
        'sequence_position' => 1,
        'note' => '',
    ];
    $entry_lock_time = new DateTimeImmutable('2026-08-28 10:00:00', new DateTimeZone('Europe/Zagreb'));
    $opposite_decision_ready = database()->query("UPDATE forever_business_vip_enrollments
        SET starting_track_key = 'reactivation',
            starting_track_reason = 'return_after_pause',
            starting_track_decided_at = '{$now}'
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $opposite_decision_ready
        && !forever_business_record_daily_outcome($fixture_user_ids[0], $fbo_id, [$fbo_id], $entry_lock_input, $entry_lock_time),
        'A stale Starter submit must be rejected after the shared FBO has committed Reaktivacija.');
    $entry_lock_reset = database()->query("UPDATE forever_business_vip_enrollments
        SET starting_track_key = NULL, starting_track_reason = NULL, starting_track_decided_at = NULL
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $entry_lock_reset
        && forever_business_record_daily_outcome($fixture_user_ids[0], $fbo_id, [$fbo_id], $entry_lock_input, $entry_lock_time),
        'The first valid entry-task completion must atomically persist its shared FBO path.');
    $entry_lock_storage_result = database()->query("SELECT starting_track_key, starting_track_reason
        FROM forever_business_vip_enrollments WHERE fbo_id = '{$fbo_id}' LIMIT 1");
    $entry_lock_storage = $entry_lock_storage_result ? $entry_lock_storage_result->fetch_assoc() : [];
    $assert(($entry_lock_storage['starting_track_key'] ?? '') === 'starter'
        && ($entry_lock_storage['starting_track_reason'] ?? '') === 'started_starter_path',
        'The enrollment row and first entry outcome must commit the same path.');
    database()->query("DELETE FROM forever_business_daily_outcomes
        WHERE fbo_id = '{$fbo_id}' AND action_key = 'vip26_starter_d01' AND action_date = '2026-08-28'");
    database()->query("UPDATE forever_business_vip_enrollments
        SET starting_track_key = NULL, starting_track_reason = NULL, starting_track_decided_at = NULL
        WHERE fbo_id = '{$fbo_id}'");

    /* A completed entry-path task freezes continuity across an earlier cohort
     * import. Before the first task, the same earlier import may safely clear a
     * provisional decision so it can be recomputed from the corrected cohort. */
    $cohort_fixture_prepared = database()->query("UPDATE forever_business_vip_enrollments
        SET qualifying_period = '2026-09-01',
            starting_track_key = 'reactivation',
            starting_track_reason = 'return_after_pause',
            starting_track_decided_at = '{$now}'
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $cohort_fixture_prepared, 'Started-path cohort fixture could not be prepared.');
    $started_fixture = database()->query("INSERT INTO forever_business_daily_outcomes
        (fbo_id, action_date, core_key, action_key, status, outcome_count, outcome_type,
         recorded_by_user_id, completion_mode, created_at, updated_at)
        VALUES ('{$fbo_id}', '2026-08-31', 'Development', 'vip26_starter_d01', 'done', 1, 'starter',
                {$fixture_user_ids[0]}, 'standard', '{$now}', '{$now}')");
    $assert((bool) $started_fixture, 'Started-path outcome fixture could not be created.');
    $started_path = forever_business_persist_vip_start([
        'fbo_id' => $fbo_id,
        'vip_qualifying_period' => '2026-09-01',
    ]);
    $assert(($started_path['key'] ?? '') === 'starter'
        && ($started_path['reason'] ?? '') === 'started_starter_path'
        && !empty($started_path['is_persisted']),
        'The earliest completed Starter task must reconcile and freeze even an opposite legacy decision.');
    $assert(forever_business_record_vip_eligibility_metric($fbo_id, '2026-08-01', 0.500, null, 'member_cc', $fixture_user_ids[0]),
        'Earlier qualifying-period fixture could not update the enrollment after a started path.');
    $started_cohort_result = database()->query("SELECT qualifying_period, starting_track_key, starting_track_reason
        FROM forever_business_vip_enrollments WHERE fbo_id = '{$fbo_id}' LIMIT 1");
    $started_cohort = $started_cohort_result ? $started_cohort_result->fetch_assoc() : [];
    $assert(($started_cohort['qualifying_period'] ?? '') === '2026-08-01'
        && ($started_cohort['starting_track_key'] ?? '') === 'starter'
        && ($started_cohort['starting_track_reason'] ?? '') === 'started_starter_path',
        'An earlier qualifying import must preserve a path after its first completed task.');
    database()->query("DELETE FROM forever_business_daily_outcomes
        WHERE fbo_id = '{$fbo_id}' AND action_key = 'vip26_starter_d01' AND action_date = '2026-08-31'");

    $reverse_path_prepared = database()->query("UPDATE forever_business_vip_enrollments
        SET qualifying_period = '2026-08-01',
            starting_track_key = NULL,
            starting_track_reason = NULL,
            starting_track_decided_at = NULL
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $reverse_path_prepared, 'Reverse path-freeze fixture could not be prepared.');
    $reactivation_started_fixture = database()->query("INSERT INTO forever_business_daily_outcomes
        (fbo_id, action_date, core_key, action_key, status, outcome_count, outcome_type,
         recorded_by_user_id, completion_mode, created_at, updated_at)
        VALUES ('{$fbo_id}', '2026-08-30', 'Development', 'vip26_reactivation_d01', 'done', 1, 'reactivation',
                {$fixture_user_ids[1]}, 'standard', '{$now}', '{$now}')");
    $assert((bool) $reactivation_started_fixture, 'Reverse Reaktivacija outcome fixture could not be created.');
    $reverse_fixed_now = new DateTimeImmutable('2026-09-01 00:00:01', new DateTimeZone('Europe/Zagreb'));
    $reverse_los = forever_business_get_los_admin_analytics($fixture_user_ids[0], 30, '', $reverse_fixed_now);
    $reverse_los_members = array_values(array_filter((array) ($reverse_los['members'] ?? []), static fn(array $member): bool => ($member['fbo_id'] ?? '') === $fbo_id));
    $assert(count($reverse_los_members) === 2
        && count(array_filter($reverse_los_members, static fn(array $member): bool =>
            ($member['starting_track_key'] ?? '') === 'reactivation'
            && ($member['starting_track_reason'] ?? '') === 'started_reactivation_path'
        )) === 2,
        'A completed Reaktivacija entry task must be reported as the shared starting path even before a decision is persisted.');
    $reverse_path = forever_business_persist_vip_start([
        'fbo_id' => $fbo_id,
        'vip_qualifying_period' => '2026-08-01',
        'vip_known_enrollment_date' => '2026-07-15',
    ]);
    $assert(($reverse_path['key'] ?? '') === 'reactivation'
        && ($reverse_path['reason'] ?? '') === 'started_reactivation_path'
        && !empty($reverse_path['is_persisted']),
        'A first completed Reaktivacija task must override later recent-enrollment evidence and freeze continuity.');
    $reverse_dashboard_a = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $reverse_fixed_now);
    $reverse_dashboard_b = forever_business_get_dashboard($fixture_user_ids[1], false, '', '', $reverse_fixed_now);
    $reverse_member_a = $reverse_dashboard_a['members'][0] ?? [];
    $reverse_member_b = $reverse_dashboard_b['members'][0] ?? [];
    $assert(($reverse_member_a['vip_starting_track_key'] ?? '') === 'reactivation'
        && ($reverse_member_a['vip_starting_track_reason'] ?? '') === 'started_reactivation_path'
        && ($reverse_member_b['vip_starting_track_key'] ?? '') === 'reactivation'
        && ($reverse_member_b['vip_starting_track_reason'] ?? '') === 'started_reactivation_path',
        'Both FCC accounts sharing the FBO must receive the frozen Reaktivacija starting path.');
    database()->query("DELETE FROM forever_business_daily_outcomes
        WHERE fbo_id = '{$fbo_id}' AND action_key = 'vip26_reactivation_d01' AND action_date = '2026-08-30'");

    $unstarted_cohort_prepared = database()->query("UPDATE forever_business_vip_enrollments
        SET qualifying_period = '2026-09-01',
            starting_track_key = 'starter',
            starting_track_reason = 'recent_enrollment',
            starting_track_decided_at = '{$now}'
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $unstarted_cohort_prepared, 'Unstarted cohort fixture could not be prepared.');
    $assert(forever_business_record_vip_eligibility_metric($fbo_id, '2026-08-01', 0.500, null, 'member_cc', $fixture_user_ids[0]),
        'Earlier qualifying-period fixture could not update the unstarted enrollment.');
    $unstarted_cohort_result = database()->query("SELECT qualifying_period, starting_track_key, starting_track_reason, starting_track_decided_at
        FROM forever_business_vip_enrollments WHERE fbo_id = '{$fbo_id}' LIMIT 1");
    $unstarted_cohort = $unstarted_cohort_result ? $unstarted_cohort_result->fetch_assoc() : [];
    $assert(($unstarted_cohort['qualifying_period'] ?? '') === '2026-08-01'
        && empty($unstarted_cohort['starting_track_key'])
        && empty($unstarted_cohort['starting_track_reason'])
        && empty($unstarted_cohort['starting_track_decided_at']),
        'An earlier qualifying import may clear an unstarted path so the corrected cohort is recomputed.');

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
        /* Deliberately incorrect client value: immutable action_key must win. */
        'sequence_position' => 30,
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

    /* Simulate a dashboard that classified the August cohort immediately before
     * an overlapping import moved the same FBO to an earlier July cohort. The
     * stale dashboard must not persist its August decision into the new cohort. */
    $stale_snapshot_ready = database()->query("UPDATE forever_business_vip_enrollments
        SET qualifying_period = '2026-07-01',
            starting_track_key = NULL,
            starting_track_reason = NULL,
            starting_track_decided_at = NULL
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $stale_snapshot_ready, 'Concurrent import fixture could not move the enrollment to an earlier cohort.');
    $stale_start_result = forever_business_persist_vip_start([
        'fbo_id' => $fbo_id,
        'vip_qualifying_period' => '2026-08-01',
        'vip_known_enrollment_date' => '2026-07-15',
    ]);
    $stale_storage_result = database()->query("SELECT qualifying_period, starting_track_key, starting_track_reason, starting_track_decided_at
        FROM forever_business_vip_enrollments
        WHERE fbo_id = '{$fbo_id}'
        LIMIT 1");
    $stale_storage = $stale_storage_result ? $stale_storage_result->fetch_assoc() : [];
    $assert(($stale_storage['qualifying_period'] ?? '') === '2026-07-01'
        && empty($stale_storage['starting_track_key'])
        && empty($stale_storage['starting_track_reason'])
        && empty($stale_storage['starting_track_decided_at'])
        && ($stale_start_result['key'] ?? '') === 'starter',
        'A stale dashboard classification must not be written after a concurrent earlier qualifying-period import.');
    $stale_snapshot_restored = database()->query("UPDATE forever_business_vip_enrollments
        SET qualifying_period = '2026-08-01'
        WHERE fbo_id = '{$fbo_id}'");
    $assert((bool) $stale_snapshot_restored, 'Concurrent import fixture could not restore the August cohort.');

    $september_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $fixed_now);
    $september_member = $september_dashboard['members'][0] ?? [];
    $starting_track_result = database()->query("SELECT starting_track_key, starting_track_reason, starting_track_decided_at
        FROM forever_business_vip_enrollments
        WHERE fbo_id = '{$fbo_id}'
        LIMIT 1");
    $starting_track = $starting_track_result ? $starting_track_result->fetch_assoc() : [];
    $assert(($starting_track['starting_track_key'] ?? '') === 'starter'
        && ($starting_track['starting_track_reason'] ?? '') === 'recent_enrollment'
        && !empty($starting_track['starting_track_decided_at']),
        'The first dashboard resolution must persist the recent enrollment as the FBO starting track exactly once.');
    $assert(($september_member['vip_starting_track_key'] ?? '') === 'starter'
        && ($september_member['vip_starting_track_reason'] ?? '') === 'recent_enrollment',
        'The dashboard member must expose the persisted FBO starting-track decision.');

    $shared_start_dashboard = forever_business_get_dashboard($fixture_user_ids[1], false, '', '', $fixed_now);
    $shared_start_member = $shared_start_dashboard['members'][0] ?? [];
    $assert(($shared_start_member['vip_starting_track_key'] ?? '') === 'starter'
        && ($shared_start_member['vip_starting_track_reason'] ?? '') === 'recent_enrollment',
        'Every FCC account linked to the same FBO must receive the same persisted starting track.');

    /* Make a later recomputation disagree with the original decision: activity
     * predating the recorded sponsor date followed by a zero month is both a
     * historical return/pause signal and a data conflict. The original FBO
     * decision, reason and timestamp must still be immutable. */
    $starting_track_timestamp_sentinel = '2026-08-20 12:34:56';
    $starting_track_sentinel_ready = database()->query("UPDATE forever_business_vip_enrollments
        SET starting_track_decided_at = '{$starting_track_timestamp_sentinel}'
        WHERE fbo_id = '{$fbo_id}'
          AND starting_track_key = 'starter'
          AND starting_track_reason = 'recent_enrollment'");
    $assert((bool) $starting_track_sentinel_ready && (int) database()->affected_rows === 1,
        'Persisted starting-track timestamp sentinel could not be prepared.');
    $history_fixture_time = database()->real_escape_string(get_date());
    $old_activity_ready = database()->query("INSERT INTO forever_business_metrics
        (fbo_id, period_month, personal_cc, total_cc, total_active_cc, non_manager_cc, leadership_cc,
         is_4cc_active, source_import_id, updated_at)
        VALUES
        ('{$fbo_id}', '2026-06-01', 0.500, 0.500, 0.500, 0.000, 0.000, 0, NULL, '{$history_fixture_time}'),
        ('{$fbo_id}', '2026-07-01', 0.000, 0.000, 0.000, 0.000, 0.000, 0, NULL, '{$history_fixture_time}')");
    $assert((bool) $old_activity_ready, 'Contradictory historical activity and pause fixtures could not be created.');

    $persistent_start_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $fixed_now);
    $persistent_start_member = $persistent_start_dashboard['members'][0] ?? [];
    $persistent_start_result = database()->query("SELECT starting_track_key, starting_track_reason, starting_track_decided_at
        FROM forever_business_vip_enrollments
        WHERE fbo_id = '{$fbo_id}'
        LIMIT 1");
    $persistent_start = $persistent_start_result ? $persistent_start_result->fetch_assoc() : [];
    $assert(($persistent_start['starting_track_key'] ?? '') === 'starter'
        && ($persistent_start['starting_track_reason'] ?? '') === 'recent_enrollment'
        && ($persistent_start['starting_track_decided_at'] ?? '') === $starting_track_timestamp_sentinel,
        'Later contradictory history must not rewrite the persisted FBO starting-track decision or timestamp.');
    $assert(($persistent_start_member['vip_starting_track_key'] ?? '') === 'starter'
        && ($persistent_start_member['vip_starting_track_reason'] ?? '') === 'recent_enrollment',
        'The dashboard must continue using the original persisted decision after later contradictory history appears.');
    database()->query("DELETE FROM forever_business_metrics
        WHERE fbo_id = '{$fbo_id}' AND period_month IN ('2026-06-01', '2026-07-01')");

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
        && ($september_member['vip_current_period_month'] ?? null) === null
        && (int) ($september_member['vip_verified_highest_track_rank'] ?? 0) === 2,
        'August must remain the previous month, persist its verified Activator threshold and never be copied into VIP current-month fields.');
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
        && count(array_filter($los_fixture_members, static fn(array $member): bool =>
            (float) ($member['personal_cc'] ?? -1) === 0.0
            && (float) ($member['total_active_cc'] ?? -1) === 0.0
            && ($member['track'] ?? '') === 'activator'
            && ($member['starting_track_reason'] ?? '') === 'recent_enrollment'
            && (int) ($member['vip_current_track_steps_completed'] ?? -1) === 0
            && empty($member['is_current_track_complete'])
        )) === 2,
        'LOS must preserve the persisted Starter entry path while reporting the promoted Activator level, its own zero progress and current-month CC zeros for every linked collaborator.');
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

    /* A promotion is a result milestone, not an accident of completing the
     * first new-track task before month end. Verify all three official-signal
     * states and persistence through a real October zero-month dashboard query. */
    forever_business_upsert_registered_member_live_cc($fbo_id, '2026-09-01', [
        'personal_cc' => 4.0,
        'total_cc' => 4.0,
        'total_active_cc' => 4.0,
        'non_manager_cc' => 0.0,
        'leadership_cc' => 0.0,
        'total_active_cc_ytd' => 81.0,
        'non_manager_cc_ytd' => 55.0,
        'leadership_cc_ytd' => 22.0,
        'is_4cc_active' => 0,
    ]);
    $october_zero = new DateTimeImmutable('2026-10-01 00:00:01', new DateTimeZone('Europe/Zagreb'));
    $official_negative_october_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $october_zero);
    $official_negative_october_member = $official_negative_october_dashboard['members'][0] ?? [];
    $assert(($official_negative_october_dashboard['period'] ?? '') === '2026-10-01'
        && (float) ($official_negative_october_member['personal_cc'] ?? -1) === 0.0
        && (int) ($official_negative_october_member['vip_verified_highest_track_rank'] ?? 0) === 2
        && ($official_negative_october_member['next_action']['track_key'] ?? '') === 'activator',
        'An explicit negative FLP360 4 CC signal must block historical Builder promotion even when September numeric values reach 1 Personal and 4 Total Active CC.');

    forever_business_upsert_registered_member_live_cc($fbo_id, '2026-09-01', [
        'personal_cc' => 1.0,
        'total_cc' => 4.0,
        'total_active_cc' => 4.0,
        'non_manager_cc' => 0.0,
        'leadership_cc' => 0.0,
        'total_active_cc_ytd' => 81.0,
        'non_manager_cc_ytd' => 55.0,
        'leadership_cc_ytd' => 22.0,
    ]);
    $unknown_signal_october_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $october_zero);
    $unknown_signal_october_member = $unknown_signal_october_dashboard['members'][0] ?? [];
    $assert((int) ($unknown_signal_october_member['vip_verified_highest_track_rank'] ?? 0) === 3
        && ($unknown_signal_october_member['next_action']['track_key'] ?? '') === 'builder'
        && ($unknown_signal_october_member['next_action']['key'] ?? '') === 'vip26_builder_d01'
        && (int) ($unknown_signal_october_member['vip_builder_sequence_position'] ?? -1) === 0,
        'A missing official FLP360 signal must allow historical Builder promotion only when September has both 1 Personal and 4 Total Active CC, then open Builder step one.');

    forever_business_upsert_registered_member_live_cc($fbo_id, '2026-09-01', [
        'personal_cc' => 0.0,
        'total_cc' => 0.0,
        'total_active_cc' => 0.0,
        'non_manager_cc' => 0.0,
        'leadership_cc' => 0.0,
        'total_active_cc_ytd' => 81.0,
        'non_manager_cc_ytd' => 55.0,
        'leadership_cc_ytd' => 22.0,
        'is_4cc_active' => 1,
    ]);
    $official_positive_october_dashboard = forever_business_get_dashboard($fixture_user_ids[0], false, '', '', $october_zero);
    $official_positive_october_member = $official_positive_october_dashboard['members'][0] ?? [];
    $official_positive_october_path = forever_business_get_vip_education_path($official_positive_october_member);
    $assert((float) ($official_positive_october_member['personal_cc'] ?? -1) === 0.0
        && (float) ($official_positive_october_member['total_active_cc'] ?? -1) === 0.0
        && (int) ($official_positive_october_member['vip_verified_highest_track_rank'] ?? 0) === 3
        && ($official_positive_october_member['next_action']['track_key'] ?? '') === 'builder'
        && ($official_positive_october_path['mode'] ?? '') === 'builder_focus',
        'An explicit positive FLP360 signal must preserve Builder after October resets to zero even when the historical numeric values are incomplete and no Builder task was completed.');
    $shared_account_october_dashboard = forever_business_get_dashboard($fixture_user_ids[1], false, '', '', $october_zero);
    $shared_account_october_member = $shared_account_october_dashboard['members'][0] ?? [];
    $assert((int) ($shared_account_october_member['vip_verified_highest_track_rank'] ?? 0) === 3
        && ($shared_account_october_member['next_action']['track_key'] ?? '') === 'builder',
        'Metric-derived progress for a shared FBO must resolve to the same education track on each linked FCC account.');
    database()->query("DELETE FROM forever_business_metrics WHERE fbo_id = '{$fbo_id}' AND period_month = '2026-09-01'");
    /* Restore the pre-existing fixture state so the later stale-qualification
     * queue test still exercises an account without permanent enrollment. */
    database()->query("DELETE FROM forever_business_vip_enrollments WHERE fbo_id = '{$fbo_id}'");

    $tampered_track_input = $input;
    $tampered_track_input['outcome_type'] = 'starter';
    $assert(!forever_business_record_daily_outcome($fixture_user_ids[0], $fbo_id, [$fbo_id], $tampered_track_input, $fixed_now), 'A client must not assign an action to a different education track.');
    $assert(forever_business_record_daily_outcome($fixture_user_ids[0], $fbo_id, [$fbo_id], $input, $fixed_now), 'First shared-FBO participant should save its own step.');
    $assert(!forever_business_record_daily_outcome($fixture_user_ids[0], $fbo_id, [$fbo_id], $input, $fixed_now), 'Same participant must not save the same/day step twice.');
    $assert(forever_business_record_daily_outcome($fixture_user_ids[1], $fbo_id, [$fbo_id], $input, $fixed_now), 'Second shared-FBO participant should save independently.');
    $assert(!forever_business_record_daily_outcome($fixture_user_ids[1], $tampered_fbo_id, [$tampered_fbo_id], $input, $fixed_now), 'Authenticated account must reject a tampered FBO ID.');

    $outcomes_result = database()->query("SELECT COUNT(*) AS total, COUNT(DISTINCT recorded_by_user_id) AS participants,
            MIN(completion_mode) AS completion_mode, MIN(sequence_position) AS min_sequence_position,
            MAX(sequence_position) AS max_sequence_position, MIN(outcome_type) AS outcome_type
        FROM forever_business_daily_outcomes
        WHERE fbo_id = '{$fbo_id}' AND action_key = 'vip26_activator_d01_biolink' AND action_date = '2026-09-01'");
    $outcomes = $outcomes_result ? $outcomes_result->fetch_assoc() : [];
    $assert((int) ($outcomes['total'] ?? 0) === 2 && (int) ($outcomes['participants'] ?? 0) === 2, 'Shared FBO must contain two separate participant outcomes.');
    $assert((string) ($outcomes['completion_mode'] ?? '') === 'standard', 'Completion mode must be stored.');
    $assert((int) ($outcomes['min_sequence_position'] ?? 0) === 1
        && (int) ($outcomes['max_sequence_position'] ?? 0) === 1
        && ($outcomes['outcome_type'] ?? '') === 'activator',
        'Per-level sequence and track must be derived from the immutable action key rather than client input.');

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

    /* LOS must keep permanent totals without letting an earlier 30-step level
     * complete, start or stall the newly promoted level. An unresolved request
     * from the earlier level remains auditable but leaves the live mentor queue. */
    $assert(forever_business_record_vip_eligibility_metric($fbo_id, '2026-08-01', 1.250, null, 'member_cc', $fixture_user_ids[0]),
        'LOS current-level fixture enrollment could not be restored.');
    $prior_level_values = [];
    for($day = 1; $day <= 30; $day++) {
        $action_key = sprintf('vip26_starter_d%02d', $day);
        $prior_level_values[] = "('{$fbo_id}', '2026-08-01', 'Development', '{$action_key}', 'done', 1, 'starter', {$day}, {$fixture_user_ids[0]}, 'standard', '{$now}', '{$now}')";
    }
    $prior_level_inserted = database()->query("INSERT INTO forever_business_daily_outcomes
        (fbo_id, action_date, core_key, action_key, status, outcome_count, outcome_type,
         sequence_position, recorded_by_user_id, completion_mode, created_at, updated_at)
        VALUES " . implode(',', $prior_level_values));
    $assert((bool) $prior_level_inserted, 'LOS prior-level lifetime fixture could not be created.');

    $los_promotion_now = new DateTimeImmutable('2026-09-04 10:00:00', new DateTimeZone('Europe/Zagreb'));
    $assert(forever_business_request_vip_help($fixture_user_ids[0], $fbo_id, [$fbo_id], [
        'action_key' => 'vip26_activator_d03',
        'track_key' => 'activator',
        'sequence_position' => 3,
        'difficulty' => 'hard',
        'note' => 'Otvorena pomoć prethodne razine za LOS provjeru.',
    ], $los_promotion_now), 'LOS previous-level help fixture could not be opened before promotion.');
    forever_business_upsert_registered_member_live_cc($fbo_id, '2026-09-01', [
        'personal_cc' => 1.0,
        'total_cc' => 4.0,
        'total_active_cc' => 4.0,
        'non_manager_cc' => 0.0,
        'leadership_cc' => 0.0,
        'total_active_cc_ytd' => 81.0,
        'non_manager_cc_ytd' => 55.0,
        'leadership_cc_ytd' => 22.0,
        'is_4cc_active' => 1,
    ]);

    $promoted_los = forever_business_get_los_admin_analytics($fixture_user_ids[0], 30, '', $los_promotion_now);
    $promoted_los_members = array_values(array_filter((array) ($promoted_los['members'] ?? []), static fn(array $member): bool => ($member['fbo_id'] ?? '') === $fbo_id));
    $promoted_los_user = array_values(array_filter($promoted_los_members, static fn(array $member): bool => (int) ($member['user_id'] ?? 0) === $fixture_user_ids[0]))[0] ?? [];
    $promoted_funnel = array_column((array) ($promoted_los['linkage_funnel'] ?? []), 'value', 'key');
    $assert(($promoted_los_user['track'] ?? '') === 'builder'
        && (int) ($promoted_los_user['vip_steps_completed'] ?? -1) === 32
        && (int) ($promoted_los_user['vip_current_track_steps_completed'] ?? -1) === 0
        && empty($promoted_los_user['is_current_track_complete'])
        && empty($promoted_los_user['needs_help'])
        && (int) ($promoted_los_user['open_help_count'] ?? -1) === 0
        && ($promoted_los_user['stall_state'] ?? '') === 'no_start_3d',
        'LOS must preserve 32 lifetime steps while a promoted Builder starts at zero, stays incomplete and ignores earlier-level help for live status.');
    $assert((int) ($promoted_los['kpis']['started']['total'] ?? -1) === 0
        && (int) ($promoted_los['kpis']['completed']['total'] ?? -1) === 0
        && (int) ($promoted_los['kpis']['open_help']['current'] ?? -1) === 0
        && (int) ($promoted_funnel['started'] ?? -1) === 0
        && (int) ($promoted_funnel['completed'] ?? -1) === 0,
        'LOS started/completed KPIs and coverage funnel must use only each participant current level.');
    $old_help_audit_result = database()->query("SELECT status FROM forever_business_vip_help_requests
        WHERE user_id = {$fixture_user_ids[0]} AND action_key = 'vip26_activator_d03' LIMIT 1");
    $old_help_audit = $old_help_audit_result ? $old_help_audit_result->fetch_assoc() : [];
    $assert(($old_help_audit['status'] ?? '') === 'open',
        'A promotion must filter previous-level help from the live queue without deleting its audit record.');

    $current_level_completion = database()->query("INSERT INTO forever_business_daily_outcomes
        (fbo_id, action_date, core_key, action_key, status, outcome_count, outcome_type,
         sequence_position, recorded_by_user_id, completion_mode, created_at, updated_at)
        VALUES ('{$fbo_id}', '2026-09-04', 'Development', 'vip26_builder_d30', 'done', 1, 'builder',
                30, {$fixture_user_ids[1]}, 'standard', '{$now}', '{$now}')");
    $assert((bool) $current_level_completion, 'LOS current-level completion fixture could not be created.');
    $completed_level_los = forever_business_get_los_admin_analytics($fixture_user_ids[0], 30, '', $los_promotion_now);
    $completed_level_members = array_values(array_filter((array) ($completed_level_los['members'] ?? []), static fn(array $member): bool => ($member['fbo_id'] ?? '') === $fbo_id));
    $completed_level_user = array_values(array_filter($completed_level_members, static fn(array $member): bool => (int) ($member['user_id'] ?? 0) === $fixture_user_ids[1]))[0] ?? [];
    $completed_level_funnel = array_column((array) ($completed_level_los['linkage_funnel'] ?? []), 'value', 'key');
    $assert(($completed_level_user['track'] ?? '') === 'builder'
        && (int) ($completed_level_user['vip_steps_completed'] ?? -1) === 2
        && (int) ($completed_level_user['vip_current_track_steps_completed'] ?? -1) === 30
        && !empty($completed_level_user['is_current_track_complete'])
        && ($completed_level_user['vip_current_track_completed_at'] ?? '') === '2026-09-04'
        && ($completed_level_user['stall_state'] ?? '') === '',
        'Only position 30 in the current track may set the LOS completion badge and remove that participant from stall handling.');
    $assert((int) ($completed_level_los['kpis']['started']['total'] ?? -1) === 1
        && (int) ($completed_level_los['kpis']['completed']['total'] ?? -1) === 1
        && (int) ($completed_level_los['kpis']['completed']['current'] ?? -1) === 1
        && (int) ($completed_level_funnel['started'] ?? -1) === 1
        && (int) ($completed_level_funnel['completed'] ?? -1) === 1,
        'LOS current-level start and completion must flow consistently through KPIs and the coverage funnel.');

    database()->query("DELETE FROM forever_business_vip_help_requests
        WHERE user_id = {$fixture_user_ids[0]} AND action_key = 'vip26_activator_d03'");
    database()->query("DELETE FROM forever_business_daily_outcomes
        WHERE recorded_by_user_id = {$fixture_user_ids[0]} AND action_key LIKE 'vip26_starter_d%'");
    database()->query("DELETE FROM forever_business_daily_outcomes
        WHERE recorded_by_user_id = {$fixture_user_ids[1]} AND action_key = 'vip26_builder_d30'");
    database()->query("DELETE FROM forever_business_metrics WHERE fbo_id = '{$fbo_id}' AND period_month = '2026-09-01'");
    database()->query("DELETE FROM forever_business_vip_enrollments WHERE fbo_id = '{$fbo_id}'");

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
        database()->query("DELETE FROM forever_business_focus_metrics WHERE fbo_id IN ('{$fbo_id}', '{$tampered_fbo_id}')");
        database()->query("DELETE FROM forever_business_metrics WHERE fbo_id IN ('{$fbo_id}', '{$tampered_fbo_id}')");
        database()->query("DELETE FROM users WHERE user_id IN ({$id_list})");
    }
    database()->query("DELETE FROM forever_business_members WHERE fbo_id IN ('{$fbo_id}', '{$tampered_fbo_id}')
        AND name LIKE 'VIP Launch Test %'");
}

exit($exit_code ?? 0);
