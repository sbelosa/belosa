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
                'types' => ['link_discount', 'link_forever_webshop_reg', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'],
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
                'types' => ['link_discount', 'link_forever_webshop_reg', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'],
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

function fcc_featured_get_main_biolink_record(int $user_id): ?object {
    fcc_featured_ensure_columns();

    if($user_id <= 0) {
        return null;
    }

    $mapped_biolink_id = (int) (fc_get_user_main_biolink_id($user_id) ?? 0);
    $select_sql = "
        SELECT
            `links`.*,
            `domains`.`scheme`,
            `domains`.`host`,
            `domains`.`link_id` AS `domain_link_id`,
            `users`.`name`,
            `users`.`email`,
            `users`.`language`,
            `users`.`anti_phishing_code`,
            `users`.`preferences`,
            `users`.`billing`,
            `users`.`plan_id`,
            `users`.`plan_settings`,
            `users`.`plan_expiration_date`,
            `users`.`extra`,
            `users`.`status`
        FROM `links`
        INNER JOIN `users` ON `users`.`user_id` = `links`.`user_id`
        LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id`
    ";

    if($mapped_biolink_id > 0) {
        $result = database()->query($select_sql . "
            WHERE `links`.`user_id` = {$user_id}
              AND `links`.`link_id` = {$mapped_biolink_id}
              AND `links`.`type` = 'biolink'
            LIMIT 1
        ");
        $biolink = $result ? $result->fetch_object() : null;

        if($biolink) {
            return $biolink;
        }
    }

    $result = database()->query($select_sql . "
        WHERE `links`.`user_id` = {$user_id}
          AND `links`.`type` = 'biolink'
        ORDER BY
            CASE WHEN `links`.`is_enabled` = 1 THEN 0 ELSE 1 END ASC,
            `links`.`datetime` ASC,
            `links`.`link_id` ASC
        LIMIT 1
    ");

    return $result ? ($result->fetch_object() ?: null) : null;
}

function fcc_featured_is_generated_profile_complete(array $generated_profile): bool {
    foreach(['public_use_case', 'public_summary', 'profile_intro', 'meta_description'] as $field) {
        if(trim((string) ($generated_profile[$field] ?? '')) === '') {
            return false;
        }
    }

    return true;
}

function fcc_featured_get_public_signal_notification_state($preferences): array {
    $preferences = fcc_ai_normalize_user_preferences($preferences);
    $state = fcc_ai_to_array($preferences->fcc_public_signal_notifications ?? []);

    return array_merge([
        'last_public_signal_30d' => 0,
        'last_public_signal_7d' => 0,
        'qualified_unlock_sent_at' => '',
        'qualified_reminder_sent_at' => '',
        'top_unlock_sent_at' => '',
        'top_reminder_sent_at' => '',
        'qualified_reentry_admin_notified_at' => '',
        'top_reentry_admin_notified_at' => '',
        'last_evaluated_at' => '',
    ], $state);
}

function fcc_featured_save_public_signal_notification_state(int $user_id, $preferences, array $state): void {
    if($user_id <= 0) {
        return;
    }

    $preferences = fcc_ai_normalize_user_preferences($preferences);
    $preferences->fcc_public_signal_notifications = (object) $state;

    db()->where('user_id', $user_id)->update('users', [
        'preferences' => json_encode($preferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
    ]);

    cache()->deleteItemsByTag('user_id=' . $user_id);
    cache()->deleteItem('user?user_id=' . $user_id);
}

function fcc_featured_get_user_public_profile_state($user, string $language = 'hr'): array {
    $language = fcc_ai_resolve_public_reply_language($language);

    if(is_numeric($user)) {
        $user_id = (int) $user;
        $user = $user_id > 0
            ? db()->where('user_id', $user_id)->getOne('users', ['user_id', 'name', 'email', 'language', 'anti_phishing_code', 'preferences', 'billing', 'plan_id', 'plan_settings', 'plan_expiration_date', 'extra', 'status'])
            : null;
    }

    $user_id = (int) ($user->user_id ?? 0);
    $main_biolink = $user_id > 0 ? fcc_featured_get_main_biolink_record($user_id) : null;
    $source = $main_biolink ?: $user;
    $public_signal = $user_id > 0 ? fcc_ai_get_user_public_visibility_signal_snapshot($user_id) : [];
    $sales_link_summary = $source && is_object($source) ? fcc_ai_get_user_sales_link_summary($source, $language) : fcc_ai_get_user_sales_link_summary((object) ['user_id' => $user_id], $language);
    $generated_profile = $main_biolink ? fcc_featured_decode_json_payload($main_biolink->fcc_featured_profile_generated ?? null) : [];
    $feature_labels = $main_biolink ? fcc_featured_get_case_study_feature_labels((int) ($main_biolink->link_id ?? 0), $language) : [];
    $public_use_case = $main_biolink ? fcc_featured_get_effective_public_use_case((string) ($main_biolink->fcc_featured_public_use_case ?? ''), $generated_profile, $feature_labels, $language) : '';
    $public_summary = $main_biolink ? fcc_featured_get_effective_public_summary((string) ($main_biolink->fcc_featured_public_summary ?? ''), $generated_profile, $feature_labels, $language) : '';
    $public_signal_30d = max(0, (int) ($public_signal['growth_signal_30d'] ?? 0));
    $public_signal_7d = max(0, (int) ($public_signal['growth_signal_7d'] ?? 0));
    $qualified_target = max(15, (int) ($public_signal['qualified_target'] ?? 15));
    $top_target = max($qualified_target, (int) ($public_signal['top_target'] ?? 50));
    $weekly_check_target = max(15, (int) ($public_signal['weekly_check_target'] ?? 15));
    $has_growth_pro = $source && is_object($source) ? fcc_ai_user_has_active_growth_pro($source) : false;
    $profile_generated_complete = fcc_featured_is_generated_profile_complete($generated_profile);
    $has_generated_profile = !empty($generated_profile);
    $opt_in_enabled = !empty($main_biolink->fcc_featured_opt_in);
    $is_approved = !empty($main_biolink->fcc_featured_is_approved);
    $is_public_base_ready = $has_growth_pro && !empty($main_biolink->is_enabled) && $opt_in_enabled && $is_approved;
    $featured_list_ready = $is_public_base_ready && $public_signal_30d >= $qualified_target;
    $recommended_list_ready = $featured_list_ready && $public_signal_30d >= $top_target && !empty($sales_link_summary['has_valid_enabled_link']);
    $profile_slug = $main_biolink ? fcc_featured_build_profile_slug((string) ($main_biolink->name ?? ($user->name ?? '')), (int) ($main_biolink->link_id ?? 0)) : '';
    $profile_edit_url = url('links?type=biolink&fcc_profile_modal=1');

    return [
        'user_id' => $user_id,
        'name' => trim((string) ($source->name ?? ($user->name ?? ''))),
        'email' => trim((string) ($source->email ?? ($user->email ?? ''))),
        'language' => trim((string) ($source->language ?? ($user->language ?? $language))),
        'anti_phishing_code' => trim((string) ($source->anti_phishing_code ?? ($user->anti_phishing_code ?? ''))),
        'has_main_biolink' => !empty($main_biolink),
        'main_biolink_id' => (int) ($main_biolink->link_id ?? 0),
        'public_app_url' => $main_biolink ? fcc_featured_build_public_app_url($main_biolink, (int) ($main_biolink->link_id ?? 0)) : '',
        'profile_edit_url' => $profile_edit_url,
        'featured_apps_url' => url('featured-apps'),
        'recommended_sponsors_url' => url('recommended-sponsors'),
        'sponsor_profile_url' => $profile_slug !== '' ? url('recommended-sponsors/' . $profile_slug) : '',
        'has_growth_pro' => $has_growth_pro,
        'is_public_base_ready' => $is_public_base_ready,
        'opt_in_enabled' => $opt_in_enabled,
        'is_approved' => $is_approved,
        'public_signal_30d' => $public_signal_30d,
        'public_signal_7d' => $public_signal_7d,
        'qualified_target' => $qualified_target,
        'top_target' => $top_target,
        'weekly_check_target' => $weekly_check_target,
        'featured_threshold_reached' => $public_signal_30d >= $qualified_target,
        'recommended_threshold_reached' => $public_signal_30d >= $top_target,
        'featured_list_ready' => $featured_list_ready,
        'recommended_list_ready' => $recommended_list_ready,
        'has_generated_profile' => $has_generated_profile,
        'generated_profile_complete' => $profile_generated_complete,
        'generated_profile' => $generated_profile,
        'profile_generated_at' => trim((string) ($generated_profile['generated_at'] ?? '')),
        'public_market' => $main_biolink ? fcc_featured_get_default_public_market($main_biolink) : '',
        'public_use_case' => $public_use_case,
        'public_summary' => $public_summary,
        'profile_intro' => trim((string) ($generated_profile['profile_intro'] ?? '')),
        'meta_description' => trim((string) ($generated_profile['meta_description'] ?? '')),
        'needs_profile_generation' => $public_signal_30d >= $qualified_target && !$profile_generated_complete,
        'sales_link_ready' => !empty($sales_link_summary['has_valid_enabled_link']),
        'sales_link_status_key' => (string) ($sales_link_summary['status_key'] ?? 'missing'),
        'sales_link_status_label' => (string) ($sales_link_summary['status_label'] ?? ''),
        'sales_link_editor_url' => (string) ($sales_link_summary['editor_url'] ?? url('links')),
        'sales_link_summary' => $sales_link_summary,
    ];
}

function fcc_featured_build_email_link_html(string $url, string $label): string {
    $url = trim($url);
    $label = trim($label);

    if($url === '' || $label === '') {
        return '';
    }

    return '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="color:#78f6ea;text-decoration:none;font-weight:700;">' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a>';
}

function fcc_featured_build_email_button_html(string $url, string $label, string $variant = 'primary'): string {
    $url = trim($url);
    $label = trim($label);

    if($url === '' || $label === '') {
        return '';
    }

    $styles = $variant === 'secondary'
        ? 'display:inline-block;padding:12px 18px;border-radius:14px;border:1px solid rgba(99,219,213,0.32);color:#d8fbf8;text-decoration:none;font-weight:700;background:rgba(16,29,45,0.72);'
        : 'display:inline-block;padding:12px 18px;border-radius:14px;border:1px solid rgba(99,219,213,0.28);color:#06212a;text-decoration:none;font-weight:800;background:linear-gradient(135deg,#67ead9 0%,#51cdd2 100%);';

    return '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="' . $styles . '">' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a>';
}

function fcc_featured_build_signal_email_message(string $event_key, array $context, string $language = 'hr', array $options = []): array {
    $language = fcc_ai_resolve_public_reply_language($language);
    $is_english = $language === 'en';
    $is_preview = !empty($options['is_preview']);
    $name = trim((string) ($context['name'] ?? ''));
    $public_signal_30d = max(0, (int) ($context['public_signal_30d'] ?? 0));
    $public_signal_7d = max(0, (int) ($context['public_signal_7d'] ?? 0));
    $qualified_target = max(15, (int) ($context['qualified_target'] ?? 15));
    $top_target = max($qualified_target, (int) ($context['top_target'] ?? 50));
    $weekly_check_target = max(15, (int) ($context['weekly_check_target'] ?? 15));
    $missing_to_top = max(0, $top_target - $public_signal_30d);
    $profile_ready = !empty($context['generated_profile_complete']);
    $sales_link_ready = !empty($context['sales_link_ready']);
    $featured_apps_url = (string) ($context['featured_apps_url'] ?? url('featured-apps'));
    $recommended_sponsors_url = (string) ($context['recommended_sponsors_url'] ?? url('recommended-sponsors'));
    $profile_edit_url = (string) ($context['profile_edit_url'] ?? url('links?type=biolink&fcc_profile_modal=1'));
    $sales_link_editor_url = (string) ($context['sales_link_editor_url'] ?? url('links'));
    $sponsor_profile_url = (string) ($context['sponsor_profile_url'] ?? '');
    $public_app_url = (string) ($context['public_app_url'] ?? '');
    $sponsor_visibility_ready = $public_signal_30d >= $top_target && $sales_link_ready;
    $status_card = '<div style="padding:18px 20px;border-radius:16px;background:linear-gradient(135deg,#11253d 0%,#123b40 100%);border:1px solid rgba(101,225,201,0.24);margin:18px 0;color:#ffffff;line-height:1.75;">'
        . '<div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#9de7e0;margin-bottom:8px;">'
        . ($is_english ? 'Current public FCC status' : 'Trenutačni javni FCC status')
        . '</div>'
        . '<strong>' . ($is_english ? '30d public signal:' : 'Javni 30d signal:') . '</strong> ' . nr($public_signal_30d) . '<br />'
        . '<strong>' . ($is_english ? '7d rhythm check:' : '7d provjera ritma:') . '</strong> ' . nr($public_signal_7d) . ' / ' . nr($weekly_check_target) . '<br />'
        . '<strong>' . ($is_english ? 'Featured Apps threshold:' : 'Prag za Istaknute aplikacije:') . '</strong> ' . nr($qualified_target) . '+ / 30d<br />'
        . '<strong>' . ($is_english ? 'Recommended sponsors threshold:' : 'Prag za preporučene sponzore:') . '</strong> ' . nr($top_target) . '+ / 30d'
        . '</div>';

    $profile_line = $profile_ready
        ? ($is_english
            ? 'Your AI public profile is already generated and ready to support your public FCC visibility.'
            : 'Tvoj AI javni profil je već generiran i spreman podržati tvoju javnu FCC vidljivost.')
        : ($is_english
            ? 'Your AI public profile is not finished yet. Open it now and generate a calm, editorial profile for indexing and public sponsor visibility.'
            : 'Tvoj AI javni profil još nije dovršen. Otvori ga sada i generiraj miran, urednički profil za indeksaciju i javnu sponsor vidljivost.');

    $sales_link_line = $sales_link_ready
        ? ($is_english
            ? 'Your Forever sales link is active, so the public sponsor layer can use it as a valid recommendation path.'
            : 'Tvoj Forever prodajni link je aktivan, pa ga javni sponsor sloj može koristiti kao valjan preporučni put.')
        : ($is_english
            ? 'Your Forever sales link still needs attention. Without a valid active link, the full recommended sponsor layer cannot work properly.'
            : 'Tvoj Forever prodajni link još traži doradu. Bez valjanog aktivnog linka puni sloj preporučenih sponzora ne može raditi kako treba.');

    $actions = [];
    $primary_url = $profile_edit_url;
    $primary_label = $is_english ? 'Open AI profile' : 'Otvori AI profil';
    $secondary_url = $featured_apps_url;
    $secondary_label = $is_english ? 'View Featured Apps' : 'Pogledaj Istaknute aplikacije';
    $notification_url = $profile_edit_url;

    if(in_array($event_key, ['top_unlocked', 'top_reminder'], true)) {
        if(!$sales_link_ready) {
            $primary_url = $sales_link_editor_url;
            $primary_label = $is_english ? 'Fix sales link' : 'Popravi prodajni link';
            $notification_url = $sales_link_editor_url;
        } elseif($sponsor_profile_url !== '') {
            $secondary_url = $sponsor_profile_url;
            $secondary_label = $is_english ? 'Open sponsor profile' : 'Otvori sponsor profil';
        } else {
            $secondary_url = $recommended_sponsors_url;
            $secondary_label = $is_english ? 'View recommended sponsors' : 'Pogledaj preporučene sponzore';
        }
    }

    $actions[] = fcc_featured_build_email_button_html($primary_url, $primary_label, 'primary');
    $actions[] = fcc_featured_build_email_button_html($secondary_url, $secondary_label, 'secondary');
    if($public_app_url !== '' && $public_app_url !== $secondary_url) {
        $actions[] = fcc_featured_build_email_button_html($public_app_url, $is_english ? 'Open main FCC app' : 'Otvori glavnu FCC aplikaciju', 'secondary');
    }
    $actions_html = '<div style="margin:22px 0 10px;display:flex;flex-wrap:wrap;gap:10px;">' . implode('', array_filter($actions)) . '</div>';

    $subject = '';
    $body = '';
    $notification_title = '';
    $notification_description = '';

    switch($event_key) {
        case 'qualified_unlocked':
            $subject = $is_english ? 'You unlocked Featured FCC Apps' : 'Otključao si Istaknute FCC aplikacije';
            $notification_title = $subject;
            $notification_description = $profile_ready
                ? ($is_english
                    ? 'Your main FCC app is now above 15+ / 30d. Stay on Featured Apps and keep building toward 50+ / 30d.'
                    : 'Tvoja glavna FCC aplikacija sada je iznad 15+ / 30d. Ostani na Istaknutim aplikacijama i gradi dalje prema 50+ / 30d.')
                : ($is_english
                    ? 'Your main FCC app reached 15+ / 30d. Now finish the AI public profile so your public listing looks clear and strong.'
                    : 'Tvoja glavna FCC aplikacija došla je na 15+ / 30d. Sada dovrši AI javni profil kako bi tvoj javni prikaz izgledao jasno i ozbiljno.');
            $body = ($is_english ? 'Hello ' : 'Pozdrav ') . htmlspecialchars($name !== '' ? $name : ($is_english ? 'FCC collaborator' : 'FCC suradniče'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ', <br /><br />'
                . ($is_english
                    ? 'your main FCC app now holds <strong>' . nr($public_signal_30d) . '</strong> qualified public clicks and contacts in the last 30 days. That puts you into the first public FCC visibility layer: <strong>Featured Apps</strong>.'
                    : 'tvoja glavna FCC aplikacija sada drži <strong>' . nr($public_signal_30d) . '</strong> kvalificiranih javnih klikova i kontakata u zadnjih 30 dana. Time ulaziš u prvi javni FCC sloj vidljivosti: <strong>Istaknute aplikacije</strong>.')
                . '<br /><br />'
                . ($is_english
                    ? 'This is the first public proof that you are actively using FCC in practice. The next main public goal is <strong>50+ / 30d</strong>, because that is the simplest way for FCC to promote you as an active recommended sponsor on the homepage and through a public sponsor profile.'
                    : 'To je prvi javni dokaz da FCC koristiš aktivno u praksi. Sljedeći glavni javni cilj je <strong>50+ / 30d</strong>, jer je to najjednostavniji način da te FCC promovira kao aktivnog preporučenog sponzora na naslovnici i kroz javni sponsor profil.')
                . $status_card
                . '<p style="margin:0 0 12px;">' . $profile_line . '</p>'
                . '<p style="margin:0 0 12px;">' . $sales_link_line . '</p>'
                . '<p style="margin:0 0 12px;">'
                . ($is_english
                    ? 'Right now the best next move is simple: open your AI profile, refine how you use FCC, and make sure your public description explains who you help and how you work.'
                    : 'Sada je najbolji sljedeći potez jednostavan: otvori svoj AI profil, doradi kako koristiš FCC i pobrini se da javni opis jasno objasni kome pomažeš i kako radiš.')
                . '</p>'
                . $actions_html
                . '<p style="margin:18px 0 0;">'
                . ($is_english
                    ? 'You are currently missing <strong>' . nr($missing_to_top) . '</strong> to reach the 50+ / 30d sponsor level.'
                    : 'Trenutačno ti nedostaje još <strong>' . nr($missing_to_top) . '</strong> do 50+ / 30d sponsor razine.')
                . '</p><br />'
                . ($is_english ? 'Best regards,<br />{{WEBSITE_TITLE}}' : 'Lijep pozdrav,<br />{{WEBSITE_TITLE}}');
            break;

        case 'qualified_reminder':
            $subject = $is_english ? 'Finish your AI profile while you are on 15+' : 'Dovrši AI profil dok si na 15+';
            $notification_title = $is_english ? 'Finish your AI profile' : 'Dovrši AI profil';
            $notification_description = $is_english
                ? 'You are already on the 15+ / 30d public FCC layer. Finishing the AI profile is the cleanest next move for Featured Apps visibility.'
                : 'Već si na 15+ / 30d javnoj FCC razini. Dovršavanje AI profila je najčišći sljedeći potez za vidljivost na Istaknutim aplikacijama.';
            $body = ($is_english ? 'Hello ' : 'Pozdrav ') . htmlspecialchars($name !== '' ? $name : ($is_english ? 'FCC collaborator' : 'FCC suradniče'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ', <br /><br />'
                . ($is_english
                    ? 'you are still holding the <strong>15+ / 30d</strong> public FCC signal, which means your main app can stay in the Featured Apps layer.'
                    : 'još uvijek držiš <strong>15+ / 30d</strong> javni FCC signal, što znači da tvoja glavna aplikacija može ostati u sloju Istaknutih aplikacija.')
                . '<br /><br />'
                . ($is_english
                    ? 'The main thing still worth finishing is your AI profile for indexing. That profile helps your public FCC listing look calm, clear and relevant for both people and search systems.'
                    : 'Glavna stvar koju sada još vrijedi dovršiti je tvoj AI profil za indeksaciju. Taj profil pomaže da tvoj javni FCC prikaz izgleda mirno, jasno i relevantno i ljudima i tražilicama.')
                . $status_card
                . '<p style="margin:0 0 12px;">' . $profile_line . '</p>'
                . '<p style="margin:0 0 12px;">'
                . ($is_english
                    ? 'If you keep the profile unfinished, you still have the signal, but you are not using the full public value of it. A finished profile gives Featured Apps and future sponsor pages a much stronger public explanation of your work.'
                    : 'Ako profil ostane nedovršen, signal postoji, ali ne koristiš njegovu punu javnu vrijednost. Dovršen profil daje Istaknutim aplikacijama i budućim sponsor stranicama puno jače javno objašnjenje tvog rada.')
                . '</p>'
                . $actions_html
                . '<br />' . ($is_english ? 'Best regards,<br />{{WEBSITE_TITLE}}' : 'Lijep pozdrav,<br />{{WEBSITE_TITLE}}');
            break;

        case 'top_unlocked':
            $subject = $is_english ? 'You reached the 50+ sponsor signal' : 'Dosegao si 50+ sponsor signal';
            $notification_title = $is_english ? 'You reached the 50+ sponsor signal' : 'Dosegao si 50+ sponsor signal';
            $notification_description = $sponsor_visibility_ready
                ? ($is_english
                    ? 'FCC can now promote you more strongly as an active recommended sponsor. Check your sponsor profile and keep the rhythm healthy.'
                    : 'FCC te sada može jače promovirati kao aktivnog preporučenog sponzora. Provjeri sponsor profil i drži ritam zdravim.')
                : ($is_english
                    ? 'You reached 50+, but one more setup step is still needed before the full recommended sponsor layer works at full strength.'
                    : 'Dosegao si 50+, ali još je potreban jedan setup korak prije nego puni sloj preporučenih sponzora proradi punom snagom.');
            $body = ($is_english ? 'Hello ' : 'Pozdrav ') . htmlspecialchars($name !== '' ? $name : ($is_english ? 'FCC collaborator' : 'FCC suradniče'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ', <br /><br />'
                . ($is_english
                    ? 'your main FCC app has now reached <strong>' . nr($public_signal_30d) . '</strong> qualified public clicks and contacts in the last 30 days. The <strong>50+ / 30d</strong> level is the main public FCC sponsor signal.'
                    : 'tvoja glavna FCC aplikacija sada je došla do <strong>' . nr($public_signal_30d) . '</strong> kvalificiranih javnih klikova i kontakata u zadnjih 30 dana. Razina <strong>50+ / 30d</strong> je glavni javni FCC sponsor signal.')
                . '<br /><br />'
                . ($is_english
                    ? 'That matters because it is the clearest way for FCC to position you as an active recommended sponsor on the homepage and through a public sponsor profile. It also gives Google and AI systems a stronger public signal about your sponsor relevance and the way you use FCC in practice.'
                    : 'To je važno zato što je to najčišći način da te FCC pozicionira kao aktivnog preporučenog sponzora na naslovnici i kroz javni sponsor profil. Ujedno Googleu i AI sustavima daje jači javni signal o tvojoj sponsor relevantnosti i načinu na koji FCC koristiš u praksi.')
                . $status_card
                . '<p style="margin:0 0 12px;">' . $profile_line . '</p>'
                . '<p style="margin:0 0 12px;">' . $sales_link_line . '</p>'
                . '<p style="margin:0 0 12px;">'
                . ($sponsor_visibility_ready
                    ? ($is_english
                        ? 'You already have the key public pieces in place. Now the smart move is to keep your sponsor profile polished and protect the weekly rhythm so this public visibility stays strong.'
                        : 'Ključni javni dijelovi su ti već složeni. Sada je pametno držati sponsor profil urednim i čuvati tjedni ritam kako bi ta javna vidljivost ostala snažna.')
                    : ($is_english
                        ? 'You already have the result signal. Now finish the missing public setup piece so FCC can use that result more strongly on the sponsor layer.'
                        : 'Signal rezultata već postoji. Sada dovrši nedostajući javni setup korak kako bi FCC taj rezultat mogao jače koristiti na sponsor sloju.'))
                . '</p>'
                . $actions_html
                . '<br />' . ($is_english ? 'Best regards,<br />{{WEBSITE_TITLE}}' : 'Lijep pozdrav,<br />{{WEBSITE_TITLE}}');
            break;

        case 'top_reminder':
        default:
            $subject = $is_english ? 'Finish your sponsor setup for stronger visibility' : 'Dovrši sponsor setup za jaču vidljivost';
            $notification_title = $is_english ? 'Finish your sponsor setup' : 'Dovrši sponsor setup';
            $notification_description = !$sales_link_ready
                ? ($is_english
                    ? 'Your 50+ signal is already there, but the Forever sales link still needs to be fixed before the full sponsor layer works properly.'
                    : 'Tvoj 50+ signal već postoji, ali Forever prodajni link još treba doradu prije nego puni sponsor sloj radi kako treba.')
                : ($is_english
                    ? 'Your 50+ signal is live. Now finish the AI sponsor profile so the public presentation is clear and useful.'
                    : 'Tvoj 50+ signal je aktivan. Sada dovrši AI sponsor profil kako bi javni prikaz bio jasan i koristan.');
            $body = ($is_english ? 'Hello ' : 'Pozdrav ') . htmlspecialchars($name !== '' ? $name : ($is_english ? 'FCC collaborator' : 'FCC suradniče'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ', <br /><br />'
                . ($is_english
                    ? 'you are already in the <strong>50+ / 30d</strong> public FCC zone, which is the main layer for recommended sponsors.'
                    : 'već si u <strong>50+ / 30d</strong> javnoj FCC zoni, koja je glavni sloj za preporučene sponzore.')
                . '<br /><br />'
                . ($is_english
                    ? 'To get the strongest value from that signal, make sure the public sponsor setup is complete: valid Forever sales link, clear AI profile, and a sponsor page that explains your work calmly and clearly.'
                    : 'Kako bi iz tog signala izvukao najjaču vrijednost, pobrini se da je javni sponsor setup dovršen: valjani Forever prodajni link, jasan AI profil i sponsor stranica koja mirno i jasno objašnjava tvoj rad.')
                . $status_card
                . '<p style="margin:0 0 12px;">' . $profile_line . '</p>'
                . '<p style="margin:0 0 12px;">' . $sales_link_line . '</p>'
                . '<p style="margin:0 0 12px;">'
                . ($is_english
                    ? 'This is worth finishing because FCC can then use your signal more cleanly for homepage placement, sponsor profile visibility and stronger Google / AI understanding of your brand.'
                    : 'Ovo vrijedi dovršiti zato što FCC tada tvoj signal može čišće koristiti za pozicioniranje na naslovnici, vidljivost sponsor profila i jače Google / AI razumijevanje tvog branda.')
                . '</p>'
                . $actions_html
                . '<br />' . ($is_english ? 'Best regards,<br />{{WEBSITE_TITLE}}' : 'Lijep pozdrav,<br />{{WEBSITE_TITLE}}');
            break;
    }

    if($is_preview) {
        $subject = '[PREVIEW] ' . $subject;
    }

    return [
        'subject' => $subject,
        'body' => $body,
        'notification_title' => $notification_title,
        'notification_description' => $notification_description,
        'notification_url' => $notification_url,
    ];
}

function fcc_featured_send_signal_preview_emails(string $recipient_email, string $language = 'hr'): array {
    $recipient_email = trim($recipient_email);

    if($recipient_email === '' || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        throw new \InvalidArgumentException('Preview email adresa nije valjana.');
    }

    $language = fcc_ai_resolve_public_reply_language($language);
    $base_url = 'https://forevercard.club/';
    $preview_contexts = [
        'qualified_unlocked' => [
            'name' => 'Preview suradnik',
            'public_signal_30d' => 18,
            'public_signal_7d' => 6,
            'qualified_target' => 15,
            'top_target' => 50,
            'weekly_check_target' => 15,
            'generated_profile_complete' => false,
            'sales_link_ready' => true,
            'featured_apps_url' => $base_url . 'featured-apps',
            'recommended_sponsors_url' => $base_url . 'recommended-sponsors',
            'profile_edit_url' => $base_url . 'links?type=biolink&fcc_profile_modal=1',
            'sales_link_editor_url' => $base_url . 'link/123?tab=blocks',
            'public_app_url' => $base_url . 'preview-suradnik',
            'sponsor_profile_url' => $base_url . 'recommended-sponsors/123-preview-suradnik',
        ],
        'qualified_reminder' => [
            'name' => 'Preview suradnik',
            'public_signal_30d' => 24,
            'public_signal_7d' => 10,
            'qualified_target' => 15,
            'top_target' => 50,
            'weekly_check_target' => 15,
            'generated_profile_complete' => false,
            'sales_link_ready' => true,
            'featured_apps_url' => $base_url . 'featured-apps',
            'recommended_sponsors_url' => $base_url . 'recommended-sponsors',
            'profile_edit_url' => $base_url . 'links?type=biolink&fcc_profile_modal=1',
            'sales_link_editor_url' => $base_url . 'link/123?tab=blocks',
            'public_app_url' => $base_url . 'preview-suradnik',
            'sponsor_profile_url' => $base_url . 'recommended-sponsors/123-preview-suradnik',
        ],
        'top_unlocked' => [
            'name' => 'Preview suradnik',
            'public_signal_30d' => 58,
            'public_signal_7d' => 17,
            'qualified_target' => 15,
            'top_target' => 50,
            'weekly_check_target' => 15,
            'generated_profile_complete' => true,
            'sales_link_ready' => true,
            'featured_apps_url' => $base_url . 'featured-apps',
            'recommended_sponsors_url' => $base_url . 'recommended-sponsors',
            'profile_edit_url' => $base_url . 'links?type=biolink&fcc_profile_modal=1',
            'sales_link_editor_url' => $base_url . 'link/123?tab=blocks',
            'public_app_url' => $base_url . 'preview-suradnik',
            'sponsor_profile_url' => $base_url . 'recommended-sponsors/123-preview-suradnik',
        ],
        'top_reminder' => [
            'name' => 'Preview suradnik',
            'public_signal_30d' => 64,
            'public_signal_7d' => 11,
            'qualified_target' => 15,
            'top_target' => 50,
            'weekly_check_target' => 15,
            'generated_profile_complete' => false,
            'sales_link_ready' => false,
            'featured_apps_url' => $base_url . 'featured-apps',
            'recommended_sponsors_url' => $base_url . 'recommended-sponsors',
            'profile_edit_url' => $base_url . 'links?type=biolink&fcc_profile_modal=1',
            'sales_link_editor_url' => $base_url . 'link/123?tab=blocks',
            'public_app_url' => $base_url . 'preview-suradnik',
            'sponsor_profile_url' => $base_url . 'recommended-sponsors/123-preview-suradnik',
        ],
    ];

    $results = [];

    foreach($preview_contexts as $event_key => $context) {
        $message = fcc_featured_build_signal_email_message($event_key, $context, $language, ['is_preview' => true]);
        $transport_result = send_mail($recipient_email, $message['subject'], $message['body'], [
            'language' => $language,
            'return_transport_result' => true,
            'allow_local_send' => true,
        ]);

        $results[] = [
            'event_key' => $event_key,
            'subject' => $message['subject'],
            'success' => is_object($transport_result) ? !empty($transport_result->success) : (bool) $transport_result,
            'message_id' => is_object($transport_result) ? (string) ($transport_result->message_id ?? '') : '',
            'error' => is_object($transport_result) ? (string) ($transport_result->ErrorInfo ?? '') : '',
        ];
    }

    return $results;
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
        'version' => '2026-07-26-billing-grace-public-signal-v2',
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
                `users`.`extra`,
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
                'app_clicks_7d' => (int) ($signal_snapshot['app_clicks_7d'] ?? 0),
                'blog_clicks_7d' => (int) ($signal_snapshot['blog_clicks_7d'] ?? 0),
                'funnel_shop_clicks_7d' => (int) ($signal_snapshot['funnel_shop_clicks_7d'] ?? 0),
                'funnel_contacts_7d' => (int) ($signal_snapshot['funnel_contacts_7d'] ?? 0),
                'funnel_contact_signal_7d' => (int) ($signal_snapshot['funnel_contact_signal_7d'] ?? 0),
                'app_clicks_30d' => (int) ($signal_snapshot['app_clicks_30d'] ?? 0),
                'blog_clicks_30d' => (int) ($signal_snapshot['blog_clicks_30d'] ?? 0),
                'funnel_shop_clicks_30d' => (int) ($signal_snapshot['funnel_shop_clicks_30d'] ?? 0),
                'funnel_contacts_30d' => (int) ($signal_snapshot['funnel_contacts_30d'] ?? 0),
                'funnel_contact_signal_30d' => (int) ($signal_snapshot['funnel_contact_signal_30d'] ?? 0),
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
    }, 300);
}
