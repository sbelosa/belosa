<?php
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-12: the new FCC card.
 * The new card shows the same blocks in a new, calm design. A card is either "v2" (new) or the classic
 * card; switching only changes the presentation and never deletes or rewrites blocks, so a member can go
 * back at any time. New registrations start on the new card in the Aloe style; PRO members may pick other
 * styles. Forever blocks keep their referral routing through fcc_forever_entry_url(). */

const FCC_CARD_THEMES = [
    'aloe' => ['name' => 'Aloe', 'pro' => false, 'swatch' => ['#EEF6F1', '#12775B']],
    'smaragd' => ['name' => 'Smaragd', 'pro' => true, 'swatch' => ['#0E2B23', '#7FD9B3']],
    'zlato' => ['name' => 'Zlato', 'pro' => true, 'swatch' => ['#121110', '#D9AE3A']],
    'pijesak' => ['name' => 'Pijesak', 'pro' => true, 'swatch' => ['#F6F0E7', '#8A5A2B']],
    'ponoc' => ['name' => 'Ponoć', 'pro' => true, 'swatch' => ['#101B2E', '#6FD3A6']],
];

/* Blocks every member can show on the new card, also on the Beginner package. */
const FCC_CARD_CORE_BLOCKS = ['avatar', 'heading', 'paragraph', 'socials', 'link_forever_webshop_reg', 'link_forever_shop', 'link_discount', 'link_save_contact', 'custom_html_whatsapp', 'divider'];

/* Blocks no longer offered for new use. Existing blocks keep working. */
const FCC_CARD_RETIRED_BLOCKS = ['phone_collector', 'email_collector'];

function fcc_card_settings($settings): object {
    if(is_string($settings)) $settings = json_decode($settings);
    if(is_array($settings)) $settings = json_decode(json_encode($settings));
    return is_object($settings) ? $settings : (object) [];
}

function fcc_card_is_v2($link): bool {
    $settings = fcc_card_settings(is_object($link) ? ($link->settings ?? null) : null);
    return ($settings->fcc_card ?? '') === 'v2';
}

function fcc_card_user_is_pro(int $user_id): bool {
    return $user_id > 0 && function_exists('fcc_pro_has_access') && fcc_pro_has_access($user_id);
}

/** The style a visitor sees. Without PRO every card shows Aloe; the chosen style returns when PRO returns. */
function fcc_card_theme($link, ?int $owner_id = null): string {
    $settings = fcc_card_settings(is_object($link) ? ($link->settings ?? null) : null);
    $theme = (string) ($settings->fcc_card_theme ?? 'aloe');
    if(!isset(FCC_CARD_THEMES[$theme])) $theme = 'aloe';
    $owner_id = $owner_id ?? (int) ($link->user_id ?? 0);
    if(FCC_CARD_THEMES[$theme]['pro'] && !fcc_card_user_is_pro($owner_id)) $theme = 'aloe';
    return $theme;
}

function fcc_card_core_block(string $type): bool {
    return in_array($type, FCC_CARD_CORE_BLOCKS, true);
}

/* Advanced look inside the chosen style. Every choice is optional; empty means "as the style defines it". */
const FCC_CARD_STYLE_OPTIONS = [
    'buttons' => ['rounded', 'pill', 'square'],
    'button_look' => ['surface', 'outline', 'soft'],
    'font' => ['modern', 'classic', 'simple'],
    'text' => ['normal', 'large'],
    'avatar' => ['circle', 'rounded'],
    'glow' => ['on', 'strong', 'off'],
];
/* Look of a single block on the new card. */
const FCC_CARD_BLOCK_STYLE_OPTIONS = [
    'look' => ['auto', 'filled', 'surface', 'outline', 'soft'],
    'align' => ['center', 'left'],
    'size' => ['m', 's', 'l'],
    'frame' => ['auto', 'plain', 'panel'],
    'corners' => ['rounded', 'straight'],
    'icons' => ['circle', 'outline', 'plain'],
];
/* Icons offered for buttons: Font Awesome solid and brand icons that the card loads. */
const FCC_CARD_BUTTON_ICONS = ['' => 'Bez ikone', 'fas fa-shopping-bag' => 'Vrećica', 'fas fa-gift' => 'Poklon', 'fas fa-leaf' => 'List', 'fas fa-heart' => 'Srce', 'fas fa-star' => 'Zvjezdica', 'fas fa-handshake' => 'Suradnja', 'fas fa-phone' => 'Telefon', 'fas fa-envelope' => 'Poruka', 'fas fa-calendar-alt' => 'Kalendar', 'fas fa-play-circle' => 'Video', 'fas fa-info-circle' => 'Informacije', 'fas fa-address-book' => 'Kontakt', 'fab fa-whatsapp' => 'WhatsApp', 'fas fa-link' => 'Poveznica'];

