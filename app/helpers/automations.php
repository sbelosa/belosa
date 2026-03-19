<?php
/* Custom code: FC-2026-03-18: live email automations helpers */
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-03-19: schema helpers for reusable mail analytics */
function fc_table_has_column(string $table, string $column): bool {
    static $cache = [];

    if(!isset($cache[$table])) {
        $columns = db()->rawQuery("SHOW COLUMNS FROM `{$table}`") ?? [];

        $cache[$table] = array_values(array_filter(array_map(static function($row) {
            if(is_array($row)) {
                return $row['Field'] ?? null;
            }

            if(is_object($row)) {
                return $row->Field ?? null;
            }

            return null;
        }, $columns)));
    }

    return in_array($column, $cache[$table], true);
}

function fc_add_table_column_if_missing(string $table, string $column, string $definition): void {
    if(fc_table_has_column($table, $column)) {
        return;
    }

    db()->rawQuery("ALTER TABLE `{$table}` ADD COLUMN {$definition}");
}
/* /Custom code: FC-2026-03-19 */

function fc_ensure_email_automation_tables() {
    static $is_ready = false;

    if($is_ready) {
        return;
    }

    db()->rawQuery("CREATE TABLE IF NOT EXISTS `email_automations` (
        `automation_id` int unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(128) NOT NULL,
        `segment` varchar(64) NOT NULL,
        `status` varchar(16) NOT NULL DEFAULT 'paused',
        `settings` longtext NULL,
        `total_sent_emails` int unsigned NOT NULL DEFAULT 0,
        `last_sent_email_datetime` datetime NULL,
        `datetime` datetime NOT NULL,
        `last_datetime` datetime NULL,
        PRIMARY KEY (`automation_id`),
        KEY `status` (`status`),
        KEY `segment` (`segment`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db()->rawQuery("CREATE TABLE IF NOT EXISTS `email_automation_steps` (
        `automation_step_id` int unsigned NOT NULL AUTO_INCREMENT,
        `automation_id` int unsigned NOT NULL,
        `step_order` tinyint unsigned NOT NULL,
        `subject` varchar(128) NOT NULL,
        `content` longtext NOT NULL,
        `delay_minutes` int unsigned NOT NULL DEFAULT 0,
        `settings` text NULL,
        `datetime` datetime NOT NULL,
        `last_datetime` datetime NULL,
        PRIMARY KEY (`automation_step_id`),
        UNIQUE KEY `automation_step_order` (`automation_id`, `step_order`),
        KEY `automation_id` (`automation_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db()->rawQuery("CREATE TABLE IF NOT EXISTS `email_automation_enrollments` (
        `automation_enrollment_id` int unsigned NOT NULL AUTO_INCREMENT,
        `automation_id` int unsigned NOT NULL,
        `user_id` int unsigned NOT NULL,
        `status` varchar(16) NOT NULL DEFAULT 'active',
        `current_step` tinyint unsigned NOT NULL DEFAULT 1,
        `entered_datetime` datetime NOT NULL,
        `next_action_datetime` datetime NULL,
        `last_sent_email_datetime` datetime NULL,
        `last_evaluated_datetime` datetime NULL,
        `completed_datetime` datetime NULL,
        `exit_datetime` datetime NULL,
        `exit_reason` varchar(64) NULL,
        `last_datetime` datetime NULL,
        PRIMARY KEY (`automation_enrollment_id`),
        UNIQUE KEY `automation_user` (`automation_id`, `user_id`),
        KEY `due_queue` (`automation_id`, `status`, `next_action_datetime`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db()->rawQuery("CREATE TABLE IF NOT EXISTS `email_automation_logs` (
        `automation_log_id` int unsigned NOT NULL AUTO_INCREMENT,
        `automation_id` int unsigned NOT NULL,
        `automation_enrollment_id` int unsigned NULL,
        `automation_step_id` int unsigned NULL,
        `user_id` int unsigned NULL,
        `action` varchar(32) NOT NULL,
        `details` longtext NULL,
        `datetime` datetime NOT NULL,
        PRIMARY KEY (`automation_log_id`),
        KEY `automation_id` (`automation_id`),
        KEY `automation_enrollment_id` (`automation_enrollment_id`),
        KEY `user_id` (`user_id`),
        KEY `action` (`action`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    /* Custom code: FC-2026-03-19: automation message and event analytics tables */
    db()->rawQuery("CREATE TABLE IF NOT EXISTS `email_automation_messages` (
        `automation_message_id` int unsigned NOT NULL AUTO_INCREMENT,
        `automation_id` int unsigned NOT NULL,
        `automation_enrollment_id` int unsigned NOT NULL,
        `automation_step_id` int unsigned NOT NULL,
        `user_id` int unsigned NOT NULL,
        `provider` varchar(32) NOT NULL DEFAULT 'brevo',
        `recipient_email` varchar(320) NOT NULL,
        `subject` varchar(256) NOT NULL,
        `brevo_message_id` varchar(191) NULL,
        `tags` text NULL,
        `status` varchar(32) NOT NULL DEFAULT 'sent',
        `sent_datetime` datetime NOT NULL,
        `delivered_datetime` datetime NULL,
        `first_open_datetime` datetime NULL,
        `first_click_datetime` datetime NULL,
        `soft_bounce_datetime` datetime NULL,
        `hard_bounce_datetime` datetime NULL,
        `blocked_datetime` datetime NULL,
        `invalid_datetime` datetime NULL,
        `spam_datetime` datetime NULL,
        `unsubscribe_datetime` datetime NULL,
        `goal_completed_datetime` datetime NULL,
        `last_event_datetime` datetime NULL,
        `last_event_type` varchar(32) NULL,
        `total_opens` int unsigned NOT NULL DEFAULT 0,
        `total_clicks` int unsigned NOT NULL DEFAULT 0,
        `raw_send_response` longtext NULL,
        `datetime` datetime NOT NULL,
        `last_datetime` datetime NULL,
        PRIMARY KEY (`automation_message_id`),
        UNIQUE KEY `brevo_message_id` (`brevo_message_id`),
        KEY `automation_id` (`automation_id`),
        KEY `automation_enrollment_id` (`automation_enrollment_id`),
        KEY `automation_step_id` (`automation_step_id`),
        KEY `user_id` (`user_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db()->rawQuery("CREATE TABLE IF NOT EXISTS `email_automation_message_events` (
        `automation_message_event_id` int unsigned NOT NULL AUTO_INCREMENT,
        `automation_message_id` int unsigned NULL,
        `automation_id` int unsigned NULL,
        `automation_step_id` int unsigned NULL,
        `user_id` int unsigned NULL,
        `provider` varchar(32) NOT NULL DEFAULT 'brevo',
        `event_type` varchar(32) NOT NULL,
        `event_hash` char(40) NOT NULL,
        `event_datetime` datetime NOT NULL,
        `url` text NULL,
        `reason` varchar(255) NULL,
        `raw_payload` longtext NULL,
        `datetime` datetime NOT NULL,
        PRIMARY KEY (`automation_message_event_id`),
        UNIQUE KEY `event_hash` (`event_hash`),
        KEY `automation_message_id` (`automation_message_id`),
        KEY `automation_id` (`automation_id`),
        KEY `automation_step_id` (`automation_step_id`),
        KEY `user_id` (`user_id`),
        KEY `event_type` (`event_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    fc_add_table_column_if_missing('email_automation_messages', 'message_type', "`message_type` varchar(32) NOT NULL DEFAULT 'automation' AFTER `user_id`");
    fc_add_table_column_if_missing('email_automation_messages', 'broadcast_id', "`broadcast_id` int unsigned NULL AFTER `automation_id`");
    fc_add_table_column_if_missing('email_automation_message_events', 'message_type', "`message_type` varchar(32) NOT NULL DEFAULT 'automation' AFTER `automation_message_id`");
    fc_add_table_column_if_missing('email_automation_message_events', 'broadcast_id', "`broadcast_id` int unsigned NULL AFTER `automation_id`");
    /* /Custom code: FC-2026-03-19 */

    $is_ready = true;
}

function fc_get_email_automation_settings($settings): object {
    if(is_string($settings)) {
        $settings = json_decode($settings ?? '');
    }

    if(is_array($settings)) {
        $settings = (object) $settings;
    }

    if(!is_object($settings)) {
        $settings = (object) [];
    }

    $default_batch_size = (int) (settings()->content->broadcasts_emails_per_cron ?? 20);

    $settings->batch_size = max(1, min(200, (int) ($settings->batch_size ?? ($default_batch_size ?: 20))));
    $settings->segment_label = trim((string) ($settings->segment_label ?? ''));
    $settings->exit_when_condition_met = (int) ($settings->exit_when_condition_met ?? 1);
    $settings->reentry_is_enabled = (int) ($settings->reentry_is_enabled ?? 1);
    $settings->video_url = !empty($settings->video_url) ? input_clean($settings->video_url, 2048) : url('fcc-education');
    $settings->template_version = (int) ($settings->template_version ?? 0);
    $settings->filters_plans = array_values(array_unique(array_filter(array_map('strval', (array) ($settings->filters_plans ?? [])))));

    return $settings;
}

/* Custom code: FC-2026-03-19: reusable automation segment definitions */
function fc_get_email_automation_segment_options(): array {
    return [
        'missing_forever_sales_link' => 'Suradnici bez Forever prodajnog linka',
        'all_active_users' => 'Svi aktivni korisnici',
        'newsletter_subscribers' => 'Pretplatnici na newsletter',
        'plan_users' => 'Korisnici po planovima',
    ];
}

function fc_get_email_automation_segment_label(string $segment, $settings = null): string {
    $settings = fc_get_email_automation_settings($settings);

    if($settings->segment_label !== '') {
        return $settings->segment_label;
    }

    $options = fc_get_email_automation_segment_options();

    if($segment === 'plan_users' && !empty($settings->filters_plans)) {
        return 'Korisnici planova: ' . implode(', ', $settings->filters_plans);
    }

    return $options[$segment] ?? $segment;
}

function fc_get_base_automation_segment_users_query() {
    return db()
        ->where('type', 0)
        ->where('status', 1)
        ->orderBy('user_id', 'DESC');
}

function fc_get_base_automation_segment_fields(): array {
    return ['user_id', 'name', 'email', 'language', 'anti_phishing_code', 'plan_id', 'status', 'type'];
}

function fc_get_all_active_segment_users(): array {
    $users = fc_get_base_automation_segment_users_query()->get('users', null, fc_get_base_automation_segment_fields()) ?? [];
    $eligible_users = [];

    foreach($users as $user) {
        $eligible_users[(int) $user->user_id] = $user;
    }

    return $eligible_users;
}

function fc_get_newsletter_subscribers_segment_users(): array {
    $users = fc_get_base_automation_segment_users_query()
        ->where('is_newsletter_subscribed', 1)
        ->get('users', null, fc_get_base_automation_segment_fields()) ?? [];

    $eligible_users = [];

    foreach($users as $user) {
        $eligible_users[(int) $user->user_id] = $user;
    }

    return $eligible_users;
}

function fc_get_plan_segment_users(array $plan_ids): array {
    $plan_ids = array_values(array_unique(array_filter(array_map('strval', $plan_ids))));

    if(empty($plan_ids)) {
        return [];
    }

    $users = fc_get_base_automation_segment_users_query()
        ->where('plan_id', $plan_ids, 'IN')
        ->get('users', null, fc_get_base_automation_segment_fields()) ?? [];

    $eligible_users = [];

    foreach($users as $user) {
        $eligible_users[(int) $user->user_id] = $user;
    }

    return $eligible_users;
}
/* /Custom code: FC-2026-03-19 */

function fc_get_default_email_automation_steps(): array {
    return [
        [
            'step_order' => 1,
            'delay_minutes' => 0,
            'subject' => 'FCC upute: aktivacija Forever prodajnog linka',
            'content' => '<p>Bok {{USER:NAME}},</p><p>saljemo ti ovu poruku jer na svom FCC racunu jos nemas aktiviran Forever prodajni link.</p><p>U FCC aplikaciji mozes bez dodatne naplate dodati link za popust i preporuku Forever proizvoda, a zatim link svoje aplikacije koristiti u biolinku ili opisu profila na drustvenim mrezama.</p><p>Za postavljanje obicno treba samo nekoliko minuta.</p><p>1. pogledaj kratke upute:<br /><a href="{{FCC_VIDEO_URL}}">{{FCC_VIDEO_URL}}</a></p><p>2. otvori stranicu za postavljanje:<br /><a href="{{SALES_LINKS_PAGE}}">{{SALES_LINKS_PAGE}}</a></p><p>Ako ti treba pomoc oko postavljanja, slobodno odgovori na ovaj email.</p><p>Ovu poruku si primio jer imas korisnicki racun na {{WEBSITE_TITLE}} i jos nemas aktiviran Forever prodajni link.</p><p>{{WEBSITE_TITLE}}</p>',
        ],
        [
            'step_order' => 2,
            'delay_minutes' => 2880,
            'subject' => 'FCC podsjetnik: Forever prodajni link jos nije postavljen',
            'content' => '<p>Bok {{USER:NAME}},</p><p>ovo je kratki podsjetnik da na tvom FCC racunu jos uvijek nije aktiviran Forever prodajni link.</p><p>Kada ga postavis, svoj FCC link mozes koristiti u biolinku ili opisu profila kako bi posjetitelji lakse otvorili tvoju aplikaciju i vidjeli preporucene proizvode.</p><p>Upute za postavljanje:<br /><a href="{{FCC_VIDEO_URL}}">{{FCC_VIDEO_URL}}</a></p><p>Stranica za aktivaciju:<br /><a href="{{SALES_LINKS_PAGE}}">{{SALES_LINKS_PAGE}}</a></p><p>Ako si link vec postavio u meduvremenu, ovu poruku mozes zanemariti.</p><p>Ovu poruku si primio jer imas korisnicki racun na {{WEBSITE_TITLE}} i jos nemas aktiviran Forever prodajni link.</p><p>{{WEBSITE_TITLE}}</p>',
        ],
        [
            'step_order' => 3,
            'delay_minutes' => 10080,
            'subject' => 'FCC podsjetnik: dovrsi postavljanje Forever prodajnog linka',
            'content' => '<p>Bok {{USER:NAME}},</p><p>jos jednom te podsjecamo da je na tvom FCC racunu i dalje dostupan korak za aktivaciju Forever prodajnog linka.</p><p>Ako to zelis postaviti sada, sve potrebno je vec spremno:</p><p>Video upute:<br /><a href="{{FCC_VIDEO_URL}}">{{FCC_VIDEO_URL}}</a></p><p>Stranica za postavljanje:<br /><a href="{{SALES_LINKS_PAGE}}">{{SALES_LINKS_PAGE}}</a></p><p>Ako ti treba pomoc ili zelis da provjerimo postavke, slobodno odgovori na ovaj email.</p><p>Ovu poruku si primio jer imas korisnicki racun na {{WEBSITE_TITLE}} i jos nemas aktiviran Forever prodajni link.</p><p>{{WEBSITE_TITLE}}</p>',
        ],
    ];
}

function fc_seed_default_email_automation() {
    fc_ensure_email_automation_tables();

    $automation = db()->where('segment', 'missing_forever_sales_link')->getOne('email_automations');

    $datetime = get_date();
    $settings = [
        'batch_size' => max(1, min(200, (int) (settings()->content->broadcasts_emails_per_cron ?? 20))),
        'segment_label' => 'Suradnici bez Forever prodajnog linka',
        'exit_when_condition_met' => 1,
        'reentry_is_enabled' => 1,
        'video_url' => url('fcc-education'),
        'template_version' => 4,
    ];

    $steps = fc_get_default_email_automation_steps();

    if($automation) {
        $automation_settings = fc_get_email_automation_settings($automation->settings ?? null);

        if($automation_settings->template_version < 4) {
            db()->where('automation_id', $automation->automation_id)->update('email_automations', [
                'settings' => json_encode(array_merge((array) $automation_settings, [
                    'video_url' => $automation_settings->video_url ?: url('fcc-education'),
                    'template_version' => 4,
                ])),
                'last_datetime' => get_date(),
            ]);

            foreach($steps as $step) {
                db()->where('automation_id', $automation->automation_id)->where('step_order', $step['step_order'])->update('email_automation_steps', [
                    'subject' => $step['subject'],
                    'content' => $step['content'],
                    'delay_minutes' => $step['delay_minutes'],
                    'last_datetime' => get_date(),
                ]);
            }
        }

        return db()->where('automation_id', $automation->automation_id)->getOne('email_automations');
    }

    $automation_id = db()->insert('email_automations', [
        'name' => 'Aktivacija bez Forever prodajnog linka',
        'segment' => 'missing_forever_sales_link',
        'status' => 'paused',
        'settings' => json_encode($settings),
        'datetime' => $datetime,
    ]);

    foreach($steps as $step) {
        db()->insert('email_automation_steps', [
            'automation_id' => $automation_id,
            'step_order' => $step['step_order'],
            'subject' => $step['subject'],
            'content' => $step['content'],
            'delay_minutes' => $step['delay_minutes'],
            'settings' => json_encode([]),
            'datetime' => $datetime,
        ]);
    }

    return db()->where('automation_id', $automation_id)->getOne('email_automations');
}

function fc_get_email_automation_steps(int $automation_id): array {
    return db()->where('automation_id', $automation_id)->orderBy('step_order', 'ASC')->get('email_automation_steps') ?? [];
}

function fc_get_forever_sales_link_block_types(): array {
    return ['link_discount'];
}

function fc_is_valid_forever_sales_link_url($url): bool {
    $url = mb_strtolower(trim((string) $url));

    return strpos($url, 'https://thealoeveraco.shop/') === 0;
}

function fc_get_missing_sales_link_segment_users(): array {
    $users = db()
        ->where('type', 0)
        ->where('status', 1)
        ->orderBy('user_id', 'DESC')
        ->get('users', null, ['user_id', 'name', 'email', 'language', 'anti_phishing_code']);

    if(empty($users)) {
        return [];
    }

    $user_ids = array_map(static function($user) {
        return (int) $user->user_id;
    }, $users);

    $valid_forever_sales_link_user_ids = [];

    if(!empty($user_ids)) {
        $discount_blocks = db()
            ->where('user_id', $user_ids, 'IN')
            ->where('type', fc_get_forever_sales_link_block_types(), 'IN')
            ->where('is_enabled', 1)
            ->get('biolinks_blocks', null, ['user_id', 'location_url']);

        foreach($discount_blocks as $discount_block) {
            if(fc_is_valid_forever_sales_link_url($discount_block->location_url ?? '')) {
                $valid_forever_sales_link_user_ids[(int) $discount_block->user_id] = true;
            }
        }
    }

    $eligible_users = [];

    foreach($users as $user) {
        $user_id = (int) $user->user_id;

        if(!isset($valid_forever_sales_link_user_ids[$user_id])) {
            $eligible_users[$user_id] = $user;
        }
    }

    return $eligible_users;
}

function fc_get_automation_segment_users(string $segment, $automation_settings = null): array {
    $automation_settings = fc_get_email_automation_settings($automation_settings);

    switch($segment) {
        case 'missing_forever_sales_link':
            return fc_get_missing_sales_link_segment_users();

        case 'all_active_users':
            return fc_get_all_active_segment_users();

        case 'newsletter_subscribers':
            return fc_get_newsletter_subscribers_segment_users();

        case 'plan_users':
            return fc_get_plan_segment_users($automation_settings->filters_plans ?? []);

        default:
            return [];
    }
}

function fc_get_automation_segment_count(string $segment, $automation_settings = null): int {
    return count(fc_get_automation_segment_users($segment, $automation_settings));
}

function fc_is_user_in_automation_segment(string $segment, int $user_id, $automation_settings = null): bool {
    $automation_settings = fc_get_email_automation_settings($automation_settings);

    if($user_id <= 0) {
        return false;
    }

    switch($segment) {
        case 'missing_forever_sales_link':
            $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'type', 'status']);

            if(!$user || (int) $user->type !== 0 || (int) $user->status !== 1) {
                return false;
            }

            $discount_blocks = db()
                ->where('user_id', $user_id)
                ->where('type', fc_get_forever_sales_link_block_types(), 'IN')
                ->where('is_enabled', 1)
                ->get('biolinks_blocks', null, ['location_url']);

            foreach($discount_blocks as $discount_block) {
                if(fc_is_valid_forever_sales_link_url($discount_block->location_url ?? '')) {
                    return false;
                }
            }

            return true;

        case 'all_active_users':
            return (bool) db()->where('user_id', $user_id)->where('type', 0)->where('status', 1)->getValue('users', 'COUNT(*)');

        case 'newsletter_subscribers':
            return (bool) db()->where('user_id', $user_id)->where('type', 0)->where('status', 1)->where('is_newsletter_subscribed', 1)->getValue('users', 'COUNT(*)');

        case 'plan_users':
            if(empty($automation_settings->filters_plans)) {
                return false;
            }

            return (bool) db()->where('user_id', $user_id)->where('type', 0)->where('status', 1)->where('plan_id', $automation_settings->filters_plans, 'IN')->getValue('users', 'COUNT(*)');

        default:
            return false;
    }
}

function fc_get_email_automation_user_variables($user, $automation_settings = null): array {
    $automation_settings = fc_get_email_automation_settings($automation_settings);

    /* Custom code: FC-2026-03-19: expose main Forever Card application URL in automation variables */
    return [
        '{{USER:NAME}}' => $user->name ?? '',
        '{{USER:EMAIL}}' => $user->email ?? '',
        '{{USER:LOGIN_LINK}}' => url('login'),
        '{{SALES_LINKS_PAGE}}' => url('links'),
        '{{FOREVER_CARD_APPLICATION_URL}}' => fc_get_user_main_biolink_url((int) ($user->user_id ?? 0)),
        '{{FCC_RESULTS_PAGE}}' => url('fcc-results'),
        '{{FEATURED_APPS_PAGE}}' => url('featured-apps'),
        '{{FCC_VIDEO_URL}}' => $automation_settings->video_url,
        '{{WEBSITE_TITLE}}' => settings()->main->title,
    ];
    /* /Custom code: FC-2026-03-19 */
}

/* Custom code: FC-2026-03-19: shared mail variables, unsubscribe signatures and footer copy */
function fc_get_user_main_biolink_url(int $user_id): string {
    if($user_id <= 0) {
        return '';
    }

    $main_biolink_result = database()->query("SELECT `links`.`link_id`, `links`.`url`, `links`.`domain_id`, `domains`.`scheme`, `domains`.`host`, `domains`.`link_id` AS `domain_link_id` FROM `links` LEFT JOIN `users_biolinks` ON `links`.`link_id` = `users_biolinks`.`biolink_id` LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id` WHERE `links`.`user_id` = {$user_id} AND `links`.`type` = 'biolink' AND `links`.`is_enabled` = 1 AND `users_biolinks`.`biolink_id` IS NOT NULL ORDER BY `links`.`datetime` ASC, `links`.`link_id` ASC LIMIT 1");
    $main_biolink = $main_biolink_result ? $main_biolink_result->fetch_object() : null;

    if(!$main_biolink) {
        $fallback_biolink_result = database()->query("SELECT `links`.`link_id`, `links`.`url`, `links`.`domain_id`, `domains`.`scheme`, `domains`.`host`, `domains`.`link_id` AS `domain_link_id` FROM `links` LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id` WHERE `links`.`user_id` = {$user_id} AND `links`.`type` = 'biolink' AND `links`.`is_enabled` = 1 ORDER BY `links`.`datetime` ASC, `links`.`link_id` ASC LIMIT 1");
        $main_biolink = $fallback_biolink_result ? $fallback_biolink_result->fetch_object() : null;
    }

    if(!$main_biolink || empty($main_biolink->url)) {
        return '';
    }

    return $main_biolink->domain_id ? $main_biolink->scheme . $main_biolink->host . '/' . ($main_biolink->domain_link_id == $main_biolink->link_id ? null : $main_biolink->url) : SITE_URL . $main_biolink->url;
}

function fc_get_broadcast_user_variables($user): array {
    return [
        '{{USER:NAME}}' => $user->name ?? '',
        '{{USER:EMAIL}}' => $user->email ?? '',
        '{{USER:CONTINENT_NAME}}' => get_continent_from_continent_code($user->continent_code ?? ''),
        '{{USER:COUNTRY_NAME}}' => get_country_from_country_code($user->country ?? ''),
        '{{USER:CITY_NAME}}' => $user->city_name ?? '',
        '{{USER:DEVICE_TYPE}}' => l('global.device.' . ($user->device_type ?? 'unknown')),
        '{{USER:OS_NAME}}' => $user->os_name ?? '',
        '{{USER:BROWSER_NAME}}' => $user->browser_name ?? '',
        '{{USER:BROWSER_LANGUAGE}}' => get_language_from_locale($user->browser_language ?? ''),
        '{{FOREVER_CARD_APPLICATION_URL}}' => fc_get_user_main_biolink_url((int) ($user->user_id ?? 0)),
        '{{WEBSITE_TITLE}}' => settings()->main->title,
    ];
}

function fc_get_email_unsubscribe_secret(): string {
    return fc_get_brevo_webhook_secret() ?: (settings()->smtp->password ?? md5(SITE_URL . '|' . (settings()->smtp->from ?? 'mail')));
}

function fc_generate_email_unsubscribe_signature(array $context): string {
    $payload = implode('|', [
        $context['message_type'] ?? '',
        (int) ($context['broadcast_id'] ?? 0),
        (int) ($context['automation_id'] ?? 0),
        (int) ($context['automation_enrollment_id'] ?? 0),
        (int) ($context['automation_step_id'] ?? 0),
        (int) ($context['user_id'] ?? 0),
        mb_strtolower(trim((string) ($context['recipient_email'] ?? ''))),
    ]);

    return hash_hmac('sha256', $payload, fc_get_email_unsubscribe_secret());
}

function fc_get_email_unsubscribe_url(array $context): string {
    $query = [
        'type' => $context['message_type'] ?? 'broadcast',
        'broadcast_id' => (int) ($context['broadcast_id'] ?? 0),
        'automation_id' => (int) ($context['automation_id'] ?? 0),
        'automation_enrollment_id' => (int) ($context['automation_enrollment_id'] ?? 0),
        'automation_step_id' => (int) ($context['automation_step_id'] ?? 0),
        'user_id' => (int) ($context['user_id'] ?? 0),
        'email' => trim((string) ($context['recipient_email'] ?? '')),
    ];

    $query['signature'] = fc_generate_email_unsubscribe_signature(array_merge($context, ['recipient_email' => $query['email']]));

    return url('email-unsubscribe') . '?' . http_build_query($query);
}

function fc_get_email_unsubscribe_footer_copy(?string $language = null): array {
    $language = mb_strtolower((string) $language);

    if(strpos($language, 'hr') === 0) {
        return [
            'text' => 'Ako više ne želiš primati ovakve email obavijesti, možeš se ručno odjaviti ovdje.',
            'link' => 'Odjavi me',
        ];
    }

    return [
        'text' => 'If you no longer want to receive emails like this, you can unsubscribe here.',
        'link' => 'Unsubscribe',
    ];
}

function fc_find_email_message_by_unsubscribe_context(array $context) {
    $query = db()->where('message_type', $context['message_type'] ?? 'broadcast');

    if(($context['message_type'] ?? 'broadcast') === 'broadcast') {
        $query->where('broadcast_id', (int) ($context['broadcast_id'] ?? 0));
    } else {
        $query->where('automation_id', (int) ($context['automation_id'] ?? 0));

        if(!empty($context['automation_enrollment_id'])) {
            $query->where('automation_enrollment_id', (int) $context['automation_enrollment_id']);
        }

        if(!empty($context['automation_step_id'])) {
            $query->where('automation_step_id', (int) $context['automation_step_id']);
        }
    }

    if(!empty($context['user_id'])) {
        $query->where('user_id', (int) $context['user_id']);
    }

    if(!empty($context['recipient_email'])) {
        $query->where('recipient_email', trim((string) $context['recipient_email']));
    }

    return $query->orderBy('automation_message_id', 'DESC')->getOne('email_automation_messages');
}

function fc_process_email_unsubscribe(array $context): array {
    /* Custom code: FC-2026-03-19: ensure shared mail analytics schema exists before unsubscribe lookups */
    fc_ensure_email_automation_tables();
    /* /Custom code: FC-2026-03-19 */

    $context['recipient_email'] = trim((string) ($context['recipient_email'] ?? ''));
    $context['user_id'] = (int) ($context['user_id'] ?? 0);
    $message = fc_find_email_message_by_unsubscribe_context($context);
    $user = $context['user_id'] ? db()->where('user_id', $context['user_id'])->getOne('users', ['user_id', 'name', 'email', 'is_newsletter_subscribed', 'preferences']) : null;
    $already_unsubscribed = $user ? !(bool) ($user->is_newsletter_subscribed ?? 0) : false;

    if($user) {
        $preferences = json_decode($user->preferences ?? '') ?: (object) [];
        $preferences->email_unsubscribe = (object) [
            'source' => 'one_click_link',
            'message_type' => $context['message_type'] ?? 'broadcast',
            'broadcast_id' => (int) ($context['broadcast_id'] ?? 0),
            'automation_id' => (int) ($context['automation_id'] ?? 0),
            'automation_enrollment_id' => (int) ($context['automation_enrollment_id'] ?? 0),
            'automation_step_id' => (int) ($context['automation_step_id'] ?? 0),
            'recipient_email' => $context['recipient_email'] ?: ($user->email ?? ''),
            'ip' => get_ip(),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'datetime' => get_date(),
        ];

        db()->where('user_id', $user->user_id)->update('users', [
            'is_newsletter_subscribed' => 0,
            'preferences' => json_encode($preferences, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        $user->preferences = json_encode($preferences, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    if($message) {
        $event_datetime = get_date();

        if(empty($message->unsubscribe_datetime) && ($message->status ?? '') !== 'unsubscribed') {
            db()->where('automation_message_id', $message->automation_message_id)->update('email_automation_messages', [
                'status' => 'unsubscribed',
                'unsubscribe_datetime' => $event_datetime,
                'last_event_type' => 'unsubscribed',
                'last_event_datetime' => $event_datetime,
                'last_datetime' => $event_datetime,
            ]);
        }

        $payload = (object) [
            'event' => 'unsubscribed',
            'email' => $context['recipient_email'] ?: ($message->recipient_email ?? ''),
            'reason' => 'One-click unsubscribe link',
            'ts_event' => $event_datetime,
            'tag' => json_decode($message->tags ?? '[]'),
        ];

        fc_store_email_automation_message_event($message, 'unsubscribed', $payload);

        if(($message->message_type ?? 'automation') === 'automation') {
            fc_log_email_automation_provider_event($message, 'unsubscribed', $payload);
        }
    }

    return [
        'user' => $user,
        'message' => $message,
        'already_unsubscribed' => $already_unsubscribed,
    ];
}

function fc_append_email_automation_footer(string $content): string {
    return $content;
}
/* /Custom code: FC-2026-03-19 */

function fc_insert_email_automation_log(int $automation_id, ?int $enrollment_id, ?int $step_id, ?int $user_id, string $action, array $details = []) {
    db()->insert('email_automation_logs', [
        'automation_id' => $automation_id,
        'automation_enrollment_id' => $enrollment_id,
        'automation_step_id' => $step_id,
        'user_id' => $user_id,
        'action' => $action,
        'details' => json_encode($details),
        'datetime' => get_date(),
    ]);
}

/* Custom code: FC-2026-03-19: Brevo automation analytics helpers */
function fc_get_brevo_webhook_secret(): string {
    /* Custom code: FC-2026-03-19: prefer admin-configured Brevo webhook secret */
    return settings()->smtp->brevo_webhook_secret ?? (defined('BREVO_WEBHOOK_SECRET') ? BREVO_WEBHOOK_SECRET : '');
    /* /Custom code: FC-2026-03-19 */
}

function fc_get_brevo_webhook_url(): string {
    $url = url('webhook-brevo-email');
    $secret = fc_get_brevo_webhook_secret();

    if($secret === '') {
        return $url;
    }

    return $url . '?' . http_build_query(['secret' => $secret]);
}

function fc_normalize_brevo_message_id($message_id): ?string {
    $message_id = trim((string) $message_id);

    if($message_id === '') {
        return null;
    }

    return trim($message_id, '<>');
}

/* Custom code: FC-2026-03-19: normalize Brevo webhook payload identifiers across variants */
function fc_get_brevo_event_message_id($payload): ?string {
    foreach(['message-id', 'messageId', 'message_id', 'Message-Id', 'Message-ID'] as $property) {
        if(isset($payload->{$property})) {
            return fc_normalize_brevo_message_id($payload->{$property});
        }
    }

    return null;
}

function fc_get_brevo_event_recipient_email($payload): string {
    foreach(['email', 'recipient', 'recipient_email'] as $property) {
        $value = trim((string) ($payload->{$property} ?? ''));

        if($value !== '') {
            return $value;
        }
    }

    if(!empty($payload->to) && is_array($payload->to)) {
        foreach($payload->to as $entry) {
            if(is_object($entry) && !empty($entry->email)) {
                return trim((string) $entry->email);
            }

            if(is_string($entry) && trim($entry) !== '') {
                return trim($entry);
            }
        }
    }

    return '';
}

function fc_find_recent_email_message_for_recipient(string $email, array $context, string $event_datetime) {
    $candidates = db()
        ->where('provider', 'brevo')
        ->where('recipient_email', $email)
        ->where('status', 'send_failed', '!=')
        ->orderBy('automation_message_id', 'DESC')
        ->get('email_automation_messages', 25) ?? [];

    if(empty($candidates)) {
        return null;
    }

    $event_timestamp = strtotime($event_datetime) ?: time();
    $best_candidate = null;
    $best_score = PHP_INT_MIN;

    foreach($candidates as $candidate) {
        $score = 0;

        if(!empty($context['broadcast_id'])) {
            $score += ($candidate->message_type ?? null) === 'broadcast' ? 40 : -80;
            $score += (int) ($candidate->broadcast_id ?? 0) === (int) $context['broadcast_id'] ? 120 : -30;
        }

        if(!empty($context['automation_id'])) {
            $score += ($candidate->message_type ?? null) === 'automation' ? 40 : -80;
            $score += (int) ($candidate->automation_id ?? 0) === (int) $context['automation_id'] ? 90 : -25;
        }

        if(!empty($context['automation_enrollment_id'])) {
            $score += (int) ($candidate->automation_enrollment_id ?? 0) === (int) $context['automation_enrollment_id'] ? 70 : -20;
        }

        if(!empty($context['automation_step_id'])) {
            $score += (int) ($candidate->automation_step_id ?? 0) === (int) $context['automation_step_id'] ? 60 : -15;
        }

        if(!empty($context['user_id'])) {
            $score += (int) ($candidate->user_id ?? 0) === (int) $context['user_id'] ? 50 : -10;
        }

        $sent_timestamp = strtotime((string) ($candidate->sent_datetime ?? '')) ?: 0;

        if($sent_timestamp > 0) {
            $time_diff = abs($event_timestamp - $sent_timestamp);

            if($sent_timestamp <= $event_timestamp) {
                $score += 35;
            }

            if($time_diff <= 3600) {
                $score += 35;
            } elseif($time_diff <= 86400) {
                $score += 20;
            } elseif($time_diff <= 604800) {
                $score += 5;
            } else {
                $score -= 25;
            }
        }

        if($score > $best_score) {
            $best_score = $score;
            $best_candidate = $candidate;
        }
    }

    return $best_score >= 0 ? $best_candidate : null;
}
/* /Custom code: FC-2026-03-19 */

function fc_get_email_automation_message_tags(int $automation_id, int $enrollment_id, int $step_id, int $user_id): array {
    return [
        'fc_automation',
        'automation_' . $automation_id,
        'enrollment_' . $enrollment_id,
        'step_' . $step_id,
        'user_' . $user_id,
    ];
}

function fc_get_email_broadcast_message_tags(int $broadcast_id, int $user_id): array {
    return [
        'fc_broadcast',
        'broadcast_' . $broadcast_id,
        'user_' . $user_id,
    ];
}

function fc_store_email_automation_message(int $automation_id, int $enrollment_id, int $step_id, int $user_id, string $recipient_email, string $subject, array $tags, $transport_result = null): ?int {
    $raw_send_response = null;
    $brevo_message_id = null;

    if(is_object($transport_result)) {
        $brevo_message_id = fc_normalize_brevo_message_id($transport_result->message_id ?? null);
        $raw_send_response = json_encode([
            'status_code' => $transport_result->status_code ?? null,
            'response_body' => $transport_result->response_body ?? null,
            'response_json' => $transport_result->response_json ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return db()->insert('email_automation_messages', [
        'automation_id' => $automation_id,
        'broadcast_id' => null,
        'automation_enrollment_id' => $enrollment_id,
        'automation_step_id' => $step_id,
        'user_id' => $user_id,
        'message_type' => 'automation',
        'provider' => 'brevo',
        'recipient_email' => $recipient_email,
        'subject' => mb_substr($subject, 0, 256),
        'brevo_message_id' => $brevo_message_id,
        'tags' => json_encode(array_values($tags)),
        'status' => 'sent',
        'sent_datetime' => get_date(),
        'raw_send_response' => $raw_send_response,
        'datetime' => get_date(),
        'last_datetime' => get_date(),
    ]);
}

function fc_store_broadcast_message(int $broadcast_id, int $user_id, string $recipient_email, string $subject, array $tags, $transport_result = null): ?int {
    $raw_send_response = null;
    $brevo_message_id = null;
    $status = 'sent';

    if(is_object($transport_result)) {
        $brevo_message_id = fc_normalize_brevo_message_id($transport_result->message_id ?? null);
        $status = !empty($transport_result->success) ? 'sent' : 'send_failed';
        $raw_send_response = json_encode([
            'status_code' => $transport_result->status_code ?? null,
            'response_body' => $transport_result->response_body ?? null,
            'response_json' => $transport_result->response_json ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return db()->insert('email_automation_messages', [
        'automation_id' => 0,
        'broadcast_id' => $broadcast_id,
        'automation_enrollment_id' => 0,
        'automation_step_id' => 0,
        'user_id' => $user_id,
        'message_type' => 'broadcast',
        'provider' => 'brevo',
        'recipient_email' => $recipient_email,
        'subject' => mb_substr($subject, 0, 256),
        'brevo_message_id' => $brevo_message_id,
        'tags' => json_encode(array_values($tags)),
        'status' => $status,
        'sent_datetime' => get_date(),
        'raw_send_response' => $raw_send_response,
        'datetime' => get_date(),
        'last_datetime' => get_date(),
    ]);
}

function fc_extract_automation_context_from_tags($tags): array {
    if(is_string($tags)) {
        $tags = [$tags];
    }

    if(!is_array($tags)) {
        return [
            'automation_id' => null,
            'automation_enrollment_id' => null,
            'automation_step_id' => null,
            'broadcast_id' => null,
            'user_id' => null,
        ];
    }

    $context = [
        'automation_id' => null,
        'automation_enrollment_id' => null,
        'automation_step_id' => null,
        'broadcast_id' => null,
        'user_id' => null,
    ];

    foreach($tags as $tag) {
        $tag = trim((string) $tag);

        if(preg_match('/^automation_(\d+)$/', $tag, $matches)) {
            $context['automation_id'] = (int) $matches[1];
        }

        if(preg_match('/^enrollment_(\d+)$/', $tag, $matches)) {
            $context['automation_enrollment_id'] = (int) $matches[1];
        }

        if(preg_match('/^step_(\d+)$/', $tag, $matches)) {
            $context['automation_step_id'] = (int) $matches[1];
        }

        if(preg_match('/^broadcast_(\d+)$/', $tag, $matches)) {
            $context['broadcast_id'] = (int) $matches[1];
        }

        if(preg_match('/^user_(\d+)$/', $tag, $matches)) {
            $context['user_id'] = (int) $matches[1];
        }
    }

    return $context;
}

function fc_get_brevo_event_datetime($payload): string {
    if(isset($payload->ts_epoch) && is_numeric($payload->ts_epoch)) {
        return date('Y-m-d H:i:s', (int) $payload->ts_epoch);
    }

    foreach(['ts_event', 'date'] as $property) {
        if(empty($payload->{$property})) {
            continue;
        }

        try {
            return (new \DateTime((string) $payload->{$property}))->format('Y-m-d H:i:s');
        } catch(\Exception $exception) {
        }
    }

    return get_date();
}

function fc_get_brevo_event_tags($payload): array {
    $candidates = [];

    foreach(['tags', 'tag'] as $property) {
        if(!isset($payload->{$property})) {
            continue;
        }

        if(is_array($payload->{$property})) {
            $candidates = array_merge($candidates, $payload->{$property});
        } else {
            $candidates[] = $payload->{$property};
        }
    }

    return array_values(array_filter(array_map(static function($tag) {
        $tag = trim((string) $tag);
        return $tag !== '' ? $tag : null;
    }, $candidates)));
}

function fc_find_email_automation_message_for_brevo_event($payload) {
    $brevo_message_id = fc_get_brevo_event_message_id($payload);

    if($brevo_message_id) {
        $message = db()->where('brevo_message_id', $brevo_message_id)->getOne('email_automation_messages');

        if($message) {
            return $message;
        }
    }

    $tags = fc_get_brevo_event_tags($payload);
    $context = fc_extract_automation_context_from_tags($tags);
    $email = fc_get_brevo_event_recipient_email($payload);
    $event_datetime = fc_get_brevo_event_datetime($payload);

    $query = db()->where('provider', 'brevo');

    if($context['broadcast_id']) {
        $query->where('message_type', 'broadcast');
        $query->where('broadcast_id', $context['broadcast_id']);
    } else {
        $query->where('message_type', 'automation');
    }

    if($context['automation_enrollment_id']) {
        $query->where('automation_enrollment_id', $context['automation_enrollment_id']);
    }

    if($context['automation_step_id']) {
        $query->where('automation_step_id', $context['automation_step_id']);
    }

    if($context['automation_id']) {
        $query->where('automation_id', $context['automation_id']);
    }

    if($context['user_id']) {
        $query->where('user_id', $context['user_id']);
    }

    if($email !== '') {
        $query->where('recipient_email', $email);
    }

    $message = $query->orderBy('automation_message_id', 'DESC')->getOne('email_automation_messages');

    if($message) {
        return $message;
    }

    if($email === '') {
        return null;
    }

    return fc_find_recent_email_message_for_recipient($email, $context, $event_datetime);
}

function fc_get_brevo_event_hash($payload, string $event_type): string {
    return sha1(json_encode([
        'event_type' => $event_type,
        'message_id' => fc_get_brevo_event_message_id($payload),
        'email' => fc_get_brevo_event_recipient_email($payload),
        'ts_epoch' => $payload->ts_epoch ?? null,
        'ts_event' => $payload->ts_event ?? null,
        'url' => $payload->url ?? ($payload->link ?? null),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function fc_store_email_automation_message_event($message, string $event_type, $payload): bool {
    $event_hash = fc_get_brevo_event_hash($payload, $event_type);

    if(db()->where('event_hash', $event_hash)->has('email_automation_message_events')) {
        return false;
    }

    $tags = fc_get_brevo_event_tags($payload);
    $context = fc_extract_automation_context_from_tags($tags);

    db()->insert('email_automation_message_events', [
        'automation_message_id' => $message->automation_message_id ?? null,
        'message_type' => $message->message_type ?? ($context['broadcast_id'] ? 'broadcast' : 'automation'),
        'automation_id' => $message->automation_id ?? ($context['automation_id'] ?: null),
        'broadcast_id' => $message->broadcast_id ?? ($context['broadcast_id'] ?: null),
        'automation_step_id' => $message->automation_step_id ?? ($context['automation_step_id'] ?: null),
        'user_id' => $message->user_id ?? ($context['user_id'] ?: null),
        'provider' => 'brevo',
        'event_type' => $event_type,
        'event_hash' => $event_hash,
        'event_datetime' => fc_get_brevo_event_datetime($payload),
        'url' => $payload->url ?? ($payload->link ?? null),
        'reason' => mb_substr((string) ($payload->reason ?? ($payload->description ?? '')), 0, 255),
        'raw_payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'datetime' => get_date(),
    ]);

    return true;
}

function fc_apply_brevo_event_to_email_automation_message($message, string $event_type, $payload): void {
    if(!$message) {
        return;
    }

    $event_datetime = fc_get_brevo_event_datetime($payload);
    $update = [
        'last_event_datetime' => $event_datetime,
        'last_event_type' => $event_type,
        'last_datetime' => get_date(),
    ];

    switch($event_type) {
        case 'sent':
            $update['status'] = 'sent';
            break;

        case 'delivered':
            $update['status'] = 'delivered';
            if(empty($message->delivered_datetime)) {
                $update['delivered_datetime'] = $event_datetime;
            }
            break;

        case 'opened':
            $update['status'] = in_array($message->status ?? '', ['clicked', 'goal_completed'], true) ? $message->status : 'opened';
            if(empty($message->first_open_datetime)) {
                $update['first_open_datetime'] = $event_datetime;
            }

            if(($payload->event ?? null) !== 'uniqueOpened') {
                $update['total_opens'] = (int) ($message->total_opens ?? 0) + 1;
            }
            break;

        case 'clicked':
            $update['status'] = 'clicked';
            if(empty($message->first_click_datetime)) {
                $update['first_click_datetime'] = $event_datetime;
            }
            $update['total_clicks'] = (int) ($message->total_clicks ?? 0) + 1;
            break;

        case 'deferred':
            $update['status'] = 'deferred';
            break;

        case 'soft_bounce':
            $update['status'] = 'soft_bounce';
            if(empty($message->soft_bounce_datetime)) {
                $update['soft_bounce_datetime'] = $event_datetime;
            }
            break;

        case 'hard_bounce':
            $update['status'] = 'hard_bounce';
            if(empty($message->hard_bounce_datetime)) {
                $update['hard_bounce_datetime'] = $event_datetime;
            }
            break;

        case 'blocked':
            $update['status'] = 'blocked';
            if(empty($message->blocked_datetime)) {
                $update['blocked_datetime'] = $event_datetime;
            }
            break;

        case 'invalid':
            $update['status'] = 'invalid';
            if(empty($message->invalid_datetime)) {
                $update['invalid_datetime'] = $event_datetime;
            }
            break;

        case 'spam':
            $update['status'] = 'spam';
            if(empty($message->spam_datetime)) {
                $update['spam_datetime'] = $event_datetime;
            }
            break;

        case 'unsubscribed':
            $update['status'] = 'unsubscribed';
            if(empty($message->unsubscribe_datetime)) {
                $update['unsubscribe_datetime'] = $event_datetime;
            }
            break;
    }

    db()->where('automation_message_id', $message->automation_message_id)->update('email_automation_messages', $update);
}

function fc_log_email_automation_provider_event($message, string $event_type, $payload): void {
    if(!$message || (($message->message_type ?? 'automation') !== 'automation')) {
        return;
    }

    $message_text = [
        'sent' => 'Brevo confirmed the message as sent.',
        'delivered' => 'Brevo confirmed the message as delivered.',
        'opened' => 'Recipient opened the automation email.',
        'clicked' => 'Recipient clicked a link in the automation email.',
        'deferred' => 'Brevo deferred the automation email.',
        'soft_bounce' => 'Automation email soft bounced.',
        'hard_bounce' => 'Automation email hard bounced.',
        'blocked' => 'Automation email was blocked.',
        'invalid' => 'Automation email was rejected as invalid.',
        'spam' => 'Automation email was marked as spam.',
        'unsubscribed' => 'Recipient unsubscribed after the automation email.',
    ][$event_type] ?? 'Brevo event received for the automation email.';

    $details = ['message' => $message_text];

    if(!empty($payload->url) || !empty($payload->link)) {
        $details['url'] = $payload->url ?? $payload->link;
    }

    if(!empty($payload->reason) || !empty($payload->description)) {
        $details['reason'] = $payload->reason ?? $payload->description;
    }

    fc_insert_email_automation_log((int) $message->automation_id, (int) $message->automation_enrollment_id, (int) $message->automation_step_id, (int) $message->user_id, 'email_' . $event_type, $details);
}

function fc_mark_email_automation_goal_completed(int $automation_id, int $enrollment_id, int $user_id): void {
    $latest_message = db()->where('message_type', 'automation')->where('automation_enrollment_id', $enrollment_id)->orderBy('automation_message_id', 'DESC')->getOne('email_automation_messages');

    if($latest_message && empty($latest_message->goal_completed_datetime)) {
        db()->where('automation_message_id', $latest_message->automation_message_id)->update('email_automation_messages', [
            'goal_completed_datetime' => get_date(),
            'status' => 'goal_completed',
            'last_event_type' => 'goal_completed',
            'last_event_datetime' => get_date(),
            'last_datetime' => get_date(),
        ]);
    }

    $already_logged = db()
        ->where('automation_enrollment_id', $enrollment_id)
        ->where('action', 'goal_completed')
        ->getValue('email_automation_logs', 'COUNT(*)');

    if(!$already_logged) {
        fc_insert_email_automation_log($automation_id, $enrollment_id, $latest_message->automation_step_id ?? null, $user_id, 'goal_completed', ['message' => 'User completed the automation goal by activating the required sales link.']);
    }
}

function fc_normalize_brevo_event_type(string $event_type): string {
    $normalized = trim($event_type);

    $map = [
        'request' => 'sent',
        'sent' => 'sent',
        'delivered' => 'delivered',
        'deferred' => 'deferred',
        'opened' => 'opened',
        'firstOpening' => 'opened',
        'first_opening' => 'opened',
        'uniqueOpened' => 'opened',
        'unique_opened' => 'opened',
        'proxyOpen' => 'opened',
        'proxy_open' => 'opened',
        'uniqueProxyOpen' => 'opened',
        'unique_proxy_open' => 'opened',
        'click' => 'clicked',
        'clicked' => 'clicked',
        'Click' => 'clicked',
        'softBounce' => 'soft_bounce',
        'soft_bounced' => 'soft_bounce',
        'soft bounced' => 'soft_bounce',
        'hardBounce' => 'hard_bounce',
        'hard_bounced' => 'hard_bounce',
        'hard bounced' => 'hard_bounce',
        'blocked' => 'blocked',
        'invalid' => 'invalid',
        'invalid_email' => 'invalid',
        'error' => 'blocked',
        'complaint' => 'spam',
        'spam' => 'spam',
        'unsubscribed' => 'unsubscribed',
    ];

    return $map[$normalized] ?? mb_strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $normalized));
}

function fc_calculate_email_automation_rates(array $summary): array {
    $sent = max(0, (int) ($summary['sent'] ?? 0));
    $delivered = max(0, (int) ($summary['delivered'] ?? 0));
    $opened = max(0, (int) ($summary['opened'] ?? 0));
    $clicked = max(0, (int) ($summary['clicked'] ?? 0));
    $goals = max(0, (int) ($summary['goal_completed'] ?? 0));

    return [
        'delivery_rate' => $sent ? round(($delivered / $sent) * 100, 1) : 0.0,
        'open_rate' => $delivered ? round(($opened / $delivered) * 100, 1) : 0.0,
        'click_rate' => $delivered ? round(($clicked / $delivered) * 100, 1) : 0.0,
        'click_to_open_rate' => $opened ? round(($clicked / $opened) * 100, 1) : 0.0,
        'goal_rate' => $delivered ? round(($goals / $delivered) * 100, 1) : 0.0,
    ];
}

function fc_get_email_messages_summary(array $messages): array {
    $summary = [
        'total' => 0,
        'sent' => 0,
        'delivered' => 0,
        'opened' => 0,
        'clicked' => 0,
        'goal_completed' => 0,
        'deferred' => 0,
        'soft_bounce' => 0,
        'hard_bounce' => 0,
        'blocked' => 0,
        'invalid' => 0,
        'spam' => 0,
        'unsubscribed' => 0,
        'send_failed' => 0,
    ];

    foreach($messages as $message) {
        $summary['total']++;

        if(($message->status ?? '') !== 'send_failed') {
            $summary['sent']++;
        } else {
            $summary['send_failed']++;
        }

        if(!empty($message->delivered_datetime)) {
            $summary['delivered']++;
        }

        if(!empty($message->first_open_datetime)) {
            $summary['opened']++;
        }

        if(!empty($message->first_click_datetime)) {
            $summary['clicked']++;
        }

        if(!empty($message->goal_completed_datetime)) {
            $summary['goal_completed']++;
        }

        if(!empty($message->unsubscribe_datetime) || ($message->status ?? null) === 'unsubscribed') {
            $summary['unsubscribed']++;
        }

        foreach(['deferred', 'soft_bounce', 'hard_bounce', 'blocked', 'invalid', 'spam'] as $status) {
            if(($message->status ?? null) === $status) {
                $summary[$status]++;
            }
        }
    }

    return $summary;
}

function fc_does_email_message_match_status($message, ?string $status_filter = null): bool {
    if(!$status_filter || $status_filter === 'all') {
        return true;
    }

    switch($status_filter) {
        case 'sent':
            return ($message->status ?? '') !== 'send_failed';

        case 'delivered':
            return !empty($message->delivered_datetime);

        case 'opened':
            return !empty($message->first_open_datetime);

        case 'clicked':
            return !empty($message->first_click_datetime);

        case 'goal_completed':
            return !empty($message->goal_completed_datetime);

        case 'unsubscribed':
            return !empty($message->unsubscribe_datetime) || ($message->status ?? '') === 'unsubscribed';

        case 'deferred':
        case 'soft_bounce':
        case 'hard_bounce':
        case 'blocked':
        case 'invalid':
        case 'spam':
        case 'send_failed':
            return ($message->status ?? '') === $status_filter;

        default:
            return false;
    }
}

function fc_get_email_resource_messages(string $message_type, int $resource_id, ?string $status_filter = null, int $limit = 50): array {
    $query = db()->where('message_type', $message_type);

    if($message_type === 'broadcast') {
        $query->where('broadcast_id', $resource_id);
    } else {
        $query->where('automation_id', $resource_id);
    }

    $messages = $query->orderBy('automation_message_id', 'DESC')->get('email_automation_messages', $limit) ?? [];

    if(!$status_filter || $status_filter === 'all') {
        return $messages;
    }

    return array_values(array_filter($messages, static function($message) use ($status_filter) {
        return fc_does_email_message_match_status($message, $status_filter);
    }));
}

function fc_get_email_resource_analytics(string $message_type, int $resource_id): array {
    $messages = fc_get_email_resource_messages($message_type, $resource_id, null, 0);
    $summary = fc_get_email_messages_summary($messages);

    return [
        'summary' => $summary,
        'rates' => fc_calculate_email_automation_rates($summary),
        'recent_messages' => array_slice($messages, 0, 10),
    ];
}

function fc_get_email_resource_webhook_event_count(string $message_type, int $resource_id): int {
    fc_ensure_email_automation_tables();

    $query = db()->where('message_type', $message_type);

    if($message_type === 'broadcast') {
        $query->where('broadcast_id', $resource_id);
    } else {
        $query->where('automation_id', $resource_id);
    }

    return (int) $query->getValue('email_automation_message_events', 'COUNT(*)');
}

function fc_get_email_webhook_health_summary(): array {
    fc_ensure_email_automation_tables();

    $latest_event = db()->orderBy('automation_message_event_id', 'DESC')->getOne('email_automation_message_events');
    $latest_unmatched_event = db()->where('automation_message_id', null, 'IS')->orderBy('automation_message_event_id', 'DESC')->getOne('email_automation_message_events');

    return [
        'total_events' => (int) db()->getValue('email_automation_message_events', 'COUNT(*)'),
        'latest_event' => $latest_event,
        'unmatched_events' => (int) db()->where('automation_message_id', null, 'IS')->getValue('email_automation_message_events', 'COUNT(*)'),
        'latest_unmatched_event' => $latest_unmatched_event,
    ];
}

/* Custom code: FC-2026-03-19: per-user email activity analytics for admin profile */
function fc_get_user_email_activity(int $user_id, int $limit = 100): array {
    fc_ensure_email_automation_tables();

    $messages = db()->where('user_id', $user_id)->orderBy('automation_message_id', 'DESC')->get('email_automation_messages', $limit) ?? [];
    $summary = fc_get_email_messages_summary($messages);
    $rates = fc_calculate_email_automation_rates($summary);

    $broadcast_ids = [];
    $automation_ids = [];

    foreach($messages as $message) {
        if(($message->message_type ?? 'automation') === 'broadcast' && !empty($message->broadcast_id)) {
            $broadcast_ids[] = (int) $message->broadcast_id;
        }

        if(($message->message_type ?? 'automation') === 'automation' && !empty($message->automation_id)) {
            $automation_ids[] = (int) $message->automation_id;
        }
    }

    $broadcasts = [];
    $automations = [];

    if($broadcast_ids = array_values(array_unique($broadcast_ids))) {
        $broadcast_rows = db()->where('broadcast_id', $broadcast_ids, 'IN')->get('broadcasts', null, ['broadcast_id', 'name']);

        foreach($broadcast_rows as $broadcast) {
            $broadcasts[(int) $broadcast->broadcast_id] = $broadcast;
        }
    }

    if($automation_ids = array_values(array_unique($automation_ids))) {
        $automation_rows = db()->where('automation_id', $automation_ids, 'IN')->get('email_automations', null, ['automation_id', 'name']);

        foreach($automation_rows as $automation) {
            $automations[(int) $automation->automation_id] = $automation;
        }
    }

    $summary_by_type = [
        'broadcast' => 0,
        'automation' => 0,
    ];

    foreach($messages as $message) {
        $message_type = ($message->message_type ?? 'automation') === 'broadcast' ? 'broadcast' : 'automation';
        $summary_by_type[$message_type]++;

        if($message_type === 'broadcast') {
            $broadcast_id = (int) ($message->broadcast_id ?? 0);
            $message->resource_name = $broadcasts[$broadcast_id]->name ?? ('Broadcast #' . $broadcast_id);
            $message->resource_url = $broadcast_id ? url('admin/broadcast-view/' . $broadcast_id) : null;
        } else {
            $automation_id = (int) ($message->automation_id ?? 0);
            $message->resource_name = $automations[$automation_id]->name ?? ('Automation #' . $automation_id);
            $message->resource_url = $automation_id ? url('admin/automation-update/' . $automation_id) : null;
        }
    }

    return [
        'summary' => $summary,
        'rates' => $rates,
        'summary_by_type' => $summary_by_type,
        'recent_messages' => $messages,
    ];
}
/* /Custom code: FC-2026-03-19 */

function fc_get_email_hub_analytics(): array {
    $messages = db()->orderBy('automation_message_id', 'DESC')->get('email_automation_messages') ?? [];
    $summary = fc_get_email_messages_summary($messages);

    return [
        'summary' => $summary,
        'rates' => fc_calculate_email_automation_rates($summary),
        'totals' => [
            'broadcasts' => (int) db()->getValue('broadcasts', 'COUNT(*)'),
            'automations' => (int) db()->getValue('email_automations', 'COUNT(*)'),
        ],
    ];
}

function fc_get_email_automation_analytics(int $automation_id): array {
    $messages = fc_get_email_resource_messages('automation', $automation_id, null, 0);
    $steps = fc_get_email_automation_steps($automation_id);
    $resource_analytics = fc_get_email_resource_analytics('automation', $automation_id);
    $summary = $resource_analytics['summary'];

    $per_step = [];

    foreach($steps as $step) {
        $per_step[(int) $step->automation_step_id] = [
            'step' => $step,
            'sent' => 0,
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'goal_completed' => 0,
        ];
    }

    foreach($messages as $message) {
        $step_id = (int) $message->automation_step_id;

        if(isset($per_step[$step_id])) {
            $per_step[$step_id]['sent']++;
        }

        if(!empty($message->delivered_datetime)) {
            $summary['delivered']++;
            if(isset($per_step[$step_id])) {
                $per_step[$step_id]['delivered']++;
            }
        }

        if(!empty($message->first_open_datetime)) {
            $summary['opened']++;
            if(isset($per_step[$step_id])) {
                $per_step[$step_id]['opened']++;
            }
        }

        if(!empty($message->first_click_datetime)) {
            $summary['clicked']++;
            if(isset($per_step[$step_id])) {
                $per_step[$step_id]['clicked']++;
            }
        }

        if(!empty($message->goal_completed_datetime)) {
            $summary['goal_completed']++;
            if(isset($per_step[$step_id])) {
                $per_step[$step_id]['goal_completed']++;
            }
        }
    }

    foreach($per_step as $step_id => $step_summary) {
        $per_step[$step_id]['rates'] = fc_calculate_email_automation_rates($step_summary);
    }

    return [
        'summary' => $summary,
        'rates' => fc_calculate_email_automation_rates($summary),
        'per_step' => array_values($per_step),
        'recent_messages' => $resource_analytics['recent_messages'],
    ];
}
/* /Custom code: FC-2026-03-19 */

function fc_get_next_email_automation_step(array $steps, int $current_step): ?object {
    foreach($steps as $step) {
        if((int) $step->step_order > $current_step) {
            return $step;
        }
    }

    return null;
}
/* /Custom code: FC-2026-03-18 */