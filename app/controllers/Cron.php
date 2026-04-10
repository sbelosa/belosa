<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

namespace Altum\Controllers;

use Altum\Logger;
use Altum\Models\Billing;
use Altum\Models\User;

defined('ALTUMCODE') || die();

class Cron extends Controller {
    public $processing_time = null;

    private function initiate() {
        /* Benchmark */
        $this->processing_time = microtime(true);

        /* Make sure no cache is being used on the endpoint */
        header('Cache-Control: no-store');

        /* Initiation */
        set_time_limit(0);

        /* Make sure the key is correct */
        if(!isset($_GET['key']) || (isset($_GET['key']) && $_GET['key'] != settings()->cron->key)) {
            die();
        }

        /* Send webhook notification if needed */
        if(settings()->webhooks->cron_start) {
            $backtrace = debug_backtrace();
            fire_and_forget('post', settings()->webhooks->cron_start, [
                'type' => $backtrace[1]['function'] ?? null,
                'datetime' => get_date(),
            ], signature: true);
        }
    }

    private function close() {
        /* Send webhook notification if needed */
        if(settings()->webhooks->cron_end) {
            $backtrace = debug_backtrace();
            fire_and_forget('post', settings()->webhooks->cron_end, [
                'type' => $backtrace[1]['function'] ?? null,
                'datetime' => get_date(),
            ], signature: true);
        }
    }

    private function update_cron_execution_datetimes($key) {
        $date = get_date();
        $processing_time = (microtime(true) - $this->processing_time);

        /* Database query */
        database()->query("UPDATE `settings` SET `value` = JSON_SET(`value`, '$.{$key}', '{$date}', '$.{$key}_processing', {$processing_time}) WHERE `key` = 'cron'");

        /* Keep cached settings aligned with the latest cron heartbeat and ops diagnostics. */
        cache()->deleteItem('settings');
    }

    public function index() {

        $this->initiate();

        $this->users_plan_expiry_checker();

        $this->users_deletion_reminder();

        $this->auto_delete_inactive_users();

        $this->auto_delete_unconfirmed_users();

        $this->users_plan_expiry_reminder();

        /* Custom code: FC-2026-03-18: live email automations sync and send */
        $this->email_automations_sync();
        $this->email_automations_send();
        /* /Custom code: FC-2026-03-18 */

        /* Custom code: FC-2026-03-17: billing risk grace period escalation and revoke */
        $this->billing_risk_monitor();
        /* /Custom code: FC-2026-03-17 */

        /* Custom code: FC-2026-03-31: Phase 6 cleanup for privacy-safe funnel fraud traces */
        $this->leader_operating_system_fraud_cleanup();
        /* /Custom code: FC-2026-03-31 */

        $this->statistics_cleanup();

        /* Make sure the reset date month is different than the current one to avoid double resetting */
        $reset_date = settings()->cron->reset_date ? (new \DateTime(settings()->cron->reset_date))->format('m') : null;
        $current_date = (new \DateTime())->format('m');

        if($reset_date != $current_date) {
            /* Benchmark */
            $this->processing_time = microtime(true);

            $this->logs_cleanup();

            $this->users_logs_cleanup();

            $this->internal_notifications_cleanup();

            $this->users_aix_reset();

            $this->guests_payments_cleanup();

            /* Clear the cache */
            cache()->deleteItem('settings');

            $this->update_cron_execution_datetimes('reset_date');
        }

        $this->close();

        $this->update_cron_execution_datetimes('cron_datetime');
    }

    /* Custom code: FC-2026-03-17: monitor Stripe failed-payment grace periods */
    private function billing_risk_monitor() {
        if(!in_array(settings()->license->type, ['Extended License', 'extended'])) {
            return;
        }

        if(!settings()->payment->is_enabled || empty(settings()->stripe->is_enabled)) {
            return;
        }

        $result = (new Billing())->process_grace_periods();

        if(DEBUG && (($result['escalated'] ?? 0) > 0 || ($result['revoked'] ?? 0) > 0)) {
            echo sprintf('billing_risk_monitor() -> escalated %s, revoked %s', $result['escalated'] ?? 0, $result['revoked'] ?? 0);
        }
    }
    /* /Custom code: FC-2026-03-17 */

    /* Custom code: FC-2026-03-31: purge short-lived LOS fraud traces automatically */
    private function leader_operating_system_fraud_cleanup() {
        if(!function_exists('fc_cleanup_funnel_analytics_data')) {
            return;
        }

        fc_cleanup_funnel_analytics_data();
        if(function_exists('fc_cleanup_forever_click_integrity_data')) {
            fc_cleanup_forever_click_integrity_data();
        }

        if(DEBUG) {
            echo sprintf(
                'leader_operating_system_fraud_cleanup() -> pruned funnel traces older than %s days, fraud summaries older than %s days, and Forever click integrity traces older than %s days',
                fc_get_los_fraud_event_retention_days(),
                fc_get_los_fraud_summary_retention_days(),
                function_exists('fc_get_forever_click_integrity_retention_days') ? fc_get_forever_click_integrity_retention_days() : 30
            );
        }
    }
    /* /Custom code: FC-2026-03-31 */

