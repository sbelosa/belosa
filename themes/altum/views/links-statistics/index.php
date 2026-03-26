<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <style>
        .fcc-stats-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .fcc-stats-header {
            background: linear-gradient(180deg, rgba(19, 27, 29, 0.92) 0%, rgba(15, 21, 23, 0.98) 100%);
            border: 1px solid rgba(127, 227, 217, 0.08);
            border-radius: 22px;
            padding: 1.35rem 1.4rem;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
        }

        .fcc-stats-heading {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }

        .fcc-stats-heading-icon {
            width: 2.85rem;
            height: 2.85rem;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(191, 246, 239, 0.18) 0%, rgba(142, 233, 222, 0.3) 100%);
            color: #9ef1e7;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
            flex-shrink: 0;
        }

        .fcc-stats-heading-copy {
            min-width: 0;
        }

        .fcc-stats-heading-copy h1 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #f5fbfb;
        }

        .fcc-stats-toolbar {
            gap: 0.75rem;
        }

        .fcc-stats-action-btn {
            border-radius: 14px;
            min-height: 2.75rem;
            font-weight: 600;
            box-shadow: none !important;
        }

        .fcc-stats-action-btn.btn-light,
        .fcc-stats-action-btn.btn-link {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            color: #d5e1e2;
        }

        .fcc-stats-action-btn.btn-light:hover,
        .fcc-stats-action-btn.btn-link:hover {
            background: rgba(127, 227, 217, 0.1);
            border-color: rgba(127, 227, 217, 0.16);
            color: #f5fbfb;
            text-decoration: none;
        }

        .fcc-stats-tabs {
            gap: 0.3rem 0;
            margin-bottom: 0;
        }

        .fcc-stats-tabs .btn-custom {
            border-radius: 14px;
            min-height: 2.8rem;
            border: 1px solid rgba(127, 227, 217, 0.08);
            background: rgba(255, 255, 255, 0.02);
            color: #b7c7c8;
            font-weight: 600;
            box-shadow: none;
            transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .fcc-stats-tabs .btn-custom:hover {
            background: rgba(127, 227, 217, 0.08);
            border-color: rgba(127, 227, 217, 0.18);
            color: #f2fcfb;
            transform: translateY(-1px);
        }

        .fcc-stats-tabs .btn-custom.active {
            background: linear-gradient(135deg, #bff6ef 0%, #8ee9de 100%);
            color: #082826;
            border-color: rgba(191, 246, 239, 0.72);
            box-shadow: 0 12px 28px rgba(73, 190, 177, 0.22);
        }

        .fcc-stats-content {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .fcc-stats-content .card,
        .fcc-stats-empty {
            background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
            border: 1px solid rgba(127, 227, 217, 0.07);
            border-radius: 22px;
            box-shadow: 0 20px 44px rgba(0, 0, 0, 0.16);
        }

        .fcc-stats-content .card-body {
            padding: 1.2rem 1.25rem;
        }

        .fcc-stats-content .card .card-body + .card-body {
            border-top: 1px solid rgba(255, 255, 255, 0.04);
        }

        .fcc-stats-content .index-widget-icon {
            background: rgba(127, 227, 217, 0.1) !important;
            color: #9ef1e7;
            border: 1px solid rgba(127, 227, 217, 0.12);
        }

        .fcc-stats-content h2,
        .fcc-stats-content h3,
        .fcc-stats-content .h5,
        .fcc-stats-content .h6 {
            color: #edf9f7;
        }

        .fcc-stats-content .text-muted,
        .fcc-stats-content small {
            color: #8ea4a6 !important;
        }

        .fcc-stats-content .progress {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 999px;
        }

        .fcc-stats-content .progress-bar {
            background: linear-gradient(90deg, #40d8c8 0%, #78ece0 100%);
            border-radius: 999px;
        }

        .fcc-stats-content .chart-container {
            padding: 0.25rem;
        }

        .fcc-stats-content .table-custom-container {
            border-radius: 22px;
            border: 1px solid rgba(127, 227, 217, 0.07);
            background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
            overflow: hidden;
            box-shadow: 0 20px 44px rgba(0, 0, 0, 0.16);
        }

        .fcc-stats-content .table-custom thead th {
            background: rgba(255, 255, 255, 0.02);
            color: #e9f7f5;
            border-bottom: 1px solid rgba(127, 227, 217, 0.08);
        }

        .fcc-stats-content .table-custom td {
            background: transparent;
            color: #dce7e8;
            border-top-color: rgba(255, 255, 255, 0.04);
        }

        .fcc-stats-content .table-custom tbody tr:hover {
            background: rgba(127, 227, 217, 0.035);
        }

        .fcc-stats-content .badge.badge-light {
            background: rgba(255, 255, 255, 0.08);
            color: #d6e5e4;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .fcc-stats-content a:not(.btn):not(.dropdown-item) {
            color: #abf5ec;
        }

        .fcc-stats-content a.text-muted:not(.btn):not(.dropdown-item) {
            color: #8ea4a6 !important;
        }
    </style>

    <div class="fcc-stats-shell">

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('links') ?>"><?= l('links.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('links_statistics.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="fcc-stats-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap fcc-stats-toolbar">
        <div class="fcc-stats-heading mb-3 mb-lg-0">
            <div class="fcc-stats-heading-icon">
                <i class="fas fa-fw fa-link"></i>
            </div>

            <div class="fcc-stats-heading-copy">
                <h1 class="text-truncate"><?= l('links_statistics.header') ?></h1>
            </div>
        </div>

        <div class="d-flex align-items-center col-auto p-0 fcc-stats-toolbar">
            <div data-toggle="tooltip" title="<?= l('statistics_reset_modal.header') ?>">
                <button
                        type="button"
                        class="btn btn-link text-secondary fcc-stats-action-btn"
                        data-toggle="modal"
                        data-target="#link_statistics_reset_modal"
                        aria-label="<?= l('statistics_reset_modal.header') ?>"
                        data-start-date="<?= $data->datetime['start_date'] ?>"
                        data-end-date="<?= $data->datetime['end_date'] ?>"
                        data-user-id="<?= user()->user_id ?>"
                        data-project-id="<?= isset($_GET['project_id']) ? (int) $_GET['project_id'] : null ?>"
                >
                    <i class="fas fa-fw fa-sm fa-eraser"></i>
                </button>
            </div>

            <div>
                <button
                        id="daterangepicker"
                        type="button"
                        class="btn btn-sm btn-light fcc-stats-action-btn"
                        data-min-date="<?= \Altum\Date::get($this->user->datetime, 4) ?>"
                        data-max-date="<?= \Altum\Date::get('', 4) ?>"
                >
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
    </div>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 mx-lg-n2 fcc-stats-tabs">
        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'overview' ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=overview&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-list mr-1"></i>
                <?= l('link.statistics.overview') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'entries' ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=entries&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-chart-bar mr-1"></i>
                <?= l('link.statistics.entries') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'continent_code' ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=continent_code&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-globe-europe mr-1"></i>
                <?= l('global.continent') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'country' ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=country&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-flag mr-1"></i>
                <?= l('global.countries') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'city_name' ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=city_name&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-city mr-1"></i>
                <?= l('global.cities') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= in_array($data->type, ['referrer_host', 'referrer_path']) ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=referrer_host&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-random mr-1"></i>
                <?= l('link.statistics.referrer_host') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'device' ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=device&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-laptop mr-1"></i>
                <?= l('link.statistics.device') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'os' ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=os&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-server mr-1"></i>
                <?= l('link.statistics.os') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'browser' ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=browser&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-window-restore mr-1"></i>
                <?= l('link.statistics.browser') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'language' ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=language&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-language mr-1"></i>
                <?= l('link.statistics.language') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= in_array($data->type, ['utm_source', 'utm_medium', 'utm_campaign']) ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=utm_source&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-link mr-1"></i>
                <?= l('link.statistics.utms') ?>
            </a>
        </div>

        <div class="p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'hour' ? 'active' : null ?>" href="<?= url('links-statistics?' . $data->filters->get_get() . '&type=hour&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-clock mr-1"></i>
                <?= l('link.statistics.hour') ?>
            </a>
        </div>
    </div>

    <div class="fcc-stats-content">

    <?php if(!$data->has_data): ?>

        <div class="fcc-stats-empty">
        <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
            'filters_get' => $data->filters->get ?? [],
            'name' => 'link.statistics',
            'has_secondary_text' => true,
        ]); ?>
        </div>

    <?php else: ?>

        <?= $this->views['statistics'] ?>

    <?php endif ?>
    </div>
    </div>

    <?php ob_start() ?>
    <link href="<?= ASSETS_FULL_URL . 'css/libraries/daterangepicker.min.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
    <?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

    <?php ob_start() ?>
    <script src="<?= ASSETS_FULL_URL . 'js/libraries/moment.min.js?v=' . PRODUCT_CODE ?>"></script>
    <script src="<?= ASSETS_FULL_URL . 'js/libraries/daterangepicker.min.js?v=' . PRODUCT_CODE ?>"></script>
    <script src="<?= ASSETS_FULL_URL . 'js/libraries/moment-timezone-with-data-10-year-range.min.js?v=' . PRODUCT_CODE ?>"></script>

    <script>
        'use strict';

        moment.tz.setDefault(<?= json_encode($this->user->timezone) ?>);

        /* Daterangepicker */
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
        }, (start, end, label) => {

            <?php
            parse_str(\Altum\Router::$original_request_query, $original_request_query_array);
            $modified_request_query_array = array_diff_key($original_request_query_array, ['start_date' => '', 'end_date' => '']);
            ?>

            /* Redirect */
            redirect(`<?= url(\Altum\Router::$original_request . '?' . http_build_query($modified_request_query_array)) ?>&start_date=${start.format('YYYY-MM-DD')}&end_date=${end.format('YYYY-MM-DD')}`, true);

        });
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
</div>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/links/link_delete_modal.php'), 'modals'); ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/statistics_reset_modal.php', ['modal_id' => 'link_statistics_reset_modal', 'resource_id' => isset($_GET['project_id']) ? 'project_id' : 'user_id', 'path' => 'links-statistics/reset']), 'modals'); ?>
