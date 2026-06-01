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

use Altum\Alerts;
use Altum\Models\BiolinksThemes;
use Altum\Models\Domain;
use Altum\Title;

defined('ALTUMCODE') || die();

class Link extends Controller {
    public $link;

    private function normalize_whatsapp_phone($phone) {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    private function extract_user_phone_from_preferences($user) {
        if(!$user) {
            return '';
        }

        $preferences = is_string($user->preferences ?? null) ? json_decode($user->preferences ?? '{}') : ($user->preferences ?? (object) []);
        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        $meta = $preferences->meta ?? (object) [];
        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        return $this->normalize_whatsapp_phone($meta->phone ?? '');
    }

    private function get_default_whatsapp_phone_for_user($user) {
        if(!$user) {
            return '';
        }

        $referrer_phone = '';
        if(!empty($user->referred_by)) {
            $referrer_user = db()->where('user_id', (int) $user->referred_by)->getOne('users', ['preferences']);
            $referrer_phone = $this->extract_user_phone_from_preferences($referrer_user);
        }

        if($referrer_phone) {
            return $referrer_phone;
        }

        return $this->extract_user_phone_from_preferences($user);
    }

    private function get_ai_profile_values($preferences): array {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        $profile = $preferences->leader_ai_profile ?? null;

        if(is_array($profile)) {
            $profile = (object) $profile;
        }

        return [
            'primary_goal' => (string) ($profile->primary_goal ?? ''),
            'priority_offer' => (string) ($profile->priority_offer ?? ''),
            'active_channels' => is_array($profile->active_channels ?? null) ? array_values($profile->active_channels) : [],
            'available_time' => (string) ($profile->available_time ?? ''),
            'biggest_blocker' => (string) ($profile->biggest_blocker ?? ''),
            'communication_style' => (string) ($profile->communication_style ?? ''),
            'follow_up_readiness' => (string) ($profile->follow_up_readiness ?? ''),
            'weekly_change' => (string) ($profile->weekly_change ?? ''),
        ];
    }

    private function is_ai_profile_complete(array $values): bool {
        return (bool) ($values['primary_goal'] && $values['priority_offer'] && !empty($values['active_channels']) && $values['available_time'] && $values['biggest_blocker'] && $values['communication_style'] && $values['follow_up_readiness'] && $values['weekly_change']);
    }

    private function get_saved_ai_weekly_checkins($preferences): array {
        if(is_array($preferences)) {
            $preferences = json_decode(json_encode($preferences));
        }

        if(!$preferences instanceof \stdClass) {
            return [];
        }

        $checkins = $this->normalize_json_to_array($preferences->leader_ai_weekly_checkins ?? []);
        $normalized = [];

        foreach($checkins as $checkin) {
            if(!is_array($checkin)) {
                continue;
            }

            $normalized[] = [
                'submitted_at' => $checkin['submitted_at'] ?? null,
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_latest_saved_ai_weekly_checkin(array $weekly_checkins): ?array {
        return $weekly_checkins[0] ?? null;
    }

    private function get_saved_ai_weekly_plans($preferences): array {
        if(is_array($preferences)) {
            $preferences = json_decode(json_encode($preferences));
        }

        if(!$preferences instanceof \stdClass) {
            return [];
        }

        $plans = $this->normalize_json_to_array($preferences->leader_ai_weekly_plans ?? []);
        $normalized = [];

        foreach($plans as $plan) {
            if(!is_array($plan)) {
                continue;
            }

            $normalized[] = [
                'checkin_submitted_at' => $plan['checkin_submitted_at'] ?? null,
                'generated_at' => $plan['generated_at'] ?? null,
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['checkin_submitted_at'] ?? $b['generated_at'] ?? ''), (string) ($a['checkin_submitted_at'] ?? $a['generated_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_latest_saved_ai_weekly_plan(array $weekly_plans, ?array $latest_weekly_checkin): ?array {
        if(!$latest_weekly_checkin) {
            return $weekly_plans[0] ?? null;
        }

        $submitted_at = (string) ($latest_weekly_checkin['submitted_at'] ?? '');

        foreach($weekly_plans as $plan) {
            if((string) ($plan['checkin_submitted_at'] ?? '') === $submitted_at) {
                return $plan;
            }
        }

        return null;
    }

    private function get_saved_ai_weekly_outcomes($preferences): array {
        if(is_array($preferences)) {
            $preferences = json_decode(json_encode($preferences));
        }

        if(!$preferences instanceof \stdClass) {
            return [];
        }

        $outcomes = $this->normalize_json_to_array($preferences->leader_ai_weekly_outcomes ?? []);
        $normalized = [];

        foreach($outcomes as $outcome) {
            if(!is_array($outcome)) {
                continue;
            }

            $normalized[] = [
                'checkin_submitted_at' => $outcome['checkin_submitted_at'] ?? null,
                'plan_generated_at' => $outcome['plan_generated_at'] ?? null,
                'submitted_at' => $outcome['submitted_at'] ?? null,
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['checkin_submitted_at'] ?? $b['submitted_at'] ?? ''), (string) ($a['checkin_submitted_at'] ?? $a['submitted_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_latest_saved_ai_weekly_outcome(array $weekly_outcomes, ?array $latest_weekly_checkin): ?array {
        if(!$latest_weekly_checkin) {
            return $weekly_outcomes[0] ?? null;
        }

        $submitted_at = (string) ($latest_weekly_checkin['submitted_at'] ?? '');

        foreach($weekly_outcomes as $weekly_outcome) {
            if((string) ($weekly_outcome['checkin_submitted_at'] ?? '') === $submitted_at) {
                return $weekly_outcome;
            }
        }

        return null;
    }

    private function get_saved_ai_weekly_outcome_by_checkin(array $weekly_outcomes, string $submitted_at): ?array {
        foreach($weekly_outcomes as $weekly_outcome) {
            if((string) ($weekly_outcome['checkin_submitted_at'] ?? '') === $submitted_at) {
                return $weekly_outcome;
            }
        }

        return null;
    }

    private function get_saved_ai_weekly_outcome_for_plan(array $weekly_outcomes, ?array $weekly_plan): ?array {
        if(!$weekly_plan) {
            return null;
        }

        $submitted_at = (string) ($weekly_plan['checkin_submitted_at'] ?? '');

        if($submitted_at !== '') {
            $outcome = $this->get_saved_ai_weekly_outcome_by_checkin($weekly_outcomes, $submitted_at);

            if($outcome) {
                return $outcome;
            }
        }

        $generated_at = (string) ($weekly_plan['generated_at'] ?? '');

        if($generated_at === '') {
            return null;
        }

        foreach($weekly_outcomes as $weekly_outcome) {
            if((string) ($weekly_outcome['plan_generated_at'] ?? '') === $generated_at) {
                return $weekly_outcome;
            }
        }

        return null;
    }

    private function get_latest_saved_ai_plan_missing_outcome(array $weekly_plans, array $weekly_outcomes): ?array {
        foreach($weekly_plans as $weekly_plan) {
            if(!$this->get_saved_ai_weekly_outcome_for_plan($weekly_outcomes, $weekly_plan)) {
                return $weekly_plan;
            }
        }

        return null;
    }

    private function get_ai_overview_next_step_payload(int $link_id, $preferences, bool $has_ai_growth_plan_access, bool $is_profile_complete, bool $app_review_is_accessible): array {
        $profile_url = url('ai-plan?section=profile') . '#ai-plan-profile-start';
        $app_review_url = url('ai-plan?section=app_review&app_review_selected_link_id=' . $link_id) . '#ai-plan-app-review';
        $weekly_url = url('ai-plan?section=weekly');
        $plan_url = url('ai-plan?section=plan');

        if(!$has_ai_growth_plan_access) {
            return [
                'step_key' => 'locked',
                'step_number' => 0,
                'title' => l('links.app_review_cta_label'),
                'text' => l('global.info_message.plan_feature_no_access'),
                'button_label' => l('ai_plan.cta_go_app_review_direct'),
                'url' => '#',
                'is_accessible' => false,
                'locked_reason' => l('global.info_message.plan_feature_no_access'),
            ];
        }

        $latest_review_for_link = $this->get_latest_saved_ai_review_for_link($link_id, $preferences);
        $weekly_checkins = $this->get_saved_ai_weekly_checkins($preferences);
        $latest_weekly_checkin = $this->get_latest_saved_ai_weekly_checkin($weekly_checkins);
        $weekly_plans = $this->get_saved_ai_weekly_plans($preferences);
        $latest_weekly_plan = $this->get_latest_saved_ai_weekly_plan($weekly_plans, $latest_weekly_checkin);
        $weekly_outcomes = $this->get_saved_ai_weekly_outcomes($preferences);
        $latest_weekly_outcome = $this->get_latest_saved_ai_weekly_outcome($weekly_outcomes, $latest_weekly_checkin);
        $latest_pending_outcome_plan = $this->get_latest_saved_ai_plan_missing_outcome($weekly_plans, $weekly_outcomes);
        $new_weekly_cycle_ready = empty($latest_pending_outcome_plan) && !empty($latest_weekly_outcome);

        $current_step = 'profile';

        if($is_profile_complete && empty($latest_review_for_link)) {
            $current_step = 'app_review';
        }

        if(!empty($latest_review_for_link) && !$latest_weekly_checkin) {
            $current_step = 'weekly';
        }

        if($latest_weekly_checkin) {
            $current_step = 'plan';
        }

        if($new_weekly_cycle_ready) {
            $current_step = 'weekly';
        }

        $payload = [
            'step_key' => $current_step,
            'step_number' => 1,
            'title' => l('ai_plan.onboarding_step_1_title'),
            'text' => l('ai_plan.sidebar_next_step_profile'),
            'button_label' => l('ai_plan.cta_go_profile'),
            'url' => $profile_url,
            'is_accessible' => true,
            'locked_reason' => '',
        ];

        if($current_step === 'app_review') {
            $payload['step_number'] = 2;
            $payload['title'] = l('ai_plan.onboarding_step_2_title');
            $payload['text'] = l('ai_plan.sidebar_next_step_app_review');
            $payload['button_label'] = l('ai_plan.cta_go_app_review_direct');
            $payload['url'] = $app_review_url;
            $payload['is_accessible'] = $app_review_is_accessible;
            $payload['locked_reason'] = $app_review_is_accessible ? '' : l('ai_plan.app_review_locked_entry_tooltip');
        }

        if($current_step === 'weekly') {
            $payload['step_number'] = 3;
            $payload['title'] = l('ai_plan.onboarding_step_3_title');
            $payload['text'] = $new_weekly_cycle_ready ? l('ai_plan.weekly_cycle_next_done') : l('ai_plan.sidebar_next_step_weekly');
            $payload['button_label'] = l('ai_plan.cta_go_weekly');
            $payload['url'] = $weekly_url;
        }

        if($current_step === 'plan') {
            $payload['step_number'] = 4;
            $payload['title'] = $latest_weekly_plan && !$latest_weekly_outcome ? l('ai_plan.weekly_cycle_step_3_title') : l('ai_plan.onboarding_step_4_title');
            $payload['text'] = $latest_weekly_plan && !$latest_weekly_outcome ? l('ai_plan.sidebar_next_step_outcome') : l('ai_plan.sidebar_next_step_plan');
            $payload['button_label'] = l('ai_plan.cta_go_plan');
            $payload['url'] = $plan_url;
        }

        return $payload;
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

    private function normalize_json_to_array($value): array {
        if(is_string($value)) {
            $value = json_decode($value, true);
        } elseif(is_object($value)) {
            $value = json_decode(json_encode($value), true);
        }

        return is_array($value) ? $value : [];
    }

    private function extract_first_hex_color(string $value): string {
        if(preg_match('/#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})\b/', $value, $matches)) {
            return strtoupper($matches[0]);
        }

        return '';
    }

    private function normalize_ai_css_color($value, bool $allow_rgba = false): string {
        if(!is_scalar($value)) {
            return '';
        }

        $value = trim((string) $value);

        if($value === '') {
            return '';
        }

        if($allow_rgba && preg_match('/^rgba?\(\s*[\d.\s,%]+\)$/i', $value)) {
            return preg_replace('/\s+/', ' ', $value) ?? $value;
        }

        return $this->extract_first_hex_color($value);
    }

    private function normalize_ai_theme_pack($value): array {
        $value = $this->normalize_json_to_array($value);
        $available_fonts = array_keys((array) (settings()->links->biolinks_fonts ?? []));
        $font = trim((string) ($value['font'] ?? $value['font_key'] ?? ''));
        $font_size = (int) ($value['font_size'] ?? 0);
        $width = trim((string) ($value['width'] ?? $value['layout_width'] ?? ''));
        $block_spacing = trim((string) ($value['block_spacing'] ?? $value['spacing'] ?? ''));
        $hover_animation = trim((string) ($value['hover_animation'] ?? $value['hover_style'] ?? ''));

        return [
            'name' => trim((string) ($value['name'] ?? '')),
            'summary' => trim((string) ($value['summary'] ?? '')),
            'background_mode' => in_array((string) ($value['background_mode'] ?? 'color'), ['color', 'gradient'], true) ? (string) ($value['background_mode'] ?? 'color') : 'color',
            'background_color' => $this->normalize_ai_css_color($value['background_color'] ?? ''),
            'gradient_start' => $this->normalize_ai_css_color($value['gradient_start'] ?? ''),
            'gradient_end' => $this->normalize_ai_css_color($value['gradient_end'] ?? ''),
            'gradient_style' => (string) ($value['gradient_style'] ?? 'current_135deg'),
            'heading_color' => $this->normalize_ai_css_color($value['heading_color'] ?? ''),
            'text_color' => $this->normalize_ai_css_color($value['text_color'] ?? ''),
            'primary_block_text' => $this->normalize_ai_css_color($value['primary_block_text'] ?? ''),
            'primary_block_background' => $this->normalize_ai_css_color($value['primary_block_background'] ?? ''),
            'primary_block_border' => $this->normalize_ai_css_color($value['primary_block_border'] ?? ''),
            'primary_block_shadow' => $this->normalize_ai_css_color($value['primary_block_shadow'] ?? '', true),
            'secondary_blocks_text' => $this->normalize_ai_css_color($value['secondary_blocks_text'] ?? ''),
            'secondary_blocks_background' => $this->normalize_ai_css_color($value['secondary_blocks_background'] ?? ''),
            'secondary_blocks_border' => $this->normalize_ai_css_color($value['secondary_blocks_border'] ?? ''),
            'secondary_blocks_shadow' => $this->normalize_ai_css_color($value['secondary_blocks_shadow'] ?? '', true),
            'font' => in_array($font, $available_fonts, true) ? $font : '',
            'font_size' => $font_size >= 12 && $font_size <= 22 ? $font_size : 0,
            'width' => in_array($width, ['6', '8', '10', '12'], true) ? $width : '',
            'block_spacing' => in_array($block_spacing, ['1', '2', '3'], true) ? $block_spacing : '',
            'hover_animation' => in_array($hover_animation, ['false', 'smooth', 'instant'], true) ? $hover_animation : '',
            'migration_note' => trim((string) ($value['migration_note'] ?? '')),
        ];
    }

    private function strip_ai_visible_meta_copy(string $value): string {
        if($value === '') {
            return '';
        }

        $meta_note_pattern = '(?:kao\s+)?(?:sporedn(?:o|i|a|e)|sekundarn(?:o|i|a|e)|rezervn(?:o|i|a|e)|opcionaln(?:o|i|a|e)|zadnj(?:e|i|a)|primarn(?:o|i|a|e)|glavn(?:o|i|a|e)|fallback|backup|secondary|primary)(?:\s+(?:put|smjer|korak|opcija|path|route|step|blok|cta|gumb|link))?';

        $value = preg_replace_callback('/\s*\(([^()]*)\)/u', static function($matches) use ($meta_note_pattern) {
            $inner = trim((string) ($matches[1] ?? ''));

            if($inner !== '' && preg_match('/^' . $meta_note_pattern . '$/iu', $inner)) {
                return '';
            }

            return $matches[0];
        }, $value) ?? $value;

        $value = preg_replace('/\s*(?:[-,:]\s*)' . $meta_note_pattern . '\s*$/iu', '', $value) ?? $value;
        $value = preg_replace('/\s+([,.:!?])/u', '$1', $value) ?? $value;
        $value = preg_replace('/\s{2,}/u', ' ', $value) ?? $value;

        return trim($value, " \t\n\r\0\x0B-,:");
    }

    private function normalize_ai_visible_copy($value): string {
        if(!is_scalar($value)) {
            return '';
        }

        $value = $this->strip_ai_visible_meta_copy(trim((string) $value));

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function normalize_ai_ideal_block_order($value): array {
        $items = $this->normalize_json_to_array($value);
        $normalized = [];

        foreach($items as $item) {
            if(!is_scalar($item)) {
                continue;
            }

            $clean_item = $this->normalize_ai_visible_copy($item);

            if($clean_item === '' || in_array($clean_item, $normalized, true)) {
                continue;
            }

            $normalized[] = $clean_item;

            if(count($normalized) >= 8) {
                break;
            }
        }

        return $normalized;
    }

    private function normalize_ai_matching_key(string $value): string {
        $value = mb_strtolower(trim($value));

        if($value === '') {
            return '';
        }

        $value = strtr($value, [
            'č' => 'c',
            'ć' => 'c',
            'đ' => 'd',
            'š' => 's',
            'ž' => 'z',
        ]);

        return preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';
    }

    private function ai_text_has_any(string $value, array $needles): bool {
        $normalized_value = $this->normalize_ai_matching_key($value);

        if($normalized_value === '') {
            return false;
        }

        foreach($needles as $needle) {
            $normalized_needle = $this->normalize_ai_matching_key((string) $needle);

            if($normalized_needle !== '' && str_contains($normalized_value, $normalized_needle)) {
                return true;
            }
        }

        return false;
    }

    private function get_contextual_ai_link_copy_value(string $block_type, string $current_label): string {
        $current_label = trim($current_label);
        $is_business_offer = $this->ai_text_has_any($current_label, ['start paket', 'start-paket', 'suradnik', 'partner', 'upis', 'registracija', 'prijava']);
        $is_discount_offer = $this->ai_text_has_any($current_label, ['web shop', 'webshop', 'shop', 'popust', 'forever living', 'forever webshop', 'kupnja']);

        if($block_type === 'link_forever_shop') {
            if($this->ai_text_has_any($current_label, ['upis', 'prijava', 'registracija', 'partner', 'suradnik'])) {
                return 'Prijavi se kao Forever partner';
            }

            return 'Postani Forever partner';
        }

        if($this->ai_text_has_any($current_label, ['partner', 'suradnja', 'suradnik', 'upis', 'registracija', 'prijava'])) {
            if($this->ai_text_has_any($current_label, ['partner'])) {
                return 'Pogledaj kako postati partner';
            }

            if($this->ai_text_has_any($current_label, ['upis', 'prijava', 'registracija'])) {
                return 'Pogledaj kako izgleda upis';
            }

            return 'Saznaj kako izgleda suradnja';
        }

        if($block_type === 'link_forever_product' && $is_business_offer) {
            return 'Postani Forever suradnik';
        }

        if(
            $is_discount_offer
            || in_array($block_type, ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)
        ) {
            return 'Naruči proizvode bez registracije';
        }

        if($this->ai_text_has_any($current_label, ['proizvod', 'proizvodi']) || $block_type === 'link_forever_product') {
            return 'Pogledaj preporučene proizvode';
        }

        if($this->ai_text_has_any($current_label, ['whatsapp'])) {
            return 'Pošalji poruku na WhatsApp';
        }

        return 'Pogledaj više detalja';
    }

    private function should_force_contextual_ai_link_copy(string $suggested_value, string $current_label, string $block_type): bool {
        $suggested_value = trim($suggested_value);

        if($suggested_value === '') {
            return true;
        }

        if($this->ai_text_has_any($suggested_value, [
            'saznaj više i otvori sljedeći korak',
            'saznaj vise i otvori sljedeci korak',
            'otvori sljedeći korak',
            'otvori sljedeci korak',
            'sljedeći korak',
            'sljedeci korak',
            'glavni korak',
        ])) {
            return true;
        }

        if($block_type === 'link_forever_shop'
            && !$this->ai_text_has_any($suggested_value, ['forever', 'partner', 'suradnik', 'upis', 'prijava', 'registracija'])) {
            return true;
        }

        if($block_type === 'link_forever_product'
            && $this->ai_text_has_any($current_label, ['start paket', 'start-paket', 'partner', 'suradnik', 'upis', 'prijava', 'registracija'])
            && !$this->ai_text_has_any($suggested_value, ['suradnik', 'partner', 'upis', 'prijava', 'start'])) {
            return true;
        }

        if($this->ai_text_has_any($current_label, ['partner', 'suradnja', 'upis', 'prijava', 'registracija'])
            && !$this->ai_text_has_any($suggested_value, ['partner', 'suradnja', 'upis', 'prijava', 'registracija'])) {
            return true;
        }

        if((
                $this->ai_text_has_any($current_label, ['web shop', 'webshop', 'shop', 'popust', 'forever living', 'forever webshop', 'kupnja'])
                || in_array($block_type, ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)
            )
            && !$this->ai_text_has_any($suggested_value, ['shop', 'webshop', 'ponud', 'popust', 'forever', 'kup', 'proizvod']) ) {
            return true;
        }

        if(($this->ai_text_has_any($current_label, ['proizvod', 'proizvodi']) || $block_type === 'link_forever_product')
            && !$this->ai_text_has_any($suggested_value, ['proizvod', 'ponud'])) {
            return true;
        }

        return false;
    }

    private function get_default_ai_picker_context(string $block_type): array {
        return match(trim($block_type)) {
            'heading', 'header', 'avatar', 'image', 'paragraph', 'markdown', 'video', 'youtube', 'vimeo' => ['preferred_group' => 'start', 'preferred_goal' => 'trust', 'picker_search' => l('link.biolink.blocks.' . trim($block_type))],
            'lead_funnel' => ['preferred_group' => 'sales', 'preferred_goal' => 'lead_capture', 'picker_search' => 'Funnel'],
            'custom_html_whatsapp' => ['preferred_group' => 'contacts', 'preferred_goal' => 'lead_capture', 'picker_search' => 'WhatsApp'],
            'custom_html_chatbot', 'custom_html_chatbot_pets' => ['preferred_group' => 'forever', 'preferred_goal' => 'product_recommendation', 'picker_search' => l('link.biolink.blocks.' . trim($block_type))],
            'link_forever_shop' => ['preferred_group' => 'forever', 'preferred_goal' => 'lead_capture', 'picker_search' => l('link.biolink.blocks.' . trim($block_type))],
            'link_forever_product', 'link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo' => ['preferred_group' => 'forever', 'preferred_goal' => 'product_recommendation', 'picker_search' => l('link.biolink.blocks.' . trim($block_type))],
            'link' => ['preferred_group' => 'sales', 'preferred_goal' => 'lead_capture', 'picker_search' => l('link.biolink.blocks.link')],
            default => ['preferred_group' => '', 'preferred_goal' => '', 'picker_search' => l('link.biolink.blocks.' . trim($block_type))],
        };
    }

    private function get_ai_chatbot_block_types(): array {
        return ['custom_html_chatbot', 'custom_html_chatbot_pets'];
    }

    private function is_ai_chatbot_block_type(string $block_type): bool {
        return in_array(trim($block_type), $this->get_ai_chatbot_block_types(), true);
    }

    private function get_ai_fcc_start_paket_public_url(): string {
        return rtrim(SITE_URL, '/') . '/blog/start-paket';
    }

    private function get_ai_fcc_start_paket_seed_settings(): array {
        return [
            'name' => 'Postani Forever suradnik',
            'description' => 'Pogledaj kako izgleda start paket i koji je najbolji sljedeći korak za suradnju.',
            'location_url' => $this->get_ai_fcc_start_paket_public_url(),
            'product_translation_key' => 'start-paket',
            'product_language_mode' => 'app',
            'product_fallback_language_code' => 'hr',
        ];
    }

    private function is_ai_start_paket_business_offer_block(array $block): bool {
        $type = trim((string) ($block['type'] ?? ''));
        $label = trim((string) ($block['label'] ?? ''));
        $location_url = trim((string) ($block['location_url'] ?? ''));

        if($type !== 'link_forever_product') {
            return false;
        }

        return str_contains($location_url, '/blog/start-paket')
            || $this->ai_text_has_any($label . ' ' . $location_url, ['start paket', 'start-paket', 'partner', 'suradnik', 'upis', 'prijava', 'registracija']);
    }

    private function is_ai_protected_core_block(array $block): bool {
        $type = trim((string) ($block['type'] ?? ''));

        if($this->is_ai_chatbot_block_type($type)) {
            return true;
        }

        if(in_array($type, ['lead_funnel', 'custom_html_whatsapp', 'link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)) {
            return true;
        }

        return $this->is_ai_start_paket_business_offer_block($block);
    }

    private function get_ai_default_discount_seed_from_user_history(): array {
        $discount_block = db()->where('user_id', $this->user->user_id)
            ->where('type', ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], 'IN')
            ->orderBy('biolink_block_id', 'DESC')
            ->getOne('biolinks_blocks', ['location_url', 'settings']);

        if(!$discount_block) {
            return [];
        }

        $location_url = trim((string) ($discount_block->location_url ?? ''));
        if($location_url === '' || !str_starts_with(mb_strtolower($location_url), 'https://thealoeveraco.shop/')) {
            return [];
        }

        $settings = $this->normalize_json_to_array($discount_block->settings ?? null);

        return [
            'location_url' => $location_url,
            'apply_to_all_products' => (int) ($settings['apply_to_all_products'] ?? 1),
        ];
    }

    private function get_preferred_ai_chatbot_block_type(array $block_catalog = [], array $additional = []): string {
        foreach($block_catalog as $block) {
            $type = trim((string) ($block['type'] ?? ''));

            if($this->is_ai_chatbot_block_type($type)) {
                return $type;
            }
        }

        $review_summary = $this->normalize_json_to_array($additional['fcc_ai_review_summary'] ?? []);
        $context = implode(' ', array_filter([
            (string) ($review_summary['selected_app_name'] ?? ''),
            (string) ($review_summary['headline'] ?? ''),
            (string) ($review_summary['summary'] ?? ''),
        ]));

        return $this->ai_text_has_any($context, ['ljubim', 'ljubimac', 'ljubimci', 'pas', 'psi', 'mack', 'mačk', 'pet', 'pets', 'animal'])
            ? 'custom_html_chatbot_pets'
            : 'custom_html_chatbot';
    }

    private function get_default_ai_chatbot_missing_recommendation(array $block_catalog, array $additional = [], array $primary_block_plan = []): ?array {
        if(!empty($this->get_first_ai_catalog_block_by_types($block_catalog, $this->get_ai_chatbot_block_types(), false))) {
            return null;
        }

        $block_type = $this->get_preferred_ai_chatbot_block_type($block_catalog, $additional);
        $insert_after = $this->get_default_ai_missing_block_insert_after($block_type, $block_catalog, $primary_block_plan);

        return array_merge($insert_after, [
            'block_type' => $block_type,
            'role_key' => 'floating_ai_assistant',
            'label' => $block_type === 'custom_html_chatbot_pets' ? 'AI savjetnik za ljubimce' : 'AI savjetnik za preporuku proizvoda',
            'why' => 'AI savjetnik je jedan od glavnih FCC Pro benefita i treba ostati aktivan kao plutajuci popup alat za preporuku proizvoda i usmjeravanje prema pravim linkovima.',
            'priority' => 1,
            'allow_existing_type' => false,
            'preferred_group' => 'forever',
            'preferred_goal' => 'product_recommendation',
            'picker_search' => l('link.biolink.blocks.' . $block_type),
            'seed_settings' => [],
        ]);
    }

    private function get_default_ai_core_missing_recommendations(array $additional, array $block_catalog, array $primary_block_plan = []): array {
        $review_summary = $this->normalize_json_to_array($additional['fcc_ai_review_summary'] ?? []);
        $goal_type = trim((string) ($review_summary['goal_type'] ?? 'hybrid'));
        $funnel_label = $goal_type === 'shop' ? 'Zatraži preporuku i sljedeći korak' : 'Prijavi se i saznaj više';
        $discount_insert_after = $this->get_default_ai_missing_block_insert_after('link_discount', $block_catalog, $primary_block_plan);
        $business_insert_after = $this->get_default_ai_missing_block_insert_after('link_forever_product', $block_catalog, $primary_block_plan);
        $funnel_insert_after = $this->get_default_ai_missing_block_insert_after('lead_funnel', $block_catalog, $primary_block_plan);
        $whatsapp_insert_after = $this->get_default_ai_missing_block_insert_after('custom_html_whatsapp', $block_catalog, $primary_block_plan);

        return [
            array_merge($funnel_insert_after, [
                'block_type' => 'lead_funnel',
                'role_key' => 'primary_funnel',
                'label' => $funnel_label,
                'why' => 'Funnel mora ostati glavni FCC korak jer najjasnije vodi prema preporuci, prijavi ili ozbiljnom sljedecem potezu.',
                'priority' => 1,
                'preferred_group' => 'sales',
                'preferred_goal' => 'lead_capture',
                'picker_search' => 'Funnel',
                'seed_settings' => [
                    'name' => $funnel_label,
                    'popup_title' => $goal_type === 'shop' ? 'Zatraži preporuku i sljedeći korak' : 'Prijava za poslovnu suradnju',
                    'popup_subtitle' => $goal_type === 'shop'
                        ? 'Ostavi podatke i dobij najjednostavniji sljedeci korak za pravu preporuku proizvoda.'
                        : 'Ostavi podatke i dobij sljedeci korak bez lutanja.',
                    'thank_you_title' => 'Prijava je zaprimljena',
                    'thank_you_text' => 'Uskoro dobivas sljedeci korak i jasniji pregled sto dalje.',
                    'thank_you_button_text' => 'Nastavi dalje',
                ],
            ]),
            array_merge($discount_insert_after, [
                'block_type' => 'link_discount',
                'role_key' => 'core_discount_offer',
                'label' => 'Naruči proizvode bez registracije',
                'why' => 'Forever web shop blok za naručivanje bez registracije mora biti prisutan na svakoj FCC aplikaciji jer je to srce prodajnog dijela sustava.',
                'priority' => $goal_type === 'shop' ? 2 : 4,
                'preferred_group' => 'forever',
                'preferred_goal' => 'product_recommendation',
                'picker_search' => l('link.biolink.blocks.link_discount'),
                'seed_settings' => [
                    'name' => 'Naruči proizvode bez registracije',
                    'apply_to_all_products' => 1,
                ],
            ]),
            array_merge($business_insert_after, [
                'block_type' => 'link_forever_product',
                'role_key' => 'core_business_offer',
                'label' => 'Postani Forever suradnik',
                'why' => 'Blok "Postani Forever suradnik" mora biti prisutan na svakoj FCC aplikaciji jer vodi na Start Paket i cuva business put kroz referral sustav.',
                'priority' => $goal_type === 'shop' ? 4 : 2,
                'preferred_group' => 'forever',
                'preferred_goal' => 'lead_capture',
                'picker_search' => l('link.biolink.blocks.link_forever_product'),
                'seed_settings' => $this->get_ai_fcc_start_paket_seed_settings(),
            ]),
            array_merge($whatsapp_insert_after, [
                'block_type' => 'custom_html_whatsapp',
                'role_key' => 'whatsapp_backup',
                'label' => 'Pošalji poruku na WhatsApp',
                'why' => 'WhatsApp mora ostati kao jednostavan rezervni put za ljude koji ne zele odmah kroz Funnel.',
                'priority' => $goal_type === 'shop' ? 3 : 3,
                'preferred_group' => 'contacts',
                'preferred_goal' => 'lead_capture',
                'picker_search' => 'WhatsApp',
                'seed_settings' => [
                    'title' => 'Pošalji poruku na WhatsApp',
                    'message' => 'Javi se ako želiš kratko pojašnjenje prije sljedećeg koraka.',
                ],
            ]),
        ];
    }

    private function normalize_ai_missing_block_type(string $block_type): string {
        $block_type = trim($block_type);

        return in_array($block_type, ['video', 'tiktok_video', 'twitter_video', 'vk_video'], true)
            ? 'youtube'
            : $block_type;
    }

    private function ai_missing_recommendation_already_satisfied(array $item, array $block_catalog): bool {
        $role_key = trim((string) ($item['role_key'] ?? ''));

        if($role_key === 'owner_identity') {
            $target_label = trim((string) (($item['seed_settings']['text'] ?? '') ?: ($item['label'] ?? '')));
            $target_key = $this->normalize_ai_matching_key($target_label);

            if($target_key === '') {
                return false;
            }

            foreach($block_catalog as $block) {
                if((int) ($block['is_enabled'] ?? 0) !== 1) {
                    continue;
                }

                if((int) ($block['order'] ?? 999) > 4) {
                    continue;
                }

                if(!in_array((string) ($block['type'] ?? ''), ['heading', 'paragraph', 'markdown'], true)) {
                    continue;
                }

                $label_key = $this->normalize_ai_matching_key((string) ($block['label'] ?? ''));

                if($label_key !== '' && ($label_key === $target_key || str_contains($label_key, $target_key) || str_contains($target_key, $label_key))) {
                    return true;
                }
            }
        }

        if($role_key === 'trust_video') {
            foreach($block_catalog as $block) {
                if((int) ($block['is_enabled'] ?? 0) !== 1) {
                    continue;
                }

                if(in_array((string) ($block['type'] ?? ''), ['youtube', 'vimeo', 'video'], true)) {
                    return true;
                }
            }
        }

        if($role_key === 'core_discount_offer') {
            foreach($block_catalog as $block) {
                if((int) ($block['is_enabled'] ?? 0) !== 1) {
                    continue;
                }

                if(in_array((string) ($block['type'] ?? ''), ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)) {
                    return true;
                }
            }
        }

        if($role_key === 'core_business_offer') {
            foreach($block_catalog as $block) {
                if((int) ($block['is_enabled'] ?? 0) !== 1) {
                    continue;
                }

                if($this->is_ai_start_paket_business_offer_block($block)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function detect_ai_video_provider_from_url(string $location_url): string {
        $location_url = trim($location_url);

        if($location_url === '') {
            return '';
        }

        $host = mb_strtolower((string) parse_url($location_url, PHP_URL_HOST));
        $host = preg_replace('/^(www\.|m\.)/', '', $host) ?? $host;

        if(in_array($host, ['youtube.com', 'youtu.be'], true)) {
            return 'youtube';
        }

        if(in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            return 'vimeo';
        }

        return '';
    }

    private function get_ai_video_seed_defaults_from_catalog(array $block_catalog, string $fallback_type = 'youtube'): array {
        foreach($block_catalog as $block) {
            if((int) ($block['is_enabled'] ?? 0) !== 1) {
                continue;
            }

            $provider = $this->detect_ai_video_provider_from_url((string) ($block['location_url'] ?? ''));

            if($provider === '') {
                continue;
            }

            return [
                'block_type' => $provider,
                'location_url' => trim((string) ($block['location_url'] ?? '')),
                'title' => trim((string) ($block['label'] ?? '')),
            ];
        }

        return [
            'block_type' => $fallback_type,
            'location_url' => '',
            'title' => '',
        ];
    }

    private function match_pending_ai_copy_suggestion_to_block(array $item, array $block_catalog): int {
        $block_type = trim((string) ($item['block_type'] ?? ''));

        if($block_type === '') {
            return 0;
        }

        $candidates = array_values(array_filter($block_catalog, static function($block) use ($block_type): bool {
            return trim((string) ($block['type'] ?? '')) === $block_type && (int) ($block['is_enabled'] ?? 0) === 1;
        }));

        if(empty($candidates)) {
            return 0;
        }

        usort($candidates, static function($a, $b) {
            $order_compare = ((int) ($b['order'] ?? 0)) <=> ((int) ($a['order'] ?? 0));

            if($order_compare !== 0) {
                return $order_compare;
            }

            return ((int) ($b['block_id'] ?? 0)) <=> ((int) ($a['block_id'] ?? 0));
        });

        $role_key = trim((string) ($item['role_key'] ?? ''));
        $value = trim((string) ($item['value'] ?? ''));

        if($role_key === 'owner_identity') {
            $value_key = $this->normalize_ai_matching_key($value);

            foreach($candidates as $candidate) {
                $label_key = $this->normalize_ai_matching_key((string) ($candidate['label'] ?? ''));

                if($value_key !== '' && $label_key !== '' && ($label_key === $value_key || str_contains($label_key, $value_key) || str_contains($value_key, $label_key))) {
                    return (int) ($candidate['block_id'] ?? 0);
                }
            }
        }

        return (int) ($candidates[0]['block_id'] ?? 0);
    }

    private function refine_ai_copy_suggestion_for_catalog(array $item, array $block_catalog): array {
        $block_id = (int) ($item['block_id'] ?? 0);
        $block_type = trim((string) ($item['block_type'] ?? ''));
        $current_label = '';

        if($block_id <= 0) {
            $matched_block_id = $this->match_pending_ai_copy_suggestion_to_block($item, $block_catalog);

            if($matched_block_id > 0) {
                $item['block_id'] = $matched_block_id;
                $block_id = $matched_block_id;
            }
        }

        foreach($block_catalog as $block) {
            if((int) ($block['block_id'] ?? 0) !== $block_id) {
                continue;
            }

            $block_type = trim((string) ($block['type'] ?? $block_type));
            $current_label = trim((string) ($block['label'] ?? ''));
            break;
        }

        $item['block_type'] = $block_type;

        if(in_array($block_type, ['link', 'link_forever_shop', 'link_forever_product', 'link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)) {
            $value = trim((string) ($item['value'] ?? ''));

            if($this->should_force_contextual_ai_link_copy($value, $current_label, $block_type)) {
                $item['value'] = $this->get_contextual_ai_link_copy_value($block_type, $current_label);
            }
        }

        $item['value'] = $this->normalize_ai_visible_copy($item['value'] ?? '');

        return $item;
    }

    private function normalize_ai_primary_block_plan($value): array {
        $value = $this->normalize_json_to_array($value);

        return [
            'block_id' => (int) ($value['block_id'] ?? 0),
            'block_type' => trim((string) ($value['block_type'] ?? $value['type'] ?? '')),
            'label' => $this->normalize_ai_visible_copy($value['label'] ?? ''),
            'reason' => trim((string) ($value['reason'] ?? '')),
            'emphasis' => in_array((string) ($value['emphasis'] ?? 'strong'), ['soft', 'balanced', 'strong'], true) ? (string) ($value['emphasis'] ?? 'strong') : 'strong',
            'apply_theme_emphasis' => !array_key_exists('apply_theme_emphasis', $value) || filter_var($value['apply_theme_emphasis'], FILTER_VALIDATE_BOOLEAN),
        ];
    }

    private function normalize_ai_copy_suggestions($value): array {
        $items = $this->normalize_json_to_array($value);
        $allowed_fields = ['name', 'title', 'button_text', 'thank_you_button_text', 'description', 'text', 'message', 'popup_title', 'popup_subtitle', 'thank_you_title', 'thank_you_text'];
        $normalized = [];

        foreach($items as $item) {
            if(!is_array($item)) {
                continue;
            }

            $copy_value = $this->normalize_ai_visible_copy($item['value'] ?? '');

            if($copy_value === '') {
                continue;
            }

            $field = trim((string) ($item['field'] ?? 'name'));
            if(!in_array($field, $allowed_fields, true)) {
                $field = 'name';
            }

            $normalized[] = [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'block_type' => trim((string) ($item['block_type'] ?? $item['type'] ?? '')),
                'role_key' => trim((string) ($item['role_key'] ?? $item['semantic_role'] ?? '')),
                'field' => $field,
                'label' => $this->normalize_ai_visible_copy($item['label'] ?? ''),
                'value' => $copy_value,
                'reason' => trim((string) ($item['reason'] ?? '')),
                'case_style' => trim((string) ($item['case_style'] ?? 'sentence')),
            ];
        }

        return array_slice($normalized, 0, 8);
    }

    private function normalize_ai_missing_block_seed_settings($value): array {
        $value = $this->normalize_json_to_array($value);
        $allowed_keys = [
            'name',
            'title',
            'text',
            'message',
            'button_text',
            'description',
            'popup_title',
            'popup_subtitle',
            'thank_you_title',
            'thank_you_text',
            'thank_you_button_text',
            'open_mode',
            'location_url',
            'product_translation_key',
            'product_language_mode',
            'product_language_code',
            'product_fallback_language_code',
            'product_image_url',
        ];
        $normalized = [];

        foreach($allowed_keys as $key) {
            if(!array_key_exists($key, $value) || !is_scalar($value[$key])) {
                continue;
            }

            $normalized_value = in_array($key, ['location_url', 'product_translation_key', 'product_language_mode', 'product_language_code', 'product_fallback_language_code', 'product_image_url'], true)
                ? trim((string) $value[$key])
                : $this->normalize_ai_visible_copy($value[$key]);

            if($normalized_value === '') {
                continue;
            }

            $normalized[$key] = $normalized_value;
        }

        if(array_key_exists('product_blog_post_id', $value)) {
            $product_blog_post_id = (int) ($value['product_blog_post_id'] ?? 0);

            if($product_blog_post_id > 0) {
                $normalized['product_blog_post_id'] = $product_blog_post_id;
            }
        }

        if(array_key_exists('apply_to_all_products', $value)) {
            $normalized['apply_to_all_products'] = (int) !empty($value['apply_to_all_products']);
        }

        return $normalized;
    }

    private function normalize_ai_missing_block_recommendations($value): array {
        $items = $this->normalize_json_to_array($value);
        $normalized = [];

        foreach($items as $index => $item) {
            if(!is_array($item)) {
                continue;
            }

            $block_type = trim((string) ($item['block_type'] ?? $item['type'] ?? ''));
            $why = trim((string) ($item['why'] ?? $item['reason'] ?? ''));

            if($block_type === '' || $why === '') {
                continue;
            }

            $priority = max(1, min(9, (int) ($item['priority'] ?? ($index + 1))));
            $insert_after_block_id = max(0, (int) ($item['insert_after_block_id'] ?? 0));
            $insert_after_type = trim((string) ($item['insert_after_type'] ?? ''));
            $insert_after_label = $this->normalize_ai_visible_copy($item['insert_after_label'] ?? '');
            $label = $this->normalize_ai_visible_copy($item['label'] ?? $item['name'] ?? '');
            $seed_settings = $this->normalize_ai_missing_block_seed_settings($item['seed_settings'] ?? []);

            $normalized[] = [
                'recommendation_key' => trim((string) ($item['recommendation_key'] ?? '')),
                'block_type' => $block_type,
                'role_key' => trim((string) ($item['role_key'] ?? $item['semantic_role'] ?? '')),
                'label' => $label,
                'why' => $why,
                'priority' => $priority,
                'insert_after_block_id' => $insert_after_block_id,
                'insert_after_type' => $insert_after_type,
                'insert_after_label' => $insert_after_label,
                'allow_existing_type' => !empty($item['allow_existing_type']),
                'preferred_group' => trim((string) ($item['preferred_group'] ?? '')),
                'preferred_goal' => trim((string) ($item['preferred_goal'] ?? '')),
                'picker_search' => $this->normalize_ai_visible_copy($item['picker_search'] ?? ''),
                'seed_settings' => $seed_settings,
            ];
        }

        return array_slice($normalized, 0, 6);
    }

    private function normalize_ai_layout_actions($value): array {
        $items = $this->normalize_json_to_array($value);
        $normalized = [];

        foreach($items as $item) {
            if(!is_array($item) || empty($item['action']) || empty($item['why'])) {
                continue;
            }

            $normalized[] = [
                'action' => trim((string) ($item['action'] ?? '')),
                'block_id' => (int) ($item['block_id'] ?? 0),
                'block_type' => trim((string) ($item['block_type'] ?? $item['type'] ?? '')),
                'label' => $this->normalize_ai_visible_copy($item['label'] ?? ''),
                'why' => trim((string) ($item['why'] ?? '')),
            ];
        }

        return array_slice($normalized, 0, 8);
    }

    private function get_ai_copy_supported_fields_by_block_type(string $block_type): array {
        $block_type = trim($block_type);

        return match($block_type) {
            'custom_html', 'code', 'iframe', 'divider', 'loading', 'custom_html_chatbot', 'custom_html_chatbot_pets' => [],
            'custom_html_whatsapp' => ['title', 'name', 'message', 'description', 'text'],
            'youtube', 'vimeo' => ['title', 'name'],
            'lead_funnel' => ['name', 'button_text', 'description', 'popup_title', 'popup_subtitle', 'thank_you_title', 'thank_you_text', 'thank_you_button_text'],
            'heading' => ['text'],
            'paragraph', 'markdown' => ['text'],
            'modal_text' => ['name', 'text', 'button_text'],
            default => ['name', 'button_text', 'thank_you_button_text', 'description', 'text', 'popup_title', 'popup_subtitle', 'thank_you_title', 'thank_you_text'],
        };
    }

    private function get_ai_missing_block_recommendation_semantic_key(array $recommendation): string {
        $block_type = trim((string) ($recommendation['block_type'] ?? ''));
        $role_key = trim((string) ($recommendation['role_key'] ?? ''));
        $label = trim((string) ($recommendation['label'] ?? ''));
        $seed_settings = $this->normalize_ai_missing_block_seed_settings($recommendation['seed_settings'] ?? []);
        $start_paket_context = $label . ' ' . ($seed_settings['name'] ?? '') . ' ' . ($seed_settings['location_url'] ?? '');

        if($role_key !== '' && in_array($role_key, ['owner_identity', 'trust_video', 'core_discount_offer', 'core_business_offer', 'primary_funnel', 'floating_ai_assistant'], true)) {
            return 'role:' . $role_key;
        }

        if(in_array($block_type, ['lead_funnel', 'custom_html_whatsapp', 'custom_html_chatbot', 'custom_html_chatbot_pets', 'youtube', 'vimeo', 'link_discount'], true)) {
            return 'singleton:' . $block_type;
        }

        if($block_type === 'link_forever_product' && $this->ai_text_has_any($start_paket_context, ['start paket', 'start-paket', 'partner', 'suradnik', 'upis', 'prijava', 'registracija'])) {
            return 'role:core_business_offer';
        }

        return '';
    }

    private function get_ai_missing_block_recommendation_key(array $recommendation): string {
        $semantic_key = $this->get_ai_missing_block_recommendation_semantic_key($recommendation);

        if($semantic_key !== '') {
            return 'ai_missing_' . substr(sha1($semantic_key), 0, 12);
        }

        $seed = implode('|', [
            trim((string) ($recommendation['block_type'] ?? '')),
            trim((string) ($recommendation['label'] ?? '')),
            trim((string) ($recommendation['why'] ?? '')),
            (string) ((int) ($recommendation['priority'] ?? 0)),
            trim((string) ($recommendation['insert_after_type'] ?? '')),
            trim((string) ($recommendation['insert_after_label'] ?? '')),
        ]);

        return 'ai_missing_' . substr(sha1($seed), 0, 12);
    }

    private function get_ai_editor_block_catalog(int $link_id): array {
        if($link_id <= 0) {
            return [];
        }

        $result = database()->query("SELECT `biolink_block_id`, `type`, `location_url`, `settings`, `order`, `is_enabled`
            FROM `biolinks_blocks`
            WHERE `user_id` = {$this->user->user_id}
              AND `link_id` = {$link_id}
            ORDER BY `order` ASC, `biolink_block_id` ASC");
        $catalog = [];

        if($result) {
            while($row = $result->fetch_object()) {
                $settings = $this->decode_biolink_block_settings($row->settings ?? null);
                $catalog[] = [
                    'block_id' => (int) ($row->biolink_block_id ?? 0),
                    'type' => (string) ($row->type ?? ''),
                    'label' => $this->get_ai_block_preview_label((string) ($row->type ?? ''), $settings),
                    'location_url' => trim((string) ($row->location_url ?? '')),
                    'order' => (int) ($row->order ?? 0),
                    'is_enabled' => (int) ($row->is_enabled ?? 0),
                ];
            }
        }

        return $catalog;
    }

    private function get_latest_saved_ai_review_for_link(int $link_id, $preferences = null): array {
        if($link_id <= 0) {
            return [];
        }

        if($preferences === null) {
            $preferences = $this->user->preferences ?? null;
        }

        if(is_array($preferences)) {
            $preferences = json_decode(json_encode($preferences));
        }

        if(!$preferences instanceof \stdClass) {
            return [];
        }

        $reviews = $this->normalize_json_to_array($preferences->leader_ai_app_reviews ?? []);
        $latest_review = [];
        $latest_generated_at = '';

        foreach($reviews as $review) {
            if(!is_array($review) || (int) ($review['selected_link_id'] ?? 0) !== $link_id) {
                continue;
            }

            $generated_at = trim((string) ($review['generated_at'] ?? ''));

            if($latest_generated_at === '' || ($generated_at !== '' && strcmp($generated_at, $latest_generated_at) > 0)) {
                $latest_generated_at = $generated_at;
                $latest_review = $review;
            }
        }

        return $latest_review;
    }

    private function get_ai_ideal_block_order(array $additional, int $link_id = 0, $preferences = null): array {
        $direct_order = $this->normalize_ai_ideal_block_order($additional['fcc_ai_ideal_block_order'] ?? []);

        if(!empty($direct_order)) {
            return $direct_order;
        }

        $review_summary = $this->normalize_json_to_array($additional['fcc_ai_review_summary'] ?? []);
        $summary_order = $this->normalize_ai_ideal_block_order($review_summary['ideal_block_order'] ?? []);

        if(!empty($summary_order)) {
            return $summary_order;
        }

        $latest_review = $this->get_latest_saved_ai_review_for_link($link_id, $preferences);

        return $this->normalize_ai_ideal_block_order($latest_review['ideal_block_order'] ?? []);
    }

    private function get_first_ai_catalog_block_by_types(array $block_catalog, array $types, bool $enabled_only = true): array {
        foreach($block_catalog as $block) {
            if($enabled_only && (int) ($block['is_enabled'] ?? 0) !== 1) {
                continue;
            }

            if(in_array((string) ($block['type'] ?? ''), $types, true)) {
                return $block;
            }
        }

        return [];
    }

    private function get_ai_preferred_webshop_catalog_block(array $block_catalog): array {
        $discount_block = $this->get_first_ai_catalog_block_by_types($block_catalog, ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo']);
        if(!empty($discount_block)) {
            return $discount_block;
        }

        return $this->get_first_ai_catalog_block_by_types($block_catalog, ['link_forever_shop']);
    }

    private function get_ai_owner_identity_catalog_block(array $block_catalog): array {
        $fallback = [];

        foreach($block_catalog as $block) {
            if((int) ($block['is_enabled'] ?? 0) !== 1 || (string) ($block['type'] ?? '') !== 'heading') {
                continue;
            }

            $label = trim((string) ($block['label'] ?? ''));
            if($label === '') {
                continue;
            }

            if(empty($fallback)) {
                $fallback = $block;
            }

            $word_count = count(array_filter(preg_split('/\s+/u', $label) ?: []));
            $looks_like_name = $word_count >= 2
                && $word_count <= 4
                && mb_strlen($label) <= 48
                && !$this->ai_text_has_any($label, ['saznaj', 'prijavi', 'prijava', 'partner', 'suradnja', 'whatsapp', 'shop', 'webshop', 'popust', 'proizvod', 'video', 'ebook', 'e-book']);

            if($looks_like_name) {
                return $block;
            }
        }

        return $fallback;
    }

    private function is_ai_contact_goal_context(array $additional, array $primary_block_plan, array $missing_block_recommendations = []): bool {
        $review_summary = $this->normalize_json_to_array($additional['fcc_ai_review_summary'] ?? []);
        $goal_type = trim((string) ($review_summary['goal_type'] ?? ''));

        if(in_array($goal_type, ['business', 'activation', 'contact', 'recruitment', 'partnership'], true)) {
            return true;
        }

        if(in_array((string) ($primary_block_plan['block_type'] ?? ''), ['lead_funnel', 'custom_html_whatsapp'], true)) {
            return true;
        }

        foreach($missing_block_recommendations as $item) {
            $role_key = trim((string) ($item['role_key'] ?? ''));
            $block_type = trim((string) ($item['block_type'] ?? ''));

            if($role_key === 'primary_funnel' || $block_type === 'lead_funnel') {
                return true;
            }
        }

        return false;
    }

    private function get_effective_ai_primary_block_plan(array $additional, array $block_catalog, array $missing_block_recommendations = [], int $link_id = 0, $preferences = null): array {
        $primary_block_plan = $this->normalize_ai_primary_block_plan($additional['fcc_ai_primary_block_plan'] ?? []);
        $ideal_block_order = $this->get_ai_ideal_block_order($additional, $link_id, $preferences);

        $find_plan_block = function(string $item, array $excluded_ids = []) use ($block_catalog): array {
            $item = trim($item);
            if($item === '') {
                return [];
            }

            $available_blocks = array_values(array_filter($block_catalog, static function($block) use ($excluded_ids): bool {
                return (int) ($block['is_enabled'] ?? 0) === 1 && !in_array((int) ($block['block_id'] ?? 0), $excluded_ids, true);
            }));

            if(empty($available_blocks)) {
                return [];
            }

            $find_by_types = static function(array $types) use ($available_blocks): array {
                foreach($available_blocks as $block) {
                    if(in_array((string) ($block['type'] ?? ''), $types, true)) {
                        return $block;
                    }
                }

                return [];
            };

            $item_key = $this->normalize_ai_matching_key($item);
            $looks_like_name = count(array_filter(preg_split('/\s+/u', $item) ?: [])) >= 2
                && count(array_filter(preg_split('/\s+/u', $item) ?: [])) <= 4
                && !$this->ai_text_has_any($item, ['avatar', 'fotografija', 'video', 'whatsapp', 'društvene', 'drustvene', 'shop', 'webshop', 'proizvod', 'prijava', 'funnel']);

            if($this->ai_text_has_any($item, ['avatar', 'profilna', 'fotografija', 'fotka', 'slika'])) {
                return $find_by_types(['avatar', 'image', 'header']);
            }

            if($this->ai_text_has_any($item, ['ime i prezime', 'puno ime', 'prezime']) || $looks_like_name) {
                $owner_block = $this->get_ai_owner_identity_catalog_block($available_blocks);
                if(!empty($owner_block)) {
                    return $owner_block;
                }
            }

            if($this->ai_text_has_any($item, ['trust', 'povjerenje', 'uvod', 'kratka poruka', 'kratki naslov', 'odlomak'])) {
                $trust_block = $find_by_types(['paragraph', 'markdown', 'heading']);
                if(!empty($trust_block)) {
                    return $trust_block;
                }
            }

            if($this->ai_text_has_any($item, ['start paket', 'start-paket', 'partner', 'suradnik', 'postani forever', 'upis'])) {
                foreach($available_blocks as $block) {
                    if($this->is_ai_start_paket_business_offer_block($block)) {
                        return $block;
                    }
                }
            }

            if($this->ai_text_has_any($item, ['video', 'vimeo', 'youtube'])) {
                return $find_by_types(['video', 'youtube', 'vimeo']);
            }

            if($this->ai_text_has_any($item, ['prijava', 'funnel', 'formular', 'obrazac', 'suradnja'])) {
                $funnel_block = $find_by_types(['lead_funnel']);
                if(!empty($funnel_block)) {
                    return $funnel_block;
                }
            }

            if($this->ai_text_has_any($item, ['whatsapp'])) {
                $whatsapp_block = $find_by_types(['custom_html_whatsapp']);
                if(!empty($whatsapp_block)) {
                    return $whatsapp_block;
                }
            }

            if($this->ai_text_has_any($item, ['društvene', 'drustvene', 'mreže', 'mreze', 'social', 'kontakti'])) {
                $socials_block = $find_by_types(['socials']);
                if(!empty($socials_block)) {
                    return $socials_block;
                }
            }

            if($this->ai_text_has_any($item, ['webshop', 'web shop', 'shop', 'popust', 'forever webshop'])) {
                $shop_block = $this->get_ai_preferred_webshop_catalog_block($available_blocks);
                if(!empty($shop_block)) {
                    return $shop_block;
                }
            }

            if($this->ai_text_has_any($item, ['proizvod', 'proizvodi'])) {
                $product_block = $find_by_types(['link_forever_product', 'link']);
                if(!empty($product_block)) {
                    return $product_block;
                }
            }

            foreach($available_blocks as $block) {
                $label_key = $this->normalize_ai_matching_key((string) ($block['label'] ?? ''));

                if($item_key !== '' && $label_key !== '' && ($label_key === $item_key || str_contains($label_key, $item_key) || str_contains($item_key, $label_key))) {
                    return $block;
                }
            }

            return [];
        };

        if(!empty($ideal_block_order)) {
            $used_ids = [];

            foreach($ideal_block_order as $item) {
                $block = $find_plan_block((string) $item, $used_ids);

                if(empty($block)) {
                    continue;
                }

                $used_ids[] = (int) ($block['block_id'] ?? 0);
                $type = (string) ($block['type'] ?? '');
                $label = trim((string) ($block['label'] ?? ''));
                $is_action_block = in_array($type, ['lead_funnel', 'custom_html_whatsapp', 'link_forever_shop', 'link_discount', 'link_forever_product', 'link'], true)
                    || $this->ai_text_has_any($label, ['prijava', 'whatsapp', 'partner', 'shop', 'webshop', 'popust', 'proizvod']);

                if(!$is_action_block) {
                    continue;
                }

                return [
                    'block_id' => (int) ($block['block_id'] ?? 0),
                    'block_type' => $type,
                    'label' => $this->normalize_ai_visible_copy($label),
                    'reason' => 'Glavni blok sada slijedi stvarni AI plan za ovu aplikaciju, a ne univerzalni fallback raspored.',
                    'emphasis' => 'strong',
                    'apply_theme_emphasis' => true,
                ];
            }
        }

        return $primary_block_plan;
    }

    private function build_effective_ai_layout_actions(array $additional, array $block_catalog, array $primary_block_plan, array $missing_block_recommendations = [], int $link_id = 0, $preferences = null): array {
        $layout_actions = $this->normalize_ai_layout_actions($additional['fcc_ai_layout_actions'] ?? []);
        $ideal_block_order = $this->get_ai_ideal_block_order($additional, $link_id, $preferences);
        $is_contact_goal = $this->is_ai_contact_goal_context($additional, $primary_block_plan, $missing_block_recommendations);
        $primary_block_id = (int) ($primary_block_plan['block_id'] ?? 0);

        $effective_actions = [];
        $append_action = function(string $action, array $block, string $why) use (&$effective_actions) {
            $block_id = (int) ($block['block_id'] ?? 0);

            if($block_id <= 0) {
                return;
            }

            foreach($effective_actions as $existing_action) {
                if((int) ($existing_action['block_id'] ?? 0) === $block_id) {
                    return;
                }
            }

            $effective_actions[] = [
                'action' => $action,
                'block_id' => $block_id,
                'block_type' => (string) ($block['type'] ?? ''),
                'label' => $this->normalize_ai_visible_copy($block['label'] ?? ''),
                'why' => $why,
            ];
        };

        $hero_visual_block = $this->get_first_ai_catalog_block_by_types($block_catalog, ['avatar', 'image', 'header']);
        $owner_identity_block = $this->get_ai_owner_identity_catalog_block($block_catalog);
        $append_action('keep_top', $hero_visual_block, 'Profilna fotografija ili avatar trebaju ostati prvi trust signal na vrhu aplikacije.');
        $append_action('keep_top', $owner_identity_block, 'Puno ime i prezime trebaju odmah slijediti iza avatara kako bi osoba odmah znala kome vjeruje.');
        $plan_sequence = [];

        if(!empty($ideal_block_order)) {
            $used_ids = array_values(array_filter([
                (int) ($hero_visual_block['block_id'] ?? 0),
                (int) ($owner_identity_block['block_id'] ?? 0),
            ]));

            foreach($ideal_block_order as $item) {
                $item = trim((string) $item);

                if($item === '') {
                    continue;
                }

                $available_blocks = array_values(array_filter($block_catalog, static function($block) use ($used_ids): bool {
                    return (int) ($block['is_enabled'] ?? 0) === 1 && !in_array((int) ($block['block_id'] ?? 0), $used_ids, true);
                }));

                if(empty($available_blocks)) {
                    break;
                }

                $matched_block = [];
                $item_key = $this->normalize_ai_matching_key($item);

                $find_by_types = static function(array $types) use ($available_blocks): array {
                    foreach($available_blocks as $block) {
                        if(in_array((string) ($block['type'] ?? ''), $types, true)) {
                            return $block;
                        }
                    }

                    return [];
                };

                $looks_like_name = count(array_filter(preg_split('/\s+/u', $item) ?: [])) >= 2
                    && count(array_filter(preg_split('/\s+/u', $item) ?: [])) <= 4
                    && !$this->ai_text_has_any($item, ['avatar', 'fotografija', 'video', 'whatsapp', 'društvene', 'drustvene', 'shop', 'webshop', 'proizvod', 'prijava', 'funnel']);

                if($this->ai_text_has_any($item, ['avatar', 'profilna', 'fotografija', 'fotka', 'slika'])) {
                    $matched_block = $find_by_types(['avatar', 'image', 'header']);
                } elseif($this->ai_text_has_any($item, ['ime i prezime', 'puno ime', 'prezime']) || $looks_like_name) {
                    $matched_block = $this->get_ai_owner_identity_catalog_block($available_blocks);
                } elseif($this->ai_text_has_any($item, ['trust', 'povjerenje', 'uvod', 'kratka poruka', 'kratki naslov', 'odlomak'])) {
                    $matched_block = $find_by_types(['paragraph', 'markdown', 'heading']);
                } elseif($this->ai_text_has_any($item, ['start paket', 'start-paket', 'partner', 'suradnik', 'postani forever', 'upis'])) {
                    foreach($available_blocks as $block) {
                        if($this->is_ai_start_paket_business_offer_block($block)) {
                            $matched_block = $block;
                            break;
                        }
                    }
                } elseif($this->ai_text_has_any($item, ['video', 'vimeo', 'youtube'])) {
                    $matched_block = $find_by_types(['video', 'youtube', 'vimeo']);
                } elseif($this->ai_text_has_any($item, ['prijava', 'funnel', 'formular', 'obrazac', 'suradnja'])) {
                    $matched_block = $find_by_types(['lead_funnel']);
                } elseif($this->ai_text_has_any($item, ['whatsapp'])) {
                    $matched_block = $find_by_types(['custom_html_whatsapp']);
                } elseif($this->ai_text_has_any($item, ['društvene', 'drustvene', 'mreže', 'mreze', 'social', 'kontakti'])) {
                    $matched_block = $find_by_types(['socials']);
                } elseif($this->ai_text_has_any($item, ['webshop', 'web shop', 'shop', 'popust', 'forever webshop'])) {
                    $matched_block = $this->get_ai_preferred_webshop_catalog_block($available_blocks);
                } elseif($this->ai_text_has_any($item, ['proizvod', 'proizvodi'])) {
                    $matched_block = $find_by_types(['link_forever_product', 'link']);
                }

                if(empty($matched_block)) {
                    foreach($available_blocks as $block) {
                        $label_key = $this->normalize_ai_matching_key((string) ($block['label'] ?? ''));

                        if($item_key !== '' && $label_key !== '' && ($label_key === $item_key || str_contains($label_key, $item_key) || str_contains($item_key, $label_key))) {
                            $matched_block = $block;
                            break;
                        }
                    }
                }

                if(empty($matched_block)) {
                    continue;
                }

                $plan_sequence[] = $matched_block;
                $used_ids[] = (int) ($matched_block['block_id'] ?? 0);
            }
        }

        if(!empty($plan_sequence)) {
            $before_primary = $primary_block_id > 0;
            $sequence_ids = array_map(static fn($block): int => (int) ($block['block_id'] ?? 0), $plan_sequence);
            $primary_in_sequence = in_array($primary_block_id, $sequence_ids, true);

            foreach($plan_sequence as $block) {
                $block_id = (int) ($block['block_id'] ?? 0);

                if($block_id === $primary_block_id) {
                    $before_primary = false;
                    continue;
                }

                if($before_primary) {
                    $append_action('keep_top', $block, 'Ovaj blok AI plan stavlja prije glavnog koraka za ovu konkretnu aplikaciju.');
                } else {
                    $append_action('keep_after_primary', $block, 'Ovaj blok AI plan stavlja nakon glavnog koraka za ovu konkretnu aplikaciju.');
                }
            }

            if(!$primary_in_sequence) {
                $before_primary = false;
            }
        }

        if($is_contact_goal) {
            $sequence_ids = array_map(static fn($block): int => (int) ($block['block_id'] ?? 0), $plan_sequence);
            $socials_block = $this->get_first_ai_catalog_block_by_types($block_catalog, ['socials']);

            if(!empty($socials_block) && !in_array((int) ($socials_block['block_id'] ?? 0), $sequence_ids, true)) {
                $append_action('keep_after_primary', $socials_block, 'Društvene mreže i kontakti ne trebaju biti iznad glavnog koraka ako ih plan nije tamo stavio.');
            }

            foreach($block_catalog as $block) {
                if((int) ($block['is_enabled'] ?? 0) !== 1 || in_array((int) ($block['block_id'] ?? 0), $sequence_ids, true)) {
                    continue;
                }

                $type = (string) ($block['type'] ?? '');
                $label = trim((string) ($block['label'] ?? ''));
                $is_product_or_shop_block = in_array($type, ['link_forever_shop', 'link_discount', 'link_forever_product'], true)
                    || ($type === 'link' && $this->ai_text_has_any($label, ['proizvod', 'proizvodi', 'shop', 'webshop', 'popust']));

                if($is_product_or_shop_block) {
                    $append_action('move_down', $block, 'Ako plan nije stavio ovaj blok ranije, treba ostati niže od glavnog koraka.');
                }
            }

        }

        foreach($layout_actions as $action) {
            $block_id = (int) ($action['block_id'] ?? 0);
            $block_type = trim((string) ($action['block_type'] ?? ''));
            $action_key = trim((string) ($action['action'] ?? ''));
            $is_protected_core_block = $this->is_ai_protected_core_block([
                'type' => $block_type,
                'label' => (string) ($action['label'] ?? ''),
                'location_url' => '',
            ]);

            if($this->is_ai_chatbot_block_type($block_type)) {
                continue;
            }

            if($block_id > 0) {
                foreach($block_catalog as $catalog_block) {
                    if((int) ($catalog_block['block_id'] ?? 0) === $block_id) {
                        if($this->is_ai_chatbot_block_type((string) ($catalog_block['type'] ?? ''))) {
                            continue 2;
                        }

                        if($this->is_ai_protected_core_block($catalog_block)) {
                            $is_protected_core_block = true;
                        }
                    }
                }
            }

            if($is_protected_core_block && in_array($action_key, ['hide_for_now', 'consider_remove'], true)) {
                continue;
            }

            if($block_id <= 0) {
                $effective_actions[] = $action;
                continue;
            }

            $already_defined = false;
            foreach($effective_actions as $effective_action) {
                if((int) ($effective_action['block_id'] ?? 0) === $block_id) {
                    $already_defined = true;
                    break;
                }
            }

            if(!$already_defined) {
                $effective_actions[] = $action;
            }
        }

        return array_slice($effective_actions, 0, 12);
    }

    private function filter_ai_copy_suggestions_for_current_blocks(array $copy_suggestions, array $block_catalog, array $missing_block_recommendations = []): array {
        $block_map = [];
        foreach($block_catalog as $block) {
            $block_id = (int) ($block['block_id'] ?? 0);

            if($block_id <= 0) {
                continue;
            }

            $block_map[$block_id] = $block;
        }

        $missing_types = [];
        $missing_role_keys = [];
        foreach($missing_block_recommendations as $item) {
            $role_key = trim((string) ($item['role_key'] ?? ''));
            $block_type = trim((string) ($item['block_type'] ?? ''));

            if($role_key !== '') {
                $missing_role_keys[] = $role_key;
            } elseif($block_type !== '') {
                $missing_types[] = $block_type;
            }
        }

        $missing_types = array_values(array_unique($missing_types));
        $missing_role_keys = array_values(array_unique($missing_role_keys));
        $filtered = [];
        $seen = [];

        foreach($copy_suggestions as $item) {
            if(!is_array($item)) {
                continue;
            }

            $item = $this->refine_ai_copy_suggestion_for_catalog($item, $block_catalog);
            $block_id = (int) ($item['block_id'] ?? 0);
            $block_type = trim((string) ($item['block_type'] ?? ''));
            $role_key = trim((string) ($item['role_key'] ?? ''));

            if($block_id > 0 && isset($block_map[$block_id])) {
                $block_type = trim((string) ($block_map[$block_id]['type'] ?? $block_type));
            }

            if($block_type === '') {
                continue;
            }

            if($block_id <= 0) {
                if(($role_key !== '' && in_array($role_key, $missing_role_keys, true)) || ($role_key === '' && in_array($block_type, $missing_types, true))) {
                    continue;
                }
            }

            $allowed_fields = $this->get_ai_copy_supported_fields_by_block_type($block_type);

            if(empty($allowed_fields)) {
                continue;
            }

            if(!in_array((string) ($item['field'] ?? ''), $allowed_fields, true)) {
                continue;
            }

            $dedupe_key = implode('|', [$block_id, $block_type, (string) ($item['field'] ?? ''), (string) ($item['value'] ?? ''), $role_key]);

            if(isset($seen[$dedupe_key])) {
                continue;
            }

            $seen[$dedupe_key] = true;
            $filtered[] = $item;

            if(count($filtered) >= 8) {
                break;
            }
        }

        return $filtered;
    }

    private function get_default_ai_missing_block_insert_after(string $block_type, array $block_catalog, array $primary_block_plan = []): array {
        $enabled_blocks = array_values(array_filter($block_catalog, static fn($block): bool => (int) ($block['is_enabled'] ?? 0) === 1));
        $primary_block_id = (int) ($primary_block_plan['block_id'] ?? 0);

        if($block_type === 'lead_funnel') {
            foreach($enabled_blocks as $block) {
                if(in_array((string) ($block['type'] ?? ''), ['video', 'youtube', 'vimeo'], true)) {
                    return [
                        'insert_after_block_id' => (int) ($block['block_id'] ?? 0),
                        'insert_after_type' => (string) ($block['type'] ?? ''),
                        'insert_after_label' => (string) ($block['label'] ?? ''),
                    ];
                }
            }
        }

        if($primary_block_id > 0) {
            foreach($enabled_blocks as $block) {
                if((int) ($block['block_id'] ?? 0) === $primary_block_id) {
                    return [
                        'insert_after_block_id' => $primary_block_id,
                        'insert_after_type' => (string) ($block['type'] ?? ''),
                        'insert_after_label' => (string) ($block['label'] ?? ''),
                    ];
                }
            }
        }

        $first_block = $enabled_blocks[0] ?? null;

        return [
            'insert_after_block_id' => (int) ($first_block['block_id'] ?? 0),
            'insert_after_type' => (string) ($first_block['type'] ?? ''),
            'insert_after_label' => (string) ($first_block['label'] ?? ''),
        ];
    }

    private function build_ai_missing_block_recommendations(array $additional, array $block_catalog, array $primary_block_plan = [], array $copy_suggestions = []): array {
        $existing_types = [];
        foreach($block_catalog as $block) {
            $type = trim((string) ($block['type'] ?? ''));

            if($type === '') {
                continue;
            }

            $existing_types[$type] = ($existing_types[$type] ?? 0) + 1;
        }

        $recommendations = [];
        $register_recommendation = function(array $item) use (&$recommendations, $existing_types, $block_catalog, $primary_block_plan, $copy_suggestions) {
            $block_type = $this->normalize_ai_missing_block_type((string) ($item['block_type'] ?? ''));
            $role_key = trim((string) ($item['role_key'] ?? ''));
            $allow_existing_type = !empty($item['allow_existing_type']);

            if($block_type === '') {
                return;
            }

            if(!$allow_existing_type && !empty($existing_types[$block_type])) {
                return;
            }

            if($this->ai_missing_recommendation_already_satisfied($item, $block_catalog)) {
                return;
            }

            $label = trim((string) ($item['label'] ?? ''));
            if($label === '') {
                $label = l('link.biolink.blocks.' . $block_type);
            }

            $why = trim((string) ($item['why'] ?? ''));
            if($why === '') {
                return;
            }

            $seed_settings = $this->normalize_ai_missing_block_seed_settings($item['seed_settings'] ?? []);
            $supported_fields = $this->get_ai_copy_supported_fields_by_block_type($block_type);

            foreach($copy_suggestions as $suggestion) {
                if(!is_array($suggestion)) {
                    continue;
                }

                if((int) ($suggestion['block_id'] ?? 0) > 0) {
                    continue;
                }

                $suggestion_block_type = trim((string) ($suggestion['block_type'] ?? ''));
                $suggestion_role_key = trim((string) ($suggestion['role_key'] ?? ''));
                $matches_role = $role_key !== '' && $suggestion_role_key !== '' && $suggestion_role_key === $role_key;
                $matches_type = $suggestion_block_type === $block_type;

                if(!$matches_role && !$matches_type) {
                    continue;
                }

                $field = trim((string) ($suggestion['field'] ?? ''));
                $value = trim((string) ($suggestion['value'] ?? ''));

                if($value === '' || !in_array($field, $supported_fields, true) || isset($seed_settings[$field])) {
                    continue;
                }

                $seed_settings[$field] = $value;
            }

            if(in_array($block_type, ['youtube', 'vimeo'], true)) {
                $video_seed = $this->get_ai_video_seed_defaults_from_catalog($block_catalog, $block_type);

                if(!empty($video_seed['block_type'])) {
                    $block_type = (string) $video_seed['block_type'];
                }

                if(empty($seed_settings['location_url']) && !empty($video_seed['location_url'])) {
                    $seed_settings['location_url'] = (string) $video_seed['location_url'];
                }

                if(empty($seed_settings['title']) && !empty($video_seed['title'])) {
                    $seed_settings['title'] = (string) $video_seed['title'];
                }
            }

            if($block_type === 'link_forever_product' && ($role_key === 'core_business_offer' || $this->ai_text_has_any($label . ' ' . ($seed_settings['name'] ?? ''), ['start paket', 'start-paket', 'partner', 'suradnik', 'upis', 'prijava', 'registracija']))) {
                $seed_settings = array_merge($this->get_ai_fcc_start_paket_seed_settings(), $seed_settings);
            }

            if($block_type === 'link_discount') {
                $discount_seed = [];

                foreach($block_catalog as $catalog_block) {
                    if(in_array((string) ($catalog_block['type'] ?? ''), ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)) {
                        $discount_seed = [
                            'location_url' => trim((string) ($catalog_block['location_url'] ?? '')),
                        ];
                        break;
                    }
                }

                if(empty($discount_seed['location_url'])) {
                    $discount_seed = $this->get_ai_default_discount_seed_from_user_history();
                }

                if(!empty($discount_seed['location_url']) && empty($seed_settings['location_url'])) {
                    $seed_settings['location_url'] = (string) $discount_seed['location_url'];
                }

                if(!isset($seed_settings['apply_to_all_products']) && array_key_exists('apply_to_all_products', $discount_seed)) {
                    $seed_settings['apply_to_all_products'] = (int) $discount_seed['apply_to_all_products'];
                }
            }

            if(!isset($seed_settings['name']) && in_array('name', $supported_fields, true)) {
                $seed_settings['name'] = $label;
            }

            if($block_type === 'custom_html_whatsapp') {
                if(empty($seed_settings['title'])) {
                    $seed_settings['title'] = $seed_settings['button_text'] ?? $seed_settings['name'] ?? $label;
                }

                if(empty($seed_settings['message'])) {
                    $seed_settings['message'] = $seed_settings['description'] ?? $seed_settings['text'] ?? '';
                }
            }

            if($block_type === 'lead_funnel' && empty($seed_settings['popup_title'])) {
                $seed_settings['popup_title'] = $label;
            }

            $recommendation = [
                'recommendation_key' => trim((string) ($item['recommendation_key'] ?? '')),
                'block_type' => $block_type,
                'role_key' => $role_key,
                'label' => $label,
                'why' => $why,
                'priority' => max(1, min(9, (int) ($item['priority'] ?? 5))),
                'insert_after_block_id' => max(0, (int) ($item['insert_after_block_id'] ?? 0)),
                'insert_after_type' => trim((string) ($item['insert_after_type'] ?? '')),
                'insert_after_label' => trim((string) ($item['insert_after_label'] ?? '')),
                'allow_existing_type' => $allow_existing_type,
                'seed_settings' => $seed_settings,
                'supports_auto_add' => in_array($block_type, ['lead_funnel', 'heading', 'paragraph', 'modal_text', 'custom_html_whatsapp', 'custom_html_chatbot', 'custom_html_chatbot_pets', 'youtube', 'vimeo', 'link_forever_product'], true)
                    || ($block_type === 'link_discount' && !empty($seed_settings['location_url'])),
            ];

            $recommendation = array_merge($this->get_default_ai_picker_context($block_type), $recommendation);
            $recommendation['preferred_group'] = trim((string) (($item['preferred_group'] ?? $recommendation['preferred_group']) ?? ''));
            $recommendation['preferred_goal'] = trim((string) (($item['preferred_goal'] ?? $recommendation['preferred_goal']) ?? ''));
            $recommendation['picker_search'] = $this->normalize_ai_visible_copy($item['picker_search'] ?? $recommendation['picker_search'] ?? '');

            if($recommendation['insert_after_block_id'] <= 0 && $recommendation['insert_after_type'] === '') {
                $recommendation = array_merge(
                    $recommendation,
                    $this->get_default_ai_missing_block_insert_after($block_type, $block_catalog, $primary_block_plan)
                );
            }

            if($recommendation['recommendation_key'] === '') {
                $recommendation['recommendation_key'] = $this->get_ai_missing_block_recommendation_key($recommendation);
            }

            $recommendations[$recommendation['recommendation_key']] = $recommendation;
        };

        foreach($this->normalize_ai_missing_block_recommendations($additional['fcc_ai_missing_block_recommendations'] ?? []) as $item) {
            $register_recommendation($item);
        }

        foreach($this->get_default_ai_core_missing_recommendations($additional, $block_catalog, $primary_block_plan) as $item) {
            $register_recommendation($item);
        }

        foreach($this->normalize_ai_layout_actions($additional['fcc_ai_layout_actions'] ?? []) as $action) {
            if((string) ($action['action'] ?? '') !== 'add_block') {
                continue;
            }

            $register_recommendation([
                'block_type' => $action['block_type'] ?? '',
                'label' => $action['label'] ?? '',
                'why' => $action['why'] ?? '',
                'priority' => 2,
            ]);
        }

        $primary_block_type = trim((string) ($primary_block_plan['block_type'] ?? ''));
        if($primary_block_type !== '' && empty($existing_types[$primary_block_type])) {
            $register_recommendation([
                'block_type' => $primary_block_type,
                'label' => $primary_block_plan['label'] ?? '',
                'why' => $primary_block_plan['reason'] ?? '',
                'priority' => 1,
            ]);
        }

        $default_chatbot_recommendation = $this->get_default_ai_chatbot_missing_recommendation($block_catalog, $additional, $primary_block_plan);
        if($default_chatbot_recommendation) {
            $register_recommendation($default_chatbot_recommendation);
        }

        $recommendations = array_values($recommendations);
        usort($recommendations, static function($a, $b) {
            $priority_compare = ((int) ($a['priority'] ?? 5)) <=> ((int) ($b['priority'] ?? 5));

            if($priority_compare !== 0) {
                return $priority_compare;
            }

            return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        $visible_recommendations = array_slice($recommendations, 0, 6);
        $has_visible_chatbot = false;
        foreach($visible_recommendations as $recommendation) {
            if($this->is_ai_chatbot_block_type((string) ($recommendation['block_type'] ?? ''))) {
                $has_visible_chatbot = true;
                break;
            }
        }

        if(!$has_visible_chatbot) {
            foreach($recommendations as $recommendation) {
                if(!$this->is_ai_chatbot_block_type((string) ($recommendation['block_type'] ?? ''))) {
                    continue;
                }

                array_pop($visible_recommendations);
                $visible_recommendations[] = $recommendation;
                usort($visible_recommendations, static function($a, $b) {
                    $priority_compare = ((int) ($a['priority'] ?? 5)) <=> ((int) ($b['priority'] ?? 5));

                    if($priority_compare !== 0) {
                        return $priority_compare;
                    }

                    return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
                });
                break;
            }
        }

        return array_values($visible_recommendations);
    }

    private function get_default_ai_performance_snapshot(): array {
        return [
            'shop_contacts_30d' => 0,
            'whatsapp_contacts_30d' => 0,
            'product_clicks_30d' => 0,
            'funnel_registrations_30d' => 0,
            'weighted_signal_score' => 0,
        ];
    }

    private function normalize_ai_performance_snapshot($value): array {
        $value = $this->normalize_json_to_array($value);

        return [
            'shop_contacts_30d' => max(0, (int) ($value['shop_contacts_30d'] ?? 0)),
            'whatsapp_contacts_30d' => max(0, (int) ($value['whatsapp_contacts_30d'] ?? 0)),
            'product_clicks_30d' => max(0, (int) ($value['product_clicks_30d'] ?? 0)),
            'funnel_registrations_30d' => max(0, (int) ($value['funnel_registrations_30d'] ?? 0)),
            'weighted_signal_score' => max(0, (int) ($value['weighted_signal_score'] ?? 0)),
        ];
    }

    private function get_ai_performance_delta(array $before, array $after): array {
        $before = $this->normalize_ai_performance_snapshot($before);
        $after = $this->normalize_ai_performance_snapshot($after);
        $metric_labels = [
            'shop_contacts_30d' => 'shop',
            'whatsapp_contacts_30d' => 'whatsapp',
            'product_clicks_30d' => 'blog_products',
            'funnel_registrations_30d' => 'funnel_contacts',
            'weighted_signal_score' => 'total_signal',
        ];
        $delta = [];

        foreach($metric_labels as $metric_key => $label) {
            $previous_value = (int) ($before[$metric_key] ?? 0);
            $current_value = (int) ($after[$metric_key] ?? 0);
            $change = $current_value - $previous_value;

            $delta[] = [
                'metric' => $label,
                'previous' => $previous_value,
                'current' => $current_value,
                'delta' => $change,
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'same'),
            ];
        }

        return $delta;
    }

    private function get_ai_block_attribution_role(string $type, \stdClass $settings): string {
        $shop_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $video_types = ['youtube', 'video', 'tiktok_video', 'vimeo', 'twitter_video', 'vk_video'];

        if($type === 'lead_funnel') {
            return 'lead_capture';
        }

        if($this->is_app_review_whatsapp_block($type, $settings)) {
            return 'whatsapp';
        }

        if(in_array($type, $shop_types, true)) {
            return 'shop';
        }

        if($type === 'link_forever_product') {
            return 'product';
        }

        if(in_array($type, ['link_app_switcher', 'link_save_contact', 'contact_collector', 'email_collector', 'link'], true)) {
            return 'cta';
        }

        if(in_array($type, $video_types, true)) {
            return 'video';
        }

        if(in_array($type, ['heading', 'paragraph', 'image', 'avatar', 'review'], true)) {
            return 'trust_content';
        }

        if($type === 'socials') {
            return 'social_contact';
        }

        return 'supporting';
    }

    private function is_ai_block_focus_sensitive_role(string $role): bool {
        return in_array($role, ['lead_capture', 'whatsapp', 'shop', 'product', 'cta', 'social_contact', 'video'], true);
    }

    private function get_ai_block_focus_cost_score(string $role, int $position): int {
        $score = match(true) {
            $position <= 1 => 5,
            $position <= 3 => 4,
            $position <= 5 => 3,
            $position <= 8 => 2,
            default => 1,
        };

        if(in_array($role, ['lead_capture', 'whatsapp', 'shop', 'product', 'cta'], true)) {
            $score += 2;
        } elseif($role === 'video') {
            $score += 1;
        }

        return $score;
    }

    private function get_ai_block_preview_label(string $type, \stdClass $settings): string {
        foreach(['name', 'title', 'heading', 'button_text', 'text'] as $key) {
            $value = trim((string) ($settings->{$key} ?? ''));

            if($value !== '') {
                return mb_substr($value, 0, 160);
            }
        }

        return match($type) {
            'socials' => 'Drustvene mreze i kontakti',
            'custom_html_whatsapp' => 'WhatsApp kontakt',
            'lead_funnel' => 'Funnel',
            'link_forever_product' => 'Proizvod',
            'link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo' => 'Forever trgovina',
            'link_app_switcher' => 'Prelazak na drugu aplikaciju',
            'heading' => 'Naslov',
            'paragraph' => 'Tekst blok',
            'image' => 'Fotografija',
            'avatar' => 'Profilna fotografija',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    private function get_ai_block_attribution_reason(string $status, int $position): string {
        return match($status) {
            'high_signal' => 'Ovaj blok trenutno nosi najvise mjerljivog signala i vrijedi ga drzati jasno vidljivim.',
            'contributing' => 'Blok vec donosi signal pa ga vrijedi zadrzati i fino doradivati.',
            'critical_focus_risk' => 'Blok je vrlo visoko, ali bez signala pa vjerojatno uzima fokus bez rezultata.',
            'focus_risk' => 'Blok je rano u aplikaciji, ali zasad nema mjerljiv rezultat pa je kandidat za spustanje ili jasniji tekst.',
            'supporting' => $position <= 4
                ? 'Blok vise gradi dojam i povjerenje nego klik, pa treba ostati kratak i miran.'
                : 'Blok trenutno vise sluzi kao podrska nego kao izravan izvor signala.',
            default => 'Blok je aktivan, ali trenutno nema mjerljiv doprinos rezultatu.',
        };
    }

    private function is_ai_block_trust_anchor(string $type, string $role): bool {
        return $role === 'trust_content';
    }

    private function normalize_ai_block_attribution_payload($value): array {
        $value = $this->normalize_json_to_array($value);

        $normalize_row = function($item): ?array {
            if(!is_array($item)) {
                return null;
            }

            $row = [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'position' => max(0, (int) ($item['position'] ?? 0)),
                'type' => trim((string) ($item['type'] ?? '')),
                'label' => trim((string) ($item['label'] ?? '')),
                'role' => trim((string) ($item['role'] ?? '')),
                'unique_clicks_30d' => max(0, (int) ($item['unique_clicks_30d'] ?? 0)),
                'funnel_leads_30d' => max(0, (int) ($item['funnel_leads_30d'] ?? 0)),
                'signal_score' => max(0, (int) ($item['signal_score'] ?? 0)),
                'focus_cost_score' => max(0, (int) ($item['focus_cost_score'] ?? 0)),
                'status' => trim((string) ($item['status'] ?? '')),
                'action_hint' => trim((string) ($item['action_hint'] ?? '')),
                'reason' => trim((string) ($item['reason'] ?? '')),
            ];

            if(
                (int) ($row['signal_score'] ?? 0) === 0
                && in_array((string) ($row['status'] ?? ''), ['focus_risk', 'critical_focus_risk'], true)
                && $this->is_ai_block_trust_anchor((string) ($row['type'] ?? ''), (string) ($row['role'] ?? ''))
            ) {
                $row['status'] = 'supporting';
                $row['action_hint'] = 'keep_supporting';
                $row['reason'] = $this->get_ai_block_attribution_reason('supporting', (int) ($row['position'] ?? 0));
            }

            return $row;
        };

        $all_blocks = [];
        foreach((array) ($value['all_blocks'] ?? []) as $item) {
            $row = $normalize_row($item);
            if($row) {
                $all_blocks[] = $row;
            }
            if(count($all_blocks) >= 18) {
                break;
            }
        }

        $top_signal_blocks = [];
        foreach((array) ($value['top_signal_blocks'] ?? []) as $item) {
            $row = $normalize_row($item);
            if($row) {
                $top_signal_blocks[] = $row;
            }
            if(count($top_signal_blocks) >= 4) {
                break;
            }
        }

        $focus_risk_blocks = [];
        foreach((array) ($value['focus_risk_blocks'] ?? []) as $item) {
            $row = $normalize_row($item);
            if($row) {
                $focus_risk_blocks[] = $row;
            }
            if(count($focus_risk_blocks) >= 4) {
                break;
            }
        }

        $summary = is_array($value['summary'] ?? null) ? (array) $value['summary'] : [];

        return [
            'has_blocks' => !empty($all_blocks),
            'summary' => [
                'tracked_blocks' => max(0, (int) ($summary['tracked_blocks'] ?? count($all_blocks))),
                'signal_blocks' => max(0, (int) ($summary['signal_blocks'] ?? count(array_filter($all_blocks, static fn($row): bool => (int) ($row['signal_score'] ?? 0) > 0)))),
                'focus_risk_blocks' => count($focus_risk_blocks),
                'zero_signal_blocks' => max(0, (int) ($summary['zero_signal_blocks'] ?? count(array_filter($all_blocks, static fn($row): bool => (int) ($row['signal_score'] ?? 0) === 0)))),
            ],
            'top_signal_blocks' => $top_signal_blocks,
            'focus_risk_blocks' => $focus_risk_blocks,
            'all_blocks' => $all_blocks,
        ];
    }

    private function normalize_ai_block_delta_summary($value): array {
        $value = $this->normalize_json_to_array($value);

        $normalize_delta_row = function($item): ?array {
            if(!is_array($item)) {
                return null;
            }

            return [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'label' => trim((string) ($item['label'] ?? '')),
                'type' => trim((string) ($item['type'] ?? '')),
                'previous_signal' => max(0, (int) ($item['previous_signal'] ?? 0)),
                'current_signal' => max(0, (int) ($item['current_signal'] ?? 0)),
                'delta_signal' => (int) ($item['delta_signal'] ?? 0),
                'direction' => in_array((string) ($item['direction'] ?? 'same'), ['up', 'down', 'same'], true) ? (string) ($item['direction'] ?? 'same') : 'same',
            ];
        };

        $build_rows = function(array $items) use ($normalize_delta_row): array {
            $rows = [];
            foreach($items as $item) {
                $row = $normalize_delta_row($item);
                if($row) {
                    $rows[] = $row;
                }
                if(count($rows) >= 4) {
                    break;
                }
            }
            return $rows;
        };

        return [
            'top_gainers' => $build_rows((array) ($value['top_gainers'] ?? [])),
            'top_decliners' => $build_rows((array) ($value['top_decliners'] ?? [])),
            'current_top_blocks' => $this->normalize_ai_block_attribution_payload(['top_signal_blocks' => (array) ($value['current_top_blocks'] ?? [])])['top_signal_blocks'],
            'focus_risk_blocks' => $this->normalize_ai_block_attribution_payload(['focus_risk_blocks' => (array) ($value['focus_risk_blocks'] ?? [])])['focus_risk_blocks'],
        ];
    }

    private function get_ai_block_delta_summary(array $before_payload, array $after_payload): array {
        $before_payload = $this->normalize_ai_block_attribution_payload($before_payload);
        $after_payload = $this->normalize_ai_block_attribution_payload($after_payload);
        $before_rows = [];
        $after_rows = [];

        foreach((array) ($before_payload['all_blocks'] ?? []) as $row) {
            $before_rows[(int) ($row['block_id'] ?? 0)] = $row;
        }

        foreach((array) ($after_payload['all_blocks'] ?? []) as $row) {
            $after_rows[(int) ($row['block_id'] ?? 0)] = $row;
        }

        $delta_rows = [];
        foreach(array_unique(array_merge(array_keys($before_rows), array_keys($after_rows))) as $block_id) {
            $block_id = (int) $block_id;
            if($block_id <= 0) {
                continue;
            }

            $before_row = $before_rows[$block_id] ?? [];
            $after_row = $after_rows[$block_id] ?? [];
            $previous_signal = (int) ($before_row['signal_score'] ?? 0);
            $current_signal = (int) ($after_row['signal_score'] ?? 0);
            $delta_signal = $current_signal - $previous_signal;

            if($delta_signal === 0) {
                continue;
            }

            $delta_rows[] = [
                'block_id' => $block_id,
                'label' => (string) (($after_row['label'] ?? '') ?: ($before_row['label'] ?? '')),
                'type' => (string) (($after_row['type'] ?? '') ?: ($before_row['type'] ?? '')),
                'previous_signal' => $previous_signal,
                'current_signal' => $current_signal,
                'delta_signal' => $delta_signal,
                'direction' => $delta_signal > 0 ? 'up' : 'down',
            ];
        }

        usort($delta_rows, static function($a, $b) {
            return abs((int) ($b['delta_signal'] ?? 0)) <=> abs((int) ($a['delta_signal'] ?? 0));
        });

        return [
            'top_gainers' => array_values(array_slice(array_filter($delta_rows, static fn($item): bool => (int) ($item['delta_signal'] ?? 0) > 0), 0, 4)),
            'top_decliners' => array_values(array_slice(array_filter($delta_rows, static fn($item): bool => (int) ($item['delta_signal'] ?? 0) < 0), 0, 4)),
            'current_top_blocks' => (array) ($after_payload['top_signal_blocks'] ?? []),
            'focus_risk_blocks' => (array) ($after_payload['focus_risk_blocks'] ?? []),
        ];
    }

    private function get_ai_block_attribution_payload(int $link_id): array {
        if($link_id <= 0) {
            return $this->normalize_ai_block_attribution_payload([]);
        }

        $period_30d_start = (new \DateTimeImmutable())->sub(new \DateInterval('P29D'))->format('Y-m-d 00:00:00');
        $result = database()->query("SELECT `biolink_block_id`, `type`, `settings`, `order`
            FROM `biolinks_blocks`
            WHERE `user_id` = {$this->user->user_id}
              AND `link_id` = {$link_id}
              AND `is_enabled` = 1
            ORDER BY `order` ASC, `biolink_block_id` ASC");
        $blocks = [];
        $block_ids = [];

        if($result) {
            while($row = $result->fetch_object()) {
                $block_id = (int) ($row->biolink_block_id ?? 0);
                if($block_id <= 0) {
                    continue;
                }

                $settings = $this->decode_biolink_block_settings($row->settings ?? null);
                $blocks[] = [
                    'block_id' => $block_id,
                    'type' => (string) ($row->type ?? ''),
                    'settings' => $settings,
                    'label' => $this->get_ai_block_preview_label((string) ($row->type ?? ''), $settings),
                ];
                $block_ids[] = $block_id;
            }
        }

        if(empty($blocks)) {
            return $this->normalize_ai_block_attribution_payload([]);
        }

        $clicks_per_block = [];
        $leads_per_block = [];
        $block_ids_sql = implode(',', array_map('intval', $block_ids));

        $clicks_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
            FROM `track_links`
            WHERE `datetime` >= '{$period_30d_start}'
              AND `is_unique` = 1
              AND `biolink_block_id` IN ({$block_ids_sql})
            GROUP BY `biolink_block_id`");

        if($clicks_result) {
            while($row = $clicks_result->fetch_object()) {
                $clicks_per_block[(int) ($row->biolink_block_id ?? 0)] = (int) ($row->total ?? 0);
            }
        }

        $funnel_ids = array_values(array_filter(array_map(static fn($block): int => (string) ($block['type'] ?? '') === 'lead_funnel' ? (int) ($block['block_id'] ?? 0) : 0, $blocks)));
        if(!empty($funnel_ids)) {
            $funnel_ids_sql = implode(',', array_map('intval', $funnel_ids));
            $leads_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                FROM `data`
                WHERE `type` = 'lead_funnel'
                  AND `datetime` >= '{$period_30d_start}'
                  AND `biolink_block_id` IN ({$funnel_ids_sql})
                GROUP BY `biolink_block_id`");

            if($leads_result) {
                while($row = $leads_result->fetch_object()) {
                    $leads_per_block[(int) ($row->biolink_block_id ?? 0)] = (int) ($row->total ?? 0);
                }
            }
        }

        $rows = [];
        foreach($blocks as $index => $block) {
            $settings = $this->decode_biolink_block_settings($block['settings'] ?? null);
            $role = $this->get_ai_block_attribution_role((string) ($block['type'] ?? ''), $settings);
            $position = $index + 1;
            $unique_clicks = (int) ($clicks_per_block[(int) ($block['block_id'] ?? 0)] ?? 0);
            $funnel_leads = (int) ($leads_per_block[(int) ($block['block_id'] ?? 0)] ?? 0);
            $signal_score = max(0, $unique_clicks + ($funnel_leads * 2));
            $focus_cost_score = $this->get_ai_block_focus_cost_score($role, $position);
            $is_focus_sensitive = $this->is_ai_block_focus_sensitive_role($role);

            if($signal_score >= 8 || $funnel_leads >= 2 || ($is_focus_sensitive && $unique_clicks >= 4)) {
                $status = 'high_signal';
            } elseif($signal_score >= 2 || $unique_clicks >= 2) {
                $status = 'contributing';
            } elseif($signal_score === 0 && $this->is_ai_block_trust_anchor((string) ($block['type'] ?? ''), $role)) {
                $status = 'supporting';
            } elseif($signal_score === 0 && $focus_cost_score >= 6 && $position <= 3 && $is_focus_sensitive) {
                $status = 'critical_focus_risk';
            } elseif($signal_score === 0 && $focus_cost_score >= 5 && $position <= 5) {
                $status = 'focus_risk';
            } elseif($signal_score === 0 && in_array($role, ['trust_content', 'video'], true)) {
                $status = 'supporting';
            } else {
                $status = 'low_signal';
            }

            $rows[] = [
                'block_id' => (int) ($block['block_id'] ?? 0),
                'position' => $position,
                'type' => (string) ($block['type'] ?? ''),
                'label' => trim((string) (($block['label'] ?? '') ?: ($block['type'] ?? ''))),
                'role' => $role,
                'unique_clicks_30d' => $unique_clicks,
                'funnel_leads_30d' => $funnel_leads,
                'signal_score' => $signal_score,
                'focus_cost_score' => $focus_cost_score,
                'status' => $status,
                'action_hint' => match($status) {
                    'high_signal' => 'keep_or_emphasize',
                    'contributing' => 'keep_and_refine',
                    'critical_focus_risk' => 'move_down_or_hide',
                    'focus_risk' => 'rewrite_or_move_down',
                    'supporting' => 'keep_supporting',
                    default => 'test_or_reduce',
                },
                'reason' => $this->get_ai_block_attribution_reason($status, $position),
            ];
        }

        $top_signal_blocks = array_values(array_filter($rows, static fn($row): bool => (int) ($row['signal_score'] ?? 0) > 0));
        usort($top_signal_blocks, static function($a, $b) {
            return (($b['signal_score'] ?? 0) <=> ($a['signal_score'] ?? 0))
                ?: (($a['position'] ?? 0) <=> ($b['position'] ?? 0));
        });

        $focus_risk_blocks = array_values(array_filter($rows, static fn($row): bool => in_array((string) ($row['status'] ?? ''), ['critical_focus_risk', 'focus_risk'], true)));
        usort($focus_risk_blocks, static function($a, $b) {
            return (($b['focus_cost_score'] ?? 0) <=> ($a['focus_cost_score'] ?? 0))
                ?: (($a['position'] ?? 0) <=> ($b['position'] ?? 0));
        });

        return $this->normalize_ai_block_attribution_payload([
            'summary' => [
                'tracked_blocks' => count($rows),
                'signal_blocks' => count(array_filter($rows, static fn($row): bool => (int) ($row['signal_score'] ?? 0) > 0)),
                'focus_risk_blocks' => count($focus_risk_blocks),
                'zero_signal_blocks' => count(array_filter($rows, static fn($row): bool => (int) ($row['signal_score'] ?? 0) === 0)),
            ],
            'top_signal_blocks' => array_slice($top_signal_blocks, 0, 4),
            'focus_risk_blocks' => array_slice($focus_risk_blocks, 0, 4),
            'all_blocks' => $rows,
        ]);
    }

    private function has_ai_evolution_window_elapsed(string $recommended_at, int $days): bool {
        if($recommended_at === '' || $days < 1) {
            return false;
        }

        try {
            $recommended_datetime = new \DateTimeImmutable($recommended_at);

            return $recommended_datetime->add(new \DateInterval('P' . $days . 'D')) <= new \DateTimeImmutable();
        } catch(\Throwable $exception) {
            return false;
        }
    }

    private function get_ai_evolution_window_status(string $recommended_at, ?string $measured_at, int $days): string {
        if(!empty($measured_at)) {
            return 'measured';
        }

        return $this->has_ai_evolution_window_elapsed($recommended_at, $days) ? 'ready' : 'pending';
    }

    private function normalize_ai_evolution_delta_items($value): array {
        $value = $this->normalize_json_to_array($value);
        $normalized = [];

        foreach($value as $item) {
            if(!is_array($item) || empty($item['metric'])) {
                continue;
            }

            $normalized[] = [
                'metric' => trim((string) ($item['metric'] ?? '')),
                'previous' => (int) ($item['previous'] ?? 0),
                'current' => (int) ($item['current'] ?? 0),
                'delta' => (int) ($item['delta'] ?? 0),
                'direction' => in_array((string) ($item['direction'] ?? 'same'), ['up', 'down', 'same'], true) ? (string) ($item['direction'] ?? 'same') : 'same',
            ];
        }

        return array_slice($normalized, 0, 5);
    }

    private function summarize_ai_evolution_delta(array $delta): string {
        foreach($delta as $item) {
            if((string) ($item['metric'] ?? '') !== 'total_signal') {
                continue;
            }

            $change = (int) ($item['delta'] ?? 0);

            if($change > 0) {
                return sprintf(l('link.settings.ai_evolution_result_positive'), nr($change));
            }

            if($change < 0) {
                return sprintf(l('link.settings.ai_evolution_result_negative'), nr(abs($change)));
            }

            return l('link.settings.ai_evolution_result_same');
        }

        return l('link.settings.ai_evolution_result_same');
    }

    private function normalize_ai_evolution_measurement($value, string $recommended_at = '', int $days = 7): array {
        $value = $this->normalize_json_to_array($value);
        $measured_at = !empty($value['measured_at']) ? (string) $value['measured_at'] : null;
        $delta = $this->normalize_ai_evolution_delta_items($value['delta'] ?? []);

        return [
            'status' => $this->get_ai_evolution_window_status($recommended_at, $measured_at, $days),
            'measured_at' => $measured_at,
            'performance' => $this->normalize_ai_performance_snapshot($value['performance'] ?? []),
            'delta' => $delta,
            'block_summary' => $this->normalize_ai_block_delta_summary($value['block_summary'] ?? []),
            'summary' => $this->summarize_ai_evolution_delta($delta),
        ];
    }

    private function normalize_ai_evolution_memory($value): array {
        $items = $this->normalize_json_to_array($value);
        $normalized = [];

        foreach($items as $item) {
            if(!is_array($item)) {
                continue;
            }

            $recommended = $this->normalize_json_to_array($item['recommended'] ?? []);
            $applied = $this->normalize_json_to_array($item['applied'] ?? []);
            $layout_summary = $this->normalize_json_to_array($applied['layout_summary'] ?? []);
            $layout_rollback_summary = $this->normalize_json_to_array($applied['layout_rollback_summary'] ?? []);
            $recommended_at = trim((string) ($item['recommended_at'] ?? ''));

            $normalized[] = [
                'review_key' => trim((string) ($item['review_key'] ?? '')),
                'recommended_at' => $recommended_at ?: null,
                'analysis_mode' => in_array((string) ($item['analysis_mode'] ?? 'initial'), ['initial', 'evolution'], true) ? (string) ($item['analysis_mode'] ?? 'initial') : 'initial',
                'quality_score' => max(0, (int) ($item['quality_score'] ?? 0)),
                'quality_level' => trim((string) ($item['quality_level'] ?? 'foundation')),
                'performance_before' => $this->normalize_ai_performance_snapshot($item['performance_before'] ?? []),
                'block_attribution_before' => $this->normalize_ai_block_attribution_payload($item['block_attribution_before'] ?? []),
                'recommended' => [
                    'headline' => trim((string) ($recommended['headline'] ?? '')),
                    'summary' => trim((string) ($recommended['summary'] ?? '')),
                    'top_recommendation' => trim((string) ($recommended['top_recommendation'] ?? '')),
                    'first_move' => trim((string) ($recommended['first_move'] ?? '')),
                    'next_move' => trim((string) ($recommended['next_move'] ?? '')),
                    'theme_name' => trim((string) ($recommended['theme_name'] ?? '')),
                    'theme_summary' => trim((string) ($recommended['theme_summary'] ?? '')),
                    'primary_block' => $this->normalize_ai_primary_block_plan($recommended['primary_block'] ?? []),
                    'layout_actions' => $this->normalize_ai_layout_actions($recommended['layout_actions'] ?? []),
                ],
                'applied' => [
                    'theme_applied_at' => !empty($applied['theme_applied_at']) ? (string) $applied['theme_applied_at'] : null,
                    'primary_applied_at' => !empty($applied['primary_applied_at']) ? (string) $applied['primary_applied_at'] : null,
                    'layout_applied_at' => !empty($applied['layout_applied_at']) ? (string) $applied['layout_applied_at'] : null,
                    'layout_reverted_at' => !empty($applied['layout_reverted_at']) ? (string) $applied['layout_reverted_at'] : null,
                    'theme_key' => trim((string) ($applied['theme_key'] ?? '')),
                    'layout_summary' => [
                        'reordered_blocks' => max(0, (int) ($layout_summary['reordered_blocks'] ?? 0)),
                        'hidden_blocks' => max(0, (int) ($layout_summary['hidden_blocks'] ?? 0)),
                        'updated_blocks' => max(0, (int) ($layout_summary['updated_blocks'] ?? 0)),
                    ],
                    'layout_rollback_summary' => [
                        'restored_blocks' => max(0, (int) ($layout_rollback_summary['restored_blocks'] ?? 0)),
                        're_enabled_blocks' => max(0, (int) ($layout_rollback_summary['re_enabled_blocks'] ?? 0)),
                    ],
                ],
                'evaluation_7d' => $this->normalize_ai_evolution_measurement($item['evaluation_7d'] ?? [], $recommended_at, 7),
                'evaluation_30d' => $this->normalize_ai_evolution_measurement($item['evaluation_30d'] ?? [], $recommended_at, 30),
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['recommended_at'] ?? ''), (string) ($a['recommended_at'] ?? ''));
        });

        return array_slice($normalized, 0, 12);
    }

    private function refresh_ai_evolution_memory(array $cycles, array $current_performance, array $current_block_attribution = [], ?string $current_datetime = null): array {
        $cycles = $this->normalize_ai_evolution_memory($cycles);
        $current_performance = $this->normalize_ai_performance_snapshot($current_performance);
        $current_block_attribution = $this->normalize_ai_block_attribution_payload($current_block_attribution);
        $current_datetime = $current_datetime ?: get_date();

        foreach($cycles as &$cycle) {
            $recommended_at = (string) ($cycle['recommended_at'] ?? '');

            if(empty($cycle['evaluation_7d']['measured_at']) && $this->has_ai_evolution_window_elapsed($recommended_at, 7)) {
                $cycle['evaluation_7d'] = [
                    'status' => 'measured',
                    'measured_at' => $current_datetime,
                    'performance' => $current_performance,
                    'delta' => $this->get_ai_performance_delta((array) ($cycle['performance_before'] ?? []), $current_performance),
                    'block_summary' => $this->get_ai_block_delta_summary((array) ($cycle['block_attribution_before'] ?? []), $current_block_attribution),
                ];
            }

            if(empty($cycle['evaluation_30d']['measured_at']) && $this->has_ai_evolution_window_elapsed($recommended_at, 30)) {
                $cycle['evaluation_30d'] = [
                    'status' => 'measured',
                    'measured_at' => $current_datetime,
                    'performance' => $current_performance,
                    'delta' => $this->get_ai_performance_delta((array) ($cycle['performance_before'] ?? []), $current_performance),
                    'block_summary' => $this->get_ai_block_delta_summary((array) ($cycle['block_attribution_before'] ?? []), $current_block_attribution),
                ];
            }
        }
        unset($cycle);

        return $cycles;
    }

    private function get_ai_layout_backup_payload(array $additional): array {
        $backup = $this->normalize_json_to_array($additional['fcc_ai_layout_backup'] ?? []);
        $last_restore = $this->normalize_json_to_array($additional['fcc_ai_layout_last_restore'] ?? []);

        return [
            'available' => !empty($backup['blocks']) && !empty($backup['captured_at']),
            'captured_at' => !empty($backup['captured_at']) ? (string) $backup['captured_at'] : null,
            'total_blocks' => count((array) ($backup['blocks'] ?? [])),
            'review_key' => trim((string) ($backup['review_key'] ?? '')),
            'last_restore' => [
                'restored_at' => !empty($last_restore['restored_at']) ? (string) $last_restore['restored_at'] : null,
                'restored_blocks' => max(0, (int) ($last_restore['restored_blocks'] ?? 0)),
                're_enabled_blocks' => max(0, (int) ($last_restore['re_enabled_blocks'] ?? 0)),
            ],
        ];
    }

    private function get_active_ai_bundle_review_key(array $additional): string {
        return trim((string) (($this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? null)['active_review_key'] ?? '')));
    }

    private function is_ai_bundle_backup_usable(array $backup, string $review_key = ''): bool {
        if(empty($backup['blocks']) || empty($backup['captured_at'])) {
            return false;
        }

        $backup_review_key = trim((string) ($backup['review_key'] ?? ''));

        return $review_key === '' || $backup_review_key === '' || $backup_review_key === $review_key;
    }

    private function get_ai_bundle_backup_payload(array $additional): array {
        $review_key = $this->get_active_ai_bundle_review_key($additional);
        $backup = $this->normalize_json_to_array($additional['fcc_ai_bundle_backup'] ?? []);
        $baseline_backup = $this->normalize_json_to_array($additional['fcc_ai_bundle_baseline_backup'] ?? []);
        $resolved_backup = [];
        $source = '';

        if($this->is_ai_bundle_backup_usable($backup, $review_key)) {
            $resolved_backup = $backup;
            $source = 'working';
        } elseif($this->is_ai_bundle_backup_usable($baseline_backup, $review_key)) {
            $resolved_backup = $baseline_backup;
            $source = 'baseline';
        }

        return [
            'available' => !empty($resolved_backup),
            'captured_at' => !empty($resolved_backup['captured_at']) ? (string) $resolved_backup['captured_at'] : null,
            'total_blocks' => count((array) ($resolved_backup['blocks'] ?? [])),
            'review_key' => trim((string) ($resolved_backup['review_key'] ?? '')),
            'source' => $source,
        ];
    }

    private function get_ai_bundle_freshness_payload(array $additional, ?string $last_datetime = null): array {
        $apply_state = $this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? []);
        $review_summary = $this->normalize_json_to_array($additional['fcc_ai_review_summary'] ?? []);
        $recommended_at = trim((string) ($apply_state['recommended_at'] ?? ($review_summary['generated_at'] ?? '')));
        $last_datetime = trim((string) ($last_datetime ?? ''));
        $reference_points = array_filter([
            $recommended_at,
            trim((string) ($apply_state['applied_at'] ?? '')),
            trim((string) ($apply_state['theme_applied_at'] ?? '')),
            trim((string) ($apply_state['primary_applied_at'] ?? '')),
            trim((string) ($apply_state['layout_applied_at'] ?? '')),
            trim((string) ($apply_state['layout_reverted_at'] ?? '')),
        ]);
        $reference_at = '';

        foreach($reference_points as $candidate) {
            if($reference_at === '' || strcmp($candidate, $reference_at) > 0) {
                $reference_at = $candidate;
            }
        }

        $is_stale = false;

        if($last_datetime !== '' && $reference_at !== '') {
            try {
                $is_stale = (new \DateTimeImmutable($last_datetime)) > (new \DateTimeImmutable($reference_at));
            } catch(\Throwable $exception) {
                $is_stale = strcmp($last_datetime, $reference_at) > 0;
            }
        }

        return [
            'is_stale' => $is_stale,
            'recommended_at' => $recommended_at !== '' ? $recommended_at : null,
            'last_changed_at' => $last_datetime !== '' ? $last_datetime : null,
            'message' => $is_stale ? l('link.settings.ai_bundle_stale_notice') : '',
        ];
    }

    private function get_ai_evolution_payload(array $additional, array $current_performance = [], int $link_id = 0, array $current_block_attribution = []): array {
        $original_memory = $this->normalize_ai_evolution_memory($additional['fcc_ai_evolution_memory'] ?? []);
        $memory = $this->refresh_ai_evolution_memory($original_memory, $current_performance, $current_block_attribution);

        if($link_id > 0 && json_encode($memory) !== json_encode($original_memory)) {
            $additional['fcc_ai_evolution_memory'] = $memory;

            db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->update('links', [
                'additional' => fcc_ai_prepare_biolink_additional_for_storage($additional),
            ]);

            cache()->deleteItemsByTag('link_id=' . $link_id);
            cache()->deleteItem('link?link_id=' . $link_id);
        }

        $active_cycle = $memory[0] ?? null;

        if(!$active_cycle) {
            return [
                'has_memory' => false,
                'active_cycle' => null,
                'recent_cycles' => [],
            ];
        }

        $recent_cycles = [];

        foreach($memory as $cycle) {
            if(empty($cycle['evaluation_7d']['measured_at']) && empty($cycle['evaluation_30d']['measured_at'])) {
                continue;
            }

            $recent_cycles[] = [
                'recommended_at' => $cycle['recommended_at'] ?? null,
                'headline' => (string) ($cycle['recommended']['headline'] ?? ''),
                'evaluation_7d' => $cycle['evaluation_7d'],
                'evaluation_30d' => $cycle['evaluation_30d'],
            ];

            if(count($recent_cycles) >= 3) {
                break;
            }
        }

        return [
            'has_memory' => true,
            'active_cycle' => $active_cycle,
            'recent_cycles' => $recent_cycles,
        ];
    }

    private function get_ai_theme_library($preferences): array {
        $preferences = is_string($preferences) ? json_decode($preferences ?? '{}', true) : $this->normalize_json_to_array($preferences);
        $library = is_array($preferences) ? ($preferences['leader_ai_theme_library'] ?? []) : [];
        $library = is_object($library) ? json_decode(json_encode($library), true) : $library;

        if(!is_array($library)) {
            return [];
        }

        $normalized = [];

        foreach($library as $entry) {
            if(!is_array($entry)) {
                continue;
            }

            $theme_pack = $this->normalize_ai_theme_pack($entry['theme_pack'] ?? []);

            if(
                $theme_pack['background_color'] === ''
                && $theme_pack['gradient_start'] === ''
                && $theme_pack['primary_block_background'] === ''
                && $theme_pack['secondary_blocks_background'] === ''
            ) {
                continue;
            }

            $normalized[] = [
                'theme_key' => trim((string) ($entry['theme_key'] ?? '')),
                'name' => trim((string) ($entry['name'] ?? $theme_pack['name'] ?? 'AI tema')),
                'summary' => trim((string) ($entry['summary'] ?? $theme_pack['summary'] ?? '')),
                'generated_at' => $entry['generated_at'] ?? null,
                'selected_link_id' => (int) ($entry['selected_link_id'] ?? 0),
                'selected_app_name' => trim((string) ($entry['selected_app_name'] ?? '')),
                'theme_pack' => $theme_pack,
            ];
        }

        return array_slice($normalized, 0, 12);
    }

    private function get_ai_editor_payload($link, $preferences, array $current_performance = [], array $current_block_attribution = []): array {
        $additional = $this->normalize_json_to_array($link->additional ?? null);
        $block_catalog = $this->get_ai_editor_block_catalog((int) ($link->link_id ?? 0));
        $raw_copy_suggestions = $this->normalize_ai_copy_suggestions($additional['fcc_ai_copy_suggestions'] ?? []);
        $raw_primary_block_plan = $this->normalize_ai_primary_block_plan($additional['fcc_ai_primary_block_plan'] ?? []);
        $raw_missing_block_recommendations = $this->build_ai_missing_block_recommendations(
            $additional,
            $block_catalog,
            $raw_primary_block_plan,
            $raw_copy_suggestions
        );
        $primary_block_plan = $this->get_effective_ai_primary_block_plan($additional, $block_catalog, $raw_missing_block_recommendations, (int) ($link->link_id ?? 0), $preferences);
        $missing_block_recommendations = $this->build_ai_missing_block_recommendations(
            $additional,
            $block_catalog,
            $primary_block_plan,
            $raw_copy_suggestions
        );
        $copy_suggestions = $this->filter_ai_copy_suggestions_for_current_blocks(
            $raw_copy_suggestions,
            $block_catalog,
            $missing_block_recommendations
        );
        $theme_pack = $this->normalize_ai_theme_pack($additional['fcc_ai_theme_pack'] ?? []);
        $bundle_backup = $this->get_ai_bundle_backup_payload($additional);
        $layout_actions = $this->build_effective_ai_layout_actions($additional, $block_catalog, $primary_block_plan, $missing_block_recommendations, (int) ($link->link_id ?? 0), $preferences);
        $can_apply_blocks = !empty($copy_suggestions) || !empty($layout_actions) || !empty($missing_block_recommendations);
        $can_apply_colors = (bool) array_filter([
            (string) ($theme_pack['background_color'] ?? ''),
            (string) ($theme_pack['gradient_start'] ?? ''),
            (string) ($theme_pack['gradient_end'] ?? ''),
            (string) ($theme_pack['heading_color'] ?? ''),
            (string) ($theme_pack['text_color'] ?? ''),
            (string) ($theme_pack['primary_block_background'] ?? ''),
            (string) ($theme_pack['secondary_blocks_background'] ?? ''),
        ]);
        $can_restore = !empty($bundle_backup['available']);

        return [
            'theme_pack' => $theme_pack,
            'primary_block_plan' => $primary_block_plan,
            'copy_suggestions' => $copy_suggestions,
            'layout_actions' => $layout_actions,
            'missing_block_recommendations' => $missing_block_recommendations,
            'block_patch_pack' => $this->normalize_json_to_array($additional['fcc_ai_block_patch_pack'] ?? []),
            'theme_apply_state' => $this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? []),
            'review_summary' => $this->normalize_json_to_array($additional['fcc_ai_review_summary'] ?? []),
            'evolution' => $this->get_ai_evolution_payload($additional, $current_performance, (int) ($link->link_id ?? 0), $current_block_attribution),
            'layout_backup' => $this->get_ai_layout_backup_payload($additional),
            'bundle_backup' => $bundle_backup,
            'can_apply_blocks' => $can_apply_blocks,
            'can_apply_colors' => $can_apply_colors,
            'can_restore' => $can_restore,
            'can_attempt_restore' => $can_restore || $can_apply_blocks || $can_apply_colors,
            'freshness' => $this->get_ai_bundle_freshness_payload($additional, (string) ($link->last_datetime ?? '')),
            'block_attribution' => $this->normalize_ai_block_attribution_payload($current_block_attribution),
            'theme_library_key' => trim((string) ($additional['fcc_ai_theme_library_key'] ?? '')),
            'theme_library' => $this->get_ai_theme_library($preferences),
        ];
    }

    private function is_app_review_whatsapp_socials_block(\stdClass $settings): bool {
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

    private function is_app_review_whatsapp_block(string $type, \stdClass $settings): bool {
        if($type === 'custom_html_whatsapp') {
            return true;
        }

        if($type === 'socials') {
            return $this->is_app_review_whatsapp_socials_block($settings);
        }

        if($type !== 'link') {
            return false;
        }

        $location_url = trim((string) ($settings->location_url ?? ''));
        if($location_url === '') {
            return false;
        }

        return str_contains(mb_strtolower($location_url), 'wa.me')
            || str_contains(mb_strtolower($location_url), 'api.whatsapp.com');
    }

    private function get_app_review_signal_block_maps(array $link_ids): array {
        $signal_maps = [];

        foreach($link_ids as $link_id) {
            $link_id = (int) $link_id;

            if($link_id <= 0) {
                continue;
            }

            $signal_maps[$link_id] = [
                'shop_block_ids' => [],
                'whatsapp_block_ids' => [],
                'product_block_ids' => [],
                'funnel_block_ids' => [],
            ];
        }

        if(empty($signal_maps)) {
            return [];
        }

        $shop_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $relevant_types = array_unique(array_merge($shop_types, ['link_forever_product', 'lead_funnel', 'custom_html_whatsapp', 'socials', 'link']));
        $relevant_types_sql = "'" . implode("','", array_map(static function($type) {
            return str_replace("'", "\\'", (string) $type);
        }, $relevant_types)) . "'";
        $link_ids_sql = implode(',', array_map('intval', array_keys($signal_maps)));

        $blocks_result = database()->query("SELECT `biolink_block_id`, `link_id`, `type`, `settings`
            FROM `biolinks_blocks`
            WHERE `link_id` IN ({$link_ids_sql})
              AND `type` IN ({$relevant_types_sql})");

        while($row = $blocks_result->fetch_object()) {
            $link_id = (int) ($row->link_id ?? 0);
            $block_id = (int) ($row->biolink_block_id ?? 0);
            $type = (string) ($row->type ?? '');

            if(!$link_id || !$block_id || !isset($signal_maps[$link_id])) {
                continue;
            }

            $settings = $this->decode_biolink_block_settings($row->settings ?? null);

            if(in_array($type, $shop_types, true)) {
                $signal_maps[$link_id]['shop_block_ids'][] = $block_id;
            }

            if($type === 'link_forever_product') {
                $signal_maps[$link_id]['product_block_ids'][] = $block_id;
            }

            if($type === 'lead_funnel') {
                $signal_maps[$link_id]['funnel_block_ids'][] = $block_id;
            }

            if($this->is_app_review_whatsapp_block($type, $settings)) {
                $signal_maps[$link_id]['whatsapp_block_ids'][] = $block_id;
            }
        }

        foreach($signal_maps as &$signal_map) {
            foreach(['shop_block_ids', 'whatsapp_block_ids', 'product_block_ids', 'funnel_block_ids'] as $signal_key) {
                $signal_map[$signal_key] = array_values(array_unique(array_map('intval', $signal_map[$signal_key])));
            }
        }
        unset($signal_map);

        return $signal_maps;
    }

    private function calculate_app_review_weighted_signal_score(array $signals): int {
        return (int) (
            (int) ($signals['shop_contacts_30d'] ?? 0)
            + (int) ($signals['whatsapp_contacts_30d'] ?? 0)
            + (int) ($signals['product_clicks_30d'] ?? 0)
            + ((int) ($signals['funnel_registrations_30d'] ?? 0) * 2)
        );
    }

    private function enrich_app_review_signal_snapshots(array $apps, string $period_start_datetime): array {
        if(empty($apps)) {
            return [];
        }

        $signal_maps = $this->get_app_review_signal_block_maps(array_keys($apps));
        $track_block_map = [];
        $funnel_block_map = [];

        foreach($apps as $link_id => &$app) {
            $signal_map = $signal_maps[(int) $link_id] ?? [
                'shop_block_ids' => [],
                'whatsapp_block_ids' => [],
                'product_block_ids' => [],
                'funnel_block_ids' => [],
            ];

            $app['shop_contacts_30d'] = 0;
            $app['whatsapp_contacts_30d'] = 0;
            $app['product_clicks_30d'] = 0;
            $app['funnel_registrations_30d'] = 0;
            $app['weighted_signal_score'] = 0;

            foreach(($signal_map['shop_block_ids'] ?? []) as $block_id) {
                $track_block_map[(int) $block_id]['shop_contacts_30d'][] = (int) $link_id;
            }

            foreach(($signal_map['whatsapp_block_ids'] ?? []) as $block_id) {
                $track_block_map[(int) $block_id]['whatsapp_contacts_30d'][] = (int) $link_id;
            }

            foreach(($signal_map['product_block_ids'] ?? []) as $block_id) {
                $track_block_map[(int) $block_id]['product_clicks_30d'][] = (int) $link_id;
            }

            foreach(($signal_map['funnel_block_ids'] ?? []) as $block_id) {
                $funnel_block_map[(int) $block_id][] = (int) $link_id;
            }
        }
        unset($app);

        if(!empty($track_block_map)) {
            $track_block_ids_sql = implode(',', array_map('intval', array_keys($track_block_map)));
            $track_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                FROM `track_links`
                WHERE `datetime` >= '{$period_start_datetime}'
                  AND `is_unique` = 1
                  AND `biolink_block_id` IN ({$track_block_ids_sql})
                GROUP BY `biolink_block_id`");

            while($row = $track_result->fetch_object()) {
                $block_id = (int) ($row->biolink_block_id ?? 0);
                $total = (int) ($row->total ?? 0);

                foreach(($track_block_map[$block_id] ?? []) as $signal_key => $link_ids) {
                    foreach((array) $link_ids as $link_id) {
                        if(isset($apps[$link_id])) {
                            $apps[$link_id][$signal_key] += $total;
                        }
                    }
                }
            }
        }

        if(!empty($funnel_block_map)) {
            $funnel_block_ids_sql = implode(',', array_map('intval', array_keys($funnel_block_map)));
            $funnel_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                FROM `data`
                WHERE `type` = 'lead_funnel'
                  AND `datetime` >= '{$period_start_datetime}'
                  AND `biolink_block_id` IN ({$funnel_block_ids_sql})
                GROUP BY `biolink_block_id`");

            while($row = $funnel_result->fetch_object()) {
                $block_id = (int) ($row->biolink_block_id ?? 0);
                $total = (int) ($row->total ?? 0);

                foreach((array) ($funnel_block_map[$block_id] ?? []) as $link_id) {
                    if(isset($apps[$link_id])) {
                        $apps[$link_id]['funnel_registrations_30d'] += $total;
                    }
                }
            }
        }

        foreach($apps as &$app) {
            $app['weighted_signal_score'] = $this->calculate_app_review_weighted_signal_score($app);
        }
        unset($app);

        return $apps;
    }

    private function compare_app_review_signal_rows(array $a, array $b): int {
        return (($b['weighted_signal_score'] ?? 0) <=> ($a['weighted_signal_score'] ?? 0))
            ?: (($b['shop_contacts_30d'] ?? 0) <=> ($a['shop_contacts_30d'] ?? 0))
            ?: (($b['whatsapp_contacts_30d'] ?? 0) <=> ($a['whatsapp_contacts_30d'] ?? 0))
            ?: (($b['funnel_registrations_30d'] ?? 0) <=> ($a['funnel_registrations_30d'] ?? 0))
            ?: (($b['product_clicks_30d'] ?? 0) <=> ($a['product_clicks_30d'] ?? 0))
            ?: ((string) ($a['url'] ?? '') <=> (string) ($b['url'] ?? ''));
    }

    private function get_default_app_review_benchmark(): array {
        return [
            'shop_contacts_30d' => 18,
            'whatsapp_contacts_30d' => 10,
            'product_clicks_30d' => 8,
            'funnel_registrations_30d' => 4,
            'weighted_signal_score' => 44,
        ];
    }

    private function get_app_review_benchmark_payload(array $selected_app = []): array {
        $period_30d_start = (new \DateTimeImmutable())->sub(new \DateInterval('P29D'))->format('Y-m-d 00:00:00');
        $now_datetime = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $shop_block_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $shop_block_types_sql = "'" . implode("','", $shop_block_types) . "'";
        $shop_condition = \Altum\Link::get_forever_shop_click_condition_sql('`tl`', '`bb`', $shop_block_types_sql);

        $qualified_users = [];
        $qualified_users_result = database()->query("SELECT `tl`.`user_id`, COUNT(*) AS `shop_contacts_30d`
            FROM `track_links` AS `tl`
            INNER JOIN `biolinks_blocks` AS `bb` ON `bb`.`biolink_block_id` = `tl`.`biolink_block_id`
            INNER JOIN `users` AS `u` ON `u`.`user_id` = `tl`.`user_id`
            WHERE `tl`.`datetime` >= '{$period_30d_start}'
              AND `tl`.`is_unique` = 1
              AND {$shop_condition}
              AND `u`.`status` = 1
              AND `u`.`plan_id` = '5'
              AND (`u`.`plan_expiration_date` IS NULL OR `u`.`plan_expiration_date` = '' OR `u`.`plan_expiration_date` >= '{$now_datetime}')
            GROUP BY `tl`.`user_id`
            HAVING `shop_contacts_30d` > 15");

        while($row = $qualified_users_result->fetch_object()) {
            $qualified_users[(int) ($row->user_id ?? 0)] = (int) ($row->shop_contacts_30d ?? 0);
        }

        if(empty($qualified_users)) {
            return [
                'benchmark' => $this->get_default_app_review_benchmark(),
                'peer_examples' => [],
            ];
        }

        $qualified_user_ids_sql = implode(',', array_map('intval', array_keys($qualified_users)));
        $users_biolinks_latest_sql = \Altum\Link::get_users_biolinks_latest_subquery('ub');
        $apps_result = database()->query("SELECT `ub`.`user_id`, `ub`.`biolink_id` AS `link_id`, `l`.`url`
            FROM {$users_biolinks_latest_sql}
            INNER JOIN `links` AS `l` ON `l`.`link_id` = `ub`.`biolink_id` AND `l`.`type` = 'biolink'
            WHERE `l`.`is_enabled` = 1
              AND `ub`.`user_id` IN ({$qualified_user_ids_sql})");

        $benchmark_apps = [];

        while($row = $apps_result->fetch_object()) {
            $link_id = (int) ($row->link_id ?? 0);

            if(!$link_id) {
                continue;
            }

            $benchmark_apps[$link_id] = [
                'link_id' => $link_id,
                'user_id' => (int) ($row->user_id ?? 0),
                'url' => (string) ($row->url ?? ''),
                'public_url' => !empty($row->url) ? url((string) $row->url) : '',
            ];
        }

        if(empty($benchmark_apps)) {
            return [
                'benchmark' => $this->get_default_app_review_benchmark(),
                'peer_examples' => [],
            ];
        }

        $benchmark_apps = array_values($this->enrich_app_review_signal_snapshots($benchmark_apps, $period_30d_start));
        usort($benchmark_apps, fn(array $a, array $b): int => $this->compare_app_review_signal_rows($a, $b));

        if(empty($benchmark_apps)) {
            return [
                'benchmark' => $this->get_default_app_review_benchmark(),
                'peer_examples' => [],
            ];
        }

        $top_benchmark_apps = array_slice($benchmark_apps, 0, min(5, count($benchmark_apps)));
        $totals = [
            'shop_contacts_30d' => 0,
            'whatsapp_contacts_30d' => 0,
            'product_clicks_30d' => 0,
            'funnel_registrations_30d' => 0,
            'weighted_signal_score' => 0,
        ];

        foreach($top_benchmark_apps as $app) {
            $totals['shop_contacts_30d'] += (int) ($app['shop_contacts_30d'] ?? 0);
            $totals['whatsapp_contacts_30d'] += (int) ($app['whatsapp_contacts_30d'] ?? 0);
            $totals['product_clicks_30d'] += (int) ($app['product_clicks_30d'] ?? 0);
            $totals['funnel_registrations_30d'] += (int) ($app['funnel_registrations_30d'] ?? 0);
            $totals['weighted_signal_score'] += (int) ($app['weighted_signal_score'] ?? 0);
        }

        $count = max(1, count($top_benchmark_apps));
        $selected_performance = !empty($selected_app) ? $selected_app : ['weighted_signal_score' => 0];
        $peer_examples = [];

        foreach($benchmark_apps as $app) {
            if($this->compare_app_review_signal_rows($app, $selected_performance) <= 0) {
                continue;
            }

            $peer_examples[] = [
                'label' => (string) (($app['url'] ?? '') ?: '-'),
                'public_url' => (string) ($app['public_url'] ?? ''),
            ];

            if(count($peer_examples) >= 3) {
                break;
            }
        }

        return [
            'benchmark' => [
                'shop_contacts_30d' => max(1, (int) round($totals['shop_contacts_30d'] / $count)),
                'whatsapp_contacts_30d' => max(1, (int) round($totals['whatsapp_contacts_30d'] / $count)),
                'product_clicks_30d' => max(1, (int) round($totals['product_clicks_30d'] / $count)),
                'funnel_registrations_30d' => max(1, (int) round($totals['funnel_registrations_30d'] / $count)),
                'weighted_signal_score' => max(1, (int) round($totals['weighted_signal_score'] / $count)),
            ],
            'peer_examples' => $peer_examples,
        ];
    }

    private function get_app_review_quality_payload(array $selected_app): array {
        $benchmark_payload = $this->get_app_review_benchmark_payload($selected_app);
        $benchmark = (array) ($benchmark_payload['benchmark'] ?? []);
        $performance = $selected_app;

        $ratios = [
            'shop_contacts_30d' => min(1.2, ((int) ($performance['shop_contacts_30d'] ?? 0)) / max(1, (int) ($benchmark['shop_contacts_30d'] ?? 1))),
            'whatsapp_contacts_30d' => min(1.2, ((int) ($performance['whatsapp_contacts_30d'] ?? 0)) / max(1, (int) ($benchmark['whatsapp_contacts_30d'] ?? 1))),
            'product_clicks_30d' => min(1.15, ((int) ($performance['product_clicks_30d'] ?? 0)) / max(1, (int) ($benchmark['product_clicks_30d'] ?? 1))),
            'funnel_registrations_30d' => min(1.25, ((int) ($performance['funnel_registrations_30d'] ?? 0)) / max(1, (int) ($benchmark['funnel_registrations_30d'] ?? 1))),
        ];

        $score = (int) round(min(100,
            ($ratios['shop_contacts_30d'] * 25) +
            ($ratios['whatsapp_contacts_30d'] * 25) +
            ($ratios['product_clicks_30d'] * 20) +
            ($ratios['funnel_registrations_30d'] * 30)
        ));

        $level_key = $score >= 80 ? 'strong' : ($score >= 60 ? 'growing' : 'foundation');

        return [
            'score' => $score,
            'level_key' => $level_key,
            'level_label' => l('ai_plan.app_review_quality_level.' . $level_key),
            'summary' => l('ai_plan.app_review_quality_summary.' . $level_key),
            'performance' => [
                'shop_contacts_30d' => (int) ($performance['shop_contacts_30d'] ?? 0),
                'whatsapp_contacts_30d' => (int) ($performance['whatsapp_contacts_30d'] ?? 0),
                'product_clicks_30d' => (int) ($performance['product_clicks_30d'] ?? 0),
                'funnel_registrations_30d' => (int) ($performance['funnel_registrations_30d'] ?? 0),
            ],
            'peer_examples' => (array) ($benchmark_payload['peer_examples'] ?? []),
        ];
    }

    public function index() {

        \Altum\Authentication::guard();

        /* Custom code: FC-2026-03-19: self-heal link states before opening editor */
        (new \Altum\Models\User())->sync_links_with_plan($this->user->user_id);
        /* /Custom code: FC-2026-03-19 */

        $link_id = isset($this->params[0]) ? (int) $this->params[0] : null;
        $method = isset($this->params[1]) && in_array($this->params[1], ['settings', 'statistics', 'download']) ? $this->params[1] : 'settings';

        /* Make sure the link exists and is accessible to the user */
        if(!$this->link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->getOne('links')) {
            redirect('dashboard');
        }

        $biolink_blocks = require APP_PATH . 'includes/biolink_blocks.php';
        $links_types = require APP_PATH . 'includes/links_types.php';

        $this->link->settings = json_decode($this->link->settings ?? '');
        $this->link->additional = json_decode($this->link->additional ?? '{}');
        $this->link->pixels_ids = json_decode($this->link->pixels_ids ?? '[]');
        $this->link->email_reports = json_decode($this->link->email_reports ?? '[]');
        $ai_profile_values = $this->get_ai_profile_values($this->user->preferences ?? null);
        $has_ai_growth_plan_access = \Altum\Authentication::is_admin() || !empty($this->user->plan_settings->ai_growth_plan_is_enabled ?? false);
        $app_review_is_accessible = $has_ai_growth_plan_access && (\Altum\Authentication::is_admin() || $this->is_ai_profile_complete($ai_profile_values));
        $app_review_locked_reason = !$has_ai_growth_plan_access ? l('global.info_message.plan_feature_no_access') : ($app_review_is_accessible ? '' : l('ai_plan.app_review_locked_entry_tooltip'));
        $app_review_page_url = url('ai-plan?section=app_review');
        $app_review_next_step_payload = $this->get_ai_overview_next_step_payload(
            (int) $this->link->link_id,
            $this->user->preferences ?? null,
            $has_ai_growth_plan_access,
            $this->is_ai_profile_complete($ai_profile_values),
            $app_review_is_accessible
        );

        /* Check for the plan limit */
        $plan_limit = match($this->link->type) {
            'biolink' => 'biolinks_limit',
            'link' => 'links_limit',
            'file' => 'files_limit',
            'vcard' => 'vcards_limit',
            'event' => 'events_limit',
            'static' => 'static_limit',
        };
        /* Custom code: FC-2026-03-19: allow editing the protected default biolink/vcard after downgrade */
        $default_biolink_id = (int) (fc_get_user_main_biolink_id((int) $this->user->user_id) ?? 0);
        $default_vcard_id = (int) (db()->where('user_id', $this->user->user_id)->getValue('users_vcards', 'vcard_id') ?? 0);
        $is_protected_default_link = ($this->link->type == 'biolink' && $default_biolink_id && (int) $this->link->link_id === $default_biolink_id)
            || ($this->link->type == 'vcard' && $default_vcard_id && (int) $this->link->link_id === $default_vcard_id);
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = '{$this->link->type}' AND `is_enabled` = 1")->fetch_object()->total ?? 0;
        if($this->user->plan_settings->{$plan_limit} != -1 && $total_rows > $this->user->plan_settings->{$plan_limit} && !$is_protected_default_link) {
            redirect('links?type=' . $this->link->type);
        }
        /* /Custom code: FC-2026-03-19 */

        /* Get the current domain if needed */
        $this->link->domain = $this->link->domain_id ? (new Domain())->get_domain_by_domain_id($this->link->domain_id) : null;

        /* Determine the actual full url */
        $this->link->full_url = $this->link->domain ? $this->link->domain->url . ($this->link->domain->link_id == $this->link->link_id ? null : $this->link->url) : SITE_URL . $this->link->url;

        /* Static links need the / for proper asset pathing */
        if($this->link->type == 'static') {
            $this->link->full_url .= '/';
        }

        $app_review_quality_payload = null;
        $ai_editor_payload = [];
        if($this->link->type === 'biolink') {
            $signal_snapshot = [
                (int) $this->link->link_id => [
                    'link_id' => (int) $this->link->link_id,
                    'user_id' => (int) $this->user->user_id,
                    'url' => (string) ($this->link->url ?? ''),
                    'public_url' => (string) ($this->link->full_url ?? ''),
                ],
            ];
            $signal_snapshot = $this->enrich_app_review_signal_snapshots($signal_snapshot, (new \DateTimeImmutable())->sub(new \DateInterval('P29D'))->format('Y-m-d 00:00:00'));
            $app_review_quality_payload = $this->get_app_review_quality_payload($signal_snapshot[(int) $this->link->link_id] ?? []);
            $current_block_attribution = $this->get_ai_block_attribution_payload((int) $this->link->link_id);
            $ai_editor_payload = $this->get_ai_editor_payload($this->link, $this->user->preferences ?? null, (array) ($app_review_quality_payload['performance'] ?? []), $current_block_attribution);
        }

        /* Main FCC app context */
        $biolink_main_id = (int) (fc_get_user_main_biolink_id((int) $this->user->user_id) ?? 0);
        $vcard_main = db()->where('user_id', $this->user->user_id)->getOne('users_vcards', ['vcard_id']);
        $is_main_biolink_app = $this->link->type === 'biolink' && $biolink_main_id && $biolink_main_id === (int) $this->link->link_id;
        $main_biolink_statistics_url = $biolink_main_id ? url('link/' . $biolink_main_id . '/statistics') : null;

        /* Set a custom title */
        Title::set(sprintf(l('link.title'), $this->link->url));

        /* Handle code for different parts of the page */
        switch($method) {
            case 'settings':

                /* Get available notification handlers */
                $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

                if($this->link->type == 'biolink') {
					$default_whatsapp_phone = $this->get_default_whatsapp_phone_for_user($this->user);

                    /* Custom code: FC-2026-03-09: provide blog product options for Forever product biolink block */
                    $blog_products = [];
                    $blog_products_result = db()
                        ->where('is_published', 1)
                        ->orderBy('datetime', 'DESC')
                        ->get('blog_posts', 300, ['blog_post_id', 'title', 'description', 'url', 'image', 'language']);
                    $blog_products_index = [];
                    $app_language_code = $this->link->settings->language_code ?? \Altum\Language::$default_code;

                    foreach($blog_products_result as $blog_post) {
                        $translation_key = trim((string) ($blog_post->url ?? ''));

                        if($translation_key === '') {
                            continue;
                        }

                        $language_code = null;
                        if(!empty($blog_post->language) && isset(\Altum\Language::$active_languages[$blog_post->language])) {
                            $language_code = \Altum\Language::$active_languages[$blog_post->language];
                        }

                        $language_prefix = $language_code ? $language_code . '/' : null;
                        $product_row = (object) [
                            'blog_post_id' => (int) $blog_post->blog_post_id,
                            'title' => (string) $blog_post->title,
                            'description' => mb_substr(trim(strip_tags((string) ($blog_post->description ?? ''))), 0, 220),
                            'blog_url' => SITE_URL . $language_prefix . 'blog/' . $blog_post->url,
                            'image_url' => !empty($blog_post->image) ? \Altum\Uploads::get_full_url('blog') . $blog_post->image : null,
                            'translation_key' => $translation_key,
                            'language_code' => $language_code ?: \Altum\Language::$default_code,
                        ];

                        if(!isset($blog_products_index[$translation_key])) {
                            $blog_products_index[$translation_key] = [
                                'rows' => [],
                                'ordered_language_codes' => [],
                            ];
                        }

                        $blog_products_index[$translation_key]['rows'][$product_row->language_code] = $product_row;

                        if(!in_array($product_row->language_code, $blog_products_index[$translation_key]['ordered_language_codes'], true)) {
                            $blog_products_index[$translation_key]['ordered_language_codes'][] = $product_row->language_code;
                        }
                    }

                    foreach($blog_products_index as $translation_key => $product_group) {
                        $preferred_product = $product_group['rows'][$app_language_code]
                            ?? $product_group['rows']['hr']
                            ?? $product_group['rows']['en']
                            ?? reset($product_group['rows']);

                        if(!$preferred_product) {
                            continue;
                        }

                        $preferred_product->translation_key = $translation_key;
                        $preferred_product->available_language_codes = array_values($product_group['ordered_language_codes']);
                        $preferred_product->available_languages_label = implode(' / ', array_map(static function($code) {
                            return mb_strtoupper((string) $code);
                        }, $preferred_product->available_language_codes));

                        $blog_products[] = $preferred_product;
                    }
                    /* /Custom code: FC-2026-03-09 */

                    /* Get available themes */
                    $biolinks_themes = (new BiolinksThemes())->get_biolinks_themes();

                    /* Get the links available for the biolink */
                    $link_links_result = database()->query("SELECT * FROM `biolinks_blocks` WHERE `link_id` = {$this->link->link_id} ORDER BY `order` ASC");
                    /* Custom code: FC-2026-03-23: lead funnel block phase 1 */
                    $user_biolinks = db()->where('user_id', $this->user->user_id)->where('type', 'biolink')->orderBy('url', 'ASC')->get('links', null, ['link_id', 'url']);
                    /* /Custom code: FC-2026-03-23 */

                    /* Add the modals for creating the links inside the biolink */
                    foreach($biolink_blocks as $key => $value) {

                        $data = [
                            'link' => $this->link,
                            'biolink_blocks' => $biolink_blocks,
							'default_whatsapp_phone' => $default_whatsapp_phone,
                            'blog_products' => $blog_products,
                            /* Custom code: FC-2026-03-23: lead funnel block phase 1 */
                            'user_biolinks' => $user_biolinks,
                            /* /Custom code: FC-2026-03-23 */
                        ];

                        /* Custom code */
                        if ($key == 'link_save_contact') {
                            $vcard_block = db()->where('user_id', $this->user->user_id)->where('type', 'vcard')->orderBy('datetime', 'ASC')->getOne('links');

                            if ($vcard_block) {
                                $data['vcard_block'] = $vcard_block;
                            } else {
                                $data['vcard_block'] = null;
                            }                          
                        }
                        /* /Custom code */

                        /* Custom code: FC-2026-03-06: skip missing create modal files to avoid black screen */
                        $create_modal_path = THEME_PATH . 'views/link/settings/biolink_blocks/' . $key . '/' . $key . '_create_modal.php';

                        if(is_file($create_modal_path)) {
                            $view = new \Altum\View('link/settings/biolink_blocks/' . $key . '/' . $key . '_create_modal', (array) $this);
                            \Altum\Event::add_content($view->run($data), 'modals');
                        } else {
                            dil('[BiolinkEditor] Missing create modal for block type: ' . $key . ' path: ' . $create_modal_path);
                        }
                        /* /Custom code: FC-2026-03-06 */
                    }

                    $data = [
                        'biolink_blocks' => $biolink_blocks,
                        'link' => $this->link,
                        'ai_editor_payload' => $ai_editor_payload,
                    ];
                    $view = new \Altum\View('link/settings/biolink_link_create_modal', (array) $this);
                    \Altum\Event::add_content($view->run($data), 'modals');

                    $data = [
                        'biolinks_themes' => $biolinks_themes,
                        'ai_editor_payload' => $ai_editor_payload,
                    ];
                    $view = new \Altum\View('link/settings/biolink_themes_modal', (array) $this);
                    \Altum\Event::add_content($view->run($data), 'modals');
                }

                /* Get the available domains to use */
                $domains = (new Domain())->get_available_domains_by_user($this->user);

                /* Existing projects */
                $projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);

                /* Existing splash pages */
                $splash_pages = (new \Altum\Models\SplashPages())->get_splash_pages_by_user_id($this->user->user_id);

                /* Existing pixels */
                $pixels = (new \Altum\Models\Pixel())->get_pixels($this->user->user_id);

                /* Existing payment processors */
                if(\Altum\Plugin::is_active('payment-blocks')) {
                    $payment_processors = (new \Altum\Models\PaymentProcessor())->get_payment_processors_by_user_id($this->user->user_id);
                }

                /* Prepare variables for the view */
                $data = [
                    'link'              => $this->link,
                    'method'            => $method,
                    'link_links_result' => $link_links_result ?? null,
                    'domains'           => $domains,
                    'projects'          => $projects,
                    'splash_pages'      => $splash_pages,
                    'pixels'            => $pixels,
                    'payment_processors'=> $payment_processors ?? null,
                    'biolink_blocks'    => $biolink_blocks,
                    'biolinks_themes'   => $biolinks_themes ?? null,
                    'links_types'       => $links_types,
                    'notification_handlers' => $notification_handlers ?? null,
                    'blog_products'     => $blog_products ?? [],
                    /* Custom code: FC-2026-03-23: lead funnel block phase 1 */
                    'user_biolinks'     => $user_biolinks ?? [],
                    /* /Custom code: FC-2026-03-23 */
                     /* Custom code */
                    'vcard_main' => $vcard_main ?? null,
                    'biolink_main' => $biolink_main ?? null,
                    'is_main_biolink_app' => $is_main_biolink_app,
                    'main_biolink_statistics_url' => $main_biolink_statistics_url,
                    'app_review_quality_payload' => $app_review_quality_payload,
                    'app_review_page_url' => $app_review_page_url,
                    'app_review_is_accessible' => $app_review_is_accessible,
                    'app_review_locked_reason' => $app_review_locked_reason,
                    'app_review_next_step_payload' => $app_review_next_step_payload,
                    'ai_editor_payload' => $ai_editor_payload,
                     /* /Custom code */
                ];

                break;


            case 'statistics':

                if(!$this->user->plan_settings->statistics) {
                    Alerts::add_error(l('global.info_message.plan_feature_no_access'));
                    redirect('links');
                }

                $action = isset($this->params[2]) && in_array($this->params[2], ['reset']) ? $this->params[2] : null;

                if($action) {
                    switch($action) {
                        case 'reset':

                            if (empty($_POST)) {
            throw_404();
        }

                            /* Team checks */
                            if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.links')) {
                                Alerts::add_error(l('global.info_message.team_no_access'));
                                redirect('link/' . $this->link->link_id . '/statistics');
                            }

                            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

                            if(!\Altum\Csrf::check()) {
                                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                                redirect('link/' . $this->link->link_id . '/statistics');
                            }

                            $datetime = \Altum\Date::get_start_end_dates_new($_POST['start_date'], $_POST['end_date']);

                            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                                /* Clear statistics data */
                                database()->query("DELETE FROM `track_links` WHERE `link_id` = {$this->link->link_id} AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')");

                                /* Set a nice success message */
                                Alerts::add_success(l('global.success_message.update2'));

                                redirect('link/' . $this->link->link_id . '/statistics');

                            }

                            redirect('link/' . $this->link->link_id . '/statistics');

                            break;
                    }
                }

                $type = isset($_GET['type']) && in_array($_GET['type'], ['overview', 'entries', 'referrer_host', 'referrer_path', 'continent_code', 'country', 'city_name', 'os', 'browser', 'device', 'language', 'utm_source', 'utm_medium', 'utm_campaign', 'hour']) ? input_clean($_GET['type']) : 'overview';

                $datetime = \Altum\Date::get_start_end_dates_new();

                /* Get data based on what statistics are needed */
                switch($type) {
                    case 'overview':

                        /* Get the required statistics */
                        $pageviews = [];
                        $pageviews_chart = [];
                        $latest_entries = [];
                        $totals = [
                            'pageviews' => 0,
                            'visitors' => 0,
                        ];

                        $convert_tz_sql = get_convert_tz_sql('`datetime`', $this->user->timezone);

                        $pageviews_result = database()->query("
                            SELECT
                                COUNT(`id`) AS `pageviews`,
                                SUM(`is_unique`) AS `visitors`,
                                DATE_FORMAT({$convert_tz_sql}, '{$datetime['query_date_format']}') AS `formatted_date`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND ({$convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `formatted_date`
                            ORDER BY
                                `formatted_date`
                        ");

                        /* Generate the raw chart data and save pageviews for later usage */
                        while($row = $pageviews_result->fetch_object()) {
                            $pageviews[] = $row;

                            $row->formatted_date = $datetime['process']($row->formatted_date, true);

                            $pageviews_chart[$row->formatted_date] = [
                                'pageviews' => $row->pageviews,
                                'visitors' => $row->visitors
                            ];

                            $totals['pageviews'] += $row->pageviews;
                            $totals['visitors'] += $row->visitors;
                        }

                        $pageviews_chart = get_chart_data($pageviews_chart);

                        $limit = $this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page;
                        $statistics_result = database()->query("
                            SELECT
                                `continent_code`,
                                `country_code`,
                                `city_name`,
                                `referrer_host`,
                                `device_type`,
                                `os_name`,
                                `browser_name`,
                                `browser_language`
                            FROM
                                `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                        ");

                        $latest_result = database()->query("
                            SELECT
                                *
                            FROM
                                `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            ORDER BY
                                `datetime` DESC
                            LIMIT {$limit}
                        ");

                        break;

                    case 'entries':

                        /* Prepare the filtering system */
                        $filters = (new \Altum\Filters([], [], ['datetime']));
                        $filters->set_default_order_by('id', $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
                        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

                        /* Prepare the paginator */
                        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `track_links` WHERE `link_id` = {$this->link->link_id} AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
                        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('link/' . $this->link->link_id . '/statistics?type=' . $type . '&start_date=' . $datetime['start_date'] . '&end_date=' . $datetime['end_date'] . $filters->get_get() . '&page=%d')));

                        $result = database()->query("
                            SELECT
                                *
                            FROM
                                `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            {$filters->get_sql_where()}
                            {$filters->get_sql_order_by()}
                            {$paginator->get_sql_limit()}
                        ");

                        break;

                    case 'referrer_host':
                    case 'continent_code':
                    case 'os':
                    case 'browser':
                    case 'device':
                    case 'language':

                        $columns = [
                            'referrer_host' => 'referrer_host',
                            'referrer_path' => 'referrer_path',
                            'continent_code' => 'continent_code',
                            'country' => 'country_code',
                            'city_name' => 'city_name',
                            'os' => 'os_name',
                            'browser' => 'browser_name',
                            'device' => 'device_type',
                            'language' => 'browser_language'
                        ];

                        $result = database()->query("
                            SELECT
                                `{$columns[$type]}`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `{$columns[$type]}`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'referrer_path':

                        $referrer_host = input_clean($_GET['referrer_host']);

                        $result = database()->query("
                            SELECT
                                `referrer_path`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND `referrer_host` = '{$referrer_host}'
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `referrer_path`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'country':

                        $continent_code = isset($_GET['continent_code']) ? input_clean($_GET['continent_code']) : null;

                        $result = database()->query("
                            SELECT
                                `country_code`,
                                " . ($continent_code ? "`continent_code`," : null) . "
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                " . ($continent_code ? "AND `continent_code` = '{$continent_code}'" : null) . "
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                " . ($continent_code ? "`continent_code`," : null) . "
                                `country_code`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'city_name':

                        $country_code = isset($_GET['country_code']) ? input_clean($_GET['country_code']) : null;

                        $result = database()->query("
                            SELECT
                                `city_name`,
                                `country_code`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                " . ($country_code ? "AND `country_code` = '{$country_code}'" : null) . "
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `country_code`,
                                `city_name`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'utm_source':

                        $result = database()->query("
                            SELECT
                                `utm_source`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                                AND `utm_source` IS NOT NULL
                            GROUP BY
                                `utm_source`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'utm_medium':

                        $utm_source = input_clean($_GET['utm_source']);

                        $result = database()->query("
                            SELECT
                                `utm_medium`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND `utm_source` = '{$utm_source}'
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `utm_medium`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'utm_campaign':

                        $utm_source = input_clean($_GET['utm_source']);
                        $utm_medium = input_clean($_GET['utm_medium']);

                        $result = database()->query("
                            SELECT
                                `utm_campaign`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND `utm_source` = '{$utm_source}'
                                AND `utm_medium` = '{$utm_medium}'
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `utm_campaign`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'hour':

                        /* Get the timezone conversion SQL */
                        $convert_tz_sql = get_convert_tz_sql('`datetime`', $this->user->timezone);

                        /* Group by HOUR after timezone adjustment */
                        $result = database()->query("
                            SELECT 
                                HOUR({$convert_tz_sql}) AS `hour`,
                                COUNT(*) AS `total`
                            FROM
                                `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND ({$convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `hour`
                            ORDER BY
                                `total` DESC
                        ");

                        break;
                }

                switch($type) {
                    case 'overview':

                        $statistics_keys = [
                            'continent_code',
                            'country_code',
                            'city_name',
                            'referrer_host',
                            'device_type',
                            'os_name',
                            'browser_name',
                            'browser_language'
                        ];

                        $latest = [];
                        $statistics = [];
                        foreach($statistics_keys as $key) {
                            $statistics[$key] = [];
                            $statistics[$key . '_total_sum'] = 0;
                        }

                        $has_data = ($statistics_result->num_rows ?? 0) || ($latest_result->num_rows ?? 0);

                        while($row = $statistics_result->fetch_object()) {
                            foreach($statistics_keys as $key) {
                                $row->{$key} = $row->{$key} ?? '';
                                $statistics[$key][$row->{$key}] = isset($statistics[$key][$row->{$key}]) ? $statistics[$key][$row->{$key}] + 1 : 1;

                                $statistics[$key . '_total_sum']++;
                            }
                        }

                        foreach($statistics_keys as $key) {
                            arsort($statistics[$key]);
                        }

                        while($row = $latest_result->fetch_object()) {
                            $latest_entries[] = $row;
                        }

                        /* Prepare the statistics method View */
                        $data = [
                            'statistics' => $statistics,
                            'link' => $this->link,
                            'method' => $method,
                            'datetime' => $datetime,
                            'latest' => $latest_entries,
                            'pageviews' => $pageviews,
                            'pageviews_chart' => $pageviews_chart,
                            'totals' => $totals,
                            'url' => 'link/' . $this->link->link_id,
                        ];

                        break;

                    case 'entries':

                        /* Store all the results from the database */
                        $statistics = [];

                        while($row = $result->fetch_object()) {
                            $statistics[] = $row;
                        }

                        /* Prepare the pagination view */
                        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

                        /* Prepare the statistics method View */
                        $data = [
                            'type' => $type,
                            'rows' => $statistics,
                            'link' => $this->link,
                            'method' => $method,
                            'datetime' => $datetime,
                            'pagination' => $pagination,
                            'filters' => $filters,
                            'url' => 'link/' . $this->link->link_id,
                        ];

                        $has_data = count($statistics);

                        break;

                    case 'referrer_host':
                    case 'continent_code':
                    case 'country':
                    case 'city_name':
                    case 'os':
                    case 'browser':
                    case 'device':
                    case 'language':
                    case 'referrer_path':
                    case 'utm_source':
                    case 'utm_medium':
                    case 'utm_campaign':

                        /* Store all the results from the database */
                        $statistics = [];
                        $statistics_total_sum = 0;

                        while($row = $result->fetch_object()) {
                            $statistics[] = $row;

                            $statistics_total_sum += $row->total;
                        }

                        /* Prepare the statistics method View */
                        $data = [
                            'rows' => $statistics,
                            'total_sum' => $statistics_total_sum,
                            'link' => $this->link,
                            'method' => $method,
                            'datetime' => $datetime,
                            'type' => $type,
                            'url' => 'link/' . $this->link->link_id,

                            'referrer_host' => $referrer_host ?? null,
                            'continent_code' => $continent_code ?? null,
                            'country_code' => $country_code ?? null,
                            'utm_source' => $utm_source ?? null,
                            'utm_medium' => $utm_medium ?? null,
                        ];

                        $has_data = count($statistics);

                        break;

                    case 'hour':

                        $statistics = [];
                        $statistics_total_sum = 0;

                        while($row = $result->fetch_object()) {
                            $statistics[] = $row;
                            $statistics_total_sum += $row->total;
                        }

                        $data = [
                            'rows' => $statistics,
                            'total_sum' => $statistics_total_sum,
                            'link' => $this->link,
                            'method' => $method,
                            'datetime' => $datetime,
                            'type' => $type,
                            'url' => 'link/' . $this->link->link_id,
                        ];

                        $has_data = count($statistics);

                        break;
                }

                /* Export handler */
                process_export_csv($statistics);
                process_export_json($statistics);

                $view = new \Altum\View('link/statistics/statistics_' . $type, (array) $this);
                $this->add_view_content('statistics', $view->run($data));

                /* Prepare variables for the view */
                $data = [
                    'link' => $this->link,
                    'method' => $method,
                    'type' => $type,
                    'datetime' => $datetime,
                    'has_data' => $has_data,
                    'biolink_main' => $biolink_main ?? null,
                    'is_main_biolink_app' => $is_main_biolink_app,
                    'main_biolink_statistics_url' => $main_biolink_statistics_url,
                    'app_review_quality_payload' => $app_review_quality_payload,
                    'app_review_page_url' => $app_review_page_url,
                    'app_review_is_accessible' => $app_review_is_accessible,
                    'app_review_locked_reason' => $app_review_locked_reason,
                    'ai_editor_payload' => $ai_editor_payload,
                ];

                break;

            case 'download':

                /* Static links need the / for proper asset pathing */
                if($this->link->type == 'static') {
                    $this->link->full_url .= '/';

                    $full_requested_file = \Altum\Uploads::get_path('static') . $this->link->settings->static_folder . '/';

                    \Altum\Uploads::download_files_as_zip(['' => $full_requested_file], l('global.download'));

                    die();
                }

                break;
        }

        /* Delete Modal */
        $view = new \Altum\View('links/link_delete_modal', (array) $this);
        \Altum\Event::add_content($view->run(), 'modals');

        /* Delete Modal */
        $view = new \Altum\View('biolink-block/biolink_block_delete_modal', (array) $this);
        \Altum\Event::add_content($view->run(), 'modals');

        /* Prepare the method View */
        $view = new \Altum\View('link/' . $method, (array) $this);
        $this->add_view_content('method', $view->run($data));

        /* Prepare the view */
        $data = [
            'link' => $this->link,
            'method' => $method,
            'links_types' => $links_types,
            /* Custom code */
            'vcard_main' => $vcard_main ?? null,
            'biolink_main' => $biolink_main ?? null,
            'is_main_biolink_app' => $is_main_biolink_app,
            'main_biolink_statistics_url' => $main_biolink_statistics_url,
            'app_review_quality_payload' => $app_review_quality_payload,
            'app_review_page_url' => $app_review_page_url,
            'app_review_is_accessible' => $app_review_is_accessible,
            'app_review_locked_reason' => $app_review_locked_reason,
            'app_review_next_step_payload' => $app_review_next_step_payload,
            'ai_editor_payload' => $ai_editor_payload,
            /* /Custom code */
        ];

        $view = new \Altum\View('link/index', (array) $this);
        $this->add_view_content('content', $view->run($data));

    }

}
