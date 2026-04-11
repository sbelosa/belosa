START TRANSACTION;

UPDATE `fcc_ai_message_feedback`
SET
    `status` = 'resolved',
    `reviewed_at` = NOW(),
    `last_datetime` = NOW()
WHERE `fcc_ai_message_feedback_id` IN (2, 3, 4, 5, 7, 8)
  AND `feedback_type` = 'down';

UPDATE `fcc_ai_conversation_insights` AS `ci`
LEFT JOIN (
    SELECT
        `fcc_ai_conversation_id`,
        SUM(CASE WHEN `feedback_type` = 'up' THEN 1 ELSE 0 END) AS `positive_feedback_total`,
        SUM(CASE WHEN `feedback_type` = 'down' AND COALESCE(`status`, 'new') != 'resolved' THEN 1 ELSE 0 END) AS `negative_feedback_total`
    FROM `fcc_ai_message_feedback`
    GROUP BY `fcc_ai_conversation_id`
) AS `fb`
    ON `fb`.`fcc_ai_conversation_id` = `ci`.`fcc_ai_conversation_id`
SET
    `ci`.`positive_feedback_total` = COALESCE(`fb`.`positive_feedback_total`, 0),
    `ci`.`negative_feedback_total` = COALESCE(`fb`.`negative_feedback_total`, 0),
    `ci`.`needs_review` = CASE
        WHEN COALESCE(`fb`.`negative_feedback_total`, 0) > 0 THEN 1
        ELSE 0
    END,
    `ci`.`quality_signal` = CASE
        WHEN COALESCE(`fb`.`negative_feedback_total`, 0) > 0 THEN 'needs_review'
        WHEN COALESCE(`fb`.`positive_feedback_total`, 0) > 0 THEN 'positive'
        WHEN COALESCE(`ci`.`intent`, '') != '' THEN `ci`.`intent`
        ELSE 'active'
    END,
    `ci`.`last_datetime` = NOW()
WHERE `ci`.`fcc_ai_conversation_id` IN (
    SELECT DISTINCT `fcc_ai_conversation_id`
    FROM `fcc_ai_message_feedback`
    WHERE `fcc_ai_message_feedback_id` IN (2, 3, 4, 5, 7, 8)
);

COMMIT;
