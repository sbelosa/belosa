<?php
/* Custom code: FC-2026-03-18: live email automations helpers */
defined('ALTUMCODE') || die();

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
    $settings->segment_label = $settings->segment_label ?? 'Suradnici bez Forever prodajnog linka';
    $settings->exit_when_condition_met = (int) ($settings->exit_when_condition_met ?? 1);
    $settings->reentry_is_enabled = (int) ($settings->reentry_is_enabled ?? 1);
    $settings->video_url = !empty($settings->video_url) ? input_clean($settings->video_url, 2048) : url('fcc-education');
    $settings->template_version = (int) ($settings->template_version ?? 0);

    return $settings;
}

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

function fc_get_automation_segment_users(string $segment): array {
    switch($segment) {
        case 'missing_forever_sales_link':
            return fc_get_missing_sales_link_segment_users();

        default:
            return [];
    }
}

function fc_get_automation_segment_count(string $segment): int {
    return count(fc_get_automation_segment_users($segment));
}

function fc_is_user_in_automation_segment(string $segment, int $user_id): bool {
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

        default:
            return false;
    }
}

function fc_get_email_automation_user_variables($user, $automation_settings = null): array {
    $automation_settings = fc_get_email_automation_settings($automation_settings);

    return [
        '{{USER:NAME}}' => $user->name ?? '',
        '{{USER:EMAIL}}' => $user->email ?? '',
        '{{USER:LOGIN_LINK}}' => url('login'),
        '{{SALES_LINKS_PAGE}}' => url('links'),
        '{{FCC_RESULTS_PAGE}}' => url('fcc-results'),
        '{{FEATURED_APPS_PAGE}}' => url('featured-apps'),
        '{{FCC_VIDEO_URL}}' => $automation_settings->video_url,
        '{{WEBSITE_TITLE}}' => settings()->main->title,
    ];
}

/* Custom code: FC-2026-03-19: disable automatic automation email footer */
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

function fc_get_next_email_automation_step(array $steps, int $current_step): ?object {
    foreach($steps as $step) {
        if((int) $step->step_order > $current_step) {
            return $step;
        }
    }

    return null;
}
/* /Custom code: FC-2026-03-18 */