function fcc_card_hex(?string $value): string {
    $value = trim((string) $value);
    if(preg_match('/^#?([0-9a-f]{3})$/i', $value, $m)) $value = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
    return preg_match('/^#?([0-9a-f]{6})$/i', $value, $m) ? '#' . mb_strtoupper($m[1]) : '';
}

/** Clean card style from a form or stored settings. */
function fcc_card_style_clean($input): array {
    $input = is_object($input) ? (array) $input : (is_array($input) ? $input : []);
    $style = [];
    foreach(['accent', 'background'] as $color) {
        $hex = fcc_card_hex(is_scalar($input[$color] ?? null) ? (string) $input[$color] : '');
        if($hex !== '') $style[$color] = $hex;
    }
    foreach(FCC_CARD_STYLE_OPTIONS as $key => $allowed) {
        $value = is_scalar($input[$key] ?? null) ? (string) $input[$key] : '';
        if($value !== '' && $value !== $allowed[0] && in_array($value, $allowed, true)) $style[$key] = $value;
    }
    return $style;
}

/** Clean look of one block. Only options that differ from the default are kept. */
function fcc_card_block_style_clean($input): array {
    $input = is_object($input) ? (array) $input : (is_array($input) ? $input : []);
    $style = [];
    foreach(FCC_CARD_BLOCK_STYLE_OPTIONS as $key => $allowed) {
        $value = is_scalar($input[$key] ?? null) ? (string) $input[$key] : '';
        if($value !== '' && $value !== $allowed[0] && in_array($value, $allowed, true)) $style[$key] = $value;
    }
    return $style;
}

/**
 * Owner preview: the editor shows a style before it is saved. It needs the card's preview key, the same one
 * that opens the preview itself, so visitors never see unsaved choices.
 */
function fcc_card_preview_choice(object $link): array {
    static $cache = [];
    $id = (int) ($link->link_id ?? 0);
    if(isset($cache[$id])) return $cache[$id];
    $key = md5((string) ($link->user_id ?? ''));
    if(!isset($_GET['preview']) || !is_string($_GET['preview']) || !hash_equals($key, $_GET['preview'])) return $cache[$id] = [];
    $choice = [];
    if(in_array($_GET['fcc_card'] ?? null, ['v1', 'v2'], true)) $choice['version'] = $_GET['fcc_card'];
    if(is_string($_GET['fcc_theme'] ?? null) && isset(FCC_CARD_THEMES[$_GET['fcc_theme']])) $choice['theme'] = $_GET['fcc_theme'];
    if(is_string($_GET['fcc_style'] ?? null) && strlen($_GET['fcc_style']) < 2000) {
        $decoded = json_decode($_GET['fcc_style'], true);
        if(is_array($decoded)) $choice['style'] = fcc_card_style_clean($decoded);
    }
    return $cache[$id] = $choice;
}

/** Version, style and look used to draw the card now: saved values, or the owner's unsaved preview. */
function fcc_card_render_is_v2(object $link): bool {
    $choice = fcc_card_preview_choice($link);
    return isset($choice['version']) ? $choice['version'] === 'v2' : fcc_card_is_v2($link);
}

function fcc_card_render_theme(object $link, ?int $owner_id = null): string {
    $choice = fcc_card_preview_choice($link);
    if(!isset($choice['theme'])) return fcc_card_theme($link, $owner_id);
    $copy = clone $link;
    $settings = clone fcc_card_settings($link->settings ?? null);
    $settings->fcc_card_theme = $choice['theme'];
    $copy->settings = $settings;
    return fcc_card_theme($copy, $owner_id);
}

function fcc_card_render_style(object $link, ?int $owner_id = null): array {
    $choice = fcc_card_preview_choice($link);
    $style = $choice['style'] ?? fcc_card_style_clean(fcc_card_settings($link->settings ?? null)->fcc_card_style ?? []);
    // Own colours are part of PRO, like the extra styles. Shapes, font and sizes are open to everyone.
    $owner_id = $owner_id ?? (int) ($link->user_id ?? 0);
    if(!fcc_card_user_is_pro($owner_id)) unset($style['accent'], $style['background']);
    return $style;
}

