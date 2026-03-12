<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('rss_automations') ?>"><?= l('rss_automations.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('rss_automation.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="card d-flex flex-row mb-4">
        <div class="pl-3 d-flex flex-column justify-content-center">
            <?php if($data->rss_automation->is_enabled): ?>
                <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-light" data-toggle="tooltip" title="<?= l('global.active') ?>">
                    <i class="fas fa-fw fa-sm fa-check text-success"></i>
                </div>
            <?php else: ?>
                <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-light" data-toggle="tooltip" title="<?= l('global.disabled') ?>">
                    <i class="fas fa-fw fa-sm fa-pause text-warning"></i>
                </div>
            <?php endif ?>
        </div>

        <div class="card-body text-truncate d-flex justify-content-between align-items-center">
            <div class="text-truncate">
                <h1 class="h4 text-truncate mb-0"><?= sprintf(l('rss_automation.header'), $data->rss_automation->name) ?></h1>

                <div class="d-flex align-items-center">
                    <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($data->website->host) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />

                    <a href="<?= url('website/' . $data->website->website_id) ?>" class="small text-muted text-truncate" data-toggle="tooltip" title="<?= $data->website->host . $data->website->path ?>">
                        <?= string_truncate($data->website->host . $data->website->path, 32) ?>
                    </a>
                </div>
            </div>

            <?= include_view(THEME_PATH . 'views/rss-automations/rss_automation_dropdown_button.php', ['id' => $data->rss_automation->rss_automation_id, 'resource_name' => $data->rss_automation->name,]) ?>
        </div>
    </div>

    <div class="my-4">
        <div class="row">
            <div class="col-12 col-md-6 col-xl-3 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true" title="<?= l('rss_automations.last_check_datetime') . ($data->rss_automation->last_check_datetime ? '<br />' . \Altum\Date::get($data->rss_automation->last_check_datetime, 2) . '<br /><small>' . \Altum\Date::get($data->rss_automation->last_check_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($data->rss_automation->last_check_datetime) . ')</small>' : '<br />' . l('global.na')) ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-calendar-check text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= $data->rss_automation->last_check_datetime ? \Altum\Date::get_timeago($data->rss_automation->last_check_datetime) : l('global.na') ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true" title="<?= l('rss_automations.next_check_datetime') . ($data->rss_automation->next_check_datetime ? '<br />' . \Altum\Date::get($data->rss_automation->next_check_datetime, 2) . '<br /><small>' . \Altum\Date::get($data->rss_automation->next_check_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($data->rss_automation->next_check_datetime) . ')</small>' : '<br />' . l('global.na')) ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-calendar-day text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= $data->rss_automation->next_check_datetime ? \Altum\Date::get_timeago($data->rss_automation->next_check_datetime) : l('global.na') ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.datetime_tooltip'), '<br />' . \Altum\Date::get($data->rss_automation->datetime, 2) . '<br /><small>' . \Altum\Date::get($data->rss_automation->datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($data->rss_automation->datetime) . ')</small>') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-clock text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= $data->rss_automation->datetime ? \Altum\Date::get_timeago($data->rss_automation->datetime) : l('global.na') ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.last_datetime_tooltip'), ($data->rss_automation->last_datetime ? '<br />' . \Altum\Date::get($data->rss_automation->last_datetime, 2) . '<br /><small>' . \Altum\Date::get($data->rss_automation->last_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($data->rss_automation->last_datetime) . ')</small>' : '<br />' . l('global.na'))) ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-clock-rotate-left text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= $data->rss_automation->last_datetime ? \Altum\Date::get_timeago($data->rss_automation->last_datetime) : l('global.na') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('rss_automations.total_campaigns') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-campaign">
                            <i class="fas fa-fw fa-sm fa-rocket text-campaign"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->rss_automation->total_campaigns) ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.total_sent_push_notifications') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-notification">
                            <i class="fas fa-fw fa-sm fa-fire text-notification"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->rss_automation->total_sent_push_notifications) ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.segment') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-subscriber">
                            <i class="fas fa-fw fa-sm fa-layer-group text-subscriber"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= l('campaigns.segment.' . $data->rss_automation->segment) ?>
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
                        <?= nr($data->rss_automation->total_displayed_push_notifications) . '/' . nr($data->rss_automation->total_sent_push_notifications) ?>
                        <span class="text-muted">
                            <?= ' (' . nr(get_percentage_between_two_numbers($data->rss_automation->total_displayed_push_notifications, $data->rss_automation->total_sent_push_notifications)) . '%' . ')' ?>
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
                        <?= nr($data->rss_automation->total_clicked_push_notifications) . '/' . nr($data->rss_automation->total_displayed_push_notifications) ?>
                        <span class="text-muted">
                            <?= ' (' . nr(get_percentage_between_two_numbers($data->rss_automation->total_clicked_push_notifications, $data->rss_automation->total_displayed_push_notifications)) . '%' . ')' ?>
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
                        <?= nr($data->rss_automation->total_displayed_push_notifications) . '/' . nr($data->rss_automation->total_displayed_push_notifications) ?>
                        <span class="text-muted">
                            <?= ' (' . nr(get_percentage_between_two_numbers($data->rss_automation->total_displayed_push_notifications, $data->rss_automation->total_displayed_push_notifications)) . '%' . ')' ?>
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
                <a href="<?= url('subscribers-logs?rss_automation_id=' . $data->rss_automation->rss_automation_id) ?>" class="btn btn-sm btn-primary-100" data-toggle="tooltip" title="<?= l('global.view_all') ?>"><i class="fas fa-fw fa-stream fa-sm"></i></a>
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

                                    <a href="<?= url('website/' . $data->website->website_id) ?>" class="small text-muted" data-toggle="tooltip" title="<?= $data->website->host . $data->website->path ?>">
                                        <?= string_truncate($data->website->host . $data->website->path, 32) ?>
                                    </a>
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
            <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3"><i class="fas fa-fw fa-sm fa-tasks mr-1"></i> <?= l('rss_automations.rss_automation') ?></h2>

            <div class="flex-fill">
                <hr class="border-gray-100" />
            </div>
        </div>

        <div class="table-responsive table-custom-container">
            <table class="table table-custom">
                <tbody>
                <tr>
                    <td class="font-weight-bold text-truncate text-muted">
                        <i class="fas fa-fw fa-feed fa-sm text-muted mr-1"></i>
                        <?= l('rss_automations.rss_url') ?>
                    </td>
                    <td class="text-truncate">
                        <?= $data->rss_automation->rss_url ?>
                        <a href="<?= $data->rss_automation->rss_url ?>" target="_blank" rel="nofollow noreferrer">
                            <i class="fas fa-fw fa-xs fa-external-link text-muted ml-1"></i>
                        </a>
                    </td>
                </tr>

                <tr>
                    <td class="font-weight-bold text-truncate text-muted">
                        <i class="fas fa-fw fa-heading fa-sm text-muted mr-1"></i>
                        <?= l('global.title') ?>
                    </td>
                    <td class="text-truncate">
                        <?= $data->rss_automation->title ?>
                    </td>
                </tr>

                <tr>
                    <td class="font-weight-bold text-truncate text-muted">
                        <i class="fas fa-fw fa-paragraph fa-sm text-muted mr-1"></i>
                        <?= l('global.description') ?>
                    </td>
                    <td class="text-truncate">
                        <?= $data->rss_automation->description ?>
                    </td>
                </tr>

                <tr>
                    <td class="font-weight-bold text-truncate text-muted">
                        <i class="fas fa-fw fa-link fa-sm text-muted mr-1"></i>
                        <?= l('global.url') ?>
                    </td>
                    <td class="text-truncate">
                        <?php if($data->rss_automation->url): ?>
                            <?= $data->rss_automation->url ?>
                            <a href="<?= $data->rss_automation->url ?>" target="_blank" rel="nofollow noreferrer">
                                <i class="fas fa-fw fa-xs fa-external-link text-muted ml-1"></i>
                            </a>
                        <?php else: ?>
                            <?= l('global.none') ?>
                        <?php endif ?>
                    </td>
                </tr>

                <tr>
                    <td class="font-weight-bold text-truncate text-muted">
                        <i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i>
                        <?= l('global.image') ?>
                    </td>
                    <td class="text-truncate">
                        <?php if($data->rss_automation->image): ?>
                            <div>
                                <a href="<?= \Altum\Uploads::get_full_url('websites_rss_automations_images') . $data->rss_automation->image ?>" target="_blank" rel="nofollow noreferrer">
                                    <img src="<?= \Altum\Uploads::get_full_url('websites_rss_automations_images') . $data->rss_automation->image ?>" class="img-fluid rounded" style="max-width: 15rem;" loading="lazy" />
                                </a>
                            </div>
                        <?php else: ?>
                            <?= l('global.none') ?>
                        <?php endif ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 p-3 text-truncate">
            <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true">
                <div class="pl-3 d-flex flex-column justify-content-center">
                    <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                        <i class="fas fa-fw fa-sm fa-mouse text-muted"></i>
                    </div>
                </div>

                <div class="card-body text-truncate">
                    <div class="font-weight-bold text-muted small text-truncate"><?= sprintf(l('campaigns.button_x'), 1) ?></div>
                    <span>
                        <?php if($data->rss_automation->settings->button_title_1): ?>
                            <?= $data->rss_automation->settings->button_title_1 ?>
                            <a href="<?= $data->rss_automation->settings->button_url_1 ?>" target="_blank" rel="nofollow noreferrer">
                                <i class="fas fa-fw fa-xs fa-external-link text-muted ml-1"></i>
                            </a>
                        <?php else: ?>
                            <?= l('global.no') ?>
                        <?php endif ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 p-3 text-truncate">
            <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true">
                <div class="pl-3 d-flex flex-column justify-content-center">
                    <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                        <i class="fas fa-fw fa-sm fa-mouse text-muted"></i>
                    </div>
                </div>

                <div class="card-body text-truncate">
                    <div class="font-weight-bold text-muted small text-truncate"><?= sprintf(l('campaigns.button_x'), 2) ?></div>
                    <span>
                        <?php if($data->rss_automation->settings->button_title_2): ?>
                            <?= $data->rss_automation->settings->button_title_2 ?>
                            <a href="<?= $data->rss_automation->settings->button_url_2 ?>" target="_blank" rel="nofollow noreferrer">
                                    <i class="fas fa-fw fa-xs fa-external-link text-muted ml-1"></i>
                                </a>
                        <?php else: ?>
                            <?= l('global.no') ?>
                        <?php endif ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 p-3 text-truncate">
            <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true">
                <div class="pl-3 d-flex flex-column justify-content-center">
                    <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                        <i class="fas fa-fw fa-sm <?= $data->rss_automation->settings->is_silent ? 'fa-volume-down' : 'fa-volume-up' ?> text-muted"></i>
                    </div>
                </div>

                <div class="card-body text-truncate">
                    <div class="font-weight-bold text-muted small text-truncate"><?= l('campaigns.is_silent') ?></div>
                    <span><?= $data->rss_automation->settings->is_silent ? l('global.yes') : l('global.no') ?></span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 p-3 text-truncate">
            <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true">
                <div class="pl-3 d-flex flex-column justify-content-center">
                    <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                        <i class="fas fa-fw fa-sm <?= $data->rss_automation->settings->is_auto_hide ? 'fa-eye-slash' : 'fa-eye' ?> text-muted"></i>
                    </div>
                </div>

                <div class="card-body text-truncate">
                    <div class="font-weight-bold text-muted small text-truncate"><?= l('campaigns.is_auto_hide') ?></div>
                    <span><?= $data->rss_automation->settings->is_auto_hide ? l('global.yes') : l('global.no') ?></span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 p-3 text-truncate">
            <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true">
                <div class="pl-3 d-flex flex-column justify-content-center">
                    <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                        <i class="fas fa-fw fa-sm fa-stopwatch text-muted"></i>
                    </div>
                </div>

                <div class="card-body text-truncate">
                    <div class="font-weight-bold text-muted small text-truncate"><?= l('campaigns.ttl') ?></div>
                    <span><?= $data->notifications_ttl[$data->rss_automation->settings->ttl] ?></span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 p-3 text-truncate">
            <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" data-html="true">
                <div class="pl-3 d-flex flex-column justify-content-center">
                    <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                        <i class="fas fa-fw fa-sm fa-gauge-high text-muted"></i>
                    </div>
                </div>

                <div class="card-body text-truncate">
                    <div class="font-weight-bold text-muted small text-truncate"><?= l('campaigns.urgency') ?></div>
                    <span><?= l('campaigns.urgency.' . $data->rss_automation->settings->urgency) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>
