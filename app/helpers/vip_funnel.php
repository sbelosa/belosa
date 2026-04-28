<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 */

defined('ALTUMCODE') || die();

function vip_funnel_normalize_object($value): \stdClass {
    if(is_string($value)) {
        $value = json_decode($value ?? '{}');
    }

    if(is_array($value)) {
        $value = (object) $value;
    }

    if(!$value instanceof \stdClass) {
        $value = (object) $value;
    }

    return $value;
}

function vip_funnel_normalize_rollout_mode(?string $value): string {
    $available_modes = ['disabled_hidden', 'testing_visible_locked', 'enabled'];

    return in_array($value, $available_modes, true) ? $value : 'testing_visible_locked';
}

function vip_funnel_parse_user_ids($value): array {
    if(is_object($value)) {
        $value = json_decode(json_encode($value), true);
    }

    if(is_string($value)) {
        $value = preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
    }

    if(!is_array($value)) {
        $value = [];
    }

    $value = array_map(static function($user_id) {
        return (int) $user_id;
    }, $value);

    $value = array_values(array_unique(array_filter($value)));

    sort($value);

    return $value;
}

function vip_funnel_get_settings(): \stdClass {
    static $settings = null;

    if($settings !== null) {
        return $settings;
    }

    $defaults = [
        'rollout_mode' => 'testing_visible_locked',
        'visible_when_locked' => true,
        'show_sidebar_entry_when_locked' => true,
        'pilot_allowed_user_ids' => [],
        'default_demo_days' => 3,
        'demo_request_requires_approval' => true,
    ];

    $stored_settings = vip_funnel_normalize_object(settings()->vip_funnel ?? []);
    $settings_array = array_merge($defaults, json_decode(json_encode($stored_settings), true) ?? []);

    $settings = (object) $settings_array;
    $settings->rollout_mode = vip_funnel_normalize_rollout_mode((string) ($settings->rollout_mode ?? ''));
    $settings->visible_when_locked = (bool) ($settings->visible_when_locked ?? true);
    $settings->show_sidebar_entry_when_locked = (bool) ($settings->show_sidebar_entry_when_locked ?? true);
    $settings->pilot_allowed_user_ids = vip_funnel_parse_user_ids($settings->pilot_allowed_user_ids ?? []);
    $settings->default_demo_days = max(1, min(3, (int) ($settings->default_demo_days ?? 3)));
    $settings->demo_request_requires_approval = (bool) ($settings->demo_request_requires_approval ?? true);

    return $settings;
}

function vip_funnel_get_user_preferences($user = null): \stdClass {
    return vip_funnel_normalize_object($user->preferences ?? []);
}

function vip_funnel_get_countdown_weekday_options(): array {
    return [
        1 => 'Ponedjeljak',
        2 => 'Utorak',
        3 => 'Srijeda',
        4 => 'Četvrtak',
        5 => 'Petak',
        6 => 'Subota',
        0 => 'Nedjelja',
    ];
}

function vip_funnel_normalize_countdown_weekly_time(string $time = '20:00'): string {
    $time = trim($time);

    if(preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time)) {
        return $time;
    }

    return '20:00';
}

function vip_funnel_normalize_countdown_timezone(string $timezone = 'Europe/Zagreb'): string {
    $timezone = trim($timezone) ?: 'Europe/Zagreb';

    try {
        new \DateTimeZone($timezone);
        return $timezone;
    } catch(\Exception $exception) {
        return 'Europe/Zagreb';
    }
}

function vip_funnel_get_next_weekly_countdown_datetime(int $weekday = 4, string $time = '20:00', string $timezone = 'Europe/Zagreb'): string {
    $weekday = max(0, min(6, $weekday));
    $time = vip_funnel_normalize_countdown_weekly_time($time);
    $timezone = vip_funnel_normalize_countdown_timezone($timezone);

    [$hour, $minute] = array_map('intval', explode(':', $time));
    $tz = new \DateTimeZone($timezone);
    $now = new \DateTimeImmutable('now', $tz);
    $today_weekday = (int) $now->format('w');
    $days_ahead = ($weekday - $today_weekday + 7) % 7;
    $candidate = $now->setTime($hour, $minute, 0)->modify('+' . $days_ahead . ' days');

    if($candidate <= $now) {
        $candidate = $candidate->modify('+7 days');
    }

    return $candidate->format(\DateTimeInterface::ATOM);
}

function vip_funnel_user_has_plan_access($user = null): bool {
    if(!$user) {
        return false;
    }

    if(\Altum\Authentication::is_admin()) {
        return true;
    }

    $plan_settings = vip_funnel_normalize_object($user->plan_settings ?? []);

    return !empty($plan_settings->vip_funnel_core_is_enabled);
}

function vip_funnel_user_is_gate_exempt($user = null): bool {
    if(!$user) {
        return false;
    }

    if(\Altum\Authentication::is_admin()) {
        return true;
    }

    $user_id = (int) ($user->user_id ?? 0);
    $settings = vip_funnel_get_settings();

    if($user_id && in_array($user_id, $settings->pilot_allowed_user_ids, true)) {
        return true;
    }

    $preferences = vip_funnel_get_user_preferences($user);
    $meta = vip_funnel_normalize_object($preferences->meta ?? []);

    if(!empty($preferences->vip_funnel_gate_exempt) || !empty($meta->vip_funnel_gate_exempt)) {
        return true;
    }

    return false;
}

function vip_funnel_user_can_publish_public_hub($user = null): bool {
    if(!$user) {
        return false;
    }

    $access = vip_funnel_resolve_access_state($user);

    if(!$access->can_access) {
        return false;
    }

    if(vip_funnel_user_has_plan_access($user) || vip_funnel_user_is_gate_exempt($user)) {
        return true;
    }

    $preferences = vip_funnel_get_user_preferences($user);

    if(!empty($preferences->vip_funnel_studio)) {
        return true;
    }

    if(function_exists('vip_funnel_studio_schema_is_ready') && function_exists('vip_funnel_studio_get_primary_funnel_row')) {
        $user_id = (int) ($user->user_id ?? 0);

        if($user_id > 0 && vip_funnel_studio_schema_is_ready() && vip_funnel_studio_get_primary_funnel_row($user_id)) {
            return true;
        }
    }

    return false;
}

function vip_funnel_get_locked_copy(string $reason = 'testing'): \stdClass {
    $map = [
        'plan' => [
            'badge' => l('vip_funnel.locked.badge_plan'),
            'title' => l('vip_funnel.locked.title_plan'),
            'message' => l('vip_funnel.locked.message_plan'),
            'footnote' => l('vip_funnel.locked.footnote_plan'),
        ],
        'testing' => [
            'badge' => l('vip_funnel.locked.badge_testing'),
            'title' => l('vip_funnel.locked.title_testing'),
            'message' => l('vip_funnel.locked.message_testing'),
            'footnote' => l('vip_funnel.locked.footnote_testing'),
        ],
    ];

    $copy = $map[$reason] ?? $map['testing'];

    return (object) $copy;
}

function vip_funnel_resolve_access_state($user = null): \stdClass {
    $settings = vip_funnel_get_settings();
    $has_plan_access = vip_funnel_user_has_plan_access($user);
    $is_gate_exempt = vip_funnel_user_is_gate_exempt($user);

    $state = (object) [
        'state' => 'hidden',
        'can_access' => false,
        'show_sidebar_entry' => false,
        'locked_reason' => null,
        'has_plan_access' => $has_plan_access,
        'is_gate_exempt' => $is_gate_exempt,
        'rollout_mode' => $settings->rollout_mode,
        'settings' => $settings,
        'locked_copy' => vip_funnel_get_locked_copy('testing'),
    ];

    switch($settings->rollout_mode) {
        case 'enabled':
            if($has_plan_access || $is_gate_exempt) {
                $state->state = 'full_access';
                $state->can_access = true;
                $state->show_sidebar_entry = true;
            } elseif($settings->show_sidebar_entry_when_locked) {
                $state->state = 'plan_locked';
                $state->show_sidebar_entry = true;
                $state->locked_reason = 'plan';
                $state->locked_copy = vip_funnel_get_locked_copy('plan');
            }
            break;

        case 'disabled_hidden':
            if($is_gate_exempt) {
                $state->state = 'full_access';
                $state->can_access = true;
                $state->show_sidebar_entry = true;
            }
            break;

        case 'testing_visible_locked':
        default:
            if($is_gate_exempt) {
                $state->state = 'full_access';
                $state->can_access = true;
                $state->show_sidebar_entry = true;
            } elseif($settings->visible_when_locked || $settings->show_sidebar_entry_when_locked) {
                $state->state = 'testing_locked';
                $state->show_sidebar_entry = true;
                $state->locked_reason = 'testing';
                $state->locked_copy = vip_funnel_get_locked_copy('testing');
            }
            break;
    }

    return $state;
}

function vip_funnel_to_array($value): array {
    if(is_string($value)) {
        $value = json_decode($value ?? '{}', true);
    } elseif(is_object($value)) {
        $value = json_decode(json_encode($value), true);
    }

    return is_array($value) ? $value : [];
}

function vip_funnel_json_encode($value): string {
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
}

function vip_funnel_normalize_meta_pixel_id($value): string {
    $value = trim((string) $value);

    if($value === '') {
        return '';
    }

    if(preg_match("/fbq\\s*\\(\\s*['\"]init['\"]\\s*,\\s*['\"](\\d{5,})['\"]/i", $value, $matches)) {
        return $matches[1];
    }

    if(preg_match('/[?&]id=(\\d{5,})/i', $value, $matches)) {
        return $matches[1];
    }

    if(preg_match('/^\\d{5,}$/', $value)) {
        return $value;
    }

    if(preg_match('/\\b(\\d{5,})\\b/', $value, $matches)) {
        return $matches[1];
    }

    return '';
}

function vip_funnel_get_phase_definitions(): array {
    return [
        'entry' => [
            'key' => 'entry',
            'title' => l('vip_funnel.phase.entry'),
            'subtitle' => l('vip_funnel.board.phase.entry'),
        ],
        'segment' => [
            'key' => 'segment',
            'title' => l('vip_funnel.phase.segment'),
            'subtitle' => l('vip_funnel.board.phase.segment'),
        ],
        'experience' => [
            'key' => 'experience',
            'title' => l('vip_funnel.phase.experience'),
            'subtitle' => l('vip_funnel.board.phase.experience'),
        ],
        'trust' => [
            'key' => 'trust',
            'title' => l('vip_funnel.phase.trust'),
            'subtitle' => l('vip_funnel.board.phase.trust'),
        ],
        'conversion' => [
            'key' => 'conversion',
            'title' => l('vip_funnel.phase.conversion'),
            'subtitle' => l('vip_funnel.board.phase.conversion'),
        ],
    ];
}

function vip_funnel_get_step_status_options(): array {
    return [
        'core' => l('vip_funnel.board.status.core'),
        'test' => l('vip_funnel.board.status.test'),
        'proof' => l('vip_funnel.board.status.proof'),
        'conversion' => l('vip_funnel.board.status.conversion'),
    ];
}

function vip_funnel_get_card_type_options(): array {
    return [
        'offer' => 'Offer / Promise',
        'question' => 'Question / Segment',
        'demo' => 'Demo / Experience',
        'proof' => 'Proof / Trust',
        'cta' => 'CTA / Conversion',
        'follow_up' => 'Follow-up',
    ];
}

function vip_funnel_get_goal_options(): array {
    return [
        'offer' => 'Privuci pažnju',
        'question' => 'Usmjeri interes',
        'demo' => 'Pokaži doživljaj',
        'proof' => 'Dodaj dokaz',
        'cta' => 'Pozovi na odluku',
        'follow_up' => 'Nastavi follow-up',
    ];
}

function vip_funnel_get_visibility_options(): array {
    return [
        'all' => 'Vidljivo svima u tom putu',
        'qualified' => 'Samo kvalificirani leadovi',
        'manual' => 'Ručno puštanje',
    ];
}

function vip_funnel_get_design_variant_options(): array {
    return [
        'spotlight' => 'Spotlight hero',
        'stacked' => 'Stacked story',
        'card' => 'Card focus',
        'proof_strip' => 'Proof strip',
        'decision' => 'Decision CTA',
    ];
}

function vip_funnel_get_block_mode_options(): array {
    return [
        'message' => 'Tekstualni blok',
        'choice' => 'Pitanje s izborima',
        'video' => 'Video blok',
        'image' => 'Slikovni blok',
        'video_form' => 'Video + forma',
        'contact_form' => 'Kontakt forma',
        'product_offer' => 'Preporuka proizvoda',
    ];
}

function vip_funnel_get_page_block_type_options(): array {
    return [
        'headline' => 'Naslov / hero',
        'text' => 'Tekst',
        'image' => 'Slika',
        'video' => 'Video',
        'cta_group' => 'CTA gumbi',
        'survey' => 'Survey / izbor',
        'radio_survey' => 'Pitanje upitnika / radio',
        'countdown' => 'Counter / countdown',
        'name_field' => 'Ime',
        'full_name_field' => 'Ime + prezime',
        'email_field' => 'Email',
        'phone_field' => 'Telefon',
        'text_field' => 'Tekst polje',
        'proof_card' => 'Proof / povjerenje',
        'product_offer' => 'Preporuka proizvoda',
        'spacer' => 'Razmak',
    ];
}

function vip_funnel_get_image_upload_size_limit_mb(): float {
    $configured_limit = (float) (settings()->links->vip_funnel_image_size_limit ?? 3);

    if($configured_limit < 0) {
        $configured_limit = 3;
    }

    return min($configured_limit, (float) get_max_upload());
}

function vip_funnel_get_page_theme_width_options(): array {
    return [
        'narrow' => 'Usko',
        'regular' => 'Regularno',
        'wide' => 'Široko',
    ];
}

function vip_funnel_get_page_block_alignment_options(): array {
    return [
        'left' => 'Lijevo',
        'center' => 'Centar',
        'right' => 'Desno',
    ];
}

function vip_funnel_get_page_block_width_options(): array {
    return [
        'full' => 'Puna širina',
        'half' => '1/2 retka',
        'third' => '1/3 retka',
        'two_thirds' => '2/3 retka',
        'quarter' => '1/4 retka',
        'three_quarters' => '3/4 retka',
    ];
}

function vip_funnel_get_page_action_type_options(): array {
    return [
        'goto_step' => 'Idi na sljedeći korak',
        'submit_next' => 'Pošalji podatke pa idi dalje',
        'submit_stay' => 'Pošalji podatke i ostani ovdje',
        'external_url' => 'Otvori vanjski URL',
    ];
}

function vip_funnel_get_page_font_family_options(): array {
    return [
        'inherit' => 'Naslijedi / default',
        'modern_sans' => 'Modern Sans',
        'clean_ui' => 'Clean UI',
        'rounded' => 'Rounded Sans',
        'classic_serif' => 'Classic Serif',
        'condensed' => 'Condensed',
    ];
}

function vip_funnel_get_page_font_family_css_map(): array {
    return [
        'inherit' => 'inherit',
        'modern_sans' => '\'Avenir Next\', \'Segoe UI\', Helvetica, Arial, sans-serif',
        'clean_ui' => '\'Helvetica Neue\', Helvetica, Arial, sans-serif',
        'rounded' => '\'Trebuchet MS\', \'Segoe UI\', sans-serif',
        'classic_serif' => 'Georgia, \'Times New Roman\', serif',
        'condensed' => '\'Arial Narrow\', \'Helvetica Neue\', Arial, sans-serif',
    ];
}

function vip_funnel_get_page_font_weight_options(): array {
    return [
        '400' => 'Normal 400',
        '500' => 'Medium 500',
        '600' => 'Semibold 600',
        '700' => 'Bold 700',
        '800' => 'Extra bold 800',
        '900' => 'Black 900',
    ];
}

function vip_funnel_get_page_block_typography_defaults(string $type = 'headline'): array {
    $shared = [
        'font_family' => 'inherit',
        'badge_size' => 13,
        'badge_weight' => 800,
        'badge_color' => '',
        'title_size' => 28,
        'title_weight' => 800,
        'title_color' => '',
        'text_size' => 17,
        'text_weight' => 500,
        'body_color' => '',
        'field_size' => 16,
        'field_weight' => 500,
        'field_text_color' => '',
        'placeholder_color' => '',
        'button_size' => 17,
        'button_weight' => 800,
        'button_text_color' => '',
    ];

    $map = [
        'headline' => ['title_size' => 54, 'title_weight' => 900, 'text_size' => 20, 'text_weight' => 500, 'button_size' => 18, 'button_weight' => 900],
        'text' => ['title_size' => 24, 'title_weight' => 800, 'text_size' => 17, 'text_weight' => 500],
        'image' => ['title_size' => 28, 'title_weight' => 800, 'text_size' => 16, 'text_weight' => 500],
        'video' => ['title_size' => 28, 'title_weight' => 800, 'text_size' => 16, 'text_weight' => 500],
        'cta_group' => ['title_size' => 30, 'title_weight' => 900, 'text_size' => 16, 'text_weight' => 500, 'button_size' => 18, 'button_weight' => 900],
        'survey' => ['title_size' => 30, 'title_weight' => 900, 'text_size' => 16, 'text_weight' => 500, 'button_size' => 17, 'button_weight' => 800],
        'radio_survey' => ['title_size' => 30, 'title_weight' => 900, 'text_size' => 16, 'text_weight' => 500, 'button_size' => 17, 'button_weight' => 800],
        'countdown' => ['title_size' => 28, 'title_weight' => 900, 'text_size' => 16, 'text_weight' => 500],
        'name_field' => ['title_size' => 24, 'title_weight' => 900, 'text_size' => 16, 'text_weight' => 500, 'field_size' => 16, 'field_weight' => 500],
        'full_name_field' => ['title_size' => 24, 'title_weight' => 900, 'text_size' => 16, 'text_weight' => 500, 'field_size' => 16, 'field_weight' => 500],
        'email_field' => ['title_size' => 24, 'title_weight' => 900, 'text_size' => 16, 'text_weight' => 500, 'field_size' => 16, 'field_weight' => 500],
        'phone_field' => ['title_size' => 24, 'title_weight' => 900, 'text_size' => 16, 'text_weight' => 500, 'field_size' => 16, 'field_weight' => 500],
        'text_field' => ['title_size' => 24, 'title_weight' => 900, 'text_size' => 16, 'text_weight' => 500, 'field_size' => 16, 'field_weight' => 500],
        'proof_card' => ['title_size' => 26, 'title_weight' => 800, 'text_size' => 17, 'text_weight' => 500],
        'product_offer' => ['title_size' => 28, 'title_weight' => 900, 'text_size' => 17, 'text_weight' => 500, 'button_size' => 18, 'button_weight' => 900],
        'spacer' => ['title_size' => 28, 'title_weight' => 800, 'text_size' => 17, 'text_weight' => 500],
    ];

    return array_merge($shared, $map[$type] ?? []);
}

function vip_funnel_normalize_page_actions($value, string $kind = 'button', array $defaults = []): array {
    if(is_string($value)) {
        $decoded = json_decode($value, true);
        $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
    } elseif(is_object($value)) {
        $value = json_decode(json_encode($value), true);
    }

    if(!is_array($value)) {
        $value = [];
    }

    $normalized = [];

    foreach(array_slice(array_values($value), 0, 8) as $index => $item) {
        $item = vip_funnel_to_array($item);
        $default = vip_funnel_to_array($defaults[$index] ?? []);
        $label = trim(input_clean((string) ($item['label'] ?? ($default['label'] ?? '')), 90));

        if($label === '') {
            continue;
        }

        $style = input_clean((string) ($item['style'] ?? ($default['style'] ?? ($index === 0 ? 'primary' : 'secondary'))), 24);
        if(!in_array($style, ['primary', 'secondary', 'ghost'], true)) {
            $style = $index === 0 ? 'primary' : 'secondary';
        }

        $action = input_clean((string) ($item['action'] ?? ($default['action'] ?? 'goto_step')), 32);
        if(!array_key_exists($action, vip_funnel_get_page_action_type_options())) {
            $action = 'goto_step';
        }

        $normalized[] = [
            'id' => trim(input_clean((string) ($item['id'] ?? vip_funnel_generate_page_block_id($kind . '_action')), 120)),
            'label' => $label,
            'hint' => trim(strip_tags(mb_substr((string) ($item['hint'] ?? ($default['hint'] ?? '')), 0, 220))),
            'value' => trim(input_clean((string) ($item['value'] ?? ($default['value'] ?? $label)), 120)),
            'style' => $style,
            'action' => $action,
            'target_step_id' => trim(input_clean((string) ($item['target_step_id'] ?? ($default['target_step_id'] ?? $item['next_step_id'] ?? '')), 128)),
            'external_url' => trim(input_clean((string) ($item['external_url'] ?? ($default['external_url'] ?? '')), 2048)),
            'require_submit' => isset($item['require_submit']) ? (bool) $item['require_submit'] : (bool) ($default['require_submit'] ?? false),
        ];
    }

    return $normalized;
}

function vip_funnel_collect_surface_validation_errors(array $surface = [], string $page_role = 'landing', string $step_id = '', string $variant_key = 'a'): array {
    $surface = vip_funnel_normalize_page_surface_payload($surface);
    $errors = [];

    foreach((array) ($surface['blocks'] ?? []) as $block) {
        $block = vip_funnel_to_array($block);
        $block_id = (string) ($block['id'] ?? '');
        $block_type = (string) ($block['type'] ?? '');
        $actions = [];

        if($block_type === 'cta_group') {
            $actions = (array) ($block['buttons'] ?? []);
        }

        if(in_array($block_type, ['survey', 'radio_survey'], true)) {
            $actions = (array) ($block['options'] ?? []);
        }

        if($block_type === 'radio_survey' && empty($block['route_on_submit'])) {
            $actions = [];
        }

        foreach($actions as $action) {
            $action = vip_funnel_to_array($action);
            $action_type = (string) ($action['action'] ?? '');
            $external_url = trim((string) ($action['external_url'] ?? ''));

            if($action_type === 'external_url' && $external_url === '') {
                $errors[] = [
                    'code' => 'external_url_required',
                    'page_role' => $page_role,
                    'step_id' => $step_id,
                    'variant_key' => $variant_key,
                    'block_id' => $block_id,
                    'action_id' => (string) ($action['id'] ?? ''),
                    'field' => 'external_url',
                    'message' => l('vip_funnel.alert.validation_external_url_required'),
                ];
            }
        }
    }

    return $errors;
}

function vip_funnel_collect_payload_validation_errors(array $payload = []): array {
    $payload = vip_funnel_normalize_studio_payload($payload);
    $errors = [];

    $landing_surface = vip_funnel_to_array($payload['landing_page'] ?? []);
    $errors = array_merge($errors, vip_funnel_collect_surface_validation_errors($landing_surface, 'landing', '', 'a'));

    if(!empty($landing_surface['ab_enabled']) && !empty($landing_surface['variant_b_blocks'])) {
        $variant_b_surface = $landing_surface;
        $variant_b_surface['blocks'] = (array) ($landing_surface['variant_b_blocks'] ?? []);
        $errors = array_merge($errors, vip_funnel_collect_surface_validation_errors($variant_b_surface, 'landing', '', 'b'));
    }

    foreach((array) ($payload['board'] ?? []) as $phase) {
        $phase = vip_funnel_to_array($phase);

        foreach((array) ($phase['steps'] ?? []) as $step) {
            $step = vip_funnel_to_array($step);
            $step_id = (string) ($step['id'] ?? '');
            $surface = vip_funnel_to_array($step['page'] ?? []);

            $errors = array_merge($errors, vip_funnel_collect_surface_validation_errors($surface, 'step', $step_id, 'a'));

            if(!empty($surface['ab_enabled']) && !empty($surface['variant_b_blocks'])) {
                $variant_b_surface = $surface;
                $variant_b_surface['blocks'] = (array) ($surface['variant_b_blocks'] ?? []);
                $errors = array_merge($errors, vip_funnel_collect_surface_validation_errors($variant_b_surface, 'step', $step_id, 'b'));
            }
        }
    }

    return $errors;
}

function vip_funnel_get_page_block_defaults(string $type = 'headline'): array {
    $type_typography = vip_funnel_get_page_block_typography_defaults($type);
    $shared_defaults = [
        'layout_width' => 'full',
    ];
    $defaults = [
        'headline' => [
            'label' => 'Hero naslov',
            'badge' => 'Funnel 2.0',
            'title' => 'Pokreni svoj prvi korak bez kaosa',
            'text' => 'Jasan uvod koji odmah govori kome je ovo namijenjeno i zašto vrijedi pogledati dalje.',
            'alignment' => 'left',
        ],
        'text' => [
            'label' => 'Tekst blok',
            'title' => '',
            'text' => 'Dodaj kratko pojašnjenje, emociju, dokaz ili mirni prijelaz prema sljedećem koraku.',
            'alignment' => 'left',
        ],
        'image' => [
            'label' => 'Slikovni blok',
            'title' => '',
            'text' => 'Vizual koji pojačava doživljaj i jasnoću poruke.',
            'media_url' => '',
            'alignment' => 'center',
        ],
        'video' => [
            'label' => 'Video blok',
            'title' => '',
            'text' => 'Kratki video uvod s jednim logičnim sljedećim klikom.',
            'media_url' => '',
            'alignment' => 'center',
        ],
        'cta_group' => [
            'label' => 'CTA grupa',
            'title' => '',
            'text' => 'Odaberi sljedeći korak ili glavnu akciju.',
            'buttons' => [
                ['label' => 'Kreni dalje', 'target_step_id' => '', 'style' => 'primary', 'action' => 'goto_step', 'require_submit' => false],
            ],
            'require_capture' => false,
            'alignment' => 'center',
        ],
        'survey' => [
            'label' => 'Survey blok',
            'title' => 'Što te sada najviše zanima?',
            'text' => 'Odaberi smjer i funnel te vodi na idealan sljedeći korak.',
            'options' => [
                ['label' => 'Online posao', 'value' => 'online_posao', 'target_step_id' => '', 'style' => 'primary', 'action' => 'goto_step', 'require_submit' => false],
                ['label' => 'Proizvodi', 'value' => 'proizvodi', 'target_step_id' => '', 'style' => 'secondary', 'action' => 'goto_step', 'require_submit' => false],
                ['label' => 'Želim demo', 'value' => 'zelim_demo', 'target_step_id' => '', 'style' => 'ghost', 'action' => 'goto_step', 'require_submit' => false],
            ],
            'required' => false,
            'auto_advance' => true,
            'alignment' => 'left',
        ],
        'radio_survey' => [
            'label' => 'Pitanje upitnika',
            'title' => 'Koji odgovor najbolje opisuje tvoj cilj?',
            'text' => 'Odaberi jedan odgovor. Završni submit može koristiti ovaj odabir za pravi sljedeći korak.',
            'options' => [
                ['label' => 'Regulacija tjelesne težine', 'value' => 'regulacija_tjelesne_tezine', 'target_step_id' => '', 'style' => 'primary', 'action' => 'goto_step', 'require_submit' => false],
                ['label' => 'Detox', 'value' => 'detox', 'target_step_id' => '', 'style' => 'secondary', 'action' => 'goto_step', 'require_submit' => false],
                ['label' => 'Više energije', 'value' => 'vise_energije', 'target_step_id' => '', 'style' => 'ghost', 'action' => 'goto_step', 'require_submit' => false],
            ],
            'required' => false,
            'route_on_submit' => true,
            'alignment' => 'left',
        ],
        'countdown' => [
            'label' => 'Countdown',
            'title' => 'Ponuda istječe uskoro',
            'text' => 'Pojačaj hitnost pravim odbrojavanjem.',
            'countdown_mode' => 'fixed',
            'countdown_style' => 'cards',
            'fixed_datetime' => '',
            'duration_minutes' => 30,
            'duration_days' => 0,
            'countdown_weekly_day' => 4,
            'countdown_weekly_time' => '20:00',
            'countdown_timezone' => 'Europe/Zagreb',
            'countdown_show_days' => true,
            'countdown_show_hours' => true,
            'countdown_show_minutes' => true,
            'countdown_show_seconds' => true,
            'countdown_number_size' => 34,
            'countdown_number_color' => '',
            'completion_text' => 'Vrijeme je isteklo.',
            'alignment' => 'center',
        ],
        'name_field' => [
            'label' => 'Polje ime',
            'title' => 'Ime',
            'placeholder' => 'Upiši ime',
            'required' => false,
        ],
        'full_name_field' => [
            'label' => 'Polje ime + prezime',
            'title' => 'Ime i prezime',
            'placeholder' => 'Upiši ime i prezime',
            'required' => false,
        ],
        'email_field' => [
            'label' => 'Polje email',
            'title' => 'Email',
            'placeholder' => 'Upiši email',
            'required' => true,
        ],
        'phone_field' => [
            'label' => 'Polje telefon',
            'title' => 'Telefon',
            'placeholder' => 'Upiši broj telefona',
            'required' => false,
        ],
        'text_field' => [
            'label' => 'Tekst polje',
            'title' => 'Kratki odgovor',
            'placeholder' => 'Upiši odgovor',
            'field_key' => '',
            'required' => false,
        ],
        'proof_card' => [
            'label' => 'Proof blok',
            'badge' => 'Povjerenje',
            'title' => 'Zašto ovaj put djeluje jasno i sigurno',
            'text' => 'Dodaj mentorstvo, proof i sigurnost odluke.',
            'alignment' => 'left',
        ],
        'product_offer' => [
            'label' => 'Produktni blok',
            'badge' => 'Preporuka',
            'title' => 'Idealna preporuka za tvoj cilj',
            'text' => 'Jedan jasan paket, jedna glavna korist i jedan logičan CTA.',
            'product_source_mode' => 'manual',
            'product_blog_post_id' => 0,
            'product_translation_key' => '',
            'product_language_mode' => 'page',
            'product_language_code' => (string) \Altum\Language::$default_code,
            'product_fallback_language_code' => 'hr',
            'product_primary_mode' => 'blog_guide',
            'product_primary_cta_text' => 'Pogledaj vodič proizvoda',
            'product_secondary_enabled' => true,
            'product_secondary_mode' => 'direct_shop',
            'product_secondary_cta_text' => 'Idi na službeni shop',
            'product_mappings' => [],
            'alignment' => 'left',
        ],
        'spacer' => [
            'label' => 'Razmak',
            'spacing' => 'md',
        ],
    ];

    return array_merge($type_typography, $shared_defaults, $defaults[$type] ?? $defaults['headline']);
}

function vip_funnel_get_page_block_template_presets(): array {
    return [
        'landing_product' => [
            'label' => 'Landing: prodaja proizvoda',
            'description' => 'Gotov landing za proizvodni funnel s jasnim uvodom, proofom i CTA-om.',
            'blocks' => [
                ['type' => 'headline', 'badge' => 'Proizvodni put', 'title' => 'Pronađi idealan paket za svoj cilj', 'text' => 'Jasno vodi osobu prema najboljoj preporuci bez preopterećenja.'],
                ['type' => 'proof_card', 'title' => 'Jednostavan ulaz u pravi izbor', 'text' => 'Kratko objasni što će osoba dobiti i zašto je ovo logičan prvi korak.'],
                ['type' => 'survey', 'title' => 'Koji ti je sada glavni cilj?', 'text' => 'Odaberi cilj i sustav vodi na idealnu sljedeću preporuku.'],
            ],
        ],
        'landing_recruitment' => [
            'label' => 'Landing: regrutacija',
            'description' => 'Ulazna stranica za poslovnu priliku, interest capture i sljedeći pregled.',
            'blocks' => [
                ['type' => 'headline', 'badge' => 'Poslovna prilika', 'title' => 'Pokreni jasniji put prema online poslu', 'text' => 'Kratko predstavi sustav, jednostavan prvi korak i osjećaj sigurnog vodstva.'],
                ['type' => 'video', 'title' => 'Pogledaj kratki uvod', 'text' => 'Jedan video koji odmah stvara aha trenutak.'],
                ['type' => 'cta_group', 'title' => '', 'text' => 'Odaberi svoj sljedeći korak.'],
            ],
        ],
        'lead_capture_pdf' => [
            'label' => 'Lead magnet PDF',
            'description' => 'Jednostavna stranica za email unlock i automatski sljedeći korak.',
            'blocks' => [
                ['type' => 'headline', 'badge' => 'PDF vodič', 'title' => 'Ostavi email i preuzmi poseban vodič', 'text' => 'Prvo uhvati lead, a zatim vodi dalje na sljedeći funnel korak.'],
                ['type' => 'email_field', 'required' => true],
                ['type' => 'cta_group', 'buttons' => [['label' => 'Pošalji i otključaj', 'target_step_id' => '', 'style' => 'primary', 'action' => 'submit_next', 'require_submit' => true]], 'require_capture' => true],
            ],
        ],
        'survey_router' => [
            'label' => 'Survey router',
            'description' => 'Pitanje s grananjem koje osobu vodi na različite korake po odgovoru.',
            'blocks' => [
                ['type' => 'headline', 'badge' => 'Usmjeravanje', 'title' => 'Odaberi što ti sada najviše treba', 'text' => 'Svaki odgovor vodi na drugi idealni korak u funnelu.'],
                ['type' => 'survey', 'title' => 'Koji ti je prioritet?', 'auto_advance' => true],
            ],
        ],
    ];
}

function vip_funnel_get_default_page_surface_payload(string $name = ''): array {
    return [
        'name' => $name !== '' ? $name : 'Nova funnel stranica',
        'background_color' => '#0f172a',
        'surface_color' => '#152132',
        'text_color' => '#eef4ff',
        'accent_color' => '#67d8c9',
        'max_width' => 'wide',
        'show_progress' => false,
        'ab_enabled' => false,
        'ab_distribution' => 50,
        'blocks' => [],
        'variant_b_blocks' => [],
        'variant_b_settings' => [],
    ];
}

function vip_funnel_generate_page_block_id(string $type = 'block'): string {
    $suffix = substr(str_replace('.', '', uniqid('', true)), -8);

    return preg_replace('/[^a-z0-9_]+/i', '_', strtolower($type)) . '_' . $suffix;
}

function vip_funnel_get_video_embed_url(string $url): string {
    $url = trim($url);

    if($url === '') {
        return '';
    }

    if(preg_match('/^https?:\/\/(?:www\.)?youtube(?:-nocookie)?\.com\/embed\/([A-Za-z0-9_-]{6,})/i', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    if(preg_match('/^https?:\/\/player\.vimeo\.com\/video\/([0-9]+)(\?h=[A-Za-z0-9]+)?/i', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1] . (!empty($matches[2]) ? $matches[2] : '');
    }

    if(preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|watch\?.+&v=|embed\/|shorts\/|v\/))([A-Za-z0-9_-]{6,})/i', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    if(preg_match('/vimeo\.com\/(?:.*\/)?([0-9]+)(\?h=[A-Za-z0-9]+)?/i', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1] . (!empty($matches[2]) ? $matches[2] : '');
    }

    return '';
}

function vip_funnel_is_direct_video_file_url(string $url): bool {
    $path = (string) parse_url(trim($url), PHP_URL_PATH);
    $extension = mb_strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

    return in_array($extension, ['mp4', 'webm', 'ogg', 'mov', 'm4v'], true);
}

function vip_funnel_get_product_target_mode_options(): array {
    return [
        'blog_guide' => 'Vodi na blog vodič',
        'direct_shop' => 'Vodi direktno na službeni shop',
    ];
}

function vip_funnel_get_product_source_mode_options(): array {
    return [
        'manual' => 'Koristi zadani proizvod',
        'dynamic' => 'Prilagodi proizvod prema odgovoru iz funnela',
    ];
}

function vip_funnel_get_product_language_mode_options(): array {
    return [
        'page' => 'Prati jezik stranice',
        'manual' => 'Koristi ručno odabrani jezik',
    ];
}

function vip_funnel_get_product_language_codes(): array {
    $language_codes = array_values(array_unique(array_filter(array_values((array) \Altum\Language::$active_languages))));

    if(!in_array((string) \Altum\Language::$default_code, $language_codes, true)) {
        $language_codes[] = (string) \Altum\Language::$default_code;
    }

    return array_values($language_codes);
}

function vip_funnel_get_product_catalog(string $preferred_language_code = ''): array {
    static $cache = [];

    $preferred_language_code = trim($preferred_language_code) !== '' ? trim($preferred_language_code) : (string) \Altum\Language::$default_code;

    if(array_key_exists($preferred_language_code, $cache)) {
        return $cache[$preferred_language_code];
    }

    $blog_products = [];
    $blog_products_result = db()
        ->where('is_published', 1)
        ->orderBy('datetime', 'DESC')
        ->get('blog_posts', 500, ['blog_post_id', 'title', 'description', 'url', 'image', 'language', 'webshop_links']);
    $blog_products_index = [];

    foreach((array) $blog_products_result as $blog_post) {
        $translation_key = trim((string) ($blog_post->url ?? ''));

        if($translation_key === '') {
            continue;
        }

        $webshop_links = json_decode($blog_post->webshop_links ?? '{}');
        if(empty(array_filter((array) $webshop_links, static function($value) {
            return !empty($value);
        }))) {
            continue;
        }

        $language_code = null;
        if(!empty($blog_post->language) && isset(\Altum\Language::$active_languages[$blog_post->language])) {
            $language_code = \Altum\Language::$active_languages[$blog_post->language];
        }

        $language_prefix = $language_code ? $language_code . '/' : '';
        $product_row = [
            'blog_post_id' => (int) $blog_post->blog_post_id,
            'title' => (string) ($blog_post->title ?? ''),
            'description' => mb_substr(trim(strip_tags((string) ($blog_post->description ?? ''))), 0, 220),
            'blog_url' => SITE_URL . $language_prefix . 'blog/' . $translation_key,
            'image_url' => !empty($blog_post->image) ? \Altum\Uploads::get_full_url('blog') . $blog_post->image : null,
            'translation_key' => $translation_key,
            'language_code' => $language_code ?: (string) \Altum\Language::$default_code,
        ];

        if(!isset($blog_products_index[$translation_key])) {
            $blog_products_index[$translation_key] = [
                'rows' => [],
                'ordered_language_codes' => [],
            ];
        }

        $blog_products_index[$translation_key]['rows'][$product_row['language_code']] = $product_row;

        if(!in_array($product_row['language_code'], $blog_products_index[$translation_key]['ordered_language_codes'], true)) {
            $blog_products_index[$translation_key]['ordered_language_codes'][] = $product_row['language_code'];
        }
    }

    foreach($blog_products_index as $translation_key => $product_group) {
        $preferred_product = $product_group['rows'][$preferred_language_code]
            ?? $product_group['rows']['hr']
            ?? $product_group['rows']['en']
            ?? reset($product_group['rows']);

        if(!$preferred_product) {
            continue;
        }

        $preferred_product['translation_key'] = $translation_key;
        $preferred_product['available_language_codes'] = array_values($product_group['ordered_language_codes']);
        $preferred_product['available_languages_label'] = implode(' / ', array_map(static function($code) {
            return mb_strtoupper((string) $code);
        }, $preferred_product['available_language_codes']));

        $blog_products[] = $preferred_product;
    }

    usort($blog_products, static function(array $a, array $b) {
        return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
    });

    return $cache[$preferred_language_code] = array_values($blog_products);
}

function vip_funnel_get_product_translation_key_by_blog_post_id(int $blog_post_id = 0): string {
    static $cache = [];

    if($blog_post_id <= 0) {
        return '';
    }

    if(isset($cache[$blog_post_id])) {
        return $cache[$blog_post_id];
    }

    $blog_post = db()
        ->where('blog_post_id', $blog_post_id)
        ->getOne('blog_posts', ['url']);

    return $cache[$blog_post_id] = trim((string) ($blog_post->url ?? ''));
}

function vip_funnel_resolve_product_catalog_entry(string $translation_key, string $target_language_code = '', string $fallback_language_code = ''): ?array {
    static $cache = [];

    $translation_key = trim($translation_key);
    $target_language_code = trim($target_language_code);
    $fallback_language_code = trim($fallback_language_code);

    if($translation_key === '') {
        return null;
    }

    $cache_key = $translation_key . '|' . $target_language_code . '|' . $fallback_language_code;
    if(array_key_exists($cache_key, $cache)) {
        return $cache[$cache_key];
    }

    $language_names_by_code = array_flip((array) \Altum\Language::$active_languages);
    $language_priority = array_values(array_filter(array_unique([
        $target_language_code,
        $fallback_language_code,
    ])));

    foreach($language_priority as $language_code) {
        $language_name = $language_names_by_code[$language_code] ?? null;

        if(!$language_name) {
            continue;
        }

        $blog_post = db()
            ->where('is_published', 1)
            ->where('url', $translation_key)
            ->where('language', $language_name)
            ->getOne('blog_posts', ['blog_post_id', 'title', 'description', 'url', 'image', 'language', 'webshop_links']);

        if(!$blog_post) {
            continue;
        }

        $webshop_links = json_decode($blog_post->webshop_links ?? '{}');
        if(empty(array_filter((array) $webshop_links, static function($value) {
            return !empty($value);
        }))) {
            continue;
        }

        $language_prefix = !empty($blog_post->language) && isset(\Altum\Language::$active_languages[$blog_post->language])
            ? \Altum\Language::$active_languages[$blog_post->language] . '/'
            : '';

        return $cache[$cache_key] = [
            'blog_post_id' => (int) ($blog_post->blog_post_id ?? 0),
            'title' => (string) ($blog_post->title ?? ''),
            'description' => mb_substr(trim(strip_tags((string) ($blog_post->description ?? ''))), 0, 220),
            'blog_url' => SITE_URL . $language_prefix . 'blog/' . ($blog_post->url ?? ''),
            'image_url' => !empty($blog_post->image) ? \Altum\Uploads::get_full_url('blog') . $blog_post->image : null,
            'language_code' => $language_code,
            'translation_key' => $translation_key,
        ];
    }

    $fallback_post = db()
        ->where('is_published', 1)
        ->where('url', $translation_key)
        ->getOne('blog_posts', ['blog_post_id', 'title', 'description', 'url', 'image', 'language', 'webshop_links']);

    if(!$fallback_post) {
        return $cache[$cache_key] = null;
    }

    $webshop_links = json_decode($fallback_post->webshop_links ?? '{}');
    if(empty(array_filter((array) $webshop_links, static function($value) {
        return !empty($value);
    }))) {
        return $cache[$cache_key] = null;
    }

    $resolved_language_code = !empty($fallback_post->language) && isset(\Altum\Language::$active_languages[$fallback_post->language])
        ? \Altum\Language::$active_languages[$fallback_post->language]
        : (string) \Altum\Language::$default_code;
    $language_prefix = !empty($fallback_post->language) && isset(\Altum\Language::$active_languages[$fallback_post->language])
        ? \Altum\Language::$active_languages[$fallback_post->language] . '/'
        : '';

    return $cache[$cache_key] = [
        'blog_post_id' => (int) ($fallback_post->blog_post_id ?? 0),
        'title' => (string) ($fallback_post->title ?? ''),
        'description' => mb_substr(trim(strip_tags((string) ($fallback_post->description ?? ''))), 0, 220),
        'blog_url' => SITE_URL . $language_prefix . 'blog/' . ($fallback_post->url ?? ''),
        'image_url' => !empty($fallback_post->image) ? \Altum\Uploads::get_full_url('blog') . $fallback_post->image : null,
        'language_code' => $resolved_language_code,
        'translation_key' => $translation_key,
    ];
}

function vip_funnel_get_user_referral_slug(int $user_id = 0): string {
    static $cache = [];

    if($user_id <= 0) {
        return '';
    }

    if(isset($cache[$user_id])) {
        return $cache[$user_id];
    }

    $main_biolink_id = function_exists('fc_get_user_main_biolink_id') ? (int) fc_get_user_main_biolink_id($user_id) : 0;

    $query = db()
        ->where('user_id', $user_id)
        ->where('type', 'biolink');

    if($main_biolink_id > 0) {
        $query->where('link_id', $main_biolink_id);
    }

    $biolink = $query->getOne('links', ['link_id', 'url']);

    if(!$biolink || empty($biolink->url)) {
        $biolink = db()
            ->where('user_id', $user_id)
            ->where('type', 'biolink')
            ->orderBy('link_id', 'ASC')
            ->getOne('links', ['link_id', 'url']);
    }

    return $cache[$user_id] = trim((string) ($biolink->url ?? ''));
}

function vip_funnel_get_owner_contact_profile($user = null): array {
    if(is_numeric($user)) {
        $user_id = (int) $user;
        $user = $user_id > 0 ? db()->where('user_id', $user_id)->getOne('users', ['user_id', 'name', 'email', 'billing', 'preferences', 'referral_key']) : null;
    }

    $user_id = (int) ($user->user_id ?? 0);
    if($user_id <= 0) {
        return [
            'user_id' => 0,
            'name' => '',
            'email' => '',
            'phone' => '',
            'whatsapp_url' => '',
            'referral_slug' => '',
            'referral_key' => '',
            'main_biolink_url' => '',
        ];
    }

    $preferences = vip_funnel_get_user_preferences($user);
    $meta = vip_funnel_normalize_object($preferences->meta ?? []);
    $billing = vip_funnel_to_array($user->billing ?? []);
    $phone = trim((string) ($meta->phone ?? ($meta->whatsapp ?? ($billing['phone'] ?? ''))));
    $phone_direct = preg_replace('/[^0-9\+]/', '', $phone);
    $phone_digits = preg_replace('/\D+/', '', $phone_direct);
    $message = (\Altum\Language::$code ?? 'hr') === 'hr'
        ? 'Pozdrav, želim nastaviti oko FCC demo pristupa.'
        : 'Hi, I want to continue with my FCC demo access.';

    return [
        'user_id' => $user_id,
        'name' => trim((string) ($user->name ?? '')),
        'email' => trim((string) ($user->email ?? '')),
        'phone' => $phone_direct,
        'whatsapp_url' => $phone_digits !== '' ? 'https://wa.me/' . $phone_digits . '?text=' . rawurlencode($message) : '',
        'referral_slug' => vip_funnel_get_user_referral_slug($user_id),
        'referral_key' => trim((string) ($user->referral_key ?? '')),
        'main_biolink_url' => function_exists('fc_get_user_main_biolink_url') ? fc_get_user_main_biolink_url($user_id) : '',
    ];
}

function vip_funnel_get_forever_business_referral_action_token(): string {
    return '{{forever_business_referral_url}}';
}

function vip_funnel_is_forever_business_referral_action_token(string $url = ''): bool {
    $url = trim($url);

    return $url !== '' && in_array($url, [
        vip_funnel_get_forever_business_referral_action_token(),
        'fcc-dynamic:forever-business-referral',
        'fcc://forever-business-referral',
    ], true);
}

function vip_funnel_get_geo_lookup_ip(): ?string {
    $geo_lookup_ip = function_exists('get_ip') ? get_ip() : null;

    if(
        !$geo_lookup_ip
        || !filter_var($geo_lookup_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
    ) {
        foreach(['HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED', 'HTTP_CLIENT_IP'] as $ip_header_key) {
            if(empty($_SERVER[$ip_header_key])) {
                continue;
            }

            $ip_candidates = array_map('trim', explode(',', (string) $_SERVER[$ip_header_key]));

            if($ip_header_key === 'HTTP_FORWARDED') {
                $ip_candidates = [];

                if(preg_match_all('/for="?\[?([a-fA-F0-9:\.]+)\]?"?/i', (string) $_SERVER[$ip_header_key], $forwarded_matches)) {
                    $ip_candidates = array_map('trim', $forwarded_matches[1] ?? []);
                }
            }

            foreach($ip_candidates as $ip_candidate) {
                if(filter_var($ip_candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip_candidate;
                }
            }
        }
    }

    return $geo_lookup_ip && filter_var($geo_lookup_ip, FILTER_VALIDATE_IP) ? $geo_lookup_ip : null;
}

function vip_funnel_get_forever_business_referral_url(int $owner_user_id = 0): string {
    if($owner_user_id <= 0) {
        return '';
    }

    $owner = db()
        ->where('user_id', $owner_user_id)
        ->where('status', 1)
        ->getOne('users', ['user_id', 'preferences']);

    if(!$owner) {
        return '';
    }

    $preferences = vip_funnel_get_user_preferences($owner);
    $meta = vip_funnel_normalize_object($preferences->meta ?? []);
    $forever_id = trim((string) ($meta->foreverId ?? ''));
    $business_links = settings()->links->forever_business_links ?? new \StdClass();
    $available_country_codes = array_keys(array_filter((array) $business_links, static function($value) {
        return !empty($value);
    }));

    if(empty($available_country_codes)) {
        return '';
    }

    $accept_language_header = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null;
    $country_code = \Altum\Link::get_trusted_forever_request_country_code();
    $country_code_is_trusted = (bool) $country_code;

    if(!$country_code) {
        $country_code = \Altum\Link::get_external_geo_country_code(vip_funnel_get_geo_lookup_ip());
    }

    if(!$country_code && vip_funnel_get_geo_lookup_ip() && is_file(APP_PATH . 'includes/GeoLite2-City.mmdb')) {
        try {
            $maxmind = (new \MaxMind\Db\Reader(APP_PATH . 'includes/GeoLite2-City.mmdb'))->get(vip_funnel_get_geo_lookup_ip());
            $country_code = $maxmind['country']['iso_code'] ?? null;
        } catch(\Exception $exception) {
            $country_code = null;
        }
    }

    $business_country_code = \Altum\Link::resolve_preferred_forever_market_country_code(
        $country_code,
        $available_country_codes,
        $accept_language_header,
        $country_code_is_trusted
    );

    $business_base_url = $business_country_code ? ($business_links->{$business_country_code} ?? null) : null;

    if(!$business_base_url && !empty($business_links->us)) {
        $business_country_code = 'us';
        $business_base_url = $business_links->us;
    }

    if(!$business_base_url && !empty($business_links->gb)) {
        $business_country_code = 'gb';
        $business_base_url = $business_links->gb;
    }

    if(!$business_base_url) {
        $business_country_code = (string) ($available_country_codes[0] ?? '');
        $business_base_url = $business_country_code !== '' ? ($business_links->{$business_country_code} ?? null) : null;
    }

    $url = \Altum\Link::build_forever_destination_url($business_base_url, $forever_id, $business_country_code);

    return is_string($url) ? $url : '';
}

function vip_funnel_get_start_package_question_message(string $language = 'hr'): string {
    $is_en = vip_funnel_resolve_import_template_language($language) === 'en';

    return $is_en
        ? 'Hi, I have a few additional questions about FCC collaboration and the Start Your Journey package. Please contact me.'
        : 'Pozdrav, imam dodatnih pitanja vezano za FCC suradnju i Start Your Journey paket. Molim vas da me kontaktirate.';
}

function vip_funnel_build_whatsapp_url_from_phone(string $phone = '', string $message = ''): string {
    $phone_digits = preg_replace('/\D+/', '', $phone);

    if($phone_digits === '') {
        return '';
    }

    return 'https://wa.me/' . $phone_digits . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

function vip_funnel_rewrite_whatsapp_url_message(string $url = '', string $message = ''): string {
    $url = trim($url);
    $message = trim($message);

    if($url === '' || $message === '') {
        return $url;
    }

    $parsed_url = parse_url($url);
    $host = mb_strtolower((string) ($parsed_url['host'] ?? ''));
    $path = trim((string) ($parsed_url['path'] ?? ''), '/');

    if($host === 'wa.me' && $path !== '') {
        return 'https://wa.me/' . preg_replace('/\D+/', '', $path) . '?text=' . rawurlencode($message);
    }

    if(in_array($host, ['api.whatsapp.com', 'web.whatsapp.com', 'www.whatsapp.com', 'whatsapp.com'], true)) {
        $query = [];

        if(!empty($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query);
        }

        $phone = preg_replace('/\D+/', '', (string) ($query['phone'] ?? ''));

        if($phone !== '') {
            return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
        }
    }

    return $url;
}

function vip_funnel_get_start_package_question_whatsapp_url($user = null, string $language = 'hr'): string {
    $owner_profile = vip_funnel_get_owner_contact_profile($user);
    $message = vip_funnel_get_start_package_question_message($language);
    $whatsapp_url = vip_funnel_rewrite_whatsapp_url_message((string) ($owner_profile['whatsapp_url'] ?? ''), $message);

    if($whatsapp_url !== '') {
        return $whatsapp_url;
    }

    return vip_funnel_build_whatsapp_url_from_phone((string) ($owner_profile['phone'] ?? ''), $message);
}

function vip_funnel_apply_owner_referral_cookies(int $owner_user_id = 0): void {
    if($owner_user_id <= 0) {
        return;
    }

    $owner = db()->where('user_id', $owner_user_id)->where('status', 1)->getOne('users', ['user_id', 'referral_key']);
    if(!$owner) {
        return;
    }

    $referral_slug = vip_funnel_get_user_referral_slug($owner_user_id);
    $expires = time() + 60 * 60 * 24 * 365;

    if($referral_slug !== '') {
        setcookie('referral', $referral_slug, $expires, '/');
        $_COOKIE['referral'] = $referral_slug;
    }

    $referral_key = trim((string) ($owner->referral_key ?? ''));
    if($referral_key !== '') {
        setcookie('referred_by', $referral_key, $expires, COOKIE_PATH);
        $_COOKIE['referred_by'] = $referral_key;
    }
}

function vip_funnel_send_owner_email_notification(int $owner_user_id = 0, string $event_key = '', array $context = []): array {
    if($owner_user_id <= 0 || trim($event_key) === '') {
        return ['attempted' => false, 'sent' => false];
    }

    $owner_profile = vip_funnel_get_owner_contact_profile($owner_user_id);
    $owner_email = trim((string) ($owner_profile['email'] ?? ''));

    if($owner_email === '' || !filter_var($owner_email, FILTER_VALIDATE_EMAIL)) {
        return ['attempted' => false, 'sent' => false];
    }

    $lead_name = trim((string) ($context['lead_name'] ?? 'Novi kontakt'));
    $lead_email = trim((string) ($context['lead_email'] ?? ''));
    $lead_phone = trim((string) ($context['lead_phone'] ?? ''));
    $funnel_name = trim((string) ($context['funnel_name'] ?? 'VIP Funnel 2.0'));
    $page_url = trim((string) ($context['page_url'] ?? ''));
    $source_label = trim((string) ($context['source_label'] ?? $funnel_name));
    $dashboard_url = trim((string) ($context['dashboard_url'] ?? url('vip-funnel-studio')));

    $subjects = [
        'lead_created' => 'Novi kontakt iz tvog VIP Funnel-a',
        'demo_requested' => 'Novi zahtjev za VIP demo pristup',
        'demo_activated' => 'VIP demo pristup je aktiviran',
    ];
    $subject = $subjects[$event_key] ?? 'VIP Funnel obavijest';

    $intro = match($event_key) {
        'demo_requested' => 'Netko je upravo zatražio VIP demo pristup kroz tvoj funnel.',
        'demo_activated' => 'VIP demo pristup je aktiviran i korisniku su poslani pristupni podaci.',
        default => 'Imaš novi kontakt kroz svoj VIP Funnel.',
    };

    $body_lines = [
        '<p>Pozdrav {{OWNER_NAME}},</p>',
        '<p>{{INTRO}}</p>',
        '<p><strong>Kontakt:</strong><br>{{LEAD_NAME}}{{LEAD_EMAIL_LINE}}{{LEAD_PHONE_LINE}}</p>',
        '<p><strong>Izvor:</strong><br>{{SOURCE_LABEL}}</p>',
    ];

    if($page_url !== '') {
        $body_lines[] = '<p><strong>Stranica:</strong><br><a href="{{PAGE_URL}}" target="_blank" rel="noopener noreferrer">{{PAGE_URL}}</a></p>';
    }

    $body_lines[] = '<p><a href="{{DASHBOARD_URL}}" target="_blank" rel="noopener noreferrer">Otvori VIP Funnel pregled</a></p>';

    $email_template = get_email_template(
        ['{{NAME}}' => trim((string) ($owner_profile['name'] ?? '')) ?: 'FCC mentor'],
        $subject,
        [
            '{{OWNER_NAME}}' => htmlspecialchars(trim((string) ($owner_profile['name'] ?? '')) ?: 'FCC mentor', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{INTRO}}' => htmlspecialchars($intro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{LEAD_NAME}}' => htmlspecialchars($lead_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{LEAD_EMAIL_LINE}}' => $lead_email !== '' ? '<br>' . htmlspecialchars($lead_email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '',
            '{{LEAD_PHONE_LINE}}' => $lead_phone !== '' ? '<br>' . htmlspecialchars($lead_phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '',
            '{{SOURCE_LABEL}}' => htmlspecialchars($source_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{PAGE_URL}}' => htmlspecialchars($page_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{DASHBOARD_URL}}' => htmlspecialchars($dashboard_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ],
        implode('', $body_lines)
    );

    $transport_result = send_mail($owner_email, $email_template->subject, $email_template->body, [
        'return_transport_result' => true,
        'is_system_email' => true,
        'language' => settings()->main->default_language,
    ]);

    return [
        'attempted' => true,
        'sent' => !empty($transport_result->success),
        'transport_result' => $transport_result,
    ];
}

function vip_funnel_append_url_query_param(string $url, string $key, string $value): string {
    $url = trim($url);
    $key = trim($key);

    if($url === '' || $key === '' || $value === '') {
        return $url;
    }

    return $url . (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . http_build_query([$key => $value]);
}

function vip_funnel_resolve_product_offer_render_data(array $block, int $owner_user_id = 0, string $page_language_code = '', array $runtime_context = []): array {
    $block = vip_funnel_normalize_page_block_payload($block);
    $page_language_code = trim($page_language_code) !== '' ? trim($page_language_code) : (string) \Altum\Language::$code;
    $runtime_context = vip_funnel_to_array($runtime_context);
    $language_mode = in_array((string) ($block['product_language_mode'] ?? 'page'), ['page', 'manual'], true) ? (string) ($block['product_language_mode'] ?? 'page') : 'page';
    $target_language_code = $language_mode === 'manual'
        ? (string) ($block['product_language_code'] ?? (string) \Altum\Language::$default_code)
        : ($page_language_code !== '' ? $page_language_code : (string) \Altum\Language::$default_code);
    $fallback_language_code = (string) ($block['product_fallback_language_code'] ?? 'hr');
    $translation_key = trim((string) ($block['product_translation_key'] ?? ''));
    $matched_mapping = null;

    if((string) ($block['product_source_mode'] ?? 'manual') === 'dynamic') {
        $matched_mapping = vip_funnel_find_product_mapping_match($block, $runtime_context);
        if($matched_mapping && !empty($matched_mapping['product_translation_key'])) {
            $translation_key = (string) $matched_mapping['product_translation_key'];
        }
    }

    if($translation_key === '' && !empty($block['product_blog_post_id'])) {
        $translation_key = vip_funnel_get_product_translation_key_by_blog_post_id((int) $block['product_blog_post_id']);
    }

    $resolved_product = $translation_key !== ''
        ? vip_funnel_resolve_product_catalog_entry($translation_key, $target_language_code, $fallback_language_code)
        : null;
    $referral_slug = vip_funnel_get_user_referral_slug($owner_user_id);
    $blog_url = '';
    $direct_shop_url = '';

    if($resolved_product) {
        $blog_url = vip_funnel_append_url_query_param((string) ($resolved_product['blog_url'] ?? ''), 'ref', $referral_slug);

        if($referral_slug !== '') {
            $direct_shop_url = url('blog-click?blog_post_id=' . (int) ($resolved_product['blog_post_id'] ?? 0) . '&ref=' . rawurlencode($referral_slug));
        }
    }

    $target_mode_options = vip_funnel_get_product_target_mode_options();
    $primary_mode = array_key_exists((string) ($block['product_primary_mode'] ?? ''), $target_mode_options)
        ? (string) $block['product_primary_mode']
        : 'blog_guide';
    $secondary_mode = array_key_exists((string) ($block['product_secondary_mode'] ?? ''), $target_mode_options)
        ? (string) $block['product_secondary_mode']
        : ($primary_mode === 'blog_guide' ? 'direct_shop' : 'blog_guide');
    $primary_url = $primary_mode === 'direct_shop'
        ? ($direct_shop_url !== '' ? $direct_shop_url : $blog_url)
        : $blog_url;
    $secondary_url = $secondary_mode === 'direct_shop'
        ? ($direct_shop_url !== '' ? $direct_shop_url : $blog_url)
        : $blog_url;

    $block['product_translation_key'] = $translation_key;
    $block['product_resolved'] = $resolved_product;
    $block['product_referral_slug'] = $referral_slug;
    $block['product_blog_url'] = $blog_url;
    $block['product_direct_shop_url'] = $direct_shop_url;
    $block['product_primary_mode'] = $primary_mode;
    $block['product_secondary_mode'] = $secondary_mode;
    $block['product_primary_url'] = $primary_url;
    $block['product_secondary_url'] = !empty($block['product_secondary_enabled']) ? $secondary_url : '';
    $block['product_primary_mode_label'] = $target_mode_options[$primary_mode] ?? 'Vodi na blog vodič';
    $block['product_secondary_mode_label'] = $target_mode_options[$secondary_mode] ?? 'Vodi direktno na službeni shop';
    $block['product_runtime_values'] = vip_funnel_get_runtime_selection_values($runtime_context);
    $block['product_matched_mapping'] = $matched_mapping;

    return $block;
}

function vip_funnel_normalize_product_mappings($mappings = []): array {
    $normalized = [];

    foreach((array) vip_funnel_to_array($mappings) as $mapping) {
        $mapping = vip_funnel_to_array($mapping);
        $match_value = trim(input_clean((string) ($mapping['match_value'] ?? ''), 120));
        $translation_key = trim(input_clean((string) ($mapping['product_translation_key'] ?? ''), 128));
        $blog_post_id = max(0, (int) ($mapping['product_blog_post_id'] ?? 0));

        if($translation_key === '' && $blog_post_id > 0) {
            $translation_key = vip_funnel_get_product_translation_key_by_blog_post_id($blog_post_id);
        }

        if($match_value === '' && $translation_key === '') {
            continue;
        }

        $normalized[] = [
            'id' => trim(input_clean((string) ($mapping['id'] ?? vip_funnel_generate_page_block_id('product_map')), 120)),
            'match_value' => $match_value,
            'product_translation_key' => $translation_key,
            'product_blog_post_id' => $blog_post_id,
        ];
    }

    return array_values($normalized);
}

function vip_funnel_get_runtime_selection_values(array $runtime_context = []): array {
    $runtime_context = vip_funnel_to_array($runtime_context);
    $values = [];

    $selection = trim((string) ($runtime_context['selection'] ?? ''));
    if($selection !== '') {
        $values[] = $selection;
    }

    foreach((array) ($runtime_context['radio_answers'] ?? []) as $answer) {
        $answer = vip_funnel_to_array($answer);
        $value = trim((string) ($answer['value'] ?? ''));

        if($value !== '') {
            $values[] = $value;
        }
    }

    return array_values(array_unique($values));
}

function vip_funnel_normalize_signal_value(string $value = ''): string {
    $value = trim(mb_strtolower($value));
    $value = strtr($value, [
        'č' => 'c',
        'ć' => 'c',
        'đ' => 'd',
        'š' => 's',
        'ž' => 'z',
    ]);
    $value = preg_replace('/[^a-z0-9]+/i', '_', $value) ?? '';
    $value = trim($value, '_');

    return $value;
}

function vip_funnel_collect_lead_signal_values(array $meta = []): array {
    $meta = vip_funnel_to_array($meta);
    $signals = [];

    $append_signal = static function($value) use (&$signals) {
        $value = trim((string) $value);

        if($value === '') {
            return;
        }

        $signals[] = $value;
        $normalized = vip_funnel_normalize_signal_value($value);

        if($normalized !== '' && $normalized !== $value) {
            $signals[] = $normalized;
        }
    };

    $append_signal($meta['selection'] ?? '');

    foreach((array) ($meta['radio_answers'] ?? []) as $answer) {
        $answer = vip_funnel_to_array($answer);
        $append_signal($answer['value'] ?? '');
        $append_signal($answer['label'] ?? '');
        $append_signal($answer['question'] ?? '');
    }

    return array_values(array_unique(array_filter($signals, static function($signal) {
        return trim((string) $signal) !== '';
    })));
}

function vip_funnel_signal_contains_any(array $signals, array $needles): bool {
    $haystack = ' ' . implode(' ', array_map(static function($signal) {
        return vip_funnel_normalize_signal_value((string) $signal);
    }, $signals)) . ' ';

    foreach($needles as $needle) {
        $needle = vip_funnel_normalize_signal_value((string) $needle);

        if($needle !== '' && str_contains($haystack, $needle)) {
            return true;
        }
    }

    return false;
}

function vip_funnel_get_follow_up_playbook(string $segment = 'warm', array $context = []): array {
    $segment = in_array($segment, ['hot', 'warm', 'demo', 'product', 'cold'], true) ? $segment : 'warm';
    $context = vip_funnel_to_array($context);
    $name = trim((string) ($context['name'] ?? ''));
    $first_name = $name !== '' ? trim((string) preg_split('/\s+/', $name)[0]) : '';
    $hello = $first_name !== '' ? 'Bok ' . $first_name . ',' : 'Bok,';

    $playbooks = [
        'hot' => [
            'priority' => 'urgent',
            'next_action' => 'Kontaktirati isti dan i poslati Start Your Journey korak.',
            'cadence' => 'Odmah, Dan 1, Dan 3',
            'steps' => [
                ['delay' => 'now', 'channel' => 'whatsapp', 'message' => $hello . ' Stjepan ovdje. Vidio sam da želiš krenuti ozbiljno i da ti Start Your Journey ima smisla. Šaljem ti najbrži sljedeći korak i tu sam za kratku potvrdu prije narudžbe.'],
                ['delay' => 'day_1', 'channel' => 'whatsapp', 'message' => 'Najvažnije je da kreneš jasno: paket, prvi zadaci, FCC sustav i mentorstvo. Ako želiš, mogu ti u par minuta potvrditi je li ovo pravi start za tebe.'],
                ['delay' => 'day_3', 'channel' => 'email', 'message' => 'Ako želiš krenuti, nemoj ostati na razmišljanju. Prvi korak je Start Your Journey, a nakon toga te vodimo kroz sustav korak po korak.'],
            ],
        ],
        'warm' => [
            'priority' => 'high',
            'next_action' => 'Poslati FCC demo i ponuditi kratak razgovor.',
            'cadence' => 'Dan 0, Dan 1, Dan 3',
            'steps' => [
                ['delay' => 'now', 'channel' => 'whatsapp_or_email', 'message' => $hello . ' hvala ti na odgovorima. Prije odluke oko start paketa najbolje je da ti pokažem kako FCC sustav radi iznutra.'],
                ['delay' => 'day_1', 'channel' => 'email', 'message' => 'Najveća razlika je krenuti sam ili krenuti uz sustav. FCC je napravljen da novi ljudi ne moraju sve izmišljati od nule.'],
                ['delay' => 'day_3', 'channel' => 'whatsapp_or_email', 'message' => 'Ako želiš, mogu ti u par minuta reći je li za tebe bolji start paket, demo ili proizvodni put.'],
            ],
        ],
        'demo' => [
            'priority' => 'medium',
            'next_action' => 'Poslati demo pregled i pitati želi li poslovni ili proizvodni put.',
            'cadence' => 'Dan 0, Dan 2',
            'steps' => [
                ['delay' => 'now', 'channel' => 'email', 'message' => $hello . ' šaljem ti kratki FCC demo. Pogledaj kako sustav vodi posjetitelja od interesa do kontakta, proizvoda ili suradnje.'],
                ['delay' => 'day_2', 'channel' => 'whatsapp_or_email', 'message' => 'Nakon demo pregleda najvažnije pitanje je: želiš li FCC koristiti za posao ili prvo za proizvode i preporuke?'],
            ],
        ],
        'product' => [
            'priority' => 'medium',
            'next_action' => 'Poslati proizvodnu preporuku i kasnije otvoriti business bridge.',
            'cadence' => 'Dan 0, Dan 2, Dan 5',
            'steps' => [
                ['delay' => 'now', 'channel' => 'whatsapp_or_email', 'message' => $hello . ' vidio sam da te sada više zanimaju proizvodi ili popust. Šaljem ti preporuku prema cilju koji si odabrao/la.'],
                ['delay' => 'day_2', 'channel' => 'email', 'message' => 'Ako ti se svidi proizvodni put, kasnije ti mogu pokazati kako isti sustav koristiti za online preporuke.'],
                ['delay' => 'day_5', 'channel' => 'whatsapp_or_email', 'message' => 'Jesi li uspio/la pogledati preporuku? Ako želiš, mogu ti reći najjednostavniji sljedeći korak.'],
            ],
        ],
        'cold' => [
            'priority' => 'low',
            'next_action' => 'Ne trošiti previše ručnog vremena; poslati miran uvod.',
            'cadence' => 'Dan 0, Dan 5',
            'steps' => [
                ['delay' => 'now', 'channel' => 'email', 'message' => $hello . ' nema pritiska. Šaljem ti kratak uvod pa se možeš vratiti kad bude pravi trenutak.'],
                ['delay' => 'day_5', 'channel' => 'email', 'message' => 'Ako se kasnije odlučiš za proizvode, demo ili poslovni start, možeš ponovno otvoriti funnel i odabrati svoj put.'],
            ],
        ],
    ];

    return array_merge(['segment' => $segment], $playbooks[$segment]);
}

function vip_funnel_calculate_lead_qualification(array $fields = [], array $meta = [], array $state = []): array {
    $fields = vip_funnel_to_array($fields);
    $meta = vip_funnel_to_array($meta);
    $signals = vip_funnel_collect_lead_signal_values($meta);
    $score = 30;
    $reasons = [];

    if(trim((string) ($fields['email'] ?? '')) !== '') {
        $score += 12;
        $reasons[] = 'email_captured';
    }

    if(trim((string) ($fields['phone'] ?? '')) !== '') {
        $score += 18;
        $reasons[] = 'phone_captured';
    }

    if(trim((string) ($fields['contact_time'] ?? '')) !== '') {
        $score += 4;
        $reasons[] = 'preferred_contact_time_captured';
    }

    if(trim((string) (($fields['full_name'] ?? '') ?: ($fields['name'] ?? ''))) !== '') {
        $score += 6;
        $reasons[] = 'name_captured';
    }

    $has_business_intent = vip_funnel_signal_contains_any($signals, ['business', 'posao', 'online_posao', 'suradnja', 'dodatni_prihod', 'ozbiljan_online_posao', 'start_your_journey', 'ready_360_now', 'ready_360_call']);
    $has_product_intent = vip_funnel_signal_contains_any($signals, ['product', 'proizvod', 'proizvodi', 'popust', 'discount', 'vise_energije', 'regulacija_tezine', 'njega_koze', 'dnevna_rutina']);
    $has_demo_intent = vip_funnel_signal_contains_any($signals, ['demo', 'fcc_demo', 'vidjeti_sustav', 'zelim_demo', 'trebam_prvo_vidjeti_sustav']);
    $ready_now = vip_funnel_signal_contains_any($signals, ['ready_360_now', 'mogu_odmah', 'krenuti_odmah', 'spreman_sam', 'vec_sam_spreman']);
    $ready_call = vip_funnel_signal_contains_any($signals, ['ready_360_call', 'zelim_kratak_razgovor', 'razgovor_prije', 'kratak_razgovor']);
    $not_ready = vip_funnel_signal_contains_any($signals, ['not_ready', 'ne_sada', 'samo_istrazujem', 'nisam_spreman', 'trebam_vise_informacija', 'time_no_capacity']);
    $time_strong = vip_funnel_signal_contains_any($signals, ['time_8_plus', '8_sati', '8_sati_tjedno', '8']);
    $time_medium = vip_funnel_signal_contains_any($signals, ['time_4_7', '4_7_sati', '4_7']);

    if($has_business_intent) {
        $score += 18;
        $reasons[] = 'business_intent';
    }

    if($ready_now) {
        $score += 30;
        $reasons[] = 'ready_for_360_now';
    } elseif($ready_call) {
        $score += 20;
        $reasons[] = 'wants_call_before_360';
    }

    if($time_strong) {
        $score += 10;
        $reasons[] = 'strong_time_capacity';
    } elseif($time_medium) {
        $score += 7;
        $reasons[] = 'medium_time_capacity';
    }

    if($has_demo_intent) {
        $score += 8;
        $reasons[] = 'demo_interest';
    }

    if($has_product_intent) {
        $score += 5;
        $reasons[] = 'product_interest';
    }

    if($not_ready) {
        $score -= 22;
        $reasons[] = 'not_ready_now';
    }

    $score = max(0, min(100, $score));
    $segment = 'warm';
    $interest_type = 'business_interest';
    $readiness_key = 'warm';

    if($has_product_intent && !$has_business_intent && !$ready_now && !$ready_call) {
        $segment = 'product';
        $interest_type = 'product_interest';
        $readiness_key = 'product';
    } elseif($ready_now && $has_business_intent && $score >= 75) {
        $segment = 'hot';
        $interest_type = 'business_interest';
        $readiness_key = 'hot';
    } elseif($ready_call) {
        $segment = 'warm';
        $interest_type = 'business_interest';
        $readiness_key = 'warm';
    } elseif($has_demo_intent) {
        $segment = 'demo';
        $interest_type = 'demo_interest';
        $readiness_key = 'demo';
    } elseif($not_ready || $score < 45) {
        $segment = 'cold';
        $interest_type = $has_product_intent ? 'product_interest' : 'nurture';
        $readiness_key = 'cold';
    } elseif($has_business_intent && $score >= 58) {
        $segment = 'warm';
        $interest_type = 'business_interest';
        $readiness_key = 'warm';
    }

    if($segment === 'hot') {
        $score = max(80, $score);
    } elseif($segment === 'warm') {
        $score = min(79, max(58, $score));
    } elseif($segment === 'demo') {
        $score = min(74, max(50, $score));
    } elseif($segment === 'product') {
        $score = min(70, max(45, $score));
    } elseif($segment === 'cold') {
        $score = min(44, $score);
    }

    $selection = trim((string) ($meta['selection'] ?? ''));

    return [
        'score' => $score,
        'segment' => $segment,
        'interest_type' => $interest_type,
        'readiness_key' => $readiness_key,
        'product_goal' => $has_product_intent ? $selection : '',
        'selection' => $selection,
        'signals' => $signals,
        'reasons' => array_values(array_unique($reasons)),
        'recommended_next_action' => vip_funnel_get_follow_up_playbook($segment, [
            'name' => (string) (($fields['full_name'] ?? '') ?: ($fields['name'] ?? '')),
        ])['next_action'] ?? '',
        'scored_at' => get_date(),
    ];
}

function vip_funnel_find_product_mapping_match(array $block = [], array $runtime_context = []): ?array {
    $block = vip_funnel_to_array($block);
    $mappings = vip_funnel_normalize_product_mappings($block['product_mappings'] ?? []);
    $selection_values = vip_funnel_get_runtime_selection_values($runtime_context);

    if(empty($mappings) || empty($selection_values)) {
        return null;
    }

    foreach($selection_values as $selection_value) {
        foreach($mappings as $mapping) {
            if(trim((string) ($mapping['match_value'] ?? '')) !== $selection_value) {
                continue;
            }

            return $mapping;
        }
    }

    return null;
}

function vip_funnel_normalize_page_block_payload($block, int $index = 0): array {
    $block = vip_funnel_to_array($block);
    $type = input_clean((string) ($block['type'] ?? 'headline'), 32);

    if(!array_key_exists($type, vip_funnel_get_page_block_type_options())) {
        $type = 'headline';
    }

    $defaults = vip_funnel_get_page_block_defaults($type);
    $font_family_options = vip_funnel_get_page_font_family_options();
    $font_weight_options = vip_funnel_get_page_font_weight_options();
    $block_id = trim(input_clean((string) ($block['id'] ?? vip_funnel_generate_page_block_id($type)), 120));
    $label = trim(input_clean((string) ($block['label'] ?? ($defaults['label'] ?? 'Blok')), 80));
    $alignment = input_clean((string) ($block['alignment'] ?? ($defaults['alignment'] ?? 'left')), 16);
    $layout_width = input_clean((string) ($block['layout_width'] ?? ($defaults['layout_width'] ?? 'full')), 24);
    $font_family = input_clean((string) ($block['font_family'] ?? ($defaults['font_family'] ?? 'inherit')), 32);
    $badge_weight = input_clean((string) ($block['badge_weight'] ?? ($defaults['badge_weight'] ?? '800')), 4);
    $title_weight = input_clean((string) ($block['title_weight'] ?? ($defaults['title_weight'] ?? '800')), 4);
    $text_weight = input_clean((string) ($block['text_weight'] ?? ($defaults['text_weight'] ?? '500')), 4);
    $field_weight = input_clean((string) ($block['field_weight'] ?? ($defaults['field_weight'] ?? '500')), 4);
    $button_weight = input_clean((string) ($block['button_weight'] ?? ($defaults['button_weight'] ?? '800')), 4);

    if(!array_key_exists($alignment, vip_funnel_get_page_block_alignment_options())) {
        $alignment = $defaults['alignment'] ?? 'left';
    }

    if(!array_key_exists($layout_width, vip_funnel_get_page_block_width_options())) {
        $layout_width = (string) ($defaults['layout_width'] ?? 'full');
    }

    if(!array_key_exists($font_family, $font_family_options)) {
        $font_family = 'inherit';
    }

    if(!array_key_exists($badge_weight, $font_weight_options)) {
        $badge_weight = (string) ($defaults['badge_weight'] ?? '800');
    }

    if(!array_key_exists($title_weight, $font_weight_options)) {
        $title_weight = (string) ($defaults['title_weight'] ?? '800');
    }

    if(!array_key_exists($text_weight, $font_weight_options)) {
        $text_weight = (string) ($defaults['text_weight'] ?? '500');
    }

    if(!array_key_exists($field_weight, $font_weight_options)) {
        $field_weight = (string) ($defaults['field_weight'] ?? '500');
    }

    if(!array_key_exists($button_weight, $font_weight_options)) {
        $button_weight = (string) ($defaults['button_weight'] ?? '800');
    }

    $payload = [
        'id' => $block_id !== '' ? $block_id : vip_funnel_generate_page_block_id($type),
        'type' => $type,
        'label' => $label !== '' ? $label : ($defaults['label'] ?? 'Blok'),
        'badge' => trim(input_clean((string) ($block['badge'] ?? ($defaults['badge'] ?? '')), 80)),
        'title' => trim(input_clean((string) ($block['title'] ?? ($defaults['title'] ?? '')), 180)),
        'text' => trim(strip_tags(mb_substr((string) ($block['text'] ?? ($defaults['text'] ?? '')), 0, 1200))),
        'media_url' => trim(input_clean((string) ($block['media_url'] ?? ($defaults['media_url'] ?? '')), 2048)),
        'alignment' => $alignment,
        'layout_width' => $layout_width,
        'font_family' => $font_family,
        'badge_size' => max(10, min(32, (int) ($block['badge_size'] ?? ($defaults['badge_size'] ?? 13)))),
        'badge_weight' => $badge_weight,
        'badge_color' => verify_hex_color((string) ($block['badge_color'] ?? '')) ? (string) $block['badge_color'] : '',
        'title_size' => max(16, min(96, (int) ($block['title_size'] ?? ($defaults['title_size'] ?? 28)))),
        'title_weight' => $title_weight,
        'title_color' => verify_hex_color((string) ($block['title_color'] ?? '')) ? (string) $block['title_color'] : '',
        'text_size' => max(12, min(48, (int) ($block['text_size'] ?? ($defaults['text_size'] ?? 17)))),
        'text_weight' => $text_weight,
        'body_color' => verify_hex_color((string) ($block['body_color'] ?? '')) ? (string) $block['body_color'] : '',
        'field_size' => max(12, min(36, (int) ($block['field_size'] ?? ($defaults['field_size'] ?? 16)))),
        'field_weight' => $field_weight,
        'field_text_color' => verify_hex_color((string) ($block['field_text_color'] ?? '')) ? (string) $block['field_text_color'] : '',
        'placeholder_color' => verify_hex_color((string) ($block['placeholder_color'] ?? '')) ? (string) $block['placeholder_color'] : '',
        'button_size' => max(12, min(36, (int) ($block['button_size'] ?? ($defaults['button_size'] ?? 17)))),
        'button_weight' => $button_weight,
        'button_text_color' => verify_hex_color((string) ($block['button_text_color'] ?? '')) ? (string) $block['button_text_color'] : '',
        'background_color' => verify_hex_color((string) ($block['background_color'] ?? '')) ? (string) $block['background_color'] : '',
        'text_color' => verify_hex_color((string) ($block['text_color'] ?? '')) ? (string) $block['text_color'] : '',
        'accent_color' => verify_hex_color((string) ($block['accent_color'] ?? '')) ? (string) $block['accent_color'] : '',
        'placeholder' => trim(input_clean((string) ($block['placeholder'] ?? ($defaults['placeholder'] ?? '')), 180)),
        'field_key' => trim(input_clean((string) ($block['field_key'] ?? ($defaults['field_key'] ?? '')), 80)),
        'required' => isset($block['required']) ? (bool) $block['required'] : (bool) ($defaults['required'] ?? false),
        'spacing' => input_clean((string) ($block['spacing'] ?? ($defaults['spacing'] ?? 'md')), 12),
        'countdown_mode' => input_clean((string) ($block['countdown_mode'] ?? ($defaults['countdown_mode'] ?? 'fixed')), 16),
        'countdown_style' => input_clean((string) ($block['countdown_style'] ?? ($defaults['countdown_style'] ?? 'cards')), 24),
        'fixed_datetime' => trim(input_clean((string) ($block['fixed_datetime'] ?? ($defaults['fixed_datetime'] ?? '')), 40)),
        'duration_minutes' => max(0, min(10080, (int) ($block['duration_minutes'] ?? ($defaults['duration_minutes'] ?? 30)))),
        'duration_days' => max(0, min(365, (int) ($block['duration_days'] ?? ($defaults['duration_days'] ?? 0)))),
        'countdown_weekly_day' => max(0, min(6, (int) ($block['countdown_weekly_day'] ?? ($defaults['countdown_weekly_day'] ?? 4)))),
        'countdown_weekly_time' => vip_funnel_normalize_countdown_weekly_time((string) ($block['countdown_weekly_time'] ?? ($defaults['countdown_weekly_time'] ?? '20:00'))),
        'countdown_timezone' => vip_funnel_normalize_countdown_timezone((string) ($block['countdown_timezone'] ?? ($defaults['countdown_timezone'] ?? 'Europe/Zagreb'))),
        'countdown_show_days' => isset($block['countdown_show_days']) ? (bool) $block['countdown_show_days'] : (bool) ($defaults['countdown_show_days'] ?? true),
        'countdown_show_hours' => isset($block['countdown_show_hours']) ? (bool) $block['countdown_show_hours'] : (bool) ($defaults['countdown_show_hours'] ?? true),
        'countdown_show_minutes' => isset($block['countdown_show_minutes']) ? (bool) $block['countdown_show_minutes'] : (bool) ($defaults['countdown_show_minutes'] ?? true),
        'countdown_show_seconds' => isset($block['countdown_show_seconds']) ? (bool) $block['countdown_show_seconds'] : (bool) ($defaults['countdown_show_seconds'] ?? true),
        'countdown_number_size' => max(16, min(96, (int) ($block['countdown_number_size'] ?? ($defaults['countdown_number_size'] ?? 34)))),
        'countdown_number_color' => verify_hex_color((string) ($block['countdown_number_color'] ?? '')) ? (string) $block['countdown_number_color'] : '',
        'completion_text' => trim(input_clean((string) ($block['completion_text'] ?? ($defaults['completion_text'] ?? '')), 180)),
        'product_blog_post_id' => max(0, (int) ($block['product_blog_post_id'] ?? ($defaults['product_blog_post_id'] ?? 0))),
        'product_translation_key' => trim(input_clean((string) ($block['product_translation_key'] ?? ($defaults['product_translation_key'] ?? '')), 128)),
        'product_source_mode' => input_clean((string) ($block['product_source_mode'] ?? ($defaults['product_source_mode'] ?? 'manual')), 16),
        'product_language_mode' => input_clean((string) ($block['product_language_mode'] ?? ($defaults['product_language_mode'] ?? 'page')), 16),
        'product_language_code' => trim(input_clean((string) ($block['product_language_code'] ?? ($defaults['product_language_code'] ?? \Altum\Language::$default_code)), 12)),
        'product_fallback_language_code' => trim(input_clean((string) ($block['product_fallback_language_code'] ?? ($defaults['product_fallback_language_code'] ?? 'hr')), 12)),
        'product_primary_mode' => input_clean((string) ($block['product_primary_mode'] ?? ($defaults['product_primary_mode'] ?? 'blog_guide')), 24),
        'product_primary_cta_text' => trim(input_clean((string) ($block['product_primary_cta_text'] ?? ($defaults['product_primary_cta_text'] ?? 'Pogledaj vodič proizvoda')), 120)),
        'product_secondary_enabled' => isset($block['product_secondary_enabled']) ? (bool) $block['product_secondary_enabled'] : (bool) ($defaults['product_secondary_enabled'] ?? true),
        'product_secondary_mode' => input_clean((string) ($block['product_secondary_mode'] ?? ($defaults['product_secondary_mode'] ?? 'direct_shop')), 24),
        'product_secondary_cta_text' => trim(input_clean((string) ($block['product_secondary_cta_text'] ?? ($defaults['product_secondary_cta_text'] ?? 'Idi na službeni shop')), 120)),
        'product_mappings' => vip_funnel_normalize_product_mappings($block['product_mappings'] ?? ($defaults['product_mappings'] ?? [])),
    ];

    if(!in_array($payload['spacing'], ['xs', 'sm', 'md', 'lg', 'xl'], true)) {
        $payload['spacing'] = 'md';
    }

    if(!in_array($payload['countdown_mode'], ['fixed', 'evergreen', 'weekly'], true)) {
        $payload['countdown_mode'] = 'fixed';
    }

    if(!in_array($payload['countdown_style'], ['cards', 'glass', 'minimal', 'spotlight'], true)) {
        $payload['countdown_style'] = 'cards';
    }

    if($type === 'countdown' && !$payload['countdown_show_days'] && !$payload['countdown_show_hours'] && !$payload['countdown_show_minutes'] && !$payload['countdown_show_seconds']) {
        $payload['countdown_show_seconds'] = true;
    }

    if(!array_key_exists($payload['product_primary_mode'], vip_funnel_get_product_target_mode_options())) {
        $payload['product_primary_mode'] = (string) ($defaults['product_primary_mode'] ?? 'blog_guide');
    }

    if(!array_key_exists($payload['product_source_mode'], vip_funnel_get_product_source_mode_options())) {
        $payload['product_source_mode'] = (string) ($defaults['product_source_mode'] ?? 'manual');
    }

    if(!array_key_exists($payload['product_secondary_mode'], vip_funnel_get_product_target_mode_options())) {
        $payload['product_secondary_mode'] = (string) ($defaults['product_secondary_mode'] ?? 'direct_shop');
    }

    if(!array_key_exists($payload['product_language_mode'], vip_funnel_get_product_language_mode_options())) {
        $payload['product_language_mode'] = (string) ($defaults['product_language_mode'] ?? 'page');
    }

    $product_language_codes = vip_funnel_get_product_language_codes();
    if(!in_array($payload['product_language_code'], $product_language_codes, true)) {
        $payload['product_language_code'] = (string) ($defaults['product_language_code'] ?? \Altum\Language::$default_code);
    }

    if($payload['product_fallback_language_code'] !== '' && !in_array($payload['product_fallback_language_code'], $product_language_codes, true)) {
        $payload['product_fallback_language_code'] = (string) ($defaults['product_fallback_language_code'] ?? 'hr');
    }

    $payload['buttons'] = vip_funnel_normalize_page_actions($block['buttons'] ?? ($defaults['buttons'] ?? []), 'button', $defaults['buttons'] ?? []);
    $payload['options'] = vip_funnel_normalize_page_actions($block['options'] ?? ($defaults['options'] ?? []), 'option', $defaults['options'] ?? []);
    $payload['require_capture'] = isset($block['require_capture']) ? (bool) $block['require_capture'] : (bool) ($defaults['require_capture'] ?? false);
    $payload['auto_advance'] = isset($block['auto_advance']) ? (bool) $block['auto_advance'] : (bool) ($defaults['auto_advance'] ?? true);
    $payload['route_on_submit'] = isset($block['route_on_submit']) ? (bool) $block['route_on_submit'] : (bool) ($defaults['route_on_submit'] ?? false);

    if(in_array($type, ['survey', 'radio_survey'], true) && empty($payload['options'])) {
        $payload['options'] = vip_funnel_normalize_page_actions($defaults['options'] ?? [], 'option', $defaults['options'] ?? []);
    }

    if($type === 'cta_group' && empty($payload['buttons'])) {
        $payload['buttons'] = vip_funnel_normalize_page_actions($defaults['buttons'] ?? [], 'button', $defaults['buttons'] ?? []);
    }

    if($type === 'radio_survey' && !empty($payload['options'])) {
        foreach($payload['options'] as &$option) {
            $option['style'] = 'primary';
        }
        unset($option);
    }

    if($type === 'product_offer' && $payload['product_translation_key'] === '' && $payload['product_blog_post_id'] > 0) {
        $payload['product_translation_key'] = vip_funnel_get_product_translation_key_by_blog_post_id((int) $payload['product_blog_post_id']);
    }

    return $payload;
}

function vip_funnel_surface_has_capture_fields(array $surface = []): bool {
    $surface = vip_funnel_normalize_page_surface_payload($surface);

    foreach((array) ($surface['blocks'] ?? []) as $block) {
        $type = (string) (($block['type'] ?? ''));
        if(in_array($type, ['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field'], true)) {
            return true;
        }
    }

    return false;
}

function vip_funnel_surface_has_submit_action(array $surface = []): bool {
    $surface = vip_funnel_normalize_page_surface_payload($surface);

    foreach((array) ($surface['blocks'] ?? []) as $block) {
        $block = vip_funnel_to_array($block);
        $type = (string) ($block['type'] ?? '');
        $actions = [];

        if($type === 'cta_group') {
            $actions = (array) ($block['buttons'] ?? []);
        }

        if($type === 'survey') {
            $actions = (array) ($block['options'] ?? []);
        }

        foreach($actions as $action) {
            $action = vip_funnel_to_array($action);
            if(in_array((string) ($action['action'] ?? ''), ['submit_next', 'submit_stay'], true) || !empty($action['require_submit'])) {
                return true;
            }
        }
    }

    return false;
}

function vip_funnel_normalize_page_surface_payload($surface, string $fallback_name = ''): array {
    $surface = vip_funnel_to_array($surface);
    $defaults = vip_funnel_get_default_page_surface_payload($fallback_name);
    $max_width = input_clean((string) ($surface['max_width'] ?? $defaults['max_width']), 16);

    if(!array_key_exists($max_width, vip_funnel_get_page_theme_width_options())) {
        $max_width = $defaults['max_width'];
    }

    $normalize_block_list = static function($blocks) {
        $blocks = vip_funnel_to_array($blocks);
        $normalized_blocks = [];
        $used_ids = [];

        foreach(array_values($blocks) as $index => $block) {
            $normalized_block = vip_funnel_normalize_page_block_payload($block, $index);

            if(in_array($normalized_block['id'], $used_ids, true)) {
                $normalized_block['id'] = vip_funnel_generate_page_block_id($normalized_block['type'] ?? 'block');
            }

            $used_ids[] = $normalized_block['id'];
            $normalized_blocks[] = $normalized_block;
        }

        return $normalized_blocks;
    };

    $normalize_variant_settings = static function($variant_settings, array $base_surface, array $fallback_defaults) {
        $variant_settings = vip_funnel_to_array($variant_settings);
        $max_width = input_clean((string) ($variant_settings['max_width'] ?? ($base_surface['max_width'] ?? $fallback_defaults['max_width'])), 16);

        if(!array_key_exists($max_width, vip_funnel_get_page_theme_width_options())) {
            $max_width = (string) ($base_surface['max_width'] ?? $fallback_defaults['max_width']);
        }

        return [
            'name' => trim(input_clean((string) ($variant_settings['name'] ?? ($base_surface['name'] ?? $fallback_defaults['name'])), 140)) ?: (string) ($base_surface['name'] ?? $fallback_defaults['name']),
            'background_color' => verify_hex_color((string) ($variant_settings['background_color'] ?? '')) ? (string) $variant_settings['background_color'] : (string) ($base_surface['background_color'] ?? $fallback_defaults['background_color']),
            'surface_color' => verify_hex_color((string) ($variant_settings['surface_color'] ?? '')) ? (string) $variant_settings['surface_color'] : (string) ($base_surface['surface_color'] ?? $fallback_defaults['surface_color']),
            'text_color' => verify_hex_color((string) ($variant_settings['text_color'] ?? '')) ? (string) $variant_settings['text_color'] : (string) ($base_surface['text_color'] ?? $fallback_defaults['text_color']),
            'accent_color' => verify_hex_color((string) ($variant_settings['accent_color'] ?? '')) ? (string) $variant_settings['accent_color'] : (string) ($base_surface['accent_color'] ?? $fallback_defaults['accent_color']),
            'max_width' => $max_width,
            'show_progress' => isset($variant_settings['show_progress']) ? (bool) $variant_settings['show_progress'] : (bool) ($base_surface['show_progress'] ?? $fallback_defaults['show_progress']),
        ];
    };

    $normalized_surface = [
        'name' => trim(input_clean((string) ($surface['name'] ?? $defaults['name']), 140)) ?: $defaults['name'],
        'background_color' => verify_hex_color((string) ($surface['background_color'] ?? '')) ? (string) $surface['background_color'] : $defaults['background_color'],
        'surface_color' => verify_hex_color((string) ($surface['surface_color'] ?? '')) ? (string) $surface['surface_color'] : $defaults['surface_color'],
        'text_color' => verify_hex_color((string) ($surface['text_color'] ?? '')) ? (string) $surface['text_color'] : $defaults['text_color'],
        'accent_color' => verify_hex_color((string) ($surface['accent_color'] ?? '')) ? (string) $surface['accent_color'] : $defaults['accent_color'],
        'max_width' => $max_width,
        'show_progress' => isset($surface['show_progress']) ? (bool) $surface['show_progress'] : (bool) $defaults['show_progress'],
        'ab_enabled' => isset($surface['ab_enabled']) ? (bool) $surface['ab_enabled'] : (bool) $defaults['ab_enabled'],
        'ab_distribution' => max(5, min(95, (int) ($surface['ab_distribution'] ?? $defaults['ab_distribution']))),
        'blocks' => $normalize_block_list($surface['blocks'] ?? []),
        'variant_b_blocks' => $normalize_block_list($surface['variant_b_blocks'] ?? []),
    ];

    $normalized_surface['variant_b_settings'] = $normalize_variant_settings($surface['variant_b_settings'] ?? [], $normalized_surface, $defaults);

    return $normalized_surface;
}

function vip_funnel_apply_surface_variant(array $surface = [], string $variant_key = 'a'): array {
    $surface = vip_funnel_normalize_page_surface_payload($surface);

    if($variant_key !== 'b' || empty($surface['ab_enabled'])) {
        return $surface;
    }

    $variant_settings = vip_funnel_to_array($surface['variant_b_settings'] ?? []);

    foreach(['name', 'background_color', 'surface_color', 'text_color', 'accent_color', 'max_width', 'show_progress'] as $field_key) {
        if(array_key_exists($field_key, $variant_settings)) {
            $surface[$field_key] = $variant_settings[$field_key];
        }
    }

    return $surface;
}

function vip_funnel_build_surface_from_legacy_step(array $step = [], string $surface_name = ''): array {
    $step = vip_funnel_to_array($step);
    $step_id = preg_replace('/[^a-z0-9_]+/i', '_', (string) ($step['id'] ?? 'legacy_step')) ?: 'legacy_step';
    $buttons = vip_funnel_normalize_page_actions($step['button_options'] ?? [], 'button', []);
    $summary = (string) ($step['summary'] ?? '');
    $helper_text = (string) ($step['helper_text'] ?? '');
    $media_url = trim((string) ($step['media_url'] ?? ''));
    $surface = vip_funnel_get_default_page_surface_payload($surface_name !== '' ? $surface_name : ((string) ($step['title'] ?? 'Funnel stranica')));
    $surface['background_color'] = verify_hex_color((string) ($step['background_color'] ?? '')) ? (string) $step['background_color'] : $surface['background_color'];
    $surface['text_color'] = verify_hex_color((string) ($step['text_color'] ?? '')) ? (string) $step['text_color'] : $surface['text_color'];
    $surface['accent_color'] = verify_hex_color((string) ($step['accent_color'] ?? '')) ? (string) $step['accent_color'] : $surface['accent_color'];
    $surface['surface_color'] = $surface['background_color'];
    $surface['show_progress'] = true;
    $surface['blocks'] = [
        vip_funnel_normalize_page_block_payload([
            'id' => $step_id . '_headline',
            'type' => 'headline',
            'badge' => (string) ($step['preview_badge'] ?? ''),
            'title' => (string) ($step['title'] ?? ''),
            'text' => $summary,
        ]),
    ];

    if($helper_text !== '') {
        $surface['blocks'][] = vip_funnel_normalize_page_block_payload([
            'id' => $step_id . '_helper',
            'type' => 'proof_card',
            'title' => 'Kratko pojašnjenje',
            'text' => $helper_text,
        ]);
    }

    if($media_url !== '') {
        $surface['blocks'][] = vip_funnel_normalize_page_block_payload([
            'id' => $step_id . '_media',
            'type' => (string) ($step['block_mode'] ?? '') === 'image' ? 'image' : 'video',
            'title' => '',
            'text' => '',
            'media_url' => $media_url,
        ]);
    }

    if((string) ($step['block_mode'] ?? '') === 'choice' || !empty($step['answers'])) {
        $options = !empty($buttons) ? array_map(static function($button) {
            return [
                'label' => $button['label'] ?? '',
                'value' => $button['label'] ?? '',
                'style' => $button['style'] ?? 'secondary',
                'action' => 'goto_step',
                'target_step_id' => $button['target_step_id'] ?? '',
                'require_submit' => false,
            ];
        }, $buttons) : array_map(static function($answer) {
            return [
                'label' => (string) $answer,
                'value' => (string) $answer,
                'style' => 'secondary',
                'action' => 'goto_step',
                'target_step_id' => '',
                'require_submit' => false,
            ];
        }, (array) ($step['answers'] ?? []));

        $surface['blocks'][] = vip_funnel_normalize_page_block_payload([
            'id' => $step_id . '_survey',
            'type' => 'survey',
            'title' => (string) ($step['cta'] ?? 'Odaberi sljedeći korak'),
            'text' => (string) ($step['next'] ?? ''),
            'options' => $options,
        ]);
    } else {
        $button_items = !empty($buttons) ? $buttons : [[
            'label' => (string) ($step['cta'] ?? 'Kreni dalje'),
            'style' => 'primary',
            'action' => 'goto_step',
            'target_step_id' => (string) ($step['next_step_id'] ?? ''),
            'require_submit' => false,
        ]];

        if(in_array((string) ($step['block_mode'] ?? ''), ['contact_form', 'video_form'], true)) {
            $button_items = array_map(static function($button) {
                $button = vip_funnel_to_array($button);
                $button['action'] = 'submit_next';
                $button['require_submit'] = true;

                return $button;
            }, $button_items);
        }

        $surface['blocks'][] = vip_funnel_normalize_page_block_payload([
            'id' => $step_id . '_cta',
            'type' => 'cta_group',
            'text' => (string) ($step['next'] ?? ''),
            'buttons' => $button_items,
        ]);
    }

    if(in_array((string) ($step['block_mode'] ?? ''), ['contact_form', 'video_form'], true)) {
        $surface['blocks'][] = vip_funnel_normalize_page_block_payload([
            'id' => $step_id . '_name',
            'type' => 'name_field',
            'required' => false,
        ]);
        $surface['blocks'][] = vip_funnel_normalize_page_block_payload([
            'id' => $step_id . '_email',
            'type' => 'email_field',
            'required' => true,
        ]);
        $surface['blocks'][] = vip_funnel_normalize_page_block_payload([
            'id' => $step_id . '_phone',
            'type' => 'phone_field',
            'required' => false,
        ]);
    }

    return vip_funnel_normalize_page_surface_payload($surface, $surface['name'] ?? 'Funnel stranica');
}

function vip_funnel_extract_surface_actions(array $surface = []): array {
    $surface = vip_funnel_normalize_page_surface_payload($surface);
    $actions = [];

    foreach((array) ($surface['blocks'] ?? []) as $block) {
        $block = vip_funnel_to_array($block);
        $block_type = (string) ($block['type'] ?? '');
        $block_id = (string) ($block['id'] ?? '');

        if($block_type === 'cta_group') {
            foreach((array) ($block['buttons'] ?? []) as $button) {
                $button = vip_funnel_to_array($button);
                $target_step_id = trim((string) ($button['target_step_id'] ?? ''));

                if($target_step_id === '') {
                    continue;
                }

                $actions[] = [
                    'edge_type' => 'button',
                    'condition_key' => $block_id,
                    'condition_value' => trim((string) ($button['label'] ?? '')),
                    'target_step_id' => $target_step_id,
                ];
            }
        }

        if($block_type === 'survey') {
            foreach((array) ($block['options'] ?? []) as $option) {
                $option = vip_funnel_to_array($option);
                $target_step_id = trim((string) ($option['target_step_id'] ?? ''));

                if($target_step_id === '') {
                    continue;
                }

                $actions[] = [
                    'edge_type' => 'survey',
                    'condition_key' => $block_id,
                    'condition_value' => trim((string) ($option['value'] ?? ($option['label'] ?? ''))),
                    'target_step_id' => $target_step_id,
                ];
            }
        }
    }

    return $actions;
}

function vip_funnel_upgrade_surface_for_legacy_step(array $surface = [], array $step = []): array {
    $surface = vip_funnel_normalize_page_surface_payload($surface, (string) ($step['title'] ?? 'Funnel stranica'));
    $step = vip_funnel_to_array($step);
    $step_id = preg_replace('/[^a-z0-9_]+/i', '_', (string) ($step['id'] ?? 'legacy_step')) ?: 'legacy_step';
    $legacy_mode = (string) ($step['block_mode'] ?? '');

    if(!in_array($legacy_mode, ['contact_form', 'video_form'], true)) {
        return $surface;
    }

    $has_capture_fields = false;
    foreach((array) ($surface['blocks'] ?? []) as $block) {
        $type = (string) (($block['type'] ?? ''));
        if(in_array($type, ['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field'], true)) {
            $has_capture_fields = true;
            break;
        }
    }

    if(!$has_capture_fields) {
        $surface['blocks'][] = vip_funnel_normalize_page_block_payload(['id' => $step_id . '_name', 'type' => 'name_field', 'required' => false]);
        $surface['blocks'][] = vip_funnel_normalize_page_block_payload(['id' => $step_id . '_email', 'type' => 'email_field', 'required' => true]);
        $surface['blocks'][] = vip_funnel_normalize_page_block_payload(['id' => $step_id . '_phone', 'type' => 'phone_field', 'required' => false]);
    }

    foreach($surface['blocks'] as &$block) {
        $block = vip_funnel_normalize_page_block_payload($block);
        if(($block['type'] ?? '') !== 'cta_group') {
            continue;
        }

        $block['buttons'] = array_map(static function($button) {
            $button = vip_funnel_to_array($button);
            $button['action'] = 'submit_next';
            $button['require_submit'] = true;
            return $button;
        }, (array) ($block['buttons'] ?? []));
    }
    unset($block);

    return vip_funnel_normalize_page_surface_payload($surface, (string) ($surface['name'] ?? ($step['title'] ?? 'Funnel stranica')));
}

function vip_funnel_get_block_template_presets(): array {
    return [
        'intro_message' => [
            'label' => 'Uvodna poruka',
            'description' => 'Jednostavan ulazni blok s jasnim obećanjem i jednim CTA-om.',
            'payload' => [
                'card_type' => 'offer',
                'block_mode' => 'message',
                'title' => 'Pokreni svoj prvi korak bez kaosa',
                'summary' => 'Pokaži osobi da postoji jednostavan i vođen put prema rezultatu, bez previše informacija odjednom.',
                'cta' => 'Želim vidjeti kako',
                'preview_badge' => 'Početak',
                'preview_headline' => 'Pokreni svoj prvi korak bez kaosa',
                'preview_body' => 'Jasan ulaz, jedan fokus i osjećaj da se ovo može pratiti bez stresa.',
                'background_color' => '#152132',
                'text_color' => '#eef4ff',
                'accent_color' => '#67d8c9',
                'button_options' => [
                    ['label' => 'Kreni dalje', 'next_step_id' => '', 'style' => 'primary'],
                ],
            ],
        ],
        'choice_paths' => [
            'label' => 'Pitanje s izborima',
            'description' => 'Blok koji usmjerava osobu na pravi smjer kroz 2 do 3 jednostavna izbora.',
            'payload' => [
                'card_type' => 'question',
                'block_mode' => 'choice',
                'title' => 'Što te sada najviše zanima?',
                'summary' => 'Kratko pitanje koje osobu odmah vodi na pravi put bez zbunjivanja.',
                'cta' => 'Odaberi smjer',
                'preview_badge' => 'Izbor',
                'preview_headline' => 'Što te sada najviše zanima?',
                'preview_body' => 'Odaberi ono što ti je sada najvažnije i sustav te vodi dalje.',
                'background_color' => '#1d2235',
                'text_color' => '#eef4ff',
                'accent_color' => '#f4b63f',
                'button_options' => [
                    ['label' => 'Online posao', 'next_step_id' => '', 'style' => 'primary'],
                    ['label' => 'Proizvodi', 'next_step_id' => '', 'style' => 'secondary'],
                    ['label' => 'Želim demo', 'next_step_id' => '', 'style' => 'ghost'],
                ],
            ],
        ],
        'video_story' => [
            'label' => 'Video blok',
            'description' => 'Blok za kratki video uvod ili objašnjenje jedne jake ideje.',
            'payload' => [
                'card_type' => 'demo',
                'block_mode' => 'video',
                'title' => 'Pogledaj kratki video uvod',
                'summary' => 'Jedan video koji pojašnjava ideju ili stvara prvi aha trenutak.',
                'cta' => 'Pogledaj video',
                'preview_badge' => 'Video',
                'preview_headline' => 'Pogledaj kratki video uvod',
                'preview_body' => 'Jedan video, jedna poruka i jasan sljedeći korak.',
                'background_color' => '#172635',
                'text_color' => '#eef4ff',
                'accent_color' => '#7cc7ff',
                'button_options' => [
                    ['label' => 'Želim nastaviti', 'next_step_id' => '', 'style' => 'primary'],
                ],
            ],
        ],
        'image_story' => [
            'label' => 'Slika + poruka',
            'description' => 'Blok s jakom slikom, kratkom porukom i jednim fokusiranim sljedećim korakom.',
            'payload' => [
                'card_type' => 'offer',
                'block_mode' => 'image',
                'title' => 'Pogledaj što možeš pokrenuti uz pravi sustav',
                'summary' => 'Vizualno predstavi rezultat, stil života ili jasnu korist prije nego osoba ide dalje.',
                'cta' => 'Želim vidjeti više',
                'preview_badge' => 'Vizual',
                'preview_headline' => 'Pogledaj što možeš pokrenuti uz pravi sustav',
                'preview_body' => 'Jedna jaka slika, kratka poruka i logičan sljedeći klik.',
                'background_color' => '#182434',
                'text_color' => '#eef4ff',
                'accent_color' => '#ffcc5c',
                'button_options' => [
                    ['label' => 'Želim vidjeti više', 'next_step_id' => '', 'style' => 'primary'],
                    ['label' => 'Pošalji detalje', 'next_step_id' => '', 'style' => 'secondary'],
                ],
            ],
        ],
        'video_form' => [
            'label' => 'Video + forma',
            'description' => 'Spoji kratki video i jednostavnu formu za interes ili prijavu.',
            'payload' => [
                'card_type' => 'demo',
                'block_mode' => 'video_form',
                'title' => 'Pogledaj video i ostavi interes',
                'summary' => 'Nakon videa osoba odmah može ostaviti kontakt bez dodatnog klikanja.',
                'cta' => 'Pošalji interes',
                'preview_badge' => 'Video + forma',
                'preview_headline' => 'Pogledaj video i ostavi interes',
                'preview_body' => 'Kratki video plus jednostavna forma za nastavak razgovora.',
                'background_color' => '#172635',
                'text_color' => '#eef4ff',
                'accent_color' => '#67d8c9',
                'button_options' => [
                    ['label' => 'Pošalji interes', 'next_step_id' => '', 'style' => 'primary'],
                ],
            ],
        ],
        'contact_block' => [
            'label' => 'Kontakt forma',
            'description' => 'Gotov blok za ostavljanje imena, e-maila ili poruke interesa.',
            'payload' => [
                'card_type' => 'cta',
                'block_mode' => 'contact_form',
                'title' => 'Ostavi kontakt i javljamo ti se',
                'summary' => 'Jednostavan blok za kontakt bez previše polja i bez pritiska.',
                'cta' => 'Pošalji kontakt',
                'preview_badge' => 'Kontakt',
                'preview_headline' => 'Ostavi kontakt i javljamo ti se',
                'preview_body' => 'Ime, e-mail i jedan klik dovoljan su za sljedeći korak.',
                'background_color' => '#101826',
                'text_color' => '#eef4ff',
                'accent_color' => '#67d8c9',
                'button_options' => [
                    ['label' => 'Pošalji kontakt', 'next_step_id' => '', 'style' => 'primary'],
                ],
            ],
        ],
        'product_offer' => [
            'label' => 'Preporuka proizvoda',
            'description' => 'Jasan proizvodni blok s fokusom na jednu preporuku i jedan CTA.',
            'payload' => [
                'card_type' => 'proof',
                'block_mode' => 'product_offer',
                'title' => 'Preporuka proizvoda za tvoj cilj',
                'summary' => 'Jedna jasna preporuka koja je laka za razumjeti i još lakša za prvi korak.',
                'cta' => 'Želim preporuku',
                'preview_badge' => 'Proizvod',
                'preview_headline' => 'Preporuka proizvoda za tvoj cilj',
                'preview_body' => 'Jedna preporuka, kratak opis i logičan sljedeći korak.',
                'background_color' => '#1d2330',
                'text_color' => '#eef4ff',
                'accent_color' => '#f4b63f',
                'button_options' => [
                    ['label' => 'Želim preporuku', 'next_step_id' => '', 'style' => 'primary'],
                    ['label' => 'Pošalji detalje', 'next_step_id' => '', 'style' => 'secondary'],
                ],
            ],
        ],
    ];
}

function vip_funnel_get_default_paths_payload(): array {
    return [
        [
            'path_key' => 'business',
            'title' => 'Online posao',
            'description' => 'Put za osobe koje žele pokrenuti posao uz vođeni sustav i jasne prve korake.',
            'sort_order' => 1,
            'is_enabled' => true,
        ],
        [
            'path_key' => 'products',
            'title' => 'Preporuka proizvoda',
            'description' => 'Put za osobe koje najprije žele rješenje kroz proizvode i jednostavnu preporuku.',
            'sort_order' => 2,
            'is_enabled' => true,
        ],
        [
            'path_key' => 'demo',
            'title' => 'Doživi sustav',
            'description' => 'Put za osobe koje trebaju osjetiti sustav iznutra prije nego donesu odluku.',
            'sort_order' => 3,
            'is_enabled' => true,
        ],
    ];
}

function vip_funnel_find_catalog_translation_key(array $candidates = [], string $preferred_language_code = 'hr'): string {
    $catalog = vip_funnel_get_product_catalog($preferred_language_code);

    if(empty($catalog)) {
        return '';
    }

    $normalized_candidates = array_values(array_filter(array_map(static function($candidate) {
        return vip_funnel_normalize_signal_value((string) $candidate);
    }, $candidates)));

    foreach($normalized_candidates as $candidate) {
        foreach($catalog as $product) {
            $translation_key = (string) ($product['translation_key'] ?? '');
            $title = (string) ($product['title'] ?? '');
            $haystack = vip_funnel_normalize_signal_value($translation_key . ' ' . $title);

            if($candidate !== '' && str_contains($haystack, $candidate)) {
                return $translation_key;
            }
        }
    }

    return (string) ($catalog[0]['translation_key'] ?? '');
}

function vip_funnel_get_stjepan_recruitment_payload($user = null, array $options = []): array {
    $options = vip_funnel_to_array($options);
    $contact_email = trim((string) ($options['contact_email'] ?? 'info@forevercard.club'));
    $contact_email = filter_var($contact_email, FILTER_VALIDATE_EMAIL) ? $contact_email : 'info@forevercard.club';
    $mailto_order_url = 'mailto:' . rawurlencode($contact_email) . '?subject=' . rawurlencode('Start Your Journey paket') . '&body=' . rawurlencode('Pozdrav, želim naručiti Start Your Journey paket i krenuti uz FCC mentorstvo.');
    $mailto_contact_url = 'mailto:' . rawurlencode($contact_email) . '?subject=' . rawurlencode('FCC mentorstvo - želim razgovor');
    $checkout_url = trim((string) ($options['checkout_url'] ?? '')) ?: $mailto_order_url;
    $whatsapp_url = trim((string) ($options['whatsapp_url'] ?? '')) ?: $mailto_contact_url;
    $calendar_url = trim((string) ($options['calendar_url'] ?? '')) ?: $whatsapp_url;
    $product_shop_url = trim((string) ($options['product_shop_url'] ?? '')) ?: SITE_URL . 'blog';
    $privacy_url = trim((string) ($options['privacy_url'] ?? '')) ?: SITE_URL . 'page/privacy-policy';
    $facebook_pixel_source = array_key_exists('facebook_pixel_id', $options) ? (string) $options['facebook_pixel_id'] : '238225369103006';
    $facebook_pixel_id = vip_funnel_normalize_meta_pixel_id($facebook_pixel_source);
    $video = static function(string $key) use ($options): string {
        return trim((string) ($options['video_' . $key] ?? ''));
    };

    $product_keys = [
        'energy' => vip_funnel_find_catalog_translation_key(['aloe vera gel', 'forever aloe vera gel', 'forever freedom', 'vitamin c'], 'hr'),
        'weight' => vip_funnel_find_catalog_translation_key(['c9', 'dx4', 'f15', 'weight', 'regulacija tezine'], 'hr'),
        'skin' => vip_funnel_find_catalog_translation_key(['marine collagen', 'vitamin c', 'aloescrub', 'skin', 'koza'], 'hr'),
        'routine' => vip_funnel_find_catalog_translation_key(['aloe msm gel', 'msm', 'forever freedom', 'aloe vera gel'], 'hr'),
        'discount' => vip_funnel_find_catalog_translation_key(['start paket', 'aloe vera gel', 'forever'], 'hr'),
    ];
    $primary_product_key = $product_keys['energy'] ?: ($product_keys['skin'] ?: ($product_keys['weight'] ?: ''));

    $action = static function(string $id, string $label, string $value = '', string $target_step_id = '', string $style = 'primary', string $action = 'goto_step', bool $require_submit = false, string $external_url = '', string $hint = ''): array {
        return [
            'id' => $id,
            'label' => $label,
            'hint' => $hint,
            'value' => $value !== '' ? $value : $id,
            'style' => $style,
            'action' => $action,
            'target_step_id' => $target_step_id,
            'external_url' => $external_url,
            'require_submit' => $require_submit,
        ];
    };

    $block = static function(string $id, string $type, array $payload = []): array {
        return array_merge([
            'id' => $id,
            'type' => $type,
            'label' => $payload['label'] ?? $id,
            'layout_width' => 'full',
            'alignment' => 'left',
        ], $payload);
    };

    $surface = static function(string $name, array $blocks, array $settings = []): array {
        return array_merge([
            'name' => $name,
            'background_color' => '#0B1118',
            'surface_color' => '#111B27',
            'text_color' => '#F5FAFF',
            'accent_color' => '#67D8C9',
            'max_width' => 'wide',
            'show_progress' => true,
            'ab_enabled' => false,
            'ab_distribution' => 50,
            'blocks' => $blocks,
            'variant_b_blocks' => [],
            'variant_b_settings' => [],
        ], $settings);
    };

    $step = static function(string $id, string $phase_key, string $path_key, string $card_type, string $title, string $summary, array $blocks, string $next_step_id = '', array $settings = []) use ($surface): array {
        return array_merge([
            'id' => $id,
            'path_key' => $path_key,
            'row_key' => $path_key,
            'card_type' => $card_type,
            'title' => $title,
            'summary' => $summary,
            'helper_text' => $summary,
            'cta' => 'Nastavi',
            'next' => '',
            'next_step_id' => $next_step_id,
            'status_key' => in_array($card_type, ['cta'], true) ? 'conversion' : ($card_type === 'proof' ? 'proof' : 'core'),
            'media_url' => '',
            'answers' => [],
            'tags' => [$phase_key, $path_key, $card_type],
            'owner_user_id' => (int) ($settings['owner_user_id'] ?? 0),
            'visibility_key' => 'all',
            'analytics_label' => $id,
            'design_variant' => $settings['design_variant'] ?? 'card',
            'preview_badge' => $settings['preview_badge'] ?? ucfirst($phase_key),
            'preview_headline' => $title,
            'preview_body' => $summary,
            'block_mode' => $settings['block_mode'] ?? 'message',
            'background_color' => $settings['background_color'] ?? '#111B27',
            'text_color' => '#F5FAFF',
            'accent_color' => $settings['accent_color'] ?? '#67D8C9',
            'button_options' => [],
            'page' => $surface($title, $blocks, $settings['surface'] ?? []),
        ], $settings['step'] ?? []);
    };

    $landing_blocks = [
        $block('landing_hero', 'headline', [
            'badge' => 'Počni ovdje',
            'title' => 'Pokreni svoj FCC put uz moje osobno mentorstvo',
            'text' => 'Došao/la si s mojih videa? Ovdje ćeš brzo vidjeti je li FCC za tebe, koji je tvoj najbolji prvi korak i kako možeš krenuti uz moje mentorstvo.',
            'title_size' => 50,
            'text_size' => 20,
            'alignment' => 'center',
        ]),
        $block('landing_intro_video', 'video', [
            'title' => 'Prvo pogledaj ovu kratku poruku',
            'text' => 'U par minuta ću ti objasniti kako funkcionira ovaj vodič i koji sljedeći korak odabrati ovisno o tome gdje se trenutno nalaziš.',
            'media_url' => $video('main'),
            'layout_width' => 'two_thirds',
            'alignment' => 'center',
        ]),
        $block('landing_proof', 'proof_card', [
            'badge' => 'Zašto sam složio ovaj vodič',
            'title' => 'Ne želim da lutaš. Želim da odmah vidiš pravi sljedeći korak.',
            'text' => 'Neki ljudi žele pokrenuti online posao, neki prvo žele razumjeti FCC sustav, neki dolaze zbog proizvoda, a neki su već spremni za start paket. Zato te ova stranica vodi jednostavno, korak po korak.',
            'layout_width' => 'third',
        ]),
        $block('landing_direction', 'survey', [
            'title' => 'Gdje se trenutno nalaziš?',
            'text' => 'Odaberi opciju koja najbolje opisuje tvoju situaciju. Nisi siguran/na što odabrati? Kreni s opcijom "Želim prvo razumjeti FCC sustav".',
            'options' => [
                $action('landing_business', 'Želim pokrenuti online posao', 'business_interest', 'business_gateway', 'primary'),
                $action('landing_demo', 'Želim prvo razumjeti FCC sustav', 'demo_interest', 'fcc_demo_preview', 'secondary'),
                $action('landing_product', 'Zanimaju me proizvodi i popusti', 'product_discount', 'product_gateway', 'secondary'),
                $action('landing_ready', 'Spreman/na sam za start paket', 'ready_360_now', 'start_package_offer', 'primary'),
            ],
            'alignment' => 'center',
            'auto_advance' => true,
        ]),
    ];

    $landing_page = $surface('Stjepan Beloša | Osobni FCC vodič', $landing_blocks, [
        'show_progress' => false,
        'ab_enabled' => true,
        'ab_distribution' => 50,
        'variant_b_blocks' => [
            $block('landing_b_hero', 'headline', [
                'badge' => 'Online posao uz vođeni sustav',
                'title' => 'Pridruži se mom FCC timu i kreni graditi online posao uz jasan sustav',
                'text' => 'Ako želiš ozbiljno krenuti, ne moraš sve smišljati sam/a. Kroz FCC sustav, proizvode, edukaciju i mentorstvo pokazat ću ti kako napraviti prve korake i uključiti se u tim.',
                'title_size' => 50,
                'text_size' => 20,
                'alignment' => 'center',
            ]),
            $block('landing_b_intro_video', 'video', [
                'title' => 'Kratka poruka prije nego odabereš svoj put',
                'text' => 'Prvo pogledaj video kako bi razumio/la što je FCC, kako izgleda suradnja i što znači krenuti uz moje mentorstvo.',
                'media_url' => $video('main'),
                'layout_width' => 'two_thirds',
                'alignment' => 'center',
            ]),
            $block('landing_b_proof', 'proof_card', [
                'badge' => 'Kako ovo funkcionira',
                'title' => 'Ovo nije samo informacija. Ovo je prvi korak prema ulasku u tim.',
                'text' => 'FCC ti daje sustav, proizvode, smjer i podršku. Tvoj zadatak je odabrati gdje si sada, a ja ću te kroz sljedeće korake usmjeriti prema odluci koja ima smisla za tebe.',
                'layout_width' => 'third',
            ]),
            $block('landing_b_direction', 'survey', [
                'title' => 'Odaberi svoj sljedeći korak',
                'text' => 'Bez pritiska. Odaberi ono što ti je trenutno najbliže i nastavi kroz vodič.',
                'options' => [
                    $action('landing_b_business', 'Želim krenuti s online poslom', 'business_interest', 'business_gateway', 'primary'),
                    $action('landing_b_demo', 'Pokaži mi kako radi FCC sustav', 'demo_interest', 'fcc_demo_preview', 'secondary'),
                    $action('landing_b_product', 'Prvo želim upoznati proizvode', 'product_discount', 'product_gateway', 'secondary'),
                    $action('landing_b_ready', 'Želim start paket i ulazak u tim', 'ready_360_now', 'start_package_offer', 'primary'),
                ],
                'alignment' => 'center',
                'auto_advance' => true,
            ]),
        ],
    ]);

    $business_gateway = $step('business_gateway', 'entry', 'business', 'offer', 'Poslovni filter za ozbiljan FCC start', 'Vodi prema start paketu, kratkoj provjeri, demo prikazu sustava ili mirnijem uvodu.', [
        $block('business_hero', 'headline', [
            'badge' => 'Poslovni put',
            'title' => 'Ako želiš krenuti ozbiljno, kreni uz sustav, tim i mentora.',
            'text' => 'Ovdje ti pokazujem što znači ulazak u moj FCC tim: kako se radi, što dobivaš kroz sustav, koji su prvi koraci i kada ima smisla uzeti start paket. Bez lutanja, bez previše informacija odjednom - samo jasan sljedeći korak.',
        ]),
        $block('business_video', 'video', [
            'title' => 'Kako izgleda ulazak u moj FCC tim',
            'text' => 'Pogledaj kratku poruku prije odluke. Pokazat ću ti što radiš u prvom tjednu, kako koristiš sustav i što znači imati mentora uz sebe.',
            'media_url' => $video('business'),
            'layout_width' => 'two_thirds',
        ]),
        $block('business_system', 'proof_card', [
            'badge' => 'Što dobivaš',
            'title' => 'Ne krećeš od nule. Krećeš kroz jasan FCC okvir.',
            'text' => 'Dobivaš smjer za prve objave, prezentaciju, preporuke, razgovore i follow-up. Rezultat ovisi o tvojoj aktivnosti, ali ne ulaziš naslijepo - znaš što radiš prvi dan, prvi tjedan i nakon toga.',
            'layout_width' => 'third',
        ]),
        $block('business_choice', 'survey', [
            'title' => 'Što ti sada treba za odluku?',
            'text' => 'Odaberi iskreno gdje si trenutno. Tako će te funnel odvesti na najbrži sljedeći korak bez nepotrebnog lutanja.',
            'options' => [
                $action('business_serious', 'Spreman/na sam za start paket i ulazak u tim', 'ready_360_now', 'start_package_offer', 'primary'),
                $action('business_check', 'Želim kratku provjeru prije starta', 'needs_check', 'qualification_form', 'secondary'),
                $action('business_demo', 'Prvo želim vidjeti kako sustav radi', 'demo_interest', 'fcc_demo_preview', 'secondary'),
                $action('business_not_ready', 'Nisam još spreman/na za start paket', 'not_ready', 'not_ready_nurture', 'ghost'),
            ],
            'auto_advance' => true,
        ]),
    ], 'qualification_form', ['design_variant' => 'spotlight', 'block_mode' => 'choice']);

    $qualification_form = $step('qualification_form', 'segment', 'business', 'question', 'Kratka provjera prije starta', 'Kvalifikacija sprema odgovore, WhatsApp/telefon i najbolji termin kontakta te vodi na pravi nastavak.', [
        $block('qualification_hero', 'headline', [
            'badge' => 'Kratka provjera prije starta',
            'title' => 'Odgovori iskreno i pokazat ću ti najbolji sljedeći korak.',
            'text' => 'Ovo nije test ni obveza. Treba mi nekoliko iskrenih odgovora da vidim ima li smisla da kreneš sa start paketom sada, da prvo prođemo kratak razgovor ili da pogledaš sustav mirnijim tempom.',
        ]),
        $block('qualification_goal', 'radio_survey', [
            'title' => 'Što želiš postići kroz FCC?',
            'text' => 'Odaberi ono što ti je trenutno najbliže. Ne mora biti konačna odluka.',
            'required' => true,
            'route_on_submit' => false,
            'options' => [
                $action('goal_extra_income', 'Dodatni prihod uz postojeće obaveze', 'dodatni_prihod', '', 'primary', 'goto_step', false, '', 'Želim krenuti postupno, ali uz jasan plan i podršku.'),
                $action('goal_serious_business', 'Ozbiljan online posao', 'ozbiljan_online_posao', '', 'primary', 'goto_step', false, '', 'Spreman/na sam učiti, raditi i dugoročnije graditi svoj tim.'),
                $action('goal_product_first', 'Prvo proizvodi i popust, posao kasnije', 'product_discount', '', 'primary', 'goto_step', false, '', 'Želim upoznati proizvode, koristiti pogodnosti i vidjeti ima li smisla preporučivati.'),
                $action('goal_research', 'Samo istražujem', 'samo_istrazujem', '', 'primary', 'goto_step', false, '', 'Želim razumjeti opcije bez pritiska i vidjeti je li FCC za mene.'),
            ],
        ]),
        $block('qualification_time', 'radio_survey', [
            'title' => 'Koliko vremena realno možeš odvojiti tjedno?',
            'text' => 'Bolje je odgovoriti realno nego idealno. Tako ćeš dobiti tempo koji ima smisla za tvoju situaciju.',
            'required' => true,
            'route_on_submit' => false,
            'options' => [
                $action('time_1_3', '1-3 sata tjedno', 'time_1_3', '', 'primary', 'goto_step', false, '', 'Za miran početak, osnovne zadatke i postupno upoznavanje sustava.'),
                $action('time_4_7', '4-7 sati tjedno', 'time_4_7', '', 'primary', 'goto_step', false, '', 'Dovoljno za ozbiljan start, prve kontakte i stvaranje radne navike.'),
                $action('time_8_plus', '8+ sati tjedno', 'time_8_plus', '', 'primary', 'goto_step', false, '', 'Spreman/na sam za brži ritam i aktivniji početak.'),
                $action('time_no_capacity', 'Trenutno ne mogu odvojiti vrijeme', 'time_no_capacity', '', 'primary', 'goto_step', false, '', 'Bolje je krenuti kasnije nego pod pritiskom i bez fokusa.'),
            ],
        ]),
        $block('qualification_investment', 'radio_survey', [
            'title' => 'Je li ti Start Your Journey paket od 360 € realan ovaj tjedan ako zaključiš da je FCC za tebe?',
            'text' => 'Ovo pitanje ne služi za pritisak, nego da te ne vodim na pogrešan sljedeći korak.',
            'required' => true,
            'route_on_submit' => true,
            'options' => [
                $action('ready_now', 'Da, mogu odmah', 'ready_360_now', 'start_package_offer', 'primary', 'goto_step', false, '', 'Ako mi je smjer jasan, mogu napraviti narudžbu i krenuti.'),
                $action('ready_call', 'Da, ali želim kratak razgovor', 'ready_360_call', 'mentor_call_request', 'primary', 'goto_step', false, '', 'Trebam potvrdu i par odgovora prije narudžbe.'),
                $action('need_demo', 'Prvo želim vidjeti sustav', 'trebam_prvo_vidjeti_sustav', 'fcc_demo_preview', 'primary', 'goto_step', false, '', 'Želim bolje razumjeti kako FCC radi prije odluke.'),
                $action('not_now', 'Ne sada', 'not_ready', 'not_ready_nurture', 'primary', 'goto_step', false, '', 'Trenutno nije pravi trenutak za start paket.'),
            ],
        ]),
        $block('qualification_channel', 'radio_survey', [
            'title' => 'Kako želiš da te kontaktiram?',
            'text' => 'Za ovu provjeru ne koristim email kao glavni kanal. Najbrže i najjasnije je preko WhatsAppa ili kratkog poziva.',
            'required' => true,
            'route_on_submit' => false,
            'options' => [
                $action('channel_whatsapp', 'WhatsApp poruka', 'channel_whatsapp', '', 'primary', 'goto_step', false, '', 'Najbrže za prvi kontakt, dogovor i slanje sljedećih uputa.'),
                $action('channel_phone', 'Kratki telefonski poziv', 'channel_phone', '', 'primary', 'goto_step', false, '', 'Najbolje ako želiš brzo proći pitanja i donijeti odluku.'),
                $action('channel_whatsapp_call', 'WhatsApp pa poziv', 'channel_whatsapp_call', '', 'primary', 'goto_step', false, '', 'Prvo ti pošaljem poruku, pa dogovorimo najbolji termin za razgovor.'),
            ],
        ]),
        $block('qualification_name', 'full_name_field', ['title' => 'Ime i prezime', 'placeholder' => 'Upiši ime i prezime', 'required' => true, 'layout_width' => 'half']),
        $block('qualification_phone', 'phone_field', ['title' => 'WhatsApp / telefon', 'placeholder' => 'Upiši broj na koji te mogu brzo kontaktirati', 'required' => true, 'layout_width' => 'half']),
        $block('qualification_contact_time', 'text_field', ['title' => 'Najbolje vrijeme za kontakt', 'placeholder' => 'npr. danas poslije 17h, sutra ujutro, navečer', 'field_key' => 'contact_time', 'required' => false, 'layout_width' => 'full']),
        $block('qualification_privacy', 'text', [
            'text' => 'Slanjem odgovora potvrđuješ da te Stjepan ili FCC tim smije kontaktirati vezano uz tvoj odabrani FCC smjer putem WhatsAppa ili telefonskog poziva. Tvoje podatke koristimo za obradu upita i komunikaciju vezanu uz FCC. Privacy: ' . $privacy_url,
            'text_size' => 14,
        ]),
        $block('qualification_submit', 'cta_group', [
            'text' => 'Pošalji odgovore i dobit ćeš preporučeni sljedeći korak. Nakon slanja vidjet ćeš preporučeni korak, a ako si odabrao/la kontakt, javit ću ti se preko WhatsAppa ili poziva.',
            'buttons' => [
                $action('qualification_submit_btn', 'Pošalji provjeru i pokaži mi sljedeći korak', 'qualification_submit', '', 'primary', 'submit_next', true),
            ],
            'alignment' => 'center',
        ]),
    ], 'hot_result', ['design_variant' => 'stacked', 'block_mode' => 'contact_form']);

    $hot_result = $step('hot_result', 'trust', 'business', 'proof', 'Tvoj najbolji sljedeći korak je Start Your Journey', 'Za osobe koje su pokazale jasnu spremnost za poslovni start.', [
        $block('hot_hero', 'headline', [
            'badge' => 'HOT kandidat',
            'title' => 'Po tvojim odgovorima ima smisla da vidiš konkretan start.',
            'text' => 'Start Your Journey paket je 360 EUR i ulaz je u proizvode, sustav i vođeni početak. Ako kreneš, ne ostaješ sam: dobit ćeš jasne prve korake i mentorstvo.',
        ]),
        $block('hot_video', 'video', [
            'title' => 'Što dobivaš za 360 EUR',
            'text' => 'Kratko objašnjenje paketa, sustava, prvih 7 dana i očekivanja.',
            'media_url' => $video('start_package'),
        ]),
        $block('hot_actions', 'cta_group', [
            'buttons' => [
                $action('hot_order', 'Naruči Start Your Journey paket', 'order_start_package', '', 'primary', 'external_url', false, $checkout_url),
                $action('hot_call', 'Želim prvo kratak razgovor', 'ready_360_call', 'mentor_call_request', 'secondary'),
                $action('hot_demo', 'Želim vidjeti FCC demo', 'demo_interest', 'fcc_demo_preview', 'ghost'),
            ],
            'alignment' => 'center',
        ]),
    ], 'start_package_offer', ['design_variant' => 'decision', 'block_mode' => 'video']);

    $start_package_offer = $step('start_package_offer', 'experience', 'business', 'cta', 'Start Your Journey: ulazak u FCC tim i prvi konkretan korak', 'Konverzijska stranica za start paket, ulazak u tim i onboarding.', [
        $block('start_hero', 'headline', [
            'badge' => 'Start Your Journey | 360 €',
            'title' => 'Ako želiš ući u FCC tim, start paket je tvoj prvi konkretan korak.',
            'text' => 'Kroz Start Your Journey dobivaš proizvode, ulazak u sustav, početnu edukaciju i moje mentorstvo za prve korake. Nakon narudžbe ne ostaješ sam/a - vodim te kroz postavljanje, razumijevanje ponude i prve zadatke.',
            'title_size' => 50,
        ]),
        $block('start_video', 'video', [
            'title' => 'Prije narudžbe pogledaj što točno dobivaš',
            'text' => 'U kratkom videu pokazujem što uključuje start paket, kako izgleda prvi tjedan i što se događa nakon što pošalješ narudžbu.',
            'media_url' => $video('start_package'),
        ]),
        $block('start_included', 'proof_card', [
            'badge' => 'Dobivaš',
            'title' => 'Proizvode, FCC sustav, prve zadatke i mentorstvo za start.',
            'text' => 'Ne kupuješ obećanje lake zarade. Dobivaš proizvode, jasan početni okvir, edukaciju i podršku. Rezultat ovisi o tvojoj aktivnosti, učenju, komunikaciji i dosljednosti.',
            'layout_width' => 'half',
        ]),
        $block('start_first_week', 'proof_card', [
            'badge' => 'Prvih 7 dana',
            'title' => 'Postavljanje, prvi zadaci, razumijevanje ponude i prvi kontakti.',
            'text' => 'Cilj je da nakon narudžbe ne ostaneš sam/a, nego da odmah znaš koji je tvoj prvi korak, kako koristiti sustav i kako krenuti s preporukama.',
            'layout_width' => 'half',
        ]),
        $block('start_countdown', 'countdown', [
            'title' => 'Sljedeći Zoom onboarding za nove suradnike',
            'text' => 'Onboarding se održava svaki četvrtak u 20:00 putem Zooma. Nakon registracije osobno ću te kontaktirati i poslati Zoom link, pripremu i sve što ti treba za prvi webinar i početak.',
            'countdown_mode' => 'weekly',
            'countdown_weekly_day' => 4,
            'countdown_weekly_time' => '20:00',
            'countdown_timezone' => 'Europe/Zagreb',
            'duration_days' => 0,
            'duration_minutes' => 0,
            'countdown_style' => 'spotlight',
        ]),
        $block('start_actions', 'cta_group', [
            'text' => 'Odaberi kako želiš napraviti sljedeći korak. Nakon klika dobit ćeš upute za narudžbu i povezivanje sa mnom prije onboardinga.',
            'buttons' => [
                $action('start_order', 'Želim naručiti Start Your Journey paket', 'order_start_package', '', 'primary', 'external_url', false, vip_funnel_get_forever_business_referral_action_token(), 'Preusmjerava te na službenu Forever Living stranicu za narudžbu i upis s preporukom tvog mentora i automatskim odabirom zemlje.'),
                $action('start_whatsapp', 'Imam pitanje prije narudžbe', 'start_whatsapp', '', 'secondary', 'external_url', false, vip_funnel_get_start_package_question_whatsapp_url($user, 'hr') ?: $whatsapp_url),
                $action('start_call', 'Nisam još siguran/na - želim kratki razgovor', 'ready_360_call', 'mentor_call_request', 'ghost'),
            ],
            'alignment' => 'center',
        ]),
    ], 'mentor_call_request', ['design_variant' => 'decision', 'block_mode' => 'video']);

    $call_fast_contact_url = vip_funnel_resolve_fast_contact_url($whatsapp_url) ?: vip_funnel_resolve_fast_contact_url($calendar_url);
    $mentor_call_request = $step('mentor_call_request', 'trust', 'business', 'cta', 'Zatraži kratak razgovor prije odluke', 'Smiruje nesigurnost i vodi osobu prema WhatsAppu ili pozivu bez pritiska.', array_values(vip_funnel_get_call_request_step_blocks('hr', 'Stjepan', $privacy_url, $call_fast_contact_url)), 'not_ready_nurture', ['design_variant' => 'decision', 'block_mode' => 'contact_form']);

    $fcc_demo_preview = $step('fcc_demo_preview', 'entry', 'demo', 'demo', 'Razumij FCC sustav prije odluke', 'Mirno objašnjava kako FCC vodi od interesa do proizvoda, provjere, demo prikaza ili ulaska u tim.', [
        $block('demo_hero', 'headline', [
            'badge' => 'FCC sustav',
            'title' => 'Prvo razumij kako FCC funkcionira, pa odluči je li ovo za tebe.',
            'text' => 'U ovom koraku pokazujem ti kako FCC vodi osobu od prvog interesa do jasnog sljedećeg koraka: proizvodi, provjera, demo ili ulazak u tim. Ako odlučiš krenuti, isti okvir možeš koristiti i u svom poslu uz moje mentorstvo.',
            'title_size' => 50,
        ]),
        $block('demo_video', 'video', [
            'title' => 'Kako izgleda FCC sustav u praksi',
            'text' => 'Pogledaj kratki prikaz sustava: kako se osoba vodi kroz funnel, kako se biraju proizvodi ili poslovni put, kako izgleda kontakt i što se događa nakon prvog interesa.',
            'media_url' => $video('demo'),
        ]),
        $block('demo_actions', 'cta_group', [
            'text' => 'Nakon što pogledaš objašnjenje, odaberi što ti je sada najbliže.',
            'buttons' => [
                $action('demo_qualify', 'Želim provjeriti je li FCC za mene', 'business_interest', 'qualification_form', 'primary'),
                $action('demo_start', 'Spreman/na sam za start paket i ulazak u tim', 'ready_360_now', 'start_package_offer', 'secondary'),
                $action('demo_request', 'Želim detaljniji demo sustava', 'demo_request', 'demo_request', 'secondary'),
                $action('demo_products', 'Zanimaju me samo proizvodi i popusti', 'product_discount', 'product_gateway', 'ghost'),
            ],
            'alignment' => 'center',
        ]),
    ], 'qualification_form', ['design_variant' => 'card', 'block_mode' => 'video']);

    $demo_request = $step('demo_request', 'segment', 'demo', 'demo', 'Zatraži kontrolirani demo pristup FCC sustavu', 'Lead capture za kontrolirani demo interes.', array_values(vip_funnel_get_demo_request_step_blocks('hr', 'Stjepan', $privacy_url)), 'not_ready_nurture', ['design_variant' => 'card', 'block_mode' => 'contact_form']);

    $product_gateway = $step('product_gateway', 'entry', 'products', 'question', 'Proizvodni put s jasnom preporukom', 'Vodi osobu prema proizvodnom cilju, popustu i kasnijem poslovnom mostu bez pritiska.', [
        $block('product_gateway_hero', 'headline', [
            'badge' => 'Proizvodi i popust',
            'title' => 'Kreni od proizvoda koji ti stvarno treba, a poslovni put može doći kasnije.',
            'text' => 'Ako te sada najviše zanimaju Forever proizvodi, odaberi cilj koji ti je najbliži. Dobit ćeš jednostavnu preporuku, mogućnost popusta i jasan sljedeći korak bez pritiska.',
            'title_size' => 50,
        ]),
        $block('product_goal', 'survey', [
            'title' => 'Što želiš prvo poboljšati?',
            'text' => 'Odaberi najbliži cilj i funnel će te voditi prema preporuci koja ima najviše smisla za tvoju situaciju.',
            'options' => [
                $action('product_energy', 'Više energije i dnevna vitalnost', 'vise_energije', 'product_recommendation', 'primary'),
                $action('product_weight', 'Regulacija težine i bolja forma', 'regulacija_tezine', 'product_recommendation', 'secondary'),
                $action('product_skin', 'Njega kože i osobna rutina', 'njega_koze', 'product_recommendation', 'secondary'),
                $action('product_routine', 'Opća dnevna rutina za mene ili obitelj', 'dnevna_rutina', 'product_recommendation', 'secondary'),
                $action('product_discount', 'Želim preporuku i popust', 'popust', 'product_recommendation', 'ghost'),
            ],
            'auto_advance' => true,
        ]),
    ], 'product_recommendation', ['design_variant' => 'stacked', 'block_mode' => 'choice']);

    $product_recommendation_blocks = [
        $block('product_recommendation_hero', 'headline', [
            'badge' => 'Preporuka',
            'title' => 'Ovo je najbolji prvi proizvodni korak za tvoj cilj',
            'text' => 'Preporuka prati odgovor koji si odabrao/la. Ako želiš samo kupnju, idi na proizvodni vodič ili shop. Ako želiš naučiti preporučivati online, otvori poslovni most.',
        ]),
    ];

    if($primary_product_key !== '') {
        $product_recommendation_blocks[] = $block('product_dynamic_offer', 'product_offer', [
            'badge' => 'Dinamička preporuka',
            'title' => 'Preporuka prema tvom odgovoru',
            'text' => 'Funnel koristi tvoj odabir i povezuje te s najbližim proizvodnim vodičem ili shop korakom.',
            'product_source_mode' => 'dynamic',
            'product_translation_key' => $primary_product_key,
            'product_language_mode' => 'page',
            'product_fallback_language_code' => 'hr',
            'product_primary_mode' => 'blog_guide',
            'product_primary_cta_text' => 'Pogledaj vodič proizvoda',
            'product_secondary_enabled' => true,
            'product_secondary_mode' => 'direct_shop',
            'product_secondary_cta_text' => 'Idi na službeni shop',
            'product_mappings' => [
                ['id' => 'map_energy', 'match_value' => 'vise_energije', 'product_translation_key' => $product_keys['energy'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_weight', 'match_value' => 'regulacija_tezine', 'product_translation_key' => $product_keys['weight'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_skin', 'match_value' => 'njega_koze', 'product_translation_key' => $product_keys['skin'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_routine', 'match_value' => 'dnevna_rutina', 'product_translation_key' => $product_keys['routine'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_discount', 'match_value' => 'popust', 'product_translation_key' => $product_keys['discount'] ?: $primary_product_key, 'product_blog_post_id' => 0],
            ],
        ]);
    } else {
        $product_recommendation_blocks[] = $block('product_manual_fallback', 'proof_card', [
            'badge' => 'Proizvodni vodič',
            'title' => 'Povezi ovaj korak s proizvodnim katalogom kada dodaš shop linkove.',
            'text' => 'Ako katalog još nije spreman, ovaj fallback vodi na opći proizvodni shop ili vodič.',
        ]);
    }

    $product_recommendation_blocks[] = $block('product_recommendation_actions', 'cta_group', [
        'buttons' => [
            $action('product_shop_external', 'Otvori proizvodni shop / vodič', 'product_shop', '', 'primary', 'external_url', false, $product_shop_url),
            $action('product_business_bridge', 'Želim naučiti preporučivati proizvode online', 'product_to_business', 'product_to_business_bridge', 'secondary'),
        ],
        'alignment' => 'center',
    ]);

    $product_recommendation = $step('product_recommendation', 'segment', 'products', 'offer', 'Ovo je najbolji prvi proizvodni korak za tvoj cilj', 'Dinamična proizvodna preporuka s mostom prema poslovnom putu.', $product_recommendation_blocks, 'product_to_business_bridge', ['design_variant' => 'card', 'block_mode' => 'product_offer']);

    $product_to_business_bridge = $step('product_to_business_bridge', 'experience', 'products', 'proof', 'Ako ti se sviđa proizvodni put, možeš ga pretvoriti u online preporuke', 'Most iz product/discount interesa u business funnel.', [
        $block('bridge_hero', 'headline', [
            'badge' => 'Most prema poslu',
            'title' => 'Mnogi krenu kroz proizvode, a kasnije shvate da isti sustav mogu koristiti za preporuke.',
            'text' => 'Ako te zanima kako od proizvoda doći do online preporuka, kontakata i vlastitog tima, otvori poslovni put.',
        ]),
        $block('bridge_video', 'video', ['title' => 'Kako proizvodni interes postaje poslovni sustav', 'media_url' => $video('bridge')]),
        $block('bridge_actions', 'cta_group', [
            'buttons' => [
                $action('bridge_business', 'Želim poslovni put', 'business_interest', 'business_gateway', 'primary'),
                $action('bridge_demo', 'Želim FCC demo', 'demo_interest', 'fcc_demo_preview', 'secondary'),
                $action('bridge_shop', 'Ostajem na proizvodima', 'product_shop', '', 'ghost', 'external_url', false, $product_shop_url),
            ],
            'alignment' => 'center',
        ]),
    ], 'business_gateway', ['design_variant' => 'proof_strip', 'block_mode' => 'video']);

    $not_ready_nurture = $step('not_ready_nurture', 'conversion', 'demo', 'follow_up', 'Nema pritiska. Poslat ću ti miran uvod i možeš se vratiti kad budeš spreman.', 'Nurture stranica za osobe koje nisu odmah spremne.', [
        $block('nurture_hero', 'headline', [
            'badge' => 'Miran nastavak',
            'title' => 'Nije cilj da svi kupe odmah. Cilj je da dobiješ pravi sljedeći korak.',
            'text' => 'Ako sada nije trenutak za 360 EUR start ili razgovor, pogledaj uvod i vrati se kad želiš demo, proizvodni put ili poslovni start.',
        ]),
        $block('nurture_video', 'video', ['title' => 'Kratki uvod bez pritiska', 'media_url' => $video('nurture')]),
        $block('nurture_email', 'email_field', ['title' => 'Email za uvodni video', 'placeholder' => 'Upiši email ako ga još nisi ostavio/la', 'required' => false, 'layout_width' => 'half']),
        $block('nurture_phone', 'phone_field', ['title' => 'Telefon / WhatsApp', 'placeholder' => 'Opcionalno za brzi kontakt', 'required' => false, 'layout_width' => 'half']),
        $block('nurture_actions', 'cta_group', [
            'buttons' => [
                $action('nurture_submit', 'Pošalji mi uvodni video', 'nurture_video', '', 'primary', 'submit_stay', true),
                $action('nurture_demo', 'Vrati me na demo', 'demo_interest', 'fcc_demo_preview', 'secondary'),
                $action('nurture_products', 'Želim proizvode', 'product_discount', 'product_gateway', 'ghost'),
            ],
            'alignment' => 'center',
        ]),
    ], '', ['design_variant' => 'card', 'block_mode' => 'contact_form']);

    return vip_funnel_normalize_studio_payload([
        'funnel' => [
            'name' => 'Stjepan Beloša - FCC Recruiting Funnel',
            'slug' => 'stjepan-online-posao',
            'status' => 'active',
            'visibility_mode' => 'pro_live',
            'owner_mode' => 'shared',
        ],
        'overview' => [
            'eyebrow' => 'Stjepan Beloša | Osobni FCC vodič',
            'headline' => 'Pokreni svoj FCC put uz moje osobno mentorstvo',
            'subheadline' => 'Došao/la si s mojih videa? Ovdje ćeš brzo vidjeti je li FCC za tebe, koji je tvoj najbolji prvi korak i kako možeš krenuti uz moje mentorstvo.',
            'primary_cta' => 'Provjeri svoj put',
            'secondary_cta' => 'Razumijem FCC sustav',
        ],
        'positioning' => [
            'for' => 'Za osobe koje dolaze s društvenih mreža i žele posao, demo FCC sustava ili proizvodni popust.',
            'problem' => 'Viralna pažnja se lako izgubi ako posjetitelj nema jasan sljedeći korak.',
            'mechanism' => 'Funnel segmentira posjetitelja, kvalificira spremnost i vodi ga prema Start paketu, razgovoru, demo iskustvu ili proizvodnoj preporuci.',
            'offer_promise' => 'Jasan put od interesa do odluke uz Stjepanovo mentorstvo i FCC sustav.',
            'why_now' => 'Publika već postoji; sada pažnju treba pretvoriti u mjerljiv i ponovljiv sustav.',
        ],
        'landing_page' => $landing_page,
        'paths' => [
            ['path_key' => 'business', 'title' => 'Online posao', 'description' => 'Put za osobe koje žele pokrenuti online posao uz FCC i mentorstvo.', 'sort_order' => 1, 'is_enabled' => true],
            ['path_key' => 'products', 'title' => 'Proizvodi i popust', 'description' => 'Put za osobe koje sada žele proizvode, preporuku ili popust.', 'sort_order' => 2, 'is_enabled' => true],
            ['path_key' => 'demo', 'title' => 'FCC demo i nurture', 'description' => 'Put za osobe koje prvo trebaju vidjeti sustav ili mirniji uvod.', 'sort_order' => 3, 'is_enabled' => true],
        ],
        'board' => [
            ['key' => 'entry', 'steps' => [$business_gateway, $fcc_demo_preview, $product_gateway]],
            ['key' => 'segment', 'steps' => [$qualification_form, $demo_request, $product_recommendation]],
            ['key' => 'experience', 'steps' => [$start_package_offer, $product_to_business_bridge]],
            ['key' => 'trust', 'steps' => [$hot_result, $mentor_call_request]],
            ['key' => 'conversion', 'steps' => [$not_ready_nurture]],
        ],
        'products' => [
            'intro' => 'Produktni put koristi survey selection i product_offer dynamic mapping gdje katalog ima povezane shop linkove.',
            'primary_offer_title' => 'Dinamična proizvodna preporuka',
            'primary_offer_text' => 'Preporuka prati cilj posjetitelja i vodi na blog vodič ili službeni shop.',
            'secondary_offer_title' => 'Most prema poslovnom putu',
            'secondary_offer_text' => 'Kupac koji pokaže interes može prijeći na FCC poslovni put.',
            'cta' => 'Otvori proizvodni put',
        ],
        'proof' => [
            'mentor_intro' => 'Stjepan je kreator FCC-a i mentor tima od 7.000+ članova.',
            'proof_1' => 'FCC daje jasan sustav za prezentaciju, preporuke i follow-up.',
            'proof_2' => 'Funnel selektira hot, warm, product i demo leadove.',
            'proof_3' => 'Svaka stranica je mala landing stranica s jasnom odlukom.',
            'faq_intro' => 'Najčešće sumnje rješavaju se kroz demo, Start paket i miran nurture put.',
        ],
        'follow_up' => [
            'cadence' => 'HOT: odmah/1/3 dana; WARM: 0/1/3 dana; PRODUCT: 0/2/5 dana; COLD: 0/5 dana',
            'message_1' => 'Bok, Stjepan ovdje. Vidio sam tvoj odabir i šaljem ti najbolji sljedeći korak.',
            'message_2' => 'Najveća razlika je krenuti sam ili uz sustav. FCC je napravljen da novi ljudi ne moraju sve izmišljati od nule.',
            'message_3' => 'Ako želiš, mogu ti u par minuta reći je li za tebe bolji Start paket, demo ili proizvodni put.',
        ],
        'demo' => [
            'micro_demo_label' => 'Brzi FCC demo',
            'sandbox_label' => 'Kontrolirani demo pregled',
            'approval_note' => 'Demo se koristi kao premium korak za osobe koje trebaju vidjeti sustav prije odluke.',
        ],
        'analytics' => [
            'primary_goal' => 'lead_capture',
            'ab_goal' => 'submit',
        ],
        'defaults' => [
            'owner_user_id' => (int) ($user->user_id ?? 0),
            'contact_email' => $contact_email,
            'checkout_url' => $checkout_url,
            'whatsapp_url' => $whatsapp_url,
            'calendar_url' => $calendar_url,
            'product_shop_url' => $product_shop_url,
            'privacy_url' => $privacy_url,
            'facebook_pixel_id' => $facebook_pixel_id,
            'hide_public_navbar' => true,
        ],
    ], $user);
}

function vip_funnel_normalize_list_items($value, int $limit = 8, int $max_length = 160): array {
    if(is_string($value)) {
        $value = preg_split('/\r\n|\r|\n/', $value);
    } elseif(is_object($value)) {
        $value = json_decode(json_encode($value), true);
    }

    if(!is_array($value)) {
        $value = [];
    }

    $value = array_slice($value, 0, $limit);
    $value = array_map(static function($item) use ($max_length) {
        return trim(input_clean((string) $item, $max_length));
    }, $value);

    return array_values(array_filter($value, static function($item) {
        return $item !== '';
    }));
}

function vip_funnel_normalize_button_options($value, array $default_options = []): array {
    if(is_string($value)) {
        $decoded = json_decode($value, true);
        $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
    } elseif(is_object($value)) {
        $value = json_decode(json_encode($value), true);
    }

    if(!is_array($value)) {
        $value = [];
    }

    $normalized = [];

    foreach(array_slice(array_values($value), 0, 8) as $index => $option) {
        $option = vip_funnel_to_array($option);
        $default_option = $default_options[$index] ?? [];
        $label = trim(input_clean((string) ($option['label'] ?? ($default_option['label'] ?? '')), 80));
        $next_step_id = trim(input_clean((string) ($option['next_step_id'] ?? ($default_option['next_step_id'] ?? '')), 128));
        $style = input_clean((string) ($option['style'] ?? ($default_option['style'] ?? 'primary')), 24);

        if($label === '') {
            continue;
        }

        if(!in_array($style, ['primary', 'secondary', 'ghost'], true)) {
            $style = 'primary';
        }

        $normalized[] = [
            'label' => $label,
            'next_step_id' => $next_step_id,
            'style' => $style,
        ];
    }

    if(empty($normalized)) {
        foreach(array_slice(array_values($default_options), 0, 8) as $default_option) {
            $default_option = vip_funnel_to_array($default_option);
            $label = trim(input_clean((string) ($default_option['label'] ?? ''), 80));

            if($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'next_step_id' => trim(input_clean((string) ($default_option['next_step_id'] ?? ''), 128)),
                'style' => in_array(($default_option['style'] ?? 'primary'), ['primary', 'secondary', 'ghost'], true) ? $default_option['style'] : 'primary',
            ];
        }
    }

    return $normalized;
}

function vip_funnel_get_studio_seed_board_payload(): array {
    return [
        [
            'key' => 'entry',
            'steps' => [
                [
                    'id' => 'entry_offer',
                    'path_key' => 'business',
                    'row_key' => 'business',
                    'card_type' => 'offer',
                    'title' => l('vip_funnel.board.step.entry_title'),
                    'summary' => l('vip_funnel.board.step.entry_summary'),
                    'helper_text' => 'Prvi korak mora biti kratak, jasan i emocionalno snažan. Ovdje osoba odmah treba osjetiti da postoji jednostavniji put.',
                    'cta' => l('vip_funnel.board.step.entry_cta'),
                    'next' => l('vip_funnel.board.step.entry_next'),
                    'next_step_id' => 'segment_choice',
                    'status_key' => 'core',
                    'media_url' => '',
                    'answers' => [],
                    'tags' => ['first_impression', 'promise'],
                    'owner_user_id' => 0,
                    'visibility_key' => 'all',
                    'analytics_label' => 'entry_offer',
                    'design_variant' => 'spotlight',
                    'preview_badge' => 'Početak',
                    'preview_headline' => l('vip_funnel.board.step.entry_title'),
                    'preview_body' => l('vip_funnel.board.step.entry_summary'),
                    'block_mode' => 'message',
                    'background_color' => '#152132',
                    'text_color' => '#eef4ff',
                    'accent_color' => '#67d8c9',
                    'button_options' => [
                        ['label' => 'Kreni dalje', 'next_step_id' => 'segment_choice', 'style' => 'primary'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'segment',
            'steps' => [
                [
                    'id' => 'segment_choice',
                    'path_key' => 'business',
                    'row_key' => 'business',
                    'card_type' => 'question',
                    'title' => l('vip_funnel.board.step.segment_title'),
                    'summary' => l('vip_funnel.board.step.segment_summary'),
                    'helper_text' => 'Ovdje se osoba usmjerava na pravi put bez kaosa. Pitanje mora biti kratko i intuitivno.',
                    'cta' => l('vip_funnel.board.step.segment_cta'),
                    'next' => l('vip_funnel.board.step.segment_next'),
                    'next_step_id' => 'experience_demo',
                    'status_key' => 'core',
                    'media_url' => '',
                    'answers' => ['Želim online posao', 'Zanimaju me proizvodi', 'Želim najprije vidjeti demo'],
                    'tags' => ['segmentation', 'qualification'],
                    'owner_user_id' => 0,
                    'visibility_key' => 'all',
                    'analytics_label' => 'segment_choice',
                    'design_variant' => 'stacked',
                    'preview_badge' => 'Usmjeravanje',
                    'preview_headline' => l('vip_funnel.board.step.segment_title'),
                    'preview_body' => l('vip_funnel.board.step.segment_summary'),
                    'block_mode' => 'choice',
                    'background_color' => '#1d2235',
                    'text_color' => '#eef4ff',
                    'accent_color' => '#f4b63f',
                    'button_options' => [
                        ['label' => 'Online posao', 'next_step_id' => 'experience_demo', 'style' => 'primary'],
                        ['label' => 'Proizvodi', 'next_step_id' => 'experience_demo', 'style' => 'secondary'],
                        ['label' => 'Želim demo', 'next_step_id' => 'experience_demo', 'style' => 'ghost'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'experience',
            'steps' => [
                [
                    'id' => 'experience_demo',
                    'path_key' => 'demo',
                    'row_key' => 'demo',
                    'card_type' => 'demo',
                    'title' => l('vip_funnel.board.step.experience_title'),
                    'summary' => l('vip_funnel.board.step.experience_summary'),
                    'helper_text' => 'Ovaj korak daje stvarni doživljaj. Ne prikazuj sve, nego jedan snažan aha trenutak.',
                    'cta' => l('vip_funnel.board.step.experience_cta'),
                    'next' => l('vip_funnel.board.step.experience_next'),
                    'next_step_id' => 'trust_proof',
                    'status_key' => 'core',
                    'media_url' => '',
                    'answers' => [],
                    'tags' => ['demo', 'experience'],
                    'owner_user_id' => 0,
                    'visibility_key' => 'all',
                    'analytics_label' => 'experience_demo',
                    'design_variant' => 'card',
                    'preview_badge' => 'Aha trenutak',
                    'preview_headline' => l('vip_funnel.board.step.experience_title'),
                    'preview_body' => l('vip_funnel.board.step.experience_summary'),
                    'block_mode' => 'video',
                    'background_color' => '#172635',
                    'text_color' => '#eef4ff',
                    'accent_color' => '#7cc7ff',
                    'button_options' => [
                        ['label' => 'Želim nastaviti', 'next_step_id' => 'conversion_call', 'style' => 'primary'],
                        ['label' => 'Pošalji mi detalje', 'next_step_id' => 'conversion_call', 'style' => 'secondary'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'trust',
            'steps' => [
                [
                    'id' => 'trust_proof',
                    'path_key' => 'products',
                    'row_key' => 'products',
                    'card_type' => 'proof',
                    'title' => l('vip_funnel.board.step.trust_title'),
                    'summary' => l('vip_funnel.board.step.trust_summary'),
                    'helper_text' => 'Dodaj dokaz, mentorstvo i osjećaj sigurnosti. Ovdje se ruše sumnje i stvara povjerenje.',
                    'cta' => l('vip_funnel.board.step.trust_cta'),
                    'next' => l('vip_funnel.board.step.trust_next'),
                    'next_step_id' => 'conversion_call',
                    'status_key' => 'proof',
                    'media_url' => '',
                    'answers' => [],
                    'tags' => ['proof', 'trust'],
                    'owner_user_id' => 0,
                    'visibility_key' => 'qualified',
                    'analytics_label' => 'trust_proof',
                    'design_variant' => 'proof_strip',
                    'preview_badge' => 'Povjerenje',
                    'preview_headline' => l('vip_funnel.board.step.trust_title'),
                    'preview_body' => l('vip_funnel.board.step.trust_summary'),
                    'block_mode' => 'image',
                    'background_color' => '#1d2330',
                    'text_color' => '#eef4ff',
                    'accent_color' => '#f4b63f',
                    'button_options' => [
                        ['label' => 'Pošalji primjer', 'next_step_id' => 'conversion_call', 'style' => 'secondary'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'conversion',
            'steps' => [
                [
                    'id' => 'conversion_call',
                    'path_key' => 'business',
                    'row_key' => 'business',
                    'card_type' => 'cta',
                    'title' => l('vip_funnel.board.step.conversion_title'),
                    'summary' => l('vip_funnel.board.step.conversion_summary'),
                    'helper_text' => 'Završni korak vodi u razgovor, demo odobrenje, preporuku proizvoda ili upis.',
                    'cta' => l('vip_funnel.board.step.conversion_cta'),
                    'next' => l('vip_funnel.board.step.conversion_next'),
                    'status_key' => 'conversion',
                    'media_url' => '',
                    'answers' => [],
                    'tags' => ['decision', 'conversion'],
                    'owner_user_id' => 0,
                    'visibility_key' => 'qualified',
                    'analytics_label' => 'conversion_call',
                    'design_variant' => 'decision',
                    'preview_badge' => 'Odluka',
                    'preview_headline' => l('vip_funnel.board.step.conversion_title'),
                    'preview_body' => l('vip_funnel.board.step.conversion_summary'),
                    'block_mode' => 'contact_form',
                    'background_color' => '#101826',
                    'text_color' => '#eef4ff',
                    'accent_color' => '#67d8c9',
                    'button_options' => [
                        ['label' => 'Želim razgovor', 'next_step_id' => '', 'style' => 'primary'],
                        ['label' => 'Želim demo račun', 'next_step_id' => '', 'style' => 'secondary'],
                    ],
                ],
            ],
        ],
    ];
}

function vip_funnel_generate_step_id(string $phase_key, int $index = 0): string {
    $suffix = substr(str_replace('.', '', uniqid('', true)), -8);

    return $phase_key . '_step_' . max(1, $index + 1) . '_' . $suffix;
}

function vip_funnel_get_default_step_template(string $phase_key, int $index = 0): array {
    $seed_board = vip_funnel_get_studio_seed_board_payload();
    $seed_phase = null;

    foreach($seed_board as $phase) {
        if(($phase['key'] ?? '') === $phase_key) {
            $seed_phase = $phase;
            break;
        }
    }

    $seed_step = $seed_phase['steps'][0] ?? [];
    $phase_definitions = vip_funnel_get_phase_definitions();
    $phase_definition = $phase_definitions[$phase_key] ?? ['title' => ucfirst($phase_key)];

    $step = [
        'id' => vip_funnel_generate_step_id($phase_key, $index),
        'path_key' => (string) ($seed_step['path_key'] ?? 'business'),
        'row_key' => (string) ($seed_step['row_key'] ?? ($seed_step['path_key'] ?? 'main')),
        'card_type' => (string) ($seed_step['card_type'] ?? 'offer'),
        'title' => $index === 0 ? (string) ($seed_step['title'] ?? '') : trim(l('vip_funnel.board.new_step_title') . ' - ' . (string) ($phase_definition['title'] ?? ucfirst($phase_key))),
        'summary' => $index === 0 ? (string) ($seed_step['summary'] ?? '') : l('vip_funnel.board.new_step_summary'),
        'helper_text' => (string) ($seed_step['helper_text'] ?? ''),
        'cta' => $index === 0 ? (string) ($seed_step['cta'] ?? '') : l('vip_funnel.board.new_step_cta'),
        'next' => $index === 0 ? (string) ($seed_step['next'] ?? '') : l('vip_funnel.board.new_step_next'),
        'status_key' => (string) ($seed_step['status_key'] ?? 'core'),
        'next_step_id' => (string) ($seed_step['next_step_id'] ?? ''),
        'media_url' => (string) ($seed_step['media_url'] ?? ''),
        'answers' => vip_funnel_normalize_list_items($seed_step['answers'] ?? []),
        'tags' => vip_funnel_normalize_list_items($seed_step['tags'] ?? [], 8, 40),
        'owner_user_id' => (int) ($seed_step['owner_user_id'] ?? 0),
        'visibility_key' => (string) ($seed_step['visibility_key'] ?? 'all'),
        'analytics_label' => (string) ($seed_step['analytics_label'] ?? vip_funnel_generate_step_id($phase_key, $index)),
        'design_variant' => (string) ($seed_step['design_variant'] ?? 'card'),
        'preview_badge' => (string) ($seed_step['preview_badge'] ?? ''),
        'preview_headline' => (string) ($seed_step['preview_headline'] ?? ($seed_step['title'] ?? '')),
        'preview_body' => (string) ($seed_step['preview_body'] ?? ($seed_step['summary'] ?? '')),
        'block_mode' => (string) ($seed_step['block_mode'] ?? 'message'),
        'background_color' => (string) ($seed_step['background_color'] ?? '#152132'),
        'text_color' => (string) ($seed_step['text_color'] ?? '#eef4ff'),
        'accent_color' => (string) ($seed_step['accent_color'] ?? '#67d8c9'),
        'button_options' => vip_funnel_normalize_button_options($seed_step['button_options'] ?? []),
    ];

    $step['page'] = vip_funnel_build_surface_from_legacy_step($step, $step['title']);

    return $step;
}

function vip_funnel_normalize_step_payload($step, string $phase_key, int $index = 0): array {
    $step = vip_funnel_to_array($step);
    $default_step = vip_funnel_get_default_step_template($phase_key, $index);
    $status_options = vip_funnel_get_step_status_options();
    $card_type_options = vip_funnel_get_card_type_options();
    $visibility_options = vip_funnel_get_visibility_options();
    $design_variant_options = vip_funnel_get_design_variant_options();
    $available_path_keys = array_column(vip_funnel_get_default_paths_payload(), 'path_key');

    $step_id = input_clean((string) ($step['id'] ?? $default_step['id']), 128);
    $step_id = preg_replace('/[^a-z0-9_]+/i', '_', $step_id) ?: $default_step['id'];
    $step_id = trim($step_id, '_');
    $path_key = input_clean((string) ($step['path_key'] ?? $default_step['path_key']), 64);
    $row_key = input_clean((string) ($step['row_key'] ?? $default_step['row_key']), 64);
    $card_type = input_clean((string) ($step['card_type'] ?? $default_step['card_type']), 64);

    $title = trim(input_clean((string) ($step['title'] ?? $default_step['title']), 120));
    $summary = trim(strip_tags(mb_substr((string) ($step['summary'] ?? $default_step['summary']), 0, 420)));
    $helper_text = trim(strip_tags(mb_substr((string) ($step['helper_text'] ?? $default_step['helper_text']), 0, 420)));
    $cta = trim(input_clean((string) ($step['cta'] ?? $default_step['cta']), 140));
    $next = trim(input_clean((string) ($step['next'] ?? $default_step['next']), 140));
    $status_key = input_clean((string) ($step['status_key'] ?? $default_step['status_key']), 32);
    $next_step_id = trim(input_clean((string) ($step['next_step_id'] ?? $default_step['next_step_id']), 128));
    $media_url = trim(input_clean((string) ($step['media_url'] ?? $default_step['media_url']), 2048));
    $answers = vip_funnel_normalize_list_items($step['answers'] ?? $default_step['answers'] ?? []);
    $tags = vip_funnel_normalize_list_items($step['tags'] ?? $default_step['tags'] ?? [], 8, 40);
    $owner_user_id = (int) ($step['owner_user_id'] ?? $default_step['owner_user_id']);
    $visibility_key = input_clean((string) ($step['visibility_key'] ?? $default_step['visibility_key']), 32);
    $analytics_label = trim(input_clean((string) ($step['analytics_label'] ?? $default_step['analytics_label']), 120));
    $design_variant = input_clean((string) ($step['design_variant'] ?? $default_step['design_variant']), 32);
    $preview_badge = trim(input_clean((string) ($step['preview_badge'] ?? $default_step['preview_badge']), 80));
    $preview_headline = trim(input_clean((string) ($step['preview_headline'] ?? $default_step['preview_headline']), 140));
    $preview_body = trim(strip_tags(mb_substr((string) ($step['preview_body'] ?? $default_step['preview_body']), 0, 280)));
    $block_mode = input_clean((string) ($step['block_mode'] ?? $default_step['block_mode']), 32);
    $background_color = (string) ($step['background_color'] ?? $default_step['background_color']);
    $text_color = (string) ($step['text_color'] ?? $default_step['text_color']);
    $accent_color = (string) ($step['accent_color'] ?? $default_step['accent_color']);
    $button_options = vip_funnel_normalize_button_options($step['button_options'] ?? [], $default_step['button_options'] ?? []);
    $page_surface = vip_funnel_normalize_page_surface_payload($step['page'] ?? vip_funnel_build_surface_from_legacy_step(array_replace($default_step, $step), $title !== '' ? $title : ($default_step['title'] ?? 'Funnel stranica')), $title !== '' ? $title : ($default_step['title'] ?? 'Funnel stranica'));
    if(empty($page_surface['blocks'])) {
        $page_surface = vip_funnel_build_surface_from_legacy_step(array_replace($default_step, $step), $title !== '' ? $title : ($default_step['title'] ?? 'Funnel stranica'));
    }
    $page_surface = vip_funnel_upgrade_surface_for_legacy_step($page_surface, array_replace($default_step, $step));

    if(empty($button_options) && !empty($answers)) {
        $button_options = vip_funnel_normalize_button_options(array_map(static function($answer) {
            return [
                'label' => $answer,
                'next_step_id' => '',
                'style' => 'secondary',
            ];
        }, $answers), $default_step['button_options'] ?? []);
    }

    if(!array_key_exists($status_key, $status_options)) {
        $status_key = $default_step['status_key'] ?? 'core';
    }

    if(!in_array($path_key, $available_path_keys, true)) {
        $path_key = $default_step['path_key'] ?? 'business';
    }

    if($row_key === '') {
        $row_key = $path_key !== '' ? $path_key : ($default_step['row_key'] ?? 'main');
    }

    if(!array_key_exists($card_type, $card_type_options)) {
        $card_type = $default_step['card_type'] ?? 'offer';
    }

    if(!array_key_exists($visibility_key, $visibility_options)) {
        $visibility_key = $default_step['visibility_key'] ?? 'all';
    }

    if(!array_key_exists($design_variant, $design_variant_options)) {
        $design_variant = $default_step['design_variant'] ?? 'card';
    }

    if(!array_key_exists($block_mode, vip_funnel_get_block_mode_options())) {
        $block_mode = $default_step['block_mode'] ?? 'message';
    }

    $background_color = verify_hex_color($background_color) ? $background_color : ($default_step['background_color'] ?? '#152132');
    $text_color = verify_hex_color($text_color) ? $text_color : ($default_step['text_color'] ?? '#eef4ff');
    $accent_color = verify_hex_color($accent_color) ? $accent_color : ($default_step['accent_color'] ?? '#67d8c9');

    return [
        'id' => $step_id !== '' ? $step_id : $default_step['id'],
        'path_key' => $path_key,
        'row_key' => $row_key,
        'card_type' => $card_type,
        'title' => $title !== '' ? $title : $default_step['title'],
        'summary' => $summary !== '' ? $summary : $default_step['summary'],
        'helper_text' => $helper_text,
        'cta' => $cta !== '' ? $cta : $default_step['cta'],
        'next' => $next !== '' ? $next : $default_step['next'],
        'status_key' => $status_key,
        'next_step_id' => $next_step_id,
        'media_url' => $media_url,
        'answers' => $answers,
        'tags' => $tags,
        'owner_user_id' => $owner_user_id,
        'visibility_key' => $visibility_key,
        'analytics_label' => $analytics_label !== '' ? $analytics_label : ($title !== '' ? strtolower(str_replace(' ', '_', $title)) : $default_step['analytics_label']),
        'design_variant' => $design_variant,
        'preview_badge' => $preview_badge,
        'preview_headline' => $preview_headline !== '' ? $preview_headline : ($title !== '' ? $title : $default_step['preview_headline']),
        'preview_body' => $preview_body !== '' ? $preview_body : ($summary !== '' ? $summary : $default_step['preview_body']),
        'block_mode' => $block_mode,
        'background_color' => $background_color,
        'text_color' => $text_color,
        'accent_color' => $accent_color,
        'button_options' => $button_options,
        'page' => $page_surface,
    ];
}

function vip_funnel_normalize_board_payload($payload): array {
    $payload = vip_funnel_to_array($payload);

    if(isset($payload['board'])) {
        $payload = vip_funnel_to_array($payload['board']);
    }

    $phase_definitions = vip_funnel_get_phase_definitions();
    $payload_by_key = [];

    foreach($payload as $phase) {
        $phase = vip_funnel_to_array($phase);
        $phase_key = input_clean((string) ($phase['key'] ?? ''), 64);

        if($phase_key !== '' && array_key_exists($phase_key, $phase_definitions)) {
            $payload_by_key[$phase_key] = $phase;
        }
    }

    foreach(array_keys($phase_definitions) as $phase_key) {
        if(!isset($payload_by_key[$phase_key]) && array_key_exists($phase_key, $payload)) {
            $payload_by_key[$phase_key] = ['key' => $phase_key, 'steps' => vip_funnel_to_array($payload[$phase_key]['steps'] ?? $payload[$phase_key])];
        }
    }

    $normalized_board = [];

    foreach($phase_definitions as $phase_key => $phase_definition) {
        $phase_payload = vip_funnel_to_array($payload_by_key[$phase_key] ?? []);
        $phase_steps = vip_funnel_to_array($phase_payload['steps'] ?? []);
        $normalized_steps = [];
        $used_ids = [];

        foreach(array_values($phase_steps) as $step_index => $step) {
            $normalized_step = vip_funnel_normalize_step_payload($step, $phase_key, $step_index);

            if(in_array($normalized_step['id'], $used_ids, true)) {
                $normalized_step['id'] = vip_funnel_generate_step_id($phase_key, $step_index);
            }

            $used_ids[] = $normalized_step['id'];
            $normalized_steps[] = $normalized_step;
        }

        $normalized_board[] = [
            'key' => $phase_key,
            'steps' => $normalized_steps,
        ];
    }

    return $normalized_board;
}

function vip_funnel_hydrate_board_for_view(array $board_payload): array {
    $phase_definitions = vip_funnel_get_phase_definitions();
    $status_options = vip_funnel_get_step_status_options();
    $board_payload = vip_funnel_normalize_board_payload($board_payload);
    $hydrated_board = [];

    foreach($board_payload as $phase) {
        $phase_key = (string) ($phase['key'] ?? '');
        $phase_definition = $phase_definitions[$phase_key] ?? ['title' => ucfirst($phase_key), 'subtitle' => ''];
        $steps = [];

        foreach((array) ($phase['steps'] ?? []) as $step) {
            $step = vip_funnel_to_array($step);
            $step['status_key'] = (string) ($step['status_key'] ?? 'core');
            $step['status'] = $status_options[$step['status_key']] ?? $status_options['core'];
            $step['card_type_label'] = vip_funnel_get_card_type_options()[(string) ($step['card_type'] ?? 'offer')] ?? 'Card';
            $step['goal_label'] = vip_funnel_get_goal_options()[(string) ($step['card_type'] ?? 'offer')] ?? $step['card_type_label'];
            $step['block_mode_label'] = vip_funnel_get_block_mode_options()[(string) ($step['block_mode'] ?? 'message')] ?? 'Tekstualni blok';
            $step['visibility_label'] = vip_funnel_get_visibility_options()[(string) ($step['visibility_key'] ?? 'all')] ?? 'Visible';
            $step['design_variant_label'] = vip_funnel_get_design_variant_options()[(string) ($step['design_variant'] ?? 'card')] ?? 'Card focus';
            $step['button_options'] = vip_funnel_normalize_button_options($step['button_options'] ?? [], []);
            $steps[] = $step;
        }

        $hydrated_board[] = [
            'key' => $phase_key,
            'title' => $phase_definition['title'],
            'subtitle' => $phase_definition['subtitle'],
            'steps' => $steps,
        ];
    }

    return $hydrated_board;
}

function vip_funnel_get_studio_seed_board(): array {
    return vip_funnel_hydrate_board_for_view(vip_funnel_get_studio_seed_board_payload());
}

function vip_funnel_get_simple_builder_phase_keys(): array {
    return ['entry', 'segment', 'experience', 'conversion'];
}

function vip_funnel_get_simple_builder_steps(array $board_payload): array {
    $board = vip_funnel_hydrate_board_for_view($board_payload);
    $board_by_key = [];

    foreach($board as $phase) {
        $board_by_key[$phase['key'] ?? ''] = $phase;
    }

    $steps = [];

    foreach(vip_funnel_get_simple_builder_phase_keys() as $phase_key) {
        $phase = $board_by_key[$phase_key] ?? ['key' => $phase_key, 'title' => ucfirst($phase_key), 'subtitle' => '', 'steps' => []];
        $primary_step = $phase['steps'][0] ?? vip_funnel_normalize_step_payload([], $phase_key, 0);
        $steps[] = [
            'phase_key' => $phase_key,
            'phase_title' => $phase['title'] ?? ucfirst($phase_key),
            'phase_subtitle' => $phase['subtitle'] ?? '',
            'step' => $primary_step,
        ];
    }

    return $steps;
}

function vip_funnel_get_user_studio_board_payload($user = null): array {
    $preferences = vip_funnel_get_user_preferences($user);
    $studio_preferences = vip_funnel_normalize_object($preferences->vip_funnel_studio ?? []);
    $board_payload = $studio_preferences->board ?? vip_funnel_get_studio_seed_board_payload();

    return vip_funnel_normalize_board_payload($board_payload);
}

function vip_funnel_get_user_studio_board($user = null): array {
    return vip_funnel_hydrate_board_for_view(vip_funnel_get_user_studio_board_payload($user));
}

function vip_funnel_set_user_studio_board_preferences($preferences, $board_payload): \stdClass {
    $preferences = vip_funnel_normalize_object($preferences);
    $preferences->vip_funnel_studio = (object) [
        'version' => 1,
        'updated_at' => get_date(),
        'board' => vip_funnel_normalize_board_payload($board_payload),
    ];

    return $preferences;
}

function vip_funnel_save_user_preferences(int $user_id, \stdClass $preferences): bool {
    if($user_id <= 0) {
        return false;
    }

    $result = db()->where('user_id', $user_id)->update('users', [
        'preferences' => json_encode($preferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
    ]);

    if(!$result || !empty(database()->error)) {
        return false;
    }

    cache()->deleteItemsByTag('user_id=' . $user_id);
    cache()->deleteItem('user?user_id=' . $user_id);

    return true;
}

function vip_funnel_normalize_image_gallery_entries($entries): array {
    $entries = vip_funnel_to_array($entries);
    $normalized_entries = [];
    $seen_urls = [];

    foreach($entries as $entry) {
        $entry = vip_funnel_to_array($entry);
        $image = trim((string) ($entry['image'] ?? ''));
        $image_url = trim((string) ($entry['image_url'] ?? ''));
        $created_at = trim((string) ($entry['created_at'] ?? ''));

        if($image_url === '' && $image !== '') {
            $image_url = \Altum\Uploads::get_full_url('vip_funnel_images') . $image;
        }

        if($image_url === '') {
            continue;
        }

        if(isset($seen_urls[$image_url])) {
            continue;
        }

        $seen_urls[$image_url] = true;

        $normalized_entries[] = [
            'image' => $image,
            'image_url' => $image_url,
            'created_at' => $created_at ?: get_date(),
        ];
    }

    return array_slice($normalized_entries, 0, 60);
}

function vip_funnel_collect_image_gallery_entries_from_payload($payload): array {
    $entries = [];

    $walk = function($node) use (&$walk, &$entries) {
        if(is_object($node)) {
            $node = json_decode(json_encode($node), true);
        }

        if(!is_array($node)) {
            return;
        }

        if(($node['type'] ?? '') === 'image' && !empty($node['media_url'])) {
            $entries[] = [
                'image' => basename((string) ($node['media_url'] ?? '')),
                'image_url' => (string) ($node['media_url'] ?? ''),
                'created_at' => get_date(),
            ];
        }

        foreach($node as $value) {
            if(is_array($value) || is_object($value)) {
                $walk($value);
            }
        }
    };

    $walk($payload);

    return vip_funnel_normalize_image_gallery_entries($entries);
}

function vip_funnel_get_image_gallery_entries($user = null, array $payload = []): array {
    $preferences = vip_funnel_get_user_preferences($user);
    $entries = vip_funnel_normalize_image_gallery_entries($preferences->vip_funnel_image_gallery ?? []);

    if(!empty($payload)) {
        $entries = vip_funnel_normalize_image_gallery_entries(array_merge(
            vip_funnel_collect_image_gallery_entries_from_payload($payload),
            $entries
        ));
    }

    return $entries;
}

function vip_funnel_set_image_gallery_entries_preferences($preferences, array $entries): \stdClass {
    $preferences = vip_funnel_normalize_object($preferences);
    $preferences->vip_funnel_image_gallery = vip_funnel_normalize_image_gallery_entries($entries);

    return $preferences;
}

function vip_funnel_register_image_in_gallery($user = null, string $image = '', string $image_url = ''): bool {
    $user_id = (int) ($user->user_id ?? 0);

    if($user_id <= 0 || $image_url === '') {
        return false;
    }

    $preferences = vip_funnel_get_user_preferences($user);
    $entries = vip_funnel_get_image_gallery_entries($user);

    array_unshift($entries, [
        'image' => $image,
        'image_url' => $image_url,
        'created_at' => get_date(),
    ]);

    $preferences = vip_funnel_set_image_gallery_entries_preferences($preferences, $entries);

    return vip_funnel_save_user_preferences($user_id, $preferences);
}

function vip_funnel_get_user_studio_full_payload($user = null): array {
    $preferences = vip_funnel_get_user_preferences($user);
    $studio_full = vip_funnel_normalize_object($preferences->vip_funnel_studio_full ?? []);
    $payload = vip_funnel_to_array($studio_full->payload ?? []);

    if(empty($payload)) {
        $payload = ['board' => vip_funnel_get_user_studio_board_payload($user)];
    }

    return vip_funnel_normalize_studio_payload($payload, $user);
}

function vip_funnel_studio_schema_is_ready(): bool {
    foreach(['vip_funnels', 'vip_funnel_paths', 'vip_funnel_cards', 'vip_funnel_edges', 'vip_funnel_runs'] as $table) {
        if(!vip_funnel_has_table($table)) {
            return false;
        }
    }

    return true;
}

function vip_funnel_slugify(string $value, string $fallback = 'vip-funnel-2-0'): string {
    $value = strtr($value, [
        'Č' => 'C', 'Ć' => 'C', 'Ž' => 'Z', 'Š' => 'S', 'Đ' => 'D',
        'č' => 'c', 'ć' => 'c', 'ž' => 'z', 'š' => 's', 'đ' => 'd',
    ]);
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : $fallback;
}

function vip_funnel_get_studio_seed_payload($user = null): array {
    $owner_options = function_exists('vip_funnel_demo_get_owner_options') ? vip_funnel_demo_get_owner_options($user) : [];
    $default_owner_user_id = (int) array_key_first($owner_options ?: [((int) ($user->user_id ?? 0)) => '']);
    $landing_page = vip_funnel_normalize_page_surface_payload([
        'name' => l('vip_funnel.studio.landing.title'),
        'background_color' => '#0e1624',
        'surface_color' => '#151f31',
        'text_color' => '#eef4ff',
        'accent_color' => '#67d8c9',
        'show_progress' => false,
        'blocks' => [
            [
                'type' => 'headline',
                'badge' => 'Funnel 2.0',
                'title' => 'Jedan centralni sustav za prodaju, regrutaciju i doživljaj poslovne prilike',
                'text' => 'Složi put koji novu osobu vodi jasno, mirno i uvjerljivo od prvog interesa do razgovora, demo pristupa ili upisa.',
            ],
            [
                'type' => 'proof_card',
                'badge' => 'Početak',
                'title' => 'Prvi dojam mora biti jednostavan i moderan',
                'text' => 'Ovdje složi landing koji odmah daje osjećaj jasnoće, sigurnosti i logičnog sljedećeg koraka.',
            ],
            [
                'type' => 'survey',
                'title' => 'Odaberi svoj smjer',
                'text' => 'Svaki odgovor može voditi na drugi prilagođeni korak funnela.',
                'options' => [
                    ['label' => 'Online posao', 'value' => 'online_posao', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'segment_choice'],
                    ['label' => 'Proizvodi', 'value' => 'proizvodi', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'trust_proof'],
                    ['label' => 'Želim demo', 'value' => 'zelim_demo', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'experience_demo'],
                ],
            ],
        ],
    ], l('vip_funnel.studio.landing.title'));

    return vip_funnel_normalize_studio_payload([
        'funnel' => [
            'name' => 'VIP Funnel 2.0',
            'slug' => 'vip-funnel-2-0',
            'status' => 'draft',
            'visibility_mode' => 'testing_locked',
            'owner_mode' => 'shared',
        ],
        'overview' => [
            'eyebrow' => 'Funnel 2.0',
            'headline' => 'Jedan centralni sustav za prodaju, regrutaciju i doživljaj poslovne prilike',
            'subheadline' => 'Složi put koji novu osobu vodi jasno, mirno i uvjerljivo od prvog interesa do razgovora, demo pristupa ili upisa.',
            'primary_cta' => 'Odaberi svoj smjer',
            'secondary_cta' => 'Zatraži kratki VIP pregled',
        ],
        'positioning' => [
            'for' => 'Za nove osobe koje žele pokrenuti online posao, bolje razumjeti Forever ili najprije pronaći pravi proizvodni put.',
            'problem' => 'Ljudi često vide previše informacija odjednom i zato ne osjete sustav dovoljno jasno da donesu odluku.',
            'mechanism' => 'Funnel 2.0 vodi osobu kroz kratak, vođen i emocionalno jasan put: segmentacija, doživljaj, dokaz, odluka.',
            'offer_promise' => 'Pokazati osobi pravi sljedeći korak bez kaosa i stvoriti osjećaj da ovo može stvarno koristiti u svom životu ili poslu.',
            'why_now' => 'Danas ljudi ne kupuju kompleksnost. Kupuju jasnoću, iskustvo i osjećaj da ih netko vodi kroz pravi put.',
        ],
        'landing_page' => $landing_page,
        'paths' => vip_funnel_get_default_paths_payload(),
        'board' => vip_funnel_get_studio_seed_board_payload(),
        'products' => [
            'intro' => 'Ovdje definiraj kako ćeš jednostavno i prirodno preporučivati proizvode unutar funnel iskustva.',
            'primary_offer_title' => 'Ulazni proizvodni fokus',
            'primary_offer_text' => 'Jedna jasna preporuka koja je najlakša za prvu odluku i početno iskustvo.',
            'secondary_offer_title' => 'Nastavak preporuke',
            'secondary_offer_text' => 'Drugi korak za osobu koja pokaže ozbiljniji interes ili želi šire rješenje.',
            'cta' => 'Želim preporuku za sebe',
        ],
        'proof' => [
            'mentor_intro' => 'Ovdje objasni zašto je ovaj put siguran, vođen i zašto osoba ne ostaje sama nakon odluke.',
            'proof_1' => 'Jasan sustav vodi osobu korak po korak bez preopterećenja.',
            'proof_2' => 'Demo i mentorstvo pomažu osobi da stvarno doživi sustav, a ne samo čita o njemu.',
            'proof_3' => 'Funnel 2.0 spaja prodaju proizvoda, regrutaciju i follow-up u jedan model.',
            'faq_intro' => 'Dodaj odgovore na najčešće sumnje kako bi povjerenje raslo prije razgovora.',
        ],
        'follow_up' => [
            'cadence' => 'Dan 0, Dan 1, Dan 3',
            'message_1' => 'Hvala ti što si prošao VIP put. Ako želiš, sljedeći korak je kratki pregled prilike i sustava koji je najlogičniji baš za tebe.',
            'message_2' => 'Javljam ti se jer mnogi tek nakon kratkog razgovora stvarno povežu kako im ovaj sustav može pomoći u prodaji ili pokretanju posla.',
            'message_3' => 'Ako želiš, mogu ti pokazati najjednostavniji ulazni korak bez pritiska i bez viška informacija.',
        ],
        'demo' => [
            'micro_demo_label' => 'Brzi micro demo bez kaosa',
            'sandbox_label' => '5-dnevni sandbox doživljaj iznutra',
            'approval_note' => 'Demo ostaje premium i kontroliran, s ručnim odobrenjem i jasnim sljedećim korakom.',
        ],
        'analytics' => [
            'primary_goal' => 'lead_capture',
            'ab_goal' => 'submit',
        ],
        'defaults' => [
            'owner_user_id' => $default_owner_user_id,
            'hide_public_navbar' => false,
        ],
    ], $user);
}

function vip_funnel_normalize_paths_payload($paths): array {
    $paths = vip_funnel_to_array($paths);
    $seed_paths = vip_funnel_get_default_paths_payload();
    $available_keys = array_column($seed_paths, 'path_key');
    $normalized = [];
    $used = [];

    foreach(array_values($paths) as $index => $path) {
        $path = vip_funnel_to_array($path);
        $path_key = input_clean((string) ($path['path_key'] ?? ''), 64);

        if($path_key === '' || in_array($path_key, $used, true)) {
            continue;
        }

        if(!in_array($path_key, $available_keys, true)) {
            $path_key = $seed_paths[$index]['path_key'] ?? null;
        }

        if(!$path_key || in_array($path_key, $used, true)) {
            continue;
        }

        $used[] = $path_key;
        $normalized[] = [
            'path_key' => $path_key,
            'title' => trim(input_clean((string) ($path['title'] ?? ($seed_paths[$index]['title'] ?? ucfirst($path_key))), 120)),
            'description' => trim(strip_tags(mb_substr((string) ($path['description'] ?? ($seed_paths[$index]['description'] ?? '')), 0, 240))),
            'sort_order' => max(1, (int) ($path['sort_order'] ?? ($index + 1))),
            'is_enabled' => isset($path['is_enabled']) ? (bool) $path['is_enabled'] : true,
        ];
    }

    foreach($seed_paths as $index => $seed_path) {
        if(!in_array($seed_path['path_key'], $used, true)) {
            $normalized[] = $seed_path;
        }
    }

    usort($normalized, static function($a, $b) {
        return (($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
    });

    return array_values($normalized);
}

function vip_funnel_normalize_studio_payload($payload, $user = null): array {
    $payload = vip_funnel_to_array($payload);
    $raw_landing_page = array_key_exists('landing_page', $payload) ? $payload['landing_page'] : null;
    $raw_paths = array_key_exists('paths', $payload) ? $payload['paths'] : null;
    $raw_board = array_key_exists('board', $payload) ? $payload['board'] : null;
    $seed = [
        'funnel' => [
            'name' => 'VIP Funnel 2.0',
            'slug' => 'vip-funnel-2-0',
            'status' => 'draft',
            'visibility_mode' => 'testing_locked',
            'owner_mode' => 'shared',
        ],
        'overview' => [
            'eyebrow' => '',
            'headline' => '',
            'subheadline' => '',
            'primary_cta' => '',
            'secondary_cta' => '',
        ],
        'positioning' => [
            'for' => '',
            'problem' => '',
            'mechanism' => '',
            'offer_promise' => '',
            'why_now' => '',
        ],
        'landing_page' => vip_funnel_get_default_page_surface_payload(l('vip_funnel.studio.landing.title')),
        'paths' => vip_funnel_get_default_paths_payload(),
        'board' => vip_funnel_get_studio_seed_board_payload(),
        'products' => [
            'intro' => '',
            'primary_offer_title' => '',
            'primary_offer_text' => '',
            'secondary_offer_title' => '',
            'secondary_offer_text' => '',
            'cta' => '',
        ],
        'proof' => [
            'mentor_intro' => '',
            'proof_1' => '',
            'proof_2' => '',
            'proof_3' => '',
            'faq_intro' => '',
        ],
        'follow_up' => [
            'cadence' => '',
            'message_1' => '',
            'message_2' => '',
            'message_3' => '',
        ],
        'demo' => [
            'micro_demo_label' => '',
            'sandbox_label' => '',
            'approval_note' => '',
        ],
        'analytics' => [
            'primary_goal' => 'lead_capture',
            'ab_goal' => 'submit',
        ],
        'defaults' => [
            'owner_user_id' => (int) ($user->user_id ?? 0),
            'hide_public_navbar' => false,
        ],
    ];

    $payload_without_list_sections = $payload;
    unset($payload_without_list_sections['landing_page'], $payload_without_list_sections['paths'], $payload_without_list_sections['board']);

    $payload = array_replace_recursive($seed, $payload_without_list_sections);
    $payload['funnel']['name'] = trim(input_clean((string) ($payload['funnel']['name'] ?? $seed['funnel']['name']), 120)) ?: $seed['funnel']['name'];
    $payload['funnel']['slug'] = vip_funnel_slugify((string) ($payload['funnel']['slug'] ?? $payload['funnel']['name']), $seed['funnel']['slug']);
    $payload['funnel']['status'] = in_array(($payload['funnel']['status'] ?? 'draft'), ['draft', 'active', 'testing'], true) ? (string) $payload['funnel']['status'] : 'draft';
    $payload['funnel']['visibility_mode'] = in_array(($payload['funnel']['visibility_mode'] ?? 'testing_locked'), ['testing_locked', 'pro_live', 'private'], true) ? (string) $payload['funnel']['visibility_mode'] : 'testing_locked';
    $payload['funnel']['owner_mode'] = in_array(($payload['funnel']['owner_mode'] ?? 'shared'), ['shared', 'assigned'], true) ? (string) $payload['funnel']['owner_mode'] : 'shared';

    foreach(['overview', 'positioning', 'products', 'proof', 'follow_up', 'demo', 'analytics'] as $section_key) {
        foreach((array) $payload[$section_key] as $field_key => $field_value) {
            $payload[$section_key][$field_key] = trim(strip_tags(mb_substr((string) $field_value, 0, 800)));
        }
    }

    $payload['landing_page'] = vip_funnel_normalize_page_surface_payload($raw_landing_page ?? $seed['landing_page'], l('vip_funnel.studio.landing.title'));
    $payload['paths'] = vip_funnel_normalize_paths_payload($raw_paths ?? $seed['paths']);
    $payload['board'] = vip_funnel_normalize_board_payload($raw_board ?? $seed['board']);
    $payload['defaults']['owner_user_id'] = (int) ($payload['defaults']['owner_user_id'] ?? ($user->user_id ?? 0));
    $payload['defaults']['hide_public_navbar'] = !empty($payload['defaults']['hide_public_navbar']);
    vip_funnel_refresh_stjepan_landing_copy_if_needed($payload);
    vip_funnel_complete_fcc_vip_landing_variant_b_if_needed($payload);

    return $payload;
}

function vip_funnel_studio_get_cards_from_payload(array $payload): array {
    $payload = vip_funnel_normalize_studio_payload($payload);
    $cards = [];

    foreach(($payload['board'] ?? []) as $phase) {
        foreach((array) ($phase['steps'] ?? []) as $sort_order => $step) {
            $cards[] = [
                'phase_key' => (string) ($phase['key'] ?? ''),
                'sort_order' => $sort_order + 1,
                'step' => vip_funnel_to_array($step),
            ];
        }
    }

    return $cards;
}

function vip_funnel_studio_get_primary_funnel_row(int $user_id = 0) {
    if($user_id <= 0 || !vip_funnel_studio_schema_is_ready()) {
        return null;
    }

    return db()->where('user_id', $user_id)->orderBy('vip_funnel_id', 'ASC')->getOne('vip_funnels');
}

function vip_funnel_studio_get_funnel_row(int $user_id = 0, int $funnel_id = 0) {
    if($user_id <= 0 || $funnel_id <= 0 || !vip_funnel_studio_schema_is_ready()) {
        return null;
    }

    return db()->where('user_id', $user_id)->where('vip_funnel_id', $funnel_id)->getOne('vip_funnels');
}

function vip_funnel_studio_get_funnel_row_by_slug(int $user_id = 0, string $slug = '') {
    if($user_id <= 0 || $slug === '' || !vip_funnel_studio_schema_is_ready()) {
        return null;
    }

    $slug = vip_funnel_slugify($slug);

    return db()->where('user_id', $user_id)->where('slug', $slug)->orderBy('vip_funnel_id', 'ASC')->getOne('vip_funnels');
}

function vip_funnel_studio_get_funnel_rows(int $user_id = 0): array {
    if($user_id <= 0 || !vip_funnel_studio_schema_is_ready()) {
        return [];
    }

    return db()->where('user_id', $user_id)->orderBy('last_datetime', 'DESC')->orderBy('vip_funnel_id', 'DESC')->get('vip_funnels') ?? [];
}

function vip_funnel_get_unique_slug_for_user(int $user_id = 0, string $slug = '', int $exclude_funnel_id = 0): string {
    $base_slug = vip_funnel_slugify($slug);

    if($user_id <= 0 || !vip_funnel_studio_schema_is_ready()) {
        return $base_slug;
    }

    $candidate = $base_slug;
    $counter = 2;

    while(true) {
        $query = db()->where('user_id', $user_id)->where('slug', $candidate);

        if($exclude_funnel_id > 0) {
            $query->where('vip_funnel_id', $exclude_funnel_id, '<>');
        }

        if(!$query->has('vip_funnels')) {
            return $candidate;
        }

        $candidate = $base_slug . '-' . $counter;
        $counter++;
    }
}

function vip_funnel_studio_get_funnel_row_public_data($funnel_row = null): array {
    if(!$funnel_row) {
        return [];
    }

    return [
        'vip_funnel_id' => (int) ($funnel_row->vip_funnel_id ?? 0),
        'user_id' => (int) ($funnel_row->user_id ?? 0),
        'name' => (string) ($funnel_row->name ?? ''),
        'slug' => (string) ($funnel_row->slug ?? ''),
        'status' => (string) ($funnel_row->status ?? ''),
        'visibility_mode' => (string) ($funnel_row->visibility_mode ?? ''),
        'owner_mode' => (string) ($funnel_row->owner_mode ?? ''),
        'datetime' => (string) ($funnel_row->datetime ?? ''),
        'last_datetime' => (string) ($funnel_row->last_datetime ?? ''),
    ];
}

function vip_funnel_studio_ensure_primary_funnel($user = null) {
    if(!$user || !vip_funnel_studio_schema_is_ready()) {
        return null;
    }

    $user_id = (int) ($user->user_id ?? 0);
    if($user_id <= 0) {
        return null;
    }

    $funnel = vip_funnel_studio_get_primary_funnel_row($user_id);

    if($funnel) {
        return $funnel;
    }

    $payload = vip_funnel_get_studio_seed_payload($user);
    $funnel_id = (int) db()->insert('vip_funnels', [
        'user_id' => $user_id,
        'name' => $payload['funnel']['name'],
        'slug' => $payload['funnel']['slug'],
        'status' => $payload['funnel']['status'],
        'visibility_mode' => $payload['funnel']['visibility_mode'],
        'owner_mode' => $payload['funnel']['owner_mode'],
        'settings' => vip_funnel_json_encode([
            'overview' => $payload['overview'],
            'positioning' => $payload['positioning'],
            'landing_page' => $payload['landing_page'],
            'products' => $payload['products'],
            'proof' => $payload['proof'],
            'follow_up' => $payload['follow_up'],
            'demo' => $payload['demo'],
            'analytics' => $payload['analytics'],
            'defaults' => $payload['defaults'],
        ]),
        'datetime' => get_date(),
        'last_datetime' => get_date(),
    ]);

    if($funnel_id <= 0) {
        return null;
    }

    vip_funnel_studio_save_to_database($user, $payload, $funnel_id);

    return db()->where('vip_funnel_id', $funnel_id)->getOne('vip_funnels');
}

function vip_funnel_studio_get_auto_next_step_id(array $board, int $phase_index, int $step_index, array $current_step): string {
    $current_path = (string) ($current_step['path_key'] ?? '');

    for($next_phase_index = $phase_index + 1; $next_phase_index < count($board); $next_phase_index++) {
        $candidate_steps = (array) ($board[$next_phase_index]['steps'] ?? []);

        foreach($candidate_steps as $candidate_step) {
            if($current_path !== '' && (string) ($candidate_step['path_key'] ?? '') === $current_path) {
                return (string) ($candidate_step['id'] ?? '');
            }
        }

        if(!empty($candidate_steps[0]['id'])) {
            return (string) $candidate_steps[0]['id'];
        }
    }

    return '';
}

function vip_funnel_studio_save_to_database($user = null, array $payload = [], int $funnel_id = 0): bool {
    if(!$user || !vip_funnel_studio_schema_is_ready()) {
        return false;
    }

    $payload = vip_funnel_normalize_studio_payload($payload, $user);
    $user_id = (int) ($user->user_id ?? 0);

    if($user_id <= 0) {
        return false;
    }

    $funnel_id = $funnel_id > 0 ? $funnel_id : (int) (vip_funnel_studio_ensure_primary_funnel($user)->vip_funnel_id ?? 0);

    if($funnel_id <= 0) {
        return false;
    }

    $payload['funnel']['slug'] = vip_funnel_get_unique_slug_for_user($user_id, (string) ($payload['funnel']['slug'] ?? $payload['funnel']['name'] ?? 'vip-funnel-2-0'), $funnel_id);

    db()->startTransaction();

    try {
        $updated = db()->where('vip_funnel_id', $funnel_id)->where('user_id', $user_id)->update('vip_funnels', [
            'name' => $payload['funnel']['name'],
            'slug' => $payload['funnel']['slug'],
            'status' => $payload['funnel']['status'],
            'visibility_mode' => $payload['funnel']['visibility_mode'],
            'owner_mode' => $payload['funnel']['owner_mode'],
            'settings' => vip_funnel_json_encode([
                'overview' => $payload['overview'],
                'positioning' => $payload['positioning'],
                'landing_page' => $payload['landing_page'],
                'products' => $payload['products'],
                'proof' => $payload['proof'],
                'follow_up' => $payload['follow_up'],
                'demo' => $payload['demo'],
                'analytics' => $payload['analytics'],
                'defaults' => $payload['defaults'],
            ]),
            'last_datetime' => get_date(),
        ]);

        if($updated === false || !empty(database()->error)) {
            throw new \Exception('vip_funnel_root_update_failed');
        }

        db()->where('vip_funnel_id', $funnel_id)->delete('vip_funnel_edges');
        db()->where('vip_funnel_id', $funnel_id)->delete('vip_funnel_cards');
        db()->where('vip_funnel_id', $funnel_id)->delete('vip_funnel_paths');

        $path_id_map = [];
        foreach(array_values($payload['paths']) as $path_index => $path) {
            $path_id = (int) db()->insert('vip_funnel_paths', [
                'vip_funnel_id' => $funnel_id,
                'path_key' => $path['path_key'],
                'title' => $path['title'],
                'description' => $path['description'],
                'sort_order' => $path_index + 1,
                'is_enabled' => $path['is_enabled'] ? 1 : 0,
            ]);

            if($path_id <= 0) {
                throw new \Exception('vip_funnel_path_insert_failed');
            }

            $path_id_map[$path['path_key']] = $path_id;
        }

        $card_id_map = [];

        foreach(array_values($payload['board']) as $phase_index => $phase) {
            foreach(array_values($phase['steps']) as $step_index => $step) {
                $step = vip_funnel_to_array($step);
                $path_key = (string) ($step['path_key'] ?? '');
                $card_id = (int) db()->insert('vip_funnel_cards', [
                    'vip_funnel_id' => $funnel_id,
                    'vip_funnel_path_id' => $path_id_map[$path_key] ?? null,
                    'phase_key' => (string) ($phase['key'] ?? ''),
                    'row_key' => (string) ($step['row_key'] ?? $path_key),
                    'card_type' => (string) ($step['card_type'] ?? 'offer'),
                    'title' => (string) ($step['title'] ?? ''),
                    'settings' => vip_funnel_json_encode([
                        'summary' => (string) ($step['summary'] ?? ''),
                        'helper_text' => (string) ($step['helper_text'] ?? ''),
                        'cta' => (string) ($step['cta'] ?? ''),
                        'next' => (string) ($step['next'] ?? ''),
                        'next_step_id' => (string) ($step['next_step_id'] ?? ''),
                        'status_key' => (string) ($step['status_key'] ?? 'core'),
                        'media_url' => (string) ($step['media_url'] ?? ''),
                        'answers' => vip_funnel_normalize_list_items($step['answers'] ?? []),
                        'tags' => vip_funnel_normalize_list_items($step['tags'] ?? [], 8, 40),
                        'owner_user_id' => (int) ($step['owner_user_id'] ?? 0),
                        'visibility_key' => (string) ($step['visibility_key'] ?? 'all'),
                        'analytics_label' => (string) ($step['analytics_label'] ?? ''),
                        'design_variant' => (string) ($step['design_variant'] ?? 'card'),
                        'preview_badge' => (string) ($step['preview_badge'] ?? ''),
                        'preview_headline' => (string) ($step['preview_headline'] ?? ''),
                        'preview_body' => (string) ($step['preview_body'] ?? ''),
                        'block_mode' => (string) ($step['block_mode'] ?? 'message'),
                        'background_color' => (string) ($step['background_color'] ?? '#152132'),
                        'text_color' => (string) ($step['text_color'] ?? '#eef4ff'),
                        'accent_color' => (string) ($step['accent_color'] ?? '#67d8c9'),
                        'button_options' => vip_funnel_normalize_button_options($step['button_options'] ?? [], []),
                        'step_id' => (string) ($step['id'] ?? ''),
                        'page' => vip_funnel_normalize_page_surface_payload($step['page'] ?? [], (string) ($step['title'] ?? 'Funnel stranica')),
                    ]),
                    'sort_order' => $step_index + 1,
                    'is_enabled' => 1,
                ]);

                if($card_id <= 0) {
                    throw new \Exception('vip_funnel_card_insert_failed');
                }

                $card_id_map[(string) ($step['id'] ?? '')] = $card_id;
            }
        }

        foreach(array_values($payload['board']) as $phase_index => $phase) {
            foreach(array_values($phase['steps']) as $step_index => $step) {
                $step = vip_funnel_to_array($step);
                $from_card_id = (int) ($card_id_map[(string) ($step['id'] ?? '')] ?? 0);

                if($from_card_id <= 0) {
                    continue;
                }

                $next_step_id = trim((string) ($step['next_step_id'] ?? ''));
                if($next_step_id === '') {
                    $next_step_id = vip_funnel_studio_get_auto_next_step_id($payload['board'], $phase_index, $step_index, $step);
                }

                $to_card_id = (int) ($card_id_map[$next_step_id] ?? 0);

                if($to_card_id <= 0) {
                    $to_card_id = 0;
                }

                if($to_card_id > 0) {
                    $edge_id = (int) db()->insert('vip_funnel_edges', [
                        'vip_funnel_id' => $funnel_id,
                        'from_card_id' => $from_card_id,
                        'to_card_id' => $to_card_id,
                        'edge_type' => 'default',
                        'condition_key' => '',
                        'condition_value' => '',
                    ]);

                    if($edge_id <= 0) {
                        throw new \Exception('vip_funnel_edge_insert_failed');
                    }
                }

                foreach(vip_funnel_extract_surface_actions(vip_funnel_to_array($step['page'] ?? [])) as $surface_action) {
                    $target_surface_step_id = (string) ($surface_action['target_step_id'] ?? '');
                    $target_surface_card_id = (int) ($card_id_map[$target_surface_step_id] ?? 0);

                    if($target_surface_card_id <= 0) {
                        continue;
                    }

                    $edge_id = (int) db()->insert('vip_funnel_edges', [
                        'vip_funnel_id' => $funnel_id,
                        'from_card_id' => $from_card_id,
                        'to_card_id' => $target_surface_card_id,
                        'edge_type' => (string) ($surface_action['edge_type'] ?? 'default'),
                        'condition_key' => (string) ($surface_action['condition_key'] ?? ''),
                        'condition_value' => (string) ($surface_action['condition_value'] ?? ''),
                    ]);

                    if($edge_id <= 0) {
                        throw new \Exception('vip_funnel_edge_insert_failed');
                    }
                }
            }
        }

        db()->commit();

        return true;
    } catch(\Throwable $exception) {
        db()->rollback();

        $db_error = method_exists(db(), 'getLastError') ? (string) db()->getLastError() : '';
        $db_errno = method_exists(db(), 'getLastErrno') ? (int) db()->getLastErrno() : 0;

        error_log(vip_funnel_json_encode([
            'channel' => 'vip_funnel_studio_save_failed',
            'user_id' => $user_id,
            'funnel_id' => $funnel_id,
            'exception' => $exception->getMessage(),
            'db_errno' => $db_errno,
            'db_error' => $db_error,
        ]));

        return false;
    }
}

function vip_funnel_get_import_template_languages(): array {
    return [
        'hr' => 'HR',
        'en' => 'ENG',
    ];
}

function vip_funnel_resolve_import_template_language(string $language = ''): string {
    $language = mb_strtolower(trim($language));

    return in_array($language, ['hr', 'en'], true) ? $language : 'hr';
}

function vip_funnel_get_import_template_options($user = null): array {
    return [
        'fcc_vip_complete' => [
            'key' => 'fcc_vip_complete',
            'name' => 'FCC VIP Funnel - kompletan sustav',
            'description' => 'Jedan veliki personalizirani funnel za regrutaciju, Start Your Journey, demo pristup, proizvode, popust i follow-up. Suradnik mijenja samo svoje videe, tekst i kontakt.',
            'badge' => 'FCC VIP',
            'goal' => 'Regrutacija + proizvodi + demo',
            'recommended' => true,
            'languages' => vip_funnel_get_import_template_languages(),
        ],
    ];
}

function vip_funnel_apply_template_landing(array $payload, string $language, array $copy): array {
    $is_hr = $language === 'hr';
    $payload['landing_page']['name'] = (string) ($copy['landing_name'] ?? $payload['landing_page']['name'] ?? 'Landing');
    $payload['landing_page']['blocks'] = [
        [
            'id' => vip_funnel_generate_page_block_id('headline'),
            'type' => 'headline',
            'badge' => (string) ($copy['badge'] ?? 'FCC Funnel 2.0'),
            'title' => (string) ($copy['headline'] ?? ''),
            'text' => (string) ($copy['subheadline'] ?? ''),
            'layout_width' => 'full',
            'alignment' => 'center',
        ],
        [
            'id' => vip_funnel_generate_page_block_id('proof'),
            'type' => 'proof_card',
            'badge' => (string) ($copy['proof_badge'] ?? ($is_hr ? 'Zašto sada' : 'Why now')),
            'title' => (string) ($copy['proof_title'] ?? ''),
            'text' => (string) ($copy['proof_text'] ?? ''),
            'layout_width' => 'half',
            'alignment' => 'left',
        ],
        [
            'id' => vip_funnel_generate_page_block_id('survey'),
            'type' => 'survey',
            'title' => (string) ($copy['survey_title'] ?? ($is_hr ? 'Što želiš prvo?' : 'What do you want first?')),
            'text' => (string) ($copy['survey_text'] ?? ($is_hr ? 'Odgovor usmjerava tvoj sljedeći korak.' : 'Your answer sends you to the best next step.')),
            'layout_width' => 'half',
            'alignment' => 'left',
            'options' => (array) ($copy['options'] ?? []),
        ],
    ];

    return $payload;
}

function vip_funnel_update_template_block(array &$payload, string $block_id, array $updates): void {
    $update_blocks = static function(&$blocks) use ($block_id, $updates): void {
        if(!is_array($blocks)) {
            return;
        }

        foreach($blocks as &$block) {
            if(!is_array($block)) {
                continue;
            }

            if((string) ($block['id'] ?? '') === $block_id) {
                $block = array_replace_recursive($block, $updates);
            }
        }
        unset($block);
    };

    if(isset($payload['landing_page']) && is_array($payload['landing_page'])) {
        if(isset($payload['landing_page']['blocks'])) {
            $update_blocks($payload['landing_page']['blocks']);
        }

        if(isset($payload['landing_page']['variant_b_blocks'])) {
            $update_blocks($payload['landing_page']['variant_b_blocks']);
        }
    }

    if(isset($payload['board']) && is_array($payload['board'])) {
        foreach($payload['board'] as &$phase) {
            if(!is_array($phase) || empty($phase['steps']) || !is_array($phase['steps'])) {
                continue;
            }

            foreach($phase['steps'] as &$step) {
                if(!is_array($step) || empty($step['page']) || !is_array($step['page'])) {
                    continue;
                }

                if(isset($step['page']['blocks'])) {
                    $update_blocks($step['page']['blocks']);
                }

                if(isset($step['page']['variant_b_blocks'])) {
                    $update_blocks($step['page']['variant_b_blocks']);
                }
            }
            unset($step);
        }
        unset($phase);
    }
}

function vip_funnel_sync_template_step_blocks(array &$payload, string $step_id, array $blocks_by_id, array $ordered_block_ids = [], array $remove_block_ids = []): void {
    $sync_blocks = static function(&$blocks) use ($blocks_by_id, $ordered_block_ids, $remove_block_ids): void {
        if(!is_array($blocks)) {
            return;
        }

        $remove_map = array_fill_keys($remove_block_ids, true);
        $indexed = [];
        $extras = [];

        foreach($blocks as $block) {
            if(!is_array($block)) {
                continue;
            }

            $block_id = (string) ($block['id'] ?? '');
            if($block_id === '' || isset($remove_map[$block_id])) {
                continue;
            }

            if(isset($blocks_by_id[$block_id])) {
                $indexed[$block_id] = array_replace_recursive($block, $blocks_by_id[$block_id]);

                foreach(['options', 'buttons'] as $list_key) {
                    if(array_key_exists($list_key, $blocks_by_id[$block_id])) {
                        $indexed[$block_id][$list_key] = $blocks_by_id[$block_id][$list_key];
                    }
                }
            } else {
                $extras[] = $block;
            }
        }

        foreach($blocks_by_id as $block_id => $updates) {
            if(!isset($indexed[$block_id])) {
                $indexed[$block_id] = $updates;
            }
        }

        $ordered = [];
        foreach($ordered_block_ids as $block_id) {
            if(isset($indexed[$block_id])) {
                $ordered[] = $indexed[$block_id];
                unset($indexed[$block_id]);
            }
        }

        foreach($indexed as $block) {
            $ordered[] = $block;
        }

        foreach($extras as $block) {
            $ordered[] = $block;
        }

        $blocks = $ordered;
    };

    if(!isset($payload['board']) || !is_array($payload['board'])) {
        return;
    }

    foreach($payload['board'] as &$phase) {
        if(!is_array($phase) || empty($phase['steps']) || !is_array($phase['steps'])) {
            continue;
        }

        foreach($phase['steps'] as &$step) {
            if(!is_array($step) || (string) ($step['id'] ?? '') !== $step_id || empty($step['page']) || !is_array($step['page'])) {
                continue;
            }

            if(isset($step['page']['blocks'])) {
                $sync_blocks($step['page']['blocks']);
            }

            if(!empty($step['page']['variant_b_blocks'])) {
                $sync_blocks($step['page']['variant_b_blocks']);
            }
        }
        unset($step);
    }
    unset($phase);
}

function vip_funnel_resolve_fast_contact_url(string $url = ''): string {
    $url = trim($url);

    if($url === '' || stripos($url, 'mailto:') === 0) {
        return '';
    }

    return $url;
}

function vip_funnel_get_demo_request_step_blocks(string $language = 'hr', string $mentor_name = '', string $privacy_url = ''): array {
    $is_en = vip_funnel_resolve_import_template_language($language) === 'en';
    $mentor_label = trim($mentor_name) !== '' ? trim($mentor_name) : ($is_en ? 'your mentor' : 'Stjepan');
    $privacy_url = trim($privacy_url) !== '' ? trim($privacy_url) : SITE_URL . 'page/privacy-policy';

    $action = static function(string $id, string $label, string $hint = '', string $value = ''): array {
        return [
            'id' => $id,
            'label' => $label,
            'hint' => $hint,
            'value' => $value !== '' ? $value : $id,
            'style' => 'primary',
            'action' => 'goto_step',
            'target_step_id' => '',
            'external_url' => '',
            'require_submit' => false,
        ];
    };

    if($is_en) {
        return [
            'demo_request_hero' => [
                'id' => 'demo_request_hero',
                'type' => 'headline',
                'badge' => 'Demo access',
                'title' => 'Want to see the FCC system from the inside? Request controlled demo access.',
                'text' => 'The demo is not a public link for everyone. The goal is to show you the part of the system that makes sense for your situation — whether you want to decide about the Start package, understand how the funnel works, or see how FCC could help your recommendations and contacts.',
            ],
            'demo_request_reason' => [
                'id' => 'demo_request_reason',
                'type' => 'radio_survey',
                'title' => 'Why do you want demo access?',
                'text' => 'Choose the reason that feels closest. That helps me know which part of the system makes the most sense to show you.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    $action('demo_before_decision', 'I want to see the system before deciding about the Start package', 'I am interested in FCC, but before deciding I want to understand how the system works in practice.', 'demo_before_decision'),
                    $action('demo_show_others', 'I want to use the system for my own recommendations', 'I want to see how FCC could guide people from interest to products, a conversation, or the Start package.', 'demo_show_others'),
                    $action('demo_have_team', 'I already have contacts, customers, or a team', 'I want to see whether FCC can help with presentation, recommendations, contacts, and follow-up.', 'demo_have_team'),
                    $action('demo_research', 'I am still exploring and want a basic overview', 'I am not ready to decide yet, but I want to calmly see what the system looks like.', 'samo_istrazujem'),
                ],
            ],
            'demo_request_name' => ['id' => 'demo_request_name', 'type' => 'full_name_field', 'title' => 'Full name', 'placeholder' => 'Enter your full name', 'required' => true, 'layout_width' => 'half'],
            'demo_request_phone' => ['id' => 'demo_request_phone', 'type' => 'phone_field', 'title' => 'WhatsApp / phone', 'placeholder' => 'Enter the number where you can be contacted quickly', 'required' => true, 'layout_width' => 'half'],
            'demo_request_email' => ['id' => 'demo_request_email', 'type' => 'email_field', 'title' => 'Email for demo access', 'placeholder' => 'Enter email only if it is needed for the demo account', 'required' => true, 'layout_width' => 'full'],
            'demo_request_focus' => ['id' => 'demo_request_focus', 'type' => 'text_field', 'title' => 'What do you want to see in the demo?', 'placeholder' => 'e.g. funnel, recommendations, products, contacts, follow-up, Start package', 'field_key' => 'demo_focus', 'required' => false, 'layout_width' => 'full'],
            'demo_request_privacy' => [
                'id' => 'demo_request_privacy',
                'type' => 'text',
                'title' => 'Consent',
                'text' => 'By sending the request, you confirm that ' . $mentor_label . ' or the FCC team may contact you about demo access via WhatsApp or phone call. Email is used only if it is needed to send or activate demo access. Privacy: ' . $privacy_url,
            ],
            'demo_request_submit' => [
                'id' => 'demo_request_submit',
                'type' => 'cta_group',
                'text' => 'Send the request and I will contact you with the best way to review the demo.',
                'buttons' => [
                    ['id' => 'demo_request_submit_btn', 'label' => 'Send demo access request', 'value' => 'demo_requested', 'style' => 'primary', 'action' => 'submit_next', 'target_step_id' => 'not_ready_nurture', 'external_url' => '', 'require_submit' => true],
                ],
                'alignment' => 'center',
            ],
        ];
    }

    return [
        'demo_request_hero' => [
            'id' => 'demo_request_hero',
            'type' => 'headline',
            'badge' => 'Demo pristup',
            'title' => 'Želiš vidjeti FCC sustav iznutra? Zatraži kontrolirani demo pristup.',
            'text' => 'Demo nije javni link za svakoga. Cilj je da ti pokažem dio sustava koji ima smisla za tvoju situaciju — bilo da želiš donijeti odluku za start paket, razumjeti kako radi funnel ili vidjeti kako bi FCC mogao pomoći tvojim preporukama i kontaktima.',
        ],
        'demo_request_reason' => [
            'id' => 'demo_request_reason',
            'type' => 'radio_survey',
            'title' => 'Zašto želiš demo pristup?',
            'text' => 'Odaberi razlog koji ti je najbliži. Tako ću znati koji dio sustava ti ima najviše smisla pokazati.',
            'required' => true,
            'route_on_submit' => false,
            'options' => [
                $action('demo_before_decision', 'Želim vidjeti sustav prije odluke o start paketu', 'Zanima me FCC, ali prije odluke želim razumjeti kako sustav radi u praksi.', 'demo_before_decision'),
                $action('demo_show_others', 'Želim koristiti sustav za vlastite preporuke', 'Želim vidjeti kako bih kroz FCC mogao/la voditi ljude od interesa do proizvoda, razgovora ili start paketa.', 'demo_show_others'),
                $action('demo_have_team', 'Već imam kontakte, kupce ili tim', 'Želim vidjeti može li mi FCC pomoći u prezentaciji, preporuci, kontaktima i follow-upu.', 'demo_have_team'),
                $action('demo_research', 'Još istražujem i želim osnovni prikaz', 'Nisam još spreman/na za odluku, ali želim mirno vidjeti kako sustav izgleda.', 'samo_istrazujem'),
            ],
        ],
        'demo_request_name' => ['id' => 'demo_request_name', 'type' => 'full_name_field', 'title' => 'Ime i prezime', 'placeholder' => 'Upiši ime i prezime', 'required' => true, 'layout_width' => 'half'],
        'demo_request_phone' => ['id' => 'demo_request_phone', 'type' => 'phone_field', 'title' => 'WhatsApp / telefon', 'placeholder' => 'Upiši broj na koji te mogu brzo kontaktirati', 'required' => true, 'layout_width' => 'half'],
        'demo_request_email' => ['id' => 'demo_request_email', 'type' => 'email_field', 'title' => 'Email za demo pristup', 'placeholder' => 'Upiši email samo ako je potreban za demo račun', 'required' => true, 'layout_width' => 'full'],
        'demo_request_focus' => ['id' => 'demo_request_focus', 'type' => 'text_field', 'title' => 'Što želiš vidjeti u demu?', 'placeholder' => 'npr. funnel, preporuke, proizvodi, kontakti, follow-up, start paket', 'field_key' => 'demo_focus', 'required' => false, 'layout_width' => 'full'],
        'demo_request_privacy' => [
            'id' => 'demo_request_privacy',
            'type' => 'text',
            'title' => 'Suglasnost',
            'text' => 'Slanjem zahtjeva potvrđuješ da te ' . $mentor_label . ' ili FCC tim smije kontaktirati vezano uz demo pristup putem WhatsAppa ili telefonskog poziva. Email koristimo samo ako je potreban za slanje ili aktivaciju demo pristupa. Privacy: ' . $privacy_url,
        ],
        'demo_request_submit' => [
            'id' => 'demo_request_submit',
            'type' => 'cta_group',
            'text' => 'Pošalji zahtjev i javit ću ti se s najboljim načinom za demo pregled.',
            'buttons' => [
                ['id' => 'demo_request_submit_btn', 'label' => 'Pošalji zahtjev za demo pristup', 'value' => 'demo_requested', 'style' => 'primary', 'action' => 'submit_next', 'target_step_id' => 'not_ready_nurture', 'external_url' => '', 'require_submit' => true],
            ],
            'alignment' => 'center',
        ],
    ];
}

function vip_funnel_get_call_request_step_blocks(string $language = 'hr', string $mentor_name = '', string $privacy_url = '', string $fast_contact_url = ''): array {
    $is_en = vip_funnel_resolve_import_template_language($language) === 'en';
    $mentor_label = trim($mentor_name) !== '' ? trim($mentor_name) : ($is_en ? 'your mentor' : 'Stjepan');
    $privacy_url = trim($privacy_url) !== '' ? trim($privacy_url) : SITE_URL . 'page/privacy-policy';
    $fast_contact_url = vip_funnel_resolve_fast_contact_url($fast_contact_url);

    $buttons = [
        [
            'id' => 'call_submit',
            'label' => $is_en ? 'Send request for a short conversation' : 'Pošalji zahtjev za kratak razgovor',
            'value' => 'call_requested',
            'style' => 'primary',
            'action' => 'submit_next',
            'target_step_id' => 'not_ready_nurture',
            'external_url' => '',
            'require_submit' => true,
        ],
    ];

    if($fast_contact_url !== '') {
        $is_whatsapp_contact = str_contains(strtolower($fast_contact_url), 'whatsapp') || str_contains(strtolower($fast_contact_url), 'wa.me');
        $buttons[] = [
            'id' => $is_whatsapp_contact ? 'call_whatsapp' : 'call_calendar',
            'label' => $is_whatsapp_contact
                ? ($is_en ? 'Send me a WhatsApp message now' : 'Pošalji mi WhatsApp poruku odmah')
                : ($is_en ? 'Book a short call time' : 'Rezerviraj termin za kratak poziv'),
            'value' => $is_whatsapp_contact ? 'whatsapp_now' : 'book_call',
            'style' => 'secondary',
            'action' => 'external_url',
            'target_step_id' => '',
            'external_url' => $fast_contact_url,
            'require_submit' => false,
        ];
    }

    if($is_en) {
        return [
            'call_hero' => [
                'id' => 'call_hero',
                'type' => 'headline',
                'badge' => 'Short conversation',
                'title' => 'Not sure yet? A short conversation will give you a clearer next step.',
                'text' => 'The goal of the conversation is not to persuade you, but to clarify in a few minutes whether the Start package, an additional demo, products, or a calmer intro makes more sense for you. If FCC is not for you right now, that is also okay.',
            ],
            'call_reason' => [
                'id' => 'call_reason',
                'type' => 'radio_survey',
                'title' => 'What do you most want to clarify before deciding?',
                'text' => 'Choose the topic that matters most right now. That way I know where to start in the conversation.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'call_fit', 'label' => 'I want to know if FCC is really for me', 'hint' => 'I am not sure whether this fits my situation, time, and goals.', 'value' => 'call_fit', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'call_360', 'label' => 'I want to understand what I get for 360 €', 'hint' => 'I want to clearly know what the Start Your Journey package includes and what happens after ordering.', 'value' => 'ready_360_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'call_sales', 'label' => 'I want to understand how FCC helps with recommendations and sales', 'hint' => 'I want to see how the system guides a person from interest to products, conversation, or the Start package.', 'value' => 'call_sales', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'call_no_experience', 'label' => 'I want to know if I can start without experience', 'hint' => 'I do not have experience in online business, sales, or recommendations and want to know what the first steps look like.', 'value' => 'call_no_experience', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'call_next_step', 'label' => 'I only want to confirm the next step', 'hint' => 'I am already interested, but I want a short confirmation before deciding.', 'value' => 'ready_360_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'call_channel' => [
                'id' => 'call_channel',
                'type' => 'radio_survey',
                'title' => 'How would you like to be contacted?',
                'text' => 'For a short check, I use WhatsApp or a call because it is the fastest and clearest.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'call_channel_whatsapp', 'label' => 'WhatsApp message', 'hint' => 'Fastest for first contact and agreement.', 'value' => 'channel_whatsapp', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'call_channel_phone', 'label' => 'Short phone call', 'hint' => 'Best if you want to clarify questions immediately.', 'value' => 'channel_phone', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'call_channel_whatsapp_call', 'label' => 'WhatsApp, then call', 'hint' => 'I send you a message first, then we agree on a time.', 'value' => 'channel_whatsapp_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'call_name' => ['id' => 'call_name', 'type' => 'full_name_field', 'title' => 'Full name', 'placeholder' => 'Enter your full name', 'required' => true, 'layout_width' => 'half'],
            'call_phone' => ['id' => 'call_phone', 'type' => 'phone_field', 'title' => 'WhatsApp / phone', 'placeholder' => 'Enter the number where you can be contacted quickly', 'required' => true, 'layout_width' => 'half'],
            'call_contact_time' => ['id' => 'call_contact_time', 'type' => 'text_field', 'title' => 'Best time to contact you', 'placeholder' => 'e.g. today after 5 pm, tomorrow morning, evening', 'field_key' => 'contact_time', 'required' => false, 'layout_width' => 'full'],
            'call_privacy' => [
                'id' => 'call_privacy',
                'type' => 'text',
                'text' => 'By submitting the request, you confirm that ' . $mentor_label . ' or the FCC team may contact you about your selected FCC path via WhatsApp or phone call. We use your details only to process the request and communicate about FCC. Privacy: ' . $privacy_url,
                'text_size' => 14,
            ],
            'call_actions' => [
                'id' => 'call_actions',
                'type' => 'cta_group',
                'text' => 'Send the request and I will contact you by WhatsApp or call.',
                'buttons' => $buttons,
                'alignment' => 'center',
            ],
        ];
    }

    return [
        'call_hero' => [
            'id' => 'call_hero',
            'type' => 'headline',
            'badge' => 'Kratki razgovor',
            'title' => 'Nisi još siguran/na? Kratak razgovor će ti dati jasniji sljedeći korak.',
            'text' => 'Cilj razgovora nije da te nagovaram, nego da u par minuta razjasnimo ima li za tebe više smisla start paket, dodatni demo, proizvodi ili mirniji uvod. Ako FCC nije za tebe sada, i to je u redu.',
        ],
        'call_reason' => [
            'id' => 'call_reason',
            'type' => 'radio_survey',
            'title' => 'Što najviše želiš razjasniti prije odluke?',
            'text' => 'Odaberi temu koja ti je trenutno najvažnija. Tako ću znati odakle krenuti u razgovoru.',
            'required' => true,
            'route_on_submit' => false,
            'options' => [
                ['id' => 'call_fit', 'label' => 'Želim znati je li FCC stvarno za mene', 'hint' => 'Nisam siguran/na uklapa li se ovo u moju situaciju, vrijeme i ciljeve.', 'value' => 'call_fit', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ['id' => 'call_360', 'label' => 'Želim razumjeti što dobivam za 360 €', 'hint' => 'Želim jasno znati što uključuje Start Your Journey paket i što se događa nakon narudžbe.', 'value' => 'ready_360_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ['id' => 'call_sales', 'label' => 'Želim razumjeti kako FCC pomaže u preporukama i prodaji', 'hint' => 'Zanima me kako sustav vodi osobu od interesa do proizvoda, razgovora ili start paketa.', 'value' => 'call_sales', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ['id' => 'call_no_experience', 'label' => 'Želim znati mogu li krenuti bez iskustva', 'hint' => 'Nemam iskustva u online poslu, prodaji ili preporukama i želim znati kako izgledaju prvi koraci.', 'value' => 'call_no_experience', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ['id' => 'call_next_step', 'label' => 'Želim samo potvrditi sljedeći korak', 'hint' => 'Već sam zainteresiran/a, ali želim kratku potvrdu prije nego donesem odluku.', 'value' => 'ready_360_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
            ],
        ],
        'call_channel' => [
            'id' => 'call_channel',
            'type' => 'radio_survey',
            'title' => 'Kako želiš da te kontaktiram?',
            'text' => 'Za kratku provjeru koristim WhatsApp ili poziv jer je tako najbrže i najjasnije.',
            'required' => true,
            'route_on_submit' => false,
            'options' => [
                ['id' => 'call_channel_whatsapp', 'label' => 'WhatsApp poruka', 'hint' => 'Najbrže za prvi kontakt i dogovor.', 'value' => 'channel_whatsapp', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ['id' => 'call_channel_phone', 'label' => 'Kratki telefonski poziv', 'hint' => 'Najbolje ako želiš odmah razjasniti pitanja.', 'value' => 'channel_phone', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ['id' => 'call_channel_whatsapp_call', 'label' => 'WhatsApp pa poziv', 'hint' => 'Prvo ti pošaljem poruku, pa dogovorimo termin.', 'value' => 'channel_whatsapp_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
            ],
        ],
        'call_name' => ['id' => 'call_name', 'type' => 'full_name_field', 'title' => 'Ime i prezime', 'placeholder' => 'Upiši ime i prezime', 'required' => true, 'layout_width' => 'half'],
        'call_phone' => ['id' => 'call_phone', 'type' => 'phone_field', 'title' => 'WhatsApp / telefon', 'placeholder' => 'Upiši broj na koji te mogu brzo kontaktirati', 'required' => true, 'layout_width' => 'half'],
        'call_contact_time' => ['id' => 'call_contact_time', 'type' => 'text_field', 'title' => 'Najbolje vrijeme za kontakt', 'placeholder' => 'npr. danas poslije 17h, sutra ujutro, navečer', 'field_key' => 'contact_time', 'required' => false, 'layout_width' => 'full'],
        'call_privacy' => [
            'id' => 'call_privacy',
            'type' => 'text',
            'text' => 'Slanjem zahtjeva potvrđuješ da te ' . $mentor_label . ' ili FCC tim smije kontaktirati vezano uz tvoj odabrani FCC smjer putem WhatsAppa ili telefonskog poziva. Podatke koristimo samo za obradu upita i komunikaciju vezanu uz FCC. Privacy: ' . $privacy_url,
            'text_size' => 14,
        ],
        'call_actions' => [
            'id' => 'call_actions',
            'type' => 'cta_group',
            'text' => 'Pošalji zahtjev i javit ću ti se preko WhatsAppa ili poziva.',
            'buttons' => $buttons,
            'alignment' => 'center',
        ],
    ];
}

function vip_funnel_update_template_step(array &$payload, string $step_id, array $updates): void {
    if(!isset($payload['board']) || !is_array($payload['board'])) {
        return;
    }

    foreach($payload['board'] as &$phase) {
        if(!is_array($phase) || empty($phase['steps']) || !is_array($phase['steps'])) {
            continue;
        }

        foreach($phase['steps'] as &$step) {
            if(!is_array($step) || (string) ($step['id'] ?? '') !== $step_id) {
                continue;
            }

            $step = array_replace_recursive($step, $updates);
        }
        unset($step);
    }
    unset($phase);
}

function vip_funnel_refresh_stjepan_landing_copy_if_needed(array &$payload): void {
    if(empty($payload['landing_page']) || !is_array($payload['landing_page'])) {
        return;
    }

    $funnel_name = (string) ($payload['funnel']['name'] ?? '');
    $landing_name = (string) ($payload['landing_page']['name'] ?? '');
    $overview_headline = (string) ($payload['overview']['headline'] ?? '');
    $is_stjepan_funnel = str_contains($funnel_name, 'Stjepan')
        || str_contains($landing_name, 'Stjepan osobni recruiting landing')
        || str_contains($overview_headline, 'Stjepan');

    if(!$is_stjepan_funnel) {
        return;
    }

    $payload['defaults'] = is_array($payload['defaults'] ?? null) ? $payload['defaults'] : [];
    $default_contact_email = strtolower(trim((string) ($payload['defaults']['contact_email'] ?? '')));
    $default_owner_user_id = (int) ($payload['defaults']['owner_user_id'] ?? 0);
    if($default_contact_email === 'info@forevercard.club' || $default_owner_user_id === 555) {
        $payload['defaults']['facebook_pixel_id'] = '238225369103006';
    }

    $overview_headline_current = (string) ($payload['overview']['headline'] ?? '');
    if(in_array($overview_headline_current, [
        'Pokreni online posao uz FCC sustav i mentorstvo Stjepana Beloše',
        'Pokreni svoj FCC put uz moje osobno mentorstvo',
    ], true)) {
        $payload['overview']['eyebrow'] = 'Stjepan Beloša | Osobni FCC vodič';
        $payload['overview']['headline'] = 'Pokreni svoj FCC put uz moje osobno mentorstvo';
        $payload['overview']['subheadline'] = 'Došao/la si s mojih videa? Ovdje ćeš brzo vidjeti je li FCC za tebe, koji je tvoj najbolji prvi korak i kako možeš krenuti uz moje mentorstvo.';
        $payload['overview']['primary_cta'] = 'Provjeri svoj put';
        $payload['overview']['secondary_cta'] = 'Razumijem FCC sustav';
    }

    if($landing_name === 'Stjepan osobni recruiting landing') {
        $payload['landing_page']['name'] = 'Stjepan Beloša | Osobni FCC vodič';
    }

    $replace_block = static function(array &$blocks, string $block_id, array $updates): void {
        foreach($blocks as &$block) {
            if(is_array($block) && (string) ($block['id'] ?? '') === $block_id) {
                $block = array_replace_recursive($block, $updates);
                break;
            }
        }
        unset($block);
    };

    $replace_option_labels = static function(array &$blocks, string $block_id, array $labels_by_id): void {
        foreach($blocks as &$block) {
            if(!is_array($block) || (string) ($block['id'] ?? '') !== $block_id || empty($block['options']) || !is_array($block['options'])) {
                continue;
            }

            foreach($block['options'] as &$option) {
                if(!is_array($option)) {
                    continue;
                }

                $option_id = (string) ($option['id'] ?? '');
                if(isset($labels_by_id[$option_id])) {
                    $option['label'] = $labels_by_id[$option_id];
                }
            }
            unset($option);
        }
        unset($block);
    };

    $find_board_block_title = static function(array $payload, string $block_id): string {
        foreach((array) ($payload['board'] ?? []) as $phase) {
            if(!is_array($phase) || empty($phase['steps']) || !is_array($phase['steps'])) {
                continue;
            }

            foreach($phase['steps'] as $step) {
                if(!is_array($step) || empty($step['page']['blocks']) || !is_array($step['page']['blocks'])) {
                    continue;
                }

                foreach($step['page']['blocks'] as $block) {
                    if(is_array($block) && (string) ($block['id'] ?? '') === $block_id) {
                        return (string) ($block['title'] ?? '');
                    }
                }
            }
        }

        return '';
    };

    $business_hero_title = $find_board_block_title($payload, 'business_hero');
    if(in_array($business_hero_title, [
        'Ne trebaš krenuti sam. Trebaš jasan sustav i mentora.',
        'Ako želiš krenuti ozbiljno, kreni uz sustav, tim i mentora.',
    ], true)) {
        vip_funnel_update_template_block($payload, 'business_hero', [
            'badge' => 'Poslovni put',
            'title' => 'Ako želiš krenuti ozbiljno, kreni uz sustav, tim i mentora.',
            'text' => 'Ovdje ti pokazujem što znači ulazak u moj FCC tim: kako se radi, što dobivaš kroz sustav, koji su prvi koraci i kada ima smisla uzeti start paket. Bez lutanja, bez previše informacija odjednom - samo jasan sljedeći korak.',
        ]);
        vip_funnel_update_template_block($payload, 'business_video', [
            'title' => 'Kako izgleda ulazak u moj FCC tim',
            'text' => 'Pogledaj kratku poruku prije odluke. Pokazat ću ti što radiš u prvom tjednu, kako koristiš sustav i što znači imati mentora uz sebe.',
        ]);
        vip_funnel_update_template_block($payload, 'business_system', [
            'badge' => 'Što dobivaš',
            'title' => 'Ne krećeš od nule. Krećeš kroz jasan FCC okvir.',
            'text' => 'Dobivaš smjer za prve objave, prezentaciju, preporuke, razgovore i follow-up. Rezultat ovisi o tvojoj aktivnosti, ali ne ulaziš naslijepo - znaš što radiš prvi dan, prvi tjedan i nakon toga.',
        ]);
        vip_funnel_update_template_block($payload, 'business_choice', [
            'title' => 'Što ti sada treba za odluku?',
            'text' => 'Odaberi iskreno gdje si trenutno. Tako će te funnel odvesti na najbrži sljedeći korak bez nepotrebnog lutanja.',
            'options' => [
                ['id' => 'business_serious', 'label' => 'Spreman/na sam za start paket i ulazak u tim', 'value' => 'ready_360_now', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'start_package_offer', 'external_url' => '', 'require_submit' => false],
                ['id' => 'business_check', 'label' => 'Želim kratku provjeru prije starta', 'value' => 'needs_check', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'qualification_form', 'external_url' => '', 'require_submit' => false],
                ['id' => 'business_demo', 'label' => 'Prvo želim vidjeti kako sustav radi', 'value' => 'demo_interest', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'fcc_demo_preview', 'external_url' => '', 'require_submit' => false],
                ['id' => 'business_not_ready', 'label' => 'Nisam još spreman/na za start paket', 'value' => 'not_ready', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'not_ready_nurture', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_update_template_step($payload, 'business_gateway', [
            'title' => 'Poslovni filter za ozbiljan FCC start',
            'summary' => 'Vodi prema start paketu, kratkoj provjeri, demo prikazu sustava ili mirnijem uvodu.',
            'helper_text' => 'Vodi prema start paketu, kratkoj provjeri, demo prikazu sustava ili mirnijem uvodu.',
            'preview_headline' => 'Poslovni filter za ozbiljan FCC start',
            'preview_body' => 'Vodi prema start paketu, kratkoj provjeri, demo prikazu sustava ili mirnijem uvodu.',
            'page' => ['name' => 'Poslovni filter za ozbiljan FCC start'],
        ]);
    }

    $qualification_hero_title = $find_board_block_title($payload, 'qualification_hero');
    if(in_array($qualification_hero_title, [
        'Odgovori iskreno. Funnel će ti pokazati najbolji sljedeći korak.',
        'Odgovori iskreno i pokazat ću ti najbolji sljedeći korak.',
    ], true)) {
        vip_funnel_sync_template_step_blocks($payload, 'qualification_form', [
            'qualification_hero' => [
                'id' => 'qualification_hero',
                'type' => 'headline',
                'badge' => 'Kratka provjera prije starta',
                'title' => 'Odgovori iskreno i pokazat ću ti najbolji sljedeći korak.',
                'text' => 'Ovo nije test ni obveza. Treba mi nekoliko iskrenih odgovora da vidim ima li smisla da kreneš sa start paketom sada, da prvo prođemo kratak razgovor ili da pogledaš sustav mirnijim tempom.',
            ],
            'qualification_goal' => [
                'id' => 'qualification_goal',
                'type' => 'radio_survey',
                'title' => 'Što želiš postići kroz FCC?',
                'text' => 'Odaberi ono što ti je trenutno najbliže. Ne mora biti konačna odluka.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'goal_extra_income', 'label' => 'Dodatni prihod uz postojeće obaveze', 'hint' => 'Želim krenuti postupno, ali uz jasan plan i podršku.', 'value' => 'dodatni_prihod', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'goal_serious_business', 'label' => 'Ozbiljan online posao', 'hint' => 'Spreman/na sam učiti, raditi i dugoročnije graditi svoj tim.', 'value' => 'ozbiljan_online_posao', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'goal_product_first', 'label' => 'Prvo proizvodi i popust, posao kasnije', 'hint' => 'Želim upoznati proizvode, koristiti pogodnosti i vidjeti ima li smisla preporučivati.', 'value' => 'product_discount', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'goal_research', 'label' => 'Samo istražujem', 'hint' => 'Želim razumjeti opcije bez pritiska i vidjeti je li FCC za mene.', 'value' => 'samo_istrazujem', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_time' => [
                'id' => 'qualification_time',
                'type' => 'radio_survey',
                'title' => 'Koliko vremena realno možeš odvojiti tjedno?',
                'text' => 'Bolje je odgovoriti realno nego idealno. Tako ćeš dobiti tempo koji ima smisla za tvoju situaciju.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'time_1_3', 'label' => '1-3 sata tjedno', 'hint' => 'Za miran početak, osnovne zadatke i postupno upoznavanje sustava.', 'value' => 'time_1_3', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'time_4_7', 'label' => '4-7 sati tjedno', 'hint' => 'Dovoljno za ozbiljan start, prve kontakte i stvaranje radne navike.', 'value' => 'time_4_7', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'time_8_plus', 'label' => '8+ sati tjedno', 'hint' => 'Spreman/na sam za brži ritam i aktivniji početak.', 'value' => 'time_8_plus', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'time_no_capacity', 'label' => 'Trenutno ne mogu odvojiti vrijeme', 'hint' => 'Bolje je krenuti kasnije nego pod pritiskom i bez fokusa.', 'value' => 'time_no_capacity', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_investment' => [
                'id' => 'qualification_investment',
                'type' => 'radio_survey',
                'title' => 'Je li ti Start Your Journey paket od 360 € realan ovaj tjedan ako zaključiš da je FCC za tebe?',
                'text' => 'Ovo pitanje ne služi za pritisak, nego da te ne vodim na pogrešan sljedeći korak.',
                'required' => true,
                'route_on_submit' => true,
                'options' => [
                    ['id' => 'ready_now', 'label' => 'Da, mogu odmah', 'hint' => 'Ako mi je smjer jasan, mogu napraviti narudžbu i krenuti.', 'value' => 'ready_360_now', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'start_package_offer', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'ready_call', 'label' => 'Da, ali želim kratak razgovor', 'hint' => 'Trebam potvrdu i par odgovora prije narudžbe.', 'value' => 'ready_360_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'mentor_call_request', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'need_demo', 'label' => 'Prvo želim vidjeti sustav', 'hint' => 'Želim bolje razumjeti kako FCC radi prije odluke.', 'value' => 'trebam_prvo_vidjeti_sustav', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'fcc_demo_preview', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'not_now', 'label' => 'Ne sada', 'hint' => 'Trenutno nije pravi trenutak za start paket.', 'value' => 'not_ready', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'not_ready_nurture', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_channel' => [
                'id' => 'qualification_channel',
                'type' => 'radio_survey',
                'title' => 'Kako želiš da te kontaktiram?',
                'text' => 'Za ovu provjeru ne koristim email kao glavni kanal. Najbrže i najjasnije je preko WhatsAppa ili kratkog poziva.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'channel_whatsapp', 'label' => 'WhatsApp poruka', 'hint' => 'Najbrže za prvi kontakt, dogovor i slanje sljedećih uputa.', 'value' => 'channel_whatsapp', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'channel_phone', 'label' => 'Kratki telefonski poziv', 'hint' => 'Najbolje ako želiš brzo proći pitanja i donijeti odluku.', 'value' => 'channel_phone', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'channel_whatsapp_call', 'label' => 'WhatsApp pa poziv', 'hint' => 'Prvo ti pošaljem poruku, pa dogovorimo najbolji termin za razgovor.', 'value' => 'channel_whatsapp_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_name' => ['id' => 'qualification_name', 'type' => 'full_name_field', 'title' => 'Ime i prezime', 'placeholder' => 'Upiši ime i prezime', 'required' => true, 'layout_width' => 'half'],
            'qualification_phone' => ['id' => 'qualification_phone', 'type' => 'phone_field', 'title' => 'WhatsApp / telefon', 'placeholder' => 'Upiši broj na koji te mogu brzo kontaktirati', 'required' => true, 'layout_width' => 'half'],
            'qualification_contact_time' => ['id' => 'qualification_contact_time', 'type' => 'text_field', 'title' => 'Najbolje vrijeme za kontakt', 'placeholder' => 'npr. danas poslije 17h, sutra ujutro, navečer', 'field_key' => 'contact_time', 'required' => false, 'layout_width' => 'full'],
            'qualification_privacy' => [
                'id' => 'qualification_privacy',
                'type' => 'text',
                'text' => 'Slanjem odgovora potvrđuješ da te Stjepan ili FCC tim smije kontaktirati vezano uz tvoj odabrani FCC smjer putem WhatsAppa ili telefonskog poziva. Tvoje podatke koristimo za obradu upita i komunikaciju vezanu uz FCC. Privacy: ' . SITE_URL . 'page/privacy-policy',
                'text_size' => 14,
            ],
            'qualification_submit' => [
                'id' => 'qualification_submit',
                'type' => 'cta_group',
                'text' => 'Pošalji odgovore i dobit ćeš preporučeni sljedeći korak. Nakon slanja vidjet ćeš preporučeni korak, a ako si odabrao/la kontakt, javit ću ti se preko WhatsAppa ili poziva.',
                'buttons' => [
                    ['id' => 'qualification_submit_btn', 'label' => 'Pošalji provjeru i pokaži mi sljedeći korak', 'value' => 'qualification_submit', 'style' => 'primary', 'action' => 'submit_next', 'target_step_id' => '', 'external_url' => '', 'require_submit' => true],
                ],
                'alignment' => 'center',
            ],
        ], [
            'qualification_hero',
            'qualification_goal',
            'qualification_time',
            'qualification_investment',
            'qualification_channel',
            'qualification_name',
            'qualification_phone',
            'qualification_contact_time',
            'qualification_privacy',
            'qualification_submit',
        ], ['qualification_email']);

        vip_funnel_update_template_step($payload, 'qualification_form', [
            'title' => 'Kratka provjera prije starta',
            'summary' => 'Kvalifikacija sprema odgovore, WhatsApp/telefon i najbolji termin kontakta te vodi na pravi nastavak.',
            'helper_text' => 'Kvalifikacija sprema odgovore, WhatsApp/telefon i najbolji termin kontakta te vodi na pravi nastavak.',
            'preview_headline' => 'Kratka provjera prije starta',
            'preview_body' => 'Odgovori iskreno i sustav te vodi prema start paketu, razgovoru, demo prikazu ili mirnijem uvodu.',
            'page' => ['name' => 'Kratka provjera prije starta'],
        ]);
    }

    $demo_hero_title = $find_board_block_title($payload, 'demo_hero');
    if(in_array($demo_hero_title, [
        'Ovdje osoba vidi sustav, ne samo priču.',
        'Ovdje vidiš kako FCC pretvara interes u jasne korake.',
        'Prvo razumij kako FCC funkcionira, pa odluči je li ovo za tebe.',
    ], true)) {
        vip_funnel_update_template_block($payload, 'demo_hero', [
            'badge' => 'FCC sustav',
            'title' => 'Prvo razumij kako FCC funkcionira, pa odluči je li ovo za tebe.',
            'text' => 'U ovom koraku pokazujem ti kako FCC vodi osobu od prvog interesa do jasnog sljedećeg koraka: proizvodi, provjera, demo ili ulazak u tim. Ako odlučiš krenuti, isti okvir možeš koristiti i u svom poslu uz moje mentorstvo.',
            'title_size' => 50,
        ]);
        vip_funnel_update_template_block($payload, 'demo_video', [
            'title' => 'Kako izgleda FCC sustav u praksi',
            'text' => 'Pogledaj kratki prikaz sustava: kako se osoba vodi kroz funnel, kako se biraju proizvodi ili poslovni put, kako izgleda kontakt i što se događa nakon prvog interesa.',
        ]);
        vip_funnel_update_template_block($payload, 'demo_actions', [
            'text' => 'Nakon što pogledaš objašnjenje, odaberi što ti je sada najbliže.',
            'buttons' => [
                ['id' => 'demo_qualify', 'label' => 'Želim provjeriti je li FCC za mene', 'value' => 'business_interest', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'qualification_form', 'external_url' => '', 'require_submit' => false],
                ['id' => 'demo_start', 'label' => 'Spreman/na sam za start paket i ulazak u tim', 'value' => 'ready_360_now', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'start_package_offer', 'external_url' => '', 'require_submit' => false],
                ['id' => 'demo_request', 'label' => 'Želim detaljniji demo sustava', 'value' => 'demo_request', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'demo_request', 'external_url' => '', 'require_submit' => false],
                ['id' => 'demo_products', 'label' => 'Zanimaju me samo proizvodi i popusti', 'value' => 'product_discount', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'product_gateway', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_update_template_step($payload, 'fcc_demo_preview', [
            'title' => 'Razumij FCC sustav prije odluke',
            'summary' => 'Mirno objašnjava kako FCC vodi od interesa do proizvoda, provjere, demo prikaza ili ulaska u tim.',
            'helper_text' => 'Mirno objašnjava kako FCC vodi od interesa do proizvoda, provjere, demo prikaza ili ulaska u tim.',
            'preview_headline' => 'Razumij FCC sustav prije odluke',
            'preview_body' => 'Mirno objašnjava kako FCC vodi od interesa do proizvoda, provjere, demo prikaza ili ulaska u tim.',
            'page' => ['name' => 'Razumij FCC sustav prije odluke'],
        ]);
    }

    vip_funnel_sync_template_step_blocks($payload, 'demo_request', vip_funnel_get_demo_request_step_blocks('hr', 'Stjepan', SITE_URL . 'page/privacy-policy'), [
        'demo_request_hero',
        'demo_request_reason',
        'demo_request_name',
        'demo_request_phone',
        'demo_request_email',
        'demo_request_focus',
        'demo_request_privacy',
        'demo_request_submit',
    ]);
    vip_funnel_update_template_step($payload, 'demo_request', [
        'title' => 'Zatraži kontrolirani demo pristup FCC sustavu',
        'summary' => 'Kontrolirani demo zahtjev s razlogom, WhatsApp/telefonom i emailom samo za demo pristup.',
        'helper_text' => 'Kontrolirani demo zahtjev s razlogom, WhatsApp/telefonom i emailom samo za demo pristup.',
        'preview_headline' => 'Zatraži kontrolirani demo pristup FCC sustavu',
        'preview_body' => 'Demo nije javni link. Osoba ostavlja razlog, kontakt i što želi vidjeti.',
        'page' => ['name' => 'Zatraži kontrolirani demo pristup FCC sustavu'],
    ]);

    $product_hero_title = $find_board_block_title($payload, 'product_gateway_hero');
    if(in_array($product_hero_title, [
        'Ne mora svatko odmah u posao. Odaberi cilj i dobit ćeš jasniji proizvodni korak.',
        'Kreni od proizvoda koji ti stvarno treba, a poslovni put može doći kasnije.',
    ], true)) {
        vip_funnel_update_template_block($payload, 'product_gateway_hero', [
            'badge' => 'Proizvodi i popust',
            'title' => 'Kreni od proizvoda koji ti stvarno treba, a poslovni put može doći kasnije.',
            'text' => 'Ako te sada najviše zanimaju Forever proizvodi, odaberi cilj koji ti je najbliži. Dobit ćeš jednostavnu preporuku, mogućnost popusta i jasan sljedeći korak bez pritiska.',
            'title_size' => 50,
        ]);
        vip_funnel_update_template_block($payload, 'product_goal', [
            'title' => 'Što želiš prvo poboljšati?',
            'text' => 'Odaberi najbliži cilj i funnel će te voditi prema preporuci koja ima najviše smisla za tvoju situaciju.',
            'options' => [
                ['id' => 'product_energy', 'label' => 'Više energije i dnevna vitalnost', 'value' => 'vise_energije', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_weight', 'label' => 'Regulacija težine i bolja forma', 'value' => 'regulacija_tezine', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_skin', 'label' => 'Njega kože i osobna rutina', 'value' => 'njega_koze', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_routine', 'label' => 'Opća dnevna rutina za mene ili obitelj', 'value' => 'dnevna_rutina', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_discount', 'label' => 'Želim preporuku i popust', 'value' => 'popust', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_update_template_step($payload, 'product_gateway', [
            'title' => 'Proizvodni put s jasnom preporukom',
            'summary' => 'Vodi osobu prema proizvodnom cilju, popustu i kasnijem poslovnom mostu bez pritiska.',
            'helper_text' => 'Vodi osobu prema proizvodnom cilju, popustu i kasnijem poslovnom mostu bez pritiska.',
            'preview_headline' => 'Proizvodni put s jasnom preporukom',
            'preview_body' => 'Vodi osobu prema proizvodnom cilju, popustu i kasnijem poslovnom mostu bez pritiska.',
            'page' => ['name' => 'Proizvodni put s jasnom preporukom'],
        ]);
    }

    $start_hero_title = $find_board_block_title($payload, 'start_hero');
    if(in_array($start_hero_title, [
        'Ovo je trenutak kada interes postaje konkretan prvi korak.',
        'Ako želiš ući u FCC tim, start paket je tvoj prvi konkretan korak.',
    ], true)) {
        vip_funnel_update_template_block($payload, 'start_hero', [
            'badge' => 'Start Your Journey | 360 €',
            'title' => 'Ako želiš ući u FCC tim, start paket je tvoj prvi konkretan korak.',
            'text' => 'Kroz Start Your Journey dobivaš proizvode, ulazak u sustav, početnu edukaciju i moje mentorstvo za prve korake. Nakon narudžbe ne ostaješ sam/a - vodim te kroz postavljanje, razumijevanje ponude i prve zadatke.',
            'title_size' => 50,
        ]);
        vip_funnel_update_template_block($payload, 'start_video', [
            'title' => 'Prije narudžbe pogledaj što točno dobivaš',
            'text' => 'U kratkom videu pokazujem što uključuje start paket, kako izgleda prvi tjedan i što se događa nakon što pošalješ narudžbu.',
        ]);
        vip_funnel_update_template_block($payload, 'start_included', [
            'badge' => 'Dobivaš',
            'title' => 'Proizvode, FCC sustav, prve zadatke i mentorstvo za start.',
            'text' => 'Ne kupuješ obećanje lake zarade. Dobivaš proizvode, jasan početni okvir, edukaciju i podršku. Rezultat ovisi o tvojoj aktivnosti, učenju, komunikaciji i dosljednosti.',
        ]);
        vip_funnel_update_template_block($payload, 'start_first_week', [
            'badge' => 'Prvih 7 dana',
            'title' => 'Postavljanje, prvi zadaci, razumijevanje ponude i prvi kontakti.',
            'text' => 'Cilj je da nakon narudžbe ne ostaneš sam/a, nego da odmah znaš koji je tvoj prvi korak, kako koristiti sustav i kako krenuti s preporukama.',
        ]);
        vip_funnel_update_template_block($payload, 'start_countdown', [
            'title' => 'Sljedeći Zoom onboarding za nove suradnike',
            'text' => 'Onboarding se održava svaki četvrtak u 20:00 putem Zooma. Nakon registracije osobno ću te kontaktirati i poslati Zoom link, pripremu i sve što ti treba za prvi webinar i početak.',
            'countdown_mode' => 'weekly',
            'countdown_weekly_day' => 4,
            'countdown_weekly_time' => '20:00',
            'countdown_timezone' => 'Europe/Zagreb',
            'duration_days' => 0,
            'duration_minutes' => 0,
        ]);
        vip_funnel_update_template_block($payload, 'start_actions', [
            'text' => 'Odaberi kako želiš napraviti sljedeći korak. Nakon klika dobit ćeš upute za narudžbu i povezivanje sa mnom prije onboardinga.',
            'buttons' => [
                ['id' => 'start_order', 'label' => 'Želim naručiti Start Your Journey paket', 'hint' => 'Preusmjerava te na službenu Forever Living stranicu za narudžbu i upis s preporukom tvog mentora i automatskim odabirom zemlje.', 'value' => 'order_start_package', 'style' => 'primary', 'action' => 'external_url', 'target_step_id' => '', 'external_url' => vip_funnel_get_forever_business_referral_action_token(), 'require_submit' => false],
                ['id' => 'start_whatsapp', 'label' => 'Imam pitanje prije narudžbe', 'value' => 'start_whatsapp', 'style' => 'secondary', 'action' => 'external_url', 'target_step_id' => '', 'external_url' => vip_funnel_get_start_package_question_whatsapp_url((int) ($payload['defaults']['owner_user_id'] ?? 0), 'hr') ?: (string) ($payload['defaults']['whatsapp_url'] ?? ''), 'require_submit' => false],
                ['id' => 'start_call', 'label' => 'Nisam još siguran/na - želim kratki razgovor', 'value' => 'ready_360_call', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'mentor_call_request', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_update_template_step($payload, 'start_package_offer', [
            'title' => 'Start Your Journey: ulazak u FCC tim i prvi konkretan korak',
            'summary' => 'Konverzijska stranica za start paket, ulazak u tim i onboarding.',
            'helper_text' => 'Konverzijska stranica za start paket, ulazak u tim i onboarding.',
            'preview_headline' => 'Start Your Journey: ulazak u FCC tim i prvi konkretan korak',
            'preview_body' => 'Konverzijska stranica za start paket, ulazak u tim i onboarding.',
            'page' => ['name' => 'Start Your Journey: ulazak u FCC tim i prvi konkretan korak'],
        ]);
    }

    $call_hero_title = $find_board_block_title($payload, 'call_hero');
    if(in_array($call_hero_title, [
        'Ako želiš ljudsku potvrdu prije odluke, ovdje ostavi najbolji kontakt.',
        'Nisi još siguran/na? Kratak razgovor će ti dati jasniji sljedeći korak.',
    ], true)) {
        $call_fast_contact_url = vip_funnel_resolve_fast_contact_url((string) ($payload['defaults']['whatsapp_url'] ?? ''))
            ?: vip_funnel_resolve_fast_contact_url((string) ($payload['defaults']['calendar_url'] ?? ''));

        vip_funnel_sync_template_step_blocks($payload, 'mentor_call_request', vip_funnel_get_call_request_step_blocks('hr', 'Stjepan', SITE_URL . 'page/privacy-policy', $call_fast_contact_url), [
            'call_hero',
            'call_reason',
            'call_channel',
            'call_name',
            'call_phone',
            'call_contact_time',
            'call_privacy',
            'call_actions',
        ], ['call_email']);

        vip_funnel_update_template_step($payload, 'mentor_call_request', [
            'title' => 'Zatraži kratak razgovor prije odluke',
            'summary' => 'Smiruje nesigurnost i vodi osobu prema WhatsAppu ili pozivu bez pritiska.',
            'helper_text' => 'Smiruje nesigurnost i vodi osobu prema WhatsAppu ili pozivu bez pritiska.',
            'preview_headline' => 'Zatraži kratak razgovor prije odluke',
            'preview_body' => 'Smiruje nesigurnost i vodi osobu prema WhatsAppu ili pozivu bez pritiska.',
            'page' => ['name' => 'Zatraži kratak razgovor prije odluke'],
        ]);
    }

    if(empty($payload['landing_page']['blocks']) || !is_array($payload['landing_page']['blocks'])) {
        return;
    }

    $landing_blocks = &$payload['landing_page']['blocks'];
    $landing_hero_title = '';
    foreach($landing_blocks as $block) {
        if(is_array($block) && (string) ($block['id'] ?? '') === 'landing_hero') {
            $landing_hero_title = (string) ($block['title'] ?? '');
            break;
        }
    }

    if(in_array($landing_hero_title, [
        'Pokreni online posao uz FCC sustav i moje mentorstvo',
        'Pokreni svoj FCC put uz moje osobno mentorstvo',
    ], true)) {
        $replace_block($landing_blocks, 'landing_hero', [
            'badge' => 'Počni ovdje',
            'title' => 'Pokreni svoj FCC put uz moje osobno mentorstvo',
            'text' => 'Došao/la si s mojih videa? Ovdje ćeš brzo vidjeti je li FCC za tebe, koji je tvoj najbolji prvi korak i kako možeš krenuti uz moje mentorstvo.',
            'title_size' => 50,
        ]);
        $replace_block($landing_blocks, 'landing_intro_video', [
            'title' => 'Prvo pogledaj ovu kratku poruku',
            'text' => 'U par minuta ću ti objasniti kako funkcionira ovaj vodič i koji sljedeći korak odabrati ovisno o tome gdje se trenutno nalaziš.',
        ]);
        $replace_block($landing_blocks, 'landing_proof', [
            'badge' => 'Zašto sam složio ovaj vodič',
            'title' => 'Ne želim da lutaš. Želim da odmah vidiš pravi sljedeći korak.',
            'text' => 'Neki ljudi žele pokrenuti online posao, neki prvo žele razumjeti FCC sustav, neki dolaze zbog proizvoda, a neki su već spremni za start paket. Zato te ova stranica vodi jednostavno, korak po korak.',
        ]);
        $replace_block($landing_blocks, 'landing_direction', [
            'title' => 'Gdje se trenutno nalaziš?',
            'text' => 'Odaberi opciju koja najbolje opisuje tvoju situaciju. Nisi siguran/na što odabrati? Kreni s opcijom "Želim prvo razumjeti FCC sustav".',
        ]);
        $replace_option_labels($landing_blocks, 'landing_direction', [
            'landing_business' => 'Želim pokrenuti online posao',
            'landing_demo' => 'Želim prvo razumjeti FCC sustav',
            'landing_product' => 'Zanimaju me proizvodi i popusti',
            'landing_ready' => 'Spreman/na sam za start paket',
        ]);
    }

    if(empty($payload['landing_page']['variant_b_blocks']) || !is_array($payload['landing_page']['variant_b_blocks'])) {
        return;
    }

    $variant_blocks = &$payload['landing_page']['variant_b_blocks'];
    $variant_hero_title = '';
    foreach($variant_blocks as $block) {
        if(is_array($block) && (string) ($block['id'] ?? '') === 'landing_b_hero') {
            $variant_hero_title = (string) ($block['title'] ?? '');
            break;
        }
    }

    if(in_array($variant_hero_title, [
        'Od milionskih pregleda do vlastitog online posla',
        'Od prvog interesa do vlastitog online posla',
        'Pridruži se mom FCC timu i kreni graditi online posao uz jasan sustav',
    ], true)) {
        $replace_block($variant_blocks, 'landing_b_hero', [
            'badge' => 'Online posao uz vođeni sustav',
            'title' => 'Pridruži se mom FCC timu i kreni graditi online posao uz jasan sustav',
            'text' => 'Ako želiš ozbiljno krenuti, ne moraš sve smišljati sam/a. Kroz FCC sustav, proizvode, edukaciju i mentorstvo pokazat ću ti kako napraviti prve korake i uključiti se u tim.',
            'title_size' => 50,
        ]);
        $replace_block($variant_blocks, 'landing_b_intro_video', [
            'title' => 'Kratka poruka prije nego odabereš svoj put',
            'text' => 'Prvo pogledaj video kako bi razumio/la što je FCC, kako izgleda suradnja i što znači krenuti uz moje mentorstvo.',
        ]);
        $replace_block($variant_blocks, 'landing_b_proof', [
            'badge' => 'Kako ovo funkcionira',
            'title' => 'Ovo nije samo informacija. Ovo je prvi korak prema ulasku u tim.',
            'text' => 'FCC ti daje sustav, proizvode, smjer i podršku. Tvoj zadatak je odabrati gdje si sada, a ja ću te kroz sljedeće korake usmjeriti prema odluci koja ima smisla za tebe.',
        ]);
        $replace_block($variant_blocks, 'landing_b_direction', [
            'title' => 'Odaberi svoj sljedeći korak',
            'text' => 'Bez pritiska. Odaberi ono što ti je trenutno najbliže i nastavi kroz vodič.',
        ]);
        $replace_option_labels($variant_blocks, 'landing_b_direction', [
            'landing_business' => 'Želim krenuti s online poslom',
            'landing_demo' => 'Pokaži mi kako radi FCC sustav',
            'landing_product' => 'Prvo želim upoznati proizvode',
            'landing_ready' => 'Želim start paket i ulazak u tim',
            'landing_b_business' => 'Želim krenuti s online poslom',
            'landing_b_demo' => 'Pokaži mi kako radi FCC sustav',
            'landing_b_product' => 'Prvo želim upoznati proizvode',
            'landing_b_ready' => 'Želim start paket i ulazak u tim',
        ]);
    }
}

function vip_funnel_set_complete_landing_variant_b(array &$payload, array $copy): void {
    if(empty($payload['landing_page']) || !is_array($payload['landing_page']) || empty($payload['landing_page']['blocks']) || !is_array($payload['landing_page']['blocks'])) {
        return;
    }

    $blocks_by_id = [];

    foreach($payload['landing_page']['blocks'] as $block) {
        if(is_array($block) && !empty($block['id'])) {
            $blocks_by_id[(string) $block['id']] = $block;
        }
    }

    $clone = static function(string $source_id, string $target_id, array $updates = []) use ($blocks_by_id): ?array {
        if(empty($blocks_by_id[$source_id]) || !is_array($blocks_by_id[$source_id])) {
            return null;
        }

        $block = $blocks_by_id[$source_id];
        $block['id'] = $target_id;

        return array_replace_recursive($block, $updates);
    };

    $variant_blocks = array_values(array_filter([
        $clone('landing_hero', 'landing_b_hero', $copy['hero'] ?? []),
        $clone('landing_intro_video', 'landing_b_intro_video', $copy['intro_video'] ?? []),
        $clone('landing_proof', 'landing_b_proof', $copy['proof'] ?? []),
        $clone('landing_direction', 'landing_b_direction', $copy['direction'] ?? []),
    ]));

    if(!empty($variant_blocks)) {
        $payload['landing_page']['variant_b_blocks'] = $variant_blocks;
    }
}

function vip_funnel_complete_fcc_vip_landing_variant_b_if_needed(array &$payload): void {
    $funnel_name = (string) ($payload['funnel']['name'] ?? '');
    $overview_eyebrow = (string) ($payload['overview']['eyebrow'] ?? '');
    $landing_name = (string) ($payload['landing_page']['name'] ?? '');
    $overview_headline = (string) ($payload['overview']['headline'] ?? '');
    $is_fcc_vip_template = str_contains($funnel_name, 'FCC VIP Funnel')
        || str_contains($overview_eyebrow, 'FCC VIP Funnel')
        || str_contains($landing_name, 'FCC VIP Funnel');
    $is_stjepan_recruiting_template = str_contains($funnel_name, 'Stjepan')
        || str_contains($funnel_name, 'FCC Recruiting Funnel')
        || str_contains($landing_name, 'recruiting landing')
        || str_contains($overview_headline, 'mentorstvo');

    if((!$is_fcc_vip_template && !$is_stjepan_recruiting_template) || empty($payload['landing_page']['blocks']) || !is_array($payload['landing_page']['blocks'])) {
        return;
    }

    $variant_b_blocks = $payload['landing_page']['variant_b_blocks'] ?? [];
    if(is_array($variant_b_blocks) && count($variant_b_blocks) >= 4) {
        return;
    }

    $source_ids = [];
    foreach((array) ($payload['landing_page']['blocks'] ?? []) as $block) {
        if(is_array($block) && !empty($block['id'])) {
            $source_ids[(string) $block['id']] = true;
        }
    }

    $variant_ids = [];
    foreach((array) $variant_b_blocks as $block) {
        if(is_array($block) && !empty($block['id'])) {
            $variant_ids[(string) $block['id']] = true;
        }
    }

    $has_complete_landing_source = isset($source_ids['landing_hero'], $source_ids['landing_intro_video'], $source_ids['landing_proof'], $source_ids['landing_direction']);
    $has_legacy_short_variant = empty($variant_ids)
        || isset($variant_ids['landing_b_hero'])
        || isset($variant_ids['landing_b_direction']);

    if(!$has_complete_landing_source || !$has_legacy_short_variant) {
        return;
    }

    if($is_stjepan_recruiting_template && !$is_fcc_vip_template) {
        vip_funnel_set_complete_landing_variant_b($payload, [
            'hero' => [
                'badge' => 'Online posao uz vođeni sustav',
                'title' => 'Pridruži se mom FCC timu i kreni graditi online posao uz jasan sustav',
                'text' => 'Ako želiš ozbiljno krenuti, ne moraš sve smišljati sam/a. Kroz FCC sustav, proizvode, edukaciju i mentorstvo pokazat ću ti kako napraviti prve korake i uključiti se u tim.',
                'title_size' => 50,
            ],
            'intro_video' => [
                'title' => 'Kratka poruka prije nego odabereš svoj put',
                'text' => 'Prvo pogledaj video kako bi razumio/la što je FCC, kako izgleda suradnja i što znači krenuti uz moje mentorstvo.',
            ],
            'proof' => [
                'badge' => 'Kako ovo funkcionira',
                'title' => 'Ovo nije samo informacija. Ovo je prvi korak prema ulasku u tim.',
                'text' => 'FCC ti daje sustav, proizvode, smjer i podršku. Tvoj zadatak je odabrati gdje si sada, a ja ću te kroz sljedeće korake usmjeriti prema odluci koja ima smisla za tebe.',
            ],
            'direction' => [
                'title' => 'Odaberi svoj sljedeći korak',
                'text' => 'Bez pritiska. Odaberi ono što ti je trenutno najbliže i nastavi kroz vodič.',
                'options' => [
                    ['label' => 'Želim krenuti s online poslom'],
                    ['label' => 'Pokaži mi kako radi FCC sustav'],
                    ['label' => 'Prvo želim upoznati proizvode'],
                    ['label' => 'Želim start paket i ulazak u tim'],
                ],
            ],
        ]);

        return;
    }

    $hero_title = (string) ($payload['landing_page']['blocks'][0]['title'] ?? '');
    $is_en = stripos($hero_title, 'start an online business') !== false
        || stripos($hero_title, 'guided mentorship') !== false;

    if($is_en) {
        vip_funnel_set_complete_landing_variant_b($payload, [
            'hero' => [
                'badge' => 'From interest to system',
                'title' => 'From first interest to your own online business',
                'text' => 'If FCC caught your attention, this short funnel shows the best next step without overwhelming you.',
                'title_size' => 48,
            ],
            'intro_video' => [
                'title' => 'Short intro from your FCC mentor',
                'text' => 'Watch the short message first, then choose the path that best matches your situation.',
            ],
            'proof' => [
                'badge' => 'Why this is not just another link',
                'title' => 'FCC connects attention, clear selection, products, and mentorship into one guided system.',
                'text' => 'A new visitor does not need to read everything at once. The funnel guides them toward business, demo, or product path, while the mentor sees who is ready for a serious conversation.',
            ],
            'direction' => [
                'title' => 'Choose your fastest path',
                'text' => 'Business, demo, or products. The system guides you without overload.',
            ],
        ]);

        return;
    }

    vip_funnel_set_complete_landing_variant_b($payload, [
        'hero' => [
            'badge' => 'Od interesa do sustava',
            'title' => 'Od prvog interesa do vlastitog online posla',
            'text' => 'Ako te zanima FCC, ovaj kratki funnel će ti pokazati najbolji sljedeći korak bez previše informacija odjednom.',
            'title_size' => 48,
        ],
        'intro_video' => [
            'title' => 'Kratki uvod tvog FCC mentora',
            'text' => 'Pogledaj prvo kratku poruku, a zatim odaberi smjer koji je najbliži tvojoj situaciji.',
        ],
        'proof' => [
            'badge' => 'Zašto ovo nije običan link',
            'title' => 'FCC spaja pažnju, jasnu selekciju, proizvode i mentorstvo u jedan vođeni sustav.',
            'text' => 'Novi posjetitelj ne mora čitati sve odjednom. Funnel ga vodi prema poslu, demo iskustvu ili proizvodnom putu, a mentoru pokazuje tko je spreman za ozbiljan razgovor.',
        ],
        'direction' => [
            'title' => 'Odaberi svoj najbrži put',
            'text' => 'Posao, demo ili proizvodi. Sustav te vodi bez viška informacija.',
        ],
    ]);
}

function vip_funnel_get_fcc_vip_import_template_payload($user = null, string $language = 'hr'): array {
    $language = vip_funnel_resolve_import_template_language($language);
    $owner_profile = vip_funnel_get_owner_contact_profile($user);
    $mentor_name = trim((string) ($owner_profile['name'] ?? ($user->name ?? '')));
    $mentor_name = $mentor_name !== '' ? $mentor_name : 'FCC mentor';
    $mentor_first_name = trim((string) preg_replace('/\s+.*/', '', $mentor_name));
    $mentor_first_name = $mentor_first_name !== '' ? $mentor_first_name : 'mentor';
    $mentor_email = trim((string) ($owner_profile['email'] ?? ($user->email ?? '')));
    $mentor_email = filter_var($mentor_email, FILTER_VALIDATE_EMAIL) ? $mentor_email : 'info@forevercard.club';
    $contact_url = trim((string) ($owner_profile['whatsapp_url'] ?? '')) ?: ('mailto:' . rawurlencode($mentor_email));
    $product_shop_url = trim((string) ($owner_profile['main_biolink_url'] ?? '')) ?: SITE_URL . 'blog';

    $payload = vip_funnel_get_stjepan_recruitment_payload($user, [
        'contact_email' => $mentor_email,
        'whatsapp_url' => $contact_url,
        'calendar_url' => $contact_url,
        'product_shop_url' => $product_shop_url,
        'facebook_pixel_id' => '',
    ]);

    $apply_hr_copy = static function(array &$payload) use ($mentor_name, $mentor_first_name, $mentor_email, $contact_url, $product_shop_url): void {
        $payload['funnel']['name'] = $mentor_name . ' - FCC VIP Funnel HR';
        $payload['funnel']['slug'] = vip_funnel_slugify($mentor_name . ' FCC VIP Funnel HR');
        $payload['funnel']['status'] = 'draft';
        $payload['funnel']['visibility_mode'] = 'pro_live';
        $payload['funnel']['owner_mode'] = 'shared';

        $payload['overview'] = [
            'eyebrow' => 'FCC VIP Funnel',
            'headline' => 'Pokreni online posao uz FCC sustav i mentorstvo',
            'subheadline' => 'U par koraka odaberi želiš li pokrenuti posao, vidjeti demo sustava ili prvo krenuti kroz proizvode i popust. Tvoj mentor: ' . $mentor_name . '.',
            'primary_cta' => 'Provjeri svoj put',
            'secondary_cta' => 'Pogledaj FCC demo',
        ];

        $payload['positioning'] = [
            'for' => 'Za osobe koje dolaze s društvenih mreža, preporuke ili FCC aplikacije i žele posao, demo FCC sustava ili proizvodni popust.',
            'problem' => 'Interes se lako izgubi ako osoba nema jasan, kratak i logičan sljedeći korak.',
            'mechanism' => 'Funnel segmentira posjetitelja, kvalificira spremnost i vodi ga prema Start paketu, razgovoru, demo iskustvu ili proizvodnoj preporuci.',
            'offer_promise' => 'Jasan put od interesa do odluke uz osobno mentorstvo i FCC sustav.',
            'why_now' => 'Svaki suradnik dobiva isti profesionalni okvir, ali s vlastitim imenom, kontaktom i referral vlasništvom.',
        ];

        $payload['proof']['mentor_intro'] = $mentor_name . ' je tvoj FCC mentor i vodi te kroz prve konkretne korake u sustavu.';
        $payload['follow_up']['message_1'] = 'Bok, ' . $mentor_first_name . ' ovdje. Vidio/la sam tvoj odabir i šaljem ti najbolji sljedeći korak.';
        $payload['follow_up']['message_2'] = 'Najveća razlika je krenuti sam ili uz sustav. FCC je napravljen da novi ljudi ne moraju sve izmišljati od nule.';
        $payload['follow_up']['message_3'] = 'Ako želiš, mogu ti u par minuta reći je li za tebe bolji Start paket, demo ili proizvodni put.';
        $payload['defaults']['contact_email'] = $mentor_email;
        $payload['defaults']['checkout_url'] = $contact_url;
        $payload['defaults']['whatsapp_url'] = $contact_url;
        $payload['defaults']['calendar_url'] = $contact_url;
        $payload['defaults']['product_shop_url'] = $product_shop_url;
        $payload['defaults']['hide_public_navbar'] = true;

        if(isset($payload['landing_page']) && is_array($payload['landing_page'])) {
            $payload['landing_page']['name'] = 'FCC VIP Funnel - personalizirani landing';
            $payload['landing_page']['variant_b_settings']['name'] = 'FCC VIP Funnel - alternativni landing';
        }

        vip_funnel_update_template_block($payload, 'landing_hero', [
            'badge' => 'FCC VIP Funnel',
            'title' => 'Pokreni online posao uz FCC sustav i mentorstvo',
            'text' => 'Ja sam ' . $mentor_name . ', tvoj FCC mentor. Ovdje u par koraka biraš svoj put: online posao, demo sustava ili proizvodi i popust.',
        ]);
        vip_funnel_update_template_block($payload, 'landing_intro_video', [
            'title' => 'Kratki uvod tvog FCC mentora',
            'text' => 'Pogledaj prvo kratku poruku, a zatim odaberi smjer koji je najbliži tvojoj situaciji.',
        ]);
        vip_funnel_update_template_block($payload, 'landing_proof', [
            'badge' => 'Zašto ovo nije običan link',
            'title' => 'FCC spaja pažnju, jasnu selekciju, proizvode i mentorstvo u jedan vođeni sustav.',
            'text' => 'Novi posjetitelj ne mora čitati sve odjednom. Funnel ga vodi prema poslu, demo iskustvu ili proizvodnom putu, a mentoru pokazuje tko je spreman za ozbiljan razgovor.',
        ]);
        vip_funnel_update_template_block($payload, 'landing_b_hero', [
            'badge' => 'Od interesa do sustava',
            'title' => 'Od prvog interesa do vlastitog online posla',
            'text' => 'Ako te zanima FCC, ovaj kratki funnel će ti pokazati najbolji sljedeći korak bez previše informacija odjednom.',
        ]);
        vip_funnel_update_template_block($payload, 'business_hero', [
            'title' => 'Ako želiš krenuti ozbiljno, kreni uz sustav, tim i mentora.',
            'text' => 'Ovdje ti ' . $mentor_name . ' pokazuje što znači ulazak u FCC tim: kako se radi, što dobivaš kroz sustav, koji su prvi koraci i kada ima smisla uzeti start paket. Bez lutanja, bez previše informacija odjednom - samo jasan sljedeći korak.',
        ]);
        vip_funnel_update_template_block($payload, 'business_video', [
            'title' => 'Kako izgleda ulazak u FCC tim',
            'text' => 'Pogledaj kratku poruku prije odluke. Tvoj mentor će ti pokazati što radiš u prvom tjednu, kako koristiš sustav i što znači imati mentora uz sebe.',
        ]);
        vip_funnel_update_template_block($payload, 'business_system', [
            'badge' => 'Što dobivaš',
            'title' => 'Ne krećeš od nule. Krećeš kroz jasan FCC okvir.',
            'text' => 'Dobivaš smjer za prve objave, prezentaciju, preporuke, razgovore i follow-up. Rezultat ovisi o tvojoj aktivnosti, ali ne ulaziš naslijepo - znaš što radiš prvi dan, prvi tjedan i nakon toga.',
        ]);
        vip_funnel_update_template_block($payload, 'business_choice', [
            'title' => 'Što ti sada treba za odluku?',
            'text' => 'Odaberi iskreno gdje si trenutno. Tako će te funnel odvesti na najbrži sljedeći korak bez nepotrebnog lutanja.',
            'options' => [
                ['id' => 'business_serious', 'label' => 'Spreman/na sam za start paket i ulazak u tim', 'value' => 'ready_360_now', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'start_package_offer', 'external_url' => '', 'require_submit' => false],
                ['id' => 'business_check', 'label' => 'Želim kratku provjeru prije starta', 'value' => 'needs_check', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'qualification_form', 'external_url' => '', 'require_submit' => false],
                ['id' => 'business_demo', 'label' => 'Prvo želim vidjeti kako sustav radi', 'value' => 'demo_interest', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'fcc_demo_preview', 'external_url' => '', 'require_submit' => false],
                ['id' => 'business_not_ready', 'label' => 'Nisam još spreman/na za start paket', 'value' => 'not_ready', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'not_ready_nurture', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_sync_template_step_blocks($payload, 'qualification_form', [
            'qualification_hero' => [
                'id' => 'qualification_hero',
                'type' => 'headline',
                'badge' => 'Kratka provjera prije starta',
                'title' => 'Odgovori iskreno i pokazat ću ti najbolji sljedeći korak.',
                'text' => 'Ovo nije test ni obveza. Treba mi nekoliko iskrenih odgovora da vidim ima li smisla da kreneš sa start paketom sada, da prvo prođemo kratak razgovor ili da pogledaš sustav mirnijim tempom.',
            ],
            'qualification_goal' => [
                'id' => 'qualification_goal',
                'type' => 'radio_survey',
                'title' => 'Što želiš postići kroz FCC?',
                'text' => 'Odaberi ono što ti je trenutno najbliže. Ne mora biti konačna odluka.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'goal_extra_income', 'label' => 'Dodatni prihod uz postojeće obaveze', 'hint' => 'Želim krenuti postupno, ali uz jasan plan i podršku.', 'value' => 'dodatni_prihod', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'goal_serious_business', 'label' => 'Ozbiljan online posao', 'hint' => 'Spreman/na sam učiti, raditi i dugoročnije graditi svoj tim.', 'value' => 'ozbiljan_online_posao', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'goal_product_first', 'label' => 'Prvo proizvodi i popust, posao kasnije', 'hint' => 'Želim upoznati proizvode, koristiti pogodnosti i vidjeti ima li smisla preporučivati.', 'value' => 'product_discount', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'goal_research', 'label' => 'Samo istražujem', 'hint' => 'Želim razumjeti opcije bez pritiska i vidjeti je li FCC za mene.', 'value' => 'samo_istrazujem', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_time' => [
                'id' => 'qualification_time',
                'type' => 'radio_survey',
                'title' => 'Koliko vremena realno možeš odvojiti tjedno?',
                'text' => 'Bolje je odgovoriti realno nego idealno. Tako ćeš dobiti tempo koji ima smisla za tvoju situaciju.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'time_1_3', 'label' => '1-3 sata tjedno', 'hint' => 'Za miran početak, osnovne zadatke i postupno upoznavanje sustava.', 'value' => 'time_1_3', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'time_4_7', 'label' => '4-7 sati tjedno', 'hint' => 'Dovoljno za ozbiljan start, prve kontakte i stvaranje radne navike.', 'value' => 'time_4_7', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'time_8_plus', 'label' => '8+ sati tjedno', 'hint' => 'Spreman/na sam za brži ritam i aktivniji početak.', 'value' => 'time_8_plus', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'time_no_capacity', 'label' => 'Trenutno ne mogu odvojiti vrijeme', 'hint' => 'Bolje je krenuti kasnije nego pod pritiskom i bez fokusa.', 'value' => 'time_no_capacity', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_investment' => [
                'id' => 'qualification_investment',
                'type' => 'radio_survey',
                'title' => 'Je li ti Start Your Journey paket od 360 € realan ovaj tjedan ako zaključiš da je FCC za tebe?',
                'text' => 'Ovo pitanje ne služi za pritisak, nego da te ne vodim na pogrešan sljedeći korak.',
                'required' => true,
                'route_on_submit' => true,
                'options' => [
                    ['id' => 'ready_now', 'label' => 'Da, mogu odmah', 'hint' => 'Ako mi je smjer jasan, mogu napraviti narudžbu i krenuti.', 'value' => 'ready_360_now', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'start_package_offer', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'ready_call', 'label' => 'Da, ali želim kratak razgovor', 'hint' => 'Trebam potvrdu i par odgovora prije narudžbe.', 'value' => 'ready_360_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'mentor_call_request', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'need_demo', 'label' => 'Prvo želim vidjeti sustav', 'hint' => 'Želim bolje razumjeti kako FCC radi prije odluke.', 'value' => 'trebam_prvo_vidjeti_sustav', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'fcc_demo_preview', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'not_now', 'label' => 'Ne sada', 'hint' => 'Trenutno nije pravi trenutak za start paket.', 'value' => 'not_ready', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'not_ready_nurture', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_channel' => [
                'id' => 'qualification_channel',
                'type' => 'radio_survey',
                'title' => 'Kako želiš da te kontaktiram?',
                'text' => 'Za ovu provjeru ne koristim email kao glavni kanal. Najbrže i najjasnije je preko WhatsAppa ili kratkog poziva.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'channel_whatsapp', 'label' => 'WhatsApp poruka', 'hint' => 'Najbrže za prvi kontakt, dogovor i slanje sljedećih uputa.', 'value' => 'channel_whatsapp', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'channel_phone', 'label' => 'Kratki telefonski poziv', 'hint' => 'Najbolje ako želiš brzo proći pitanja i donijeti odluku.', 'value' => 'channel_phone', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'channel_whatsapp_call', 'label' => 'WhatsApp pa poziv', 'hint' => 'Prvo ti pošaljem poruku, pa dogovorimo najbolji termin za razgovor.', 'value' => 'channel_whatsapp_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_name' => ['id' => 'qualification_name', 'type' => 'full_name_field', 'title' => 'Ime i prezime', 'placeholder' => 'Upiši ime i prezime', 'required' => true, 'layout_width' => 'half'],
            'qualification_phone' => ['id' => 'qualification_phone', 'type' => 'phone_field', 'title' => 'WhatsApp / telefon', 'placeholder' => 'Upiši broj na koji te mogu brzo kontaktirati', 'required' => true, 'layout_width' => 'half'],
            'qualification_contact_time' => ['id' => 'qualification_contact_time', 'type' => 'text_field', 'title' => 'Najbolje vrijeme za kontakt', 'placeholder' => 'npr. danas poslije 17h, sutra ujutro, navečer', 'field_key' => 'contact_time', 'required' => false, 'layout_width' => 'full'],
            'qualification_privacy' => [
                'id' => 'qualification_privacy',
                'type' => 'text',
                'text' => 'Slanjem odgovora potvrđuješ da te ' . $mentor_name . ' ili FCC tim smije kontaktirati vezano uz tvoj odabrani FCC smjer putem WhatsAppa ili telefonskog poziva. Tvoje podatke koristimo za obradu upita i komunikaciju vezanu uz FCC. Privacy: ' . SITE_URL . 'page/privacy-policy',
                'text_size' => 14,
            ],
            'qualification_submit' => [
                'id' => 'qualification_submit',
                'type' => 'cta_group',
                'text' => 'Pošalji odgovore i dobit ćeš preporučeni sljedeći korak. Nakon slanja vidjet ćeš preporučeni korak, a ako si odabrao/la kontakt, mentor će ti se javiti preko WhatsAppa ili poziva.',
                'buttons' => [
                    ['id' => 'qualification_submit_btn', 'label' => 'Pošalji provjeru i pokaži mi sljedeći korak', 'value' => 'qualification_submit', 'style' => 'primary', 'action' => 'submit_next', 'target_step_id' => '', 'external_url' => '', 'require_submit' => true],
                ],
                'alignment' => 'center',
            ],
        ], [
            'qualification_hero',
            'qualification_goal',
            'qualification_time',
            'qualification_investment',
            'qualification_channel',
            'qualification_name',
            'qualification_phone',
            'qualification_contact_time',
            'qualification_privacy',
            'qualification_submit',
        ], ['qualification_email']);
        vip_funnel_update_template_step($payload, 'qualification_form', [
            'title' => 'Kratka provjera prije starta',
            'summary' => 'Kvalifikacija sprema odgovore, WhatsApp/telefon i najbolji termin kontakta te vodi na pravi nastavak.',
            'helper_text' => 'Kvalifikacija sprema odgovore, WhatsApp/telefon i najbolji termin kontakta te vodi na pravi nastavak.',
            'preview_headline' => 'Kratka provjera prije starta',
            'preview_body' => 'Odgovori iskreno i sustav te vodi prema start paketu, razgovoru, demo prikazu ili mirnijem uvodu.',
            'page' => ['name' => 'Kratka provjera prije starta'],
        ]);
        vip_funnel_update_template_block($payload, 'demo_hero', [
            'badge' => 'FCC sustav',
            'title' => 'Prvo razumij kako FCC funkcionira, pa odluči je li ovo za tebe.',
            'text' => 'U ovom koraku tvoj mentor pokazuje kako FCC vodi osobu od prvog interesa do jasnog sljedećeg koraka: proizvodi, provjera, demo ili ulazak u tim. Ako odlučiš krenuti, isti okvir možeš koristiti i u svom poslu uz mentorstvo.',
            'title_size' => 50,
        ]);
        vip_funnel_update_template_block($payload, 'demo_video', [
            'title' => 'Kako izgleda FCC sustav u praksi',
            'text' => 'Pogledaj kratki prikaz sustava: kako se osoba vodi kroz funnel, kako se biraju proizvodi ili poslovni put, kako izgleda kontakt i što se događa nakon prvog interesa.',
        ]);
        vip_funnel_update_template_block($payload, 'demo_actions', [
            'text' => 'Nakon što pogledaš objašnjenje, odaberi što ti je sada najbliže.',
            'buttons' => [
                ['id' => 'demo_qualify', 'label' => 'Želim provjeriti je li FCC za mene', 'value' => 'business_interest', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'qualification_form', 'external_url' => '', 'require_submit' => false],
                ['id' => 'demo_start', 'label' => 'Spreman/na sam za start paket i ulazak u tim', 'value' => 'ready_360_now', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'start_package_offer', 'external_url' => '', 'require_submit' => false],
                ['id' => 'demo_request', 'label' => 'Želim detaljniji demo sustava', 'value' => 'demo_request', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'demo_request', 'external_url' => '', 'require_submit' => false],
                ['id' => 'demo_products', 'label' => 'Zanimaju me samo proizvodi i popusti', 'value' => 'product_discount', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'product_gateway', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_sync_template_step_blocks($payload, 'demo_request', vip_funnel_get_demo_request_step_blocks('hr', $mentor_name, SITE_URL . 'page/privacy-policy'), [
            'demo_request_hero',
            'demo_request_reason',
            'demo_request_name',
            'demo_request_phone',
            'demo_request_email',
            'demo_request_focus',
            'demo_request_privacy',
            'demo_request_submit',
        ]);
        vip_funnel_update_template_step($payload, 'demo_request', [
            'title' => 'Zatraži kontrolirani demo pristup FCC sustavu',
            'summary' => 'Kontrolirani demo zahtjev s razlogom, WhatsApp/telefonom i emailom samo za demo pristup.',
            'helper_text' => 'Kontrolirani demo zahtjev s razlogom, WhatsApp/telefonom i emailom samo za demo pristup.',
            'preview_headline' => 'Zatraži kontrolirani demo pristup FCC sustavu',
            'preview_body' => 'Demo nije javni link. Osoba ostavlja razlog, kontakt i što želi vidjeti.',
            'page' => ['name' => 'Zatraži kontrolirani demo pristup FCC sustavu'],
        ]);
        vip_funnel_update_template_block($payload, 'product_gateway_hero', [
            'badge' => 'Proizvodi i popust',
            'title' => 'Kreni od proizvoda koji ti stvarno treba, a poslovni put može doći kasnije.',
            'text' => 'Ako te sada najviše zanimaju Forever proizvodi, odaberi cilj koji ti je najbliži. Dobit ćeš jednostavnu preporuku, mogućnost popusta i jasan sljedeći korak bez pritiska.',
            'title_size' => 50,
        ]);
        vip_funnel_update_template_block($payload, 'product_goal', [
            'title' => 'Što želiš prvo poboljšati?',
            'text' => 'Odaberi najbliži cilj i funnel će te voditi prema preporuci koja ima najviše smisla za tvoju situaciju.',
            'options' => [
                ['id' => 'product_energy', 'label' => 'Više energije i dnevna vitalnost', 'value' => 'vise_energije', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_weight', 'label' => 'Regulacija težine i bolja forma', 'value' => 'regulacija_tezine', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_skin', 'label' => 'Njega kože i osobna rutina', 'value' => 'njega_koze', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_routine', 'label' => 'Opća dnevna rutina za mene ili obitelj', 'value' => 'dnevna_rutina', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_discount', 'label' => 'Želim preporuku i popust', 'value' => 'popust', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_update_template_block($payload, 'start_hero', [
            'badge' => 'Start Your Journey | 360 €',
            'title' => 'Ako želiš ući u FCC tim, start paket je tvoj prvi konkretan korak.',
            'text' => 'Kroz Start Your Journey dobivaš proizvode, ulazak u sustav, početnu edukaciju i mentorstvo za prve korake. Nakon narudžbe ne ostaješ sam/a - tvoj mentor te vodi kroz postavljanje, razumijevanje ponude i prve zadatke.',
            'title_size' => 50,
        ]);
        vip_funnel_update_template_block($payload, 'start_video', [
            'title' => 'Prije narudžbe pogledaj što točno dobivaš',
            'text' => 'U kratkom videu tvoj mentor pokazuje što uključuje start paket, kako izgleda prvi tjedan i što se događa nakon što pošalješ narudžbu.',
        ]);
        vip_funnel_update_template_block($payload, 'start_included', [
            'badge' => 'Dobivaš',
            'title' => 'Proizvode, FCC sustav, prve zadatke i mentorstvo za start.',
            'text' => 'Ne kupuješ obećanje lake zarade. Dobivaš proizvode, jasan početni okvir, edukaciju i podršku. Rezultat ovisi o tvojoj aktivnosti, učenju, komunikaciji i dosljednosti.',
        ]);
        vip_funnel_update_template_block($payload, 'start_first_week', [
            'badge' => 'Prvih 7 dana',
            'title' => 'Postavljanje, prvi zadaci, razumijevanje ponude i prvi kontakti.',
            'text' => 'Cilj je da nakon narudžbe ne ostaneš sam/a, nego da odmah znaš koji je tvoj prvi korak, kako koristiti sustav i kako krenuti s preporukama.',
        ]);
        vip_funnel_update_template_block($payload, 'start_countdown', [
            'title' => 'Sljedeći Zoom onboarding za nove suradnike',
            'text' => 'Onboarding se održava svaki četvrtak u 20:00 putem Zooma. Nakon registracije mentor će te kontaktirati i poslati Zoom link, pripremu i sve što ti treba za prvi webinar i početak.',
            'countdown_mode' => 'weekly',
            'countdown_weekly_day' => 4,
            'countdown_weekly_time' => '20:00',
            'countdown_timezone' => 'Europe/Zagreb',
            'duration_days' => 0,
            'duration_minutes' => 0,
        ]);
        vip_funnel_update_template_block($payload, 'start_actions', [
            'text' => 'Odaberi kako želiš napraviti sljedeći korak. Nakon klika dobit ćeš upute za narudžbu i povezivanje s mentorom prije onboardinga.',
            'buttons' => [
                ['id' => 'start_order', 'label' => 'Želim naručiti Start Your Journey paket', 'hint' => 'Preusmjerava te na službenu Forever Living stranicu za narudžbu i upis s preporukom tvog mentora i automatskim odabirom zemlje.', 'value' => 'order_start_package', 'style' => 'primary', 'action' => 'external_url', 'target_step_id' => '', 'external_url' => vip_funnel_get_forever_business_referral_action_token(), 'require_submit' => false],
                ['id' => 'start_whatsapp', 'label' => 'Imam pitanje prije narudžbe', 'value' => 'start_whatsapp', 'style' => 'secondary', 'action' => 'external_url', 'target_step_id' => '', 'external_url' => vip_funnel_get_start_package_question_whatsapp_url((int) ($payload['defaults']['owner_user_id'] ?? 0), 'hr') ?: $contact_url, 'require_submit' => false],
                ['id' => 'start_call', 'label' => 'Nisam još siguran/na - želim kratki razgovor', 'value' => 'ready_360_call', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'mentor_call_request', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_sync_template_step_blocks($payload, 'mentor_call_request', vip_funnel_get_call_request_step_blocks('hr', $mentor_name, SITE_URL . 'page/privacy-policy', vip_funnel_resolve_fast_contact_url($contact_url)), [
            'call_hero',
            'call_reason',
            'call_channel',
            'call_name',
            'call_phone',
            'call_contact_time',
            'call_privacy',
            'call_actions',
        ], ['call_email']);

        vip_funnel_update_template_step($payload, 'business_gateway', [
            'title' => 'Poslovni filter za ozbiljan FCC start',
            'summary' => 'Vodi prema start paketu, kratkoj provjeri, demo prikazu sustava ili mirnijem uvodu.',
            'helper_text' => 'Vodi prema start paketu, kratkoj provjeri, demo prikazu sustava ili mirnijem uvodu.',
            'preview_headline' => 'Poslovni filter za ozbiljan FCC start',
            'preview_body' => 'Vodi prema start paketu, kratkoj provjeri, demo prikazu sustava ili mirnijem uvodu.',
            'page' => ['name' => 'Poslovni filter za ozbiljan FCC start'],
        ]);
        vip_funnel_update_template_step($payload, 'fcc_demo_preview', [
            'title' => 'Razumij FCC sustav prije odluke',
            'summary' => 'Mirno objašnjava kako FCC vodi od interesa do proizvoda, provjere, demo prikaza ili ulaska u tim.',
            'helper_text' => 'Mirno objašnjava kako FCC vodi od interesa do proizvoda, provjere, demo prikaza ili ulaska u tim.',
            'preview_headline' => 'Razumij FCC sustav prije odluke',
            'preview_body' => 'Mirno objašnjava kako FCC vodi od interesa do proizvoda, provjere, demo prikaza ili ulaska u tim.',
            'page' => ['name' => 'Razumij FCC sustav prije odluke'],
        ]);
        vip_funnel_update_template_step($payload, 'product_gateway', [
            'title' => 'Proizvodni put s jasnom preporukom',
            'summary' => 'Vodi osobu prema proizvodnom cilju, popustu i kasnijem poslovnom mostu bez pritiska.',
            'helper_text' => 'Vodi osobu prema proizvodnom cilju, popustu i kasnijem poslovnom mostu bez pritiska.',
            'preview_headline' => 'Proizvodni put s jasnom preporukom',
            'preview_body' => 'Vodi osobu prema proizvodnom cilju, popustu i kasnijem poslovnom mostu bez pritiska.',
            'page' => ['name' => 'Proizvodni put s jasnom preporukom'],
        ]);
        vip_funnel_update_template_step($payload, 'start_package_offer', [
            'title' => 'Start Your Journey: ulazak u FCC tim i prvi konkretan korak',
            'summary' => 'Konverzijska stranica za start paket, ulazak u tim i onboarding.',
            'helper_text' => 'Konverzijska stranica za start paket, ulazak u tim i onboarding.',
            'preview_headline' => 'Start Your Journey: ulazak u FCC tim i prvi konkretan korak',
            'preview_body' => 'Konverzijska stranica za start paket, ulazak u tim i onboarding.',
            'page' => ['name' => 'Start Your Journey: ulazak u FCC tim i prvi konkretan korak'],
        ]);
        vip_funnel_update_template_step($payload, 'mentor_call_request', [
            'title' => 'Zatraži kratak razgovor prije odluke',
            'summary' => 'Smiruje nesigurnost i vodi osobu prema WhatsAppu ili pozivu bez pritiska.',
            'helper_text' => 'Smiruje nesigurnost i vodi osobu prema WhatsAppu ili pozivu bez pritiska.',
            'preview_headline' => 'Zatraži kratak razgovor prije odluke',
            'preview_body' => 'Smiruje nesigurnost i vodi osobu prema WhatsAppu ili pozivu bez pritiska.',
            'page' => ['name' => 'Zatraži kratak razgovor prije odluke'],
        ]);

        vip_funnel_set_complete_landing_variant_b($payload, [
            'hero' => [
                'badge' => 'Od interesa do sustava',
                'title' => 'Od prvog interesa do vlastitog online posla',
                'text' => 'Ako te zanima FCC, ovaj kratki funnel će ti pokazati najbolji sljedeći korak bez previše informacija odjednom.',
            ],
            'intro_video' => [
                'title' => 'Kratki uvod tvog FCC mentora',
                'text' => 'Pogledaj prvo kratku poruku, a zatim odaberi smjer koji je najbliži tvojoj situaciji.',
            ],
            'proof' => [
                'badge' => 'Zašto ovo nije običan link',
                'title' => 'FCC spaja pažnju, jasnu selekciju, proizvode i mentorstvo u jedan vođeni sustav.',
                'text' => 'Novi posjetitelj ne mora čitati sve odjednom. Funnel ga vodi prema poslu, demo iskustvu ili proizvodnom putu, a mentoru pokazuje tko je spreman za ozbiljan razgovor.',
            ],
            'direction' => [
                'title' => 'Odaberi svoj najbrži put',
                'text' => 'Posao, demo ili proizvodi. Sustav te vodi bez viška informacija.',
            ],
        ]);
    };

    $apply_en_copy = static function(array &$payload) use ($mentor_name, $mentor_first_name, $mentor_email, $contact_url, $product_shop_url): void {
        $payload['funnel']['name'] = $mentor_name . ' - FCC VIP Funnel ENG';
        $payload['funnel']['slug'] = vip_funnel_slugify($mentor_name . ' FCC VIP Funnel ENG');
        $payload['funnel']['status'] = 'draft';
        $payload['funnel']['visibility_mode'] = 'pro_live';
        $payload['funnel']['owner_mode'] = 'shared';

        $payload['overview'] = [
            'eyebrow' => 'FCC VIP Funnel',
            'headline' => 'Start an online business with FCC and guided mentorship',
            'subheadline' => 'In a few clear steps, choose whether you want to start the business, see the FCC system demo, or begin with products and a discount. Your mentor: ' . $mentor_name . '.',
            'primary_cta' => 'Check your path',
            'secondary_cta' => 'See the FCC demo',
        ];

        $payload['positioning'] = [
            'for' => 'For people coming from social media, referrals, or the FCC app who want a business, FCC system demo, or product discount.',
            'problem' => 'Interest is easy to lose when a visitor does not get a clear, short, and logical next step.',
            'mechanism' => 'The funnel segments visitors, qualifies readiness, and guides them toward the Start package, a mentor conversation, demo experience, or product recommendation.',
            'offer_promise' => 'A clear path from interest to decision with personal mentorship and the FCC system.',
            'why_now' => 'Every collaborator gets the same professional framework, personalized with their own name, contact, and referral ownership.',
        ];

        $payload['proof']['mentor_intro'] = $mentor_name . ' is your FCC mentor and guides you through the first concrete steps in the system.';
        $payload['follow_up']['message_1'] = 'Hi, ' . $mentor_first_name . ' here. I saw your choice and I am sending you the best next step.';
        $payload['follow_up']['message_2'] = 'The biggest difference is starting alone or with a system. FCC is built so new people do not have to invent everything from zero.';
        $payload['follow_up']['message_3'] = 'If you want, I can quickly help you choose between the Start package, demo, or product path.';
        $payload['defaults']['contact_email'] = $mentor_email;
        $payload['defaults']['checkout_url'] = $contact_url;
        $payload['defaults']['whatsapp_url'] = $contact_url;
        $payload['defaults']['calendar_url'] = $contact_url;
        $payload['defaults']['product_shop_url'] = $product_shop_url;
        $payload['defaults']['hide_public_navbar'] = true;

        if(isset($payload['landing_page']) && is_array($payload['landing_page'])) {
            $payload['landing_page']['name'] = 'FCC VIP Funnel - personalized landing';
            $payload['landing_page']['variant_b_settings']['name'] = 'FCC VIP Funnel - alternative landing';
        }

        vip_funnel_update_template_block($payload, 'landing_hero', [
            'badge' => 'FCC VIP Funnel',
            'title' => 'Start an online business with FCC and guided mentorship',
            'text' => 'I am ' . $mentor_name . ', your FCC mentor. In a few short steps, choose your path: online business, system demo, or products and discount.',
        ]);
        vip_funnel_update_template_block($payload, 'landing_intro_video', [
            'title' => 'Short intro from your FCC mentor',
            'text' => 'Watch the short message first, then choose the path that best matches your situation.',
        ]);
        vip_funnel_update_template_block($payload, 'landing_proof', [
            'badge' => 'Why this is not just another link',
            'title' => 'FCC connects attention, clear selection, products, and mentorship into one guided system.',
            'text' => 'A new visitor does not need to read everything at once. The funnel guides them toward business, demo, or product path, while the mentor sees who is ready for a serious conversation.',
        ]);
        vip_funnel_update_template_block($payload, 'landing_b_hero', [
            'badge' => 'From interest to system',
            'title' => 'From first interest to your own online business',
            'text' => 'If FCC caught your attention, this short funnel shows the best next step without overwhelming you.',
        ]);
        vip_funnel_update_template_block($payload, 'business_hero', [
            'title' => 'If you want to start seriously, start with a system, team, and mentor.',
            'text' => 'Here, ' . $mentor_name . ' shows you what joining the FCC team means: how the work is done, what you get through the system, the first steps, and when the Start package makes sense. No wandering, no information overload - just a clear next step.',
        ]);
        vip_funnel_update_template_block($payload, 'business_video', [
            'title' => 'What joining the FCC team looks like',
            'text' => 'Watch this short message before deciding. Your mentor will show you what to do in the first week, how to use the system, and what it means to have support beside you.',
        ]);
        vip_funnel_update_template_block($payload, 'business_system', [
            'badge' => 'What you get',
            'title' => 'You are not starting from zero. You start with a clear FCC framework.',
            'text' => 'You get direction for your first posts, presentation, recommendations, conversations, and follow-up. Results depend on your activity, but you do not enter blindly - you know what to do on day one, week one, and after that.',
        ]);
        vip_funnel_update_template_block($payload, 'business_choice', [
            'title' => 'What do you need now to decide?',
            'text' => 'Choose honestly where you are right now. The funnel will send you to the fastest next step without unnecessary wandering.',
            'options' => [
                ['id' => 'business_serious', 'label' => 'I am ready for the Start package and joining the team', 'value' => 'ready_360_now', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'start_package_offer', 'external_url' => '', 'require_submit' => false],
                ['id' => 'business_check', 'label' => 'I want a short check before starting', 'value' => 'needs_check', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'qualification_form', 'external_url' => '', 'require_submit' => false],
                ['id' => 'business_demo', 'label' => 'First I want to see how the system works', 'value' => 'demo_interest', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'fcc_demo_preview', 'external_url' => '', 'require_submit' => false],
                ['id' => 'business_not_ready', 'label' => 'I am not ready for the Start package yet', 'value' => 'not_ready', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'not_ready_nurture', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_sync_template_step_blocks($payload, 'qualification_form', [
            'qualification_hero' => [
                'id' => 'qualification_hero',
                'type' => 'headline',
                'badge' => 'Short check before starting',
                'title' => 'Answer honestly and I will show you the best next step.',
                'text' => 'This is not a test or obligation. I need a few honest answers to see whether it makes sense for you to start with the Start package now, have a short conversation first, or see the system at a calmer pace.',
            ],
            'qualification_goal' => [
                'id' => 'qualification_goal',
                'type' => 'radio_survey',
                'title' => 'What do you want to achieve through FCC?',
                'text' => 'Choose what feels closest right now. It does not need to be your final decision.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'goal_extra_income', 'label' => 'Additional income alongside current responsibilities', 'hint' => 'I want to start gradually, but with a clear plan and support.', 'value' => 'dodatni_prihod', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'goal_serious_business', 'label' => 'A serious online business', 'hint' => 'I am ready to learn, work, and build my team long-term.', 'value' => 'ozbiljan_online_posao', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'goal_product_first', 'label' => 'Products and discount first, business later', 'hint' => 'I want to understand the products, use the benefits, and see whether recommending them makes sense.', 'value' => 'product_discount', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'goal_research', 'label' => 'I am just exploring', 'hint' => 'I want to understand the options without pressure and see if FCC is for me.', 'value' => 'samo_istrazujem', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_time' => [
                'id' => 'qualification_time',
                'type' => 'radio_survey',
                'title' => 'How much time can you realistically set aside weekly?',
                'text' => 'It is better to answer realistically than ideally. That way you get a pace that fits your situation.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'time_1_3', 'label' => '1-3 hours weekly', 'hint' => 'For a calm start, basic tasks, and gradual system understanding.', 'value' => 'time_1_3', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'time_4_7', 'label' => '4-7 hours weekly', 'hint' => 'Enough for a serious start, first contacts, and building a work habit.', 'value' => 'time_4_7', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'time_8_plus', 'label' => '8+ hours weekly', 'hint' => 'I am ready for a faster rhythm and a more active start.', 'value' => 'time_8_plus', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'time_no_capacity', 'label' => 'I cannot set aside time right now', 'hint' => 'It is better to start later than under pressure and without focus.', 'value' => 'time_no_capacity', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_investment' => [
                'id' => 'qualification_investment',
                'type' => 'radio_survey',
                'title' => 'Is the 360 € Start Your Journey package realistic for you this week if you decide FCC is for you?',
                'text' => 'This question is not for pressure. It helps avoid sending you to the wrong next step.',
                'required' => true,
                'route_on_submit' => true,
                'options' => [
                    ['id' => 'ready_now', 'label' => 'Yes, I can start now', 'hint' => 'If the direction is clear, I can order and start.', 'value' => 'ready_360_now', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'start_package_offer', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'ready_call', 'label' => 'Yes, but I want a short conversation', 'hint' => 'I need confirmation and a few answers before ordering.', 'value' => 'ready_360_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'mentor_call_request', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'need_demo', 'label' => 'First I want to see the system', 'hint' => 'I want to better understand how FCC works before deciding.', 'value' => 'trebam_prvo_vidjeti_sustav', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'fcc_demo_preview', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'not_now', 'label' => 'Not right now', 'hint' => 'This is not the right moment for the Start package.', 'value' => 'not_ready', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'not_ready_nurture', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_channel' => [
                'id' => 'qualification_channel',
                'type' => 'radio_survey',
                'title' => 'How would you like to be contacted?',
                'text' => 'For this check, email is not the main channel. WhatsApp or a short call is faster and clearer.',
                'required' => true,
                'route_on_submit' => false,
                'options' => [
                    ['id' => 'channel_whatsapp', 'label' => 'WhatsApp message', 'hint' => 'Fastest for first contact, agreement, and next instructions.', 'value' => 'channel_whatsapp', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'channel_phone', 'label' => 'Short phone call', 'hint' => 'Best if you want to quickly go through questions and decide.', 'value' => 'channel_phone', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                    ['id' => 'channel_whatsapp_call', 'label' => 'WhatsApp, then call', 'hint' => 'I send you a message first, then we agree on the best time to talk.', 'value' => 'channel_whatsapp_call', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => '', 'external_url' => '', 'require_submit' => false],
                ],
            ],
            'qualification_name' => ['id' => 'qualification_name', 'type' => 'full_name_field', 'title' => 'Full name', 'placeholder' => 'Enter your full name', 'required' => true, 'layout_width' => 'half'],
            'qualification_phone' => ['id' => 'qualification_phone', 'type' => 'phone_field', 'title' => 'WhatsApp / phone', 'placeholder' => 'Enter the number where you can be contacted quickly', 'required' => true, 'layout_width' => 'half'],
            'qualification_contact_time' => ['id' => 'qualification_contact_time', 'type' => 'text_field', 'title' => 'Best time to contact you', 'placeholder' => 'e.g. today after 5 pm, tomorrow morning, evening', 'field_key' => 'contact_time', 'required' => false, 'layout_width' => 'full'],
            'qualification_privacy' => [
                'id' => 'qualification_privacy',
                'type' => 'text',
                'text' => 'By submitting your answers, you confirm that ' . $mentor_name . ' or the FCC team may contact you about your selected FCC path via WhatsApp or phone call. Your details are used to process the request and communicate about FCC. Privacy: ' . SITE_URL . 'page/privacy-policy',
                'text_size' => 14,
            ],
            'qualification_submit' => [
                'id' => 'qualification_submit',
                'type' => 'cta_group',
                'text' => 'Send your answers and you will get the recommended next step. After submitting, you will see the recommended step, and if you selected contact, your mentor will reach out by WhatsApp or call.',
                'buttons' => [
                    ['id' => 'qualification_submit_btn', 'label' => 'Send the check and show me the next step', 'value' => 'qualification_submit', 'style' => 'primary', 'action' => 'submit_next', 'target_step_id' => '', 'external_url' => '', 'require_submit' => true],
                ],
                'alignment' => 'center',
            ],
        ], [
            'qualification_hero',
            'qualification_goal',
            'qualification_time',
            'qualification_investment',
            'qualification_channel',
            'qualification_name',
            'qualification_phone',
            'qualification_contact_time',
            'qualification_privacy',
            'qualification_submit',
        ], ['qualification_email']);
        vip_funnel_update_template_step($payload, 'qualification_form', [
            'title' => 'Short check before starting',
            'summary' => 'Qualification saves answers, WhatsApp/phone, and preferred contact time, then routes to the right next step.',
            'helper_text' => 'Qualification saves answers, WhatsApp/phone, and preferred contact time, then routes to the right next step.',
            'preview_headline' => 'Short check before starting',
            'preview_body' => 'Answer honestly and the system routes you toward the Start package, a conversation, system demo, or calmer intro.',
            'page' => ['name' => 'Short check before starting'],
        ]);
        vip_funnel_update_template_block($payload, 'demo_hero', [
            'badge' => 'FCC system',
            'title' => 'First understand how FCC works, then decide if this is for you.',
            'text' => 'In this step, your mentor shows how FCC guides a person from first interest to a clear next step: products, a short check, demo, or joining the team. If you decide to start, you can use the same framework in your own business with mentorship.',
            'title_size' => 50,
        ]);
        vip_funnel_update_template_block($payload, 'demo_video', [
            'title' => 'What the FCC system looks like in practice',
            'text' => 'Watch a short system overview: how a person is guided through the funnel, how products or the business path are selected, how contact works, and what happens after first interest.',
        ]);
        vip_funnel_update_template_block($payload, 'demo_actions', [
            'text' => 'After watching the explanation, choose what feels closest to you right now.',
            'buttons' => [
                ['id' => 'demo_qualify', 'label' => 'I want to check if FCC is for me', 'value' => 'business_interest', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'qualification_form', 'external_url' => '', 'require_submit' => false],
                ['id' => 'demo_start', 'label' => 'I am ready for the Start package and joining the team', 'value' => 'ready_360_now', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'start_package_offer', 'external_url' => '', 'require_submit' => false],
                ['id' => 'demo_request', 'label' => 'I want a more detailed system demo', 'value' => 'demo_request', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'demo_request', 'external_url' => '', 'require_submit' => false],
                ['id' => 'demo_products', 'label' => 'I am only interested in products and discounts', 'value' => 'product_discount', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'product_gateway', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_sync_template_step_blocks($payload, 'demo_request', vip_funnel_get_demo_request_step_blocks('en', $mentor_name, SITE_URL . 'page/privacy-policy'), [
            'demo_request_hero',
            'demo_request_reason',
            'demo_request_name',
            'demo_request_phone',
            'demo_request_email',
            'demo_request_focus',
            'demo_request_privacy',
            'demo_request_submit',
        ]);
        vip_funnel_update_template_step($payload, 'demo_request', [
            'title' => 'Request controlled FCC system demo access',
            'summary' => 'Controlled demo request with reason, WhatsApp/phone, and email only for demo access.',
            'helper_text' => 'Controlled demo request with reason, WhatsApp/phone, and email only for demo access.',
            'preview_headline' => 'Request controlled FCC system demo access',
            'preview_body' => 'The demo is not a public link. The visitor leaves a reason, contact, and what they want to see.',
            'page' => ['name' => 'Request controlled FCC system demo access'],
        ]);
        vip_funnel_update_template_block($payload, 'product_gateway_hero', [
            'badge' => 'Products and discount',
            'title' => 'Start with the product you actually need, and the business path can come later.',
            'text' => 'If Forever products are your main interest right now, choose the goal that feels closest. You will get a simple recommendation, a discount option, and a clear next step without pressure.',
            'title_size' => 50,
        ]);
        vip_funnel_update_template_block($payload, 'product_goal', [
            'title' => 'What do you want to improve first?',
            'text' => 'Choose the closest goal and the funnel will guide you toward the recommendation that makes the most sense for your situation.',
            'options' => [
                ['id' => 'product_energy', 'label' => 'More energy and daily vitality', 'value' => 'vise_energije', 'style' => 'primary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_weight', 'label' => 'Weight management and better shape', 'value' => 'regulacija_tezine', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_skin', 'label' => 'Skin care and personal routine', 'value' => 'njega_koze', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_routine', 'label' => 'Daily routine for me or my family', 'value' => 'dnevna_rutina', 'style' => 'secondary', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
                ['id' => 'product_discount', 'label' => 'I want a recommendation and discount', 'value' => 'popust', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'product_recommendation', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_update_template_block($payload, 'start_hero', [
            'badge' => 'Start Your Journey | 360 €',
            'title' => 'If you want to join the FCC team, the Start package is your first concrete step.',
            'text' => 'Through Start Your Journey you get products, access to the system, initial education, and mentorship for your first steps. After ordering, you are not left alone - your mentor guides setup, understanding the offer, and your first tasks.',
            'title_size' => 50,
        ]);
        vip_funnel_update_template_block($payload, 'start_video', [
            'title' => 'Before ordering, see exactly what you get',
            'text' => 'In this short video, your mentor shows what the Start package includes, what the first week looks like, and what happens after you send the order.',
        ]);
        vip_funnel_update_template_block($payload, 'start_included', [
            'badge' => 'You get',
            'title' => 'Products, the FCC system, first tasks, and mentorship to start.',
            'text' => 'You are not buying a promise of easy income. You get products, a clear starting framework, education, and support. Results depend on your activity, learning, communication, and consistency.',
        ]);
        vip_funnel_update_template_block($payload, 'start_first_week', [
            'badge' => 'First 7 days',
            'title' => 'Setup, first tasks, understanding the offer, and first contacts.',
            'text' => 'The goal is that after ordering you are not left alone, but you immediately know your first step, how to use the system, and how to start making recommendations.',
        ]);
        vip_funnel_update_template_block($payload, 'start_countdown', [
            'title' => 'Next Zoom onboarding for new collaborators',
            'text' => 'Onboarding happens every Thursday at 20:00 via Zoom. After registration, your mentor will contact you and send the Zoom link, preparation notes, and everything you need for the first webinar and your start.',
            'countdown_mode' => 'weekly',
            'countdown_weekly_day' => 4,
            'countdown_weekly_time' => '20:00',
            'countdown_timezone' => 'Europe/Zagreb',
            'duration_days' => 0,
            'duration_minutes' => 0,
        ]);
        vip_funnel_update_template_block($payload, 'start_actions', [
            'text' => 'Choose how you want to make the next step. After clicking, you will get order instructions and connect with your mentor before onboarding.',
            'buttons' => [
                ['id' => 'start_order', 'label' => 'I want to order the Start Your Journey package', 'hint' => 'Redirects to the official Forever Living order and signup page with your mentor referral and automatic country routing.', 'value' => 'order_start_package', 'style' => 'primary', 'action' => 'external_url', 'target_step_id' => '', 'external_url' => vip_funnel_get_forever_business_referral_action_token(), 'require_submit' => false],
                ['id' => 'start_whatsapp', 'label' => 'I have a question before ordering', 'value' => 'start_whatsapp', 'style' => 'secondary', 'action' => 'external_url', 'target_step_id' => '', 'external_url' => vip_funnel_get_start_package_question_whatsapp_url((int) ($payload['defaults']['owner_user_id'] ?? 0), 'en') ?: $contact_url, 'require_submit' => false],
                ['id' => 'start_call', 'label' => 'I am not sure yet - I want a short conversation', 'value' => 'ready_360_call', 'style' => 'ghost', 'action' => 'goto_step', 'target_step_id' => 'mentor_call_request', 'external_url' => '', 'require_submit' => false],
            ],
        ]);
        vip_funnel_sync_template_step_blocks($payload, 'mentor_call_request', vip_funnel_get_call_request_step_blocks('en', $mentor_name, SITE_URL . 'page/privacy-policy', vip_funnel_resolve_fast_contact_url($contact_url)), [
            'call_hero',
            'call_reason',
            'call_channel',
            'call_name',
            'call_phone',
            'call_contact_time',
            'call_privacy',
            'call_actions',
        ], ['call_email']);

        vip_funnel_update_template_step($payload, 'business_gateway', [
            'title' => 'Business filter for a serious FCC start',
            'summary' => 'Guides the visitor toward the Start package, a short check, the system demo, or a calmer introduction.',
            'helper_text' => 'Guides the visitor toward the Start package, a short check, the system demo, or a calmer introduction.',
            'preview_headline' => 'Business filter for a serious FCC start',
            'preview_body' => 'Guides the visitor toward the Start package, a short check, the system demo, or a calmer introduction.',
            'page' => ['name' => 'Business filter for a serious FCC start'],
        ]);
        vip_funnel_update_template_step($payload, 'fcc_demo_preview', [
            'title' => 'Understand the FCC system before deciding',
            'summary' => 'Calmly explains how FCC guides interest toward products, a short check, demo, or joining the team.',
            'helper_text' => 'Calmly explains how FCC guides interest toward products, a short check, demo, or joining the team.',
            'preview_headline' => 'Understand the FCC system before deciding',
            'preview_body' => 'Calmly explains how FCC guides interest toward products, a short check, demo, or joining the team.',
            'page' => ['name' => 'Understand the FCC system before deciding'],
        ]);
        vip_funnel_update_template_step($payload, 'product_gateway', [
            'title' => 'Product path with a clear recommendation',
            'summary' => 'Guides the visitor toward a product goal, discount, and later business bridge without pressure.',
            'helper_text' => 'Guides the visitor toward a product goal, discount, and later business bridge without pressure.',
            'preview_headline' => 'Product path with a clear recommendation',
            'preview_body' => 'Guides the visitor toward a product goal, discount, and later business bridge without pressure.',
            'page' => ['name' => 'Product path with a clear recommendation'],
        ]);
        vip_funnel_update_template_step($payload, 'start_package_offer', [
            'title' => 'Start Your Journey: join the FCC team and take the first concrete step',
            'summary' => 'Conversion page for the Start package, joining the team, and onboarding.',
            'helper_text' => 'Conversion page for the Start package, joining the team, and onboarding.',
            'preview_headline' => 'Start Your Journey: join the FCC team and take the first concrete step',
            'preview_body' => 'Conversion page for the Start package, joining the team, and onboarding.',
            'page' => ['name' => 'Start Your Journey: join the FCC team and take the first concrete step'],
        ]);
        vip_funnel_update_template_step($payload, 'mentor_call_request', [
            'title' => 'Request a short conversation before deciding',
            'summary' => 'Calms uncertainty and guides the visitor toward WhatsApp or a call without pressure.',
            'helper_text' => 'Calms uncertainty and guides the visitor toward WhatsApp or a call without pressure.',
            'preview_headline' => 'Request a short conversation before deciding',
            'preview_body' => 'Calms uncertainty and guides the visitor toward WhatsApp or a call without pressure.',
            'page' => ['name' => 'Request a short conversation before deciding'],
        ]);

        vip_funnel_set_complete_landing_variant_b($payload, [
            'hero' => [
                'badge' => 'From interest to system',
                'title' => 'From first interest to your own online business',
                'text' => 'If FCC caught your attention, this short funnel shows the best next step without overwhelming you.',
            ],
            'intro_video' => [
                'title' => 'Short intro from your FCC mentor',
                'text' => 'Watch the short message first, then choose the path that best matches your situation.',
            ],
            'proof' => [
                'badge' => 'Why this is not just another link',
                'title' => 'FCC connects attention, clear selection, products, and mentorship into one guided system.',
                'text' => 'A new visitor does not need to read everything at once. The funnel guides them toward business, demo, or product path, while the mentor sees who is ready for a serious conversation.',
            ],
            'direction' => [
                'title' => 'Choose your fastest path',
                'text' => 'Business, demo, or products. The system guides you without overload.',
            ],
        ]);
    };

    $apply_hr_copy($payload);

    if($language === 'en') {
        $payload = vip_funnel_localize_template_payload($payload, 'en');
        $apply_en_copy($payload);
    }

    $payload = vip_funnel_normalize_studio_payload($payload, $user);

    if($language === 'en') {
        $apply_en_copy($payload);
    } else {
        $apply_hr_copy($payload);
    }

    return $payload;
}

function vip_funnel_translate_template_strings($value, array $translations) {
    if(is_array($value)) {
        foreach($value as $key => $item) {
            $value[$key] = vip_funnel_translate_template_strings($item, $translations);
        }

        return $value;
    }

    if(!is_string($value) || $value === '') {
        return $value;
    }

    if(isset($translations[$value])) {
        return $translations[$value];
    }

    if(str_starts_with($value, 'Slanjem podataka potvrđuješ')) {
        $privacy_url = trim((string) (explode('Privacy:', $value, 2)[1] ?? ''));
        return 'By submitting your details, you confirm that your mentor or the FCC team may contact you about the path you selected.' . ($privacy_url !== '' ? ' Privacy: ' . $privacy_url : '');
    }

    if(str_starts_with($value, 'Slanjem odgovora potvrđuješ')) {
        $privacy_url = trim((string) (explode('Privacy:', $value, 2)[1] ?? ''));
        return 'By submitting your answers, you confirm that your mentor or the FCC team may contact you about your selected FCC path via WhatsApp or phone call. Your details are used to process the request and communicate about FCC.' . ($privacy_url !== '' ? ' Privacy: ' . $privacy_url : '');
    }

    if(str_starts_with($value, 'Slanjem zahtjeva potvrđuješ')) {
        $privacy_url = trim((string) (explode('Privacy:', $value, 2)[1] ?? ''));
        return 'By submitting the request, you confirm that your mentor or the FCC team may contact you about your selected FCC path via WhatsApp or phone call. We use your details only to process the request and communicate about FCC.' . ($privacy_url !== '' ? ' Privacy: ' . $privacy_url : '');
    }

    return $value;
}

function vip_funnel_localize_template_payload(array $payload, string $language): array {
    if(vip_funnel_resolve_import_template_language($language) !== 'en') {
        return $payload;
    }

    $translations = [
        'Nastavi' => 'Continue',
        'Stjepan osobni recruiting landing' => 'Personal FCC recruiting landing',
        'Pokreni online posao uz FCC sustav i moje mentorstvo' => 'Start an online business with FCC and my mentorship',
        'Ja sam Stjepan Beloša, kreator FCC-a i mentor tima od 7.000+ članova. Ako si došao s mojih videa, ovdje u par koraka biraš svoj put: posao, demo sustava ili proizvodi i popust.' => 'I am your FCC mentor. If you came from my videos, this funnel helps you choose your next step: business, system demo, or products and discount.',
        'Kratki uvod za one koji dolaze s mojih videa' => 'Short intro for people coming from my videos',
        'Pogledaj prvo poruku, a zatim odaberi smjer koji je najbliži tvojoj situaciji.' => 'Watch the message first, then choose the path that matches your situation.',
        'Zašto ovo nije običan link' => 'Why this is not just another link',
        'FCC spaja video pažnju, jasnu selekciju, proizvode i mentorstvo u jedan vođeni sustav.' => 'FCC connects video attention, clear selection, products, and mentorship into one guided system.',
        'Novi posjetitelj ne mora čitati sve odjednom. Funnel ga vodi prema poslu, demo iskustvu ili proizvodnom putu, a meni pokazuje tko je spreman za ozbiljan razgovor.' => 'A new visitor does not need to read everything at once. The funnel guides them toward the business, demo experience, or product path, and shows the mentor who is ready for a serious conversation.',
        'Što te sada najviše zanima?' => 'What are you most interested in now?',
        'Odaberi iskreno. Svaki smjer vodi na drugu stranicu koja je složena kao mala landing stranica.' => 'Choose honestly. Every path opens a focused page built like a small landing page.',
        'Želim pokrenuti online posao' => 'I want to start an online business',
        'Želim vidjeti FCC sustav' => 'I want to see the FCC system',
        'Želim popust ili proizvode' => 'I want products or a discount',
        'Već sam spreman za start paket' => 'I am ready for the start package',
        'Od videa do sustava' => 'From video to system',
        'Od milionskih pregleda do vlastitog online posla' => 'From viral views to your own online business',
        'Ako ti se sviđa moj sadržaj i zanima te kako to možeš pretvoriti u vlastiti online posao, kreni kroz kratki FCC Funnel 2.0 pregled.' => 'If you like my content and want to see how it can become your own online business, start with this short FCC Funnel 2.0 overview.',
        'Odaberi svoj najbrži put' => 'Choose your fastest path',
        'Posao, demo ili proizvodi. Sustav te vodi bez viška informacija.' => 'Business, demo, or products. The system guides you without overload.',
        'Online posao' => 'Online business',
        'Proizvodi / popust' => 'Products / discount',
        'Od pregleda videa do online posla, ali bez kretanja od nule' => 'From video views to an online business, without starting from zero',
        'Objašnjava zašto je FCC vođeni poslovni sustav, a ne samo još jedna informacija.' => 'Explains why FCC is a guided business system, not just more information.',
        'Poslovni filter za ozbiljan FCC start' => 'Business filter for a serious FCC start',
        'Vodi prema start paketu, kratkoj provjeri, demo prikazu sustava ili mirnijem uvodu.' => 'Guides the visitor toward the Start package, a short check, the system demo, or a calmer introduction.',
        'Poslovni put' => 'Business path',
        'Ne trebaš krenuti sam. Trebaš jasan sustav i mentora.' => 'You do not need to start alone. You need a clear system and a mentor.',
        'FCC ti daje okvir: što pokazati, kako objasniti, kako voditi ljude i kako graditi naviku rada. Ja te vodim kroz prve korake, a ti učiš raditi konkretno i dosljedno.' => 'FCC gives you a framework: what to show, how to explain it, how to guide people, and how to build consistent work habits. Your mentor guides the first steps while you learn to work clearly and consistently.',
        'Ako želiš krenuti ozbiljno, kreni uz sustav, tim i mentora.' => 'If you want to start seriously, start with a system, team, and mentor.',
        'Ovdje ti pokazujem što znači ulazak u moj FCC tim: kako se radi, što dobivaš kroz sustav, koji su prvi koraci i kada ima smisla uzeti start paket. Bez lutanja, bez previše informacija odjednom - samo jasan sljedeći korak.' => 'Here I show you what joining my FCC team means: how the work is done, what you get through the system, the first steps, and when the Start package makes sense. No wandering, no information overload - just a clear next step.',
        'Tko sam ja i zašto sam napravio FCC' => 'Who I am and why FCC was created',
        'Kratko objašnjenje sustava, tima i načina rada.' => 'A short explanation of the system, team, and way of working.',
        'Kako izgleda ulazak u moj FCC tim' => 'What joining my FCC team looks like',
        'Pogledaj kratku poruku prije odluke. Pokazat ću ti što radiš u prvom tjednu, kako koristiš sustav i što znači imati mentora uz sebe.' => 'Watch this short message before deciding. I will show you what to do in the first week, how to use the system, and what it means to have a mentor beside you.',
        'Sustav' => 'System',
        'Ne krećeš s praznim profilom i praznom idejom.' => 'You are not starting with an empty profile and an empty idea.',
        'Krećeš kroz gotov FCC okvir koji pomaže u prezentaciji, preporuci, kontaktima i follow-upu.' => 'You start with a ready FCC framework for presentation, recommendations, contacts, and follow-up.',
        'Što dobivaš' => 'What you get',
        'Ne krećeš od nule. Krećeš kroz jasan FCC okvir.' => 'You are not starting from zero. You start with a clear FCC framework.',
        'Dobivaš smjer za prve objave, prezentaciju, preporuke, razgovore i follow-up. Rezultat ovisi o tvojoj aktivnosti, ali ne ulaziš naslijepo - znaš što radiš prvi dan, prvi tjedan i nakon toga.' => 'You get direction for your first posts, presentation, recommendations, conversations, and follow-up. Results depend on your activity, but you do not enter blindly - you know what to do on day one, week one, and after that.',
        'Koji opis ti je najbliži?' => 'Which description fits you best?',
        'Ovo određuje hoće li te funnel voditi na provjeru, demo ili mirniji uvod.' => 'This determines whether the funnel sends you to qualification, demo, or a calmer introduction.',
        'Što ti sada treba za odluku?' => 'What do you need now to decide?',
        'Odaberi iskreno gdje si trenutno. Tako će te funnel odvesti na najbrži sljedeći korak bez nepotrebnog lutanja.' => 'Choose honestly where you are right now. The funnel will send you to the fastest next step without unnecessary wandering.',
        'Želim krenuti ozbiljno ovaj tjedan' => 'I want to start seriously this week',
        'Zanima me, ali trebam prvo provjeru' => 'I am interested, but I need to check first',
        'Želim prvo vidjeti demo' => 'I want to see the demo first',
        'Nisam spreman za investiciju' => 'I am not ready for the investment',
        'Spreman/na sam za start paket i ulazak u tim' => 'I am ready for the Start package and joining the team',
        'Želim kratku provjeru prije starta' => 'I want a short check before starting',
        'Prvo želim vidjeti kako sustav radi' => 'First I want to see how the system works',
        'Nisam još spreman/na za start paket' => 'I am not ready for the Start package yet',
        'Provjeri koji je najbolji sljedeći korak za tebe' => 'Check the best next step for you',
        'Kvalifikacija sprema kontakt i odgovore te vodi na pravi nastavak.' => 'Qualification saves your contact and answers, then routes you to the right next step.',
        'Kratka provjera prije starta' => 'Short check before starting',
        'Kvalifikacija sprema odgovore, WhatsApp/telefon i najbolji termin kontakta te vodi na pravi nastavak.' => 'Qualification saves answers, WhatsApp/phone, and preferred contact time, then routes you to the right next step.',
        'Kratka provjera' => 'Short check',
        'Odgovori iskreno. Funnel će ti pokazati najbolji sljedeći korak.' => 'Answer honestly. The funnel will show your best next step.',
        'Ako si spreman za poslovni start, vidjet ćeš Start Your Journey put. Ako nisi, dobit ćeš demo ili mirniji uvod bez pritiska.' => 'If you are ready for a business start, you will see the Start Your Journey path. If not, you will get a demo or a calmer introduction without pressure.',
        'Odgovori iskreno i pokazat ću ti najbolji sljedeći korak.' => 'Answer honestly and I will show you the best next step.',
        'Ovo nije test ni obveza. Treba mi nekoliko iskrenih odgovora da vidim ima li smisla da kreneš sa start paketom sada, da prvo prođemo kratak razgovor ili da pogledaš sustav mirnijim tempom.' => 'This is not a test or obligation. I need a few honest answers to see whether it makes sense for you to start with the Start package now, have a short conversation first, or see the system at a calmer pace.',
        'Što želiš izgraditi?' => 'What do you want to build?',
        'Što želiš postići kroz FCC?' => 'What do you want to achieve through FCC?',
        'Odaberi ono što ti je trenutno najbliže. Ne mora biti konačna odluka.' => 'Choose what feels closest right now. It does not need to be your final decision.',
        'Dodatni prihod' => 'Additional income',
        'Dodatni prihod uz postojeće obaveze' => 'Additional income alongside current responsibilities',
        'Želim krenuti postupno, ali uz jasan plan i podršku.' => 'I want to start gradually, but with a clear plan and support.',
        'Ozbiljan online posao' => 'A serious online business',
        'Spreman/na sam učiti, raditi i dugoročnije graditi svoj tim.' => 'I am ready to learn, work, and build my team long-term.',
        'Proizvodni popust pa kasnije možda posao' => 'Product discount first, maybe business later',
        'Prvo proizvodi i popust, posao kasnije' => 'Products and discount first, business later',
        'Želim upoznati proizvode, koristiti pogodnosti i vidjeti ima li smisla preporučivati.' => 'I want to understand the products, use the benefits, and see whether recommending them makes sense.',
        'Samo istražujem' => 'I am just exploring',
        'Želim razumjeti opcije bez pritiska i vidjeti je li FCC za mene.' => 'I want to understand the options without pressure and see if FCC is for me.',
        'Koliko vremena realno možeš odvojiti tjedno?' => 'How much time can you realistically invest weekly?',
        'Bolje je odgovoriti realno nego idealno. Tako ćeš dobiti tempo koji ima smisla za tvoju situaciju.' => 'It is better to answer realistically than ideally. That way you get a pace that fits your situation.',
        '1-3 sata' => '1-3 hours',
        '1-3 sata tjedno' => '1-3 hours weekly',
        'Za miran početak, osnovne zadatke i postupno upoznavanje sustava.' => 'For a calm start, basic tasks, and gradual system understanding.',
        '4-7 sati' => '4-7 hours',
        '4-7 sati tjedno' => '4-7 hours weekly',
        'Dovoljno za ozbiljan start, prve kontakte i stvaranje radne navike.' => 'Enough for a serious start, first contacts, and building a work habit.',
        '8+ sati' => '8+ hours',
        '8+ sati tjedno' => '8+ hours weekly',
        'Spreman/na sam za brži ritam i aktivniji početak.' => 'I am ready for a faster rhythm and a more active start.',
        'Trenutno ne mogu odvojiti vrijeme' => 'I cannot set aside time right now',
        'Bolje je krenuti kasnije nego pod pritiskom i bez fokusa.' => 'It is better to start later than under pressure and without focus.',
        'Jesi li spreman investirati 360 EUR u Start Your Journey ako vidiš da je ovo za tebe?' => 'Are you ready to invest 360 EUR in Start Your Journey if this is right for you?',
        'Ovo pitanje određuje tvoj sljedeći korak.' => 'This question determines your next step.',
        'Je li ti Start Your Journey paket od 360 € realan ovaj tjedan ako zaključiš da je FCC za tebe?' => 'Is the 360 € Start Your Journey package realistic for you this week if you decide FCC is for you?',
        'Ovo pitanje ne služi za pritisak, nego da te ne vodim na pogrešan sljedeći korak.' => 'This question is not for pressure. It helps avoid sending you to the wrong next step.',
        'Da, mogu odmah' => 'Yes, I can start now',
        'Ako mi je smjer jasan, mogu napraviti narudžbu i krenuti.' => 'If the direction is clear, I can order and start.',
        'Da, ali želim kratak razgovor' => 'Yes, but I want a short conversation',
        'Trebam potvrdu i par odgovora prije narudžbe.' => 'I need confirmation and a few answers before ordering.',
        'Trebam prvo vidjeti sustav' => 'I need to see the system first',
        'Prvo želim vidjeti sustav' => 'First I want to see the system',
        'Želim bolje razumjeti kako FCC radi prije odluke.' => 'I want to better understand how FCC works before deciding.',
        'Ne sada' => 'Not right now',
        'Trenutno nije pravi trenutak za start paket.' => 'This is not the right moment for the Start package.',
        'Kako želiš da te kontaktiram?' => 'How do you want to be contacted?',
        'Za ovu provjeru ne koristim email kao glavni kanal. Najbrže i najjasnije je preko WhatsAppa ili kratkog poziva.' => 'For this check, email is not the main channel. WhatsApp or a short call is faster and clearer.',
        'WhatsApp poruka' => 'WhatsApp message',
        'Najbrže za prvi kontakt, dogovor i slanje sljedećih uputa.' => 'Fastest for first contact, agreement, and next instructions.',
        'Telefon' => 'Phone',
        'Kratki telefonski poziv' => 'Short phone call',
        'Najbolje ako želiš brzo proći pitanja i donijeti odluku.' => 'Best if you want to quickly go through questions and decide.',
        'WhatsApp pa poziv' => 'WhatsApp, then call',
        'Prvo ti pošaljem poruku, pa dogovorimo najbolji termin za razgovor.' => 'I send you a message first, then we agree on the best time to talk.',
        'Ime i prezime' => 'Full name',
        'Upiši ime i prezime' => 'Enter your full name',
        'Upiši email' => 'Enter your email',
        'Telefon / WhatsApp' => 'Phone / WhatsApp',
        'Upiši broj za brzi kontakt' => 'Enter a number for fast contact',
        'WhatsApp / telefon' => 'WhatsApp / phone',
        'Upiši broj na koji te mogu brzo kontaktirati' => 'Enter the number where you can be contacted quickly',
        'Najbolje vrijeme za kontakt' => 'Best time to contact you',
        'npr. danas poslije 17h, sutra ujutro, navečer' => 'e.g. today after 5 pm, tomorrow morning, evening',
        'Prikaži moj sljedeći korak' => 'Show my next step',
        'Pošalji odgovore i dobit ćeš preporučeni sljedeći korak. Nakon slanja vidjet ćeš preporučeni korak, a ako si odabrao/la kontakt, javit ću ti se preko WhatsAppa ili poziva.' => 'Send your answers and you will get the recommended next step. After submitting, you will see the recommended step, and if you selected contact, I will reach out by WhatsApp or call.',
        'Pošalji provjeru i pokaži mi sljedeći korak' => 'Send the check and show me the next step',
        'Tvoj najbolji sljedeći korak je Start Your Journey' => 'Your best next step is Start Your Journey',
        'Za osobe koje su pokazale jasnu spremnost za poslovni start.' => 'For people who showed clear readiness for a business start.',
        'HOT kandidat' => 'HOT candidate',
        'Po tvojim odgovorima ima smisla da vidiš konkretan start.' => 'Based on your answers, it makes sense to see the concrete start.',
        'Start Your Journey paket je 360 EUR i ulaz je u proizvode, sustav i vođeni početak. Ako kreneš, ne ostaješ sam: dobit ćeš jasne prve korake i mentorstvo.' => 'The Start Your Journey package is 360 EUR and gives you products, the system, and a guided start. If you start, you are not left alone: you get clear first steps and mentorship.',
        'Što dobivaš za 360 EUR' => 'What you get for 360 EUR',
        'Kratko objašnjenje paketa, sustava, prvih 7 dana i očekivanja.' => 'A short explanation of the package, system, first 7 days, and expectations.',
        'Naruči Start Your Journey paket' => 'Order the Start Your Journey package',
        'Želim prvo kratak razgovor' => 'I want a short conversation first',
        'Želim vidjeti FCC demo' => 'I want to see the FCC demo',
        'Start Your Journey: tvoj prvi poslovni korak uz FCC i mentorstvo' => 'Start Your Journey: your first business step with FCC and mentorship',
        'Prodajna stranica za start paket od 360 EUR.' => 'Sales page for the 360 EUR start package.',
        'Start Your Journey: ulazak u FCC tim i prvi konkretan korak' => 'Start Your Journey: join the FCC team and take the first concrete step',
        'Konverzijska stranica za start paket, ulazak u tim i onboarding.' => 'Conversion page for the Start package, joining the team, and onboarding.',
        'Start paket 360 EUR' => 'Start package 360 EUR',
        'Start Your Journey | 360 €' => 'Start Your Journey | 360 €',
        'Ovo je trenutak kada interes postaje konkretan prvi korak.' => 'This is the moment when interest becomes a concrete first step.',
        'Start Your Journey nije kupnja informacije. To je ulaz u proizvode, FCC sustav, edukaciju i mentorstvo za prve poslovne korake.' => 'Start Your Journey is not buying information. It is an entry into products, the FCC system, education, and mentorship for the first business steps.',
        'Ako želiš ući u FCC tim, start paket je tvoj prvi konkretan korak.' => 'If you want to join the FCC team, the Start package is your first concrete step.',
        'Kroz Start Your Journey dobivaš proizvode, ulazak u sustav, početnu edukaciju i moje mentorstvo za prve korake. Nakon narudžbe ne ostaješ sam/a - vodim te kroz postavljanje, razumijevanje ponude i prve zadatke.' => 'Through Start Your Journey you get products, access to the system, initial education, and my mentorship for your first steps. After ordering, you are not left alone - I guide setup, understanding the offer, and your first tasks.',
        'Pogledaj što je uključeno' => 'See what is included',
        'Prije narudžbe pogledaj što točno dobivaš' => 'Before ordering, see exactly what you get',
        'U kratkom videu pokazujem što uključuje start paket, kako izgleda prvi tjedan i što se događa nakon što pošalješ narudžbu.' => 'In this short video, I show what the Start package includes, what the first week looks like, and what happens after you send the order.',
        'Dobivaš' => 'You get',
        'Proizvode, jasne prve zadatke, FCC alate i mentorstvo.' => 'Products, clear first tasks, FCC tools, and mentorship.',
        'Nema obećanja lake zarade. Dobivaš sustav i vodstvo, a rezultat ovisi o tvojoj aktivnosti, učenju i dosljednosti.' => 'There are no promises of easy income. You get a system and guidance; results depend on your activity, learning, and consistency.',
        'Proizvode, FCC sustav, prve zadatke i mentorstvo za start.' => 'Products, the FCC system, first tasks, and mentorship to start.',
        'Ne kupuješ obećanje lake zarade. Dobivaš proizvode, jasan početni okvir, edukaciju i podršku. Rezultat ovisi o tvojoj aktivnosti, učenju, komunikaciji i dosljednosti.' => 'You are not buying a promise of easy income. You get products, a clear starting framework, education, and support. Results depend on your activity, learning, communication, and consistency.',
        'Prvih 7 dana' => 'First 7 days',
        'Postavljanje, razumijevanje ponude, prvi kontakti i prvi follow-up.' => 'Setup, understanding the offer, first contacts, and first follow-up.',
        'Cilj je da ne ostaneš sam nakon narudžbe nego da odmah znaš što radiš sljedeće.' => 'The goal is that you are not left alone after ordering, but know immediately what to do next.',
        'Postavljanje, prvi zadaci, razumijevanje ponude i prvi kontakti.' => 'Setup, first tasks, understanding the offer, and first contacts.',
        'Cilj je da nakon narudžbe ne ostaneš sam/a, nego da odmah znaš koji je tvoj prvi korak, kako koristiti sustav i kako krenuti s preporukama.' => 'The goal is that after ordering you are not left alone, but you immediately know your first step, how to use the system, and how to start making recommendations.',
        'Prioritetni onboarding prozor' => 'Priority onboarding window',
        'Ako želiš ući u prvi onboarding krug, pošalji narudžbu ili upit sada.' => 'If you want to enter the first onboarding group, send your order or question now.',
        'Ulazak u sljedeći onboarding krug' => 'Entry into the next onboarding group',
        'Ako želiš krenuti s nama u sljedećem onboarding krugu, pošalji narudžbu ili mi se javi prije isteka ovog prozora.' => 'If you want to start with us in the next onboarding group, send the order or message me before this window ends.',
        'Sljedeći Zoom onboarding za nove suradnike' => 'Next Zoom onboarding for new collaborators',
        'Onboarding se održava svaki četvrtak u 20:00 putem Zooma. Nakon registracije osobno ću te kontaktirati i poslati Zoom link, pripremu i sve što ti treba za prvi webinar i početak.' => 'Onboarding happens every Thursday at 20:00 via Zoom. After registration, I will personally contact you and send the Zoom link, preparation notes, and everything you need for the first webinar and your start.',
        'Onboarding se održava svaki četvrtak u 20:00 putem Zooma. Nakon registracije mentor će te kontaktirati i poslati Zoom link, pripremu i sve što ti treba za prvi webinar i početak.' => 'Onboarding happens every Thursday at 20:00 via Zoom. After registration, your mentor will contact you and send the Zoom link, preparation notes, and everything you need for the first webinar and your start.',
        'Odaberi kako želiš napraviti sljedeći korak. Nakon klika dobit ćeš upute za narudžbu i povezivanje sa mnom prije onboardinga.' => 'Choose how you want to make the next step. After clicking, you will get order instructions and connect with me before onboarding.',
        'Pošalji mi poruku prije narudžbe' => 'Message me before ordering',
        'Nisam siguran, želim razgovor' => 'I am not sure, I want to talk',
        'Želim naručiti Start Your Journey paket' => 'I want to order the Start Your Journey package',
        'Imam pitanje prije narudžbe' => 'I have a question before ordering',
        'Nisam još siguran/na - želim kratki razgovor' => 'I am not sure yet - I want a short conversation',
        'Zatraži kratki pregled sa mnom ili mojim timom' => 'Request a short review with me or my team',
        'Kontakt stranica za osobu koja želi potvrdu prije odluke.' => 'Contact page for someone who wants confirmation before deciding.',
        'Razgovor' => 'Conversation',
        'Ako želiš ljudsku potvrdu prije odluke, ovdje ostavi najbolji kontakt.' => 'If you want human confirmation before deciding, leave your best contact here.',
        'Cilj razgovora nije pritisak nego jasnoća: je li za tebe Start paket, demo ili proizvodni put.' => 'The goal of the conversation is clarity, not pressure: start package, demo, or product path.',
        'Što želiš razjasniti?' => 'What do you want to clarify?',
        'Je li ovo za mene' => 'Is this for me?',
        'Kako izgleda 360 EUR start' => 'How the 360 EUR start works',
        'Kako FCC pomaže u prodaji' => 'How FCC helps with sales',
        'Kako krenuti ako nemam iskustva' => 'How to start without experience',
        'Upiši broj' => 'Enter your number',
        'Pošalji zahtjev za razgovor' => 'Send conversation request',
        'Otvori termin ili poruku' => 'Open a time slot or message',
        'Zatraži kratak razgovor prije odluke' => 'Request a short conversation before deciding',
        'Smiruje nesigurnost i vodi osobu prema WhatsAppu ili pozivu bez pritiska.' => 'Calms uncertainty and guides the visitor toward WhatsApp or a call without pressure.',
        'Kratki razgovor' => 'Short conversation',
        'Nisi još siguran/na? Kratak razgovor će ti dati jasniji sljedeći korak.' => 'Not sure yet? A short conversation will give you a clearer next step.',
        'Cilj razgovora nije da te nagovaram, nego da u par minuta razjasnimo ima li za tebe više smisla start paket, dodatni demo, proizvodi ili mirniji uvod. Ako FCC nije za tebe sada, i to je u redu.' => 'The goal of the conversation is not to persuade you, but to clarify in a few minutes whether the Start package, an additional demo, products, or a calmer intro makes more sense for you. If FCC is not for you right now, that is also okay.',
        'Što najviše želiš razjasniti prije odluke?' => 'What do you most want to clarify before deciding?',
        'Odaberi temu koja ti je trenutno najvažnija. Tako ću znati odakle krenuti u razgovoru.' => 'Choose the topic that matters most right now. That way I know where to start in the conversation.',
        'Želim znati je li FCC stvarno za mene' => 'I want to know if FCC is really for me',
        'Nisam siguran/na uklapa li se ovo u moju situaciju, vrijeme i ciljeve.' => 'I am not sure whether this fits my situation, time, and goals.',
        'Želim razumjeti što dobivam za 360 €' => 'I want to understand what I get for 360 €',
        'Želim jasno znati što uključuje Start Your Journey paket i što se događa nakon narudžbe.' => 'I want to clearly know what the Start Your Journey package includes and what happens after ordering.',
        'Želim razumjeti kako FCC pomaže u preporukama i prodaji' => 'I want to understand how FCC helps with recommendations and sales',
        'Zanima me kako sustav vodi osobu od interesa do proizvoda, razgovora ili start paketa.' => 'I want to see how the system guides a person from interest to products, conversation, or the Start package.',
        'Želim znati mogu li krenuti bez iskustva' => 'I want to know if I can start without experience',
        'Nemam iskustva u online poslu, prodaji ili preporukama i želim znati kako izgledaju prvi koraci.' => 'I do not have experience in online business, sales, or recommendations and want to know what the first steps look like.',
        'Želim samo potvrditi sljedeći korak' => 'I only want to confirm the next step',
        'Već sam zainteresiran/a, ali želim kratku potvrdu prije nego donesem odluku.' => 'I am already interested, but I want a short confirmation before deciding.',
        'Za kratku provjeru koristim WhatsApp ili poziv jer je tako najbrže i najjasnije.' => 'For a short check, I use WhatsApp or a call because it is the fastest and clearest.',
        'Najbrže za prvi kontakt i dogovor.' => 'Fastest for first contact and agreement.',
        'Najbolje ako želiš odmah razjasniti pitanja.' => 'Best if you want to clarify questions immediately.',
        'Prvo ti pošaljem poruku, pa dogovorimo termin.' => 'I send you a message first, then we agree on a time.',
        'Pošalji zahtjev i javit ću ti se preko WhatsAppa ili poziva.' => 'Send the request and I will contact you by WhatsApp or call.',
        'Pošalji zahtjev za kratak razgovor' => 'Send request for a short conversation',
        'Pošalji mi WhatsApp poruku odmah' => 'Send me a WhatsApp message now',
        'Rezerviraj termin za kratak poziv' => 'Book a short call time',
        'Pogledaj kako FCC pretvara interes u jasne korake' => 'See how FCC turns interest into clear steps',
        'Demo stranica za osobe koje trebaju doživjeti sustav prije odluke.' => 'Demo page for people who need to experience the system before deciding.',
        'Razumij FCC sustav prije odluke' => 'Understand the FCC system before deciding',
        'Mirno objašnjava kako FCC vodi od interesa do proizvoda, provjere, demo prikaza ili ulaska u tim.' => 'Calmly explains how FCC guides interest toward products, a short check, demo, or joining the team.',
        'FCC sustav' => 'FCC system',
        'Ovdje osoba vidi sustav, ne samo priču.' => 'Here the visitor sees the system, not just the story.',
        'Pogledaj kako FCC vodi posjetitelja od interesa do kontakta, proizvoda ili suradnje. Ovo je i pokazni primjer što ćeš moći koristiti u svom poslu.' => 'See how FCC guides a visitor from interest to contact, products, or collaboration. This is also a demo of what you can use in your own business.',
        'Prvo razumij kako FCC funkcionira, pa odluči je li ovo za tebe.' => 'First understand how FCC works, then decide if this is for you.',
        'U ovom koraku pokazujem ti kako FCC vodi osobu od prvog interesa do jasnog sljedećeg koraka: proizvodi, provjera, demo ili ulazak u tim. Ako odlučiš krenuti, isti okvir možeš koristiti i u svom poslu uz moje mentorstvo.' => 'In this step, I show you how FCC guides a person from first interest to a clear next step: products, a short check, demo, or joining the team. If you decide to start, you can use the same framework in your own business with my mentorship.',
        'FCC demo iznutra' => 'FCC demo from the inside',
        'Pokaži aplikaciju, funnel, product preporuku, kontakte i follow-up.' => 'Show the app, funnel, product recommendation, contacts, and follow-up.',
        'Kako izgleda FCC sustav u praksi' => 'What the FCC system looks like in practice',
        'Pogledaj kratki prikaz sustava: kako se osoba vodi kroz funnel, kako se biraju proizvodi ili poslovni put, kako izgleda kontakt i što se događa nakon prvog interesa.' => 'Watch a short system overview: how a person is guided through the funnel, how products or the business path are selected, how contact works, and what happens after first interest.',
        'Nakon što pogledaš objašnjenje, odaberi što ti je sada najbliže.' => 'After watching the explanation, choose what feels closest to you right now.',
        'Želim provjeriti mogu li krenuti' => 'I want to check if I can start',
        'Želim Start Your Journey' => 'I want Start Your Journey',
        'Želim demo pristup' => 'I want demo access',
        'Želim samo proizvode' => 'I only want products',
        'Želim provjeriti je li FCC za mene' => 'I want to check if FCC is for me',
        'Želim detaljniji demo sustava' => 'I want a more detailed system demo',
        'Zanimaju me samo proizvodi i popusti' => 'I am only interested in products and discounts',
        'Zatraži demo pregled FCC sustava' => 'Request an FCC system demo review',
        'Lead capture za demo interes.' => 'Lead capture for demo interest.',
        'Demo zahtjev' => 'Demo request',
        'Ako želiš prvo vidjeti sustav, ostavi podatke i razlog.' => 'If you want to see the system first, leave your details and reason.',
        'Demo ostaje kontroliran i premium. Cilj je pokazati ti pravi dio sustava za tvoju situaciju.' => 'The demo stays controlled and premium. The goal is to show the right part of the system for your situation.',
        'Zašto želiš demo?' => 'Why do you want the demo?',
        'Želim vidjeti sustav prije odluke' => 'I want to see the system before deciding',
        'Želim pokazati sustav drugima' => 'I want to show the system to others',
        'Već imam tim ili prodaju' => 'I already have a team or sales',
        'Zatraži demo pregled' => 'Request demo review',
        'Ako sada želiš samo proizvode ili popust, kreni ovim putem' => 'If you only want products or a discount now, start here',
        'Produktni ulaz koji monetizira osobe koje ne žele odmah posao.' => 'Product entry path for people who do not want the business immediately.',
        'Proizvodni put s jasnom preporukom' => 'Product path with a clear recommendation',
        'Vodi osobu prema proizvodnom cilju, popustu i kasnijem poslovnom mostu bez pritiska.' => 'Guides the visitor toward a product goal, discount, and later business bridge without pressure.',
        'Proizvodni put' => 'Product path',
        'Proizvodi i popust' => 'Products and discount',
        'Ne mora svatko odmah u posao. Odaberi cilj i dobit ćeš jasniji proizvodni korak.' => 'Not everyone needs to start the business immediately. Choose your goal and you will get a clearer product step.',
        'Ako kasnije poželiš graditi posao, isti FCC sustav možeš koristiti za preporuke, kontakte i vlastiti tim.' => 'If you later want to build a business, the same FCC system can help with recommendations, contacts, and your own team.',
        'Kreni od proizvoda koji ti stvarno treba, a poslovni put može doći kasnije.' => 'Start with the product you actually need, and the business path can come later.',
        'Ako te sada najviše zanimaju Forever proizvodi, odaberi cilj koji ti je najbliži. Dobit ćeš jednostavnu preporuku, mogućnost popusta i jasan sljedeći korak bez pritiska.' => 'If Forever products are your main interest right now, choose the goal that feels closest. You will get a simple recommendation, a discount option, and a clear next step without pressure.',
        'Što ti je sada glavni cilj?' => 'What is your main goal right now?',
        'Što želiš prvo poboljšati?' => 'What do you want to improve first?',
        'Odaberi najbliži cilj i funnel će te voditi prema preporuci koja ima najviše smisla za tvoju situaciju.' => 'Choose the closest goal and the funnel will guide you toward the recommendation that makes the most sense for your situation.',
        'Više energije' => 'More energy',
        'Regulacija težine' => 'Weight management',
        'Njega kože' => 'Skin care',
        'Opća dnevna rutina' => 'General daily routine',
        'Želim popust' => 'I want a discount',
        'Više energije i dnevna vitalnost' => 'More energy and daily vitality',
        'Regulacija težine i bolja forma' => 'Weight management and better shape',
        'Njega kože i osobna rutina' => 'Skin care and personal routine',
        'Opća dnevna rutina za mene ili obitelj' => 'Daily routine for me or my family',
        'Želim preporuku i popust' => 'I want a recommendation and discount',
        'Ovo je najbolji prvi proizvodni korak za tvoj cilj' => 'This is the best first product step for your goal',
        'Dinamična proizvodna preporuka s mostom prema poslovnom putu.' => 'Dynamic product recommendation with a bridge toward the business path.',
        'Preporuka' => 'Recommendation',
        'Preporuka prati odgovor koji si odabrao/la. Ako želiš samo kupnju, idi na proizvodni vodič ili shop. Ako želiš naučiti preporučivati online, otvori poslovni most.' => 'The recommendation follows the answer you selected. If you only want to buy, open the product guide or shop. If you want to learn online recommendations, open the business bridge.',
        'Dinamička preporuka' => 'Dynamic recommendation',
        'Preporuka prema tvom odgovoru' => 'Recommendation based on your answer',
        'Funnel koristi tvoj odabir i povezuje te s najbližim proizvodnim vodičem ili shop korakom.' => 'The funnel uses your selection and connects you with the closest product guide or shop step.',
        'Pogledaj vodič proizvoda' => 'View product guide',
        'Idi na službeni shop' => 'Go to the official shop',
        'Proizvodni vodič' => 'Product guide',
        'Povezi ovaj korak s proizvodnim katalogom kada dodaš shop linkove.' => 'Connect this step with the product catalog when you add shop links.',
        'Ako katalog još nije spreman, ovaj fallback vodi na opći proizvodni shop ili vodič.' => 'If the catalog is not ready yet, this fallback leads to a general product shop or guide.',
        'Otvori proizvodni shop / vodič' => 'Open product shop / guide',
        'Želim naučiti preporučivati proizvode online' => 'I want to learn online product recommendations',
        'Ako ti se sviđa proizvodni put, možeš ga pretvoriti u online preporuke' => 'If you like the product path, you can turn it into online recommendations',
        'Most iz product/discount interesa u business funnel.' => 'Bridge from product/discount interest into the business funnel.',
        'Most prema poslu' => 'Bridge to business',
        'Mnogi krenu kroz proizvode, a kasnije shvate da isti sustav mogu koristiti za preporuke.' => 'Many people start with products and later realize they can use the same system for recommendations.',
        'Ako te zanima kako od proizvoda doći do online preporuka, kontakata i vlastitog tima, otvori poslovni put.' => 'If you want to see how products can lead to online recommendations, contacts, and your own team, open the business path.',
        'Kako proizvodni interes postaje poslovni sustav' => 'How product interest becomes a business system',
        'Želim poslovni put' => 'I want the business path',
        'Ostajem na proizvodima' => 'I will stay with products',
        'Nema pritiska. Poslat ću ti miran uvod i možeš se vratiti kad budeš spreman.' => 'No pressure. You will receive a calm introduction and can come back when you are ready.',
        'Nurture stranica za osobe koje nisu odmah spremne.' => 'Nurture page for people who are not ready immediately.',
        'Miran nastavak' => 'Calm next step',
        'Nije cilj da svi kupe odmah. Cilj je da dobiješ pravi sljedeći korak.' => 'The goal is not for everyone to buy immediately. The goal is to give you the right next step.',
        'Ako sada nije trenutak za 360 EUR start ili razgovor, pogledaj uvod i vrati se kad želiš demo, proizvodni put ili poslovni start.' => 'If now is not the moment for a 360 EUR start or conversation, watch the intro and come back when you want the demo, product path, or business start.',
        'Kratki uvod bez pritiska' => 'Short intro without pressure',
        'Email za uvodni video' => 'Email for the intro video',
        'Upiši email ako ga još nisi ostavio/la' => 'Enter your email if you have not left it yet',
        'Opcionalno za brzi kontakt' => 'Optional for fast contact',
        'Pošalji mi uvodni video' => 'Send me the intro video',
        'Vrati me na demo' => 'Take me back to the demo',
        'Želim proizvode' => 'I want products',
        'Stjepan Beloša - FCC Recruiting Funnel' => 'FCC Recruiting Funnel',
        'Pokreni online posao uz FCC sustav i mentorstvo Stjepana Beloše' => 'Start an online business with FCC and mentorship',
        'Jedan vođeni funnel za regrutaciju, demo sustava, prodaju proizvoda i follow-up.' => 'One guided funnel for recruiting, system demo, product sales, and follow-up.',
        'Provjeri svoj put' => 'Check your path',
        'Pogledaj FCC demo' => 'See the FCC demo',
        'Za osobe koje dolaze s društvenih mreža i žele posao, demo FCC sustava ili proizvodni popust.' => 'For people coming from social media who want a business, FCC system demo, or product discount.',
        'Viralna pažnja se lako izgubi ako posjetitelj nema jasan sljedeći korak.' => 'Viral attention is easily lost if the visitor does not have a clear next step.',
        'Funnel segmentira posjetitelja, kvalificira spremnost i vodi ga prema Start paketu, razgovoru, demo iskustvu ili proizvodnoj preporuci.' => 'The funnel segments the visitor, qualifies readiness, and guides them toward the start package, conversation, demo experience, or product recommendation.',
        'Jasan put od interesa do odluke uz Stjepanovo mentorstvo i FCC sustav.' => 'A clear path from interest to decision with mentorship and the FCC system.',
        'Publika već postoji; sada pažnju treba pretvoriti u mjerljiv i ponovljiv sustav.' => 'The audience already exists; now attention needs to become a measurable and repeatable system.',
        'Proizvodi i popust' => 'Products and discount',
        'FCC demo i nurture' => 'FCC demo and nurture',
        'Put za osobe koje žele pokrenuti online posao uz FCC i mentorstvo.' => 'Path for people who want to start an online business with FCC and mentorship.',
        'Put za osobe koje sada žele proizvode, preporuku ili popust.' => 'Path for people who want products, a recommendation, or a discount now.',
        'Put za osobe koje prvo trebaju vidjeti sustav ili mirniji uvod.' => 'Path for people who first need to see the system or a calmer introduction.',
        'Produktni put koristi survey selection i product_offer dynamic mapping gdje katalog ima povezane shop linkove.' => 'The product path uses survey selection and product_offer dynamic mapping where the catalog has connected shop links.',
        'Dinamična proizvodna preporuka' => 'Dynamic product recommendation',
        'Preporuka prati cilj posjetitelja i vodi na blog vodič ili službeni shop.' => 'The recommendation follows the visitor goal and leads to a blog guide or official shop.',
        'Most prema poslovnom putu' => 'Bridge toward the business path',
        'Kupac koji pokaže interes može prijeći na FCC poslovni put.' => 'A customer who shows interest can move into the FCC business path.',
        'Otvori proizvodni put' => 'Open product path',
        'Stjepan je kreator FCC-a i mentor tima od 7.000+ članova.' => 'Your mentor is connected to the FCC system and duplication model.',
        'FCC daje jasan sustav za prezentaciju, preporuke i follow-up.' => 'FCC gives a clear system for presentation, recommendations, and follow-up.',
        'Funnel selektira hot, warm, product i demo leadove.' => 'The funnel selects hot, warm, product, and demo leads.',
        'Svaka stranica je mala landing stranica s jasnom odlukom.' => 'Every page is a small landing page with a clear decision.',
    ];

    return vip_funnel_translate_template_strings($payload, $translations);
}

function vip_funnel_get_import_template_payload(string $template_key = '', $user = null, string $language = 'hr'): ?array {
    $template_key = trim($template_key);
    $language = vip_funnel_resolve_import_template_language($language);

    switch($template_key) {
        case 'fcc_vip_complete':
        case 'fcc_recruiting_mentor':
        case 'mentor_recruiting':
            return vip_funnel_get_fcc_vip_import_template_payload($user, $language);

        default:
            return null;
    }
}

function vip_funnel_studio_create_funnel_from_payload($user = null, array $payload = []) {
    if(!$user || !vip_funnel_studio_schema_is_ready()) {
        return null;
    }

    $user_id = (int) ($user->user_id ?? 0);

    if($user_id <= 0) {
        return null;
    }

    $payload = vip_funnel_normalize_studio_payload($payload, $user);
    $payload['funnel']['slug'] = vip_funnel_get_unique_slug_for_user($user_id, (string) ($payload['funnel']['slug'] ?? $payload['funnel']['name'] ?? 'vip-funnel-2-0'));

    $funnel_id = (int) db()->insert('vip_funnels', [
        'user_id' => $user_id,
        'name' => $payload['funnel']['name'],
        'slug' => $payload['funnel']['slug'],
        'status' => $payload['funnel']['status'],
        'visibility_mode' => $payload['funnel']['visibility_mode'],
        'owner_mode' => $payload['funnel']['owner_mode'],
        'settings' => vip_funnel_json_encode([
            'overview' => $payload['overview'],
            'positioning' => $payload['positioning'],
            'landing_page' => $payload['landing_page'],
            'products' => $payload['products'],
            'proof' => $payload['proof'],
            'follow_up' => $payload['follow_up'],
            'demo' => $payload['demo'],
            'analytics' => $payload['analytics'],
            'defaults' => $payload['defaults'],
        ]),
        'datetime' => get_date(),
        'last_datetime' => get_date(),
    ]);

    if($funnel_id <= 0) {
        return null;
    }

    if(!vip_funnel_studio_save_to_database($user, $payload, $funnel_id)) {
        db()->where('vip_funnel_id', $funnel_id)->where('user_id', $user_id)->delete('vip_funnels');
        return null;
    }

    return vip_funnel_studio_get_funnel_row($user_id, $funnel_id);
}

function vip_funnel_studio_delete_funnel($user = null, int $funnel_id = 0): bool {
    if(!$user || $funnel_id <= 0 || !vip_funnel_studio_schema_is_ready()) {
        return false;
    }

    $user_id = (int) ($user->user_id ?? 0);

    if($user_id <= 0 || !vip_funnel_studio_get_funnel_row($user_id, $funnel_id)) {
        return false;
    }

    db()->startTransaction();

    try {
        if(vip_funnel_has_table('vip_leads')) {
            $lead_detached = db()->where('vip_funnel_id', $funnel_id)->where('owner_user_id', $user_id)->update('vip_leads', [
                'vip_funnel_id' => null,
            ]);

            if($lead_detached === false || !empty(database()->error)) {
                throw new \Exception('vip_funnel_lead_detach_failed');
            }
        }

        foreach(['vip_funnel_events', 'vip_funnel_runs', 'vip_funnel_edges', 'vip_funnel_cards', 'vip_funnel_paths'] as $table) {
            if(!vip_funnel_has_table($table)) {
                continue;
            }

            $deleted = db()->where('vip_funnel_id', $funnel_id)->delete($table);

            if($deleted === false || !empty(database()->error)) {
                throw new \Exception('vip_funnel_related_delete_failed_' . $table);
            }
        }

        $deleted_funnel = db()->where('vip_funnel_id', $funnel_id)->where('user_id', $user_id)->delete('vip_funnels');

        if($deleted_funnel === false || !empty(database()->error)) {
            throw new \Exception('vip_funnel_root_delete_failed');
        }

        db()->commit();

        cache()->deleteItemsByTag('user_id=' . $user_id);
        cache()->deleteItem('user?user_id=' . $user_id);

        return true;
    } catch(\Throwable $exception) {
        db()->rollback();

        error_log(vip_funnel_json_encode([
            'channel' => 'vip_funnel_delete_failed',
            'user_id' => $user_id,
            'funnel_id' => $funnel_id,
            'message' => $exception->getMessage(),
            'database_error' => database()->error ?? '',
        ]));

        return false;
    }
}

function vip_funnel_studio_load_from_database($user = null, int $funnel_id = 0): array {
    $seed_payload = vip_funnel_get_studio_seed_payload($user);

    if(!$user || !vip_funnel_studio_schema_is_ready()) {
        return $seed_payload;
    }

    $user_id = (int) ($user->user_id ?? 0);
    $funnel = $funnel_id > 0
        ? vip_funnel_studio_get_funnel_row($user_id, $funnel_id)
        : vip_funnel_studio_ensure_primary_funnel($user);

    if(!$funnel) {
        return $seed_payload;
    }

    $settings = vip_funnel_to_array($funnel->settings ?? []);
    $paths_result = db()->where('vip_funnel_id', (int) $funnel->vip_funnel_id)->orderBy('sort_order', 'ASC')->get('vip_funnel_paths');
    $cards_result = db()->where('vip_funnel_id', (int) $funnel->vip_funnel_id)->orderBy('phase_key', 'ASC')->orderBy('sort_order', 'ASC')->get('vip_funnel_cards');
    $edges_result = db()->where('vip_funnel_id', (int) $funnel->vip_funnel_id)->get('vip_funnel_edges');

    $paths = [];
    $path_map = [];
    foreach((array) $paths_result as $path_row) {
        $path_key = (string) ($path_row->path_key ?? '');
        $paths[] = [
            'path_key' => $path_key,
            'title' => (string) ($path_row->title ?? ''),
            'description' => (string) ($path_row->description ?? ''),
            'sort_order' => (int) ($path_row->sort_order ?? 1),
            'is_enabled' => (bool) ($path_row->is_enabled ?? true),
        ];
        $path_map[(int) $path_row->vip_funnel_path_id] = $path_key;
    }

    $edge_target_map = [];
    $edge_rows = [];
    foreach((array) $edges_result as $edge_row) {
        $edge_rows[(int) $edge_row->from_card_id] = (int) $edge_row->to_card_id;
    }

    $step_id_by_card = [];
    foreach((array) $cards_result as $card_row) {
        $card_settings = vip_funnel_to_array($card_row->settings ?? []);
        $step_id_by_card[(int) $card_row->vip_funnel_card_id] = (string) ($card_settings['step_id'] ?? '');
    }

    foreach($edge_rows as $from_card_id => $to_card_id) {
        if(!empty($step_id_by_card[$to_card_id])) {
            $edge_target_map[$from_card_id] = $step_id_by_card[$to_card_id];
        }
    }

    $phase_map = [];
    foreach((array) $cards_result as $card_row) {
        $phase_key = (string) ($card_row->phase_key ?? '');
        if($phase_key === '') {
            continue;
        }

        $card_settings = vip_funnel_to_array($card_row->settings ?? []);
        $step_payload = [
            'id' => (string) ($card_settings['step_id'] ?? ('card_' . $card_row->vip_funnel_card_id)),
            'path_key' => $path_map[(int) ($card_row->vip_funnel_path_id ?? 0)] ?? 'business',
            'row_key' => (string) ($card_row->row_key ?? ''),
            'card_type' => (string) ($card_row->card_type ?? 'offer'),
            'title' => (string) ($card_row->title ?? ''),
            'summary' => (string) ($card_settings['summary'] ?? ''),
            'helper_text' => (string) ($card_settings['helper_text'] ?? ''),
            'cta' => (string) ($card_settings['cta'] ?? ''),
            'next' => (string) ($card_settings['next'] ?? ''),
            'next_step_id' => (string) ($card_settings['next_step_id'] ?? ($edge_target_map[(int) $card_row->vip_funnel_card_id] ?? '')),
            'status_key' => (string) ($card_settings['status_key'] ?? 'core'),
            'media_url' => (string) ($card_settings['media_url'] ?? ''),
            'answers' => vip_funnel_normalize_list_items($card_settings['answers'] ?? []),
            'tags' => vip_funnel_normalize_list_items($card_settings['tags'] ?? [], 8, 40),
            'owner_user_id' => (int) ($card_settings['owner_user_id'] ?? 0),
            'visibility_key' => (string) ($card_settings['visibility_key'] ?? 'all'),
            'analytics_label' => (string) ($card_settings['analytics_label'] ?? ''),
            'design_variant' => (string) ($card_settings['design_variant'] ?? 'card'),
            'preview_badge' => (string) ($card_settings['preview_badge'] ?? ''),
            'preview_headline' => (string) ($card_settings['preview_headline'] ?? ''),
            'preview_body' => (string) ($card_settings['preview_body'] ?? ''),
            'block_mode' => (string) ($card_settings['block_mode'] ?? 'message'),
            'background_color' => (string) ($card_settings['background_color'] ?? '#152132'),
            'text_color' => (string) ($card_settings['text_color'] ?? '#eef4ff'),
            'accent_color' => (string) ($card_settings['accent_color'] ?? '#67d8c9'),
            'button_options' => vip_funnel_normalize_button_options($card_settings['button_options'] ?? [], []),
            'page' => vip_funnel_normalize_page_surface_payload($card_settings['page'] ?? [], (string) ($card_row->title ?? 'Funnel stranica')),
        ];

        if(!isset($phase_map[$phase_key])) {
            $phase_map[$phase_key] = ['key' => $phase_key, 'steps' => []];
        }

        $phase_map[$phase_key]['steps'][] = $step_payload;
    }

    $payload = vip_funnel_normalize_studio_payload([
        'funnel' => [
            'name' => (string) ($funnel->name ?? $seed_payload['funnel']['name']),
            'slug' => (string) ($funnel->slug ?? $seed_payload['funnel']['slug']),
            'status' => (string) ($funnel->status ?? $seed_payload['funnel']['status']),
            'visibility_mode' => (string) ($funnel->visibility_mode ?? $seed_payload['funnel']['visibility_mode']),
            'owner_mode' => (string) ($funnel->owner_mode ?? $seed_payload['funnel']['owner_mode']),
        ],
        'overview' => $settings['overview'] ?? $seed_payload['overview'],
        'positioning' => $settings['positioning'] ?? $seed_payload['positioning'],
        'landing_page' => $settings['landing_page'] ?? $seed_payload['landing_page'],
        'paths' => !empty($paths) ? $paths : $seed_payload['paths'],
        'board' => array_values($phase_map),
        'products' => $settings['products'] ?? $seed_payload['products'],
        'proof' => $settings['proof'] ?? $seed_payload['proof'],
        'follow_up' => $settings['follow_up'] ?? $seed_payload['follow_up'],
        'demo' => $settings['demo'] ?? $seed_payload['demo'],
        'analytics' => $settings['analytics'] ?? $seed_payload['analytics'],
        'defaults' => $settings['defaults'] ?? $seed_payload['defaults'],
    ], $user);
    $payload['funnel_row'] = vip_funnel_studio_get_funnel_row_public_data($funnel);

    return $payload;
}

function vip_funnel_get_results_snapshot($user = null, int $funnel_id = 0): array {
    $snapshot = [
        'total_paths' => 0,
        'total_cards' => 0,
        'total_leads' => 0,
        'active_demos' => 0,
        'converted_demos' => 0,
        'recent_events' => 0,
    ];

    if($funnel_id > 0 && vip_funnel_studio_schema_is_ready()) {
        $paths_result = database()->query("SELECT COUNT(*) AS `total` FROM `vip_funnel_paths` WHERE `vip_funnel_id` = " . (int) $funnel_id);
        $cards_result = database()->query("SELECT COUNT(*) AS `total` FROM `vip_funnel_cards` WHERE `vip_funnel_id` = " . (int) $funnel_id);
        $snapshot['total_paths'] = (int) ($paths_result ? ($paths_result->fetch_object()->total ?? 0) : 0);
        $snapshot['total_cards'] = (int) ($cards_result ? ($cards_result->fetch_object()->total ?? 0) : 0);
    }

    if(vip_funnel_demo_schema_is_ready()) {
        $owner_user_ids = vip_funnel_get_pilot_owner_user_ids($user);
        $owner_sql = !empty($owner_user_ids) ? ' AND `owner_user_id` IN (' . implode(',', array_map('intval', $owner_user_ids)) . ')' : '';

        $leads_result = database()->query("SELECT COUNT(*) AS `total` FROM `vip_leads` WHERE 1 {$owner_sql}");
        $active_result = database()->query("SELECT COUNT(*) AS `total` FROM `vip_demo_accounts` WHERE `status` IN ('active', 'expiring', 'paused') {$owner_sql}");
        $converted_result = database()->query("SELECT COUNT(*) AS `total` FROM `vip_demo_accounts` WHERE `status` = 'converted' {$owner_sql}");
        $events_result = database()->query("SELECT COUNT(*) AS `total` FROM `vip_demo_events` WHERE `datetime` >= DATE_SUB(NOW(), INTERVAL 30 DAY)");

        $snapshot['total_leads'] = (int) ($leads_result ? ($leads_result->fetch_object()->total ?? 0) : 0);
        $snapshot['active_demos'] = (int) ($active_result ? ($active_result->fetch_object()->total ?? 0) : 0);
        $snapshot['converted_demos'] = (int) ($converted_result ? ($converted_result->fetch_object()->total ?? 0) : 0);
        $snapshot['recent_events'] = (int) ($events_result ? ($events_result->fetch_object()->total ?? 0) : 0);
    }

    return $snapshot;
}

function vip_funnel_ensure_runtime_schema(): void {
    static $done = false;

    if($done) {
        return;
    }

    $done = true;

    database()->query("
        CREATE TABLE IF NOT EXISTS `vip_funnels` (
            `vip_funnel_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int unsigned NOT NULL,
            `name` varchar(120) NOT NULL,
            `slug` varchar(160) NOT NULL,
            `status` varchar(32) NOT NULL DEFAULT 'draft',
            `visibility_mode` varchar(32) NOT NULL DEFAULT 'testing_locked',
            `owner_mode` varchar(32) NOT NULL DEFAULT 'shared',
            `settings` longtext NULL,
            `datetime` datetime NOT NULL,
            `last_datetime` datetime DEFAULT NULL,
            PRIMARY KEY (`vip_funnel_id`),
            KEY `user_id` (`user_id`),
            KEY `slug` (`slug`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    database()->query("
        CREATE TABLE IF NOT EXISTS `vip_funnel_paths` (
            `vip_funnel_path_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `vip_funnel_id` bigint unsigned NOT NULL,
            `path_key` varchar(64) NOT NULL,
            `title` varchar(120) NOT NULL,
            `description` varchar(240) NOT NULL DEFAULT '',
            `sort_order` int unsigned NOT NULL DEFAULT 1,
            `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`vip_funnel_path_id`),
            KEY `vip_funnel_id` (`vip_funnel_id`),
            KEY `path_key` (`path_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    database()->query("
        CREATE TABLE IF NOT EXISTS `vip_funnel_cards` (
            `vip_funnel_card_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `vip_funnel_id` bigint unsigned NOT NULL,
            `vip_funnel_path_id` bigint unsigned DEFAULT NULL,
            `phase_key` varchar(64) NOT NULL,
            `row_key` varchar(64) NOT NULL DEFAULT '',
            `card_type` varchar(64) NOT NULL DEFAULT 'offer',
            `title` varchar(120) NOT NULL,
            `settings` longtext NULL,
            `sort_order` int unsigned NOT NULL DEFAULT 1,
            `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`vip_funnel_card_id`),
            KEY `vip_funnel_id` (`vip_funnel_id`),
            KEY `vip_funnel_path_id` (`vip_funnel_path_id`),
            KEY `phase_key` (`phase_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    database()->query("
        CREATE TABLE IF NOT EXISTS `vip_funnel_edges` (
            `vip_funnel_edge_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `vip_funnel_id` bigint unsigned NOT NULL,
            `from_card_id` bigint unsigned NOT NULL,
            `to_card_id` bigint unsigned NOT NULL,
            `edge_type` varchar(64) NOT NULL DEFAULT 'default',
            `condition_key` varchar(64) NOT NULL DEFAULT '',
            `condition_value` varchar(160) NOT NULL DEFAULT '',
            PRIMARY KEY (`vip_funnel_edge_id`),
            KEY `vip_funnel_id` (`vip_funnel_id`),
            KEY `from_card_id` (`from_card_id`),
            KEY `to_card_id` (`to_card_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    database()->query("
        CREATE TABLE IF NOT EXISTS `vip_leads` (
            `vip_lead_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int unsigned NOT NULL DEFAULT 0,
            `owner_user_id` int unsigned NULL DEFAULT NULL,
            `vip_funnel_id` bigint unsigned NULL DEFAULT NULL,
            `source_step_key` varchar(128) NULL DEFAULT NULL,
            `selection_value` varchar(160) NULL DEFAULT NULL,
            `visitor_key` varchar(64) NULL DEFAULT NULL,
            `lead_name` varchar(160) NULL DEFAULT NULL,
            `full_name` varchar(160) NULL DEFAULT NULL,
            `lead_email` varchar(320) NULL DEFAULT NULL,
            `lead_phone` varchar(64) NULL DEFAULT NULL,
            `source` varchar(64) NOT NULL DEFAULT 'manual_pilot',
            `interest_type` varchar(64) NOT NULL DEFAULT 'demo',
            `business_readiness` varchar(64) NOT NULL DEFAULT '',
            `product_goal` varchar(120) NOT NULL DEFAULT '',
            `demo_status` varchar(32) NOT NULL DEFAULT 'requested',
            `payload` longtext NULL,
            `datetime` datetime NOT NULL,
            `last_datetime` datetime NULL DEFAULT NULL,
            PRIMARY KEY (`vip_lead_id`),
            KEY `idx_vip_leads_user_id` (`user_id`),
            KEY `idx_vip_leads_owner_user_id` (`owner_user_id`),
            KEY `idx_vip_leads_vip_funnel_id` (`vip_funnel_id`),
            KEY `idx_vip_leads_demo_status` (`demo_status`),
            KEY `idx_vip_leads_interest_type` (`interest_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    database()->query("
        CREATE TABLE IF NOT EXISTS `vip_demo_accounts` (
            `vip_demo_account_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `vip_lead_id` bigint unsigned NOT NULL,
            `demo_user_id` int unsigned NULL DEFAULT NULL,
            `owner_user_id` int unsigned NULL DEFAULT NULL,
            `status` varchar(32) NOT NULL DEFAULT 'requested',
            `expires_at` datetime NULL DEFAULT NULL,
            `approved_at` datetime NULL DEFAULT NULL,
            `approved_by_user_id` int unsigned NULL DEFAULT NULL,
            `settings` longtext NULL,
            `datetime` datetime NOT NULL,
            `last_datetime` datetime NULL DEFAULT NULL,
            PRIMARY KEY (`vip_demo_account_id`),
            KEY `idx_vip_demo_accounts_vip_lead_id` (`vip_lead_id`),
            KEY `idx_vip_demo_accounts_owner_user_id` (`owner_user_id`),
            KEY `idx_vip_demo_accounts_status` (`status`),
            KEY `idx_vip_demo_accounts_expires_at` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    database()->query("
        CREATE TABLE IF NOT EXISTS `vip_demo_events` (
            `vip_demo_event_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `vip_demo_account_id` bigint unsigned NOT NULL,
            `actor_user_id` int unsigned NULL DEFAULT NULL,
            `event_key` varchar(64) NOT NULL,
            `payload` longtext NULL,
            `datetime` datetime NOT NULL,
            PRIMARY KEY (`vip_demo_event_id`),
            KEY `idx_vip_demo_events_account_id` (`vip_demo_account_id`),
            KEY `idx_vip_demo_events_actor_user_id` (`actor_user_id`),
            KEY `idx_vip_demo_events_event_key` (`event_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    database()->query("
        CREATE TABLE IF NOT EXISTS `vip_funnel_runs` (
            `vip_funnel_run_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `vip_funnel_id` bigint unsigned NOT NULL,
            `vip_lead_id` bigint unsigned DEFAULT NULL,
            `source` varchar(64) NOT NULL DEFAULT 'manual',
            `current_card_id` bigint unsigned DEFAULT NULL,
            `current_step_key` varchar(128) DEFAULT NULL,
            `status` varchar(32) NOT NULL DEFAULT 'open',
            `visitor_key` varchar(64) DEFAULT NULL,
            `variant_key` varchar(8) DEFAULT NULL,
            `payload` longtext NULL,
            `datetime` datetime NOT NULL,
            `last_datetime` datetime DEFAULT NULL,
            PRIMARY KEY (`vip_funnel_run_id`),
            KEY `vip_funnel_id` (`vip_funnel_id`),
            KEY `vip_lead_id` (`vip_lead_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    if(function_exists('fc_add_table_column_if_missing')) {
        fc_add_table_column_if_missing('vip_funnel_runs', 'visitor_key', "`visitor_key` varchar(64) NULL AFTER `status`");
        fc_add_table_column_if_missing('vip_funnel_runs', 'variant_key', "`variant_key` varchar(8) NULL AFTER `visitor_key`");
        fc_add_table_column_if_missing('vip_funnel_runs', 'current_step_key', "`current_step_key` varchar(128) NULL AFTER `current_card_id`");
        fc_add_table_column_if_missing('vip_leads', 'visitor_key', "`visitor_key` varchar(64) NULL AFTER `owner_user_id`");
        fc_add_table_column_if_missing('vip_leads', 'vip_funnel_id', "`vip_funnel_id` bigint unsigned NULL AFTER `owner_user_id`");
        fc_add_table_column_if_missing('vip_leads', 'lead_name', "`lead_name` varchar(160) NULL AFTER `visitor_key`");
        fc_add_table_column_if_missing('vip_leads', 'full_name', "`full_name` varchar(160) NULL AFTER `lead_name`");
        fc_add_table_column_if_missing('vip_leads', 'lead_email', "`lead_email` varchar(320) NULL AFTER `full_name`");
        fc_add_table_column_if_missing('vip_leads', 'lead_phone', "`lead_phone` varchar(64) NULL AFTER `lead_email`");
        fc_add_table_column_if_missing('vip_leads', 'source_step_key', "`source_step_key` varchar(128) NULL AFTER `vip_funnel_id`");
        fc_add_table_column_if_missing('vip_leads', 'selection_value', "`selection_value` varchar(160) NULL AFTER `source_step_key`");
    }

    database()->query("
        CREATE TABLE IF NOT EXISTS `vip_funnel_events` (
            `vip_funnel_event_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `vip_funnel_id` bigint unsigned NOT NULL,
            `user_id` int unsigned NOT NULL,
            `visitor_key` varchar(64) NOT NULL,
            `vip_funnel_run_id` bigint unsigned DEFAULT NULL,
            `vip_lead_id` bigint unsigned DEFAULT NULL,
            `step_key` varchar(128) NOT NULL DEFAULT '',
            `page_role` varchar(32) NOT NULL DEFAULT 'step',
            `block_id` varchar(128) NOT NULL DEFAULT '',
            `variant_key` varchar(8) NOT NULL DEFAULT 'a',
            `event_type` varchar(32) NOT NULL DEFAULT 'view',
            `event_label` varchar(160) NOT NULL DEFAULT '',
            `meta` longtext NULL,
            `datetime` datetime NOT NULL,
            PRIMARY KEY (`vip_funnel_event_id`),
            KEY `vip_funnel_id` (`vip_funnel_id`),
            KEY `user_id` (`user_id`),
            KEY `visitor_key` (`visitor_key`),
            KEY `step_key` (`step_key`),
            KEY `event_type` (`event_type`),
            KEY `variant_key` (`variant_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function vip_funnel_get_analytics_step_registry(array $payload = []): array {
    $payload = vip_funnel_normalize_studio_payload($payload);
    $phase_definitions = vip_funnel_get_phase_definitions();
    $path_titles = [];

    foreach((array) ($payload['paths'] ?? []) as $path) {
        $path = vip_funnel_to_array($path);
        $path_key = (string) ($path['path_key'] ?? '');

        if($path_key === '') {
            continue;
        }

        $path_titles[$path_key] = (string) ($path['title'] ?? $path_key);
    }

    $registry = [
        'landing' => [
            'title' => trim((string) ($payload['landing_page']['name'] ?? ($payload['overview']['headline'] ?? ($payload['funnel']['name'] ?? l('vip_funnel.analytics.landing_title'))))),
            'phase_key' => 'landing',
            'phase_title' => l('vip_funnel.analytics.landing_phase'),
            'path_key' => '',
            'path_title' => '',
        ],
    ];

    foreach((array) ($payload['board'] ?? []) as $phase) {
        $phase = vip_funnel_to_array($phase);
        $phase_key = (string) ($phase['key'] ?? '');
        $phase_title = (string) ($phase_definitions[$phase_key]['title'] ?? ucfirst($phase_key));

        foreach((array) ($phase['steps'] ?? []) as $step) {
            $step = vip_funnel_to_array($step);
            $step_id = (string) ($step['id'] ?? '');

            if($step_id === '') {
                continue;
            }

            $path_key = (string) ($step['path_key'] ?? '');

            $registry[$step_id] = [
                'title' => trim((string) ($step['title'] ?? $step_id)),
                'phase_key' => $phase_key,
                'phase_title' => $phase_title,
                'path_key' => $path_key,
                'path_title' => (string) ($path_titles[$path_key] ?? ''),
            ];
        }
    }

    return $registry;
}

function vip_funnel_collect_selection_labels_from_surface(array $surface = [], array &$map = []): void {
    $surface = vip_funnel_normalize_page_surface_payload($surface);
    $block_groups = [
        (array) ($surface['blocks'] ?? []),
        (array) ($surface['variant_b_blocks'] ?? []),
    ];

    foreach($block_groups as $blocks) {
        foreach($blocks as $block) {
            $block = vip_funnel_to_array($block);
            $type = (string) ($block['type'] ?? '');

            if(!in_array($type, ['survey', 'radio_survey'], true)) {
                continue;
            }

            foreach((array) ($block['options'] ?? []) as $option) {
                $option = vip_funnel_to_array($option);
                $value = trim((string) ($option['value'] ?? ''));
                $label = trim((string) ($option['label'] ?? ''));

                if($value === '' || $label === '' || isset($map[$value])) {
                    continue;
                }

                $map[$value] = $label;
            }
        }
    }
}

function vip_funnel_get_analytics_selection_label_map(array $payload = []): array {
    $payload = vip_funnel_normalize_studio_payload($payload);
    $map = [];

    vip_funnel_collect_selection_labels_from_surface((array) ($payload['landing_page'] ?? []), $map);

    foreach((array) ($payload['board'] ?? []) as $phase) {
        $phase = vip_funnel_to_array($phase);

        foreach((array) ($phase['steps'] ?? []) as $step) {
            $step = vip_funnel_to_array($step);
            vip_funnel_collect_selection_labels_from_surface((array) ($step['page'] ?? []), $map);
        }
    }

    return $map;
}

function vip_funnel_get_public_event_type_labels(): array {
    return [
        'view' => l('vip_funnel.analytics.event.view'),
        'submit' => l('vip_funnel.analytics.event.submit'),
        'lead_capture' => l('vip_funnel.analytics.event.lead_capture'),
        'advance' => l('vip_funnel.analytics.event.advance'),
    ];
}

function vip_funnel_get_analytics_snapshot(int $funnel_id = 0, array $payload = []): array {
    $snapshot = [
        'views' => 0,
        'unique_visitors' => 0,
        'submits' => 0,
        'advances' => 0,
        'leads' => 0,
        'submit_rate' => 0,
        'lead_rate' => 0,
        'contacts_in_data' => 0,
        'best_step' => null,
        'best_selection' => null,
        'ab' => [
            'a_views' => 0,
            'b_views' => 0,
            'a_submits' => 0,
            'b_submits' => 0,
            'a_rate' => 0,
            'b_rate' => 0,
            'winner' => '',
        ],
        'steps' => [],
        'selections' => [],
        'recent_events' => [],
        'demo' => [
            'requests' => 0,
            'approved' => 0,
            'live' => 0,
            'converted' => 0,
            'archived' => 0,
            'activation_rate' => 0,
            'conversion_rate' => 0,
            'recent_events' => [],
        ],
    ];

    if($funnel_id <= 0) {
        return $snapshot;
    }

    vip_funnel_ensure_runtime_schema();

    if(!vip_funnel_has_table('vip_funnel_events')) {
        return $snapshot;
    }

    $step_registry = vip_funnel_get_analytics_step_registry($payload);
    $selection_labels = vip_funnel_get_analytics_selection_label_map($payload);

    $totals_result = database()->query("SELECT
            SUM(CASE WHEN `event_type` = 'view' THEN 1 ELSE 0 END) AS `views`,
            COUNT(DISTINCT CASE WHEN `event_type` = 'view' THEN `visitor_key` END) AS `unique_visitors`,
            SUM(CASE WHEN `event_type` = 'submit' THEN 1 ELSE 0 END) AS `submits`,
            SUM(CASE WHEN `event_type` = 'advance' THEN 1 ELSE 0 END) AS `advances`,
            SUM(CASE WHEN `event_type` = 'lead_capture' THEN 1 ELSE 0 END) AS `leads`,
            SUM(CASE WHEN `variant_key` = 'a' AND `event_type` = 'view' THEN 1 ELSE 0 END) AS `a_views`,
            SUM(CASE WHEN `variant_key` = 'b' AND `event_type` = 'view' THEN 1 ELSE 0 END) AS `b_views`,
            SUM(CASE WHEN `variant_key` = 'a' AND `event_type` = 'submit' THEN 1 ELSE 0 END) AS `a_submits`,
            SUM(CASE WHEN `variant_key` = 'b' AND `event_type` = 'submit' THEN 1 ELSE 0 END) AS `b_submits`
        FROM `vip_funnel_events`
        WHERE `vip_funnel_id` = " . (int) $funnel_id);
    $totals = $totals_result ? $totals_result->fetch_object() : null;

    if($totals) {
        $snapshot['views'] = (int) ($totals->views ?? 0);
        $snapshot['unique_visitors'] = (int) ($totals->unique_visitors ?? 0);
        $snapshot['submits'] = (int) ($totals->submits ?? 0);
        $snapshot['advances'] = (int) ($totals->advances ?? 0);
        $snapshot['leads'] = (int) ($totals->leads ?? 0);
        $snapshot['ab']['a_views'] = (int) ($totals->a_views ?? 0);
        $snapshot['ab']['b_views'] = (int) ($totals->b_views ?? 0);
        $snapshot['ab']['a_submits'] = (int) ($totals->a_submits ?? 0);
        $snapshot['ab']['b_submits'] = (int) ($totals->b_submits ?? 0);
    }

    $best_step_result = database()->query("SELECT `step_key`, COUNT(*) AS `total`
        FROM `vip_funnel_events`
        WHERE `vip_funnel_id` = " . (int) $funnel_id . " AND `event_type` = 'view'
        GROUP BY `step_key`
        ORDER BY `total` DESC
        LIMIT 1");
    $best_step = $best_step_result ? $best_step_result->fetch_object() : null;

    if($best_step) {
        $best_step_key = (string) ($best_step->step_key ?? '');
        $snapshot['best_step'] = [
            'step_key' => $best_step_key,
            'title' => (string) (($step_registry[$best_step_key]['title'] ?? $best_step_key) ?: $best_step_key),
            'views' => (int) ($best_step->total ?? 0),
        ];
    }

    $snapshot['submit_rate'] = $snapshot['views'] > 0 ? round(($snapshot['submits'] / max(1, $snapshot['views'])) * 100, 1) : 0;
    $snapshot['lead_rate'] = $snapshot['views'] > 0 ? round(($snapshot['leads'] / max(1, $snapshot['views'])) * 100, 1) : 0;

    $a_rate = $snapshot['ab']['a_views'] > 0 ? ($snapshot['ab']['a_submits'] / max(1, $snapshot['ab']['a_views'])) : 0;
    $b_rate = $snapshot['ab']['b_views'] > 0 ? ($snapshot['ab']['b_submits'] / max(1, $snapshot['ab']['b_views'])) : 0;
    $snapshot['ab']['a_rate'] = round($a_rate * 100, 1);
    $snapshot['ab']['b_rate'] = round($b_rate * 100, 1);
    if($snapshot['ab']['a_views'] || $snapshot['ab']['b_views']) {
        $snapshot['ab']['winner'] = $b_rate > $a_rate ? 'B' : 'A';
    }

    $step_result = database()->query("SELECT
            `step_key`,
            MAX(`page_role`) AS `page_role`,
            COUNT(DISTINCT CASE WHEN `event_type` = 'view' THEN `visitor_key` END) AS `visitors`,
            SUM(CASE WHEN `event_type` = 'view' THEN 1 ELSE 0 END) AS `views`,
            SUM(CASE WHEN `event_type` = 'submit' THEN 1 ELSE 0 END) AS `submits`,
            SUM(CASE WHEN `event_type` = 'lead_capture' THEN 1 ELSE 0 END) AS `leads`,
            SUM(CASE WHEN `event_type` = 'advance' THEN 1 ELSE 0 END) AS `advances`,
            SUM(CASE WHEN `variant_key` = 'a' AND `event_type` = 'view' THEN 1 ELSE 0 END) AS `a_views`,
            SUM(CASE WHEN `variant_key` = 'b' AND `event_type` = 'view' THEN 1 ELSE 0 END) AS `b_views`
        FROM `vip_funnel_events`
        WHERE `vip_funnel_id` = " . (int) $funnel_id . "
        GROUP BY `step_key`
        ORDER BY `views` DESC, `step_key` ASC");

    while($step_result && ($row = $step_result->fetch_object())) {
        $step_key = (string) ($row->step_key ?? '');
        $meta = $step_registry[$step_key] ?? [
            'title' => $step_key,
            'phase_key' => '',
            'phase_title' => '',
            'path_key' => '',
            'path_title' => '',
        ];

        $views = (int) ($row->views ?? 0);
        $submits = (int) ($row->submits ?? 0);
        $leads = (int) ($row->leads ?? 0);

        $snapshot['steps'][] = [
            'step_key' => $step_key,
            'title' => (string) ($meta['title'] ?? $step_key),
            'page_role' => (string) ($row->page_role ?? 'step'),
            'phase_key' => (string) ($meta['phase_key'] ?? ''),
            'phase_title' => (string) ($meta['phase_title'] ?? ''),
            'path_key' => (string) ($meta['path_key'] ?? ''),
            'path_title' => (string) ($meta['path_title'] ?? ''),
            'visitors' => (int) ($row->visitors ?? 0),
            'views' => $views,
            'submits' => $submits,
            'leads' => $leads,
            'advances' => (int) ($row->advances ?? 0),
            'submit_rate' => $views > 0 ? round(($submits / max(1, $views)) * 100, 1) : 0,
            'lead_rate' => $views > 0 ? round(($leads / max(1, $views)) * 100, 1) : 0,
            'a_views' => (int) ($row->a_views ?? 0),
            'b_views' => (int) ($row->b_views ?? 0),
        ];
    }

    $selection_result = database()->query("SELECT
            COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`meta`, '$.selection')), ''), NULLIF(`event_label`, ''), '') AS `selection_key`,
            SUM(CASE WHEN `event_type` = 'submit' THEN 1 ELSE 0 END) AS `submits`,
            SUM(CASE WHEN `event_type` = 'lead_capture' THEN 1 ELSE 0 END) AS `leads`,
            SUM(CASE WHEN `event_type` = 'advance' THEN 1 ELSE 0 END) AS `advances`
        FROM `vip_funnel_events`
        WHERE `vip_funnel_id` = " . (int) $funnel_id . "
        GROUP BY `selection_key`
        HAVING `selection_key` <> ''
        ORDER BY `submits` DESC, `leads` DESC, `selection_key` ASC");

    while($selection_result && ($row = $selection_result->fetch_object())) {
        $selection_key = trim((string) ($row->selection_key ?? ''));
        if($selection_key === '') {
            continue;
        }

        $selection_row = [
            'selection_key' => $selection_key,
            'label' => (string) ($selection_labels[$selection_key] ?? $selection_key),
            'submits' => (int) ($row->submits ?? 0),
            'leads' => (int) ($row->leads ?? 0),
            'advances' => (int) ($row->advances ?? 0),
        ];

        if($snapshot['best_selection'] === null) {
            $snapshot['best_selection'] = $selection_row;
        }

        $snapshot['selections'][] = $selection_row;
    }

    $recent_result = database()->query("SELECT
            `step_key`,
            `page_role`,
            `event_type`,
            `event_label`,
            `variant_key`,
            `meta`,
            `datetime`
        FROM `vip_funnel_events`
        WHERE `vip_funnel_id` = " . (int) $funnel_id . "
        ORDER BY `vip_funnel_event_id` DESC
        LIMIT 12");

    $event_type_labels = vip_funnel_get_public_event_type_labels();

    while($recent_result && ($row = $recent_result->fetch_object())) {
        $step_key = (string) ($row->step_key ?? '');
        $meta = vip_funnel_to_array($row->meta ?? []);
        $selection_key = trim((string) ($meta['selection'] ?? ''));
        $event_label = trim((string) ($row->event_label ?? ''));
        $label_key = $selection_key !== '' ? $selection_key : $event_label;

        $snapshot['recent_events'][] = [
            'datetime' => (string) ($row->datetime ?? ''),
            'event_type' => (string) ($row->event_type ?? ''),
            'event_type_label' => (string) ($event_type_labels[(string) ($row->event_type ?? '')] ?? ucfirst((string) ($row->event_type ?? ''))),
            'step_key' => $step_key,
            'step_title' => (string) (($step_registry[$step_key]['title'] ?? $step_key) ?: $step_key),
            'variant_key' => strtoupper((string) ($row->variant_key ?? 'a')),
            'label' => (string) ($selection_labels[$label_key] ?? ($label_key !== '' ? $label_key : '')),
        ];
    }

    if(vip_funnel_has_table('data')) {
        $contacts_result = database()->query("SELECT COUNT(*) AS `total`
            FROM `data`
            WHERE `type` = 'lead_funnel'
              AND JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.vip_funnel_id')) = '" . (int) $funnel_id . "'");
        $snapshot['contacts_in_data'] = (int) ($contacts_result ? ($contacts_result->fetch_object()->total ?? 0) : 0);
    }

    if(vip_funnel_demo_schema_is_ready()) {
        $demo_totals_result = database()->query("SELECT
                SUM(CASE WHEN `vip_demo_accounts`.`status` = 'requested' THEN 1 ELSE 0 END) AS `requests`,
                SUM(CASE WHEN `vip_demo_accounts`.`status` = 'approved' THEN 1 ELSE 0 END) AS `approved`,
                SUM(CASE WHEN `vip_demo_accounts`.`status` IN ('active', 'expiring', 'paused') THEN 1 ELSE 0 END) AS `live`,
                SUM(CASE WHEN `vip_demo_accounts`.`status` = 'converted' THEN 1 ELSE 0 END) AS `converted`,
                SUM(CASE WHEN `vip_demo_accounts`.`status` IN ('expired', 'closed', 'rejected') THEN 1 ELSE 0 END) AS `archived`
            FROM `vip_demo_accounts`
            INNER JOIN `vip_leads` ON `vip_leads`.`vip_lead_id` = `vip_demo_accounts`.`vip_lead_id`
            WHERE `vip_leads`.`vip_funnel_id` = " . (int) $funnel_id);
        $demo_totals = $demo_totals_result ? $demo_totals_result->fetch_object() : null;

        if($demo_totals) {
            $snapshot['demo']['requests'] = (int) ($demo_totals->requests ?? 0);
            $snapshot['demo']['approved'] = (int) ($demo_totals->approved ?? 0);
            $snapshot['demo']['live'] = (int) ($demo_totals->live ?? 0);
            $snapshot['demo']['converted'] = (int) ($demo_totals->converted ?? 0);
            $snapshot['demo']['archived'] = (int) ($demo_totals->archived ?? 0);
        }

        $demo_base = $snapshot['demo']['requests'] + $snapshot['demo']['approved'] + $snapshot['demo']['live'] + $snapshot['demo']['converted'] + $snapshot['demo']['archived'];
        $snapshot['demo']['activation_rate'] = $demo_base > 0 ? round((($snapshot['demo']['live'] + $snapshot['demo']['converted']) / max(1, $demo_base)) * 100, 1) : 0;
        $snapshot['demo']['conversion_rate'] = $demo_base > 0 ? round(($snapshot['demo']['converted'] / max(1, $demo_base)) * 100, 1) : 0;

        $demo_event_labels = vip_funnel_demo_get_event_labels();
        $demo_recent_result = database()->query("SELECT
                `vip_demo_events`.`event_key`,
                `vip_demo_events`.`datetime`,
                `actors`.`name` AS `actor_name`,
                `vip_leads`.`lead_name`,
                `vip_leads`.`full_name`,
                `vip_leads`.`payload` AS `lead_payload`
            FROM `vip_demo_events`
            INNER JOIN `vip_demo_accounts` ON `vip_demo_accounts`.`vip_demo_account_id` = `vip_demo_events`.`vip_demo_account_id`
            INNER JOIN `vip_leads` ON `vip_leads`.`vip_lead_id` = `vip_demo_accounts`.`vip_lead_id`
            LEFT JOIN `users` AS `actors` ON `actors`.`user_id` = `vip_demo_events`.`actor_user_id`
            WHERE `vip_leads`.`vip_funnel_id` = " . (int) $funnel_id . "
            ORDER BY `vip_demo_events`.`vip_demo_event_id` DESC
            LIMIT 10");

        while($demo_recent_result && ($row = $demo_recent_result->fetch_object())) {
            $lead_payload = vip_funnel_to_array($row->lead_payload ?? []);
            $lead_name = trim((string) ($row->lead_name ?? '')) ?: trim((string) ($row->full_name ?? '')) ?: trim((string) ($lead_payload['lead_name'] ?? ''));

            $snapshot['demo']['recent_events'][] = [
                'datetime' => (string) ($row->datetime ?? ''),
                'event_key' => (string) ($row->event_key ?? ''),
                'event_label' => (string) ($demo_event_labels[(string) ($row->event_key ?? '')] ?? ucfirst((string) ($row->event_key ?? ''))),
                'lead_name' => $lead_name,
                'actor_name' => trim((string) ($row->actor_name ?? '')),
            ];
        }
    }

    return $snapshot;
}

function vip_funnel_get_studio_state($user = null, int $funnel_id = 0): array {
    vip_funnel_ensure_runtime_schema();
    $schema_ready = vip_funnel_studio_schema_is_ready();
    if($user) {
        vip_funnel_backfill_owner_runtime_contacts((int) ($user->user_id ?? 0));
    }
    $payload = $schema_ready ? vip_funnel_studio_load_from_database($user, $funnel_id) : vip_funnel_get_user_studio_full_payload($user);
    $payload = vip_funnel_normalize_studio_payload($payload, $user);
    $funnel_row = $schema_ready && $user
        ? ($funnel_id > 0 ? vip_funnel_studio_get_funnel_row((int) ($user->user_id ?? 0), $funnel_id) : vip_funnel_studio_get_primary_funnel_row((int) ($user->user_id ?? 0)))
        : null;

    return [
        'schema_ready' => $schema_ready,
        'storage_mode' => $schema_ready ? 'database' : 'preferences_fallback',
        'payload' => $payload,
        'board_payload' => $payload['board'],
        'board' => vip_funnel_hydrate_board_for_view($payload['board']),
        'paths' => $payload['paths'],
        'funnel' => $payload['funnel'],
        'results' => vip_funnel_get_results_snapshot($user, (int) ($funnel_row->vip_funnel_id ?? 0)),
        'analytics' => vip_funnel_get_analytics_snapshot((int) ($funnel_row->vip_funnel_id ?? 0), $payload),
        'funnel_row' => $funnel_row,
        'funnels' => $schema_ready && $user ? vip_funnel_studio_get_funnel_rows((int) ($user->user_id ?? 0)) : [],
        'card_type_options' => vip_funnel_get_card_type_options(),
        'visibility_options' => vip_funnel_get_visibility_options(),
        'design_variant_options' => vip_funnel_get_design_variant_options(),
        'page_block_type_options' => vip_funnel_get_page_block_type_options(),
        'page_width_options' => vip_funnel_get_page_theme_width_options(),
        'page_alignment_options' => vip_funnel_get_page_block_alignment_options(),
        'page_block_width_options' => vip_funnel_get_page_block_width_options(),
        'page_action_options' => vip_funnel_get_page_action_type_options(),
        'page_block_template_presets' => vip_funnel_get_page_block_template_presets(),
        'owner_options' => function_exists('vip_funnel_demo_get_owner_options') ? vip_funnel_demo_get_owner_options($user) : [],
    ];
}

function vip_funnel_get_public_preferences_payload($user = null): array {
    if(!$user) {
        return [];
    }

    $preferences = vip_funnel_get_user_preferences($user);
    $studio_full = vip_funnel_normalize_object($preferences->vip_funnel_studio_full ?? []);
    $payload = vip_funnel_to_array($studio_full->payload ?? []);

    if(!empty($payload)) {
        return vip_funnel_normalize_studio_payload($payload, $user);
    }

    $studio_preferences = vip_funnel_normalize_object($preferences->vip_funnel_studio ?? []);
    $board = vip_funnel_to_array($studio_preferences->board ?? []);

    if(!empty($board)) {
        return vip_funnel_normalize_studio_payload([
            'board' => $board,
        ], $user);
    }

    return [];
}

function vip_funnel_get_public_payload_for_user(int $user_id = 0, string $funnel_slug = '', int $funnel_id = 0): ?array {
    if($user_id <= 0) {
        return null;
    }

    $user = db()
        ->where('user_id', $user_id)
        ->getOne('users', ['user_id', 'name', 'email', 'preferences', 'plan_settings']);

    if(!$user || !vip_funnel_user_can_publish_public_hub($user)) {
        return null;
    }

    if(vip_funnel_studio_schema_is_ready()) {
        $funnel = null;

        if($funnel_id > 0) {
            $funnel = vip_funnel_studio_get_funnel_row($user_id, $funnel_id);
        } elseif(trim($funnel_slug) !== '') {
            $funnel = vip_funnel_studio_get_funnel_row_by_slug($user_id, $funnel_slug);
        } else {
            $funnel = vip_funnel_studio_get_primary_funnel_row($user_id);
        }

        if($funnel) {
            return vip_funnel_studio_load_from_database($user, (int) $funnel->vip_funnel_id);
        }

        if($funnel_id > 0 || trim($funnel_slug) !== '') {
            return null;
        }
    }

    $preferences_payload = vip_funnel_get_public_preferences_payload($user);

    return !empty($preferences_payload) ? $preferences_payload : null;
}

function vip_funnel_get_public_step_registry(array $payload): array {
    $payload = vip_funnel_normalize_studio_payload($payload);
    $phase_definitions = vip_funnel_get_phase_definitions();
    $flat_steps = [];
    $lookup = [];

    foreach(array_values((array) ($payload['board'] ?? [])) as $phase_index => $phase) {
        $phase = vip_funnel_to_array($phase);
        $phase_key = (string) ($phase['key'] ?? '');
        $phase_title = (string) ($phase_definitions[$phase_key]['title'] ?? ucfirst($phase_key));

        foreach(array_values((array) ($phase['steps'] ?? [])) as $step_index => $step) {
            $step = vip_funnel_to_array($step);
            $step_id = (string) ($step['id'] ?? '');

            if($step_id === '') {
                continue;
            }

            $context = [
                'phase_index' => $phase_index,
                'step_index' => $step_index,
                'phase_key' => $phase_key,
                'phase_title' => $phase_title,
                'step' => $step,
            ];

            $flat_steps[] = $context;
            $lookup[$step_id] = $context;
        }
    }

    return [
        'flat_steps' => $flat_steps,
        'lookup' => $lookup,
    ];
}

function vip_funnel_get_page_default_target_step_id(array $payload, array $registry, ?array $active_context = null): string {
    if($active_context) {
        return vip_funnel_studio_get_auto_next_step_id($payload['board'] ?? [], (int) ($active_context['phase_index'] ?? 0), (int) ($active_context['step_index'] ?? 0), $active_context['step'] ?? []);
    }

    $first_step = $registry['flat_steps'][0]['step'] ?? [];
    return (string) ($first_step['id'] ?? '');
}

function vip_funnel_resolve_public_action(array $action, array $payload, array $registry, ?array $active_context = null, int $user_id = 0, string $slug = ''): array {
    $action = vip_funnel_to_array($action);
    $target_step_id = trim((string) ($action['target_step_id'] ?? ''));
    $external_url = trim((string) ($action['external_url'] ?? ''));
    $action_type = (string) ($action['action'] ?? 'goto_step');
    $defer_to_route_on_submit = false;

    if($action_type === 'external_url' && vip_funnel_is_forever_business_referral_action_token($external_url)) {
        $dynamic_url = vip_funnel_get_forever_business_referral_url($user_id);

        return [
            'target_step_id' => '',
            'url' => $dynamic_url !== '' ? $dynamic_url : SITE_URL . 'blog/start-paket',
            'action' => 'external_url',
            'is_submit' => false,
            'require_submit' => false,
        ];
    }

    if($action_type === 'external_url' && $external_url !== '') {
        return [
            'target_step_id' => '',
            'url' => $external_url,
            'action' => 'external_url',
            'is_submit' => false,
            'require_submit' => false,
        ];
    }

    if($target_step_id === '') {
        if($action_type === 'submit_next' && $active_context) {
            foreach((array) ($active_context['step']['page']['blocks'] ?? []) as $block) {
                $block = vip_funnel_to_array($block);
                if(($block['type'] ?? '') === 'radio_survey' && !empty($block['route_on_submit'])) {
                    $defer_to_route_on_submit = true;
                    break;
                }
            }
        }

        if(!$defer_to_route_on_submit && $active_context && !empty($active_context['step']['next_step_id'])) {
            $target_step_id = (string) $active_context['step']['next_step_id'];
        }

        if(!$defer_to_route_on_submit && $target_step_id === '') {
            $target_step_id = vip_funnel_get_page_default_target_step_id($payload, $registry, $active_context);
        }
    }

    $is_submit = in_array($action_type, ['submit_next', 'submit_stay'], true) || !empty($action['require_submit']);

    $resolved_url = $action_type === 'submit_stay' ? '' : ($target_step_id !== '' ? vip_funnel_get_public_funnel_url($user_id, $slug, $target_step_id) : vip_funnel_get_public_funnel_url($user_id, $slug));

    $selection_to_carry = trim((string) ($action['value'] ?? ''));
    if($selection_to_carry === '') {
        $selection_to_carry = trim((string) (vip_funnel_to_array($payload['runtime_context'] ?? [])['selection'] ?? ''));
    }

    if($resolved_url !== '' && $selection_to_carry !== '') {
        $resolved_url = vip_funnel_append_url_query_param($resolved_url, 'vfsel', $selection_to_carry);
    }

    return [
        'target_step_id' => $target_step_id,
        'url' => $resolved_url,
        'action' => $action_type,
        'is_submit' => $is_submit,
        'require_submit' => !empty($action['require_submit']),
    ];
}

function vip_funnel_pick_variant_for_surface(array $surface, string $visitor_key = ''): string {
    if(empty($surface['ab_enabled']) || empty($surface['variant_b_blocks'])) {
        return 'a';
    }

    $distribution = max(5, min(95, (int) ($surface['ab_distribution'] ?? 50)));
    $hash_seed = $visitor_key !== '' ? $visitor_key : uniqid('vipf_', true);
    $bucket = abs(crc32($hash_seed . '|' . ($surface['name'] ?? 'surface'))) % 100;

    return $bucket < $distribution ? 'b' : 'a';
}

function vip_funnel_prepare_public_blocks(array $blocks, array $payload, array $registry, ?array $active_context, int $user_id, string $slug): array {
    $prepared = [];
    $page_language_code = (string) \Altum\Language::$code;
    $runtime_context = vip_funnel_to_array($payload['runtime_context'] ?? []);

    foreach(array_values($blocks) as $block) {
        $block = vip_funnel_normalize_page_block_payload($block);

        if($block['type'] === 'product_offer') {
            $block = vip_funnel_resolve_product_offer_render_data($block, $user_id, $page_language_code, $runtime_context);
        }

        if($block['type'] === 'cta_group') {
            $buttons = [];

            foreach((array) ($block['buttons'] ?? []) as $button) {
                $resolution = vip_funnel_resolve_public_action($button, $payload, $registry, $active_context, $user_id, $slug);
                $buttons[] = array_merge($button, $resolution);
            }

            $block['buttons'] = $buttons;
        }

        if(in_array($block['type'], ['survey', 'radio_survey'], true)) {
            $options = [];

            foreach((array) ($block['options'] ?? []) as $option) {
                $resolution = vip_funnel_resolve_public_action($option, $payload, $registry, $active_context, $user_id, $slug);
                $options[] = array_merge($option, $resolution);
            }

            $block['options'] = $options;
        }

        $prepared[] = $block;
    }

    return $prepared;
}

function vip_funnel_get_public_funnel_url(int $user_id = 0, string $funnel_slug = '', string $step_id = ''): string {
    if($user_id <= 0) {
        return '';
    }

    $funnel_slug = vip_funnel_slugify($funnel_slug !== '' ? $funnel_slug : 'vip-funnel-2-0', 'vip-funnel-2-0');
    $url = SITE_URL . 'vip-funnel/' . rawurlencode((string) $user_id) . '/' . rawurlencode($funnel_slug);

    if($step_id !== '') {
        $url .= '?step=' . rawurlencode($step_id);
    }

    return $url;
}

function vip_funnel_get_public_entry_step_id(array $payload): string {
    $payload = vip_funnel_normalize_studio_payload($payload);
    $registry = vip_funnel_get_public_step_registry($payload);
    $first_step = $registry['flat_steps'][0]['step'] ?? [];

    return (string) ($first_step['id'] ?? '');
}

function vip_funnel_get_public_step_state(int $user_id = 0, string $requested_step_id = '', string $requested_slug = '', int $requested_funnel_id = 0): ?array {
    $payload = vip_funnel_get_public_payload_for_user($user_id, $requested_slug, $requested_funnel_id);

    if(!$payload) {
        return null;
    }

    vip_funnel_ensure_runtime_schema();
    $payload = vip_funnel_normalize_studio_payload($payload, (object) ['user_id' => $user_id]);
    $registry = vip_funnel_get_public_step_registry($payload);
    $flat_steps = $registry['flat_steps'];
    $step_lookup = $registry['lookup'];

    if(empty($flat_steps) && empty($payload['landing_page']['blocks'])) {
        return null;
    }

    $first_context = $flat_steps[0] ?? null;
    $active_context = $requested_step_id !== '' ? ($step_lookup[$requested_step_id] ?? $first_context) : null;
    $active_step = $active_context['step'] ?? [];
    $slug = vip_funnel_slugify((string) ($payload['funnel']['slug'] ?? 'vip-funnel-2-0'), 'vip-funnel-2-0');
    $funnel_row = vip_funnel_to_array($payload['funnel_row'] ?? []);
    $funnel_id = (int) ($funnel_row['vip_funnel_id'] ?? 0);
    $first_step_id = (string) ($first_context['step']['id'] ?? '');
    $current_step_id = (string) ($active_step['id'] ?? '');
    $viewer_key = function_exists('fc_get_funnel_visitor_key') ? fc_get_funnel_visitor_key() : md5(uniqid((string) $user_id, true));
    $runtime_context = vip_funnel_get_public_runtime_context($user_id, $viewer_key, $funnel_id);
    $query_selection = trim(input_clean((string) ($_GET['vfsel'] ?? ''), 120));
    if($query_selection !== '') {
        $runtime_context['selection'] = $query_selection;
    }
    $payload['runtime_context'] = $runtime_context;

    $path_title = '';
    foreach((array) ($payload['paths'] ?? []) as $path) {
        $path = vip_funnel_to_array($path);

        if((string) ($path['path_key'] ?? '') === (string) ($active_step['path_key'] ?? '')) {
            $path_title = (string) ($path['title'] ?? '');
            break;
        }
    }

    $page_role = $current_step_id === '' ? 'landing' : 'step';
    $surface = $page_role === 'landing'
        ? vip_funnel_normalize_page_surface_payload($payload['landing_page'] ?? [], l('vip_funnel.studio.landing.title'))
        : vip_funnel_normalize_page_surface_payload($active_step['page'] ?? [], (string) ($active_step['title'] ?? 'Funnel stranica'));
    if($page_role === 'step' && empty($surface['blocks'])) {
        $surface = vip_funnel_build_surface_from_legacy_step($active_step, (string) ($active_step['title'] ?? 'Funnel stranica'));
    }
    if($page_role === 'step') {
        $surface = vip_funnel_upgrade_surface_for_legacy_step($surface, $active_step);
    }
    $variant_key = vip_funnel_pick_variant_for_surface($surface, $viewer_key);
    $surface = vip_funnel_apply_surface_variant($surface, $variant_key);
    $surface_blocks = $variant_key === 'b' && !empty($surface['variant_b_blocks']) ? $surface['variant_b_blocks'] : $surface['blocks'];
    $surface_blocks = vip_funnel_prepare_public_blocks($surface_blocks, $payload, $registry, $active_context, $user_id, $slug);

    $current_index = 1;
    foreach($flat_steps as $index => $context) {
        if((string) ($context['step']['id'] ?? '') === $current_step_id) {
            $current_index = $index + 1;
            break;
        }
    }

    return [
        'user_id' => $user_id,
        'funnel_id' => $funnel_id,
        'funnel_row' => $funnel_row,
        'payload' => $payload,
        'viewer_key' => $viewer_key,
        'slug' => $slug,
        'first_step_id' => $first_step_id,
        'current_step_id' => $current_step_id,
        'canonical_url' => vip_funnel_get_public_funnel_url($user_id, $slug, $current_step_id === $first_step_id ? '' : $current_step_id),
        'entry_url' => vip_funnel_get_public_funnel_url($user_id, $slug),
        'page_role' => $page_role,
        'page_key' => $page_role === 'landing' ? 'landing' : $current_step_id,
        'page_surface' => $surface,
        'variant_key' => $variant_key,
        'blocks' => $surface_blocks,
        'total_steps' => count($flat_steps),
        'current_step_number' => $page_role === 'landing' ? 0 : $current_index,
        'path_title' => $path_title,
        'runtime_context' => $runtime_context,
        'owner_profile' => vip_funnel_get_owner_contact_profile($user_id),
        'active' => [
            'id' => $current_step_id,
            'phase_key' => (string) ($active_context['phase_key'] ?? ''),
            'phase_title' => (string) ($active_context['phase_title'] ?? ''),
            'title' => $page_role === 'landing' ? (string) ($payload['overview']['headline'] ?? ($payload['funnel']['name'] ?? 'VIP Funnel 2.0')) : (string) ($active_step['title'] ?? ''),
            'summary' => $page_role === 'landing' ? (string) ($payload['overview']['subheadline'] ?? '') : (string) ($active_step['summary'] ?? ''),
            'helper_text' => (string) ($active_step['helper_text'] ?? ''),
            'preview_badge' => $page_role === 'landing' ? (string) ($payload['overview']['eyebrow'] ?? 'Funnel 2.0') : (string) ($active_step['preview_badge'] ?? ''),
            'background_color' => (string) ($surface['background_color'] ?? '#152132'),
            'text_color' => (string) ($surface['text_color'] ?? '#eef4ff'),
            'accent_color' => (string) ($surface['accent_color'] ?? '#67d8c9'),
            'block_mode' => $page_role === 'landing' ? 'landing_page' : (string) ($active_step['block_mode'] ?? 'message'),
            'cta' => (string) ($active_step['cta'] ?? ''),
        ],
        'buttons' => [],
    ];
}

function vip_funnel_log_public_event(array $state, string $event_type, string $event_label = '', array $meta = [], int $vip_lead_id = 0, int $run_id = 0): void {
    vip_funnel_ensure_runtime_schema();

    if(!vip_funnel_has_table('vip_funnel_events')) {
        return;
    }

    $payload = $state['payload'] ?? [];
    $funnel_id = vip_funnel_resolve_state_funnel_id($state);

    if($funnel_id <= 0 && vip_funnel_studio_schema_is_ready()) {
        $funnel = vip_funnel_studio_get_primary_funnel_row((int) ($state['user_id'] ?? 0));
        $funnel_id = (int) ($funnel->vip_funnel_id ?? 0);
    }

    if($funnel_id <= 0) {
        $funnel = vip_funnel_studio_get_primary_funnel_row((int) ($state['user_id'] ?? 0));
        $funnel_id = (int) ($funnel->vip_funnel_id ?? 0);
    }

    if($funnel_id <= 0) {
        return;
    }

    db()->insert('vip_funnel_events', [
        'vip_funnel_id' => $funnel_id,
        'user_id' => (int) ($state['user_id'] ?? 0),
        'visitor_key' => (string) ($state['viewer_key'] ?? ''),
        'vip_funnel_run_id' => $run_id > 0 ? $run_id : null,
        'vip_lead_id' => $vip_lead_id > 0 ? $vip_lead_id : null,
        'step_key' => (string) ($state['page_key'] ?? 'landing'),
        'page_role' => (string) ($state['page_role'] ?? 'landing'),
        'block_id' => trim(input_clean((string) ($meta['block_id'] ?? ''), 120)),
        'variant_key' => (string) ($state['variant_key'] ?? 'a'),
        'event_type' => input_clean($event_type, 32),
        'event_label' => trim(input_clean($event_label, 160)),
        'meta' => vip_funnel_json_encode($meta),
        'datetime' => get_date(),
    ]);
}

function vip_funnel_log_public_block_views(array $state, int $run_id = 0): void {
    foreach((array) ($state['blocks'] ?? []) as $block) {
        $block = vip_funnel_to_array($block);
        $block_id = trim((string) ($block['id'] ?? ''));
        $block_type = trim((string) ($block['type'] ?? ''));

        if($block_id === '') {
            continue;
        }

        vip_funnel_log_public_event($state, 'block_view', trim((string) ($block['title'] ?? ($block['label'] ?? $block_type))), [
            'block_id' => $block_id,
            'block_type' => $block_type,
            'layout_width' => (string) ($block['layout_width'] ?? 'full'),
        ], 0, $run_id);
    }
}

function vip_funnel_process_public_tracking(array $state, array $post = []): array {
    $post = vip_funnel_to_array($post);
    $event_type = input_clean((string) ($post['vf_event_type'] ?? 'cta_click'), 32);

    if($event_type === '') {
        return ['success' => false];
    }

    $run_id = vip_funnel_get_or_create_public_run($state);
    $cta_label = trim(input_clean((string) ($post['vf_label'] ?? ''), 160));
    vip_funnel_log_public_event($state, $event_type, $cta_label !== '' ? $cta_label : trim(input_clean((string) ($post['vf_action'] ?? ''), 64)), [
        'block_id' => trim(input_clean((string) ($post['vf_block_id'] ?? ''), 120)),
        'block_type' => trim(input_clean((string) ($post['vf_block_type'] ?? ''), 64)),
        'cta_label' => $cta_label,
        'action' => trim(input_clean((string) ($post['vf_action'] ?? ''), 64)),
        'target_step_id' => trim(input_clean((string) ($post['vf_target_step_id'] ?? ''), 128)),
        'external_url' => trim(input_clean((string) ($post['vf_external_url'] ?? ''), 2048)),
        'selection' => trim(input_clean((string) ($post['vf_selection'] ?? ''), 160)),
        'signal_key' => trim(input_clean((string) ($post['vf_signal_key'] ?? ''), 64)),
    ], 0, $run_id);

    return ['success' => true];
}

function vip_funnel_get_public_runtime_context(int $user_id = 0, string $viewer_key = '', int $funnel_id = 0): array {
    $context = [
        'selection' => '',
        'radio_answers' => [],
    ];

    if(!vip_funnel_studio_schema_is_ready()) {
        return $context;
    }

    vip_funnel_ensure_runtime_schema();
    $viewer_key = trim($viewer_key);

    if($user_id <= 0 || $viewer_key === '') {
        return $context;
    }

    if($funnel_id <= 0) {
        $funnel = vip_funnel_studio_get_primary_funnel_row($user_id);
        $funnel_id = (int) ($funnel->vip_funnel_id ?? 0);
    }

    if($funnel_id <= 0) {
        return $context;
    }

    $run = db()
        ->where('vip_funnel_id', $funnel_id)
        ->where('visitor_key', $viewer_key)
        ->orderBy('vip_funnel_run_id', 'DESC')
        ->getOne('vip_funnel_runs', ['payload']);

    if(!$run) {
        return $context;
    }

    $payload = vip_funnel_to_array($run->payload ?? []);
    $context['selection'] = trim((string) ($payload['selection'] ?? ''));
    $context['radio_answers'] = vip_funnel_to_array($payload['radio_answers'] ?? []);

    return $context;
}

function vip_funnel_get_or_create_public_run(array $state, int $vip_lead_id = 0, array $context = []): int {
    if(!vip_funnel_studio_schema_is_ready()) {
        return 0;
    }

    vip_funnel_ensure_runtime_schema();
    $user_id = (int) ($state['user_id'] ?? 0);
    $viewer_key = trim((string) ($state['viewer_key'] ?? ''));

    if($user_id <= 0 || $viewer_key === '') {
        return 0;
    }

    $funnel_id = vip_funnel_resolve_state_funnel_id($state);

    if($funnel_id <= 0) {
        return 0;
    }

    $run = db()
        ->where('vip_funnel_id', $funnel_id)
        ->where('visitor_key', $viewer_key)
        ->orderBy('vip_funnel_run_id', 'DESC')
        ->getOne('vip_funnel_runs');

    $payload = [
        'page_key' => (string) ($state['page_key'] ?? 'landing'),
        'page_role' => (string) ($state['page_role'] ?? 'landing'),
        'variant_key' => (string) ($state['variant_key'] ?? 'a'),
        'last_view_at' => get_date(),
    ];
    $context = vip_funnel_to_array($context);
    if(array_key_exists('selection', $context)) {
        $payload['selection'] = trim((string) ($context['selection'] ?? ''));
    }
    if(array_key_exists('radio_answers', $context)) {
        $payload['radio_answers'] = vip_funnel_to_array($context['radio_answers'] ?? []);
    }

    if($run) {
        db()->where('vip_funnel_run_id', (int) $run->vip_funnel_run_id)->update('vip_funnel_runs', [
            'vip_lead_id' => $vip_lead_id > 0 ? $vip_lead_id : ($run->vip_lead_id ?? null),
            'current_step_key' => (string) ($state['page_key'] ?? 'landing'),
            'variant_key' => (string) ($state['variant_key'] ?? 'a'),
            'payload' => vip_funnel_json_encode(array_merge(vip_funnel_to_array($run->payload ?? []), $payload)),
            'last_datetime' => get_date(),
        ]);

        return (int) $run->vip_funnel_run_id;
    }

    return (int) db()->insert('vip_funnel_runs', [
        'vip_funnel_id' => $funnel_id,
        'vip_lead_id' => $vip_lead_id > 0 ? $vip_lead_id : null,
        'source' => 'vip_funnel_public',
        'current_card_id' => null,
        'current_step_key' => (string) ($state['page_key'] ?? 'landing'),
        'status' => 'open',
        'visitor_key' => $viewer_key,
        'variant_key' => (string) ($state['variant_key'] ?? 'a'),
        'payload' => vip_funnel_json_encode($payload),
        'datetime' => get_date(),
        'last_datetime' => get_date(),
    ]);
}

function vip_funnel_get_public_capture_field_map(array $blocks): array {
    $map = [];

    foreach($blocks as $block) {
        $block = vip_funnel_to_array($block);
        $type = (string) ($block['type'] ?? '');

        if(in_array($type, ['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field'], true)) {
            $map[$block['id']] = $block;
        }
    }

    return $map;
}

function vip_funnel_get_public_radio_field_map(array $blocks): array {
    $map = [];

    foreach($blocks as $block) {
        $block = vip_funnel_to_array($block);

        if((string) ($block['type'] ?? '') === 'radio_survey') {
            $map[$block['id']] = $block;
        }
    }

    return $map;
}

function vip_funnel_resolve_state_funnel_id(array $state = []): int {
    $payload = vip_funnel_to_array($state['payload'] ?? []);
    $funnel_row = vip_funnel_to_array($state['funnel_row'] ?? []);
    $payload_funnel_row = vip_funnel_to_array($payload['funnel_row'] ?? []);
    $funnel_id = (int) (($state['funnel_id'] ?? 0) ?: ($funnel_row['vip_funnel_id'] ?? 0) ?: ($payload_funnel_row['vip_funnel_id'] ?? 0));

    if($funnel_id <= 0 && vip_funnel_studio_schema_is_ready()) {
        $funnel = vip_funnel_studio_get_primary_funnel_row((int) ($state['user_id'] ?? 0));
        $funnel_id = (int) ($funnel->vip_funnel_id ?? 0);
    }

    return $funnel_id;
}

function vip_funnel_get_contact_intent_key(string $source_key = 'vip_funnel', string $demo_status = ''): string {
    if($source_key === 'vip_demo_access') {
        switch($demo_status) {
            case 'active':
            case 'expiring':
            case 'paused':
                return 'demo_active';

            case 'converted':
                return 'demo_converted';

            case 'expired':
            case 'closed':
            case 'rejected':
                return 'demo_archived';

            default:
                return 'demo_request';
        }
    }

    return $demo_status === 'converted' ? 'demo_converted' : 'funnel_lead';
}

function vip_funnel_find_contact_datum(int $user_id = 0, int $vip_lead_id = 0, string $lead_email = '', string $lead_phone = '', string $visitor_key = ''): ?\stdClass {
    if($user_id <= 0 || !vip_funnel_has_table('data')) {
        return null;
    }

    $conditions = [];

    if($vip_lead_id > 0) {
        $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.vip_lead_id')) = '" . (int) $vip_lead_id . "'";
    }

    if($lead_email !== '') {
        $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.email')) = '" . database()->real_escape_string($lead_email) . "'";
    }

    if($lead_phone !== '') {
        $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.phone')) = '" . database()->real_escape_string($lead_phone) . "'";
    }

    if($visitor_key !== '') {
        $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.visitor_key')) = '" . database()->real_escape_string($visitor_key) . "'";
    }

    if(empty($conditions)) {
        return null;
    }

    $sql = "SELECT `datum_id`, `data`
        FROM `data`
        WHERE `user_id` = " . (int) $user_id . "
          AND `type` = 'lead_funnel'
          AND (" . implode(' OR ', $conditions) . ")
        ORDER BY `datum_id` DESC
        LIMIT 1";

    $result = database()->query($sql);

    return $result ? $result->fetch_object() : null;
}

function vip_funnel_merge_contact_payload(array $existing = [], array $payload = []): array {
    foreach($payload as $key => $value) {
        if(is_array($value)) {
            if(!empty($value) || !isset($existing[$key])) {
                $existing[$key] = $value;
            }
            continue;
        }

        if($value !== '' && $value !== null) {
            $existing[$key] = $value;
            continue;
        }

        if(!array_key_exists($key, $existing)) {
            $existing[$key] = $value;
        }
    }

    return $existing;
}

function vip_funnel_sync_contact_data_entry(int $user_id = 0, array $payload = []): int {
    if($user_id <= 0 || !vip_funnel_has_table('data')) {
        return 0;
    }

    $vip_lead_id = (int) ($payload['vip_lead_id'] ?? 0);
    $lead_email = trim((string) ($payload['email'] ?? ''));
    $lead_phone = trim((string) ($payload['phone'] ?? ''));
    $visitor_key = trim((string) ($payload['visitor_key'] ?? ''));
    $existing = vip_funnel_find_contact_datum($user_id, $vip_lead_id, $lead_email, $lead_phone, $visitor_key);
    $stored_payload = $existing ? vip_funnel_to_array($existing->data ?? []) : [];
    $merged_payload = vip_funnel_merge_contact_payload($stored_payload, $payload);

    if($existing) {
        db()->where('datum_id', (int) $existing->datum_id)->update('data', [
            'data' => vip_funnel_json_encode($merged_payload),
            'datetime' => get_date(),
        ]);

        return (int) $existing->datum_id;
    }

    return (int) db()->insert('data', [
        'biolink_block_id' => null,
        'link_id' => null,
        'project_id' => null,
        'user_id' => $user_id,
        'type' => 'lead_funnel',
        'data' => vip_funnel_json_encode($merged_payload),
        'datetime' => get_date(),
    ]);
}

function vip_funnel_sync_contact_data_from_lead_id(int $vip_lead_id = 0, array $context = []): int {
    if($vip_lead_id <= 0 || !vip_funnel_demo_schema_is_ready()) {
        return 0;
    }

    $lead = db()->where('vip_lead_id', $vip_lead_id)->getOne('vip_leads');

    if(!$lead) {
        return 0;
    }

    $lead_payload = vip_funnel_to_array($lead->payload ?? []);
    $funnel_context = vip_funnel_to_array($lead_payload['funnel_context'] ?? []);
    $contact_origin = vip_funnel_to_array($lead_payload['contact_origin'] ?? []);
    $meta = vip_funnel_to_array($lead_payload['meta'] ?? []);
    $captured_fields = vip_funnel_to_array($lead_payload['captured_fields'] ?? []);
    $qualification = vip_funnel_to_array($lead_payload['qualification'] ?? []);
    $follow_up_automation = vip_funnel_to_array($lead_payload['follow_up_automation'] ?? []);
    $demo_status = trim((string) ($context['demo_status'] ?? ($lead->demo_status ?? '')));
    $source_key = trim((string) ($contact_origin['source_key'] ?? ($lead->source === 'vip_funnel_public' ? 'vip_funnel' : 'vip_demo_access')));
    $source_label = trim((string) ($contact_origin['source_label'] ?? ($source_key === 'vip_demo_access' ? l('vip_funnel.contacts.source.vip_demo_access') : l('vip_funnel.contacts.source.vip_funnel'))));
    $funnel_name = trim((string) ($funnel_context['funnel_name'] ?? ''));
    $step_title = trim((string) ($funnel_context['step_title'] ?? ''));
    $source_context = trim((string) ($contact_origin['source_context'] ?? ''));
    $owner_profile = vip_funnel_get_owner_contact_profile((int) ($lead->owner_user_id ?? 0));

    if($source_context === '') {
        $parts = array_values(array_filter([$funnel_name, $step_title], static function($value) {
            return trim((string) $value) !== '';
        }));

        if(!empty($parts)) {
            $source_context = implode(' • ', $parts);
        }
    }

    $contact_payload = [
        'vip_lead_id' => (int) $vip_lead_id,
        'vip_funnel_id' => (int) ($lead->vip_funnel_id ?? ($funnel_context['vip_funnel_id'] ?? 0)),
        'source' => (string) ($lead->source ?? ''),
        'source_key' => $source_key,
        'source_label' => $source_label,
        'source_context' => $source_context,
        'contact_intent_key' => vip_funnel_get_contact_intent_key($source_key, $demo_status),
        'name' => trim((string) ($lead->lead_name ?? ($captured_fields['name'] ?? ''))),
        'full_name' => trim((string) ($lead->full_name ?? ($captured_fields['full_name'] ?? ''))),
        'email' => trim((string) ($lead->lead_email ?? ($captured_fields['email'] ?? ''))),
        'phone' => trim((string) ($lead->lead_phone ?? ($captured_fields['phone'] ?? ''))),
        'contact_time' => trim((string) ($captured_fields['contact_time'] ?? '')),
        'visitor_key' => trim((string) ($lead->visitor_key ?? ($funnel_context['visitor_key'] ?? ''))),
        'interest_type' => (string) ($lead->interest_type ?? ''),
        'business_readiness' => (string) ($lead->business_readiness ?? ''),
        'product_goal' => (string) ($lead->product_goal ?? ''),
        'selection' => trim((string) ($lead->selection_value ?? ($meta['selection'] ?? $lead->product_goal ?? ''))),
        'radio_answers' => vip_funnel_to_array($meta['radio_answers'] ?? []),
        'lead_score' => (int) ($qualification['score'] ?? 0),
        'lead_segment' => trim((string) ($qualification['segment'] ?? '')),
        'lead_readiness' => trim((string) ($qualification['readiness_key'] ?? '')),
        'qualification_reasons' => vip_funnel_to_array($qualification['reasons'] ?? []),
        'recommended_next_action' => trim((string) ($qualification['recommended_next_action'] ?? '')),
        'follow_up_automation' => $follow_up_automation,
        'demo_status' => $demo_status,
        'demo_account_id' => (int) ($context['vip_demo_account_id'] ?? 0),
        'demo_workspace_url' => trim((string) ($context['workspace_url'] ?? '')),
        'demo_login_email' => trim((string) ($context['login_email'] ?? ($lead_payload['demo_login_email'] ?? ''))),
        'funnel_name' => $funnel_name,
        'funnel_slug' => trim((string) ($funnel_context['funnel_slug'] ?? '')),
        'funnel_step_key' => trim((string) ($lead->source_step_key ?? ($funnel_context['step_key'] ?? ''))),
        'funnel_step_title' => $step_title,
        'funnel_page_role' => trim((string) ($funnel_context['page_role'] ?? '')),
        'funnel_page_url' => trim((string) ($funnel_context['page_url'] ?? '')),
        'variant_key' => trim((string) ($funnel_context['variant_key'] ?? '')),
        'owner_user_id' => (int) ($lead->owner_user_id ?? 0),
        'owner_name' => (string) ($owner_profile['name'] ?? ''),
        'owner_email' => (string) ($owner_profile['email'] ?? ''),
        'owner_whatsapp_url' => (string) ($owner_profile['whatsapp_url'] ?? ''),
        'owner_referral_slug' => (string) ($owner_profile['referral_slug'] ?? ''),
        'owner_referral_key' => (string) ($owner_profile['referral_key'] ?? ''),
        'owner_main_biolink_url' => (string) ($owner_profile['main_biolink_url'] ?? ''),
    ];

    return vip_funnel_sync_contact_data_entry((int) ($lead->owner_user_id ?? 0), $contact_payload);
}

function vip_funnel_backfill_owner_runtime_contacts(int $owner_user_id = 0): array {
    if($owner_user_id <= 0 || !vip_funnel_demo_schema_is_ready()) {
        return ['updated' => 0, 'synced' => 0];
    }

    vip_funnel_ensure_runtime_schema();

    $result = database()->query("
        SELECT *
        FROM `vip_leads`
        WHERE `owner_user_id` = " . (int) $owner_user_id . "
        ORDER BY `vip_lead_id` ASC
    ");

    if(!$result) {
        return ['updated' => 0, 'synced' => 0];
    }

    $updated = 0;
    $synced = 0;

    while($lead = $result->fetch_object()) {
        $payload = vip_funnel_to_array($lead->payload ?? []);
        $captured_fields = vip_funnel_to_array($payload['captured_fields'] ?? []);
        $meta = vip_funnel_to_array($payload['meta'] ?? []);
        $funnel_context = vip_funnel_to_array($payload['funnel_context'] ?? []);
        $contact_origin = vip_funnel_to_array($payload['contact_origin'] ?? []);

        $update = [];

        $resolved_funnel_id = (int) ($lead->vip_funnel_id ?? 0);
        if($resolved_funnel_id <= 0) {
            $resolved_funnel_id = (int) ($funnel_context['vip_funnel_id'] ?? 0);
            if($resolved_funnel_id > 0) {
                $update['vip_funnel_id'] = $resolved_funnel_id;
            }
        }

        $resolved_step_key = trim((string) ($lead->source_step_key ?? ''));
        if($resolved_step_key === '') {
            $resolved_step_key = trim((string) ($funnel_context['step_key'] ?? ($funnel_context['page_key'] ?? ($payload['page_key'] ?? ''))));
            if($resolved_step_key !== '') {
                $update['source_step_key'] = $resolved_step_key;
            }
        }

        $resolved_selection = trim((string) ($lead->selection_value ?? ''));
        if($resolved_selection === '') {
            $resolved_selection = trim((string) ($meta['selection'] ?? ($lead->product_goal ?? '')));
            if($resolved_selection !== '') {
                $update['selection_value'] = $resolved_selection;
            }
        }

        $resolved_name = trim((string) ($lead->lead_name ?? ''));
        if($resolved_name === '') {
            $resolved_name = trim((string) ($payload['lead_name'] ?? ($captured_fields['name'] ?? '')));
            if($resolved_name !== '') {
                $update['lead_name'] = $resolved_name;
            }
        }

        $resolved_full_name = trim((string) ($lead->full_name ?? ''));
        if($resolved_full_name === '') {
            $resolved_full_name = trim((string) ($payload['full_name'] ?? ($captured_fields['full_name'] ?? $resolved_name)));
            if($resolved_full_name !== '') {
                $update['full_name'] = $resolved_full_name;
            }
        }

        $resolved_email = trim((string) ($lead->lead_email ?? ''));
        if($resolved_email === '') {
            $resolved_email = trim((string) ($payload['lead_email'] ?? ($captured_fields['email'] ?? '')));
            if($resolved_email !== '') {
                $update['lead_email'] = $resolved_email;
            }
        }

        $resolved_phone = trim((string) ($lead->lead_phone ?? ''));
        if($resolved_phone === '') {
            $resolved_phone = trim((string) ($payload['lead_phone'] ?? ($captured_fields['phone'] ?? '')));
            if($resolved_phone !== '') {
                $update['lead_phone'] = $resolved_phone;
            }
        }

        if(empty($contact_origin)) {
            $payload['contact_origin'] = [
                'source_key' => $lead->source === 'vip_funnel_public' ? 'vip_funnel' : 'vip_demo_access',
                'source_label' => $lead->source === 'vip_funnel_public' ? l('vip_funnel.contacts.source.vip_funnel') : l('vip_funnel.contacts.source.vip_demo_access'),
            ];
            $update['payload'] = vip_funnel_json_encode($payload);
        }

        if(!empty($update)) {
            db()->where('vip_lead_id', (int) $lead->vip_lead_id)->update('vip_leads', $update);
            $updated++;
        }

        $synced += vip_funnel_sync_contact_data_from_lead_id((int) $lead->vip_lead_id, [
            'demo_status' => (string) ($lead->demo_status ?? ''),
        ]) ? 1 : 0;
    }

    return [
        'updated' => $updated,
        'synced' => $synced,
    ];
}

function vip_funnel_upsert_public_lead(array $state, array $fields = [], array $meta = []): int {
    if(!vip_funnel_demo_schema_is_ready()) {
        return 0;
    }

    vip_funnel_ensure_runtime_schema();
    $owner_user_id = (int) ($state['user_id'] ?? 0);
    $vip_funnel_id = vip_funnel_resolve_state_funnel_id($state);
    $visitor_key = trim((string) ($state['viewer_key'] ?? ''));
    $lead_email = trim((string) ($fields['email'] ?? ''));
    $lead_phone = trim((string) ($fields['phone'] ?? ''));
    $lead_name = trim((string) ($fields['name'] ?? ''));
    $full_name = trim((string) ($fields['full_name'] ?? ($lead_name !== '' ? $lead_name : '')));
    $payload_state = vip_funnel_to_array($state['payload'] ?? []);
    $funnel_name = trim((string) ($payload_state['funnel']['name'] ?? ''));
    $funnel_slug = trim((string) ($state['slug'] ?? ($payload_state['funnel']['slug'] ?? '')));
    $step_title = trim((string) ($state['active']['title'] ?? ''));
    $page_key = trim((string) ($state['page_key'] ?? ''));
    $page_role = trim((string) ($state['page_role'] ?? 'landing'));
    $selection_value = trim((string) ($meta['selection'] ?? ''));
    $qualification = vip_funnel_calculate_lead_qualification($fields, $meta, $state);
    $follow_up_automation = vip_funnel_get_follow_up_playbook((string) ($qualification['segment'] ?? 'warm'), [
        'name' => (string) ($full_name !== '' ? $full_name : $lead_name),
    ]);

    $query = db()->where('owner_user_id', $owner_user_id);
    if($lead_email !== '') {
        $query->where('lead_email', $lead_email);
    } elseif($lead_phone !== '') {
        $query->where('lead_phone', $lead_phone);
    } elseif($visitor_key !== '') {
        $query->where('visitor_key', $visitor_key);
    }

    $existing = ($lead_email !== '' || $lead_phone !== '' || $visitor_key !== '') ? $query->orderBy('vip_lead_id', 'DESC')->getOne('vip_leads') : null;
    $payload = array_merge(vip_funnel_to_array($existing->payload ?? []), [
        'page_key' => (string) ($state['page_key'] ?? ''),
        'variant_key' => (string) ($state['variant_key'] ?? 'a'),
        'captured_fields' => $fields,
        'meta' => $meta,
        'funnel_context' => [
            'vip_funnel_id' => $vip_funnel_id,
            'funnel_name' => $funnel_name,
            'funnel_slug' => $funnel_slug,
            'page_key' => $page_key,
            'page_role' => $page_role,
            'step_key' => $page_key,
            'step_title' => $step_title,
            'page_url' => trim((string) ($state['canonical_url'] ?? '')),
            'visitor_key' => $visitor_key,
            'variant_key' => (string) ($state['variant_key'] ?? 'a'),
        ],
        'contact_origin' => [
            'source_key' => 'vip_funnel',
            'source_label' => l('vip_funnel.contacts.source.vip_funnel'),
        ],
        'owner_profile' => vip_funnel_get_owner_contact_profile($owner_user_id),
        'qualification' => $qualification,
        'follow_up_automation' => $follow_up_automation,
    ]);

    if($existing) {
        db()->where('vip_lead_id', (int) $existing->vip_lead_id)->update('vip_leads', [
            'vip_funnel_id' => $vip_funnel_id > 0 ? $vip_funnel_id : ($existing->vip_funnel_id ?? null),
            'source_step_key' => $page_key !== '' ? $page_key : ($existing->source_step_key ?? null),
            'selection_value' => $selection_value !== '' ? $selection_value : ($existing->selection_value ?? null),
            'visitor_key' => $visitor_key !== '' ? $visitor_key : ($existing->visitor_key ?? null),
            'lead_name' => $lead_name !== '' ? $lead_name : ($existing->lead_name ?? null),
            'full_name' => $full_name !== '' ? $full_name : ($existing->full_name ?? null),
            'lead_email' => $lead_email !== '' ? $lead_email : ($existing->lead_email ?? null),
            'lead_phone' => $lead_phone !== '' ? $lead_phone : ($existing->lead_phone ?? null),
            'interest_type' => (string) ($qualification['interest_type'] ?? ($existing->interest_type ?? 'funnel')),
            'business_readiness' => (string) ($qualification['readiness_key'] ?? ($existing->business_readiness ?? 'warm')),
            'product_goal' => (string) (($qualification['product_goal'] ?? '') ?: ($selection_value !== '' ? $selection_value : ($existing->product_goal ?? ''))),
            'payload' => vip_funnel_json_encode($payload),
            'last_datetime' => get_date(),
        ]);

        vip_funnel_sync_contact_data_from_lead_id((int) $existing->vip_lead_id, [
            'demo_status' => (string) ($existing->demo_status ?? 'captured'),
        ]);

        return (int) $existing->vip_lead_id;
    }

    $lead_id = (int) db()->insert('vip_leads', [
        'user_id' => $owner_user_id,
        'owner_user_id' => $owner_user_id,
        'vip_funnel_id' => $vip_funnel_id > 0 ? $vip_funnel_id : null,
        'source_step_key' => $page_key !== '' ? $page_key : null,
        'selection_value' => $selection_value !== '' ? $selection_value : null,
        'visitor_key' => $visitor_key !== '' ? $visitor_key : null,
        'lead_name' => $lead_name !== '' ? $lead_name : null,
        'full_name' => $full_name !== '' ? $full_name : null,
        'lead_email' => $lead_email !== '' ? $lead_email : null,
        'lead_phone' => $lead_phone !== '' ? $lead_phone : null,
        'source' => 'vip_funnel_public',
        'interest_type' => (string) ($qualification['interest_type'] ?? 'business_interest'),
        'business_readiness' => (string) ($qualification['readiness_key'] ?? 'warm'),
        'product_goal' => (string) (($qualification['product_goal'] ?? '') ?: trim((string) ($meta['selection'] ?? ''))),
        'demo_status' => 'captured',
        'payload' => vip_funnel_json_encode($payload),
        'datetime' => get_date(),
        'last_datetime' => get_date(),
    ]);

    if($lead_id > 0) {
        vip_funnel_sync_contact_data_from_lead_id($lead_id, [
            'demo_status' => 'captured',
        ]);
        vip_funnel_send_owner_email_notification($owner_user_id, 'lead_created', [
            'lead_name' => $full_name !== '' ? $full_name : $lead_name,
            'lead_email' => $lead_email,
            'lead_phone' => $lead_phone,
            'funnel_name' => $funnel_name,
            'page_url' => trim((string) ($state['canonical_url'] ?? '')),
            'source_label' => $funnel_name !== '' ? $funnel_name : 'VIP Funnel 2.0',
            'dashboard_url' => url('vip-funnel-studio'),
        ]);
    }

    return $lead_id;
}

function vip_funnel_public_submission_requests_demo(array $state, array $fields = [], string $selection = '', string $target_step_id = '', array $radio_answers = []): bool {
    $email = trim((string) ($fields['email'] ?? ''));

    if($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $haystack_parts = [
        (string) ($state['current_step_id'] ?? ''),
        (string) ($state['page_key'] ?? ''),
        (string) ($state['active']['title'] ?? ''),
        $selection,
        $target_step_id,
    ];

    foreach($radio_answers as $answer) {
        $answer = vip_funnel_to_array($answer);
        $haystack_parts[] = (string) ($answer['value'] ?? '');
        $haystack_parts[] = (string) ($answer['label'] ?? '');
        $haystack_parts[] = (string) ($answer['target_step_id'] ?? '');
    }

    $haystack = mb_strtolower(implode(' ', $haystack_parts));

    return str_contains($haystack, 'demo');
}

function vip_funnel_resolve_public_qualification_target(array $state, array $radio_answers = []): string {
    $current_step_id = (string) (($state['current_step_id'] ?? '') ?: ($state['page_key'] ?? ''));

    if($current_step_id !== 'qualification_form') {
        return '';
    }

    $answers_by_block = [];
    foreach($radio_answers as $answer) {
        $answer = vip_funnel_to_array($answer);
        $block_id = (string) ($answer['block_id'] ?? '');
        if($block_id !== '') {
            $answers_by_block[$block_id] = (string) ($answer['value'] ?? '');
        }
    }

    $goal = (string) ($answers_by_block['qualification_goal'] ?? '');
    $time = (string) ($answers_by_block['qualification_time'] ?? '');
    $investment = (string) ($answers_by_block['qualification_investment'] ?? '');

    if($goal === 'product_discount') {
        return 'product_gateway';
    }

    if($investment === 'trebam_prvo_vidjeti_sustav') {
        return 'fcc_demo_preview';
    }

    if($investment === 'ready_360_call') {
        return 'mentor_call_request';
    }

    if($goal === 'samo_istrazujem' || $time === 'time_no_capacity' || $investment === 'not_ready') {
        return 'not_ready_nurture';
    }

    if($investment === 'ready_360_now') {
        return 'start_package_offer';
    }

    return '';
}

function vip_funnel_resolve_public_qualification_selection(array $state, array $radio_answers = []): string {
    $current_step_id = (string) (($state['current_step_id'] ?? '') ?: ($state['page_key'] ?? ''));

    if($current_step_id !== 'qualification_form') {
        return '';
    }

    $answers_by_block = [];
    foreach($radio_answers as $answer) {
        $answer = vip_funnel_to_array($answer);
        $block_id = (string) ($answer['block_id'] ?? '');
        if($block_id !== '') {
            $answers_by_block[$block_id] = (string) ($answer['value'] ?? '');
        }
    }

    $goal = (string) ($answers_by_block['qualification_goal'] ?? '');
    $time = (string) ($answers_by_block['qualification_time'] ?? '');
    $investment = (string) ($answers_by_block['qualification_investment'] ?? '');

    if($goal === 'product_discount') {
        return 'product_discount';
    }

    if($goal === 'samo_istrazujem') {
        return 'samo_istrazujem';
    }

    if($time === 'time_no_capacity') {
        return 'time_no_capacity';
    }

    return $investment;
}

function vip_funnel_process_public_submission(array $state, array $post = []): array {
    $post = vip_funnel_to_array($post);
    $action = input_clean((string) ($post['vf_action'] ?? 'submit_next'), 32);
    $target_step_id = trim(input_clean((string) ($post['vf_target_step_id'] ?? ''), 128));
    $external_url = trim(input_clean((string) ($post['vf_external_url'] ?? ''), 2048));
    $selection = trim(input_clean((string) ($post['vf_selection'] ?? ''), 120));
    $submitted_block_id = trim(input_clean((string) ($post['vf_block_id'] ?? ''), 120));

    $blocks = (array) ($state['blocks'] ?? []);
    $field_map = vip_funnel_get_public_capture_field_map($blocks);
    $radio_map = vip_funnel_get_public_radio_field_map($blocks);
    $fields = [];
    $errors = [];
    $radio_answers = [];
    $routing_answer = null;

    foreach($field_map as $block_id => $field_block) {
        $value = trim((string) ($post['vf_field_' . $block_id] ?? ''));
        $type = (string) ($field_block['type'] ?? '');

        if(!empty($field_block['required']) && $value === '') {
            $errors[] = trim((string) ($field_block['title'] ?? 'Polje'));
            continue;
        }

        if($type === 'email_field' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = l('global.email');
            continue;
        }

        if($type === 'name_field') {
            $fields['name'] = $value;
        }

        if($type === 'full_name_field') {
            $fields['full_name'] = $value;
            if($value !== '' && empty($fields['name'])) {
                $fields['name'] = trim((string) preg_split('/\s+/', $value)[0]);
            }
        }

        if($type === 'email_field') {
            $fields['email'] = $value;
        }

        if($type === 'phone_field') {
            $fields['phone'] = $value;
        }

        if($type === 'text_field') {
            $field_key = trim((string) ($field_block['field_key'] ?? ''));
            if($field_key === '') {
                $field_key = vip_funnel_normalize_signal_value((string) $block_id);
            }
            if($field_key !== '') {
                $fields[$field_key] = $value;
            }
        }
    }

    foreach($radio_map as $block_id => $radio_block) {
        $selected_value = trim((string) ($post['vf_radio_' . $block_id] ?? ''));

        if(!empty($radio_block['required']) && $selected_value === '') {
            $errors[] = trim((string) ($radio_block['title'] ?? 'Odabir'));
            continue;
        }

        if($selected_value === '') {
            continue;
        }

        $matched_option = null;
        foreach((array) ($radio_block['options'] ?? []) as $option) {
            $option = vip_funnel_to_array($option);
            if((string) ($option['value'] ?? '') === $selected_value) {
                $matched_option = $option;
                break;
            }
        }

        if(!$matched_option) {
            continue;
        }

        $answer = [
            'block_id' => $block_id,
            'question' => (string) ($radio_block['title'] ?? ''),
            'label' => (string) ($matched_option['label'] ?? $selected_value),
            'value' => $selected_value,
            'action' => (string) ($matched_option['action'] ?? 'goto_step'),
            'target_step_id' => (string) ($matched_option['target_step_id'] ?? ''),
            'external_url' => (string) ($matched_option['external_url'] ?? ''),
            'route_on_submit' => !empty($radio_block['route_on_submit']),
        ];

        $radio_answers[] = $answer;

        if(!$routing_answer && !empty($answer['route_on_submit'])) {
            $routing_answer = $answer;
        }
    }

    if(!empty($errors)) {
        return [
            'success' => false,
            'message' => sprintf(l('vip_funnel.public.alert.required_fields'), implode(', ', array_unique($errors))),
        ];
    }

    $has_capture = !empty(array_filter($fields, static function($value) {
        return trim((string) $value) !== '';
    }));
    $qualification_target_step_id = vip_funnel_resolve_public_qualification_target($state, $radio_answers);
    $qualification_selection = vip_funnel_resolve_public_qualification_selection($state, $radio_answers);
    $effective_selection = $selection !== '' ? $selection : ($qualification_selection !== '' ? $qualification_selection : (string) ($routing_answer['value'] ?? ($radio_answers[0]['value'] ?? '')));
    $lead_meta = [
        'selection' => $effective_selection,
        'block_id' => $submitted_block_id,
        'radio_answers' => $radio_answers,
    ];
    $vip_lead_id = ($has_capture || !empty($radio_answers)) ? vip_funnel_upsert_public_lead($state, $fields, $lead_meta) : 0;
    $run_id = vip_funnel_get_or_create_public_run($state, $vip_lead_id, [
        'selection' => $effective_selection,
        'radio_answers' => $radio_answers,
    ]);
    vip_funnel_log_public_event($state, 'submit', $effective_selection !== '' ? $effective_selection : $action, ['block_id' => $submitted_block_id, 'selection' => $effective_selection, 'radio_answers' => $radio_answers], $vip_lead_id, $run_id);

    if($has_capture) {
        vip_funnel_log_public_event($state, 'lead_capture', $effective_selection !== '' ? $effective_selection : 'capture', ['block_id' => $submitted_block_id, 'radio_answers' => $radio_answers], $vip_lead_id, $run_id);
    }

    if($vip_lead_id > 0 && vip_funnel_public_submission_requests_demo($state, $fields, $effective_selection, $target_step_id, $radio_answers)) {
        $payload_state = vip_funnel_to_array($state['payload'] ?? []);
        $demo_notes = ['Automatski demo zahtjev iz javnog Funnel 2.0 obrasca.'];
        $demo_focus = trim((string) ($fields['demo_focus'] ?? ''));

        if($demo_focus !== '') {
            $demo_notes[] = 'Što osoba želi vidjeti u demu: ' . $demo_focus;
        }

        if(!empty($radio_answers[0]['label'])) {
            $demo_notes[] = 'Razlog za demo: ' . (string) $radio_answers[0]['label'];
        }

        $demo_result = vip_funnel_demo_create_request(null, [
            'existing_vip_lead_id' => $vip_lead_id,
            'lead_name' => trim((string) ($fields['full_name'] ?? ($fields['name'] ?? ''))),
            'lead_email' => trim((string) ($fields['email'] ?? '')),
            'lead_phone' => trim((string) ($fields['phone'] ?? '')),
            'owner_user_id' => (int) ($state['user_id'] ?? 0),
            'source' => 'vip_funnel_public',
            'interest_type' => 'demo',
            'business_readiness' => 'curious',
            'product_goal' => $effective_selection,
            'notes' => implode("\n", $demo_notes),
            'funnel_context' => [
                'vip_funnel_id' => vip_funnel_resolve_state_funnel_id($state),
                'funnel_name' => (string) ($payload_state['funnel']['name'] ?? 'VIP Funnel 2.0'),
                'funnel_slug' => (string) ($state['slug'] ?? ($payload_state['funnel']['slug'] ?? '')),
                'page_key' => (string) ($state['page_key'] ?? ''),
                'page_url' => trim((string) ($state['canonical_url'] ?? '')),
                'selection' => $effective_selection,
                'demo_focus' => $demo_focus,
                'radio_answers' => $radio_answers,
            ],
        ]);

        if(empty($demo_result['success'])) {
            return [
                'success' => false,
                'message' => (string) ($demo_result['message'] ?? l('vip_funnel.demo.alert.request_failed')),
                'vip_lead_id' => $vip_lead_id,
                'run_id' => $run_id,
            ];
        }

        vip_funnel_log_public_event($state, 'demo_request', $effective_selection !== '' ? $effective_selection : 'demo_request', ['vip_demo_account_id' => (int) ($demo_result['vip_demo_account_id'] ?? 0)], $vip_lead_id, $run_id);
    }

    if($qualification_target_step_id !== '' && $external_url === '' && $action !== 'submit_stay') {
        $target_step_id = $qualification_target_step_id;
    }

    if($routing_answer && $external_url === '' && $target_step_id === '' && $action !== 'submit_stay') {
        if(($routing_answer['action'] ?? '') === 'external_url' && !empty($routing_answer['external_url'])) {
            $external_url = (string) $routing_answer['external_url'];
            $action = 'external_url';
        } elseif(!empty($routing_answer['target_step_id'])) {
            $target_step_id = (string) $routing_answer['target_step_id'];
        }
    }

    if($external_url !== '' && $action === 'external_url') {
        return ['success' => true, 'redirect_url' => $external_url];
    }

    $slug = (string) ($state['slug'] ?? 'vip-funnel-2-0');
    $redirect_url = $target_step_id !== ''
        ? vip_funnel_get_public_funnel_url((int) ($state['user_id'] ?? 0), $slug, $target_step_id)
        : (($action === 'submit_stay') ? ($state['canonical_url'] ?? vip_funnel_get_public_funnel_url((int) ($state['user_id'] ?? 0), $slug)) : ($state['entry_url'] ?? vip_funnel_get_public_funnel_url((int) ($state['user_id'] ?? 0), $slug)));

    if($effective_selection !== '' && $redirect_url !== '' && $action !== 'external_url') {
        $redirect_url = vip_funnel_append_url_query_param($redirect_url, 'vfsel', $effective_selection);
    }

    if($target_step_id !== '') {
        vip_funnel_log_public_event($state, 'advance', $target_step_id, ['block_id' => $submitted_block_id, 'selection' => $effective_selection, 'radio_answers' => $radio_answers], $vip_lead_id, $run_id);
    }

    return [
        'success' => true,
        'redirect_url' => $redirect_url,
        'vip_lead_id' => $vip_lead_id,
        'run_id' => $run_id,
    ];
}

function vip_funnel_get_public_hub_render_data(int $user_id = 0, $block_settings = null): array {
    $block_settings = vip_funnel_to_array($block_settings);
    $selected_funnel_id = (int) ($block_settings['vip_funnel_id'] ?? 0);
    $public_payload = vip_funnel_get_public_payload_for_user($user_id, '', $selected_funnel_id);

    if(!$public_payload && $selected_funnel_id > 0) {
        $public_payload = vip_funnel_get_public_payload_for_user($user_id);
    }

    $payload = $public_payload ?: vip_funnel_get_studio_seed_payload((object) ['user_id' => $user_id]);
    $payload = vip_funnel_normalize_studio_payload($payload, (object) ['user_id' => $user_id]);
    $paths = array_values(array_filter((array) ($payload['paths'] ?? []), static function($path) {
        return !isset($path['is_enabled']) || !empty($path['is_enabled']);
    }));
    $first_step_id = $public_payload ? vip_funnel_get_public_entry_step_id($payload) : '';
    $primary_url = $public_payload && $first_step_id !== '' ? vip_funnel_get_public_funnel_url($user_id, (string) ($payload['funnel']['slug'] ?? 'vip-funnel-2-0')) : '';

    return [
        'kicker' => trim((string) ($block_settings['kicker'] ?? ($payload['overview']['eyebrow'] ?? 'Funnel 2.0'))),
        'title' => trim((string) ($block_settings['title'] ?? ($payload['overview']['headline'] ?? 'VIP Funnel 2.0'))),
        'subtitle' => trim((string) ($block_settings['subtitle'] ?? ($payload['overview']['subheadline'] ?? ''))),
        'primary_cta_text' => trim((string) ($block_settings['primary_cta_text'] ?? ($payload['overview']['primary_cta'] ?? 'Otvori funnel'))),
        'primary_url' => $primary_url,
        'secondary_cta_text' => trim((string) ($block_settings['secondary_cta_text'] ?? ($payload['overview']['secondary_cta'] ?? ''))),
        'secondary_url' => trim((string) ($block_settings['secondary_url'] ?? '')),
        'show_paths' => array_key_exists('show_paths', $block_settings) ? !empty($block_settings['show_paths']) : true,
        'paths' => array_slice($paths, 0, 3),
    ];
}

function vip_funnel_get_user_funnel_select_options(int $user_id = 0): array {
    $options = [];

    if($user_id <= 0 || !vip_funnel_studio_schema_is_ready()) {
        return $options;
    }

    foreach(vip_funnel_studio_get_funnel_rows($user_id) as $row) {
        $options[(int) ($row->vip_funnel_id ?? 0)] = [
            'id' => (int) ($row->vip_funnel_id ?? 0),
            'name' => (string) ($row->name ?? 'VIP Funnel 2.0'),
            'slug' => (string) ($row->slug ?? ''),
            'url' => vip_funnel_get_public_funnel_url($user_id, (string) ($row->slug ?? 'vip-funnel-2-0')),
        ];
    }

    return array_filter($options, static function($option) {
        return !empty($option['id']);
    });
}

function vip_funnel_has_table(string $table): bool {
    if(function_exists('fc_table_exists')) {
        return fc_table_exists($table);
    }

    $table_sql = database()->real_escape_string($table);
    $result = database()->query("SHOW TABLES LIKE '{$table_sql}'");

    return (bool) ($result && $result->num_rows);
}

function vip_funnel_get_public_qualification_signal_map(string $period_start_datetime = '', string $period_end_datetime = ''): array {
    $map = [];

    if($period_start_datetime === '') {
        return $map;
    }

    vip_funnel_ensure_runtime_schema();
    $period_start_datetime = database()->real_escape_string($period_start_datetime);
    $period_end_datetime = trim($period_end_datetime) !== '' ? database()->real_escape_string($period_end_datetime) : '';
    $range_sql = "`datetime` >= '{$period_start_datetime}'";

    if($period_end_datetime !== '') {
        $range_sql .= " AND `datetime` < '{$period_end_datetime}'";
    }

    if(vip_funnel_has_table('vip_leads')) {
        $contacts_result = database()->query("SELECT
                `owner_user_id` AS `user_id`,
                COUNT(*) AS `total`
            FROM `vip_leads`
            WHERE `source` = 'vip_funnel_public'
              AND {$range_sql}
            GROUP BY `owner_user_id`");

        while($contacts_result && ($row = $contacts_result->fetch_object())) {
            $user_id = (int) ($row->user_id ?? 0);

            if($user_id <= 0) {
                continue;
            }

            if(!isset($map[$user_id])) {
                $map[$user_id] = [
                    'funnel_contacts' => 0,
                    'funnel_contact_signal' => 0,
                    'funnel_shop_clicks' => 0,
                    'total' => 0,
                ];
            }

            $map[$user_id]['funnel_contacts'] = (int) ($row->total ?? 0);
        }
    }

    if(vip_funnel_has_table('vip_funnel_events')) {
        $shop_clicks_result = database()->query("SELECT
                `user_id`,
                COUNT(DISTINCT CASE
                    WHEN COALESCE(`visitor_key`, '') <> '' THEN CONCAT(`visitor_key`, '|', COALESCE(NULLIF(`block_id`, ''), 'shop'))
                    ELSE CONCAT('event_', `vip_funnel_event_id`)
                END) AS `total`
            FROM `vip_funnel_events`
            WHERE `event_type` = 'cta_click'
              AND JSON_UNQUOTE(JSON_EXTRACT(`meta`, '$.signal_key')) = 'forever_shop'
              AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`meta`, '$.external_url')), '') NOT LIKE '%blog-click%'
              AND {$range_sql}
            GROUP BY `user_id`");

        while($shop_clicks_result && ($row = $shop_clicks_result->fetch_object())) {
            $user_id = (int) ($row->user_id ?? 0);

            if($user_id <= 0) {
                continue;
            }

            if(!isset($map[$user_id])) {
                $map[$user_id] = [
                    'funnel_contacts' => 0,
                    'funnel_contact_signal' => 0,
                    'funnel_shop_clicks' => 0,
                    'total' => 0,
                ];
            }

            $map[$user_id]['funnel_shop_clicks'] = (int) ($row->total ?? 0);
        }
    }

    foreach($map as &$row) {
        $row['funnel_contacts'] = (int) ($row['funnel_contacts'] ?? 0);
        $row['funnel_contact_signal'] = $row['funnel_contacts'] * 3;
        $row['funnel_shop_clicks'] = (int) ($row['funnel_shop_clicks'] ?? 0);
        $row['total'] = $row['funnel_contact_signal'] + $row['funnel_shop_clicks'];
    }
    unset($row);

    return $map;
}

function vip_funnel_get_public_qualification_signal_payload(int $user_id = 0, string $period_start_datetime = '', string $period_end_datetime = ''): array {
    $map = vip_funnel_get_public_qualification_signal_map($period_start_datetime, $period_end_datetime);

    return $map[$user_id] ?? [
        'funnel_contacts' => 0,
        'funnel_contact_signal' => 0,
        'funnel_shop_clicks' => 0,
        'total' => 0,
    ];
}

function vip_funnel_demo_schema_is_ready(): bool {
    foreach(['vip_leads', 'vip_demo_accounts', 'vip_demo_events'] as $table) {
        if(!vip_funnel_has_table($table)) {
            return false;
        }
    }

    return true;
}

function vip_funnel_get_pilot_owner_user_ids($user = null): array {
    $settings = vip_funnel_get_settings();
    $user_ids = vip_funnel_parse_user_ids($settings->pilot_allowed_user_ids ?? []);
    $current_user_id = (int) ($user->user_id ?? 0);

    if($current_user_id > 0 && !in_array($current_user_id, $user_ids, true)) {
        $user_ids[] = $current_user_id;
    }

    sort($user_ids);

    return array_values(array_unique(array_filter($user_ids)));
}

function vip_funnel_get_pilot_users($user = null): array {
    $user_ids = vip_funnel_get_pilot_owner_user_ids($user);

    if(empty($user_ids)) {
        return [];
    }

    $ids_sql = implode(',', array_map('intval', $user_ids));
    $result = database()->query("SELECT `user_id`, `name`, `email` FROM `users` WHERE `user_id` IN ({$ids_sql}) ORDER BY FIELD(`user_id`, {$ids_sql})");
    $users = [];

    if($result) {
        while($row = $result->fetch_object()) {
            $users[] = $row;
        }
    }

    return $users;
}

function vip_funnel_demo_get_owner_options($user = null): array {
    $options = [
        0 => l('vip_funnel.demo.owner.shared'),
    ];

    foreach(vip_funnel_get_pilot_users($user) as $pilot_user) {
        $options[(int) $pilot_user->user_id] = trim(($pilot_user->name ?? '') . ' <' . ($pilot_user->email ?? '') . '>');
    }

    return $options;
}

function vip_funnel_demo_get_default_owner_user_id($user = null): int {
    $current_user_id = (int) ($user->user_id ?? 0);
    $owner_options = vip_funnel_demo_get_owner_options($user);

    return array_key_exists($current_user_id, $owner_options) ? $current_user_id : 0;
}

function vip_funnel_demo_get_request_form_defaults($user = null): array {
    return [
        'lead_name' => '',
        'lead_email' => '',
        'demo_login_email' => '',
        'lead_phone' => '',
        'forever_id' => '',
        'interest_type' => 'demo',
        'business_readiness' => 'curious',
        'product_goal' => '',
        'owner_user_id' => vip_funnel_demo_get_default_owner_user_id($user),
        'source' => 'manual_pilot',
        'notes' => '',
    ];
}

function vip_funnel_demo_validate_optional_forever_id(string $forever_id): bool {
    $forever_id = trim($forever_id);

    if($forever_id === '') {
        return true;
    }

    return mb_strlen($forever_id) === 12;
}

function vip_funnel_demo_get_interest_options(): array {
    return [
        'demo' => l('vip_funnel.demo.interest.demo'),
        'business' => l('vip_funnel.demo.interest.business'),
        'product' => l('vip_funnel.demo.interest.product'),
    ];
}

function vip_funnel_demo_get_readiness_options(): array {
    return [
        'new' => l('vip_funnel.demo.readiness.new'),
        'curious' => l('vip_funnel.demo.readiness.curious'),
        'considering' => l('vip_funnel.demo.readiness.considering'),
        'ready_now' => l('vip_funnel.demo.readiness.ready_now'),
    ];
}

function vip_funnel_demo_get_status_labels(): array {
    return [
        'requested' => l('vip_funnel.demo.status.requested'),
        'approved' => l('vip_funnel.demo.status.approved'),
        'active' => l('vip_funnel.demo.status.active'),
        'paused' => l('vip_funnel.demo.status.paused'),
        'expiring' => l('vip_funnel.demo.status.expiring'),
        'expired' => l('vip_funnel.demo.status.expired'),
        'rejected' => l('vip_funnel.demo.status.rejected'),
        'closed' => l('vip_funnel.demo.status.closed'),
        'converted' => l('vip_funnel.demo.status.converted'),
    ];
}

function vip_funnel_demo_get_event_labels(): array {
    return [
        'requested' => l('vip_funnel.demo.event.requested'),
        'approved' => l('vip_funnel.demo.event.approved'),
        'activated' => l('vip_funnel.demo.event.activated'),
        'provisioned' => l('vip_funnel.demo.event.provisioned'),
        'paused' => l('vip_funnel.demo.event.paused'),
        'expired' => l('vip_funnel.demo.event.expired'),
        'extended' => l('vip_funnel.demo.event.extended'),
        'rejected' => l('vip_funnel.demo.event.rejected'),
        'closed' => l('vip_funnel.demo.event.closed'),
        'converted' => l('vip_funnel.demo.event.converted'),
    ];
}

function vip_funnel_demo_get_status_badge_class(string $status): string {
    $map = [
        'requested' => 'warning',
        'approved' => 'info',
        'active' => 'success',
        'paused' => 'secondary',
        'expiring' => 'danger',
        'expired' => 'dark',
        'rejected' => 'secondary',
        'closed' => 'secondary',
        'converted' => 'primary',
    ];

    return $map[$status] ?? 'secondary';
}

function vip_funnel_demo_normalize_owner_user_id($value, $user = null): int {
    $owner_user_id = (int) $value;
    $owner_options = vip_funnel_demo_get_owner_options($user);

    return array_key_exists($owner_user_id, $owner_options) ? $owner_user_id : vip_funnel_demo_get_default_owner_user_id($user);
}

function vip_funnel_demo_sync_statuses(): void {
    static $is_synced = false;

    if($is_synced || !vip_funnel_demo_schema_is_ready()) {
        return;
    }

    $is_synced = true;

    $now = get_date();
    $now_sql = db()->escape($now);
    $soon_sql = db()->escape((new \DateTimeImmutable($now))->modify('+1 day')->format('Y-m-d H:i:s'));
    $expired_ids = [];
    $expired_result = database()->query("SELECT `vip_demo_account_id` FROM `vip_demo_accounts` WHERE `status` IN ('active', 'expiring', 'paused') AND `expires_at` IS NOT NULL AND `expires_at` < '{$now_sql}'");

    if($expired_result) {
        while($expired_row = $expired_result->fetch_object()) {
            $expired_ids[] = (int) ($expired_row->vip_demo_account_id ?? 0);
        }
    }

    database()->query("UPDATE `vip_demo_accounts` SET `status` = 'expired', `last_datetime` = '{$now_sql}' WHERE `status` IN ('active', 'expiring', 'paused') AND `expires_at` IS NOT NULL AND `expires_at` < '{$now_sql}'");
    database()->query("UPDATE `vip_demo_accounts` SET `status` = 'expiring', `last_datetime` = '{$now_sql}' WHERE `status` = 'active' AND `expires_at` IS NOT NULL AND `expires_at` BETWEEN '{$now_sql}' AND '{$soon_sql}'");
    database()->query("UPDATE `vip_demo_accounts` SET `status` = 'active' WHERE `status` = 'expiring' AND `expires_at` IS NOT NULL AND `expires_at` > '{$soon_sql}'");
    database()->query("UPDATE `vip_leads` `l` INNER JOIN `vip_demo_accounts` `d` ON `d`.`vip_lead_id` = `l`.`vip_lead_id` SET `l`.`demo_status` = `d`.`status`, `l`.`last_datetime` = `d`.`last_datetime`");

    foreach(array_values(array_unique(array_filter($expired_ids))) as $expired_account_id) {
        $expired_account = vip_funnel_demo_get_account_context((int) $expired_account_id);

        if(!$expired_account) {
            continue;
        }

        vip_funnel_demo_sync_demo_user_access($expired_account, 'expired');
        vip_funnel_demo_log_event((int) $expired_account_id, 0, 'expired', [
            'expires_at' => (string) ($expired_account->expires_at ?? ''),
        ]);
    }
}

function vip_funnel_demo_log_event(int $account_id, int $actor_user_id, string $event_key, array $payload = []): void {
    if($account_id <= 0 || !vip_funnel_demo_schema_is_ready()) {
        return;
    }

    db()->insert('vip_demo_events', [
        'vip_demo_account_id' => $account_id,
        'actor_user_id' => $actor_user_id > 0 ? $actor_user_id : null,
        'event_key' => input_clean($event_key, 64),
        'payload' => vip_funnel_json_encode($payload),
        'datetime' => get_date(),
    ]);
}

function vip_funnel_demo_get_available_actions(string $status): array {
    switch($status) {
        case 'requested':
            return ['approve', 'reject'];

        case 'approved':
            return ['activate', 'reject'];

        case 'active':
        case 'expiring':
            return ['pause', 'close', 'convert'];

        case 'paused':
            return ['activate', 'close'];

        case 'expired':
            return ['close', 'convert'];

        default:
            return [];
    }
}

function vip_funnel_demo_hydrate_row($row): \stdClass {
    $row = is_object($row) ? clone $row : (object) vip_funnel_to_array($row);
    $lead_payload = vip_funnel_to_array($row->lead_payload ?? []);
    $settings_payload = vip_funnel_to_array($row->settings ?? []);
    $status = (string) ($row->status ?? 'requested');
    $status_labels = vip_funnel_demo_get_status_labels();
    $interest_options = vip_funnel_demo_get_interest_options();
    $readiness_options = vip_funnel_demo_get_readiness_options();

    $row->lead_name = trim((string) ($lead_payload['lead_name'] ?? '')) ?: l('vip_funnel.demo.contact_missing');
    $row->lead_email = trim((string) ($lead_payload['lead_email'] ?? ''));
    $row->requested_demo_login_email = trim((string) ($lead_payload['demo_login_email'] ?? ''));
    $row->lead_phone = trim((string) ($lead_payload['lead_phone'] ?? ''));
    $row->forever_id = trim((string) ($lead_payload['forever_id'] ?? ''));
    $row->lead_notes = trim((string) ($lead_payload['notes'] ?? ''));
    $row->lead_payload = $lead_payload;
    $row->settings_payload = $settings_payload;
    $row->status = $status;
    $row->status_label = $status_labels[$status] ?? ucfirst($status);
    $row->status_badge_class = vip_funnel_demo_get_status_badge_class($status);
    $row->owner_label = !empty($row->owner_name) ? (string) $row->owner_name : l('vip_funnel.demo.owner.shared');
    $row->requested_days = max(1, min(3, (int) ($settings_payload['requested_days'] ?? vip_funnel_get_settings()->default_demo_days ?? 3)));
    $row->provisioning_status = (string) ($settings_payload['provisioning_status'] ?? 'pending');
    $row->interest_label = $interest_options[(string) ($row->interest_type ?? '')] ?? (string) ($row->interest_type ?? '');
    $row->business_readiness_label = $readiness_options[(string) ($row->business_readiness ?? '')] ?? (string) ($row->business_readiness ?? '');
    $row->workspace_url = trim((string) ($settings_payload['workspace_url'] ?? ''));
    $row->login_email = trim((string) ($settings_payload['login_email'] ?? ($row->demo_user_email ?? $row->requested_demo_login_email ?? '')));
    $row->temporary_password = trim((string) ($settings_payload['temporary_password'] ?? ''));
    $row->reset_password_url = trim((string) ($settings_payload['reset_password_url'] ?? ''));
    $row->access_state = trim((string) ($settings_payload['access_state'] ?? ''));
    $row->available_actions = vip_funnel_demo_get_available_actions($status);
    $row->datetime_display = !empty($row->datetime) ? \Altum\Date::get($row->datetime, 2) : l('global.none');
    $row->datetime_timeago = !empty($row->datetime) ? \Altum\Date::get_timeago($row->datetime) : '';
    $row->approved_at_display = !empty($row->approved_at) ? \Altum\Date::get($row->approved_at, 2) : '';
    $row->approved_at_timeago = !empty($row->approved_at) ? \Altum\Date::get_timeago($row->approved_at) : '';
    $row->expires_at_display = !empty($row->expires_at) ? \Altum\Date::get($row->expires_at, 2) : '';
    $row->expires_at_timeago = !empty($row->expires_at) ? \Altum\Date::get_timeago($row->expires_at) : '';

    return $row;
}

function vip_funnel_demo_get_account(int $account_id): ?\stdClass {
    if($account_id <= 0 || !vip_funnel_demo_schema_is_ready()) {
        return null;
    }

    return db()->where('vip_demo_account_id', $account_id)->getOne('vip_demo_accounts');
}

function vip_funnel_demo_get_account_context(int $account_id): ?\stdClass {
    if($account_id <= 0 || !vip_funnel_demo_schema_is_ready()) {
        return null;
    }

    $result = database()->query("
        SELECT
            `vip_demo_accounts`.*,
            `vip_leads`.`source`,
            `vip_leads`.`interest_type`,
            `vip_leads`.`business_readiness`,
            `vip_leads`.`product_goal`,
            `vip_leads`.`payload` AS `lead_payload`,
            `owners`.`name` AS `owner_name`,
            `owners`.`email` AS `owner_email`,
            `owners`.`timezone` AS `owner_timezone`,
            `owners`.`language` AS `owner_language`,
            `owners`.`plan_settings` AS `owner_plan_settings`,
            `approvers`.`name` AS `approved_by_name`,
            `demo_users`.`email` AS `demo_user_email`,
            `demo_users`.`name` AS `demo_user_name`
        FROM
            `vip_demo_accounts`
        LEFT JOIN `vip_leads` ON `vip_leads`.`vip_lead_id` = `vip_demo_accounts`.`vip_lead_id`
        LEFT JOIN `users` AS `owners` ON `owners`.`user_id` = `vip_demo_accounts`.`owner_user_id`
        LEFT JOIN `users` AS `approvers` ON `approvers`.`user_id` = `vip_demo_accounts`.`approved_by_user_id`
        LEFT JOIN `users` AS `demo_users` ON `demo_users`.`user_id` = `vip_demo_accounts`.`demo_user_id`
        WHERE
            `vip_demo_accounts`.`vip_demo_account_id` = {$account_id}
        LIMIT 1
    ");

    $row = $result ? $result->fetch_object() : null;

    return $row ? vip_funnel_demo_hydrate_row($row) : null;
}

function vip_funnel_demo_find_duplicate_by_email(string $email = ''): ?\stdClass {
    if(!vip_funnel_demo_schema_is_ready()) {
        return null;
    }

    $email = mb_strtolower(trim($email));

    if($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $email_sql = database()->real_escape_string($email);
    $result = database()->query("
        SELECT
            `vip_demo_accounts`.`vip_demo_account_id`,
            `vip_demo_accounts`.`owner_user_id`,
            `vip_demo_accounts`.`status`,
            `vip_demo_accounts`.`datetime`,
            `vip_leads`.`lead_name`,
            `vip_leads`.`lead_email`,
            `vip_leads`.`payload` AS `lead_payload`
        FROM `vip_demo_accounts`
        LEFT JOIN `vip_leads` ON `vip_leads`.`vip_lead_id` = `vip_demo_accounts`.`vip_lead_id`
        WHERE LOWER(COALESCE(`vip_leads`.`lead_email`, '')) = '{$email_sql}'
           OR LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`vip_leads`.`payload`, '$.demo_login_email')), '')) = '{$email_sql}'
        ORDER BY `vip_demo_accounts`.`vip_demo_account_id` DESC
        LIMIT 1
    ");

    return $result ? ($result->fetch_object() ?: null) : null;
}

function vip_funnel_demo_get_duplicate_notice(string $email = '', int $owner_user_id = 0): string {
    $owner_profile = vip_funnel_get_owner_contact_profile($owner_user_id);
    $mentor_name = trim((string) ($owner_profile['name'] ?? 'mentor'));
    $whatsapp_url = trim((string) ($owner_profile['whatsapp_url'] ?? ''));
    $owner_email = trim((string) ($owner_profile['email'] ?? ''));
    $contact_html = '';

    if($whatsapp_url !== '') {
        $contact_html = '<a href="' . htmlspecialchars($whatsapp_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">otvori WhatsApp kontakt</a>';
    } elseif($owner_email !== '') {
        $contact_html = '<a href="mailto:' . htmlspecialchars($owner_email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">pošalji email mentoru</a>';
    }

    $message = 'Demo pristup se ne može zatražiti dva puta s istim emailom';

    if(trim($email) !== '') {
        $message .= ' (' . htmlspecialchars(trim($email), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ')';
    }

    $message .= '. Za nastavak, produženje ili regularnu registraciju / kupnju paketa javi se mentoru';

    if($mentor_name !== '') {
        $message .= ' ' . htmlspecialchars($mentor_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    return $contact_html !== '' ? $message . ': ' . $contact_html . '.' : $message . '.';
}

function vip_funnel_demo_update_account_settings(int $account_id, array $settings_payload): bool {
    if($account_id <= 0) {
        return false;
    }

    $result = db()->where('vip_demo_account_id', $account_id)->update('vip_demo_accounts', [
        'settings' => vip_funnel_json_encode($settings_payload),
        'last_datetime' => get_date(),
    ]);

    return (bool) ($result && empty(database()->error));
}

function vip_funnel_demo_generate_temporary_password(int $length = 12): string {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $max_index = strlen($characters) - 1;
    $password = '';

    for($i = 0; $i < max(10, $length); $i++) {
        $password .= $characters[random_int(0, $max_index)];
    }

    return $password;
}

function vip_funnel_demo_get_owner_user($account_context = null, $fallback_user = null): ?\stdClass {
    $owner_user_id = (int) ($account_context->owner_user_id ?? 0);

    if($owner_user_id > 0) {
        $owner_user = db()->where('user_id', $owner_user_id)->getOne('users', [
            'user_id',
            'name',
            'email',
            'timezone',
            'language',
            'plan_id',
            'plan_settings',
            'preferences',
        ]);

        if($owner_user) {
            return $owner_user;
        }
    }

    return $fallback_user && is_object($fallback_user) ? $fallback_user : null;
}

function vip_funnel_demo_get_demo_plan_settings($owner_user = null): \stdClass {
    $base_settings = vip_funnel_normalize_object(settings()->plan_custom->settings ?? []);
    $owner_plan_settings = vip_funnel_normalize_object($owner_user->plan_settings ?? []);

    if(!empty((array) $owner_plan_settings)) {
        $base_settings = vip_funnel_normalize_object(array_replace(vip_funnel_to_array($base_settings), vip_funnel_to_array($owner_plan_settings)));
    }

    $base_settings->fcc_ai_is_enabled = true;
    $base_settings->fcc_coach_is_enabled = true;
    $base_settings->ai_growth_plan_is_enabled = true;
    $base_settings->vip_funnel_core_is_enabled = true;

    return $base_settings;
}

function vip_funnel_demo_get_user_payload($user = null): \stdClass {
    $preferences = vip_funnel_get_user_preferences($user);
    $meta = vip_funnel_normalize_object($preferences->meta ?? []);
    $payload = vip_funnel_normalize_object($preferences->vip_funnel_demo ?? []);

    if((int) ($payload->vip_demo_account_id ?? 0) <= 0 && (int) ($meta->vip_demo_account_id ?? 0) > 0) {
        $payload->vip_demo_account_id = (int) ($meta->vip_demo_account_id ?? 0);
    }

    if((int) ($payload->owner_user_id ?? 0) <= 0 && (int) ($meta->vip_demo_owner_user_id ?? 0) > 0) {
        $payload->owner_user_id = (int) ($meta->vip_demo_owner_user_id ?? 0);
    }

    return $payload;
}

function vip_funnel_demo_is_sandbox_user($user = null): bool {
    static $statuses_synced = false;

    if(!$statuses_synced && vip_funnel_demo_schema_is_ready()) {
        $statuses_synced = true;
        vip_funnel_demo_sync_statuses();
    }

    $payload = vip_funnel_demo_get_user_payload($user);

    return (int) ($payload->vip_demo_account_id ?? 0) > 0 || !empty($payload->vip_demo_is_sandbox);
}

function vip_funnel_demo_get_owner_user_id_from_user($user = null): int {
    $payload = vip_funnel_demo_get_user_payload($user);
    $owner_user_id = (int) ($payload->owner_user_id ?? 0);

    if($owner_user_id > 0) {
        return $owner_user_id;
    }

    return (int) ($user->user_id ?? 0);
}

function vip_funnel_demo_get_locked_badge_label(): string {
    return (\Altum\Language::$code ?? 'hr') === 'hr' ? 'Demo' : 'Demo';
}

function vip_funnel_demo_get_locked_route_module_map(): array {
    return [
        'vip-funnel-studio' => 'vip_funnel',
        'vip-funnel-demo-access' => 'vip_funnel',
        'ai-plan' => 'ai_plan',
        'ai-app-review' => 'ai_plan',
        'payment-processors' => 'payment_processors',
        'payment-processor-create' => 'payment_processors',
        'payment-processor-update' => 'payment_processors',
        'account-plan' => 'account_plan',
        'account-payments' => 'account_payments',
    ];
}

function vip_funnel_demo_get_module_key_by_route(?string $route_key = null): ?string {
    $route_key = trim((string) ($route_key ?? ''));
    $map = vip_funnel_demo_get_locked_route_module_map();

    return $map[$route_key] ?? null;
}

function vip_funnel_demo_resolve_start_package_offer($user = null, ?string $page_language_code = null): array {
    static $cache = [];

    $resolved_language_code = trim((string) ($page_language_code ?? (\Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr')));
    $owner_user_id = vip_funnel_demo_get_owner_user_id_from_user($user);
    $referral_slug = vip_funnel_get_user_referral_slug($owner_user_id);
    $cache_key = md5(json_encode([$owner_user_id, $referral_slug, $resolved_language_code]));

    $is_hr = (\Altum\Language::$code ?? 'hr') === 'hr';
    $default_title = $is_hr ? 'Start Paket' : 'Start Package';
    $default_guide_url = url('blog?search=' . rawurlencode($is_hr ? 'start paket' : 'start package'));
    $default_direct_url = $default_guide_url;

    if(isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    try {
        $current_language_name = (string) (\Altum\Language::$name ?? \Altum\Language::$default_name ?? '');
        $default_language_name = (string) (\Altum\Language::$default_name ?? $current_language_name);
        $current_language_sql = db()->escape($current_language_name);
        $default_language_sql = db()->escape($default_language_name);

        $start_package_result = database()->query("
            SELECT
                `blog_post_id`,
                `title`,
                `description`,
                `url`,
                `image`,
                `language`
            FROM
                `blog_posts`
            WHERE
                LOWER(`url`) = 'start-paket'
                AND `is_published` = 1
            ORDER BY
                CASE
                    WHEN `language` = '{$current_language_sql}' THEN 300
                    WHEN `language` IS NULL THEN 200
                    WHEN `language` = '{$default_language_sql}' THEN 100
                    ELSE 0
                END DESC,
                `blog_post_id` ASC
            LIMIT 1
        ");

        $start_package_post = $start_package_result ? $start_package_result->fetch_object() : null;
        $owner_name = '';
        $owner_phone_raw = '';

        if($owner_user_id > 0) {
            $owner_user = db()->where('user_id', $owner_user_id)->getOne('users', ['name', 'billing', 'preferences']);

            if($owner_user) {
                $owner_name = trim((string) ($owner_user->name ?? ''));
                $owner_preferences = vip_funnel_normalize_object($owner_user->preferences ?? []);
                $owner_meta = vip_funnel_normalize_object($owner_preferences->meta ?? []);
                $owner_phone_raw = trim((string) ($owner_meta->phone ?? ''));

                if($owner_phone_raw === '') {
                    $owner_billing = vip_funnel_to_array($owner_user->billing ?? []);
                    $owner_phone_raw = trim((string) ($owner_billing['phone'] ?? ''));
                }
            }
        }

        $blog_guide_url = $default_guide_url;
        $direct_shop_url = $default_direct_url;
        $blog_post_id = 0;
        $blog_post_title = $default_title;

        if($start_package_post) {
            $blog_post_id = (int) ($start_package_post->blog_post_id ?? 0);
            $blog_post_title = trim((string) ($start_package_post->title ?? '')) ?: $blog_post_title;
            $language_prefix = '';

            if(!empty($start_package_post->language) && isset(\Altum\Language::$active_languages[$start_package_post->language])) {
                $language_prefix = \Altum\Language::$active_languages[$start_package_post->language] . '/';
            }

            $blog_guide_url = SITE_URL . $language_prefix . 'blog/' . trim((string) ($start_package_post->url ?? 'start-paket'), '/');
            $direct_shop_url = url('blog-click?blog_post_id=' . $blog_post_id);
        }

        if($referral_slug !== '') {
            $blog_guide_url = vip_funnel_append_url_query_param($blog_guide_url, 'ref', $referral_slug);
            $direct_shop_url = vip_funnel_append_url_query_param($direct_shop_url, 'ref', $referral_slug);
        }

        $blog_guide_url = vip_funnel_append_url_query_param($blog_guide_url, 'utm_source', 'vip_demo_unlock');
        $direct_shop_url = vip_funnel_append_url_query_param($direct_shop_url, 'utm_source', 'vip_demo_unlock');
        $owner_phone_digits = preg_replace('/\D+/', '', $owner_phone_raw);
        $owner_phone_direct = preg_replace('/[^0-9\+]/', '', $owner_phone_raw);
        $whatsapp_message = $is_hr
            ? 'Želim se registrirati, pokrenuti edukaciju i otključati FCC sustav.'
            : 'I want to register, start the onboarding, and unlock the FCC system.';
        $owner_whatsapp_url = $owner_phone_digits !== ''
            ? 'https://wa.me/' . $owner_phone_digits . '?text=' . rawurlencode($whatsapp_message)
            : '';

        return $cache[$cache_key] = [
            'blog_post_id' => $blog_post_id,
            'title' => $blog_post_title,
            'guide_url' => $blog_guide_url,
            'direct_shop_url' => $direct_shop_url,
            'referral_slug' => $referral_slug,
            'owner_user_id' => $owner_user_id,
            'owner_name' => $owner_name,
            'owner_phone' => $owner_phone_direct,
            'owner_whatsapp_url' => $owner_whatsapp_url,
            'primary_url' => $direct_shop_url !== '' ? $direct_shop_url : $blog_guide_url,
            'secondary_url' => $blog_guide_url !== '' ? $blog_guide_url : $direct_shop_url,
        ];
    } catch(\Throwable $exception) {
        error_log('vip_funnel_demo_resolve_start_package_offer_failed: ' . $exception->getMessage());

        return $cache[$cache_key] = [
            'blog_post_id' => 0,
            'title' => $default_title,
            'guide_url' => $default_guide_url,
            'direct_shop_url' => $default_direct_url,
            'referral_slug' => $referral_slug,
            'owner_user_id' => $owner_user_id,
            'owner_name' => '',
            'owner_phone' => '',
            'owner_whatsapp_url' => '',
            'primary_url' => $default_direct_url,
            'secondary_url' => $default_guide_url,
        ];
    }
}

function vip_funnel_demo_get_locked_module_config(string $module_key): array {
    $modules = ['vip_funnel', 'ai_plan', 'account_plan', 'account_payments', 'payment_processors', 'default'];
    $map = [];

    foreach($modules as $key) {
        $map[$key] = [
            'eyebrow' => l('vip_funnel.demo_lock.modules.' . $key . '.eyebrow'),
            'title' => l('vip_funnel.demo_lock.modules.' . $key . '.title'),
            'message' => l('vip_funnel.demo_lock.modules.' . $key . '.message'),
            'features' => array_values(array_filter([
                l('vip_funnel.demo_lock.modules.' . $key . '.feature_1'),
                l('vip_funnel.demo_lock.modules.' . $key . '.feature_2'),
                l('vip_funnel.demo_lock.modules.' . $key . '.feature_3'),
            ])),
        ];
    }

    return $map[$module_key] ?? $map['default'];
}

function vip_funnel_demo_get_locked_module_payload($user = null, string $module_key = 'default', array $options = []): ?\stdClass {
    if(!vip_funnel_demo_is_sandbox_user($user)) {
        return null;
    }

    $module = vip_funnel_demo_get_locked_module_config($module_key);
    $offer = vip_funnel_demo_resolve_start_package_offer($user, (string) (\Altum\Language::$code ?? \Altum\Language::$default_code));
    return (object) [
        'module_key' => $module_key,
        'eyebrow' => (string) ($module['eyebrow'] ?? ''),
        'title' => (string) ($module['title'] ?? ''),
        'message' => (string) ($module['message'] ?? ''),
        'features' => array_values(array_filter(array_map('strval', $module['features'] ?? []))),
        'badge' => vip_funnel_demo_get_locked_badge_label(),
        'primary_url' => (string) ($offer['primary_url'] ?? ''),
        'secondary_url' => (string) ($offer['secondary_url'] ?? ''),
        'primary_label' => l('vip_funnel.demo_lock.primary_label'),
        'secondary_label' => l('vip_funnel.demo_lock.secondary_label'),
        'whatsapp_url' => (string) ($offer['owner_whatsapp_url'] ?? ''),
        'whatsapp_label' => l('vip_funnel.demo_lock.whatsapp_label'),
        'owner_name' => (string) ($offer['owner_name'] ?? ''),
        'owner_phone' => (string) ($offer['owner_phone'] ?? ''),
        'offer_title' => (string) ($offer['title'] ?? l('vip_funnel.demo_lock.offer_title')),
        'footnote' => l('vip_funnel.demo_lock.footnote'),
        'back_url' => (string) ($options['back_url'] ?? url('dashboard')),
        'back_label' => (string) ($options['back_label'] ?? l('vip_funnel.demo_lock.back_label')),
        'banner_text' => l('vip_funnel.demo_lock.banner_text'),
    ];
}

function vip_funnel_demo_get_global_banner_payload($user = null, ?string $route_key = null): ?\stdClass {
    if(!vip_funnel_demo_is_sandbox_user($user)) {
        return null;
    }

    if(vip_funnel_demo_get_module_key_by_route($route_key)) {
        return null;
    }

    $payload = vip_funnel_demo_get_locked_module_payload($user, 'default');

    if(!$payload) {
        return null;
    }

    $payload->eyebrow = l('vip_funnel.demo_lock.global.eyebrow');
    $payload->title = l('vip_funnel.demo_lock.global.title');
    $payload->message = l('vip_funnel.demo_lock.global.message');
    $payload->features = array_values(array_filter([
        l('vip_funnel.demo_lock.global.feature_vip_funnel'),
        l('vip_funnel.demo_lock.global.feature_growth_plan'),
        l('vip_funnel.demo_lock.global.feature_plan_activation'),
        l('vip_funnel.demo_lock.global.feature_payments'),
    ]));

    return $payload;
}

function vip_funnel_demo_get_locked_action_message(string $module_key = 'default'): string {
    try {
        $payload = vip_funnel_demo_get_locked_module_payload(user(), $module_key);
    } catch(\Throwable $exception) {
        error_log('vip_funnel_demo_get_locked_action_message_failed: ' . $exception->getMessage());
        $payload = null;
    }

    return $payload ? $payload->title : l('global.info_message.plan_feature_no_access');
}

function vip_funnel_demo_render_locked_route($controller, $user = null, string $module_key = 'default', array $options = []): bool {
    try {
        $payload = vip_funnel_demo_get_locked_module_payload($user, $module_key, $options);
    } catch(\Throwable $exception) {
        error_log('vip_funnel_demo_render_locked_route_failed: ' . $exception->getMessage());
        $payload = null;
    }

    if(!$payload || !is_object($controller) || !method_exists($controller, 'add_view_content')) {
        return false;
    }

    \Altum\Title::set($payload->title);

    $view = new \Altum\View('demo-account-locked/index', (array) $controller);
    $controller->add_view_content('content', $view->run([
        'lock' => $payload,
    ]));

    return true;
}

function vip_funnel_demo_build_seed_weekly_plan($account_context = null, $owner_user = null, ?string $generated_at = null): array {
    $generated_at = $generated_at ?: get_date();
    $interest_type = (string) ($account_context->interest_type ?? 'demo');
    $lead_name = trim((string) ($account_context->lead_name ?? ''));
    $owner_name = trim((string) ($owner_user->name ?? $account_context->owner_label ?? 'FCC mentor'));
    $owner_label = $owner_name !== '' ? $owner_name : 'FCC mentor';

    $config = [
        'headline' => 'Tvoja prva 3 VIP demo dana u FCC-u',
        'summary' => 'Upoznaj sustav bez kaosa: jedan jasan smjer, jedan rezultat i jedan sljedeći korak u 3 dana.',
        'focus' => 'Osjeti kako FCC vodi osobu od interesa do odluke bez preopterećenja.',
        'coach_intro' => 'Ovaj demo je složen da ti brzo pokaže kako bi izgledala prva 3 dana uz vodstvo i jasan plan.',
        'brutal_truth' => 'Ne trebaš razumjeti cijeli sustav prvi dan. Trebaš vidjeti jedan logičan put koji tebi ima smisla.',
        'power_move' => 'Otvori glavni workspace, pregledaj tjedni plan i postavi coachu jedno konkretno pitanje koje ti je sada najvažnije.',
        'why_this_week' => 'Cilj nije da naučiš sve nego da u 3 dana doživiš kako sustav razmišlja i kako ti pomaže da kreneš sigurnije.',
        'encouragement' => 'Dovoljno je da osjetiš prvi “aha” trenutak. Od tamo dalje sve postaje jasnije.',
        'priority_channels' => ['whatsapp', 'instagram'],
        'content_ideas' => [
            'Zapiši što ti je najjači prvi dojam dok prolaziš kroz demo.',
            'Odaberi jednu poruku kojom bi objasnio/la što te najviše privlači u ovom modelu.',
            'Spremi jedno pitanje koje želiš postaviti mentoru nakon demo obilaska.',
        ],
        'coach_ideas' => [
            'Traži od coacha da ti objasni najbolji prvi korak za tvoju situaciju.',
            'Usporedi put za proizvode, online posao i sustav te odaberi što ti sada najviše odgovara.',
            'Zatraži prijedlog kako da ne uđeš u overload nego da ideš fazno.',
        ],
        'do_not_do' => [
            'Ne pokušavaj proučiti sve funkcije odjednom.',
            'Ne uspoređuj svoj početak s tuđim zrelim rezultatom.',
            'Ne preskači razgovor s mentorom ako ti je nešto nejasno.',
        ],
        'daily_plan' => [
            ['day' => 'Dan 1', 'title' => 'Ulaz i prvi dojam', 'tasks' => ['Otvori demo workspace i pregledaj glavni put.', 'Pogledaj što je tvoj fokus ovog demo tjedna.', 'Zapiši jedan prvi “aha” trenutak.']],
            ['day' => 'Dan 2', 'title' => 'Doživi sustav', 'tasks' => ['Prođi jedan vođeni korak do kraja.', 'Otvori AI coach i postavi jedno konkretno pitanje.', 'Pogledaj kako izgleda radni ritam bez kaosa.']],
            ['day' => 'Dan 3', 'title' => 'Pronađi svoj smjer', 'tasks' => ['Odaberi zanima li te više posao, proizvodi ili sustav.', 'Pregledaj preporučeni put za taj interes.', 'Spremi što ti je najrealniji sljedeći korak.']],
        ],
    ];

    if($interest_type === 'business') {
        $config['headline'] = '3-dnevni VIP demo za pokretanje online posla';
        $config['summary'] = 'Ovaj demo ti pokazuje kako bi izgledao početak online Forever posla uz gotov sustav i vodstvo.';
        $config['focus'] = 'Razumjeti kako FCC pojednostavljuje sponzoriranje, praćenje i prvi momentum.';
        $config['power_move'] = 'Pogledaj business put, otvori coach i traži prvi korak za svoj osobni start.';
        $config['content_ideas'] = [
            'Zapiši zašto te privlači online model bez improvizacije.',
            'Sažmi kako bi objasnio/la ovaj sustav jednoj novoj osobi.',
            'Odredi koji bi bio tvoj prvi kontaktni krug za start.',
        ];
    } elseif($interest_type === 'product') {
        $config['headline'] = '3-dnevni VIP demo za proizvode i preporuke';
        $config['summary'] = 'Demo te vodi kroz jednostavan produktni pristup: potrebe, preporuka, iskustvo i nastavak suradnje.';
        $config['focus'] = 'Doživjeti kako FCC može pomoći da se proizvodi preporučuju logično i bez pritiska.';
        $config['power_move'] = 'Pregledaj produktni put i zamoli coacha da ti predloži najbolji početni pristup za preporuku proizvoda.';
        $config['coach_ideas'] = [
            'Traži preporuku kako razgovarati o potrebama osobe prije proizvoda.',
            'Provjeri kako spojiti proizvodni interes s kasnijom suradnjom.',
            'Zamoli coacha za jednostavan prvi follow-up nakon preporuke.',
        ];
    }

    if($lead_name !== '') {
        $config['coach_intro'] = 'Ovaj demo za ' . $lead_name . ' je složen da vrlo brzo pokaže kako izgleda jasan i miran početak uz FCC.';
    }

    $config['why_this_week'] .= ' Mentor za ovaj demo je ' . $owner_label . '.';

    return [
        'checkin_submitted_at' => $generated_at,
        'generated_at' => $generated_at,
        'model' => fc_get_resolved_openai_model(settings()->main->openai_model ?? ''),
        'headline' => $config['headline'],
        'summary' => $config['summary'],
        'focus' => $config['focus'],
        'coach_intro' => $config['coach_intro'],
        'brutal_truth' => $config['brutal_truth'],
        'power_move' => $config['power_move'],
        'why_this_week' => $config['why_this_week'],
        'encouragement' => $config['encouragement'],
        'priority_channels' => $config['priority_channels'],
        'content_ideas' => $config['content_ideas'],
        'coach_ideas' => $config['coach_ideas'],
        'do_not_do' => $config['do_not_do'],
        'daily_plan' => $config['daily_plan'],
    ];
}

function vip_funnel_demo_build_seed_admin_coaching($account_context = null, $owner_user = null): \stdClass {
    $interest_labels = vip_funnel_demo_get_interest_options();
    $readiness_labels = vip_funnel_demo_get_readiness_options();
    $lead_name = trim((string) ($account_context->lead_name ?? 'nova osoba'));
    $interest_label = $interest_labels[(string) ($account_context->interest_type ?? 'demo')] ?? 'VIP demo';
    $readiness_label = $readiness_labels[(string) ($account_context->business_readiness ?? 'curious')] ?? '';
    $product_goal = trim((string) ($account_context->product_goal ?? ''));
    $owner_name = trim((string) ($owner_user->name ?? $account_context->owner_label ?? 'FCC mentor'));

    $guidance = [
        'Ovo je VIP demo korisnik unutar FCC-a. Vodi osobu nježno, jasno i fazno.',
        'Primarni fokus ovog demo računa je: ' . $interest_label . '.',
    ];

    if($readiness_label !== '') {
        $guidance[] = 'Procijenjena spremnost: ' . $readiness_label . '.';
    }

    if($product_goal !== '') {
        $guidance[] = 'Zabilježeni cilj/interes: ' . $product_goal . '.';
    }

    $guidance[] = 'Ne pretrpavaj osobu svim mogućnostima. Uvijek preporuči samo jedan sljedeći najlogičniji korak.';
    $guidance[] = 'Kad osoba pokaže interes, poveži je s mentorom ' . ($owner_name !== '' ? $owner_name : 'FCC mentor') . ' za razgovor ili iduću odluku.';
    $guidance[] = 'Ako osoba pita za proizvode, vodi je kroz potrebu, iskustvo i jednostavnu preporuku. Ako pita za posao, vodi je kroz jednostavan put ulaza i podrške.';

    return (object) [
        'ai_guidance' => implode("\n", $guidance),
    ];
}

function vip_funnel_demo_build_seed_preferences($preferences, $account_context = null, $owner_user = null, array $access_payload = []): \stdClass {
    $preferences = vip_funnel_normalize_object($preferences);
    $preferences->meta = vip_funnel_normalize_object($preferences->meta ?? []);
    $preferences->meta->fcc_core_gate_exempt = true;
    $preferences->meta->fcc_core_completed = true;
    $preferences->meta->vip_demo_account_id = (int) ($account_context->vip_demo_account_id ?? 0);
    $preferences->meta->vip_demo_owner_user_id = (int) ($account_context->owner_user_id ?? 0);

    $generated_at = get_date();
    $existing_weekly_plans = vip_funnel_to_array($preferences->leader_ai_weekly_plans ?? []);

    if(empty($existing_weekly_plans)) {
        $preferences->leader_ai_weekly_plans = [vip_funnel_demo_build_seed_weekly_plan($account_context, $owner_user, $generated_at)];
    }

    $preferences->leader_ai_admin_coaching = vip_funnel_demo_build_seed_admin_coaching($account_context, $owner_user);
    $preferences->leader_ai_access = (object) [
        'starter_app_review_used' => 0,
        'starter_weekly_plan_used' => 0,
        'manual_tier' => 'qualified',
        'manual_note' => 'VIP demo sandbox',
        'manual_unlocked_at' => $generated_at,
    ];

    $preferences->vip_funnel_demo = (object) array_merge([
        'vip_demo_account_id' => (int) ($account_context->vip_demo_account_id ?? 0),
        'vip_lead_id' => (int) ($account_context->vip_lead_id ?? 0),
        'owner_user_id' => (int) ($account_context->owner_user_id ?? 0),
        'interest_type' => (string) ($account_context->interest_type ?? ''),
        'business_readiness' => (string) ($account_context->business_readiness ?? ''),
        'product_goal' => (string) ($account_context->product_goal ?? ''),
        'lead_name' => (string) ($account_context->lead_name ?? ''),
        'forever_id' => (string) ($account_context->forever_id ?? ''),
    ], $access_payload);

    return $preferences;
}

function vip_funnel_demo_refresh_user_access_payload(int $demo_user_id, $account_context = null, $owner_user = null, array $access_payload = []): bool {
    if($demo_user_id <= 0) {
        return false;
    }

    $user = db()->where('user_id', $demo_user_id)->getOne('users', ['user_id', 'preferences']);

    if(!$user) {
        return false;
    }

    $preferences = vip_funnel_demo_build_seed_preferences($user->preferences ?? [], $account_context, $owner_user, $access_payload);

    return vip_funnel_save_user_preferences($demo_user_id, $preferences);
}

function vip_funnel_demo_build_reset_password_url(string $email, string $lost_password_code, int $demo_user_id = 0): string {
    if($email === '' || $lost_password_code === '') {
        return '';
    }

    return url('reset-password/' . md5($email) . '/' . $lost_password_code . '?redirect=dashboard' . ($demo_user_id > 0 ? '&welcome=' . $demo_user_id : ''));
}

function vip_funnel_demo_send_access_email($account_context = null): array {
    $account_context = $account_context && is_object($account_context) ? $account_context : null;

    if(!$account_context) {
        return ['attempted' => false, 'sent' => false];
    }

    $login_email = trim((string) ($account_context->login_email ?? $account_context->requested_demo_login_email ?? $account_context->lead_email ?? ''));
    $lead_email_for_delivery = trim((string) ($account_context->lead_email ?? ''));
    $recipient_email = filter_var($lead_email_for_delivery, FILTER_VALIDATE_EMAIL) ? $lead_email_for_delivery : $login_email;
    $workspace_url = trim((string) ($account_context->workspace_url ?? ''));
    $reset_password_url = trim((string) ($account_context->reset_password_url ?? ''));
    $temporary_password = trim((string) ($account_context->temporary_password ?? ''));
    $lead_name = trim((string) ($account_context->lead_name ?? '')) ?: 'VIP demo korisnik';
    $owner_name = trim((string) ($account_context->owner_name ?? '')) ?: trim((string) ($account_context->owner_label ?? ''));
    $owner_profile = vip_funnel_get_owner_contact_profile((int) ($account_context->owner_user_id ?? 0));
    $owner_email = trim((string) ($owner_profile['email'] ?? ($account_context->owner_email ?? '')));
    $owner_whatsapp_url = trim((string) ($owner_profile['whatsapp_url'] ?? ''));
    $expires_at_label = trim((string) ($account_context->expires_at_display ?? $account_context->expires_at ?? ''));

    if($recipient_email === '' || $login_email === '' || ($workspace_url === '' && $reset_password_url === '')) {
        return ['attempted' => false, 'sent' => false];
    }

    $subject = 'Tvoj VIP demo pristup je spreman';
    $body_lines = [
        '<p>Pozdrav {{NAME}},</p>',
        '<p>Tvoj VIP demo račun je aktiviran i spreman za korištenje.</p>',
    ];

    if($workspace_url !== '') {
        $body_lines[] = '<p><strong>Demo workspace:</strong><br><a href="{{WORKSPACE_URL}}" target="_blank" rel="noopener noreferrer">{{WORKSPACE_URL}}</a></p>';
    }

    $body_lines[] = '<p><strong>Login email:</strong><br>{{LOGIN_EMAIL}}</p>';

    if($temporary_password !== '') {
        $body_lines[] = '<p><strong>Privremena lozinka:</strong><br>{{TEMP_PASSWORD}}</p>';
    }

    if($reset_password_url !== '') {
        $body_lines[] = '<p><strong>Reset / pristupni link:</strong><br><a href="{{RESET_PASSWORD_URL}}" target="_blank" rel="noopener noreferrer">{{RESET_PASSWORD_URL}}</a></p>';
    }

    if($expires_at_label !== '') {
        $body_lines[] = '<p><strong>Demo pristup vrijedi do:</strong><br>{{EXPIRES_AT}}</p>';
    }

    if($owner_name !== '') {
        $body_lines[] = '<p><strong>Tvoj kontakt / mentor:</strong><br>{{OWNER_NAME}}</p>';
    }

    if($owner_email !== '') {
        $body_lines[] = '<p><strong>Email mentora:</strong><br><a href="mailto:{{OWNER_EMAIL}}">{{OWNER_EMAIL}}</a></p>';
    }

    if($owner_whatsapp_url !== '') {
        $body_lines[] = '<p><strong>WhatsApp mentora:</strong><br><a href="{{OWNER_WHATSAPP_URL}}" target="_blank" rel="noopener noreferrer">Otvori WhatsApp razgovor</a></p>';
    }

    $body_lines[] = '<p>Preporuka: nakon prve prijave odmah postavi svoju trajnu lozinku preko reset linka.</p>';
    $body_lines[] = '<p>Vidimo se unutra,<br>{{WEBSITE_TITLE}}</p>';

    $email_template = get_email_template(
        [
            '{{NAME}}' => $lead_name,
        ],
        $subject,
        [
            '{{NAME}}' => htmlspecialchars($lead_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{WORKSPACE_URL}}' => htmlspecialchars($workspace_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{LOGIN_EMAIL}}' => htmlspecialchars($login_email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{TEMP_PASSWORD}}' => htmlspecialchars($temporary_password, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{RESET_PASSWORD_URL}}' => htmlspecialchars($reset_password_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{EXPIRES_AT}}' => htmlspecialchars($expires_at_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{OWNER_NAME}}' => htmlspecialchars($owner_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{OWNER_EMAIL}}' => htmlspecialchars($owner_email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{OWNER_WHATSAPP_URL}}' => htmlspecialchars($owner_whatsapp_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ],
        implode('', $body_lines)
    );

    $transport_result = send_mail($recipient_email, $email_template->subject, $email_template->body, [
        'return_transport_result' => true,
        'is_system_email' => true,
        'language' => settings()->main->default_language,
    ]);

    return [
        'attempted' => true,
        'sent' => !empty($transport_result->success),
        'transport_result' => $transport_result,
    ];
}

function vip_funnel_demo_provision_demo_user($account_context = null, $fallback_user = null): array {
    $account_context = $account_context && is_object($account_context) ? $account_context : null;
    $account_id = (int) ($account_context->vip_demo_account_id ?? 0);
    $lead_email = trim((string) ($account_context->lead_email ?? ''));
    $demo_login_email = trim((string) ($account_context->requested_demo_login_email ?? ''));
    $lead_name = trim((string) ($account_context->lead_name ?? ''));
    $lead_phone = trim((string) ($account_context->lead_phone ?? ''));
    $forever_id = trim((string) ($account_context->forever_id ?? ''));
    $provision_email = $demo_login_email !== '' ? $demo_login_email : $lead_email;

    if($account_id <= 0 || $provision_email === '' || $lead_name === '') {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.provision_failed')];
    }

    if($demo_login_email === '') {
        $site_host = parse_url(SITE_URL, PHP_URL_HOST) ?: 'forevercard.club';
        $site_host = preg_replace('/^www\./i', '', (string) $site_host);
        $site_host = preg_replace('/[^a-z0-9\.\-]/i', '', $site_host) ?: 'forevercard.club';
        $provision_email = 'demo-' . $account_id . '-' . substr(md5($lead_email), 0, 8) . '@' . $site_host;
    }

    if((int) ($account_context->demo_user_id ?? 0) > 0) {
        return [
            'success' => true,
            'demo_user_id' => (int) $account_context->demo_user_id,
            'settings_payload' => vip_funnel_to_array($account_context->settings_payload ?? []),
            'created' => false,
        ];
    }

    $existing_user = db()->where('email', $provision_email)->getOne('users', ['user_id', 'email']);

    if($existing_user) {
        $settings_payload = vip_funnel_to_array($account_context->settings_payload ?? []);
        $settings_payload['provisioning_status'] = 'blocked_existing_email';
        $settings_payload['provisioning_message'] = l('vip_funnel.demo.alert.provision_existing_email');
        vip_funnel_demo_update_account_settings($account_id, $settings_payload);

        return ['success' => false, 'message' => l('vip_funnel.demo.alert.provision_existing_email')];
    }

    $owner_user = vip_funnel_demo_get_owner_user($account_context, $fallback_user);
    $plan_settings = vip_funnel_demo_get_demo_plan_settings($owner_user);
    $temporary_password = vip_funnel_demo_generate_temporary_password();
    $timezone = trim((string) ($owner_user->timezone ?? settings()->main->default_timezone));
    $plan_expiration_date = (new \DateTimeImmutable())->modify('+10 years')->format('Y-m-d H:i:s');

    $registered_user = (new \Altum\Models\User())->create(
        $provision_email,
        $temporary_password,
        $lead_name,
        1,
        'vip_demo',
        null,
        null,
        false,
        'vip_demo',
        vip_funnel_json_encode($plan_settings),
        $plan_expiration_date,
        $timezone !== '' ? $timezone : settings()->main->default_timezone,
        [
            'vip_demo_account_id' => $account_id,
            'vip_demo_owner_user_id' => (int) ($account_context->owner_user_id ?? 0),
            'vip_demo_interest_type' => (string) ($account_context->interest_type ?? 'demo'),
        ],
        true,
        [
            'vip_demo_account_id' => $account_id,
            'vip_demo_lead_email' => $lead_email,
            'vip_demo_login_email' => $provision_email,
            'vip_demo_is_sandbox' => true,
            'phone' => $lead_phone,
            'foreverId' => $forever_id,
        ]
    );

    $demo_user_id = (int) ($registered_user['user_id'] ?? 0);

    if($demo_user_id <= 0) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.provision_failed')];
    }

    (new \Altum\Models\User())->create_links($demo_user_id);

    $lost_password_code = md5(uniqid('', true) . random_bytes(16));
    db()->where('user_id', $demo_user_id)->update('users', [
        'lost_password_code' => $lost_password_code,
    ]);

    $workspace_url = function_exists('fc_get_user_main_biolink_url') ? fc_get_user_main_biolink_url($demo_user_id) : '';
    $reset_password_url = vip_funnel_demo_build_reset_password_url($provision_email, $lost_password_code, $demo_user_id);
    $settings_payload = vip_funnel_to_array($account_context->settings_payload ?? []);
    $settings_payload['provisioning_status'] = 'ready';
    $settings_payload['workspace_url'] = $workspace_url;
    $settings_payload['login_email'] = $provision_email;
    $settings_payload['temporary_password'] = $temporary_password;
    $settings_payload['reset_password_url'] = $reset_password_url;
    $settings_payload['access_state'] = 'active';
    $settings_payload['provisioned_at'] = get_date();
    $settings_payload['provisioning_message'] = '';

    $access_payload = [
        'workspace_url' => $workspace_url,
        'login_email' => $provision_email,
        'temporary_password' => $temporary_password,
        'reset_password_url' => $reset_password_url,
        'access_state' => 'active',
        'expires_at' => (string) ($account_context->expires_at ?? ''),
        'owner_name' => (string) ($owner_user->name ?? ''),
    ];

    vip_funnel_demo_refresh_user_access_payload($demo_user_id, $account_context, $owner_user, $access_payload);

    $account_updated = db()->where('vip_demo_account_id', $account_id)->update('vip_demo_accounts', [
        'demo_user_id' => $demo_user_id,
        'settings' => vip_funnel_json_encode($settings_payload),
        'last_datetime' => get_date(),
    ]);

    if(!$account_updated || !empty(database()->error)) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.provision_failed')];
    }

    vip_funnel_demo_log_event($account_id, (int) ($fallback_user->user_id ?? 0), 'provisioned', [
        'demo_user_id' => $demo_user_id,
        'workspace_url' => $workspace_url,
    ]);

    return [
        'success' => true,
        'demo_user_id' => $demo_user_id,
        'settings_payload' => $settings_payload,
        'created' => true,
    ];
}

function vip_funnel_demo_sync_demo_user_access($account_context = null, string $target_status = '', $fallback_user = null): array {
    $account_context = $account_context && is_object($account_context) ? $account_context : null;
    $account_id = (int) ($account_context->vip_demo_account_id ?? 0);

    if($account_id <= 0) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.account_missing')];
    }

    $settings_payload = vip_funnel_to_array($account_context->settings_payload ?? []);
    $owner_user = vip_funnel_demo_get_owner_user($account_context, $fallback_user);
    $demo_user_id = (int) ($account_context->demo_user_id ?? 0);
    $is_unlock_state = in_array($target_status, ['active', 'expiring'], true);

    if($is_unlock_state && $demo_user_id <= 0) {
        $provisioning = vip_funnel_demo_provision_demo_user($account_context, $fallback_user);

        if(!$provisioning['success']) {
            return $provisioning;
        }

        $demo_user_id = (int) ($provisioning['demo_user_id'] ?? 0);
        $settings_payload = vip_funnel_to_array($provisioning['settings_payload'] ?? $settings_payload);
        $refreshed_account_context = vip_funnel_demo_get_account_context($account_id);
        if($refreshed_account_context) {
            $refreshed_account_context->demo_user_id = $demo_user_id;
            $refreshed_account_context->expires_at = $account_context->expires_at ?? $refreshed_account_context->expires_at;
            $refreshed_account_context->approved_at = $account_context->approved_at ?? $refreshed_account_context->approved_at;
            $account_context = $refreshed_account_context;
        } else {
            $account_context->demo_user_id = $demo_user_id;
        }
    }

    if($demo_user_id <= 0) {
        return ['success' => true, 'settings_payload' => $settings_payload];
    }

    $demo_user = db()->where('user_id', $demo_user_id)->getOne('users', ['user_id', 'email']);

    if(!$demo_user) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.provision_missing_user')];
    }

    $workspace_url = trim((string) ($settings_payload['workspace_url'] ?? (function_exists('fc_get_user_main_biolink_url') ? fc_get_user_main_biolink_url($demo_user_id) : '')));
    $login_email = trim((string) ($demo_user->email ?? $settings_payload['login_email'] ?? ''));
    $user_update = [];
    $access_state = 'locked';

    if($is_unlock_state) {
        $temporary_password = vip_funnel_demo_generate_temporary_password();
        $lost_password_code = md5(uniqid('', true) . random_bytes(16));
        $user_update = [
            'password' => password_hash($temporary_password, PASSWORD_DEFAULT),
            'lost_password_code' => $lost_password_code,
            'status' => 1,
            'plan_expiration_date' => (new \DateTimeImmutable())->modify('+10 years')->format('Y-m-d H:i:s'),
        ];

        $settings_payload['temporary_password'] = $temporary_password;
        $settings_payload['reset_password_url'] = vip_funnel_demo_build_reset_password_url($login_email, $lost_password_code, $demo_user_id);
        $settings_payload['provisioning_status'] = 'ready';
        $access_state = 'active';
    } else {
        $user_update = [
            'password' => password_hash(vip_funnel_demo_generate_temporary_password(24), PASSWORD_DEFAULT),
            'lost_password_code' => null,
            'status' => $target_status === 'converted' ? 1 : 0,
        ];
        $settings_payload['temporary_password'] = '';
        $settings_payload['reset_password_url'] = '';
        $settings_payload['provisioning_status'] = match($target_status) {
            'paused' => 'paused',
            'expired' => 'expired',
            'closed' => 'closed',
            'rejected' => 'rejected',
            'converted' => 'converted',
            default => 'locked',
        };
        $access_state = $settings_payload['provisioning_status'];
    }

    $user_updated = db()->where('user_id', $demo_user_id)->update('users', $user_update);

    if(!$user_updated || !empty(database()->error)) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.provision_failed')];
    }

    $settings_payload['workspace_url'] = $workspace_url;
    $settings_payload['login_email'] = $login_email;
    $settings_payload['access_state'] = $access_state;
    $settings_payload['access_updated_at'] = get_date();
    $settings_payload['expires_at'] = (string) ($account_context->expires_at ?? '');
    $settings_payload['owner_name'] = (string) ($owner_user->name ?? '');

    vip_funnel_demo_refresh_user_access_payload($demo_user_id, $account_context, $owner_user, [
        'workspace_url' => $workspace_url,
        'login_email' => $login_email,
        'temporary_password' => (string) ($settings_payload['temporary_password'] ?? ''),
        'reset_password_url' => (string) ($settings_payload['reset_password_url'] ?? ''),
        'access_state' => $access_state,
        'expires_at' => (string) ($account_context->expires_at ?? ''),
        'owner_name' => (string) ($owner_user->name ?? ''),
    ]);

    $settings_updated = vip_funnel_demo_update_account_settings($account_id, $settings_payload);

    if(!$settings_updated) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.action_failed')];
    }

    cache()->deleteItemsByTag('user_id=' . $demo_user_id);
    cache()->deleteItem('user?user_id=' . $demo_user_id);

    return ['success' => true, 'settings_payload' => $settings_payload];
}

function vip_funnel_demo_create_request($user = null, array $input = []): array {
    if(!vip_funnel_demo_schema_is_ready()) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.schema_missing')];
    }

    vip_funnel_ensure_runtime_schema();
    $lead_name = trim(input_clean((string) ($input['lead_name'] ?? ''), 120));
    $lead_email = trim(input_clean((string) ($input['lead_email'] ?? ''), 320));
    $demo_login_email = trim(input_clean((string) ($input['demo_login_email'] ?? ''), 320));
    $lead_phone = trim(input_clean((string) ($input['lead_phone'] ?? ''), 32));
    $forever_id = trim(input_clean((string) ($input['forever_id'] ?? ''), 12));
    $source = trim(input_clean((string) ($input['source'] ?? 'manual_pilot'), 64)) ?: 'manual_pilot';
    $interest_options = vip_funnel_demo_get_interest_options();
    $readiness_options = vip_funnel_demo_get_readiness_options();
    $interest_type = input_clean((string) ($input['interest_type'] ?? 'demo'), 64);
    $business_readiness = input_clean((string) ($input['business_readiness'] ?? 'curious'), 64);
    $product_goal = trim(input_clean((string) ($input['product_goal'] ?? ''), 120));
    $notes = trim(strip_tags(mb_substr((string) ($input['notes'] ?? ''), 0, 800)));
    $existing_vip_lead_id = max(0, (int) ($input['existing_vip_lead_id'] ?? 0));
    $requested_owner_user_id = (int) ($input['owner_user_id'] ?? 0);
    $owner_user_id = vip_funnel_demo_normalize_owner_user_id($requested_owner_user_id, $user);
    $actor_user_id = (int) ($input['actor_user_id'] ?? ($user->user_id ?? 0));

    if($source === 'vip_funnel_public' && $requested_owner_user_id > 0) {
        $public_owner = db()->where('user_id', $requested_owner_user_id)->where('status', 1)->getOne('users', ['user_id']);
        if($public_owner) {
            $owner_user_id = (int) $public_owner->user_id;
        }
    }

    if($lead_name === '') {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.name_required')];
    }

    if($lead_email === '') {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.email_required')];
    }

    if(!filter_var($lead_email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.email_invalid')];
    }

    if($demo_login_email !== '' && !filter_var($demo_login_email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.demo_login_email_invalid')];
    }

    if(!vip_funnel_demo_validate_optional_forever_id($forever_id)) {
        return ['success' => false, 'message' => l('register.error_message.foreverId_length')];
    }

    if(!array_key_exists($interest_type, $interest_options)) {
        $interest_type = 'demo';
    }

    if(!array_key_exists($business_readiness, $readiness_options)) {
        $business_readiness = 'curious';
    }

    $settings = vip_funnel_get_settings();
    $requested_days = max(1, min(3, (int) ($settings->default_demo_days ?? 3)));
    $demo_status = !empty($settings->demo_request_requires_approval) ? 'requested' : 'approved';
    $approved_at = $demo_status === 'approved' ? get_date() : null;
    $duplicate_email_candidates = array_values(array_unique(array_filter(array_map(static function($value) {
        return mb_strtolower(trim((string) $value));
    }, [$lead_email, $demo_login_email]))));

    foreach($duplicate_email_candidates as $duplicate_email_candidate) {
        $duplicate_demo = vip_funnel_demo_find_duplicate_by_email($duplicate_email_candidate);

        if($duplicate_demo) {
            $duplicate_owner_user_id = (int) ($duplicate_demo->owner_user_id ?? $owner_user_id);

            return [
                'success' => false,
                'duplicate' => true,
                'message' => vip_funnel_demo_get_duplicate_notice($duplicate_email_candidate, $duplicate_owner_user_id > 0 ? $duplicate_owner_user_id : $owner_user_id),
                'vip_demo_account_id' => (int) ($duplicate_demo->vip_demo_account_id ?? 0),
            ];
        }
    }

    db()->startTransaction();

    try {
        $existing_lead = null;
        if($existing_vip_lead_id > 0) {
            $existing_lead = db()->where('vip_lead_id', $existing_vip_lead_id)->getOne('vip_leads');
            if($existing_lead && $owner_user_id > 0 && (int) ($existing_lead->owner_user_id ?? 0) !== $owner_user_id) {
                $existing_lead = null;
            }
        }

        $lead_payload = array_merge(vip_funnel_to_array($existing_lead->payload ?? []), [
            'lead_name' => $lead_name,
            'lead_email' => $lead_email,
            'demo_login_email' => $demo_login_email,
            'lead_phone' => $lead_phone,
            'forever_id' => $forever_id,
            'notes' => $notes,
            'funnel_context' => vip_funnel_to_array($input['funnel_context'] ?? (vip_funnel_to_array($existing_lead->payload ?? [])['funnel_context'] ?? [])),
            'owner_profile' => vip_funnel_get_owner_contact_profile($owner_user_id),
            'contact_origin' => [
                'source_key' => $source === 'vip_funnel_public' ? 'vip_funnel' : 'vip_demo_access',
                'source_label' => $source === 'vip_funnel_public' ? l('vip_funnel.contacts.source.vip_funnel') : l('vip_funnel.contacts.source.vip_demo_access'),
                'source_context' => $source === 'vip_funnel_public' ? 'Public Funnel 2.0 demo request' : l('vip_funnel.contacts.source.vip_demo_access'),
                'contact_intent_key' => 'demo_request',
            ],
        ]);

        if($existing_lead) {
            $lead_id = (int) $existing_lead->vip_lead_id;
            db()->where('vip_lead_id', $lead_id)->update('vip_leads', [
                'user_id' => $actor_user_id > 0 ? $actor_user_id : (int) ($existing_lead->user_id ?? 0),
                'owner_user_id' => $owner_user_id > 0 ? $owner_user_id : ($existing_lead->owner_user_id ?? null),
                'selection_value' => $product_goal !== '' ? $product_goal : ($existing_lead->selection_value ?? null),
                'lead_name' => $lead_name,
                'full_name' => $lead_name,
                'lead_email' => $lead_email,
                'lead_phone' => $lead_phone !== '' ? $lead_phone : ($existing_lead->lead_phone ?? null),
                'source' => $source,
                'interest_type' => $interest_type,
                'business_readiness' => $business_readiness,
                'product_goal' => $product_goal,
                'demo_status' => $demo_status,
                'payload' => vip_funnel_json_encode($lead_payload),
                'last_datetime' => get_date(),
            ]);
        } else {
            $lead_id = (int) db()->insert('vip_leads', [
                'user_id' => $actor_user_id > 0 ? $actor_user_id : 0,
                'owner_user_id' => $owner_user_id > 0 ? $owner_user_id : null,
                'vip_funnel_id' => null,
                'source_step_key' => null,
                'selection_value' => $product_goal !== '' ? $product_goal : null,
                'lead_name' => $lead_name,
                'full_name' => $lead_name,
                'lead_email' => $lead_email,
                'lead_phone' => $lead_phone !== '' ? $lead_phone : null,
                'source' => $source,
                'interest_type' => $interest_type,
                'business_readiness' => $business_readiness,
                'product_goal' => $product_goal,
                'demo_status' => $demo_status,
                'payload' => vip_funnel_json_encode($lead_payload),
                'datetime' => get_date(),
                'last_datetime' => get_date(),
            ]);
        }

        if($lead_id <= 0) {
            throw new \Exception('lead_insert_failed');
        }

        $account_id = (int) db()->insert('vip_demo_accounts', [
            'vip_lead_id' => $lead_id,
            'demo_user_id' => null,
            'owner_user_id' => $owner_user_id > 0 ? $owner_user_id : null,
            'status' => $demo_status,
            'expires_at' => null,
            'approved_at' => $approved_at,
            'approved_by_user_id' => $approved_at ? ($actor_user_id > 0 ? $actor_user_id : null) : null,
            'settings' => vip_funnel_json_encode([
                'requested_days' => $requested_days,
                'requires_approval' => (bool) ($settings->demo_request_requires_approval ?? true),
                'sandbox_mode' => 'guided',
                'provisioning_status' => 'pending',
                'owner_profile' => vip_funnel_get_owner_contact_profile($owner_user_id),
                'funnel_context' => vip_funnel_to_array($input['funnel_context'] ?? []),
            ]),
            'datetime' => get_date(),
            'last_datetime' => get_date(),
        ]);

        if($account_id <= 0) {
            throw new \Exception('account_insert_failed');
        }

        vip_funnel_demo_log_event($account_id, $actor_user_id, 'requested', [
            'lead_name' => $lead_name,
            'lead_email' => $lead_email,
            'interest_type' => $interest_type,
            'owner_user_id' => $owner_user_id,
        ]);
        vip_funnel_sync_contact_data_from_lead_id($lead_id, [
            'vip_demo_account_id' => $account_id,
            'demo_status' => $demo_status,
        ]);

        db()->commit();

        vip_funnel_send_owner_email_notification($owner_user_id, 'demo_requested', [
            'lead_name' => $lead_name,
            'lead_email' => $lead_email,
            'lead_phone' => $lead_phone,
            'funnel_name' => (string) (($lead_payload['funnel_context']['funnel_name'] ?? '') ?: 'VIP Funnel 2.0'),
            'page_url' => (string) ($lead_payload['funnel_context']['page_url'] ?? ''),
            'source_label' => $source === 'vip_funnel_public' ? 'VIP Funnel 2.0 demo zahtjev' : l('vip_funnel.contacts.source.vip_demo_access'),
            'dashboard_url' => url('vip-funnel-demo-access'),
        ]);

        $activation_message = '';
        if($demo_status === 'approved') {
            $activation_result = vip_funnel_demo_apply_action($user, $account_id, 'activate');
            if(!empty($activation_result['message'])) {
                $activation_message = ' ' . $activation_result['message'];
            }
        }

        return [
            'success' => true,
            'message' => sprintf(l('vip_funnel.demo.alert.request_created'), $lead_name) . $activation_message,
            'vip_demo_account_id' => $account_id,
        ];
    } catch(\Throwable $exception) {
        db()->rollback();

        return ['success' => false, 'message' => l('vip_funnel.demo.alert.request_failed')];
    }
}

function vip_funnel_demo_apply_action($user = null, int $account_id = 0, string $action = ''): array {
    if(!vip_funnel_demo_schema_is_ready()) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.schema_missing')];
    }

    vip_funnel_demo_sync_statuses();

    $action = input_clean($action, 32);
    $account = vip_funnel_demo_get_account_context($account_id);

    if(!$account) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.account_missing')];
    }

    $available_actions = vip_funnel_demo_get_available_actions((string) $account->status);

    if(!in_array($action, $available_actions, true)) {
        return ['success' => false, 'message' => l('vip_funnel.demo.alert.action_invalid')];
    }

    $actor_user_id = (int) ($user->user_id ?? 0);
    $now = get_date();
    $settings = vip_funnel_get_settings();
    $default_demo_days = max(1, min(3, (int) ($settings->default_demo_days ?? 3)));
    $update_payload = [
        'last_datetime' => $now,
    ];
    $event_key = $action;
    $event_payload = [];
    $failure_message = l('vip_funnel.demo.alert.action_failed');

    switch($action) {
        case 'approve':
            $now_datetime = new \DateTimeImmutable($now);
            $expires_at = $now_datetime->modify('+' . $default_demo_days . ' days');
            $update_payload['status'] = $expires_at <= $now_datetime->modify('+1 day') ? 'expiring' : 'active';
            $update_payload['expires_at'] = $expires_at->format('Y-m-d H:i:s');
            $update_payload['approved_at'] = $now;
            $update_payload['approved_by_user_id'] = $actor_user_id > 0 ? $actor_user_id : null;
            $event_key = 'activated';
            $event_payload['expires_at'] = $update_payload['expires_at'];
            $event_payload['approved_from_request'] = true;
            break;

        case 'reject':
            $update_payload['status'] = 'rejected';
            break;

        case 'activate':
            $now_datetime = new \DateTimeImmutable($now);
            $expires_at = $now_datetime->modify('+' . $default_demo_days . ' days');

            $update_payload['status'] = $expires_at <= $now_datetime->modify('+1 day') ? 'expiring' : 'active';
            $update_payload['expires_at'] = $expires_at->format('Y-m-d H:i:s');

            if(empty($account->approved_at)) {
                $update_payload['approved_at'] = $now;
                $update_payload['approved_by_user_id'] = $actor_user_id > 0 ? $actor_user_id : null;
            }

            $event_key = 'activated';
            $event_payload['expires_at'] = $update_payload['expires_at'];
            break;

        case 'pause':
            $update_payload['status'] = 'paused';
            $event_key = 'paused';
            break;

        case 'extend_2':
        case 'extend_5':
            $now_datetime = new \DateTimeImmutable($now);
            $expires_at = $now_datetime->modify('+' . $default_demo_days . ' days');
            $update_payload['expires_at'] = $expires_at->format('Y-m-d H:i:s');
            $update_payload['status'] = $account->status === 'paused' ? 'paused' : ($expires_at <= $now_datetime->modify('+1 day') ? 'expiring' : 'active');
            $event_key = 'extended';
            $event_payload = [
                'days' => $default_demo_days,
                'capped_to_max_days' => 3,
                'expires_at' => $update_payload['expires_at'],
            ];
            break;

        case 'close':
            $update_payload['status'] = 'closed';
            $event_key = 'closed';
            break;

        case 'convert':
            $update_payload['status'] = 'converted';
            $event_key = 'converted';
            break;
    }

    db()->startTransaction();

    try {
        $access_target_status = (string) ($update_payload['status'] ?? $account->status);
        $account->status = $access_target_status;
        if(isset($update_payload['expires_at'])) {
            $account->expires_at = $update_payload['expires_at'];
        }
        if(isset($update_payload['approved_at'])) {
            $account->approved_at = $update_payload['approved_at'];
        }

        $access_sync = vip_funnel_demo_sync_demo_user_access($account, $access_target_status, $user);

        if(!$access_sync['success']) {
            $failure_message = $access_sync['message'] ?? l('vip_funnel.demo.alert.action_failed');
            throw new \Exception('access_sync_failed');
        }

        $updated_account = db()->where('vip_demo_account_id', $account_id)->update('vip_demo_accounts', $update_payload);

        if(!$updated_account || !empty(database()->error)) {
            throw new \Exception('account_update_failed');
        }

        $lead_updated = db()->where('vip_lead_id', (int) $account->vip_lead_id)->update('vip_leads', [
            'demo_status' => $update_payload['status'],
            'last_datetime' => $now,
        ]);

        if($lead_updated === false || !empty(database()->error)) {
            throw new \Exception('lead_update_failed');
        }

        vip_funnel_demo_log_event($account_id, $actor_user_id, $event_key, $event_payload);
        vip_funnel_sync_contact_data_from_lead_id((int) $account->vip_lead_id, [
            'vip_demo_account_id' => $account_id,
            'demo_status' => (string) ($update_payload['status'] ?? $account->status),
            'workspace_url' => (string) ($access_sync['settings_payload']['workspace_url'] ?? ''),
            'login_email' => (string) ($access_sync['settings_payload']['login_email'] ?? ''),
        ]);

        db()->commit();

        $message = l('vip_funnel.demo.alert.action_saved');

        if(in_array($action, ['activate', 'approve'], true)) {
            $updated_account_context = vip_funnel_demo_get_account_context($account_id);
            $access_email_result = vip_funnel_demo_send_access_email($updated_account_context);

            if(!empty($access_email_result['attempted'])) {
                $message .= ' ' . (!empty($access_email_result['sent']) ? l('vip_funnel.demo.alert.access_email_sent') : l('vip_funnel.demo.alert.access_email_failed'));
            }

            if($updated_account_context) {
                vip_funnel_send_owner_email_notification((int) ($updated_account_context->owner_user_id ?? 0), 'demo_activated', [
                    'lead_name' => (string) ($updated_account_context->lead_name ?? ''),
                    'lead_email' => (string) ($updated_account_context->lead_email ?? ''),
                    'lead_phone' => (string) ($updated_account_context->lead_phone ?? ''),
                    'funnel_name' => 'VIP Funnel 2.0 demo pristup',
                    'source_label' => 'VIP Funnel demo',
                    'dashboard_url' => url('vip-funnel-demo-access'),
                ]);
            }
        }

        return ['success' => true, 'message' => $message];
    } catch(\Throwable $exception) {
        db()->rollback();

        return ['success' => false, 'message' => $failure_message];
    }
}

function vip_funnel_demo_get_recent_events(int $limit = 12): array {
    if(!vip_funnel_demo_schema_is_ready()) {
        return [];
    }

    $limit = max(1, min(50, $limit));
    $result = database()->query("
        SELECT
            `vip_demo_events`.*,
            `vip_demo_accounts`.`status` AS `account_status`,
            `vip_leads`.`payload` AS `lead_payload`,
            `actors`.`name` AS `actor_name`
        FROM
            `vip_demo_events`
        LEFT JOIN `vip_demo_accounts` ON `vip_demo_accounts`.`vip_demo_account_id` = `vip_demo_events`.`vip_demo_account_id`
        LEFT JOIN `vip_leads` ON `vip_leads`.`vip_lead_id` = `vip_demo_accounts`.`vip_lead_id`
        LEFT JOIN `users` AS `actors` ON `actors`.`user_id` = `vip_demo_events`.`actor_user_id`
        ORDER BY
            `vip_demo_events`.`vip_demo_event_id` DESC
        LIMIT {$limit}
    ");

    $events = [];
    $event_labels = vip_funnel_demo_get_event_labels();

    if($result) {
        while($row = $result->fetch_object()) {
            $lead_payload = vip_funnel_to_array($row->lead_payload ?? []);
            $payload = vip_funnel_to_array($row->payload ?? []);
            $row->lead_name = trim((string) ($lead_payload['lead_name'] ?? '')) ?: l('vip_funnel.demo.contact_missing');
            $row->event_label = $event_labels[$row->event_key] ?? ucfirst(str_replace('_', ' ', (string) $row->event_key));
            $row->payload_data = $payload;
            $row->datetime_display = \Altum\Date::get($row->datetime, 2);
            $row->datetime_timeago = \Altum\Date::get_timeago($row->datetime);
            $events[] = $row;
        }
    }

    return $events;
}

function vip_funnel_demo_get_dashboard($user = null): array {
    $data = [
        'schema_ready' => vip_funnel_demo_schema_is_ready(),
        'requests' => [],
        'active' => [],
        'expiring' => [],
        'expired' => [],
        'archived' => [],
        'events' => [],
        'metrics' => [
            'requests' => 0,
            'active' => 0,
            'expiring' => 0,
            'expired' => 0,
            'archived' => 0,
        ],
        'owner_options' => vip_funnel_demo_get_owner_options($user),
        'interest_options' => vip_funnel_demo_get_interest_options(),
        'readiness_options' => vip_funnel_demo_get_readiness_options(),
        'default_request_form' => vip_funnel_demo_get_request_form_defaults($user),
    ];

    if(!$data['schema_ready']) {
        return $data;
    }

    vip_funnel_demo_sync_statuses();
    if($user) {
        vip_funnel_backfill_owner_runtime_contacts((int) ($user->user_id ?? 0));
    }

    $result = database()->query("
        SELECT
            `vip_demo_accounts`.*,
            `vip_leads`.`source`,
            `vip_leads`.`interest_type`,
            `vip_leads`.`business_readiness`,
            `vip_leads`.`product_goal`,
            `vip_leads`.`payload` AS `lead_payload`,
            `owners`.`name` AS `owner_name`,
            `owners`.`email` AS `owner_email`,
            `approvers`.`name` AS `approved_by_name`,
            `demo_users`.`email` AS `demo_user_email`,
            `demo_users`.`name` AS `demo_user_name`
        FROM
            `vip_demo_accounts`
        LEFT JOIN `vip_leads` ON `vip_leads`.`vip_lead_id` = `vip_demo_accounts`.`vip_lead_id`
        LEFT JOIN `users` AS `owners` ON `owners`.`user_id` = `vip_demo_accounts`.`owner_user_id`
        LEFT JOIN `users` AS `approvers` ON `approvers`.`user_id` = `vip_demo_accounts`.`approved_by_user_id`
        LEFT JOIN `users` AS `demo_users` ON `demo_users`.`user_id` = `vip_demo_accounts`.`demo_user_id`
        ORDER BY
            COALESCE(`vip_demo_accounts`.`last_datetime`, `vip_demo_accounts`.`datetime`) DESC,
            `vip_demo_accounts`.`vip_demo_account_id` DESC
    ");

    if($result) {
        while($row = $result->fetch_object()) {
            $demo = vip_funnel_demo_hydrate_row($row);

            if(in_array($demo->status, ['requested', 'approved'], true)) {
                $data['requests'][] = $demo;
            } elseif(in_array($demo->status, ['active', 'paused'], true)) {
                $data['active'][] = $demo;
            } elseif($demo->status === 'expiring') {
                $data['expiring'][] = $demo;
            } elseif($demo->status === 'expired') {
                $data['expired'][] = $demo;
            } else {
                $data['archived'][] = $demo;
            }
        }
    }

    $data['events'] = vip_funnel_demo_get_recent_events();
    $data['metrics']['requests'] = count($data['requests']);
    $data['metrics']['active'] = count($data['active']);
    $data['metrics']['expiring'] = count($data['expiring']);
    $data['metrics']['expired'] = count($data['expired']);
    $data['metrics']['archived'] = count($data['archived']);

    return $data;
}

function vip_funnel_demo_run_maintenance(): array {
    $before_counts = [
        'expired' => 0,
        'expiring' => 0,
    ];

    if(!vip_funnel_demo_schema_is_ready()) {
        return $before_counts;
    }

    $expired_result = database()->query("SELECT COUNT(*) AS `total` FROM `vip_demo_accounts` WHERE `status` IN ('active', 'expiring', 'paused') AND `expires_at` IS NOT NULL AND `expires_at` < '" . db()->escape(get_date()) . "'");
    $expiring_result = database()->query("SELECT COUNT(*) AS `total` FROM `vip_demo_accounts` WHERE `status` = 'active' AND `expires_at` IS NOT NULL AND `expires_at` BETWEEN '" . db()->escape(get_date()) . "' AND '" . db()->escape((new \DateTimeImmutable())->modify('+1 day')->format('Y-m-d H:i:s')) . "'");

    $before_counts['expired'] = (int) ($expired_result ? ($expired_result->fetch_object()->total ?? 0) : 0);
    $before_counts['expiring'] = (int) ($expiring_result ? ($expiring_result->fetch_object()->total ?? 0) : 0);

    vip_funnel_demo_sync_statuses();

    return $before_counts;
}