function fcc_card_luminance(string $hex): float {
    $hex = ltrim($hex, '#');
    $channel = static function(string $part): float { $c = hexdec($part) / 255; return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4; };
    return 0.2126 * $channel(substr($hex, 0, 2)) + 0.7152 * $channel(substr($hex, 2, 2)) + 0.0722 * $channel(substr($hex, 4, 2));
}

function fcc_card_contrast(string $a, string $b): float {
    $la = fcc_card_luminance($a); $lb = fcc_card_luminance($b);
    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

/** CSS variables and body classes for the chosen colours and details. Text stays readable on any colour. */
function fcc_card_style_css(array $style): array {
    $vars = [];
    $classes = [];
    if(!empty($style['background'])) {
        $bg = $style['background'];
        $dark = fcc_card_luminance($bg) < 0.22;
        $vars['--fc-bg'] = $bg;
        $vars['--fc-glow'] = 'color-mix(in srgb, ' . $bg . ' 82%, ' . ($dark ? '#FFFFFF' : '#FFFFFF') . ')';
        $vars['--fc-surface'] = $dark ? 'color-mix(in srgb, ' . $bg . ' 88%, #FFFFFF)' : '#FFFFFF';
        $vars['--fc-ink'] = $dark ? '#F4F7F5' : '#15201C';
        $vars['--fc-muted'] = $dark ? 'color-mix(in srgb, #F4F7F5 72%, ' . $bg . ')' : 'color-mix(in srgb, #15201C 68%, ' . $bg . ')';
        $vars['--fc-line'] = $dark ? 'color-mix(in srgb, ' . $bg . ' 76%, #FFFFFF)' : 'color-mix(in srgb, ' . $bg . ' 80%, #15201C)';
        $classes[] = $dark ? 'fcc-card-dark' : 'fcc-card-light';
    }
    if(!empty($style['accent'])) {
        $accent = $style['accent'];
        $vars['--fc-accent'] = $accent;
        $vars['--fc-accent-ink'] = fcc_card_contrast($accent, '#FFFFFF') >= fcc_card_contrast($accent, '#14201B') ? '#FFFFFF' : '#14201B';
        $vars['--fc-accent-soft'] = 'color-mix(in srgb, ' . $accent . ' 16%, var(--fc-surface))';
    }
    foreach(['buttons', 'button_look', 'font', 'text', 'avatar', 'glow'] as $key) {
        if(!empty($style[$key])) $classes[] = 'fcc-' . str_replace('_', '-', $key) . '-' . $style[$key];
    }
    $css = '';
    foreach($vars as $name => $value) $css .= $name . ':' . $value . ';';
    return ['css' => $css, 'classes' => implode(' ', $classes)];
}

/** Main card of a member or another biolink they own. */
function fcc_card_owned_link(int $user_id, int $link_id): ?object {
    $link = db()->where('link_id', $link_id)->where('user_id', $user_id)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'url', 'settings', 'domain_id']);
    return $link ?: null;
}

function fcc_card_clear_cache(object $link): void {
    cache()->deleteItem('link?link_id=' . (int) $link->link_id);
    cache()->deleteItem('biolink_blocks?link_id=' . (int) $link->link_id);
    cache()->deleteItem('link?url=' . md5((string) $link->url));
    if(!empty($link->domain_id)) cache()->deleteItem('link?url=' . md5((string) $link->url) . '&domain_id=' . (int) $link->domain_id);
}

/** Switches between the new and the classic card and stores the style. Blocks are never touched. */
function fcc_card_set_design(int $user_id, int $link_id, string $version, string $theme = ''): void {
    $link = fcc_card_owned_link($user_id, $link_id);
    if(!$link) throw new InvalidArgumentException(fcc_t('Kartica nije pronađena.'));
    if(!in_array($version, ['v2', 'v1'], true)) throw new InvalidArgumentException(fcc_t('Odaberi novu ili staru karticu.'));
    $settings = fcc_card_settings($link->settings);
    // Without a chosen style the stored one stays, so going back to the classic card keeps it for later.
    if($theme === '') $theme = (string) ($settings->fcc_card_theme ?? 'aloe');
    if(!isset(FCC_CARD_THEMES[$theme])) $theme = 'aloe';
    if($version === 'v2' && FCC_CARD_THEMES[$theme]['pro'] && !fcc_card_user_is_pro($user_id)) {
        if(isset($settings->fcc_card_theme) && $settings->fcc_card_theme === $theme) $theme = 'aloe';
        else throw new InvalidArgumentException(fcc_t('Ovaj stil dostupan je uz PRO paket.'));
    }

    $before = ($settings->fcc_card ?? '') === 'v2' ? 'v2' : 'v1';
    $settings->fcc_card = $version;
    $settings->fcc_card_theme = $theme;
    if($before !== $version) {
        $history = is_array($settings->fcc_card_history ?? null) ? $settings->fcc_card_history : [];
        $history[] = ['from' => $before, 'to' => $version, 'at' => gmdate('c')];
        $settings->fcc_card_history = array_slice($history, -20);
    }
    db()->where('link_id', $link_id)->where('user_id', $user_id)->update('links', ['settings' => json_encode($settings), 'last_datetime' => gmdate('Y-m-d H:i:s')]);
    if($version === 'v2') fcc_card_restore_core_blocks($user_id, $link_id);
    fcc_card_clear_cache($link);
}

