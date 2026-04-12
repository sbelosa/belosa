<?php

defined('ALTUMCODE') || die();

function fcc_featured_ensure_columns(): void {
    static $is_ready = false;

    if($is_ready) {
        return;
    }

    $required_columns = [
        'fcc_featured_opt_in' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_opt_in` TINYINT(1) NOT NULL DEFAULT 1",
        'fcc_featured_is_approved' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_is_approved` TINYINT(1) NOT NULL DEFAULT 1",
        'fcc_featured_public_market' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_public_market` VARCHAR(64) NULL DEFAULT NULL",
        'fcc_featured_public_use_case' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_public_use_case` VARCHAR(128) NULL DEFAULT NULL",
        'fcc_featured_public_summary' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_public_summary` VARCHAR(512) NULL DEFAULT NULL",
        'fcc_featured_profile_form' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_profile_form` MEDIUMTEXT NULL DEFAULT NULL",
        'fcc_featured_profile_generated' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_profile_generated` MEDIUMTEXT NULL DEFAULT NULL",
    ];

    foreach($required_columns as $column => $query) {
        $column_result = db()->rawQuery("SHOW COLUMNS FROM `links` LIKE '{$column}'");

        if(empty($column_result)) {
            db()->rawQuery($query);
        }
    }

    $is_ready = true;
}

function fcc_featured_resolve_display_image_for_link(?int $link_id): ?string {
    if(!$link_id) {
        return null;
    }

    $hero_block = db()->where('link_id', $link_id)
        ->where('is_enabled', 1)
        ->where('type', ['header', 'avatar', 'image'], 'IN')
        ->orderBy('`order`', 'ASC')
        ->getOne('biolinks_blocks', ['type', 'settings']);

    if(!$hero_block) {
        return null;
    }

    $hero_block->settings = json_decode($hero_block->settings ?? '');

    if($hero_block->type === 'header' && !empty($hero_block->settings->avatar)) {
        return \Altum\Uploads::get_full_url('avatars') . $hero_block->settings->avatar;
    }

    if($hero_block->type === 'avatar' && !empty($hero_block->settings->image)) {
        return \Altum\Uploads::get_full_url('avatars') . $hero_block->settings->image;
    }

    if($hero_block->type === 'image' && !empty($hero_block->settings->image)) {
        return \Altum\Uploads::get_full_url('block_images') . $hero_block->settings->image;
    }

    return null;
}

function fcc_featured_get_default_public_market(object $row): string {
    $preferences = is_string($row->preferences ?? null) ? json_decode($row->preferences ?? '{}') : ($row->preferences ?? (object) []);
    if(is_array($preferences)) {
        $preferences = (object) $preferences;
    }

    $meta = $preferences->meta ?? (object) [];
    if(is_array($meta)) {
        $meta = (object) $meta;
    }

    $billing = is_string($row->billing ?? null) ? json_decode($row->billing ?? '{}') : ($row->billing ?? (object) []);
    if(is_array($billing)) {
        $billing = (object) $billing;
    }

    $candidates = [
        trim((string) ($row->fcc_featured_public_market ?? '')),
        trim((string) ($meta->country ?? '')),
        trim((string) ($billing->country ?? '')),
    ];

    foreach($candidates as $candidate) {
        if($candidate === '') {
            continue;
        }

        if(strlen($candidate) === 2) {
            $countries = get_countries_array();
            if(isset($countries[$candidate])) {
                return $countries[$candidate];
            }
        }

        return $candidate;
    }

    return '';
}

function fcc_featured_get_case_study_feature_labels(int $link_id, ?string $language = null): array {
    $labels = [];
    $block_types_result = database()->query("SELECT `type` FROM `biolinks_blocks` WHERE `link_id` = {$link_id} AND `is_enabled` = 1");

    if(!$block_types_result) {
        return $labels;
    }

    $available_types = [];

    while($row = $block_types_result->fetch_object()) {
        $available_types[(string) $row->type] = true;
    }

    $language = $language ?? \Altum\Language::$code;

    $map = $language === 'hr'
        ? [
            'smart_links' => [
                'label' => 'Pametni preporučni linkovi',
                'types' => ['link_discount', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'],
            ],
            'ai' => [
                'label' => 'AI asistenti',
                'types' => ['custom_html_chatbot', 'custom_html_chatbot_pets'],
            ],
            'funnel' => [
                'label' => 'FCC Funnel',
                'types' => ['lead_funnel'],
            ],
            'lead_capture' => [
                'label' => 'Prikupljanje kontakata',
                'types' => ['contact_collector', 'email_collector', 'phone_collector', 'appointment_calendar'],
            ],
            'contact' => [
                'label' => 'Kontakt i spremanje kontakta',
                'types' => ['link_save_contact', 'custom_html_whatsapp'],
            ],
        ]
        : [
            'smart_links' => [
                'label' => 'Smart referral links',
                'types' => ['link_discount', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'],
            ],
            'ai' => [
                'label' => 'AI assistants',
                'types' => ['custom_html_chatbot', 'custom_html_chatbot_pets'],
            ],
            'funnel' => [
                'label' => 'FCC Funnel',
                'types' => ['lead_funnel'],
            ],
            'lead_capture' => [
                'label' => 'Lead capture',
                'types' => ['contact_collector', 'email_collector', 'phone_collector', 'appointment_calendar'],
            ],
            'contact' => [
                'label' => 'Contact actions',
                'types' => ['link_save_contact', 'custom_html_whatsapp'],
            ],
        ];

    foreach($map as $group) {
        foreach($group['types'] as $type) {
            if(isset($available_types[$type])) {
                $labels[] = $group['label'];
                break;
            }
        }
    }

    return array_slice($labels, 0, 5);
}

function fcc_featured_get_public_summary(string $stored_summary, array $feature_labels, ?string $language = null): string {
    $stored_summary = trim($stored_summary);
    if($stored_summary !== '') {
        return $stored_summary;
    }

    $feature_labels = array_values(array_filter(array_map('trim', $feature_labels)));
    $language = $language ?? \Altum\Language::$code;

    if(empty($feature_labels)) {
        return $language === 'hr'
            ? 'Glavna Forever Card Aplikacija povezuje predstavljanje, preporuke i kontakt u jednom jasnom poslovnom toku.'
            : 'The main Forever Card App connects presentation, referrals, and contact actions inside one clear business flow.';
    }

    $top_labels = array_slice($feature_labels, 0, 3);

    return $language === 'hr'
        ? 'Glavna Forever Card Aplikacija koristi ' . implode(', ', $top_labels) . ' kao dio svakodnevnog Forever poslovanja.'
        : 'The main Forever Card App uses ' . implode(', ', $top_labels) . ' as part of the everyday Forever workflow.';
}

function fcc_featured_get_public_use_case(string $stored_use_case, array $feature_labels, ?string $language = null): string {
    $stored_use_case = trim($stored_use_case);
    if($stored_use_case !== '') {
        return $stored_use_case;
    }

    $feature_labels = array_values(array_filter(array_map('trim', $feature_labels)));
    $language = $language ?? \Altum\Language::$code;

    if(empty($feature_labels)) {
        return $language === 'hr'
            ? 'Glavna Forever Card aplikacija za predstavljanje i preporuku'
            : 'Main Forever Card app for presentation and referrals';
    }

    $top_labels = array_slice($feature_labels, 0, 2);

    return $language === 'hr'
        ? 'Glavna FCC aplikacija za ' . mb_strtolower(implode(' i ', $top_labels))
        : 'Main FCC app for ' . mb_strtolower(implode(' and ', $top_labels));
}

function fcc_featured_build_public_app_url(object $row, int $link_id): string {
    $has_custom_domain = !empty($row->domain_id) && !empty($row->host) && !empty($row->scheme);

    return $has_custom_domain
        ? $row->scheme . $row->host . ((int) ($row->domain_link_id ?? 0) === $link_id ? '' : '/' . $row->url)
        : SITE_URL . $row->url;
}

function fcc_featured_build_profile_slug(string $name, int $link_id): string {
    $name_slug = get_slug($name !== '' ? $name : ('sponsor-' . $link_id));

    return $link_id . '-' . ($name_slug !== '' ? $name_slug : ('sponsor-' . $link_id));
}

function fcc_featured_extract_profile_link_id(string $profile_slug): int {
    if(preg_match('/^(\d+)/', trim($profile_slug), $matches)) {
        return (int) ($matches[1] ?? 0);
    }

    return 0;
}

function fcc_featured_build_profile_url(array $item): string {
    $profile_slug = trim((string) ($item['profile_slug'] ?? ''));

    return $profile_slug !== '' ? url('recommended-sponsors/' . $profile_slug) : url('recommended-sponsors');
}

function fcc_featured_decode_json_payload($value): array {
    if(is_array($value)) {
        return $value;
    }

    if(is_object($value)) {
        return (array) $value;
    }

    if(!is_string($value) || trim($value) === '') {
        return [];
    }

    $decoded = json_decode($value, true);

    return is_array($decoded) ? $decoded : [];
}

function fcc_featured_clear_public_cache(int $user_id = 0, int $link_id = 0): void {
    cache()->deleteItemsByTag('fcc_featured_catalog');

    if($user_id > 0) {
        cache()->deleteItemsByTag('user_id=' . $user_id);
        cache()->deleteItem('user?user_id=' . $user_id);
    }

    if($link_id > 0) {
        cache()->deleteItemsByTag('link_id=' . $link_id);
        cache()->deleteItem('link?link_id=' . $link_id);
        cache()->deleteItem('biolink_blocks?link_id=' . $link_id);
    }
}

function fcc_featured_get_effective_public_use_case(string $stored_use_case, array $generated_profile, array $feature_labels, ?string $language = null): string {
    $generated_use_case = trim((string) ($generated_profile['public_use_case'] ?? ''));

    if($generated_use_case !== '') {
        return $generated_use_case;
    }

    return fcc_featured_get_public_use_case($stored_use_case, $feature_labels, $language);
}

function fcc_featured_get_effective_public_summary(string $stored_summary, array $generated_profile, array $feature_labels, ?string $language = null): string {
    $generated_summary = trim((string) ($generated_profile['public_summary'] ?? ''));

    if($generated_summary !== '') {
        return $generated_summary;
    }

    return fcc_featured_get_public_summary($stored_summary, $feature_labels, $language);
}

function fcc_featured_get_catalog(array $options = []): array {
    fcc_featured_ensure_columns();

    $language = (string) ($options['language'] ?? \Altum\Language::$code);
    $min_signal_30d = max(0, (int) ($options['min_signal_30d'] ?? 15));
    $experience_signal_target = max($min_signal_30d, (int) ($options['experience_signal_target'] ?? 50));
    $weekly_check_target = max(0, (int) ($options['weekly_check_target'] ?? 15));
    $require_experience_signal = (bool) ($options['require_experience_signal'] ?? false);
    $require_valid_sales_link = (bool) ($options['require_valid_sales_link'] ?? false);
    $limit = isset($options['limit']) ? max(0, (int) $options['limit']) : 0;
    $only_link_id = max(0, (int) ($options['only_link_id'] ?? 0));
    $only_user_id = max(0, (int) ($options['only_user_id'] ?? 0));

    $cache_key = 'fcc_featured_catalog?hash=' . md5(json_encode([
        'version' => '2026-04-12-public-signal-alignment-v1',
        'language' => $language,
        'min_signal_30d' => $min_signal_30d,
        'experience_signal_target' => $experience_signal_target,
        'weekly_check_target' => $weekly_check_target,
        'require_experience_signal' => $require_experience_signal,
        'require_valid_sales_link' => $require_valid_sales_link,
        'limit' => $limit,
        'only_link_id' => $only_link_id,
        'only_user_id' => $only_user_id,
    ]));

    return \Altum\Cache::cache_function_result($cache_key, ['fcc_featured_catalog'], function() use (
        $language,
        $min_signal_30d,
        $experience_signal_target,
        $weekly_check_target,
        $require_experience_signal,
        $require_valid_sales_link,
        $limit,
        $only_link_id,
        $only_user_id
    ) {
        $items = [];
        $seen_featured_user_ids = [];
        $seen_featured_link_ids = [];
        $users_biolinks_latest_sql = \Altum\Link::get_users_biolinks_latest_subquery('users_biolinks');

        $where_clauses = [
            "`main_link`.`type` = 'biolink'",
            "`main_link`.`is_enabled` = 1",
            "`main_link`.`fcc_featured_opt_in` = 1",
            "`main_link`.`fcc_featured_is_approved` = 1",
        ];

        if($only_link_id > 0) {
            $where_clauses[] = "`main_link`.`link_id` = {$only_link_id}";
        }

        if($only_user_id > 0) {
            $where_clauses[] = "`main_link`.`user_id` = {$only_user_id}";
        }

        $candidate_apps_result = database()->query("
            SELECT
                `main_link`.`link_id`,
                `main_link`.`user_id`,
                `main_link`.`url`,
                `main_link`.`domain_id`,
                `main_link`.`fcc_featured_public_market`,
                `main_link`.`fcc_featured_public_use_case`,
                `main_link`.`fcc_featured_public_summary`,
                `main_link`.`fcc_featured_profile_generated`,
                `main_link`.`last_datetime`,
                `main_link`.`datetime`,
                `domains`.`scheme`,
                `domains`.`host`,
                `domains`.`link_id` AS `domain_link_id`,
                `users`.`plan_id`,
                `users`.`plan_settings`,
                `users`.`plan_expiration_date`,
                `users`.`name`,
                `users`.`email`,
                `users`.`avatar`,
                `users`.`preferences`,
                `users`.`billing`
            FROM {$users_biolinks_latest_sql}
            INNER JOIN `links` AS `main_link` ON `main_link`.`link_id` = `users_biolinks`.`biolink_id`
            INNER JOIN `users` ON `users`.`user_id` = `users_biolinks`.`user_id`
            LEFT JOIN `domains` ON `main_link`.`domain_id` = `domains`.`domain_id`
            WHERE " . implode(' AND ', $where_clauses) . "
            ORDER BY `users`.`name` ASC
        ");

        while($row = $candidate_apps_result->fetch_object()) {
            $link_id = (int) ($row->link_id ?? 0);
            $user_id = (int) ($row->user_id ?? 0);

            if(
                !$link_id
                || !$user_id
                || empty($row->url)
                || isset($seen_featured_link_ids[$link_id])
                || isset($seen_featured_user_ids[$user_id])
            ) {
                continue;
            }

            if(!fcc_ai_user_has_active_growth_pro($row)) {
                continue;
            }

            $signal_snapshot = fcc_ai_get_user_public_visibility_signal_snapshot($user_id);
            $growth_signal_7d = (int) ($signal_snapshot['growth_signal_7d'] ?? 0);
            $growth_signal_30d = (int) ($signal_snapshot['growth_signal_30d'] ?? 0);

            if($growth_signal_30d < $min_signal_30d) {
                continue;
            }

            if($require_experience_signal && $growth_signal_30d < $experience_signal_target) {
                continue;
            }

            $sales_link_summary = fcc_ai_get_user_sales_link_summary($row, $language);
            if($require_valid_sales_link && empty($sales_link_summary['has_valid_enabled_link'])) {
                continue;
            }

            $feature_labels = fcc_featured_get_case_study_feature_labels($link_id, $language);
            $profile_slug = fcc_featured_build_profile_slug((string) ($row->name ?? ''), $link_id);
            $generated_profile = fcc_featured_decode_json_payload($row->fcc_featured_profile_generated ?? null);

            $item = [
                'link_id' => $link_id,
                'user_id' => $user_id,
                'name' => (string) ($row->name ?? l('global.unknown')),
                'email' => (string) ($row->email ?? ''),
                'avatar' => (string) ($row->avatar ?? ''),
                'display_image_url' => fcc_featured_resolve_display_image_for_link($link_id),
                'default_image_url' => SITE_URL . 'uploads/logo/forever.png',
                'generated_avatar_url' => get_user_avatar(null, (string) ($row->email ?? ($row->name ?? ''))),
                'app_url' => fcc_featured_build_public_app_url($row, $link_id),
                'profile_slug' => $profile_slug,
                'growth_signal_7d' => $growth_signal_7d,
                'growth_signal_30d' => $growth_signal_30d,
                'has_weekly_check_7d' => $growth_signal_7d >= $weekly_check_target,
                'has_experience_signal_30d' => $growth_signal_30d >= $experience_signal_target,
                'shop_contacts_7d' => (int) ($signal_snapshot['shop_contacts_7d'] ?? 0),
                'whatsapp_contacts_7d' => (int) ($signal_snapshot['whatsapp_contacts_7d'] ?? 0),
                'funnel_registrations_7d' => (int) ($signal_snapshot['funnel_registrations_7d'] ?? 0),
                'ai_chat_leads_7d' => (int) ($signal_snapshot['ai_chat_leads_7d'] ?? 0),
                'shop_contacts_30d' => (int) ($signal_snapshot['shop_contacts_30d'] ?? 0),
                'whatsapp_contacts_30d' => (int) ($signal_snapshot['whatsapp_contacts_30d'] ?? 0),
                'funnel_registrations_30d' => (int) ($signal_snapshot['funnel_registrations_30d'] ?? 0),
                'ai_chat_leads_30d' => (int) ($signal_snapshot['ai_chat_leads_30d'] ?? 0),
                'public_market' => fcc_featured_get_default_public_market($row),
                'public_use_case' => fcc_featured_get_effective_public_use_case((string) ($row->fcc_featured_public_use_case ?? ''), $generated_profile, $feature_labels, $language),
                'public_summary' => fcc_featured_get_effective_public_summary((string) ($row->fcc_featured_public_summary ?? ''), $generated_profile, $feature_labels, $language),
                'generated_profile' => $generated_profile,
                'profile_intro' => trim((string) ($generated_profile['profile_intro'] ?? '')),
                'meta_description' => trim((string) ($generated_profile['meta_description'] ?? '')),
                'profile_generated_at' => trim((string) ($generated_profile['generated_at'] ?? '')),
                'feature_labels' => $feature_labels,
                'sales_link_status_key' => (string) ($sales_link_summary['status_key'] ?? 'missing'),
                'sales_link_status_label' => (string) ($sales_link_summary['status_label'] ?? ''),
                'sales_link_ready' => !empty($sales_link_summary['has_valid_enabled_link']),
                'sales_link_apply_to_all_products' => !empty($sales_link_summary['main_app_apply_to_all_products']),
                'link_lastmod' => (string) ($row->last_datetime ?? $row->datetime ?? ''),
            ];

            $item['profile_url'] = fcc_featured_build_profile_url($item);

            $items[] = $item;
            $seen_featured_link_ids[$link_id] = true;
            $seen_featured_user_ids[$user_id] = true;
        }

        usort($items, static function(array $a, array $b) {
            $experience_compare = ((int) (!empty($b['has_experience_signal_30d']))) <=> ((int) (!empty($a['has_experience_signal_30d'])));

            if($experience_compare !== 0) {
                return $experience_compare;
            }

            $signal_30d_compare = ((int) ($b['growth_signal_30d'] ?? 0)) <=> ((int) ($a['growth_signal_30d'] ?? 0));

            if($signal_30d_compare !== 0) {
                return $signal_30d_compare;
            }

            $signal_7d_compare = ((int) ($b['growth_signal_7d'] ?? 0)) <=> ((int) ($a['growth_signal_7d'] ?? 0));

            if($signal_7d_compare !== 0) {
                return $signal_7d_compare;
            }

            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        if($limit > 0) {
            $items = array_slice($items, 0, $limit);
        }

        return $items;
    });
}
