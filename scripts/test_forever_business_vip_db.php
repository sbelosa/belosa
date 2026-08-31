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

    $zagreb_launch_boundary = new DateTimeImmutable('2026-09-01 00:01:00', new DateTimeZone('Europe/Zagreb'));
    forever_business_record_page_visit($fixture_user_ids[0], $zagreb_launch_boundary);
    $visit_result = database()->query("SELECT visit_date FROM forever_business_page_visits
        WHERE user_id = {$fixture_user_ids[0]} LIMIT 1");
    $visit = $visit_result ? $visit_result->fetch_assoc() : [];
    $assert(($visit['visit_date'] ?? '') === '2026-09-01', 'Launch visit must use the Zagreb calendar date rather than UTC.');

    $input = [
        'core_key' => 'Development',
        'action_key' => 'vip26_starter_d01',
        'outcome_type' => 'starter',
        'result_type' => 'training',
        'difficulty' => 'normal',
        'completion_mode' => 'standard',
        'needs_help' => false,
        'outcome_count' => 1,
        'sequence_position' => 1,
        'note' => '',
    ];

    $fixed_now = new DateTimeImmutable('2026-09-01 00:00:01', new DateTimeZone('Europe/Zagreb'));
    $assert(forever_business_record_daily_outcome($fixture_user_ids[0], $fbo_id, [$fbo_id], $input, $fixed_now), 'First shared-FBO participant should save its own step.');
    $assert(!forever_business_record_daily_outcome($fixture_user_ids[0], $fbo_id, [$fbo_id], $input, $fixed_now), 'Same participant must not save the same/day step twice.');
    $assert(forever_business_record_daily_outcome($fixture_user_ids[1], $fbo_id, [$fbo_id], $input, $fixed_now), 'Second shared-FBO participant should save independently.');
    $assert(!forever_business_record_daily_outcome($fixture_user_ids[1], $tampered_fbo_id, [$tampered_fbo_id], $input, $fixed_now), 'Authenticated account must reject a tampered FBO ID.');

    $outcomes_result = database()->query("SELECT COUNT(*) AS total, COUNT(DISTINCT recorded_by_user_id) AS participants,
            MIN(completion_mode) AS completion_mode
        FROM forever_business_daily_outcomes
        WHERE fbo_id = '{$fbo_id}' AND action_key = 'vip26_starter_d01' AND action_date = '2026-09-01'");
    $outcomes = $outcomes_result ? $outcomes_result->fetch_assoc() : [];
    $assert((int) ($outcomes['total'] ?? 0) === 2 && (int) ($outcomes['participants'] ?? 0) === 2, 'Shared FBO must contain two separate participant outcomes.');
    $assert((string) ($outcomes['completion_mode'] ?? '') === 'standard', 'Completion mode must be stored.');

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
        'action_key' => 'vip26_starter_d02',
        'track_key' => 'starter',
        'sequence_position' => 2,
        'difficulty' => 'hard',
        'note' => 'Trebam pomoć sa zamjenskom radnjom.',
    ], $help_day), 'Participant should be able to request help without a completion.');

    $help_result = database()->query("SELECT status, note FROM forever_business_vip_help_requests
        WHERE user_id = {$fixture_user_ids[0]} AND action_key = 'vip26_starter_d02'");
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
        'action_key' => 'vip26_starter_d02',
        'outcome_type' => 'starter',
        'result_type' => 'conversation',
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
        WHERE user_id = {$fixture_user_ids[0]} AND action_key = 'vip26_starter_d02'");
    $resolved_help = $resolved_help_result ? $resolved_help_result->fetch_assoc() : [];
    $assert(($resolved_help['status'] ?? '') === 'resolved' && !empty($resolved_help['resolved_at']), 'Completion must resolve the open help request for the same participant and action.');
    $assert(!forever_business_request_vip_help($fixture_user_ids[0], $fbo_id, [$fbo_id], [
        'action_key' => 'vip26_starter_d02',
        'track_key' => 'starter',
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
        database()->query("DELETE FROM users WHERE user_id IN ({$id_list})");
    }
    database()->query("DELETE FROM forever_business_members WHERE fbo_id IN ('{$fbo_id}', '{$tampered_fbo_id}')
        AND name LIKE 'VIP Launch Test %'");
}

exit($exit_code ?? 0);
