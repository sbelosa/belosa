<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <?php if($data->website): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('websites') ?>"><?= l('websites.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li>
                    <a href="<?= url('website/' . $data->website->website_id) ?>"><?= l('website.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('subscribers_statistics.breadcrumb') ?></li>
            </ol>
        </nav>
        <?php endif ?>
    <?php endif ?>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="h4 text-truncate mb-0"><i class="fas fa-fw fa-xs fa-pager mr-1"></i> <?= $data->website ? sprintf(l('subscribers_statistics.header_dynamic'), $data->website->name) : l('subscribers_statistics.header') ?></h1>

        <div class="d-flex align-items-center col-auto p-0">
            <div>
                <button
                        id="daterangepicker"
                        type="button"
                        class="btn btn-sm btn-light"
                        data-min-date="<?= \Altum\Date::get($data->website ? $data->website->datetime : $this->user->datetime, 4) ?>"
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

            <?= $data->website ? include_view(THEME_PATH . 'views/websites/website_dropdown_button.php', ['id' => $data->website->website_id, 'resource_name' => $data->website->name, 'host' => $data->website->host, 'path' => $data->website->path, 'pixel_key' => $data->website->pixel_key, 'domain_id' => $data->website->domain_id, 'domains' => $data->domains]) : null ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'overview' ? 'active' : null ?>" href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=overview&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-list mr-1"></i>
                <?= l('subscribers_statistics.overview') ?>
            </a>
        </div>

        <div class="col-lg p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'continent_code' ? 'active' : null ?>" href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=continent_code&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-earth-europe mr-1"></i>
                <?= l('global.continents') ?>
            </a>
        </div>

        <div class="col-lg p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'country' ? 'active' : null ?>" href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=country&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-flag mr-1"></i>
                <?= l('global.countries') ?>
            </a>
        </div>

        <div class="col-lg p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'city_name' ? 'active' : null ?>" href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=city_name&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-city mr-1"></i>
                <?= l('global.cities') ?>
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'device' ? 'active' : null ?>" href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=device&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-laptop mr-1"></i>
                <?= l('subscribers_statistics.device') ?>
            </a>
        </div>

        <div class="col-lg p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'os' ? 'active' : null ?>" href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=os&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-server mr-1"></i>
                <?= l('subscribers_statistics.os') ?>
            </a>
        </div>

        <div class="col-lg p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'browser' ? 'active' : null ?>" href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=browser&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-window-restore mr-1"></i>
                <?= l('subscribers_statistics.browser') ?>
            </a>
        </div>

        <div class="col-lg p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'language' ? 'active' : null ?>" href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=language&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-language mr-1"></i>
                <?= l('subscribers_statistics.language') ?>
            </a>
        </div>

        <div class="col-lg p-1 p-lg-2 text-truncate">
            <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'subscribed_on_url' ? 'active' : null ?>" href="<?= url('subscribers-statistics?' . $data->website_url_query . '&type=subscribed_on_url&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
                <i class="fas fa-fw fa-sm fa-link mr-1"></i>
                <?= l('subscribers_statistics.subscribed_on_url') ?>
            </a>
        </div>
    </div>

    <?php if(!$data->has_data): ?>

        <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
            'filters_get' => $data->filters->get ?? [],
            'name' => 'subscribers_statistics',
            'has_secondary_text' => true,
        ]); ?>

    <?php else: ?>

        <?= $this->views['statistics'] ?>

    <?php endif ?>

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

