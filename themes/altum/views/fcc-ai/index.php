<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_ai_filters = (array) ($data->conversation_filters ?? []);
$fcc_ai_has_filters = (string) ($fcc_ai_filters['assistant_type'] ?? '') !== ''
    || (string) ($fcc_ai_filters['scope'] ?? '') !== ''
    || (string) ($fcc_ai_filters['lead_status'] ?? '') !== '';
$fcc_ai_is_admin = (bool) ($data->is_admin ?? false);
$fcc_ai_editable_assistant_types = is_array($data->editable_assistant_types ?? null) ? $data->editable_assistant_types : (array) ($data->assistant_types ?? []);
$fcc_ai_apps_url = url('links?type=biolink');
$fcc_ai_hero_intro = l($fcc_ai_is_admin ? 'fcc_ai.hero_intro_admin' : 'fcc_ai.hero_intro_user');
$fcc_ai_brand_logo_url = ASSETS_FULL_URL . 'images/fcc-preporuka-logo-wide.png';
$fcc_ai_useful_items = is_array($data->useful_items ?? null) ? $data->useful_items : [];
$fcc_ai_recent_alerts = is_array($data->recent_alerts ?? null) ? $data->recent_alerts : [];
$fcc_ai_rising_topics = is_array($data->rising_topics ?? null) ? $data->rising_topics : [];
$fcc_ai_build_hub_url = static function(array $overrides = []) use ($fcc_ai_filters) {
    $query = array_merge([
        'assistant_type' => (string) ($fcc_ai_filters['assistant_type'] ?? ''),
        'scope' => (string) ($fcc_ai_filters['scope'] ?? ''),
        'lead_status' => (string) ($fcc_ai_filters['lead_status'] ?? ''),
    ], $overrides);

    $query = array_filter($query, static function($value) {
        return $value !== null && $value !== '';
    });

    return url('fcc-ai' . (!empty($query) ? '?' . http_build_query($query) : ''));
};
?>

<div
    class="container fcc-ai-shell"
    data-review-resolve-url="<?= htmlspecialchars((string) ($data->review_resolve_endpoint ?? url('fcc-ai/resolve-feedback')), ENT_QUOTES, 'UTF-8') ?>"
    data-csrf-token="<?= htmlspecialchars(\Altum\Csrf::get(), ENT_QUOTES, 'UTF-8') ?>"
