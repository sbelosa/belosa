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
            'subject' => 'Imas 3 minute za besplatan korak koji moze donijeti rezultat.',
            'content' => '<p>Bok {{USER:NAME}},</p><p>zelimo te potaknuti da iskoristis jednu jednostavnu mogucnost koju sada imas na svojoj FCC aplikaciji.</p><p>Na svoju Forever Card aplikaciju mozes potpuno besplatno postaviti linkove za <strong>popust</strong> i <strong>preporuku Forever proizvoda</strong>, a zatim link svoje aplikacije ubaciti i u biolink opis na svojim drustvenim mrezama.</p><p>Ovo je:</p><ul><li>besplatna opcija</li><li>i ne zahtjeva Pro paket</li><li>uzima ti oko 3 minute</li><li>moze ti donijeti pasivnu zaradu i preporuke</li></ul><p>Upravo je napravljen novi FCC update i dodano je puno novih stvari, ali za ovaj dio ti ne treba nikakav dodatni paket. Dovoljno je samo da aktiviras ono sto ti je vec dostupno.</p><p>Ako jos nisi pogledao video, vodi te korak po korak:<br /><a href="{{FCC_VIDEO_URL}}">{{FCC_VIDEO_URL}}</a></p><p>Postavljanje odradi ovdje:<br /><a href="{{SALES_LINKS_PAGE}}">{{SALES_LINKS_PAGE}}</a></p><p>Nemoj cekati savrsen trenutak. Ovo je mali korak koji ti moze otvoriti dodatne preporuke i narudzbe.</p><p>{{WEBSITE_TITLE}}</p>',
        ],
        [
            'step_order' => 2,
            'delay_minutes' => 2880,
            'subject' => 'Besplatno je, brzo je i steta je da ti stoji neiskoristeno.',
            'content' => '<p>Bok {{USER:NAME}},</p><p>samo kratki podsjetnik, jer jos uvijek ne vidimo aktivirane Forever linkove na tvojoj FCC aplikaciji.</p><p>Ovo je:</p><ul><li>besplatna opcija</li><li>i ne zahtjeva Pro paket</li><li>uzima ti oko 3 minute</li><li>moze ti donijeti pasivnu zaradu i preporuke</li></ul><p>Kad na aplikaciji postavis link za popust i preporuku Forever proizvoda, dovoljno je da svoj link od aplikacije postavis u biolink u opisu na svojim drustvenim mrezama. Tako ljudi mogu samostalno kliknuti i naruciti proizvode s tvojom preporukom.</p><p>Ako jos nisi pogledao video, vodi te korak po korak:<br /><a href="{{FCC_VIDEO_URL}}">{{FCC_VIDEO_URL}}</a></p><p>Postavljanje odradi ovdje:<br /><a href="{{SALES_LINKS_PAGE}}">{{SALES_LINKS_PAGE}}</a></p><p>Nemoj cekati savrsen trenutak, ovo je mali korak koji realno moze donijeti ogroman rezultat.</p><p>{{WEBSITE_TITLE}}</p>',
        ],
        [
            'step_order' => 3,
            'delay_minutes' => 10080,
            'subject' => 'Vrijedi aktivirati ovo sada, dok ti je sve vec spremno.',
            'content' => '<p>Bok {{USER:NAME}},</p><p>ovo je jos jedan kratki podsjetnik, jer bi bilo steta da ne iskoristis ovu mogucnost koju vec imas unutar svoje FCC aplikacije.</p><p>Za postavljanje linkova za popust i preporuku Forever proizvoda ne treba ti Pro paket, ne placas nista dodatno i sve mozes odraditi za par minuta.</p><p>Ovo je:</p><ul><li>besplatna opcija</li><li>i ne zahtjeva Pro paket</li><li>uzima ti oko 3 minute</li><li>moze ti donijeti pasivnu zaradu i preporuke</li></ul><p>Kada to postavis na svoju aplikaciju i link aplikacije stavis u biolink opis na drustvenim mrezama, ljudima olaksavas da sami kliknu, pogledaju i kupe preko tvoje preporuke.</p><p>Ako jos nisi pogledao video, vodi te korak po korak:<br /><a href="{{FCC_VIDEO_URL}}">{{FCC_VIDEO_URL}}</a></p><p>Postavljanje odradi ovdje:<br /><a href="{{SALES_LINKS_PAGE}}">{{SALES_LINKS_PAGE}}</a></p><p>Napravis li to sada, mozes vec danas imati aktivan alat koji radi za tebe bez dodatnog troska.</p><p>{{WEBSITE_TITLE}}</p>',
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
        'template_version' => 3,
    ];

    $steps = fc_get_default_email_automation_steps();

    if($automation) {
        $automation_settings = fc_get_email_automation_settings($automation->settings ?? null);

        if($automation_settings->template_version < 3) {
            db()->where('automation_id', $automation->automation_id)->update('email_automations', [
                'settings' => json_encode(array_merge((array) $automation_settings, [
                    'video_url' => $automation_settings->video_url ?: url('fcc-education'),
                    'template_version' => 3,
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