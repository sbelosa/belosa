<?php defined('ALTUMCODE') || die() ?>

<?php
$e = static function($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$hex_alpha = static function($hex, $alpha = '1A') {
    $hex = strtoupper(trim((string) $hex));

    if(function_exists('verify_hex_color') && verify_hex_color($hex) && preg_match('/^#[0-9A-F]{6}$/', $hex)) {
        return $hex . strtoupper(trim((string) $alpha));
    }

    return 'rgba(255,255,255,0.08)';
};

$clamp_opacity = static function($value, int $fallback = 100): int {
    if($value === '' || $value === null) {
        return $fallback;
    }

    return max(0, min(100, (int) round((float) $value)));
};

$color_with_opacity = static function($hex, $opacity = 100, string $fallback = '#0f172a') use ($clamp_opacity): string {
    $hex = strtoupper(trim((string) $hex));

    if(!(function_exists('verify_hex_color') && verify_hex_color($hex) && preg_match('/^#[0-9A-F]{6}$/', $hex))) {
        $hex = strtoupper(trim($fallback));
    }

    if(!preg_match('/^#[0-9A-F]{6}$/', $hex)) {
        $hex = '#0F172A';
    }

    $opacity = $clamp_opacity($opacity, 100);

    if($opacity >= 100) {
        return $hex;
    }

    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    $alpha = rtrim(rtrim(number_format($opacity / 100, 3, '.', ''), '0'), '.');

    return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ')';
};

$state = is_array($data->state ?? null) ? $data->state : [];
$payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];
$surface = is_array($state['page_surface'] ?? null) ? $state['page_surface'] : [];
$blocks = is_array($state['blocks'] ?? null) ? $state['blocks'] : [];
$active = is_array($state['active'] ?? null) ? $state['active'] : [];
$owner_profile = is_array($state['owner_profile'] ?? null) ? $state['owner_profile'] : [];
$ai_owner_user_id = (int) ($state['user_id'] ?? 0);
$ai_owner_link_id = (int) ($state['ai_owner_link_id'] ?? 0);
if($ai_owner_link_id <= 0 && $ai_owner_user_id > 0 && function_exists('fc_get_user_main_biolink_id')) {
    $ai_owner_link_id = (int) (fc_get_user_main_biolink_id($ai_owner_user_id) ?? 0);
}
$ai_public_access_enabled = $ai_owner_user_id > 0 && function_exists('fcc_ai_user_has_public_ai_access') && fcc_ai_user_has_public_ai_access($ai_owner_user_id);
$ai_page_language_code = trim((string) (\Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr'));
$ai_language_code = function_exists('fcc_ai_get_public_assistant_default_language')
    ? fcc_ai_get_public_assistant_default_language($ai_owner_user_id, 'product_advisor', 'public_app', $ai_page_language_code)
    : $ai_page_language_code;
$ai_widget_dom_id = 'vip-funnel-ai-advisor-' . md5((string) $ai_owner_user_id . '|' . (string) ($state['page_key'] ?? 'landing') . '|' . (string) ($state['variant_key'] ?? 'a'));
$ai_widget_registered = false;
$hide_public_navbar = !empty($payload['defaults']['hide_public_navbar']);
$facebook_pixel_id = function_exists('vip_funnel_normalize_meta_pixel_id')
    ? vip_funnel_normalize_meta_pixel_id($payload['defaults']['facebook_pixel_id'] ?? '')
    : preg_replace('/\D+/', '', (string) ($payload['defaults']['facebook_pixel_id'] ?? ''));
$meta_pixel_page_key = (string) ($state['page_key'] ?? 'landing');
$meta_pixel_event_map = [
    'landing' => 'VIPFunnelLandingView',
    'business_gateway' => 'VIPFunnelBusinessView',
    'fcc_demo_preview' => 'VIPFunnelSystemDemoView',
    'product_gateway' => 'VIPFunnelProductView',
    'product_recommendation' => 'VIPFunnelProductView',
    'product_to_business_bridge' => 'VIPFunnelProductView',
    'qualification_form' => 'VIPFunnelQualificationView',
    'demo_request' => 'VIPFunnelDemoRequestView',
    'start_package_offer' => 'VIPFunnelStartPackageView',
    'mentor_call_request' => 'VIPFunnelConversationView',
    'not_ready_nurture' => 'VIPFunnelNurtureView',
];
$meta_pixel_step_event = $meta_pixel_event_map[$meta_pixel_page_key] ?? 'VIPFunnelStepView';
$meta_pixel_step_parameters = [
    'content_category' => 'vip_funnel',
    'content_name' => (string) (($active['title'] ?? '') ?: ($payload['funnel']['name'] ?? 'VIP Funnel 2.0')),
    'funnel_id' => (int) ($state['funnel_id'] ?? 0),
    'funnel_name' => (string) ($payload['funnel']['name'] ?? 'VIP Funnel 2.0'),
    'funnel_slug' => (string) ($state['slug'] ?? ($payload['funnel']['slug'] ?? '')),
    'owner_user_id' => (int) ($state['user_id'] ?? ($payload['defaults']['owner_user_id'] ?? 0)),
    'page_key' => $meta_pixel_page_key,
    'page_role' => (string) ($state['page_role'] ?? 'landing'),
    'step_id' => (string) ($state['current_step_id'] ?? ''),
    'step_title' => (string) (($active['title'] ?? '') ?: ($payload['funnel']['name'] ?? 'VIP Funnel 2.0')),
    'path_title' => (string) ($state['path_title'] ?? ''),
    'variant' => strtoupper((string) ($state['variant_key'] ?? 'a')),
];
$background_color = $surface['background_color'] ?? '#0f172a';
$background_opacity = $clamp_opacity($surface['background_opacity'] ?? 100, 100);
$background_css_color = $color_with_opacity($background_color, $background_opacity, '#0f172a');
$background_image_url = trim((string) ($surface['background_image_url'] ?? ''));
$background_image_css_value = str_replace(["\r", "\n", '"', "'", '<', '>', '\\'], '', $background_image_url);
$background_image_css_url = $background_image_css_value !== '' ? json_encode($background_image_css_value, JSON_UNESCAPED_SLASHES) : '';
$surface_color = $surface['surface_color'] ?? '#152132';
$text_color = $surface['text_color'] ?? '#eef4ff';
$accent_color = $surface['accent_color'] ?? '#67d8c9';
$title = trim((string) ($active['title'] ?? ($payload['funnel']['name'] ?? 'VIP Funnel 2.0')));
$subtitle = trim((string) ($active['summary'] ?? ($payload['overview']['subheadline'] ?? '')));
$max_width = $surface['max_width'] ?? 'wide';
$canvas_max_width = $max_width === 'narrow' ? '680px' : ($max_width === 'regular' ? '860px' : '100%');
$viewer_key = (string) ($state['viewer_key'] ?? '');
$active_block_mode = (string) ($active['block_mode'] ?? '');
$page_has_capture_fields = false;
$page_has_deferred_survey = false;
$page_has_submit_action = false;
$font_family_css_map = function_exists('vip_funnel_get_page_font_family_css_map') ? vip_funnel_get_page_font_family_css_map() : ['inherit' => 'inherit'];
$resolve_font_family = static function($key) use ($font_family_css_map) {
    return $font_family_css_map[$key] ?? ($font_family_css_map['inherit'] ?? 'inherit');
};
foreach($blocks as $preview_block) {
    $preview_block = is_array($preview_block) ? $preview_block : [];
    if(in_array((string) ($preview_block['type'] ?? ''), ['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field', 'checkbox_field'], true)) {
        $page_has_capture_fields = true;
    }

    if((string) ($preview_block['type'] ?? '') === 'radio_survey') {
        $page_has_deferred_survey = true;
    }

    if(in_array((string) ($preview_block['type'] ?? ''), ['cta_group', 'survey'], true)) {
        $actions = (array) ((string) ($preview_block['type'] ?? '') === 'survey' ? ($preview_block['options'] ?? []) : ($preview_block['buttons'] ?? []));
        foreach($actions as $action) {
            $action = is_array($action) ? $action : [];
            if(!empty($action['require_submit']) || in_array((string) ($action['action'] ?? ''), ['submit_next', 'submit_stay'], true)) {
                $page_has_submit_action = true;
                break;
            }
        }
    }
}

$sticky_cta = null;
foreach($blocks as $preview_block) {
    $preview_block = is_array($preview_block) ? $preview_block : [];
    $preview_block_type = (string) ($preview_block['type'] ?? '');

    if(!in_array($preview_block_type, ['cta_group', 'survey'], true)) {
        continue;
    }

    $actions = (array) ($preview_block_type === 'survey' ? ($preview_block['options'] ?? []) : ($preview_block['buttons'] ?? []));

    foreach($actions as $action) {
        $action = is_array($action) ? $action : [];

        if(empty($action['sticky'])) {
            continue;
        }

        $is_submit = !empty($action['is_submit']) || in_array(($action['action'] ?? ''), ['submit_next', 'submit_stay'], true) || !empty($action['require_submit']) || ($page_has_capture_fields && in_array($active_block_mode, ['contact_form', 'video_form'], true));
        $sticky_url = trim((string) ($action['url'] ?? ($action['external_url'] ?? '#')));
        $sticky_signal_key = !$is_submit && class_exists('\\Altum\\Link') && \Altum\Link::is_monitored_forever_destination_url($sticky_url) ? 'forever_shop' : '';
        $sticky_cta = [
            'label' => (string) ($action['label'] ?? 'Nastavi'),
            'hint' => (string) ($action['hint'] ?? ''),
            'style' => in_array(($action['style'] ?? 'primary'), ['primary', 'secondary', 'ghost'], true) ? (string) ($action['style'] ?? 'primary') : 'primary',
            'action' => (string) ($action['action'] ?? ($is_submit ? 'submit_next' : 'goto_step')),
            'target' => (string) ($action['target_step_id'] ?? ''),
            'external' => $is_submit ? (string) ($action['external_url'] ?? '') : $sticky_url,
            'url' => $sticky_url !== '' ? $sticky_url : '#',
            'selection' => (string) ($action['value'] ?? ($action['label'] ?? '')),
            'block_id' => (string) ($preview_block['id'] ?? ''),
            'block_type' => $preview_block_type,
            'event_key' => (string) ($action['event_key'] ?? ''),
            'signal_key' => (string) (($action['signal_key'] ?? '') ?: $sticky_signal_key),
            'is_submit' => $is_submit,
        ];
        break 2;
    }
}

$surface_progress_current = (int) ($surface['progress_current'] ?? 0);
$surface_progress_total = (int) ($surface['progress_total'] ?? 0);
$surface_progress_label = trim((string) ($surface['progress_label'] ?? ''));
$fallback_progress_current = (int) ($state['current_step_number'] ?? 0);
$fallback_progress_total = (int) ($state['total_steps'] ?? 0);
$progress_current = $surface_progress_current > 0 ? $surface_progress_current : $fallback_progress_current;
$progress_total = $surface_progress_total > 0 ? $surface_progress_total : $fallback_progress_total;
$progress_percent = $progress_total > 0 ? max(0, min(100, (int) round(($progress_current / max(1, $progress_total)) * 100))) : 0;
$progress_label = $surface_progress_label !== '' ? $surface_progress_label : (($state['page_role'] ?? 'landing') === 'landing' && $surface_progress_current <= 0 ? 'Prvi dojam' : ('Korak ' . max(1, $progress_current) . ' od ' . max(1, $progress_total)));
$show_progress_bar = !empty($surface['show_progress']) && $progress_total > 0;
?>

<?= \Altum\Alerts::output_alerts() ?>

<style>
    <?php if($hide_public_navbar): ?>
    body .fcc-navbar-shell,
    body #navbar {
        display: none !important;
    }
    <?php endif ?>

    .vip-funnel-public {
        --vf-bg: <?= $e($background_css_color) ?>;
        --vf-surface: <?= $e($surface_color) ?>;
        --vf-text: <?= $e($text_color) ?>;
        --vf-accent: <?= $e($accent_color) ?>;
        padding: 3rem 0 4rem;
        min-height: 100vh;
        overflow-x: hidden;
        background-color: var(--vf-bg);
        <?php if($background_image_css_url): ?>
        background-image: linear-gradient(180deg, rgba(2,8,23,0.22), rgba(2,8,23,0.36)), url(<?= $background_image_css_url ?>);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        <?php endif ?>
    }

    .vip-funnel-public,
    .vip-funnel-public * {
        box-sizing: border-box;
    }

    .vip-funnel-public__wrap {
        width: 100%;
        max-width: 980px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    .vip-funnel-public__grid {
        display: block;
    }

    .vip-funnel-public__page-shell {
        width: 100%;
        background-color: var(--vf-bg);
        <?php if($background_image_css_url): ?>
        background-image: linear-gradient(180deg, rgba(2,8,23,0.22), rgba(2,8,23,0.36)), url(<?= $background_image_css_url ?>);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        <?php endif ?>
        border-radius: 1.6rem;
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 1.4rem 3rem rgba(2,8,23,0.22);
        padding: 1rem;
        color: var(--vf-text);
    }

    .vip-funnel-public__canvas {
        width: 100%;
        margin: 0 auto;
        max-width: <?= $e($canvas_max_width) ?>;
        border-radius: 1.45rem;
        background: var(--vf-surface);
        padding: 1rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
    }

    .vip-funnel-public__progress {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .7rem;
        margin-bottom: 1rem;
        color: rgba(236,243,255,0.72);
        font-size: .82rem;
        font-weight: 800;
    }

    .vip-funnel-public__progress-bar {
        flex: 1;
        height: .45rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.08);
        overflow: hidden;
    }

    .vip-funnel-public__progress-fill {
        height: 100%;
        border-radius: inherit;
        background: var(--vf-accent);
    }

    .vip-funnel-public__blocks {
        display: grid;
        grid-template-columns: repeat(60, minmax(0, 1fr));
        gap: .95rem 0;
    }

    .vip-funnel-public__block {
        min-width: 0;
        border-radius: 1.15rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.05);
        padding: 1rem;
        color: inherit;
    }

    .vip-funnel-public__blocks > [data-vf-span="full"] { grid-column: span 60; }
    .vip-funnel-public__blocks > [data-vf-span="half"] { grid-column: span 30; }
    .vip-funnel-public__blocks > [data-vf-span="third"] { grid-column: span 20; }
    .vip-funnel-public__blocks > [data-vf-span="two_thirds"] { grid-column: span 40; }
    .vip-funnel-public__blocks > [data-vf-span="quarter"] { grid-column: span 15; }
    .vip-funnel-public__blocks > [data-vf-span="three_quarters"] { grid-column: span 45; }
    .vip-funnel-public__blocks > [data-vf-span="fifth"] { grid-column: span 12; }
    .vip-funnel-public__blocks > [data-vf-span="sixth"] { grid-column: span 10; }

    .vip-funnel-public__blocks > [data-vf-span]:not([data-vf-span="full"]) {
        margin-inline: .45rem;
    }

    .vip-funnel-public__blocks > [data-vf-span="full"] {
        margin-inline: 0;
    }

    .vip-funnel-public__spacer {
        min-width: 0;
    }

    .vip-funnel-public__block.align-center { text-align: center; }
    .vip-funnel-public__block.align-right { text-align: right; }

    .vip-funnel-public__badge {
        display: inline-flex;
        align-items: center;
        padding: .32rem .68rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.1);
        font-size: .78rem;
        font-weight: 900;
        margin-bottom: .62rem;
    }

    .vip-funnel-public__block-title {
        font-size: clamp(1.45rem, 3vw, 2.65rem);
        line-height: 1.04;
        font-weight: 900;
        margin-bottom: .45rem;
    }

    .vip-funnel-public__block-text {
        font-size: 1rem;
        line-height: 1.7;
        opacity: .93;
    }

    .vip-funnel-public__media {
        width: 100%;
        min-height: 200px;
        border-radius: 1rem;
        border: 1px dashed rgba(255,255,255,0.16);
        background: rgba(255,255,255,0.04);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin-top: .85rem;
        text-align: center;
        color: rgba(236,243,255,0.62);
    }

    .vip-funnel-public__media.is-video {
        min-height: 0;
        aspect-ratio: 16 / 9;
        padding: 0;
        display: block;
        background: #000;
        border-style: solid;
    }

    .vip-funnel-public__media img {
        width: 100%;
        height: auto;
        display: block;
    }

    .vip-funnel-public__media iframe,
    .vip-funnel-public__media video {
        width: 100%;
        height: 100%;
        min-height: 0;
        border: 0;
        display: block;
        background: #000;
    }

    .vip-funnel-public__product-card {
        margin-top: .9rem;
        padding: 1rem;
        border-radius: 1.15rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.04);
    }

    .vip-funnel-public__product-inner {
        display: grid;
        grid-template-columns: minmax(0, 132px) minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
    }

    .vip-funnel-public__product-image {
        aspect-ratio: 1 / 1;
        overflow: hidden;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.05);
    }

    .vip-funnel-public__product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .vip-funnel-public__product-content {
        min-width: 0;
    }

    .vip-funnel-public__product-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-bottom: .55rem;
    }

    .vip-funnel-public__product-chip {
        display: inline-flex;
        align-items: center;
        padding: .3rem .62rem;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.08);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .vip-funnel-public__product-name {
        font-size: 1.18rem;
        font-weight: 900;
        line-height: 1.18;
        margin-bottom: .42rem;
    }

    .vip-funnel-public__product-description {
        font-size: .96rem;
        line-height: 1.6;
        color: rgba(236,243,255,0.82);
    }

    .vip-funnel-public__product-empty {
        padding: .95rem 1rem;
        border-radius: 1rem;
        border: 1px dashed rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.03);
        color: rgba(236,243,255,0.72);
        line-height: 1.6;
    }

    .vip-funnel-public__ai-card {
        margin-top: .95rem;
        display: grid;
        gap: .9rem;
        border-radius: 1.15rem;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.04);
        padding: 1rem;
    }

    .vip-funnel-public__ai-head {
        display: flex;
        align-items: center;
        gap: .82rem;
        min-width: 0;
    }

    .vip-funnel-public__ai-mark {
        width: 48px;
        height: 48px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        background: radial-gradient(circle at 35% 30%, rgba(255,255,255,0.96), rgba(103,216,201,0.22) 46%, rgba(103,216,201,0.08));
        color: #0f172a;
        font-weight: 900;
        box-shadow: 0 10px 26px rgba(2,8,23,0.22);
    }

    .vip-funnel-public__ai-copy {
        min-width: 0;
        display: grid;
        gap: .16rem;
        line-height: 1.35;
    }

    .vip-funnel-public__ai-title {
        font-weight: 900;
    }

    .vip-funnel-public__ai-text {
        color: rgba(236,243,255,0.74);
        font-size: .92rem;
    }

    .vip-funnel-public__ai-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }

    .vip-funnel-public__ai-chip {
        display: inline-flex;
        align-items: center;
        padding: .32rem .64rem;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.07);
        font-size: .74rem;
        font-weight: 800;
    }

    .vip-funnel-public__actions {
        display: grid;
        gap: .68rem;
        margin-top: .9rem;
    }

    .vip-funnel-public__btn,
    .vip-funnel-public__btn:visited {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .2rem;
        width: 100%;
        padding: .96rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.12);
        font-weight: 900;
        text-align: center;
        text-decoration: none;
        transition: transform .18s ease, opacity .18s ease;
    }

    .vip-funnel-public__btn-label {
        display: block;
        width: 100%;
    }

    .vip-funnel-public__btn-hint {
        display: block;
        width: 100%;
        max-width: 780px;
        font-size: .78em;
        line-height: 1.35;
        font-weight: 700;
        opacity: .78;
    }

    .vip-funnel-public__btn:hover {
        transform: translateY(-1px);
        text-decoration: none;
    }

    .vip-funnel-public__btn.is-submitting {
        opacity: .78;
        cursor: progress;
        pointer-events: none;
    }

    .vip-funnel-public__btn.is-submitting .vip-funnel-public__btn-label::after {
        content: '...';
    }

    .vip-funnel-public__btn.is-primary {
        background: var(--vf-accent);
        color: #0f172a;
        border-color: transparent;
    }

    .vip-funnel-public__btn.is-secondary {
        background: rgba(255,255,255,0.1);
        color: var(--vf-text);
    }

    .vip-funnel-public__btn.is-ghost {
        background: transparent;
        color: var(--vf-text);
    }

    .vip-funnel-public__field {
        width: 100%;
        border-radius: .95rem;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.06);
        color: var(--vf-text);
        padding: .95rem 1rem;
        outline: none;
        margin-top: .7rem;
    }

    .vip-funnel-public__field::placeholder {
        color: var(--vf-placeholder-color, rgba(236,243,255,0.48));
    }

    .vip-funnel-public__checkbox {
        display: flex;
        align-items: flex-start;
        gap: .78rem;
        margin-top: .7rem;
        padding: .9rem 1rem;
        border-radius: .95rem;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.06);
        cursor: pointer;
    }

    .vip-funnel-public__checkbox input[type="checkbox"] {
        width: 1.08rem;
        height: 1.08rem;
        margin-top: .18rem;
        accent-color: var(--vf-accent);
        flex: 0 0 auto;
    }

    .vip-funnel-public__checkbox-copy {
        display: grid;
        gap: .22rem;
        min-width: 0;
    }

    .vip-funnel-public__checkbox-title {
        line-height: 1.35;
    }

    .vip-funnel-public__checkbox-text {
        font-size: .88rem;
        line-height: 1.48;
        color: rgba(236,243,255,0.68);
    }

    .vip-funnel-public__radio-list {
        display: grid;
        gap: .7rem;
        margin-top: .9rem;
    }

    .vip-funnel-public__radio-option {
        display: flex;
        align-items: flex-start;
        gap: .8rem;
        padding: .9rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.05);
        cursor: pointer;
        transition: border-color .18s ease, background .18s ease, transform .18s ease;
    }

    .vip-funnel-public__radio-option:hover {
        border-color: rgba(103,216,201,0.26);
        background: rgba(255,255,255,0.07);
        transform: translateY(-1px);
    }

    .vip-funnel-public__radio-option input[type="radio"] {
        width: 1.05rem;
        height: 1.05rem;
        margin-top: .2rem;
        accent-color: var(--vf-accent);
        flex: 0 0 auto;
    }

    .vip-funnel-public__radio-copy {
        display: grid;
        gap: .18rem;
    }

    .vip-funnel-public__radio-title {
        font-weight: 800;
        line-height: 1.4;
    }

    .vip-funnel-public__radio-hint {
        font-size: .86rem;
        line-height: 1.5;
        color: rgba(236,243,255,0.66);
    }

    .vip-funnel-public__countdown {
        width: 100%;
        display: grid;
        gap: .75rem;
        margin-top: .85rem;
    }

    .vip-funnel-public__countdown-row {
        display: grid;
        gap: .75rem;
        align-items: stretch;
    }

    .vip-funnel-public__countdown-row.is-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .vip-funnel-public__countdown-row.is-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .vip-funnel-public__countdown-row.is-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .vip-funnel-public__countdown-row.is-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

    .vip-funnel-public__countdown-item {
        min-width: 0;
        padding: .95rem .6rem;
        border-radius: 1.1rem;
        border: 1px solid var(--vf-countdown-border, rgba(255,255,255,0.08));
        display: grid;
        place-items: center;
        gap: .28rem;
        text-align: center;
    }

    .vip-funnel-public__countdown-value {
        line-height: 1;
        font-weight: 900;
        letter-spacing: -.03em;
    }

    .vip-funnel-public__countdown-label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: rgba(236,243,255,0.62);
    }

    .vip-funnel-public__countdown--cards .vip-funnel-public__countdown-item {
        background: var(--vf-countdown-surface, rgba(255,255,255,0.06));
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .vip-funnel-public__countdown--glass .vip-funnel-public__countdown-row {
        gap: .55rem;
        padding: .65rem;
        border-radius: 1.25rem;
        background: linear-gradient(135deg, var(--vf-countdown-glass-a, rgba(255,255,255,0.08)), var(--vf-countdown-glass-b, rgba(255,255,255,0.03)));
        border: 1px solid var(--vf-countdown-border, rgba(255,255,255,0.08));
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
        backdrop-filter: blur(10px);
    }

    .vip-funnel-public__countdown--glass .vip-funnel-public__countdown-item {
        background: var(--vf-countdown-glass-item, rgba(11,17,27,0.35));
    }

    .vip-funnel-public__countdown--minimal .vip-funnel-public__countdown-item {
        background: transparent;
        border: 0;
        border-bottom: 1px solid var(--vf-countdown-border, rgba(255,255,255,0.1));
        border-radius: 0;
        padding: .2rem 0 .65rem;
    }

    .vip-funnel-public__countdown--spotlight .vip-funnel-public__countdown-row {
        padding: .8rem;
        border-radius: 1.35rem;
        background:
            radial-gradient(circle at top, rgba(255,255,255,0.12), rgba(255,255,255,0.02) 55%),
            linear-gradient(145deg, rgba(255,255,255,0.06), rgba(10,18,28,0.08));
        border: 1px solid var(--vf-countdown-border, rgba(255,255,255,0.08));
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), 0 18px 36px rgba(2,8,23,0.18);
    }

    .vip-funnel-public__countdown--spotlight .vip-funnel-public__countdown-item {
        background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
        box-shadow: 0 10px 24px rgba(2,8,23,0.16);
    }

    .vip-funnel-public__countdown.is-expired .vip-funnel-public__countdown-item {
        background: rgba(255,122,147,0.1);
        border-color: rgba(255,122,147,0.18);
    }

    .vip-funnel-public__countdown-expired {
        padding: 1rem 1.1rem;
        border-radius: 1.15rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: linear-gradient(135deg, rgba(255,122,147,0.18), rgba(255,255,255,0.04));
        display: grid;
        gap: .35rem;
        text-align: center;
    }

    .vip-funnel-public__countdown-expired-kicker {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: rgba(255,232,238,0.78);
    }

    .vip-funnel-public__countdown-expired-text {
        font-size: 1rem;
        font-weight: 800;
        color: #fff3f6;
        line-height: 1.45;
    }

    .vip-funnel-public__sticky-cta {
        display: none;
    }

    @media (max-width: 720px) {
        .vip-funnel-public {
            padding: 1.15rem 0 calc(2.75rem + env(safe-area-inset-bottom));
            background-attachment: scroll;
        }

        .vip-funnel-public.has-sticky-cta {
            padding-bottom: calc(8.2rem + env(safe-area-inset-bottom));
        }

        .vip-funnel-public__wrap {
            padding: 0 .55rem;
        }

        .vip-funnel-public__page-shell {
            border-radius: 1.15rem;
            padding: .5rem;
        }

        .vip-funnel-public__canvas {
            border-radius: 1rem;
            padding: .65rem;
        }

        .vip-funnel-public__blocks {
            grid-template-columns: minmax(0, 1fr);
            gap: .72rem;
        }

        .vip-funnel-public__blocks > [data-vf-span] {
            grid-column: span 1;
            margin-inline: 0;
        }

        .vip-funnel-public__block {
            border-radius: .95rem;
            padding: .9rem;
        }

        .vip-funnel-public__badge {
            font-size: .72rem !important;
            line-height: 1.1;
            margin-bottom: .5rem;
            padding: .28rem .6rem;
        }

        .vip-funnel-public__block-title {
            font-size: clamp(1.35rem, 6.9vw, 1.85rem) !important;
            line-height: 1.08 !important;
            overflow-wrap: break-word;
            hyphens: auto;
        }

        .vip-funnel-public__block[data-vf-block-type="headline"] .vip-funnel-public__block-title {
            font-size: clamp(1.48rem, 7.8vw, 2rem) !important;
        }

        .vip-funnel-public__block-text {
            font-size: clamp(.98rem, 4.5vw, 1.15rem) !important;
            line-height: 1.55 !important;
        }

        .vip-funnel-public__media {
            min-height: 140px;
            border-radius: .85rem;
            margin-top: .72rem;
        }

        .vip-funnel-public__actions {
            gap: .58rem;
            margin-top: .78rem;
        }

        .vip-funnel-public__btn,
        .vip-funnel-public__btn:visited {
            border-radius: .85rem;
            font-size: 1rem !important;
            line-height: 1.25;
            padding: .86rem .75rem;
        }

        .vip-funnel-public__field {
            border-radius: .85rem;
            font-size: 1rem !important;
            padding: .86rem .85rem;
        }

        .vip-funnel-public__checkbox {
            border-radius: .85rem;
            padding: .82rem .85rem;
        }

        .vip-funnel-public__radio-title {
            font-size: 1rem !important;
            line-height: 1.3;
        }

        .vip-funnel-public__countdown-row.is-cols-3,
        .vip-funnel-public__countdown-row.is-cols-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .vip-funnel-public__product-inner {
            grid-template-columns: 1fr;
        }

        .vip-funnel-public__product-image {
            max-width: 220px;
        }

        .vip-funnel-public__sticky-cta {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1080;
            display: block;
            padding: .72rem .72rem calc(.72rem + env(safe-area-inset-bottom));
            background: linear-gradient(180deg, rgba(8,13,23,0), rgba(8,13,23,0.94) 22%, rgba(8,13,23,0.98));
            border-top: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(14px);
        }

        .vip-funnel-public__sticky-cta-inner {
            max-width: 620px;
            margin: 0 auto;
        }

        .vip-funnel-public__sticky-cta .vip-funnel-public__btn {
            box-shadow: 0 12px 26px rgba(2,8,23,0.28);
        }
    }

    @media (max-width: 420px) {
        .vip-funnel-public__block-title {
            font-size: clamp(1.28rem, 6.8vw, 1.72rem) !important;
        }

        .vip-funnel-public__block[data-vf-block-type="headline"] .vip-funnel-public__block-title {
            font-size: clamp(1.42rem, 7.6vw, 1.9rem) !important;
        }

        .vip-funnel-public__block {
            padding: .82rem;
        }

        .vip-funnel-public__block-text {
            font-size: clamp(.95rem, 4.4vw, 1.08rem) !important;
        }
    }

