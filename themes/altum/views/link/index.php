<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_is_hr = \Altum\Language::$code === 'hr';
$fcc_is_short_link_editor = $data->link->type === 'link' && $data->method === 'settings';
$fcc_is_biolink_editor = $data->link->type === 'biolink' && $data->method === 'settings';
$fcc_link_header_subheader = $data->link->type === 'link'
    ? l('link.header.short_link_subheader')
    : l('link.header.subheader');
$fcc_ai_editor_payload = is_array($data->ai_editor_payload ?? null) ? $data->ai_editor_payload : [];
$fcc_ai_theme_pack = is_array($fcc_ai_editor_payload['theme_pack'] ?? null) ? $fcc_ai_editor_payload['theme_pack'] : [];
$fcc_ai_theme_bundle_ready = (bool) array_filter([
    (string) ($fcc_ai_theme_pack['background_color'] ?? ''),
    (string) ($fcc_ai_theme_pack['gradient_start'] ?? ''),
    (string) ($fcc_ai_theme_pack['gradient_end'] ?? ''),
    (string) ($fcc_ai_theme_pack['heading_color'] ?? ''),
    (string) ($fcc_ai_theme_pack['text_color'] ?? ''),
    (string) ($fcc_ai_theme_pack['primary_block_background'] ?? ''),
    (string) ($fcc_ai_theme_pack['secondary_blocks_background'] ?? ''),
]);
$fcc_ai_block_bundle_ready = !empty($fcc_ai_editor_payload['missing_block_recommendations']) || !empty($fcc_ai_editor_payload['copy_suggestions']) || !empty($fcc_ai_editor_payload['layout_actions']);
$fcc_ai_bundle_restore_ready = !empty($fcc_ai_editor_payload['bundle_backup']['available']);
$fcc_ai_review_summary_ready = !empty($fcc_ai_editor_payload['review_summary']);
$fcc_ai_actions_freshness = is_array($fcc_ai_editor_payload['freshness'] ?? null) ? $fcc_ai_editor_payload['freshness'] : [];
$fcc_ai_actions_stale = !empty($fcc_ai_actions_freshness['is_stale']);
$fcc_ai_review_teaser_actions_visible = $fcc_ai_review_summary_ready || $fcc_ai_theme_bundle_ready || $fcc_ai_block_bundle_ready || $fcc_ai_bundle_restore_ready;
$fcc_ai_review_teaser_notification_id = 'fcc_app_review_ai_actions_notification';
$fcc_app_review_next_step_payload = is_array($data->app_review_next_step_payload ?? null) ? $data->app_review_next_step_payload : [];
$fcc_app_review_next_step_number = max(0, (int) ($fcc_app_review_next_step_payload['step_number'] ?? 0));
$fcc_app_review_next_step_title = (string) ($fcc_app_review_next_step_payload['title'] ?? l('ai_plan.onboarding_step_2_title'));
$fcc_app_review_next_step_text = (string) ($fcc_app_review_next_step_payload['text'] ?? l('links.app_review_cta_text'));
$fcc_app_review_next_step_button_label = (string) ($fcc_app_review_next_step_payload['button_label'] ?? l('ai_plan.cta_go_app_review_direct'));
$fcc_app_review_next_step_url = (string) ($fcc_app_review_next_step_payload['url'] ?? ($data->app_review_page_url ?? '#'));
$fcc_app_review_next_step_is_accessible = array_key_exists('is_accessible', $fcc_app_review_next_step_payload) ? (bool) $fcc_app_review_next_step_payload['is_accessible'] : (bool) ($data->app_review_is_accessible ?? false);
$fcc_app_review_next_step_locked_reason = (string) ($fcc_app_review_next_step_payload['locked_reason'] ?? ($data->app_review_locked_reason ?? ''));
$fcc_short_link_editor_steps = $fcc_is_short_link_editor ? [
    ['selector' => '#fcc_short_link_editor_step_intro', 'title' => l('link.short_editor.tour.intro_title'), 'text' => l('link.short_editor.tour.intro_text')],
    ['selector' => '#fcc_short_link_editor_step_basics', 'title' => l('link.short_editor.tour.basics_title'), 'text' => l('link.short_editor.tour.basics_text')],
    ['selector' => '#fcc_short_link_editor_step_app_linking', 'title' => l('link.short_editor.tour.app_linking_title'), 'text' => l('link.short_editor.tour.app_linking_text')],
    ['selector' => '#fcc_short_link_editor_step_temporary', 'title' => l('link.short_editor.tour.temporary_title'), 'text' => l('link.short_editor.tour.temporary_text')],
    ['selector' => '#fcc_short_link_editor_step_utm', 'title' => l('link.short_editor.tour.utm_title'), 'text' => l('link.short_editor.tour.utm_text')],
    ['selector' => '#fcc_short_link_editor_step_protection', 'title' => l('link.short_editor.tour.protection_title'), 'text' => l('link.short_editor.tour.protection_text')],
    ['selector' => '#fcc_short_link_editor_step_targeting', 'title' => l('link.short_editor.tour.targeting_title'), 'text' => l('link.short_editor.tour.targeting_text')],
    ['selector' => '#fcc_short_link_editor_step_cloaking', 'title' => l('link.short_editor.tour.cloaking_title'), 'text' => l('link.short_editor.tour.cloaking_text')],
    ['selector' => '#fcc_short_link_editor_step_http', 'title' => l('link.short_editor.tour.http_title'), 'text' => l('link.short_editor.tour.http_text')],
    ['selector' => '#fcc_short_link_editor_step_advanced', 'title' => l('link.short_editor.tour.advanced_title'), 'text' => l('link.short_editor.tour.advanced_text')],
    ['selector' => '#fcc_short_link_editor_step_save', 'title' => l('link.short_editor.tour.save_title'), 'text' => l('link.short_editor.tour.save_text')],
] : [];
?>

