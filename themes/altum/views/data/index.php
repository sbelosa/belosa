<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <style>
        .fcc-contacts-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            position: relative;
        }

        .fcc-contacts-shell::before {
            content: '';
            position: absolute;
            inset: 4.8rem 0 auto;
            height: 34rem;
            border-radius: 34px;
            background:
                radial-gradient(circle at 14% 16%, rgba(70, 220, 214, 0.06) 0%, rgba(70, 220, 214, 0) 40%),
                radial-gradient(circle at 82% 10%, rgba(61, 118, 255, 0.06) 0%, rgba(61, 118, 255, 0) 34%),
                radial-gradient(circle at 46% 0%, rgba(34, 185, 129, 0.03) 0%, rgba(34, 185, 129, 0) 26%),
                linear-gradient(180deg, rgba(12, 19, 33, 0.60) 0%, rgba(16, 16, 18, 0) 100%);
            pointer-events: none;
            z-index: 0;
        }

        .fcc-contacts-shell > * {
            position: relative;
            z-index: 1;
        }

        .fcc-contacts-header {
            background:
                radial-gradient(circle at top left, rgba(71, 224, 213, 0.05) 0%, rgba(71, 224, 213, 0) 32%),
                radial-gradient(circle at top right, rgba(69, 120, 255, 0.05) 0%, rgba(69, 120, 255, 0) 30%),
                linear-gradient(180deg, rgba(16, 26, 45, 0.97) 0%, rgba(11, 16, 28, 0.99) 100%);
            border: 1px solid rgba(101, 220, 232, 0.08);
            border-radius: 22px;
            padding: 1.35rem 1.4rem;
            box-shadow: 0 24px 50px rgba(4, 10, 24, 0.30);
        }

        .fcc-contacts-heading {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }

        .fcc-contacts-heading-icon {
            width: 2.85rem;
            height: 2.85rem;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(76, 226, 214, 0.18) 0%, rgba(48, 105, 235, 0.26) 100%);
            color: #9ff4ec;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.10), 0 16px 30px rgba(9, 20, 46, 0.28);
            flex-shrink: 0;
        }

        .fcc-contacts-heading-copy h1 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #f4f8ff;
        }

        .fcc-contacts-heading-copy p {
            margin: 0.35rem 0 0;
            color: #b7c8dd;
            font-size: 0.92rem;
        }

        .fcc-contacts-toolbar {
            gap: 0.75rem;
        }

        .fcc-contacts-action-btn {
            border-radius: 14px;
            min-height: 2.75rem;
            font-weight: 600;
            box-shadow: none !important;
        }

        .fcc-contacts-action-btn.btn-light,
        .fcc-contacts-action-btn.btn-dark {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.06);
            color: #d5e1e2;
        }

        .fcc-contacts-action-btn.btn-light:hover,
        .fcc-contacts-action-btn.btn-dark:hover {
            background: rgba(127, 227, 217, 0.1);
            border-color: rgba(127, 227, 217, 0.16);
            color: #f5fbfb;
        }

        .fcc-contacts-summary {
            margin: 0;
        }

        .fcc-contacts-summary-card {
            background:
                radial-gradient(circle at top left, rgba(72, 220, 214, 0.05) 0%, rgba(72, 220, 214, 0) 26%),
                linear-gradient(180deg, rgba(16, 23, 39, 0.98) 0%, rgba(10, 14, 24, 0.99) 100%);
            border: 1px solid rgba(90, 201, 230, 0.07);
            border-radius: 20px;
            padding: 1rem 1.1rem;
            height: 100%;
            box-shadow: 0 20px 40px rgba(4, 10, 24, 0.22);
        }

        .fcc-contacts-summary-label {
            color: #8fb5d0;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .fcc-contacts-summary-value {
            margin-top: 0.45rem;
            color: #f4f8ff;
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1;
        }

        .fcc-contacts-summary-note {
            margin-top: 0.45rem;
            color: #9bb3c7;
            font-size: 0.88rem;
        }

        .fcc-contacts-table-card,
        .fcc-contacts-empty {
            background:
                radial-gradient(circle at top center, rgba(72, 220, 214, 0.05) 0%, rgba(72, 220, 214, 0) 24%),
                linear-gradient(180deg, rgba(16, 23, 39, 0.99) 0%, rgba(10, 14, 24, 0.99) 100%);
            border: 1px solid rgba(90, 201, 230, 0.07);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 24px 48px rgba(4, 10, 24, 0.26);
        }

        .fcc-contacts-table-card .table-custom-container {
            border: 0;
            background: transparent;
        }

        .fcc-contacts-table-card .table-custom {
            margin-bottom: 0;
        }

        .fcc-contacts-table-card .table-custom thead th {
            background: rgba(80, 150, 255, 0.02);
            color: #e7f2ff;
            border-bottom-color: rgba(95, 205, 231, 0.08);
        }

        .fcc-contacts-table-card .table-custom td {
            background: transparent;
            color: #dce9f7;
            border-top-color: rgba(95, 205, 231, 0.05);
            vertical-align: top;
        }

        .fcc-contacts-table-card .table-custom tbody tr:hover {
            background: rgba(73, 200, 218, 0.02);
        }

        .fcc-contact-row-highlight td {
            background:
                radial-gradient(circle at 12% 14%, rgba(63, 215, 199, 0.08) 0%, rgba(63, 215, 199, 0) 34%),
                radial-gradient(circle at 88% 10%, rgba(84, 124, 255, 0.08) 0%, rgba(84, 124, 255, 0) 32%),
                radial-gradient(circle at 76% 0%, rgba(226, 188, 116, 0.06) 0%, rgba(226, 188, 116, 0) 24%),
                linear-gradient(180deg, rgba(18, 27, 45, 0.98) 0%, rgba(11, 16, 28, 0.995) 100%) !important;
            border-top-color: rgba(115, 176, 255, 0.12) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03), inset 0 -1px 0 rgba(63, 215, 199, 0.05);
        }

        .fcc-contact-row-highlight td:first-child {
            box-shadow: inset 3px 0 0 rgba(96, 235, 220, 0.36), inset 0 1px 0 rgba(255, 255, 255, 0.03), inset 0 -1px 0 rgba(63, 215, 199, 0.05);
        }

        .fcc-contact-row-highlight:hover td {
            background:
                radial-gradient(circle at 12% 14%, rgba(63, 215, 199, 0.11) 0%, rgba(63, 215, 199, 0) 34%),
                radial-gradient(circle at 88% 10%, rgba(84, 124, 255, 0.10) 0%, rgba(84, 124, 255, 0) 32%),
                radial-gradient(circle at 76% 0%, rgba(226, 188, 116, 0.08) 0%, rgba(226, 188, 116, 0) 24%),
                linear-gradient(180deg, rgba(20, 30, 49, 0.99) 0%, rgba(11, 17, 30, 0.998) 100%) !important;
        }

        .fcc-contact-main {
            display: flex;
            gap: 0.8rem;
            min-width: 16rem;
        }

        .fcc-contact-avatar {
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(63, 215, 199, 0.28) 0%, rgba(109, 233, 221, 0.16) 100%);
            color: #bff6ef;
            font-weight: 700;
            flex-shrink: 0;
        }

        .fcc-contact-name {
            color: #f5fbfb;
            font-weight: 700;
            font-size: 0.93rem;
            line-height: 1.2;
        }

        .fcc-contact-line {
            color: #98acad;
            font-size: 0.82rem;
            margin-top: 0.18rem;
            word-break: break-word;
        }

        .fcc-contact-line strong {
            color: #d6e5e4;
            font-weight: 600;
        }

        .fcc-contact-source {
            min-width: 14rem;
        }

        .fcc-contact-source.is-emphasized {
            position: relative;
            padding: 0.9rem 1rem;
            border-radius: 20px;
            border: 1px solid rgba(118, 180, 255, 0.14);
            background:
                radial-gradient(circle at top right, rgba(84, 124, 255, 0.10) 0%, rgba(84, 124, 255, 0) 42%),
                radial-gradient(circle at 12% 0%, rgba(226, 188, 116, 0.05) 0%, rgba(226, 188, 116, 0) 26%),
                linear-gradient(180deg, rgba(17, 24, 40, 0.9) 0%, rgba(12, 17, 30, 0.96) 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04), 0 18px 34px rgba(3, 10, 23, 0.16);
        }

        .fcc-contact-source.is-emphasized::before {
            content: '';
            position: absolute;
            inset: 0 0 auto;
            height: 2px;
            border-radius: 20px 20px 0 0;
            background: linear-gradient(90deg, rgba(95, 236, 221, 0.9) 0%, rgba(104, 158, 255, 0.72) 56%, rgba(228, 188, 118, 0.82) 100%);
            opacity: 0.95;
        }

        .fcc-contact-source-title {
            color: #edf9f7;
            font-weight: 600;
            margin-bottom: 0.35rem;
            font-size: 0.9rem;
        }

        .fcc-contact-meta {
            color: #8ea4a6;
            font-size: 0.8rem;
            margin-top: 0.18rem;
        }

        .fcc-contact-status-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin-top: .45rem;
            border-radius: 999px;
            padding: .24rem .58rem;
            font-size: .72rem;
            font-weight: 700;
        }

        .fcc-contact-status-badge.is-ready {
            background: rgba(46, 211, 198, 0.14);
            color: #9ef1e7;
            border: 1px solid rgba(46, 211, 198, 0.2);
        }

        .fcc-contact-status-badge.is-review {
            background: rgba(245, 158, 11, 0.12);
            color: #fcd58a;
            border: 1px solid rgba(245, 158, 11, 0.22);
        }

        .fcc-contact-source.is-emphasized .fcc-contact-status-badge.is-review {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.16) 0%, rgba(180, 83, 9, 0.16) 100%);
            color: #ffe2a6;
            border-color: rgba(245, 158, 11, 0.3);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .fcc-contact-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.45rem;
        }

        .fcc-contact-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(255, 255, 255, 0.08);
            color: #d6e5e4;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 999px;
            padding: 0.28rem 0.65rem;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .fcc-contact-actions {
            min-width: 10rem;
        }

        .fcc-contact-actions.is-emphasized {
            padding: 0.9rem 0.95rem;
            border-radius: 18px;
            border: 1px solid rgba(118, 180, 255, 0.1);
            background: linear-gradient(180deg, rgba(18, 25, 41, 0.76) 0%, rgba(12, 17, 30, 0.88) 100%);
        }

        .fcc-contact-primary-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            gap: 0.4rem;
            border-radius: 12px;
            padding: 0.52rem 0.7rem;
            font-weight: 700;
            font-size: 0.78rem;
        }

        .fcc-contact-primary-action.is-whatsapp {
            background: rgba(37, 211, 102, 0.12);
            color: #7ff0a9;
            border: 1px solid rgba(37, 211, 102, 0.24);
        }

        .fcc-contact-primary-action.is-viber {
            background: rgba(115, 96, 242, 0.14);
            color: #c5bbff;
            border: 1px solid rgba(115, 96, 242, 0.28);
        }

        .fcc-contact-primary-action.is-sms {
            background: rgba(73, 227, 207, 0.12);
            color: #9ef1e7;
            border: 1px solid rgba(73, 227, 207, 0.22);
        }

        .fcc-contact-primary-action.is-call,
        .fcc-contact-primary-action.is-email {
            background: rgba(255, 255, 255, 0.07);
            color: #dce7e8;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .fcc-contact-primary-action:hover {
            text-decoration: none;
        }

        .fcc-contact-primary-action.is-whatsapp:hover {
            color: #d9ffe7;
            background: rgba(37, 211, 102, 0.18);
        }

        .fcc-contact-primary-action.is-viber:hover {
            color: #ede9ff;
            background: rgba(115, 96, 242, 0.2);
        }

        .fcc-contact-primary-action.is-sms:hover {
            color: #ddfffb;
            background: rgba(73, 227, 207, 0.18);
        }

        .fcc-contact-primary-action.is-call:hover,
        .fcc-contact-primary-action.is-email:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
        }

        .fcc-contact-secondary-actions {
            margin-top: 0.45rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.4rem;
        }

        .fcc-contact-secondary-action {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border-radius: 999px;
            padding: 0.28rem 0.52rem;
            font-size: 0.72rem;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            color: #cfe0e1;
        }

        .fcc-contact-notes {
            margin-top: 0.45rem;
            display: flex;
            flex-direction: column;
            gap: 0.28rem;
        }

        .fcc-contact-note {
            color: #8ea4a6;
            font-size: 0.78rem;
            line-height: 1.4;
            word-break: break-word;
        }

        .fcc-contact-secondary-action:hover {
            text-decoration: none;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.09);
        }

        .fcc-contact-date {
            color: #8ea4a6;
            font-size: 0.76rem;
            margin-top: 0.55rem;
            text-align: center;
            line-height: 1.4;
        }

        .fcc-contact-actions-menu {
            margin-top: 0.35rem;
            display: flex;
            justify-content: center;
        }

        .fcc-contact-actions-footer {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            align-items: stretch;
        }

        .fcc-contact-mobile-label {
            display: none;
        }

        .fcc-contacts-mobile-list {
            display: none;
        }

        .fcc-contact-mobile-card {
            background:
                radial-gradient(circle at 10% 0%, rgba(71, 224, 213, 0.05) 0%, rgba(71, 224, 213, 0) 28%),
                linear-gradient(180deg, rgba(15, 22, 37, 0.98) 0%, rgba(10, 14, 24, 0.99) 100%);
            border: 1px solid rgba(90, 201, 230, 0.08);
            border-radius: 20px;
            padding: 1rem;
            box-shadow: 0 14px 30px rgba(4, 10, 24, 0.18);
        }

        .fcc-contact-mobile-card.is-highlighted {
            border-color: rgba(245, 158, 11, 0.18);
            box-shadow: inset 3px 0 0 rgba(245, 158, 11, 0.24), 0 14px 30px rgba(4, 10, 24, 0.18);
        }

        .fcc-contact-mobile-head {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
        }

        .fcc-contact-mobile-head-copy {
            flex: 1;
            min-width: 0;
        }

        .fcc-contact-mobile-status {
            margin-top: 0.35rem;
        }

        .fcc-contact-mobile-section {
            margin-top: 0.9rem;
            padding-top: 0.85rem;
            border-top: 1px solid rgba(95, 205, 231, 0.06);
        }

        .fcc-contact-mobile-section-title {
            margin-bottom: 0.5rem;
            color: #8fb5d0;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .fcc-contacts-table-card .table-custom th,
        .fcc-contacts-table-card .table-custom td {
            padding-top: 0.95rem;
            padding-bottom: 0.95rem;
        }

        @media (max-width: 1200px) {
            .fcc-contact-main,
            .fcc-contact-source,
            .fcc-contact-actions {
                min-width: unset;
            }
        }

        @media (max-width: 767.98px) {
            .fcc-contacts-shell {
                gap: 1rem;
            }

            .fcc-contacts-header {
                padding: 1.1rem 1rem;
                border-radius: 20px;
            }

            .fcc-contacts-heading {
                align-items: flex-start;
            }

            .fcc-contacts-heading-icon {
                width: 2.55rem;
                height: 2.55rem;
                border-radius: 14px;
            }

            .fcc-contacts-heading-copy h1 {
                font-size: 1.05rem;
            }

            .fcc-contacts-heading-copy p {
                font-size: 0.86rem;
                line-height: 1.45;
            }

            .fcc-contacts-toolbar {
                width: 100%;
            }

            .fcc-contacts-toolbar > div {
                flex: 1 1 calc(33.333% - 0.5rem);
                min-width: 0;
            }

            .fcc-contacts-toolbar .btn,
            .fcc-contacts-toolbar .dropdown,
            .fcc-contacts-toolbar .btn-group {
                width: 100%;
            }

            .fcc-contacts-action-btn {
                min-height: 2.6rem;
                border-radius: 12px;
            }

            .fcc-contacts-summary-card {
                border-radius: 18px;
                padding: 0.9rem 1rem;
            }

            .fcc-contacts-summary-value {
                font-size: 1.35rem;
            }

            .fcc-contacts-mobile-list {
                display: flex;
                flex-direction: column;
                gap: 0.9rem;
            }

            .fcc-contacts-table-card {
                display: none;
            }

            .fcc-contact-mobile-label {
                display: none;
            }

            .fcc-contact-main {
                min-width: 0;
                gap: 0.75rem;
                align-items: flex-start;
            }

            .fcc-contact-avatar {
                width: 2.65rem;
                height: 2.65rem;
                border-radius: 15px;
            }

            .fcc-contact-name {
                font-size: 0.98rem;
            }

            .fcc-contact-line {
                font-size: 0.85rem;
            }

            .fcc-contact-source,
            .fcc-contact-actions {
                min-width: 0;
            }

            .fcc-contact-source.is-emphasized,
            .fcc-contact-actions.is-emphasized {
                padding: 0;
                border: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }

            .fcc-contact-source.is-emphasized::before {
                display: none;
            }

            .fcc-contact-source-title {
                font-size: 1rem;
                margin-bottom: 0.3rem;
            }

            .fcc-contact-meta,
            .fcc-contact-note {
                font-size: 0.84rem;
            }

            .fcc-contact-tags {
                gap: 0.45rem;
            }

            .fcc-contact-tag {
                font-size: 0.74rem;
            }

            .fcc-contact-primary-action {
                min-height: 2.8rem;
                border-radius: 14px;
                font-size: 0.88rem;
            }

            .fcc-contact-secondary-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.45rem;
            }

            .fcc-contact-secondary-action {
                justify-content: center;
                padding: 0.45rem 0.35rem;
                text-align: center;
                font-size: 0.72rem;
                min-height: 2.2rem;
            }

            .fcc-contact-actions-footer {
                margin-top: 0.65rem;
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .fcc-contact-date {
                margin-top: 0;
                text-align: left;
                font-size: 0.75rem;
            }

            .fcc-contact-actions-menu {
                margin-top: 0;
                justify-content: flex-end;
                flex-shrink: 0;
            }

            .fcc-contacts-pagination {
                margin-top: 0.65rem;
                padding: 0;
            }
        }

        .fcc-contacts-pagination {
            margin-top: 1rem;
            padding: 0 0.35rem;
        }
    </style>

    <div class="fcc-contacts-shell">
        <div class="fcc-contacts-header">
            <div class="row align-items-center">
                <div class="col-12 col-lg mb-4 mb-lg-0 text-truncate">
                    <div class="fcc-contacts-heading">
                        <div class="fcc-contacts-heading-icon">
                            <i class="fas fa-fw fa-address-book"></i>
                        </div>

                        <div class="fcc-contacts-heading-copy">
                            <h1 class="text-truncate">
                                <?= l('data.header') ?>
                                <span class="ml-1" data-toggle="tooltip" title="<?= l('data.subheader') ?>">
                                    <i class="fas fa-fw fa-info-circle text-muted"></i>
                                </span>
                            </h1>
                            <p>Svi kontakti s vaših FCC aplikacija na jednom mjestu, spremni za brzu obradu i WhatsApp kontakt.</p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-auto d-flex flex-wrap fcc-contacts-toolbar d-print-none">
                    <div>
                        <div class="dropdown">
                            <button type="button" class="btn btn-light dropdown-toggle-simple fcc-contacts-action-btn <?= !empty($data->data) ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip title="<?= l('global.export') ?>" data-tooltip-hide-on-click>
                                <i class="fas fa-fw fa-sm fa-download"></i>
                            </button>

                            <div class="dropdown-menu dropdown-menu-right d-print-none">
                                <a href="<?= url('data?' . $data->filters->get_get() . '&export=csv')  ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->csv ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->csv ? null : get_plan_feature_disabled_info() ?>>
                                    <i class="fas fa-fw fa-sm fa-file-csv mr-2"></i> <?= sprintf(l('global.export_to'), 'CSV') ?>
                                </a>
                                <a href="<?= url('data?' . $data->filters->get_get() . '&export=json') ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->json ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->json ? null : get_plan_feature_disabled_info() ?>>
                                    <i class="fas fa-fw fa-sm fa-file-code mr-2"></i> <?= sprintf(l('global.export_to'), 'JSON') ?>
                                </a>
                                <a href="#" class="dropdown-item <?= $this->user->plan_settings->export->pdf ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->pdf ? 'onclick="event.preventDefault(); window.print();"' : get_plan_feature_disabled_info() ?>>
                                    <i class="fas fa-fw fa-sm fa-file-pdf mr-2"></i> <?= sprintf(l('global.export_to'), 'PDF') ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="dropdown">
                            <button type="button" class="btn <?= $data->filters->has_applied_filters ? 'btn-dark' : 'btn-light' ?> filters-button dropdown-toggle-simple fcc-contacts-action-btn <?= !empty($data->data) || $data->filters->has_applied_filters ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip data-html="true" title="<?= l('global.filters.tooltip') ?>" data-tooltip-hide-on-click>
                                <i class="fas fa-fw fa-sm fa-filter"></i>
                            </button>

                            <div class="dropdown-menu dropdown-menu-right filters-dropdown">
                                <div class="dropdown-header d-flex justify-content-between">
                                    <span class="h6 m-0"><?= l('global.filters.header') ?></span>

                                    <?php if($data->filters->has_applied_filters): ?>
                                        <a href="<?= url(\Altum\Router::$original_request) ?>" class="text-muted"><?= l('global.filters.reset') ?></a>
                                    <?php endif ?>
                                </div>

                                <div class="dropdown-divider"></div>

                                <form action="" method="get" role="form">
                                    <div class="form-group px-4">
                                        <label for="type" class="small"><?= l('global.type') ?></label>
                                        <select name="type" id="type" class="custom-select custom-select-sm">
                                            <option value=""><?= l('global.all') ?></option>
                                            <?php foreach(['email_collector', 'phone_collector', 'contact_collector', 'lead_funnel', 'appointment_calendar'] as $value): ?>
                                                <option value="<?= $value ?>" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == $value ? 'selected="selected"' : null ?>><?= l('link.biolink.blocks.' . $value) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>

                                    <div class="form-group px-4">
                                        <label for="preferred_contact_channel" class="small">Preferirani kanal</label>
                                        <select name="preferred_contact_channel" id="preferred_contact_channel" class="custom-select custom-select-sm">
                                            <option value="">Svi kanali</option>
                                            <?php foreach($data->contact_channel_options as $channel_key => $channel_label): ?>
                                                <option value="<?= $channel_key ?>" <?= ($_GET['preferred_contact_channel'] ?? null) === $channel_key ? 'selected="selected"' : null ?>><?= $channel_label ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>

                                    <div class="form-group px-4">
                                        <label for="filters_order_by" class="small"><?= l('global.filters.order_by') ?></label>
                                        <select name="order_by" id="filters_order_by" class="custom-select custom-select-sm">
                                            <option value="datum_id" <?= $data->filters->order_by == 'datum_id' ? 'selected="selected"' : null ?>><?= l('global.id') ?></option>
                                            <option value="datetime" <?= $data->filters->order_by == 'datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_datetime') ?></option>
                                        </select>
                                    </div>

                                    <div class="form-group px-4">
                                        <label for="filters_order_type" class="small"><?= l('global.filters.order_type') ?></label>
                                        <select name="order_type" id="filters_order_type" class="custom-select custom-select-sm">
                                            <option value="ASC" <?= $data->filters->order_type == 'ASC' ? 'selected="selected"' : null ?>><?= l('global.filters.order_type_asc') ?></option>
                                            <option value="DESC" <?= $data->filters->order_type == 'DESC' ? 'selected="selected"' : null ?>><?= l('global.filters.order_type_desc') ?></option>
                                        </select>
                                    </div>

                                    <div class="form-group px-4">
                                        <label for="filters_results_per_page" class="small"><?= l('global.filters.results_per_page') ?></label>
                                        <select name="results_per_page" id="filters_results_per_page" class="custom-select custom-select-sm">
                                            <?php foreach($data->filters->allowed_results_per_page as $key): ?>
                                                <option value="<?= $key ?>" <?= $data->filters->results_per_page == $key ? 'selected="selected"' : null ?>><?= $key ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>

                                    <div class="form-group px-4 mt-4">
                                        <button type="submit" name="submit" class="btn btn-sm btn-primary btn-block"><?= l('global.submit') ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button id="bulk_enable" type="button" class="btn btn-light fcc-contacts-action-btn" data-toggle="tooltip" title="<?= l('global.bulk_actions') ?>"><i class="fas fa-fw fa-sm fa-list"></i></button>

                        <div id="bulk_group" class="btn-group d-none" role="group">
                            <div class="btn-group dropdown" role="group">
                                <button id="bulk_actions" type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false">
                                    <?= l('global.bulk_actions') ?> <span id="bulk_counter" class="d-none"></span>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="bulk_actions">
                                    <a href="#" class="dropdown-item" data-toggle="modal" data-target="#bulk_delete_modal"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>
                                </div>
                            </div>

                            <button id="bulk_disable" type="button" class="btn btn-secondary" data-toggle="tooltip" title="<?= l('global.close') ?>"><i class="fas fa-fw fa-times"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row fcc-contacts-summary">
            <div class="col-12 col-md-6 col-xl-3 mb-3">
                <div class="fcc-contacts-summary-card">
                    <div class="fcc-contacts-summary-label">Ukupno kontakata</div>
                    <div class="fcc-contacts-summary-value"><?= nr($data->summary['total']) ?></div>
                    <div class="fcc-contacts-summary-note">Svi spremljeni kontakti iz obrazaca i funnel-a.</div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3 mb-3">
                <div class="fcc-contacts-summary-card">
                    <div class="fcc-contacts-summary-label">S telefonom</div>
                    <div class="fcc-contacts-summary-value"><?= nr($data->summary['with_phone']) ?></div>
                    <div class="fcc-contacts-summary-note">Kontakti koje možeš odmah nazvati ili otvoriti u WhatsAppu.</div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3 mb-3">
                <div class="fcc-contacts-summary-card">
                    <div class="fcc-contacts-summary-label">S email adresom</div>
                    <div class="fcc-contacts-summary-value"><?= nr($data->summary['with_email']) ?></div>
                    <div class="fcc-contacts-summary-note">Kontakti spremni za follow-up mail ili newsletter segmentaciju.</div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3 mb-3">
                <div class="fcc-contacts-summary-card">
                    <div class="fcc-contacts-summary-label">Spremni za kontakt</div>
                    <div class="fcc-contacts-summary-value"><?= nr($data->summary['with_whatsapp']) ?></div>
                    <div class="fcc-contacts-summary-note">Zapisi s valjanim kanalom za brzu poruku, poziv ili email.</div>
                </div>
            </div>

        </div>

        <?php if (!empty($data->data)): ?>
            <form id="table" action="<?= SITE_URL . 'data/bulk' ?>" method="post" role="form">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                <input type="hidden" name="type" value="" data-bulk-type />
                <input type="hidden" name="original_request" value="<?= base64_encode(\Altum\Router::$original_request) ?>" />
                <input type="hidden" name="original_request_query" value="<?= base64_encode(\Altum\Router::$original_request_query) ?>" />

                <div class="fcc-contacts-mobile-list d-md-none">
                    <?php foreach($data->data as $row): ?>
                        <?php $fcc_contact_is_highlighted = $row->contact_status !== 'ready'; ?>
                        <div class="fcc-contact-mobile-card <?= $fcc_contact_is_highlighted ? 'is-highlighted' : null ?>">
                            <div class="fcc-contact-mobile-head">
                                <div class="fcc-contact-avatar"><?= $row->initials ?: 'C' ?></div>

                                <div class="fcc-contact-mobile-head-copy">
                                    <div class="fcc-contact-name"><?= $row->contact_identity ?></div>

                                    <?php if($row->contact_email): ?><div class="fcc-contact-line"><strong>Email:</strong> <?= $row->contact_email ?></div><?php endif ?>
                                    <?php if($row->contact_phone): ?><div class="fcc-contact-line"><strong>Telefon:</strong> <?= $row->contact_phone ?></div><?php endif ?>

                                    <?php if(!$row->contact_email && !$row->contact_phone && $row->contact_name): ?>
                                        <div class="fcc-contact-line"><strong>Ime:</strong> <?= $row->contact_name ?></div>
                                    <?php endif ?>

                                    <div class="fcc-contact-mobile-status">
                                        <div class="fcc-contact-status-badge <?= $row->contact_status === 'ready' ? 'is-ready' : 'is-review' ?>">
                                            <i class="fas fa-fw <?= $row->contact_status === 'ready' ? 'fa-bolt' : 'fa-exclamation-circle' ?>"></i>
                                            <?= $row->contact_status_label ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="fcc-contact-mobile-section">
                                <div class="fcc-contact-mobile-section-title"><?= l('vip_funnel.contacts.data.section.source') ?></div>
                                    <div class="fcc-contact-source">
                                        <div class="fcc-contact-source-title">
                                            <?= $row->biolink_block_id ? string_truncate($row->settings->name ?? l('global.unknown'), 42) : ($row->source_label ?: l('global.unknown')) ?>
                                        </div>
                                    <div class="fcc-contact-meta"><?= l('vip_funnel.contacts.data.label.app') ?>: <?= $row->app_name ?></div>
                                    <?php if($row->is_ai_chat_lead): ?>
                                        <div class="fcc-contact-meta text-info font-weight-bold"><?= l('vip_funnel.contacts.data.note.ai_origin') ?></div>
                                    <?php endif ?>
                                    <?php if($row->preferred_contact_channel): ?>
                                        <div class="fcc-contact-meta"><?= l('vip_funnel.contacts.data.label.preferred_channel') ?>: <?= mb_strtoupper($row->preferred_contact_channel) ?></div>
                                    <?php endif ?>
                                    <?php if($row->contact_intent): ?>
                                        <div class="fcc-contact-meta"><?= l('vip_funnel.contacts.data.label.intent') ?>: <?= htmlspecialchars((string) $row->contact_intent, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif ?>
                                    <?php if($row->source_context): ?>
                                        <div class="fcc-contact-meta"><?= $row->source_context ?></div>
                                    <?php endif ?>

                                    <div class="fcc-contact-notes">
                                        <?php if($row->contact_message): ?>
                                            <div class="fcc-contact-note"><?= l('vip_funnel.contacts.data.label.message') ?>: <?= string_truncate($row->contact_message, 90) ?></div>
                                        <?php endif ?>
                                        <?php if(!empty($row->extra_fields)): ?>
                                            <?php foreach(array_slice($row->extra_fields, 0, 2) as $field): ?>
                                                <div class="fcc-contact-note"><?= ucfirst(str_replace('_', ' ', $field['label'])) ?>: <?= string_truncate($field['value'], 70) ?></div>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                        <?php if(!$row->contact_message && empty($row->extra_fields)): ?>
                                            <div class="fcc-contact-note"><?= l('vip_funnel.contacts.data.note.ready') ?></div>
                                        <?php endif ?>
                                    </div>

                                    <div class="fcc-contact-tags">
                                        <span class="fcc-contact-tag">
                                            <i class="<?= $row->type_icon ?> fa-fw fa-sm"></i>
                                            <?= $row->type_label ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="fcc-contact-mobile-section">
                                <div class="fcc-contact-mobile-section-title"><?= l('vip_funnel.contacts.data.section.actions') ?></div>
                                <div class="fcc-contact-actions">
                                    <?php if($row->primary_action): ?>
                                        <a href="<?= $row->primary_action['url'] ?>" target="_blank" rel="noopener noreferrer" class="fcc-contact-primary-action <?= $row->primary_action['class'] ?>">
                                            <i class="<?= $row->primary_action['icon'] ?>"></i>
                                            <?= $row->primary_action['label'] ?>
                                        </a>
                                    <?php endif ?>

                                    <?php if(!empty($row->available_actions)): ?>
                                        <div class="fcc-contact-secondary-actions">
                                            <?php foreach($row->available_actions as $action): ?>
                                                <?php if($row->primary_action && $action['key'] === $row->primary_action['key']) continue; ?>
                                                <a href="<?= $action['url'] ?>" target="_blank" rel="noopener noreferrer" class="fcc-contact-secondary-action">
                                                    <i class="<?= $action['icon'] ?>"></i>
                                                    <?= $action['label'] ?>
                                                </a>
                                            <?php endforeach ?>
                                        </div>
                                    <?php endif ?>

                                    <div class="fcc-contact-actions-footer">
                                        <div class="fcc-contact-date">
                                            <?= \Altum\Date::get($row->datetime, 2) ?><br />
                                            <small><?= \Altum\Date::get_timeago($row->datetime) ?></small>
                                        </div>

                                        <div class="fcc-contact-actions-menu">
                                            <?= include_view(THEME_PATH . 'views/data/datum_dropdown_button.php', ['id' => $row->datum_id, 'button_text_class' => 'text-muted']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>

                <div class="fcc-contacts-table-card d-none d-md-block">
                    <div class="table-responsive table-custom-container">
                        <table class="table table-custom">
                            <thead>
                            <tr>
                                <th data-bulk-table class="d-none">
                                    <div class="custom-control custom-checkbox">
                                        <input id="bulk_select_all" type="checkbox" class="custom-control-input" />
                                        <label class="custom-control-label" for="bulk_select_all"></label>
                                    </div>
                                </th>
                                <th><?= l('vip_funnel.contacts.data.section.contact') ?></th>
                                <th><?= l('vip_funnel.contacts.data.section.source') ?></th>
                                <th><?= l('vip_funnel.contacts.data.section.actions') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($data->data as $row): ?>
                                <?php $fcc_contact_is_highlighted = $row->contact_status !== 'ready'; ?>
                                <tr class="<?= $fcc_contact_is_highlighted ? 'fcc-contact-row-highlight' : null ?>">
                                    <td data-bulk-table class="d-none">
                                        <div class="custom-control custom-checkbox">
                                            <input id="selected_datum_id_<?= $row->datum_id ?>" type="checkbox" class="custom-control-input" name="selected[]" value="<?= $row->datum_id ?>" />
                                            <label class="custom-control-label" for="selected_datum_id_<?= $row->datum_id ?>"></label>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fcc-contact-mobile-label"><?= l('vip_funnel.contacts.data.section.contact') ?></div>
                                        <div class="fcc-contact-main">
                                            <div class="fcc-contact-avatar"><?= $row->initials ?: 'C' ?></div>

                                            <div>
                                                <div class="fcc-contact-name"><?= $row->contact_identity ?></div>

                                                <?php if($row->contact_email): ?><div class="fcc-contact-line"><strong><?= l('vip_funnel.contacts.data.label.email') ?>:</strong> <?= $row->contact_email ?></div><?php endif ?>
                                                <?php if($row->contact_phone): ?><div class="fcc-contact-line"><strong><?= l('vip_funnel.contacts.data.label.phone') ?>:</strong> <?= $row->contact_phone ?></div><?php endif ?>

                                                <?php if(!$row->contact_email && !$row->contact_phone && $row->contact_name): ?>
                                                    <div class="fcc-contact-line"><strong><?= l('vip_funnel.contacts.data.label.name') ?>:</strong> <?= $row->contact_name ?></div>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fcc-contact-mobile-label"><?= l('vip_funnel.contacts.data.section.source') ?></div>
                                        <div class="fcc-contact-source <?= $fcc_contact_is_highlighted ? 'is-emphasized' : null ?>">
                                            <div class="fcc-contact-source-title">
                                                <?= $row->biolink_block_id ? string_truncate($row->settings->name ?? l('global.unknown'), 42) : ($row->source_label ?: l('global.unknown')) ?>
                                            </div>
                                            <div class="fcc-contact-meta"><?= l('vip_funnel.contacts.data.label.app') ?>: <?= $row->app_name ?></div>
                                            <?php if($row->is_ai_chat_lead): ?>
                                                <div class="fcc-contact-meta text-info font-weight-bold"><?= l('vip_funnel.contacts.data.note.ai_origin') ?></div>
                                            <?php endif ?>
                                            <?php if($row->preferred_contact_channel): ?>
                                                <div class="fcc-contact-meta"><?= l('vip_funnel.contacts.data.label.preferred_channel') ?>: <?= mb_strtoupper($row->preferred_contact_channel) ?></div>
                                            <?php endif ?>
                                            <?php if($row->contact_intent): ?>
                                                <div class="fcc-contact-meta"><?= l('vip_funnel.contacts.data.label.intent') ?>: <?= htmlspecialchars((string) $row->contact_intent, ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif ?>
                                            <?php if($row->source_context): ?>
                                                <div class="fcc-contact-meta"><?= $row->source_context ?></div>
                                            <?php endif ?>
                                            <div class="fcc-contact-status-badge <?= $row->contact_status === 'ready' ? 'is-ready' : 'is-review' ?>">
                                                <i class="fas fa-fw <?= $row->contact_status === 'ready' ? 'fa-bolt' : 'fa-exclamation-circle' ?>"></i>
                                                <?= $row->contact_status_label ?>
                                            </div>
                                            <div class="fcc-contact-notes">
                                                <?php if($row->contact_message): ?>
                                                    <div class="fcc-contact-note"><?= l('vip_funnel.contacts.data.label.message') ?>: <?= string_truncate($row->contact_message, 90) ?></div>
                                                <?php endif ?>
                                                <?php if(!empty($row->extra_fields)): ?>
                                                    <?php foreach(array_slice($row->extra_fields, 0, 2) as $field): ?>
                                                        <div class="fcc-contact-note"><?= ucfirst(str_replace('_', ' ', $field['label'])) ?>: <?= string_truncate($field['value'], 70) ?></div>
                                                    <?php endforeach ?>
                                                <?php endif ?>
                                                <?php if(!$row->contact_message && empty($row->extra_fields)): ?>
                                                    <div class="fcc-contact-note"><?= l('vip_funnel.contacts.data.note.ready') ?></div>
                                                <?php endif ?>
                                            </div>
                                            <div class="fcc-contact-tags">
                                                <span class="fcc-contact-tag">
                                                    <i class="<?= $row->type_icon ?> fa-fw fa-sm"></i>
                                                    <?= $row->type_label ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fcc-contact-mobile-label"><?= l('vip_funnel.contacts.data.section.actions') ?></div>
                                        <div class="fcc-contact-actions <?= $fcc_contact_is_highlighted ? 'is-emphasized' : null ?>">
                                            <?php if($row->primary_action): ?>
                                                <a href="<?= $row->primary_action['url'] ?>" target="_blank" rel="noopener noreferrer" class="fcc-contact-primary-action <?= $row->primary_action['class'] ?>">
                                                    <i class="<?= $row->primary_action['icon'] ?>"></i>
                                                    <?= $row->primary_action['label'] ?>
                                                </a>
                                            <?php endif ?>

                                            <?php if(!empty($row->available_actions)): ?>
                                                <div class="fcc-contact-secondary-actions">
                                                    <?php foreach($row->available_actions as $action): ?>
                                                        <?php if($row->primary_action && $action['key'] === $row->primary_action['key']) continue; ?>
                                                        <a href="<?= $action['url'] ?>" target="_blank" rel="noopener noreferrer" class="fcc-contact-secondary-action">
                                                            <i class="<?= $action['icon'] ?>"></i>
                                                            <?= $action['label'] ?>
                                                        </a>
                                                    <?php endforeach ?>
                                                </div>
                                            <?php endif ?>

                                            <div class="fcc-contact-actions-footer">
                                                <div class="fcc-contact-date">
                                                    <?= \Altum\Date::get($row->datetime, 2) ?><br />
                                                    <small><?= \Altum\Date::get_timeago($row->datetime) ?></small>
                                                </div>

                                                <div class="fcc-contact-actions-menu">
                                                    <?= include_view(THEME_PATH . 'views/data/datum_dropdown_button.php', ['id' => $row->datum_id, 'button_text_class' => 'text-muted']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

            <div class="fcc-contacts-pagination"><?= $data->pagination ?></div>
        <?php else: ?>
            <div class="fcc-contacts-empty">
                <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                    'filters_get' => $data->filters->get ?? [],
                    'name' => 'data',
                    'has_secondary_text' => false,
                ]); ?>
            </div>
        <?php endif ?>
    </div>
</div>

<?php require THEME_PATH . 'views/partials/js_bulk.php' ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_delete_modal.php'), 'modals'); ?>
