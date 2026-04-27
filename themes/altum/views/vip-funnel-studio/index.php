<?php defined('ALTUMCODE') || die() ?>

<?php
$e = static function($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$payload = is_array($data->payload ?? null) ? $data->payload : [];
$studio = is_array($data->studio ?? null) ? $data->studio : [];
$selected_funnel_id = (int) ($data->selected_funnel_id ?? ($studio['funnel_row']->vip_funnel_id ?? 0));
$phase_definitions = array_values(vip_funnel_get_phase_definitions());
$logo_url = ASSETS_FULL_URL . 'images/vip-funnel-logo-wide.png';
$analytics = is_array($studio['analytics'] ?? null) ? $studio['analytics'] : [
    'views' => 0,
    'submits' => 0,
    'advances' => 0,
    'leads' => 0,
    'best_step' => null,
    'ab' => ['a_views' => 0, 'b_views' => 0, 'a_submits' => 0, 'b_submits' => 0, 'winner' => ''],
];
?>

<style>
    .vf-core {
        --vf-bg: #09111d;
        --vf-panel: #101a29;
        --vf-panel-soft: #152133;
        --vf-panel-soft-2: #1a2638;
        --vf-border: rgba(255, 255, 255, 0.08);
        --vf-text: #eef4ff;
        --vf-text-soft: rgba(233, 241, 255, 0.72);
        --vf-accent: #67d8c9;
        --vf-accent-2: #f4c34d;
        --vf-danger: #ff7a93;
        --vf-shadow: 0 1.6rem 3rem rgba(2, 8, 23, 0.26);
        padding-bottom: 3rem;
    }

    .vf-hero {
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(300px, .85fr);
        gap: 1.25rem;
        align-items: center;
        padding: 1.45rem 1.6rem;
        border-radius: 1.65rem;
        border: 1px solid var(--vf-border);
        background:
            radial-gradient(460px 180px at 0% 0%, rgba(103, 216, 201, 0.14), transparent 65%),
            radial-gradient(420px 180px at 100% 0%, rgba(106, 153, 255, 0.14), transparent 60%),
            linear-gradient(180deg, #0e1725, #0d1522);
        box-shadow: var(--vf-shadow);
        margin-bottom: 1.2rem;
    }

    .vf-hero__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .42rem .8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.06);
        color: rgba(240, 246, 255, 0.82);
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .vf-hero__title {
        margin: .85rem 0 0;
        color: #ffffff;
        font-size: clamp(2rem, 3.5vw, 3rem);
        line-height: 1;
        font-weight: 900;
    }

    .vf-hero__copy {
        margin-top: .95rem;
        max-width: 52rem;
        color: var(--vf-text-soft);
        font-size: 1rem;
        line-height: 1.7;
    }

    .vf-hero__logo {
        display: flex;
        justify-content: flex-end;
    }

    .vf-hero__logo-shell {
        width: min(100%, 370px);
        padding: 1rem 1.1rem;
        border-radius: 1.3rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background:
            radial-gradient(220px 100px at 20% 30%, rgba(103, 216, 201, 0.08), transparent 70%),
            linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    .vf-hero__logo-shell img {
        display: block;
        width: 100%;
        height: auto;
    }

    .vf-topbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1rem;
    }

    .vf-tabs {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: .55rem;
    }

    .vf-tab {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .72rem 1rem;
        border-radius: 999px;
        border: 1px solid var(--vf-border);
        background: rgba(255, 255, 255, 0.03);
        color: var(--vf-text-soft);
        font-weight: 800;
        cursor: pointer;
        transition: .18s ease;
    }

    .vf-tab:hover,
    .vf-tab.is-active {
        background: rgba(103, 216, 201, 0.15);
        color: var(--vf-text);
        border-color: rgba(103, 216, 201, 0.32);
    }

    .vf-step-tab {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: .72rem;
        min-width: 0;
        max-width: 18rem;
        min-height: 4rem;
        padding: .78rem 1rem;
        border-radius: 1.2rem;
        border: 1px solid rgba(255, 255, 255, 0.07);
        background:
            radial-gradient(180px 100px at 0% 0%, rgba(103, 216, 201, 0.08), transparent 72%),
            linear-gradient(180deg, rgba(255,255,255,0.045), rgba(255,255,255,0.02));
        color: var(--vf-text-soft);
        font-weight: 800;
        cursor: pointer;
        transition: .18s ease, box-shadow .2s ease, border-color .2s ease;
        user-select: none;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .vf-step-tab:hover,
    .vf-step-tab.is-active {
        color: var(--vf-text);
        border-color: rgba(103, 216, 201, 0.34);
        background:
            radial-gradient(180px 100px at 0% 0%, rgba(103, 216, 201, 0.14), transparent 72%),
            linear-gradient(180deg, rgba(103, 216, 201, 0.09), rgba(255,255,255,0.02));
        box-shadow: 0 .8rem 1.8rem rgba(2, 8, 23, 0.18);
    }

    .vf-step-tab.is-active {
        box-shadow:
            0 1rem 2rem rgba(2, 8, 23, 0.22),
            0 0 0 1px rgba(103, 216, 201, 0.12);
    }

    .vf-step-tab.is-dragging {
        opacity: .55;
        transform: scale(.98);
    }

    .vf-step-tab.is-drop-before {
        box-shadow: inset 3px 0 0 rgba(103, 216, 201, 0.95);
    }

    .vf-step-tab.is-drop-after {
        box-shadow: inset -3px 0 0 rgba(103, 216, 201, 0.95);
    }

    .vf-step-tab__index {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 1.85rem;
        height: 1.85rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.94);
        font-size: .8rem;
        font-weight: 900;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
    }

    .vf-step-tab__main {
        display: grid;
        gap: .18rem;
        min-width: 0;
        flex: 1 1 auto;
    }

    .vf-step-tab__phase {
        display: inline-flex;
        align-items: center;
        gap: .28rem;
        width: fit-content;
        max-width: 100%;
        padding: .22rem .5rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.06);
        color: rgba(236, 243, 255, 0.74);
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: .05em;
        text-transform: uppercase;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .vf-step-tab__label {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #fff;
        font-size: .94rem;
        line-height: 1.15;
        font-weight: 900;
    }

    .vf-step-tab__grip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 1.65rem;
        height: 1.65rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.04);
        color: rgba(255, 255, 255, 0.48);
        font-size: .92rem;
        line-height: 1;
    }

    .vf-tab--add {
        min-width: 3.4rem;
        min-height: 3.4rem;
        padding-inline: 1.05rem;
        border-radius: 1.15rem;
        font-size: 1.28rem;
        font-weight: 900;
        background:
            radial-gradient(160px 90px at 0% 0%, rgba(103, 216, 201, 0.12), transparent 72%),
            linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
    }

    .vf-tab--add:hover {
        transform: translateY(-1px) scale(1.02);
    }

    .vf-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .7rem;
    }

    .vf-bottombar {
        margin-top: 1rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        padding: 1rem 1.15rem;
        border-radius: 1.25rem;
        border: 1px solid rgba(103, 216, 201, 0.16);
        background:
            radial-gradient(220px 120px at 0% 0%, rgba(103, 216, 201, 0.08), transparent 70%),
            linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.02));
        box-shadow: var(--vf-shadow);
    }

    .vf-bottombar__hint {
        max-width: 42rem;
    }

    .vf-bottombar__eyebrow {
        color: var(--vf-accent-2);
        font-size: .78rem;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .vf-bottombar__text {
        margin-top: .2rem;
        color: var(--vf-text-soft);
        font-size: .92rem;
        line-height: 1.55;
    }

    .vf-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        padding: .78rem 1rem;
        border-radius: 1rem;
        border: 1px solid var(--vf-border);
        background: rgba(255, 255, 255, 0.05);
        color: var(--vf-text);
        font-weight: 800;
        cursor: pointer;
        transition: .18s ease;
    }

    .vf-btn:hover {
        transform: translateY(-1px);
        text-decoration: none;
        color: #fff;
    }

    .vf-btn--primary {
        background: linear-gradient(180deg, #7be2d5, #55c8b7);
        border-color: rgba(103, 216, 201, 0.6);
        color: #0f172a;
    }

    .vf-btn--ghost {
        background: transparent;
    }

    .vf-btn--danger {
        background: rgba(255, 122, 147, 0.1);
        border-color: rgba(255, 122, 147, 0.28);
        color: #ffc0cf;
    }

    .vf-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.24fr) minmax(320px, .9fr);
        gap: 1rem;
        align-items: start;
    }

    .vf-card {
        border-radius: 1.45rem;
        border: 1px solid var(--vf-border);
        background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.015));
        box-shadow: var(--vf-shadow);
        overflow: hidden;
    }

    .vf-card__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .8rem;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .vf-card__title {
        color: #fff;
        font-size: 1.08rem;
        line-height: 1.2;
        font-weight: 900;
        margin: 0;
    }

    .vf-card__sub {
        margin-top: .22rem;
        color: var(--vf-text-soft);
        font-size: .88rem;
        line-height: 1.5;
    }

    .vf-card__body {
        padding: 1rem 1.15rem 1.15rem;
    }

    .vf-workspace {
        display: grid;
        gap: 1rem;
    }

    .vf-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .8rem;
        margin-bottom: 1rem;
    }

    .vf-kpi {
        padding: .95rem 1rem;
        border-radius: 1.1rem;
        border: 1px solid rgba(255,255,255,0.07);
        background: rgba(255,255,255,0.03);
    }

    .vf-kpi__label {
        color: rgba(226, 235, 247, 0.66);
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .vf-kpi__value {
        margin-top: .35rem;
        color: #fff;
        font-size: 1.6rem;
        line-height: 1;
        font-weight: 900;
    }

    .vf-two {
        display: grid;
        grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
        gap: 1rem;
    }

    .vf-three {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .vf-stack {
        display: grid;
        gap: .85rem;
    }

    .vf-step-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .8rem;
        margin-bottom: .2rem;
    }

    .vf-step-toolbar__meta {
        color: var(--vf-text-soft);
        font-size: .9rem;
        line-height: 1.55;
    }

    .vf-section-label {
        color: rgba(244, 195, 77, 0.95);
        font-weight: 900;
        font-size: .82rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: .65rem;
    }

    .vf-inline {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
    }

    .vf-block-adder {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: .75rem;
        align-items: end;
        margin-bottom: .9rem;
    }

    .vf-block-adder select {
        width: 100%;
        min-width: 0;
    }

    .vf-block-adder .vf-btn {
        white-space: nowrap;
    }

    .vf-field {
        display: grid;
        gap: .42rem;
    }

    .vf-field label {
        color: rgba(236, 243, 255, 0.82);
        font-size: .86rem;
        font-weight: 700;
        margin: 0;
    }

    .vf-field input,
    .vf-field textarea,
    .vf-field select {
        width: 100%;
        border-radius: .95rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(7, 12, 22, 0.42);
        color: #eef4ff;
        padding: .82rem .95rem;
        outline: none;
        box-shadow: none;
    }

    .vf-field input:focus,
    .vf-field textarea:focus,
    .vf-field select:focus {
        border-color: rgba(103, 216, 201, 0.34);
        box-shadow: 0 0 0 3px rgba(103, 216, 201, 0.11);
    }

    .vf-field textarea {
        resize: vertical;
        min-height: 7rem;
    }

    .vf-field input[type="color"] {
        padding: .35rem;
        min-height: 3rem;
    }

    .vf-field__hint {
        color: rgba(226, 235, 247, 0.58);
        font-size: .78rem;
        line-height: 1.5;
    }

    .vf-field.is-error input,
    .vf-field.is-error textarea,
    .vf-field.is-error select {
        border-color: rgba(255, 122, 147, 0.46);
        box-shadow: 0 0 0 1px rgba(255, 122, 147, 0.12);
    }

    .vf-field__hint--error {
        color: #ffc0cf;
        font-weight: 700;
    }

    .vf-media-gallery {
        margin-top: 1rem;
        padding: 1rem;
        border-radius: 1.15rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: linear-gradient(180deg, rgba(255,255,255,0.035), rgba(255,255,255,0.015));
    }

    .vf-media-gallery__head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .85rem;
    }

    .vf-media-gallery__title {
        margin: 0;
        color: #fff;
        font-size: .98rem;
        font-weight: 900;
    }

    .vf-media-gallery__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: .8rem;
    }

    .vf-media-gallery__empty {
        padding: .9rem 1rem;
        border-radius: 1rem;
        border: 1px dashed rgba(255, 255, 255, 0.12);
        color: rgba(226, 234, 249, 0.72);
        font-size: .9rem;
        line-height: 1.5;
    }

    .vf-media-gallery__item {
        display: grid;
        gap: .55rem;
        padding: .5rem;
        border-radius: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(7, 12, 21, 0.34);
        cursor: pointer;
        transition: .18s ease;
    }

    .vf-media-gallery__item:hover {
        transform: translateY(-1px);
        border-color: rgba(103, 216, 201, 0.28);
        box-shadow: 0 10px 24px rgba(3, 8, 18, 0.28);
    }

    .vf-media-gallery__item.is-active {
        border-color: rgba(103, 216, 201, 0.5);
        box-shadow: 0 0 0 1px rgba(103, 216, 201, 0.22);
        background: linear-gradient(180deg, rgba(103,216,201,0.08), rgba(255,255,255,0.02));
    }

    .vf-media-gallery__thumb {
        width: 100%;
        aspect-ratio: 1 / 1;
        border-radius: .85rem;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .vf-media-gallery__thumb img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vf-media-gallery__meta {
        display: grid;
        gap: .25rem;
    }

    .vf-media-gallery__label {
        color: rgba(255, 255, 255, 0.95);
        font-size: .78rem;
        font-weight: 800;
        line-height: 1.35;
        word-break: break-word;
    }

    .vf-media-gallery__state {
        color: rgba(226, 234, 249, 0.68);
        font-size: .74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .vf-toggle {
        display: flex;
        align-items: center;
        gap: .55rem;
        color: var(--vf-text-soft);
        font-weight: 700;
    }

    .vf-step-map {
        display: grid;
        gap: .75rem;
    }

    .vf-phase {
        border-radius: 1.15rem;
        border: 1px solid rgba(255,255,255,0.07);
        background: rgba(255,255,255,0.025);
        overflow: hidden;
    }

    .vf-phase__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .65rem;
        padding: .8rem .95rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .vf-phase__title {
        color: #fff;
        font-size: .98rem;
        font-weight: 900;
    }

    .vf-phase__sub {
        margin-top: .12rem;
        color: rgba(226, 235, 247, 0.6);
        font-size: .8rem;
    }

    .vf-phase__steps {
        display: grid;
        gap: .65rem;
        padding: .8rem .95rem .95rem;
    }

    .vf-step-card,
    .vf-block-card,
    .vf-template-card,
    .vf-action-card {
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.03);
        transition: .18s ease;
    }

    .vf-step-card,
    .vf-block-card {
        cursor: pointer;
    }

    .vf-step-card[draggable="true"],
    .vf-block-card[draggable="true"] {
        user-select: none;
    }

    .vf-step-card:hover,
    .vf-step-card.is-active,
    .vf-block-card:hover,
    .vf-block-card.is-active,
    .vf-template-card:hover {
        border-color: rgba(103, 216, 201, 0.28);
        background: rgba(103, 216, 201, 0.09);
    }

    .vf-step-card,
    .vf-block-card,
    .vf-template-card {
        padding: .9rem;
    }

    .vf-block-card {
        display: grid;
        gap: .75rem;
        min-width: 0;
    }

    .vf-block-card__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
    }

    .vf-block-card__main {
        min-width: 0;
        flex: 1 1 auto;
    }

    .vf-block-card__meta {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .35rem;
        max-width: 14rem;
        flex: 0 0 auto;
        min-width: 0;
    }

    .vf-block-card__width-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.45rem;
        padding: .34rem .52rem;
        border-radius: .8rem;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.04);
        color: rgba(236, 243, 255, 0.8);
        font-size: .72rem;
        font-weight: 900;
        line-height: 1;
        cursor: pointer;
        transition: .18s ease;
    }

    .vf-block-card__width-btn:hover {
        border-color: rgba(103, 216, 201, 0.24);
        color: #fff;
    }

    .vf-block-card__width-btn.is-active {
        background: rgba(103, 216, 201, 0.14);
        border-color: rgba(103, 216, 201, 0.34);
        color: #d7fffa;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .vf-block-card__hint {
        color: rgba(226, 235, 247, 0.54);
        font-size: .74rem;
        line-height: 1.45;
    }

    .vf-block-card__grip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.8rem;
        height: 1.8rem;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.04);
        color: rgba(236, 243, 255, 0.52);
        font-size: .92rem;
        flex: 0 0 auto;
    }

    .vf-block-card[data-vf-block-span="third"] .vf-block-card__top,
    .vf-block-card[data-vf-block-span="quarter"] .vf-block-card__top {
        flex-direction: column;
    }

    .vf-block-card[data-vf-block-span="third"] .vf-block-card__meta,
    .vf-block-card[data-vf-block-span="quarter"] .vf-block-card__meta {
        justify-content: flex-start;
        max-width: 100%;
    }

    .vf-block-card[data-vf-block-span="quarter"] .vf-block-card__width-btn {
        min-width: 2.15rem;
        padding: .28rem .42rem;
        font-size: .67rem;
    }

    .vf-action-card {
        position: relative;
        padding: 1.05rem;
        border-color: rgba(103, 216, 201, 0.16);
        background:
            radial-gradient(circle at top right, rgba(103, 216, 201, 0.09), transparent 38%),
            linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.025));
        box-shadow: 0 18px 40px rgba(3, 8, 18, 0.24);
    }

    .vf-action-card.is-open {
        border-color: rgba(103, 216, 201, 0.32);
        background:
            radial-gradient(circle at top right, rgba(103, 216, 201, 0.14), transparent 42%),
            linear-gradient(180deg, rgba(103, 216, 201, 0.11), rgba(255,255,255,0.04));
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.03),
            0 22px 44px rgba(3, 8, 18, 0.28);
    }

    .vf-action-card__toggle {
        width: 100%;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
        gap: .85rem;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        text-align: left;
        cursor: pointer;
    }

    .vf-action-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .85rem;
        padding: .1rem 0 .95rem;
        margin-bottom: .95rem;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }

    .vf-action-card__eyebrow {
        color: rgba(244, 195, 77, 0.95);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .vf-action-card__title {
        margin-top: .32rem;
        color: #fff;
        font-size: 1.12rem;
        font-weight: 900;
        line-height: 1.25;
    }

    .vf-action-card__sub {
        margin-top: .24rem;
        max-width: 32rem;
        color: rgba(226, 235, 247, 0.68);
        font-size: .85rem;
        line-height: 1.45;
    }

    .vf-action-card__body {
        display: grid;
        gap: .95rem;
        padding-top: .2rem;
    }

    .vf-action-card__section {
        border-radius: 1.05rem;
        border: 1px solid rgba(255,255,255,0.07);
        background: rgba(10, 15, 26, 0.28);
        padding: 1rem;
    }

    .vf-action-card__section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: .9rem;
    }

    .vf-action-card__section-kicker {
        color: rgba(244, 195, 77, 0.95);
        font-size: .7rem;
        font-weight: 900;
        letter-spacing: .09em;
        text-transform: uppercase;
    }

    .vf-action-card__section-text {
        margin-top: .28rem;
        color: rgba(226, 235, 247, 0.62);
        font-size: .8rem;
        line-height: 1.45;
    }

    .vf-action-card__main-grid {
        display: grid;
        grid-template-columns: minmax(0, .82fr) minmax(0, 1.05fr) minmax(0, 1.15fr);
        gap: .85rem;
        align-items: start;
    }

    .vf-action-card__main-grid > .is-full {
        grid-column: 1 / -1;
    }

    .vf-field--compact select,
    .vf-field--compact input {
        font-size: .92rem;
        padding: .78rem .88rem;
    }

    .vf-field--route .vf-field__hint--error {
        margin-top: .28rem;
        max-width: 22rem;
        font-size: .8rem;
        line-height: 1.45;
    }

    .vf-action-card__advanced {
        margin-top: 0;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 1.05rem;
        background: rgba(7, 12, 22, 0.22);
        padding: .3rem .3rem .4rem;
    }

    .vf-action-card__advanced summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .45rem;
        padding: .72rem .78rem;
        border-radius: .9rem;
        background: rgba(255,255,255,0.035);
        color: rgba(236, 243, 255, 0.86);
        font-size: .84rem;
        font-weight: 800;
        cursor: pointer;
        user-select: none;
        list-style: none;
    }

    .vf-action-card__advanced summary::-webkit-details-marker {
        display: none;
    }

    .vf-action-card__advanced-grid {
        display: grid;
        gap: .85rem;
        margin-top: .85rem;
        padding: 0 .75rem .75rem;
    }

    .vf-product-mapping-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
        gap: 1rem;
    }

    .vf-product-mapping-grid .vf-field {
        gap: .5rem;
    }

    .vf-product-mapping-grid .vf-field label {
        min-height: 1.25rem;
    }

    .vf-product-mapping-grid .vf-field select {
        min-height: 3.2rem;
    }

    .vf-product-mapping-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .8rem;
    }

    .vf-product-mapping-meta {
        color: rgba(226, 235, 247, 0.72);
        font-size: .82rem;
        line-height: 1.45;
    }

    .vf-product-mapping-meta strong {
        color: rgba(255, 255, 255, 0.94);
        font-weight: 800;
    }

    .vf-action-card__meta {
        justify-content: flex-end;
        margin-bottom: 0;
    }

    .vf-action-card__arrow {
        width: 2.3rem;
        height: 2.3rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.12);
        background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
        color: rgba(236, 243, 255, 0.82);
        font-size: .95rem;
        transition: transform .18s ease, background .18s ease, border-color .18s ease;
    }

    .vf-action-card.is-open .vf-action-card__arrow {
        transform: rotate(180deg);
        background: rgba(103, 216, 201, 0.12);
        border-color: rgba(103, 216, 201, 0.24);
        color: #d7fffa;
    }

    .vf-chip--soft {
        background: rgba(255,255,255,0.05);
        border-color: rgba(255,255,255,0.1);
        color: rgba(236, 243, 255, 0.82);
    }

    .vf-chip--accent {
        background: linear-gradient(180deg, rgba(103, 216, 201, 0.2), rgba(103, 216, 201, 0.12));
        border-color: rgba(103, 216, 201, 0.26);
        color: #d7fffa;
    }

    .vf-step-card.is-dragging,
    .vf-block-card.is-dragging {
        opacity: .48;
        border-style: dashed;
    }

    .vf-phase__steps.is-drop-target,
    .vf-blocks-list.is-drop-target {
        outline: 2px dashed rgba(103, 216, 201, 0.4);
        outline-offset: .25rem;
        border-radius: 1rem;
    }

    .vf-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-bottom: .62rem;
        min-width: 0;
    }

    .vf-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .38rem .72rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.06);
        color: rgba(236, 243, 255, 0.78);
        border: 1px solid rgba(255,255,255,0.08);
        font-size: .73rem;
        font-weight: 800;
        letter-spacing: .01em;
        max-width: 100%;
        min-width: 0;
        white-space: normal;
        overflow-wrap: anywhere;
        line-height: 1.25;
    }

    .vf-step-card__title,
    .vf-block-card__title,
    .vf-template-card__title {
        color: #fff;
        font-weight: 900;
        line-height: 1.25;
        margin-bottom: .35rem;
    }

    .vf-step-card__text,
    .vf-block-card__text,
    .vf-template-card__text {
        color: var(--vf-text-soft);
        font-size: .88rem;
        line-height: 1.55;
    }

    .vf-card-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .75rem;
    }

    .vf-card-actions button {
        padding: .44rem .62rem;
        border-radius: .85rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.04);
        color: var(--vf-text);
        font-size: .78rem;
        font-weight: 800;
        cursor: pointer;
    }

    .vf-blocks-list,
    .vf-template-grid,
    .vf-actions-list {
        display: grid;
        gap: .75rem;
    }

    .vf-blocks-list {
        grid-template-columns: repeat(12, minmax(0, 1fr));
        align-items: start;
    }

    .vf-blocks-list > [data-vf-block-span="full"] { grid-column: span 12; }
    .vf-blocks-list > [data-vf-block-span="half"] { grid-column: span 6; }
    .vf-blocks-list > [data-vf-block-span="third"] { grid-column: span 4; }
    .vf-blocks-list > [data-vf-block-span="two_thirds"] { grid-column: span 8; }
    .vf-blocks-list > [data-vf-block-span="quarter"] { grid-column: span 3; }
    .vf-blocks-list > [data-vf-block-span="three_quarters"] { grid-column: span 9; }

    .vf-blocks-list > .vf-empty {
        grid-column: 1 / -1;
    }

    .vf-template-grid {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .vf-builder-columns {
        display: grid;
        grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr);
        gap: 1rem;
    }

    .vf-preview-shell {
        position: sticky;
        top: 1.2rem;
        display: grid;
        gap: 1rem;
    }

    .vf-preview-page {
        border-radius: 1.4rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: #0f172a;
        padding: 1rem;
        overflow: hidden;
    }

    .vf-preview-page__canvas {
        margin: 0 auto;
        border-radius: 1.4rem;
        padding: 1rem;
        min-height: 540px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .vf-preview-page__header {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
        margin-bottom: .95rem;
    }

    .vf-preview-blocks {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: .95rem;
    }

    .vf-preview-block {
        min-width: 0;
        border-radius: 1.15rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.05);
        padding: 1rem;
    }

    .vf-preview-blocks > [data-vf-span="full"] { grid-column: span 12; }
    .vf-preview-blocks > [data-vf-span="half"] { grid-column: span 6; }
    .vf-preview-blocks > [data-vf-span="third"] { grid-column: span 4; }
    .vf-preview-blocks > [data-vf-span="two_thirds"] { grid-column: span 8; }
    .vf-preview-blocks > [data-vf-span="quarter"] { grid-column: span 3; }
    .vf-preview-blocks > [data-vf-span="three_quarters"] { grid-column: span 9; }

    .vf-preview-spacer {
        min-width: 0;
    }

    .vf-preview-blocks > .vf-empty {
        grid-column: 1 / -1;
    }

    .vf-preview-block.align-center {
        text-align: center;
    }

    .vf-preview-block.align-right {
        text-align: right;
    }

    .vf-preview-badge {
        display: inline-flex;
        align-items: center;
        padding: .3rem .66rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.08);
        font-size: .76rem;
        font-weight: 900;
        margin-bottom: .65rem;
    }

    .vf-preview-title {
        font-size: clamp(1.4rem, 3vw, 2.55rem);
        line-height: 1.03;
        font-weight: 900;
        margin-bottom: .45rem;
        color: inherit;
    }

    .vf-preview-text {
        font-size: .98rem;
        line-height: 1.7;
        color: inherit;
        opacity: .92;
    }

    .vf-preview-media {
        width: 100%;
        min-height: 180px;
        border-radius: 1rem;
        border: 1px dashed rgba(255,255,255,0.16);
        background: rgba(255,255,255,0.04);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(236,243,255,0.6);
        text-align: center;
        overflow: hidden;
    }

    .vf-preview-media.is-video {
        min-height: 0;
        aspect-ratio: 16 / 9;
        display: block;
        background: #000;
        border-style: solid;
    }

    .vf-preview-media img {
        width: 100%;
        height: auto;
        display: block;
    }

    .vf-preview-media iframe,
    .vf-preview-media video {
        width: 100%;
        height: 100%;
        min-height: 0;
        border: 0;
        display: block;
        background: #000;
    }

    .vf-preview-product-card {
        margin-top: .95rem;
        border-radius: 1.1rem;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.04);
        padding: .95rem;
    }

    .vf-preview-product-card__inner {
        display: grid;
        grid-template-columns: minmax(0, 120px) minmax(0, 1fr);
        gap: .95rem;
        align-items: start;
    }

    .vf-preview-product-card__image {
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.08);
        overflow: hidden;
        background: rgba(255,255,255,0.05);
        aspect-ratio: 1 / 1;
    }

    .vf-preview-product-card__image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .vf-preview-product-card__content {
        min-width: 0;
    }

    .vf-preview-product-card__title {
        font-size: 1.16rem;
        font-weight: 900;
        line-height: 1.2;
        margin-bottom: .4rem;
    }

    .vf-preview-product-card__text {
        font-size: .94rem;
        line-height: 1.55;
        opacity: .86;
    }

    .vf-preview-product-card__empty {
        padding: .85rem;
        border-radius: .95rem;
        background: rgba(255,255,255,0.03);
        color: rgba(236,243,255,0.72);
        line-height: 1.6;
    }

    .vf-preview-buttons {
        display: grid;
        gap: .68rem;
        margin-top: .9rem;
    }

    .vf-preview-btn {
        display: block;
        width: 100%;
        padding: .92rem 1rem;
        border-radius: 1rem;
        font-weight: 900;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .vf-preview-btn.is-primary {
        background: var(--vf-accent);
        color: #0f172a;
        border-color: transparent;
    }

    .vf-preview-btn.is-secondary {
        background: rgba(255,255,255,0.1);
        color: inherit;
    }

    .vf-preview-btn.is-ghost {
        background: transparent;
        color: inherit;
    }

    .vf-preview-radio-list {
        display: grid;
        gap: .68rem;
        margin-top: .9rem;
    }

    .vf-preview-radio-item {
        display: flex;
        align-items: flex-start;
        gap: .78rem;
        padding: .9rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.05);
        text-align: left;
    }

    .vf-preview-radio-dot {
        width: 1rem;
        height: 1rem;
        margin-top: .22rem;
        border-radius: 999px;
        border: 2px solid rgba(255,255,255,0.26);
        background: transparent;
        flex: 0 0 auto;
    }

    .vf-preview-radio-copy {
        display: grid;
        gap: .18rem;
    }

    .vf-preview-radio-label {
        line-height: 1.45;
    }

    .vf-preview-radio-hint {
        color: rgba(236,243,255,0.64);
        font-size: .82rem;
        line-height: 1.5;
    }

    .vf-preview-field {
        width: 100%;
        padding: .9rem 1rem;
        border-radius: .95rem;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.06);
        color: inherit;
    }

    .vf-preview-field::placeholder {
        color: var(--vf-placeholder-color, rgba(238, 244, 255, 0.5));
    }

    @media (max-width: 760px) {
        .vf-preview-blocks {
            grid-template-columns: minmax(0, 1fr);
        }

        .vf-preview-blocks > [data-vf-span] {
            grid-column: span 1;
        }

        .vf-preview-product-card__inner {
            grid-template-columns: 1fr;
        }

        .vf-preview-product-card__image {
            max-width: 220px;
        }
    }

    .vf-preview-countdown {
        margin-top: .85rem;
        width: 100%;
        display: grid;
        gap: .75rem;
    }

    .vf-preview-countdown__row {
        display: grid;
        gap: .75rem;
        align-items: stretch;
    }

    .vf-preview-countdown__row.is-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .vf-preview-countdown__row.is-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .vf-preview-countdown__row.is-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .vf-preview-countdown__row.is-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

    .vf-preview-countdown__item {
        min-width: 0;
        border-radius: 1.1rem;
        border: 1px solid rgba(255,255,255,0.08);
        display: grid;
        place-items: center;
        gap: .25rem;
        padding: .9rem .55rem;
        text-align: center;
    }

    .vf-preview-countdown__value {
        line-height: 1;
        font-weight: 900;
        letter-spacing: -.03em;
    }

    .vf-preview-countdown__label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: rgba(236,243,255,0.62);
    }

    .vf-preview-countdown--cards .vf-preview-countdown__item {
        background: rgba(255,255,255,0.06);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .vf-preview-countdown--glass .vf-preview-countdown__row {
        gap: .55rem;
        padding: .65rem;
        border-radius: 1.25rem;
        background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
        backdrop-filter: blur(10px);
    }

    .vf-preview-countdown--glass .vf-preview-countdown__item {
        background: rgba(11,17,27,0.35);
    }

    .vf-preview-countdown--minimal .vf-preview-countdown__row {
        gap: .9rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .vf-preview-countdown--minimal .vf-preview-countdown__item {
        background: transparent;
        border: 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        border-radius: 0;
        padding: .2rem 0 .65rem;
    }

    .vf-preview-countdown--spotlight .vf-preview-countdown__row {
        padding: .8rem;
        border-radius: 1.35rem;
        background:
            radial-gradient(circle at top, rgba(255,255,255,0.12), rgba(255,255,255,0.02) 55%),
            linear-gradient(145deg, rgba(255,255,255,0.06), rgba(10,18,28,0.08));
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), 0 18px 36px rgba(2,8,23,0.18);
    }

    .vf-preview-countdown--spotlight .vf-preview-countdown__item {
        background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
        box-shadow: 0 10px 24px rgba(2,8,23,0.16);
    }

    .vf-preview-countdown__expired {
        padding: 1rem 1.1rem;
        border-radius: 1.15rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: linear-gradient(135deg, rgba(255,122,147,0.18), rgba(255,255,255,0.04));
        display: grid;
        gap: .35rem;
        text-align: center;
    }

    .vf-preview-countdown__expired-kicker {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: rgba(255,232,238,0.78);
    }

    .vf-preview-countdown__expired-text {
        font-size: 1rem;
        font-weight: 800;
        color: #fff3f6;
    }

    @media (max-width: 720px) {
        .vf-preview-countdown__row.is-cols-3,
        .vf-preview-countdown__row.is-cols-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .vf-empty {
        padding: 1.1rem;
        border-radius: 1rem;
        border: 1px dashed rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.02);
        color: rgba(226,235,247,0.58);
        text-align: center;
    }

    .vf-analytics-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .vf-analytics-layout {
        display: grid;
        gap: 1rem;
    }

    .vf-analytics-split {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        gap: 1rem;
    }

    .vf-analytics-table-wrap {
        overflow-x: auto;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.02);
    }

    .vf-analytics-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 680px;
    }

    .vf-analytics-table th,
    .vf-analytics-table td {
        padding: .78rem .85rem;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        text-align: left;
        vertical-align: top;
    }

    .vf-analytics-table th {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: rgba(205, 220, 238, 0.68);
        font-weight: 800;
        white-space: nowrap;
    }

    .vf-analytics-table td {
        color: rgba(236, 243, 255, 0.9);
        font-size: .92rem;
    }

    .vf-analytics-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .vf-analytics-step-title {
        font-weight: 800;
        color: #f6fbff;
    }

    .vf-analytics-step-sub {
        margin-top: .22rem;
        font-size: .8rem;
        color: rgba(205, 220, 238, 0.64);
    }

    .vf-analytics-rate {
        display: inline-flex;
        align-items: center;
        padding: .24rem .55rem;
        border-radius: 999px;
        background: rgba(103,216,201,0.12);
        color: #95efe2;
        font-weight: 800;
        font-size: .82rem;
    }

    .vf-analytics-muted {
        color: rgba(205, 220, 238, 0.62);
    }

    .vf-analytics-list {
        display: grid;
        gap: .85rem;
    }

    .vf-analytics-list-item {
        padding: .95rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.03);
    }

    .vf-analytics-list-top {
        display: flex;
        justify-content: space-between;
        gap: .8rem;
        align-items: flex-start;
    }

    .vf-analytics-list-title {
        font-weight: 800;
        color: #f7fbff;
    }

    .vf-analytics-list-sub {
        margin-top: .2rem;
        font-size: .84rem;
        color: rgba(205, 220, 238, 0.66);
    }

    .vf-analytics-list-meta {
        margin-top: .55rem;
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }

    .vf-analytics-empty {
        padding: 1rem;
        border-radius: 1rem;
        border: 1px dashed rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.02);
        color: rgba(205, 220, 238, 0.6);
    }

    .vf-note {
        padding: .95rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(103,216,201,0.18);
        background: rgba(103,216,201,0.08);
        color: rgba(235,245,255,0.88);
        line-height: 1.6;
    }

    @media (max-width: 1360px) {
        .vf-grid,
        .vf-builder-columns,
        .vf-two,
        .vf-analytics-split,
        .vf-action-card__main-grid,
        .vf-hero {
            grid-template-columns: minmax(0, 1fr);
        }

        .vf-action-card__toggle {
            grid-template-columns: minmax(0, 1fr);
        }

        .vf-preview-shell {
            position: static;
        }

        .vf-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .vf-action-card__header {
            flex-direction: column;
        }

        .vf-action-card__meta {
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .vf-card__body,
        .vf-card__head,
        .vf-hero {
            padding-left: .95rem;
            padding-right: .95rem;
        }

        .vf-kpi-grid,
        .vf-analytics-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .vf-block-adder {
            grid-template-columns: minmax(0, 1fr);
        }

        .vf-three {
            grid-template-columns: minmax(0, 1fr);
        }

        .vf-blocks-list {
            grid-template-columns: minmax(0, 1fr);
        }

        .vf-blocks-list > [data-vf-block-span] {
            grid-column: span 1;
        }

        .vf-block-card__top {
            flex-direction: column;
        }

        .vf-block-card__meta {
            justify-content: flex-start;
            max-width: 100%;
        }

        .vf-block-adder .vf-btn {
            width: 100%;
        }
    }
</style>

<form id="vf-studio-form" method="post" class="vf-core" novalidate>
    <input type="hidden" name="token" id="vf_token_input" value="<?= $e(\Altum\Csrf::get('token')) ?>" />
    <input type="hidden" name="global_token" id="vf_global_token_input" value="<?= $e(\Altum\Csrf::get('global_token')) ?>" />
    <input type="hidden" name="funnel_id" id="vf_funnel_id_input" value="<?= (int) $selected_funnel_id ?>" />
    <textarea hidden name="vip_funnel_studio_payload" id="vf_payload_input"><?= $e(vip_funnel_json_encode($payload)) ?></textarea>

    <section class="vf-hero">
        <div>
            <span class="vf-hero__eyebrow">Funnel 2.0 Block Builder</span>
            <h1 class="vf-hero__title">VIP Funnel 2.0</h1>
            <div class="vf-hero__copy">Prvi korak je sada odvojen kao prava landing stranica, a svaki sljedeći korak koristi isti builder za sadržaj, logiku, grananje i prikupljanje leadova.</div>
        </div>

        <div class="vf-hero__logo">
            <div class="vf-hero__logo-shell">
                <img src="<?= $e($logo_url) ?>" alt="Forever Card Funnel" />
            </div>
        </div>
    </section>

    <div class="vf-kpi-grid">
        <div class="vf-kpi">
            <div class="vf-kpi__label"><?= l('vip_funnel.analytics.top.views') ?></div>
            <div class="vf-kpi__value"><?= nr((int) ($analytics['views'] ?? 0)) ?></div>
        </div>
        <div class="vf-kpi">
            <div class="vf-kpi__label"><?= l('vip_funnel.analytics.top.submits') ?></div>
            <div class="vf-kpi__value"><?= nr((int) ($analytics['submits'] ?? 0)) ?></div>
        </div>
        <div class="vf-kpi">
            <div class="vf-kpi__label"><?= l('vip_funnel.analytics.top.leads') ?></div>
            <div class="vf-kpi__value"><?= nr((int) ($analytics['leads'] ?? 0)) ?></div>
        </div>
        <div class="vf-kpi">
            <div class="vf-kpi__label"><?= l('vip_funnel.analytics.top.ab_winner') ?></div>
            <div class="vf-kpi__value"><?= $e((string) (($analytics['ab']['winner'] ?? '') !== '' ? sprintf(l('vip_funnel.analytics.variant_label'), ($analytics['ab']['winner'] ?? '')) : '—')) ?></div>
        </div>
    </div>

    <div class="vf-topbar">
        <div class="vf-tabs" id="vf_tabs"></div>

        <div class="vf-actions">
            <a href="<?= $e($data->funnels_index_url ?? url('vip-funnel-studio')) ?>" class="vf-btn vf-btn--ghost">Svi funnel-i</a>
            <a href="<?= $e($data->demo_access_url ?? url('vip-funnel-demo-access')) ?>" class="vf-btn vf-btn--ghost"><?= l('vip_funnel.analytics.demo_access_button') ?></a>
            <button type="button" class="vf-btn vf-btn--primary" data-vf-save-button="1"><?= l('vip_funnel.analytics.save_button') ?></button>
            <button type="submit" class="vf-btn vf-btn--danger" data-vf-reset-button="1" name="reset_vip_funnel_studio" value="1"><?= l('vip_funnel.analytics.reset_button') ?></button>
        </div>
    </div>

    <div id="vf_save_notice" class="vf-note" style="display:none;margin-bottom:1rem;"></div>

    <div class="vf-grid">
        <div class="vf-workspace" id="vf_workspace"></div>
        <div class="vf-preview-shell" id="vf_preview"></div>
    </div>

    <div class="vf-bottombar">
        <div class="vf-bottombar__hint">
            <div class="vf-bottombar__eyebrow"><?= l('vip_funnel.analytics.quick_save_title') ?></div>
            <div class="vf-bottombar__text"><?= l('vip_funnel.analytics.quick_save_text') ?></div>
        </div>

        <div class="vf-actions">
            <button type="button" class="vf-btn vf-btn--primary" data-vf-save-button="1"><?= l('vip_funnel.analytics.save_button') ?></button>
        </div>
    </div>
</form>

<script>
(() => {
    const payloadInput = document.getElementById('vf_payload_input');
    const tokenInput = document.getElementById('vf_token_input');
    const globalTokenInput = document.getElementById('vf_global_token_input');
    const funnelIdInput = document.getElementById('vf_funnel_id_input');
    const workspaceRoot = document.getElementById('vf_workspace');
    const previewRoot = document.getElementById('vf_preview');
    const tabsRoot = document.getElementById('vf_tabs');
    const form = document.getElementById('vf-studio-form');
    const saveNotice = document.getElementById('vf_save_notice');
    const saveButtons = Array.from(form.querySelectorAll('[data-vf-save-button]'));
    const resetButton = form.querySelector('[data-vf-reset-button]');
    const saveUrl = <?= json_encode(url('vip-funnel-studio/save-ajax'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const imageUploadUrl = <?= json_encode($data->image_upload_url ?? url('vip-funnel-studio/upload-image'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const imageUploadMaxSizeMb = <?= json_encode((float) ($data->image_upload_max_size_mb ?? 3), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const imageUploadAccept = <?= json_encode($data->image_upload_accept ?? '.jpg, .jpeg, .png, .svg, .gif, .webp, .avif', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const imageGalleryEntries = <?= json_encode(array_values($data->image_gallery_entries ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const productCatalog = <?= json_encode(array_values($data->product_catalog ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const productSourceModeOptions = <?= json_encode($data->product_source_mode_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const productTargetModeOptions = <?= json_encode($data->product_target_mode_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const productLanguageModeOptions = <?= json_encode($data->product_language_mode_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const productLanguageOptions = <?= json_encode($data->product_language_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const preferredProductLanguageCode = <?= json_encode($data->preferred_product_language_code ?? (\Altum\Language::$default_code ?? 'hr'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const phaseDefinitions = <?= json_encode($phase_definitions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pageBlockTypes = <?= json_encode($data->page_block_type_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pageWidthOptions = <?= json_encode($data->page_width_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pageAlignmentOptions = <?= json_encode($data->page_alignment_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pageBlockWidthOptions = <?= json_encode($data->page_block_width_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pageActionOptions = <?= json_encode($data->page_action_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pageFontFamilyOptions = <?= json_encode($data->page_font_family_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pageFontFamilyCssMap = <?= json_encode($data->page_font_family_css_map ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pageFontWeightOptions = <?= json_encode($data->page_font_weight_options ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pageTemplates = <?= json_encode($data->page_block_template_presets ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pathOptions = <?= json_encode(array_map(static function($path) {
        return ['path_key' => $path['path_key'] ?? '', 'title' => $path['title'] ?? ''];
    }, (array) ($payload['paths'] ?? [])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const analyticsSeed = <?= json_encode($analytics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const validationMessages = <?= json_encode([
        'fixBeforeSave' => l('vip_funnel.alert.validation_fix_before_save'),
        'externalUrlRequired' => l('vip_funnel.alert.validation_external_url_required'),
        'imageUploadMissing' => l('vip_funnel.alert.image_upload_missing'),
        'imageUploadUploading' => l('vip_funnel.alert.image_uploading'),
        'imageUploaded' => l('vip_funnel.alert.image_uploaded'),
        'imageUploadFailed' => l('vip_funnel.alert.image_upload_failed'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const analyticsMessages = <?= json_encode([
        'landingTab' => l('vip_funnel.analytics.tab.landing'),
        'stepDefault' => l('vip_funnel.analytics.tab.step_default'),
        'addStepTitle' => l('vip_funnel.analytics.tab.add_step'),
        'analyticsTab' => l('vip_funnel.analytics.tab.analytics'),
        'overviewTitle' => l('vip_funnel.analytics.overview.title'),
        'overviewSub' => l('vip_funnel.analytics.overview.subheader'),
        'views' => l('vip_funnel.analytics.top.views'),
        'uniqueVisitors' => l('vip_funnel.analytics.overview.unique_visitors'),
        'submits' => l('vip_funnel.analytics.top.submits'),
        'leads' => l('vip_funnel.analytics.top.leads'),
        'advances' => l('vip_funnel.analytics.overview.advances'),
        'contactsSynced' => l('vip_funnel.analytics.overview.contacts_synced'),
        'submitRate' => l('vip_funnel.analytics.overview.submit_rate'),
        'leadRate' => l('vip_funnel.analytics.overview.lead_rate'),
        'bestStep' => l('vip_funnel.analytics.overview.best_step'),
        'bestSelection' => l('vip_funnel.analytics.overview.best_selection'),
        'stepsTitle' => l('vip_funnel.analytics.steps.title'),
        'stepsSub' => l('vip_funnel.analytics.steps.subheader'),
        'selectionsTitle' => l('vip_funnel.analytics.selections.title'),
        'selectionsSub' => l('vip_funnel.analytics.selections.subheader'),
        'abTitle' => l('vip_funnel.analytics.ab.title'),
        'abSub' => l('vip_funnel.analytics.ab.subheader'),
        'demoTitle' => l('vip_funnel.analytics.demo.title'),
        'demoSub' => l('vip_funnel.analytics.demo.subheader'),
        'recentEventsTitle' => l('vip_funnel.analytics.recent.title'),
        'recentEventsSub' => l('vip_funnel.analytics.recent.subheader'),
        'recentDemoTitle' => l('vip_funnel.analytics.demo_recent.title'),
        'recentDemoSub' => l('vip_funnel.analytics.demo_recent.subheader'),
        'emptyRows' => l('vip_funnel.analytics.empty'),
        'emptyRecent' => l('vip_funnel.analytics.recent.empty'),
        'emptyDemoRecent' => l('vip_funnel.analytics.demo_recent.empty'),
        'tableStep' => l('vip_funnel.analytics.table.step'),
        'tablePhase' => l('vip_funnel.analytics.table.phase'),
        'tablePath' => l('vip_funnel.analytics.table.path'),
        'tableViews' => l('vip_funnel.analytics.table.views'),
        'tableVisitors' => l('vip_funnel.analytics.table.visitors'),
        'tableSubmits' => l('vip_funnel.analytics.table.submits'),
        'tableLeads' => l('vip_funnel.analytics.table.leads'),
        'tableAdvances' => l('vip_funnel.analytics.table.advances'),
        'tableRate' => l('vip_funnel.analytics.table.rate'),
        'tableSelection' => l('vip_funnel.analytics.table.selection'),
        'tableSelectionLabel' => l('vip_funnel.analytics.table.selection_label'),
        'tableVariant' => l('vip_funnel.analytics.table.variant'),
        'winner' => l('vip_funnel.analytics.top.ab_winner'),
        'winnerNone' => l('vip_funnel.analytics.winner_none'),
        'variantLabel' => l('vip_funnel.analytics.variant_label'),
        'variantA' => l('vip_funnel.analytics.variant_a'),
        'variantB' => l('vip_funnel.analytics.variant_b'),
        'requests' => l('vip_funnel.analytics.demo.requests'),
        'approved' => l('vip_funnel.analytics.demo.approved'),
        'live' => l('vip_funnel.analytics.demo.live'),
        'converted' => l('vip_funnel.analytics.demo.converted'),
        'archived' => l('vip_funnel.analytics.demo.archived'),
        'activationRate' => l('vip_funnel.analytics.demo.activation_rate'),
        'conversionRate' => l('vip_funnel.analytics.demo.conversion_rate'),
        'eventAt' => l('vip_funnel.analytics.recent.at'),
        'eventBy' => l('vip_funnel.analytics.demo_recent.by'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const studioMessages = <?= json_encode([
        'landingTitle' => l('vip_funnel.studio.landing.title'),
        'landingSub' => l('vip_funnel.studio.landing.sub'),
        'landingNote' => l('vip_funnel.studio.landing.note'),
        'stepDefaultTitle' => l('vip_funnel.studio.step.default_title'),
        'stepSub' => l('vip_funnel.studio.step.sub'),
        'stepBuilderTitle' => l('vip_funnel.studio.step.builder_title'),
        'stepBuilderSub' => l('vip_funnel.studio.step.builder_sub'),
        'stepEmptyAdd' => l('vip_funnel.studio.step.empty_add'),
        'stepEmptySelect' => l('vip_funnel.studio.step.empty_select'),
        'stepToolbar' => l('vip_funnel.studio.step_meta.toolbar'),
        'stepDelete' => l('vip_funnel.studio.step_meta.delete'),
        'stepSettings' => l('vip_funnel.studio.step_meta.section'),
        'stepTitleLabel' => l('vip_funnel.studio.step_meta.title_label'),
        'stepSummaryLabel' => l('vip_funnel.studio.step_meta.summary_label'),
        'stepBadgeLabel' => l('vip_funnel.studio.step_meta.badge_label'),
        'stepPhaseLabel' => l('vip_funnel.studio.step_meta.phase_label'),
        'stepPathLabel' => l('vip_funnel.studio.step_meta.path_label'),
        'stepLegacyCtaLabel' => l('vip_funnel.studio.step_meta.legacy_cta_label'),
        'surfaceDesignTitle' => l('vip_funnel.studio.surface.design_title'),
        'surfaceDesignSub' => l('vip_funnel.studio.surface.design_sub'),
        'surfaceNameLabel' => l('vip_funnel.studio.surface.name_label'),
        'surfaceBackgroundLabel' => l('vip_funnel.studio.surface.background_label'),
        'surfaceColorLabel' => l('vip_funnel.studio.surface.surface_label'),
        'surfaceTextColorLabel' => l('vip_funnel.studio.surface.text_label'),
        'surfaceAccentLabel' => l('vip_funnel.studio.surface.accent_label'),
        'surfaceWidthLabel' => l('vip_funnel.studio.surface.max_width_label'),
        'surfaceAbLabel' => l('vip_funnel.studio.surface.ab_distribution_label'),
        'surfaceShowProgress' => l('vip_funnel.studio.surface.show_progress'),
        'surfaceAbEnabled' => l('vip_funnel.studio.surface.ab_enabled'),
        'surfaceEditVariantA' => l('vip_funnel.studio.surface.edit_variant_a'),
        'surfaceEditVariantB' => l('vip_funnel.studio.surface.edit_variant_b'),
        'surfaceBlocksTitle' => l('vip_funnel.studio.surface.blocks_title'),
        'surfaceBlocksSub' => l('vip_funnel.studio.surface.blocks_sub'),
        'surfaceSubmitNoteSurvey' => l('vip_funnel.studio.surface.submit_note_survey'),
        'surfaceSubmitNoteForm' => l('vip_funnel.studio.surface.submit_note_form'),
        'surfaceAddSubmitButton' => l('vip_funnel.studio.surface.add_submit_button'),
        'surfaceBlocksEmpty' => l('vip_funnel.studio.surface.blocks_empty'),
        'surfaceAddBlockButton' => l('vip_funnel.studio.surface.add_block_button'),
        'surfaceTemplatesTitle' => l('vip_funnel.studio.surface.templates_title'),
        'surfaceTemplatesSub' => l('vip_funnel.studio.surface.templates_sub'),
        'surfaceEditorTitle' => l('vip_funnel.studio.surface.editor_title'),
        'surfaceEditorSub' => l('vip_funnel.studio.surface.editor_sub'),
        'surfaceEditorEmpty' => l('vip_funnel.studio.surface.editor_empty'),
        'blockNoDesc' => l('vip_funnel.studio.block.no_desc'),
        'blockWidthHint' => l('vip_funnel.studio.block.width_hint'),
        'blockAutoSubmit' => l('vip_funnel.studio.block.auto_submit'),
        'galleryTitle' => l('vip_funnel.studio.gallery.title'),
        'galleryHint' => l('vip_funnel.studio.gallery.hint'),
        'galleryEmpty' => l('vip_funnel.studio.gallery.empty'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let vipFunnelImageGalleryEntries = Array.isArray(imageGalleryEntries) ? imageGalleryEntries.slice() : [];

    const defaultSurface = () => ({
        name: 'Nova funnel stranica',
        background_color: '#0f172a',
        surface_color: '#152132',
        text_color: '#eef4ff',
        accent_color: '#67d8c9',
        max_width: 'wide',
        show_progress: false,
        ab_enabled: false,
        ab_distribution: 50,
        blocks: [],
        variant_b_blocks: [],
        variant_b_settings: {}
    });

    const cloneVariantSurfaceSettings = (surface = {}) => ({
        name: surface.name || 'Nova funnel stranica',
        background_color: surface.background_color || '#0f172a',
        surface_color: surface.surface_color || '#152132',
        text_color: surface.text_color || '#eef4ff',
        accent_color: surface.accent_color || '#67d8c9',
        max_width: surface.max_width || 'wide',
        show_progress: !!surface.show_progress
    });

    const normalizeVariantSurfaceSettings = (variantSettings = {}, sourceSurface = {}) => {
        const normalized = cloneVariantSurfaceSettings(sourceSurface);
        const raw = variantSettings && typeof variantSettings === 'object' ? variantSettings : {};

        ['name', 'background_color', 'surface_color', 'text_color', 'accent_color', 'max_width', 'show_progress'].forEach(field => {
            if(raw[field] !== undefined) {
                normalized[field] = raw[field];
            }
        });

        return normalized;
    };

    const defaultBlockByType = (type) => {
        const typographyDefaults = {
            headline: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 54, title_weight: '900', title_color: '', text_size: 20, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 18, button_weight: '900', button_text_color: ''},
            text: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 24, title_weight: '800', title_color: '', text_size: 17, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            image: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 28, title_weight: '800', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            video: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 28, title_weight: '800', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            cta_group: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 30, title_weight: '900', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 18, button_weight: '900', button_text_color: ''},
            survey: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 30, title_weight: '900', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            radio_survey: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 30, title_weight: '900', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            countdown: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 28, title_weight: '900', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            name_field: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 24, title_weight: '900', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            full_name_field: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 24, title_weight: '900', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            email_field: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 24, title_weight: '900', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            phone_field: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 24, title_weight: '900', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            text_field: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 24, title_weight: '900', title_color: '', text_size: 16, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            proof_card: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 26, title_weight: '800', title_color: '', text_size: 17, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''},
            product_offer: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 28, title_weight: '900', title_color: '', text_size: 17, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 18, button_weight: '900', button_text_color: ''},
            spacer: {font_family: 'inherit', badge_size: 13, badge_weight: '800', badge_color: '', title_size: 28, title_weight: '800', title_color: '', text_size: 17, text_weight: '500', body_color: '', field_size: 16, field_weight: '500', field_text_color: '', placeholder_color: '', button_size: 17, button_weight: '800', button_text_color: ''}
        };

        const map = {
            headline: {type, label: 'Hero naslov', badge: 'Funnel 2.0', title: 'Pokreni svoj prvi korak bez kaosa', text: 'Dodaj jasan naslov i kratki podnaslov.', alignment: 'left', buttons: [], options: []},
            text: {type, label: 'Tekst', badge: '', title: '', text: 'Dodaj kratki tekst koji pojašnjava i vodi dalje.', alignment: 'left', buttons: [], options: []},
            image: {type, label: 'Slika', badge: '', title: '', text: 'Vizual koji pojačava poruku.', media_url: '', alignment: 'center', buttons: [], options: []},
            video: {type, label: 'Video', badge: '', title: '', text: 'Kratki video uvod ili objašnjenje.', media_url: '', alignment: 'center', buttons: [], options: []},
            cta_group: {type, label: 'CTA gumbi', badge: '', title: '', text: 'Dodaj gumbe i odredi kamo vode.', alignment: 'center', require_capture: false, buttons: [{id: uid('btn'), label: 'Kreni dalje', value: 'kreni_dalje', style: 'primary', action: 'goto_step', target_step_id: '', external_url: '', require_submit: false}], options: []},
            survey: {type, label: 'Survey', badge: '', title: 'Što te sada najviše zanima?', text: 'Svaki odgovor može voditi na drugi korak.', alignment: 'left', auto_advance: true, require_capture: false, buttons: [], options: [{id: uid('opt'), label: 'Online posao', value: 'online_posao', style: 'primary', action: 'goto_step', target_step_id: '', external_url: '', require_submit: false}]},
            radio_survey: {type, label: 'Pitanje upitnika', badge: '', title: 'Koji odgovor najbolje opisuje tvoj cilj?', text: 'Odaberi jedan odgovor. Završni submit može koristiti ovaj odabir za pravi sljedeći korak.', alignment: 'left', required: false, route_on_submit: true, buttons: [], options: [{id: uid('opt'), label: 'Regulacija tjelesne težine', value: 'regulacija_tjelesne_tezine', style: 'primary', action: 'goto_step', target_step_id: '', external_url: '', require_submit: false}]},
            countdown: {type, label: 'Countdown', badge: '', title: 'Ponuda istječe uskoro', text: 'Pojačaj hitnost odbrojavanjem.', alignment: 'center', countdown_mode: 'fixed', countdown_style: 'cards', fixed_datetime: '', duration_minutes: 30, duration_days: 0, countdown_show_days: true, countdown_show_hours: true, countdown_show_minutes: true, countdown_show_seconds: true, countdown_number_size: 34, countdown_number_color: '', completion_text: 'Vrijeme je isteklo.', buttons: [], options: []},
            name_field: {type, label: 'Ime', badge: '', title: 'Ime', text: '', placeholder: 'Upiši ime', required: false, alignment: 'left', buttons: [], options: []},
            full_name_field: {type, label: 'Ime + prezime', badge: '', title: 'Ime i prezime', text: '', placeholder: 'Upiši ime i prezime', required: false, alignment: 'left', buttons: [], options: []},
            email_field: {type, label: 'Email', badge: '', title: 'Email', text: '', placeholder: 'Upiši email', required: true, alignment: 'left', buttons: [], options: []},
            phone_field: {type, label: 'Telefon', badge: '', title: 'Telefon', text: '', placeholder: 'Upiši broj telefona', required: false, alignment: 'left', buttons: [], options: []},
            text_field: {type, label: 'Tekst polje', badge: '', title: 'Kratki odgovor', text: '', placeholder: 'Upiši odgovor', field_key: '', required: false, alignment: 'left', buttons: [], options: []},
            proof_card: {type, label: 'Proof / povjerenje', badge: 'Povjerenje', title: 'Zašto ovaj put djeluje sigurno', text: 'Dodaj proof, sigurnost, mentorstvo ili konkretan benefit.', alignment: 'left', buttons: [], options: []},
            product_offer: {type, label: 'Preporuka proizvoda', badge: 'Preporuka', title: 'Idealna preporuka za tvoj cilj', text: 'Odaberi proizvod i poveži osobu ili na blog vodič ili direktno na službeni shop s referral logikom.', alignment: 'left', product_source_mode: 'manual', product_blog_post_id: 0, product_translation_key: '', product_language_mode: 'page', product_language_code: preferredProductLanguageCode || 'hr', product_fallback_language_code: 'hr', product_primary_mode: 'blog_guide', product_primary_cta_text: 'Pogledaj vodič proizvoda', product_secondary_enabled: true, product_secondary_mode: 'direct_shop', product_secondary_cta_text: 'Idi na službeni shop', product_mappings: [], buttons: [], options: []},
            spacer: {type, label: 'Razmak', badge: '', title: '', text: '', spacing: 'lg', alignment: 'left', buttons: [], options: []}
        };

        return JSON.parse(JSON.stringify(Object.assign({layout_width: 'full'}, typographyDefaults[type] || typographyDefaults.headline, map[type] || map.headline)));
    };

    const state = {
        payload: safeParse(payloadInput.value) || {},
        screen: window.location.hash === '#analytics' ? 'analytics' : 'landing',
        activeStepId: '',
        activeBlockId: '',
        activeActionId: '',
        activeVariant: 'a',
        analytics: analyticsSeed || {},
        drag: {
            type: '',
            id: '',
            phaseKey: '',
            scope: '',
            element: null
        },
        save: {
            inFlight: false
        },
        resetRequested: false,
        validation: {
            errors: []
        }
    };

    function safeParse(value) {
        try {
            return JSON.parse(value);
        } catch(error) {
            return null;
        }
    }

    function uid(prefix = 'vf') {
        return `${prefix}_${Math.random().toString(36).slice(2, 10)}`;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function numberFormat(value) {
        return new Intl.NumberFormat(document.documentElement.lang || 'hr-HR').format(Number(value || 0));
    }

    function rateFormat(value) {
        return `${Number(value || 0).toFixed(1)}%`;
    }

    function variantLabel(value) {
        if(!value) {
            return analyticsMessages.winnerNone;
        }

        return analyticsMessages.variantLabel.replace('%s', String(value).toUpperCase());
    }

    function coerceFieldValue(fieldElement) {
        if(!fieldElement) {
            return '';
        }

        const rawValue = fieldElement.value;
        const fieldType = String(fieldElement.getAttribute('type') || '').toLowerCase();

        if(fieldType === 'number') {
            if(rawValue === '') {
                return '';
            }

            const parsed = rawValue.includes('.') ? parseFloat(rawValue) : parseInt(rawValue, 10);
            return Number.isNaN(parsed) ? rawValue : parsed;
        }

        return rawValue;
    }

    function getImageGalleryEntries() {
        if(!Array.isArray(vipFunnelImageGalleryEntries)) {
            vipFunnelImageGalleryEntries = [];
        }

        return vipFunnelImageGalleryEntries.filter(entry => entry && entry.image_url);
    }

    function registerImageGalleryEntry(entry) {
        if(!entry || !entry.image_url) {
            return;
        }

        const normalized = {
            image: entry.image || '',
            image_url: entry.image_url,
            created_at: entry.created_at || new Date().toISOString()
        };

        vipFunnelImageGalleryEntries = [
            normalized,
            ...getImageGalleryEntries().filter(item => item.image_url !== normalized.image_url)
        ].slice(0, 60);
    }

    function hexToRgba(hex, alpha = 1) {
        const normalized = String(hex || '').replace('#', '');
        if(!/^[0-9a-f]{6}$/i.test(normalized)) {
            return `rgba(103, 216, 201, ${alpha})`;
        }

        const r = parseInt(normalized.slice(0, 2), 16);
        const g = parseInt(normalized.slice(2, 4), 16);
        const b = parseInt(normalized.slice(4, 6), 16);

        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    function getFontFamilyCss(fontKey) {
        return pageFontFamilyCssMap[fontKey] || pageFontFamilyCssMap.inherit || 'inherit';
    }

    function resolveBlockColor(primary, fallback) {
        return primary || fallback || '#eef4ff';
    }

    function currentSurfaceHasCaptureFields() {
        return getEditableBlocks().some(block => ['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field'].includes(block.type));
    }

    function currentSurfaceHasDeferredSurvey() {
        return getEditableBlocks().some(block => block.type === 'radio_survey');
    }

    function isOptionBlockType(type = '') {
        return ['survey', 'radio_survey'].includes(type);
    }

    function getBlockActionItems(block = null) {
        if(!block) {
            return [];
        }

        if(isOptionBlockType(block.type)) {
            return Array.isArray(block.options) ? block.options : [];
        }

        if(block.type === 'cta_group') {
            return Array.isArray(block.buttons) ? block.buttons : [];
        }

        return [];
    }

    function isSystemSubmitBlock(block = null) {
        return !!(block && block.type === 'cta_group' && block.system_role === 'auto_submit');
    }

    function blockHasSubmitAction(block = null) {
        if(!block) {
            return false;
        }

        const items = getBlockActionItems(block);

        return items.some(item => ['submit_next', 'submit_stay'].includes(item.action) || item.require_submit);
    }

    function getAvailablePageActionOptions(hasCaptureFields = currentSurfaceHasCaptureFields(), context = {}) {
        const options = Object.assign({}, pageActionOptions);
        const isSystemSubmit = !!context.isSystemSubmitBlock;
        const isRadioSurvey = !!context.isRadioSurveyBlock;

        if(isSystemSubmit) {
            return {
                submit_next: options.submit_next,
                submit_stay: options.submit_stay
            };
        }

        if(isRadioSurvey) {
            return {
                goto_step: options.goto_step,
                external_url: options.external_url
            };
        }

        if(!hasCaptureFields) {
            delete options.submit_next;
            delete options.submit_stay;
        }

        return options;
    }

    function normalizeRequireSubmitFlagsForBlocks(blocks = []) {
        const hasCaptureFields = Array.isArray(blocks) && blocks.some(block => ['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field'].includes(block.type));
        let changed = false;

        (blocks || []).forEach(block => {
            const items = getBlockActionItems(block);
            const shouldStripSubmitLogic = block?.type === 'radio_survey' || !hasCaptureFields;

            items.forEach(item => {
                if(shouldStripSubmitLogic && item && ['submit_next', 'submit_stay'].includes(item.action)) {
                    item.action = 'goto_step';
                    changed = true;
                }

                if(shouldStripSubmitLogic && item && item.require_submit) {
                    item.require_submit = false;
                    changed = true;
                }
            });
        });

        return changed;
    }

    function buildSystemSubmitBlock(existingBlock = null) {
        const submitBlock = normalizeBlock(existingBlock || defaultBlockByType('cta_group'));
        submitBlock.id = submitBlock.id || uid('cta_group');
        submitBlock.system_role = 'auto_submit';
        submitBlock.label = submitBlock.label || 'Submit forma';
        submitBlock.title = submitBlock.title || 'Pošalji podatke';
        submitBlock.text = submitBlock.text || 'Pošalji formu i prijeđi na sljedeći korak.';

        const primaryButton = normalizeAction((submitBlock.buttons || [])[0] || {});
        primaryButton.label = primaryButton.label || 'Pošalji podatke';
        primaryButton.value = primaryButton.value || 'posalji_podatke';
        primaryButton.style = primaryButton.style || 'primary';
        primaryButton.action = ['submit_next', 'submit_stay'].includes(primaryButton.action) ? primaryButton.action : 'submit_next';
        primaryButton.require_submit = true;
        primaryButton.external_url = '';

        submitBlock.buttons = [primaryButton];

        return submitBlock;
    }

    function syncAutoSubmitBlocksForBlocks(blocks = []) {
        const normalizedBlocks = (blocks || []).map(normalizeBlock);
        const hasCaptureFields = normalizedBlocks.some(block => ['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field'].includes(block.type));
        const systemBlocks = normalizedBlocks.filter(isSystemSubmitBlock);
        const hasCustomSubmitAction = normalizedBlocks.some(block => !isSystemSubmitBlock(block) && blockHasSubmitAction(block));
        const contentBlocks = normalizedBlocks.filter(block => !isSystemSubmitBlock(block));
        let changed = false;

        if(systemBlocks.length > 1) {
            changed = true;
        }

        if(!hasCaptureFields || hasCustomSubmitAction) {
            return {
                blocks: contentBlocks,
                changed: changed || systemBlocks.length > 0
            };
        }

        const systemBlock = buildSystemSubmitBlock(systemBlocks[0] || null);

        if(systemBlocks.length !== 1) {
            changed = true;
        }

        return {
            blocks: [...contentBlocks, systemBlock],
            changed: changed || systemBlocks.length !== 1
        };
    }

    function normalizeRequireSubmitFlags() {
        let changed = false;

        if(state.payload?.landing_page) {
            changed = normalizeRequireSubmitFlagsForBlocks(state.payload.landing_page.blocks || []) || changed;
            changed = normalizeRequireSubmitFlagsForBlocks(state.payload.landing_page.variant_b_blocks || []) || changed;
            const syncedLandingA = syncAutoSubmitBlocksForBlocks(state.payload.landing_page.blocks || []);
            const syncedLandingB = syncAutoSubmitBlocksForBlocks(state.payload.landing_page.variant_b_blocks || []);
            state.payload.landing_page.blocks = syncedLandingA.blocks;
            state.payload.landing_page.variant_b_blocks = syncedLandingB.blocks;
            changed = syncedLandingA.changed || changed;
            changed = syncedLandingB.changed || changed;
        }

        (state.payload?.board || []).forEach(phase => {
            (phase.steps || []).forEach(step => {
                if(step?.page) {
                    changed = normalizeRequireSubmitFlagsForBlocks(step.page.blocks || []) || changed;
                    changed = normalizeRequireSubmitFlagsForBlocks(step.page.variant_b_blocks || []) || changed;
                    const syncedStepA = syncAutoSubmitBlocksForBlocks(step.page.blocks || []);
                    const syncedStepB = syncAutoSubmitBlocksForBlocks(step.page.variant_b_blocks || []);
                    step.page.blocks = syncedStepA.blocks;
                    step.page.variant_b_blocks = syncedStepB.blocks;
                    changed = syncedStepA.changed || changed;
                    changed = syncedStepB.changed || changed;
                }
            });
        });

        return changed;
    }

    function currentSurfaceHasSubmitAction() {
        return getEditableBlocks().some(block => blockHasSubmitAction(block));
    }

    function addDefaultSubmitCta() {
        const blocks = getEditableBlocks().slice();
        const submitBlock = buildSystemSubmitBlock({
            id: uid('cta_group'),
            label: 'Submit forma',
            title: 'Pošalji podatke',
            text: 'Jedan klik za slanje forme i nastavak funnel puta.',
            buttons: [{
                id: uid('btn'),
                label: 'Pošalji podatke',
                value: 'posalji_podatke',
                style: 'primary',
                action: 'submit_next',
                target_step_id: '',
                external_url: '',
                require_submit: true
            }]
        });
        blocks.push(submitBlock);
        setEditableBlocks(blocks);
        state.activeBlockId = submitBlock.id;
        renderAll();
    }

    function ensurePayload() {
        if(!state.payload || typeof state.payload !== 'object') {
            state.payload = {};
        }

        if(!state.payload.funnel) {
            state.payload.funnel = {name: 'VIP Funnel 2.0', slug: 'vip-funnel-2-0', status: 'draft', visibility_mode: 'testing_locked', owner_mode: 'shared'};
        }

        if(!state.payload.defaults || typeof state.payload.defaults !== 'object') {
            state.payload.defaults = {};
        }

        state.payload.defaults.hide_public_navbar = !!state.payload.defaults.hide_public_navbar;

        if(!state.payload.landing_page) {
            state.payload.landing_page = defaultSurface();
            state.payload.landing_page.name = studioMessages.landingTitle;
        }

        if(!Array.isArray(state.payload.paths)) {
            state.payload.paths = [];
        }

        if(!Array.isArray(state.payload.board)) {
            state.payload.board = [];
        }

        phaseDefinitions.forEach((phase, index) => {
            let phaseRow = state.payload.board.find(item => item.key === phase.key);
            if(!phaseRow) {
                phaseRow = {key: phase.key, steps: []};
                state.payload.board.push(phaseRow);
            }

            if(!Array.isArray(phaseRow.steps)) {
                phaseRow.steps = [];
            }

            phaseRow.steps.forEach((step, stepIndex) => {
                step.id = step.id || `${phase.key}_step_${stepIndex + 1}_${uid('step').slice(-6)}`;
                step.title = step.title || `${phase.title} korak`;
                step.summary = step.summary || '';
                step.preview_badge = step.preview_badge || phase.title;
                step.path_key = step.path_key || (pathOptions[0]?.path_key || 'business');
                step.page = normalizeSurface(step.page || defaultSurface(), step.title || `${phase.title} korak`);
            });
        });

        if(!state.activeStepId) {
            state.activeStepId = flatSteps()[0]?.id || '';
        }

        if(!state.payload.analytics) {
            state.payload.analytics = {primary_goal: 'lead_capture', ab_goal: 'submit'};
        }

        ensureActiveBlock();
    }

    function normalizeSurface(surface, fallbackName = 'Nova funnel stranica') {
        const normalized = Object.assign(defaultSurface(), surface || {});
        normalized.name = normalized.name || fallbackName;
        normalized.blocks = Array.isArray(normalized.blocks) ? normalized.blocks.map(normalizeBlock) : [];
        normalized.variant_b_blocks = Array.isArray(normalized.variant_b_blocks) ? normalized.variant_b_blocks.map(normalizeBlock) : [];
        normalized.variant_b_settings = normalizeVariantSurfaceSettings(normalized.variant_b_settings || {}, normalized);
        return normalized;
    }

    function normalizeBlock(block) {
        const type = (block && block.type) || 'headline';
        const normalized = Object.assign(defaultBlockByType(type), block || {});
        normalized.id = normalized.id || uid(type);
        if(!Object.prototype.hasOwnProperty.call(pageBlockWidthOptions, normalized.layout_width)) {
            normalized.layout_width = 'full';
        }
        normalized.buttons = Array.isArray(normalized.buttons) ? normalized.buttons.map(normalizeAction) : [];
        normalized.options = Array.isArray(normalized.options) ? normalized.options.map(normalizeAction) : [];
        if(type === 'radio_survey') {
            normalized.options = normalized.options.map(option => Object.assign({}, option, {style: 'primary'}));
        }
        return normalized;
    }

    function getBlockWidthLabel(widthKey = 'full') {
        return pageBlockWidthOptions[widthKey] || pageBlockWidthOptions.full || 'Puna širina';
    }

    function normalizeAction(action) {
        return Object.assign({
            id: uid('act'),
            label: 'Opcija',
            hint: '',
            value: 'opcija',
            style: 'primary',
            action: 'goto_step',
            target_step_id: '',
            external_url: '',
            require_submit: false
        }, action || {});
    }

    function flatSteps() {
        const steps = [];
        (state.payload.board || []).forEach(phase => {
            (phase.steps || []).forEach(step => {
                steps.push(Object.assign({phase_key: phase.key}, step));
            });
        });
        return steps;
    }

    function findStepRecord(stepId) {
        for(const phase of (state.payload.board || [])) {
            for(const step of (phase.steps || [])) {
                if(step.id === stepId) {
                    return {
                        step,
                        phaseKey: phase.key || ''
                    };
                }
            }
        }

        return null;
    }

    function getActiveStep() {
        return findStepRecord(state.activeStepId)?.step || null;
    }

    function getActiveStepPhaseKey() {
        return findStepRecord(state.activeStepId)?.phaseKey || '';
    }

    function setActiveStep(stepId) {
        state.activeStepId = stepId || '';
        state.screen = 'step';
        state.activeVariant = 'a';
        state.activeActionId = '';
        ensureActiveBlock();
        renderAll();
    }

    function getDefaultPhaseKeyForNewStep() {
        return getActiveStepPhaseKey() || phaseDefinitions[0]?.key || 'entry';
    }

    function getCurrentSurface() {
        if(state.screen === 'landing') {
            state.payload.landing_page = normalizeSurface(state.payload.landing_page, studioMessages.landingTitle);
            return state.payload.landing_page;
        }

        const activeStep = getActiveStep();
        if(!activeStep) {
            return normalizeSurface(defaultSurface(), 'Nova funnel stranica');
        }

        activeStep.page = normalizeSurface(activeStep.page, activeStep.title || 'Nova funnel stranica');
        return activeStep.page;
    }

    function ensureVariantBSurfaceState(surface) {
        if(!surface || typeof surface !== 'object') {
            return cloneVariantSurfaceSettings(defaultSurface());
        }

        surface.variant_b_settings = normalizeVariantSurfaceSettings(surface.variant_b_settings || {}, surface);
        return surface.variant_b_settings;
    }

    function getRenderableSurface() {
        const surface = getCurrentSurface();

        if(surface.ab_enabled && state.activeVariant === 'b') {
            return Object.assign({}, surface, ensureVariantBSurfaceState(surface));
        }

        return surface;
    }

    function getEditableSurfaceSettingsTarget() {
        const surface = getCurrentSurface();

        if(surface.ab_enabled && state.activeVariant === 'b') {
            return ensureVariantBSurfaceState(surface);
        }

        return surface;
    }

    function setCurrentSurface(surface) {
        if(state.screen === 'landing') {
            state.payload.landing_page = normalizeSurface(surface, studioMessages.landingTitle);
            return;
        }

        const activeStep = getActiveStep();
        if(activeStep) {
            activeStep.page = normalizeSurface(surface, activeStep.title || 'Nova funnel stranica');
        }
    }

    function getEditableBlocks() {
        const surface = getCurrentSurface();
        if(surface.ab_enabled && state.activeVariant === 'b') {
            if(!surface.variant_b_blocks.length && surface.blocks.length) {
                surface.variant_b_blocks = JSON.parse(JSON.stringify(surface.blocks));
            }
            return surface.variant_b_blocks;
        }

        return surface.blocks;
    }

    function setEditableBlocks(blocks) {
        const surface = getCurrentSurface();
        if(surface.ab_enabled && state.activeVariant === 'b') {
            surface.variant_b_blocks = blocks.map(normalizeBlock);
        } else {
            surface.blocks = blocks.map(normalizeBlock);
        }
    }

    function ensureActiveBlock() {
        const blocks = getEditableBlocks();
        if(!blocks.length) {
            state.activeBlockId = '';
            state.activeActionId = '';
            return;
        }

        if(!blocks.some(block => block.id === state.activeBlockId)) {
            state.activeBlockId = blocks[0].id;
        }

        const activeBlock = blocks.find(block => block.id === state.activeBlockId) || null;
        const items = activeBlock ? getBlockActionItems(activeBlock) : [];

        if(!items.some(item => item.id === state.activeActionId)) {
            state.activeActionId = '';
        }
    }

    function getActiveBlock() {
        return getEditableBlocks().find(block => block.id === state.activeBlockId) || null;
    }

    function toggleActiveAction(actionId = '') {
        state.activeActionId = state.activeActionId === actionId ? '' : actionId;
        renderAll();
    }

    function addBlock(type) {
        const block = normalizeBlock(defaultBlockByType(type));
        block.id = uid(type);
        const blocks = getEditableBlocks().slice();
        blocks.push(block);
        const isCaptureField = ['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field'].includes(type);
        if(isCaptureField && !blocks.some(item => {
            return blockHasSubmitAction(item);
        })) {
            const submitBlock = buildSystemSubmitBlock({
                id: uid('cta_group'),
                label: 'Submit forma',
                title: 'Pošalji podatke',
                text: 'Pošalji formu i prijeđi na sljedeći korak.',
                buttons: [{
                    id: uid('btn'),
                    label: 'Pošalji podatke',
                    value: 'posalji_podatke',
                    style: 'primary',
                    action: 'submit_next',
                    target_step_id: '',
                    external_url: '',
                    require_submit: true
                }]
            });
            blocks.push(submitBlock);
        }
        setEditableBlocks(blocks);
        state.activeBlockId = block.id;
        state.activeActionId = '';
        renderAll();
    }

    function duplicateBlock(blockId) {
        const blocks = getEditableBlocks().slice();
        const index = blocks.findIndex(block => block.id === blockId);
        if(index === -1) return;
        const clone = JSON.parse(JSON.stringify(blocks[index]));
        clone.id = uid(clone.type || 'block');
        clone.label = `${clone.label || 'Blok'} kopija`;
        if(Array.isArray(clone.buttons)) clone.buttons = clone.buttons.map(item => Object.assign({}, item, {id: uid('btn')}));
        if(Array.isArray(clone.options)) clone.options = clone.options.map(item => Object.assign({}, item, {id: uid('opt')}));
        if(Array.isArray(clone.product_mappings)) clone.product_mappings = clone.product_mappings.map(item => Object.assign({}, item, {id: uid('product_map')}));
        blocks.splice(index + 1, 0, clone);
        setEditableBlocks(blocks);
        state.activeBlockId = clone.id;
        state.activeActionId = '';
        renderAll();
    }

    function moveBlock(blockId, direction) {
        const blocks = getEditableBlocks().slice();
        const index = blocks.findIndex(block => block.id === blockId);
        const nextIndex = index + direction;
        if(index === -1 || nextIndex < 0 || nextIndex >= blocks.length) return;
        const [block] = blocks.splice(index, 1);
        blocks.splice(nextIndex, 0, block);
        setEditableBlocks(blocks);
        state.activeBlockId = block.id;
        renderAll();
    }

    function moveBlockToIndex(blockId, targetIndex) {
        const blocks = getEditableBlocks().slice();
        const currentIndex = blocks.findIndex(block => block.id === blockId);
        if(currentIndex === -1) return;

        const [block] = blocks.splice(currentIndex, 1);
        const safeIndex = Math.max(0, Math.min(targetIndex, blocks.length));
        blocks.splice(safeIndex, 0, block);

        setEditableBlocks(blocks);
        state.activeBlockId = block.id;
        renderAll();
    }

    function deleteBlock(blockId) {
        const blocks = getEditableBlocks().filter(block => block.id !== blockId);
        setEditableBlocks(blocks);
        state.activeActionId = '';
        ensureActiveBlock();
        renderAll();
    }

    function addAction(kind) {
        const block = getActiveBlock();
        if(!block) return;
        const key = kind === 'survey' ? 'options' : 'buttons';
        block[key] = Array.isArray(block[key]) ? block[key] : [];
        const action = normalizeAction({
            id: uid(kind === 'survey' ? 'opt' : 'btn'),
            label: kind === 'survey' ? 'Novi odgovor' : 'Novi gumb',
            value: kind === 'survey' ? 'novi_odgovor' : 'novi_gumb'
        });
        block[key].push(action);
        state.activeActionId = action.id;
        renderAll();
    }

    function removeAction(kind, actionId) {
        const block = getActiveBlock();
        if(!block) return;
        const key = kind === 'survey' ? 'options' : 'buttons';
        block[key] = (block[key] || []).filter(item => item.id !== actionId);
        if(state.activeActionId === actionId) {
            state.activeActionId = '';
        }
        renderAll();
    }

    function addProductMapping() {
        const block = getActiveBlock();
        if(!block || block.type !== 'product_offer') return;
        block.product_mappings = normalizeProductMappings(block.product_mappings || []);
        block.product_mappings.push({
            id: uid('product_map'),
            match_value: '',
            product_translation_key: '',
            product_blog_post_id: 0
        });
        renderAll();
    }

    function removeProductMapping(mappingId = '') {
        const block = getActiveBlock();
        if(!block || block.type !== 'product_offer') return;
        block.product_mappings = normalizeProductMappings(block.product_mappings || []).filter(mapping => mapping.id !== mappingId);
        renderAll();
    }

    function addStep(phaseKey) {
        const phase = (state.payload.board || []).find(item => item.key === phaseKey);
        if(!phase) return;
        const phaseDef = phaseDefinitions.find(item => item.key === phaseKey) || {title: 'Korak'};
        const stepId = `${phaseKey}_${uid('step')}`;
        phase.steps.push({
            id: stepId,
            path_key: pathOptions[0]?.path_key || 'business',
            row_key: pathOptions[0]?.path_key || 'business',
            card_type: 'offer',
            title: `${phaseDef.title} korak`,
            summary: 'Dodaj jasan cilj ovog koraka i što osoba ovdje treba doživjeti.',
            helper_text: '',
            cta: 'Kreni dalje',
            next: '',
            next_step_id: '',
            status_key: 'core',
            media_url: '',
            answers: [],
            tags: [],
            owner_user_id: 0,
            visibility_key: 'all',
            analytics_label: stepId,
            design_variant: 'card',
            preview_badge: phaseDef.title,
            preview_headline: `${phaseDef.title} korak`,
            preview_body: 'Dodaj sadržaj ovog koraka.',
            block_mode: 'message',
            background_color: '#152132',
            text_color: '#eef4ff',
            accent_color: '#67d8c9',
            button_options: [],
            page: normalizeSurface(defaultSurface(), `${phaseDef.title} korak`)
        });
        state.activeStepId = stepId;
        state.screen = 'step';
        ensureActiveBlock();
        renderAll();
    }

    function addStepFromRail() {
        addStep(getDefaultPhaseKeyForNewStep());
    }

    function deleteStep(stepId) {
        state.payload.board.forEach(phase => {
            phase.steps = (phase.steps || []).filter(step => step.id !== stepId);
        });
        state.activeStepId = flatSteps()[0]?.id || '';
        state.screen = state.activeStepId ? 'step' : 'landing';
        ensureActiveBlock();
        renderAll();
    }

    function moveStep(stepId, direction) {
        state.payload.board.forEach(phase => {
            const steps = phase.steps || [];
            const index = steps.findIndex(step => step.id === stepId);
            const nextIndex = index + direction;
            if(index === -1 || nextIndex < 0 || nextIndex >= steps.length) return;
            const [step] = steps.splice(index, 1);
            steps.splice(nextIndex, 0, step);
        });
        state.activeStepId = stepId;
        ensureActiveBlock();
        renderAll();
    }

    function moveStepToPhase(stepId, phaseKey, targetIndex) {
        let draggedStep = null;

        state.payload.board.forEach(phase => {
            const steps = phase.steps || [];
            const index = steps.findIndex(step => step.id === stepId);
            if(index !== -1) {
                [draggedStep] = steps.splice(index, 1);
            }
        });

        if(!draggedStep) {
            return;
        }

        const targetPhase = (state.payload.board || []).find(item => item.key === phaseKey);
        if(!targetPhase) {
            return;
        }

        if(!Array.isArray(targetPhase.steps)) {
            targetPhase.steps = [];
        }

        const safeIndex = Math.max(0, Math.min(targetIndex, targetPhase.steps.length));
        targetPhase.steps.splice(safeIndex, 0, draggedStep);

        state.activeStepId = draggedStep.id;
        state.screen = 'step';
        ensureActiveBlock();
        renderAll();
    }

    function moveStepRelativeToStep(stepId, targetStepId, position = 'before') {
        if(!stepId || !targetStepId || stepId === targetStepId) {
            return;
        }

        let draggedStep = null;

        state.payload.board.forEach(phase => {
            const steps = phase.steps || [];
            const index = steps.findIndex(step => step.id === stepId);

            if(index !== -1) {
                [draggedStep] = steps.splice(index, 1);
            }
        });

        if(!draggedStep) {
            return;
        }

        let targetPhase = null;
        let targetIndex = -1;

        state.payload.board.forEach(phase => {
            const steps = phase.steps || [];
            const index = steps.findIndex(step => step.id === targetStepId);

            if(index !== -1) {
                targetPhase = phase;
                targetIndex = index;
            }
        });

        if(!targetPhase) {
            const fallbackPhase = (state.payload.board || []).find(phase => Array.isArray(phase.steps)) || null;

            if(!fallbackPhase) {
                return;
            }

            fallbackPhase.steps.push(draggedStep);
        } else {
            const insertIndex = position === 'after' ? targetIndex + 1 : targetIndex;
            targetPhase.steps.splice(insertIndex, 0, draggedStep);
        }

        state.activeStepId = draggedStep.id;
        state.screen = 'step';
        ensureActiveBlock();
        renderAll();
    }

    function applyTemplate(templateKey) {
        const template = pageTemplates[templateKey];
        if(!template) return;
        const surface = getCurrentSurface();
        const templateBlocks = (template.blocks || []).map(item => normalizeBlock(Object.assign(defaultBlockByType(item.type || 'headline'), item, {id: uid(item.type || 'block')})));

        if(surface.ab_enabled && state.activeVariant === 'b') {
            surface.variant_b_blocks = templateBlocks;
            ensureVariantBSurfaceState(surface);
        } else {
            surface.blocks = templateBlocks;
        }
        ensureActiveBlock();
        renderAll();
    }

    function syncPayloadInput() {
        payloadInput.value = JSON.stringify(state.payload);
    }

    function getActionValidationErrors(action, block = null) {
        const errors = [];
        const isDormantRadioRouting = block?.type === 'radio_survey' && block?.route_on_submit === false;

        if(isDormantRadioRouting) {
            return errors;
        }

        if((action.action || '') === 'external_url' && !String(action.external_url || '').trim()) {
            errors.push({
                code: 'external_url_required',
                field: 'external_url',
                message: validationMessages.externalUrlRequired
            });
        }

        return errors;
    }

    function collectStudioValidationErrors() {
        const errors = [];

        const inspectBlocks = (blocks, context = {}) => {
            (blocks || []).forEach(block => {
                if(!['survey', 'radio_survey', 'cta_group'].includes(block.type)) {
                    return;
                }

                const items = getBlockActionItems(block);

                items.forEach(action => {
                    getActionValidationErrors(action, block).forEach(error => {
                        errors.push(Object.assign({}, error, {
                            page_role: context.page_role || 'landing',
                            step_id: context.step_id || '',
                            variant_key: context.variant_key || 'a',
                            block_id: block.id || '',
                            action_id: action.id || ''
                        }));
                    });
                });
            });
        };

        const landing = normalizeSurface(state.payload.landing_page || defaultSurface(), studioMessages.landingTitle);
        inspectBlocks(landing.blocks || [], {page_role: 'landing', variant_key: 'a'});
        if(landing.ab_enabled && Array.isArray(landing.variant_b_blocks)) {
            inspectBlocks(landing.variant_b_blocks || [], {page_role: 'landing', variant_key: 'b'});
        }

        (state.payload.board || []).forEach(phase => {
            (phase.steps || []).forEach(step => {
                const surface = normalizeSurface(step.page || defaultSurface(), step.title || 'Nova funnel stranica');
                inspectBlocks(surface.blocks || [], {page_role: 'step', step_id: step.id || '', variant_key: 'a'});
                if(surface.ab_enabled && Array.isArray(surface.variant_b_blocks)) {
                    inspectBlocks(surface.variant_b_blocks || [], {page_role: 'step', step_id: step.id || '', variant_key: 'b'});
                }
            });
        });

        return errors;
    }

    function refreshValidationState() {
        state.validation.errors = collectStudioValidationErrors();
    }

    function getActionValidationErrorsForCurrentView(action, block = null) {
        return getActionValidationErrors(action, block);
    }

    function focusValidationError(error) {
        if(!error) {
            return;
        }

        state.screen = error.page_role === 'step' ? 'step' : 'landing';

        if(error.page_role === 'step' && error.step_id) {
            state.activeStepId = error.step_id;
        }

        state.activeVariant = error.variant_key || 'a';
        state.activeBlockId = error.block_id || state.activeBlockId;
        state.activeActionId = error.action_id || state.activeActionId;
        renderAll();
    }

    function setSaveNotice(message = '', type = 'success') {
        if(!saveNotice) {
            return;
        }

        if(!message) {
            saveNotice.style.display = 'none';
            saveNotice.innerHTML = '';
            saveNotice.style.borderColor = '';
            saveNotice.style.background = '';
            saveNotice.style.color = '';
            return;
        }

        const palette = type === 'error'
            ? {
                border: 'rgba(239, 68, 68, 0.26)',
                background: 'rgba(127, 29, 29, 0.18)',
                color: '#fecaca'
            }
            : {
                border: 'rgba(103, 216, 201, 0.26)',
                background: 'rgba(18, 90, 82, 0.18)',
                color: '#d1fae5'
            };

        saveNotice.style.display = 'block';
        saveNotice.style.borderColor = palette.border;
        saveNotice.style.background = palette.background;
        saveNotice.style.color = palette.color;
        saveNotice.textContent = message;
    }

    function setSaveButtonState(isSaving = false) {
        if(!saveButtons.length) {
            return;
        }

        saveButtons.forEach(button => {
            button.disabled = isSaving;
            button.textContent = isSaving ? 'Spremam funnel...' : 'Spremi cijeli funnel';
        });
    }

    async function flushActiveEditorField() {
        const activeElement = document.activeElement;

        if(!(activeElement instanceof HTMLElement) || !workspaceRoot.contains(activeElement)) {
            return;
        }

        applyGenericFieldUpdate(activeElement);

        if(typeof activeElement.blur === 'function') {
            activeElement.blur();
        }

        await new Promise(resolve => window.requestAnimationFrame(() => resolve()));
    }

    async function saveStudio() {
        if(state.save.inFlight) {
            return;
        }

        await flushActiveEditorField();
        syncPayloadInput();
        refreshValidationState();
        setSaveNotice('');

        if(state.validation.errors.length) {
            focusValidationError(state.validation.errors[0]);
            setSaveNotice(validationMessages.fixBeforeSave, 'error');
            return;
        }

        state.save.inFlight = true;
        setSaveButtonState(true);

        try {
            const formData = new FormData();
            formData.append('token', tokenInput?.value || '');
            formData.append('global_token', globalTokenInput?.value || '');
            formData.append('funnel_id', funnelIdInput?.value || '');
            formData.append('vip_funnel_studio_payload', payloadInput.value || '');

            const response = await fetch(saveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json().catch(() => null);

            if(!response.ok || !result || result.status !== 'success') {
                if(Array.isArray(result?.details?.validation_errors) && result.details.validation_errors.length) {
                    state.validation.errors = result.details.validation_errors;
                    focusValidationError(result.details.validation_errors[0]);
                }
                const message = Array.isArray(result?.message) ? result.message.join(' ') : (result?.message || 'Spremanje trenutno nije uspjelo.');
                throw new Error(message);
            }

            const message = Array.isArray(result.message) ? result.message.join(' ') : result.message;
            setSaveNotice(message || 'Promjene su spremljene.', 'success');
        } catch(error) {
            setSaveNotice(error.message || 'Spremanje trenutno nije uspjelo. Osvježi stranicu i pokušaj ponovno.', 'error');
        } finally {
            state.save.inFlight = false;
            setSaveButtonState(false);
        }
    }

    async function uploadImageForBlock(fileInput) {
        const blockId = fileInput?.getAttribute('data-vf-image-upload') || '';
        const file = fileInput?.files?.[0] || null;

        if(!blockId || !file) {
            setSaveNotice(validationMessages.imageUploadMissing, 'error');
            return;
        }

        if(imageUploadMaxSizeMb > 0 && file.size > imageUploadMaxSizeMb * 1000000) {
            setSaveNotice(`Maksimalna veličina slike za VIP Funnel je ${imageUploadMaxSizeMb} MB.`, 'error');
            fileInput.value = '';
            return;
        }

        const block = getEditableBlocks().find(item => item.id === blockId) || null;
        if(!block) {
            setSaveNotice(validationMessages.imageUploadFailed, 'error');
            fileInput.value = '';
            return;
        }

        setSaveNotice(validationMessages.imageUploadUploading, 'success');
        fileInput.disabled = true;

        try {
            const formData = new FormData();
            formData.append('token', tokenInput?.value || '');
            formData.append('global_token', globalTokenInput?.value || '');
            formData.append('image', file);

            const response = await fetch(imageUploadUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json().catch(() => null);

            if(!response.ok || !result || result.status !== 'success' || !result.details?.image_url) {
                const message = Array.isArray(result?.message) ? result.message.join(' ') : (result?.message || validationMessages.imageUploadFailed);
                throw new Error(message);
            }

            block.media_url = result.details.image_url;
            registerImageGalleryEntry({
                image: result.details.image || '',
                image_url: result.details.image_url,
                created_at: new Date().toISOString()
            });
            renderAll();
            setSaveNotice(Array.isArray(result.message) ? result.message.join(' ') : (result.message || validationMessages.imageUploaded), 'success');
        } catch(error) {
            setSaveNotice(error.message || validationMessages.imageUploadFailed, 'error');
        } finally {
            fileInput.disabled = false;
            fileInput.value = '';
        }
    }

    function renderTabs() {
        const steps = flatSteps();

        tabsRoot.innerHTML = `
            <button type="button" class="vf-tab ${state.screen === 'landing' ? 'is-active' : ''}" data-vf-screen="landing">
                ${escapeHtml(analyticsMessages.landingTab)}
            </button>

            ${steps.map((step, index) => {
                const phase = phaseDefinitions.find(item => item.key === step.phase_key) || null;
                return `
                <button
                    type="button"
                    class="vf-step-tab ${state.screen === 'step' && state.activeStepId === step.id ? 'is-active' : ''}"
                    data-vf-step-tab="${escapeHtml(step.id)}"
                    draggable="true"
                >
                    <span class="vf-step-tab__index">${index + 1}</span>
                    <span class="vf-step-tab__main">
                        <span class="vf-step-tab__phase">${escapeHtml(phase?.title || analyticsMessages.stepDefault)}</span>
                        <span class="vf-step-tab__label">${escapeHtml(step.title || `${analyticsMessages.stepDefault} ${index + 1}`)}</span>
                    </span>
                    <span class="vf-step-tab__grip" aria-hidden="true">⋮⋮</span>
                </button>
            `;
            }).join('')}

            <button type="button" class="vf-tab vf-tab--add" data-vf-add-step-rail="1" title="${escapeHtml(analyticsMessages.addStepTitle)}">+</button>

            <button type="button" class="vf-tab ${state.screen === 'analytics' ? 'is-active' : ''}" data-vf-screen="analytics">
                ${escapeHtml(analyticsMessages.analyticsTab)}
            </button>
        `;
    }

    function renderWorkspace() {
        if(state.screen === 'landing') {
            workspaceRoot.innerHTML = renderLandingWorkspace();
            return;
        }

        if(state.screen === 'analytics') {
            workspaceRoot.innerHTML = renderAnalyticsWorkspace();
            return;
        }

        workspaceRoot.innerHTML = renderStepWorkspace();
    }

    function renderLandingWorkspace() {
        const surface = getRenderableSurface();
        return `
            ${renderFunnelSettingsEditor()}
            <div class="vf-card">
                <div class="vf-card__head">
                    <div>
                        <h2 class="vf-card__title">${escapeHtml(studioMessages.landingTitle)}</h2>
                        <div class="vf-card__sub">${escapeHtml(studioMessages.landingSub)}</div>
                    </div>
                </div>
                <div class="vf-card__body">
                    <div class="vf-note">${studioMessages.landingNote}</div>
                    ${renderSurfaceBuilder(surface, 'landing')}
                </div>
            </div>
        `;
    }

    function renderFunnelSettingsEditor() {
        const funnel = state.payload.funnel || {};
        const defaults = state.payload.defaults || {};

        return `
            <div class="vf-card">
                <div class="vf-card__head">
                    <div>
                        <h2 class="vf-card__title">Postavke odabranog funnel-a</h2>
                        <div class="vf-card__sub">Ovdje određuješ naziv, javni URL, status i prikaz glavnog menija na javnom funnel-u.</div>
                    </div>
                </div>
                <div class="vf-card__body vf-stack">
                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Naziv funnel-a</label>
                            <input type="text" data-vf-funnel-field="name" value="${escapeHtml(funnel.name || '')}" />
                        </div>
                        <div class="vf-field">
                            <label>URL slug</label>
                            <input type="text" data-vf-funnel-field="slug" value="${escapeHtml(funnel.slug || '')}" />
                            <div class="vf-field__hint">Primjer: stjepan-online-posao. Sustav će kod spremanja spriječiti dupli slug.</div>
                        </div>
                    </div>

                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Status</label>
                            <select data-vf-funnel-field="status">
                                ${['draft', 'testing', 'active'].map(status => `<option value="${status}" ${String(funnel.status || 'draft') === status ? 'selected' : ''}>${status}</option>`).join('')}
                            </select>
                        </div>
                        <div class="vf-field">
                            <label>Vidljivost</label>
                            <select data-vf-funnel-field="visibility_mode">
                                ${[
                                    ['testing_locked', 'Test / zaključano'],
                                    ['pro_live', 'PRO live'],
                                    ['private', 'Privatno']
                                ].map(([key, label]) => `<option value="${key}" ${String(funnel.visibility_mode || 'testing_locked') === key ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                            </select>
                        </div>
                    </div>

                    <label class="vf-toggle">
                        <input type="checkbox" data-vf-default-toggle="hide_public_navbar" ${defaults.hide_public_navbar ? 'checked' : ''}>
                        Sakrij glavni FCC meni kada se ovaj funnel javno otvori
                    </label>
                </div>
            </div>
        `;
    }

    function renderStepWorkspace() {
        const activeStep = getActiveStep();
        return `
            <div class="vf-card">
                <div class="vf-card__head">
                    <div>
                        <h2 class="vf-card__title">${activeStep ? escapeHtml(activeStep.title || studioMessages.stepDefaultTitle) : escapeHtml(studioMessages.stepDefaultTitle)}</h2>
                        <div class="vf-card__sub">${escapeHtml(studioMessages.stepSub)}</div>
                    </div>
                </div>
                <div class="vf-card__body">
                    ${activeStep ? renderStepMetaEditor(activeStep) : `<div class="vf-empty">${escapeHtml(studioMessages.stepEmptyAdd)}</div>`}
                </div>
            </div>

            <div class="vf-card">
                <div class="vf-card__head">
                    <div>
                        <h2 class="vf-card__title">${escapeHtml(studioMessages.stepBuilderTitle)}</h2>
                        <div class="vf-card__sub">${escapeHtml(studioMessages.stepBuilderSub)}</div>
                    </div>
                </div>
                <div class="vf-card__body">
                    ${activeStep ? renderSurfaceBuilder(getRenderableSurface(), 'step') : `<div class="vf-empty">${escapeHtml(studioMessages.stepEmptySelect)}</div>`}
                </div>
            </div>
        `;
    }

    function renderAnalyticsWorkspace() {
        const analytics = state.analytics || {};
        const ab = analytics.ab || {};
        const bestStep = analytics.best_step || null;
        const bestSelection = analytics.best_selection || null;
        const steps = Array.isArray(analytics.steps) ? analytics.steps : [];
        const selections = Array.isArray(analytics.selections) ? analytics.selections : [];
        const demo = analytics.demo || {};
        const recentEvents = Array.isArray(analytics.recent_events) ? analytics.recent_events : [];
        const recentDemoEvents = Array.isArray(demo.recent_events) ? demo.recent_events : [];

        const stepsTable = steps.length ? `
            <div class="vf-analytics-table-wrap">
                <table class="vf-analytics-table">
                    <thead>
                        <tr>
                            <th>${escapeHtml(analyticsMessages.tableStep)}</th>
                            <th>${escapeHtml(analyticsMessages.tablePhase)}</th>
                            <th>${escapeHtml(analyticsMessages.tablePath)}</th>
                            <th>${escapeHtml(analyticsMessages.tableViews)}</th>
                            <th>${escapeHtml(analyticsMessages.tableVisitors)}</th>
                            <th>${escapeHtml(analyticsMessages.tableSubmits)}</th>
                            <th>${escapeHtml(analyticsMessages.tableLeads)}</th>
                            <th>${escapeHtml(analyticsMessages.tableAdvances)}</th>
                            <th>${escapeHtml(analyticsMessages.tableRate)}</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${steps.map((item) => `
                            <tr>
                                <td>
                                    <div class="vf-analytics-step-title">${escapeHtml(item.title || item.step_key || analyticsMessages.stepDefault)}</div>
                                    <div class="vf-analytics-step-sub">${escapeHtml(item.step_key || '')}</div>
                                </td>
                                <td>${escapeHtml(item.phase_title || '—')}</td>
                                <td>${escapeHtml(item.path_title || '—')}</td>
                                <td>${numberFormat(item.views || 0)}</td>
                                <td>${numberFormat(item.visitors || 0)}</td>
                                <td>${numberFormat(item.submits || 0)}</td>
                                <td>${numberFormat(item.leads || 0)}</td>
                                <td>${numberFormat(item.advances || 0)}</td>
                                <td><span class="vf-analytics-rate">${rateFormat(item.submit_rate || 0)}</span></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        ` : `<div class="vf-analytics-empty">${escapeHtml(analyticsMessages.emptyRows)}</div>`;

        const selectionsTable = selections.length ? `
            <div class="vf-analytics-table-wrap">
                <table class="vf-analytics-table">
                    <thead>
                        <tr>
                            <th>${escapeHtml(analyticsMessages.tableSelectionLabel)}</th>
                            <th>${escapeHtml(analyticsMessages.tableSelection)}</th>
                            <th>${escapeHtml(analyticsMessages.tableSubmits)}</th>
                            <th>${escapeHtml(analyticsMessages.tableLeads)}</th>
                            <th>${escapeHtml(analyticsMessages.tableAdvances)}</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${selections.map((item) => `
                            <tr>
                                <td>
                                    <div class="vf-analytics-step-title">${escapeHtml(item.label || item.selection_key || '—')}</div>
                                </td>
                                <td><span class="vf-chip">${escapeHtml(item.selection_key || '')}</span></td>
                                <td>${numberFormat(item.submits || 0)}</td>
                                <td>${numberFormat(item.leads || 0)}</td>
                                <td>${numberFormat(item.advances || 0)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        ` : `<div class="vf-analytics-empty">${escapeHtml(analyticsMessages.emptyRows)}</div>`;

        const recentEventList = recentEvents.length ? `
            <div class="vf-analytics-list">
                ${recentEvents.map((item) => `
                    <div class="vf-analytics-list-item">
                        <div class="vf-analytics-list-top">
                            <div>
                                <div class="vf-analytics-list-title">${escapeHtml(item.event_type_label || item.event_type || '')}</div>
                                <div class="vf-analytics-list-sub">${escapeHtml(item.step_title || item.step_key || '')}</div>
                            </div>
                            <span class="vf-chip">${escapeHtml(variantLabel(item.variant_key || ''))}</span>
                        </div>
                        <div class="vf-analytics-list-meta">
                            ${item.label ? `<span class="vf-chip">${escapeHtml(item.label)}</span>` : ''}
                            <span class="vf-chip">${escapeHtml(analyticsMessages.eventAt)}: ${escapeHtml(item.datetime || '')}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
        ` : `<div class="vf-analytics-empty">${escapeHtml(analyticsMessages.emptyRecent)}</div>`;

        const recentDemoList = recentDemoEvents.length ? `
            <div class="vf-analytics-list">
                ${recentDemoEvents.map((item) => `
                    <div class="vf-analytics-list-item">
                        <div class="vf-analytics-list-top">
                            <div>
                                <div class="vf-analytics-list-title">${escapeHtml(item.event_label || item.event_key || '')}</div>
                                <div class="vf-analytics-list-sub">${escapeHtml(item.lead_name || '')}</div>
                            </div>
                            <span class="vf-chip">${escapeHtml(analyticsMessages.eventAt)}: ${escapeHtml(item.datetime || '')}</span>
                        </div>
                        ${item.actor_name ? `<div class="vf-analytics-list-meta"><span class="vf-chip">${escapeHtml(analyticsMessages.eventBy)}: ${escapeHtml(item.actor_name)}</span></div>` : ''}
                    </div>
                `).join('')}
            </div>
        ` : `<div class="vf-analytics-empty">${escapeHtml(analyticsMessages.emptyDemoRecent)}</div>`;

        return `
            <div class="vf-analytics-layout">
                <div class="vf-card">
                    <div class="vf-card__head">
                        <div>
                            <h2 class="vf-card__title">${escapeHtml(analyticsMessages.overviewTitle)}</h2>
                            <div class="vf-card__sub">${escapeHtml(analyticsMessages.overviewSub)}</div>
                        </div>
                    </div>
                    <div class="vf-card__body">
                        <div class="vf-analytics-grid">
                            <div class="vf-kpi">
                                <div class="vf-kpi__label">${escapeHtml(analyticsMessages.views)}</div>
                                <div class="vf-kpi__value">${numberFormat(analytics.views || 0)}</div>
                            </div>
                            <div class="vf-kpi">
                                <div class="vf-kpi__label">${escapeHtml(analyticsMessages.uniqueVisitors)}</div>
                                <div class="vf-kpi__value">${numberFormat(analytics.unique_visitors || 0)}</div>
                            </div>
                            <div class="vf-kpi">
                                <div class="vf-kpi__label">${escapeHtml(analyticsMessages.submits)}</div>
                                <div class="vf-kpi__value">${numberFormat(analytics.submits || 0)}</div>
                            </div>
                            <div class="vf-kpi">
                                <div class="vf-kpi__label">${escapeHtml(analyticsMessages.leads)}</div>
                                <div class="vf-kpi__value">${numberFormat(analytics.leads || 0)}</div>
                            </div>
                            <div class="vf-kpi">
                                <div class="vf-kpi__label">${escapeHtml(analyticsMessages.advances)}</div>
                                <div class="vf-kpi__value">${numberFormat(analytics.advances || 0)}</div>
                            </div>
                            <div class="vf-kpi">
                                <div class="vf-kpi__label">${escapeHtml(analyticsMessages.contactsSynced)}</div>
                                <div class="vf-kpi__value">${numberFormat(analytics.contacts_in_data || 0)}</div>
                            </div>
                        </div>

                        <div class="vf-card-actions" style="margin-top:1rem;">
                            <span class="vf-chip">${escapeHtml(analyticsMessages.submitRate)}: ${rateFormat(analytics.submit_rate || 0)}</span>
                            <span class="vf-chip">${escapeHtml(analyticsMessages.leadRate)}: ${rateFormat(analytics.lead_rate || 0)}</span>
                            <span class="vf-chip">${escapeHtml(analyticsMessages.bestStep)}: ${escapeHtml(bestStep ? (bestStep.title || bestStep.step_key || analyticsMessages.winnerNone) : analyticsMessages.winnerNone)}</span>
                            <span class="vf-chip">${escapeHtml(analyticsMessages.bestSelection)}: ${escapeHtml(bestSelection ? (bestSelection.label || bestSelection.selection_key || analyticsMessages.winnerNone) : analyticsMessages.winnerNone)}</span>
                        </div>
                    </div>
                </div>

                <div class="vf-analytics-split">
                    <div class="vf-card">
                        <div class="vf-card__head">
                            <div>
                                <h2 class="vf-card__title">${escapeHtml(analyticsMessages.stepsTitle)}</h2>
                                <div class="vf-card__sub">${escapeHtml(analyticsMessages.stepsSub)}</div>
                            </div>
                        </div>
                        <div class="vf-card__body">${stepsTable}</div>
                    </div>

                    <div class="vf-card">
                        <div class="vf-card__head">
                            <div>
                                <h2 class="vf-card__title">${escapeHtml(analyticsMessages.selectionsTitle)}</h2>
                                <div class="vf-card__sub">${escapeHtml(analyticsMessages.selectionsSub)}</div>
                            </div>
                        </div>
                        <div class="vf-card__body">${selectionsTable}</div>
                    </div>
                </div>

                <div class="vf-analytics-split">
                    <div class="vf-card">
                        <div class="vf-card__head">
                            <div>
                                <h2 class="vf-card__title">${escapeHtml(analyticsMessages.abTitle)}</h2>
                                <div class="vf-card__sub">${escapeHtml(analyticsMessages.abSub)}</div>
                            </div>
                        </div>
                        <div class="vf-card__body">
                            <div class="vf-analytics-grid">
                                <div class="vf-kpi">
                                    <div class="vf-kpi__label">${escapeHtml(analyticsMessages.variantA)} · ${escapeHtml(analyticsMessages.views)}</div>
                                    <div class="vf-kpi__value">${numberFormat(ab.a_views || 0)}</div>
                                </div>
                                <div class="vf-kpi">
                                    <div class="vf-kpi__label">${escapeHtml(analyticsMessages.variantB)} · ${escapeHtml(analyticsMessages.views)}</div>
                                    <div class="vf-kpi__value">${numberFormat(ab.b_views || 0)}</div>
                                </div>
                                <div class="vf-kpi">
                                    <div class="vf-kpi__label">${escapeHtml(analyticsMessages.variantA)} · ${escapeHtml(analyticsMessages.submits)}</div>
                                    <div class="vf-kpi__value">${numberFormat(ab.a_submits || 0)}</div>
                                </div>
                                <div class="vf-kpi">
                                    <div class="vf-kpi__label">${escapeHtml(analyticsMessages.variantB)} · ${escapeHtml(analyticsMessages.submits)}</div>
                                    <div class="vf-kpi__value">${numberFormat(ab.b_submits || 0)}</div>
                                </div>
                            </div>
                            <div class="vf-card-actions" style="margin-top:1rem;">
                                <span class="vf-chip">${escapeHtml(analyticsMessages.variantA)}: ${rateFormat(ab.a_rate || 0)}</span>
                                <span class="vf-chip">${escapeHtml(analyticsMessages.variantB)}: ${rateFormat(ab.b_rate || 0)}</span>
                                <span class="vf-chip">${escapeHtml(analyticsMessages.winner)}: ${escapeHtml(variantLabel(ab.winner || ''))}</span>
                            </div>
                        </div>
                    </div>

                    <div class="vf-card">
                        <div class="vf-card__head">
                            <div>
                                <h2 class="vf-card__title">${escapeHtml(analyticsMessages.demoTitle)}</h2>
                                <div class="vf-card__sub">${escapeHtml(analyticsMessages.demoSub)}</div>
                            </div>
                        </div>
                        <div class="vf-card__body">
                            <div class="vf-analytics-grid">
                                <div class="vf-kpi">
                                    <div class="vf-kpi__label">${escapeHtml(analyticsMessages.requests)}</div>
                                    <div class="vf-kpi__value">${numberFormat(demo.requests || 0)}</div>
                                </div>
                                <div class="vf-kpi">
                                    <div class="vf-kpi__label">${escapeHtml(analyticsMessages.approved)}</div>
                                    <div class="vf-kpi__value">${numberFormat(demo.approved || 0)}</div>
                                </div>
                                <div class="vf-kpi">
                                    <div class="vf-kpi__label">${escapeHtml(analyticsMessages.live)}</div>
                                    <div class="vf-kpi__value">${numberFormat(demo.live || 0)}</div>
                                </div>
                                <div class="vf-kpi">
                                    <div class="vf-kpi__label">${escapeHtml(analyticsMessages.converted)}</div>
                                    <div class="vf-kpi__value">${numberFormat(demo.converted || 0)}</div>
                                </div>
                                <div class="vf-kpi">
                                    <div class="vf-kpi__label">${escapeHtml(analyticsMessages.archived)}</div>
                                    <div class="vf-kpi__value">${numberFormat(demo.archived || 0)}</div>
                                </div>
                                <div class="vf-kpi">
                                    <div class="vf-kpi__label">${escapeHtml(analyticsMessages.activationRate)}</div>
                                    <div class="vf-kpi__value">${rateFormat(demo.activation_rate || 0)}</div>
                                </div>
                            </div>
                            <div class="vf-card-actions" style="margin-top:1rem;">
                                <span class="vf-chip">${escapeHtml(analyticsMessages.conversionRate)}: ${rateFormat(demo.conversion_rate || 0)}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="vf-analytics-split">
                    <div class="vf-card">
                        <div class="vf-card__head">
                            <div>
                                <h2 class="vf-card__title">${escapeHtml(analyticsMessages.recentEventsTitle)}</h2>
                                <div class="vf-card__sub">${escapeHtml(analyticsMessages.recentEventsSub)}</div>
                            </div>
                        </div>
                        <div class="vf-card__body">${recentEventList}</div>
                    </div>

                    <div class="vf-card">
                        <div class="vf-card__head">
                            <div>
                                <h2 class="vf-card__title">${escapeHtml(analyticsMessages.recentDemoTitle)}</h2>
                                <div class="vf-card__sub">${escapeHtml(analyticsMessages.recentDemoSub)}</div>
                            </div>
                        </div>
                        <div class="vf-card__body">${recentDemoList}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderStepMap() {
        return phaseDefinitions.map(phase => {
            const phaseRow = (state.payload.board || []).find(item => item.key === phase.key) || {steps: []};
            const steps = phaseRow.steps || [];
            return `
                <div class="vf-phase">
                    <div class="vf-phase__head">
                        <div>
                            <div class="vf-phase__title">${escapeHtml(phase.title)}</div>
                            <div class="vf-phase__sub">${escapeHtml(phase.subtitle || '')}</div>
                        </div>
                        <button type="button" class="vf-btn" data-vf-add-step="${escapeHtml(phase.key)}">+ Dodaj korak</button>
                    </div>
                    <div class="vf-phase__steps" data-vf-step-dropzone="${escapeHtml(phase.key)}">
                        ${steps.length ? steps.map(step => renderStepCard(step, phase)).join('') : '<div class="vf-empty">Još nema koraka u ovoj fazi.</div>'}
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderStepCard(step, phase) {
        const isActive = step.id === state.activeStepId;
        return `
            <div class="vf-step-card ${isActive ? 'is-active' : ''}" data-vf-step-card="${escapeHtml(step.id)}" data-vf-step-id="${escapeHtml(step.id)}" data-vf-step-phase="${escapeHtml(phase.key)}" draggable="true">
                <div class="vf-chip-row">
                    <span class="vf-chip">${escapeHtml(phase.title)}</span>
                    <span class="vf-chip">${escapeHtml((pathOptions.find(item => item.path_key === step.path_key)?.title) || step.path_key || 'Put')}</span>
                </div>
                <div class="vf-step-card__title">${escapeHtml(step.title || 'Korak')}</div>
                <div class="vf-step-card__text">${escapeHtml(step.summary || 'Dodaj kratak opis i jasan cilj ovog koraka.')}</div>
                <div class="vf-card-actions">
                    <button type="button" data-vf-step-up="${escapeHtml(step.id)}">Gore</button>
                    <button type="button" data-vf-step-down="${escapeHtml(step.id)}">Dolje</button>
                    <button type="button" data-vf-step-delete="${escapeHtml(step.id)}">Obriši</button>
                </div>
            </div>
        `;
    }

    function renderStepMetaEditor(step) {
        return `
            <div class="vf-stack">
                <div class="vf-step-toolbar">
                    <div class="vf-step-toolbar__meta">${escapeHtml(studioMessages.stepToolbar)}</div>
                    <button type="button" class="vf-btn vf-btn--danger" data-vf-delete-current-step="${escapeHtml(step.id)}">${escapeHtml(studioMessages.stepDelete)}</button>
                </div>
                <div class="vf-section-label">${escapeHtml(studioMessages.stepSettings)}</div>
                <div class="vf-field">
                    <label>${escapeHtml(studioMessages.stepTitleLabel)}</label>
                    <input type="text" data-vf-step-field="title" value="${escapeHtml(step.title || '')}" />
                </div>
                <div class="vf-field">
                    <label>${escapeHtml(studioMessages.stepSummaryLabel)}</label>
                    <textarea data-vf-step-field="summary">${escapeHtml(step.summary || '')}</textarea>
                </div>
                <div class="vf-two">
                    <div class="vf-field">
                        <label>${escapeHtml(studioMessages.stepBadgeLabel)}</label>
                        <input type="text" data-vf-step-field="preview_badge" value="${escapeHtml(step.preview_badge || '')}" />
                    </div>
                    <div class="vf-field">
                        <label>${escapeHtml(studioMessages.stepPhaseLabel)}</label>
                        <select data-vf-step-phase-select="1">
                            ${phaseDefinitions.map(phase => `<option value="${escapeHtml(phase.key)}" ${String(step.phase_key || '') === String(phase.key) ? 'selected' : ''}>${escapeHtml(phase.title)}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="vf-two">
                    <div class="vf-field">
                        <label>${escapeHtml(studioMessages.stepPathLabel)}</label>
                        <select data-vf-step-field="path_key">
                            ${pathOptions.map(path => `<option value="${escapeHtml(path.path_key)}" ${step.path_key === path.path_key ? 'selected' : ''}>${escapeHtml(path.title)}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="vf-field">
                    <label>${escapeHtml(studioMessages.stepLegacyCtaLabel)}</label>
                    <input type="text" data-vf-step-field="cta" value="${escapeHtml(step.cta || '')}" />
                </div>
            </div>
        `;
    }

    function renderSurfaceBuilder(surface, scope) {
        const blocks = getEditableBlocks();
        const activeBlock = getActiveBlock();
        const allowAB = true;
        const hasCaptureFields = currentSurfaceHasCaptureFields();
        const hasDeferredSurvey = currentSurfaceHasDeferredSurvey();
        const hasSubmitAction = currentSurfaceHasSubmitAction();
        return `
            <div class="vf-builder-columns">
                <div class="vf-stack">
                    <div class="vf-card" style="box-shadow:none;">
                        <div class="vf-card__head">
                        <div>
                                <h3 class="vf-card__title">${escapeHtml(studioMessages.surfaceDesignTitle)}</h3>
                                <div class="vf-card__sub">${escapeHtml(studioMessages.surfaceDesignSub)}</div>
                            </div>
                        </div>
                        <div class="vf-card__body vf-stack">
                            <div class="vf-field">
                                <label>${escapeHtml(studioMessages.surfaceNameLabel)}</label>
                                <input type="text" data-vf-surface-field="name" value="${escapeHtml(surface.name || '')}" />
                            </div>

                            <div class="vf-two">
                                <div class="vf-field">
                                    <label>${escapeHtml(studioMessages.surfaceBackgroundLabel)}</label>
                                    <input type="color" data-vf-surface-field="background_color" value="${escapeHtml(surface.background_color || '#0f172a')}" />
                                </div>
                                <div class="vf-field">
                                    <label>${escapeHtml(studioMessages.surfaceColorLabel)}</label>
                                    <input type="color" data-vf-surface-field="surface_color" value="${escapeHtml(surface.surface_color || '#152132')}" />
                                </div>
                            </div>

                            <div class="vf-two">
                                <div class="vf-field">
                                    <label>${escapeHtml(studioMessages.surfaceTextColorLabel)}</label>
                                    <input type="color" data-vf-surface-field="text_color" value="${escapeHtml(surface.text_color || '#eef4ff')}" />
                                </div>
                                <div class="vf-field">
                                    <label>${escapeHtml(studioMessages.surfaceAccentLabel)}</label>
                                    <input type="color" data-vf-surface-field="accent_color" value="${escapeHtml(surface.accent_color || '#67d8c9')}" />
                                </div>
                            </div>

                            <div class="vf-two">
                                <div class="vf-field">
                                    <label>${escapeHtml(studioMessages.surfaceWidthLabel)}</label>
                                    <select data-vf-surface-field="max_width">
                                        ${Object.entries(pageWidthOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${surface.max_width === key ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                                    </select>
                                </div>
                                <div class="vf-field">
                                    <label>${escapeHtml(studioMessages.surfaceAbLabel)}</label>
                                    <input type="number" min="5" max="95" data-vf-surface-field="ab_distribution" value="${escapeHtml(surface.ab_distribution || 50)}" />
                                </div>
                            </div>

                            <label class="vf-toggle"><input type="checkbox" data-vf-surface-toggle="show_progress" ${surface.show_progress ? 'checked' : ''}> ${escapeHtml(studioMessages.surfaceShowProgress)}</label>
                            ${allowAB ? `<label class="vf-toggle"><input type="checkbox" data-vf-surface-toggle="ab_enabled" ${surface.ab_enabled ? 'checked' : ''}> ${escapeHtml(studioMessages.surfaceAbEnabled)}</label>` : ''}

                            ${surface.ab_enabled ? `
                                <div class="vf-inline">
                                    <button type="button" class="vf-btn ${state.activeVariant === 'a' ? 'vf-btn--primary' : ''}" data-vf-variant="a">${escapeHtml(studioMessages.surfaceEditVariantA)}</button>
                                    <button type="button" class="vf-btn ${state.activeVariant === 'b' ? 'vf-btn--primary' : ''}" data-vf-variant="b">${escapeHtml(studioMessages.surfaceEditVariantB)}</button>
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <div class="vf-card" style="box-shadow:none;">
                        <div class="vf-card__head">
                            <div>
                                <h3 class="vf-card__title">${escapeHtml(studioMessages.surfaceBlocksTitle)}</h3>
                                <div class="vf-card__sub">${escapeHtml(studioMessages.surfaceBlocksSub)}</div>
                            </div>
                        </div>
                        <div class="vf-card__body">
                            ${(hasCaptureFields || hasDeferredSurvey) && !hasSubmitAction ? `
                                <div class="vf-note" style="margin-bottom:.95rem;">
                                    ${escapeHtml(hasDeferredSurvey && !hasCaptureFields ? studioMessages.surfaceSubmitNoteSurvey : studioMessages.surfaceSubmitNoteForm)}
                                    <div class="vf-card-actions" style="margin-top:.75rem;">
                                        <button type="button" class="vf-btn vf-btn--primary" data-vf-add-submit-cta="1">${escapeHtml(studioMessages.surfaceAddSubmitButton)}</button>
                                    </div>
                                </div>
                            ` : ''}
                            <div class="vf-block-adder">
                                <select id="vf_add_block_select_${scope}">
                                    ${Object.entries(pageBlockTypes).map(([key, label]) => `<option value="${escapeHtml(key)}">${escapeHtml(label)}</option>`).join('')}
                                </select>
                                <button type="button" class="vf-btn vf-btn--primary" data-vf-add-block="${scope}">${escapeHtml(studioMessages.surfaceAddBlockButton)}</button>
                            </div>
                            <div class="vf-blocks-list" data-vf-block-dropzone="${escapeHtml(scope)}">
                                ${blocks.length ? blocks.map(block => renderBlockCard(block)).join('') : `<div class="vf-empty">${escapeHtml(studioMessages.surfaceBlocksEmpty)}</div>`}
                            </div>
                        </div>
                    </div>

                    <div class="vf-card" style="box-shadow:none;">
                        <div class="vf-card__head">
                            <div>
                                <h3 class="vf-card__title">${escapeHtml(studioMessages.surfaceTemplatesTitle)}</h3>
                                <div class="vf-card__sub">${escapeHtml(studioMessages.surfaceTemplatesSub)}</div>
                            </div>
                        </div>
                        <div class="vf-card__body">
                            <div class="vf-template-grid">
                                ${Object.entries(pageTemplates).map(([key, template]) => `
                                    <button type="button" class="vf-template-card" data-vf-template="${escapeHtml(key)}">
                                        <div class="vf-template-card__title">${escapeHtml(template.label || 'Predložak')}</div>
                                        <div class="vf-template-card__text">${escapeHtml(template.description || '')}</div>
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="vf-stack">
                    <div class="vf-card" style="box-shadow:none;">
                        <div class="vf-card__head">
                            <div>
                                <h3 class="vf-card__title">${escapeHtml(studioMessages.surfaceEditorTitle)}</h3>
                                <div class="vf-card__sub">${escapeHtml(studioMessages.surfaceEditorSub)}</div>
                            </div>
                        </div>
                        <div class="vf-card__body">
                            ${activeBlock ? renderBlockEditor(activeBlock) : `<div class="vf-empty">${escapeHtml(studioMessages.surfaceEditorEmpty)}</div>`}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderBlockCard(block) {
        const quickWidthOptions = [
            ['full', '1/1'],
            ['half', '1/2'],
            ['third', '1/3'],
            ['quarter', '1/4']
        ];

        return `
            <div class="vf-block-card ${block.id === state.activeBlockId ? 'is-active' : ''}" data-vf-block-card="${escapeHtml(block.id)}" data-vf-block-id="${escapeHtml(block.id)}" data-vf-block-span="${escapeHtml(block.layout_width || 'full')}" draggable="true">
                <div class="vf-block-card__top">
                    <div class="vf-block-card__main">
                        <div class="vf-chip-row">
                            <span class="vf-chip">${escapeHtml(pageBlockTypes[block.type] || block.type)}</span>
                            <span class="vf-chip vf-chip--soft">${escapeHtml(getBlockWidthLabel(block.layout_width || 'full'))}</span>
                            ${isSystemSubmitBlock(block) ? `<span class="vf-chip vf-chip--accent">${escapeHtml(studioMessages.blockAutoSubmit)}</span>` : ''}
                            ${block.required ? '<span class="vf-chip">Obavezno</span>' : ''}
                        </div>
                    </div>
                    <div class="vf-block-card__meta">
                        ${quickWidthOptions.map(([key, label]) => `
                            <button type="button" class="vf-block-card__width-btn ${String(block.layout_width || 'full') === key ? 'is-active' : ''}" data-vf-block-width="${escapeHtml(block.id)}|${escapeHtml(key)}">${escapeHtml(label)}</button>
                        `).join('')}
                        <span class="vf-block-card__grip" title="Povuci blok">⋮⋮</span>
                    </div>
                </div>
                <div class="vf-block-card__title">${escapeHtml(block.title || block.label || 'Blok')}</div>
                <div class="vf-block-card__text">${escapeHtml(block.text || block.placeholder || studioMessages.blockNoDesc)}</div>
                <div class="vf-block-card__hint">${escapeHtml(studioMessages.blockWidthHint)}</div>
                <div class="vf-card-actions">
                    <button type="button" data-vf-block-duplicate="${escapeHtml(block.id)}">Dupliciraj</button>
                    <button type="button" data-vf-block-up="${escapeHtml(block.id)}">Gore</button>
                    <button type="button" data-vf-block-down="${escapeHtml(block.id)}">Dolje</button>
                    <button type="button" data-vf-block-delete="${escapeHtml(block.id)}">Obriši</button>
                </div>
            </div>
        `;
    }

    function renderImageGalleryPicker(block) {
        const entries = getImageGalleryEntries();

        return `
            <div class="vf-media-gallery">
                <div class="vf-media-gallery__head">
                    <div>
                        <h4 class="vf-media-gallery__title">${escapeHtml(studioMessages.galleryTitle)}</h4>
                        <div class="vf-field__hint">${escapeHtml(studioMessages.galleryHint)}</div>
                    </div>
                </div>
                ${entries.length ? `
                    <div class="vf-media-gallery__grid">
                        ${entries.map(entry => {
                            const fileName = (entry.image || '').trim() || entry.image_url.split('/').pop() || 'Slika';
                            const isActive = (block.media_url || '') === entry.image_url;

                            return `
                                <button type="button" class="vf-media-gallery__item ${isActive ? 'is-active' : ''}" data-vf-gallery-select="${escapeHtml(entry.image_url)}">
                                    <div class="vf-media-gallery__thumb">
                                        <img src="${escapeHtml(entry.image_url)}" alt="${escapeHtml(fileName)}" loading="lazy">
                                    </div>
                                    <div class="vf-media-gallery__meta">
                                        <div class="vf-media-gallery__label">${escapeHtml(fileName)}</div>
                                        <div class="vf-media-gallery__state">${isActive ? 'Trenutno odabrana' : 'Klikni za odabir'}</div>
                                    </div>
                                </button>
                            `;
                        }).join('')}
                    </div>
                ` : `
                    <div class="vf-media-gallery__empty">
                        ${escapeHtml(studioMessages.galleryEmpty)}
                    </div>
                `}
            </div>
        `;
    }

    function getProductCatalogEntry(block = {}) {
        const translationKey = String(block.product_translation_key || '').trim();
        const blogPostId = Number(block.product_blog_post_id || 0);

        if(translationKey) {
            return (productCatalog || []).find(item => String(item.translation_key || '') === translationKey) || null;
        }

        if(blogPostId > 0) {
            return (productCatalog || []).find(item => Number(item.blog_post_id || 0) === blogPostId) || null;
        }

        return null;
    }

    function getProductTargetModeLabel(mode = '') {
        return productTargetModeOptions[mode] || 'Vodi na blog vodič';
    }

    function getProductLanguageModeLabel(mode = '') {
        return productLanguageModeOptions[mode] || 'Prati jezik stranice';
    }

    function getProductSourceModeLabel(mode = '') {
        return productSourceModeOptions[mode] || 'Koristi zadani proizvod';
    }

    function normalizeProductMappings(mappings = []) {
        return (Array.isArray(mappings) ? mappings : []).map(mapping => ({
            id: mapping?.id || uid('product_map'),
            match_value: String(mapping?.match_value || ''),
            product_translation_key: String(mapping?.product_translation_key || ''),
            product_blog_post_id: Number(mapping?.product_blog_post_id || 0)
        })).filter(mapping => mapping.id || mapping.match_value || mapping.product_translation_key || mapping.product_blog_post_id);
    }

    function collectTagValuesFromBlocks(blocks = [], tagMap = new Map()) {
        (Array.isArray(blocks) ? blocks : []).forEach(rawBlock => {
            const block = normalizeBlock(rawBlock || {});
            const title = String(block.title || block.label || pageBlockTypes[block.type] || 'Blok').trim();
            let items = [];

            if(['survey', 'radio_survey'].includes(block.type)) {
                items = Array.isArray(block.options) ? block.options : [];
            } else if(block.type === 'cta_group' && !isSystemSubmitBlock(block)) {
                items = (Array.isArray(block.buttons) ? block.buttons : []).filter(item => !['submit_next', 'submit_stay'].includes(String(item?.action || '')));
            }

            items.forEach(item => {
                const value = String(item?.value || '').trim();
                if(!value || tagMap.has(value)) {
                    return;
                }

                tagMap.set(value, {
                    value,
                    sourceLabel: title
                });
            });
        });

        return tagMap;
    }

    function getAvailableFunnelTagOptions() {
        const tagMap = new Map();

        if(state.payload?.landing_page) {
            collectTagValuesFromBlocks(state.payload.landing_page.blocks || [], tagMap);
            collectTagValuesFromBlocks(state.payload.landing_page.variant_b_blocks || [], tagMap);
        }

        (state.payload?.board || []).forEach(phase => {
            (phase.steps || []).forEach(step => {
                if(step?.page) {
                    collectTagValuesFromBlocks(step.page.blocks || [], tagMap);
                    collectTagValuesFromBlocks(step.page.variant_b_blocks || [], tagMap);
                }
            });
        });

        return Array.from(tagMap.values()).sort((a, b) => a.value.localeCompare(b.value, 'hr'));
    }

    function getProductMappingCatalogEntry(mapping = {}) {
        const translationKey = String(mapping.product_translation_key || '').trim();
        const blogPostId = Number(mapping.product_blog_post_id || 0);

        if(translationKey) {
            return (productCatalog || []).find(item => String(item.translation_key || '') === translationKey) || null;
        }

        if(blogPostId > 0) {
            return (productCatalog || []).find(item => Number(item.blog_post_id || 0) === blogPostId) || null;
        }

        return null;
    }

    function getPreviewProductData(block = {}) {
        const sourceMode = block.product_source_mode === 'dynamic' ? 'dynamic' : 'manual';
        const mappings = normalizeProductMappings(block.product_mappings || []);
        let product = getProductCatalogEntry(block);
        let previewMapping = null;

        if(sourceMode === 'dynamic' && mappings.length) {
            previewMapping = mappings.find(mapping => getProductMappingCatalogEntry(mapping)) || mappings[0];
            const mappedProduct = previewMapping ? getProductMappingCatalogEntry(previewMapping) : null;

            if(mappedProduct) {
                product = mappedProduct;
            }
        }

        const languageMode = block.product_language_mode === 'manual' ? 'manual' : 'page';
        const resolvedLanguageCode = languageMode === 'manual'
            ? (block.product_language_code || preferredProductLanguageCode || 'hr')
            : (preferredProductLanguageCode || 'hr');

        return {
            product,
            sourceMode,
            mappings,
            previewMapping,
            languageMode,
            resolvedLanguageCode,
            fallbackLanguageCode: block.product_fallback_language_code || 'hr',
            primaryMode: block.product_primary_mode || 'blog_guide',
            secondaryMode: block.product_secondary_mode || 'direct_shop',
            primaryCtaText: block.product_primary_cta_text || 'Pogledaj vodič proizvoda',
            secondaryEnabled: block.product_secondary_enabled !== false,
            secondaryCtaText: block.product_secondary_cta_text || 'Idi na službeni shop'
        };
    }

    function renderBlockEditor(block) {
        const isMedia = ['image', 'video'].includes(block.type);
        const isField = ['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field'].includes(block.type);
        const isSurvey = block.type === 'survey';
        const isRadioSurvey = block.type === 'radio_survey';
        const radioRoutingEnabled = !isRadioSurvey || block.route_on_submit !== false;
        const isOptionBlock = isOptionBlockType(block.type);
        const isButtons = block.type === 'cta_group';
        const isCountdown = block.type === 'countdown';
        const isSpacer = block.type === 'spacer';
        const isProductOffer = block.type === 'product_offer';
        const isSystemSubmit = isSystemSubmitBlock(block);
        const actionItems = getBlockActionItems(block);
        const selectedProduct = getProductCatalogEntry(block);
        const blockNameLabel = isRadioSurvey ? 'Interni naziv pitanja' : 'Naziv bloka';
        const badgeLabel = isRadioSurvey ? 'Oznaka pitanja' : 'Badge / oznaka';
        const titleLabel = isRadioSurvey ? 'Pitanje' : 'Naslov bloka';
        const textLabel = isRadioSurvey ? 'Pojašnjenje pitanja' : 'Tekst / opis';
        const actionsSectionLabel = isButtons
            ? 'Gumbi i grananje'
            : (isRadioSurvey
                ? (radioRoutingEnabled ? 'Odgovori ovog pitanja i routing nakon submita' : 'Odgovori ovog pitanja')
                : 'Opcije i grananje');

        return `
            <div class="vf-stack">
                <div class="vf-two">
                    <div class="vf-field">
                        <label>${blockNameLabel}</label>
                        <input type="text" data-vf-block-field="label" value="${escapeHtml(block.label || '')}" />
                    </div>
                    <div class="vf-field">
                        <label>Poravnanje</label>
                        <select data-vf-block-field="alignment">
                            ${Object.entries(pageAlignmentOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${block.alignment === key ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                        </select>
                    </div>
                </div>

                ${!isSpacer ? `
                    <div class="vf-two">
                        <div class="vf-field">
                            <label>${badgeLabel}</label>
                            <input type="text" data-vf-block-field="badge" value="${escapeHtml(block.badge || '')}" />
                        </div>
                        <div class="vf-field">
                            <label>${titleLabel}</label>
                            <input type="text" data-vf-block-field="title" value="${escapeHtml(block.title || '')}" />
                        </div>
                    </div>

                    <div class="vf-field">
                        <label>${textLabel}</label>
                        <textarea data-vf-block-field="text">${escapeHtml(block.text || '')}</textarea>
                    </div>
                ` : ''}

                ${isMedia ? `
                    <div class="vf-field">
                        <label>${block.type === 'image' ? 'URL slike' : 'YouTube, Vimeo ili URL videa'}</label>
                        <input type="text" data-vf-block-field="media_url" value="${escapeHtml(block.media_url || '')}" />
                        ${block.type === 'video' ? `<div class="vf-field__hint">Zalijepi YouTube, Vimeo ili direktni video link i odmah će se prikazati embed u pregledu.</div>` : ''}
                    </div>
                    ${block.type === 'image' ? `
                        <div class="vf-field">
                            <label>Upload slike</label>
                            <input type="file" accept="${escapeHtml(imageUploadAccept)}" data-vf-image-upload="${escapeHtml(block.id)}" />
                            <div class="vf-field__hint">Možeš zalijepiti URL ili direktno uploadati sliku. Maksimalna veličina: ${escapeHtml(imageUploadMaxSizeMb)} MB.</div>
                        </div>
                        ${renderImageGalleryPicker(block)}
                    ` : ''}
                ` : ''}

                ${isProductOffer ? `
                    <div class="vf-section-label">Odabir proizvoda i odredišta</div>
                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Način odabira proizvoda</label>
                            <select data-vf-block-field="product_source_mode">
                                ${Object.entries(productSourceModeOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.product_source_mode || 'manual') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="vf-field">
                            <label>Način jezika</label>
                            <select data-vf-block-field="product_language_mode">
                                ${Object.entries(productLanguageModeOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.product_language_mode || 'page') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                            </select>
                            <div class="vf-field__hint">U modu praćenja stranice blok pokušava otvoriti odgovarajući prijevod proizvoda prema jeziku trenutne funnel stranice.</div>
                        </div>
                    </div>

                    <div class="vf-field">
                        <label>${String(block.product_source_mode || 'manual') === 'dynamic' ? 'Zadani / fallback proizvod' : 'Proizvod iz blog kataloga'}</label>
                        <select data-vf-block-field="product_translation_key" data-vf-product-selector>
                            <option value="">${String(block.product_source_mode || 'manual') === 'dynamic' ? 'Odaberi fallback proizvod' : 'Odaberi proizvod'}</option>
                            ${(productCatalog || []).map(item => `
                                <option
                                    value="${escapeHtml(item.translation_key || '')}"
                                    data-blog-post-id="${escapeHtml(item.blog_post_id || 0)}"
                                    ${String(block.product_translation_key || '') === String(item.translation_key || '') ? 'selected' : ''}
                                >${escapeHtml(item.title || 'Proizvod')}${item.available_languages_label ? ` [${escapeHtml(item.available_languages_label)}]` : ''}</option>
                            `).join('')}
                        </select>
                        <div class="vf-field__hint">${String(block.product_source_mode || 'manual') === 'dynamic' ? 'Ako nijedno mapiranje ne pogodi odgovor osobe, blok će koristiti ovaj zadani proizvod.' : 'Odaberi proizvod koji je već postavljen na blogu. Funnel će tada koristiti isti referral i webshop mehanizam koji već radi na blog člancima.'}</div>
                    </div>

                    ${selectedProduct ? `
                        <div class="vf-note">
                            <strong>${String(block.product_source_mode || 'manual') === 'dynamic' ? 'Fallback proizvod' : 'Odabrani proizvod'}:</strong> ${escapeHtml(selectedProduct.title || 'Proizvod')}<br>
                            <span class="vf-field__hint">Dostupni jezici: ${escapeHtml(selectedProduct.available_languages_label || 'HR / EN')}</span>
                        </div>
                    ` : `
                        <div class="vf-note">${String(block.product_source_mode || 'manual') === 'dynamic' ? 'Odaberi fallback proizvod ili složi mapiranja ispod. Tako blok može pokazati različit proizvod ovisno o odgovoru osobe.' : 'Kad odabereš proizvod, ovdje ćeš odmah vidjeti koji su jezici dostupni i kako će blok voditi na blog vodič ili direktno na službeni shop.'}</div>
                    `}

                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Fallback jezik</label>
                            <select data-vf-block-field="product_fallback_language_code">
                                <option value="">Bez fallbacka</option>
                                ${Object.entries(productLanguageOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.product_fallback_language_code || 'hr') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                            </select>
                        </div>
                        ${String(block.product_language_mode || 'page') === 'manual' ? `
                            <div class="vf-field">
                                <label>Ručni jezik proizvoda</label>
                                <select data-vf-block-field="product_language_code">
                                    ${Object.entries(productLanguageOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.product_language_code || preferredProductLanguageCode || 'hr') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                                </select>
                            </div>
                        ` : `<div></div>`}
                    </div>

                    ${String(block.product_source_mode || 'manual') === 'dynamic' ? `
                        <div class="vf-section-label">Mapiranje proizvoda prema odgovoru</div>
                        <div class="vf-note">Ovdje kažeš sustavu: ako osoba negdje prije odabere određeni tag ili odgovor, ovdje prikaži točno određeni proizvod. Primjer: <strong>regulacija_tjelesne_tezine → proizvod A</strong>.</div>
                        <div class="vf-stack">
                            ${(() => {
                                const availableTags = getAvailableFunnelTagOptions();
                                return normalizeProductMappings(block.product_mappings || []).length ? normalizeProductMappings(block.product_mappings || []).map(mapping => {
                                const mappedProduct = getProductMappingCatalogEntry(mapping);
                                const hasMappedTagOption = availableTags.some(option => option.value === String(mapping.match_value || '').trim());
                                const mappingTagOptions = hasMappedTagOption || !String(mapping.match_value || '').trim()
                                    ? availableTags
                                    : [{value: String(mapping.match_value || '').trim(), sourceLabel: 'Ručno spremljeni tag'}, ...availableTags];
                                return `
                                    <div class="vf-action-card is-open">
                                        <div class="vf-action-card__body" style="padding-top:.9rem;">
                                            <div class="vf-two vf-product-mapping-grid">
                                                <div class="vf-field">
                                                    <label>Tag odgovora iz funnela</label>
                                                    <select data-vf-product-mapping-field="${escapeHtml(mapping.id)}|match_value">
                                                        <option value="">${availableTags.length ? 'Odaberi tag iz ovog funnela' : 'Još nema dostupnih tagova'}</option>
                                                        ${mappingTagOptions.map(option => `
                                                            <option value="${escapeHtml(option.value)}" ${String(mapping.match_value || '') === String(option.value || '') ? 'selected' : ''}>
                                                                ${escapeHtml(option.value)}${option.sourceLabel ? ` — ${escapeHtml(option.sourceLabel)}` : ''}
                                                            </option>
                                                        `).join('')}
                                                    </select>
                                                </div>
                                                <div class="vf-field">
                                                    <label>Proizvod koji se prikazuje</label>
                                                    <select data-vf-product-mapping-field="${escapeHtml(mapping.id)}|product_translation_key">
                                                        <option value="">Odaberi proizvod</option>
                                                        ${(productCatalog || []).map(item => `<option value="${escapeHtml(item.translation_key || '')}" data-blog-post-id="${escapeHtml(item.blog_post_id || 0)}" ${String(mapping.product_translation_key || '') === String(item.translation_key || '') ? 'selected' : ''}>${escapeHtml(item.title || 'Proizvod')}${item.available_languages_label ? ` [${escapeHtml(item.available_languages_label)}]` : ''}</option>`).join('')}
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="vf-product-mapping-footer">
                                                <div class="vf-product-mapping-meta">${mappedProduct ? `Mapirano na: <strong>${escapeHtml(mappedProduct.title || 'Proizvod')}</strong>` : '&nbsp;'}</div>
                                                <div class="vf-card-actions">
                                                <button type="button" data-vf-product-mapping-remove="${escapeHtml(mapping.id)}">Ukloni mapiranje</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('') : '<div class="vf-empty">Još nema mapiranja. Dodaj prvo pravilo i poveži jedan odgovor s jednim proizvodom.</div>';
                            })()}
                            <button type="button" class="vf-btn" data-vf-product-mapping-add>+ Dodaj mapiranje proizvoda</button>
                        </div>
                    ` : ''}

                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Glavni CTA vodi na</label>
                            <select data-vf-block-field="product_primary_mode">
                                ${Object.entries(productTargetModeOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.product_primary_mode || 'blog_guide') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="vf-field">
                            <label>Tekst glavnog CTA-a</label>
                            <input type="text" data-vf-block-field="product_primary_cta_text" value="${escapeHtml(block.product_primary_cta_text || 'Pogledaj vodič proizvoda')}" />
                        </div>
                    </div>

                    <label class="vf-toggle"><input type="checkbox" data-vf-block-toggle="product_secondary_enabled" ${block.product_secondary_enabled !== false ? 'checked' : ''}> Prikaži i sekundarni CTA</label>

                    ${block.product_secondary_enabled !== false ? `
                        <div class="vf-two">
                            <div class="vf-field">
                                <label>Sekundarni CTA vodi na</label>
                                <select data-vf-block-field="product_secondary_mode">
                                    ${Object.entries(productTargetModeOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.product_secondary_mode || 'direct_shop') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                                </select>
                            </div>
                            <div class="vf-field">
                                <label>Tekst sekundarnog CTA-a</label>
                                <input type="text" data-vf-block-field="product_secondary_cta_text" value="${escapeHtml(block.product_secondary_cta_text || 'Idi na službeni shop')}" />
                            </div>
                        </div>
                    ` : ''}

                    <div class="vf-note"><strong>Blog vodič</strong> otvara blog članak s referral parametrom suradnika. <strong>Direktno na službeni shop</strong> ne koristi sirovi Forever link nego postojeći blog redirect engine, pa referral, tržište i preporuka ostaju aktivni.</div>
                ` : ''}

                ${isField ? `
                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Placeholder</label>
                            <input type="text" data-vf-block-field="placeholder" value="${escapeHtml(block.placeholder || '')}" />
                        </div>
                        <div class="vf-field">
                            <label>Obavezno polje</label>
                            <select data-vf-block-boolean="required">
                                <option value="0" ${!block.required ? 'selected' : ''}>Ne</option>
                                <option value="1" ${block.required ? 'selected' : ''}>Da</option>
                            </select>
                        </div>
                    </div>
                ` : ''}

                ${isRadioSurvey ? `
                    <div class="vf-note">Jedan <strong>Radio survey / upitnik</strong> blok predstavlja jedno pitanje. Ako želiš sljedeće pitanje, dodaj novi blok istog tipa i složi njegov završni routing odvojeno.</div>
                    <div class="vf-section-label">Postavke ovog pitanja</div>
                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Odgovor na ovo pitanje je obavezan</label>
                            <select data-vf-block-boolean="required">
                                <option value="0" ${!block.required ? 'selected' : ''}>Ne</option>
                                <option value="1" ${block.required ? 'selected' : ''}>Da</option>
                            </select>
                        </div>
                        <div class="vf-field">
                            <label>Završni submit koristi odgovor ovog pitanja</label>
                            <select data-vf-block-boolean="route_on_submit">
                                <option value="1" ${block.route_on_submit !== false ? 'selected' : ''}>Da, odabrani odgovor određuje sljedeći korak</option>
                                <option value="0" ${block.route_on_submit === false ? 'selected' : ''}>Ne, samo spremi odgovor na ovo pitanje</option>
                            </select>
                        </div>
                    </div>
                ` : ''}

                ${isButtons || isOptionBlock ? `
                    <div class="vf-section-label">${actionsSectionLabel}</div>
                    <div class="vf-actions-list">
                        ${actionItems.map(item => renderActionCard(item, block)).join('')}
                    </div>
                    ${isSystemSubmit ? '' : `<button type="button" class="vf-btn" data-vf-add-action="${isOptionBlock ? 'survey' : 'cta'}">+ Dodaj ${isOptionBlock ? (isRadioSurvey ? 'odgovor' : 'opciju') : 'gumb'}</button>`}
                ` : ''}

                ${isSystemSubmit ? `
                    <div class="vf-note">Ovaj sistemski submit blok služi samo za slanje forme. Ako neka survey opcija ili drugi gumb preuzme slanje podataka, ovaj blok se automatski uklanja da ne radi duplu radnju.</div>
                ` : ''}

                ${(isButtons || isSurvey) && !isSystemSubmit ? `
                    <label class="vf-toggle"><input type="checkbox" data-vf-block-toggle="require_capture" ${block.require_capture ? 'checked' : ''}> Ovaj blok traži capture prije nastavka</label>
                ` : ''}

                ${isSurvey ? `
                    <label class="vf-toggle"><input type="checkbox" data-vf-block-toggle="auto_advance" ${block.auto_advance !== false ? 'checked' : ''}> Survey nakon odabira odmah vodi dalje</label>
                ` : ''}

                ${isRadioSurvey ? `
                    <div class="vf-note">${radioRoutingEnabled ? 'Ovo pitanje ne vodi dalje odmah po kliku. Osoba prvo odabere odgovor, a završni submit CTA koristi taj odabir za routing samo ako CTA nema svoj ručno zadani korak ili URL.' : 'Na ovom pitanju je routing isključen. Odgovor se samo sprema, a završni submit ga trenutno neće koristiti za usmjeravanje.'}</div>
                ` : ''}

                ${isCountdown ? `
                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Tip countdowna</label>
                            <select data-vf-block-field="countdown_mode">
                                <option value="fixed" ${block.countdown_mode !== 'evergreen' ? 'selected' : ''}>Fiksni datum</option>
                                <option value="evergreen" ${block.countdown_mode === 'evergreen' ? 'selected' : ''}>Evergreen po posjetitelju</option>
                            </select>
                        </div>
                        <div class="vf-field">
                            <label>Tekst nakon isteka</label>
                            <input type="text" data-vf-block-field="completion_text" value="${escapeHtml(block.completion_text || '')}" />
                        </div>
                    </div>
                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Dizajn countera</label>
                            <select data-vf-block-field="countdown_style">
                                <option value="cards" ${block.countdown_style !== 'glass' && block.countdown_style !== 'minimal' && block.countdown_style !== 'spotlight' ? 'selected' : ''}>Kartice</option>
                                <option value="glass" ${block.countdown_style === 'glass' ? 'selected' : ''}>Glass panel</option>
                                <option value="minimal" ${block.countdown_style === 'minimal' ? 'selected' : ''}>Minimalna linija</option>
                                <option value="spotlight" ${block.countdown_style === 'spotlight' ? 'selected' : ''}>Spotlight glow</option>
                            </select>
                        </div>
                        <div class="vf-field">
                            <label>Veličina brojeva (px)</label>
                            <input type="number" min="16" max="96" data-vf-block-field="countdown_number_size" value="${escapeHtml(block.countdown_number_size || 34)}" />
                        </div>
                    </div>
                    <div class="vf-field">
                        <label>Boja brojeva countdowna</label>
                        <input type="color" data-vf-block-field="countdown_number_color" value="${escapeHtml(block.countdown_number_color || block.accent_color || '#67d8c9')}" />
                    </div>
                    <div class="vf-section-label">Prikaz jedinica</div>
                    <div class="vf-two">
                        <label class="vf-toggle"><input type="checkbox" data-vf-block-toggle="countdown_show_days" ${block.countdown_show_days !== false ? 'checked' : ''}> Prikaži dane</label>
                        <label class="vf-toggle"><input type="checkbox" data-vf-block-toggle="countdown_show_hours" ${block.countdown_show_hours !== false ? 'checked' : ''}> Prikaži sate</label>
                    </div>
                    <div class="vf-two">
                        <label class="vf-toggle"><input type="checkbox" data-vf-block-toggle="countdown_show_minutes" ${block.countdown_show_minutes !== false ? 'checked' : ''}> Prikaži minute</label>
                        <label class="vf-toggle"><input type="checkbox" data-vf-block-toggle="countdown_show_seconds" ${block.countdown_show_seconds !== false ? 'checked' : ''}> Prikaži sekunde</label>
                    </div>
                    <div class="vf-field__hint">Za kratki evergreen od 30 minuta najčešće ima smisla prikazati samo Min + Sek.</div>
                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Fiksni datum i vrijeme</label>
                            <input type="datetime-local" data-vf-block-field="fixed_datetime" value="${escapeHtml(block.fixed_datetime || '')}" />
                        </div>
                        <div class="vf-two">
                            <div class="vf-field">
                                <label>Minute</label>
                                <input type="number" min="0" data-vf-block-field="duration_minutes" value="${escapeHtml(block.duration_minutes || 0)}" />
                            </div>
                            <div class="vf-field">
                                <label>Dani</label>
                                <input type="number" min="0" data-vf-block-field="duration_days" value="${escapeHtml(block.duration_days || 0)}" />
                            </div>
                        </div>
                    </div>
                ` : ''}

                ${isSpacer ? `
                    <div class="vf-field">
                        <label>Veličina razmaka</label>
                        <select data-vf-block-field="spacing">
                            ${['xs', 'sm', 'md', 'lg', 'xl'].map(size => `<option value="${size}" ${block.spacing === size ? 'selected' : ''}>${size.toUpperCase()}</option>`).join('')}
                        </select>
                    </div>
                ` : ''}

                ${!isSpacer ? `
                    <div class="vf-section-label">Uređenje teksta</div>
                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Font</label>
                            <select data-vf-block-field="font_family">
                                ${Object.entries(pageFontFamilyOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${block.font_family === key ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="vf-field">
                            <label>Veličina naslova (px)</label>
                            <input type="number" min="16" max="96" data-vf-block-field="title_size" value="${escapeHtml(block.title_size || 28)}" />
                        </div>
                    </div>

                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Badge veličina (px)</label>
                            <input type="number" min="10" max="32" data-vf-block-field="badge_size" value="${escapeHtml(block.badge_size || 13)}" />
                        </div>
                        <div class="vf-field">
                            <label>Badge težina</label>
                            <select data-vf-block-field="badge_weight">
                                ${Object.entries(pageFontWeightOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.badge_weight || '800') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                            </select>
                        </div>
                    </div>

                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Boja badgea</label>
                            <input type="color" data-vf-block-field="badge_color" value="${escapeHtml(block.badge_color || block.text_color || '#eef4ff')}" />
                        </div>
                        <div class="vf-field">
                            <label>Težina naslova</label>
                            <select data-vf-block-field="title_weight">
                                ${Object.entries(pageFontWeightOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.title_weight || '800') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                            </select>
                        </div>
                    </div>

                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Boja naslova</label>
                            <input type="color" data-vf-block-field="title_color" value="${escapeHtml(block.title_color || block.text_color || '#eef4ff')}" />
                        </div>
                        <div class="vf-field">
                            <label>Veličina teksta (px)</label>
                            <input type="number" min="12" max="48" data-vf-block-field="text_size" value="${escapeHtml(block.text_size || 17)}" />
                        </div>
                    </div>

                    <div class="vf-two">
                        <div class="vf-field">
                            <label>Težina teksta</label>
                            <select data-vf-block-field="text_weight">
                                ${Object.entries(pageFontWeightOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.text_weight || '500') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="vf-field">
                            <label>Boja opisa</label>
                            <input type="color" data-vf-block-field="body_color" value="${escapeHtml(block.body_color || block.text_color || '#eef4ff')}" />
                        </div>
                    </div>

                    ${isField ? `
                        <div class="vf-two">
                            <div class="vf-field">
                                <label>Veličina polja (px)</label>
                                <input type="number" min="12" max="36" data-vf-block-field="field_size" value="${escapeHtml(block.field_size || 16)}" />
                            </div>
                            <div class="vf-field">
                                <label>Težina polja</label>
                                <select data-vf-block-field="field_weight">
                                    ${Object.entries(pageFontWeightOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.field_weight || '500') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                                </select>
                            </div>
                        </div>

                        <div class="vf-two">
                            <div class="vf-field">
                                <label>Boja unosa u polju</label>
                                <input type="color" data-vf-block-field="field_text_color" value="${escapeHtml(block.field_text_color || block.text_color || '#eef4ff')}" />
                            </div>
                            <div class="vf-field">
                                <label>Boja placeholdera</label>
                                <input type="color" data-vf-block-field="placeholder_color" value="${escapeHtml(block.placeholder_color || '#aab4c7')}" />
                            </div>
                        </div>
                    ` : ''}

                    ${(isButtons || isSurvey || isProductOffer) ? `
                        <div class="vf-two">
                            <div class="vf-field">
                                <label>Veličina teksta gumba (px)</label>
                                <input type="number" min="12" max="36" data-vf-block-field="button_size" value="${escapeHtml(block.button_size || 17)}" />
                            </div>
                            <div class="vf-field">
                                <label>Težina teksta gumba</label>
                                <select data-vf-block-field="button_weight">
                                    ${Object.entries(pageFontWeightOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(block.button_weight || '800') === String(key) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                                </select>
                            </div>
                        </div>

                        <div class="vf-field">
                            <label>Boja teksta gumba</label>
                            <input type="color" data-vf-block-field="button_text_color" value="${escapeHtml(block.button_text_color || '#0f172a')}" />
                        </div>
                    ` : ''}
                ` : ''}

                <div class="vf-section-label">Vizual bloka</div>
                <div class="vf-two">
                    <div class="vf-field">
                        <label>Pozadina bloka</label>
                        <input type="color" data-vf-block-field="background_color" value="${escapeHtml(block.background_color || '#152132')}" />
                    </div>
                    <div class="vf-field">
                        <label>Tekst bloka</label>
                        <input type="color" data-vf-block-field="text_color" value="${escapeHtml(block.text_color || '#eef4ff')}" />
                    </div>
                </div>
                <div class="vf-field">
                    <label>Naglasak bloka</label>
                    <input type="color" data-vf-block-field="accent_color" value="${escapeHtml(block.accent_color || '#67d8c9')}" />
                </div>
            </div>
        `;
    }

    function renderActionCard(action, block) {
        const styleLabels = {
            primary: 'Primarni stil',
            secondary: 'Sekundarni stil',
            ghost: 'Prozirni stil'
        };
        const blockType = block.type;
        const isSurvey = blockType === 'survey';
        const isRadioSurvey = blockType === 'radio_survey';
        const radioRoutingEnabled = !isRadioSurvey || block.route_on_submit !== false;
        const isSystemSubmit = isSystemSubmitBlock(block);
        const isExternal = action.action === 'external_url';
        const isSubmitAction = ['submit_next', 'submit_stay'].includes(action.action) || action.require_submit;
        const targetStep = radioRoutingEnabled ? flatSteps().find(step => step.id === action.target_step_id) : null;
        const targetStepLabel = !radioRoutingEnabled ? 'Bez routinga' : (targetStep ? (targetStep.title || targetStep.id) : (isExternal ? 'Vanjski URL' : 'Automatski izbor'));
        const actionLabel = !radioRoutingEnabled ? 'Sprema odgovor' : (pageActionOptions[action.action] || 'Idi dalje');
        const title = action.label || (isOptionBlockType(blockType) ? 'Novi odgovor' : 'Novi gumb');
        const subtitle = isSystemSubmit
            ? 'Sistemski gumb koji služi samo za slanje podataka iz forme.'
            : isRadioSurvey
            ? (radioRoutingEnabled ? 'Odgovor se sprema unutar ovog pitanja, a završni submit ga može koristiti za routing.' : 'Odgovor se trenutno samo sprema kao dio ovog pitanja.')
            : isSurvey
            ? 'Odgovor osobe i pravilo kamo se funnel nastavlja.'
            : 'Gumb koji vodi na sljedeći korak ili šalje podatke.';
        const isExpanded = state.activeActionId === action.id;
        const validationErrors = getActionValidationErrorsForCurrentView(action, block);
        const externalUrlError = validationErrors.find(error => error.field === 'external_url');
        const hasCaptureFields = currentSurfaceHasCaptureFields();
        const availableActionOptions = getAvailablePageActionOptions(hasCaptureFields, {isSystemSubmitBlock: isSystemSubmit, isRadioSurveyBlock: isRadioSurvey});

        return `
            <div class="vf-action-card ${isExpanded ? 'is-open' : ''}">
                <button type="button" class="vf-action-card__toggle" data-vf-action-card="${escapeHtml(action.id)}" aria-expanded="${isExpanded ? 'true' : 'false'}">
                    <div class="vf-action-card__header">
                        <div>
                            <div class="vf-action-card__eyebrow">${isRadioSurvey ? 'Odgovor upitnika' : (isSurvey ? 'Survey opcija' : 'CTA gumb')}</div>
                            <div class="vf-action-card__title">${escapeHtml(title)}</div>
                            <div class="vf-action-card__sub">${escapeHtml(subtitle)}</div>
                        </div>
                    </div>
                    <div class="vf-chip-row vf-action-card__meta">
                        ${isRadioSurvey ? '' : `<span class="vf-chip vf-chip--soft">${escapeHtml(styleLabels[action.style] || action.style || 'Stil')}</span>`}
                        <span class="vf-chip vf-chip--accent">${escapeHtml(actionLabel)}</span>
                        <span class="vf-chip vf-chip--soft">${escapeHtml(targetStepLabel)}</span>
                    </div>
                    <span class="vf-action-card__arrow">⌄</span>
                </button>

                ${isExpanded ? `
                    <div class="vf-action-card__body">
                        <div class="vf-action-card__section">
                            <div class="vf-action-card__section-head">
                                <div>
                                    <div class="vf-action-card__section-kicker">Glavne postavke</div>
                                    <div class="vf-action-card__section-text">${isRadioSurvey ? (radioRoutingEnabled ? 'Uredi odgovor i odredi kamo završni submit vodi ako osoba odabere baš ovu opciju. Odgovori su namjerno vizualno ujednačeni jer ovo nije CTA nego pitanje.' : 'Uredi odgovor ovog pitanja. Routing je trenutno ugašen pa se ovaj odgovor samo sprema. Odgovori su namjerno vizualno ujednačeni jer ovo nije CTA nego pitanje.') : 'Uredi tekst, stil i odredi što se događa nakon klika na ovu opciju.'}</div>
                                </div>
                            </div>

                            <div class="vf-action-card__main-grid">
                                <div class="vf-field is-full">
                                    <label>${isOptionBlockType(blockType) ? 'Tekst odgovora' : 'Tekst gumba'}</label>
                                    <input type="text" data-vf-action-field="${escapeHtml(action.id)}|label" value="${escapeHtml(action.label || '')}" />
                                </div>
                                ${isRadioSurvey ? `
                                    <div class="vf-field is-full">
                                        <label>Kratko pojašnjenje odgovora</label>
                                        <input type="text" data-vf-action-field="${escapeHtml(action.id)}|hint" value="${escapeHtml(action.hint || '')}" />
                                    </div>
                                ` : ''}
                                ${isRadioSurvey ? '' : `
                                    <div class="vf-field vf-field--compact">
                                        <label>Stil</label>
                                        <select data-vf-action-field="${escapeHtml(action.id)}|style">
                                            ${['primary', 'secondary', 'ghost'].map(style => `<option value="${style}" ${action.style === style ? 'selected' : ''}>${style}</option>`).join('')}
                                        </select>
                                    </div>
                                `}
                                ${radioRoutingEnabled ? `
                                    <div class="vf-field vf-field--compact">
                                        <label>Akcija</label>
                                        <select data-vf-action-field="${escapeHtml(action.id)}|action">
                                            ${Object.entries(availableActionOptions).map(([key, label]) => `<option value="${escapeHtml(key)}" ${action.action === key ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                                        </select>
                                    </div>
                                    ${isExternal ? `
                                        <div class="vf-field vf-field--route ${externalUrlError ? 'is-error' : ''}">
                                            <label>Vanjski URL</label>
                                            <input type="text" data-vf-action-field="${escapeHtml(action.id)}|external_url" value="${escapeHtml(action.external_url || '')}" />
                                            ${externalUrlError ? `<div class="vf-field__hint vf-field__hint--error">${escapeHtml(externalUrlError.message)}</div>` : ''}
                                        </div>
                                    ` : `
                                        <div class="vf-field vf-field--route">
                                            <label>Korak na koji vodi</label>
                                            <select data-vf-action-field="${escapeHtml(action.id)}|target_step_id">
                                                <option value="">Automatski / nema</option>
                                                ${flatSteps().map(step => `<option value="${escapeHtml(step.id)}" ${action.target_step_id === step.id ? 'selected' : ''}>${escapeHtml(step.title || step.id)}</option>`).join('')}
                                            </select>
                                        </div>
                                    `}
                                ` : ``}
                            </div>
                        </div>

                        <details class="vf-action-card__advanced" ${(isSubmitAction || action.value || (radioRoutingEnabled && action.external_url) || (radioRoutingEnabled && isExternal)) ? 'open' : ''}>
                            <summary>
                                <span>Napredne postavke</span>
                                <span class="vf-field__hint">${isRadioSurvey ? (radioRoutingEnabled ? 'Tagovi, dodatni link i završni routing' : 'Tag odgovora i dodatne vrijednosti') : 'Tagovi, dodatni link i obavezni submit'}</span>
                            </summary>
                            <div class="vf-action-card__advanced-grid">
                                <div class="vf-two">
                                    <div class="vf-field">
                                        <label>Vrijednost / tag odgovora</label>
                                        <input type="text" data-vf-action-field="${escapeHtml(action.id)}|value" value="${escapeHtml(action.value || '')}" />
                                    </div>
                                    ${radioRoutingEnabled && !isExternal ? `
                                        <div class="vf-field ${externalUrlError ? 'is-error' : ''}">
                                            <label>Vanjski URL</label>
                                            <input type="text" data-vf-action-field="${escapeHtml(action.id)}|external_url" value="${escapeHtml(action.external_url || '')}" />
                                            ${externalUrlError ? `<div class="vf-field__hint vf-field__hint--error">${escapeHtml(externalUrlError.message)}</div>` : ''}
                                        </div>
                                    ` : radioRoutingEnabled ? `
                                        <div class="vf-field">
                                            <label>Interni korak</label>
                                            <select data-vf-action-field="${escapeHtml(action.id)}|target_step_id">
                                                <option value="">Ne koristi se</option>
                                                ${flatSteps().map(step => `<option value="${escapeHtml(step.id)}" ${action.target_step_id === step.id ? 'selected' : ''}>${escapeHtml(step.title || step.id)}</option>`).join('')}
                                            </select>
                                        </div>
                                    ` : ''}
                                </div>

                                ${hasCaptureFields && !isSystemSubmit && !isRadioSurvey ? `
                                    <label class="vf-toggle"><input type="checkbox" data-vf-action-toggle="${escapeHtml(action.id)}|require_submit" ${action.require_submit ? 'checked' : ''}> Prije ove akcije obavezno pošalji podatke</label>
                                ` : ``}
                            </div>
                        </details>

                        <div class="vf-card-actions">
                            <button type="button" data-vf-action-remove="${escapeHtml(action.id)}">Ukloni</button>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    function renderPreview() {
        const surface = getRenderableSurface();
        const blocks = getEditableBlocks();
        const activeStep = getActiveStep();
        const metaChips = [];
        const needsSubmitFallback = (currentSurfaceHasCaptureFields() || currentSurfaceHasDeferredSurvey()) && !currentSurfaceHasSubmitAction();

        if(state.screen === 'landing') {
            metaChips.push(surface.name || 'Glavna landing stranica');
        } else if(activeStep) {
            metaChips.push(activeStep.title || 'Korak');
            metaChips.push((pathOptions.find(item => item.path_key === activeStep.path_key)?.title) || activeStep.path_key || 'Put');
        }

        if(surface.ab_enabled && state.screen !== 'landing') {
            metaChips.push(`Varijanta ${state.activeVariant.toUpperCase()}`);
        }

        previewRoot.innerHTML = `
            <div class="vf-card">
                <div class="vf-card__head">
                    <div>
                        <h2 class="vf-card__title">Live pregled stranice</h2>
                        <div class="vf-card__sub">Sve što mijenjaš lijevo odmah se preslikava na preview desno.</div>
                    </div>
                </div>
                <div class="vf-card__body">
                    <div class="vf-preview-page" style="background:${escapeHtml(surface.background_color || '#0f172a')};">
                        <div class="vf-preview-page__canvas" style="
                            max-width:${surface.max_width === 'narrow' ? '640px' : surface.max_width === 'regular' ? '820px' : '100%'};
                            background:${escapeHtml(surface.surface_color || '#152132')};
                            color:${escapeHtml(surface.text_color || '#eef4ff')};
                            --vf-accent:${escapeHtml(surface.accent_color || '#67d8c9')};
                        ">
                            <div class="vf-preview-page__header">${metaChips.map(chip => `<span class="vf-chip">${escapeHtml(chip)}</span>`).join('')}</div>
                            <div class="vf-preview-blocks">
                                ${blocks.length ? blocks.map(block => renderPreviewBlock(block, surface)).join('') : '<div class="vf-empty">Dodaj prvi blok i pregled će se odmah pojaviti ovdje.</div>'}
                                ${needsSubmitFallback ? `
                                    <div class="vf-preview-block" data-vf-span="full" style="background:${hexToRgba(surface.accent_color || '#67d8c9', 0.08)};border-color:${hexToRgba(surface.accent_color || '#67d8c9', 0.22)};">
                                        <div class="vf-preview-text" style="margin-bottom:.7rem;font-weight:700;">Ova stranica još nema završni submit gumb pa je ovdje prikazan sigurnosni fallback.</div>
                                        <div class="vf-preview-buttons">
                                            <span class="vf-preview-btn is-primary" style="background:${surface.accent_color || '#67d8c9'};color:#0f172a;border-color:transparent;">Pošalji i nastavi</span>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderPreviewBlock(block, surface) {
        const accentColor = block.accent_color || surface.accent_color || '#67d8c9';
        const layoutWidth = Object.prototype.hasOwnProperty.call(pageBlockWidthOptions, block.layout_width) ? block.layout_width : 'full';
        const blockBackground = block.background_color || hexToRgba(accentColor, 0.08);
        const baseTextColor = block.text_color || surface.text_color || '#eef4ff';
        const badgeColor = resolveBlockColor(block.badge_color, baseTextColor);
        const titleColor = resolveBlockColor(block.title_color, baseTextColor);
        const bodyColor = resolveBlockColor(block.body_color, baseTextColor);
        const fieldTextColor = resolveBlockColor(block.field_text_color, baseTextColor);
        const placeholderColor = block.placeholder_color || '#aab4c7';
        const customButtonTextColor = block.button_text_color || '';
        const fontFamily = getFontFamilyCss(block.font_family);
        const badgeSize = Number(block.badge_size || 13);
        const badgeWeight = Number(block.badge_weight || 800);
        const titleSize = Number(block.title_size || 28);
        const titleWeight = Number(block.title_weight || 800);
        const textSize = Number(block.text_size || 17);
        const textWeight = Number(block.text_weight || 500);
        const fieldSize = Number(block.field_size || 16);
        const fieldWeight = Number(block.field_weight || 500);
        const buttonSize = Number(block.button_size || 17);
        const buttonWeight = Number(block.button_weight || 800);
        const badgeStyle = `background:${hexToRgba(accentColor, 0.16)};border:1px solid ${hexToRgba(accentColor, 0.34)};color:${badgeColor};font-size:${badgeSize}px;font-weight:${badgeWeight};font-family:${fontFamily};`;
        const blockStyles = [
            `background:${blockBackground}`,
            `color:${baseTextColor}`,
            `--vf-accent:${accentColor}`,
            `border-color:${hexToRgba(accentColor, 0.26)}`,
            `font-family:${fontFamily}`,
            `--vf-placeholder-color:${placeholderColor}`
        ];
        const titleStyle = `font-size:${titleSize}px;font-weight:${titleWeight};font-family:${fontFamily};color:${titleColor};`;
        const textStyle = `font-size:${textSize}px;font-weight:${textWeight};font-family:${fontFamily};color:${bodyColor};`;
        const fieldStyle = `font-size:${fieldSize}px;font-weight:${fieldWeight};font-family:${fontFamily};color:${fieldTextColor};border-color:${hexToRgba(accentColor, 0.28)};background:${hexToRgba(accentColor, 0.08)};--vf-placeholder-color:${placeholderColor};`;

        const resolveVideoEmbedUrl = (url) => {
            const value = String(url || '').trim();

            if(!value) {
                return '';
            }

            let match = value.match(/^https?:\/\/(?:www\.)?youtube(?:-nocookie)?\.com\/embed\/([A-Za-z0-9_-]{6,})/i);
            if(match) {
                return `https://www.youtube.com/embed/${match[1]}`;
            }

            match = value.match(/^https?:\/\/player\.vimeo\.com\/video\/([0-9]+)(\?h=[A-Za-z0-9]+)?/i);
            if(match) {
                return `https://player.vimeo.com/video/${match[1]}${match[2] || ''}`;
            }

            match = value.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|watch\?.+&v=|embed\/|shorts\/|v\/))([A-Za-z0-9_-]{6,})/i);
            if(match) {
                return `https://www.youtube.com/embed/${match[1]}`;
            }

            match = value.match(/vimeo\.com\/(?:.*\/)?([0-9]+)(\?h=[A-Za-z0-9]+)?/i);
            if(match) {
                return `https://player.vimeo.com/video/${match[1]}${match[2] || ''}`;
            }

            return '';
        };

        const isDirectVideoFileUrl = (url) => {
            const value = String(url || '').trim().split('?')[0].toLowerCase();
            return ['.mp4', '.webm', '.ogg', '.mov', '.m4v'].some(extension => value.endsWith(extension));
        };

        if(block.type === 'spacer') {
            const spacingMap = {xs: '24px', sm: '38px', md: '56px', lg: '88px', xl: '128px'};
            return `<div class="vf-preview-spacer" data-vf-span="${escapeHtml(layoutWidth)}" style="height:${spacingMap[block.spacing] || '56px'};"></div>`;
        }

        if(['name_field', 'full_name_field', 'email_field', 'phone_field', 'text_field'].includes(block.type)) {
            return `
                <div class="vf-preview-block align-${escapeHtml(block.alignment || 'left')}" data-vf-span="${escapeHtml(layoutWidth)}" style="${blockStyles.join(';')}">
                    ${block.title ? `<div class="vf-preview-text" style="margin-bottom:.45rem;${titleStyle}">${escapeHtml(block.title)}</div>` : ''}
                    <input class="vf-preview-field" value="" placeholder="${escapeHtml(block.placeholder || '')}" readonly style="${fieldStyle}" />
                </div>
            `;
        }

        if(block.type === 'countdown') {
            const countdownStyle = ['cards', 'glass', 'minimal', 'spotlight'].includes(block.countdown_style) ? block.countdown_style : 'cards';
            const countdownNumberSize = Math.max(16, Math.min(96, parseInt(block.countdown_number_size || 34, 10) || 34));
            const countdownNumberColor = block.countdown_number_color || accentColor;
            const countdownUnits = [
                {enabled: block.countdown_show_days !== false, value: '03', label: 'Dana'},
                {enabled: block.countdown_show_hours !== false, value: '12', label: 'Sati'},
                {enabled: block.countdown_show_minutes !== false, value: '34', label: 'Min'},
                {enabled: block.countdown_show_seconds !== false, value: '56', label: 'Sek'}
            ].filter(item => item.enabled);
            const visibleCountdownUnits = countdownUnits.length ? countdownUnits : [{value: '56', label: 'Sek'}];
            return `
                <div class="vf-preview-block align-${escapeHtml(block.alignment || 'left')}" data-vf-span="${escapeHtml(layoutWidth)}" style="${blockStyles.join(';')}">
                    ${block.badge ? `<div class="vf-preview-badge" style="${badgeStyle}">${escapeHtml(block.badge)}</div>` : ''}
                    ${block.title ? `<div class="vf-preview-title" style="${titleStyle}">${escapeHtml(block.title)}</div>` : ''}
                    ${block.text ? `<div class="vf-preview-text" style="${textStyle}">${escapeHtml(block.text)}</div>` : ''}
                    <div class="vf-preview-countdown vf-preview-countdown--${escapeHtml(countdownStyle)}" style="font-family:${fontFamily};">
                        <div class="vf-preview-countdown__row is-cols-${visibleCountdownUnits.length}">
                            ${visibleCountdownUnits.map(({value, label}) => `
                                <div class="vf-preview-countdown__item" style="border-color:${hexToRgba(accentColor, 0.2)};">
                                    <div class="vf-preview-countdown__value" style="font-size:${countdownNumberSize}px;color:${countdownNumberColor};">${value}</div>
                                    <div class="vf-preview-countdown__label">${label}</div>
                                </div>
                            `).join('')}
                        </div>
                        <div class="vf-preview-countdown__expired">
                            <div class="vf-preview-countdown__expired-kicker">Expired stanje</div>
                            <div class="vf-preview-countdown__expired-text">${escapeHtml(block.completion_text || 'Vrijeme je isteklo.')}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        if(block.type === 'product_offer') {
            const productData = getPreviewProductData(block);
            const product = productData.product;
            const primaryStyle = `background:${accentColor};color:${customButtonTextColor || '#0f172a'};border-color:transparent;font-size:${buttonSize}px;font-weight:${buttonWeight};font-family:${fontFamily};`;
            const secondaryStyle = `background:${hexToRgba(accentColor, 0.12)};color:${customButtonTextColor || bodyColor};border-color:${hexToRgba(accentColor, 0.24)};font-size:${buttonSize}px;font-weight:${buttonWeight};font-family:${fontFamily};`;

            return `
                <div class="vf-preview-block align-${escapeHtml(block.alignment || 'left')}" data-vf-span="${escapeHtml(layoutWidth)}" style="${blockStyles.join(';')}">
                    ${block.badge ? `<div class="vf-preview-badge" style="${badgeStyle}">${escapeHtml(block.badge)}</div>` : ''}
                    ${block.title ? `<div class="vf-preview-title" style="${titleStyle}">${escapeHtml(block.title)}</div>` : ''}
                    ${block.text ? `<div class="vf-preview-text" style="${textStyle}">${escapeHtml(block.text)}</div>` : ''}
                    <div class="vf-preview-product-card" style="border-color:${hexToRgba(accentColor, 0.22)};background:${hexToRgba(accentColor, 0.08)};">
                        ${product ? `
                            <div class="vf-preview-product-card__inner">
                                ${product.image_url ? `
                                    <div class="vf-preview-product-card__image" style="border-color:${hexToRgba(accentColor, 0.18)};">
                                        <img src="${escapeHtml(product.image_url)}" alt="${escapeHtml(product.title || 'Proizvod')}" />
                                    </div>
                                ` : ''}
                                <div class="vf-preview-product-card__content">
                                    <div class="vf-chip-row" style="margin-bottom:.45rem;">
                                        <span class="vf-chip vf-chip--soft">${escapeHtml(getProductSourceModeLabel(productData.sourceMode))}</span>
                                        <span class="vf-chip vf-chip--accent">${escapeHtml(getProductTargetModeLabel(productData.primaryMode))}</span>
                                        <span class="vf-chip vf-chip--soft">${escapeHtml(getProductLanguageModeLabel(productData.languageMode))}</span>
                                        <span class="vf-chip vf-chip--soft">${escapeHtml((productData.resolvedLanguageCode || 'hr').toUpperCase())}</span>
                                    </div>
                                    ${productData.sourceMode === 'dynamic' && productData.previewMapping ? `
                                        <div class="vf-field__hint" style="margin-bottom:.5rem;">Preview mapiranja: ako osoba odabere <strong>${escapeHtml(productData.previewMapping.match_value || '')}</strong>, blok prikazuje ovaj proizvod.</div>
                                    ` : ''}
                                    <div class="vf-preview-product-card__title" style="font-family:${fontFamily};color:${titleColor};">${escapeHtml(product.title || 'Odabrani proizvod')}</div>
                                    ${product.description ? `<div class="vf-preview-product-card__text" style="font-family:${fontFamily};color:${bodyColor};">${escapeHtml(product.description)}</div>` : ''}
                                    <div class="vf-preview-buttons" style="margin-top:.9rem;">
                                        <span class="vf-preview-btn is-primary" style="${primaryStyle}">${escapeHtml(productData.primaryCtaText)}</span>
                                        ${productData.secondaryEnabled ? `<span class="vf-preview-btn is-secondary" style="${secondaryStyle}">${escapeHtml(productData.secondaryCtaText)}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                        ` : `
                            <div class="vf-preview-product-card__empty">
                                Odaberi proizvod iz blog kataloga i ovdje ćeš odmah vidjeti premium preview preporuke, slike i oba CTA modela.
                            </div>
                        `}
                    </div>
                </div>
            `;
        }

        if(block.type === 'image' || block.type === 'video') {
            const mediaPreview = (() => {
                if(!block.media_url) {
                    return `<div>${block.type === 'image' ? 'Ovdje ide slika' : 'Ovdje ide video'}</div>`;
                }

                if(block.type === 'image') {
                    return `<img src="${escapeHtml(block.media_url)}" alt="">`;
                }

                const embedUrl = resolveVideoEmbedUrl(block.media_url);

                if(embedUrl) {
                    return `<iframe src="${escapeHtml(embedUrl)}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>`;
                }

                if(isDirectVideoFileUrl(block.media_url)) {
                    return `<video controls preload="metadata"><source src="${escapeHtml(block.media_url)}"></video>`;
                }

                return `<div>Video URL<br>${escapeHtml(block.media_url)}</div>`;
            })();

            return `
                <div class="vf-preview-block align-${escapeHtml(block.alignment || 'center')}" data-vf-span="${escapeHtml(layoutWidth)}" style="${blockStyles.join(';')}">
                    ${block.badge ? `<div class="vf-preview-badge" style="${badgeStyle}">${escapeHtml(block.badge)}</div>` : ''}
                    ${block.title ? `<div class="vf-preview-title" style="${titleStyle}">${escapeHtml(block.title)}</div>` : ''}
                    ${block.text ? `<div class="vf-preview-text" style="margin-bottom:.8rem;${textStyle}">${escapeHtml(block.text)}</div>` : ''}
                    <div class="vf-preview-media ${block.type === 'video' ? 'is-video' : ''}">
                        ${mediaPreview}
                    </div>
                </div>
            `;
        }

        const buttonsHtml = (items) => `
            <div class="vf-preview-buttons">
                ${(items || []).map(item => {
                    const style = item.style || 'primary';
                    const buttonTextColor = customButtonTextColor || (style === 'primary' ? '#0f172a' : bodyColor);
                    let inlineStyle = `border-color:${hexToRgba(accentColor, 0.26)};`;

                    if(style === 'primary') {
                        inlineStyle += `background:${accentColor};color:${buttonTextColor};`;
                    } else if(style === 'secondary') {
                        inlineStyle += `background:${hexToRgba(accentColor, 0.14)};color:${buttonTextColor || bodyColor};`;
                    } else {
                        inlineStyle += `background:transparent;color:${buttonTextColor || bodyColor};`;
                    }

                    inlineStyle += `font-size:${buttonSize}px;font-weight:${buttonWeight};font-family:${fontFamily};`;

                    return `<span class="vf-preview-btn is-${escapeHtml(style)}" style="${inlineStyle}">${escapeHtml(item.label || 'Opcija')}</span>`;
                }).join('')}
            </div>
        `;

        const radioOptionsHtml = (items) => `
            <div class="vf-preview-radio-list">
                ${(items || []).map(item => `
                    <label class="vf-preview-radio-item" style="border-color:${hexToRgba(accentColor, 0.22)};background:${hexToRgba(accentColor, 0.08)};">
                            <span class="vf-preview-radio-dot" style="border-color:${hexToRgba(accentColor, 0.42)};"></span>
                            <span class="vf-preview-radio-copy">
                                <span class="vf-preview-radio-label" style="font-size:${textSize}px;font-weight:${Math.max(700, textWeight)};font-family:${fontFamily};color:${bodyColor};">${escapeHtml(item.label || 'Odgovor')}</span>
                            <span class="vf-preview-radio-hint">${escapeHtml(item.hint || 'Ovaj odgovor pomaže da dobiješ jasniji sljedeći korak.')}</span>
                        </span>
                    </label>
                `).join('')}
            </div>
        `;

        return `
            <div class="vf-preview-block align-${escapeHtml(block.alignment || 'left')}" data-vf-span="${escapeHtml(layoutWidth)}" style="${blockStyles.join(';')}">
                ${block.badge ? `<div class="vf-preview-badge" style="${badgeStyle}">${escapeHtml(block.badge)}</div>` : ''}
                ${block.title ? `<div class="vf-preview-title" style="${titleStyle}">${escapeHtml(block.title)}</div>` : ''}
                ${block.text ? `<div class="vf-preview-text" style="${textStyle}">${escapeHtml(block.text)}</div>` : ''}
                ${block.type === 'survey' ? buttonsHtml(block.options) : ''}
                ${block.type === 'radio_survey' ? radioOptionsHtml(block.options) : ''}
                ${block.type === 'cta_group' ? buttonsHtml(block.buttons) : ''}
            </div>
        `;
    }

    function clearDropTargets() {
        form.querySelectorAll('.is-drop-target, .is-drop-before, .is-drop-after').forEach(node => {
            node.classList.remove('is-drop-target', 'is-drop-before', 'is-drop-after');
        });
    }

    function resetDragState() {
        if(state.drag.element) {
            state.drag.element.classList.remove('is-dragging');
        }

        clearDropTargets();
        state.drag = {
            type: '',
            id: '',
            phaseKey: '',
            scope: '',
            element: null
        };
    }

    function getDropIndex(container, selector, pointerY, pointerX = null, targetCard = null) {
        const cards = [...container.querySelectorAll(selector)].filter(node => node !== state.drag.element);

        if(!cards.length) {
            return 0;
        }

        if(targetCard && cards.includes(targetCard)) {
            const targetIndex = cards.indexOf(targetCard);
            const rect = targetCard.getBoundingClientRect();
            const sameRowPointer = pointerY >= rect.top && pointerY <= rect.bottom;
            const placeAfter = sameRowPointer && pointerX !== null
                ? pointerX >= rect.left + (rect.width / 2)
                : pointerY >= rect.top + (rect.height / 2);

            targetCard.classList.add(placeAfter ? 'is-drop-after' : 'is-drop-before');

            return placeAfter ? targetIndex + 1 : targetIndex;
        }

        for(let index = 0; index < cards.length; index++) {
            const rect = cards[index].getBoundingClientRect();
            if(pointerY < rect.top + (rect.height / 2)) {
                return index;
            }
        }

        return cards.length;
    }

    function renderAll() {
        ensurePayload();
        normalizeRequireSubmitFlags();
        refreshValidationState();
        renderTabs();
        renderWorkspace();
        renderPreview();
        syncPayloadInput();
    }

    tabsRoot.addEventListener('click', event => {
        const stepTab = event.target.closest('[data-vf-step-tab]');
        if(stepTab) {
            setActiveStep(stepTab.getAttribute('data-vf-step-tab'));
            return;
        }

        const addStepRailButton = event.target.closest('[data-vf-add-step-rail]');
        if(addStepRailButton) {
            addStepFromRail();
            return;
        }

        const tab = event.target.closest('[data-vf-screen]');
        if(!tab) return;
        state.screen = tab.getAttribute('data-vf-screen');
        if(state.screen === 'landing') {
            state.activeVariant = 'a';
        }
        ensureActiveBlock();
        renderAll();
    });

    tabsRoot.addEventListener('dragstart', event => {
        const stepTab = event.target.closest('[data-vf-step-tab]');
        if(!stepTab) {
            return;
        }

        state.drag = {
            type: 'step-tab',
            id: stepTab.getAttribute('data-vf-step-tab') || '',
            phaseKey: '',
            scope: 'rail',
            element: stepTab
        };
        stepTab.classList.add('is-dragging');

        if(event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', state.drag.id);
        }
    });

    tabsRoot.addEventListener('dragover', event => {
        if(state.drag.type !== 'step-tab') {
            return;
        }

        const targetTab = event.target.closest('[data-vf-step-tab]');
        if(!targetTab || targetTab === state.drag.element) {
            return;
        }

        event.preventDefault();
        clearDropTargets();

        const rect = targetTab.getBoundingClientRect();
        const position = event.clientX < rect.left + (rect.width / 2) ? 'before' : 'after';
        targetTab.classList.add(position === 'before' ? 'is-drop-before' : 'is-drop-after');

        if(event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
    });

    tabsRoot.addEventListener('drop', event => {
        if(state.drag.type !== 'step-tab') {
            return;
        }

        const targetTab = event.target.closest('[data-vf-step-tab]');
        if(!targetTab || targetTab === state.drag.element) {
            resetDragState();
            return;
        }

        event.preventDefault();

        const rect = targetTab.getBoundingClientRect();
        const position = event.clientX < rect.left + (rect.width / 2) ? 'before' : 'after';
        moveStepRelativeToStep(state.drag.id, targetTab.getAttribute('data-vf-step-tab') || '', position);
        resetDragState();
    });

    tabsRoot.addEventListener('dragend', () => {
        if(state.drag.type === 'step-tab') {
            resetDragState();
        }
    });

    workspaceRoot.addEventListener('click', event => {
        const deleteCurrentStepButton = event.target.closest('[data-vf-delete-current-step]');
        if(deleteCurrentStepButton) {
            deleteStep(deleteCurrentStepButton.getAttribute('data-vf-delete-current-step'));
            return;
        }

        const duplicateButton = event.target.closest('[data-vf-block-duplicate]');
        if(duplicateButton) {
            duplicateBlock(duplicateButton.getAttribute('data-vf-block-duplicate'));
            return;
        }

        const blockUpButton = event.target.closest('[data-vf-block-up]');
        if(blockUpButton) {
            moveBlock(blockUpButton.getAttribute('data-vf-block-up'), -1);
            return;
        }

        const blockDownButton = event.target.closest('[data-vf-block-down]');
        if(blockDownButton) {
            moveBlock(blockDownButton.getAttribute('data-vf-block-down'), 1);
            return;
        }

        const blockDeleteButton = event.target.closest('[data-vf-block-delete]');
        if(blockDeleteButton) {
            deleteBlock(blockDeleteButton.getAttribute('data-vf-block-delete'));
            return;
        }

        const stepUpButton = event.target.closest('[data-vf-step-up]');
        if(stepUpButton) {
            moveStep(stepUpButton.getAttribute('data-vf-step-up'), -1);
            return;
        }

        const stepDownButton = event.target.closest('[data-vf-step-down]');
        if(stepDownButton) {
            moveStep(stepDownButton.getAttribute('data-vf-step-down'), 1);
            return;
        }

        const stepDeleteButton = event.target.closest('[data-vf-step-delete]');
        if(stepDeleteButton) {
            deleteStep(stepDeleteButton.getAttribute('data-vf-step-delete'));
            return;
        }

        const addStepButton = event.target.closest('[data-vf-add-step]');
        if(addStepButton) {
            addStep(addStepButton.getAttribute('data-vf-add-step'));
            return;
        }

        const stepCard = event.target.closest('[data-vf-step-card]');
        if(stepCard) {
            setActiveStep(stepCard.getAttribute('data-vf-step-card'));
            return;
        }

        const addBlockButton = event.target.closest('[data-vf-add-block]');
        if(addBlockButton) {
            const scope = addBlockButton.getAttribute('data-vf-add-block');
            const select = document.getElementById(`vf_add_block_select_${scope}`);
            addBlock(select ? select.value : 'headline');
            return;
        }

        const addSubmitCtaButton = event.target.closest('[data-vf-add-submit-cta]');
        if(addSubmitCtaButton) {
            addDefaultSubmitCta();
            return;
        }

        const gallerySelectButton = event.target.closest('[data-vf-gallery-select]');
        if(gallerySelectButton) {
            const block = getActiveBlock();
            const imageUrl = gallerySelectButton.getAttribute('data-vf-gallery-select') || '';

            if(block && block.type === 'image' && imageUrl) {
                block.media_url = imageUrl;
                syncPayloadInput();
                renderAll();
            }

            return;
        }

        const blockWidthButton = event.target.closest('[data-vf-block-width]');
        if(blockWidthButton) {
            const [blockId, widthKey] = String(blockWidthButton.getAttribute('data-vf-block-width') || '').split('|');
            const block = getEditableBlocks().find(item => item.id === blockId);

            if(block && Object.prototype.hasOwnProperty.call(pageBlockWidthOptions, widthKey)) {
                block.layout_width = widthKey;
                state.activeBlockId = block.id;
                syncPayloadInput();
                renderAll();
            }

            return;
        }

        const blockCard = event.target.closest('[data-vf-block-card]');
        if(blockCard) {
            state.activeBlockId = blockCard.getAttribute('data-vf-block-card');
            state.activeActionId = '';
            renderAll();
            return;
        }

        const actionCardToggle = event.target.closest('[data-vf-action-card]');
        if(actionCardToggle) {
            toggleActiveAction(actionCardToggle.getAttribute('data-vf-action-card'));
            return;
        }

        const templateButton = event.target.closest('[data-vf-template]');
        if(templateButton) {
            applyTemplate(templateButton.getAttribute('data-vf-template'));
            return;
        }

        const addActionButton = event.target.closest('[data-vf-add-action]');
        if(addActionButton) {
            addAction(addActionButton.getAttribute('data-vf-add-action'));
            return;
        }

        const addProductMappingButton = event.target.closest('[data-vf-product-mapping-add]');
        if(addProductMappingButton) {
            addProductMapping();
            return;
        }

        const removeActionButton = event.target.closest('[data-vf-action-remove]');
        if(removeActionButton) {
            const block = getActiveBlock();
            if(!block) return;
            removeAction(isOptionBlockType(block.type) ? 'survey' : 'cta', removeActionButton.getAttribute('data-vf-action-remove'));
            return;
        }

        const removeProductMappingButton = event.target.closest('[data-vf-product-mapping-remove]');
        if(removeProductMappingButton) {
            removeProductMapping(removeProductMappingButton.getAttribute('data-vf-product-mapping-remove'));
            return;
        }

        const variantButton = event.target.closest('[data-vf-variant]');
        if(variantButton) {
            state.activeVariant = variantButton.getAttribute('data-vf-variant');
            if(state.activeVariant === 'b') {
                ensureVariantBSurfaceState(getCurrentSurface());
            }
            ensureActiveBlock();
            renderAll();
        }
    });

    workspaceRoot.addEventListener('dragstart', event => {
        const stepCard = event.target.closest('[data-vf-step-card]');
        const blockCard = event.target.closest('[data-vf-block-card]');

        if(stepCard) {
            state.drag = {
                type: 'step',
                id: stepCard.getAttribute('data-vf-step-id') || '',
                phaseKey: stepCard.getAttribute('data-vf-step-phase') || '',
                scope: '',
                element: stepCard
            };
            stepCard.classList.add('is-dragging');
        } else if(blockCard) {
            state.drag = {
                type: 'block',
                id: blockCard.getAttribute('data-vf-block-id') || '',
                phaseKey: '',
                scope: state.screen === 'landing' ? 'landing' : 'step',
                element: blockCard
            };
            blockCard.classList.add('is-dragging');
        } else {
            return;
        }

        if(event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', state.drag.id);
        }
    });

    workspaceRoot.addEventListener('dragover', event => {
        if(!state.drag.type) {
            return;
        }

        const dropzone = state.drag.type === 'step'
            ? event.target.closest('[data-vf-step-dropzone]')
            : event.target.closest('[data-vf-block-dropzone]');

        if(!dropzone) {
            return;
        }

        event.preventDefault();
        clearDropTargets();
        dropzone.classList.add('is-drop-target');

        if(state.drag.type === 'block') {
            const targetCard = event.target.closest('[data-vf-block-card]');
            if(targetCard && targetCard !== state.drag.element) {
                getDropIndex(dropzone, '[data-vf-block-card]', event.clientY, event.clientX, targetCard);
            }
        }

        if(event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
    });

    workspaceRoot.addEventListener('drop', event => {
        if(!state.drag.type) {
            return;
        }

        const dropzone = state.drag.type === 'step'
            ? event.target.closest('[data-vf-step-dropzone]')
            : event.target.closest('[data-vf-block-dropzone]');

        if(!dropzone) {
            return;
        }

        event.preventDefault();

        if(state.drag.type === 'step') {
            const targetPhaseKey = dropzone.getAttribute('data-vf-step-dropzone') || '';
            const targetIndex = getDropIndex(dropzone, '[data-vf-step-card]', event.clientY);
            moveStepToPhase(state.drag.id, targetPhaseKey, targetIndex);
        }

        if(state.drag.type === 'block') {
            const targetCard = event.target.closest('[data-vf-block-card]');
            const targetIndex = getDropIndex(dropzone, '[data-vf-block-card]', event.clientY, event.clientX, targetCard && targetCard !== state.drag.element ? targetCard : null);
            moveBlockToIndex(state.drag.id, targetIndex);
        }

        resetDragState();
    });

    workspaceRoot.addEventListener('dragend', () => {
        resetDragState();
    });

    function applyGenericFieldUpdate(target) {
        const funnelField = target.closest('[data-vf-funnel-field]');
        if(funnelField) {
            const field = funnelField.getAttribute('data-vf-funnel-field');
            state.payload.funnel = state.payload.funnel || {};
            state.payload.funnel[field] = coerceFieldValue(funnelField);
            syncPayloadInput();
            renderPreview();
            return true;
        }

        const surfaceField = target.closest('[data-vf-surface-field]');
        if(surfaceField) {
            const field = surfaceField.getAttribute('data-vf-surface-field');
            const globalSurfaceFields = ['ab_distribution'];
            const surfaceTarget = globalSurfaceFields.includes(field) ? getCurrentSurface() : getEditableSurfaceSettingsTarget();
            surfaceTarget[field] = coerceFieldValue(surfaceField);
            syncPayloadInput();
            renderPreview();
            return true;
        }

        const blockField = target.closest('[data-vf-block-field]');
        if(blockField) {
            const block = getActiveBlock();
            if(!block) return true;
            block[blockField.getAttribute('data-vf-block-field')] = coerceFieldValue(blockField);
            syncPayloadInput();
            renderPreview();
            return true;
        }

        const stepField = target.closest('[data-vf-step-field]');
        if(stepField) {
            const activeStep = getActiveStep();
            if(!activeStep) return true;
            activeStep[stepField.getAttribute('data-vf-step-field')] = coerceFieldValue(stepField);
            if(stepField.getAttribute('data-vf-step-field') === 'title' && activeStep.page && !activeStep.page.name) {
                activeStep.page.name = stepField.value;
            }
            syncPayloadInput();
            renderPreview();
            return true;
        }

        const actionField = target.closest('[data-vf-action-field]');
        if(actionField) {
            const block = getActiveBlock();
            if(!block) return true;
            const [actionId, field] = actionField.getAttribute('data-vf-action-field').split('|');
            const items = getBlockActionItems(block);
            const action = items.find(item => item.id === actionId);
            if(!action) return true;
            action[field] = coerceFieldValue(actionField);
            syncPayloadInput();
            renderPreview();
            return true;
        }

        const productMappingField = target.closest('[data-vf-product-mapping-field]');
        if(productMappingField) {
            const block = getActiveBlock();
            if(!block || block.type !== 'product_offer') return true;
            const [mappingId, field] = productMappingField.getAttribute('data-vf-product-mapping-field').split('|');
            block.product_mappings = normalizeProductMappings(block.product_mappings || []);
            const mapping = block.product_mappings.find(item => item.id === mappingId);
            if(!mapping) return true;
            mapping[field] = coerceFieldValue(productMappingField);

            if(field === 'product_translation_key') {
                const selectedOption = productMappingField.options ? productMappingField.options[productMappingField.selectedIndex] : null;
                mapping.product_blog_post_id = selectedOption ? Number(selectedOption.getAttribute('data-blog-post-id') || 0) : 0;
            }

            syncPayloadInput();
            renderPreview();
            return true;
        }

        return false;
    }

    workspaceRoot.addEventListener('input', event => {
        applyGenericFieldUpdate(event.target);
    });

    workspaceRoot.addEventListener('focusout', event => {
        if(event.target.closest('[data-vf-funnel-field], [data-vf-surface-field], [data-vf-block-field], [data-vf-step-field], [data-vf-action-field], [data-vf-product-mapping-field]')) {
            renderAll();
        }
    });

    workspaceRoot.addEventListener('change', event => {
        const stepPhaseSelect = event.target.closest('[data-vf-step-phase-select]');
        if(stepPhaseSelect) {
            const activeStep = getActiveStep();
            if(activeStep) {
                const targetPhaseKey = stepPhaseSelect.value || getDefaultPhaseKeyForNewStep();
                const targetPhase = (state.payload.board || []).find(phase => phase.key === targetPhaseKey);
                const targetIndex = targetPhase && Array.isArray(targetPhase.steps) ? targetPhase.steps.length : 0;
                moveStepToPhase(activeStep.id, targetPhaseKey, targetIndex);
            }
            return;
        }

        const imageUploadField = event.target.closest('[data-vf-image-upload]');
        if(imageUploadField) {
            uploadImageForBlock(imageUploadField);
            return;
        }

        const productSelector = event.target.closest('[data-vf-product-selector]');
        if(productSelector) {
            const block = getActiveBlock();
            if(!block) return;
            const selectedOption = productSelector.options[productSelector.selectedIndex];
            block.product_translation_key = productSelector.value || '';
            block.product_blog_post_id = selectedOption ? Number(selectedOption.getAttribute('data-blog-post-id') || 0) : 0;
            syncPayloadInput();
            renderAll();
            return;
        }

        const actionField = event.target.closest('[data-vf-action-field]');
        const blockField = event.target.closest('[data-vf-block-field]');
        const productMappingField = event.target.closest('[data-vf-product-mapping-field]');

        if(applyGenericFieldUpdate(event.target)) {
            if(actionField) {
                const [, field] = actionField.getAttribute('data-vf-action-field').split('|');

                if(['action', 'style', 'target_step_id'].includes(field)) {
                    renderAll();
                }
            }

            if(blockField) {
                const field = blockField.getAttribute('data-vf-block-field');
                if(['product_language_mode', 'product_source_mode'].includes(field)) {
                    renderAll();
                }
            }

            if(productMappingField) {
                renderAll();
            }

            return;
        }

        const surfaceToggle = event.target.closest('[data-vf-surface-toggle]');
        if(surfaceToggle) {
            const surface = getCurrentSurface();
            const field = surfaceToggle.getAttribute('data-vf-surface-toggle');

            if(field === 'ab_enabled') {
                surface.ab_enabled = !!surfaceToggle.checked;
            } else {
                const surfaceTarget = getEditableSurfaceSettingsTarget();
                surfaceTarget[field] = !!surfaceToggle.checked;
            }

            if(surface.ab_enabled && !surface.variant_b_blocks.length && surface.blocks.length) {
                surface.variant_b_blocks = JSON.parse(JSON.stringify(surface.blocks));
            }
            if(surface.ab_enabled) {
                ensureVariantBSurfaceState(surface);
            }
            renderAll();
            return;
        }

        const defaultToggle = event.target.closest('[data-vf-default-toggle]');
        if(defaultToggle) {
            state.payload.defaults = state.payload.defaults || {};
            state.payload.defaults[defaultToggle.getAttribute('data-vf-default-toggle')] = !!defaultToggle.checked;
            syncPayloadInput();
            return;
        }

        const blockToggle = event.target.closest('[data-vf-block-toggle]');
        if(blockToggle) {
            const block = getActiveBlock();
            if(!block) return;
            block[blockToggle.getAttribute('data-vf-block-toggle')] = !!blockToggle.checked;
            if(block.type === 'countdown') {
                const countdownToggleFields = ['countdown_show_days', 'countdown_show_hours', 'countdown_show_minutes', 'countdown_show_seconds'];
                if(countdownToggleFields.includes(blockToggle.getAttribute('data-vf-block-toggle'))) {
                    const hasVisibleUnit = countdownToggleFields.some(field => block[field] !== false);
                    if(!hasVisibleUnit) {
                        block.countdown_show_seconds = true;
                    }
                }
            }
            renderAll();
            return;
        }

        const blockBoolean = event.target.closest('[data-vf-block-boolean]');
        if(blockBoolean) {
            const block = getActiveBlock();
            if(!block) return;
            block[blockBoolean.getAttribute('data-vf-block-boolean')] = blockBoolean.value === '1';
            renderAll();
            return;
        }

        const actionToggle = event.target.closest('[data-vf-action-toggle]');
        if(actionToggle) {
            const block = getActiveBlock();
            if(!block) return;
            const [actionId, field] = actionToggle.getAttribute('data-vf-action-toggle').split('|');
            const items = getBlockActionItems(block);
            const action = items.find(item => item.id === actionId);
            if(!action) return;
            action[field] = !!actionToggle.checked;
            renderAll();
        }
    });

    resetButton?.addEventListener('click', () => {
        state.resetRequested = true;
    });

    saveButtons.forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            state.resetRequested = false;
            saveStudio();
        });
    });

    form.addEventListener('submit', event => {
        syncPayloadInput();

        const submitter = event.submitter;
        const isResetSubmit = state.resetRequested || submitter?.hasAttribute('data-vf-reset-button');

        if(isResetSubmit) {
            state.resetRequested = false;
            return;
        }

        event.preventDefault();
        saveStudio();
    });

    document.addEventListener('keydown', event => {
        const isSaveShortcut = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's';

        if(!isSaveShortcut) {
            return;
        }

        event.preventDefault();
        saveStudio();
    });

    ensurePayload();
    renderAll();
})();
</script>