/** Core blocks that a package change switched off come back on the new card, where every package shows them. */
function fcc_card_restore_core_blocks(int $user_id, int $link_id): int {
    $restored = 0;
    foreach(db()->where('link_id', $link_id)->where('user_id', $user_id)->where('is_enabled', 0)->where('type', FCC_CARD_CORE_BLOCKS, 'IN')->get('biolinks_blocks', null, ['biolink_block_id', 'settings']) ?: [] as $block) {
        $settings = fcc_card_settings($block->settings);
        if(empty($settings->plan_auto_disabled)) continue;
        $settings->plan_auto_disabled = false;
        db()->where('biolink_block_id', $block->biolink_block_id)->update('biolinks_blocks', ['is_enabled' => 1, 'settings' => json_encode($settings)]);
        $restored++;
    }
    return $restored;
}

/** The new card shows button labels in normal case and gives the contact buttons an icon. Display only. */
function fcc_card_tidy_label(object $row): void {
    if(!is_object($row->settings ?? null)) return;
    foreach(['name', 'title'] as $field) {
        $text = trim((string) ($row->settings->{$field} ?? ''));
        if(mb_strlen($text) < 4 || !preg_match('/\p{Lu}.*\p{Lu}/u', $text) || mb_strtoupper($text) !== $text || mb_strtolower($text) === $text) continue;
        $lower = mb_strtolower($text);
        $row->settings->{$field} = mb_strtoupper(mb_substr($lower, 0, 1)) . mb_substr($lower, 1);
    }
    if($row->type === 'link_save_contact' && empty($row->settings->icon)) $row->settings->icon = 'fas fa-address-book';
    // Cards load only the solid and brand icon sets, so regular icons would stay invisible.
    if(is_string($row->settings->icon ?? null) && str_starts_with($row->settings->icon, 'far ')) $row->settings->icon = 'fas ' . substr($row->settings->icon, 4);
}

/**
 * WhatsApp needs the number with the country code and without the leading zero. A number typed in another
 * form is shown in that form; on the new card an empty number falls back to the phone in the member's profile.
 */
function fcc_card_whatsapp_phone(object $row, $owner, bool $profile_fallback = true): void {
    if($row->type !== 'custom_html_whatsapp' || !is_object($row->settings ?? null)) return;
    $typed = trim((string) ($row->settings->phone ?? ''));
    $phone = $typed !== '' ? fc_normalize_whatsapp_phone($typed, fc_user_phone_countries($owner)) : '';
    if($phone === '' && $profile_fallback) $phone = fc_get_user_default_whatsapp_phone($owner);
    if($phone !== '') $row->settings->phone = $phone;
}

function fcc_card_block_insert(int $user_id, int $link_id, string $type, array $settings, ?string $location_url, int $order, int $is_enabled = 1): int {
    $settings += ['display_continents' => [], 'display_countries' => [], 'display_cities' => [], 'display_devices' => [], 'display_languages' => [], 'display_operating_systems' => [], 'display_browsers' => []];
    return (int) db()->insert('biolinks_blocks', [
        'user_id' => $user_id, 'link_id' => $link_id, 'type' => $type, 'location_url' => $location_url,
        'settings' => json_encode($settings), 'order' => $order, 'is_enabled' => $is_enabled, 'datetime' => gmdate('Y-m-d H:i:s'),
    ]);
}

/** Intro under the name. A bold first line is shown as a role badge on the new card. */
function fcc_card_intro_text(?string $language = null): string {
    return '<p><strong>' . htmlspecialchars(fcc_t('Neovisni Forever Business Partner', [], $language), ENT_QUOTES, 'UTF-8') . '</strong></p>'
        . '<p>' . htmlspecialchars(fcc_t('Za više informacija o Forever proizvodima ili poslovnoj suradnji slobodno me kontaktirajte.', [], $language), ENT_QUOTES, 'UTF-8') . '</p>';
}

