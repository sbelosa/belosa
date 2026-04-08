<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-03-31: Leader Operating System overview shell -->
<style>
    .leader-os-shell {
        --leader-bg-0: #122029;
        --leader-bg-1: #152634;
        --leader-bg-2: #1a2c3c;
        --leader-panel: rgba(22, 34, 50, 0.92);
        --leader-panel-2: rgba(18, 29, 43, 0.96);
        --leader-border: rgba(118, 170, 208, 0.22);
        --leader-border-strong: rgba(129, 197, 255, 0.38);
        --leader-text: #f2f7ff;
        --leader-text-soft: rgba(213, 225, 239, 0.78);
        --leader-chip: rgba(46, 94, 141, 0.44);
        --leader-chip-border: rgba(129, 197, 255, 0.3);
        --leader-blue: #5ec8ff;
        --leader-cyan: #3fe0c8;
        --leader-green: #6be38b;
        --leader-gold: #efc86b;
    }

    .leader-os-shell {
        border: 1px solid var(--leader-border);
        border-radius: 1.25rem;
        background:
            radial-gradient(circle at top left, rgba(63, 224, 200, 0.1), transparent 22%),
            radial-gradient(circle at 78% 0%, rgba(94, 200, 255, 0.12), transparent 26%),
            linear-gradient(180deg, rgba(24, 38, 53, 0.98) 0%, rgba(15, 24, 37, 0.99) 100%);
        box-shadow: 0 1.8rem 3.2rem rgba(2, 6, 23, 0.32), inset 0 1px 0 rgba(255,255,255,0.04);
        overflow: hidden;
    }

    .leader-os-shell,
    .leader-os-shell h1,
    .leader-os-shell h2,
    .leader-os-shell h3,
    .leader-os-shell .h1,
    .leader-os-shell .h2,
    .leader-os-shell .h3,
    .leader-os-shell .h4,
    .leader-os-shell .h5,
    .leader-os-shell .h6 {
        color: #ecf3ff;
    }

    .leader-os-shell .card-body {
        padding: 1.1rem;
    }

    .leader-os-kpi,
    .leader-os-panel {
        height: 100%;
        border-radius: 1.15rem;
        border: 1px solid var(--leader-border);
        background: var(--leader-panel);
        box-shadow: 0 1rem 2rem rgba(2, 6, 23, 0.16), inset 0 1px 0 rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(10px);
    }

    .leader-os-kpi {
        padding: 0.82rem 0.95rem 0.86rem;
        position: relative;
        overflow: hidden;
        min-height: 98px;
        background:
            radial-gradient(circle at top right, rgba(94, 200, 255, 0.12), transparent 30%),
            linear-gradient(180deg, rgba(31, 47, 69, 0.94) 0%, rgba(19, 29, 44, 0.98) 100%);
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .leader-os-panel {
        padding: 1.05rem;
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at top right, rgba(94, 200, 255, 0.09), transparent 28%),
            linear-gradient(180deg, rgba(26, 39, 58, 0.95) 0%, rgba(15, 24, 38, 0.98) 100%);
        box-shadow: 0 1rem 2rem rgba(2, 6, 23, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }

    .leader-os-panel::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 1px;
        background: linear-gradient(90deg, rgba(94, 185, 255, 0.24) 0%, rgba(73, 214, 208, 0.12) 45%, rgba(87, 213, 127, 0.08) 78%, rgba(255, 255, 255, 0) 100%);
        pointer-events: none;
    }

    .leader-os-kpi:hover {
        transform: translateY(-1px);
        border-color: var(--leader-border-strong);
        box-shadow: 0 1rem 2rem rgba(2, 6, 23, 0.2), inset 0 0 0 1px rgba(94, 200, 255, 0.08);
    }

    .leader-os-kpi::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, var(--leader-blue) 0%, var(--leader-cyan) 48%, var(--leader-green) 100%);
        opacity: 0.85;
    }

    .leader-os-kpi.is-clickable {
        cursor: pointer;
    }

    .leader-os-kpi-topline {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
    }

    .leader-os-kpi-label {
        font-size: 0.68rem;
        letter-spacing: 0.11em;
        text-transform: uppercase;
        color: rgba(215, 227, 240, 0.74);
        line-height: 1.15;
        font-weight: 700;
        padding-right: 0.35rem;
    }

    .leader-os-kpi-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.22rem 0.48rem;
        border-radius: 999px;
        border: 1px solid var(--leader-chip-border);
        background: linear-gradient(180deg, rgba(53, 98, 146, 0.56) 0%, rgba(35, 65, 100, 0.72) 100%);
        color: var(--leader-text);
        font-size: 0.64rem;
        font-weight: 600;
        flex-shrink: 0;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.06);
    }

    .leader-os-kpi-value {
        display: inline-flex;
        align-items: baseline;
        gap: 0.45rem;
        font-size: 1.72rem;
        line-height: 0.95;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--leader-text);
    }

    .leader-os-kpi-value-wrap {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .leader-os-kpi-detail-link {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.66rem;
        font-weight: 700;
        color: #b9ebff;
        background: rgba(150, 226, 255, 0.06);
        border: 1px solid rgba(150, 226, 255, 0.14);
        border-radius: 999px;
        padding: 0.26rem 0.48rem;
        white-space: nowrap;
        opacity: 0.92;
    }

    .leader-os-kpi-hint {
        margin-top: 0.42rem;
        font-size: 0.72rem;
        color: rgba(210, 223, 238, 0.62);
        line-height: 1.28;
        max-width: 34ch;
    }

    .leader-os-kpi-link {
        color: #9adfff;
        text-decoration: none;
    }

    .leader-os-kpi-link:hover {
        color: #ccecff;
        text-decoration: none;
    }

    .leader-os-shell .text-muted,
    .leader-os-panel .text-muted,
    .leader-os-kpi .text-muted {
        color: var(--leader-text-soft) !important;
    }

    .leader-os-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(129, 197, 255, 0.22);
        background: linear-gradient(180deg, rgba(40, 60, 86, 0.82) 0%, rgba(27, 41, 59, 0.92) 100%);
        color: var(--leader-text);
        font-size: 0.78rem;
        font-weight: 600;
    }

    .leader-os-inline-note {
	        border-radius: 0.95rem;
        border: 1px solid rgba(125, 211, 252, 0.14);
        background: linear-gradient(90deg, rgba(17, 86, 96, 0.34) 0%, rgba(30, 44, 63, 0.3) 100%);
        color: #edf7ff;
        padding: 0.8rem 0.95rem;
        font-size: 0.84rem;
	        line-height: 1.45;
	    }

	    .leader-os-conversation-item {
	        border-radius: 0.95rem;
	        border: 1px solid rgba(134, 177, 216, 0.16);
	        background: linear-gradient(180deg, rgba(27, 40, 58, 0.94) 0%, rgba(17, 28, 42, 0.98) 100%);
	        padding: 0.95rem 1rem;
	        margin-bottom: 0.9rem;
	    }

    .leader-os-conversation-item.is-admin {
        background: linear-gradient(180deg, rgba(23, 97, 104, 0.52) 0%, rgba(20, 41, 54, 0.96) 100%);
        border-color: rgba(63, 224, 200, 0.28);
    }

	    .leader-os-conversation-message {
	        color: #eef6ff;
	        font-size: 0.94rem;
	        line-height: 1.55;
	        white-space: pre-wrap;
	    }

    .leader-os-link {
        color: #89dbff !important;
    }

    .leader-os-action-button {
        border-color: rgba(129, 197, 255, 0.26);
        background: linear-gradient(180deg, rgba(47, 68, 98, 0.96) 0%, rgba(29, 44, 64, 0.98) 100%);
        color: #f8fbff;
        box-shadow: 0 0.6rem 1.4rem rgba(2, 6, 23, 0.16), inset 0 1px 0 rgba(255,255,255,0.06);
    }

    .leader-os-action-button:hover,
    .leader-os-action-button:focus {
        border-color: rgba(129, 197, 255, 0.58);
        background: linear-gradient(180deg, rgba(67, 98, 140, 0.98) 0%, rgba(39, 61, 89, 1) 100%);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .leader-os-ops-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
        gap: 1rem;
    }

    .leader-os-ops-list {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
    }

    .leader-os-ops-item {
        border-radius: 1rem;
        border: 1px solid rgba(129, 197, 255, 0.16);
        background: linear-gradient(180deg, rgba(27, 40, 58, 0.96) 0%, rgba(15, 24, 38, 0.99) 100%);
        padding: 1rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .leader-os-ops-item-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.9rem;
        margin-bottom: 0.8rem;
    }

    .leader-os-ops-title {
        font-size: 1rem;
        font-weight: 800;
        color: #f5f8ff;
        line-height: 1.15;
    }

    .leader-os-ops-email {
        font-size: 0.84rem;
        color: rgba(201, 217, 234, 0.82);
        word-break: break-word;
    }

    .leader-os-ops-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.8rem 1rem;
        margin-bottom: 0.9rem;
    }

    .leader-os-ops-meta-label {
        display: block;
        font-size: 0.68rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: rgba(219, 232, 247, 0.74);
        margin-bottom: 0.22rem;
        font-weight: 700;
    }

    .leader-os-ops-meta-value {
        font-size: 0.9rem;
        color: #f4f9ff;
        line-height: 1.45;
        word-break: break-word;
    }

    .leader-os-ops-item .text-muted,
    .leader-os-ops-item .text-muted.small,
    .leader-os-ops-item .leader-os-ops-email {
        color: rgba(220, 232, 245, 0.82) !important;
    }

    .leader-os-ops-address {
        white-space: pre-line;
    }

    .leader-os-ops-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        margin-top: 0.2rem;
    }

    .leader-os-ops-actions .btn {
        border-radius: 999px;
    }

    .leader-os-ops-actions .btn-outline-light,
    .leader-os-ops-recent-table .btn-outline-light {
        border-color: rgba(156, 214, 255, 0.46);
        background: linear-gradient(180deg, rgba(68, 95, 129, 0.86) 0%, rgba(42, 62, 88, 0.96) 100%);
        color: #f7fbff;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.08), 0 0.75rem 1.4rem rgba(2, 6, 23, 0.16);
    }

    .leader-os-ops-actions .btn-outline-light:hover,
    .leader-os-ops-actions .btn-outline-light:focus,
    .leader-os-ops-recent-table .btn-outline-light:hover,
    .leader-os-ops-recent-table .btn-outline-light:focus {
        border-color: rgba(185, 232, 255, 0.72);
        background: linear-gradient(180deg, rgba(94, 129, 173, 0.94) 0%, rgba(58, 83, 118, 1) 100%);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .leader-os-ops-actions .leader-os-action-button-danger {
        border-color: rgba(255, 162, 162, 0.58);
        background: linear-gradient(180deg, rgba(150, 42, 52, 0.92) 0%, rgba(108, 24, 40, 0.98) 100%);
        color: #fff5f5;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.08), 0 0.9rem 1.6rem rgba(71, 14, 24, 0.28);
    }

    .leader-os-ops-actions .leader-os-action-button-danger:hover,
    .leader-os-ops-actions .leader-os-action-button-danger:focus {
        border-color: rgba(255, 199, 199, 0.76);
        background: linear-gradient(180deg, rgba(178, 55, 68, 0.96) 0%, rgba(128, 29, 46, 1) 100%);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .leader-os-ops-empty {
        border-radius: 1rem;
        border: 1px dashed rgba(129, 197, 255, 0.24);
        background: rgba(13, 23, 35, 0.42);
        padding: 1.1rem 1rem;
        color: rgba(215, 227, 240, 0.78);
        font-size: 0.92rem;
    }

    .leader-os-ops-kpis .leader-os-kpi {
        min-height: 110px;
    }

    .leader-os-ops-recent-table .table {
        color: #edf5ff;
    }

    .leader-os-ops-recent-table th {
        border-top: 0;
        border-bottom-color: rgba(129, 197, 255, 0.16);
        color: rgba(201, 217, 234, 0.72);
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .leader-os-ops-recent-table td {
        border-color: rgba(129, 197, 255, 0.12);
        vertical-align: middle;
    }

    .leader-os-ops-helper {
        color: rgba(223, 235, 248, 0.86) !important;
        line-height: 1.5;
    }

    .leader-os-toolbar {
        display: grid;
        grid-template-columns: 1.4fr 0.9fr 0.9fr 0.9fr 0.9fr;
        gap: 0.85rem;
    }

    .leader-os-table {
        border: 1px solid rgba(134, 177, 216, 0.16);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(23, 35, 52, 0.9) 0%, rgba(17, 26, 39, 0.98) 100%);
        overflow: hidden;
    }

    .leader-os-table .table {
        margin-bottom: 0;
    }

    .leader-os-table .table thead th {
        border-bottom-color: rgba(134, 177, 216, 0.14);
        color: rgba(210, 223, 238, 0.82);
        background: rgba(255, 255, 255, 0.03);
        white-space: nowrap;
    }

    .leader-os-table .table td,
    .leader-os-table .table th {
        border-top-color: rgba(148, 163, 184, 0.1);
        background: transparent;
        vertical-align: middle;
    }

    .leader-os-status-badge {
        border: 1px solid rgba(129, 197, 255, 0.18);
        border-radius: 999px;
        padding: 0.34rem 0.68rem;
        display: inline-flex;
        align-items: center;
        font-size: 0.74rem;
        font-weight: 700;
        color: #f5f9ff;
        background: linear-gradient(180deg, rgba(45, 63, 90, 0.82) 0%, rgba(30, 44, 66, 0.92) 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
    }

    .leader-os-status-badge.status-success {
        background: linear-gradient(180deg, rgba(29, 109, 83, 0.62) 0%, rgba(21, 72, 58, 0.88) 100%);
        border-color: rgba(107, 227, 139, 0.28);
    }

    .leader-os-status-badge.status-warning {
        background: linear-gradient(180deg, rgba(126, 90, 28, 0.62) 0%, rgba(94, 65, 19, 0.88) 100%);
        border-color: rgba(239, 200, 107, 0.32);
    }

    .leader-os-status-badge.status-info {
        background: linear-gradient(180deg, rgba(39, 92, 148, 0.62) 0%, rgba(25, 67, 109, 0.9) 100%);
        border-color: rgba(94, 200, 255, 0.3);
    }

    .leader-os-status-badge.status-dark {
        background: linear-gradient(180deg, rgba(55, 67, 85, 0.66) 0%, rgba(35, 45, 60, 0.88) 100%);
        border-color: rgba(148, 163, 184, 0.2);
    }

    .leader-os-status-badge.status-webinar {
        background: linear-gradient(180deg, rgba(23, 124, 93, 0.7) 0%, rgba(18, 83, 64, 0.94) 100%);
        border-color: rgba(63, 224, 200, 0.34);
    }

    .leader-os-growth-positive {
        color: #86efac;
    }

    .leader-os-growth-negative {
        color: #fca5a5;
    }

    .leader-os-filter-chip {
        display: inline-flex;
        align-items: center;
        margin: 0 0.5rem 0.5rem 0;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(129, 197, 255, 0.18);
        color: #e6eef8;
        text-decoration: none;
        background: linear-gradient(180deg, rgba(34, 49, 71, 0.78) 0%, rgba(24, 37, 54, 0.9) 100%);
    }

    .leader-os-filter-chip.active {
        border-color: rgba(94, 200, 255, 0.42);
        background: linear-gradient(180deg, rgba(36, 97, 127, 0.54) 0%, rgba(28, 74, 103, 0.82) 100%);
        color: #ffffff;
    }

    .leader-os-sort-link {
        color: rgba(191, 211, 238, 0.8);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .leader-os-sort-link:hover {
        color: #ffffff;
        text-decoration: none;
    }

    .leader-os-sort-link.active {
        color: #ffffff;
    }

    .leader-os-sort-link.active::after {
        content: '↑';
        font-size: 0.75rem;
        color: #7cc8ff;
    }

    .leader-os-queue-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 0.85rem;
    }

    .leader-os-queue-card {
        border: 1px solid rgba(134, 177, 216, 0.14);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(24, 37, 55, 0.9) 0%, rgba(16, 26, 39, 0.98) 100%);
        padding: 1rem;
    }

    .leader-os-queue-reason {
        color: rgba(191, 211, 238, 0.82);
        font-size: 0.88rem;
        line-height: 1.5;
    }

    .leader-os-queue-meta {
        display: grid;
        gap: 0.55rem;
        margin-top: 0.85rem;
    }

    .leader-os-strategist-hero {
        border: 1px solid rgba(125, 211, 252, 0.18);
        border-radius: 1.05rem;
        padding: 1.15rem;
        background:
            radial-gradient(circle at top right, rgba(14, 165, 233, 0.18), transparent 30%),
            linear-gradient(135deg, rgba(20, 77, 80, 0.52) 0%, rgba(25, 39, 60, 0.88) 100%);
        box-shadow: 0 1rem 2rem rgba(2, 6, 23, 0.16), inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }

    .leader-os-strategist-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
        padding: 0.9rem 1rem;
        border-radius: 0.95rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(7, 12, 24, 0.52);
    }

    .leader-os-strategist-actions-forms {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .leader-os-strategist-meta {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .leader-os-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .leader-os-form-grid-full {
        grid-column: 1 / -1;
    }

    .leader-os-field-label {
        display: block;
        margin-bottom: 0.45rem;
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: rgba(191, 211, 238, 0.72);
    }

    .leader-os-input,
    .leader-os-select,
    .leader-os-textarea {
        width: 100%;
        border-radius: 0.9rem;
        border: 1px solid rgba(134, 177, 216, 0.18);
        background: linear-gradient(180deg, rgba(25, 37, 54, 0.9) 0%, rgba(18, 28, 41, 0.98) 100%);
        color: #edf5ff;
        padding: 0.8rem 0.9rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .leader-os-input:focus,
    .leader-os-select:focus,
    .leader-os-textarea:focus {
        outline: none;
        border-color: rgba(96, 165, 250, 0.45);
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.14);
    }

    .leader-os-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .leader-os-segment-list {
        display: flex;
        gap: 0.55rem;
        flex-wrap: wrap;
        margin-top: 0.8rem;
    }

    .leader-os-strategist-list,
    .leader-os-support-list {
        display: grid;
        gap: 0.65rem;
    }

    .leader-os-strategist-item,
    .leader-os-support-item {
        padding: 0.85rem 0.95rem;
        border-radius: 0.95rem;
        border: 1px solid rgba(134, 177, 216, 0.18);
        background: linear-gradient(180deg, rgba(29, 43, 62, 0.9) 0%, rgba(18, 28, 41, 0.98) 100%);
    }

    .leader-os-support-ticket {
        padding: 0.9rem 1rem;
        border-radius: 0.95rem;
        border: 1px solid rgba(126, 180, 220, 0.2);
        background: linear-gradient(180deg, rgba(28, 43, 63, 0.92) 0%, rgba(17, 28, 42, 0.98) 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
    }

    .leader-os-support-ticket.is-clickable {
        cursor: pointer;
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .leader-os-support-ticket.is-clickable:hover {
        transform: translateY(-1px);
        border-color: rgba(94, 200, 255, 0.42);
        box-shadow: 0 1rem 2rem rgba(2, 6, 23, 0.2), inset 0 0 0 1px rgba(94, 200, 255, 0.12);
    }

    .leader-os-support-ticket-button {
        display: block;
        width: 100%;
        padding: 0;
        margin: 0;
        background: transparent;
        border: 0;
        color: inherit;
        text-align: left;
    }

    .leader-os-support-ticket + .leader-os-support-ticket {
        margin-top: 0.75rem;
    }

    .leader-os-support-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1.22fr) minmax(320px, 0.78fr);
        gap: 1rem;
        align-items: start;
    }

    .leader-os-support-main {
        display: grid;
        gap: 1rem;
        align-items: start;
    }

    .leader-os-support-rail {
        display: grid;
        gap: 0.85rem;
    }

    .leader-os-support-main > .leader-os-panel,
    .leader-os-support-main .leader-os-panel .leader-os-panel,
    .leader-os-support-main .leader-os-grid-2 .leader-os-panel {
        height: auto;
    }

    .leader-os-support-main > .leader-os-panel {
        border-color: rgba(126, 180, 220, 0.22);
        background:
            radial-gradient(circle at top right, rgba(56, 189, 248, 0.12), transparent 24%),
            linear-gradient(180deg, rgba(28, 43, 62, 0.94) 0%, rgba(17, 27, 41, 0.98) 100%);
        box-shadow: 0 1.2rem 2.4rem rgba(2, 6, 23, 0.24), inset 0 1px 0 rgba(255,255,255,0.03);
    }

    .leader-os-support-rail > .leader-os-panel {
        border-color: rgba(126, 180, 220, 0.18);
        background:
            radial-gradient(circle at top right, rgba(99, 102, 241, 0.08), transparent 24%),
            linear-gradient(180deg, rgba(24, 37, 54, 0.94) 0%, rgba(15, 23, 36, 0.98) 100%);
    }

    .leader-os-support-ticket.is-compact {
        padding: 0.8rem 0.9rem;
    }

    .leader-os-support-ticket.is-compact .leader-os-panel {
        margin-top: 0.7rem !important;
        padding: 0.8rem 0.85rem !important;
    }

    .leader-os-support-ticket-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 0.85rem;
    }

    .leader-os-support-ticket-badges {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.45rem;
        flex-wrap: wrap;
    }

    .leader-os-support-toggle {
        margin-top: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .leader-os-is-hidden {
        display: none !important;
    }

    .leader-os-support-preview {
        color: rgba(233, 241, 249, 0.92);
        font-size: 0.9rem;
        line-height: 1.55;
        margin-top: 0.55rem;
    }

    .leader-os-support-opening-message {
        color: #eef6ff;
        font-size: 0.98rem;
        line-height: 1.65;
        white-space: pre-wrap;
        background: linear-gradient(180deg, rgba(29, 44, 62, 0.92) 0%, rgba(18, 29, 42, 0.98) 100%);
        border: 1px solid rgba(126, 180, 220, 0.18);
        border-radius: 0.9rem;
        padding: 0.95rem 1rem;
        min-height: 72px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
    }

    .leader-os-support-thread-card {
        display: grid;
        gap: 1rem;
    }

    .leader-os-support-stat-open {
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.14), transparent 30%),
            linear-gradient(180deg, rgba(14, 36, 43, 0.96) 0%, rgba(9, 18, 27, 0.98) 100%);
        border-color: rgba(45, 212, 191, 0.24);
    }

    .leader-os-support-stat-answered {
        background:
            radial-gradient(circle at top right, rgba(56, 189, 248, 0.16), transparent 30%),
            linear-gradient(180deg, rgba(13, 32, 47, 0.96) 0%, rgba(9, 18, 27, 0.98) 100%);
        border-color: rgba(56, 189, 248, 0.24);
    }

    .leader-os-support-stat-watch {
        background:
            radial-gradient(circle at top right, rgba(251, 191, 36, 0.14), transparent 30%),
            linear-gradient(180deg, rgba(40, 31, 18, 0.96) 0%, rgba(18, 16, 10, 0.98) 100%);
        border-color: rgba(251, 191, 36, 0.24);
    }

    .leader-os-support-stat-action {
        background:
            radial-gradient(circle at top right, rgba(74, 222, 128, 0.14), transparent 30%),
            linear-gradient(180deg, rgba(18, 39, 24, 0.96) 0%, rgba(10, 19, 12, 0.98) 100%);
        border-color: rgba(74, 222, 128, 0.22);
    }

    .leader-os-support-trend-card {
        background:
            radial-gradient(circle at top right, rgba(96, 165, 250, 0.08), transparent 26%),
            linear-gradient(180deg, rgba(20, 31, 51, 0.92) 0%, rgba(12, 19, 33, 0.98) 100%);
        border-color: rgba(148, 163, 184, 0.18);
    }

    .leader-os-support-stat-open {
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.14), transparent 30%),
            linear-gradient(180deg, rgba(14, 36, 43, 0.96) 0%, rgba(9, 18, 27, 0.98) 100%);
        border-color: rgba(45, 212, 191, 0.24);
    }

    .leader-os-support-stat-answered {
        background:
            radial-gradient(circle at top right, rgba(56, 189, 248, 0.16), transparent 30%),
            linear-gradient(180deg, rgba(13, 32, 47, 0.96) 0%, rgba(9, 18, 27, 0.98) 100%);
        border-color: rgba(56, 189, 248, 0.24);
    }

    .leader-os-support-stat-watch {
        background:
            radial-gradient(circle at top right, rgba(251, 191, 36, 0.14), transparent 30%),
            linear-gradient(180deg, rgba(40, 31, 18, 0.96) 0%, rgba(18, 16, 10, 0.98) 100%);
        border-color: rgba(251, 191, 36, 0.24);
    }

    .leader-os-support-stat-action {
        background:
            radial-gradient(circle at top right, rgba(74, 222, 128, 0.14), transparent 30%),
            linear-gradient(180deg, rgba(18, 39, 24, 0.96) 0%, rgba(10, 19, 12, 0.98) 100%);
        border-color: rgba(74, 222, 128, 0.22);
    }

    .leader-os-support-trend-card {
        background:
            radial-gradient(circle at top right, rgba(96, 165, 250, 0.08), transparent 26%),
            linear-gradient(180deg, rgba(20, 31, 51, 0.92) 0%, rgba(12, 19, 33, 0.98) 100%);
        border-color: rgba(148, 163, 184, 0.18);
    }

    .leader-os-support-thread-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .leader-os-support-thread-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 0.85rem 1rem;
        border: 1px solid rgba(63, 224, 200, 0.22);
        border-radius: 0.95rem;
        background: linear-gradient(180deg, rgba(23, 71, 69, 0.46) 0%, rgba(19, 32, 46, 0.94) 100%);
    }

    .leader-os-support-thread-compose {
        border-top: 1px solid rgba(126, 180, 220, 0.14);
        padding-top: 1rem;
        margin-top: 0.25rem;
        border-radius: 1rem;
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.1), transparent 24%),
            linear-gradient(180deg, rgba(24, 38, 54, 0.9) 0%, rgba(15, 24, 36, 0.98) 100%);
        border: 1px solid rgba(126, 180, 220, 0.18);
        padding: 1rem;
    }

    .leader-os-support-ai-note {
        border-radius: 0.95rem;
        border: 1px solid rgba(63, 224, 200, 0.24);
        background: linear-gradient(180deg, rgba(20, 97, 98, 0.52) 0%, rgba(18, 39, 51, 0.94) 100%);
        padding: 0.85rem 0.95rem;
    }

    .leader-os-support-ai-note,
    .leader-os-support-ai-note .text-white,
    .leader-os-support-ai-note .text-muted,
    .leader-os-support-ai-note strong,
    .leader-os-support-ai-note span {
        color: #eafaf8 !important;
    }

    #leader_os_support_ticket_reply_message {
        min-height: 170px;
        border: 1px solid rgba(126, 180, 220, 0.22);
        background: linear-gradient(180deg, rgba(27, 41, 57, 0.98) 0%, rgba(18, 28, 40, 1) 100%);
        color: #f8fbff;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
    }

    #leader_os_support_ticket_reply_message:focus {
        border-color: rgba(45, 212, 191, 0.45);
        box-shadow: 0 0 0 0.2rem rgba(45, 212, 191, 0.12);
    }

    .leader-os-support-thread-compose .leader-os-field-label,
    .leader-os-support-thread-header .text-uppercase,
    .leader-os-support-rail .text-uppercase,
    .leader-os-support-main .text-uppercase {
        color: rgba(191, 211, 238, 0.82);
        letter-spacing: 0.06em;
    }

    .leader-os-support-main .h4,
    .leader-os-support-main .h5,
    .leader-os-support-rail .leader-os-link.font-weight-bold {
        color: #f8fbff;
    }

    .leader-os-support-thread-card #leader_os_support_conversation {
        display: grid;
        gap: 0.9rem;
    }

    .leader-os-support-thread-card .leader-os-conversation-item {
        margin-bottom: 0;
        border-color: rgba(126, 180, 220, 0.18);
        background: linear-gradient(180deg, rgba(31, 45, 64, 0.95) 0%, rgba(20, 31, 44, 0.99) 100%);
    }

    .leader-os-support-thread-card .leader-os-conversation-item.is-admin {
        background: linear-gradient(180deg, rgba(23, 107, 109, 0.56) 0%, rgba(18, 47, 58, 0.98) 100%);
        border-color: rgba(63, 224, 200, 0.3);
    }

    .leader-os-support-thread-card .leader-os-conversation-item strong {
        font-size: 1rem;
        color: #f8fbff;
    }

    .leader-os-support-thread-card .leader-os-conversation-message {
        font-size: 1rem;
        line-height: 1.7;
        color: #f1f5f9;
    }

    /* Custom code: FC-2026-03-31: LOS overview AI usage badges */
    .leader-os-ai-usage {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.7rem;
    }

    .leader-os-ai-usage-main {
        font-size: 0.72rem;
        letter-spacing: 0.01em;
    }

    .leader-os-ai-usage-badge {
        font-size: 0.7rem;
        padding: 0.24rem 0.5rem;
    }

    .leader-os-ai-access-meta {
        display: grid;
        gap: 0.3rem;
        margin-top: 0.7rem;
    }

    .leader-os-ai-access-panel {
        margin-top: 0.75rem;
        padding: 0.75rem 0.85rem;
        border-radius: 0.9rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(15, 23, 42, 0.48);
    }

    .leader-os-ai-access-panel.is-start {
        border-color: rgba(251, 191, 36, 0.22);
        background: linear-gradient(180deg, rgba(120, 53, 15, 0.12) 0%, rgba(15, 23, 42, 0.4) 100%);
    }

    .leader-os-ai-access-panel.is-active {
        border-color: rgba(96, 165, 250, 0.24);
        background: linear-gradient(180deg, rgba(30, 64, 175, 0.12) 0%, rgba(15, 23, 42, 0.4) 100%);
    }

    .leader-os-ai-access-panel.is-vip {
        border-color: rgba(34, 197, 94, 0.24);
        background: linear-gradient(180deg, rgba(22, 101, 52, 0.14) 0%, rgba(15, 23, 42, 0.42) 100%);
    }

    .leader-os-ai-access-panel.is-locked {
        border-color: rgba(75, 85, 99, 0.24);
        background: linear-gradient(180deg, rgba(31, 41, 55, 0.18) 0%, rgba(15, 23, 42, 0.4) 100%);
    }

    .leader-os-ai-access-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.7rem;
    }

    .leader-os-ai-access-actions form {
        margin: 0;
    }

    .leader-os-action-button.is-primary {
        border-color: rgba(94, 200, 255, 0.42);
        background: linear-gradient(180deg, rgba(45, 130, 170, 0.72) 0%, rgba(33, 92, 126, 0.96) 100%);
    }

    .leader-os-action-button.is-success {
        border-color: rgba(107, 227, 139, 0.36);
        background: linear-gradient(180deg, rgba(42, 137, 87, 0.72) 0%, rgba(28, 95, 61, 0.94) 100%);
    }

    .leader-os-signal-meter {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        margin-top: 0.3rem;
    }

    .leader-os-signal-bar {
        flex: 1;
        height: 0.4rem;
        border-radius: 999px;
        background: rgba(51, 65, 85, 0.72);
        overflow: hidden;
    }

    .leader-os-signal-bar > span {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(96, 165, 250, 0.95) 0%, rgba(52, 211, 153, 0.95) 100%);
    }

    .leader-os-signal-thresholds {
        font-size: 0.68rem;
        color: rgba(191, 211, 238, 0.72);
        letter-spacing: 0.01em;
    }

    .leader-os-anomaly-badge {
        font-size: 0.7rem;
        padding: 0.24rem 0.5rem;
    }
    /* /Custom code: FC-2026-03-31 */

    .leader-os-alert-list {
        display: grid;
        gap: 0.75rem;
    }

    .leader-os-alert-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    }

    .leader-os-alert-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .leader-os-coaching-list {
        display: grid;
        gap: 0.75rem;
    }

    .leader-os-coaching-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    }

    .leader-os-coaching-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .leader-os-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .leader-os-pagination-links {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Custom code: FC-2026-03-31: Leader OS numeric pagination styling */
    .leader-os-page-link {
        min-width: 2.2rem;
        justify-content: center;
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.04);
    }

    .leader-os-page-link:hover,
    .leader-os-page-link:focus {
        color: #ffffff;
        border-color: rgba(124, 200, 255, 0.55);
        background: rgba(124, 200, 255, 0.14);
    }

    .leader-os-page-link.active {
        background: rgba(30, 64, 175, 0.32);
        border-color: rgba(96, 165, 250, 0.45);
        color: #ffffff;
    }

    .leader-os-mini-chart {
        display: grid;
        gap: 0.55rem;
    }

    .leader-os-mini-chart-row {
        display: grid;
        grid-template-columns: 68px minmax(0, 1fr) 44px;
        gap: 0.75rem;
        align-items: center;
    }

    .leader-os-mini-chart-label,
    .leader-os-mini-chart-value {
        font-size: 0.78rem;
        color: rgba(191, 211, 238, 0.82);
    }

    .leader-os-mini-chart-track {
        position: relative;
        height: 0.65rem;
        border-radius: 999px;
        background: rgba(30, 41, 59, 0.86);
        overflow: hidden;
    }

    .leader-os-mini-chart-fill {
        position: absolute;
        inset: 0 auto 0 0;
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(56, 189, 248, 0.78) 0%, rgba(96, 165, 250, 0.92) 100%);
    }

    .leader-os-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .leader-os-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    @media (max-width: 1199.98px) {
        .leader-os-support-workspace {
            grid-template-columns: 1fr;
        }

        .leader-os-trend-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .leader-os-trend-summary {
            grid-template-columns: 1fr;
        }
    }

    .leader-os-leaderboard-list {
        display: grid;
        gap: 0.7rem;
    }

    .leader-os-leaderboard-item {
        border-top: 1px solid rgba(148, 163, 184, 0.1);
        padding-top: 0.7rem;
    }

    .leader-os-trend-panel {
        padding: 1.15rem;
    }

    .leader-os-trend-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .leader-os-trend-periods {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.28rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(15, 23, 42, 0.42);
    }

    .leader-os-trend-period {
        border: 0;
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        background: transparent;
        color: rgba(226, 232, 240, 0.78);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .leader-os-trend-period.is-active {
        background: linear-gradient(135deg, rgba(44, 203, 182, 0.28) 0%, rgba(65, 143, 245, 0.24) 100%);
        color: #eff9ff;
        box-shadow: inset 0 0 0 1px rgba(129, 197, 255, 0.25);
    }

    .leader-os-trend-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.7rem 1rem;
    }

    .leader-os-trend-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: rgba(225, 235, 247, 0.82);
        font-size: 0.82rem;
        font-weight: 600;
    }

    .leader-os-trend-dot {
        width: 0.72rem;
        height: 0.72rem;
        border-radius: 999px;
        box-shadow: 0 0 0 0.18rem rgba(255, 255, 255, 0.03);
    }

    .leader-os-trend-dot.is-clicks { background: #57e389; }
    .leader-os-trend-dot.is-registrations { background: #68b7ff; }
    .leader-os-trend-dot.is-leads { background: #f8d060; }
    .leader-os-trend-dot.is-blog { background: #3fe0c8; }

    .leader-os-trend-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
        margin-bottom: 1rem;
    }

    .leader-os-trend-summary-card {
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: linear-gradient(180deg, rgba(20, 30, 44, 0.72) 0%, rgba(12, 20, 31, 0.82) 100%);
        padding: 0.9rem 1rem;
    }

    .leader-os-trend-summary-card.is-clickable {
        cursor: pointer;
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .leader-os-trend-summary-card.is-clickable:hover {
        transform: translateY(-1px);
        border-color: rgba(129, 197, 255, 0.28);
        box-shadow: 0 0.9rem 1.8rem rgba(2, 6, 23, 0.16);
    }

    .leader-os-trend-summary-label {
        color: rgba(200, 215, 233, 0.72);
        font-size: 0.75rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }

    .leader-os-trend-summary-value {
        color: #f7fbff;
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1;
    }

    .leader-os-trend-chart-wrap {
        border-radius: 1.05rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background:
            radial-gradient(circle at top right, rgba(94, 200, 255, 0.09), transparent 30%),
            linear-gradient(180deg, rgba(16, 25, 38, 0.82) 0%, rgba(9, 16, 27, 0.92) 100%);
        padding: 1rem 1rem 0.75rem;
    }

    .leader-os-trend-chart {
        width: 100%;
        height: auto;
        display: block;
    }

    .leader-os-trend-axis-text {
        fill: rgba(205, 221, 241, 0.64);
        font-size: 11px;
        font-family: inherit;
    }

    .leader-os-trend-grid-line {
        stroke: rgba(148, 163, 184, 0.12);
        stroke-width: 1;
    }

    .leader-os-trend-series-line {
        fill: none;
        stroke-width: 3;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .leader-os-trend-series-line.is-clicks { stroke: #57e389; }
    .leader-os-trend-series-line.is-registrations { stroke: #68b7ff; }
    .leader-os-trend-series-line.is-leads { stroke: #f8d060; }
    .leader-os-trend-series-line.is-blog { stroke: #3fe0c8; }

    .leader-os-trend-series-dot {
        stroke: rgba(10, 18, 30, 0.9);
        stroke-width: 2;
    }

    .leader-os-trend-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-top: 0.75rem;
        flex-wrap: wrap;
    }

    .leader-os-trend-note {
        color: rgba(205, 221, 241, 0.7);
        font-size: 0.82rem;
    }

    .leader-os-status-chart-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .leader-os-status-chart {
        display: grid;
        gap: 0.9rem;
    }

    .leader-os-status-chart-row {
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr) 48px;
        gap: 0.8rem;
        align-items: center;
    }

    .leader-os-status-chart-label {
        color: rgba(223, 233, 244, 0.82);
        font-size: 0.82rem;
        font-weight: 600;
    }

    .leader-os-status-chart-track {
        position: relative;
        height: 0.9rem;
        border-radius: 999px;
        background: rgba(20, 33, 49, 0.88);
        overflow: hidden;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
    }

    .leader-os-status-chart-fill {
        position: absolute;
        inset: 0 auto 0 0;
        border-radius: 999px;
    }

    .leader-os-status-chart-fill.is-inactive { background: linear-gradient(90deg, rgba(97, 114, 137, 0.75) 0%, rgba(126, 141, 163, 0.94) 100%); }
    .leader-os-status-chart-fill.is-stable { background: linear-gradient(90deg, rgba(74, 222, 128, 0.7) 0%, rgba(94, 234, 212, 0.92) 100%); }
    .leader-os-status-chart-fill.is-rising { background: linear-gradient(90deg, rgba(59, 130, 246, 0.8) 0%, rgba(96, 165, 250, 0.96) 100%); }
    .leader-os-status-chart-fill.is-high_potential { background: linear-gradient(90deg, rgba(250, 204, 21, 0.78) 0%, rgba(251, 191, 36, 0.96) 100%); }
    .leader-os-status-chart-fill.is-risk { background: linear-gradient(90deg, rgba(248, 113, 113, 0.8) 0%, rgba(251, 146, 60, 0.94) 100%); }

    .leader-os-status-chart-value {
        color: #eef7ff;
        font-size: 0.86rem;
        font-weight: 700;
        text-align: right;
    }

    .leader-os-status-chart-note {
        margin-top: 0.95rem;
        color: rgba(205, 221, 241, 0.7);
        font-size: 0.8rem;
    }

    .leader-os-status-graph-wrap {
        margin-top: 1rem;
        border-top: 1px solid rgba(148, 163, 184, 0.12);
        padding-top: 1rem;
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(18, 28, 41, 0.54) 0%, rgba(12, 20, 31, 0.18) 100%);
    }

    .leader-os-status-graph {
        width: 100%;
        height: auto;
        display: block;
    }

    .leader-os-status-graph-axis {
        fill: rgba(221, 233, 247, 0.78);
        font-size: 12px;
        font-family: inherit;
    }

    .leader-os-status-graph-value {
        fill: #eff8ff;
        font-size: 13px;
        font-weight: 700;
        font-family: inherit;
    }

    .leader-os-status-graph-label {
        fill: rgba(221, 233, 247, 0.82);
        font-size: 12px;
        font-weight: 600;
        font-family: inherit;
    }

    .leader-os-status-graph-grid {
        stroke: rgba(148, 163, 184, 0.12);
        stroke-width: 1;
    }

    .leader-os-heatmap {
        overflow-x: auto;
    }

    .leader-os-heatmap-table {
        width: 100%;
        min-width: 820px;
        border-collapse: separate;
        border-spacing: 0.32rem;
    }

    .leader-os-heatmap-table th {
        font-size: 0.72rem;
        color: rgba(191, 211, 238, 0.72);
        font-weight: 600;
        white-space: nowrap;
    }

    .leader-os-heatmap-day {
        padding-right: 0.5rem;
    }

    .leader-os-heatmap-cell {
        width: 28px;
        height: 28px;
        border-radius: 0.6rem;
        border: 1px solid rgba(148, 163, 184, 0.08);
        background: rgba(15, 23, 42, 0.65);
        position: relative;
    }

    .leader-os-heatmap-cell::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: rgba(56, 189, 248, var(--heat-alpha, 0));
    }

    .leader-os-matrix-table td,
    .leader-os-matrix-table th {
        white-space: nowrap;
    }

    .leader-os-modal .modal-dialog {
        max-width: 780px;
    }

    .leader-os-modal .modal-content {
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 1.1rem;
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.98) 0%, rgba(10, 15, 28, 0.98) 100%);
        color: #ecf3ff;
        box-shadow: 0 1.5rem 3rem rgba(2, 6, 23, 0.34);
    }

    .leader-os-modal .modal-header,
    .leader-os-modal .modal-footer {
        border-color: rgba(148, 163, 184, 0.12);
    }

    .leader-os-modal-list {
        display: grid;
        gap: 0.85rem;
        max-height: 65vh;
        overflow-y: auto;
        padding-right: 0.25rem;
    }

    .leader-os-modal-item {
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 0.95rem;
        background: rgba(11, 18, 32, 0.76);
        padding: 0.9rem 1rem;
    }

    .leader-os-modal-item strong {
        font-size: 1rem;
    }

    .leader-os-modal-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        padding: 0.9rem 1rem;
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 0.95rem;
        background: rgba(9, 15, 28, 0.82);
    }

    .leader-os-modal-summary-label {
        color: rgba(191, 211, 238, 0.7);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .leader-os-modal-summary-value {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #f8fbff;
    }

    .leader-os-modal-item-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.4rem;
    }

    .leader-os-modal-item-metric {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 88px;
        padding: 0.35rem 0.6rem;
        border-radius: 999px;
        background: rgba(30, 64, 175, 0.22);
        border: 1px solid rgba(96, 165, 250, 0.18);
        color: #dbeafe;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .leader-os-modal-open {
        color: #8dd5ff;
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .leader-os-modal-open:hover {
        color: #d7efff;
        text-decoration: none;
    }

    .leader-os-modal-empty {
        padding: 1rem;
        border-radius: 0.95rem;
        border: 1px dashed rgba(148, 163, 184, 0.16);
        color: rgba(191, 211, 238, 0.72);
        text-align: center;
    }

    .leader-os-tabs {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .leader-os-tab {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.9rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        color: #dceaff;
        text-decoration: none;
        background: rgba(15, 23, 42, 0.72);
    }

    .leader-os-tab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.35rem;
        height: 1.35rem;
        padding: 0 0.38rem;
        border-radius: 999px;
        background: rgba(220, 38, 38, 0.92);
        color: #ffffff;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1;
        box-shadow: 0 0 0 1px rgba(248, 113, 113, 0.22);
    }

    .leader-os-tab:hover,
    .leader-os-tab:focus {
        color: #ffffff;
        text-decoration: none;
        border-color: rgba(124, 200, 255, 0.55);
    }

    .leader-os-tab.active {
        border-color: rgba(96, 165, 250, 0.36);
        background: rgba(30, 64, 175, 0.24);
        color: #ffffff;
    }
    /* /Custom code: FC-2026-03-31 */

    @media (max-width: 767.98px) {
        .leader-os-shell .card-body {
            padding: 0.95rem;
        }

        .leader-os-toolbar {
            grid-template-columns: 1fr;
        }

        .leader-os-grid-2,
        .leader-os-grid-3,
        .leader-os-ops-grid,
        .leader-os-ops-meta {
            grid-template-columns: 1fr;
        }

        .leader-os-ops-item-header {
            flex-direction: column;
        }
    }
</style>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
    <div>
        <h1 class="h3 mb-1"><?= l('admin_leader_operating_system.header') ?></h1>
        <p class="text-muted mb-0"><?= l('admin_leader_operating_system.subheader') ?></p>
    </div>

    <div class="mt-3 mt-lg-0 d-flex align-items-center">
        <span class="leader-os-pill mr-2"><?= l('admin_leader_operating_system.overview_badge') ?></span>

        <form action="<?= url('admin/leader-operating-system') ?>" method="get" class="mb-0">
            <input type="hidden" name="search" value="<?= input_clean($data->search_query) ?>" />
            <input type="hidden" name="status" value="<?= $data->selected_status ?>" />
            <input type="hidden" name="ai_status" value="<?= $data->selected_ai_status ?>" />
            <input type="hidden" name="anomaly_status" value="<?= $data->selected_anomaly_status ?>" />
            <input type="hidden" name="fraud_status" value="<?= $data->selected_fraud_status ?>" />
            <input type="hidden" name="sort" value="<?= $data->selected_sort ?>" />
            <input type="hidden" name="tab" value="<?= $data->selected_tab ?>" />
            <select name="period" class="custom-select custom-select-sm" onchange="this.form.submit()">
                <?php foreach($data->period_options as $period_option): ?>
                    <option value="<?= $period_option ?>" <?= $data->selected_period === $period_option ? 'selected="selected"' : null ?>><?= l('admin_leader_operating_system.period_' . $period_option) ?></option>
                <?php endforeach ?>
            </select>
        </form>
    </div>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<?php
$leader_os_state_query = [
    'period' => $data->selected_period,
    'search' => $data->search_query,
    'status' => $data->selected_status,
    'ai_status' => $data->selected_ai_status,
    'anomaly_status' => $data->selected_anomaly_status,
    'fraud_status' => $data->selected_fraud_status,
    'sort' => $data->selected_sort,
    'tab' => $data->selected_tab,
    'support_ticket_id' => $data->selected_support_ticket_id ?? 0,
    'page' => $data->overview['pagination']['page'] ?? 1,
];
$leader_os_action_url = url('admin/leader-operating-system?' . http_build_query($leader_os_state_query));
?>

<?php
$tab_labels = [
    'overview' => 'Pregled',
    'operations' => 'Operativa',
    'collaborators' => 'Suradnici',
    'analytics' => 'Analitika',
    'ai' => 'AI navike',
    'fraud' => 'Fraud i anomalije',
    'coaching' => 'Coaching',
    'support' => 'Podrška',
];

$fraud_filter_labels = [
    'all' => 'Sav fraud signal',
    'clean' => 'Clean',
    'watch' => 'Fraud watch',
    'high' => 'Fraud high',
];

$sort_labels = [
    'leader_os' => l('admin_leader_operating_system.sort.leader_os'),
    'app_quality' => l('admin_leader_operating_system.sort.app_quality'),
    'fraud' => 'Fraud signal',
    'shop_clicks' => l('admin_leader_operating_system.sort.shop_clicks'),
    'growth' => l('admin_leader_operating_system.sort.growth'),
    'registrations' => l('admin_leader_operating_system.sort.registrations'),
    'risk' => l('admin_leader_operating_system.sort.risk'),
    'country' => l('admin_leader_operating_system.sort.country'),
    'source' => l('admin_leader_operating_system.sort.source'),
    'last_click' => l('admin_leader_operating_system.sort.last_click'),
];

$render_mini_chart = static function(array $items, int $max_total = 0, string $value_key = 'total') {
    $fallback_max = !empty($items) ? max(array_map(static fn($item) => (int) ($item[$value_key] ?? 0), $items)) : 0;
    $max_total = max(1, $max_total, $fallback_max);
    ob_start();
    ?>
    <div class="leader-os-mini-chart">
        <?php foreach($items as $item): ?>
            <?php $value = (int) ($item[$value_key] ?? 0); ?>
            <?php $width = min(100, round(($value / $max_total) * 100, 1)); ?>
            <div class="leader-os-mini-chart-row">
                <div class="leader-os-mini-chart-label"><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="leader-os-mini-chart-track"><span class="leader-os-mini-chart-fill" style="width: <?= htmlspecialchars((string) $width, ENT_QUOTES, 'UTF-8') ?>%"></span></div>
                <div class="leader-os-mini-chart-value text-right"><?= nr($value) ?></div>
            </div>
        <?php endforeach ?>
    </div>
    <?php
    return ob_get_clean();
};

$render_kpi_card = static function(string $key, string $label, $value, string $hint = '', ?string $chip = null) use ($data) {
    $drilldown = $data->overview['kpi_drilldowns'][$key] ?? null;
    $has_drilldown = is_array($drilldown);
    $payload = $has_drilldown ? htmlspecialchars(json_encode([
        'title' => (string) ($drilldown['title'] ?? $label),
        'summary_label' => (string) $label,
        'summary_value' => (string) ($drilldown['signal_total_display'] ?? nr($value)),
        'summary_note' => (string) ($drilldown['summary_note'] ?? ('Suradnika u signalu: ' . nr(count((array) ($drilldown['items'] ?? []))))),
        'items' => array_values($drilldown['items'] ?? []),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') : '';

    ob_start();
    ?>
    <div class="leader-os-kpi <?= $has_drilldown ? 'is-clickable' : '' ?>" <?= $has_drilldown ? 'role="button" tabindex="0" data-toggle="modal" data-target="#leader_os_drilldown_modal" data-drilldown="' . $payload . '"' : '' ?>>
        <div class="leader-os-kpi-topline">
            <div class="leader-os-kpi-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if($chip !== null && $chip !== ''): ?>
                <span class="leader-os-kpi-chip"><?= htmlspecialchars($chip, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif ?>
        </div>
        <div class="leader-os-kpi-value-wrap">
            <div class="leader-os-kpi-value"><?= nr($value) ?></div>
            <?php if($has_drilldown): ?>
                <span class="leader-os-kpi-detail-link">Otvori popis</span>
            <?php endif ?>
        </div>
        <?php if($hint !== ''): ?>
            <div class="leader-os-kpi-hint"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif($has_drilldown): ?>
            <div class="leader-os-kpi-hint">Klikni za popis suradnika</div>
        <?php endif ?>
    </div>
    <?php
    return ob_get_clean();
};

$support_tab_badge_total = (int) (($data->overview['support_center']['totals']['outstanding_total'] ?? 0));
$operations_tab_badge_total = (int) (($data->operations['totals']['pending_approvals'] ?? 0) + ($data->operations['totals']['card_queue'] ?? 0));
?>

<div class="leader-os-tabs">
    <?php foreach(($data->tab_options ?? []) as $tab_option): ?>
        <?php $tab_query = http_build_query(array_merge($leader_os_state_query, ['tab' => $tab_option, 'page' => 1])); ?>
        <a href="<?= url('admin/leader-operating-system?' . $tab_query) ?>" class="leader-os-tab <?= ($data->selected_tab ?? 'overview') === $tab_option ? 'active' : null ?>">
            <?= htmlspecialchars((string) ($tab_labels[$tab_option] ?? ucfirst($tab_option)), ENT_QUOTES, 'UTF-8') ?>
            <?php if($tab_option === 'operations' && $operations_tab_badge_total > 0): ?>
                <span class="leader-os-tab-badge"><?= nr($operations_tab_badge_total) ?></span>
            <?php endif ?>
            <?php if($tab_option === 'support' && $support_tab_badge_total > 0): ?>
                <span class="leader-os-tab-badge"><?= nr($support_tab_badge_total) ?></span>
            <?php endif ?>
        </a>
    <?php endforeach ?>
</div>

<?php if(($data->selected_tab ?? 'overview') === 'operations'): ?>
<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start mb-4">
            <div>
                <div class="text-uppercase small text-muted mb-2">LOS operativa</div>
                <h2 class="h4 mb-2">Odobrenja novih suradnika i NFC kartice</h2>
                <p class="text-muted mb-0">Ovdje vodiš ručno odobrenje pristupa, vidiš tko je aktivirao PRO ili PRO trial i preuzimaš sve što treba za izradu i slanje poklon NFC kartice.</p>
            </div>

            <div class="mt-3 mt-lg-0 d-flex flex-wrap" style="gap:0.55rem;">
                <span class="leader-os-pill">Registrirani: <?= nr((int) ($data->operations['totals']['registered'] ?? 0)) ?></span>
                <span class="leader-os-pill">Čeka odobrenje: <?= nr((int) ($data->operations['totals']['pending_approvals'] ?? 0)) ?></span>
                <span class="leader-os-pill">Kartice za obradu: <?= nr((int) ($data->operations['totals']['card_queue'] ?? 0)) ?></span>
            </div>
        </div>

        <div class="leader-os-inline-note mb-4">
            LOS sada odvaja dva ključna toka: prvo odobravaš pristup novom suradniku i šalješ email dobrodošlice, a zatim, tek kad aktivira Forever Pro ili 30-dnevni PRO trial, suradnik ulazi u red za QR, pismo i slanje poklon NFC kartice.
        </div>

        <div class="row leader-os-ops-kpis mb-3">
            <div class="col-12 col-lg-4 mb-3">
                <?= $render_kpi_card('ops_registered', 'Registrirani korisnici', (int) ($data->operations['totals']['registered'] ?? 0), 'Ukupan broj korisnika koji su vidljivi u ovom operativnom pregledu', 'LOS') ?>
            </div>
            <div class="col-12 col-lg-4 mb-3">
                <?= $render_kpi_card('ops_pending', 'Nova odobrenja', (int) ($data->operations['totals']['pending_approvals'] ?? 0), 'Novi suradnici koji još čekaju ručno odobrenje pristupa', 'Approve') ?>
            </div>
            <div class="col-12 col-lg-4 mb-3">
                <?= $render_kpi_card('ops_cards', 'Kartice za slanje', (int) ($data->operations['totals']['card_queue'] ?? 0), 'Aktivirani PRO i PRO trial suradnici koji trebaju NFC karticu', 'NFC') ?>
            </div>
        </div>

        <div class="leader-os-ops-grid">
            <div class="leader-os-panel">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:0.75rem;">
                    <div>
                        <div class="text-uppercase small text-muted mb-2">Prvi korak</div>
                        <h3 class="h5 mb-1">Novi suradnici za odobrenje</h3>
                        <div class="text-muted small">Kad odobriš pristup, suradnik dobiva poseban email da sada može koristiti FCC i svoju izrađenu aplikaciju.</div>
                    </div>
                    <span class="leader-os-status-badge status-warning"><?= nr((int) ($data->operations['totals']['pending_approvals'] ?? 0)) ?> čekaju</span>
                </div>

                <?php if(empty($data->operations['pending_rows'])): ?>
                    <div class="leader-os-ops-empty">Trenutno nema novih suradnika koji čekaju odobrenje.</div>
                <?php else: ?>
                    <div class="leader-os-ops-list">
                        <?php foreach(($data->operations['pending_rows'] ?? []) as $row): ?>
                            <div class="leader-os-ops-item">
                                <div class="leader-os-ops-item-header">
                                    <div>
                                        <div class="leader-os-ops-title"><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="leader-os-ops-email"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>

                                    <div class="d-flex flex-wrap justify-content-lg-end" style="gap:0.45rem;">
                                        <span class="leader-os-status-badge status-warning"><?= htmlspecialchars((string) ($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="leader-os-status-badge status-info"><?= htmlspecialchars((string) ($row['plan_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>

                                <div class="leader-os-ops-meta">
                                    <div>
                                        <span class="leader-os-ops-meta-label">Registracija</span>
                                        <div class="leader-os-ops-meta-value"><?= !empty($row['datetime']) ? \Altum\Date::get($row['datetime'], 2) : '-' ?></div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label">Glavna aplikacija</span>
                                        <div class="leader-os-ops-meta-value">
                                            <?php if(!empty($row['main_biolink_url'])): ?>
                                                <a href="<?= $row['main_biolink_url'] ?>" target="_blank" rel="noopener" class="leader-os-link"><?= htmlspecialchars((string) $row['main_biolink_url'], ENT_QUOTES, 'UTF-8') ?></a>
                                            <?php else: ?>
                                                Još nije povezana
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label">Kontakt za karticu</span>
                                        <div class="leader-os-ops-meta-value">
                                            <?= !empty($row['phone']) ? htmlspecialchars((string) $row['phone'], ENT_QUOTES, 'UTF-8') : 'Telefon nije upisan' ?>
                                            <?php if(!empty($row['forever_id'])): ?>
                                                <br><span class="text-muted">Forever ID: <?= htmlspecialchars((string) $row['forever_id'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label">Adresa</span>
                                        <div class="leader-os-ops-meta-value leader-os-ops-address"><?= !empty($row['address_lines']) ? htmlspecialchars(implode("\n", (array) $row['address_lines']), ENT_QUOTES, 'UTF-8') : 'Adresa još nije upisana' ?></div>
                                    </div>
                                </div>

                                <div class="leader-os-ops-actions">
                                    <form action="<?= $leader_os_action_url ?>" method="post" class="mb-0 d-inline-block">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" name="user_id" value="<?= (int) ($row['user_id'] ?? 0) ?>" />
                                        <button type="submit" name="los_approve_access" value="1" class="btn btn-sm leader-os-action-button">
                                            <i class="fas fa-check-circle mr-1"></i> Odobri pristup
                                        </button>
                                    </form>

                                    <form action="<?= $leader_os_action_url ?>" method="post" class="mb-0 d-inline-block" onsubmit="return confirm('Jeste li sigurni da želite odbiti pristup? Korisniku će se prvo poslati email obavijest, a zatim će račun biti trajno uklonjen s Forever Card Cluba.');">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" name="user_id" value="<?= (int) ($row['user_id'] ?? 0) ?>" />
                                        <button type="submit" name="los_reject_access" value="1" class="btn btn-sm leader-os-action-button-danger">
                                            <i class="fas fa-times-circle mr-1"></i> Pristup odbijen
                                        </button>
                                    </form>

                                    <a href="<?= $row['admin_user_update_url'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-user-cog mr-1"></i> Uredi korisnika
                                    </a>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>

            <div class="leader-os-panel">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:0.75rem;">
                    <div>
                        <div class="text-uppercase small text-muted mb-2">Drugi korak</div>
                        <h3 class="h5 mb-1">NFC kartice za obradu i slanje</h3>
                        <div class="text-muted small">U ovu listu automatski ulaze samo suradnici koji su aktivirali Forever Pro ili 30-dnevni PRO trial i sada trebaju QR, pismo i slanje kartice.</div>
                    </div>
                    <span class="leader-os-status-badge status-info"><?= nr((int) ($data->operations['totals']['card_queue'] ?? 0)) ?> u redu</span>
                </div>

                <?php if(empty($data->operations['card_queue_rows'])): ?>
                    <div class="leader-os-ops-empty">Trenutno nema aktiviranih PRO suradnika koji čekaju obradu kartice.</div>
                <?php else: ?>
                    <div class="leader-os-ops-list">
                        <?php foreach(($data->operations['card_queue_rows'] ?? []) as $row): ?>
                            <div class="leader-os-ops-item">
                                <div class="leader-os-ops-item-header">
                                    <div>
                                        <div class="leader-os-ops-title"><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="leader-os-ops-email"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>

                                    <div class="d-flex flex-wrap justify-content-lg-end" style="gap:0.45rem;">
                                        <span class="leader-os-status-badge status-success"><?= htmlspecialchars((string) ($row['plan_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="leader-os-status-badge <?= !empty($row['is_trial_activation']) ? 'status-warning' : 'status-info' ?>">
                                            <?= !empty($row['is_trial_activation']) ? 'PRO trial' : 'PRO aktivacija' ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="leader-os-ops-meta">
                                    <div>
                                        <span class="leader-os-ops-meta-label">Kartica otvorena</span>
                                        <div class="leader-os-ops-meta-value"><?= !empty($row['card_requested_at']) ? \Altum\Date::get($row['card_requested_at'], 2) : (!empty($row['datetime']) ? \Altum\Date::get($row['datetime'], 2) : '-') ?></div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label">Glavna aplikacija</span>
                                        <div class="leader-os-ops-meta-value">
                                            <?php if(!empty($row['main_biolink_url'])): ?>
                                                <a href="<?= $row['main_biolink_url'] ?>" target="_blank" rel="noopener" class="leader-os-link"><?= htmlspecialchars((string) $row['main_biolink_url'], ENT_QUOTES, 'UTF-8') ?></a>
                                            <?php else: ?>
                                                Još nije povezana
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label">Kontakt i ID</span>
                                        <div class="leader-os-ops-meta-value">
                                            <?= !empty($row['phone']) ? htmlspecialchars((string) $row['phone'], ENT_QUOTES, 'UTF-8') : 'Telefon nije upisan' ?>
                                            <?php if(!empty($row['forever_id'])): ?>
                                                <br><span class="text-muted">Forever ID: <?= htmlspecialchars((string) $row['forever_id'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label">Adresa za slanje</span>
                                        <div class="leader-os-ops-meta-value leader-os-ops-address"><?= !empty($row['address_lines']) ? htmlspecialchars(implode("\n", (array) $row['address_lines']), ENT_QUOTES, 'UTF-8') : 'Adresa još nije upisana' ?></div>
                                    </div>
                                </div>

                                <div class="leader-os-ops-helper small mb-3">Preuzmi gotove datoteke ili otvori ispis. Za QR karticu odaberi printer za kartice, a za pismo klasičan A4 printer.</div>

                                <div class="leader-os-ops-actions">
                                    <a href="<?= $row['qr_download_url'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-qrcode mr-1"></i> Preuzmi QR kod
                                    </a>

                                    <a href="<?= $row['qr_download_url'] . '&mode=print' ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-print mr-1"></i> Ispiši QR karticu
                                    </a>

                                    <a href="<?= $row['letter_download_url'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-file-pdf mr-1"></i> Preuzmi pismo
                                    </a>

                                    <a href="<?= $row['letter_download_url'] . '&mode=print' ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-print mr-1"></i> Ispiši pismo
                                    </a>

                                    <a href="<?= $row['admin_user_update_url'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-user-cog mr-1"></i> Uredi korisnika
                                    </a>

                                    <form action="<?= $leader_os_action_url ?>" method="post" class="mb-0 d-inline-block">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" name="user_id" value="<?= (int) ($row['user_id'] ?? 0) ?>" />
                                        <button type="submit" name="los_mark_card_sent" value="1" class="btn btn-sm leader-os-action-button">
                                            <i class="fas fa-paper-plane mr-1"></i> Označi poslano
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <div class="leader-os-panel mt-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:0.75rem;">
                <div>
                    <div class="text-uppercase small text-muted mb-2">Pregled registracija</div>
                    <h3 class="h5 mb-1">Nedavno registrirani suradnici</h3>
                    <div class="text-muted small">Brzi pregled svih novih registracija, plana, statusa i glavne aplikacije bez izlaska iz LOS-a.</div>
                </div>
                <span class="leader-os-status-badge status-dark"><?= nr(count((array) ($data->operations['recent_rows'] ?? []))) ?> prikazano</span>
            </div>

            <div class="leader-os-table leader-os-ops-recent-table table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>Suradnik</th>
                        <th>Status</th>
                        <th>Plan</th>
                        <th>Glavna aplikacija</th>
                        <th class="text-right">Akcija</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach(($data->operations['recent_rows'] ?? []) as $row): ?>
                        <tr>
                            <td>
                                <div class="font-weight-bold"><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td>
                                <span class="leader-os-status-badge <?= (int) ($row['status'] ?? 0) === 1 ? 'status-success' : ((int) ($row['status'] ?? 0) === 2 ? 'status-dark' : 'status-warning') ?>">
                                    <?= htmlspecialchars((string) ($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <div><?= htmlspecialchars((string) ($row['plan_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small"><?= !empty($row['datetime']) ? \Altum\Date::get($row['datetime'], 2) : '-' ?></div>
                            </td>
                            <td>
                                <?php if(!empty($row['main_biolink_url'])): ?>
                                    <a href="<?= $row['main_biolink_url'] ?>" target="_blank" rel="noopener" class="leader-os-link"><?= htmlspecialchars((string) $row['main_biolink_url'], ENT_QUOTES, 'UTF-8') ?></a>
                                <?php else: ?>
                                    <span class="text-muted">Još nije povezana</span>
                                <?php endif ?>
                            </td>
                            <td class="text-right">
                                <div class="d-flex justify-content-end flex-wrap" style="gap:0.45rem;">
                                    <?php if(!empty($row['is_active_pro'])): ?>
                                        <a href="<?= $row['qr_download_url'] ?>" class="btn btn-sm btn-outline-light">
                                            <i class="fas fa-qrcode mr-1"></i> QR
                                        </a>
                                        <a href="<?= $row['letter_download_url'] . '&mode=print' ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">
                                            <i class="fas fa-file-pdf mr-1"></i> Pismo
                                        </a>
                                    <?php endif ?>

                                    <a href="<?= $row['admin_user_update_url'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-user-cog mr-1"></i> Otvori
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif ?>

<?php if(in_array(($data->selected_tab ?? 'overview'), ['overview', 'analytics', 'ai'], true)): ?>
<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start mb-4">
            <div>
                <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.section_overview') ?></div>
                <h2 class="h4 mb-2"><?= l('admin_leader_operating_system.phase_title') ?></h2>
                <p class="text-muted mb-0"><?= l('admin_leader_operating_system.phase_description') ?></p>
            </div>

            <?php if(!empty($data->overview['rows'][0]['detail_url'] ?? null)): ?>
                <a href="<?= $data->overview['rows'][0]['detail_url'] ?>" class="btn btn-outline-light btn-sm mt-3 mt-lg-0 leader-os-link">
                    <?= l('admin_leader_operating_system.open_detail_demo') ?>
                </a>
            <?php endif ?>
        </div>

        <?php if(($data->selected_tab ?? 'overview') === 'overview'): ?>
            <div class="leader-os-panel mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:1rem;">
                    <div>
                        <div class="text-uppercase small text-muted mb-2">Executive summary</div>
                        <h3 class="h5 mb-1"><?= htmlspecialchars((string) ($data->overview['executive_summary']['headline'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="text-muted small"><?= htmlspecialchars((string) ($data->overview['executive_summary']['subheadline'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <span class="leader-os-status-badge status-info"><?= htmlspecialchars((string) ($data->selected_period ?? '30d'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="row mt-3">
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                        <div class="text-muted small mb-1">Top tržište / izvor</div>
                        <div><strong><?= htmlspecialchars((string) ($data->overview['executive_summary']['top_country'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong> · <?= htmlspecialchars((string) ($data->overview['executive_summary']['top_source'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                        <div class="text-muted small mb-1">Top vrijeme aktivnosti</div>
                        <div><strong><?= htmlspecialchars((string) ($data->overview['executive_summary']['top_hour'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="text-muted small mb-1">Team focus / friction</div>
                        <div><strong><?= htmlspecialchars((string) ($data->overview['executive_summary']['focus_term'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong> · <?= htmlspecialchars((string) ($data->overview['executive_summary']['friction_term'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <div class="row">
            <div class="col-12 col-lg-4 mb-3">
                <?= $render_kpi_card('all_collaborators', l('admin_leader_operating_system.placeholder_kpi_1'), $data->overview['totals']['all_collaborators'] ?? 0, 'Ukupan roster u odabranom periodu', 'Team') ?>
            </div>

            <div class="col-12 col-lg-4 mb-3">
                <?= $render_kpi_card('qualified', l('admin_leader_operating_system.kpi_qualified'), $data->overview['totals']['qualified'] ?? 0, 'Suradnici s dokazanim signalom kvalitete', 'Signal') ?>
            </div>

            <div class="col-12 col-lg-4 mb-3">
                <?= $render_kpi_card('rising', l('admin_leader_operating_system.kpi_rising'), $data->overview['totals']['rising'] ?? 0, 'Najjači kandidati za scale-up', 'Growth') ?>
            </div>

            <div class="col-12 col-lg-6 mb-3">
                <?= $render_kpi_card('risk', l('admin_leader_operating_system.kpi_risk'), $data->overview['totals']['risk'] ?? 0, 'Suradnici koji traže coaching ili operativni zahvat', 'Risk') ?>
            </div>

            <div class="col-12 col-lg-3 mb-3">
                <?= $render_kpi_card('anomaly_high', l('admin_leader_operating_system.kpi_anomaly_high'), $data->overview['totals']['anomaly_high'] ?? 0, 'Najhitniji signali za provjeru', 'Now') ?>
            </div>

            <div class="col-12 col-lg-3 mb-3">
                <?= $render_kpi_card('anomaly_watch', l('admin_leader_operating_system.kpi_anomaly_watch'), $data->overview['totals']['anomaly_watch'] ?? 0, 'Signali koje treba pratiti kroz period', 'Watch') ?>
            </div>

            <div class="col-12 col-lg-6 mb-3">
                <?= $render_kpi_card('quality_ready', l('admin_leader_operating_system.kpi_quality_ready'), $data->overview['totals']['quality_ready'] ?? 0, 'Suradnici s jakom aplikacijskom strukturom', 'Apps') ?>
            </div>

            <div class="col-12 col-lg-6">
                <?= $render_kpi_card('total_shop_clicks_period', l('admin_leader_operating_system.kpi_total_shop_clicks'), $data->overview['totals']['total_shop_clicks_period'] ?? 0, 'Tko stvarno donosi webshop aktivnost', 'Revenue') ?>
            </div>

            <div class="col-12 col-lg-4 mb-3">
                <?= $render_kpi_card('active_collaborators', 'Aktivni suradnici', (int) ($data->overview['totals']['active_collaborators'] ?? 0), 'Tko je stvarno bio aktivan u periodu', 'Active') ?>
            </div>

            <div class="col-12 col-lg-4 mb-3">
                <?= $render_kpi_card('total_registrations_period', 'Registracije u periodu', (int) ($data->overview['totals']['total_registrations_period'] ?? 0), 'Klik otvara nositelje registracijskog rezultata', 'Convert') ?>
            </div>

            <div class="col-12 col-lg-4 mb-3">
                <?= $render_kpi_card('total_funnel_leads_period', 'Funnel leadovi', (int) ($data->overview['totals']['total_funnel_leads_period'] ?? 0), 'Tko puni funnel u odabranom periodu', 'Funnel') ?>
            </div>
        </div>

        <?php if(($data->selected_tab ?? 'overview') === 'overview'): ?>
            <div class="row mt-2">
                <div class="col-12 col-xl-8 mb-3">
                    <div class="leader-os-panel leader-os-trend-panel">
                        <div class="text-uppercase small text-muted mb-2">Trend tima</div>
                        <div class="leader-os-trend-toolbar">
                            <h3 class="h5 mb-0">Klikovi, registracije, leadovi i blog -> Forever</h3>
                            <div class="leader-os-trend-periods" id="leader-os-team-trend-periods">
                                <button type="button" class="leader-os-trend-period is-active" data-days="7">7 dana</button>
                                <button type="button" class="leader-os-trend-period" data-days="30">30 dana</button>
                                <button type="button" class="leader-os-trend-period" data-days="90">90 dana</button>
                            </div>
                        </div>
                        <div class="leader-os-trend-legend mb-3">
                            <span class="leader-os-trend-legend-item"><span class="leader-os-trend-dot is-clicks"></span> Klikovi</span>
                            <span class="leader-os-trend-legend-item"><span class="leader-os-trend-dot is-registrations"></span> Registracije</span>
                            <span class="leader-os-trend-legend-item"><span class="leader-os-trend-dot is-leads"></span> Leadovi</span>
                            <span class="leader-os-trend-legend-item"><span class="leader-os-trend-dot is-blog"></span> Blog -> Forever</span>
                        </div>
                        <div class="leader-os-trend-summary" id="leader-os-team-trend-summary">
                            <div class="leader-os-trend-summary-card">
                                <div class="leader-os-trend-summary-label">Klikovi</div>
                                <div class="leader-os-trend-summary-value" data-trend-total="clicks">0</div>
                            </div>
                            <div class="leader-os-trend-summary-card">
                                <div class="leader-os-trend-summary-label">Registracije</div>
                                <div class="leader-os-trend-summary-value" data-trend-total="registrations">0</div>
                            </div>
                            <div class="leader-os-trend-summary-card is-clickable" role="button" tabindex="0" data-toggle="modal" data-target="#leader_os_drilldown_modal" data-trend-summary-card="leads">
                                <div class="leader-os-trend-summary-label">Leadovi</div>
                                <div class="leader-os-trend-summary-value" data-trend-total="leads">0</div>
                            </div>
                            <div class="leader-os-trend-summary-card is-clickable" role="button" tabindex="0" data-toggle="modal" data-target="#leader_os_drilldown_modal" data-trend-summary-card="blog_forever">
                                <div class="leader-os-trend-summary-label">Blog -> Forever</div>
                                <div class="leader-os-trend-summary-value" data-trend-total="blog_forever">0</div>
                            </div>
                        </div>
                        <div class="leader-os-trend-chart-wrap">
                            <svg id="leader-os-team-trend-chart" class="leader-os-trend-chart" viewBox="0 0 920 300" role="img" aria-label="Trend tima kroz vrijeme" data-trend='<?= htmlspecialchars(json_encode(($data->overview['team_trend']['rows'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'></svg>
                            <div class="leader-os-trend-footer">
                                <div class="leader-os-trend-note" id="leader-os-team-trend-note">Pregled zadnjih 7 dana.</div>
                                <div class="leader-os-trend-note text-right" id="leader-os-team-trend-range"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4 mb-3">
                    <div class="leader-os-panel leader-os-support-trend-card h-100">
                        <div class="text-uppercase small text-muted mb-2">Status distribucija</div>
                        <div class="leader-os-status-chart-toolbar">
                            <h3 class="h5 mb-0">Kako je tim raspoređen</h3>
                            <div class="leader-os-trend-periods" id="leader-os-status-periods">
                                <button type="button" class="leader-os-trend-period" data-status-days="7">7 dana</button>
                                <button type="button" class="leader-os-trend-period is-active" data-status-days="30">30 dana</button>
                                <button type="button" class="leader-os-trend-period" data-status-days="90">90 dana</button>
                            </div>
                        </div>
                        <div id="leader-os-status-chart" class="leader-os-status-chart" data-status-distribution='<?= htmlspecialchars(json_encode(($data->overview['status_distribution']['ranges'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'></div>
                        <div class="leader-os-status-chart-note" id="leader-os-status-chart-note">Pregled rasporeda tima u zadnjih 30 dana prema activity prozoru.</div>
                        <div class="leader-os-status-graph-wrap">
                            <svg id="leader-os-status-graph" class="leader-os-status-graph" viewBox="0 0 640 280" role="img" aria-label="Status distribucija grafa"></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="leader-os-grid-3 mb-3">
                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Top po scoreu</div>
                    <div class="leader-os-leaderboard-list">
                        <?php foreach(($data->overview['team_leaderboards']['top_by_score'] ?? []) as $item): ?>
                            <div class="leader-os-leaderboard-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="<?= $item['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                    <strong><?= nr((int) ($item['metric'] ?? 0)) ?></strong>
                                </div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($item['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Top opportunity</div>
                    <div class="leader-os-leaderboard-list">
                        <?php foreach(($data->overview['team_leaderboards']['top_by_opportunity'] ?? []) as $item): ?>
                            <div class="leader-os-leaderboard-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="<?= $item['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                    <strong><?= nr((int) ($item['metric'] ?? 0)) ?></strong>
                                </div>
                                <div class="text-muted small">Leader score <?= nr((int) ($item['leader_os_score'] ?? 0)) ?></div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Top risk</div>
                    <div class="leader-os-leaderboard-list">
                        <?php foreach(($data->overview['team_leaderboards']['top_by_risk'] ?? []) as $item): ?>
                            <div class="leader-os-leaderboard-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="<?= $item['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                    <strong><?= nr((int) ($item['metric'] ?? 0)) ?></strong>
                                </div>
                                <div class="text-muted small">Leader score <?= nr((int) ($item['leader_os_score'] ?? 0)) ?></div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(($data->selected_tab ?? 'overview') === 'overview'): ?>
        <div class="row mt-2">
            <div class="col-12 col-xl-6 mb-3">
                <div class="leader-os-panel">
                    <div class="d-flex justify-content-between align-items-start mb-3" style="gap:.75rem;">
                        <div>
                            <div class="text-uppercase small text-muted mb-2">Blog signal</div>
                            <h3 class="h5 mb-1">Blog -> Forever Living</h3>
                            <div class="text-muted small">Koliko blog sadržaj stvarno vodi prema Forever Living odredištima u odabranom periodu.</div>
                        </div>
                        <span class="leader-os-status-badge status-info"><?= nr((int) ($data->overview['team_blog_forever']['total_clicks'] ?? 0)) ?></span>
                    </div>

                    <div class="row">
                        <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                            <div class="text-muted small mb-1">Ukupni klikovi</div>
                            <div class="h4 mb-0"><?= nr((int) ($data->overview['team_blog_forever']['total_clicks'] ?? 0)) ?></div>
                        </div>
                        <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                            <div class="text-muted small mb-1">Product CTA</div>
                            <div class="h4 mb-0"><?= nr((int) ($data->overview['team_blog_forever']['product_clicks'] ?? 0)) ?></div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="text-muted small mb-1">Business CTA</div>
                            <div class="h4 mb-0"><?= nr((int) ($data->overview['team_blog_forever']['business_clicks'] ?? 0)) ?></div>
                        </div>
                    </div>

                    <div class="text-muted small mt-3">Aktivni suradnici s barem jednim blog -> Forever klikom: <strong class="text-white"><?= nr((int) ($data->overview['team_blog_forever']['active_collaborators'] ?? 0)) ?></strong></div>

                    <?php if(!empty($data->overview['team_blog_forever']['top_collaborators'])): ?>
                        <div class="mt-3">
                            <div class="text-muted small mb-2">Top suradnici</div>
                            <?php foreach(($data->overview['team_blog_forever']['top_collaborators'] ?? []) as $blog_row): ?>
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <a href="<?= $blog_row['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($blog_row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                    <strong><?= nr((int) ($blog_row['total'] ?? 0)) ?></strong>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>

                    <?php if(!empty($data->overview['team_blog_forever']['top_articles'])): ?>
                        <div class="mt-3">
                            <div class="text-muted small mb-2">Top blog članci</div>
                            <?php foreach(($data->overview['team_blog_forever']['top_articles'] ?? []) as $article_row): ?>
                                <div class="d-flex justify-content-between align-items-center py-1" style="gap:.75rem;">
                                    <div class="text-truncate"><?= htmlspecialchars((string) ($article_row['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <strong><?= nr((int) ($article_row['total'] ?? 0)) ?></strong>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>

            <div class="col-12 col-xl-6 mb-3">
                <div class="leader-os-panel">
                    <div class="d-flex justify-content-between align-items-start mb-3" style="gap:.75rem;">
                        <div>
                            <div class="text-uppercase small text-muted mb-2">AI navike</div>
                            <h3 class="h5 mb-1">Pregled AI usage navika tima</h3>
                            <div class="text-muted small">Sažetak AI maturity signala i najvažnijih obrazaca rada, bez dubinskog overloada na glavnom pregledu.</div>
                        </div>
                        <div class="d-flex flex-column align-items-end" style="gap:.5rem;">
                            <span class="leader-os-status-badge status-success"><?= nr((int) ($data->overview['totals']['ai_active_collaborators'] ?? 0)) ?></span>
                            <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($leader_os_state_query, ['tab' => 'ai', 'page' => 1]))) ?>" class="leader-os-link small">Otvori AI navike</a>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6 col-lg-3 mb-3 mb-lg-0">
                            <?= $render_kpi_card('ai_profiles_total', 'Profil', (int) ($data->overview['team_ai_habits']['profiles_total'] ?? 0), 'Tko je završio AI profil', 'AI') ?>
                        </div>
                        <div class="col-6 col-lg-3 mb-3 mb-lg-0">
                            <?= $render_kpi_card('ai_checkins_total', 'Check-in', (int) ($data->overview['team_ai_habits']['checkins_total'] ?? 0), 'Tko aktivno radi tjedne AI check-inove', 'Pulse') ?>
                        </div>
                        <div class="col-6 col-lg-3">
                            <?= $render_kpi_card('ai_plans_total', 'Plan', (int) ($data->overview['team_ai_habits']['plans_total'] ?? 0), 'Tko pretvara AI u konkretan plan rada', 'Plan') ?>
                        </div>
                        <div class="col-6 col-lg-3">
                            <?= $render_kpi_card('ai_outcomes_total', 'Outcome', (int) ($data->overview['team_ai_habits']['outcomes_total'] ?? 0), 'Tko zatvara tjedan s outcome refleksijom', 'Outcome') ?>
                        </div>
                    </div>

                    <div class="leader-os-grid-2 mb-3">
                        <div class="leader-os-panel">
                            <div class="text-uppercase small text-muted mb-2">AI health</div>
                            <div class="text-muted small mb-2">Mentorirani ovaj tjedan: <strong class="text-white"><?= nr((int) ($data->overview['team_ai_habits']['mentored_this_week_total'] ?? 0)) ?></strong></div>
                            <div class="text-muted small mb-2">Prosječni consistency: <strong class="text-white"><?= nr((float) ($data->overview['team_consistency']['average_score'] ?? 0)) ?></strong></div>
                            <div class="text-muted small mb-2">Strong: <strong class="text-white"><?= nr((int) ($data->overview['team_consistency']['strong_total'] ?? 0)) ?></strong> · Watch/low: <strong class="text-white"><?= nr((int) ($data->overview['team_consistency']['watch_total'] ?? 0)) ?></strong></div>
                            <div class="text-muted small">Open-text signal: <strong class="text-white"><?= nr((int) (($data->overview['team_ai_text_intelligence']['context_total'] ?? 0) + ($data->overview['team_ai_text_intelligence']['blocker_total'] ?? 0) + ($data->overview['team_ai_text_intelligence']['lesson_total'] ?? 0))) ?></strong></div>
                        </div>

                        <div class="leader-os-panel">
                            <div class="text-uppercase small text-muted mb-2">Coaching signal</div>
                            <div class="text-muted small mb-2">Coaching touch: <strong class="text-white"><?= nr((int) ($data->overview['team_coaching_roi']['touched_total'] ?? 0)) ?></strong></div>
                            <div class="text-muted small mb-2">Pozitivan signal: <strong class="text-white"><?= nr((int) ($data->overview['team_coaching_roi']['positive_signal_total'] ?? 0)) ?></strong></div>
                            <div class="text-muted small mb-3">Rizik nakon touch-a: <strong class="text-white"><?= nr((int) ($data->overview['team_coaching_roi']['risk_after_touch_total'] ?? 0)) ?></strong></div>

                            <?php if(!empty($data->overview['team_coaching_roi']['top_positive'][0])): ?>
                                <?php $top_coaching = $data->overview['team_coaching_roi']['top_positive'][0]; ?>
                                <div class="text-muted small mb-1">Top coaching signal</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="<?= $top_coaching['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($top_coaching['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                    <strong><?= ($top_coaching['growth_percent'] === null ? '-' : (($top_coaching['growth_percent'] > 0 ? '+' : '') . nr((float) $top_coaching['growth_percent']) . '%')) ?></strong>
                                </div>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="leader-os-grid-2">
                        <div class="leader-os-panel">
                            <div class="text-uppercase small text-muted mb-2">Najčešći obrasci</div>
                            <div class="row">
                                <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                                    <div class="text-muted small mb-2">Top ciljevi</div>
                                    <?= $render_mini_chart(($data->overview['team_ai_habits']['top_goals'] ?? []), 0) ?>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="text-muted small mb-2">Top blockeri</div>
                                    <?= $render_mini_chart(($data->overview['team_ai_habits']['top_blockers'] ?? []), 0) ?>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                                    <div class="text-muted small mb-2">Top AI potrebe</div>
                                    <?= $render_mini_chart(($data->overview['team_ai_habits']['top_ai_needs'] ?? []), 0) ?>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="text-muted small mb-2">Energy / completion</div>
                                    <?= $render_mini_chart(array_slice(array_merge(($data->overview['team_ai_habits']['top_weekly_energy'] ?? []), ($data->overview['team_ai_habits']['top_completion_levels'] ?? [])), 0, 5), 0) ?>
                                </div>
                            </div>
                        </div>

                        <div class="leader-os-panel">
                            <div class="text-uppercase small text-muted mb-2">Focus i akcija</div>
                            <div class="row">
                                <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                                    <div class="text-muted small mb-2">Team focus</div>
                                    <?= $render_mini_chart(($data->overview['team_ai_actions']['top_focus_terms'] ?? []), 0) ?>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="text-muted small mb-2">Team friction</div>
                                    <?= $render_mini_chart(($data->overview['team_ai_actions']['top_friction_terms'] ?? []), 0) ?>
                                </div>
                            </div>

                            <?php if(!empty($data->overview['team_consistency']['top_collaborators'][0])): ?>
                                <?php $top_consistency = $data->overview['team_consistency']['top_collaborators'][0]; ?>
                                <div class="mt-3 pt-3 border-top border-dark">
                                    <div class="text-muted small mb-1">Najkonzistentniji suradnik</div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="<?= $top_consistency['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($top_consistency['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                        <strong><?= nr((int) ($top_consistency['score'] ?? 0)) ?></strong>
                                    </div>
                                </div>
                            <?php endif ?>

                            <?php if(!empty($data->overview['team_ai_actions']['priority_collaborators'][0])): ?>
                                <?php $priority_item = $data->overview['team_ai_actions']['priority_collaborators'][0]; ?>
                                <div class="mt-3 pt-3 border-top border-dark">
                                    <div class="text-muted small mb-1">Prioritet za coaching</div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="<?= $priority_item['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($priority_item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                        <strong><?= nr((int) ($priority_item['priority_score'] ?? 0)) ?></strong>
                                    </div>
                                    <div class="text-muted small mt-1"><?= htmlspecialchars((string) ($priority_item['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif ?>

        <?php if(($data->selected_tab ?? 'overview') === 'analytics'): ?>
            <div class="leader-os-grid-2 mt-2">
                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Countries matrix</div>
                    <h3 class="h5 mb-3">Gdje imamo promet i gdje imamo konverziju</h3>
                    <div class="leader-os-table table-responsive leader-os-matrix-table">
                        <table class="table table-sm mb-0">
                            <thead>
                            <tr>
                                <th>Zemlja</th>
                                <th>Klikovi</th>
                                <th>Registracije</th>
                                <th>CR</th>
                                <th>Aktivni</th>
                                <th>Top source</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach(($data->overview['countries_matrix']['rows'] ?? []) as $country_row): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($country_row['country'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= nr((int) ($country_row['clicks'] ?? 0)) ?></td>
                                    <td><?= nr((int) ($country_row['registrations'] ?? 0)) ?></td>
                                    <td><?= nr((float) ($country_row['conversion_rate'] ?? 0)) ?>%</td>
                                    <td><?= nr((int) ($country_row['active_collaborators'] ?? 0)) ?></td>
                                    <td><?= htmlspecialchars((string) ($country_row['top_source'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Activity heatmap</div>
                    <h3 class="h5 mb-3">Kada tim stvarno radi</h3>
                    <div class="leader-os-heatmap">
                        <table class="leader-os-heatmap-table">
                            <thead>
                            <tr>
                                <th></th>
                                <?php foreach(($data->overview['activity_heatmap']['hours'] ?? []) as $hour_label): ?>
                                    <th><?= htmlspecialchars((string) $hour_label, ENT_QUOTES, 'UTF-8') ?></th>
                                <?php endforeach ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $heatmap_max = max(1, (int) ($data->overview['activity_heatmap']['max_total'] ?? 0)); ?>
                            <?php foreach(($data->overview['activity_heatmap']['rows'] ?? []) as $heatmap_row): ?>
                                <tr>
                                    <th class="leader-os-heatmap-day"><?= htmlspecialchars((string) ($heatmap_row['day_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                    <?php foreach(($heatmap_row['cells'] ?? []) as $cell): ?>
                                        <?php $heat_alpha = min(0.92, max(0.08, ((int) ($cell['total'] ?? 0)) / $heatmap_max)); ?>
                                        <td class="leader-os-heatmap-cell" style="--heat-alpha: <?= htmlspecialchars((string) $heat_alpha, ENT_QUOTES, 'UTF-8') ?>;" title="<?= htmlspecialchars((string) (($heatmap_row['day_label'] ?? '') . ' ' . ($cell['hour'] ?? '') . ': clicks ' . nr((int) ($cell['clicks'] ?? 0)) . ', leads ' . nr((int) ($cell['leads'] ?? 0)) . ', AI ' . nr((int) ($cell['ai_checkins'] ?? 0))), ENT_QUOTES, 'UTF-8') ?>"></td>
                                    <?php endforeach ?>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(($data->selected_tab ?? 'overview') === 'ai'): ?>
            <div class="leader-os-grid-2 mt-2">
                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">AI answers</div>
                    <h3 class="h5 mb-3">Najčešći odabrani odgovori</h3>
                    <div class="row">
                        <div class="col-12 col-lg-6 mb-3">
                            <div class="text-muted small mb-2">Priority offer</div>
                            <?= $render_mini_chart(($data->overview['team_ai_distributions']['top_priority_offers'] ?? []), 0) ?>
                        </div>
                        <div class="col-12 col-lg-6 mb-3">
                            <div class="text-muted small mb-2">Active channels</div>
                            <?= $render_mini_chart(($data->overview['team_ai_distributions']['top_active_channels'] ?? []), 0) ?>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="text-muted small mb-2">Follow-up readiness</div>
                            <?= $render_mini_chart(($data->overview['team_ai_distributions']['top_follow_up_readiness'] ?? []), 0) ?>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="text-muted small mb-2">Weekly priority</div>
                            <?= $render_mini_chart(($data->overview['team_ai_distributions']['top_weekly_priorities'] ?? []), 0) ?>
                        </div>
                    </div>
                </div>

                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">AI execution</div>
                    <h3 class="h5 mb-3">Commitment, follow-up i completion</h3>
                    <div class="row">
                        <div class="col-12 col-lg-6 mb-3">
                            <div class="text-muted small mb-2">Content commitment</div>
                            <?= $render_mini_chart(($data->overview['team_ai_distributions']['top_content_commitment'] ?? []), 0) ?>
                        </div>
                        <div class="col-12 col-lg-6 mb-3">
                            <div class="text-muted small mb-2">Follow-up volume</div>
                            <?= $render_mini_chart(($data->overview['team_ai_distributions']['top_follow_up_volume'] ?? []), 0) ?>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="text-muted small mb-2">Weekly energy</div>
                            <?= $render_mini_chart(($data->overview['team_ai_distributions']['top_weekly_energy'] ?? []), 0) ?>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="text-muted small mb-2">Completion level</div>
                            <?= $render_mini_chart(($data->overview['team_ai_distributions']['top_completion_levels'] ?? []), 0) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(($data->selected_tab ?? 'overview') === 'overview'): ?>
            <div class="row mt-2">
                <div class="col-12 col-xl-6 mb-3">
                    <div class="leader-os-panel">
                        <div class="text-uppercase small text-muted mb-2">Market pulse</div>
                        <h3 class="h5 mb-3">Top zemlje i izvori</h3>
                        <div class="row">
                            <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                                <div class="text-muted small mb-2">Top zemlje</div>
                                <?php foreach(array_slice(($data->overview['team_analytics']['top_countries'] ?? []), 0, 4) as $item): ?>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                                    </div>
                                <?php endforeach ?>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="text-muted small mb-2">Top izvori</div>
                                <?php foreach(array_slice(($data->overview['team_analytics']['top_sources'] ?? []), 0, 4) as $item): ?>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6 mb-3">
                    <div class="leader-os-panel">
                        <div class="text-uppercase small text-muted mb-2">Time pulse</div>
                        <h3 class="h5 mb-3">Top sati i blog market</h3>
                        <div class="row">
                            <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                                <div class="text-muted small mb-2">Top sati klikova</div>
                                <?php foreach(array_slice(($data->overview['team_analytics']['top_hours'] ?? []), 0, 4) as $item): ?>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                                    </div>
                                <?php endforeach ?>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="text-muted small mb-2">Blog -> Forever zemlje</div>
                                <?php foreach(array_slice(($data->overview['team_analytics']['blog_top_countries'] ?? []), 0, 4) as $item): ?>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-xl-6 mb-3">
                    <div class="leader-os-panel">
                        <div class="text-uppercase small text-muted mb-2">Team momentum</div>
                        <h3 class="h5 mb-3">Tko trenutno vuče tim naprijed</h3>
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="text-muted small mb-1">Hot streak</div>
                                <div class="h4 mb-0"><?= nr((int) ($data->overview['team_momentum']['hot_streak_total'] ?? 0)) ?></div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small mb-1">Growth ready</div>
                                <div class="h4 mb-0"><?= nr((int) ($data->overview['team_momentum']['growth_ready_total'] ?? 0)) ?></div>
                            </div>
                        </div>

                        <?php foreach(($data->overview['team_momentum']['top_collaborators'] ?? []) as $item): ?>
                            <div class="py-2 border-top border-dark">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="<?= $item['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                    <strong><?= nr((int) ($item['leader_os_score'] ?? 0)) ?></strong>
                                </div>
                                <div class="text-muted small">
                                    Growth <?= ($item['growth_percent'] === null ? '-' : (($item['growth_percent'] > 0 ? '+' : '') . nr((float) $item['growth_percent']) . '%')) ?>
                                    · Consistency <?= nr((int) ($item['consistency_score'] ?? 0)) ?>
                                    · <?= htmlspecialchars((string) ($item['strongest_country'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="col-12 col-xl-6 mb-3">
                    <div class="leader-os-panel">
                        <div class="text-uppercase small text-muted mb-2">Market champions</div>
                        <h3 class="h5 mb-3">Najjači suradnici po tržištu i konverziji</h3>
                        <?php foreach(($data->overview['team_momentum']['market_champions'] ?? []) as $item): ?>
                            <div class="py-2 border-top border-dark">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="<?= $item['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                    <strong><?= nr((int) ($item['shop_clicks'] ?? 0)) ?></strong>
                                </div>
                                <div class="text-muted small">
                                    <?= htmlspecialchars((string) ($item['country'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    · <?= htmlspecialchars((string) ($item['source'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    · Registracije <?= nr((int) ($item['registrations'] ?? 0)) ?>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>
<?php endif ?>

<?php if(in_array(($data->selected_tab ?? 'overview'), ['overview', 'coaching', 'fraud', 'collaborators'], true)): ?>
<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <form action="<?= url('admin/leader-operating-system') ?>" method="get">
            <input type="hidden" name="period" value="<?= $data->selected_period ?>" />
            <input type="hidden" name="tab" value="<?= $data->selected_tab ?>" />

            <div class="leader-os-toolbar mb-3">
                <div>
                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.search_label') ?></label>
                    <input type="search" name="search" class="form-control" value="<?= input_clean($data->search_query) ?>" placeholder="<?= l('admin_leader_operating_system.search_placeholder') ?>" />
                </div>

                <div>
                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.filter_label') ?></label>
                    <select name="status" class="custom-select">
                        <?php foreach($data->status_options as $status_option): ?>
                            <option value="<?= $status_option ?>" <?= $data->selected_status === $status_option ? 'selected="selected"' : null ?>><?= l('admin_leader_operating_system.filter.' . $status_option) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div>
                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.ai_filter_label') ?></label>
                    <select name="ai_status" class="custom-select">
                        <?php foreach($data->ai_status_options as $ai_status_option): ?>
                            <option value="<?= $ai_status_option ?>" <?= $data->selected_ai_status === $ai_status_option ? 'selected="selected"' : null ?>><?= l('admin_leader_operating_system.ai_filter.' . $ai_status_option) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div>
                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.anomaly_filter_label') ?></label>
                    <select name="anomaly_status" class="custom-select">
                        <?php foreach($data->anomaly_status_options as $anomaly_status_option): ?>
                            <option value="<?= $anomaly_status_option ?>" <?= $data->selected_anomaly_status === $anomaly_status_option ? 'selected="selected"' : null ?>><?= l('admin_leader_operating_system.anomaly_filter.' . $anomaly_status_option) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div>
                    <label class="small text-muted d-block mb-2">Fraud status</label>
                    <select name="fraud_status" class="custom-select">
                        <?php foreach($data->fraud_status_options as $fraud_status_option): ?>
                            <option value="<?= $fraud_status_option ?>" <?= $data->selected_fraud_status === $fraud_status_option ? 'selected="selected"' : null ?>><?= htmlspecialchars((string) ($fraud_filter_labels[$fraud_status_option] ?? ucfirst($fraud_status_option)), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div>
                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.sort_label') ?></label>
                    <select name="sort" class="custom-select">
                        <?php foreach($data->sort_options as $sort_option): ?>
                            <option value="<?= $sort_option ?>" <?= $data->selected_sort === $sort_option ? 'selected="selected"' : null ?>><?= htmlspecialchars((string) ($sort_labels[$sort_option] ?? ucfirst($sort_option)), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <?php foreach($data->status_options as $status_option): ?>
                        <?php $chip_query = http_build_query(['period' => $data->selected_period, 'search' => $data->search_query, 'status' => $status_option, 'ai_status' => $data->selected_ai_status, 'anomaly_status' => $data->selected_anomaly_status, 'fraud_status' => $data->selected_fraud_status, 'sort' => $data->selected_sort, 'tab' => $data->selected_tab]); ?>
                        <a href="<?= url('admin/leader-operating-system?' . $chip_query) ?>" class="leader-os-filter-chip <?= $data->selected_status === $status_option ? 'active' : null ?>">
                            <?= l('admin_leader_operating_system.filter.' . $status_option) ?>
                        </a>
                    <?php endforeach ?>
                </div>

                <button type="submit" class="btn btn-sm mt-2 mt-lg-0 leader-os-action-button"><?= l('admin_leader_operating_system.apply_filters') ?></button>
            </div>

            <!-- Custom code: FC-2026-03-31: LOS overview helper note -->
            <div class="leader-os-inline-note mt-3"><?= l('admin_leader_operating_system.ai_filter_hint') ?></div>
            <!-- /Custom code: FC-2026-03-31 -->
        </form>
</div>
</div>
<?php endif ?>

<?php if(($data->selected_tab ?? 'overview') === 'analytics'): ?>
<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <div class="text-uppercase small text-muted mb-2">Analitika</div>
                <h2 class="h4 mb-1">Team analytics breakdown</h2>
                <p class="text-muted mb-0">Najjače zemlje, izvori i sati klikova za odabrani period.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xl-4 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Top zemlje</div>
                    <?php foreach(($data->overview['team_analytics']['top_countries'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="col-12 col-xl-4 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Top gradovi</div>
                    <?php foreach(($data->overview['team_analytics']['top_cities'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="col-12 col-xl-4 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Top izvori</div>
                    <?php foreach(($data->overview['team_analytics']['top_sources'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="col-12 col-xl-4 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Top sati klikova</div>
                    <?php foreach(($data->overview['team_analytics']['top_hours'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="col-12 col-xl-4 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Top jezici</div>
                    <?php foreach(($data->overview['team_analytics']['top_languages'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="col-12 col-xl-4 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Top uređaji</div>
                    <?php foreach(($data->overview['team_analytics']['top_devices'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="col-12 col-xl-4 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Top browseri</div>
                    <?php foreach(($data->overview['team_analytics']['top_browsers'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="col-12 col-xl-6 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Blog -> Forever po zemljama</div>
                    <?php foreach(($data->overview['team_analytics']['blog_top_countries'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="col-12 col-xl-6 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Blog -> Forever po izvorima</div>
                    <?php foreach(($data->overview['team_analytics']['blog_top_sources'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif ?>

<?php if(in_array(($data->selected_tab ?? 'overview'), ['overview', 'fraud', 'coaching', 'collaborators'], true)): ?>
<?php if(($data->selected_tab ?? 'overview') === 'fraud'): ?>
<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <div class="text-uppercase small text-muted mb-2">Fraud dashboard</div>
                <h2 class="h4 mb-1">Pokušaji zaobilaženja i rizični obrasci</h2>
                <p class="text-muted mb-0">Sažetak anomalija, blokiranih pokušaja i glavnih patterna koje vrijedi pratiti na razini cijelog tima.</p>
            </div>
        </div>

            <div class="row mb-4">
                <div class="col-12 col-lg-2 mb-3">
                    <?= $render_kpi_card('anomaly_high', 'High anomaly', (int) ($data->overview['fraud_dashboard']['totals']['high_anomaly_total'] ?? 0), 'Otvara popis suradnika za hitnu provjeru', 'Fraud') ?>
                </div>
                <div class="col-12 col-lg-2 mb-3">
                    <?= $render_kpi_card('anomaly_watch', 'Watch anomaly', (int) ($data->overview['fraud_dashboard']['totals']['watch_anomaly_total'] ?? 0), 'Popis suradnika koje treba pratiti', 'Watch') ?>
                </div>
                <div class="col-12 col-lg-2 mb-3">
                    <?= $render_kpi_card('fraud_queue_total', 'Queue', (int) ($data->overview['fraud_dashboard']['totals']['queue_total'] ?? 0), 'Tko je trenutno u fraud queueu', 'Queue') ?>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <?= $render_kpi_card('fraud_suspicious_affected_total', 'Affected collaborators', (int) ($data->overview['fraud_dashboard']['totals']['suspicious_affected_total'] ?? 0), 'Tko ima zabilježen suspicious signal', 'Affected') ?>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <?= $render_kpi_card('fraud_blocked_attempts_total', 'Blocked attempts', (int) ($data->overview['fraud_dashboard']['totals']['blocked_attempts_total'] ?? 0), 'Tko generira najviše blokiranih pokušaja', 'Blocked') ?>
                </div>
            </div>

        <div class="row">
            <div class="col-12 col-xl-4 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Top anomaly driveri</div>
                    <?php foreach(($data->overview['fraud_dashboard']['top_anomaly_drivers'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="col-12 col-xl-4 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Top suspicious razlozi</div>
                    <?php foreach(($data->overview['fraud_dashboard']['top_suspicious_reasons'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="col-12 col-xl-4 mb-3">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-2">Top suspicious targeti</div>
                    <?php foreach(($data->overview['fraud_dashboard']['top_suspicious_targets'] ?? []) as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>

        <?php if(!empty($data->overview['fraud_dashboard']['top_risk_collaborators'])): ?>
            <div class="mt-3">
                <div class="text-muted small mb-2">Top rizični suradnici</div>
                <?php foreach(($data->overview['fraud_dashboard']['top_risk_collaborators'] ?? []) as $item): ?>
                    <div class="py-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= $item['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                            <strong>Anomaly <?= nr((int) ($item['anomaly_score'] ?? 0)) ?></strong>
                        </div>
                        <div class="text-muted small"><?= htmlspecialchars((string) ($item['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · Risk <?= nr((int) ($item['risk_score'] ?? 0)) ?> · Blocked <?= nr((int) ($item['blocked_attempts_total'] ?? 0)) ?></div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
</div>
<?php endif ?>

<?php if(($data->selected_tab ?? 'overview') === 'coaching'): ?>
<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <div class="text-uppercase small text-muted mb-2">Coaching</div>
                <h2 class="h4 mb-1">Coaching command</h2>
                <p class="text-muted mb-0">Pregled follow-up opterećenja, recent coaching touch-eva i akcija koje se najviše ponavljaju.</p>
            </div>
        </div>

        <div class="leader-os-strategist-hero mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:1rem;">
                <div>
                    <div class="text-uppercase small text-muted mb-2">AI Team Strategist</div>
                    <h3 class="h4 mb-1"><?= htmlspecialchars((string) ($data->overview['team_strategist']['headline'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                    <div class="text-muted small"><?= htmlspecialchars((string) ($data->overview['team_strategist']['subheadline'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <span class="leader-os-status-badge status-info"><?= htmlspecialchars((string) ($data->selected_period ?? '30d'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="leader-os-strategist-actions">
                <div>
                    <div class="text-uppercase small text-muted mb-1">Tjedni briefing za mentora</div>
                    <div class="text-muted small">Generiraj AI analizu iz aktualnog LOS snapshot-a i odmah dobij fokus, webinar temu, coaching prioritete i poruke za tim.</div>
                </div>
                <div class="leader-os-strategist-actions-forms">
                    <div class="leader-os-strategist-meta">
                        <?php if(($data->overview['team_strategist']['source'] ?? 'heuristic') === 'ai'): ?>
                            <span class="leader-os-status-badge status-success">AI report</span>
                            <?php if(!empty($data->overview['team_strategist']['generated_at'])): ?>
                                <span class="leader-os-pill small"><?= \Altum\Date::get($data->overview['team_strategist']['generated_at'], 2) ?></span>
                            <?php endif ?>
                            <?php if(!empty($data->overview['team_strategist']['model'])): ?>
                                <span class="leader-os-pill small"><?= htmlspecialchars((string) ($data->overview['team_strategist']['model'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif ?>
                        <?php else: ?>
                            <span class="leader-os-status-badge status-warning">Heuristika</span>
                        <?php endif ?>
                    </div>

                    <form action="<?= $leader_os_action_url ?>" method="post" class="d-inline-flex">
                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                        <button type="submit" name="generate_team_strategist" value="1" class="btn btn-sm leader-os-action-button is-primary">Generiraj AI analizu</button>
                    </form>

                    <?php if(($data->overview['team_strategist']['source'] ?? 'heuristic') === 'ai'): ?>
                        <form action="<?= $leader_os_action_url ?>" method="post" class="d-inline-flex">
                            <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                            <button type="submit" name="regenerate_team_strategist" value="1" class="btn btn-sm leader-os-action-button">Osvježi</button>
                        </form>
                    <?php endif ?>
                </div>
            </div>

            <div class="leader-os-grid-2">
                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Tjedni fokus</div>
                    <div class="h5 mb-2"><?= htmlspecialchars((string) ($data->overview['team_strategist']['weekly_focus']['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small mb-3"><?= htmlspecialchars((string) ($data->overview['team_strategist']['weekly_focus']['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="leader-os-inline-note">Primarni KPI fokus: <strong><?= htmlspecialchars((string) ($data->overview['team_strategist']['weekly_focus']['primary_kpi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                </div>

                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Preporučeni webinar</div>
                    <div class="h5 mb-2"><?= htmlspecialchars((string) ($data->overview['team_strategist']['recommended_webinar']['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small mb-3"><?= htmlspecialchars((string) ($data->overview['team_strategist']['recommended_webinar']['why_now'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="leader-os-strategist-list">
                        <?php foreach(($data->overview['team_strategist']['recommended_webinar']['agenda_points'] ?? []) as $agenda_point): ?>
                            <div class="leader-os-strategist-item"><?= htmlspecialchars((string) $agenda_point, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>

            <div class="leader-os-grid-3 mt-3">
                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Jake točke</div>
                    <div class="leader-os-strategist-list">
                        <?php foreach(($data->overview['team_strategist']['strengths'] ?? []) as $strength): ?>
                            <div class="leader-os-strategist-item"><?= htmlspecialchars((string) $strength, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Slabe točke</div>
                    <div class="leader-os-strategist-list">
                        <?php foreach(($data->overview['team_strategist']['weaknesses'] ?? []) as $weakness): ?>
                            <div class="leader-os-strategist-item"><?= htmlspecialchars((string) $weakness, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Sljedeći potezi</div>
                    <div class="leader-os-strategist-list">
                        <?php foreach(($data->overview['team_strategist']['next_actions'] ?? []) as $next_action): ?>
                            <div class="leader-os-strategist-item"><?= htmlspecialchars((string) $next_action, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>

            <div class="leader-os-grid-2 mt-3">
                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Poruka timu</div>
                    <div class="leader-os-inline-note"><?= htmlspecialchars((string) ($data->overview['team_strategist']['team_message_preview'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Poruka risk grupi</div>
                    <div class="leader-os-inline-note"><?= htmlspecialchars((string) ($data->overview['team_strategist']['risk_group_message_preview'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <div class="leader-os-grid-3 mt-3">
                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Coaching prioriteti</div>
                    <div class="leader-os-strategist-list">
                        <?php foreach(($data->overview['team_strategist']['coaching_priorities'] ?? []) as $priority): ?>
                            <div class="leader-os-strategist-item"><?= htmlspecialchars((string) $priority, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">Support insights</div>
                    <div class="leader-os-strategist-list">
                        <?php foreach(($data->overview['team_strategist']['support_insights'] ?? []) as $insight): ?>
                            <div class="leader-os-strategist-item"><?= htmlspecialchars((string) $insight, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="leader-os-panel">
                    <div class="text-uppercase small text-muted mb-2">KPI za pratiti</div>
                    <div class="leader-os-strategist-list">
                        <?php foreach(($data->overview['team_strategist']['kpis_to_watch'] ?? []) as $kpi): ?>
                            <div class="leader-os-strategist-item"><?= htmlspecialchars((string) $kpi, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
                <div class="col-12 col-lg-3 mb-3">
                    <?= $render_kpi_card('coaching_queue_total', 'Queue', (int) ($data->overview['coaching_dashboard']['totals']['queue_total'] ?? 0), 'Otvara coaching priority listu', 'Coach') ?>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <?= $render_kpi_card('coaching_mentored_this_week_total', 'Mentored this week', (int) ($data->overview['coaching_dashboard']['totals']['mentored_this_week_total'] ?? 0), 'Suradnici koji su imali recent coaching touch', 'Recent') ?>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <?= $render_kpi_card('coaching_needs_follow_up_total', 'Needs follow-up', (int) ($data->overview['coaching_dashboard']['totals']['needs_follow_up_total'] ?? 0), 'Tko traži novi coaching kontakt', 'Follow-up') ?>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <?= $render_kpi_card('coaching_stale_follow_up_total', 'Stale follow-up', (int) ($data->overview['coaching_dashboard']['totals']['stale_follow_up_total'] ?? 0), 'Tko je ostao predugo bez follow-upa', 'Stale') ?>
                </div>
        </div>

        <div class="leader-os-grid-2">
            <div class="leader-os-panel">
                <div class="text-uppercase small text-muted mb-2">Top coaching actions</div>
                <?= $render_mini_chart(($data->overview['coaching_dashboard']['top_actions'] ?? []), 0) ?>
            </div>

            <div class="leader-os-panel">
                <div class="text-uppercase small text-muted mb-2">Coaching ROI</div>
                <div class="text-muted small mb-2">Touched: <strong class="text-white"><?= nr((int) ($data->overview['team_coaching_roi']['touched_total'] ?? 0)) ?></strong></div>
                <div class="text-muted small mb-2">Pozitivan signal: <strong class="text-white"><?= nr((int) ($data->overview['team_coaching_roi']['positive_signal_total'] ?? 0)) ?></strong></div>
                <div class="text-muted small">Rizik nakon touch-a: <strong class="text-white"><?= nr((int) ($data->overview['team_coaching_roi']['risk_after_touch_total'] ?? 0)) ?></strong></div>

                <?php if(!empty($data->overview['team_coaching_roi']['top_positive'])): ?>
                    <div class="mt-3">
                        <div class="text-muted small mb-2">Top coaching signal</div>
                        <?php foreach(($data->overview['team_coaching_roi']['top_positive'] ?? []) as $item): ?>
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <a href="<?= $item['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                <strong><?= ($item['growth_percent'] === null ? '-' : (($item['growth_percent'] > 0 ? '+' : '') . nr((float) $item['growth_percent']) . '%')) ?></strong>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <div class="leader-os-panel mt-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:1rem;">
                <div>
                    <div class="text-uppercase small text-muted mb-2">Support intelligence</div>
                    <div class="h5 mb-1">Što tim trenutno najviše pita i gdje treba edukacija</div>
                    <div class="text-muted small">Ovaj blok povezuje coaching odluke sa stvarnim support signalima i recurring nejasnoćama.</div>
                </div>
                <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($leader_os_state_query, ['tab' => 'support', 'page' => 1]))) ?>" class="leader-os-link small">Otvori Podršku</a>
            </div>

            <div class="leader-os-grid-3">
                <div>
                    <div class="text-muted small mb-2">Top teme</div>
                    <?= $render_mini_chart(($data->overview['support_center']['top_themes'] ?? []), 0) ?>
                </div>
                <div>
                    <div class="text-muted small mb-2">Top prijedlozi</div>
                    <?= $render_mini_chart(($data->overview['support_center']['top_suggestion_themes'] ?? []), 0) ?>
                </div>
                <div>
                    <div class="text-muted small mb-2">Top kategorije</div>
                    <?= $render_mini_chart(($data->overview['support_center']['top_categories'] ?? []), 0) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif ?>
<?php endif ?>

<?php if(($data->selected_tab ?? 'overview') === 'support'): ?>
<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <div class="text-uppercase small text-muted mb-2">Podrška</div>
                <h2 class="h4 mb-1">Support centar tima</h2>
                <p class="text-muted mb-0">Odaberi ticket desno, obradi ga lijevo i na istom mjestu vodi cijelu komunikaciju.</p>
            </div>
        </div>

        <?php if(empty($data->overview['support_center']['is_available'])): ?>
            <div class="leader-os-inline-note">Support tablice još nisu dostupne. Kad feedback/ticket modul bude aktivan, ovdje će se pojaviti support intelligence, ticket queue i recurring teme.</div>
        <?php else: ?>
            <?php
            $support_drilldowns = (array) ($data->overview['support_center']['drilldowns'] ?? []);
            $render_support_kpi_attrs = static function(array $drilldowns, string $key, string $fallback_title) {
                $drilldown = $drilldowns[$key] ?? null;
                $has_drilldown = !empty($drilldown['items']);

                if(!$has_drilldown) {
                    return '';
                }

                $payload = htmlspecialchars(json_encode([
                    'title' => (string) ($drilldown['title'] ?? $fallback_title),
                    'items' => array_values($drilldown['items'] ?? []),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');

                return ' role="button" tabindex="0" data-toggle="modal" data-target="#leader_os_drilldown_modal" data-drilldown="' . $payload . '"';
            };
            ?>
            <div class="row mb-4">
                <div class="col-12 col-lg-3 mb-3">
                    <div class="leader-os-kpi leader-os-support-stat-open is-clickable"<?= $render_support_kpi_attrs($support_drilldowns, 'open_total', 'Otvoreni ticketi') ?>>
                        <div class="leader-os-kpi-topline">
                            <div class="leader-os-kpi-label">Otvoreni ticketi</div>
                            <span class="leader-os-kpi-chip">Open</span>
                        </div>
                        <div class="leader-os-kpi-value"><?= nr((int) ($data->overview['support_center']['totals']['open_total'] ?? 0)) ?></div>
                        <div class="leader-os-kpi-hint">Aktivni upiti koji još traže odgovor ili zatvaranje.</div>
                    </div>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <div class="leader-os-kpi leader-os-support-stat-answered is-clickable"<?= $render_support_kpi_attrs($support_drilldowns, 'answered_total', 'Odgovoreni ticketi') ?>>
                        <div class="leader-os-kpi-topline">
                            <div class="leader-os-kpi-label">Odgovoreni</div>
                            <span class="leader-os-kpi-chip">Answered</span>
                        </div>
                        <div class="leader-os-kpi-value"><?= nr((int) ($data->overview['support_center']['totals']['answered_total'] ?? 0)) ?></div>
                        <div class="leader-os-kpi-hint">Ticketi koji imaju reply, ali ih još vrijedi pratiti.</div>
                    </div>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <div class="leader-os-kpi leader-os-support-stat-watch is-clickable"<?= $render_support_kpi_attrs($support_drilldowns, 'stale_total', 'Stale ticketi > 3 dana') ?>>
                        <div class="leader-os-kpi-topline">
                            <div class="leader-os-kpi-label">Stale > 3 dana</div>
                            <span class="leader-os-kpi-chip">Watch</span>
                        </div>
                        <div class="leader-os-kpi-value"><?= nr((int) ($data->overview['support_center']['totals']['stale_total'] ?? 0)) ?></div>
                        <div class="leader-os-kpi-hint">Upiti koji predugo stoje i ruše osjećaj podrške.</div>
                    </div>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <div class="leader-os-kpi leader-os-support-stat-action is-clickable"<?= $render_support_kpi_attrs($support_drilldowns, 'mentor_follow_up_total', 'Treba mentor follow-up') ?>>
                        <div class="leader-os-kpi-topline">
                            <div class="leader-os-kpi-label">Treba mentor follow-up</div>
                            <span class="leader-os-kpi-chip">Action</span>
                        </div>
                        <div class="leader-os-kpi-value"><?= nr((int) ($data->overview['support_center']['totals']['mentor_follow_up_total'] ?? 0)) ?></div>
                        <div class="leader-os-kpi-hint">Support signali koji vrijede pretvoriti u coaching ili webinar temu.</div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12 col-xl-3 mb-3">
                    <div class="leader-os-panel leader-os-support-trend-card h-100">
                        <div class="text-uppercase small text-muted mb-2">Top teme</div>
                        <?= $render_mini_chart(($data->overview['support_center']['top_themes'] ?? []), 0) ?>
                    </div>
                </div>
                <div class="col-12 col-xl-3 mb-3">
                    <div class="leader-os-panel leader-os-support-trend-card h-100">
                        <div class="text-uppercase small text-muted mb-2">Top prijedlozi</div>
                        <?= $render_mini_chart(($data->overview['support_center']['top_suggestion_themes'] ?? []), 0) ?>
                    </div>
                </div>
                <div class="col-12 col-xl-3 mb-3">
                    <div class="leader-os-panel leader-os-support-trend-card h-100">
                        <div class="text-uppercase small text-muted mb-2">Top webinar teme</div>
                        <?= $render_mini_chart(($data->overview['support_center']['top_webinar_topics'] ?? []), 0) ?>
                    </div>
                </div>
                <div class="col-12 col-xl-3 mb-3">
                    <div class="leader-os-panel h-100">
                        <div class="text-uppercase small text-muted mb-2">Top kategorije</div>
                        <?= $render_mini_chart(($data->overview['support_center']['top_categories'] ?? []), 0) ?>
                    </div>
                </div>
            </div>

            <?php
            $support_recent_tickets = array_values((array) ($data->overview['support_center']['recent_tickets'] ?? []));
            $support_top_collaborators = array_values((array) ($data->overview['support_center']['top_collaborators'] ?? []));
            ?>

            <div class="leader-os-support-workspace mb-3">
                <div class="leader-os-support-main">
                <?php if(!empty($data->overview['selected_support_ticket'])): ?>
                    <?php $selected_ticket = $data->overview['selected_support_ticket']; ?>
                    <?php $selected_ai = (array) ($selected_ticket['ai_insight'] ?? []); ?>
                    <?php $selected_status_class = ($selected_ticket['status_key'] ?? 'open') === 'closed' ? 'status-dark' : ((($selected_ticket['status_key'] ?? 'open') === 'answered') ? 'status-success' : 'status-warning'); ?>
                    <?php $selected_opening_message = trim((string) ($selected_ticket['initial_user_message'] ?? '')); ?>
                    <?php if($selected_opening_message === ''): ?>
                        <?php foreach(($selected_ticket['conversation'] ?? []) as $conversation_message): ?>
                            <?php if(empty($conversation_message['is_admin_reply'])) { $selected_opening_message = trim((string) ($conversation_message['message'] ?? '')); break; } ?>
                        <?php endforeach ?>
                    <?php endif ?>
                    <?php if($selected_opening_message === ''): ?>
                        <?php $selected_opening_message = trim((string) ($selected_ticket['message_preview'] ?? '')); ?>
                    <?php endif ?>
                    <?php if($selected_opening_message === ''): ?>
                        <?php $selected_opening_message = trim((string) ($selected_ticket['subject'] ?? '')); ?>
                    <?php endif ?>
                    <div class="leader-os-panel" id="leader-os-support-active-ticket">
                        <div class="leader-os-support-thread-card">
                            <div class="leader-os-support-thread-header">
                                <div>
                                    <div class="text-uppercase small text-muted mb-2">Otvoreni razgovor</div>
                                    <div class="h4 mb-2" id="leader_os_support_title">#<?= (int) ($selected_ticket['feedback_ticket_id'] ?? 0) ?> · <?= htmlspecialchars((string) ($selected_ticket['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted" id="leader_os_support_meta"><?= htmlspecialchars((string) ($selected_ticket['category_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · Zadnje ažuriranje <span id="leader_os_support_last_activity"><?= !empty($selected_ticket['last_datetime']) ? \Altum\Date::get($selected_ticket['last_datetime'], 2) : '-' ?></span></div>
                                </div>
                                <div class="d-flex align-items-center flex-wrap" style="gap:.5rem;">
                                    <span class="leader-os-status-badge status-info <?= empty($selected_ticket['is_webinar_topic_suggestion']) ? 'd-none' : '' ?>" id="leader_os_support_webinar_badge">Predložena tema webinara</span>
                                    <span class="leader-os-status-badge status-webinar <?= empty($selected_ticket['is_webinar_topic_confirmed']) ? 'd-none' : '' ?>" id="leader_os_support_webinar_confirmed_badge">Potvrđeno za webinar</span>
                                    <span class="leader-os-status-badge <?= $selected_status_class ?>" id="leader_os_support_status_badge"><?= htmlspecialchars((string) ($selected_ticket['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>

                            <div class="leader-os-support-thread-actions">
                                <div class="text-muted small">Sve poruke i odgovori nalaze se ovdje, bez otvaranja nove stranice. Poruke: <span id="leader_os_support_message_count"><?= nr((int) ($selected_ticket['message_count'] ?? 0)) ?></span></div>
                                <div class="d-flex flex-wrap" style="gap:.75rem;">
                                    <form action="<?= $leader_os_action_url ?>" method="post" class="d-inline-flex">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" id="leader_os_generate_ai_ticket_id" name="feedback_ticket_id" value="<?= (int) ($selected_ticket['feedback_ticket_id'] ?? 0) ?>" />
                                        <button type="submit" id="leader_os_generate_ai_button" name="generate_support_ticket_ai" value="1" class="btn btn-sm leader-os-action-button"><?= (($selected_ai['source'] ?? 'heuristic') === 'ai') ? 'Osvježi AI' : 'Generiraj AI' ?></button>
                                    </form>
                                    <form action="<?= $leader_os_action_url ?>" method="post" class="d-inline-flex">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" id="leader_os_status_ticket_id" name="feedback_ticket_id" value="<?= (int) ($selected_ticket['feedback_ticket_id'] ?? 0) ?>" />
                                        <input type="hidden" id="leader_os_status_next" name="next_status" value="<?= ($selected_ticket['status_key'] ?? 'open') === 'closed' ? 'open' : 'closed' ?>" />
                                        <button type="submit" id="leader_os_status_button" name="update_support_ticket_status" value="1" class="btn btn-sm leader-os-action-button"><?= ($selected_ticket['status_key'] ?? 'open') === 'closed' ? 'Ponovno otvori' : 'Označi kao riješeno' ?></button>
                                    </form>
                                    <form action="<?= $leader_os_action_url ?>" method="post" class="d-inline-flex">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" id="leader_os_webinar_ticket_id" name="feedback_ticket_id" value="<?= (int) ($selected_ticket['feedback_ticket_id'] ?? 0) ?>" />
                                        <input type="hidden" id="leader_os_webinar_confirmed" name="is_webinar_topic_confirmed" value="<?= !empty($selected_ticket['is_webinar_topic_confirmed']) ? 0 : 1 ?>" />
                                        <button type="submit" id="leader_os_webinar_button" name="toggle_support_ticket_webinar" value="1" class="btn btn-sm leader-os-action-button <?= !empty($selected_ticket['is_webinar_topic_confirmed']) ? 'is-success' : '' ?>"><?= !empty($selected_ticket['is_webinar_topic_confirmed']) ? 'Makni iz webinara' : 'Uvrsti u webinar' ?></button>
                                    </form>
                                </div>
                            </div>

                            <div id="leader_os_support_conversation">
                                <?php foreach(($selected_ticket['conversation'] ?? []) as $message): ?>
                                    <div class="leader-os-conversation-item <?= !empty($message['is_admin_reply']) ? 'is-admin' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                                            <strong><?= htmlspecialchars((string) ($message['author_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                            <span class="text-muted small"><?= !empty($message['datetime']) ? \Altum\Date::get($message['datetime'], 2) : '-' ?></span>
                                        </div>
                                        <div class="leader-os-conversation-message"><?= nl2br(htmlspecialchars((string) ($message['message'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                                        <?php if(!empty($message['attachment'])): ?>
                                            <a href="<?= \Altum\Uploads::get_full_url('feedback_tickets') . $message['attachment'] ?>" target="_blank" class="d-inline-block mt-2"><?= l('feedback_tickets.view_attachment') ?></a>
                                        <?php endif ?>
                                    </div>
                                <?php endforeach ?>
                            </div>

                            <div class="leader-os-support-thread-compose" id="leader-os-support-communication">
                                <div class="text-uppercase small text-muted mb-2">Tvoj odgovor</div>
                                <div class="leader-os-support-ai-note mb-3">
                                    <div class="text-white small mb-2"><strong>AI prijedlog:</strong> <span id="leader_os_support_ai_recommendation"><?= htmlspecialchars((string) ($selected_ai['recommended_action'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div class="text-muted small"><strong>Tema:</strong> <span id="leader_os_support_ai_issue"><?= htmlspecialchars((string) ($selected_ai['core_issue'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span> · <strong>Sažetak:</strong> <span id="leader_os_support_ai_summary"><?= htmlspecialchars((string) ($selected_ai['summary'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span> · <strong>Webinar:</strong> <span id="leader_os_support_ai_webinar"><?= htmlspecialchars((string) ($selected_ai['webinar_candidate'] ?? 'ne'), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($selected_ai['webinar_reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                </div>
                                <form action="<?= $leader_os_action_url ?>" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                    <input type="hidden" id="leader_os_reply_ticket_id" name="feedback_ticket_id" value="<?= (int) ($selected_ticket['feedback_ticket_id'] ?? 0) ?>" />
                                    <input type="hidden" name="support_communication_mode" value="both" />
                                    <input type="hidden" id="leader_os_reply_title" name="support_communication_title" value="<?= htmlspecialchars((string) ('Odgovor na tvoj upit: ' . ($selected_ticket['subject'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" />
                                    <div>
                                        <label class="leader-os-field-label" for="leader_os_support_ticket_reply_message">Napiši odgovor ili dodatno pojašnjenje</label>
                                        <textarea id="leader_os_support_ticket_reply_message" name="support_reply_message" maxlength="10000" class="leader-os-textarea" required><?= htmlspecialchars((string) (($selected_ai['suggested_reply'] ?? '') ?: ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>
                                    <div class="mt-3">
                                        <label class="leader-os-field-label" for="leader_os_support_ticket_attachment"><?= l('feedback_tickets.attachment_optional') ?></label>
                                        <input id="leader_os_support_ticket_attachment" type="file" name="attachment" class="form-control" accept="<?= \Altum\Uploads::get_whitelisted_file_extensions_accept('feedback_tickets') ?>" />
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap mt-3" style="gap:.75rem;">
                                        <div class="text-muted small">Odgovor se sprema u razgovor i korisnik ga odmah vidi u svojoj podršci.</div>
                                        <button type="submit" name="send_support_communication" value="1" class="btn btn-sm leader-os-action-button is-primary">Pošalji odgovor</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="leader-os-panel">
                        <div class="text-uppercase small text-muted mb-2">Aktivni ticket</div>
                        <div class="h5 mb-2">Odaberi ticket s desne strane</div>
                        <div class="text-muted small">Ovdje će se otvoriti cijeli ticket, AI insight i polja za odgovor čim klikneš jedan od zadnjih upita.</div>
                    </div>
                <?php endif ?>
                    <div class="leader-os-panel">
                        <div class="d-flex justify-content-between align-items-center mb-3" style="gap:.75rem;">
                            <div>
                                <div class="text-uppercase small text-muted mb-1">Top suradnici po upitima</div>
                                <div class="text-muted small">Najjačih 5 po ukupnom broju upita, ostale možeš otvoriti po potrebi.</div>
                            </div>
                            <span class="leader-os-pill small"><?= nr(count($support_top_collaborators)) ?></span>
                        </div>
                        <div class="leader-os-support-list">
                            <?php foreach($support_top_collaborators as $collaborator_index => $item): ?>
                                <div class="leader-os-support-item <?= $collaborator_index >= 5 ? 'leader-os-is-hidden leader-os-more-collaborator-item' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="<?= $item['detail_url'] ?>" class="leader-os-link"><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                        <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                                    </div>
                                    <div class="text-muted small"><?= htmlspecialchars((string) ($item['user_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <?php if(count($support_top_collaborators) > 5): ?>
                            <button type="button" class="btn btn-sm leader-os-action-button leader-os-support-toggle" data-target=".leader-os-more-collaborator-item" data-open-label="Otvori ostale suradnike" data-close-label="Sakrij ostale suradnike">Otvori ostale suradnike</button>
                        <?php endif ?>
                    </div>
                </div>

                <div class="leader-os-support-rail">
                    <div class="leader-os-panel">
                        <div class="d-flex justify-content-between align-items-center mb-3" style="gap:.75rem;">
                            <div>
                                <div class="text-uppercase small text-muted mb-1">Recent ticketi</div>
                                <div class="text-muted small">Zadnjih 5 u fokusu, noviji uvijek idu na vrh.</div>
                            </div>
                            <span class="leader-os-pill small"><?= nr(count($support_recent_tickets)) ?></span>
                        </div>
                        <?php foreach($support_recent_tickets as $ticket_index => $ticket): ?>
                            <?php $status_class = ($ticket['status_key'] ?? 'open') === 'closed' ? 'status-dark' : ((($ticket['status_key'] ?? 'open') === 'answered') ? 'status-success' : 'status-warning'); ?>
                            <?php $ai_insight = (array) ($ticket['ai_insight'] ?? []); ?>
                            <?php $ai_source = (string) ($ai_insight['source'] ?? 'heuristic'); ?>
                            <?php $urgency_value = (string) ($ai_insight['urgency'] ?? 'normalno'); ?>
                            <?php $urgency_badge_class = match($urgency_value) {
                                'visoko' => 'status-danger',
                                'srednje' => 'status-warning',
                                'nisko' => 'status-secondary',
                                default => 'status-info',
                            }; ?>
                            <?php $ticket_json_payload = htmlspecialchars(json_encode([
                                'feedback_ticket_id' => (int) ($ticket['feedback_ticket_id'] ?? 0),
                                'subject' => (string) ($ticket['subject'] ?? ''),
                                'user_name' => (string) ($ticket['user_name'] ?? ''),
                                'user_email' => (string) ($ticket['user_email'] ?? ''),
                                'category_label' => (string) ($ticket['category_label'] ?? ''),
                                'status_label' => (string) ($ticket['status_label'] ?? ''),
                                'status_key' => (string) ($ticket['status_key'] ?? 'open'),
                                'is_webinar_topic_suggestion' => !empty($ticket['is_webinar_topic_suggestion']),
                                'is_webinar_topic_confirmed' => !empty($ticket['is_webinar_topic_confirmed']),
                                'last_datetime_display' => !empty($ticket['last_datetime']) ? \Altum\Date::get($ticket['last_datetime'], 2) : '-',
                                'message_count' => (int) ($ticket['message_count'] ?? 0),
                                'initial_user_message' => (string) (($ticket['initial_user_message'] ?? '') !== '' ? $ticket['initial_user_message'] : ($ticket['message_preview'] ?? $ticket['subject'] ?? '')),
                                'conversation' => array_values((array) ($ticket['conversation'] ?? [])),
                                'ai_insight' => (array) ($ticket['ai_insight'] ?? []),
                            ]), ENT_QUOTES, 'UTF-8'); ?>
                            <div class="<?= $ticket_index >= 5 ? 'leader-os-is-hidden leader-os-more-ticket-item' : '' ?>">
                                <button type="button" class="leader-os-support-ticket-button">
                                    <div class="leader-os-support-ticket is-compact is-clickable <?= ((int) ($ticket['feedback_ticket_id'] ?? 0) === (int) ($data->selected_support_ticket_id ?? 0)) ? 'border border-primary' : '' ?>" data-support-ticket='<?= $ticket_json_payload ?>' data-support-ticket-url="<?= htmlspecialchars(url('admin/leader-operating-system?' . http_build_query(array_merge($leader_os_state_query, ['tab' => 'support', 'page' => 1, 'support_ticket_id' => (int) ($ticket['feedback_ticket_id'] ?? 0)]))), ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="d-flex justify-content-between align-items-start" style="gap:.75rem;">
                                            <div>
                                                <div class="leader-os-link font-weight-bold"><?= htmlspecialchars((string) ($ticket['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars((string) ($ticket['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($ticket['category_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>
                                            <div class="d-flex align-items-center flex-wrap justify-content-end" style="gap:.45rem;">
                                                <?php if(!empty($ticket['is_webinar_topic_confirmed'])): ?>
                                                    <span class="leader-os-status-badge status-webinar">Potvrđeno</span>
                                                <?php elseif(!empty($ticket['is_webinar_topic_suggestion'])): ?>
                                                    <span class="leader-os-status-badge status-info">Predloženo</span>
                                                <?php endif ?>
                                                <?php if(!empty($ticket['is_webinar_topic_suggestion'])): ?>
                                                    <span class="leader-os-status-badge status-info">Webinar</span>
                                                <?php endif ?>
                                                <span class="leader-os-status-badge <?= $status_class ?>"><?= htmlspecialchars((string) ($ticket['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </div>
                                        <div class="leader-os-support-preview"><?= htmlspecialchars((string) ($ticket['message_preview'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="leader-os-support-ticket-meta">
                                            <div class="text-muted small"><?= !empty($ticket['last_datetime']) ? \Altum\Date::get($ticket['last_datetime'], 2) : '-' ?> · Poruka <?= nr((int) ($ticket['message_count'] ?? 0)) ?></div>
                                            <div class="leader-os-support-ticket-badges">
                                                <span class="leader-os-status-badge <?= $ai_source === 'ai' ? 'status-success' : 'status-secondary' ?>"><?= $ai_source === 'ai' ? 'AI' : 'Heur.' ?></span>
                                                <span class="leader-os-status-badge <?= $urgency_badge_class ?>"><?= htmlspecialchars($urgency_value, ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </div>
                                        <div class="text-muted small mt-2"><strong>Tema:</strong> <?= htmlspecialchars((string) ($ai_insight['core_issue'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-muted small mt-1"><strong>Preporuka:</strong> <?= htmlspecialchars((string) ($ai_insight['recommended_action'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="d-flex justify-content-between align-items-center mt-3" style="gap:.75rem;">
                                            <div class="text-muted small">Klik otvara ticket lijevo.</div>
                                            <span class="leader-os-link small">Otvori ticket</span>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        <?php endforeach ?>
                        <?php if(count($support_recent_tickets) > 5): ?>
                            <button type="button" class="btn btn-sm leader-os-action-button leader-os-support-toggle" data-target=".leader-os-more-ticket-item" data-open-label="Otvori ostale tickete" data-close-label="Sakrij ostale tickete">Otvori ostale tickete</button>
                        <?php endif ?>
                    </div>
                </div>
            </div>

        <?php endif ?>
    </div>
</div>
<?php endif ?>

<?php if(in_array(($data->selected_tab ?? 'overview'), ['overview', 'coaching', 'fraud', 'collaborators'], true)): ?>
<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <?php if(in_array(($data->selected_tab ?? 'overview'), ['overview', 'coaching'], true)): ?>
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.section_queue') ?></div>
                <h2 class="h4 mb-1"><?= l('admin_leader_operating_system.queue_title') ?></h2>
                <p class="text-muted mb-0"><?= l('admin_leader_operating_system.queue_text') ?></p>
            </div>
        </div>

        <?php if(empty($data->overview['queue_rows'])): ?>
            <div class="leader-os-panel mb-4">
                <p class="text-muted mb-0"><?= l('admin_leader_operating_system.queue_empty') ?></p>
            </div>
        <?php else: ?>
            <?php if(($data->selected_tab ?? 'overview') === 'coaching'): ?>
                <div class="leader-os-panel mb-4" id="leader-os-message-center">
                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:1rem;">
                        <div>
                            <div class="text-uppercase small text-muted mb-2">Message Center</div>
                            <div class="h5 mb-1">Jedan sustav za privatne i grupne poruke</div>
                            <div class="text-muted small">Odaberi šalješ li poruku pojedinom suradniku ili grupi. Isti obrazac koristi strategist poruke, priority listu i vidljive interne obavijesti.</div>
                        </div>
                        <span class="leader-os-status-badge status-info">Internal notification</span>
                    </div>

                    <div class="leader-os-segment-list">
                        <?php foreach((array) ($data->overview['message_targets'] ?? []) as $group_key => $group): ?>
                            <span class="leader-os-pill small">
                                <?= htmlspecialchars((string) ($group['label'] ?? ucfirst((string) $group_key)), ENT_QUOTES, 'UTF-8') ?>
                                <strong><?= nr((int) ($group['count'] ?? 0)) ?></strong>
                            </span>
                        <?php endforeach ?>
                    </div>

                    <form action="<?= $leader_os_action_url ?>" method="post" class="mt-3">
                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />

                        <div class="leader-os-form-grid">
                            <div>
                                <label class="leader-os-field-label" for="leader_os_message_target_mode">Način slanja</label>
                                <select id="leader_os_message_target_mode" name="message_target_mode" class="leader-os-select" required>
                                    <option value="single">Pojedini suradnik</option>
                                    <option value="group">Grupa suradnika</option>
                                </select>
                            </div>

                            <div id="leader_os_single_target_wrap">
                                <label class="leader-os-field-label" for="leader_os_target_user_id">Suradnik</label>
                                <select id="leader_os_target_user_id" name="message_target_user_id" class="leader-os-select">
                                    <option value="">Odaberi suradnika</option>
                                    <?php foreach((array) ($data->overview['message_targets']['individual_targets'] ?? []) as $target_row): ?>
                                        <option value="<?= (int) ($target_row['user_id'] ?? 0) ?>">
                                            <?= htmlspecialchars((string) ($target_row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            <?= !empty($target_row['meta']) ? ' - ' . htmlspecialchars((string) $target_row['meta'], ENT_QUOTES, 'UTF-8') : '' ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div id="leader_os_group_target_wrap" style="display:none;">
                                <label class="leader-os-field-label" for="leader_os_target_group">Grupa</label>
                                <select id="leader_os_target_group" name="message_target_group" class="leader-os-select">
                                    <option value="">Odaberi grupu</option>
                                    <?php foreach((array) ($data->overview['message_targets'] ?? []) as $group_key => $group): ?>
                                        <option value="<?= htmlspecialchars((string) $group_key, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string) ($group['label'] ?? ucfirst((string) $group_key)), ENT_QUOTES, 'UTF-8') ?> (<?= nr((int) ($group['count'] ?? 0)) ?>)
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div>
                                <label class="leader-os-field-label" for="leader_os_message_title">Naslov poruke</label>
                                <input id="leader_os_message_title" type="text" name="message_title" maxlength="128" class="leader-os-input" value="Tjedni fokus za tvoj sljedeći korak" required />
                            </div>

                            <div class="leader-os-form-grid-full">
                                <label class="leader-os-field-label" for="leader_os_message_description">Poruka</label>
                                <textarea id="leader_os_message_description" name="message_description" maxlength="1024" class="leader-os-textarea" required><?= htmlspecialchars((string) ($data->overview['team_strategist']['team_message_preview'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap mt-3" style="gap:.75rem;">
                            <div class="d-flex flex-wrap" style="gap:.5rem;">
                                <button type="button" class="btn btn-sm leader-os-action-button leader-os-template-trigger" data-mode="group" data-group="team" data-title="Tjedni fokus za cijeli tim" data-message="<?= htmlspecialchars((string) ($data->overview['team_strategist']['team_message_preview'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">Učitaj tim poruku</button>
                                <button type="button" class="btn btn-sm leader-os-action-button leader-os-template-trigger" data-mode="group" data-group="risk" data-title="Fokus za risk grupu ovaj tjedan" data-message="<?= htmlspecialchars((string) ($data->overview['team_strategist']['risk_group_message_preview'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">Učitaj risk poruku</button>
                                <button type="button" class="btn btn-sm leader-os-action-button leader-os-template-trigger" data-mode="group" data-group="rising" data-title="Momentum poruka za rising grupu" data-message="<?= htmlspecialchars((string) (($data->overview['team_strategist']['weekly_focus']['title'] ?? '') ? 'Ovaj tjedan koristi momentum koji već imaš i fokusiraj se na: ' . ($data->overview['team_strategist']['weekly_focus']['title'] ?? '') . '.' : ''), ENT_QUOTES, 'UTF-8') ?>">Učitaj rising poruku</button>
                            </div>

                            <button type="submit" name="send_message_center_message" value="1" class="btn btn-sm leader-os-action-button is-primary">Pošalji poruku</button>
                        </div>
                    </form>
                </div>
            <?php endif ?>

            <div class="leader-os-queue-grid mb-4">
                <?php foreach($data->overview['queue_rows'] as $queue_row): ?>
                    <div class="leader-os-queue-card">
                        <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                            <div>
                                <div class="font-weight-bold"><?= $queue_row['name'] ?></div>
                                <div class="text-muted small"><?= $queue_row['email'] ?></div>
                                <!-- Custom code: FC-2026-03-31: LOS overview AI usage badges -->
                                <div class="leader-os-ai-usage">
                                    <span class="leader-os-status-badge leader-os-ai-usage-main <?= $queue_row['ai_usage_stage_class'] ?>"><?= htmlspecialchars((string) ($queue_row['ai_usage_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="leader-os-status-badge leader-os-anomaly-badge <?= $queue_row['anomaly_stage_class'] ?>"><?= htmlspecialchars((string) ($queue_row['anomaly_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php foreach(($queue_row['ai_usage_badges'] ?? []) as $ai_usage_badge): ?>
                                        <span class="leader-os-status-badge leader-os-ai-usage-badge <?= $ai_usage_badge['class'] ?>"><?= htmlspecialchars((string) ($ai_usage_badge['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach ?>
                                </div>
                                <!-- /Custom code: FC-2026-03-31 -->
                            </div>
                            <span class="leader-os-status-badge status-<?= $queue_row['status_class'] ?>"><?= $queue_row['status_label'] ?></span>
                        </div>

                        <div class="leader-os-queue-reason mb-2"><?= htmlspecialchars((string) (($queue_row['combined_priority_reason'] ?? '') ?: ($queue_row['queue_reason'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>

                        <div class="leader-os-queue-meta">
                            <div class="text-muted small"><?= l('admin_leader_operating_system.queue_score') ?>: <strong class="text-white"><?= nr((int) (($queue_row['combined_priority_score'] ?? 0) ?: ($queue_row['queue_priority_score'] ?? 0))) ?></strong></div>
                            <div class="text-muted small"><?= l('admin_leader_operating_system.queue_risk') ?>: <strong class="text-white"><?= nr((int) ($queue_row['risk_score'] ?? 0)) ?></strong></div>
                            <div class="text-muted small"><?= l('admin_leader_operating_system.queue_anomaly') ?>: <strong class="text-white"><?= nr((int) ($queue_row['anomaly_score'] ?? 0)) ?></strong></div>
                            <div class="text-muted small">Blocked attempts: <strong class="text-white"><?= nr((int) ($queue_row['blocked_attempts_total'] ?? 0)) ?></strong></div>
                            <div class="text-muted small">AI status: <strong class="text-white"><?= htmlspecialchars((string) ($queue_row['ai_access_tier_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                            <div class="text-muted small">Klikovi i prijave 30d: <strong class="text-white"><?= nr((int) ($queue_row['ai_access_growth_signal_30d'] ?? 0)) ?></strong></div>
                            <div class="text-muted small">Shop <?= nr((int) ($queue_row['ai_access_shop_clicks_30d'] ?? 0)) ?> · Funnel <?= nr((int) ($queue_row['ai_access_funnel_registrations_30d'] ?? 0)) ?> · WhatsApp <?= nr((int) ($queue_row['ai_access_whatsapp_contacts_30d'] ?? 0)) ?></div>
                            <?php if(!empty($queue_row['last_contacted_at'])): ?>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.queue_last_contact') ?>: <strong class="text-white"><?= \Altum\Date::get($queue_row['last_contacted_at'], 2) ?></strong></div>
                            <?php endif ?>
                            <?php if(!empty($queue_row['latest_mentor_event_summary'])): ?>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.queue_last_event') ?>: <strong class="text-white"><?= htmlspecialchars((string) $queue_row['latest_mentor_event_summary'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                            <?php endif ?>
                            <?php if(!empty($queue_row['mentor_history_total'])): ?>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.queue_history_total') ?>: <strong class="text-white"><?= nr((int) $queue_row['mentor_history_total']) ?></strong></div>
                            <?php endif ?>
                            <?php if(!empty($queue_row['mentor_next_action'])): ?>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.queue_next_action') ?>: <strong class="text-white"><?= htmlspecialchars((string) $queue_row['mentor_next_action'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                            <?php endif ?>
                        </div>

                        <div class="mt-3 d-flex flex-column">
                            <a href="<?= $queue_row['detail_url'] ?>" class="leader-os-link"><?= l('admin_leader_operating_system.queue_open') ?></a>
                            <a href="<?= $queue_row['admin_user_url'] ?>" class="leader-os-link text-muted"><?= l('admin_index.biolink_qualified_watch.open_profile') ?></a>
                            <?php if(($data->selected_tab ?? 'overview') === 'coaching'): ?>
                                <a href="#leader-os-message-center" class="leader-os-link text-muted leader-os-quick-message-trigger" data-user-id="<?= (int) ($queue_row['user_id'] ?? 0) ?>">Odaberi za poruku</a>
                            <?php endif ?>
                        </div>

                        <?php if(!empty($queue_row['ai_access_is_pro'])): ?>
                            <div class="leader-os-ai-access-actions">
                                <?php if(($queue_row['ai_access_tier_key'] ?? '') !== 'pro_active' && ($queue_row['ai_access_tier_key'] ?? '') !== 'pro_vip'): ?>
                                    <form action="<?= $leader_os_action_url ?>" method="post">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" name="user_id" value="<?= (int) $queue_row['user_id'] ?>" />
                                        <input type="hidden" name="los_ai_unlock_action" value="pro_active" />
                                        <button type="submit" class="btn btn-sm leader-os-action-button is-primary">Otključaj Active</button>
                                    </form>
                                <?php endif ?>
                                <?php if(($queue_row['ai_access_tier_key'] ?? '') !== 'pro_vip'): ?>
                                    <form action="<?= $leader_os_action_url ?>" method="post">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" name="user_id" value="<?= (int) $queue_row['user_id'] ?>" />
                                        <input type="hidden" name="los_ai_unlock_action" value="pro_vip" />
                                        <button type="submit" class="btn btn-sm leader-os-action-button is-success">Otključaj VIP</button>
                                    </form>
                                <?php endif ?>
                                <?php if(!empty($queue_row['ai_access_manual_tier'])): ?>
                                    <form action="<?= $leader_os_action_url ?>" method="post">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" name="user_id" value="<?= (int) $queue_row['user_id'] ?>" />
                                        <input type="hidden" name="los_ai_unlock_action" value="auto" />
                                        <button type="submit" class="btn btn-sm leader-os-action-button">Vrati automatski</button>
                                    </form>
                                <?php endif ?>
                            </div>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php endif ?>
        <?php if(in_array(($data->selected_tab ?? 'overview'), ['overview', 'fraud'], true)): ?>
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.section_suspicious_clicks') ?></div>
                <h2 class="h4 mb-1"><?= l('admin_leader_operating_system.suspicious_clicks_title') ?></h2>
                <p class="text-muted mb-0"><?= sprintf(l('admin_leader_operating_system.suspicious_clicks_text'), nr((int) ($data->overview['suspicious_clicks']['effective_period_days'] ?? 30))) ?></p>
                <div class="leader-os-inline-note mt-3"><?= sprintf(l('admin_leader_operating_system.suspicious_clicks_helper'), nr((int) ($data->overview['suspicious_clicks']['retention_days'] ?? 30))) ?></div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12 col-lg-4 mb-3">
                <div class="leader-os-kpi">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.suspicious_clicks_affected') ?></div>
                    <div class="h2 mb-0"><?= nr((int) ($data->overview['suspicious_clicks']['totals']['affected_collaborators'] ?? 0)) ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-4 mb-3">
                <div class="leader-os-kpi">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.suspicious_clicks_blocked') ?></div>
                    <div class="h2 mb-0"><?= nr((int) ($data->overview['suspicious_clicks']['totals']['blocked_attempts_total'] ?? 0)) ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-4 mb-3">
                <div class="leader-os-kpi">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.suspicious_clicks_groups') ?></div>
                    <div class="h2 mb-0"><?= nr((int) ($data->overview['suspicious_clicks']['totals']['groups_total'] ?? 0)) ?></div>
                </div>
            </div>
        </div>

        <?php if(empty($data->overview['suspicious_clicks']['rows'])): ?>
            <div class="leader-os-panel mb-4">
                <p class="text-muted mb-0"><?= l('admin_leader_operating_system.suspicious_clicks_empty') ?></p>
            </div>
        <?php else: ?>
            <div class="leader-os-queue-grid mb-4">
                <?php foreach(($data->overview['suspicious_clicks']['rows'] ?? []) as $suspicious_row): ?>
                    <div class="leader-os-queue-card">
                        <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                            <div>
                                <div class="font-weight-bold"><?= htmlspecialchars((string) ($suspicious_row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($suspicious_row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="leader-os-ai-usage">
                                    <span class="leader-os-status-badge leader-os-ai-usage-main <?= $suspicious_row['ai_usage_stage_class'] ?>"><?= htmlspecialchars((string) ($suspicious_row['ai_usage_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="leader-os-status-badge leader-os-anomaly-badge <?= $suspicious_row['anomaly_stage_class'] ?>"><?= htmlspecialchars((string) ($suspicious_row['anomaly_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <span class="leader-os-status-badge status-<?= htmlspecialchars((string) ($suspicious_row['status_class'] ?? 'secondary'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($suspicious_row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <div class="leader-os-queue-reason mb-2"><?= htmlspecialchars((string) ($suspicious_row['top_reason_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted small mb-2"><?= htmlspecialchars((string) ($suspicious_row['top_reason_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

                        <div class="leader-os-queue-meta">
                            <div class="text-muted small"><?= l('admin_leader_operating_system.suspicious_clicks_blocked') ?>: <strong class="text-white"><?= nr((int) ($suspicious_row['blocked_attempts_total'] ?? 0)) ?></strong></div>
                            <div class="text-muted small"><?= l('admin_leader_operating_system.suspicious_clicks_groups') ?>: <strong class="text-white"><?= nr((int) ($suspicious_row['suspicious_groups_total'] ?? 0)) ?></strong></div>
                            <div class="text-muted small"><?= l('admin_leader_operating_system.suspicious_clicks_targets') ?>: <strong class="text-white"><?= nr((int) ($suspicious_row['targets_total'] ?? 0)) ?></strong></div>
                            <div class="text-muted small"><?= l('admin_leader_operating_system.suspicious_clicks_last') ?>: <strong class="text-white"><?= !empty($suspicious_row['last_suspicious_at']) ? \Altum\Date::get($suspicious_row['last_suspicious_at'], 2) : '-' ?></strong></div>
                        </div>

                        <div class="mt-3 d-flex flex-column">
                            <a href="<?= $suspicious_row['detail_url'] ?>" class="leader-os-link"><?= l('admin_leader_operating_system.queue_open') ?></a>
                            <a href="<?= $suspicious_row['admin_user_url'] ?>" class="leader-os-link text-muted"><?= l('admin_index.biolink_qualified_watch.open_profile') ?></a>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php endif ?>
        <?php if(($data->selected_tab ?? 'overview') === 'collaborators'): ?>
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.section_roster') ?></div>
                <h2 class="h4 mb-1"><?= l('admin_leader_operating_system.roster_title') ?></h2>
                <p class="text-muted mb-0">
                    <?= sprintf(l('admin_leader_operating_system.results_total'), nr($data->overview['pagination']['total_results'])) ?>
                    ·
                    <?= sprintf(l('admin_leader_operating_system.results_range'), nr($data->overview['pagination']['from']), nr($data->overview['pagination']['to'])) ?>
                </p>
                <!-- Custom code: FC-2026-03-31: LOS overview roster helper -->
                <div class="leader-os-inline-note mt-3"><?= l('admin_leader_operating_system.roster_text') ?></div>
                <!-- /Custom code: FC-2026-03-31 -->
            </div>
        </div>

        <?php if(empty($data->overview['rows'])): ?>
            <div class="leader-os-panel">
                <p class="text-muted mb-0"><?= l('admin_leader_operating_system.empty') ?></p>
            </div>
        <?php else: ?>
            <?php
            $sort_header_query_base = [
                'period' => $data->selected_period,
                'search' => $data->search_query,
                'status' => $data->selected_status,
                'ai_status' => $data->selected_ai_status,
                'anomaly_status' => $data->selected_anomaly_status,
                'fraud_status' => $data->selected_fraud_status,
                'tab' => $data->selected_tab,
                'page' => 1,
            ];
            $get_sort_url = static function(string $sort_key) use ($sort_header_query_base) {
                return url('admin/leader-operating-system?' . http_build_query(array_merge($sort_header_query_base, ['sort' => $sort_key])));
            };
            $get_sort_class = static function(string $sort_key, string $selected_sort) {
                return $selected_sort === $sort_key ? 'active' : null;
            };
            ?>
            <div class="leader-os-table table-responsive">
                <table class="table table-hover align-items-center mb-0">
                    <thead>
                    <tr>
                        <th><?= l('admin_leader_operating_system.table.collaborator') ?></th>
                        <th><?= l('admin_leader_operating_system.table.status') ?></th>
                        <th><a href="<?= $get_sort_url('leader_os') ?>" class="leader-os-sort-link <?= $get_sort_class('leader_os', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.score') ?></a></th>
                        <th><a href="<?= $get_sort_url('fraud') ?>" class="leader-os-sort-link <?= $get_sort_class('fraud', $data->selected_sort) ?>">Fraud</a></th>
                        <th><a href="<?= $get_sort_url('app_quality') ?>" class="leader-os-sort-link <?= $get_sort_class('app_quality', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.app_quality') ?></a></th>
                        <th><a href="<?= $get_sort_url('shop_clicks') ?>" class="leader-os-sort-link <?= $get_sort_class('shop_clicks', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.shop_clicks') ?></a></th>
                        <th><a href="<?= $get_sort_url('growth') ?>" class="leader-os-sort-link <?= $get_sort_class('growth', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.growth') ?></a></th>
                        <th><a href="<?= $get_sort_url('registrations') ?>" class="leader-os-sort-link <?= $get_sort_class('registrations', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.registrations') ?></a></th>
                        <th><a href="<?= $get_sort_url('country') ?>" class="leader-os-sort-link <?= $get_sort_class('country', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.country') ?></a></th>
                        <th><a href="<?= $get_sort_url('source') ?>" class="leader-os-sort-link <?= $get_sort_class('source', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.source') ?></a></th>
                        <th><a href="<?= $get_sort_url('last_click') ?>" class="leader-os-sort-link <?= $get_sort_class('last_click', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.last_click') ?></a></th>
                        <th><?= l('admin_leader_operating_system.table.actions') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($data->overview['rows'] as $row): ?>
                        <?php $growth_percent = $row['growth_percent']; ?>
                        <?php $growth_class = $growth_percent === null ? '' : ($growth_percent >= 0 ? 'leader-os-growth-positive' : 'leader-os-growth-negative'); ?>
                        <?php
                        $ai_access_panel_class = match((string) ($row['ai_access_tier_key'] ?? 'locked')) {
                            'pro_start' => 'is-start',
                            'pro_active' => 'is-active',
                            'pro_vip' => 'is-vip',
                            default => 'is-locked',
                        };
                        $ai_signal_progress = min(100, max(0, ((int) ($row['ai_access_growth_signal_30d'] ?? 0) / 50) * 100));
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <div class="font-weight-bold"><?= $row['name'] ?></div>
                                    <div class="text-muted small"><?= $row['email'] ?></div>
                                    <div class="text-muted small"><?= l('admin_index.biolink_qualified_watch.forever_id') ?>: <?= $row['forever_id'] ?></div>
                                    <!-- Custom code: FC-2026-03-31: LOS overview AI usage badges -->
                                    <div class="leader-os-ai-usage">
                                        <span class="leader-os-status-badge leader-os-ai-usage-main <?= $row['ai_usage_stage_class'] ?>"><?= htmlspecialchars((string) ($row['ai_usage_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="leader-os-status-badge leader-os-anomaly-badge <?= $row['anomaly_stage_class'] ?>"><?= htmlspecialchars((string) ($row['anomaly_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if(!empty($row['fraud_badge_label'])): ?>
                                            <span class="leader-os-status-badge <?= htmlspecialchars((string) ($row['fraud_badge_class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($row['fraud_badge_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif ?>
                                        <?php foreach(($row['ai_usage_badges'] ?? []) as $ai_usage_badge): ?>
                                            <span class="leader-os-status-badge leader-os-ai-usage-badge <?= $ai_usage_badge['class'] ?>"><?= htmlspecialchars((string) ($ai_usage_badge['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endforeach ?>
                                    </div>
                                    <?php if(!empty($row['top_reason_title'])): ?>
                                        <div class="text-muted small mt-2">Fraud signal: <strong class="text-white"><?= htmlspecialchars((string) ($row['top_reason_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong> · blocked <strong class="text-white"><?= nr((int) ($row['blocked_attempts_total'] ?? 0)) ?></strong></div>
                                    <?php endif ?>
                                    <div class="leader-os-ai-access-panel <?= $ai_access_panel_class ?>">
                                        <div class="leader-os-ai-access-meta">
                                            <div class="text-muted small">AI pristup: <span class="leader-os-status-badge <?= htmlspecialchars((string) ($row['ai_access_tier_class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($row['ai_access_tier_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                            <div class="leader-os-signal-meter">
                                                <div class="leader-os-signal-bar"><span style="width: <?= nr($ai_signal_progress) ?>%"></span></div>
                                                <strong class="text-white"><?= nr((int) ($row['ai_access_growth_signal_30d'] ?? 0)) ?></strong>
                                            </div>
                                            <div class="leader-os-signal-thresholds">15 = Active · 50 = VIP</div>
                                            <div class="text-muted small">Shop <?= nr((int) ($row['ai_access_shop_clicks_30d'] ?? 0)) ?> · Funnel <?= nr((int) ($row['ai_access_funnel_registrations_30d'] ?? 0)) ?> · WhatsApp <?= nr((int) ($row['ai_access_whatsapp_contacts_30d'] ?? 0)) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars((string) ($row['ai_access_starter_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($row['ai_access_source_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if(!empty($row['ai_access_manual_expires_at'])): ?>
                                                <div class="text-muted small">Ručni status traje do: <?= \Altum\Date::get($row['ai_access_manual_expires_at'], 2) ?></div>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <!-- /Custom code: FC-2026-03-31 -->
                                </div>
                            </td>
                            <td>
                                <div class="mb-1"><span class="leader-os-status-badge status-<?= $row['status_class'] ?>"><?= $row['status_label'] ?></span></div>
                                <?php if($row['qualified']): ?>
                                    <div class="text-muted small"><?= l('admin_leader_operating_system.qualified_hint') ?></div>
                                <?php endif ?>
                            </td>
                            <td>
                                <div class="font-weight-bold"><?= $row['leader_os_score'] ?></div>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.table.risk_score') ?>: <?= $row['risk_score'] ?></div>
                            </td>
                            <td>
                                <div class="font-weight-bold"><?= nr((int) ($row['blocked_attempts_total'] ?? 0)) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) (($row['top_reason_title'] ?? '') ?: (($row['fraud_badge_label'] ?? '') ?: 'Clean')), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td>
                                <div class="font-weight-bold"><?= nr((int) ($row['app_quality_score'] ?? 0)) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($row['app_quality_stage_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> · <?= nr((int) ($row['app_signal_score'] ?? 0)) ?></div>
                            </td>
                            <td>
                                <div class="font-weight-bold"><?= nr($row['forever_shop_clicks_period']) ?></div>
                                <div class="text-muted small">90d: <?= nr($row['forever_shop_clicks_90d']) ?></div>
                            </td>
                            <td>
                                <div class="font-weight-bold <?= $growth_class ?>">
                                    <?= $growth_percent === null ? l('admin_index.biolink_qualified_watch.growth_new') : ($growth_percent > 0 ? '+' : '') . nr($growth_percent) . '%' ?>
                                </div>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.table.delta') ?>: <?= ($row['growth_difference'] > 0 ? '+' : '') . nr($row['growth_difference']) ?></div>
                            </td>
                            <td>
                                <div class="font-weight-bold"><?= nr($row['forever_registration_clicks_period']) ?></div>
                                <div class="text-muted small"><?= nr($row['registration_rate_percent']) ?>%</div>
                            </td>
                            <td><?= $row['strongest_country'] ?></td>
                            <td><?= $row['top_source_label'] ?></td>
                            <td><?= $row['last_click_at'] ? \Altum\Date::get($row['last_click_at'], 2) : '-' ?></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <a href="<?= $row['detail_url'] ?>" class="leader-os-link"><?= l('admin_leader_operating_system.open_detail_demo') ?></a>
                                    <a href="<?= $row['admin_user_url'] ?>" class="leader-os-link text-muted"><?= l('admin_index.biolink_qualified_watch.open_profile') ?></a>
                                    <?php if(!empty($row['ai_access_is_pro'])): ?>
                                        <div class="leader-os-ai-access-actions">
                                            <?php if(($row['ai_access_tier_key'] ?? '') !== 'pro_active' && ($row['ai_access_tier_key'] ?? '') !== 'pro_vip'): ?>
                                                <form action="<?= $leader_os_action_url ?>" method="post">
                                                    <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                                    <input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>" />
                                                    <input type="hidden" name="los_ai_unlock_action" value="pro_active" />
                                                    <button type="submit" class="btn btn-sm leader-os-action-button is-primary">Active</button>
                                                </form>
                                            <?php endif ?>

                                            <?php if(($row['ai_access_tier_key'] ?? '') !== 'pro_vip'): ?>
                                                <form action="<?= $leader_os_action_url ?>" method="post">
                                                    <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                                    <input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>" />
                                                    <input type="hidden" name="los_ai_unlock_action" value="pro_vip" />
                                                    <button type="submit" class="btn btn-sm leader-os-action-button is-success">VIP</button>
                                                </form>
                                            <?php endif ?>

                                            <?php if(!empty($row['ai_access_manual_tier'])): ?>
                                                <form action="<?= $leader_os_action_url ?>" method="post">
                                                    <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                                    <input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>" />
                                                    <input type="hidden" name="los_ai_unlock_action" value="auto" />
                                                    <button type="submit" class="btn btn-sm leader-os-action-button">Auto</button>
                                                </form>
                                            <?php endif ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted small mt-2">Ručni AI unlock dostupan je samo za aktivni PRO paket.</div>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>

            <?php if(($data->overview['pagination']['total_pages'] ?? 1) > 1): ?>
                <?php
                $base_query = [
                    'period' => $data->selected_period,
                    'search' => $data->search_query,
                    'status' => $data->selected_status,
                    'ai_status' => $data->selected_ai_status,
                    'anomaly_status' => $data->selected_anomaly_status,
                    'fraud_status' => $data->selected_fraud_status,
                    'sort' => $data->selected_sort,
                    'tab' => $data->selected_tab,
                ];
                $previous_page = max(1, $data->overview['pagination']['page'] - 1);
                $next_page = min($data->overview['pagination']['total_pages'], $data->overview['pagination']['page'] + 1);
                $current_page = (int) $data->overview['pagination']['page'];
                $total_pages = (int) $data->overview['pagination']['total_pages'];
                $page_start = max(1, $current_page - 2);
                $page_end = min($total_pages, $current_page + 2);

                if($page_end - $page_start < 4) {
                    if($page_start === 1) {
                        $page_end = min($total_pages, $page_start + 4);
                    }

                    if($page_end === $total_pages) {
                        $page_start = max(1, $page_end - 4);
                    }
                }
                ?>
                <!-- Custom code: FC-2026-03-31: Leader OS numeric pagination -->
                <div class="leader-os-pagination">
                    <div class="text-muted small">
                        <?= sprintf(l('admin_leader_operating_system.pagination_summary'), nr($data->overview['pagination']['page']), nr($data->overview['pagination']['total_pages'])) ?>
                    </div>

                    <div class="leader-os-pagination-links">
                        <?php if($current_page <= 1): ?>
                            <span class="btn btn-outline-light btn-sm disabled"><?= l('admin_leader_operating_system.pagination_previous') ?></span>
                        <?php else: ?>
                            <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($base_query, ['page' => $previous_page]))) ?>" class="btn btn-outline-light btn-sm">
                                <?= l('admin_leader_operating_system.pagination_previous') ?>
                            </a>
                        <?php endif ?>

                        <?php if($page_start > 1): ?>
                            <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($base_query, ['page' => 1]))) ?>" class="btn btn-outline-light btn-sm leader-os-page-link">1</a>
                            <?php if($page_start > 2): ?>
                                <span class="btn btn-outline-light btn-sm disabled">...</span>
                            <?php endif ?>
                        <?php endif ?>

                        <?php for($page_number = $page_start; $page_number <= $page_end; $page_number++): ?>
                            <?php if($page_number === $current_page): ?>
                                <span class="btn btn-outline-light btn-sm leader-os-page-link active"><?= nr($page_number) ?></span>
                            <?php else: ?>
                                <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($base_query, ['page' => $page_number]))) ?>" class="btn btn-outline-light btn-sm leader-os-page-link"><?= nr($page_number) ?></a>
                            <?php endif ?>
                        <?php endfor ?>

                        <?php if($page_end < $total_pages): ?>
                            <?php if($page_end < $total_pages - 1): ?>
                                <span class="btn btn-outline-light btn-sm disabled">...</span>
                            <?php endif ?>
                            <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($base_query, ['page' => $total_pages]))) ?>" class="btn btn-outline-light btn-sm leader-os-page-link"><?= nr($total_pages) ?></a>
                        <?php endif ?>

                        <?php if($current_page >= $total_pages): ?>
                            <span class="btn btn-outline-light btn-sm disabled"><?= l('admin_leader_operating_system.pagination_next') ?></span>
                        <?php else: ?>
                            <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($base_query, ['page' => $next_page]))) ?>" class="btn btn-outline-light btn-sm">
                                <?= l('admin_leader_operating_system.pagination_next') ?>
                            </a>
                        <?php endif ?>
                    </div>
                </div>
                <!-- /Custom code: FC-2026-03-31 -->
            <?php endif ?>
        <?php endif ?>
        <?php endif ?>
    </div>
</div>
<?php endif ?>

<?php if(in_array(($data->selected_tab ?? 'overview'), ['overview', 'ai', 'coaching'], true)): ?>
<div class="card leader-os-shell">
    <div class="card-body">
        <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.section_next') ?></div>
        <div class="row">
            <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.alert_group_manual_follow_up') ?></div>
                    <div class="h2 mb-2"><?= nr((int) ($data->overview['alerts']['totals']['manual_follow_up'] ?? 0)) ?></div>
                    <div><?= l('admin_leader_operating_system.placeholder_next_1') ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.alert_group_weekly_signal') ?></div>
                    <div class="h2 mb-2"><?= nr((int) ($data->overview['alerts']['totals']['weekly_signal_gaps'] ?? 0)) ?></div>
                    <div><?= l('admin_leader_operating_system.placeholder_next_2') ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.alert_group_execution_risk') ?></div>
                    <div class="h2 mb-2"><?= nr((int) ($data->overview['alerts']['totals']['execution_or_risk'] ?? 0)) ?></div>
                    <div><?= l('admin_leader_operating_system.placeholder_next_3') ?></div>
                </div>
            </div>
        </div>

        <div class="leader-os-panel mt-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="font-weight-bold mb-1"><?= l('admin_leader_operating_system.alert_title') ?></div>
                    <div class="text-muted small"><?= l('admin_leader_operating_system.alert_text') ?></div>
                </div>
            </div>

            <?php if(empty($data->overview['alerts']['rows'])): ?>
                <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.alert_empty') ?></div>
            <?php else: ?>
                <div class="leader-os-alert-list">
                    <?php foreach($data->overview['alerts']['rows'] as $alert_row): ?>
                        <div class="leader-os-alert-item">
                            <div>
                                <div class="font-weight-bold"><?= htmlspecialchars((string) ($alert_row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($alert_row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <a href="<?= $alert_row['detail_url'] ?>" class="leader-os-link"><?= l('admin_leader_operating_system.queue_open') ?></a>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <div class="leader-os-panel mt-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="font-weight-bold mb-1"><?= l('admin_leader_operating_system.recent_coaching_title') ?></div>
                    <div class="text-muted small"><?= l('admin_leader_operating_system.recent_coaching_text') ?></div>
                </div>
            </div>

            <?php if(empty($data->overview['recent_coaching_rows'])): ?>
                <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.recent_coaching_empty') ?></div>
            <?php else: ?>
                <div class="leader-os-coaching-list">
                    <?php foreach($data->overview['recent_coaching_rows'] as $coaching_row): ?>
                        <div class="leader-os-coaching-item">
                            <div>
                                <div class="font-weight-bold"><?= htmlspecialchars((string) ($coaching_row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($coaching_row['latest_mentor_event_summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small">
                                    <?= !empty($coaching_row['latest_mentor_event_admin']) ? htmlspecialchars((string) ($coaching_row['latest_mentor_event_admin'] ?? ''), ENT_QUOTES, 'UTF-8') . ' · ' : '' ?>
                                    <?= !empty($coaching_row['latest_mentor_event_at']) ? \Altum\Date::get($coaching_row['latest_mentor_event_at'], 2) : '-' ?>
                                </div>
                            </div>
                            <a href="<?= $coaching_row['detail_url'] ?>#leader-os-phase4" class="leader-os-link"><?= l('admin_leader_operating_system.queue_open') ?></a>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
<?php endif ?>

<div class="modal fade leader-os-modal" id="leader_os_drilldown_modal" tabindex="-1" role="dialog" aria-labelledby="leader_os_drilldown_modal_title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="text-uppercase small text-muted mb-1">LOS Drilldown</div>
                    <h5 class="modal-title mb-0" id="leader_os_drilldown_modal_title">Suradnici</h5>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="leader-os-modal-summary">
                    <div>
                        <div class="leader-os-modal-summary-label" id="leader_os_drilldown_modal_summary_label">Vrijednost signala</div>
                        <div class="text-muted small" id="leader_os_drilldown_modal_summary_note">Klik na ime otvara detalj suradnika.</div>
                    </div>
                    <div class="leader-os-modal-summary-value" id="leader_os_drilldown_modal_count">0</div>
                </div>
                <div class="leader-os-modal-list" id="leader_os_drilldown_modal_list">
                    <div class="leader-os-modal-empty">Nema dostupnih suradnika za ovaj signal.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('leader_os_drilldown_modal');
    const modalTitle = document.getElementById('leader_os_drilldown_modal_title');
    const modalCount = document.getElementById('leader_os_drilldown_modal_count');
    const modalList = document.getElementById('leader_os_drilldown_modal_list');
    const modalSummaryLabel = document.getElementById('leader_os_drilldown_modal_summary_label');
    const modalSummaryNote = document.getElementById('leader_os_drilldown_modal_summary_note');

    if (!modal || !modalTitle || !modalCount || !modalList || !modalSummaryLabel || !modalSummaryNote) {
        return;
    }

    const escapeHtml = (value) => {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const teamTrendChart = document.getElementById('leader-os-team-trend-chart');
    const teamTrendPeriods = Array.from(document.querySelectorAll('.leader-os-trend-period'));
    const teamTrendSummaryCards = Array.from(document.querySelectorAll('[data-trend-summary-card]'));
    const teamTrendNote = document.getElementById('leader-os-team-trend-note');
    const teamTrendRange = document.getElementById('leader-os-team-trend-range');
    const teamTrendTotalNodes = {
        clicks: document.querySelector('[data-trend-total="clicks"]'),
        registrations: document.querySelector('[data-trend-total="registrations"]'),
        leads: document.querySelector('[data-trend-total="leads"]'),
        blog_forever: document.querySelector('[data-trend-total="blog_forever"]')
    };

    const trendMetricConfig = [
        {key: 'clicks', label: 'Klikovi', color: '#57e389'},
        {key: 'registrations', label: 'Registracije', color: '#68b7ff'},
        {key: 'leads', label: 'Leadovi', color: '#f8d060'},
        {key: 'blog_forever', label: 'Blog -> Forever', color: '#3fe0c8'}
    ];

    const teamTrendRows = (() => {
        if (!teamTrendChart) {
            return [];
        }

        try {
            const raw = teamTrendChart.getAttribute('data-trend') || '[]';
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    })();

    const teamTrendSummaryDrilldowns = (() => {
        if (!teamTrendChart) {
            return {};
        }

        try {
            const raw = <?= json_encode($data->overview['team_trend']['summary_drilldowns'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            return raw && typeof raw === 'object' ? raw : {};
        } catch (error) {
            return {};
        }
    })();

    const formatTrendNumber = (value) => {
        const numericValue = Number(value || 0);
        if (typeof Intl !== 'undefined' && Intl.NumberFormat) {
            return new Intl.NumberFormat('hr-HR').format(numericValue);
        }

        return String(numericValue);
    };

    const buildTrendPath = (points) => {
        return points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
    };

    const renderTeamTrendChart = (days) => {
        if (!teamTrendChart || !teamTrendRows.length) {
            return;
        }

        const windowDays = Number(days || 7);
        const rows = teamTrendRows.slice(-windowDays);
        const width = 920;
        const height = 300;
        const padding = {top: 18, right: 18, bottom: 38, left: 28};
        const plotWidth = width - padding.left - padding.right;
        const plotHeight = height - padding.top - padding.bottom;
        const maxValue = Math.max(5, ...rows.flatMap((row) => trendMetricConfig.map((metric) => Number(row[metric.key] || 0))));
        const safeMax = maxValue <= 0 ? 5 : maxValue;
        const xStep = rows.length > 1 ? plotWidth / (rows.length - 1) : plotWidth;

        const horizontalGrid = [0, 0.25, 0.5, 0.75, 1].map((ratio) => {
            const y = padding.top + plotHeight * ratio;
            const value = Math.round(safeMax * (1 - ratio));

            return `
                <line class="leader-os-trend-grid-line" x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}"></line>
                <text class="leader-os-trend-axis-text" x="${padding.left - 8}" y="${y + 4}" text-anchor="end">${formatTrendNumber(value)}</text>
            `;
        }).join('');

        const labelIndexes = new Set([0, Math.max(0, Math.floor((rows.length - 1) * 0.25)), Math.max(0, Math.floor((rows.length - 1) * 0.5)), Math.max(0, Math.floor((rows.length - 1) * 0.75)), Math.max(0, rows.length - 1)]);
        const verticalLabels = rows.map((row, index) => {
            if (!labelIndexes.has(index)) {
                return '';
            }

            const x = padding.left + xStep * index;
            return `<text class="leader-os-trend-axis-text" x="${x}" y="${height - 12}" text-anchor="${index === 0 ? 'start' : (index === rows.length - 1 ? 'end' : 'middle')}">${escapeHtml(row.label || '')}</text>`;
        }).join('');

        const seriesMarkup = trendMetricConfig.map((metric) => {
            const points = rows.map((row, index) => {
                const value = Number(row[metric.key] || 0);
                const x = padding.left + xStep * index;
                const y = padding.top + plotHeight - ((value / safeMax) * plotHeight);
                return {x, y, value};
            });

            const path = buildTrendPath(points);
            const dots = points.map((point) => `<circle class="leader-os-trend-series-dot" cx="${point.x}" cy="${point.y}" r="4" fill="${metric.color}"></circle>`).join('');

            return `
                <path class="leader-os-trend-series-line is-${metric.key === 'blog_forever' ? 'blog' : metric.key}" d="${path}"></path>
                ${dots}
            `;
        }).join('');

        teamTrendChart.innerHTML = `
            ${horizontalGrid}
            ${seriesMarkup}
            ${verticalLabels}
        `;

        const totals = rows.reduce((carry, row) => {
            trendMetricConfig.forEach((metric) => {
                carry[metric.key] += Number(row[metric.key] || 0);
            });
            return carry;
        }, {clicks: 0, registrations: 0, leads: 0, blog_forever: 0});

        Object.keys(teamTrendTotalNodes).forEach((key) => {
            if (teamTrendTotalNodes[key]) {
                teamTrendTotalNodes[key].textContent = formatTrendNumber(totals[key] || 0);
            }
        });

        if (teamTrendNote) {
            teamTrendNote.textContent = `Pregled zadnjih ${windowDays} dana.`;
        }

        if (teamTrendRange) {
            const first = rows[0]?.label || '';
            const last = rows[rows.length - 1]?.label || '';
            teamTrendRange.textContent = first && last ? `${first} → ${last}` : '';
        }

        teamTrendSummaryCards.forEach((card) => {
            const metricKey = card.getAttribute('data-trend-summary-card') || '';
            const payload = teamTrendSummaryDrilldowns[String(windowDays)]?.[metricKey] || null;

            if (!payload) {
                card.setAttribute('data-drilldown', JSON.stringify({title: metricKey, items: []}));
                return;
            }

            card.setAttribute('data-drilldown', JSON.stringify({
                title: payload.title || metricKey,
                items: Array.isArray(payload.items) ? payload.items : []
            }));
        });
    };

    if (teamTrendChart && teamTrendPeriods.length && teamTrendRows.length) {
        renderTeamTrendChart(7);

        teamTrendPeriods.forEach((button) => {
            button.addEventListener('click', function () {
                const days = Number(this.getAttribute('data-days') || 7);

                teamTrendPeriods.forEach((item) => item.classList.remove('is-active'));
                this.classList.add('is-active');
                renderTeamTrendChart(days);
            });
        });
    }

    const statusChartRoot = document.getElementById('leader-os-status-chart');
    const statusGraph = document.getElementById('leader-os-status-graph');
    const statusPeriodButtons = Array.from(document.querySelectorAll('[data-status-days]'));
    const statusChartNote = document.getElementById('leader-os-status-chart-note');
    const statusDistributionRanges = (() => {
        if (!statusChartRoot) {
            return {};
        }

        try {
            const raw = statusChartRoot.getAttribute('data-status-distribution') || '{}';
            const parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    })();

    const renderStatusChart = (days) => {
        if (!statusChartRoot) {
            return;
        }

        const rows = Array.isArray(statusDistributionRanges[String(days)]) ? statusDistributionRanges[String(days)] : [];
        const maxTotal = Math.max(1, ...rows.map((row) => Number(row.total || 0)));

        statusChartRoot.innerHTML = rows.map((row) => {
            const total = Number(row.total || 0);
            const width = Math.max(2, Math.min(100, (total / maxTotal) * 100));
            const key = String(row.key || 'stable');
            return `
                <div class="leader-os-status-chart-row">
                    <div class="leader-os-status-chart-label">${escapeHtml(row.label || key)}</div>
                    <div class="leader-os-status-chart-track">
                        <span class="leader-os-status-chart-fill is-${escapeHtml(key)}" style="width:${width}%"></span>
                    </div>
                    <div class="leader-os-status-chart-value">${formatTrendNumber(total)}</div>
                </div>
            `;
        }).join('');

        if (statusChartNote) {
            statusChartNote.textContent = `Pregled rasporeda tima u zadnjih ${days} dana prema activity prozoru.`;
        }

        if (statusGraph) {
            const width = 640;
            const height = 280;
            const padding = {top: 28, right: 22, bottom: 72, left: 54};
            const plotWidth = width - padding.left - padding.right;
            const plotHeight = height - padding.top - padding.bottom;
            const barGap = 24;
            const barWidth = rows.length ? Math.max(44, (plotWidth - (barGap * (rows.length - 1))) / rows.length) : 48;

            const colorByKey = {
                inactive: '#8a9ab0',
                stable: '#45d6b2',
                rising: '#63a8ff',
                high_potential: '#f5c85f',
                risk: '#f38b67'
            };

            const gradientDefs = rows.map((row) => {
                const key = String(row.key || 'stable');
                const color = colorByKey[key] || '#68b7ff';
                return `
                    <linearGradient id="leader-os-status-grad-${key}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="${color}" stop-opacity="0.98"></stop>
                        <stop offset="100%" stop-color="${color}" stop-opacity="0.72"></stop>
                    </linearGradient>
                `;
            }).join('');

            const makeLabelTspans = (label, x, y) => {
                const words = String(label || '').split(' ');
                if (words.length <= 1) {
                    return `<text class="leader-os-status-graph-label" x="${x}" y="${y}" text-anchor="middle">${escapeHtml(label || '')}</text>`;
                }

                const firstLine = escapeHtml(words.slice(0, Math.ceil(words.length / 2)).join(' '));
                const secondLine = escapeHtml(words.slice(Math.ceil(words.length / 2)).join(' '));

                return `
                    <text class="leader-os-status-graph-label" x="${x}" y="${y}" text-anchor="middle">
                        <tspan x="${x}" dy="0">${firstLine}</tspan>
                        <tspan x="${x}" dy="15">${secondLine}</tspan>
                    </text>
                `;
            };

            const grid = [0, 0.5, 1].map((ratio) => {
                const y = padding.top + plotHeight * ratio;
                const value = Math.round(maxTotal * (1 - ratio));
                return `
                    <line class="leader-os-status-graph-grid" x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}"></line>
                    <text class="leader-os-status-graph-axis" x="${padding.left - 12}" y="${y + 4}" text-anchor="end">${formatTrendNumber(value)}</text>
                `;
            }).join('');

            const bars = rows.map((row, index) => {
                const total = Number(row.total || 0);
                const x = padding.left + index * (barWidth + barGap);
                const barHeight = maxTotal > 0 ? (total / maxTotal) * plotHeight : 0;
                const y = padding.top + plotHeight - barHeight;
                const key = String(row.key || 'stable');
                const centerX = x + (barWidth / 2);
                const valueY = Math.max(padding.top - 8, y - 12);
                const labelMarkup = makeLabelTspans(row.label || key, centerX, height - 26);

                return `
                    <rect x="${x}" y="${padding.top}" width="${barWidth}" height="${plotHeight}" rx="16" fill="rgba(255,255,255,0.03)"></rect>
                    <rect x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" rx="16" fill="url(#leader-os-status-grad-${key})"></rect>
                    <text class="leader-os-status-graph-value" x="${centerX}" y="${valueY}" text-anchor="middle">${formatTrendNumber(total)}</text>
                    ${labelMarkup}
                `;
            }).join('');

            statusGraph.innerHTML = `<defs>${gradientDefs}</defs>${grid}${bars}`;
        }
    };

    if (statusChartRoot && statusPeriodButtons.length) {
        renderStatusChart(30);

        statusPeriodButtons.forEach((button) => {
            button.addEventListener('click', function () {
                const days = Number(this.getAttribute('data-status-days') || 30);
                statusPeriodButtons.forEach((item) => item.classList.remove('is-active'));
                this.classList.add('is-active');
                renderStatusChart(days);
            });
        });
    }

    teamTrendSummaryCards.forEach((card) => {
        card.addEventListener('click', function () {
            populateDrilldown(this);
            openModal();
        });

        card.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            populateDrilldown(this);
            openModal();
        });
    });

    const openModal = () => {
        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(modal).modal('show');
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    };

    const populateDrilldown = (trigger) => {
        if (!trigger) {
            return;
        }

        let payload = null;

        try {
            payload = JSON.parse(trigger.getAttribute('data-drilldown') || '{}');
        } catch (error) {
            payload = null;
        }

        const items = payload && Array.isArray(payload.items) ? payload.items : [];
        modalTitle.textContent = payload && payload.title ? payload.title : 'Suradnici';
        modalSummaryLabel.textContent = payload && payload.summary_label ? payload.summary_label : 'Vrijednost signala';
        modalCount.textContent = String(payload && payload.summary_value ? payload.summary_value : items.length);
        modalSummaryNote.textContent = payload && payload.summary_note
            ? payload.summary_note
            : `Prikazano suradnika: ${items.length}`;

        if (!items.length) {
            modalList.innerHTML = '<div class="leader-os-modal-empty">Nema dostupnih suradnika za ovaj signal.</div>';
            return;
        }

        modalList.innerHTML = items.map((item) => {
            const detailUrl = item.detail_url ? `<a href="${escapeHtml(item.detail_url)}" class="leader-os-modal-open">Otvori detalj</a>` : '';
            const statusLabel = item.status_label ? `<div class="text-muted small">${escapeHtml(item.status_label)}</div>` : '';
            const meta = item.meta ? `<div class="text-muted small mt-1">${escapeHtml(item.meta)}</div>` : '';
            const metricDisplay = item.metric_display ? item.metric_display : (item.metric != null ? item.metric : '');

            return `
                <div class="leader-os-modal-item">
                    <div class="leader-os-modal-item-top">
                        <div>
                            <strong>${escapeHtml(item.name || '')}</strong>
                            ${statusLabel}
                        </div>
                        <div class="text-right">
                            <div class="leader-os-modal-item-metric">${escapeHtml(metricDisplay)}</div>
                            <div class="mt-2">${detailUrl}</div>
                        </div>
                    </div>
                    ${meta}
                </div>
            `;
        }).join('');
    };

    document.querySelectorAll('[data-drilldown]').forEach((trigger) => {
        trigger.addEventListener('click', function () {
            populateDrilldown(this);
        });

        trigger.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            populateDrilldown(this);
            openModal();
        });
    });

    const targetMode = document.getElementById('leader_os_message_target_mode');
    const prioritySelect = document.getElementById('leader_os_target_user_id');
    const groupSelect = document.getElementById('leader_os_target_group');
    const messageTitle = document.getElementById('leader_os_message_title');
    const messageDescription = document.getElementById('leader_os_message_description');
    const singleTargetWrap = document.getElementById('leader_os_single_target_wrap');
    const groupTargetWrap = document.getElementById('leader_os_group_target_wrap');

    const syncMessageCenterMode = () => {
        if (!targetMode) {
            return;
        }

        const isGroup = targetMode.value === 'group';

        if (singleTargetWrap) {
            singleTargetWrap.style.display = isGroup ? 'none' : '';
        }

        if (groupTargetWrap) {
            groupTargetWrap.style.display = isGroup ? '' : 'none';
        }

        if (prioritySelect) {
            prioritySelect.required = !isGroup;
        }

        if (groupSelect) {
            groupSelect.required = isGroup;
        }
    };

    if (targetMode) {
        targetMode.addEventListener('change', syncMessageCenterMode);
        syncMessageCenterMode();
    }

    document.querySelectorAll('.leader-os-quick-message-trigger').forEach((trigger) => {
        trigger.addEventListener('click', function () {
            if (!prioritySelect) {
                return;
            }

            const userId = this.getAttribute('data-user-id') || '';

            if (targetMode) {
                targetMode.value = 'single';
                syncMessageCenterMode();
            }

            if (userId !== '') {
                prioritySelect.value = userId;
                prioritySelect.dispatchEvent(new Event('change'));
            }
        });
    });

    document.querySelectorAll('.leader-os-template-trigger').forEach((trigger) => {
        trigger.addEventListener('click', function () {
            if (targetMode) {
                targetMode.value = this.getAttribute('data-mode') || 'group';
                syncMessageCenterMode();
            }

            if (groupSelect && this.getAttribute('data-group')) {
                groupSelect.value = this.getAttribute('data-group') || '';
            }

            if (messageTitle) {
                messageTitle.value = this.getAttribute('data-title') || '';
            }

            if (messageDescription) {
                messageDescription.value = this.getAttribute('data-message') || '';
            }
        });
    });

    document.querySelectorAll('.leader-os-support-toggle').forEach((trigger) => {
        trigger.addEventListener('click', function () {
            const selector = this.getAttribute('data-target') || '';

            if (!selector) {
                return;
            }

            const items = Array.from(document.querySelectorAll(selector));

            if (!items.length) {
                return;
            }

            const shouldOpen = items.some((item) => item.classList.contains('leader-os-is-hidden'));

            items.forEach((item) => {
                item.classList.toggle('leader-os-is-hidden', !shouldOpen);
            });

            this.textContent = shouldOpen
                ? (this.getAttribute('data-close-label') || 'Sakrij')
                : (this.getAttribute('data-open-label') || 'Otvori');
        });
    });

    const supportTitle = document.getElementById('leader_os_support_title');
    const supportMeta = document.getElementById('leader_os_support_meta');
    const supportStatusBadge = document.getElementById('leader_os_support_status_badge');
    const supportWebinarBadge = document.getElementById('leader_os_support_webinar_badge');
    const supportWebinarConfirmedBadge = document.getElementById('leader_os_support_webinar_confirmed_badge');
    const supportAiSummary = document.getElementById('leader_os_support_ai_summary');
    const supportAiIssue = document.getElementById('leader_os_support_ai_issue');
    const supportAiRecommendation = document.getElementById('leader_os_support_ai_recommendation');
    const supportAiWebinar = document.getElementById('leader_os_support_ai_webinar');
    const supportLastActivity = document.getElementById('leader_os_support_last_activity');
    const supportMessageCount = document.getElementById('leader_os_support_message_count');
    const supportConversation = document.getElementById('leader_os_support_conversation');
    const supportReplyMessage = document.getElementById('leader_os_support_ticket_reply_message');
    const supportReplyTitle = document.getElementById('leader_os_reply_title');
    const supportReplyTicketId = document.getElementById('leader_os_reply_ticket_id');
    const supportGenerateAiTicketId = document.getElementById('leader_os_generate_ai_ticket_id');
    const supportGenerateAiButton = document.getElementById('leader_os_generate_ai_button');
    const supportStatusTicketId = document.getElementById('leader_os_status_ticket_id');
    const supportStatusNext = document.getElementById('leader_os_status_next');
    const supportStatusButton = document.getElementById('leader_os_status_button');
    const supportWebinarTicketId = document.getElementById('leader_os_webinar_ticket_id');
    const supportWebinarConfirmedInput = document.getElementById('leader_os_webinar_confirmed');
    const supportWebinarButton = document.getElementById('leader_os_webinar_button');
    const supportWorkspaceRoot = document.getElementById('leader-os-support-active-ticket');

    const setStatusBadgeClass = (element, statusKey) => {
        if (!element) {
            return;
        }

        element.classList.remove('status-dark', 'status-success', 'status-warning');
        if (statusKey === 'closed') {
            element.classList.add('status-dark');
        } else if (statusKey === 'answered') {
            element.classList.add('status-success');
        } else {
            element.classList.add('status-warning');
        }
    };

    const renderSupportConversation = (messages) => {
        if (!Array.isArray(messages) || !supportConversation) {
            return;
        }

        supportConversation.innerHTML = messages.map((message) => {
            const isAdmin = Number(message.is_admin_reply || 0) === 1 || message.is_admin_reply === true;
            const authorLabel = escapeHtml(message.author_label || (isAdmin ? 'Admin / mentor' : 'Suradnik'));
            const datetime = escapeHtml(message.datetime || '-');
            const body = escapeHtml(message.message || '').replace(/\\n/g, '<br>');
            const attachment = escapeHtml(message.attachment || '');
            const attachmentHtml = attachment ? `<a href="<?= \Altum\Uploads::get_full_url('feedback_tickets') ?>${attachment}" target="_blank" class="d-inline-block mt-2"><?= l('feedback_tickets.view_attachment') ?></a>` : '';

            return `
                <div class="leader-os-conversation-item ${isAdmin ? 'is-admin' : ''}">
                    <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                        <strong>${authorLabel}</strong>
                        <span class="text-muted small">${datetime}</span>
                    </div>
                    <div class="leader-os-conversation-message">${body}</div>
                    ${attachmentHtml}
                </div>
            `;
        }).join('');

    };

    document.querySelectorAll('[data-support-ticket]').forEach((ticketCard) => {
        ticketCard.addEventListener('click', function () {
            const rawPayload = this.getAttribute('data-support-ticket');
            if (!rawPayload) {
                return;
            }

            let ticket = null;
            try {
                ticket = JSON.parse(rawPayload);
            } catch (error) {
                return;
            }

            if (!ticket) {
                return;
            }

            document.querySelectorAll('[data-support-ticket]').forEach((item) => {
                item.classList.remove('border', 'border-primary');
            });
            this.classList.add('border', 'border-primary');

            if (supportTitle) {
                supportTitle.textContent = `#${ticket.feedback_ticket_id || 0} · ${ticket.subject || ''}`;
            }

            if (supportMeta) {
                supportMeta.textContent = `${ticket.user_name || ''} · ${ticket.user_email || ''} · ${ticket.category_label || ''}`;
            }

            if (supportStatusBadge) {
                supportStatusBadge.textContent = ticket.status_label || '';
                setStatusBadgeClass(supportStatusBadge, ticket.status_key || 'open');
            }

            if (supportWebinarBadge) {
                supportWebinarBadge.classList.toggle('d-none', !ticket.is_webinar_topic_suggestion);
            }

            if (supportWebinarConfirmedBadge) {
                supportWebinarConfirmedBadge.classList.toggle('d-none', !ticket.is_webinar_topic_confirmed);
            }

            const aiInsight = ticket.ai_insight || {};

            if (supportAiSummary) {
                supportAiSummary.textContent = aiInsight.summary || '-';
            }

            if (supportAiIssue) {
                supportAiIssue.textContent = aiInsight.core_issue || '-';
            }

            if (supportAiRecommendation) {
                supportAiRecommendation.textContent = aiInsight.recommended_action || '-';
            }

            if (supportAiWebinar) {
                supportAiWebinar.textContent = `${aiInsight.webinar_candidate || 'ne'} · ${aiInsight.webinar_reason || '-'}`;
            }

            if (supportLastActivity) {
                supportLastActivity.textContent = ticket.last_datetime_display || '-';
            }

            if (supportMessageCount) {
                supportMessageCount.textContent = String(ticket.message_count || 0);
            }

            if (supportReplyMessage) {
                supportReplyMessage.value = aiInsight.suggested_reply || '';
            }

            if (supportReplyTitle) {
                supportReplyTitle.value = `Odgovor na tvoj upit: ${ticket.subject || ''}`;
            }

            if (supportReplyTicketId) {
                supportReplyTicketId.value = ticket.feedback_ticket_id || 0;
            }

            if (supportGenerateAiTicketId) {
                supportGenerateAiTicketId.value = ticket.feedback_ticket_id || 0;
            }

            if (supportGenerateAiButton) {
                supportGenerateAiButton.textContent = (aiInsight.source || 'heuristic') === 'ai' ? 'Osvježi AI' : 'Generiraj AI';
            }

            if (supportStatusTicketId) {
                supportStatusTicketId.value = ticket.feedback_ticket_id || 0;
            }

            if (supportStatusNext) {
                supportStatusNext.value = (ticket.status_key || 'open') === 'closed' ? 'open' : 'closed';
            }

            if (supportStatusButton) {
                supportStatusButton.textContent = (ticket.status_key || 'open') === 'closed' ? 'Ponovno otvori' : 'Označi kao riješeno';
            }

            if (supportWebinarTicketId) {
                supportWebinarTicketId.value = ticket.feedback_ticket_id || 0;
            }

            if (supportWebinarConfirmedInput) {
                supportWebinarConfirmedInput.value = ticket.is_webinar_topic_confirmed ? '0' : '1';
            }

            if (supportWebinarButton) {
                supportWebinarButton.textContent = ticket.is_webinar_topic_confirmed ? 'Makni iz webinara' : 'Uvrsti u webinar';
                supportWebinarButton.classList.toggle('is-success', !!ticket.is_webinar_topic_confirmed);
            }

            renderSupportConversation(Array.isArray(ticket.conversation) ? ticket.conversation : []);

            if (supportWorkspaceRoot) {
                supportWorkspaceRoot.scrollIntoView({behavior: 'smooth', block: 'start'});
            }

            const serverUrl = this.getAttribute('data-support-ticket-url') || '';
            if (serverUrl !== '') {
                window.history.replaceState({}, '', `${serverUrl}#leader-os-support-active-ticket`);
            }
        });
    });

});
</script>
<!-- /Custom code: FC-2026-03-31 -->
