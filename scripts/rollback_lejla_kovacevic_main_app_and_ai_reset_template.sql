-- Roll back Lejla Kovacevic main-app repair and AI reset
-- 1. Replace PASTE_BACKUP_KEY_HERE with the backup_key returned by:
--    scripts/fix_lejla_kovacevic_main_app_and_ai_reset_2026_04_10.sql
-- 2. Run this on live.

SET @target_user_id := 855;
SET @backup_key := 'PASTE_BACKUP_KEY_HERE';
SET @rollback_now := NOW();

START TRANSACTION;

UPDATE `users` `u`
JOIN (
  SELECT `user_id`, `preferences`
  FROM `ops_support_users_preferences_backup`
  WHERE `backup_key` = @backup_key
    AND `user_id` = @target_user_id
  ORDER BY `snapshot_id` DESC
  LIMIT 1
) `b` ON `b`.`user_id` = `u`.`user_id`
SET
  `u`.`preferences` = `b`.`preferences`
WHERE `u`.`user_id` = @target_user_id;

DELETE FROM `users_biolinks`
WHERE `user_id` = @target_user_id;

INSERT INTO `users_biolinks` (`id`, `user_id`, `biolink_id`)
SELECT `id`, `user_id`, `biolink_id`
FROM `ops_support_users_biolinks_backup`
WHERE `backup_key` = @backup_key
  AND `user_id` = @target_user_id
ORDER BY `snapshot_id` ASC;

UPDATE `links` `l`
JOIN (
  SELECT
    `link_id`,
    `user_id`,
    `url`,
    `additional`,
    `last_datetime`
  FROM `ops_support_links_backup`
  WHERE `backup_key` = @backup_key
    AND `user_id` = @target_user_id
) `b` ON `b`.`link_id` = `l`.`link_id` AND `b`.`user_id` = `l`.`user_id`
SET
  `l`.`additional` = `b`.`additional`,
  `l`.`last_datetime` = @rollback_now
WHERE `l`.`user_id` = @target_user_id
  AND `l`.`type` = 'biolink';

COMMIT;

SELECT
  @backup_key AS `restored_backup_key`,
  @rollback_now AS `rolled_back_at`,
  (
    SELECT `ub`.`biolink_id`
    FROM `users_biolinks` `ub`
    WHERE `ub`.`user_id` = @target_user_id
    ORDER BY `ub`.`id` ASC
    LIMIT 1
  ) AS `current_main_link_id_after_rollback`;