    private function users_plan_expiry_checker() {
        if(!settings()->payment->user_plan_expiry_checker_is_enabled) {
            return;
        }

        $date = get_date();

        $result = database()->query("
            SELECT 
                `user_id`,
                `plan_id`,
                `payment_processor`,
                `name`,
                `email`,
                `language`,
                `anti_phishing_code`
            FROM 
                `users`
            WHERE 
                `plan_id` <> 'free'
				/* Custom code: FC-2026-03-24: prevent false expiry downgrades for recurring subscriptions */
				AND (`payment_subscription_id` IS NULL OR `payment_subscription_id` = '')
				/* /Custom code: FC-2026-03-24 */
				AND `plan_expiration_date` < '{$date}' 
            LIMIT 25
        ");

        $plans = [];
        if($result->num_rows) {
            $plans = (new \Altum\Models\Plan())->get_plans();
        }

        /* Custom code: FC-2026-03-09: fallback all expired users to Beginner plan */
        $beginner_plan = (new \Altum\Models\Plan())->get_plan_by_id(2);
        /* /Custom code: FC-2026-03-09 */

        /* Go through each result */
        while($user = $result->fetch_object()) {

            /* Custom code: FC-2026-03-09: fallback all expired users to Beginner plan */
            $fallback_plan_id = $beginner_plan ? 2 : 'free';
            $fallback_plan_settings = $beginner_plan
                ? json_encode($beginner_plan->settings)
                : json_encode(settings()->plan_free->settings);
            /* Custom code: FC-2026-03-11: prevent repeated expiry emails after Beginner fallback */
            $fallback_plan_expiration_date = $beginner_plan
                ? (new \DateTime())->modify('+10 years')->format('Y-m-d H:i:s')
                : get_date();
            /* /Custom code: FC-2026-03-11 */
            /* /Custom code: FC-2026-03-09 */

            /* Switch the user to the default plan */
            db()->where('user_id', $user->user_id)->update('users', [
                'plan_id' => $fallback_plan_id,
                'plan_settings' => $fallback_plan_settings,
                'plan_expiration_date' => $fallback_plan_expiration_date,
                'payment_subscription_id' => '',
                'payment_processor' => '',
                'payment_total_amount' => 0,
                'payment_currency' => '',
            ]);

            /* Custom code: FC-2026-03-19: enforce link limits immediately after expiry downgrade */
            (new \Altum\Models\User())->sync_links_with_plan($user->user_id);
            /* /Custom code: FC-2026-03-19 */

            /* Prepare the email */
            $email_template = get_email_template(
                [],
                l('global.emails.user_plan_expired.subject', $user->language),
                [
                    '{{USER_PLAN_RENEW_LINK}}' => url('pay/' . $user->plan_id),
                    '{{NAME}}' => $user->name,
                    '{{PLAN_NAME}}' => $plans[$user->plan_id]->name,
                ],
                l('global.emails.user_plan_expired.body', $user->language)
            );

            send_mail($user->email, $email_template->subject, $email_template->body, ['anti_phishing_code' => $user->anti_phishing_code, 'language' => $user->language]);

            /* Clear the cache */
            cache()->deleteItemsByTag('user_id=' .  $user->user_id);

            if(DEBUG) {
                echo sprintf('users_plan_expiry_checker() -> Plan expired for user_id %s - reverting account to %s plan', $user->user_id, $fallback_plan_id);
            }
        }
    }

    private function users_deletion_reminder() {
        if(!settings()->users->auto_delete_inactive_users) {
            return;
        }

        /* Determine when to send the email reminder */
        $days_until_deletion = settings()->users->user_deletion_reminder;
        $days = settings()->users->auto_delete_inactive_users - $days_until_deletion;
        $past_date = (new \DateTime())->modify('-' . $days . ' days')->format('Y-m-d H:i:s');

        /* Get the users that need to be reminded */
        $result = database()->query("
            SELECT `user_id`, `name`, `email`, `language`, `anti_phishing_code` 
            FROM `users` 
            WHERE 
                `plan_id` = 'free' 
                AND `last_activity` < '{$past_date}' 
                AND `user_deletion_reminder` = 0 
                AND `type` = 0 
            LIMIT 25
        ");

        /* Go through each result */
        while($user = $result->fetch_object()) {

            /* Prepare the email */
            $email_template = get_email_template(
                [
                    '{{DAYS_UNTIL_DELETION}}' => $days_until_deletion,
                ],
                l('global.emails.user_deletion_reminder.subject', $user->language),
                [
                    '{{DAYS_UNTIL_DELETION}}' => $days_until_deletion,
                    '{{LOGIN_LINK}}' => url('login'),
                    '{{NAME}}' => $user->name,
                ],
                l('global.emails.user_deletion_reminder.body', $user->language)
            );

            if(settings()->users->user_deletion_reminder) {
                send_mail($user->email, $email_template->subject, $email_template->body, ['anti_phishing_code' => $user->anti_phishing_code, 'language' => $user->language]);
            }

            /* Update user */
            db()->where('user_id', $user->user_id)->update('users', ['user_deletion_reminder' => 1]);

            if(DEBUG) {
                if(settings()->users->user_deletion_reminder) echo sprintf('users_deletion_reminder() -> User deletion reminder email sent for user_id %s', $user->user_id);
            }
        }

    }

    private function auto_delete_inactive_users() {
        if(!settings()->users->auto_delete_inactive_users) {
            return;
        }

        /* Determine what users to delete */
        $days = settings()->users->auto_delete_inactive_users;
        $past_date = (new \DateTime())->modify('-' . $days . ' days')->format('Y-m-d H:i:s');

        /* Get the users that need to be reminded */
        $result = database()->query("
            SELECT `user_id`, `name`, `email`, `language`, `anti_phishing_code` FROM `users` WHERE `plan_id` = 'free' AND `last_activity` < '{$past_date}' AND `user_deletion_reminder` = 1 AND `type` = 0 LIMIT 25
        ");

        /* Go through each result */
        while($user = $result->fetch_object()) {

            /* Prepare the email */
            $email_template = get_email_template(
                [],
                l('global.emails.auto_delete_inactive_users.subject', $user->language),
                [
                    '{{INACTIVITY_DAYS}}' => settings()->users->auto_delete_inactive_users,
                    '{{REGISTER_LINK}}' => url('register'),
                    '{{NAME}}' => $user->name,
                ],
                l('global.emails.auto_delete_inactive_users.body', $user->language)
            );

            send_mail($user->email, $email_template->subject, $email_template->body, ['anti_phishing_code' => $user->anti_phishing_code, 'language' => $user->language]);

            /* Delete user */
            (new User())->delete($user->user_id);

            if(DEBUG) {
                echo sprintf('User deletion for inactivity user_id %s', $user->user_id);
            }
        }

    }

    private function auto_delete_unconfirmed_users() {
        if(!settings()->users->auto_delete_unconfirmed_users) {
            return;
        }

        /* Determine what users to delete */
        $days = settings()->users->auto_delete_unconfirmed_users;
        $past_date = (new \DateTime())->modify('-' . $days . ' days')->format('Y-m-d H:i:s');

        /* Get the users that need to be reminded */
        $result = database()->query("SELECT `user_id` FROM `users` WHERE `status` = '0' AND `datetime` < '{$past_date}' LIMIT 100");

        /* Go through each result */
        while($user = $result->fetch_object()) {

            /* Delete user */
            (new User())->delete($user->user_id);

            if(DEBUG) {
                echo sprintf('User deleted for unconfirmed account user_id %s', $user->user_id);
            }
        }
    }

    private function logs_cleanup() {
        /* Clear files caches */
        clearstatcache();

        $current_month = (new \DateTime())->format('m');

        $deleted_count = 0;

        /* Get the data */
        foreach(glob(UPLOADS_PATH . 'logs/' . '*.log') as $file_path) {
            $file_last_modified = filemtime($file_path);

            if((new \DateTime())->setTimestamp($file_last_modified)->format('m') != $current_month) {
                unlink($file_path);
                $deleted_count++;
            }
        }

        if(DEBUG) {
            echo sprintf('logs_cleanup: Deleted %s file logs.', $deleted_count);
        }
    }

    private function users_logs_cleanup() {
        /* Delete old users logs */
        $ninety_days_ago_datetime = (new \DateTime())->modify('-90 days')->format('Y-m-d H:i:s');
        db()->where('datetime', $ninety_days_ago_datetime, '<')->delete('users_logs');
    }

    private function internal_notifications_cleanup() {
        /* Delete old users notifications */
        $days_ago_datetime = (new \DateTime())->modify('-30 days')->format('Y-m-d H:i:s');
        db()->where('datetime', $days_ago_datetime, '<')->delete('internal_notifications');
    }

    private function statistics_cleanup() {
        $protected_fcc_qualified_click_retention_days = 90;
        $qualified_click_condition_sql = \Altum\Link::get_fcc_results_qualified_click_condition_sql('`track_links`', '`biolinks_blocks`');
        $protected_qualified_click_condition_sql = "(`track_links`.`is_unique` = 1 AND {$qualified_click_condition_sql})";

        /* Only clean users that have not been cleaned recently */
        $now_datetime = get_date();

        /* Clean the track notifications table based on the users plan */
        $result = database()->query("SELECT `user_id`, `plan_settings` FROM `users` WHERE `status` = 1 AND `next_cleanup_datetime` < '{$now_datetime}'");

        /* Go through each result */
        while($user = $result->fetch_object()) {
            /* Update user cleanup date */
            db()->where('user_id', $user->user_id)->update('users', ['next_cleanup_datetime' => (new \DateTime())->modify('+1 days')->format('Y-m-d H:i:s')]);

            $user->plan_settings = json_decode($user->plan_settings);

            /* Skip if retention is infinite */
            if($user->plan_settings->track_links_retention == -1) continue;

            /* Clear out old notification statistics logs */
            $x_days_ago_datetime = (new \DateTime())->modify('-' . ($user->plan_settings->track_links_retention ?? 90) . ' days')->format('Y-m-d H:i:s');
            $protected_qualified_clicks_cutoff_datetime = (new \DateTime())->modify('-' . $protected_fcc_qualified_click_retention_days . ' days')->format('Y-m-d H:i:s');
            database()->query("DELETE `track_links`
                FROM `track_links`
                LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
                WHERE `track_links`.`user_id` = {$user->user_id}
                  AND `track_links`.`datetime` < '{$x_days_ago_datetime}'
                  AND (
                    `track_links`.`datetime` < '{$protected_qualified_clicks_cutoff_datetime}'
                    OR NOT {$protected_qualified_click_condition_sql}
                  )");

            if(DEBUG) {
                echo sprintf('statistics_cleanup() -> Statistics cleanup done for user_id %s', $user->user_id);
            }
        }

    }

    public function email_reports() {

        $this->initiate();

        /* Only run this part if the email reports are enabled */
        if(!settings()->links->email_reports_is_enabled) {
            $this->close();
            $this->update_cron_execution_datetimes('email_reports_datetime');
            return;
        }

        $date = get_date();

        /* Determine the frequency of email reports */
        $days_interval = 7;

        switch(settings()->links->email_reports_is_enabled) {
            case 'weekly':
                $days_interval = 7;
                break;

            case 'monthly':
                $days_interval = 30;
                break;
        }

        /* Cache notification handlers */
        $cached_notification_handlers = [];

        /* Get potential links from users that have almost all the conditions to get an email report right now */
        $result = database()->query("
            SELECT
                `links`.`link_id`,
                `links`.`url`,
                `links`.`email_reports_last_datetime`,
                `links`.`email_reports`,
                `users`.`user_id`,
                `users`.`email`,
                `users`.`plan_settings`,
                `users`.`language`,
                `users`.`anti_phishing_code`
            FROM 
                `links`
            LEFT JOIN 
                `users` ON `links`.`user_id` = `users`.`user_id` 
            WHERE 
                `users`.`status` = 1
                AND `links`.`is_enabled` = 1 
                AND `links`.`email_reports_count` > 0
				AND DATE_ADD(`links`.`email_reports_last_datetime`, INTERVAL {$days_interval} DAY) <= '{$date}'
            LIMIT 25
        ");

        /* Go through each result */
        while($row = $result->fetch_object()) {
            $row->plan_settings = json_decode($row->plan_settings);
            $row->email_reports = json_decode($row->email_reports);

            /* Make sure the plan still lets the user get email reports */
            if(!$row->plan_settings->email_reports_is_enabled) {
                db()->where('link_id', $row->link_id)->update('links', ['email_reports' => '[]']);
                continue;
            }

            /* Prepare */
            $previous_start_date = (new \DateTime())->modify('-' . $days_interval * 2 . ' days')->format('Y-m-d H:i:s');
            $start_date = (new \DateTime())->modify('-' . $days_interval . ' days')->format('Y-m-d H:i:s');

            /* Get required stats */
            $statistics_result = database()->query("
                SELECT
                     COUNT(`id`) AS `pageviews`,
                     SUM(`is_unique`) AS `visitors`
                FROM
                     `track_links`
                WHERE
                    `link_id` = {$row->link_id} 
                    AND (`datetime` BETWEEN '{$start_date}' AND '{$date}')
            ")->fetch_object();

            $statistics = [
                'pageviews' => $statistics_result->pageviews ?? 0,
                'visitors' => $statistics_result->visitors ?? 0,
            ];

            /* Get previous required stats */
            $previous_statistics_result = database()->query("
                SELECT
                     COUNT(`id`) AS `pageviews`,
                     SUM(`is_unique`) AS `visitors`
                FROM
                     `track_links`
                WHERE
                    `link_id` = {$row->link_id} 
                    AND (`datetime` BETWEEN '{$previous_start_date}' AND '{$start_date}')
            ")->fetch_object();

            $previous_statistics = [
                'pageviews' => $previous_statistics_result->pageviews ?? 0,
                'visitors' => $previous_statistics_result->visitors ?? 0,
            ];

            /* Get available notification handlers */
            if(isset($cached_notification_handlers[$row->user_id])) {
                $notification_handlers = $cached_notification_handlers[$row->user_id];
            } else {
                $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($row->user_id);
                $cached_notification_handlers[$row->user_id] = $notification_handlers;
            }

            /* Processing the notification handlers */
            foreach($notification_handlers as $notification_handler) {
                if(!$notification_handler->is_enabled) continue;
                if(!in_array($notification_handler->notification_handler_id, $row->email_reports)) continue;
                if($notification_handler->type != 'email') continue;

                /* Prepare the email title */
                $replacers = [
                    '{{LINK:URL}}' => $row->url,
                    '{{START_DATE}}' => \Altum\Date::get($start_date, 5),
                    '{{END_DATE}}' => \Altum\Date::get('', 5),
                ];

                $email_title = str_replace(
                    array_keys($replacers),
                    array_values($replacers),
                    l('cron.email_reports.title', $row->language)
                );

                /* Prepare the View for the email content */
                $data = [
                    'row'                       => $row,
                    'statistics'                => $statistics,
                    'previous_statistics'       => $previous_statistics,
                    'previous_start_date'       => $previous_start_date,
                    'start_date'                => $start_date,
                    'date'                      => $date,
                ];

                $email_content = (new \Altum\View('partials/cron/email_reports', (array) $this))->run($data);

                /* Send the email */
                send_mail($notification_handler->settings->email, $email_title, $email_content, ['anti_phishing_code' => $row->anti_phishing_code, 'language' => $row->language]);
            }

            /* Update the website */
            db()->where('link_id', $row->link_id)->update('links', ['email_reports_last_datetime' => $date]);

            /* Insert email log */
            db()->insert('email_reports', [
                'user_id' => $row->user_id,
                'link_id' => $row->link_id,
                'datetime' => $date,
            ]);

            if(DEBUG) {
                echo sprintf('Email sent for user_id %s and link_id %s', $row->user_id, $row->link_id);
            }
        }

        $this->close();

        $this->update_cron_execution_datetimes('email_reports_datetime');
    }

    /* Custom code: FC-2026-03-18: live email automations sync */
    private function email_automations_sync() {
        fc_ensure_email_automation_tables();
        fc_seed_default_email_automation();

        $automations = db()->where('status', 'active')->get('email_automations') ?? [];

        foreach($automations as $automation) {
            $settings = fc_get_email_automation_settings($automation->settings ?? null);
            $eligible_users = fc_get_automation_segment_users($automation->segment, $settings);
            $eligible_user_ids = array_map('intval', array_keys($eligible_users));

            $enrollments = db()->where('automation_id', $automation->automation_id)->get('email_automation_enrollments') ?? [];
            $enrollments_by_user_id = [];

            foreach($enrollments as $enrollment) {
                $enrollments_by_user_id[(int) $enrollment->user_id] = $enrollment;
            }

            foreach($eligible_users as $user_id => $user) {
                if(!isset($enrollments_by_user_id[$user_id])) {
                    $enrollment_id = db()->insert('email_automation_enrollments', [
                        'automation_id' => $automation->automation_id,
                        'user_id' => $user_id,
                        'status' => 'active',
                        'current_step' => 1,
                        'entered_datetime' => get_date(),
                        'next_action_datetime' => get_date(),
                        'last_evaluated_datetime' => get_date(),
                    ]);

                    fc_insert_email_automation_log((int) $automation->automation_id, (int) $enrollment_id, null, (int) $user_id, 'entered', ['message' => 'User entered the live automation segment.']);
                    continue;
                }

                $existing_enrollment = $enrollments_by_user_id[$user_id];

                if($settings->reentry_is_enabled && in_array($existing_enrollment->status, ['completed', 'exited'])) {
                    db()->where('automation_enrollment_id', $existing_enrollment->automation_enrollment_id)->update('email_automation_enrollments', [
                        'status' => 'active',
                        'current_step' => 1,
                        'entered_datetime' => get_date(),
                        'next_action_datetime' => get_date(),
                        'last_evaluated_datetime' => get_date(),
                        'completed_datetime' => null,
                        'exit_datetime' => null,
                        'exit_reason' => null,
                        'last_datetime' => get_date(),
                    ]);

                    fc_insert_email_automation_log((int) $automation->automation_id, (int) $existing_enrollment->automation_enrollment_id, null, (int) $user_id, 'reentered', ['message' => 'User re-entered the live automation segment.']);
                } elseif($existing_enrollment->status == 'active') {
                    db()->where('automation_enrollment_id', $existing_enrollment->automation_enrollment_id)->update('email_automation_enrollments', [
                        'last_evaluated_datetime' => get_date(),
                        'last_datetime' => get_date(),
                    ]);
                }
            }

            foreach($enrollments as $enrollment) {
                if($enrollment->status !== 'active') {
                    continue;
                }

                if(in_array((int) $enrollment->user_id, $eligible_user_ids, true)) {
                    continue;
                }

                db()->where('automation_enrollment_id', $enrollment->automation_enrollment_id)->update('email_automation_enrollments', [
                    'status' => 'exited',
                    'next_action_datetime' => null,
                    'last_evaluated_datetime' => get_date(),
                    'exit_datetime' => get_date(),
                    'exit_reason' => 'condition_resolved',
                    'last_datetime' => get_date(),
                ]);

                /* Custom code: FC-2026-03-19: mark goal completion when the user resolves the segment condition */
                fc_mark_email_automation_goal_completed((int) $automation->automation_id, (int) $enrollment->automation_enrollment_id, (int) $enrollment->user_id);
                /* /Custom code: FC-2026-03-19 */

                fc_insert_email_automation_log((int) $automation->automation_id, (int) $enrollment->automation_enrollment_id, null, (int) $enrollment->user_id, 'exited', ['message' => 'User exited because the segment condition is no longer true.', 'exit_reason' => 'condition_resolved']);
            }
        }
    }
    /* /Custom code: FC-2026-03-18 */

    /* Custom code: FC-2026-03-18: live email automations sender */
    private function email_automations_send() {
        fc_ensure_email_automation_tables();

        $automations = db()->where('status', 'active')->get('email_automations') ?? [];

        foreach($automations as $automation) {
            $settings = fc_get_email_automation_settings($automation->settings ?? null);
            $steps = fc_get_email_automation_steps((int) $automation->automation_id);

            if(empty($steps)) {
                continue;
            }

            $steps_map = [];
            foreach($steps as $step) {
                $steps_map[(int) $step->step_order] = $step;
            }

            $due_enrollments = db()
                ->where('automation_id', $automation->automation_id)
                ->where('status', 'active')
                ->where('next_action_datetime', get_date(), '<=')
                ->orderBy('next_action_datetime', 'ASC')
                ->get('email_automation_enrollments', $settings->batch_size) ?? [];

            if(empty($due_enrollments)) {
                continue;
            }

            $user_ids = array_map(static function($enrollment) {
                return (int) $enrollment->user_id;
            }, $due_enrollments);

            $users = db()->where('user_id', $user_ids, 'IN')->get('users', null, ['user_id', 'name', 'email', 'language', 'anti_phishing_code', 'status']) ?? [];
            $users_map = [];
            foreach($users as $user) {
                $users_map[(int) $user->user_id] = $user;
            }

            foreach($due_enrollments as $enrollment) {
                $user = $users_map[(int) $enrollment->user_id] ?? null;

                if(!$user || !fc_is_user_in_automation_segment($automation->segment, (int) $enrollment->user_id, $settings)) {
                    db()->where('automation_enrollment_id', $enrollment->automation_enrollment_id)->update('email_automation_enrollments', [
                        'status' => 'exited',
                        'next_action_datetime' => null,
                        'exit_datetime' => get_date(),
                        'exit_reason' => 'condition_resolved',
                        'last_datetime' => get_date(),
                    ]);

                    /* Custom code: FC-2026-03-19: mark goal completion when the user no longer needs automation */
                    fc_mark_email_automation_goal_completed((int) $automation->automation_id, (int) $enrollment->automation_enrollment_id, (int) $enrollment->user_id);
                    /* /Custom code: FC-2026-03-19 */

                    fc_insert_email_automation_log((int) $automation->automation_id, (int) $enrollment->automation_enrollment_id, null, (int) $enrollment->user_id, 'exited', ['message' => 'Enrollment skipped because the user is no longer eligible.', 'exit_reason' => 'condition_resolved']);
                    continue;
                }

                $current_step = $steps_map[(int) $enrollment->current_step] ?? null;

                if(!$current_step) {
                    db()->where('automation_enrollment_id', $enrollment->automation_enrollment_id)->update('email_automation_enrollments', [
                        'status' => 'completed',
                        'next_action_datetime' => null,
                        'completed_datetime' => get_date(),
                        'last_datetime' => get_date(),
                    ]);

                    fc_insert_email_automation_log((int) $automation->automation_id, (int) $enrollment->automation_enrollment_id, null, (int) $enrollment->user_id, 'completed', ['message' => 'Enrollment completed because there are no more steps.']);
                    continue;
                }

                $vars = fc_get_email_automation_user_variables($user, $settings);
                $email_template = get_email_template(
                    $vars,
                    htmlspecialchars_decode($current_step->subject),
                    $vars,
                    fc_append_email_automation_footer($current_step->content)
                );

                /* Custom code: FC-2026-03-19: attach Brevo automation tags and capture transport result */
                $automation_tags = fc_get_email_automation_message_tags((int) $automation->automation_id, (int) $enrollment->automation_enrollment_id, (int) $current_step->automation_step_id, (int) $user->user_id);
                /* /Custom code: FC-2026-03-19 */

                /* Custom code: FC-2026-03-18: avoid recipient reply-to on automation emails */
                /* Custom code: FC-2026-03-19: embed signed unsubscribe link into automation sends */
                $automation_unsubscribe_url = fc_get_email_unsubscribe_url([
                    'message_type' => 'automation',
                    'automation_id' => (int) $automation->automation_id,
                    'automation_enrollment_id' => (int) $enrollment->automation_enrollment_id,
                    'automation_step_id' => (int) $current_step->automation_step_id,
                    'user_id' => (int) $user->user_id,
                    'recipient_email' => $user->email,
                ]);
                $send_result = send_automation_mail($user->email, $email_template->subject, $email_template->body, ['is_system_email' => false, 'anti_phishing_code' => $user->anti_phishing_code, 'language' => $user->language, 'brevo_tags' => $automation_tags, 'unsubscribe_url' => $automation_unsubscribe_url, 'return_transport_result' => true]);
                /* /Custom code: FC-2026-03-19 */
                /* /Custom code: FC-2026-03-18 */

                $is_sent = is_object($send_result) ? (bool) ($send_result->success ?? false) : (bool) $send_result;

                if($is_sent) {
                    $next_step = fc_get_next_email_automation_step($steps, (int) $current_step->step_order);
                    $enrollment_update = [
                        'last_sent_email_datetime' => get_date(),
                        'last_datetime' => get_date(),
                    ];

                    if($next_step) {
                        $enrollment_update['current_step'] = (int) $next_step->step_order;
                        $enrollment_update['next_action_datetime'] = (new \DateTime())->modify('+' . (int) $next_step->delay_minutes . ' minutes')->format('Y-m-d H:i:s');
                    } else {
                        $enrollment_update['status'] = 'completed';
                        $enrollment_update['next_action_datetime'] = null;
                        $enrollment_update['completed_datetime'] = get_date();
                    }

                    db()->where('automation_enrollment_id', $enrollment->automation_enrollment_id)->update('email_automation_enrollments', $enrollment_update);
                    database()->query("UPDATE `email_automations` SET `total_sent_emails` = `total_sent_emails` + 1, `last_sent_email_datetime` = '" . get_date() . "', `last_datetime` = '" . get_date() . "' WHERE `automation_id` = {$automation->automation_id}");

                    /* Custom code: FC-2026-03-19: persist sent messages for webhook analytics */
                    fc_store_email_automation_message((int) $automation->automation_id, (int) $enrollment->automation_enrollment_id, (int) $current_step->automation_step_id, (int) $user->user_id, $user->email, $email_template->subject, $automation_tags, $send_result);
                    /* /Custom code: FC-2026-03-19 */

                    fc_insert_email_automation_log((int) $automation->automation_id, (int) $enrollment->automation_enrollment_id, (int) $current_step->automation_step_id, (int) $user->user_id, 'email_sent', ['message' => 'Automation step email sent successfully.']);

                    if(DEBUG) {
                        echo sprintf('email_automations_send() -> automation_id %s sent step %s to user_id %s', $automation->automation_id, $current_step->step_order, $user->user_id);
                    }
                } else {
                    db()->where('automation_enrollment_id', $enrollment->automation_enrollment_id)->update('email_automation_enrollments', [
                        'next_action_datetime' => (new \DateTime())->modify('+60 minutes')->format('Y-m-d H:i:s'),
                        'last_datetime' => get_date(),
                    ]);

                    fc_insert_email_automation_log((int) $automation->automation_id, (int) $enrollment->automation_enrollment_id, (int) $current_step->automation_step_id, (int) $user->user_id, 'send_failed', ['message' => 'Automation step email failed and will retry in 60 minutes.']);
                }
            }
        }
    }
    /* /Custom code: FC-2026-03-18 */

    private function users_plan_expiry_reminder() {
        if(!settings()->payment->user_plan_expiry_reminder) {
            return;
        }

        /* Determine when to send the email reminder */
        $days = settings()->payment->user_plan_expiry_reminder;
        $future_date = (new \DateTime())->modify('+' . $days . ' days')->format('Y-m-d H:i:s');

        /* Get potential monitors from users that have almost all the conditions to get an email report right now */
        $result = database()->query("
            SELECT
                `user_id`,
                `name`,
                `email`,
                `plan_id`,
                `plan_expiration_date`,
                `language`,
                `anti_phishing_code`
            FROM 
                `users`
            WHERE 
                `status` = 1
                AND `plan_id` <> 'free'
                AND `plan_expiry_reminder` = '0'
                AND (`payment_subscription_id` IS NULL OR `payment_subscription_id` = '')
				AND `plan_expiration_date` < '{$future_date}'
            LIMIT 25
        ");

        $plans = [];
        if($result->num_rows) {
            $plans = (new \Altum\Models\Plan())->get_plans();
        }

        /* Go through each result */
        while($user = $result->fetch_object()) {

            /* Determine the exact days until expiration */
            $days_until_expiration = (new \DateTime($user->plan_expiration_date))->diff((new \DateTime()))->days;

            /* Prepare the email */
            $email_template = get_email_template(
                [
                    '{{DAYS_UNTIL_EXPIRATION}}' => $days_until_expiration,
                ],
                l('global.emails.user_plan_expiry_reminder.subject', $user->language),
                [
                    '{{DAYS_UNTIL_EXPIRATION}}' => $days_until_expiration,
                    '{{USER_PLAN_RENEW_LINK}}' => url('pay/' . $user->plan_id),
                    '{{NAME}}' => $user->name,
                    '{{PLAN_NAME}}' => $plans[$user->plan_id]->name,
                ],
                l('global.emails.user_plan_expiry_reminder.body', $user->language)
            );

            send_mail($user->email, $email_template->subject, $email_template->body, ['anti_phishing_code' => $user->anti_phishing_code, 'language' => $user->language]);

            /* Update user */
            db()->where('user_id', $user->user_id)->update('users', ['plan_expiry_reminder' => 1]);

            if(DEBUG) {
                echo sprintf('users_plan_expiry_reminder() -> Email sent for user_id %s', $user->user_id);
            }
        }

    }

    private function guests_payments_cleanup() {

        if(!\Altum\Plugin::is_active('payment-blocks')) {
            return;
        }

        /* Clean up unfulfilled guest payments */
        $x_days_ago_datetime = (new \DateTime())->modify('-' . 12 . ' hours')->format('Y-m-d H:i:s');

        database()->query("DELETE FROM `guests_payments` WHERE `datetime` < '{$x_days_ago_datetime}' AND `status` = 0");

    }

    private function users_aix_reset() {

        if(!\Altum\Plugin::is_active('aix')) {
            return;
        }

        db()->update('users', [
            'aix_documents_current_month' => 0,
            'aix_words_current_month' => 0,
            'aix_images_current_month' => 0,
            'aix_transcriptions_current_month' => 0,
            'aix_chats_current_month' => 0,
            'aix_syntheses_current_month' => 0,
            'aix_synthesized_characters_current_month' => 0,
        ]);
    }

    public function broadcasts() {

        $this->initiate();
        fc_ensure_email_automation_tables();

        /* We'll send up to X emails per run */
        $max_batch_size = settings()->content->broadcasts_emails_per_cron ?? 40;

        /* Fetch a broadcast in "processing" status */
        $broadcast = db()->where('status', 'processing')->getOne('broadcasts');
        if(!$broadcast) {
            $this->close();
            $this->update_cron_execution_datetimes('broadcasts_datetime');
            return;
        }

        $broadcast->users_ids = json_decode($broadcast->users_ids ?? '[]', true);
        $broadcast->sent_users_ids = json_decode($broadcast->sent_users_ids ?? '[]', true);
        $broadcast->settings = json_decode($broadcast->settings ?? '[]');

        /* Find which users are left to process */
        $remaining_user_ids = array_values(array_diff($broadcast->users_ids, $broadcast->sent_users_ids));

        /* If no one is left, mark broadcast as "sent" and exit */
        if(empty($remaining_user_ids)) {

            $sent_emails_count = count($broadcast->sent_users_ids);

            db()->where('broadcast_id', $broadcast->broadcast_id)->update('broadcasts', [
                'sent_emails'              => $sent_emails_count,
                'sent_users_ids'           => json_encode($broadcast->sent_users_ids),
                'status'                   => 'sent',
                'last_sent_email_datetime' => get_date(),
            ]);

            $this->close();
            $this->update_cron_execution_datetimes('broadcasts_datetime');

            return;
        }

        /* Get all batch users at once in one go */
        $user_ids_for_this_run = array_slice($remaining_user_ids, 0, $max_batch_size);

        $users = db()
            ->where('user_id', $user_ids_for_this_run, 'IN')
            ->get('users', null, [
                'user_id',
                'name',
                'email',
                'language',
                'anti_phishing_code',
                'continent_code',
                'country',
                'city_name',
                'device_type',
                'os_name',
                'browser_name',
                'browser_language'
            ]);

        $users_ids = array_column($users, 'user_id');

        /* Non existing users in this batch */
        $missing_user_ids = array_diff($user_ids_for_this_run, $users_ids);

        /* Mark non existing users as processed (sent) */
        $broadcast->sent_users_ids = array_merge($broadcast->sent_users_ids, $missing_user_ids);

        /* Send emails only for existing users */
        if(!empty($users)) {
            /* Loop through users and send */
            foreach($users as $user) {

                /* Prepare placeholders and the final template */
                /* Custom code: FC-2026-03-19: reuse broadcast variables including Forever Card application URL */
                $vars = fc_get_broadcast_user_variables($user);
                /* /Custom code: FC-2026-03-19 */

                $email_template = get_email_template(
                    $vars,
                    htmlspecialchars_decode($broadcast->subject),
                    $vars,
                    /* Custom code: FC-2026-03-19: support both legacy EditorJS and new Quill html broadcasts */
                    json_decode($broadcast->content) ? convert_editorjs_json_to_html($broadcast->content) : $broadcast->content
                    /* /Custom code: FC-2026-03-19 */
                );

                /* Optional: tracking pixel & link rewriting */
                if(settings()->content->broadcasts_statistics_is_enabled) {
                    $tracking_id = base64_encode('broadcast_id=' . $broadcast->broadcast_id . '&user_id=' . $user->user_id);
                    $email_template->body .= '<img src="' . SITE_URL . 'broadcast?id=' . $tracking_id . '" style="display: none;" />';
                    $email_template->body = preg_replace(
                        '/<a href=\"(.+)\"/',
                        '<a href="' . SITE_URL . 'broadcast?id=' . $tracking_id . '&url=$1"',
                        $email_template->body
                    );
                }

                /* Custom code: FC-2026-03-19: attach Brevo broadcast tags and capture transport result */
                $broadcast_tags = fc_get_email_broadcast_message_tags((int) $broadcast->broadcast_id, (int) $user->user_id);
                /* Custom code: FC-2026-03-19: embed signed unsubscribe link into broadcast sends */
                $broadcast_unsubscribe_url = fc_get_email_unsubscribe_url([
                    'message_type' => 'broadcast',
                    'broadcast_id' => (int) $broadcast->broadcast_id,
                    'user_id' => (int) $user->user_id,
                    'recipient_email' => $user->email,
                ]);
                $send_result = send_mail($user->email, $email_template->subject, $email_template->body, [
                    'is_broadcast' => true,
                    'is_system_email' => $broadcast->settings->is_system_email,
                    'anti_phishing_code' => $user->anti_phishing_code,
                    'language' => $user->language,
                    'brevo_tags' => $broadcast_tags,
                    'unsubscribe_url' => $broadcast_unsubscribe_url,
                    'return_transport_result' => true,
                ]);
                /* /Custom code: FC-2026-03-19 */
                fc_store_broadcast_message((int) $broadcast->broadcast_id, (int) $user->user_id, $user->email, $email_template->subject, $broadcast_tags, $send_result);
                /* /Custom code: FC-2026-03-19 */

                /* Track who we just processed (sent or attempted) */
                $broadcast->sent_users_ids[] = $user->user_id;
            }
        }

        /* Total "sent" (processed) */
        $sent_emails_count = count($broadcast->sent_users_ids);

        /* Check if all users (existing or not) have been processed */
        $all_users_processed = empty(array_diff($broadcast->users_ids, $broadcast->sent_users_ids));

        /* Update broadcast once for the entire batch */
        db()->where('broadcast_id', $broadcast->broadcast_id)->update('broadcasts', [
            'sent_emails'              => $sent_emails_count,
            'sent_users_ids'           => json_encode($broadcast->sent_users_ids),
            'status'                   => $all_users_processed ? 'sent' : 'processing',
            'last_sent_email_datetime' => get_date(),
        ]);

        /* Debugging */
        if(DEBUG) {
            echo '<br />' . 'broadcasts() - broadcast_id - ' . $broadcast->broadcast_id;
        }

        $this->close();

        $this->update_cron_execution_datetimes('broadcasts_datetime');
    }

    public function push_notifications() {
        if(\Altum\Plugin::is_active('push-notifications')) {

            $this->initiate();

            require_once \Altum\Plugin::get('push-notifications')->path . 'controllers/Cron.php';

            $this->close();

            /* mark cron execution */
            $this->update_cron_execution_datetimes('push_notifications_datetime');
        }
    }

}