function fcc_card_own_vcard_url(int $user_id): ?string {
    $vcard = db()->where('user_id', $user_id)->where('type', 'vcard')->where('is_enabled', 1)->orderBy('link_id', 'ASC')->getOne('links', ['url']);
    return $vcard ? SITE_URL . $vcard->url : null;
}

/** Blocks the new card recommends and which of them this card is missing. */
function fcc_card_missing_recommended(int $user_id, int $link_id): array {
    $types = array_map(static fn($row) => $row->type, db()->where('link_id', $link_id)->where('user_id', $user_id)->get('biolinks_blocks', null, ['type']) ?: []);
    $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'preferences', 'country']);
    $missing = [];
    foreach(['avatar', 'heading', 'paragraph', 'link_forever_webshop_reg', 'custom_html_whatsapp', 'link_save_contact', 'link_forever_shop'] as $type) {
        if(in_array($type, $types, true)) continue;
        if($type === 'custom_html_whatsapp' && (!$user || fc_get_user_default_whatsapp_phone($user) === '')) continue;
        if($type === 'link_save_contact' && !fcc_card_own_vcard_url($user_id)) continue;
        $missing[] = $type;
    }
    return $missing;
}

/** Adds the recommended blocks a card is missing, in the new card's order. Returns the number added. */
function fcc_card_add_recommended(int $user_id, int $link_id): int {
    $link = fcc_card_owned_link($user_id, $link_id);
    if(!$link) throw new InvalidArgumentException(fcc_t('Kartica nije pronađena.'));
    $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'name', 'language', 'preferences', 'country']);
    $language = function_exists('fcc_locale') ? fcc_locale((string) ($user->language ?? 'hr')) : 'hr';
    $added = 0;
    $order = static fn(int $position) => $position;
    foreach(fcc_card_missing_recommended($user_id, $link_id) as $type) {
        switch($type) {
            case 'avatar':
                fcc_card_block_insert($user_id, $link_id, 'avatar', ['image' => null, 'image_alt' => '', 'use_default_avatar' => true, 'size' => 125, 'border_radius' => 'round', 'object_fit' => 'cover', 'border_width' => 0, 'border_style' => 'solid', 'border_color' => '#ffffff', 'border_shadow_style' => 'none', 'border_shadow_color' => '#00000000', 'open_in_new_tab' => 0], null, $order(0));
                break;
            case 'heading':
                fcc_card_block_insert($user_id, $link_id, 'heading', ['heading_type' => 'h2', 'text' => (string) ($user->name ?? ''), 'text_color' => '#ffffff', 'text_alignment' => 'center', 'verified_location' => ''], null, $order(1));
                break;
            case 'paragraph':
                fcc_card_block_insert($user_id, $link_id, 'paragraph', ['text' => fcc_card_intro_text($language), 'text_color' => '#ffffff', 'background_color' => '#00000000', 'border_radius' => 'rounded', 'border_shadow_style' => 'none', 'border_shadow_color' => '#00000000', 'border_width' => 0, 'border_style' => 'solid', 'border_color' => '#ffffff', 'text_alignment' => 'center'], null, $order(2));
                break;
            case 'link_forever_webshop_reg':
            case 'link_forever_shop':
                fc_ensure_factory_forever_block($user_id, $link_id, $type === 'link_forever_shop' ? 'business' : 'shop');
                break;
            case 'custom_html_whatsapp':
                fcc_card_block_insert($user_id, $link_id, 'custom_html_whatsapp', ['title' => 'WhatsApp', 'phone' => fc_get_user_default_whatsapp_phone($user), 'message' => fcc_t('Bok! Javljam se preko tvoje FCC kartice. Zanimaju me Forever proizvodi.', [], $language), 'icon' => 'fab fa-whatsapp', 'border_radius' => 'rounded'], null, $order(4));
                break;
            case 'link_save_contact':
                fcc_card_block_insert($user_id, $link_id, 'link_save_contact', ['name' => fcc_t('Spremi kontakt', [], $language), 'icon' => 'fas fa-address-book', 'open_in_new_tab' => false, 'border_radius' => 'rounded', 'image' => ''], fcc_card_own_vcard_url($user_id), $order(5));
                break;
        }
        $added++;
    }
    if($added) {
        fcc_card_reorder($user_id, $link_id);
        fcc_card_clear_cache($link);
    }
    return $added;
}

