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
        'default_demo_days' => 5,
        'demo_request_requires_approval' => true,
    ];

    $stored_settings = vip_funnel_normalize_object(settings()->vip_funnel ?? []);
    $settings_array = array_merge($defaults, json_decode(json_encode($stored_settings), true) ?? []);

    $settings = (object) $settings_array;
    $settings->rollout_mode = vip_funnel_normalize_rollout_mode((string) ($settings->rollout_mode ?? ''));
    $settings->visible_when_locked = (bool) ($settings->visible_when_locked ?? true);
    $settings->show_sidebar_entry_when_locked = (bool) ($settings->show_sidebar_entry_when_locked ?? true);
    $settings->pilot_allowed_user_ids = vip_funnel_parse_user_ids($settings->pilot_allowed_user_ids ?? []);
    $settings->default_demo_days = max(1, min(30, (int) ($settings->default_demo_days ?? 5)));
    $settings->demo_request_requires_approval = (bool) ($settings->demo_request_requires_approval ?? true);

    return $settings;
}

function vip_funnel_get_user_preferences($user = null): \stdClass {
    return vip_funnel_normalize_object($user->preferences ?? []);
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

    if(!empty($preferences->fcc_core_gate_exempt)) {
        return true;
    }

    $meta = vip_funnel_normalize_object($preferences->meta ?? []);

    return !empty($meta->fcc_core_gate_exempt);
}

