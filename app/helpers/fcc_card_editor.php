<?php
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-12: the simple card editor.
 * Three steps (Profil, Sadržaj, Izgled) with an always visible live preview. Every save changes only the
 * fields it shows and keeps all other block settings, so the advanced editor stays fully compatible.
 * Forever buttons keep their automatic referral addresses; the editor never asks for them. */

/** Types the simple editor can edit field by field. Other types stay editable in the advanced editor. */
const FCC_CARD_EDITOR_TYPES = ['avatar', 'heading', 'paragraph', 'link_forever_webshop_reg', 'link_forever_shop', 'link_discount', 'custom_html_whatsapp', 'link_save_contact', 'socials', 'link', 'link_forever_product', 'custom_html_chatbot', 'youtube', 'vimeo', 'image', 'review', 'faq', 'divider'];

/** The small block library offered by "Dodaj blok", in the order of the guide. */
const FCC_CARD_EDITOR_LIBRARY = ['link_forever_webshop_reg', 'link_forever_shop', 'link_forever_product', 'custom_html_chatbot', 'link', 'custom_html_whatsapp', 'link_save_contact', 'socials', 'youtube', 'image', 'review', 'faq', 'heading', 'paragraph', 'divider'];

/** Blocks that need content before they can appear on the card; the editor creates them only on save. */
const FCC_CARD_EDITOR_NEEDS_FIELDS = ['link', 'link_forever_product', 'youtube', 'vimeo', 'image', 'review', 'faq', 'heading', 'paragraph', 'socials', 'custom_html_whatsapp'];

/** Social profiles offered in the simple form. Other networks already on a card are kept as they are. */
const FCC_CARD_EDITOR_SOCIALS = [
    'instagram' => ['label' => 'Instagram', 'icon' => 'fab fa-instagram', 'placeholder' => 'ime.profila', 'hosts' => ['instagram.com', 'www.instagram.com']],
    'facebook' => ['label' => 'Facebook', 'icon' => 'fab fa-facebook', 'placeholder' => 'ime.profila', 'hosts' => ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'fb.com']],
    'tiktok' => ['label' => 'TikTok', 'icon' => 'fab fa-tiktok', 'placeholder' => 'imeprofila', 'hosts' => ['tiktok.com', 'www.tiktok.com']],
    'youtube' => ['label' => 'YouTube', 'icon' => 'fab fa-youtube', 'placeholder' => '@kanal', 'hosts' => ['youtube.com', 'www.youtube.com', 'm.youtube.com']],
    'linkedin' => ['label' => 'LinkedIn', 'icon' => 'fab fa-linkedin', 'placeholder' => 'in/ime-prezime', 'hosts' => ['linkedin.com', 'www.linkedin.com']],
    'email' => ['label' => 'E-mail', 'icon' => 'fas fa-envelope', 'placeholder' => 'ime@primjer.hr', 'hosts' => []],
];

function fcc_card_editor_h(?string $text): string {
    return htmlspecialchars(html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8');
}

/** Plain text as the member typed it, for form fields. */
function fcc_card_editor_plain(?string $text): string {
    return trim(html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/** One line of text, stored the way the classic editor stores it. */
function fcc_card_editor_line($value, int $max): string {
    $value = preg_replace('/\s+/u', ' ', fcc_card_editor_plain(is_scalar($value) ? (string) $value : ''));
    return input_clean($value, $max);
}

function fcc_card_editor_guard(string $permission = 'update.biolinks_blocks'): void {
    if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access($permission)) throw new InvalidArgumentException(fcc_t('Nemaš dopuštenje za uređivanje ove kartice.'));
}

/** The member's own card with decoded settings. */
function fcc_card_editor_link(int $user_id, int $link_id): object {
    $link = db()->where('link_id', $link_id)->where('user_id', $user_id)->where('type', 'biolink')->getOne('links');
    if(!$link) throw new InvalidArgumentException(fcc_t('Kartica nije pronađena.'));
    $link->settings = fcc_card_settings($link->settings);
    return $link;
}

/** Where a page load of link/{id} goes: a new card opens this editor. The classic editor stays for classic
 * cards and for every deliberate classic address: classic=1, a tab, an editor view (the preview too) or a block
 * in the query, and the settings, statistics and download paths. Posts are never routed. */
function fcc_card_editor_route(object $link, array $params, array $query, string $request_method, bool $partner_enabled): ?string {
    if(!$partner_enabled || !in_array($request_method, ['GET', 'HEAD'], true)) return null;
    if(($link->type ?? '') !== 'biolink' || !fcc_card_is_v2($link)) return null;
    if(isset($params[1]) || array_intersect_key($query, array_flip(['classic', 'tab', 'editor', 'biolink_block_id'])) !== []) return null;
    return 'partner/card/' . (int) $link->link_id;
}

/** A message queued by the page that led here, such as a saved look, is shown once in this editor's toast. */
function fcc_card_editor_take_notice(): string {
    $messages = [];
    foreach(['success', 'info'] as $type) {
        foreach((array) session_get($type, []) as $group) foreach((array) $group as $message) $messages[] = trim(strip_tags((string) $message));
        session_unset_key($type);
    }
    return implode(' ', array_filter($messages));
}

/** After a look change in the classic editor a new card continues in this editor, a classic card stays. */
function fcc_card_design_return_path(int $link_id, bool $is_v2, string $action): string {
    if(!$is_v2) return 'link/' . $link_id . '?tab=settings&editor=design';
    return 'partner/card/' . $link_id . '?step=' . ($action === 'card_recommended' ? 'content' : 'design');
}

function fcc_card_editor_language(object $link, ?object $user = null): string {
    $code = (string) ($link->settings->language_code ?? ($user->language ?? 'hr'));
    return function_exists('fcc_locale') ? fcc_locale($code) : 'hr';
}

function fcc_card_editor_blocks(object $link): array {
    $rows = db()->where('link_id', (int) $link->link_id)->where('user_id', (int) $link->user_id)->orderBy('`order`', 'ASC')->orderBy('biolink_block_id', 'ASC')->get('biolinks_blocks') ?: [];
    foreach($rows as $row) $row->settings = fcc_card_settings($row->settings);
    return $rows;
}

function fcc_card_editor_plan_blocks(object $user): object {
    $blocks = $user->plan_settings->enabled_biolink_blocks ?? (object) [];
    if(is_string($blocks)) $blocks = json_decode($blocks) ?: (object) [];
    return is_object($blocks) ? $blocks : (object) $blocks;
}

/** Whether a block type may be added and shown on this card. */
function fcc_card_editor_type_allowed(string $type, object $plan_blocks, bool $is_v2): bool {
    if(in_array($type, ['link_forever_webshop_reg', 'link_forever_shop'], true)) return true;
    return fcc_card_block_open($type, $plan_blocks, $is_v2);
}

/** The first avatar, heading and paragraph form the profile at the top of the card. */
function fcc_card_editor_profile_ids(array $blocks): array {
    $ids = ['avatar' => 0, 'heading' => 0, 'paragraph' => 0];
    foreach($blocks as $block) {
        if(isset($ids[$block->type]) && !$ids[$block->type]) $ids[$block->type] = (int) $block->biolink_block_id;
    }
    return $ids;
}

/** Splits the intro into the bold role line and the text, as the profile form shows them. */
function fcc_card_editor_split_intro(string $html): array {
    $role = '';
    if(preg_match('~^\s*<p[^>]*>\s*<strong>(.*?)</strong>\s*</p>~su', $html, $match) && !preg_match('~<(?!/?(?:em|i|u)\b)[a-z]~i', $match[1])) {
        $role = fcc_card_editor_plain(strip_tags($match[1]));
        $html = substr($html, strlen($match[0]));
    }
    return [$role, fcc_card_editor_html_to_text($html)];
}

function fcc_card_editor_html_to_text(string $html): string {
    $html = preg_replace('~<br\s*/?>~i', "\n", $html);
    $html = preg_replace('~</(p|div|h[1-6]|li)>~i', "\n\n", $html);
    $text = fcc_card_editor_plain(strip_tags($html));
    return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace("/[ \t]+\n/", "\n", $text)));
}

