<?php defined('ALTUMCODE') || die() ?>

<?php ob_start() ?>
<style>
    .funnels-analytics-shell {
        position: relative;
    }

    .funnels-analytics-shell::before {
        content: '';
        position: absolute;
        inset: -2rem 0 auto;
        height: 24rem;
        background:
            radial-gradient(circle at top left, rgba(73, 227, 207, 0.16), transparent 32%),
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.14), transparent 28%),
            linear-gradient(180deg, rgba(15, 23, 42, 0.16), rgba(15, 23, 42, 0));
        pointer-events: none;
    }

    .funnels-analytics-shell > * {
        position: relative;
        z-index: 1;
    }

    .funnels-analytics-panel,
    .funnels-analytics-card,
    .funnels-analytics-filter-card,
    .funnels-analytics-modal-card {
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 1.5rem;
        background:
            linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02)),
            radial-gradient(circle at top right, rgba(73, 227, 207, 0.08), transparent 28%);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .funnels-analytics-panel .card-body,
    .funnels-analytics-card .card-body,
    .funnels-analytics-filter-card .card-body,
    .funnels-analytics-modal-card .card-body {
        padding: 1.25rem;
    }

    .funnels-analytics-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .funnels-analytics-kpi {
        width: 100%;
        text-align: left;
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 1.35rem;
        padding: 1.05rem 1.1rem;
        background: rgba(15, 23, 42, 0.72);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
        min-height: 9.2rem;
    }

    .funnels-analytics-filter-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
        width: 100%;
    }

    .funnels-analytics-filter-actions .btn {
        min-height: 2.75rem;
        border-radius: .95rem;
        font-weight: 700;
        font-size: .92rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
    }

    .funnels-analytics-period-switcher {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.06);
        background: rgba(15, 23, 42, 0.72);
        margin-bottom: 1rem;
    }

    .funnels-analytics-period-switcher-link {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .45rem .85rem;
        color: var(--gray-500);
        font-size: .78rem;
        font-weight: 700;
        text-decoration: none;
        transition: background .2s ease, color .2s ease, transform .2s ease;
    }

    .funnels-analytics-period-switcher-link:hover {
        color: #fff;
        text-decoration: none;
    }

    .funnels-analytics-period-switcher-link.is-active {
        background: rgba(73, 227, 207, 0.14);
        color: #49e3cf;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
    }

    .funnels-analytics-kpi.is-actionable {
        cursor: pointer;
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .funnels-analytics-kpi.is-actionable:hover {
        transform: translateY(-2px);
        border-color: rgba(73, 227, 207, 0.28);
        box-shadow: 0 16px 35px rgba(15, 23, 42, 0.24);
    }

    .funnels-analytics-kpi-label {
        color: var(--gray-500);
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .8rem;
    }

    .funnels-analytics-kpi-value {
        font-size: 2rem;
        line-height: 1;
        font-weight: 700;
        color: #fff;
        background: none;
        border: 0;
        padding: 0;
    }

    .funnels-analytics-kpi-value.is-link {
        color: #49e3cf;
    }

    .funnels-analytics-kpi-meta {
        color: var(--gray-600);
        font-size: .82rem;
        line-height: 1.45;
        margin-top: .65rem;
    }

    .funnels-analytics-main-grid,
    .funnels-analytics-bottom-grid,
    .funnels-analytics-flow-grid,
    .funnels-analytics-insight-grid {
        display: grid;
        gap: 1rem;
    }

    .funnels-analytics-main-grid {
        grid-template-columns: minmax(0, 1.6fr) minmax(20rem, .9fr);
        margin-bottom: 1rem;
    }

    .funnels-analytics-bottom-grid {
        grid-template-columns: minmax(0, 1.25fr) minmax(20rem, .95fr);
    }

    .funnels-analytics-insight-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-bottom: 1rem;
    }

    .funnels-analytics-flow-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-top: 1rem;
    }

    .funnels-analytics-stack + .funnels-analytics-stack {
        margin-top: 1rem;
    }

    .funnels-analytics-title {
        font-size: .96rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: .25rem;
    }

    .funnels-analytics-help,
    .funnels-analytics-muted,
    .funnels-analytics-meta {
        color: var(--gray-600);
        font-size: .82rem;
        line-height: 1.45;
    }

    .funnels-analytics-help {
        margin-bottom: 1rem;
    }

    .funnels-analytics-chart-wrap {
        position: relative;
        height: 20rem;
    }

    .funnels-analytics-source-row,
    .funnels-analytics-device-row,
    .funnels-analytics-funnel-row,
    .funnels-analytics-submission-row,
    .funnels-analytics-modal-contact,
    .funnels-analytics-biolink-row {
        border-radius: 1.05rem;
        border: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.025);
    }

    .funnels-analytics-source-row,
    .funnels-analytics-device-row,
    .funnels-analytics-funnel-row,
    .funnels-analytics-modal-contact,
    .funnels-analytics-biolink-row {
        padding: .9rem 1rem;
    }

    .funnels-analytics-submission-row {
        padding: 1rem 1.05rem;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
    }

    .funnels-analytics-submission-main {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: .22rem;
    }

    .funnels-analytics-submission-side {
        min-width: 12.5rem;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: space-between;
        gap: .85rem;
    }

    .funnels-analytics-submission-date {
        text-align: right;
    }

    .funnels-analytics-submission-date-value {
        color: #cbd5e1;
        font-size: .82rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .funnels-analytics-submission-actions {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: .45rem;
    }

    .funnels-analytics-submission-actions .btn {
        width: 100%;
        min-width: 0;
        justify-content: center;
    }

    .funnels-analytics-submission-secondary-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .45rem;
        width: 100%;
    }

    .funnels-analytics-submission-funnel {
        color: #94a3b8;
        font-size: .8rem;
        font-weight: 600;
        line-height: 1.35;
        margin-top: .2rem;
    }

    .funnels-analytics-source-row + .funnels-analytics-source-row,
    .funnels-analytics-device-row + .funnels-analytics-device-row,
    .funnels-analytics-funnel-row + .funnels-analytics-funnel-row,
    .funnels-analytics-submission-row + .funnels-analytics-submission-row,
    .funnels-analytics-modal-contact + .funnels-analytics-modal-contact,
    .funnels-analytics-biolink-row + .funnels-analytics-biolink-row {
        margin-top: .75rem;
    }

    .funnels-analytics-progress {
        width: 100%;
        height: .45rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.08);
        overflow: hidden;
        margin-top: .45rem;
    }

    .funnels-analytics-progress-bar {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, rgba(73, 227, 207, 0.95), rgba(59, 130, 246, 0.92));
    }

    <?php /* Custom code: FC-2026-03-23: force source percentage text to white */ ?>
    .funnels-analytics-source-row .font-weight-bold {
        color: #fff !important;
    }
    <?php /* /Custom code: FC-2026-03-23 */ ?>

    .funnels-analytics-funnel-row {
        padding: 1rem 1.05rem;
    }

    .funnels-analytics-funnel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .funnels-analytics-funnel-summary {
        min-width: 0;
        flex: 1;
    }

    .funnels-analytics-funnel-metrics {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: .85rem;
    }

    .funnels-analytics-funnel-metric {
        padding-top: .85rem;
        border-top: 1px solid rgba(255,255,255,0.06);
    }

    .funnels-analytics-funnel-name,
    .funnels-analytics-submission-title {
        color: #fff;
        font-weight: 700;
        line-height: 1.35;
        margin-bottom: .25rem;
    }

    .funnels-analytics-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: .16rem .5rem;
        background: rgba(255,255,255,0.06);
        color: var(--gray-500);
        font-size: .68rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        max-width: 100%;
    }

    .funnels-analytics-badge.is-ready {
        background: rgba(46, 211, 198, 0.14);
        color: #9ef1e7;
        border: 1px solid rgba(46, 211, 198, 0.22);
    }

    .funnels-analytics-badge.is-review {
        background: rgba(245, 158, 11, 0.12);
        color: #fcd58a;
        border: 1px solid rgba(245, 158, 11, 0.22);
    }

    .funnels-analytics-badge-wrap {
        display: flex;
        align-items: center;
        gap: .5rem;
        min-width: 0;
    }

    .funnels-analytics-stat-label {
        color: var(--gray-500);
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-bottom: .2rem;
    }

    .funnels-analytics-stat-value {
        color: #fff;
        font-weight: 700;
        font-size: .98rem;
    }

    <?php /* Custom code: FC-2026-03-23: compact last submission date styling */ ?>
    .funnels-analytics-stat-value-date {
        font-size: .84rem;
        line-height: 1.2;
        white-space: nowrap;
    }
    <?php /* /Custom code: FC-2026-03-23 */ ?>

    .funnels-analytics-actions,
    .funnels-analytics-submission-actions,
    .funnels-analytics-modal-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }

    .funnels-analytics-actions {
        justify-content: flex-end;
    }

    .funnels-analytics-highlight {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .85rem;
        padding: 1rem 1.05rem;
        border-radius: 1.15rem;
        border: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.025);
    }

    .funnels-analytics-highlight + .funnels-analytics-highlight {
        margin-top: .75rem;
    }

    .funnels-analytics-highlight-value {
        color: #fff;
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.25;
    }

    .funnels-analytics-flow-step {
        padding: 1rem 1.05rem;
        border-radius: 1.15rem;
        border: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.025);
    }

    .funnels-analytics-flow-step-value {
        color: #fff;
        font-size: 1.45rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: .4rem;
    }

    .funnels-analytics-list-item + .funnels-analytics-list-item {
        margin-top: .75rem;
    }

    .funnels-analytics-actions .btn,
    .funnels-analytics-submission-actions .btn,
    .funnels-analytics-modal-actions .btn {
        border-radius: .8rem;
    }

    .funnels-analytics-outline-cta {
        color: #eafffb;
        background: rgba(73, 227, 207, 0.12);
        border: 1px solid rgba(73, 227, 207, 0.42);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04), 0 10px 24px rgba(0, 0, 0, 0.18);
        font-weight: 700;
        transition: background .2s ease, border-color .2s ease, color .2s ease, transform .2s ease, box-shadow .2s ease;
    }

    .funnels-analytics-outline-cta:hover,
    .funnels-analytics-outline-cta:focus {
        color: #ffffff;
        background: rgba(73, 227, 207, 0.2);
        border-color: rgba(73, 227, 207, 0.72);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), 0 14px 30px rgba(0, 0, 0, 0.24);
        transform: translateY(-1px);
    }

    .funnels-analytics-contact-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        min-width: 0;
    }

    .funnels-analytics-contact-action.is-whatsapp {
        background: rgba(34, 197, 94, 0.14);
        border-color: rgba(34, 197, 94, 0.28);
        color: #b7f7cb;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
    }

    .funnels-analytics-contact-action.is-whatsapp:hover {
        background: rgba(34, 197, 94, 0.22);
        border-color: rgba(34, 197, 94, 0.38);
        color: #f3fff7;
    }

    .funnels-analytics-contact-action.is-viber {
        background: rgba(115, 96, 242, 0.18);
        border-color: rgba(115, 96, 242, 0.3);
        color: #d8d2ff;
    }

    .funnels-analytics-contact-action.is-viber:hover {
        background: rgba(115, 96, 242, 0.26);
        border-color: rgba(115, 96, 242, 0.4);
        color: #f1efff;
    }

    .funnels-analytics-contact-action.is-call,
    .funnels-analytics-contact-action.is-email {
        background: rgba(255,255,255,0.06);
        border-color: rgba(255,255,255,0.12);
        color: #fff;
    }

    .funnels-analytics-contact-action.is-sms {
        background: rgba(73, 227, 207, 0.2);
        border-color: rgba(73, 227, 207, 0.34);
        color: #dffdfa;
    }

    .funnels-analytics-contact-action.is-sms:hover {
        background: rgba(73, 227, 207, 0.28);
        border-color: rgba(73, 227, 207, 0.42);
        color: #ffffff;
    }

    .funnels-analytics-contact-action.is-call:hover,
    .funnels-analytics-contact-action.is-email:hover {
        background: rgba(255,255,255,0.12);
        border-color: rgba(255,255,255,0.18);
        color: #fff;
    }

    .funnels-analytics-footer {
        margin-top: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .funnels-analytics-empty {
        color: var(--gray-600);
        font-size: .9rem;
        margin: 0;
    }

    .funnels-analytics-modal .modal-content {
        border: 0;
        border-radius: 1.5rem;
        overflow: hidden;
        background: #0f172a;
        color: #fff;
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.45);
    }

    .funnels-analytics-modal .modal-header,
    .funnels-analytics-modal .modal-footer {
        border-color: rgba(255,255,255,0.08);
    }

    .funnels-analytics-modal .close {
        color: #fff;
        opacity: .75;
    }

    .funnels-analytics-contact-link {
        color: #49e3cf;
        font-weight: 700;
        text-decoration: none;
    }

    .funnels-analytics-contact-link:hover {
        color: #8cf3e5;
        text-decoration: none;
    }

    .funnels-analytics-device-row .font-weight-bold,
    .funnels-analytics-device-row .funnels-analytics-meta,
    .funnels-analytics-device-row .h5 {
        color: #fff !important;
    }

    .funnels-analytics-modal-block {
        display: block;
        border-radius: 1.1rem;
        border: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.025);
        padding: 1rem 1.05rem;
        color: inherit;
        text-decoration: none;
        transition: transform .18s ease, border-color .18s ease, background .18s ease;
    }

    .funnels-analytics-modal-block:hover {
        text-decoration: none;
        color: inherit;
        transform: translateY(-1px);
        border-color: rgba(73, 227, 207, 0.25);
        background: rgba(73, 227, 207, 0.05);
    }

    .funnels-analytics-modal-block + .funnels-analytics-modal-block {
        margin-top: .8rem;
    }

    .funnels-analytics-modal-block-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) repeat(4, minmax(5.5rem, .7fr)) auto;
        gap: .85rem;
        align-items: center;
    }

    .funnels-analytics-modal-block-name {
        color: #fff;
        font-weight: 700;
        line-height: 1.35;
        margin-bottom: .25rem;
    }

    .funnels-analytics-modal-block-arrow {
        color: #49e3cf;
        font-size: 1rem;
        text-align: right;
    }

    @media (max-width: 1199.98px) {
        .funnels-analytics-kpi-grid,
        .funnels-analytics-main-grid,
        .funnels-analytics-bottom-grid,
        .funnels-analytics-insight-grid,
        .funnels-analytics-flow-grid,
        .funnels-analytics-funnel-metrics {
            grid-template-columns: 1fr;
        }

        .funnels-analytics-modal-block-grid {
            grid-template-columns: 1fr;
        }

        .funnels-analytics-funnel-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .funnels-analytics-submission-row {
            grid-template-columns: 1fr;
        }

        .funnels-analytics-submission-side {
            width: 100%;
            min-width: 0;
            align-items: flex-start;
        }

        .funnels-analytics-submission-date {
            text-align: left;
        }

        .funnels-analytics-submission-actions,
        .funnels-analytics-actions {
            justify-content: flex-start;
            min-width: 0;
        }

        .funnels-analytics-submission-secondary-actions {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .funnels-analytics-panel .card-body,
        .funnels-analytics-card .card-body,
        .funnels-analytics-filter-card .card-body,
        .funnels-analytics-modal-card .card-body {
            padding: 1rem;
        }

        .funnels-analytics-kpi {
            min-height: auto;
        }

        .funnels-analytics-filter-actions,
        .funnels-analytics-kpi-grid {
            grid-template-columns: 1fr;
        }

        .funnels-analytics-chart-wrap {
            height: 16rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<div class="container funnels-analytics-shell">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('links') ?>"><?= l('links.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('funnels_analytics.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="funnels-analytics-panel card border-0 mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4">
                <div class="mb-3 mb-lg-0 pr-lg-4">
                    <h1 class="h4 text-truncate mb-2"><i class="fas fa-fw fa-xs fa-filter mr-1"></i> <?= l('funnels_analytics.header') ?></h1>
                    <p class="text-muted mb-0" style="max-width: 56rem;"><?= l('funnels_analytics.subheader') ?></p>
                </div>

                <div class="d-flex align-items-center col-auto p-0">
                    <button id="daterangepicker" type="button" class="btn btn-sm btn-light" data-min-date="<?= \Altum\Date::get($this->user->datetime, 4) ?>" data-max-date="<?= \Altum\Date::get('', 4) ?>">
                        <i class="fas fa-fw fa-calendar mr-lg-1"></i>
                        <span class="d-none d-lg-inline-block">
                            <?php if($data->datetime['start_date'] == $data->datetime['end_date']): ?>
                                <?= \Altum\Date::get($data->datetime['start_date'], 6, \Altum\Date::$default_timezone) ?>
                            <?php else: ?>
                                <?= \Altum\Date::get($data->datetime['start_date'], 6, \Altum\Date::$default_timezone) . ' - ' . \Altum\Date::get($data->datetime['end_date'], 6, \Altum\Date::$default_timezone) ?>
                            <?php endif ?>
                        </span>
                        <i class="fas fa-fw fa-caret-down d-none d-lg-inline-block ml-lg-1"></i>
                    </button>
                </div>
            </div>

            <div class="funnels-analytics-filter-card card border-0 mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <div class="funnels-analytics-title"><?= l('funnels_analytics.filters') ?></div>
                        <div class="funnels-analytics-help mb-0"><?= l('funnels_analytics.filters_help') ?></div>
                    </div>

                    <form action="<?= url('funnels-analytics') ?>" method="get">
                        <div class="row align-items-end">
                            <div class="col-12 col-lg-3 mb-3">
                                <label for="funnels_analytics_project_id" class="small text-muted mb-1"><?= l('funnels_analytics.filters.project') ?></label>
                                <select id="funnels_analytics_project_id" name="project_id" class="custom-select">
                                    <option value=""><?= l('funnels_analytics.filters.all_projects') ?></option>
                                    <?php foreach($data->projects as $project): ?>
                                        <option value="<?= $project->project_id ?>" <?= (int) ($_GET['project_id'] ?? 0) === (int) $project->project_id ? 'selected="selected"' : null ?>><?= $project->name ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="col-12 col-lg-3 mb-3">
                                <label for="funnels_analytics_link_id" class="small text-muted mb-1"><?= l('funnels_analytics.filters.biolink') ?></label>
                                <select id="funnels_analytics_link_id" name="link_id" class="custom-select">
                                    <option value=""><?= l('funnels_analytics.filters.all_biolinks') ?></option>
                                    <?php foreach($data->biolinks as $biolink): ?>
                                        <option value="<?= $biolink->link_id ?>" <?= (int) ($_GET['link_id'] ?? 0) === (int) $biolink->link_id ? 'selected="selected"' : null ?>><?= $biolink->url ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="col-12 col-lg-4 mb-3">
                                <label for="funnels_analytics_biolink_block_id" class="small text-muted mb-1"><?= l('funnels_analytics.filters.funnel') ?></label>
                                <select id="funnels_analytics_biolink_block_id" name="biolink_block_id" class="custom-select">
                                    <option value=""><?= l('funnels_analytics.filters.all_funnels') ?></option>
                                    <?php foreach($data->funnel_options as $funnel_option): ?>
                                        <option value="<?= $funnel_option->biolink_block_id ?>" <?= (int) ($_GET['biolink_block_id'] ?? 0) === (int) $funnel_option->biolink_block_id ? 'selected="selected"' : null ?>><?= $funnel_option->name ?> · <?= $funnel_option->biolink_url ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="col-12 col-lg-2 mb-3">
                                <div class="funnels-analytics-filter-actions">
                                    <button type="submit" class="btn btn-primary"><?= l('global.submit') ?></button>
                                    <a href="<?= url('funnels-analytics') ?>" class="btn btn-light"><?= l('global.reset') ?></a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if(!$data->has_funnels): ?>
                <?= include_view(THEME_PATH . 'views/partials/no_data.php', ['filters_get' => $_GET, 'name' => 'funnels_analytics', 'has_secondary_text' => true]) ?>
            <?php else: ?>
                <?php
                $summary_switcher_query = [];
                foreach(['project_id', 'link_id', 'biolink_block_id', 'start_date', 'end_date'] as $parameter) {
                    if(isset($_GET[$parameter]) && $_GET[$parameter] !== '') {
                        $summary_switcher_query[$parameter] = $_GET[$parameter];
                    }
                }
                $selected_period_summary = $data->selected_period_summary ?? $data->summary;
                ?>

                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                    <div class="mb-2 mb-lg-0">
                        <div class="funnels-analytics-title mb-1"><?= l('funnels_analytics.period_overview') ?></div>
                        <div class="funnels-analytics-help mb-0"><?= l('funnels_analytics.period_overview_help') ?></div>
                    </div>

                    <div class="funnels-analytics-period-switcher">
                        <?php foreach([7, 30, 90] as $period_days): ?>
                            <?php $period_url = url('funnels-analytics?' . http_build_query(array_merge($summary_switcher_query, ['summary_window' => $period_days]))); ?>
                            <a href="<?= $period_url ?>" class="funnels-analytics-period-switcher-link <?= (int) ($_GET['summary_window'] ?? 30) === $period_days ? 'is-active' : null ?>"><?= sprintf(l('funnels_analytics.period_days'), $period_days) ?></a>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="funnels-analytics-kpi-grid">
                    <button type="button" class="funnels-analytics-kpi is-actionable" data-toggle="modal" data-target="#funnels-active-biolinks-modal">
                        <div class="funnels-analytics-kpi-label"><?= l('funnels_analytics.active_funnels') ?></div>
                        <div class="funnels-analytics-kpi-value is-link"><?= nr($selected_period_summary['active_funnels'] ?? 0) ?> / <?= nr($selected_period_summary['total_funnels'] ?? 0) ?></div>
                        <div class="funnels-analytics-kpi-meta"><?= sprintf(l('funnels_analytics.active_funnels_meta'), (int) ($data->selected_period_days ?? 30)) ?></div>
                    </button>

                    <div class="funnels-analytics-kpi">
                        <div class="funnels-analytics-kpi-label"><?= l('biolink_lead_funnel.unique_clicks') ?></div>
                        <div class="funnels-analytics-kpi-value"><?= nr($selected_period_summary['unique_clicks'] ?? 0) ?></div>
                        <div class="funnels-analytics-kpi-meta"><?= l('funnels_analytics.simple_clicks_help') ?></div>
                    </div>

                    <button type="button" class="funnels-analytics-kpi is-actionable" data-toggle="modal" data-target="#funnels-leads-modal">
                        <div class="funnels-analytics-kpi-label"><?= l('biolink_lead_funnel.total_leads') ?></div>
                        <div class="funnels-analytics-kpi-value is-link"><?= nr($selected_period_summary['total_leads'] ?? 0) ?></div>
                        <div class="funnels-analytics-kpi-meta"><?= l('funnels_analytics.simple_leads_help') ?></div>
                    </button>

                    <div class="funnels-analytics-kpi">
                        <div class="funnels-analytics-kpi-label"><?= l('biolink_lead_funnel.conversion_rate') ?></div>
                        <div class="funnels-analytics-kpi-value"><?= $selected_period_summary['conversion_rate'] ?? 0 ?>%</div>
                        <div class="funnels-analytics-kpi-meta"><?= l('funnels_analytics.simple_conversion_help') ?></div>
                    </div>

                    <div class="funnels-analytics-kpi">
                        <div class="funnels-analytics-kpi-label"><?= l('funnels_analytics.best_setup') ?></div>
                        <div class="funnels-analytics-kpi-value" style="font-size: 1.3rem; line-height: 1.25;"><?= $data->type_performance['best_open_mode'] ? l('biolink_lead_funnel.open_mode_' . $data->type_performance['best_open_mode']['type']) : '—' ?></div>
                        <div class="funnels-analytics-kpi-meta"><?= l('funnels_analytics.best_thank_you_type') ?>: <?= $data->type_performance['best_thank_you_type'] ? l('biolink_lead_funnel.thank_you_type_' . $data->type_performance['best_thank_you_type']['type']) : '—' ?></div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center mb-4" style="gap: .75rem;">
                    <button type="button" class="btn btn-sm btn-outline-light" data-toggle="modal" data-target="#funnels-all-blocks-modal">
                        <i class="fas fa-fw fa-list-ul fa-sm mr-1"></i> <?= l('funnels_analytics.total_funnels') ?>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-light" data-toggle="modal" data-target="#funnels-active-biolinks-modal">
                        <i class="fas fa-fw fa-layer-group fa-sm mr-1"></i> <?= l('funnels_analytics.active_funnels') ?>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-light" data-toggle="modal" data-target="#funnels-leads-modal">
                        <i class="fas fa-fw fa-comments fa-sm mr-1"></i> <?= l('biolink_lead_funnel.total_leads') ?>
                    </button>
                </div>

                <div class="funnels-analytics-main-grid">
                    <div class="funnels-analytics-card card border-0">
                        <div class="card-body">
                            <div class="funnels-analytics-title"><?= l('biolink_lead_funnel.trend_title') ?></div>
                            <div class="funnels-analytics-help"><?= l('funnels_analytics.simple_trend_help') ?></div>

                            <?php if($data->summary['unique_clicks'] || $data->summary['total_leads']): ?>
                                <div class="funnels-analytics-chart-wrap"><canvas id="funnels_analytics_chart"></canvas></div>
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center text-center text-muted" style="min-height: 18rem;"><div><i class="fas fa-fw fa-chart-line fa-2x mb-3 opacity-50"></i><div><?= l('biolink_lead_funnel.no_clicks_yet') ?></div></div></div>
                            <?php endif ?>
                        </div>
                    </div>

                    <div>
                        <div class="funnels-analytics-card card border-0 funnels-analytics-stack">
                            <div class="card-body">
                                <div class="funnels-analytics-title"><?= l('funnels_analytics.best_performing_title') ?></div>
                                <div class="funnels-analytics-help"><?= l('funnels_analytics.best_performing_help') ?></div>

                                <div class="funnels-analytics-highlight">
                                    <div>
                                        <div class="funnels-analytics-stat-label"><?= l('funnels_analytics.best_opening_mode') ?></div>
                                        <div class="funnels-analytics-highlight-value"><?= $data->type_performance['best_open_mode'] ? l('biolink_lead_funnel.open_mode_' . $data->type_performance['best_open_mode']['type']) : '—' ?></div>
                                        <?php if($data->type_performance['best_open_mode']): ?>
                                            <div class="funnels-analytics-meta"><?= nr($data->type_performance['best_open_mode']['total_leads']) ?> <?= mb_strtolower(l('biolink_lead_funnel.total_leads')) ?> · <?= nr($data->type_performance['best_open_mode']['conversion_rate']) ?>%</div>
                                        <?php endif ?>
                                    </div>
                                    <span class="funnels-analytics-badge"><?= sprintf(l('funnels_analytics.period_days_short'), (int) ($data->selected_period_days ?? 30)) ?></span>
                                </div>

                                <div class="funnels-analytics-highlight">
                                    <div>
                                        <div class="funnels-analytics-stat-label"><?= l('funnels_analytics.best_thank_you_type') ?></div>
                                        <div class="funnels-analytics-highlight-value"><?= $data->type_performance['best_thank_you_type'] ? l('biolink_lead_funnel.thank_you_type_' . $data->type_performance['best_thank_you_type']['type']) : '—' ?></div>
                                        <?php if($data->type_performance['best_thank_you_type']): ?>
                                            <div class="funnels-analytics-meta"><?= nr($data->type_performance['best_thank_you_type']['total_leads']) ?> <?= mb_strtolower(l('biolink_lead_funnel.total_leads')) ?> · <?= nr($data->type_performance['best_thank_you_type']['conversion_rate']) ?>%</div>
                                        <?php endif ?>
                                    </div>
                                    <span class="funnels-analytics-badge"><?= sprintf(l('funnels_analytics.period_days_short'), (int) ($data->selected_period_days ?? 30)) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="funnels-analytics-card card border-0 funnels-analytics-stack">
                            <div class="card-body">
                                <div class="funnels-analytics-title"><?= l('funnels_analytics.top_funnels_title') ?></div>
                                <div class="funnels-analytics-help"><?= l('funnels_analytics.top_funnels_help') ?></div>

                                <?php if($data->top_funnels): ?>
                                    <?php foreach($data->top_funnels as $funnel): ?>
                                        <div class="funnels-analytics-highlight funnels-analytics-list-item">
                                            <div class="min-width-0">
                                                <div class="funnels-analytics-funnel-name"><?= $funnel->name ?></div>
                                                <div class="funnels-analytics-meta"><?= $funnel->biolink_url ?></div>
                                                <div class="mt-2 funnels-analytics-badge-wrap">
                                                    <span class="funnels-analytics-badge"><?= $funnel->open_mode == 'page' ? l('biolink_lead_funnel.open_mode_page') : l('biolink_lead_funnel.open_mode_popup') ?></span>
                                                    <span class="funnels-analytics-badge"><?= l('biolink_lead_funnel.thank_you_type_' . $funnel->thank_you_type) ?></span>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="funnels-analytics-highlight-value"><?= nr($funnel->selected_total_leads) ?></div>
                                                <div class="funnels-analytics-meta"><?= l('biolink_lead_funnel.total_leads') ?> · <?= nr($funnel->selected_conversion_rate) ?>%</div>
                                            </div>
                                        </div>
                                    <?php endforeach ?>
                                <?php else: ?>
                                    <p class="funnels-analytics-empty"><?= l('biolink_lead_funnel.no_clicks_yet') ?></p>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                $flow_stage_labels = [
                    'entry_to_start' => l('funnels_analytics.flow_entry_to_start'),
                    'start_to_success' => l('funnels_analytics.flow_start_to_success'),
                    'success_to_cta' => l('funnels_analytics.flow_success_to_cta'),
                ];
                ?>

                <div class="funnels-analytics-card card border-0 mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap: .75rem;">
                            <div>
                                <div class="funnels-analytics-title"><?= l('funnels_analytics.flow_title') ?></div>
                                <div class="funnels-analytics-help mb-0"><?= l('funnels_analytics.flow_help') ?></div>
                            </div>
                            <?php if($data->flow_summary['weakest_stage']): ?>
                                <span class="funnels-analytics-badge"><?= l('funnels_analytics.weakest_stage') ?>: <?= $flow_stage_labels[$data->flow_summary['weakest_stage']] ?? '—' ?></span>
                            <?php endif ?>
                        </div>

                        <div class="funnels-analytics-flow-grid">
                            <div class="funnels-analytics-flow-step">
                                <div class="funnels-analytics-stat-label"><?= l('funnels_analytics.flow_entries') ?></div>
                                <div class="funnels-analytics-flow-step-value"><?= nr($data->flow_summary['entry_points']) ?></div>
                                <div class="funnels-analytics-meta"><?= nr($data->flow_summary['page_views']) ?> <?= mb_strtolower(l('biolink_lead_funnel.open_mode_page')) ?> · <?= nr($data->flow_summary['popup_opens']) ?> <?= mb_strtolower(l('biolink_lead_funnel.open_mode_popup')) ?></div>
                            </div>

                            <div class="funnels-analytics-flow-step">
                                <div class="funnels-analytics-stat-label"><?= l('funnels_analytics.flow_form_starts') ?></div>
                                <div class="funnels-analytics-flow-step-value"><?= nr($data->flow_summary['form_starts']) ?></div>
                                <div class="funnels-analytics-meta"><?= nr($data->flow_summary['entry_to_start_rate'] ?? 0) ?>% <?= l('funnels_analytics.from_entries') ?></div>
                            </div>

                            <div class="funnels-analytics-flow-step">
                                <div class="funnels-analytics-stat-label"><?= l('funnels_analytics.flow_successful_submits') ?></div>
                                <div class="funnels-analytics-flow-step-value"><?= nr($data->flow_summary['submit_success']) ?></div>
                                <div class="funnels-analytics-meta"><?= nr($data->flow_summary['start_to_success_rate'] ?? 0) ?>% <?= l('funnels_analytics.from_form_starts') ?></div>
                            </div>

                            <div class="funnels-analytics-flow-step">
                                <div class="funnels-analytics-stat-label"><?= l('funnels_analytics.flow_cta_clicks') ?></div>
                                <div class="funnels-analytics-flow-step-value"><?= nr($data->flow_summary['cta_clicks']) ?></div>
                                <div class="funnels-analytics-meta"><?= nr($data->flow_summary['submit_errors']) ?> <?= l('funnels_analytics.submit_errors_short') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="funnels-analytics-insight-grid align-items-start">
                    <div class="funnels-analytics-card card border-0">
                        <div class="card-body">
                            <div class="funnels-analytics-title"><?= l('funnels_analytics.top_conversion_title') ?></div>
                            <div class="funnels-analytics-help"><?= l('funnels_analytics.top_conversion_help') ?></div>

                            <?php if($data->top_conversion_funnels): ?>
                                <?php foreach($data->top_conversion_funnels as $funnel): ?>
                                    <div class="funnels-analytics-highlight funnels-analytics-list-item">
                                        <div class="min-width-0">
                                            <div class="funnels-analytics-funnel-name"><?= $funnel->name ?></div>
                                            <div class="funnels-analytics-meta"><?= $funnel->biolink_url ?></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="funnels-analytics-highlight-value"><?= nr($funnel->selected_conversion_rate) ?>%</div>
                                            <div class="funnels-analytics-meta"><?= nr($funnel->selected_unique_clicks) ?> <?= mb_strtolower(l('biolink_lead_funnel.unique_clicks')) ?> · <?= nr($funnel->selected_total_leads) ?> <?= mb_strtolower(l('biolink_lead_funnel.total_leads')) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            <?php else: ?>
                                <p class="funnels-analytics-empty"><?= l('funnels_analytics.top_conversion_empty') ?></p>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="funnels-analytics-card card border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                                <div>
                                    <div class="funnels-analytics-title"><?= l('funnels_analytics.recent_submissions') ?></div>
                                    <div class="funnels-analytics-help mb-0"><?= l('funnels_analytics.recent_submissions_help') ?></div>
                                </div>
                                <div class="funnels-analytics-muted"><?= l('funnels_analytics.recent_submissions_limit') ?></div>
                            </div>

                            <?php if($data->recent_submissions): ?>
                                <?php foreach($data->recent_submissions as $submission): ?>
                                    <div class="funnels-analytics-submission-row">
                                        <div class="funnels-analytics-submission-main">
                                            <div class="funnels-analytics-submission-title"><?= $submission->funnel_name ?></div>
                                            <div class="funnels-analytics-meta"><?= htmlspecialchars($submission->identity, ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if($submission->contact_phone): ?><div class="funnels-analytics-meta"><?= htmlspecialchars($submission->contact_phone, ENT_QUOTES, 'UTF-8') ?></div><?php endif ?>
                                            <div class="funnels-analytics-badge-wrap mt-2">
                                                <?php if($submission->preferred_contact_channel): ?>
                                                    <span class="funnels-analytics-badge"><?= mb_strtoupper($submission->preferred_contact_channel) ?></span>
                                                <?php endif ?>
                                                <span class="funnels-analytics-badge <?= $submission->contact_status === 'ready' ? 'is-ready' : 'is-review' ?>"><?= $submission->contact_status_label ?></span>
                                            </div>
                                            <div class="funnels-analytics-submission-funnel"><?= $submission->biolink_url ?></div>
                                        </div>

                                        <div class="funnels-analytics-submission-side">
                                            <div class="funnels-analytics-submission-date">
                                                <div class="funnels-analytics-submission-date-value"><?= \Altum\Date::get($submission->datetime, 2) ?></div>
                                                <div class="funnels-analytics-meta"><?= \Altum\Date::get_timeago($submission->datetime) ?></div>
                                            </div>

                                            <div class="funnels-analytics-submission-actions">
                                                <?php if($submission->primary_action): ?>
                                                    <?php
                                                    $primary_action_class = 'btn btn-sm btn-outline-light funnels-analytics-contact-action ' . $submission->primary_action['class'];
                                                    if($submission->primary_action['key'] === 'whatsapp_url') {
                                                        $primary_action_class = 'btn btn-sm btn-outline-success funnels-analytics-contact-action ' . $submission->primary_action['class'];
                                                    } elseif($submission->primary_action['key'] === 'sms_url') {
                                                        $primary_action_class = 'btn btn-sm funnels-analytics-contact-action ' . $submission->primary_action['class'];
                                                    }
                                                    ?>
                                                    <a href="<?= $submission->primary_action['url'] ?>" target="_blank" rel="noopener noreferrer" class="<?= $primary_action_class ?>">
                                                        <i class="<?= $submission->primary_action['icon'] ?>"></i>
                                                        <?= $submission->primary_action['label'] ?>
                                                    </a>
                                                <?php endif ?>
                                                <div class="funnels-analytics-submission-secondary-actions">
                                                    <?php foreach($submission->available_actions as $action): ?>
                                                        <?php if($submission->primary_action && $action['key'] === $submission->primary_action['key']) continue; ?>
                                                        <a href="<?= $action['url'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-light funnels-analytics-contact-action <?= $action['class'] ?>">
                                                            <i class="<?= $action['icon'] ?>"></i>
                                                            <?= $action['label'] ?>
                                                        </a>
                                                    <?php endforeach ?>
                                                </div>
                                                <?php if($submission->data_url): ?>
                                                    <a href="<?= $submission->data_url ?>" class="btn btn-sm btn-light"><?= l('funnels_analytics.view_data') ?></a>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach ?>

                                <?php if(!empty(trim($data->pagination))): ?>
                                    <div class="funnels-analytics-footer">
                                        <div class="funnels-analytics-muted"><?= l('funnels_analytics.recent_submissions_pagination_help') ?></div>
                                        <div><?= $data->pagination ?></div>
                                    </div>
                                <?php endif ?>
                            <?php else: ?>
                                <p class="funnels-analytics-empty"><?= l('biolink_lead_funnel.no_submissions_yet') ?></p>
                            <?php endif ?>
                        </div>
                    </div>
                </div>

                <div class="funnels-analytics-card card border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap: .75rem;">
                            <div>
                                <div class="funnels-analytics-title"><?= l('funnels_analytics.performance_table') ?></div>
                                <div class="funnels-analytics-help mb-0"><?= l('funnels_analytics.performance_table_help_simple') ?></div>
                            </div>
                            <button type="button" class="btn btn-sm funnels-analytics-outline-cta" data-toggle="modal" data-target="#funnels-all-blocks-modal">
                                <?= l('funnels_analytics.total_funnels') ?>
                            </button>
                        </div>

                        <?php foreach(array_slice($data->funnel_blocks, 0, 5) as $funnel): ?>
                            <div class="funnels-analytics-funnel-row">
                                <div class="funnels-analytics-funnel-header">
                                    <div class="funnels-analytics-funnel-summary">
                                        <div class="funnels-analytics-funnel-name"><?= $funnel->name ?></div>
                                        <div class="funnels-analytics-meta"><?= $funnel->biolink_url ?><?= $funnel->project_name ? ' • ' . $funnel->project_name : '' ?></div>
                                        <div class="mt-2 funnels-analytics-badge-wrap">
                                            <span class="funnels-analytics-badge"><?= $funnel->open_mode == 'page' ? l('biolink_lead_funnel.open_mode_page') : l('biolink_lead_funnel.open_mode_popup') ?></span>
                                            <span class="funnels-analytics-badge"><?= l('biolink_lead_funnel.thank_you_type_' . $funnel->thank_you_type) ?></span>
                                        </div>
                                    </div>

                                    <div class="funnels-analytics-actions">
                                        <a href="<?= $funnel->data_url ?>" class="btn btn-sm btn-outline-secondary"><?= l('funnels_analytics.view_data') ?></a>
                                        <a href="<?= $funnel->edit_url ?>" class="btn btn-sm btn-primary"><?= l('funnels_analytics.edit_funnel') ?></a>
                                    </div>
                                </div>

                                <div class="funnels-analytics-funnel-metrics">
                                    <div class="funnels-analytics-funnel-metric">
                                        <div class="funnels-analytics-stat-label"><?= l('biolink_lead_funnel.unique_clicks') ?></div>
                                        <div class="funnels-analytics-stat-value"><?= nr($funnel->selected_unique_clicks ?? 0) ?></div>
                                    </div>

                                    <div class="funnels-analytics-funnel-metric">
                                        <div class="funnels-analytics-stat-label"><?= l('biolink_lead_funnel.total_leads') ?></div>
                                        <div class="funnels-analytics-stat-value"><?= nr($funnel->selected_total_leads ?? 0) ?></div>
                                    </div>

                                    <div class="funnels-analytics-funnel-metric">
                                        <div class="funnels-analytics-stat-label"><?= l('biolink_lead_funnel.conversion_rate') ?></div>
                                        <div class="funnels-analytics-stat-value"><?= nr($funnel->selected_conversion_rate ?? 0) ?>%</div>
                                    </div>

                                    <div class="funnels-analytics-funnel-metric">
                                        <div class="funnels-analytics-stat-label"><?= l('biolink_lead_funnel.last_submission') ?></div>
                                        <div class="funnels-analytics-stat-value funnels-analytics-stat-value-date"><?php if($funnel->last_submission): ?><?= date('d.m.Y.', strtotime($funnel->last_submission)) ?><?php else: ?>—<?php endif ?></div>
                                        <?php if($funnel->last_submission): ?><div class="funnels-analytics-meta"><?= \Altum\Date::get_timeago($funnel->last_submission) ?></div><?php endif ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>

<?php if($data->has_funnels): ?>
    <div class="modal fade funnels-analytics-modal" id="funnels-all-blocks-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1"><?= l('funnels_analytics.total_funnels') ?></h5>
                        <div class="funnels-analytics-meta">Popis svih FCC Funnel blokova. Klik na blok vodi direktno u njegovo uređivanje.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php foreach($data->funnel_blocks as $funnel): ?>
                        <a href="<?= $funnel->edit_url ?>" class="funnels-analytics-modal-block">
                            <div class="funnels-analytics-modal-block-grid">
                                <div>
                                    <div class="funnels-analytics-modal-block-name"><?= $funnel->name ?></div>
                                    <div class="funnels-analytics-meta"><?= $funnel->biolink_url ?><?= $funnel->project_name ? ' • ' . $funnel->project_name : '' ?></div>
                                    <div class="mt-2 funnels-analytics-badge-wrap"><span class="funnels-analytics-badge"><?= $funnel->open_mode == 'page' ? l('biolink_lead_funnel.open_mode_page') : l('biolink_lead_funnel.open_mode_popup') ?></span></div>
                                </div>

                                <div>
                                    <div class="funnels-analytics-stat-label"><?= l('biolink_lead_funnel.total_clicks') ?></div>
                                    <div class="funnels-analytics-stat-value"><?= nr($funnel->total_clicks) ?></div>
                                </div>

                                <div>
                                    <div class="funnels-analytics-stat-label"><?= l('biolink_lead_funnel.unique_clicks') ?></div>
                                    <div class="funnels-analytics-stat-value"><?= nr($funnel->unique_clicks) ?></div>
                                </div>

                                <div>
                                    <div class="funnels-analytics-stat-label"><?= l('biolink_lead_funnel.total_leads') ?></div>
                                    <div class="funnels-analytics-stat-value"><?= nr($funnel->total_leads) ?></div>
                                </div>

                                <div>
                                    <div class="funnels-analytics-stat-label"><?= l('biolink_lead_funnel.conversion_rate') ?></div>
                                    <div class="funnels-analytics-stat-value"><?= $funnel->conversion_rate ?>%</div>
                                </div>

                                <div class="funnels-analytics-modal-block-arrow">
                                    <i class="fas fa-fw fa-arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade funnels-analytics-modal" id="funnels-leads-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1"><?= l('biolink_lead_funnel.total_leads') ?></h5>
                        <div class="funnels-analytics-meta">Svaki lead ima prioritetni kanal kontakta i dostupne fallback akcije za brži follow-up.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if($data->lead_contacts_preview): ?>
                        <?php foreach($data->lead_contacts_preview as $submission): ?>
                            <div class="funnels-analytics-modal-contact">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div class="pr-3">
                                        <?php if($submission->primary_action): ?>
                                            <a href="<?= $submission->primary_action['url'] ?>" target="_blank" rel="noopener noreferrer" class="funnels-analytics-contact-link"><?= htmlspecialchars($submission->contact_name, ENT_QUOTES, 'UTF-8') ?></a>
                                        <?php else: ?>
                                            <div class="font-weight-bold text-white"><?= htmlspecialchars($submission->contact_name, ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif ?>
                                        <div class="funnels-analytics-meta"><?= $submission->funnel_name ?> • <?= $submission->biolink_url ?></div>
                                        <div class="funnels-analytics-badge-wrap mt-2">
                                            <?php if($submission->preferred_contact_channel): ?>
                                                <span class="funnels-analytics-badge"><?= mb_strtoupper($submission->preferred_contact_channel) ?></span>
                                            <?php endif ?>
                                            <span class="funnels-analytics-badge <?= $submission->contact_status === 'ready' ? 'is-ready' : 'is-review' ?>"><?= $submission->contact_status_label ?></span>
                                        </div>
                                        <?php if($submission->contact_email): ?><div class="funnels-analytics-meta"><?= htmlspecialchars($submission->contact_email, ENT_QUOTES, 'UTF-8') ?></div><?php endif ?>
                                        <?php if($submission->contact_phone): ?><div class="funnels-analytics-meta"><?= htmlspecialchars($submission->contact_phone, ENT_QUOTES, 'UTF-8') ?></div><?php endif ?>
                                    </div>
                                    <div class="text-right">
                                        <div class="small text-white"><?= \Altum\Date::get($submission->datetime, 2) ?></div>
                                        <div class="funnels-analytics-meta"><?= \Altum\Date::get_timeago($submission->datetime) ?></div>
                                    </div>
                                </div>

                                <div class="funnels-analytics-modal-actions mt-3">
                                    <?php if($submission->primary_action): ?>
                                        <?php
                                        $primary_action_class = 'btn btn-sm btn-outline-light funnels-analytics-contact-action ' . $submission->primary_action['class'];
                                        if($submission->primary_action['key'] === 'whatsapp_url') {
                                            $primary_action_class = 'btn btn-sm btn-success funnels-analytics-contact-action ' . $submission->primary_action['class'];
                                        } elseif($submission->primary_action['key'] === 'sms_url') {
                                            $primary_action_class = 'btn btn-sm funnels-analytics-contact-action ' . $submission->primary_action['class'];
                                        }
                                        ?>
                                        <a href="<?= $submission->primary_action['url'] ?>" target="_blank" rel="noopener noreferrer" class="<?= $primary_action_class ?>">
                                            <i class="<?= $submission->primary_action['icon'] ?>"></i>
                                            <?= $submission->primary_action['label'] ?>
                                        </a>
                                    <?php endif ?>
                                    <?php foreach($submission->available_actions as $action): ?>
                                        <?php if($submission->primary_action && $action['key'] === $submission->primary_action['key']) continue; ?>
                                        <a href="<?= $action['url'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-light funnels-analytics-contact-action <?= $action['class'] ?>">
                                            <i class="<?= $action['icon'] ?>"></i>
                                            <?= $action['label'] ?>
                                        </a>
                                    <?php endforeach ?>
                                    <a href="<?= $submission->data_url ?>" class="btn btn-sm btn-light"><?= l('funnels_analytics.view_data') ?></a>
                                </div>
                            </div>
                        <?php endforeach ?>
                    <?php else: ?>
                        <p class="funnels-analytics-empty"><?= l('biolink_lead_funnel.no_submissions_yet') ?></p>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade funnels-analytics-modal" id="funnels-active-biolinks-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1"><?= l('funnels_analytics.active_funnels') ?></h5>
                        <div class="funnels-analytics-meta">Popis biolinkova na kojima su funneli aktivni, s brzim ulazom u editor.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if($data->active_biolinks): ?>
                        <?php foreach($data->active_biolinks as $biolink): ?>
                            <div class="funnels-analytics-biolink-row">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div class="pr-3">
                                        <div class="font-weight-bold text-white"><?= $biolink->biolink_url ?></div>
                                        <div class="funnels-analytics-meta"><?= $biolink->project_name ?: 'Bez projekta' ?></div>
                                        <div class="funnels-analytics-meta"><?= nr($biolink->active_funnels) ?> aktivnih funnel blokova • <?= nr($biolink->total_leads) ?> prijava</div>
                                    </div>
                                    <div class="funnels-analytics-actions">
                                        <a href="<?= $biolink->analytics_url ?>" class="btn btn-sm btn-outline-light">Analitika</a>
                                        <a href="<?= $biolink->editor_url ?>" class="btn btn-sm btn-primary">Otvori editor</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    <?php else: ?>
                        <p class="funnels-analytics-empty">Nema aktivnih funnel biolinkova u odabranom periodu.</p>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<?php ob_start() ?>
<link href="<?= ASSETS_FULL_URL . 'css/libraries/daterangepicker.min.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php require THEME_PATH . 'views/partials/js_chart_defaults.php' ?>

<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/moment.min.js?v=' . PRODUCT_CODE ?>"></script>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/daterangepicker.min.js?v=' . PRODUCT_CODE ?>"></script>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/moment-timezone-with-data-10-year-range.min.js?v=' . PRODUCT_CODE ?>"></script>

<script>
    'use strict';

    moment.tz.setDefault(<?= json_encode($this->user->timezone) ?>);

    $('#daterangepicker').daterangepicker({
        startDate: <?= json_encode($data->datetime['start_date']) ?>,
        endDate: <?= json_encode($data->datetime['end_date']) ?>,
        minDate: $('#daterangepicker').data('min-date'),
        maxDate: $('#daterangepicker').data('max-date'),
        ranges: {
            <?= json_encode(l('global.date.today')) ?>: [moment(), moment()],
            <?= json_encode(l('global.date.yesterday')) ?>: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            <?= json_encode(l('global.date.this_week')) ?>: [moment().startOf('week'), moment().endOf('week')],
            <?= json_encode(l('global.date.last_30_days')) ?>: [moment().subtract(29, 'days'), moment()],
            <?= json_encode(l('global.date.this_month')) ?>: [moment().startOf('month'), moment().endOf('month')],
            <?= json_encode(l('global.date.last_month')) ?>: [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            <?= json_encode(l('global.date.this_year')) ?>: [moment().startOf('year'), moment()],
            <?= json_encode(l('global.date.last_year')) ?>: [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
            <?= json_encode(l('global.date.all_time')) ?>: [moment($('#daterangepicker').data('min-date')), moment()]
        },
        alwaysShowCalendars: true,
        linkedCalendars: false,
        singleCalendar: true,
        locale: <?= json_encode(require APP_PATH . 'includes/daterangepicker_translations.php') ?>,
    }, (start, end) => {
        <?php
        parse_str(\Altum\Router::$original_request_query, $original_request_query_array);
        $modified_request_query_array = array_diff_key($original_request_query_array, ['start_date' => '', 'end_date' => '']);
        ?>
        redirect(`<?= url(\Altum\Router::$original_request . '?' . http_build_query($modified_request_query_array)) ?>&start_date=${start.format('YYYY-MM-DD')}&end_date=${end.format('YYYY-MM-DD')}`, true);
    });

    <?php if($data->summary['unique_clicks'] || $data->summary['total_leads']): ?>
    (() => {
        const chartElement = document.getElementById('funnels_analytics_chart');

        if(!chartElement) {
            return;
        }

        const chart = chartElement.getContext('2d');
        const css = window.getComputedStyle(document.body);
        const leadsColor = '#49e3cf';
        const uniqueClicksColor = css.getPropertyValue('--gray-500').trim() || '#94a3b8';
        const leadsGradient = chart.createLinearGradient(0, 0, 0, 260);
        leadsGradient.addColorStop(0, set_hex_opacity(leadsColor, 0.45));
        leadsGradient.addColorStop(1, set_hex_opacity(leadsColor, 0.05));
        const clicksGradient = chart.createLinearGradient(0, 0, 0, 260);
        clicksGradient.addColorStop(0, set_hex_opacity(uniqueClicksColor, 0.35));
        clicksGradient.addColorStop(1, set_hex_opacity(uniqueClicksColor, 0.05));

        new Chart(chart, {
            type: 'line',
            data: {
                labels: <?= $data->chart['labels'] ?>,
                datasets: [
                    {label: <?= json_encode(l('biolink_lead_funnel.chart_leads')) ?>, data: <?= $data->chart['leads'] ?? '[]' ?>, backgroundColor: leadsGradient, borderColor: leadsColor, fill: true},
                    {label: <?= json_encode(l('biolink_lead_funnel.chart_unique_clicks')) ?>, data: <?= $data->chart['unique_clicks'] ?? '[]' ?>, backgroundColor: clicksGradient, borderColor: uniqueClicksColor, fill: true}
                ]
            },
            options: {
                ...chart_options,
                plugins: {
                    ...chart_options.plugins,
                    legend: {
                        display: true,
                        labels: {
                            color: '#cbd5e1',
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                }
            }
        });
    })();
    <?php endif ?>
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
