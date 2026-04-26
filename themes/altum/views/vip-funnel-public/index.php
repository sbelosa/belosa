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

$state = is_array($data->state ?? null) ? $data->state : [];
$payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];
$surface = is_array($state['page_surface'] ?? null) ? $state['page_surface'] : [];
$blocks = is_array($state['blocks'] ?? null) ? $state['blocks'] : [];
$active = is_array($state['active'] ?? null) ? $state['active'] : [];
$hide_public_navbar = !empty($payload['defaults']['hide_public_navbar']);
$background_color = $surface['background_color'] ?? '#0f172a';
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
    if(in_array((string) ($preview_block['type'] ?? ''), ['name_field', 'full_name_field', 'email_field', 'phone_field'], true)) {
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
        --vf-bg: <?= $e($background_color) ?>;
        --vf-surface: <?= $e($surface_color) ?>;
        --vf-text: <?= $e($text_color) ?>;
        --vf-accent: <?= $e($accent_color) ?>;
        padding: 3rem 0 4rem;
        min-height: 100vh;
        background:
            radial-gradient(720px 320px at 0% 0%, rgba(103,216,201,0.14), transparent 60%),
            radial-gradient(620px 280px at 100% 0%, rgba(244,195,77,0.13), transparent 58%),
            linear-gradient(180deg, #0a111b, #09111b 55%, #0b1320);
    }

    .vip-funnel-public__wrap {
        max-width: 980px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    .vip-funnel-public__grid {
        display: block;
    }

    .vip-funnel-public__page-shell {
        background: var(--vf-bg);
        border-radius: 1.6rem;
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 1.4rem 3rem rgba(2,8,23,0.22);
        padding: 1rem;
        color: var(--vf-text);
    }

    .vip-funnel-public__canvas {
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
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: .95rem;
    }

    .vip-funnel-public__block {
        min-width: 0;
        border-radius: 1.15rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.05);
        padding: 1rem;
        color: inherit;
    }

    .vip-funnel-public__blocks > [data-vf-span="full"] { grid-column: span 12; }
    .vip-funnel-public__blocks > [data-vf-span="half"] { grid-column: span 6; }
    .vip-funnel-public__blocks > [data-vf-span="third"] { grid-column: span 4; }
    .vip-funnel-public__blocks > [data-vf-span="two_thirds"] { grid-column: span 8; }
    .vip-funnel-public__blocks > [data-vf-span="quarter"] { grid-column: span 3; }
    .vip-funnel-public__blocks > [data-vf-span="three_quarters"] { grid-column: span 9; }

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

    .vip-funnel-public__actions {
        display: grid;
        gap: .68rem;
        margin-top: .9rem;
    }

    .vip-funnel-public__btn,
    .vip-funnel-public__btn:visited {
        display: block;
        width: 100%;
        padding: .96rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.12);
        font-weight: 900;
        text-align: center;
        text-decoration: none;
        transition: transform .18s ease, opacity .18s ease;
    }

    .vip-funnel-public__btn:hover {
        transform: translateY(-1px);
        text-decoration: none;
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

    @media (max-width: 720px) {
        .vip-funnel-public__blocks {
            grid-template-columns: minmax(0, 1fr);
        }

        .vip-funnel-public__blocks > [data-vf-span] {
            grid-column: span 1;
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
    }

</style>

<div class="vip-funnel-public">
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
                        <input type="hidden" name="viewer_key" value="<?= $e($viewer_key) ?>">

                        <?php if(!empty($surface['show_progress']) && !empty($state['total_steps'])): ?>
                            <?php $progress_percent = max(0, min(100, (int) round(((int) ($state['current_step_number'] ?? 0) / max(1, (int) ($state['total_steps'] ?? 1))) * 100))); ?>
                            <div class="vip-funnel-public__progress">
                                <span><?= $e(($state['page_role'] ?? 'landing') === 'landing' ? 'Prvi dojam' : ('Korak ' . (int) ($state['current_step_number'] ?? 1) . ' / ' . max(1, (int) ($state['total_steps'] ?? 1)))) ?></span>
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
                                if(!empty($block['background_color'])) $block_style[] = 'background:' . $block['background_color'];
                                $block_style[] = 'color:' . $base_text_color;
                                if(!empty($block['accent_color'])) $block_style[] = '--vf-accent:' . $block['accent_color'];
                                $block_style[] = 'font-family:' . $font_family;
                                $block_style[] = '--vf-placeholder-color:' . $placeholder_color;
                                ?>

                                <?php if($block_type === 'spacer'): ?>
                                    <?php
                                    $spacing_map = ['xs' => '24px', 'sm' => '40px', 'md' => '60px', 'lg' => '92px', 'xl' => '128px'];
                                    $spacing_height = $spacing_map[$block['spacing'] ?? 'md'] ?? '60px';
                                    ?>
                                    <div class="vip-funnel-public__spacer" data-vf-span="<?= $e($layout_width) ?>" style="height: <?= $e($spacing_height) ?>"></div>
                                    <?php continue; ?>
                                <?php endif ?>

                                <div class="vip-funnel-public__block align-<?= $e($alignment) ?>" data-vf-span="<?= $e($layout_width) ?>" style="<?= $e(implode(';', $block_style)) ?>">
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

                                    <?php if(in_array($block_type, ['name_field', 'full_name_field', 'email_field', 'phone_field'], true)): ?>
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
                                                        <span class="vip-funnel-public__radio-hint">
                                                            <?= $e(!empty($block['route_on_submit']) ? 'Odgovor na ovo pitanje može odrediti konačni sljedeći korak nakon submita.' : 'Odgovor na ovo pitanje sprema se kao dio upitnika.') ?>
                                                        </span>
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
                                                    ><?= $e($option['label'] ?? 'Opcija') ?></button>
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
                                                    ><?= $e($option['label'] ?? 'Opcija') ?></a>
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
                                                    ><?= $e($button['label'] ?? 'Gumb') ?></button>
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
                                                    ><?= $e($button['label'] ?? 'Gumb') ?></a>
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
                                        >Pošalji i nastavi</button>
                                    </div>
                                </div>
                            <?php endif ?>
                        </div>
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
            actionInput.value = button.getAttribute('data-vf-action') || 'submit_next';
            targetInput.value = button.getAttribute('data-vf-target') || '';
            externalInput.value = button.getAttribute('data-vf-external') || '';
            selectionInput.value = button.getAttribute('data-vf-selection') || '';
            blockInput.value = button.getAttribute('data-vf-block') || '';
        });
    });

    document.querySelectorAll('[data-vf-track]').forEach(node => {
        node.addEventListener('click', () => {
            trackEvent(node);
        }, {passive: true});
    });

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

            const diff = targetTs - Date.now();

            if(diff <= 0) {
                node.innerHTML = `<div class="vip-funnel-public__countdown-expired"><div class="vip-funnel-public__countdown-expired-kicker">Countdown završen</div><div class="vip-funnel-public__countdown-expired-text">${expiredText}</div></div>`;
                node.classList.add('is-expired');
                return;
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
