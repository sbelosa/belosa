<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-03-31: Leader Operating System detail shell -->
<style>
    .leader-os-detail-page {
        color: #ecf3ff;
    }

    .leader-os-detail-shell {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1.15rem;
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.96) 0%, rgba(10, 15, 28, 0.98) 100%);
        box-shadow: 0 1.5rem 3rem rgba(2, 6, 23, 0.32);
        overflow: hidden;
    }

    .leader-os-detail-shell,
    .leader-os-detail-shell h1,
    .leader-os-detail-shell h2,
    .leader-os-detail-shell h3,
    .leader-os-detail-shell .h1,
    .leader-os-detail-shell .h2,
    .leader-os-detail-shell .h3,
    .leader-os-detail-shell .h4,
    .leader-os-detail-shell .h5,
    .leader-os-detail-shell .h6 {
        color: #ecf3ff;
    }

    .leader-os-detail-shell .card-body {
        padding: 1.1rem;
    }

    .leader-os-detail-panel {
        height: 100%;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(11, 18, 32, 0.72);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
        padding: 1rem;
    }

    .leader-os-detail-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(96, 165, 250, 0.18);
        background: rgba(15, 23, 42, 0.72);
        color: #dceaff;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .leader-os-detail-chip.is-subtle {
        padding: 0.28rem 0.55rem;
        font-size: 0.72rem;
        border-color: rgba(148, 163, 184, 0.16);
        background: rgba(255, 255, 255, 0.05);
        color: #ecf3ff;
    }

    .leader-os-detail-note {
        border-radius: 0.95rem;
        border: 1px solid rgba(125, 211, 252, 0.14);
        background: linear-gradient(90deg, rgba(8, 47, 73, 0.34) 0%, rgba(15, 23, 42, 0.18) 100%);
        color: #dceaff;
        padding: 0.78rem 0.9rem;
        font-size: 0.84rem;
        line-height: 1.45;
    }

    .leader-os-cockpit-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.95rem;
    }

    .leader-os-cockpit-card {
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: linear-gradient(180deg, rgba(9, 16, 29, 0.84) 0%, rgba(14, 22, 37, 0.94) 100%);
        padding: 1rem;
        min-height: 100%;
    }

    .leader-os-cockpit-label {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        color: rgba(191, 211, 238, 0.78);
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .leader-os-cockpit-help {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.05rem;
        height: 1.05rem;
        border-radius: 999px;
        border: 1px solid rgba(125, 211, 252, 0.2);
        background: rgba(14, 116, 144, 0.18);
        color: #dff6ff;
        font-size: 0.7rem;
        font-weight: 700;
        cursor: help;
        flex-shrink: 0;
    }

    .leader-os-cockpit-value {
        font-size: 2rem;
        line-height: 1;
        font-weight: 700;
        color: #f8fbff;
        margin-top: 0.55rem;
    }

    .leader-os-cockpit-meta {
        color: rgba(191, 211, 238, 0.78);
        font-size: 0.86rem;
        margin-top: 0.45rem;
        line-height: 1.5;
    }

    .leader-os-cockpit-list {
        display: flex;
        flex-direction: column;
        gap: 0.78rem;
        margin-top: 0.85rem;
    }

    .leader-os-cockpit-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        padding-bottom: 0.72rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.09);
    }

    .leader-os-cockpit-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .leader-os-cockpit-item span {
        color: rgba(191, 211, 238, 0.72);
        font-size: 0.84rem;
    }

    .leader-os-cockpit-item strong {
        color: #f8fbff;
        font-size: 0.9rem;
        line-height: 1.45;
        text-align: right;
    }

    .leader-os-cockpit-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    .leader-os-cockpit-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        margin-top: 0.85rem;
    }

    .leader-os-cockpit-summary-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(0, 0.9fr);
        gap: 1rem;
    }

    .leader-os-cockpit-spotlight {
        border-radius: 0.95rem;
        border: 1px solid rgba(96, 165, 250, 0.16);
        background: rgba(8, 47, 73, 0.24);
        padding: 0.9rem;
        margin-top: 0.9rem;
    }

    .leader-os-cockpit-spotlight + .leader-os-cockpit-spotlight {
        margin-top: 0.75rem;
    }

    .leader-os-cockpit-spotlight-title {
        color: #9ddcff;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 0.42rem;
    }

    .leader-os-cockpit-spotlight-text {
        color: #f8fbff;
        font-size: 0.92rem;
        line-height: 1.55;
    }

    .leader-os-anomaly-score {
        font-size: 2.4rem;
        line-height: 1;
        font-weight: 700;
        color: #f8fbff;
    }

    .leader-os-anomaly-item {
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(7, 12, 24, 0.62);
        padding: 0.95rem;
    }

    .leader-os-detail-shell .text-muted,
    .leader-os-detail-panel .text-muted {
        color: rgba(191, 211, 238, 0.72) !important;
    }

    .leader-os-detail-page .text-body,
    .leader-os-detail-page .text-dark,
    .leader-os-detail-page .text-reset,
    .leader-os-detail-page .text-black-50,
    .leader-os-detail-page .text-secondary {
        color: rgba(220, 232, 245, 0.82) !important;
    }

    .leader-os-detail-page .text-success {
        color: #86efac !important;
    }

    .leader-os-detail-page .text-info,
    .leader-os-detail-page .text-primary {
        color: #89dbff !important;
    }

    .leader-os-detail-page .text-warning {
        color: #fcd97b !important;
    }

    .leader-os-detail-page .text-danger {
        color: #fca5a5 !important;
    }

    .leader-os-detail-page label,
    .leader-os-detail-page .custom-control-label,
    .leader-os-detail-page .form-check-label {
        color: #edf5ff;
    }

    .leader-os-detail-page .form-control,
    .leader-os-detail-page .custom-select,
    .leader-os-detail-page .input-group-text {
        border-color: rgba(148, 163, 184, 0.16);
        background: linear-gradient(180deg, rgba(25, 37, 54, 0.92) 0%, rgba(18, 28, 41, 0.99) 100%);
        color: #edf5ff;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .leader-os-detail-page .form-control::placeholder {
        color: rgba(201, 217, 234, 0.56);
    }

    .leader-os-detail-page .form-control:focus,
    .leader-os-detail-page .custom-select:focus {
        background: linear-gradient(180deg, rgba(29, 45, 66, 0.96) 0%, rgba(18, 28, 41, 1) 100%);
        color: #f8fbff;
        border-color: rgba(124, 200, 255, 0.45);
        box-shadow: 0 0 0 0.2rem rgba(124, 200, 255, 0.08);
    }

    .leader-os-detail-page .custom-select option {
        color: #111827;
        background: #ffffff;
    }

    .leader-os-detail-page input[type="file"].form-control {
        padding: 0.4rem 0.55rem;
    }

    .leader-os-detail-page input[type="file"].form-control::file-selector-button {
        margin-right: 0.75rem;
        border: 0;
        border-radius: 0.7rem;
        padding: 0.42rem 0.8rem;
        background: linear-gradient(180deg, rgba(56, 189, 248, 0.24) 0%, rgba(37, 99, 235, 0.34) 100%);
        color: #f8fbff;
        font-weight: 700;
    }

    .leader-os-detail-page .alert {
        border-radius: 1rem;
        border-width: 1px;
        color: #f5f9ff;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .leader-os-detail-page .alert-success {
        background: linear-gradient(180deg, rgba(22, 101, 52, 0.28) 0%, rgba(10, 30, 20, 0.94) 100%);
        border-color: rgba(34, 197, 94, 0.28);
    }

    .leader-os-detail-page .alert-danger {
        background: linear-gradient(180deg, rgba(127, 29, 29, 0.32) 0%, rgba(44, 14, 20, 0.94) 100%);
        border-color: rgba(248, 113, 113, 0.32);
    }

    .leader-os-detail-page .alert-warning {
        background: linear-gradient(180deg, rgba(120, 53, 15, 0.3) 0%, rgba(42, 24, 10, 0.94) 100%);
        border-color: rgba(251, 191, 36, 0.32);
    }

    .leader-os-detail-page .alert-info {
        background: linear-gradient(180deg, rgba(30, 64, 175, 0.28) 0%, rgba(16, 28, 56, 0.94) 100%);
        border-color: rgba(96, 165, 250, 0.3);
    }

    .leader-os-detail-page .alert,
    .leader-os-detail-page .alert-heading,
    .leader-os-detail-page .alert p,
    .leader-os-detail-page .alert strong,
    .leader-os-detail-page .alert a,
    .leader-os-detail-page .alert .alert-link {
        color: #f5f9ff !important;
    }

    .leader-os-detail-page .close {
        color: #f8fbff;
        text-shadow: none;
        opacity: 0.84;
    }

    .leader-os-detail-kpi {
        height: 100%;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(8, 13, 26, 0.76);
        padding: 1rem;
    }

    .leader-os-detail-status {
        display: inline-flex;
        align-items: center;
        padding: 0.38rem 0.75rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(30, 41, 59, 0.72);
        color: #ecf3ff;
    }

    .leader-os-detail-status.status-success {
        background: rgba(22, 101, 52, 0.28);
        border-color: rgba(34, 197, 94, 0.28);
    }

    .leader-os-detail-status.status-warning {
        background: rgba(120, 53, 15, 0.28);
        border-color: rgba(251, 191, 36, 0.28);
    }

    .leader-os-detail-status.status-info {
        background: rgba(30, 64, 175, 0.28);
        border-color: rgba(96, 165, 250, 0.3);
    }

    .leader-os-detail-status.status-dark {
        background: rgba(31, 41, 55, 0.82);
        border-color: rgba(75, 85, 99, 0.35);
    }

    .leader-os-detail-periods {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .leader-os-detail-period-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 6rem;
        padding: 0.55rem 0.9rem;
        border-radius: 999px;
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.05);
        text-decoration: none;
    }

    .leader-os-detail-period-link.active {
        border-color: rgba(124, 200, 255, 0.55);
        background: rgba(124, 200, 255, 0.16);
    }

    .leader-os-detail-period-link:hover,
    .leader-os-detail-period-link:focus {
        color: #ffffff;
        text-decoration: none;
        border-color: rgba(124, 200, 255, 0.45);
    }

    .leader-os-detail-list {
        display: grid;
        gap: 0.7rem;
    }

    .leader-os-detail-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: 0.7rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    }

    .leader-os-detail-list-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .leader-os-detail-meter {
        height: 0.45rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        overflow: hidden;
        margin-top: 0.4rem;
    }

    .leader-os-detail-meter > span {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #7cc8ff 0%, #f8fafc 100%);
    }

    .leader-os-detail-action {
        border-color: rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .leader-os-detail-action:hover,
    .leader-os-detail-action:focus {
        color: #ffffff;
        border-color: rgba(124, 200, 255, 0.55);
        background: rgba(124, 200, 255, 0.18);
    }

    .leader-os-detail-chart-wrap {
        position: relative;
        min-height: 290px;
    }

    .leader-os-country-periods {
        display: inline-flex;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .leader-os-country-period {
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff;
        border-radius: 999px;
        padding: 0.38rem 0.72rem;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
    }

    .leader-os-country-period.active {
        border-color: rgba(45, 212, 191, 0.45);
        background: rgba(45, 212, 191, 0.16);
        color: #dffefa;
    }

    .leader-os-country-table-wrap {
        max-height: 290px;
        overflow: auto;
    }

    .leader-os-country-table {
        width: 100%;
        color: #ecf3ff;
        margin-bottom: 0;
    }

    .leader-os-country-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: rgba(8, 13, 26, 0.96);
        color: rgba(191, 211, 238, 0.82);
        border-color: rgba(148, 163, 184, 0.08);
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .leader-os-country-table td {
        border-color: rgba(148, 163, 184, 0.08);
        vertical-align: middle;
    }

    .leader-os-country-table tfoot th,
    .leader-os-country-table tfoot td {
        position: sticky;
        bottom: 0;
        z-index: 1;
        background:
            linear-gradient(180deg, rgba(18, 41, 61, 0.98) 0%, rgba(10, 22, 36, 1) 100%);
        color: #f4f9ff;
        border-top: 1px solid rgba(63, 224, 200, 0.34);
        border-bottom: 0;
        font-weight: 800;
        box-shadow: 0 -10px 18px rgba(2, 6, 23, 0.32), inset 0 1px 0 rgba(255,255,255,0.05);
    }

    .leader-os-country-table tfoot th:first-child {
        color: #dffefa;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-size: 0.76rem;
    }

    .leader-os-country-table tbody tr:hover {
        background: rgba(124, 200, 255, 0.06);
    }

    .leader-os-country-name {
        display: flex;
        flex-direction: column;
        gap: 0.12rem;
    }

    .leader-os-country-code {
        color: rgba(191, 211, 238, 0.64);
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .leader-os-country-table-empty {
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: rgba(191, 211, 238, 0.72);
        font-size: 0.9rem;
    }

    .leader-os-breakdown-trigger {
        cursor: pointer;
        transition: border-color 0.2s ease, transform 0.2s ease, background 0.2s ease;
    }

    .leader-os-breakdown-trigger:hover {
        border-color: rgba(124, 200, 255, 0.45);
        transform: translateY(-2px);
        background: rgba(14, 24, 42, 0.84);
    }

    .leader-os-breakdown-hint {
        color: #7cc8ff;
        font-size: 0.8rem;
    }

    .leader-os-modal .modal-content {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.98) 0%, rgba(10, 15, 28, 0.99) 100%);
        color: #ecf3ff;
    }

    .leader-os-modal .close,
    .leader-os-modal .modal-header,
    .leader-os-modal .modal-footer {
        color: #ffffff;
        border-color: rgba(148, 163, 184, 0.12);
    }

    .leader-os-modal-list {
        display: grid;
        gap: 0.85rem;
    }

    .leader-os-modal-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    }

    .leader-os-modal-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .leader-os-ai-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin: 1rem 0 1.1rem;
    }

    .leader-os-ai-button {
        border-color: rgba(124, 200, 255, 0.34);
        background: rgba(124, 200, 255, 0.14);
        color: #ffffff;
    }

    .leader-os-ai-button:hover,
    .leader-os-ai-button:focus {
        color: #ffffff;
        border-color: rgba(124, 200, 255, 0.6);
        background: rgba(124, 200, 255, 0.22);
    }

    .leader-os-ai-report {
        display: grid;
        gap: 1rem;
    }

    .leader-os-ai-section {
        padding-top: 0.9rem;
        border-top: 1px solid rgba(148, 163, 184, 0.1);
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .leader-os-ai-section:first-child {
        padding-top: 0;
        border-top: 0;
    }

    .leader-os-ai-title {
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: rgba(191, 211, 238, 0.72);
        margin-bottom: 0.55rem;
    }

    .leader-os-ai-list {
        margin: 0;
        padding-left: 1.15rem;
        color: #ecf3ff;
    }

    .leader-os-ai-list li + li {
        margin-top: 0.45rem;
    }

    .leader-os-ai-email-box {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 0.9rem;
        background: rgba(7, 12, 24, 0.74);
        padding: 0.85rem;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .leader-os-ai-details {
        border-top: 1px solid rgba(148, 163, 184, 0.1);
        padding-top: 0.9rem;
    }

    .leader-os-ai-details summary {
        cursor: pointer;
        list-style: none;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: rgba(191, 211, 238, 0.72);
        margin-bottom: 0.65rem;
    }

    .leader-os-ai-details summary::-webkit-details-marker {
        display: none;
    }

    .leader-os-phase4-priority {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.78);
    }

    .leader-os-phase4-priority.priority-high {
        border-color: rgba(248, 113, 113, 0.35);
        background: rgba(127, 29, 29, 0.28);
    }

    .leader-os-phase4-priority.priority-medium {
        border-color: rgba(251, 191, 36, 0.35);
        background: rgba(120, 53, 15, 0.28);
    }

    .leader-os-phase4-priority.priority-low {
        border-color: rgba(52, 211, 153, 0.28);
        background: rgba(6, 78, 59, 0.28);
    }

    .leader-os-phase4-note {
        border: 1px solid rgba(124, 200, 255, 0.18);
        border-radius: 1rem;
        background: rgba(8, 17, 32, 0.86);
        padding: 0.95rem;
    }

    .leader-os-phase4-change {
        display: grid;
        gap: 0.3rem;
        padding: 0.8rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    }

    .leader-os-phase4-change:first-child {
        padding-top: 0;
    }

    .leader-os-phase4-change:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .leader-os-phase4-delta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        align-items: center;
        color: rgba(191, 211, 238, 0.8);
        font-size: 0.86rem;
    }

    .leader-os-phase4-arrow {
        color: #7cc8ff;
        font-weight: 700;
    }

    .leader-os-phase4-form .form-control,
    .leader-os-phase4-form .custom-select {
        background: rgba(8, 13, 26, 0.72);
        border-color: rgba(148, 163, 184, 0.16);
        color: #ecf3ff;
    }

    .leader-os-phase4-form .form-control:focus,
    .leader-os-phase4-form .custom-select:focus {
        background: rgba(8, 13, 26, 0.9);
        color: #ecf3ff;
        border-color: rgba(124, 200, 255, 0.45);
        box-shadow: 0 0 0 0.2rem rgba(124, 200, 255, 0.08);
    }

    .leader-os-phase4-quick-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .leader-os-phase4-history {
        display: grid;
        gap: 0.8rem;
    }

    .leader-os-phase4-history-item {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 0.95rem;
        background: rgba(7, 12, 24, 0.62);
        padding: 0.9rem 0.95rem;
    }

    .leader-os-phase4-history-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 0.55rem;
        font-size: 0.76rem;
        color: rgba(191, 211, 238, 0.72);
    }

    .leader-os-outreach-form .form-control {
        background: rgba(8, 13, 26, 0.72);
        border-color: rgba(148, 163, 184, 0.16);
        color: #ecf3ff;
    }

    .leader-os-outreach-form .form-control:focus {
        background: rgba(8, 13, 26, 0.9);
        color: #ecf3ff;
        border-color: rgba(124, 200, 255, 0.45);
        box-shadow: 0 0 0 0.2rem rgba(124, 200, 255, 0.08);
    }

    .leader-os-outreach-history {
        display: grid;
        gap: 0.8rem;
    }

    .leader-os-outreach-history-item {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 0.95rem;
        padding: 0.9rem;
        background: rgba(8, 13, 26, 0.62);
    }

    .leader-os-outreach-status {
        display: inline-flex;
        align-items: center;
        padding: 0.28rem 0.6rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        background: rgba(22, 101, 52, 0.24);
        border: 1px solid rgba(34, 197, 94, 0.28);
    }

    /* Custom code: FC-2026-03-31: LOS score snapshot panel */
    .leader-os-score-snapshot-grid {
        display: grid;
        grid-template-columns: 1.1fr 1.6fr;
        gap: 1rem;
    }

    .leader-os-score-snapshot-box {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 0.95rem;
        padding: 0.95rem;
        background: rgba(8, 13, 26, 0.62);
    }

    .leader-os-score-snapshot-hero {
        display: flex;
        align-items: baseline;
        gap: 0.7rem;
        margin-bottom: 0.5rem;
    }

    .leader-os-score-snapshot-value {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
    }

    .leader-os-score-snapshot-delta {
        font-size: 0.82rem;
        font-weight: 700;
    }

    .leader-os-score-snapshot-delta.is-positive {
        color: #86efac;
    }

    .leader-os-score-snapshot-delta.is-negative {
        color: #fca5a5;
    }

    .leader-os-score-snapshot-delta.is-neutral {
        color: rgba(191, 211, 238, 0.72);
    }

    .leader-os-score-snapshot-metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .leader-os-score-snapshot-metric {
        border-top: 1px solid rgba(148, 163, 184, 0.08);
        padding-top: 0.7rem;
    }

    .leader-os-score-metric-trigger {
        appearance: none;
        border: 0;
        background: transparent;
        padding: 0;
        margin: 0;
        color: #ecf3ff;
        font: inherit;
        font-weight: 700;
        line-height: inherit;
        cursor: pointer;
        text-align: left;
    }

    .leader-os-score-metric-trigger:hover,
    .leader-os-score-metric-trigger:focus {
        color: #7cc8ff;
        outline: none;
        text-decoration: none;
    }

    .leader-os-score-history-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.85rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    }

    .leader-os-score-history-item:first-child {
        padding-top: 0;
    }

    .leader-os-score-history-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .leader-os-detail-jump-links {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .leader-os-cohort-grid {
        display: grid;
        grid-template-columns: 1.15fr 1.55fr;
        gap: 1rem;
    }

    .leader-os-cohort-box {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 0.95rem;
        padding: 0.95rem;
        background: rgba(8, 13, 26, 0.62);
    }

    .leader-os-cohort-metric {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.8rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    }

    .leader-os-cohort-metric:first-child {
        padding-top: 0;
    }

    .leader-os-cohort-metric:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .leader-os-cohort-delta.is-positive {
        color: #86efac;
    }

    .leader-os-cohort-delta.is-negative {
        color: #fca5a5;
    }

    .leader-os-cohort-delta.is-neutral {
        color: rgba(191, 211, 238, 0.72);
    }

    /* Custom code: FC-2026-03-31: Content structure diagnostics */
    .leader-os-structure-health {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.95rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 0.95rem;
        background: rgba(8, 13, 26, 0.62);
        margin-bottom: 1rem;
    }

    .leader-os-structure-score {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
    }

    .leader-os-structure-counts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .leader-os-structure-count {
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 0.9rem;
        padding: 0.8rem;
        background: rgba(8, 13, 26, 0.42);
    }

    .leader-os-structure-diagnostic {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 0.95rem;
        padding: 0.9rem;
        background: rgba(8, 13, 26, 0.62);
    }

    .leader-os-structure-diagnostic + .leader-os-structure-diagnostic {
        margin-top: 0.75rem;
    }

    .leader-os-design-review-grid {
        display: grid;
        grid-template-columns: 1.05fr 1.35fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .leader-os-design-review-box {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 0.95rem;
        padding: 0.95rem;
        background: rgba(8, 13, 26, 0.62);
    }

    .leader-os-design-score {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
    }

    .leader-os-design-metrics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .leader-os-design-metric {
        border-top: 1px solid rgba(148, 163, 184, 0.08);
        padding-top: 0.75rem;
    }

    .leader-os-preview-frame {
        width: 100%;
        min-height: 540px;
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 1rem;
        background: rgba(8, 13, 26, 0.92);
        overflow: hidden;
    }

    .leader-os-preview-frame iframe {
        width: 100%;
        min-height: 540px;
        border: 0;
        background: #ffffff;
    }

    .leader-os-review-check {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    }

    .leader-os-review-check:first-child {
        padding-top: 0;
    }

    .leader-os-review-check:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .leader-os-billing-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .leader-os-billing-note {
        border: 1px solid rgba(124, 200, 255, 0.18);
        border-radius: 0.95rem;
        background: rgba(8, 17, 32, 0.82);
        padding: 0.9rem;
    }

    .leader-os-billing-code {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.78rem;
        color: #dceaff;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .leader-os-billing-invoice {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 0.95rem;
        padding: 0.9rem;
        background: rgba(8, 13, 26, 0.62);
    }

    .leader-os-billing-invoice + .leader-os-billing-invoice {
        margin-top: 0.75rem;
    }

    .leader-os-billing-invoice-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        margin-top: 0.45rem;
        font-size: 0.76rem;
        color: rgba(191, 211, 238, 0.72);
    }
    /* /Custom code: FC-2026-03-31 */

    @media (max-width: 991.98px) {
        .leader-os-cockpit-grid,
        .leader-os-cockpit-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .leader-os-score-snapshot-grid {
            grid-template-columns: 1fr;
        }

        .leader-os-cohort-grid {
            grid-template-columns: 1fr;
        }

        .leader-os-design-review-grid {
            grid-template-columns: 1fr;
        }

        .leader-os-design-metrics {
            grid-template-columns: 1fr;
        }
    }
    /* /Custom code: FC-2026-03-31 */

    @media (max-width: 767.98px) {
        .leader-os-cockpit-grid,
        .leader-os-cockpit-summary-grid {
            grid-template-columns: 1fr;
        }

        .leader-os-cockpit-value {
            font-size: 1.7rem;
        }

        .leader-os-detail-shell .card-body {
            padding: 0.95rem;
        }
    }
</style>

<div class="leader-os-detail-page">
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
    <div>
        <h1 class="h3 mb-1"><?= l('admin_leader_operating_system.leader.header') ?></h1>
        <p class="text-muted mb-0"><?= l('admin_leader_operating_system.leader.subheader') ?></p>
    </div>

    <div class="mt-3 mt-lg-0 d-flex align-items-center">
        <span class="leader-os-detail-chip mr-2"><?= l('admin_leader_operating_system.period_' . $data->selected_period) ?></span>
        <a href="<?= $data->overview_url ?>" class="btn btn-sm leader-os-detail-action"><?= l('admin_leader_operating_system.leader.back') ?></a>
    </div>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<?php $detail = $data->detail; ?>
<?php $selected = $data->selected_payload ?? []; ?>
<?php $opportunity_actions = $data->opportunity_actions ?? null; ?>
<?php $ai_report = $data->ai_report; ?>
<?php $app_structure = $detail['app_structure'] ?? []; ?>
<?php $ai_plan_admin = $detail['ai_plan_admin'] ?? []; ?>
<?php $mentor_actions = $ai_plan_admin['mentor_actions'] ?? []; ?>
<?php $mentor_history = $ai_plan_admin['mentor_history'] ?? []; ?>
<?php $mentor_ai_guidance_active = !empty($mentor_actions['ai_guidance']); ?>
<?php $los_outreach = $data->los_outreach ?? []; ?>
<?php $report_history = $los_outreach['report_history'] ?? []; ?>
<?php $send_history = $los_outreach['send_history'] ?? []; ?>
<?php $score_history = $detail['score_history'][$data->selected_period] ?? ['latest' => null, 'previous' => null, 'history' => [], 'total' => 0]; ?>
<?php $cohort_comparison = $data->cohort_comparison ?? []; ?>
<?php $behavior_anomaly = $data->behavior_anomaly ?? []; ?>
<?php $fraud_intelligence = $data->fraud_intelligence ?? []; ?>
<?php $fraud_action = $data->fraud_action ?? []; ?>
<?php $billing_summary = $data->billing_summary ?? []; ?>
<?php $stripe_billing = $data->stripe_billing ?? []; ?>
<?php $consistency = $data->consistency ?? []; ?>
<?php $coaching_roi = $data->coaching_roi ?? []; ?>
<?php $ai_text_detail = $data->ai_text_detail ?? []; ?>
<?php
$latest_checkin = $ai_plan_admin['latest_checkin'] ?? [];
$latest_plan = $ai_plan_admin['latest_plan'] ?? [];
$latest_outcome = $ai_plan_admin['latest_outcome'] ?? [];
$app_live_url = $app_structure['page_review']['public_url'] ?? ($app_structure['top_app_public_url'] ?? null);
$main_opportunity_item = $opportunity_actions['items'][0] ?? [];
$main_opportunity_label = (string) ($main_opportunity_item['label'] ?? l('global.none'));
$main_opportunity_text = (string) ($main_opportunity_item['text'] ?? $selected['next_step']);
$main_risk_label = (string) (($behavior_anomaly['top_concern'] ?? '') ?: ($fraud_intelligence['top_concern'] ?? '') ?: l('global.none'));
$main_risk_text = (string) (($behavior_anomaly['signals'][0]['action'] ?? '') ?: ($fraud_action['text'] ?? '') ?: l('global.none'));
$billing_period_label = !empty($stripe_billing['trial_end'])
    ? \Altum\Date::get($stripe_billing['trial_end'], 2)
    : (!empty($stripe_billing['current_period_end']) ? \Altum\Date::get($stripe_billing['current_period_end'], 2) : l('global.none'));
$last_contacted_label = !empty($mentor_actions['last_contacted_at']) ? \Altum\Date::get($mentor_actions['last_contacted_at'], 2) : l('global.none');
$last_checkin_label = !empty($latest_checkin['submitted_at']) ? \Altum\Date::get($latest_checkin['submitted_at'], 2) : l('global.none');
$latest_plan_label = !empty($latest_plan['generated_at']) ? \Altum\Date::get($latest_plan['generated_at'], 2) : l('global.none');
$latest_outcome_label = !empty($latest_outcome['created_at']) ? \Altum\Date::get($latest_outcome['created_at'], 2) : l('global.none');
$mentor_status_label = !empty($mentor_actions['status']) ? l('admin_leader_operating_system.leader.ai_plan_admin_status.' . $mentor_actions['status']) : l('global.none');
$render_detail_help = static function(string $tooltip): void {
    if($tooltip === '') {
        return;
    }

    echo '<span class="leader-os-cockpit-help" data-toggle="tooltip" title="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '">?</span>';
};
?>

<div class="card leader-os-detail-shell mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-12 col-xl-7 mb-3 mb-xl-0">
                <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.section_detail') ?></div>
                <h2 class="h4 mb-2">
                    <?= $detail ? ($detail['name'] ?: $detail['email']) : l('admin_leader_operating_system.leader.no_user') ?>
                </h2>
                <p class="text-muted mb-0">
                    <?= $detail ? l('admin_leader_operating_system.leader.selected_user') . ': #' . $detail['user_id'] . ' · ' . $detail['email'] . ' · Forever ID: ' . $detail['forever_id'] : l('admin_leader_operating_system.leader.missing') ?>
                </p>
                <div class="leader-os-cockpit-tags">
                    <span class="leader-os-detail-status status-<?= htmlspecialchars((string) ($selected['status_class'] ?? 'dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($selected['status_label'] ?? l('global.none')), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if(!empty($consistency)): ?>
                        <span class="leader-os-detail-chip is-subtle">Dosljednost · <?= nr((int) ($consistency['score'] ?? 0)) ?> · <?= htmlspecialchars((string) ($consistency['state_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif ?>
                    <span class="leader-os-detail-chip is-subtle">AI prioritet · <?= htmlspecialchars((string) (($ai_plan_admin['priority']['label'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="leader-os-detail-chip is-subtle">Naplata · <?= htmlspecialchars((string) (($stripe_billing['billing_state'] ?? '') ? l('admin_billing_risk.state_' . $stripe_billing['billing_state']) : l('global.none')), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if(!empty($stripe_billing['plan_name'])): ?>
                        <span class="leader-os-detail-chip is-subtle"><?= htmlspecialchars((string) $stripe_billing['plan_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif ?>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="leader-os-detail-periods justify-content-xl-end">
                    <?php foreach($data->period_options as $period_key): ?>
                        <a href="<?= url('admin/leader-operating-system-leader?user_id=' . $detail['user_id'] . '&period=' . $period_key) ?>" class="leader-os-detail-period-link <?= $data->selected_period === $period_key ? 'active' : null ?>">
                            <?= l('admin_leader_operating_system.period_' . $period_key) ?>
                        </a>
                    <?php endforeach ?>
                </div>

                <div class="leader-os-cockpit-actions justify-content-xl-end">
                    <a href="<?= $data->overview_url ?>" class="btn btn-sm leader-os-detail-action"><?= l('admin_leader_operating_system.leader.back') ?></a>
                    <a href="<?= htmlspecialchars((string) ($detail['admin_user_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm leader-os-detail-action"><?= l('admin_leader_operating_system.leader.open_profile_user') ?></a>
                    <?php if(!empty($app_live_url)): ?>
                        <a href="<?= htmlspecialchars((string) $app_live_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm leader-os-detail-action">Otvori live aplikaciju</a>
                    <?php endif ?>
                    <button type="button" class="btn btn-sm leader-os-detail-action leader-os-scroll-link" data-scroll-target="leader-os-stripe-billing"><?= l('admin_leader_operating_system.leader.open_stripe_billing') ?></button>
                    <button type="button" class="btn btn-sm leader-os-ai-button leader-os-scroll-link" data-scroll-target="leader-os-ai-report">Pokreni / otvori AI analizu</button>
                    <button type="button" class="btn btn-sm leader-os-detail-action leader-os-scroll-link" data-scroll-target="leader-os-app-structure">Struktura aplikacije</button>
                    <button type="button" class="btn btn-sm leader-os-detail-action leader-os-scroll-link" data-scroll-target="leader-os-outreach-panel">Pošalji poruku suradniku</button>
                </div>
            </div>
        </div>

        <div class="leader-os-cockpit-grid mt-4">
            <div class="leader-os-cockpit-card">
                <div class="leader-os-cockpit-label">
                    Trenutno stanje
                    <?php $render_detail_help('Što je: brzi pregled poslovnog zdravlja suradnika. | Kako se računa: kombinira LOS rezultat, status, consistency i zadnju aktivnost. | Kako koristiš: odmah vidiš je li osoba stabilna, pada ili traži brzu reakciju.'); ?>
                </div>
                <div class="leader-os-cockpit-value"><?= nr((int) ($selected['leader_os_score'] ?? 0)) ?>/100</div>
                <div class="leader-os-cockpit-meta"><?= htmlspecialchars((string) ($selected['status_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> · Zadnji klik: <?= !empty($selected['last_click_at']) ? \Altum\Date::get($selected['last_click_at'], 2) : l('global.none') ?></div>
                <div class="leader-os-cockpit-list">
                    <div class="leader-os-cockpit-item">
                        <span>Dosljednost</span>
                        <strong><?= !empty($consistency) ? (nr((int) ($consistency['score'] ?? 0)) . ' · ' . htmlspecialchars((string) ($consistency['state_label'] ?? '-'), ENT_QUOTES, 'UTF-8')) : l('global.none') ?></strong>
                    </div>
                    <div class="leader-os-cockpit-item">
                        <span>Aktivni dani</span>
                        <strong><?= nr((int) ($selected['active_days_total'] ?? 0)) ?></strong>
                    </div>
                    <div class="leader-os-cockpit-item">
                        <span>Coaching signal</span>
                        <strong><?= htmlspecialchars((string) ($coaching_roi['signal_label'] ?? l('global.none')), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>

            <div class="leader-os-cockpit-card">
                <div class="leader-os-cockpit-label">
                    Prodajni signal
                    <?php $render_detail_help('Što je: mjeri stvarni put od interesa do Forever rezultata. | Kako se računa: gleda webshop klikove, registracije, stopu registracije i izvore prometa u odabranom periodu. | Kako koristiš: vidiš ima li suradnik promet bez rezultata ili rezultat koji treba skalirati.'); ?>
                </div>
                <div class="leader-os-cockpit-value"><?= nr((int) ($selected['forever_shop_clicks_period'] ?? 0)) ?></div>
                <div class="leader-os-cockpit-meta">Klikovi prema Foreveru · Registracije <?= nr((int) ($selected['forever_registration_clicks_period'] ?? 0)) ?> · Stopa <?= nr((float) ($selected['registration_rate_percent'] ?? 0)) ?>%</div>
                <div class="leader-os-cockpit-list">
                    <div class="leader-os-cockpit-item">
                        <span>Delta prema prošlom periodu</span>
                        <strong><?= (($selected['growth_difference'] ?? 0) > 0 ? '+' : '') . nr((int) ($selected['growth_difference'] ?? 0)) ?></strong>
                    </div>
                    <div class="leader-os-cockpit-item">
                        <span>Najjači izvor</span>
                        <strong><?= htmlspecialchars((string) ($selected['top_source_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-cockpit-item">
                        <span>Najjače tržište</span>
                        <strong><?= htmlspecialchars((string) ($selected['top_country_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>

            <div class="leader-os-cockpit-card">
                <div class="leader-os-cockpit-label">
                    AI i coaching ritam
                    <?php $render_detail_help('Što je: pokazuje prolazi li suradnik kroz AI ciklus i admin coaching. | Kako se računa: gleda AI profil, zadnji check-in, plan, outcome, admin status i zadnji kontakt. | Kako koristiš: brzo vidiš je li osoba zapela prije plana, u provedbi ili u follow-upu.'); ?>
                </div>
                <div class="leader-os-cockpit-value"><?= htmlspecialchars((string) (($ai_plan_admin['priority']['label'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="leader-os-cockpit-meta">Admin status · <?= htmlspecialchars((string) $mentor_status_label, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="leader-os-cockpit-list">
                    <div class="leader-os-cockpit-item">
                        <span>Zadnji check-in</span>
                        <strong><?= htmlspecialchars((string) $last_checkin_label, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-cockpit-item">
                        <span>Zadnji plan / outcome</span>
                        <strong><?= htmlspecialchars((string) $latest_plan_label, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $latest_outcome_label, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-cockpit-item">
                        <span>Zadnji kontakt / sljedeći korak</span>
                        <strong><?= htmlspecialchars((string) $last_contacted_label, ENT_QUOTES, 'UTF-8') ?><br><?= htmlspecialchars((string) (($mentor_actions['next_action'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>

            <div class="leader-os-cockpit-card">
                <div class="leader-os-cockpit-label">
                    Billing i plan
                    <?php $render_detail_help('Što je: sažetak pretplate i billing zdravlja. | Kako se računa: koristi Stripe customer/subscription podatke, recovery stanje i broj neuspjelih pokušaja. | Kako koristiš: odmah vidiš je li poslovni pad možda billing problem, a ne promet ili AI.'); ?>
                </div>
                <div class="leader-os-cockpit-value"><?= htmlspecialchars((string) (($stripe_billing['plan_name'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="leader-os-cockpit-meta"><?= htmlspecialchars((string) (($stripe_billing['status'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) (($stripe_billing['plan_price_label'] ?? '') ?: ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="leader-os-cockpit-list">
                    <div class="leader-os-cockpit-item">
                        <span>Kraj tekućeg perioda</span>
                        <strong><?= htmlspecialchars((string) $billing_period_label, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-cockpit-item">
                        <span>Otkazivanje na kraju perioda</span>
                        <strong><?= !empty($stripe_billing['cancel_at_period_end']) ? l('global.yes') : l('global.no') ?></strong>
                    </div>
                    <div class="leader-os-cockpit-item">
                        <span>Neuspjeli pokušaji</span>
                        <strong><?= nr((int) ($stripe_billing['failed_attempts'] ?? 0)) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="leader-os-detail-note mt-3">Kako čitaš ovaj vrh: prvo pogledaj <strong>Trenutno stanje</strong>, zatim <strong>Prodajni signal</strong>, pa <strong>AI i coaching ritam</strong> i tek onda provjeri je li problem možda u <strong>billingu</strong>. Tako najbrže vidiš je li suradnik zapinje u prometu, konverziji, provedbi ili naplati.</div>
    </div>
</div>

<?php if(!$detail || !$selected): ?>
    <div class="leader-os-detail-panel">
        <p class="text-muted mb-0"><?= l('admin_leader_operating_system.leader.missing') ?></p>
    </div>
<?php else: ?>
    <?php
    $country_matrix_periods = $detail['country_signal_matrix_periods'] ?? [];
    $initial_country_period = isset($country_matrix_periods[$data->selected_period]) ? $data->selected_period : '30d';
    $initial_country_matrix = $country_matrix_periods[$initial_country_period] ?? ['rows' => []];
    $initial_country_totals = $initial_country_matrix['totals'] ?? [
        'app_visits' => 0,
        'app_shop_clicks' => 0,
        'blog_clicks' => 0,
        'funnel_registrations' => 0,
    ];
    ?>
    <div class="card leader-os-detail-shell mb-4" id="leader-os-stripe-billing">
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-xl-6 mb-3 mb-xl-0">
                    <div class="leader-os-detail-panel h-100">
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                            <div>
                                <h3 class="h5 mb-1"><?= l('admin_leader_operating_system.leader.stripe_title') ?></h3>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.leader.stripe_text') ?></div>
                            </div>

                            <div class="d-flex flex-wrap mt-2 mt-xl-0" style="gap:.5rem;">
                                <span class="leader-os-detail-status status-<?= htmlspecialchars((string) ($stripe_billing['billing_state_class'] ?? 'dark'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= l('admin_billing_risk.state_' . ($stripe_billing['billing_state'] ?? 'healthy')) ?>
                                </span>
                                <span class="leader-os-detail-status status-<?= htmlspecialchars((string) ($stripe_billing['status_class'] ?? 'dark'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) (($stripe_billing['status'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </div>

                        <div class="leader-os-billing-actions">
                            <?php if(!empty($stripe_billing['portal_available'])): ?>
                                <form action="" method="post" target="_blank" class="d-inline-block">
                                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                                    <button type="submit" name="open_stripe_customer_portal" value="1" class="btn btn-sm leader-os-ai-button"><?= l('admin_leader_operating_system.leader.stripe_portal_open') ?></button>
                                </form>
                            <?php else: ?>
                                <span class="leader-os-detail-chip is-subtle"><?= l('admin_leader_operating_system.leader.stripe_portal_unavailable') ?></span>
                            <?php endif ?>

                            <?php if(!empty($stripe_billing['customer_dashboard_url'])): ?>
                                <a href="<?= htmlspecialchars((string) $stripe_billing['customer_dashboard_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm leader-os-detail-action"><?= l('admin_leader_operating_system.leader.stripe_dashboard_customer') ?></a>
                            <?php endif ?>

                            <?php if(!empty($stripe_billing['subscription_dashboard_url'])): ?>
                                <a href="<?= htmlspecialchars((string) $stripe_billing['subscription_dashboard_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm leader-os-detail-action"><?= l('admin_leader_operating_system.leader.stripe_dashboard_subscription') ?></a>
                            <?php endif ?>
                        </div>

                        <?php if(!empty($stripe_billing['portal_error'])): ?>
                            <div class="leader-os-billing-note mb-3">
                                <?= htmlspecialchars((string) $stripe_billing['portal_error'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php else: ?>
                            <div class="leader-os-billing-note mb-3">
                                <?= l('admin_leader_operating_system.leader.stripe_portal_note') ?>
                            </div>
                        <?php endif ?>

                        <div class="leader-os-detail-list">
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.stripe_plan') ?></span>
                                <strong class="text-right ml-3">
                                    <?= htmlspecialchars((string) (($stripe_billing['plan_name'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if(!empty($stripe_billing['plan_price_label'])): ?>
                                        <br /><small class="text-muted"><?= htmlspecialchars((string) $stripe_billing['plan_price_label'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif ?>
                                </strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted">
                                    <?= !empty($stripe_billing['trial_end']) ? l('admin_leader_operating_system.leader.stripe_trial_end') : l('admin_leader_operating_system.leader.stripe_renews_at') ?>
                                </span>
                                <strong>
                                    <?=
                                        !empty($stripe_billing['trial_end'])
                                            ? \Altum\Date::get($stripe_billing['trial_end'], 2)
                                            : (!empty($stripe_billing['current_period_end']) ? \Altum\Date::get($stripe_billing['current_period_end'], 2) : l('global.none'))
                                    ?>
                                </strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.stripe_cancel_at_period_end') ?></span>
                                <strong>
                                    <?= !empty($stripe_billing['cancel_at_period_end']) ? l('global.yes') : l('global.no') ?>
                                    <?php if(!empty($stripe_billing['cancel_at'])): ?>
                                        <br /><small class="text-muted"><?= \Altum\Date::get($stripe_billing['cancel_at'], 2) ?></small>
                                    <?php endif ?>
                                </strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.stripe_customer_id') ?></span>
                                <strong class="text-right ml-3">
                                    <span class="leader-os-billing-code"><?= htmlspecialchars((string) (($stripe_billing['customer_id'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?></span>
                                </strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.stripe_subscription_id') ?></span>
                                <strong class="text-right ml-3">
                                    <span class="leader-os-billing-code"><?= htmlspecialchars((string) (($stripe_billing['subscription_id'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?></span>
                                </strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.stripe_last_failure') ?></span>
                                <strong class="text-right ml-3"><?= htmlspecialchars((string) (($stripe_billing['last_failed_reason_text'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.stripe_next_retry') ?></span>
                                <strong><?= !empty($stripe_billing['next_retry_at']) ? \Altum\Date::get($stripe_billing['next_retry_at'], 2) : l('global.none') ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.stripe_grace_until') ?></span>
                                <strong><?= !empty($stripe_billing['grace_until']) ? \Altum\Date::get($stripe_billing['grace_until'], 2) : l('global.none') ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.stripe_failed_attempts') ?></span>
                                <strong><?= nr((int) ($stripe_billing['failed_attempts'] ?? 0)) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="leader-os-detail-panel h-100">
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                            <div>
                                <h3 class="h5 mb-1"><?= l('admin_leader_operating_system.leader.stripe_recent_invoices') ?></h3>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.leader.stripe_recent_invoices_text') ?></div>
                            </div>

                            <?php if(!empty($stripe_billing['last_invoice_dashboard_url'])): ?>
                                <a href="<?= htmlspecialchars((string) $stripe_billing['last_invoice_dashboard_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm leader-os-detail-action mt-2 mt-xl-0"><?= l('admin_leader_operating_system.leader.stripe_last_invoice_open') ?></a>
                            <?php endif ?>
                        </div>

                        <?php if(empty($stripe_billing['recent_invoices'])): ?>
                            <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.stripe_recent_invoices_empty') ?></div>
                        <?php else: ?>
                            <?php foreach($stripe_billing['recent_invoices'] as $invoice): ?>
                                <div class="leader-os-billing-invoice">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                                        <div class="mr-3">
                                            <div class="leader-os-billing-code"><?= htmlspecialchars((string) ($invoice['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="leader-os-billing-invoice-meta">
                                                <span><?= htmlspecialchars((string) (($invoice['status'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?></span>
                                                <span><?= !empty($invoice['created_at']) ? \Altum\Date::get($invoice['created_at'], 2) : l('global.none') ?></span>
                                                <span><?= htmlspecialchars((string) (($invoice['total'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                            <div class="text-muted small mt-2"><?= l('admin_leader_operating_system.leader.stripe_invoice_paid') ?>: <?= htmlspecialchars((string) (($invoice['amount_paid'] ?? '') ?: l('global.none')), ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>

                                        <div class="d-flex flex-wrap mt-2 mt-xl-0" style="gap:.5rem;">
                                            <?php if(!empty($invoice['hosted_invoice_url'])): ?>
                                                <a href="<?= htmlspecialchars((string) $invoice['hosted_invoice_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm leader-os-detail-action"><?= l('admin_leader_operating_system.leader.stripe_invoice_open') ?></a>
                                            <?php endif ?>

                                            <?php if(!empty($invoice['dashboard_url'])): ?>
                                                <a href="<?= htmlspecialchars((string) $invoice['dashboard_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm leader-os-detail-action"><?= l('admin_leader_operating_system.leader.stripe_dashboard_invoice') ?></a>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-xl-8 mb-3">
            <div class="leader-os-detail-panel h-100">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <div>
                        <h3 class="h5 mb-1"><?= l('admin_leader_operating_system.leader.chart_main_title') ?></h3>
                        <div class="text-muted small"><?= l('admin_leader_operating_system.leader.chart_main_text') ?></div>
                    </div>
                </div>
                <div class="leader-os-detail-chart-wrap">
                    <canvas id="leader-os-detail-trend-chart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4 mb-3">
            <div class="leader-os-detail-panel h-100">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <div>
                        <h3 class="h5 mb-1"><?= l('admin_leader_operating_system.leader.chart_compare_title') ?></h3>
                        <div class="text-muted small"><?= l('admin_leader_operating_system.leader.chart_compare_text') ?></div>
                    </div>
                    <div class="leader-os-country-periods">
                        <?php foreach(['1d', '7d', '30d', '90d'] as $country_period_key): ?>
                            <button type="button" class="leader-os-country-period <?= $country_period_key === $initial_country_period ? 'active' : null ?>" data-country-period="<?= $country_period_key ?>">
                                <?= l('admin_leader_operating_system.period_' . $country_period_key) ?>
                            </button>
                        <?php endforeach ?>
                    </div>
                </div>
                <div class="leader-os-country-table-wrap">
                    <table class="table table-sm leader-os-country-table">
                        <thead>
                            <tr>
                                <th><?= l('admin_leader_operating_system.leader.country_table.country') ?></th>
                                <th class="text-right"><?= l('admin_leader_operating_system.leader.chart_metric_app_visits') ?></th>
                                <th class="text-right"><?= l('admin_leader_operating_system.leader.chart_metric_app_shop_clicks') ?></th>
                                <th class="text-right"><?= l('admin_leader_operating_system.leader.chart_metric_blog_clicks') ?></th>
                                <th class="text-right"><?= l('admin_leader_operating_system.leader.chart_metric_funnel_registrations') ?></th>
                            </tr>
                        </thead>
                        <tbody id="leader-os-country-table-body">
                            <?php if(empty($initial_country_matrix['rows'])): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="leader-os-country-table-empty"><?= l('admin_leader_operating_system.leader.country_table.empty') ?></div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($initial_country_matrix['rows'] as $country_row): ?>
                                    <tr>
                                        <td>
                                            <div class="leader-os-country-name">
                                                <strong><?= htmlspecialchars((string) ($country_row['country_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                                <span class="leader-os-country-code"><?= htmlspecialchars((string) ($country_row['country_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </td>
                                        <td class="text-right"><?= nr((int) ($country_row['app_visits'] ?? 0)) ?></td>
                                        <td class="text-right"><?= nr((int) ($country_row['app_shop_clicks'] ?? 0)) ?></td>
                                        <td class="text-right"><?= nr((int) ($country_row['blog_clicks'] ?? 0)) ?></td>
                                        <td class="text-right"><?= nr((int) ($country_row['funnel_registrations'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            <?php endif ?>
                        </tbody>
                        <tfoot id="leader-os-country-table-foot">
                            <tr>
                                <th><?= l('admin_leader_operating_system.leader.country_table.total') ?></th>
                                <td class="text-right"><?= nr((int) ($initial_country_totals['app_visits'] ?? 0)) ?></td>
                                <td class="text-right"><?= nr((int) ($initial_country_totals['app_shop_clicks'] ?? 0)) ?></td>
                                <td class="text-right"><?= nr((int) ($initial_country_totals['blog_clicks'] ?? 0)) ?></td>
                                <td class="text-right"><?= nr((int) ($initial_country_totals['funnel_registrations'] ?? 0)) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-6 col-xl-3 mb-3">
            <div class="leader-os-detail-kpi">
                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.kpi_score') ?></div>
                <div class="h2 mb-1"><?= $selected['leader_os_score'] ?></div>
                <div class="text-muted small"><?= l('admin_leader_operating_system.leader.kpi_status') ?>: <?= $selected['status_label'] ?></div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3 mb-3">
            <div class="leader-os-detail-kpi">
                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.kpi_shop_clicks') ?></div>
                <div class="h2 mb-1"><?= nr($selected['forever_shop_clicks_period']) ?></div>
                <div class="text-muted small"><?= l('admin_leader_operating_system.leader.delta_label') ?>: <?= ($selected['growth_difference'] > 0 ? '+' : '') . nr($selected['growth_difference']) ?></div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3 mb-3">
            <div class="leader-os-detail-kpi">
                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.kpi_registrations') ?></div>
                <div class="h2 mb-1"><?= nr($selected['forever_registration_clicks_period']) ?></div>
                <div class="text-muted small"><?= nr($selected['registration_rate_percent']) ?>%</div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3 mb-3">
            <div class="leader-os-detail-kpi">
                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.kpi_last_click') ?></div>
                <div class="h6 mb-1"><?= $selected['last_click_at'] ? \Altum\Date::get($selected['last_click_at'], 2) : '-' ?></div>
                <div class="text-muted small"><?= l('admin_leader_operating_system.leader.active_days') ?>: <?= nr($selected['active_days_total']) ?></div>
            </div>
        </div>
    </div>

    <!-- Custom code: FC-2026-03-31: LOS score snapshot panel -->
    <div class="card leader-os-detail-shell mb-4" id="leader-os-score-history">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <h3 class="h5 mb-1"><?= l('admin_leader_operating_system.leader.score_snapshot_title') ?></h3>
                    <div class="text-muted small"><?= l('admin_leader_operating_system.leader.score_snapshot_text') ?></div>
                </div>
                <span class="leader-os-detail-chip is-subtle"><?= sprintf(l('admin_leader_operating_system.leader.score_snapshot_total'), nr((int) ($score_history['total'] ?? 0))) ?></span>
            </div>

            <!-- Custom code: FC-2026-03-31: LOS detail score history helper -->
            <div class="leader-os-detail-note mb-3"><?= l('admin_leader_operating_system.leader.score_snapshot_helper') ?></div>
            <!-- /Custom code: FC-2026-03-31 -->

            <?php if(empty($score_history['latest'])): ?>
                <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.score_snapshot_empty') ?></div>
            <?php else: ?>
                <?php $latest_score_snapshot = $score_history['latest']; ?>
                <?php $previous_score_snapshot = $score_history['previous'] ?? null; ?>
                <?php $latest_score_delta = $latest_score_snapshot['delta_leader_os_score'] ?? null; ?>
                <div class="leader-os-score-snapshot-grid">
                    <div class="leader-os-score-snapshot-box">
                        <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.score_snapshot_current') ?></div>
                        <div class="leader-os-score-snapshot-hero">
                            <div class="leader-os-score-snapshot-value"><?= nr((int) ($latest_score_snapshot['leader_os_score'] ?? 0)) ?></div>
                            <div class="leader-os-score-snapshot-delta <?= $latest_score_snapshot['delta_class'] ?>">
                                <?php if($latest_score_delta === null): ?>
                                    <?= l('admin_leader_operating_system.leader.score_snapshot_first') ?>
                                <?php else: ?>
                                    <?= ($latest_score_delta > 0 ? '+' : '') . nr((int) $latest_score_delta) ?>
                                    · <?= l('admin_leader_operating_system.leader.score_snapshot_vs_previous') ?>
                                <?php endif ?>
                            </div>
                        </div>

                        <div class="text-muted small"><?= !empty($latest_score_snapshot['created_at']) ? \Altum\Date::get($latest_score_snapshot['created_at'], 2) : '-' ?></div>

                        <div class="leader-os-score-snapshot-metrics">
                            <div class="leader-os-score-snapshot-metric">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.performance_score') ?></div>
                                <strong><?= nr((int) ($latest_score_snapshot['performance_score'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-score-snapshot-metric">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.momentum_score') ?></div>
                                <strong><?= nr((int) ($latest_score_snapshot['momentum_score'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-score-snapshot-metric">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.conversion_score') ?></div>
                                <strong><?= nr((int) ($latest_score_snapshot['conversion_score'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-score-snapshot-metric">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.risk_score') ?></div>
                                <strong><?= nr((int) ($latest_score_snapshot['risk_score'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-score-snapshot-metric">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.opportunity_score') ?></div>
                                <strong>
                                    <button
                                        type="button"
                                        class="leader-os-score-metric-trigger"
                                        data-toggle="modal"
                                        data-target="#leader-os-opportunity-modal"
                                    >
                                        <?= nr((int) ($latest_score_snapshot['opportunity_score'] ?? 0)) ?>
                                    </button>
                                </strong>
                            </div>
                            <div class="leader-os-score-snapshot-metric">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.score_snapshot_previous') ?></div>
                                <strong><?= $previous_score_snapshot ? nr((int) ($previous_score_snapshot['leader_os_score'] ?? 0)) : '-' ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="leader-os-score-snapshot-box">
                        <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.score_snapshot_history') ?></div>

                        <?php foreach(($score_history['history'] ?? []) as $history_item): ?>
                            <?php $history_delta = $history_item['delta_leader_os_score'] ?? null; ?>
                            <div class="leader-os-score-history-item">
                                <div>
                                    <div class="font-weight-bold"><?= !empty($history_item['created_at']) ? \Altum\Date::get($history_item['created_at'], 2) : '-' ?></div>
                                    <div class="text-muted small">
                                        <?= l('admin_leader_operating_system.leader.performance_score') ?>: <?= nr((int) ($history_item['performance_score'] ?? 0)) ?>
                                        · <?= l('admin_leader_operating_system.leader.momentum_score') ?>: <?= nr((int) ($history_item['momentum_score'] ?? 0)) ?>
                                        · <?= l('admin_leader_operating_system.leader.conversion_score') ?>: <?= nr((int) ($history_item['conversion_score'] ?? 0)) ?>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="h5 mb-1"><?= nr((int) ($history_item['leader_os_score'] ?? 0)) ?></div>
                                    <div class="leader-os-score-snapshot-delta <?= $history_item['delta_class'] ?>">
                                        <?= $history_delta === null ? l('admin_leader_operating_system.leader.score_snapshot_first') : (($history_delta > 0 ? '+' : '') . nr((int) $history_delta)) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
    <!-- /Custom code: FC-2026-03-31 -->

    <!-- Custom code: FC-2026-03-31: V2 cohort comparison panel -->
    <div class="card leader-os-detail-shell mb-4" id="leader-os-cohort-comparison">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <h3 class="h5 mb-1"><?= l('admin_leader_operating_system.leader.cohort_title') ?></h3>
                    <div class="text-muted small"><?= l('admin_leader_operating_system.leader.cohort_text') ?></div>
                </div>
                <span class="leader-os-detail-chip is-subtle"><?= htmlspecialchars((string) ($cohort_comparison['scope_label'] ?? l('admin_leader_operating_system.leader.cohort_scope.tier_only')), ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <!-- Custom code: FC-2026-03-31: LOS detail cohort helper -->
            <div class="leader-os-detail-note mb-3"><?= l('admin_leader_operating_system.leader.cohort_helper') ?></div>
            <!-- /Custom code: FC-2026-03-31 -->

            <?php if(empty($cohort_comparison['cohort_size'])): ?>
                <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.cohort_empty') ?></div>
            <?php else: ?>
                <div class="leader-os-cohort-grid">
                    <div class="leader-os-cohort-box">
                        <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.cohort_context') ?></div>
                        <div class="leader-os-detail-list">
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.snapshot_country') ?></span>
                                <strong><?= htmlspecialchars((string) ($cohort_comparison['selected_country'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.cohort_tier_label') ?></span>
                                <strong><?= htmlspecialchars((string) ($cohort_comparison['selected_tier_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.cohort_size_label') ?></span>
                                <strong><?= nr((int) ($cohort_comparison['cohort_size'] ?? 0)) ?></strong>
                            </div>
                        </div>

                        <?php if(!empty($cohort_comparison['top_peers'])): ?>
                            <div class="leader-os-ai-title mt-3"><?= l('admin_leader_operating_system.leader.cohort_top_peers') ?></div>
                            <div class="leader-os-detail-list">
                                <?php foreach($cohort_comparison['top_peers'] as $peer): ?>
                                    <div class="leader-os-detail-list-item">
                                        <span class="text-muted"><?= htmlspecialchars((string) ($peer['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= nr((int) ($peer['leader_os_score'] ?? 0)) ?></strong>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        <?php endif ?>
                    </div>

                    <div class="leader-os-cohort-box">
                        <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.cohort_metrics') ?></div>

                        <?php foreach(($cohort_comparison['metrics'] ?? []) as $metric): ?>
                            <div class="leader-os-cohort-metric">
                                <div>
                                    <div class="font-weight-bold"><?= htmlspecialchars((string) ($metric['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small">
                                        <?= l('admin_leader_operating_system.leader.cohort_you') ?>: <?= nr((float) ($metric['selected_value'] ?? 0)) ?>
                                        · <?= l('admin_leader_operating_system.leader.cohort_average') ?>: <?= nr((float) ($metric['cohort_value'] ?? 0)) ?>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="leader-os-cohort-delta <?= $metric['delta_class'] ?>">
                                        <?= ((float) ($metric['delta'] ?? 0) > 0 ? '+' : '') . nr((float) ($metric['delta'] ?? 0)) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
    <!-- /Custom code: FC-2026-03-31 -->

    <!-- Custom code: FC-2026-03-31: V3 anomaly radar panel -->
    <div class="card leader-os-detail-shell mb-4" id="leader-os-anomaly-radar">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <h3 class="h5 mb-1"><?= l('admin_leader_operating_system.leader.anomaly_title') ?></h3>
                    <div class="text-muted small"><?= l('admin_leader_operating_system.leader.anomaly_text') ?></div>
                </div>
                <span class="leader-os-detail-status <?= htmlspecialchars((string) ($behavior_anomaly['level_class'] ?? 'status-success'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) ($behavior_anomaly['level_label'] ?? l('admin_leader_operating_system.leader.anomaly_level.stable')), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <div class="leader-os-detail-note mb-3"><?= l('admin_leader_operating_system.leader.anomaly_helper') ?></div>

            <div class="row">
                <div class="col-12 col-xl-4 mb-3 mb-xl-0">
                    <div class="leader-os-anomaly-item h-100">
                        <div class="leader-os-ai-title mb-2"><?= l('admin_leader_operating_system.leader.anomaly_score_label') ?></div>
                        <div class="leader-os-anomaly-score mb-3"><?= nr((int) ($behavior_anomaly['score'] ?? 0)) ?></div>

                        <div class="leader-os-detail-list">
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.anomaly_signal_count') ?></span>
                                <strong><?= nr((int) ($behavior_anomaly['signals_total'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.anomaly_top_concern') ?></span>
                                <strong><?= htmlspecialchars((string) ($behavior_anomaly['top_concern'] ?? l('admin_leader_operating_system.leader.anomaly_none')), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <?php if(empty($behavior_anomaly['signals'])): ?>
                        <div class="leader-os-anomaly-item h-100 d-flex align-items-center">
                            <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.anomaly_empty') ?></div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach(($behavior_anomaly['signals'] ?? []) as $signal): ?>
                                <div class="col-12 mb-3">
                                    <div class="leader-os-anomaly-item">
                                        <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                                            <div class="font-weight-bold"><?= htmlspecialchars((string) ($signal['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <span class="leader-os-detail-status <?= htmlspecialchars((string) ($signal['class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars((string) ($signal['level_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                        <div class="text-muted small mb-2"><?= htmlspecialchars((string) ($signal['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div><?= htmlspecialchars((string) ($signal['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
    <!-- /Custom code: FC-2026-03-31 -->

    <!-- Custom code: FC-2026-03-31: Phase 6 privacy-safe fraud intelligence panel -->
    <div class="card leader-os-detail-shell mb-4" id="leader-os-fraud-intelligence">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <h3 class="h5 mb-1"><?= l('admin_leader_operating_system.leader.fraud_title') ?></h3>
                    <div class="text-muted small"><?= l('admin_leader_operating_system.leader.fraud_text') ?></div>
                </div>
                <span class="leader-os-detail-status <?= htmlspecialchars((string) ($fraud_intelligence['level_class'] ?? 'status-success'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) ($fraud_intelligence['level_label'] ?? l('admin_leader_operating_system.leader.fraud_level.stable')), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <div class="leader-os-detail-note mb-3"><?= sprintf(l('admin_leader_operating_system.leader.fraud_helper'), nr((int) ($fraud_intelligence['retention_days'] ?? 0))) ?></div>

            <?php if(!empty($data->fraud_action)): ?>
                <div class="leader-os-anomaly-item mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:.75rem;">
                        <div>
                            <div class="leader-os-ai-title mb-1">Recommended fraud action</div>
                            <div class="text-muted small"><?= htmlspecialchars((string) ($data->fraud_action['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <span class="leader-os-detail-status <?= htmlspecialchars((string) ($data->fraud_action['class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) ($data->fraud_action['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>
            <?php endif ?>

            <div class="row">
                <div class="col-12 col-xl-4 mb-3 mb-xl-0">
                    <div class="leader-os-anomaly-item h-100">
                        <div class="leader-os-ai-title mb-2"><?= l('admin_leader_operating_system.leader.fraud_score_label') ?></div>
                        <div class="leader-os-anomaly-score mb-3"><?= nr((int) ($fraud_intelligence['score'] ?? 0)) ?></div>

                        <div class="leader-os-detail-list">
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.fraud_accepted_clicks') ?></span>
                                <strong><?= nr((int) ($fraud_intelligence['accepted_clicks_total'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.fraud_blocked_attempts') ?></span>
                                <strong><?= nr((int) ($fraud_intelligence['blocked_attempts_total'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.fraud_cluster_count') ?></span>
                                <strong><?= nr((int) ($fraud_intelligence['clusters_total'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.fraud_top_concern') ?></span>
                                <strong><?= htmlspecialchars((string) ($fraud_intelligence['top_concern'] ?? l('admin_leader_operating_system.leader.fraud_none')), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.fraud_retention_label') ?></span>
                                <strong><?= sprintf(l('admin_leader_operating_system.leader.fraud_retention_value'), nr((int) ($fraud_intelligence['retention_days'] ?? 0))) ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted">Blocked / accepted omjer</span>
                                <strong><?= nr((float) ($fraud_intelligence['blocked_vs_accepted_ratio'] ?? 0)) ?>%</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <?php if(empty($fraud_intelligence['clusters'])): ?>
                        <div class="leader-os-anomaly-item h-100 d-flex align-items-center">
                            <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.fraud_empty') ?></div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach(($fraud_intelligence['clusters'] ?? []) as $cluster): ?>
                                <div class="col-12 mb-3">
                                    <div class="leader-os-anomaly-item">
                                        <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                                            <div>
                                                <div class="font-weight-bold"><?= htmlspecialchars((string) ($cluster['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="text-muted small mt-1"><?= htmlspecialchars((string) ($cluster['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>
                                            <span class="leader-os-detail-status <?= ((int) ($cluster['score'] ?? 0)) >= 55 ? 'status-warning' : 'status-info' ?>">
                                                <?= nr((int) ($cluster['score'] ?? 0)) ?>
                                            </span>
                                        </div>

                                        <div class="leader-os-detail-list mb-2">
                                            <div class="leader-os-detail-list-item">
                                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.fraud_cluster_visitors') ?></span>
                                                <strong><?= nr((int) ($cluster['visitors_total'] ?? 0)) ?></strong>
                                            </div>
                                            <div class="leader-os-detail-list-item">
                                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.fraud_cluster_funnels') ?></span>
                                                <strong><?= nr((int) ($cluster['funnels_total'] ?? 0)) ?></strong>
                                            </div>
                                            <div class="leader-os-detail-list-item">
                                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.fraud_cluster_duration') ?></span>
                                                <strong><?= nr((int) ($cluster['duration_minutes'] ?? 0)) ?> min</strong>
                                            </div>
                                        </div>

                                        <div><?= htmlspecialchars((string) ($cluster['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12 col-xl-6 mb-3">
                    <div class="leader-os-anomaly-item h-100">
                        <div class="leader-os-ai-title mb-2">Top fraud razlozi</div>
                        <?php if(empty($fraud_intelligence['top_reasons'])): ?>
                            <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.fraud_empty') ?></div>
                        <?php else: ?>
                            <?php foreach(($fraud_intelligence['top_reasons'] ?? []) as $item): ?>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                                </div>
                            <?php endforeach ?>
                        <?php endif ?>
                    </div>
                </div>

                <div class="col-12 col-xl-6 mb-3">
                    <div class="leader-os-anomaly-item h-100">
                        <div class="leader-os-ai-title mb-2">Top fraud targeti</div>
                        <?php if(empty($fraud_intelligence['top_targets'])): ?>
                            <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.fraud_empty') ?></div>
                        <?php else: ?>
                            <?php foreach(($fraud_intelligence['top_targets'] ?? []) as $item): ?>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong><?= nr((int) ($item['total'] ?? 0)) ?></strong>
                                </div>
                            <?php endforeach ?>
                        <?php endif ?>
                    </div>
                </div>
            </div>

            <?php if(!empty($fraud_intelligence['recent_attempts'])): ?>
                <div class="leader-os-anomaly-item mt-3">
                    <div class="leader-os-ai-title mb-2"><?= l('admin_leader_operating_system.leader.fraud_recent_attempts') ?></div>

                    <?php foreach(($fraud_intelligence['recent_attempts'] ?? []) as $attempt): ?>
                        <div class="border-bottom py-2">
                            <div class="font-weight-bold"><?= htmlspecialchars((string) ($attempt['reason_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small mt-1"><?= htmlspecialchars((string) ($attempt['reason_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small mt-1">
                                <?= l('admin_leader_operating_system.leader.fraud_recent_time') ?>:
                                <strong class="text-white"><?= \Altum\Date::get($attempt['datetime'] ?? '', 2) ?></strong>
                                <?php if(!empty($attempt['target_label'])): ?>
                                    · <?= l('admin_leader_operating_system.leader.fraud_recent_target') ?>:
                                    <strong class="text-white"><?= htmlspecialchars((string) ($attempt['target_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php endif ?>
                                <?php if(!empty($attempt['source_type'])): ?>
                                    · <?= l('admin_leader_operating_system.leader.fraud_recent_source') ?>:
                                    <strong class="text-white"><?= htmlspecialchars((string) ($attempt['source_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php endif ?>
                                <?php if(!empty($attempt['ip_address'])): ?>
                                    · IP:
                                    <strong class="text-white"><?= htmlspecialchars((string) ($attempt['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php endif ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
    <!-- /Custom code: FC-2026-03-31 -->

    <div class="card leader-os-detail-shell mb-4">
        <div class="card-body">
            <?php if(!empty($data->consistency)): ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <div>
                        <h3 class="h5 mb-1">Consistency score</h3>
                        <div class="text-muted small">Signal discipline rada, kontinuiteta aktivnosti i prolaska kroz AI weekly ciklus.</div>
                    </div>
                    <span class="leader-os-detail-status <?= htmlspecialchars((string) ($data->consistency['state_class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>">
                        <?= nr((int) ($data->consistency['score'] ?? 0)) ?> · <?= htmlspecialchars((string) ($data->consistency['state_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            <?php endif ?>

            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <h3 class="h5 mb-1">Blog -> Forever Living</h3>
                    <div class="text-muted small">Pregled koliko blog članci stvarno vode prema Forever Living odredištima u odabranom periodu.</div>
                </div>
                <span class="leader-os-detail-status status-info">
                    <?= nr((int) ($selected['blog_forever']['total_clicks'] ?? 0)) ?>
                </span>
            </div>

            <div class="row">
                <div class="col-12 col-lg-3 mb-3">
                    <div class="leader-os-anomaly-item h-100">
                        <div class="text-muted small mb-1">Ukupni klikovi</div>
                        <div class="h3 mb-0"><?= nr((int) ($selected['blog_forever']['total_clicks'] ?? 0)) ?></div>
                    </div>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <div class="leader-os-anomaly-item h-100">
                        <div class="text-muted small mb-1">Product CTA</div>
                        <div class="h3 mb-0"><?= nr((int) ($selected['blog_forever']['product_clicks'] ?? 0)) ?></div>
                    </div>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <div class="leader-os-anomaly-item h-100">
                        <div class="text-muted small mb-1">Business CTA</div>
                        <div class="h3 mb-0"><?= nr((int) ($selected['blog_forever']['business_clicks'] ?? 0)) ?></div>
                    </div>
                </div>
                <div class="col-12 col-lg-3 mb-3">
                    <div class="leader-os-anomaly-item h-100">
                        <div class="text-muted small mb-1">Udio u Forever klikovima</div>
                        <div class="h3 mb-0"><?= nr((float) ($selected['blog_forever']['share_percent'] ?? 0)) ?>%</div>
                    </div>
                </div>
            </div>

            <div class="leader-os-detail-note">
                Top blog članak:
                <strong><?= htmlspecialchars((string) ($selected['blog_forever']['top_article_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if(!empty($selected['blog_forever']['top_article_clicks'])): ?>
                    · <?= nr((int) ($selected['blog_forever']['top_article_clicks'] ?? 0)) ?> klikova
                <?php endif ?>
            </div>
        </div>
    </div>

    <?php if(!empty($data->ai_text_detail['has_any'])): ?>
        <div class="card leader-os-detail-shell mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <div>
                        <h3 class="h5 mb-1">AI open-text signal</h3>
                        <div class="text-muted small">Sažetak zadnjih tekstualnih signala iz check-ina, outcomea i AI plana.</div>
                    </div>
                    <span class="leader-os-detail-status status-info"><?= htmlspecialchars((string) ($data->detail['ai_plan_admin']['priority']['label'] ?? 'AI signal'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="leader-os-detail-list">
                    <div class="leader-os-detail-list-item">
                        <span class="text-muted">Trenutni fokus</span>
                        <strong><?= htmlspecialchars((string) (($data->ai_text_detail['focus_summary'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-detail-list-item">
                        <span class="text-muted">Weekly context</span>
                        <strong><?= htmlspecialchars((string) (($data->ai_text_detail['weekly_context'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-detail-list-item">
                        <span class="text-muted">Adaptive odgovor</span>
                        <strong><?= htmlspecialchars((string) (($data->ai_text_detail['adaptive_answer'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-detail-list-item">
                        <span class="text-muted">Glavni blocker sada</span>
                        <strong><?= htmlspecialchars((string) (($data->ai_text_detail['main_blocker_now'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-detail-list-item">
                        <span class="text-muted">Najveća lekcija</span>
                        <strong><?= htmlspecialchars((string) (($data->ai_text_detail['biggest_lesson'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-detail-list-item">
                        <span class="text-muted">Next adjustment</span>
                        <strong><?= htmlspecialchars((string) (($data->ai_text_detail['next_adjustment'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-detail-list-item">
                        <span class="text-muted">Najbolji odgovor</span>
                        <strong><?= htmlspecialchars((string) (($data->ai_text_detail['best_response'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>

    <div class="row">
        <div class="col-12 col-xl-7 mb-3">
            <div class="leader-os-detail-panel h-100">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:.75rem;">
                    <div>
                        <h3 class="h5 mb-1">Operativni sažetak suradnika</h3>
                        <div class="text-muted small">Najvažnije poslovne činjenice, glavni rizik i najbolji sljedeći potez na jednom mjestu.</div>
                    </div>
                    <span class="leader-os-detail-chip is-subtle"><?= l('admin_leader_operating_system.period_' . $data->selected_period) ?></span>
                </div>

                <div class="leader-os-cockpit-summary-grid">
                    <div>
                        <div class="leader-os-cockpit-spotlight">
                            <div class="leader-os-cockpit-spotlight-title">Što trenutno radi</div>
                            <div class="leader-os-cockpit-spotlight-text">
                                <?= htmlspecialchars((string) (($ai_text_detail['focus_summary'] ?? '') ?: $selected['next_step']), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>

                        <div class="leader-os-cockpit-spotlight">
                            <div class="leader-os-cockpit-spotlight-title">Gdje najviše zapinje</div>
                            <div class="leader-os-cockpit-spotlight-text">
                                <strong><?= htmlspecialchars((string) $main_risk_label, ENT_QUOTES, 'UTF-8') ?></strong><br>
                                <?= htmlspecialchars((string) $main_risk_text, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>

                        <div class="leader-os-cockpit-spotlight">
                            <div class="leader-os-cockpit-spotlight-title">Što prvo promijeniti</div>
                            <div class="leader-os-cockpit-spotlight-text">
                                <strong><?= htmlspecialchars((string) $main_opportunity_label, ENT_QUOTES, 'UTF-8') ?></strong><br>
                                <?= htmlspecialchars((string) $main_opportunity_text, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="leader-os-detail-list">
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted">Ukupni klikovi</span>
                                <strong><?= nr((int) ($selected['clicks_total_period'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted">Kvaliteta aplikacije</span>
                                <strong><?= nr((int) ($selected['app_quality_score'] ?? 0)) ?> · <?= htmlspecialchars((string) ($selected['app_quality_stage_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted">Shop share / registracije</span>
                                <strong><?= nr((float) ($selected['shop_share_percent'] ?? 0)) ?>% · <?= nr((float) ($selected['registration_rate_percent'] ?? 0)) ?>%</strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted">App signal</span>
                                <strong>Shop <?= nr((int) ($selected['app_shop_contacts_period'] ?? 0)) ?> · WA <?= nr((int) ($selected['app_whatsapp_contacts_period'] ?? 0)) ?> · Funnel <?= nr((int) ($selected['app_funnel_registrations_period'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted">Top grad / jezik</span>
                                <strong><?= htmlspecialchars((string) (($selected['top_cities'][0]['label'] ?? '-') . ' · ' . ($selected['top_language_label'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted">Funnel aktivnost</span>
                                <strong><?= nr((int) ($selected['funnel']['total_leads'] ?? 0)) ?> leadova · <?= nr((float) ($selected['funnel']['conversion_rate'] ?? 0)) ?>%</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5 mb-3">
            <div class="leader-os-detail-panel">
                <h3 class="h5 mb-2" id="leader-os-ai-report">AI analiza i akcijski centar</h3>
                <div class="text-muted small mb-3">Ovdje pokrećeš individualnu AI analizu, pratiš zadnju verziju i odmah pripremaš poruku ili follow-up za suradnika.</div>

                <div class="leader-os-detail-note mb-3">Prvo pogledaj headline i executive summary, zatim `primarne rizike`, `prilike` i `sljedećih 30 dana`, pa iz istog profila pošalji poruku ili follow-up.</div>

                <div class="leader-os-detail-list mb-3">
                    <div class="leader-os-detail-list-item">
                        <span class="text-muted">Admin status</span>
                        <strong><?= htmlspecialchars((string) $mentor_status_label, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="leader-os-detail-list-item">
                        <span class="text-muted">Treba follow-up</span>
                        <strong><?= !empty($mentor_actions['needs_follow_up']) ? l('global.yes') : l('global.no') ?></strong>
                    </div>
                    <div class="leader-os-detail-list-item">
                        <span class="text-muted">Mentoriran ovaj tjedan</span>
                        <strong><?= !empty($mentor_actions['mentored_this_week']) ? l('global.yes') : l('global.no') ?></strong>
                    </div>
                </div>

                <div class="leader-os-ai-actions mb-3">
                    <?php if($ai_report): ?>
                        <form action="" method="post" class="mb-0">
                            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                            <button type="submit" name="regenerate_ai_report" value="1" class="btn btn-sm leader-os-detail-action"><?= l('admin_leader_operating_system.leader.ai_regenerate') ?></button>
                        </form>
                        <button type="button" class="btn btn-sm leader-os-detail-action leader-os-scroll-link" data-scroll-target="leader-os-ai-history"><?= l('admin_leader_operating_system.leader.ai_open_history') ?></button>
                        <button type="button" class="btn btn-sm leader-os-detail-action leader-os-scroll-link" data-scroll-target="leader-os-phase4">Otvori coaching</button>
                    <?php else: ?>
                        <form action="" method="post" class="mb-0">
                            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                            <button type="submit" name="generate_ai_report" value="1" class="btn btn-sm leader-os-ai-button"><?= l('admin_leader_operating_system.leader.ai_generate') ?></button>
                        </form>
                    <?php endif ?>
                </div>

                <?php if($ai_report): ?>
                    <div class="leader-os-ai-report">
                        <div class="leader-os-ai-section">
                            <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.ai_generated_at') ?></div>
                            <div><?= \Altum\Date::get($ai_report['generated_at'], 2) ?> · <?= htmlspecialchars($ai_report['model'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if(!empty($los_outreach['latest_report']['version_number'])): ?>
                                <div class="mt-2"><span class="leader-os-detail-chip is-subtle"><?= sprintf(l('admin_leader_operating_system.leader.report_version'), nr((int) $los_outreach['latest_report']['version_number'])) ?></span></div>
                            <?php endif ?>
                            <div class="text-muted small mt-2"><?= l('admin_leader_operating_system.leader.ai_history_hint') ?></div>
                        </div>

                        <div class="leader-os-ai-section">
                            <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.ai_headline') ?></div>
                            <div class="h6 mb-2"><?= htmlspecialchars($ai_report['headline'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($ai_report['executive_summary'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <?php if(!empty($ai_report['primary_risks'])): ?>
                            <details class="leader-os-ai-details">
                                <summary><?= l('admin_leader_operating_system.leader.ai_primary_risks') ?></summary>
                                <ul class="leader-os-ai-list">
                                    <?php foreach($ai_report['primary_risks'] as $item): ?>
                                        <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach ?>
                                </ul>
                            </details>
                        <?php endif ?>

                        <?php if(!empty($ai_report['opportunities'])): ?>
                            <details class="leader-os-ai-details">
                                <summary><?= l('admin_leader_operating_system.leader.ai_opportunities') ?></summary>
                                <ul class="leader-os-ai-list">
                                    <?php foreach($ai_report['opportunities'] as $item): ?>
                                        <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach ?>
                                </ul>
                            </details>
                        <?php endif ?>

                        <details class="leader-os-ai-details" open>
                            <summary><?= l('admin_leader_operating_system.leader.ai_next_30_days') ?></summary>
                            <ul class="leader-os-ai-list">
                                <?php foreach($ai_report['next_30_days'] as $item): ?>
                                    <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach ?>
                            </ul>
                        </details>

                        <details class="leader-os-ai-details">
                            <summary><?= l('admin_leader_operating_system.leader.ai_email_draft') ?></summary>

                            <div class="leader-os-ai-email-box mb-2">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.ai_email_subject') ?></div>
                                <strong><?= htmlspecialchars($ai_report['email_subject'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>

                            <div class="leader-os-ai-email-box mb-2">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.ai_email_intro') ?></div>
                                <div><?= htmlspecialchars($ai_report['email_intro'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>

                            <?php if(!empty($ai_report['email_body_points'])): ?>
                                <div class="leader-os-ai-email-box mb-2">
                                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.ai_email_points') ?></div>
                                    <ul class="leader-os-ai-list mb-0">
                                        <?php foreach($ai_report['email_body_points'] as $item): ?>
                                            <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                                        <?php endforeach ?>
                                    </ul>
                                </div>
                            <?php endif ?>

                            <div class="leader-os-ai-email-box">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.ai_email_cta') ?></div>
                                <div><?= htmlspecialchars($ai_report['email_cta'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </details>
                    </div>
                <?php else: ?>
                    <div class="leader-os-ai-section">
                        <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.ai_empty') ?></div>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- Custom code: FC-2026-03-31: LOS outreach review and history panel -->
    <div class="card leader-os-detail-shell mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-xl-7 mb-3 mb-xl-0">
                    <div class="leader-os-detail-panel h-100" id="leader-os-outreach-panel">
                        <h3 class="h5 mb-2"><?= l('admin_leader_operating_system.leader.outreach_panel') ?></h3>
                        <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.outreach_panel_text') ?></div>

                        <?php if(empty($los_outreach['latest_report'])): ?>
                            <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.outreach_empty') ?></div>
                        <?php else: ?>
                            <div class="leader-os-detail-list mb-3">
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('admin_leader_operating_system.leader.outreach_latest_report') ?></span>
                                    <strong><?= !empty($los_outreach['latest_report']['generated_at']) ? \Altum\Date::get($los_outreach['latest_report']['generated_at'], 2) : '-' ?></strong>
                                </div>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('admin_leader_operating_system.leader.outreach_latest_send') ?></span>
                                    <strong><?= !empty($los_outreach['latest_send']['sent_at']) ? \Altum\Date::get($los_outreach['latest_send']['sent_at'], 2) : '-' ?></strong>
                                </div>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('admin_leader_operating_system.leader.outreach_follow_up_status') ?></span>
                                    <strong><?= !empty($mentor_actions['needs_follow_up']) ? l('global.yes') : l('global.no') ?></strong>
                                </div>
                            </div>

                            <form action="" method="post" class="leader-os-outreach-form">
                                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                                <div class="form-group">
                                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.leader.outreach_email') ?></label>
                                    <input type="email" name="outreach_email" class="form-control" maxlength="320" value="<?= htmlspecialchars((string) ($los_outreach['draft_email'] ?? $detail['email']), ENT_QUOTES, 'UTF-8') ?>" />
                                </div>

                                <div class="form-group">
                                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.leader.outreach_subject') ?></label>
                                    <input type="text" name="outreach_subject" class="form-control" maxlength="320" value="<?= htmlspecialchars((string) ($los_outreach['draft_subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.leader.outreach_body') ?></label>
                                    <textarea name="outreach_body" rows="9" class="form-control" maxlength="12000"><?= htmlspecialchars((string) ($los_outreach['draft_body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>

                                <button type="submit" name="send_ai_report" value="1" class="btn btn-sm leader-os-ai-button"><?= l('admin_leader_operating_system.leader.outreach_send') ?></button>
                            </form>
                        <?php endif ?>
                    </div>
                </div>

                <div class="col-12 col-xl-5">
                    <div class="leader-os-detail-panel h-100" id="leader-os-ai-history">
                        <h3 class="h5 mb-2"><?= l('admin_leader_operating_system.leader.outreach_history') ?></h3>
                        <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.outreach_history_text') ?></div>

                        <?php if(empty($report_history) && empty($send_history)): ?>
                            <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.outreach_history_empty') ?></div>
                        <?php else: ?>
                            <div class="leader-os-outreach-history">
                                <?php if(!empty($report_history)): ?>
                                    <div class="leader-os-ai-title mb-1"><?= l('admin_leader_operating_system.leader.outreach_report_history') ?></div>
                                    <?php foreach($report_history as $report_item): ?>
                                        <div class="leader-os-outreach-history-item">
                                            <div class="d-flex justify-content-between align-items-start mb-1" style="gap:.75rem;">
                                                <div class="font-weight-bold"><?= htmlspecialchars((string) ($report_item['headline'] ?? $report_item['email_subject'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php if(!empty($report_item['version_number'])): ?>
                                                    <span class="leader-os-detail-chip is-subtle"><?= sprintf(l('admin_leader_operating_system.leader.report_version'), nr((int) $report_item['version_number'])) ?></span>
                                                <?php endif ?>
                                            </div>
                                            <div class="text-muted small mb-1"><?= !empty($report_item['generated_at']) ? \Altum\Date::get($report_item['generated_at'], 2) : '-' ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars((string) ($report_item['email_subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <button
                                                type="button"
                                                class="btn btn-sm leader-os-detail-action leader-os-load-report mt-2"
                                                data-email="<?= htmlspecialchars((string) ($los_outreach['draft_email'] ?? $detail['email']), ENT_QUOTES, 'UTF-8') ?>"
                                                data-subject="<?= htmlspecialchars((string) ($report_item['email_subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-body="<?= htmlspecialchars(implode("\n\n", array_filter([
                                                    (string) ($report_item['email_intro'] ?? ''),
                                                    !empty($report_item['email_body_points']) ? implode("\n", array_map(static function($item) {
                                                        return '- ' . $item;
                                                    }, (array) $report_item['email_body_points'])) : '',
                                                    (string) ($report_item['email_cta'] ?? ''),
                                                ])), ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                <?= l('admin_leader_operating_system.leader.outreach_use_report') ?>
                                            </button>
                                        </div>
                                    <?php endforeach ?>
                                <?php endif ?>

                                <?php if(!empty($send_history)): ?>
                                    <div class="leader-os-ai-title mb-1 mt-2"><?= l('admin_leader_operating_system.leader.outreach_send_history') ?></div>
                                    <?php foreach($send_history as $send_item): ?>
                                        <div class="leader-os-outreach-history-item">
                                            <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                                                <div>
                                                    <div class="font-weight-bold"><?= htmlspecialchars((string) ($send_item['subject'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars((string) ($send_item['email_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                                <div class="d-flex flex-column align-items-end" style="gap:.35rem;">
                                                    <span class="leader-os-outreach-status"><?= htmlspecialchars((string) ($send_item['status'] ?? 'sent'), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php if(!empty($send_item['report_version_number'])): ?>
                                                        <span class="leader-os-detail-chip is-subtle"><?= sprintf(l('admin_leader_operating_system.leader.report_version_short'), nr((int) $send_item['report_version_number'])) ?></span>
                                                    <?php endif ?>
                                                </div>
                                            </div>
                                            <div class="text-muted small mb-1"><?= !empty($send_item['sent_at']) ? \Altum\Date::get($send_item['sent_at'], 2) : '-' ?></div>
                                            <?php if(!empty($send_item['body_snapshot'])): ?>
                                                <div class="text-muted small"><?= nl2br(htmlspecialchars(mb_substr((string) $send_item['body_snapshot'], 0, 220), ENT_QUOTES, 'UTF-8')) ?><?= mb_strlen((string) $send_item['body_snapshot']) > 220 ? '…' : '' ?></div>
                                            <?php endif ?>
                                        </div>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Custom code: FC-2026-03-31 -->

    <!-- Custom code: FC-2026-03-31: Show current app structure used as AI context -->
    <div class="card leader-os-detail-shell mb-4" id="leader-os-app-structure">
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-xl-5 mb-3 mb-xl-0">
                    <div class="leader-os-detail-panel h-100">
                        <h3 class="h5 mb-2"><?= l('admin_leader_operating_system.leader.panel_app_structure') ?></h3>
                        <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.panel_app_structure_text') ?></div>

                        <!-- Custom code: FC-2026-03-31: LOS detail app structure helper -->
                        <div class="leader-os-detail-note mb-3"><?= l('admin_leader_operating_system.leader.panel_app_structure_helper') ?></div>
                        <!-- /Custom code: FC-2026-03-31 -->

                        <div class="leader-os-structure-health">
                            <div>
                                <div class="leader-os-ai-title mb-2"><?= l('admin_leader_operating_system.leader.structure_health_title') ?></div>
                                <div class="leader-os-structure-score"><?= nr((int) ($app_structure['health_score'] ?? 0)) ?></div>
                            </div>

                            <div class="text-right">
                                <span class="leader-os-detail-status <?= htmlspecialchars((string) ($app_structure['health_class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($app_structure['health_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                <div class="text-muted small mt-2"><?= l('admin_leader_operating_system.leader.structure_health_text') ?></div>
                            </div>
                        </div>

                        <div class="leader-os-detail-list">
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.total_apps') ?></span>
                                <strong><?= nr((int) ($app_structure['total_apps'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.top_app_url') ?></span>
                                <strong><?= htmlspecialchars((string) ($app_structure['top_app_url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="leader-os-detail-list-item">
                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.top_app_blocks') ?></span>
                                <strong><?= nr((int) ($app_structure['top_app_total_blocks'] ?? 0)) ?></strong>
                            </div>
                        </div>

                        <div class="leader-os-ai-title mt-3"><?= l('admin_leader_operating_system.leader.structure_counts_title') ?></div>
                        <div class="leader-os-structure-counts">
                            <div class="leader-os-structure-count">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.structure_count.content') ?></div>
                                <strong><?= nr((int) ($app_structure['content_stack_total'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-structure-count">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.structure_count.visuals') ?></div>
                                <strong><?= nr((int) ($app_structure['visual_stack_total'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-structure-count">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.structure_count.social') ?></div>
                                <strong><?= nr((int) ($app_structure['social_stack_total'] ?? 0)) ?></strong>
                            </div>
                            <div class="leader-os-structure-count">
                                <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.structure_count.funnels') ?></div>
                                <strong><?= nr((int) ($app_structure['funnel_stack_total'] ?? 0)) ?></strong>
                            </div>
                        </div>

                        <div class="leader-os-ai-title mt-3"><?= l('admin_leader_operating_system.leader.composition_title') ?></div>
                        <div class="leader-os-design-review-box">
                            <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                                <div>
                                    <div class="leader-os-design-score"><?= nr((int) ($app_structure['composition_score'] ?? 0)) ?></div>
                                    <div class="text-muted small mt-2"><?= l('admin_leader_operating_system.leader.composition_text') ?></div>
                                </div>
                                <span class="leader-os-detail-status <?= htmlspecialchars((string) ($app_structure['composition_class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($app_structure['composition_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <div class="leader-os-design-metrics">
                                <div class="leader-os-design-metric">
                                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.cta_audit_title') ?></div>
                                    <strong><?= nr((int) ($app_structure['cta_audit']['score'] ?? 0)) ?></strong>
                                    <div class="text-muted small mt-1"><?= htmlspecialchars((string) ($app_structure['cta_audit']['summary'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div class="leader-os-design-metric">
                                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.trust_audit_title') ?></div>
                                    <strong><?= nr((int) ($app_structure['trust_audit']['score'] ?? 0)) ?></strong>
                                    <div class="text-muted small mt-1"><?= htmlspecialchars((string) ($app_structure['trust_audit']['summary'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div class="leader-os-design-metric">
                                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.leader.content_audit_title') ?></div>
                                    <strong><?= nr((int) ($app_structure['content_audit']['score'] ?? 0)) ?></strong>
                                    <div class="text-muted small mt-1"><?= htmlspecialchars((string) ($app_structure['content_audit']['summary'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-7">
                    <div class="leader-os-detail-panel h-100">
                        <div class="leader-os-design-review-grid">
                            <div class="leader-os-design-review-box">
                                <h3 class="h5 mb-2"><?= l('admin_leader_operating_system.leader.page_review_title') ?></h3>
                                <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.page_review_text') ?></div>

                                <?php foreach(($app_structure['page_review']['checklist'] ?? []) as $review_item): ?>
                                    <div class="leader-os-review-check">
                                        <span class="text-muted"><?= htmlspecialchars((string) ($review_item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="leader-os-detail-status <?= htmlspecialchars((string) ($review_item['class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($review_item['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                <?php endforeach ?>

                                <?php if(!empty($app_structure['top_apps'])): ?>
                                    <div class="leader-os-ai-title mt-4"><?= l('admin_leader_operating_system.leader.top_apps_title') ?></div>
                                    <div class="leader-os-detail-list">
                                        <?php foreach(($app_structure['top_apps'] ?? []) as $top_app_item): ?>
                                            <div class="leader-os-detail-list-item">
                                                <div>
                                                    <div class="font-weight-bold"><?= htmlspecialchars((string) ($top_app_item['url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="text-muted small"><?= sprintf(l('admin_leader_operating_system.leader.top_apps_meta'), nr((int) ($top_app_item['total_blocks'] ?? 0)), nr((int) ($top_app_item['cta_blocks'] ?? 0)), nr((int) ($top_app_item['trust_blocks'] ?? 0))) ?></div>
                                                </div>
                                                <?php if(!empty($top_app_item['public_url'])): ?>
                                                    <a href="<?= htmlspecialchars((string) $top_app_item['public_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="leader-os-link"><?= l('admin_leader_operating_system.leader.page_review_open_live') ?></a>
                                                <?php endif ?>
                                            </div>
                                        <?php endforeach ?>
                                    </div>
                                <?php endif ?>
                            </div>

                            <div class="leader-os-design-review-box">
                                <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                                    <div>
                                        <h3 class="h5 mb-1"><?= l('admin_leader_operating_system.leader.preview_title') ?></h3>
                                        <div class="text-muted small"><?= l('admin_leader_operating_system.leader.preview_text') ?></div>
                                    </div>
                                    <?php if(!empty($app_structure['page_review']['public_url'])): ?>
                                        <a href="<?= htmlspecialchars((string) $app_structure['page_review']['public_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="leader-os-link"><?= l('admin_leader_operating_system.leader.page_review_open_live') ?></a>
                                    <?php endif ?>
                                </div>

                                <?php if(!empty($app_structure['page_review']['has_preview']) && !empty($app_structure['page_review']['public_url'])): ?>
                                    <div class="leader-os-preview-frame">
                                        <iframe src="<?= htmlspecialchars((string) $app_structure['page_review']['public_url'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" title="<?= htmlspecialchars((string) ($app_structure['page_review']['preview_title'] ?? 'App preview'), ENT_QUOTES, 'UTF-8') ?>"></iframe>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.preview_empty') ?></div>
                                <?php endif ?>
                            </div>
                        </div>

                        <h3 class="h5 mb-2"><?= l('admin_leader_operating_system.leader.structure_diagnostics_title') ?></h3>
                        <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.structure_diagnostics_text') ?></div>

                        <?php foreach(($app_structure['diagnostics'] ?? []) as $diagnostic): ?>
                            <div class="leader-os-structure-diagnostic">
                                <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                                    <div class="font-weight-bold"><?= htmlspecialchars((string) ($diagnostic['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <span class="leader-os-detail-status <?= htmlspecialchars((string) ($diagnostic['class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($diagnostic['state_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($diagnostic['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endforeach ?>

                        <h3 class="h5 mt-4 mb-2"><?= l('admin_leader_operating_system.leader.composition_findings_title') ?></h3>
                        <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.composition_findings_text') ?></div>

                        <?php foreach(($app_structure['composition_findings'] ?? []) as $diagnostic): ?>
                            <div class="leader-os-structure-diagnostic">
                                <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                                    <div class="font-weight-bold"><?= htmlspecialchars((string) ($diagnostic['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <span class="leader-os-detail-status <?= htmlspecialchars((string) ($diagnostic['class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($diagnostic['state_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($diagnostic['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endforeach ?>

                        <h3 class="h5 mt-4 mb-2"><?= l('admin_leader_operating_system.leader.block_mix') ?></h3>
                        <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.block_mix_text') ?></div>

                        <div class="leader-os-detail-list">
                            <?php if(empty($app_structure['block_mix'])): ?>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.leader.no_breakdown_data') ?></div>
                            <?php else: ?>
                                <?php foreach($app_structure['block_mix'] as $block_item): ?>
                                    <div class="leader-os-detail-list-item">
                                        <span class="text-muted"><?= htmlspecialchars((string) ($block_item['type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= nr((int) ($block_item['total'] ?? 0)) ?></strong>
                                    </div>
                                <?php endforeach ?>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Custom code: FC-2026-03-31 -->

    <!-- Custom code: FC-2026-03-31: Phase 4 admin coaching panel from AI Plan history -->
    <div class="card leader-os-detail-shell mb-4" id="leader-os-phase4">
        <div class="card-body">
            <?php if(!empty($data->coaching_roi)): ?>
                <div class="leader-os-detail-panel mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <div>
                            <h3 class="h5 mb-1">Coaching ROI</h3>
                            <div class="text-muted small">Rani signal pomaka nakon zadnjeg mentoring kontakta i score snapshotova.</div>
                        </div>
                        <span class="leader-os-detail-status <?= htmlspecialchars((string) ($data->coaching_roi['signal_class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) ($data->coaching_roi['signal_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <div class="leader-os-detail-list">
                        <div class="leader-os-detail-list-item">
                            <span class="text-muted">Zadnji coaching touch</span>
                            <strong><?= !empty($data->coaching_roi['anchor_at']) ? \Altum\Date::get($data->coaching_roi['anchor_at'], 2) : '-' ?></strong>
                        </div>
                        <div class="leader-os-detail-list-item">
                            <span class="text-muted">Dana od kontakta</span>
                            <strong><?= $data->coaching_roi['days_since_touch'] !== null ? nr((int) $data->coaching_roi['days_since_touch']) : '-' ?></strong>
                        </div>
                        <div class="leader-os-detail-list-item">
                            <span class="text-muted">LOS score prije / sada</span>
                            <strong>
                                <?= $data->coaching_roi['score_before'] !== null ? nr((int) $data->coaching_roi['score_before']) : '-' ?>
                                /
                                <?= $data->coaching_roi['score_current'] !== null ? nr((int) $data->coaching_roi['score_current']) : '-' ?>
                            </strong>
                        </div>
                        <div class="leader-os-detail-list-item">
                            <span class="text-muted">Delta score</span>
                            <strong><?= $data->coaching_roi['score_delta'] !== null ? (($data->coaching_roi['score_delta'] > 0 ? '+' : '') . nr((int) $data->coaching_roi['score_delta'])) : '-' ?></strong>
                        </div>
                        <div class="leader-os-detail-list-item">
                            <span class="text-muted">Risk prije / sada</span>
                            <strong>
                                <?= $data->coaching_roi['risk_before'] !== null ? nr((int) $data->coaching_roi['risk_before']) : '-' ?>
                                /
                                <?= $data->coaching_roi['risk_current'] !== null ? nr((int) $data->coaching_roi['risk_current']) : '-' ?>
                            </strong>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <div class="row">
                <div class="col-12 col-xl-5 mb-3 mb-xl-0">
                    <div class="leader-os-detail-panel h-100">
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                            <div>
                                <h3 class="h5 mb-2"><?= l('admin_leader_operating_system.leader.panel_ai_plan_coaching') ?></h3>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.leader.panel_ai_plan_coaching_text') ?></div>
                            </div>

                            <?php if(!empty($ai_plan_admin['priority']['label'])): ?>
                                <span class="leader-os-phase4-priority priority-<?= htmlspecialchars((string) ($ai_plan_admin['priority']['level'] ?? 'waiting'), ENT_QUOTES, 'UTF-8') ?> mt-2 mt-xl-0">
                                    <?= htmlspecialchars((string) ($ai_plan_admin['priority']['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            <?php endif ?>
                        </div>

                        <?php if(empty($ai_plan_admin['has_profile']) && empty($ai_plan_admin['has_checkin'])): ?>
                            <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.ai_plan_empty') ?></div>
                        <?php else: ?>
                            <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.ai_plan_mentor_actions') ?></div>
                            <?php if($mentor_ai_guidance_active): ?>
                                <div class="mb-3">
                                    <span class="leader-os-detail-chip"><?= l('admin_leader_operating_system.leader.ai_plan_ai_guidance_active') ?></span>
                                </div>
                            <?php endif ?>
                            <form action="" method="post" class="leader-os-phase4-form mb-3">
                                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                                <div class="leader-os-phase4-quick-actions">
                                    <button type="submit" name="toggle_follow_up" value="1" class="btn btn-sm leader-os-detail-action">
                                        <?= !empty($mentor_actions['needs_follow_up']) ? l('admin_leader_operating_system.leader.ai_plan_follow_up_remove') : l('admin_leader_operating_system.leader.ai_plan_follow_up_mark') ?>
                                    </button>

                                    <?php if(!empty($mentor_actions['mentored_this_week'])): ?>
                                        <button type="submit" name="reset_mentored_this_week" value="1" class="btn btn-sm leader-os-detail-action">
                                            <?= l('admin_leader_operating_system.leader.ai_plan_mentored_reset') ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="mark_mentored_this_week" value="1" class="btn btn-sm leader-os-ai-button">
                                            <?= l('admin_leader_operating_system.leader.ai_plan_mentored_mark') ?>
                                        </button>
                                    <?php endif ?>
                                </div>

                                <div class="leader-os-detail-list mb-3">
                                    <div class="leader-os-detail-list-item">
                                        <span class="text-muted"><?= l('admin_leader_operating_system.leader.ai_plan_follow_up_status') ?></span>
                                        <strong><?= !empty($mentor_actions['needs_follow_up']) ? l('global.yes') : l('global.no') ?></strong>
                                    </div>
                                    <div class="leader-os-detail-list-item">
                                        <span class="text-muted"><?= l('admin_leader_operating_system.leader.ai_plan_mentored_status') ?></span>
                                        <strong><?= !empty($mentor_actions['mentored_this_week']) ? l('global.yes') : l('global.no') ?></strong>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.leader.ai_plan_admin_status') ?></label>
                                    <select name="mentor_status" class="custom-select">
                                        <?php foreach(['pending_contact', 'in_progress', 'monitoring', 'resolved'] as $mentor_status): ?>
                                            <option value="<?= $mentor_status ?>" <?= (($mentor_actions['status'] ?? 'pending_contact') === $mentor_status) ? 'selected="selected"' : null ?>><?= l('admin_leader_operating_system.leader.ai_plan_admin_status.' . $mentor_status) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.leader.ai_plan_next_action') ?></label>
                                    <input type="text" name="mentor_next_action" maxlength="280" class="form-control" value="<?= htmlspecialchars((string) ($mentor_actions['next_action'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= l('admin_leader_operating_system.leader.ai_plan_next_action_placeholder') ?>" />
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.leader.ai_plan_private_note') ?></label>
                                    <textarea name="mentor_note" rows="4" maxlength="2000" class="form-control" placeholder="<?= l('admin_leader_operating_system.leader.ai_plan_private_note_placeholder') ?>"><?= htmlspecialchars((string) ($mentor_actions['mentor_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>

                                <!-- Custom code: FC-2026-03-31: Separate mentor AI guidance from private note -->
                                <div class="form-group mb-3">
                                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.leader.ai_plan_ai_guidance') ?></label>
                                    <textarea name="mentor_ai_guidance" rows="4" maxlength="2400" class="form-control" placeholder="<?= l('admin_leader_operating_system.leader.ai_plan_ai_guidance_placeholder') ?>"><?= htmlspecialchars((string) ($mentor_actions['ai_guidance'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    <div class="text-muted small mt-2 mb-0"><?= l('admin_leader_operating_system.leader.ai_plan_ai_guidance_help') ?></div>
                                </div>
                                <!-- /Custom code: FC-2026-03-31 -->

                                <button type="submit" name="save_mentor_actions" value="1" class="btn btn-sm leader-os-ai-button"><?= l('admin_leader_operating_system.leader.ai_plan_save_actions') ?></button>
                            </form>

                            <div class="leader-os-phase4-note mb-3">
                                <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.ai_plan_mentor_note') ?></div>
                                <div class="mb-2"><?= htmlspecialchars((string) ($ai_plan_admin['mentor_note'] ?? l('admin_leader_operating_system.leader.ai_plan_no_mentor_note')), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small mb-0"><?= htmlspecialchars((string) ($ai_plan_admin['priority']['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

                                <div class="leader-os-ai-title mt-3"><?= l('admin_leader_operating_system.leader.ai_plan_ai_guidance_summary') ?></div>
                                <div class="mb-2"><?= htmlspecialchars((string) (($mentor_actions['ai_guidance'] ?? '') ?: l('admin_leader_operating_system.leader.ai_plan_no_ai_guidance')), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.ai_plan_ai_guidance_summary_text') ?></div>
                            </div>

                            <div class="leader-os-detail-list mb-3">
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('admin_leader_operating_system.leader.ai_plan_history_total') ?></span>
                                    <strong><?= nr((int) ($ai_plan_admin['history_total'] ?? 0)) ?></strong>
                                </div>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('admin_leader_operating_system.leader.ai_plan_last_checkin_gap') ?></span>
                                    <strong>
                                        <?php if($ai_plan_admin['days_since_last_checkin'] !== null): ?>
                                            <?= sprintf(l('admin_leader_operating_system.leader.ai_plan_days_ago'), nr((int) $ai_plan_admin['days_since_last_checkin'])) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif ?>
                                    </strong>
                                </div>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('admin_leader_operating_system.leader.ai_plan_last_contacted') ?></span>
                                    <strong><?= !empty($mentor_actions['last_contacted_at']) ? \Altum\Date::get($mentor_actions['last_contacted_at'], 2) : '-' ?></strong>
                                </div>
                            </div>

                            <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.ai_plan_profile_snapshot') ?></div>
                            <div class="leader-os-detail-list">
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('ai_plan.primary_goal') ?></span>
                                    <strong><?= htmlspecialchars((string) (($ai_plan_admin['profile']['primary_goal_label'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('ai_plan.priority_offer') ?></span>
                                    <strong><?= htmlspecialchars((string) (($ai_plan_admin['profile']['priority_offer_label'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('ai_plan.biggest_blocker') ?></span>
                                    <strong><?= htmlspecialchars((string) (($ai_plan_admin['profile']['biggest_blocker_label'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('ai_plan.active_channels') ?></span>
                                    <strong><?= htmlspecialchars((string) (($ai_plan_admin['profile']['active_channels_label'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                            </div>

                            <div class="leader-os-ai-title mt-3"><?= l('admin_leader_operating_system.leader.ai_plan_history_title') ?></div>
                            <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.ai_plan_history_text') ?></div>

                            <?php if(empty($mentor_history)): ?>
                                <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.ai_plan_history_empty') ?></div>
                            <?php else: ?>
                                <div class="leader-os-phase4-history">
                                    <?php foreach($mentor_history as $history_item): ?>
                                        <div class="leader-os-phase4-history-item">
                                            <div class="font-weight-bold"><?= htmlspecialchars((string) ($history_item['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if(!empty($history_item['details'])): ?>
                                                <div class="text-muted small mt-2"><?= htmlspecialchars((string) ($history_item['details'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif ?>
                                            <div class="leader-os-phase4-history-meta">
                                                <span><?= !empty($history_item['admin_name']) ? htmlspecialchars((string) $history_item['admin_name'], ENT_QUOTES, 'UTF-8') : 'Admin' ?></span>
                                                <span><?= !empty($history_item['created_at']) ? \Altum\Date::get($history_item['created_at'], 2) : '-' ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            <?php endif ?>
                        <?php endif ?>
                    </div>
                </div>

                <div class="col-12 col-xl-7">
                    <div class="leader-os-detail-panel h-100">
                        <?php if(!empty($ai_plan_admin['latest_checkin'])): ?>
                            <div class="row mb-3">
                                <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                                    <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.ai_plan_current_week') ?></div>
                                    <div class="leader-os-detail-list">
                                        <div class="leader-os-detail-list-item">
                                            <span class="text-muted"><?= l('admin_leader_operating_system.leader.ai_plan_submitted_at') ?></span>
                                            <strong><?= !empty($ai_plan_admin['latest_checkin']['submitted_at']) ? \Altum\Date::get($ai_plan_admin['latest_checkin']['submitted_at'], 2) : '-' ?></strong>
                                        </div>
                                        <div class="leader-os-detail-list-item">
                                            <span class="text-muted"><?= l('ai_plan.weekly_priority') ?></span>
                                            <strong><?= htmlspecialchars((string) ($ai_plan_admin['latest_checkin']['weekly_priority_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="leader-os-detail-list-item">
                                            <span class="text-muted"><?= l('ai_plan.weekly_energy') ?></span>
                                            <strong><?= htmlspecialchars((string) ($ai_plan_admin['latest_checkin']['weekly_energy_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="leader-os-detail-list-item">
                                            <span class="text-muted"><?= l('ai_plan.ai_need') ?></span>
                                            <strong><?= htmlspecialchars((string) ($ai_plan_admin['latest_checkin']['ai_need_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.ai_plan_previous_week') ?></div>
                                    <?php if(!empty($ai_plan_admin['previous_checkin'])): ?>
                                        <div class="leader-os-detail-list">
                                            <div class="leader-os-detail-list-item">
                                                <span class="text-muted"><?= l('admin_leader_operating_system.leader.ai_plan_submitted_at') ?></span>
                                                <strong><?= !empty($ai_plan_admin['previous_checkin']['submitted_at']) ? \Altum\Date::get($ai_plan_admin['previous_checkin']['submitted_at'], 2) : '-' ?></strong>
                                            </div>
                                            <div class="leader-os-detail-list-item">
                                                <span class="text-muted"><?= l('ai_plan.weekly_priority') ?></span>
                                                <strong><?= htmlspecialchars((string) ($ai_plan_admin['previous_checkin']['weekly_priority_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </div>
                                            <div class="leader-os-detail-list-item">
                                                <span class="text-muted"><?= l('ai_plan.weekly_energy') ?></span>
                                                <strong><?= htmlspecialchars((string) ($ai_plan_admin['previous_checkin']['weekly_energy_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </div>
                                            <div class="leader-os-detail-list-item">
                                                <span class="text-muted"><?= l('ai_plan.ai_need') ?></span>
                                                <strong><?= htmlspecialchars((string) ($ai_plan_admin['previous_checkin']['ai_need_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted small"><?= l('admin_leader_operating_system.leader.ai_plan_no_previous_week') ?></div>
                                    <?php endif ?>
                                </div>
                            </div>

                            <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.ai_plan_change_log') ?></div>
                            <?php if(empty($ai_plan_admin['change_log'])): ?>
                                <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.ai_plan_no_changes') ?></div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <?php foreach($ai_plan_admin['change_log'] as $change): ?>
                                        <div class="leader-os-phase4-change">
                                            <div class="text-muted small"><?= htmlspecialchars((string) ($change['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="leader-os-phase4-delta">
                                                <span><?= htmlspecialchars((string) ($change['before'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="leader-os-phase4-arrow">&rarr;</span>
                                                <strong><?= htmlspecialchars((string) ($change['after'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </div>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            <?php endif ?>
                        <?php else: ?>
                            <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.ai_plan_no_checkin') ?></div>
                        <?php endif ?>

                        <?php if(!empty($ai_plan_admin['latest_plan'])): ?>
                            <div class="leader-os-ai-title"><?= l('admin_leader_operating_system.leader.ai_plan_latest_plan') ?></div>
                            <div class="leader-os-detail-list">
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('admin_leader_operating_system.leader.ai_plan_plan_generated') ?></span>
                                    <strong><?= !empty($ai_plan_admin['latest_plan']['generated_at']) ? \Altum\Date::get($ai_plan_admin['latest_plan']['generated_at'], 2) : '-' ?></strong>
                                </div>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('ai_plan.plan_focus') ?></span>
                                    <strong><?= htmlspecialchars((string) ($ai_plan_admin['latest_plan']['focus'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('ai_plan.plan_brutal_truth') ?></span>
                                    <strong><?= htmlspecialchars((string) (($ai_plan_admin['latest_plan']['brutal_truth'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="leader-os-detail-list-item">
                                    <span class="text-muted"><?= l('ai_plan.plan_power_move') ?></span>
                                    <strong><?= htmlspecialchars((string) (($ai_plan_admin['latest_plan']['power_move'] ?? '') ?: '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                            </div>

                            <?php if(!empty($ai_plan_admin['latest_plan']['coach_ideas'])): ?>
                                <details class="leader-os-ai-details mt-3" open>
                                    <summary><?= l('ai_plan.plan_coach_ideas') ?></summary>
                                    <ul class="leader-os-ai-list mb-0">
                                        <?php foreach($ai_plan_admin['latest_plan']['coach_ideas'] as $idea): ?>
                                            <li><?= htmlspecialchars((string) $idea, ENT_QUOTES, 'UTF-8') ?></li>
                                        <?php endforeach ?>
                                    </ul>
                                </details>
                            <?php endif ?>
                        <?php else: ?>
                            <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.leader.ai_plan_no_plan') ?></div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Custom code: FC-2026-03-31 -->

    <div class="card leader-os-detail-shell mb-4">
        <div class="card-body">
            <div class="row">
                <?php foreach(['7d', '30d', '90d'] as $period_key): ?>
                    <?php $period_payload = $detail['periods'][$period_key]; ?>
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                        <div class="leader-os-detail-panel h-100">
                            <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.period_' . $period_key) ?></div>
                            <div class="h4 mb-2"><?= nr($period_payload['forever_shop_clicks_period']) ?> <?= l('admin_leader_operating_system.leader.snapshot_shop_clicks') ?></div>
                            <div class="text-muted small mb-2"><?= l('admin_leader_operating_system.leader.snapshot_registrations') ?>: <?= nr($period_payload['forever_registration_clicks_period']) ?></div>
                            <div class="text-muted small mb-2"><?= l('admin_leader_operating_system.leader.snapshot_source') ?>: <?= $period_payload['top_source_label'] ?></div>
                            <div class="text-muted small"><?= l('admin_leader_operating_system.leader.snapshot_country') ?>: <?= $period_payload['top_country_label'] ?></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>

    <?php $breakdown_map = [
        'top_countries' => l('admin_leader_operating_system.leader.breakdown_countries'),
        'top_cities' => 'Top gradovi',
        'top_sources' => l('admin_leader_operating_system.leader.breakdown_sources'),
        'top_languages' => 'Jezici',
        'top_devices' => l('admin_leader_operating_system.leader.breakdown_devices'),
        'top_browsers' => l('admin_leader_operating_system.leader.breakdown_browsers'),
    ]; ?>

    <div class="row">
        <?php foreach($breakdown_map as $breakdown_key => $breakdown_title): ?>
            <div class="col-12 col-xl-3 mb-3">
                <div class="leader-os-detail-panel h-100 leader-os-breakdown-trigger" role="button" tabindex="0" data-toggle="modal" data-target="#leader-os-breakdown-modal" data-breakdown-key="<?= $breakdown_key ?>" data-breakdown-title="<?= input_clean($breakdown_title) ?>">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 mb-0"><?= $breakdown_title ?></h3>
                        <span class="leader-os-breakdown-hint"><?= l('admin_leader_operating_system.leader.breakdown_open') ?></span>
                    </div>
                    <div class="leader-os-detail-list">
                        <?php if(empty($selected[$breakdown_key])): ?>
                            <div class="text-muted small"><?= l('admin_leader_operating_system.leader.no_breakdown_data') ?></div>
                        <?php else: ?>
                            <?php foreach($selected[$breakdown_key] as $item): ?>
                                <div class="leader-os-detail-list-item">
                                    <div>
                                        <div><?= $item['label'] ?></div>
                                        <div class="text-muted small"><?= nr($item['share']) ?>%</div>
                                    </div>
                                    <strong><?= nr($item['total']) ?></strong>
                                </div>
                            <?php endforeach ?>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <div class="modal fade leader-os-modal" id="leader-os-breakdown-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="leader-os-breakdown-modal-title"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="text-muted small mb-3"><?= l('admin_leader_operating_system.leader.breakdown_modal_text') ?></div>
                    <div id="leader-os-breakdown-modal-body" class="leader-os-modal-list"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade leader-os-modal" id="leader-os-opportunity-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= l('admin_leader_operating_system.leader.opportunity_modal_title') ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="text-muted small mb-3" id="leader-os-opportunity-modal-intro"></div>
                    <div id="leader-os-opportunity-modal-body" class="leader-os-modal-list"></div>
                </div>
            </div>
        </div>
    </div>

    <?php require THEME_PATH . 'views/partials/js_chart_defaults.php' ?>

    <?php ob_start() ?>
    <script>
    'use strict';

    const leaderOsSelectedChart = <?= json_encode($selected['chart']) ?>;
    const leaderOsCountrySignalMatrix = <?= json_encode($country_matrix_periods) ?>;
    const leaderOsInitialCountryPeriod = <?= json_encode($initial_country_period) ?>;
    const leaderOsBreakdowns = <?= json_encode([
        'top_countries' => $selected['top_countries'],
        'top_cities' => $selected['top_cities'],
        'top_sources' => $selected['top_sources'],
        'top_languages' => $selected['top_languages'],
        'top_devices' => $selected['top_devices'],
        'top_browsers' => $selected['top_browsers'],
    ]) ?>;
    const leaderOsOpportunityActions = <?= json_encode($opportunity_actions ?? ['intro' => '', 'items' => []]) ?>;
    const leaderOsOutreachForm = document.querySelector('.leader-os-outreach-form');
    const leaderOsCountryTableBody = document.getElementById('leader-os-country-table-body');
    const leaderOsCountryTableFoot = document.getElementById('leader-os-country-table-foot');
    const leaderOsNumberFormatter = new Intl.NumberFormat('hr-HR');

    const escapeLeaderOsHtml = value => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const trendChartCanvas = document.getElementById('leader-os-detail-trend-chart');
    if(typeof Chart !== 'undefined' && trendChartCanvas) {
        new Chart(trendChartCanvas, {
            type: 'line',
            data: {
                labels: leaderOsSelectedChart.labels,
                datasets: [
                    {
                        label: <?= json_encode(l('admin_leader_operating_system.leader.chart_metric_app_visits')) ?>,
                        data: leaderOsSelectedChart.app_visits,
                        borderColor: '#94a3ff',
                        backgroundColor: 'rgba(148, 163, 255, 0.16)',
                        tension: 0.35,
                        fill: true,
                    },
                    {
                        label: <?= json_encode(l('admin_leader_operating_system.leader.chart_metric_app_shop_clicks')) ?>,
                        data: leaderOsSelectedChart.app_shop_clicks,
                        borderColor: '#7cc8ff',
                        backgroundColor: 'rgba(124, 200, 255, 0.08)',
                        tension: 0.35,
                        fill: false,
                    },
                    {
                        label: <?= json_encode(l('admin_leader_operating_system.leader.chart_metric_blog_clicks')) ?>,
                        data: leaderOsSelectedChart.blog_clicks,
                        borderColor: '#f9c74f',
                        backgroundColor: 'rgba(249, 199, 79, 0.08)',
                        tension: 0.35,
                        fill: false,
                    },
                    {
                        label: <?= json_encode(l('admin_leader_operating_system.leader.chart_metric_funnel_registrations')) ?>,
                        data: leaderOsSelectedChart.funnel_registrations,
                        borderColor: '#86efac',
                        backgroundColor: 'rgba(134, 239, 172, 0.1)',
                        tension: 0.35,
                        fill: false,
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {mode: 'index', intersect: false},
                plugins: {
                    legend: {labels: {color: '#ecf3ff'}},
                },
                scales: {
                    x: {
                        ticks: {color: 'rgba(191, 211, 238, 0.72)'},
                        grid: {color: 'rgba(148, 163, 184, 0.08)'}
                    },
                    y: {
                        ticks: {color: 'rgba(191, 211, 238, 0.72)', precision: 0},
                        grid: {color: 'rgba(148, 163, 184, 0.08)'}
                    }
                }
            }
        });
    }

    const renderLeaderOsCountrySignalTable = periodKey => {
        if(!leaderOsCountryTableBody) {
            return;
        }

        const payload = leaderOsCountrySignalMatrix[periodKey] || leaderOsCountrySignalMatrix[leaderOsInitialCountryPeriod] || {rows: []};
        const rows = Array.isArray(payload.rows) ? payload.rows : [];
        const totals = payload.totals && typeof payload.totals === 'object' ? payload.totals : {};

        if(!rows.length) {
            leaderOsCountryTableBody.innerHTML = `
                <tr>
                    <td colspan="5">
                        <div class="leader-os-country-table-empty"><?= addslashes(l('admin_leader_operating_system.leader.country_table.empty')) ?></div>
                    </td>
                </tr>
            `;
        } else {
            leaderOsCountryTableBody.innerHTML = rows.map(row => `
                <tr>
                    <td>
                        <div class="leader-os-country-name">
                            <strong>${escapeLeaderOsHtml(row.country_name || '-')}</strong>
                            <span class="leader-os-country-code">${escapeLeaderOsHtml(row.country_code || '')}</span>
                        </div>
                    </td>
                    <td class="text-right">${leaderOsNumberFormatter.format(Number(row.app_visits || 0))}</td>
                    <td class="text-right">${leaderOsNumberFormatter.format(Number(row.app_shop_clicks || 0))}</td>
                    <td class="text-right">${leaderOsNumberFormatter.format(Number(row.blog_clicks || 0))}</td>
                    <td class="text-right">${leaderOsNumberFormatter.format(Number(row.funnel_registrations || 0))}</td>
                </tr>
            `).join('');
        }

        if(leaderOsCountryTableFoot) {
            leaderOsCountryTableFoot.innerHTML = `
                <tr>
                    <th><?= addslashes(l('admin_leader_operating_system.leader.country_table.total')) ?></th>
                    <td class="text-right">${leaderOsNumberFormatter.format(Number(totals.app_visits || 0))}</td>
                    <td class="text-right">${leaderOsNumberFormatter.format(Number(totals.app_shop_clicks || 0))}</td>
                    <td class="text-right">${leaderOsNumberFormatter.format(Number(totals.blog_clicks || 0))}</td>
                    <td class="text-right">${leaderOsNumberFormatter.format(Number(totals.funnel_registrations || 0))}</td>
                </tr>
            `;
        }
    };

    document.querySelectorAll('[data-country-period]').forEach(button => {
        button.addEventListener('click', () => {
            const periodKey = button.getAttribute('data-country-period');

            document.querySelectorAll('[data-country-period]').forEach(item => {
                item.classList.toggle('active', item === button);
            });

            renderLeaderOsCountrySignalTable(periodKey);
        });
    });

    if(leaderOsCountryTableBody) {
        renderLeaderOsCountrySignalTable(leaderOsInitialCountryPeriod);
    }

    $('#leader-os-breakdown-modal').on('show.bs.modal', event => {
        const trigger = $(event.relatedTarget);
        const breakdownKey = trigger.data('breakdown-key');
        const breakdownTitle = trigger.data('breakdown-title');
        const items = leaderOsBreakdowns[breakdownKey] || [];

        $('#leader-os-breakdown-modal-title').text(breakdownTitle);

        let html = '';
        if(!items.length) {
            html = `<div class="text-muted small"><?= addslashes(l('admin_leader_operating_system.leader.no_breakdown_data')) ?></div>`;
        } else {
            html = items.map(item => `
                <div class="leader-os-modal-item">
                    <div>
                        <div>${item.label}</div>
                        <div class="text-muted small">${item.share}%</div>
                    </div>
                    <strong>${item.total}</strong>
                </div>
            `).join('');
        }

        $('#leader-os-breakdown-modal-body').html(html);
    });

    $('#leader-os-opportunity-modal').on('show.bs.modal', () => {
        $('#leader-os-opportunity-modal-intro').text(leaderOsOpportunityActions.intro || '');

        const items = leaderOsOpportunityActions.items || [];
        let html = '';

        if(!items.length) {
            html = `<div class="text-muted small"><?= addslashes(l('admin_leader_operating_system.leader.opportunity_modal_empty')) ?></div>`;
        } else {
            html = items.map(item => `
                <div class="leader-os-modal-item">
                    <div>
                        <div class="font-weight-bold mb-1">${item.label || ''}</div>
                        <div class="text-muted small mb-2">${item.text || ''}</div>
                        <div class="text-white small">${(item.actions || []).map(action => `• ${action}`).join('<br>')}</div>
                    </div>
                </div>
            `).join('');
        }

        $('#leader-os-opportunity-modal-body').html(html);
    });

    document.querySelectorAll('.leader-os-load-report').forEach(button => {
        button.addEventListener('click', () => {
            if(!leaderOsOutreachForm) {
                return;
            }

            const emailInput = leaderOsOutreachForm.querySelector('input[name="outreach_email"]');
            const subjectInput = leaderOsOutreachForm.querySelector('input[name="outreach_subject"]');
            const bodyInput = leaderOsOutreachForm.querySelector('textarea[name="outreach_body"]');

            if(emailInput) {
                emailInput.value = button.dataset.email || emailInput.value;
            }

            if(subjectInput) {
                subjectInput.value = button.dataset.subject || '';
            }

            if(bodyInput) {
                bodyInput.value = button.dataset.body || '';
                bodyInput.focus();
            }
        });
    });

    document.querySelectorAll('.leader-os-scroll-link').forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.dataset.scrollTarget || '';
            const targetElement = targetId ? document.getElementById(targetId) : null;

            if(!targetElement) {
                return;
            }

            targetElement.scrollIntoView({behavior: 'smooth', block: 'start'});
        });
    });

    if (window.jQuery && typeof window.jQuery.fn.tooltip === 'function') {
        window.jQuery('[data-toggle="tooltip"]').tooltip({container: 'body'});
    }
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>
<!-- /Custom code: FC-2026-03-31 -->
</div>
