-- Lejla Kovacevic click diagnostics
-- Target user:
--   user_id = 855
--   email   = lejlaflp35@gmail.com
--
-- Purpose:
-- 1. Confirm current plan and main-app mapping.
-- 2. Confirm whether qualified FCC clicks still exist in raw track_links.
-- 3. Break qualified clicks down by app, day, and recent raw events.
--
-- Qualified click logic matches /fcc-results:
-- - unique clicks only
-- - block types:
--   link_forever_shop
--   link_forever_product
--   link_forever_living_bih
--   link_forever_living_alb_kosovo
--   link_forever_living_albania_kosovo
--   link_discount
-- - plus blog mediums:
--   blog_cta_product
--   blog_cta_business

SET @target_user_id := 855;
SET @qualified_7d_start := DATE_SUB(CURDATE(), INTERVAL 6 DAY);
SET @qualified_30d_start := DATE_SUB(CURDATE(), INTERVAL 29 DAY);
SET @qualified_60d_start := DATE_SUB(CURDATE(), INTERVAL 59 DAY);
SET @qualified_360d_start := DATE_SUB(CURDATE(), INTERVAL 359 DAY);

SELECT
  `u`.`user_id`,
  `u`.`name`,
  `u`.`email`,
  `u`.`plan_id`,
  `u`.`plan_expiration_date`,
  JSON_EXTRACT(`u`.`plan_settings`, '$.track_links_retention') AS `track_links_retention`,
  JSON_EXTRACT(`u`.`plan_settings`, '$.biolinks_limit') AS `biolinks_limit`,
  JSON_EXTRACT(`u`.`plan_settings`, '$.ai_growth_plan_is_enabled') AS `ai_growth_plan_is_enabled`,
  `u`.`next_cleanup_datetime`,
  `u`.`last_activity`
FROM `users` `u`
WHERE `u`.`user_id` = @target_user_id;

SELECT
  `ub`.`id` AS `mapping_row_id`,
  `ub`.`user_id`,
  `ub`.`biolink_id`,
  `l`.`url` AS `mapped_slug`,
  `l`.`is_enabled` AS `mapped_link_is_enabled`,
  CASE WHEN `ub`.`id` = (
    SELECT `ub2`.`id`
    FROM `users_biolinks` `ub2`
    WHERE `ub2`.`user_id` = @target_user_id
    ORDER BY `ub2`.`id` ASC
    LIMIT 1
  ) THEN 1 ELSE 0 END AS `is_current_main_mapping`
FROM `users_biolinks` `ub`
LEFT JOIN `links` `l`
  ON `l`.`link_id` = `ub`.`biolink_id`
 AND `l`.`user_id` = `ub`.`user_id`
WHERE `ub`.`user_id` = @target_user_id
ORDER BY `ub`.`id` ASC;

SELECT
  `l`.`link_id`,
  `l`.`url` AS `app_slug`,
  `l`.`is_enabled`,
  `l`.`datetime`,
  `l`.`last_datetime`,
  COUNT(`bb`.`biolink_block_id`) AS `total_blocks`
FROM `links` `l`
LEFT JOIN `biolinks_blocks` `bb`
  ON `bb`.`link_id` = `l`.`link_id`
WHERE `l`.`user_id` = @target_user_id
  AND `l`.`type` = 'biolink'
GROUP BY `l`.`link_id`, `l`.`url`, `l`.`is_enabled`, `l`.`datetime`, `l`.`last_datetime`
ORDER BY
  CASE WHEN `l`.`link_id` = (
    SELECT `ub3`.`biolink_id`
    FROM `users_biolinks` `ub3`
    WHERE `ub3`.`user_id` = @target_user_id
    ORDER BY `ub3`.`id` ASC
    LIMIT 1
  ) THEN 0 ELSE 1 END ASC,
  `l`.`datetime` ASC,
  `l`.`link_id` ASC;