<?php ob_start() ?>
<style>
    .fcc-app-review-teaser-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(119, 181, 255, .18) !important;
        border-radius: 24px;
        background:
            radial-gradient(circle at 12% 14%, rgba(63, 215, 199, .14) 0%, rgba(63, 215, 199, 0) 34%),
            radial-gradient(circle at 88% 10%, rgba(84, 124, 255, .14) 0%, rgba(84, 124, 255, 0) 32%),
            radial-gradient(circle at 72% 0%, rgba(226, 188, 116, .08) 0%, rgba(226, 188, 116, 0) 24%),
            linear-gradient(135deg, rgba(17, 25, 41, .985) 0%, rgba(9, 13, 23, .995) 100%);
        box-shadow: 0 28px 64px rgba(3, 9, 23, .34), inset 0 1px 0 rgba(255,255,255,.04);
    }

    .fcc-app-review-teaser-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto;
        height: 3px;
        background: linear-gradient(90deg, rgba(89, 239, 224, .95) 0%, rgba(103, 160, 255, .76) 52%, rgba(228, 188, 118, .9) 100%);
        opacity: .96;
    }

    .fcc-app-review-teaser-card::after {
        content: '';
        position: absolute;
        inset: auto auto -4.5rem -3rem;
        width: 15rem;
        height: 15rem;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(63, 215, 199, .11) 0%, rgba(63, 215, 199, 0) 72%);
        pointer-events: none;
    }

    .fcc-app-review-teaser-body {
        position: relative;
        z-index: 1;
        padding: 1.55rem;
    }

    .fcc-app-review-teaser-grid {
        align-items: stretch;
    }

    .fcc-app-review-teaser-copy {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .fcc-app-review-teaser-pill {
        display: inline-flex;
        align-items: center;
        padding: .74rem 1rem;
        margin-bottom: .95rem;
        border-radius: 999px;
        border: 1px solid rgba(111, 244, 228, .22);
        background: linear-gradient(135deg, rgba(44, 205, 191, .16) 0%, rgba(27, 125, 165, .16) 100%);
        color: #b7fff3;
        font-size: .8rem;
        font-weight: 800;
        letter-spacing: .03em;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.05);
    }

    .fcc-app-review-teaser-title {
        margin-bottom: .65rem;
        color: #f5fbfb;
    }

    .fcc-app-review-teaser-summary {
        max-width: 54rem;
        margin-bottom: 1rem;
        color: rgba(224, 232, 243, .72) !important;
        font-size: 1.02rem;
        line-height: 1.55;
    }

    .fcc-app-review-teaser-metrics {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
    }

    .fcc-app-review-teaser-metric {
        display: inline-flex;
        align-items: center;
        min-height: 2.55rem;
        padding: .58rem .94rem;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,.08);
        background: rgba(255,255,255,.045);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
        color: #d5e1e2;
        font-weight: 700;
    }

    .fcc-app-review-teaser-metric.is-quality {
        border-color: rgba(63, 215, 199, .34);
        background: linear-gradient(135deg, rgba(54, 205, 190, .2) 0%, rgba(20, 102, 120, .18) 100%);
        color: #b8fff3;
    }

    .fcc-app-review-teaser-metric.is-level {
        border-color: rgba(121, 150, 255, .22);
        background: linear-gradient(135deg, rgba(79, 116, 255, .16) 0%, rgba(30, 61, 146, .12) 100%);
        color: #e1e8ff;
    }

    .fcc-app-review-teaser-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .72rem;
        max-width: 38rem;
        margin-top: 1rem;
    }

    .fcc-app-review-teaser-action {
        width: 100%;
        min-height: 3rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .58rem;
        padding: .78rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, .16);
        background: linear-gradient(145deg, rgba(18, 26, 44, .92), rgba(10, 16, 28, .88));
        box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
        color: #edf6ff;
        font-size: .95rem;
        font-weight: 800;
        letter-spacing: -.01em;
        line-height: 1.15;
        transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease, opacity .18s ease;
    }

    .fcc-app-review-teaser-action:hover:not(:disabled),
    .fcc-app-review-teaser-action:focus-visible:not(:disabled) {
        transform: translateY(-1px);
        border-color: rgba(127, 227, 217, .28);
        background: linear-gradient(145deg, rgba(23, 34, 56, .94), rgba(11, 18, 32, .9));
        box-shadow: 0 .95rem 1.9rem rgba(2, 6, 23, .18), inset 0 1px 0 rgba(255,255,255,.04);
        color: #ffffff;
        outline: none;
        text-decoration: none;
    }

    .fcc-app-review-teaser-action.is-primary {
        grid-column: 1 / -1;
        border-color: rgba(63, 215, 199, .22);
        background: linear-gradient(145deg, rgba(49, 210, 197, .18), rgba(11, 118, 132, .14));
        color: #d7fffa;
    }

    .fcc-app-review-teaser-action.is-primary:hover:not(:disabled),
    .fcc-app-review-teaser-action.is-primary:focus-visible:not(:disabled) {
        border-color: rgba(63, 215, 199, .34);
        background: linear-gradient(145deg, rgba(49, 210, 197, .24), rgba(11, 118, 132, .18));
    }

    .fcc-app-review-teaser-action.is-muted {
        color: rgba(226, 232, 240, .88);
    }

    .fcc-app-review-teaser-action:disabled {
        cursor: not-allowed;
        opacity: .5;
        filter: saturate(.86);
        transform: none !important;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.02);
    }

    .fcc-app-review-teaser-notification {
        margin-top: .78rem;
    }

    .fcc-app-review-teaser-notification .alert {
        border-radius: 1rem;
    }

    .fcc-app-review-teaser-step-card {
        height: 100%;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        border-radius: 22px;
        padding: 1.35rem;
        border: 1px solid rgba(126, 155, 255, .18);
        background:
            radial-gradient(circle at top right, rgba(87, 120, 255, .16) 0%, rgba(87, 120, 255, 0) 42%),
            radial-gradient(circle at 15% 0%, rgba(226, 188, 116, .08) 0%, rgba(226, 188, 116, 0) 28%),
            linear-gradient(180deg, rgba(23, 30, 56, .88) 0%, rgba(16, 20, 39, .96) 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.03), 0 20px 34px rgba(4, 11, 25, .18);
    }

    .fcc-app-review-teaser-step-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto;
        height: 2px;
        background: linear-gradient(90deg, rgba(126, 155, 255, .78) 0%, rgba(92, 235, 220, .82) 100%);
        opacity: .9;
    }

    .fcc-app-review-teaser-step-label {
        margin-bottom: .4rem;
        color: rgba(212, 220, 236, .62) !important;
        letter-spacing: .03em;
    }

    .fcc-app-review-teaser-step-number {
        margin-bottom: .35rem;
        color: #f3f7ff;
        font-size: 1.95rem;
        font-weight: 850;
        line-height: 1;
    }

    .fcc-app-review-teaser-step-title {
        margin-bottom: .75rem;
        color: #f3f7ff;
        font-weight: 800;
    }

    .fcc-app-review-teaser-step-text {
        margin-bottom: 1rem;
        color: rgba(220, 228, 241, .72) !important;
    }

    .fcc-app-review-teaser-step-card .btn-primary {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 3.2rem;
        border-radius: 18px;
        font-weight: 800;
        box-shadow: 0 16px 28px rgba(44, 202, 188, .18);
    }

    @media (max-width: 767.98px) {
        .fcc-app-review-teaser-actions {
            grid-template-columns: 1fr;
            max-width: none;
        }

        .fcc-app-review-teaser-action.is-primary {
            grid-column: auto;
        }

        .fcc-app-review-teaser-body {
            padding: 1.2rem;
        }

        .fcc-app-review-teaser-summary {
            font-size: .96rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<input type="hidden" name="link_base" value="<?= $this->link->domain ? $this->link->domain->url : SITE_URL ?>" />

<?php if($fcc_is_short_link_editor || $fcc_is_biolink_editor): ?>
<?php ob_start() ?>
<style>
    .fcc-short-link-page-shell {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        position: relative;
    }

    .fcc-short-link-page-shell::before {
        content: '';
        position: absolute;
        inset: 4.8rem 0 auto;
        height: 34rem;
        border-radius: 32px;
        background:
            radial-gradient(circle at 14% 16%, rgba(70, 220, 214, 0.06) 0%, rgba(70, 220, 214, 0) 40%),
            radial-gradient(circle at 82% 10%, rgba(61, 118, 255, 0.06) 0%, rgba(61, 118, 255, 0) 34%),
            radial-gradient(circle at 46% 0%, rgba(34, 185, 129, 0.03) 0%, rgba(34, 185, 129, 0) 26%),
            linear-gradient(180deg, rgba(12, 19, 33, 0.60) 0%, rgba(16, 16, 18, 0) 100%);
        pointer-events: none;
        z-index: 0;
    }

    .fcc-short-link-page-shell > * {
        position: relative;
        z-index: 1;
    }

    .fcc-short-link-page-rail {
        display: flex;
        justify-content: flex-end;
    }

    .fcc-short-link-page-guide {
        display: inline-flex;
        align-items: center;
        gap: .48rem;
        justify-content: center;
        padding: .68rem .98rem;
        min-height: 2.7rem;
        border-radius: .95rem;
        border: 1px solid rgba(111, 244, 228, .28);
        background: linear-gradient(135deg, rgba(42, 215, 199, .14) 0%, rgba(29, 122, 209, .12) 100%);
        color: #eefdfb;
        font-size: .86rem;
        font-weight: 750;
        text-decoration: none !important;
        box-shadow: 0 12px 24px rgba(4, 14, 25, .14), inset 0 1px 0 rgba(255,255,255,.06);
        transition: all .18s ease;
    }

    .fcc-short-link-page-guide:hover,
    .fcc-short-link-page-guide:focus {
        color: #ffffff;
        border-color: rgba(111, 244, 228, .42);
        background: linear-gradient(135deg, rgba(44, 214, 199, .2) 0%, rgba(41, 126, 212, .18) 100%);
        box-shadow: 0 16px 30px rgba(63, 215, 199, .12);
        transform: translateY(-1px);
        outline: none;
    }

    .fcc-short-link-page-guide i {
        color: #8cf6e9;
        font-size: .92em;
    }

    .fcc-short-link-page-hero {
        background:
            radial-gradient(circle at top left, rgba(72, 220, 214, 0.04) 0%, rgba(72, 220, 214, 0) 28%),
            radial-gradient(circle at 88% 14%, rgba(61, 118, 255, 0.05) 0%, rgba(61, 118, 255, 0) 24%),
            linear-gradient(180deg, rgba(14, 22, 40, 0.98) 0%, rgba(8, 13, 24, 0.99) 100%);
        border: 1px solid rgba(90, 201, 230, 0.08);
        border-radius: 24px;
        padding: 1.3rem 1.35rem;
        box-shadow: 0 28px 56px rgba(4, 10, 24, 0.30);
    }

    .fcc-short-link-page-hero-copy h2 {
        margin: 0 0 0.75rem;
        color: #f7fbff;
        font-size: clamp(1.8rem, 3vw, 2.85rem);
        line-height: 1.02;
        letter-spacing: -0.055em;
        font-weight: 900;
        max-width: 14ch;
    }

    .fcc-short-link-page-hero-copy p {
        margin: 0;
        max-width: 72ch;
        color: #c8d8ea;
        line-height: 1.72;
        font-size: 1.04rem;
    }

    .fcc-short-link-page-hero-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
        margin-top: 1.2rem;
    }

    .fcc-short-link-page-hero-card {
        padding: 1rem 1.05rem;
        border-radius: 18px;
        border: 1px solid rgba(84, 166, 255, 0.08);
        background:
            radial-gradient(circle at top left, rgba(40, 194, 146, 0.05) 0%, rgba(40, 194, 146, 0) 28%),
            linear-gradient(180deg, rgba(16, 23, 39, 0.95) 0%, rgba(10, 14, 24, 0.98) 100%);
    }

    .fcc-short-link-page-hero-card strong {
        display: block;
        margin-bottom: 0.35rem;
        color: #f1f8ff;
        font-size: 0.96rem;
        font-weight: 700;
    }

    .fcc-short-link-page-hero-card span {
        color: #adc2d9;
        font-size: 0.9rem;
        line-height: 1.62;
    }

    .fcc-short-link-tour-target {
        scroll-margin-top: 6rem;
    }

    .fcc-short-link-tour-ancestor {
        position: relative !important;
        z-index: 2051 !important;
        overflow: visible !important;
    }

    .fcc-short-link-tour-active {
        position: relative !important;
        z-index: 2052 !important;
        isolation: isolate;
        transform: translateZ(0);
        filter: brightness(1.06) saturate(1.04);
        box-shadow: 0 0 0 2px rgba(73, 227, 207, .98), 0 0 0 10px rgba(112, 244, 228, .18), 0 18px 54px rgba(7, 19, 38, .34) !important;
        border-radius: 1.35rem !important;
    }

    .fcc-short-link-tour-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2050;
        display: none;
        pointer-events: none;
    }

    .fcc-short-link-tour-backdrop.is-visible {
        display: block;
    }

    .fcc-short-link-tour-backdrop-segment {
        position: fixed;
        background: rgba(2, 8, 23, .58);
        backdrop-filter: blur(3px);
        pointer-events: none;
    }

    .fcc-short-link-tour-popover {
        position: fixed;
        z-index: 2055;
        width: min(25rem, calc(100vw - 2rem));
        display: none;
        border-radius: 1.2rem;
        border: 1px solid rgba(147, 197, 253, .22);
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .18), transparent 30%),
            linear-gradient(180deg, rgba(25, 36, 58, .98), rgba(16, 24, 41, .97));
        box-shadow: 0 30px 80px rgba(2, 8, 23, .44), inset 0 1px 0 rgba(255,255,255,.05);
        padding: 1.05rem 1.05rem 1rem;
    }

    .fcc-short-link-tour-popover.is-visible {
        display: block;
    }

    .fcc-short-link-tour-progress {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .35rem .65rem;
        border-radius: 999px;
        background: rgba(73, 227, 207, .18);
        color: #e8fffb;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-bottom: .75rem;
        border: 1px solid rgba(73, 227, 207, .16);
    }

    .fcc-short-link-tour-title {
        color: #f8fbff;
        font-size: 1.12rem;
        font-weight: 800;
        line-height: 1.3;
        margin-bottom: .45rem;
    }

    .fcc-short-link-tour-text {
        color: rgba(236, 244, 255, .94);
        font-size: .94rem;
        line-height: 1.65;
        margin-bottom: 1rem;
    }

    .fcc-short-link-tour-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .fcc-short-link-tour-actions-main {
        display: flex;
        gap: .65rem;
        flex-wrap: wrap;
    }

    .fcc-short-link-tour-actions .btn {
        border-radius: .85rem;
    }

    .fcc-short-link-tour-actions .btn-link {
        color: rgba(226, 232, 240, .82) !important;
        text-decoration: none;
    }

    .fcc-short-link-tour-actions .btn-outline-light {
        color: #ecf8ff !important;
        border-color: rgba(147, 197, 253, .28) !important;
        background: rgba(59, 130, 246, .12) !important;
    }

    .fcc-short-link-tour-actions .btn-outline-light:hover,
    .fcc-short-link-tour-actions .btn-outline-light:focus {
        color: #ffffff !important;
        border-color: rgba(147, 197, 253, .48) !important;
        background: rgba(59, 130, 246, .2) !important;
    }

    @media (max-width: 1199px) {
        .fcc-short-link-page-hero-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .fcc-short-link-page-rail {
            justify-content: stretch;
        }

        .fcc-short-link-page-guide {
            width: 100%;
        }

        .fcc-short-link-page-hero-grid {
            grid-template-columns: 1fr;
        }

        .fcc-short-link-tour-popover {
            left: 1rem !important;
            right: 1rem !important;
            width: auto;
            top: auto !important;
            bottom: 1rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
<?php endif ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if($fcc_is_short_link_editor): ?>
    <div class="fcc-short-link-page-shell">
        <div class="fcc-short-link-page-rail">
            <button type="button" class="fcc-short-link-page-guide" data-fcc-start-short-link-editor-tour>
                <i class="fas fa-fw fa-route"></i>
                <span><?= l('dashboard.tour.launch') ?></span>
            </button>
        </div>
    <?php elseif($fcc_is_biolink_editor): ?>
        <div class="fcc-short-link-page-rail">
            <button
                type="button"
                id="fcc_biolink_page_guide"
                class="fcc-short-link-page-guide fcc-biolink-tour-target"
                data-fcc-start-biolink-tour="main"
            >
                <i class="fas fa-fw fa-route"></i>
                <span><?= l('dashboard.tour.launch') ?></span>
            </button>
        </div>
    <?php endif ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url('links') ?>"><?= l('links.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page">
                    <?= l('link.breadcrumb.' . $data->link->type) . ' ' . l('link.' . $data->method . '.breadcrumb') ?>
                </li>
            </ol>
        </nav>
    <?php endif ?>

    <?php if($fcc_is_short_link_editor): ?>
        <div class="fcc-short-link-page-hero fcc-short-link-tour-target" id="fcc_short_link_editor_step_intro">
            <div class="fcc-short-link-page-hero-copy">
                <h2><?= l('link.short_editor.hero_title') ?></h2>
                <p><?= l('link.short_editor.hero_text') ?></p>
            </div>
            <div class="fcc-short-link-page-hero-grid">
                <div class="fcc-short-link-page-hero-card">
                    <strong><?= l('link.short_editor.hero_card_1_title') ?></strong>
                    <span><?= l('link.short_editor.hero_card_1_text') ?></span>
                </div>
                <div class="fcc-short-link-page-hero-card">
                    <strong><?= l('link.short_editor.hero_card_2_title') ?></strong>
                    <span><?= l('link.short_editor.hero_card_2_text') ?></span>
                </div>
                <div class="fcc-short-link-page-hero-card">
                    <strong><?= l('link.short_editor.hero_card_3_title') ?></strong>
                    <span><?= l('link.short_editor.hero_card_3_text') ?></span>
                </div>
                <div class="fcc-short-link-page-hero-card">
                    <strong><?= l('link.short_editor.hero_card_4_title') ?></strong>
                    <span><?= l('link.short_editor.hero_card_4_text') ?></span>
                </div>
            </div>
        </div>
    <?php endif ?>

    <div class="row">
        <div class="col text-truncate">
            <h1 id="link_url" class="h3 text-truncate"><?= sprintf(l('link.header.header'), $data->link->url) ?></h1>
        </div>

        <div class="col-auto">
            <div class="d-flex align-items-center">
                <div class="custom-control custom-switch" data-toggle="tooltip" title="<?= l('links.is_enabled_tooltip') ?>">
                    <input
                            type="checkbox"
                            class="custom-control-input"
                            id="link_is_enabled_<?= $data->link->link_id ?>"
                            data-row-id="<?= $data->link->link_id ?>"
                            onchange="ajax_call_helper(event, 'link-ajax', 'is_enabled_toggle')"
                        <?= $data->link->is_enabled ? 'checked="checked"' : null ?>
                    >
                    <label class="custom-control-label" for="link_is_enabled_<?= $data->link->link_id ?>"></label>
                </div>

                <button
                        id="link_full_url_copy"
                        type="button"
                        class="btn btn-link text-secondary <?= $fcc_is_short_link_editor ? 'fcc-short-link-tour-target' : null ?> <?= $fcc_is_biolink_editor ? 'fcc-biolink-tour-target' : null ?>"
                        <?= $fcc_is_short_link_editor ? 'data-short-link-copy-target="1"' : null ?>
                        data-toggle="tooltip"
                        title="<?= l('global.clipboard_copy') ?>"
                        aria-label="<?= l('global.clipboard_copy') ?>"
                        data-copy="<?= l('global.clipboard_copy') ?>"
                        data-copied="<?= l('global.clipboard_copied') ?>"
                        data-clipboard-text="<?= $data->link->full_url ?>"
                >
                    <i class="fas fa-fw fa-sm fa-copy"></i>
                </button>

                <?php if($data->method != 'statistics'): ?>
                    <a
                        href="<?= url('link/' . $data->link->link_id . '/statistics') ?>"
                        class="btn btn-link text-secondary <?= $fcc_is_short_link_editor ? 'fcc-short-link-tour-target' : null ?> <?= $fcc_is_biolink_editor ? 'fcc-biolink-tour-target' : null ?>"
                        <?= $fcc_is_short_link_editor ? 'id="fcc_short_link_editor_step_stats"' : ($fcc_is_biolink_editor ? 'id="fcc_biolink_editor_step_statistics"' : null) ?>
                        data-toggle="tooltip"
                        title="<?= l('link.statistics.link') ?>"
                    ><i class="fas fa-fw fa-sm fa-chart-bar"></i></a>
                <?php endif ?>

                <?php if($data->method != 'settings'): ?>
                    <a href="<?= url('link/' . $data->link->link_id . '/settings') ?>" class="btn btn-link text-secondary" data-toggle="tooltip" title="<?= l('global.edit') ?>"><i class="fas fa-fw fa-pencil-alt"></i></a>
                <?php endif ?>

                <div class="dropdown">
                    <button type="button" class="btn btn-link text-secondary dropdown-toggle dropdown-toggle-simple" data-toggle="dropdown" data-boundary="viewport">
                        <i class="fas fa-fw fa-ellipsis-v"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="<?= url('link/' . $data->link->link_id) ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-pencil-alt mr-2"></i> <?= l('global.edit') ?></a>
                        <a href="<?= url('link/' . $data->link->link_id . '/statistics') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-chart-bar mr-2"></i> <?= l('link.statistics.link') ?></a>
                        <?php if(settings()->codes->qr_codes_is_enabled): ?>
                            <a href="<?= url('qr-code-create?name=' . $data->link->url . '&project_id=' . $data->link->project_id . '&type=url&url=' . $data->link->full_url . '&link_id=' . $data->link->link_id . '&url_dynamic=1') ?>" class="dropdown-item" rel="noreferrer"><i class="fas fa-fw fa-sm fa-qrcode mr-2"></i> <?= l('qr_codes.create') ?></a>
                        <?php endif ?>

                        <?php if($data->link->type == 'static'): ?>
                            <a href="<?= url('link/' . $data->link->link_id . '/download') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-download mr-2"></i> <?= l('global.download') ?></a>
                        <?php endif ?>

                        <a href="#" data-toggle="modal" data-target="#link_duplicate_modal" class="dropdown-item" data-link-id="<?= $data->link->link_id ?>"><i class="fas fa-fw fa-sm fa-clone mr-2"></i> <?= l('global.duplicate') ?></a>
                        <!-- Custom code -->
                        <?php if (($data->biolink_main && $data->biolink_main->biolink_id == $data->link->link_id) || ($data->vcard_main && $data->vcard_main->vcard_id == $data->link->link_id)): ?>
                        <?php else: ?>                            
                            <a href="#" data-toggle="modal" data-target="#link_reset_modal" class="dropdown-item" data-link-id="<?= $data->link->link_id ?>"><i class="fas fa-fw fa-sm fa-redo mr-2"></i> <?= l('global.reset') ?></a>                        
                            <a href="#" data-toggle="modal" data-target="#link_delete_modal" class="dropdown-item" data-link-id="<?= $data->link->link_id ?>"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>
                        <?php endif; ?>
                        <!-- /Custom code -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-baseline mb-4">
        <span class="mr-1" data-toggle="tooltip" title="<?= l('link.' . $data->link->type . '.name') ?>">
            <i class="fas fa-fw fa-circle fa-sm" style="color: <?= $data->links_types[$data->link->type]['color'] ?>"></i>
        </span>

        <div class="text-muted text-truncate">
            <?= sprintf($fcc_link_header_subheader, '<a id="link_full_url" href="' . $data->link->full_url . '" target="_blank" rel="noreferrer">' . remove_url_protocol_from_url($data->link->full_url) . '</a>') ?>
        </div>
    </div>

    <?php if($data->method === 'statistics' && $data->link->type === 'biolink'): ?>
        <div class="fcc-app-stats-guide-rail">
            <button type="button" class="fcc-app-stats-guide-trigger" id="fcc_app_stats_start_tour">
                <i class="fas fa-fw fa-route"></i>
                <span><?= fc_resolve_language_name(\Altum\Language::$name ?? '') === 'Hrvatski' || mb_strtolower((string) (\Altum\Language::$code ?? '')) === 'hr' ? 'Pokreni tutorijal' : 'Start tutorial' ?></span>
            </button>
        </div>
    <?php endif ?>

    <?php if($data->link->type === 'biolink' && !empty($data->app_review_quality_payload) && !empty($data->is_main_biolink_app)): ?>
        <?php $quality = $data->app_review_quality_payload; ?>
        <div id="fcc_app_stats_tour_step_ai_block" class="card mb-4 border-0 fcc-biolink-tour-target fcc-app-review-teaser-card">
            <div class="card-body fcc-app-review-teaser-body">
                <div class="row fcc-app-review-teaser-grid">
                    <div class="col-12 col-xl-8 mb-4 mb-xl-0 fcc-app-review-teaser-copy">
                        <div class="fcc-app-review-teaser-pill">
                            <?= l('ai_plan.app_review_menu') ?>
                        </div>
                        <h2 class="h4 fcc-app-review-teaser-title"><?= l('links.app_review_teaser_title') ?></h2>
                        <p class="text-muted fcc-app-review-teaser-summary"><?= htmlspecialchars((string) ($quality['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="fcc-app-review-teaser-metrics">
                            <span class="fcc-app-review-teaser-metric is-quality"><?= l('links.app_review_quality_short') ?> <?= nr((int) ($quality['score'] ?? 0)) ?></span>
                            <span class="fcc-app-review-teaser-metric is-level"><?= htmlspecialchars((string) ($quality['level_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="fcc-app-review-teaser-metric"><?= l('links.app_review_metric_shop_short') ?> <?= nr((int) (($quality['performance']['shop_contacts_30d'] ?? 0))) ?></span>
                            <span class="fcc-app-review-teaser-metric"><?= l('links.app_review_metric_whatsapp_short') ?> <?= nr((int) (($quality['performance']['whatsapp_contacts_30d'] ?? 0))) ?></span>
                            <span class="fcc-app-review-teaser-metric"><?= l('links.app_review_metric_products_short') ?> <?= nr((int) (($quality['performance']['product_clicks_30d'] ?? 0))) ?></span>
                            <span class="fcc-app-review-teaser-metric"><?= l('links.app_review_metric_funnel_short') ?> <?= nr((int) (($quality['performance']['funnel_registrations_30d'] ?? 0))) ?></span>
                        </div>
                        <?php if($fcc_ai_review_teaser_actions_visible): ?>
                            <div class="fcc-app-review-teaser-actions">
                                <button
                                    type="button"
                                    class="fcc-app-review-teaser-action is-primary js-link-index-ai-editor-action"
                                    data-request-type="apply_ai_block_bundle"
                                    data-link-id="<?= (int) $data->link->link_id ?>"
                                    data-notification-target="#<?= htmlspecialchars($fcc_ai_review_teaser_notification_id, ENT_QUOTES, 'UTF-8') ?>"
                                    data-ai-stale="<?= $fcc_ai_actions_stale ? '1' : '0' ?>"
                                    data-ai-stale-message="<?= htmlspecialchars((string) ($fcc_ai_actions_freshness['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $fcc_ai_block_bundle_ready ? null : 'disabled="disabled"' ?>
                                >
                                    <i class="fas fa-fw fa-layer-group"></i>
                                    <span><?= htmlspecialchars(l('link.settings.ai_block_bundle_apply'), ENT_QUOTES, 'UTF-8') ?></span>
                                </button>

                                <button
                                    type="button"
                                    class="fcc-app-review-teaser-action js-link-index-ai-editor-action"
                                    data-request-type="apply_ai_color_bundle"
                                    data-link-id="<?= (int) $data->link->link_id ?>"
                                    data-notification-target="#<?= htmlspecialchars($fcc_ai_review_teaser_notification_id, ENT_QUOTES, 'UTF-8') ?>"
                                    data-ai-stale="<?= $fcc_ai_actions_stale ? '1' : '0' ?>"
                                    data-ai-stale-message="<?= htmlspecialchars((string) ($fcc_ai_actions_freshness['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $fcc_ai_theme_bundle_ready ? null : 'disabled="disabled"' ?>
                                >
                                    <i class="fas fa-fw fa-palette"></i>
                                    <span><?= htmlspecialchars(l('link.settings.ai_color_bundle_apply'), ENT_QUOTES, 'UTF-8') ?></span>
                                </button>

                                <button
                                    type="button"
                                    class="fcc-app-review-teaser-action is-muted js-link-index-ai-editor-action"
                                    data-request-type="restore_ai_bundle_backup"
                                    data-link-id="<?= (int) $data->link->link_id ?>"
                                    data-notification-target="#<?= htmlspecialchars($fcc_ai_review_teaser_notification_id, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $fcc_ai_bundle_restore_ready ? null : 'disabled="disabled"' ?>
                                >
                                    <i class="fas fa-fw fa-undo"></i>
                                    <span><?= htmlspecialchars(l('link.settings.ai_bundle_restore'), ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            </div>

                            <?php if($fcc_ai_actions_stale): ?>
                                <div class="alert alert-warning mt-3 mb-0" style="border-radius:1rem;">
                                    <?= htmlspecialchars((string) ($fcc_ai_actions_freshness['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif ?>

                            <div id="<?= htmlspecialchars($fcc_ai_review_teaser_notification_id, ENT_QUOTES, 'UTF-8') ?>" class="fcc-app-review-teaser-notification"></div>
                        <?php endif ?>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="fcc-app-review-teaser-step-card">
                            <div class="small text-uppercase font-weight-bold fcc-app-review-teaser-step-label"><?= l('links.app_review_cta_label') ?></div>
                            <div class="fcc-app-review-teaser-step-number"><?= nr($fcc_app_review_next_step_number) ?></div>
                            <div class="fcc-app-review-teaser-step-title"><?= htmlspecialchars($fcc_app_review_next_step_title, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small fcc-app-review-teaser-step-text"><?= htmlspecialchars($fcc_app_review_next_step_text, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if($fcc_app_review_next_step_is_accessible): ?>
                                <a href="<?= htmlspecialchars($fcc_app_review_next_step_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-block"><?= htmlspecialchars($fcc_app_review_next_step_button_label, ENT_QUOTES, 'UTF-8') ?></a>
                            <?php else: ?>
                                <a href="#" class="btn btn-primary btn-block disabled pointer-events-all" data-tooltip title="<?= htmlspecialchars($fcc_app_review_next_step_locked_reason, ENT_QUOTES, 'UTF-8') ?>" onclick="event.preventDefault();" style="opacity:.62; filter:saturate(.75);"><?= htmlspecialchars($fcc_app_review_next_step_button_label, ENT_QUOTES, 'UTF-8') ?></a>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>

    <div id="links_auto_copy_link"></div>

    <?= $this->views['method'] ?>

    <?php if($fcc_is_short_link_editor): ?>
    </div>
    <?php endif ?>
</div>


<?php ob_start() ?>
<link href="<?= ASSETS_FULL_URL . 'css/libraries/daterangepicker.min.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
<link href="<?= ASSETS_FULL_URL . 'css/libraries/fontawesome-iconpicker.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>
<?php include_view(THEME_PATH . 'views/partials/color_picker_js.php', ['exclude_js' => true]) ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/duplicate_modal.php', ['modal_id' => 'link_duplicate_modal', 'resource_id' => 'link_id', 'path' => 'link-ajax/duplicate']), 'modals'); ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/duplicate_modal.php', ['modal_id' => 'biolink_block_duplicate_modal', 'resource_id' => 'biolink_block_id', 'path' => 'biolink-block-ajax/duplicate']), 'modals'); ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/x_reset_modal.php', ['modal_id' => 'link_reset_modal', 'resource_id' => 'link_id', 'path' => 'links/reset']), 'modals'); ?>

<?php ob_start() ?>
<script>
    'use strict';
    
    const query_parameters = new URLSearchParams(window.location.search);

    if (query_parameters.has('auto_copy_link')) {
        let text = document.querySelector('#link_full_url_copy').getAttribute('data-clipboard-text');
        let notification_container = document.querySelector('#links_auto_copy_link');

        navigator.clipboard.writeText(text).then(() => {
            display_notifications(<?= json_encode(l('links.auto_copy_link.success')) ?>, 'success', notification_container);
        }).catch((error) => {
            display_notifications(<?= json_encode(l('links.auto_copy_link.error')) ?>, 'error', notification_container);
        });
    }

    const post_link_index_ai_editor_action = ({request_type = '', link_id = 0, notification_target = ''} = {}) => {
        let notification_container = notification_target ? document.querySelector(notification_target) : null;

        if(notification_container) {
            notification_container.innerHTML = '';
        }

        $.ajax({
            type: 'POST',
            url: `${url}link-ajax`,
            dataType: 'json',
            data: {
                token: <?= json_encode(\Altum\Csrf::get()) ?>,
                request_type,
                link_id
            },
            success: (data) => {
                if(notification_container) {
                    display_notifications(data.message || <?= json_encode(l('global.error_message.basic')) ?>, data.status || 'error', notification_container);
                    notification_container.scrollIntoView({behavior: 'smooth', block: 'nearest'});
                }

                if(data.status === 'success') {
                    window.setTimeout(() => window.location.reload(), 700);
                }
            },
            error: () => {
                if(notification_container) {
                    display_notifications(<?= json_encode(l('global.error_message.basic')) ?>, 'error', notification_container);
                }
            }
        });
    };

    document.querySelectorAll('.js-link-index-ai-editor-action').forEach(element => {
        element.addEventListener('click', event => {
            const button = event.currentTarget;

            if(button.disabled) {
                return;
            }

            const notification_target = button.getAttribute('data-notification-target') || '';
            const notification_container = notification_target ? document.querySelector(notification_target) : null;

            if((button.getAttribute('data-ai-stale') || '0') === '1' && notification_container) {
                display_notifications(button.getAttribute('data-ai-stale-message') || <?= json_encode(l('link.settings.ai_bundle_stale_notice')) ?>, 'warning', notification_container);
                notification_container.scrollIntoView({behavior: 'smooth', block: 'nearest'});
            }

            post_link_index_ai_editor_action({
                request_type: button.getAttribute('data-request-type') || '',
                link_id: button.getAttribute('data-link-id') || 0,
                notification_target
            });
        });
    });
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php if($fcc_is_short_link_editor): ?>
<?php ob_start() ?>
<div class="fcc-short-link-tour-backdrop" id="fcc_short_link_editor_backdrop"></div>
<div class="fcc-short-link-tour-popover" id="fcc_short_link_editor_popover" aria-live="polite">
    <div class="fcc-short-link-tour-progress" id="fcc_short_link_editor_progress">1 / <?= count($fcc_short_link_editor_steps) ?></div>
    <div class="fcc-short-link-tour-title" id="fcc_short_link_editor_title"></div>
    <div class="fcc-short-link-tour-text" id="fcc_short_link_editor_text"></div>
    <div class="fcc-short-link-tour-actions">
        <button type="button" class="btn btn-link text-muted px-0" id="fcc_short_link_editor_skip"><?= l('dashboard.tour.skip') ?></button>
        <div class="fcc-short-link-tour-actions-main">
            <button type="button" class="btn btn-outline-light" id="fcc_short_link_editor_prev"><?= l('dashboard.tour.prev') ?></button>
            <button type="button" class="btn btn-primary" id="fcc_short_link_editor_next"><?= l('dashboard.tour.next') ?></button>
        </div>
    </div>
</div>
<?php \Altum\Event::add_content(ob_get_clean(), 'modals') ?>

<?php ob_start() ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const storageKey = 'fcc_short_link_editor_tour_seen_v1';
    const steps = <?= json_encode($fcc_short_link_editor_steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const backdrop = document.getElementById('fcc_short_link_editor_backdrop');
    const popover = document.getElementById('fcc_short_link_editor_popover');
    const title = document.getElementById('fcc_short_link_editor_title');
    const text = document.getElementById('fcc_short_link_editor_text');
    const progress = document.getElementById('fcc_short_link_editor_progress');
    const prevButton = document.getElementById('fcc_short_link_editor_prev');
    const nextButton = document.getElementById('fcc_short_link_editor_next');
    const skipButton = document.getElementById('fcc_short_link_editor_skip');
    const startButtons = document.querySelectorAll('[data-fcc-start-short-link-editor-tour]');

    if(!backdrop || !popover || !title || !text || !progress || !prevButton || !nextButton || !skipButton || !Array.isArray(steps) || !steps.length) {
        return;
    }

    let activeStep = -1;
    let currentTarget = null;
    let elevatedAncestors = [];
    let backdropSegments = [];

    const setTourMode = isActive => {
        document.body.classList.toggle('fcc-tour-mode', !!isActive);

        if(typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent('fcc:tutorial:state', {
                detail: {active: !!isActive}
            }));
        }
    };

    const ensureBackdropSegments = () => {
        if(backdropSegments.length) return backdropSegments;

        backdropSegments = Array.from({length: 4}, () => {
            const segment = document.createElement('div');
            segment.className = 'fcc-short-link-tour-backdrop-segment';
            backdrop.appendChild(segment);
            return segment;
        });

        return backdropSegments;
    };

    const getElevatedAncestors = target => {
        const ancestors = [];
        let node = target?.parentElement ?? null;

        while(node && node !== document.body) {
            const computedStyle = window.getComputedStyle(node);
            const hasClippingOverflow = ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflow) || ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflowX) || ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflowY);
            const shouldElevate = hasClippingOverflow;

            if(shouldElevate) {
                ancestors.push(node);
            }

            node = node.parentElement;
        }

        return ancestors;
    };

    const clearHighlight = () => {
        if(currentTarget) {
            currentTarget.classList.remove('fcc-short-link-tour-active');
        }

        elevatedAncestors.forEach(node => node.classList.remove('fcc-short-link-tour-ancestor'));
        elevatedAncestors = [];

        currentTarget = null;
    };

    const placePopover = () => {
        if(!currentTarget || !popover.classList.contains('is-visible')) {
            return;
        }

        const rect = currentTarget.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const popoverWidth = popover.offsetWidth;
        const popoverHeight = popover.offsetHeight;
        const spacing = 18;

        let top = rect.bottom + spacing;
        let left = rect.left;

        if(top + popoverHeight > viewportHeight - spacing) {
            top = Math.max(spacing, rect.top - popoverHeight - spacing);
        }

        if(left + popoverWidth > viewportWidth - spacing) {
            left = Math.max(spacing, viewportWidth - popoverWidth - spacing);
        }

        if(left < spacing) {
            left = spacing;
        }

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
    };

    const updateBackdropSpotlight = () => {
        if(!currentTarget || !backdrop.classList.contains('is-visible')) {
            return;
        }

        const segments = ensureBackdropSegments();
        const rect = currentTarget.getBoundingClientRect();
        const padding = 10;
        const top = Math.max(0, rect.top - padding);
        const left = Math.max(0, rect.left - padding);
        const right = Math.min(window.innerWidth, rect.right + padding);
        const bottom = Math.min(window.innerHeight, rect.bottom + padding);
        const holeWidth = Math.max(0, right - left);
        const holeHeight = Math.max(0, bottom - top);

        Object.assign(segments[0].style, {top: '0px', left: '0px', width: '100vw', height: `${top}px`});
        Object.assign(segments[1].style, {top: `${top}px`, left: '0px', width: `${left}px`, height: `${holeHeight}px`});
        Object.assign(segments[2].style, {top: `${top}px`, left: `${right}px`, width: `${Math.max(0, window.innerWidth - right)}px`, height: `${holeHeight}px`});
        Object.assign(segments[3].style, {top: `${bottom}px`, left: '0px', width: '100vw', height: `${Math.max(0, window.innerHeight - bottom)}px`});
    };

    const endTour = completed => {
        clearHighlight();
        activeStep = -1;
        setTourMode(false);
        backdrop.classList.remove('is-visible');
        popover.classList.remove('is-visible');

        if(completed) {
            localStorage.setItem(storageKey, '1');
        }
    };

    const renderStep = index => {
        const step = steps[index];
        if(!step) {
            endTour(false);
            return;
        }

        const target = document.querySelector(step.selector);
        if(!target) {
            if(index >= steps.length - 1) {
                endTour(true);
                return;
            }

            renderStep(index + 1);
            return;
        }

        activeStep = index;
        clearHighlight();
        currentTarget = target;
        elevatedAncestors = getElevatedAncestors(currentTarget);
        elevatedAncestors.forEach(node => node.classList.add('fcc-short-link-tour-ancestor'));
        currentTarget.classList.add('fcc-short-link-tour-active');
        currentTarget.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'});

        title.textContent = step.title || '';
        text.textContent = step.text || '';
        progress.textContent = `${index + 1} / ${steps.length}`;
        prevButton.style.visibility = index === 0 ? 'hidden' : 'visible';
        nextButton.textContent = index === steps.length - 1 ? <?= json_encode(l('dashboard.tour.finish')) ?> : <?= json_encode(l('dashboard.tour.next')) ?>;

        backdrop.classList.add('is-visible');
        popover.classList.add('is-visible');
        updateBackdropSpotlight();
        setTimeout(placePopover, 140);
    };

    const startTour = ({markAutoSeen = false} = {}) => {
        if(markAutoSeen) {
            localStorage.setItem(storageKey, '1');
        }

        setTourMode(true);
        renderStep(0);
    };

    startButtons.forEach(button => button.addEventListener('click', startTour));
    skipButton.addEventListener('click', () => endTour(false));
    prevButton.addEventListener('click', () => {
        if(activeStep > 0) {
            renderStep(activeStep - 1);
        }
    });
    nextButton.addEventListener('click', () => {
        if(activeStep >= steps.length - 1) {
            endTour(true);
            return;
        }

        renderStep(activeStep + 1);
    });

    const syncOverlay = () => {
        placePopover();
        updateBackdropSpotlight();
    };

    window.addEventListener('resize', syncOverlay);
    window.addEventListener('scroll', syncOverlay, {passive: true});

    if(!localStorage.getItem(storageKey)) {
        setTimeout(() => startTour({markAutoSeen: true}), 500);
    }
});
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>