function vip_funnel_user_can_publish_public_hub($user = null): bool {
    if(!$user) {
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
        'required' => isset($block['required']) ? (bool) $block['required'] : (bool) ($defaults['required'] ?? false),
        'spacing' => input_clean((string) ($block['spacing'] ?? ($defaults['spacing'] ?? 'md')), 12),
        'countdown_mode' => input_clean((string) ($block['countdown_mode'] ?? ($defaults['countdown_mode'] ?? 'fixed')), 16),
        'countdown_style' => input_clean((string) ($block['countdown_style'] ?? ($defaults['countdown_style'] ?? 'cards')), 24),
        'fixed_datetime' => trim(input_clean((string) ($block['fixed_datetime'] ?? ($defaults['fixed_datetime'] ?? '')), 40)),
        'duration_minutes' => max(0, min(10080, (int) ($block['duration_minutes'] ?? ($defaults['duration_minutes'] ?? 30)))),
        'duration_days' => max(0, min(365, (int) ($block['duration_days'] ?? ($defaults['duration_days'] ?? 0)))),
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

    if(!in_array($payload['countdown_mode'], ['fixed', 'evergreen'], true)) {
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
        if(in_array($type, ['name_field', 'full_name_field', 'email_field', 'phone_field'], true)) {
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
        if(in_array($type, ['name_field', 'full_name_field', 'email_field', 'phone_field'], true)) {
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
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : $fallback;
}

function vip_funnel_get_studio_seed_payload($user = null): array {
    $owner_options = function_exists('vip_funnel_demo_get_owner_options') ? vip_funnel_demo_get_owner_options($user) : [];
    $default_owner_user_id = (int) array_key_first($owner_options ?: [((int) ($user->user_id ?? 0)) => '']);
    $landing_page = vip_funnel_normalize_page_surface_payload([
        'name' => 'Glavna stranica funnela',
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
    ], 'Glavna stranica funnela');

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
        'landing_page' => vip_funnel_get_default_page_surface_payload('Glavna stranica funnela'),
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

    $payload['landing_page'] = vip_funnel_normalize_page_surface_payload($raw_landing_page ?? $seed['landing_page'], 'Glavna stranica funnela');
    $payload['paths'] = vip_funnel_normalize_paths_payload($raw_paths ?? $seed['paths']);
    $payload['board'] = vip_funnel_normalize_board_payload($raw_board ?? $seed['board']);
    $payload['defaults']['owner_user_id'] = (int) ($payload['defaults']['owner_user_id'] ?? ($user->user_id ?? 0));

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

function vip_funnel_studio_load_from_database($user = null): array {
    $seed_payload = vip_funnel_get_studio_seed_payload($user);

    if(!$user || !vip_funnel_studio_schema_is_ready()) {
        return $seed_payload;
    }

    $funnel = vip_funnel_studio_ensure_primary_funnel($user);

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

    return vip_funnel_normalize_studio_payload([
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

    if(function_exists('fc_add_table_column_if_missing')) {
        fc_add_table_column_if_missing('vip_funnel_runs', 'visitor_key', "`visitor_key` varchar(64) NULL AFTER `status`");
        fc_add_table_column_if_missing('vip_funnel_runs', 'variant_key', "`variant_key` varchar(8) NULL AFTER `visitor_key`");
        fc_add_table_column_if_missing('vip_funnel_runs', 'current_step_key', "`current_step_key` varchar(128) NULL AFTER `current_card_id`");
        fc_add_table_column_if_missing('vip_leads', 'visitor_key', "`visitor_key` varchar(64) NULL AFTER `owner_user_id`");
        fc_add_table_column_if_missing('vip_leads', 'lead_name', "`lead_name` varchar(160) NULL AFTER `visitor_key`");
        fc_add_table_column_if_missing('vip_leads', 'full_name', "`full_name` varchar(160) NULL AFTER `lead_name`");
        fc_add_table_column_if_missing('vip_leads', 'lead_email', "`lead_email` varchar(320) NULL AFTER `full_name`");
        fc_add_table_column_if_missing('vip_leads', 'lead_phone', "`lead_phone` varchar(64) NULL AFTER `lead_email`");
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

function vip_funnel_get_analytics_snapshot(int $funnel_id = 0): array {
    $snapshot = [
        'views' => 0,
        'submits' => 0,
        'advances' => 0,
        'leads' => 0,
        'best_step' => null,
        'ab' => [
            'a_views' => 0,
            'b_views' => 0,
            'a_submits' => 0,
            'b_submits' => 0,
            'winner' => '',
        ],
    ];

    if($funnel_id <= 0) {
        return $snapshot;
    }

    vip_funnel_ensure_runtime_schema();

    if(!vip_funnel_has_table('vip_funnel_events')) {
        return $snapshot;
    }

    $totals_result = database()->query("SELECT
            SUM(CASE WHEN `event_type` = 'view' THEN 1 ELSE 0 END) AS `views`,
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
        $snapshot['best_step'] = [
            'step_key' => (string) ($best_step->step_key ?? ''),
            'views' => (int) ($best_step->total ?? 0),
        ];
    }

    $a_rate = $snapshot['ab']['a_views'] > 0 ? ($snapshot['ab']['a_submits'] / max(1, $snapshot['ab']['a_views'])) : 0;
    $b_rate = $snapshot['ab']['b_views'] > 0 ? ($snapshot['ab']['b_submits'] / max(1, $snapshot['ab']['b_views'])) : 0;
    if($snapshot['ab']['a_views'] || $snapshot['ab']['b_views']) {
        $snapshot['ab']['winner'] = $b_rate > $a_rate ? 'B' : 'A';
    }

    return $snapshot;
}

function vip_funnel_get_studio_state($user = null): array {
    $schema_ready = vip_funnel_studio_schema_is_ready();
    vip_funnel_ensure_runtime_schema();
    $payload = $schema_ready ? vip_funnel_studio_load_from_database($user) : vip_funnel_get_user_studio_full_payload($user);
    $payload = vip_funnel_normalize_studio_payload($payload, $user);
    $funnel_row = $schema_ready && $user ? vip_funnel_studio_get_primary_funnel_row((int) ($user->user_id ?? 0)) : null;

    return [
        'schema_ready' => $schema_ready,
        'storage_mode' => $schema_ready ? 'database' : 'preferences_fallback',
        'payload' => $payload,
        'board_payload' => $payload['board'],
        'board' => vip_funnel_hydrate_board_for_view($payload['board']),
        'paths' => $payload['paths'],
        'funnel' => $payload['funnel'],
        'results' => vip_funnel_get_results_snapshot($user, (int) ($funnel_row->vip_funnel_id ?? 0)),
        'analytics' => vip_funnel_get_analytics_snapshot((int) ($funnel_row->vip_funnel_id ?? 0)),
        'funnel_row' => $funnel_row,
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

function vip_funnel_get_public_payload_for_user(int $user_id = 0): ?array {
    if($user_id <= 0) {
        return null;
    }

    $user = db()
        ->where('user_id', $user_id)
        ->getOne('users', ['user_id', 'name', 'email', 'preferences']);

    if(!$user || !vip_funnel_user_can_publish_public_hub($user)) {
        return null;
    }

    if(vip_funnel_studio_schema_is_ready()) {
        $funnel = vip_funnel_studio_get_primary_funnel_row($user_id);

        if($funnel) {
            return vip_funnel_studio_load_from_database((object) ['user_id' => $user_id]);
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
        if($active_context && !empty($active_context['step']['next_step_id'])) {
            $target_step_id = (string) $active_context['step']['next_step_id'];
        }

        if($target_step_id === '') {
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

function vip_funnel_get_public_step_state(int $user_id = 0, string $requested_step_id = ''): ?array {
    $payload = vip_funnel_get_public_payload_for_user($user_id);

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
    $first_step_id = (string) ($first_context['step']['id'] ?? '');
    $current_step_id = (string) ($active_step['id'] ?? '');
    $viewer_key = function_exists('fc_get_funnel_visitor_key') ? fc_get_funnel_visitor_key() : md5(uniqid((string) $user_id, true));
    $runtime_context = vip_funnel_get_public_runtime_context($user_id, $viewer_key);
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
        ? vip_funnel_normalize_page_surface_payload($payload['landing_page'] ?? [], 'Glavna stranica funnela')
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
    $funnel_row = $payload['funnel'] ?? [];
    $funnel_id = (int) (($state['funnel_row']['vip_funnel_id'] ?? 0) ?: ($payload['funnel_row']['vip_funnel_id'] ?? 0));

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

function vip_funnel_get_public_runtime_context(int $user_id = 0, string $viewer_key = ''): array {
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

    $funnel = vip_funnel_studio_get_primary_funnel_row($user_id);
    $funnel_id = (int) ($funnel->vip_funnel_id ?? 0);

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

    $funnel = vip_funnel_studio_get_primary_funnel_row($user_id);
    $funnel_id = (int) ($funnel->vip_funnel_id ?? 0);

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

        if(in_array($type, ['name_field', 'full_name_field', 'email_field', 'phone_field'], true)) {
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

function vip_funnel_upsert_public_lead(array $state, array $fields = [], array $meta = []): int {
    if(!vip_funnel_demo_schema_is_ready()) {
        return 0;
    }

    vip_funnel_ensure_runtime_schema();
    $owner_user_id = (int) ($state['user_id'] ?? 0);
    $visitor_key = trim((string) ($state['viewer_key'] ?? ''));
    $lead_email = trim((string) ($fields['email'] ?? ''));
    $lead_phone = trim((string) ($fields['phone'] ?? ''));
    $lead_name = trim((string) ($fields['name'] ?? ''));
    $full_name = trim((string) ($fields['full_name'] ?? ($lead_name !== '' ? $lead_name : '')));

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
    ]);

    if($existing) {
        db()->where('vip_lead_id', (int) $existing->vip_lead_id)->update('vip_leads', [
            'visitor_key' => $visitor_key !== '' ? $visitor_key : ($existing->visitor_key ?? null),
            'lead_name' => $lead_name !== '' ? $lead_name : ($existing->lead_name ?? null),
            'full_name' => $full_name !== '' ? $full_name : ($existing->full_name ?? null),
            'lead_email' => $lead_email !== '' ? $lead_email : ($existing->lead_email ?? null),
            'lead_phone' => $lead_phone !== '' ? $lead_phone : ($existing->lead_phone ?? null),
            'payload' => vip_funnel_json_encode($payload),
            'last_datetime' => get_date(),
        ]);

        return (int) $existing->vip_lead_id;
    }

    $lead_id = (int) db()->insert('vip_leads', [
        'user_id' => $owner_user_id,
        'owner_user_id' => $owner_user_id,
        'visitor_key' => $visitor_key !== '' ? $visitor_key : null,
        'lead_name' => $lead_name !== '' ? $lead_name : null,
        'full_name' => $full_name !== '' ? $full_name : null,
        'lead_email' => $lead_email !== '' ? $lead_email : null,
        'lead_phone' => $lead_phone !== '' ? $lead_phone : null,
        'source' => 'vip_funnel_public',
        'interest_type' => 'funnel',
        'business_readiness' => 'warm',
        'product_goal' => trim((string) ($meta['selection'] ?? '')),
        'demo_status' => 'captured',
        'payload' => vip_funnel_json_encode($payload),
        'datetime' => get_date(),
        'last_datetime' => get_date(),
    ]);

    if($lead_id > 0) {
        db()->insert('data', [
            'biolink_block_id' => null,
            'link_id' => null,
            'project_id' => null,
            'user_id' => $owner_user_id,
            'type' => 'lead_funnel',
            'data' => vip_funnel_json_encode([
                'name' => $lead_name,
                'full_name' => $full_name,
                'email' => $lead_email,
                'phone' => $lead_phone,
                'source' => 'vip_funnel_public',
                'page_key' => (string) ($state['page_key'] ?? ''),
                'selection' => (string) ($meta['selection'] ?? ''),
                'radio_answers' => vip_funnel_to_array($meta['radio_answers'] ?? []),
            ]),
            'datetime' => get_date(),
        ]);
    }

    return $lead_id;
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
            $errors[] = 'Email';
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
            'message' => 'Molimo ispuni obavezna polja: ' . implode(', ', array_unique($errors)),
        ];
    }

    $has_capture = !empty(array_filter($fields, static function($value) {
        return trim((string) $value) !== '';
    }));
    $effective_selection = $selection !== '' ? $selection : (string) ($routing_answer['value'] ?? ($radio_answers[0]['value'] ?? ''));
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
    $public_payload = vip_funnel_get_public_payload_for_user($user_id);
    $payload = $public_payload ?: vip_funnel_get_studio_seed_payload((object) ['user_id' => $user_id]);
    $payload = vip_funnel_normalize_studio_payload($payload, (object) ['user_id' => $user_id]);
    $block_settings = vip_funnel_to_array($block_settings);
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

function vip_funnel_has_table(string $table): bool {
    static $cache = [];

    if(isset($cache[$table])) {
        return $cache[$table];
    }

    $table_sql = database()->real_escape_string($table);
    $result = database()->query("SHOW TABLES LIKE '{$table_sql}'");

    return $cache[$table] = (bool) ($result && $result->num_rows);
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
            return ['pause', 'extend_2', 'extend_5', 'close', 'convert'];

        case 'paused':
            return ['activate', 'extend_2', 'extend_5', 'close'];

        case 'expired':
            return ['extend_2', 'extend_5', 'close', 'convert'];

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
    $row->requested_days = max(1, (int) ($settings_payload['requested_days'] ?? vip_funnel_get_settings()->default_demo_days ?? 5));
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
    $is_hr = (\Altum\Language::$code ?? 'hr') === 'hr';

    $map = $is_hr ? [
        'vip_funnel' => [
            'eyebrow' => 'VIP Funnel',
            'title' => 'VIP Funnel se otključava nakon aktivacije Start Paketa',
            'message' => 'Ovdje se slažu prodajni, recruitment i demo tokovi koji kasnije mogu raditi za tvoj posao i tim. U demo računu dobivaš dojam sustava, ali puni builder i stvarna aktivacija otključavaju se tek kad kreneš ozbiljno.',
            'features' => [
                'Složi vlastite prodajne i recruitment funnele bez improvizacije.',
                'Pokreni tokove za proizvode, demo pristup i poslovnu suradnju.',
                'Kasnije dupliciraj sustav za svoj tim iz jednog centralnog modela.',
            ],
        ],
        'ai_plan' => [
            'eyebrow' => 'Tvoj plan rasta',
            'title' => 'AI analize i planovi rasta otključavaju se uz puni pristup',
            'message' => 'Tvoj plan rasta pretvara profil, aplikaciju i rezultate u konkretne AI preporuke, planove i sljedeće poteze. U demo računu vidiš temelj sustava, a puni AI pregled i akcijski planovi dolaze nakon aktivacije.',
            'features' => [
                'AI pregled aplikacije i ponude s konkretnim poboljšanjima.',
                'Tjedni planovi rasta, fokus i preporučeni sljedeći potezi.',
                'Jasniji smjer za prodaju, praćenje ljudi i razvoj posla.',
            ],
        ],
        'account_plan' => [
            'eyebrow' => 'Paketi i aktivacija',
            'title' => 'Ovdje se kasnije aktivira puni pristup FCC sustavu',
            'message' => 'U demo računu ovaj dio ostaje zaključan jer je sljedeći pravi korak Start Paket preko preporuke mentora. Tek tada se otključava puni sustav, alati i sve što je potrebno za ozbiljan rad.',
            'features' => [
                'Aktivacija punog FCC pristupa i premium modula.',
                'Jasan prijelaz iz demo iskustva u stvaran radni račun.',
                'Povezivanje pristupa s preporukom mentora i Start Paket tokom.',
            ],
        ],
        'account_payments' => [
            'eyebrow' => 'Plaćanja i aktivacije',
            'title' => 'Stvarna plaćanja i računi nisu dostupni u demo računu',
            'message' => 'Ovaj dio služi za praćenje aktivacija, uplata i računa nakon što imaš puni pristup. U demo računu ga držimo zaključanim kako bi iskustvo ostalo čisto, sigurno i fokusirano na odluku.',
            'features' => [
                'Pregled uplata, aktivacija i računa na jednom mjestu.',
                'Sigurni podaci tek nakon stvarne aktivacije računa.',
                'Jasna veza između paketa, pristupa i aktivnog poslovnog rada.',
            ],
        ],
        'payment_processors' => [
            'eyebrow' => 'Naplata i checkout',
            'title' => 'Payment linkovi i naplata otključavaju se kad kreneš ozbiljno',
            'message' => 'Ovdje se inače spajaju naplate, payment linkovi i checkout opcije za tvoju FCC aplikaciju. U demo računu taj dio ostaje zaključan jer najprije trebaš aktivirati puni pristup i svoj Start Paket.',
            'features' => [
                'Poveži naplatu sa svojom aplikacijom i offerima.',
                'Dodaj checkout opcije i payment linkove za stvaran rad.',
                'Pretvori interes u konkretnu prodaju tek kad je račun aktiviran.',
            ],
        ],
        'default' => [
            'eyebrow' => 'Premium modul',
            'title' => 'Ovaj dio se otključava nakon aktivacije punog pristupa',
            'message' => 'Demo račun ti pokazuje kako FCC sustav radi iznutra. Premium dijelovi se otključavaju nakon Start Paketa i pune aktivacije računa.',
            'features' => [
                'Otključaj sve premium module iz stvarnog računa.',
                'Poveži svoj pristup s preporukom mentora.',
                'Kreni dalje iz dema prema pravom radu i rezultatima.',
            ],
        ],
    ] : [
        'vip_funnel' => [
            'eyebrow' => 'VIP Funnel',
            'title' => 'VIP Funnel unlocks after the Start Package is activated',
            'message' => 'This is where the main sales, recruitment, and demo flows are built. The demo account shows the direction, while the full builder and live business activation unlock after you move forward seriously.',
            'features' => [
                'Build your own sales and recruitment funnels without improvisation.',
                'Launch flows for products, demo access, and business onboarding.',
                'Later duplicate the same engine across your team from one core model.',
            ],
        ],
        'ai_plan' => [
            'eyebrow' => 'Your Growth Plan',
            'title' => 'AI analysis and growth plans unlock with full access',
            'message' => 'Your Growth Plan turns your profile, app, and results into practical AI recommendations and next moves. The demo shows the foundation, while the full AI review and action plans unlock after activation.',
            'features' => [
                'AI review of your app and offer with concrete fixes.',
                'Weekly growth plans, focus, and suggested next moves.',
                'Clearer direction for sales, follow-up, and business growth.',
            ],
        ],
        'account_plan' => [
            'eyebrow' => 'Plans and Activation',
            'title' => 'This is where full FCC access gets activated later',
            'message' => 'In the demo account this section stays locked because the next real step is the Start Package through your mentor referral. That is what unlocks the full system and tools.',
            'features' => [
                'Activate full FCC access and premium modules.',
                'Move from demo experience into a real working account.',
                'Connect your access with the Start Package and mentor referral.',
            ],
        ],
        'account_payments' => [
            'eyebrow' => 'Payments and Activation',
            'title' => 'Real payments and invoices are not available in the demo account',
            'message' => 'This area is used for activation, payments, and invoices after full access is live. In the demo it stays locked so the experience remains clean and focused on the decision.',
            'features' => [
                'See activations, payments, and invoices in one place.',
                'Use real billing only after the account is fully activated.',
                'Keep the link between package, access, and live business clear.',
            ],
        ],
        'payment_processors' => [
            'eyebrow' => 'Checkout and Payments',
            'title' => 'Payment links and checkout unlock when you go live',
            'message' => 'This is where payment links and checkout options are connected to your FCC app. In the demo account it stays locked because full access and the Start Package come first.',
            'features' => [
                'Connect checkout with your app and offers.',
                'Add payment options and links for real business use.',
                'Turn interest into real sales after activation.',
            ],
        ],
        'default' => [
            'eyebrow' => 'Premium module',
            'title' => 'This area unlocks after full access is activated',
            'message' => 'Your demo account shows how FCC works from the inside. Premium parts unlock after the Start Package and full account activation.',
            'features' => [
                'Unlock all premium modules from a live account.',
                'Connect your access with your mentor referral.',
                'Move from demo into real work and results.',
            ],
        ],
    ];

    return $map[$module_key] ?? $map['default'];
}

function vip_funnel_demo_get_locked_module_payload($user = null, string $module_key = 'default', array $options = []): ?\stdClass {
    if(!vip_funnel_demo_is_sandbox_user($user)) {
        return null;
    }

    $module = vip_funnel_demo_get_locked_module_config($module_key);
    $offer = vip_funnel_demo_resolve_start_package_offer($user, (string) (\Altum\Language::$code ?? \Altum\Language::$default_code));
    $is_hr = (\Altum\Language::$code ?? 'hr') === 'hr';

    return (object) [
        'module_key' => $module_key,
        'eyebrow' => (string) ($module['eyebrow'] ?? ''),
        'title' => (string) ($module['title'] ?? ''),
        'message' => (string) ($module['message'] ?? ''),
        'features' => array_values(array_filter(array_map('strval', $module['features'] ?? []))),
        'badge' => vip_funnel_demo_get_locked_badge_label(),
        'primary_url' => (string) ($offer['primary_url'] ?? ''),
        'secondary_url' => (string) ($offer['secondary_url'] ?? ''),
        'primary_label' => $is_hr ? 'Naruči Start Paket' : 'Order the Start Package',
        'secondary_label' => $is_hr ? 'Pogledaj vodič za Start Paket' : 'Open the Start Package guide',
        'whatsapp_url' => (string) ($offer['owner_whatsapp_url'] ?? ''),
        'whatsapp_label' => $is_hr ? 'Pošalji WhatsApp sponzoru' : 'Message your sponsor on WhatsApp',
        'owner_name' => (string) ($offer['owner_name'] ?? ''),
        'owner_phone' => (string) ($offer['owner_phone'] ?? ''),
        'offer_title' => (string) ($offer['title'] ?? ($is_hr ? 'Start Paket' : 'Start Package')),
        'footnote' => $is_hr
            ? 'Nakon aktivacije Start Paketa dodjeljuje se Forever ID i otključava puni FCC sustav, edukacija i poslovni alati.'
            : 'After the Start Package is activated, the Forever ID is assigned and the full FCC system, onboarding, and business tools unlock.',
        'back_url' => (string) ($options['back_url'] ?? url('dashboard')),
        'back_label' => (string) ($options['back_label'] ?? ($is_hr ? 'Natrag na demo račun' : 'Back to the demo account')),
        'banner_text' => $is_hr
            ? 'Kad odlučiš krenuti ozbiljno, aktivacija Start Paketa otključava puni FCC sustav, edukaciju, poslovne alate i tvoj radni račun.'
            : 'When you decide to move forward seriously, activating the Start Package unlocks the full FCC system, onboarding, business tools, and your live account.',
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

    $is_hr = (\Altum\Language::$code ?? 'hr') === 'hr';
    $payload->eyebrow = $is_hr ? 'Demo račun' : 'Demo account';
    $payload->title = $is_hr
        ? 'Tvoj demo račun pokazuje kako FCC sustav radi iznutra'
        : 'Your demo account shows how FCC works from the inside';
    $payload->message = $is_hr
        ? 'Ovdje možeš doživjeti kako FCC sustav izgleda u praksi i kako te vodi kroz aplikaciju, edukaciju i radni tijek. Kada poželiš aktivirati puni pristup, sljedeći korak je Start Paket preko preporučenog sponzora.'
        : 'Here you can experience how FCC works in practice and how it guides you through the app, onboarding, and workflow. When you decide to activate full access, the next step is the Start Package through your recommended sponsor.';
    $payload->features = $is_hr
        ? ['VIP Funnel', 'Tvoj plan rasta', 'Aktivacija paketa', 'Naplata i checkout']
        : ['VIP Funnel', 'Growth Plan', 'Plan activation', 'Payments and checkout'];

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
        'headline' => 'Tvoj prvi VIP demo tjedan u FCC-u',
        'summary' => 'Upoznaj sustav bez kaosa: jedan jasan smjer, jedan rezultat i jedan sljedeći korak.',
        'focus' => 'Osjeti kako FCC vodi osobu od interesa do odluke bez preopterećenja.',
        'coach_intro' => 'Ovaj demo je složen da ti brzo pokaže kako bi izgledao tvoj prvi tjedan uz vodstvo i jasan plan.',
        'brutal_truth' => 'Ne trebaš razumjeti cijeli sustav prvi dan. Trebaš vidjeti jedan logičan put koji tebi ima smisla.',
        'power_move' => 'Otvori glavni workspace, pregledaj tjedni plan i postavi coachu jedno konkretno pitanje koje ti je sada najvažnije.',
        'why_this_week' => 'Cilj nije da naučiš sve nego da doživiš kako sustav razmišlja i kako ti pomaže da kreneš sigurnije.',
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
            ['day' => 'Dan 4', 'title' => 'Mentor i povjerenje', 'tasks' => ['Pregledaj dokazne elemente i podršku mentora.', 'Pripremi 2 pitanja za razgovor.', 'Poveži svoj cilj s onim što si vidio/la u demu.']],
            ['day' => 'Dan 5', 'title' => 'Odluka i sljedeći korak', 'tasks' => ['Zaključi vidiš li sebe u ovom modelu.', 'Dogovori razgovor ili traži idući korak.', 'Odluči ideš li dalje kroz suradnju ili kroz proizvode.']],
        ],
    ];

    if($interest_type === 'business') {
        $config['headline'] = 'VIP demo tjedan za pokretanje online posla';
        $config['summary'] = 'Ovaj demo ti pokazuje kako bi izgledao početak online Forever posla uz gotov sustav i vodstvo.';
        $config['focus'] = 'Razumjeti kako FCC pojednostavljuje sponzoriranje, praćenje i prvi momentum.';
        $config['power_move'] = 'Pogledaj business put, otvori coach i traži prvi korak za svoj osobni start.';
        $config['content_ideas'] = [
            'Zapiši zašto te privlači online model bez improvizacije.',
            'Sažmi kako bi objasnio/la ovaj sustav jednoj novoj osobi.',
            'Odredi koji bi bio tvoj prvi kontaktni krug za start.',
        ];
    } elseif($interest_type === 'product') {
        $config['headline'] = 'VIP demo tjedan za proizvode i preporuke';
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

    $recipient_email = trim((string) ($account_context->login_email ?? $account_context->requested_demo_login_email ?? $account_context->lead_email ?? ''));
    $workspace_url = trim((string) ($account_context->workspace_url ?? ''));
    $reset_password_url = trim((string) ($account_context->reset_password_url ?? ''));
    $temporary_password = trim((string) ($account_context->temporary_password ?? ''));
    $lead_name = trim((string) ($account_context->lead_name ?? '')) ?: 'VIP demo korisnik';
    $owner_name = trim((string) ($account_context->owner_name ?? '')) ?: trim((string) ($account_context->owner_label ?? ''));
    $expires_at_label = trim((string) ($account_context->expires_at_display ?? $account_context->expires_at ?? ''));

    if($recipient_email === '' || ($workspace_url === '' && $reset_password_url === '')) {
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
            '{{LOGIN_EMAIL}}' => htmlspecialchars($recipient_email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{TEMP_PASSWORD}}' => htmlspecialchars($temporary_password, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{RESET_PASSWORD_URL}}' => htmlspecialchars($reset_password_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{EXPIRES_AT}}' => htmlspecialchars($expires_at_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{{OWNER_NAME}}' => htmlspecialchars($owner_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
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
    $owner_user_id = vip_funnel_demo_normalize_owner_user_id($input['owner_user_id'] ?? 0, $user);
    $actor_user_id = (int) ($user->user_id ?? 0);

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
    $requested_days = max(1, (int) ($settings->default_demo_days ?? 5));
    $demo_status = !empty($settings->demo_request_requires_approval) ? 'requested' : 'approved';
    $approved_at = $demo_status === 'approved' ? get_date() : null;

    db()->startTransaction();

    try {
        $lead_id = (int) db()->insert('vip_leads', [
            'user_id' => $actor_user_id > 0 ? $actor_user_id : 0,
            'owner_user_id' => $owner_user_id > 0 ? $owner_user_id : null,
            'source' => $source,
            'interest_type' => $interest_type,
            'business_readiness' => $business_readiness,
            'product_goal' => $product_goal,
            'demo_status' => $demo_status,
            'payload' => vip_funnel_json_encode([
                'lead_name' => $lead_name,
                'lead_email' => $lead_email,
                'demo_login_email' => $demo_login_email,
                'lead_phone' => $lead_phone,
                'forever_id' => $forever_id,
                'notes' => $notes,
            ]),
            'datetime' => get_date(),
            'last_datetime' => get_date(),
        ]);

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

        db()->commit();

        return [
            'success' => true,
            'message' => sprintf(l('vip_funnel.demo.alert.request_created'), $lead_name),
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
    $default_demo_days = max(1, (int) ($settings->default_demo_days ?? 5));
    $update_payload = [
        'last_datetime' => $now,
    ];
    $event_key = $action;
    $event_payload = [];
    $failure_message = l('vip_funnel.demo.alert.action_failed');

    switch($action) {
        case 'approve':
            $update_payload['status'] = 'approved';
            $update_payload['approved_at'] = $now;
            $update_payload['approved_by_user_id'] = $actor_user_id > 0 ? $actor_user_id : null;
            break;

        case 'reject':
            $update_payload['status'] = 'rejected';
            break;

        case 'activate':
            $base_datetime = !empty($account->expires_at) ? new \DateTimeImmutable($account->expires_at) : new \DateTimeImmutable($now);
            $now_datetime = new \DateTimeImmutable($now);

            if($base_datetime < $now_datetime) {
                $base_datetime = $now_datetime;
            }

            $expires_at = empty($account->expires_at) || $base_datetime == $now_datetime
                ? $now_datetime->modify('+' . $default_demo_days . ' days')
                : $base_datetime;

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
            $extend_days = $action === 'extend_2' ? 2 : 5;
            $now_datetime = new \DateTimeImmutable($now);
            $base_datetime = !empty($account->expires_at) ? new \DateTimeImmutable($account->expires_at) : $now_datetime;

            if($base_datetime < $now_datetime) {
                $base_datetime = $now_datetime;
            }

            $expires_at = $base_datetime->modify('+' . $extend_days . ' days');
            $update_payload['expires_at'] = $expires_at->format('Y-m-d H:i:s');
            $update_payload['status'] = $account->status === 'paused' ? 'paused' : ($expires_at <= $now_datetime->modify('+1 day') ? 'expiring' : 'active');
            $event_key = 'extended';
            $event_payload = [
                'days' => $extend_days,
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

        db()->commit();

        $message = l('vip_funnel.demo.alert.action_saved');

        if($action === 'activate') {
            $updated_account_context = vip_funnel_demo_get_account_context($account_id);
            $access_email_result = vip_funnel_demo_send_access_email($updated_account_context);

            if(!empty($access_email_result['attempted'])) {
                $message .= ' ' . (!empty($access_email_result['sent']) ? l('vip_funnel.demo.alert.access_email_sent') : l('vip_funnel.demo.alert.access_email_failed'));
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