SELECT
  SUM(CASE WHEN `tl`.`datetime` >= @qualified_7d_start AND `tl`.`is_unique` = 1 AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  ) THEN 1 ELSE 0 END) AS `qualified_clicks_7d`,
  SUM(CASE WHEN `tl`.`datetime` >= @qualified_30d_start AND `tl`.`is_unique` = 1 AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  ) THEN 1 ELSE 0 END) AS `qualified_clicks_30d`,
  SUM(CASE WHEN `tl`.`datetime` >= @qualified_60d_start AND `tl`.`is_unique` = 1 AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  ) THEN 1 ELSE 0 END) AS `qualified_clicks_60d`,
  SUM(CASE WHEN `tl`.`datetime` >= @qualified_360d_start AND `tl`.`is_unique` = 1 AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  ) THEN 1 ELSE 0 END) AS `qualified_clicks_360d`,
  MAX(CASE WHEN `tl`.`is_unique` = 1 AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  ) THEN `tl`.`datetime` ELSE NULL END) AS `last_qualified_click_at`
FROM `track_links` `tl`
LEFT JOIN `biolinks_blocks` `bb`
  ON `bb`.`biolink_block_id` = `tl`.`biolink_block_id`
WHERE `tl`.`user_id` = @target_user_id;

SELECT
  COALESCE(`page_link`.`url`, '[no_link]') AS `app_slug`,
  SUM(CASE WHEN `tl`.`datetime` >= @qualified_7d_start AND `tl`.`is_unique` = 1 AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  ) THEN 1 ELSE 0 END) AS `qualified_clicks_7d`,
  SUM(CASE WHEN `tl`.`datetime` >= @qualified_30d_start AND `tl`.`is_unique` = 1 AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  ) THEN 1 ELSE 0 END) AS `qualified_clicks_30d`,
  SUM(CASE WHEN `tl`.`datetime` >= @qualified_60d_start AND `tl`.`is_unique` = 1 AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  ) THEN 1 ELSE 0 END) AS `qualified_clicks_60d`,
  MAX(CASE WHEN `tl`.`is_unique` = 1 AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  ) THEN `tl`.`datetime` ELSE NULL END) AS `last_qualified_click_at`
FROM `track_links` `tl`
LEFT JOIN `biolinks_blocks` `bb`
  ON `bb`.`biolink_block_id` = `tl`.`biolink_block_id`
LEFT JOIN `links` `page_link`
  ON `page_link`.`link_id` = `tl`.`link_id`
WHERE `tl`.`user_id` = @target_user_id
GROUP BY COALESCE(`page_link`.`url`, '[no_link]')
ORDER BY `qualified_clicks_60d` DESC, `qualified_clicks_30d` DESC, `app_slug` ASC;

SELECT
  DATE(`tl`.`datetime`) AS `date_key`,
  COUNT(*) AS `qualified_clicks`
FROM `track_links` `tl`
LEFT JOIN `biolinks_blocks` `bb`
  ON `bb`.`biolink_block_id` = `tl`.`biolink_block_id`
WHERE `tl`.`user_id` = @target_user_id
  AND `tl`.`datetime` >= @qualified_60d_start
  AND `tl`.`is_unique` = 1
  AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  )
GROUP BY DATE(`tl`.`datetime`)
ORDER BY `date_key` DESC;

SELECT
  `tl`.`datetime`,
  `tl`.`link_id`,
  COALESCE(`page_link`.`url`, '[no_link]') AS `app_slug`,
  `tl`.`biolink_block_id`,
  COALESCE(`bb`.`type`, '[no_block]') AS `block_type`,
  `tl`.`utm_medium`,
  `tl`.`utm_source`,
  `tl`.`referrer_host`,
  `tl`.`country_code`,
  `tl`.`is_unique`
FROM `track_links` `tl`
LEFT JOIN `biolinks_blocks` `bb`
  ON `bb`.`biolink_block_id` = `tl`.`biolink_block_id`
LEFT JOIN `links` `page_link`
  ON `page_link`.`link_id` = `tl`.`link_id`
WHERE `tl`.`user_id` = @target_user_id
  AND `tl`.`is_unique` = 1
  AND (
    `bb`.`type` IN (
      'link_forever_shop',
      'link_forever_product',
      'link_forever_living_bih',
      'link_forever_living_alb_kosovo',
      'link_forever_living_albania_kosovo',
      'link_discount'
    )
    OR `tl`.`utm_medium` IN ('blog_cta_product', 'blog_cta_business')
  )
ORDER BY `tl`.`datetime` DESC
LIMIT 120;
