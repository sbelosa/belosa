<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('subscribers') ?>"><?= l('subscribers.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('subscriber.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="card d-flex flex-row mb-4">
        <div class="pl-3 d-flex flex-column justify-content-center">
            <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-subscriber">
                <i class="fas fa-fw fa-sm fa-user-check text-subscriber"></i>
            </div>
        </div>

        <div class="card-body text-truncate d-flex justify-content-between align-items-center">
            <div class="text-truncate">
                <h1 class="h4 text-truncate mb-0"><?= sprintf(l('subscriber.header'), $data->subscriber->ip) ?></h1>

                <div class="d-flex align-items-center">
                    <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($data->website->host) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />

                    <a href="<?= url('website/' . $data->website->website_id) ?>" class="small text-muted text-truncate">
                        <?= $data->website->host . $data->website->path ?>
                    </a>
                </div>
            </div>

            <?= include_view(THEME_PATH . 'views/subscribers/subscriber_dropdown_button.php', ['id' => $data->subscriber->subscriber_id, 'resource_name' => $data->subscriber->ip, 'website_id' => $data->subscriber->website_id]) ?>
        </div>
    </div>

    <div class="my-4">
        <div class="row">
            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true" title="<?= l('campaigns.last_sent_datetime') . ($data->subscriber->last_sent_datetime ? '<br />' . \Altum\Date::get($data->subscriber->last_sent_datetime, 2) . '<br /><small>' . \Altum\Date::get($data->subscriber->last_sent_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($data->subscriber->last_sent_datetime) . ')</small>' : '<br />' . l('global.na')) ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-rocket text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= $data->subscriber->last_sent_datetime ? \Altum\Date::get_timeago($data->subscriber->last_sent_datetime) : l('global.na') ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.datetime_tooltip'), '<br />' . \Altum\Date::get($data->subscriber->datetime, 2) . '<br /><small>' . \Altum\Date::get($data->subscriber->datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($data->subscriber->datetime) . ')</small>') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-clock text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= $data->subscriber->datetime ? \Altum\Date::get_timeago($data->subscriber->datetime) : l('global.na') ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.last_datetime_tooltip'), ($data->subscriber->last_datetime ? '<br />' . \Altum\Date::get($data->subscriber->last_datetime, 2) . '<br /><small>' . \Altum\Date::get($data->subscriber->last_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($data->subscriber->last_datetime) . ')</small>' : '<br />' . l('global.na'))) ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-clock-rotate-left text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= $data->subscriber->last_datetime ? \Altum\Date::get_timeago($data->subscriber->last_datetime) : l('global.na') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('global.device') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-<?= $data->subscriber->device_type ?? 'desktop' ?> text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= $data->subscriber->device_type ? l('global.device.' . $data->subscriber->device_type) : l('global.unknown') ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('global.os_name') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-server text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <img src="<?= ASSETS_FULL_URL . 'images/os/' . os_name_to_os_key($data->subscriber->os_name) . '.svg' ?>" class="img-fluid icon-favicon-small mr-2" />
                        <?= $data->subscriber->os_name ?: l('global.unknown') ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('global.browser_name') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-window-restore text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <img src="<?= ASSETS_FULL_URL . 'images/browsers/' . browser_name_to_browser_key($data->subscriber->browser_name) . '.svg' ?>" class="img-fluid icon-favicon-small mr-2" />
                        <?= $data->subscriber->browser_name ?: l('global.unknown') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('global.continent') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-globe-europe text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= $data->subscriber->continent_code ? get_continent_from_continent_code($data->subscriber->continent_code) : l('global.unknown') ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('global.country') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-flag text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <img src="<?= ASSETS_FULL_URL . 'images/countries/' . mb_strtolower($data->subscriber->country_code) . '.svg' ?>" class="img-fluid icon-favicon-small mr-2" />
                        <?= $data->subscriber->country_code ? get_country_from_country_code($data->subscriber->country_code) : l('global.unknown') ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('global.city') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-city text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= $data->subscriber->city_name ?: l('global.unknown') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-6 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.total_sent_push_notifications') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-notification">
                            <i class="fas fa-fw fa-sm fa-fire text-notification"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->subscriber->total_sent_push_notifications) ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('subscriber.custom_parameters') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-primary-50">
                            <i class="fas fa-fw fa-sm fa-fingerprint text-primary"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr(count($data->subscriber->custom_parameters)) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('subscribers.subscribed_on_url') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-link text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?php if($data->subscriber->subscribed_on_url): ?>
                            <?= $data->subscriber->subscribed_on_url ?>
                            <a href="<?= $data->subscriber->subscribed_on_url ?>" target="_blank" rel="nofollow noreferrer">
                                <i class="fas fa-fw fa-xs fa-external-link text-muted ml-1"></i>
                            </a>
                        <?php else: ?>
                            <?= l('global.unknown') ?>
                        <?php endif ?>
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
                        <?= nr($data->subscriber->total_displayed_push_notifications) . '/' . nr($data->subscriber->total_sent_push_notifications) ?>
                        <span class="text-muted">
                            <?= ' (' . nr(get_percentage_between_two_numbers($data->subscriber->total_displayed_push_notifications, $data->subscriber->total_sent_push_notifications)) . '%' . ')' ?>
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
                        <?= nr($data->subscriber->total_clicked_push_notifications) . '/' . nr($data->subscriber->total_displayed_push_notifications) ?>
                        <span class="text-muted">
                            <?= ' (' . nr(get_percentage_between_two_numbers($data->subscriber->total_clicked_push_notifications, $data->subscriber->total_displayed_push_notifications)) . '%' . ')' ?>
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
                        <?= nr($data->subscriber->total_displayed_push_notifications) . '/' . nr($data->subscriber->total_displayed_push_notifications) ?>
                        <span class="text-muted">
                            <?= ' (' . nr(get_percentage_between_two_numbers($data->subscriber->total_displayed_push_notifications, $data->subscriber->total_displayed_push_notifications)) . '%' . ')' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-4 mb-5">
        <div class="d-flex align-items-center mb-3">
            <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3"><i class="fas fa-fw fa-sm fa-stream mr-1"></i> <?= l('subscriber.logs') ?></h2>

            <div class="flex-fill">
                <hr class="border-gray-100" />
            </div>

            <div class="ml-3">
                <a href="<?= url('subscribers-logs?subscriber_id=' . $data->subscriber->subscriber_id) ?>" class="btn btn-sm btn-primary-100" data-toggle="tooltip" title="<?= l('global.view_all') ?>"><i class="fas fa-fw fa-stream fa-sm"></i></a>
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
                                    <?= $row->ip ?>
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

    <?php if (!empty($data->subscriber->custom_parameters)): ?>
        <div class="mt-4 mb-5">
            <div class="d-flex align-items-center mb-3">
                <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3"><i class="fas fa-fw fa-sm fa-fingerprint mr-1"></i> <?= l('subscriber.custom_parameters') ?></h2>

                <div class="flex-fill">
                    <hr class="border-gray-100" />
                </div>
            </div>

            <div class="table-responsive table-custom-container">
                <table class="table table-custom">
                    <tbody>

                    <?php foreach($data->subscriber->custom_parameters as $key => $value): ?>
                        <tr>
                            <td class="font-weight-bold text-truncate text-muted">
                                <?= e($key) ?>
                            </td>
                            <td class="text-truncate">
                                <?= e($value) ?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    </tbody>
                </table>
            </div>
        </div>
    <?php endif ?>

    <div class="mt-4 mb-5">
        <div class="d-flex align-items-center mb-3">
            <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3"><i class="fas fa-fw fa-sm fa-laptop-code mr-1"></i> <?= l('subscriber.advanced') ?></h2>

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
                        <div class="font-weight-bold text-muted small"><?= l('subscriber.subscriber_id') ?></div>
                        <span><?= $data->subscriber->subscriber_id ?></span>
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
                                data-clipboard-text="<?= $data->subscriber->subscriber_id ?>"
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
                        <div class="font-weight-bold text-muted small"><?= l('subscriber.endpoint') ?></div>
                        <span><?= $data->subscriber->endpoint ?></span>
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
                                data-clipboard-text="<?= $data->subscriber->endpoint ?>"
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
                        <div class="font-weight-bold text-muted small"><?= l('subscriber.keys.p256dh') ?></div>
                        <span><?= $data->subscriber->keys->p256dh ?></span>
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
                                data-clipboard-text="<?= $data->subscriber->keys->p256dh ?>"
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
                            <i class="fas fa-fw fa-sm fa-key text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <div class="font-weight-bold text-muted small"><?= l('subscriber.keys.auth') ?></div>
                        <span><?= $data->subscriber->keys->auth ?></span>
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
                                data-clipboard-text="<?= $data->subscriber->endpoint ?>"
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
