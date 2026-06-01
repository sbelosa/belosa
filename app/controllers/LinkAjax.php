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
use Altum\Date;
use Altum\Models\BiolinksThemes;
use Altum\Models\Domain;
use Altum\Response;


defined('ALTUMCODE') || die();

class LinkAjax extends Controller {
	public $links_types = null;

	private function normalize_whatsapp_phone($phone): string {
		return preg_replace('/\D+/', '', (string) $phone);
	}

	private function extract_user_phone_from_preferences($user): string {
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

	private function get_default_whatsapp_phone_for_current_user(): string {
		$referrer_phone = '';
		if(!empty($this->user->referred_by)) {
			$referrer_user = db()->where('user_id', (int) $this->user->referred_by)->getOne('users', ['preferences']);
			$referrer_phone = $this->extract_user_phone_from_preferences($referrer_user);
		}

		if($referrer_phone !== '') {
			return $referrer_phone;
		}

		return $this->extract_user_phone_from_preferences($this->user);
	}

	public function index() {
		\Altum\Authentication::guard();

		if(!empty($_POST) && (\Altum\Csrf::check('token') || \Altum\Csrf::check('global_token')) && isset($_POST['request_type'])) {

			$this->links_types = require APP_PATH . 'includes/links_types.php';

			switch($_POST['request_type']) {

				/* Status toggle */
				case 'is_enabled_toggle': $this->is_enabled_toggle(); break;

				/* Create */
				case 'create': $this->create(); break;

				/* Update */
				case 'update': $this->update(); break;

				/* Delete */
				case 'delete': $this->delete(); break;

				/* Duplicate */
				case 'duplicate': $this->duplicate(); break;

				/* Custom code: FC-2026-03-06: biolink factory reset endpoint */
				case 'reset_biolink_factory': $this->reset_biolink_factory(); break;
				case 'apply_ai_theme_pack': $this->apply_ai_theme_pack(); break;
				case 'apply_ai_color_bundle': $this->apply_ai_color_bundle(); break;
				case 'apply_ai_primary_block_focus': $this->apply_ai_primary_block_focus(); break;
				case 'add_ai_recommended_block': $this->add_ai_recommended_block(); break;
				case 'apply_ai_layout_actions': $this->apply_ai_layout_actions(); break;
				case 'apply_ai_block_bundle': $this->apply_ai_block_bundle(); break;
				case 'restore_ai_layout_backup': $this->restore_ai_layout_backup(); break;
				case 'restore_ai_bundle_backup': $this->restore_ai_bundle_backup(); break;
				/* /Custom code: FC-2026-03-06 */

			}

		}

		die();
	}

	private function get_plan_limit_key_by_link_type($type) {
		return match($type) {
			'biolink' => 'biolinks_limit',
			'link' => 'links_limit',
			'file' => 'files_limit',
			'vcard' => 'vcards_limit',
			'event' => 'events_limit',
			'static' => 'static_limit',
			default => null,
		};
	}

	private function can_enable_link_by_plan($type, $link_id = null) {
		$plan_limit_key = $this->get_plan_limit_key_by_link_type($type);

		if(!$plan_limit_key) {
			return true;
		}

		$limit = isset($this->user->plan_settings->{$plan_limit_key}) ? (int) $this->user->plan_settings->{$plan_limit_key} : -1;

		if($limit == -1) {
			return true;
		}

		$total_enabled = db()
			->where('user_id', $this->user->user_id)
			->where('type', $type)
			->where('is_enabled', 1)
			->where('link_id', $link_id, '<>')
			->getValue('links', 'count(`link_id`)');

		return $total_enabled < max($limit, 0);
	}

	/* Custom code: FC-2026-03-06: prioritize template linked to /link/83 with safe fallback */
	private function get_factory_biolink_template() {
		$biolink_template = db()->where('is_enabled', 1)->where('link_id', 83)->getOne('biolinks_templates');

		if(!$biolink_template) {
			$biolink_template = db()->where('link_id', 83)->getOne('biolinks_templates');
		}

		if(!$biolink_template) {
			$biolink_template = db()->where('is_enabled', 1)->where('biolink_template_id', 1)->getOne('biolinks_templates');
		}

		if(!$biolink_template) {
			$biolink_template = db()->where('biolink_template_id', 1)->getOne('biolinks_templates');
		}

		if(!$biolink_template) {
			$biolink_template = db()->where('is_enabled', 1)->orderBy('biolink_template_id', 'ASC')->getOne('biolinks_templates');
		}

		if(!$biolink_template) {
			$biolink_template = db()->orderBy('biolink_template_id', 'ASC')->getOne('biolinks_templates');
		}

		return $biolink_template;
	}
	/* /Custom code: FC-2026-03-06 */

	private function normalize_json_to_array($value): array {
		if(is_string($value)) {
			$value = json_decode($value, true);
		} elseif(is_object($value)) {
			$value = json_decode(json_encode($value), true);
		}

		return is_array($value) ? $value : [];
	}

	private function prepare_biolink_additional_for_storage(array $additional): ?string {
		return fcc_ai_prepare_biolink_additional_for_storage($additional);
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

	private function get_preferences_object($preferences = null): \stdClass {
		if($preferences === null) {
			$preferences = $this->user->preferences ?? new \stdClass();
		}

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

	private function is_biolink_block_enabled_for_current_plan(string $block_type): bool {
		$enabled_biolink_blocks = $this->user->plan_settings->enabled_biolink_blocks ?? (object) [];

		if(is_array($enabled_biolink_blocks)) {
			$enabled_biolink_blocks = (object) $enabled_biolink_blocks;
		}

		return (bool) ($enabled_biolink_blocks->{$block_type} ?? false);
	}

	private function validate_biolink_block_access_for_current_plan(string $block_type): void {
		if($this->is_biolink_block_enabled_for_current_plan($block_type)) {
			return;
		}

		$message = l('global.info_message.plan_feature_no_access');

		if(settings()->payment->is_enabled) {
			$message .= ' ' . l('global.info_message.plan_upgrade') . ': ' . url('plan');
		}

		Response::json($message, 'error');
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

	private function get_ai_reactivatable_missing_recommendation_block(array $recommendation, array $block_catalog): array {
		$block_type = trim((string) ($recommendation['block_type'] ?? ''));
		$role_key = trim((string) ($recommendation['role_key'] ?? ''));

		if($role_key === 'core_business_offer') {
			foreach($block_catalog as $block) {
				if($this->is_ai_start_paket_business_offer_block($block)) {
					return $block;
				}
			}
		}

		if($role_key === 'core_discount_offer') {
			return $this->get_first_ai_catalog_block_by_types($block_catalog, ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], false);
		}

		if($block_type === 'lead_funnel') {
			return $this->get_first_ai_catalog_block_by_types($block_catalog, ['lead_funnel'], false);
		}

		if($block_type === 'custom_html_whatsapp') {
			return $this->get_first_ai_catalog_block_by_types($block_catalog, ['custom_html_whatsapp'], false);
		}

		if($this->is_ai_chatbot_block_type($block_type)) {
			return $this->get_first_ai_catalog_block_by_types($block_catalog, [$block_type], false)
				?: $this->get_first_ai_catalog_block_by_types($block_catalog, $this->get_ai_chatbot_block_types(), false);
		}

		return [];
	}

	private function get_ai_protected_core_block_ids(array $blocks): array {
		$protected_ids = [];

		foreach($blocks as $block) {
			$block_id = (int) ($block['biolink_block_id'] ?? $block['block_id'] ?? 0);

			if($block_id <= 0) {
				continue;
			}

			$normalized_block = $block;

			if(empty($normalized_block['label']) && !empty($normalized_block['settings']) && is_array($normalized_block['settings'])) {
				$normalized_block['label'] = $this->get_ai_biolink_block_preview_label((string) ($normalized_block['type'] ?? ''), $this->decode_ai_biolink_block_settings($normalized_block['settings']));
			}

			if($this->is_ai_protected_core_block($normalized_block)) {
				$protected_ids[] = $block_id;
			}
		}

		return array_values(array_unique($protected_ids));
	}

	private function normalize_ai_signal_protection_summary($value): array {
		$value = $this->normalize_json_to_array($value);

		$normalize_row = function($item): ?array {
			if(!is_array($item)) {
				return null;
			}

			return [
				'block_id' => max(0, (int) ($item['block_id'] ?? 0)),
				'label' => $this->normalize_ai_visible_copy($item['label'] ?? ''),
				'status' => trim((string) ($item['status'] ?? '')),
				'planned_action' => trim((string) ($item['planned_action'] ?? '')),
				'reason' => trim((string) ($item['reason'] ?? '')),
			];
		};

		$build_rows = function(array $items) use ($normalize_row): array {
			$rows = [];
			foreach($items as $item) {
				$row = $normalize_row($item);
				if($row) {
					$rows[] = $row;
				}
				if(count($rows) >= 4) {
					break;
				}
			}
			return $rows;
		};

		$protected_block_ids = array_values(array_unique(array_filter(array_map(static function($item): int {
			return max(0, (int) $item);
		}, (array) ($value['protected_block_ids'] ?? [])))));

		$kept_signal_blocks = $build_rows((array) ($value['kept_signal_blocks'] ?? []));
		$repositioned_focus_blocks = $build_rows((array) ($value['repositioned_focus_blocks'] ?? []));
		$summary = trim((string) ($value['summary'] ?? ''));

		return [
			'has_items' => $summary !== '' || !empty($protected_block_ids) || !empty($kept_signal_blocks) || !empty($repositioned_focus_blocks),
			'summary' => $summary,
			'protected_block_ids' => $protected_block_ids,
			'kept_signal_blocks' => $kept_signal_blocks,
			'repositioned_focus_blocks' => $repositioned_focus_blocks,
		];
	}

	private function build_ai_signal_protection_summary(array $block_attribution_payload, array $layout_actions = []): array {
		$block_attribution_payload = $this->normalize_ai_block_attribution_payload($block_attribution_payload);
		$layout_actions = $this->normalize_ai_layout_actions($layout_actions);
		$action_map = [];

		foreach($layout_actions as $action) {
			$block_id = (int) ($action['block_id'] ?? 0);
			if($block_id <= 0 || isset($action_map[$block_id])) {
				continue;
			}

			$action_map[$block_id] = (string) ($action['action'] ?? '');
		}

		$protected_block_ids = [];
		$kept_signal_blocks = [];
		$repositioned_focus_blocks = [];

		foreach((array) ($block_attribution_payload['all_blocks'] ?? []) as $block) {
			$block_id = (int) ($block['block_id'] ?? 0);
			if($block_id <= 0) {
				continue;
			}

			$status = (string) ($block['status'] ?? '');
			$label = $this->normalize_ai_visible_copy((string) (($block['label'] ?? '') ?: ($block['type'] ?? 'Blok')));
			$planned_action = (string) ($action_map[$block_id] ?? '');

			if(in_array($status, ['high_signal', 'contributing'], true)) {
				$protected_block_ids[] = $block_id;

				if(in_array($planned_action, ['hide_for_now', 'consider_remove'], true)) {
					$planned_action = 'keep';
				}

				$kept_signal_blocks[] = [
					'block_id' => $block_id,
					'label' => $label,
					'status' => $status,
					'planned_action' => $planned_action !== '' ? $planned_action : 'keep',
					'reason' => $status === 'high_signal'
						? 'Ovaj blok ostaje aktivan jer već donosi mjerljiv signal.'
						: 'Ovaj blok već doprinosi rezultatu pa ga ne treba gasiti.',
				];
				continue;
			}

			if(!in_array($status, ['focus_risk', 'critical_focus_risk'], true) || !in_array($planned_action, ['move_down', 'consider_remove', 'hide_for_now'], true)) {
				continue;
			}

			$repositioned_focus_blocks[] = [
				'block_id' => $block_id,
				'label' => $label,
				'status' => $status,
				'planned_action' => $planned_action,
				'reason' => $planned_action === 'move_down'
					? 'Ovaj blok ide niže jer prerano uzima pažnju bez signala.'
					: 'Ovaj blok trenutno nije prioritet jer nema mjerljiv rezultat.',
			];
		}

		return $this->normalize_ai_signal_protection_summary([
			'summary' => !empty($kept_signal_blocks)
				? 'Blokovi koji donose signal ostaju aktivni, a bez rezultata se spuštaju niže.'
				: '',
			'protected_block_ids' => array_values(array_unique($protected_block_ids)),
			'kept_signal_blocks' => array_slice($kept_signal_blocks, 0, 4),
			'repositioned_focus_blocks' => array_slice($repositioned_focus_blocks, 0, 4),
		]);
	}

	private function get_ai_protected_signal_block_ids(array $additional, int $link_id = 0, array $blocks = [], $preferences = null): array {
		$summaries = [];
		$direct_summary = $this->normalize_ai_signal_protection_summary($additional['fcc_ai_signal_protection_summary'] ?? []);
		if(!empty($direct_summary['has_items'])) {
			$summaries[] = $direct_summary;
		}

		$review_summary = $this->normalize_json_to_array($additional['fcc_ai_review_summary'] ?? []);
		$embedded_summary = $this->normalize_ai_signal_protection_summary($review_summary['signal_protection_summary'] ?? []);
		if(!empty($embedded_summary['has_items'])) {
			$summaries[] = $embedded_summary;
		}

		if(empty($summaries) && $link_id > 0) {
			$latest_review = $this->get_latest_saved_ai_review_for_link($link_id, $preferences);
			if(!empty($latest_review)) {
				$summaries[] = $this->build_ai_signal_protection_summary(
					$this->normalize_ai_block_attribution_payload($latest_review['block_attribution_snapshot'] ?? []),
					$this->normalize_ai_layout_actions($latest_review['layout_actions'] ?? [])
				);
			}
		}

		$existing_ids = [];
		foreach($blocks as $block) {
			$block_id = (int) ($block['biolink_block_id'] ?? $block['block_id'] ?? 0);
			if($block_id > 0) {
				$existing_ids[$block_id] = true;
			}
		}

		$protected_ids = [];
		foreach($summaries as $summary) {
			foreach((array) ($summary['protected_block_ids'] ?? []) as $block_id) {
				$block_id = (int) $block_id;
				if($block_id > 0 && (empty($existing_ids) || isset($existing_ids[$block_id]))) {
					$protected_ids[] = $block_id;
				}
			}
		}

		return array_values(array_unique($protected_ids));
	}

	private function get_ai_block_semantic_label(array $block): string {
		$settings = $this->normalize_json_to_array($block['settings'] ?? null);

		foreach(['name', 'title', 'heading', 'button_text', 'text'] as $property) {
			$value = trim((string) ($settings[$property] ?? ''));

			if($value !== '') {
				return $value;
			}
		}

		return trim((string) ($block['label'] ?? ''));
	}

	private function get_ai_protected_block_cluster_key(array $block): string {
		$type = trim((string) ($block['type'] ?? ''));
		$label = $this->get_ai_block_semantic_label($block);
		$context = $label . ' ' . trim((string) ($block['location_url'] ?? ''));

		if($type === 'lead_funnel') {
			return 'primary_funnel';
		}

		if($type === 'custom_html_whatsapp') {
			return 'whatsapp';
		}

		if($this->is_ai_chatbot_block_type($type)) {
			return 'floating_ai_assistant';
		}

		if(in_array($type, ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)) {
			return 'webshop_offer';
		}

		if($this->is_ai_start_paket_business_offer_block($block)) {
			return 'business_offer';
		}

		if($type === 'link_forever_shop') {
			if($this->ai_text_has_any($context, ['partner', 'suradnik', 'upis', 'prijava', 'registracija', 'start paket', 'start-paket'])) {
				return 'business_offer';
			}

			if($this->ai_text_has_any($context, ['webshop', 'web shop', 'shop', 'popust', 'proizvod', 'proizvodi'])) {
				return 'webshop_offer';
			}
		}

		return '';
	}

	private function should_replace_ai_protected_cluster_candidate(array $current, array $candidate, array $visible_map, array $core_map, array $signal_map): bool {
		$current_id = (int) ($current['biolink_block_id'] ?? 0);
		$candidate_id = (int) ($candidate['biolink_block_id'] ?? 0);

		$current_visible = isset($visible_map[$current_id]);
		$candidate_visible = isset($visible_map[$candidate_id]);
		if($current_visible !== $candidate_visible) {
			return $candidate_visible;
		}

		$current_start_paket = $this->is_ai_start_paket_business_offer_block($current);
		$candidate_start_paket = $this->is_ai_start_paket_business_offer_block($candidate);
		if($current_start_paket !== $candidate_start_paket) {
			return $candidate_start_paket;
		}

		$current_discount = in_array((string) ($current['type'] ?? ''), ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true);
		$candidate_discount = in_array((string) ($candidate['type'] ?? ''), ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true);
		if($current_discount !== $candidate_discount) {
			return $candidate_discount;
		}

		$current_core = isset($core_map[$current_id]);
		$candidate_core = isset($core_map[$candidate_id]);
		if($current_core !== $candidate_core) {
			return $candidate_core;
		}

		$current_signal = isset($signal_map[$current_id]);
		$candidate_signal = isset($signal_map[$candidate_id]);
		if($current_signal !== $candidate_signal) {
			return $candidate_signal;
		}

		$current_enabled = (int) ($current['is_enabled'] ?? 0) === 1;
		$candidate_enabled = (int) ($candidate['is_enabled'] ?? 0) === 1;
		if($current_enabled !== $candidate_enabled) {
			return $candidate_enabled;
		}

		$current_order = (int) ($current['order'] ?? PHP_INT_MAX);
		$candidate_order = (int) ($candidate['order'] ?? PHP_INT_MAX);
		if($current_order !== $candidate_order) {
			return $candidate_order < $current_order;
		}

		return $candidate_id < $current_id;
	}

	private function collapse_ai_protected_block_ids(array $blocks, array $protected_core_block_ids, array $protected_signal_block_ids, array $visible_ids): array {
		$core_map = array_fill_keys(array_values(array_filter(array_map('intval', $protected_core_block_ids))), true);
		$signal_map = array_fill_keys(array_values(array_filter(array_map('intval', $protected_signal_block_ids))), true);
		$visible_map = array_fill_keys(array_values(array_filter(array_map('intval', $visible_ids))), true);
		$cluster_winners = [];
		$protected_ids = [];

		foreach($blocks as $block) {
			$block_id = (int) ($block['biolink_block_id'] ?? 0);

			if($block_id <= 0 || (!isset($core_map[$block_id]) && !isset($signal_map[$block_id]))) {
				continue;
			}

			$cluster_key = $this->get_ai_protected_block_cluster_key($block);
			if($cluster_key === '') {
				$protected_ids[] = $block_id;
				continue;
			}

			if(!isset($cluster_winners[$cluster_key]) || $this->should_replace_ai_protected_cluster_candidate($cluster_winners[$cluster_key], $block, $visible_map, $core_map, $signal_map)) {
				$cluster_winners[$cluster_key] = $block;
			}
		}

		foreach($cluster_winners as $winner) {
			$winner_id = (int) ($winner['biolink_block_id'] ?? 0);
			if($winner_id > 0) {
				$protected_ids[] = $winner_id;
			}
		}

		return array_values(array_unique($protected_ids));
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
				'priority' => 3,
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

	private function get_default_ai_chatbot_embed_html(string $block_type): string {
		return '';
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
		$value = $this->normalize_json_to_array($value);
		$allowed_fields = ['name', 'title', 'button_text', 'thank_you_button_text', 'description', 'text', 'message', 'popup_title', 'popup_subtitle', 'thank_you_title', 'thank_you_text'];
		$normalized = [];

		foreach($value as $item) {
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

	private function normalize_ai_block_patch_pack($value): array {
		$value = $this->normalize_json_to_array($value);
		$normalized = [];

		foreach($value as $item) {
			if(!is_array($item)) {
				continue;
			}

			$settings = [];

			foreach((array) ($item['settings'] ?? []) as $setting_key => $setting_value) {
				if(!is_scalar($setting_value)) {
					continue;
				}

				$normalized_key = preg_replace('/[^a-z0-9_]+/i', '_', (string) $setting_key) ?? '';
				$normalized_key = trim($normalized_key, '_');

				if($normalized_key === '') {
					continue;
				}

				$settings[$normalized_key] = str_contains($normalized_key, 'color') || str_contains($normalized_key, 'shadow')
					? ($this->normalize_ai_css_color($setting_value, str_contains($normalized_key, 'shadow')) ?: trim((string) $setting_value))
					: trim((string) $setting_value);
			}

			if(empty($settings)) {
				continue;
			}

			$normalized[] = [
				'block_id' => (int) ($item['block_id'] ?? 0),
				'block_type' => trim((string) ($item['block_type'] ?? $item['type'] ?? '')),
				'reason' => trim((string) ($item['reason'] ?? '')),
				'settings' => $settings,
			];
		}

		return array_slice($normalized, 0, 8);
	}

	private function normalize_ai_layout_actions($value): array {
		$value = $this->normalize_json_to_array($value);
		$allowed_actions = ['move_up', 'move_down', 'keep_top', 'keep_after_primary', 'consider_remove', 'hide_for_now', 'add_block', 'swap_order', 'keep'];
		$normalized = [];

		foreach($value as $item) {
			if(!is_array($item) || empty($item['action']) || empty($item['why'])) {
				continue;
			}

			$action = trim((string) ($item['action'] ?? ''));
			if(!in_array($action, $allowed_actions, true)) {
				continue;
			}

			$normalized[] = [
				'action' => $action,
				'block_id' => (int) ($item['block_id'] ?? 0),
				'block_type' => trim((string) ($item['block_type'] ?? $item['type'] ?? '')),
				'label' => $this->normalize_ai_visible_copy($item['label'] ?? ''),
				'why' => trim((string) ($item['why'] ?? '')),
			];
		}

		return array_slice($normalized, 0, 8);
	}

	private function normalize_ai_missing_block_seed_settings($value): array {
		$value = $this->normalize_json_to_array($value);
		$allowed_keys = ['name', 'title', 'text', 'message', 'button_text', 'description', 'popup_title', 'popup_subtitle', 'thank_you_title', 'thank_you_text', 'thank_you_button_text', 'open_mode', 'location_url', 'product_translation_key', 'product_language_mode', 'product_language_code', 'product_fallback_language_code', 'product_image_url'];
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
		$value = $this->normalize_json_to_array($value);
		$normalized = [];

		foreach($value as $index => $item) {
			if(!is_array($item)) {
				continue;
			}

			$block_type = trim((string) ($item['block_type'] ?? $item['type'] ?? ''));
			$why = trim((string) ($item['why'] ?? $item['reason'] ?? ''));

			if($block_type === '' || $why === '') {
				continue;
			}

			$normalized[] = [
				'recommendation_key' => trim((string) ($item['recommendation_key'] ?? '')),
				'block_type' => $block_type,
				'role_key' => trim((string) ($item['role_key'] ?? $item['semantic_role'] ?? '')),
				'label' => $this->normalize_ai_visible_copy($item['label'] ?? $item['name'] ?? ''),
				'why' => $why,
				'priority' => max(1, min(9, (int) ($item['priority'] ?? ($index + 1)))),
				'insert_after_block_id' => max(0, (int) ($item['insert_after_block_id'] ?? 0)),
				'insert_after_type' => trim((string) ($item['insert_after_type'] ?? '')),
				'insert_after_label' => $this->normalize_ai_visible_copy($item['insert_after_label'] ?? ''),
				'allow_existing_type' => !empty($item['allow_existing_type']),
				'preferred_group' => trim((string) ($item['preferred_group'] ?? '')),
				'preferred_goal' => trim((string) ($item['preferred_goal'] ?? '')),
				'picker_search' => $this->normalize_ai_visible_copy($item['picker_search'] ?? ''),
				'seed_settings' => $this->normalize_ai_missing_block_seed_settings($item['seed_settings'] ?? []),
			];
		}

		return array_slice($normalized, 0, 6);
	}

	private function normalize_ai_final_block_plan($value): array {
		$value = $this->normalize_json_to_array($value);
		$normalized = [];
		$allowed_actions = ['move_up', 'move_down', 'keep_top', 'keep_after_primary', 'consider_remove', 'hide_for_now', 'add_block', 'swap_order', 'keep', 'add'];

		foreach($value as $index => $item) {
			if(!is_array($item)) {
				continue;
			}

			$label = $this->normalize_ai_visible_copy($item['label'] ?? '');
			$block_type = trim((string) ($item['block_type'] ?? $item['type'] ?? ''));
			$reason = trim((string) ($item['reason'] ?? $item['why'] ?? ''));

			if($label === '' || ($block_type === '' && (int) ($item['block_id'] ?? 0) <= 0)) {
				continue;
			}

			$planned_action = trim((string) ($item['planned_action'] ?? $item['action'] ?? 'keep'));
			if(!in_array($planned_action, $allowed_actions, true)) {
				$planned_action = 'keep';
			}

			$normalized[] = [
				'display_order' => max(1, (int) ($item['display_order'] ?? ($index + 1))),
				'block_id' => max(0, (int) ($item['block_id'] ?? 0)),
				'block_type' => $block_type,
				'label' => $label,
				'source' => trim((string) ($item['source'] ?? 'existing')),
				'status' => trim((string) ($item['status'] ?? '')),
				'planned_action' => $planned_action,
				'reason' => $reason,
				'include_on_app' => array_key_exists('include_on_app', $item) ? !empty($item['include_on_app']) : !in_array($planned_action, ['hide_for_now', 'consider_remove'], true),
				'position' => max(0, (int) ($item['position'] ?? 0)),
				'insert_after_block_id' => max(0, (int) ($item['insert_after_block_id'] ?? 0)),
				'insert_after_type' => trim((string) ($item['insert_after_type'] ?? '')),
				'insert_after_label' => $this->normalize_ai_visible_copy($item['insert_after_label'] ?? ''),
			];

			if(count($normalized) >= 24) {
				break;
			}
		}

		usort($normalized, static function(array $a, array $b): int {
			return ((int) ($a['display_order'] ?? 0) <=> (int) ($b['display_order'] ?? 0))
				?: ((int) ($a['position'] ?? 0) <=> (int) ($b['position'] ?? 0))
				?: strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
		});

		return $normalized;
	}

	private function get_ai_final_block_plan(array $additional, int $link_id = 0, $preferences = null): array {
		$direct_plan = $this->normalize_ai_final_block_plan($additional['fcc_ai_final_block_plan'] ?? []);

		if(!empty($direct_plan)) {
			return $direct_plan;
		}

		$latest_review = $this->get_latest_saved_ai_review_for_link($link_id, $preferences);

		return $this->normalize_ai_final_block_plan($latest_review['final_block_plan'] ?? []);
	}

	private function get_ai_final_plan_scope(array $additional, int $link_id = 0, $preferences = null): array {
		$final_block_plan = $this->get_ai_final_block_plan($additional, $link_id, $preferences);
		$included_block_ids = [];
		$included_block_types = [];

		foreach($final_block_plan as $item) {
			if(empty($item['include_on_app'])) {
				continue;
			}

			$block_id = (int) ($item['block_id'] ?? 0);
			$block_type = trim((string) ($item['block_type'] ?? ''));

			if($block_id > 0) {
				$included_block_ids[] = $block_id;
			}

			if($block_type !== '') {
				$included_block_types[] = $block_type;
			}
		}

		return [
			'has_plan' => !empty($final_block_plan),
			'included_block_ids' => array_values(array_unique($included_block_ids)),
			'included_block_types' => array_values(array_unique($included_block_types)),
		];
	}

	private function get_ai_missing_block_recommendation_plan_key(array $recommendation): string {
		$recommendation_key = trim((string) ($recommendation['recommendation_key'] ?? ''));
		if($recommendation_key !== '') {
			return 'recommendation:' . $recommendation_key;
		}

		$role_key = trim((string) ($recommendation['role_key'] ?? ''));
		if($role_key !== '') {
			return 'role:' . $role_key;
		}

		$block_type = trim((string) ($recommendation['block_type'] ?? ''));
		$label_key = $this->normalize_ai_matching_key((string) ($recommendation['label'] ?? ''));

		return 'type:' . $block_type . '|label:' . $label_key;
	}

	private function get_ai_final_plan_missing_item_key(array $item): string {
		$block_type = trim((string) ($item['block_type'] ?? ''));
		$label_key = $this->normalize_ai_matching_key((string) ($item['label'] ?? ''));

		return 'type:' . $block_type . '|label:' . $label_key;
	}

	private function filter_ai_missing_block_recommendations_by_plan_scope(array $recommendations, array $additional, int $link_id = 0, $preferences = null): array {
		$final_block_plan = $this->get_ai_final_block_plan($additional, $link_id, $preferences);

		if(empty($final_block_plan)) {
			return $recommendations;
		}

		$allowed_missing_keys = [];

		foreach($final_block_plan as $item) {
			if(empty($item['include_on_app'])) {
				continue;
			}

			$planned_action = trim((string) ($item['planned_action'] ?? ''));
			$is_missing_row = (int) ($item['block_id'] ?? 0) <= 0 || (string) ($item['source'] ?? '') === 'missing';

			if(!$is_missing_row && !in_array($planned_action, ['add', 'add_block'], true)) {
				continue;
			}

			$allowed_missing_keys[] = $this->get_ai_final_plan_missing_item_key($item);
		}

		$allowed_missing_keys = array_values(array_unique(array_filter($allowed_missing_keys)));

		if(empty($allowed_missing_keys)) {
			return [];
		}

		return array_values(array_filter($recommendations, function($item) use ($allowed_missing_keys): bool {
			if(!is_array($item)) {
				return false;
			}

			$item_key = $this->get_ai_missing_block_recommendation_plan_key($item);

			return $item_key !== '' && in_array($item_key, $allowed_missing_keys, true);
		}));
	}

	private function filter_ai_copy_suggestions_by_plan_scope(array $copy_suggestions, array $plan_scope): array {
		if(empty($plan_scope['has_plan'])) {
			return $copy_suggestions;
		}

		$allowed_block_ids = array_map('intval', (array) ($plan_scope['included_block_ids'] ?? []));
		$allowed_block_types = array_map('strval', (array) ($plan_scope['included_block_types'] ?? []));

		return array_values(array_filter($copy_suggestions, static function($item) use ($allowed_block_ids, $allowed_block_types): bool {
			if(!is_array($item)) {
				return false;
			}

			$block_id = (int) ($item['block_id'] ?? 0);
			$block_type = trim((string) ($item['block_type'] ?? ''));

			if($block_id > 0) {
				return in_array($block_id, $allowed_block_ids, true);
			}

			return $block_type !== '' && in_array($block_type, $allowed_block_types, true);
		}));
	}

	private function filter_ai_block_patch_pack_by_plan_scope(array $block_patch_pack, array $plan_scope): array {
		if(empty($plan_scope['has_plan'])) {
			return $block_patch_pack;
		}

		$allowed_block_ids = array_map('intval', (array) ($plan_scope['included_block_ids'] ?? []));
		$allowed_block_types = array_map('strval', (array) ($plan_scope['included_block_types'] ?? []));

		return array_values(array_filter($block_patch_pack, static function($item) use ($allowed_block_ids, $allowed_block_types): bool {
			if(!is_array($item)) {
				return false;
			}

			$block_id = (int) ($item['block_id'] ?? 0);
			$block_type = trim((string) ($item['block_type'] ?? ''));

			if($block_id > 0) {
				return in_array($block_id, $allowed_block_ids, true);
			}

			return $block_type !== '' && in_array($block_type, $allowed_block_types, true);
		}));
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

	private function decode_ai_biolink_block_settings($value): \stdClass {
		if($value instanceof \stdClass) {
			return $value;
		}

		if(is_array($value)) {
			return json_decode(json_encode($value));
		}

		if(is_string($value)) {
			$decoded = json_decode($value ?? '{}');
			if($decoded instanceof \stdClass) {
				return $decoded;
			}
		}

		return new \stdClass();
	}

	private function get_ai_biolink_block_preview_label(string $type, \stdClass $settings): string {
		foreach(['name', 'text', 'popup_title', 'title', 'heading', 'label'] as $property) {
			if(!empty($settings->{$property})) {
				return mb_substr(trim(strip_tags((string) $settings->{$property})), 0, 90);
			}
		}

		$translation = l('link.biolink.blocks.' . $type);

		return $translation !== '' ? $translation : $type;
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
				$settings = $this->decode_ai_biolink_block_settings($row->settings ?? null);
				$catalog[] = [
					'block_id' => (int) ($row->biolink_block_id ?? 0),
					'type' => (string) ($row->type ?? ''),
					'label' => $this->get_ai_biolink_block_preview_label((string) ($row->type ?? ''), $settings),
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
					$matched_block = $find_by_types(['link_discount', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo']);
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

	private function build_ai_missing_block_recommendations(array $additional, array $block_catalog, array $primary_block_plan = []): array {
		$existing_types = [];
		foreach($block_catalog as $block) {
			$type = trim((string) ($block['type'] ?? ''));

			if($type === '') {
				continue;
			}

			$existing_types[$type] = ($existing_types[$type] ?? 0) + 1;
		}

		$copy_suggestions = $this->normalize_ai_copy_suggestions($additional['fcc_ai_copy_suggestions'] ?? []);
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

			$why = trim((string) ($item['why'] ?? ''));
			if($why === '') {
				return;
			}

			$label = trim((string) ($item['label'] ?? ''));
			if($label === '') {
				$label = l('link.biolink.blocks.' . $block_type);
			}

			$seed_settings = $this->normalize_ai_missing_block_seed_settings($item['seed_settings'] ?? []);
			$supported_fields = $this->get_ai_copy_supported_fields_by_block_type($block_type);

			foreach($copy_suggestions as $suggestion) {
				if(!is_array($suggestion) || (int) ($suggestion['block_id'] ?? 0) > 0) {
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

	private function normalize_ai_block_attribution_payload($value): array {
		$value = $this->normalize_json_to_array($value);

		$normalize_row = function($item): ?array {
			if(!is_array($item)) {
				return null;
			}

			return [
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
				'focus_risk_blocks' => max(0, (int) ($summary['focus_risk_blocks'] ?? count($focus_risk_blocks))),
				'zero_signal_blocks' => max(0, (int) ($summary['zero_signal_blocks'] ?? count(array_filter($all_blocks, static fn($row): bool => (int) ($row['signal_score'] ?? 0) === 0)))),
			],
			'top_signal_blocks' => $top_signal_blocks,
			'focus_risk_blocks' => $focus_risk_blocks,
			'all_blocks' => $all_blocks,
		];
	}

	private function normalize_ai_block_delta_summary($value): array {
		$value = $this->normalize_json_to_array($value);

		$normalize_row = function($item): ?array {
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

		$build_rows = function(array $items) use ($normalize_row): array {
			$rows = [];
			foreach($items as $item) {
				$row = $normalize_row($item);
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

	private function normalize_ai_evolution_measurement($value): array {
		$value = $this->normalize_json_to_array($value);

		return [
			'measured_at' => !empty($value['measured_at']) ? (string) $value['measured_at'] : null,
			'performance' => $this->normalize_ai_performance_snapshot($value['performance'] ?? []),
			'delta' => $this->normalize_ai_evolution_delta_items($value['delta'] ?? []),
			'block_summary' => $this->normalize_ai_block_delta_summary($value['block_summary'] ?? []),
		];
	}

	private function normalize_ai_evolution_memory($value): array {
		$value = $this->normalize_json_to_array($value);
		$normalized = [];

		foreach($value as $item) {
			if(!is_array($item)) {
				continue;
			}

			$recommended = $this->normalize_json_to_array($item['recommended'] ?? []);
			$applied = $this->normalize_json_to_array($item['applied'] ?? []);
			$layout_summary = $this->normalize_json_to_array($applied['layout_summary'] ?? []);

			$normalized[] = [
				'review_key' => trim((string) ($item['review_key'] ?? '')),
				'recommended_at' => !empty($item['recommended_at']) ? (string) $item['recommended_at'] : null,
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
						'restored_blocks' => max(0, (int) (($applied['layout_rollback_summary']['restored_blocks'] ?? 0))),
						're_enabled_blocks' => max(0, (int) (($applied['layout_rollback_summary']['re_enabled_blocks'] ?? 0))),
					],
				],
				'evaluation_7d' => $this->normalize_ai_evolution_measurement($item['evaluation_7d'] ?? []),
				'evaluation_30d' => $this->normalize_ai_evolution_measurement($item['evaluation_30d'] ?? []),
			];
		}

		usort($normalized, static function($a, $b) {
			return strcmp((string) ($b['recommended_at'] ?? ''), (string) ($a['recommended_at'] ?? ''));
		});

		return array_slice($normalized, 0, 12);
	}

	private function sync_ai_evolution_apply_state(array $additional, array $changes): array {
		$additional['fcc_ai_theme_apply_state'] = $this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? null);
		$memory = $this->normalize_ai_evolution_memory($additional['fcc_ai_evolution_memory'] ?? []);
		$target_review_key = trim((string) ($additional['fcc_ai_theme_apply_state']['active_review_key'] ?? ''));
		$target_index = null;

		foreach($memory as $index => $cycle) {
			if($target_review_key !== '' && (string) ($cycle['review_key'] ?? '') === $target_review_key) {
				$target_index = $index;
				break;
			}
		}

		if($target_index === null && !empty($memory)) {
			$target_index = 0;
		}

		if($target_index !== null && isset($memory[$target_index])) {
			$applied = $memory[$target_index]['applied'] ?? [];

			foreach(['theme_applied_at', 'primary_applied_at', 'layout_applied_at', 'layout_reverted_at', 'theme_key'] as $key) {
				if(array_key_exists($key, $changes)) {
					$applied[$key] = $changes[$key];
				}
			}

			if(array_key_exists('layout_summary', $changes) && is_array($changes['layout_summary'])) {
				$applied['layout_summary'] = array_merge($applied['layout_summary'] ?? [
					'reordered_blocks' => 0,
					'hidden_blocks' => 0,
					'updated_blocks' => 0,
				], $changes['layout_summary']);
			}

			if(array_key_exists('layout_rollback_summary', $changes) && is_array($changes['layout_rollback_summary'])) {
				$applied['layout_rollback_summary'] = array_merge($applied['layout_rollback_summary'] ?? [
					'restored_blocks' => 0,
					're_enabled_blocks' => 0,
				], $changes['layout_rollback_summary']);
			}

			$memory[$target_index]['applied'] = $applied;
			$additional['fcc_ai_evolution_memory'] = $memory;
		}

		return $additional;
	}

	private function get_ai_layout_payload($link): array {
		$link_additional = $this->normalize_json_to_array($link->additional ?? null);
		$block_catalog = $this->get_ai_editor_block_catalog((int) ($link->link_id ?? 0));
		$raw_primary_block_plan = $this->normalize_ai_primary_block_plan($link_additional['fcc_ai_primary_block_plan'] ?? []);
		$raw_missing_block_recommendations = $this->build_ai_missing_block_recommendations($link_additional, $block_catalog, $raw_primary_block_plan);
		$primary_block_plan = $this->get_effective_ai_primary_block_plan($link_additional, $block_catalog, $raw_missing_block_recommendations, (int) ($link->link_id ?? 0), $this->user->preferences ?? null);
		$missing_block_recommendations = $this->build_ai_missing_block_recommendations($link_additional, $block_catalog, $primary_block_plan);

		return [
			'primary_block_plan' => $primary_block_plan,
			'layout_actions' => $this->build_effective_ai_layout_actions($link_additional, $block_catalog, $primary_block_plan, $missing_block_recommendations, (int) ($link->link_id ?? 0), $this->user->preferences ?? null),
			'additional' => $link_additional,
		];
	}

	private function format_ai_html_copy_value(string $value): string {
		$value = trim($value);

		if($value === '') {
			return '';
		}

		return '<p>' . nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) . '</p>';
	}

	private function apply_ai_copy_value_to_block_settings(array $settings, string $block_type, string $field, string $value): array {
		$value = trim($value);

		if($value === '') {
			return $settings;
		}

		$short_copy = mb_substr(input_clean($value, 256), 0, 256);
		$medium_copy = mb_substr(input_clean($value, 512), 0, 512);
		$long_copy = mb_substr(trim($value), 0, 5000);

		switch($block_type) {
			case 'youtube':
			case 'vimeo':
				if(in_array($field, ['title', 'name'], true)) {
					$settings['title'] = $short_copy;
				}
				break;

			case 'heading':
				if(in_array($field, ['text', 'name', 'title'], true)) {
					$settings['text'] = $short_copy;
				}
				break;

			case 'paragraph':
			case 'markdown':
				if(in_array($field, ['text', 'description'], true)) {
					$settings['text'] = $this->format_ai_html_copy_value($long_copy);
				}
				break;

			case 'modal_text':
				if(in_array($field, ['name', 'title'], true)) {
					$settings['name'] = $short_copy;
				}
				if(in_array($field, ['text', 'description'], true)) {
					$settings['text'] = $this->format_ai_html_copy_value($long_copy);
				}
				if($field === 'button_text') {
					$settings['button_text'] = $short_copy;
				}
				break;

			case 'custom_html_whatsapp':
				if(in_array($field, ['name', 'title', 'button_text'], true)) {
					$settings['title'] = $short_copy;
					$settings['button'] = $short_copy;
				}
				if(in_array($field, ['message', 'description', 'text'], true)) {
					$settings['message'] = $long_copy;
				}
				break;

			case 'lead_funnel':
				if(in_array($field, ['name', 'button_text', 'popup_title', 'thank_you_title', 'thank_you_button_text'], true)) {
					$settings[$field] = $short_copy;
				}
				if(in_array($field, ['description', 'popup_subtitle', 'thank_you_text'], true)) {
					$settings[$field] = $field === 'popup_subtitle' ? $medium_copy : $long_copy;
				}
				break;

			default:
				if(in_array($field, ['name', 'title', 'button_text', 'thank_you_button_text', 'popup_title', 'thank_you_title'], true)) {
					$settings[$field] = $short_copy;
				}
				if(in_array($field, ['description', 'message', 'popup_subtitle', 'thank_you_text'], true)) {
					$settings[$field] = $field === 'popup_subtitle' ? $medium_copy : $long_copy;
				}
				if($field === 'text') {
					$settings[$field] = $long_copy;
				}
				break;
		}

		return $settings;
	}

	private function apply_ai_copy_suggestions_to_blocks(int $link_id, array $copy_suggestions, array $block_catalog): array {
		$updated_blocks = 0;
		$applied_suggestions = 0;

		foreach($copy_suggestions as $suggestion) {
			if(!is_array($suggestion)) {
				continue;
			}

			$suggestion = $this->refine_ai_copy_suggestion_for_catalog($suggestion, $block_catalog);
			$block_id = (int) ($suggestion['block_id'] ?? 0);
			$block_type = trim((string) ($suggestion['block_type'] ?? ''));
			$field = trim((string) ($suggestion['field'] ?? 'name'));
			$value = trim((string) ($suggestion['value'] ?? ''));

			if($block_id <= 0 || $block_type === '' || $value === '') {
				continue;
			}

			$allowed_fields = $this->get_ai_copy_supported_fields_by_block_type($block_type);
			if(empty($allowed_fields) || !in_array($field, $allowed_fields, true)) {
				continue;
			}

			$block = db()->where('biolink_block_id', $block_id)->where('link_id', $link_id)->where('user_id', $this->user->user_id)->getOne('biolinks_blocks', ['biolink_block_id', 'type', 'settings']);

			if(!$block) {
				continue;
			}

			$settings = $this->normalize_json_to_array($block->settings ?? null);
			$updated_settings = $this->apply_ai_copy_value_to_block_settings($settings, (string) ($block->type ?? $block_type), $field, $value);

			if(json_encode($updated_settings) === json_encode($settings)) {
				continue;
			}

			db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
				'settings' => json_encode($updated_settings),
			]);
			$updated_blocks++;
			$applied_suggestions++;
		}

		return [
			'updated_blocks' => $updated_blocks,
			'applied_suggestions' => $applied_suggestions,
		];
	}

	private function get_ai_plan_sequence_blocks(array $additional, array $block_catalog, int $link_id = 0, $preferences = null): array {
		$ideal_block_order = $this->get_ai_ideal_block_order($additional, $link_id, $preferences);
		$hero_visual_block = $this->get_first_ai_catalog_block_by_types($block_catalog, ['avatar', 'image', 'header']);
		$owner_identity_block = $this->get_ai_owner_identity_catalog_block($block_catalog);
		$ordered_blocks = [];
		$used_ids = [];
		$append_unique = static function(array $block) use (&$ordered_blocks, &$used_ids): void {
			$block_id = (int) ($block['block_id'] ?? 0);

			if($block_id <= 0 || in_array($block_id, $used_ids, true) || (int) ($block['is_enabled'] ?? 0) !== 1) {
				return;
			}

			$ordered_blocks[] = $block;
			$used_ids[] = $block_id;
		};

		$append_unique($hero_visual_block);
		$append_unique($owner_identity_block);

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
				$matched_block = $find_by_types(['youtube', 'vimeo', 'video']);
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

			$append_unique($matched_block);
		}

		return $ordered_blocks;
	}

	private function get_ai_final_plan_sequence_blocks(array $additional, array $block_catalog, int $link_id = 0, $preferences = null): array {
		$final_block_plan = $this->get_ai_final_block_plan($additional, $link_id, $preferences);

		if(empty($final_block_plan) || empty($block_catalog)) {
			return [];
		}

		$ordered_blocks = [];
		$used_ids = [];
		$append_unique = static function(array $block) use (&$ordered_blocks, &$used_ids): void {
			$block_id = (int) ($block['block_id'] ?? 0);

			if($block_id <= 0 || in_array($block_id, $used_ids, true)) {
				return;
			}

			$ordered_blocks[] = $block;
			$used_ids[] = $block_id;
		};

		foreach($final_block_plan as $plan_item) {
			$available_blocks = array_values(array_filter($block_catalog, static function(array $block) use ($used_ids): bool {
				return !in_array((int) ($block['block_id'] ?? 0), $used_ids, true);
			}));

			if(empty($available_blocks)) {
				break;
			}

			$matched_block = [];
			$requested_block_id = (int) ($plan_item['block_id'] ?? 0);
			$requested_block_type = trim((string) ($plan_item['block_type'] ?? ''));
			$requested_label = $this->normalize_ai_visible_copy((string) ($plan_item['label'] ?? ''));
			$requested_label_key = $this->normalize_ai_matching_key($requested_label);

			if($requested_block_id > 0) {
				foreach($available_blocks as $block) {
					if((int) ($block['block_id'] ?? 0) === $requested_block_id) {
						$matched_block = $block;
						break;
					}
				}
			}

			if(empty($matched_block) && $requested_block_type !== '' && $requested_label !== '') {
				foreach($available_blocks as $block) {
					$label_key = $this->normalize_ai_matching_key((string) ($block['label'] ?? ''));

					if(
						(string) ($block['type'] ?? '') === $requested_block_type
						&& $label_key !== ''
						&& ($label_key === $requested_label_key || str_contains($label_key, $requested_label_key) || str_contains($requested_label_key, $label_key))
					) {
						$matched_block = $block;
						break;
					}
				}
			}

			if(empty($matched_block) && $requested_block_type !== '') {
				$type_matches = array_values(array_filter($available_blocks, static function(array $block) use ($requested_block_type): bool {
					return (string) ($block['type'] ?? '') === $requested_block_type;
				}));

				if(count($type_matches) === 1) {
					$matched_block = $type_matches[0];
				}
			}

			if(empty($matched_block) && $requested_label !== '') {
				foreach($available_blocks as $block) {
					$label_key = $this->normalize_ai_matching_key((string) ($block['label'] ?? ''));

					if($requested_label_key !== '' && $label_key !== '' && ($label_key === $requested_label_key || str_contains($label_key, $requested_label_key) || str_contains($requested_label_key, $label_key))) {
						$matched_block = $block;
						break;
					}
				}
			}

			if(empty($matched_block)) {
				continue;
			}

			$matched_block['ai_plan_include_on_app'] = !empty($plan_item['include_on_app']);
			$matched_block['ai_plan_action'] = (string) ($plan_item['planned_action'] ?? 'keep');
			$matched_block['ai_plan_reason'] = (string) ($plan_item['reason'] ?? '');
			$matched_block['ai_plan_source'] = (string) ($plan_item['source'] ?? 'existing');

			$append_unique($matched_block);
		}

		return $ordered_blocks;
	}

	private function apply_ai_plan_sequence_to_blocks(int $link_id, array $blocks, array $plan_sequence_blocks, int $primary_block_id = 0, array $additional = []): array {
		$current_map = [];
		$protected_core_block_ids = $this->get_ai_protected_core_block_ids($blocks);
		$protected_signal_block_ids = $this->get_ai_protected_signal_block_ids($additional, $link_id, $blocks);
		foreach($blocks as $block) {
			$block_id = (int) ($block['biolink_block_id'] ?? 0);
			$current_map[$block_id] = $block;
		}

		$visible_ids = [];
		$explicit_hidden_ids = [];
		foreach($plan_sequence_blocks as $block) {
			$block_id = (int) ($block['block_id'] ?? 0);
			$include_on_app = array_key_exists('ai_plan_include_on_app', $block) ? !empty($block['ai_plan_include_on_app']) : true;
			$planned_action = trim((string) ($block['ai_plan_action'] ?? ''));

			if($block_id <= 0 || !isset($current_map[$block_id])) {
				continue;
			}

			if($include_on_app && !in_array($block_id, $visible_ids, true)) {
				$visible_ids[] = $block_id;
			}

			if((!$include_on_app || in_array($planned_action, ['hide_for_now', 'consider_remove'], true)) && !in_array($block_id, $explicit_hidden_ids, true)) {
				$explicit_hidden_ids[] = $block_id;
			}
		}

		if($primary_block_id > 0 && isset($current_map[$primary_block_id]) && !in_array($primary_block_id, $visible_ids, true)) {
			$visible_ids[] = $primary_block_id;
		}

		$protected_block_ids = $this->collapse_ai_protected_block_ids($blocks, $protected_core_block_ids, $protected_signal_block_ids, $visible_ids);

		if(empty($visible_ids) && empty($protected_block_ids)) {
			return [
				'reordered_blocks' => 0,
				'hidden_blocks' => 0,
				're_enabled_blocks' => 0,
				'updated_blocks' => 0,
			];
		}

		if(empty($visible_ids)) {
			$re_enabled_blocks = 0;
			$updated_blocks = 0;

			foreach($protected_block_ids as $block_id) {
				if(!isset($current_map[$block_id]) || (int) ($current_map[$block_id]['is_enabled'] ?? 0) === 1) {
					continue;
				}

				db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
					'is_enabled' => 1,
				]);
				$re_enabled_blocks++;
				$updated_blocks++;
			}

			return [
				'reordered_blocks' => 0,
				'hidden_blocks' => 0,
				're_enabled_blocks' => $re_enabled_blocks,
				'updated_blocks' => $updated_blocks,
			];
		}

		$remaining_blocks = $blocks;
		usort($remaining_blocks, static function($a, $b) {
			$order_compare = ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));

			if($order_compare !== 0) {
				return $order_compare;
			}

			return ((int) ($a['biolink_block_id'] ?? 0)) <=> ((int) ($b['biolink_block_id'] ?? 0));
		});

		$ordered_all_ids = $visible_ids;
		foreach($explicit_hidden_ids as $block_id) {
			if($block_id > 0 && !in_array($block_id, $ordered_all_ids, true)) {
				$ordered_all_ids[] = $block_id;
			}
		}

		foreach($remaining_blocks as $block) {
			$block_id = (int) ($block['biolink_block_id'] ?? 0);

			if($block_id > 0 && !in_array($block_id, $ordered_all_ids, true)) {
				$ordered_all_ids[] = $block_id;
			}
		}

		$reordered_blocks = 0;
		$hidden_blocks = 0;
		$re_enabled_blocks = 0;
		$updated_blocks = 0;

		foreach($ordered_all_ids as $index => $block_id) {
			if(!isset($current_map[$block_id])) {
				continue;
			}

			$current_block = $current_map[$block_id];
			$updates = [];
			$new_order = $index + 1;
			$current_enabled = (int) ($current_block['is_enabled'] ?? 0);
			$should_enable = $current_enabled;

			if(in_array($block_id, $visible_ids, true) || in_array($block_id, $protected_block_ids, true)) {
				$should_enable = 1;
			} elseif(in_array($block_id, $explicit_hidden_ids, true)) {
				$should_enable = 0;
			}

			if((int) ($current_block['order'] ?? 0) !== $new_order) {
				$updates['order'] = $new_order;
				$reordered_blocks++;
			}

			if((int) ($current_block['is_enabled'] ?? 0) !== $should_enable) {
				$updates['is_enabled'] = $should_enable;

				if($should_enable === 1) {
					$re_enabled_blocks++;
				} else {
					$hidden_blocks++;
				}
			}

			if(!empty($updates)) {
				db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', $updates);
				$updated_blocks++;
			}
		}

		return [
			'reordered_blocks' => $reordered_blocks,
			'hidden_blocks' => $hidden_blocks,
			're_enabled_blocks' => $re_enabled_blocks,
			'updated_blocks' => $updated_blocks,
		];
	}

	private function process_biolink_theme_id_settings($link, $settings, string $type): string {
		$themable_blocks = [];

		foreach(require APP_PATH . 'includes/biolink_blocks.php' as $key => $value) {
			if(!empty($value['themable'])) {
				$themable_blocks[] = $key;
			}
		}

		if(!in_array($type, $themable_blocks, true) || empty($link->biolink_theme_id)) {
			return is_string($settings) ? $settings : json_encode($settings);
		}

		$biolinks_themes = (new BiolinksThemes())->get_biolinks_themes();
		$biolink_theme = $biolinks_themes[$link->biolink_theme_id] ?? null;

		if(!$biolink_theme) {
			return is_string($settings) ? $settings : json_encode($settings);
		}

		if(is_string($settings)) {
			$settings = json_decode($settings);
		}

		switch($type) {
			case 'socials':
				$new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block_socials ?? []));
				break;

			case 'heading':
				$new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block_heading ?? []));
				break;

			case 'paragraph':
				$new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block_paragraph ?? []));
				break;

			case 'counter':
			case 'loading':
				$biolink_theme->settings->biolink_block->number_color = $biolink_theme->settings->biolink_block->text_color;
				$new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block ?? []));
				break;

			case 'external_item':
				$biolink_theme->settings->biolink_block->price_color = $biolink_theme->settings->biolink_block->text_color;
				$biolink_theme->settings->biolink_block->name_color = $biolink_theme->settings->biolink_block->text_color;
				$new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block ?? []));
				break;

			case 'business_hours':
				$biolink_theme->settings->biolink_block->icon_color = $biolink_theme->settings->biolink_block->text_color;
				$new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block ?? []));
				break;

			default:
				$new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block ?? []));
				break;
		}

		return $new_settings;
	}

	private function apply_ai_theme_pack_to_seeded_block_settings(array $settings, array $theme_pack, string $block_type): array {
		if(empty($theme_pack)) {
			return $settings;
		}

		switch($block_type) {
			case 'lead_funnel':
				if(!empty($theme_pack['primary_block_background'])) {
					$settings['background_color'] = $theme_pack['primary_block_background'];
					$settings['popup_button_background_color'] = $theme_pack['primary_block_background'];
					$settings['page_button_background_color'] = $theme_pack['primary_block_background'];
				}
				if(!empty($theme_pack['primary_block_text'])) {
					$settings['text_color'] = $theme_pack['primary_block_text'];
					$settings['popup_button_text_color'] = $theme_pack['primary_block_text'];
					$settings['page_button_text_color'] = $theme_pack['primary_block_text'];
				}
				if(!empty($theme_pack['primary_block_border'])) {
					$settings['border_color'] = $theme_pack['primary_block_border'];
				}
				if(!empty($theme_pack['primary_block_shadow'])) {
					$settings['border_shadow_color'] = $theme_pack['primary_block_shadow'];
				}
				if(!empty($theme_pack['secondary_blocks_background'])) {
					$settings['popup_background_color'] = $theme_pack['secondary_blocks_background'];
					$settings['page_background_color'] = $theme_pack['secondary_blocks_background'];
				}
				if(!empty($theme_pack['text_color'])) {
					$settings['popup_text_color'] = $theme_pack['text_color'];
					$settings['page_text_color'] = $theme_pack['text_color'];
				}
				break;

			case 'heading':
				if(!empty($theme_pack['heading_color'])) {
					$settings['text_color'] = $theme_pack['heading_color'];
				}
				break;

			case 'paragraph':
			case 'modal_text':
			case 'custom_html_whatsapp':
				if(!empty($theme_pack['secondary_blocks_background'])) {
					$settings['background_color'] = $theme_pack['secondary_blocks_background'];
				}
				if(!empty($theme_pack['secondary_blocks_text'])) {
					$settings['text_color'] = $theme_pack['secondary_blocks_text'];
				}
				if(!empty($theme_pack['secondary_blocks_border'])) {
					$settings['border_color'] = $theme_pack['secondary_blocks_border'];
				}
				if(!empty($theme_pack['secondary_blocks_shadow'])) {
					$settings['border_shadow_color'] = $theme_pack['secondary_blocks_shadow'];
				}
				break;
		}

		return $settings;
	}

	private function normalize_ai_seed_location_url(string $block_type, $value): string {
		if(!is_scalar($value)) {
			return '';
		}

		$location_url = get_url((string) $value);

		if($location_url === '') {
			return '';
		}

		$host = mb_strtolower((string) parse_url($location_url, PHP_URL_HOST));
		$host = preg_replace('/^(www\.|m\.)/', '', $host) ?? $host;

		return match($block_type) {
			'youtube' => in_array($host, ['youtube.com', 'youtu.be'], true) ? $location_url : '',
			'vimeo' => in_array($host, ['vimeo.com', 'player.vimeo.com'], true) ? $location_url : '',
			default => $location_url,
		};
	}

	private function apply_ai_seed_settings_to_existing_block(int $block_id, string $block_type, array $seed_settings): bool {
		if($block_id <= 0 || $block_type === '') {
			return false;
		}

		$seed_settings = $this->normalize_ai_missing_block_seed_settings($seed_settings);
		$block = db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->getOne('biolinks_blocks', ['biolink_block_id', 'link_id', 'type', 'location_url', 'settings']);

		if(!$block) {
			return false;
		}

		$current_settings = $this->normalize_json_to_array($block->settings ?? null);
		$updated_settings = $current_settings;
		$updated_location_url = trim((string) ($block->location_url ?? ''));
		$allowed_fields = $this->get_ai_copy_supported_fields_by_block_type($block_type);

		foreach($seed_settings as $field => $value) {
			if($value === '' || !in_array($field, $allowed_fields, true)) {
				continue;
			}

			$updated_settings = $this->apply_ai_copy_value_to_block_settings($updated_settings, $block_type, (string) $field, (string) $value);
		}

		if($block_type === 'link_forever_product') {
			$link_settings_row = db()->where('link_id', (int) ($block->link_id ?? 0))->where('user_id', $this->user->user_id)->getOne('links', ['settings']);
			$link_settings = json_decode($link_settings_row->settings ?? '{}');
			$default_language_code = $link_settings->language_code ?? \Altum\Language::$default_code;
			$product_language_mode = in_array($seed_settings['product_language_mode'] ?? '', ['app', 'manual'], true) ? (string) $seed_settings['product_language_mode'] : ($updated_settings['product_language_mode'] ?? 'app');
			$product_language_code = trim((string) ($seed_settings['product_language_code'] ?? ($updated_settings['product_language_code'] ?? $default_language_code)));
			$product_fallback_language_code = trim((string) ($seed_settings['product_fallback_language_code'] ?? ($updated_settings['product_fallback_language_code'] ?? 'hr')));
			$product_translation_key = trim((string) ($seed_settings['product_translation_key'] ?? ($updated_settings['product_translation_key'] ?? '')));
			$product_blog_post_id = max(0, (int) ($seed_settings['product_blog_post_id'] ?? ($updated_settings['product_blog_post_id'] ?? 0)));

			if($product_blog_post_id <= 0 && $product_translation_key !== '') {
				$blog_post = db()->where('url', $product_translation_key)->where('language', $product_language_code)->getOne('blog_posts', ['blog_post_id', 'image']);

				if(!$blog_post && $product_fallback_language_code !== '') {
					$blog_post = db()->where('url', $product_translation_key)->where('language', $product_fallback_language_code)->getOne('blog_posts', ['blog_post_id', 'image']);
				}

				if(!$blog_post) {
					$blog_post = db()->where('url', $product_translation_key)->getOne('blog_posts', ['blog_post_id', 'image']);
				}

				if($blog_post) {
					$product_blog_post_id = (int) ($blog_post->blog_post_id ?? 0);

					if(empty($seed_settings['product_image_url']) && !empty($blog_post->image)) {
						$seed_settings['product_image_url'] = UPLOADS_FULL_URL . 'blog/' . $blog_post->image;
					}
				}
			}

			if($product_translation_key !== '') {
				$updated_settings['product_translation_key'] = mb_substr(query_clean($product_translation_key), 0, 128);
			}

			if($product_blog_post_id > 0) {
				$updated_settings['product_blog_post_id'] = $product_blog_post_id;
			}

			$updated_settings['product_language_mode'] = $product_language_mode;
			$updated_settings['product_language_code'] = $product_language_code;
			$updated_settings['product_fallback_language_code'] = $product_fallback_language_code;

			if(!empty($seed_settings['product_image_url'])) {
				$updated_settings['product_image_url'] = mb_substr(query_clean((string) $seed_settings['product_image_url']), 0, 2048);
			}

			if(!empty($seed_settings['description'])) {
				$updated_settings['description'] = mb_substr(query_clean((string) $seed_settings['description']), 0, 220);
			}

			$normalized_location_url = $this->normalize_ai_seed_location_url($block_type, $seed_settings['location_url'] ?? '');
			if($normalized_location_url !== '') {
				$updated_location_url = $normalized_location_url;
			}
		}

		if(in_array($block_type, ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)) {
			if(array_key_exists('apply_to_all_products', $seed_settings)) {
				$updated_settings['apply_to_all_products'] = (int) $seed_settings['apply_to_all_products'];
			}

			$normalized_location_url = $this->normalize_ai_seed_location_url($block_type, $seed_settings['location_url'] ?? '');
			if($normalized_location_url !== '') {
				$updated_location_url = $normalized_location_url;
				$updated_settings['decoded_url'] = \Altum\Link::decode_discount_link($updated_location_url);
			}
		}

		$updates = [];

		if(json_encode($updated_settings) !== json_encode($current_settings)) {
			$updates['settings'] = json_encode($updated_settings);
		}

		if($updated_location_url !== trim((string) ($block->location_url ?? ''))) {
			$updates['location_url'] = $updated_location_url;
		}

		if(empty($updates)) {
			return false;
		}

		db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', $updates);

		return true;
	}

	private function create_ai_seeded_biolink_block(\stdClass $link, string $block_type, array $seed_settings = [], bool $apply_ai_theme_pack = true): int {
		$block_type = trim($block_type);
		$seed_settings = $this->normalize_ai_missing_block_seed_settings($seed_settings);
		$theme_pack = $this->normalize_ai_theme_pack($this->normalize_json_to_array($link->additional ?? null)['fcc_ai_theme_pack'] ?? []);
		$total_blocks = (int) database()->query("SELECT COUNT(*) AS `total` FROM `biolinks_blocks` WHERE `user_id` = {$this->user->user_id} AND `link_id` = {$link->link_id}")->fetch_object()->total;

		$settings = null;
		$location_url = null;

		switch($block_type) {
			case 'lead_funnel':
				$settings = [
					'name' => mb_substr(query_clean($seed_settings['name'] ?? l('link.biolink.blocks.lead_funnel')), 0, 128),
					'image' => '',
					'text_color' => '#000000',
					'text_alignment' => 'center',
					'background_color' => '#ffffff',
					'border_shadow_style' => 'subtle',
					'border_shadow_color' => '#00000010',
					'border_width' => 0,
					'border_style' => 'solid',
					'border_color' => '#ffffff',
					'border_radius' => 'rounded',
					'animation' => false,
					'animation_runs' => 'repeat-1',
					'icon' => '',
					'open_mode' => in_array(($seed_settings['open_mode'] ?? 'popup'), ['popup', 'page'], true) ? ($seed_settings['open_mode'] ?? 'popup') : 'popup',
					'popup_background_color' => '#ffffff',
					'popup_text_color' => '#212529',
					'popup_button_background_color' => '#007bff',
					'popup_button_text_color' => '#ffffff',
					'page_background_color' => '#ffffff',
					'page_text_color' => '#212529',
					'page_button_background_color' => '#007bff',
					'page_button_text_color' => '#ffffff',
					'popup_title' => mb_substr(input_clean($seed_settings['popup_title'] ?? ($seed_settings['name'] ?? l('link.biolink.blocks.lead_funnel')), 128), 0, 128),
					'popup_subtitle' => mb_substr(input_clean($seed_settings['popup_subtitle'] ?? '', 512), 0, 512),
					'description' => mb_substr(input_clean($seed_settings['description'] ?? '', 5000), 0, 5000),
					'video_provider' => 'youtube',
					'video_url' => '',
					'show_name' => true,
					'show_email' => true,
					'show_phone' => true,
					'show_message' => true,
					'require_name' => true,
					'require_email' => true,
					'require_phone' => false,
					'require_message' => false,
					'email_placeholder' => l('biolink_lead_funnel.email_placeholder_default'),
					'phone_placeholder' => l('biolink_lead_funnel.phone_placeholder_default'),
					'name_placeholder' => l('biolink_lead_funnel.name_placeholder_default'),
					'message_placeholder' => l('biolink_lead_funnel.message_placeholder_default'),
					'button_text' => mb_substr(input_clean($seed_settings['button_text'] ?? l('biolink_lead_funnel.button_text_default'), 128), 0, 128),
					'success_text' => l('biolink_lead_funnel.success_text_default'),
					'show_agreement' => false,
					'agreement_url' => '',
					'agreement_text' => '',
					'thank_you_type' => 'message',
					'thank_you_title' => mb_substr(input_clean($seed_settings['thank_you_title'] ?? l('biolink_lead_funnel.thank_you_title_default'), 128), 0, 128),
					'thank_you_text' => mb_substr(input_clean($seed_settings['thank_you_text'] ?? l('biolink_lead_funnel.thank_you_text_default'), 5000), 0, 5000),
					'thank_you_url' => '',
					'thank_you_biolink_id' => null,
					'thank_you_file_source' => 'local_upload',
					'thank_you_file' => '',
					'thank_you_file_url' => '',
					'thank_you_button_text' => mb_substr(input_clean($seed_settings['thank_you_button_text'] ?? l('biolink_lead_funnel.thank_you_button_text_default'), 128), 0, 128),
					'notifications' => [],
					'columns' => 1,
					'display_continents' => [],
					'display_countries' => [],
					'display_cities' => [],
					'display_devices' => [],
					'display_languages' => [],
					'display_operating_systems' => [],
					'display_browsers' => [],
				];
				break;

			case 'heading':
				$settings = [
					'heading_type' => 'h2',
					'text' => mb_substr(query_clean($seed_settings['text'] ?? ($seed_settings['name'] ?? l('link.biolink.blocks.heading'))), 0, 256),
					'text_color' => '#ffffff',
					'text_alignment' => 'center',
					'verified_location' => '',
					'display_continents' => [],
					'display_countries' => [],
					'display_cities' => [],
					'display_devices' => [],
					'display_languages' => [],
					'display_operating_systems' => [],
					'display_browsers' => [],
				];
				break;

			case 'paragraph':
				$paragraph_text = quilljs_to_bootstrap($seed_settings['text'] ?? ($seed_settings['description'] ?? ''));
				$settings = [
					'text' => mb_substr((string) $paragraph_text, 0, 10000),
					'text_color' => '#ffffff',
					'background_color' => '#00000000',
					'border_radius' => 'rounded',
					'border_shadow_style' => 'none',
					'border_shadow_color' => '#00000000',
					'border_width' => 0,
					'border_style' => 'solid',
					'border_color' => '#ffffff',
					'text_alignment' => 'center',
					'display_continents' => [],
					'display_countries' => [],
					'display_cities' => [],
					'display_devices' => [],
					'display_languages' => [],
					'display_operating_systems' => [],
					'display_browsers' => [],
				];
				break;

			case 'modal_text':
				$modal_text = quilljs_to_bootstrap($seed_settings['text'] ?? '');
				$settings = [
					'name' => mb_substr(input_clean($seed_settings['name'] ?? l('link.biolink.blocks.modal_text'), 128), 0, 128),
					'text' => mb_substr((string) $modal_text, 0, 10000),
					'button_text' => mb_substr(input_clean($seed_settings['button_text'] ?? '', 128), 0, 128),
					'text_color' => '#000000',
					'text_alignment' => 'center',
					'background_color' => '#ffffff',
					'border_shadow_style' => 'subtle',
					'border_shadow_color' => '#00000010',
					'border_width' => 0,
					'border_style' => 'solid',
					'border_color' => '#ffffff',
					'border_radius' => 'rounded',
					'animation' => false,
					'animation_runs' => 'repeat-1',
					'icon' => '',
					'image' => '',
					'display_continents' => [],
					'display_countries' => [],
					'display_cities' => [],
					'display_devices' => [],
					'display_languages' => [],
					'display_operating_systems' => [],
					'display_browsers' => [],
				];
				break;

			case 'custom_html_whatsapp':
				$default_whatsapp_phone = mb_substr($this->get_default_whatsapp_phone_for_current_user(), 0, 256);
				$whatsapp_title = $seed_settings['title'] ?? ($seed_settings['button_text'] ?? ($seed_settings['name'] ?? l('create_biolink_custom_html_whatsapp_modal.button')));
				$whatsapp_message = $seed_settings['message'] ?? ($seed_settings['description'] ?? ($seed_settings['text'] ?? l('create_biolink_custom_html_whatsapp_modal.message')));
				$settings = [
					'message' => mb_substr(trim((string) $whatsapp_message), 0, 5000),
					'phone' => $default_whatsapp_phone,
					'title' => mb_substr(trim((string) $whatsapp_title), 0, 256),
					'button' => mb_substr(trim((string) $whatsapp_title), 0, 256),
					'text_color' => '#000000',
					'text_alignment' => 'center',
					'background_color' => '#ffffff',
					'border_shadow_style' => 'subtle',
					'border_shadow_color' => '#00000010',
					'border_width' => 0,
					'border_style' => 'solid',
					'border_color' => '#ffffff',
					'border_radius' => 'rounded',
					'icon' => 'fab fa-whatsapp',
					'display_continents' => [],
					'display_countries' => [],
					'display_cities' => [],
					'display_devices' => [],
					'display_languages' => [],
					'display_operating_systems' => [],
					'display_browsers' => [],
				];
				break;

			case 'custom_html_chatbot':
			case 'custom_html_chatbot_pets':
				$settings = [
					'html' => $this->get_default_ai_chatbot_embed_html($block_type),
					'display_continents' => [],
					'display_countries' => [],
					'display_cities' => [],
					'display_devices' => [],
					'display_languages' => [],
					'display_operating_systems' => [],
					'display_browsers' => [],
				];
				break;

			case 'youtube':
				$location_url = $this->normalize_ai_seed_location_url('youtube', $seed_settings['location_url'] ?? '');
				$settings = [
					'title' => mb_substr(input_clean($seed_settings['title'] ?? ($seed_settings['name'] ?? 'Kratki video'), 256), 0, 256),
					'video_autoplay' => false,
					'video_controls' => true,
					'video_loop' => false,
					'video_muted' => false,
					'border_shadow_style' => 'subtle',
					'border_shadow_color' => '#00000010',
					'border_width' => 0,
					'border_style' => 'solid',
					'border_color' => '#ffffff',
					'border_radius' => 'rounded',
					'display_continents' => [],
					'display_countries' => [],
					'display_cities' => [],
					'display_devices' => [],
					'display_languages' => [],
					'display_operating_systems' => [],
					'display_browsers' => [],
				];
				break;

			case 'vimeo':
				$location_url = $this->normalize_ai_seed_location_url('vimeo', $seed_settings['location_url'] ?? '');
				$settings = [
					'title' => mb_substr(input_clean($seed_settings['title'] ?? ($seed_settings['name'] ?? 'Kratki video'), 256), 0, 256),
					'border_shadow_style' => 'subtle',
					'border_shadow_color' => '#00000010',
					'border_width' => 0,
					'border_style' => 'solid',
					'border_color' => '#ffffff',
					'border_radius' => 'rounded',
					'display_continents' => [],
					'display_countries' => [],
					'display_cities' => [],
					'display_devices' => [],
					'display_languages' => [],
					'display_operating_systems' => [],
					'display_browsers' => [],
				];
				break;

			case 'link_forever_product':
				$link_settings_row = db()->where('link_id', (int) $link->link_id)->where('user_id', $this->user->user_id)->getOne('links', ['settings']);
				$link_settings = json_decode($link_settings_row->settings ?? '{}');
				$default_language_code = $link_settings->language_code ?? \Altum\Language::$default_code;
				$product_language_mode = in_array($seed_settings['product_language_mode'] ?? '', ['app', 'manual'], true) ? (string) $seed_settings['product_language_mode'] : 'app';
				$product_language_code = trim((string) ($seed_settings['product_language_code'] ?? $default_language_code));
				$product_fallback_language_code = trim((string) ($seed_settings['product_fallback_language_code'] ?? 'hr'));
				$product_translation_key = trim((string) ($seed_settings['product_translation_key'] ?? ''));
				$product_blog_post_id = max(0, (int) ($seed_settings['product_blog_post_id'] ?? 0));

				if($product_blog_post_id <= 0 && $product_translation_key !== '') {
					$blog_post = db()->where('url', $product_translation_key)->where('language', $product_language_code)->getOne('blog_posts', ['blog_post_id', 'image']);

					if(!$blog_post && $product_fallback_language_code !== '') {
						$blog_post = db()->where('url', $product_translation_key)->where('language', $product_fallback_language_code)->getOne('blog_posts', ['blog_post_id', 'image']);
					}

					if(!$blog_post) {
						$blog_post = db()->where('url', $product_translation_key)->getOne('blog_posts', ['blog_post_id', 'image']);
					}

					if($blog_post) {
						$product_blog_post_id = (int) ($blog_post->blog_post_id ?? 0);

						if(empty($seed_settings['product_image_url']) && !empty($blog_post->image)) {
							$seed_settings['product_image_url'] = UPLOADS_FULL_URL . 'blog/' . $blog_post->image;
						}
					}
				}

				$location_url = $this->normalize_ai_seed_location_url('link_forever_product', $seed_settings['location_url'] ?? '');
				if($location_url === '') {
					$location_url = $this->get_ai_fcc_start_paket_public_url();
				}
				$settings = [
					'name' => mb_substr(query_clean($seed_settings['name'] ?? 'Postani Forever suradnik'), 0, 128),
					'open_in_new_tab' => false,
					'text_color' => '#000000',
					'text_alignment' => 'center',
					'background_color' => '#ffffff',
					'border_shadow_style' => 'subtle',
					'border_shadow_color' => '#00000010',
					'border_width' => 0,
					'border_style' => 'solid',
					'border_color' => '#ffffff',
					'border_radius' => 'rounded',
					'animation' => false,
					'animation_runs' => 'repeat-1',
					'icon' => '',
					'image' => null,
					'sensitive_content' => false,
					'columns' => 1,
					'product_blog_post_id' => $product_blog_post_id,
					'product_translation_key' => mb_substr(query_clean($product_translation_key), 0, 128),
					'product_language_mode' => $product_language_mode,
					'product_language_code' => $product_language_code,
					'product_fallback_language_code' => $product_fallback_language_code,
					'product_image_url' => mb_substr(query_clean((string) ($seed_settings['product_image_url'] ?? '')), 0, 2048),
					'description' => mb_substr(query_clean((string) ($seed_settings['description'] ?? '')), 0, 220),
					'display_continents' => [],
					'display_countries' => [],
					'display_cities' => [],
					'display_devices' => [],
					'display_languages' => [],
					'display_operating_systems' => [],
					'display_browsers' => [],
				];
				break;

			case 'link_discount':
				$location_url = $this->normalize_ai_seed_location_url('link_discount', $seed_settings['location_url'] ?? '');
				if($location_url === '' || !str_starts_with(mb_strtolower($location_url), 'https://thealoeveraco.shop/')) {
					return 0;
				}

				$settings = [
					'name' => mb_substr(query_clean($seed_settings['name'] ?? 'Naruči proizvode bez registracije'), 0, 128),
					'open_in_new_tab' => false,
					'text_color' => 'black',
					'text_alignment' => 'center',
					'background_color' => '#FFC600',
					'border_shadow_style' => 'subtle',
					'border_shadow_offset_x' => 0,
					'border_shadow_offset_y' => 0,
					'border_shadow_blur' => 20,
					'border_shadow_spread' => 1,
					'border_shadow_color' => '#000000',
					'border_width' => 0,
					'border_style' => 'solid',
					'border_color' => '#000000',
					'border_radius' => 'rounded',
					'animation' => false,
					'animation_runs' => 'repeat-1',
					'icon' => '',
					'image' => '',
					'decoded_url' => \Altum\Link::decode_discount_link($location_url),
					'apply_to_all_products' => array_key_exists('apply_to_all_products', $seed_settings) ? (int) $seed_settings['apply_to_all_products'] : 1,
					'display_countries' => [],
					'display_devices' => [],
					'display_languages' => [],
					'display_operating_systems' => [],
				];
				break;
		}

		if(!$settings) {
			return 0;
		}

		$settings = $this->process_biolink_theme_id_settings($link, json_encode($settings), $block_type);
		$settings = $this->normalize_json_to_array($settings);
		if($apply_ai_theme_pack) {
			$settings = $this->apply_ai_theme_pack_to_seeded_block_settings($settings, $theme_pack, $block_type);
		}
		$settings = json_encode($settings);

		db()->insert('biolinks_blocks', [
			'user_id' => $this->user->user_id,
			'link_id' => (int) $link->link_id,
			'type' => $block_type,
			'location_url' => $location_url,
			'settings' => $settings,
			'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$total_blocks : $total_blocks,
			'datetime' => get_date(),
		]);

		return (int) db()->getInsertId();
	}

	private function apply_ai_block_patch_pack_to_block(int $block_id, string $block_type, array $block_patch_pack): bool {
		if($block_id <= 0 || $block_type === '' || empty($block_patch_pack)) {
			return false;
		}

		foreach($block_patch_pack as $patch) {
			$patch_block_type = trim((string) ($patch['block_type'] ?? ''));
			$patch_block_id = (int) ($patch['block_id'] ?? 0);

			if($patch_block_type !== $block_type || $patch_block_id > 0 || empty($patch['settings'])) {
				continue;
			}

			$new_block = db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->getOne('biolinks_blocks', ['biolink_block_id', 'settings']);

			if(!$new_block) {
				return false;
			}

			$new_block_settings = $this->normalize_json_to_array($new_block->settings ?? null);
			$new_block_settings = array_merge($new_block_settings, (array) ($patch['settings'] ?? []));

			db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
				'settings' => json_encode($new_block_settings),
			]);

			return true;
		}

		return false;
	}

	private function insert_biolink_block_after(int $link_id, int $new_block_id, int $after_block_id = 0): bool {
		$blocks = $this->get_biolink_blocks_layout_snapshot($link_id);

		if(empty($blocks) || $new_block_id <= 0) {
			return false;
		}

		$ordered_ids = [];
		foreach($blocks as $block) {
			$block_id = (int) ($block['biolink_block_id'] ?? 0);

			if($block_id <= 0 || $block_id === $new_block_id) {
				continue;
			}

			$ordered_ids[] = $block_id;
		}

		if($after_block_id > 0) {
			$inserted = false;
			$reordered = [];

			foreach($ordered_ids as $block_id) {
				$reordered[] = $block_id;

				if($block_id === $after_block_id) {
					$reordered[] = $new_block_id;
					$inserted = true;
				}
			}

			$ordered_ids = $inserted ? $reordered : array_merge([$new_block_id], $ordered_ids);
		} else {
			array_unshift($ordered_ids, $new_block_id);
		}

		foreach($ordered_ids as $index => $block_id) {
			db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
				'order' => $index + 1,
			]);
		}

		return true;
	}

	private function resolve_ai_missing_recommendation(array $recommendations, string $recommendation_key = '', string $block_type = ''): ?array {
		foreach($recommendations as $recommendation) {
			if($recommendation_key !== '' && (string) ($recommendation['recommendation_key'] ?? '') === $recommendation_key) {
				return $recommendation;
			}
		}

		foreach($recommendations as $recommendation) {
			if($block_type !== '' && (string) ($recommendation['block_type'] ?? '') === $block_type) {
				return $recommendation;
			}
		}

		return null;
	}

	private function get_biolink_blocks_full_snapshot(int $link_id): array {
		$result = database()->query("SELECT `biolink_block_id`, `type`, `location_url`, `settings`, `order`, `is_enabled`
			FROM `biolinks_blocks`
			WHERE `user_id` = {$this->user->user_id} AND `link_id` = {$link_id}
			ORDER BY `order` ASC, `biolink_block_id` ASC");
		$blocks = [];

		if($result) {
			while($row = $result->fetch_object()) {
				$blocks[] = [
					'biolink_block_id' => (int) ($row->biolink_block_id ?? 0),
					'type' => (string) ($row->type ?? ''),
					'location_url' => trim((string) ($row->location_url ?? '')),
					'settings' => $this->normalize_json_to_array($row->settings ?? null),
					'order' => (int) ($row->order ?? 0),
					'is_enabled' => (int) ($row->is_enabled ?? 0),
				];
			}
		}

		return $blocks;
	}

	private function get_biolink_blocks_layout_snapshot(int $link_id): array {
		$result = database()->query("SELECT `biolink_block_id`, `type`, `order`, `is_enabled`
			FROM `biolinks_blocks`
			WHERE `user_id` = {$this->user->user_id} AND `link_id` = {$link_id}
			ORDER BY `order` ASC, `biolink_block_id` ASC");
		$blocks = [];

		if($result) {
			while($row = $result->fetch_object()) {
				$blocks[] = [
					'biolink_block_id' => (int) ($row->biolink_block_id ?? 0),
					'type' => (string) ($row->type ?? ''),
					'order' => (int) ($row->order ?? 0),
					'is_enabled' => (int) ($row->is_enabled ?? 0),
				];
			}
		}

		return $blocks;
	}

	private function build_ai_layout_backup(int $link_id, array $additional, array $blocks): array {
		$review_key = trim((string) (($this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? null)['active_review_key'] ?? '')));

		return [
			'captured_at' => get_date(),
			'review_key' => $review_key,
			'blocks' => array_values(array_map(static function($block): array {
				return [
					'biolink_block_id' => (int) ($block['biolink_block_id'] ?? 0),
					'type' => (string) ($block['type'] ?? ''),
					'order' => (int) ($block['order'] ?? 0),
					'is_enabled' => (int) ($block['is_enabled'] ?? 0),
				];
			}, $blocks)),
			];
	}

	private function build_ai_bundle_backup(\stdClass $link, array $additional): array {
		$review_key = trim((string) (($this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? null)['active_review_key'] ?? '')));

		return [
			'captured_at' => get_date(),
			'review_key' => $review_key,
			'link_settings' => $this->normalize_json_to_array($link->settings ?? null),
			'biolink_theme_id' => (int) ($link->biolink_theme_id ?? 0),
			'blocks' => $this->get_biolink_blocks_full_snapshot((int) ($link->link_id ?? 0)),
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

	private function get_ai_bundle_baseline_backup(array $additional, string $review_key = ''): array {
		$baseline_backup = $this->normalize_json_to_array($additional['fcc_ai_bundle_baseline_backup'] ?? []);

		return $this->is_ai_bundle_backup_usable($baseline_backup, $review_key) ? $baseline_backup : [];
	}

	private function ensure_ai_bundle_backup(\stdClass $link, array $additional): array {
		$existing_backup = $this->normalize_json_to_array($additional['fcc_ai_bundle_backup'] ?? []);
		$baseline_backup = $this->normalize_json_to_array($additional['fcc_ai_bundle_baseline_backup'] ?? []);
		$current_review_key = $this->get_active_ai_bundle_review_key($additional);
		$has_existing_backup = $this->is_ai_bundle_backup_usable($existing_backup, $current_review_key);
		$has_baseline_backup = $this->is_ai_bundle_backup_usable($baseline_backup, $current_review_key);

		if($has_existing_backup && $has_baseline_backup) {
			return $additional;
		}

		$resolved_backup = $has_existing_backup ? $existing_backup : $this->build_ai_bundle_backup($link, $additional);
		$additional['fcc_ai_bundle_backup'] = $resolved_backup;

		if(!$has_baseline_backup) {
			$additional['fcc_ai_bundle_baseline_backup'] = $resolved_backup;
		}

		return $additional;
	}

	private function restore_biolink_blocks_layout_snapshot(array $current_blocks, array $backup_blocks): array {
		$current_map = [];
		foreach($current_blocks as $block) {
			$current_map[(int) ($block['biolink_block_id'] ?? 0)] = $block;
		}

		$ordered_ids = [];
		$backup_map = [];
		foreach($backup_blocks as $block) {
			$block_id = (int) ($block['biolink_block_id'] ?? 0);

			if($block_id <= 0 || !isset($current_map[$block_id])) {
				continue;
			}

			$ordered_ids[] = $block_id;
			$backup_map[$block_id] = [
				'order' => (int) ($block['order'] ?? 0),
				'is_enabled' => (int) ($block['is_enabled'] ?? 0),
			];
		}

		foreach($current_blocks as $block) {
			$block_id = (int) ($block['biolink_block_id'] ?? 0);

			if($block_id > 0 && !isset($backup_map[$block_id])) {
				$ordered_ids[] = $block_id;
			}
		}

		$restored_blocks = 0;
		$re_enabled_blocks = 0;

		foreach($ordered_ids as $index => $block_id) {
			$new_order = $index + 1;
			$current_order = (int) ($current_map[$block_id]['order'] ?? 0);

			if($current_order !== $new_order) {
				db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
					'order' => $new_order,
				]);
				$restored_blocks++;
			}

			if(isset($backup_map[$block_id])) {
				$current_enabled = (int) ($current_map[$block_id]['is_enabled'] ?? 0);
				$target_enabled = (int) ($backup_map[$block_id]['is_enabled'] ?? $current_enabled);

				if($current_enabled !== $target_enabled) {
					db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
						'is_enabled' => $target_enabled,
					]);
					$restored_blocks++;

					if($target_enabled === 1 && $current_enabled !== 1) {
						$re_enabled_blocks++;
					}
				}
			}
		}

		return [
			'restored_blocks' => $restored_blocks,
			're_enabled_blocks' => $re_enabled_blocks,
		];
	}

	private function restore_biolink_blocks_full_snapshot(array $current_blocks, array $backup_blocks): array {
		$current_map = [];
		foreach($current_blocks as $block) {
			$current_map[(int) ($block['biolink_block_id'] ?? 0)] = $block;
		}

		$backup_map = [];
		$ordered_backup_ids = [];
		foreach($backup_blocks as $block) {
			$block_id = (int) ($block['biolink_block_id'] ?? 0);

			if($block_id <= 0 || !isset($current_map[$block_id])) {
				continue;
			}

			$ordered_backup_ids[] = $block_id;
			$backup_map[$block_id] = [
				'location_url' => trim((string) ($block['location_url'] ?? '')),
				'settings' => $this->normalize_json_to_array($block['settings'] ?? []),
				'order' => (int) ($block['order'] ?? 0),
				'is_enabled' => (int) ($block['is_enabled'] ?? 0),
			];
		}

		$extra_blocks = array_values(array_filter($current_blocks, static function($block) use ($backup_map): bool {
			return !isset($backup_map[(int) ($block['biolink_block_id'] ?? 0)]);
		}));
		usort($extra_blocks, static function($a, $b) {
			$order_compare = ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));

			if($order_compare !== 0) {
				return $order_compare;
			}

			return ((int) ($a['biolink_block_id'] ?? 0)) <=> ((int) ($b['biolink_block_id'] ?? 0));
		});

		$ordered_all_ids = array_merge(
			$ordered_backup_ids,
			array_map(static fn($block): int => (int) ($block['biolink_block_id'] ?? 0), $extra_blocks)
		);
		$restored_blocks = 0;
		$re_enabled_blocks = 0;
		$hidden_new_blocks = 0;

		foreach($ordered_all_ids as $index => $block_id) {
			if($block_id <= 0 || !isset($current_map[$block_id])) {
				continue;
			}

			$updates = [];
			$current_block = $current_map[$block_id];
			$new_order = $index + 1;

			if((int) ($current_block['order'] ?? 0) !== $new_order) {
				$updates['order'] = $new_order;
			}

			if(isset($backup_map[$block_id])) {
				$target_block = $backup_map[$block_id];

				if((int) ($current_block['is_enabled'] ?? 0) !== (int) ($target_block['is_enabled'] ?? 0)) {
					$updates['is_enabled'] = (int) ($target_block['is_enabled'] ?? 0);

					if((int) ($target_block['is_enabled'] ?? 0) === 1 && (int) ($current_block['is_enabled'] ?? 0) !== 1) {
						$re_enabled_blocks++;
					}
				}

				if(trim((string) ($current_block['location_url'] ?? '')) !== (string) ($target_block['location_url'] ?? '')) {
					$updates['location_url'] = (string) ($target_block['location_url'] ?? '');
				}

				if(json_encode($this->normalize_json_to_array($current_block['settings'] ?? [])) !== json_encode($this->normalize_json_to_array($target_block['settings'] ?? []))) {
					$updates['settings'] = json_encode($this->normalize_json_to_array($target_block['settings'] ?? []));
				}
			} else {
				if((int) ($current_block['is_enabled'] ?? 0) !== 0) {
					$updates['is_enabled'] = 0;
					$hidden_new_blocks++;
				}
			}

			if(!empty($updates)) {
				db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', $updates);
				$restored_blocks++;
			}
		}

		return [
			'restored_blocks' => $restored_blocks,
			're_enabled_blocks' => $re_enabled_blocks,
			'hidden_new_blocks' => $hidden_new_blocks,
		];
	}

	private function resolve_ai_layout_action_block_id(array $blocks, array $action): int {
		$block_id = (int) ($action['block_id'] ?? 0);

		if($block_id > 0) {
			foreach($blocks as $block) {
				if((int) ($block['biolink_block_id'] ?? 0) === $block_id) {
					return $block_id;
				}
			}
		}

		$block_type = trim((string) ($action['block_type'] ?? ''));
		if($block_type === '') {
			return 0;
		}

		foreach($blocks as $block) {
			if((string) ($block['type'] ?? '') === $block_type && (int) ($block['is_enabled'] ?? 0) === 1) {
				return (int) ($block['biolink_block_id'] ?? 0);
			}
		}

		foreach($blocks as $block) {
			if((string) ($block['type'] ?? '') === $block_type) {
				return (int) ($block['biolink_block_id'] ?? 0);
			}
		}

		return 0;
	}

	private function get_ai_theme_library(): array {
		$preferences = $this->get_preferences_object();
		$library = $preferences->leader_ai_theme_library ?? [];

		if($library instanceof \stdClass) {
			$library = (array) $library;
		}

		if(!is_array($library)) {
			return [];
		}

		$normalized = [];

		foreach($library as $entry) {
			if($entry instanceof \stdClass) {
				$entry = (array) $entry;
			}

			if(!is_array($entry)) {
				continue;
			}

			$theme_pack = $this->normalize_ai_theme_pack($entry['theme_pack'] ?? []);

			$normalized[] = [
				'theme_key' => trim((string) ($entry['theme_key'] ?? '')),
				'theme_pack' => $theme_pack,
			];
		}

		return $normalized;
	}

	private function get_ai_theme_pack_for_link($link, string $theme_key = ''): array {
		$link_additional = $this->normalize_json_to_array($link->additional ?? null);

		if($theme_key !== '') {
			foreach($this->get_ai_theme_library() as $entry) {
				if((string) ($entry['theme_key'] ?? '') === $theme_key) {
					return $this->normalize_ai_theme_pack($entry['theme_pack'] ?? []);
				}
			}
		}

		return $this->normalize_ai_theme_pack($link_additional['fcc_ai_theme_pack'] ?? []);
	}

	private function get_ai_primary_focus_payload($link): array {
		$link_additional = $this->normalize_json_to_array($link->additional ?? null);
		$block_catalog = $this->get_ai_editor_block_catalog((int) ($link->link_id ?? 0));
		$raw_primary_block_plan = $this->normalize_ai_primary_block_plan($link_additional['fcc_ai_primary_block_plan'] ?? []);
		$raw_missing_block_recommendations = $this->build_ai_missing_block_recommendations($link_additional, $block_catalog, $raw_primary_block_plan);
		$primary_block_plan = $this->get_effective_ai_primary_block_plan($link_additional, $block_catalog, $raw_missing_block_recommendations, (int) ($link->link_id ?? 0), $this->user->preferences ?? null);
		$missing_block_recommendations = $this->build_ai_missing_block_recommendations($link_additional, $block_catalog, $primary_block_plan);

		return [
			'theme_pack' => $this->normalize_ai_theme_pack($link_additional['fcc_ai_theme_pack'] ?? []),
			'primary_block_plan' => $primary_block_plan,
			'block_patch_pack' => $this->normalize_ai_block_patch_pack($link_additional['fcc_ai_block_patch_pack'] ?? []),
			'layout_actions' => $this->build_effective_ai_layout_actions($link_additional, $block_catalog, $primary_block_plan, $missing_block_recommendations, (int) ($link->link_id ?? 0), $this->user->preferences ?? null),
			'additional' => $link_additional,
		];
	}

	private function clear_biolink_cache(int $link_id): void {
		cache()->deleteItem('biolink_blocks?link_id=' . $link_id);
		cache()->deleteItem('link?link_id=' . $link_id);
		cache()->deleteItemsByTag('link_id=' . $link_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);
	}

	private function get_themable_biolink_block_types(): array {
		$biolink_blocks = require APP_PATH . 'includes/biolink_blocks.php';

		return array_keys(array_filter($biolink_blocks, static fn($block) => !empty($block['themable'])));
	}

	private function apply_ai_secondary_theme_to_block_settings(array $settings, string $type, array $theme_pack): array {
		if(isset($settings['text_color']) && $theme_pack['secondary_blocks_text'] !== '') {
			$settings['text_color'] = $theme_pack['secondary_blocks_text'];
		}

		if(isset($settings['background_color']) && $theme_pack['secondary_blocks_background'] !== '') {
			$settings['background_color'] = $theme_pack['secondary_blocks_background'];
		}

		if(isset($settings['border_color']) && $theme_pack['secondary_blocks_border'] !== '') {
			$settings['border_color'] = $theme_pack['secondary_blocks_border'];
		}

		if(isset($settings['border_shadow_color']) && $theme_pack['secondary_blocks_shadow'] !== '') {
			$settings['border_shadow_color'] = $theme_pack['secondary_blocks_shadow'];
		}

		if(isset($settings['border_shadow_style']) && $theme_pack['secondary_blocks_shadow'] !== '' && in_array((string) ($settings['border_shadow_style'] ?? ''), ['', 'none'], true)) {
			$settings['border_shadow_style'] = 'subtle';
		}

		if($type === 'heading' && isset($settings['text_color']) && $theme_pack['heading_color'] !== '') {
			$settings['text_color'] = $theme_pack['heading_color'];
		}

		if($type === 'socials' && isset($settings['background_color']) && $theme_pack['secondary_blocks_background'] !== '') {
			$settings['background_color'] = $theme_pack['secondary_blocks_background'];
		}

		return $settings;
	}

	private function apply_ai_primary_theme_to_block_settings(array $settings, array $theme_pack): array {
		if(isset($settings['text_color']) && $theme_pack['primary_block_text'] !== '') {
			$settings['text_color'] = $theme_pack['primary_block_text'];
		}

		if(isset($settings['background_color']) && $theme_pack['primary_block_background'] !== '') {
			$settings['background_color'] = $theme_pack['primary_block_background'];
		}

		if(isset($settings['border_color']) && $theme_pack['primary_block_border'] !== '') {
			$settings['border_color'] = $theme_pack['primary_block_border'];
		}

		if(isset($settings['border_shadow_color']) && $theme_pack['primary_block_shadow'] !== '') {
			$settings['border_shadow_color'] = $theme_pack['primary_block_shadow'];
		}

		if(isset($settings['border_shadow_style']) && $theme_pack['primary_block_shadow'] !== '' && in_array((string) ($settings['border_shadow_style'] ?? ''), ['', 'none'], true)) {
			$settings['border_shadow_style'] = 'subtle';
		}

		foreach(['button_background_color', 'popup_button_background_color', 'page_button_background_color'] as $key) {
			if(isset($settings[$key]) && $theme_pack['primary_block_background'] !== '') {
				$settings[$key] = $theme_pack['primary_block_background'];
			}
		}

		foreach(['button_text_color', 'popup_button_text_color', 'page_button_text_color'] as $key) {
			if(isset($settings[$key]) && $theme_pack['primary_block_text'] !== '') {
				$settings[$key] = $theme_pack['primary_block_text'];
			}
		}

		foreach(['popup_background_color', 'page_background_color'] as $key) {
			if(isset($settings[$key]) && $theme_pack['primary_block_background'] !== '') {
				$settings[$key] = $theme_pack['primary_block_background'];
			}
		}

		foreach(['popup_text_color', 'page_text_color'] as $key) {
			if(isset($settings[$key]) && $theme_pack['primary_block_text'] !== '') {
				$settings[$key] = $theme_pack['primary_block_text'];
			}
		}

		return $settings;
	}

	private function resolve_ai_primary_block_id(int $link_id, array $primary_block_plan): int {
		$block_id = (int) ($primary_block_plan['block_id'] ?? 0);

		if($block_id > 0) {
			return $block_id;
		}

		$block_type = (string) ($primary_block_plan['block_type'] ?? '');

		if($block_type === '') {
			return 0;
		}

		$candidate_blocks = db()->where('link_id', $link_id)->where('type', $block_type)->orderBy('`order`', 'ASC')->get('biolinks_blocks', null, ['biolink_block_id']);

		if(count($candidate_blocks) === 1) {
			return (int) ($candidate_blocks[0]->biolink_block_id ?? 0);
		}

		return 0;
	}

	private function get_biolink_theme_controlled_settings_keys(): array {
		return [
			'background_type',
			'background',
			'background_color_one',
			'background_color_two',
			'font',
			'font_size',
			'background_blur',
			'background_brightness',
			'width',
			'block_spacing',
			'hover_animation',
		];
	}

	private function get_biolink_theme_default_settings(): array {
		return [
			'background_type' => 'preset',
			'background' => 'zero',
			'background_color_one' => null,
			'background_color_two' => null,
			'font' => 'default',
			'font_size' => 16,
			'background_blur' => 0,
			'background_brightness' => 100,
			'width' => 8,
			'block_spacing' => 2,
			'hover_animation' => 'smooth',
		];
	}

	private function get_themable_biolink_blocks_snapshot(int $link_id): array {
		$biolink_blocks = require APP_PATH . 'includes/biolink_blocks.php';
		$themable_blocks = array_keys(array_filter($biolink_blocks, fn($block) => !empty($block['themable'])));

		if(empty($themable_blocks)) {
			return [];
		}

		$themable_blocks_sql = "'" . implode('\', \'', $themable_blocks) . "'";
		$result = database()->query("SELECT `biolink_block_id`, `type`, `settings` FROM `biolinks_blocks` WHERE `link_id` = {$link_id} AND `type` IN ({$themable_blocks_sql})");
		$snapshot = [];

		while($biolink_block = $result->fetch_object()) {
			$snapshot[(int) $biolink_block->biolink_block_id] = [
				'type' => (string) $biolink_block->type,
				'settings' => $this->normalize_json_to_array($biolink_block->settings ?? null),
			];
		}

		return $snapshot;
	}

	private function restore_themable_biolink_blocks_snapshot(array $snapshot): void {
		foreach($snapshot as $biolink_block_id => $block_data) {
			$biolink_block_id = (int) $biolink_block_id;

			if($biolink_block_id <= 0) {
				continue;
			}

			db()->where('biolink_block_id', $biolink_block_id)->update('biolinks_blocks', [
				'settings' => json_encode($block_data['settings'] ?? []),
			]);
		}
	}

	private function build_biolink_theme_custom_backup($link): array {
		$settings = $this->normalize_json_to_array($link->settings ?? null);
		$settings = array_intersect_key($settings, array_flip($this->get_biolink_theme_controlled_settings_keys()));

		$additional = $this->normalize_json_to_array($link->additional ?? null);
		unset($additional['fcc_theme_custom_backup']);

		if(!empty($link->biolink_theme_id)) {
			unset($additional['custom_css'], $additional['custom_js']);
		}

		return [
			'settings' => $settings,
			'additional' => $additional,
			'blocks' => $this->get_themable_biolink_blocks_snapshot((int) $link->link_id),
		];
	}

	private function are_theme_values_equal($a, $b): bool {
		return json_encode($a) === json_encode($b);
	}

	private function remove_matching_theme_values(array $settings, array $theme_values): array {
		foreach($theme_values as $key => $value) {
			if(array_key_exists($key, $settings) && $this->are_theme_values_equal($settings[$key], $value)) {
				unset($settings[$key]);
			}
		}

		return $settings;
	}

	private function get_theme_values_to_remove_for_block_type(string $type, $biolink_theme): array {
		$theme_block = $this->normalize_json_to_array($biolink_theme->settings->biolink_block ?? null);

		switch($type) {
			case 'socials':
				return $this->normalize_json_to_array($biolink_theme->settings->biolink_block_socials ?? null);

			case 'heading':
				return $this->normalize_json_to_array($biolink_theme->settings->biolink_block_heading ?? null);

			case 'paragraph':
				return array_merge(
					$theme_block,
					$this->normalize_json_to_array($biolink_theme->settings->biolink_block_paragraph ?? null)
				);

			case 'counter':
			case 'loading':
				$theme_block['number_color'] = $theme_block['text_color'] ?? ($theme_block['number_color'] ?? null);
				return $theme_block;

			case 'external_item':
				$theme_block['price_color'] = $theme_block['text_color'] ?? ($theme_block['price_color'] ?? null);
				$theme_block['name_color'] = $theme_block['text_color'] ?? ($theme_block['name_color'] ?? null);
				return $theme_block;

			case 'business_hours':
				$theme_block['icon_color'] = $theme_block['text_color'] ?? ($theme_block['icon_color'] ?? null);
				return $theme_block;

			default:
				return $theme_block;
		}
	}

	private function remove_biolink_theme_styles_from_existing_blocks(int $link_id, $biolink_theme): void {
		if(!$biolink_theme) {
			return;
		}

		$biolink_blocks = require APP_PATH . 'includes/biolink_blocks.php';
		$themable_blocks = array_keys(array_filter($biolink_blocks, fn($block) => !empty($block['themable'])));

		if(empty($themable_blocks)) {
			return;
		}

		$themable_blocks_sql = "'" . implode('\', \'', $themable_blocks) . "'";
		$result = database()->query("SELECT `biolink_block_id`, `type`, `settings` FROM `biolinks_blocks` WHERE `link_id` = {$link_id} AND `type` IN ({$themable_blocks_sql})");

		while($biolink_block = $result->fetch_object()) {
			$settings = $this->normalize_json_to_array($biolink_block->settings ?? null);
			$theme_values = $this->get_theme_values_to_remove_for_block_type((string) $biolink_block->type, $biolink_theme);
			$settings = $this->remove_matching_theme_values($settings, $theme_values);

			db()->where('biolink_block_id', (int) $biolink_block->biolink_block_id)->update('biolinks_blocks', [
				'settings' => json_encode($settings),
			]);
		}
	}

	private function get_biolink_theme_fallback_settings_after_disable(array $settings, $biolink_theme): array {
		if(!$biolink_theme) {
			return $settings;
		}

		$theme_settings = $this->normalize_json_to_array($biolink_theme->settings->biolink ?? null);
		$default_settings = $this->get_biolink_theme_default_settings();

		foreach($this->get_biolink_theme_controlled_settings_keys() as $key) {
			if(
				array_key_exists($key, $theme_settings)
				&& array_key_exists($key, $settings)
				&& $this->are_theme_values_equal($settings[$key], $theme_settings[$key])
			) {
				$settings[$key] = $default_settings[$key] ?? null;
			}
		}

		if(($settings['background_type'] ?? null) !== 'gradient') {
			$settings['background_color_one'] = null;
			$settings['background_color_two'] = null;
		}

		return $settings;
	}

	private function is_enabled_toggle() {
		/* Team checks */
		if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.links')) {
			Response::json(l('global.info_message.team_no_access'), 'error');
		}

		$_POST['link_id'] = (int) $_POST['link_id'];

		/* Get the current status */
		$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links', ['link_id', 'domain_id', 'is_enabled', 'url', 'location_url', 'type']);

		if($link) {
			$new_is_enabled = (int) !$link->is_enabled;

			/* Custom code: FC-2026-03-19: keep the default biolink/vcard always available on limited plans */
			$default_biolink_id = (int) (fc_get_user_main_biolink_id((int) $this->user->user_id) ?? 0);
			$default_vcard_id = (int) (db()->where('user_id', $this->user->user_id)->getValue('users_vcards', 'vcard_id') ?? 0);
			$is_protected_default_link = ($link->type == 'biolink' && $default_biolink_id && (int) $link->link_id === $default_biolink_id)
				|| ($link->type == 'vcard' && $default_vcard_id && (int) $link->link_id === $default_vcard_id);

			if(!$new_is_enabled && $is_protected_default_link) {
				Response::json(l('link_delete_modal.error_message.main_biolink_locked'), 'error');
			}

			if($new_is_enabled && $link->type == 'biolink' && $default_biolink_id && (int) $link->link_id !== $default_biolink_id && (int) ($this->user->plan_settings->biolinks_limit ?? -1) === 1) {
				Response::json(l('global.info_message.plan_feature_limit'), 'error');
			}
			/* /Custom code: FC-2026-03-19 */

			if($new_is_enabled && !$this->can_enable_link_by_plan($link->type, $link->link_id)) {
				Response::json(l('global.info_message.plan_feature_limit'), 'error');
			}

			/* Custom code: FC-2026-02-24: FCC core biolink gate */
			if($link->type == 'biolink' && $new_is_enabled && !\Altum\Authentication::can_use_biolinks_without_fcc_completion()) {
				Response::json(l('fcc.core_gate.biolinks_disabled'), 'error');
			}
			/* /Custom code: FC-2026-02-24 */

			db()->where('link_id', $link->link_id)->update('links', ['is_enabled' => $new_is_enabled]);

            /* Get domain */
            $domain = (new Domain())->get_domain_by_domain_id($link->domain_id);

			/* Clear the cache */
			cache()->deleteItem('link?link_id=' . $_POST['link_id']);
			cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);
			cache()->deleteItemsByTag('link_id=' . $_POST['link_id']);

            /* Send webhook notification if needed */
            if(settings()->webhooks->link_update) {
                fire_and_forget('post', settings()->webhooks->link_update, [
                    'user_id' => $this->user->user_id,
                    'link_id' => $link->link_id,
                    'domain_id' => $link->domain_id,
                    'url' => $link->url,
                    'location_url' => $link->location_url,
                    'full_url' => $link->domain_id ? $domain->scheme . $domain->host . '/' . ($domain->link_id == $link->link_id ? null : $link->url) : SITE_URL . $link->url,
                    'type' => 'link',
                    'datetime' => get_date(),
                ], signature: true);
            }

			Response::json(l('global.success_message.create2'), 'success');
		}
	}

	private function create() {
		/* Team checks */
		if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.links')) {
			Response::json(l('global.info_message.team_no_access'), 'error');
		}

		$_POST['type'] = trim(query_clean($_POST['type']));

		/* Check for possible errors */
		if(!array_key_exists($_POST['type'], $this->links_types)) {
			die();
		}

		$this->{'create_' . $_POST['type']}();

	}

	private function create_link() {
		if(!settings()->links->shortener_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$_POST['location_url'] = get_url($_POST['location_url']);
		$_POST['url'] = !empty($_POST['url']) && $this->user->plan_settings->custom_url ? get_slug($_POST['url'], '-', false) : null;
		$_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);
		$type = 'link';

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Check if custom domain is set */
        $domain_id = 0;
        $domains = [];

        if(isset($_POST['domain_id'])) {
            /* Get domains */
            $domains = (new Domain())->get_available_domains_by_user($this->user);

            if(isset($domains[$domain_id])) {
                $domain_id = $domains[$_POST['domain_id']]->domain_id;
            }
        }

		if(empty($_POST['location_url'])) {
			Response::json(l('global.error_message.empty_fields'), 'error');
		}

		$this->check_url($_POST['url']);

		$this->check_location_url($_POST['location_url']);

		/* Check for the plan limit */
		$user_total_links = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = 'link'")->fetch_object()->total;
		if($this->user->plan_settings->links_limit != -1 && $user_total_links >= $this->user->plan_settings->links_limit) {
			Response::json(l('global.info_message.plan_feature_limit'), 'error');
		}

		/* Check for duplicate url if needed */
		if($_POST['url']) {
			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}

			$url = $_POST['url'];
		} else {
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));

			/* Generate random url if not specified */
			while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
			}
		}

		$app_linking = [
			'ios_location_url' => null,
			'android_location_url' => null,
			'app' => null,
		];

		$supported_apps = require APP_PATH . 'includes/app_linking.php';
		$app_linking_location_url = $_POST['location_url'];

		foreach($supported_apps as $app_key => $app) {
			foreach($app['formats'] as $format => $targets) {

				if(preg_match('/' . $targets['regex'] . '/', $app_linking_location_url, $match)) {

					/* Extract and normalize hostnames */
					$user_host = parse_url($app_linking_location_url, PHP_URL_HOST);
					$format_host = parse_url('https://' . str_replace('%s', 'placeholder', $format), PHP_URL_HOST);

					/* Remove www. and m. prefixes for more flexible comparison */
					$user_host = preg_replace('/^(www\.|m\.)/', '', $user_host);
					$format_host = preg_replace('/^(www\.|m\.)/', '', $format_host);

					/* Compare the normalized hosts */
					if($user_host === $format_host) {

						if(count($match) > 1) {
							array_shift($match);
							$app_linking['ios_location_url'] = vsprintf($targets['iOS'], $match);
							$app_linking['android_location_url'] = vsprintf($targets['Android'], $match);
							$app_linking['app'] = $app_key;
						}

						break 2;
					}
				}

			}
		}

		$settings = json_encode([
			'http_status_code' => 301,
			'clicks_limit' => null,
			'expiration_url' => null,
			'password' => null,
			'sensitive_content' => false,
			'targeting_type' => null,
			'app_linking_is_enabled' => $this->user->plan_settings->app_linking_is_enabled,
			'app_linking' => $app_linking,
			'cloaking_is_enabled' => false,
			'cloaking_title' => null,
			'cloaking_meta_description' => null,
			'cloaking_custom_js' => null,
			'cloaking_favicon' => null,
			'cloaking_opengraph' => null,
			'forward_query_parameters_is_enabled' => false,
			'utm' => [
				'source' => null,
				'medium' => null,
				'campaign' => null,
			]
		]);

		/* Insert to database */
		$link_id = db()->insert('links', [
			'user_id' => $this->user->user_id,
			'domain_id' => $domain_id,
			'type' => $type,
			'url' => $url,
			'location_url' => $_POST['location_url'],
			'settings' => $settings,
			'datetime' => get_date(),
			'email_reports_last_datetime' => get_date(),
		]);

		/* Clear the cache */
		cache()->deleteItem($type . '_links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_new) {
            fire_and_forget('post', settings()->webhooks->link_new, [
                'user_id' => $this->user->user_id,
                'link_id' => $link_id,
                'domain_id' => $domain_id,
                'url' => $url,
                'location_url' => $_POST['location_url'],
                'full_url' => $domain_id ? $domains[$domain_id]->url . $url : SITE_URL . $url,
                'type' => $type,
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.create2'), 'success', ['url' => url('link/' . $link_id . ($this->user->preferences->links_auto_copy_link ? '?auto_copy_link=true' : ''))]);
	}

	private function create_biolink() {
		if(!settings()->links->biolinks_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$_POST['url'] = !empty($_POST['url']) && $this->user->plan_settings->custom_url ? get_slug($_POST['url'], '-', false) : null;
		$_POST['biolink_template_id'] = isset($_POST['biolink_template_id']) && in_array($_POST['biolink_template_id'], $this->user->plan_settings->biolinks_templates ?? []) ? (int) $_POST['biolink_template_id'] : null;

		/* Check for a default template id */
		if(!$_POST['biolink_template_id'] && settings()->links->default_biolink_template_id) {
			$_POST['biolink_template_id'] = settings()->links->default_biolink_template_id;
		}

		/* Custom code: FC-2026-02-27: fallback template for first biolink creation */
		if(!$_POST['biolink_template_id']) {
			$user_total_biolinks_for_template_fallback = (int) database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = 'biolink'")->fetch_object()->total;

			if($user_total_biolinks_for_template_fallback === 0) {
				/* Custom code: FC-2026-03-06: force /link/83 template as primary fallback */
				$first_enabled_template = $this->get_factory_biolink_template();
				/* /Custom code: FC-2026-03-06 */

				if($first_enabled_template && isset($first_enabled_template->biolink_template_id)) {
					$_POST['biolink_template_id'] = (int) $first_enabled_template->biolink_template_id;
				}
			}
		}
		/* /Custom code: FC-2026-02-27 */

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Check if custom domain is set */
        $domain_id = 0;
        $domains = [];

        if(isset($_POST['domain_id'])) {

            /* Get domains */
            $domains = (new Domain())->get_available_domains_by_user($this->user);

            if(isset($domains[$_POST['domain_id']])) {
                $domain_id = $domains[$_POST['domain_id']]->domain_id;
            }
        }

		/* Check for the plan limit */
		$user_total_biolinks = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = 'biolink'")->fetch_object()->total;
		if($this->user->plan_settings->biolinks_limit != -1 && $user_total_biolinks >= $this->user->plan_settings->biolinks_limit) {
			Response::json(l('global.info_message.plan_feature_limit'), 'error');
		}

		/* Check for duplicate url if needed */
		if($_POST['url']) {
			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}
		}

		/* Start the creation process */
		$url = $_POST['url'] ? $_POST['url'] : mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
		$type = 'biolink';
		$settings = [
			'service_worker' => null,

			'pwa_file_name' => null,
			'pwa_is_enabled' => false,
			'pwa_display_install_bar' => false,
			'pwa_display_install_bar_delay' => 3,
			'pwa_theme_color' => '#000000',
			'pwa_icon' => null,

			'branded_button_is_enabled' => false,
			'branded_button_icon' => null,
			'branded_button_title' => null,
			'branded_button_content' => null,

			'verified_location' => 'top',
			'favicon' => null,
			'background_type' => 'preset',
			'background' => 'zero',
			'background_attachment' => 'scroll',
			'background_blur' => 0,
			'background_brightness' => 100,
			'text_color' => '#ffffff',
			'display_branding' => true,
			'branding' => [
				'url' => '',
				'name' => ''
			],
			'seo' => [
				'block' => false,
				'title' => '',
				'meta_description' => '',
				'meta_keywords' => '',
				'image' => '',
			],
			'utm' => [
				'medium' => '',
				'source' => '',
			],
			'font' => 'default',
			'font_size' => 16,
			'width' => 8,
			'block_spacing' => 2,
			'hover_animation' => 'smooth',
			'password' => null,
			'sensitive_content' => false,
			'leap_link' => null,
			'custom_css' => null,
			'custom_js' => null,
			'share_is_enabled' => true,
			'scroll_buttons_is_enabled' => true,
            'language_code' => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : \Altum\Language::$default_code,
		];

		/* Generate random url if not specified */
		while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
		}

		$this->check_url($_POST['url']);

		$additional = null;
		$biolink_theme_id = null;

		/* Check for biolink templates */
		if($_POST['biolink_template_id']) {
			$biolinks_templates = (new \Altum\Models\BiolinksTemplates())->get_biolinks_templates();

			if(array_key_exists($_POST['biolink_template_id'], $biolinks_templates)) {
				$biolink_template = $biolinks_templates[$_POST['biolink_template_id']];

				/* Get the details of the biolink page */
				$biolink = db()->where('link_id', $biolink_template->link_id)->getOne('links');

				if($biolink) {
					/* Get all the biolink blocks as well */
					$biolink->settings = json_decode($biolink->settings ?? '');
					$biolink->settings->seo->image = \Altum\Uploads::copy_uploaded_file($biolink->settings->seo->image, 'block_images/', 'block_images/', 'json_error');
					$biolink->settings->favicon = \Altum\Uploads::copy_uploaded_file($biolink->settings->favicon, 'favicons/', 'favicons/', 'json_error');
					if($biolink->settings->background_type == 'image') $biolink->settings->background = \Altum\Uploads::copy_uploaded_file($biolink->settings->background, 'backgrounds/', 'backgrounds/', 'json_error');
					$biolink->settings->pwa_is_enabled = false;
					$biolink->settings->pwa_icon = null;
					$biolink->settings->branded_button_icon = null;
                    $biolink->settings->service_worker = null;
					$additional = $biolink->additional;
					$biolink_theme_id = $biolink->biolink_theme_id;

					/* Overwrite default settings with the settings of the template */
					$settings = $biolink->settings;

					/* Database query */
					db()->where('biolink_template_id', $biolink_template->biolink_template_id)->update('biolinks_templates', [
						'total_usage' => db()->inc()
					]);

				}
			}
		}

		/* Check for a default theme id */
		if(!$_POST['biolink_template_id'] && settings()->links->default_biolink_theme_id) {
			$biolink_theme_id = settings()->links->default_biolink_theme_id;

			/* Get available themes */
			$biolinks_themes = (new BiolinksThemes())->get_biolinks_themes();
			$biolink_theme_id = isset($biolink_theme_id) && array_key_exists($biolink_theme_id, $biolinks_themes) ? $biolink_theme_id : null;

			if($biolink_theme_id) {
				$biolink_theme = $biolinks_themes[$biolink_theme_id];

				/* Save settings for biolink page */
				$settings = array_merge($settings, (array) $biolink_theme->settings->biolink);

				/* Save the additional settings */
				$additional = json_encode($biolink_theme->settings->additional);
			}
		}

		$settings = json_encode($settings);

		/* Insert to database */
		/* Custom code: FC-2026-02-24: FCC core biolink gate */
		$is_enabled = \Altum\Authentication::can_use_biolinks_without_fcc_completion() ? 1 : 0;
		/* /Custom code: FC-2026-02-24 */
		$link_id = db()->insert('links', [
			'user_id' => $this->user->user_id,
			'domain_id' => $domain_id,
			'biolink_theme_id' => $biolink_theme_id ?? null,
			'type' => $type,
			'url' => $url,
			'settings' => $settings,
			'additional' => $additional,
			/* Custom code: FC-2026-02-24: FCC core biolink gate */
			'is_enabled' => $is_enabled,
			/* /Custom code: FC-2026-02-24 */
			'datetime' => get_date(),
		]);

		/* Check for a template usage */
		if(isset($biolink_template)) {
			/* Get all biolink blocks if needed */
			$biolink_blocks = db()->where('link_id', $biolink_template->link_id)->get('biolinks_blocks');

			foreach($biolink_blocks as $biolink_block) {
				$biolink_block->settings = json_decode($biolink_block->settings ?? '');

				if(is_array($biolink_block->settings)) {
					$biolink_block->settings = (object) $biolink_block->settings;
				}

				/* Duplication of resources */
				switch($biolink_block->type) {
					case 'file':
					case 'audio':
					case 'video':
					case 'pdf_document':
					case 'powerpoint_presentation':
					case 'excel_spreadsheet':
						$biolink_block->settings->file = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->file, \Altum\Uploads::get_path('files'), \Altum\Uploads::get_path('files'), 'json_error');
						break;

					case 'review':
						$biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, \Altum\Uploads::get_path('block_images'), \Altum\Uploads::get_path('block_images'), 'json_error');
						break;

					case 'avatar':
						$biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'avatars/', 'avatars/', 'json_error');
						break;

					case 'header':
						$biolink_block->settings->avatar = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->avatar, 'avatars/', 'avatars/', 'json_error');
						$biolink_block->settings->background = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->background, 'backgrounds/', 'backgrounds/', 'json_error');
                        $biolink_block->settings->video_file = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->video_file, 'files/', 'files/', 'json_error');
						break;

					case 'vcard':
						$biolink_block->settings->vcard_avatar = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->vcard_avatar, 'avatars/', 'avatars/', 'json_error');
						$biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_thumbnail_images/', 'block_thumbnail_images/', 'json_error');
						break;

					case 'image':
					case 'image_grid':
						$biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_images/', 'block_images/', 'json_error');
						break;

                    case 'image_comparison':
                        $biolink_block->settings->before_image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->before_image, 'block_images/', 'block_images/', 'json_error');
                        $biolink_block->settings->after_image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->after_image, 'block_images/', 'block_images/', 'json_error');
                        break;

					case 'heading':
						$biolink_block->settings->verified_location = '';
						break;

					case 'image_slider':

						$biolink_block->settings->items = (array) $biolink_block->settings->items;

						foreach($biolink_block->settings->items as $key => $item) {
							$biolink_block->settings->items[$key]->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->items[$key]->image, 'block_images/', 'block_images/', 'json_error');
						}

						break;

					default:
						$biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_thumbnail_images/', 'block_thumbnail_images/', 'json_error');
						break;
				}

				/* Database query */
				db()->insert('biolinks_blocks', [
					'user_id' => $this->user->user_id,
					'link_id' => $link_id,
					'type' => $biolink_block->type,
					'location_url' => $biolink_block->location_url,
					'settings' => json_encode($biolink_block->settings),
					'order' => $biolink_block->order,
					'start_date' => $biolink_block->start_date,
					'end_date' => $biolink_block->end_date,
					'is_enabled' => $biolink_block->is_enabled,
					'datetime' => get_date(),
				]);
			}

			(new \Altum\Models\User())->hydrate_biolink_from_user_data($this->user->user_id, $link_id);
		}

		/* Clear the cache */
		cache()->deleteItem($type . '_links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_new) {
            fire_and_forget('post', settings()->webhooks->link_new, [
                'user_id' => $this->user->user_id,
                'link_id' => $link_id,
                'domain_id' => $domain_id,
                'url' => $url,
                'full_url' => $domain_id ? $domains[$domain_id]->url . $url : SITE_URL . $url,
                'type' => $type,
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.create2'), 'success', ['url' => url('link/' . $link_id . ($this->user->preferences->links_auto_copy_link ? '?auto_copy_link=true' : ''))]);
	}

	private function create_file() {
		if(!settings()->links->files_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$_POST['url'] = !empty($_POST['url']) && $this->user->plan_settings->custom_url ? get_slug($_POST['url'], '-', false) : null;

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Check for the plan limit */
		$user_total_files = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = 'file'")->fetch_object()->total;
		if($this->user->plan_settings->files_limit != -1 && $user_total_files >= $this->user->plan_settings->files_limit) {
			Response::json(l('global.info_message.plan_feature_limit'), 'error');
		}

		/* Check if custom domain is set */
        $domain_id = 0;
        $domains = [];

        if(isset($_POST['domain_id'])) {
            /* Get domains */
            $domains = (new Domain())->get_available_domains_by_user($this->user);

            if(isset($domains[$_POST['domain_id']])) {
                $domain_id = $domains[$_POST['domain_id']]->domain_id;
            }
        }

		/* File upload */
		$db_file = \Altum\Uploads::process_upload(null, 'files', 'file', 'file_remove', settings()->links->file_size_limit, 'json_error');

		/* Check for duplicate url if needed */
		if($_POST['url']) {
			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}
		}

		/* Start the creation process */
		$url = $_POST['url'] ?? mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
		$type = 'file';
		$settings = json_encode([
			'file' => $db_file,
			'force_download_is_enabled' => false,
			'password' => null,
			'sensitive_content' => false,
			'clicks_limit' => null,
			'expiration_url' => null,
		]);

		/* Generate random url if not specified */
		while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
		}

		$this->check_url($_POST['url']);

		/* Insert to database */
		$link_id = db()->insert('links', [
			'user_id' => $this->user->user_id,
			'domain_id' => $domain_id,
			'type' => $type,
			'url' => $url,
			'settings' => $settings,
			'datetime' => get_date(),
			'email_reports_last_datetime' => get_date(),
		]);

		/* Clear the cache */
		cache()->deleteItem($type . '_links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_new) {
            fire_and_forget('post', settings()->webhooks->link_new, [
                'user_id' => $this->user->user_id,
                'link_id' => $link_id,
                'domain_id' => $domain_id,
                'url' => $url,
                'full_url' => $domain_id ? $domains[$domain_id]->url . $url : SITE_URL . $url,
                'type' => $type,
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.create2'), 'success', ['url' => url('link/' . $link_id . ($this->user->preferences->links_auto_copy_link ? '?auto_copy_link=true' : ''))]);
	}

	private function create_vcard() {
		if(!settings()->links->vcards_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$_POST['url'] = !empty($_POST['url']) && $this->user->plan_settings->custom_url ? get_slug($_POST['url'], '-', false) : null;

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Check if custom domain is set */
        $domain_id = 0;
        $domains = [];

        if(isset($_POST['domain_id'])) {
            /* Get domains */
            $domains = (new Domain())->get_available_domains_by_user($this->user);

            if(isset($domains[$_POST['domain_id']])) {
                $domain_id = $domains[$_POST['domain_id']]->domain_id;
            }
        }

		/* Check for the plan limit */
		$user_total_vcards = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = 'vcard'")->fetch_object()->total;
		if($this->user->plan_settings->vcards_limit != -1 && $user_total_vcards >= $this->user->plan_settings->vcards_limit) {
			Response::json(l('global.info_message.plan_feature_limit'), 'error');
		}

		/* Check for duplicate url if needed */
		if($_POST['url']) {
			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}
		}

		/* Start the creation process */
		$url = $_POST['url'] ?? mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
		$type = 'vcard';
		$settings = json_encode([
			'password' => null,
			'sensitive_content' => false,
			'clicks_limit' => null,
			'expiration_url' => null,
			'vcard_avatar' => null,
			'vcard_first_name' => null,
			'vcard_last_name' => null,
			'vcard_email' => null,
			'vcard_url' => null,
			'vcard_company' => null,
			'vcard_job_title' => null,
			'vcard_birthday' => null,
			'vcard_street' => null,
			'vcard_city' => null,
			'vcard_zip' => null,
			'vcard_region' => null,
			'vcard_country' => null,
			'vcard_note' => null,
			'vcard_socials' => [],
			'vcard_phone_numbers' => [],
		]);

		/* Generate random url if not specified */
		while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
		}

		$this->check_url($_POST['url']);

		/* Insert to database */
		$link_id = db()->insert('links', [
			'user_id' => $this->user->user_id,
			'domain_id' => $domain_id,
			'type' => $type,
			'url' => $url,
			'settings' => $settings,
			'datetime' => get_date(),
			'email_reports_last_datetime' => get_date(),
		]);

		/* Clear the cache */
		cache()->deleteItem($type . '_links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_new) {
            fire_and_forget('post', settings()->webhooks->link_new, [
                'user_id' => $this->user->user_id,
                'link_id' => $link_id,
                'domain_id' => $domain_id,
                'url' => $url,
                'full_url' => $domain_id ? $domains[$domain_id]->url . $url : SITE_URL . $url,
                'type' => $type,
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.create2'), 'success', ['url' => url('link/' . $link_id . ($this->user->preferences->links_auto_copy_link ? '?auto_copy_link=true' : ''))]);
	}

	private function create_event() {
		if(!settings()->links->events_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$_POST['url'] = !empty($_POST['url']) && $this->user->plan_settings->custom_url ? get_slug($_POST['url'], '-', false) : null;

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Check if custom domain is set */
        $domain_id = 0;
        $domains = [];

        if(isset($_POST['domain_id'])) {
            /* Get domains */
            $domains = (new Domain())->get_available_domains_by_user($this->user);

            if(isset($domains[$_POST['domain_id']])) {
                $domain_id = $domains[$_POST['domain_id']]->domain_id;
            }
        }

		/* Check for the plan limit */
		$user_total_events = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = 'event'")->fetch_object()->total;
		if($this->user->plan_settings->events_limit != -1 && $user_total_events >= $this->user->plan_settings->events_limit) {
			Response::json(l('global.info_message.plan_feature_limit'), 'error');
		}

		/* Check for duplicate url if needed */
		if($_POST['url']) {
			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}
		}

		/* Start the creation process */
		$url = $_POST['url'] ?? mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
		$type = 'event';
		$settings = json_encode([
			'password' => null,
			'sensitive_content' => false,
			'clicks_limit' => null,
			'expiration_url' => null,
			'event_name' => null,
			'event_note' => null,
			'event_url' => null,
			'event_location' => null,
			'event_start_datetime' => null,
			'event_end_datetime' => null,
			'event_first_alert_datetime' => null,
			'event_second_alert_datetime' => null,
			'event_timezone' => $this->user->timezone,
		]);

		/* Generate random url if not specified */
		while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
		}

		$this->check_url($_POST['url']);

		/* Insert to database */
		$link_id = db()->insert('links', [
			'user_id' => $this->user->user_id,
			'domain_id' => $domain_id,
			'type' => $type,
			'url' => $url,
			'settings' => $settings,
			'datetime' => get_date(),
			'email_reports_last_datetime' => get_date(),
		]);

		/* Clear the cache */
		cache()->deleteItem($type . '_links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_new) {
            fire_and_forget('post', settings()->webhooks->link_new, [
                'user_id' => $this->user->user_id,
                'link_id' => $link_id,
                'domain_id' => $domain_id,
                'url' => $url,
                'full_url' => $domain_id ? $domains[$domain_id]->url . $url : SITE_URL . $url,
                'type' => $type,
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.create2'), 'success', ['url' => url('link/' . $link_id . ($this->user->preferences->links_auto_copy_link ? '?auto_copy_link=true' : ''))]);
	}

	private function create_static() {
		/* Make sure feature is enabled */
		if(!settings()->links->static_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		/* Sanitize custom URL if provided */
		$_POST['url'] = !empty($_POST['url']) && $this->user->plan_settings->custom_url ? get_slug($_POST['url'], '-', false) : null;

		/* Check main domain availability */
		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Enforce user static files limit */
		$user_total_files = database()->query("
			SELECT COUNT(*) AS `total`
			FROM `links`
			WHERE `user_id` = {$this->user->user_id}
			AND `type` = 'static'
		")->fetch_object()->total;

		if($this->user->plan_settings->static_limit != -1 && $user_total_files >= $this->user->plan_settings->static_limit) {
			Response::json(l('global.info_message.plan_feature_limit'), 'error');
		}

        /* Check if custom domain is set */
        $domain_id = 0;
        $domains = [];

        if(isset($_POST['domain_id'])) {
            /* Get domains */
            $domains = (new Domain())->get_available_domains_by_user($this->user);

            if(isset($domains[$_POST['domain_id']])) {
                $domain_id = $domains[$_POST['domain_id']]->domain_id;
            }
        }

		/* Check duplicate URL */
		if($_POST['url']) {
			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}
		}

		/* Handle file upload */
		if(!empty($_FILES['file']['name'])) {
			$file_extension = mb_strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
			$file_temp = $_FILES['file']['tmp_name'];

			/* Increase execution limits for large files */
			set_time_limit(120);

			/* Upload error checks */
			if($_FILES['file']['error'] == UPLOAD_ERR_INI_SIZE) {
				Response::json(sprintf(l('global.error_message.file_size_limit'), get_max_upload()), 'error');
			}
			if($_FILES['file']['error'] && $_FILES['file']['error'] != UPLOAD_ERR_INI_SIZE) {
				Response::json(l('global.error_message.file_upload'), 'error');
			}

			/* Validate file type */
			if(!in_array($file_extension, \Altum\Uploads::get_whitelisted_file_extensions('static'))) {
				Response::json(l('global.error_message.invalid_file_type'), 'error');
			}

			/* Validate permissions */
			if(!\Altum\Plugin::is_active('offload') || (\Altum\Plugin::is_active('offload') && !settings()->offload->uploads_url)) {
				if(!is_writable(UPLOADS_PATH . \Altum\Uploads::get_path('static'))) {
					Response::json(sprintf(l('global.error_message.directory_not_writable'), UPLOADS_PATH . \Altum\Uploads::get_path('static')), 'error');
				}
			}

			/* Validate max file size */
			if(settings()->links->static_size_limit && $_FILES['file']['size'] > settings()->links->static_size_limit * 1000000) {
				Response::json(sprintf(l('global.error_message.file_size_limit'), settings()->links->static_size_limit), 'error');
			}
		}

		/* Create target folder */
		$static_folder_name = md5(uniqid('', true) . random_bytes(16));
		$base_folder = \Altum\Uploads::get_full_path('static') . $static_folder_name;
		mkdir($base_folder, 0777, true);

		/* Track files and folders */
		$files = [];
		$folders = [];

		/* If it's a single HTML file */
		if($file_extension == 'html') {
			move_uploaded_file($file_temp, $base_folder . '/index.html');
			$files[] = 'index.html';
		}

		/* If it's a zip, extract it */
		if($file_extension == 'zip') {
			$zip = new \ZipArchive;

			if($zip->open($file_temp) === true) {
				/* Create folders */
				$created_folders = [];
				for($i = 0; $i < $zip->numFiles; $i++) {
					$entry_name = $zip->getNameIndex($i);
					$entry_info = $zip->statIndex($i);

					if($entry_info['name'][strlen($entry_info['name'])-1] == '/' && !str_contains($entry_info['name'], '__MACOSX')) {
						$folder_path = $base_folder . '/' . $entry_info['name'];
						if(!in_array($folder_path, $created_folders)) {
							mkdir($folder_path, 0777, true);
							$created_folders[] = $folder_path;
							$folders[] = $entry_info['name'];
						}
					}
				}

				/* Secure against zip slip */
				$real_base_folder = realpath($base_folder);

				/* Extract files */
				for($i = 0; $i < $zip->numFiles; $i++) {
					$entry_name = $zip->getNameIndex($i);
					$entry_info = $zip->statIndex($i);
					$entry_extension = mb_strtolower(pathinfo($entry_name, PATHINFO_EXTENSION));

					/* Skip folders & __MACOSX junk */
					if($entry_info['name'][strlen($entry_info['name'])-1] == '/' || str_contains($entry_info['name'], '__MACOSX')) {
						continue;
					}

					/* Only allow whitelisted file types */
					if(in_array($entry_extension, \Altum\Uploads::$uploads['static']['inside_zip_whitelisted_file_extensions'])) {

						$target_file = $base_folder . '/' . $entry_info['name'];
						$real_target_file = realpath(dirname($target_file));

						/* Prevent zip slip attack */
						if(strpos($real_target_file, $real_base_folder) !== 0) {
							$zip->close();
							Response::json('Invalid file path detected inside zip.', 'error');
						}

						/* Extract file */
						copy('zip://' . $file_temp . '#' . $entry_name, $target_file);
						$files[] = $entry_info['name'];
					}

					/* Flush occasionally to keep response alive */
					if($i % 100 == 0) {
						flush();
						ob_flush();
					}
				}

				$zip->close();

				/* Remove temp file */
				unlink($file_temp);
			} else {
				Response::json(l('global.error_message.basic'), 'error');
			}
		}

		/* Create link */
		$url = $_POST['url'] ?? mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
		$type = 'static';
		$settings = json_encode([
			'static_folder' => $static_folder_name
		]);

		/* Generate unique URL */
		while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
		}

		$this->check_url($_POST['url']);

		/* Insert into database */
		$link_id = db()->insert('links', [
			'user_id' => $this->user->user_id,
			'domain_id' => $domain_id,
			'type' => $type,
			'url' => $url,
			'settings' => $settings,
			'datetime' => get_date(),
			'email_reports_last_datetime' => get_date(),
		]);

		/* Clear caches */
		cache()->deleteItem($type . '_links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links_total?user_id=' . $this->user->user_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_new) {
            fire_and_forget('post', settings()->webhooks->link_new, [
                'user_id' => $this->user->user_id,
                'link_id' => $link_id,
                'domain_id' => $domain_id,
                'url' => $url,
                'full_url' => $domain_id ? $domains[$domain_id]->url . $url : SITE_URL . $url,
                'type' => $type,
                'datetime' => get_date(),
            ], signature: true);
        }

		/* Return success */
		Response::json(l('global.success_message.create2'), 'success', [
			'url' => url('link/' . $link_id . ($this->user->preferences->links_auto_copy_link ? '?auto_copy_link=true' : ''))
		]);
	}

	private function update() {
		/* Team checks */
		if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.links')) {
			Response::json(l('global.info_message.team_no_access'), 'error');
		}

		if(empty($_POST)) {
			die();
		}

		/* Check for possible errors */
		if(!array_key_exists($_POST['type'], $this->links_types)) {
			die();
		}

		$this->{'update_' . $_POST['type']}();

	}

	private function update_link() {
		if(!settings()->links->shortener_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$_POST['link_id'] = (int) $_POST['link_id'];
		$_POST['project_id'] = empty($_POST['project_id']) ? null : (int) $_POST['project_id'];
		$_POST['url'] = !empty($_POST['url']) ? get_slug($_POST['url'], '-', false) : false;
		$_POST['location_url'] = get_url($_POST['location_url']);
		$_POST['schedule'] = (int) isset($_POST['schedule']);
		if($_POST['schedule'] && !empty($_POST['start_date']) && !empty($_POST['end_date']) && Date::validate($_POST['start_date'], 'Y-m-d H:i:s') && Date::validate($_POST['end_date'], 'Y-m-d H:i:s')) {
			$_POST['start_date'] = (new \DateTime($_POST['start_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
			$_POST['end_date'] = (new \DateTime($_POST['end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
		} else {
			$_POST['start_date'] = $_POST['end_date'] = null;
		}
		$_POST['expiration_url'] = get_url($_POST['expiration_url']);
		$_POST['clicks_limit'] = empty($_POST['clicks_limit']) ? null : (int) $_POST['clicks_limit'];
		$this->check_location_url($_POST['expiration_url'], true);
		$_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);
		$_POST['app_linking_is_enabled'] = (int) isset($_POST['app_linking_is_enabled']);
		$_POST['cloaking_is_enabled'] = (int) isset($_POST['cloaking_is_enabled']);
		$_POST['cloaking_title'] = input_clean($_POST['cloaking_title'], 70);
		$_POST['cloaking_meta_description'] = input_clean($_POST['cloaking_meta_description'], 160);
		$_POST['cloaking_custom_js'] = mb_substr(trim($_POST['cloaking_custom_js']), 0, 10000);

		/* Query parameters forwarding */
		$_POST['forward_query_parameters_is_enabled'] = (int) isset($_POST['forward_query_parameters_is_enabled']);

		/* UTM */
		$_POST['utm_medium'] = input_clean($_POST['utm_medium'], 128);
		$_POST['utm_source'] = input_clean($_POST['utm_source'], 128);
		$_POST['utm_campaign'] = input_clean($_POST['utm_campaign'], 128);

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Get domains */
		$domains = (new Domain())->get_available_domains_by_user($this->user);

		/* Check if custom domain is set */
		$domain_id = isset($domains[$_POST['domain_id']]) ? $_POST['domain_id'] : 0;

		/* Exclusivity check */
		$_POST['is_main_link'] = isset($_POST['is_main_link']) && $domain_id && $domains[$_POST['domain_id']]->type == 0;

		/* Existing pixels */
		$pixels = (new \Altum\Models\Pixel())->get_pixels($this->user->user_id);
		$_POST['pixels_ids'] = isset($_POST['pixels_ids']) ? array_map(
			'intval',
			array_filter($_POST['pixels_ids'], function($pixel_id) use($pixels) {
				return array_key_exists($pixel_id, $pixels);
			})
		) : [];
		$_POST['pixels_ids'] = json_encode($_POST['pixels_ids']);

		/* Check for any errors */
		$required_fields = ['location_url'];
		foreach($required_fields as $field) {
			if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
				Response::json(l('global.error_message.empty_fields'), 'error');
				break 1;
			}
		}

		$this->check_url($_POST['url']);

		$this->check_location_url($_POST['location_url']);

		if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
			die();
		}
		$link->settings = json_decode($link->settings ?? '');

		/* Cloaking */
		$link->settings->cloaking_favicon = \Altum\Uploads::process_upload($link->settings->cloaking_favicon, 'favicons', 'cloaking_favicon', 'cloaking_favicon_remove', settings()->links->favicon_size_limit, 'json_error');
		$link->settings->cloaking_opengraph = \Altum\Uploads::process_upload($link->settings->cloaking_opengraph, 'biolink_seo_image', 'cloaking_opengraph', 'cloaking_opengraph_remove', settings()->links->seo_image_size_limit, 'json_error');

		/* Existing projects */
		$projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);
		$_POST['project_id'] = !empty($_POST['project_id']) && array_key_exists($_POST['project_id'], $projects) ? (int) $_POST['project_id'] : null;

		/* Existing splash pages */
		$splash_pages = (new \Altum\Models\SplashPages())->get_splash_pages_by_user_id($this->user->user_id);
		$_POST['splash_page_id'] = !empty($_POST['splash_page_id']) && array_key_exists($_POST['splash_page_id'], $splash_pages) ? (int) $_POST['splash_page_id'] : null;

		/* Check for a password set */
		$_POST['password'] = !empty($_POST['qweasdzxc']) ?
			($_POST['qweasdzxc'] != $link->settings->password ? password_hash($_POST['qweasdzxc'], PASSWORD_DEFAULT) : $link->settings->password)
			: null;


		/* Check for duplicate url if needed */
		if($_POST['url'] && ($_POST['url'] != $link->url || $domain_id != $link->domain_id)) {

			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}

		}

		$url = $_POST['url'];

		if(empty($_POST['url'])) {
			/* Generate random url if not specified */
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));

			while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
			}

		}

		/* App linking check */
		$app_linking = [
			'ios_location_url' => null,
			'android_location_url' => null,
			'app' => null,
		];

		if($_POST['app_linking_is_enabled']) {
			$supported_apps = require APP_PATH . 'includes/app_linking.php';
			$app_linking_location_url = $_POST['location_url'];

			foreach($supported_apps as $app_key => $app) {
				foreach($app['formats'] as $format => $targets) {

					if(preg_match('/' . $targets['regex'] . '/', $app_linking_location_url, $match)) {

						/* Extract and normalize hostnames */
						$user_host = parse_url($app_linking_location_url, PHP_URL_HOST);
						$format_host = parse_url('https://' . str_replace('%s', 'placeholder', $format), PHP_URL_HOST);

						/* Remove www. and m. prefixes for more flexible comparison */
						$user_host = preg_replace('/^(www\.|m\.)/', '', $user_host);
						$format_host = preg_replace('/^(www\.|m\.)/', '', $format_host);

						/* Compare the normalized hosts */
						if($user_host === $format_host) {

							if(count($match) > 1) {
								array_shift($match);
								$app_linking['ios_location_url'] = vsprintf($targets['iOS'], $match);
								$app_linking['android_location_url'] = vsprintf($targets['Android'], $match);
								$app_linking['app'] = $app_key;
							}

							break 2;
						}
					}

				}
			}
		}

		/* Prepare the settings */
		$targeting_types = ['continent_code', 'country_code', 'city_name', 'device_type', 'browser_language', 'rotation', 'os_name', 'browser_name'];
		$_POST['targeting_type'] = in_array($_POST['targeting_type'], array_merge(['false'], $targeting_types)) ? query_clean($_POST['targeting_type']) : 'false';
		$_POST['http_status_code'] = in_array($_POST['http_status_code'], [301, 302, 307, 308]) ? (int) $_POST['http_status_code'] : 301;

		/* Get available notification handlers */
		$notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

		/* Notification handlers */
		$_POST['email_reports'] = array_map(
			'intval',
			array_filter($_POST['email_reports'] ?? [], function($notification_handler_id) use ($notification_handlers) {
				return array_key_exists($notification_handler_id, $notification_handlers);
			})
		);

		$settings = [
			'clicks_limit' => $_POST['clicks_limit'],
			'expiration_url' => $_POST['expiration_url'],
			'schedule' => $_POST['schedule'],
			'password' => $_POST['password'],
			'sensitive_content' => $_POST['sensitive_content'],
			'targeting_type' => $_POST['targeting_type'],
			'http_status_code' => $_POST['http_status_code'],

			/* Cloaking */
			'cloaking_is_enabled' => $_POST['cloaking_is_enabled'],
			'cloaking_title' => $_POST['cloaking_title'],
			'cloaking_meta_description' => $_POST['cloaking_meta_description'],
			'cloaking_custom_js' => $_POST['cloaking_custom_js'],
			'cloaking_favicon' => $link->settings->cloaking_favicon,
			'cloaking_opengraph' => $link->settings->cloaking_opengraph,

			/* App linking */
			'app_linking_is_enabled' => $_POST['app_linking_is_enabled'],
			'app_linking' => $app_linking,

			/* Forward query parameters */
			'forward_query_parameters_is_enabled' => $_POST['forward_query_parameters_is_enabled'],

			/* UTM */
			'utm' => [
				'source' => $_POST['utm_source'],
				'medium' => $_POST['utm_medium'],
				'campaign' => $_POST['utm_campaign'],
			]
		];

		/* Process the targeting */
		foreach($targeting_types as $targeting_type) {
			${'targeting_' . $targeting_type} = [];

			if(isset($_POST['targeting_' . $targeting_type . '_key'])) {
				foreach($_POST['targeting_' . $targeting_type . '_key'] as $key => $value) {
					if(empty(trim($_POST['targeting_' . $targeting_type . '_value'][$key]))) continue;

					${'targeting_' . $targeting_type}[] = [
						'key' => trim(query_clean($value)),
						'value' => get_url($_POST['targeting_' . $targeting_type . '_value'][$key]),
					];
				}

				$settings['targeting_' . $targeting_type] = ${'targeting_' . $targeting_type};
			}
		}

		$settings = json_encode($settings);

		db()->where('link_id', $_POST['link_id'])->update('links', [
			'project_id' => $_POST['project_id'],
			'email_reports' => json_encode($_POST['email_reports']),
			'email_reports_count' => count($_POST['email_reports']),
			'email_reports_last_datetime' => !$link->email_reports_last_datetime ? get_date() : $link->email_reports_last_datetime,
			'splash_page_id' => $_POST['splash_page_id'],
			'domain_id' => $domain_id,
			'pixels_ids' => $_POST['pixels_ids'],
			'url' => $url,
			'location_url' => $_POST['location_url'],
			'start_date' => $_POST['start_date'],
			'end_date' => $_POST['end_date'],
			'settings' => $settings,
			'last_datetime' => get_date(),
		]);

		$this->process_is_main_link_domain($link, $domains);

		$url = $domain_id && $_POST['is_main_link'] ? '' : $url;

		/* Clear the cache */
		cache()->deleteItem('biolink_blocks?link_id=' . $link->link_id);
		cache()->deleteItem('link?link_id=' . $link->link_id);
		cache()->deleteItemsByTag('link_id=' . $link->link_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_update) {
            fire_and_forget('post', settings()->webhooks->link_update, [
                'user_id' => $this->user->user_id,
                'link_id' => $_POST['link_id'],
                'domain_id' => $domain_id,
                'url' => $url,
                'location_url' => $_POST['location_url'],
                'full_url' => $domain_id ? $domains[$domain_id]->url . ($domains[$domain_id]->link_id == $_POST['link_id'] ? null : $url) : SITE_URL . $url,
                'type' => 'link',
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.update2'), 'success', ['url' => $url, 'app_linking' => $app_linking]);
	}

	private function apply_ai_color_bundle() {
		if(!settings()->links->biolinks_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$link_id = (int) ($_POST['link_id'] ?? 0);
		$theme_key = input_clean($_POST['theme_key'] ?? '', 64);

		if(!$link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'settings', 'additional', 'biolink_theme_id', 'last_datetime'])) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$theme_pack = $this->get_ai_theme_pack_for_link($link, $theme_key);

		if(
			$theme_pack['background_color'] === ''
			&& ($theme_pack['gradient_start'] === '' || $theme_pack['gradient_end'] === '')
			&& $theme_pack['text_color'] === ''
		) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$settings = $this->normalize_json_to_array($link->settings ?? null);
		$additional = $this->normalize_json_to_array($link->additional ?? null);
		$additional = $this->ensure_ai_bundle_backup($link, $additional);
		$plan_scope = $this->get_ai_final_plan_scope($additional, (int) $link->link_id, $this->user->preferences ?? null);

		if(($theme_pack['background_mode'] ?? 'color') === 'gradient' && $theme_pack['gradient_start'] !== '' && $theme_pack['gradient_end'] !== '') {
			$settings['background_type'] = 'gradient';
			$settings['background_color_one'] = $theme_pack['gradient_start'];
			$settings['background_color_two'] = $theme_pack['gradient_end'];
		} else {
			$settings['background_type'] = 'color';
			$settings['background'] = $theme_pack['background_color'] ?: ($settings['background'] ?? '#0F172A');
			$settings['background_color_one'] = null;
			$settings['background_color_two'] = null;
		}

		if($theme_pack['text_color'] !== '') {
			$settings['text_color'] = $theme_pack['text_color'];
		}

		if($theme_pack['font'] !== '' && (($theme_pack['font'] === 'default') || !empty($this->user->plan_settings->fonts))) {
			$settings['font'] = $theme_pack['font'];
		}

		if(!empty($theme_pack['font_size'])) {
			$settings['font_size'] = (int) $theme_pack['font_size'];
		}

		if($theme_pack['width'] !== '') {
			$settings['width'] = $theme_pack['width'];
		}

		if($theme_pack['block_spacing'] !== '') {
			$settings['block_spacing'] = $theme_pack['block_spacing'];
		}

		if($theme_pack['hover_animation'] !== '') {
			$settings['hover_animation'] = $theme_pack['hover_animation'];
		}

		$themable_types = $this->get_themable_biolink_block_types();

		if(!empty($themable_types)) {
			$themable_types_sql = "'" . implode("','", array_map(static function($type) {
				return str_replace("'", "\\'", (string) $type);
			}, $themable_types)) . "'";
			$result = database()->query("SELECT `biolink_block_id`, `type`, `settings` FROM `biolinks_blocks` WHERE `link_id` = {$link->link_id} AND `type` IN ({$themable_types_sql})");

			while($biolink_block = $result->fetch_object()) {
				if(
					!empty($plan_scope['has_plan'])
					&& !in_array((int) ($biolink_block->biolink_block_id ?? 0), (array) ($plan_scope['included_block_ids'] ?? []), true)
				) {
					continue;
				}

				$block_settings = $this->normalize_json_to_array($biolink_block->settings ?? null);
				$updated_block_settings = $this->apply_ai_secondary_theme_to_block_settings($block_settings, (string) ($biolink_block->type ?? ''), $theme_pack);

				if(json_encode($updated_block_settings) === json_encode($block_settings)) {
					continue;
				}

				db()->where('biolink_block_id', (int) $biolink_block->biolink_block_id)->update('biolinks_blocks', [
					'settings' => json_encode($updated_block_settings),
				]);
			}
		}

		$block_catalog = $this->get_ai_editor_block_catalog((int) $link->link_id);
		$raw_primary_block_plan = $this->normalize_ai_primary_block_plan($additional['fcc_ai_primary_block_plan'] ?? []);
		$raw_missing_block_recommendations = $this->filter_ai_missing_block_recommendations_by_plan_scope(
			$this->build_ai_missing_block_recommendations($additional, $block_catalog, $raw_primary_block_plan),
			$additional,
			(int) $link->link_id,
			$this->user->preferences ?? null
		);
		$primary_block_plan = $this->get_effective_ai_primary_block_plan($additional, $block_catalog, $raw_missing_block_recommendations, (int) $link->link_id, $this->user->preferences ?? null);
		$missing_block_recommendations = $this->filter_ai_missing_block_recommendations_by_plan_scope(
			$this->build_ai_missing_block_recommendations($additional, $block_catalog, $primary_block_plan),
			$additional,
			(int) $link->link_id,
			$this->user->preferences ?? null
		);
		$block_patch_pack = $this->filter_ai_block_patch_pack_by_plan_scope(
			$this->normalize_ai_block_patch_pack($additional['fcc_ai_block_patch_pack'] ?? []),
			$plan_scope
		);
		$primary_block_id = $this->resolve_ai_primary_block_id((int) $link->link_id, $primary_block_plan);
		$primary_updated = false;

		if($primary_block_id > 0 && !empty($primary_block_plan['apply_theme_emphasis'])) {
			$primary_block = db()->where('biolink_block_id', $primary_block_id)->where('link_id', $link->link_id)->getOne('biolinks_blocks', ['biolink_block_id', 'settings']);

			if($primary_block) {
				$block_settings = $this->normalize_json_to_array($primary_block->settings ?? null);
				$updated_block_settings = $this->apply_ai_primary_theme_to_block_settings($block_settings, $theme_pack);

				if(json_encode($updated_block_settings) !== json_encode($block_settings)) {
					db()->where('biolink_block_id', $primary_block_id)->update('biolinks_blocks', [
						'settings' => json_encode($updated_block_settings),
					]);
					$primary_updated = true;
				}
			}
		}

		foreach($block_patch_pack as $patch) {
			$target_block_id = (int) ($patch['block_id'] ?? 0);

			if($target_block_id <= 0 && !empty($patch['block_type']) && !empty($primary_block_plan['block_type']) && $patch['block_type'] === $primary_block_plan['block_type']) {
				$target_block_id = $primary_block_id;
			}

			if($target_block_id <= 0) {
				continue;
			}

			$target_block = db()->where('biolink_block_id', $target_block_id)->where('link_id', $link->link_id)->getOne('biolinks_blocks', ['biolink_block_id', 'settings']);

			if(!$target_block) {
				continue;
			}

			$block_settings = $this->normalize_json_to_array($target_block->settings ?? null);
			$updated_block_settings = array_merge($block_settings, (array) ($patch['settings'] ?? []));

			if(json_encode($updated_block_settings) === json_encode($block_settings)) {
				continue;
			}

			db()->where('biolink_block_id', $target_block_id)->update('biolinks_blocks', [
				'settings' => json_encode($updated_block_settings),
			]);
			$primary_updated = true;
		}

		$applied_at = get_date();
		$apply_state = $this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? null);
		$apply_state['recommended_at'] = $apply_state['recommended_at'] ?? $applied_at;
		$apply_state['applied_at'] = $applied_at;
		$apply_state['last_applied_theme_key'] = $theme_key !== '' ? $theme_key : (string) ($additional['fcc_ai_theme_library_key'] ?? '');
		if($primary_updated) {
			$apply_state['primary_applied_at'] = $applied_at;
		}
		$additional['fcc_ai_theme_apply_state'] = $apply_state;
		$additional = $this->sync_ai_evolution_apply_state($additional, [
			'theme_applied_at' => $applied_at,
			'primary_applied_at' => $primary_updated ? $applied_at : ($apply_state['primary_applied_at'] ?? null),
			'theme_key' => (string) $apply_state['last_applied_theme_key'],
		]);

		if($theme_key !== '') {
			$additional['fcc_ai_theme_library_key'] = $theme_key;
		}

		db()->where('link_id', $link->link_id)->where('user_id', $this->user->user_id)->update('links', [
			'biolink_theme_id' => null,
			'settings' => json_encode($settings),
			'additional' => $this->prepare_biolink_additional_for_storage($additional),
			'last_datetime' => $applied_at,
		]);

		$this->clear_biolink_cache((int) $link->link_id);

		Response::json(l('link.settings.ai_color_bundle_apply_success'), 'success');
	}

	private function apply_ai_theme_pack() {
		if(!settings()->links->biolinks_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$link_id = (int) ($_POST['link_id'] ?? 0);
		$theme_key = input_clean($_POST['theme_key'] ?? '', 64);

		if(!$link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'settings', 'additional', 'biolink_theme_id', 'last_datetime'])) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$theme_pack = $this->get_ai_theme_pack_for_link($link, $theme_key);

		if(
			$theme_pack['background_color'] === ''
			&& ($theme_pack['gradient_start'] === '' || $theme_pack['gradient_end'] === '')
			&& $theme_pack['text_color'] === ''
		) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$settings = $this->normalize_json_to_array($link->settings ?? null);
		$additional = $this->normalize_json_to_array($link->additional ?? null);
		if(($theme_pack['background_mode'] ?? 'color') === 'gradient' && $theme_pack['gradient_start'] !== '' && $theme_pack['gradient_end'] !== '') {
			$settings['background_type'] = 'gradient';
			$settings['background_color_one'] = $theme_pack['gradient_start'];
			$settings['background_color_two'] = $theme_pack['gradient_end'];
		} else {
			$settings['background_type'] = 'color';
			$settings['background'] = $theme_pack['background_color'] ?: ($settings['background'] ?? '#0F172A');
			$settings['background_color_one'] = null;
			$settings['background_color_two'] = null;
		}

		if($theme_pack['text_color'] !== '') {
			$settings['text_color'] = $theme_pack['text_color'];
		}

		if($theme_pack['font'] !== '' && (($theme_pack['font'] === 'default') || !empty($this->user->plan_settings->fonts))) {
			$settings['font'] = $theme_pack['font'];
		}

		if(!empty($theme_pack['font_size'])) {
			$settings['font_size'] = (int) $theme_pack['font_size'];
		}

		if($theme_pack['width'] !== '') {
			$settings['width'] = $theme_pack['width'];
		}

		if($theme_pack['block_spacing'] !== '') {
			$settings['block_spacing'] = $theme_pack['block_spacing'];
		}

		if($theme_pack['hover_animation'] !== '') {
			$settings['hover_animation'] = $theme_pack['hover_animation'];
		}

		$themable_types = $this->get_themable_biolink_block_types();

		if(!empty($themable_types)) {
			$themable_types_sql = "'" . implode("','", array_map(static function($type) {
				return str_replace("'", "\\'", (string) $type);
			}, $themable_types)) . "'";
			$result = database()->query("SELECT `biolink_block_id`, `type`, `settings` FROM `biolinks_blocks` WHERE `link_id` = {$link->link_id} AND `type` IN ({$themable_types_sql})");

			while($biolink_block = $result->fetch_object()) {
				$block_settings = $this->normalize_json_to_array($biolink_block->settings ?? null);
				$updated_block_settings = $this->apply_ai_secondary_theme_to_block_settings($block_settings, (string) ($biolink_block->type ?? ''), $theme_pack);

				if(json_encode($updated_block_settings) === json_encode($block_settings)) {
					continue;
				}

				db()->where('biolink_block_id', (int) $biolink_block->biolink_block_id)->update('biolinks_blocks', [
					'settings' => json_encode($updated_block_settings),
				]);
			}
		}

		$apply_state = $this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? null);
		$apply_state['recommended_at'] = $apply_state['recommended_at'] ?? get_date();
		$apply_state['applied_at'] = get_date();
		$apply_state['last_applied_theme_key'] = $theme_key !== '' ? $theme_key : (string) ($additional['fcc_ai_theme_library_key'] ?? '');
		$additional['fcc_ai_theme_apply_state'] = $apply_state;
		$additional = $this->sync_ai_evolution_apply_state($additional, [
			'theme_applied_at' => $apply_state['applied_at'],
			'theme_key' => (string) $apply_state['last_applied_theme_key'],
		]);
		if($theme_key !== '') {
			$additional['fcc_ai_theme_library_key'] = $theme_key;
		}

		db()->where('link_id', $link->link_id)->where('user_id', $this->user->user_id)->update('links', [
			'biolink_theme_id' => null,
			'settings' => json_encode($settings),
			'additional' => $this->prepare_biolink_additional_for_storage($additional),
			'last_datetime' => get_date(),
		]);

		$this->clear_biolink_cache((int) $link->link_id);

		Response::json(l('link.settings.ai_theme_apply_success'), 'success');
	}

	private function apply_ai_primary_block_focus() {
		if(!settings()->links->biolinks_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$link_id = (int) ($_POST['link_id'] ?? 0);

		if(!$link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'settings', 'additional', 'biolink_theme_id', 'last_datetime'])) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$payload = $this->get_ai_primary_focus_payload($link);
		$theme_pack = $payload['theme_pack'];
		$primary_block_plan = $payload['primary_block_plan'];
		$block_patch_pack = $payload['block_patch_pack'];
		$additional = $this->ensure_ai_bundle_backup($link, $payload['additional']);

		$primary_block_id = $this->resolve_ai_primary_block_id((int) $link->link_id, $primary_block_plan);
		$has_applied_anything = false;

		if($primary_block_id > 0 && !empty($primary_block_plan['apply_theme_emphasis'])) {
			$primary_block = db()->where('biolink_block_id', $primary_block_id)->where('link_id', $link->link_id)->getOne('biolinks_blocks', ['biolink_block_id', 'settings']);

			if($primary_block) {
				$block_settings = $this->normalize_json_to_array($primary_block->settings ?? null);
				$updated_block_settings = $this->apply_ai_primary_theme_to_block_settings($block_settings, $theme_pack);

				if(json_encode($updated_block_settings) !== json_encode($block_settings)) {
					db()->where('biolink_block_id', $primary_block_id)->update('biolinks_blocks', [
						'settings' => json_encode($updated_block_settings),
					]);
					$has_applied_anything = true;
				}
			}
		}

		foreach($block_patch_pack as $patch) {
			$target_block_id = (int) ($patch['block_id'] ?? 0);

			if($target_block_id <= 0 && !empty($patch['block_type']) && !empty($primary_block_plan['block_type']) && $patch['block_type'] === $primary_block_plan['block_type']) {
				$target_block_id = $primary_block_id;
			}

			if($target_block_id <= 0) {
				continue;
			}

			$target_block = db()->where('biolink_block_id', $target_block_id)->where('link_id', $link->link_id)->getOne('biolinks_blocks', ['biolink_block_id', 'settings']);

			if(!$target_block) {
				continue;
			}

			$block_settings = $this->normalize_json_to_array($target_block->settings ?? null);
			$updated_block_settings = array_merge($block_settings, (array) ($patch['settings'] ?? []));

			if(json_encode($updated_block_settings) === json_encode($block_settings)) {
				continue;
			}

			db()->where('biolink_block_id', $target_block_id)->update('biolinks_blocks', [
				'settings' => json_encode($updated_block_settings),
			]);
			$has_applied_anything = true;
		}

		if(!$has_applied_anything) {
			Response::json(l('link.settings.ai_theme_primary_missing'), 'error');
		}

		$apply_state = $this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? null);
		$apply_state['primary_applied_at'] = get_date();
		$additional['fcc_ai_theme_apply_state'] = $apply_state;
		$additional = $this->sync_ai_evolution_apply_state($additional, [
			'primary_applied_at' => $apply_state['primary_applied_at'],
		]);

		db()->where('link_id', $link->link_id)->where('user_id', $this->user->user_id)->update('links', [
			'additional' => $this->prepare_biolink_additional_for_storage($additional),
			'last_datetime' => get_date(),
		]);

		$this->clear_biolink_cache((int) $link->link_id);

		Response::json(l('link.settings.ai_theme_primary_apply_success'), 'success');
	}

	private function add_ai_recommended_block() {
		if(!settings()->links->biolinks_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$link_id = (int) ($_POST['link_id'] ?? 0);
		$recommendation_key = trim((string) ($_POST['recommendation_key'] ?? ''));
		$requested_block_type = trim((string) ($_POST['block_type'] ?? ''));

		if(!$link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'biolink_theme_id', 'additional'])) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$additional = $this->normalize_json_to_array($link->additional ?? null);
		$additional = $this->ensure_ai_bundle_backup($link, $additional);
		$primary_block_plan = $this->normalize_ai_primary_block_plan($additional['fcc_ai_primary_block_plan'] ?? []);
		$block_patch_pack = $this->normalize_ai_block_patch_pack($additional['fcc_ai_block_patch_pack'] ?? []);
		$block_catalog = $this->get_ai_editor_block_catalog((int) $link->link_id);
		$recommendations = $this->build_ai_missing_block_recommendations($additional, $block_catalog, $primary_block_plan);
		$recommendation = $this->resolve_ai_missing_recommendation($recommendations, $recommendation_key, $requested_block_type);

		if(!$recommendation) {
			Response::json(l('link.settings.ai_missing_blocks_not_ready'), 'error');
		}

		$block_type = trim((string) ($recommendation['block_type'] ?? ''));

		if($block_type === '') {
			Response::json(l('link.settings.ai_missing_blocks_not_ready'), 'error');
		}

		$this->validate_biolink_block_access_for_current_plan($block_type);

		if(!in_array($block_type, ['lead_funnel', 'heading', 'paragraph', 'modal_text', 'custom_html_whatsapp', 'custom_html_chatbot', 'custom_html_chatbot_pets', 'youtube', 'vimeo', 'link_forever_product'], true) && !($block_type === 'link_discount' && !empty(($recommendation['seed_settings']['location_url'] ?? '')))) {
			Response::json(l('link.settings.ai_missing_blocks_manual_only'), 'error');
		}

		$existing_block = $this->get_ai_reactivatable_missing_recommendation_block($recommendation, $block_catalog);
		$new_block_id = (int) ($existing_block['block_id'] ?? 0);

		if($new_block_id <= 0) {
			$total_biolink_blocks = (int) database()->query("SELECT COUNT(*) AS `total` FROM `biolinks_blocks` WHERE `user_id` = {$this->user->user_id} AND `link_id` = {$link->link_id}")->fetch_object()->total;
			if($this->user->plan_settings->biolink_blocks_limit != -1 && $total_biolink_blocks >= $this->user->plan_settings->biolink_blocks_limit) {
				Response::json(l('global.info_message.plan_feature_limit'), 'error');
			}
		}

		if($new_block_id > 0) {
			$this->apply_ai_seed_settings_to_existing_block($new_block_id, (string) ($existing_block['type'] ?? $block_type), (array) ($recommendation['seed_settings'] ?? []));
			db()->where('biolink_block_id', $new_block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
				'is_enabled' => 1,
			]);
		} else {
			$new_block_id = $this->create_ai_seeded_biolink_block($link, $block_type, (array) ($recommendation['seed_settings'] ?? []));
		}

		if($new_block_id <= 0) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$this->apply_ai_block_patch_pack_to_block($new_block_id, $block_type, $block_patch_pack);

		$this->insert_biolink_block_after((int) $link->link_id, $new_block_id, (int) ($recommendation['insert_after_block_id'] ?? 0));
		db()->where('link_id', $link->link_id)->where('user_id', $this->user->user_id)->update('links', [
			'additional' => $this->prepare_biolink_additional_for_storage($additional),
			'last_datetime' => get_date(),
		]);
		$this->clear_biolink_cache((int) $link->link_id);

		Response::json(
			sprintf(l('link.settings.ai_missing_blocks_add_success'), (string) (($recommendation['label'] ?? '') ?: l('link.biolink.blocks.' . $block_type))),
			'success',
			[
				'url' => url('link/' . $link->link_id . '?tab=blocks&biolink_block_id=' . $new_block_id),
				'biolink_block_id' => $new_block_id,
				'block_type' => $block_type,
			]
			);
	}

	private function apply_ai_block_bundle() {
		if(!settings()->links->biolinks_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$link_id = (int) ($_POST['link_id'] ?? 0);

		if(!$link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'settings', 'additional', 'biolink_theme_id', 'last_datetime'])) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$additional = $this->normalize_json_to_array($link->additional ?? null);
		$additional = $this->ensure_ai_bundle_backup($link, $additional);
		$block_catalog = $this->get_ai_editor_block_catalog((int) $link->link_id);
		$plan_scope = $this->get_ai_final_plan_scope($additional, (int) $link->link_id, $this->user->preferences ?? null);
		$raw_primary_block_plan = $this->normalize_ai_primary_block_plan($additional['fcc_ai_primary_block_plan'] ?? []);
		$raw_copy_suggestions = $this->normalize_ai_copy_suggestions($additional['fcc_ai_copy_suggestions'] ?? []);
		$missing_block_recommendations = $this->filter_ai_missing_block_recommendations_by_plan_scope(
			$this->build_ai_missing_block_recommendations($additional, $block_catalog, $raw_primary_block_plan),
			$additional,
			(int) $link->link_id,
			$this->user->preferences ?? null
		);
		$primary_block_plan = $this->get_effective_ai_primary_block_plan($additional, $block_catalog, $missing_block_recommendations, (int) $link->link_id, $this->user->preferences ?? null);
		$missing_block_recommendations = $this->filter_ai_missing_block_recommendations_by_plan_scope(
			$this->build_ai_missing_block_recommendations($additional, $block_catalog, $primary_block_plan),
			$additional,
			(int) $link->link_id,
			$this->user->preferences ?? null
		);
		$block_patch_pack = $this->filter_ai_block_patch_pack_by_plan_scope(
			$this->normalize_ai_block_patch_pack($additional['fcc_ai_block_patch_pack'] ?? []),
			$plan_scope
		);
		$existing_chatbot_block = $this->get_first_ai_catalog_block_by_types($block_catalog, $this->get_ai_chatbot_block_types(), false);
		$has_disabled_chatbot = !empty($existing_chatbot_block['block_id']) && (int) ($existing_chatbot_block['is_enabled'] ?? 0) !== 1;

		if(empty($missing_block_recommendations) && empty($raw_copy_suggestions) && empty($this->get_ai_ideal_block_order($additional, (int) $link->link_id, $this->user->preferences ?? null)) && !$has_disabled_chatbot) {
			Response::json(l('link.settings.ai_block_bundle_apply_missing'), 'error');
		}

		$current_total_blocks = (int) database()->query("SELECT COUNT(*) AS `total` FROM `biolinks_blocks` WHERE `user_id` = {$this->user->user_id} AND `link_id` = {$link->link_id}")->fetch_object()->total;
		$created_blocks = 0;
		$revived_blocks = 0;
		$skipped_labels = [];

		foreach($missing_block_recommendations as $recommendation) {
			$block_type = trim((string) ($recommendation['block_type'] ?? ''));
			$label = (string) (($recommendation['label'] ?? '') ?: l('link.biolink.blocks.' . $block_type));

			if($block_type === '' || empty($recommendation['supports_auto_add'])) {
				$skipped_labels[] = $label;
				continue;
			}

			$this->validate_biolink_block_access_for_current_plan($block_type);

			$existing_block = $this->get_ai_reactivatable_missing_recommendation_block($recommendation, $block_catalog);
			$new_block_id = (int) ($existing_block['block_id'] ?? 0);
			$was_existing_block = $new_block_id > 0;

			if(!$was_existing_block && $this->user->plan_settings->biolink_blocks_limit != -1 && $current_total_blocks >= $this->user->plan_settings->biolink_blocks_limit) {
				$skipped_labels[] = $label;
				continue;
			}

			if($new_block_id > 0) {
				$this->apply_ai_seed_settings_to_existing_block($new_block_id, (string) ($existing_block['type'] ?? $block_type), (array) ($recommendation['seed_settings'] ?? []));
				db()->where('biolink_block_id', $new_block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
					'is_enabled' => 1,
				]);
			} else {
				$new_block_id = $this->create_ai_seeded_biolink_block($link, $block_type, (array) ($recommendation['seed_settings'] ?? []), false);
			}

			if($new_block_id <= 0) {
				$skipped_labels[] = $label;
				continue;
			}

			$this->apply_ai_block_patch_pack_to_block($new_block_id, $block_type, $block_patch_pack);
			$this->insert_biolink_block_after((int) $link->link_id, $new_block_id, (int) ($recommendation['insert_after_block_id'] ?? 0));
			if($was_existing_block) {
				$revived_blocks++;
			} else {
				$current_total_blocks++;
				$created_blocks++;
			}

			$block_catalog = $this->get_ai_editor_block_catalog((int) $link->link_id);
		}

		$block_catalog = $this->get_ai_editor_block_catalog((int) $link->link_id);
		$plan_scope = $this->get_ai_final_plan_scope($additional, (int) $link->link_id, $this->user->preferences ?? null);
		$raw_copy_suggestions = $this->filter_ai_copy_suggestions_by_plan_scope($raw_copy_suggestions, $plan_scope);
		$copy_summary = $this->apply_ai_copy_suggestions_to_blocks((int) $link->link_id, $raw_copy_suggestions, $block_catalog);
		$block_catalog = $this->get_ai_editor_block_catalog((int) $link->link_id);
		$plan_sequence = $this->get_ai_final_plan_sequence_blocks($additional, $block_catalog, (int) $link->link_id, $this->user->preferences ?? null);

		if(empty($plan_sequence)) {
			$plan_sequence = $this->get_ai_plan_sequence_blocks($additional, $block_catalog, (int) $link->link_id, $this->user->preferences ?? null);
		}

		$primary_block_id = $this->resolve_ai_primary_block_id((int) $link->link_id, $primary_block_plan);
		$full_snapshot = $this->get_biolink_blocks_full_snapshot((int) $link->link_id);
		$layout_summary = $this->apply_ai_plan_sequence_to_blocks((int) $link->link_id, $full_snapshot, $plan_sequence, $primary_block_id, $additional);
		$total_updates = $created_blocks + $revived_blocks + (int) ($copy_summary['updated_blocks'] ?? 0) + (int) ($layout_summary['updated_blocks'] ?? 0);

		if($total_updates <= 0) {
			Response::json(l('link.settings.ai_block_bundle_apply_missing'), 'error');
		}

		$applied_at = get_date();
		$apply_state = $this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? null);
		$apply_state['layout_applied_at'] = $applied_at;
		$additional['fcc_ai_theme_apply_state'] = $apply_state;
		$additional = $this->sync_ai_evolution_apply_state($additional, [
			'layout_applied_at' => $applied_at,
			'layout_reverted_at' => null,
			'layout_summary' => [
				'reordered_blocks' => (int) ($layout_summary['reordered_blocks'] ?? 0),
				'hidden_blocks' => (int) ($layout_summary['hidden_blocks'] ?? 0),
				'updated_blocks' => $total_updates,
			],
			'layout_rollback_summary' => [
				'restored_blocks' => 0,
				're_enabled_blocks' => 0,
			],
		]);

		db()->where('link_id', $link->link_id)->where('user_id', $this->user->user_id)->update('links', [
			'additional' => $this->prepare_biolink_additional_for_storage($additional),
			'last_datetime' => $applied_at,
		]);

		$this->clear_biolink_cache((int) $link->link_id);

		$message = sprintf(
			l('link.settings.ai_block_bundle_apply_success'),
			nr($created_blocks),
			nr((int) ($copy_summary['updated_blocks'] ?? 0)),
			nr((int) ($layout_summary['hidden_blocks'] ?? 0))
		);

		if(!empty($skipped_labels)) {
			$message .= ' ' . sprintf(
				l('link.settings.ai_block_bundle_apply_skipped'),
				htmlspecialchars(implode(', ', array_slice(array_unique(array_filter($skipped_labels)), 0, 4)), ENT_QUOTES, 'UTF-8')
			);
		}

		Response::json($message, 'success');
	}

	private function apply_ai_layout_actions() {
		if(!settings()->links->biolinks_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$link_id = (int) ($_POST['link_id'] ?? 0);

		if(!$link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'settings', 'additional', 'biolink_theme_id', 'last_datetime'])) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$payload = $this->get_ai_layout_payload($link);
		$layout_actions = $payload['layout_actions'];
		$primary_block_plan = $payload['primary_block_plan'];
		$additional = $this->ensure_ai_bundle_backup($link, $payload['additional']);
		$editor_catalog = $this->get_ai_editor_block_catalog((int) $link->link_id);
		$missing_block_recommendations = $this->build_ai_missing_block_recommendations($additional, $editor_catalog, $primary_block_plan);
		$blocking_missing_block_recommendations = array_values(array_filter($missing_block_recommendations, function($item): bool {
			return !$this->is_ai_chatbot_block_type((string) ($item['block_type'] ?? ''));
		}));

		if(empty($layout_actions)) {
			Response::json(l('link.settings.ai_layout_apply_missing'), 'error');
		}

		if(!empty($blocking_missing_block_recommendations)) {
			$first_missing = $blocking_missing_block_recommendations[0];
			$missing_label = (string) (($first_missing['label'] ?? '') ?: l('link.biolink.blocks.' . ($first_missing['block_type'] ?? '')));
			Response::json(sprintf(l('link.settings.ai_layout_apply_missing_block_first'), $missing_label), 'error');
		}

		$blocks = $this->get_biolink_blocks_full_snapshot((int) $link->link_id);

		if(empty($blocks)) {
			Response::json(l('link.settings.ai_layout_apply_missing'), 'error');
		}

		$additional['fcc_ai_layout_backup'] = $this->build_ai_layout_backup((int) $link->link_id, $additional, $blocks);
		unset($additional['fcc_ai_layout_last_restore']);

		$primary_block_id = $this->resolve_ai_primary_block_id((int) $link->link_id, $primary_block_plan);
		$enabled_blocks = array_values(array_filter($blocks, static fn($block) => (int) ($block['is_enabled'] ?? 0) === 1));
		$disabled_blocks = array_values(array_filter($blocks, static fn($block) => (int) ($block['is_enabled'] ?? 0) !== 1));
		$current_enabled_ids = array_map(static fn($block) => (int) ($block['biolink_block_id'] ?? 0), $enabled_blocks);
		$groups = [
			'keep_top' => [],
			'after_primary' => [],
			'move_up' => [],
			'move_down' => [],
			'consider_remove' => [],
		];
		$hide_ids = [];
		$used_ids = [];
		$protected_core_block_ids = $this->get_ai_protected_core_block_ids($blocks);
		$protected_signal_block_ids = $this->get_ai_protected_signal_block_ids($additional, (int) $link->link_id, $blocks);
		$protected_block_ids = array_values(array_unique(array_merge($protected_core_block_ids, $protected_signal_block_ids)));

		foreach($layout_actions as $action) {
			$target_block_id = $this->resolve_ai_layout_action_block_id($blocks, $action);

			if($target_block_id <= 0) {
				continue;
			}

			if(in_array($target_block_id, $protected_core_block_ids, true) || $this->is_ai_chatbot_block_type((string) ($action['block_type'] ?? ''))) {
				continue;
			}

			$action_key = (string) ($action['action'] ?? '');

			if(in_array($target_block_id, $protected_signal_block_ids, true) && in_array($action_key, ['hide_for_now', 'consider_remove'], true)) {
				continue;
			}

			if($action_key === 'hide_for_now') {
				$hide_ids[$target_block_id] = true;
				continue;
			}

			if(in_array($target_block_id, $used_ids, true)) {
				continue;
			}

			switch($action_key) {
				case 'keep_top':
					$groups['keep_top'][] = $target_block_id;
					$used_ids[] = $target_block_id;
					break;

				case 'keep_after_primary':
					$groups['after_primary'][] = $target_block_id;
					$used_ids[] = $target_block_id;
					break;

				case 'move_up':
					$groups['move_up'][] = $target_block_id;
					$used_ids[] = $target_block_id;
					break;

				case 'move_down':
					$groups['move_down'][] = $target_block_id;
					$used_ids[] = $target_block_id;
					break;

				case 'consider_remove':
					$groups['consider_remove'][] = $target_block_id;
					$used_ids[] = $target_block_id;
					break;
			}
		}

		$hide_ids = array_values(array_filter(array_keys($hide_ids), static fn($block_id): bool => !in_array((int) $block_id, $protected_block_ids, true)));
		$enabled_ids_without_hidden = array_values(array_filter($current_enabled_ids, static fn($block_id) => !in_array($block_id, $hide_ids, true)));
		$ordered_groups = [];

		foreach(['keep_top', 'after_primary', 'move_up', 'move_down', 'consider_remove'] as $group_key) {
			$ordered_groups[$group_key] = array_values(array_filter($groups[$group_key], static function($block_id) use ($enabled_ids_without_hidden) {
				return in_array($block_id, $enabled_ids_without_hidden, true);
			}));
		}

		$ordered_enabled_ids = [];

		foreach($ordered_groups['keep_top'] as $block_id) {
			if(!in_array($block_id, $ordered_enabled_ids, true)) {
				$ordered_enabled_ids[] = $block_id;
			}
		}

		if($primary_block_id > 0 && in_array($primary_block_id, $enabled_ids_without_hidden, true) && !in_array($primary_block_id, $ordered_enabled_ids, true)) {
			$ordered_enabled_ids[] = $primary_block_id;
		}

		foreach(['after_primary', 'move_up'] as $group_key) {
			foreach($ordered_groups[$group_key] as $block_id) {
				if(!in_array($block_id, $ordered_enabled_ids, true)) {
					$ordered_enabled_ids[] = $block_id;
				}
			}
		}

		foreach($enabled_ids_without_hidden as $block_id) {
			if(!in_array($block_id, $ordered_enabled_ids, true) && !in_array($block_id, $ordered_groups['move_down'], true) && !in_array($block_id, $ordered_groups['consider_remove'], true)) {
				$ordered_enabled_ids[] = $block_id;
			}
		}

		foreach($protected_block_ids as $block_id) {
			if(!in_array($block_id, $ordered_enabled_ids, true)) {
				$ordered_enabled_ids[] = $block_id;
			}
		}

		foreach(['move_down', 'consider_remove'] as $group_key) {
			foreach($ordered_groups[$group_key] as $block_id) {
				if(!in_array($block_id, $ordered_enabled_ids, true)) {
					$ordered_enabled_ids[] = $block_id;
				}
			}
		}

		$disabled_ids = array_map(static fn($block) => (int) ($block['biolink_block_id'] ?? 0), $disabled_blocks);
		$new_hidden_ids = array_values(array_filter($hide_ids, fn($block_id) => in_array($block_id, $current_enabled_ids, true) && !in_array($block_id, $protected_block_ids, true)));
		$ordered_all_ids = array_merge($ordered_enabled_ids, $disabled_ids, $new_hidden_ids);
		$ordered_all_ids = array_values(array_unique(array_filter($ordered_all_ids)));

		foreach($blocks as $block) {
			$block_id = (int) ($block['biolink_block_id'] ?? 0);

			if($block_id > 0 && !in_array($block_id, $ordered_all_ids, true)) {
				$ordered_all_ids[] = $block_id;
			}
		}

		$block_map = [];
		foreach($blocks as $block) {
			$block_map[(int) ($block['biolink_block_id'] ?? 0)] = $block;
		}

		$reordered_blocks = 0;
		$updated_blocks = 0;

		foreach($ordered_all_ids as $index => $block_id) {
			$new_order = $index + 1;
			$current_order = (int) ($block_map[$block_id]['order'] ?? 0);

			if($current_order !== $new_order) {
				db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
					'order' => $new_order,
				]);
				$reordered_blocks++;
				$updated_blocks++;
			}
		}

		$hidden_blocks = 0;
		foreach($new_hidden_ids as $block_id) {
			if(((int) ($block_map[$block_id]['is_enabled'] ?? 0)) !== 1) {
				continue;
			}

			db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
				'is_enabled' => 0,
			]);
			$hidden_blocks++;
			$updated_blocks++;
		}

		foreach($protected_block_ids as $block_id) {
			if(((int) ($block_map[$block_id]['is_enabled'] ?? 0)) === 1) {
				continue;
			}

			db()->where('biolink_block_id', $block_id)->where('user_id', $this->user->user_id)->update('biolinks_blocks', [
				'is_enabled' => 1,
			]);
			$updated_blocks++;
		}

		if(!$updated_blocks) {
			Response::json(l('link.settings.ai_layout_apply_missing'), 'error');
		}

		$apply_state = $this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? null);
		$apply_state['layout_applied_at'] = get_date();
		$additional['fcc_ai_theme_apply_state'] = $apply_state;
		$additional = $this->sync_ai_evolution_apply_state($additional, [
			'layout_applied_at' => $apply_state['layout_applied_at'],
			'layout_reverted_at' => null,
			'layout_summary' => [
				'reordered_blocks' => $reordered_blocks,
				'hidden_blocks' => $hidden_blocks,
				'updated_blocks' => $updated_blocks,
			],
			'layout_rollback_summary' => [
				'restored_blocks' => 0,
				're_enabled_blocks' => 0,
			],
		]);

		db()->where('link_id', $link->link_id)->where('user_id', $this->user->user_id)->update('links', [
			'additional' => $this->prepare_biolink_additional_for_storage($additional),
			'last_datetime' => get_date(),
		]);

		$this->clear_biolink_cache((int) $link->link_id);

		Response::json(sprintf(l('link.settings.ai_layout_apply_success'), nr($reordered_blocks), nr($hidden_blocks)), 'success');
	}

	private function restore_ai_layout_backup() {
		if(!settings()->links->biolinks_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$link_id = (int) ($_POST['link_id'] ?? 0);

		if(!$link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'additional'])) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$additional = $this->normalize_json_to_array($link->additional ?? null);
		$backup = $this->normalize_json_to_array($additional['fcc_ai_layout_backup'] ?? []);
		$backup_blocks = array_values(array_filter((array) ($backup['blocks'] ?? []), 'is_array'));

		if(empty($backup_blocks)) {
			Response::json(l('link.settings.ai_layout_restore_missing'), 'error');
		}

		$current_blocks = $this->get_biolink_blocks_layout_snapshot((int) $link->link_id);
		$restore_summary = $this->restore_biolink_blocks_layout_snapshot($current_blocks, $backup_blocks);

		if(empty($restore_summary['restored_blocks'])) {
			Response::json(l('link.settings.ai_layout_restore_missing'), 'error');
		}

		unset($additional['fcc_ai_layout_backup']);
		$additional['fcc_ai_layout_last_restore'] = [
			'restored_at' => get_date(),
			'restored_blocks' => (int) ($restore_summary['restored_blocks'] ?? 0),
			're_enabled_blocks' => (int) ($restore_summary['re_enabled_blocks'] ?? 0),
		];
		$additional = $this->sync_ai_evolution_apply_state($additional, [
			'layout_applied_at' => null,
			'layout_reverted_at' => (string) $additional['fcc_ai_layout_last_restore']['restored_at'],
			'layout_summary' => [
				'reordered_blocks' => 0,
				'hidden_blocks' => 0,
				'updated_blocks' => 0,
			],
			'layout_rollback_summary' => [
				'restored_blocks' => (int) ($restore_summary['restored_blocks'] ?? 0),
				're_enabled_blocks' => (int) ($restore_summary['re_enabled_blocks'] ?? 0),
			],
		]);

		db()->where('link_id', $link->link_id)->where('user_id', $this->user->user_id)->update('links', [
			'additional' => $this->prepare_biolink_additional_for_storage($additional),
			'last_datetime' => get_date(),
		]);

		$this->clear_biolink_cache((int) $link->link_id);

		Response::json(sprintf(l('link.settings.ai_layout_restore_success'), nr((int) ($restore_summary['restored_blocks'] ?? 0)), nr((int) ($restore_summary['re_enabled_blocks'] ?? 0))), 'success');
	}

	private function restore_ai_bundle_backup() {
		if(!settings()->links->biolinks_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$link_id = (int) ($_POST['link_id'] ?? 0);

		if(!$link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'settings', 'additional', 'biolink_theme_id'])) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$additional = $this->normalize_json_to_array($link->additional ?? null);
		$current_review_key = $this->get_active_ai_bundle_review_key($additional);
		$backup = $this->normalize_json_to_array($additional['fcc_ai_bundle_backup'] ?? []);

		if(!$this->is_ai_bundle_backup_usable($backup, $current_review_key)) {
			$backup = $this->get_ai_bundle_baseline_backup($additional, $current_review_key);
		}

		$backup_blocks = array_values(array_filter((array) ($backup['blocks'] ?? []), 'is_array'));

		if(empty($backup_blocks) || empty($backup['captured_at'])) {
			Response::json(l('link.settings.ai_bundle_restore_missing'), 'error');
		}

		$current_settings = $this->normalize_json_to_array($link->settings ?? null);
		$backup_settings = $this->normalize_json_to_array($backup['link_settings'] ?? []);
		$backup_theme_id = (int) ($backup['biolink_theme_id'] ?? 0);
		$current_blocks = $this->get_biolink_blocks_full_snapshot((int) $link->link_id);
		$restore_summary = $this->restore_biolink_blocks_full_snapshot($current_blocks, $backup_blocks);
		$settings_changed = json_encode($current_settings) !== json_encode($backup_settings);
		$theme_changed = (int) ($link->biolink_theme_id ?? 0) !== $backup_theme_id;

		unset($additional['fcc_ai_bundle_backup'], $additional['fcc_ai_layout_backup'], $additional['fcc_ai_layout_last_restore']);
		$restored_at = get_date();
		$additional['fcc_ai_bundle_last_restore'] = [
			'restored_at' => $restored_at,
			'restored_blocks' => (int) ($restore_summary['restored_blocks'] ?? 0),
			're_enabled_blocks' => (int) ($restore_summary['re_enabled_blocks'] ?? 0),
			'hidden_new_blocks' => (int) ($restore_summary['hidden_new_blocks'] ?? 0),
		];
		$additional = $this->sync_ai_evolution_apply_state($additional, [
			'theme_applied_at' => null,
			'primary_applied_at' => null,
			'layout_applied_at' => null,
			'layout_reverted_at' => $restored_at,
			'layout_summary' => [
				'reordered_blocks' => 0,
				'hidden_blocks' => 0,
				'updated_blocks' => 0,
			],
			'layout_rollback_summary' => [
				'restored_blocks' => (int) ($restore_summary['restored_blocks'] ?? 0),
				're_enabled_blocks' => (int) ($restore_summary['re_enabled_blocks'] ?? 0),
			],
		]);

		if(
			!$settings_changed
			&& !$theme_changed
			&& (int) ($restore_summary['restored_blocks'] ?? 0) === 0
			&& (int) ($restore_summary['hidden_new_blocks'] ?? 0) === 0
		) {
			Response::json(l('link.settings.ai_bundle_restore_missing'), 'error');
		}

		db()->where('link_id', $link->link_id)->where('user_id', $this->user->user_id)->update('links', [
			'biolink_theme_id' => $backup_theme_id ?: null,
			'settings' => json_encode($backup_settings),
			'additional' => $this->prepare_biolink_additional_for_storage($additional),
			'last_datetime' => $restored_at,
		]);

		$this->clear_biolink_cache((int) $link->link_id);

		Response::json(
			sprintf(
				l('link.settings.ai_bundle_restore_success'),
				nr((int) ($restore_summary['restored_blocks'] ?? 0)),
				nr((int) ($restore_summary['hidden_new_blocks'] ?? 0))
			),
			'success'
		);
	}

	private function update_biolink() {
		if(!settings()->links->biolinks_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$_POST['project_id'] = empty($_POST['project_id']) ? null : (int) $_POST['project_id'];
		$_POST['url'] = !empty($_POST['url']) ? get_slug($_POST['url'], '-', false) : false;

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Get domains */
		$domains = (new Domain())->get_available_domains_by_user($this->user);

		/* Check if custom domain is set */
		$domain_id = isset($domains[$_POST['domain_id']]) ? $_POST['domain_id'] : 0;

		/* Exclusivity check */
		$_POST['is_main_link'] = isset($_POST['is_main_link']) && $domain_id && $domains[$_POST['domain_id']]->type == 0;

		/* Check for any errors */
		if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
			die();
		}

		$main_biolink_id = (int) (fc_get_user_main_biolink_id((int) $this->user->user_id) ?? 0);
		$is_locked_main_biolink = $main_biolink_id > 0 && $main_biolink_id === (int) $link->link_id;

		if($is_locked_main_biolink) {
			$domain_id = (int) ($link->domain_id ?? 0);
			$_POST['url'] = (string) ($link->url ?? '');
			$_POST['is_main_link'] = $domain_id
				&& isset($domains[$domain_id])
				&& (int) ($domains[$domain_id]->type ?? 0) === 0
				&& (int) ($domains[$domain_id]->link_id ?? 0) === (int) $link->link_id;
		}

		/* Existing projects */
		$projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);
		$_POST['project_id'] = !empty($_POST['project_id']) && array_key_exists($_POST['project_id'], $projects) ? (int) $_POST['project_id'] : null;

		/* Existing splash pages */
		$splash_pages = (new \Altum\Models\SplashPages())->get_splash_pages_by_user_id($this->user->user_id);
		$_POST['splash_page_id'] = !empty($_POST['splash_page_id']) && array_key_exists($_POST['splash_page_id'], $splash_pages) ? (int) $_POST['splash_page_id'] : null;

		$link->settings = json_decode($link->settings ?? '');

		/* Get available themes */
		$biolinks_themes = (new BiolinksThemes())->get_biolinks_themes();
		$_POST['biolink_theme_id'] = isset($_POST['biolink_theme_id']) && array_key_exists($_POST['biolink_theme_id'], $biolinks_themes) ? (int) $_POST['biolink_theme_id'] : null;

		/* Make sure theme is accessible via plan */
		$_POST['biolink_theme_id'] = $_POST['biolink_theme_id'] && in_array($_POST['biolink_theme_id'], $this->user->plan_settings->biolinks_themes ?? []) ? $_POST['biolink_theme_id'] : null;

		/* Existing pixels */
		$pixels = (new \Altum\Models\Pixel())->get_pixels($this->user->user_id);
		$_POST['pixels_ids'] = isset($_POST['pixels_ids']) ? array_map(
			'intval',
			array_filter($_POST['pixels_ids'], function($pixel_id) use($pixels) {
				return array_key_exists($pixel_id, $pixels);
			})
		) : [];
		$_POST['pixels_ids'] = json_encode($_POST['pixels_ids']);

		if($_POST['url'] == $link->url) {
			$url = $link->url;

			if($link->domain_id != $domain_id) {
				if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
					Response::json(l('link.error_message.url_exists'), 'error');
				}
			}

		} else {
			$url = $_POST['url'] ? $_POST['url'] : mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));

			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}

			/* Generate random url if not specified */
			while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
			}

			$this->check_url($_POST['url']);
		}

		/* Image uploads */
		$image_allowed_extensions = [
			'branded_button_icon' => \Altum\Uploads::get_whitelisted_file_extensions('branded_button_icon'),
			'pwa_icon' => \Altum\Uploads::get_whitelisted_file_extensions('app_icon'),
			'seo_image' => \Altum\Uploads::get_whitelisted_file_extensions('biolink_seo_image'),
			'favicon' => \Altum\Uploads::get_whitelisted_file_extensions('favicons'),
			'background' => \Altum\Uploads::get_whitelisted_file_extensions('biolink_background'),
		];
		$image = [
			'branded_button_icon' => !empty($_FILES['branded_button_icon']['name']) && !isset($_POST['branded_button_icon_remove']),
			'pwa_icon' => !empty($_FILES['pwa_icon']['name']) && !isset($_POST['pwa_icon_remove']),
			'seo_image' => !empty($_FILES['seo_image']['name']) && !isset($_POST['seo_image_remove']),
			'favicon' => !empty($_FILES['favicon']['name']) && !isset($_POST['favicon_remove']),
			'background' => !empty($_FILES['background']['name']) && !isset($_POST['background_remove']),
		];
		$image_upload_path = [
			'branded_button_icon' => \Altum\Uploads::get_path('branded_button_icon'),
			'pwa_icon' => \Altum\Uploads::get_path('app_icon'),
			'seo_image' => \Altum\Uploads::get_path('biolink_seo_image'),
			'favicon' => \Altum\Uploads::get_path('favicons'),
			'background' => \Altum\Uploads::get_path('biolink_background'),
		];
		$image_uploaded_file = [
			'branded_button_icon' => $link->settings->branded_button_icon,
			'pwa_icon' => $link->settings->pwa_icon,
			'seo_image' => $link->settings->seo->image,
			'favicon' => $link->settings->favicon,
		];
		$image_url = [
			'branded_button_icon' => null,
			'pwa_icon' => null,
			'seo_image' => null,
			'favicon' => null,
			'background' => null,
		];

		foreach(['favicon', 'seo_image', 'pwa_icon', 'branded_button_icon'] as $image_key) {
			if($image[$image_key]) {
				$file_name = $_FILES[$image_key]['name'];
				$file_extension = mb_strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
				$file_temp = $_FILES[$image_key]['tmp_name'];

				if($_FILES[$image_key]['error'] == UPLOAD_ERR_INI_SIZE) {
					Response::json(sprintf(l('global.error_message.file_size_limit'), settings()->links->{$image_key . '_size_limit'}), 'error');
				}

				if($_FILES[$image_key]['error'] && $_FILES[$image_key]['error'] != UPLOAD_ERR_INI_SIZE) {
					Response::json(l('global.error_message.file_upload'), 'error');
				}

				if(!in_array($file_extension, $image_allowed_extensions[$image_key])) {
					Response::json(l('global.error_message.invalid_file_type'), 'error');
				}

				if(!\Altum\Plugin::is_active('offload') || (\Altum\Plugin::is_active('offload') && !settings()->offload->uploads_url)) {
					if(!is_writable(UPLOADS_PATH . $image_upload_path[$image_key])) {
						Response::json(sprintf(l('global.error_message.directory_not_writable'), UPLOADS_PATH . $image_upload_path[$image_key]), 'error');
					}
				}

				if($_FILES[$image_key]['size'] > settings()->links->{$image_key . '_size_limit'} * 1000000) {
					Response::json(sprintf(l('global.error_message.file_size_limit'), settings()->links->{$image_key . '_size_limit'}), 'error');
				}

				/* Generate new name for image */
				$image_new_name = md5(uniqid('', true) . random_bytes(16)) . '.' . $file_extension;

				/* Try to compress the image */
				if(\Altum\Plugin::is_active('image-optimizer') && settings()->image_optimizer->is_enabled) {
					\Altum\Plugin\ImageOptimizer::optimize($file_temp, $image_new_name, $_FILES[$image_key]['name'], UPLOADS_PATH . $image_upload_path[$image_key]);
				}

				/* Sanitize SVG uploads */
				if($file_extension == 'svg') {
					$svg_sanitizer = new \enshrined\svgSanitize\Sanitizer();
					$dirty_svg = file_get_contents($file_temp);
					$clean_svg = $svg_sanitizer->sanitize($dirty_svg);
					file_put_contents($file_temp, $clean_svg);
				}

				/* Offload uploading */
				if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
					try {
						$s3 = new \Aws\S3\S3Client(get_aws_s3_config());

						/* Delete current image */
						$s3->deleteObject([
							'Bucket' => settings()->offload->storage_name,
							'Key' => 'uploads/' . $image_upload_path[$image_key] . $image_uploaded_file[$image_key],
						]);

						/* Upload image */
						$result = $s3->putObject([
							'Bucket' => settings()->offload->storage_name,
							'Key' => 'uploads/' . $image_upload_path[$image_key] . $image_new_name,
							'ContentType' => mime_content_type($file_temp),
							'SourceFile' => $file_temp,
							'ACL' => 'public-read'
						]);
					} catch (\Exception $exception) {
						Response::json($exception->getMessage(), 'error');
					}
				}

				/* Local uploading */
				else {
					/* Delete current image */
					if(!empty($image_uploaded_file[$image_key]) && file_exists(UPLOADS_PATH . $image_upload_path[$image_key] . $image_uploaded_file[$image_key])) {
						// unlink(UPLOADS_PATH . $image_upload_path[$image_key] . $image_uploaded_file[$image_key]);  /* Custom code */
					}

					/* Upload the original */
					move_uploaded_file($file_temp, UPLOADS_PATH . $image_upload_path[$image_key] . $image_new_name);
				}

				$image_uploaded_file[$image_key] = $image_new_name;
			}

			/* Check for the removal of the already uploaded file */
			if(isset($_POST[$image_key . '_remove'])) {

				/* Offload deleting */
				if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
					$s3 = new \Aws\S3\S3Client(get_aws_s3_config());
					$s3->deleteObject([
						'Bucket' => settings()->offload->storage_name,
						'Key' => 'uploads/' . $image_upload_path[$image_key] . $image_uploaded_file[$image_key],
					]);
				}

				/* Local deleting */
				else {
					/* Delete current file */
					if(!empty($image_uploaded_file[$image_key]) && file_exists(UPLOADS_PATH . $image_upload_path[$image_key] . $image_uploaded_file[$image_key])) {
						// unlink(UPLOADS_PATH . $image_upload_path[$image_key] . $image_uploaded_file[$image_key]); /* Custom code */
					}
				}

				$image_uploaded_file[$image_key] = null;
			}

			$image_url[$image_key] = $image_uploaded_file[$image_key] ? UPLOADS_FULL_URL . $image_upload_path[$image_key] . $image_uploaded_file[$image_key] : null;
		}

		$biolink_backgrounds = require APP_PATH . 'includes/biolink_backgrounds.php';
		$_POST['background_type'] = array_key_exists($_POST['background_type'], $biolink_backgrounds) ? $_POST['background_type'] : 'preset';
		$_POST['background_attachment'] = isset($_POST['background_attachment']) && in_array($_POST['background_attachment'], ['scroll', 'fixed']) ? $_POST['background_attachment'] : 'scroll';
		$_POST['background_blur'] = isset($_POST['background_blur']) && in_array((int) $_POST['background_attachment'], range(0, 30)) ? (int) $_POST['background_blur'] : 0;
		$_POST['background_brightness'] = isset($_POST['background_brightness']) && in_array((int) $_POST['background_attachment'], range(0, 150)) ? (int) $_POST['background_brightness'] : 0;

		switch($_POST['background_type']) {
			case 'preset':
			case 'preset_abstract':
				$background = array_key_exists($_POST['background'], $biolink_backgrounds[$_POST['background_type']]) ? $_POST['background'] : 'zero';
				break;

			case 'color':

				$background = !verify_hex_color($_POST['background']) ? '#000000' : $_POST['background'];

				break;

			case 'gradient':

				$background_color_one = !verify_hex_color($_POST['background_color_one']) ? '#000000' : $_POST['background_color_one'];
				$background_color_two = !verify_hex_color($_POST['background_color_two']) ? '#000000' : $_POST['background_color_two'];

				break;

			case 'image':

				/* Background processing */
				if($image['background']) {
					$background_file_extension = mb_strtolower(pathinfo($_FILES['background']['name'], PATHINFO_EXTENSION));
					$background_file_temp = $_FILES['background']['tmp_name'];

					if($_FILES['background']['error'] == UPLOAD_ERR_INI_SIZE) {
						Response::json(sprintf(l('global.error_message.file_size_limit'), settings()->links->background_size_limit), 'error');
					}

					if($_FILES['background']['error'] && $_FILES['background']['error'] != UPLOAD_ERR_INI_SIZE) {
						Response::json(l('global.error_message.file_upload'), 'error');
					}

					if(!is_writable(UPLOADS_PATH . $image_upload_path['background'])) {
						Response::json(sprintf(l('global.error_message.directory_not_writable'), UPLOADS_PATH . $image_upload_path['background']), 'error');
					}

					if(!in_array($background_file_extension, $image_allowed_extensions['background'])) {
						Response::json(l('global.error_message.invalid_file_type'), 'error');
					}

					if($_FILES['background']['size'] > settings()->links->background_size_limit * 1000000) {
						Response::json(sprintf(l('global.error_message.file_size_limit'), settings()->links->background_size_limit), 'error');
					}

					/* Generate new name */
					$background_new_name = md5(uniqid('', true) . random_bytes(16)) . '.' . $background_file_extension;

					/* Try to compress the image */
					if(\Altum\Plugin::is_active('image-optimizer') && settings()->image_optimizer->is_enabled) {
						\Altum\Plugin\ImageOptimizer::optimize($background_file_temp, $background_new_name, $_FILES['background']['name'], UPLOADS_PATH . $image_upload_path['background']);
					}

					/* Sanitize SVG uploads */
					if($background_file_extension == 'svg') {
						$svg_sanitizer = new \enshrined\svgSanitize\Sanitizer();
						$dirty_svg = file_get_contents($background_file_temp);
						$clean_svg = $svg_sanitizer->sanitize($dirty_svg);
						file_put_contents($background_file_temp, $clean_svg);
					}

					/* Offload uploading */
					if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
						try {
							$s3 = new \Aws\S3\S3Client(get_aws_s3_config());

							/* Delete current image */
							if(!$link->biolink_theme_id && is_string($link->settings->background)) {
								$s3->deleteObject([
									'Bucket' => settings()->offload->storage_name,
									'Key' => 'uploads/backgrounds/' . $link->settings->background,
								]);
							}

							/* Upload image */
							$result = $s3->putObject([
								'Bucket' => settings()->offload->storage_name,
								'Key' => 'uploads/backgrounds/' . $background_new_name,
								'ContentType' => mime_content_type($background_file_temp),
								'SourceFile' => $background_file_temp,
								'ACL' => 'public-read'
							]);
						} catch (\Exception $exception) {
							Response::json($exception->getMessage(), 'error');
						}
					}

					/* Local uploading */
					else {
						/* Delete current file */
						if(!$link->biolink_theme_id && is_string($link->settings->background) && !empty($link->settings->background) && file_exists(UPLOADS_PATH . $image_upload_path['background'] . $link->settings->background)) {
							// unlink(UPLOADS_PATH . $image_upload_path['background'] . $link->settings->background); /* Custom code */
						}

						/* Upload the original */
						move_uploaded_file($background_file_temp, UPLOADS_PATH . $image_upload_path['background'] . $background_new_name);
					}

					$background = $background_new_name;
				}

				break;
		}

		/* Delete existing background file if needed */
		if(
			$link->settings->background_type == 'image'
			&& (
                $image['background']
                || ($_POST['biolink_theme_id'] && $link->biolink_theme_id != $_POST['biolink_theme_id'])
                || $_POST['background_type'] != $link->settings->background_type
            )
			&& is_string($link->settings->background)
			&& !$link->biolink_theme_id
		) {
			/* Offload deleting */
			if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
				$s3 = new \Aws\S3\S3Client(get_aws_s3_config());
				$s3->deleteObject([
					'Bucket' => settings()->offload->storage_name,
					'Key' => 'uploads/backgrounds/' . $link->settings->background,
				]);
			}

			/* Local deleting */
			else {
				/* Delete current file */
				if(!empty($link->settings->background) && file_exists(UPLOADS_PATH . $image_upload_path['background'] . $link->settings->background)) {
					// unlink(UPLOADS_PATH . $image_upload_path['background'] . $link->settings->background); /* Custom code */
				}
			}
		}

		$_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#ffffff' : $_POST['text_color'];
		$_POST['display_branding'] = (int) isset($_POST['display_branding']);
		$_POST['verified_location'] = in_array($_POST['verified_location'], ['', 'top', 'bottom']) ? query_clean($_POST['verified_location']) : 'top';
		$_POST['branding_name'] = mb_substr(trim(query_clean($_POST['branding_name'])), 0, 128);
		$_POST['branding_url'] = get_url($_POST['branding_url']);
		$_POST['seo_block'] = (int) isset($_POST['seo_block']);
		$_POST['seo_title'] = trim(query_clean(mb_substr($_POST['seo_title'], 0, 70)));
		$_POST['seo_meta_description'] = trim(query_clean(mb_substr($_POST['seo_meta_description'], 0, 160)));
        $_POST['seo_meta_keywords'] = trim(query_clean(mb_substr($_POST['seo_meta_keywords'], 0, 160)));
        $_POST['language_code'] = array_key_exists($_POST['language_code'], get_locale_languages_array()) ? $_POST['language_code'] : \Altum\Language::$default_code;
		$_POST['utm_medium'] = input_clean($_POST['utm_medium'], 128);
		$_POST['utm_source'] = input_clean($_POST['utm_source'], 128);
		$_POST['password'] = !empty($_POST['qweasdzxc']) ?
			($_POST['qweasdzxc'] != $link->settings->password ? password_hash($_POST['qweasdzxc'], PASSWORD_DEFAULT) : $link->settings->password)
			: null;
		$_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);
		$_POST['custom_css'] = mb_substr(trim($_POST['custom_css']), 0, 10000);
		$_POST['custom_js'] = mb_substr(trim($_POST['custom_js']), 0, 10000);
		$_POST['leap_link'] = get_url($_POST['leap_link'] ?? null);
		$_POST['share_is_enabled'] = (int) isset($_POST['share_is_enabled']);
		$_POST['scroll_buttons_is_enabled'] = (int) isset($_POST['scroll_buttons_is_enabled']);
		$_POST['directory_is_enabled'] = (int) isset($_POST['directory_is_enabled']);
		$this->check_location_url($_POST['leap_link'], true);

		/* Make sure the font is ok */
		$_POST['font'] = !array_key_exists($_POST['font'], (array) settings()->links->biolinks_fonts) ? false : query_clean($_POST['font']);
		$_POST['font_size'] = (int) $_POST['font_size'] < 12 || (int) $_POST['font_size'] > 22 ? 16 : (int) $_POST['font_size'];

		/* Width */
		$_POST['width'] = isset($_POST['width']) && in_array($_POST['width'], [6, 8, 10, 12]) ? (int) $_POST['width'] : 8;

		/* Block spacing */
		$_POST['block_spacing'] = isset($_POST['block_spacing']) && in_array($_POST['block_spacing'], [1, 2, 3,]) ? (int) $_POST['block_spacing'] : 2;

		/* Link hover animation */
		$_POST['hover_animation'] = isset($_POST['hover_animation']) && in_array($_POST['hover_animation'], ['false', 'smooth', 'instant',]) ? input_clean($_POST['hover_animation']) : 'smooth';

		/* Service worker */
		if(settings()->links->sixsixpusher_is_enabled) {
			$service_worker = \Altum\Uploads::process_upload($link->settings->service_worker, 'service_workers', 'service_worker', 'service_worker_remove', settings()->links->sixsixpusher_service_worker_size_limit, 'json_error', force_local: true);
		}

		/* PWA generation */
		$_POST['pwa_is_enabled'] = (int) isset($_POST['pwa_is_enabled']);
		$_POST['pwa_display_install_bar'] = (int) isset($_POST['pwa_display_install_bar']);
		$_POST['pwa_display_install_bar_delay'] = max(1, (int) $_POST['pwa_display_install_bar_delay'] ?? 3);
		$_POST['pwa_theme_color'] = isset($_POST['pwa_theme_color']) && verify_hex_color($_POST['pwa_theme_color']) ? $_POST['pwa_theme_color'] : '#000000';

		if(\Altum\Plugin::is_active('pwa') && settings()->pwa->is_enabled && $this->user->plan_settings->custom_pwa_is_enabled && $_POST['pwa_is_enabled']) {
			$pwa_file_name = $link->settings->pwa_file_name ?? 'biolinks-' . md5(uniqid('', true) . random_bytes(16));

			$start_url = $domain_id ? $domains[$_POST['domain_id']]->scheme . $domains[$_POST['domain_id']]->host . '/' . ($_POST['is_main_link'] ? null : $_POST['url']) : SITE_URL . $_POST['url'];
			$scope_url = $start_url;

			/* Add UTM tracking params */
			$start_url = $start_url . '?' . http_build_query([
					'utm_source' => 'pwa',
					'utm_medium' => 'web-app',
					'utm_campaign' => 'install-or-pwa-launch',
				]);

			/* Generate the manifest file */
			$manifest = pwa_generate_manifest([
				'name' => $_POST['seo_title'] ?: $_POST['url'] . ' - ' . settings()->main->title,
				'short_name' => $_POST['url'],
				'description' => $_POST['seo_meta_description'] ?: $_POST['url'],
				'theme_color' => $_POST['pwa_theme_color'],
				'app_icon_url' => $image_uploaded_file['pwa_icon'] ? \Altum\Uploads::get_full_url('app_icon') . $image_uploaded_file['pwa_icon'] : (settings()->pwa->app_icon ? \Altum\Uploads::get_full_url('app_icon') . settings()->pwa->app_icon : null),
				'app_icon_maskable_url' => $image_uploaded_file['pwa_icon'] ? \Altum\Uploads::get_full_url('app_icon') . $image_uploaded_file['pwa_icon'] : (settings()->pwa->app_icon_maskable ? \Altum\Uploads::get_full_url('app_icon') . settings()->pwa->app_icon_maskable : null),
				'start_url' => $start_url,
				'scope' => $scope_url,
				'mobile_screenshots' => [],
				'desktop_screenshots' => [],
				'shortcuts' => [],
			]);
			pwa_save_manifest($manifest, $pwa_file_name);
		}

		/* Branded button */
		$_POST['branded_button_is_enabled'] = (int) isset($_POST['branded_button_is_enabled']);
		$_POST['branded_button_title'] = input_clean($_POST['branded_button_title'], 64);
		$_POST['branded_button_content'] = mb_substr(trim($_POST['branded_button_content']), 0, 10000);

		/* Set the new settings variable */
		$settings = [
			'service_worker' => $service_worker ?? null,

			'pwa_file_name' => $pwa_file_name ?? null,
			'pwa_is_enabled' => $_POST['pwa_is_enabled'],
			'pwa_display_install_bar' => $_POST['pwa_display_install_bar'],
			'pwa_display_install_bar_delay' => $_POST['pwa_display_install_bar_delay'],
			'pwa_theme_color' => $_POST['pwa_theme_color'],
			'pwa_icon' => $image_uploaded_file['pwa_icon'],

			'branded_button_is_enabled' => $_POST['branded_button_is_enabled'],
			'branded_button_icon' => $image_uploaded_file['branded_button_icon'],
			'branded_button_title' => $_POST['branded_button_title'],
			'branded_button_content' => $_POST['branded_button_content'],

			'verified_location' => $_POST['verified_location'],
			'background_type' => $_POST['background_type'],
			'background_attachment' => $_POST['background_attachment'],
			'background_blur' => $_POST['background_blur'],
			'background_brightness' => $_POST['background_brightness'],
			'background' => $background ?? $link->settings->background,
			'background_color_one' => $background_color_one ?? null,
			'background_color_two' => $background_color_two ?? null,
			'favicon' => $image_uploaded_file['favicon'],
			'text_color' => $_POST['text_color'],
			'display_branding' => $_POST['display_branding'],
			'branding' => [
				'name' => $_POST['branding_name'],
				'url' => $_POST['branding_url'],
			],
			'seo' => [
				'block' => $_POST['seo_block'],
				'title' => $_POST['seo_title'],
				'meta_description' => $_POST['seo_meta_description'],
				'meta_keywords' => $_POST['seo_meta_keywords'],
				'image' => $image_uploaded_file['seo_image'],
			],
			'utm' => [
				'medium' => $_POST['utm_medium'],
				'source' => $_POST['utm_source'],
			],
			'font' => $_POST['font'],
			'width' => $_POST['width'],
			'block_spacing' => $_POST['block_spacing'],
			'hover_animation' => $_POST['hover_animation'],
			'font_size' => $_POST['font_size'],
			'password' => $_POST['password'],
			'sensitive_content' => $_POST['sensitive_content'],
			'leap_link' => $_POST['leap_link'],
			'custom_css' => $_POST['custom_css'],
			'custom_js' => $_POST['custom_js'],
			'share_is_enabled' => $_POST['share_is_enabled'],
			'scroll_buttons_is_enabled' => $_POST['scroll_buttons_is_enabled'],
            'language_code' => $_POST['language_code'],
		];

		/* Check if we need to override defaults for a new theme */
		$additional = $link->additional ?? '';
		$link_additional_data = $this->normalize_json_to_array($link->additional ?? null);
		$theme_custom_backup = $this->normalize_json_to_array($link_additional_data['fcc_theme_custom_backup'] ?? null);
		$previous_biolink_theme_id = (int) ($link->biolink_theme_id ?? 0);
		$previous_biolink_theme = $previous_biolink_theme_id && isset($biolinks_themes[$previous_biolink_theme_id]) ? $biolinks_themes[$previous_biolink_theme_id] : null;
		$is_switching_to_new_theme = $_POST['biolink_theme_id'] && $previous_biolink_theme_id != (int) $_POST['biolink_theme_id'];
		$is_disabling_theme = !$_POST['biolink_theme_id'] && $previous_biolink_theme_id;

		if($is_disabling_theme) {
			if(!empty($theme_custom_backup)) {
				$settings = array_merge(
					$settings,
					array_intersect_key(
						(array) ($theme_custom_backup['settings'] ?? []),
						array_flip($this->get_biolink_theme_controlled_settings_keys())
					)
				);

				$additional = $this->prepare_biolink_additional_for_storage(
					$this->normalize_json_to_array($theme_custom_backup['additional'] ?? null)
				);

				$this->restore_themable_biolink_blocks_snapshot((array) ($theme_custom_backup['blocks'] ?? []));
			} else {
				$settings = $this->get_biolink_theme_fallback_settings_after_disable($settings, $previous_biolink_theme);
				$this->remove_biolink_theme_styles_from_existing_blocks($link->link_id, $previous_biolink_theme);

				unset($link_additional_data['custom_css'], $link_additional_data['custom_js'], $link_additional_data['fcc_theme_custom_backup']);
				$additional = $this->prepare_biolink_additional_for_storage($link_additional_data);
			}
		}

		if($is_switching_to_new_theme) {
			$biolink_theme = $biolinks_themes[$_POST['biolink_theme_id']];
			$theme_custom_backup = !empty($theme_custom_backup) ? $theme_custom_backup : $this->build_biolink_theme_custom_backup($link);

			/* Save settings for biolink page */
			$settings = array_merge($settings, (array) $biolink_theme->settings->biolink);

			/* Save the additional settings */
			$theme_additional = $this->normalize_json_to_array($biolink_theme->settings->additional ?? null);
			$theme_additional['fcc_theme_custom_backup'] = $theme_custom_backup;
			$additional = $this->prepare_biolink_additional_for_storage($theme_additional);

			/* Save settings for all existing blocks */
			$biolink_blocks = require APP_PATH . 'includes/biolink_blocks.php';
			$themable_blocks = array_keys(array_filter($biolink_blocks, fn($block) => !empty($block['themable'])));
			$themable_blocks_sql = "'" . implode('\', \'', $themable_blocks) . "'";

			$biolink_blocks_result = database()->query("SELECT `biolink_block_id`, `type`, `settings` FROM `biolinks_blocks` WHERE `link_id` = {$link->link_id} AND `type` IN ({$themable_blocks_sql})");
			while($biolink_block = $biolink_blocks_result->fetch_object()) {
				$biolink_block->settings = json_decode($biolink_block->settings ?? '');

				switch($biolink_block->type) {
					case 'socials':
						$biolink_block->settings = (object) array_merge((array) $biolink_block->settings, (array) $biolink_theme->settings->biolink_block_socials ?? []);
						break;

					case 'heading':
						$biolink_block->settings = (object) array_merge((array) $biolink_block->settings, (array) $biolink_theme->settings->biolink_block_heading ?? []);
						break;

					case 'paragraph':
						$biolink_block->settings = (object) array_merge((array) $biolink_block->settings, (array) $biolink_theme->settings->biolink_block ?? [], (array) $biolink_theme->settings->biolink_block_paragraph ?? []);
						break;

                    case 'counter':
                    case 'loading':
                        $biolink_theme->settings->biolink_block->number_color = $biolink_theme->settings->biolink_block->text_color;

                        $biolink_block->settings = (object) array_merge((array) $biolink_block->settings, (array) $biolink_theme->settings->biolink_block ?? []);
                        break;

                    case 'external_item':
                        $biolink_theme->settings->biolink_block->price_color = $biolink_theme->settings->biolink_block->text_color;
                        $biolink_theme->settings->biolink_block->name_color = $biolink_theme->settings->biolink_block->text_color;

                        $biolink_block->settings = (object) array_merge((array) $biolink_block->settings, (array) $biolink_theme->settings->biolink_block ?? []);
                        break;

                    case 'business_hours':
                        $biolink_theme->settings->biolink_block->icon_color = $biolink_theme->settings->biolink_block->text_color;

                        $biolink_block->settings = (object) array_merge((array) $biolink_block->settings, (array) $biolink_theme->settings->biolink_block ?? []);
                        break;

					default:
						$biolink_block->settings = (object) array_merge((array) $biolink_block->settings, (array) $biolink_theme->settings->biolink_block ?? []);
						break;
				}

				$new_biolink_block_settings = json_encode($biolink_block->settings);

				db()->where('biolink_block_id', $biolink_block->biolink_block_id)->update('biolinks_blocks', [
					'settings' => $new_biolink_block_settings,
				]);
			}

			/* Clear the cache */
			cache()->deleteItem('biolink_blocks?link_id=' . $link->link_id);
			cache()->deleteItem('link?link_id=' . $link->link_id);
			cache()->deleteItemsByTag('link_id=' . $link->link_id);
			cache()->deleteItem('links?user_id=' . $this->user->user_id);
		}

		/* Prepare background url if needed */
		$image_url['background'] = $settings['background_type'] == 'image' && $settings['background'] ?  UPLOADS_FULL_URL . $image_upload_path['background'] . $settings['background'] : null;

		/* Get available notification handlers */
		$notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

		/* Notification handlers */
		$_POST['email_reports'] = array_map(
			'intval',
			array_filter($_POST['email_reports'] ?? [], function($notification_handler_id) use ($notification_handlers) {
				return array_key_exists($notification_handler_id, $notification_handlers);
			})
		);

		/* Prepare settings for JSON insertion */
		$settings = json_encode($settings);

		/* Update the record */
		db()->where('link_id', $link->link_id)->update('links', [
			'email_reports' => json_encode($_POST['email_reports']),
			'email_reports_count' => count($_POST['email_reports']),
			'email_reports_last_datetime' => !$link->email_reports_last_datetime ? get_date() : $link->email_reports_last_datetime,
			'project_id' => $_POST['project_id'],
			'splash_page_id' => $_POST['splash_page_id'],
			'domain_id' => $domain_id,
			'biolink_theme_id' => $_POST['biolink_theme_id'],
			'pixels_ids' => $_POST['pixels_ids'],
			'url' => $url,
			'settings' => $settings,
			'additional' => $additional,
			'directory_is_enabled' => $_POST['directory_is_enabled'],
			'last_datetime' => get_date(),
		]);

		$this->process_is_main_link_domain($link, $domains);

		$url = $domain_id && $_POST['is_main_link'] ? '' : $url;

		/* Clear the cache */
		cache()->deleteItem('biolink_blocks?link_id=' . $link->link_id);
		cache()->deleteItem('link?link_id=' . $link->link_id);
		cache()->deleteItemsByTag('link_id=' . $link->link_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_update) {
            fire_and_forget('post', settings()->webhooks->link_update, [
                'user_id' => $this->user->user_id,
                'link_id' => $_POST['link_id'],
                'domain_id' => $domain_id,
                'url' => $url,
                'full_url' => $domain_id ? $domains[$domain_id]->url . ($domains[$domain_id]->link_id == $_POST['link_id'] ? null : $url) : SITE_URL . $url,
                'type' => 'biolink',
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.update2'), 'success', [
			'url' => $url,
			'images' => [
				'seo_image' => $image_url['seo_image'],
				'favicon' => $image_url['favicon'],
				'background' => $image_url['background'],
				'pwa_icon' => $image_url['pwa_icon'],
				'branded_button_icon' => $image_url['branded_button_icon'],
			],
		]);

	}

	/* Custom code: FC-2026-03-06: reset existing biolink to factory template */
	private function reset_biolink_factory() {
		/* Team checks */
		if(
			\Altum\Teams::is_delegated()
			&& !\Altum\Teams::has_access('update.links')
		) {
			Response::json(l('global.info_message.team_no_access'), 'error');
		}

		$_POST['link_id'] = (int) ($_POST['link_id'] ?? 0);

		/* Custom code: FC-2026-03-06: debug trace for reset issues */
		$reset_debug_log = function($message) {
			@file_put_contents(UPLOADS_PATH . 'logs/bih_block_debug.log', '[' . get_date() . '] ' . $message . PHP_EOL, FILE_APPEND);
		};
		$reset_debug_log('reset_biolink_factory:start link_id=' . $_POST['link_id'] . ' user_id=' . $this->user->user_id);
		/* /Custom code: FC-2026-03-06 */

		$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->where('type', 'biolink')->getOne('links');

		if(!$link) {
			$reset_debug_log('reset_biolink_factory:error link_not_found');
			Response::json(l('global.error_message.basic'), 'error');
		}

		$biolink_template = $this->get_factory_biolink_template();

		$template_biolink = null;

		if($biolink_template) {
			$template_biolink = db()->where('link_id', $biolink_template->link_id)->where('type', 'biolink')->getOne('links');
		}

		/* Custom code: FC-2026-03-06: direct /link/83 fallback when template mapping is missing */
		if(!$template_biolink) {
			$template_biolink = db()->where('link_id', 83)->where('type', 'biolink')->getOne('links');
		}
		/* /Custom code: FC-2026-03-06 */

		/* Custom code: FC-2026-03-06: broad fallback to first available biolink template source */
		if(!$template_biolink) {
			$template_biolink = db()->where('type', 'biolink')->where('link_id', $link->link_id, '<>')->orderBy('link_id', 'ASC')->getOne('links');
		}
		/* /Custom code: FC-2026-03-06 */

		if(!$template_biolink) {
			$reset_debug_log('reset_biolink_factory:error template_biolink_not_found');
			Response::json(l('global.error_message.basic'), 'error');
		}

		$template_biolink->settings = json_decode($template_biolink->settings ?? '');

		if(!is_object($template_biolink->settings)) {
			$template_biolink->settings = (object) [];
		}

		if(!isset($template_biolink->settings->seo) || !is_object($template_biolink->settings->seo)) {
			$template_biolink->settings->seo = (object) [];
		}

		$template_biolink_seo_image = $template_biolink->settings->seo->image ?? ($template_biolink->settings->seo_image ?? null);
		/* Custom code: FC-2026-03-06: keep original assets on reset to avoid missing-file failures */
		$template_biolink->settings->seo->image = $template_biolink_seo_image;
		unset($template_biolink->settings->seo_image);

		$template_biolink->settings->favicon = $template_biolink->settings->favicon ?? null;

		if(($template_biolink->settings->background_type ?? null) == 'image') {
			$template_biolink->settings->background = $template_biolink->settings->background ?? null;
		}
		/* /Custom code: FC-2026-03-06 */

		$template_biolink->settings->pwa_is_enabled = false;
		$template_biolink->settings->pwa_icon = null;
		$template_biolink->settings->branded_button_icon = null;
		$template_biolink->settings->service_worker = null;

		db()->where('link_id', $link->link_id)->update('links', [
			/* Custom code: FC-2026-03-06: avoid theme ownership dependency on reset */
			'biolink_theme_id' => null,
			/* /Custom code: FC-2026-03-06 */
			'settings' => json_encode($template_biolink->settings),
			'additional' => $template_biolink->additional,
			'last_datetime' => get_date(),
		]);

		db()->where('link_id', $link->link_id)->delete('biolinks_blocks');

		$biolink_blocks = db()->where('link_id', $template_biolink->link_id)->get('biolinks_blocks');
		$reset_debug_log('reset_biolink_factory:template_link_id=' . $template_biolink->link_id . ' blocks=' . count($biolink_blocks ?? []));

		foreach($biolink_blocks as $biolink_block) {
			$biolink_block->settings = json_decode($biolink_block->settings ?? '');

			if(is_array($biolink_block->settings)) {
				$biolink_block->settings = (object) $biolink_block->settings;
			}

			if(!is_object($biolink_block->settings)) {
				$biolink_block->settings = (object) [];
			}

			switch($biolink_block->type) {
				case 'file':
				case 'audio':
				case 'video':
				case 'pdf_document':
				case 'powerpoint_presentation':
				case 'excel_spreadsheet':
				case 'review':
				case 'avatar':
				case 'header':
				case 'vcard':
				case 'image':
				case 'image_grid':
				case 'image_comparison':
					break;

				case 'heading':
					$biolink_block->settings->verified_location = '';
					break;

				case 'image_slider':
					$biolink_block->settings->items = (array) ($biolink_block->settings->items ?? []);

					foreach($biolink_block->settings->items as $key => $item) {
						if(is_array($biolink_block->settings->items[$key])) {
							$biolink_block->settings->items[$key] = (object) $biolink_block->settings->items[$key];
						}

						if(!is_object($biolink_block->settings->items[$key])) {
							$biolink_block->settings->items[$key] = (object) [];
						}

						$biolink_block->settings->items[$key]->image = $biolink_block->settings->items[$key]->image ?? null;
					}

					break;

				default:
					break;
			}

			db()->insert('biolinks_blocks', [
				'user_id' => $this->user->user_id,
				'link_id' => $link->link_id,
				'type' => $biolink_block->type,
				'location_url' => $biolink_block->location_url,
				'settings' => json_encode($biolink_block->settings),
				'order' => $biolink_block->order,
				'start_date' => $biolink_block->start_date,
				'end_date' => $biolink_block->end_date,
				'is_enabled' => $biolink_block->is_enabled,
				'datetime' => get_date(),
			]);
		}

		if($biolink_template && isset($biolink_template->biolink_template_id)) {
			db()->where('biolink_template_id', $biolink_template->biolink_template_id)->update('biolinks_templates', [
				'total_usage' => db()->inc()
			]);
		}

		(new \Altum\Models\User())->hydrate_biolink_from_user_data($this->user->user_id, $link->link_id);

		cache()->deleteItem('biolink_blocks?link_id=' . $link->link_id);
		cache()->deleteItem('link?link_id=' . $link->link_id);
		cache()->deleteItemsByTag('link_id=' . $link->link_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);
		$reset_debug_log('reset_biolink_factory:success target_link_id=' . $link->link_id);

		Response::json(l('global.success_message.update2'), 'success');
	}
	/* /Custom code: FC-2026-03-06 */

	private function update_file() {
		if(!settings()->links->files_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$_POST['link_id'] = (int) $_POST['link_id'];
		$_POST['project_id'] = empty($_POST['project_id']) ? null : (int) $_POST['project_id'];
		$_POST['url'] = !empty($_POST['url']) ? get_slug($_POST['url'], '-', false) : false;
		$_POST['schedule'] = (int) isset($_POST['schedule']);
		if($_POST['schedule'] && !empty($_POST['start_date']) && !empty($_POST['end_date']) && Date::validate($_POST['start_date'], 'Y-m-d H:i:s') && Date::validate($_POST['end_date'], 'Y-m-d H:i:s')) {
			$_POST['start_date'] = (new \DateTime($_POST['start_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
			$_POST['end_date'] = (new \DateTime($_POST['end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
		} else {
			$_POST['start_date'] = $_POST['end_date'] = null;
		}
		$_POST['expiration_url'] = get_url($_POST['expiration_url']);
		$_POST['clicks_limit'] = empty($_POST['clicks_limit']) ? null : (int) $_POST['clicks_limit'];
		$this->check_location_url($_POST['expiration_url'], true);
		$_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);
		$_POST['force_download_is_enabled'] = (int) isset($_POST['force_download_is_enabled']);

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Get domains */
		$domains = (new Domain())->get_available_domains_by_user($this->user);

		/* Check if custom domain is set */
		$domain_id = isset($domains[$_POST['domain_id']]) ? $_POST['domain_id'] : 0;

		/* Exclusivity check */
		$_POST['is_main_link'] = isset($_POST['is_main_link']) && $domain_id && $domains[$_POST['domain_id']]->type == 0;

		/* Existing pixels */
		$pixels = (new \Altum\Models\Pixel())->get_pixels($this->user->user_id);
		$_POST['pixels_ids'] = isset($_POST['pixels_ids']) ? array_map(
			'intval',
			array_filter($_POST['pixels_ids'], function($pixel_id) use($pixels) {
				return array_key_exists($pixel_id, $pixels);
			})
		) : [];
		$_POST['pixels_ids'] = json_encode($_POST['pixels_ids']);

		/* Check for any errors */
		$required_fields = [];
		foreach($required_fields as $field) {
			if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
				Response::json(l('global.error_message.empty_fields'), 'error');
				break 1;
			}
		}

		$this->check_url($_POST['url']);

		if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
			die();
		}

		/* Existing projects */
		$projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);
		$_POST['project_id'] = !empty($_POST['project_id']) && array_key_exists($_POST['project_id'], $projects) ? (int) $_POST['project_id'] : null;

		/* Existing splash pages */
		$splash_pages = (new \Altum\Models\SplashPages())->get_splash_pages_by_user_id($this->user->user_id);
		$_POST['splash_page_id'] = !empty($_POST['splash_page_id']) && array_key_exists($_POST['splash_page_id'], $splash_pages) ? (int) $_POST['splash_page_id'] : null;

		$link->settings = json_decode($link->settings ?? '');

		/* Check for a password set */
		$_POST['password'] = !empty($_POST['qweasdzxc']) ?
			($_POST['qweasdzxc'] != $link->settings->password ? password_hash($_POST['qweasdzxc'], PASSWORD_DEFAULT) : $link->settings->password)
			: null;

		/* Check for duplicate url if needed */
		if($_POST['url'] && ($_POST['url'] != $link->url || $domain_id != $link->domain_id)) {

			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}

		}

		$url = $_POST['url'];

		if(empty($_POST['url'])) {
			/* Generate random url if not specified */
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));

			while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
			}
		}

		/* File upload */
		$db_file = \Altum\Uploads::process_upload($link->settings->file, 'files', 'file', 'file_remove', settings()->links->file_size_limit, 'json_error');

		$settings = [
			'file' => $db_file,
			'clicks_limit' => $_POST['clicks_limit'],
			'expiration_url' => $_POST['expiration_url'],
			'schedule' => $_POST['schedule'],
			'password' => $_POST['password'],
			'sensitive_content' => $_POST['sensitive_content'],
			'force_download_is_enabled' => $_POST['force_download_is_enabled'],
		];

		/* Get available notification handlers */
		$notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

		/* Notification handlers */
		$_POST['email_reports'] = array_map(
			'intval',
			array_filter($_POST['email_reports'] ?? [], function($notification_handler_id) use ($notification_handlers) {
				return array_key_exists($notification_handler_id, $notification_handlers);
			})
		);

		$settings = json_encode($settings);

		db()->where('link_id', $_POST['link_id'])->update('links', [
			'project_id' => $_POST['project_id'],
			'email_reports' => json_encode($_POST['email_reports']),
			'email_reports_count' => count($_POST['email_reports']),
			'email_reports_last_datetime' => !$link->email_reports_last_datetime ? get_date() : $link->email_reports_last_datetime,
			'splash_page_id' => $_POST['splash_page_id'],
			'domain_id' => $domain_id,
			'pixels_ids' => $_POST['pixels_ids'],
			'url' => $url,
			'start_date' => $_POST['start_date'],
			'end_date' => $_POST['end_date'],
			'settings' => $settings,
			'last_datetime' => get_date(),
		]);

		$this->process_is_main_link_domain($link, $domains);

		$url = $domain_id && $_POST['is_main_link'] ? '' : $url;

		/* Clear the cache */
		cache()->deleteItem('biolink_blocks?link_id=' . $link->link_id);
		cache()->deleteItem('link?link_id=' . $link->link_id);
		cache()->deleteItemsByTag('link_id=' . $link->link_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_update) {
            fire_and_forget('post', settings()->webhooks->link_update, [
                'user_id' => $this->user->user_id,
                'link_id' => $_POST['link_id'],
                'domain_id' => $domain_id,
                'url' => $url,
                'full_url' => $domain_id ? $domains[$domain_id]->url . ($domains[$domain_id]->link_id == $_POST['link_id'] ? null : $url) : SITE_URL . $url,
                'type' => 'file',
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.update2'), 'success', ['url' => $url, 'file' => $db_file, 'file_url' => \Altum\Uploads::get_full_url('files') . $db_file]);
	}

	private function update_static() {
		/* feature check */
		if(!settings()->links->static_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		/* sanitize & normalize inputs */
		$_POST['link_id'] = (int) $_POST['link_id'];
		$_POST['project_id'] = empty($_POST['project_id']) ? null : (int) $_POST['project_id'];
		$_POST['url'] = !empty($_POST['url']) ? get_slug($_POST['url'], '-', false) : false;
		$_POST['schedule'] = (int) isset($_POST['schedule']);

		if($_POST['schedule'] && !empty($_POST['start_date']) && !empty($_POST['end_date']) && Date::validate($_POST['start_date'], 'Y-m-d H:i:s') && Date::validate($_POST['end_date'], 'Y-m-d H:i:s')) {
			$_POST['start_date'] = (new \DateTime($_POST['start_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
			$_POST['end_date'] = (new \DateTime($_POST['end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
		} else {
			$_POST['start_date'] = $_POST['end_date'] = null;
		}

		$_POST['expiration_url'] = get_url($_POST['expiration_url']);
		$_POST['clicks_limit'] = empty($_POST['clicks_limit']) ? null : (int) $_POST['clicks_limit'];
		$this->check_location_url($_POST['expiration_url'], true);
		$_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* available domains */
		$domains = (new Domain())->get_available_domains_by_user($this->user);
		$domain_id = isset($domains[$_POST['domain_id']]) ? $_POST['domain_id'] : 0;

		/* exclusivity */
		$_POST['is_main_link'] = isset($_POST['is_main_link']) && $domain_id && $domains[$_POST['domain_id']]->type == 0;

		/* pixels */
		$pixels = (new \Altum\Models\Pixel())->get_pixels($this->user->user_id);
		$_POST['pixels_ids'] = isset($_POST['pixels_ids']) ? array_map(
			'intval',
			array_filter($_POST['pixels_ids'], function($pixel_id) use($pixels) { return array_key_exists($pixel_id, $pixels); })
		) : [];
		$_POST['pixels_ids'] = json_encode($_POST['pixels_ids']);

		/* basic validation */
		$required_fields = [];
		foreach($required_fields as $field) {
			if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
				Response::json(l('global.error_message.empty_fields'), 'error');
				break 1;
			}
		}

		$this->check_url($_POST['url']);

		/* fetch link */
		if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
			die();
		}

		/* projects & splash pages */
		$projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);
		$_POST['project_id'] = !empty($_POST['project_id']) && array_key_exists($_POST['project_id'], $projects) ? (int) $_POST['project_id'] : null;

		$splash_pages = (new \Altum\Models\SplashPages())->get_splash_pages_by_user_id($this->user->user_id);
		$_POST['splash_page_id'] = !empty($_POST['splash_page_id']) && array_key_exists($_POST['splash_page_id'], $splash_pages) ? (int) $_POST['splash_page_id'] : null;

		$link->settings = json_decode($link->settings ?? '');

		/* password handling */
		$_POST['password'] = !empty($_POST['qweasdzxc'])
			? ($_POST['qweasdzxc'] != $link->settings->password ? password_hash($_POST['qweasdzxc'], PASSWORD_DEFAULT) : $link->settings->password)
			: null;

		/* duplicate url check */
		if($_POST['url'] && ($_POST['url'] != $link->url || $domain_id != $link->domain_id)) {
			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}
		}

		/* compute final url */
		$url = $_POST['url'];
		if(empty($_POST['url'])) {
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
			while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
			}
		}

		/* file upload handling */
		if(!empty($_FILES['file']['name'])) {
			$uploaded_file_extension = mb_strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
			$uploaded_file_temp = $_FILES['file']['tmp_name'];

			/* raise limits for large archives */
			set_time_limit(120);

			/* error checks */
			if($_FILES['file']['error'] == UPLOAD_ERR_INI_SIZE) {
				Response::json(sprintf(l('global.error_message.file_size_limit'), get_max_upload()), 'error');
			}
			if($_FILES['file']['error'] && $_FILES['file']['error'] != UPLOAD_ERR_INI_SIZE) {
				Response::json(l('global.error_message.file_upload'), 'error');
			}

			/* type whitelist */
			if(!in_array($uploaded_file_extension, \Altum\Uploads::get_whitelisted_file_extensions('static'))) {
				Response::json(l('global.error_message.invalid_file_type'), 'error');
			}

			/* permissions check when not offloading */
			if(!\Altum\Plugin::is_active('offload') || (\Altum\Plugin::is_active('offload') && !settings()->offload->uploads_url)) {
				if(!is_writable(UPLOADS_PATH . \Altum\Uploads::get_path('static'))) {
					Response::json(sprintf(l('global.error_message.directory_not_writable'), UPLOADS_PATH . \Altum\Uploads::get_path('static')), 'error');
				}
			}

			/* size limit */
			if(settings()->links->static_size_limit && $_FILES['file']['size'] > settings()->links->static_size_limit * 1000000) {
				Response::json(sprintf(l('global.error_message.file_size_limit'), settings()->links->static_size_limit), 'error');
			}

			/* target folder (reuse existing static folder) */
			$static_folder_name = $link->settings->static_folder;

			/* clear and recreate target */
			remove_directory_and_contents(\Altum\Uploads::get_full_path('static') . $static_folder_name);
			$base_folder = \Altum\Uploads::get_full_path('static') . $static_folder_name;
			mkdir($base_folder, 0777, true);

			/* single html upload */
			if($uploaded_file_extension == 'html') {
				move_uploaded_file($uploaded_file_temp, $base_folder . '/index.html');
				@unlink($uploaded_file_temp);
			}

			/* zip archive extraction */
			if($uploaded_file_extension == 'zip') {
				$zip = new \ZipArchive;
				if($zip->open($uploaded_file_temp) === true) {

					/* secure base path */
					$real_base_folder = realpath($base_folder);

					/* create directories and extract files */
					for($file_index = 0; $file_index < $zip->numFiles; $file_index++) {
						$entry_name = $zip->getNameIndex($file_index);
						$entry_info = $zip->statIndex($file_index);

						/* skip macos junk and directory entries */
						if(str_contains($entry_info['name'], '__MACOSX')) { continue; }

						$is_directory = $entry_info['name'][strlen($entry_info['name']) - 1] == '/';

						/* normalize relative path and deny zip slip patterns */
						$relative_path = ltrim(preg_replace('#/+#', '/', $entry_info['name']), '/');
						if(preg_match('#(^|/)\.\.(?:/|$)#', $relative_path)) {
							$zip->close();
							Response::json('Invalid file path detected inside zip.', 'error');
						}

						$target_path = $base_folder . '/' . $relative_path;

						if($is_directory) {
							if(!is_dir($target_path)) {
								mkdir($target_path, 0777, true);
							}
							/* continue to next entry */
							continue;
						}

						/* whitelist file extensions for extracted files */
						$entry_extension = mb_strtolower(pathinfo($entry_name, PATHINFO_EXTENSION));
						if(!in_array($entry_extension, \Altum\Uploads::$uploads['static']['inside_zip_whitelisted_file_extensions'])) {
							continue;
						}

						/* ensure directory exists */
						$target_dir = dirname($target_path);
						if(!is_dir($target_dir)) {
							mkdir($target_dir, 0777, true);
						}

						/* final zip slip guard using realpath on dir */
						$real_target_dir = realpath($target_dir);
						if($real_target_dir === false || strpos($real_target_dir, $real_base_folder) !== 0) {
							$zip->close();
							Response::json('Invalid file path detected inside zip.', 'error');
						}

						/* extract */
						copy('zip://' . $uploaded_file_temp . '#' . $entry_name, $target_path);

						/* keep connection alive on big archives */
						if($file_index % 100 == 0) {
							flush();
							@ob_flush();
						}
					}

					$zip->close();
					@unlink($uploaded_file_temp);
				} else {
					Response::json(l('global.error_message.basic'), 'error');
				}
			}
		}

		/* settings payload: keep it lean (do not store large file lists) */
		$settings = json_encode([
			'static_folder' => $link->settings->static_folder,
			'schedule' => $_POST['schedule'],
			'clicks_limit' => $_POST['clicks_limit'],
			'expiration_url' => $_POST['expiration_url'],
			'password' => $_POST['password'],
			'sensitive_content' => $_POST['sensitive_content'],
		]);

		/* notification handlers */
		$notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);
		$_POST['email_reports'] = array_map(
			'intval',
			array_filter($_POST['email_reports'] ?? [], function($notification_handler_id) use ($notification_handlers) {
				return array_key_exists($notification_handler_id, $notification_handlers);
			})
		);

		/* Database query */
		db()->where('link_id', $_POST['link_id'])->update('links', [
			'project_id' => $_POST['project_id'],
			'email_reports' => json_encode($_POST['email_reports']),
			'email_reports_count' => count($_POST['email_reports']),
			'email_reports_last_datetime' => !$link->email_reports_last_datetime ? get_date() : $link->email_reports_last_datetime,
			'splash_page_id' => $_POST['splash_page_id'],
			'domain_id' => $domain_id,
			'pixels_ids' => $_POST['pixels_ids'],
			'url' => $url,
			'start_date' => $_POST['start_date'],
			'end_date' => $_POST['end_date'],
			'settings' => $settings,
			'last_datetime' => get_date(),
		]);

		/* process main link exclusivity */
		$this->process_is_main_link_domain($link, $domains);
		$url = $domain_id && $_POST['is_main_link'] ? '' : $url;

		/* Clear the cache */
		cache()->deleteItem('biolink_blocks?link_id=' . $link->link_id);
		cache()->deleteItem('link?link_id=' . $link->link_id);
		cache()->deleteItemsByTag('link_id=' . $link->link_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_update) {
            fire_and_forget('post', settings()->webhooks->link_update, [
                'user_id' => $this->user->user_id,
                'link_id' => $_POST['link_id'],
                'domain_id' => $domain_id,
                'url' => $url,
                'full_url' => $domain_id ? $domains[$domain_id]->url . ($domains[$domain_id]->link_id == $_POST['link_id'] ? null : $url) : SITE_URL . $url,
                'type' => 'static',
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.update2'), 'success', ['url' => $url]);
	}
	private function update_vcard() {
		if(!settings()->links->vcards_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$_POST['link_id'] = (int) $_POST['link_id'];
		$_POST['project_id'] = empty($_POST['project_id']) ? null : (int) $_POST['project_id'];
		$_POST['url'] = !empty($_POST['url']) ? get_slug($_POST['url'], '-', false) : false;
		$_POST['schedule'] = (int) isset($_POST['schedule']);
		if($_POST['schedule'] && !empty($_POST['start_date']) && !empty($_POST['end_date']) && Date::validate($_POST['start_date'], 'Y-m-d H:i:s') && Date::validate($_POST['end_date'], 'Y-m-d H:i:s')) {
			$_POST['start_date'] = (new \DateTime($_POST['start_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
			$_POST['end_date'] = (new \DateTime($_POST['end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
		} else {
			$_POST['start_date'] = $_POST['end_date'] = null;
		}
		$_POST['expiration_url'] = get_url($_POST['expiration_url']);
		$_POST['clicks_limit'] = empty($_POST['clicks_limit']) ? null : (int) $_POST['clicks_limit'];
		$this->check_location_url($_POST['expiration_url'], true);
		$_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Get domains */
		$domains = (new Domain())->get_available_domains_by_user($this->user);

		/* Check if custom domain is set */
		$domain_id = isset($domains[$_POST['domain_id']]) ? $_POST['domain_id'] : 0;

		/* Exclusivity check */
		$_POST['is_main_link'] = isset($_POST['is_main_link']) && $domain_id && $domains[$_POST['domain_id']]->type == 0;

		/* Existing pixels */
		$pixels = (new \Altum\Models\Pixel())->get_pixels($this->user->user_id);
		$_POST['pixels_ids'] = isset($_POST['pixels_ids']) ? array_map(
			'intval',
			array_filter($_POST['pixels_ids'], function($pixel_id) use($pixels) {
				return array_key_exists($pixel_id, $pixels);
			})
		) : [];
		$_POST['pixels_ids'] = json_encode($_POST['pixels_ids']);

		/* Check for any errors */
		$required_fields = [];
		foreach($required_fields as $field) {
			if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
				Response::json(l('global.error_message.empty_fields'), 'error');
				break 1;
			}
		}

		$this->check_url($_POST['url']);

		if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
			die();
		}

		/* Existing projects */
		$projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);
		$_POST['project_id'] = !empty($_POST['project_id']) && array_key_exists($_POST['project_id'], $projects) ? (int) $_POST['project_id'] : null;

		/* Existing splash pages */
		$splash_pages = (new \Altum\Models\SplashPages())->get_splash_pages_by_user_id($this->user->user_id);
		$_POST['splash_page_id'] = !empty($_POST['splash_page_id']) && array_key_exists($_POST['splash_page_id'], $splash_pages) ? (int) $_POST['splash_page_id'] : null;

		$link->settings = json_decode($link->settings ?? '');

		/* Check for a password set */
		$_POST['password'] = !empty($_POST['qweasdzxc']) ?
			($_POST['qweasdzxc'] != $link->settings->password ? password_hash($_POST['qweasdzxc'], PASSWORD_DEFAULT) : $link->settings->password)
			: null;


		/* Check for duplicate url if needed */
		if($_POST['url'] && ($_POST['url'] != $link->url || $domain_id != $link->domain_id)) {

			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}

		}

		$url = $_POST['url'];

		if(empty($_POST['url'])) {
			/* Generate random url if not specified */
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));

			while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
			}
		}

		/* File upload */
		$db_vcard_avatar = \Altum\Uploads::process_upload($link->settings->vcard_avatar, 'vcards_avatars', 'vcard_avatar', 'vcard_avatar_remove', 0.75, 'json_error');
		$vcard_avatar_url = $db_vcard_avatar ? \Altum\Uploads::get_full_url('avatars') . $db_vcard_avatar : null;

		$settings = [
			'vcard_avatar' => $db_vcard_avatar,
			'schedule' => $_POST['schedule'],
			'clicks_limit' => $_POST['clicks_limit'],
			'expiration_url' => $_POST['expiration_url'],
			'password' => $_POST['password'],
			'sensitive_content' => $_POST['sensitive_content'],
		];

		/* Process vcard */
		$settings['vcard_first_name'] = $_POST['vcard_first_name'] = mb_substr(input_clean($_POST['vcard_first_name']), 0, $this->links_types['vcard']['fields']['first_name']['max_length']);
		$settings['vcard_last_name'] = $_POST['vcard_last_name'] = mb_substr(input_clean($_POST['vcard_last_name']), 0, $this->links_types['vcard']['fields']['last_name']['max_length']);
		$settings['vcard_email'] = $_POST['vcard_email'] = mb_substr(input_clean($_POST['vcard_email']), 0, $this->links_types['vcard']['fields']['email']['max_length']);
		$settings['vcard_url'] = $_POST['vcard_url'] = mb_substr(input_clean($_POST['vcard_url']), 0, $this->links_types['vcard']['fields']['url']['max_length']);
		$settings['vcard_company'] = $_POST['vcard_company'] = mb_substr(input_clean($_POST['vcard_company']), 0, $this->links_types['vcard']['fields']['company']['max_length']);
		$settings['vcard_job_title'] = $_POST['vcard_job_title'] = mb_substr(input_clean($_POST['vcard_job_title']), 0, $this->links_types['vcard']['fields']['job_title']['max_length']);
		$settings['vcard_birthday'] = $_POST['vcard_birthday'] = mb_substr(input_clean($_POST['vcard_birthday']), 0, $this->links_types['vcard']['fields']['birthday']['max_length']);
		$settings['vcard_street'] = $_POST['vcard_street'] = mb_substr(input_clean($_POST['vcard_street']), 0, $this->links_types['vcard']['fields']['street']['max_length']);
		$settings['vcard_city'] = $_POST['vcard_city'] = mb_substr(input_clean($_POST['vcard_city']), 0, $this->links_types['vcard']['fields']['city']['max_length']);
		$settings['vcard_zip'] = $_POST['vcard_zip'] = mb_substr(input_clean($_POST['vcard_zip']), 0, $this->links_types['vcard']['fields']['zip']['max_length']);
		$settings['vcard_region'] = $_POST['vcard_region'] = mb_substr(input_clean($_POST['vcard_region']), 0, $this->links_types['vcard']['fields']['region']['max_length']);
		$settings['vcard_country'] = $_POST['vcard_country'] = mb_substr(input_clean($_POST['vcard_country']), 0, $this->links_types['vcard']['fields']['country']['max_length']);
		$settings['vcard_note'] = $_POST['vcard_note'] = mb_substr(input_clean($_POST['vcard_note']), 0, $this->links_types['vcard']['fields']['note']['max_length']);

		/* Phone numbers */
		if(!isset($_POST['vcard_phone_number_label'])) {
			$_POST['vcard_phone_number_label'] = [];
			$_POST['vcard_phone_number_value'] = [];
		}
		$vcard_phone_numbers = [];
		foreach($_POST['vcard_phone_number_label'] as $key => $value) {
			if($key >= 20) continue;

			$vcard_phone_numbers[] = [
				'label' => mb_substr(input_clean($value), 0, $this->links_types['vcard']['fields']['phone_number_value']['max_length']),
				'value' => mb_substr(input_clean($_POST['vcard_phone_number_value'][$key]), 0, $this->links_types['vcard']['fields']['phone_number_value']['max_length'])
			];
		}
		$settings['vcard_phone_numbers'] = $vcard_phone_numbers;

		/* Socials */
		if(!isset($_POST['vcard_social_label'])) {
			$_POST['vcard_social_label'] = [];
			$_POST['vcard_social_value'] = [];
		}

		$vcard_socials = [];
		foreach($_POST['vcard_social_label'] as $key => $value) {
			if(empty(trim($value))) continue;
			if($key >= 20) continue;

			$vcard_socials[] = [
				'label' => mb_substr(input_clean($value), 0, $this->links_types['vcard']['fields']['social_value']['max_length']),
				'value' => mb_substr(input_clean($_POST['vcard_social_value'][$key]), 0, $this->links_types['vcard']['fields']['social_value']['max_length'])
			];
		}
		$settings['vcard_socials'] = $vcard_socials;

		/* Get available notification handlers */
		$notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

		/* Notification handlers */
		$_POST['email_reports'] = array_map(
			'intval',
			array_filter($_POST['email_reports'] ?? [], function($notification_handler_id) use ($notification_handlers) {
				return array_key_exists($notification_handler_id, $notification_handlers);
			})
		);

		$settings = json_encode($settings);

		db()->where('link_id', $_POST['link_id'])->update('links', [
			'project_id' => $_POST['project_id'],
			'email_reports' => json_encode($_POST['email_reports']),
			'email_reports_count' => count($_POST['email_reports']),
			'email_reports_last_datetime' => !$link->email_reports_last_datetime ? get_date() : $link->email_reports_last_datetime,
			'splash_page_id' => $_POST['splash_page_id'],
			'domain_id' => $domain_id,
			'pixels_ids' => $_POST['pixels_ids'],
			'url' => $url,
			'start_date' => $_POST['start_date'],
			'end_date' => $_POST['end_date'],
			'settings' => $settings,
			'last_datetime' => get_date(),
		]);

		$this->process_is_main_link_domain($link, $domains);

		$url = $domain_id && $_POST['is_main_link'] ? '' : $url;

		/* Clear the cache */
		cache()->deleteItem('biolink_blocks?link_id=' . $link->link_id);
		cache()->deleteItem('link?link_id=' . $link->link_id);
		cache()->deleteItemsByTag('link_id=' . $link->link_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_update) {
            fire_and_forget('post', settings()->webhooks->link_update, [
                'user_id' => $this->user->user_id,
                'link_id' => $_POST['link_id'],
                'domain_id' => $domain_id,
                'url' => $url,
                'full_url' => $domain_id ? $domains[$domain_id]->url . ($domains[$domain_id]->link_id == $_POST['link_id'] ? null : $url) : SITE_URL . $url,
                'type' => 'vcard',
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.update2'), 'success', ['url' => $url, 'images' => ['vcard_avatar' => $vcard_avatar_url]]);
	}

	private function update_event() {
		if(!settings()->links->events_is_enabled) {
			Response::json(l('global.error_message.basic'), 'error');
		}

		$_POST['link_id'] = (int) $_POST['link_id'];
		$_POST['project_id'] = empty($_POST['project_id']) ? null : (int) $_POST['project_id'];
		$_POST['url'] = !empty($_POST['url']) ? get_slug($_POST['url'], '-', false) : false;
		$_POST['schedule'] = (int) isset($_POST['schedule']);
		if($_POST['schedule'] && !empty($_POST['start_date']) && !empty($_POST['end_date']) && Date::validate($_POST['start_date'], 'Y-m-d H:i:s') && Date::validate($_POST['end_date'], 'Y-m-d H:i:s')) {
			$_POST['start_date'] = (new \DateTime($_POST['start_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
			$_POST['end_date'] = (new \DateTime($_POST['end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
		} else {
			$_POST['start_date'] = $_POST['end_date'] = null;
		}
		$_POST['expiration_url'] = get_url($_POST['expiration_url']);
		$_POST['clicks_limit'] = empty($_POST['clicks_limit']) ? null : (int) $_POST['clicks_limit'];
		$this->check_location_url($_POST['expiration_url'], true);
		$_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);

		if(empty($_POST['domain_id']) && !settings()->links->main_domain_is_enabled && !\Altum\Authentication::is_admin()) {
			Response::json(l('create_link_modal.error_message.main_domain_is_disabled'), 'error');
		}

		/* Get domains */
		$domains = (new Domain())->get_available_domains_by_user($this->user);

		/* Check if custom domain is set */
		$domain_id = isset($domains[$_POST['domain_id']]) ? $_POST['domain_id'] : 0;

		/* Exclusivity check */
		$_POST['is_main_link'] = isset($_POST['is_main_link']) && $domain_id && $domains[$_POST['domain_id']]->type == 0;

		/* Existing pixels */
		$pixels = (new \Altum\Models\Pixel())->get_pixels($this->user->user_id);
		$_POST['pixels_ids'] = isset($_POST['pixels_ids']) ? array_map(
			'intval',
			array_filter($_POST['pixels_ids'], function($pixel_id) use($pixels) {
				return array_key_exists($pixel_id, $pixels);
			})
		) : [];
		$_POST['pixels_ids'] = json_encode($_POST['pixels_ids']);

		/* Check for any errors */
		$required_fields = [];
		foreach($required_fields as $field) {
			if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
				Response::json(l('global.error_message.empty_fields'), 'error');
				break 1;
			}
		}

		$this->check_url($_POST['url']);

		if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
			die();
		}

		/* Existing projects */
		$projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);
		$_POST['project_id'] = !empty($_POST['project_id']) && array_key_exists($_POST['project_id'], $projects) ? (int) $_POST['project_id'] : null;

		/* Existing splash pages */
		$splash_pages = (new \Altum\Models\SplashPages())->get_splash_pages_by_user_id($this->user->user_id);
		$_POST['splash_page_id'] = !empty($_POST['splash_page_id']) && array_key_exists($_POST['splash_page_id'], $splash_pages) ? (int) $_POST['splash_page_id'] : null;

		$link->settings = json_decode($link->settings ?? '');

		/* Check for a password set */
		$_POST['password'] = !empty($_POST['qweasdzxc']) ?
			($_POST['qweasdzxc'] != $link->settings->password ? password_hash($_POST['qweasdzxc'], PASSWORD_DEFAULT) : $link->settings->password)
			: null;


		/* Check for duplicate url if needed */
		if($_POST['url'] && ($_POST['url'] != $link->url || $domain_id != $link->domain_id)) {

			if(db()->where('url', $_POST['url'])->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				Response::json(l('link.error_message.url_exists'), 'error');
			}

		}

		$url = $_POST['url'];

		if(empty($_POST['url'])) {
			/* Generate random url if not specified */
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));

			while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
				$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
			}
		}

		$settings = [
			'schedule' => $_POST['schedule'],
			'clicks_limit' => $_POST['clicks_limit'],
			'expiration_url' => $_POST['expiration_url'],
			'password' => $_POST['password'],
			'sensitive_content' => $_POST['sensitive_content'],
		];

		/* Process event */
		$settings['event_name'] = $_POST['event_name'] = mb_substr(input_clean($_POST['event_name']), 0, $this->links_types['event']['fields']['name']['max_length']);
		$settings['event_location'] = $_POST['event_location'] = mb_substr(input_clean($_POST['event_location']), 0, $this->links_types['event']['fields']['location']['max_length']);
		$settings['event_url'] = $_POST['event_url'] = mb_substr(input_clean($_POST['event_url']), 0, $this->links_types['event']['fields']['url']['max_length']);
		$settings['event_note'] = $_POST['event_note'] = mb_substr(input_clean($_POST['event_note']), 0, $this->links_types['event']['fields']['note']['max_length']);
		$settings['event_timezone'] = $_POST['event_timezone'] = in_array($_POST['event_timezone'], \DateTimeZone::listIdentifiers()) ? input_clean($_POST['event_timezone']) : Date::$default_timezone;
		try {
			$settings['event_start_datetime'] = $_POST['event_start_datetime'] = (new \DateTime($_POST['event_start_datetime']))->format('Y-m-d\TH:i:s');
			$settings['event_end_datetime'] = $_POST['event_end_datetime'] = (new \DateTime($_POST['event_end_datetime']))->format('Y-m-d\TH:i:s');
			$settings['event_first_alert_datetime'] = $_POST['event_first_alert_datetime'] = (new \DateTime($_POST['event_first_alert_datetime']))->format('Y-m-d\TH:i:s');
			$settings['event_second_alert_datetime'] = $_POST['event_second_alert_datetime'] = (new \DateTime($_POST['event_second_alert_datetime']))->format('Y-m-d\TH:i:s');
		} catch (\Exception $exception) {
			/* :) */
		}

		/* Get available notification handlers */
		$notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

		/* Notification handlers */
		$_POST['email_reports'] = array_map(
			'intval',
			array_filter($_POST['email_reports'] ?? [], function($notification_handler_id) use ($notification_handlers) {
				return array_key_exists($notification_handler_id, $notification_handlers);
			})
		);

		$settings = json_encode($settings);

		db()->where('link_id', $_POST['link_id'])->update('links', [
			'project_id' => $_POST['project_id'],
			'email_reports' => json_encode($_POST['email_reports']),
			'email_reports_count' => count($_POST['email_reports']),
			'email_reports_last_datetime' => !$link->email_reports_last_datetime ? get_date() : $link->email_reports_last_datetime,
			'splash_page_id' => $_POST['splash_page_id'],
			'domain_id' => $domain_id,
			'pixels_ids' => $_POST['pixels_ids'],
			'url' => $url,
			'start_date' => $_POST['start_date'],
			'end_date' => $_POST['end_date'],
			'settings' => $settings,
			'last_datetime' => get_date(),
		]);

		$this->process_is_main_link_domain($link, $domains);

		$url = $domain_id && $_POST['is_main_link'] ? '' : $url;

		/* Clear the cache */
		cache()->deleteItem('biolink_blocks?link_id=' . $link->link_id);
		cache()->deleteItem('link?link_id=' . $link->link_id);
		cache()->deleteItemsByTag('link_id=' . $link->link_id);
		cache()->deleteItem('links?user_id=' . $this->user->user_id);

        /* Send webhook notification if needed */
        if(settings()->webhooks->link_update) {
            fire_and_forget('post', settings()->webhooks->link_update, [
                'user_id' => $this->user->user_id,
                'link_id' => $_POST['link_id'],
                'domain_id' => $domain_id,
                'url' => $url,
                'full_url' => $domain_id ? $domains[$domain_id]->url . ($domains[$domain_id]->link_id == $_POST['link_id'] ? null : $url) : SITE_URL . $url,
                'type' => 'event',
                'datetime' => get_date(),
            ], signature: true);
        }

		Response::json(l('global.success_message.update2'), 'success', ['url' => $url]);
	}

	private function delete() {
		/* Team checks */
		if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.links')) {
			Response::json(l('global.info_message.team_no_access'), 'error');
		}

		$_POST['link_id'] = (int) $_POST['link_id'];

		/* Check for possible errors */
		if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links', ['link_id', 'type'])) {
			die();
		}

		/* Custom code: FC-2026-02-24: lock main NFC biolink deletion */
		$main_biolink_id = (int) (fc_get_user_main_biolink_id((int) $this->user->user_id) ?? 0);
		if($main_biolink_id && $main_biolink_id === (int) $link->link_id) {
			Response::json(l('link_delete_modal.error_message.main_biolink_locked'), 'error');
		}
		/* /Custom code: FC-2026-02-24 */

		(new \Altum\Models\Link())->delete($link->link_id);

		Response::json(l('global.success_message.delete2'), 'success', ['url' => url('links?type=' . $link->type)]);
	}

	public function duplicate() {
		/* Team checks */
		if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.links')) {
			Alerts::add_error(l('global.info_message.team_no_access'));
			redirect('links');
		}

		$_POST['link_id'] = (int) $_POST['link_id'];

		//ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

		if(!\Altum\Csrf::check()) {
			Alerts::add_error(l('global.error_message.invalid_csrf_token'));
			redirect('links');
		}

		/* Get the link data */
		$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links');

		if(!$link) {
			redirect('links');
		}

		/* Check for the plan limit */
		if($link->type == 'link') {
			if(!settings()->links->shortener_is_enabled) {
				Response::json(l('global.error_message.basic'), 'error');
			}

			$user_total_links = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = 'link'")->fetch_object()->total;
			if($this->user->plan_settings->links_limit != -1 && $user_total_links >= $this->user->plan_settings->links_limit) {
				Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
			}
		}

		elseif($link->type == 'biolink') {
			if(!settings()->links->biolinks_is_enabled) {
				Response::json(l('global.error_message.basic'), 'error');
			}

			$user_total_biolinks = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = 'biolink'")->fetch_object()->total;
			if($this->user->plan_settings->biolinks_limit != -1 && $user_total_biolinks >= $this->user->plan_settings->biolinks_limit) {
				Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
			}
		}

		elseif($link->type == 'file') {
			if(!settings()->links->files_is_enabled) {
				Response::json(l('global.error_message.basic'), 'error');
			}

			$user_total_files = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = 'file'")->fetch_object()->total;
			if($this->user->plan_settings->files_limit != -1 && $user_total_files >= $this->user->plan_settings->files_limit) {
				Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
			}
		}

		elseif($link->type == 'vcard') {
			if(!settings()->links->vcards_is_enabled) {
				Response::json(l('global.error_message.basic'), 'error');
			}

			$user_total_vcards = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = 'vcard'")->fetch_object()->total;
			if($this->user->plan_settings->vcards_limit != -1 && $user_total_vcards >= $this->user->plan_settings->vcards_limit) {
				Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
			}
		}

		if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

			/* Duplicate the link */
			$link->settings = json_decode($link->settings ?? '');

			if($link->type == 'biolink') {
				$link->settings->seo->image = \Altum\Uploads::copy_uploaded_file($link->settings->seo->image, 'block_images/', 'block_images/', 'json_error');
				$link->settings->favicon = \Altum\Uploads::copy_uploaded_file($link->settings->favicon, 'favicons/', 'favicons/', 'json_error');
				if($link->settings->background_type == 'image' && !$link->biolink_theme_id) $link->settings->background = \Altum\Uploads::copy_uploaded_file($link->settings->background, 'backgrounds/', 'backgrounds/', 'json_error');
				$link->settings->pwa_icon = \Altum\Uploads::copy_uploaded_file($link->settings->pwa_icon, 'pwa/', 'pwa/', 'json_error');
				$link->settings->branded_button_icon = \Altum\Uploads::copy_uploaded_file($link->settings->branded_button_icon, 'favicon/', 'favicon/', 'json_error');
				$link->settings->pwa_is_enabled = false;
                $link->settings->service_worker = false;
			}

			if($link->type == 'vcard') {
				$link->settings->vcard_avatar = \Altum\Uploads::copy_uploaded_file($link->settings->vcard_avatar, \Altum\Uploads::get_path('vcards_avatars'), \Altum\Uploads::get_path('vcards_avatars'), 'json_error');
			}

			if($link->type == 'file') {
				$link->settings->file = \Altum\Uploads::copy_uploaded_file($link->settings->file, \Altum\Uploads::get_path('files'), \Altum\Uploads::get_path('files'), 'json_error');
			}

			/* Generate random url if not specified */
			$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
			while (db()->where('url', $url)->where('domain_id', $link->domain_id)->getValue('links', 'link_id')) {
				$url = mb_strtolower(string_generate(settings()->links->random_url_length ?? 7));
			}

			/* Database query */
			$link_id = db()->insert('links', [
				'user_id' => $this->user->user_id,
				'project_id' => $link->project_id,
				'email_reports' => $link->email_reports,
				'email_reports_last_datetime' => $link->email_reports_last_datetime,
				'biolink_theme_id' => $link->biolink_theme_id,
				'domain_id' => $link->domain_id,
				'pixels_ids' => $link->pixels_ids,
				'type' => $link->type,
				'url' => $url,
				'location_url' => $link->location_url,
				'settings' => json_encode($link->settings),
				'additional' => $link->additional ?? '',
				'start_date' => $link->start_date,
				'end_date' => $link->end_date,
				'is_verified' => 0,
				'is_enabled' => $link->is_enabled,
				'datetime' => get_date(),
			]);

			/* Duplicate the biolink blocks */
			if($link->type == 'biolink') {
				/* Get all biolink blocks if needed */
				$biolink_blocks = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->get('biolinks_blocks');

				foreach($biolink_blocks as $biolink_block) {
					$biolink_block->settings = json_decode($biolink_block->settings ?? '');

					if(is_array($biolink_block->settings)) {
						$biolink_block->settings = (object) $biolink_block->settings;
					}

					/* Duplication of resources */
					switch($biolink_block->type) {
						case 'file':
						case 'audio':
						case 'video':
						case 'pdf_document':
						case 'powerpoint_presentation':
						case 'excel_spreadsheet':
							$biolink_block->settings->file = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->file, \Altum\Uploads::get_path('files'), \Altum\Uploads::get_path('files'), 'json_error');
							break;

						case 'review':
							$biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, \Altum\Uploads::get_path('block_images'), \Altum\Uploads::get_path('block_images'), 'json_error');
							break;

						case 'avatar':
							$biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'avatars/', 'avatars/', 'json_error');
							break;

						case 'header':
							$biolink_block->settings->avatar = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->avatar, 'avatars/', 'avatars/', 'json_error');
							$biolink_block->settings->background = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->background, 'backgrounds/', 'backgrounds/', 'json_error');
                            $biolink_block->settings->video_file = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->video_file, 'files/', 'files/', 'json_error');
							break;

						case 'vcard':
							$biolink_block->settings->vcard_avatar = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->vcard_avatar, 'avatars/', 'avatars/', 'json_error');
							break;

						case 'image':
						case 'image_grid':
							$biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_images/', 'block_images/', 'json_error');
							break;

                        case 'image_comparison':
                            $biolink_block->settings->before_image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->before_image, 'block_images/', 'block_images/', 'json_error');
                            $biolink_block->settings->after_image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->after_image, 'block_images/', 'block_images/', 'json_error');
                            break;

						case 'heading':
							$biolink_block->settings->verified_location = '';
							break;

						case 'image_slider':
							$biolink_block->settings->items = (array) $biolink_block->settings->items;
							foreach($biolink_block->settings->items as $key => $item) {
								$biolink_block->settings->items[$key]->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->items[$key]->image, 'block_images/', 'block_images/', 'json_error');
							}

							break;

						default:
							$biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_thumbnail_images/', 'block_thumbnail_images/', 'json_error');
							break;
					}

					/* Database query */
					db()->insert('biolinks_blocks', [
						'user_id' => $this->user->user_id,
						'link_id' => $link_id,
						'type' => $biolink_block->type,
						'location_url' => $biolink_block->location_url,
						'settings' => json_encode($biolink_block->settings),
						'order' => $biolink_block->order,
						'start_date' => $biolink_block->start_date,
						'end_date' => $biolink_block->end_date,
						'is_enabled' => $biolink_block->is_enabled,
						'datetime' => get_date(),
					]);
				}
			}

			/* Set a nice success message */
			Alerts::add_success(l('global.success_message.create2'));

			/* Redirect */
			redirect('link/' . $link_id);
		}

		redirect('links');
	}


	/* Function to bundle together all the checks of a custom url */
	private function check_url($url) {
		if($url) {
			/* Make sure the url alias is not blocked by a route of the product */
			if(array_key_exists($url, \Altum\Router::$routes['']) || in_array($url, \Altum\Language::$active_languages) || file_exists(ROOT_PATH . $url)) {
				Response::json(l('link.error_message.blacklisted_url'), 'error');
			}

			/* Make sure the custom url is not blacklisted */
			if(in_array(mb_strtolower($url), settings()->links->blacklisted_keywords)) {
				Response::json(l('link.error_message.blacklisted_keyword'), 'error');
			}

			/* Make sure the custom url meets the requirements */
			if(mb_strlen($url) < ($this->user->plan_settings->url_minimum_characters ?? 1)) {
				Response::json(sprintf(l('link.error_message.url_minimum_characters'), $this->user->plan_settings->url_minimum_characters ?? 1), 'error');
			}

			if(mb_strlen($url) > ($this->user->plan_settings->url_maximum_characters ?? 64)) {
				Response::json(sprintf(l('link.error_message.url_maximum_characters'), $this->user->plan_settings->url_maximum_characters ?? 64), 'error');
			}
		}
	}

	/* Function to bundle together all the checks of an url */
	private function check_location_url($url, $can_be_empty = false) {

		if(empty(trim($url)) && $can_be_empty) {
			return;
		}

		if(empty(trim($url))) {
			Response::json(l('global.error_message.empty_fields'), 'error');
		}

		$url_details = parse_url($url);

		if(!isset($url_details['scheme'])) {
			Response::json(l('link.error_message.invalid_location_url'), 'error');
		}

		if(!$this->user->plan_settings->deep_links && !in_array($url_details['scheme'], ['http', 'https'])) {
			Response::json(l('link.error_message.invalid_location_url'), 'error');
		}

		/* Make sure the domain is not blacklisted */
		$domain = get_domain_from_url($url);

		if($domain && in_array($domain, settings()->links->blacklisted_domains)) {
			Response::json(l('link.error_message.blacklisted_domain'), 'error');
		}

		/* Check the url with google safe browsing to make sure it is a safe website */
		if(settings()->links->google_safe_browsing_is_enabled) {
			if(google_safe_browsing_check($url, settings()->links->google_safe_browsing_api_key)) {
				Response::json(l('link.error_message.blacklisted_location_url'), 'error');
			}
		}
	}

	private function process_is_main_link_domain($link, $domains) {
		/* Update custom domain if needed */
		if($_POST['is_main_link']) {

			/* If the main status page of a particular domain is changing, update the old domain as well to "free" it */
			if($_POST['domain_id'] != $link->domain_id) {
				/* Database query */
				db()->where('domain_id', $link->domain_id)->update('domains', [
					'link_id' => null,
					'last_datetime' => get_date(),
				]);
			}

			/* Database query */
			db()->where('domain_id', $_POST['domain_id'])->update('domains', [
				'link_id' => $link->link_id,
				'last_datetime' => get_date(),
			]);

			/* Clear the cache */
			cache()->deleteItems([
				'domains?user_id=' . $this->user->user_id,
				'domain?domain_id=' . $link->domain_id,
				'domain?domain_id=' . $_POST['domain_id'],
				'domain?host=' . md5($domains[$link->domain_id]->host ?? ''),
				'domain?host=' . md5($domains[$_POST['domain_id']]->host ?? ''),
			]);
			cache()->deleteItemsByTag('domains?user_id=' . $this->user->user_id);
		}

		/* Update old main custom domain if needed */
		if(!$_POST['is_main_link'] && $link->domain_id && $domains[$link->domain_id]->link_id == $link->link_id) {
			/* Database query */
			db()->where('domain_id', $link->domain_id)->update('domains', [
				'link_id' => null,
				'last_datetime' => get_date(),
			]);

			/* Clear the cache */
			cache()->deleteItems([
				'domains?user_id=' . $this->user->user_id,
				'domain?domain_id=' . $link->domain_id,
				'domain?domain_id=' . $_POST['domain_id'],
				'domain?host=' . md5($domains[$link->domain_id]->host ?? ''),
				'domain?host=' . md5($domains[$_POST['domain_id']]->host ?? ''),
			]);
			cache()->deleteItemsByTag('domains?user_id=' . $this->user->user_id);
		}
	}
}
