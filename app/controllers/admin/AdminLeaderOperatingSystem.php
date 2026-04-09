<?php
/* Custom code: FC-2026-03-31: Leader Operating System overview controller */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Title;

defined('ALTUMCODE') || die();

class AdminLeaderOperatingSystem extends Controller {

    private function ensure_feedback_workflow_columns(): void {
        static $is_checked = false;

        if($is_checked || !$this->has_feedback_tables()) {
            return;
        }

        $columns = [];
        $result = database()->query("SHOW COLUMNS FROM `feedback_tickets`");

        while($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = true;
        }

        if(!isset($columns['admin_last_replied_at'])) {
            database()->query("ALTER TABLE `feedback_tickets` ADD `admin_last_replied_at` DATETIME NULL DEFAULT NULL AFTER `last_datetime`");
        }

        if(!isset($columns['user_last_read_at'])) {
            database()->query("ALTER TABLE `feedback_tickets` ADD `user_last_read_at` DATETIME NULL DEFAULT NULL AFTER `admin_last_replied_at`");
        }

        if(!isset($columns['is_webinar_topic_suggestion'])) {
            database()->query("ALTER TABLE `feedback_tickets` ADD `is_webinar_topic_suggestion` TINYINT(1) NOT NULL DEFAULT 0 AFTER `user_last_read_at`");
        }

        if(!isset($columns['is_webinar_topic_confirmed'])) {
            database()->query("ALTER TABLE `feedback_tickets` ADD `is_webinar_topic_confirmed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_webinar_topic_suggestion`");
        }

        $is_checked = true;
    }

    private function auto_close_read_answered_tickets(): void {
        if(!$this->has_feedback_tables()) {
            return;
        }

        $this->ensure_feedback_workflow_columns();

        database()->query("
            UPDATE `feedback_tickets`
            SET `status` = 'closed'
            WHERE `status` = 'answered'
              AND `admin_last_replied_at` IS NOT NULL
              AND `user_last_read_at` IS NOT NULL
              AND `user_last_read_at` >= `admin_last_replied_at`
              AND `user_last_read_at` <= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
    }

    private function ensure_feedback_upload_directory_is_writable(): bool {
        $directory_path = \Altum\Uploads::get_full_path('feedback_tickets');

        if(!is_dir($directory_path)) {
            @mkdir($directory_path, 0755, true);
        }

        if(!is_writable($directory_path)) {
            @chmod($directory_path, 0755);
        }

        return is_dir($directory_path) && is_writable($directory_path);
    }

    private function create_support_resolution_note(int $feedback_ticket_id, int $user_id): void {
        if($feedback_ticket_id <= 0 || $user_id <= 0) {
            return;
        }

        db()->insert('feedback_ticket_messages', [
            'feedback_ticket_id' => $feedback_ticket_id,
            'user_id' => $user_id,
            'admin_user_id' => (int) ($this->user->user_id ?? null),
            'is_admin_reply' => 1,
            'message' => 'Ticket je označen kao riješen. Ako trebaš dodatnu pomoć, slobodno odgovori na ovaj ticket i ponovno ćemo ga otvoriti.',
            'attachment' => null,
            'datetime' => get_date(),
        ]);
    }

    private function get_preferences_object($preferences): \stdClass {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!$preferences instanceof \stdClass) {
            $preferences = (object) $preferences;
        }

        return $preferences;
    }

    private function is_active_pro_row(array $row): bool {
        if((string) ($row['plan_id'] ?? '') !== '5') {
            return false;
        }

        $plan_expiration_date = (string) ($row['plan_expiration_date'] ?? '');

        if($plan_expiration_date === '') {
            return true;
        }

        try {
            return (new \DateTimeImmutable($plan_expiration_date)) >= (new \DateTimeImmutable());
        } catch(\Throwable $exception) {
            return false;
        }
    }

    private function get_ai_growth_access_settings($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $access = $preferences->leader_ai_access ?? null;

        if(is_array($access)) {
            $access = (object) $access;
        }

        if(!$access instanceof \stdClass) {
            $access = new \stdClass();
        }

        $has_existing_app_review = !empty($preferences->leader_ai_app_reviews) && is_array($preferences->leader_ai_app_reviews) && !empty($preferences->leader_ai_app_reviews[0]);
        $has_existing_weekly_plan = !empty($preferences->leader_ai_weekly_plans) && is_array($preferences->leader_ai_weekly_plans) && !empty($preferences->leader_ai_weekly_plans[0]);

        return [
            'starter_app_review_used' => min(1, max(0, (int) ($access->starter_app_review_used ?? ($has_existing_app_review ? 1 : 0)))),
            'starter_weekly_plan_used' => min(1, max(0, (int) ($access->starter_weekly_plan_used ?? ($has_existing_weekly_plan ? 1 : 0)))),
            'manual_tier' => (string) ($access->manual_tier ?? ''),
            'manual_note' => (string) ($access->manual_note ?? ''),
            'manual_unlocked_at' => $access->manual_unlocked_at ?? null,
        ];
    }

    private function set_ai_growth_access_settings(\stdClass $preferences, array $settings): \stdClass {
        $preferences->leader_ai_access = (object) [
            'starter_app_review_used' => min(1, max(0, (int) ($settings['starter_app_review_used'] ?? 0))),
            'starter_weekly_plan_used' => min(1, max(0, (int) ($settings['starter_weekly_plan_used'] ?? 0))),
            'manual_tier' => (string) ($settings['manual_tier'] ?? ''),
            'manual_note' => (string) ($settings['manual_note'] ?? ''),
            'manual_unlocked_at' => $settings['manual_unlocked_at'] ?? null,
        ];

        return $preferences;
    }

    private function get_manual_ai_override_payload(array $access_settings): array {
        $manual_tier = (string) ($access_settings['manual_tier'] ?? '');
        $manual_unlocked_at = (string) ($access_settings['manual_unlocked_at'] ?? '');

        if($manual_tier === '' || $manual_unlocked_at === '') {
            return [
                'tier' => '',
                'is_active' => false,
                'expires_at' => null,
            ];
        }

        try {
            $expires_at = (new \DateTimeImmutable($manual_unlocked_at))->modify('+30 days');

            return [
                'tier' => $expires_at >= (new \DateTimeImmutable()) ? $manual_tier : '',
                'is_active' => $expires_at >= (new \DateTimeImmutable()),
                'expires_at' => $expires_at->format('Y-m-d H:i:s'),
            ];
        } catch(\Throwable $exception) {
            return [
                'tier' => '',
                'is_active' => false,
                'expires_at' => null,
            ];
        }
    }

    private function create_user_internal_notification(int $user_id, string $title, string $description, string $url): void {
        $this->ensure_user_internal_notifications_enabled();

        db()->insert('internal_notifications', [
            'user_id' => $user_id,
            'for_who' => 'user',
            'from_who' => 'system',
            'icon' => 'fas fa-sparkles',
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'datetime' => get_date(),
        ]);

        db()->where('user_id', $user_id)->update('users', [
            'has_pending_internal_notifications' => 1,
        ]);
    }

    private function create_admin_user_notification(int $user_id, string $title, string $description, string $url = '', string $icon = 'fas fa-comment-dots'): void {
        $this->ensure_user_internal_notifications_enabled();

        db()->insert('internal_notifications', [
            'user_id' => $user_id,
            'for_who' => 'user',
            'from_who' => 'admin',
            'icon' => $icon,
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'datetime' => get_date(),
        ]);

        db()->where('user_id', $user_id)->update('users', [
            'has_pending_internal_notifications' => 1,
        ]);
    }

    private function get_user_meta_object($preferences): \stdClass {
        $preferences = $this->get_preferences_object($preferences);
        $meta = $preferences->meta ?? null;

        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        if(!$meta instanceof \stdClass) {
            $meta = new \stdClass();
        }

        return $meta;
    }

    private function get_plan_name_label($plan_id, ?string $fallback_name = null): string {
        if($fallback_name !== null && $fallback_name !== '') {
            return $fallback_name;
        }

        if((string) $plan_id === 'free') {
            return settings()->plan_free->name;
        }

        if((string) $plan_id === 'custom') {
            return settings()->plan_custom->name;
        }

        $plan_id = is_numeric($plan_id) ? (int) $plan_id : input_clean((string) $plan_id, 32);
        $plan_name = db()->where('plan_id', $plan_id)->getValue('plans', 'name');

        return $plan_name ?: ('Plan #' . $plan_id);
    }

    private function get_main_biolink_record(int $user_id): ?object {
        if($user_id <= 0) {
            return null;
        }

        $main_biolink_id = (int) (fc_get_user_main_biolink_id($user_id) ?? 0);
        $biolink = null;

        if($main_biolink_id > 0) {
            $biolink = db()
                ->where('link_id', $main_biolink_id)
                ->where('user_id', $user_id)
                ->where('type', 'biolink')
                ->getOne('links', ['link_id', 'url']);
        }

        return $biolink ?: null;
    }

    private function ensure_user_primary_assets(int $user_id): array {
        $main_biolink = $this->get_main_biolink_record($user_id);

        if(!db()->where('user_id', $user_id)->has('users_vcards')) {
            $vcard = db()
                ->where('user_id', $user_id)
                ->where('type', 'vcard')
                ->orderBy('link_id', 'ASC')
                ->getOne('links', ['link_id']);

            if($vcard && !empty($vcard->link_id)) {
                db()->insert('users_vcards', [
                    'user_id' => $user_id,
                    'vcard_id' => $vcard->link_id,
                ]);
            }
        }

        $main_biolink_url = null;
        $main_biolink_nfc_url = null;

        if($main_biolink && !empty($main_biolink->url)) {
            $main_biolink_url = SITE_URL . $main_biolink->url;
            $main_biolink_nfc_url = \Altum\Link::get_share_tracking_url($main_biolink_url, 'nfc_card', 'nfc_tap', 'nfc_card');
        }

        return [
            'main_biolink_id' => (int) ($main_biolink->link_id ?? 0),
            'main_biolink_url' => $main_biolink_url,
            'main_biolink_nfc_url' => $main_biolink_nfc_url,
        ];
    }

    private function fire_user_new_webhook(int $user_id, string $name, string $email, \stdClass $meta, ?string $main_biolink_url): void {
        if(empty(settings()->webhooks->user_new)) {
            return;
        }

        try {
            \Unirest\Request::post(settings()->webhooks->user_new, [], [
                'user_id' => $user_id,
                'name' => $name,
                'email' => $email,
                'biolink' => $main_biolink_url,
                'phone' => $meta->phone ?? null,
                'address' => $meta->address ?? null,
                'zip' => $meta->zip ?? null,
                'city' => $meta->city ?? null,
                'country' => $meta->country ?? null,
                'foreverId' => $meta->foreverId ?? null,
            ]);
        } catch(\Throwable $exception) {
            error_log('[AdminLeaderOperatingSystem::fire_user_new_webhook] ' . $exception->getMessage());
        }
    }

    private function send_fcc_access_approved_email(object $user, ?string $main_biolink_url): void {
        if(empty($user->email)) {
            return;
        }

        $language = fc_resolve_language_name($user->language ?? null);
        $dashboard_link = url('dashboard');
        $account_plan_link = url('account-plan');
        $app_link_html = $this->build_fcc_email_link_html($main_biolink_url ?: $dashboard_link);

        $email_template = get_email_template(
            [],
            l('global.emails.admin.fcc_access_approved.subject', $language),
            [
                '{{NAME}}' => str_replace('.', '. ', (string) ($user->name ?? '')),
                '{{APP_LINK}}' => $app_link_html,
                '{{DASHBOARD_LINK}}' => $this->build_fcc_email_link_html($dashboard_link),
                '{{ACCOUNT_PLAN_LINK}}' => $this->build_fcc_email_link_html($account_plan_link),
            ],
            l('global.emails.admin.fcc_access_approved.body', $language)
        );

        send_mail($user->email, $email_template->subject, $email_template->body, [
            'anti_phishing_code' => $user->anti_phishing_code ?? null,
            'language' => $language,
        ]);
    }

    private function send_fcc_card_sent_email(object $user, ?string $main_biolink_url): void {
        if(empty($user->email)) {
            return;
        }

        $language = fc_resolve_language_name($user->language ?? null);
        $dashboard_link = url('dashboard');
        $app_link_html = $this->build_fcc_email_link_html($main_biolink_url ?: $dashboard_link);

        $email_template = get_email_template(
            [],
            l('global.emails.admin.card_sent_email.subject', $language),
            [
                '{{NAME}}' => str_replace('.', '. ', (string) ($user->name ?? '')),
                '{{LINK}}' => $app_link_html,
                '{{DASHBOARD_LINK}}' => $this->build_fcc_email_link_html($dashboard_link),
            ],
            l('global.emails.admin.card_sent_email.body', $language)
        );

        send_mail($user->email, $email_template->subject, $email_template->body, [
            'anti_phishing_code' => $user->anti_phishing_code ?? null,
            'language' => $language,
        ]);
    }

    private function send_fcc_access_rejected_email(object $user): bool {
        if(empty($user->email)) {
            return false;
        }

        $language = fc_resolve_language_name($user->language ?? null);

        $email_template = get_email_template(
            [],
            l('global.emails.admin.fcc_access_rejected.subject', $language),
            [
                '{{NAME}}' => str_replace('.', '. ', (string) ($user->name ?? '')),
                '{{CONTACT_EMAIL}}' => $this->build_fcc_email_link_html('mailto:info@forevercard.club', 'info@forevercard.club'),
            ],
            l('global.emails.admin.fcc_access_rejected.body', $language)
        );

        $mail_result = send_mail($user->email, $email_template->subject, $email_template->body, [
            'anti_phishing_code' => $user->anti_phishing_code ?? null,
            'language' => $language,
            'return_transport_result' => true,
        ]);

        if(is_object($mail_result) && property_exists($mail_result, 'success')) {
            return (bool) $mail_result->success;
        }

        return (bool) $mail_result;
    }

    private function build_fcc_email_link_html(string $url, ?string $label = null): string {
        $url = trim($url);
        $label = trim((string) ($label ?? $url));

        if($url === '' || $label === '') {
            return '';
        }

        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="color:#9de7e0;text-decoration:underline;font-weight:700;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    private function create_support_ticket_from_admin_message(int $user_id, string $title, string $description): ?int {
        if(!$this->has_feedback_tables()) {
            return null;
        }

        $this->ensure_feedback_workflow_columns();

        $feedback_ticket_id = (int) db()->insert('feedback_tickets', [
            'user_id' => $user_id,
            'subject' => $title,
            'category' => 'other',
            'status' => 'answered',
            'screenshot' => null,
            'datetime' => get_date(),
            'last_datetime' => get_date(),
            'admin_last_replied_at' => get_date(),
            'user_last_read_at' => null,
        ]);

        if($feedback_ticket_id <= 0) {
            return null;
        }

        db()->insert('feedback_ticket_messages', [
            'feedback_ticket_id' => $feedback_ticket_id,
            'user_id' => $user_id,
            'admin_user_id' => (int) ($this->user->user_id ?? null),
            'is_admin_reply' => 1,
            'message' => $description,
            'attachment' => null,
            'datetime' => get_date(),
        ]);

        return $feedback_ticket_id;
    }

    private function ensure_user_internal_notifications_enabled(): void {
        $settings_payload = settings()->internal_notifications ?? null;

        if(is_array($settings_payload)) {
            $settings_payload = (object) $settings_payload;
        }

        if($settings_payload instanceof \stdClass && !empty($settings_payload->users_is_enabled)) {
            return;
        }

        $value = [
            'users_is_enabled' => 1,
            'admins_is_enabled' => (int) (($settings_payload->admins_is_enabled ?? 0)),
            'new_user' => (int) (($settings_payload->new_user ?? 0)),
            'delete_user' => (int) (($settings_payload->delete_user ?? 0)),
            'new_newsletter_subscriber' => (int) (($settings_payload->new_newsletter_subscriber ?? 0)),
            'new_payment' => (int) (($settings_payload->new_payment ?? 0)),
            'new_affiliate_withdrawal' => (int) (($settings_payload->new_affiliate_withdrawal ?? 0)),
        ];

        db()->where('`key`', 'internal_notifications')->update('settings', [
            'value' => json_encode($value),
        ]);

        settings()->internal_notifications = (object) $value;
    }

    private function enrich_rows_with_ai_growth_signal(array $rows): array {
        if(empty($rows)) {
            return $rows;
        }

        $period_start_datetime = $this->get_period_start_datetime(30);
        $row_map = [];

        foreach($rows as $index => $row) {
            $user_id = (int) ($row['user_id'] ?? 0);

            if($user_id <= 0) {
                continue;
            }

            $row_map[$user_id] = $index;
            $rows[$index]['ai_shop_clicks_30d'] = 0;
            $rows[$index]['ai_whatsapp_contacts_30d'] = 0;
            $rows[$index]['ai_funnel_registrations_30d'] = 0;
            $rows[$index]['ai_growth_signal_30d'] = 0;
        }

        if(empty($row_map)) {
            return $rows;
        }

        $user_ids_sql = implode(',', array_map('intval', array_keys($row_map)));
        $shop_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $relevant_types = array_unique(array_merge($shop_types, ['lead_funnel', 'custom_html_whatsapp', 'socials', 'link']));
        $relevant_types_sql = "'" . implode("','", array_map(static function($type) {
            return str_replace("'", "\\'", (string) $type);
        }, $relevant_types)) . "'";

        $signal_targets = [];
        $blocks_result = database()->query("SELECT `user_id`, `biolink_block_id`, `type`, `settings`
            FROM `biolinks_blocks`
            WHERE `user_id` IN ({$user_ids_sql})
              AND `type` IN ({$relevant_types_sql})");

        while($row = $blocks_result->fetch_object()) {
            $user_id = (int) ($row->user_id ?? 0);
            $block_id = (int) ($row->biolink_block_id ?? 0);
            $type = (string) ($row->type ?? '');

            if(!$user_id || !$block_id || !isset($row_map[$user_id])) {
                continue;
            }

            $settings = $this->decode_biolink_block_settings($row->settings ?? null);

            if(in_array($type, $shop_types, true)) {
                $signal_targets[$block_id]['ai_shop_clicks_30d'] = $user_id;
            }

            if($type === 'lead_funnel') {
                $signal_targets[$block_id]['ai_funnel_registrations_30d'] = $user_id;
            }

            if($this->is_whatsapp_block($type, $settings)) {
                $signal_targets[$block_id]['ai_whatsapp_contacts_30d'] = $user_id;
            }
        }

        if(!empty($signal_targets)) {
            $block_ids_sql = implode(',', array_map('intval', array_keys($signal_targets)));
            $track_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                FROM `track_links`
                WHERE `datetime` >= '{$period_start_datetime}'
                  AND `is_unique` = 1
                  AND `biolink_block_id` IN ({$block_ids_sql})
                GROUP BY `biolink_block_id`");

            while($track_row = $track_result->fetch_object()) {
                $block_id = (int) ($track_row->biolink_block_id ?? 0);
                $total = (int) ($track_row->total ?? 0);

                foreach(($signal_targets[$block_id] ?? []) as $signal_key => $user_id) {
                    $row_index = $row_map[(int) $user_id] ?? null;

                    if($row_index === null) {
                        continue;
                    }

                    $rows[$row_index][$signal_key] += $total;
                }
            }

            $funnel_block_ids = [];
            foreach($signal_targets as $block_id => $target_map) {
                if(isset($target_map['ai_funnel_registrations_30d'])) {
                    $funnel_block_ids[] = (int) $block_id;
                }
            }

            if(!empty($funnel_block_ids)) {
                $funnel_block_ids_sql = implode(',', array_map('intval', $funnel_block_ids));
                $funnel_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                    FROM `data`
                    WHERE `type` = 'lead_funnel'
                      AND `datetime` >= '{$period_start_datetime}'
                      AND `biolink_block_id` IN ({$funnel_block_ids_sql})
                    GROUP BY `biolink_block_id`");

                while($funnel_row = $funnel_result->fetch_object()) {
                    $block_id = (int) ($funnel_row->biolink_block_id ?? 0);
                    $total = (int) ($funnel_row->total ?? 0);
                    $user_id = (int) (($signal_targets[$block_id]['ai_funnel_registrations_30d'] ?? 0));
                    $row_index = $row_map[$user_id] ?? null;

                    if($row_index === null) {
                        continue;
                    }

                    $rows[$row_index]['ai_funnel_registrations_30d'] += $total;
                }
            }
        }

        foreach($rows as $index => $row) {
            $rows[$index]['ai_growth_signal_30d'] = (int) (
                (int) ($row['ai_shop_clicks_30d'] ?? 0)
                + (int) ($row['ai_whatsapp_contacts_30d'] ?? 0)
                + (int) ($row['ai_funnel_registrations_30d'] ?? 0)
            );
        }

        return $rows;
    }

    private function get_ai_access_payload(array $row): array {
        $preferences = $this->get_preferences_object($row['preferences'] ?? null);
        $access_settings = $this->get_ai_growth_access_settings($preferences);
        $is_pro = $this->is_active_pro_row($row);
        $manual_override = $this->get_manual_ai_override_payload($access_settings);
        $manual_tier = (string) ($manual_override['tier'] ?? '');
        $growth_signal_30d = (int) ($row['ai_growth_signal_30d'] ?? 0);
        $starter_app_review_used = (int) ($access_settings['starter_app_review_used'] ?? 0);
        $starter_weekly_plan_used = (int) ($access_settings['starter_weekly_plan_used'] ?? 0);

        $tier_key = 'locked';
        $tier_label = 'Bez PRO pristupa';
        $tier_class = 'status-dark';
        $analysis_interval_days = null;

        if($is_pro) {
            if($manual_tier === 'pro_vip' || $growth_signal_30d >= 50) {
                $tier_key = 'pro_vip';
                $tier_label = 'PRO VIP';
                $tier_class = 'status-success';
                $analysis_interval_days = 7;
            } elseif(in_array($manual_tier, ['pro_active', 'pro_vip'], true) || $growth_signal_30d >= 15) {
                $tier_key = 'pro_active';
                $tier_label = 'PRO Active';
                $tier_class = 'status-info';
                $analysis_interval_days = 14;
            } else {
                $tier_key = 'pro_start';
                $tier_label = 'PRO Start';
                $tier_class = 'status-warning';
            }
        }

        $source_label = $manual_tier !== '' ? 'Ručni unlock (30 dana)' : 'Automatski';
        $starter_label = 'Analiza ' . ($starter_app_review_used ? 'iskorištena' : 'dostupna') . ' · Plan ' . ($starter_weekly_plan_used ? 'iskorišten' : 'dostupan');

        return [
            'ai_access_is_pro' => $is_pro,
            'ai_access_tier_key' => $tier_key,
            'ai_access_tier_label' => $tier_label,
            'ai_access_tier_class' => $tier_class,
            'ai_access_source_label' => $source_label,
            'ai_access_manual_tier' => $manual_tier,
            'ai_access_manual_expires_at' => $manual_override['expires_at'] ?? null,
            'ai_access_analysis_interval_days' => $analysis_interval_days,
            'ai_access_growth_signal_30d' => $growth_signal_30d,
            'ai_access_shop_clicks_30d' => (int) ($row['ai_shop_clicks_30d'] ?? 0),
            'ai_access_funnel_registrations_30d' => (int) ($row['ai_funnel_registrations_30d'] ?? 0),
            'ai_access_whatsapp_contacts_30d' => (int) ($row['ai_whatsapp_contacts_30d'] ?? 0),
            'ai_access_starter_app_review_used' => $starter_app_review_used,
            'ai_access_starter_weekly_plan_used' => $starter_weekly_plan_used,
            'ai_access_starter_label' => $starter_label,
        ];
    }

    private function handle_ai_access_action(array $redirect_query): void {
        if(empty($_POST['los_ai_unlock_action']) || empty($_POST['user_id'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $user_id = (int) $_POST['user_id'];
        $action = input_clean($_POST['los_ai_unlock_action'] ?? '', 32);
        $allowed_actions = ['pro_active', 'pro_vip', 'auto'];

        if($user_id <= 0 || !in_array($action, $allowed_actions, true)) {
            Alerts::add_error('AI unlock akcija nije valjana.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'name', 'preferences', 'plan_id', 'plan_expiration_date']);

        if(!$user) {
            Alerts::add_error('Suradnik nije pronađen.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $row = [
            'plan_id' => $user->plan_id ?? '',
            'plan_expiration_date' => $user->plan_expiration_date ?? '',
        ];

        if(!$this->is_active_pro_row($row)) {
            Alerts::add_error('Ručni AI unlock moguć je samo za aktivni PRO paket.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $preferences = $this->get_preferences_object($user->preferences ?? null);
        $settings = $this->get_ai_growth_access_settings($preferences);

        if($action === 'auto') {
            $settings['manual_tier'] = '';
            $settings['manual_note'] = '';
            $settings['manual_unlocked_at'] = null;
        } else {
            $settings['manual_tier'] = $action;
            $settings['manual_note'] = 'LOS ručni unlock';
            $settings['manual_unlocked_at'] = get_date();
        }

        $preferences = $this->set_ai_growth_access_settings($preferences, $settings);

        db()->where('user_id', $user_id)->update('users', [
            'preferences' => json_encode($preferences),
        ]);

        cache()->deleteItemsByTag('user_id=' . $user_id);
        cache()->deleteItem('user?user_id=' . $user_id);

        if($action === 'pro_active') {
            $this->create_user_internal_notification(
                $user_id,
                'Aktiviran ti je dodatni AI pristup',
                'Otključan ti je PRO Active pristup. Sada imaš tjedni plan i novu analizu aplikacije svakih 14 dana.',
                url('ai-plan')
            );
            Alerts::add_success('Ručno je otključan PRO Active za ' . ($user->name ?: ('korisnika #' . $user_id)) . '.');
        } elseif($action === 'pro_vip') {
            $this->create_user_internal_notification(
                $user_id,
                'Aktiviran ti je VIP AI pristup',
                'Otključan ti je PRO VIP pristup. Sada imaš tjedni plan i novu analizu aplikacije svakih 7 dana.',
                url('ai-plan')
            );
            Alerts::add_success('Ručno je otključan PRO VIP za ' . ($user->name ?: ('korisnika #' . $user_id)) . '.');
        } else {
            Alerts::add_success('AI pristup za ' . ($user->name ?: ('korisnika #' . $user_id)) . ' vraćen je na automatska pravila.');
        }

        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function handle_team_strategist_action(array $redirect_query): void {
        if(!isset($_POST['generate_team_strategist']) && !isset($_POST['regenerate_team_strategist'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        try {
            $selected_period = (string) ($redirect_query['period'] ?? '30d');
            $overview_payload = $this->get_overview_payload(
                $selected_period,
                (string) ($redirect_query['search'] ?? ''),
                (string) ($redirect_query['status'] ?? 'all'),
                (string) ($redirect_query['ai_status'] ?? 'all'),
                (string) ($redirect_query['anomaly_status'] ?? 'all'),
                (string) ($redirect_query['fraud_status'] ?? 'all'),
                (string) ($redirect_query['sort'] ?? 'leader_os'),
                1
            );

            $force_refresh = isset($_POST['regenerate_team_strategist']);
            $this->generate_team_strategist_report($overview_payload, $selected_period, $force_refresh);

            Alerts::add_success($force_refresh ? 'AI strategist analiza je osvježena.' : 'AI strategist analiza je generirana.');
        } catch(\Throwable $exception) {
            Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 280) ?: 'AI strategist analiza nije uspjela.');
        }

        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function handle_priority_collaborator_message_action(array $redirect_query): void {
        if(!isset($_POST['send_priority_collaborator_message'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $target_user_id = (int) ($_POST['target_user_id'] ?? 0);
        $title = input_clean($_POST['message_title'] ?? '', 128);
        $description = input_clean($_POST['message_description'] ?? '', 1024);

        if($target_user_id <= 0) {
            Alerts::add_error('Odaberi suradnika kojem želiš poslati poruku.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if($title === '' || $description === '') {
            Alerts::add_error('Naslov i poruka su obavezni.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $user = db()->where('user_id', $target_user_id)->getOne('users', ['user_id', 'name']);

        if(!$user) {
            Alerts::add_error('Suradnik nije pronađen.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        try {
            $selected_period = (string) ($redirect_query['period'] ?? '30d');
            $feedback_ticket_id = $this->create_support_ticket_from_admin_message($target_user_id, $title, $description);
            $detail_url = $feedback_ticket_id
                ? url('feedback-tickets/ticket/' . $feedback_ticket_id)
                : url('admin/leader-operating-system-leader?user_id=' . $target_user_id . '&period=' . $selected_period);

            $this->create_admin_user_notification(
                $target_user_id,
                $title,
                $description,
                $detail_url,
                'fas fa-comment-dots'
            );

            Alerts::add_success('Poruka je poslana suradniku ' . ($user->name ?: ('#' . $target_user_id)) . '.');
        } catch(\Throwable $exception) {
            Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 220) ?: 'Poruku nije bilo moguće poslati.');
        }

        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function handle_group_message_action(array $redirect_query): void {
        if(!isset($_POST['send_group_message'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $group_key = input_clean($_POST['target_group'] ?? '', 32);
        $allowed_groups = ['team', 'risk', 'rising', 'priority'];
        $title = input_clean($_POST['group_message_title'] ?? '', 128);
        $description = input_clean($_POST['group_message_description'] ?? '', 1024);

        if(!in_array($group_key, $allowed_groups, true)) {
            Alerts::add_error('Odaberi valjanu grupu suradnika.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if($title === '' || $description === '') {
            Alerts::add_error('Naslov i poruka za grupu su obavezni.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        try {
            $selected_period = (string) ($redirect_query['period'] ?? '30d');
            $overview_payload = $this->get_overview_payload(
                $selected_period,
                (string) ($redirect_query['search'] ?? ''),
                (string) ($redirect_query['status'] ?? 'all'),
                (string) ($redirect_query['ai_status'] ?? 'all'),
                (string) ($redirect_query['anomaly_status'] ?? 'all'),
                (string) ($redirect_query['fraud_status'] ?? 'all'),
                (string) ($redirect_query['sort'] ?? 'leader_os'),
                1
            );

            $message_targets = $overview_payload['message_targets'] ?? [];
            $target_group = $message_targets[$group_key] ?? null;
            $user_ids = array_values(array_filter(array_map('intval', (array) ($target_group['user_ids'] ?? []))));

            if(empty($user_ids)) {
                throw new \Exception('Za odabranu grupu trenutno nema suradnika.');
            }

            $group_label = (string) ($target_group['label'] ?? 'odabranu grupu');
            $landing_url = url('admin/leader-operating-system?tab=coaching&period=' . $selected_period);

            foreach($user_ids as $user_id) {
                $this->create_admin_user_notification(
                    $user_id,
                    $title,
                    $description,
                    $landing_url,
                    'fas fa-bullhorn'
                );
            }

            Alerts::add_success('Poruka je poslana za ' . $group_label . ' (' . nr(count($user_ids)) . ').');
        } catch(\Throwable $exception) {
            Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 240) ?: 'Grupnu poruku nije bilo moguće poslati.');
        }

        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function handle_message_center_action(array $redirect_query): void {
        if(!isset($_POST['send_message_center_message'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $target_mode = input_clean($_POST['message_target_mode'] ?? '', 16);
        $title = input_clean($_POST['message_title'] ?? '', 128);
        $description = input_clean($_POST['message_description'] ?? '', 1024);

        if(!in_array($target_mode, ['single', 'group'], true)) {
            Alerts::add_error('Odaberi želiš li poslati poruku pojedincu ili grupi.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if($title === '' || $description === '') {
            Alerts::add_error('Naslov i poruka su obavezni.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if($target_mode === 'single') {
            $target_user_id = (int) ($_POST['message_target_user_id'] ?? 0);

            if($target_user_id <= 0) {
                Alerts::add_error('Odaberi suradnika kojem želiš poslati poruku.');
                redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
            }

            $user = db()->where('user_id', $target_user_id)->getOne('users', ['user_id', 'name']);

            if(!$user) {
                Alerts::add_error('Suradnik nije pronađen.');
                redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
            }

        try {
            $selected_period = (string) ($redirect_query['period'] ?? '30d');
            $feedback_ticket_id = $this->create_support_ticket_from_admin_message($target_user_id, $title, $description);
            $detail_url = $feedback_ticket_id
                ? url('feedback-tickets/ticket/' . $feedback_ticket_id)
                : url('admin/leader-operating-system-leader?user_id=' . $target_user_id . '&period=' . $selected_period);

            $this->create_admin_user_notification(
                $target_user_id,
                $title,
                    $description,
                    $detail_url,
                    'fas fa-comment-dots'
                );

                Alerts::add_success('Poruka je poslana suradniku ' . ($user->name ?: ('#' . $target_user_id)) . '.');
            } catch(\Throwable $exception) {
                Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 220) ?: 'Poruku nije bilo moguće poslati.');
            }

            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $group_key = input_clean($_POST['message_target_group'] ?? '', 32);
        $allowed_groups = ['team', 'risk', 'rising', 'priority'];

        if(!in_array($group_key, $allowed_groups, true)) {
            Alerts::add_error('Odaberi valjanu grupu suradnika.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        try {
            $selected_period = (string) ($redirect_query['period'] ?? '30d');
            $overview_payload = $this->get_overview_payload(
                $selected_period,
                (string) ($redirect_query['search'] ?? ''),
                (string) ($redirect_query['status'] ?? 'all'),
                (string) ($redirect_query['ai_status'] ?? 'all'),
                (string) ($redirect_query['anomaly_status'] ?? 'all'),
                (string) ($redirect_query['fraud_status'] ?? 'all'),
                (string) ($redirect_query['sort'] ?? 'leader_os'),
                1
            );

            $message_targets = $overview_payload['message_targets'] ?? [];
            $target_group = $message_targets[$group_key] ?? null;
            $user_ids = array_values(array_filter(array_map('intval', (array) ($target_group['user_ids'] ?? []))));

            if(empty($user_ids)) {
                throw new \Exception('Za odabranu grupu trenutno nema suradnika.');
            }

            $group_label = (string) ($target_group['label'] ?? 'odabranu grupu');
            $landing_url = url('admin/leader-operating-system?tab=coaching&period=' . $selected_period);

            foreach($user_ids as $user_id) {
                $this->create_admin_user_notification(
                    $user_id,
                    $title,
                    $description,
                    $landing_url,
                    'fas fa-bullhorn'
                );
            }

            Alerts::add_success('Poruka je poslana za ' . $group_label . ' (' . nr(count($user_ids)) . ').');
        } catch(\Throwable $exception) {
            Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 240) ?: 'Grupnu poruku nije bilo moguće poslati.');
        }

        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function handle_support_ticket_ai_action(array $redirect_query): void {
        if(!isset($_POST['generate_support_ticket_ai'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $feedback_ticket_id = (int) ($_POST['feedback_ticket_id'] ?? 0);
        $redirect_query['support_ticket_id'] = $feedback_ticket_id;

        if($feedback_ticket_id <= 0) {
            Alerts::add_error('Odaberi valjani support ticket.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if(!$this->has_feedback_tables()) {
            Alerts::add_error('Support ticket modul trenutno nije dostupan.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $ticket = db()
            ->join('users', 'feedback_tickets.user_id = users.user_id', 'LEFT')
            ->where('feedback_tickets.feedback_ticket_id', $feedback_ticket_id)
            ->getOne('feedback_tickets', [
                'feedback_tickets.*',
                'users.name as user_name',
                'users.email as user_email',
            ]);

        if(!$ticket) {
            Alerts::add_error('Support ticket nije pronađen.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $messages = db()->where('feedback_ticket_id', $feedback_ticket_id)->orderBy('feedback_ticket_message_id', 'ASC')->get('feedback_ticket_messages');
        $initial_user_message = '';
        $latest_admin_reply = '';

        foreach($messages as $message) {
            if((int) ($message->is_admin_reply ?? 0) === 0 && $initial_user_message === '') {
                $initial_user_message = trim((string) ($message->message ?? ''));
            }

            if((int) ($message->is_admin_reply ?? 0) === 1) {
                $latest_admin_reply = trim((string) ($message->message ?? ''));
            }
        }

        $ticket_payload = [
            'feedback_ticket_id' => (int) ($ticket->feedback_ticket_id ?? 0),
            'user_id' => (int) ($ticket->user_id ?? 0),
            'subject' => (string) ($ticket->subject ?? ''),
            'category_key' => (string) ($ticket->category ?? 'other'),
            'category_label' => match((string) ($ticket->category ?? 'other')) {
                'change' => 'Promjena',
                'add' => 'Prijedlog',
                'bug' => 'Bug',
                default => 'Ostalo',
            },
            'status_key' => (string) ($ticket->status ?? 'open'),
            'status_label' => match((string) ($ticket->status ?? 'open')) {
                'answered' => 'Odgovoreno',
                'closed' => 'Zatvoreno',
                default => 'Otvoreno',
            },
            'user_name' => (string) ($ticket->user_name ?? l('global.unknown')),
            'user_email' => (string) ($ticket->user_email ?? ''),
            'last_datetime' => (string) ($ticket->last_datetime ?? ''),
            'initial_user_message' => $initial_user_message,
            'latest_admin_reply' => $latest_admin_reply,
            'message_count' => count($messages),
            'is_stale' => (string) ($ticket->status ?? 'open') !== 'closed' && (string) ($ticket->last_datetime ?? '') !== '' && (string) ($ticket->last_datetime ?? '') <= date('Y-m-d H:i:s', strtotime('-3 days')),
        ];

        try {
            $this->generate_support_ticket_ai_report($ticket_payload, true);
            Alerts::add_success('AI analiza support ticketa je generirana.');
        } catch(\Throwable $exception) {
            Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 240) ?: 'AI analiza support ticketa nije uspjela.');
        }

        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function handle_support_ticket_reply_action(array $redirect_query): void {
        if(!isset($_POST['reply_support_ticket']) && !isset($_POST['send_support_communication'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $feedback_ticket_id = (int) ($_POST['feedback_ticket_id'] ?? 0);
        $redirect_query['support_ticket_id'] = $feedback_ticket_id;
        $message = input_clean($_POST['support_reply_message'] ?? '', 10000);
        $communication_mode = input_clean($_POST['support_communication_mode'] ?? 'ticket', 16);
        $notification_title = input_clean($_POST['support_communication_title'] ?? '', 128);
        $attachment = null;

        if($feedback_ticket_id <= 0) {
            Alerts::add_error('Odaberi valjani support ticket.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if($message === '') {
            Alerts::add_error('Odgovor ne može biti prazan.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if(!in_array($communication_mode, ['ticket', 'notification', 'both'], true)) {
            Alerts::add_error('Način komunikacije nije valjan.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if(!empty($_FILES['attachment']['name'])) {
            if($this->ensure_feedback_upload_directory_is_writable()) {
                $attachment = \Altum\Uploads::process_upload(null, 'feedback_tickets', 'attachment', 'attachment_remove', 5);
            } else {
                Alerts::add_warning(l('feedback_tickets.alert.upload_directory_not_writable'));
            }
        }

        if(Alerts::has_field_errors() || Alerts::has_errors()) {
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $feedback_ticket = db()
            ->join('users', 'feedback_tickets.user_id = users.user_id', 'LEFT')
            ->where('feedback_tickets.feedback_ticket_id', $feedback_ticket_id)
            ->getOne('feedback_tickets', [
                'feedback_tickets.*',
                'users.name as user_name',
            ]);

        if(!$feedback_ticket) {
            Alerts::add_error('Support ticket nije pronađen.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if(in_array($communication_mode, ['ticket', 'both'], true)) {
            $this->ensure_feedback_workflow_columns();

            db()->insert('feedback_ticket_messages', [
                'feedback_ticket_id' => $feedback_ticket->feedback_ticket_id,
                'user_id' => $feedback_ticket->user_id,
                'admin_user_id' => $this->user->user_id,
                'is_admin_reply' => 1,
                'message' => $message,
                'attachment' => $attachment,
                'datetime' => get_date(),
            ]);

            db()->where('feedback_ticket_id', $feedback_ticket->feedback_ticket_id)->update('feedback_tickets', [
                'status' => 'answered',
                'last_datetime' => get_date(),
                'admin_last_replied_at' => get_date(),
                'user_last_read_at' => null,
            ]);
        }

        if(in_array($communication_mode, ['notification', 'both'], true)) {
            $this->create_admin_user_notification(
                (int) $feedback_ticket->user_id,
                $notification_title !== '' ? $notification_title : ('Odgovor na tvoj upit: ' . (string) ($feedback_ticket->subject ?? '')),
                $message,
                url('feedback-tickets/ticket/' . $feedback_ticket->feedback_ticket_id),
                'fas fa-reply'
            );
        }

        $success_message = match($communication_mode) {
            'both' => 'Odgovor je poslan u ticket i kao vidljiva obavijest suradniku ' . (($feedback_ticket->user_name ?? '') ?: ('#' . (int) $feedback_ticket->user_id)) . '.',
            'notification' => 'Vidljiva privatna poruka je poslana suradniku ' . (($feedback_ticket->user_name ?? '') ?: ('#' . (int) $feedback_ticket->user_id)) . '.',
            default => 'Odgovor je poslan u ticket suradniku ' . (($feedback_ticket->user_name ?? '') ?: ('#' . (int) $feedback_ticket->user_id)) . '.',
        };

        Alerts::add_success($success_message);
        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function handle_support_ticket_status_action(array $redirect_query): void {
        if(!isset($_POST['update_support_ticket_status'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $feedback_ticket_id = (int) ($_POST['feedback_ticket_id'] ?? 0);
        $redirect_query['support_ticket_id'] = $feedback_ticket_id;
        $next_status = input_clean($_POST['next_status'] ?? '', 16);

        if($feedback_ticket_id <= 0 || !in_array($next_status, ['open', 'closed'], true)) {
            Alerts::add_error('Status support ticketa nije valjan.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $feedback_ticket = db()->where('feedback_ticket_id', $feedback_ticket_id)->getOne('feedback_tickets');

        if(!$feedback_ticket) {
            Alerts::add_error('Support ticket nije pronađen.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $update_payload = [
            'status' => $next_status,
            'last_datetime' => get_date(),
        ];

        if($next_status === 'closed') {
            $this->ensure_feedback_workflow_columns();
            $this->create_support_resolution_note((int) $feedback_ticket_id, (int) ($feedback_ticket->user_id ?? 0));

            $update_payload['admin_last_replied_at'] = get_date();
            $update_payload['user_last_read_at'] = null;

            $this->create_admin_user_notification(
                (int) ($feedback_ticket->user_id ?? 0),
                'Tvoj support ticket je riješen',
                'Otvorili smo ticket i označili ga kao riješen. Ako želiš provjeriti odgovor ili dodati novo pitanje, otvori ticket.',
                url('feedback-tickets/ticket/' . $feedback_ticket_id),
                'fas fa-check-circle'
            );
        }

        if($next_status === 'open') {
            $update_payload['user_last_read_at'] = null;
        }

        db()->where('feedback_ticket_id', $feedback_ticket_id)->update('feedback_tickets', $update_payload);

        Alerts::add_success($next_status === 'closed' ? 'Support ticket je označen kao riješen.' : 'Support ticket je ponovno otvoren.');
        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function handle_support_ticket_webinar_action(array $redirect_query): void {
        if(!isset($_POST['toggle_support_ticket_webinar'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if(!$this->has_feedback_tables()) {
            Alerts::add_error('Support ticket modul trenutno nije dostupan.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $this->ensure_feedback_workflow_columns();

        $feedback_ticket_id = (int) ($_POST['feedback_ticket_id'] ?? 0);
        $redirect_query['support_ticket_id'] = $feedback_ticket_id;
        $confirmed = (int) ($_POST['is_webinar_topic_confirmed'] ?? 0) === 1 ? 1 : 0;

        if($feedback_ticket_id <= 0) {
            Alerts::add_error('Odaberi valjani support ticket.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $feedback_ticket = db()->where('feedback_ticket_id', $feedback_ticket_id)->getOne('feedback_tickets', ['feedback_ticket_id', 'subject']);

        if(!$feedback_ticket) {
            Alerts::add_error('Support ticket nije pronađen.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        db()->where('feedback_ticket_id', $feedback_ticket_id)->update('feedback_tickets', [
            'is_webinar_topic_confirmed' => $confirmed,
            'last_datetime' => get_date(),
        ]);

        $this->clear_team_strategist_cache();

        Alerts::add_success(
            $confirmed
                ? 'Tema je potvrđena kao relevantna za webinar i ulazi u strategist prijedloge.'
                : 'Tema je maknuta iz potvrđenih webinar prijedloga.'
        );

        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function get_period_days(string $period_key): int {
        return [
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
        ][$period_key] ?? 30;
    }

    private function get_period_start_datetime(int $days): string {
        $days = max(1, $days);

        return (new \DateTime())->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');
    }

    private function has_funnel_events_table(): bool {
        static $has_funnel_events_table = null;

        if($has_funnel_events_table !== null) {
            return $has_funnel_events_table;
        }

        $result = database()->query("SHOW TABLES LIKE 'funnel_events'");
        $has_funnel_events_table = (bool) ($result && $result->num_rows);

        return $has_funnel_events_table;
    }

    private function get_team_app_webshop_block_types(): array {
        $app_webshop_block_types = \Altum\Link::get_monitored_forever_outbound_types();
        $app_webshop_block_types[] = 'link_forever_living_albania_kosovo';

        return array_values(array_unique($app_webshop_block_types));
    }

    private function get_team_app_webshop_block_types_sql(): string {
        return "'" . implode("', '", $this->get_team_app_webshop_block_types()) . "'";
    }

    private function get_team_blog_referral_click_condition_sql(string $track_links_alias): string {
        $blog_mediums = $this->get_blog_cta_mediums();
        $product_medium = db()->escape($blog_mediums['product']);
        $business_medium = db()->escape($blog_mediums['business']);

        return "({$track_links_alias}.`utm_medium` IN ('{$product_medium}', '{$business_medium}') AND {$track_links_alias}.`utm_campaign` LIKE 'blog_post:%')";
    }

    private function get_country_table_key(?string $country_code): string {
        $country_code = strtoupper(trim((string) $country_code));

        return $country_code !== '' ? $country_code : '__unknown__';
    }

    private function get_country_table_name(string $country_key): string {
        if($country_key === '__unknown__') {
            return l('admin_leader_operating_system.leader.country_table.unknown');
        }

        return get_country_from_country_code($country_key);
    }

    private function get_growth_metrics(int $current, int $previous): array {
        $difference = $current - $previous;
        $growth_percent = null;

        if($previous > 0) {
            $growth_percent = round(($difference / $previous) * 100, 1);
        } elseif($current === 0) {
            $growth_percent = 0.0;
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => $difference,
            'growth_percent' => $growth_percent,
        ];
    }

    private function get_biolink_sets(): array {
        $forever_shop_block_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $forever_registration_block_types = ['link_forever_shop'];

        return [
            'forever_shop_block_types_sql' => "'" . implode("', '", $forever_shop_block_types) . "'",
            'forever_registration_block_types_sql' => "'" . implode("', '", $forever_registration_block_types) . "'",
        ];
    }

    private function extract_forever_id_from_preferences($preferences): string {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!is_object($preferences)) {
            return '-';
        }

        $meta = $preferences->meta ?? null;

        if(is_string($meta)) {
            $decoded_meta = json_decode($meta);
            if(is_array($decoded_meta)) {
                $decoded_meta = (object) $decoded_meta;
            }
            if(is_object($decoded_meta)) {
                $meta = $decoded_meta;
            }
        }

        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        if(!is_object($meta)) {
            return '-';
        }

        $forever_id = $meta->foreverId ?? $meta->forever_id ?? $meta->foreverID ?? null;
        $forever_id = is_scalar($forever_id) ? trim((string) $forever_id) : '';

        return $forever_id !== '' ? $forever_id : '-';
    }

    private function normalize_source_value(string $source): string {
        $source = mb_strtolower(trim($source));

        if($source === '') {
            return '';
        }

        if(strpos($source, '://') !== false) {
            $parsed_host = parse_url($source, PHP_URL_HOST);
            if(is_string($parsed_host) && $parsed_host !== '') {
                $source = $parsed_host;
            }
        }

        if(strpos($source, '/') !== false) {
            $source = explode('/', $source)[0];
        }

        return preg_replace('/^www\./', '', $source) ?? $source;
    }

    private function is_internal_source(string $source): bool {
        $source = $this->normalize_source_value($source);

        if($source === '') {
            return false;
        }

        $site_host = parse_url(SITE_URL, PHP_URL_HOST);
        $site_host = is_string($site_host) ? $this->normalize_source_value($site_host) : '';

        if($site_host !== '' && ($source === $site_host || str_ends_with($source, '.' . $site_host))) {
            return true;
        }

        return $source === 'forevercard.club' || str_ends_with($source, '.forevercard.club');
    }

    private function normalize_source_label(string $source): string {
        $source = $this->normalize_source_value($source);

        if($source === '' || in_array($source, ['(direct)', 'direct', 'none', '(none)'], true) || $this->is_internal_source($source)) {
            return l('admin_index.biolink_qualified_watch.source.direct_share');
        }

        if($source === 'direct_share') {
            return l('admin_index.biolink_qualified_watch.source.direct_share');
        }

        if($source === 'nfc_card') {
            return l('admin_index.biolink_qualified_watch.source.nfc_card');
        }

        if($source === 'qr') {
            return l('admin_index.biolink_qualified_watch.source.qr');
        }

        if(strpos($source, 'messenger') !== false) {
            return 'messenger';
        }

        if($source === 'fb' || strpos($source, 'facebook') !== false) {
            return 'facebook';
        }

        if($source === 'ig' || strpos($source, 'instagram') !== false) {
            return 'instagram';
        }

        if(strpos($source, 'whatsapp') !== false || $source === 'wa') {
            return 'whatsapp';
        }

        if(strpos($source, 'tiktok') !== false) {
            return 'tiktok';
        }

        if(strpos($source, 'youtube') !== false || $source === 'youtu.be') {
            return 'youtube';
        }

        if(strpos($source, 'telegram') !== false) {
            return 'telegram';
        }

        if(strpos($source, 'viber') !== false) {
            return 'viber';
        }

        if(strpos($source, 'email') !== false || strpos($source, 'mail') !== false) {
            return 'email';
        }

        if(strpos($source, 'google') !== false || $source === 'gclid') {
            return 'google';
        }

        if($source === 'x' || strpos($source, 'twitter') !== false) {
            return 'x';
        }

        if(strpos($source, 'threads') !== false) {
            return 'threads';
        }

        if(strpos($source, 'linkedin') !== false) {
            return 'linkedin';
        }

        if(strpos($source, 'pinterest') !== false) {
            return 'pinterest';
        }

        if(strpos($source, 'reddit') !== false) {
            return 'reddit';
        }

        if(strpos($source, 'snapchat') !== false) {
            return 'snapchat';
        }

        if(strpos($source, 'teams') !== false) {
            return 'teams';
        }

        return $source;
    }

    private function get_source_label(array $click): string {
        $utm_source = trim((string) ($click['utm_source'] ?? ''));
        $referrer_host = trim((string) ($click['referrer_host'] ?? ''));

        foreach([$utm_source, $referrer_host] as $candidate_source) {
            $normalized_source = $this->normalize_source_value($candidate_source);

            if($normalized_source === '' || $this->is_internal_source($normalized_source)) {
                continue;
            }

            return $this->normalize_source_label($normalized_source);
        }

        return l('admin_index.biolink_qualified_watch.source.direct_share');
    }

    private function decode_biolink_block_settings($settings): \stdClass {
        if(is_string($settings)) {
            $settings = json_decode($settings ?? '{}');
        }

        if(is_array($settings)) {
            $settings = (object) $settings;
        }

        if(!$settings instanceof \stdClass) {
            $settings = new \stdClass();
        }

        return $settings;
    }

    private function is_whatsapp_socials_block(\stdClass $settings): bool {
        $socials = $settings->socials ?? null;

        if(is_object($socials)) {
            $socials = (array) $socials;
        }

        if(!is_array($socials)) {
            return false;
        }

        $whatsapp_value = trim((string) ($socials['whatsapp'] ?? ''));

        if($whatsapp_value === '') {
            return false;
        }

        foreach($socials as $social_key => $social_value) {
            if($social_key === 'whatsapp') {
                continue;
            }

            if(trim((string) $social_value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function is_whatsapp_block(string $type, \stdClass $settings): bool {
        if($type === 'custom_html_whatsapp') {
            return true;
        }

        if($type === 'socials') {
            return $this->is_whatsapp_socials_block($settings);
        }

        if($type !== 'link') {
            return false;
        }

        $location_url = trim((string) ($settings->location_url ?? ''));
        if($location_url === '') {
            return false;
        }

        $location_url = mb_strtolower($location_url);

        return str_contains($location_url, 'wa.me') || str_contains($location_url, 'api.whatsapp.com');
    }

    private function calculate_app_signal_score(array $row): int {
        return (int) (
            (int) ($row['app_shop_contacts_period'] ?? 0)
            + (int) ($row['app_whatsapp_contacts_period'] ?? 0)
            + (int) ($row['app_product_clicks_period'] ?? 0)
            + ((int) ($row['app_funnel_registrations_period'] ?? 0) * 2)
        );
    }

    private function get_app_quality_payload(int $signal_score): array {
        $quality_score = $this->clamp_score($signal_score * 4);
        $stage_key = $quality_score >= 70 ? 'strong' : ($quality_score >= 40 ? 'growing' : 'foundation');
        $class_map = [
            'strong' => 'status-success',
            'growing' => 'status-info',
            'foundation' => 'status-warning',
        ];

        return [
            'app_quality_score' => $quality_score,
            'app_quality_stage_key' => $stage_key,
            'app_quality_stage_label' => l('admin_leader_operating_system.app_quality_stage.' . $stage_key),
            'app_quality_stage_class' => $class_map[$stage_key] ?? 'status-dark',
        ];
    }

    private function enrich_rows_with_app_signals(array $rows, string $period_start_datetime, ?string $previous_period_start_datetime = null): array {
        if(empty($rows)) {
            return $rows;
        }

        $row_map = [];
        foreach($rows as $index => $row) {
            $user_id = (int) ($row['user_id'] ?? 0);

            if($user_id <= 0) {
                continue;
            }

            $row_map[$user_id] = $index;
            $rows[$index]['app_shop_contacts_period'] = 0;
            $rows[$index]['app_whatsapp_contacts_period'] = 0;
            $rows[$index]['app_product_clicks_period'] = 0;
            $rows[$index]['app_funnel_registrations_period'] = 0;
            $rows[$index]['previous_app_funnel_registrations_period'] = 0;
            $rows[$index]['app_signal_score'] = 0;
        }

        if(empty($row_map)) {
            return $rows;
        }

        $user_ids_sql = implode(',', array_map('intval', array_keys($row_map)));
        $shop_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $relevant_types = array_unique(array_merge($shop_types, ['link_forever_product', 'lead_funnel', 'custom_html_whatsapp', 'socials', 'link']));
        $relevant_types_sql = "'" . implode("','", array_map(static function($type) {
            return str_replace("'", "\\'", (string) $type);
        }, $relevant_types)) . "'";

        $signal_targets = [];
        $blocks_result = database()->query("SELECT `user_id`, `biolink_block_id`, `type`, `settings`
            FROM `biolinks_blocks`
            WHERE `user_id` IN ({$user_ids_sql})
              AND `type` IN ({$relevant_types_sql})");

        while($row = $blocks_result->fetch_object()) {
            $user_id = (int) ($row->user_id ?? 0);
            $block_id = (int) ($row->biolink_block_id ?? 0);
            $type = (string) ($row->type ?? '');

            if(!$user_id || !$block_id || !isset($row_map[$user_id])) {
                continue;
            }

            $settings = $this->decode_biolink_block_settings($row->settings ?? null);

            if(in_array($type, $shop_types, true)) {
                $signal_targets[$block_id]['app_shop_contacts_period'] = $user_id;
            }

            if($type === 'link_forever_product') {
                $signal_targets[$block_id]['app_product_clicks_period'] = $user_id;
            }

            if($type === 'lead_funnel') {
                $signal_targets[$block_id]['app_funnel_registrations_period'] = $user_id;
            }

            if($this->is_whatsapp_block($type, $settings)) {
                $signal_targets[$block_id]['app_whatsapp_contacts_period'] = $user_id;
            }
        }

        if(!empty($signal_targets)) {
            $block_ids_sql = implode(',', array_map('intval', array_keys($signal_targets)));
            $track_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                FROM `track_links`
                WHERE `datetime` >= '{$period_start_datetime}'
                  AND `is_unique` = 1
                  AND `biolink_block_id` IN ({$block_ids_sql})
                GROUP BY `biolink_block_id`");

            while($track_row = $track_result->fetch_object()) {
                $block_id = (int) ($track_row->biolink_block_id ?? 0);
                $total = (int) ($track_row->total ?? 0);

                foreach(($signal_targets[$block_id] ?? []) as $signal_key => $user_id) {
                    $row_index = $row_map[(int) $user_id] ?? null;

                    if($row_index === null) {
                        continue;
                    }

                    $rows[$row_index][$signal_key] += $total;
                }
            }

            $funnel_block_ids = [];
            foreach($signal_targets as $block_id => $target_map) {
                if(isset($target_map['app_funnel_registrations_period'])) {
                    $funnel_block_ids[] = (int) $block_id;
                }
            }

            if(!empty($funnel_block_ids)) {
                $funnel_block_ids_sql = implode(',', array_map('intval', $funnel_block_ids));
                $funnel_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                    FROM `data`
                    WHERE `type` = 'lead_funnel'
                      AND `datetime` >= '{$period_start_datetime}'
                      AND `biolink_block_id` IN ({$funnel_block_ids_sql})
                    GROUP BY `biolink_block_id`");

                while($funnel_row = $funnel_result->fetch_object()) {
                    $block_id = (int) ($funnel_row->biolink_block_id ?? 0);
                    $total = (int) ($funnel_row->total ?? 0);
                    $user_id = (int) (($signal_targets[$block_id]['app_funnel_registrations_period'] ?? 0));
                    $row_index = $row_map[$user_id] ?? null;

                    if($row_index === null) {
                        continue;
                    }

                    $rows[$row_index]['app_funnel_registrations_period'] += $total;
                }

                if($previous_period_start_datetime !== null) {
                    $previous_funnel_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                    FROM `data`
                    WHERE `type` = 'lead_funnel'
                      AND `datetime` >= '{$previous_period_start_datetime}'
                      AND `datetime` < '{$period_start_datetime}'
                      AND `biolink_block_id` IN ({$funnel_block_ids_sql})
                    GROUP BY `biolink_block_id`");

                    while($previous_funnel_row = $previous_funnel_result->fetch_object()) {
                        $block_id = (int) ($previous_funnel_row->biolink_block_id ?? 0);
                        $total = (int) ($previous_funnel_row->total ?? 0);
                        $user_id = (int) (($signal_targets[$block_id]['app_funnel_registrations_period'] ?? 0));
                        $row_index = $row_map[$user_id] ?? null;

                        if($row_index === null) {
                            continue;
                        }

                        $rows[$row_index]['previous_app_funnel_registrations_period'] += $total;
                    }
                }
            }
        }

        foreach($rows as $index => $row) {
            $signal_score = $this->calculate_app_signal_score($row);
            $rows[$index]['app_signal_score'] = $signal_score;
            $rows[$index] = array_merge($rows[$index], $this->get_app_quality_payload($signal_score));
        }

        return $rows;
    }

    private function enrich_rows_with_context(array $rows, string $period_start_datetime, array $biolink_sets): array {
        if(empty($rows)) {
            return $rows;
        }

        $user_ids_sql = implode(',', array_map(static fn($row) => (int) $row['user_id'], $rows));
        $top_context = [];
        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);
        $context_result = database()->query("SELECT
            `track_links`.`user_id`,
            `track_links`.`country_code`,
            `track_links`.`utm_source`,
            `track_links`.`utm_medium`,
            `track_links`.`utm_campaign`,
            `track_links`.`referrer_host`
        FROM `track_links`
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `track_links`.`is_unique` = 1
          AND `track_links`.`user_id` IN ({$user_ids_sql})
                    AND {$outbound_condition}");

        while($context_row = $context_result->fetch_assoc()) {
            $user_id = (int) ($context_row['user_id'] ?? 0);
            if(!isset($top_context[$user_id])) {
                $top_context[$user_id] = [
                    'countries' => [],
                    'sources' => [],
                ];
            }

            $country_code = strtoupper(trim((string) ($context_row['country_code'] ?? '')));
            if($country_code !== '') {
                $top_context[$user_id]['countries'][$country_code] = ($top_context[$user_id]['countries'][$country_code] ?? 0) + 1;
            }

            $source_label = $this->get_source_label($context_row);
            $top_context[$user_id]['sources'][$source_label] = ($top_context[$user_id]['sources'][$source_label] ?? 0) + 1;
        }

        foreach($rows as &$row) {
            $context = $top_context[(int) $row['user_id']] ?? ['countries' => [], 'sources' => []];
            arsort($context['countries']);
            arsort($context['sources']);
            $row['strongest_country'] = !empty($context['countries']) ? (string) array_key_first($context['countries']) : '-';
            $row['strongest_country_count'] = !empty($context['countries']) ? (int) reset($context['countries']) : 0;
            $row['top_source_label'] = !empty($context['sources']) ? (string) array_key_first($context['sources']) : l('admin_index.biolink_qualified_watch.source.direct_share');
            $row['top_source_count'] = !empty($context['sources']) ? (int) reset($context['sources']) : 0;
        }
        unset($row);

        return $rows;
    }

    private function clamp_score(float $value): int {
        return (int) max(0, min(100, round($value)));
    }

    private function get_scores(array $row): array {
        $total_clicks = (int) ($row['clicks_total_period'] ?? 0);
        $shop_clicks = (int) ($row['forever_shop_clicks_period'] ?? 0);
        $registration_clicks = (int) ($row['forever_registration_clicks_period'] ?? 0);
        $app_signal_score = (int) ($row['app_signal_score'] ?? 0);
        $whatsapp_contacts = (int) ($row['app_whatsapp_contacts_period'] ?? 0);
        $product_clicks = (int) ($row['app_product_clicks_period'] ?? 0);
        $funnel_registrations = (int) ($row['app_funnel_registrations_period'] ?? 0);
        $growth = $row['growth_percent'];
        $shop_share = $total_clicks > 0 ? (($shop_clicks / $total_clicks) * 100) : 0;
        $registration_rate = $shop_clicks > 0 ? (($registration_clicks / $shop_clicks) * 100) : 0;

        $performance_score = $this->clamp_score(min(34, $shop_clicks * 2.1) + min(22, $registration_clicks * 6) + min(20, $app_signal_score * 1.15) + min(24, $total_clicks * 0.38));
        $momentum_score = $this->clamp_score($growth === null ? ($shop_clicks > 0 ? 58 : 0) : 50 + ($growth * 1.1));
        $conversion_score = $this->clamp_score(($shop_share * 0.55) + ($registration_rate * 0.9));

        $risk_score = 0;
        if($growth !== null && $growth <= -20) {
            $risk_score += 35;
        }
        if((int) ($row['previous_forever_shop_clicks_period'] ?? 0) > 0 && $shop_clicks === 0) {
            $risk_score += 35;
        }
        if($total_clicks === 0 && (int) ($row['forever_shop_clicks_90d'] ?? 0) > 0) {
            $risk_score += 20;
        }
        $risk_score = $this->clamp_score($risk_score);

        $opportunity_score = 0;
        if($total_clicks >= 20 && $shop_share < 25) {
            $opportunity_score += 35;
        }
        if($shop_clicks >= 10 && $registration_clicks === 0) {
            $opportunity_score += 20;
        }
        if($growth !== null && $growth > 0 && $registration_rate < 10) {
            $opportunity_score += 15;
        }
        if(($whatsapp_contacts + $product_clicks + ($funnel_registrations * 2)) >= 10 && $shop_clicks < 5) {
            $opportunity_score += 18;
        }
        $opportunity_score = $this->clamp_score($opportunity_score);

        $leader_os_score = $this->clamp_score(
            ($performance_score * 0.35)
            + ($momentum_score * 0.2)
            + ($conversion_score * 0.2)
            + ((100 - $risk_score) * 0.1)
            + ($opportunity_score * 0.15)
        );

        return [
            'performance_score' => $performance_score,
            'momentum_score' => $momentum_score,
            'conversion_score' => $conversion_score,
            'risk_score' => $risk_score,
            'opportunity_score' => $opportunity_score,
            'leader_os_score' => $leader_os_score,
            'shop_share_percent' => round($shop_share, 1),
            'registration_rate_percent' => round($registration_rate, 1),
        ];
    }

    private function get_status_payload(array $row): array {
        $qualified = (int) ($row['forever_shop_clicks_90d'] ?? 0) >= 15;
        $growth = $row['growth_percent'] ?? null;
        $current_shop_clicks = (int) ($row['forever_shop_clicks_period'] ?? 0);
        $previous_shop_clicks = (int) ($row['previous_forever_shop_clicks_period'] ?? 0);
        $total_clicks = (int) ($row['clicks_total_period'] ?? 0);
        $opportunity_score = (int) ($row['opportunity_score'] ?? 0);
        $risk_score = (int) ($row['risk_score'] ?? 0);

        $status_key = 'stable';
        $status_label = l('admin_leader_operating_system.status.stable');
        $status_class = 'secondary';

        if($total_clicks === 0 && (int) ($row['forever_shop_clicks_90d'] ?? 0) === 0) {
            $status_key = 'inactive';
            $status_label = l('admin_leader_operating_system.status.inactive');
            $status_class = 'dark';
        } elseif($risk_score >= 55 || ($previous_shop_clicks > 0 && $current_shop_clicks === 0) || ($growth !== null && $growth <= -20)) {
            $status_key = 'risk';
            $status_label = l('admin_leader_operating_system.status.risk');
            $status_class = 'warning';
        } elseif($opportunity_score >= 60 && $total_clicks >= 20) {
            $status_key = 'high_potential';
            $status_label = l('admin_leader_operating_system.status.high_potential');
            $status_class = 'info';
        } elseif(($growth !== null && $growth >= 20) || $current_shop_clicks >= 12) {
            $status_key = 'rising';
            $status_label = l('admin_leader_operating_system.status.rising');
            $status_class = 'success';
        }

        return [
            'qualified' => $qualified,
            'status_key' => $status_key,
            'status_label' => $status_label,
            'status_class' => $status_class,
        ];
    }

    private function get_ai_plan_overview_context($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $profile = $preferences->leader_ai_profile ?? null;
        $checkins = $preferences->leader_ai_weekly_checkins ?? [];
        $outcomes = $preferences->leader_ai_weekly_outcomes ?? [];
        $plans = $preferences->leader_ai_weekly_plans ?? [];
        $mentor_actions = $preferences->leader_ai_admin_coaching ?? null;
        $mentor_history = $this->get_ai_plan_mentor_history($preferences);

        if(is_object($profile)) {
            $profile = (array) $profile;
        }

        if(is_object($checkins)) {
            $checkins = (array) $checkins;
        }

        if(is_object($outcomes)) {
            $outcomes = (array) $outcomes;
        }

        if(is_object($plans)) {
            $plans = (array) $plans;
        }

        if(is_object($mentor_actions)) {
            $mentor_actions = (array) $mentor_actions;
        }

        $latest_checkin = [];
        if(is_array($checkins) && !empty($checkins[0])) {
            $latest_checkin = is_object($checkins[0]) ? (array) $checkins[0] : (array) $checkins[0];
        }

        $latest_outcome = [];
        if(is_array($outcomes) && !empty($outcomes[0])) {
            $latest_outcome = is_object($outcomes[0]) ? (array) $outcomes[0] : (array) $outcomes[0];
        }

        $latest_plan = [];
        if(is_array($plans) && !empty($plans[0])) {
            $latest_plan = is_object($plans[0]) ? (array) $plans[0] : (array) $plans[0];
        }

        $allowed_statuses = ['pending_contact', 'in_progress', 'monitoring', 'resolved'];
        $mentor_status = trim((string) ($mentor_actions['status'] ?? 'pending_contact'));

        if(!in_array($mentor_status, $allowed_statuses, true)) {
            $mentor_status = 'pending_contact';
        }

        $days_since_last_checkin = null;
        if(!empty($latest_checkin['submitted_at'])) {
            try {
                $latest_date = new \DateTimeImmutable((string) $latest_checkin['submitted_at']);
                $days_since_last_checkin = (int) $latest_date->diff(new \DateTimeImmutable())->format('%a');
            } catch(\Throwable $exception) {
                $days_since_last_checkin = null;
            }
        }

        $days_since_last_contact = null;
        if(!empty($mentor_actions['last_contacted_at'])) {
            try {
                $last_contact_date = new \DateTimeImmutable((string) $mentor_actions['last_contacted_at']);
                $days_since_last_contact = (int) $last_contact_date->diff(new \DateTimeImmutable())->format('%a');
            } catch(\Throwable $exception) {
                $days_since_last_contact = null;
            }
        }

        $latest_mentor_event = $mentor_history[0] ?? [];

        $overview_context = [
            'has_profile' => !empty($profile['primary_goal']),
            'has_checkin' => !empty($latest_checkin),
            'has_plan' => !empty($latest_plan),
            'latest_checkin_at' => $latest_checkin['submitted_at'] ?? null,
            'days_since_last_checkin' => $days_since_last_checkin,
            'latest_outcome_completion_level' => (string) ($latest_outcome['completion_level'] ?? ''),
            'needs_follow_up' => (bool) ($mentor_actions['needs_follow_up'] ?? false),
            'mentored_this_week' => (bool) ($mentor_actions['mentored_this_week'] ?? false),
            'mentor_status' => $mentor_status,
            'has_ai_guidance' => trim((string) ($mentor_actions['ai_guidance'] ?? '')) !== '',
            'mentor_next_action' => trim((string) ($mentor_actions['next_action'] ?? '')),
            'last_contacted_at' => $mentor_actions['last_contacted_at'] ?? null,
            'days_since_last_contact' => $days_since_last_contact,
            'mentor_history_total' => count($mentor_history),
            'latest_mentor_event_summary' => trim((string) ($latest_mentor_event['summary'] ?? '')),
            'latest_mentor_event_at' => $latest_mentor_event['created_at'] ?? null,
            'latest_mentor_event_admin' => trim((string) ($latest_mentor_event['admin_name'] ?? '')),
        ];

        /* Custom code: FC-2026-03-31: LOS overview AI usage payload */
        return array_merge($overview_context, $this->get_ai_usage_payload($overview_context));
        /* /Custom code: FC-2026-03-31 */
    }

    private function get_ai_plan_mentor_history($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $history = $preferences->leader_ai_admin_history ?? [];

        if(is_object($history)) {
            $history = (array) $history;
        }

        if(!is_array($history)) {
            return [];
        }

        $normalized = [];

        foreach($history as $history_item) {
            if(is_object($history_item)) {
                $history_item = (array) $history_item;
            }

            if(!is_array($history_item)) {
                continue;
            }

            $summary = trim((string) ($history_item['summary'] ?? ''));

            if($summary === '') {
                continue;
            }

            $normalized[] = [
                'summary' => $summary,
                'created_at' => $history_item['created_at'] ?? null,
                'admin_name' => trim((string) ($history_item['admin_name'] ?? '')),
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $normalized;
    }

    /* Custom code: FC-2026-03-31: LOS overview AI usage payload */
    private function get_ai_usage_payload(array $overview_context): array {
        $stage_key = 'inactive';
        $stage_class = 'status-dark';

        if(!empty($overview_context['has_plan'])) {
            $stage_key = 'active';
            $stage_class = 'status-success';
        } elseif(!empty($overview_context['has_checkin'])) {
            $stage_key = 'questionnaire';
            $stage_class = 'status-info';
        } elseif(!empty($overview_context['has_profile'])) {
            $stage_key = 'started';
            $stage_class = 'status-warning';
        }

        $badges = [];

        if(!empty($overview_context['has_profile'])) {
            $badges[] = [
                'label' => l('admin_leader_operating_system.ai_usage.badge_profile'),
                'class' => 'status-warning',
            ];
        }

        if(!empty($overview_context['has_checkin'])) {
            $badges[] = [
                'label' => l('admin_leader_operating_system.ai_usage.badge_checkin'),
                'class' => 'status-info',
            ];
        }

        if(!empty($overview_context['has_plan'])) {
            $badges[] = [
                'label' => l('admin_leader_operating_system.ai_usage.badge_plan'),
                'class' => 'status-success',
            ];
        }

        if(!empty($overview_context['has_ai_guidance'])) {
            $badges[] = [
                'label' => l('admin_leader_operating_system.ai_usage.badge_guidance'),
                'class' => 'status-info',
            ];
        }

        return [
            'ai_usage_stage_key' => $stage_key,
            'ai_usage_stage_label' => l('admin_leader_operating_system.ai_usage.' . $stage_key),
            'ai_usage_stage_class' => $stage_class,
            'ai_usage_badges' => $badges,
        ];
    }
    /* /Custom code: FC-2026-03-31 */

    private function get_queue_priority_payload(array $candidate): array {
        $score = 0;
        $reason = l('admin_leader_operating_system.queue_reason_monitor');
        $anomaly_score = (int) ($candidate['anomaly_score'] ?? 0);

        if(!empty($candidate['needs_follow_up']) && (($candidate['days_since_last_contact'] ?? null) === null || (int) ($candidate['days_since_last_contact'] ?? 0) >= 7)) {
            $score = 110;
            $reason = l('admin_leader_operating_system.queue_reason_follow_up_stale');
        } elseif(!empty($candidate['needs_follow_up'])) {
            $score = 100;
            $reason = l('admin_leader_operating_system.queue_reason_follow_up');
        } elseif($anomaly_score >= 55) {
            $score = 90;
            $reason = l('admin_leader_operating_system.queue_reason_anomaly_high');
        } elseif(!$candidate['has_profile']) {
            $score = 85;
            $reason = l('admin_leader_operating_system.queue_reason_waiting_profile');
        } elseif(!$candidate['has_checkin']) {
            $score = 75;
            $reason = l('admin_leader_operating_system.queue_reason_waiting_checkin');
        } elseif(($candidate['risk_score'] ?? 0) >= 55 || ($candidate['status_key'] ?? '') === 'risk') {
            $score = 70;
            $reason = l('admin_leader_operating_system.queue_reason_risk');
        } elseif($anomaly_score >= 25) {
            $score = 62;
            $reason = l('admin_leader_operating_system.queue_reason_anomaly_watch');
        } elseif(empty($candidate['mentored_this_week']) && ($candidate['leader_os_score'] ?? 0) < 55) {
            $score = 55;
            $reason = l('admin_leader_operating_system.queue_reason_no_mentor_touch');
        } elseif(($candidate['mentor_status'] ?? '') === 'in_progress') {
            $score = 40;
            $reason = l('admin_leader_operating_system.queue_reason_in_progress');
        }

        return [
            'queue_priority_score' => $score,
            'queue_reason' => $reason,
        ];
    }

    private function get_combined_priority_payload(array $candidate): array {
        $base_score = (int) ($candidate['queue_priority_score'] ?? 0);
        $base_reason = (string) ($candidate['queue_reason'] ?? l('admin_leader_operating_system.queue_reason_monitor'));
        $blocked_attempts_total = (int) ($candidate['blocked_attempts_total'] ?? 0);
        $fraud_bonus = 0;
        $fraud_reason = '';

        if($blocked_attempts_total >= 5) {
            $fraud_bonus = 28;
            $fraud_reason = 'Fraud high signal';
        } elseif($blocked_attempts_total > 0) {
            $fraud_bonus = min(18, $blocked_attempts_total * 4);
            $fraud_reason = 'Fraud watch signal';
        }

        return [
            'combined_priority_score' => $base_score + $fraud_bonus,
            'combined_priority_reason' => $fraud_reason !== '' ? ($base_reason . ' · ' . $fraud_reason) : $base_reason,
        ];
    }

    private function get_alert_entries(array $candidate): array {
        $alerts = [];

        if(!empty($candidate['needs_follow_up'])) {
            $alerts[] = [
                'type' => 'manual_follow_up',
                'label' => l('admin_leader_operating_system.alert.manual_follow_up'),
            ];
        }

        if(!empty($candidate['needs_follow_up']) && (($candidate['days_since_last_contact'] ?? null) === null || (int) ($candidate['days_since_last_contact'] ?? 0) >= 7)) {
            $alerts[] = [
                'type' => 'stale_follow_up',
                'label' => l('admin_leader_operating_system.alert.stale_follow_up'),
            ];
        }

        if(!empty($candidate['has_profile']) && empty($candidate['has_checkin'])) {
            $alerts[] = [
                'type' => 'missing_weekly',
                'label' => l('admin_leader_operating_system.alert.missing_weekly'),
            ];
        }

        if(($candidate['days_since_last_checkin'] ?? null) !== null && (int) $candidate['days_since_last_checkin'] >= 14) {
            $alerts[] = [
                'type' => 'stale_weekly',
                'label' => l('admin_leader_operating_system.alert.stale_weekly'),
            ];
        }

        if(in_array((string) ($candidate['latest_outcome_completion_level'] ?? ''), ['low_execution', 'not_started'], true)) {
            $alerts[] = [
                'type' => 'weak_execution',
                'label' => l('admin_leader_operating_system.alert.weak_execution'),
            ];
        }

        if(($candidate['status_key'] ?? '') === 'risk') {
            $alerts[] = [
                'type' => 'analytics_risk',
                'label' => l('admin_leader_operating_system.alert.analytics_risk'),
            ];
        }

        return $alerts;
    }

    private function get_blog_cta_mediums(): array {
        return [
            'product' => \Altum\Link::get_blog_cta_tracking_medium('product'),
            'business' => \Altum\Link::get_blog_cta_tracking_medium('business'),
        ];
    }

    private function increment_count_bucket(array &$buckets, string $key): void {
        $key = trim($key);

        if($key === '') {
            return;
        }

        $buckets[$key] = ($buckets[$key] ?? 0) + 1;
    }

    private function sort_count_buckets(array $buckets, int $limit = 5): array {
        arsort($buckets);
        $result = [];

        foreach(array_slice($buckets, 0, $limit, true) as $label => $total) {
            $result[] = [
                'label' => (string) $label,
                'total' => (int) $total,
            ];
        }

        return $result;
    }

    private function is_ai_text_stopword(string $term): bool {
        static $stopwords = [
            'a', 'about', 'after', 'again', 'ai', 'ako', 'ali', 'an', 'and', 'are', 'as', 'at',
            'ba', 'bas', 'be', 'because', 'been', 'bi', 'bih', 'bila', 'bili', 'bilo', 'bio',
            'blog', 'blok', 'bude', 'budem', 'budemo', 'budes', 'business', 'by',
            'ce', 'ces', 'da', 'danas', 'do', 'dok', 'doing', 'done',
            'email', 'for', 'from', 'goal', 'goals',
            'i', 'ili', 'in', 'is', 'it', 'its', 'iz',
            'ja', 'je', 'jedan', 'jedna', 'jedno', 'jer', 'jos', 'kad', 'kada', 'kao',
            'koja', 'koje', 'koji', 'kojim', 'koju', 'kroz',
            'li', 'link', 'los',
            'me', 'mi', 'moj', 'moja', 'moje', 'mozes',
            'na', 'nad', 'nakon', 'nam', 'napraviti', 'ne', 'nego', 'nema', 'new', 'ni', 'nije', 'nisam', 'no',
            'od', 'oko', 'on', 'ona', 'oni', 'ono', 'or', 'ovaj', 'ovdje', 'ovo',
            'pa', 'plan', 'plus', 'po', 'pod', 'post', 'prema', 'prije', 'problem', 'profile', 'project',
            'rad', 'rada', 'radim',
            'sa', 'sam', 'se', 'shop', 'smo', 'so', 'sta', 'sada', 'su', 'suradnik', 'suradnici', 'sve', 'svi',
            'ta', 'taj', 'takoder', 'te', 'team', 'that', 'the', 'their', 'them', 'this', 'ti', 'to', 'treba', 'trebam', 'trebamo',
            'u', 'uz',
            'vrlo',
            'was', 'we', 'what', 'when', 'where', 'which', 'while', 'with',
            'za', 'zbog'
        ];

        return in_array($term, $stopwords, true);
    }

    private function normalize_ai_text(string $text): string {
        $text = trim(mb_strtolower($text, 'UTF-8'));
        $text = preg_replace('/https?:\/\/\S+/u', ' ', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    private function extract_ai_text_terms(string $text): array {
        $normalized = $this->normalize_ai_text($text);

        if($normalized === '') {
            return [];
        }

        $terms = preg_split('/\s+/u', $normalized) ?: [];
        $result = [];

        foreach($terms as $term) {
            $term = trim($term);

            if($term === '' || mb_strlen($term, 'UTF-8') < 3 || is_numeric($term) || $this->is_ai_text_stopword($term)) {
                continue;
            }

            $result[] = $term;
        }

        return $result;
    }

    private function append_ai_text_terms(array &$buckets, string $text): void {
        foreach($this->extract_ai_text_terms($text) as $term) {
            $this->increment_count_bucket($buckets, $term);
        }
    }

    private function translate_ai_plan_option(string $group, $value): string {
        if(is_array($value)) {
            $labels = [];

            foreach($value as $item) {
                $label = $this->translate_ai_plan_option($group, $item);

                if($label !== '-') {
                    $labels[] = $label;
                }
            }

            return !empty($labels) ? implode(', ', $labels) : '-';
        }

        if(!is_scalar($value)) {
            return '-';
        }

        $value = trim((string) $value);

        if($value === '') {
            return '-';
        }

        $key = 'ai_plan.option.' . $group . '.' . $value;
        $label = l($key);

        if($label === $key) {
            return ucfirst(str_replace('_', ' ', $value));
        }

        return $label;
    }

    private function get_team_blog_forever_payload(string $period_start_datetime): array {
        $mediums = $this->get_blog_cta_mediums();
        $product_medium = db()->escape($mediums['product']);
        $business_medium = db()->escape($mediums['business']);

        $payload = [
            'total_clicks' => 0,
            'product_clicks' => 0,
            'business_clicks' => 0,
            'active_collaborators' => 0,
            'top_collaborators' => [],
            'top_articles' => [],
        ];

        $summary = database()->query("SELECT
            COUNT(*) AS `total_clicks`,
            SUM(CASE WHEN `track_links`.`utm_medium` = '{$product_medium}' THEN 1 ELSE 0 END) AS `product_clicks`,
            SUM(CASE WHEN `track_links`.`utm_medium` = '{$business_medium}' THEN 1 ELSE 0 END) AS `business_clicks`,
            COUNT(DISTINCT `track_links`.`user_id`) AS `active_collaborators`
        FROM `track_links`
        LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
        WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `users`.`type` = 0
          AND `track_links`.`utm_medium` IN ('{$product_medium}', '{$business_medium}')")->fetch_object();

        if($summary) {
            $payload['total_clicks'] = (int) ($summary->total_clicks ?? 0);
            $payload['product_clicks'] = (int) ($summary->product_clicks ?? 0);
            $payload['business_clicks'] = (int) ($summary->business_clicks ?? 0);
            $payload['active_collaborators'] = (int) ($summary->active_collaborators ?? 0);
        }

        $top_collaborators_result = database()->query("SELECT
            `track_links`.`user_id`,
            `users`.`name`,
            COUNT(*) AS `total`
        FROM `track_links`
        LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
        WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `users`.`type` = 0
          AND `track_links`.`utm_medium` IN ('{$product_medium}', '{$business_medium}')
        GROUP BY `track_links`.`user_id`, `users`.`name`
        ORDER BY `total` DESC, `track_links`.`user_id` DESC
        LIMIT 5");

        while($row = $top_collaborators_result->fetch_object()) {
            $payload['top_collaborators'][] = [
                'user_id' => (int) ($row->user_id ?? 0),
                'name' => (string) ($row->name ?? l('global.unknown')),
                'total' => (int) ($row->total ?? 0),
                'detail_url' => url('admin/leader-operating-system-leader?user_id=' . (int) ($row->user_id ?? 0) . '&period=30d'),
            ];
        }

        $top_articles_result = database()->query("SELECT
            CAST(SUBSTRING_INDEX(`track_links`.`utm_campaign`, ':', -1) AS UNSIGNED) AS `blog_post_id`,
            `blog_posts`.`title`,
            `blog_posts`.`url`,
            COUNT(*) AS `total`
        FROM `track_links`
        LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
        LEFT JOIN `blog_posts` ON `blog_posts`.`blog_post_id` = CAST(SUBSTRING_INDEX(`track_links`.`utm_campaign`, ':', -1) AS UNSIGNED)
        WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `users`.`type` = 0
          AND `track_links`.`utm_medium` IN ('{$product_medium}', '{$business_medium}')
        GROUP BY `blog_post_id`, `blog_posts`.`title`, `blog_posts`.`url`
        ORDER BY `total` DESC, `blog_post_id` DESC
        LIMIT 5");

        while($row = $top_articles_result->fetch_object()) {
            $payload['top_articles'][] = [
                'blog_post_id' => (int) ($row->blog_post_id ?? 0),
                'title' => !empty($row->title) ? (string) $row->title : 'Blog članak #' . (int) ($row->blog_post_id ?? 0),
                'url' => (string) ($row->url ?? ''),
                'total' => (int) ($row->total ?? 0),
            ];
        }

        return $payload;
    }

    private function get_team_ai_habits_payload(array $rows): array {
        $payload = [
            'profiles_total' => 0,
            'checkins_total' => 0,
            'plans_total' => 0,
            'outcomes_total' => 0,
            'mentored_this_week_total' => 0,
            'top_goals' => [],
            'top_blockers' => [],
            'top_ai_needs' => [],
            'top_weekly_energy' => [],
            'top_completion_levels' => [],
        ];

        $goal_buckets = [];
        $blocker_buckets = [];
        $ai_need_buckets = [];
        $energy_buckets = [];
        $completion_buckets = [];

        foreach($rows as $row) {
            $preferences = $this->get_preferences_object($row['preferences'] ?? null);
            $profile = $preferences->leader_ai_profile ?? null;
            $checkins = $preferences->leader_ai_weekly_checkins ?? [];
            $plans = $preferences->leader_ai_weekly_plans ?? [];
            $outcomes = $preferences->leader_ai_weekly_outcomes ?? [];
            $coaching = $preferences->leader_ai_admin_coaching ?? null;

            if(is_array($profile) || is_object($profile)) {
                $profile = (array) $profile;
            } else {
                $profile = [];
            }

            if(is_object($checkins)) $checkins = (array) $checkins;
            if(is_object($plans)) $plans = (array) $plans;
            if(is_object($outcomes)) $outcomes = (array) $outcomes;
            if(is_array($coaching) || is_object($coaching)) {
                $coaching = (array) $coaching;
            } else {
                $coaching = [];
            }

            if(!empty($profile['primary_goal'])) {
                $payload['profiles_total']++;
                $this->increment_count_bucket($goal_buckets, $this->translate_ai_plan_option('primary_goal', $profile['primary_goal']));
            }

            if(!empty($profile['biggest_blocker'])) {
                $this->increment_count_bucket($blocker_buckets, $this->translate_ai_plan_option('biggest_blocker', $profile['biggest_blocker']));
            }

            if(!empty($checkins[0])) {
                $payload['checkins_total']++;
                $latest_checkin = is_object($checkins[0]) ? (array) $checkins[0] : (array) $checkins[0];

                if(!empty($latest_checkin['ai_need'])) {
                    $this->increment_count_bucket($ai_need_buckets, $this->translate_ai_plan_option('ai_need', $latest_checkin['ai_need']));
                }

                if(!empty($latest_checkin['weekly_energy'])) {
                    $this->increment_count_bucket($energy_buckets, $this->translate_ai_plan_option('weekly_energy', $latest_checkin['weekly_energy']));
                }
            }

            if(!empty($plans[0])) {
                $payload['plans_total']++;
            }

            if(!empty($outcomes[0])) {
                $payload['outcomes_total']++;
                $latest_outcome = is_object($outcomes[0]) ? (array) $outcomes[0] : (array) $outcomes[0];

                if(!empty($latest_outcome['completion_level'])) {
                    $this->increment_count_bucket($completion_buckets, ucfirst(str_replace('_', ' ', (string) $latest_outcome['completion_level'])));
                }
            }

            if(!empty($coaching['mentored_this_week'])) {
                $payload['mentored_this_week_total']++;
            }
        }

        $payload['top_goals'] = $this->sort_count_buckets($goal_buckets, 3);
        $payload['top_blockers'] = $this->sort_count_buckets($blocker_buckets, 3);
        $payload['top_ai_needs'] = $this->sort_count_buckets($ai_need_buckets, 3);
        $payload['top_weekly_energy'] = $this->sort_count_buckets($energy_buckets, 3);
        $payload['top_completion_levels'] = $this->sort_count_buckets($completion_buckets, 3);

        return $payload;
    }

    private function get_team_ai_text_intelligence_payload(array $rows): array {
        $payload = [
            'context_total' => 0,
            'adaptive_total' => 0,
            'blocker_total' => 0,
            'lesson_total' => 0,
            'adjustment_total' => 0,
            'response_total' => 0,
            'top_context_terms' => [],
            'top_adaptive_terms' => [],
            'top_blocker_terms' => [],
            'top_lesson_terms' => [],
            'top_adjustment_terms' => [],
            'top_response_terms' => [],
        ];

        $context_buckets = [];
        $adaptive_buckets = [];
        $blocker_buckets = [];
        $lesson_buckets = [];
        $adjustment_buckets = [];
        $response_buckets = [];

        foreach($rows as $row) {
            $preferences = $this->get_preferences_object($row['preferences'] ?? null);
            $checkins = $preferences->leader_ai_weekly_checkins ?? [];
            $outcomes = $preferences->leader_ai_weekly_outcomes ?? [];

            if(is_object($checkins)) $checkins = (array) $checkins;
            if(is_object($outcomes)) $outcomes = (array) $outcomes;

            $latest_checkin = !empty($checkins[0]) ? (array) (is_object($checkins[0]) ? $checkins[0] : $checkins[0]) : [];
            $latest_outcome = !empty($outcomes[0]) ? (array) (is_object($outcomes[0]) ? $outcomes[0] : $outcomes[0]) : [];

            $weekly_context = trim((string) ($latest_checkin['weekly_context'] ?? ''));
            $adaptive_answer = trim((string) ($latest_checkin['adaptive_answer'] ?? ''));
            $main_blocker_now = trim((string) ($latest_outcome['main_blocker_now'] ?? ''));
            $biggest_lesson = trim((string) ($latest_outcome['biggest_lesson'] ?? ''));
            $next_adjustment = trim((string) ($latest_outcome['next_adjustment'] ?? ''));
            $best_response = trim((string) ($latest_outcome['best_response'] ?? ''));

            if($weekly_context !== '') {
                $payload['context_total']++;
                $this->append_ai_text_terms($context_buckets, $weekly_context);
            }

            if($adaptive_answer !== '') {
                $payload['adaptive_total']++;
                $this->append_ai_text_terms($adaptive_buckets, $adaptive_answer);
            }

            if($main_blocker_now !== '') {
                $payload['blocker_total']++;
                $this->append_ai_text_terms($blocker_buckets, $main_blocker_now);
            }

            if($biggest_lesson !== '') {
                $payload['lesson_total']++;
                $this->append_ai_text_terms($lesson_buckets, $biggest_lesson);
            }

            if($next_adjustment !== '') {
                $payload['adjustment_total']++;
                $this->append_ai_text_terms($adjustment_buckets, $next_adjustment);
            }

            if($best_response !== '') {
                $payload['response_total']++;
                $this->append_ai_text_terms($response_buckets, $best_response);
            }
        }

        $payload['top_context_terms'] = $this->sort_count_buckets($context_buckets, 5);
        $payload['top_adaptive_terms'] = $this->sort_count_buckets($adaptive_buckets, 5);
        $payload['top_blocker_terms'] = $this->sort_count_buckets($blocker_buckets, 5);
        $payload['top_lesson_terms'] = $this->sort_count_buckets($lesson_buckets, 5);
        $payload['top_adjustment_terms'] = $this->sort_count_buckets($adjustment_buckets, 5);
        $payload['top_response_terms'] = $this->sort_count_buckets($response_buckets, 5);

        return $payload;
    }

    private function get_team_analytics_payload(string $period_start_datetime, array $biolink_sets): array {
        $payload = [
            'top_countries' => [],
            'top_cities' => [],
            'top_sources' => [],
            'top_languages' => [],
            'top_devices' => [],
            'top_browsers' => [],
            'top_hours' => [],
            'blog_top_countries' => [],
            'blog_top_sources' => [],
        ];

        $country_buckets = [];
        $city_buckets = [];
        $source_buckets = [];
        $language_buckets = [];
        $device_buckets = [];
        $browser_buckets = [];
        $hour_buckets = [];
        $blog_country_buckets = [];
        $blog_source_buckets = [];
        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);
        $blog_mediums = $this->get_blog_cta_mediums();

        $result = database()->query("SELECT
            `track_links`.`country_code`,
            `track_links`.`city_name`,
            `track_links`.`utm_source`,
            `track_links`.`referrer_host`,
            `track_links`.`browser_language`,
            `track_links`.`browser_name`,
            `track_links`.`device_type`,
            `track_links`.`utm_medium`,
            `track_links`.`datetime`
        FROM `track_links`
        LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `track_links`.`is_unique` = 1
          AND `users`.`type` = 0
          AND {$outbound_condition}");

        while($row = $result->fetch_assoc()) {
            $country_code = strtoupper(trim((string) ($row['country_code'] ?? '')));
            $city_name = trim((string) ($row['city_name'] ?? ''));
            $language = trim((string) ($row['browser_language'] ?? ''));
            $browser = trim((string) ($row['browser_name'] ?? ''));
            $device = trim((string) ($row['device_type'] ?? ''));
            if($country_code !== '') {
                $this->increment_count_bucket($country_buckets, $country_code);
            }
            if($city_name !== '') {
                $this->increment_count_bucket($city_buckets, $city_name);
            }
            if($language !== '') {
                $this->increment_count_bucket($language_buckets, $language);
            }
            if($browser !== '') {
                $this->increment_count_bucket($browser_buckets, $browser);
            }
            if($device !== '') {
                $this->increment_count_bucket($device_buckets, $device);
            }

            $source_label = $this->get_source_label($row);
            $this->increment_count_bucket($source_buckets, $source_label);

            $hour_label = substr((string) ($row['datetime'] ?? ''), 11, 2);
            if($hour_label !== '') {
                $this->increment_count_bucket($hour_buckets, $hour_label . ':00');
            }

            $utm_medium = (string) ($row['utm_medium'] ?? '');
            if(in_array($utm_medium, [$blog_mediums['product'], $blog_mediums['business']], true)) {
                if($country_code !== '') {
                    $this->increment_count_bucket($blog_country_buckets, $country_code);
                }
                $this->increment_count_bucket($blog_source_buckets, $source_label);
            }
        }

        $payload['top_countries'] = $this->sort_count_buckets($country_buckets, 8);
        $payload['top_cities'] = $this->sort_count_buckets($city_buckets, 8);
        $payload['top_sources'] = $this->sort_count_buckets($source_buckets, 8);
        $payload['top_languages'] = $this->sort_count_buckets($language_buckets, 8);
        $payload['top_devices'] = $this->sort_count_buckets($device_buckets, 8);
        $payload['top_browsers'] = $this->sort_count_buckets($browser_buckets, 8);
        $payload['top_hours'] = $this->sort_count_buckets($hour_buckets, 8);
        $payload['blog_top_countries'] = $this->sort_count_buckets($blog_country_buckets, 5);
        $payload['blog_top_sources'] = $this->sort_count_buckets($blog_source_buckets, 5);

        return $payload;
    }

    private function get_consistency_payload(array $candidate): array {
        $score = 0;

        $active_days_total = (int) ($candidate['active_days_total'] ?? 0);
        $has_profile = !empty($candidate['has_profile']);
        $has_checkin = !empty($candidate['has_checkin']);
        $has_plan = !empty($candidate['has_plan']);
        $completion_level = (string) ($candidate['latest_outcome_completion_level'] ?? '');
        $days_since_last_checkin = $candidate['days_since_last_checkin'] ?? null;

        $score += min(35, $active_days_total * 4);

        if($has_profile) $score += 10;
        if($has_checkin) $score += 15;
        if($has_plan) $score += 15;

        if($completion_level === 'completed') {
            $score += 20;
        } elseif(in_array($completion_level, ['partial', 'medium_execution', 'high_execution'], true)) {
            $score += 12;
        } elseif(in_array($completion_level, ['low_execution'], true)) {
            $score += 6;
        }

        if($days_since_last_checkin !== null) {
            if((int) $days_since_last_checkin <= 7) {
                $score += 8;
            } elseif((int) $days_since_last_checkin <= 14) {
                $score += 3;
            } else {
                $score -= 8;
            }
        }

        $score = $this->clamp_score($score);
        $state_key = 'low';
        $state_class = 'status-dark';

        if($score >= 75) {
            $state_key = 'strong';
            $state_class = 'status-success';
        } elseif($score >= 50) {
            $state_key = 'steady';
            $state_class = 'status-info';
        } elseif($score >= 30) {
            $state_key = 'watch';
            $state_class = 'status-warning';
        }

        return [
            'consistency_score' => $score,
            'consistency_state_key' => $state_key,
            'consistency_state_label' => ucfirst($state_key),
            'consistency_state_class' => $state_class,
        ];
    }

    private function get_team_consistency_payload(array $rows): array {
        $payload = [
            'average_score' => 0,
            'strong_total' => 0,
            'watch_total' => 0,
            'top_collaborators' => [],
        ];

        if(empty($rows)) {
            return $payload;
        }

        $sum = 0;
        foreach($rows as $row) {
            $sum += (int) ($row['consistency_score'] ?? 0);

            if(($row['consistency_state_key'] ?? '') === 'strong') {
                $payload['strong_total']++;
            }

            if(in_array(($row['consistency_state_key'] ?? ''), ['watch', 'low'], true)) {
                $payload['watch_total']++;
            }
        }

        $payload['average_score'] = round($sum / count($rows), 1);

        $ranked = $rows;
        usort($ranked, static function($a, $b) {
            return (($b['consistency_score'] ?? 0) <=> ($a['consistency_score'] ?? 0))
                ?: (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0));
        });

        foreach(array_slice($ranked, 0, 3) as $row) {
            $payload['top_collaborators'][] = [
                'name' => (string) ($row['name'] ?? l('global.unknown')),
                'score' => (int) ($row['consistency_score'] ?? 0),
                'detail_url' => (string) ($row['detail_url'] ?? ''),
            ];
        }

        return $payload;
    }

    private function get_team_coaching_roi_payload(array $rows): array {
        $payload = [
            'touched_total' => 0,
            'positive_signal_total' => 0,
            'risk_after_touch_total' => 0,
            'top_positive' => [],
        ];

        $candidates = [];

        foreach($rows as $row) {
            $has_recent_touch = !empty($row['mentored_this_week']) || !empty($row['last_contacted_at']) || !empty($row['latest_mentor_event_at']);

            if(!$has_recent_touch) {
                continue;
            }

            $payload['touched_total']++;

            $positive_signal = (($row['growth_percent'] ?? null) !== null && (float) ($row['growth_percent'] ?? 0) > 0)
                || in_array((string) ($row['status_key'] ?? ''), ['rising', 'high_potential'], true)
                || (int) ($row['leader_os_score'] ?? 0) >= 60;

            $risk_after_touch = (int) ($row['risk_score'] ?? 0) >= 55 || (int) ($row['anomaly_score'] ?? 0) >= 45 || (string) ($row['status_key'] ?? '') === 'risk';

            if($positive_signal) {
                $payload['positive_signal_total']++;
            }

            if($risk_after_touch) {
                $payload['risk_after_touch_total']++;
            }

            $candidates[] = [
                'name' => (string) ($row['name'] ?? l('global.unknown')),
                'detail_url' => (string) ($row['detail_url'] ?? ''),
                'growth_percent' => $row['growth_percent'] ?? null,
                'leader_os_score' => (int) ($row['leader_os_score'] ?? 0),
                'status_label' => (string) ($row['status_label'] ?? ''),
                'positive_signal' => $positive_signal,
            ];
        }

        usort($candidates, static function($a, $b) {
            return (($b['positive_signal'] ?? false) <=> ($a['positive_signal'] ?? false))
                ?: ((float) ($b['growth_percent'] ?? -9999) <=> (float) ($a['growth_percent'] ?? -9999))
                ?: (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0));
        });

        foreach(array_slice($candidates, 0, 3) as $row) {
            $payload['top_positive'][] = $row;
        }

        return $payload;
    }

    private function merge_ai_term_lists(array ...$lists): array {
        $buckets = [];

        foreach($lists as $list) {
            foreach($list as $item) {
                $label = trim((string) ($item['label'] ?? ''));
                if($label === '') {
                    continue;
                }

                $buckets[$label] = ($buckets[$label] ?? 0) + (int) ($item['total'] ?? 0);
            }
        }

        return $this->sort_count_buckets($buckets, 5);
    }

    private function get_team_ai_action_payload(array $rows, array $team_ai_text_intelligence): array {
        $payload = [
            'top_focus_terms' => $this->merge_ai_term_lists(
                $team_ai_text_intelligence['top_context_terms'] ?? [],
                $team_ai_text_intelligence['top_adjustment_terms'] ?? [],
                $team_ai_text_intelligence['top_response_terms'] ?? []
            ),
            'top_friction_terms' => $this->merge_ai_term_lists(
                $team_ai_text_intelligence['top_blocker_terms'] ?? [],
                $team_ai_text_intelligence['top_lesson_terms'] ?? [],
                $team_ai_text_intelligence['top_adaptive_terms'] ?? []
            ),
            'priority_collaborators' => [],
        ];

        $priority_rows = [];

        foreach($rows as $row) {
            $priority_score = 0;
            $reason_parts = [];

            $risk_score = (int) ($row['risk_score'] ?? 0);
            $anomaly_score = (int) ($row['anomaly_score'] ?? 0);
            $consistency_score = (int) ($row['consistency_score'] ?? 0);
            $days_since_last_checkin = $row['days_since_last_checkin'] ?? null;
            $completion_level = (string) ($row['latest_outcome_completion_level'] ?? '');

            $priority_score += $risk_score;
            $priority_score += (int) round($anomaly_score * 0.7);
            $priority_score += max(0, 100 - $consistency_score);

            if(($row['status_key'] ?? '') === 'risk') {
                $priority_score += 18;
                $reason_parts[] = 'risk status';
            }

            if(in_array(($row['consistency_state_key'] ?? ''), ['watch', 'low'], true)) {
                $priority_score += 15;
                $reason_parts[] = 'slaba konzistentnost';
            }

            if(empty($row['has_plan'])) {
                $priority_score += 10;
                $reason_parts[] = 'bez AI plana';
            }

            if($days_since_last_checkin !== null && (int) $days_since_last_checkin > 14) {
                $priority_score += 10;
                $reason_parts[] = 'stari check-in';
            }

            if(in_array($completion_level, ['not_started', 'low_execution'], true)) {
                $priority_score += 8;
                $reason_parts[] = 'slab outcome';
            }

            if(!empty($row['needs_follow_up'])) {
                $priority_score += 8;
                $reason_parts[] = 'needs follow-up';
            }

            if((int) ($row['blocked_attempts_total'] ?? 0) > 0) {
                $priority_score += min(18, (int) ($row['blocked_attempts_total'] ?? 0) * 3);
                $reason_parts[] = 'fraud signal';
            }

            if(($row['growth_percent'] ?? null) !== null && (float) ($row['growth_percent'] ?? 0) <= 0) {
                $priority_score += 5;
                $reason_parts[] = 'nema rasta';
            }

            if($priority_score < 95) {
                continue;
            }

            $priority_rows[] = [
                'name' => (string) ($row['name'] ?? l('global.unknown')),
                'detail_url' => (string) ($row['detail_url'] ?? ''),
                'priority_score' => $priority_score,
                'consistency_score' => $consistency_score,
                'risk_score' => $risk_score,
                'reason' => !empty($reason_parts) ? implode(' · ', array_slice(array_unique($reason_parts), 0, 3)) : 'potreban pregled',
            ];
        }

        usort($priority_rows, static function($a, $b) {
            return (($b['priority_score'] ?? 0) <=> ($a['priority_score'] ?? 0))
                ?: (($b['risk_score'] ?? 0) <=> ($a['risk_score'] ?? 0))
                ?: (($a['name'] ?? '') <=> ($b['name'] ?? ''));
        });

        $payload['priority_collaborators'] = array_slice($priority_rows, 0, 5);

        return $payload;
    }

    /* Custom code: FC-2026-03-31: LOS overview anomaly stage payload */
    private function get_overview_anomaly_payload(array $candidate): array {
        $points = 0;

        if((int) ($candidate['forever_shop_clicks_period'] ?? 0) >= 12 && (int) ($candidate['forever_registration_clicks_period'] ?? 0) === 0) {
            $points += 28;
        }

        if((float) ($candidate['growth_percent'] ?? 0) <= -35) {
            $points += 22;
        }

        if((int) ($candidate['risk_score'] ?? 0) >= 55) {
            $points += 18;
        }

        if((int) ($candidate['clicks_total_period'] ?? 0) >= 20 && (int) ($candidate['active_days_total'] ?? 0) <= 2) {
            $points += 12;
        }

        if(((int) ($candidate['forever_shop_clicks_90d'] ?? 0) >= 15) && (($candidate['days_since_last_checkin'] ?? null) !== null) && (int) $candidate['days_since_last_checkin'] >= 14) {
            $points += 14;
        }

        if(!empty($candidate['needs_follow_up'])) {
            $points += 10;
        }

        if((int) ($candidate['app_signal_score'] ?? 0) >= 12 && (int) ($candidate['forever_shop_clicks_period'] ?? 0) < 5) {
            $points += 16;
        }

        $score = $this->clamp_score($points);
        $stage_key = 'stable';
        $stage_class = 'status-success';

        if($score >= 45) {
            $stage_key = 'high';
            $stage_class = 'status-warning';
        } elseif($score >= 20) {
            $stage_key = 'watch';
            $stage_class = 'status-info';
        }

        return [
            'anomaly_stage_key' => $stage_key,
            'anomaly_stage_label' => l('admin_leader_operating_system.anomaly_filter.' . $stage_key),
            'anomaly_stage_class' => $stage_class,
            'anomaly_score' => $score,
        ];
    }
    /* /Custom code: FC-2026-03-31 */

    /* Custom code: FC-2026-04-01: LOS overview suspicious Forever click collaborators */
    private function get_suspicious_click_overview_payload(array $rows, string $period_key): array {
        $retention_days = function_exists('fc_get_forever_click_integrity_retention_days') ? fc_get_forever_click_integrity_retention_days() : 30;
        $effective_period_days = min($this->get_period_days($period_key), $retention_days);
        $period_start_datetime = $this->get_period_start_datetime($effective_period_days);

        $payload = [
            'retention_days' => $retention_days,
            'effective_period_days' => $effective_period_days,
            'totals' => [
                'affected_collaborators' => 0,
                'blocked_attempts_total' => 0,
                'groups_total' => 0,
            ],
            'top_reasons' => [],
            'top_targets' => [],
            'rows' => [],
        ];

        if(empty($rows) || !function_exists('fc_ensure_forever_click_integrity_tables')) {
            return $payload;
        }

        fc_ensure_forever_click_integrity_tables();

        $row_map = [];
        foreach($rows as $row) {
            $row_map[(int) ($row['user_id'] ?? 0)] = $row;
        }

        $user_ids = array_keys($row_map);
        if(empty($user_ids)) {
            return $payload;
        }

        $user_ids_sql = implode(',', array_map(static fn($user_id) => (int) $user_id, $user_ids));
        $suspicious_result = database()->query("SELECT
            `user_id`,
            `reason_key`,
            `reason_title`,
            `reason_text`,
            `target_signature`,
            `target_label`,
            `datetime`
        FROM `forever_click_integrity_suspicious`
        WHERE `datetime` >= '{$period_start_datetime}'
          AND `user_id` IN ({$user_ids_sql})
        ORDER BY `datetime` DESC");

        $grouped_rows = [];
        $reason_buckets = [];
        $target_buckets = [];

        while($row = $suspicious_result->fetch_assoc()) {
            $user_id = (int) ($row['user_id'] ?? 0);

            if(!$user_id || !isset($row_map[$user_id])) {
                continue;
            }

            if(!isset($grouped_rows[$user_id])) {
                $grouped_rows[$user_id] = [
                    'blocked_attempts_total' => 0,
                    'groups' => [],
                    'targets' => [],
                    'latest_datetime' => (string) ($row['datetime'] ?? ''),
                    'latest_reason_title' => (string) ($row['reason_title'] ?? ''),
                    'latest_reason_text' => (string) ($row['reason_text'] ?? ''),
                ];
            }

            $grouped_rows[$user_id]['blocked_attempts_total']++;
            $grouped_rows[$user_id]['groups'][(string) (($row['reason_key'] ?? 'unknown') . '|' . ($row['target_signature'] ?? 'target'))] = true;
            $grouped_rows[$user_id]['targets'][(string) ($row['target_label'] ?? '-')] = true;
            $this->increment_count_bucket($reason_buckets, (string) ($row['reason_title'] ?? 'Nepoznati razlog'));
            $this->increment_count_bucket($target_buckets, (string) ($row['target_label'] ?? 'Nepoznati target'));

            if((string) ($row['datetime'] ?? '') >= $grouped_rows[$user_id]['latest_datetime']) {
                $grouped_rows[$user_id]['latest_datetime'] = (string) ($row['datetime'] ?? '');
                $grouped_rows[$user_id]['latest_reason_title'] = (string) ($row['reason_title'] ?? '');
                $grouped_rows[$user_id]['latest_reason_text'] = (string) ($row['reason_text'] ?? '');
            }
        }

        foreach($grouped_rows as $user_id => $suspicious_row) {
            $base_row = $row_map[$user_id];

            $payload['rows'][] = [
                'user_id' => $user_id,
                'name' => (string) ($base_row['name'] ?? l('global.unknown')),
                'email' => (string) ($base_row['email'] ?? ''),
                'detail_url' => (string) ($base_row['detail_url'] ?? ''),
                'admin_user_url' => (string) ($base_row['admin_user_url'] ?? ''),
                'status_label' => (string) ($base_row['status_label'] ?? ''),
                'status_class' => (string) ($base_row['status_class'] ?? 'secondary'),
                'ai_usage_stage_label' => (string) ($base_row['ai_usage_stage_label'] ?? ''),
                'ai_usage_stage_class' => (string) ($base_row['ai_usage_stage_class'] ?? 'status-dark'),
                'anomaly_stage_label' => (string) ($base_row['anomaly_stage_label'] ?? ''),
                'anomaly_stage_class' => (string) ($base_row['anomaly_stage_class'] ?? 'status-info'),
                'blocked_attempts_total' => (int) ($suspicious_row['blocked_attempts_total'] ?? 0),
                'suspicious_groups_total' => count($suspicious_row['groups'] ?? []),
                'targets_total' => count($suspicious_row['targets'] ?? []),
                'last_suspicious_at' => (string) ($suspicious_row['latest_datetime'] ?? ''),
                'top_reason_title' => (string) ($suspicious_row['latest_reason_title'] ?? ''),
                'top_reason_text' => (string) ($suspicious_row['latest_reason_text'] ?? ''),
            ];
        }

        usort($payload['rows'], static function($a, $b) {
            return (($b['blocked_attempts_total'] ?? 0) <=> ($a['blocked_attempts_total'] ?? 0))
                ?: (($b['suspicious_groups_total'] ?? 0) <=> ($a['suspicious_groups_total'] ?? 0))
                ?: strcmp((string) ($b['last_suspicious_at'] ?? ''), (string) ($a['last_suspicious_at'] ?? ''));
        });

        $payload['rows'] = array_slice($payload['rows'], 0, 8);
        $payload['totals']['affected_collaborators'] = count($grouped_rows);

        foreach($grouped_rows as $suspicious_row) {
            $payload['totals']['blocked_attempts_total'] += (int) ($suspicious_row['blocked_attempts_total'] ?? 0);
            $payload['totals']['groups_total'] += count($suspicious_row['groups'] ?? []);
        }

        $payload['top_reasons'] = $this->sort_count_buckets($reason_buckets, 5);
        $payload['top_targets'] = $this->sort_count_buckets($target_buckets, 5);

        return $payload;
    }
    /* /Custom code: FC-2026-04-01 */

    private function get_fraud_dashboard_payload(array $rows, array $suspicious_clicks): array {
        $payload = [
            'totals' => [
                'high_anomaly_total' => 0,
                'watch_anomaly_total' => 0,
                'queue_total' => 0,
                'suspicious_affected_total' => (int) ($suspicious_clicks['totals']['affected_collaborators'] ?? 0),
                'blocked_attempts_total' => (int) ($suspicious_clicks['totals']['blocked_attempts_total'] ?? 0),
            ],
            'top_anomaly_drivers' => [],
            'top_risk_collaborators' => [],
            'top_suspicious_reasons' => $suspicious_clicks['top_reasons'] ?? [],
            'top_suspicious_targets' => $suspicious_clicks['top_targets'] ?? [],
        ];

        $driver_buckets = [];
        $risk_rows = [];

        foreach($rows as $row) {
            $anomaly_stage_key = (string) ($row['anomaly_stage_key'] ?? 'stable');

            if($anomaly_stage_key === 'high') {
                $payload['totals']['high_anomaly_total']++;
            } elseif($anomaly_stage_key === 'watch') {
                $payload['totals']['watch_anomaly_total']++;
            }

            if(!empty($row['queue_reason'])) {
                $payload['totals']['queue_total']++;
            }

            $reasons = [];

            if((int) ($row['forever_shop_clicks_period'] ?? 0) >= 12 && (int) ($row['forever_registration_clicks_period'] ?? 0) === 0) {
                $this->increment_count_bucket($driver_buckets, 'Mnogo klikova bez registracija');
                $reasons[] = 'klikovi bez registracija';
            }

            if((float) ($row['growth_percent'] ?? 0) <= -35) {
                $this->increment_count_bucket($driver_buckets, 'Jak pad rasta');
                $reasons[] = 'jak pad rasta';
            }

            if((int) ($row['clicks_total_period'] ?? 0) >= 20 && (int) ($row['active_days_total'] ?? 0) <= 2) {
                $this->increment_count_bucket($driver_buckets, 'Puno klikova u premalo aktivnih dana');
                $reasons[] = 'klik spike u malo dana';
            }

            if(((int) ($row['forever_shop_clicks_90d'] ?? 0) >= 15) && (($row['days_since_last_checkin'] ?? null) !== null) && (int) $row['days_since_last_checkin'] >= 14) {
                $this->increment_count_bucket($driver_buckets, 'Jak promet bez svježeg AI check-ina');
                $reasons[] = 'nema svježeg check-ina';
            }

            if((int) ($row['app_signal_score'] ?? 0) >= 12 && (int) ($row['forever_shop_clicks_period'] ?? 0) < 5) {
                $this->increment_count_bucket($driver_buckets, 'App signal bez shop konverzije');
                $reasons[] = 'app signal bez shop konverzije';
            }

            if((int) ($row['risk_score'] ?? 0) >= 55) {
                $this->increment_count_bucket($driver_buckets, 'Visok risk score');
                $reasons[] = 'visok risk';
            }

            if(!empty($row['needs_follow_up'])) {
                $this->increment_count_bucket($driver_buckets, 'Otvoren follow-up signal');
                $reasons[] = 'needs follow-up';
            }

            if($anomaly_stage_key === 'stable' && empty($reasons)) {
                continue;
            }

            $risk_rows[] = [
                'name' => (string) ($row['name'] ?? l('global.unknown')),
                'detail_url' => (string) ($row['detail_url'] ?? ''),
                'risk_score' => (int) ($row['risk_score'] ?? 0),
                'anomaly_score' => (int) ($row['anomaly_score'] ?? 0),
                'blocked_attempts_total' => 0,
                'reason' => !empty($reasons) ? implode(' · ', array_slice(array_unique($reasons), 0, 3)) : 'povišen fraud signal',
            ];
        }

        $blocked_by_user = [];
        foreach(($suspicious_clicks['rows'] ?? []) as $suspicious_row) {
            $blocked_by_user[(int) ($suspicious_row['user_id'] ?? 0)] = (int) ($suspicious_row['blocked_attempts_total'] ?? 0);
        }

        foreach($risk_rows as $index => $risk_row) {
            $detail_url = (string) ($risk_row['detail_url'] ?? '');
            $user_id = 0;
            if(preg_match('/user_id=(\d+)/', $detail_url, $matches)) {
                $user_id = (int) ($matches[1] ?? 0);
            }
            $risk_rows[$index]['blocked_attempts_total'] = $blocked_by_user[$user_id] ?? 0;
        }

        usort($risk_rows, static function($a, $b) {
            return (($b['blocked_attempts_total'] ?? 0) <=> ($a['blocked_attempts_total'] ?? 0))
                ?: (($b['anomaly_score'] ?? 0) <=> ($a['anomaly_score'] ?? 0))
                ?: (($b['risk_score'] ?? 0) <=> ($a['risk_score'] ?? 0));
        });

        $payload['top_anomaly_drivers'] = $this->sort_count_buckets($driver_buckets, 6);
        $payload['top_risk_collaborators'] = array_slice($risk_rows, 0, 5);

        return $payload;
    }

    private function get_executive_summary_compare_map(array $overview_payload): array {
        $map = [];

        foreach((array) ($overview_payload['primary_team_kpis'] ?? []) as $kpi) {
            $key = (string) ($kpi['key'] ?? '');

            if($key === '') {
                continue;
            }

            $map[$key] = (array) ($kpi['compare'] ?? []);
        }

        return $map;
    }

    private function get_executive_summary_focus_payload(array $overview_payload): array {
        $is_hr = \Altum\Language::$code === 'hr';
        $totals = $overview_payload['totals'] ?? [];
        $team_consistency = $overview_payload['team_consistency'] ?? [];
        $support_center = $overview_payload['support_center'] ?? [];
        $fraud_dashboard = $overview_payload['fraud_dashboard'] ?? [];

        $risk_total = (int) ($totals['risk'] ?? 0);
        $rising_total = (int) ($totals['rising'] ?? 0);
        $qualified_total = (int) ($totals['qualified'] ?? 0);
        $shop_clicks_total = (int) ($totals['total_shop_clicks_period'] ?? 0);
        $funnel_leads_total = (int) ($totals['total_funnel_leads_period'] ?? 0);
        $capture_rate = $shop_clicks_total > 0 ? round(($funnel_leads_total / max(1, $shop_clicks_total)) * 100, 1) : 0;
        $consistency_avg = (float) ($team_consistency['average_score'] ?? 0);
        $high_anomaly_total = (int) ($totals['anomaly_high'] ?? 0);
        $blocked_attempts_total = (int) ($fraud_dashboard['totals']['blocked_attempts_total'] ?? 0);
        $stale_support_total = (int) (($support_center['totals']['stale_total'] ?? 0));
        $top_support_theme = trim((string) ($support_center['top_themes'][0]['label'] ?? ''));

        if($high_anomaly_total >= 3 || $blocked_attempts_total >= 10) {
            return [
                'focus' => [
                    'key' => 'fraud_watch',
                    'label' => $is_hr ? 'Zaštita prometa i provjera tima' : 'Traffic protection and team validation',
                    'note' => $is_hr
                        ? nr($blocked_attempts_total) . ' blokiranih pokušaja · ' . nr($high_anomaly_total) . ' suradnika s jakim anomaly signalom'
                        : nr($blocked_attempts_total) . ' blocked attempts · ' . nr($high_anomaly_total) . ' high anomaly collaborators',
                ],
                'friction' => [
                    'key' => 'fraud_pressure',
                    'label' => $is_hr ? 'Sumnjivi obrasci i anomaly signal' : 'Suspicious patterns and anomaly signal',
                    'note' => $is_hr
                        ? 'Prije novog širenja prvo provjeri najrizičnije klikove i suradnike.'
                        : 'Validate the riskiest clicks and collaborators before scaling further.',
                ],
            ];
        }

        if($risk_total >= max(4, $rising_total)) {
            return [
                'focus' => [
                    'key' => 'risk_stabilization',
                    'label' => $is_hr ? 'Stabilizacija rizične grupe' : 'Stabilize the risk segment',
                    'note' => $is_hr
                        ? nr($risk_total) . ' rizičnih suradnika · dosljednost ' . nr($consistency_avg) . '/100'
                        : nr($risk_total) . ' at-risk collaborators · consistency ' . nr($consistency_avg) . '/100',
                ],
                'friction' => [
                    'key' => 'follow_up_gap',
                    'label' => $is_hr ? 'Rupa u praćenju i provedbi' : 'Follow-up and execution gap',
                    'note' => $is_hr
                        ? 'Rizični segment je jači od segmenta rasta i traži coaching prije novog širenja.'
                        : 'The risk segment currently outweighs growth and needs coaching before new scale-up.',
                ],
            ];
        }

        if($stale_support_total >= 5 && $top_support_theme !== '') {
            return [
                'focus' => [
                    'key' => 'support_clarity',
                    'label' => $is_hr ? 'Jasnoća poruke i podrške' : 'Message clarity and support',
                    'note' => $is_hr
                        ? nr($stale_support_total) . ' starih ticketa · tema: ' . $top_support_theme
                        : nr($stale_support_total) . ' stale tickets · theme: ' . $top_support_theme,
                ],
                'friction' => [
                    'key' => 'repeated_confusion',
                    'label' => $is_hr ? 'Ponavljajuće nejasnoće u timu' : 'Repeated team confusion',
                    'note' => $is_hr
                        ? 'Ista tema se vraća kroz podršku i traži jasniji webinar, FAQ ili internu poruku.'
                        : 'The same issue keeps returning through support and needs a clearer webinar, FAQ, or internal update.',
                ],
            ];
        }

        if($shop_clicks_total >= 20 && $capture_rate < 10) {
            return [
                'focus' => [
                    'key' => 'conversion',
                    'label' => $is_hr ? 'Pretvaranje klikova u prijave' : 'Turn clicks into sign-ups',
                    'note' => $is_hr
                        ? nr($funnel_leads_total) . ' prijava na ' . nr($shop_clicks_total) . ' klikova · ' . nr($capture_rate) . '% stopa prijave'
                        : nr($funnel_leads_total) . ' sign-ups from ' . nr($shop_clicks_total) . ' clicks · ' . nr($capture_rate) . '% capture rate',
                ],
                'friction' => [
                    'key' => 'conversion_gap',
                    'label' => $is_hr ? 'Klikovi bez dovoljnog broja prijava' : 'Clicks without enough funnel output',
                    'note' => $is_hr
                        ? 'Promet postoji, ali put do prijave još nije dovoljno jasan i vođen.'
                        : 'Traffic exists, but the path to sign-up is not yet clear and guided enough.',
                ],
            ];
        }

        if($consistency_avg < 45) {
            return [
                'focus' => [
                    'key' => 'consistency',
                    'label' => $is_hr ? 'Dosljednost i zatvaranje ciklusa' : 'Consistency and cycle completion',
                    'note' => $is_hr
                        ? 'Prosjek konzistentnosti tima je ' . nr($consistency_avg) . '/100'
                        : 'Average team consistency is ' . nr($consistency_avg) . '/100',
                ],
                'friction' => [
                    'key' => 'execution_rhythm',
                    'label' => $is_hr ? 'Slab ritam provedbe' : 'Weak execution rhythm',
                    'note' => $is_hr
                        ? 'Planovi i aktivnosti postoje, ali se tjedni ciklus ne zatvara dovoljno dosljedno.'
                        : 'Plans and activity exist, but the weekly cycle is not being closed consistently enough.',
                ],
            ];
        }

        if($rising_total >= max(3, $risk_total) && $qualified_total >= 5 && $consistency_avg >= 55) {
            return [
                'focus' => [
                    'key' => 'growth',
                    'label' => $is_hr ? 'Skaliranje onoga što već radi' : 'Scale what already works',
                    'note' => $is_hr
                        ? nr($rising_total) . ' suradnika u rastu · ' . nr($qualified_total) . ' kvalificiranih'
                        : nr($rising_total) . ' rising collaborators · ' . nr($qualified_total) . ' qualified',
                ],
                'friction' => [
                    'key' => 'quality_guard',
                    'label' => $is_hr ? 'Zadržati kvalitetu tijekom rasta' : 'Keep quality while scaling',
                    'note' => $is_hr
                        ? 'Najveći rizik nije promet nego zadržavanje kvalitete praćenja i pretvorbe.'
                        : 'The biggest risk is not traffic but maintaining follow-up and conversion quality.',
                ],
            ];
        }

        return [
            'focus' => [
                'key' => 'stability',
                'label' => $is_hr ? 'Održavanje zdravog momentuma' : 'Maintain healthy momentum',
                'note' => $is_hr
                    ? 'Tim je stabilan i spreman za precizne optimizacije.'
                    : 'The team is stable and ready for precise optimization.',
            ],
            'friction' => [
                'key' => 'monitoring',
                'label' => $is_hr ? 'Nema dominantne frikcije' : 'No dominant friction',
                'note' => $is_hr
                    ? 'Prati kvalitetu pretvorbe i signale koji prvi odstupaju.'
                    : 'Monitor conversion quality and the first signals that start to drift.',
            ],
        ];
    }

    private function get_executive_summary_action_payload(array $overview_payload, array $focus_payload): array {
        $is_hr = \Altum\Language::$code === 'hr';
        $totals = $overview_payload['totals'] ?? [];
        $support_center = $overview_payload['support_center'] ?? [];
        $compare_map = $this->get_executive_summary_compare_map($overview_payload);
        $shop_compare_text = trim((string) (($compare_map['total_shop_clicks_period']['text'] ?? '')));
        $funnel_compare_text = trim((string) (($compare_map['total_funnel_leads_period']['text'] ?? '')));
        $risk_total = (int) ($totals['risk'] ?? 0);
        $rising_total = (int) ($totals['rising'] ?? 0);

        switch((string) ($focus_payload['key'] ?? 'stability')) {
            case 'fraud_watch':
                return [
                    'eyebrow' => $is_hr ? 'Admin akcija sada' : 'Admin action now',
                    'title' => $is_hr ? 'Prvo otvori listu prijevara i rizika' : 'Open the Fraud and Risk lists first',
                    'subtitle' => $is_hr
                        ? 'Pregledaj sumnjive obrasce, blokirane pokušaje i suradnike s najvećim anomaly signalom prije novih timskih akcija.'
                        : 'Review the top suspicious patterns, blocked attempts, and collaborators with the highest anomaly signal before new team actions.',
                    'note' => $is_hr
                        ? 'Početni fokus: rizik ' . nr($risk_total) . ' · rast ' . nr($rising_total)
                        : 'Starting focus: risk ' . nr($risk_total) . ' · rising ' . nr($rising_total),
                    'tone' => 'danger',
                ];

            case 'risk_stabilization':
                return [
                    'eyebrow' => $is_hr ? 'Admin akcija sada' : 'Admin action now',
                    'title' => $is_hr ? 'Pokreni coaching praćenje za rizičnu grupu' : 'Start coaching follow-up for the risk segment',
                    'subtitle' => $is_hr
                        ? 'Prioritet su suradnici s rizičnim statusom, starim check-inom i slabijom dosljednošću prije novih akcija rasta.'
                        : 'Prioritize collaborators with risk status, stale check-ins, and weaker consistency before new growth pushes.',
                    'note' => $is_hr
                        ? 'Rizik je trenutno jači od rasta i zato coaching ima prednost.'
                        : 'Risk currently outweighs growth, so coaching should come first.',
                    'tone' => 'warning',
                ];

            case 'support_clarity':
                return [
                    'eyebrow' => $is_hr ? 'Admin akcija sada' : 'Admin action now',
                    'title' => $is_hr ? 'Pretvori temu iz podrške u jasan timski odgovor' : 'Turn the support theme into a clear team answer',
                    'subtitle' => $is_hr
                        ? 'Pripremi kratku internu poruku, FAQ ili webinar oko teme koja se najviše ponavlja u podršci.'
                        : 'Prepare a short internal message, FAQ, or webinar around the issue that repeats most often in support.',
                    'note' => $is_hr
                        ? 'Glavna tema podrške: ' . ((string) ($support_center['top_themes'][0]['label'] ?? '-'))
                        : 'Top support topic: ' . ((string) ($support_center['top_themes'][0]['label'] ?? '-')),
                    'tone' => 'info',
                ];

            case 'conversion':
                return [
                    'eyebrow' => $is_hr ? 'Admin akcija sada' : 'Admin action now',
                    'title' => $is_hr ? 'Pregledaj top aplikacije s klikovima bez prijava' : 'Review top apps with clicks but no sign-ups',
                    'subtitle' => $is_hr
                        ? 'Promet postoji, pa sada treba pojednostaviti poziv na akciju, prijavni korak i praćenje tamo gdje klik postoji, ali prijava ne prati.'
                        : 'Traffic exists, so the next move is to tighten CTA, funnel steps, and follow-up where clicks are happening but sign-ups are not.',
                    'note' => $is_hr
                        ? 'Klikovi: ' . ($shop_compare_text !== '' ? $shop_compare_text : '-') . ' · Prijave: ' . ($funnel_compare_text !== '' ? $funnel_compare_text : '-')
                        : 'Clicks: ' . ($shop_compare_text !== '' ? $shop_compare_text : '-') . ' · Funnel: ' . ($funnel_compare_text !== '' ? $funnel_compare_text : '-'),
                    'tone' => 'warning',
                ];

            case 'consistency':
                return [
                    'eyebrow' => $is_hr ? 'Admin akcija sada' : 'Admin action now',
                    'title' => $is_hr ? 'Podigni dosljednost prije novog rasta' : 'Raise consistency before the next scale-up',
                    'subtitle' => $is_hr
                        ? 'Usmjeri coaching na check-in ritam, završavanje plana i zatvaranje outcome ciklusa.'
                        : 'Focus coaching on check-in rhythm, plan completion, and closing the outcome loop.',
                    'note' => $is_hr
                        ? 'Bez jačeg ritma tim teže pretvara promet u stabilan rezultat.'
                        : 'Without a stronger rhythm, the team struggles to turn traffic into stable outcomes.',
                    'tone' => 'info',
                ];

            case 'growth':
                return [
                    'eyebrow' => $is_hr ? 'Admin akcija sada' : 'Admin action now',
                    'title' => $is_hr ? 'Dupliciraj ono što radi na glavnom tržištu' : 'Duplicate what works in the top market',
                    'subtitle' => $is_hr
                        ? 'Iskoristi suradnike u rastu i najjači obrazac tržišta i izvora kao model za ostatak tima.'
                        : 'Use the rising collaborators and the strongest market/source pattern as the model for the rest of the team.',
                    'note' => $is_hr
                        ? 'Rast je zdrav, pa je sada prioritet standardizacija najboljeg obrasca.'
                        : 'Growth is healthy, so the next priority is standardizing the best-performing pattern.',
                    'tone' => 'success',
                ];
        }

        return [
            'eyebrow' => $is_hr ? 'Admin akcija sada' : 'Admin action now',
            'title' => $is_hr ? 'Prati signal i reagiraj na prvo odstupanje' : 'Monitor the signal and react to the first deviation',
            'subtitle' => $is_hr
                ? 'Tim je stabilan, pa je najbolja akcija održati kvalitetu pretvorbe, dosljednost i jasnu prioritizaciju.'
                : 'The team is stable, so the best action is to preserve conversion quality, consistency, and clear prioritization.',
            'note' => $is_hr
                ? 'Najveća korist sada dolazi iz preciznih korekcija, ne iz širokih promjena.'
                : 'The biggest gain now comes from precise corrections, not broad changes.',
            'tone' => 'info',
        ];
    }

    private function get_executive_summary_payload(array $overview_payload): array {
        $is_hr = \Altum\Language::$code === 'hr';
        $totals = $overview_payload['totals'] ?? [];
        $team_analytics = $overview_payload['team_analytics'] ?? [];
        $team_consistency = $overview_payload['team_consistency'] ?? [];
        $fraud_dashboard = $overview_payload['fraud_dashboard'] ?? [];
        $country_matrices = $overview_payload['team_country_signal_matrix_periods'] ?? [];
        $selected_period = (string) ($overview_payload['selected_period'] ?? '30d');
        $selected_country_matrix = $country_matrices[$selected_period] ?? ['rows' => [], 'totals' => []];
        $top_country_row = $selected_country_matrix['rows'][0] ?? [];
        $compare_map = $this->get_executive_summary_compare_map($overview_payload);

        $risk_total = (int) ($totals['risk'] ?? 0);
        $high_anomaly_total = (int) ($totals['anomaly_high'] ?? 0);
        $blocked_attempts_total = (int) ($fraud_dashboard['totals']['blocked_attempts_total'] ?? 0);
        $rising_total = (int) ($totals['rising'] ?? 0);
        $qualified_total = (int) ($totals['qualified'] ?? 0);
        $active_collaborators = (int) ($totals['active_collaborators'] ?? 0);
        $active_pro_total = (int) ($totals['active_pro_total'] ?? 0);
        $shop_clicks_total = (int) ($totals['total_shop_clicks_period'] ?? 0);
        $funnel_leads_total = (int) ($totals['total_funnel_leads_period'] ?? 0);
        $capture_rate = $shop_clicks_total > 0 ? round(($funnel_leads_total / max(1, $shop_clicks_total)) * 100, 1) : 0;
        $consistency_avg = (float) ($team_consistency['average_score'] ?? 0);
        $shop_compare_text = trim((string) (($compare_map['total_shop_clicks_period']['text'] ?? '')));
        $funnel_compare_text = trim((string) (($compare_map['total_funnel_leads_period']['text'] ?? '')));
        $build_metric_link = static function(string $key, string $label, string $value_display, string $description = ''): array {
            return [
                'key' => $key,
                'label' => $label,
                'value_display' => $value_display,
                'description' => $description,
            ];
        };
        $focus_metric_links = [];

        $resolve_focus_metric_links = static function(string $focus_key) use (
            $is_hr,
            $risk_total,
            $high_anomaly_total,
            $shop_clicks_total,
            $funnel_leads_total,
            $qualified_total,
            $active_collaborators,
            $active_pro_total,
            $rising_total,
            $build_metric_link
        ): array {
            return match($focus_key) {
                'fraud_watch', 'risk_stabilization' => [
                    $build_metric_link('risk', $is_hr ? 'Risk suradnici' : 'Risk collaborators', nr($risk_total), $is_hr ? 'Suradnici koji trenutno traže pažnju, coaching ili operativni zahvat.' : 'Collaborators who currently need attention, coaching, or intervention.'),
                    $build_metric_link('anomaly_high', $is_hr ? 'Provjeri odmah' : 'Check now', nr($high_anomaly_total), $is_hr ? 'Suradnici s najjačim anomaly signalom koje prvo treba provjeriti.' : 'Collaborators with the strongest anomaly signal to review first.'),
                ],
                'conversion' => [
                    $build_metric_link('total_shop_clicks_period', $is_hr ? 'Klikovi prema Foreveru' : 'Clicks toward Forever', nr($shop_clicks_total), $is_hr ? 'Ukupni klikovi iz aplikacija prema Forever odredištima u odabranom periodu.' : 'Total clicks from apps toward Forever destinations in the selected period.'),
                    $build_metric_link('total_funnel_leads_period', $is_hr ? 'Funnel prijave' : 'Funnel sign-ups', nr($funnel_leads_total), $is_hr ? 'Koliko se tog interesa stvarno pretvorilo u ispunjene prijave.' : 'How much of that interest turned into completed sign-ups.'),
                ],
                'growth' => [
                    $build_metric_link('rising', $is_hr ? 'U rastu' : 'Rising', nr($rising_total), $is_hr ? 'Suradnici koji trenutno pokazuju najzdraviji momentum za scale-up.' : 'Collaborators currently showing the healthiest growth momentum.'),
                    $build_metric_link('qualified', $is_hr ? 'Kvalificirani' : 'Qualified', nr($qualified_total), $is_hr ? 'Jezgra tima s dokazanim signalom aktivnosti.' : 'The core of the team with a proven activity signal.'),
                ],
                'consistency', 'stability' => [
                    $build_metric_link('qualified', $is_hr ? 'Kvalificirani' : 'Qualified', nr($qualified_total), $is_hr ? 'Jezgra tima koja već ima dokazani signal.' : 'The core team that already has a proven signal.'),
                    $build_metric_link('active_collaborators', $is_hr ? 'Aktivni' : 'Active', nr($active_collaborators), $is_hr ? 'Suradnici koji su stvarno imali aktivnost u promatranom periodu.' : 'Collaborators who actually had activity in the observed period.'),
                    $build_metric_link('active_pro_total', $is_hr ? 'Aktivni PRO' : 'Active PRO', nr($active_pro_total), $is_hr ? 'Trenutno aktivni PRO računi koji nose monetizacijsku jezgru tima.' : 'Currently active PRO accounts representing the monetized team base.'),
                ],
                default => [],
            };
        };

        $status_key = 'stable';
        $status_label = $is_hr ? 'Stabilno' : 'Stable';
        $status_class = 'status-info';
        $headline = $is_hr ? 'Tim je stabilan i spreman za preciznu optimizaciju.' : 'The team is stable and ready for precise optimization.';
        $subheadline = $is_hr ? 'Glavni fokus je zadržati momentum, paziti na kvalitetu pretvorbe i reagirati na prvo odstupanje.' : 'The main focus is to preserve momentum, watch conversion quality, and react to the first deviation.';

        if($high_anomaly_total >= 3 || $blocked_attempts_total >= 10) {
            $status_key = 'fraud_watch';
            $status_label = $is_hr ? 'Fraud nadzor' : 'Fraud watch';
            $status_class = 'status-danger';
            $headline = $is_hr ? 'Tim ima izražen fraud i anomaly pritisak.' : 'The team is under visible fraud and anomaly pressure.';
            $subheadline = $is_hr ? 'Prije daljnjeg rasta prvo provjeri sumnjive obrasce, blokirane pokušaje i najrizičnije suradnike.' : 'Before any further scale-up, review suspicious patterns, blocked attempts, and the riskiest collaborators first.';
        } elseif($risk_total >= max(4, $rising_total)) {
            $status_key = 'coaching';
            $status_label = $is_hr ? 'Fokus coachinga' : 'Coaching focus';
            $status_class = 'status-warning';
            $headline = $is_hr ? 'Tim traži stabilizaciju prije novog rasta.' : 'The team needs stabilization before the next growth push.';
            $subheadline = $is_hr ? 'Rizični segment je trenutno jači od segmenta rasta pa coaching, praćenje i dosljednost trebaju imati prioritet.' : 'The risk segment currently outweighs growth, so coaching, follow-up, and consistency should come first.';
        } elseif($rising_total >= max(3, $risk_total) && $qualified_total >= 5 && $consistency_avg >= 55) {
            $status_key = 'growth';
            $status_label = $is_hr ? 'Momentum rasta' : 'Growth momentum';
            $status_class = 'status-success';
            $headline = $is_hr ? 'Tim pokazuje zdrav ritam za daljnji rast.' : 'The team shows healthy momentum for scale-up.';
            $subheadline = $is_hr ? 'Najveća prilika je duplicirati ono što već radi na top tržištu i u najjačem vremenu aktivnosti.' : 'The biggest opportunity is to duplicate what already works in the top market and top activity window.';
        }

        $focus_payload = $this->get_executive_summary_focus_payload($overview_payload);
        $action_payload = $this->get_executive_summary_action_payload($overview_payload, $focus_payload['focus'] ?? []);
        $focus_metric_links = $resolve_focus_metric_links((string) ($focus_payload['focus']['key'] ?? 'stability'));

        $top_country_name = (string) ($top_country_row['country_name'] ?? ($team_analytics['top_countries'][0]['label'] ?? '-'));
        $top_country_total = (int) (($top_country_row['app_visits'] ?? 0) + ($top_country_row['app_shop_clicks'] ?? 0) + ($top_country_row['blog_clicks'] ?? 0) + ($top_country_row['funnel_registrations'] ?? 0));
        $top_source = $team_analytics['top_sources'][0] ?? ['label' => '-', 'total' => 0];
        $top_hour = $team_analytics['top_hours'][0] ?? ['label' => '-', 'total' => 0];

        return [
            'status_key' => $status_key,
            'status_label' => $status_label,
            'status_class' => $status_class,
            'headline' => $headline,
            'subheadline' => $subheadline,
            'top_country' => $top_country_name,
            'top_hour' => (string) ($top_hour['label'] ?? '-'),
            'top_source' => (string) ($top_source['label'] ?? '-'),
            'focus_term' => (string) ($focus_payload['focus']['label'] ?? '-'),
            'friction_term' => (string) ($focus_payload['friction']['label'] ?? '-'),
            'signals' => [
                [
                    'label' => $is_hr ? 'Rizik i anomaly' : 'Risk and anomaly',
                    'value' => $is_hr ? nr($risk_total) . ' u riziku' : nr($risk_total) . ' risk',
                    'note' => $is_hr
                        ? nr($high_anomaly_total) . ' jak anomaly · ' . nr($blocked_attempts_total) . ' blokiranih pokušaja'
                        : nr($high_anomaly_total) . ' high anomaly · ' . nr($blocked_attempts_total) . ' blocked attempts',
                    'what_it_shows' => $is_hr
                        ? 'Koliko suradnika trenutno ulazi u risk zonu i koliko njih već ima jak anomaly signal ili blokirane pokušaje.'
                        : 'How many collaborators are currently in the risk zone and how many already show a strong anomaly signal or blocked attempts.',
                    'how_to_use' => $is_hr
                        ? 'Ako ovo raste, prvo provjeri tko je unutra prije novih timskih akcija ili scale-upa.'
                        : 'If this grows, review who is inside before any new team actions or scale-up.',
                    'metric_links' => [
                        $build_metric_link('risk', $is_hr ? 'Rizik' : 'Risk', nr($risk_total), $is_hr ? 'Suradnici koji traže pažnju, coaching ili operativni zahvat.' : 'Collaborators who need attention, coaching, or intervention.'),
                        $build_metric_link('anomaly_high', $is_hr ? 'Jak anomaly' : 'High anomaly', nr($high_anomaly_total), $is_hr ? 'Suradnici koje treba provjeriti odmah zbog jakog anomaly signala.' : 'Collaborators to review immediately due to a strong anomaly signal.'),
                    ],
                    'tone' => ($high_anomaly_total >= 3 || $blocked_attempts_total >= 10) ? 'danger' : ($risk_total > 0 ? 'warning' : 'success'),
                ],
                [
                    'label' => $is_hr ? 'Klikovi → prijave' : 'Clicks to sign-ups',
                    'value' => $is_hr ? nr($funnel_leads_total) . ' leadova' : nr($funnel_leads_total) . ' leads',
                    'note' => $is_hr
                        ? nr($shop_clicks_total) . ' klikova · ' . nr($capture_rate) . '% stopa prijave'
                        : nr($shop_clicks_total) . ' clicks · ' . nr($capture_rate) . '% capture rate',
                    'what_it_shows' => $is_hr
                        ? 'Koliko klikova prema Foreveru tim stvara i koliko ih se stvarno pretvara u funnel prijave.'
                        : 'How many clicks toward Forever the team creates and how many actually turn into funnel sign-ups.',
                    'how_to_use' => $is_hr
                        ? 'Ako klikovi rastu, a prijave ne prate, problem je u CTA-u, funnel koraku ili follow-upu.'
                        : 'If clicks grow but sign-ups do not, the issue is in the CTA, funnel step, or follow-up.',
                    'metric_links' => [
                        $build_metric_link('total_shop_clicks_period', $is_hr ? 'Klikovi' : 'Clicks', nr($shop_clicks_total), $is_hr ? 'Ukupni klikovi prema Forever odredištima u odabranom periodu.' : 'Total clicks toward Forever destinations in the selected period.'),
                        $build_metric_link('total_funnel_leads_period', $is_hr ? 'Prijave' : 'Sign-ups', nr($funnel_leads_total), $is_hr ? 'Ispunjeni funnel obrasci u istom periodu.' : 'Completed funnel forms in the same period.'),
                    ],
                    'tone' => ($shop_clicks_total >= 20 && $capture_rate < 10) ? 'warning' : ($capture_rate >= 15 ? 'success' : 'info'),
                ],
                [
                    'label' => $is_hr ? 'Dosljednost i jezgra tima' : 'Consistency and core team',
                    'value' => nr($consistency_avg) . '/100',
                    'note' => $is_hr
                        ? nr($qualified_total) . ' kvalificiranih · ' . nr($active_collaborators) . ' aktivnih · ' . nr($active_pro_total) . ' PRO'
                        : nr($qualified_total) . ' qualified · ' . nr($active_collaborators) . ' active · ' . nr($active_pro_total) . ' PRO',
                    'what_it_shows' => $is_hr
                        ? 'Dosljednost pokazuje koliko tim ima redovit ritam rada kroz check-in, plan, provedbu i praćenje rezultata. Jezgra tima pokazuje koliko ljudi stvarno nosi aktivnost i PRO bazu.'
                        : 'How consistent the team is and how large its truly active and monetized core is.',
                    'how_to_use' => $is_hr
                        ? 'Ako je broj nizak, tim ima slab ritam i rezultat teže postaje stabilan. Ako broj raste, znači da više suradnika radi redovito i da tim ima zdraviju bazu za daljnji rast.'
                        : 'Use it to judge whether growth is sustainable, not just currently loud.',
                    'metric_links' => [
                        $build_metric_link('qualified', $is_hr ? 'Kvalificirani' : 'Qualified', nr($qualified_total), $is_hr ? 'Suradnici s dokazanim signalom aktivnosti u zadnjih 90 dana. To je šira jezgra tima koja već pokazuje stvarni interes i smjer.' : 'The core with a proven activity signal in the last 90 days.'),
                        $build_metric_link('active_collaborators', $is_hr ? 'Aktivni' : 'Active', nr($active_collaborators), $is_hr ? 'Suradnici koji su stvarno imali aktivnost u odabranom periodu. To pokazuje koliko jezgra tima trenutno radi, a ne samo postoji.' : 'Collaborators who actually had activity in the selected period.'),
                        $build_metric_link('active_pro_total', $is_hr ? 'PRO' : 'PRO', nr($active_pro_total), $is_hr ? 'Trenutno aktivni PRO računi. To pokazuje koliko jezgra tima ima stvarnu i monetiziranu bazu.' : 'Currently active PRO accounts representing the monetized core.'),
                    ],
                    'tone' => $consistency_avg >= 55 ? 'success' : ($consistency_avg < 45 ? 'warning' : 'info'),
                ],
            ],
            'cards' => [
                [
                    'eyebrow' => $is_hr ? 'Top tržište i izvor' : 'Top market and source',
                    'title' => $top_country_name,
                    'subtitle' => $is_hr
                        ? nr($top_country_total) . ' ukupnih signala · izvor: ' . (string) ($top_source['label'] ?? '-')
                        : nr($top_country_total) . ' total signals · source: ' . (string) ($top_source['label'] ?? '-'),
                    'note' => $is_hr
                        ? 'Posjete ' . nr((int) ($top_country_row['app_visits'] ?? 0)) . ' · app klikovi ' . nr((int) ($top_country_row['app_shop_clicks'] ?? 0)) . ' · blog ' . nr((int) ($top_country_row['blog_clicks'] ?? 0)) . ' · funnel ' . nr((int) ($top_country_row['funnel_registrations'] ?? 0))
                        : 'Visits ' . nr((int) ($top_country_row['app_visits'] ?? 0)) . ' · app clicks ' . nr((int) ($top_country_row['app_shop_clicks'] ?? 0)) . ' · blog ' . nr((int) ($top_country_row['blog_clicks'] ?? 0)) . ' · funnel ' . nr((int) ($top_country_row['funnel_registrations'] ?? 0)),
                    'what_it_shows' => $is_hr
                        ? 'Iz koje zemlje i iz kojeg izvora trenutno dolazi najviše korisnog signala u odabranom periodu.'
                        : 'Which country and source currently produce the most useful signal in the selected period.',
                    'how_to_use' => $is_hr
                        ? 'To je market i izvor koji vrijedi proučiti i pokušati duplicirati na ostatak tima.'
                        : 'This is the market and source worth studying and duplicating across the rest of the team.',
                    'tone' => 'info',
                ],
                [
                    'eyebrow' => $is_hr ? 'Top vrijeme aktivnosti' : 'Top activity window',
                    'title' => (string) ($top_hour['label'] ?? '-'),
                    'subtitle' => $is_hr
                        ? nr((int) ($top_hour['total'] ?? 0)) . ' unique outbound klikova u najjačem satu'
                        : nr((int) ($top_hour['total'] ?? 0)) . ' unique outbound clicks in the strongest hour',
                    'note' => $is_hr
                        ? 'Ovo pokazuje kada tim najčešće vodi promet prema Forever odredištima.'
                        : 'This shows when the team most often drives traffic toward Forever destinations.',
                    'what_it_shows' => $is_hr
                        ? 'Sat u kojem tim najčešće šalje najkorisniji promet prema Forever odredištima.'
                        : 'The hour in which the team most often sends the most useful traffic toward Forever destinations.',
                    'how_to_use' => $is_hr
                        ? 'Koristi za timing objava, follow-upa, webinara i aktiviranja tima.'
                        : 'Use it for timing posts, follow-ups, webinars, and team activation.',
                    'tone' => 'info',
                ],
                [
                    'eyebrow' => $is_hr ? 'Fokus i frikcija tima' : 'Team focus and friction',
                    'title' => (string) ($focus_payload['focus']['label'] ?? '-'),
                    'subtitle' => $is_hr
                        ? 'Frikcija: ' . (string) ($focus_payload['friction']['label'] ?? '-')
                        : 'Friction: ' . (string) ($focus_payload['friction']['label'] ?? '-'),
                    'note' => trim(implode(' · ', array_filter([
                        (string) ($focus_payload['focus']['note'] ?? ''),
                        (string) ($focus_payload['friction']['note'] ?? ''),
                    ]))),
                    'what_it_shows' => $is_hr
                        ? 'Što tim trenutno najviše pokušava postići i koji ga obrazac najviše koči.'
                        : 'What the team is currently trying to achieve most and which pattern is holding it back.',
                    'how_to_use' => $is_hr
                        ? 'To ti daje temu za coaching, internu poruku, webinar ili sljedeću optimizaciju.'
                        : 'This gives you the topic for coaching, an internal message, a webinar, or the next optimization.',
                    'metric_links' => $focus_metric_links,
                    'tone' => 'warning',
                ],
                [
                    'eyebrow' => (string) ($action_payload['eyebrow'] ?? ($is_hr ? 'Admin akcija sada' : 'Admin action now')),
                    'title' => (string) ($action_payload['title'] ?? '-'),
                    'subtitle' => (string) ($action_payload['subtitle'] ?? ''),
                    'note' => trim(implode(' · ', array_filter([
                        (string) ($action_payload['note'] ?? ''),
                        $shop_compare_text !== '' ? ($is_hr ? 'Klikovi: ' : 'Clicks: ') . $shop_compare_text : '',
                        $funnel_compare_text !== '' ? ($is_hr ? 'Funnel: ' : 'Funnel: ') . $funnel_compare_text : '',
                    ]))),
                    'what_it_shows' => $is_hr
                        ? 'Najkorisniji sljedeći potez koji trebaš napraviti na temelju aktualnih LOS signala.'
                        : 'The most useful next move you should make based on the current LOS signals.',
                    'how_to_use' => $is_hr
                        ? 'Ako ovaj tjedan odradiš samo jednu stvar za tim, kreni od ove preporuke.'
                        : 'If you only do one thing for the team this week, start with this recommendation.',
                    'metric_links' => $focus_metric_links,
                    'tone' => (string) ($action_payload['tone'] ?? 'info'),
                ],
            ],
        ];
    }

    private function get_team_momentum_payload(array $rows): array {
        $payload = [
            'hot_streak_total' => 0,
            'growth_ready_total' => 0,
            'top_collaborators' => [],
            'market_champions' => [],
        ];

        if(empty($rows)) {
            return $payload;
        }

        foreach($rows as $row) {
            $growth_percent = $row['growth_percent'] ?? null;
            $consistency_score = (int) ($row['consistency_score'] ?? 0);
            $leader_os_score = (int) ($row['leader_os_score'] ?? 0);
            $shop_clicks = (int) ($row['forever_shop_clicks_period'] ?? 0);

            if($growth_percent !== null && (float) $growth_percent > 0 && $consistency_score >= 60) {
                $payload['hot_streak_total']++;
            }

            if($leader_os_score >= 60 && $shop_clicks >= 5) {
                $payload['growth_ready_total']++;
            }
        }

        $top_collaborators = $rows;
        usort($top_collaborators, static function($a, $b) {
            return (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0))
                ?: ((float) ($b['growth_percent'] ?? -9999) <=> (float) ($a['growth_percent'] ?? -9999))
                ?: (($b['consistency_score'] ?? 0) <=> ($a['consistency_score'] ?? 0))
                ?: (($b['forever_shop_clicks_period'] ?? 0) <=> ($a['forever_shop_clicks_period'] ?? 0));
        });

        foreach(array_slice($top_collaborators, 0, 5) as $row) {
            $payload['top_collaborators'][] = [
                'name' => (string) ($row['name'] ?? l('global.unknown')),
                'detail_url' => (string) ($row['detail_url'] ?? ''),
                'leader_os_score' => (int) ($row['leader_os_score'] ?? 0),
                'growth_percent' => $row['growth_percent'] ?? null,
                'consistency_score' => (int) ($row['consistency_score'] ?? 0),
                'strongest_country' => (string) (($row['strongest_country'] ?? '') !== '' ? $row['strongest_country'] : '-'),
            ];
        }

        $market_champions = array_filter($rows, static function($row) {
            return (int) ($row['forever_shop_clicks_period'] ?? 0) > 0
                || (int) ($row['forever_registration_clicks_period'] ?? 0) > 0;
        });

        usort($market_champions, static function($a, $b) {
            return (($b['forever_shop_clicks_period'] ?? 0) <=> ($a['forever_shop_clicks_period'] ?? 0))
                ?: (($b['forever_registration_clicks_period'] ?? 0) <=> ($a['forever_registration_clicks_period'] ?? 0))
                ?: (($b['strongest_country_count'] ?? 0) <=> ($a['strongest_country_count'] ?? 0))
                ?: (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0));
        });

        foreach(array_slice($market_champions, 0, 5) as $row) {
            $payload['market_champions'][] = [
                'name' => (string) ($row['name'] ?? l('global.unknown')),
                'detail_url' => (string) ($row['detail_url'] ?? ''),
                'country' => (string) (($row['strongest_country'] ?? '') !== '' ? $row['strongest_country'] : '-'),
                'source' => (string) (($row['top_source_label'] ?? '') !== '' ? $row['top_source_label'] : '-'),
                'shop_clicks' => (int) ($row['forever_shop_clicks_period'] ?? 0),
                'registrations' => (int) ($row['forever_registration_clicks_period'] ?? 0),
            ];
        }

        return $payload;
    }

    private function extract_record_datetime($record): ?string {
        if(is_object($record)) {
            $record = (array) $record;
        }

        if(!is_array($record)) {
            return null;
        }

        foreach(['generated_at', 'created_at', 'datetime', 'submitted_at', 'plan_generated_at'] as $key) {
            $value = trim((string) ($record[$key] ?? ''));

            if($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function get_daily_period_map(string $period_start_datetime): array {
        $map = [];
        $cursor = new \DateTimeImmutable(substr($period_start_datetime, 0, 10) . ' 00:00:00');
        $today = new \DateTimeImmutable('today');

        while($cursor <= $today) {
            $date_key = $cursor->format('Y-m-d');
            $map[$date_key] = [
                'date' => $date_key,
                'label' => $cursor->format('d.m.'),
                'clicks' => 0,
                'shop_clicks' => 0,
                'registrations' => 0,
                'leads' => 0,
                'ai_checkins' => 0,
                'blog_forever' => 0,
                'blocked_attempts' => 0,
            ];
            $cursor = $cursor->modify('+1 day');
        }

        return $map;
    }

    private function get_team_signal_chart_payload(string $period_start_datetime, int $period_days): array {
        $labels = [];
        $app_visits = [];
        $app_shop_clicks = [];
        $blog_clicks = [];
        $funnel_registrations = [];

        $period_start = new \DateTimeImmutable($period_start_datetime);
        $date_index = [];

        for($day = 0; $day < $period_days; $day++) {
            $date = $period_start->add(new \DateInterval('P' . $day . 'D'));
            $date_key = $date->format('Y-m-d');
            $date_index[$date_key] = $day;
            $labels[] = $date->format('d.m.');
            $app_visits[] = 0;
            $app_shop_clicks[] = 0;
            $blog_clicks[] = 0;
            $funnel_registrations[] = 0;
        }

        $app_webshop_block_types_sql = $this->get_team_app_webshop_block_types_sql();
        $blog_referral_click_condition = $this->get_team_blog_referral_click_condition_sql('`track_links`');

        $result = database()->query("SELECT
            DATE(`track_links`.`datetime`) AS `date_key`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND `track_links`.`link_id` IS NOT NULL AND `track_links`.`biolink_block_id` IS NULL AND `links`.`type` = 'biolink' THEN 1 ELSE 0 END) AS `app_visits`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND `biolinks_blocks`.`type` IN ({$app_webshop_block_types_sql}) THEN 1 ELSE 0 END) AS `app_shop_clicks`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND {$blog_referral_click_condition} THEN 1 ELSE 0 END) AS `blog_clicks`
        FROM `track_links`
        LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
        LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id`
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `users`.`type` = 0
        GROUP BY DATE(`track_links`.`datetime`)");

        while($row = $result->fetch_assoc()) {
            $date_key = (string) ($row['date_key'] ?? '');

            if($date_key === '' || !isset($date_index[$date_key])) {
                continue;
            }

            $index = $date_index[$date_key];
            $app_visits[$index] = (int) ($row['app_visits'] ?? 0);
            $app_shop_clicks[$index] = (int) ($row['app_shop_clicks'] ?? 0);
            $blog_clicks[$index] = (int) ($row['blog_clicks'] ?? 0);
        }

        if($this->has_funnel_events_table()) {
            $funnel_result = database()->query("SELECT
                DATE(`funnel_events`.`datetime`) AS `date_key`,
                COUNT(*) AS `total`
            FROM `funnel_events`
            LEFT JOIN `users` ON `funnel_events`.`user_id` = `users`.`user_id`
            WHERE `funnel_events`.`datetime` >= '{$period_start_datetime}'
              AND `funnel_events`.`event_type` = 'submit_success'
              AND `users`.`type` = 0
            GROUP BY DATE(`funnel_events`.`datetime`)");

            while($funnel_row = $funnel_result->fetch_assoc()) {
                $date_key = (string) ($funnel_row['date_key'] ?? '');

                if($date_key === '' || !isset($date_index[$date_key])) {
                    continue;
                }

                $index = $date_index[$date_key];
                $funnel_registrations[$index] = (int) ($funnel_row['total'] ?? 0);
            }
        }

        return [
            'labels' => $labels,
            'app_visits' => $app_visits,
            'app_shop_clicks' => $app_shop_clicks,
            'blog_clicks' => $blog_clicks,
            'funnel_registrations' => $funnel_registrations,
        ];
    }

    private function get_team_country_signal_matrix_payload(int $period_days, string $period_key): array {
        $period_start_datetime = $this->get_period_start_datetime($period_days);
        $app_webshop_block_types_sql = $this->get_team_app_webshop_block_types_sql();
        $blog_referral_click_condition = $this->get_team_blog_referral_click_condition_sql('`track_links`');
        $rows_map = [];

        $clicks_result = database()->query("SELECT
            UPPER(TRIM(COALESCE(`track_links`.`country_code`, ''))) AS `country_code`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND `track_links`.`link_id` IS NOT NULL AND `track_links`.`biolink_block_id` IS NULL AND `links`.`type` = 'biolink' THEN 1 ELSE 0 END) AS `app_visits`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND `biolinks_blocks`.`type` IN ({$app_webshop_block_types_sql}) THEN 1 ELSE 0 END) AS `app_shop_clicks`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND {$blog_referral_click_condition} THEN 1 ELSE 0 END) AS `blog_clicks`
        FROM `track_links`
        LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
        LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id`
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `users`.`type` = 0
        GROUP BY `country_code`");

        while($row = $clicks_result->fetch_object()) {
            $country_code = $this->get_country_table_key($row->country_code ?? '');

            $rows_map[$country_code] = [
                'country_code' => $country_code === '__unknown__' ? '' : $country_code,
                'country_name' => $this->get_country_table_name($country_code),
                'app_visits' => (int) ($row->app_visits ?? 0),
                'app_shop_clicks' => (int) ($row->app_shop_clicks ?? 0),
                'blog_clicks' => (int) ($row->blog_clicks ?? 0),
                'funnel_registrations' => 0,
            ];
        }

        if($this->has_funnel_events_table()) {
            $funnel_result = database()->query("SELECT
                UPPER(TRIM(COALESCE(`funnel_events`.`country_code`, ''))) AS `country_code`,
                COUNT(*) AS `total`
            FROM `funnel_events`
            LEFT JOIN `users` ON `funnel_events`.`user_id` = `users`.`user_id`
            WHERE `funnel_events`.`datetime` >= '{$period_start_datetime}'
              AND `funnel_events`.`event_type` = 'submit_success'
              AND `users`.`type` = 0
            GROUP BY `country_code`");

            while($row = $funnel_result->fetch_object()) {
                $country_code = $this->get_country_table_key($row->country_code ?? '');

                if(!isset($rows_map[$country_code])) {
                    $rows_map[$country_code] = [
                        'country_code' => $country_code === '__unknown__' ? '' : $country_code,
                        'country_name' => $this->get_country_table_name($country_code),
                        'app_visits' => 0,
                        'app_shop_clicks' => 0,
                        'blog_clicks' => 0,
                        'funnel_registrations' => 0,
                    ];
                }

                $rows_map[$country_code]['funnel_registrations'] = (int) ($row->total ?? 0);
            }
        }

        $rows = array_values(array_filter($rows_map, static function(array $row) {
            return ((int) ($row['app_visits'] ?? 0)
                + (int) ($row['app_shop_clicks'] ?? 0)
                + (int) ($row['blog_clicks'] ?? 0)
                + (int) ($row['funnel_registrations'] ?? 0)) > 0;
        }));

        usort($rows, static function(array $a, array $b) {
            $total_a = (int) ($a['app_visits'] ?? 0) + (int) ($a['app_shop_clicks'] ?? 0) + (int) ($a['blog_clicks'] ?? 0) + (int) ($a['funnel_registrations'] ?? 0);
            $total_b = (int) ($b['app_visits'] ?? 0) + (int) ($b['app_shop_clicks'] ?? 0) + (int) ($b['blog_clicks'] ?? 0) + (int) ($b['funnel_registrations'] ?? 0);

            return ($total_b <=> $total_a)
                ?: (($b['app_shop_clicks'] ?? 0) <=> ($a['app_shop_clicks'] ?? 0))
                ?: (($b['blog_clicks'] ?? 0) <=> ($a['blog_clicks'] ?? 0))
                ?: (($a['country_name'] ?? '') <=> ($b['country_name'] ?? ''));
        });

        return [
            'period_key' => $period_key,
            'period_days' => $period_days,
            'rows' => $rows,
            'totals' => [
                'app_visits' => array_sum(array_column($rows, 'app_visits')),
                'app_shop_clicks' => array_sum(array_column($rows, 'app_shop_clicks')),
                'blog_clicks' => array_sum(array_column($rows, 'blog_clicks')),
                'funnel_registrations' => array_sum(array_column($rows, 'funnel_registrations')),
            ],
        ];
    }

    private function get_team_country_signal_matrix_periods_payload(): array {
        $payload = [];

        foreach(['1d' => 1, '7d' => 7, '30d' => 30, '90d' => 90] as $period_key => $period_days) {
            $payload[$period_key] = $this->get_team_country_signal_matrix_payload($period_days, $period_key);
        }

        return $payload;
    }

    private function get_team_trend_payload(string $period_start_datetime, string $period_key, array $rows, array $biolink_sets): array {
        $payload = [
            'rows' => [],
            'max_value' => 0,
            'summary_drilldowns' => [],
        ];

        $trend_rows = $this->get_daily_period_map($period_start_datetime);
        $shop_condition = \Altum\Link::get_forever_shop_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql']);
        $registration_condition = \Altum\Link::get_forever_registration_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_registration_block_types_sql']);
        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);
        $blog_mediums = $this->get_blog_cta_mediums();
        $product_medium = db()->escape($blog_mediums['product']);
        $business_medium = db()->escape($blog_mediums['business']);

        $result = database()->query("SELECT
            DATE(`track_links`.`datetime`) AS `trend_date`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND {$outbound_condition} THEN 1 ELSE 0 END) AS `clicks`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND {$shop_condition} THEN 1 ELSE 0 END) AS `shop_clicks`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND {$registration_condition} THEN 1 ELSE 0 END) AS `registrations`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND `track_links`.`utm_medium` IN ('{$product_medium}', '{$business_medium}') THEN 1 ELSE 0 END) AS `blog_forever`
        FROM `track_links`
        LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `users`.`type` = 0
        GROUP BY `trend_date`
        ORDER BY `trend_date` ASC");

        while($row = $result->fetch_assoc()) {
            $date_key = (string) ($row['trend_date'] ?? '');

            if($date_key === '' || !isset($trend_rows[$date_key])) {
                continue;
            }

            $trend_rows[$date_key]['clicks'] = (int) ($row['clicks'] ?? 0);
            $trend_rows[$date_key]['shop_clicks'] = (int) ($row['shop_clicks'] ?? 0);
            $trend_rows[$date_key]['registrations'] = (int) ($row['registrations'] ?? 0);
            $trend_rows[$date_key]['blog_forever'] = (int) ($row['blog_forever'] ?? 0);
        }

        $lead_result = database()->query("SELECT
            DATE(`data`.`datetime`) AS `trend_date`,
            COUNT(*) AS `leads`
        FROM `data`
        LEFT JOIN `users` ON `data`.`user_id` = `users`.`user_id`
        WHERE `data`.`datetime` >= '{$period_start_datetime}'
          AND `data`.`type` = 'lead_funnel'
          AND `users`.`type` = 0
        GROUP BY `trend_date`
        ORDER BY `trend_date` ASC");

        while($row = $lead_result->fetch_assoc()) {
            $date_key = (string) ($row['trend_date'] ?? '');

            if($date_key === '' || !isset($trend_rows[$date_key])) {
                continue;
            }

            $trend_rows[$date_key]['leads'] = (int) ($row['leads'] ?? 0);
        }

        foreach($rows as $row) {
            $preferences = $this->get_preferences_object($row['preferences'] ?? null);
            $checkins = $preferences->leader_ai_weekly_checkins ?? [];

            if(is_object($checkins)) {
                $checkins = (array) $checkins;
            }

            if(!is_array($checkins)) {
                continue;
            }

            foreach($checkins as $checkin) {
                $datetime = $this->extract_record_datetime($checkin);

                if($datetime === null || $datetime < $period_start_datetime) {
                    continue;
                }

                $date_key = substr($datetime, 0, 10);
                if(isset($trend_rows[$date_key])) {
                    $trend_rows[$date_key]['ai_checkins']++;
                }
            }
        }

        if(function_exists('fc_ensure_forever_click_integrity_tables')) {
            fc_ensure_forever_click_integrity_tables();

            $suspicious_result = database()->query("SELECT DATE(`datetime`) AS `trend_date`, COUNT(*) AS `blocked_attempts`
                FROM `forever_click_integrity_suspicious`
                WHERE `datetime` >= '{$period_start_datetime}'
                GROUP BY `trend_date`
                ORDER BY `trend_date` ASC");

            while($row = $suspicious_result->fetch_assoc()) {
                $date_key = (string) ($row['trend_date'] ?? '');

                if($date_key === '' || !isset($trend_rows[$date_key])) {
                    continue;
                }

                $trend_rows[$date_key]['blocked_attempts'] = (int) ($row['blocked_attempts'] ?? 0);
            }
        }

        $payload['rows'] = array_values($trend_rows);
        foreach($payload['rows'] as $row) {
            $payload['max_value'] = max(
                $payload['max_value'],
                (int) ($row['clicks'] ?? 0),
                (int) ($row['shop_clicks'] ?? 0),
                (int) ($row['registrations'] ?? 0),
                (int) ($row['leads'] ?? 0),
                (int) ($row['ai_checkins'] ?? 0),
                (int) ($row['blog_forever'] ?? 0),
                (int) ($row['blocked_attempts'] ?? 0)
            );
        }

        $payload['summary_drilldowns'] = $this->get_team_trend_summary_drilldowns_payload($rows);

        return $payload;
    }

    private function get_team_trend_summary_drilldowns_payload(array $rows): array {
        $ranges = [
            7 => (new \DateTimeImmutable('today'))->modify('-6 days')->format('Y-m-d 00:00:00'),
            30 => (new \DateTimeImmutable('today'))->modify('-29 days')->format('Y-m-d 00:00:00'),
            90 => (new \DateTimeImmutable('today'))->modify('-89 days')->format('Y-m-d 00:00:00'),
        ];

        $payload = [];
        $clickCountsByRange = [
            7 => [],
            30 => [],
            90 => [],
        ];
        $registrationCountsByRange = [
            7 => [],
            30 => [],
            90 => [],
        ];
        $leadCountsByRange = [
            7 => [],
            30 => [],
            90 => [],
        ];
        $blogCountsByRange = [
            7 => [],
            30 => [],
            90 => [],
        ];
        $blog_mediums = $this->get_blog_cta_mediums();
        $product_medium = db()->escape($blog_mediums['product']);
        $business_medium = db()->escape($blog_mediums['business']);
        $biolink_sets = $this->get_biolink_sets();
        $shop_condition = \Altum\Link::get_forever_shop_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql']);
        $registration_condition = \Altum\Link::get_forever_registration_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_registration_block_types_sql']);
        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);
        $start_7d = db()->escape($ranges[7]);
        $start_30d = db()->escape($ranges[30]);
        $start_90d = db()->escape($ranges[90]);

        $click_result = database()->query("SELECT
                `track_links`.`user_id`,
                SUM(CASE WHEN `track_links`.`datetime` >= '{$start_7d}' AND `track_links`.`is_unique` = 1 AND {$outbound_condition} THEN 1 ELSE 0 END) AS `clicks_7d`,
                SUM(CASE WHEN `track_links`.`datetime` >= '{$start_30d}' AND `track_links`.`is_unique` = 1 AND {$outbound_condition} THEN 1 ELSE 0 END) AS `clicks_30d`,
                SUM(CASE WHEN `track_links`.`datetime` >= '{$start_90d}' AND `track_links`.`is_unique` = 1 AND {$outbound_condition} THEN 1 ELSE 0 END) AS `clicks_90d`,
                SUM(CASE WHEN `track_links`.`datetime` >= '{$start_7d}' AND `track_links`.`is_unique` = 1 AND {$registration_condition} THEN 1 ELSE 0 END) AS `registrations_7d`,
                SUM(CASE WHEN `track_links`.`datetime` >= '{$start_30d}' AND `track_links`.`is_unique` = 1 AND {$registration_condition} THEN 1 ELSE 0 END) AS `registrations_30d`,
                SUM(CASE WHEN `track_links`.`datetime` >= '{$start_90d}' AND `track_links`.`is_unique` = 1 AND {$registration_condition} THEN 1 ELSE 0 END) AS `registrations_90d`,
                SUM(CASE WHEN `track_links`.`datetime` >= '{$start_7d}' AND `track_links`.`is_unique` = 1 AND `track_links`.`utm_medium` IN ('{$product_medium}', '{$business_medium}') THEN 1 ELSE 0 END) AS `blog_7d`,
                SUM(CASE WHEN `track_links`.`datetime` >= '{$start_30d}' AND `track_links`.`is_unique` = 1 AND `track_links`.`utm_medium` IN ('{$product_medium}', '{$business_medium}') THEN 1 ELSE 0 END) AS `blog_30d`,
                SUM(CASE WHEN `track_links`.`datetime` >= '{$start_90d}' AND `track_links`.`is_unique` = 1 AND `track_links`.`utm_medium` IN ('{$product_medium}', '{$business_medium}') THEN 1 ELSE 0 END) AS `blog_90d`
            FROM `track_links`
            LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`datetime` >= '{$start_90d}'
              AND `users`.`type` = 0
            GROUP BY `track_links`.`user_id`");

        while($click_row = $click_result->fetch_assoc()) {
            $user_id = (int) ($click_row['user_id'] ?? 0);

            if($user_id <= 0) {
                continue;
            }

            foreach([7, 30, 90] as $days) {
                $click_total = (int) ($click_row['clicks_' . $days . 'd'] ?? 0);
                $registration_total = (int) ($click_row['registrations_' . $days . 'd'] ?? 0);
                $blog_total = (int) ($click_row['blog_' . $days . 'd'] ?? 0);

                if($click_total > 0) {
                    $clickCountsByRange[$days][$user_id] = $click_total;
                }

                if($registration_total > 0) {
                    $registrationCountsByRange[$days][$user_id] = $registration_total;
                }

                if($blog_total > 0) {
                    $blogCountsByRange[$days][$user_id] = $blog_total;
                }
            }
        }

        foreach($ranges as $days => $start_datetime) {
            $escaped_start_datetime = db()->escape($start_datetime);
            $lead_result = database()->query("SELECT `data`.`user_id`, COUNT(*) AS `lead_total`
                FROM `data`
                LEFT JOIN `users` ON `data`.`user_id` = `users`.`user_id`
                WHERE `data`.`datetime` >= '{$escaped_start_datetime}'
                  AND `data`.`type` = 'lead_funnel'
                  AND `users`.`type` = 0
                GROUP BY `data`.`user_id`");

            while($lead_row = $lead_result->fetch_assoc()) {
                $leadCountsByRange[$days][(int) ($lead_row['user_id'] ?? 0)] = (int) ($lead_row['lead_total'] ?? 0);
            }

        }

        foreach($ranges as $days => $start_datetime) {
            $click_total = array_sum($clickCountsByRange[$days]);
            $registration_total = array_sum($registrationCountsByRange[$days]);
            $lead_total = array_sum($leadCountsByRange[$days]);
            $blog_total = array_sum($blogCountsByRange[$days]);

            $payload[(string) $days] = [
                'clicks' => [
                    'title' => 'Klikovi prema Foreveru · zadnjih ' . $days . ' dana',
                    'summary_label' => 'Klikovi prema Foreveru',
                    'summary_value' => nr($click_total),
                    'summary_note' => 'Suradnika u signalu: ' . nr(count($clickCountsByRange[$days])) . ' · Klik na ime otvara detalj suradnika.',
                    'items' => $this->map_drilldown_items(
                        $rows,
                        function($row) use ($clickCountsByRange, $days) {
                            return (int) ($clickCountsByRange[$days][(int) ($row['user_id'] ?? 0)] ?? 0);
                        },
                        function($row) use ($clickCountsByRange, $days) {
                            return (int) ($clickCountsByRange[$days][(int) ($row['user_id'] ?? 0)] ?? 0) > 0;
                        },
                        40,
                        static fn($row, $metric) => 'Klikovi ' . nr((int) $metric)
                    ),
                ],
                'registrations' => [
                    'title' => 'Registracije · zadnjih ' . $days . ' dana',
                    'summary_label' => 'Registracije',
                    'summary_value' => nr($registration_total),
                    'summary_note' => 'Suradnika u signalu: ' . nr(count($registrationCountsByRange[$days])) . ' · Klik na ime otvara detalj suradnika.',
                    'items' => $this->map_drilldown_items(
                        $rows,
                        function($row) use ($registrationCountsByRange, $days) {
                            return (int) ($registrationCountsByRange[$days][(int) ($row['user_id'] ?? 0)] ?? 0);
                        },
                        function($row) use ($registrationCountsByRange, $days) {
                            return (int) ($registrationCountsByRange[$days][(int) ($row['user_id'] ?? 0)] ?? 0) > 0;
                        },
                        40,
                        static fn($row, $metric) => 'Registracije ' . nr((int) $metric)
                    ),
                ],
                'leads' => [
                    'title' => 'Leadovi · zadnjih ' . $days . ' dana',
                    'summary_label' => 'Leadovi',
                    'summary_value' => nr($lead_total),
                    'summary_note' => 'Suradnika u signalu: ' . nr(count($leadCountsByRange[$days])) . ' · Klik na ime otvara detalj suradnika.',
                    'items' => $this->map_drilldown_items(
                        $rows,
                        function($row) use ($leadCountsByRange, $days) {
                            return (int) ($leadCountsByRange[$days][(int) ($row['user_id'] ?? 0)] ?? 0);
                        },
                        function($row) use ($leadCountsByRange, $days) {
                            return (int) ($leadCountsByRange[$days][(int) ($row['user_id'] ?? 0)] ?? 0) > 0;
                        },
                        40,
                        static fn($row, $metric) => 'Leadovi ' . nr((int) $metric)
                    ),
                ],
                'blog_forever' => [
                    'title' => 'Blog -> Forever · zadnjih ' . $days . ' dana',
                    'summary_label' => 'Blog -> Forever',
                    'summary_value' => nr($blog_total),
                    'summary_note' => 'Suradnika u signalu: ' . nr(count($blogCountsByRange[$days])) . ' · Klik na ime otvara detalj suradnika.',
                    'items' => $this->map_drilldown_items(
                        $rows,
                        function($row) use ($blogCountsByRange, $days) {
                            return (int) ($blogCountsByRange[$days][(int) ($row['user_id'] ?? 0)] ?? 0);
                        },
                        function($row) use ($blogCountsByRange, $days) {
                            return (int) ($blogCountsByRange[$days][(int) ($row['user_id'] ?? 0)] ?? 0) > 0;
                        },
                        40,
                        static fn($row, $metric) => 'Blog klikovi ' . nr((int) $metric)
                    ),
                ],
            ];
        }

        return $payload;
    }

    private function get_status_distribution_payload(array $rows): array {
        $ranges = [
            7 => (new \DateTimeImmutable('today'))->modify('-6 days')->format('Y-m-d 00:00:00'),
            30 => (new \DateTimeImmutable('today'))->modify('-29 days')->format('Y-m-d 00:00:00'),
            90 => (new \DateTimeImmutable('today'))->modify('-89 days')->format('Y-m-d 00:00:00'),
        ];

        $status_descriptions = [
            'inactive' => 'Nema klik prema Foreveru u odabranom periodu.',
            'stable' => 'Ima aktivnost, ali bez jačeg rasta ili izraženog rizika.',
            'rising' => 'Pokazuje zdrav rast i spreman je za jači fokus.',
            'high_potential' => 'Ima potencijal, ali još nije pretvorio signal u puni rezultat.',
            'risk' => 'Traži coaching, praćenje ili operativni zahvat.',
        ];
        $range_payload = [];

        foreach($ranges as $days => $start_datetime) {
            $buckets = [
                'inactive' => 0,
                'stable' => 0,
                'rising' => 0,
                'high_potential' => 0,
                'risk' => 0,
            ];
            $bucket_rows = [
                'inactive' => [],
                'stable' => [],
                'rising' => [],
                'high_potential' => [],
                'risk' => [],
            ];

            foreach($rows as $row) {
                $status_key = (string) ($row['status_key'] ?? 'stable');
                $last_click_at = (string) ($row['last_click_at'] ?? '');

                if($last_click_at === '' || $last_click_at < $start_datetime) {
                    $status_key = 'inactive';
                }

                $buckets[$status_key] = ($buckets[$status_key] ?? 0) + 1;
                $bucket_rows[$status_key][] = $row;
            }

            $items = [];
            $drilldowns = [];
            foreach($buckets as $status_key => $total) {
                $share = !empty($rows) ? round(($total / max(1, count($rows))) * 100, 1) : 0;
                $label = l('admin_leader_operating_system.status.' . $status_key);
                $items[] = [
                    'key' => $status_key,
                    'label' => $label,
                    'total' => (int) $total,
                    'share' => $share,
                    'description' => (string) ($status_descriptions[$status_key] ?? ''),
                ];

                $drilldown_metric_resolver = match($status_key) {
                    'rising' => static fn($row) => (float) ($row['growth_percent'] ?? 0),
                    'high_potential' => static fn($row) => (int) ($row['opportunity_score'] ?? 0),
                    'risk' => static fn($row) => (int) ($row['risk_score'] ?? 0),
                    'inactive' => static fn($row) => (int) ($row['forever_shop_clicks_90d'] ?? 0),
                    default => static fn($row) => (int) ($row['leader_os_score'] ?? 0),
                };

                $drilldown_metric_display_resolver = match($status_key) {
                    'rising' => static fn($row, $metric) => (($metric > 0 ? '+' : '') . nr((float) $metric) . '% rast'),
                    'high_potential' => static fn($row, $metric) => 'Prilika ' . nr((int) $metric),
                    'risk' => static fn($row, $metric) => 'Rizik ' . nr((int) $metric),
                    'inactive' => static fn($row, $metric) => 'Shop 90d ' . nr((int) $metric),
                    default => static fn($row, $metric) => 'LOS ' . nr((int) $metric),
                };

                $drilldowns[$status_key] = [
                    'title' => $label . ' · zadnjih ' . $days . ' dana',
                    'summary_label' => $label,
                    'summary_value' => nr($total),
                    'summary_note' => trim(implode(' · ', array_filter([
                        (string) ($status_descriptions[$status_key] ?? ''),
                        'Udio u timu: ' . nr($share) . '%',
                        'Klik na ime otvara detalj suradnika.',
                    ]))),
                    'items' => $this->map_drilldown_items($bucket_rows[$status_key], $drilldown_metric_resolver, null, 40, $drilldown_metric_display_resolver),
                ];
            }

            $active_core_total = (int) (($buckets['stable'] ?? 0) + ($buckets['rising'] ?? 0) + ($buckets['high_potential'] ?? 0) + ($buckets['risk'] ?? 0));
            $growth_pool_total = (int) (($buckets['rising'] ?? 0) + ($buckets['high_potential'] ?? 0));
            $risk_total = (int) ($buckets['risk'] ?? 0);
            $inactive_total = (int) ($buckets['inactive'] ?? 0);
            $all_total = max(1, count($rows));
            $active_core_rows = array_merge($bucket_rows['stable'], $bucket_rows['rising'], $bucket_rows['high_potential'], $bucket_rows['risk']);
            $growth_pool_rows = array_merge($bucket_rows['rising'], $bucket_rows['high_potential']);

            $range_payload[(string) $days] = [
                'items' => $items,
                'drilldowns' => $drilldowns,
                'insights' => [
                    'active_core' => [
                        'label' => 'Aktivna jezgra',
                        'total' => $active_core_total,
                        'share' => round(($active_core_total / $all_total) * 100, 1),
                        'description' => 'Suradnici koji imaju neki aktualni signal rada u promatranom periodu.',
                    ],
                    'growth_pool' => [
                        'label' => 'Rast + potencijal',
                        'total' => $growth_pool_total,
                        'share' => round(($growth_pool_total / $all_total) * 100, 1),
                        'description' => 'Suradnici koji su najbliže zdravom rastu ili već pokazuju momentum.',
                    ],
                    'risk' => [
                        'label' => 'Treba pažnju',
                        'total' => $risk_total,
                        'share' => round(($risk_total / $all_total) * 100, 1),
                        'description' => 'Suradnici kojima sada najviše treba coaching ili operativni zahvat.',
                    ],
                    'inactive' => [
                        'label' => 'Neaktivni',
                        'total' => $inactive_total,
                        'share' => round(($inactive_total / $all_total) * 100, 1),
                        'description' => 'Suradnici bez klika prema Foreveru u promatranom periodu.',
                    ],
                ],
                'summary_drilldowns' => [
                    'active_core' => [
                        'title' => 'Aktivna jezgra · zadnjih ' . $days . ' dana',
                        'summary_label' => 'Aktivna jezgra',
                        'summary_value' => nr($active_core_total),
                        'summary_note' => 'Udio u timu: ' . nr(round(($active_core_total / $all_total) * 100, 1)) . '% · Suradnici s aktualnim signalom rada u promatranom periodu.',
                        'items' => $this->map_drilldown_items($active_core_rows, static fn($row) => (int) ($row['leader_os_score'] ?? 0), null, 40, static fn($row, $metric) => 'LOS ' . nr((int) $metric)),
                    ],
                    'growth_pool' => [
                        'title' => 'Rast + potencijal · zadnjih ' . $days . ' dana',
                        'summary_label' => 'Rast + potencijal',
                        'summary_value' => nr($growth_pool_total),
                        'summary_note' => 'Udio u timu: ' . nr(round(($growth_pool_total / $all_total) * 100, 1)) . '% · Suradnici najbliži zdravom rastu ili skaliranju.',
                        'items' => $this->map_drilldown_items($growth_pool_rows, static fn($row) => (float) (($row['growth_percent'] ?? null) ?? ($row['opportunity_score'] ?? 0)), null, 40, static fn($row, $metric) => (($row['growth_percent'] ?? null) !== null ? (($metric > 0 ? '+' : '') . nr((float) $metric) . '% rast') : ('Prilika ' . nr((int) $metric)))),
                    ],
                    'risk' => [
                        'title' => 'Treba pažnju · zadnjih ' . $days . ' dana',
                        'summary_label' => 'Treba pažnju',
                        'summary_value' => nr($risk_total),
                        'summary_note' => 'Udio u timu: ' . nr(round(($risk_total / $all_total) * 100, 1)) . '% · Suradnici kojima sada najviše treba coaching ili operativni zahvat.',
                        'items' => $this->map_drilldown_items($bucket_rows['risk'], static fn($row) => (int) ($row['risk_score'] ?? 0), null, 40, static fn($row, $metric) => 'Rizik ' . nr((int) $metric)),
                    ],
                    'inactive' => [
                        'title' => 'Neaktivni · zadnjih ' . $days . ' dana',
                        'summary_label' => 'Neaktivni',
                        'summary_value' => nr($inactive_total),
                        'summary_note' => 'Udio u timu: ' . nr(round(($inactive_total / $all_total) * 100, 1)) . '% · Suradnici bez klika prema Foreveru u promatranom periodu.',
                        'items' => $this->map_drilldown_items($bucket_rows['inactive'], static fn($row) => (int) ($row['forever_shop_clicks_90d'] ?? 0), null, 40, static fn($row, $metric) => 'Shop 90d ' . nr((int) $metric)),
                    ],
                ],
            ];
        }

        return [
            'default' => $range_payload['30']['items'] ?? [],
            'ranges' => $range_payload,
        ];
    }

    private function map_leaderboard_rows(array $rows, string $metric_key, int $limit = 5): array {
        $result = [];

        foreach(array_slice($rows, 0, $limit) as $row) {
            $result[] = [
                'name' => (string) ($row['name'] ?? l('global.unknown')),
                'detail_url' => (string) ($row['detail_url'] ?? ''),
                'metric' => $row[$metric_key] ?? 0,
                'leader_os_score' => (int) ($row['leader_os_score'] ?? 0),
                'status_label' => (string) ($row['status_label'] ?? ''),
            ];
        }

        return $result;
    }

    private function get_team_leaderboards_payload(array $rows): array {
        $payload = [
            'top_by_score' => [],
            'top_by_growth' => [],
            'top_by_registrations' => [],
            'top_by_funnel_leads' => [],
            'top_by_app_quality' => [],
            'top_by_consistency' => [],
            'top_by_risk' => [],
            'top_by_opportunity' => [],
        ];

        if(empty($rows)) {
            return $payload;
        }

        $score_rows = $rows;
        usort($score_rows, static fn($a, $b) => (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0)) ?: (($b['performance_score'] ?? 0) <=> ($a['performance_score'] ?? 0)));
        $payload['top_by_score'] = $this->map_leaderboard_rows($score_rows, 'leader_os_score');

        $growth_rows = array_values(array_filter($rows, static fn($row) => ($row['growth_percent'] ?? null) !== null));
        usort($growth_rows, static fn($a, $b) => ((float) ($b['growth_percent'] ?? -9999) <=> (float) ($a['growth_percent'] ?? -9999)) ?: (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0)));
        $payload['top_by_growth'] = $this->map_leaderboard_rows($growth_rows, 'growth_percent');

        $registration_rows = $rows;
        usort($registration_rows, static fn($a, $b) => (($b['forever_registration_clicks_period'] ?? 0) <=> ($a['forever_registration_clicks_period'] ?? 0)) ?: (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0)));
        $payload['top_by_registrations'] = $this->map_leaderboard_rows($registration_rows, 'forever_registration_clicks_period');

        $lead_rows = $rows;
        usort($lead_rows, static fn($a, $b) => (($b['app_funnel_registrations_period'] ?? 0) <=> ($a['app_funnel_registrations_period'] ?? 0)) ?: (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0)));
        $payload['top_by_funnel_leads'] = $this->map_leaderboard_rows($lead_rows, 'app_funnel_registrations_period');

        $app_quality_rows = $rows;
        usort($app_quality_rows, static fn($a, $b) => (($b['app_quality_score'] ?? 0) <=> ($a['app_quality_score'] ?? 0)) ?: (($b['app_signal_score'] ?? 0) <=> ($a['app_signal_score'] ?? 0)));
        $payload['top_by_app_quality'] = $this->map_leaderboard_rows($app_quality_rows, 'app_quality_score');

        $consistency_rows = $rows;
        usort($consistency_rows, static fn($a, $b) => (($b['consistency_score'] ?? 0) <=> ($a['consistency_score'] ?? 0)) ?: (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0)));
        $payload['top_by_consistency'] = $this->map_leaderboard_rows($consistency_rows, 'consistency_score');

        $risk_rows = $rows;
        usort($risk_rows, static fn($a, $b) => (($b['risk_score'] ?? 0) <=> ($a['risk_score'] ?? 0)) ?: (($b['anomaly_score'] ?? 0) <=> ($a['anomaly_score'] ?? 0)));
        $payload['top_by_risk'] = $this->map_leaderboard_rows($risk_rows, 'risk_score');

        $opportunity_rows = $rows;
        usort($opportunity_rows, static fn($a, $b) => (($b['opportunity_score'] ?? 0) <=> ($a['opportunity_score'] ?? 0)) ?: (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0)));
        $payload['top_by_opportunity'] = $this->map_leaderboard_rows($opportunity_rows, 'opportunity_score');

        return $payload;
    }

    private function get_countries_matrix_payload(string $period_start_datetime, array $biolink_sets): array {
        $payload = [
            'rows' => [],
        ];

        $country_map = [];
        $shop_condition = \Altum\Link::get_forever_shop_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql']);
        $registration_condition = \Altum\Link::get_forever_registration_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_registration_block_types_sql']);
        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);

        $result = database()->query("SELECT
            `track_links`.`user_id`,
            `track_links`.`country_code`,
            `track_links`.`utm_source`,
            `track_links`.`utm_medium`,
            `track_links`.`referrer_host`,
            CASE WHEN `track_links`.`is_unique` = 1 AND {$outbound_condition} THEN 1 ELSE 0 END AS `is_click`,
            CASE WHEN `track_links`.`is_unique` = 1 AND {$registration_condition} THEN 1 ELSE 0 END AS `is_registration`
        FROM `track_links`
        LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `users`.`type` = 0
          AND {$outbound_condition}");

        while($row = $result->fetch_assoc()) {
            $country_code = strtoupper(trim((string) ($row['country_code'] ?? '')));

            if($country_code === '') {
                continue;
            }

            if(!isset($country_map[$country_code])) {
                $country_map[$country_code] = [
                    'country' => $country_code,
                    'clicks' => 0,
                    'registrations' => 0,
                    'active_collaborators_map' => [],
                    'sources' => [],
                ];
            }

            $country_map[$country_code]['clicks'] += (int) ($row['is_click'] ?? 0);
            $country_map[$country_code]['registrations'] += (int) ($row['is_registration'] ?? 0);
            $country_map[$country_code]['active_collaborators_map'][(int) ($row['user_id'] ?? 0)] = true;
            $source_label = $this->get_source_label($row);
            $country_map[$country_code]['sources'][$source_label] = ($country_map[$country_code]['sources'][$source_label] ?? 0) + 1;
        }

        foreach($country_map as $country_code => $country_row) {
            arsort($country_row['sources']);
            $payload['rows'][] = [
                'country' => $country_code,
                'clicks' => (int) ($country_row['clicks'] ?? 0),
                'registrations' => (int) ($country_row['registrations'] ?? 0),
                'conversion_rate' => (int) ($country_row['clicks'] ?? 0) > 0 ? round(((int) ($country_row['registrations'] ?? 0) / (int) ($country_row['clicks'] ?? 0)) * 100, 1) : 0,
                'active_collaborators' => count($country_row['active_collaborators_map'] ?? []),
                'top_source' => !empty($country_row['sources']) ? (string) array_key_first($country_row['sources']) : '-',
            ];
        }

        usort($payload['rows'], static fn($a, $b) => (($b['clicks'] ?? 0) <=> ($a['clicks'] ?? 0)) ?: (($b['registrations'] ?? 0) <=> ($a['registrations'] ?? 0)));
        $payload['rows'] = array_slice($payload['rows'], 0, 8);

        return $payload;
    }

    private function get_activity_heatmap_payload(string $period_start_datetime, array $rows, array $biolink_sets): array {
        $day_labels = ['Mon' => 'Pon', 'Tue' => 'Uto', 'Wed' => 'Sri', 'Thu' => 'Cet', 'Fri' => 'Pet', 'Sat' => 'Sub', 'Sun' => 'Ned'];
        $hour_labels = [];
        for($hour = 0; $hour < 24; $hour++) {
            $hour_labels[] = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
        }

        $heatmap = [];
        foreach($day_labels as $day_key => $day_label) {
            $heatmap[$day_key] = [];
            foreach($hour_labels as $hour_label) {
                $heatmap[$day_key][$hour_label] = [
                    'clicks' => 0,
                    'leads' => 0,
                    'ai_checkins' => 0,
                ];
            }
        }

        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);
        $click_result = database()->query("SELECT `track_links`.`datetime`
            FROM `track_links`
            LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
              AND `track_links`.`is_unique` = 1
              AND `users`.`type` = 0
              AND {$outbound_condition}");

        while($row = $click_result->fetch_assoc()) {
            $datetime = (string) ($row['datetime'] ?? '');
            if($datetime === '') continue;
            $day_key = date('D', strtotime($datetime));
            $hour_key = substr($datetime, 11, 2);
            if(isset($heatmap[$day_key][$hour_key])) {
                $heatmap[$day_key][$hour_key]['clicks']++;
            }
        }

        $lead_result = database()->query("SELECT `data`.`datetime`
            FROM `data`
            LEFT JOIN `users` ON `data`.`user_id` = `users`.`user_id`
            WHERE `data`.`datetime` >= '{$period_start_datetime}'
              AND `data`.`type` = 'lead_funnel'
              AND `users`.`type` = 0");

        while($row = $lead_result->fetch_assoc()) {
            $datetime = (string) ($row['datetime'] ?? '');
            if($datetime === '') continue;
            $day_key = date('D', strtotime($datetime));
            $hour_key = substr($datetime, 11, 2);
            if(isset($heatmap[$day_key][$hour_key])) {
                $heatmap[$day_key][$hour_key]['leads']++;
            }
        }

        foreach($rows as $row) {
            $preferences = $this->get_preferences_object($row['preferences'] ?? null);
            $checkins = $preferences->leader_ai_weekly_checkins ?? [];

            if(is_object($checkins)) {
                $checkins = (array) $checkins;
            }

            if(!is_array($checkins)) {
                continue;
            }

            foreach($checkins as $checkin) {
                $datetime = $this->extract_record_datetime($checkin);
                if($datetime === null || $datetime < $period_start_datetime) {
                    continue;
                }
                $day_key = date('D', strtotime($datetime));
                $hour_key = substr($datetime, 11, 2);
                if(isset($heatmap[$day_key][$hour_key])) {
                    $heatmap[$day_key][$hour_key]['ai_checkins']++;
                }
            }
        }

        $result_rows = [];
        $max_total = 0;
        foreach($day_labels as $day_key => $day_label) {
            $cells = [];
            foreach($hour_labels as $hour_label) {
                $cell = $heatmap[$day_key][$hour_label];
                $cell['total'] = (int) $cell['clicks'] + (int) $cell['leads'] + (int) $cell['ai_checkins'];
                $max_total = max($max_total, (int) $cell['total']);
                $cells[] = array_merge([
                    'hour' => $hour_label,
                ], $cell);
            }

            $result_rows[] = [
                'day_key' => $day_key,
                'day_label' => $day_label,
                'cells' => $cells,
            ];
        }

        return [
            'hours' => $hour_labels,
            'rows' => $result_rows,
            'max_total' => $max_total,
        ];
    }

    private function get_team_ai_distribution_payload(array $rows): array {
        $payload = [
            'top_priority_offers' => [],
            'top_active_channels' => [],
            'top_follow_up_readiness' => [],
            'top_weekly_priorities' => [],
            'top_content_commitment' => [],
            'top_follow_up_volume' => [],
            'top_weekly_energy' => [],
            'top_completion_levels' => [],
        ];

        $priority_offer_buckets = [];
        $active_channel_buckets = [];
        $follow_up_readiness_buckets = [];
        $weekly_priority_buckets = [];
        $content_commitment_buckets = [];
        $follow_up_volume_buckets = [];
        $weekly_energy_buckets = [];
        $completion_level_buckets = [];

        foreach($rows as $row) {
            $preferences = $this->get_preferences_object($row['preferences'] ?? null);
            $profile = $preferences->leader_ai_profile ?? [];
            $checkins = $preferences->leader_ai_weekly_checkins ?? [];
            $outcomes = $preferences->leader_ai_weekly_outcomes ?? [];

            if(is_object($profile)) $profile = (array) $profile;
            if(is_object($checkins)) $checkins = (array) $checkins;
            if(is_object($outcomes)) $outcomes = (array) $outcomes;

            $latest_checkin = !empty($checkins[0]) ? (array) (is_object($checkins[0]) ? $checkins[0] : $checkins[0]) : [];
            $latest_outcome = !empty($outcomes[0]) ? (array) (is_object($outcomes[0]) ? $outcomes[0] : $outcomes[0]) : [];

            if(!empty($profile['priority_offer'])) {
                $this->increment_count_bucket($priority_offer_buckets, $this->translate_ai_plan_option('priority_offer', $profile['priority_offer']));
            }

            if(!empty($profile['active_channels'])) {
                $channels = is_array($profile['active_channels']) ? $profile['active_channels'] : [$profile['active_channels']];
                foreach($channels as $channel) {
                    $this->increment_count_bucket($active_channel_buckets, $this->translate_ai_plan_option('active_channels', $channel));
                }
            }

            if(!empty($profile['follow_up_readiness'])) {
                $this->increment_count_bucket($follow_up_readiness_buckets, $this->translate_ai_plan_option('follow_up_readiness', $profile['follow_up_readiness']));
            }

            if(!empty($latest_checkin['weekly_priority'])) {
                $this->increment_count_bucket($weekly_priority_buckets, $this->translate_ai_plan_option('weekly_priority', $latest_checkin['weekly_priority']));
            }

            if(!empty($latest_checkin['content_commitment'])) {
                $this->increment_count_bucket($content_commitment_buckets, $this->translate_ai_plan_option('content_commitment', $latest_checkin['content_commitment']));
            }

            if(!empty($latest_checkin['follow_up_volume'])) {
                $this->increment_count_bucket($follow_up_volume_buckets, $this->translate_ai_plan_option('follow_up_volume', $latest_checkin['follow_up_volume']));
            }

            if(!empty($latest_checkin['weekly_energy'])) {
                $this->increment_count_bucket($weekly_energy_buckets, $this->translate_ai_plan_option('weekly_energy', $latest_checkin['weekly_energy']));
            }

            if(!empty($latest_outcome['completion_level'])) {
                $this->increment_count_bucket($completion_level_buckets, ucfirst(str_replace('_', ' ', (string) $latest_outcome['completion_level'])));
            }
        }

        $payload['top_priority_offers'] = $this->sort_count_buckets($priority_offer_buckets, 5);
        $payload['top_active_channels'] = $this->sort_count_buckets($active_channel_buckets, 5);
        $payload['top_follow_up_readiness'] = $this->sort_count_buckets($follow_up_readiness_buckets, 5);
        $payload['top_weekly_priorities'] = $this->sort_count_buckets($weekly_priority_buckets, 5);
        $payload['top_content_commitment'] = $this->sort_count_buckets($content_commitment_buckets, 5);
        $payload['top_follow_up_volume'] = $this->sort_count_buckets($follow_up_volume_buckets, 5);
        $payload['top_weekly_energy'] = $this->sort_count_buckets($weekly_energy_buckets, 5);
        $payload['top_completion_levels'] = $this->sort_count_buckets($completion_level_buckets, 5);

        return $payload;
    }

    private function get_team_ai_dashboard_payload(array $rows): array {
        $payload = [
            'funnel' => [
                'profiles_total' => 0,
                'checkins_total' => 0,
                'plans_total' => 0,
                'outcomes_total' => 0,
                'profile_to_checkin_rate' => 0,
                'checkin_to_plan_rate' => 0,
                'plan_to_outcome_rate' => 0,
            ],
            'bottlenecks' => [
                'profile_only_total' => 0,
                'checkin_waiting_plan_total' => 0,
                'plan_waiting_outcome_total' => 0,
                'stale_checkin_total' => 0,
            ],
            'results' => [
                'ai_active_total' => 0,
                'ai_resultful_total' => 0,
                'ai_active_no_result_total' => 0,
                'ai_pro_without_usage_total' => 0,
                'ai_strong_routine_total' => 0,
                'ai_result_rate' => 0,
            ],
        ];

        foreach($rows as $row) {
            $has_profile = !empty($row['has_profile']);
            $has_checkin = !empty($row['has_checkin']);
            $has_plan = !empty($row['has_plan']);
            $has_outcome = trim((string) ($row['latest_outcome_completion_level'] ?? '')) !== '';
            $ai_stage_key = (string) ($row['ai_usage_stage_key'] ?? 'inactive');
            $ai_active = in_array($ai_stage_key, ['started', 'questionnaire', 'active'], true);
            $days_since_last_checkin = $row['days_since_last_checkin'] ?? null;
            $has_result_signal = (int) ($row['ai_access_growth_signal_30d'] ?? 0) > 0
                || (int) ($row['ai_access_shop_clicks_30d'] ?? 0) > 0
                || (int) ($row['ai_access_funnel_registrations_30d'] ?? 0) > 0
                || (int) ($row['ai_access_whatsapp_contacts_30d'] ?? 0) > 0;

            if($has_profile) {
                $payload['funnel']['profiles_total']++;
            }

            if($has_checkin) {
                $payload['funnel']['checkins_total']++;
            }

            if($has_plan) {
                $payload['funnel']['plans_total']++;
            }

            if($has_outcome) {
                $payload['funnel']['outcomes_total']++;
            }

            if($has_profile && !$has_checkin) {
                $payload['bottlenecks']['profile_only_total']++;
            }

            if($has_checkin && !$has_plan) {
                $payload['bottlenecks']['checkin_waiting_plan_total']++;
            }

            if($has_plan && !$has_outcome) {
                $payload['bottlenecks']['plan_waiting_outcome_total']++;
            }

            if($has_checkin && $days_since_last_checkin !== null && (int) $days_since_last_checkin > 14) {
                $payload['bottlenecks']['stale_checkin_total']++;
            }

            if($ai_active) {
                $payload['results']['ai_active_total']++;
            }

            if($ai_active && $has_result_signal) {
                $payload['results']['ai_resultful_total']++;
            }

            if($ai_active && !$has_result_signal) {
                $payload['results']['ai_active_no_result_total']++;
            }

            if(!empty($row['is_active_pro']) && !$ai_active) {
                $payload['results']['ai_pro_without_usage_total']++;
            }

            if($has_plan && $has_outcome && (string) ($row['consistency_state_key'] ?? '') === 'strong') {
                $payload['results']['ai_strong_routine_total']++;
            }
        }

        $payload['funnel']['profile_to_checkin_rate'] = $payload['funnel']['profiles_total'] > 0
            ? round(($payload['funnel']['checkins_total'] / max(1, $payload['funnel']['profiles_total'])) * 100, 1)
            : 0;
        $payload['funnel']['checkin_to_plan_rate'] = $payload['funnel']['checkins_total'] > 0
            ? round(($payload['funnel']['plans_total'] / max(1, $payload['funnel']['checkins_total'])) * 100, 1)
            : 0;
        $payload['funnel']['plan_to_outcome_rate'] = $payload['funnel']['plans_total'] > 0
            ? round(($payload['funnel']['outcomes_total'] / max(1, $payload['funnel']['plans_total'])) * 100, 1)
            : 0;
        $payload['results']['ai_result_rate'] = $payload['results']['ai_active_total'] > 0
            ? round(($payload['results']['ai_resultful_total'] / max(1, $payload['results']['ai_active_total'])) * 100, 1)
            : 0;

        return $payload;
    }

    private function get_coaching_dashboard_payload(array $rows, array $queue_rows, array $recent_coaching_rows): array {
        $payload = [
            'totals' => [
                'queue_total' => count($queue_rows),
                'mentored_this_week_total' => 0,
                'needs_follow_up_total' => 0,
                'stale_follow_up_total' => 0,
            ],
            'top_actions' => [],
            'recent_rows' => $recent_coaching_rows,
        ];

        $action_buckets = [];

        foreach($rows as $row) {
            if(!empty($row['mentored_this_week'])) {
                $payload['totals']['mentored_this_week_total']++;
            }

            if(!empty($row['needs_follow_up'])) {
                $payload['totals']['needs_follow_up_total']++;
            }

            if(!empty($row['needs_follow_up']) && (($row['days_since_last_contact'] ?? null) === null || (int) ($row['days_since_last_contact'] ?? 0) >= 7)) {
                $payload['totals']['stale_follow_up_total']++;
            }

            if(!empty($row['mentor_next_action'])) {
                $this->increment_count_bucket($action_buckets, (string) $row['mentor_next_action']);
            } elseif(!empty($row['latest_mentor_event_summary'])) {
                $this->increment_count_bucket($action_buckets, (string) $row['latest_mentor_event_summary']);
            }
        }

        $payload['top_actions'] = $this->sort_count_buckets($action_buckets, 6);

        return $payload;
    }

    private function has_feedback_tables(): bool {
        try {
            $has_feedback_tickets = database()->query("SHOW TABLES LIKE 'feedback_tickets'");
            $has_feedback_ticket_messages = database()->query("SHOW TABLES LIKE 'feedback_ticket_messages'");

            return $has_feedback_tickets && $has_feedback_tickets->num_rows && $has_feedback_ticket_messages && $has_feedback_ticket_messages->num_rows;
        } catch(\Throwable $exception) {
            return false;
        }
    }

    private function get_support_center_payload(array $rows): array {
        $payload = [
            'is_available' => false,
            'totals' => [
                'open_total' => 0,
                'answered_total' => 0,
                'closed_total' => 0,
                'stale_total' => 0,
                'mentor_follow_up_total' => 0,
                'outstanding_total' => 0,
                'webinar_topic_total' => 0,
                'confirmed_webinar_total' => 0,
            ],
            'top_categories' => [],
            'top_themes' => [],
            'top_suggestion_themes' => [],
            'top_webinar_topics' => [],
            'top_confirmed_webinar_topics' => [],
            'confirmed_webinar_tickets' => [],
            'top_collaborators' => [],
            'recent_tickets' => [],
            'drilldowns' => [
                'open_total' => [
                    'title' => 'Otvoreni ticketi',
                    'items' => [],
                ],
                'answered_total' => [
                    'title' => 'Odgovoreni ticketi',
                    'items' => [],
                ],
                'stale_total' => [
                    'title' => 'Stale ticketi > 3 dana',
                    'items' => [],
                ],
                'mentor_follow_up_total' => [
                    'title' => 'Treba mentor follow-up',
                    'items' => [],
                ],
            ],
        ];

        if(!$this->has_feedback_tables()) {
            return $payload;
        }

        $this->ensure_feedback_workflow_columns();
        $this->auto_close_read_answered_tickets();

        $payload['is_available'] = true;

        $status_labels = [
            'open' => 'Otvoreno',
            'answered' => 'Odgovoreno',
            'closed' => 'Zatvoreno',
        ];

        $category_labels = [
            'change' => 'Promjena',
            'add' => 'Prijedlog',
            'bug' => 'Bug',
            'other' => 'Ostalo',
        ];

        $theme_buckets = [];
        $suggestion_buckets = [];
        $webinar_topic_buckets = [];
        $confirmed_webinar_topic_buckets = [];
        $category_buckets = [];
        $collaborator_buckets = [];
        $messages_by_ticket_id = [];

        $tickets = db()
            ->join('users', 'feedback_tickets.user_id = users.user_id', 'LEFT')
            ->orderBy('feedback_tickets.last_datetime', 'DESC')
            ->get('feedback_tickets', 80, [
                'feedback_tickets.*',
                'users.name as user_name',
                'users.email as user_email',
            ]);

        if(empty($tickets)) {
            return $payload;
        }

        $ticket_ids = array_filter(array_map(static fn($ticket) => (int) ($ticket->feedback_ticket_id ?? 0), $tickets));

        if(!empty($ticket_ids)) {
            $ticket_ids_sql = implode(',', array_map('intval', $ticket_ids));
            $messages_result = database()->query("SELECT `feedback_ticket_id`, `message`, `is_admin_reply`
                FROM `feedback_ticket_messages`
                WHERE `feedback_ticket_id` IN ({$ticket_ids_sql})
                ORDER BY `feedback_ticket_message_id` ASC");

            while($message = $messages_result->fetch_assoc()) {
                $feedback_ticket_id = (int) ($message['feedback_ticket_id'] ?? 0);

                if($feedback_ticket_id <= 0) {
                    continue;
                }

                $messages_by_ticket_id[$feedback_ticket_id][] = [
                    'message' => (string) ($message['message'] ?? ''),
                    'is_admin_reply' => (int) ($message['is_admin_reply'] ?? 0),
                ];
            }
        }

        $stale_threshold = date('Y-m-d H:i:s', strtotime('-3 days'));

        foreach($tickets as $ticket) {
            $status = (string) ($ticket->status ?? 'open');
            $category = (string) ($ticket->category ?? 'other');
            $feedback_ticket_id = (int) ($ticket->feedback_ticket_id ?? 0);
            $ticket_messages = $messages_by_ticket_id[$feedback_ticket_id] ?? [];
            $initial_user_message = '';
            $latest_admin_reply = '';

            foreach($ticket_messages as $ticket_message) {
                if((int) ($ticket_message['is_admin_reply'] ?? 0) === 0) {
                    $initial_user_message = trim((string) ($ticket_message['message'] ?? ''));
                    break;
                }
            }

            foreach(array_reverse($ticket_messages) as $ticket_message) {
                if((int) ($ticket_message['is_admin_reply'] ?? 0) === 1) {
                    $latest_admin_reply = trim((string) ($ticket_message['message'] ?? ''));
                    break;
                }
            }

            $is_stale = $status !== 'closed' && (string) ($ticket->last_datetime ?? '') !== '' && (string) ($ticket->last_datetime ?? '') <= $stale_threshold;

            if($status === 'closed') {
                $payload['totals']['closed_total']++;
            } elseif($status === 'answered') {
                $payload['totals']['answered_total']++;
            } else {
                $payload['totals']['open_total']++;
            }

            if($is_stale) {
                $payload['totals']['stale_total']++;
            }

            if(in_array($status, ['open', 'answered'], true)) {
                $payload['totals']['mentor_follow_up_total']++;
            }

            $this->increment_count_bucket($category_buckets, $category_labels[$category] ?? ucfirst($category));
            $this->append_ai_text_terms($theme_buckets, trim(((string) ($ticket->subject ?? '')) . ' ' . $initial_user_message));

            if(in_array($category, ['add', 'change'], true)) {
                $this->append_ai_text_terms($suggestion_buckets, trim(((string) ($ticket->subject ?? '')) . ' ' . $initial_user_message));
            }

            $collaborator_key = trim((string) ($ticket->user_name ?? l('global.unknown')));
            $this->increment_count_bucket($collaborator_buckets, $collaborator_key);

            $ticket_payload = [
                'feedback_ticket_id' => $feedback_ticket_id,
                'user_id' => (int) ($ticket->user_id ?? 0),
                'subject' => (string) ($ticket->subject ?? ''),
                'category_key' => $category,
                'category_label' => (string) ($category_labels[$category] ?? ucfirst($category)),
                'status_label' => (string) ($status_labels[$status] ?? ucfirst($status)),
                'status_key' => $status,
                'user_name' => (string) ($ticket->user_name ?? l('global.unknown')),
                'user_email' => (string) ($ticket->user_email ?? ''),
                'last_datetime' => (string) ($ticket->last_datetime ?? ''),
                'detail_url' => url('admin/feedback-tickets/ticket/' . $feedback_ticket_id),
                'message_preview' => mb_substr($initial_user_message !== '' ? $initial_user_message : (string) ($ticket->subject ?? ''), 0, 180),
                'message_count' => count($ticket_messages),
                'initial_user_message' => $initial_user_message,
                'latest_admin_reply' => $latest_admin_reply,
                'conversation' => $ticket_messages,
                'is_stale' => $is_stale,
                'is_webinar_topic_suggestion' => !empty($ticket->is_webinar_topic_suggestion),
                'is_webinar_topic_confirmed' => !empty($ticket->is_webinar_topic_confirmed),
            ];

            $ticket_payload['ai_insight'] = $this->get_cached_support_ticket_ai_report($feedback_ticket_id, (string) ($ticket->last_datetime ?? ''))
                ?? $this->build_support_ticket_heuristic_report($ticket_payload);

            $is_webinar_candidate = !empty($ticket_payload['is_webinar_topic_suggestion']) || (($ticket_payload['ai_insight']['webinar_candidate'] ?? 'ne') === 'da');

            $payload['recent_tickets'][] = $ticket_payload;

            $drilldown_item = [
                'name' => (string) ($ticket_payload['subject'] ?? ''),
                'status_label' => trim((string) ($ticket_payload['user_name'] ?? '') . ' · ' . (string) ($ticket_payload['category_label'] ?? '')),
                'meta' => (string) ($ticket_payload['message_preview'] ?? ''),
                'metric_display' => (string) ($ticket_payload['status_label'] ?? ''),
                'detail_url' => url('admin/leader-operating-system?tab=support&support_ticket_id=' . $feedback_ticket_id . '#leader-os-support-communication'),
            ];

            if($status === 'open') {
                $payload['drilldowns']['open_total']['items'][] = $drilldown_item;
            }

            if($status === 'answered') {
                $payload['drilldowns']['answered_total']['items'][] = $drilldown_item;
            }

            if($is_stale) {
                $payload['drilldowns']['stale_total']['items'][] = $drilldown_item;
            }

            if(in_array($status, ['open', 'answered'], true)) {
                $payload['drilldowns']['mentor_follow_up_total']['items'][] = $drilldown_item;
            }

            if($is_webinar_candidate) {
                $payload['totals']['webinar_topic_total']++;
                $this->append_ai_text_terms($webinar_topic_buckets, trim(((string) ($ticket->subject ?? '')) . ' ' . $initial_user_message));
            }

            if(!empty($ticket_payload['is_webinar_topic_confirmed'])) {
                $payload['totals']['confirmed_webinar_total']++;
                $this->append_ai_text_terms($confirmed_webinar_topic_buckets, trim(((string) ($ticket->subject ?? '')) . ' ' . $initial_user_message));
                $payload['confirmed_webinar_tickets'][] = [
                    'feedback_ticket_id' => $feedback_ticket_id,
                    'subject' => (string) ($ticket_payload['subject'] ?? ''),
                    'category_label' => (string) ($ticket_payload['category_label'] ?? ''),
                    'status_label' => (string) ($ticket_payload['status_label'] ?? ''),
                    'user_name' => (string) ($ticket_payload['user_name'] ?? ''),
                    'message_preview' => (string) ($ticket_payload['message_preview'] ?? ''),
                ];
            }
        }

        $payload['top_categories'] = $this->sort_count_buckets($category_buckets, 6);
        $payload['top_themes'] = $this->sort_count_buckets($theme_buckets, 6);
        $payload['top_suggestion_themes'] = $this->sort_count_buckets($suggestion_buckets, 5);
        $payload['top_webinar_topics'] = $this->sort_count_buckets($webinar_topic_buckets, 6);
        $payload['top_confirmed_webinar_topics'] = $this->sort_count_buckets($confirmed_webinar_topic_buckets, 6);
        $payload['totals']['outstanding_total'] = (int) $payload['totals']['open_total'] + (int) $payload['totals']['answered_total'];

        $top_collaborators = $this->sort_count_buckets($collaborator_buckets, 12);
        $payload['top_collaborators'] = array_map(static function($item) use ($tickets) {
            $matching_ticket = null;
            foreach($tickets as $ticket) {
                if((string) ($ticket->user_name ?? l('global.unknown')) === (string) ($item['label'] ?? '')) {
                    $matching_ticket = $ticket;
                    break;
                }
            }

            return [
                'label' => (string) ($item['label'] ?? ''),
                'total' => (int) ($item['total'] ?? 0),
                'user_email' => (string) ($matching_ticket->user_email ?? ''),
                'detail_url' => !empty($matching_ticket->feedback_ticket_id) ? url('admin/feedback-tickets/ticket/' . (int) $matching_ticket->feedback_ticket_id) : url('admin/feedback-tickets'),
            ];
        }, $top_collaborators);

        $payload['recent_tickets'] = array_slice($payload['recent_tickets'], 0, 12);

        return $payload;
    }

    private function get_selected_support_ticket_payload(int $selected_ticket_id = 0): ?array {
        if(!$this->has_feedback_tables()) {
            return null;
        }

        $this->ensure_feedback_workflow_columns();
        $this->auto_close_read_answered_tickets();

        if($selected_ticket_id <= 0) {
            $selected_ticket_id = (int) (db()->orderBy('last_datetime', 'DESC')->getValue('feedback_tickets', 'feedback_ticket_id'));
        }

        if($selected_ticket_id <= 0) {
            return null;
        }

        $feedback_ticket = db()
            ->join('users', 'feedback_tickets.user_id = users.user_id', 'LEFT')
            ->where('feedback_tickets.feedback_ticket_id', $selected_ticket_id)
            ->getOne('feedback_tickets', [
                'feedback_tickets.*',
                'users.name as user_name',
                'users.email as user_email',
            ]);

        if(!$feedback_ticket) {
            return null;
        }

        $messages = db()->where('feedback_ticket_id', $selected_ticket_id)->orderBy('feedback_ticket_message_id', 'ASC')->get('feedback_ticket_messages');
        $initial_user_message = '';
        $latest_admin_reply = '';
        $conversation = [];

        foreach($messages as $message) {
            $is_admin_reply = (int) ($message->is_admin_reply ?? 0) === 1;

            if(!$is_admin_reply && $initial_user_message === '') {
                $initial_user_message = trim((string) ($message->message ?? ''));
            }

            if($is_admin_reply) {
                $latest_admin_reply = trim((string) ($message->message ?? ''));
            }

            $conversation[] = [
                'is_admin_reply' => $is_admin_reply,
                'author_label' => $is_admin_reply ? 'Admin / mentor' : 'Suradnik',
                'message' => (string) ($message->message ?? ''),
                'datetime' => (string) ($message->datetime ?? ''),
                'attachment' => (string) ($message->attachment ?? ''),
            ];
        }

        $ticket_payload = [
            'feedback_ticket_id' => (int) ($feedback_ticket->feedback_ticket_id ?? 0),
            'user_id' => (int) ($feedback_ticket->user_id ?? 0),
            'subject' => (string) ($feedback_ticket->subject ?? ''),
            'category_key' => (string) ($feedback_ticket->category ?? 'other'),
            'category_label' => match((string) ($feedback_ticket->category ?? 'other')) {
                'change' => 'Promjena',
                'add' => 'Prijedlog',
                'bug' => 'Bug',
                default => 'Ostalo',
            },
            'status_key' => (string) ($feedback_ticket->status ?? 'open'),
            'status_label' => match((string) ($feedback_ticket->status ?? 'open')) {
                'answered' => 'Odgovoreno',
                'closed' => 'Zatvoreno',
                default => 'Otvoreno',
            },
            'user_name' => (string) ($feedback_ticket->user_name ?? l('global.unknown')),
            'user_email' => (string) ($feedback_ticket->user_email ?? ''),
            'last_datetime' => (string) ($feedback_ticket->last_datetime ?? ''),
            'initial_user_message' => $initial_user_message,
            'message_preview' => mb_substr($initial_user_message !== '' ? $initial_user_message : (string) ($feedback_ticket->subject ?? ''), 0, 180),
            'latest_admin_reply' => $latest_admin_reply,
            'message_count' => count($conversation),
            'is_stale' => (string) ($feedback_ticket->status ?? 'open') !== 'closed' && (string) ($feedback_ticket->last_datetime ?? '') !== '' && (string) ($feedback_ticket->last_datetime ?? '') <= date('Y-m-d H:i:s', strtotime('-3 days')),
            'detail_url' => url('admin/feedback-tickets/ticket/' . (int) ($feedback_ticket->feedback_ticket_id ?? 0)),
            'conversation' => $conversation,
            'is_webinar_topic_suggestion' => !empty($feedback_ticket->is_webinar_topic_suggestion),
            'is_webinar_topic_confirmed' => !empty($feedback_ticket->is_webinar_topic_confirmed),
        ];

        $ticket_payload['ai_insight'] = $this->get_cached_support_ticket_ai_report((int) $feedback_ticket->feedback_ticket_id, (string) ($feedback_ticket->last_datetime ?? ''))
            ?? $this->build_support_ticket_heuristic_report($ticket_payload);

        return $ticket_payload;
    }

    private function get_ai_credentials(): array {
        $api_key = trim((string) (settings()->main->openai_api_key ?? settings()->aix->openai_api_key ?? ''));
        $model = trim((string) (settings()->main->openai_model ?? 'gpt-4o'));

        return [
            'api_key' => $api_key,
            'model' => $model !== '' ? $model : 'gpt-4o',
        ];
    }

    private function get_team_strategist_cache_key(string $period_key): string {
        return 'leader_operating_system_team_strategist_v1?period=' . $period_key;
    }

    private function clear_team_strategist_cache(): void {
        cache()->deleteItemsByTag('leader_os_team_strategist');

        foreach(['7d', '30d', '90d'] as $period_key) {
            cache()->deleteItem($this->get_team_strategist_cache_key($period_key));
        }
    }

    private function get_cached_team_strategist_report(string $period_key): ?array {
        $cache_instance = cache()->getItem($this->get_team_strategist_cache_key($period_key));
        $report = $cache_instance->get();

        return is_array($report) ? $report : null;
    }

    private function sanitize_ai_string(string $value, int $limit = 280): string {
        return mb_substr(trim(strip_tags($value)), 0, $limit);
    }

    private function get_support_ticket_ai_cache_key(int $feedback_ticket_id, string $last_datetime = ''): string {
        return 'leader_operating_system_support_ticket_ai_v1?ticket=' . $feedback_ticket_id . '&updated=' . md5($last_datetime);
    }

    private function get_cached_support_ticket_ai_report(int $feedback_ticket_id, string $last_datetime = ''): ?array {
        $cache_instance = cache()->getItem($this->get_support_ticket_ai_cache_key($feedback_ticket_id, $last_datetime));
        $report = $cache_instance->get();

        return is_array($report) ? $report : null;
    }

    private function normalize_single_short_string($value, int $limit = 160, string $fallback = '-'): string {
        $value = $this->sanitize_ai_string((string) $value, $limit);

        return $value !== '' ? $value : $fallback;
    }

    private function guess_support_ticket_topic(string $subject, string $message, string $category): string {
        $haystack = mb_strtolower(trim($subject . ' ' . $message));

        $patterns = [
            'registracije i konverzija' => ['prijav', 'registr', 'konverz', 'lead', 'kupuj', 'klikov'],
            'follow-up i komunikacija' => ['follow', 'poruk', 'javlja', 'odgovor', 'kontakt', 'komunik'],
            'blog i sadržaj' => ['blog', 'sadrž', 'clanak', 'članak', 'objav', 'video'],
            'tehnički problem' => ['bug', 'error', 'grešk', 'gresk', 'ne radi', 'blok', 'slika', 'link'],
            'ai plan i korištenje' => ['ai', 'plan', 'check', 'analiz', 'prompt'],
            'pro paket i članstvo' => ['pro', 'član', 'clan', 'paket', 'obnova', 'vip'],
        ];

        foreach($patterns as $label => $needles) {
            foreach($needles as $needle) {
                if($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                    return $label;
                }
            }
        }

        return match($category) {
            'bug' => 'tehnički problem',
            'add', 'change' => 'prijedlog i poboljšanje',
            default => 'opća podrška',
        };
    }

    private function build_support_ticket_heuristic_report(array $ticket_payload): array {
        $subject = (string) ($ticket_payload['subject'] ?? '');
        $message = (string) ($ticket_payload['initial_user_message'] ?? '');
        $category = (string) ($ticket_payload['category_key'] ?? 'other');
        $status = (string) ($ticket_payload['status_key'] ?? 'open');
        $is_stale = !empty($ticket_payload['is_stale']);
        $topic = $this->guess_support_ticket_topic($subject, $message, $category);
        $message_lower = mb_strtolower($message);

        $sentiment = 'neutralno';
        if(strpbrk($message_lower, '!?') !== false || mb_strpos($message_lower, 'ne radi') !== false || mb_strpos($message_lower, 'problem') !== false) {
            $sentiment = 'frustracija / hitnost';
        } elseif(mb_strpos($message_lower, 'prijedlog') !== false || $category === 'add' || $category === 'change') {
            $sentiment = 'konstruktivan prijedlog';
        }

        $urgency = 'normalno';
        if($category === 'bug' || $is_stale) {
            $urgency = 'visoko';
        } elseif($status === 'open') {
            $urgency = 'srednje';
        }

        $webinar_candidate = in_array($topic, ['registracije i konverzija', 'follow-up i komunikacija', 'blog i sadržaj', 'ai plan i korištenje'], true);
        $summary = $this->normalize_single_short_string(
            $message !== '' ? $message : $subject,
            220,
            'Suradnik traži pojašnjenje ili smjer vezan uz ovu temu.'
        );

        $recommended_action = match(true) {
            $category === 'bug' => 'Provjeri tehnički uzrok i pošalji jasan workaround ili potvrdu rješenja.',
            $webinar_candidate => 'Odgovori kratko i ovu temu zabilježi kao mogući webinar / FAQ fokus.',
            $category === 'add' || $category === 'change' => 'Zahvali na prijedlogu, potvrdi da je zabilježen i reci što je sljedeći korak.',
            default => 'Pošalji konkretan odgovor s jednim jasnim sljedećim korakom za suradnika.',
        };

        $suggested_reply = match($topic) {
            'registracije i konverzija' => 'Hvala ti na upitu. Najvažnije je da pojednostaviš put od klika do prijave: jedan jasan CTA, kratak follow-up i provjera gdje ljudi odustaju. Ako želiš, mogu ti složiti i konkretan sljedeći korak za tvoj funnel.',
            'follow-up i komunikacija' => 'Hvala ti na poruci. Ovdje je ključ u kratkom i jasnom follow-upu: jedna poruka, jedan cilj i jedan idući korak. Ako želiš, mogu ti odmah dati primjer poruke koju možeš poslati kontaktu.',
            'blog i sadržaj' => 'Hvala ti na upitu. Predlažem da fokus staviš na jedan sadržaj koji vodi prema jednoj jasnoj akciji, umjesto više paralelnih smjerova. Ako želiš, mogu ti pomoći složiti jednostavniji sadržajni tok.',
            'ai plan i korištenje' => 'Hvala ti na poruci. Najviše koristi dobivaš kad AI koristiš za jedan konkretan tjedni cilj, plan i provjeru rezultata. Ako želiš, mogu ti pomoći složiti točno kako to postaviti.',
            'tehnički problem' => 'Hvala ti na prijavi. Pogledat ćemo tehnički dio i javiti ti jasan odgovor ili workaround. Ako se problem i dalje ponavlja, pošalji nam i korak prije nego što se greška pojavi.',
            default => 'Hvala ti na poruci. Pogledali smo tvoj upit i šaljemo ti smjer za sljedeći korak. Ako zapneš na konkretnom dijelu, slobodno pošalji još jedan detalj i vodimo te dalje.',
        };

        return [
            'source' => 'heuristic',
            'summary' => $summary,
            'core_issue' => $topic,
            'sentiment' => $sentiment,
            'urgency' => $urgency,
            'recommended_action' => $this->normalize_single_short_string($recommended_action, 220),
            'suggested_reply' => $this->normalize_single_short_string($suggested_reply, 480),
            'webinar_candidate' => $webinar_candidate ? 'da' : 'ne',
            'webinar_reason' => $webinar_candidate ? 'Tema se može pretvoriti u edukaciju za širi dio tima.' : 'Tema zasad izgleda više individualno nego timski.',
        ];
    }

    private function validate_support_ticket_ai_response(array $response, array $fallback_report): array {
        return [
            'source' => 'ai',
            'summary' => $this->normalize_single_short_string($response['summary'] ?? '', 220, $fallback_report['summary'] ?? '-'),
            'core_issue' => $this->normalize_single_short_string($response['core_issue'] ?? '', 120, $fallback_report['core_issue'] ?? '-'),
            'sentiment' => $this->normalize_single_short_string($response['sentiment'] ?? '', 80, $fallback_report['sentiment'] ?? '-'),
            'urgency' => $this->normalize_single_short_string($response['urgency'] ?? '', 40, $fallback_report['urgency'] ?? 'normalno'),
            'recommended_action' => $this->normalize_single_short_string($response['recommended_action'] ?? '', 220, $fallback_report['recommended_action'] ?? '-'),
            'suggested_reply' => $this->normalize_single_short_string($response['suggested_reply'] ?? '', 480, $fallback_report['suggested_reply'] ?? '-'),
            'webinar_candidate' => $this->normalize_single_short_string($response['webinar_candidate'] ?? '', 10, $fallback_report['webinar_candidate'] ?? 'ne'),
            'webinar_reason' => $this->normalize_single_short_string($response['webinar_reason'] ?? '', 200, $fallback_report['webinar_reason'] ?? '-'),
        ];
    }

    private function get_support_ticket_ai_input(array $ticket_payload): array {
        return $this->sanitize_utf8_for_json([
            'ticket' => [
                'feedback_ticket_id' => (int) ($ticket_payload['feedback_ticket_id'] ?? 0),
                'subject' => (string) ($ticket_payload['subject'] ?? ''),
                'category' => (string) ($ticket_payload['category_label'] ?? ''),
                'status' => (string) ($ticket_payload['status_label'] ?? ''),
                'user_name' => (string) ($ticket_payload['user_name'] ?? ''),
                'last_datetime' => (string) ($ticket_payload['last_datetime'] ?? ''),
                'message_count' => (int) ($ticket_payload['message_count'] ?? 0),
                'initial_user_message' => (string) ($ticket_payload['initial_user_message'] ?? ''),
                'latest_admin_reply' => (string) ($ticket_payload['latest_admin_reply'] ?? ''),
                'is_stale' => !empty($ticket_payload['is_stale']),
            ],
        ]);
    }

    private function generate_support_ticket_ai_report(array $ticket_payload, bool $force_refresh = false): array {
        $feedback_ticket_id = (int) ($ticket_payload['feedback_ticket_id'] ?? 0);
        $last_datetime = (string) ($ticket_payload['last_datetime'] ?? '');
        $fallback_report = $this->build_support_ticket_heuristic_report($ticket_payload);

        if($feedback_ticket_id <= 0) {
            return $fallback_report;
        }

        if(!$force_refresh) {
            $cached_report = $this->get_cached_support_ticket_ai_report($feedback_ticket_id, $last_datetime);

            if($cached_report) {
                return $cached_report;
            }
        }

        $credentials = $this->get_ai_credentials();

        if($credentials['api_key'] === '') {
            return $fallback_report;
        }

        $ai_input = $this->get_support_ticket_ai_input($ticket_payload);

        $response = \Unirest\Request::post(
            'https://api.openai.com/v1/chat/completions',
            [
                'Authorization' => 'Bearer ' . get_random_line_from_text($credentials['api_key']),
                'Content-Type' => 'application/json',
            ],
            \Unirest\Request\Body::json([
                'model' => $credentials['model'],
                'response_format' => [
                    'type' => 'json_object',
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Pisi iskljucivo na hrvatskom. Ti si senior support strategist i mentor. Tvoj zadatak je procijeniti korisnicki ticket, sazet njegov problem i pripremiti jasan, koristan odgovor koji admin moze poslati suradniku. Vrati samo valjan JSON bez markdowna.'
                    ],
                    [
                        'role' => 'user',
                        'content' => implode("\n\n", [
                            'Analiziraj ovaj support ticket i vrati samo JSON sa sljedecim kljucevima: summary, core_issue, sentiment, urgency, recommended_action, suggested_reply, webinar_candidate, webinar_reason.',
                            'Pravila:',
                            '- summary neka bude kratki sazetak stvarnog problema.',
                            '- core_issue neka bude naziv glavne teme.',
                            '- sentiment neka bude kratka procjena tona korisnika.',
                            '- urgency neka bude jedna od vrijednosti: nisko, normalno, srednje, visoko.',
                            '- recommended_action neka bude sto admin treba napraviti.',
                            '- suggested_reply neka bude prirodan odgovor spreman za slanje korisniku.',
                            '- webinar_candidate neka bude da ili ne.',
                            '- webinar_reason neka objasni zasluzuje li tema edukaciju za siri tim.',
                            '- Nemoj izmisljati detalje koji nisu prisutni u ticketu.',
                            'Input JSON: ' . json_encode($ai_input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]),
                    ],
                ],
            ])
        );

        if($response->code >= 400) {
            throw new \Exception($response->body->error->message ?? 'AI analiza support ticketa nije prošla.');
        }

        $content = trim((string) ($response->body->choices[0]->message->content ?? ''));

        if(substr($content, 0, 3) === '```') {
            $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);
        }

        $decoded_report = json_decode($content, true);

        if(!is_array($decoded_report)) {
            throw new \Exception('AI support analiza nije vratila valjan JSON odgovor.');
        }

        $report = $this->validate_support_ticket_ai_response($decoded_report, $fallback_report);
        $report['generated_at'] = get_date();
        $report['model'] = $credentials['model'];

        $cache_item = cache()->getItem($this->get_support_ticket_ai_cache_key($feedback_ticket_id, $last_datetime));
        $cache_item
            ->set($report)
            ->expiresAfter(86400)
            ->addTag('leader_os_support_ticket_ai');

        cache()->save($cache_item);

        return $report;
    }

    private function sanitize_utf8_for_json($value) {
        if(is_array($value)) {
            foreach($value as $key => $item) {
                $value[$key] = $this->sanitize_utf8_for_json($item);
            }

            return $value;
        }

        if(is_object($value)) {
            foreach($value as $key => $item) {
                $value->{$key} = $this->sanitize_utf8_for_json($item);
            }

            return $value;
        }

        if(is_string($value)) {
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        return $value;
    }

    private function normalize_string_list($values, int $limit = 6): array {
        $normalized = [];

        foreach((array) $values as $value) {
            if(is_array($value) || is_object($value)) {
                continue;
            }

            $value = trim((string) $value);

            if($value === '') {
                continue;
            }

            $normalized[] = mb_substr($value, 0, 220);

            if(count($normalized) >= $limit) {
                break;
            }
        }

        return $normalized;
    }

    private function validate_team_strategist_response(array $response): array {
        $headline = $this->sanitize_ai_string((string) ($response['headline'] ?? ''), 140);
        $subheadline = $this->sanitize_ai_string((string) ($response['subheadline'] ?? ''), 260);
        $team_message_preview = $this->sanitize_ai_string((string) ($response['team_message_preview'] ?? ''), 420);
        $risk_group_message_preview = $this->sanitize_ai_string((string) ($response['risk_group_message_preview'] ?? ''), 420);

        $weekly_focus = is_array($response['weekly_focus'] ?? null) ? $response['weekly_focus'] : [];
        $recommended_webinar = is_array($response['recommended_webinar'] ?? null) ? $response['recommended_webinar'] : [];

        $weekly_focus_title = $this->sanitize_ai_string((string) ($weekly_focus['title'] ?? ''), 140);
        $weekly_focus_reason = $this->sanitize_ai_string((string) ($weekly_focus['reason'] ?? ''), 320);
        $weekly_focus_primary_kpi = $this->sanitize_ai_string((string) ($weekly_focus['primary_kpi'] ?? ''), 120);
        $recommended_webinar_title = $this->sanitize_ai_string((string) ($recommended_webinar['title'] ?? ''), 160);
        $recommended_webinar_why_now = $this->sanitize_ai_string((string) ($recommended_webinar['why_now'] ?? ''), 320);

        if($headline === '' || $weekly_focus_title === '' || $recommended_webinar_title === '') {
            throw new \Exception('AI strategist nije vratio valjan strukturirani odgovor.');
        }

        return [
            'headline' => $headline,
            'subheadline' => $subheadline !== '' ? $subheadline : 'AI analiza je složena iz aktualnih LOS, coaching, AI usage i support signala.',
            'weekly_focus' => [
                'title' => $weekly_focus_title,
                'reason' => $weekly_focus_reason,
                'primary_kpi' => $weekly_focus_primary_kpi !== '' ? $weekly_focus_primary_kpi : 'Registracije, consistency i support clarity',
            ],
            'recommended_webinar' => [
                'title' => $recommended_webinar_title,
                'why_now' => $recommended_webinar_why_now,
                'agenda_points' => $this->normalize_string_list($recommended_webinar['agenda_points'] ?? [], 5),
            ],
            'strengths' => $this->normalize_string_list($response['strengths'] ?? [], 6),
            'weaknesses' => $this->normalize_string_list($response['weaknesses'] ?? [], 6),
            'next_actions' => $this->normalize_string_list($response['next_actions'] ?? [], 6),
            'coaching_priorities' => $this->normalize_string_list($response['coaching_priorities'] ?? [], 5),
            'support_insights' => $this->normalize_string_list($response['support_insights'] ?? [], 5),
            'kpis_to_watch' => $this->normalize_string_list($response['kpis_to_watch'] ?? [], 5),
            'team_message_preview' => $team_message_preview,
            'risk_group_message_preview' => $risk_group_message_preview,
        ];
    }

    private function build_team_strategist_ai_input(array $overview_payload, string $period_key): array {
        $support_center = $overview_payload['support_center'] ?? [];
        $coaching_dashboard = $overview_payload['coaching_dashboard'] ?? [];
        $fraud_dashboard = $overview_payload['fraud_dashboard'] ?? [];
        $team_ai_habits = $overview_payload['team_ai_habits'] ?? [];
        $team_ai_actions = $overview_payload['team_ai_actions'] ?? [];

        return $this->sanitize_utf8_for_json([
            'period_key' => $period_key,
            'generated_at' => get_date(),
            'team_snapshot' => $overview_payload['team_snapshot'] ?? [],
            'executive_summary' => $overview_payload['executive_summary'] ?? [],
            'market_pulse' => [
                'top_countries' => array_slice((array) ($overview_payload['team_analytics']['top_countries'] ?? []), 0, 5),
                'top_sources' => array_slice((array) ($overview_payload['team_analytics']['top_sources'] ?? []), 0, 5),
                'top_hours' => array_slice((array) ($overview_payload['team_analytics']['top_hours'] ?? []), 0, 5),
                'blog_forever_countries' => array_slice((array) ($overview_payload['team_analytics']['blog_forever_countries'] ?? []), 0, 5),
            ],
            'ai_habits' => [
                'totals' => [
                    'profiles_total' => (int) ($team_ai_habits['profiles_total'] ?? 0),
                    'checkins_total' => (int) ($team_ai_habits['checkins_total'] ?? 0),
                    'plans_total' => (int) ($team_ai_habits['plans_total'] ?? 0),
                    'outcomes_total' => (int) ($team_ai_habits['outcomes_total'] ?? 0),
                    'mentored_this_week_total' => (int) ($team_ai_habits['mentored_this_week_total'] ?? 0),
                ],
                'top_goals' => array_slice((array) ($team_ai_habits['top_goals'] ?? []), 0, 5),
                'top_blockers' => array_slice((array) ($team_ai_habits['top_blockers'] ?? []), 0, 5),
                'top_ai_needs' => array_slice((array) ($team_ai_habits['top_ai_needs'] ?? []), 0, 5),
                'team_focus_terms' => array_slice((array) ($team_ai_actions['top_focus_terms'] ?? []), 0, 5),
                'team_friction_terms' => array_slice((array) ($team_ai_actions['top_friction_terms'] ?? []), 0, 5),
            ],
            'coaching' => [
                'dashboard_totals' => $coaching_dashboard['totals'] ?? [],
                'top_actions' => array_slice((array) ($coaching_dashboard['top_actions'] ?? []), 0, 5),
                'coaching_roi' => $overview_payload['team_coaching_roi'] ?? [],
                'top_priority_rows' => array_map(static function($row) {
                    return [
                        'name' => (string) ($row['name'] ?? ''),
                        'status' => (string) ($row['status_label'] ?? ''),
                        'reason' => (string) (($row['combined_priority_reason'] ?? '') ?: ($row['queue_reason'] ?? '')),
                        'priority_score' => (int) (($row['combined_priority_score'] ?? 0) ?: ($row['queue_priority_score'] ?? 0)),
                        'risk_score' => (int) ($row['risk_score'] ?? 0),
                        'anomaly_score' => (int) ($row['anomaly_score'] ?? 0),
                    ];
                }, array_slice((array) ($overview_payload['queue_rows'] ?? []), 0, 8)),
            ],
            'support' => [
                'totals' => $support_center['totals'] ?? [],
                'top_categories' => array_slice((array) ($support_center['top_categories'] ?? []), 0, 5),
                'top_themes' => array_slice((array) ($support_center['top_themes'] ?? []), 0, 5),
                'top_suggestion_themes' => array_slice((array) ($support_center['top_suggestion_themes'] ?? []), 0, 5),
                'top_webinar_topics' => array_slice((array) ($support_center['top_webinar_topics'] ?? []), 0, 5),
                'top_confirmed_webinar_topics' => array_slice((array) ($support_center['top_confirmed_webinar_topics'] ?? []), 0, 5),
                'confirmed_webinar_tickets' => array_map(static function($ticket) {
                    return [
                        'subject' => (string) ($ticket['subject'] ?? ''),
                        'category' => (string) ($ticket['category_label'] ?? ''),
                        'status' => (string) ($ticket['status_label'] ?? ''),
                        'user_name' => (string) ($ticket['user_name'] ?? ''),
                        'preview' => (string) ($ticket['message_preview'] ?? ''),
                    ];
                }, array_slice((array) ($support_center['confirmed_webinar_tickets'] ?? []), 0, 6)),
                'recent_tickets' => array_map(static function($ticket) {
                    return [
                        'subject' => (string) ($ticket['subject'] ?? ''),
                        'category' => (string) ($ticket['category_label'] ?? ''),
                        'status' => (string) ($ticket['status_label'] ?? ''),
                        'preview' => (string) ($ticket['message_preview'] ?? ''),
                        'is_webinar_topic_suggestion' => !empty($ticket['is_webinar_topic_suggestion']),
                        'is_webinar_topic_confirmed' => !empty($ticket['is_webinar_topic_confirmed']),
                    ];
                }, array_slice((array) ($support_center['recent_tickets'] ?? []), 0, 6)),
            ],
            'fraud' => [
                'totals' => $fraud_dashboard['totals'] ?? [],
                'top_drivers' => array_slice((array) ($fraud_dashboard['top_anomaly_drivers'] ?? []), 0, 5),
                'top_suspicious_reasons' => array_slice((array) ($fraud_dashboard['top_suspicious_reasons'] ?? []), 0, 5),
                'top_risk_collaborators' => array_map(static function($item) {
                    return [
                        'name' => (string) ($item['name'] ?? ''),
                        'reason' => (string) ($item['reason'] ?? ''),
                        'anomaly_score' => (int) ($item['anomaly_score'] ?? 0),
                        'risk_score' => (int) ($item['risk_score'] ?? 0),
                        'blocked_attempts_total' => (int) ($item['blocked_attempts_total'] ?? 0),
                    ];
                }, array_slice((array) ($fraud_dashboard['top_risk_collaborators'] ?? []), 0, 6)),
            ],
            'leaderboards' => [
                'top_score' => array_slice((array) ($overview_payload['team_leaderboards']['top_score'] ?? []), 0, 5),
                'top_opportunity' => array_slice((array) ($overview_payload['team_leaderboards']['top_opportunity'] ?? []), 0, 5),
                'top_risk' => array_slice((array) ($overview_payload['team_leaderboards']['top_risk'] ?? []), 0, 5),
                'top_consistency' => array_slice((array) ($overview_payload['team_leaderboards']['top_consistency'] ?? []), 0, 5),
            ],
        ]);
    }

    private function generate_team_strategist_report(array $overview_payload, string $period_key, bool $force_refresh = false): array {
        if(!$force_refresh) {
            $cached_report = $this->get_cached_team_strategist_report($period_key);

            if($cached_report) {
                return $cached_report;
            }
        }

        $credentials = $this->get_ai_credentials();

        if($credentials['api_key'] === '') {
            throw new \Exception('OpenAI API ključ nije postavljen u admin postavkama.');
        }

        $ai_input = $this->build_team_strategist_ai_input($overview_payload, $period_key);

        $response = \Unirest\Request::post(
            'https://api.openai.com/v1/chat/completions',
            [
                'Authorization' => 'Bearer ' . get_random_line_from_text($credentials['api_key']),
                'Content-Type' => 'application/json',
            ],
            \Unirest\Request\Body::json([
                'model' => $credentials['model'],
                'response_format' => [
                    'type' => 'json_object',
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Pisi iskljucivo na hrvatskom. Ti si elitni strategist i mentor za Forever Card Club tim. Tvoj posao nije prepricati podatke nego donijeti jasan tjedni fokus, otkriti sto tim trenutno najvise koci i predloziti webinar, coaching i poruke koje admin moze odmah iskoristiti. Vrati samo valjan JSON bez markdowna i bez dodatnih kljuceva.'
                    ],
                    [
                        'role' => 'user',
                        'content' => implode("\n\n", [
                            'Analiziraj team snapshot iz FCC Leader Operating Systema i vrati konkretan tjedni strateški brief za admina/mentora.',
                            'Vrati samo JSON sa sljedecim kljucevima: headline, subheadline, weekly_focus, recommended_webinar, strengths, weaknesses, next_actions, coaching_priorities, support_insights, kpis_to_watch, team_message_preview, risk_group_message_preview.',
                            'weekly_focus mora imati kljuceve: title, reason, primary_kpi.',
                            'recommended_webinar mora imati kljuceve: title, why_now, agenda_points.',
                            'strengths, weaknesses, next_actions, coaching_priorities, support_insights, kpis_to_watch i recommended_webinar.agenda_points moraju biti polja kratkih konkretnih stringova.',
                            'Pravila:',
                            '- Zakljuci moraju biti strogo vezani uz dane podatke, ne genericki.',
                            '- Ako support teme ponavljaju istu nejasnocu, predlozi da to postane webinar, FAQ ili interna obavijest.',
                            '- Ako je glavni problem consistency, fokusiraj se na execution i tjedni ritam rada.',
                            '- Ako je glavni problem promet bez registracija, fokusiraj se na put od klika do prijave i follow-up.',
                            '- Ako postoje rising suradnici i market championi, navedi kako ih koristiti kao primjer timu.',
                            '- Coaching priorities neka budu precizne skupine ili tipovi suradnika koje mentor treba prvo obraditi.',
                            '- team_message_preview i risk_group_message_preview moraju biti prirodne poruke koje admin moze doraditi i poslati timu.',
                            '- Nemoj koristiti markdown ni dodatne kljuceve.',
                            'Input JSON: ' . json_encode($ai_input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]),
                    ],
                ],
            ])
        );

        if($response->code >= 400) {
            throw new \Exception($response->body->error->message ?? 'AI strategist zahtjev nije prošao.');
        }

        $content = trim((string) ($response->body->choices[0]->message->content ?? ''));

        if(substr($content, 0, 3) === '```') {
            $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);
        }

        $decoded_report = json_decode($content, true);

        if(!is_array($decoded_report)) {
            throw new \Exception('AI strategist nije vratio valjan JSON odgovor.');
        }

        $report = $this->validate_team_strategist_response($decoded_report);
        $report['generated_at'] = get_date();
        $report['model'] = $credentials['model'];
        $report['period_key'] = $period_key;
        $report['source'] = 'ai';
        $report['snapshot'] = $overview_payload['team_snapshot'] ?? [];

        $cache_item = cache()->getItem($this->get_team_strategist_cache_key($period_key));
        $cache_item
            ->set($report)
            ->expiresAfter(86400)
            ->addTag('leader_os_team_strategist');

        cache()->save($cache_item);

        return $report;
    }

    private function get_team_snapshot_payload(array $overview_payload): array {
        $totals = $overview_payload['totals'] ?? [];
        $support_center = $overview_payload['support_center'] ?? [];
        $team_analytics = $overview_payload['team_analytics'] ?? [];
        $team_ai_habits = $overview_payload['team_ai_habits'] ?? [];
        $team_ai_actions = $overview_payload['team_ai_actions'] ?? [];
        $team_consistency = $overview_payload['team_consistency'] ?? [];
        $team_coaching_roi = $overview_payload['team_coaching_roi'] ?? [];
        $fraud_dashboard = $overview_payload['fraud_dashboard'] ?? [];

        return [
            'executive' => [
                'period' => (string) ($overview_payload['selected_period'] ?? '30d'),
                'all_collaborators' => (int) ($totals['all_collaborators'] ?? 0),
                'qualified' => (int) ($totals['qualified'] ?? 0),
                'rising' => (int) ($totals['rising'] ?? 0),
                'risk' => (int) ($totals['risk'] ?? 0),
                'active_collaborators' => (int) ($totals['active_collaborators'] ?? 0),
                'registrations' => (int) ($totals['total_registrations_period'] ?? 0),
                'funnel_leads' => (int) ($totals['total_funnel_leads_period'] ?? 0),
                'shop_clicks' => (int) ($totals['total_shop_clicks_period'] ?? 0),
            ],
            'analytics' => [
                'top_country' => $team_analytics['top_countries'][0]['label'] ?? '-',
                'top_source' => $team_analytics['top_sources'][0]['label'] ?? '-',
                'top_hour' => $team_analytics['top_hours'][0]['label'] ?? '-',
            ],
            'ai' => [
                'profiles_total' => (int) ($team_ai_habits['profiles_total'] ?? 0),
                'checkins_total' => (int) ($team_ai_habits['checkins_total'] ?? 0),
                'plans_total' => (int) ($team_ai_habits['plans_total'] ?? 0),
                'outcomes_total' => (int) ($team_ai_habits['outcomes_total'] ?? 0),
                'top_goal' => $team_ai_habits['top_goals'][0]['label'] ?? '-',
                'top_blocker' => $team_ai_habits['top_blockers'][0]['label'] ?? '-',
                'focus_term' => $team_ai_actions['top_focus_terms'][0]['label'] ?? '-',
                'friction_term' => $team_ai_actions['top_friction_terms'][0]['label'] ?? '-',
            ],
            'coaching' => [
                'average_consistency' => (float) ($team_consistency['average_score'] ?? 0),
                'coaching_touched_total' => (int) ($team_coaching_roi['touched_total'] ?? 0),
                'positive_signal_total' => (int) ($team_coaching_roi['positive_signal_total'] ?? 0),
                'risk_after_touch_total' => (int) ($team_coaching_roi['risk_after_touch_total'] ?? 0),
            ],
            'fraud' => [
                'high_anomaly_total' => (int) ($fraud_dashboard['totals']['high_anomaly_total'] ?? 0),
                'watch_anomaly_total' => (int) ($fraud_dashboard['totals']['watch_anomaly_total'] ?? 0),
                'blocked_attempts_total' => (int) ($fraud_dashboard['totals']['blocked_attempts_total'] ?? 0),
            ],
            'support' => [
                'open_total' => (int) ($support_center['totals']['open_total'] ?? 0),
                'answered_total' => (int) ($support_center['totals']['answered_total'] ?? 0),
                'stale_total' => (int) ($support_center['totals']['stale_total'] ?? 0),
                'top_theme' => $support_center['top_themes'][0]['label'] ?? '-',
            ],
        ];
    }

    private function get_team_strategist_payload(array $overview_payload): array {
        $period_key = (string) ($overview_payload['selected_period'] ?? '30d');
        $support_center = $overview_payload['support_center'] ?? [];
        $confirmed_webinar_total = (int) ($support_center['totals']['confirmed_webinar_total'] ?? 0);
        $confirmed_webinar_topic = trim((string) ($support_center['confirmed_webinar_tickets'][0]['subject'] ?? ''));
        if($confirmed_webinar_topic === '') {
            $confirmed_webinar_topic = (string) ($support_center['top_confirmed_webinar_topics'][0]['label'] ?? '');
        }
        $cached_report = $this->get_cached_team_strategist_report($period_key);

        if($cached_report) {
            $cached_report['source'] = 'ai';
            $cached_report['snapshot'] = $overview_payload['team_snapshot'] ?? ($cached_report['snapshot'] ?? []);

            if($confirmed_webinar_total > 0 && $confirmed_webinar_topic !== '') {
                $cached_report['weekly_focus']['title'] = 'Obraditi potvrđenu webinar temu iz podrške';
                $cached_report['weekly_focus']['reason'] = 'U podršci je ručno potvrđena tema koja se pokazala dovoljno relevantnom za širi tim i zaslužuje mjesto u sljedećem webinaru.';
                $cached_report['recommended_webinar']['title'] = 'Tjedni webinar: ' . $confirmed_webinar_topic;
                $cached_report['recommended_webinar']['why_now'] = 'Ova tema je ručno potvrđena iz podrške kao relevantna za širi tim i zato ima prioritet nad općim signalima.';
                $cached_report['support_insights'] = array_values(array_unique(array_filter(array_merge(
                    ['Potvrđene support teme sada imaju prioritet za webinar, FAQ i timsku poruku.'],
                    (array) ($cached_report['support_insights'] ?? [])
                ))));
            }

            return $cached_report;
        }

        $snapshot = $this->get_team_snapshot_payload($overview_payload);
        $totals = $overview_payload['totals'] ?? [];
        $team_ai_habits = $overview_payload['team_ai_habits'] ?? [];
        $team_ai_actions = $overview_payload['team_ai_actions'] ?? [];

        $shop_clicks = (int) ($totals['total_shop_clicks_period'] ?? 0);
        $registrations = (int) ($totals['total_registrations_period'] ?? 0);
        $conversion_percent = $shop_clicks > 0 ? round(($registrations / max(1, $shop_clicks)) * 100, 1) : 0;

        $focus_title = 'Povezati promet s boljom konverzijom';
        $focus_reason = 'Tim ima promet, ali najveća poslovna vrijednost dolazi iz boljeg puta od klika do registracije i jasnijeg follow-upa.';
        $webinar_title = 'Kako iz klikova do prijava i konkretnog follow-upa';
        $support_note = 'Support treba pratiti kao dodatni signal za nejasnoće i teme koje zaslužuju webinar ili FAQ.';

        if($confirmed_webinar_total > 0 && $confirmed_webinar_topic !== '') {
            $focus_title = 'Obraditi potvrđenu webinar temu iz podrške';
            $focus_reason = 'U podršci je ručno potvrđena tema koja se pokazala dovoljno relevantnom za širi tim i zaslužuje mjesto u sljedećem webinaru.';
            $webinar_title = 'Tjedni webinar: ' . $confirmed_webinar_topic;
            $support_note = 'Potvrđene support teme sada imaju prioritet za webinar, FAQ i timsku poruku.';
        } elseif((int) ($support_center['totals']['open_total'] ?? 0) >= 8 && !empty($support_center['top_themes'][0]['label'])) {
            $focus_title = 'Riješiti najčešću nejasnoću iz podrške';
            $focus_reason = 'Support upiti i dalje otvaraju istu temu, što znači da timu treba jasnije objašnjenje, primjer i centraliziran odgovor.';
            $webinar_title = 'Tjedni webinar: ' . (string) ($support_center['top_themes'][0]['label'] ?? 'najčešća tema iz podrške');
            $support_note = 'Najveća prilika je pretvoriti recurring support temu u edukaciju, FAQ i internu poruku timu.';
        } elseif(!empty($team_ai_habits['top_blockers'][0]['label'])) {
            $focus_title = 'Maknuti glavni blocker koji koči execution';
            $focus_reason = 'AI check-inovi pokazuju da se isti blocker ponavlja i da prvo treba riješiti obrazac rada, a tek onda tražiti veći scale.';
            $webinar_title = 'Tjedni webinar: ' . (string) ($team_ai_habits['top_blockers'][0]['label'] ?? 'glavni blocker tima');
        } elseif((float) ($overview_payload['team_consistency']['average_score'] ?? 0) < 45) {
            $focus_title = 'Podignuti dosljednost i završavanje tjednog ciklusa';
            $focus_reason = 'Tim ulazi u AI i coaching tok, ali bez dovoljno zatvorenih planova i outcome refleksije.';
            $webinar_title = 'Kako povećati consistency i završiti tjedni plan';
        }

        return [
            'snapshot' => $snapshot,
            'source' => 'heuristic',
            'headline' => 'AI Team Strategist je spreman za tjedni coaching fokus.',
            'subheadline' => 'Ovo je pripremni strateški sloj za puni GPT strategist. Već sada objedinjuje ključne LOS, AI, support i coaching signale u jedan operativni okvir.',
            'weekly_focus' => [
                'title' => $focus_title,
                'reason' => $focus_reason,
                'primary_kpi' => 'Registracije, consistency i support clarity',
            ],
            'recommended_webinar' => [
                'title' => $webinar_title,
                'why_now' => 'Tema je odabrana iz kombinacije conversion signala, AI blockera, coaching prioriteta i support inputa.',
                'agenda_points' => [
                    'Pokaži točan put od sadržaja ili klika do prijave / kontakta.',
                    'Objasni najčešću grešku koju tim trenutno ponavlja.',
                    'Daj jedan jasan tjedni zadatak koji svi mogu odmah provesti.',
                ],
            ],
            'strengths' => array_values(array_filter([
                !empty($snapshot['analytics']['top_country']) && $snapshot['analytics']['top_country'] !== '-' ? 'Postoji jasno tržište koje trenutno nosi momentum: ' . $snapshot['analytics']['top_country'] . '.' : '',
                (int) ($totals['qualified'] ?? 0) > 0 ? 'Tim već ima bazu kvalificiranih suradnika koji mogu služiti kao primjeri dobrog rada.' : '',
                (int) ($totals['ai_active_collaborators'] ?? 0) > 0 ? 'AI usage više nije marginalan i može se koristiti kao poluga za coaching i standardizaciju rada.' : '',
            ])),
            'weaknesses' => array_values(array_filter([
                $conversion_percent < 35 ? 'Konverzija iz shop klikova u registracije još traži jači follow-up i jasniji put korisnika.' : '',
                !empty($team_ai_habits['top_blockers'][0]['label']) ? 'Najčešći blocker koji iskače u AI unosima je: ' . $team_ai_habits['top_blockers'][0]['label'] . '.' : '',
                (int) ($support_center['totals']['stale_total'] ?? 0) > 0 ? 'Otvoreni support upiti predugo stoje bez zatvaranja, što usporava osjećaj podrške i povjerenja.' : '',
            ])),
            'next_actions' => [
                'Generiraj tjednu GPT analizu iz ovog snapshot sloja čim spojimo AI action.',
                'Odaberi jednu webinar temu tjedno i pošalji ciljanu poruku risk i rising grupi.',
                $support_note,
            ],
            'coaching_priorities' => [
                'Risk grupa bez AI plana i bez novog check-ina.',
                'Suradnici s prometom bez registracija ili funnel leadova.',
                'Mentor follow-up slučajevi koji predugo stoje bez novog kontakta.',
            ],
            'support_insights' => [
                $support_note,
                'Recurring support teme pretvori u kratke odgovore, webinar i internu obavijest.',
                'Pazi koje se nejasnoće ponavljaju nakon odgovora jer to obično znači da objašnjenje nije dovoljno jasno ili sustav nije dovoljno vođen.',
            ],
            'kpis_to_watch' => [
                'Registracije po suradniku i po tržištu.',
                'Consistency score i broj zatvorenih AI ciklusa.',
                'Broj otvorenih/stale support upita kroz tjedan.',
            ],
            'team_message_preview' => 'Ovaj tjedan fokus je na jednostavnijem putu do rezultata: manje raspršenosti, jasniji follow-up i jedna konkretna akcija koju svi mogu provesti odmah.',
            'risk_group_message_preview' => 'Ako imaš promet bez rezultata ili si zapela u executionu, ovaj tjedan ne širimo aktivnosti nego čistimo glavni blocker i put do prijave.',
        ];
    }

    private function map_drilldown_items(array $rows, callable $value_resolver, ?callable $filter = null, int $limit = 60, ?callable $display_resolver = null): array {
        $items = [];

        foreach($rows as $row) {
            if($filter && !$filter($row)) {
                continue;
            }

            $metric = $value_resolver($row);

            if($metric === null) {
                continue;
            }

            $items[] = [
                'name' => (string) ($row['name'] ?? l('global.unknown')),
                'detail_url' => (string) ($row['detail_url'] ?? ''),
                'status_label' => (string) ($row['status_label'] ?? ''),
                'metric' => is_numeric($metric) ? (float) $metric : (string) $metric,
                'metric_display' => $display_resolver ? (string) $display_resolver($row, $metric) : (is_numeric($metric) ? nr((float) $metric) : (string) $metric),
                'meta' => trim(implode(' · ', array_filter([
                    !empty($row['strongest_country']) && ($row['strongest_country'] ?? '-') !== '-' ? (string) $row['strongest_country'] : '',
                    !empty($row['top_source_label']) ? (string) $row['top_source_label'] : '',
                    'LOS ' . (int) ($row['leader_os_score'] ?? 0),
                ]))),
            ];
        }

        usort($items, static function($a, $b) {
            return (($b['metric'] ?? 0) <=> ($a['metric'] ?? 0))
                ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return array_slice($items, 0, $limit);
    }

    private function build_drilldown_payload(string $title, array $rows, callable $value_resolver, ?callable $filter = null, int $limit = 60, ?callable $display_resolver = null, string $summary_mode = 'count'): array {
        $all_items = [];
        $signal_total = 0;

        foreach($rows as $row) {
            if($filter && !$filter($row)) {
                continue;
            }

            $metric = $value_resolver($row);

            if($metric === null) {
                continue;
            }

            if(in_array($summary_mode, ['sum_metric', 'average_metric'], true)) {
                $signal_total += is_numeric($metric) ? (float) $metric : 0;
            }

            $all_items[] = [
                'name' => (string) ($row['name'] ?? l('global.unknown')),
                'detail_url' => (string) ($row['detail_url'] ?? ''),
                'status_label' => (string) ($row['status_label'] ?? ''),
                'metric' => is_numeric($metric) ? (float) $metric : (string) $metric,
                'metric_display' => $display_resolver ? (string) $display_resolver($row, $metric) : (is_numeric($metric) ? nr((float) $metric) : (string) $metric),
                'meta' => trim(implode(' · ', array_filter([
                    !empty($row['strongest_country']) && ($row['strongest_country'] ?? '-') !== '-' ? (string) $row['strongest_country'] : '',
                    !empty($row['top_source_label']) ? (string) $row['top_source_label'] : '',
                    'LOS ' . (int) ($row['leader_os_score'] ?? 0),
                ]))),
            ];
        }

        usort($all_items, static function($a, $b) {
            return (($b['metric'] ?? 0) <=> ($a['metric'] ?? 0))
                ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        $contributors_total = count($all_items);

        if($summary_mode === 'count') {
            $signal_total = $contributors_total;
        } elseif($summary_mode === 'average_metric') {
            $signal_total = $contributors_total > 0 ? round($signal_total / $contributors_total, 1) : 0;
        }

        $items = array_slice($all_items, 0, $limit);
        $signal_total_display = is_float($signal_total) && floor($signal_total) !== $signal_total ? nr($signal_total) : nr((int) $signal_total);
        $summary_note = 'Suradnika u signalu: ' . nr($contributors_total);

        if($contributors_total > count($items)) {
            $summary_note .= ' · Prikazano: ' . nr(count($items));
        }

        return [
            'title' => $title,
            'signal_total' => $signal_total,
            'signal_total_display' => $signal_total_display,
            'contributors_total' => $contributors_total,
            'items' => $items,
            'summary_note' => $summary_note,
        ];
    }

    private function get_kpi_drilldowns_payload(array $rows, array $suspicious_clicks = []): array {
        $payload = [
            'team_score_average' => $this->build_drilldown_payload('LOS score tima', $rows, static fn($row) => (int) ($row['leader_os_score'] ?? 0), null, 60, static fn($row, $metric) => 'LOS ' . nr((int) $metric), 'average_metric'),
            'all_collaborators' => $this->build_drilldown_payload('Svi suradnici', $rows, static fn($row) => (int) ($row['leader_os_score'] ?? 0), null, 60, static fn($row, $metric) => 'LOS ' . nr((int) $metric)),
            'qualified' => $this->build_drilldown_payload('Kvalificirani suradnici', $rows, static fn($row) => (int) ($row['forever_shop_clicks_90d'] ?? 0), static fn($row) => !empty($row['qualified']), 60, static fn($row, $metric) => 'Shop 90d ' . nr((int) $metric)),
            'rising' => $this->build_drilldown_payload('Suradnici u rastu', $rows, static fn($row) => (float) ($row['growth_percent'] ?? 0), static fn($row) => (string) ($row['status_key'] ?? '') === 'rising', 60, static fn($row, $metric) => (($metric > 0 ? '+' : '') . nr((float) $metric) . '% rast')),
            'risk' => $this->build_drilldown_payload('Suradnici koji traže pažnju', $rows, static fn($row) => (int) ($row['risk_score'] ?? 0), static fn($row) => (string) ($row['status_key'] ?? '') === 'risk', 60, static fn($row, $metric) => 'Risk ' . nr((int) $metric)),
            'anomaly_high' => $this->build_drilldown_payload('Provjeri odmah', $rows, static fn($row) => (int) ($row['anomaly_score'] ?? 0), static fn($row) => (string) ($row['anomaly_stage_key'] ?? '') === 'high', 60, static fn($row, $metric) => 'Anomaly ' . nr((int) $metric)),
            'anomaly_watch' => $this->build_drilldown_payload('Za pracenje', $rows, static fn($row) => (int) ($row['anomaly_score'] ?? 0), static fn($row) => (string) ($row['anomaly_stage_key'] ?? '') === 'watch', 60, static fn($row, $metric) => 'Anomaly ' . nr((int) $metric)),
            'quality_ready' => $this->build_drilldown_payload('Jake aplikacije', $rows, static fn($row) => (int) ($row['app_quality_score'] ?? 0), static fn($row) => (int) ($row['app_quality_score'] ?? 0) >= 70, 60, static fn($row, $metric) => 'App ' . nr((int) $metric)),
            'active_collaborators' => $this->build_drilldown_payload('Aktivni suradnici', $rows, static fn($row) => (int) ($row['clicks_total_period'] ?? 0), static fn($row) => (int) ($row['clicks_total_period'] ?? 0) > 0 || (int) ($row['active_days_total'] ?? 0) > 0, 60, static fn($row, $metric) => 'Klikovi ' . nr((int) $metric)),
            'total_registrations_period' => $this->build_drilldown_payload('Registracije u periodu', $rows, static fn($row) => (int) ($row['forever_registration_clicks_period'] ?? 0), static fn($row) => (int) ($row['forever_registration_clicks_period'] ?? 0) > 0, 60, static fn($row, $metric) => 'Registracije ' . nr((int) $metric), 'sum_metric'),
            'total_funnel_leads_period' => $this->build_drilldown_payload('Funnel leadovi', $rows, static fn($row) => (int) ($row['app_funnel_registrations_period'] ?? 0), static fn($row) => (int) ($row['app_funnel_registrations_period'] ?? 0) > 0, 60, static fn($row, $metric) => 'Leadovi ' . nr((int) $metric), 'sum_metric'),
            'total_shop_clicks_period' => $this->build_drilldown_payload('Webshop klikovi u periodu', $rows, static fn($row) => (int) ($row['forever_shop_clicks_period'] ?? 0), static fn($row) => (int) ($row['forever_shop_clicks_period'] ?? 0) > 0, 60, static fn($row, $metric) => 'Shop klikovi ' . nr((int) $metric), 'sum_metric'),
            'active_pro_total' => $this->build_drilldown_payload('Aktivni PRO', $rows, static fn($row) => (int) ($row['leader_os_score'] ?? 0), static fn($row) => !empty($row['is_active_pro']), 60, static fn($row, $metric) => 'LOS ' . nr((int) $metric)),
            'ai_active_collaborators' => $this->build_drilldown_payload('AI aktivni suradnici', $rows, static fn($row) => (int) ($row['ai_access_growth_signal_30d'] ?? 0), static fn($row) => in_array((string) ($row['ai_usage_stage_key'] ?? 'inactive'), ['started', 'questionnaire', 'active'], true), 60, static fn($row, $metric) => 'AI signal ' . nr((int) $metric)),
            'ai_profiles_total' => $this->build_drilldown_payload('AI profili', $rows, static fn($row) => (int) ($row['leader_os_score'] ?? 0), function($row) {
                    $preferences = $this->get_preferences_object($row['preferences'] ?? null);
                    $profile = $preferences->leader_ai_profile ?? null;
                    $profile = is_array($profile) || is_object($profile) ? (array) $profile : [];

                    return !empty($profile['primary_goal']);
                }, 60, static fn($row, $metric) => 'LOS ' . nr((int) $metric)),
            'ai_checkins_total' => $this->build_drilldown_payload('AI check-inovi', $rows, static fn($row) => (int) ($row['days_since_last_checkin'] ?? 999), function($row) {
                    $preferences = $this->get_preferences_object($row['preferences'] ?? null);
                    $checkins = $preferences->leader_ai_weekly_checkins ?? [];
                    if(is_object($checkins)) $checkins = (array) $checkins;

                    return !empty($checkins[0]);
                }, 60, static fn($row, $metric) => ($metric === 999 ? 'Bez datuma' : 'Check-in prije ' . nr((int) $metric) . ' d')),
            'ai_plans_total' => $this->build_drilldown_payload('AI planovi', $rows, static fn($row) => (int) ($row['consistency_score'] ?? 0), function($row) {
                    $preferences = $this->get_preferences_object($row['preferences'] ?? null);
                    $plans = $preferences->leader_ai_weekly_plans ?? [];
                    if(is_object($plans)) $plans = (array) $plans;

                    return !empty($plans[0]);
                }, 60, static fn($row, $metric) => 'Consistency ' . nr((int) $metric)),
            'ai_outcomes_total' => $this->build_drilldown_payload('AI outcomes', $rows, static fn($row) => (int) ($row['consistency_score'] ?? 0), function($row) {
                    $preferences = $this->get_preferences_object($row['preferences'] ?? null);
                    $outcomes = $preferences->leader_ai_weekly_outcomes ?? [];
                    if(is_object($outcomes)) $outcomes = (array) $outcomes;

                    return !empty($outcomes[0]);
                }, 60, static fn($row, $metric) => 'Consistency ' . nr((int) $metric)),
            'ai_profile_only_total' => $this->build_drilldown_payload('Profil otvoren, ali bez check-ina', $rows, static fn($row) => (int) ($row['leader_os_score'] ?? 0), static fn($row) => !empty($row['has_profile']) && empty($row['has_checkin']), 60, static fn($row, $metric) => 'LOS ' . nr((int) $metric)),
            'ai_checkin_waiting_plan_total' => $this->build_drilldown_payload('Check-in postoji, ali plan nije generiran', $rows, static fn($row) => (int) ($row['days_since_last_checkin'] ?? 999), static fn($row) => !empty($row['has_checkin']) && empty($row['has_plan']), 60, static fn($row, $metric) => ($metric === 999 ? 'Bez datuma' : 'Check-in prije ' . nr((int) $metric) . ' d')),
            'ai_plan_waiting_outcome_total' => $this->build_drilldown_payload('AI plan postoji, ali outcome nije zatvoren', $rows, static fn($row) => (int) ($row['consistency_score'] ?? 0), static fn($row) => !empty($row['has_plan']) && trim((string) ($row['latest_outcome_completion_level'] ?? '')) === '', 60, static fn($row, $metric) => 'Consistency ' . nr((int) $metric)),
            'ai_stale_checkin_total' => $this->build_drilldown_payload('AI ritam je stao', $rows, static fn($row) => (int) ($row['days_since_last_checkin'] ?? 999), static fn($row) => !empty($row['has_checkin']) && (($row['days_since_last_checkin'] ?? null) !== null) && (int) ($row['days_since_last_checkin'] ?? 0) > 14, 60, static fn($row, $metric) => ($metric === 999 ? 'Bez datuma' : 'Zadnji check-in prije ' . nr((int) $metric) . ' d')),
            'ai_resultful_total' => $this->build_drilldown_payload('AI aktivni koji već imaju rezultat', $rows, static fn($row) => (int) ($row['ai_access_growth_signal_30d'] ?? 0), static function($row) {
                    $ai_active = in_array((string) ($row['ai_usage_stage_key'] ?? 'inactive'), ['started', 'questionnaire', 'active'], true);
                    $has_result = (int) ($row['ai_access_growth_signal_30d'] ?? 0) > 0
                        || (int) ($row['ai_access_shop_clicks_30d'] ?? 0) > 0
                        || (int) ($row['ai_access_funnel_registrations_30d'] ?? 0) > 0
                        || (int) ($row['ai_access_whatsapp_contacts_30d'] ?? 0) > 0;

                    return $ai_active && $has_result;
                }, 60, static fn($row, $metric) => 'Shop ' . nr((int) ($row['ai_access_shop_clicks_30d'] ?? 0)) . ' · Funnel ' . nr((int) ($row['ai_access_funnel_registrations_30d'] ?? 0)) . ' · WA ' . nr((int) ($row['ai_access_whatsapp_contacts_30d'] ?? 0))),
            'ai_active_no_result_total' => $this->build_drilldown_payload('AI aktivni bez rezultata', $rows, static fn($row) => (int) ($row['days_since_last_checkin'] ?? 999), static function($row) {
                    $ai_active = in_array((string) ($row['ai_usage_stage_key'] ?? 'inactive'), ['started', 'questionnaire', 'active'], true);
                    $has_result = (int) ($row['ai_access_growth_signal_30d'] ?? 0) > 0
                        || (int) ($row['ai_access_shop_clicks_30d'] ?? 0) > 0
                        || (int) ($row['ai_access_funnel_registrations_30d'] ?? 0) > 0
                        || (int) ($row['ai_access_whatsapp_contacts_30d'] ?? 0) > 0;

                    return $ai_active && !$has_result;
                }, 60, static fn($row, $metric) => ($metric === 999 ? 'Bez check-ina' : 'Zadnji check-in prije ' . nr((int) $metric) . ' d')),
            'ai_pro_without_usage_total' => $this->build_drilldown_payload('PRO bez AI navike', $rows, static fn($row) => (int) ($row['leader_os_score'] ?? 0), static fn($row) => !empty($row['is_active_pro']) && !in_array((string) ($row['ai_usage_stage_key'] ?? 'inactive'), ['started', 'questionnaire', 'active'], true), 60, static fn($row, $metric) => 'LOS ' . nr((int) $metric)),
            'ai_strong_routine_total' => $this->build_drilldown_payload('Jak AI ritam', $rows, static fn($row) => (int) ($row['consistency_score'] ?? 0), static fn($row) => !empty($row['has_plan']) && trim((string) ($row['latest_outcome_completion_level'] ?? '')) !== '' && (string) ($row['consistency_state_key'] ?? '') === 'strong', 60, static fn($row, $metric) => 'Consistency ' . nr((int) $metric)),
            'coaching_queue_total' => $this->build_drilldown_payload('Coaching queue', $rows, static fn($row) => (int) ($row['priority_score'] ?? 0), static fn($row) => !empty($row['queue_reason']), 60, static fn($row, $metric) => 'Priority ' . nr((int) $metric)),
            'coaching_mentored_this_week_total' => $this->build_drilldown_payload('Mentorirani ovaj tjedan', $rows, static fn($row) => (int) ($row['leader_os_score'] ?? 0), static fn($row) => !empty($row['mentored_this_week']), 60, static fn($row, $metric) => 'LOS ' . nr((int) $metric)),
            'coaching_needs_follow_up_total' => $this->build_drilldown_payload('Trebaju follow-up', $rows, static fn($row) => (int) ($row['days_since_last_contact'] ?? 0), static fn($row) => !empty($row['needs_follow_up']), 60, static fn($row, $metric) => 'Zadnji kontakt prije ' . nr((int) $metric) . ' d'),
            'coaching_stale_follow_up_total' => $this->build_drilldown_payload('Stale follow-up', $rows, static fn($row) => (int) ($row['days_since_last_contact'] ?? 999), static fn($row) => !empty($row['needs_follow_up']) && (($row['days_since_last_contact'] ?? null) === null || (int) ($row['days_since_last_contact'] ?? 0) >= 7), 60, static fn($row, $metric) => ($metric === 999 ? 'Bez kontakta' : 'Bez follow-upa ' . nr((int) $metric) . ' d')),
            'fraud_suspicious_affected_total' => [
                'title' => 'Pogođeni suradnici',
                'signal_total' => 0,
                'signal_total_display' => nr(0),
                'contributors_total' => 0,
                'summary_note' => 'Suradnika u signalu: 0',
                'items' => [],
            ],
            'fraud_queue_total' => $this->build_drilldown_payload('Fraud queue', $rows, static fn($row) => (int) ($row['anomaly_score'] ?? 0), static fn($row) => !empty($row['queue_reason']), 60, static fn($row, $metric) => 'Anomaly ' . nr((int) $metric)),
            'fraud_blocked_attempts_total' => [
                'title' => 'Blokirani pokušaji',
                'signal_total' => 0,
                'signal_total_display' => nr(0),
                'contributors_total' => 0,
                'summary_note' => 'Suradnika u signalu: 0',
                'items' => [],
            ],
        ];

        foreach(($suspicious_clicks['rows'] ?? []) as $suspicious_row) {
            $item = [
                'name' => (string) ($suspicious_row['name'] ?? l('global.unknown')),
                'detail_url' => (string) ($suspicious_row['detail_url'] ?? ''),
                'status_label' => (string) ($suspicious_row['status_label'] ?? ''),
                'metric' => (int) ($suspicious_row['blocked_attempts_total'] ?? 0),
                'metric_display' => 'Blocked ' . nr((int) ($suspicious_row['blocked_attempts_total'] ?? 0)),
                'meta' => trim(implode(' · ', array_filter([
                    (string) ($suspicious_row['top_reason_title'] ?? ''),
                    !empty($suspicious_row['top_target_label']) ? (string) $suspicious_row['top_target_label'] : '',
                    'Risk ' . (int) ($suspicious_row['risk_score'] ?? 0),
                ]))),
            ];

            $payload['fraud_suspicious_affected_total']['items'][] = $item;

            if((int) ($suspicious_row['blocked_attempts_total'] ?? 0) > 0) {
                $payload['fraud_blocked_attempts_total']['items'][] = $item;
            }
        }

        usort($payload['fraud_suspicious_affected_total']['items'], static fn($a, $b) => (($b['metric'] ?? 0) <=> ($a['metric'] ?? 0)) ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        usort($payload['fraud_blocked_attempts_total']['items'], static fn($a, $b) => (($b['metric'] ?? 0) <=> ($a['metric'] ?? 0)) ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

        $fraud_suspicious_affected_items = $payload['fraud_suspicious_affected_total']['items'];
        $fraud_blocked_attempt_items = $payload['fraud_blocked_attempts_total']['items'];

        $payload['fraud_suspicious_affected_total']['contributors_total'] = count($fraud_suspicious_affected_items);
        $payload['fraud_suspicious_affected_total']['signal_total'] = count($fraud_suspicious_affected_items);
        $payload['fraud_suspicious_affected_total']['signal_total_display'] = nr(count($fraud_suspicious_affected_items));
        $payload['fraud_suspicious_affected_total']['summary_note'] = 'Suradnika u signalu: ' . nr(count($fraud_suspicious_affected_items));
        if(count($fraud_suspicious_affected_items) > 60) {
            $payload['fraud_suspicious_affected_total']['summary_note'] .= ' · Prikazano: ' . nr(60);
        }
        $payload['fraud_suspicious_affected_total']['items'] = array_slice($fraud_suspicious_affected_items, 0, 60);

        $blocked_total = array_sum(array_map(static fn($item) => (int) ($item['metric'] ?? 0), $fraud_blocked_attempt_items));
        $payload['fraud_blocked_attempts_total']['contributors_total'] = count($fraud_blocked_attempt_items);
        $payload['fraud_blocked_attempts_total']['signal_total'] = $blocked_total;
        $payload['fraud_blocked_attempts_total']['signal_total_display'] = nr($blocked_total);
        $payload['fraud_blocked_attempts_total']['summary_note'] = 'Suradnika u signalu: ' . nr(count($fraud_blocked_attempt_items));
        if(count($fraud_blocked_attempt_items) > 60) {
            $payload['fraud_blocked_attempts_total']['summary_note'] .= ' · Prikazano: ' . nr(60);
        }
        $payload['fraud_blocked_attempts_total']['items'] = array_slice($fraud_blocked_attempt_items, 0, 60);

        return $payload;
    }

    private function get_primary_team_kpis_payload(array $rows, array $totals, string $period_key): array {
        $team_score_average = !empty($rows)
            ? round(array_sum(array_map(static fn($row) => (float) ($row['leader_os_score'] ?? 0), $rows)) / count($rows), 1)
            : 0.0;
        $active_pro_total = count(array_filter($rows, static fn($row) => !empty($row['is_active_pro'])));
        $previous_shop_clicks_total = array_sum(array_map(static fn($row) => (int) ($row['previous_forever_shop_clicks_period'] ?? 0), $rows));
        $previous_funnel_total = array_sum(array_map(static fn($row) => (int) ($row['previous_app_funnel_registrations_period'] ?? 0), $rows));

        $build_period_compare = static function(int $current, int $previous): array {
            $delta = $current - $previous;
            $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');

            if($previous === 0 && $current > 0) {
                $text = 'Novi signal u odnosu na prošli period';
            } elseif($delta === 0) {
                $text = 'Isto kao prošli period';
            } else {
                $text = 'Vs prošli period ' . ($delta > 0 ? '+' : '') . nr($delta);
            }

            return [
                'mode' => 'period',
                'current' => $current,
                'previous' => $previous,
                'delta' => $delta,
                'direction' => $direction,
                'text' => $text,
            ];
        };

        return [
            [
                'key' => 'team_score_average',
                'label' => 'LOS score tima',
                'value' => $team_score_average,
                'value_display' => nr($team_score_average) . '/100',
                'chip' => 'Glavni signal',
                'tooltip' => 'Prosjek LOS rezultata svih suradnika u odabranom periodu. Viši rezultat znači zdraviji tim, jači momentum i bolji put prema konverziji.',
                'hint' => 'Glavni health score za praćenje smjera cijelog tima.',
                'compare' => [
                    'mode' => 'snapshot',
                    'text' => 'Prosjek svih suradnika u odabranom periodu',
                ],
            ],
            [
                'key' => 'qualified',
                'label' => 'Kvalificirani suradnici',
                'value' => (int) ($totals['qualified'] ?? 0),
                'value_display' => nr((int) ($totals['qualified'] ?? 0)),
                'chip' => '90d baza',
                'tooltip' => 'Suradnici koji imaju barem 15 klikova prema Foreveru u zadnjih 90 dana. To je baza tima s dokazanim signalom aktivnosti.',
                'hint' => 'Pokazuje koliko tim ima stvarno aktivne jezgre.',
                'compare' => [
                    'mode' => 'snapshot',
                    'text' => 'Snapshot signala u zadnjih 90 dana',
                ],
            ],
            [
                'key' => 'total_funnel_leads_period',
                'label' => 'Funnel prijave',
                'value' => (int) ($totals['total_funnel_leads_period'] ?? 0),
                'value_display' => nr((int) ($totals['total_funnel_leads_period'] ?? 0)),
                'chip' => strtoupper($period_key),
                'tooltip' => 'Koliko je funnel obrazaca stvarno ispunjeno u odabranom periodu. Ovo je glavni lead output tima.',
                'hint' => 'Najvažniji izlaz interesa iz timskih aplikacija.',
                'compare' => $build_period_compare((int) ($totals['total_funnel_leads_period'] ?? 0), $previous_funnel_total),
            ],
            [
                'key' => 'total_shop_clicks_period',
                'label' => 'Klikovi prema Foreveru',
                'value' => (int) ($totals['total_shop_clicks_period'] ?? 0),
                'value_display' => nr((int) ($totals['total_shop_clicks_period'] ?? 0)),
                'chip' => strtoupper($period_key),
                'tooltip' => 'Ukupni klikovi iz aplikacija prema Forever odredištima u odabranom periodu. To je glavni signal namjere i interesa tima.',
                'hint' => 'Pokazuje ide li promet prema stvarnom poslovnom cilju.',
                'compare' => $build_period_compare((int) ($totals['total_shop_clicks_period'] ?? 0), $previous_shop_clicks_total),
            ],
            [
                'key' => 'active_pro_total',
                'label' => 'Aktivni PRO',
                'value' => $active_pro_total,
                'value_display' => nr($active_pro_total),
                'chip' => 'Sada',
                'tooltip' => 'Broj suradnika koji trenutno imaju aktivan PRO plan. Ovo je glavni monetizacijski snapshot tima.',
                'hint' => 'Trenutno stanje aktivne PRO baze.',
                'compare' => [
                    'mode' => 'snapshot',
                    'text' => 'Trenutno aktivni PRO računi',
                ],
            ],
        ];
    }

    private function get_message_targets_payload(array $rows, array $queue_rows = []): array {
        $groups = [
            'team' => [
                'label' => 'Cijeli tim',
                'user_ids' => [],
                'count' => 0,
            ],
            'risk' => [
                'label' => 'Risk grupa',
                'user_ids' => [],
                'count' => 0,
            ],
            'rising' => [
                'label' => 'Rising grupa',
                'user_ids' => [],
                'count' => 0,
            ],
            'priority' => [
                'label' => 'Priority grupa',
                'user_ids' => [],
                'count' => 0,
            ],
        ];
        $individual_targets = [];

        foreach($rows as $row) {
            $user_id = (int) ($row['user_id'] ?? 0);

            if($user_id <= 0) {
                continue;
            }

            $groups['team']['user_ids'][] = $user_id;

            if(!isset($individual_targets[$user_id])) {
                $individual_targets[$user_id] = [
                    'user_id' => $user_id,
                    'name' => (string) ($row['name'] ?? l('global.unknown')),
                    'status_label' => (string) ($row['status_label'] ?? ''),
                    'meta' => trim(implode(' · ', array_filter([
                        !empty($row['combined_priority_reason']) ? (string) ($row['combined_priority_reason'] ?? '') : (!empty($row['queue_reason']) ? (string) ($row['queue_reason'] ?? '') : ''),
                        !empty($row['strongest_country']) ? (string) ($row['strongest_country'] ?? '') : '',
                        'LOS ' . (int) ($row['leader_os_score'] ?? 0),
                    ]))),
                    'priority_score' => (int) (($row['combined_priority_score'] ?? 0) ?: ($row['queue_priority_score'] ?? 0)),
                ];
            }

            if((string) ($row['status_key'] ?? '') === 'risk') {
                $groups['risk']['user_ids'][] = $user_id;
            }

            if(in_array((string) ($row['status_key'] ?? ''), ['rising', 'high_potential'], true)) {
                $groups['rising']['user_ids'][] = $user_id;
            }
        }

        foreach($queue_rows as $row) {
            $user_id = (int) ($row['user_id'] ?? 0);

            if($user_id > 0) {
                $groups['priority']['user_ids'][] = $user_id;
            }
        }

        foreach($groups as $key => $group) {
            $groups[$key]['user_ids'] = array_values(array_unique(array_filter(array_map('intval', (array) ($group['user_ids'] ?? [])))));
            $groups[$key]['count'] = count($groups[$key]['user_ids']);
        }

        usort($individual_targets, static function($a, $b) {
            return (($b['priority_score'] ?? 0) <=> ($a['priority_score'] ?? 0))
                ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        $groups['individual_targets'] = array_values($individual_targets);

        return $groups;
    }

    private function get_suspicious_user_signal_map(array $rows, string $period_key): array {
        $signals = [];

        if(empty($rows) || !function_exists('fc_ensure_forever_click_integrity_tables')) {
            return $signals;
        }

        fc_ensure_forever_click_integrity_tables();

        $retention_days = function_exists('fc_get_forever_click_integrity_retention_days') ? fc_get_forever_click_integrity_retention_days() : 30;
        $effective_period_days = min($this->get_period_days($period_key), $retention_days);
        $period_start_datetime = $this->get_period_start_datetime($effective_period_days);
        $user_ids = array_values(array_filter(array_map(static fn($row) => (int) ($row['user_id'] ?? 0), $rows)));

        if(empty($user_ids)) {
            return $signals;
        }

        $user_ids_sql = implode(',', array_map('intval', $user_ids));
        $result = database()->query("SELECT
            `user_id`,
            `reason_title`,
            `reason_text`,
            `datetime`
        FROM `forever_click_integrity_suspicious`
        WHERE `datetime` >= '{$period_start_datetime}'
          AND `user_id` IN ({$user_ids_sql})
        ORDER BY `datetime` DESC");

        while($row = $result->fetch_assoc()) {
            $user_id = (int) ($row['user_id'] ?? 0);

            if($user_id <= 0) {
                continue;
            }

            if(!isset($signals[$user_id])) {
                $signals[$user_id] = [
                    'blocked_attempts_total' => 0,
                    'top_reason_title' => (string) ($row['reason_title'] ?? ''),
                    'top_reason_text' => (string) ($row['reason_text'] ?? ''),
                    'last_suspicious_at' => (string) ($row['datetime'] ?? ''),
                    'fraud_badge_label' => 'Fraud watch',
                    'fraud_badge_class' => 'status-warning',
                ];
            }

            $signals[$user_id]['blocked_attempts_total']++;

            if((string) ($row['datetime'] ?? '') >= (string) ($signals[$user_id]['last_suspicious_at'] ?? '')) {
                $signals[$user_id]['top_reason_title'] = (string) ($row['reason_title'] ?? '');
                $signals[$user_id]['top_reason_text'] = (string) ($row['reason_text'] ?? '');
                $signals[$user_id]['last_suspicious_at'] = (string) ($row['datetime'] ?? '');
            }
        }

        foreach($signals as $user_id => $signal) {
            if((int) ($signal['blocked_attempts_total'] ?? 0) >= 5) {
                $signals[$user_id]['fraud_badge_label'] = 'Fraud high';
                $signals[$user_id]['fraud_badge_class'] = 'status-danger';
            }
        }

        return $signals;
    }

    private function normalize_operations_user_row(object $row): array {
        $preferences = $this->get_preferences_object($row->preferences ?? null);
        $meta = $this->get_user_meta_object($preferences);
        $assets = $this->ensure_user_primary_assets((int) ($row->user_id ?? 0));
        $card_status = (int) ($meta->card_status ?? 0);
        $card_requested_at = (string) ($meta->fcc_nfc_requested_at ?? '');
        $card_sent_at = (string) ($meta->fcc_nfc_sent_at ?? '');
        $is_active_pro = $this->is_active_pro_row([
            'plan_id' => (string) ($row->plan_id ?? ''),
            'plan_expiration_date' => (string) ($row->plan_expiration_date ?? ''),
        ]);
        $card_required = $is_active_pro
            && $card_status !== 1
            && $card_sent_at === ''
            && (
                (int) ($meta->fcc_nfc_required ?? 0) === 1
                || !isset($meta->card_status)
                || $card_status === 0
            );

        $city_line = trim(implode(' ', array_filter([
            trim((string) ($meta->zip ?? '')),
            trim((string) ($meta->city ?? '')),
        ])));

        return [
            'user_id' => (int) ($row->user_id ?? 0),
            'name' => (string) ($row->name ?? l('global.unknown')),
            'email' => (string) ($row->email ?? ''),
            'status' => (int) ($row->status ?? 0),
            'status_label' => match((int) ($row->status ?? 0)) {
                1 => 'Aktivan',
                2 => 'Onemogućen',
                default => 'Čeka odobrenje',
            },
            'plan_id' => (string) ($row->plan_id ?? ''),
            'plan_name' => $this->get_plan_name_label($row->plan_id ?? '', $row->plan_name ?? null),
            'plan_expiration_date' => (string) ($row->plan_expiration_date ?? ''),
            'datetime' => (string) ($row->datetime ?? ''),
            'last_activity' => (string) ($row->last_activity ?? ''),
            'is_active_pro' => $is_active_pro,
            'is_trial_activation' => $is_active_pro && (float) ($row->payment_total_amount ?? 0) <= 0.0,
            'main_biolink_url' => $assets['main_biolink_url'],
            'main_biolink_nfc_url' => $assets['main_biolink_nfc_url'],
            'main_biolink_id' => (int) ($assets['main_biolink_id'] ?? 0),
            'qr_download_url' => url('admin/qr-code?user_id=' . (int) ($row->user_id ?? 0)),
            'letter_download_url' => url('admin/envelope?user_id=' . (int) ($row->user_id ?? 0)),
            'letter_exists' => file_exists(UPLOADS_PATH . 'qr_code/' . (int) ($row->user_id ?? 0) . '.pdf'),
            'admin_user_update_url' => url('admin/user-update/' . (int) ($row->user_id ?? 0)),
            'approval_datetime' => (string) ($meta->fcc_access_approved_at ?? ''),
            'approval_email_sent_at' => (string) ($meta->fcc_access_approval_email_sent_at ?? ''),
            'card_requested_at' => $card_requested_at,
            'card_sent_at' => $card_sent_at,
            'card_required' => $card_required,
            'card_status' => $card_status,
            'card_status_label' => $card_status === 1 ? 'Poslano' : ($card_required ? 'Čeka slanje' : 'Nije otvoreno'),
            'phone' => trim((string) ($meta->phone ?? '')),
            'forever_id' => trim((string) ($meta->foreverId ?? '')),
            'address_lines' => array_values(array_filter([
                trim((string) ($meta->address ?? '')),
                $city_line,
                trim((string) ($meta->country ?? '')),
            ])),
            'meta_limited' => (int) ($meta->limited ?? 0) === 1,
        ];
    }

    private function get_operations_payload(string $search_query = ''): array {
        $escaped_search = database()->real_escape_string($search_query);
        $search_sql = $search_query !== ''
            ? " AND (`users`.`name` LIKE '%{$escaped_search}%' OR `users`.`email` LIKE '%{$escaped_search}%')"
            : '';

        $total_registered = (int) (database()->query("SELECT COUNT(*) AS `total` FROM `users` WHERE 1=1 {$search_sql}")->fetch_object()->total ?? 0);
        $pending_approval_total = (int) (database()->query("SELECT COUNT(*) AS `total` FROM `users` WHERE `status` = 0 {$search_sql}")->fetch_object()->total ?? 0);

        $pending_rows = [];
        $pending_result = database()->query("SELECT
                `users`.`user_id`,
                `users`.`name`,
                `users`.`email`,
                `users`.`status`,
                `users`.`plan_id`,
                `users`.`plan_expiration_date`,
                `users`.`payment_total_amount`,
                `users`.`preferences`,
                `users`.`language`,
                `users`.`anti_phishing_code`,
                `users`.`datetime`,
                `users`.`last_activity`,
                `plans`.`name` AS `plan_name`
            FROM `users`
            LEFT JOIN `plans` ON CAST(`plans`.`plan_id` AS CHAR) = CAST(`users`.`plan_id` AS CHAR)
            WHERE `users`.`status` = 0
              {$search_sql}
            ORDER BY `users`.`datetime` DESC
            LIMIT 40");

        while($row = $pending_result->fetch_object()) {
            $pending_rows[] = $this->normalize_operations_user_row($row);
        }

        $recent_rows = [];
        $recent_result = database()->query("SELECT
                `users`.`user_id`,
                `users`.`name`,
                `users`.`email`,
                `users`.`status`,
                `users`.`plan_id`,
                `users`.`plan_expiration_date`,
                `users`.`payment_total_amount`,
                `users`.`preferences`,
                `users`.`language`,
                `users`.`anti_phishing_code`,
                `users`.`datetime`,
                `users`.`last_activity`,
                `plans`.`name` AS `plan_name`
            FROM `users`
            LEFT JOIN `plans` ON CAST(`plans`.`plan_id` AS CHAR) = CAST(`users`.`plan_id` AS CHAR)
            WHERE 1 = 1
              {$search_sql}
            ORDER BY `users`.`datetime` DESC
            LIMIT 18");

        while($row = $recent_result->fetch_object()) {
            $recent_rows[] = $this->normalize_operations_user_row($row);
        }

        $card_queue_rows = [];
        $card_candidates_result = database()->query("SELECT
                `users`.`user_id`,
                `users`.`name`,
                `users`.`email`,
                `users`.`status`,
                `users`.`plan_id`,
                `users`.`plan_expiration_date`,
                `users`.`payment_total_amount`,
                `users`.`preferences`,
                `users`.`language`,
                `users`.`anti_phishing_code`,
                `users`.`datetime`,
                `users`.`last_activity`,
                `plans`.`name` AS `plan_name`
            FROM `users`
            LEFT JOIN `plans` ON CAST(`plans`.`plan_id` AS CHAR) = CAST(`users`.`plan_id` AS CHAR)
            WHERE CAST(`users`.`plan_id` AS CHAR) = '5'
              {$search_sql}
            ORDER BY `users`.`datetime` DESC");

        while($row = $card_candidates_result->fetch_object()) {
            $normalized_row = $this->normalize_operations_user_row($row);

            if(!$normalized_row['is_active_pro'] || !$normalized_row['card_required']) {
                continue;
            }

            $card_queue_rows[] = $normalized_row;
        }

        usort($card_queue_rows, static function(array $a, array $b) {
            return strcmp((string) ($b['card_requested_at'] ?: $b['datetime']), (string) ($a['card_requested_at'] ?: $a['datetime']));
        });

        $sent_cards_total = 0;
        foreach($recent_rows as $row) {
            if((int) ($row['card_status'] ?? 0) === 1) {
                $sent_cards_total++;
            }
        }

        return [
            'totals' => [
                'registered' => $total_registered,
                'pending_approvals' => $pending_approval_total,
                'card_queue' => count($card_queue_rows),
                'sent_cards_recent' => $sent_cards_total,
            ],
            'pending_rows' => $pending_rows,
            'card_queue_rows' => array_slice($card_queue_rows, 0, 40),
            'recent_rows' => $recent_rows,
        ];
    }

    private function handle_operations_approval_action(array $redirect_query): void {
        if(!isset($_POST['los_approve_access'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $user_id = (int) ($_POST['user_id'] ?? 0);

        if($user_id <= 0) {
            Alerts::add_error('Suradnik nije valjan.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $user = db()->where('user_id', $user_id)->getOne('users', [
            'user_id',
            'name',
            'email',
            'status',
            'preferences',
            'language',
            'anti_phishing_code',
        ]);

        if(!$user) {
            Alerts::add_error('Suradnik nije pronađen.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $preferences = $this->get_preferences_object($user->preferences ?? null);
        $meta = $this->get_user_meta_object($preferences);
        $assets = $this->ensure_user_primary_assets($user_id);

        if((int) ($user->status ?? 0) !== 1) {
            db()->where('user_id', $user_id)->update('users', [
                'status' => 1,
            ]);

            $this->fire_user_new_webhook($user_id, (string) $user->name, (string) $user->email, $meta, $assets['main_biolink_url']);
        }

        if(empty($meta->fcc_access_approved_at)) {
            $meta->fcc_access_approved_at = get_date();
        }

        $should_send_approval_email = empty($meta->fcc_access_approval_email_sent_at);

        if($should_send_approval_email) {
            $this->send_fcc_access_approved_email($user, $assets['main_biolink_url']);
            $meta->fcc_access_approval_email_sent_at = get_date();
        }

        $preferences->meta = $meta;

        db()->where('user_id', $user_id)->update('users', [
            'preferences' => json_encode($preferences),
        ]);

        cache()->deleteItemsByTag('user_id=' . $user_id);
        cache()->deleteItem('user?user_id=' . $user_id);

        Alerts::add_success('Pristup je odobren za ' . ($user->name ?: ('korisnika #' . $user_id)) . '.');

        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function handle_operations_rejection_action(array $redirect_query): void {
        if(!isset($_POST['los_reject_access'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $user_id = (int) ($_POST['user_id'] ?? 0);

        if($user_id <= 0) {
            Alerts::add_error('Suradnik nije valjan.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if($user_id === (int) ($this->user->user_id ?? 0)) {
            Alerts::add_error('Nije moguće odbiti i obrisati vlastiti admin račun.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $user = db()->where('user_id', $user_id)->getOne('users', [
            'user_id',
            'name',
            'email',
            'status',
            'language',
            'anti_phishing_code',
        ]);

        if(!$user) {
            Alerts::add_error('Suradnik nije pronađen.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if((int) ($user->status ?? 0) === 1) {
            Alerts::add_error('Pristup je već odobren i ovaj račun se više ne nalazi u listi za odbijanje.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $email_sent = $this->send_fcc_access_rejected_email($user);

        if(!$email_sent) {
            Alerts::add_error('Email odbijenice nije poslan, pa račun nije obrisan. Provjeri postavke slanja emaila i pokušaj ponovno.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        (new \Altum\Models\User())->delete($user_id);

        Alerts::add_success('Pristup je odbijen, email je poslan i račun je uklonjen za ' . ($user->name ?: ('korisnika #' . $user_id)) . '.');

        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function handle_operations_card_action(array $redirect_query): void {
        if(!isset($_POST['los_mark_card_sent'])) {
            return;
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $user_id = (int) ($_POST['user_id'] ?? 0);

        if($user_id <= 0) {
            Alerts::add_error('Suradnik nije valjan.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $user = db()->where('user_id', $user_id)->getOne('users', [
            'user_id',
            'name',
            'email',
            'preferences',
            'language',
            'anti_phishing_code',
        ]);

        if(!$user) {
            Alerts::add_error('Suradnik nije pronađen.');
            redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        $preferences = $this->get_preferences_object($user->preferences ?? null);
        $meta = $this->get_user_meta_object($preferences);
        $assets = $this->ensure_user_primary_assets($user_id);
        $should_send_card_email = empty($meta->fcc_nfc_sent_email_sent_at);

        $meta->card_status = 1;
        $meta->fcc_nfc_required = 0;
        $meta->send_card_email = $meta->send_card_email ?? get_date();

        if(empty($meta->fcc_nfc_requested_at)) {
            $meta->fcc_nfc_requested_at = get_date();
        }

        if(empty($meta->fcc_nfc_sent_at)) {
            $meta->fcc_nfc_sent_at = get_date();
        }

        if($should_send_card_email) {
            $this->send_fcc_card_sent_email($user, $assets['main_biolink_url']);
            $meta->fcc_nfc_sent_email_sent_at = get_date();
        }

        $preferences->meta = $meta;

        db()->where('user_id', $user_id)->update('users', [
            'preferences' => json_encode($preferences),
        ]);

        cache()->deleteItemsByTag('user_id=' . $user_id);
        cache()->deleteItem('user?user_id=' . $user_id);

        Alerts::add_success('Kartica je označena kao poslana za ' . ($user->name ?: ('korisnika #' . $user_id)) . '.');

        redirect('admin/leader-operating-system' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
    }

    private function get_overview_payload(string $period_key, string $search_query, string $status_filter, string $ai_status_filter, string $anomaly_status_filter, string $fraud_status_filter, string $sort_key, int $page): array {
        $period_days = $this->get_period_days($period_key);
        $period_start_datetime = $this->get_period_start_datetime($period_days);
        $previous_period_start_datetime = (new \DateTimeImmutable($period_start_datetime))->sub(new \DateInterval('P' . $period_days . 'D'))->format('Y-m-d H:i:s');
        $ninety_days_start_datetime = $this->get_period_start_datetime(90);
        $query_start_datetime = $period_days === 90 ? $previous_period_start_datetime : $ninety_days_start_datetime;
        $los_self_user_id = (int) ($this->user->user_id ?? 0);
        $los_user_scope_sql = $los_self_user_id > 0
            ? "(`users`.`type` = 0 OR `users`.`user_id` = {$los_self_user_id})"
            : "`users`.`type` = 0";
        $biolink_sets = $this->get_biolink_sets();
        $shop_condition = \Altum\Link::get_forever_shop_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql']);
        $registration_condition = \Altum\Link::get_forever_registration_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_registration_block_types_sql']);
        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);

        $rows = [];
        $result = database()->query("SELECT
            `users`.`user_id`,
            `users`.`name`,
            `users`.`email`,
            `users`.`plan_id`,
            `users`.`plan_expiration_date`,
            `users`.`preferences`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' THEN 1 ELSE 0 END) AS `clicks_total_period`,
            COUNT(DISTINCT CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' AND {$outbound_condition} THEN DATE(`track_links`.`datetime`) ELSE NULL END) AS `active_days_total`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$shop_condition} THEN 1 ELSE 0 END) AS `forever_shop_clicks_period`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$registration_condition} THEN 1 ELSE 0 END) AS `forever_registration_clicks_period`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$previous_period_start_datetime}' AND `track_links`.`datetime` < '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$shop_condition} THEN 1 ELSE 0 END) AS `previous_forever_shop_clicks_period`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$ninety_days_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$outbound_condition} THEN 1 ELSE 0 END) AS `forever_shop_clicks_90d`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$ninety_days_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$registration_condition} THEN 1 ELSE 0 END) AS `forever_registration_clicks_90d`,
            MAX(`track_links`.`datetime`) AS `last_click_at`
        FROM `users`
        LEFT JOIN `track_links` ON `track_links`.`user_id` = `users`.`user_id` AND `track_links`.`datetime` >= '{$query_start_datetime}'
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE {$los_user_scope_sql}
        GROUP BY `users`.`user_id`
        ORDER BY `users`.`name` ASC");

        $search_query_normalized = mb_strtolower(trim($search_query));

        while($row = $result->fetch_object()) {
            $forever_id = $this->extract_forever_id_from_preferences($row->preferences ?? null);

            if($search_query_normalized !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    (string) ($row->name ?? ''),
                    (string) ($row->email ?? ''),
                    (string) $forever_id,
                ]));

                if(mb_strpos($haystack, $search_query_normalized) === false) {
                    continue;
                }
            }

            $candidate = [
                'user_id' => (int) ($row->user_id ?? 0),
                'name' => (string) ($row->name ?? l('global.unknown')),
                'email' => (string) ($row->email ?? ''),
                'plan_id' => (string) ($row->plan_id ?? ''),
                'plan_expiration_date' => (string) ($row->plan_expiration_date ?? ''),
                'preferences' => $row->preferences ?? null,
                'forever_id' => $forever_id,
                'clicks_total_period' => (int) ($row->clicks_total_period ?? 0),
                'active_days_total' => (int) ($row->active_days_total ?? 0),
                'forever_shop_clicks_period' => (int) ($row->forever_shop_clicks_period ?? 0),
                'forever_registration_clicks_period' => (int) ($row->forever_registration_clicks_period ?? 0),
                'previous_forever_shop_clicks_period' => (int) ($row->previous_forever_shop_clicks_period ?? 0),
                'forever_shop_clicks_90d' => (int) ($row->forever_shop_clicks_90d ?? 0),
                'forever_registration_clicks_90d' => (int) ($row->forever_registration_clicks_90d ?? 0),
                'last_click_at' => (string) ($row->last_click_at ?? ''),
                'detail_url' => url('admin/leader-operating-system-leader?user_id=' . (int) ($row->user_id ?? 0) . '&period=' . $period_key),
                'admin_user_url' => url('admin/user-view/' . (int) ($row->user_id ?? 0)),
            ];
            $candidate['is_active_pro'] = $this->is_active_pro_row($candidate);

            $rows[] = $candidate;
        }

        $rows = $this->enrich_rows_with_app_signals($rows, $period_start_datetime, $previous_period_start_datetime);
        $rows = $this->enrich_rows_with_ai_growth_signal($rows);

        foreach($rows as $index => $candidate) {
            $candidate['growth'] = $this->get_growth_metrics($candidate['forever_shop_clicks_period'], $candidate['previous_forever_shop_clicks_period']);
            $candidate['growth_percent'] = $candidate['growth']['growth_percent'];
            $candidate['growth_difference'] = $candidate['growth']['difference'];
            $candidate = array_merge($candidate, $this->get_scores($candidate));
            $candidate = array_merge($candidate, $this->get_status_payload($candidate));
            $candidate = array_merge($candidate, $this->get_ai_plan_overview_context($candidate['preferences'] ?? null));
            $candidate = array_merge($candidate, $this->get_ai_access_payload($candidate));
            $candidate = array_merge($candidate, $this->get_consistency_payload($candidate));
            $candidate = array_merge($candidate, $this->get_overview_anomaly_payload($candidate));
            $candidate = array_merge($candidate, $this->get_queue_priority_payload($candidate));
            $rows[$index] = $candidate;
        }

        $team_scope_rows = $rows;

        $rows = array_values(array_filter($rows, function($candidate) use ($status_filter, $ai_status_filter, $anomaly_status_filter) {
            if($status_filter !== 'all') {
                $matches_filter = match($status_filter) {
                    'qualified' => (bool) ($candidate['qualified'] ?? false),
                    'rising', 'stable', 'risk', 'high_potential', 'inactive' => ($candidate['status_key'] ?? '') === $status_filter,
                    default => true,
                };

                if(!$matches_filter) {
                    return false;
                }
            }

            if($ai_status_filter !== 'all' && ($candidate['ai_usage_stage_key'] ?? 'inactive') !== $ai_status_filter) {
                return false;
            }

            if($anomaly_status_filter !== 'all' && ($candidate['anomaly_stage_key'] ?? 'stable') !== $anomaly_status_filter) {
                return false;
            }

            return true;
        }));

        $rows = $this->enrich_rows_with_context($rows, $period_start_datetime, $biolink_sets);
        $suspicious_user_signals = $this->get_suspicious_user_signal_map($rows, $period_key);

        $queue_rows = array_values(array_filter($rows, static function($row) {
            return (int) ($row['queue_priority_score'] ?? 0) > 0;
        }));

        usort($queue_rows, static function($a, $b) {
            return (($b['queue_priority_score'] ?? 0) <=> ($a['queue_priority_score'] ?? 0))
                ?: (($b['anomaly_score'] ?? 0) <=> ($a['anomaly_score'] ?? 0))
                ?: (($b['risk_score'] ?? 0) <=> ($a['risk_score'] ?? 0))
                ?: (($a['name'] ?? '') <=> ($b['name'] ?? ''));
        });

        $queue_rows = array_slice($queue_rows, 0, 6);

        $recent_coaching_rows = array_values(array_filter($rows, static function($row) {
            return !empty($row['latest_mentor_event_at']);
        }));

        usort($recent_coaching_rows, static function($a, $b) {
            return strcmp((string) ($b['latest_mentor_event_at'] ?? ''), (string) ($a['latest_mentor_event_at'] ?? ''));
        });

        $recent_coaching_rows = array_slice($recent_coaching_rows, 0, 8);

        $alerts_totals = [
            'manual_follow_up' => 0,
            'weekly_signal_gaps' => 0,
            'execution_or_risk' => 0,
        ];
        $alert_rows = [];

        foreach($rows as $row) {
            $row_alerts = $this->get_alert_entries($row);

            foreach($row_alerts as $alert) {
                if(($alert['type'] ?? '') === 'manual_follow_up') {
                    $alerts_totals['manual_follow_up']++;
                }

                if(in_array(($alert['type'] ?? ''), ['missing_weekly', 'stale_weekly'], true)) {
                    $alerts_totals['weekly_signal_gaps']++;
                }

                if(in_array(($alert['type'] ?? ''), ['weak_execution', 'analytics_risk'], true)) {
                    $alerts_totals['execution_or_risk']++;
                }

                $alert_rows[] = [
                    'name' => $row['name'],
                    'label' => $alert['label'],
                    'detail_url' => $row['detail_url'],
                ];
            }
        }

        $alert_rows = array_slice($alert_rows, 0, 8);

        foreach($rows as $index => $row) {
            $user_id = (int) ($row['user_id'] ?? 0);
            $rows[$index] = array_merge($row, $suspicious_user_signals[$user_id] ?? [
                'blocked_attempts_total' => 0,
                'top_reason_title' => '',
                'top_reason_text' => '',
                'last_suspicious_at' => '',
                'fraud_badge_label' => '',
                'fraud_badge_class' => 'status-dark',
            ]);
            $rows[$index]['fraud_stage_key'] = (int) ($rows[$index]['blocked_attempts_total'] ?? 0) >= 5 ? 'high' : ((int) ($rows[$index]['blocked_attempts_total'] ?? 0) > 0 ? 'watch' : 'clean');
            $rows[$index] = array_merge($rows[$index], $this->get_combined_priority_payload($rows[$index]));
        }

        if($fraud_status_filter !== 'all') {
            $rows = array_values(array_filter($rows, static function($row) use ($fraud_status_filter) {
                return (string) ($row['fraud_stage_key'] ?? 'clean') === $fraud_status_filter;
            }));
        }

        $queue_rows = array_values(array_filter($rows, static function($row) {
            return (int) ($row['combined_priority_score'] ?? 0) > 0;
        }));

        usort($queue_rows, static function($a, $b) {
            return (($b['combined_priority_score'] ?? 0) <=> ($a['combined_priority_score'] ?? 0))
                ?: (($b['blocked_attempts_total'] ?? 0) <=> ($a['blocked_attempts_total'] ?? 0))
                ?: (($b['anomaly_score'] ?? 0) <=> ($a['anomaly_score'] ?? 0))
                ?: (($b['risk_score'] ?? 0) <=> ($a['risk_score'] ?? 0))
                ?: (($a['name'] ?? '') <=> ($b['name'] ?? ''));
        });

        $queue_rows = array_slice($queue_rows, 0, 6);

        $recent_coaching_rows = array_values(array_filter($rows, static function($row) {
            return !empty($row['latest_mentor_event_at']);
        }));

        usort($recent_coaching_rows, static function($a, $b) {
            return strcmp((string) ($b['latest_mentor_event_at'] ?? ''), (string) ($a['latest_mentor_event_at'] ?? ''));
        });

        $recent_coaching_rows = array_slice($recent_coaching_rows, 0, 8);

        $alerts_totals = [
            'manual_follow_up' => 0,
            'weekly_signal_gaps' => 0,
            'execution_or_risk' => 0,
        ];
        $alert_rows = [];

        foreach($rows as $row) {
            $row_alerts = $this->get_alert_entries($row);

            foreach($row_alerts as $alert) {
                if(($alert['type'] ?? '') === 'manual_follow_up') {
                    $alerts_totals['manual_follow_up']++;
                }

                if(in_array(($alert['type'] ?? ''), ['missing_weekly', 'stale_weekly'], true)) {
                    $alerts_totals['weekly_signal_gaps']++;
                }

                if(in_array(($alert['type'] ?? ''), ['weak_execution', 'analytics_risk'], true)) {
                    $alerts_totals['execution_or_risk']++;
                }

                $alert_rows[] = [
                    'name' => $row['name'],
                    'label' => $alert['label'],
                    'detail_url' => $row['detail_url'],
                ];
            }
        }

        $alert_rows = array_slice($alert_rows, 0, 8);

        $totals = [
            'all_collaborators' => count($rows),
            'qualified' => 0,
            'rising' => 0,
            'risk' => 0,
            'anomaly_high' => 0,
            'anomaly_watch' => 0,
            'high_potential' => 0,
            'total_shop_clicks_period' => 0,
            'quality_ready' => 0,
            'active_pro_total' => 0,
        ];

        foreach($rows as $row) {
            if($row['qualified']) {
                $totals['qualified']++;
            }
            if($row['status_key'] === 'rising') {
                $totals['rising']++;
            }
            if($row['status_key'] === 'risk') {
                $totals['risk']++;
            }
            if(($row['anomaly_stage_key'] ?? 'stable') === 'high') {
                $totals['anomaly_high']++;
            }
            if(($row['anomaly_stage_key'] ?? 'stable') === 'watch') {
                $totals['anomaly_watch']++;
            }
            if($row['status_key'] === 'high_potential') {
                $totals['high_potential']++;
            }
            $totals['total_shop_clicks_period'] += (int) $row['forever_shop_clicks_period'];
            if((int) ($row['app_quality_score'] ?? 0) >= 70) {
                $totals['quality_ready']++;
            }
            if(!empty($row['is_active_pro'])) {
                $totals['active_pro_total']++;
            }
        }

        $totals['active_collaborators'] = count(array_filter($rows, static function($row) {
            return (int) ($row['clicks_total_period'] ?? 0) > 0 || (int) ($row['active_days_total'] ?? 0) > 0;
        }));
        $totals['total_registrations_period'] = array_sum(array_map(static fn($row) => (int) ($row['forever_registration_clicks_period'] ?? 0), $rows));
        $totals['total_funnel_leads_period'] = array_sum(array_map(static fn($row) => (int) ($row['app_funnel_registrations_period'] ?? 0), $rows));
        $totals['ai_active_collaborators'] = count(array_filter($rows, static function($row) {
            return in_array((string) ($row['ai_usage_stage_key'] ?? 'inactive'), ['started', 'questionnaire', 'active'], true);
        }));
        $totals['ai_plans_total'] = count(array_filter($rows, static fn($row) => !empty($row['has_plan'])));
        $totals['mentored_this_week_total'] = count(array_filter($rows, static fn($row) => !empty($row['mentored_this_week'])));

        $suspicious_clicks = $this->get_suspicious_click_overview_payload($rows, $period_key);
        $fraud_dashboard = $this->get_fraud_dashboard_payload($rows, $suspicious_clicks);

        usort($rows, function($a, $b) use ($sort_key) {
            return match($sort_key) {
                'app_quality' => (($b['app_quality_score'] ?? 0) <=> ($a['app_quality_score'] ?? 0)) ?: (($b['app_signal_score'] ?? 0) <=> ($a['app_signal_score'] ?? 0)),
                'fraud' => (($b['blocked_attempts_total'] ?? 0) <=> ($a['blocked_attempts_total'] ?? 0)) ?: (($b['anomaly_score'] ?? 0) <=> ($a['anomaly_score'] ?? 0)) ?: (($b['risk_score'] ?? 0) <=> ($a['risk_score'] ?? 0)),
                'shop_clicks' => ($b['forever_shop_clicks_period'] <=> $a['forever_shop_clicks_period']) ?: ($b['leader_os_score'] <=> $a['leader_os_score']),
                'growth' => (($b['growth_percent'] ?? -9999) <=> ($a['growth_percent'] ?? -9999)) ?: ($b['leader_os_score'] <=> $a['leader_os_score']),
                'registrations' => ($b['forever_registration_clicks_period'] <=> $a['forever_registration_clicks_period']) ?: ($b['leader_os_score'] <=> $a['leader_os_score']),
                'risk' => ($b['risk_score'] <=> $a['risk_score']) ?: ($b['leader_os_score'] <=> $a['leader_os_score']),
                'country' => ($b['strongest_country_count'] <=> $a['strongest_country_count']) ?: (($a['strongest_country'] ?? '') <=> ($b['strongest_country'] ?? '')),
                'source' => ($b['top_source_count'] <=> $a['top_source_count']) ?: (($a['top_source_label'] ?? '') <=> ($b['top_source_label'] ?? '')),
                'last_click' => (($b['last_click_at'] ?? '') <=> ($a['last_click_at'] ?? '')) ?: ($b['leader_os_score'] <=> $a['leader_os_score']),
                default => ($b['leader_os_score'] <=> $a['leader_os_score']) ?: ($b['forever_shop_clicks_period'] <=> $a['forever_shop_clicks_period']),
            };
        });

        $team_ai_habits = $this->get_team_ai_habits_payload($rows);
        $team_ai_dashboard = $this->get_team_ai_dashboard_payload($rows);
        $team_ai_distributions = $this->get_team_ai_distribution_payload($rows);
        $team_ai_text_intelligence = $this->get_team_ai_text_intelligence_payload($rows);
        $team_ai_actions = $this->get_team_ai_action_payload($rows, $team_ai_text_intelligence);
        $team_consistency = $this->get_team_consistency_payload($rows);
        $team_coaching_roi = $this->get_team_coaching_roi_payload($rows);
        $team_momentum = $this->get_team_momentum_payload($rows);
        $team_trend_start_datetime = (new \DateTimeImmutable('today'))->modify('-89 days')->format('Y-m-d 00:00:00');
        $team_trend = $this->get_team_trend_payload($team_trend_start_datetime, '90d', $rows, $biolink_sets);
        $status_distribution = $this->get_status_distribution_payload($team_scope_rows);
        $team_leaderboards = $this->get_team_leaderboards_payload($rows);
        $countries_matrix = $this->get_countries_matrix_payload($period_start_datetime, $biolink_sets);
        $activity_heatmap = $this->get_activity_heatmap_payload($period_start_datetime, $rows, $biolink_sets);
        $message_targets = $this->get_message_targets_payload($rows, $queue_rows);

        $all_rows = $rows;
        $total_results = count($all_rows);
        $per_page = 25;
        $total_pages = max(1, (int) ceil($total_results / $per_page));
        $page = max(1, min($page, $total_pages));
        $offset = ($page - 1) * $per_page;
        $rows = array_slice($all_rows, $offset, $per_page);

        $coaching_dashboard = $this->get_coaching_dashboard_payload($all_rows, $queue_rows, $recent_coaching_rows);
        $support_center = $this->get_support_center_payload($all_rows);
        $selected_support_ticket = $this->get_selected_support_ticket_payload((int) ($_GET['support_ticket_id'] ?? 0));

        $kpi_drilldowns = $this->get_kpi_drilldowns_payload($all_rows, $suspicious_clicks);
        $primary_team_kpis = $this->get_primary_team_kpis_payload($all_rows, $totals, $period_key);

        foreach($primary_team_kpis as $primary_kpi) {
            $key = (string) ($primary_kpi['key'] ?? '');

            if($key === '' || empty($kpi_drilldowns[$key])) {
                continue;
            }

            $compare_payload = $primary_kpi['compare'] ?? [];
            if(!empty($primary_kpi['value_display'])) {
                $kpi_drilldowns[$key]['signal_total_display'] = (string) $primary_kpi['value_display'];
            }
            $summary_note_parts = [];

            if(!empty($compare_payload['text'])) {
                $summary_note_parts[] = (string) $compare_payload['text'];
            }

            $summary_note_parts[] = 'Klik na ime otvara detalj suradnika.';
            $kpi_drilldowns[$key]['summary_note'] = implode(' · ', array_filter($summary_note_parts));
        }

        $overview_payload = [
            'totals' => $totals,
            'kpi_drilldowns' => $kpi_drilldowns,
            'primary_team_kpis' => $primary_team_kpis,
            'selected_period' => $period_key,
            'team_signal_chart' => $this->get_team_signal_chart_payload($period_start_datetime, $this->get_period_days($period_key)),
            'team_country_signal_matrix_periods' => $this->get_team_country_signal_matrix_periods_payload(),
            'team_analytics' => $this->get_team_analytics_payload($period_start_datetime, $biolink_sets),
            'team_trend' => $team_trend,
            'status_distribution' => $status_distribution,
            'team_leaderboards' => $team_leaderboards,
            'countries_matrix' => $countries_matrix,
            'activity_heatmap' => $activity_heatmap,
            'team_blog_forever' => $this->get_team_blog_forever_payload($period_start_datetime),
            'team_ai_habits' => $team_ai_habits,
            'team_ai_dashboard' => $team_ai_dashboard,
            'team_ai_distributions' => $team_ai_distributions,
            'team_ai_text_intelligence' => $team_ai_text_intelligence,
            'team_ai_actions' => $team_ai_actions,
            'team_consistency' => $team_consistency,
            'team_coaching_roi' => $team_coaching_roi,
            'coaching_dashboard' => $coaching_dashboard,
            'support_center' => $support_center,
            'selected_support_ticket' => $selected_support_ticket,
            'message_targets' => $message_targets,
            'team_momentum' => $team_momentum,
            'queue_rows' => $queue_rows,
            'recent_coaching_rows' => $recent_coaching_rows,
            'alerts' => [
                'totals' => $alerts_totals,
                'rows' => $alert_rows,
            ],
            'fraud_dashboard' => $fraud_dashboard,
            'suspicious_clicks' => $suspicious_clicks,
            'rows' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_results' => $total_results,
                'total_pages' => $total_pages,
                'from' => $total_results > 0 ? ($offset + 1) : 0,
                'to' => min($offset + $per_page, $total_results),
            ],
        ];

        $overview_payload['executive_summary'] = $this->get_executive_summary_payload($overview_payload);
        $overview_payload['team_snapshot'] = $this->get_team_snapshot_payload($overview_payload);
        $overview_payload['team_strategist'] = $this->get_team_strategist_payload($overview_payload);

        return $overview_payload;
    }

    public function index() {
        $allowed_periods = ['7d', '30d', '90d'];
        $selected_period = isset($_GET['period']) && in_array($_GET['period'], $allowed_periods, true) ? $_GET['period'] : '30d';
        $allowed_statuses = ['all', 'qualified', 'rising', 'stable', 'risk', 'high_potential', 'inactive'];
        $selected_status = isset($_GET['status']) && in_array($_GET['status'], $allowed_statuses, true) ? $_GET['status'] : 'all';
        $allowed_ai_statuses = ['all', 'inactive', 'started', 'questionnaire', 'active'];
        $selected_ai_status = isset($_GET['ai_status']) && in_array($_GET['ai_status'], $allowed_ai_statuses, true) ? $_GET['ai_status'] : 'all';
        $allowed_anomaly_statuses = ['all', 'stable', 'watch', 'high'];
        $selected_anomaly_status = isset($_GET['anomaly_status']) && in_array($_GET['anomaly_status'], $allowed_anomaly_statuses, true) ? $_GET['anomaly_status'] : 'all';
        $allowed_fraud_statuses = ['all', 'clean', 'watch', 'high'];
        $selected_fraud_status = isset($_GET['fraud_status']) && in_array($_GET['fraud_status'], $allowed_fraud_statuses, true) ? $_GET['fraud_status'] : 'all';
        $allowed_sorts = ['leader_os', 'app_quality', 'fraud', 'shop_clicks', 'growth', 'registrations', 'risk', 'country', 'source', 'last_click'];
        $selected_sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts, true) ? $_GET['sort'] : 'leader_os';
        $allowed_tabs = ['overview', 'operations', 'collaborators', 'analytics', 'ai', 'fraud', 'coaching', 'support'];
        $selected_tab = isset($_GET['tab']) && in_array($_GET['tab'], $allowed_tabs, true) ? $_GET['tab'] : 'overview';
        $search_query = trim((string) ($_GET['search'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $selected_support_ticket_id = max(0, (int) ($_GET['support_ticket_id'] ?? 0));

        $redirect_query = [
            'period' => $selected_period,
            'search' => $search_query,
            'status' => $selected_status,
            'ai_status' => $selected_ai_status,
            'anomaly_status' => $selected_anomaly_status,
            'fraud_status' => $selected_fraud_status,
            'sort' => $selected_sort,
            'tab' => $selected_tab,
            'page' => $page,
            'support_ticket_id' => $selected_support_ticket_id,
        ];

        if(!empty($_POST)) {
            $this->handle_operations_rejection_action($redirect_query);
            $this->handle_operations_approval_action($redirect_query);
            $this->handle_operations_card_action($redirect_query);
            $this->handle_team_strategist_action($redirect_query);
            $this->handle_support_ticket_ai_action($redirect_query);
            $this->handle_support_ticket_reply_action($redirect_query);
            $this->handle_support_ticket_status_action($redirect_query);
            $this->handle_support_ticket_webinar_action($redirect_query);
            $this->handle_message_center_action($redirect_query);
            $this->handle_group_message_action($redirect_query);
            $this->handle_priority_collaborator_message_action($redirect_query);
            $this->handle_ai_access_action($redirect_query);
        }

        Title::set(l('admin_leader_operating_system.title'));

        $data = [
            'selected_period' => $selected_period,
            'period_options' => $allowed_periods,
            'selected_status' => $selected_status,
            'status_options' => $allowed_statuses,
            'selected_ai_status' => $selected_ai_status,
            'ai_status_options' => $allowed_ai_statuses,
            'selected_anomaly_status' => $selected_anomaly_status,
            'anomaly_status_options' => $allowed_anomaly_statuses,
            'selected_fraud_status' => $selected_fraud_status,
            'fraud_status_options' => $allowed_fraud_statuses,
            'selected_sort' => $selected_sort,
            'sort_options' => $allowed_sorts,
            'selected_tab' => $selected_tab,
            'selected_support_ticket_id' => $selected_support_ticket_id,
            'tab_options' => $allowed_tabs,
            'search_query' => $search_query,
            'operations' => $this->get_operations_payload($search_query),
            'overview' => $this->get_overview_payload($selected_period, $search_query, $selected_status, $selected_ai_status, $selected_anomaly_status, $selected_fraud_status, $selected_sort, $page),
        ];

        $view = new \Altum\View('admin/leader-operating-system/index', (array) $this);
        $this->add_view_content('content', $view->run((object) $data));
    }
}

/* /Custom code: FC-2026-03-31 */