>
    <style>
        .fcc-ai-shell {
            --fcc-ai-gap: 1rem;
            --fcc-ai-border: rgba(148, 163, 184, 0.14);
            --fcc-ai-border-strong: rgba(148, 163, 184, 0.24);
            --fcc-ai-surface: linear-gradient(160deg, rgba(31, 41, 60, 0.96), rgba(16, 22, 35, 0.98));
            --fcc-ai-surface-soft: linear-gradient(155deg, rgba(34, 45, 66, 0.94), rgba(18, 25, 39, 0.96));
            --fcc-ai-surface-alt: linear-gradient(155deg, rgba(41, 53, 76, 0.92), rgba(22, 31, 47, 0.94));
            --fcc-ai-surface-deep: #0f172a;
            --fcc-ai-text: #f3f7fd;
            --fcc-ai-text-soft: rgba(191, 203, 218, 0.82);
            --fcc-ai-blue: #5f89ff;
            --fcc-ai-violet: #7d6bff;
            --fcc-ai-orange: #ffb14b;
            --fcc-ai-green: #49d7c7;
            --fcc-ai-cyan: #7df3e6;
            padding-bottom: 1.5rem;
        }

        .fcc-ai-shell > .card,
        .fcc-ai-shell > .fcc-ai-quicknav,
        .fcc-ai-shell > .fcc-ai-action-grid,
        .fcc-ai-shell > .fcc-ai-section-row {
            margin-bottom: 0;
        }

        .fcc-ai-shell > :not(style) + :not(style) {
            margin-top: var(--fcc-ai-gap);
        }

        .fcc-ai-shell .card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.15rem;
            background: var(--fcc-ai-surface-soft);
            box-shadow: 0 1rem 2rem rgba(2, 6, 23, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
        }

        .fcc-ai-shell .card::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.03), transparent 28%, transparent 72%, rgba(255, 255, 255, 0.015));
        }

        .fcc-ai-shell .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1rem 1.15rem;
            position: relative;
            z-index: 1;
        }

        .fcc-ai-shell .card-body {
            padding: 1.15rem;
            position: relative;
            z-index: 1;
        }

        .fcc-ai-shell h1,
        .fcc-ai-shell h2,
        .fcc-ai-shell h3,
        .fcc-ai-shell h4,
        .fcc-ai-shell h5,
        .fcc-ai-shell h6,
        .fcc-ai-shell .font-weight-bold {
            color: var(--fcc-ai-text);
        }

        .fcc-ai-shell .text-muted,
        .fcc-ai-shell .small,
        .fcc-ai-shell .card-header .small,
        .fcc-ai-shell label.small {
            color: var(--fcc-ai-text-soft) !important;
        }

        .fcc-ai-shell [id] {
            scroll-margin-top: 1rem;
        }

        .fcc-ai-hero {
            background:
                radial-gradient(900px 260px at -8% -40%, rgba(45, 212, 191, 0.18), transparent 62%),
                radial-gradient(680px 220px at 104% 0%, rgba(95, 137, 255, 0.16), transparent 60%),
                radial-gradient(340px 160px at 85% 20%, rgba(255, 177, 75, 0.08), transparent 58%),
                linear-gradient(160deg, rgba(20, 28, 44, 0.98), rgba(10, 15, 25, 0.995));
            color: #f8fafc;
            border-color: rgba(127, 215, 208, 0.18);
            box-shadow: 0 1.35rem 2.8rem rgba(2, 8, 23, 0.24), inset 0 3px 0 rgba(92, 239, 223, 0.72), inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .fcc-ai-hero::before {
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.05), transparent 28%, transparent 72%, rgba(255, 255, 255, 0.02));
        }

        .fcc-ai-hero .card-body {
            padding: 1.35rem;
        }

        .fcc-ai-hero__top {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
        }

        .fcc-ai-hero__main {
            flex: 1 1 540px;
            min-width: 0;
        }

        .fcc-ai-hero__side {
            flex: 0 1 360px;
            min-width: min(100%, 320px);
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .85rem;
        }

        .fcc-ai-hero__eyebrow {
            color: #9ff3e7;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            margin-bottom: .55rem;
        }

        .fcc-ai-hero__title {
            font-size: 1.95rem;
            line-height: 1.04;
            letter-spacing: -.03em;
            margin-bottom: .45rem;
            color: #ffffff;
        }

        .fcc-ai-hero__copy {
            max-width: 760px;
            color: rgba(226, 232, 240, .96);
            line-height: 1.68;
            margin-bottom: 0;
        }

        .fcc-ai-hero__brand-card {
            width: min(100%, 360px);
            padding: .95rem 1rem;
            border-radius: 1.1rem;
            border: 1px solid rgba(255, 255, 255, .1);
            background:
                radial-gradient(circle at top left, rgba(255, 177, 75, .14), transparent 34%),
                radial-gradient(circle at right center, rgba(95, 137, 255, .14), transparent 42%),
                linear-gradient(155deg, rgba(255, 255, 255, .06), rgba(255, 255, 255, .018));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.05), 0 1rem 2rem rgba(2, 8, 23, .16);
            backdrop-filter: blur(10px);
        }

        .fcc-ai-hero__brand-label {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: .65rem;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(219, 234, 254, .88);
        }

        .fcc-ai-hero__brand-label::before {
            content: '';
            width: .5rem;
            height: .5rem;
            border-radius: 999px;
            background: linear-gradient(135deg, #49d7c7, #5f89ff);
            box-shadow: 0 0 0 .2rem rgba(73, 215, 199, .12);
        }

        .fcc-ai-hero__brand-logo {
            display: block;
            width: 100%;
            max-width: 300px;
            height: auto;
            margin-left: auto;
            filter: drop-shadow(0 1rem 1.6rem rgba(2, 8, 23, .24));
        }

        .fcc-ai-hero__brand-copy {
            margin-top: .55rem;
            margin-bottom: 0;
            font-size: .8rem;
            line-height: 1.5;
            color: rgba(226, 232, 240, .78);
            text-align: right;
        }

        .fcc-ai-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(132px, 1fr));
            gap: .85rem;
            margin-top: 1.15rem;
        }

        .fcc-ai-metric {
            padding: .95rem .95rem;
            border-radius: 1rem;
            background: linear-gradient(155deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
            border: 1px solid rgba(255, 255, 255, .09);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
        }

        .fcc-ai-metric__label {
            color: rgba(191, 219, 254, .92);
            font-size: .75rem;
            margin-bottom: .4rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .fcc-ai-metric__value {
            color: #fff;
            font-size: 1.4rem;
            line-height: 1;
            font-weight: 800;
            margin-bottom: .35rem;
        }

        .fcc-ai-metric__hint {
            color: rgba(226, 232, 240, .76);
            font-size: .72rem;
        }

        .fcc-ai-quicknav {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--fcc-ai-gap);
            margin-bottom: 0;
        }

        .fcc-ai-quicknav__link {
            display: flex;
            flex-direction: column;
            gap: .28rem;
            min-height: 100%;
            padding: .95rem 1rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.09);
            background: var(--fcc-ai-surface-alt);
            color: var(--fcc-ai-text);
            text-decoration: none;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04), 0 .8rem 1.6rem rgba(2,6,23,.12);
            transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease;
        }

        .fcc-ai-quicknav__link:hover {
            color: var(--fcc-ai-text);
            text-decoration: none;
            transform: translateY(-1px);
            border-color: rgba(127, 243, 230, 0.22);
            background: linear-gradient(145deg, rgba(46, 60, 86, 0.95), rgba(25, 35, 53, 0.98));
            box-shadow: 0 1rem 1.8rem rgba(2,6,23,.12);
        }

        .fcc-ai-quicknav__link.is-static {
            cursor: default;
        }

        .fcc-ai-quicknav__link.is-static:hover {
            transform: none;
            border-color: rgba(255, 255, 255, 0.09);
            background: var(--fcc-ai-surface-alt);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04), 0 .8rem 1.6rem rgba(2,6,23,.12);
        }

        .fcc-ai-quicknav__eyebrow {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #9ff3e7;
        }

        .fcc-ai-quicknav__title {
            font-weight: 700;
            color: #f8fafc;
        }

        .fcc-ai-quicknav__copy {
            font-size: .82rem;
            line-height: 1.5;
            color: var(--fcc-ai-text-soft);
        }

        .fcc-ai-section-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .75rem;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .fcc-ai-section-head__copy p {
            color: var(--fcc-ai-text-soft);
            margin: .3rem 0 0;
        }

        .fcc-ai-inline-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .62rem;
            border-radius: 999px;
            background: rgba(10, 18, 31, 0.38);
            border: 1px solid rgba(96, 165, 250, .22);
            color: #dbeafe;
            font-size: .76rem;
            font-weight: 800;
        }

        .fcc-ai-accordion-stack {
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }

        .fcc-ai-accordion {
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 1rem;
            background: var(--fcc-ai-surface-soft);
            overflow: hidden;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
        }

        .fcc-ai-accordion[open] {
            border-color: rgba(127,215,208,.18);
            background:
                radial-gradient(circle at top right, rgba(84,124,255,.12) 0%, rgba(84,124,255,0) 38%),
                radial-gradient(circle at top left, rgba(45,212,191,.1) 0%, rgba(45,212,191,0) 36%),
                linear-gradient(160deg, rgba(41, 53, 76, 0.96), rgba(20, 29, 45, 0.98));
            box-shadow: 0 1rem 2rem rgba(2,6,23,.16), inset 0 1px 0 rgba(255,255,255,.05);
        }

        .fcc-ai-accordion__summary {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .9rem;
            align-items: flex-start;
            padding: 1rem 1.05rem;
            cursor: pointer;
            position: relative;
            padding-right: 3.3rem;
        }

        .fcc-ai-accordion__summary::-webkit-details-marker {
            display: none;
        }

        .fcc-ai-accordion__summary::after {
            content: '+';
            position: absolute;
            right: 1.05rem;
            top: 1rem;
            width: 1.9rem;
            height: 1.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-size: 1rem;
            font-weight: 700;
            color: #9ff3e7;
            background: rgba(9, 17, 31, 0.4);
            border: 1px solid rgba(127, 243, 230, 0.16);
        }

        .fcc-ai-accordion[open] .fcc-ai-accordion__summary::after {
            content: '−';
        }

        .fcc-ai-accordion__title {
            display: flex;
            align-items: center;
            gap: .55rem;
            font-weight: 700;
            color: var(--fcc-ai-text);
            margin-bottom: .3rem;
        }

        .fcc-ai-accordion__description {
            color: var(--fcc-ai-text-soft);
            font-size: .92rem;
            line-height: 1.5;
            max-width: 560px;
        }

        .fcc-ai-accordion__meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .45rem;
            max-width: 360px;
        }

        .fcc-ai-accordion__body {
            padding: 0 1.05rem 1.05rem;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .fcc-ai-accordion__state {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .58rem;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 800;
            background: rgba(59, 130, 246, .18);
            color: #dbeafe;
        }

        .fcc-ai-accordion__state.is-pending {
            background: rgba(249, 115, 22, .16);
            color: #ffd6ad;
        }

        .fcc-ai-compact-list {
            display: grid;
            gap: .65rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .fcc-ai-compact-list li {
            padding: .8rem .9rem;
            border-radius: .95rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            color: var(--fcc-ai-text);
            line-height: 1.52;
        }

        .fcc-ai-compact-list strong {
            display: block;
            font-size: .92rem;
            margin-bottom: .15rem;
        }

        .fcc-ai-panel-stack {
            display: flex;
            flex-direction: column;
            gap: var(--fcc-ai-gap);
        }

        .fcc-ai-panel-note {
            color: var(--fcc-ai-text-soft);
            font-size: .9rem;
            line-height: 1.55;
        }

        .fcc-ai-code {
            display: block;
            margin-top: .35rem;
            padding: .72rem .8rem;
            border-radius: .9rem;
            background: rgba(15, 23, 42, 0.78);
            border: 1px solid rgba(255,255,255,.08);
            color: #e2e8f0;
            font-size: .76rem;
            line-height: 1.5;
            word-break: break-all;
        }

        .fcc-ai-disclosure {
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 1rem;
            background: var(--fcc-ai-surface-alt);
            overflow: hidden;
        }

        .fcc-ai-disclosure summary {
            list-style: none;
            cursor: pointer;
            padding: .95rem 1rem;
            font-weight: 700;
            color: var(--fcc-ai-text);
            position: relative;
            padding-right: 3rem;
        }

        .fcc-ai-disclosure summary::-webkit-details-marker {
            display: none;
        }

        .fcc-ai-disclosure summary::after {
            content: '+';
            position: absolute;
            right: 1rem;
            top: .9rem;
            color: #9ff3e7;
        }

        .fcc-ai-disclosure[open] summary::after {
            content: '−';
        }

        .fcc-ai-disclosure__body {
            padding: 0 1rem 1rem;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .fcc-ai-guide-summary {
            display: block;
            padding-right: 1rem;
        }

        .fcc-ai-guide-summary__title {
            display: block;
            font-weight: 700;
            color: var(--fcc-ai-text);
        }

        .fcc-ai-guide-summary__text {
            display: block;
            margin-top: .25rem;
            color: var(--fcc-ai-text-soft);
            font-size: .88rem;
            font-weight: 500;
            line-height: 1.5;
        }

        .fcc-ai-guide-copy {
            display: grid;
            gap: .85rem;
            color: var(--fcc-ai-text);
        }

        .fcc-ai-guide-copy p {
            margin: 0;
            color: var(--fcc-ai-text-soft);
            line-height: 1.65;
        }

        .fcc-ai-guide-points {
            display: grid;
            gap: .65rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .fcc-ai-guide-points li {
            padding: .85rem .9rem;
            border-radius: .95rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            color: var(--fcc-ai-text);
            line-height: 1.55;
        }

        .fcc-ai-guide-tip {
            padding: .85rem .9rem;
            border-radius: .95rem;
            background: rgba(45, 212, 191, .08);
            border: 1px solid rgba(127, 243, 230, .12);
            color: var(--fcc-ai-text);
            line-height: 1.55;
        }

        .fcc-ai-filter-bar {
            display: grid;
            gap: .9rem;
            padding: .1rem 0;
        }

        .fcc-ai-filter-grid {
            display: grid;
            gap: .75rem;
            grid-template-columns: 1fr;
        }

        .fcc-ai-filter-field {
            padding: .85rem .9rem;
            border-radius: 1rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
        }

        .fcc-ai-filter-label {
            display: block;
            margin-bottom: .5rem;
            color: var(--fcc-ai-text-soft);
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .fcc-ai-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            align-items: center;
        }

        .fcc-ai-filter-actions .btn {
            min-width: 140px;
        }

        .fcc-ai-filter-status {
            display: inline-flex;
            align-items: center;
        }

        @media (max-width: 575.98px) {
            .fcc-ai-filter-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .fcc-ai-filter-actions .btn,
            .fcc-ai-filter-status {
                width: 100%;
                justify-content: center;
            }
        }

        .fcc-ai-conversation-list {
            display: flex;
            flex-direction: column;
            gap: .8rem;
            max-height: 920px;
            overflow-y: auto;
        }

        .fcc-ai-conversation-item {
            display: block;
            padding: .95rem 1rem;
            border-radius: 1rem;
            border: 1px solid rgba(255,255,255,.09);
            background: var(--fcc-ai-surface-alt);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04), 0 .75rem 1.45rem rgba(2,6,23,.1);
            color: var(--fcc-ai-text);
            text-decoration: none;
            transition: transform .14s ease, border-color .14s ease, box-shadow .14s ease;
        }

        .fcc-ai-conversation-item:hover {
            text-decoration: none;
            color: var(--fcc-ai-text);
            border-color: rgba(127,215,208,.18);
            box-shadow: 0 1rem 1.8rem rgba(2,6,23,.12);
            transform: translateY(-1px);
        }

        .fcc-ai-conversation-item.is-selected {
            border-color: rgba(127,215,208,.22);
            background:
                radial-gradient(circle at top right, rgba(84,124,255,.12) 0%, rgba(84,124,255,0) 38%),
                radial-gradient(circle at top left, rgba(45,212,191,.1) 0%, rgba(45,212,191,0) 36%),
                linear-gradient(160deg, rgba(43, 57, 82, 0.96), rgba(22, 31, 48, 0.98));
        }

        .fcc-ai-conversation-item__top {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: flex-start;
            margin-bottom: .55rem;
        }

        .fcc-ai-conversation-item__title {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            align-items: center;
            font-weight: 700;
        }

        .fcc-ai-conversation-item__time {
            color: var(--fcc-ai-text-soft);
            font-size: .78rem;
            white-space: nowrap;
        }

        .fcc-ai-conversation-item__source,
        .fcc-ai-conversation-item__meta {
            color: var(--fcc-ai-text-soft);
            font-size: .8rem;
        }

        .fcc-ai-conversation-item__preview {
            margin: .5rem 0;
            color: var(--fcc-ai-text);
            line-height: 1.5;
        }

        .fcc-ai-empty {
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(255,255,255,.04);
            border: 1px dashed rgba(148,163,184,.24);
            color: var(--fcc-ai-text-soft);
        }

        .fcc-ai-thread {
            display: flex;
            flex-direction: column;
            gap: .9rem;
            max-height: 760px;
            overflow-y: auto;
            padding-right: .15rem;
        }

        .fcc-ai-thread__message {
            display: flex;
        }

        .fcc-ai-thread__message.is-user {
            justify-content: flex-end;
        }

        .fcc-ai-thread__message.is-system {
            justify-content: center;
        }

        .fcc-ai-thread__bubble {
            width: min(100%, 92%);
            padding: .9rem 1rem;
            border-radius: 1rem;
            background: linear-gradient(160deg, rgba(39, 51, 73, 0.94), rgba(22, 30, 45, 0.96));
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
        }

        .fcc-ai-thread__message.is-user .fcc-ai-thread__bubble {
            background: linear-gradient(135deg, rgba(16, 109, 98, 0.32), rgba(22, 78, 154, 0.28));
            border-color: rgba(127,215,208,.22);
        }

        .fcc-ai-thread__message.is-system .fcc-ai-thread__bubble {
            width: min(100%, 84%);
            background: linear-gradient(135deg, rgba(120, 53, 15, 0.26), rgba(251, 146, 60, 0.12));
            border-color: rgba(251,146,60,.18);
        }

        .fcc-ai-thread__meta {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .4rem;
            font-size: .75rem;
            color: rgba(191,203,218,.7);
        }

        .fcc-ai-thread__content {
            white-space: pre-line;
            line-height: 1.6;
            color: #f3f7fd;
        }

        .fcc-ai-thread__suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .7rem;
        }

        .fcc-ai-thread__suggestion {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .42rem .7rem;
            border-radius: 999px;
            background: rgba(59,130,246,.14);
            border: 1px solid rgba(147,197,253,.2);
            color: #dbeafe;
            font-size: .78rem;
            text-decoration: none;
        }

        .fcc-ai-thread__suggestion:hover {
            text-decoration: none;
            background: rgba(59,130,246,.2);
            color: #eff6ff;
        }

        .fcc-ai-detail-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: .9rem;
            margin-bottom: 1.1rem;
        }

        .fcc-ai-detail-hero {
            margin-bottom: 1rem;
            padding: 1rem 1.05rem;
            border-radius: 1.05rem;
            border: 1px solid rgba(127,215,208,.14);
            background:
                radial-gradient(circle at top right, rgba(84,124,255,.12) 0%, rgba(84,124,255,0) 36%),
                radial-gradient(circle at top left, rgba(45,212,191,.1) 0%, rgba(45,212,191,0) 34%),
                linear-gradient(160deg, rgba(38, 51, 74, 0.96), rgba(21, 30, 46, 0.98));
            box-shadow: 0 1rem 2rem rgba(2,6,23,.12), inset 0 1px 0 rgba(255,255,255,.04);
        }

        .fcc-ai-detail-hero__top {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
        }

        .fcc-ai-detail-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            padding: .36rem .68rem;
            border-radius: 999px;
            background: rgba(59,130,246,.16);
            border: 1px solid rgba(147,197,253,.18);
            color: #dbeafe;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .fcc-ai-detail-hero__title {
            margin-top: .8rem;
            font-size: 1.18rem;
            font-weight: 800;
            line-height: 1.35;
            color: #f8fbff;
            max-width: 680px;
        }

        .fcc-ai-detail-hero__id {
            display: inline-flex;
            align-items: center;
            margin-top: .8rem;
            padding: .42rem .68rem;
            border-radius: .82rem;
            background: rgba(9, 17, 31, 0.36);
            border: 1px solid rgba(255,255,255,.08);
            color: #c9d6ea;
            font-size: .77rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            word-break: break-all;
        }

        .fcc-ai-detail-hero__pills {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: flex-end;
        }

        .fcc-ai-detail-pill {
            display: inline-flex;
            align-items: center;
            padding: .44rem .74rem;
            border-radius: 999px;
            font-size: .76rem;
            font-weight: 800;
            line-height: 1;
            border: 1px solid rgba(255,255,255,.1);
            background: rgba(255,255,255,.05);
            color: #e6eefc;
        }

        .fcc-ai-detail-pill.is-success {
            background: rgba(16, 185, 129, .16);
            border-color: rgba(110, 231, 183, .18);
            color: #d1fae5;
        }

        .fcc-ai-detail-pill.is-muted {
            background: rgba(148,163,184,.14);
            border-color: rgba(203,213,225,.12);
            color: #e2e8f0;
        }

        .fcc-ai-detail-meta__card {
            padding: .95rem 1rem;
            border-radius: 1rem;
            background: linear-gradient(160deg, rgba(44, 56, 79, 0.9), rgba(29, 38, 57, 0.92));
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
        }

        .fcc-ai-detail-meta__card.is-wide {
            grid-column: 1 / -1;
        }

        .fcc-ai-detail-meta__label {
            color: var(--fcc-ai-text-soft);
            font-size: .76rem;
            margin-bottom: .45rem;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .fcc-ai-detail-meta__value {
            color: var(--fcc-ai-text);
            font-weight: 700;
            line-height: 1.45;
            font-size: 1.02rem;
        }

        .fcc-ai-detail-meta__value.is-mono {
            font-size: .86rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            word-break: break-word;
            color: #dbe6f8;
        }

        .fcc-ai-detail-meta__split {
            display: flex;
            flex-wrap: wrap;
            gap: .9rem;
        }

        .fcc-ai-detail-meta__split span {
            display: inline-flex;
            align-items: baseline;
            gap: .3rem;
            color: #dce7f7;
            font-size: .95rem;
        }

        .fcc-ai-detail-meta__split strong {
            color: #ffffff;
            font-size: 1.18rem;
            font-weight: 800;
        }

        .fcc-ai-detail-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            margin-bottom: 1.15rem;
            padding: .95rem 1rem;
            border-radius: 1rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
        }

        .fcc-ai-thread-panel {
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(9, 17, 31, 0.34);
            border: 1px solid rgba(255,255,255,.08);
        }

        .fcc-ai-detail-column.is-loading {
            opacity: .6;
            pointer-events: none;
            transition: opacity .18s ease;
        }

        .fcc-ai-recent-leads {
            display: grid;
            gap: .8rem;
        }

        .fcc-ai-review-list {
            display: grid;
            gap: .8rem;
        }

        .fcc-ai-review-item {
            display: grid;
            gap: .75rem;
            padding: .95rem 1rem;
            border-radius: 1rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            transition: transform .16s ease, border-color .16s ease, background-color .16s ease;
        }

        .fcc-ai-review-item:hover {
            transform: translateY(-1px);
            border-color: rgba(248, 113, 113, .28);
            background: rgba(255,255,255,.055);
        }

        .fcc-ai-review-item__body {
            display: block;
            color: var(--fcc-ai-text);
            text-decoration: none;
        }

        .fcc-ai-review-item__body:hover {
            color: var(--fcc-ai-text);
            text-decoration: none;
        }

        .fcc-ai-review-item__top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            margin-bottom: .45rem;
        }

        .fcc-ai-review-item__title {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            align-items: center;
            font-weight: 700;
        }

        .fcc-ai-review-item__meta,
        .fcc-ai-review-item__excerpt {
            color: var(--fcc-ai-text-soft);
            font-size: .83rem;
            line-height: 1.55;
        }

        .fcc-ai-review-item__note {
            margin-top: .45rem;
            color: #fee2e2;
            font-size: .8rem;
            line-height: 1.5;
        }

        .fcc-ai-review-item__actions {
            display: flex;
            justify-content: flex-end;
        }

        .fcc-ai-review-item__resolve {
            min-width: 150px;
        }

        .fcc-ai-recent-lead {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .85rem;
            padding: .95rem 1rem;
            border-radius: 1rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
        }

        .fcc-ai-recent-lead__meta {
            color: var(--fcc-ai-text-soft);
            font-size: .8rem;
            line-height: 1.55;
        }

        .fcc-ai-thread__feedback {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .7rem;
        }

        .fcc-ai-thread__feedback-chip {
            display: inline-flex;
            align-items: center;
            gap: .32rem;
            padding: .34rem .56rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.05);
            color: #dbeafe;
        }

        .fcc-ai-thread__feedback-chip.is-negative {
            background: rgba(239, 68, 68, .12);
            border-color: rgba(248, 113, 113, .22);
            color: #fee2e2;
        }

        .fcc-ai-thread__feedback-chip.is-positive {
            background: rgba(16, 185, 129, .12);
            border-color: rgba(52, 211, 153, .22);
            color: #d1fae5;
        }

        @media (max-width: 767.98px) {
            .fcc-ai-detail-hero__pills {
                justify-content: flex-start;
            }
        }

        .fcc-ai-shell .form-control,
        .fcc-ai-shell .custom-select {
            min-height: 3rem;
            border-radius: .95rem;
            border-color: rgba(148,163,184,.16) !important;
            background: linear-gradient(145deg, rgba(15,23,42,.58), rgba(15,23,42,.42)) !important;
            color: #f8fafc !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04) !important;
        }

        .fcc-ai-shell textarea.form-control {
            min-height: 6rem;
        }

        .fcc-ai-shell .custom-select option {
            color: #f8fafc;
            background: #0f172a;
        }

        .fcc-ai-shell .form-control::placeholder {
            color: rgba(148,163,184,.72);
        }

        .fcc-ai-shell .form-control:focus,
        .fcc-ai-shell .custom-select:focus {
            border-color: rgba(45,212,191,.35);
            box-shadow: 0 0 0 .18rem rgba(45,212,191,.08);
            color: #f8fafc;
        }

        .fcc-ai-shell .custom-control-label,
        .fcc-ai-shell .custom-control-label::before,
        .fcc-ai-shell .custom-control-label::after {
            cursor: pointer;
        }

        .fcc-ai-shell .custom-control-label {
            color: var(--fcc-ai-text-soft);
        }

        .fcc-ai-shell .btn {
            border-radius: .95rem;
            font-weight: 700;
            min-height: 2.85rem;
            padding: .72rem 1rem;
        }

        .fcc-ai-shell .btn-primary {
            color: #07302d;
            border: 0;
            background: linear-gradient(135deg, #bbfff6 0%, #7df3e6 24%, #4fded7 62%, #2ecfca 100%);
            box-shadow: 0 .95rem 2rem rgba(46, 207, 202, 0.24);
        }

        .fcc-ai-shell .btn-primary:hover,
        .fcc-ai-shell .btn-primary:focus {
            color: #07302d;
            background: linear-gradient(135deg, #cafff8 0%, #89f6ea 24%, #5ae3db 62%, #35d4ce 100%);
        }

        .fcc-ai-shell .btn-outline-secondary,
        .fcc-ai-shell .btn-outline-primary,
        .fcc-ai-shell .btn-outline-success {
            border-color: rgba(148,163,184,.18);
            background: rgba(255,255,255,.06);
            color: #e2e8f0;
        }

        .fcc-ai-shell .btn-outline-secondary:hover,
        .fcc-ai-shell .btn-outline-primary:hover,
        .fcc-ai-shell .btn-outline-success:hover,
        .fcc-ai-shell .btn-outline-secondary:focus,
        .fcc-ai-shell .btn-outline-primary:focus,
        .fcc-ai-shell .btn-outline-success:focus {
            border-color: rgba(127,215,208,.22);
            background: linear-gradient(145deg, rgba(45,212,191,.12), rgba(14,165,233,.06));
            color: #f8fafc;
        }

        .fcc-ai-shell .border-bottom {
            border-bottom-color: rgba(255,255,255,.08) !important;
        }

        .fcc-ai-shell code {
            color: #c7ddff;
            background: rgba(9,17,31,.38);
            padding: .15rem .35rem;
            border-radius: .5rem;
        }

        .fcc-ai-shell .alert-success {
            color: #d9fff9;
            background: rgba(20,184,166,.14);
            border-color: rgba(127,215,208,.18);
        }

        .fcc-ai-shell .badge-primary,
        .fcc-ai-shell .badge-light,
        .fcc-ai-shell .badge-success,
        .fcc-ai-shell .badge-secondary {
            padding: .38rem .6rem;
            border-radius: 999px;
            font-weight: 700;
            border: 1px solid rgba(255,255,255,.08);
        }

        .fcc-ai-shell .badge-primary {
            background: rgba(59,130,246,.16);
            color: #dbeafe;
        }

        .fcc-ai-shell .badge-light {
            background: rgba(148,163,184,.12);
            color: #e2e8f0;
        }

        .fcc-ai-shell .badge-success {
            background: rgba(20,184,166,.16);
            color: #d6fff8;
        }

        .fcc-ai-shell .badge-secondary {
            background: rgba(84,124,255,.14);
            color: #d6ddff;
        }

        .fcc-ai-action-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--fcc-ai-gap);
            margin-top: 0;
        }

        .fcc-ai-section-row {
            --bs-gutter-x: var(--fcc-ai-gap);
            --bs-gutter-y: var(--fcc-ai-gap);
        }

        .fcc-ai-column-stack {
            display: flex;
            flex-direction: column;
            gap: var(--fcc-ai-gap);
            height: 100%;
        }

        .fcc-ai-action-card {
            border: 1px solid rgba(125,145,185,.18);
            border-radius: 1.1rem;
            background: linear-gradient(180deg, rgba(20,30,48,.94), rgba(14,21,36,.92));
            box-shadow: 0 16px 36px rgba(7,11,18,.18);
            overflow: hidden;
        }

        .fcc-ai-action-card__body {
            padding: 1.15rem 1.2rem;
        }

        .fcc-ai-action-card__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .fcc-ai-action-card__title {
            font-size: 1rem;
            font-weight: 700;
            color: #f6fbff;
            margin: 0;
        }

        .fcc-ai-action-card__note {
            margin: .35rem 0 0;
            font-size: .88rem;
            line-height: 1.55;
            color: rgba(214,225,242,.74);
        }

        .fcc-ai-action-list {
            display: grid;
            gap: .85rem;
        }

        .fcc-ai-action-item,
        .fcc-ai-alert-item {
            border: 1px solid rgba(125,145,185,.16);
            border-radius: .95rem;
            background: rgba(11,18,31,.38);
            padding: .9rem 1rem;
        }

        .fcc-ai-action-item__title,
        .fcc-ai-alert-item__title {
            font-size: .96rem;
            font-weight: 700;
            color: #f6fbff;
            margin-bottom: .35rem;
        }

        .fcc-ai-action-item__text,
        .fcc-ai-alert-item__text {
            font-size: .86rem;
            line-height: 1.55;
            color: rgba(218,229,245,.76);
        }

        .fcc-ai-action-item__footer,
        .fcc-ai-alert-item__footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            margin-top: .75rem;
        }

        .fcc-ai-topic-pills {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: 1rem;
        }

        .fcc-ai-topic-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border-radius: 999px;
            padding: .45rem .8rem;
            background: rgba(70,192,201,.14);
            color: #dff9fb;
            font-size: .8rem;
            border: 1px solid rgba(70,192,201,.24);
        }

        .fcc-ai-topic-pill strong {
            color: #fff;
            font-weight: 700;
        }

        @media (max-width: 991.98px) {
            .fcc-ai-hero__title {
                font-size: 1.65rem;
            }

            .fcc-ai-quicknav,
            .fcc-ai-action-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .fcc-ai-thread {
                max-height: 620px;
            }
        }

        @media (max-width: 767.98px) {
            .fcc-ai-metrics,
            .fcc-ai-quicknav,
            .fcc-ai-action-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 991.98px) {
            .fcc-ai-hero__side {
                flex-basis: 100%;
                min-width: 100%;
                align-items: flex-start;
            }

            .fcc-ai-hero__brand-card {
                width: 100%;
            }

            .fcc-ai-hero__brand-logo {
                max-width: 260px;
                margin-left: 0;
            }

            .fcc-ai-hero__brand-copy {
                text-align: left;
            }

            .fcc-ai-action-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="card fcc-ai-hero" id="fcc-ai-overview">
        <div class="card-body">
            <div class="fcc-ai-hero__top">
                <div class="fcc-ai-hero__main">
                    <div class="fcc-ai-hero__eyebrow"><?= l('fcc_ai.hero_eyebrow') ?></div>
                    <h1 class="fcc-ai-hero__title"><?= l('fcc_ai.hero_title') ?></h1>
                    <p class="fcc-ai-hero__copy"><?= htmlspecialchars($fcc_ai_hero_intro, ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="fcc-ai-hero__side">
                    <div class="fcc-ai-hero__brand-card">
                        <div class="fcc-ai-hero__brand-label">ChatExtreme</div>
                        <img src="<?= htmlspecialchars($fcc_ai_brand_logo_url, ENT_QUOTES, 'UTF-8') ?>" alt="ChatExtreme" class="fcc-ai-hero__brand-logo" />
                        <p class="fcc-ai-hero__brand-copy"><?= l('fcc_ai.hero_brand_copy') ?></p>
                    </div>
                </div>
            </div>

            <div class="fcc-ai-metrics">
                <div class="fcc-ai-metric">
                    <div class="fcc-ai-metric__label"><?= l('fcc_ai.metric.conversations.label') ?></div>
                    <div class="fcc-ai-metric__value"><?= nr($data->stats['conversations']) ?></div>
                    <div class="fcc-ai-metric__hint"><?= l('fcc_ai.metric.conversations.hint') ?></div>
                </div>

                <div class="fcc-ai-metric">
                    <div class="fcc-ai-metric__label"><?= l('fcc_ai.metric.messages.label') ?></div>
                    <div class="fcc-ai-metric__value"><?= nr($data->stats['messages']) ?></div>
                    <div class="fcc-ai-metric__hint"><?= l('fcc_ai.metric.messages.hint') ?></div>
                </div>

                <div class="fcc-ai-metric">
                    <div class="fcc-ai-metric__label"><?= l('fcc_ai.metric.ai_leads.label') ?></div>
                    <div class="fcc-ai-metric__value"><?= nr($data->stats['data_leads']) ?></div>
                    <div class="fcc-ai-metric__hint"><?= l('fcc_ai.metric.ai_leads.hint') ?></div>
                </div>

                <div class="fcc-ai-metric">
                    <div class="fcc-ai-metric__label"><?= l('fcc_ai.metric.window.label') ?></div>
                    <div class="fcc-ai-metric__value"><?= nr((int) ($data->inbox_stats['conversations_30d'] ?? 0)) ?></div>
                    <div class="fcc-ai-metric__hint"><?= l('fcc_ai.metric.window.hint') ?></div>
                </div>

                <div class="fcc-ai-metric">
                    <div class="fcc-ai-metric__label"><?= l('fcc_ai.metric.coach.label') ?></div>
                    <div class="fcc-ai-metric__value"><?= nr((int) ($data->inbox_stats['coach_30d'] ?? 0)) ?></div>
                    <div class="fcc-ai-metric__hint"><?= l('fcc_ai.metric.coach.hint') ?></div>
                </div>

                <div class="fcc-ai-metric">
                    <div class="fcc-ai-metric__label"><?= l('fcc_ai.metric.public.label') ?></div>
                    <div class="fcc-ai-metric__value"><?= nr((int) ($data->inbox_stats['public_30d'] ?? 0)) ?></div>
                    <div class="fcc-ai-metric__hint"><?= l('fcc_ai.metric.public.hint') ?></div>
                </div>

                <div class="fcc-ai-metric">
                    <div class="fcc-ai-metric__label"><?= l('fcc_ai.metric.captured_leads.label') ?></div>
                    <div class="fcc-ai-metric__value"><?= nr((int) ($data->inbox_stats['captured_leads_30d'] ?? 0)) ?></div>
                    <div class="fcc-ai-metric__hint"><?= l('fcc_ai.metric.captured_leads.hint') ?></div>
                </div>

                <div class="fcc-ai-metric">
                    <div class="fcc-ai-metric__label"><?= l('fcc_ai.metric.review.label') ?></div>
                    <div
                        class="fcc-ai-metric__value"
                        id="fcc-ai-review-metric-value"
                        data-review-count="<?= (int) ($data->inbox_stats['negative_feedback_30d'] ?? 0) ?>"
                    ><?= nr((int) ($data->inbox_stats['negative_feedback_30d'] ?? 0)) ?></div>
                    <div class="fcc-ai-metric__hint"><?= l('fcc_ai.metric.review.hint') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="fcc-ai-quicknav">
        <div class="fcc-ai-quicknav__link is-static">
            <span class="fcc-ai-quicknav__eyebrow"><?= l('fcc_ai.quicknav.coach.eyebrow') ?></span>
            <span class="fcc-ai-quicknav__title"><?= l('fcc_ai.quicknav.coach.title') ?></span>
            <span class="fcc-ai-quicknav__copy"><?= l('fcc_ai.quicknav.coach.copy') ?></span>
        </div>

        <div class="fcc-ai-quicknav__link is-static">
            <span class="fcc-ai-quicknav__eyebrow"><?= l('fcc_ai.quicknav.apps.eyebrow') ?></span>
            <span class="fcc-ai-quicknav__title"><?= l('fcc_ai.quicknav.apps.title') ?></span>
            <span class="fcc-ai-quicknav__copy"><?= l('fcc_ai.quicknav.apps.copy_before') ?> <code><?= l('fcc_ai.quicknav.apps.humans') ?></code> <?= l('fcc_ai.quicknav.apps.copy_join') ?> <code><?= l('fcc_ai.quicknav.apps.pets') ?></code> <?= l('fcc_ai.quicknav.apps.copy_after') ?></span>
        </div>

        <div class="fcc-ai-quicknav__link is-static">
            <span class="fcc-ai-quicknav__eyebrow"><?= l('fcc_ai.quicknav.rule.eyebrow') ?></span>
            <span class="fcc-ai-quicknav__title"><?= l('fcc_ai.quicknav.rule.title') ?></span>
            <span class="fcc-ai-quicknav__copy"><?= l('fcc_ai.quicknav.rule.copy') ?></span>
        </div>

        <div class="fcc-ai-quicknav__link is-static">
            <span class="fcc-ai-quicknav__eyebrow"><?= l('fcc_ai.quicknav.tracking.eyebrow') ?></span>
            <span class="fcc-ai-quicknav__title"><?= l('fcc_ai.quicknav.tracking.title') ?></span>
            <span class="fcc-ai-quicknav__copy"><?= l('fcc_ai.quicknav.tracking.copy') ?></span>
        </div>
    </div>

    <div class="fcc-ai-action-grid">
        <div class="fcc-ai-action-card">
            <div class="fcc-ai-action-card__body">
                <div class="fcc-ai-action-card__head">
                    <div>
                        <h2 class="fcc-ai-action-card__title"><?= l('fcc_ai.useful.title') ?></h2>
                        <p class="fcc-ai-action-card__note"><?= l('fcc_ai.useful.subtitle') ?></p>
                    </div>
                    <span class="fcc-ai-inline-chip"><?= nr(count($fcc_ai_useful_items)) ?></span>
                </div>

                <?php if(empty($fcc_ai_useful_items)): ?>
                    <div class="fcc-ai-empty"><?= l('fcc_ai.useful.empty') ?></div>
                <?php else: ?>
                    <div class="fcc-ai-action-list">
                        <?php foreach($fcc_ai_useful_items as $useful_item): ?>
                            <div class="fcc-ai-action-item">
                                <div class="fcc-ai-action-item__title"><?= htmlspecialchars((string) ($useful_item['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="fcc-ai-action-item__text"><?= htmlspecialchars((string) ($useful_item['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if(!empty($useful_item['url'])): ?>
                                    <div class="fcc-ai-action-item__footer">
                                        <span class="small text-muted"><?= l('fcc_ai.useful.open_hint') ?></span>
                                        <a href="<?= htmlspecialchars((string) $useful_item['url'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm"><?= l('fcc_ai.useful.open_action') ?></a>
                                    </div>
                                <?php endif ?>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>

                <?php if(!empty($fcc_ai_rising_topics)): ?>
                    <div class="fcc-ai-topic-pills">
                        <?php foreach(array_slice($fcc_ai_rising_topics, 0, 4) as $topic_row): ?>
                            <span class="fcc-ai-topic-pill">
                                <strong><?= htmlspecialchars((string) ($topic_row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                <span>+<?= nr((int) ($topic_row['delta_total'] ?? 0)) ?></span>
                            </span>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <div class="fcc-ai-action-card">
            <div class="fcc-ai-action-card__body">
                <div class="fcc-ai-action-card__head">
                    <div>
                        <h2 class="fcc-ai-action-card__title"><?= l('fcc_ai.alerts.title') ?></h2>
                        <p class="fcc-ai-action-card__note"><?= l('fcc_ai.alerts.subtitle') ?></p>
                    </div>
                    <span class="fcc-ai-inline-chip"><?= nr(count($fcc_ai_recent_alerts)) ?></span>
                </div>

                <?php if(empty($fcc_ai_recent_alerts)): ?>
                    <div class="fcc-ai-empty"><?= l('fcc_ai.alerts.empty') ?></div>
                <?php else: ?>
                    <div class="fcc-ai-action-list">
                        <?php foreach($fcc_ai_recent_alerts as $alert_row): ?>
                            <div class="fcc-ai-alert-item">
                                <div class="fcc-ai-alert-item__title"><?= htmlspecialchars((string) ($alert_row['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="fcc-ai-alert-item__text"><?= htmlspecialchars((string) ($alert_row['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="fcc-ai-alert-item__footer">
                                    <span class="small text-muted"><?= !empty($alert_row['datetime']) ? \Altum\Date::get((string) $alert_row['datetime'], 2) : '-' ?></span>
                                    <?php if(!empty($alert_row['url'])): ?>
                                        <a href="<?= htmlspecialchars((string) $alert_row['url'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm"><?= l('fcc_ai.alerts.open_action') ?></a>
                                    <?php endif ?>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="row fcc-ai-section-row">
        <div class="col-12 col-xl-7">
            <div class="card h-100" id="fcc-ai-assistants">
                <div class="card-body">
                    <div class="fcc-ai-section-head">
                        <div class="fcc-ai-section-head__copy">
                            <h2 class="h5 mb-0"><?= l('fcc_ai.assistants.title') ?></h2>
                            <p><?= l('fcc_ai.assistants.text') ?></p>
                        </div>

                        <span class="fcc-ai-inline-chip"><?= l('fcc_ai.assistants.chip') ?></span>
                    </div>

                    <div class="fcc-ai-accordion-stack">
                    <?php foreach($fcc_ai_editable_assistant_types as $assistant_type => $definition): ?>
                        <?php
                        $matching_assistant = null;
                        foreach($data->assistants as $assistant_row) {
                            if($assistant_row->assistant_type === $assistant_type) {
                                $matching_assistant = $assistant_row;
                                break;
                            }
                        }
                        ?>

                        <details class="fcc-ai-accordion" <?= $assistant_type === 'product_advisor' ? 'open' : '' ?>>
                            <summary class="fcc-ai-accordion__summary">
                                <div>
                                    <div class="fcc-ai-accordion__title">
                                        <i class="<?= $definition['icon'] ?> fa-fw"></i>
                                        <span><?= $definition['label'] ?></span>
                                    </div>
                                    <div class="fcc-ai-accordion__description"><?= $definition['description'] ?></div>
                                </div>
                            </summary>

                            <div class="fcc-ai-accordion__body">
                            <?php if($matching_assistant): ?>
                                <?php
                                $assistant_tone = (string) ($matching_assistant->settings->tone ?? ($assistant_type === 'coach' ? 'supportive' : 'consultative'));
                                $assistant_language = (string) ($matching_assistant->language ?: ($matching_assistant->settings->language_mode ?? 'auto'));
                                if($assistant_language === '') {
                                    $assistant_language = 'auto';
                                }
                                ?>

                                <form method="post">
                                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                                    <input type="hidden" name="fcc_ai_assistant_id" value="<?= (int) $matching_assistant->fcc_ai_assistant_id ?>" />

                                    <div class="form-row">
                                        <div class="form-group col-12">
                                            <label class="small text-muted mb-1"><?= l('fcc_ai.form.display_name') ?></label>
                                            <input
                                                type="text"
                                                name="display_name"
                                                maxlength="128"
                                                class="form-control"
                                                value="<?= htmlspecialchars((string) ($matching_assistant->display_name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            />
                                        </div>

                                        <div class="form-group col-12 col-md-6">
                                            <label class="small text-muted mb-1"><?= l('fcc_ai.form.language') ?></label>
                                            <select name="language" class="custom-select">
                                                <?php foreach($data->language_options as $language_value => $language_label): ?>
                                                    <option value="<?= htmlspecialchars($language_value, ENT_QUOTES, 'UTF-8') ?>" <?= $assistant_language === $language_value ? 'selected="selected"' : '' ?>>
                                                        <?= htmlspecialchars($language_label, ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>

                                        <div class="form-group col-12 col-md-6">
                                            <label class="small text-muted mb-1"><?= l('fcc_ai.form.tone') ?></label>
                                            <select name="tone" class="custom-select">
                                                <?php foreach($data->tone_options as $tone_value => $tone_label): ?>
                                                    <option value="<?= htmlspecialchars($tone_value, ENT_QUOTES, 'UTF-8') ?>" <?= $assistant_tone === $tone_value ? 'selected="selected"' : '' ?>>
                                                        <?= htmlspecialchars($tone_label, ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>

                                        <div class="form-group col-12">
                                            <label class="small text-muted mb-1"><?= l('fcc_ai.form.persona_prompt') ?></label>
                                            <textarea name="persona_prompt" class="form-control" rows="3" maxlength="4000" placeholder="<?= htmlspecialchars(l('fcc_ai.form.persona_placeholder'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($matching_assistant->persona_prompt ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                        </div>

                                        <div class="form-group col-12">
                                            <label class="small text-muted mb-1"><?= l('fcc_ai.form.rules_prompt') ?></label>
                                            <textarea name="rules_prompt" class="form-control" rows="4" maxlength="6000" placeholder="<?= htmlspecialchars(l('fcc_ai.form.rules_placeholder'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($matching_assistant->rules_prompt ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                                        <div class="custom-control custom-switch mb-2">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="fcc-ai-enabled-<?= (int) $matching_assistant->fcc_ai_assistant_id ?>"
                                                name="is_enabled"
                                                value="1"
                                                <?= (int) ($matching_assistant->is_enabled ?? 0) === 1 ? 'checked="checked"' : '' ?>
                                            />
                                            <label class="custom-control-label" for="fcc-ai-enabled-<?= (int) $matching_assistant->fcc_ai_assistant_id ?>">
                                                <?= l('fcc_ai.form.assistant_enabled') ?>
                                            </label>
                                        </div>

                                        <button type="submit" class="btn btn-primary mb-2"><?= l('fcc_ai.form.save') ?></button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="fcc-ai-empty"><?= l('fcc_ai.assistants.empty') ?></div>
                            <?php endif ?>
                            </div>
                        </details>
                    <?php endforeach ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="fcc-ai-column-stack">
                <div class="card">
                    <div class="card-body">
                        <div class="fcc-ai-panel-stack">
                        <div>
                            <h2 class="h5 mb-2"><?= l('fcc_ai.guide.title') ?></h2>
                            <p class="fcc-ai-panel-note mb-0"><?= l('fcc_ai.guide.subtitle') ?></p>
                        </div>

                        <details class="fcc-ai-disclosure fcc-ai-guide-disclosure">
                            <summary>
                                <span class="fcc-ai-guide-summary">
                                    <span class="fcc-ai-guide-summary__title"><?= l('fcc_ai.guide.coach.title') ?></span>
                                    <span class="fcc-ai-guide-summary__text"><?= l('fcc_ai.guide.coach.summary') ?></span>
                                </span>
                            </summary>
                            <div class="fcc-ai-disclosure__body">
                                <div class="fcc-ai-guide-copy">
                                    <p><?= l('fcc_ai.guide.coach.intro') ?></p>
                                    <ul class="fcc-ai-guide-points">
                                        <li><?= l('fcc_ai.guide.coach.point_1') ?></li>
                                        <li><?= l('fcc_ai.guide.coach.point_2') ?></li>
                                        <li><?= l('fcc_ai.guide.coach.point_3') ?></li>
                                    </ul>
                                    <div class="fcc-ai-guide-tip"><?= l('fcc_ai.guide.coach.tip') ?></div>
                                </div>
                            </div>
                        </details>

                        <details class="fcc-ai-disclosure fcc-ai-guide-disclosure">
                            <summary>
                                <span class="fcc-ai-guide-summary">
                                    <span class="fcc-ai-guide-summary__title"><?= l('fcc_ai.guide.sales.title') ?></span>
                                    <span class="fcc-ai-guide-summary__text"><?= l('fcc_ai.guide.sales.summary') ?></span>
                                </span>
                            </summary>
                            <div class="fcc-ai-disclosure__body">
                                <div class="fcc-ai-guide-copy">
                                    <p><?= l('fcc_ai.guide.sales.intro') ?></p>
                                    <ul class="fcc-ai-guide-points">
                                        <li><?= l('fcc_ai.guide.sales.point_1') ?></li>
                                        <li><?= l('fcc_ai.guide.sales.point_2') ?></li>
                                        <li><?= l('fcc_ai.guide.sales.point_3') ?></li>
                                        <li><?= l('fcc_ai.guide.sales.point_4') ?></li>
                                    </ul>
                                    <div class="fcc-ai-guide-tip"><?= l('fcc_ai.guide.sales.tip') ?></div>
                                </div>
                            </div>
                        </details>

                        <details class="fcc-ai-disclosure fcc-ai-guide-disclosure">
                            <summary>
                                <span class="fcc-ai-guide-summary">
                                    <span class="fcc-ai-guide-summary__title"><?= l('fcc_ai.guide.settings.title') ?></span>
                                    <span class="fcc-ai-guide-summary__text"><?= l('fcc_ai.guide.settings.summary') ?></span>
                                </span>
                            </summary>
                            <div class="fcc-ai-disclosure__body">
                                <div class="fcc-ai-guide-copy">
                                    <p><?= l('fcc_ai.guide.settings.intro') ?></p>
                                    <ul class="fcc-ai-guide-points">
                                        <li><?= l('fcc_ai.guide.settings.point_1') ?></li>
                                        <li><?= l('fcc_ai.guide.settings.point_2') ?></li>
                                        <li><?= l('fcc_ai.guide.settings.point_3') ?></li>
                                        <li><?= l('fcc_ai.guide.settings.point_4') ?></li>
                                    </ul>
                                    <div class="fcc-ai-guide-tip"><?= l('fcc_ai.guide.settings.tip') ?></div>
                                </div>
                            </div>
                        </details>

                        <details class="fcc-ai-disclosure fcc-ai-guide-disclosure">
                            <summary>
                                <span class="fcc-ai-guide-summary">
                                    <span class="fcc-ai-guide-summary__title"><?= l('fcc_ai.guide.inbox.title') ?></span>
                                    <span class="fcc-ai-guide-summary__text"><?= l('fcc_ai.guide.inbox.summary') ?></span>
                                </span>
                            </summary>
                            <div class="fcc-ai-disclosure__body">
                                <div class="fcc-ai-guide-copy">
                                    <p><?= l('fcc_ai.guide.inbox.intro') ?></p>
                                    <ul class="fcc-ai-guide-points">
                                        <li><?= l('fcc_ai.guide.inbox.point_1') ?></li>
                                        <li><?= l('fcc_ai.guide.inbox.point_2') ?></li>
                                        <li><?= l('fcc_ai.guide.inbox.point_3') ?></li>
                                        <li><?= l('fcc_ai.guide.inbox.point_4') ?></li>
                                    </ul>
                                    <div class="fcc-ai-guide-tip"><?= l('fcc_ai.guide.inbox.tip') ?></div>
                                </div>
                            </div>
                        </details>
                        </div>
                    </div>
                </div>

                <div class="card" id="fcc-ai-review">
                    <div class="card-header">
                        <h2 class="h5 mb-0"><?= l('fcc_ai.review.title') ?></h2>
                        <div class="small text-muted mt-1"><?= l('fcc_ai.review.subtitle') ?></div>
                    </div>

                    <div class="card-body">
                        <div class="fcc-ai-review-list" data-fcc-ai-review-list <?= empty($data->review_items) ? 'hidden' : '' ?>>
                            <?php foreach($data->review_items as $review_item): ?>
                                <article
                                    class="fcc-ai-review-item"
                                    data-fcc-ai-review-item
                                    data-feedback-id="<?= (int) ($review_item->feedback_id ?? 0) ?>"
                                    data-conversation-public-id="<?= htmlspecialchars((string) ($review_item->conversation_public_id ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <a
                                        href="<?= htmlspecialchars((string) ($review_item->detail_url ?? url('fcc-ai')), ENT_QUOTES, 'UTF-8') ?>"
                                        class="fcc-ai-review-item__body fcc-ai-conversation-nav-link"
                                        data-conversation-public-id="<?= htmlspecialchars((string) ($review_item->conversation_public_id ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <div class="fcc-ai-review-item__top">
                                            <div class="fcc-ai-review-item__title">
                                                <span><?= htmlspecialchars((string) ($review_item->assistant_label ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="fcc-ai-inline-chip"><?= htmlspecialchars((string) ($review_item->reason_label ?? l('fcc_ai.review.badge')), ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                            <div class="small text-muted"><?= \Altum\Date::get((string) ($review_item->datetime ?? ''), 2) ?></div>
                                        </div>

                                        <div class="fcc-ai-review-item__meta">
                                            <?= htmlspecialchars((string) (($review_item->source_page_label ?? '') ?: ($review_item->scope_label ?? '-')), ENT_QUOTES, 'UTF-8') ?>
                                        </div>

                                        <div class="fcc-ai-review-item__excerpt">
                                            <?= htmlspecialchars((string) (($review_item->message_excerpt ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?>
                                        </div>

                                        <?php if(!empty($review_item->note)): ?>
                                            <div class="fcc-ai-review-item__note"><?= htmlspecialchars((string) $review_item->note, ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif ?>
                                    </a>

                                    <div class="fcc-ai-review-item__actions">
                                        <button
                                            type="button"
                                            class="btn btn-outline-success btn-sm fcc-ai-review-item__resolve"
                                            data-fcc-ai-review-resolve
                                            data-feedback-id="<?= (int) ($review_item->feedback_id ?? 0) ?>"
                                        >
                                            <?= l('feedback_tickets.close_ticket') ?>
                                        </button>
                                    </div>
                                </article>
                            <?php endforeach ?>
                        </div>

                        <div class="fcc-ai-empty" data-fcc-ai-review-empty <?= empty($data->review_items) ? '' : 'hidden' ?>><?= l('fcc_ai.review.empty') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row fcc-ai-section-row">
        <div class="col-12 col-xl-4">
            <div class="card h-100" id="fcc-ai-inbox">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h2 class="h5 mb-0"><?= sprintf(l('fcc_ai.inbox.title'), (int) ($data->conversation_window_days ?? 30)) ?></h2>
                        <div class="small text-muted mt-1"><?= l('fcc_ai.inbox.subtitle') ?></div>
                    </div>
                </div>

                <div class="card-body border-bottom">
                    <form action="<?= url('fcc-ai') ?>" method="get" class="fcc-ai-filter-bar">
                        <div class="fcc-ai-filter-grid">
                            <div class="fcc-ai-filter-field">
                                <label class="fcc-ai-filter-label"><?= l('fcc_ai.filters.assistant') ?></label>
                                <select name="assistant_type" class="custom-select">
                                    <option value=""><?= l('fcc_ai.filters.all_assistants') ?></option>
                                    <?php foreach($data->assistant_types as $assistant_type => $assistant_definition): ?>
                                        <option value="<?= htmlspecialchars($assistant_type, ENT_QUOTES, 'UTF-8') ?>" <?= ($fcc_ai_filters['assistant_type'] ?? '') === $assistant_type ? 'selected="selected"' : '' ?>>
                                            <?= htmlspecialchars((string) ($assistant_definition['label'] ?? $assistant_type), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="fcc-ai-filter-field">
                                <label class="fcc-ai-filter-label"><?= l('fcc_ai.filters.scope') ?></label>
                                <select name="scope" class="custom-select">
                                    <?php foreach(($data->scope_options ?? []) as $scope_value => $scope_label): ?>
                                        <option value="<?= htmlspecialchars((string) $scope_value, ENT_QUOTES, 'UTF-8') ?>" <?= ($fcc_ai_filters['scope'] ?? '') === (string) $scope_value ? 'selected="selected"' : '' ?>>
                                            <?= htmlspecialchars((string) $scope_label, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="fcc-ai-filter-field">
                                <label class="fcc-ai-filter-label"><?= l('fcc_ai.filters.lead_status') ?></label>
                                <select name="lead_status" class="custom-select">
                                    <?php foreach(($data->lead_status_options ?? []) as $lead_status_value => $lead_status_label): ?>
                                        <option value="<?= htmlspecialchars((string) $lead_status_value, ENT_QUOTES, 'UTF-8') ?>" <?= ($fcc_ai_filters['lead_status'] ?? '') === (string) $lead_status_value ? 'selected="selected"' : '' ?>>
                                            <?= htmlspecialchars((string) $lead_status_label, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="fcc-ai-filter-actions">
                            <button type="submit" class="btn btn-primary"><?= l('fcc_ai.filters.apply') ?></button>
                            <a href="<?= url('fcc-ai') ?>" class="btn btn-outline-secondary"><?= l('fcc_ai.filters.reset') ?></a>
                            <?php if($fcc_ai_has_filters): ?>
                                <span class="fcc-ai-inline-chip fcc-ai-filter-status"><?= l('fcc_ai.filters.active') ?></span>
                            <?php endif ?>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <?php if(empty($data->conversations)): ?>
                        <div class="fcc-ai-empty"><?= sprintf(l('fcc_ai.inbox.empty'), (int) ($data->conversation_window_days ?? 30)) ?></div>
                    <?php else: ?>
                        <div class="fcc-ai-conversation-list">
                            <?php foreach($data->conversations as $conversation): ?>
                                <?php
                                $row_url = $fcc_ai_build_hub_url(['conversation' => (string) ($conversation->public_id ?? '')]);
                                $is_selected = !empty($data->selected_conversation) && (string) ($data->selected_conversation->public_id ?? '') === (string) ($conversation->public_id ?? '');
                                ?>
                                <a href="<?= $row_url ?>" class="fcc-ai-conversation-item fcc-ai-conversation-nav-link <?= $is_selected ? 'is-selected' : '' ?>" data-conversation-public-id="<?= htmlspecialchars((string) ($conversation->public_id ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="fcc-ai-conversation-item__top">
                                        <div class="fcc-ai-conversation-item__title">
                                            <span><?= htmlspecialchars((string) ($conversation->assistant_label ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="fcc-ai-inline-chip"><?= htmlspecialchars((string) ($conversation->scope_label ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if((string) ($conversation->lead_status ?? '') === 'captured'): ?>
                                                <span class="fcc-ai-inline-chip"><?= l('fcc_ai.inbox.lead_chip') ?></span>
                                            <?php endif ?>
                                            <?php if((int) ($conversation->negative_feedback_total ?? 0) > 0): ?>
                                                <span class="fcc-ai-inline-chip"><?= l('fcc_ai.inbox.review_chip') ?> <?= nr((int) ($conversation->negative_feedback_total ?? 0)) ?></span>
                                            <?php endif ?>
                                        </div>

                                        <div class="fcc-ai-conversation-item__time">
                                            <?= \Altum\Date::get((string) (($conversation->last_message_at ?? '') ?: ($conversation->datetime ?? '')), 2) ?>
                                        </div>
                                    </div>

                                    <div class="fcc-ai-conversation-item__source">
                                        <?= htmlspecialchars((string) (($conversation->source_page_label ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>

                                    <div class="fcc-ai-conversation-item__preview <?= (string) ($conversation->latest_message_role ?? '') === 'user' ? 'text-primary' : '' ?>">
                                        <?= htmlspecialchars((string) (($conversation->latest_message_preview ?? '') ?: l('fcc_ai.inbox.no_message')), ENT_QUOTES, 'UTF-8') ?>
                                    </div>

                                    <div class="fcc-ai-conversation-item__meta">
                                        <?= nr((int) ($conversation->total_user_messages ?? 0)) ?> <?= l('fcc_ai.common.user') ?> /
                                        <?= nr((int) ($conversation->total_assistant_messages ?? 0)) ?> <?= l('fcc_ai.common.ai') ?>
                                        · <?= htmlspecialchars((string) ($conversation->public_id ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </a>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8 fcc-ai-detail-column" id="fcc-ai-detail-column">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-start">
                    <div>
                        <h2 class="h5 mb-0"><?= l('fcc_ai.details.title') ?></h2>
                        <div class="small text-muted mt-1"><?= l('fcc_ai.details.subtitle') ?></div>
                    </div>
                </div>

                <div class="card-body">
                    <?php if(empty($data->selected_conversation)): ?>
                        <div class="text-muted"><?= l('fcc_ai.details.empty') ?></div>
                    <?php else: ?>
                        <?php
                        $selected_conversation = $data->selected_conversation;
                        $selected_conversation_meta = is_object($selected_conversation->meta ?? null) ? $selected_conversation->meta : (object) [];
                        $selected_link = $data->selected_conversation_link ?? null;
                        $selected_blog_post = $data->selected_conversation_blog_post ?? null;
                        $lead = $data->selected_conversation_lead ?? null;
                        $selected_assistant_label = trim((string) ($selected_conversation->assistant_label ?? ''));
                        $selected_scope_label = trim((string) ($selected_conversation->scope_label ?? ''));
                        $selected_conversation_is_coach = fcc_ai_is_coach_assistant((string) ($selected_conversation->assistant_type ?? ''));
                        $selected_source_title = trim((string) ($selected_conversation_meta->source_page_title ?? ''));
                        if($selected_source_title === '') {
                            $selected_source_title = trim((string) ($selected_conversation_meta->source_context ?? ''));
                        }
                        if($selected_source_title === '') {
                            $selected_source_title = $selected_assistant_label !== '' ? $selected_assistant_label : '-';
                        }
                        $show_scope_chip = $selected_scope_label !== '' && mb_strtolower($selected_scope_label, 'UTF-8') !== mb_strtolower($selected_assistant_label, 'UTF-8');
                        $blog_url = null;
                        if($selected_blog_post) {
                            $blog_url = fcc_ai_get_blog_post_public_url((object) [
                                'language' => $selected_blog_post->language ?? '',
                                'url' => $selected_blog_post->url ?? '',
                            ], '');
                        }
                        ?>

                        <div class="fcc-ai-detail-hero">
                            <div class="fcc-ai-detail-hero__top">
                                <div class="fcc-ai-detail-hero__copy">
                                    <div class="fcc-ai-detail-hero__eyebrow"><?= htmlspecialchars($selected_assistant_label, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="fcc-ai-detail-hero__title"><?= htmlspecialchars($selected_source_title, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="fcc-ai-detail-hero__id"><?= htmlspecialchars((string) ($selected_conversation->public_id ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>

                                <div class="fcc-ai-detail-hero__pills">
                                    <?php if($show_scope_chip): ?>
                                        <span class="fcc-ai-detail-pill is-muted"><?= htmlspecialchars($selected_scope_label, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif ?>
                                    <?php if((string) ($selected_conversation->lead_status ?? '') === 'captured'): ?>
                                        <span class="fcc-ai-detail-pill is-success"><?= l('fcc_ai.details.lead_saved') ?></span>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>

                        <div class="fcc-ai-detail-meta">
                            <div class="fcc-ai-detail-meta__card">
                                <div class="fcc-ai-detail-meta__label"><?= l('fcc_ai.details.last_activity') ?></div>
                                <div class="fcc-ai-detail-meta__value"><?= \Altum\Date::get((string) (($selected_conversation->last_message_at ?? '') ?: ($selected_conversation->datetime ?? '')), 2) ?></div>
                            </div>

                            <div class="fcc-ai-detail-meta__card">
                                <div class="fcc-ai-detail-meta__label"><?= l('fcc_ai.details.messages') ?></div>
                                <div class="fcc-ai-detail-meta__split">
                                    <span><strong><?= nr((int) ($selected_conversation->total_user_messages ?? 0)) ?></strong> <?= l('fcc_ai.common.user') ?></span>
                                    <span><strong><?= nr((int) ($selected_conversation->total_assistant_messages ?? 0)) ?></strong> <?= l('fcc_ai.common.ai') ?></span>
                                </div>
                            </div>

                            <div class="fcc-ai-detail-meta__card">
                                <div class="fcc-ai-detail-meta__label"><?= l('fcc_ai.details.source') ?></div>
                                <div class="fcc-ai-detail-meta__value"><?= htmlspecialchars((string) (($selected_conversation_meta->source_page_title ?? '') ?: ($selected_conversation_meta->source_context ?? '-') ), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>

                            <div class="fcc-ai-detail-meta__card is-wide">
                                <div class="fcc-ai-detail-meta__label"><?= l('fcc_ai.details.url') ?></div>
                                <div class="fcc-ai-detail-meta__value is-mono"><?= htmlspecialchars((string) ($selected_conversation_meta->source_page_url ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>

                        <?php if($selected_link || $selected_blog_post || $lead): ?>
                            <div class="fcc-ai-detail-actions">
                                    <?php if($selected_link): ?>
                                        <a href="<?= url('link/' . (int) $selected_link->link_id . '?tab=blocks') ?>" class="btn btn-outline-primary btn-sm mr-2 mb-2">
                                            <?= l('fcc_ai.details.open_app') ?>
                                        </a>
                                    <?php endif ?>

                                    <?php if($blog_url): ?>
                                        <a href="<?= $blog_url ?>" target="_blank" class="btn btn-outline-secondary btn-sm mr-2 mb-2">
                                            <?= l('fcc_ai.details.open_blog') ?>
                                        </a>
                                    <?php endif ?>

                                    <?php if($lead && !empty($selected_conversation->latest_datum_id)): ?>
                                        <a href="<?= url('data?datum_id=' . (int) $selected_conversation->latest_datum_id) ?>" class="btn btn-outline-success btn-sm mb-2">
                                            <?= l('fcc_ai.details.open_contact') ?>
                                        </a>
                                    <?php endif ?>
                            </div>
                        <?php endif ?>

                        <?php if($lead): ?>
                            <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center">
                                <div class="mr-3">
                                    <div class="font-weight-bold mb-1"><?= l('fcc_ai.details.lead_title') ?></div>
                                    <div class="small">
                                        <?= htmlspecialchars((string) (($lead->name ?: $lead->email) ?: $lead->phone), ENT_QUOTES, 'UTF-8') ?>
                                        · <?= htmlspecialchars((string) ($lead->lead_type ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        · <?= l('fcc_ai.common.score') ?> <?= nr((int) ($lead->lead_score ?? 0)) ?>
                                    </div>
                                </div>
                                <div class="small text-muted">
                                    <?= \Altum\Date::get((string) ($lead->datetime ?? ''), 2) ?>
                                </div>
                            </div>
                        <?php endif ?>

                        <div class="fcc-ai-thread-panel">
                            <div class="fcc-ai-thread">
                                <?php foreach(($data->selected_messages ?? []) as $message): ?>
                                    <?php
                                    $message_role = in_array((string) ($message['role'] ?? ''), ['assistant', 'user', 'system'], true) ? (string) $message['role'] : 'assistant';
                                    $message_meta = is_array($message['meta'] ?? null) ? $message['meta'] : [];
                                    $message_suggestions = is_array($message_meta['knowledge_suggestions'] ?? null) ? $message_meta['knowledge_suggestions'] : [];
                                    $message_feedback = is_array($message['feedback'] ?? null) ? $message['feedback'] : [];
                                    ?>
                                    <div class="fcc-ai-thread__message is-<?= htmlspecialchars($message_role, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="fcc-ai-thread__bubble">
                                            <div class="fcc-ai-thread__meta">
                                                <span>
                                                    <?php if($message_role === 'assistant'): ?>
                                                        <?= l('fcc_ai.common.ai') ?>
                                                    <?php elseif($message_role === 'user'): ?>
                                                        <?= l('fcc_ai.common.user') ?>
                                                    <?php else: ?>
                                                        <?= l('fcc_ai.common.system') ?>
                                                    <?php endif ?>

                                                    <?php if($fcc_ai_is_admin && (!empty($message_meta['provider']) || !empty($message_meta['model']))): ?>
                                                        <?php
                                                        $message_provider_label = trim((string) ($message_meta['provider'] ?? ''));
                                                        $message_model_label = trim((string) ($message_meta['model'] ?? ''));

                                                        if($message_model_label !== '') {
                                                            $message_model_label = preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $message_model_label);
                                                        }

                                                        $message_model_badge = trim($message_provider_label . ' ' . $message_model_label);
                                                        ?>
                                                        <?php if($message_model_badge !== ''): ?>
                                                            · <?= htmlspecialchars($message_model_badge, ENT_QUOTES, 'UTF-8') ?>
                                                        <?php endif ?>
                                                    <?php endif ?>
                                                </span>

                                                <span><?= \Altum\Date::get((string) ($message['datetime'] ?? ''), 2) ?></span>
                                            </div>

                                            <div class="fcc-ai-thread__content"><?= htmlspecialchars((string) ($message['content'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

                                            <?php if($message_role === 'assistant' && !empty($message_suggestions)): ?>
                                                <div class="fcc-ai-thread__suggestions">
                                                    <?php foreach(array_slice($message_suggestions, 0, 5) as $suggestion): ?>
                                                        <?php if(empty($suggestion['title']) || empty($suggestion['url'])) continue; ?>
                                                        <a href="<?= htmlspecialchars((string) $suggestion['url'], ENT_QUOTES, 'UTF-8') ?>" class="fcc-ai-thread__suggestion" <?= str_starts_with((string) $suggestion['url'], SITE_URL) ? null : 'target="_blank"' ?>>
                                                            <?= htmlspecialchars((string) $suggestion['title'], ENT_QUOTES, 'UTF-8') ?>
                                                        </a>
                                                    <?php endforeach ?>
                                                </div>
                                            <?php endif ?>

                                            <?php if(
                                                $message_role === 'assistant'
                                                && (!$selected_conversation_is_coach || $fcc_ai_is_admin)
                                                && (((int) ($message_feedback['positive_total'] ?? 0)) > 0 || ((int) ($message_feedback['negative_total'] ?? 0)) > 0)
                                            ): ?>
                                                <div class="fcc-ai-thread__feedback">
                                                    <?php if((int) ($message_feedback['positive_total'] ?? 0) > 0): ?>
                                                        <span class="fcc-ai-thread__feedback-chip is-positive">
                                                            <i class="fas fa-thumbs-up fa-fw"></i>
                                                            <?= nr((int) ($message_feedback['positive_total'] ?? 0)) ?>
                                                        </span>
                                                    <?php endif ?>

                                                    <?php if((int) ($message_feedback['negative_total'] ?? 0) > 0): ?>
                                                        <span class="fcc-ai-thread__feedback-chip is-negative">
                                                            <i class="fas fa-thumbs-down fa-fw"></i>
                                                            <?= nr((int) ($message_feedback['negative_total'] ?? 0)) ?>
                                                            <?php if(!empty($message_feedback['viewer_reason'])): ?>
                                                                · <?= htmlspecialchars(fcc_ai_get_feedback_reason_label((string) $message_feedback['viewer_reason'], \Altum\Language::$code), ENT_QUOTES, 'UTF-8') ?>
                                                            <?php endif ?>
                                                        </span>
                                                    <?php endif ?>
                                                </div>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card" id="fcc-ai-leads">
        <div class="card-header">
            <h2 class="h5 mb-0"><?= l('fcc_ai.leads.title') ?></h2>
        </div>

        <div class="card-body">
            <?php if(empty($data->recent_leads)): ?>
                <div class="fcc-ai-empty"><?= l('fcc_ai.leads.empty') ?></div>
            <?php else: ?>
                <div class="fcc-ai-recent-leads">
                    <?php foreach($data->recent_leads as $lead): ?>
                        <div class="fcc-ai-recent-lead">
                            <div>
                                <div class="font-weight-bold mb-1"><?= htmlspecialchars((string) (($lead->name ?: $lead->email) ?: $lead->phone), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="fcc-ai-recent-lead__meta">
                                    <?= htmlspecialchars((string) $lead->lead_type, ENT_QUOTES, 'UTF-8') ?>
                                    · <?= htmlspecialchars((string) ($lead->preferred_contact_channel ?: '-'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="font-weight-bold"><?= l('fcc_ai.common.score') ?> <?= nr((int) ($lead->lead_score ?? 0)) ?></div>
                                <div class="fcc-ai-recent-lead__meta"><?= \Altum\Date::get((string) ($lead->datetime ?? ''), 2) ?></div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>

<script>
    (function() {
        let fccAiDetailRequestController = null;

        const getConversationPublicIdFromUrl = (value) => {
            try {
                const targetUrl = new URL(value, window.location.origin);
                return targetUrl.searchParams.get('conversation') || '';
            } catch (error) {
                return '';
            }
        };

        const syncConversationSelection = (activeConversationId) => {
            document.querySelectorAll('.fcc-ai-conversation-item').forEach(item => {
                const itemConversationId = item.getAttribute('data-conversation-public-id') || getConversationPublicIdFromUrl(item.href || '');
                item.classList.toggle('is-selected', activeConversationId !== '' && itemConversationId === activeConversationId);
            });
        };

        const replaceDetailPanel = (html, urlToSync) => {
            const parser = new DOMParser();
            const nextDocument = parser.parseFromString(html, 'text/html');
            const currentDetailColumn = document.querySelector('#fcc-ai-detail-column');
            const nextDetailColumn = nextDocument.querySelector('#fcc-ai-detail-column');

            if(!currentDetailColumn || !nextDetailColumn) {
                window.location.href = urlToSync;
                return;
            }

            currentDetailColumn.innerHTML = nextDetailColumn.innerHTML;
            currentDetailColumn.classList.remove('is-loading');
            syncConversationSelection(getConversationPublicIdFromUrl(urlToSync));
        };

        const loadConversationDetails = async (urlToLoad, pushHistory = true) => {
            const detailColumn = document.querySelector('#fcc-ai-detail-column');

            if(!detailColumn) {
                window.location.href = urlToLoad;
                return;
            }

            if(fccAiDetailRequestController) {
                fccAiDetailRequestController.abort();
            }

            fccAiDetailRequestController = new AbortController();
            detailColumn.classList.add('is-loading');

            try {
                const response = await fetch(urlToLoad, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: fccAiDetailRequestController.signal
                });

                if(!response.ok) {
                    throw new Error('Failed to load conversation details');
                }

                const responseHtml = await response.text();
                replaceDetailPanel(responseHtml, urlToLoad);

                if(pushHistory) {
                    window.history.pushState({fccAiConversationUrl: urlToLoad}, '', urlToLoad);
                }
            } catch (error) {
                if(error.name === 'AbortError') {
                    return;
                }

                detailColumn.classList.remove('is-loading');
                window.location.href = urlToLoad;
            }
        };

        const shouldHandleConversationClick = (event) => {
            return !(
                event.defaultPrevented ||
                event.button !== 0 ||
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey
            );
        };

        const initFccAiConversationLoader = () => {
            const conversationScope = document.querySelector('.fcc-ai-shell');

            if(!conversationScope) {
                return;
            }

            conversationScope.addEventListener('click', (event) => {
                const conversationItem = event.target.closest('.fcc-ai-conversation-nav-link');

                if(!conversationItem || !shouldHandleConversationClick(event)) {
                    return;
                }

                event.preventDefault();

                const targetUrl = conversationItem.href;
                const targetConversationId = conversationItem.getAttribute('data-conversation-public-id') || getConversationPublicIdFromUrl(targetUrl);

                if(targetConversationId !== '') {
                    syncConversationSelection(targetConversationId);
                }

                loadConversationDetails(targetUrl, true);
            });

            window.addEventListener('popstate', () => {
                loadConversationDetails(window.location.href, false);
            });
        };

        const initFccAiGuideAccordion = () => {
            const guideBlocks = Array.from(document.querySelectorAll('.fcc-ai-guide-disclosure'));

            if(guideBlocks.length < 2) {
                return;
            }

            guideBlocks.forEach(currentBlock => {
                currentBlock.addEventListener('toggle', () => {
                    if(!currentBlock.open) {
                        return;
                    }

                    guideBlocks.forEach(otherBlock => {
                        if(otherBlock !== currentBlock) {
                            otherBlock.open = false;
                        }
                    });
                });
            });
        };

        const initFccAiReviewResolve = () => {
            const shell = document.querySelector('.fcc-ai-shell');

            if(!shell) {
                return;
            }

            const resolveUrl = shell.dataset.reviewResolveUrl || '';
            const csrfToken = shell.dataset.csrfToken || '';

            if(!resolveUrl || !csrfToken) {
                return;
            }

            const syncReviewEmptyState = () => {
                const reviewList = shell.querySelector('[data-fcc-ai-review-list]');
                const reviewEmpty = shell.querySelector('[data-fcc-ai-review-empty]');

                if(!reviewList || !reviewEmpty) {
                    return;
                }

                const hasItems = reviewList.querySelectorAll('[data-fcc-ai-review-item]').length > 0;
                reviewList.hidden = !hasItems;
                reviewEmpty.hidden = hasItems;
            };

            const decrementReviewMetric = () => {
                const metricValue = document.getElementById('fcc-ai-review-metric-value');

                if(!metricValue) {
                    return;
                }

                const currentValue = Number(metricValue.dataset.reviewCount || metricValue.textContent || 0);
                const nextValue = Math.max(0, currentValue - 1);
                metricValue.dataset.reviewCount = String(nextValue);
                metricValue.textContent = String(nextValue);
            };

            const showResolveError = (message) => {
                if(typeof window.display_notifications === 'function') {
                    window.display_notifications(message, 'error');
                    return;
                }

                window.alert(message);
            };

            shell.addEventListener('click', async (event) => {
                const resolveButton = event.target.closest('[data-fcc-ai-review-resolve]');

                if(!resolveButton) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const feedbackId = Number(resolveButton.getAttribute('data-feedback-id') || 0);
                const reviewItem = resolveButton.closest('[data-fcc-ai-review-item]');

                if(feedbackId <= 0 || !reviewItem || resolveButton.disabled) {
                    return;
                }

                const currentConversationId = new URL(window.location.href, window.location.origin).searchParams.get('conversation') || '';
                const reviewConversationId = reviewItem.getAttribute('data-conversation-public-id') || '';
                const originalLabel = resolveButton.innerHTML;

                resolveButton.disabled = true;
                resolveButton.classList.add('disabled', 'container-disabled-simple');
                resolveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                try {
                    const response = await fetch(resolveUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            token: csrfToken,
                            feedback_id: String(feedbackId)
                        }).toString()
                    });

                    const result = await response.json();

                    if(!response.ok || result.status !== 'success') {
                        throw new Error(Array.isArray(result.message) ? result.message.join(' ') : (result.message || 'Signal nije označen kao riješen.'));
                    }

                    reviewItem.remove();
                    decrementReviewMetric();
                    syncReviewEmptyState();

                    if(reviewConversationId !== '' && currentConversationId === reviewConversationId) {
                        loadConversationDetails(window.location.href, false);
                    }
                } catch(error) {
                    resolveButton.disabled = false;
                    resolveButton.classList.remove('disabled', 'container-disabled-simple');
                    resolveButton.innerHTML = originalLabel;
                    showResolveError(error.message || 'Signal nije označen kao riješen.');
                }
            });
        };

        if(document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initFccAiGuideAccordion();
                initFccAiConversationLoader();
                initFccAiReviewResolve();
            });
        } else {
            initFccAiGuideAccordion();
            initFccAiConversationLoader();
            initFccAiReviewResolve();
        }
    })();
</script>
