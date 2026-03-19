<?php defined('ALTUMCODE') || die() ?>

<?php $base_url = url('admin/broadcast-view/' . $data->broadcast->broadcast_id) ?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4">
    <div>
        <h1 class="h3 mb-2"><i class="fas fa-fw fa-xs fa-mail-bulk text-primary-900 mr-2"></i> <?= e($data->broadcast->name) ?></h1>
        <p class="text-muted mb-0">Pregled isporuke, otvaranja, klikova i liste primatelja za ovaj mail.</p>
    </div>

    <div class="d-flex align-items-center mt-3 mt-lg-0">
        <div>
            <button id="daterangepicker" type="button" class="btn btn-sm btn-light" data-min-date="<?= \Altum\Date::get($data->broadcast->datetime, 4) ?>" data-max-date="<?= \Altum\Date::get('', 4) ?>">
                <i class="fas fa-fw fa-calendar mr-lg-1"></i>
                <span class="d-none d-lg-inline-block"><?php if($data->datetime['start_date'] == $data->datetime['end_date']): ?><?= \Altum\Date::get($data->datetime['start_date'], 6, \Altum\Date::$default_timezone) ?><?php else: ?><?= \Altum\Date::get($data->datetime['start_date'], 6, \Altum\Date::$default_timezone) . ' - ' . \Altum\Date::get($data->datetime['end_date'], 6, \Altum\Date::$default_timezone) ?><?php endif ?></span>
                <i class="fas fa-fw fa-caret-down d-none d-lg-inline-block ml-lg-1"></i>
            </button>
        </div>

        <div class="ml-3">
            <?= include_view(THEME_PATH . 'views/admin/broadcasts/admin_broadcast_dropdown_button.php', ['id' => $data->broadcast->broadcast_id, 'resource_name' => $data->broadcast->name]) ?>
        </div>
    </div>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<?php if(!empty($data->needs_webhook_attention)): ?>
    <div class="alert alert-warning mb-4">
        <div class="font-weight-bold mb-1"><?= l('admin_settings.smtp.brevo_webhook_warning_title') ?></div>
        <div><?= l('admin_settings.smtp.brevo_webhook_warning_body') ?></div>
    </div>
<?php endif ?>