/** Puts the profile first, then the shop, contact, other blocks and the partnership block last. */
function fcc_card_reorder(int $user_id, int $link_id): void {
    $rank = ['avatar' => 0, 'header' => 0, 'heading' => 1, 'paragraph' => 2, 'link_forever_webshop_reg' => 3, 'link_discount' => 3, 'custom_html_whatsapp' => 4, 'link_save_contact' => 5, 'socials' => 8, 'link_forever_shop' => 9];
    $blocks = db()->where('link_id', $link_id)->where('user_id', $user_id)->orderBy('`order`', 'ASC')->orderBy('biolink_block_id', 'ASC')->get('biolinks_blocks', null, ['biolink_block_id', 'type']);
    $sorted = [];
    foreach($blocks as $index => $block) $sorted[] = [$rank[$block->type] ?? 6, $index, (int) $block->biolink_block_id];
    usort($sorted, static fn($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
    foreach($sorted as $position => $item) db()->where('biolink_block_id', $item[2])->update('biolinks_blocks', ['order' => $position]);
}

/** Called once for every new registration, after the factory card is created and filled in. */
function fcc_card_prepare_new(int $user_id, int $link_id): void {
    $link = fcc_card_owned_link($user_id, $link_id);
    if(!$link) return;
    // The new card leaves out the shared team video, the install hint and the retired lead forms.
    db()->where('link_id', $link_id)->where('user_id', $user_id)->where('type', ['vimeo', 'link_homescreen_android', 'link_homescreen_ios', 'phone_collector', 'email_collector'], 'IN')->delete('biolinks_blocks');
    // The WhatsApp button gets the member's registration number in the form WhatsApp needs.
    $owner = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'preferences', 'country']);
    $profile_phone = $owner ? fc_get_user_default_whatsapp_phone($owner) : '';
    foreach(db()->where('link_id', $link_id)->where('user_id', $user_id)->where('type', 'custom_html_whatsapp')->get('biolinks_blocks', null, ['biolink_block_id', 'settings', 'is_enabled']) ?: [] as $block) {
        $settings = fcc_card_settings($block->settings);
        $phone = fc_normalize_whatsapp_phone((string) ($settings->phone ?? ''), $owner ? fc_user_phone_countries($owner) : ['HR']) ?: $profile_phone;
        if($phone === '') continue;
        $settings->phone = $phone;
        $settings->plan_auto_disabled = false;
        db()->where('biolink_block_id', (int) $block->biolink_block_id)->update('biolinks_blocks', ['settings' => json_encode($settings), 'is_enabled' => 1]);
    }
    fcc_card_restore_core_blocks($user_id, $link_id);
    fcc_card_add_recommended($user_id, $link_id);
    fcc_card_reorder($user_id, $link_id);
    $settings = fcc_card_settings($link->settings);
    $settings->fcc_card = 'v2';
    $settings->fcc_card_theme = 'aloe';
    $settings->fcc_card_history = [['from' => 'new', 'to' => 'v2', 'at' => gmdate('c')]];
    db()->where('link_id', $link_id)->update('links', ['settings' => json_encode($settings)]);
    fcc_card_clear_cache($link);
}

/** Factory labels are stored in the language of the account; show them in the visitor's language. */
function fcc_card_localize_factory_label(object $row): void {
    if(!in_array($row->type, ['link_forever_shop', 'link_forever_webshop_reg'], true) || !is_object($row->settings ?? null)) return;
    $name = trim((string) ($row->settings->name ?? ''));
    if($name === '') return;
    static $known = null;
    if($known === null) {
        $copy = require APP_PATH . 'config/fcc_forever_routing_copy.php';
        $known = ['shop' => [], 'business' => []];
        foreach($copy as $language_copy) {
            foreach(['shop', 'business'] as $key) if(!empty($language_copy[$key])) $known[$key][] = mb_strtolower(trim((string) $language_copy[$key]));
        }
    }
    $key = $row->type === 'link_forever_shop' ? 'business' : 'shop';
    if(in_array(mb_strtolower($name), $known[$key], true)) $row->settings->name = fcc_forever_copy($key);
}

/** "Save contact" always saves the card owner's own contact. Returns false when the owner has none. */
function fcc_card_fix_save_contact(object $row, int $owner_id): bool {
    if($row->type !== 'link_save_contact') return true;
    $own = fcc_card_own_vcard_url($owner_id);
    $path = trim((string) parse_url((string) ($row->location_url ?? ''), PHP_URL_PATH), '/');
    $target = $path !== '' ? db()->where('url', basename($path))->where('type', 'vcard')->getOne('links', ['user_id']) : null;
    if($target && (int) $target->user_id === $owner_id) return true;
    if($target || $path === '') {
        if(!$own) return false;
        $row->location_url = $own;
    }
    return true;
}

