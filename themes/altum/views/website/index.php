<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('websites') ?>"><?= l('websites.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('website.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="card d-flex flex-row mb-4">
        <div class="pl-3 d-flex flex-column justify-content-center">
            <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-website">
                <i class="fas fa-fw fa-sm fa-pager text-website"></i>
            </div>
        </div>

        <div class="card-body text-truncate d-flex justify-content-between align-items-center">
            <div class="text-truncate">
                <h1 class="h4 text-truncate mb-0"><?= sprintf(l('website.header'), $data->website->name) ?></h1>

                <div class="d-flex align-items-center">
                    <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($data->website->host) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />

                    <span class="small text-muted text-truncate" data-toggle="tooltip" title="<?= $data->website->host . $data->website->path ?>">
                        <?= string_truncate($data->website->host . $data->website->path, 32) ?>
                    </span>
                </div>
            </div>

            <?= include_view(THEME_PATH . 'views/websites/website_dropdown_button.php', ['id' => $data->website->website_id, 'resource_name' => $data->website->name, 'host' => $data->website->host, 'path' => $data->website->path, 'pixel_key' => $data->website->pixel_key, 'domain_id' => $data->website->domain_id, 'domains' => $data->domains]) ?>
        </div>
    </div>

    <div class="my-4">
        <div class="row">
            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('websites.total_subscribers') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-subscriber">
                            <i class="fas fa-fw fa-sm fa-user-check text-subscriber"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->website->total_subscribers) ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('website.total_sent_campaigns') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-campaign">
                            <i class="fas fa-fw fa-sm fa-rocket text-campaign"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->website->total_sent_campaigns) ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.total_sent_push_notifications') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-notification">
                            <i class="fas fa-fw fa-sm fa-fire text-notification"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->website->total_sent_push_notifications) ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="row">
            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.total_displayed_push_notifications') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-displayed">
                            <i class="fas fa-fw fa-sm fa-mobile text-displayed"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->website->total_displayed_push_notifications) . '/' . nr($data->website->total_sent_push_notifications) ?>
                        <span class="text-muted">
                            <?= ' (' . nr(get_percentage_between_two_numbers($data->website->total_displayed_push_notifications, $data->website->total_sent_push_notifications)) . '%' . ')' ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.total_clicked_push_notifications') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-clicked">
                            <i class="fas fa-fw fa-sm fa-mouse text-clicked"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->website->total_clicked_push_notifications) . '/' . nr($data->website->total_displayed_push_notifications) ?>
                        <span class="text-muted">
                            <?= ' (' . nr(get_percentage_between_two_numbers($data->website->total_clicked_push_notifications, $data->website->total_displayed_push_notifications)) . '%' . ')' ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.total_closed_push_notifications') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-times text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->website->total_closed_push_notifications) . '/' . nr($data->website->total_displayed_push_notifications) ?>
                        <span class="text-muted">
                            <?= ' (' . nr(get_percentage_between_two_numbers($data->website->total_closed_push_notifications, $data->website->total_displayed_push_notifications)) . '%' . ')' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 mb-5">
        <div class="d-flex align-items-center mb-3">
            <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3"><i class="fas fa-fw fa-sm fa-rocket mr-1"></i> <?= l('campaigns.header') ?></h2>

            <div class="flex-fill">
                <hr class="border-gray-100" />
            </div>

            <div class="ml-3">
                <a href="<?= url('campaign-create?website_id=' . $data->website->website_id) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('campaigns.create') ?></a>
                <a href="<?= url('campaigns?website_id=' . $data->website->website_id) ?>" class="btn btn-sm btn-primary-100" data-toggle="tooltip" title="<?= l('global.view_all') ?>"><i class="fas fa-fw fa-rocket fa-sm"></i></a>
            </div>
        </div>

        <?php if (!empty($data->campaigns)): ?>
            <div class="table-responsive table-custom-container">
                <table class="table table-custom">
                    <thead>
                    <tr>
                        <th><?= l('campaigns.campaign') ?></th>
                        <th><?= l('campaigns.segment') ?></th>
                        <th><?= l('campaigns.notifications') ?></th>
                        <th><?= l('global.status') ?></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach($data->campaigns as $row): ?>

                        <tr>
                            <td class="text-nowrap">
                                <div>
                                    <?php if(in_array($row->status, ['draft', 'scheduled'])): ?>
                                        <a href="<?= url('campaign-update/' . $row->campaign_id) ?>"><?= $row->name ?></a>
                                    <?php elseif(in_array($row->status, ['processing', 'sent'])): ?>
                                        <a href="<?= url('campaign/' . $row->campaign_id) ?>"><?= $row->name ?></a>
                                    <?php endif ?>
                                </div>

                                <div class="d-flex align-items-center">
                                    <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($data->website->host) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />

                                    <span class="small text-muted" data-toggle="tooltip" title="<?= $data->website->host . $data->website->path ?>"><?= string_truncate($data->website->host . $data->website->path, 32) ?></span>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <?php if(is_numeric($row->segment)): ?>
                                    <a href="<?= url('segment-update/' . $row->segment) ?>" class="badge badge-light">
                                        <i class="fas fa-fw fa-sm fa-layer-group mr-1"></i> <?= l('campaigns.segment.saved') ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge badge-light">
                                        <i class="fas fa-fw fa-sm fa-layer-group mr-1"></i> <?= l('campaigns.segment.' . $row->segment) ?>
                                    </span>
                                <?php endif ?>
                            </td>

                            <td class="text-nowrap">
                                <span class="badge text-notification bg-notification" data-toggle="tooltip" title="<?= nr(get_percentage_between_two_numbers($row->total_sent_push_notifications, $row->total_push_notifications)) . '%' ?>">
                                    <i class="fas fa-fw fa-sm fa-fire mr-1"></i> <?= nr($row->total_sent_push_notifications) . '/' . nr($row->total_push_notifications) ?>
                                </span>
                            </td>

                            <td class="text-nowrap">
                                <?php if($row->status == 'draft'): ?>
                                    <span class="badge badge-light"><i class="fas fa-fw fa-sm fa-save mr-1"></i> <?= l('campaigns.status.draft') ?></span>
                                <?php elseif($row->status == 'scheduled'): ?>
                                    <span class="badge badge-gray-300" data-toggle="tooltip" title="<?= \Altum\Date::get_time_until($row->scheduled_datetime) ?>"><i class="fas fa-fw fa-sm fa-calendar-day mr-1"></i> <?= l('campaigns.status.scheduled') ?></span>
                                <?php elseif($row->status == 'processing'): ?>
                                    <span class="badge badge-warning"><i class="fas fa-fw fa-sm fa-spinner fa-spin mr-1"></i> <?= l('campaigns.status.processing') ?></span>
                                <?php elseif($row->status == 'sent'): ?>
                                    <span class="badge badge-success"><i class="fas fa-fw fa-sm fa-check mr-1"></i> <?= l('campaigns.status.sent') ?></span>
                                <?php endif ?>
                            </td>

                            <td class="text-nowrap">
                                <div class="d-flex align-items-center">
                                    <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= l('campaigns.scheduled_datetime') . ($row->scheduled_datetime && $row->settings->is_scheduled ? '<br />' . \Altum\Date::get($row->scheduled_datetime, 2) . '<br /><small>' . \Altum\Date::get($row->scheduled_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->scheduled_datetime) . ')</small>' : '<br />' . l('global.na')) ?>">
                                        <i class="fas fa-fw fa-calendar-day text-muted"></i>
                                    </span>

                                    <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= l('campaigns.last_sent_datetime') . ($row->last_sent_datetime ? '<br />' . \Altum\Date::get($row->last_sent_datetime, 2) . '<br /><small>' . \Altum\Date::get($row->last_sent_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->last_sent_datetime) . ')</small>' : '<br />' . l('global.na')) ?>">
                                        <i class="fas fa-fw fa-rocket text-muted"></i>
                                    </span>

                                    <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.datetime_tooltip'), '<br />' . \Altum\Date::get($row->datetime, 2) . '<br /><small>' . \Altum\Date::get($row->datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->datetime) . ')</small>') ?>">
                                        <i class="fas fa-fw fa-clock text-muted"></i>
                                    </span>

                                    <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.last_datetime_tooltip'), ($row->last_datetime ? '<br />' . \Altum\Date::get($row->last_datetime, 2) . '<br /><small>' . \Altum\Date::get($row->last_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->last_datetime) . ')</small>' : '<br />' . l('global.na'))) ?>">
                                        <i class="fas fa-fw fa-history text-muted"></i>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex justify-content-end">
                                    <?= include_view(THEME_PATH . 'views/campaigns/campaign_dropdown_button.php', ['id' => $row->campaign_id, 'resource_name' => $row->name,]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    </tbody>
                </table>
            </div>
        <?php else: ?>

            <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                'filters_get' => $data->filters->get ?? [],
                'name' => 'campaigns',
                'has_secondary_text' => true,
            ]); ?>

        <?php endif ?>

    </div>

    <div class="mt-4 mb-5">
        <div class="d-flex align-items-center mb-3">
            <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3"><i class="fas fa-fw fa-sm fa-user-check mr-1"></i> <?= l('subscribers.header') ?></h2>

            <div class="flex-fill">
                <hr class="border-gray-100" />
            </div>

            <div class="ml-3">
                <a href="<?= url('subscribers?website_id=' . $data->website->website_id) ?>" class="btn btn-sm btn-primary-100" data-toggle="tooltip" title="<?= l('global.view_all') ?>"><i class="fas fa-fw fa-user-check fa-sm"></i></a>
            </div>
        </div>

        <?php if (!empty($data->subscribers)): ?>
            <div class="table-responsive table-custom-container">
                <table class="table table-custom">
                    <thead>
                    <tr>
                        <th><?= l('websites.subscriber') ?></th>
                        <th><?= l('global.device') ?></th>
                        <th><?= l('campaigns.notifications') ?></th>
                        <th><?= l('global.details') ?></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach($data->subscribers as $row): ?>

                        <tr>
                            <td class="text-nowrap">
                                <div>
                                    <a href="<?= url('subscriber/' . $row->subscriber_id) ?>">
                                        <?= $row->ip ?>
                                    </a>
                                </div>

                                <div class="d-flex align-items-center">
                                    <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($data->website->host) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />

                                    <span class="small text-muted" data-toggle="tooltip" title="<?= $data->website->host . $data->website->path ?>"><?= string_truncate($data->website->host . $data->website->path, 32) ?></span>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                 <span class="badge badge-light">
                                    <?= $row->device_type ? '<i class="fas fa-fw fa-sm fa-' . $row->device_type . ' mr-1"></i>' . l('global.device.' . $row->device_type) : l('global.unknown') ?>
                                </span>
                            </td>

                            <td class="text-nowrap">
                                <span class="badge text-notification bg-notification">
                                    <i class="fas fa-fw fa-sm fa-fire mr-1"></i> <?= nr($row->total_sent_push_notifications) ?>
                                </span>
                            </td>

                            <td class="text-nowrap">
                                <div class="d-flex align-items-center">
                                    <?php if(($row->custom_parameters = json_decode($row->custom_parameters ?? '', true)) && count($row->custom_parameters)): ?>
                                        <?php ob_start() ?>
                                        <div class='d-flex flex-column text-left'>
                                            <div class='d-flex flex-column my-1'>
                                                <strong><?= sprintf(l('subscribers.custom_parameters'), count($row->custom_parameters)) ?></strong>
                                            </div>

                                            <?php foreach($row->custom_parameters as $key => $value): ?>
                                                <div class='d-flex flex-column my-1'>
                                                    <div><?= e($key) ?></div>
                                                    <strong><?= e($value) ?></strong>
                                                </div>
                                            <?php endforeach ?>
                                        </div>

                                        <?php $tooltip = ob_get_clean() ?>

                                        <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= $tooltip ?>">
                                            <i class="fas fa-fw fa-fingerprint text-primary"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('subscribers.custom_parameters'), 0) ?>">
                                            <i class="fas fa-fw fa-fingerprint text-muted"></i>
                                        </span>
                                    <?php endif ?>

                                    <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= l('campaigns.last_sent_datetime') . ($row->last_sent_datetime ? '<br />' . \Altum\Date::get($row->last_sent_datetime, 2) . '<br /><small>' . \Altum\Date::get($row->last_sent_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->last_sent_datetime) . ')</small>' : '<br />' . l('global.na')) ?>">
                                        <i class="fas fa-fw fa-rocket text-muted"></i>
                                    </span>

                                    <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.datetime_tooltip'), '<br />' . \Altum\Date::get($row->datetime, 2) . '<br /><small>' . \Altum\Date::get($row->datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->datetime) . ')</small>') ?>">
                                        <i class="fas fa-fw fa-clock text-muted"></i>
                                    </span>

                                    <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.last_datetime_tooltip'), ($row->last_datetime ? '<br />' . \Altum\Date::get($row->last_datetime, 2) . '<br /><small>' . \Altum\Date::get($row->last_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->last_datetime) . ')</small>' : '<br />' . l('global.na'))) ?>">
                                        <i class="fas fa-fw fa-history text-muted"></i>
                                    </span>

                                    <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= l('subscribers.subscribed_on_url') . '<br />' . $row->subscribed_on_url ?>">
                                        <i class="fas fa-fw fa-link text-muted"></i>
                                    </span>

                                    <span class="mr-2" data-toggle="tooltip" title="<?= get_continent_from_continent_code($row->continent_code ?? l('global.unknown')) ?>">
                                        <i class="fas fa-fw fa-globe-europe text-muted"></i>
                                    </span>

                                    <?php if($row->country_code): ?>
                                        <img src="<?= ASSETS_FULL_URL . 'images/countries/' . mb_strtolower($row->country_code) . '.svg' ?>" class="icon-favicon mr-2" data-toggle="tooltip" title="<?= get_country_from_country_code($row->country_code) ?>" />
                                    <?php else: ?>
                                        <span class="mr-2" data-toggle="tooltip" title="<?= l('global.unknown') ?>">
                                            <i class="fas fa-fw fa-flag text-muted"></i>
                                        </span>
                                    <?php endif ?>

                                    <span class="mr-2" data-toggle="tooltip" title="<?= $row->city_name ?? l('global.unknown') ?>">
                                        <i class="fas fa-fw fa-city text-muted"></i>
                                    </span>

                                    <img src="<?= ASSETS_FULL_URL . 'images/os/' . os_name_to_os_key($row->os_name) . '.svg' ?>" class="img-fluid icon-favicon mr-2" data-toggle="tooltip" title="<?= $row->os_name ?: l('global.unknown') ?>" />

                                    <img src="<?= ASSETS_FULL_URL . 'images/browsers/' . browser_name_to_browser_key($row->browser_name) . '.svg' ?>" class="img-fluid icon-favicon mr-2" data-toggle="tooltip" title="<?= $row->browser_name ?: l('global.unknown') ?>" />
                                </div>
                            </td>

                            <td>
                                <div class="d-flex justify-content-end">
                                    <?= include_view(THEME_PATH . 'views/subscribers/subscriber_dropdown_button.php', ['id' => $row->subscriber_id, 'resource_name' => $row->ip, 'website_id' => $row->website_id]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    </tbody>
                </table>
            </div>
        <?php else: ?>

            <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                'filters_get' => $data->filters->get ?? [],
                'name' => 'websites',
                'has_secondary_text' => true,
            ]); ?>

        <?php endif ?>

    </div>

    <div class="mt-4 mb-5">
        <div class="d-flex align-items-center mb-3">
            <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3"><i class="fas fa-fw fa-sm fa-stream mr-1"></i> <?= l('subscriber.logs') ?></h2>

            <div class="flex-fill">
                <hr class="border-gray-100" />
            </div>

            <div class="ml-3">
                <a href="<?= url('subscribers-logs?website_id=' . $data->website->website_id) ?>" class="btn btn-sm btn-primary-100" data-toggle="tooltip" title="<?= l('global.view_all') ?>"><i class="fas fa-fw fa-stream fa-sm"></i></a>
            </div>
        </div>

        <?php if (!empty($data->subscriber_logs)): ?>
            <div class="table-responsive table-custom-container">
                <table class="table table-custom">
                    <thead>
                    <tr>
                        <th><?= l('global.ip') ?></th>
                        <th><?= l('global.type') ?></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach($data->subscriber_logs as $row): ?>

                        <tr>
                            <td class="text-nowrap">
                                <div>
                                    <?php if($row->subscriber_id): ?>
                                        <a href="<?= url('subscriber/' . $row->subscriber_id) ?>">
                                            <?= $row->ip ?>
                                        </a>
                                    <?php else: ?>
                                        <?= $row->ip ?>
                                    <?php endif ?>
                                </div>

                                <div class="d-flex align-items-center">
                                    <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($data->website->host) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />

                                    <span class="small text-muted" data-toggle="tooltip" title="<?= $data->website->host . $data->website->path ?>"><?= string_truncate($data->website->host . $data->website->path, 32) ?></span>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <?= display_subscriber_log_type($row->type, $row->error) ?>
                            </td>

                            <td class="text-nowrap">
                                <span class="text-muted" data-toggle="tooltip" title="<?= \Altum\Date::get($row->datetime, 1) ?>">
                                    <?= \Altum\Date::get_timeago($row->datetime) ?>
                                </span>
                            </td>

                            <td class="text-nowrap text-muted">
                                <a href="<?= url('campaign/' . $row->campaign_id) ?>" class="mr-2 <?= $row->campaign_id ? null : 'container-disabled' ?>" data-toggle="tooltip" title="<?= l('campaign.menu') ?>">
                                    <i class="fas fa-fw fa-rocket text-muted"></i>
                                </a>
                                <a href="<?= url('flow/' . $row->flow_id) ?>" class="mr-2 <?= $row->flow_id ? null : 'container-disabled' ?>" data-toggle="tooltip" title="<?= l('flow.menu') ?>">
                                    <i class="fas fa-fw fa-tasks text-muted"></i>
                                </a>
                                <a href="<?= url('personal-notification-update/' . $row->personal_notification_id) ?>" class="mr-2 <?= $row->personal_notification_id ? null : 'container-disabled' ?>" data-toggle="tooltip" title="<?= l('personal_notifications.personal_notification') ?>">
                                    <i class="fas fa-fw fa-code-branch text-muted"></i>
                                </a>
                                <a href="<?= url('rss-automation/' . $row->rss_automation_id) ?>" class="mr-2 <?= $row->rss_automation_id ? null : 'container-disabled' ?>" data-toggle="tooltip" title="<?= l('rss_automations.rss_automation') ?>">
                                    <i class="fas fa-fw fa-rss text-muted"></i>
                                </a>
                            </td>

                            <td>
                                <div class="d-flex justify-content-end">
                                    <?= include_view(THEME_PATH . 'views/subscribers-logs/subscriber_log_dropdown_button.php', ['id' => $row->subscriber_log_id, 'resource_name' => $row->ip]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    </tbody>
                </table>
            </div>
        <?php else: ?>

            <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                'filters_get' => $data->filters->get ?? [],
                'name' => 'subscribers_logs',
                'has_secondary_text' => true,
            ]); ?>

        <?php endif ?>
    </div>

    <div class="mt-4 mb-5">
        <div class="d-flex align-items-center mb-3">
            <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3"><i class="fas fa-fw fa-sm fa-laptop-code mr-1"></i> <?= l('website.advanced') ?></h2>

            <div class="flex-fill">
                <hr class="border-gray-100" />
            </div>
        </div>

        <div class="row">
            <div class="col-12 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-fingerprint text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <div class="font-weight-bold text-muted small"><?= l('website.website_id') ?></div>
                        <span><?= $data->website->website_id ?></span>
                    </div>

                    <div class="pr-3 d-flex flex-column justify-content-center">
                        <button
                                type="button"
                                class="btn btn-light p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center"
                                data-toggle="tooltip"
                                title="<?= l('global.clipboard_copy') ?>"
                                aria-label="<?= l('global.clipboard_copy') ?>"
                                data-copy="<?= l('global.clipboard_copy') ?>"
                                data-copied="<?= l('global.clipboard_copied') ?>"
                                data-clipboard-text="<?= $data->website->website_id ?>"
                        >
                            <i class="fas fa-fw fa-sm fa-copy text-muted"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-code text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <div class="font-weight-bold text-muted small"><?= l('website.public_key') ?></div>
                        <span><?= $data->website->keys->public_key ?></span>
                    </div>

                    <div class="pr-3 d-flex flex-column justify-content-center">
                        <button
                                type="button"
                                class="btn btn-light p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center"
                                data-toggle="tooltip"
                                title="<?= l('global.clipboard_copy') ?>"
                                aria-label="<?= l('global.clipboard_copy') ?>"
                                data-copy="<?= l('global.clipboard_copy') ?>"
                                data-copied="<?= l('global.clipboard_copied') ?>"
                                data-clipboard-text="<?= $data->website->keys->public_key ?>"
                        >
                            <i class="fas fa-fw fa-sm fa-copy text-muted"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-lock text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <div class="font-weight-bold text-muted small"><?= l('website.private_key') ?></div>
                        <span><?= $data->website->keys->private_key ?></span>
                    </div>

                    <div class="pr-3 d-flex flex-column justify-content-center">
                        <button
                                type="button"
                                class="btn btn-light p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center"
                                data-toggle="tooltip"
                                title="<?= l('global.clipboard_copy') ?>"
                                aria-label="<?= l('global.clipboard_copy') ?>"
                                data-copy="<?= l('global.clipboard_copy') ?>"
                                data-copied="<?= l('global.clipboard_copied') ?>"
                                data-clipboard-text="<?= $data->website->keys->private_key ?>"
                        >
                            <i class="fas fa-fw fa-sm fa-copy text-muted"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>

<?php ob_start() ?>
<script>
    'use strict';
    
    <?php if(isset($_GET['install'])): ?>
    /* Open the pixel key modal */
    $('[data-target="#website_install_code_modal"]').trigger('click');
    <?php endif ?>
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