<div class="row mb-4">
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Status</div><div class="h5 mb-0"><?php if($data->broadcast->status == 'draft'): ?>Draft<?php elseif($data->broadcast->status == 'processing'): ?>Processing<?php else: ?>Sent<?php endif ?></div></div></div></div>
    <?php foreach(['sent' => 'Poslano', 'delivered' => 'Isporučeno', 'opened' => 'Otvoreno', 'clicked' => 'Kliknuto', 'bounced' => 'Bounce', 'unsubscribed' => 'Odjavljeno'] as $status_key => $status_label): ?>
        <div class="col-6 col-xl-2 mb-3">
            <a href="<?= $base_url . '?start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date'] . '&status_filter=' . $status_key ?>" class="card h-100 text-decoration-none <?= $data->status_filter === $status_key ? 'border-primary' : '' ?>">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-1"><?= $status_label ?></div>
                    <div class="h3 mb-0 text-body"><?= nr($data->analytics['summary'][$status_key]) ?></div>
                </div>
            </a>
        </div>
    <?php endforeach ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-lg-4 mb-3 mb-lg-0">
                <div class="small text-uppercase text-muted mb-2">Osnovno</div>
                <div class="mb-2"><strong>Naziv:</strong> <?= e($data->broadcast->name) ?></div>
                <div class="mb-2"><strong>Predmet:</strong> <?= e($data->broadcast->subject) ?></div>
                <div class="mb-2"><strong>Skupina:</strong> <?= l('admin_broadcasts.segment.' . $data->broadcast->segment) ?></div>
                <div class="mb-2"><strong>Poslano:</strong> <?= nr($data->broadcast->sent_emails) ?> / <?= nr($data->broadcast->total_emails) ?></div>
            </div>
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-md-3 col-6 mb-3"><div class="bg-gray-100 rounded p-3 h-100"><div class="small text-uppercase text-muted mb-1">Delivery rate</div><div class="h4 mb-0"><?= $data->analytics['rates']['delivery_rate'] ?>%</div></div></div>
                    <div class="col-md-3 col-6 mb-3"><div class="bg-gray-100 rounded p-3 h-100"><div class="small text-uppercase text-muted mb-1">Open rate</div><div class="h4 mb-0"><?= $data->analytics['rates']['open_rate'] ?>%</div></div></div>
                    <div class="col-md-3 col-6 mb-3"><div class="bg-gray-100 rounded p-3 h-100"><div class="small text-uppercase text-muted mb-1">Click rate</div><div class="h4 mb-0"><?= $data->analytics['rates']['click_rate'] ?>%</div></div></div>
                    <div class="col-md-3 col-6 mb-3"><div class="bg-gray-100 rounded p-3 h-100"><div class="small text-uppercase text-muted mb-1">CTOR</div><div class="h4 mb-0"><?= $data->analytics['rates']['click_to_open_rate'] ?>%</div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Primatelji</h2>
            <div class="small text-muted">Prikazano zadnjih <?= nr($data->messages_display_limit) ?> zapisa<?php if(($data->filtered_messages_total ?? 0) > $data->messages_display_limit): ?> od ukupno <?= nr($data->filtered_messages_total) ?><?php endif ?> • Filter: <?= e($data->status_filter) ?></div>
        </div>
        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
                <thead>
                <tr>
                    <th>Korisnik</th>
                    <th>Status</th>
                    <th>Poslano</th>
                    <th>Isporučeno</th>
                    <th>Otvoreno</th>
                    <th>Kliknuto</th>
                    <th>Odjavljeno</th>
                    <th>Poruka</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($data->filtered_messages as $message): ?>
                    <tr>
                        <td class="text-nowrap"><?php if($message->user): ?><div class="font-weight-bold"><?= e($message->user->name ?: ('#' . $message->user_id)) ?></div><div class="small text-muted"><?= e($message->user->email) ?></div><?php else: ?><div class="font-weight-bold">#<?= (int) $message->user_id ?></div><div class="small text-muted"><?= e($message->recipient_email) ?></div><?php endif ?></td>
                        <td class="text-nowrap"><span class="badge badge-light"><?= e(str_replace('_', ' ', $message->status)) ?></span></td>
                        <td class="text-nowrap"><?= \Altum\Date::get($message->sent_datetime, 2) ?></td>
                        <td class="text-nowrap"><?= $message->delivered_datetime ? \Altum\Date::get($message->delivered_datetime, 2) : '-' ?></td>
                        <td class="text-nowrap"><?= $message->first_open_datetime ? \Altum\Date::get($message->first_open_datetime, 2) : '-' ?></td>
                        <td class="text-nowrap"><?= $message->first_click_datetime ? \Altum\Date::get($message->first_click_datetime, 2) : '-' ?></td>
                        <td class="text-nowrap"><?= $message->unsubscribe_datetime ? \Altum\Date::get($message->unsubscribe_datetime, 2) : '-' ?></td>
                        <td class="text-nowrap small"><?php if($message->brevo_message_id): ?><code data-copy><?= e($message->brevo_message_id) ?></code><?php else: ?>-<?php endif ?></td>
                    </tr>
                <?php endforeach ?>
                <?php if(empty($data->filtered_messages)): ?><tr><td colspan="8" class="text-center text-muted py-4">Nema primatelja za odabrani filter.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-5">
    <div class="card-body">
        <div class="chart-container <?= !$data->statistics_chart['is_empty'] ? null : 'd-none' ?>">
            <canvas id="statistics"></canvas>
        </div>
        <?= !$data->statistics_chart['is_empty'] ? null : include_view(THEME_PATH . 'views/partials/no_chart_data.php', ['has_wrapper' => false]); ?>
    </div>
</div>

