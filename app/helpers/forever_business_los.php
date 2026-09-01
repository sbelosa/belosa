<?php
/* Custom code: FC-2026-08-21: Read-only Moj Forever analytics for the admin LOS */

defined('ALTUMCODE') || die();

/**
 * This helper deliberately contains SELECT/SHOW queries only. The collaborator
 * dashboard owns enrollment and task writes; the LOS only reads their results.
 */

function forever_business_los_table_columns(string $table): array {
    static $cache = [];

    if(isset($cache[$table])) {
        return $cache[$table];
    }

    if(!preg_match('/^[a-z0-9_]+$/i', $table)) {
        return [];
    }

    $columns = [];
    try {
        $result = database()->query("SHOW COLUMNS FROM `{$table}`");
        while($result && $row = $result->fetch_assoc()) {
            $columns[(string) $row['Field']] = true;
        }
    } catch(\Throwable $exception) {
        $columns = [];
    }

    return $cache[$table] = $columns;
}

function forever_business_los_rows(string $query): array {
    $rows = [];
    $result = database()->query($query);
    if(!$result) {
        throw new \RuntimeException('Moj Forever LOS query could not be completed safely.');
    }
    while($result && $row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function forever_business_los_row(string $query): array {
    $result = database()->query($query);
    if(!$result) {
        throw new \RuntimeException('Moj Forever LOS query could not be completed safely.');
    }
    return $result && ($row = $result->fetch_assoc()) ? $row : [];
}

function forever_business_los_delta(float $current, float $previous): array {
    return [
        'current' => $current,
        'previous' => $previous,
        'change' => $current - $previous,
        'change_percent' => $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null,
    ];
}

function forever_business_los_effective_four_cc(array $metric): bool {
    if(array_key_exists('is_4cc_active', $metric) && $metric['is_4cc_active'] !== null) {
        return (int) $metric['is_4cc_active'] === 1;
    }

    if(($metric['personal_cc'] ?? null) === null || ($metric['total_active_cc'] ?? null) === null) {
        return false;
    }

    return (float) ($metric['personal_cc'] ?? 0) >= 1.0
        && (float) ($metric['total_active_cc'] ?? 0) >= 4.0;
}

function forever_business_los_track_from_action_key(string $action_key): string {
    foreach(['leader', 'builder', 'activator', 'reactivation', 'starter'] as $track) {
        if(strpos($action_key, 'vip26_' . $track . '_') === 0) {
            return $track;
        }
    }
    return 'other';
}

/**
 * Resolve the participant's live education track with the same promotion
 * rules used by the member workspace. LOS supplies trusted, FBO-wide metric
 * facts and participant-scoped outcome ranks; task history itself stays
 * attached to the FCC account.
 */
function forever_business_los_resolve_current_track(array $member, int $admin_user_id, string $admin_root_fbo_id, bool $admin_is_platform_admin): string {
    $track_member = $member + [
        'vip_starting_track_key' => $member['starting_track_key'] ?? null,
        'vip_starting_track_reason' => $member['starting_track_reason'] ?? null,
        'vip_qualifying_period' => $member['qualifying_period'] ?? null,
        'vip_base_personal_cc' => $member['qualifying_personal_cc'] ?? 0,
        'vip_base_is_4cc_active' => null,
        'vip_august_is_4cc_active' => $member['vip_august_is_4cc_active'] ?? null,
        'vip_current_personal_cc' => $member['vip_current_personal_cc'] ?? 0,
        'vip_current_total_active_cc' => $member['vip_current_total_active_cc'] ?? 0,
        'vip_current_is_4cc_active' => $member['vip_current_is_4cc_active'] ?? null,
        'vip_verified_highest_track_rank' => $member['vip_verified_highest_track_rank'] ?? 0,
        'vip_highest_track_rank' => $member['vip_highest_track_rank'] ?? 0,
        'vip_highest_nonleader_track_rank' => $member['vip_highest_nonleader_track_rank'] ?? 0,
        'verified_progress' => forever_business_get_verified_progress($member),
    ];

    if($admin_is_platform_admin
        && (int) ($member['user_id'] ?? 0) === $admin_user_id
        && $admin_root_fbo_id !== ''
        && hash_equals($admin_root_fbo_id, (string) ($member['fbo_id'] ?? ''))) {
        $track_member['force_vip_leader'] = true;
    }

    $track = forever_business_get_vip_track($track_member);
    return in_array((string) ($track['key'] ?? ''), ['starter', 'reactivation', 'activator', 'builder', 'leader'], true)
        ? (string) $track['key']
        : 'starter';
}

function forever_business_los_periods(?\DateTimeInterface $now = null): array {
    if(empty(forever_business_los_table_columns('forever_business_metrics'))) {
        return [];
    }

    $current_zagreb_period = forever_business_current_zagreb_period($now);
    $rows = forever_business_los_rows("SELECT DISTINCT `period_month`
        FROM `forever_business_metrics`
        WHERE `period_month` <= '{$current_zagreb_period}'
        ORDER BY `period_month` DESC
        LIMIT 36");

    $periods = array_values(array_filter(array_map(static function(array $row): string {
        return preg_match('/^20\d{2}-\d{2}-01$/', (string) ($row['period_month'] ?? ''))
            ? (string) $row['period_month']
            : '';
    }, $rows)));

    /* The open Zagreb month is a reporting period even before its first metric
     * row. Keep historical rows intact and represent the missing month only in
     * this read model. */
    return array_values(array_unique(array_merge([$current_zagreb_period], $periods)));
}

function forever_business_los_empty_model(int $window_days, string $period, array $warnings): array {
    return [
        'window_days' => $window_days,
        'period' => $period,
        'periods' => [],
        'window' => [],
        'kpis' => [],
        'global' => [],
        'linkage_funnel' => [],
        'charts' => [
            'daily' => ['labels' => [], 'tasks' => [], 'results' => [], 'standard' => [], 'quick' => []],
            'cc' => ['labels' => [], 'values' => [], 'closed' => []],
            'outcomes' => ['result_type' => [], 'core' => [], 'track' => [], 'difficulty' => [], 'completion_mode' => []],
        ],
        'top_performers' => [],
        'top_results' => [],
        'attention_queue' => [],
        'members' => [],
        'email_queue' => [],
        'data_quality' => [],
        'warnings' => $warnings,
        'generated_at' => date('c'),
    ];
}

function forever_business_los_distribution(array $counts): array {
    arsort($counts);
    $result = [];
    foreach($counts as $key => $value) {
        $result[] = ['key' => (string) $key, 'value' => (int) $value];
    }
    return $result;
}

function forever_business_get_los_admin_analytics(int $admin_user_id, int $window_days = 30, string $period = '', ?\DateTimeInterface $now = null): array {
    $admin_user_id = max(1, $admin_user_id); // Authorization belongs to the admin route; no user data is written here.

    $allowed_windows = [7, 14, 30, 60];
    $window_days = in_array($window_days, $allowed_windows, true) ? $window_days : 30;
    $warnings = [];

    $required_tables = [
        'forever_business_members',
        'forever_business_metrics',
        'forever_business_daily_outcomes',
    ];
    foreach($required_tables as $required_table) {
        if(empty(forever_business_los_table_columns($required_table))) {
            $warnings[] = ['key' => 'missing_table', 'params' => [$required_table]];
        }
    }

    $timezone = new \DateTimeZone('Europe/Zagreb');
    $today = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)->setTime(0, 0)
        : new \DateTimeImmutable('today', $timezone);
    $current_zagreb_period = $today->format('Y-m-01');
    $periods = forever_business_los_periods($today);
    $period = preg_match('/^20\d{2}-\d{2}-01$/', $period) && in_array($period, $periods, true)
        ? $period
        : ($periods[0] ?? $current_zagreb_period);

    if(!empty($warnings)) {
        $empty = forever_business_los_empty_model($window_days, $period, $warnings);
        $empty['periods'] = $periods;
        return $empty;
    }

    $admin_root_fbo_id = '';
    $admin_root_row = forever_business_los_row("SELECT `type`, REPLACE(TRIM(COALESCE(
            JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.foreverId')),
            JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.forever_id')),
            JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.foreverID')),
            ''
        )), '-', '') AS `fbo_id`
        FROM `users`
        WHERE `user_id` = {$admin_user_id} AND `status` = 1 AND JSON_VALID(`preferences`) = 1
        LIMIT 1");
    if(preg_match('/^[0-9]{12}$/', (string) ($admin_root_row['fbo_id'] ?? ''))) {
        $admin_root_fbo_id = (string) $admin_root_row['fbo_id'];
    }
    $admin_is_platform_admin = (int) ($admin_root_row['type'] ?? 0) === 1;

    $current_start = $today->modify('-' . ($window_days - 1) . ' days');
    $previous_end = $current_start->modify('-1 day');
    $previous_start = $previous_end->modify('-' . ($window_days - 1) . ' days');
    $current_end_string = $today->format('Y-m-d');
    $current_start_string = $current_start->format('Y-m-d');
    $previous_end_string = $previous_end->format('Y-m-d');
    $previous_start_string = $previous_start->format('Y-m-d');
    $previous_period = (new \DateTimeImmutable($period))->modify('-1 month')->format('Y-m-01');

    $member_rows = forever_business_los_rows("SELECT `fbo_id`, `name`, `title`, `generation`, `is_manager`, `is_in_current_structure`
        FROM `forever_business_members`
        ORDER BY `name` ASC");
    $members = [];
    foreach($member_rows as $row) {
        $fbo_id = (string) $row['fbo_id'];
        $members[$fbo_id] = [
            'fbo_id' => $fbo_id,
            'name' => (string) ($row['name'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'generation' => isset($row['generation']) ? (int) $row['generation'] : null,
            'is_manager' => !empty($row['is_manager']),
            'is_in_current_structure' => !empty($row['is_in_current_structure']),
            'linked_accounts' => 0,
            'is_enrolled' => false,
            'qualification_source' => '',
            'starting_track_key' => '',
            'starting_track_reason' => '',
            'starting_track_decided_at' => null,
            'enrolled_at' => null,
            'enrollment_event_date' => null,
            'qualifying_period' => null,
            'qualifying_personal_cc' => null,
            'last_verified_period' => null,
            'last_verified_personal_cc' => null,
            'personal_cc' => null,
            'total_active_cc' => null,
            'is_4cc_active' => null,
            'has_activity_data' => false,
            'has_complete_activity_verification' => false,
            'tasks' => 0,
            'results' => 0,
            'previous_tasks' => 0,
            'previous_results' => 0,
            'started_at' => null,
            'last_task_date' => null,
            'vip_steps_completed' => 0,
            'completed_at' => null,
            'vip_current_track_steps_completed' => 0,
            'vip_current_track_started_at' => null,
            'vip_current_track_last_task_date' => null,
            'vip_current_track_completed_at' => null,
            'vip_current_track_available_at' => null,
            'is_current_track_complete' => false,
            'vip_verified_highest_track_rank' => 0,
            'vip_august_is_4cc_active' => null,
            'vip_current_personal_cc' => 0.0,
            'vip_current_total_active_cc' => 0.0,
            'vip_current_is_4cc_active' => null,
            'vip_activator_achieved_at' => null,
            'vip_builder_achieved_at' => null,
            'vip_leader_eligible_at' => null,
            'result_type' => '',
            'difficulty' => '',
            'needs_help' => false,
            'track' => '',
            'stall_state' => '',
        ];
    }

    $metric_rows = forever_business_los_rows("SELECT metric.`fbo_id`, metric.`period_month`, metric.`personal_cc`, metric.`total_active_cc`,
            CASE WHEN metric.`source_import_id` IS NOT NULL AND (source_import.`import_id` IS NULL OR source_import.`report_kind` NOT IN ('downline', 'four_cc_active')) THEN NULL ELSE metric.`is_4cc_active` END AS `is_4cc_active`
        FROM `forever_business_metrics` metric
        LEFT JOIN `forever_business_imports` source_import ON source_import.`import_id` = metric.`source_import_id`
        WHERE metric.`period_month` IN ('{$period}', '{$previous_period}')");
    $current_metrics = [];
    $previous_metrics = [];
    foreach($metric_rows as $row) {
        $fbo_id = (string) $row['fbo_id'];
        if(!isset($members[$fbo_id])) continue;
        $metric = forever_business_normalize_current_month_metrics([
            'personal_cc' => $row['personal_cc'] === null ? null : (float) $row['personal_cc'],
            'total_active_cc' => $row['total_active_cc'] === null ? null : (float) $row['total_active_cc'],
            'is_4cc_active' => $row['is_4cc_active'] === null ? null : (int) $row['is_4cc_active'],
        ], (string) $row['period_month'], $today);
        $metric['has_activity_data'] = $metric['is_4cc_active'] !== null
            || $metric['personal_cc'] !== null
            || $metric['total_active_cc'] !== null;
        $metric['has_complete_activity_verification'] = $metric['is_4cc_active'] !== null
            || ($metric['personal_cc'] !== null && $metric['total_active_cc'] !== null);
        if((string) $row['period_month'] === $period) {
            $current_metrics[$fbo_id] = $metric;
            $members[$fbo_id] = array_merge($members[$fbo_id], $metric);
        } else {
            $previous_metrics[$fbo_id] = $metric;
        }
    }

    /* Exact Forever ID linkage only; names and email addresses never enter this model. */
    $linked_rows = forever_business_los_rows("SELECT `fbo_id`, COUNT(*) AS `linked_accounts`
        FROM (
            SELECT REPLACE(TRIM(COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.foreverId')),
                JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.forever_id')),
                JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.foreverID')),
                ''
            )), '-', '') AS `fbo_id`
            FROM `users`
            WHERE `status` = 1 AND JSON_VALID(`preferences`) = 1
        ) `linked`
        WHERE `fbo_id` REGEXP '^[0-9]{12}$'
        GROUP BY `fbo_id`");
    $linked_account_counts = [];
    foreach($linked_rows as $row) {
        $linked_account_counts[(string) $row['fbo_id']] = (int) $row['linked_accounts'];
        if(isset($members[$row['fbo_id']])) {
            $members[$row['fbo_id']]['linked_accounts'] = (int) $row['linked_accounts'];
        }
    }

    $enrollment_columns = forever_business_los_table_columns('forever_business_vip_enrollments');
    if(empty($enrollment_columns)) {
        $warnings[] = ['key' => 'missing_enrollment', 'params' => []];
    } else {
        $qualification_source_select = isset($enrollment_columns['qualification_source']) ? '`qualification_source`' : "'' AS `qualification_source`";
        $starting_track_key_select = isset($enrollment_columns['starting_track_key']) ? '`starting_track_key`' : "'' AS `starting_track_key`";
        $starting_track_reason_select = isset($enrollment_columns['starting_track_reason']) ? '`starting_track_reason`' : "'' AS `starting_track_reason`";
        $starting_track_decided_at_select = isset($enrollment_columns['starting_track_decided_at']) ? '`starting_track_decided_at`' : 'NULL AS `starting_track_decided_at`';
        $enrollment_rows = forever_business_los_rows("SELECT `fbo_id`, `qualifying_period`, `qualifying_personal_cc`,
                `last_verified_period`, `last_verified_personal_cc`, {$qualification_source_select},
                {$starting_track_key_select}, {$starting_track_reason_select}, {$starting_track_decided_at_select}, `enrolled_at`
            FROM `forever_business_vip_enrollments`");
        foreach($enrollment_rows as $row) {
            $fbo_id = (string) $row['fbo_id'];
            if(!isset($members[$fbo_id])) {
                $members[$fbo_id] = [
                    'fbo_id' => $fbo_id,
                    'name' => 'FBO ' . $fbo_id,
                    'title' => '',
                    'generation' => null,
                    'is_manager' => false,
                    'is_in_current_structure' => false,
                    'linked_accounts' => $linked_account_counts[$fbo_id] ?? 0,
                    'is_enrolled' => false,
                    'qualification_source' => '',
                    'starting_track_key' => '',
                    'starting_track_reason' => '',
                    'starting_track_decided_at' => null,
                    'enrolled_at' => null,
                    'enrollment_event_date' => null,
                    'qualifying_period' => null,
                    'qualifying_personal_cc' => null,
                    'last_verified_period' => null,
                    'last_verified_personal_cc' => null,
                    'personal_cc' => null,
                    'total_active_cc' => null,
                    'is_4cc_active' => null,
                    'has_activity_data' => false,
                    'has_complete_activity_verification' => false,
                    'tasks' => 0,
                    'results' => 0,
                    'previous_tasks' => 0,
                    'previous_results' => 0,
                    'started_at' => null,
                    'last_task_date' => null,
                    'vip_steps_completed' => 0,
                    'completed_at' => null,
                    'vip_current_track_steps_completed' => 0,
                    'vip_current_track_started_at' => null,
                    'vip_current_track_last_task_date' => null,
                    'vip_current_track_completed_at' => null,
                    'vip_current_track_available_at' => null,
                    'is_current_track_complete' => false,
                    'vip_verified_highest_track_rank' => 0,
                    'vip_august_is_4cc_active' => null,
                    'vip_current_personal_cc' => 0.0,
                    'vip_current_total_active_cc' => 0.0,
                    'vip_current_is_4cc_active' => null,
                    'vip_activator_achieved_at' => null,
                    'vip_builder_achieved_at' => null,
                    'vip_leader_eligible_at' => null,
                    'result_type' => '',
                    'difficulty' => '',
                    'needs_help' => false,
                    'track' => '',
                    'stall_state' => '',
                ];
            }
            $members[$fbo_id]['is_enrolled'] = true;
            $members[$fbo_id]['qualification_source'] = (string) ($row['qualification_source'] ?? '');
            $saved_starting_track = in_array((string) ($row['starting_track_key'] ?? ''), ['starter', 'reactivation'], true)
                ? (string) $row['starting_track_key']
                : '';
            /* Unresolved evidence is intentionally not persisted while imports
             * may still complete. Reporting keeps those enrolled participants
             * visible on the conservative Starter path instead of blank. */
            $members[$fbo_id]['starting_track_key'] = $saved_starting_track !== '' ? $saved_starting_track : 'starter';
            $members[$fbo_id]['starting_track_reason'] = $saved_starting_track !== ''
                ? (string) ($row['starting_track_reason'] ?? '')
                : 'insufficient_history';
            $members[$fbo_id]['starting_track_decided_at'] = $row['starting_track_decided_at'] ?: null;
            $members[$fbo_id]['track'] = $members[$fbo_id]['starting_track_key'];
            $members[$fbo_id]['enrolled_at'] = $row['enrolled_at'] ?: null;
            $members[$fbo_id]['enrollment_event_date'] = (string) ($row['qualification_source'] ?? '') === 'legacy_august_backfill'
                ? ($row['qualifying_period'] ?: null)
                : ($row['enrolled_at'] ? substr((string) $row['enrolled_at'], 0, 10) : null);
            $members[$fbo_id]['qualifying_period'] = $row['qualifying_period'] ?: null;
            $members[$fbo_id]['qualifying_personal_cc'] = isset($row['qualifying_personal_cc'])
                ? (float) $row['qualifying_personal_cc']
                : null;
            $members[$fbo_id]['last_verified_period'] = $row['last_verified_period'] ?: null;
            $members[$fbo_id]['last_verified_personal_cc'] = isset($row['last_verified_personal_cc'])
                ? (float) $row['last_verified_personal_cc']
                : null;
        }
    }

    /* Promotions belong to the Forever ID and survive an open-month reset.
     * Rebuild the same trusted highest metric rank used by the participant
     * workspace, while retaining the first available evidence timestamp for
     * current-level no-start monitoring. */
    $verified_four_cc_sql = "(CASE
        WHEN `metric`.`source_import_id` IS NOT NULL
         AND (`source_import`.`import_id` IS NULL OR `source_import`.`report_kind` NOT IN ('downline', 'four_cc_active'))
            THEN NULL
        ELSE `metric`.`is_4cc_active`
    END)";
    $metric_track_rows = forever_business_los_rows("SELECT `metric`.`fbo_id`,
            MAX(CASE
                WHEN `metric`.`period_month` < COALESCE(`enrollment`.`qualifying_period`, '2026-08-01') THEN 0
                WHEN {$verified_four_cc_sql} = 1
                  OR ({$verified_four_cc_sql} IS NULL AND `metric`.`personal_cc` >= 1 AND `metric`.`total_active_cc` >= 4) THEN 3
                WHEN `metric`.`personal_cc` >= 1 THEN 2
                ELSE 1
            END) AS `verified_highest_track_rank`,
            MIN(CASE
                WHEN `metric`.`period_month` >= COALESCE(`enrollment`.`qualifying_period`, '2026-08-01')
                 AND `metric`.`personal_cc` >= 1 THEN `metric`.`updated_at`
            END) AS `activator_achieved_at`,
            MIN(CASE
                WHEN `metric`.`period_month` >= COALESCE(`enrollment`.`qualifying_period`, '2026-08-01')
                 AND ({$verified_four_cc_sql} = 1
                      OR ({$verified_four_cc_sql} IS NULL AND `metric`.`personal_cc` >= 1 AND `metric`.`total_active_cc` >= 4))
                    THEN `metric`.`updated_at`
            END) AS `builder_achieved_at`,
            MAX(CASE WHEN `metric`.`period_month` = '2026-08-01' AND {$verified_four_cc_sql} = 1 THEN 1 ELSE 0 END) AS `august_is_4cc_active`,
            MIN(CASE WHEN `metric`.`period_month` = '2026-08-01' AND {$verified_four_cc_sql} = 1 THEN `metric`.`updated_at` END) AS `leader_eligible_at`,
            MAX(CASE WHEN `metric`.`period_month` = '{$current_zagreb_period}' THEN `metric`.`personal_cc` END) AS `current_personal_cc`,
            MAX(CASE WHEN `metric`.`period_month` = '{$current_zagreb_period}' THEN `metric`.`total_active_cc` END) AS `current_total_active_cc`,
            MAX(CASE WHEN `metric`.`period_month` = '{$current_zagreb_period}' THEN {$verified_four_cc_sql} END) AS `current_is_4cc_active`
        FROM `forever_business_metrics` `metric`
        LEFT JOIN `forever_business_imports` `source_import` ON `source_import`.`import_id` = `metric`.`source_import_id`
        LEFT JOIN `forever_business_vip_enrollments` `enrollment` ON `enrollment`.`fbo_id` = `metric`.`fbo_id`
        WHERE `metric`.`period_month` <= '{$current_zagreb_period}'
        GROUP BY `metric`.`fbo_id`");
    foreach($metric_track_rows as $row) {
        $fbo_id = (string) ($row['fbo_id'] ?? '');
        if(!isset($members[$fbo_id])) continue;
        $members[$fbo_id]['vip_verified_highest_track_rank'] = max(0, min(3, (int) ($row['verified_highest_track_rank'] ?? 0)));
        $members[$fbo_id]['vip_august_is_4cc_active'] = !empty($row['august_is_4cc_active']) ? 1 : 0;
        $members[$fbo_id]['vip_current_personal_cc'] = max(0.0, (float) ($row['current_personal_cc'] ?? 0));
        $members[$fbo_id]['vip_current_total_active_cc'] = max(0.0, (float) ($row['current_total_active_cc'] ?? 0));
        $members[$fbo_id]['vip_current_is_4cc_active'] = $row['current_is_4cc_active'] === null
            ? null
            : (int) $row['current_is_4cc_active'];
        $members[$fbo_id]['vip_activator_achieved_at'] = $row['activator_achieved_at'] ?: null;
        $members[$fbo_id]['vip_builder_achieved_at'] = $row['builder_achieved_at'] ?: null;
        $members[$fbo_id]['vip_leader_eligible_at'] = $row['leader_eligible_at'] ?: null;
    }

    if($period === $current_zagreb_period) {
        foreach($members as $fbo_id => &$member) {
            if(isset($current_metrics[$fbo_id])) continue;
            $current_metrics[$fbo_id] = [
                'personal_cc' => 0.0,
                'total_active_cc' => 0.0,
                'is_4cc_active' => null,
                'has_activity_data' => true,
                'has_complete_activity_verification' => true,
            ];
            $member = array_merge($member, $current_metrics[$fbo_id]);
        }
        unset($member);
    }

    /* CC, 4 CC and qualification stay deduplicated by Forever ID. Education
     * execution is different: each signed-in FCC account is an independent
     * participant, including accounts that intentionally share one approved ID. */
    $fbo_members = $members;
    $empty_fbo_member = static function(string $fbo_id, int $linked_accounts = 0) use ($period, $current_zagreb_period): array {
        $is_open_month = $period === $current_zagreb_period;
        return [
            'fbo_id' => $fbo_id,
            'name' => 'FBO ' . $fbo_id,
            'title' => '',
            'generation' => null,
            'is_manager' => false,
            'is_in_current_structure' => false,
            'linked_accounts' => $linked_accounts,
            'is_enrolled' => false,
            'qualification_source' => '',
            'starting_track_key' => '',
            'starting_track_reason' => '',
            'starting_track_decided_at' => null,
            'enrolled_at' => null,
            'enrollment_event_date' => null,
            'qualifying_period' => null,
            'qualifying_personal_cc' => null,
            'last_verified_period' => null,
            'last_verified_personal_cc' => null,
            'personal_cc' => $is_open_month ? 0.0 : null,
            'total_active_cc' => $is_open_month ? 0.0 : null,
            'is_4cc_active' => null,
            'has_activity_data' => $is_open_month,
            'has_complete_activity_verification' => $is_open_month,
        ];
    };
    $make_participant = static function(array $fbo_member, int $user_id, string $account_name, bool $account_enabled): array {
        $fbo_name = (string) ($fbo_member['name'] ?? '');
        return array_merge([
            'fbo_id' => '', 'title' => '', 'linked_accounts' => 0, 'is_enrolled' => false,
        ], $fbo_member, [
            'participant_key' => $user_id > 0 ? 'user:' . $user_id : 'fbo:' . (string) ($fbo_member['fbo_id'] ?? ''),
            'user_id' => $user_id > 0 ? $user_id : null,
            'has_fcc_account' => $user_id > 0,
            'account_enabled' => $account_enabled,
            'fbo_name' => $fbo_name,
            'name' => trim($account_name) !== '' ? trim($account_name) : $fbo_name,
            'is_shared_fbo' => (int) ($fbo_member['linked_accounts'] ?? 0) > 1,
            'tasks' => 0,
            'results' => 0,
            'standard_tasks' => 0,
            'quick_tasks' => 0,
            'hard_tasks' => 0,
            'previous_tasks' => 0,
            'previous_results' => 0,
            'previous_standard_tasks' => 0,
            'previous_quick_tasks' => 0,
            'previous_hard_tasks' => 0,
            'started_at' => null,
            'last_task_date' => null,
            'vip_steps_completed' => 0,
            'completed_at' => null,
            'vip_current_track_steps_completed' => 0,
            'vip_current_track_started_at' => null,
            'vip_current_track_last_task_date' => null,
            'vip_current_track_completed_at' => null,
            'vip_current_track_available_at' => null,
            'is_current_track_complete' => false,
            'result_type' => '',
            'difficulty' => '',
            'completion_mode' => '',
            'needs_help' => false,
            'open_help_count' => 0,
            'help_note' => '',
            'help_requested_at' => null,
            'help_action_key' => '',
            'track' => (string) ($fbo_member['track'] ?? $fbo_member['starting_track_key'] ?? ''),
            'stall_state' => '',
            'is_legacy_unattributed' => false,
            'has_outcome_fbo_mismatch' => false,
        ]);
    };

    $account_rows = forever_business_los_rows("SELECT `account`.`user_id`, `account`.`name`, `account`.`status`, `account`.`fbo_id`
        FROM (
            SELECT `user_id`, `name`, `status`, REPLACE(TRIM(COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.foreverId')),
                JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.forever_id')),
                JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.foreverID')),
                ''
            )), '-', '') AS `fbo_id`
            FROM `users`
            WHERE `status` = 1 AND JSON_VALID(`preferences`) = 1
        ) `account`
        WHERE `account`.`fbo_id` REGEXP '^[0-9]{12}$'
        ORDER BY `account`.`user_id` ASC");
    $participants = [];
    $participant_ids_by_fbo = [];
    foreach($account_rows as $row) {
        $user_id = (int) ($row['user_id'] ?? 0);
        $fbo_id = (string) ($row['fbo_id'] ?? '');
        if($user_id <= 0 || !preg_match('/^[0-9]{12}$/D', $fbo_id)) continue;
        if(!isset($fbo_members[$fbo_id])) {
            $fbo_members[$fbo_id] = $empty_fbo_member($fbo_id, $linked_account_counts[$fbo_id] ?? 0);
        }
        $participant = $make_participant($fbo_members[$fbo_id], $user_id, (string) ($row['name'] ?? ''), true);
        $participants[$user_id] = $participant;
        $participant_ids_by_fbo[$fbo_id][] = $user_id;
    }

    /* Keep current/enrolled FBO records with no active FCC account visible as
     * coverage placeholders. They never contribute to participant execution. */
    foreach($fbo_members as $fbo_id => $fbo_member) {
        if(!empty($participant_ids_by_fbo[$fbo_id])) continue;
        $placeholder_key = 'fbo:' . $fbo_id;
        $participants[$placeholder_key] = $make_participant($fbo_member, 0, (string) ($fbo_member['name'] ?? ''), false);
    }
    $participant_keys_by_fbo = [];
    foreach($participants as $participant_key => $participant) {
        $participant_fbo_id = (string) ($participant['fbo_id'] ?? '');
        if($participant_fbo_id !== '') {
            $participant_keys_by_fbo[$participant_fbo_id][] = $participant_key;
        }
    }

    /* The first completed entry-path task is an FBO-wide continuity decision,
     * even when multiple FCC accounts share that Forever ID. Apply it to every
     * linked participant before individual later outcomes raise their track. */
    $started_path_rows = forever_business_los_rows("SELECT `fbo_id`,
            SUBSTRING_INDEX(GROUP_CONCAT(`action_key` ORDER BY `action_date` ASC, `outcome_id` ASC SEPARATOR ','), ',', 1) AS `action_key`
        FROM `forever_business_daily_outcomes`
        WHERE `status` = 'done'
          AND `action_key` REGEXP '^vip26_(starter|reactivation)_d(0[1-9]|[12][0-9]|30)$'
        GROUP BY `fbo_id`");
    foreach($started_path_rows as $row) {
        $started_fbo_id = (string) ($row['fbo_id'] ?? '');
        $started_track = forever_business_los_track_from_action_key((string) ($row['action_key'] ?? ''));
        if(!in_array($started_track, ['starter', 'reactivation'], true)) continue;
        $started_reason = $started_track === 'reactivation' ? 'started_reactivation_path' : 'started_starter_path';
        foreach($participant_keys_by_fbo[$started_fbo_id] ?? [] as $participant_key) {
            $participants[$participant_key]['starting_track_key'] = $started_track;
            $participants[$participant_key]['starting_track_reason'] = $started_reason;
            $participants[$participant_key]['track'] = $started_track;
        }
    }

    $outcome_columns = forever_business_los_table_columns('forever_business_daily_outcomes');
    $result_type_select = isset($outcome_columns['result_type']) ? '`outcome`.`result_type`' : "'' AS `result_type`";
    $difficulty_select = isset($outcome_columns['difficulty']) ? '`outcome`.`difficulty`' : "'' AS `difficulty`";
    $completion_mode_select = isset($outcome_columns['completion_mode']) ? '`outcome`.`completion_mode`' : "'standard' AS `completion_mode`";
    if(!isset($outcome_columns['recorded_by_user_id'], $outcome_columns['result_type'], $outcome_columns['difficulty'], $outcome_columns['needs_help'], $outcome_columns['completion_mode'])) {
        $warnings[] = ['key' => 'missing_structured_outcomes', 'params' => []];
    }

    $outcome_fbo_mismatch_users = [];
    $ensure_outcome_participant = static function(array $row) use (&$participants, &$fbo_members, &$outcome_fbo_mismatch_users, $empty_fbo_member, $make_participant, $linked_account_counts): ?int {
        $user_id = (int) ($row['recorded_by_user_id'] ?? 0);
        if($user_id <= 0) return null;
        $fbo_id = (string) ($row['fbo_id'] ?? '');
        if(isset($participants[$user_id])) {
            $current_fbo_id = (string) ($participants[$user_id]['fbo_id'] ?? '');
            if($fbo_id !== '' && $current_fbo_id !== '' && !hash_equals($current_fbo_id, $fbo_id)) {
                $participants[$user_id]['has_outcome_fbo_mismatch'] = true;
                $outcome_fbo_mismatch_users[$user_id] = true;
            }
            return $user_id;
        }
        if(!isset($fbo_members[$fbo_id])) {
            $fbo_members[$fbo_id] = $empty_fbo_member($fbo_id, $linked_account_counts[$fbo_id] ?? 0);
        }
        $participants[$user_id] = $make_participant(
            $fbo_members[$fbo_id],
            $user_id,
            (string) ($row['account_name'] ?? ('FCC račun #' . $user_id)),
            false
        );
        return $user_id;
    };

    $outcome_summary_rows = forever_business_los_rows("SELECT `outcome`.`recorded_by_user_id`,
            SUBSTRING_INDEX(GROUP_CONCAT(`outcome`.`fbo_id` ORDER BY `outcome`.`outcome_id` DESC SEPARATOR ','), ',', 1) AS `fbo_id`,
            MAX(`account`.`name`) AS `account_name`, MIN(`outcome`.`action_date`) AS `started_at`, MAX(`outcome`.`action_date`) AS `last_task_date`,
            SUM(`outcome`.`action_key` NOT LIKE 'vip26\\_sunday\\_%') AS `vip_steps_completed`,
            SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(
                CASE WHEN `outcome`.`action_key` NOT LIKE 'vip26\\_sunday\\_%' THEN DATE_FORMAT(`outcome`.`action_date`, '%Y-%m-%d') END
                ORDER BY `outcome`.`action_date` ASC, `outcome`.`outcome_id` ASC SEPARATOR ','
            ), ',', 30), ',', -1) AS `completion_at`
        FROM `forever_business_daily_outcomes` `outcome`
        LEFT JOIN `users` `account` ON `account`.`user_id` = `outcome`.`recorded_by_user_id`
        WHERE `outcome`.`status` = 'done'
          AND `outcome`.`action_key` LIKE 'vip26\\_%'
          AND `outcome`.`action_key` <> 'vip26_activator_d01'
          AND `outcome`.`recorded_by_user_id` IS NOT NULL
          AND `outcome`.`recorded_by_user_id` > 0
        GROUP BY `outcome`.`recorded_by_user_id`");
    foreach($outcome_summary_rows as $row) {
        $participant_id = $ensure_outcome_participant($row);
        if($participant_id === null) continue;
        $participants[$participant_id]['started_at'] = $row['started_at'] ?: null;
        $participants[$participant_id]['last_task_date'] = $row['last_task_date'] ?: null;
        $participants[$participant_id]['vip_steps_completed'] = (int) ($row['vip_steps_completed'] ?? 0);
        $participants[$participant_id]['completed_at'] = $participants[$participant_id]['vip_steps_completed'] >= 30
            ? ($row['completion_at'] ?: null)
            : null;
    }

    $latest_outcome_rows = forever_business_los_rows("SELECT `latest`.`recorded_by_user_id`, `latest`.`fbo_id`, `account`.`name` AS `account_name`,
            `latest`.`action_key`, `latest`.`outcome_type`,
            " . (isset($outcome_columns['result_type']) ? '`latest`.`result_type`' : "'' AS `result_type`") . ",
            " . (isset($outcome_columns['difficulty']) ? '`latest`.`difficulty`' : "'' AS `difficulty`") . ",
            " . (isset($outcome_columns['completion_mode']) ? '`latest`.`completion_mode`' : "'standard' AS `completion_mode`") . ",
            " . (isset($outcome_columns['needs_help']) ? '`latest`.`needs_help`' : '0 AS `needs_help`') . "
        FROM `forever_business_daily_outcomes` `latest`
        LEFT JOIN `users` `account` ON `account`.`user_id` = `latest`.`recorded_by_user_id`
        INNER JOIN (
            SELECT `recorded_by_user_id`, MAX(`outcome_id`) AS `outcome_id`
            FROM `forever_business_daily_outcomes`
            WHERE `status` = 'done' AND `action_key` LIKE 'vip26\\_%'
              AND `action_key` <> 'vip26_activator_d01'
              AND `recorded_by_user_id` IS NOT NULL AND `recorded_by_user_id` > 0
            GROUP BY `recorded_by_user_id`
        ) `selected` ON `selected`.`outcome_id` = `latest`.`outcome_id`");
    foreach($latest_outcome_rows as $row) {
        $participant_id = $ensure_outcome_participant($row);
        if($participant_id === null) continue;
        $participants[$participant_id]['result_type'] = trim((string) ($row['result_type'] ?? ''));
        $participants[$participant_id]['difficulty'] = trim((string) ($row['difficulty'] ?? ''));
        $participants[$participant_id]['completion_mode'] = trim((string) ($row['completion_mode'] ?? ''));
        /* This is a compatibility fallback only. The open help table below is
         * authoritative whenever it exists. */
        $participants[$participant_id]['needs_help'] = !empty($row['needs_help']);
        $latest_track = trim((string) ($row['outcome_type'] ?? ''))
            ?: forever_business_los_track_from_action_key((string) $row['action_key']);
        $participants[$participant_id]['track'] = $latest_track;
    }

    /* Lifetime progress remains available in vip_steps_completed. The active
     * 30-step cycle is counted independently per track so a promotion starts
     * at day one without erasing any earlier outcome. */
    $sequence_position_sql = isset($outcome_columns['sequence_position'])
        ? "CASE
                WHEN `outcome`.`sequence_position` BETWEEN 1 AND 30 THEN `outcome`.`sequence_position`
                WHEN `outcome`.`action_key` = 'vip26_activator_d01_biolink' THEN 1
                ELSE CAST(RIGHT(`outcome`.`action_key`, 2) AS UNSIGNED)
            END"
        : "CASE
                WHEN `outcome`.`action_key` = 'vip26_activator_d01_biolink' THEN 1
                ELSE CAST(RIGHT(`outcome`.`action_key`, 2) AS UNSIGNED)
            END";
    $track_progress_rows = forever_business_los_rows("SELECT `track_progress`.`recorded_by_user_id`,
            SUBSTRING_INDEX(GROUP_CONCAT(`track_progress`.`fbo_id` ORDER BY `track_progress`.`outcome_id` DESC SEPARATOR ','), ',', 1) AS `fbo_id`,
            MAX(`account`.`name`) AS `account_name`, `track_progress`.`track_key`, MAX(`track_progress`.`sequence_position`) AS `steps_completed`,
            MIN(`track_progress`.`action_date`) AS `started_at`, MAX(`track_progress`.`action_date`) AS `last_task_date`,
            MAX(CASE WHEN `track_progress`.`sequence_position` = 30 THEN `track_progress`.`action_date` END) AS `completion_at`
        FROM (
            SELECT `outcome`.`outcome_id`, `outcome`.`recorded_by_user_id`, `outcome`.`fbo_id`, `outcome`.`action_date`,
                CASE
                    WHEN `outcome`.`action_key` LIKE 'vip26_starter_d%' THEN 'starter'
                    WHEN `outcome`.`action_key` LIKE 'vip26_reactivation_d%' THEN 'reactivation'
                    WHEN `outcome`.`action_key` LIKE 'vip26_activator_d%' THEN 'activator'
                    WHEN `outcome`.`action_key` LIKE 'vip26_builder_d%' THEN 'builder'
                    WHEN `outcome`.`action_key` LIKE 'vip26_leader_d%' THEN 'leader'
                    ELSE 'other'
                END AS `track_key`,
                {$sequence_position_sql} AS `sequence_position`
            FROM `forever_business_daily_outcomes` `outcome`
            WHERE `outcome`.`status` = 'done'
              AND `outcome`.`recorded_by_user_id` IS NOT NULL
              AND `outcome`.`recorded_by_user_id` > 0
              AND (
                  `outcome`.`action_key` REGEXP '^vip26_(starter|reactivation|builder|leader)_d(0[1-9]|[12][0-9]|30)$'
                  OR `outcome`.`action_key` REGEXP '^vip26_activator_d(0[2-9]|[12][0-9]|30)$'
                  OR `outcome`.`action_key` = 'vip26_activator_d01_biolink'
              )
        ) `track_progress`
        LEFT JOIN `users` `account` ON `account`.`user_id` = `track_progress`.`recorded_by_user_id`
        GROUP BY `track_progress`.`recorded_by_user_id`, `track_progress`.`track_key`");
    $track_progress_by_user = [];
    foreach($track_progress_rows as $row) {
        $participant_id = $ensure_outcome_participant($row);
        if($participant_id === null) continue;
        $track_key = (string) ($row['track_key'] ?? '');
        if(!in_array($track_key, ['starter', 'reactivation', 'activator', 'builder', 'leader'], true)) continue;
        $steps_completed = max(0, (int) ($row['steps_completed'] ?? 0));
        $track_progress_by_user[$participant_id][$track_key] = [
            'steps_completed' => $steps_completed,
            'started_at' => $row['started_at'] ?: null,
            'last_task_date' => $row['last_task_date'] ?: null,
            'completed_at' => $steps_completed >= 30 ? ($row['completion_at'] ?: null) : null,
        ];
    }

    $track_definitions = forever_business_vip_track_definitions();
    $launch_date_string = '2026-09-01';
    foreach($participants as $participant_key => &$participant) {
        $participant_id = (int) ($participant['user_id'] ?? 0);
        $highest_track_rank = 0;
        $highest_nonleader_track_rank = 0;
        foreach($track_progress_by_user[$participant_id] ?? [] as $completed_track_key => $completed_track_progress) {
            if((int) ($completed_track_progress['steps_completed'] ?? 0) <= 0) continue;
            $completed_rank = (int) ($track_definitions[$completed_track_key]['rank'] ?? 0);
            $highest_track_rank = max($highest_track_rank, $completed_rank);
            if($completed_track_key !== 'leader') {
                $highest_nonleader_track_rank = max($highest_nonleader_track_rank, $completed_rank);
            }
        }
        $participant['vip_highest_track_rank'] = $highest_track_rank;
        $participant['vip_highest_nonleader_track_rank'] = $highest_nonleader_track_rank;
        $participant['track'] = forever_business_los_resolve_current_track(
            $participant,
            $admin_user_id,
            $admin_root_fbo_id,
            $admin_is_platform_admin
        );

        $current_track_progress = (array) ($track_progress_by_user[$participant_id][$participant['track']] ?? []);
        $participant['vip_current_track_steps_completed'] = max(0, (int) ($current_track_progress['steps_completed'] ?? 0));
        $participant['vip_current_track_started_at'] = $current_track_progress['started_at'] ?? null;
        $participant['vip_current_track_last_task_date'] = $current_track_progress['last_task_date'] ?? null;
        $participant['vip_current_track_completed_at'] = $current_track_progress['completed_at'] ?? null;
        $participant['is_current_track_complete'] = $participant['vip_current_track_steps_completed'] >= 30;

        $available_at = match($participant['track']) {
            'activator' => $participant['vip_activator_achieved_at'] ?? null,
            'builder' => $participant['vip_builder_achieved_at'] ?? null,
            'leader' => $participant['vip_leader_eligible_at'] ?? null,
            default => $participant['starting_track_decided_at'] ?? ($participant['enrolled_at'] ?? null),
        };
        $available_at = $participant['vip_current_track_started_at'] ?: $available_at ?: ($participant['enrolled_at'] ?? null);
        if($available_at && substr((string) $available_at, 0, 10) < $launch_date_string) {
            $available_at = $launch_date_string;
        }
        $participant['vip_current_track_available_at'] = $available_at ?: null;
    }
    unset($participant);

    $outcome_rows = forever_business_los_rows("SELECT `outcome`.`recorded_by_user_id`, `outcome`.`fbo_id`, `account`.`name` AS `account_name`,
            `outcome`.`action_date`, `outcome`.`core_key`, `outcome`.`action_key`, `outcome`.`outcome_type`,
            {$result_type_select}, {$difficulty_select}, {$completion_mode_select}, `outcome`.`outcome_count`
        FROM `forever_business_daily_outcomes` `outcome`
        LEFT JOIN `users` `account` ON `account`.`user_id` = `outcome`.`recorded_by_user_id`
        WHERE `outcome`.`status` = 'done'
          AND `outcome`.`action_key` LIKE 'vip26\\_%'
          AND `outcome`.`action_key` <> 'vip26_activator_d01'
          AND `outcome`.`recorded_by_user_id` IS NOT NULL
          AND `outcome`.`recorded_by_user_id` > 0
          AND `outcome`.`action_date` BETWEEN '{$previous_start_string}' AND '{$current_end_string}'
        ORDER BY `outcome`.`action_date` ASC, `outcome`.`outcome_id` ASC");

    $daily = [];
    for($day = $current_start; $day <= $today; $day = $day->modify('+1 day')) {
        $date = $day->format('Y-m-d');
        $daily[$date] = ['tasks' => 0, 'results' => 0, 'standard' => 0, 'quick' => 0];
    }
    $result_type_counts = [];
    $core_counts = [];
    $track_counts = [];
    $difficulty_counts = [];
    $completion_mode_counts = [];
    foreach($outcome_rows as $row) {
        $participant_id = $ensure_outcome_participant($row);
        if($participant_id === null) continue;
        $date = (string) $row['action_date'];
        $result_count = max(0, (int) ($row['outcome_count'] ?? 0));
        $difficulty = trim((string) ($row['difficulty'] ?? '')) ?: 'unspecified';
        $completion_mode = trim((string) ($row['completion_mode'] ?? '')) ?: 'standard';

        if($date >= $current_start_string && $date <= $current_end_string) {
            $participants[$participant_id]['tasks']++;
            $participants[$participant_id]['results'] += $result_count;
            $participants[$participant_id]['standard_tasks'] += $completion_mode === 'standard' ? 1 : 0;
            $participants[$participant_id]['quick_tasks'] += $completion_mode === 'quick' ? 1 : 0;
            $participants[$participant_id]['hard_tasks'] += $difficulty === 'hard' ? 1 : 0;
            $daily[$date]['tasks']++;
            $daily[$date]['results'] += $result_count;
            $daily[$date]['standard'] += $completion_mode === 'standard' ? 1 : 0;
            $daily[$date]['quick'] += $completion_mode === 'quick' ? 1 : 0;

            $result_type = trim((string) ($row['result_type'] ?? '')) ?: 'unspecified';
            $core = trim((string) ($row['core_key'] ?? '')) ?: 'unspecified';
            /* outcome_type is the immutable server-derived program track. Each
             * distribution counts comparable completed tasks, not raw targets. */
            $track = trim((string) ($row['outcome_type'] ?? '')) ?: forever_business_los_track_from_action_key((string) $row['action_key']);
            $result_type_counts[$result_type] = ($result_type_counts[$result_type] ?? 0) + 1;
            $core_counts[$core] = ($core_counts[$core] ?? 0) + 1;
            $track_counts[$track] = ($track_counts[$track] ?? 0) + 1;
            $difficulty_counts[$difficulty] = ($difficulty_counts[$difficulty] ?? 0) + 1;
            $completion_mode_counts[$completion_mode] = ($completion_mode_counts[$completion_mode] ?? 0) + 1;
        } elseif($date >= $previous_start_string && $date <= $previous_end_string) {
            $participants[$participant_id]['previous_tasks']++;
            $participants[$participant_id]['previous_results'] += $result_count;
            $participants[$participant_id]['previous_standard_tasks'] += $completion_mode === 'standard' ? 1 : 0;
            $participants[$participant_id]['previous_quick_tasks'] += $completion_mode === 'quick' ? 1 : 0;
            $participants[$participant_id]['previous_hard_tasks'] += $difficulty === 'hard' ? 1 : 0;
        }
    }

    $help_columns = forever_business_los_table_columns('forever_business_vip_help_requests');
    if(!empty($help_columns)) {
        /* Only help for the live track is actionable. Requests from a level
         * completed before promotion remain in the audit table but must not
         * keep the participant in the current mentor queue. */
        foreach($participants as &$participant) {
            $participant['needs_help'] = false;
            $participant['open_help_count'] = 0;
            $participant['help_note'] = '';
            $participant['help_requested_at'] = null;
            $participant['help_action_key'] = '';
        }
        unset($participant);
        $open_help_rows = forever_business_los_rows("SELECT `request`.`request_id`, `request`.`user_id` AS `recorded_by_user_id`, `request`.`fbo_id`,
                `account`.`name` AS `account_name`, `request`.`action_key`, `request`.`track_key`, `request`.`difficulty`, `request`.`note`,
                `request`.`request_date`, `request`.`created_at`, `request`.`updated_at`
            FROM `forever_business_vip_help_requests` `request`
            LEFT JOIN `users` `account` ON `account`.`user_id` = `request`.`user_id`
            WHERE `request`.`status` = 'open'
              AND `request`.`action_key` <> 'vip26_activator_d01'
            ORDER BY `request`.`user_id` ASC, `request`.`updated_at` DESC, `request`.`request_id` DESC");
        foreach($open_help_rows as $row) {
            $known_participant = isset($participants[(int) ($row['recorded_by_user_id'] ?? 0)]);
            $participant_id = $ensure_outcome_participant($row);
            if($participant_id === null) continue;
            if(!$known_participant) {
                $participants[$participant_id]['vip_highest_track_rank'] = 0;
                $participants[$participant_id]['vip_highest_nonleader_track_rank'] = 0;
                $participants[$participant_id]['track'] = forever_business_los_resolve_current_track(
                    $participants[$participant_id],
                    $admin_user_id,
                    $admin_root_fbo_id,
                    $admin_is_platform_admin
                );
            }
            $request_track = trim((string) ($row['track_key'] ?? ''));
            if(!in_array($request_track, ['starter', 'reactivation', 'activator', 'builder', 'leader'], true)) {
                $request_track = forever_business_los_track_from_action_key((string) ($row['action_key'] ?? ''));
            }
            if(!hash_equals((string) ($participants[$participant_id]['track'] ?? ''), $request_track)) {
                continue;
            }
            $participants[$participant_id]['needs_help'] = true;
            $participants[$participant_id]['open_help_count']++;
            if($participants[$participant_id]['help_action_key'] === '') {
                $participants[$participant_id]['difficulty'] = trim((string) ($row['difficulty'] ?? '')) ?: 'hard';
                $participants[$participant_id]['help_note'] = trim((string) ($row['note'] ?? ''));
                $participants[$participant_id]['help_requested_at'] = $row['updated_at'] ?: ($row['created_at'] ?: ($row['request_date'] ?? null));
                $participants[$participant_id]['help_action_key'] = trim((string) ($row['action_key'] ?? ''));
            }
        }
    } else {
        $warnings[] = 'Tablica otvorenih VIP zahtjeva za pomoć nije dostupna; LOS privremeno prikazuje samo povijesnu oznaku sa zadnjeg dovršenog koraka.';
    }

    $legacy_outcome_row = forever_business_los_row("SELECT COUNT(*) AS `outcomes`, COUNT(DISTINCT `fbo_id`) AS `fbo_ids`
        FROM `forever_business_daily_outcomes`
        WHERE `status` = 'done' AND `action_key` LIKE 'vip26\\_%'
          AND (`recorded_by_user_id` IS NULL OR `recorded_by_user_id` = 0)");
    $legacy_unattributed_outcomes = (int) ($legacy_outcome_row['outcomes'] ?? 0);
    if($legacy_unattributed_outcomes > 0) {
        $warnings[] = $legacy_unattributed_outcomes . ' povijesnih VIP dovršetaka nema jednoznačan FCC račun i zato nije pripisano nijednom polazniku; ostaju u auditu kvalitete podataka.';
    }

    $members = $participants;

    /* Preserve the permanent project cohort independently of today's FLP tree.
     * Historical member rows that were never enrolled and never started remain
     * outside the LOS; current members always remain visible. */
    $members = array_filter($members, static fn(array $member): bool =>
        !empty($member['is_in_current_structure'])
        || !empty($member['is_enrolled'])
        || $member['started_at'] !== null
    );
    $current_structure_members = array_filter($members, static fn(array $member): bool => !empty($member['is_in_current_structure']));
    /* CC metrics remain one row per FBO even when two participant accounts use
     * that same ID. Participant keys must never be used to intersect CC maps. */
    $current_structure_fbos = array_filter($fbo_members, static fn(array $member): bool => !empty($member['is_in_current_structure']));
    $current_structure_ids = array_fill_keys(array_keys($current_structure_fbos), true);
    $current_structure_metrics = array_intersect_key($current_metrics, $current_structure_ids);
    $previous_structure_metrics = array_intersect_key($previous_metrics, $current_structure_ids);

    $launch_date = new \DateTimeImmutable('2026-09-01', $timezone);
    $launch_with_grace = $launch_date->modify('+3 days');
    foreach($members as &$member) {
        if($member['needs_help']) {
            $member['stall_state'] = 'needs_help';
            continue;
        }
        if(empty($member['account_enabled'])) continue;
        if(!$member['is_enrolled']) continue;
        if(!empty($member['is_current_track_complete'])) continue;
        if($today < $launch_with_grace) continue;

        $current_track_available = !empty($member['vip_current_track_available_at'])
            ? new \DateTimeImmutable(substr((string) $member['vip_current_track_available_at'], 0, 10), $timezone)
            : null;
        if((int) ($member['vip_current_track_steps_completed'] ?? 0) === 0 && $current_track_available) {
            $eligible_after_track_open = $current_track_available->modify('+3 days');
            $eligible_after = $eligible_after_track_open > $launch_with_grace ? $eligible_after_track_open : $launch_with_grace;
            if($today >= $eligible_after) {
                $member['stall_state'] = 'no_start_3d';
            }
        } elseif(!empty($member['vip_current_track_last_task_date'])) {
            $last_task = new \DateTimeImmutable((string) $member['vip_current_track_last_task_date'], $timezone);
            if((int) $last_task->diff($today)->format('%r%a') >= 7) {
                $member['stall_state'] = 'inactive_7d';
            }
        }
    }
    unset($member);

    $current_qualified = count(array_filter($current_structure_metrics, static fn(array $metric): bool => $metric['personal_cc'] !== null && $metric['personal_cc'] >= 0.33));
    $previous_qualified = count(array_filter($previous_structure_metrics, static fn(array $metric): bool => $metric['personal_cc'] !== null && $metric['personal_cc'] >= 0.33));
    $official_four_cc = count(array_filter($current_structure_metrics, static fn(array $metric): bool => $metric['is_4cc_active'] !== null && (int) $metric['is_4cc_active'] === 1));
    $previous_official_four_cc = count(array_filter($previous_structure_metrics, static fn(array $metric): bool => $metric['is_4cc_active'] !== null && (int) $metric['is_4cc_active'] === 1));
    $effective_four_cc = count(array_filter($current_structure_metrics, 'forever_business_los_effective_four_cc'));
    $previous_effective_four_cc = count(array_filter($previous_structure_metrics, 'forever_business_los_effective_four_cc'));

    $enrolled = array_values(array_filter($members, static fn(array $member): bool => !empty($member['has_fcc_account']) && $member['is_enrolled']));
    $lifetime_started = array_values(array_filter($members, static fn(array $member): bool => $member['started_at'] !== null));
    $started = array_values(array_filter($members, static fn(array $member): bool => $member['vip_current_track_started_at'] !== null));
    $completed = array_values(array_filter($members, static fn(array $member): bool => !empty($member['is_current_track_complete'])));
    $active = array_values(array_filter($lifetime_started, static fn(array $member): bool => $member['tasks'] > 0));
    $previous_active = array_values(array_filter($lifetime_started, static fn(array $member): bool => $member['previous_tasks'] > 0));
    $started_without_enrollment = array_values(array_filter($lifetime_started, static fn(array $member): bool => !$member['is_enrolled']));
    $tasks_current = array_sum(array_column($members, 'tasks'));
    $tasks_previous = array_sum(array_column($members, 'previous_tasks'));
    $results_current = array_sum(array_column($members, 'results'));
    $results_previous = array_sum(array_column($members, 'previous_results'));
    $standard_tasks_current = array_sum(array_column($members, 'standard_tasks'));
    $standard_tasks_previous = array_sum(array_column($members, 'previous_standard_tasks'));
    $quick_tasks_current = array_sum(array_column($members, 'quick_tasks'));
    $quick_tasks_previous = array_sum(array_column($members, 'previous_quick_tasks'));
    $hard_tasks_current = array_sum(array_column($members, 'hard_tasks'));
    $hard_tasks_previous = array_sum(array_column($members, 'previous_hard_tasks'));
    $open_help = array_values(array_filter($members, static fn(array $member): bool => !empty($member['needs_help'])));
    $open_help_requests_current = array_sum(array_column($open_help, 'open_help_count'));
    $enrolled_current = count(array_filter($enrolled, static fn(array $member): bool => (string) $member['enrollment_event_date'] >= $current_start_string && (string) $member['enrollment_event_date'] <= $current_end_string));
    $enrolled_previous = count(array_filter($enrolled, static fn(array $member): bool => (string) $member['enrollment_event_date'] >= $previous_start_string && (string) $member['enrollment_event_date'] <= $previous_end_string));
    $started_current = count(array_filter($started, static fn(array $member): bool => (string) ($member['vip_current_track_started_at'] ?? '') >= $current_start_string && (string) ($member['vip_current_track_started_at'] ?? '') <= $current_end_string));
    $started_previous = count(array_filter($started, static fn(array $member): bool => (string) ($member['vip_current_track_started_at'] ?? '') >= $previous_start_string && (string) ($member['vip_current_track_started_at'] ?? '') <= $previous_end_string));
    $completed_current = count(array_filter($completed, static fn(array $member): bool => (string) ($member['vip_current_track_completed_at'] ?? '') >= $current_start_string && (string) ($member['vip_current_track_completed_at'] ?? '') <= $current_end_string));
    $completed_previous = count(array_filter($completed, static fn(array $member): bool => (string) ($member['vip_current_track_completed_at'] ?? '') >= $previous_start_string && (string) ($member['vip_current_track_completed_at'] ?? '') <= $previous_end_string));

    $cc_rows = [];
    $has_official_global_snapshot = false;
    if($admin_root_fbo_id === '') {
        $warnings[] = ['key' => 'invalid_admin_fbo', 'params' => []];
    } elseif(!empty(forever_business_los_table_columns('forever_business_total_cc_snapshots'))) {
        $root_filter = " AND `fbo_id` = '{$admin_root_fbo_id}'";
        $snapshot_rows = forever_business_los_rows("SELECT `period_month`, `total_cc`, `is_closed`, `source_note`, `captured_at`
            FROM `forever_business_total_cc_snapshots`
            WHERE `country_scope` = 'GLOBAL' AND `period_month` <= '{$period}'{$root_filter}
            ORDER BY `period_month` DESC, `captured_at` DESC
            LIMIT 32");
        $seen_periods = [];
        foreach($snapshot_rows as $row) {
            $row_period = (string) $row['period_month'];
            if(isset($seen_periods[$row_period])) continue;
            $seen_periods[$row_period] = true;
            $row['is_official_snapshot'] = 1;
            $cc_rows[] = $row;
            if(count($cc_rows) >= 8) break;
        }
        $cc_rows = array_reverse($cc_rows);
    }
    if(empty($cc_rows)) {
        $cc_rows = array_reverse(forever_business_los_rows("SELECT `metric`.`period_month`, COALESCE(SUM(`metric`.`personal_cc`), 0) AS `total_cc`,
                (`metric`.`period_month` < '{$current_zagreb_period}') AS `is_closed`,
                'FCC zbroj osobnih CC' AS `source_note`
            FROM `forever_business_metrics` `metric`
            INNER JOIN `forever_business_members` `member`
              ON `member`.`fbo_id` = `metric`.`fbo_id`
             AND `member`.`is_in_current_structure` = 1
            WHERE `metric`.`period_month` <= '{$period}'
            GROUP BY `metric`.`period_month`
            ORDER BY `metric`.`period_month` DESC
            LIMIT 8"));
        foreach($cc_rows as &$cc_row) {
            $cc_row['is_official_snapshot'] = 0;
        }
        unset($cc_row);
    }
    if($period === $current_zagreb_period
        && !array_filter($cc_rows, static fn(array $row): bool => (string) ($row['period_month'] ?? '') === $period)) {
        $current_fcc_total_cc = array_sum(array_map(
            static fn(array $metric): float => (float) ($metric['personal_cc'] ?? 0),
            $current_structure_metrics
        ));
        $cc_rows = array_slice($cc_rows, -7);
        $cc_rows[] = [
            'period_month' => $period,
            'total_cc' => $current_fcc_total_cc,
            'is_closed' => 0,
            'source_note' => $current_fcc_total_cc > 0
                ? 'FCC zbroj osobnih CC dok službeni Global Total CC nije dostupan'
                : 'Otvoreni mjesec bez sinkroniziranih narudžbi',
            'captured_at' => null,
            'is_official_snapshot' => 0,
        ];
    }
    $current_cc_row = end($cc_rows) ?: [];
    reset($cc_rows);
    $has_official_global_snapshot = !empty($current_cc_row['is_official_snapshot']);
    $global_total_cc = (float) ($current_cc_row['total_cc'] ?? 0);
    $previous_cc_row = count($cc_rows) > 1 ? $cc_rows[count($cc_rows) - 2] : [];
    $previous_total_cc = isset($previous_cc_row['total_cc']) ? (float) $previous_cc_row['total_cc'] : null;
    $global_change_cc = $previous_total_cc === null ? null : $global_total_cc - $previous_total_cc;
    $global_change_percent = $previous_total_cc !== null && $previous_total_cc > 0
        ? round(($global_change_cc / $previous_total_cc) * 100, 1)
        : null;
    $closed_values = array_map(static fn(array $row): float => (float) $row['total_cc'], array_slice(array_values(array_filter($cc_rows, static fn(array $row): bool => !empty($row['is_closed']))), -6));
    $closed_six_average = !empty($closed_values) ? array_sum($closed_values) / count($closed_values) : 0.0;

    $top_performers = array_values($members);
    usort($top_performers, static function(array $left, array $right): int {
        foreach(['tasks', 'standard_tasks', 'quick_tasks'] as $key) {
            if($left[$key] != $right[$key]) return $right[$key] <=> $left[$key];
        }
        return strcasecmp($left['name'], $right['name']);
    });
    $top_performers = array_slice(array_values(array_filter($top_performers, static fn(array $member): bool => $member['tasks'] > 0)), 0, 12);

    /* Backward-compatible payload key, but no raw outcome_count ranking: unlike
     * contacts, videos and trainings, full/quick task completions are comparable. */
    $top_results = array_values($members);
    usort($top_results, static function(array $left, array $right): int {
        foreach(['standard_tasks', 'tasks', 'quick_tasks'] as $key) {
            if($left[$key] != $right[$key]) return $right[$key] <=> $left[$key];
        }
        return strcasecmp($left['name'], $right['name']);
    });
    $top_results = array_slice(array_values(array_filter($top_results, static fn(array $member): bool => $member['tasks'] > 0)), 0, 12);

    $attention_queue = array_values(array_filter($members, static fn(array $member): bool => $member['stall_state'] !== ''));
    $priority = ['needs_help' => 0, 'no_start_3d' => 1, 'inactive_7d' => 2];
    usort($attention_queue, static function(array $left, array $right) use ($priority): int {
        $left_date = $left['stall_state'] === 'needs_help' ? ($left['help_requested_at'] ?? '') : ($left['vip_current_track_last_task_date'] ?? '');
        $right_date = $right['stall_state'] === 'needs_help' ? ($right['help_requested_at'] ?? '') : ($right['vip_current_track_last_task_date'] ?? '');
        return [$priority[$left['stall_state']] ?? 9, $left_date, $left['name']]
            <=> [$priority[$right['stall_state']] ?? 9, $right_date, $right['name']];
    });

    $member_table = array_values($members);
    usort($member_table, static function(array $left, array $right) use ($priority): int {
        return [isset($priority[$left['stall_state']]) ? $priority[$left['stall_state']] : 9, -$left['tasks'], $left['name']]
            <=> [isset($priority[$right['stall_state']]) ? $priority[$right['stall_state']] : 9, -$right['tasks'], $right['name']];
    });

    $missing_linkage_fbos = array_filter($current_structure_fbos, static fn(array $member): bool => (int) $member['linked_accounts'] === 0);
    $unique_linked_fbos = array_filter($current_structure_fbos, static fn(array $member): bool => $member['linked_accounts'] === 1);
    $shared_fbos = array_filter($current_structure_fbos, static fn(array $member): bool => (int) $member['linked_accounts'] > 1);
    $shared_accounts = array_sum(array_map(static fn(array $member): int => (int) $member['linked_accounts'], $shared_fbos));
    $linked_current_participants = array_filter($current_structure_members, static fn(array $member): bool => !empty($member['has_fcc_account']) && !empty($member['account_enabled']));
    $enrolled_fbos = array_filter($fbo_members, static fn(array $member): bool => !empty($member['is_enrolled']));
    $enrolled_without_active_linkage = array_filter($enrolled_fbos, static fn(array $member): bool => (int) ($member['linked_accounts'] ?? 0) === 0);
    $enrolled_outside_current_structure = array_filter($enrolled_fbos, static fn(array $member): bool => empty($member['is_in_current_structure']));

    $email_queue = [];
    if(!empty(forever_business_los_table_columns('forever_business_vip_email_deliveries'))) {
        $email_queue_row = forever_business_los_row("SELECT COUNT(*) AS `total`,
                SUM(`status` = 'pending') AS `pending`,
                SUM(`status` = 'sending') AS `sending`,
                SUM(`status` = 'failed' AND `attempts` < 5) AS `retryable_failed`,
                SUM(`status` = 'failed' AND `attempts` >= 5) AS `exhausted_failed`,
                SUM(`status` = 'sent') AS `accepted`,
                SUM(`status` = 'superseded') AS `superseded`,
                MAX(`updated_at`) AS `last_updated_at`
            FROM `forever_business_vip_email_deliveries`");
        $email_queue = [
            'total' => (int) ($email_queue_row['total'] ?? 0),
            'pending' => (int) ($email_queue_row['pending'] ?? 0),
            'sending' => (int) ($email_queue_row['sending'] ?? 0),
            'retryable_failed' => (int) ($email_queue_row['retryable_failed'] ?? 0),
            'exhausted_failed' => (int) ($email_queue_row['exhausted_failed'] ?? 0),
            'accepted' => (int) ($email_queue_row['accepted'] ?? 0),
            'superseded' => (int) ($email_queue_row['superseded'] ?? 0),
            'last_updated_at' => $email_queue_row['last_updated_at'] ?? null,
        ];
    }

    return [
        'window_days' => $window_days,
        'period' => $period,
        'periods' => $periods,
        'window' => [
            'current_start' => $current_start_string,
            'current_end' => $current_end_string,
            'previous_start' => $previous_start_string,
            'previous_end' => $previous_end_string,
        ],
        'kpis' => [
            'qualified' => forever_business_los_delta($current_qualified, $previous_qualified),
            'enrolled' => array_merge(forever_business_los_delta($enrolled_current, $enrolled_previous), ['total' => count($enrolled)]),
            'started' => array_merge(forever_business_los_delta($started_current, $started_previous), ['total' => count($started)]),
            'completed' => array_merge(forever_business_los_delta($completed_current, $completed_previous), ['total' => count($completed)]),
            'active' => forever_business_los_delta(count($active), count($previous_active)),
            'tasks' => forever_business_los_delta($tasks_current, $tasks_previous),
            'results' => forever_business_los_delta($results_current, $results_previous),
            'standard_tasks' => forever_business_los_delta($standard_tasks_current, $standard_tasks_previous),
            'quick_tasks' => forever_business_los_delta($quick_tasks_current, $quick_tasks_previous),
            'hard_tasks' => forever_business_los_delta($hard_tasks_current, $hard_tasks_previous),
            'open_help' => forever_business_los_delta($open_help_requests_current, 0),
            'official_four_cc' => forever_business_los_delta($official_four_cc, $previous_official_four_cc),
            'effective_four_cc' => forever_business_los_delta($effective_four_cc, $previous_effective_four_cc),
        ],
        'global' => [
            'period' => $current_cc_row['period_month'] ?? null,
            'previous_period' => $previous_cc_row['period_month'] ?? null,
            'total_cc' => $global_total_cc,
            'previous_total_cc' => $previous_total_cc,
            'change_cc' => $global_change_cc,
            'change_percent' => $global_change_percent,
            'goal_cc' => 1000.0,
            'gap_cc' => max(0, 1000.0 - $global_total_cc),
            'progress_percent' => min(100, round(($global_total_cc / 1000.0) * 100, 1)),
            'closed_six_average_cc' => round($closed_six_average, 3),
            'closed_sample_count' => count($closed_values),
            'multiplier_to_goal' => $closed_six_average > 0 ? round(1000.0 / $closed_six_average, 2) : null,
            'is_closed' => !empty($current_cc_row['is_closed']),
            'is_official_snapshot' => $has_official_global_snapshot,
            'source' => $has_official_global_snapshot
                ? (trim((string) ($current_cc_row['source_note'] ?? '')) ?: 'FLP360 Global Total CC')
                : '',
            'source_key' => $has_official_global_snapshot ? null : 'fallback_personal_cc',
            'root_fbo_id' => $admin_root_fbo_id,
        ],
        'linkage_funnel' => [
            ['key' => 'structure', 'value' => count($current_structure_fbos)],
            ['key' => 'qualified', 'value' => $current_qualified],
            ['key' => 'linked', 'value' => count($linked_current_participants)],
            ['key' => 'enrolled', 'value' => count($enrolled)],
            ['key' => 'started', 'value' => count($started)],
            ['key' => 'completed', 'value' => count($completed)],
            ['key' => 'active', 'value' => count($active)],
        ],
        'charts' => [
            'daily' => [
                'labels' => array_keys($daily),
                'tasks' => array_column($daily, 'tasks'),
                'results' => array_column($daily, 'results'),
                'standard' => array_column($daily, 'standard'),
                'quick' => array_column($daily, 'quick'),
            ],
            'cc' => [
                'labels' => array_column($cc_rows, 'period_month'),
                'values' => array_map('floatval', array_column($cc_rows, 'total_cc')),
                'closed' => array_map('intval', array_column($cc_rows, 'is_closed')),
            ],
            'outcomes' => [
                'result_type' => forever_business_los_distribution($result_type_counts),
                'core' => forever_business_los_distribution($core_counts),
                'track' => forever_business_los_distribution($track_counts),
                'difficulty' => forever_business_los_distribution($difficulty_counts),
                'completion_mode' => forever_business_los_distribution($completion_mode_counts),
            ],
        ],
        'top_performers' => $top_performers,
        'top_results' => $top_results,
        'attention_queue' => $attention_queue,
        'members' => $member_table,
        'email_queue' => $email_queue,
        'data_quality' => [
            'missing_linkage' => count($missing_linkage_fbos),
            /* Compatibility alias: shared linkage is valid and informational,
             * never an access/data error. */
            'duplicate_linkage' => count($shared_fbos),
            'shared_fbo_ids' => count($shared_fbos),
            'shared_accounts' => $shared_accounts,
            'enrolled_without_unique_linkage' => count($enrolled_without_active_linkage),
            'enrolled_without_active_linkage' => count($enrolled_without_active_linkage),
            'enrolled_outside_current_structure' => count($enrolled_outside_current_structure),
            'started_without_enrollment' => count($started_without_enrollment),
            'unattributed_outcomes' => $legacy_unattributed_outcomes,
            'outcome_fbo_mismatch_accounts' => count($outcome_fbo_mismatch_users),
        ],
        'warnings' => $warnings,
        'generated_at' => (new \DateTimeImmutable('now', $timezone))->format(DATE_ATOM),
    ];
}

/* /Custom code: FC-2026-08-21 */
