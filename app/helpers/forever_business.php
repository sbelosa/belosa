<?php
/* Custom code: FC-2026-08-13: Forever business import, hierarchy and access layer */

defined('ALTUMCODE') || die();

function forever_business_ensure_tables(): void {
    static $ready = false;

    if($ready) {
        return;
    }

    /* DDL is a deployment concern, not work that hundreds of launch-page
     * reloads should repeat. A versioned durable marker makes the normal path
     * one indexed SELECT; a database advisory lock serializes the one request
     * that provisions a new version. Bump this key whenever schema DDL below
     * changes. */
    $runtime_schema_key = 'forever_business_runtime_schema_v20260831_4';
    $escaped_runtime_schema_key = database()->real_escape_string($runtime_schema_key);
    try {
        $runtime_schema_result = database()->query("SELECT `completed_at`
            FROM `forever_business_schema_migrations`
            WHERE `migration_key` = '{$escaped_runtime_schema_key}' AND `completed_at` IS NOT NULL
            LIMIT 1");
        if($runtime_schema_result && $runtime_schema_result->fetch_assoc()) {
            $ready = true;
            return;
        }
    } catch(\Throwable $exception) {
        /* Fresh databases do not have the marker table yet. */
    }

    $schema_lock_name = 'fcc_forever_schema_v20260831_4';
    $schema_lock_result = database()->query("SELECT GET_LOCK('{$schema_lock_name}', 20) AS `acquired`");
    $schema_lock_row = $schema_lock_result ? $schema_lock_result->fetch_assoc() : null;
    if((int) ($schema_lock_row['acquired'] ?? 0) !== 1) {
        throw new \RuntimeException('Forever Business schema provisioning is busy; try again shortly.');
    }

    try {
        try {
            $runtime_schema_result = database()->query("SELECT `completed_at`
            FROM `forever_business_schema_migrations`
            WHERE `migration_key` = '{$escaped_runtime_schema_key}' AND `completed_at` IS NOT NULL
            LIMIT 1");
            if($runtime_schema_result && $runtime_schema_result->fetch_assoc()) {
                $ready = true;
                return;
            }
        } catch(\Throwable $exception) {
            /* Continue with full bootstrap on a fresh database. */
        }

        $queries = [
        "CREATE TABLE IF NOT EXISTS `forever_business_imports` (
            `import_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `source_type` VARCHAR(16) NOT NULL,
            `report_kind` VARCHAR(32) NOT NULL,
            `original_name` VARCHAR(255) NOT NULL,
            `file_sha256` CHAR(64) NOT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'processing',
            `root_fbo_id` CHAR(12) NULL,
            `row_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `period_start` DATE NULL,
            `period_end` DATE NULL,
            `warning_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `summary_json` MEDIUMTEXT NULL,
            `imported_by_user_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `completed_at` DATETIME NULL,
            PRIMARY KEY (`import_id`),
            UNIQUE KEY `forever_business_import_sha_uq` (`file_sha256`),
            KEY `forever_business_import_status_idx` (`status`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_sync_checks` (
            `sync_check_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `report_kind` VARCHAR(32) NOT NULL,
            `original_name` VARCHAR(255) NOT NULL,
            `file_sha256` CHAR(64) NOT NULL,
            `import_id` BIGINT UNSIGNED NULL,
            `is_duplicate` TINYINT(1) NOT NULL DEFAULT 0,
            `checked_at` DATETIME NOT NULL,
            PRIMARY KEY (`sync_check_id`),
            KEY `forever_business_sync_checks_time_idx` (`checked_at`),
            KEY `forever_business_sync_checks_import_idx` (`import_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_members` (
            `fbo_id` CHAR(12) NOT NULL,
            `name` VARCHAR(160) NOT NULL,
            `title` VARCHAR(96) NULL,
            `generation` SMALLINT UNSIGNED NULL,
            `country_code` VARCHAR(8) NULL,
            `sponsor_date` DATE NULL,
            `parent_fbo_id` CHAR(12) NULL,
            `tree_sequence` VARCHAR(64) NULL,
            `is_manager` TINYINT(1) NOT NULL DEFAULT 0,
            `is_privacy_requested` TINYINT(1) NOT NULL DEFAULT 0,
            `is_in_current_structure` TINYINT(1) NOT NULL DEFAULT 1,
            `email_hash` CHAR(64) NULL,
            `phone_hash` CHAR(64) NULL,
            `first_seen_import_id` BIGINT UNSIGNED NULL,
            `last_seen_import_id` BIGINT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`fbo_id`),
            KEY `forever_business_member_parent_idx` (`parent_fbo_id`),
            KEY `forever_business_member_manager_idx` (`is_manager`, `is_in_current_structure`),
            KEY `forever_business_member_email_hash_idx` (`email_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_metrics` (
            `fbo_id` CHAR(12) NOT NULL,
            `period_month` DATE NOT NULL,
            `personal_cc` DECIMAL(12,3) NULL,
            `total_cc` DECIMAL(12,3) NULL,
            `total_active_cc` DECIMAL(12,3) NULL,
            `non_manager_cc` DECIMAL(12,3) NULL,
            `leadership_cc` DECIMAL(12,3) NULL,
            `is_4cc_active` TINYINT(1) NULL DEFAULT NULL,
            `source_import_id` BIGINT UNSIGNED NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`fbo_id`, `period_month`),
            KEY `forever_business_metrics_period_idx` (`period_month`, `is_4cc_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_yearly_metrics` (
            `fbo_id` CHAR(12) NOT NULL,
            `period_year` SMALLINT UNSIGNED NOT NULL,
            `total_active_cc_ytd` DECIMAL(12,3) NULL,
            `non_manager_cc_ytd` DECIMAL(12,3) NULL,
            `leadership_cc_ytd` DECIMAL(12,3) NULL,
            `source_import_id` BIGINT UNSIGNED NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`fbo_id`, `period_year`),
            KEY `forever_business_yearly_period_idx` (`period_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_focus_metrics` (
            `fbo_id` CHAR(12) NOT NULL,
            `period_month` DATE NOT NULL,
            `snapshot_date` DATE NOT NULL,
            `next_level` VARCHAR(96) NULL,
            `enrollment_date` DATE NULL,
            `last_purchase_date` DATE NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 0,
            `was_active_previous_month` TINYINT(1) NOT NULL DEFAULT 0,
            `open_group_cc_2m` DECIMAL(12,3) NULL,
            `needed_cc_next_level` DECIMAL(12,3) NULL,
            `personal_cc` DECIMAL(12,3) NULL,
            `new_recruits` INT UNSIGNED NOT NULL DEFAULT 0,
            `sponsor_fbo_id` CHAR(12) NULL,
            `sponsor_name` VARCHAR(160) NULL,
            `source_import_id` BIGINT UNSIGNED NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`fbo_id`, `period_month`),
            KEY `forever_business_focus_period_idx` (`period_month`, `is_active`, `needed_cc_next_level`),
            KEY `forever_business_focus_sponsor_idx` (`sponsor_fbo_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_four_core_snapshots` (
            `fbo_id` CHAR(12) NOT NULL,
            `period_month` DATE NOT NULL,
            `business_scope` VARCHAR(16) NOT NULL,
            `timeframe` VARCHAR(16) NOT NULL,
            `recruitment` DECIMAL(12,3) NULL,
            `retention` DECIMAL(12,3) NULL,
            `productivity` DECIMAL(12,3) NULL,
            `development` DECIMAL(12,3) NULL,
            `captured_at` DATETIME NOT NULL,
            `source_note` VARCHAR(255) NULL,
            PRIMARY KEY (`fbo_id`, `period_month`, `business_scope`, `timeframe`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_total_cc_snapshots` (
            `fbo_id` CHAR(12) NOT NULL,
            `period_month` DATE NOT NULL,
            `country_scope` VARCHAR(32) NOT NULL DEFAULT 'GLOBAL',
            `total_cc` DECIMAL(12,3) NOT NULL,
            `is_closed` TINYINT(1) NOT NULL DEFAULT 0,
            `captured_at` DATETIME NOT NULL,
            `source_note` VARCHAR(255) NULL,
            PRIMARY KEY (`fbo_id`, `period_month`, `country_scope`),
            KEY `forever_business_total_cc_period_idx` (`period_month`, `is_closed`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_hierarchy` (
            `ancestor_fbo_id` CHAR(12) NOT NULL,
            `descendant_fbo_id` CHAR(12) NOT NULL,
            `depth` SMALLINT UNSIGNED NOT NULL,
            `source_import_id` BIGINT UNSIGNED NULL,
            PRIMARY KEY (`ancestor_fbo_id`, `descendant_fbo_id`),
            KEY `forever_business_hierarchy_descendant_idx` (`descendant_fbo_id`, `depth`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_access` (
            `access_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `fbo_id` CHAR(12) NOT NULL,
            `access_role` VARCHAR(24) NOT NULL DEFAULT 'manager',
            `status` VARCHAR(16) NOT NULL DEFAULT 'active',
            `granted_by_user_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`access_id`),
            UNIQUE KEY `forever_business_access_user_fbo_uq` (`user_id`, `fbo_id`),
            KEY `forever_business_access_fbo_idx` (`fbo_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_daily_outcomes` (
            `outcome_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `fbo_id` CHAR(12) NOT NULL,
            `action_date` DATE NOT NULL,
            `core_key` VARCHAR(24) NOT NULL,
            `action_key` VARCHAR(48) NOT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'done',
            `outcome_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `outcome_type` VARCHAR(32) NULL,
            `note` VARCHAR(500) NULL,
            `recorded_by_user_id` INT UNSIGNED NOT NULL,
            `completion_mode` VARCHAR(16) NOT NULL DEFAULT 'standard',
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`outcome_id`),
            UNIQUE KEY `forever_business_outcome_user_daily_uq` (`recorded_by_user_id`, `action_date`, `action_key`),
            KEY `forever_business_outcome_user_progress_idx` (`recorded_by_user_id`, `status`, `action_date`),
            KEY `forever_business_outcome_fbo_idx` (`fbo_id`, `status`, `action_date`),
            KEY `forever_business_outcome_date_idx` (`action_date`, `core_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_vip_help_requests` (
            `request_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `fbo_id` CHAR(12) NOT NULL,
            `action_key` VARCHAR(48) NOT NULL,
            `track_key` VARCHAR(24) NULL,
            `sequence_position` TINYINT UNSIGNED NULL,
            `request_date` DATE NOT NULL,
            `difficulty` VARCHAR(16) NOT NULL DEFAULT 'hard',
            `note` VARCHAR(500) NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'open',
            `resolved_at` DATETIME NULL,
            `resolved_by_user_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`request_id`),
            UNIQUE KEY `forever_business_vip_help_user_action_uq` (`user_id`, `action_key`),
            KEY `forever_business_vip_help_status_idx` (`status`, `request_date`),
            KEY `forever_business_vip_help_fbo_idx` (`fbo_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        /* Custom code: FC-2026-08-21: permanent rolling VIP education enrollment */
        "CREATE TABLE IF NOT EXISTS `forever_business_vip_enrollments` (
            `fbo_id` CHAR(12) NOT NULL,
            `user_id` INT UNSIGNED NULL,
            `qualifying_period` DATE NOT NULL,
            `qualifying_personal_cc` DECIMAL(12,3) NOT NULL,
            `qualification_source` VARCHAR(32) NOT NULL,
            `last_verified_period` DATE NOT NULL,
            `last_verified_personal_cc` DECIMAL(12,3) NOT NULL,
            `source_import_id` BIGINT UNSIGNED NULL,
            `enrolled_at` DATETIME NOT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`fbo_id`),
            KEY `forever_business_vip_enrollment_user_idx` (`user_id`),
            KEY `forever_business_vip_enrollment_period_idx` (`qualifying_period`, `enrolled_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_schema_migrations` (
            `migration_key` VARCHAR(96) NOT NULL,
            `completed_at` DATETIME NULL,
            PRIMARY KEY (`migration_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        /* /Custom code: FC-2026-08-21 */
        "CREATE TABLE IF NOT EXISTS `forever_business_page_visits` (
            `user_id` INT UNSIGNED NOT NULL,
            `visit_date` DATE NOT NULL,
            `visit_count` INT UNSIGNED NOT NULL DEFAULT 1,
            `last_visit_at` DATETIME NOT NULL,
            PRIMARY KEY (`user_id`, `visit_date`),
            KEY `forever_business_visit_date_idx` (`visit_date`, `last_visit_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        /* Custom code: FC-2026-08-25: idempotent VIP access and qualification email queue */
        "CREATE TABLE IF NOT EXISTS `forever_business_vip_email_deliveries` (
            `delivery_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `event_key` VARCHAR(32) NOT NULL,
            `fbo_id` CHAR(12) NULL,
            `eligibility_period` DATE NULL,
            `personal_cc` DECIMAL(12,3) NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
            `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `message_id` VARCHAR(255) NULL,
            `last_error` VARCHAR(500) NULL,
            `last_attempt_at` DATETIME NULL,
            `sent_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`delivery_id`),
            UNIQUE KEY `forever_business_vip_email_user_event_uq` (`user_id`, `event_key`),
            KEY `forever_business_vip_email_status_idx` (`status`, `updated_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        /* /Custom code: FC-2026-08-25 */
    ];

    foreach($queries as $query) {
        db()->rawQuery($query);
    }

    /* rawQuery does not provide a uniform failure contract across all
     * supported database drivers. Verify every required table before any
     * migration marker can make a partial bootstrap look complete. */
    $required_tables = [
        'forever_business_imports',
        'forever_business_sync_checks',
        'forever_business_members',
        'forever_business_metrics',
        'forever_business_yearly_metrics',
        'forever_business_focus_metrics',
        'forever_business_four_core_snapshots',
        'forever_business_total_cc_snapshots',
        'forever_business_hierarchy',
        'forever_business_access',
        'forever_business_daily_outcomes',
        'forever_business_vip_help_requests',
        'forever_business_vip_enrollments',
        'forever_business_schema_migrations',
        'forever_business_page_visits',
        'forever_business_vip_email_deliveries',
    ];
    $required_table_list = "'" . implode("','", array_map(
        static fn(string $table): string => database()->real_escape_string($table),
        $required_tables
    )) . "'";
    $required_tables_result = database()->query("SELECT `TABLE_NAME` AS `table_name`
        FROM `information_schema`.`tables`
        WHERE `table_schema` = DATABASE() AND `table_name` IN ({$required_table_list})");
    $available_tables = [];
    while($required_tables_result && $required_table = $required_tables_result->fetch_assoc()) {
        $available_tables[(string) ($required_table['table_name'] ?? '')] = true;
    }
    $missing_required_tables = array_values(array_diff($required_tables, array_keys($available_tables)));
    if(!$required_tables_result || $missing_required_tables) {
        throw new \RuntimeException('Forever Business required tables could not be provisioned: ' . implode(', ', $missing_required_tables));
    }

    /* `NULL` means FLP360 has not supplied an official 4 CC result for this
     * member/month. Changing only the column nullability preserves every
     * existing explicit 0/1 result and lets new non-authoritative imports stay
     * unknown instead of silently creating a false official result. */
    $four_cc_column_result = database()->query("SHOW COLUMNS FROM `forever_business_metrics` LIKE 'is_4cc_active'");
    $four_cc_column = $four_cc_column_result ? $four_cc_column_result->fetch_assoc() : null;
    if(!$four_cc_column) {
        throw new \RuntimeException('Forever Business 4 CC signal column could not be verified.');
    }
    if($four_cc_column && mb_strtoupper((string) ($four_cc_column['Null'] ?? 'NO')) !== 'YES') {
        db()->rawQuery("ALTER TABLE `forever_business_metrics`
            MODIFY `is_4cc_active` TINYINT(1) NULL DEFAULT NULL");

        $four_cc_verify_result = database()->query("SHOW COLUMNS FROM `forever_business_metrics` LIKE 'is_4cc_active'");
        $four_cc_verify = $four_cc_verify_result ? $four_cc_verify_result->fetch_assoc() : null;
        if(!$four_cc_verify || mb_strtoupper((string) ($four_cc_verify['Null'] ?? 'NO')) !== 'YES') {
            throw new \RuntimeException('Forever Business 4 CC signal column must allow NULL before imports can continue.');
        }
    }

    /* Custom code: FC-2026-08-21: structured education outcomes are additive so
     * every historical completion remains valid and reportable. */
    $outcome_columns_result = database()->query("SHOW COLUMNS FROM `forever_business_daily_outcomes`");
    if(!$outcome_columns_result) {
        throw new \RuntimeException('Forever Business outcome columns could not be audited.');
    }
    $outcome_columns = [];
    while($outcome_columns_result && $outcome_column = $outcome_columns_result->fetch_assoc()) {
        $outcome_columns[(string) ($outcome_column['Field'] ?? '')] = true;
    }
    if(!isset($outcome_columns['result_type'])) {
        db()->rawQuery("ALTER TABLE `forever_business_daily_outcomes`
            ADD `result_type` VARCHAR(32) NULL AFTER `outcome_type`");
    }
    if(!isset($outcome_columns['difficulty'])) {
        db()->rawQuery("ALTER TABLE `forever_business_daily_outcomes`
            ADD `difficulty` VARCHAR(16) NULL AFTER `result_type`");
    }
    if(!isset($outcome_columns['needs_help'])) {
        db()->rawQuery("ALTER TABLE `forever_business_daily_outcomes`
            ADD `needs_help` TINYINT(1) NOT NULL DEFAULT 0 AFTER `difficulty`,
            ADD KEY `forever_business_outcome_help_idx` (`needs_help`, `action_date`)");
    }
    if(!isset($outcome_columns['completion_mode'])) {
        db()->rawQuery("ALTER TABLE `forever_business_daily_outcomes`
            ADD `completion_mode` VARCHAR(16) NOT NULL DEFAULT 'standard' AFTER `needs_help`");
    }

    /* VIP eligibility and CC remain attached to the Forever ID, while task
     * progress belongs to the signed-in FCC account. Backfill only historical
     * rows whose FBO has exactly one active account; ambiguous legacy rows stay
     * visible to the LOS data-quality audit and are never guessed. */
    $backfilled_outcomes = database()->query("UPDATE `forever_business_daily_outcomes` `outcome`
        INNER JOIN (
            SELECT `linked`.`fbo_id`, MIN(`linked`.`user_id`) AS `user_id`
            FROM (
                SELECT `user_id`, REPLACE(TRIM(COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.foreverId')),
                    JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.forever_id')),
                    JSON_UNQUOTE(JSON_EXTRACT(`preferences`, '$.meta.foreverID')),
                    ''
                )), '-', '') AS `fbo_id`
                FROM `users`
                WHERE `status` = 1 AND JSON_VALID(`preferences`) = 1
            ) `linked`
            WHERE `linked`.`fbo_id` REGEXP '^360[0-9]{9}$'
            GROUP BY `linked`.`fbo_id`
            HAVING COUNT(*) = 1
        ) `unique_link` ON `unique_link`.`fbo_id` = `outcome`.`fbo_id`
        SET `outcome`.`recorded_by_user_id` = `unique_link`.`user_id`
        WHERE (`outcome`.`recorded_by_user_id` IS NULL OR `outcome`.`recorded_by_user_id` = 0)");
    if(!$backfilled_outcomes) {
        throw new \RuntimeException('Forever Business participant backfill could not be completed safely.');
    }

    $outcome_indexes_result = database()->query("SHOW INDEX FROM `forever_business_daily_outcomes`");
    if(!$outcome_indexes_result) {
        throw new \RuntimeException('Forever Business outcome indexes could not be audited.');
    }
    $outcome_indexes = [];
    while($outcome_index = $outcome_indexes_result->fetch_assoc()) {
        $outcome_indexes[(string) ($outcome_index['Key_name'] ?? '')] = true;
    }
    if(!isset($outcome_indexes['forever_business_outcome_user_daily_uq'])) {
        db()->rawQuery("ALTER TABLE `forever_business_daily_outcomes`
            ADD UNIQUE KEY `forever_business_outcome_user_daily_uq` (`recorded_by_user_id`, `action_date`, `action_key`)");
    }
    if(!isset($outcome_indexes['forever_business_outcome_user_progress_idx'])) {
        db()->rawQuery("ALTER TABLE `forever_business_daily_outcomes`
            ADD KEY `forever_business_outcome_user_progress_idx` (`recorded_by_user_id`, `status`, `action_date`)");
    }
    if(!isset($outcome_indexes['forever_business_outcome_fbo_idx'])) {
        db()->rawQuery("ALTER TABLE `forever_business_daily_outcomes`
            ADD KEY `forever_business_outcome_fbo_idx` (`fbo_id`, `status`, `action_date`)");
    }
    if(isset($outcome_indexes['forever_business_outcome_daily_uq'])) {
        db()->rawQuery("ALTER TABLE `forever_business_daily_outcomes`
            DROP INDEX `forever_business_outcome_daily_uq`");
    }

    $outcome_columns_verify_result = database()->query("SHOW COLUMNS FROM `forever_business_daily_outcomes`");
    $outcome_columns_verify = [];
    while($outcome_columns_verify_result && $outcome_column = $outcome_columns_verify_result->fetch_assoc()) {
        $outcome_columns_verify[(string) ($outcome_column['Field'] ?? '')] = true;
    }
    if(!$outcome_columns_verify_result
        || !isset($outcome_columns_verify['result_type'], $outcome_columns_verify['difficulty'], $outcome_columns_verify['needs_help'], $outcome_columns_verify['completion_mode'])) {
        throw new \RuntimeException('Forever Business structured outcome columns could not be provisioned.');
    }

    $outcome_indexes_verify_result = database()->query("SHOW INDEX FROM `forever_business_daily_outcomes`");
    $outcome_indexes_verify = [];
    while($outcome_indexes_verify_result && $outcome_index = $outcome_indexes_verify_result->fetch_assoc()) {
        $outcome_indexes_verify[(string) ($outcome_index['Key_name'] ?? '')] = true;
    }
    if(!$outcome_indexes_verify_result
        || !isset($outcome_indexes_verify['forever_business_outcome_user_daily_uq'], $outcome_indexes_verify['forever_business_outcome_user_progress_idx'], $outcome_indexes_verify['forever_business_outcome_fbo_idx'])
        || isset($outcome_indexes_verify['forever_business_outcome_daily_uq'])) {
        throw new \RuntimeException('Forever Business participant-scoped outcome indexes could not be provisioned.');
    }

    $help_columns_result = database()->query("SHOW COLUMNS FROM `forever_business_vip_help_requests`");
    $help_columns = [];
    while($help_columns_result && $help_column = $help_columns_result->fetch_assoc()) {
        $help_columns[(string) ($help_column['Field'] ?? '')] = true;
    }
    $required_help_columns = [
        'request_id', 'user_id', 'fbo_id', 'action_key', 'track_key', 'sequence_position',
        'request_date', 'difficulty', 'note', 'status', 'resolved_at',
        'resolved_by_user_id', 'created_at', 'updated_at',
    ];
    if(!$help_columns_result || array_diff($required_help_columns, array_keys($help_columns))) {
        throw new \RuntimeException('Forever Business VIP help-request columns could not be provisioned.');
    }

    $help_indexes_result = database()->query("SHOW INDEX FROM `forever_business_vip_help_requests`");
    $help_indexes = [];
    while($help_indexes_result && $help_index = $help_indexes_result->fetch_assoc()) {
        $help_indexes[(string) ($help_index['Key_name'] ?? '')] = true;
    }
    if(!$help_indexes_result
        || !isset($help_indexes['PRIMARY'], $help_indexes['forever_business_vip_help_user_action_uq'], $help_indexes['forever_business_vip_help_status_idx'], $help_indexes['forever_business_vip_help_fbo_idx'])) {
        throw new \RuntimeException('Forever Business VIP help-request indexes could not be provisioned.');
    }

    /* Before help became its own lifecycle, a completed outcome could also
     * carry needs_help=1. Preserve those still-actionable requests exactly
     * once. Existing open or resolved help rows always win and are not
     * reopened by this compatibility migration. */
    $legacy_help_migrated = database()->query("INSERT IGNORE INTO `forever_business_vip_help_requests`
        (`user_id`, `fbo_id`, `action_key`, `track_key`, `sequence_position`, `request_date`,
         `difficulty`, `note`, `status`, `resolved_at`, `resolved_by_user_id`, `created_at`, `updated_at`)
        SELECT `outcome`.`recorded_by_user_id`, `outcome`.`fbo_id`, `outcome`.`action_key`,
               NULLIF(`outcome`.`outcome_type`, ''),
               CASE
                   WHEN `outcome`.`action_key` REGEXP '_d[0-9]{2}$'
                       THEN CAST(SUBSTRING_INDEX(`outcome`.`action_key`, '_d', -1) AS UNSIGNED)
                   ELSE NULL
               END,
               `outcome`.`action_date`, COALESCE(NULLIF(`outcome`.`difficulty`, ''), 'hard'),
               `outcome`.`note`, 'open', NULL, NULL,
               COALESCE(`outcome`.`created_at`, CONCAT(`outcome`.`action_date`, ' 00:00:00')),
               COALESCE(`outcome`.`updated_at`, `outcome`.`created_at`, CONCAT(`outcome`.`action_date`, ' 00:00:00'))
        FROM `forever_business_daily_outcomes` `outcome`
        WHERE `outcome`.`needs_help` = 1
          AND `outcome`.`recorded_by_user_id` IS NOT NULL
          AND `outcome`.`recorded_by_user_id` > 0
          AND `outcome`.`action_key` LIKE 'vip26\\_%'
          AND NOT EXISTS (
              SELECT 1
              FROM `forever_business_daily_outcomes` `later`
              WHERE `later`.`recorded_by_user_id` = `outcome`.`recorded_by_user_id`
                AND `later`.`status` = 'done'
                AND `later`.`action_key` LIKE 'vip26\\_%'
                AND (`later`.`action_date` > `outcome`.`action_date`
                     OR (`later`.`action_date` = `outcome`.`action_date` AND `later`.`outcome_id` > `outcome`.`outcome_id`))
          )");
    if(!$legacy_help_migrated) {
        throw new \RuntimeException('Legacy VIP help requests could not be preserved.');
    }
    $legacy_help_verify_result = database()->query("SELECT COUNT(*) AS `total`
        FROM `forever_business_daily_outcomes` `outcome`
        LEFT JOIN `forever_business_vip_help_requests` `request`
          ON `request`.`user_id` = `outcome`.`recorded_by_user_id`
         AND `request`.`action_key` = `outcome`.`action_key`
        WHERE `outcome`.`needs_help` = 1
          AND `outcome`.`recorded_by_user_id` IS NOT NULL
          AND `outcome`.`recorded_by_user_id` > 0
          AND `outcome`.`action_key` LIKE 'vip26\\_%'
          AND NOT EXISTS (
              SELECT 1
              FROM `forever_business_daily_outcomes` `later`
              WHERE `later`.`recorded_by_user_id` = `outcome`.`recorded_by_user_id`
                AND `later`.`status` = 'done'
                AND `later`.`action_key` LIKE 'vip26\\_%'
                AND (`later`.`action_date` > `outcome`.`action_date`
                     OR (`later`.`action_date` = `outcome`.`action_date` AND `later`.`outcome_id` > `outcome`.`outcome_id`))
          )
          AND `request`.`request_id` IS NULL");
    $legacy_help_verify = $legacy_help_verify_result ? $legacy_help_verify_result->fetch_assoc() : null;
    if(!$legacy_help_verify || (int) ($legacy_help_verify['total'] ?? 0) > 0) {
        throw new \RuntimeException('Legacy VIP help-request migration could not be verified.');
    }

    /* Preserve exactly the cohort admitted by the former August-only gate at
     * deployment time. A durable marker prevents later Focus-only August rows
     * from being grandfathered by a request-time schema check. */
    $legacy_migration_key = 'vip_enrollment_august_gate_v1';
    $escaped_legacy_migration_key = database()->real_escape_string($legacy_migration_key);
    $legacy_precheck_result = database()->query("SELECT `completed_at`
        FROM `forever_business_schema_migrations`
        WHERE `migration_key` = '{$escaped_legacy_migration_key}'
        LIMIT 1");
    if(!$legacy_precheck_result) {
        throw new \RuntimeException('Legacy VIP education migration marker could not be checked.');
    }
    $legacy_precheck = $legacy_precheck_result->fetch_assoc();

    /* The common post-migration path stays read-only and avoids serializing all
     * simultaneous page opens. Missing/incomplete markers still use the strict
     * transactional upsert + row lock below. */
    if(!$legacy_precheck || empty($legacy_precheck['completed_at'])) {
        db()->startTransaction();
        try {
            $marker_ready = database()->query("INSERT INTO `forever_business_schema_migrations`
                (`migration_key`, `completed_at`) VALUES ('{$escaped_legacy_migration_key}', NULL)
                ON DUPLICATE KEY UPDATE `migration_key` = VALUES(`migration_key`)");
            if(!$marker_ready) {
                throw new \RuntimeException('Legacy VIP education migration marker could not be prepared.');
            }
            $legacy_migration_result = database()->query("SELECT `completed_at`
                FROM `forever_business_schema_migrations`
                WHERE `migration_key` = '{$escaped_legacy_migration_key}'
                LIMIT 1 FOR UPDATE");
            if(!$legacy_migration_result) {
                throw new \RuntimeException('Legacy VIP education migration marker could not be locked.');
            }
            $legacy_migration = $legacy_migration_result->fetch_assoc();

            if(!$legacy_migration || empty($legacy_migration['completed_at'])) {
                $migration_time = database()->real_escape_string(get_date());
                $legacy_inserted = database()->query("INSERT IGNORE INTO `forever_business_vip_enrollments`
                    (`fbo_id`, `user_id`, `qualifying_period`, `qualifying_personal_cc`, `qualification_source`,
                     `last_verified_period`, `last_verified_personal_cc`, `source_import_id`, `enrolled_at`, `created_at`, `updated_at`)
                    SELECT metric.fbo_id, NULL, metric.period_month, metric.personal_cc, 'legacy_august_backfill',
                           metric.period_month, metric.personal_cc, metric.source_import_id, '{$migration_time}', '{$migration_time}', '{$migration_time}'
                    FROM `forever_business_metrics` metric
                    WHERE metric.period_month = '2026-08-01' AND metric.personal_cc >= 0.330");
                if(!$legacy_inserted) {
                    throw new \RuntimeException('Legacy VIP education cohort could not be copied.');
                }

                $legacy_verify_result = database()->query("SELECT COUNT(*) AS total
                    FROM `forever_business_metrics` metric
                    LEFT JOIN `forever_business_vip_enrollments` enrollment ON enrollment.fbo_id = metric.fbo_id
                    WHERE metric.period_month = '2026-08-01' AND metric.personal_cc >= 0.330 AND enrollment.fbo_id IS NULL");
                $legacy_verify_row = $legacy_verify_result ? $legacy_verify_result->fetch_assoc() : null;
                if(!$legacy_verify_row || (int) ($legacy_verify_row['total'] ?? 0) > 0) {
                    throw new \RuntimeException('Legacy VIP education cohort could not be preserved.');
                }

                $marker_completed = database()->query("UPDATE `forever_business_schema_migrations`
                    SET `completed_at` = '{$migration_time}'
                    WHERE `migration_key` = '{$escaped_legacy_migration_key}' AND `completed_at` IS NULL");
                if(!$marker_completed || database()->affected_rows !== 1) {
                    throw new \RuntimeException('Legacy VIP education migration could not be finalized.');
                }
            }

            db()->commit();
        } catch(\Throwable $exception) {
            db()->rollback();
            throw $exception;
        }
    }
    /* /Custom code: FC-2026-08-21 */

    $runtime_schema_time = database()->real_escape_string(get_date());
    $runtime_schema_saved = database()->query("INSERT INTO `forever_business_schema_migrations`
        (`migration_key`, `completed_at`) VALUES ('{$escaped_runtime_schema_key}', '{$runtime_schema_time}')
        ON DUPLICATE KEY UPDATE `completed_at` = VALUES(`completed_at`)");
    if(!$runtime_schema_saved) {
        throw new \RuntimeException('Forever Business runtime schema marker could not be saved.');
    }
    $ready = true;
    } finally {
        database()->query("SELECT RELEASE_LOCK('{$schema_lock_name}')");
    }
}

function forever_business_normalize_fbo_id($value): string {
    $digits = preg_replace('/\D+/', '', trim((string) $value));
    return strlen($digits) === 12 ? $digits : '';
}

function forever_business_normalize_header($value): string {
    $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
    $value = preg_replace('/\s+/', ' ', trim($value));
    return mb_strtoupper($value);
}

function forever_business_number($value): float {
    if($value === null || $value === '') {
        return 0.0;
    }

    $value = trim((string) $value);
    $value = str_replace([' ', ','], ['', '.'], $value);
    return is_numeric($value) ? (float) $value : 0.0;
}

function forever_business_contact_hash($value): ?string {
    $value = mb_strtolower(trim((string) $value));

    if($value === '' || $value === 'privacy requested') {
        return null;
    }

    $salt = defined('LOS_PRIVACY_HASH_SALT') ? LOS_PRIVACY_HASH_SALT : ROOT_PATH;
    return hash_hmac('sha256', $value, $salt);
}

function forever_business_phone_hash($value): ?string {
    $value = preg_replace('/\D+/', '', trim((string) $value));
    return $value === '' ? null : forever_business_contact_hash($value);
}

function forever_business_parse_date($value): ?string {
    $value = trim((string) $value);

    if($value === '') {
        return null;
    }

    foreach(['d-m-Y', 'Y-m-d', 'd.m.Y', 'm/d/Y'] as $format) {
        $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
        if($date instanceof \DateTimeImmutable) {
            return $date->format('Y-m-d');
        }
    }

    return null;
}

function forever_business_period_from_label($value): ?string {
    $value = mb_strtoupper(trim((string) $value));
    if(preg_match('/^(20\d{2})-(0[1-9]|1[0-2])(?:-(?:0[1-9]|[12]\d|3[01]))?$/', $value, $iso_matches)) {
        return sprintf('%04d-%02d-01', (int) $iso_matches[1], (int) $iso_matches[2]);
    }
    $months = [
        'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4,
        'MAY' => 5, 'JUN' => 6, 'JUL' => 7, 'AUG' => 8,
        'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12,
    ];

    if(!preg_match('/\b(' . implode('|', array_keys($months)) . ')\b\s*(?:-|\/|\s)*\s*(20\d{2})\b/', $value, $matches)) {
        return null;
    }

    return sprintf('%04d-%02d-01', (int) $matches[2], $months[$matches[1]]);
}

function forever_business_current_zagreb_period(?\DateTimeInterface $now = null): string {
    $timezone = new \DateTimeZone('Europe/Zagreb');
    $current_time = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);
    return $current_time->format('Y-m-01');
}

/* Monthly FLP values reset at the start of a new Zagreb calendar month. Until
 * the first synchronized order creates that month's row, the current month is
 * a real zero — never a copy of the preceding month. Historical selections
 * and cumulative/YTD fields remain untouched. */
function forever_business_normalize_current_month_metrics(
    array $metric,
    string $period,
    ?\DateTimeInterface $now = null
): array {
    $period = forever_business_period_from_label($period) ?: '';
    if($period === '' || $period !== forever_business_current_zagreb_period($now)) {
        return $metric;
    }

    foreach(['personal_cc', 'total_cc', 'total_active_cc', 'non_manager_cc', 'leadership_cc'] as $field) {
        if(!array_key_exists($field, $metric) || $metric[$field] === null || $metric[$field] === '') {
            $metric[$field] = 0.0;
        }
    }

    return $metric;
}

function forever_business_read_csv(string $path): array {
    $handle = fopen($path, 'rb');

    if(!$handle) {
        throw new \RuntimeException('CSV datoteku nije moguće otvoriti.');
    }

    $sample = fgets($handle);
    rewind($handle);
    $delimiters = [',' => substr_count((string) $sample, ','), ';' => substr_count((string) $sample, ';'), "\t" => substr_count((string) $sample, "\t")];
    arsort($delimiters);
    $delimiter = (string) array_key_first($delimiters);
    $rows = [];

    while(($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
        $clean = [];
        foreach($row as $value) {
            $value = (string) $value;
            if(!mb_check_encoding($value, 'UTF-8')) {
                $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1250');
            }
            $clean[] = trim($value);
        }
        if(!empty(array_filter($clean, static fn($value) => $value !== ''))) {
            $rows[] = $clean;
        }
    }

    fclose($handle);
    return $rows;
}

function forever_business_xlsx_column_index(string $reference): int {
    preg_match('/^[A-Z]+/i', $reference, $matches);
    $letters = mb_strtoupper($matches[0] ?? 'A');
    $index = 0;

    for($i = 0, $length = strlen($letters); $i < $length; $i++) {
        $index = ($index * 26) + (ord($letters[$i]) - 64);
    }

    return max(0, $index - 1);
}

function forever_business_read_xlsx(string $path): array {
    $zip = new \ZipArchive();

    if($zip->open($path) !== true) {
        throw new \RuntimeException('Excel datoteku nije moguće otvoriti.');
    }

    $shared = [];
    $shared_xml_raw = $zip->getFromName('xl/sharedStrings.xml');
    if($shared_xml_raw !== false) {
        $shared_xml = simplexml_load_string($shared_xml_raw);
        if($shared_xml) {
            $shared_xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach($shared_xml->xpath('//m:si') ?: [] as $item) {
                $parts = [];
                foreach($item->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                    $parts[] = (string) $text;
                }
                $shared[] = implode('', $parts);
            }
        }
    }

    $workbook_raw = $zip->getFromName('xl/workbook.xml');
    $relations_raw = $zip->getFromName('xl/_rels/workbook.xml.rels');

    if($workbook_raw === false || $relations_raw === false) {
        $zip->close();
        throw new \RuntimeException('Excel struktura nije podržana.');
    }

    $workbook = simplexml_load_string($workbook_raw);
    $relations = simplexml_load_string($relations_raw);
    $workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $relation_map = [];

    $relations->registerXPathNamespace('p', 'http://schemas.openxmlformats.org/package/2006/relationships');
    foreach($relations->xpath('//p:Relationship') ?: [] as $relation) {
        $relation_map[(string) $relation['Id']] = (string) $relation['Target'];
    }

    $sheets = $workbook->xpath('//m:sheets/m:sheet') ?: [];
    if(empty($sheets)) {
        $zip->close();
        throw new \RuntimeException('Excel nema radni list.');
    }

    $relationship_attributes = $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $relationship_id = (string) ($relationship_attributes['id'] ?? '');
    $target = $relation_map[$relationship_id] ?? '';

    if($target === '') {
        $zip->close();
        throw new \RuntimeException('Excel radni list nije pronađen.');
    }

    $sheet_path = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . ltrim($target, '/');
    $sheet_path = str_replace('xl/../', '', $sheet_path);
    $sheet_raw = $zip->getFromName($sheet_path);
    $zip->close();

    if($sheet_raw === false) {
        throw new \RuntimeException('Excel sadržaj nije pronađen.');
    }

    $sheet = simplexml_load_string($sheet_raw);
    $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $rows = [];

    foreach($sheet->xpath('//m:sheetData/m:row') ?: [] as $row_node) {
        $row = [];
        foreach($row_node->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->c as $cell) {
            $cell_attributes = $cell->attributes();
            $reference = (string) ($cell_attributes['r'] ?? 'A1');
            $index = forever_business_xlsx_column_index($reference);
            $type = (string) ($cell_attributes['t'] ?? '');
            $value = '';

            if($type === 'inlineStr') {
                $parts = [];
                foreach($cell->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                    $parts[] = (string) $text;
                }
                $value = implode('', $parts);
            } else {
                $cell_children = $cell->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $raw_value = (string) ($cell_children->v ?? '');
                $value = $type === 's' ? ($shared[(int) $raw_value] ?? '') : $raw_value;
            }

            $row[$index] = trim((string) $value);
        }

        if(!empty($row)) {
            ksort($row);
            $width = max(array_keys($row)) + 1;
            $normalized = array_fill(0, $width, '');
            foreach($row as $index => $value) {
                $normalized[$index] = $value;
            }
            $rows[] = $normalized;
        }
    }

    return $rows;
}

function forever_business_read_report_file(string $path, string $original_name): array {
    $extension = mb_strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    if($extension === 'csv') {
        return forever_business_read_csv($path);
    }

    if($extension === 'xlsx') {
        return forever_business_read_xlsx($path);
    }

    throw new \RuntimeException('Podržani su CSV i XLSX izvještaji. PDF ide u ručnu provjeru prije importa.');
}

function forever_business_parse_report(string $path, string $original_name, string $root_fbo_id = '', string $root_name = '', string $report_period = ''): array {
    $rows = forever_business_read_report_file($path, $original_name);

    if(count($rows) < 2) {
        throw new \RuntimeException('Izvještaj nema podatkovne retke.');
    }

    $headers = array_map('forever_business_normalize_header', $rows[0]);
    $header_map = [];
    foreach($headers as $index => $header) {
        $header_map[$header] = $index;
    }

    $is_full = isset($header_map['FBO ID'], $header_map['TREESEQUENCE'], $header_map['NAME'], $header_map['TITLE'], $header_map['GENERATION']);
    $is_four_cc = isset($header_map['FBO ID'], $header_map['FBO NAME'], $header_map['PERSONAL CC'], $header_map['TOTAL ACTIVE CC'], $header_map['SELECTED MONTH/YEAR']);
    $is_focus_group = isset($header_map['FBO ID'], $header_map['FBO NAME'], $header_map['CURRENT LEVEL'], $header_map['NEXT LEVEL'], $header_map['LAST PURCHASE DATE'], $header_map['NEEDED CC FOR NEXT LEVEL']);

    if(!$is_full && !$is_four_cc && !$is_focus_group) {
        throw new \RuntimeException('Struktura izvještaja nije prepoznata. Očekuje se FLP360 downline CSV, 4 CC Active XLSX ili Focus Group XLSX.');
    }

    $kind = $is_full ? 'downline' : ($is_four_cc ? 'four_cc_active' : 'focus_group');

    $report = [
        'kind' => $kind,
        'source_type' => mb_strtolower(pathinfo($original_name, PATHINFO_EXTENSION)),
        'original_name' => mb_substr(basename($original_name), 0, 255),
        'root_fbo_id' => forever_business_normalize_fbo_id($root_fbo_id),
        'root_name' => trim($root_name),
        'members' => [],
        'metrics' => [],
        'yearly_metrics' => [],
        'focus_metrics' => [],
        'parent_map' => [],
        'periods' => [],
        'warnings' => [],
        'errors' => [],
    ];

    if($is_full) {
        if($report['root_fbo_id'] === '') {
            $report['errors'][] = 'Za downline izvještaj nedostaje glavni Forever ID.';
        }

        $metric_columns = [];
        $yearly_metric_columns = [];
        foreach($headers as $index => $header) {
            if(preg_match('/^(4CC ACTIVE|PERSONAL CC|TOTAL CC|TOTAL ACTIVE CC|NON MANAGER CC|LEADERSHIP CC) - ([A-Z]{3}) - (20\d{2})$/', $header, $matches)) {
                $period = forever_business_period_from_label($matches[2] . ' ' . $matches[3]);
                if($period) {
                    $metric_columns[] = ['index' => $index, 'metric' => $matches[1], 'period' => $period];
                    $report['periods'][$period] = true;
                }
            }
            if(preg_match('/^(TOTAL ACTIVE CC|NON MANAGER CC|LEADERSHIP CC) YTD - (20\d{2})$/', $header, $matches)) {
                $yearly_metric_columns[] = ['index' => $index, 'metric' => $matches[1], 'year' => (int) $matches[2]];
            }
        }

        if(empty($metric_columns)) {
            $report['errors'][] = 'Nisu pronađeni mjesečni CC stupci.';
        }

        $seen_ids = [];
        $stack = [];
        if($report['root_fbo_id'] !== '') {
            $stack[0] = $report['root_fbo_id'];
        }

        foreach(array_slice($rows, 1) as $row_number => $row) {
            $fbo_id = forever_business_normalize_fbo_id($row[$header_map['FBO ID']] ?? '');
            if($fbo_id === '') {
                $report['errors'][] = 'Redak ' . ($row_number + 2) . ': neispravan FBO ID.';
                continue;
            }
            if(isset($seen_ids[$fbo_id])) {
                $report['errors'][] = 'Duplikat FBO ID-a ' . $fbo_id . '.';
                continue;
            }
            $seen_ids[$fbo_id] = true;

            $generation = (int) ($row[$header_map['GENERATION']] ?? 0);
            if($generation < 1 || ($generation > 1 && empty($stack[$generation - 1]))) {
                $report['errors'][] = 'Redak ' . ($row_number + 2) . ': hijerarhija nema roditelja za generaciju ' . $generation . '.';
                continue;
            }

            $parent_fbo_id = $generation === 1 ? $report['root_fbo_id'] : ($stack[$generation - 1] ?? '');
            $stack[$generation] = $fbo_id;
            foreach(array_keys($stack) as $depth) {
                if($depth > $generation) unset($stack[$depth]);
            }

            $title = trim((string) ($row[$header_map['TITLE']] ?? ''));
            $name = trim((string) ($row[$header_map['NAME']] ?? ''));
            $report['members'][$fbo_id] = [
                'fbo_id' => $fbo_id,
                'name' => $name !== '' ? mb_substr($name, 0, 160) : 'Bez imena',
                'title' => mb_substr($title, 0, 96),
                'generation' => $generation,
                'country_code' => mb_substr(trim((string) ($row[$header_map['COUNTRY']] ?? '')), 0, 8),
                'sponsor_date' => forever_business_parse_date($row[$header_map['SPONSOR DATE']] ?? ''),
                'parent_fbo_id' => $parent_fbo_id,
                'tree_sequence' => mb_substr(trim((string) ($row[$header_map['TREESEQUENCE']] ?? '')), 0, 64),
                'is_manager' => stripos($title, 'manager') !== false ? 1 : 0,
                'is_privacy_requested' => stripos($name, 'privacy requested') !== false ? 1 : 0,
                'email_hash' => forever_business_contact_hash($row[$header_map['EMAIL']] ?? ''),
                'phone_hash' => forever_business_phone_hash($row[$header_map['PHONE']] ?? ''),
            ];
            $report['parent_map'][$fbo_id] = $parent_fbo_id;

            foreach($metric_columns as $column) {
                $key = $fbo_id . '|' . $column['period'];
                if(!isset($report['metrics'][$key])) {
                    $report['metrics'][$key] = [
                        'fbo_id' => $fbo_id,
                        'period_month' => $column['period'],
                        'personal_cc' => null,
                        'total_cc' => null,
                        'total_active_cc' => null,
                        'non_manager_cc' => null,
                        'leadership_cc' => null,
                        'is_4cc_active' => null,
                    ];
                }
                $value = $row[$column['index']] ?? '';
                $field_map = [
                    'PERSONAL CC' => 'personal_cc',
                    'TOTAL CC' => 'total_cc',
                    'TOTAL ACTIVE CC' => 'total_active_cc',
                    'NON MANAGER CC' => 'non_manager_cc',
                    'LEADERSHIP CC' => 'leadership_cc',
                ];
                if($column['metric'] === '4CC ACTIVE') {
                    $report['metrics'][$key]['is_4cc_active'] = mb_strtoupper(trim((string) $value)) === 'Y' ? 1 : 0;
                } elseif(isset($field_map[$column['metric']])) {
                    $report['metrics'][$key][$field_map[$column['metric']]] = forever_business_number($value);
                }
            }

            foreach($yearly_metric_columns as $column) {
                $key = $fbo_id . '|' . $column['year'];
                if(!isset($report['yearly_metrics'][$key])) {
                    $report['yearly_metrics'][$key] = [
                        'fbo_id' => $fbo_id,
                        'period_year' => $column['year'],
                        'total_active_cc_ytd' => null,
                        'non_manager_cc_ytd' => null,
                        'leadership_cc_ytd' => null,
                    ];
                }
                $field_map = [
                    'TOTAL ACTIVE CC' => 'total_active_cc_ytd',
                    'NON MANAGER CC' => 'non_manager_cc_ytd',
                    'LEADERSHIP CC' => 'leadership_cc_ytd',
                ];
                $report['yearly_metrics'][$key][$field_map[$column['metric']]] = forever_business_number($row[$column['index']] ?? 0);
            }
        }
    } elseif($is_four_cc) {
        $seen_ids = [];
        foreach(array_slice($rows, 1) as $row_number => $row) {
            $fbo_id = forever_business_normalize_fbo_id($row[$header_map['FBO ID']] ?? '');
            $period = forever_business_period_from_label($row[$header_map['SELECTED MONTH/YEAR']] ?? '');
            if($fbo_id === '' || !$period) {
                $report['errors'][] = 'Redak ' . ($row_number + 2) . ': neispravan FBO ID ili mjesec.';
                continue;
            }
            if(isset($seen_ids[$fbo_id])) {
                $report['errors'][] = 'Duplikat FBO ID-a ' . $fbo_id . '.';
                continue;
            }
            $seen_ids[$fbo_id] = true;
            $title = trim((string) ($row[$header_map['LEVEL']] ?? ''));
            $name = trim((string) ($row[$header_map['FBO NAME']] ?? ''));
            $report['periods'][$period] = true;
            $report['members'][$fbo_id] = [
                'fbo_id' => $fbo_id,
                'name' => $name !== '' ? mb_substr($name, 0, 160) : 'Bez imena',
                'title' => mb_substr($title, 0, 96),
                'generation' => null,
                'country_code' => mb_substr(trim((string) ($row[$header_map['HOME COUNTRY']] ?? '')), 0, 8),
                'sponsor_date' => null,
                'parent_fbo_id' => null,
                'tree_sequence' => null,
                'is_manager' => stripos($title, 'manager') !== false ? 1 : 0,
                'is_privacy_requested' => stripos($name, 'privacy requested') !== false ? 1 : 0,
                'email_hash' => null,
                'phone_hash' => null,
            ];
            $report['metrics'][$fbo_id . '|' . $period] = [
                'fbo_id' => $fbo_id,
                'period_month' => $period,
                'personal_cc' => forever_business_number($row[$header_map['PERSONAL CC']] ?? 0),
                'total_cc' => null,
                'total_active_cc' => forever_business_number($row[$header_map['TOTAL ACTIVE CC']] ?? 0),
                'non_manager_cc' => null,
                'leadership_cc' => null,
                'is_4cc_active' => 1,
            ];
        }
    } else {
        $period = forever_business_period_from_label($report_period);
        if(!$period) {
            $report['errors'][] = 'Za Focus Group izvještaj odaberi mjesec na koji se izvještaj odnosi.';
        }

        $seen_ids = [];
        foreach(array_slice($rows, 1) as $row_number => $row) {
            $fbo_id = forever_business_normalize_fbo_id($row[$header_map['FBO ID']] ?? '');
            if($fbo_id === '') {
                $report['errors'][] = 'Redak ' . ($row_number + 2) . ': neispravan FBO ID.';
                continue;
            }
            if(isset($seen_ids[$fbo_id])) {
                $report['errors'][] = 'Duplikat FBO ID-a ' . $fbo_id . '.';
                continue;
            }
            $seen_ids[$fbo_id] = true;

            $title = trim((string) ($row[$header_map['CURRENT LEVEL']] ?? ''));
            $name = trim((string) ($row[$header_map['FBO NAME']] ?? ''));
            $sponsor_fbo_id = forever_business_normalize_fbo_id($row[$header_map['SPONSOR ID']] ?? '');
            $personal_cc = forever_business_number($row[$header_map['PERSONAL CC']] ?? 0);
            $is_active = mb_strtoupper(trim((string) ($row[$header_map['ACTIVE']] ?? ''))) === 'YES' ? 1 : 0;
            if($period) $report['periods'][$period] = true;

            $report['members'][$fbo_id] = [
                'fbo_id' => $fbo_id,
                'name' => $name !== '' ? mb_substr($name, 0, 160) : 'Bez imena',
                'title' => mb_substr($title, 0, 96),
                'generation' => isset($header_map['GEN']) ? max(0, (int) ($row[$header_map['GEN']] ?? 0)) : null,
                'country_code' => null,
                'sponsor_date' => forever_business_parse_date($row[$header_map['ENROLLMENT DATE']] ?? ''),
                'parent_fbo_id' => $sponsor_fbo_id ?: null,
                'tree_sequence' => null,
                'is_manager' => stripos($title, 'manager') !== false ? 1 : 0,
                'is_privacy_requested' => stripos($name, 'privacy requested') !== false ? 1 : 0,
                'email_hash' => null,
                'phone_hash' => null,
            ];

            if($period) {
                $report['metrics'][$fbo_id . '|' . $period] = [
                    'fbo_id' => $fbo_id,
                    'period_month' => $period,
                    'personal_cc' => $personal_cc,
                    'total_cc' => null,
                    'total_active_cc' => null,
                    'non_manager_cc' => null,
                    'leadership_cc' => null,
                    /* Focus Group ACTIVE is a focus/report signal, not the official
                     * FLP360 4 CC Active result. Only Downline 4CC ACTIVE and the
                     * dedicated 4 CC Active report may set this field. */
                    'is_4cc_active' => null,
                ];
                $report['focus_metrics'][$fbo_id . '|' . $period] = [
                    'fbo_id' => $fbo_id,
                    'period_month' => $period,
                    'snapshot_date' => date('Y-m-d'),
                    'next_level' => mb_substr(trim((string) ($row[$header_map['NEXT LEVEL']] ?? '')), 0, 96),
                    'enrollment_date' => forever_business_parse_date($row[$header_map['ENROLLMENT DATE']] ?? ''),
                    'last_purchase_date' => forever_business_parse_date($row[$header_map['LAST PURCHASE DATE']] ?? ''),
                    'is_active' => $is_active,
                    'was_active_previous_month' => mb_strtoupper(trim((string) ($row[$header_map['PREVIOUS MONTH ACTIVE']] ?? ''))) === 'YES' ? 1 : 0,
                    'open_group_cc_2m' => forever_business_number($row[$header_map['2 MONTHS OPEN GROUP CC']] ?? 0),
                    'needed_cc_next_level' => forever_business_number($row[$header_map['NEEDED CC FOR NEXT LEVEL']] ?? 0),
                    'personal_cc' => $personal_cc,
                    'new_recruits' => max(0, (int) forever_business_number($row[$header_map['NEW RECRUITS']] ?? 0)),
                    'sponsor_fbo_id' => $sponsor_fbo_id ?: null,
                    'sponsor_name' => mb_substr(trim((string) ($row[$header_map['SPONSOR NAME']] ?? '')), 0, 160),
                ];
            }
        }
    }

    $report['periods'] = array_keys($report['periods']);
    sort($report['periods']);
    $latest_period = !empty($report['periods']) ? end($report['periods']) : null;
    $latest_metrics = array_values(array_filter($report['metrics'], static fn($metric) => $metric['period_month'] === $latest_period));
    $report['summary'] = [
        'kind' => $report['kind'],
        'rows' => count($report['members']),
        'managers' => count(array_filter($report['members'], static fn($member) => !empty($member['is_manager']))),
        'periods' => $report['periods'],
        'latest_period' => $latest_period,
        'latest_personal_cc' => array_sum(array_map(static fn($metric) => (float) ($metric['personal_cc'] ?? 0), $latest_metrics)),
        'latest_personal_active' => count(array_filter($latest_metrics, static fn($metric) => (float) ($metric['personal_cc'] ?? 0) > 0)),
        'latest_4cc_active' => count(array_filter($latest_metrics, static fn($metric) => !empty($metric['is_4cc_active']))),
        'yearly_rows' => count($report['yearly_metrics']),
        'focus_rows' => count($report['focus_metrics']),
    ];

    return $report;
}

function forever_business_upsert_member(array $member, int $import_id, bool $preserve_hierarchy = false): void {
    $now = get_date();
    $data = [
        'fbo_id' => $member['fbo_id'],
        'name' => $member['name'],
        'title' => $member['title'] ?: null,
        'generation' => $member['generation'],
        'country_code' => $member['country_code'] ?: null,
        'sponsor_date' => $member['sponsor_date'],
        'parent_fbo_id' => $member['parent_fbo_id'] ?: null,
        'tree_sequence' => $member['tree_sequence'] ?: null,
        'is_manager' => (int) $member['is_manager'],
        'is_privacy_requested' => (int) $member['is_privacy_requested'],
        'is_in_current_structure' => 1,
        'email_hash' => $member['email_hash'],
        'phone_hash' => $member['phone_hash'],
        'first_seen_import_id' => $import_id,
        'last_seen_import_id' => $import_id,
        'created_at' => $now,
        'updated_at' => $now,
    ];

    $update_columns = ['name', 'title', 'country_code', 'is_manager', 'is_privacy_requested', 'is_in_current_structure', 'last_seen_import_id', 'updated_at'];
    if(!$preserve_hierarchy) {
        array_push($update_columns, 'generation', 'sponsor_date', 'parent_fbo_id', 'tree_sequence', 'email_hash', 'phone_hash');
    }

    db()->onDuplicate($update_columns)->insert('forever_business_members', $data);
}

function forever_business_import_report(array $report, string $file_sha256, int $user_id): array {
    forever_business_ensure_tables();

    if(!empty($report['errors'])) {
        throw new \RuntimeException(implode(' ', array_slice($report['errors'], 0, 5)));
    }

    /* No report may create a future period that later becomes the dashboard's
     * apparent latest month. Reject before deduplication/write so the same file
     * can be imported normally once its Zagreb month actually begins. */
    $report_periods_to_validate = array_merge(
        (array) ($report['periods'] ?? []),
        array_map(static fn(array $metric) => $metric['period_month'] ?? '', (array) ($report['metrics'] ?? []))
    );
    foreach($report_periods_to_validate as $report_period_to_validate) {
        $metric_period = forever_business_period_from_label($report_period_to_validate) ?: '';
        if($metric_period !== '' && !forever_business_period_is_current_or_past($metric_period)) {
            throw new \RuntimeException('Budući FLP360 mjesec još se ne može uvesti. Ponovi uvoz kada taj mjesec započne.');
        }
    }

    /* Focus Group exports do not carry a period in their filename or rows. Include the
       confirmed report period in the idempotency key so the same export can be used for
       a corrected month without weakening duplicate protection inside that month. */
    $dedupe_sha256 = $report['kind'] === 'focus_group'
        ? hash('sha256', $file_sha256 . '|' . implode(',', $report['periods']))
        : $file_sha256;

    $existing = db()->where('file_sha256', $dedupe_sha256)->getOne('forever_business_imports');
    if($existing && $existing->status === 'completed') {
        forever_business_record_sync_check($report, $dedupe_sha256, (int) $existing->import_id, true);
        return ['duplicate' => true, 'import_id' => (int) $existing->import_id, 'summary' => json_decode($existing->summary_json ?? '{}', true) ?: []];
    }
    if($existing && $existing->status === 'processing') {
        throw new \RuntimeException('Isti FLP360 izvještaj već se obrađuje. Pričekaj završetak prije ponovnog pokušaja.');
    }
    if($existing && $existing->status === 'failed') {
        $deleted_failed_import = db()->where('import_id', (int) $existing->import_id)->where('status', 'failed')->delete('forever_business_imports');
        if(!$deleted_failed_import || (int) database()->affected_rows !== 1) {
            throw new \RuntimeException('Prethodni neuspjeli FLP360 uvoz nije moguće sigurno pripremiti za ponovni pokušaj.');
        }
    } elseif($existing) {
        throw new \RuntimeException('Isti FLP360 izvještaj već postoji u neočekivanom stanju i neće se ponovno obrađivati.');
    }

    $periods = $report['periods'];
    $import_id = db()->insert('forever_business_imports', [
        'source_type' => $report['source_type'],
        'report_kind' => $report['kind'],
        'original_name' => $report['original_name'],
        'file_sha256' => $dedupe_sha256,
        'status' => 'processing',
        'root_fbo_id' => $report['root_fbo_id'] ?: null,
        'row_count' => count($report['members']),
        'period_start' => !empty($periods) ? reset($periods) : null,
        'period_end' => !empty($periods) ? end($periods) : null,
        'warning_count' => count($report['warnings']),
        'summary_json' => json_encode($report['summary'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'imported_by_user_id' => $user_id,
        'created_at' => get_date(),
        'completed_at' => null,
    ]);
    if(!$import_id || (int) $import_id <= 0) {
        /* A concurrent request may have won the unique file hash between our
         * read and insert. Never continue with import_id=0 or orphan provenance. */
        $concurrent = db()->where('file_sha256', $dedupe_sha256)->getOne('forever_business_imports');
        if($concurrent && $concurrent->status === 'completed') {
            forever_business_record_sync_check($report, $dedupe_sha256, (int) $concurrent->import_id, true);
            return ['duplicate' => true, 'import_id' => (int) $concurrent->import_id, 'summary' => json_decode($concurrent->summary_json ?? '{}', true) ?: []];
        }
        throw new \RuntimeException('Isti FLP360 izvještaj upravo se obrađuje. Pričekaj završetak prije ponovnog pokušaja.');
    }

    db()->startTransaction();

    try {
        if($report['kind'] === 'downline') {
            db()->update('forever_business_members', ['is_in_current_structure' => 0, 'updated_at' => get_date()]);

            $root_id = $report['root_fbo_id'];
            forever_business_upsert_member([
                'fbo_id' => $root_id,
                'name' => $report['root_name'] !== '' ? mb_substr($report['root_name'], 0, 160) : 'Glavni tim',
                'title' => 'Root Manager',
                'generation' => 0,
                'country_code' => null,
                'sponsor_date' => null,
                'parent_fbo_id' => null,
                'tree_sequence' => '0',
                'is_manager' => 1,
                'is_privacy_requested' => 0,
                'email_hash' => null,
                'phone_hash' => null,
            ], $import_id, false);
        }

        foreach($report['members'] as $member) {
            forever_business_upsert_member($member, $import_id, $report['kind'] !== 'downline');
        }

        foreach($report['metrics'] as $metric) {
            $metric_update_columns = $report['kind'] === 'four_cc_active'
                ? ['personal_cc', 'total_active_cc', 'is_4cc_active', 'source_import_id', 'updated_at']
                : ($report['kind'] === 'focus_group'
                    /* Keep the prior official/downline import provenance when a
                     * Focus report only refreshes Personal CC. */
                    ? ['personal_cc', 'updated_at']
                    : ['personal_cc', 'total_cc', 'total_active_cc', 'non_manager_cc', 'leadership_cc', 'is_4cc_active', 'source_import_id', 'updated_at']);
            db()->onDuplicate($metric_update_columns)->insert('forever_business_metrics', [
                'fbo_id' => $metric['fbo_id'],
                'period_month' => $metric['period_month'],
                'personal_cc' => $metric['personal_cc'],
                'total_cc' => $metric['total_cc'],
                'total_active_cc' => $metric['total_active_cc'],
                'non_manager_cc' => $metric['non_manager_cc'],
                'leadership_cc' => $metric['leadership_cc'],
                'is_4cc_active' => $metric['is_4cc_active'] === null ? null : (int) $metric['is_4cc_active'],
                'source_import_id' => $import_id,
                'updated_at' => get_date(),
            ]);

            /* Only authoritative Downline / 4 CC imports can create a permanent
             * education enrollment. Focus remains diagnostic and can never
             * create an irreversible enrollment by itself. */
            if(in_array($report['kind'], ['downline', 'four_cc_active'], true)) {
                $enrollment_recorded = forever_business_record_vip_eligibility_metric(
                    (string) $metric['fbo_id'],
                    (string) $metric['period_month'],
                    $metric['personal_cc'],
                    (int) $import_id,
                    (string) $report['kind']
                );
                if((float) $metric['personal_cc'] >= .330
                    && forever_business_vip_eligibility_period_is_open((string) $metric['period_month'])
                    && !$enrollment_recorded) {
                    throw new \RuntimeException('VIP education enrollment could not be persisted.');
                }
            }
        }

        foreach($report['yearly_metrics'] as $metric) {
            db()->onDuplicate([
                'total_active_cc_ytd', 'non_manager_cc_ytd', 'leadership_cc_ytd', 'source_import_id', 'updated_at',
            ])->insert('forever_business_yearly_metrics', [
                'fbo_id' => $metric['fbo_id'],
                'period_year' => $metric['period_year'],
                'total_active_cc_ytd' => $metric['total_active_cc_ytd'],
                'non_manager_cc_ytd' => $metric['non_manager_cc_ytd'],
                'leadership_cc_ytd' => $metric['leadership_cc_ytd'],
                'source_import_id' => $import_id,
                'updated_at' => get_date(),
            ]);
        }

        foreach($report['focus_metrics'] as $metric) {
            db()->onDuplicate([
                'snapshot_date', 'next_level', 'enrollment_date', 'last_purchase_date', 'is_active', 'was_active_previous_month',
                'open_group_cc_2m', 'needed_cc_next_level', 'personal_cc', 'new_recruits', 'sponsor_fbo_id', 'sponsor_name',
                'source_import_id', 'updated_at',
            ])->insert('forever_business_focus_metrics', [
                'fbo_id' => $metric['fbo_id'],
                'period_month' => $metric['period_month'],
                'snapshot_date' => $metric['snapshot_date'],
                'next_level' => $metric['next_level'] ?: null,
                'enrollment_date' => $metric['enrollment_date'],
                'last_purchase_date' => $metric['last_purchase_date'],
                'is_active' => (int) $metric['is_active'],
                'was_active_previous_month' => (int) $metric['was_active_previous_month'],
                'open_group_cc_2m' => $metric['open_group_cc_2m'],
                'needed_cc_next_level' => $metric['needed_cc_next_level'],
                'personal_cc' => $metric['personal_cc'],
                'new_recruits' => (int) $metric['new_recruits'],
                'sponsor_fbo_id' => $metric['sponsor_fbo_id'],
                'sponsor_name' => $metric['sponsor_name'] ?: null,
                'source_import_id' => $import_id,
                'updated_at' => get_date(),
            ]);
        }

        if($report['kind'] === 'downline') {
            db()->delete('forever_business_hierarchy');
            $parent_map = $report['parent_map'];
            $root_id = $report['root_fbo_id'];
            $parent_map[$root_id] = null;

            foreach(array_keys($parent_map) as $descendant_id) {
                $current_id = $descendant_id;
                $depth = 0;
                $visited = [];

                while($current_id && !isset($visited[$current_id])) {
                    $visited[$current_id] = true;
                    db()->insert('forever_business_hierarchy', [
                        'ancestor_fbo_id' => $current_id,
                        'descendant_fbo_id' => $descendant_id,
                        'depth' => $depth,
                        'source_import_id' => $import_id,
                    ]);
                    $current_id = $parent_map[$current_id] ?? null;
                    $depth++;
                }
            }
        }

        db()->where('import_id', $import_id)->update('forever_business_imports', [
            'status' => 'completed',
            'completed_at' => get_date(),
        ]);
        db()->commit();
    } catch(\Throwable $exception) {
        db()->rollback();
        db()->where('import_id', $import_id)->update('forever_business_imports', [
            'status' => 'failed',
            'summary_json' => json_encode(['error' => mb_substr($exception->getMessage(), 0, 500)], JSON_UNESCAPED_UNICODE),
            'completed_at' => get_date(),
        ]);
        throw $exception;
    }

    try {
        forever_business_provision_fcc_members();
    } catch(\Throwable $exception) {
        error_log('Forever FCC placeholder provisioning failed after import: ' . $exception->getMessage());
    }

    forever_business_record_sync_check($report, $dedupe_sha256, (int) $import_id, false);

    return ['duplicate' => false, 'import_id' => (int) $import_id, 'summary' => $report['summary']];
}

/* Custom code: FC-2026-08-14: distinguish a successful source check from a data-changing import */
function forever_business_record_sync_check(array $report, string $file_sha256, int $import_id, bool $is_duplicate): void {
    try {
        db()->insert('forever_business_sync_checks', [
            'report_kind' => mb_substr((string) ($report['kind'] ?? 'report'), 0, 32),
            'original_name' => mb_substr((string) ($report['original_name'] ?? 'report'), 0, 255),
            'file_sha256' => $file_sha256,
            'import_id' => $import_id ?: null,
            'is_duplicate' => (int) $is_duplicate,
            'checked_at' => get_date(),
        ]);
    } catch(\Throwable $exception) {
        error_log('Forever sync check audit failed: ' . $exception->getMessage());
    }
}

function forever_business_format_zagreb_datetime(?string $datetime): string {
    $datetime = trim((string) $datetime);
    if($datetime === '') {
        return '';
    }

    try {
        return (new \DateTimeImmutable($datetime, new \DateTimeZone('UTC')))
            ->setTimezone(new \DateTimeZone('Europe/Zagreb'))
            ->format('d.m.Y. H:i');
    } catch(\Throwable $exception) {
        return '';
    }
}
/* /Custom code: FC-2026-08-14 */

function forever_business_extract_user_fbo_id($preferences): string {
    if(is_string($preferences)) {
        $preferences = json_decode($preferences ?: '{}');
    }
    if(is_array($preferences)) {
        $preferences = (object) $preferences;
    }
    $meta = is_object($preferences) ? ($preferences->meta ?? (object) []) : (object) [];
    if(is_array($meta)) {
        $meta = (object) $meta;
    }
    return forever_business_normalize_fbo_id($meta->foreverId ?? $meta->forever_id ?? $meta->foreverID ?? '');
}

/* Keep every SQL-side account audit aligned with the PHP extractor. The
 * expression is intentionally limited to two internal column spellings so a
 * caller can never inject a dynamic identifier into a query. */
function forever_business_user_fbo_sql_expression(string $column = 'preferences', bool $normalized = true): string {
    if(!in_array($column, ['preferences', 'u.preferences'], true)) {
        throw new \InvalidArgumentException('Unsupported preferences column.');
    }
    $raw = "TRIM(COALESCE(
        JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.meta.foreverId')),
        JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.meta.forever_id')),
        JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.meta.foreverID')),
        ''
    ))";
    return $normalized ? "REPLACE({$raw}, '-', '')" : $raw;
}

/* Custom code: FC-2026-08-21: permanent rolling VIP education enrollment */
function forever_business_resolve_unique_active_user_id_for_fbo(string $fbo_id): ?int {
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    if($fbo_id === '') return null;

    $escaped_fbo_id = database()->real_escape_string($fbo_id);
    $result = database()->query("SELECT COUNT(*) AS total, MIN(user_id) AS user_id
        FROM users
        WHERE status = 1
          AND JSON_VALID(preferences) = 1
          AND REPLACE(TRIM(COALESCE(
              JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverId')),
              JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.forever_id')),
              JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverID')),
              ''
          )), '-', '') = '{$escaped_fbo_id}'");
    $row = $result ? $result->fetch_assoc() : null;

    return (int) ($row['total'] ?? 0) === 1 ? (int) ($row['user_id'] ?? 0) : null;
}

function forever_business_get_active_user_link_count_for_fbo(string $fbo_id): int {
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    if($fbo_id === '') return 0;

    $escaped_fbo_id = database()->real_escape_string($fbo_id);
    $result = database()->query("SELECT COUNT(*) AS total
        FROM users
        WHERE status = 1
          AND JSON_VALID(preferences) = 1
          AND REPLACE(TRIM(COALESCE(
              JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverId')),
              JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.forever_id')),
              JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverID')),
              ''
          )), '-', '') = '{$escaped_fbo_id}'");
    $row = $result ? $result->fetch_assoc() : null;
    return (int) ($row['total'] ?? 0);
}

function forever_business_vip_eligibility_period_is_open(string $period, ?\DateTimeInterface $now = null): bool {
    $period = forever_business_period_from_label($period) ?: '';
    if($period === '') return false;

    $timezone = new \DateTimeZone('Europe/Zagreb');
    $current_time = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);

    return $period >= '2026-08-01' && $period <= $current_time->format('Y-m-01');
}

function forever_business_period_is_current_or_past(string $period, ?\DateTimeInterface $now = null): bool {
    $period = forever_business_period_from_label($period) ?: '';
    if($period === '') return false;

    $timezone = new \DateTimeZone('Europe/Zagreb');
    $current_time = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);

    return $period <= $current_time->format('Y-m-01');
}

function forever_business_record_vip_eligibility_metric(
    string $fbo_id,
    string $period,
    $personal_cc,
    ?int $source_import_id,
    string $qualification_source,
    ?int $linked_user_id = null
): bool {
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    $allowed_sources = ['downline', 'four_cc_active', 'member_cc', 'legacy_august_backfill'];
    $qualification_source = in_array($qualification_source, $allowed_sources, true) ? $qualification_source : '';
    if($fbo_id === '' || $period === '' || $qualification_source === '' || !is_numeric($personal_cc)) {
        return false;
    }

    if(!forever_business_vip_eligibility_period_is_open($period)) {
        return false;
    }

    $personal_cc = max(0, round((float) $personal_cc, 3));
    $existing = db()->where('fbo_id', $fbo_id)->getOne('forever_business_vip_enrollments');

    /* Once enrolled, later trusted snapshots update diagnostics only. A lower
     * month can never remove or suspend an enrollment. */
    if($personal_cc < .330) {
        if(!$existing) {
            return false;
        }

        $escaped_low_fbo_id = database()->real_escape_string($fbo_id);
        $escaped_low_period = database()->real_escape_string($period);
        $escaped_low_time = database()->real_escape_string(get_date());
        $low_personal_sql = number_format($personal_cc, 3, '.', '');
        $low_user_sql = $linked_user_id && $linked_user_id > 0 ? (string) (int) $linked_user_id : 'NULL';
        return (bool) database()->query("UPDATE `forever_business_vip_enrollments`
            SET `user_id` = COALESCE({$low_user_sql}, `user_id`),
                `last_verified_period` = '{$escaped_low_period}',
                `last_verified_personal_cc` = {$low_personal_sql},
                `updated_at` = '{$escaped_low_time}'
            WHERE `fbo_id` = '{$escaped_low_fbo_id}'
              AND `last_verified_period` <= '{$escaped_low_period}'");
    }

    $linked_user_id = $linked_user_id && $linked_user_id > 0 ? $linked_user_id : forever_business_resolve_unique_active_user_id_for_fbo($fbo_id);
    $escaped_fbo_id = database()->real_escape_string($fbo_id);
    $escaped_period = database()->real_escape_string($period);
    $escaped_source = database()->real_escape_string($qualification_source);
    $timestamp = database()->real_escape_string(get_date());
    $personal_sql = number_format($personal_cc, 3, '.', '');
    $user_sql = $linked_user_id && $linked_user_id > 0 ? (string) (int) $linked_user_id : 'NULL';
    $import_sql = $source_import_id && $source_import_id > 0 ? (string) (int) $source_import_id : 'NULL';

    $result = database()->query("INSERT INTO `forever_business_vip_enrollments`
        (`fbo_id`, `user_id`, `qualifying_period`, `qualifying_personal_cc`, `qualification_source`,
         `last_verified_period`, `last_verified_personal_cc`, `source_import_id`, `enrolled_at`, `created_at`, `updated_at`)
        VALUES ('{$escaped_fbo_id}', {$user_sql}, '{$escaped_period}', {$personal_sql}, '{$escaped_source}',
                '{$escaped_period}', {$personal_sql}, {$import_sql}, '{$timestamp}', '{$timestamp}', '{$timestamp}')
        ON DUPLICATE KEY UPDATE
            `user_id` = COALESCE(VALUES(`user_id`), `user_id`),
            `qualifying_personal_cc` = IF(VALUES(`qualifying_period`) < `qualifying_period`, VALUES(`qualifying_personal_cc`), `qualifying_personal_cc`),
            `qualification_source` = IF(VALUES(`qualifying_period`) < `qualifying_period`, VALUES(`qualification_source`), `qualification_source`),
            `source_import_id` = IF(VALUES(`qualifying_period`) < `qualifying_period`, VALUES(`source_import_id`), `source_import_id`),
            `qualifying_period` = LEAST(`qualifying_period`, VALUES(`qualifying_period`)),
            `last_verified_personal_cc` = IF(VALUES(`last_verified_period`) >= `last_verified_period`, VALUES(`last_verified_personal_cc`), `last_verified_personal_cc`),
            `last_verified_period` = GREATEST(`last_verified_period`, VALUES(`last_verified_period`)),
            `updated_at` = VALUES(`updated_at`)");

    return (bool) $result;
}
/* /Custom code: FC-2026-08-21 */

function forever_business_get_periods(?\DateTimeInterface $now = null): array {
    forever_business_ensure_tables();
    $current_zagreb_period = forever_business_current_zagreb_period($now);
    $rows = database()->query("SELECT period_month FROM forever_business_metrics WHERE period_month <= '{$current_zagreb_period}' UNION SELECT period_month FROM forever_business_total_cc_snapshots WHERE period_month <= '{$current_zagreb_period}' ORDER BY period_month DESC");
    /* The open month must exist in the selector even before its first order or
     * FLP snapshot. No database row is written merely to render a zero. */
    $periods = [$current_zagreb_period];
    while($rows && $row = $rows->fetch_assoc()) {
        $row_period = (string) $row['period_month'];
        if($row_period !== '' && !in_array($row_period, $periods, true)) {
            $periods[] = $row_period;
        }
    }
    return $periods;
}

function forever_business_get_user_access_roots(int $user_id): array {
    forever_business_ensure_tables();
    return db()->join('forever_business_members m', 'm.fbo_id = a.fbo_id', 'LEFT')
        ->where('a.user_id', $user_id)
        ->where('a.status', 'active')
        ->orderBy('m.name', 'ASC')
        ->get('forever_business_access a', null, ['a.access_id', 'a.fbo_id', 'a.access_role', 'm.name', 'm.title']) ?? [];
}

function forever_business_get_scope_ids(int $user_id, bool $is_admin, string $requested_root = ''): array {
    forever_business_ensure_tables();
    $requested_root = forever_business_normalize_fbo_id($requested_root);

    if($is_admin) {
        if($requested_root !== '') {
            $rows = db()->where('ancestor_fbo_id', $requested_root)->get('forever_business_hierarchy', null, ['descendant_fbo_id']) ?? [];
            return !empty($rows) ? array_values(array_unique(array_map(static fn($row) => (string) $row->descendant_fbo_id, $rows))) : [$requested_root];
        }
        $rows = db()->where('is_in_current_structure', 1)->get('forever_business_members', null, ['fbo_id']) ?? [];
        return array_values(array_unique(array_map(static fn($row) => (string) $row->fbo_id, $rows)));
    }

    /* Privacy contract: every non-admin account is permanently scoped to the
       Forever ID stored in that account. Query parameters and legacy manager
       access records must never expand this scope. */
    $user = db()->where('user_id', $user_id)->getOne('users', ['preferences']);
    $own_fbo_id = $user ? forever_business_extract_user_fbo_id($user->preferences ?? null) : '';
    return $own_fbo_id !== '' ? [$own_fbo_id] : [];
}

function forever_business_safe_id_list(array $ids): string {
    $ids = array_values(array_unique(array_filter(array_map('forever_business_normalize_fbo_id', $ids))));
    return empty($ids) ? "''" : "'" . implode("','", $ids) . "'";
}

function forever_business_provision_fcc_members(?int $only_user_id = null): int {
    forever_business_ensure_tables();
    $now = database()->real_escape_string(get_date());
    $user_filter = $only_user_id !== null ? ' AND user_id = ' . max(0, $only_user_id) : '';
    $result = database()->query("INSERT IGNORE INTO forever_business_members
        (fbo_id, name, title, generation, country_code, sponsor_date, parent_fbo_id, tree_sequence,
         is_manager, is_privacy_requested, is_in_current_structure, email_hash, phone_hash,
         first_seen_import_id, last_seen_import_id, created_at, updated_at)
        SELECT
            REPLACE(TRIM(COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverId')),
                JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.forever_id')),
                JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverID')),
                ''
            )), '-', ''),
            LEFT(name, 160), 'FCC suradnik', NULL, NULL, NULL, NULL, NULL,
            0, 0, 0, NULL, NULL, NULL, NULL, '{$now}', '{$now}'
        FROM users
        WHERE type = 0 AND status = 1 AND JSON_VALID(preferences) = 1
          {$user_filter}
          AND REPLACE(TRIM(COALESCE(
              JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverId')),
              JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.forever_id')),
              JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverID')),
              ''
          )), '-', '') REGEXP '^[0-9]{12}$'");
    return $result ? max(0, (int) database()->affected_rows) : 0;
}

/* Return only machine-safe identifiers required for live CC synchronization.
 * Names, email addresses and other account data never leave FCC through the
 * sync endpoint. Shared approved Forever IDs are intentionally collapsed to
 * one FLP360 lookup and remain valid for every linked active account. */
function forever_business_get_registered_sync_accounts(string $period): array {
    forever_business_ensure_tables();
    forever_business_provision_fcc_members();
    $period = forever_business_period_from_label($period) ?: '';
    if($period === '' || !forever_business_period_is_current_or_past($period)) {
        throw new \InvalidArgumentException('Neispravan mjesec za FCC account CC provjeru.');
    }

    $escaped_period = database()->real_escape_string($period);
    $result = database()->query("SELECT account.fbo_id,
            COUNT(*) AS active_link_count,
            COALESCE(MAX(NULLIF(UPPER(TRIM(member.country_code)), '')), MAX(NULLIF(UPPER(TRIM(account.account_country_code)), ''))) AS country_code,
            MAX(metric.period_month) AS metric_period,
            MAX(metric.personal_cc) AS personal_cc,
            MAX(metric.total_cc) AS total_cc,
            MAX(metric.total_active_cc) AS total_active_cc,
            MAX(metric.non_manager_cc) AS non_manager_cc,
            MAX(metric.leadership_cc) AS leadership_cc,
            MAX(metric.is_4cc_active) AS is_4cc_active,
            MAX(yearly.total_active_cc_ytd) AS total_active_cc_ytd,
            MAX(yearly.non_manager_cc_ytd) AS non_manager_cc_ytd,
            MAX(yearly.leadership_cc_ytd) AS leadership_cc_ytd,
            MAX(CASE WHEN enrollment.fbo_id IS NULL THEN 0 ELSE 1 END) AS is_vip_enrolled
        FROM (
            SELECT user_id,
                   UPPER(TRIM(COALESCE(country, ''))) AS account_country_code,
                   REPLACE(TRIM(COALESCE(
                       JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverId')),
                       JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.forever_id')),
                       JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverID')),
                       ''
                   )), '-', '') AS fbo_id
            FROM users
            WHERE type = 0 AND status = 1 AND JSON_VALID(preferences) = 1
        ) account
        LEFT JOIN forever_business_members member ON member.fbo_id = account.fbo_id
        LEFT JOIN forever_business_metrics metric ON metric.fbo_id = account.fbo_id AND metric.period_month = '{$escaped_period}'
        LEFT JOIN forever_business_yearly_metrics yearly ON yearly.fbo_id = account.fbo_id AND yearly.period_year = YEAR('{$escaped_period}')
        LEFT JOIN forever_business_vip_enrollments enrollment ON enrollment.fbo_id = account.fbo_id
        WHERE account.fbo_id REGEXP '^360[0-9]{9}$'
        GROUP BY account.fbo_id
        ORDER BY account.fbo_id ASC");
    if(!$result) {
        throw new \RuntimeException('Aktivni FCC Forever ID-jevi nisu dostupni za sinkronizaciju.');
    }

    $accounts = [];
    while($row = $result->fetch_assoc()) {
        $accounts[] = [
            'fbo_id' => (string) $row['fbo_id'],
            'country_code' => (string) ($row['country_code'] ?? ''),
            'active_link_count' => max(1, (int) ($row['active_link_count'] ?? 1)),
            'metric_period' => $row['metric_period'] ?: null,
            'personal_cc' => $row['personal_cc'] === null ? null : round((float) $row['personal_cc'], 3),
            'total_cc' => $row['total_cc'] === null ? null : round((float) $row['total_cc'], 3),
            'total_active_cc' => $row['total_active_cc'] === null ? null : round((float) $row['total_active_cc'], 3),
            'non_manager_cc' => $row['non_manager_cc'] === null ? null : round((float) $row['non_manager_cc'], 3),
            'leadership_cc' => $row['leadership_cc'] === null ? null : round((float) $row['leadership_cc'], 3),
            'is_4cc_active' => $row['is_4cc_active'] === null ? null : (int) $row['is_4cc_active'] === 1,
            'total_active_cc_ytd' => $row['total_active_cc_ytd'] === null ? null : round((float) $row['total_active_cc_ytd'], 3),
            'non_manager_cc_ytd' => $row['non_manager_cc_ytd'] === null ? null : round((float) $row['non_manager_cc_ytd'], 3),
            'leadership_cc_ytd' => $row['leadership_cc_ytd'] === null ? null : round((float) $row['leadership_cc_ytd'], 3),
            'is_vip_enrolled' => !empty($row['is_vip_enrolled']),
        ];
    }
    return $accounts;
}

function forever_business_has_verified_four_cc_activity(array $member): bool {
    /* An explicit FLP360 result always wins, including 0. Only when the
     * official signal is genuinely unknown may the complete supporting values
     * provide the conservative 1 Personal CC + 4 Total Active CC fallback. */
    if(array_key_exists('is_4cc_active', $member) && $member['is_4cc_active'] !== null) {
        return (int) $member['is_4cc_active'] === 1;
    }

    return isset($member['personal_cc'], $member['total_active_cc'])
        && (float) $member['personal_cc'] >= 1.0
        && (float) $member['total_active_cc'] >= 4.0;
}

function forever_business_get_verified_progress(array $member): array {
    $personal_cc = isset($member['personal_cc']) ? (float) $member['personal_cc'] : null;
    $total_active_cc = isset($member['total_active_cc']) ? (float) $member['total_active_cc'] : null;
    $has_official_activity_data = array_key_exists('is_4cc_active', $member) && $member['is_4cc_active'] !== null;
    $official_activity_signal = $has_official_activity_data ? ((int) $member['is_4cc_active'] === 1 ? 1 : 0) : null;
    $has_formula_data = $personal_cc !== null && $total_active_cc !== null;
    $has_activity_data = $has_official_activity_data || $personal_cc !== null || $total_active_cc !== null;
    $meets_activity_formula = $has_formula_data && $personal_cc >= 1 && $total_active_cc >= 4;
    $is_four_cc_active = forever_business_has_verified_four_cc_activity($member);
    $is_officially_active = $official_activity_signal === 1;
    $activity_source = $has_official_activity_data ? 'official' : ($has_formula_data ? 'formula' : 'unknown');

    $current_total_cc = isset($member['total_cc']) ? (float) $member['total_cc'] : null;
    $previous_total_cc = isset($member['previous_total_cc']) ? (float) $member['previous_total_cc'] : null;
    $two_months_ago_total_cc = isset($member['two_months_ago_total_cc']) ? (float) $member['two_months_ago_total_cc'] : null;
    $three_months_ago_total_cc = isset($member['three_months_ago_total_cc']) ? (float) $member['three_months_ago_total_cc'] : null;
    $title = trim((string) ($member['title'] ?? ''));
    $title_key = mb_strtolower($title);
    $is_assistant_manager = str_contains($title_key, 'assistant manager');
    $is_unrecognized_manager = str_contains($title_key, 'unrecognized manager');
    $is_manager_candidate = $is_assistant_manager || $is_unrecognized_manager;
    $is_full_manager = !$is_manager_candidate && str_contains($title_key, 'manager');
    $windows = [];
    $next_title = 'Supervisor';
    $rank_mode = 'rank';

    $make_window = static function(string $label, ?float $current, float $target, bool $complete, string $metric): array {
        $current = $complete ? max(0, (float) $current) : null;
        return [
            'label' => $label,
            'metric' => $metric,
            'current' => $current,
            'target' => $target,
            'gap' => $complete ? max(0, round($target - $current, 3)) : null,
            'progress' => $complete ? min(100, round(($current / $target) * 100, 1)) : 0,
            'complete' => $complete,
            'achieved' => $complete && $current >= $target,
        ];
    };

    if($is_full_manager) {
        $rank_mode = 'manager';
        $non_manager_cc = isset($member['non_manager_cc']) ? (float) $member['non_manager_cc'] : null;
        $target = $non_manager_cc !== null && $non_manager_cc >= 60 ? 100.0 : 60.0;
        $next_title = $target === 100.0 ? '100 CC Non-Manager cilj' : '60 CC Non-Manager cilj';
        $windows[] = $make_window('Ovaj mjesec', $non_manager_cc, $target, $non_manager_cc !== null, 'Non-Manager CC');
    } elseif($is_manager_candidate) {
        $next_title = $is_unrecognized_manager ? 'Recognized Manager' : 'Manager';
        $two_complete = $current_total_cc !== null && $previous_total_cc !== null;
        $four_complete = $two_complete && $two_months_ago_total_cc !== null && $three_months_ago_total_cc !== null;
        $two_total = $two_complete ? $current_total_cc + $previous_total_cc : null;
        $four_total = $four_complete ? $current_total_cc + $previous_total_cc + $two_months_ago_total_cc + $three_months_ago_total_cc : null;
        $windows[] = $make_window('Put A · 2 kalendarska mjeseca', $two_total, 120.0, $two_complete, 'Total CC');
        $windows[] = $make_window('Put B · 4 kalendarska mjeseca', $four_total, 150.0, $four_complete, 'Total CC');
    } elseif($title_key === 'supervisor') {
        $next_title = 'Assistant Manager';
        $complete = $current_total_cc !== null && $previous_total_cc !== null;
        $windows[] = $make_window('2 kalendarska mjeseca', $complete ? $current_total_cc + $previous_total_cc : null, 60.0, $complete, 'Total CC');
    } else {
        $next_title = 'Supervisor';
        $windows[] = $make_window('Ovaj mjesec', $current_total_cc, 10.0, $current_total_cc !== null, 'Total CC');
    }

    return [
        'has_activity_data' => $has_activity_data,
        'has_official_activity_data' => $has_official_activity_data,
        'official_activity_signal' => $official_activity_signal,
        'activity_source' => $activity_source,
        'personal_cc' => $personal_cc,
        'total_active_cc' => $total_active_cc,
        'personal_progress' => $personal_cc !== null ? min(100, round(($personal_cc / 1) * 100, 1)) : 0,
        'regional_progress' => $total_active_cc !== null ? min(100, round(($total_active_cc / 4) * 100, 1)) : 0,
        'personal_gap' => $personal_cc !== null ? max(0, round(1 - $personal_cc, 3)) : null,
        'regional_gap' => $total_active_cc !== null ? max(0, round(4 - $total_active_cc, 3)) : null,
        'meets_activity_formula' => $meets_activity_formula,
        'is_officially_active' => $is_officially_active,
        'is_4cc_active' => $is_four_cc_active,
        'activity_source_consistent' => $has_official_activity_data && $has_formula_data
            ? ($official_activity_signal === 1) === $meets_activity_formula
            : null,
        'rank' => [
            'mode' => $rank_mode,
            'current_title' => $title ?: 'Bez statusa',
            'next_title' => $next_title,
            'windows' => $windows,
        ],
    ];
}

/* Custom code: FC-2026-08-14: Self-only 4 CC notice for the main dashboard */
function forever_business_get_user_activity_notice(int $user_id): array {
    forever_business_ensure_tables();

    $empty_notice = [
        'status' => 'unavailable',
        'has_data' => false,
        'period' => null,
        'is_active' => false,
        'is_officially_active' => false,
        'personal_cc' => null,
        'total_active_cc' => null,
        'personal_gap' => null,
        'regional_gap' => null,
        'progress' => 0,
        'progress_basis' => 'activity',
        'has_regional_data' => false,
        'activity_source' => 'unknown',
        'official_activity_signal' => null,
    ];

    if($user_id <= 0) return $empty_notice;

    /* The Forever ID is always resolved from the signed-in account. No request
     * parameter or team-access record can expand this query to another person. */
    $user = db()->where('user_id', $user_id)->getOne('users', ['preferences']);
    $fbo_id = $user ? forever_business_extract_user_fbo_id($user->preferences ?? null) : '';
    if($fbo_id === '') return $empty_notice;

    $period = forever_business_get_periods()[0] ?? null;
    if(!$period) return $empty_notice;

    $member = db()->join('forever_business_metrics metric', "metric.fbo_id = member.fbo_id AND metric.period_month = '{$period}'", 'LEFT')
        ->join('forever_business_imports metric_source', 'metric_source.import_id = metric.source_import_id', 'LEFT')
        ->where('member.fbo_id', $fbo_id)
        ->getOne('forever_business_members member', [
            'member.fbo_id',
            'member.title',
            'metric.personal_cc',
            'metric.total_cc',
            'metric.total_active_cc',
            'metric.non_manager_cc',
            'metric.leadership_cc',
            "CASE WHEN metric.source_import_id IS NOT NULL AND (metric_source.import_id IS NULL OR metric_source.report_kind NOT IN ('downline', 'four_cc_active')) THEN NULL ELSE metric.is_4cc_active END AS is_4cc_active",
        ]);

    if(!$member) {
        $empty_notice['period'] = $period;
        return $empty_notice;
    }

    $member = forever_business_normalize_current_month_metrics((array) $member, $period);
    $progress = forever_business_get_verified_progress($member);
    $has_personal_data = $progress['personal_cc'] !== null;
    $has_regional_data = $progress['total_active_cc'] !== null;
    $has_official_activity_data = !empty($progress['has_official_activity_data']);
    if(!$has_personal_data && !$has_regional_data && !$has_official_activity_data) {
        $empty_notice['period'] = $period;
        return $empty_notice;
    }

    $status = $progress['is_4cc_active']
        ? 'active'
        : ($progress['official_activity_signal'] === 0 && $progress['meets_activity_formula'] ? 'pending' : 'inactive');

    $progress_basis = $has_regional_data ? 'activity' : 'personal';
    $notice_progress = $progress['is_4cc_active']
        ? 100
        : ($has_regional_data
            ? min((float) $progress['personal_progress'], (float) $progress['regional_progress'])
            : (float) $progress['personal_progress']);

    return [
        'status' => $status,
        'has_data' => true,
        'period' => $period,
        'is_active' => (bool) $progress['is_4cc_active'],
        'is_officially_active' => (bool) $progress['is_officially_active'],
        'personal_cc' => $progress['personal_cc'],
        'total_active_cc' => $progress['total_active_cc'],
        'personal_gap' => $progress['personal_gap'],
        'regional_gap' => $progress['regional_gap'],
        'progress' => $notice_progress,
        'progress_basis' => $progress_basis,
        'has_regional_data' => $has_regional_data,
        'activity_source' => $progress['activity_source'],
        'official_activity_signal' => $progress['official_activity_signal'],
    ];
}
/* /Custom code: FC-2026-08-14 */

/* Custom code: FC-2026-08-15: VIP 4 Core launch and eligibility gate */
function forever_business_vip_whatsapp_group_url(): string {
    return 'https://chat.whatsapp.com/G0Mxgm8yXfrIDAOxNqPbmw?mode=gi_t';
}

function forever_business_vip_education_url(): string {
    return 'https://forevercard.club/forever-business';
}

function forever_business_vip_webinar_url(): string {
    return 'https://forevercard.club/vip-edukacija';
}

function forever_business_vip_period_label(string $period): string {
    $labels = [
        1 => 'siječanj', 2 => 'veljača', 3 => 'ožujak', 4 => 'travanj',
        5 => 'svibanj', 6 => 'lipanj', 7 => 'srpanj', 8 => 'kolovoz',
        9 => 'rujan', 10 => 'listopad', 11 => 'studeni', 12 => 'prosinac',
    ];

    try {
        $date = new \DateTimeImmutable($period);
        return ($labels[(int) $date->format('n')] ?? $date->format('m')) . ' ' . $date->format('Y') . '.';
    } catch(\Throwable $exception) {
        return 'potvrđeno razdoblje';
    }
}

function forever_business_build_vip_program_state(
    ?float $personal_cc,
    ?\DateTimeInterface $now = null,
    ?array $enrollment = null,
    bool $has_valid_linkage = true,
    int $active_link_count = 1,
    ?string $current_period = null
): array {
    $timezone = new \DateTimeZone('Europe/Zagreb');
    $current_time = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);
    $launch_at = new \DateTimeImmutable('2026-09-01 00:00:00', $timezone);
    $threshold = 0.33;
    $has_data = $personal_cc !== null;
    $personal_cc = $has_data ? max(0, round((float) $personal_cc, 3)) : null;
    /* NULL enrollment keeps pure preview/test calls backward compatible. An
     * array, including an empty one, activates the production persistence gate. */
    $uses_persistent_gate = $enrollment !== null;
    $is_enrolled = $uses_persistent_gate
        ? !empty($enrollment['fbo_id'])
        : ($has_data && $personal_cc >= $threshold);
    $is_eligible = $is_enrolled;
    $qualifying_period = $is_enrolled ? (string) ($enrollment['qualifying_period'] ?? $current_period ?? '2026-08-01') : null;
    $qualifying_personal_cc = $is_enrolled
        ? (float) ($enrollment['qualifying_personal_cc'] ?? $personal_cc ?? $threshold)
        : null;
    $eligibility_period = $qualifying_period ?: ($current_period ?: $current_time->format('Y-m-01'));
    try {
        $eligibility_period_label = (new \DateTimeImmutable($eligibility_period))->format('m/Y');
    } catch(\Throwable $exception) {
        $eligibility_period_label = '';
    }
    $is_launched = $current_time >= $launch_at;
    $seconds_remaining = $is_launched ? 0 : max(0, $launch_at->getTimestamp() - $current_time->getTimestamp());

    if(!$has_valid_linkage && $active_link_count > 1) {
        $status = 'duplicate_linkage';
    } elseif(!$has_valid_linkage) {
        $status = 'missing_linkage';
    } elseif($is_enrolled && $is_launched) {
        $status = 'active';
    } elseif($is_enrolled) {
        $status = 'qualified_waiting';
    } elseif(!$has_data) {
        $status = 'waiting_data';
    } elseif($personal_cc >= $threshold) {
        $status = 'waiting_confirmation';
    } elseif($is_launched) {
        $status = 'locked';
    } else {
        $status = 'needs_progress';
    }

    return [
        'status' => $status,
        'eligibility_period' => $eligibility_period,
        'eligibility_period_label' => $eligibility_period_label,
        'qualifying_period' => $qualifying_period,
        'qualifying_personal_cc' => $qualifying_personal_cc,
        'qualification_source' => $is_enrolled ? (string) ($enrollment['qualification_source'] ?? 'preview') : null,
        'enrolled_at' => $is_enrolled ? ($enrollment['enrolled_at'] ?? null) : null,
        'current_period' => $current_period,
        'current_personal_cc' => $personal_cc,
        'threshold_cc' => $threshold,
        'personal_cc' => $personal_cc,
        'gap_cc' => $is_enrolled ? 0.0 : ($has_data ? max(0, round($threshold - $personal_cc, 3)) : null),
        'progress' => $is_enrolled ? 100.0 : ($has_data ? min(100, round(($personal_cc / $threshold) * 100, 1)) : 0.0),
        'has_data' => $has_data,
        'is_enrolled' => $is_enrolled,
        'is_eligible' => $is_eligible,
        'is_launched' => $is_launched,
        'has_valid_linkage' => $has_valid_linkage,
        'active_link_count' => max(0, $active_link_count),
        'linkage_status' => $has_valid_linkage
            ? ($active_link_count > 1 ? 'shared' : 'valid')
            : ($active_link_count > 1 ? 'duplicate' : 'missing'),
        'is_shared_linkage' => $has_valid_linkage && $active_link_count > 1,
        'can_access_education' => $is_launched && $is_enrolled && $has_valid_linkage,
        'launch_at_iso' => $launch_at->format(\DateTimeInterface::ATOM),
        'server_now_iso' => $current_time->format(\DateTimeInterface::ATOM),
        'launch_at_display' => '1. rujna 2026. u 00:00',
        'seconds_remaining' => $seconds_remaining,
        'whatsapp_group_url' => forever_business_vip_whatsapp_group_url(),
        'education_url' => forever_business_vip_education_url(),
        'webinar_url' => forever_business_vip_webinar_url(),
        'marketing_plan' => forever_business_get_marketing_plan_state($current_time),
    ];
}

function forever_business_get_vip_program_state(int $user_id, ?\DateTimeInterface $now = null): array {
    forever_business_ensure_tables();

    $state = forever_business_build_vip_program_state(null, $now, [], false, 0);
    $state['has_linked_id'] = false;
    $state['fbo_id'] = '';
    if($user_id <= 0) return $state;

    /* Access is always resolved from the signed-in account and an immutable
     * FBO enrollment. A selected dashboard month or hidden form field cannot
     * change it. Every active FCC account has an administrator-approved
     * Forever ID; approved shared IDs intentionally expose the same CC data
     * and VIP program to each linked account. */
    $user = db()->where('user_id', $user_id)->where('status', 1)->getOne('users', ['preferences']);
    $fbo_id = $user ? forever_business_extract_user_fbo_id($user->preferences ?? null) : '';
    if($fbo_id === '') return $state;

    $timezone = new \DateTimeZone('Europe/Zagreb');
    $current_time = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);
    $current_month = $current_time->format('Y-m-01');
    $metric = db()->where('fbo_id', $fbo_id)
        ->where('period_month', $current_month)
        ->getOne('forever_business_metrics', ['period_month', 'personal_cc']);
    $enrollment_row = db()->where('fbo_id', $fbo_id)->getOne('forever_business_vip_enrollments');
    $enrollment = $enrollment_row ? (array) $enrollment_row : [];
    /* Access remains anchored to the permanent enrollment, but the visible
     * current-month progress always starts at zero and never falls back to a
     * prior month's Personal CC. */
    $personal_cc = $metric && $metric->personal_cc !== null ? (float) $metric->personal_cc : 0.0;
    $active_link_count = forever_business_get_active_user_link_count_for_fbo($fbo_id);
    $has_valid_linkage = $active_link_count >= 1;

    /* The enrollment belongs to the Forever ID. Its optional user_id is only
     * synchronized when the ID has a single account, so shared accounts never
     * overwrite one another while opening the page. */
    if($enrollment_row && $active_link_count === 1 && (int) ($enrollment_row->user_id ?? 0) !== $user_id) {
        db()->where('fbo_id', $fbo_id)->update('forever_business_vip_enrollments', [
            'user_id' => $user_id,
            'updated_at' => get_date(),
        ]);
        $enrollment['user_id'] = $user_id;
    }

    $state = forever_business_build_vip_program_state(
        $personal_cc,
        $now,
        $enrollment,
        $has_valid_linkage,
        $active_link_count,
        $current_month
    );
    $state['has_linked_id'] = true;
    $state['fbo_id'] = $fbo_id;
    return $state;
}
/* /Custom code: FC-2026-08-15 */

/* Custom code: FC-2026-08-15: Full 30-step VIP education program */
function forever_business_get_marketing_plan_state(?\DateTimeInterface $now = null): array {
    $timezone = new \DateTimeZone('Europe/Zagreb');
    $current_time = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);
    $first_event = new \DateTimeImmutable('2026-09-06 18:00:00', $timezone);
    $calendar_sunday = $current_time->modify('sunday this week')->setTime(18, 0);
    $this_event = $calendar_sunday < $first_event ? $first_event : $calendar_sunday;
    $is_event_date = $current_time->format('Y-m-d') === $this_event->format('Y-m-d');
    $event_end = $this_event->modify('+90 minutes');
    $next_event = $this_event;

    if($current_time >= $event_end) {
        $next_event = $this_event->modify('+1 week');
    }

    return [
        'weekday' => 7,
        'weekday_label' => 'svake nedjelje',
        'time_label' => '18:00',
        'timezone' => 'Europe/Zagreb',
        'first_at_iso' => $first_event->format(\DateTimeInterface::ATOM),
        'is_today' => $is_event_date && $current_time->format('Y-m-d') >= $first_event->format('Y-m-d'),
        'is_live_window' => $is_event_date && $current_time >= $this_event && $current_time < $event_end,
        'can_record_outcome' => $is_event_date && $current_time >= $event_end,
        'completion_available_at_iso' => $event_end->format(\DateTimeInterface::ATOM),
        'completion_available_at_display' => $event_end->format('H:i'),
        'next_at_iso' => $next_event->format(\DateTimeInterface::ATOM),
        'next_at_display' => 'nedjelja, ' . $next_event->format('d.m.Y.') . ' u 18:00',
        'url' => forever_business_vip_webinar_url(),
    ];
}

/* Custom code: FC-2026-08-25: approved and qualified VIP 4 Core email delivery */
function forever_business_vip_build_email_message(object $user, array $state, string $event_key): array {
    $name = htmlspecialchars(str_replace('.', '. ', trim((string) ($user->name ?? ''))), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $name = $name !== '' ? $name : 'suradniče/suradnice';
    $education_url = forever_business_vip_education_url();
    $webinar_url = forever_business_vip_webinar_url();
    $whatsapp_url = forever_business_vip_whatsapp_group_url();
    $qualifying_period = (string) ($state['qualifying_period'] ?? $state['eligibility_period'] ?? '');
    $period_label = htmlspecialchars(forever_business_vip_period_label($qualifying_period), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $qualifying_cc = $state['qualifying_personal_cc'] ?? $state['personal_cc'] ?? 0;
    $personal_cc = number_format((float) $qualifying_cc, 3, ',', '.');

    if($event_key === 'qualified') {
        $launch_copy = !empty($state['is_launched'])
            ? 'Tvoj trenutačni zadatak već te čeka na stranici <strong>Moj Forever</strong>.'
            : 'Edukacija počinje <strong>1. rujna 2026.</strong>, a do tada se možeš odmah pridružiti VIP grupi i pripremiti za prvi korak.';

        return [
            'subject' => 'Tvoja VIP 4 Core edukacija je otključana',
            'body' => 'Pozdrav ' . $name . ',<br /><br />

Tvoj potvrđeni osobni promet za <strong>' . $period_label . '</strong> iznosi <strong>' . $personal_cc . ' CC</strong>, zato je uvjet od najmanje <strong>0,330 osobnog CC</strong> ispunjen. ' . $launch_copy . '<br /><br />

<strong>Kako edukacija funkcionira:</strong><br />
1. Na stranici <strong>Moj Forever</strong> vidiš svoje potvrđene CC bodove i jedan jasan zadatak za taj dan.<br />
2. Zadatak ostaje otvoren dok ga ne dovršiš. Nakon potvrde, sljedeći korak otvara se idućeg dana.<br />
3. Zadaci se izmjenjuju kroz sva četiri područja 4 Corea: Recruitment, Retention, Productivity i Development.<br />
4. Svake nedjelje u 18:00 održava se online Marketing plan na koji možeš pozvati osobe zainteresirane za Forever poslovanje.<br />
5. VIP WhatsApp grupa služi za kratke obavijesti, pitanja i podršku tijekom izvršavanja zadataka.<br /><br />

<div style="margin:20px 0;">
    <a href="' . $education_url . '" style="display:inline-block;margin:0 8px 8px 0;padding:13px 18px;border-radius:10px;background:#153a4d;color:#ffffff;text-decoration:none;font-weight:700;">Otvori Moj Forever i edukaciju</a>
    <a href="' . $whatsapp_url . '" style="display:inline-block;margin:0 8px 8px 0;padding:13px 18px;border-radius:10px;background:#25D366;color:#082b18;text-decoration:none;font-weight:700;">Pridruži se VIP WhatsApp grupi</a>
</div>

<strong>Tjedni Marketing plan:</strong> svake nedjelje u 18:00, počevši 6. rujna 2026.<br />
Izravna poveznica: <a href="' . $webinar_url . '">' . $webinar_url . '</a><br /><br />

Za početak otvori Moj Forever, provjeri svoje bodove i pročitaj prvi zadatak. Ako ti nešto nije jasno, napiši pitanje u VIP grupu.<br /><br />

Vidimo se u edukaciji!<br />
Tim Forever Card Cluba',
        ];
    }

    return [
        'subject' => 'Tvoj pristup stranici Moj Forever je spreman',
        'body' => 'Pozdrav ' . $name . ',<br /><br />

Tvoj pristup Forever Card Clubu je odobren i od sada na stranici <strong>Moj Forever</strong> možeš pratiti svoje potvrđene CC bodove, 4 CC aktivnost i napredak prema sljedećoj razini.<br /><br />

Na istom mjestu nalazi se i vođena VIP 4 Core edukacija. Za otključavanje edukacije i pristupa VIP WhatsApp grupi potrebno je imati najmanje <strong>0,330 osobnog CC</strong> na svojem Forever ID-u u jednom kvalifikacijskom mjesecu. FCC taj uvjet provjerava automatski nakon osvježavanja FLP360 podataka.<br /><br />

Kada ispuniš uvjet, dobit ćeš zaseban e-mail s poveznicom za VIP WhatsApp grupu i izravnim pristupom dnevnim zadacima.<br /><br />

<div style="margin:20px 0;">
    <a href="' . $education_url . '" style="display:inline-block;padding:13px 18px;border-radius:10px;background:#153a4d;color:#ffffff;text-decoration:none;font-weight:700;">Otvori Moj Forever</a>
</div>

U Moj Foreveru uvijek provjeri i vrijeme posljednjeg osvježavanja kako bi znao/la do kada su bodovi ažurirani.<br /><br />

Lijep pozdrav,<br />
Tim Forever Card Cluba',
    ];
}

function forever_business_vip_prepare_email_delivery(int $user_id, string $event_key, array $state): ?object {
    if($user_id <= 0 || !in_array($event_key, ['approved_pending', 'qualified'], true)) return null;
    forever_business_ensure_tables();

    $timestamp = get_date();
    $eligibility_period = $event_key === 'qualified'
        ? ($state['qualifying_period'] ?? $state['eligibility_period'] ?? null)
        : ($state['current_period'] ?? $state['eligibility_period'] ?? null);
    $personal_cc = $event_key === 'qualified'
        ? ($state['qualifying_personal_cc'] ?? $state['personal_cc'] ?? null)
        : ($state['current_personal_cc'] ?? $state['personal_cc'] ?? null);

    db()->onDuplicate(['fbo_id', 'eligibility_period', 'personal_cc', 'updated_at'])->insert('forever_business_vip_email_deliveries', [
        'user_id' => $user_id,
        'event_key' => $event_key,
        'fbo_id' => !empty($state['fbo_id']) ? (string) $state['fbo_id'] : null,
        'eligibility_period' => !empty($eligibility_period) ? (string) $eligibility_period : null,
        'personal_cc' => $personal_cc,
        'status' => 'pending',
        'attempts' => 0,
        'message_id' => null,
        'last_error' => null,
        'last_attempt_at' => null,
        'sent_at' => null,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    return db()->where('user_id', $user_id)->where('event_key', $event_key)->getOne('forever_business_vip_email_deliveries');
}

function forever_business_vip_send_notification_for_user(int $user_id, string $trigger = 'qualified'): bool {
    if($user_id <= 0 || !in_array($trigger, ['approved', 'qualified'], true)) return false;
    forever_business_ensure_tables();

    $user = db()->where('user_id', $user_id)->where('status', 1)->getOne('users', ['user_id', 'name', 'email', 'language', 'anti_phishing_code', 'preferences']);
    if(!$user || !filter_var((string) ($user->email ?? ''), FILTER_VALIDATE_EMAIL)) {
        /* A terminal recipient problem must leave the retryable queue. Otherwise
         * the same oldest rows can consume the cron limit forever and starve
         * newly qualified accounts. The marker allows a repaired account to be
         * re-queued safely below. */
        db()->where('user_id', $user_id)
            ->where('status', ['pending', 'failed'], 'IN')
            ->update('forever_business_vip_email_deliveries', [
                'status' => 'superseded',
                'last_error' => 'recipient_unavailable: inactive account or invalid email',
                'updated_at' => get_date(),
            ]);
        return false;
    }

    $state = forever_business_get_vip_program_state($user_id);
    $is_qualified = !empty($state['is_eligible']) && !empty($state['has_valid_linkage']);
    $event_key = $is_qualified ? 'qualified' : 'approved_pending';
    if($trigger === 'qualified' && $event_key !== 'qualified') {
        /* A queued qualification notice can become stale after an approved
         * account/FBO correction. Remove it from the retry batch instead of
         * letting stale rows consume every cron slot. */
        db()->where('user_id', $user_id)
            ->where('event_key', 'qualified')
            ->where('status', ['pending', 'failed'], 'IN')
            ->update('forever_business_vip_email_deliveries', [
                'status' => 'superseded',
                'last_error' => 'qualification_unavailable: eligibility or linkage no longer valid',
                'updated_at' => get_date(),
            ]);
        return false;
    }

    /* If the account/email was repaired after a terminal recipient transition,
     * make that exact event eligible again without reopening policy-superseded
     * deliveries. */
    $escaped_event_key = database()->real_escape_string($event_key);
    $requeue_timestamp = get_date();
    database()->query("UPDATE `forever_business_vip_email_deliveries`
        SET `status` = 'pending', `attempts` = 0, `last_error` = NULL,
            `last_attempt_at` = NULL, `updated_at` = '{$requeue_timestamp}'
        WHERE `user_id` = {$user_id} AND `event_key` = '{$escaped_event_key}'
          AND `status` = 'superseded'
          AND (LEFT(COALESCE(`last_error`, ''), 22) = 'recipient_unavailable:'
              OR LEFT(COALESCE(`last_error`, ''), 26) = 'qualification_unavailable:')");

    if($event_key === 'qualified') {
        db()->where('user_id', $user_id)
            ->where('event_key', 'approved_pending')
            ->where('status', ['pending', 'failed'], 'IN')
            ->update('forever_business_vip_email_deliveries', [
                'status' => 'superseded',
                'updated_at' => get_date(),
            ]);
    }

    $delivery = forever_business_vip_prepare_email_delivery($user_id, $event_key, $state);
    if(!$delivery) return false;
    if((string) ($delivery->status ?? '') === 'sent') return true;
    if((int) ($delivery->attempts ?? 0) >= 5) return false;

    $delivery_id = (int) $delivery->delivery_id;
    $timestamp = get_date();
    database()->query("UPDATE `forever_business_vip_email_deliveries`
        SET `status` = 'sending', `attempts` = `attempts` + 1,
            `last_attempt_at` = '{$timestamp}', `updated_at` = '{$timestamp}'
        WHERE `delivery_id` = {$delivery_id}
          AND `status` IN ('pending', 'failed')
          AND `attempts` < 5");
    if((int) database()->affected_rows < 1) {
        $current = db()->where('delivery_id', $delivery_id)->getOne('forever_business_vip_email_deliveries', ['status']);
        return $current && (string) $current->status === 'sent';
    }

    $message = forever_business_vip_build_email_message($user, $state, $event_key);
    $transport_result = send_mail($user->email, $message['subject'], $message['body'], [
        'is_system_email' => true,
        'anti_phishing_code' => $user->anti_phishing_code ?? null,
        'language' => $user->language ?? 'Hrvatski',
        'brevo_tags' => ['fcc', 'vip-4core', $event_key],
        'return_transport_result' => true,
    ]);
    $is_sent = is_object($transport_result) ? !empty($transport_result->success) : (bool) $transport_result;
    $message_id = is_object($transport_result) ? mb_substr((string) ($transport_result->message_id ?? ''), 0, 255) : '';
    $last_error = '';
    if(!$is_sent) {
        $last_error = is_object($transport_result)
            ? (string) ($transport_result->ErrorInfo ?? $transport_result->curl_error ?? 'Slanje nije uspjelo.')
            : 'Slanje nije uspjelo.';
    }

    db()->where('delivery_id', $delivery_id)->update('forever_business_vip_email_deliveries', [
        'status' => $is_sent ? 'sent' : 'failed',
        'message_id' => $message_id !== '' ? $message_id : null,
        'last_error' => $last_error !== '' ? mb_substr($last_error, 0, 500) : null,
        'sent_at' => $is_sent ? get_date() : null,
        'updated_at' => get_date(),
    ]);

    return $is_sent;
}

function forever_business_process_vip_email_notifications(int $limit = 25): array {
    forever_business_ensure_tables();
    $limit = max(1, min(100, $limit));
    $result = ['sent' => 0, 'failed' => 0, 'processed' => 0];
    $stale_before = (new \DateTimeImmutable('now'))->modify('-60 minutes')->format('Y-m-d H:i:s');
    database()->query("UPDATE `forever_business_vip_email_deliveries`
        SET `status` = 'failed', `updated_at` = '" . get_date() . "'
        WHERE `status` = 'sending' AND `updated_at` < '{$stale_before}'");

    $pending = db()->where('status', ['pending', 'failed'], 'IN')
        ->where('attempts', 5, '<')
        ->orderBy('updated_at', 'ASC')
        ->get('forever_business_vip_email_deliveries', $limit, ['user_id', 'event_key']) ?? [];
    foreach($pending as $delivery) {
        $trigger = (string) $delivery->event_key === 'qualified' ? 'qualified' : 'approved';
        $sent = forever_business_vip_send_notification_for_user((int) $delivery->user_id, $trigger);
        $result[$sent ? 'sent' : 'failed']++;
        $result['processed']++;
    }

    $remaining = $limit - $result['processed'];
    if($remaining <= 0) return $result;

    /* Every active FCC account has an administrator-approved Forever ID.
     * When multiple accounts intentionally share an ID (for example spouses),
     * each qualified account receives its own idempotent access email. */
    $qualified_query = database()->query("SELECT u.user_id
        FROM users u
        INNER JOIN forever_business_vip_enrollments enrollment
            ON enrollment.fbo_id = REPLACE(TRIM(COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.foreverId')),
                JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.forever_id')),
                JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.foreverID')),
                ''
            )), '-', '')
        LEFT JOIN forever_business_vip_email_deliveries delivery
            ON delivery.user_id = u.user_id AND delivery.event_key = 'qualified'
        WHERE u.status = 1
          AND JSON_VALID(u.preferences) = 1
          AND u.email IS NOT NULL
          AND u.email LIKE '%@%'
          AND delivery.delivery_id IS NULL
        ORDER BY u.user_id ASC
        LIMIT {$remaining}");

    while($qualified_query && $row = $qualified_query->fetch_assoc()) {
        $sent = forever_business_vip_send_notification_for_user((int) $row['user_id'], 'qualified');
        $result[$sent ? 'sent' : 'failed']++;
        $result['processed']++;
    }

    return $result;
}
/* /Custom code: FC-2026-08-25 */

function forever_business_vip_track_definitions(): array {
    return [
        'starter' => ['label' => 'Starter', 'rank' => 1, 'goal' => 'Izgradi jednostavan dnevni ritam i napreduj prema najmanje 1 osobnom CC bez stvaranja zaliha.'],
        'reactivation' => ['label' => 'Reaktivacija', 'rank' => 1, 'goal' => 'Vrati kontinuitet kroz postojeće odnose, jednostavne razgovore i korisničku podršku.'],
        'activator' => ['label' => 'Aktivator', 'rank' => 2, 'goal' => 'Uz najmanje 1 osobni CC gradi prema službeno potvrđenoj aktivnosti od 4 CC.'],
        'builder' => ['label' => 'Builder', 'rank' => 3, 'goal' => 'Pretvori potvrđenu aktivnost u stabilnu produktivnost, retention i nove poslovne razgovore.'],
        'leader' => ['label' => 'Leader', 'rank' => 4, 'goal' => 'Razvijaj samostalne suradnike, buduće voditelje i jednostavan sustav koji se može duplicirati.'],
    ];
}

function forever_business_vip_task_title(string $task): string {
    /* A period after a number belongs to expressions such as 2., 7., 21. and
     * 30. day; it is not a sentence boundary. */
    $parts = preg_split('/(?<!\d\.)(?<=[.!?])\s+/u', trim($task), 2);
    $title = rtrim(trim((string) ($parts[0] ?? $task)), '.!?');
    if(mb_strlen($title) <= 88) return $title;

    $title = mb_substr($title, 0, 85);
    $last_space = mb_strrpos($title, ' ');
    if($last_space !== false && $last_space >= 48) $title = mb_substr($title, 0, $last_space);
    return rtrim($title, ',;: ') . '…';
}

function forever_business_vip_task_meta(): array {
    static $meta = null;
    if($meta !== null) return $meta;

    $path = __DIR__ . '/../config/forever_business_vip_task_meta.php';
    if(!is_file($path)) {
        throw new \RuntimeException('VIP task metadata is missing; the curriculum cannot be opened safely.');
    }
    $loaded = require $path;
    if(!is_array($loaded)) {
        throw new \RuntimeException('VIP task metadata is invalid; the curriculum cannot be opened safely.');
    }

    $result_types = forever_business_vip_result_type_options();
    foreach(['starter', 'activator', 'builder', 'leader', 'reactivation'] as $track_key) {
        $track = $loaded[$track_key] ?? null;
        if(!is_array($track)
            || count($track['targets'] ?? []) !== 30
            || count($track['quick_targets'] ?? []) !== 30
            || count($track['result_types'] ?? []) !== 30
            || (isset($track['fallbacks']) && !is_array($track['fallbacks']))
            || (isset($track['examples']) && !is_array($track['examples']))
            || (isset($track['allowed_result_types']) && !is_array($track['allowed_result_types']))
            || (isset($track['checklists']) && !is_array($track['checklists']))) {
            throw new \RuntimeException("VIP task metadata for {$track_key} must contain exactly 30 reviewed rules.");
        }
        for($index = 0; $index < 30; $index++) {
            $target = $track['targets'][$index] ?? null;
            $quick_target = $track['quick_targets'][$index] ?? null;
            $result_type = (string) ($track['result_types'][$index] ?? '');
            if(!is_int($target) || $target < 1 || $target > 999
                || !is_int($quick_target) || $quick_target < 1 || $quick_target > $target
                || !array_key_exists($result_type, $result_types)) {
                throw new \RuntimeException("VIP task metadata for {$track_key}, day " . ($index + 1) . ' is invalid.');
            }
        }
        foreach(($track['checklists'] ?? []) as $day => $items) {
            if(!is_int($day) || $day < 1 || $day > 30 || !is_array($items) || !$items) {
                throw new \RuntimeException("VIP task metadata for {$track_key}, day {$day} contains an invalid checklist.");
            }
            foreach($items as $item) {
                if(!is_string($item) || trim($item) === '') {
                    throw new \RuntimeException("VIP task metadata for {$track_key}, day {$day} contains an invalid checklist item.");
                }
            }
        }
        foreach(($track['allowed_result_types'] ?? []) as $day => $allowed_types) {
            if(!is_int($day) || $day < 1 || $day > 30 || !is_array($allowed_types) || !$allowed_types) {
                throw new \RuntimeException("VIP task metadata for {$track_key}, day {$day} contains invalid allowed result types.");
            }
            $expected_result_type = (string) ($track['result_types'][$day - 1] ?? '');
            foreach($allowed_types as $result_type) {
                if(!is_string($result_type)
                    || !array_key_exists($result_type, forever_business_vip_result_type_options())) {
                    throw new \RuntimeException("VIP task metadata for {$track_key}, day {$day} contains an invalid allowed result type.");
                }
            }
            if(!in_array($expected_result_type, $allowed_types, true)) {
                throw new \RuntimeException("VIP task metadata for {$track_key}, day {$day} must allow its expected result type.");
            }
        }
    }

    return $meta = $loaded;
}

function forever_business_vip_expected_result_type(string $track_key, string $core, string $task): string {
    $task_lower = mb_strtolower($task);

    if(preg_match('/\b(story|video|objav|sadržaj)\w*/u', $task_lower)) return 'content';
    if(preg_match('/\b(onboarding|uvedi|uvođenj)\w*/u', $task_lower)) return 'onboarding';
    if(preg_match('/\b(preporuk|rutin)\w*/u', $task_lower) && $core === 'Productivity') return 'recommendation';
    if($core === 'Retention') return 'customer_checkin';
    if($core === 'Recruitment') {
        if(preg_match('/follow-up|nastavak|zatvori krug|zatvori \w*razgovor/u', $task_lower)) return 'follow_up';
        if(preg_match('/\b(poziv|pozovi|gost|marketing plan)\w*/u', $task_lower)) return 'invitation';
        return 'conversation';
    }
    if($track_key === 'leader' || preg_match('/\b(osob|polaznik|voditelj|grup|mentor)\w*/u', $task_lower)) return 'coaching';
    return 'training';
}

function forever_business_vip_allowed_result_types(string $expected): array {
    $allowed = [
        'contact' => ['contact', 'conversation', 'follow_up', 'no_response', 'training'],
        'invitation' => ['invitation', 'conversation', 'follow_up', 'new_partner', 'no_response', 'training'],
        'follow_up' => ['follow_up', 'conversation', 'recommendation', 'order', 'new_partner', 'no_response', 'training'],
        'conversation' => ['conversation', 'follow_up', 'recommendation', 'invitation', 'no_response', 'training'],
        'customer_checkin' => ['customer_checkin', 'recommendation', 'order', 'no_response', 'training'],
        'recommendation' => ['recommendation', 'order', 'follow_up', 'no_response', 'training'],
        'content' => ['content', 'contact', 'conversation', 'no_response'],
        'coaching' => ['coaching', 'training', 'onboarding'],
        'planning' => ['planning', 'training', 'other'],
        'training' => ['training', 'coaching', 'other'],
        'onboarding' => ['onboarding', 'customer_checkin', 'training'],
        'event' => ['event', 'invitation', 'follow_up'],
    ];
    return $allowed[$expected] ?? [$expected, 'other'];
}

function forever_business_vip_default_fallback(string $expected, int $quick_target): string {
    $quick_target = max(1, $quick_target);
    $fallbacks = [
        'contact' => 'Ako danas nemaš dovoljno ljudi kojima se možeš prirodno javiti, s mentorom pripremi i uvježbaj broj toplih osobnih poruka iz brzog cilja.',
        'conversation' => 'Ako danas nemaš osobu za razgovor, s mentorom uvježbaj broj kratkih, prirodnih razgovora iz brzog cilja.',
        'invitation' => 'Ako danas nemaš osobu spremnu za poziv, s mentorom pripremi i uvježbaj broj osobnih poziva iz brzog cilja.',
        'follow_up' => 'Ako danas nemaš otvoren razgovor za nastavak, s mentorom uvježbaj broj toplih follow-up razgovora iz brzog cilja.',
        'customer_checkin' => 'Ako danas nemaš kupca za provjeru, s mentorom uvježbaj broj prijateljskih korisničkih check-inova iz brzog cilja.',
        'recommendation' => 'Ako danas nitko ne čeka preporuku, s mentorom pripremi broj jednostavnih probnih preporuka iz brzog cilja.',
        'onboarding' => 'Ako danas nemaš novu osobu za uvođenje, s mentorom prođi broj probnih onboardinga iz brzog cilja.',
        'coaching' => 'Ako danas nemaš člana tima za ovaj korak, s mentorom uvježbaj broj coaching situacija iz brzog cilja i zatraži jednu konkretnu povratnu informaciju.',
    ];
    return $fallbacks[$expected] ?? '';
}

function forever_business_vip_finalize_fallback(string $fallback, array $allowed_result_types): string {
    $fallback = trim($fallback);
    $already_explains_training = mb_stripos($fallback, 'Edukacija / trening') !== false;
    $is_mentor_practice = preg_match('/\b(mentor\w*|uvježba\w*|prob\w*)\b/ui', $fallback) === 1;
    if($fallback !== '' && $is_mentor_practice && in_array('training', $allowed_result_types, true) && !$already_explains_training) {
        $fallback .= ' Kada radiš ovu mentorsku verziju, kao vrstu radnje odaberi „Edukacija / trening”.';
    }
    return $fallback;
}

function forever_business_vip_message_example(array $examples, string $task): string {
    $task_lower = mb_strtolower($task);
    $heading = '';
    if(preg_match('/zatvori krug|kulturno (?:zaključi|zatvar)/u', $task_lower)) {
        $heading = 'kulturno zatvaranje razgovora';
    } elseif(preg_match('/follow-up|nakon (?:nedjeljnog )?marketing plana/u', $task_lower)) {
        $heading = 'follow-up nakon marketing plana';
    } elseif(preg_match('/check-in|postojeć\w* kup|korisnik/u', $task_lower)) {
        $heading = 'korisnički check-in';
    } elseif(preg_match('/poziv|pozovi/u', $task_lower) && str_contains($task_lower, 'marketing plan')) {
        $heading = 'poziv na marketing plan';
    } elseif(preg_match('/poruk|kontakt|razgovor/u', $task_lower)) {
        $heading = 'prvi osobni kontakt';
    }

    return $heading !== '' ? (string) ($examples[$heading] ?? '') : '';
}

function forever_business_vip_task_target(string $task): int {
    $number_words = [
        'jedan' => 1, 'jednu' => 1, 'jedno' => 1, 'jednoj' => 1,
        'dva' => 2, 'dvije' => 2, 'dvjema' => 2,
        'tri' => 3, 'trima' => 3, 'četiri' => 4, 'pet' => 5, 'osam' => 8, 'deset' => 10, 'dvanaest' => 12, 'petnaest' => 15, 'dvadeset' => 20,
    ];
    $object_pattern = '(?:osob\w*|imen\w*|kontakt\w*|razgovor\w*|poruk\w*|poziv\w*|kup\w*|gost\w*|polaznik\w*|konzultacij\w*|check-in\w*|follow-up\w*|potreb\w*|preporuk\w*|status\w*|story\w*|suradnik\w*)';
    if(preg_match('/\b(20|15|12|10|8|5|4|3|2|1)\s*[–-]\s*\d+\s+(?:\p{L}+\s+){0,2}' . $object_pattern . '\b/ui', $task, $matches)) {
        return max(1, min(20, (int) $matches[1]));
    }
    if(preg_match('/\b(20|15|12|10|8|5|4|3|2|1)\s+(?:\p{L}+\s+){0,2}' . $object_pattern . '\b/ui', $task, $matches)) {
        return max(1, min(20, (int) $matches[1]));
    }
    if(preg_match('/\b(' . implode('|', array_keys($number_words)) . ')\s+(?:\p{L}+\s+){0,2}' . $object_pattern . '\b/ui', mb_strtolower($task), $matches)) {
        return $number_words[mb_strtolower($matches[1])] ?? 1;
    }
    return 1;
}

function forever_business_get_vip_task_catalog(): array {
    static $catalog = null;
    if($catalog !== null) return $catalog;

    $catalog = [];
    $definitions = forever_business_vip_track_definitions();
    $path = __DIR__ . '/../config/forever_business_vip_tasks.php';
    $content = is_file($path) ? require $path : '';
    $lines = is_string($content) ? preg_split('/\R/u', $content) : [];
    $meta = forever_business_vip_task_meta();
    $message_examples = [];
    if(is_string($content)) {
        preg_match_all('/^##\s+([^\r\n]+)\R\R>\s*([^\r\n]+)$/mu', $content, $example_matches, PREG_SET_ORDER);
        foreach($example_matches as $example_match) {
            $message_examples[mb_strtolower(trim((string) ($example_match[1] ?? '')))] = trim((string) ($example_match[2] ?? ''));
        }
    }
    $track_key = '';

    foreach($lines ?: [] as $line) {
        if(preg_match('/^# Razina\s+\d+\s+—\s+(.+)$/u', trim($line), $matches)) {
            $label = mb_strtolower(trim($matches[1]));
            $track_key = '';
            foreach($definitions as $key => $definition) {
                if($label === mb_strtolower($definition['label'])) {
                    $track_key = $key;
                    $catalog[$track_key] = [];
                    break;
                }
            }
            continue;
        }

        if($track_key === '' || !preg_match('/^\|\s*(\d+)\s*\|\s*([^|]+?)\s*\|\s*([^|]+?)\s*\|\s*([^|]+?)\s*\|$/u', trim($line), $matches)) {
            continue;
        }

        $day = (int) $matches[1];
        if($day < 1 || $day > 30) continue;
        $core = trim($matches[2]);
        $task = trim($matches[3]);
        $success = trim($matches[4]);
        $target = (int) ($meta[$track_key]['targets'][$day - 1] ?? forever_business_vip_task_target($task));
        $target = max(1, min(999, $target));
        $quick_target = (int) ($meta[$track_key]['quick_targets'][$day - 1] ?? ceil($target / 2));
        $quick_target = max(1, min($target, $quick_target));
        $task_parts = preg_split('/(?<!\d\.)(?<=[.!?])\s+/u', $task, 2);
        $title = forever_business_vip_task_title($task);
        $first_sentence = rtrim(trim((string) ($task_parts[0] ?? $task)), '.!?');
        $title_without_ellipsis = rtrim($title, '….!?');
        $title_was_truncated = mb_strlen($first_sentence) > mb_strlen($title_without_ellipsis);
        /* When the summary title shortens a long first sentence, the body must
         * keep the complete original task. Otherwise the omitted middle of a
         * compliance instruction would disappear from the member UI. */
        $instruction = $title_was_truncated ? $task : trim((string) ($task_parts[1] ?? ''));
        if($instruction === '' && mb_strlen(rtrim($task, '.!?')) > mb_strlen(rtrim($title, '….!?'))) {
            $instruction = $task;
        }
        $configured_result_type = (string) ($meta[$track_key]['result_types'][$day - 1] ?? '');
        $expected_result_type = array_key_exists($configured_result_type, forever_business_vip_result_type_options())
            ? $configured_result_type
            : forever_business_vip_expected_result_type($track_key, $core, $task);
        $allowed_result_types = forever_business_vip_allowed_result_types($expected_result_type);
        if(array_key_exists($day, $meta[$track_key]['allowed_result_types'] ?? [])) {
            $allowed_result_types = array_values(array_unique(
                (array) $meta[$track_key]['allowed_result_types'][$day]
            ));
        }
        $fallback = trim((string) ($meta[$track_key]['fallbacks'][$day] ?? ''));
        if($fallback === '') {
            $fallback = forever_business_vip_default_fallback($expected_result_type, $quick_target);
        }
        $fallback = forever_business_vip_finalize_fallback($fallback, $allowed_result_types);
        $checklist = array_values(array_map(
            static fn($item) => trim((string) $item),
            (array) ($meta[$track_key]['checklists'][$day] ?? [])
        ));
        $message_example = '';
        if(array_key_exists('examples', $meta[$track_key] ?? [])) {
            $example_heading = mb_strtolower(trim((string) ($meta[$track_key]['examples'][$day] ?? '')));
            $message_example = $example_heading !== '' ? (string) ($message_examples[$example_heading] ?? '') : '';
        } else {
            $message_example = forever_business_vip_message_example($message_examples, $task);
        }
        $catalog[$track_key][$day] = [
            'core' => $core,
            'title' => $title,
            'instruction' => $instruction,
            'task_text' => $task,
            'checklist' => $checklist,
            'success_definition' => $success,
            'target' => $target,
            'quick_target' => $quick_target,
            'expected_result_type' => $expected_result_type,
            'allowed_result_types' => $allowed_result_types,
            'fallback' => $fallback,
            'message_example' => $message_example,
        ];
    }

    return $catalog;
}

function forever_business_get_vip_track(array $member): array {
    $definitions = forever_business_vip_track_definitions();
    $progress = $member['verified_progress'] ?? forever_business_get_verified_progress($member);
    $base_personal_cc = (float) ($member['vip_base_personal_cc'] ?? $member['personal_cc'] ?? 0);
    $base_is_officially_active = array_key_exists('vip_base_is_4cc_active', $member)
        ? $member['vip_base_is_4cc_active'] !== null && (int) $member['vip_base_is_4cc_active'] === 1
        : !empty($progress['is_officially_active']);
    $august_is_officially_active = array_key_exists('vip_august_is_4cc_active', $member)
        ? $member['vip_august_is_4cc_active'] !== null && (int) $member['vip_august_is_4cc_active'] === 1
        : $base_is_officially_active;
    $base_is_active = array_key_exists('vip_base_is_4cc_active', $member)
        ? forever_business_has_verified_four_cc_activity([
            'is_4cc_active' => $member['vip_base_is_4cc_active'],
            'personal_cc' => $member['vip_base_personal_cc'] ?? null,
            'total_active_cc' => $member['vip_base_total_active_cc'] ?? null,
        ])
        : !empty($progress['is_4cc_active']);
    $base_had_previous_activity = !empty($member['vip_base_had_previous_activity_12m'])
        || !empty($member['vip_base_focus_previous_active'])
        || (float) ($member['vip_base_previous_personal_cc'] ?? $member['previous_personal_cc'] ?? 0) > 0;
    /* The qualification snapshot fixes the initial curriculum, while upgrades
     * use only this calendar month's activity. A missing open-month row is a
     * zero and must never inherit the qualifying/August CC. */
    $current_personal_cc = (float) ($member['vip_current_personal_cc'] ?? 0);
    $current_is_active = forever_business_has_verified_four_cc_activity([
        'is_4cc_active' => $member['vip_current_is_4cc_active'] ?? null,
        'personal_cc' => $member['vip_current_personal_cc'] ?? 0.0,
        'total_active_cc' => $member['vip_current_total_active_cc'] ?? 0.0,
    ]);

    $is_recognized_manager = ($progress['rank']['mode'] ?? '') === 'manager';

    /* Leader is intentionally fixed from the August qualification snapshot:
     * a full Manager title plus an explicit official August 4 CC Active=1.
     * The NULL-only formula fallback may classify Builder activity, never Leader. */
    if(!empty($member['force_vip_leader'])) {
        /* The authenticated root administrator uses the Leader curriculum in
         * their own Moj Forever workspace. This flag is never accepted from a
         * request and is set only server-side for that one profile. */
        $track_key = 'leader';
    } elseif($is_recognized_manager && $august_is_officially_active) {
        $track_key = 'leader';
    } elseif($base_is_active) {
        $track_key = 'builder';
    } elseif($base_personal_cc >= 1) {
        $track_key = 'activator';
    } elseif($base_had_previous_activity) {
        $track_key = 'reactivation';
    } else {
        $track_key = 'starter';
    }

    if($current_is_active && (int) $definitions[$track_key]['rank'] < (int) $definitions['builder']['rank']) {
        $track_key = 'builder';
    } elseif($current_personal_cc >= 1 && (int) $definitions[$track_key]['rank'] < (int) $definitions['activator']['rank']) {
        $track_key = 'activator';
    }

    $highest_rank = max(0, (int) ($member['vip_highest_track_rank'] ?? 0));
    if($highest_rank === (int) $definitions['leader']['rank'] && empty($member['force_vip_leader']) && !($is_recognized_manager && $august_is_officially_active)) {
        /* A historical Leader action must not permanently bypass the stricter
         * Manager + August official 4 CC qualification. */
        $highest_rank = max(0, (int) ($member['vip_highest_nonleader_track_rank'] ?? 0));
    }
    if($highest_rank > (int) $definitions[$track_key]['rank']) {
        foreach(array_reverse($definitions, true) as $key => $definition) {
            if((int) $definition['rank'] === $highest_rank) {
                $track_key = $key;
                break;
            }
        }
    }

    $resolved_rank = (int) $definitions[$track_key]['rank'];
    return [
        'key' => $track_key,
        'has_advanced' => $highest_rank > 0 && $resolved_rank > $highest_rank,
    ] + $definitions[$track_key];
}

function forever_business_vip_action_key(string $track_key, int $day): string {
    $default_key = sprintf('vip26_%s_d%02d', $track_key, $day);
    /* Activator day 1 was materially replaced at launch. A versioned key and
     * the progress exclusions below ensure that any very early completion of
     * the superseded CC-review task cannot grant credit for the new biolink
     * task. The historical record remains available for audit statistics. */
    return $default_key === 'vip26_activator_d01'
        ? 'vip26_activator_d01_biolink'
        : $default_key;
}

function forever_business_get_action(array $member, ?array $metric, int $completed_total = 0, bool $sunday_done_today = false, ?\DateTimeInterface $now = null, bool $vip_done_today = false): array {
    $track = forever_business_get_vip_track($member);
    $marketing_plan = forever_business_get_marketing_plan_state($now);
    $timezone = new \DateTimeZone('Europe/Zagreb');
    $current_time = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);

    if($vip_done_today && $completed_total < 30) {
        return [
            'core' => 'Development',
            'key' => 'vip26_daily_complete_' . $current_time->format('Ymd'),
            'title' => 'Današnji korak je dovršen',
            'instruction' => 'Zadatak je spremljen. Novi zadatak otvorit će se sutra kako bi program ostao jasan, održiv i usporediv za sve polaznike.',
            'checklist' => [],
            'success_definition' => 'Za danas je dovoljno. Primijeni dogovoreni nastavak i vrati se sutra po novi korak.',
            'target' => 0,
            'quick_target' => 0,
            'can_complete' => false,
            'sequence_position' => min(30, max(1, $completed_total)),
            'sequence_total' => 30,
            'track_key' => $track['key'],
            'track_label' => $track['label'],
            'track_goal' => $track['goal'],
            'is_daily_complete' => true,
            'marketing_plan' => $marketing_plan,
        ];
    }

    if(!empty($marketing_plan['is_today']) && !$sunday_done_today) {
        $leader = $track['key'] === 'leader';
        return [
            'core' => $leader ? 'Development' : 'Recruitment',
            'key' => 'vip26_sunday_' . $current_time->format('Ymd'),
            'title' => 'Marketing plan danas u 18:00',
            'instruction' => $leader
                ? 'Provjeri jesu li gosti i pozivatelji spremni, dodijeli jasne uloge i pridruži se tjednom Marketing planu u 18:00.'
                : 'Pošalji posljednju osobnu potvrdu svojim gostima, pridruži se Marketing planu u 18:00 i nakon prezentacije dogovori njihov sljedeći korak.',
            'checklist' => $leader
                ? ['Potvrdi popis gostiju i njihove pozivatelje.', 'Provjeri tko dočekuje goste i tko vodi follow-up.', 'Nakon plana potvrdi sljedeći korak za svakog gosta.']
                : ['Potvrdi gostima termin i pošalji detalje.', 'Pridruži se nekoliko minuta ranije.', 'Nakon prezentacije pitaj gosta što mu je bilo najzanimljivije.'],
            'success_definition' => $leader
                ? 'Gotovo je kada su Marketing plan, atribucija gostiju i vlasnici follow-upa evidentirani.'
                : 'Gotovo je kada si prisustvovao/la i svaki tvoj gost ima zabilježen interes ili dogovoreni nastavak.',
            'target' => $leader ? 5 : 2,
            'quick_target' => 1,
            'expected_result_type' => 'event',
            'allowed_result_types' => forever_business_vip_allowed_result_types('event'),
            'fallback' => $leader
                ? 'Ako danas nema gostiju, pridruži se radi vlastitog učenja i s timom dogovori jednu konkretnu doradu za sljedeću nedjelju.'
                : 'Ako danas nemaš gosta, pridruži se radi vlastitog učenja, zapiši jednu korisnu ideju i pripremi osobni poziv za sljedeću nedjelju.',
            'message_example' => '',
            /* Success includes attendance and post-event follow-up, so a
             * Sunday date alone can never unlock completion before 19:30. */
            'can_complete' => !empty($marketing_plan['can_record_outcome']),
            'is_waiting_for_event_completion' => empty($marketing_plan['can_record_outcome']),
            'sequence_position' => min(30, $completed_total + 1),
            'sequence_total' => 30,
            'track_key' => $track['key'],
            'track_label' => $track['label'],
            'track_goal' => $track['goal'],
            'track_has_advanced' => !empty($track['has_advanced']),
            'is_weekly_plan' => true,
            'marketing_plan' => $marketing_plan,
        ];
    }

    if($completed_total >= 30) {
        return [
            'core' => 'Development',
            'key' => 'vip26_program_complete',
            'title' => 'Prvih 30 koraka je dovršeno',
            'instruction' => 'Pregledaj svoj napredak i nastavi primjenjivati ritam koji ti je donio najviše kvalitetnih razgovora, kupaca, gostiju i suradnika.',
            'checklist' => [],
            'success_definition' => 'Tvoj prvi VIP 4 Core ciklus je završen. Nedjeljni Marketing plan i dalje će se prikazivati svakog tjedna.',
            'target' => 0,
            'quick_target' => 0,
            'can_complete' => false,
            'sequence_position' => 30,
            'sequence_total' => 30,
            'track_key' => $track['key'],
            'track_label' => $track['label'],
            'track_goal' => $track['goal'],
            'is_program_complete' => true,
            'marketing_plan' => $marketing_plan,
        ];
    }

    $day = $completed_total + 1;
    $catalog = forever_business_get_vip_task_catalog();
    $task = $catalog[$track['key']][$day] ?? null;
    if(!$task) {
        return [
            'core' => 'Development',
            'key' => 'vip26_content_check',
            'title' => 'Tvoj sljedeći korak uskoro će biti spreman',
            'instruction' => 'Sadržaj tvoje razine trenutačno se provjerava. Ne trebaš ponovno dovršavati prethodni korak.',
            'checklist' => [],
            'success_definition' => 'Novi korak prikazat će se nakon osvježavanja sadržaja.',
            'target' => 0,
            'quick_target' => 0,
            'can_complete' => false,
            'sequence_position' => $day,
            'sequence_total' => 30,
            'track_key' => $track['key'],
            'track_label' => $track['label'],
            'track_goal' => $track['goal'],
            'marketing_plan' => $marketing_plan,
        ];
    }

    $task['key'] = forever_business_vip_action_key($track['key'], $day);
    $task['can_complete'] = true;
    $task['sequence_position'] = $day;
    $task['sequence_total'] = 30;
    $task['track_key'] = $track['key'];
    $task['track_label'] = $track['label'];
    $task['track_goal'] = $track['goal'];
    $task['track_has_advanced'] = !empty($track['has_advanced']);
    $task['is_weekly_plan'] = false;
    $task['marketing_plan'] = $marketing_plan;
    $original_quick_target = max(1, (int) ($task['quick_target'] ?? 1));
    $adapted_quick_target = max(1, (int) ceil($original_quick_target / 2));
    if($adapted_quick_target < $original_quick_target
        && (($member['vip_last_difficulty'] ?? '') === 'hard' || ($member['vip_last_completion_mode'] ?? '') === 'quick')) {
        $task['quick_target'] = $adapted_quick_target;
        $adaptive_fallback = forever_business_vip_default_fallback(
            (string) ($task['expected_result_type'] ?? ''),
            (int) $task['quick_target']
        );
        if($adaptive_fallback !== '') {
            $task['fallback'] = forever_business_vip_finalize_fallback(
                $adaptive_fallback,
                (array) ($task['allowed_result_types'] ?? [])
            );
        } elseif(!empty($task['fallback'])) {
            $task['fallback'] .= ' Za današnju lakšu verziju napravi broj radnji koji vidiš u brzom cilju.';
        }
        $task['is_adaptively_simplified'] = true;
        $task['adaptive_note'] = 'Jučerašnji korak bio ti je zahtjevan ili si odabrao/la bržu verziju, pa je današnji brzi cilj još malo lakši. Puni cilj ostaje dostupan ako ti odgovara.';
    }
    return $task;
}
/* /Custom code: FC-2026-08-15 */

function forever_business_upsert_four_core_snapshot(string $fbo_id, string $period, array $values, string $source_note = 'FLP360 4 Core Summary'): void {
    forever_business_ensure_tables();
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    if($fbo_id === '' || !preg_match('/^360[0-9]{9}$/D', $fbo_id) || $period === '') {
        throw new \InvalidArgumentException('Neispravan Forever ID ili razdoblje 4 Core snimke.');
    }
    if(!forever_business_period_is_current_or_past($period)) {
        throw new \InvalidArgumentException('Budući FLP360 mjesec još se ne može spremiti.');
    }

    foreach(['open' => 'open', 'downline' => 'downline'] as $scope_key => $business_scope) {
        foreach(['month', 'ytd'] as $timeframe) {
            $row = $values[$scope_key][$timeframe] ?? null;
            if(!is_array($row)) continue;
            db()->onDuplicate(['recruitment', 'retention', 'productivity', 'development', 'captured_at', 'source_note'])->insert('forever_business_four_core_snapshots', [
                'fbo_id' => $fbo_id,
                'period_month' => $period,
                'business_scope' => $business_scope,
                'timeframe' => $timeframe,
                'recruitment' => isset($row['recruitment']) ? forever_business_number($row['recruitment']) : null,
                'retention' => isset($row['retention']) ? forever_business_number($row['retention']) : null,
                'productivity' => isset($row['productivity']) ? forever_business_number($row['productivity']) : null,
                'development' => isset($row['development']) ? forever_business_number($row['development']) : null,
                'captured_at' => get_date(),
                'source_note' => mb_substr($source_note, 0, 255),
            ]);
        }
    }
}

function forever_business_get_four_core_snapshot(string $fbo_id, string $period): array {
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    if($fbo_id === '' || $period === '') return [];

    $snapshot_period_row = db()->where('fbo_id', $fbo_id)->where('period_month', $period, '<=')->orderBy('period_month', 'DESC')->getOne('forever_business_four_core_snapshots', ['period_month']);
    if(!$snapshot_period_row) return [];
    $snapshot_period = (string) $snapshot_period_row->period_month;
    $rows = db()->where('fbo_id', $fbo_id)->where('period_month', $snapshot_period)->get('forever_business_four_core_snapshots') ?? [];
    $result = ['period_month' => $snapshot_period];
    foreach($rows as $row) {
        $result[$row->business_scope][$row->timeframe] = [
            'recruitment' => $row->recruitment,
            'retention' => $row->retention,
            'productivity' => $row->productivity,
            'development' => $row->development,
            'captured_at' => $row->captured_at,
            'source_note' => $row->source_note,
        ];
    }

    /* The FLP360 summary shows the same month from the prior year beside the
       current values. Keep that comparison as a separate, official snapshot so
       a negative percentage can never be mistaken for negative CC. */
    $comparison_period = (new \DateTimeImmutable($snapshot_period))->modify('-1 year')->format('Y-m-01');
    $comparison_rows = db()->where('fbo_id', $fbo_id)->where('period_month', $comparison_period)->get('forever_business_four_core_snapshots') ?? [];
    if($comparison_rows) {
        $result['comparison_period'] = $comparison_period;
        foreach($comparison_rows as $row) {
            $result['previous'][$row->business_scope][$row->timeframe] = [
                'recruitment' => $row->recruitment,
                'retention' => $row->retention,
                'productivity' => $row->productivity,
                'development' => $row->development,
                'captured_at' => $row->captured_at,
                'source_note' => $row->source_note,
            ];
        }
    }

    return $result;
}

function forever_business_upsert_total_cc_snapshot(string $fbo_id, string $period, float $total_cc, bool $is_closed, string $country_scope = 'GLOBAL', string $source_note = 'FLP360 CC Summary · Global Total CC'): void {
    forever_business_ensure_tables();
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    $country_scope = mb_strtoupper(trim($country_scope));
    if($fbo_id === '' || $period === '' || $country_scope === '') {
        throw new \InvalidArgumentException('Neispravan Forever ID, razdoblje ili tržište Total CC snimke.');
    }
    if(!forever_business_period_is_current_or_past($period)) {
        throw new \InvalidArgumentException('Budući FLP360 mjesec još se ne može spremiti.');
    }

    db()->onDuplicate(['total_cc', 'is_closed', 'captured_at', 'source_note'])->insert('forever_business_total_cc_snapshots', [
        'fbo_id' => $fbo_id,
        'period_month' => $period,
        'country_scope' => mb_substr($country_scope, 0, 32),
        'total_cc' => max(0, $total_cc),
        'is_closed' => (int) $is_closed,
        'captured_at' => get_date(),
        'source_note' => mb_substr($source_note, 0, 255),
    ]);
}

/* Active FCC accounts can belong outside the last confirmed team hierarchy.
 * Their live FLP360 CC is synchronized independently, without changing
 * is_in_current_structure or any hierarchy edge. The active account linkage
 * is rechecked server-side for every write. */
function forever_business_upsert_registered_member_live_cc(string $fbo_id, string $period, array $metrics): void {
    forever_business_ensure_tables();
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    if($fbo_id === '' || $period === '') {
        throw new \InvalidArgumentException('Neispravan FCC Forever ID ili razdoblje live CC snimke.');
    }
    if(!forever_business_period_is_current_or_past($period)) {
        throw new \InvalidArgumentException('Budući FLP360 mjesec još se ne može spremiti.');
    }
    if(forever_business_get_active_user_link_count_for_fbo($fbo_id) < 1) {
        throw new \InvalidArgumentException('Live CC strojni unos dopušten je samo za Forever ID aktivnog FCC računa.');
    }

    $number = static function(string $key, bool $required = false) use ($metrics): ?float {
        $value = $metrics[$key] ?? null;
        if($value === null || $value === '') {
            if($required) throw new \InvalidArgumentException('Nedostaje obavezna live CC vrijednost: ' . $key . '.');
            return null;
        }
        if(!is_numeric($value) || (float) $value < 0) {
            throw new \InvalidArgumentException('Neispravna live CC vrijednost: ' . $key . '.');
        }
        return round((float) $value, 3);
    };

    $personal_cc = $number('personal_cc', true);
    $total_cc = $number('total_cc', true);
    $total_active_cc = $number('total_active_cc', true);
    $non_manager_cc = $number('non_manager_cc');
    $leadership_cc = $number('leadership_cc');
    $total_active_cc_ytd = $number('total_active_cc_ytd');
    $non_manager_cc_ytd = $number('non_manager_cc_ytd');
    $leadership_cc_ytd = $number('leadership_cc_ytd');

    db()->startTransaction();
    try {
        $escaped_fbo_id = database()->real_escape_string($fbo_id);
        $member_time = database()->real_escape_string(get_date());
        $member_ready = database()->query("INSERT IGNORE INTO forever_business_members
            (fbo_id, name, title, generation, country_code, sponsor_date, parent_fbo_id, tree_sequence,
             is_manager, is_privacy_requested, is_in_current_structure, email_hash, phone_hash,
             first_seen_import_id, last_seen_import_id, created_at, updated_at)
            SELECT
                '{$escaped_fbo_id}', LEFT(name, 160), 'FCC suradnik', NULL, NULL, NULL, NULL, NULL,
                0, 0, 0, NULL, NULL, NULL, NULL, '{$member_time}', '{$member_time}'
            FROM users
            WHERE type = 0 AND status = 1 AND JSON_VALID(preferences) = 1
              AND REPLACE(TRIM(COALESCE(
                  JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverId')),
                  JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.forever_id')),
                  JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverID')),
                  ''
              )), '-', '') = '{$escaped_fbo_id}'
            LIMIT 1");
        if(!$member_ready) throw new \RuntimeException('FCC suradnik nije moguće pripremiti za live CC upis.');
        $metric_update_columns = ['personal_cc', 'total_cc', 'total_active_cc', 'is_4cc_active', 'updated_at'];
        if($non_manager_cc !== null) $metric_update_columns[] = 'non_manager_cc';
        if($leadership_cc !== null) $metric_update_columns[] = 'leadership_cc';
        db()->onDuplicate($metric_update_columns)->insert('forever_business_metrics', [
            'fbo_id' => $fbo_id,
            'period_month' => $period,
            'personal_cc' => $personal_cc,
            'total_cc' => $total_cc,
            'total_active_cc' => $total_active_cc,
            'non_manager_cc' => $non_manager_cc,
            'leadership_cc' => $leadership_cc,
            'is_4cc_active' => array_key_exists('is_4cc_active', $metrics) ? (int) !empty($metrics['is_4cc_active']) : null,
            'source_import_id' => null,
            'updated_at' => get_date(),
        ]);

        $yearly_update_columns = ['updated_at'];
        if($total_active_cc_ytd !== null) $yearly_update_columns[] = 'total_active_cc_ytd';
        if($non_manager_cc_ytd !== null) $yearly_update_columns[] = 'non_manager_cc_ytd';
        if($leadership_cc_ytd !== null) $yearly_update_columns[] = 'leadership_cc_ytd';
        db()->onDuplicate($yearly_update_columns)->insert('forever_business_yearly_metrics', [
            'fbo_id' => $fbo_id,
            'period_year' => (int) substr($period, 0, 4),
            'total_active_cc_ytd' => $total_active_cc_ytd,
            'non_manager_cc_ytd' => $non_manager_cc_ytd,
            'leadership_cc_ytd' => $leadership_cc_ytd,
            'source_import_id' => null,
            'updated_at' => get_date(),
        ]);

        $enrollment_recorded = forever_business_record_vip_eligibility_metric(
            $fbo_id,
            $period,
            $personal_cc,
            null,
            'member_cc'
        );
        if($personal_cc >= .330 && forever_business_vip_eligibility_period_is_open($period) && !$enrollment_recorded) {
            throw new \RuntimeException('VIP education enrollment aktivnog FCC računa nije moguće spremiti.');
        }
        db()->commit();
    } catch(\Throwable $exception) {
        db()->rollback();
        throw $exception;
    }
}

/* Custom code: FC-2026-08-15: Keep the pinned root FBO's live CC aligned with the
 * collaborator Downline without representing the root as its own descendant. */
function forever_business_upsert_root_live_cc(string $fbo_id, string $period, array $metrics): void {
    forever_business_ensure_tables();
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    if($fbo_id === '' || $period === '') {
        throw new \InvalidArgumentException('Neispravan glavni Forever ID ili razdoblje live CC snimke.');
    }
    $current_zagreb_period = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Zagreb')))->format('Y-m-01');
    if($period > $current_zagreb_period) {
        throw new \InvalidArgumentException('Budući FLP360 mjesec još se ne može spremiti.');
    }

    $number = static fn(string $key): float => max(0, forever_business_number($metrics[$key] ?? 0));
    db()->startTransaction();
    try {
        db()->onDuplicate([
            'personal_cc', 'total_cc', 'total_active_cc', 'non_manager_cc', 'leadership_cc', 'is_4cc_active', 'updated_at',
        ])->insert('forever_business_metrics', [
            'fbo_id' => $fbo_id,
            'period_month' => $period,
            'personal_cc' => $number('personal_cc'),
            'total_cc' => $number('total_cc'),
            'total_active_cc' => $number('total_active_cc'),
            'non_manager_cc' => $number('non_manager_cc'),
            'leadership_cc' => $number('leadership_cc'),
            'is_4cc_active' => !empty($metrics['is_4cc_active']) ? 1 : 0,
            'source_import_id' => null,
            'updated_at' => get_date(),
        ]);

        db()->onDuplicate([
            'total_active_cc_ytd', 'non_manager_cc_ytd', 'leadership_cc_ytd', 'updated_at',
        ])->insert('forever_business_yearly_metrics', [
            'fbo_id' => $fbo_id,
            'period_year' => (int) substr($period, 0, 4),
            'total_active_cc_ytd' => $number('total_active_cc_ytd'),
            'non_manager_cc_ytd' => $number('non_manager_cc_ytd'),
            'leadership_cc_ytd' => $number('leadership_cc_ytd'),
            'source_import_id' => null,
            'updated_at' => get_date(),
        ]);

        $root_enrollment_recorded = forever_business_record_vip_eligibility_metric(
            $fbo_id,
            $period,
            $number('personal_cc'),
            null,
            'member_cc'
        );
        if($number('personal_cc') >= .330 && forever_business_vip_eligibility_period_is_open($period) && !$root_enrollment_recorded) {
            throw new \RuntimeException('Root VIP education enrollment could not be persisted.');
        }
        db()->commit();
    } catch(\Throwable $exception) {
        db()->rollback();
        throw $exception;
    }
}
/* /Custom code: FC-2026-08-15 */

function forever_business_get_total_cc_snapshot(string $fbo_id, string $period, string $country_scope = 'GLOBAL'): ?array {
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    if($fbo_id === '' || $period === '') return null;

    $row = db()->where('fbo_id', $fbo_id)
        ->where('period_month', $period)
        ->where('country_scope', mb_strtoupper(trim($country_scope)))
        ->getOne('forever_business_total_cc_snapshots');
    return $row ? (array) $row : null;
}

function forever_business_get_total_cc_trend(string $fbo_id, string $period, string $country_scope = 'GLOBAL', int $limit = 8): array {
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    if($fbo_id === '' || $period === '') return [];

    $rows = db()->where('fbo_id', $fbo_id)
        ->where('period_month', $period, '<=')
        ->where('country_scope', mb_strtoupper(trim($country_scope)))
        ->orderBy('period_month', 'DESC')
        ->get('forever_business_total_cc_snapshots', max(1, min(24, $limit))) ?? [];
    return array_reverse(array_map(static fn($row) => (array) $row, $rows));
}

function forever_business_get_dashboard(int $user_id, bool $is_admin, string $requested_root = '', string $period = '', ?\DateTimeInterface $now = null): array {
    forever_business_ensure_tables();
    $timezone = new \DateTimeZone('Europe/Zagreb');
    $zagreb_now = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);
    $today = $zagreb_now->format('Y-m-d');
    $seven_day_start = $zagreb_now->modify('-6 days')->format('Y-m-d');
    $current_zagreb_period = forever_business_current_zagreb_period($zagreb_now);
    $periods = forever_business_get_periods($zagreb_now);
    $period = in_array($period, $periods, true) ? $period : ($periods[0] ?? $current_zagreb_period);
    $previous_period = (new \DateTimeImmutable($period))->modify('-1 month')->format('Y-m-01');
    $two_months_ago_period = (new \DateTimeImmutable($period))->modify('-2 months')->format('Y-m-01');
    $three_months_ago_period = (new \DateTimeImmutable($period))->modify('-3 months')->format('Y-m-01');
    $vip_current_period = $current_zagreb_period;
    $dashboard_user = db()->where('user_id', $user_id)->getOne('users', ['preferences']);
    $authenticated_dashboard_root = $dashboard_user ? forever_business_extract_user_fbo_id($dashboard_user->preferences ?? null) : '';
    $requested_root_id = forever_business_normalize_fbo_id($requested_root);
    $scope_ids = forever_business_get_scope_ids($user_id, $is_admin, $requested_root_id);
    $id_list = forever_business_safe_id_list($scope_ids);
    $members = [];
    /* Non-admin scope is self-only even when an arbitrary ?root= value is
     * supplied. Otherwise shared-FBO team aggregates could replace this
     * account's progress and derive the wrong next action. */
    $dashboard_root = $is_admin
        ? (forever_business_normalize_fbo_id($requested_root) ?: $authenticated_dashboard_root)
        : $authenticated_dashboard_root;
    $escaped_dashboard_root = database()->real_escape_string($dashboard_root);

    if(!empty($scope_ids)) {
        $query = "
            SELECT m.*,
                   cur.personal_cc, cur.total_cc, cur.total_active_cc, cur.non_manager_cc, cur.leadership_cc,
                   CASE WHEN cur.source_import_id IS NOT NULL AND (cur_source.import_id IS NULL OR cur_source.report_kind NOT IN ('downline', 'four_cc_active')) THEN NULL ELSE cur.is_4cc_active END AS is_4cc_active,
                   prev.personal_cc AS previous_personal_cc, prev.total_cc AS previous_total_cc,
                   prev2.total_cc AS two_months_ago_total_cc,
                   prev3.total_cc AS three_months_ago_total_cc,
                   COALESCE(vip_enrollment.qualifying_personal_cc, vip_base.personal_cc) AS vip_base_personal_cc,
                   vip_base.total_active_cc AS vip_base_total_active_cc,
                   CASE WHEN vip_base.source_import_id IS NOT NULL AND (vip_base_source.import_id IS NULL OR vip_base_source.report_kind NOT IN ('downline', 'four_cc_active')) THEN NULL ELSE vip_base.is_4cc_active END AS vip_base_is_4cc_active,
                   CASE WHEN vip_august.source_import_id IS NOT NULL AND (vip_august_source.import_id IS NULL OR vip_august_source.report_kind NOT IN ('downline', 'four_cc_active')) THEN NULL ELSE vip_august.is_4cc_active END AS vip_august_is_4cc_active,
                   vip_base_prev.personal_cc AS vip_base_previous_personal_cc,
                   vip_base_focus.was_active_previous_month AS vip_base_focus_previous_active,
                   EXISTS(
                       SELECT 1
                       FROM forever_business_metrics vip_history
                       WHERE vip_history.fbo_id = m.fbo_id
                         AND vip_history.period_month < COALESCE(vip_enrollment.qualifying_period, '2026-08-01')
                         AND vip_history.period_month >= DATE_SUB(COALESCE(vip_enrollment.qualifying_period, '2026-08-01'), INTERVAL 12 MONTH)
                         AND vip_history.personal_cc > 0
                   ) OR EXISTS(
                       SELECT 1
                       FROM forever_business_focus_metrics vip_focus_history
                       WHERE vip_focus_history.fbo_id = m.fbo_id
                         AND vip_focus_history.period_month < COALESCE(vip_enrollment.qualifying_period, '2026-08-01')
                         AND vip_focus_history.period_month >= DATE_SUB(COALESCE(vip_enrollment.qualifying_period, '2026-08-01'), INTERVAL 12 MONTH)
                         AND (vip_focus_history.was_active_previous_month = 1
                              OR vip_focus_history.last_purchase_date >= DATE_SUB(COALESCE(vip_enrollment.qualifying_period, '2026-08-01'), INTERVAL 12 MONTH))
                   ) AS vip_base_had_previous_activity_12m,
                   vip_current.period_month AS vip_current_period_month,
                   vip_current.personal_cc AS vip_current_personal_cc,
                   vip_current.total_active_cc AS vip_current_total_active_cc,
                   CASE WHEN vip_current.source_import_id IS NOT NULL AND (vip_current_source.import_id IS NULL OR vip_current_source.report_kind NOT IN ('downline', 'four_cc_active')) THEN NULL ELSE vip_current.is_4cc_active END AS vip_current_is_4cc_active,
                   focus.snapshot_date AS focus_snapshot_date, focus.next_level, focus.last_purchase_date,
                   focus.is_active AS focus_is_active, focus.was_active_previous_month AS focus_previous_active,
                   focus.open_group_cc_2m, focus.needed_cc_next_level, focus.new_recruits,
                   COALESCE(actor_outcomes.actions_done, 0) AS actions_done_7d,
                   COALESCE(actor_outcomes.action_units_total, 0) AS action_units_total_7d,
                   COALESCE(actor_outcomes.actions_done_total, 0) AS actions_done_total,
                   COALESCE(actor_outcomes.vip_actions_done_total, 0) AS vip_actions_done_total,
                   COALESCE(actor_outcomes.vip_sunday_done_today, 0) AS vip_sunday_done_today,
                   COALESCE(actor_outcomes.vip_action_done_today, 0) AS vip_action_done_today,
                   COALESCE(actor_outcomes.vip_highest_track_rank, 0) AS vip_highest_track_rank,
                   COALESCE(actor_outcomes.vip_highest_nonleader_track_rank, 0) AS vip_highest_nonleader_track_rank,
                   actor_outcomes.vip_last_difficulty,
                   actor_outcomes.vip_last_completion_mode,
                   actor_outcomes.last_action_at,
                   COALESCE(outcomes.actions_done, 0) AS team_actions_done_7d,
                   COALESCE(outcomes.action_units_total, 0) AS team_action_units_total_7d,
                   COALESCE(outcomes.actions_done_total, 0) AS team_actions_done_total,
                   COALESCE(outcomes.vip_actions_done_total, 0) AS team_vip_actions_done_total,
                   COALESCE(outcomes.vip_sunday_done_today, 0) AS team_vip_sunday_done_today,
                   COALESCE(outcomes.vip_action_done_today, 0) AS team_vip_action_done_today,
                   COALESCE(outcomes.vip_highest_track_rank, 0) AS team_vip_highest_track_rank,
                   COALESCE(outcomes.vip_highest_nonleader_track_rank, 0) AS team_vip_highest_nonleader_track_rank,
                   outcomes.vip_last_difficulty AS team_vip_last_difficulty,
                   outcomes.vip_last_completion_mode AS team_vip_last_completion_mode,
                   outcomes.last_action_at AS team_last_action_at
            FROM forever_business_members m
            LEFT JOIN forever_business_metrics cur ON cur.fbo_id = m.fbo_id AND cur.period_month = '{$period}'
            LEFT JOIN forever_business_imports cur_source ON cur_source.import_id = cur.source_import_id
            LEFT JOIN forever_business_metrics prev ON prev.fbo_id = m.fbo_id AND prev.period_month = '{$previous_period}'
            LEFT JOIN forever_business_metrics prev2 ON prev2.fbo_id = m.fbo_id AND prev2.period_month = '{$two_months_ago_period}'
            LEFT JOIN forever_business_metrics prev3 ON prev3.fbo_id = m.fbo_id AND prev3.period_month = '{$three_months_ago_period}'
            LEFT JOIN forever_business_vip_enrollments vip_enrollment ON vip_enrollment.fbo_id = m.fbo_id
            LEFT JOIN forever_business_metrics vip_base ON vip_base.fbo_id = m.fbo_id
                AND vip_base.period_month = COALESCE(vip_enrollment.qualifying_period, '2026-08-01')
            LEFT JOIN forever_business_imports vip_base_source ON vip_base_source.import_id = vip_base.source_import_id
            LEFT JOIN forever_business_metrics vip_august ON vip_august.fbo_id = m.fbo_id AND vip_august.period_month = '2026-08-01'
            LEFT JOIN forever_business_imports vip_august_source ON vip_august_source.import_id = vip_august.source_import_id
            LEFT JOIN forever_business_metrics vip_base_prev ON vip_base_prev.fbo_id = m.fbo_id
                AND vip_base_prev.period_month = DATE_SUB(COALESCE(vip_enrollment.qualifying_period, '2026-08-01'), INTERVAL 1 MONTH)
            LEFT JOIN forever_business_focus_metrics vip_base_focus ON vip_base_focus.fbo_id = m.fbo_id
                AND vip_base_focus.period_month = COALESCE(vip_enrollment.qualifying_period, '2026-08-01')
            LEFT JOIN forever_business_metrics vip_current ON vip_current.fbo_id = m.fbo_id
                AND vip_current.period_month = '{$vip_current_period}'
            LEFT JOIN forever_business_imports vip_current_source ON vip_current_source.import_id = vip_current.source_import_id
            LEFT JOIN forever_business_focus_metrics focus ON focus.fbo_id = m.fbo_id AND focus.period_month = '{$period}'
            LEFT JOIN (
                SELECT fbo_id,
                       COUNT(DISTINCT IF(action_date >= '{$seven_day_start}' AND status = 'done' AND action_key LIKE 'vip26\\_%', action_date, NULL)) AS actions_done,
                       SUM(IF(action_date >= '{$seven_day_start}' AND status = 'done' AND action_key LIKE 'vip26\\_%', outcome_count, 0)) AS action_units_total,
                       SUM(status = 'done') AS actions_done_total,
                       SUM(status = 'done' AND action_key LIKE 'vip26\\_%' AND action_key NOT LIKE 'vip26\\_sunday\\_%' AND action_key <> 'vip26_activator_d01') AS vip_actions_done_total,
                       SUM(status = 'done' AND action_date = '{$today}' AND action_key = CONCAT('vip26_sunday_', DATE_FORMAT('{$today}', '%Y%m%d'))) AS vip_sunday_done_today,
                       SUM(status = 'done' AND action_date = '{$today}' AND action_key LIKE 'vip26\\_%' AND action_key <> 'vip26_activator_d01') AS vip_action_done_today,
                       MAX(CASE
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_leader\\_%' THEN 4
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_builder\\_%' THEN 3
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_activator\\_%' THEN 2
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_reactivation\\_%' THEN 1
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_starter\\_%' THEN 1
                           ELSE 0
                       END) AS vip_highest_track_rank,
                       MAX(CASE
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_builder\\_%' THEN 3
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_activator\\_%' THEN 2
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_reactivation\\_%' THEN 1
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_starter\\_%' THEN 1
                           ELSE 0
                       END) AS vip_highest_nonleader_track_rank,
                       SUBSTRING_INDEX(GROUP_CONCAT(IF(status = 'done' AND action_key LIKE 'vip26\\_%', difficulty, NULL) ORDER BY outcome_id DESC SEPARATOR ','), ',', 1) AS vip_last_difficulty,
                       SUBSTRING_INDEX(GROUP_CONCAT(IF(status = 'done' AND action_key LIKE 'vip26\\_%', completion_mode, NULL) ORDER BY outcome_id DESC SEPARATOR ','), ',', 1) AS vip_last_completion_mode,
                       MAX(IF(status = 'done', updated_at, NULL)) AS last_action_at
                FROM forever_business_daily_outcomes
                WHERE action_key <> 'vip26_activator_d01'
                GROUP BY fbo_id
            ) outcomes ON outcomes.fbo_id = m.fbo_id
            LEFT JOIN (
                SELECT recorded_by_user_id,
                       COUNT(DISTINCT IF(action_date >= '{$seven_day_start}' AND status = 'done' AND action_key LIKE 'vip26\\_%', action_date, NULL)) AS actions_done,
                       SUM(IF(action_date >= '{$seven_day_start}' AND status = 'done' AND action_key LIKE 'vip26\\_%', outcome_count, 0)) AS action_units_total,
                       SUM(status = 'done') AS actions_done_total,
                       SUM(status = 'done' AND action_key LIKE 'vip26\\_%' AND action_key NOT LIKE 'vip26\\_sunday\\_%' AND action_key <> 'vip26_activator_d01') AS vip_actions_done_total,
                       SUM(status = 'done' AND action_date = '{$today}' AND action_key = CONCAT('vip26_sunday_', DATE_FORMAT('{$today}', '%Y%m%d'))) AS vip_sunday_done_today,
                       SUM(status = 'done' AND action_date = '{$today}' AND action_key LIKE 'vip26\\_%' AND action_key <> 'vip26_activator_d01') AS vip_action_done_today,
                       MAX(CASE
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_leader\\_%' THEN 4
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_builder\\_%' THEN 3
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_activator\\_%' THEN 2
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_reactivation\\_%' THEN 1
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_starter\\_%' THEN 1
                           ELSE 0
                       END) AS vip_highest_track_rank,
                       MAX(CASE
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_builder\\_%' THEN 3
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_activator\\_%' THEN 2
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_reactivation\\_%' THEN 1
                           WHEN status = 'done' AND action_key LIKE 'vip26\\_starter\\_%' THEN 1
                           ELSE 0
                       END) AS vip_highest_nonleader_track_rank,
                       SUBSTRING_INDEX(GROUP_CONCAT(IF(status = 'done' AND action_key LIKE 'vip26\\_%', difficulty, NULL) ORDER BY outcome_id DESC SEPARATOR ','), ',', 1) AS vip_last_difficulty,
                       SUBSTRING_INDEX(GROUP_CONCAT(IF(status = 'done' AND action_key LIKE 'vip26\\_%', completion_mode, NULL) ORDER BY outcome_id DESC SEPARATOR ','), ',', 1) AS vip_last_completion_mode,
                       MAX(IF(status = 'done', updated_at, NULL)) AS last_action_at
                FROM forever_business_daily_outcomes
                WHERE recorded_by_user_id = {$user_id}
                  AND action_key <> 'vip26_activator_d01'
                GROUP BY recorded_by_user_id
            ) actor_outcomes ON actor_outcomes.recorded_by_user_id = {$user_id}
                AND m.fbo_id = '{$escaped_dashboard_root}'
            WHERE m.fbo_id IN ({$id_list})
            ORDER BY COALESCE(cur.personal_cc, 0) DESC, m.name ASC
        ";
        $result = database()->query($query);
        while($result && $row = $result->fetch_assoc()) {
            $row = forever_business_normalize_current_month_metrics($row, $period, $zagreb_now);
            $is_actor_member = $dashboard_root !== '' && (string) ($row['fbo_id'] ?? '') === $dashboard_root;
            if(!$is_actor_member) {
                foreach([
                    'actions_done_7d', 'action_units_total_7d', 'actions_done_total',
                    'vip_actions_done_total', 'vip_sunday_done_today', 'vip_action_done_today',
                    'vip_highest_track_rank', 'vip_highest_nonleader_track_rank',
                    'vip_last_difficulty', 'vip_last_completion_mode', 'last_action_at',
                ] as $progress_key) {
                    $row[$progress_key] = $row['team_' . $progress_key] ?? null;
                }
            }
            $row['force_vip_leader'] = $is_admin
                && $requested_root_id === ''
                && $dashboard_root !== ''
                && (string) ($row['fbo_id'] ?? '') === $dashboard_root;
            $metric = [
                'personal_cc' => $row['personal_cc'],
                'total_cc' => $row['total_cc'],
                'total_active_cc' => $row['total_active_cc'],
                'non_manager_cc' => $row['non_manager_cc'],
                'leadership_cc' => $row['leadership_cc'],
                'is_4cc_active' => $row['is_4cc_active'],
            ];
            $row['verified_progress'] = forever_business_get_verified_progress($row);
            $row['next_action'] = forever_business_get_action(
                $row,
                $metric,
                (int) ($row['vip_actions_done_total'] ?? 0),
                !empty($row['vip_sunday_done_today']),
                $zagreb_now,
                !empty($row['vip_action_done_today'])
            );
            $members[] = $row;
        }
    }

    $summary = [
        'members' => count($members),
        'personal_cc' => array_sum(array_map(static fn($row) => (float) ($row['personal_cc'] ?? 0), $members)),
        'personal_active' => count(array_filter($members, static fn($row) => (float) ($row['personal_cc'] ?? 0) > 0)),
        /* Keep the headline FLP360 count auditable against the source endpoint;
         * formula fallback is exposed separately and must never inflate it. */
        'active_4cc' => count(array_filter($members, static fn($row) =>
            array_key_exists('is_4cc_active', $row)
            && $row['is_4cc_active'] !== null
            && (int) $row['is_4cc_active'] === 1
        )),
        'effective_active_4cc' => count(array_filter($members, static fn($row) => forever_business_has_verified_four_cc_activity($row))),
        'zero_cc' => count(array_filter($members, static fn($row) => (float) ($row['personal_cc'] ?? 0) <= 0)),
        'managers' => count(array_filter($members, static fn($row) => !empty($row['is_manager']))),
        'focus_members' => count(array_filter($members, static fn($row) => !empty($row['focus_snapshot_date']))),
    ];
    $period_end = (new \DateTimeImmutable($period))->modify('last day of this month')->format('Y-m-d');
    $summary['recruited'] = count(array_filter($members, static fn($row) => !empty($row['sponsor_date']) && $row['sponsor_date'] >= $period && $row['sponsor_date'] <= $period_end));
    $previous_active = count(array_filter($members, static fn($row) => (float) ($row['previous_personal_cc'] ?? 0) > 0));
    $retained = count(array_filter($members, static fn($row) => (float) ($row['previous_personal_cc'] ?? 0) > 0 && (float) ($row['personal_cc'] ?? 0) > 0));
    $summary['retained'] = $retained;
    $summary['retention_rate'] = $previous_active > 0 ? round(($retained / $previous_active) * 100, 1) : 0.0;
    $summary['average_personal_cc'] = $summary['personal_active'] > 0 ? round($summary['personal_cc'] / $summary['personal_active'], 3) : 0.0;
    $summary['development_rate'] = $summary['members'] > 0 ? round(($summary['active_4cc'] / $summary['members']) * 100, 1) : 0.0;
    $official_total_cc = forever_business_get_total_cc_snapshot($dashboard_root, $period);
    $goal_current_cc = $official_total_cc ? (float) $official_total_cc['total_cc'] : (float) $summary['personal_cc'];
    $summary['goal_cc'] = 1000.0;
    $summary['goal_current_cc'] = $goal_current_cc;
    $summary['goal_metric_source'] = $official_total_cc ? 'FLP360 Global Total CC · ' . $official_total_cc['country_scope'] : 'FCC zbroj osobnih CC';
    $summary['goal_is_closed'] = $official_total_cc ? (bool) $official_total_cc['is_closed'] : false;
    $summary['goal_gap_cc'] = max(0, round($summary['goal_cc'] - $goal_current_cc, 3));
    $summary['goal_progress'] = min(100, round(($goal_current_cc / $summary['goal_cc']) * 100, 1));

    $trend_periods = array_reverse(array_slice(array_values(array_filter($periods, static fn($trend_period) => $trend_period <= $period)), 0, 8));
    if(!$is_admin) {
        /* A collaborator's chart must use their imported Total CC, while activity colour
         * follows the official tri-state result, with the complete 1 + 4 formula used
         * only when that official result is unavailable. */
        $metric_rows = [];
        if($dashboard_root !== '') {
            $escaped_dashboard_root = database()->real_escape_string($dashboard_root);
            $escaped_dashboard_period = database()->real_escape_string($period);
            $trend_rows = database()->query("SELECT metric.period_month, metric.total_cc, metric.personal_cc, metric.total_active_cc,
                    CASE WHEN metric.source_import_id IS NOT NULL AND (metric_source.import_id IS NULL OR metric_source.report_kind NOT IN ('downline', 'four_cc_active')) THEN NULL ELSE metric.is_4cc_active END AS is_4cc_active
                FROM forever_business_metrics metric
                LEFT JOIN forever_business_imports metric_source ON metric_source.import_id = metric.source_import_id
                WHERE metric.fbo_id = '{$escaped_dashboard_root}' AND metric.period_month <= '{$escaped_dashboard_period}'
                ORDER BY metric.period_month DESC
                LIMIT 8");
            while($trend_rows && $row = $trend_rows->fetch_assoc()) {
                $metric_rows[$row['period_month']] = $row;
            }
        }

        $trend = [];
        foreach($trend_periods as $trend_period) {
            $row = forever_business_normalize_current_month_metrics(
                (array) ($metric_rows[$trend_period] ?? []),
                $trend_period,
                $zagreb_now
            );
            $has_activity_data = !empty($row) && (
                (array_key_exists('is_4cc_active', $row) && $row['is_4cc_active'] !== null)
                || (isset($row['personal_cc'], $row['total_active_cc']))
            );
            $is_verified_active = $has_activity_data && forever_business_has_verified_four_cc_activity($row);
            $trend[] = [
                'period_month' => $trend_period,
                'total_cc' => (float) ($row['total_cc'] ?? 0),
                'is_closed' => $trend_period < $current_zagreb_period ? 1 : 0,
                'country_scope' => 'FCC',
                'has_activity_data' => $has_activity_data,
                'is_4cc_active' => $is_verified_active,
            ];
        }
    } else {
        $trend = forever_business_get_total_cc_trend($dashboard_root, $period);
        if(empty($trend)) {
            foreach($trend_periods as $trend_period) {
                $sum = 0.0;
                if(!empty($scope_ids)) {
                    $row = database()->query("SELECT COALESCE(SUM(personal_cc), 0) AS total FROM forever_business_metrics WHERE period_month = '{$trend_period}' AND fbo_id IN ({$id_list})")->fetch_assoc();
                    $sum = (float) ($row['total'] ?? 0);
                }
                $trend[] = ['period_month' => $trend_period, 'total_cc' => $sum, 'is_closed' => $trend_period < $current_zagreb_period ? 1 : 0, 'country_scope' => 'FCC'];
            }
        }
        if($period === $current_zagreb_period
            && !array_filter($trend, static fn($row) => (string) ($row['period_month'] ?? '') === $period)) {
            $trend = array_slice($trend, -7);
            $current_fcc_total_cc = (float) ($summary['personal_cc'] ?? 0);
            $trend[] = [
                'period_month' => $period,
                'total_cc' => $current_fcc_total_cc,
                'is_closed' => 0,
                'country_scope' => 'GLOBAL',
                'source_note' => $current_fcc_total_cc > 0
                    ? 'FCC zbroj osobnih CC dok službeni Global Total CC nije dostupan'
                    : 'Otvoreni mjesec bez sinkroniziranih narudžbi',
            ];
        }
    }
    $closed_trend = array_values(array_filter($trend, static fn($row) => !empty($row['is_closed'])));
    $closed_six = array_slice($closed_trend, -6);
    $closed_six_values = array_map(static fn($row) => (float) $row['total_cc'], $closed_six);
    $summary['closed_6m_average_cc'] = !empty($closed_six_values) ? round(array_sum($closed_six_values) / count($closed_six_values), 3) : 0.0;
    $summary['latest_closed_cc'] = !empty($closed_trend) ? (float) end($closed_trend)['total_cc'] : 0.0;
    $summary['goal_multiplier_from_average'] = $summary['closed_6m_average_cc'] > 0 ? round($summary['goal_cc'] / $summary['closed_6m_average_cc'], 2) : 0.0;

    $access_roots = $is_admin
        ? db()->where('is_manager', 1)->where('is_in_current_structure', 1)->orderBy('generation', 'ASC')->orderBy('name', 'ASC')->get('forever_business_members', null, ['fbo_id', 'name', 'title']) ?? []
        : [];

    $official_four_core = forever_business_get_four_core_snapshot($dashboard_root, $period);
    $last_sync = db()->where('status', 'completed')->orderBy('completed_at', 'DESC')->getOne('forever_business_imports', ['completed_at']);
    $last_sync_check = db()->orderBy('checked_at', 'DESC')->getOne('forever_business_sync_checks', ['report_kind', 'original_name', 'is_duplicate', 'checked_at']);
    $last_sync_at = $last_sync_check->checked_at ?? ($last_sync->completed_at ?? null);

    return [
        'period' => $period,
        'previous_period' => $previous_period,
        'periods' => $periods,
        'scope_ids' => $scope_ids,
        'access_roots' => $access_roots,
        'members' => $members,
        'summary' => $summary,
        'trend' => $trend,
        'is_manager_view' => count($scope_ids) > 1,
        'official_four_core' => $official_four_core,
        'official_total_cc' => $official_total_cc,
        'last_sync_at' => $last_sync_at,
        'last_sync_report_kind' => $last_sync_check->report_kind ?? null,
        'last_sync_original_name' => $last_sync_check->original_name ?? null,
        'last_sync_was_duplicate' => isset($last_sync_check->is_duplicate) ? (bool) $last_sync_check->is_duplicate : false,
        'last_data_import_at' => $last_sync->completed_at ?? null,
    ];
}

function forever_business_normalize_outcome_count($value): ?int {
    if(!is_scalar($value) || !preg_match('/^\d+$/', trim((string) $value))) return null;
    $count = (int) $value;
    return $count >= 1 && $count <= 999 ? $count : null;
}

/* Custom code: FC-2026-08-21: small structured vocabulary for useful coaching analytics */
function forever_business_vip_result_type_options(): array {
    return [
        'contact' => 'Kontakt / javljanje',
        'conversation' => 'Kvalitetan razgovor',
        'invitation' => 'Poziv / gost',
        'follow_up' => 'Dogovoreni follow-up',
        'customer_checkin' => 'Provjera kupca',
        'recommendation' => 'Preporuka proizvoda',
        'order' => 'Narudžba',
        'new_partner' => 'Novi suradnik',
        'content' => 'Objava / sadržaj',
        'planning' => 'Planiranje / priprema',
        'training' => 'Edukacija / trening',
        'coaching' => 'Podrška članu tima',
        'onboarding' => 'Uvođenje nove osobe',
        'event' => 'Marketing plan / događaj',
        'no_response' => 'Aktivnost bez odgovora',
        'other' => 'Drugi mjerljiv rezultat',
    ];
}

function forever_business_normalize_result_type($value): ?string {
    $value = trim((string) $value);
    return array_key_exists($value, forever_business_vip_result_type_options()) ? $value : null;
}

function forever_business_vip_difficulty_options(): array {
    return [
        'easy' => 'Lako',
        'normal' => 'Izvedivo',
        'hard' => 'Teško',
    ];
}

function forever_business_normalize_difficulty($value): ?string {
    $value = trim((string) $value);
    return array_key_exists($value, forever_business_vip_difficulty_options()) ? $value : null;
}

function forever_business_vip_completion_mode_for_count(array $action, int $outcome_count): ?string {
    $target = max(1, (int) ($action['target'] ?? 1));
    $quick_target = max(1, min($target, (int) ($action['quick_target'] ?? $target)));
    if($outcome_count >= $target) return 'standard';
    if($outcome_count >= $quick_target) return 'quick';
    return null;
}

function forever_business_normalize_completion_mode($value): ?string {
    $value = trim((string) $value);
    return in_array($value, ['standard', 'quick'], true) ? $value : null;
}
/* /Custom code: FC-2026-08-21 */

function forever_business_record_daily_outcome(int $user_id, string $fbo_id, array $scope_ids, array $input, ?\DateTimeInterface $now = null): bool {
    forever_business_ensure_tables();
    if($user_id <= 0) return false;
    $timezone = new \DateTimeZone('Europe/Zagreb');
    $zagreb_now = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    if($fbo_id === '' || !in_array($fbo_id, $scope_ids, true)) {
        return false;
    }

    $allowed_cores = ['Recruitment', 'Retention', 'Productivity', 'Development'];
    $core = trim((string) ($input['core_key'] ?? ''));
    if(!in_array($core, $allowed_cores, true)) {
        return false;
    }

    $action_key = mb_substr(preg_replace('/[^a-z0-9_\-]/i', '', (string) ($input['action_key'] ?? '')), 0, 48);
    $outcome_count = forever_business_normalize_outcome_count($input['outcome_count'] ?? null);
    $result_type = forever_business_normalize_result_type($input['result_type'] ?? null);
    $difficulty = forever_business_normalize_difficulty($input['difficulty'] ?? null);
    $completion_mode = forever_business_normalize_completion_mode($input['completion_mode'] ?? null);
    $needs_help = !empty($input['needs_help']) ? 1 : 0;
    if($action_key === '' || !str_starts_with($action_key, 'vip26_') || $outcome_count === null || $result_type === null || $difficulty === null || $completion_mode === null) {
        return false;
    }

    $action_date = $zagreb_now->format('Y-m-d');
    $timestamp = get_date();
    db()->startTransaction();

    try {
        /* Progress belongs to the authenticated FCC account. Locking that user
         * serializes two concurrent submits from the same participant while
         * allowing two approved accounts that share an FBO to progress alone. */
        $lock = database()->query("SELECT `user_id`, `preferences`
            FROM `users`
            WHERE `user_id` = {$user_id} AND `status` = 1
            LIMIT 1 FOR UPDATE");
        $locked_user = $lock ? $lock->fetch_assoc() : null;
        $locked_fbo_id = $locked_user ? forever_business_extract_user_fbo_id($locked_user['preferences'] ?? null) : '';
        if(!$locked_user || $locked_fbo_id === '' || !hash_equals($locked_fbo_id, $fbo_id)) {
            throw new \RuntimeException('vip_participant_lock_failed');
        }

        $existing = database()->query("SELECT outcome_id FROM forever_business_daily_outcomes
            WHERE recorded_by_user_id = {$user_id} AND status = 'done'
              AND ((action_date = '{$action_date}' AND action_key LIKE 'vip26\\_%' AND action_key <> 'vip26_activator_d01') OR action_key = '{$action_key}')
            LIMIT 1");
        if(!$existing) {
            throw new \RuntimeException('vip_outcome_lookup_failed');
        }
        if($existing->fetch_assoc()) {
            db()->rollback();
            return false;
        }

        $inserted = db()->insert('forever_business_daily_outcomes', [
            'fbo_id' => $fbo_id,
            'action_date' => $action_date,
            'core_key' => $core,
            'action_key' => $action_key,
            'status' => 'done',
            'outcome_count' => $outcome_count,
            'outcome_type' => mb_substr(input_clean($input['outcome_type'] ?? '', 32), 0, 32) ?: null,
            'result_type' => $result_type,
            'difficulty' => $difficulty,
            'needs_help' => $needs_help,
            'completion_mode' => $completion_mode,
            'note' => mb_substr(input_clean($input['note'] ?? '', 500), 0, 500) ?: null,
            'recorded_by_user_id' => $user_id,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        if($inserted === false) throw new \RuntimeException('vip_outcome_insert_failed');

        $help_note = mb_substr(input_clean($input['note'] ?? '', 500), 0, 500) ?: null;
        if($needs_help) {
            $escaped_action_key = database()->real_escape_string($action_key);
            $escaped_track_key = database()->real_escape_string(mb_substr((string) ($input['outcome_type'] ?? ''), 0, 24));
            $escaped_difficulty = database()->real_escape_string($difficulty);
            $escaped_help_note = $help_note === null ? 'NULL' : "'" . database()->real_escape_string($help_note) . "'";
            $sequence_position = max(0, min(30, (int) ($input['sequence_position'] ?? 0)));
            $help_saved = database()->query("INSERT INTO `forever_business_vip_help_requests`
                (`user_id`, `fbo_id`, `action_key`, `track_key`, `sequence_position`, `request_date`, `difficulty`, `note`, `status`, `resolved_at`, `resolved_by_user_id`, `created_at`, `updated_at`)
                VALUES ({$user_id}, '{$fbo_id}', '{$escaped_action_key}', '{$escaped_track_key}', {$sequence_position}, '{$action_date}', '{$escaped_difficulty}', {$escaped_help_note}, 'open', NULL, NULL, '{$timestamp}', '{$timestamp}')
                ON DUPLICATE KEY UPDATE `fbo_id` = VALUES(`fbo_id`), `track_key` = VALUES(`track_key`),
                    `sequence_position` = VALUES(`sequence_position`), `request_date` = VALUES(`request_date`),
                    `difficulty` = VALUES(`difficulty`), `note` = VALUES(`note`), `status` = 'open',
                    `resolved_at` = NULL, `resolved_by_user_id` = NULL, `updated_at` = VALUES(`updated_at`)");
            if(!$help_saved) throw new \RuntimeException('vip_help_request_upsert_failed');
        } else {
            $escaped_action_key = database()->real_escape_string($action_key);
            /* Only one curriculum action can be current for an account. Once a
             * participant completes any later/current action, older open help
             * requests (including a dated Sunday action or a pre-promotion
             * track) are historical and must not remain falsely actionable. */
            $help_resolved = database()->query("UPDATE `forever_business_vip_help_requests`
                SET `status` = 'resolved', `resolved_at` = '{$timestamp}', `resolved_by_user_id` = {$user_id}, `updated_at` = '{$timestamp}'
                WHERE `user_id` = {$user_id} AND `status` = 'open'");
            if(!$help_resolved) throw new \RuntimeException('vip_help_request_resolve_failed');
        }

        db()->commit();
        return true;
    } catch(\Throwable $exception) {
        db()->rollback();
        error_log('Forever VIP outcome save failed: ' . $exception->getMessage());
        return false;
    }
}

function forever_business_request_vip_help(int $user_id, string $fbo_id, array $scope_ids, array $input, ?\DateTimeInterface $now = null): bool {
    forever_business_ensure_tables();
    if($user_id <= 0) return false;

    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $action_key = mb_substr(preg_replace('/[^a-z0-9_\-]/i', '', (string) ($input['action_key'] ?? '')), 0, 48);
    $track_key = mb_substr(preg_replace('/[^a-z0-9_\-]/i', '', (string) ($input['track_key'] ?? '')), 0, 24);
    $sequence_position = max(0, min(30, (int) ($input['sequence_position'] ?? 0)));
    $difficulty = forever_business_normalize_difficulty($input['difficulty'] ?? 'hard');
    $note = mb_substr(input_clean($input['note'] ?? '', 500), 0, 500);
    if($fbo_id === '' || !in_array($fbo_id, $scope_ids, true) || !str_starts_with($action_key, 'vip26_') || $difficulty === null || mb_strlen(trim($note)) < 3) {
        return false;
    }

    $timezone = new \DateTimeZone('Europe/Zagreb');
    $zagreb_now = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);
    $request_date = $zagreb_now->format('Y-m-d');
    $timestamp = get_date();
    db()->startTransaction();

    try {
        $lock = database()->query("SELECT `user_id`, `preferences`
            FROM `users`
            WHERE `user_id` = {$user_id} AND `status` = 1
            LIMIT 1 FOR UPDATE");
        $locked_user = $lock ? $lock->fetch_assoc() : null;
        $locked_fbo_id = $locked_user ? forever_business_extract_user_fbo_id($locked_user['preferences'] ?? null) : '';
        if(!$locked_user || $locked_fbo_id === '' || !hash_equals($locked_fbo_id, $fbo_id)) {
            throw new \RuntimeException('vip_help_participant_lock_failed');
        }

        $escaped_action_key = database()->real_escape_string($action_key);
        /* The participant lock serializes help and completion submissions from
         * separate tabs. Never reopen help for an action (or a second VIP
         * action today) after the corresponding completion has committed. */
        $completed = database()->query("SELECT outcome_id FROM forever_business_daily_outcomes
            WHERE recorded_by_user_id = {$user_id} AND status = 'done'
              AND ((action_date = '{$request_date}' AND action_key LIKE 'vip26\\_%' AND action_key <> 'vip26_activator_d01') OR action_key = '{$escaped_action_key}')
            LIMIT 1");
        if(!$completed) {
            throw new \RuntimeException('vip_help_completion_lookup_failed');
        }
        if($completed->fetch_assoc()) {
            db()->rollback();
            return false;
        }

        $escaped_track_key = database()->real_escape_string($track_key);
        $escaped_difficulty = database()->real_escape_string($difficulty);
        $escaped_note = database()->real_escape_string($note);
        $saved = database()->query("INSERT INTO `forever_business_vip_help_requests`
            (`user_id`, `fbo_id`, `action_key`, `track_key`, `sequence_position`, `request_date`, `difficulty`, `note`, `status`, `resolved_at`, `resolved_by_user_id`, `created_at`, `updated_at`)
            VALUES ({$user_id}, '{$fbo_id}', '{$escaped_action_key}', '{$escaped_track_key}', {$sequence_position}, '{$request_date}', '{$escaped_difficulty}', '{$escaped_note}', 'open', NULL, NULL, '{$timestamp}', '{$timestamp}')
            ON DUPLICATE KEY UPDATE `fbo_id` = VALUES(`fbo_id`), `track_key` = VALUES(`track_key`),
                `sequence_position` = VALUES(`sequence_position`), `request_date` = VALUES(`request_date`),
                `difficulty` = VALUES(`difficulty`), `note` = VALUES(`note`), `status` = 'open',
                `resolved_at` = NULL, `resolved_by_user_id` = NULL, `updated_at` = VALUES(`updated_at`)");
        if(!$saved) throw new \RuntimeException('vip_help_request_save_failed');

        db()->commit();
        return true;
    } catch(\Throwable $exception) {
        db()->rollback();
        error_log('Forever VIP help request failed: ' . $exception->getMessage());
        return false;
    }
}

function forever_business_record_page_visit(int $user_id, ?\DateTimeInterface $now = null): void {
    forever_business_ensure_tables();
    if($user_id <= 0) return;
    $timezone = new \DateTimeZone('Europe/Zagreb');
    $zagreb_now = $now
        ? (new \DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($timezone)
        : new \DateTimeImmutable('now', $timezone);
    $today = $zagreb_now->format('Y-m-d');
    $now = get_date();
    database()->query("INSERT INTO forever_business_page_visits (user_id, visit_date, visit_count, last_visit_at)
        VALUES ({$user_id}, '{$today}', 1, '{$now}')
        ON DUPLICATE KEY UPDATE visit_count = visit_count + 1, last_visit_at = VALUES(last_visit_at)");
}

function forever_business_get_usage_summary(): array {
    forever_business_ensure_tables();
    $since_30d = (new \DateTimeImmutable())->modify('-30 days')->format('Y-m-d H:i:s');
    $since_180d = (new \DateTimeImmutable())->modify('-180 days')->format('Y-m-d H:i:s');
    /* VIP action_date is recorded as an Europe/Zagreb calendar date. Use the
     * same boundary for 7/30-day analytics so midnight cannot shift a task
     * into the wrong reporting window. */
    $zagreb_now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Zagreb'));
    $current_zagreb_period = $zagreb_now->format('Y-m-01');
    $since_7d_date = $zagreb_now->modify('-6 days')->format('Y-m-d');
    $since_30d_date = $zagreb_now->modify('-29 days')->format('Y-m-d');
    $account_raw_fbo_sql = forever_business_user_fbo_sql_expression('preferences', false);
    $account_fbo_sql = forever_business_user_fbo_sql_expression('preferences');
    $user_fbo_sql = forever_business_user_fbo_sql_expression('u.preferences');

    $accounts_result = database()->query("SELECT
        COUNT(*) AS regular_accounts,
        SUM(status = 1) AS enabled_accounts,
        SUM(last_activity >= '{$since_30d}') AS active_30d,
        SUM(last_activity >= '{$since_180d}') AS active_180d,
        SUM(last_activity IS NULL) AS never_active
        FROM users WHERE type = 0");
    $accounts = $accounts_result ? $accounts_result->fetch_assoc() : [];

    $account_id_quality_result = database()->query("SELECT
        SUM(raw_fbo_id = '') AS accounts_missing_fbo_id,
        SUM(raw_fbo_id <> '' AND normalized_fbo_id NOT REGEXP '^[0-9]{12}$') AS accounts_invalid_fbo_id
        FROM (
            SELECT
                {$account_raw_fbo_sql} AS raw_fbo_id,
                {$account_fbo_sql} AS normalized_fbo_id
            FROM users
            WHERE type = 0
        ) account_ids");
    $account_id_quality = $account_id_quality_result ? $account_id_quality_result->fetch_assoc() : [];

    $duplicate_ids_result = database()->query("SELECT
        COUNT(*) AS duplicate_fbo_id_groups,
        COALESCE(SUM(accounts_per_id - 1), 0) AS duplicate_fbo_id_extra_accounts
        FROM (
            SELECT
                {$account_fbo_sql} AS normalized_fbo_id,
                COUNT(*) AS accounts_per_id
            FROM users
            WHERE type = 0
              AND {$account_fbo_sql} REGEXP '^[0-9]{12}$'
            GROUP BY normalized_fbo_id
            HAVING COUNT(*) > 1
        ) duplicate_ids");
    $duplicate_ids = $duplicate_ids_result ? $duplicate_ids_result->fetch_assoc() : [];

    $profile_accounts_result = database()->query("SELECT
        COUNT(*) AS accounts_with_valid_fbo_id,
        SUM(u.status = 1) AS enabled_with_valid_fbo_id,
        SUM(m.fbo_id IS NOT NULL) AS accounts_with_personal_profile,
        SUM(u.status = 1 AND m.fbo_id IS NOT NULL) AS enabled_with_personal_profile,
        SUM(m.fbo_id IS NOT NULL AND m.is_in_current_structure = 0) AS waiting_profile_accounts
        FROM users u
        LEFT JOIN forever_business_members m
            ON m.fbo_id = {$user_fbo_sql}
        WHERE u.type = 0
          AND {$user_fbo_sql} REGEXP '^[0-9]{12}$'");
    $profile_accounts = $profile_accounts_result ? $profile_accounts_result->fetch_assoc() : [];

    $team_result = database()->query("SELECT
        COUNT(*) AS matched_team_accounts,
        SUM(u.last_activity >= '{$since_30d}') AS matched_active_30d,
        SUM(u.last_activity >= '{$since_180d}') AS matched_active_180d
        FROM users u
        INNER JOIN forever_business_members m
            ON m.fbo_id = {$user_fbo_sql}
            AND m.is_in_current_structure = 1
        WHERE u.type = 0");
    $team = $team_result ? $team_result->fetch_assoc() : [];

    $current_members_result = database()->query("SELECT
        COUNT(*) AS current_team_members,
        SUM(EXISTS(
            SELECT 1 FROM users u
            WHERE u.type = 0
              AND {$user_fbo_sql} = m.fbo_id
        )) AS current_members_with_fcc_account,
        SUM(NOT EXISTS(
            SELECT 1 FROM users u
            WHERE u.type = 0
              AND {$user_fbo_sql} = m.fbo_id
        )) AS current_members_without_fcc_account,
        SUM(EXISTS(
            SELECT 1 FROM users u
            WHERE u.type = 0 AND u.status = 1
              AND {$user_fbo_sql} = m.fbo_id
        )) AS current_members_with_enabled_fcc_account
        FROM forever_business_members m
        WHERE m.is_in_current_structure = 1");
    $current_members = $current_members_result ? $current_members_result->fetch_assoc() : [];

    $latest_metrics_result = database()->query("SELECT
        COUNT(*) AS current_members_with_latest_cc,
        SUM(metric.fbo_id IS NULL) AS current_members_missing_latest_cc
        FROM forever_business_members m
        LEFT JOIN forever_business_metrics metric
          ON metric.fbo_id = m.fbo_id
         AND metric.period_month = (SELECT MAX(period_month) FROM forever_business_metrics WHERE period_month <= '{$current_zagreb_period}')
        WHERE m.is_in_current_structure = 1");
    $latest_metrics = $latest_metrics_result ? $latest_metrics_result->fetch_assoc() : [];

    $managers_result = database()->query("SELECT
        COUNT(*) AS imported_managers,
        SUM(EXISTS(
            SELECT 1 FROM users u
            WHERE {$user_fbo_sql} = m.fbo_id
        )) AS managers_with_fcc_account
        FROM forever_business_members m
        WHERE m.is_manager = 1 AND m.is_in_current_structure = 1");
    $managers = $managers_result ? $managers_result->fetch_assoc() : [];

    $visits_result = database()->query("SELECT
        COUNT(DISTINCT IF(visit_date >= '{$since_7d_date}', user_id, NULL)) AS four_core_users_7d,
        COUNT(DISTINCT IF(visit_date >= '{$since_30d_date}', user_id, NULL)) AS four_core_users_30d,
        COALESCE(SUM(IF(visit_date >= '{$since_30d_date}', visit_count, 0)), 0) AS four_core_visits_30d
        FROM forever_business_page_visits");
    $visits = $visits_result ? $visits_result->fetch_assoc() : [];

    $vip_outcomes_result = database()->query("SELECT
        COUNT(DISTINCT IF(action_date >= '{$since_7d_date}' AND status = 'done' AND action_key LIKE 'vip26\\_%', recorded_by_user_id, NULL)) AS vip_participants_7d,
        COUNT(DISTINCT IF(action_date >= '{$since_30d_date}' AND status = 'done' AND action_key LIKE 'vip26\\_%', recorded_by_user_id, NULL)) AS vip_participants_30d,
        SUM(action_date >= '{$since_7d_date}' AND status = 'done' AND action_key LIKE 'vip26\\_%') AS vip_tasks_completed_7d,
        SUM(action_date >= '{$since_30d_date}' AND status = 'done' AND action_key LIKE 'vip26\\_%') AS vip_tasks_completed_30d,
        COALESCE(SUM(IF(action_date >= '{$since_30d_date}' AND status = 'done' AND action_key LIKE 'vip26\\_%', outcome_count, 0)), 0) AS vip_recorded_action_units_30d
        FROM forever_business_daily_outcomes");
    $vip_outcomes = $vip_outcomes_result ? $vip_outcomes_result->fetch_assoc() : [];

    $privacy_result = database()->query("SELECT COUNT(*) AS active_team_access_records
        FROM forever_business_access
        WHERE status = 'active'");
    $privacy = $privacy_result ? $privacy_result->fetch_assoc() : [];

    $result = array_merge($accounts, $account_id_quality, $duplicate_ids, $profile_accounts, $team, $current_members, $latest_metrics, $managers, $visits, $vip_outcomes, $privacy);
    return array_map(static fn($value) => (int) ($value ?? 0), $result);
}

function forever_business_get_account_audit_rows(): array {
    forever_business_ensure_tables();
    $account_fbo_sql = forever_business_user_fbo_sql_expression('preferences');
    $user_raw_fbo_sql = forever_business_user_fbo_sql_expression('u.preferences', false);
    $user_fbo_sql = forever_business_user_fbo_sql_expression('u.preferences');

    $fetch_rows = static function($result): array {
        $rows = [];
        while($result && $row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    };

    /* This detailed payload is exposed only through the signed machine-sync endpoint.
       It is intended for a private administrator reconciliation workbook. */
    $invalid_accounts = $fetch_rows(database()->query("SELECT
        u.user_id, u.name AS account_name, u.email, u.status, u.last_activity,
        {$user_raw_fbo_sql} AS raw_fbo_id
        FROM users u
        WHERE u.type = 0
          AND {$user_fbo_sql} NOT REGEXP '^[0-9]{12}$'
        ORDER BY u.name ASC, u.user_id ASC"));

    $duplicate_accounts = $fetch_rows(database()->query("SELECT
        normalized.normalized_fbo_id AS fbo_id,
        duplicate_ids.accounts_per_id,
        u.user_id, u.name AS account_name, u.email, u.status, u.last_activity,
        m.name AS flp_name, m.title AS flp_title, m.country_code,
        COALESCE(m.is_in_current_structure, 0) AS is_in_current_structure
        FROM users u
        INNER JOIN (
            SELECT
                user_id,
                {$account_fbo_sql} AS normalized_fbo_id
            FROM users
            WHERE type = 0
        ) normalized ON normalized.user_id = u.user_id
        INNER JOIN (
            SELECT
                {$account_fbo_sql} AS normalized_fbo_id,
                COUNT(*) AS accounts_per_id
            FROM users
            WHERE type = 0
              AND {$account_fbo_sql} REGEXP '^[0-9]{12}$'
            GROUP BY normalized_fbo_id
            HAVING COUNT(*) > 1
        ) duplicate_ids ON duplicate_ids.normalized_fbo_id = normalized.normalized_fbo_id
        LEFT JOIN forever_business_members m ON m.fbo_id = normalized.normalized_fbo_id
        WHERE u.type = 0
        ORDER BY normalized.normalized_fbo_id ASC, u.status DESC, u.last_activity DESC, u.user_id ASC"));

    $outside_current_structure = $fetch_rows(database()->query("SELECT
        u.user_id, u.name AS account_name, u.email, u.status, u.last_activity,
        m.fbo_id, m.name AS flp_name, m.title AS flp_title, m.country_code,
        m.generation, m.updated_at AS flp_profile_updated_at
        FROM users u
        INNER JOIN forever_business_members m
          ON m.fbo_id = {$user_fbo_sql}
         AND m.is_in_current_structure = 0
        WHERE u.type = 0
        ORDER BY u.name ASC, u.user_id ASC"));

    $valid_without_profile = $fetch_rows(database()->query("SELECT
        u.user_id, u.name AS account_name, u.email, u.status, u.last_activity,
        {$user_fbo_sql} AS fbo_id
        FROM users u
        LEFT JOIN forever_business_members m
          ON m.fbo_id = {$user_fbo_sql}
        WHERE u.type = 0
          AND {$user_fbo_sql} REGEXP '^[0-9]{12}$'
          AND m.fbo_id IS NULL
        ORDER BY u.name ASC, u.user_id ASC"));

    $current_members_without_account = $fetch_rows(database()->query("SELECT
        m.fbo_id, m.name AS flp_name, m.title AS flp_title, m.country_code,
        m.generation, m.sponsor_date, m.parent_fbo_id, m.updated_at AS flp_profile_updated_at
        FROM forever_business_members m
        WHERE m.is_in_current_structure = 1
          AND NOT EXISTS(
              SELECT 1 FROM users u
              WHERE u.type = 0
                AND {$user_fbo_sql} = m.fbo_id
          )
        ORDER BY m.generation ASC, m.name ASC, m.fbo_id ASC"));

    $disabled_accounts = $fetch_rows(database()->query("SELECT
        u.user_id, u.name AS account_name, u.email, u.status, u.last_activity,
        {$user_raw_fbo_sql} AS raw_fbo_id
        FROM users u
        WHERE u.type = 0 AND u.status <> 1
        ORDER BY u.name ASC, u.user_id ASC"));

    $managers_without_account = $fetch_rows(database()->query("SELECT
        m.fbo_id, m.name AS flp_name, m.title AS flp_title, m.country_code,
        m.generation, m.sponsor_date, m.parent_fbo_id
        FROM forever_business_members m
        WHERE m.is_in_current_structure = 1 AND m.is_manager = 1
          AND NOT EXISTS(
              SELECT 1 FROM users u
              WHERE u.type = 0
                AND {$user_fbo_sql} = m.fbo_id
          )
        ORDER BY m.generation ASC, m.name ASC, m.fbo_id ASC"));

    return [
        'invalid_accounts' => $invalid_accounts,
        'duplicate_accounts' => $duplicate_accounts,
        'outside_current_structure' => $outside_current_structure,
        'valid_without_profile' => $valid_without_profile,
        'current_members_without_account' => $current_members_without_account,
        'disabled_accounts' => $disabled_accounts,
        'managers_without_account' => $managers_without_account,
    ];
}

function forever_business_grant_access(int $user_id, string $fbo_id, string $role, int $granted_by_user_id): bool {
    /* Self-only privacy mode: no collaborator account can receive a team scope. */
    return false;
}

function forever_business_revoke_access(int $access_id): bool {
    forever_business_ensure_tables();
    return (bool) db()->where('access_id', $access_id)->update('forever_business_access', ['status' => 'revoked', 'updated_at' => get_date()]);
}

function forever_business_grant_exact_manager_accesses(int $granted_by_user_id): int {
    return 0;
}

function forever_business_enforce_self_only_access(): int {
    forever_business_ensure_tables();
    db()->where('status', 'active')->update('forever_business_access', [
        'status' => 'revoked',
        'updated_at' => get_date(),
    ]);
    return (int) db()->count;
}

function forever_business_get_admin_data(int $user_id, string $period = ''): array {
    forever_business_ensure_tables();
    $dashboard = forever_business_get_dashboard($user_id, true, '', $period);
    $imports = db()->orderBy('import_id', 'DESC')->get('forever_business_imports', 20) ?? [];
    $access = db()->join('users u', 'u.user_id = a.user_id', 'LEFT')
        ->join('forever_business_members m', 'm.fbo_id = a.fbo_id', 'LEFT')
        ->orderBy('a.updated_at', 'DESC')
        ->get('forever_business_access a', null, ['a.*', 'u.name AS user_name', 'u.email AS user_email', 'm.name AS member_name', 'm.title AS member_title']) ?? [];
    $managers = db()->where('is_manager', 1)->where('is_in_current_structure', 1)->orderBy('generation', 'ASC')->orderBy('name', 'ASC')->get('forever_business_members') ?? [];
    $users = db()->where('status', 1)->orderBy('name', 'ASC')->get('users', null, ['user_id', 'name', 'email', 'preferences']) ?? [];
    $users_by_fbo = [];
    $users_by_email_hash = [];

    foreach($users as $user) {
        $fbo_id = forever_business_extract_user_fbo_id($user->preferences ?? null);
        if($fbo_id !== '') {
            $users_by_fbo[$fbo_id][] = $user;
        }
        $email_hash = forever_business_contact_hash($user->email ?? '');
        if($email_hash) {
            $users_by_email_hash[$email_hash][] = $user;
        }
    }

    $active_access_keys = [];
    foreach($access as $access_row) {
        if($access_row->status === 'active') {
            $active_access_keys[$access_row->user_id . '|' . $access_row->fbo_id] = true;
        }
    }

    $suggestions = [];
    foreach($managers as $manager) {
        $candidates = [];
        foreach($users_by_fbo[$manager->fbo_id] ?? [] as $candidate) {
            $candidates[(int) $candidate->user_id] = ['user' => $candidate, 'reason' => 'Forever ID'];
        }
        foreach(!empty($manager->email_hash) ? ($users_by_email_hash[$manager->email_hash] ?? []) : [] as $candidate) {
            if(!isset($candidates[(int) $candidate->user_id])) {
                $candidates[(int) $candidate->user_id] = ['user' => $candidate, 'reason' => 'e-mail'];
            }
        }
        $suggestions[] = [
            'manager' => $manager,
            'candidates' => array_values($candidates),
            'active_user_ids' => array_values(array_filter(array_map(static function($candidate) use ($active_access_keys, $manager) {
                $id = (int) $candidate['user']->user_id;
                return isset($active_access_keys[$id . '|' . $manager->fbo_id]) ? $id : null;
            }, array_values($candidates)))),
        ];
    }

    return [
        'dashboard' => $dashboard,
        'usage' => forever_business_get_usage_summary(),
        'self_only_mode' => true,
        'imports' => $imports,
        'access' => $access,
        'managers' => $managers,
        'users' => $users,
        'suggestions' => $suggestions,
    ];
}

/* /Custom code: FC-2026-08-13 */