/** One quiet line under the first Forever button: which country the offer is for, and how to change it. */
function fcc_card_country_line(?string $country_code, string $change_url, bool $v2, string $text_color = ''): string {
    $change = htmlspecialchars(fcc_forever_copy('change'), ENT_QUOTES, 'UTF-8');
    $href = htmlspecialchars($change_url, ENT_QUOTES, 'UTF-8');
    if($v2) {
        $country = $country_code && function_exists('fcc_library_pilot_country_name') ? fcc_library_pilot_country_name(strtolower($country_code)) : '';
        $label = $country !== '' ? fcc_t('Ponuda za: {country}', ['{country}' => $country]) : fcc_t('Zemlju dostave biraš na sljedećem koraku.');
        return '<div class="col-12 fcc-card-country"><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span> <a href="' . $href . '">' . $change . '</a></div>';
    }
    $style = preg_match('/^#[0-9a-f]{3,8}$/i', $text_color) ? ' style="color:' . $text_color . ';opacity:.85"' : '';
    return '<div class="col-12 text-center mb-2"><small' . $style . '>' . htmlspecialchars(fcc_forever_copy('automatic'), ENT_QUOTES, 'UTF-8') . ' <a href="' . $href . '"' . $style . '><u>' . $change . '</u></a></small></div>';
}

/** Footer in the visitor's language. The new card adds the independence notice. */
function fcc_card_footer_html(bool $v2): string {
    $club = '<a href="' . htmlspecialchars(url(), ENT_QUOTES, 'UTF-8') . '">Forever Card Club</a>';
    if($v2) {
        return '<div class="fcc-card-legal"><p>' . htmlspecialchars(fcc_t('Neovisni Forever Business Partner. Ovo nije službena stranica Forever Living Products.'), ENT_QUOTES, 'UTF-8') . '</p><p>' . $club . '</p></div>';
    }
    return htmlspecialchars(fcc_t('Kartica neovisnog Forever Business Partnera'), ENT_QUOTES, 'UTF-8') . ' · ' . str_replace('<a ', '<a style="color:inherit;text-decoration:underline" ', $club);
}

/** Marks a rendered block with its type and look, so the new card can style every block consistently. */
function fcc_card_mark_block(string $html, string $type, array $style = []): string {
    if($html === '') return $html;
    $attributes = ' data-fcc-type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"';
    foreach(fcc_card_block_style_clean($style) as $key => $value) $attributes .= ' data-fcc-' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    return (string) preg_replace('/<div\b/', '<div' . $attributes, $html, 1);
}

/**
 * The simple block guide shown in "Dodaj blok" and on top of every block form.
 * Groups: forever (leads to Forever with the member's referral), contact, about.
 */