<div class="row">
    <?php if(settings()->content->broadcasts_statistics_is_enabled): ?>
        <div class="col-xl-6 mb-5">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5 mb-4"><?= l('admin_broadcasts.latest_views') ?></h2>

                    <?php if (!empty($data->users)): ?>

                        <div>
                            <?php foreach($data->users as $user): ?>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="d-flex">
                                        <a href="<?= url('admin/user-view/' . $user->user_id) ?>">
                                            <img src="<?= get_user_avatar($user->avatar, $user->email) ?>" class="user-avatar rounded-circle mr-3" alt="" />
                                        </a>

                                        <div class="d-flex flex-column">
                                            <div>
                                                <a href="<?= url('admin/user-view/' . $user->user_id) ?>"><?= $user->name ?></a>
                                            </div>

                                            <span class="small text-muted"><?= $user->email ?></span>
                                        </div>
                                    </div>

                                    <div>
                                        <span class="text-muted" data-toggle="tooltip" title="<?= \Altum\Date::get($user->datetime, 1) ?>"><?= \Altum\Date::get_timeago($user->datetime) ?></span>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>

                    <?php else: ?>

                        <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                            'filters_get' => $data->filters->get ?? [],
                            'name' => 'global',
                            'has_secondary_text' => false,
                            'has_wrapper' => false,
                        ]); ?>

                    <?php endif ?>
                </div>
            </div>
        </div>
    <?php endif ?>
</div>


<?php if(settings()->content->broadcasts_statistics_is_enabled): ?>
    <div class="card h-100">
        <div class="card-body">
            <h2 class="h5 mb-4"><?= l('admin_broadcasts.clicks') ?></h2>

            <?php if (!empty($data->clicks)): ?>

                <div>
                    <?php foreach($data->clicks as $click): ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($click->target) ?>" class="img-fluid icon-favicon mr-1" loading="lazy" />

                                <a href="<?= $click->target ?>" target="_blank" rel="noreferrer">
                                    <?= remove_url_protocol_from_url($click->target) ?>
                                </a>
                            </div>

                            <div>
                                <span class="text-muted"><?= nr($click->clicks) ?></span>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>

            <?php else: ?>

                <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                    'filters_get' => $data->filters->get ?? [],
                    'name' => 'global',
                    'has_secondary_text' => false,
                    'has_wrapper' => false,
                ]); ?>

            <?php endif ?>
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
    'use strict'

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
            <?= json_encode(l('global.date.all_time')) ?>: [moment('2015-01-01'), moment()]
        },
        alwaysShowCalendars: true,
        linkedCalendars: false,
        singleCalendar: true,
        locale: <?= json_encode(require APP_PATH . 'includes/daterangepicker_translations.php') ?>,
    }, (start, end, label) => {

        /* Redirect */
        redirect(`<?= url('admin/broadcast-view/' . $data->broadcast->broadcast_id) ?>?start_date=${start.format('YYYY-MM-DD')}&end_date=${end.format('YYYY-MM-DD')}`, true);

    });

    /* Chart */
    let css = window.getComputedStyle(document.body)
    let views_color = css.getPropertyValue('--primary');
    let clicks_color = css.getPropertyValue('--gray-500');
    let views_color_gradient = null;
    let clicks_color_gradient = null;

    /* Display chart */
    let statistics_chart = document.getElementById('statistics').getContext('2d');

    views_color_gradient = statistics_chart.createLinearGradient(0, 0, 0, 250);
    views_color_gradient.addColorStop(0, set_hex_opacity(views_color, 0.1));
    views_color_gradient.addColorStop(1, set_hex_opacity(views_color, 0.025));

    clicks_color_gradient = statistics_chart.createLinearGradient(0, 0, 0, 250);
    clicks_color_gradient.addColorStop(0, set_hex_opacity(clicks_color, 0.1));
    clicks_color_gradient.addColorStop(1, set_hex_opacity(clicks_color, 0.025));

    new Chart(statistics_chart, {
        type: 'line',
        data: {
            labels: <?= $data->statistics_chart['labels'] ?>,
            datasets: [
                {
                    label: <?= json_encode(l('admin_broadcasts.views')) ?>,
                    data: <?= $data->statistics_chart['views'] ?? '[]' ?>,
                    backgroundColor: views_color_gradient,
                    borderColor: views_color,
                    fill: true
                },

                {
                    label: <?= json_encode(l('admin_broadcasts.clicks')) ?>,
                    data: <?= $data->statistics_chart['clicks'] ?? '[]' ?>,
                    backgroundColor: clicks_color_gradient,
                    borderColor: clicks_color,
                    fill: true
                }
            ]
        },
        options: chart_options
    });
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>



