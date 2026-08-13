<?php defined('ALTUMCODE') || die() ?>

<?php
$dashboard_forever_products_url = fc_get_forever_products_blog_category_url(\Altum\Language::$name);
$dashboard_forever_activity = (array) ($data->forever_activity_notice ?? []);
$dashboard_forever_activity_status = $dashboard_forever_activity['status'] ?? 'unavailable';
$dashboard_format_cc = static function($value): string {
    $formatted = number_format((float) $value, 3, ',', '.');
    return rtrim(rtrim($formatted, '0'), ',');
};
?>

<?php ob_start() ?>
<style>
    .dashboard-four-cc-notice {
        --notice-accent: #f6c900;
        --notice-accent-rgb: 246, 201, 0;
        position: relative;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        grid-template-rows: auto auto;
        align-items: center;
        gap: 1.15rem;
        overflow: hidden;
        margin-bottom: 1rem;
        padding: 1.05rem 1.15rem;
        border: 1px solid rgba(var(--notice-accent-rgb), .26);
        border-radius: 1.25rem;
        color: #f8fbff !important;
        text-decoration: none !important;
        background:
            radial-gradient(circle at 88% 0%, rgba(var(--notice-accent-rgb), .13), transparent 30%),
            linear-gradient(135deg, rgba(var(--notice-accent-rgb), .085), rgba(10, 17, 30, .96) 48%);
        box-shadow: 0 18px 38px rgba(2, 8, 23, .14), inset 0 1px 0 rgba(255,255,255,.04);
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .dashboard-four-cc-notice:hover,
    .dashboard-four-cc-notice:focus {
        color: #fff !important;
        transform: translateY(-1px);
        border-color: rgba(var(--notice-accent-rgb), .43);
        box-shadow: 0 22px 44px rgba(2, 8, 23, .2), 0 0 0 1px rgba(var(--notice-accent-rgb), .08);
        outline: none;
    }

    .dashboard-four-cc-notice--active {
        --notice-accent: #49e3cf;
        --notice-accent-rgb: 73, 227, 207;
    }

    .dashboard-four-cc-notice--pending {
        --notice-accent: #5bb6ff;
        --notice-accent-rgb: 91, 182, 255;
    }

    .dashboard-four-cc-notice--unavailable {
        --notice-accent: #94a3b8;
        --notice-accent-rgb: 148, 163, 184;
    }

    .dashboard-four-cc-notice-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3.2rem;
        height: 3.2rem;
        flex-shrink: 0;
        border-radius: 1rem;
        color: var(--notice-accent);
        background: rgba(var(--notice-accent-rgb), .12);
        border: 1px solid rgba(var(--notice-accent-rgb), .18);
        font-size: 1.18rem;
        grid-row: 1 / 3;
    }

    .dashboard-four-cc-notice-copy {
        min-width: 0;
    }

    .dashboard-four-cc-notice-kicker {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
        margin-bottom: .25rem;
        color: var(--notice-accent);
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .055em;
        text-transform: uppercase;
    }

    .dashboard-four-cc-notice-period {
        padding: .23rem .48rem;
        border-radius: 999px;
        color: rgba(226, 232, 240, .86);
        background: rgba(255,255,255,.055);
        font-size: .68rem;
        letter-spacing: .03em;
    }

    .dashboard-four-cc-notice-title {
        display: block;
        margin: 0 0 .28rem;
        color: #fff;
        font-size: 1.08rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .dashboard-four-cc-notice-text {
        display: block;
        margin: 0;
        color: rgba(203, 213, 225, .84);
        font-size: .88rem;
        line-height: 1.5;
    }

    .dashboard-four-cc-notice-progress {
        min-width: 0;
        width: min(100%, 34rem);
        grid-column: 2;
        grid-row: 2;
        padding-top: .15rem;
    }

    .dashboard-four-cc-notice-progress-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .48rem;
        color: rgba(226, 232, 240, .82);
        font-size: .76rem;
        font-weight: 700;
    }

    .dashboard-four-cc-notice-progress-top strong {
        color: #fff;
        white-space: nowrap;
    }

    .dashboard-four-cc-notice-track {
        display: block;
        width: 100%;
        height: .55rem;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(148, 163, 184, .17);
    }

    .dashboard-four-cc-notice-fill {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, rgba(var(--notice-accent-rgb), .72), var(--notice-accent));
        box-shadow: 0 0 14px rgba(var(--notice-accent-rgb), .24);
    }

    .dashboard-four-cc-notice-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.55rem;
        height: 2.55rem;
        border-radius: .85rem;
        color: var(--notice-accent);
        background: rgba(var(--notice-accent-rgb), .1);
        transition: transform .18s ease;
        grid-column: 3;
        grid-row: 1 / 3;
    }

    .dashboard-four-cc-notice:hover .dashboard-four-cc-notice-arrow,
    .dashboard-four-cc-notice:focus .dashboard-four-cc-notice-arrow {
        transform: translateX(3px);
    }

    .dashboard-funnel-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 1.35rem;
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, 0.1), transparent 28%),
            linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.015));
    }

    .dashboard-funnel-card::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(135deg, rgba(73, 227, 207, 0.05), transparent 48%);
    }

    .dashboard-funnel-shell {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(18rem, 1fr);
        gap: 1rem;
        align-items: stretch;
    }

    .dashboard-funnel-side,
    .dashboard-funnel-metrics {
        min-width: 0;
    }

    .dashboard-funnel-kpis {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .8rem;
    }

    .dashboard-funnel-kpi {
        border-radius: 1rem;
        padding: .9rem 1rem;
        border: 1px solid rgba(255,255,255,.06);
        background: rgba(255,255,255,.03);
    }

    .dashboard-funnel-kpi-label {
        color: var(--gray-500);
        font-size: .74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .45rem;
    }

    .dashboard-funnel-kpi-value {
        color: #fff;
        font-size: 1.3rem;
        font-weight: 700;
        line-height: 1.15;
    }

    .dashboard-funnel-insights {
        display: flex;
        flex-direction: column;
        gap: .7rem;
        margin-top: .9rem;
    }

    .dashboard-funnel-insight {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        padding: .85rem .95rem;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,.06);
        background: rgba(255,255,255,.03);
    }

    .dashboard-funnel-insight-value {
        color: #fff;
        font-weight: 700;
    }

    .dashboard-funnel-status-copy {
        max-width: 38rem;
    }

    .dashboard-funnel-status-copy .btn {
        border-radius: .9rem;
    }

    .dashboard-funnel-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 999px;
        padding: .35rem .7rem;
        font-size: .76rem;
        font-weight: 700;
        margin-bottom: .8rem;
    }

    .dashboard-funnel-badge.is-good {
        color: #34d399;
        background: rgba(52, 211, 153, .12);
    }

    .dashboard-funnel-badge.is-warning {
        color: #fbbf24;
        background: rgba(251, 191, 36, .12);
    }

    .dashboard-funnel-badge.is-danger,
    .dashboard-funnel-badge.is-setup {
        color: #fb7185;
        background: rgba(251, 113, 133, .12);
    }

    .dashboard-modern-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 1.2rem;
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, 0.07), transparent 24%),
            linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.015));
        box-shadow: 0 18px 40px rgba(15, 23, 42, .12);
    }

    .dashboard-modern-card::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(135deg, rgba(73, 227, 207, 0.04), transparent 52%);
    }

    .dashboard-modern-card > .card-body {
        position: relative;
        z-index: 1;
    }

    .dashboard-section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.1rem;
        padding: 0 0 .25rem;
    }

    .dashboard-section-heading .h5 {
        margin-bottom: 0;
    }

    .dashboard-page-guide-rail {
        display: flex;
        justify-content: flex-end;
        margin: 0 0 .72rem;
    }

    .dashboard-page-guide-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .48rem;
        padding: .68rem .98rem;
        min-height: 2.7rem;
        border-radius: .95rem;
        border: 1px solid rgba(111, 244, 228, .28);
        background: linear-gradient(135deg, rgba(42, 215, 199, .14) 0%, rgba(29, 122, 209, .12) 100%);
        color: #eefdfb;
        font-size: .86rem;
        font-weight: 750;
        line-height: 1.1;
        text-decoration: none !important;
        box-shadow: 0 12px 24px rgba(4, 14, 25, .14), inset 0 1px 0 rgba(255,255,255,.06);
        transition: all .18s ease;
    }

    .dashboard-page-guide-trigger i {
        color: #8cf6e9;
        font-size: .92em;
    }

    .dashboard-page-guide-trigger:hover,
    .dashboard-page-guide-trigger:focus {
        color: #ffffff;
        border-color: rgba(111, 244, 228, .42);
        background: linear-gradient(135deg, rgba(44, 214, 199, .2) 0%, rgba(41, 126, 212, .18) 100%);
        box-shadow: 0 16px 30px rgba(63, 215, 199, .12);
        transform: translateY(-1px);
        outline: none;
    }

    .dashboard-onboarding-card .small + .small {
        margin-top: .35rem;
    }

    .dashboard-onboarding-card {
        position: relative;
        overflow: hidden;
        padding: 1.15rem 1.2rem;
        border-radius: 1.3rem;
        border: 1px solid rgba(91, 182, 255, .14);
        background:
            radial-gradient(circle at top right, rgba(91, 182, 255, .12), transparent 28%),
            linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.015));
    }

    .dashboard-onboarding-card::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(135deg, rgba(73, 227, 207, .05), transparent 50%);
    }

    .dashboard-onboarding-card > * {
        position: relative;
        z-index: 1;
    }

    .dashboard-onboarding-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(18rem, .85fr);
        gap: 1.1rem;
        align-items: stretch;
    }

    .dashboard-onboarding-card--embedded {
        margin-top: 1.15rem;
        max-width: 44rem;
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .08), transparent 28%),
            linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.015));
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-grid {
        grid-template-columns: minmax(0, 1fr) minmax(15rem, .9fr);
        gap: .9rem;
    }

    .dashboard-onboarding-copy {
        max-width: 46rem;
    }

    .dashboard-onboarding-title {
        margin-bottom: .5rem;
        font-size: 1.45rem;
        line-height: 1.15;
        color: #fff;
    }

    .dashboard-onboarding-text {
        color: rgba(226, 232, 240, .86);
        font-size: 1rem;
        line-height: 1.65;
        margin-bottom: 1rem;
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-title {
        font-size: 1.12rem;
        margin-bottom: .45rem;
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-text {
        font-size: .95rem;
        line-height: 1.55;
        margin-bottom: .8rem;
    }

    .dashboard-onboarding-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        margin-top: 1rem;
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: stretch;
        gap: .65rem;
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-actions .dashboard-growth-button {
        border-radius: .9rem;
        padding: .72rem .9rem;
        font-size: .92rem;
        line-height: 1.2;
        min-height: 3rem;
        justify-content: center;
        text-align: center;
        white-space: normal;
        word-break: break-word;
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-actions .btn-primary,
    .dashboard-onboarding-card--embedded .dashboard-onboarding-actions .btn-outline-light {
        min-width: 0;
        width: 100%;
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-actions .btn-outline-light {
        color: #dff7ff !important;
        border-color: rgba(91, 182, 255, 0.3) !important;
        background: rgba(91, 182, 255, 0.12) !important;
        box-shadow: inset 0 0 0 1px rgba(91, 182, 255, 0.08);
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-actions .btn-link {
        grid-column: 1 / -1;
        padding-top: 0;
        font-size: .88rem;
        text-align: left;
        justify-content: flex-start;
    }

    .dashboard-onboarding-list {
        display: grid;
        gap: .85rem;
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-list {
        gap: .65rem;
    }

    .dashboard-onboarding-list-item {
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,.08);
        background: rgba(8, 15, 30, .44);
        padding: .95rem 1rem;
    }

    .dashboard-onboarding-list-item strong {
        display: block;
        color: #fff;
        font-size: .95rem;
        margin-bottom: .25rem;
    }

    .dashboard-onboarding-list-item span {
        color: rgba(203, 213, 225, .82);
        font-size: .92rem;
        line-height: 1.55;
        display: block;
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-list-item {
        padding: .8rem .9rem;
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-list-item strong {
        font-size: .9rem;
        margin-bottom: .2rem;
    }

    .dashboard-onboarding-card--embedded .dashboard-onboarding-list-item span {
        font-size: .84rem;
        line-height: 1.45;
    }

    .dashboard-onboarding-quickhelp {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        margin-top: .2rem;
        color: rgba(148, 163, 184, .92);
        font-size: .88rem;
    }

    .dashboard-onboarding-quickhelp i {
        color: #49e3cf;
    }

    .dashboard-onboarding-tutorials {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(148, 163, 184, 0.14);
    }

    .dashboard-onboarding-tutorials-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: .9rem;
    }

    .dashboard-onboarding-tutorials-title {
        margin: 0 0 .35rem;
        color: #ffffff;
        font-size: 1rem;
        line-height: 1.2;
    }

    .dashboard-onboarding-tutorials-text {
        margin: 0;
        color: rgba(203, 213, 225, .82);
        font-size: .9rem;
        line-height: 1.55;
        max-width: 34rem;
    }

    .dashboard-onboarding-tutorials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
        gap: .85rem;
    }

    .dashboard-onboarding-tutorial-button {
        width: 100%;
        appearance: none;
        border: 1px solid rgba(111, 244, 228, .16);
        border-radius: 1.15rem;
        padding: 1rem 1rem .95rem;
        text-align: left;
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .12), transparent 28%),
            linear-gradient(180deg, rgba(9, 15, 29, .92), rgba(13, 22, 39, .96));
        box-shadow: inset 0 1px 0 rgba(255,255,255,.04), 0 14px 30px rgba(2, 8, 23, .14);
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
        cursor: pointer;
    }

    .dashboard-onboarding-tutorial-button:hover,
    .dashboard-onboarding-tutorial-button:focus {
        outline: none;
        transform: translateY(-1px);
        border-color: rgba(111, 244, 228, .28);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.05), 0 18px 34px rgba(2, 8, 23, .2);
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .18), transparent 32%),
            linear-gradient(180deg, rgba(11, 19, 35, .98), rgba(15, 25, 43, .98));
    }

    .dashboard-onboarding-tutorial-badge {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .7rem;
        padding: .36rem .68rem;
        border-radius: 999px;
        color: #dffdf8;
        font-size: .74rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        background: rgba(73, 227, 207, .12);
        border: 1px solid rgba(73, 227, 207, .16);
    }

    .dashboard-onboarding-tutorial-button strong {
        display: block;
        color: #ffffff;
        font-size: 1rem;
        line-height: 1.3;
        margin-bottom: .4rem;
    }

    .dashboard-onboarding-tutorial-button > span:not(.dashboard-onboarding-tutorial-badge):not(.dashboard-onboarding-tutorial-action) {
        display: block;
        color: rgba(226, 232, 240, .84);
        font-size: .9rem;
        line-height: 1.6;
    }

    .dashboard-onboarding-tutorial-action {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        margin-top: .85rem;
        color: #75f3df;
        font-size: .88rem;
        font-weight: 800;
    }

    .dashboard-onboarding-tutorial-action i {
        font-size: .8rem;
    }

    .dashboard-onboarding-modal-step {
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,.08);
        background: rgba(15, 23, 42, .72);
        padding: 1rem 1rem .95rem;
        height: 100%;
    }

    .dashboard-onboarding-modal-step strong {
        display: block;
        color: #fff;
        margin-bottom: .35rem;
        font-size: .95rem;
    }

    .dashboard-onboarding-modal-step p {
        margin-bottom: 0;
        color: rgba(203, 213, 225, .84);
        line-height: 1.6;
        font-size: .92rem;
    }

    .dashboard-onboarding-modal-step--highlight {
        border-color: rgba(111, 244, 228, .22);
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .14), transparent 32%),
            linear-gradient(180deg, rgba(14, 24, 41, .9), rgba(12, 20, 35, .94));
    }

    .dashboard-onboarding-modal-callout {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .9rem;
        padding: .95rem 1rem;
        margin-bottom: 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(111, 244, 228, .16);
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .14), transparent 34%),
            rgba(11, 19, 34, .92);
    }

    .dashboard-onboarding-modal-callout-copy {
        min-width: 0;
        flex: 1 1 20rem;
    }

    .dashboard-onboarding-modal-callout-text {
        margin: .45rem 0 0;
        color: rgba(226, 232, 240, .84);
        font-size: .9rem;
        line-height: 1.6;
    }

    .dashboard-tour-target {
        scroll-margin-top: 6rem;
    }

    .dashboard-tour-active-target {
        position: relative !important;
        z-index: 2052 !important;
        box-shadow: 0 0 0 2px rgba(73, 227, 207, .95), 0 0 0 14px rgba(73, 227, 207, .16), 0 24px 72px rgba(2, 8, 23, .42) !important;
        border-radius: 1.35rem !important;
    }

    .dashboard-tour-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2050;
        display: none;
        pointer-events: none;
    }

    .dashboard-tour-backdrop.is-visible {
        display: block;
    }

    .dashboard-tour-backdrop-segment {
        position: fixed;
        background: rgba(2, 8, 23, .58);
        backdrop-filter: blur(3px);
        pointer-events: none;
    }

    .dashboard-tour-popover {
        position: fixed;
        z-index: 2055;
        width: min(24rem, calc(100vw - 2rem));
        display: none;
        border-radius: 1.2rem;
        border: 1px solid rgba(147, 197, 253, .22);
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .18), transparent 30%),
            linear-gradient(180deg, rgba(25, 36, 58, .98), rgba(16, 24, 41, .97));
        box-shadow: 0 30px 80px rgba(2, 8, 23, .44), inset 0 1px 0 rgba(255,255,255,.05);
        padding: 1.05rem 1.05rem 1rem;
    }

    .dashboard-tour-popover.is-visible {
        display: block;
    }

    .dashboard-tour-progress {
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

    .dashboard-tour-title {
        color: #f8fbff;
        font-size: 1.12rem;
        font-weight: 800;
        line-height: 1.3;
        margin-bottom: .45rem;
    }

    .dashboard-tour-text {
        color: rgba(236, 244, 255, .94);
        font-size: .94rem;
        line-height: 1.65;
        margin-bottom: 1rem;
    }

    .dashboard-tour-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .dashboard-tour-actions-main {
        display: flex;
        gap: .65rem;
        flex-wrap: wrap;
    }

    .dashboard-tour-actions .btn {
        border-radius: .85rem;
    }

    .dashboard-tour-actions .btn-link {
        color: rgba(226, 232, 240, .82) !important;
        text-decoration: none;
    }

    .dashboard-tour-actions .btn-link:hover,
    .dashboard-tour-actions .btn-link:focus {
        color: #ffffff !important;
        text-decoration: none;
    }

    .dashboard-tour-actions .btn-outline-light {
        color: #ecf8ff !important;
        border-color: rgba(147, 197, 253, .28) !important;
        background: rgba(59, 130, 246, .12) !important;
    }

    .dashboard-tour-actions .btn-outline-light:hover,
    .dashboard-tour-actions .btn-outline-light:focus {
        color: #ffffff !important;
        border-color: rgba(147, 197, 253, .48) !important;
        background: rgba(59, 130, 246, .2) !important;
    }

    .dashboard-kpi-card .badge {
        border-radius: 999px;
        padding: .35rem .55rem;
    }

    .dashboard-chart-card {
        margin-top: 1rem;
    }

    .dashboard-kpi-trigger {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        cursor: pointer;
    }

    .dashboard-kpi-trigger:hover .dashboard-kpi-trigger__value,
    .dashboard-kpi-trigger:focus .dashboard-kpi-trigger__value {
        text-decoration: underline;
    }

    .dashboard-kpi-trigger:focus {
        outline: none;
    }

    .dashboard-kpi-trigger__value {
        color: #fff;
    }

    .dashboard-geo-list-row {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        padding: .65rem 0;
        border-bottom: 1px solid rgba(255,255,255,.06);
    }

    .dashboard-geo-list-row--interactive {
        cursor: pointer;
        border-radius: .8rem;
        padding: .75rem .6rem;
        margin: 0 -.6rem;
    }

    .dashboard-geo-list-row--interactive:hover,
    .dashboard-geo-list-row--interactive:focus {
        background: rgba(255,255,255,.04);
        outline: none;
    }

    .dashboard-geo-list-row--active {
        background: rgba(73, 227, 207, 0.08);
    }

    .dashboard-geo-list-row:last-child {
        border-bottom: 0;
    }

    .dashboard-geo-list-label {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        min-width: 0;
        color: var(--gray-700);
    }

    .dashboard-geo-list-total {
        white-space: nowrap;
        color: #fff;
        font-weight: 700;
    }

    .dashboard-geo-list-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: .1rem;
        white-space: nowrap;
    }

    .dashboard-geo-list-share {
        color: var(--gray-500);
        font-size: .78rem;
    }

    .dashboard-geo-flag {
        width: 1rem;
        height: 1rem;
        border-radius: 999px;
        object-fit: cover;
        flex-shrink: 0;
        box-shadow: 0 0 0 1px rgba(255,255,255,.08);
    }

    .dashboard-geo-filter-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .85rem;
    }

    .dashboard-geo-filter-bar .badge {
        border-radius: 999px;
        padding: .45rem .65rem;
    }

    .dashboard-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .dashboard-grid-tight {
        margin: 0 -.5rem;
        row-gap: 0;
    }

    .dashboard-grid-tight > [class*="col-"] {
        padding: .5rem;
    }

    .dashboard-modern-card .card-body {
        padding: 1rem 1.1rem;
    }

    .dashboard-funnel-card .card-body {
        padding: 1.1rem 1.15rem;
    }

    .dashboard-list-grid + .dashboard-list-grid {
        margin-top: 0;
    }

    @media (max-width: 991.98px) {
        .dashboard-funnel-shell,
        .dashboard-funnel-kpis {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .dashboard-four-cc-notice {
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: .85rem;
            padding: .95rem;
        }

        .dashboard-four-cc-notice-progress {
            grid-column: 1 / -1;
            grid-row: 2;
            width: 100%;
        }

        .dashboard-four-cc-notice-icon {
            width: 2.85rem;
            height: 2.85rem;
            grid-row: 1;
        }

        .dashboard-four-cc-notice-arrow {
            grid-row: 1;
        }

        .dashboard-modern-card .card-body,
        .dashboard-funnel-card .card-body {
            padding: .95rem 1rem;
        }

        .dashboard-stack {
            gap: .85rem;
        }

        .dashboard-onboarding-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-onboarding-card--embedded {
            max-width: none;
            margin-top: 1rem;
        }

        .dashboard-onboarding-card--embedded .dashboard-onboarding-grid {
            grid-template-columns: 1fr;
            gap: .8rem;
        }

        .dashboard-onboarding-copy {
            max-width: none;
        }

        .dashboard-tour-popover {
            left: 1rem !important;
            right: 1rem !important;
            width: auto;
            top: auto !important;
            bottom: 1rem;
        }

        .dashboard-page-guide-rail {
            margin-bottom: .72rem;
        }

        .dashboard-page-guide-trigger {
            width: 100%;
            min-height: 2.8rem;
        }

        .dashboard-onboarding-card--embedded .dashboard-onboarding-actions {
            grid-template-columns: 1fr;
        }

        .dashboard-onboarding-card--embedded .dashboard-onboarding-actions .btn-primary,
        .dashboard-onboarding-card--embedded .dashboard-onboarding-actions .btn-outline-light {
            justify-content: center;
        }

        .dashboard-onboarding-tutorials-header,
        .dashboard-onboarding-modal-callout {
            align-items: flex-start;
        }
    }

    .dashboard-growth-shell {
        color: #eef6ff;
    }

    .dashboard-growth-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 1.55rem;
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.08), transparent 26%),
            linear-gradient(180deg, rgba(17, 24, 39, 0.98) 0%, rgba(9, 13, 25, 0.98) 100%);
        box-shadow: 0 1.6rem 3.4rem rgba(2, 6, 23, 0.18);
    }

    .dashboard-growth-card::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.06), transparent 50%);
    }

    .dashboard-growth-shell .text-muted {
        color: rgba(191, 211, 238, 0.74) !important;
    }

    .dashboard-growth-hero,
    .dashboard-workbench,
    .dashboard-signal-chart-card,
    .dashboard-side-card,
    .dashboard-list-card {
        padding: 1.45rem;
    }

    .dashboard-growth-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(320px, .88fr);
        gap: 1rem;
        align-items: stretch;
    }

    .dashboard-growth-pills {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        margin-bottom: 1rem;
    }

    .dashboard-growth-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .52rem .9rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        border: 1px solid rgba(255,255,255,.12);
        color: #e8f7ff;
    }

    .dashboard-growth-pill--green {
        background: rgba(34, 197, 94, 0.18);
        border-color: rgba(34, 197, 94, 0.34);
    }

    .dashboard-growth-pill--blue {
        background: rgba(59, 130, 246, 0.18);
        border-color: rgba(59, 130, 246, 0.34);
    }

    .dashboard-growth-pill--teal {
        background: rgba(45, 212, 191, 0.18);
        border-color: rgba(45, 212, 191, 0.34);
    }

    .dashboard-growth-pill--gold {
        background: rgba(234, 179, 8, 0.18);
        border-color: rgba(234, 179, 8, 0.34);
    }

    .dashboard-growth-title {
        font-size: clamp(2rem, 3vw, 3.2rem);
        line-height: 1.04;
        letter-spacing: -0.05em;
        color: #f8fbff;
        font-weight: 800;
        margin-bottom: .8rem;
        max-width: 14ch;
    }

    .dashboard-growth-subtitle {
        max-width: 44rem;
        color: rgba(226, 232, 240, 0.84);
        font-size: 1rem;
        line-height: 1.62;
        margin-bottom: 1rem;
    }

    .dashboard-growth-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .8rem;
    }

    .dashboard-growth-cta,
    .dashboard-growth-cta-secondary,
    .dashboard-growth-button {
        border-radius: 1rem;
        padding: .85rem 1.15rem;
        font-weight: 700;
    }

    .dashboard-growth-cta {
        box-shadow: 0 1rem 2.4rem rgba(45, 212, 191, 0.18);
    }

    .dashboard-growth-cta-secondary {
        color: #dff7ff !important;
        border-color: rgba(91, 182, 255, 0.28) !important;
        background: rgba(91, 182, 255, 0.12) !important;
        box-shadow: inset 0 0 0 1px rgba(91, 182, 255, 0.08);
    }

    .dashboard-growth-cta-secondary:hover,
    .dashboard-growth-cta-secondary:focus,
    .dashboard-growth-button.btn-outline-light:hover,
    .dashboard-growth-button.btn-outline-light:focus,
    .dashboard-workbench-actions .btn-outline-light:hover,
    .dashboard-workbench-actions .btn-outline-light:focus {
        color: #fff !important;
        border-color: rgba(91, 182, 255, 0.45) !important;
        background: rgba(91, 182, 255, 0.18) !important;
    }

    .dashboard-side-card .btn-outline-light {
        color: #eaf8ff !important;
        border-color: rgba(91, 182, 255, 0.32) !important;
        background: rgba(91, 182, 255, 0.14) !important;
        box-shadow: inset 0 0 0 1px rgba(91, 182, 255, 0.08);
    }

    .dashboard-side-card .btn-outline-light:hover,
    .dashboard-side-card .btn-outline-light:focus {
        color: #ffffff !important;
        border-color: rgba(91, 182, 255, 0.48) !important;
        background: rgba(91, 182, 255, 0.22) !important;
    }

    .dashboard-growth-hero-aside {
        padding: 1rem 1.05rem;
        border-radius: 1.25rem;
        border: 1px solid rgba(148, 163, 184, 0.14);
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.12), transparent 28%),
            rgba(15, 23, 42, 0.76);
    }

    .dashboard-growth-aside-stack {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }

    .dashboard-growth-aside-intro {
        border-radius: .95rem;
        border: 1px solid rgba(148, 163, 184, 0.1);
        background: rgba(255,255,255,.03);
        padding: .7rem .8rem;
        color: rgba(191, 211, 238, 0.78);
        font-size: .8rem;
        line-height: 1.5;
    }

    .dashboard-growth-aside-row {
        display: flex;
        justify-content: space-between;
        gap: .9rem;
        padding: .72rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        color: rgba(191, 211, 238, 0.86);
    }

    .dashboard-growth-aside-row span {
        font-size: .92rem;
        line-height: 1.45;
    }

    .dashboard-growth-aside-row strong {
        color: #f8fbff;
        font-size: .98rem;
        text-align: right;
        line-height: 1.3;
    }

    .dashboard-growth-aside-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .dashboard-growth-aside-progress {
        display: grid;
        gap: .7rem;
        margin-top: .2rem;
    }

    .dashboard-growth-aside-progress-card {
        border-radius: 1.05rem;
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(255,255,255,.035);
        padding: .85rem .9rem;
    }

    .dashboard-growth-aside-progress-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .9rem;
        margin-bottom: .75rem;
    }

    .dashboard-growth-aside-progress-label {
        color: rgba(191, 211, 238, 0.82);
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        line-height: 1.4;
    }

    .dashboard-growth-aside-progress-value {
        text-align: right;
        flex-shrink: 0;
    }

    .dashboard-growth-aside-progress-value span {
        display: block;
        color: rgba(191, 211, 238, 0.72);
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 800;
        margin-bottom: .2rem;
    }

    .dashboard-growth-aside-progress-value strong {
        display: block;
        color: #f8fbff;
        font-size: 1.3rem;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .dashboard-growth-progress-track {
        height: .62rem;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.18);
        overflow: hidden;
        margin-bottom: .6rem;
    }

    .dashboard-growth-progress-fill {
        height: 100%;
        border-radius: inherit;
        width: 0;
        transition: width .35s ease;
    }

    .dashboard-growth-progress-fill--teal {
        background: linear-gradient(90deg, #49e3cf 0%, #5bb6ff 100%);
        box-shadow: 0 0 18px rgba(73, 227, 207, 0.24);
    }

    .dashboard-growth-progress-fill--gold {
        background: linear-gradient(90deg, #ffbf3c 0%, #ffd96a 100%);
        box-shadow: 0 0 18px rgba(255, 191, 60, 0.24);
    }

    .dashboard-growth-aside-progress-copy {
        color: rgba(191, 211, 238, 0.8);
        font-size: .85rem;
        line-height: 1.55;
    }

    .dashboard-growth-aside-note {
        border-radius: 1.05rem;
        border: 1px solid rgba(45, 212, 191, 0.18);
        background: linear-gradient(180deg, rgba(15, 118, 110, 0.16), rgba(15, 23, 42, 0.45));
        padding: .9rem .95rem;
        color: rgba(236, 253, 245, 0.92);
        line-height: 1.55;
        font-size: .92rem;
    }

    .dashboard-growth-eyebrow {
        margin-bottom: .55rem;
        color: rgba(191, 211, 238, 0.72);
        text-transform: uppercase;
        letter-spacing: .08em;
        font-size: .78rem;
        font-weight: 800;
    }

    .dashboard-workbench-title {
        font-size: clamp(1.45rem, 2vw, 2rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #f8fbff;
        margin-bottom: .45rem;
    }

    .dashboard-workbench-copy {
        color: rgba(191, 211, 238, 0.82);
        max-width: 58rem;
        line-height: 1.65;
        font-size: .98rem;
        margin-bottom: 0;
    }

    .dashboard-workbench-header {
        margin-bottom: .25rem;
    }

    .dashboard-workbench-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 1rem;
        align-items: stretch;
    }

    .dashboard-workbench-card {
        position: relative;
        border-radius: 1.25rem;
        padding: 1.05rem;
        border: 1px solid rgba(148, 163, 184, 0.14);
        background:
            radial-gradient(circle at top right, rgba(255,255,255,.04), transparent 32%),
            linear-gradient(180deg, rgba(18, 27, 46, 0.88), rgba(11, 18, 33, 0.94));
        box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
        display: flex;
        flex-direction: column;
        min-height: 21rem;
    }

    .dashboard-workbench-card h3,
    .dashboard-side-card-title {
        color: #f8fbff;
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.22;
        margin: .8rem 0 .55rem;
    }

    .dashboard-workbench-card p,
    .dashboard-side-card-copy {
        color: rgba(191, 211, 238, 0.82);
        line-height: 1.62;
        font-size: .97rem;
        margin-bottom: 0;
    }

    .dashboard-workbench-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
        margin-top: auto;
        padding-top: 1rem;
    }

    .dashboard-workbench-actions .btn {
        border-radius: .95rem;
        padding: .82rem .95rem;
        font-weight: 700;
        width: 100%;
        justify-content: center;
        text-align: center;
        line-height: 1.2;
    }

    .dashboard-workbench-actions .btn-outline-light {
        color: #e7f8ff !important;
        border-color: rgba(91, 182, 255, 0.28) !important;
        background: rgba(91, 182, 255, 0.12) !important;
        box-shadow: inset 0 0 0 1px rgba(91, 182, 255, 0.08);
    }

    .dashboard-workbench-card--green {
        background:
            radial-gradient(circle at top right, rgba(34, 197, 94, 0.14), transparent 32%),
            linear-gradient(180deg, rgba(12, 56, 40, 0.82), rgba(12, 21, 35, 0.94));
        border-color: rgba(34, 197, 94, 0.2);
    }

    .dashboard-workbench-card--blue {
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.14), transparent 32%),
            linear-gradient(180deg, rgba(11, 48, 88, 0.82), rgba(12, 21, 35, 0.94));
        border-color: rgba(59, 130, 246, 0.2);
    }

    .dashboard-workbench-card--gold {
        background:
            radial-gradient(circle at top right, rgba(234, 179, 8, 0.14), transparent 32%),
            linear-gradient(180deg, rgba(74, 54, 13, 0.82), rgba(12, 21, 35, 0.94));
        border-color: rgba(234, 179, 8, 0.2);
    }

    .dashboard-workbench-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .8rem;
        margin-top: 1rem;
        padding-top: .9rem;
        border-top: 1px solid rgba(148, 163, 184, 0.12);
    }

    .dashboard-workbench-meta > div {
        border-radius: .95rem;
        border: 1px solid rgba(148, 163, 184, 0.1);
        background: rgba(7, 13, 26, 0.22);
        padding: .8rem .85rem;
        min-width: 0;
    }

    .dashboard-workbench-meta span {
        display: block;
        margin-bottom: .35rem;
        color: rgba(148, 163, 184, 0.76);
        font-size: .8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .dashboard-workbench-meta strong {
        color: #f8fbff;
        font-size: 1rem;
        display: block;
        line-height: 1.3;
        word-break: break-word;
    }

    .dashboard-workbench-note {
        margin-top: 1rem;
        border-radius: 1.2rem;
        border: 1px solid rgba(148, 163, 184, 0.14);
        padding: 1rem 1.05rem;
        color: rgba(204, 221, 245, 0.82);
        background: rgba(10, 18, 35, 0.42);
        line-height: 1.6;
    }

    .dashboard-growth-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .dashboard-growth-kpi {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 1.2rem;
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent 28%),
            rgba(15, 23, 42, 0.7);
        padding: .95rem 1rem;
        min-height: 8.25rem;
    }

    .dashboard-growth-kpi-label {
        color: rgba(191, 211, 238, 0.72);
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 800;
        margin-bottom: .7rem;
    }

    .dashboard-growth-kpi-value {
        color: #fff;
        font-size: 1.95rem;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.04em;
        margin-bottom: .35rem;
    }

    .dashboard-growth-kpi-delta {
        color: rgba(45, 212, 191, 0.9);
        font-weight: 700;
        margin-bottom: .55rem;
    }

    .dashboard-signal-chart-card {
        min-height: 27rem;
    }

    .dashboard-chart-card-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        align-items: flex-start;
    }

    .dashboard-chart-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .85rem;
        color: rgba(191, 211, 238, 0.82);
        font-size: .85rem;
    }

    .dashboard-growth-side-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        height: 100%;
    }

    .dashboard-side-card-list > * + * {
        margin-top: .75rem;
    }

    .dashboard-side-card-list-item {
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.1);
        background: rgba(15, 23, 42, 0.46);
        padding: 1rem 1.05rem;
        color: rgba(226, 232, 240, 0.9);
        line-height: 1.6;
        font-size: .98rem;
    }

    .dashboard-list-card .dashboard-side-card-list-item {
        font-size: 1rem;
    }

    .dashboard-list-card .dashboard-side-card-list-item strong {
        font-size: 1rem;
        color: #f8fbff;
        font-weight: 800;
    }

    .dashboard-list-card .dashboard-side-card-list-item .text-muted.small {
        font-size: .88rem !important;
        color: rgba(191, 211, 238, 0.82) !important;
        line-height: 1.45;
    }

    .dashboard-list-card .dashboard-side-card-list-item .font-weight-bold,
    .dashboard-list-card .dashboard-side-card-list-item a {
        font-size: 1rem;
        line-height: 1.35;
    }

    .dashboard-list-source-meta {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        margin-bottom: .35rem;
        flex-wrap: wrap;
    }

    .dashboard-list-source-badge {
        display: inline-flex;
        align-items: center;
        padding: .22rem .5rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
        border: 1px solid rgba(255,255,255,.1);
        color: rgba(226, 232, 240, .92);
        background: rgba(255,255,255,.04);
    }

    .dashboard-list-source-badge--app {
        color: #8ff2e4;
        background: rgba(73, 227, 207, .12);
        border-color: rgba(73, 227, 207, .22);
    }

    .dashboard-list-source-badge--blog {
        color: #8bc8ff;
        background: rgba(91, 182, 255, .12);
        border-color: rgba(91, 182, 255, .22);
    }

    .dashboard-list-source-copy {
        color: rgba(203, 213, 225, .78);
        font-size: .83rem;
        line-height: 1.45;
    }

    .dashboard-side-card-list-item.is-positive {
        border-color: rgba(45, 212, 191, 0.16);
        background: linear-gradient(180deg, rgba(8, 47, 73, 0.46), rgba(15, 23, 42, 0.54));
    }

    .dashboard-side-card-list-item.is-warning {
        border-color: rgba(234, 179, 8, 0.18);
        background: linear-gradient(180deg, rgba(92, 59, 10, 0.2), rgba(15, 23, 42, 0.54));
    }

    .dashboard-list-card {
        height: 100%;
        padding: 1.3rem;
    }

    .dashboard-main-grid,
    .dashboard-focus-grid,
    .dashboard-lists-grid {
        display: grid;
        gap: 1rem;
    }

    .dashboard-main-grid {
        grid-template-columns: 1fr;
        margin-bottom: 1rem;
    }

    .dashboard-focus-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: stretch;
    }

    .dashboard-lists-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    @media (max-width: 1199.98px) {
        .dashboard-growth-hero,
        .dashboard-workbench-grid,
        .dashboard-growth-kpi-grid,
        .dashboard-main-grid,
        .dashboard-focus-grid,
        .dashboard-lists-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-chart-card-header {
            flex-direction: column;
        }

    }

    @media (max-width: 767.98px) {
        .dashboard-workbench-actions,
        .dashboard-workbench-meta {
            grid-template-columns: 1fr;
        }

        .dashboard-workbench-card {
            min-height: auto;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php /* Custom code: FC-2026-03-05: dashboard demo mode banner */ ?>
    <?php if(isset($_GET['demo']) && $_GET['demo'] == '1'): ?>
        <div class="alert alert-info">
            <?= l('dashboard.forever_analytics.demo_banner') ?>
        </div>
    <?php endif ?>
    <?php /* /Custom code: FC-2026-03-05 */ ?>

    <?php if(!empty($data->needs_fcc_education)): ?>
        <!-- Custom code: FC-2026-02-24: FCC core education banner -->
        <div class="alert alert-warning d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
            <div class="mb-3 mb-md-0">
                <div class="font-weight-bold mb-1">
                    <?= l('fcc_education.banner_title') ?>
                </div>
                <div><?= l('fcc_education.banner_text') ?></div>
            </div>
            <a class="btn btn-dark" href="<?= url('fcc-education') ?>">
                <?= l('fcc_education.banner_button') ?>
            </a>
        </div>
        <!-- /Custom code: FC-2026-02-24 -->
    <?php endif ?>

    <div class="mb-5 dashboard-growth-shell">
        <div class="dashboard-page-guide-rail">
            <button type="button" class="dashboard-page-guide-trigger dashboard-tour-target" id="dashboard_tour_step_page_guide" data-dashboard-start-tour>
                <i class="fas fa-fw fa-route"></i>
                <span><?= l('dashboard.tour.launch') ?></span>
            </button>
        </div>

    <?php /* Custom code: FC-2026-08-14: verified self-only 4 CC dashboard notice */ ?>
    <?php
    $activity_period_label = !empty($dashboard_forever_activity['period'])
        ? (new DateTimeImmutable($dashboard_forever_activity['period']))->format('m/Y')
        : null;
    $activity_personal_gap = (float) ($dashboard_forever_activity['personal_gap'] ?? 0);
    $activity_has_regional_data = !empty($dashboard_forever_activity['has_regional_data']);
    $activity_regional_gap = $activity_has_regional_data ? (float) ($dashboard_forever_activity['regional_gap'] ?? 0) : null;
    $activity_progress = max(0, min(100, (float) ($dashboard_forever_activity['progress'] ?? 0)));
    $activity_progress_label = ($dashboard_forever_activity['progress_basis'] ?? 'activity') === 'personal'
        ? l('dashboard.four_cc.personal_progress_label')
        : l('dashboard.four_cc.progress_label');
    $activity_notice_class = 'dashboard-four-cc-notice--' . $dashboard_forever_activity_status;

    if($dashboard_forever_activity_status === 'active') {
        $activity_title = l('dashboard.four_cc.active_title');
        $activity_text = l('dashboard.four_cc.active_text');
        $activity_icon = 'fa-check';
    } elseif($dashboard_forever_activity_status === 'pending') {
        $activity_title = l('dashboard.four_cc.pending_title');
        $activity_text = l('dashboard.four_cc.pending_text');
        $activity_icon = 'fa-clock';
    } elseif($dashboard_forever_activity_status === 'inactive') {
        if(!$activity_has_regional_data && $activity_personal_gap > 0) {
            $activity_title = sprintf(l('dashboard.four_cc.missing_personal_title'), $dashboard_format_cc($activity_personal_gap));
        } elseif(!$activity_has_regional_data) {
            $activity_title = l('dashboard.four_cc.unconfirmed_title');
        } elseif($activity_regional_gap > 0 && $activity_personal_gap > 0) {
            $activity_title = sprintf(l('dashboard.four_cc.missing_total_title'), $dashboard_format_cc($activity_regional_gap));
        } elseif($activity_regional_gap > 0) {
            $activity_title = sprintf(l('dashboard.four_cc.missing_total_title'), $dashboard_format_cc($activity_regional_gap));
        } else {
            $activity_title = sprintf(l('dashboard.four_cc.missing_personal_title'), $dashboard_format_cc($activity_personal_gap));
        }
        $activity_text = $activity_personal_gap > 0
            ? sprintf(l('dashboard.four_cc.inactive_personal_gap_text'), $dashboard_format_cc($activity_personal_gap))
            : l('dashboard.four_cc.inactive_text');
        $activity_icon = 'fa-bolt';
    } else {
        $activity_title = l('dashboard.four_cc.unavailable_title');
        $activity_text = l('dashboard.four_cc.unavailable_text');
        $activity_icon = 'fa-chart-line';
    }
    ?>
    <a href="<?= url('forever-business') ?>" class="dashboard-four-cc-notice <?= $activity_notice_class ?>" aria-label="<?= l('dashboard.four_cc.open_aria') ?>">
        <span class="dashboard-four-cc-notice-icon" aria-hidden="true"><i class="fas <?= $activity_icon ?>"></i></span>
        <span class="dashboard-four-cc-notice-copy">
            <span class="dashboard-four-cc-notice-kicker">
                <?= l('dashboard.four_cc.kicker') ?>
                <?php if($activity_period_label): ?><span class="dashboard-four-cc-notice-period"><?= $activity_period_label ?></span><?php endif ?>
            </span>
            <span class="dashboard-four-cc-notice-title"><?= $activity_title ?></span>
            <span class="dashboard-four-cc-notice-text"><?= $activity_text ?></span>
        </span>
        <span class="dashboard-four-cc-notice-progress" aria-hidden="true">
            <span class="dashboard-four-cc-notice-progress-top">
                <span><?= $activity_progress_label ?></span>
                <strong><?= number_format($activity_progress, 0, ',', '.') ?>%</strong>
            </span>
            <span class="dashboard-four-cc-notice-track"><span class="dashboard-four-cc-notice-fill" style="width: <?= $activity_progress ?>%"></span></span>
        </span>
        <span class="dashboard-four-cc-notice-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
    </a>
    <?php /* /Custom code: FC-2026-08-14 */ ?>
        <div class="dashboard-growth-hero dashboard-growth-card mb-4 dashboard-tour-target" id="dashboard_tour_step_hero">
            <div class="dashboard-growth-hero-main">
                <div class="dashboard-growth-pills">
                    <span class="dashboard-growth-pill dashboard-growth-pill--blue" id="dashboard_growth_stage_badge">...</span>
                    <span class="dashboard-growth-pill dashboard-growth-pill--green"><?= l('dashboard.hero.period_30_days') ?></span>
                    <span class="dashboard-growth-pill dashboard-growth-pill--teal"><?= l('dashboard.hero.blog_plus_apps') ?></span>
                </div>
                <h1 class="dashboard-growth-title" id="dashboard_hero_title"><?= l('dashboard.hero.loading_title') ?></h1>
                <p class="dashboard-growth-subtitle" id="dashboard_hero_description"><?= l('dashboard.hero.loading_description') ?></p>
                <div class="dashboard-growth-actions">
                    <a href="<?= url('links?type=biolink') ?>" class="btn btn-primary btn-lg dashboard-growth-cta" id="dashboard_hero_primary_cta"><?= l('dashboard.hero.primary_cta') ?></a>
                    <a href="<?= url('feedback-tickets') ?>" class="btn btn-outline-light btn-lg dashboard-growth-cta-secondary"><?= l('dashboard.hero.secondary_cta') ?></a>
                </div>
                <div class="dashboard-onboarding-card dashboard-onboarding-card--embedded mt-4">
                    <div class="dashboard-onboarding-grid">
                        <div class="dashboard-onboarding-copy">
                            <div class="dashboard-growth-eyebrow"><?= l('dashboard.onboarding_intro.eyebrow') ?></div>
                            <h2 class="dashboard-onboarding-title"><?= l('dashboard.onboarding_intro.title') ?></h2>
                            <p class="dashboard-onboarding-text"><?= l('dashboard.onboarding_intro.text') ?></p>
                        </div>
                        <div class="dashboard-onboarding-list">
                            <div class="dashboard-onboarding-list-item">
                                <strong><?= l('dashboard.onboarding_intro.item_1_title') ?></strong>
                                <span><?= l('dashboard.onboarding_intro.item_1_text') ?></span>
                            </div>
                            <div class="dashboard-onboarding-list-item">
                                <strong><?= l('dashboard.onboarding_intro.item_2_title') ?></strong>
                                <span><?= l('dashboard.onboarding_intro.item_2_text') ?></span>
                            </div>
                            <div class="dashboard-onboarding-list-item">
                                <strong><?= l('dashboard.onboarding_intro.item_3_title') ?></strong>
                                <span><?= l('dashboard.onboarding_intro.item_3_text') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-onboarding-tutorials dashboard-tour-target" id="dashboard_tour_step_tutorials">
                        <div class="dashboard-onboarding-tutorials-grid">
                            <button type="button" class="dashboard-onboarding-tutorial-button" data-toggle="modal" data-target="#dashboard_blog_referral_tutorial_modal">
                                <span class="dashboard-onboarding-tutorial-badge"><?= l('dashboard.tutorials.blog_referral.badge') ?></span>
                                <strong><?= l('dashboard.tutorials.blog_referral.title') ?></strong>
                                <span><?= l('dashboard.tutorials.blog_referral.text') ?></span>
                                <span class="dashboard-onboarding-tutorial-action">
                                    <?= l('dashboard.tutorials.blog_referral.button') ?>
                                    <i class="fas fa-arrow-right"></i>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dashboard-growth-hero-aside dashboard-tour-target" id="dashboard_tour_step_growth">
                <div class="dashboard-growth-aside-stack">
                    <div class="dashboard-growth-aside-intro">
                        <?= l('dashboard.growth.intro') ?>
                    </div>
                    <div class="dashboard-growth-aside-row">
                        <span><?= l('dashboard.growth.outbound_clicks_label') ?></span>
                        <strong id="dashboard_growth_signal_30d">0</strong>
                    </div>
                    <div class="dashboard-growth-aside-row">
                        <span><?= l('dashboard.growth.ai_status_label') ?></span>
                        <strong id="dashboard_growth_stage_label"><?= l('dashboard.dynamic.stage.building.label') ?></strong>
                    </div>
                    <div class="dashboard-growth-aside-row">
                        <span><?= l('dashboard.growth.next_unlock_label') ?></span>
                        <strong id="dashboard_next_unlock"><?= l('dashboard.growth.remaining_label') ?> 15</strong>
                    </div>
                    <div class="dashboard-growth-aside-row">
                        <span><?= l('dashboard.growth.blog_webshop_label') ?></span>
                        <strong id="dashboard_blog_signal_30d">0</strong>
                    </div>

                    <div class="dashboard-growth-aside-progress">
                        <div class="dashboard-growth-aside-progress-card">
                            <div class="dashboard-growth-aside-progress-top">
                                <div class="dashboard-growth-aside-progress-label"><?= l('dashboard.growth.active_threshold_label') ?></div>
                                <div class="dashboard-growth-aside-progress-value">
                                    <span><?= l('dashboard.growth.remaining_label') ?></span>
                                    <strong id="dashboard_active_remaining">15</strong>
                                </div>
                            </div>
                            <div class="dashboard-growth-progress-track">
                                <div class="dashboard-growth-progress-fill dashboard-growth-progress-fill--teal" id="dashboard_active_progress_fill"></div>
                            </div>
                            <div class="dashboard-growth-aside-progress-copy" id="dashboard_active_progress_copy">
                                <?= l('dashboard.growth.active_progress_default') ?>
                            </div>
                        </div>

                        <div class="dashboard-growth-aside-progress-card">
                            <div class="dashboard-growth-aside-progress-top">
                                <div class="dashboard-growth-aside-progress-label"><?= l('dashboard.growth.vip_threshold_label') ?></div>
                                <div class="dashboard-growth-aside-progress-value">
                                    <span><?= l('dashboard.growth.remaining_label') ?></span>
                                    <strong id="dashboard_vip_remaining">50</strong>
                                </div>
                            </div>
                            <div class="dashboard-growth-progress-track">
                                <div class="dashboard-growth-progress-fill dashboard-growth-progress-fill--gold" id="dashboard_vip_progress_fill"></div>
                            </div>
                            <div class="dashboard-growth-aside-progress-copy" id="dashboard_vip_progress_copy">
                                <?= l('dashboard.growth.vip_progress_default') ?>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-growth-aside-note" id="dashboard_growth_note">
                        <?= l('dashboard.growth.note_default') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-workbench dashboard-growth-card mb-4 dashboard-tour-target" id="dashboard_tour_step_workbench">
            <div class="dashboard-workbench-header">
                <div>
                    <div class="dashboard-growth-eyebrow"><?= l('dashboard.workbench.eyebrow') ?></div>
                    <h2 class="dashboard-workbench-title"><?= l('dashboard.workbench.title') ?></h2>
                    <p class="dashboard-workbench-copy"><?= l('dashboard.workbench.description') ?></p>
                </div>
            </div>

            <div class="dashboard-workbench-grid">
                <div class="dashboard-workbench-card dashboard-workbench-card--green">
                    <div class="dashboard-growth-pill dashboard-growth-pill--green"><?= l('dashboard.workbench.content_pill') ?></div>
                    <h3><?= l('dashboard.workbench.content_title') ?></h3>
                    <p id="dashboard_best_content_summary"><?= l('dashboard.workbench.content_loading') ?></p>
                    <div class="dashboard-workbench-meta">
                        <div>
                            <span><?= l('dashboard.workbench.top_blog') ?></span>
                            <strong id="dashboard_top_blog_title">—</strong>
                        </div>
                        <div>
                            <span><?= l('dashboard.workbench.top_app') ?></span>
                            <strong id="dashboard_top_app_title">—</strong>
                        </div>
                    </div>
                </div>

                <div class="dashboard-workbench-card dashboard-workbench-card--blue">
                    <div class="dashboard-growth-pill dashboard-growth-pill--blue"><?= l('dashboard.workbench.conversion_pill') ?></div>
                    <h3><?= l('dashboard.workbench.conversion_title') ?></h3>
                    <p id="dashboard_conversion_summary"><?= l('dashboard.workbench.conversion_loading') ?></p>
                    <div class="dashboard-workbench-meta">
                        <div>
                            <span><?= l('dashboard.workbench.top_country') ?></span>
                            <strong id="dashboard_top_country_label">—</strong>
                        </div>
                        <div>
                            <span><?= l('dashboard.workbench.top_source') ?></span>
                            <strong id="dashboard_top_source_label">—</strong>
                        </div>
                    </div>
                </div>

                <div class="dashboard-workbench-card dashboard-workbench-card--gold dashboard-tour-target" id="dashboard_tour_step_support">
                    <div class="dashboard-growth-pill dashboard-growth-pill--gold"><?= l('dashboard.workbench.support_pill') ?></div>
                    <h3><?= l('dashboard.workbench.support_title') ?></h3>
                    <p id="dashboard_support_summary_text"><?= l('dashboard.workbench.support_loading') ?></p>
                    <div class="dashboard-workbench-meta">
                        <div>
                            <span><?= l('dashboard.workbench.open_conversations') ?></span>
                            <strong id="dashboard_support_open_total">0</strong>
                        </div>
                        <div>
                            <span><?= l('dashboard.workbench.unread_admin_replies') ?></span>
                            <strong id="dashboard_support_unread_total">0</strong>
                        </div>
                    </div>
                    <div class="dashboard-workbench-actions">
                        <a href="<?= url('feedback-tickets') ?>" class="btn btn-primary" id="dashboard_support_primary_cta"><?= l('dashboard.workbench.support_primary_cta') ?></a>
                        <a href="<?= url('feedback-tickets') ?>" class="btn btn-outline-light" id="dashboard_support_secondary_cta"><?= l('dashboard.workbench.support_secondary_cta') ?></a>
                    </div>
                </div>
            </div>

            <div class="dashboard-workbench-note">
                <?= l('dashboard.workbench.note') ?>
            </div>
        </div>

        <div class="dashboard-growth-kpi-grid mb-4">
            <div class="dashboard-growth-kpi">
                <div class="dashboard-growth-kpi-label"><?= l('dashboard.kpi.biolink_visits') ?></div>
                <div class="dashboard-growth-kpi-value" id="dashboard_biolink_visits_30d">0</div>
                <div class="dashboard-growth-kpi-delta" id="dashboard_biolink_visits_delta">—</div>
            </div>
            <div class="dashboard-growth-kpi">
                <div class="dashboard-growth-kpi-label"><?= l('dashboard.kpi.qualified_clicks') ?></div>
                <div class="dashboard-growth-kpi-value" id="dashboard_qualified_clicks_30d">0</div>
                <div class="dashboard-growth-kpi-delta" id="dashboard_qualified_clicks_delta">—</div>
            </div>
            <div class="dashboard-growth-kpi">
                <div class="dashboard-growth-kpi-label"><?= l('dashboard.kpi.registration_clicks') ?></div>
                <div class="dashboard-growth-kpi-value" id="dashboard_forever_registration_clicks_30d">0</div>
                <div class="dashboard-growth-kpi-delta" id="dashboard_registration_clicks_delta">—</div>
            </div>
            <div class="dashboard-growth-kpi">
                <div class="dashboard-growth-kpi-label"><?= l('dashboard.kpi.funnel_leads') ?></div>
                <div class="dashboard-growth-kpi-value" id="dashboard_funnel_leads_30d">0</div>
                <div class="dashboard-growth-kpi-delta" id="dashboard_funnel_conversion_rate_30d">—</div>
            </div>
        </div>

        <div class="dashboard-main-grid">
            <div class="dashboard-focus-grid mb-4">
                <div class="dashboard-growth-card dashboard-side-card dashboard-side-card--green">
                    <div class="dashboard-growth-eyebrow"><?= l('dashboard.focus.strengths_eyebrow') ?></div>
                    <h3 class="dashboard-side-card-title"><?= l('dashboard.focus.strengths_title') ?></h3>
                    <div class="dashboard-side-card-list" id="dashboard_strengths_list"><?= l('dashboard.loading') ?></div>
                </div>

                <div class="dashboard-growth-card dashboard-side-card dashboard-side-card--gold">
                    <div class="dashboard-growth-eyebrow"><?= l('dashboard.focus.blockers_eyebrow') ?></div>
                    <h3 class="dashboard-side-card-title"><?= l('dashboard.focus.blockers_title') ?></h3>
                    <div class="dashboard-side-card-list" id="dashboard_blockers_list"><?= l('dashboard.loading') ?></div>
                </div>

                <div class="dashboard-growth-card dashboard-side-card dashboard-side-card--blue dashboard-tour-target" id="dashboard_tour_step_next_action">
                    <div class="dashboard-growth-eyebrow"><?= l('dashboard.focus.next_action_eyebrow') ?></div>
                    <h3 class="dashboard-side-card-title" id="dashboard_next_action_title"><?= l('dashboard.focus.next_action_loading_title') ?></h3>
                    <p class="dashboard-side-card-copy" id="dashboard_next_action_description"><?= l('dashboard.focus.next_action_loading_description') ?></p>
                    <div class="d-flex flex-wrap" style="gap:.65rem;">
                        <a href="<?= url('links?type=biolink') ?>" class="btn btn-primary dashboard-growth-button" id="dashboard_next_action_cta"><?= l('dashboard.hero.primary_cta') ?></a>
                        <a href="<?= url('feedback-tickets') ?>" class="btn btn-outline-light dashboard-growth-button"><?= l('dashboard.focus.next_action_support_cta') ?></a>
                    </div>
                </div>
            </div>

            <div class="dashboard-growth-card dashboard-signal-chart-card h-100 dashboard-tour-target" id="dashboard_tour_step_chart">
                <div class="dashboard-chart-card-header">
                    <div>
                        <div class="dashboard-growth-eyebrow"><?= l('dashboard.chart.eyebrow') ?></div>
                        <h2 class="dashboard-workbench-title mb-2"><?= l('dashboard.chart.title') ?></h2>
                        <p class="dashboard-workbench-copy mb-0"><?= l('dashboard.chart.description') ?></p>
                    </div>
                    <div class="dashboard-chart-legend">
                        <span><i class="fas fa-circle" style="color:#49e3cf"></i> <?= l('dashboard.chart.legend_app') ?></span>
                        <span><i class="fas fa-circle" style="color:#5bb6ff"></i> <?= l('dashboard.chart.legend_blog') ?></span>
                        <span><i class="fas fa-circle" style="color:#ffd166"></i> <?= l('dashboard.chart.legend_registrations') ?></span>
                        <span><i class="fas fa-circle" style="color:#8b5cf6"></i> <?= l('dashboard.chart.legend_leads') ?></span>
                    </div>
                </div>
                <div class="chart-container d-flex align-items-center justify-content-center" id="dashboard_signal_chart_loading">
                    <span class="spinner-border spinner-border-lg" role="status"></span>
                </div>
                <div class="chart-container d-none" id="dashboard_signal_chart_container">
                    <canvas id="dashboard_signal_chart"></canvas>
                </div>
                <div id="dashboard_signal_chart_no_data" class="d-none">
                    <?= include_view(THEME_PATH . 'views/partials/no_chart_data.php', ['has_wrapper' => false]); ?>
                </div>
            </div>
        </div>

        <div class="dashboard-lists-grid">
            <div>
                <div class="dashboard-growth-card dashboard-list-card">
                    <div class="dashboard-growth-eyebrow"><?= l('dashboard.lists.qualified_eyebrow') ?></div>
                    <h3 class="dashboard-side-card-title"><?= l('dashboard.lists.qualified_title') ?></h3>
                    <div class="dashboard-workbench-copy mb-3"><?= l('dashboard.lists.qualified_description') ?></div>
                    <div id="dashboard_top_forever_pages_30d" class="small text-muted"><?= l('dashboard.loading') ?></div>
                </div>
            </div>
            <div>
                <div class="dashboard-growth-card dashboard-list-card">
                    <div class="dashboard-growth-eyebrow"><?= l('dashboard.lists.sources_eyebrow') ?></div>
                    <h3 class="dashboard-side-card-title"><?= l('dashboard.lists.sources_title') ?></h3>
                    <div id="dashboard_top_shop_sources_30d" class="small text-muted"><?= l('dashboard.loading') ?></div>
                    <div id="dashboard_top_shop_sources_30d_toggle" class="pt-2"></div>
                </div>
            </div>
            <div>
                <div class="dashboard-growth-card dashboard-list-card">
                    <div class="dashboard-growth-eyebrow"><?= l('dashboard.lists.market_eyebrow') ?></div>
                    <h3 class="dashboard-side-card-title"><?= l('dashboard.lists.market_title') ?></h3>
                    <div id="dashboard_top_countries_30d" class="small text-muted"><?= l('dashboard.loading') ?></div>
                    <div id="dashboard_top_countries_30d_toggle" class="pt-2"></div>
                </div>
            </div>
        </div>

        <?php require THEME_PATH . 'views/partials/js_chart_defaults.php' ?>
    </div>
</div>

<div class="modal fade" id="dashboard_how_fcc_works_modal" tabindex="-1" role="dialog" aria-labelledby="dashboard_how_fcc_works_modal_title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="dashboard_how_fcc_works_modal_title"><?= l('dashboard.onboarding_modal.title') ?></h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <div class="dashboard-onboarding-modal-step">
                            <strong><?= l('dashboard.onboarding_modal.step_1_title') ?></strong>
                            <p><?= l('dashboard.onboarding_modal.step_1_text') ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="dashboard-onboarding-modal-step">
                            <strong><?= l('dashboard.onboarding_modal.step_2_title') ?></strong>
                            <p><?= l('dashboard.onboarding_modal.step_2_text') ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="dashboard-onboarding-modal-step">
                            <strong><?= l('dashboard.onboarding_modal.step_3_title') ?></strong>
                            <p><?= l('dashboard.onboarding_modal.step_3_text') ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="dashboard-onboarding-modal-step">
                            <strong><?= l('dashboard.onboarding_modal.step_4_title') ?></strong>
                            <p><?= l('dashboard.onboarding_modal.step_4_text') ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="dashboard-onboarding-modal-step">
                            <strong><?= l('dashboard.onboarding_modal.step_5_title') ?></strong>
                            <p><?= l('dashboard.onboarding_modal.step_5_text') ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="dashboard-onboarding-modal-step">
                            <strong><?= l('dashboard.onboarding_modal.step_6_title') ?></strong>
                            <p><?= l('dashboard.onboarding_modal.step_6_text') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-light" data-dismiss="modal"><?= l('dashboard.onboarding_modal.secondary') ?></button>
                <button type="button" class="btn btn-primary" id="dashboard_modal_start_tour"><?= l('dashboard.onboarding_modal.primary') ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dashboard_blog_referral_tutorial_modal" tabindex="-1" role="dialog" aria-labelledby="dashboard_blog_referral_tutorial_modal_title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="dashboard_blog_referral_tutorial_modal_title"><?= l('dashboard.tutorials.blog_referral.modal_title') ?></h5>
                    <div class="small text-muted mt-1"><?= l('dashboard.tutorials.blog_referral.modal_subtitle') ?></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="dashboard-onboarding-modal-callout">
                    <div class="dashboard-onboarding-modal-callout-copy">
                        <span class="dashboard-onboarding-tutorial-badge mb-0"><?= l('dashboard.tutorials.blog_referral.badge') ?></span>
                        <p class="dashboard-onboarding-modal-callout-text"><?= l('dashboard.tutorials.blog_referral.modal_callout') ?></p>
                    </div>
                    <button type="button" class="btn btn-primary" id="dashboard_start_blog_referral_tour_top">
                        <?= l('dashboard.tutorials.blog_referral.modal_primary') ?>
                    </button>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <div class="dashboard-onboarding-modal-step">
                            <strong><?= l('dashboard.tutorials.blog_referral.step_1_title') ?></strong>
                            <p><?= l('dashboard.tutorials.blog_referral.step_1_text') ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="dashboard-onboarding-modal-step">
                            <strong><?= l('dashboard.tutorials.blog_referral.step_2_title') ?></strong>
                            <p><?= l('dashboard.tutorials.blog_referral.step_2_text') ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <div class="dashboard-onboarding-modal-step">
                            <strong><?= l('dashboard.tutorials.blog_referral.step_3_title') ?></strong>
                            <p><?= l('dashboard.tutorials.blog_referral.step_3_text') ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="dashboard-onboarding-modal-step">
                            <strong><?= l('dashboard.tutorials.blog_referral.step_4_title') ?></strong>
                            <p><?= l('dashboard.tutorials.blog_referral.step_4_text') ?></p>
                        </div>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="dashboard-onboarding-modal-step dashboard-onboarding-modal-step--highlight">
                            <strong><?= l('dashboard.tutorials.blog_referral.step_5_title') ?></strong>
                            <p><?= l('dashboard.tutorials.blog_referral.step_5_text') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-light" data-dismiss="modal"><?= l('global.close') ?></button>
                <button type="button" class="btn btn-primary" id="dashboard_start_blog_referral_tour_bottom">
                    <?= l('dashboard.tutorials.blog_referral.modal_primary') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-tour-backdrop" id="dashboard_tour_backdrop">
    <div class="dashboard-tour-backdrop-segment" data-segment="top"></div>
    <div class="dashboard-tour-backdrop-segment" data-segment="left"></div>
    <div class="dashboard-tour-backdrop-segment" data-segment="right"></div>
    <div class="dashboard-tour-backdrop-segment" data-segment="bottom"></div>
</div>
<div class="dashboard-tour-popover" id="dashboard_tour_popover" aria-live="polite">
    <div class="dashboard-tour-progress" id="dashboard_tour_progress">1 / 6</div>
    <div class="dashboard-tour-title" id="dashboard_tour_title"></div>
    <div class="dashboard-tour-text" id="dashboard_tour_text"></div>
    <div class="dashboard-tour-actions">
        <button type="button" class="btn btn-link text-muted px-0" id="dashboard_tour_skip"><?= l('dashboard.tour.skip') ?></button>
        <div class="dashboard-tour-actions-main">
            <button type="button" class="btn btn-outline-light" id="dashboard_tour_prev"><?= l('dashboard.tour.prev') ?></button>
            <button type="button" class="btn btn-primary" id="dashboard_tour_next"><?= l('dashboard.tour.next') ?></button>
        </div>
    </div>
</div>

<!-- Custom code: FC-2026-03-30: dashboard geo breakdown modal -->
<div class="modal fade" id="dashboard_geo_breakdown_modal" tabindex="-1" role="dialog" aria-labelledby="dashboard_geo_breakdown_modal_title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dashboard_geo_breakdown_modal_title"><?= l('dashboard.forever_analytics.breakdown_modal_default_title') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-lg-6 mb-4 mb-lg-0">
                        <div class="font-weight-bold mb-3"><?= l('dashboard.forever_analytics.breakdown_countries') ?></div>
                        <div id="dashboard_geo_breakdown_countries" class="small text-muted"></div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="dashboard-geo-filter-bar">
                            <div class="font-weight-bold"><?= l('dashboard.forever_analytics.breakdown_cities') ?></div>
                            <div id="dashboard_geo_breakdown_city_filter"></div>
                        </div>
                        <div id="dashboard_geo_breakdown_cities" class="small text-muted"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-30 -->

<?php ob_start() ?>
    <script>
    'use strict';

        const dashboard_country_names = <?= json_encode(get_countries_array()) ?>;
        const dashboard_country_flags_base_url = <?= json_encode(ASSETS_FULL_URL . 'images/countries/') ?>;
        const dashboard_onboarding_storage_key = 'fcc_dashboard_onboarding_seen_v1';
        const dashboard_tour_storage_key = 'fcc_dashboard_tour_completed_v1';
        const dashboard_blog_referral_tour_storage_key = 'fcc_blog_referral_tour_v1';
        const dashboard_forever_products_url = <?= json_encode($dashboard_forever_products_url) ?>;
        const dashboard_should_auto_open_onboarding = <?= json_encode((bool) ($data->should_auto_open_dashboard_onboarding ?? false)) ?>;
        const dashboard_mark_onboarding_seen_url = <?= json_encode(url('dashboard/mark_onboarding_seen')) ?>;

        const funnel_open_mode_labels = {
            popup: <?= json_encode(l('biolink_lead_funnel.open_mode_popup')) ?>,
            page: <?= json_encode(l('biolink_lead_funnel.open_mode_page')) ?>,
        };

        const funnel_thank_you_labels = {
            message: <?= json_encode(l('biolink_lead_funnel.thank_you_type_message')) ?>,
            external_url: <?= json_encode(l('biolink_lead_funnel.thank_you_type_external_url')) ?>,
            biolink_redirect: <?= json_encode(l('biolink_lead_funnel.thank_you_type_biolink_redirect')) ?>,
            file_download: <?= json_encode(l('biolink_lead_funnel.thank_you_type_file_download')) ?>,
        };

        const dashboard_i18n = {
            source_direct: <?= json_encode(l('dashboard.forever_analytics.source_direct')) ?>,
            fetch_error: <?= json_encode(l('dashboard.dynamic.fetch_error')) ?>,
            hero_fallback_title: <?= json_encode(l('dashboard.dynamic.hero_fallback_title')) ?>,
            hero_primary_cta: <?= json_encode(l('dashboard.hero.primary_cta')) ?>,
            support_primary_cta: <?= json_encode(l('dashboard.workbench.support_primary_cta')) ?>,
            support_secondary_cta: <?= json_encode(l('dashboard.workbench.support_secondary_cta')) ?>,
            next_action_support_cta: <?= json_encode(l('dashboard.focus.next_action_support_cta')) ?>,
            no_signal: <?= json_encode(l('dashboard.dynamic.no_signal')) ?>,
            no_blog_signal: <?= json_encode(l('dashboard.dynamic.no_blog_signal')) ?>,
            no_app_signal: <?= json_encode(l('dashboard.dynamic.no_app_signal')) ?>,
            conversion_rate_suffix: <?= json_encode(l('dashboard.kpi.conversion_suffix')) ?>,
            workbench: {
                top_blog: <?= json_encode(l('dashboard.workbench.top_blog')) ?>,
                top_app: <?= json_encode(l('dashboard.workbench.top_app')) ?>,
                top_country: <?= json_encode(l('dashboard.workbench.top_country')) ?>,
                top_source: <?= json_encode(l('dashboard.workbench.top_source')) ?>,
            },
            stage: {
                building: {
                    badge: <?= json_encode(l('dashboard.dynamic.stage.building.badge')) ?>,
                    label: <?= json_encode(l('dashboard.dynamic.stage.building.label')) ?>,
                    unlock: <?= json_encode(l('dashboard.dynamic.stage.building.unlock')) ?>,
                },
                active: {
                    badge: <?= json_encode(l('dashboard.dynamic.stage.active.badge')) ?>,
                    label: <?= json_encode(l('dashboard.dynamic.stage.active.label')) ?>,
                    unlock: <?= json_encode(l('dashboard.dynamic.stage.active.unlock')) ?>,
                },
                vip: {
                    badge: <?= json_encode(l('dashboard.dynamic.stage.vip.badge')) ?>,
                    label: <?= json_encode(l('dashboard.dynamic.stage.vip.label')) ?>,
                    unlock: <?= json_encode(l('dashboard.dynamic.stage.vip.unlock')) ?>,
                },
            },
            templates: {
                active_progress_remaining: <?= json_encode(l('dashboard.dynamic.active_progress_remaining')) ?>,
                active_progress_unlocked: <?= json_encode(l('dashboard.dynamic.active_progress_unlocked')) ?>,
                vip_progress_remaining: <?= json_encode(l('dashboard.dynamic.vip_progress_remaining')) ?>,
                vip_progress_unlocked: <?= json_encode(l('dashboard.dynamic.vip_progress_unlocked')) ?>,
                growth_note_webinars: <?= json_encode(l('dashboard.dynamic.growth_note_webinars')) ?>,
                growth_note_default: <?= json_encode(l('dashboard.growth.note_default')) ?>,
                best_content_blog: <?= json_encode(l('dashboard.dynamic.best_content_blog')) ?>,
                best_content_app: <?= json_encode(l('dashboard.dynamic.best_content_app')) ?>,
                best_content_none: <?= json_encode(l('dashboard.dynamic.best_content_none')) ?>,
                conversion_registration_gap: <?= json_encode(l('dashboard.dynamic.conversion_registration_gap')) ?>,
                conversion_leads: <?= json_encode(l('dashboard.dynamic.conversion_leads')) ?>,
                conversion_default: <?= json_encode(l('dashboard.dynamic.conversion_default')) ?>,
                contacts_breakdown_both: <?= json_encode(l('dashboard.dynamic.contacts_breakdown_both')) ?>,
                contacts_breakdown_funnel_only: <?= json_encode(l('dashboard.dynamic.contacts_breakdown_funnel_only')) ?>,
                contacts_breakdown_chat_only: <?= json_encode(l('dashboard.dynamic.contacts_breakdown_chat_only')) ?>,
                conversion_best_opening: <?= json_encode(l('dashboard.dynamic.conversion_best_opening')) ?>,
                conversion_best_thank_you: <?= json_encode(l('dashboard.dynamic.conversion_best_thank_you')) ?>,
                strength_blog: <?= json_encode(l('dashboard.dynamic.strength_blog')) ?>,
                strength_app: <?= json_encode(l('dashboard.dynamic.strength_app')) ?>,
                strength_contacts: <?= json_encode(l('dashboard.dynamic.strength_contacts')) ?>,
                strength_source: <?= json_encode(l('dashboard.dynamic.strength_source')) ?>,
                strengths_empty: <?= json_encode(l('dashboard.focus.strengths.empty')) ?>,
                blocker_active_gap: <?= json_encode(l('dashboard.dynamic.blocker_active_gap')) ?>,
                blocker_no_registrations: <?= json_encode(l('dashboard.dynamic.blocker_no_registrations')) ?>,
                blocker_funnel_no_leads: <?= json_encode(l('dashboard.dynamic.blocker_funnel_no_leads')) ?>,
                blocker_repeated_issue: <?= json_encode(l('dashboard.dynamic.blocker_repeated_issue')) ?>,
                blockers_empty: <?= json_encode(l('dashboard.focus.blockers.empty')) ?>,
                next_action_fallback_title: <?= json_encode(l('dashboard.dynamic.next_action_fallback_title')) ?>,
                next_action_fallback_description: <?= json_encode(l('dashboard.dynamic.next_action_fallback_description')) ?>,
                support_repeated_issue: <?= json_encode(l('dashboard.dynamic.support_repeated_issue')) ?>,
                support_unread: <?= json_encode(l('dashboard.dynamic.support_unread')) ?>,
                support_default: <?= json_encode(l('dashboard.dynamic.support_default')) ?>,
                support_secondary_webinar_topics: <?= json_encode(l('dashboard.dynamic.support_secondary_webinar_topics')) ?>,
            },
            actions: {
                support_primary_admin_replies: <?= json_encode(l('dashboard.dynamic.support_primary_admin_replies')) ?>,
            },
            channels: {
                app_badge: <?= json_encode(l('dashboard.dynamic.channel_app_badge')) ?>,
                app_title: <?= json_encode(l('dashboard.dynamic.channel_app_title')) ?>,
                app_copy: <?= json_encode(l('dashboard.dynamic.channel_app_copy')) ?>,
                blog_badge: <?= json_encode(l('dashboard.dynamic.channel_blog_badge')) ?>,
                blog_title: <?= json_encode(l('dashboard.dynamic.channel_blog_title')) ?>,
                blog_copy: <?= json_encode(l('dashboard.dynamic.channel_blog_copy')) ?>,
            },
            chart: {
                app: <?= json_encode(l('dashboard.chart.legend_app')) ?>,
                blog: <?= json_encode(l('dashboard.chart.legend_blog')) ?>,
                registrations: <?= json_encode(l('dashboard.chart.legend_registrations')) ?>,
                leads: <?= json_encode(l('dashboard.chart.legend_leads')) ?>,
            }
        };

        const dashboard_format = (template, replacements = {}) => {
            let output = String(template ?? '');

            Object.entries(replacements).forEach(([key, value]) => {
                output = output.split(`{${key}}`).join(String(value));
            });

            return output;
        };

        const dashboard_compact_state = {};
        let dashboard_signal_chart_instance = null;
        let dashboard_tour_initialized = false;
        let dashboard_tour_active_step = -1;
        let dashboard_tour_active_position = -1;
        let dashboard_tour_current_target = null;
        let dashboard_tour_sequence = [];
        let dashboard_onboarding_seen_synced = <?= json_encode((bool) ($data->dashboard_onboarding_seen ?? false)) ?>;

        const dashboard_set_tour_mode = isActive => {
            document.body.classList.toggle('fcc-tour-mode', !!isActive);

            if(typeof window.CustomEvent === 'function') {
                window.dispatchEvent(new CustomEvent('fcc:tutorial:state', {
                    detail: {active: !!isActive}
                }));
            }
        };

        const dashboard_tour_steps = [
            {
                selector: '#dashboard_tour_step_page_guide',
                title: <?= json_encode(l('dashboard.tour.step_guide_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_guide_text')) ?>,
            },
            {
                selector: '#fcc_dashboard_tour_sidebar',
                title: <?= json_encode(l('dashboard.tour.step_sidebar_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_sidebar_text')) ?>,
                placement: 'right',
                disable_scroll: true,
            },
            {
                selector: '#fcc_dashboard_tour_sidebar_dashboard',
                title: <?= json_encode(l('dashboard.tour.step_menu_dashboard_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_menu_dashboard_text')) ?>,
            },
            {
                selector: '#fcc_dashboard_tour_sidebar_apps',
                title: <?= json_encode(l('dashboard.tour.step_menu_apps_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_menu_apps_text')) ?>,
            },
            {
                selector: '#fcc_dashboard_tour_sidebar_ai_plan',
                title: <?= json_encode(l('dashboard.tour.step_menu_ai_plan_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_menu_ai_plan_text')) ?>,
            },
            {
                selector: '#fcc_dashboard_tour_sidebar_results',
                title: <?= json_encode(l('dashboard.tour.step_menu_results_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_menu_results_text')) ?>,
            },
            {
                selector: '#fcc_dashboard_tour_sidebar_education',
                title: <?= json_encode(l('dashboard.tour.step_menu_education_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_menu_education_text')) ?>,
            },
            {
                selector: '#fcc_dashboard_tour_sidebar_products',
                title: <?= json_encode(l('dashboard.tour.step_menu_products_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_menu_products_text')) ?>,
            },
            {
                selector: '#dashboard_tour_step_hero',
                title: <?= json_encode(l('dashboard.tour.step_hero_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_hero_text')) ?>,
            },
            {
                selector: '#dashboard_tour_step_growth',
                title: <?= json_encode(l('dashboard.tour.step_growth_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_growth_text')) ?>,
            },
            {
                selector: '#dashboard_tour_step_tutorials',
                title: <?= json_encode(l('dashboard.tour.step_tutorials_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_tutorials_text')) ?>,
            },
            {
                selector: '#dashboard_tour_step_workbench',
                title: <?= json_encode(l('dashboard.tour.step_workbench_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_workbench_text')) ?>,
            },
            {
                selector: '#dashboard_tour_step_chart',
                title: <?= json_encode(l('dashboard.tour.step_chart_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_chart_text')) ?>,
            },
            {
                selector: '#dashboard_tour_step_next_action',
                title: <?= json_encode(l('dashboard.tour.step_next_action_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_next_action_text')) ?>,
            },
            {
                selector: '#dashboard_tour_step_support',
                title: <?= json_encode(l('dashboard.tour.step_support_title')) ?>,
                text: <?= json_encode(l('dashboard.tour.step_support_text')) ?>,
            },
        ];

        const dashboard_open_how_it_works_modal = () => {
            if(window.jQuery) {
                window.jQuery('#dashboard_how_fcc_works_modal').modal('show');
            }
        };

        const dashboard_close_how_it_works_modal = () => {
            if(window.jQuery) {
                window.jQuery('#dashboard_how_fcc_works_modal').modal('hide');
            }
        };

        const dashboard_close_blog_referral_modal = () => {
            if(window.jQuery) {
                window.jQuery('#dashboard_blog_referral_tutorial_modal').modal('hide');
            }
        };

        const dashboard_start_blog_referral_tour = () => {
            try {
                localStorage.setItem(dashboard_blog_referral_tour_storage_key, JSON.stringify({
                    flow: 'blog_referral',
                    stage: 'category',
                    started_at: Date.now()
                }));
            } catch(error) {
                /* Ignore storage failures and continue to the page. */
            }

            dashboard_close_blog_referral_modal();
            window.location.href = dashboard_forever_products_url;
        };

        const dashboard_set_onboarding_storage = () => {
            try {
                localStorage.setItem(dashboard_onboarding_storage_key, '1');
            } catch(error) {
                /* Ignore storage failures. */
            }
        };

        const dashboard_has_onboarding_storage = () => {
            try {
                return !!localStorage.getItem(dashboard_onboarding_storage_key);
            } catch(error) {
                return false;
            }
        };

        const dashboard_mark_onboarding_seen = () => {
            dashboard_set_onboarding_storage();

            if(dashboard_onboarding_seen_synced) {
                return;
            }

            dashboard_onboarding_seen_synced = true;

            const payload = 'source=dashboard';

            if(navigator.sendBeacon) {
                try {
                    const beaconPayload = new Blob([payload], {type: 'application/x-www-form-urlencoded; charset=UTF-8'});
                    if(navigator.sendBeacon(dashboard_mark_onboarding_seen_url, beaconPayload)) {
                        return;
                    }
                } catch(error) {
                    /* Continue with fetch fallback. */
                }
            }

            fetch(dashboard_mark_onboarding_seen_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json'
                },
                body: payload
            }).catch(() => {});
        };

        const dashboard_clear_tour_highlight = () => {
            if(dashboard_tour_current_target) {
                dashboard_tour_current_target.classList.remove('dashboard-tour-active-target');
            }

            dashboard_tour_current_target = null;
        };

        const dashboard_update_tour_backdrop = (rect) => {
            const backdrop = document.getElementById('dashboard_tour_backdrop');
            if(!backdrop || !rect) {
                return;
            }

            const topSegment = backdrop.querySelector('[data-segment="top"]');
            const leftSegment = backdrop.querySelector('[data-segment="left"]');
            const rightSegment = backdrop.querySelector('[data-segment="right"]');
            const bottomSegment = backdrop.querySelector('[data-segment="bottom"]');

            if(!topSegment || !leftSegment || !rightSegment || !bottomSegment) {
                return;
            }

            const padding = 12;
            const top = Math.max(0, rect.top - padding);
            const left = Math.max(0, rect.left - padding);
            const right = Math.min(window.innerWidth, rect.right + padding);
            const bottom = Math.min(window.innerHeight, rect.bottom + padding);
            const spotlightHeight = Math.max(0, bottom - top);

            topSegment.style.top = '0px';
            topSegment.style.left = '0px';
            topSegment.style.width = '100vw';
            topSegment.style.height = `${top}px`;

            leftSegment.style.top = `${top}px`;
            leftSegment.style.left = '0px';
            leftSegment.style.width = `${left}px`;
            leftSegment.style.height = `${spotlightHeight}px`;

            rightSegment.style.top = `${top}px`;
            rightSegment.style.left = `${right}px`;
            rightSegment.style.width = `${Math.max(0, window.innerWidth - right)}px`;
            rightSegment.style.height = `${spotlightHeight}px`;

            bottomSegment.style.top = `${bottom}px`;
            bottomSegment.style.left = '0px';
            bottomSegment.style.width = '100vw';
            bottomSegment.style.height = `${Math.max(0, window.innerHeight - bottom)}px`;
        };

        const dashboard_place_tour_popover = () => {
            const popover = document.getElementById('dashboard_tour_popover');
            if(!popover || !dashboard_tour_current_target || !popover.classList.contains('is-visible')) {
                return;
            }

            const activeStep = typeof dashboard_tour_active_step !== 'undefined' && dashboard_tour_active_step >= 0
                ? dashboard_tour_steps[dashboard_tour_active_step]
                : null;
            const rect = dashboard_tour_current_target.getBoundingClientRect();
            const margin = 20;
            const popoverWidth = popover.offsetWidth || 360;
            const popoverHeight = popover.offsetHeight || 220;

            dashboard_update_tour_backdrop(rect);

            let top = rect.bottom + 16;
            let left = rect.left;

            if(activeStep && activeStep.placement === 'right') {
                const rightSpace = window.innerWidth - rect.right - margin;
                const leftSpace = rect.left - margin;

                top = Math.min(
                    Math.max(margin, rect.top + ((rect.height - popoverHeight) / 2)),
                    Math.max(margin, window.innerHeight - popoverHeight - margin)
                );

                if(rightSpace >= popoverWidth + 16) {
                    left = rect.right + 16;
                } else if(leftSpace >= popoverWidth + 16) {
                    left = rect.left - popoverWidth - 16;
                } else {
                    left = Math.min(
                        Math.max(margin, rect.right + 16),
                        Math.max(margin, window.innerWidth - popoverWidth - margin)
                    );
                }
            } else {
                if(top + popoverHeight > window.innerHeight - margin) {
                    top = Math.max(margin, rect.top - popoverHeight - 16);
                }

                if(left + popoverWidth > window.innerWidth - margin) {
                    left = window.innerWidth - popoverWidth - margin;
                }
                if(left < margin) {
                    left = margin;
                }
            }

            popover.style.top = `${top}px`;
            popover.style.left = `${left}px`;
        };

        const dashboard_end_tour = (completed = false) => {
            const backdrop = document.getElementById('dashboard_tour_backdrop');
            const popover = document.getElementById('dashboard_tour_popover');

            dashboard_clear_tour_highlight();
            dashboard_tour_active_step = -1;
            dashboard_tour_active_position = -1;
            dashboard_tour_sequence = [];
            dashboard_set_tour_mode(false);

            if(backdrop) {
                backdrop.classList.remove('is-visible');
            }

            if(popover) {
                popover.classList.remove('is-visible');
                popover.style.top = '';
                popover.style.left = '';
            }

            if(completed) {
                localStorage.setItem(dashboard_tour_storage_key, '1');
            }
        };

        const dashboard_resolve_tour_target = (step) => {
            if(!step || !step.selector) {
                return null;
            }

            const target = document.querySelector(step.selector);

            if(!target) {
                return null;
            }

            const targetRect = target.getBoundingClientRect();
            const targetStyle = window.getComputedStyle(target);

            if(
                (targetRect.width === 0 && targetRect.height === 0)
                || targetStyle.visibility === 'hidden'
                || targetStyle.display === 'none'
            ) {
                return null;
            }

            return target;
        };

        const dashboard_build_tour_sequence = () => dashboard_tour_steps.reduce((sequence, step, index) => {
            if(dashboard_resolve_tour_target(step)) {
                sequence.push(index);
            }

            return sequence;
        }, []);

        const dashboard_should_scroll_target = (step, target) => {
            if(!step || !target || step.disable_scroll) {
                return false;
            }

            const targetStyle = window.getComputedStyle(target);

            if(targetStyle.position === 'fixed' || targetStyle.position === 'sticky') {
                return false;
            }

            if(target.closest('.app-sidebar')) {
                return false;
            }

            return true;
        };

        const dashboard_render_tour_step = (position) => {
            const step_index = dashboard_tour_sequence[position];
            const step = typeof step_index !== 'undefined' ? dashboard_tour_steps[step_index] : null;
            const target = dashboard_resolve_tour_target(step);
            const popover = document.getElementById('dashboard_tour_popover');
            const backdrop = document.getElementById('dashboard_tour_backdrop');
            const title = document.getElementById('dashboard_tour_title');
            const text = document.getElementById('dashboard_tour_text');
            const progress = document.getElementById('dashboard_tour_progress');
            const prevButton = document.getElementById('dashboard_tour_prev');
            const nextButton = document.getElementById('dashboard_tour_next');

            if(!step || !target || !popover || !backdrop) {
                dashboard_end_tour(false);
                return;
            }

            dashboard_tour_active_step = step_index;
            dashboard_tour_active_position = position;
            dashboard_clear_tour_highlight();
            dashboard_tour_current_target = target;
            dashboard_tour_current_target.classList.add('dashboard-tour-active-target');

            if(dashboard_should_scroll_target(step, dashboard_tour_current_target)) {
                dashboard_tour_current_target.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'});
            }

            title.textContent = step.title;
            text.textContent = step.text;
            progress.textContent = `${position + 1} / ${dashboard_tour_sequence.length}`;
            prevButton.style.display = position === 0 ? 'none' : 'inline-flex';
            nextButton.textContent = position === dashboard_tour_sequence.length - 1 ? <?= json_encode(l('dashboard.tour.finish')) ?> : <?= json_encode(l('dashboard.tour.next')) ?>;

            backdrop.classList.add('is-visible');
            popover.classList.add('is-visible');

            setTimeout(dashboard_place_tour_popover, 140);
        };

        const dashboard_start_tour = () => {
            dashboard_mark_onboarding_seen();
            dashboard_close_how_it_works_modal();
            dashboard_tour_sequence = dashboard_build_tour_sequence();

            if(!dashboard_tour_sequence.length) {
                dashboard_end_tour(false);
                return;
            }

            dashboard_set_tour_mode(true);
            dashboard_render_tour_step(0);
        };

        const initDashboardOnboarding = () => {
            if(dashboard_tour_initialized) {
                return;
            }

            dashboard_tour_initialized = true;

            document.querySelectorAll('[data-dashboard-open-fcc-modal]').forEach(button => {
                button.addEventListener('click', dashboard_open_how_it_works_modal);
            });

            document.querySelectorAll('[data-dashboard-start-tour]').forEach(button => {
                button.addEventListener('click', dashboard_start_tour);
            });

            const modalStartButton = document.getElementById('dashboard_modal_start_tour');
            if(modalStartButton) {
                modalStartButton.addEventListener('click', dashboard_start_tour);
            }

            document.querySelectorAll('#dashboard_start_blog_referral_tour_top, #dashboard_start_blog_referral_tour_bottom').forEach(button => {
                button.addEventListener('click', dashboard_start_blog_referral_tour);
            });

            const skipButton = document.getElementById('dashboard_tour_skip');
            const prevButton = document.getElementById('dashboard_tour_prev');
            const nextButton = document.getElementById('dashboard_tour_next');

            if(skipButton) {
                skipButton.addEventListener('click', () => {
                    dashboard_mark_onboarding_seen();
                    dashboard_end_tour(false);
                });
            }

            if(prevButton) {
                prevButton.addEventListener('click', () => {
                    if(dashboard_tour_active_position > 0) {
                        dashboard_render_tour_step(dashboard_tour_active_position - 1);
                    }
                });
            }

            if(nextButton) {
                nextButton.addEventListener('click', () => {
                    if(dashboard_tour_active_position >= dashboard_tour_sequence.length - 1) {
                        dashboard_end_tour(true);
                        return;
                    }

                    dashboard_render_tour_step(dashboard_tour_active_position + 1);
                });
            }

            window.addEventListener('resize', dashboard_place_tour_popover);
            window.addEventListener('scroll', dashboard_place_tour_popover, {passive: true});

            const hasStoredOnboarding = dashboard_has_onboarding_storage();

            if(hasStoredOnboarding && !dashboard_onboarding_seen_synced) {
                dashboard_mark_onboarding_seen();
            }

            if(dashboard_should_auto_open_onboarding && !hasStoredOnboarding) {
                setTimeout(() => {
                    dashboard_mark_onboarding_seen();
                    dashboard_open_how_it_works_modal();
                }, 700);
            } else if(!hasStoredOnboarding) {
                dashboard_set_onboarding_storage();
            }
        };

        const render_dashboard_compact_list = (container_selector, toggle_selector, items_html, visible_limit = 5) => {
            const container = document.querySelector(container_selector);
            const toggle_container = document.querySelector(toggle_selector);

            if(!container || !toggle_container) {
                return;
            }

            if(!Array.isArray(items_html) || !items_html.length) {
                container.innerHTML = `<span class="text-muted"><?= l('global.no_data') ?></span>`;
                toggle_container.innerHTML = '';
                return;
            }

            if(dashboard_compact_state[container_selector] === undefined) {
                dashboard_compact_state[container_selector] = false;
            }

            const is_expanded = dashboard_compact_state[container_selector];
            const should_toggle = items_html.length > visible_limit;
            const visible_items = should_toggle && !is_expanded ? items_html.slice(0, visible_limit) : items_html;

            container.innerHTML = visible_items.join('');

            if(!should_toggle) {
                toggle_container.innerHTML = '';
                return;
            }

            toggle_container.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-secondary" data-dashboard-compact-toggle="${container_selector}">
                    ${is_expanded ? '<?= l('dashboard.compact_list.show_less') ?>' : '<?= l('global.view_more') ?>'}
                </button>
            `;

            const toggle_button = toggle_container.querySelector('[data-dashboard-compact-toggle]');
            if(toggle_button) {
                toggle_button.addEventListener('click', () => {
                    dashboard_compact_state[container_selector] = !dashboard_compact_state[container_selector];
                    render_dashboard_compact_list(container_selector, toggle_selector, items_html, visible_limit);
                });
            }
        };
    
        (async function fetch_statistics() {
            /* Custom code: FC-2026-03-05: pass demo query flag to ajax endpoint */
            const dashboard_query_params = new URLSearchParams(window.location.search);
            const dashboard_stats_url = dashboard_query_params.get('demo') === '1'
                ? `${window.location.origin}/dashboard/get_stats_ajax?demo=1`
                : `${window.location.origin}/dashboard/get_stats_ajax`;
            /* /Custom code: FC-2026-03-05 */

            const render_dashboard_fetch_error = (message = dashboard_i18n.fetch_error) => {
                const safeMessage = message || dashboard_i18n.fetch_error;
                const loadingTargets = [
                    '#dashboard_strengths_list',
                    '#dashboard_blockers_list',
                    '#dashboard_top_forever_pages_30d',
                    '#dashboard_top_shop_sources_30d',
                    '#dashboard_top_countries_30d'
                ];

                loadingTargets.forEach(selector => {
                    const element = document.querySelector(selector);
                    if(element) {
                        element.innerHTML = `<div class="dashboard-side-card-list-item is-warning">${safeMessage}</div>`;
                    }
                });

                const chartLoading = document.querySelector('#dashboard_signal_chart_loading');
                const chartContainer = document.querySelector('#dashboard_signal_chart_container');
                const chartNoData = document.querySelector('#dashboard_signal_chart_no_data');

                if(chartLoading) {
                    chartLoading.classList.add('d-none');
                    chartLoading.classList.remove('d-flex', 'align-items-center', 'justify-content-center');
                }

                if(chartContainer) {
                    chartContainer.classList.add('d-none');
                }

                if(chartNoData) {
                    chartNoData.classList.remove('d-none');
                }
            };

            try {
                /* Send request to server */
                let response = await fetch(dashboard_stats_url, {
                    method: 'get',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (error) {
                    render_dashboard_fetch_error();
                    return;
                }

                if(!response.ok) {
                    render_dashboard_fetch_error();
                    return;
                }

                if(!data || data.status == 'error') {
                    render_dashboard_fetch_error();
                    return;
                } else if(data.status == 'success') {

                /* update link_links_total */
                const link_links_total_element = document.querySelector('#link_links_total');
                if (link_links_total_element) {
                    link_links_total_element.innerHTML = data.details.link_links_total ? nr(data.details.link_links_total) : 0;
                }

                /* update file_links_total */
                const file_links_total_element = document.querySelector('#file_links_total');
                if (file_links_total_element) {
                    file_links_total_element.innerHTML = data.details.file_links_total ? nr(data.details.file_links_total) : 0;
                }

                /* update vcard_links_total */
                const vcard_links_total_element = document.querySelector('#vcard_links_total');
                if (vcard_links_total_element) {
                    vcard_links_total_element.innerHTML = data.details.vcard_links_total ? nr(data.details.vcard_links_total) : 0;
                }

                /* update biolink_links_total */
                const biolink_links_total_element = document.querySelector('#biolink_links_total');
                if (biolink_links_total_element) {
                    biolink_links_total_element.innerHTML = data.details.biolink_links_total ? nr(data.details.biolink_links_total) : 0;
                }

                /* update event_links_total */
                const event_links_total_element = document.querySelector('#event_links_total');
                if (event_links_total_element) {
                    event_links_total_element.innerHTML = data.details.event_links_total ? nr(data.details.event_links_total) : 0;
                }

                /* update static_links_total */
                const static_links_total_element = document.querySelector('#static_links_total');
                if (static_links_total_element) {
                    static_links_total_element.innerHTML = data.details.static_links_total ? nr(data.details.static_links_total) : 0;
                }

                const dashboard_value = (value, fallback = null) => {
                    return value === null || typeof value === 'undefined' ? fallback : value;
                };

                const dashboard_nested_value = (object, keys, fallback = null) => {
                    let current = object;

                    for(let i = 0; i < keys.length; i++) {
                        if(current === null || typeof current === 'undefined' || (typeof current !== 'object' && typeof current !== 'function')) {
                            return fallback;
                        }

                        current = current[keys[i]];
                    }

                    return dashboard_value(current, fallback);
                };

                const dashboard_nr = value => {
                    const safeValue = dashboard_value(value, 0);

                    if(typeof nr === 'function') {
                        return nr(safeValue);
                    }

                    const numericValue = Number(safeValue);

                    if(isFinite(numericValue)) {
                        return String(numericValue);
                    }

                    return '0';
                };

                const dashboard_source_label = source => {
                    const safeSource = String(dashboard_value(source, '') || '').trim();

                    if(safeSource === '' || safeSource === '(direct)') {
                        return dashboard_i18n.source_direct;
                    }

                    return safeSource;
                };

                const dashboard_forever_analytics = dashboard_value(data.details.dashboard_forever_analytics, {});
                const dashboard_funnel_analytics = dashboard_value(data.details.dashboard_funnel_analytics, {});
                const dashboard_support_summary = dashboard_value(data.details.dashboard_support_summary, {});

                const dashboard_to_active = Number(dashboard_value(dashboard_forever_analytics.to_active, 0));
                const dashboard_to_vip = Number(dashboard_value(dashboard_forever_analytics.to_vip, 0));
                const dashboard_webinar_total = Number(dashboard_value(dashboard_support_summary.webinar_total, 0));
                const dashboard_qualified_clicks_30d = Number(dashboard_value(dashboard_forever_analytics.qualified_clicks_30d, 0));
                const dashboard_registration_clicks_30d = Number(dashboard_value(dashboard_forever_analytics.forever_registration_clicks_30d, 0));
                const dashboard_blog_qualified_clicks_30d = Number(dashboard_value(dashboard_forever_analytics.blog_qualified_clicks_30d, 0));
                const dashboard_app_qualified_clicks_30d = Number(dashboard_value(dashboard_forever_analytics.app_qualified_clicks_30d, 0));
                const dashboard_growth_active_threshold = Number(dashboard_value(dashboard_forever_analytics.growth_active_threshold, 15));
                const dashboard_funnel_leads_30d = Number(dashboard_value(dashboard_funnel_analytics.funnel_leads_30d, dashboard_value(dashboard_funnel_analytics.leads_30d, 0)));
                const dashboard_ai_chat_leads_30d = Number(dashboard_value(dashboard_funnel_analytics.ai_chat_leads_30d, 0));
                const dashboard_contact_captures_30d = Number(dashboard_value(dashboard_funnel_analytics.contact_captures_30d, (dashboard_funnel_leads_30d + dashboard_ai_chat_leads_30d)));
                const dashboard_funnel_unique_clicks_30d = Number(dashboard_value(dashboard_funnel_analytics.unique_clicks_30d, 0));
                const dashboard_support_repeated_issue_detected = !!dashboard_value(dashboard_support_summary.repeated_issue_detected, false);
                const dashboard_support_unread_total = Number(dashboard_value(dashboard_support_summary.unread_total, 0));

                const set_text = (selector, value, fallback = '—') => {
                    const element = document.querySelector(selector);
                    if(element) {
                        element.innerText = dashboard_value(value, fallback);
                    }
                };

                const set_html = (selector, value, fallback = '—') => {
                    const element = document.querySelector(selector);
                    if(element) {
                        element.innerHTML = dashboard_value(value, fallback);
                    }
                };

                const set_metric = (selector, value, suffix = '') => {
                    const element = document.querySelector(selector);
                    if(element) {
                        element.innerText = `${dashboard_nr(dashboard_value(value, 0))}${suffix}`;
                    }
                };

                const set_progress = (selector, value) => {
                    const element = document.querySelector(selector);
                    if(element) {
                        const safe_value = Math.max(0, Math.min(100, Number(dashboard_value(value, 0))));
                        element.style.width = `${safe_value}%`;
                    }
                };

                const set_delta = (selector, value, suffix = '%') => {
                    const element = document.querySelector(selector);
                    if(!element) {
                        return;
                    }

                    const numeric_value = Number(dashboard_value(value, 0));
                    const sign = numeric_value > 0 ? '+' : '';
                    element.innerText = `${sign}${dashboard_nr(numeric_value)}${suffix}`;
                };

                const stage_map = {
                    building: {
                        badge: dashboard_i18n.stage.building.badge,
                        label: dashboard_i18n.stage.building.label,
                        unlock: dashboard_format(dashboard_i18n.stage.building.unlock, {count: dashboard_nr(dashboard_to_active)})
                    },
                    active: {
                        badge: dashboard_i18n.stage.active.badge,
                        label: dashboard_i18n.stage.active.label,
                        unlock: dashboard_format(dashboard_i18n.stage.active.unlock, {count: dashboard_nr(dashboard_to_vip)})
                    },
                    vip: {
                        badge: dashboard_i18n.stage.vip.badge,
                        label: dashboard_i18n.stage.vip.label,
                        unlock: dashboard_i18n.stage.vip.unlock
                    }
                };

                const selected_stage = stage_map[dashboard_forever_analytics.growth_stage] || stage_map.building;
                set_text('#dashboard_growth_stage_badge', selected_stage.badge);
                set_text('#dashboard_growth_stage_label', selected_stage.label);
                set_text('#dashboard_next_unlock', selected_stage.unlock);
                set_text('#dashboard_hero_title', dashboard_value(dashboard_forever_analytics.hero_title, dashboard_i18n.hero_fallback_title));
                set_text('#dashboard_hero_description', dashboard_value(dashboard_forever_analytics.hero_description, ''));
                set_metric('#dashboard_growth_signal_30d', dashboard_forever_analytics.qualified_clicks_30d);
                set_metric('#dashboard_blog_signal_30d', dashboard_forever_analytics.blog_qualified_clicks_30d);

                set_metric('#dashboard_biolink_visits_30d', dashboard_forever_analytics.biolink_visits_30d);
                set_metric('#dashboard_qualified_clicks_30d', dashboard_forever_analytics.qualified_clicks_30d);
                set_metric('#dashboard_forever_registration_clicks_30d', dashboard_forever_analytics.forever_registration_clicks_30d);
                set_metric('#dashboard_funnel_leads_30d', dashboard_contact_captures_30d);
                set_delta('#dashboard_biolink_visits_delta', dashboard_forever_analytics.biolink_visits_delta_percent);
                set_delta('#dashboard_qualified_clicks_delta', dashboard_forever_analytics.qualified_clicks_delta_percent);
                set_delta('#dashboard_registration_clicks_delta', dashboard_forever_analytics.registration_clicks_delta_percent);
                const contactsBreakdown = dashboard_contact_captures_30d <= 0
                    ? '—'
                    : (dashboard_funnel_leads_30d > 0 && dashboard_ai_chat_leads_30d > 0
                        ? dashboard_format(dashboard_i18n.templates.contacts_breakdown_both, {funnel: dashboard_nr(dashboard_funnel_leads_30d), chat: dashboard_nr(dashboard_ai_chat_leads_30d)})
                        : (dashboard_funnel_leads_30d > 0
                            ? dashboard_format(dashboard_i18n.templates.contacts_breakdown_funnel_only, {funnel: dashboard_nr(dashboard_funnel_leads_30d)})
                            : dashboard_format(dashboard_i18n.templates.contacts_breakdown_chat_only, {chat: dashboard_nr(dashboard_ai_chat_leads_30d)})));
                set_text('#dashboard_funnel_conversion_rate_30d', contactsBreakdown);
                set_metric('#dashboard_active_remaining', dashboard_forever_analytics.to_active);
                set_metric('#dashboard_vip_remaining', dashboard_forever_analytics.to_vip);
                set_progress('#dashboard_active_progress_fill', dashboard_forever_analytics.active_progress_percent);
                set_progress('#dashboard_vip_progress_fill', dashboard_forever_analytics.vip_progress_percent);
                set_text('#dashboard_active_progress_copy', dashboard_to_active > 0
                    ? dashboard_format(dashboard_i18n.templates.active_progress_remaining, {count: dashboard_nr(dashboard_to_active)})
                    : dashboard_i18n.templates.active_progress_unlocked);
                set_text('#dashboard_vip_progress_copy', dashboard_to_vip > 0
                    ? dashboard_format(dashboard_i18n.templates.vip_progress_remaining, {count: dashboard_nr(dashboard_to_vip)})
                    : dashboard_i18n.templates.vip_progress_unlocked);
                set_text('#dashboard_growth_note', dashboard_webinar_total > 0
                    ? dashboard_format(dashboard_i18n.templates.growth_note_webinars, {count: dashboard_nr(dashboard_webinar_total)})
                    : dashboard_i18n.templates.growth_note_default);

                const topBlogContent = dashboard_value(dashboard_forever_analytics.top_blog_content_30d, null);
                const topAppContent = dashboard_value(dashboard_forever_analytics.top_app_content_30d, null);
                set_text('#dashboard_top_blog_title', topBlogContent ? (topBlogContent.title || topBlogContent.url || '—') : dashboard_i18n.no_blog_signal);
                set_text('#dashboard_top_app_title', topAppContent ? (topAppContent.url || '—') : dashboard_i18n.no_app_signal);

                const bestContentSummary = topBlogContent
                    ? dashboard_format(dashboard_i18n.templates.best_content_blog, {title: topBlogContent.title || topBlogContent.url || '—'})
                    : (topAppContent ? dashboard_format(dashboard_i18n.templates.best_content_app, {title: topAppContent.url || '—'}) : dashboard_i18n.templates.best_content_none);
                set_text('#dashboard_best_content_summary', bestContentSummary);

                const topCountry = dashboard_value(dashboard_forever_analytics.top_country_30d, null);
                const topSource = dashboard_value(dashboard_forever_analytics.top_shop_source_30d, null);
                set_text('#dashboard_top_country_label', topCountry ? `${topCountry.country_code} · ${dashboard_nr(topCountry.total)}` : dashboard_i18n.no_signal);
                set_text('#dashboard_top_source_label', topSource ? `${dashboard_source_label(topSource.source)} · ${dashboard_nr(topSource.total)}` : dashboard_i18n.no_signal);

                const conversionSummary = dashboard_registration_clicks_30d <= 0 && dashboard_qualified_clicks_30d > 0
                    ? dashboard_i18n.templates.conversion_registration_gap
                    : (dashboard_contact_captures_30d > 0
                        ? dashboard_i18n.templates.conversion_leads
                        : dashboard_i18n.templates.conversion_default);
                const openModeType = dashboard_nested_value(dashboard_funnel_analytics, ['best_open_mode', 'type'], null);
                const thankYouType = dashboard_nested_value(dashboard_funnel_analytics, ['best_thank_you_type', 'type'], null);
                const openModeLabel = openModeType && funnel_open_mode_labels[openModeType] ? funnel_open_mode_labels[openModeType] : null;
                const thankYouLabel = thankYouType && funnel_thank_you_labels[thankYouType] ? funnel_thank_you_labels[thankYouType] : null;
                const conversionAddOn = [];
                if(openModeLabel) {
                    conversionAddOn.push(dashboard_format(dashboard_i18n.templates.conversion_best_opening, {label: openModeLabel.toLowerCase()}));
                }
                if(thankYouLabel) {
                    conversionAddOn.push(dashboard_format(dashboard_i18n.templates.conversion_best_thank_you, {label: thankYouLabel.toLowerCase()}));
                }
                set_text('#dashboard_conversion_summary', conversionAddOn.length ? `${conversionSummary} ${conversionAddOn.join(' · ')}.` : conversionSummary);

                const strengths = [];
                if(dashboard_blog_qualified_clicks_30d > 0) {
                    strengths.push(dashboard_format(dashboard_i18n.templates.strength_blog, {count: dashboard_nr(dashboard_blog_qualified_clicks_30d)}));
                }
                if(dashboard_app_qualified_clicks_30d > 0) {
                    strengths.push(dashboard_format(dashboard_i18n.templates.strength_app, {count: dashboard_nr(dashboard_app_qualified_clicks_30d)}));
                }
                if(dashboard_contact_captures_30d > 0) {
                    strengths.push(dashboard_format(dashboard_i18n.templates.strength_contacts, {count: dashboard_nr(dashboard_contact_captures_30d)}));
                }
                if(topSource && topSource.source) {
                    strengths.push(dashboard_format(dashboard_i18n.templates.strength_source, {source: dashboard_source_label(topSource.source)}));
                }
                set_html('#dashboard_strengths_list', strengths.length ? strengths.map(item => `<div class="dashboard-side-card-list-item is-positive">${item}</div>`).join('') : `<div class="dashboard-side-card-list-item is-positive">${dashboard_i18n.templates.strengths_empty}</div>`);

                const blockers = [];
                if(dashboard_qualified_clicks_30d < dashboard_growth_active_threshold) {
                    blockers.push(dashboard_format(dashboard_i18n.templates.blocker_active_gap, {count: dashboard_nr(dashboard_to_active)}));
                }
                if(dashboard_registration_clicks_30d <= 0 && dashboard_qualified_clicks_30d > 0) {
                    blockers.push(dashboard_i18n.templates.blocker_no_registrations);
                }
                if(dashboard_funnel_unique_clicks_30d > 0 && dashboard_funnel_leads_30d <= 0) {
                    blockers.push(dashboard_i18n.templates.blocker_funnel_no_leads);
                }
                if(dashboard_support_repeated_issue_detected && !dashboard_webinar_total) {
                    blockers.push(dashboard_i18n.templates.blocker_repeated_issue);
                }
                set_html('#dashboard_blockers_list', blockers.length ? blockers.map(item => `<div class="dashboard-side-card-list-item is-warning">${item}</div>`).join('') : `<div class="dashboard-side-card-list-item is-warning">${dashboard_i18n.templates.blockers_empty}</div>`);

                const dashboardRecommendations = Array.isArray(dashboard_forever_analytics.recommendations) ? dashboard_forever_analytics.recommendations : [];
                const nextRecommendation = dashboardRecommendations.length ? dashboardRecommendations[0] : null;
                set_text('#dashboard_next_action_title', nextRecommendation && nextRecommendation.title ? nextRecommendation.title : dashboard_value(dashboard_forever_analytics.next_focus, dashboard_i18n.templates.next_action_fallback_title));
                set_text('#dashboard_next_action_description', nextRecommendation && nextRecommendation.description ? nextRecommendation.description : dashboard_i18n.templates.next_action_fallback_description);
                const nextActionCta = document.querySelector('#dashboard_next_action_cta');
                if(nextActionCta) {
                    nextActionCta.innerText = nextRecommendation && nextRecommendation.cta_label ? nextRecommendation.cta_label : dashboard_i18n.hero_primary_cta;
                    nextActionCta.setAttribute('href', nextRecommendation && nextRecommendation.cta_url ? nextRecommendation.cta_url : <?= json_encode(url('links?type=biolink')) ?>);
                }

                const supportSummaryText = dashboard_support_repeated_issue_detected && !dashboard_webinar_total
                    ? dashboard_i18n.templates.support_repeated_issue
                    : (dashboard_support_unread_total > 0
                        ? dashboard_format(dashboard_i18n.templates.support_unread, {count: dashboard_nr(dashboard_support_unread_total)})
                        : dashboard_i18n.templates.support_default);
                set_text('#dashboard_support_summary_text', supportSummaryText);
                set_metric('#dashboard_support_open_total', dashboard_support_summary.open_total);
                set_metric('#dashboard_support_unread_total', dashboard_support_summary.unread_total);
                const supportPrimaryCta = document.querySelector('#dashboard_support_primary_cta');
                const supportSecondaryCta = document.querySelector('#dashboard_support_secondary_cta');
                if(supportPrimaryCta) {
                    supportPrimaryCta.setAttribute('href', dashboard_value(dashboard_support_summary.selected_ticket_url, <?= json_encode(url('feedback-tickets')) ?>));
                    supportPrimaryCta.innerText = dashboard_support_unread_total > 0 ? dashboard_i18n.actions.support_primary_admin_replies : dashboard_i18n.support_primary_cta;
                }
                if(supportSecondaryCta) {
                    supportSecondaryCta.setAttribute('href', <?= json_encode(url('feedback-tickets')) ?>);
                    supportSecondaryCta.innerText = dashboard_support_repeated_issue_detected && !dashboard_webinar_total
                        ? dashboard_i18n.support_secondary_cta
                        : dashboard_format(dashboard_i18n.templates.support_secondary_webinar_topics, {count: dashboard_nr(dashboard_webinar_total)});
                }

                const top_countries_html = dashboard_value(dashboard_forever_analytics.top_countries_30d, []).map(country => `
                    <div class="dashboard-side-card-list-item d-flex justify-content-between align-items-center">
                        <span>${country.country_code ? `${country.country_code}` : '-'}</span>
                        <strong>${dashboard_nr(dashboard_value(country.total, 0))}</strong>
                    </div>
                `);
                render_dashboard_compact_list('#dashboard_top_countries_30d', '#dashboard_top_countries_30d_toggle', top_countries_html, 5);

                const groupedProgressChannels = [
                    {
                        badge: dashboard_i18n.channels.app_badge,
                        badgeClass: 'dashboard-list-source-badge dashboard-list-source-badge--app',
                        title: dashboard_i18n.channels.app_title,
                        copy: dashboard_i18n.channels.app_copy,
                        total: Number(dashboard_value(dashboard_forever_analytics.app_qualified_clicks_30d, 0)),
                    },
                    {
                        badge: dashboard_i18n.channels.blog_badge,
                        badgeClass: 'dashboard-list-source-badge dashboard-list-source-badge--blog',
                        title: dashboard_i18n.channels.blog_title,
                        copy: dashboard_i18n.channels.blog_copy,
                        total: Number(dashboard_value(dashboard_forever_analytics.blog_qualified_clicks_30d, 0)),
                    }
                ];

                const top_forever_pages_html = groupedProgressChannels.map(item => `
                    <div class="dashboard-side-card-list-item">
                        <div class="d-flex justify-content-between align-items-start" style="gap:.75rem;">
                            <div class="pr-2">
                                <div class="dashboard-list-source-meta">
                                    <span class="${item.badgeClass}">${item.badge}</span>
                                </div>
                                <div class="font-weight-bold text-white mb-1">${item.title}</div>
                                <div class="dashboard-list-source-copy">${item.copy}</div>
                            </div>
                            <strong>${dashboard_nr(dashboard_value(item.total, 0))}</strong>
                        </div>
                    </div>
                `);
                set_html('#dashboard_top_forever_pages_30d', top_forever_pages_html.join(''));

                const top_shop_sources_html = dashboard_value(dashboard_forever_analytics.top_shop_sources_30d, []).map(item => `
                    <div class="dashboard-side-card-list-item d-flex justify-content-between align-items-center">
                        <span class="text-truncate pr-2">${dashboard_source_label(item.source)}</span>
                        <strong>${dashboard_nr(dashboard_value(item.total, 0))}</strong>
                    </div>
                `);
                render_dashboard_compact_list('#dashboard_top_shop_sources_30d', '#dashboard_top_shop_sources_30d_toggle', top_shop_sources_html, 5);

                const signalChartLoading = document.querySelector('#dashboard_signal_chart_loading');
                const signalChartContainer = document.querySelector('#dashboard_signal_chart_container');
                const signalChartNoData = document.querySelector('#dashboard_signal_chart_no_data');
                const hideSignalChartLoading = () => {
                    if(signalChartLoading) {
                        signalChartLoading.classList.add('d-none');
                        signalChartLoading.classList.remove('d-flex', 'align-items-center', 'justify-content-center');
                        signalChartLoading.style.display = 'none';
                        signalChartLoading.style.visibility = 'hidden';
                        signalChartLoading.style.pointerEvents = 'none';
                    }
                };

                hideSignalChartLoading();

                const signalChartData = dashboard_value(dashboard_forever_analytics.signal_chart_30d, {});
                const hasSignalChartData = Array.isArray(signalChartData.labels) && signalChartData.labels.length > 0;
                if(!hasSignalChartData) {
                    hideSignalChartLoading();
                    signalChartNoData && signalChartNoData.classList.remove('d-none');
                } else {
                    hideSignalChartLoading();
                    signalChartContainer && signalChartContainer.classList.remove('d-none');
                    const chartCanvas = document.getElementById('dashboard_signal_chart');
                    const chartContext = chartCanvas ? chartCanvas.getContext('2d') : null;
                    const chartDependenciesReady = typeof Chart !== 'undefined' && typeof chart_options !== 'undefined';
                    if(chartContext && chartDependenciesReady) {
                        if(dashboard_signal_chart_instance) {
                            dashboard_signal_chart_instance.destroy();
                        }
                        const chartOptions = JSON.parse(JSON.stringify(chart_options));
                        chartOptions.maintainAspectRatio = false;
                        chartOptions.plugins = chartOptions.plugins || {};
                        chartOptions.plugins.legend = {display: false};
                        dashboard_signal_chart_instance = new Chart(chartContext, {
                            type: 'line',
                            data: {
                                labels: signalChartData.labels,
                                datasets: [
                                    {
                                        label: dashboard_i18n.chart.app,
                                        data: dashboard_value(signalChartData.app_clicks, []),
                                        borderColor: '#49e3cf',
                                        backgroundColor: 'rgba(73,227,207,.12)',
                                        borderWidth: 3,
                                        pointRadius: 2.5,
                                        fill: false,
                                        tension: .35
                                    },
                                    {
                                        label: dashboard_i18n.chart.blog,
                                        data: dashboard_value(signalChartData.blog_clicks, []),
                                        borderColor: '#5bb6ff',
                                        backgroundColor: 'rgba(91,182,255,.12)',
                                        borderWidth: 3,
                                        pointRadius: 2.5,
                                        fill: false,
                                        tension: .35
                                    },
                                    {
                                        label: dashboard_i18n.chart.registrations,
                                        data: dashboard_value(signalChartData.registration_clicks, []),
                                        borderColor: '#ffd166',
                                        backgroundColor: 'rgba(255,209,102,.12)',
                                        borderWidth: 2.5,
                                        pointRadius: 2,
                                        fill: false,
                                        tension: .35
                                    },
                                    {
                                        label: dashboard_i18n.chart.leads,
                                        data: dashboard_value(signalChartData.leads, []),
                                        borderColor: '#8b5cf6',
                                        backgroundColor: 'rgba(139,92,246,.12)',
                                        borderWidth: 2.5,
                                        pointRadius: 2,
                                        fill: false,
                                        tension: .35
                                    }
                                ]
                            },
                            options: chartOptions
                        });
                        hideSignalChartLoading();
                    } else {
                        hideSignalChartLoading();
                        signalChartNoData && signalChartNoData.classList.remove('d-none');
                    }
                }

                    initDashboardOnboarding();
                }
            } catch (error) {
                render_dashboard_fetch_error();
            }
        })();

        initDashboardOnboarding();
    </script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