function fcc_card_block_catalog(): array {
    static $catalog = null;
    return $catalog ??= [
        'link_forever_webshop_reg' => ['group' => 'forever', 'title' => 'Forever trgovina', 'what' => 'Gumb koji otvara službenu Forever trgovinu za zemlju posjetitelja. Narudžba se vodi uz tvoju preporuku.', 'how' => 'Dodaj ga jednom i drži ga visoko na kartici. Zemlja se odabire sama, a posjetitelj je može promijeniti.'],
        'link_forever_shop' => ['group' => 'forever', 'title' => 'Poslovna suradnja', 'what' => 'Gumb za osobu koja želi surađivati. Otvara službenu Forever prijavu ili Start paket s tobom kao sponzorom.', 'how' => 'Drži ga na dnu kartice. Za detaljno predstavljanje posla šalji Pozivnicu u tim iz izbornika Podijeli.'],
        'link_forever_product' => ['group' => 'forever', 'title' => 'Preporučeni proizvod', 'what' => 'Kartica jednog proizvoda s fotografijom. Vodi na stranicu proizvoda u Akademiji, s tvojom preporukom i narudžbom.', 'how' => 'Odaberi proizvod koji najčešće preporučuješ. Možeš dodati dva ili tri različita.'],
        'custom_html_chatbot' => ['group' => 'forever', 'title' => 'AI savjetnik', 'what' => 'Posjetitelj napiše što treba, a savjetnik predloži Forever proizvode i vodi na narudžbu uz tvoju preporuku.', 'how' => 'Dovoljno ga je dodati. Na novoj kartici prikazuje se kao gumb „Pitaj savjetnika“.'],
        'link' => ['group' => 'forever', 'title' => 'Gumb s poveznicom', 'what' => 'Gumb koji vodi na bilo koju stranicu, primjerice tvoj blog ili događaj.', 'how' => 'Upiši naslov i poveznicu. Ako upišeš Forever poveznicu, sustav joj sam doda tvoju preporuku.'],
        'custom_html_whatsapp' => ['group' => 'contact', 'title' => 'WhatsApp', 'what' => 'Posjetitelj ti jednim dodirom piše na WhatsApp, s već upisanom prvom porukom.', 'how' => 'Broj iz registracije upisuje se sam, u obliku koji WhatsApp traži. Možeš ga promijeniti u obrascu bloka.'],
        'link_save_contact' => ['group' => 'contact', 'title' => 'Spremi kontakt', 'what' => 'Posjetitelj sprema tvoje ime, broj i e-mail u imenik mobitela.', 'how' => 'Ne treba ništa upisivati. Podaci dolaze iz tvojeg profila.'],
        'socials' => ['group' => 'contact', 'title' => 'Društvene mreže', 'what' => 'Male ikone tvojih profila, primjerice Instagrama i Facebooka.', 'how' => 'Upiši samo profile koje stvarno koristiš. Prazna polja se ne prikazuju.'],
        'avatar' => ['group' => 'about', 'title' => 'Fotografija', 'what' => 'Tvoja fotografija na vrhu kartice. Ljudi lakše vjeruju kada vide lice.', 'how' => 'Učitaj jasnu fotografiju lica. Najbolje izgleda kvadratna fotografija.'],
        'heading' => ['group' => 'about', 'title' => 'Ime ili naslov', 'what' => 'Tvoje ime ili kratki naslov iznad ostalog sadržaja.', 'how' => 'Na vrhu drži svoje ime. Naslov možeš dodati i iznad novog dijela kartice.'],
        'paragraph' => ['group' => 'about', 'title' => 'Tekst o meni', 'what' => 'Dvije do tri rečenice o tebi i o tome kako pomažeš ljudima.', 'how' => 'Piši kao da se predstavljaš uživo. Kratko i osobno djeluje najbolje.'],
        'youtube' => ['group' => 'about', 'title' => 'YouTube video', 'what' => 'Tvoj video s YouTubea prikazan na kartici.', 'how' => 'Zalijepi poveznicu videa. Najbolje rade kratka predstavljanja do dvije minute.'],
        'vimeo' => ['group' => 'about', 'title' => 'Vimeo video', 'what' => 'Video s Vimea prikazan na kartici.', 'how' => 'Zalijepi poveznicu videa s Vimea.'],
        'image' => ['group' => 'about', 'title' => 'Slika', 'what' => 'Jedna fotografija, primjerice proizvoda ili događaja.', 'how' => 'Učitaj sliku. Po želji dodaj poveznicu na koju vodi dodir.'],
        'review' => ['group' => 'about', 'title' => 'Iskustvo kupca', 'what' => 'Kratko iskustvo zadovoljnog kupca s imenom.', 'how' => 'Upiši samo stvarno iskustvo, uz dopuštenje te osobe.'],
        'faq' => ['group' => 'about', 'title' => 'Česta pitanja', 'what' => 'Pitanja i odgovori koji se otvaraju dodirom.', 'how' => 'Dodaj pitanja koja ti ljudi najčešće postavljaju, primjerice o dostavi ili načinu narudžbe.'],
        'divider' => ['group' => 'about', 'title' => 'Razdjelnik', 'what' => 'Tanka crta koja odvaja dijelove kartice.', 'how' => 'Koristi ga rijetko, samo između većih cjelina.'],
    ];
}

function fcc_card_block_groups(): array {
    return [
        'forever' => ['title' => 'Forever i preporuka', 'text' => 'Ovi blokovi vode na Forever s tvojom preporukom.'],
        'contact' => ['title' => 'Kontakt', 'text' => 'Kako te ljudi mogu kontaktirati i spremiti tvoj broj.'],
        'about' => ['title' => 'O meni i sadržaj', 'text' => 'Tko si i što nudiš, riječima, slikom i videom.'],
    ];
}

/** Whether a member may add a block type to a card. The new card opens its core blocks to every package. */
function fcc_card_block_open(string $type, object $plan_blocks, bool $is_v2): bool {
    if(in_array($type, FCC_CARD_RETIRED_BLOCKS, true)) return false;
    if($is_v2 && fcc_card_core_block($type)) return true;
    return (bool) ($plan_blocks->{$type} ?? false);
}
/* /Custom code: FC-2026-10-12 */