/** Plain text to safe paragraphs: an empty line starts a new paragraph. */
function fcc_card_editor_text_to_html(string $text): string {
    $out = '';
    foreach(preg_split("/\n\s*\n/u", trim(str_replace("\r", '', $text))) as $paragraph) {
        $paragraph = trim($paragraph);
        if($paragraph === '') continue;
        $out .= '<p>' . str_replace("\n", '<br>', htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8')) . '</p>';
    }
    return $out;
}

/** Formatting the simple editor cannot keep (links, colours, lists). The form warns before saving. */
function fcc_card_editor_has_rich_text(string $html): bool {
    $body = preg_replace('~^\s*<p[^>]*>\s*<strong>.*?</strong>\s*</p>~su', '', $html);
    return (bool) preg_match('~<(?!/?(?:p|br)\b)[a-z]~i', (string) $body);
}

function fcc_card_editor_label(string $type): string {
    $catalog = fcc_card_block_catalog();
    if(isset($catalog[$type])) return fcc_t($catalog[$type]['title']);
    $label = l('link.biolink.blocks.' . $type);
    return is_string($label) && $label !== '' && !str_starts_with($label, 'link.') ? $label : $type;
}

/** A short line that tells the member which block this is. */
function fcc_card_editor_summary(object $block): string {
    $s = $block->settings;
    $text = match($block->type) {
        'heading' => (string) ($s->text ?? ''),
        'paragraph' => fcc_card_editor_html_to_text((string) ($s->text ?? '')),
        'custom_html_whatsapp' => (string) ($s->title ?? 'WhatsApp'),
        'socials' => implode(', ', array_map(static fn($key) => FCC_CARD_EDITOR_SOCIALS[$key]['label'] ?? ucfirst((string) $key), array_keys(array_filter((array) ($s->socials ?? []))))),
        'youtube', 'vimeo' => (string) (($s->title ?? '') ?: parse_url((string) $block->location_url, PHP_URL_HOST)),
        'review' => (string) ($s->author_name ?? ''),
        'faq' => fcc_t('Pitanja: {count}', ['{count}' => (string) count((array) ($s->items ?? []))]),
        'image' => (string) (($s->image_alt ?? '') ?: fcc_t('Slika')),
        'custom_html_chatbot' => fcc_t('Gumb „Pitaj savjetnika“'),
        'divider' => '',
        default => (string) ($s->name ?? ($s->title ?? '')),
    };
    $text = preg_replace('/\s+/u', ' ', fcc_card_editor_plain(strip_tags($text)));
    if(mb_strlen($text) > 3 && mb_strtoupper($text) === $text && mb_strtolower($text) !== $text) $text = mb_strtoupper(mb_substr(mb_strtolower($text), 0, 1)) . mb_substr(mb_strtolower($text), 1);
    return mb_strlen($text) > 70 ? mb_substr($text, 0, 68) . '…' : $text;
}

function fcc_card_editor_icon(string $type, array $known): string {
    return match($type) {
        'link_forever_webshop_reg', 'link_discount' => 'fas fa-shopping-bag',
        'link_forever_shop' => 'fas fa-handshake',
        'custom_html_whatsapp' => 'fab fa-whatsapp',
        'link_save_contact' => 'fas fa-address-book',
        'custom_html_chatbot' => 'fas fa-wand-magic-sparkles',
        default => (string) ($known[$type]['icon'] ?? 'fas fa-square'),
    };
}

/** Everything the editor page needs to draw the three steps. */
function fcc_card_editor_state(object $user, object $link): array {
    $blocks = fcc_card_editor_blocks($link);
    $profile_ids = fcc_card_editor_profile_ids($blocks);
    $is_v2 = fcc_card_is_v2($link);
    $plan = fcc_card_editor_plan_blocks($user);
    $known = require APP_PATH . 'includes/biolink_blocks.php';
    $by_id = [];
    foreach($blocks as $block) $by_id[(int) $block->biolink_block_id] = $block;

    $avatar = $profile_ids['avatar'] ? $by_id[$profile_ids['avatar']] : null;
    $heading = $profile_ids['heading'] ? $by_id[$profile_ids['heading']] : null;
    $paragraph = $profile_ids['paragraph'] ? $by_id[$profile_ids['paragraph']] : null;
    [$role, $about] = $paragraph ? fcc_card_editor_split_intro((string) ($paragraph->settings->text ?? '')) : ['', ''];

    $parts = [];
    foreach($blocks as $block) {
        $id = (int) $block->biolink_block_id;
        if(in_array($id, $profile_ids, true)) continue;
        $retired = in_array($block->type, FCC_CARD_RETIRED_BLOCKS, true);
        $allowed = fcc_card_editor_type_allowed($block->type, $plan, $is_v2) && !($is_v2 && $retired);
        $parts[] = [
            'id' => $id,
            'type' => $block->type,
            'label' => fcc_card_editor_label($block->type),
            'summary' => fcc_card_editor_summary($block),
            'icon' => fcc_card_editor_icon($block->type, $known),
            'enabled' => (int) $block->is_enabled === 1,
            'shown' => (int) $block->is_enabled === 1 && $allowed,
            'locked' => !$allowed,
            'retired' => $retired,
            'editable' => in_array($block->type, FCC_CARD_EDITOR_TYPES, true),
        ];
    }

    $library = [];
    $existing_types = array_map(static fn($block) => $block->type, $blocks);
    foreach(FCC_CARD_EDITOR_LIBRARY as $type) {
        if(!isset($known[$type])) continue;
        $single = in_array($type, ['link_forever_webshop_reg', 'link_forever_shop', 'custom_html_chatbot', 'link_save_contact', 'socials'], true);
        $library[] = [
            'type' => $type,
            'item' => fcc_card_block_catalog()[$type] ?? ['group' => 'about', 'title' => fcc_card_editor_label($type), 'what' => '', 'how' => ''],
            'icon' => fcc_card_editor_icon($type, $known),
            'open' => fcc_card_editor_type_allowed($type, $plan, $is_v2),
            'exists' => $single && in_array($type, $existing_types, true),
            'needs_fields' => in_array($type, FCC_CARD_EDITOR_NEEDS_FIELDS, true),
        ];
    }

    return [
        'is_v2' => $is_v2,
        'is_pro' => fcc_card_user_is_pro((int) $user->user_id),
        'theme' => fcc_card_theme($link, (int) $user->user_id),
        'style' => fcc_card_style_clean($link->settings->fcc_card_style ?? []),
        'profile' => [
            'avatar_id' => $profile_ids['avatar'],
            'heading_id' => $profile_ids['heading'],
            'paragraph_id' => $profile_ids['paragraph'],
            'photo' => $avatar && !empty($avatar->settings->image) ? get_biolink_avatar_url($avatar->settings) : '',
            'name' => fcc_card_editor_plain((string) ($heading->settings->text ?? '')),
            'role' => $role,
            'about' => $about,
            'rich_text' => $paragraph && fcc_card_editor_has_rich_text((string) ($paragraph->settings->text ?? '')),
        ],
        'parts' => $parts,
        'library' => $library,
        'missing' => $is_v2 ? fcc_card_missing_recommended((int) $user->user_id, (int) $link->link_id) : [],
        'blocks_limit' => (int) ($user->plan_settings->biolink_blocks_limit ?? -1),
        'blocks_total' => count($blocks),
    ];
}

/** Look options the advanced part of a block form offers, by block type. */
function fcc_card_editor_style_fields(string $type): array {
    return match($type) {
        'link', 'link_forever_webshop_reg', 'link_forever_shop', 'link_discount', 'link_save_contact', 'custom_html_whatsapp' => ['look', 'icon'],
        'heading' => ['size', 'align'],
        'paragraph' => ['frame', 'align'],
        'image', 'youtube', 'vimeo' => ['corners'],
        'socials' => ['icons'],
        'divider' => ['line', 'space'],
        default => [],
    };
}

/** Classic card: colours and shapes the classic editor offered for a block, kept until everyone moves to the new card. */
function fcc_card_editor_classic_fields(string $type): array {
    return match($type) {
        'link', 'link_forever_webshop_reg', 'link_forever_shop', 'link_discount', 'link_save_contact', 'custom_html_whatsapp', 'link_forever_product' => ['text_color', 'background_color', 'border_radius', 'border_width', 'border_color', 'border_shadow_style', 'border_shadow_color'],
        'heading' => ['text_color', 'text_alignment'],
        'paragraph' => ['text_color', 'background_color', 'text_alignment'],
        'socials' => ['color', 'background_color'],
        'divider' => ['background_color'],
        'faq' => ['text_color', 'background_color'],
        default => [],
    };
}

const FCC_CARD_CLASSIC_CHOICES = [
    'border_radius' => ['rounded', 'round', 'straight'],
    'border_shadow_style' => ['none', 'subtle', 'strong', 'hard'],
    'text_alignment' => ['center', 'left', 'right'],
];

/** A stored colour for a colour input (#RRGGBB) and whether it is fully transparent. */
function fcc_card_editor_color_parts(?string $stored, string $fallback = '#000000'): array {
    $stored = trim((string) $stored);
    $transparent = (bool) preg_match('/^#[0-9a-f]{6}00$/i', $stored) || $stored === 'transparent';
    $hex = preg_match('/^#([0-9a-f]{6})/i', $stored, $m) ? '#' . mb_strtoupper($m[1]) : (fcc_card_hex($stored) ?: $fallback);
    return [$hex, $transparent];
}

/** Field values for one block's form, already decoded for the inputs. */
function fcc_card_editor_fields(object $block, ?object $owner = null): array {
    $s = $block->settings;
    $fields = match($block->type) {
        'heading' => ['text' => fcc_card_editor_plain((string) ($s->text ?? ''))],
        'paragraph' => ['text' => fcc_card_editor_html_to_text((string) ($s->text ?? '')), 'rich_text' => fcc_card_editor_has_rich_text((string) ($s->text ?? ''))],
        'link_forever_webshop_reg', 'link_forever_shop', 'link_discount', 'link_save_contact' => ['name' => fcc_card_editor_plain((string) ($s->name ?? ''))],
        'custom_html_whatsapp' => ['title' => fcc_card_editor_plain((string) ($s->title ?? '')), 'message' => fcc_card_editor_plain((string) ($s->message ?? '')), 'phone' => fcc_card_editor_whatsapp_display((string) ($s->phone ?? ''), $owner)],
        'link' => ['name' => fcc_card_editor_plain((string) ($s->name ?? '')), 'url' => (string) $block->location_url],
        'youtube', 'vimeo' => ['url' => (string) $block->location_url, 'title' => fcc_card_editor_plain((string) ($s->title ?? ''))],
        'image' => ['url' => (string) $block->location_url, 'photo' => !empty($s->image) ? \Altum\Uploads::get_full_url('block_images') . $s->image : '', 'alt' => fcc_card_editor_plain((string) ($s->image_alt ?? ''))],
        'review' => ['author_name' => fcc_card_editor_plain((string) ($s->author_name ?? '')), 'description' => fcc_card_editor_plain((string) ($s->description ?? '')), 'stars' => (int) ($s->stars ?? 5)],
        'faq' => ['items' => array_map(static fn($item) => ['title' => fcc_card_editor_plain((string) ($item->title ?? '')), 'content' => fcc_card_editor_plain((string) ($item->content ?? ''))], array_values((array) ($s->items ?? [])))],
        'socials' => ['socials' => array_map(static fn($value) => fcc_card_editor_plain((string) $value), (array) ($s->socials ?? []))],
        'link_forever_product' => ['product_id' => (int) ($s->product_blog_post_id ?? 0), 'product_name' => fcc_card_editor_plain((string) ($s->name ?? ''))],
        'avatar' => ['photo' => !empty($s->image) ? get_biolink_avatar_url($s) : ''],
        'divider' => ['icon' => (string) ($s->icon ?? '')],
        default => [],
    };
    foreach(fcc_card_editor_classic_fields($block->type) as $key) {
        if(isset(FCC_CARD_CLASSIC_CHOICES[$key])) {
            $value = (string) ($s->{$key} ?? '');
            $fields['classic_' . $key] = in_array($value, FCC_CARD_CLASSIC_CHOICES[$key], true) ? $value : FCC_CARD_CLASSIC_CHOICES[$key][0];
        } elseif($key === 'border_width') {
            $fields['classic_border_width'] = (string) max(0, min(5, (int) ($s->border_width ?? 0)));
        } else {
            [$hex, $transparent] = fcc_card_editor_color_parts($s->{$key} ?? null, $key === 'background_color' ? '#FFFFFF' : '#000000');
            $fields['classic_' . $key] = $hex;
            if($key === 'background_color') $fields['classic_background_color_none'] = $transparent;
        }
    }
    $look = fcc_card_block_style_clean($s->fcc_style ?? []);
    foreach(fcc_card_editor_style_fields($block->type) as $key) {
        $fields['style_' . $key] = $key === 'icon' ? (string) ($s->icon ?? '') : ($look[$key] ?? FCC_CARD_BLOCK_STYLE_OPTIONS[$key][0]);
    }
    return $fields;
}

/** The WhatsApp number for the form: the block's number, otherwise the registration number, as +385 91 123 4567. */
function fcc_card_editor_whatsapp_display(string $phone, ?object $owner): string {
    $countries = $owner ? fc_user_phone_countries($owner) : ['HR'];
    $normalized = trim($phone) !== '' ? fc_normalize_whatsapp_phone($phone, $countries) : '';
    if($normalized === '' && trim($phone) !== '') return $phone;
    if($normalized === '' && $owner) $normalized = fc_get_user_default_whatsapp_phone($owner);
    return fc_format_whatsapp_phone($normalized);
}

/** Settings for a new block, close to what the classic editor creates. */
function fcc_card_editor_defaults(string $type, object $user, string $language): array {
    $box = ['border_shadow_style' => 'subtle', 'border_shadow_color' => '#00000010', 'border_width' => 0, 'border_style' => 'solid', 'border_color' => '#ffffff', 'border_radius' => 'rounded'];
    $button = ['name' => '', 'open_in_new_tab' => false, 'text_color' => '#000000', 'text_alignment' => 'center', 'background_color' => '#ffffff', 'animation' => false, 'animation_runs' => 'repeat-1', 'icon' => '', 'image' => '', 'sensitive_content' => false, 'columns' => 1] + $box;
    return match($type) {
        'avatar' => ['image' => null, 'image_alt' => '', 'use_default_avatar' => true, 'size' => 125, 'border_radius' => 'round', 'object_fit' => 'cover', 'border_width' => 0, 'border_style' => 'solid', 'border_color' => '#ffffff', 'border_shadow_style' => 'none', 'border_shadow_color' => '#00000000', 'open_in_new_tab' => 0],
        'heading' => ['heading_type' => 'h2', 'text' => '', 'text_color' => '#ffffff', 'text_alignment' => 'center', 'verified_location' => ''],
        'paragraph' => ['text' => '', 'text_color' => '#ffffff', 'background_color' => '#00000000', 'border_radius' => 'rounded', 'border_shadow_style' => 'none', 'border_shadow_color' => '#00000000', 'border_width' => 0, 'border_style' => 'solid', 'border_color' => '#ffffff', 'text_alignment' => 'center'],
        'link' => $button,
        'link_forever_product' => $button + ['description' => '', 'product_blog_post_id' => 0, 'product_translation_key' => '', 'product_language_mode' => 'app', 'product_language_code' => $language, 'product_fallback_language_code' => 'hr', 'product_image_url' => ''],
        'custom_html_whatsapp' => ['title' => 'WhatsApp', 'phone' => '', 'message' => fcc_t('Bok! Javljam se preko tvoje FCC kartice. Zanimaju me Forever proizvodi.', [], $language), 'icon' => 'fab fa-whatsapp', 'border_radius' => 'rounded'],
        'link_save_contact' => ['name' => fcc_t('Spremi kontakt', [], $language), 'icon' => 'fas fa-address-book', 'open_in_new_tab' => false, 'border_radius' => 'rounded', 'image' => ''],
        'socials' => ['color' => '#ffffff', 'background_color' => '#FFFFFF00', 'socials' => (object) [], 'size' => 'm', 'border_radius' => 'rounded'],
        'youtube' => $box + ['title' => '', 'text_color' => '#F8FAFC', 'font_size' => 20, 'video_autoplay' => false, 'video_controls' => true, 'video_loop' => false, 'video_muted' => false],
        'vimeo' => $box + ['title' => '', 'text_color' => '#F8FAFC', 'font_size' => 20],
        'image' => ['image' => null, 'image_alt' => null, 'open_in_new_tab' => false] + $box,
        'review' => ['title' => '', 'description' => '', 'author_name' => '', 'author_description' => '', 'image' => null, 'stars' => 5, 'title_color' => '#000000', 'description_color' => '#000000', 'author_name_color' => '#000000', 'author_description_color' => '#000000', 'stars_color' => '#FFDF00', 'text_alignment' => 'left', 'background_color' => '#FFFFFF'] + $box,
        'faq' => ['items' => [], 'text_color' => '#000000', 'text_alignment' => 'center', 'background_color' => '#ffffff'] + $box,
        'divider' => ['margin_top' => 3, 'margin_bottom' => 3, 'background_color' => '#ffffff', 'icon' => 'fas fa-leaf'],
        'custom_html_chatbot' => ['html' => ''],
        default => [],
    };
}

/** Files of the change in progress. A new upload is removed if the change fails; a replaced or
 * removed photo is deleted only after the whole change has been saved. */
function fcc_card_editor_files(string $action, ?string $name = null, string $folder = ''): array {
    static $files = ['new' => [], 'old' => []];
    $snapshot = $files;
    // Reset hands back what was recorded before it empties the list.
    if($action === 'reset') $files = ['new' => [], 'old' => []];
    elseif(in_array($action, ['new', 'old'], true) && !empty($name) && basename($name) === $name) { $files[$action][] = [$name, $folder]; $snapshot = $files; }
    return $snapshot;
}

function fcc_card_editor_files_finish(bool $saved): void {
    $files = fcc_card_editor_files('reset');
    foreach($saved ? $files['old'] : $files['new'] as [$name, $folder]) {
        try { fcc_card_editor_remove_upload($name, $folder); } catch(\Throwable $e) { error_log('Card editor file cleanup: ' . get_class($e)); }
    }
}

/** Stores an uploaded photo the same way the classic editor does. Returns the stored file name. */
function fcc_card_editor_upload(string $field, ?string $existing, string $folder, float $size_limit_mb): ?string {
    $file = $_FILES[$field] ?? null;
    if(!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($file['name'])) return $existing;
    $too_big = fcc_t('Fotografija je prevelika. Najveća veličina je {size} MB.', ['{size}' => rtrim(rtrim(number_format($size_limit_mb, 1, '.', ''), '0'), '.')]);
    if($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || (int) $file['size'] > $size_limit_mb * 1000000) throw new InvalidArgumentException($too_big);
    if($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) throw new InvalidArgumentException(fcc_t('Fotografija nije učitana. Pokušaj ponovno.'));
    $extension = mb_strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if($extension === 'jpeg') $extension = 'jpg';
    if(!in_array($extension, ['jpg', 'png', 'webp', 'gif', 'avif'], true) || !@getimagesize($file['tmp_name'])) throw new InvalidArgumentException(fcc_t('Odaberi fotografiju u obliku JPG, PNG ili WEBP.'));
    $name = md5(uniqid('', true) . random_bytes(16)) . '.' . $extension;

    if(\Altum\Plugin::is_active('image-optimizer') && settings()->image_optimizer->is_enabled) {
        \Altum\Plugin\ImageOptimizer::optimize($file['tmp_name'], $name, $file['name'], $folder);
    }

    if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
        $s3 = new \Aws\S3\S3Client(get_aws_s3_config());
        $s3->putObject(['Bucket' => settings()->offload->storage_name, 'Key' => UPLOADS_URL_PATH . $folder . $name, 'ContentType' => mime_content_type($file['tmp_name']), 'SourceFile' => $file['tmp_name'], 'ACL' => 'public-read']);
        fcc_card_editor_files('new', $name, $folder);
        if(!empty($existing) && $existing !== $name) fcc_card_editor_files('old', $existing, $folder);
        return $name;
    }

    if(!is_writable(UPLOADS_PATH . $folder)) throw new InvalidArgumentException(fcc_t('Fotografija nije učitana. Pokušaj ponovno.'));
    if(!move_uploaded_file($file['tmp_name'], UPLOADS_PATH . $folder . $name)) throw new InvalidArgumentException(fcc_t('Fotografija nije učitana. Pokušaj ponovno.'));
    fcc_card_editor_files('new', $name, $folder);
    if(!empty($existing) && $existing !== $name) fcc_card_editor_files('old', $existing, $folder);
    return $name;
}