</style>

<?php if($facebook_pixel_id !== ''): ?>
    <?php
    $meta_pixel_script_attributes = settings()->cookie_consent->is_enabled ? 'type="text/plain" data-category="targeting"' : '';
    $meta_pixel_step_parameters_json = function_exists('vip_funnel_json_encode') ? vip_funnel_json_encode($meta_pixel_step_parameters) : json_encode($meta_pixel_step_parameters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $meta_pixel_step_event_json = json_encode($meta_pixel_step_event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $facebook_pixel_id_json = json_encode($facebook_pixel_id, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?>
    <script <?= $meta_pixel_script_attributes ?>>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', <?= $facebook_pixel_id_json ?>);
        fbq('track', 'PageView');

        window.vipFunnelMetaPixelParams = <?= $meta_pixel_step_parameters_json ?>;
        window.vipFunnelTrackMeta = function(eventName, params, standardEvent) {
            if(typeof fbq !== 'function' || !eventName) return;

            const eventParams = Object.assign({}, window.vipFunnelMetaPixelParams || {}, params || {});
            fbq(standardEvent ? 'track' : 'trackCustom', eventName, eventParams);
        };

        window.vipFunnelTrackMeta('VIPFunnelStepView');
        if(<?= $meta_pixel_step_event_json ?> !== 'VIPFunnelStepView') {
            window.vipFunnelTrackMeta(<?= $meta_pixel_step_event_json ?>);
        }
    </script>
    <?php if(!settings()->cookie_consent->is_enabled): ?>
        <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= $e($facebook_pixel_id) ?>&ev=PageView&noscript=1" alt="" /></noscript>
    <?php endif ?>
<?php endif ?>

<div class="vip-funnel-public<?= $sticky_cta ? ' has-sticky-cta' : '' ?>">
    <div class="vip-funnel-public__wrap">
        <div class="vip-funnel-public__grid">
            <div class="vip-funnel-public__page-shell">
                <div class="vip-funnel-public__canvas">
                    <form method="post" id="vip-funnel-public-form">
                        <input type="hidden" name="vf_action" id="vf_action" value="submit_next">
                        <input type="hidden" name="vf_target_step_id" id="vf_target_step_id" value="">
                        <input type="hidden" name="vf_external_url" id="vf_external_url" value="">
                        <input type="hidden" name="vf_selection" id="vf_selection" value="">
                        <input type="hidden" name="vf_block_id" id="vf_block_id" value="">
                        <input type="hidden" name="vf_event_key" id="vf_event_key" value="">
                        <input type="hidden" name="viewer_key" value="<?= $e($viewer_key) ?>">

                        <?php if($show_progress_bar): ?>
                            <div class="vip-funnel-public__progress">
                                <span><?= $e($progress_label) ?></span>
                                <div class="vip-funnel-public__progress-bar"><div class="vip-funnel-public__progress-fill" style="width: <?= $e($progress_percent) ?>%;"></div></div>
                            </div>
                        <?php endif ?>

                        <div class="vip-funnel-public__blocks">
                            <?php foreach($blocks as $block): ?>
                                <?php
                                $block = is_array($block) ? $block : [];
                                $block_id = (string) ($block['id'] ?? '');
                                $block_type = (string) ($block['type'] ?? 'headline');
                                $layout_width = array_key_exists((string) ($block['layout_width'] ?? 'full'), vip_funnel_get_page_block_width_options()) ? (string) $block['layout_width'] : 'full';
                                $alignment = (string) ($block['alignment'] ?? 'left');
                                $font_family = $resolve_font_family((string) ($block['font_family'] ?? 'inherit'));
                                $badge_size = max(10, min(32, (int) ($block['badge_size'] ?? 13)));
                                $badge_weight = max(400, min(900, (int) ($block['badge_weight'] ?? 800)));
                                $title_size = max(16, min(96, (int) ($block['title_size'] ?? 28)));
                                $title_weight = max(400, min(900, (int) ($block['title_weight'] ?? 800)));
                                $text_size = max(12, min(48, (int) ($block['text_size'] ?? 17)));
                                $text_weight = max(400, min(900, (int) ($block['text_weight'] ?? 500)));
                                $field_size = max(12, min(36, (int) ($block['field_size'] ?? 16)));
                                $field_weight = max(400, min(900, (int) ($block['field_weight'] ?? 500)));
                                $button_size = max(12, min(36, (int) ($block['button_size'] ?? 17)));
                                $button_weight = max(400, min(900, (int) ($block['button_weight'] ?? 800)));
                                $base_text_color = !empty($block['text_color']) ? (string) $block['text_color'] : $text_color;
                                $badge_color = !empty($block['badge_color']) ? (string) $block['badge_color'] : $base_text_color;
                                $title_color = !empty($block['title_color']) ? (string) $block['title_color'] : $base_text_color;
                                $body_color = !empty($block['body_color']) ? (string) $block['body_color'] : $base_text_color;
                                $field_text_color = !empty($block['field_text_color']) ? (string) $block['field_text_color'] : $base_text_color;
                                $placeholder_color = !empty($block['placeholder_color']) ? (string) $block['placeholder_color'] : '#aab4c7';
                                $button_text_color = !empty($block['button_text_color']) ? (string) $block['button_text_color'] : '';
                                $block_style = [];
                                if(!empty($block['background_color'])) $block_style[] = 'background:' . $color_with_opacity($block['background_color'], $block['background_opacity'] ?? 100, (string) $block['background_color']);
                                $block_style[] = 'color:' . $base_text_color;
                                if(!empty($block['accent_color'])) $block_style[] = '--vf-accent:' . $block['accent_color'];
                                $block_style[] = 'font-family:' . $font_family;
                                $block_style[] = '--vf-placeholder-color:' . $placeholder_color;

                                if($block_type === 'ai_product_advisor' && isset($block['ai_advisor_enabled']) && empty($block['ai_advisor_enabled'])) {
                                    continue;
                                }

                                $ai_block_can_render = false;
                                if($block_type === 'ai_product_advisor') {
                                    $ai_block_can_render = $ai_public_access_enabled && $ai_owner_link_id > 0 && function_exists('fcc_ai_get_assistant_type');

                                    if($ai_block_can_render && !$ai_widget_registered) {
                                        $ai_intro_label = trim((string) ($block['ai_intro_label'] ?? 'Tvoj osobni vodič')) ?: 'Tvoj osobni vodič';
                                        $ai_launcher_label = trim((string) ($block['ai_launcher_label'] ?? 'Moja preporuka')) ?: 'Moja preporuka';
                                        $ai_input_placeholder = trim((string) ($block['ai_input_placeholder'] ?? 'Napiši cilj ili pitanje...')) ?: 'Napiši cilj ili pitanje...';
                                        $ai_source_context = 'VIP Funnel pametna preporuka - ' . trim((string) (($active['title'] ?? '') ?: ($payload['funnel']['name'] ?? 'VIP Funnel 2.0')));

                                        ob_start();
                                        echo include_view(THEME_PATH . 'views/l/partials/fcc_chat_extreme_popup.php', [
                                            'config' => [
                                                'assistant_type' => 'product_advisor',
                                                'scope' => 'public_app',
                                                'link_id' => $ai_owner_link_id,
                                                'blog_post_id' => 0,
                                                'owner_name' => trim((string) ($owner_profile['name'] ?? '')),
                                                'language_code' => $ai_language_code,
                                                'source_context' => $ai_source_context,
                                                'hide_without_context' => false,
                                                'dom_id' => $ai_widget_dom_id,
                                                'intro_label' => $ai_intro_label,
                                                'launcher_label' => $ai_launcher_label,
                                                'input_placeholder' => $ai_input_placeholder,
                                                'lead_enabled' => !isset($block['ai_lead_capture_enabled']) || !empty($block['ai_lead_capture_enabled']),
                                            ],
                                        ]);
                                        \Altum\Event::add_content(ob_get_clean(), 'modals', 'vip_funnel_ai_advisor_' . $ai_widget_dom_id);
                                        $ai_widget_registered = true;
                                    }
                                }
                                ?>

                                <?php if($block_type === 'spacer'): ?>
                                    <?php
                                    $spacing_map = ['xs' => '24px', 'sm' => '40px', 'md' => '60px', 'lg' => '92px', 'xl' => '128px'];
                                    $spacing_height = $spacing_map[$block['spacing'] ?? 'md'] ?? '60px';
                                    ?>
                                    <div class="vip-funnel-public__spacer" data-vf-span="<?= $e($layout_width) ?>" style="height: <?= $e($spacing_height) ?>"></div>
                                    <?php continue; ?>
                                <?php endif ?>

                                <div class="vip-funnel-public__block align-<?= $e($alignment) ?>" data-vf-span="<?= $e($layout_width) ?>" data-vf-block-type="<?= $e($block_type) ?>" style="<?= $e(implode(';', $block_style)) ?>">
                                    <?php if(!empty($block['badge'])): ?>
                                        <div class="vip-funnel-public__badge" style="font-size: <?= $e($badge_size) ?>px; font-weight: <?= $e($badge_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($badge_color) ?>;"><?= $e($block['badge']) ?></div>
                                    <?php endif ?>

                                    <?php if(!empty($block['title'])): ?>
                                        <div class="vip-funnel-public__block-title" style="font-size: <?= $e($title_size) ?>px; font-weight: <?= $e($title_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($title_color) ?>;"><?= $e($block['title']) ?></div>
                                    <?php endif ?>

                                    <?php if(!empty($block['text'])): ?>
                                        <div class="vip-funnel-public__block-text" style="font-size: <?= $e($text_size) ?>px; font-weight: <?= $e($text_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($body_color) ?>;"><?= $e($block['text']) ?></div>
                                    <?php endif ?>

                                    <?php if($block_type === 'product_offer'): ?>
                                        <?php
                                        $product = is_array($block['product_resolved'] ?? null) ? $block['product_resolved'] : [];
                                        $primary_mode = (string) ($block['product_primary_mode'] ?? 'blog_guide');
                                        $secondary_mode = (string) ($block['product_secondary_mode'] ?? 'direct_shop');
                                        $primary_mode_label = (string) ($block['product_primary_mode_label'] ?? ($primary_mode === 'direct_shop' ? 'Vodi direktno na službeni shop' : 'Vodi na blog vodič'));
                                        $secondary_mode_label = (string) ($block['product_secondary_mode_label'] ?? ($secondary_mode === 'direct_shop' ? 'Vodi direktno na službeni shop' : 'Vodi na blog vodič'));
                                        $primary_url = trim((string) ($block['product_primary_url'] ?? ''));
                                        $secondary_url = trim((string) ($block['product_secondary_url'] ?? ''));
                                        $primary_cta_text = trim((string) ($block['product_primary_cta_text'] ?? 'Pogledaj vodič proizvoda'));
                                        $secondary_cta_text = trim((string) ($block['product_secondary_cta_text'] ?? 'Idi na službeni shop'));
                                        $secondary_enabled = !empty($block['product_secondary_enabled']) && $secondary_url !== '';
                                        $resolved_language_code = mb_strtoupper((string) (($product['language_code'] ?? '') ?: ($block['product_language_code'] ?? \Altum\Language::$code)));
                                        $product_source_mode = (string) ($block['product_source_mode'] ?? 'manual');
                                        $matched_mapping = is_array($block['product_matched_mapping'] ?? null) ? $block['product_matched_mapping'] : [];
                                        ?>
                                        <div class="vip-funnel-public__product-card" style="border-color: <?= $e($hex_alpha($accent_color, '33')) ?>; background: <?= $e($hex_alpha($accent_color, '12')) ?>;">
                                            <?php if($product): ?>
                                                <div class="vip-funnel-public__product-inner">
                                                    <?php if(!empty($product['image_url'])): ?>
                                                        <div class="vip-funnel-public__product-image">
                                                            <img src="<?= $e($product['image_url']) ?>" alt="<?= $e($product['title'] ?? 'Proizvod') ?>">
                                                        </div>
                                                    <?php endif ?>
                                                    <div class="vip-funnel-public__product-content">
                                                        <div class="vip-funnel-public__product-meta">
                                                            <span class="vip-funnel-public__product-chip"><?= $e($product_source_mode === 'dynamic' ? 'Dinamički proizvod' : 'Zadani proizvod') ?></span>
                                                            <span class="vip-funnel-public__product-chip"><?= $e($primary_mode_label) ?></span>
                                                            <span class="vip-funnel-public__product-chip"><?= $e(($block['product_language_mode'] ?? 'page') === 'manual' ? 'Ručni jezik' : 'Jezik stranice') ?></span>
                                                            <?php if($resolved_language_code !== ''): ?>
                                                                <span class="vip-funnel-public__product-chip"><?= $e($resolved_language_code) ?></span>
                                                            <?php endif ?>
                                                        </div>
                                                        <?php if($product_source_mode === 'dynamic' && !empty($matched_mapping['match_value'])): ?>
                                                            <div class="vip-funnel-public__product-description" style="margin-bottom:.5rem;color:rgba(236,243,255,0.72);">Preporuka je prilagođena odgovoru: <strong><?= $e($matched_mapping['match_value']) ?></strong></div>
                                                        <?php endif ?>
                                                        <div class="vip-funnel-public__product-name"><?= $e($product['title'] ?? 'Odabrani proizvod') ?></div>
                                                        <?php if(!empty($product['description'])): ?>
                                                            <div class="vip-funnel-public__product-description"><?= $e($product['description']) ?></div>
                                                        <?php endif ?>
                                                        <div class="vip-funnel-public__actions">
                                                            <?php if($primary_url !== ''): ?>
                                                                <?php $primary_signal_key = $primary_mode === 'direct_shop' ? 'forever_shop' : ''; ?>
                                                                <a
                                                                    href="<?= $e($primary_url) ?>"
                                                                    class="vip-funnel-public__btn is-primary"
                                                                    style="font-size: <?= $e($button_size) ?>px; font-weight: <?= $e($button_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($button_text_color !== '' ? $button_text_color : '#0f172a') ?>;"
                                                                    data-vf-track="cta_click"
                                                                    data-vf-block="<?= $e($block_id) ?>"
                                                                    data-vf-block-type="<?= $e($block_type) ?>"
                                                                    data-vf-label="<?= $e($primary_cta_text) ?>"
                                                                    data-vf-action="<?= $e($primary_mode) ?>"
                                                                    data-vf-external="<?= $e($primary_url) ?>"
                                                                    data-vf-selection="<?= $e((string) (($block['product_matched_mapping']['match_value'] ?? '') ?: (($state['runtime_context']['selection'] ?? '') ?: ''))) ?>"
                                                                    data-vf-signal-key="<?= $e($primary_signal_key) ?>"
                                                                    data-vf-event-key="<?= $e(($block['event_key'] ?? '') ?: ($primary_signal_key === 'forever_shop' ? 'click_webshop' : 'click_product_offer')) ?>"
                                                                ><?= $e($primary_cta_text) ?></a>
                                                            <?php endif ?>
                                                            <?php if($secondary_enabled): ?>
                                                                <?php $secondary_signal_key = $secondary_mode === 'direct_shop' ? 'forever_shop' : ''; ?>
                                                                <a
                                                                    href="<?= $e($secondary_url) ?>"
                                                                    class="vip-funnel-public__btn is-secondary"
                                                                    style="font-size: <?= $e($button_size) ?>px; font-weight: <?= $e($button_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($button_text_color !== '' ? $button_text_color : $body_color) ?>;"
                                                                    data-vf-track="cta_click"
                                                                    data-vf-block="<?= $e($block_id) ?>"
                                                                    data-vf-block-type="<?= $e($block_type) ?>"
                                                                    data-vf-label="<?= $e($secondary_cta_text) ?>"
                                                                    data-vf-action="<?= $e($secondary_mode) ?>"
                                                                    data-vf-external="<?= $e($secondary_url) ?>"
                                                                    data-vf-selection="<?= $e((string) (($block['product_matched_mapping']['match_value'] ?? '') ?: (($state['runtime_context']['selection'] ?? '') ?: ''))) ?>"
                                                                    data-vf-signal-key="<?= $e($secondary_signal_key) ?>"
                                                                    data-vf-event-key="<?= $e(($block['event_key'] ?? '') ?: ($secondary_signal_key === 'forever_shop' ? 'click_webshop' : 'click_product_offer')) ?>"
                                                                ><?= $e($secondary_cta_text) ?></a>
                                                            <?php endif ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="vip-funnel-public__product-empty">Ovaj produktni blok još nema odabran proizvod. Vrati se u VIP Funnel Studio i poveži ga s proizvodom iz blog kataloga.</div>
                                            <?php endif ?>
                                        </div>
                                    <?php endif ?>

                                    <?php if($block_type === 'ai_product_advisor'): ?>
                                        <?php
                                        $ai_button_label = trim((string) ($block['ai_button_label'] ?? 'Započni moju preporuku')) ?: 'Započni moju preporuku';
                                        $ai_intro_label_public = trim((string) ($block['ai_intro_label'] ?? 'Tvoj osobni vodič')) ?: 'Tvoj osobni vodič';
                                        $ai_lead_enabled_public = !isset($block['ai_lead_capture_enabled']) || !empty($block['ai_lead_capture_enabled']);
                                        ?>
                                        <div class="vip-funnel-public__ai-card" style="border-color: <?= $e($hex_alpha($accent_color, '33')) ?>; background: linear-gradient(135deg, <?= $e($hex_alpha($accent_color, '24')) ?>, rgba(255,255,255,0.04));">
                                            <div class="vip-funnel-public__ai-head">
                                                <div class="vip-funnel-public__ai-mark">AI</div>
                                                <div class="vip-funnel-public__ai-copy">
                                                    <div class="vip-funnel-public__ai-title" style="font-size: <?= $e(max(16, min(32, $text_size + 1))) ?>px; font-weight: <?= $e(max(700, $title_weight)) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($title_color) ?>;"><?= $e($ai_intro_label_public) ?></div>
                                                    <div class="vip-funnel-public__ai-text">Odgovori prirodno, bez traženja po katalogu. Savjetnik pomaže složiti preporuku prema tvojem cilju.</div>
                                                </div>
                                            </div>
                                            <div class="vip-funnel-public__ai-chips">
                                                <span class="vip-funnel-public__ai-chip">Preporuka po tvojem cilju</span>
                                                <span class="vip-funnel-public__ai-chip"><?= $e($ai_lead_enabled_public ? 'Moguć osobni nastavak' : 'Bez kontakt forme') ?></span>
                                            </div>
                                            <?php if($ai_block_can_render): ?>
                                                <div class="vip-funnel-public__actions" style="margin-top:0;">
                                                    <button
                                                        type="button"
                                                        class="vip-funnel-public__btn is-primary"
                                                        style="font-size: <?= $e($button_size) ?>px; font-weight: <?= $e($button_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($button_text_color !== '' ? $button_text_color : '#0f172a') ?>;"
                                                        data-vf-track="cta_click"
                                                        data-vf-block="<?= $e($block_id) ?>"
                                                        data-vf-block-type="<?= $e($block_type) ?>"
                                                        data-vf-label="<?= $e($ai_button_label) ?>"
                                                        data-vf-action="open_ai_product_advisor"
                                                        data-vf-target=""
                                                        data-vf-external=""
                                                        data-vf-selection="fcc_preporuka"
                                                        data-vf-signal-key="fcc_ai_product_advisor"
                                                        data-vf-event-key="<?= $e(($block['event_key'] ?? '') ?: 'open_ai_widget') ?>"
                                                        onclick="return window.fccChatExtremeToggle ? window.fccChatExtremeToggle(<?= $e(json_encode($ai_widget_dom_id)) ?>, true) : false;"
                                                    ><?= $e($ai_button_label) ?></button>
                                                </div>
                                            <?php else: ?>
                                                <div class="vip-funnel-public__product-empty">AI savjetnik trenutno nije dostupan za ovu stranicu.</div>
                                            <?php endif ?>
                                        </div>
                                    <?php endif ?>

                                    <?php if(in_array($block_type, ['image', 'video'], true)): ?>
                                        <div class="vip-funnel-public__media <?= $block_type === 'video' ? 'is-video' : '' ?>">
                                            <?php if(!empty($block['media_url'])): ?>
                                                <?php if($block_type === 'image'): ?>
                                                    <img src="<?= $e($block['media_url']) ?>" alt="">
                                                <?php else: ?>
                                                    <?php $embed_url = vip_funnel_get_video_embed_url((string) $block['media_url']); ?>
                                                    <?php if($embed_url !== ''): ?>
                                                        <iframe
                                                            src="<?= $e($embed_url) ?>"
                                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                            allowfullscreen
                                                            loading="lazy"
                                                            referrerpolicy="strict-origin-when-cross-origin"
                                                        ></iframe>
                                                    <?php elseif(vip_funnel_is_direct_video_file_url((string) $block['media_url'])): ?>
                                                        <video controls preload="metadata">
                                                            <source src="<?= $e($block['media_url']) ?>">
                                                        </video>
                                                    <?php else: ?>
                                                        <div>Video URL<br><?= $e($block['media_url']) ?></div>
                                                    <?php endif ?>
                                                <?php endif ?>
                                            <?php else: ?>
                                                <div><?= $e($block_type === 'image' ? 'Ovdje ide slika' : 'Ovdje ide video') ?></div>
                                            <?php endif ?>
                                        </div>
                                    <?php endif ?>

                                    <?php if(in_array($block_type, ['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field'], true)): ?>
                                        <?php
                                        $input_type = $block_type === 'email_field' ? 'email' : ($block_type === 'phone_field' ? 'tel' : 'text');
                                        $placeholder = (string) ($block['placeholder'] ?? ($block['title'] ?? ''));
                                        ?>
                                        <input
                                            class="vip-funnel-public__field"
                                            type="<?= $e($input_type) ?>"
                                            name="vf_field_<?= $e($block_id) ?>"
                                            placeholder="<?= $e($placeholder) ?>"
                                            style="font-size: <?= $e($field_size) ?>px; font-weight: <?= $e($field_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($field_text_color) ?>; --vf-placeholder-color: <?= $e($placeholder_color) ?>;"
                                            <?= !empty($block['required']) ? 'required' : '' ?>
                                        />
                                    <?php endif ?>

                                    <?php if($block_type === 'checkbox_field'): ?>
                                        <?php $checkbox_value = isset($_POST['vf_field_' . $block_id]) ? (string) $_POST['vf_field_' . $block_id] : ''; ?>
                                        <label class="vip-funnel-public__checkbox">
                                            <input
                                                type="checkbox"
                                                name="vf_field_<?= $e($block_id) ?>"
                                                value="1"
                                                <?= $checkbox_value !== '' ? 'checked' : '' ?>
                                                <?= !empty($block['required']) ? 'required' : '' ?>
                                            />
                                            <span class="vip-funnel-public__checkbox-copy">
                                                <?php if(!empty($block['title'])): ?>
                                                    <span class="vip-funnel-public__checkbox-title" style="font-size: <?= $e($field_size) ?>px; font-weight: <?= $e($field_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($field_text_color) ?>;"><?= $e($block['title']) ?></span>
                                                <?php endif ?>
                                                <?php if(!empty($block['text'])): ?>
                                                    <span class="vip-funnel-public__checkbox-text"><?= $e($block['text']) ?></span>
                                                <?php endif ?>
                                            </span>
                                        </label>
                                    <?php endif ?>

                                    <?php if($block_type === 'radio_survey' && !empty($block['options'])): ?>
                                        <div class="vip-funnel-public__radio-list">
                                            <?php foreach($block['options'] as $option): ?>
                                                <?php
                                                $radio_value = (string) ($option['value'] ?? ($option['label'] ?? ''));
                                                $is_checked = isset($_POST['vf_radio_' . $block_id]) && (string) ($_POST['vf_radio_' . $block_id] ?? '') === $radio_value;
                                                ?>
                                                <label class="vip-funnel-public__radio-option">
                                                    <input
                                                        type="radio"
                                                        name="vf_radio_<?= $e($block_id) ?>"
                                                        value="<?= $e($radio_value) ?>"
                                                        <?= $is_checked ? 'checked' : '' ?>
                                                        <?= !empty($block['required']) ? 'required' : '' ?>
                                                    />
                                                    <span class="vip-funnel-public__radio-copy">
                                                        <span class="vip-funnel-public__radio-title" style="font-size: <?= $e($text_size) ?>px; font-weight: <?= $e($button_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($body_color) ?>;">
                                                            <?= $e($option['label'] ?? 'Odgovor') ?>
                                                        </span>
                                                        <?php
                                                        $option_hint = trim((string) ($option['hint'] ?? ''));
                                                        if($option_hint === '') {
                                                            $option_hint = !empty($block['route_on_submit']) ? 'Ovaj odgovor mi pomaže da te bolje usmjerim.' : 'Ovaj odgovor pomaže da dobiješ jasniji sljedeći korak.';
                                                        }
                                                        ?>
                                                        <span class="vip-funnel-public__radio-hint"><?= $e($option_hint) ?></span>
                                                    </span>
                                                </label>
                                            <?php endforeach ?>
                                        </div>
                                    <?php endif ?>

                                    <?php if($block_type === 'countdown'): ?>
                                        <?php
                                        $countdown_style = in_array((string) ($block['countdown_style'] ?? 'cards'), ['cards', 'glass', 'minimal', 'spotlight'], true) ? (string) $block['countdown_style'] : 'cards';
                                        $countdown_number_size = max(16, min(96, (int) ($block['countdown_number_size'] ?? 34)));
                                        $countdown_number_color = verify_hex_color((string) ($block['countdown_number_color'] ?? '')) ? (string) $block['countdown_number_color'] : $accent_color;
                                        $countdown_weekly_day = max(0, min(6, (int) ($block['countdown_weekly_day'] ?? 4)));
                                        $countdown_weekly_time = function_exists('vip_funnel_normalize_countdown_weekly_time') ? vip_funnel_normalize_countdown_weekly_time((string) ($block['countdown_weekly_time'] ?? '20:00')) : '20:00';
                                        $countdown_timezone = function_exists('vip_funnel_normalize_countdown_timezone') ? vip_funnel_normalize_countdown_timezone((string) ($block['countdown_timezone'] ?? 'Europe/Zagreb')) : 'Europe/Zagreb';
                                        $countdown_weekly_target = function_exists('vip_funnel_get_next_weekly_countdown_datetime') ? vip_funnel_get_next_weekly_countdown_datetime($countdown_weekly_day, $countdown_weekly_time, $countdown_timezone) : '';
                                        $countdown_units = [];
                                        if(!isset($block['countdown_show_days']) || !empty($block['countdown_show_days'])) $countdown_units[] = ['days', 'Dana'];
                                        if(!isset($block['countdown_show_hours']) || !empty($block['countdown_show_hours'])) $countdown_units[] = ['hours', 'Sati'];
                                        if(!isset($block['countdown_show_minutes']) || !empty($block['countdown_show_minutes'])) $countdown_units[] = ['minutes', 'Min'];
                                        if(!isset($block['countdown_show_seconds']) || !empty($block['countdown_show_seconds'])) $countdown_units[] = ['seconds', 'Sek'];
                                        if(empty($countdown_units)) $countdown_units[] = ['seconds', 'Sek'];
                                        ?>
                                        <div
                                            class="vip-funnel-public__countdown vip-funnel-public__countdown--<?= $e($countdown_style) ?>"
                                            data-vf-countdown
                                            data-vf-viewer="<?= $e($viewer_key) ?>"
                                            data-vf-page="<?= $e((string) ($state['page_key'] ?? 'landing')) ?>"
                                            data-vf-mode="<?= $e((string) ($block['countdown_mode'] ?? 'fixed')) ?>"
                                            data-vf-fixed="<?= $e((string) ($block['fixed_datetime'] ?? '')) ?>"
                                            data-vf-duration-minutes="<?= $e((int) ($block['duration_minutes'] ?? 0)) ?>"
                                            data-vf-duration-days="<?= $e((int) ($block['duration_days'] ?? 0)) ?>"
                                            data-vf-weekly-target="<?= $e($countdown_weekly_target) ?>"
                                            data-vf-weekly-day="<?= $e($countdown_weekly_day) ?>"
                                            data-vf-weekly-time="<?= $e($countdown_weekly_time) ?>"
                                            data-vf-weekly-timezone="<?= $e($countdown_timezone) ?>"
                                            data-vf-expired="<?= $e((string) ($block['completion_text'] ?? 'Vrijeme je isteklo.')) ?>"
                                            data-vf-number-size="<?= $e($countdown_number_size) ?>"
                                            data-vf-number-color="<?= $e($countdown_number_color) ?>"
                                            style="--vf-countdown-border: <?= $e($hex_alpha($accent_color, '33')) ?>; --vf-countdown-surface: <?= $e($hex_alpha($accent_color, '1A')) ?>; --vf-countdown-glass-a: <?= $e($hex_alpha($accent_color, '29')) ?>; --vf-countdown-glass-b: rgba(255,255,255,0.03); --vf-countdown-glass-item: rgba(11,17,27,0.35);"
                                        >
                                            <div class="vip-funnel-public__countdown-row is-cols-<?= $e(count($countdown_units)) ?>">
                                                <?php foreach($countdown_units as [$unit, $label]): ?>
                                                    <div class="vip-funnel-public__countdown-item">
                                                        <div class="vip-funnel-public__countdown-value" data-vf-countdown-value="<?= $e($unit) ?>" style="font-size: <?= $e($countdown_number_size) ?>px; color: <?= $e($countdown_number_color) ?>;">00</div>
                                                        <div class="vip-funnel-public__countdown-label"><?= $e($label) ?></div>
                                                    </div>
                                                <?php endforeach ?>
                                            </div>
                                        </div>
                                    <?php endif ?>

                                    <?php if($block_type === 'survey' && !empty($block['options'])): ?>
                                        <div class="vip-funnel-public__actions">
                                            <?php foreach($block['options'] as $option): ?>
                                                <?php
                                                $is_submit = !empty($option['is_submit']) || in_array(($option['action'] ?? ''), ['submit_next', 'submit_stay'], true) || !empty($option['require_submit']) || ($page_has_capture_fields && in_array($active_block_mode, ['contact_form', 'video_form'], true));
                                                $option_style = in_array(($option['style'] ?? 'primary'), ['primary', 'secondary', 'ghost'], true) ? $option['style'] : 'primary';
                                                $option_text_color = $button_text_color !== '' ? $button_text_color : ($option_style === 'primary' ? '#0f172a' : $body_color);
                                                ?>
                                                <?php if($is_submit): ?>
                                                    <button
                                                        type="submit"
                                                        class="vip-funnel-public__btn is-<?= $e($option_style) ?>"
                                                        style="font-size: <?= $e($button_size) ?>px; font-weight: <?= $e($button_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($option_text_color) ?>;"
                                                        data-vf-submit
                                                        data-vf-action="<?= $e($option['action'] ?? 'submit_next') ?>"
                                                        data-vf-target="<?= $e($option['target_step_id'] ?? '') ?>"
                                                        data-vf-external="<?= $e($option['external_url'] ?? '') ?>"
                                                        data-vf-selection="<?= $e($option['value'] ?? ($option['label'] ?? '')) ?>"
                                                        data-vf-block="<?= $e($block_id) ?>"
                                                        data-vf-block-type="<?= $e($block_type) ?>"
                                                        data-vf-label="<?= $e($option['label'] ?? 'Opcija') ?>"
                                                        data-vf-event-key="<?= $e($option['event_key'] ?? '') ?>"
                                                        data-is-ajax
                                                    >
                                                        <span class="vip-funnel-public__btn-label"><?= $e($option['label'] ?? 'Opcija') ?></span>
                                                        <?php if(!empty($option['hint'])): ?><span class="vip-funnel-public__btn-hint"><?= $e($option['hint']) ?></span><?php endif ?>
                                                    </button>
                                                <?php else: ?>
                                                    <?php $option_signal_key = \Altum\Link::is_monitored_forever_destination_url((string) ($option['url'] ?? '')) ? 'forever_shop' : ''; ?>
                                                    <a
                                                        href="<?= $e($option['url'] ?? '#') ?>"
                                                        class="vip-funnel-public__btn is-<?= $e($option_style) ?>"
                                                        style="font-size: <?= $e($button_size) ?>px; font-weight: <?= $e($button_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($option_text_color) ?>;"
                                                        data-vf-track="cta_click"
                                                        data-vf-block="<?= $e($block_id) ?>"
                                                        data-vf-block-type="<?= $e($block_type) ?>"
                                                        data-vf-label="<?= $e($option['label'] ?? 'Opcija') ?>"
                                                        data-vf-action="<?= $e($option['action'] ?? '') ?>"
                                                        data-vf-target="<?= $e($option['target_step_id'] ?? '') ?>"
                                                        data-vf-external="<?= $e($option['url'] ?? '') ?>"
                                                        data-vf-selection="<?= $e($option['value'] ?? ($option['label'] ?? '')) ?>"
                                                        data-vf-signal-key="<?= $e($option_signal_key) ?>"
                                                        data-vf-event-key="<?= $e($option['event_key'] ?? '') ?>"
                                                    >
                                                        <span class="vip-funnel-public__btn-label"><?= $e($option['label'] ?? 'Opcija') ?></span>
                                                        <?php if(!empty($option['hint'])): ?><span class="vip-funnel-public__btn-hint"><?= $e($option['hint']) ?></span><?php endif ?>
                                                    </a>
                                                <?php endif ?>
                                            <?php endforeach ?>
                                        </div>
                                    <?php endif ?>

                                    <?php if($block_type === 'cta_group' && !empty($block['buttons'])): ?>
                                        <div class="vip-funnel-public__actions">
                                            <?php foreach($block['buttons'] as $button): ?>
                                                <?php
                                                $is_submit = !empty($button['is_submit']) || in_array(($button['action'] ?? ''), ['submit_next', 'submit_stay'], true) || !empty($button['require_submit']) || ($page_has_capture_fields && in_array($active_block_mode, ['contact_form', 'video_form'], true));
                                                $button_style = in_array(($button['style'] ?? 'primary'), ['primary', 'secondary', 'ghost'], true) ? $button['style'] : 'primary';
                                                $button_label_color = $button_text_color !== '' ? $button_text_color : ($button_style === 'primary' ? '#0f172a' : $body_color);
                                                ?>
                                                <?php if($is_submit): ?>
                                                    <button
                                                        type="submit"
                                                        class="vip-funnel-public__btn is-<?= $e($button_style) ?>"
                                                        style="font-size: <?= $e($button_size) ?>px; font-weight: <?= $e($button_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($button_label_color) ?>;"
                                                        data-vf-submit
                                                        data-vf-action="<?= $e($button['action'] ?? 'submit_next') ?>"
                                                        data-vf-target="<?= $e($button['target_step_id'] ?? '') ?>"
                                                        data-vf-external="<?= $e($button['external_url'] ?? '') ?>"
                                                        data-vf-selection="<?= $e($button['value'] ?? ($button['label'] ?? '')) ?>"
                                                        data-vf-block="<?= $e($block_id) ?>"
                                                        data-vf-block-type="<?= $e($block_type) ?>"
                                                        data-vf-label="<?= $e($button['label'] ?? 'Gumb') ?>"
                                                        data-vf-event-key="<?= $e($button['event_key'] ?? '') ?>"
                                                        data-is-ajax
                                                    >
                                                        <span class="vip-funnel-public__btn-label"><?= $e($button['label'] ?? 'Gumb') ?></span>
                                                        <?php if(!empty($button['hint'])): ?><span class="vip-funnel-public__btn-hint"><?= $e($button['hint']) ?></span><?php endif ?>
                                                    </button>
                                                <?php else: ?>
                                                    <?php $button_signal_key = \Altum\Link::is_monitored_forever_destination_url((string) ($button['url'] ?? '')) ? 'forever_shop' : ''; ?>
                                                    <a
                                                        href="<?= $e($button['url'] ?? '#') ?>"
                                                        class="vip-funnel-public__btn is-<?= $e($button_style) ?>"
                                                        style="font-size: <?= $e($button_size) ?>px; font-weight: <?= $e($button_weight) ?>; font-family: <?= $e($font_family) ?>; color: <?= $e($button_label_color) ?>;"
                                                        data-vf-track="cta_click"
                                                        data-vf-block="<?= $e($block_id) ?>"
                                                        data-vf-block-type="<?= $e($block_type) ?>"
                                                        data-vf-label="<?= $e($button['label'] ?? 'Gumb') ?>"
                                                        data-vf-action="<?= $e($button['action'] ?? '') ?>"
                                                        data-vf-target="<?= $e($button['target_step_id'] ?? '') ?>"
                                                        data-vf-external="<?= $e($button['url'] ?? '') ?>"
                                                        data-vf-selection="<?= $e($button['value'] ?? ($button['label'] ?? '')) ?>"
                                                        data-vf-signal-key="<?= $e($button_signal_key) ?>"
                                                        data-vf-event-key="<?= $e($button['event_key'] ?? '') ?>"
                                                    >
                                                        <span class="vip-funnel-public__btn-label"><?= $e($button['label'] ?? 'Gumb') ?></span>
                                                        <?php if(!empty($button['hint'])): ?><span class="vip-funnel-public__btn-hint"><?= $e($button['hint']) ?></span><?php endif ?>
                                                    </a>
                                                <?php endif ?>
                                            <?php endforeach ?>
                                        </div>
                                    <?php endif ?>
                                </div>
                            <?php endforeach ?>

                            <?php if(($page_has_capture_fields || $page_has_deferred_survey) && !$page_has_submit_action): ?>
                                <div class="vip-funnel-public__block" data-vf-span="full">
                                    <div class="vip-funnel-public__block-text" style="margin-bottom:.75rem;"><?= $e($page_has_deferred_survey && !$page_has_capture_fields ? 'Ovaj upitnik još nema završni submit CTA pa je uključen sigurnosni gumb za nastavak.' : 'Ova forma ili upitnik još nema poseban submit CTA pa je uključen sigurnosni gumb za slanje i nastavak.') ?></div>
                                    <div class="vip-funnel-public__actions">
                                        <button
                                            type="submit"
                                            class="vip-funnel-public__btn is-primary"
                                            data-vf-submit
                                            data-vf-action="submit_next"
                                            data-vf-target=""
                                            data-vf-external=""
                                            data-vf-selection=""
                                            data-vf-block="auto_submit_fallback"
                                            data-vf-block-type="cta_group"
                                            data-vf-label="Pošalji i nastavi"
                                            data-vf-event-key="submit_fallback"
                                            data-is-ajax
                                        >Pošalji i nastavi</button>
                                    </div>
                                </div>
                            <?php endif ?>
                        </div>

                        <?php if($sticky_cta): ?>
                            <div class="vip-funnel-public__sticky-cta" aria-label="Glavna akcija">
                                <div class="vip-funnel-public__sticky-cta-inner">
                                    <?php if(!empty($sticky_cta['is_submit'])): ?>
                                        <button
                                            type="submit"
                                            class="vip-funnel-public__btn is-<?= $e($sticky_cta['style']) ?>"
                                            data-vf-submit
                                            data-vf-action="<?= $e($sticky_cta['action']) ?>"
                                            data-vf-target="<?= $e($sticky_cta['target']) ?>"
                                            data-vf-external="<?= $e($sticky_cta['external']) ?>"
                                            data-vf-selection="<?= $e($sticky_cta['selection']) ?>"
                                            data-vf-block="<?= $e($sticky_cta['block_id']) ?>"
                                            data-vf-block-type="<?= $e($sticky_cta['block_type']) ?>"
                                            data-vf-label="<?= $e($sticky_cta['label']) ?>"
                                            data-vf-event-key="<?= $e($sticky_cta['event_key']) ?>"
                                            data-is-ajax
                                        >
                                            <span class="vip-funnel-public__btn-label"><?= $e($sticky_cta['label']) ?></span>
                                            <?php if(!empty($sticky_cta['hint'])): ?><span class="vip-funnel-public__btn-hint"><?= $e($sticky_cta['hint']) ?></span><?php endif ?>
                                        </button>
                                    <?php else: ?>
                                        <a
                                            href="<?= $e($sticky_cta['url']) ?>"
                                            class="vip-funnel-public__btn is-<?= $e($sticky_cta['style']) ?>"
                                            data-vf-track="cta_click"
                                            data-vf-block="<?= $e($sticky_cta['block_id']) ?>"
                                            data-vf-block-type="<?= $e($sticky_cta['block_type']) ?>"
                                            data-vf-label="<?= $e($sticky_cta['label']) ?>"
                                            data-vf-action="<?= $e($sticky_cta['action']) ?>"
                                            data-vf-target="<?= $e($sticky_cta['target']) ?>"
                                            data-vf-external="<?= $e($sticky_cta['external']) ?>"
                                            data-vf-selection="<?= $e($sticky_cta['selection']) ?>"
                                            data-vf-signal-key="<?= $e($sticky_cta['signal_key']) ?>"
                                            data-vf-event-key="<?= $e($sticky_cta['event_key']) ?>"
                                        >
                                            <span class="vip-funnel-public__btn-label"><?= $e($sticky_cta['label']) ?></span>
                                            <?php if(!empty($sticky_cta['hint'])): ?><span class="vip-funnel-public__btn-hint"><?= $e($sticky_cta['hint']) ?></span><?php endif ?>
                                        </a>
                                    <?php endif ?>
                                </div>
                            </div>
                        <?php endif ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const actionInput = document.getElementById('vf_action');
    const targetInput = document.getElementById('vf_target_step_id');
    const externalInput = document.getElementById('vf_external_url');
    const selectionInput = document.getElementById('vf_selection');
    const blockInput = document.getElementById('vf_block_id');
    const eventKeyInput = document.getElementById('vf_event_key');
    const form = document.getElementById('vip-funnel-public-form');
    const pageHasLeadForm = <?= ($page_has_capture_fields || $page_has_deferred_survey) ? 'true' : 'false' ?>;
    let lastSubmitButton = null;

    const trackMeta = (eventName, params = {}, standardEvent = false) => {
        if(typeof window.vipFunnelTrackMeta !== 'function') return;
        try {
            window.vipFunnelTrackMeta(eventName, params, standardEvent);
        } catch (error) {
            console.warn('VIP Funnel Meta tracking skipped.', error);
        }
    };

    const normalizeLabel = value => String(value || '').replace(/\s+/g, ' ').trim().slice(0, 180);

    const getMetaParamsFromNode = node => ({
        button_label: normalizeLabel(node.getAttribute('data-vf-label') || node.textContent || ''),
        action: node.getAttribute('data-vf-action') || '',
        target_step_id: node.getAttribute('data-vf-target') || '',
        external_url: node.getAttribute('data-vf-external') || node.getAttribute('href') || '',
        selection: node.getAttribute('data-vf-selection') || '',
        block_id: node.getAttribute('data-vf-block') || '',
        block_type: node.getAttribute('data-vf-block-type') || '',
        signal_key: node.getAttribute('data-vf-signal-key') || '',
        event_key: node.getAttribute('data-vf-event-key') || ''
    });

    const trackMetaCtaIntent = node => {
        if(!node) return;

        const params = getMetaParamsFromNode(node);
        const signal = `${params.button_label} ${params.action} ${params.selection} ${params.external_url} ${params.signal_key}`.toLowerCase();

        trackMeta('VIPFunnelCTA', params);
        if(params.event_key) {
            trackMeta(params.event_key, params);
        }

        if(signal.includes('order_start_package') || signal.includes('start your journey') || signal.includes('start paket')) {
            trackMeta('VIPFunnelStartOrderClick', params);
            trackMeta('InitiateCheckout', Object.assign({value: 360, currency: 'EUR'}, params), true);
        }

        if(signal.includes('whatsapp') || signal.includes('wa.me') || signal.includes('api.whatsapp')) {
            trackMeta('VIPFunnelWhatsAppClick', params);
            trackMeta('Contact', params, true);
        }

        if(signal.includes('demo')) {
            trackMeta('VIPFunnelDemoIntent', params);
        }

        if(signal.includes('forever_shop') || signal.includes('foreverliving')) {
            trackMeta('VIPFunnelForeverShopClick', params);
        }

        if(signal.includes('product') || signal.includes('proizvod') || signal.includes('popust')) {
            trackMeta('VIPFunnelProductIntent', params);
        }
    };

    const trackEvent = (node) => {
        if(!node) return;

        const payload = new FormData();
        payload.append('vf_track_event', '1');
        payload.append('vf_event_type', node.getAttribute('data-vf-track') || 'cta_click');
        payload.append('vf_block_id', node.getAttribute('data-vf-block') || '');
        payload.append('vf_block_type', node.getAttribute('data-vf-block-type') || '');
        payload.append('vf_label', node.getAttribute('data-vf-label') || '');
        payload.append('vf_action', node.getAttribute('data-vf-action') || '');
        payload.append('vf_target_step_id', node.getAttribute('data-vf-target') || '');
        payload.append('vf_external_url', node.getAttribute('data-vf-external') || node.getAttribute('href') || '');
        payload.append('vf_selection', node.getAttribute('data-vf-selection') || '');
        payload.append('vf_signal_key', node.getAttribute('data-vf-signal-key') || '');
        payload.append('vf_event_key', node.getAttribute('data-vf-event-key') || '');

        if(navigator.sendBeacon) {
            navigator.sendBeacon(window.location.href, payload);
            return;
        }

        fetch(window.location.href, {
            method: 'POST',
            body: payload,
            credentials: 'same-origin',
            keepalive: true
        }).catch(() => {});
    };

    document.querySelectorAll('[data-vf-submit]').forEach(button => {
        button.addEventListener('click', () => {
            lastSubmitButton = button;
            actionInput.value = button.getAttribute('data-vf-action') || 'submit_next';
            targetInput.value = button.getAttribute('data-vf-target') || '';
            externalInput.value = button.getAttribute('data-vf-external') || '';
            selectionInput.value = button.getAttribute('data-vf-selection') || '';
            blockInput.value = button.getAttribute('data-vf-block') || '';
            if(eventKeyInput) eventKeyInput.value = button.getAttribute('data-vf-event-key') || '';
            try {
                trackMetaCtaIntent(button);
            } catch (error) {
                console.warn('VIP Funnel CTA tracking skipped.', error);
            }
        });
    });

    document.querySelectorAll('[data-vf-track]').forEach(node => {
        node.addEventListener('click', () => {
            trackEvent(node);
            trackMetaCtaIntent(node);
        }, {passive: true});
    });

    if(form) {
        form.addEventListener('submit', () => {
            const params = lastSubmitButton ? getMetaParamsFromNode(lastSubmitButton) : {
                button_label: 'Submit',
                action: actionInput ? actionInput.value : '',
                target_step_id: targetInput ? targetInput.value : '',
                external_url: externalInput ? externalInput.value : '',
                selection: selectionInput ? selectionInput.value : '',
                block_id: blockInput ? blockInput.value : '',
                block_type: '',
                signal_key: '',
                event_key: eventKeyInput ? eventKeyInput.value : ''
            };

            if(form.dataset.vfSubmitting === '1') {
                return;
            }

            form.dataset.vfSubmitting = '1';

            if(lastSubmitButton) {
                lastSubmitButton.classList.add('is-submitting');
                lastSubmitButton.setAttribute('aria-busy', 'true');
            }

            try {
                trackMeta('VIPFunnelSubmit', params);
                if(pageHasLeadForm) {
                    trackMeta('Lead', params, true);
                }
            } catch (error) {
                console.warn('VIP Funnel submit tracking skipped.', error);
            }

            window.setTimeout(() => {
                if(form.dataset.vfSubmitting !== '1') return;

                form.dataset.vfSubmitting = '0';
                if(lastSubmitButton) {
                    lastSubmitButton.classList.remove('is-submitting');
                    lastSubmitButton.removeAttribute('aria-busy');
                }
            }, 15000);
        });
    }

    const countdowns = document.querySelectorAll('[data-vf-countdown]');
    const pad = value => String(value).padStart(2, '0');

    countdowns.forEach(node => {
        const viewerKey = node.getAttribute('data-vf-viewer') || 'guest';
        const pageKey = node.getAttribute('data-vf-page') || 'landing';
        const mode = node.getAttribute('data-vf-mode') || 'fixed';
        const expiredText = node.getAttribute('data-vf-expired') || 'Vrijeme je isteklo.';
        const storageKey = `vf_countdown_${viewerKey}_${pageKey}_${node.dataset.vfMode || mode}`;
        const unitSecondsMap = {days: 86400, hours: 3600, minutes: 60, seconds: 1};
        const unitNodes = [...node.querySelectorAll('[data-vf-countdown-value]')].map(item => ({
            unit: item.getAttribute('data-vf-countdown-value') || 'seconds',
            node: item
        }));
        let targetTs = 0;

        if(mode === 'evergreen') {
            const stored = window.localStorage.getItem(storageKey);
            if(stored) {
                targetTs = parseInt(stored, 10) || 0;
            }

            if(!targetTs) {
                const days = parseInt(node.getAttribute('data-vf-duration-days') || '0', 10) || 0;
                const minutes = parseInt(node.getAttribute('data-vf-duration-minutes') || '0', 10) || 0;
                targetTs = Date.now() + (((days * 24 * 60) + minutes) * 60 * 1000);
                window.localStorage.setItem(storageKey, String(targetTs));
            }
        } else if(mode === 'weekly') {
            const weeklyTarget = node.getAttribute('data-vf-weekly-target') || '';
            targetTs = weeklyTarget ? new Date(weeklyTarget).getTime() : 0;
        } else {
            const fixedValue = node.getAttribute('data-vf-fixed') || '';
            targetTs = fixedValue ? new Date(fixedValue).getTime() : 0;
        }

        const tick = () => {
            if(!targetTs) {
                node.innerHTML = `<div class="vip-funnel-public__countdown-expired"><div class="vip-funnel-public__countdown-expired-kicker">Countdown završen</div><div class="vip-funnel-public__countdown-expired-text">${expiredText}</div></div>`;
                node.classList.add('is-expired');
                return;
            }

            let diff = targetTs - Date.now();

            if(diff <= 0) {
                if(mode === 'weekly') {
                    const weekMs = 7 * 24 * 60 * 60 * 1000;
                    while(targetTs <= Date.now()) {
                        targetTs += weekMs;
                    }
                    diff = targetTs - Date.now();
                } else {
                    node.innerHTML = `<div class="vip-funnel-public__countdown-expired"><div class="vip-funnel-public__countdown-expired-kicker">Countdown završen</div><div class="vip-funnel-public__countdown-expired-text">${expiredText}</div></div>`;
                    node.classList.add('is-expired');
                    return;
                }
            }

            const totalSeconds = Math.floor(diff / 1000);
            let remainingSeconds = totalSeconds;
            unitNodes.forEach((entry, index) => {
                const unitSeconds = unitSecondsMap[entry.unit] || 1;
                const isLast = index === unitNodes.length - 1;
                const value = isLast ? Math.floor(remainingSeconds / unitSeconds) : Math.floor(remainingSeconds / unitSeconds);
                remainingSeconds = Math.max(0, remainingSeconds - (value * unitSeconds));
                entry.node.textContent = pad(value);
            });
        };

        tick();
        window.setInterval(tick, 1000);
    });
})();
</script>
