-- Lejla Kovacevic main-app repair and AI app-review reset
-- Target user:
--   user_id = 855
--   email   = lejlaflp35@gmail.com
--
-- Goal:
-- 1. Set forevercard.club/Lejla (link_id 2181) as the main FCC app.
-- 2. Reset app-review level AI state so a fresh app review can be generated.
--
-- IMPORTANT
-- 1. Run on live only after reviewing scripts/check_lejla_kovacevic_clicks_2026_04_10.sql.
-- 2. Copy the returned backup_key from the final SELECT.
-- 3. If anything looks wrong, use scripts/rollback_lejla_kovacevic_main_app_and_ai_reset_template.sql.

CREATE TABLE IF NOT EXISTS `ops_support_users_preferences_backup` (
  `snapshot_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `backup_key` varchar(96) NOT NULL,
  `captured_at` datetime NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `preferences` longtext DEFAULT NULL,
  PRIMARY KEY (`snapshot_id`),
  KEY `backup_key` (`backup_key`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ops_support_users_biolinks_backup` (
  `snapshot_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `backup_key` varchar(96) NOT NULL,
  `captured_at` datetime NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `biolink_id` int(11) NOT NULL,
  PRIMARY KEY (`snapshot_id`),
  KEY `backup_key` (`backup_key`),
  KEY `user_id` (`user_id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ops_support_links_backup` (
  `snapshot_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `backup_key` varchar(96) NOT NULL,
  `captured_at` datetime NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `link_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `additional` longtext DEFAULT NULL,
  `last_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`snapshot_id`),
  KEY `backup_key` (`backup_key`),
  KEY `link_id` (`link_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET @target_user_id := 855;
SET @target_main_link_id := 2181;
SET @backup_key := CONCAT('lejla_main_app_ai_reset_', DATE_FORMAT(NOW(), '%Y%m%d_%H%i%s'));
SET @repair_note := 'Lejla main app -> /Lejla and AI app-review reset 2026-04-10';
SET @repair_now := NOW();
SET @current_main_mapping_row_id := (
  SELECT `id`
  FROM `users_biolinks`
  WHERE `user_id` = @target_user_id
  ORDER BY `id` ASC
  LIMIT 1
);
SET @previous_main_link_id := (
  SELECT `biolink_id`
  FROM `users_biolinks`
  WHERE `user_id` = @target_user_id
  ORDER BY `id` ASC
  LIMIT 1
);

START TRANSACTION;

INSERT INTO `ops_support_users_preferences_backup`
(`backup_key`, `captured_at`, `note`, `user_id`, `preferences`)
SELECT
  @backup_key,
  @repair_now,
  @repair_note,
  `user_id`,
  `preferences`
FROM `users`
WHERE `user_id` = @target_user_id;

INSERT INTO `ops_support_users_biolinks_backup`
(`backup_key`, `captured_at`, `note`, `id`, `user_id`, `biolink_id`)
SELECT
  @backup_key,
  @repair_now,
  @repair_note,
  `id`,
  `user_id`,
  `biolink_id`
FROM `users_biolinks`
WHERE `user_id` = @target_user_id;

INSERT INTO `ops_support_links_backup`
(`backup_key`, `captured_at`, `note`, `link_id`, `user_id`, `url`, `additional`, `last_datetime`)
SELECT
  @backup_key,
  @repair_now,
  @repair_note,
  `link_id`,
  `user_id`,
  `url`,
  `additional`,
  `last_datetime`
FROM `links`
WHERE `user_id` = @target_user_id
  AND `link_id` IN (@previous_main_link_id, @target_main_link_id)
  AND `type` = 'biolink';

UPDATE `users_biolinks`
SET `biolink_id` = @target_main_link_id
WHERE `id` = @current_main_mapping_row_id
  AND `user_id` = @target_user_id;

UPDATE `users`
SET `preferences` = JSON_SET(
  COALESCE(NULLIF(`preferences`, ''), '{}'),
  '$.leader_ai_app_reviews', JSON_ARRAY(),
  '$.leader_ai_theme_library', JSON_ARRAY(),
  '$.leader_ai_app_review_job', JSON_OBJECT(
    'status', 'idle',
    'job_id', '',
    'started_at', NULL,
    'completed_at', NULL,
    'selected_link_id', 0,
    'error_message', ''
  ),
  '$.leader_ai_access.starter_app_review_used', 0
)
WHERE `user_id` = @target_user_id;

UPDATE `links`
SET
  `additional` = JSON_REMOVE(
    COALESCE(NULLIF(`additional`, ''), '{}'),
    '$.fcc_ai_theme_pack',
    '$.fcc_ai_primary_block_plan',
    '$.fcc_ai_block_patch_pack',
    '$.fcc_ai_copy_suggestions',
    '$.fcc_ai_layout_actions',
    '$.fcc_ai_missing_block_recommendations',
    '$.fcc_ai_ideal_block_order',
    '$.fcc_ai_core_block_policy',
    '$.fcc_ai_signal_protection_summary',
    '$.fcc_ai_evolution_memory',
    '$.fcc_ai_theme_library_key',
    '$.fcc_ai_review_summary',
    '$.fcc_ai_theme_apply_state'
  ),
  `last_datetime` = @repair_now
WHERE `user_id` = @target_user_id
  AND `link_id` IN (@previous_main_link_id, @target_main_link_id)
  AND `type` = 'biolink';

COMMIT;

SELECT
  @backup_key AS `backup_key`,
  @repair_now AS `repaired_at`,
  @previous_main_link_id AS `previous_main_link_id`,
  @target_main_link_id AS `new_main_link_id`,
  (
    SELECT `l`.`url`
    FROM `links` `l`
    WHERE `l`.`link_id` = @previous_main_link_id
      AND `l`.`user_id` = @target_user_id
    LIMIT 1
  ) AS `previous_main_slug`,
  (
    SELECT `l`.`url`
    FROM `links` `l`
    WHERE `l`.`link_id` = @target_main_link_id
      AND `l`.`user_id` = @target_user_id
    LIMIT 1
  ) AS `new_main_slug`,
  (
    SELECT `ub`.`biolink_id`
    FROM `users_biolinks` `ub`
    WHERE `ub`.`user_id` = @target_user_id
    ORDER BY `ub`.`id` ASC
    LIMIT 1
  ) AS `current_main_link_id_after_fix`,
  JSON_LENGTH(JSON_EXTRACT(`u`.`preferences`, '$.leader_ai_app_reviews')) AS `app_review_count_after_fix`,
  JSON_EXTRACT(`u`.`preferences`, '$.leader_ai_access.starter_app_review_used') AS `starter_app_review_used_after_fix`
FROM `users` `u`
WHERE `u`.`user_id` = @target_user_id;