function fcc_card_editor_remove_upload(?string $existing, string $folder): void {
    if(empty($existing) || basename($existing) !== $existing) return;
    if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
        (new \Aws\S3\S3Client(get_aws_s3_config()))->deleteObject(['Bucket' => settings()->offload->storage_name, 'Key' => UPLOADS_URL_PATH . $folder . $existing]);
        return;
    }
    if(is_file(UPLOADS_PATH . $folder . $existing)) @unlink(UPLOADS_PATH . $folder . $existing);
}

/** A web address for a button or a photo. Forever addresses get the member's referral when the card is shown. */
function fcc_card_editor_url($value, bool $required, object $user): string {
    $raw = trim(is_scalar($value) ? (string) $value : '');
    if($raw === '') {
        if($required) throw new InvalidArgumentException(fcc_t('Upiši poveznicu.'));
        return '';
    }
    if(!preg_match('~^[a-z][a-z0-9+.-]*://~i', $raw)) $raw = 'https://' . $raw;
    $url = get_url($raw);
    $scheme = mb_strtolower((string) parse_url($url, PHP_URL_SCHEME));
    $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));
    if($url === '' || !in_array($scheme, ['http', 'https'], true) || $host === '' || !str_contains($host, '.')) throw new InvalidArgumentException(fcc_t('Poveznica nije ispravna. Primjer: https://www.primjer.hr'));
    $domain = get_domain_from_url($url);
    if($domain && in_array($domain, (array) (settings()->links->blacklisted_domains ?? []), true)) throw new InvalidArgumentException(fcc_t('Ova poveznica nije dopuštena.'));
    $forever = (bool) preg_match('~(^|\.)(foreverliving\.com|forevercard\.club|forever[a-z]*\.[a-z]{2,})$~', $host);
    if(!$forever && !empty(settings()->links->google_safe_browsing_is_enabled) && google_safe_browsing_check($url, settings()->links->google_safe_browsing_api_key)) throw new InvalidArgumentException(fcc_t('Ova poveznica nije dopuštena.'));
    return $url;
}

/** Video address in the form the YouTube and Vimeo blocks accept. */
function fcc_card_editor_video_url($value, string $type, object $user): string {
    $url = fcc_card_editor_url($value, true, $user);
    $parts = parse_url($url);
    $host = mb_strtolower((string) ($parts['host'] ?? ''));
    if($type === 'youtube') {
        if(in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com'], true)) $host = 'www.youtube.com';
        if(!in_array($host, ['www.youtube.com', 'youtu.be'], true)) throw new InvalidArgumentException(fcc_t('Zalijepi poveznicu YouTube videa, primjerice https://youtu.be/abc123.'));
    } else {
        if($host === 'www.vimeo.com') $host = 'vimeo.com';
        if($host !== 'vimeo.com') throw new InvalidArgumentException(fcc_t('Zalijepi poveznicu Vimeo videa, primjerice https://vimeo.com/123456.'));
    }
    return 'https://' . $host . ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
}

/** A social profile as the socials block stores it: the part after the network's address. */
function fcc_card_editor_social_value(string $network, $value): string {
    $value = trim(fcc_card_editor_plain(is_scalar($value) ? (string) $value : ''));
    if($value === '') return '';
    if($network === 'email') {
        $email = input_clean_email($value);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException(fcc_t('E-mail adresa nije ispravna.'));
        return $email;
    }
    if(preg_match('~^(?:https?://)?([^/]+)/(.*)$~i', $value, $match) && in_array(mb_strtolower($match[1]), FCC_CARD_EDITOR_SOCIALS[$network]['hosts'], true)) {
        $value = $match[2];
    }
    $value = preg_replace('~[?#].*$~', '', $value);
    $value = trim($value, " /");
    if($network === 'tiktok') $value = ltrim($value, '@');
    if(in_array($network, ['instagram', 'facebook'], true)) $value = ltrim($value, '@');
    if($network === 'youtube' && !str_contains($value, '/') && !str_starts_with($value, '@')) $value = '@' . $value;
    if($network === 'linkedin' && !str_contains($value, '/')) $value = 'in/' . $value;
    $value = input_clean($value, 128);
    if($value === '' || preg_match('~\s~u', $value)) throw new InvalidArgumentException(fcc_t('Profil {network} nije ispravan. Upiši samo ime profila.', ['{network}' => FCC_CARD_EDITOR_SOCIALS[$network]['label']]));
    return $value;
}

function fcc_card_editor_next_order(object $link): int {
    $max = db()->where('link_id', (int) $link->link_id)->getValue('biolinks_blocks', 'MAX(`order`)');
    return (int) $max + 1;
}

/** New blocks go just above the business partnership button when it is last, otherwise to the end. */
function fcc_card_editor_insert_position(object $link): int {
    $blocks = fcc_card_editor_blocks($link);
    $last = end($blocks);
    if($last && $last->type === 'link_forever_shop') {
        $position = (int) $last->order;
        db()->where('biolink_block_id', (int) $last->biolink_block_id)->update('biolinks_blocks', ['order' => $position + 1]);
        return $position;
    }
    return fcc_card_editor_next_order($link);
}

function fcc_card_editor_check_limit(object $user, object $link): void {
    $limit = (int) ($user->plan_settings->biolink_blocks_limit ?? -1);
    if($limit === -1) return;
    $total = (int) db()->where('link_id', (int) $link->link_id)->getValue('biolinks_blocks', 'COUNT(*)');
    if($total >= $limit) throw new InvalidArgumentException(fcc_t('Kartica ima najveći broj blokova za tvoj paket ({limit}). Ukloni blok koji ne koristiš.', ['{limit}' => (string) $limit]));
}

function fcc_card_editor_save_block(object $block, array $settings, ?string $location_url = null, bool $set_location = false): void {
    $update = ['settings' => json_encode($settings), 'last_datetime' => gmdate('Y-m-d H:i:s')];
    if($set_location) $update['location_url'] = $location_url;
    // A failed write must fail the change, so a replaced photo is not deleted while the block still uses it.
    if(db()->where('biolink_block_id', (int) $block->biolink_block_id)->where('user_id', (int) $block->user_id)->update('biolinks_blocks', $update) === false) throw new RuntimeException('Card block was not saved.');
}

/** Applies one block form. For a new block ($block->biolink_block_id = 0) it inserts the block. Returns the block id. */
function fcc_card_editor_apply_fields(object $user, object $link, object $block, array $input): int {
    $language = fcc_card_editor_language($link, $user);
    $settings = (array) $block->settings;
    $location = $block->location_url ?? null;
    $set_location = false;
    $type = $block->type;

    switch($type) {
        case 'heading':
            $settings['text'] = fcc_card_editor_line($input['text'] ?? '', 128);
            if($settings['text'] === '') throw new InvalidArgumentException(fcc_t('Upiši naslov.'));
            break;
        case 'paragraph':
            $text = mb_substr(trim(str_replace("\r", '', (string) ($input['text'] ?? ''))), 0, 1500);
            if($text === '') throw new InvalidArgumentException(fcc_t('Upiši tekst.'));
            $settings['text'] = fcc_card_editor_text_to_html(strip_tags($text));
            break;
        case 'link_forever_webshop_reg':
        case 'link_forever_shop':
        case 'link_discount':
        case 'link_save_contact':
            $settings['name'] = fcc_card_editor_line($input['name'] ?? '', 64);
            if($settings['name'] === '') throw new InvalidArgumentException(fcc_t('Upiši natpis gumba.'));
            break;
        case 'custom_html_whatsapp':
            $settings['title'] = fcc_card_editor_line($input['title'] ?? '', 64) ?: 'WhatsApp';
            $settings['message'] = mb_substr(trim(strip_tags((string) ($input['message'] ?? ''))), 0, 300);
            // Stored in the form WhatsApp needs: country code and number, digits only.
            $typed = trim((string) ($input['phone'] ?? ''));
            $phone = $typed !== '' ? fc_normalize_whatsapp_phone($typed, fc_user_phone_countries($user)) : fc_get_user_default_whatsapp_phone($user);
            if($typed !== '' && $phone === '') throw new InvalidArgumentException(fcc_t('Broj nije ispravan. Upiši ga s pozivnim brojem zemlje, primjerice 385 91 123 4567.'));
            if($phone === '') throw new InvalidArgumentException(fcc_t('Upiši broj za WhatsApp ili ga dodaj u svoj profil.'));
            $settings['phone'] = $phone;
            break;
        case 'link':
            $settings['name'] = fcc_card_editor_line($input['name'] ?? '', 64);
            if($settings['name'] === '') throw new InvalidArgumentException(fcc_t('Upiši natpis gumba.'));
            $location = fcc_card_editor_url($input['url'] ?? '', true, $user);
            $set_location = true;
            break;
        case 'youtube':
        case 'vimeo':
            $location = fcc_card_editor_video_url($input['url'] ?? '', $type, $user);
            $set_location = true;
            $settings['title'] = fcc_card_editor_line($input['title'] ?? '', 120);
            break;
        case 'image':
        case 'avatar':
            $folder = $type === 'avatar' ? 'avatars/' : 'block_images/';
            $limit = (float) ($type === 'avatar' ? (settings()->links->avatar_size_limit ?? 2) : (settings()->links->image_size_limit ?? 2));
            // Everything else is checked first, so a refused form never touches the stored photo.
            if($type === 'image') {
                $settings['image_alt'] = fcc_card_editor_line($input['alt'] ?? '', 100);
                $location = fcc_card_editor_url($input['url'] ?? '', false, $user);
                $set_location = true;
            }
            $settings['image'] = fcc_card_editor_upload('photo', $settings['image'] ?? null, $folder, $limit);
            if(empty($settings['image'])) throw new InvalidArgumentException(fcc_t('Odaberi fotografiju.'));
            if($type === 'avatar') $settings['use_default_avatar'] = false;
            break;
        case 'review':
            $settings['author_name'] = fcc_card_editor_line($input['author_name'] ?? '', 64);
            $settings['description'] = mb_substr(trim(strip_tags((string) ($input['description'] ?? ''))), 0, 600);
            $settings['description'] = input_clean($settings['description'], 600);
            $settings['stars'] = max(1, min(5, (int) ($input['stars'] ?? 5)));
            if($settings['author_name'] === '' || $settings['description'] === '') throw new InvalidArgumentException(fcc_t('Upiši ime osobe i njezino iskustvo.'));
            break;
        case 'faq':
            $items = [];
            $titles = (array) ($input['item_title'] ?? []);
            $contents = (array) ($input['item_content'] ?? []);
            foreach(array_slice($titles, 0, 6, true) as $key => $title) {
                $title = fcc_card_editor_line($title, 128);
                $content = input_clean(mb_substr(trim(strip_tags((string) ($contents[$key] ?? ''))), 0, 1000), 1000);
                if($title === '' && $content === '') continue;
                if($title === '' || $content === '') throw new InvalidArgumentException(fcc_t('Svako pitanje treba i odgovor.'));
                $items[] = ['title' => $title, 'content' => $content];
            }
            if(!$items) throw new InvalidArgumentException(fcc_t('Dodaj barem jedno pitanje s odgovorom.'));
            $settings['items'] = $items;
            break;
        case 'socials':
            $socials = (array) ($settings['socials'] ?? []);
            foreach(array_keys(FCC_CARD_EDITOR_SOCIALS) as $network) {
                $value = fcc_card_editor_social_value($network, $input['socials'][$network] ?? '');
                if($value === '') unset($socials[$network]); else $socials[$network] = $value;
            }
            if(!array_filter($socials)) throw new InvalidArgumentException(fcc_t('Upiši barem jedan profil.'));
            $settings['socials'] = (object) $socials;
            break;
        case 'link_forever_product':
            $previous = $block->biolink_block_id ? (object) $settings : null;
            $normalized = fcc_product_normalize_submission([
                'product_blog_post_id' => (int) ($input['product_id'] ?? 0),
                'product_translation_key' => '',
                'product_language_mode' => 'app',
                'product_fallback_language_code' => 'hr',
                'name' => (int) ($input['product_id'] ?? 0) === (int) ($settings['product_blog_post_id'] ?? 0) ? (string) ($settings['name'] ?? '') : '',
                'description' => (int) ($input['product_id'] ?? 0) === (int) ($settings['product_blog_post_id'] ?? 0) ? (string) ($settings['description'] ?? '') : '',
            ], $link, $previous);
            if(!$normalized || empty($normalized['location_url'])) throw new InvalidArgumentException(fcc_t('Odaberi proizvod.'));
            foreach(['name', 'description', 'product_blog_post_id', 'product_translation_key', 'product_language_mode', 'product_fallback_language_code', 'product_image_url', 'product_name_mode', 'product_description_mode', 'product_name_auto_value', 'product_description_auto_value'] as $key) {
                if(array_key_exists($key, $normalized)) $settings[$key] = $normalized[$key];
            }
            $location = $normalized['location_url'];
            $set_location = true;
            break;
        case 'divider':
            // The sign in the middle of the line, for the new and the classic card alike.
            if(array_key_exists('icon', $input)) {
                $icon = (string) $input['icon'];
                if(array_key_exists($icon, FCC_CARD_DIVIDER_ICONS) || $icon === (string) ($settings['icon'] ?? '')) $settings['icon'] = $icon;
            }
            break;
        case 'custom_html_chatbot':
            break;
        default:
            throw new InvalidArgumentException(fcc_t('Ovaj blok uređuješ u naprednom uređivaču.'));
    }

    // Colours and shapes of the classic card. A colour that was not changed keeps its stored form (for example its transparency).
    if(isset($input['classic_present'])) {
        foreach(fcc_card_editor_classic_fields($type) as $key) {
            if(isset(FCC_CARD_CLASSIC_CHOICES[$key])) {
                $value = (string) ($input['classic_' . $key] ?? '');
                if(in_array($value, FCC_CARD_CLASSIC_CHOICES[$key], true)) $settings[$key] = $value;
            } elseif($key === 'border_width') {
                $settings['border_width'] = max(0, min(5, (int) ($input['classic_border_width'] ?? ($settings['border_width'] ?? 0))));
            } elseif($key === 'background_color' && !empty($input['classic_background_color_none'])) {
                $settings['background_color'] = '#00000000';
            } else {
                $hex = fcc_card_hex((string) ($input['classic_' . $key] ?? ''));
                if($hex === '') continue;
                [$stored_hex, $stored_transparent] = fcc_card_editor_color_parts($settings[$key] ?? null);
                if($hex === $stored_hex && !($key === 'background_color' && $stored_transparent)) continue;
                $settings[$key] = $hex;
            }
        }
    }

    // The block's own look on the new card. Only options this block type offers are read.
    $look_keys = fcc_card_editor_style_fields($type);
    if($look_keys && array_intersect_key($input, array_flip(array_map(static fn($key) => 'style_' . $key, $look_keys)))) {
        $look = [];
        foreach($look_keys as $key) {
            if($key === 'icon') {
                $icon = (string) ($input['style_icon'] ?? '');
                if(array_key_exists($icon, FCC_CARD_BUTTON_ICONS)) $settings['icon'] = $icon;
                continue;
            }
            $look[$key] = (string) ($input['style_' . $key] ?? '');
        }
        $look = fcc_card_block_style_clean($look);
        if($look) $settings['fcc_style'] = $look; else unset($settings['fcc_style']);
    }

    if(!$block->biolink_block_id) {
        fcc_card_editor_check_limit($user, $link);
        $order = fcc_card_editor_insert_position($link);
        $id = fcc_card_block_insert((int) $user->user_id, (int) $link->link_id, $type, $settings, $set_location ? $location : null, $order);
        if($id <= 0) throw new RuntimeException('Card block was not added.');
        return $id;
    }
    fcc_card_editor_save_block($block, $settings, $location, $set_location);
    return (int) $block->biolink_block_id;
}

/** Saves the profile: photo, name, role line and intro. Missing profile blocks are created at the top. */
function fcc_card_editor_save_profile(object $user, object $link, array $input): void {
    $language = fcc_card_editor_language($link, $user);
    $blocks = fcc_card_editor_blocks($link);
    $ids = fcc_card_editor_profile_ids($blocks);
    $by_id = [];
    foreach($blocks as $block) $by_id[(int) $block->biolink_block_id] = $block;

    $name = fcc_card_editor_line($input['name'] ?? '', 64);
    if($name === '') throw new InvalidArgumentException(fcc_t('Upiši svoje ime.'));
    $role = fcc_card_editor_line($input['role'] ?? '', 80);
    $about = mb_substr(trim(strip_tags(str_replace("\r", '', (string) ($input['about'] ?? '')))), 0, 600);
    $intro = ($role !== '' ? '<p><strong>' . htmlspecialchars(fcc_card_editor_plain($role), ENT_QUOTES, 'UTF-8') . '</strong></p>' : '') . fcc_card_editor_text_to_html($about);

    $has_photo = !empty($_FILES['photo']['name']);
    $remove_photo = !empty($input['photo_remove']) && !$has_photo;
    $created = false;

    // Photo
    if($ids['avatar']) {
        $avatar = $by_id[$ids['avatar']];
        $settings = (array) $avatar->settings;
        if($has_photo) {
            $settings['image'] = fcc_card_editor_upload('photo', $settings['image'] ?? null, 'avatars/', (float) (settings()->links->avatar_size_limit ?? 2));
            $settings['use_default_avatar'] = false;
        } elseif($remove_photo) {
            fcc_card_editor_files('old', $settings['image'] ?? null, 'avatars/');
            $settings['image'] = null;
            $settings['use_default_avatar'] = true;
        }
        if($has_photo || $remove_photo) fcc_card_editor_save_block($avatar, $settings);
    } else {
        $settings = fcc_card_editor_defaults('avatar', $user, $language);
        if($has_photo) {
            $settings['image'] = fcc_card_editor_upload('photo', null, 'avatars/', (float) (settings()->links->avatar_size_limit ?? 2));
            $settings['use_default_avatar'] = false;
        }
        if(fcc_card_block_insert((int) $user->user_id, (int) $link->link_id, 'avatar', $settings, null, -3) <= 0) throw new RuntimeException('Card photo was not added.');
        $created = true;
    }

    // Name
    if($ids['heading']) {
        $settings = (array) $by_id[$ids['heading']]->settings;
        $settings['text'] = $name;
        fcc_card_editor_save_block($by_id[$ids['heading']], $settings);
    } else {
        if(fcc_card_block_insert((int) $user->user_id, (int) $link->link_id, 'heading', ['text' => $name] + fcc_card_editor_defaults('heading', $user, $language), null, -2) <= 0) throw new RuntimeException('Card name was not added.');
        $created = true;
    }

    // Role line and intro
    if($ids['paragraph']) {
        $settings = (array) $by_id[$ids['paragraph']]->settings;
        if($intro === '') {
            (new \Altum\Models\BiolinkBlock())->delete($ids['paragraph']);
        } else {
            $settings['text'] = $intro;
            fcc_card_editor_save_block($by_id[$ids['paragraph']], $settings);
        }
    } elseif($intro !== '') {
        if(fcc_card_block_insert((int) $user->user_id, (int) $link->link_id, 'paragraph', ['text' => $intro] + fcc_card_editor_defaults('paragraph', $user, $language), null, -1) <= 0) throw new RuntimeException('Card introduction was not added.');
        $created = true;
    }

    if($created) fcc_card_editor_normalize_order($link);
}

/** Colours and details inside the chosen style. Own colours need PRO, like the extra styles. */
function fcc_card_editor_style_input(array $input, bool $is_pro): array {
    $style = [];
    foreach(['accent', 'background'] as $color) {
        if($is_pro && !empty($input[$color . '_on'])) $style[$color] = (string) ($input[$color] ?? '');
    }
    foreach(array_keys(FCC_CARD_STYLE_OPTIONS) as $key) $style[$key] = (string) ($input['style_' . $key] ?? '');
    return fcc_card_style_clean($style);
}

function fcc_card_editor_save_style(object $user, int $link_id, array $input): void {
    $link = fcc_card_editor_link((int) $user->user_id, $link_id);
    $settings = $link->settings;
    $style = fcc_card_editor_style_input($input, fcc_card_user_is_pro((int) $user->user_id));
    if($style) $settings->fcc_card_style = $style; else unset($settings->fcc_card_style);
    db()->where('link_id', $link_id)->where('user_id', (int) $user->user_id)->update('links', ['settings' => json_encode($settings)]);
}

/** Gives the blocks consecutive positions in their current order. */
function fcc_card_editor_normalize_order(object $link): void {
    foreach(array_values(fcc_card_editor_blocks($link)) as $position => $block) {
        if((int) $block->order !== $position) db()->where('biolink_block_id', (int) $block->biolink_block_id)->update('biolinks_blocks', ['order' => $position]);
    }
}

function fcc_card_editor_owned_block(object $link, int $block_id): object {
    $block = db()->where('biolink_block_id', $block_id)->where('link_id', (int) $link->link_id)->where('user_id', (int) $link->user_id)->getOne('biolinks_blocks');
    if(!$block) throw new InvalidArgumentException(fcc_t('Blok nije pronađen. Osvježi stranicu.'));
    $block->settings = fcc_card_settings($block->settings);
    return $block;
}

/**
 * One editor action. Returns ['message' => ..., 'block_id' => ...] for the page script.
 * Actions: profile, save, add, toggle, move, delete, design, recommended.
 */
function fcc_card_editor_mutate(object $user, int $link_id, array $input): array {
    $uid = (int) $user->user_id;
    $link = fcc_card_editor_link($uid, $link_id);
    $action = (string) ($input['action'] ?? '');
    $is_v2 = fcc_card_is_v2($link);
    $plan = fcc_card_editor_plan_blocks($user);
    $result = ['message' => fcc_t('Spremljeno.'), 'block_id' => 0];
    fcc_card_editor_files('reset');
    // Custom code: FC-2026-10-12: keep profile and upload-bearing saves atomic in the shared mysqli connection.
    $transaction = false;

    try {
    if(in_array($action, ['profile', 'save'], true)) {
        if(!database()->begin_transaction()) throw new RuntimeException('Card save could not start.');
        $transaction = true;
    }
    switch($action) {
        case 'profile':
            fcc_card_editor_guard();
            fcc_card_editor_save_profile($user, $link, $input);
            $result['message'] = fcc_t('Profil je spremljen.');
            break;

        case 'save':
            fcc_card_editor_guard();
            $block_id = (int) ($input['block_id'] ?? 0);
            if($block_id) {
                $block = fcc_card_editor_owned_block($link, $block_id);
            } else {
                fcc_card_editor_guard('create.biolinks_blocks');
                $type = (string) ($input['type'] ?? '');
                if(!in_array($type, FCC_CARD_EDITOR_LIBRARY, true) && $type !== 'vimeo') throw new InvalidArgumentException(fcc_t('Ovaj blok nije moguće dodati ovdje.'));
                if(!fcc_card_editor_type_allowed($type, $plan, $is_v2)) throw new InvalidArgumentException(fcc_t('Ovaj blok dostupan je uz PRO paket.'));
                $block = (object) ['biolink_block_id' => 0, 'user_id' => $uid, 'type' => $type, 'location_url' => null, 'settings' => (object) fcc_card_editor_defaults($type, $user, fcc_card_editor_language($link, $user))];
            }
            $result['block_id'] = fcc_card_editor_apply_fields($user, $link, $block, $input);
            $result['message'] = $block_id ? fcc_t('Spremljeno. Promjena je odmah na kartici.') : fcc_t('Blok je dodan na karticu.');
            break;

        case 'add':
            fcc_card_editor_guard('create.biolinks_blocks');
            $type = (string) ($input['type'] ?? '');
            if(!in_array($type, FCC_CARD_EDITOR_LIBRARY, true)) throw new InvalidArgumentException(fcc_t('Ovaj blok nije moguće dodati ovdje.'));
            if(!fcc_card_editor_type_allowed($type, $plan, $is_v2)) throw new InvalidArgumentException(fcc_t('Ovaj blok dostupan je uz PRO paket.'));
            if(in_array($type, FCC_CARD_EDITOR_NEEDS_FIELDS, true)) throw new InvalidArgumentException(fcc_t('Ispuni polja bloka pa spremi.'));
            $existing = db()->where('link_id', $link_id)->where('type', $type)->getValue('biolinks_blocks', 'COUNT(*)');
            if($existing && in_array($type, ['link_forever_webshop_reg', 'link_forever_shop', 'custom_html_chatbot', 'link_save_contact'], true)) throw new InvalidArgumentException(fcc_t('Kartica već ima ovaj blok.'));
            fcc_card_editor_check_limit($user, $link);
            if($type === 'custom_html_chatbot' && !(function_exists('fcc_ai_user_has_public_ai_access') && fcc_ai_user_has_public_ai_access($uid))) throw new InvalidArgumentException(fcc_t('AI savjetnik dostupan je uz PRO paket.'));
            if($type === 'link_save_contact' && !fcc_card_own_vcard_url($uid)) throw new InvalidArgumentException(fcc_t('Za ovaj gumb potreban je tvoj kontakt. Javi se podršci.'));
            if(in_array($type, ['link_forever_webshop_reg', 'link_forever_shop'], true)) {
                fc_ensure_factory_forever_block($uid, $link_id, $type === 'link_forever_shop' ? 'business' : 'shop');
                $result['block_id'] = (int) db()->where('link_id', $link_id)->where('type', $type)->orderBy('biolink_block_id', 'DESC')->getValue('biolinks_blocks', 'biolink_block_id');
            } else {
                $location = $type === 'link_save_contact' ? fcc_card_own_vcard_url($uid) : null;
                $result['block_id'] = fcc_card_block_insert($uid, $link_id, $type, fcc_card_editor_defaults($type, $user, fcc_card_editor_language($link, $user)), $location, fcc_card_editor_insert_position($link));
            }
            $result['message'] = fcc_t('Blok je dodan na karticu.');
            break;

        case 'toggle':
            fcc_card_editor_guard();
            $block = fcc_card_editor_owned_block($link, (int) ($input['block_id'] ?? 0));
            $enable = !empty($input['enabled']);
            if($enable && !fcc_card_editor_type_allowed($block->type, $plan, $is_v2)) throw new InvalidArgumentException(fcc_t('Ovaj blok dostupan je uz PRO paket.'));
            if($enable && $is_v2 && in_array($block->type, FCC_CARD_RETIRED_BLOCKS, true)) throw new InvalidArgumentException(fcc_t('Ovaj blok više se ne prikazuje na novoj kartici.'));
            db()->where('biolink_block_id', (int) $block->biolink_block_id)->update('biolinks_blocks', ['is_enabled' => $enable ? 1 : 0]);
            $result['message'] = $enable ? fcc_t('Blok je vidljiv na kartici.') : fcc_t('Blok je skriven. Možeš ga ponovno uključiti.');
            $result['block_id'] = (int) $block->biolink_block_id;
            break;

        case 'move':
            fcc_card_editor_guard();
            $block = fcc_card_editor_owned_block($link, (int) ($input['block_id'] ?? 0));
            $blocks = array_values(fcc_card_editor_blocks($link));
            $profile = fcc_card_editor_profile_ids($blocks);
            $ids = array_values(array_filter(array_map(static fn($row) => (int) $row->biolink_block_id, $blocks), static fn($id) => !in_array($id, $profile, true)));
            $index = array_search((int) $block->biolink_block_id, $ids, true);
            $target = ($input['direction'] ?? '') === 'up' ? $index - 1 : $index + 1;
            if($index === false || $target < 0 || $target >= count($ids)) break;
            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
            $ordered = array_merge(array_values(array_filter($profile)), $ids);
            foreach(array_map(static fn($row) => (int) $row->biolink_block_id, $blocks) as $id) if(!in_array($id, $ordered, true)) $ordered[] = $id;
            foreach($ordered as $position => $id) db()->where('biolink_block_id', $id)->where('link_id', $link_id)->update('biolinks_blocks', ['order' => $position]);
            $result['message'] = fcc_t('Redoslijed je promijenjen.');
            $result['block_id'] = (int) $block->biolink_block_id;
            break;

        case 'delete':
            fcc_card_editor_guard('delete.biolinks_blocks');
            $block = fcc_card_editor_owned_block($link, (int) ($input['block_id'] ?? 0));
            if(empty($input['confirm'])) throw new InvalidArgumentException(fcc_t('Potvrdi uklanjanje bloka.'));
            (new \Altum\Models\BiolinkBlock())->delete((int) $block->biolink_block_id);
            $result['message'] = fcc_t('Blok je uklonjen s kartice.');
            break;

        case 'design':
            fcc_card_editor_guard('update.links');
            fcc_card_set_design($uid, $link_id, (string) ($input['card_version'] ?? ''), (string) ($input['card_theme'] ?? ''));
            if(isset($input['style_present'])) fcc_card_editor_save_style($user, $link_id, $input);
            $result['message'] = fcc_t('Izgled kartice je spremljen. Blokovi su ostali isti.');
            break;

        case 'recommended':
            fcc_card_editor_guard('create.biolinks_blocks');
            $added = fcc_card_add_recommended($uid, $link_id);
            $result['message'] = $added ? fcc_t('Preporučeni blokovi su dodani. Možeš ih urediti ili ukloniti.') : fcc_t('Kartica već ima sve preporučene blokove.');
            break;

        default:
            throw new InvalidArgumentException(fcc_t('Radnja nije prepoznata. Osvježi stranicu.'));
    }

    if(db()->where('link_id', $link_id)->where('user_id', $uid)->update('links', ['last_datetime' => gmdate('Y-m-d H:i:s')]) === false) throw new RuntimeException('Card change date was not saved.');
    if($transaction) {
        if(!database()->commit()) throw new RuntimeException('Card save could not finish.');
        $transaction = false;
    }
    } catch(\Throwable $e) {
        if($transaction) database()->rollback();
        fcc_card_editor_files_finish(false);
        throw $e;
    }
    fcc_card_editor_files_finish(true);
    // /Custom code: FC-2026-10-12
    fcc_card_clear_cache($link);
    return $result;
}

/** Products for the product block, in the card language, with the member's referral. */
function fcc_card_editor_products(object $link, string $query = ''): array {
    $language = fcc_card_editor_language($link);
    $query = mb_strtolower(trim($query));
    $out = [];
    foreach(fcc_product_picker_catalog($language, (string) $link->url) as $product) {
        if($query !== '' && !str_contains(mb_strtolower($product->title), $query)) continue;
        $out[] = ['id' => (int) $product->blog_post_id, 'title' => html_entity_decode((string) $product->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'image' => (string) ($product->image_url ?? '')];
        if(count($out) >= 40) break;
    }
    return $out;
}
/* /Custom code: FC-2026-10-12 */
