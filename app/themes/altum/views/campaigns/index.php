<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <div class="row mb-4">
        <div class="col-12 col-lg d-flex align-items-center mb-3 mb-lg-0 text-truncate">
            <h1 class="h4 m-0 text-truncate"><i class="fas fa-fw fa-xs fa-rocket mr-1"></i> <?= l('campaigns.header') ?></h1>

            <div class="ml-2">
                <span data-toggle="tooltip" title="<?= l('campaigns.subheader') ?>">
                    <i class="fas fa-fw fa-info-circle text-muted"></i>
                </span>
            </div>
        </div>

        <div class="col-12 col-lg-auto d-flex flex-wrap gap-3 d-print-none">
            <div>
                <?php if(($this->user->plan_settings->campaigns_per_month_limit != -1 && $data->campaigns_current_month >= $this->user->plan_settings->campaigns_per_month_limit)): ?>
                    <button type="button" class="btn btn-primary disabled" <?= get_plan_feature_limit_reached_info() ?>>
                        <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('campaigns.create') ?>
                    </button>
                <?php else: ?>
                    <a href="<?= url('campaign-create') ?>" class="btn btn-primary" data-toggle="tooltip" data-html="true" title="<?= get_plan_feature_limit_info($data->campaigns_current_month, $this->user->plan_settings->campaigns_per_month_limit, isset($data->filters) ? !$data->filters->has_applied_filters : true) ?>">
                        <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('campaigns.create') ?>
                    </a>
                <?php endif ?>
            </div>

            <div>
                <div class="dropdown">
                    <button type="button" class="btn btn-light dropdown-toggle-simple <?= count($data->campaigns) ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip title="<?= l('global.export') ?>" data-tooltip-hide-on-click>
                        <i class="fas fa-fw fa-sm fa-download"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right d-print-none">
                        <a href="<?= url('campaigns?' . $data->filters->get_get() . '&export=csv')  ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->csv ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->csv ? null : get_plan_feature_disabled_info() ?>>
                            <i class="fas fa-fw fa-sm fa-file-csv mr-2"></i> <?= sprintf(l('global.export_to'), 'CSV') ?>
                        </a>
                        <a href="<?= url('campaigns?' . $data->filters->get_get() . '&export=json') ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->json ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->json ? null : get_plan_feature_disabled_info() ?>>
                            <i class="fas fa-fw fa-sm fa-file-code mr-2"></i> <?= sprintf(l('global.export_to'), 'JSON') ?>
                        </a>
                        <a href="#" class="dropdown-item <?= $this->user->plan_settings->export->pdf ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->pdf ? $this->user->plan_settings->export->pdf ? 'onclick="event.preventDefault(); window.print();"' : 'disabled pointer-events-all' : get_plan_feature_disabled_info() ?>>
                            <i class="fas fa-fw fa-sm fa-file-pdf mr-2"></i> <?= sprintf(l('global.export_to'), 'PDF') ?>
                        </a>
                    </div>
                </div>
            </div>

            <div>
                <div class="dropdown">
                    <button type="button" class="btn <?= $data->filters->has_applied_filters ? 'btn-dark' : 'btn-light' ?> filters-button dropdown-toggle-simple <?= count($data->campaigns) || $data->filters->has_applied_filters ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip data-html="true" title="<?= l('global.filters.tooltip') ?>" data-tooltip-hide-on-click>
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
                                <label for="filters_search" class="small"><?= l('global.filters.search') ?></label>
                                <input type="search" name="search" id="filters_search" class="form-control form-control-sm" value="<?= $data->filters->search ?>" />
                            </div>

                            <div class="form-group px-4">
                                <label for="filters_search_by" class="small"><?= l('global.filters.search_by') ?></label>
                                <select name="search_by" id="filters_search_by" class="custom-select custom-select-sm">
                                    <option value="name" <?= $data->filters->search_by == 'name' ? 'selected="selected"' : null ?>><?= l('global.name') ?></option>
                                    <option value="title" <?= $data->filters->search_by == 'title' ? 'selected="selected"' : null ?>><?= l('global.title') ?></option>
                                    <option value="description" <?= $data->filters->search_by == 'description' ? 'selected="selected"' : null ?>><?= l('global.description') ?></option>
                                    <option value="url" <?= $data->filters->search_by == 'url' ? 'selected="selected"' : null ?>><?= l('global.url') ?></option>
                                </select>
                            </div>

                            <div class="form-group px-4">
                                <label for="filters_status" class="small"><?= l('global.status') ?></label>
                                <select name="status" id="filters_status" class="custom-select custom-select-sm">
                                    <option value=""><?= l('global.all') ?></option>
                                    <option value="draft" <?= isset($data->filters->filters['status']) && $data->filters->filters['status'] == 'draft' ? 'selected="selected"' : null ?>><?= l('campaigns.status.draft') ?></option>
                                    <option value="scheduled" <?= isset($data->filters->filters['status']) && $data->filters->filters['status'] == 'scheduled' ? 'selected="selected"' : null ?>><?= l('campaigns.status.scheduled') ?></option>
                                    <option value="processing" <?= isset($data->filters->filters['status']) && $data->filters->filters['status'] == 'processing' ? 'selected="selected"' : null ?>><?= l('campaigns.status.processing') ?></option>
                                    <option value="sent" <?= isset($data->filters->filters['status']) && $data->filters->filters['status'] == 'sent' ? 'selected="selected"' : null ?>><?= l('campaigns.status.sent') ?></option>
                                </select>
                            </div>

                            <div class="form-group px-4">
                                <label for="filters_website_id" class="small"><?= l('websites.website') ?></label>
                                <select name="website_id" id="filters_website_id" class="custom-select custom-select-sm">
                                    <option value=""><?= l('global.all') ?></option>
                                    <?php foreach($data->websites as $website_id => $website): ?>
                                        <option value="<?= $website_id ?>" <?= isset($data->filters->filters['website_id']) && $data->filters->filters['website_id'] == $website_id ? 'selected="selected"' : null ?>><?= $website->name . ' - ' . $website->host . $website->path ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="form-group px-4">
                                <label for="filters_order_by" class="small"><?= l('global.filters.order_by') ?></label>
                                <select name="order_by" id="filters_order_by" class="custom-select custom-select-sm">
                                    <option value="campaign_id" <?= $data->filters->order_by == 'campaign_id' ? 'selected="selected"' : null ?>><?= l('global.id') ?></option>
                                    <option value="datetime" <?= $data->filters->order_by == 'datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_datetime') ?></option>
                                    <option value="last_datetime" <?= $data->filters->order_by == 'last_datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_last_datetime') ?></option>
                                    <option value="scheduled_datetime" <?= $data->filters->order_by == 'scheduled_datetime' ? 'selected="selected"' : null ?>><?= l('campaigns.scheduled_datetime') ?></option>
                                    <option value="last_sent_datetime" <?= $data->filters->order_by == 'last_sent_datetime' ? 'selected="selected"' : null ?>><?= l('campaigns.last_sent_datetime') ?></option>
                                    <option value="name" <?= $data->filters->order_by == 'name' ? 'selected="selected"' : null ?>><?= l('global.name') ?></option>
                                    <option value="title" <?= $data->filters->order_by == 'title' ? 'selected="selected"' : null ?>><?= l('global.title') ?></option>
                                    <option value="total_push_notifications" <?= $data->filters->order_by == 'total_push_notifications' ? 'selected="selected"' : null ?>><?= l('campaigns.total_push_notifications') ?></option>
                                    <option value="total_sent_push_notifications" <?= $data->filters->order_by == 'total_sent_push_notifications' ? 'selected="selected"' : null ?>><?= l('campaigns.total_sent_push_notifications') ?></option>
                                    <option value="total_displayed_push_notifications" <?= $data->filters->order_by == 'total_displayed_push_notifications' ? 'selected="selected"' : null ?>><?= l('campaigns.total_displayed_push_notifications') ?></option>
                                    <option value="total_clicked_push_notifications" <?= $data->filters->order_by == 'total_clicked_push_notifications' ? 'selected="selected"' : null ?>><?= l('campaigns.total_clicked_push_notifications') ?></option>
                                    <option value="total_closed_push_notifications" <?= $data->filters->order_by == 'total_closed_push_notifications' ? 'selected="selected"' : null ?>><?= l('campaigns.total_closed_push_notifications') ?></option>
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
                <button id="bulk_enable" type="button" class="btn btn-light <?= count($data->campaigns) ? null : 'disabled' ?>" data-toggle="tooltip" title="<?= l('global.bulk_actions') ?>"><i class="fas fa-fw fa-sm fa-list"></i></button>

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

    <?php if (!empty($data->campaigns)): ?>
        <div class="row mt-n3 mb-3">
            <div class="col-12 col-md-6 col-xl-3 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.total_sent_push_notifications') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-notification">
                            <i class="fas fa-fw fa-sm fa-fire text-notification"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->websites_stats['total_sent_push_notifications']) ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.total_displayed_push_notifications') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-displayed">
                            <i class="fas fa-fw fa-sm fa-mobile text-displayed"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->websites_stats['total_displayed_push_notifications']) . '/' . nr($data->websites_stats['total_sent_push_notifications']) ?>
                        <span class="text-muted">
                        <?= ' (' . nr(get_percentage_between_two_numbers($data->websites_stats['total_displayed_push_notifications'], $data->websites_stats['total_sent_push_notifications'])) . '%' . ')' ?>
                    </span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.total_clicked_push_notifications') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-clicked">
                            <i class="fas fa-fw fa-sm fa-mouse text-clicked"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->websites_stats['total_clicked_push_notifications']) . '/' . nr($data->websites_stats['total_displayed_push_notifications']) ?>
                        <span class="text-muted">
                        <?= ' (' . nr(get_percentage_between_two_numbers($data->websites_stats['total_clicked_push_notifications'], $data->websites_stats['total_displayed_push_notifications'])) . '%' . ')' ?>
                    </span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3 p-3 text-truncate">
                <div class="card d-flex flex-row h-100 overflow-hidden" data-toggle="tooltip" title="<?= l('campaigns.total_closed_push_notifications') ?>">
                    <div class="pl-3 d-flex flex-column justify-content-center">
                        <div class="p-2 rounded-2x index-widget-icon d-flex align-items-center justify-content-center bg-gray-50">
                            <i class="fas fa-fw fa-sm fa-times text-muted"></i>
                        </div>
                    </div>

                    <div class="card-body text-truncate">
                        <?= nr($data->websites_stats['total_closed_push_notifications']) . '/' . nr($data->websites_stats['total_displayed_push_notifications']) ?>
                        <span class="text-muted">
                            <?= ' (' . nr(get_percentage_between_two_numbers($data->websites_stats['total_closed_push_notifications'], $data->websites_stats['total_displayed_push_notifications'])) . '%' . ')' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <form id="table" action="<?= SITE_URL . 'campaigns/bulk' ?>" method="post" role="form">
            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
            <input type="hidden" name="type" value="" data-bulk-type />
            <input type="hidden" name="original_request" value="<?= base64_encode(\Altum\Router::$original_request) ?>" />
            <input type="hidden" name="original_request_query" value="<?= base64_encode(\Altum\Router::$original_request_query) ?>" />

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
                            <td data-bulk-table class="d-none">
                                <div class="custom-control custom-checkbox">
                                    <input id="selected_campaign_id_<?= $row->campaign_id ?>" type="checkbox" class="custom-control-input" name="selected[]" value="<?= $row->campaign_id ?>" />
                                    <label class="custom-control-label" for="selected_campaign_id_<?= $row->campaign_id ?>"></label>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <div>
                                    <?php ob_start() ?>
                                    <div class='d-flex flex-column text-left'>
                                        <div class='d-flex flex-column my-1'>
                                            <div class='font-weight-bold'><?= l('global.title') ?></div>
                                            <span>
                                                <?= $row->title ?>
                                            </span>
                                        </div>

                                        <div class='d-flex flex-column my-1'>
                                            <div class='font-weight-bold'><?= l('global.description') ?></div>
                                            <span>
                                                <?= $row->description ?>
                                            </span>
                                        </div>

                                        <div class='d-flex flex-column my-1'>
                                            <div class='font-weight-bold'><?= l('global.url') ?></div>
                                            <span>
                                                <?= $row->url ? remove_url_protocol_from_url($row->url) : l('global.none') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php $tooltip = ob_get_clean(); ?>

                                    <?php if(in_array($row->status, ['draft', 'scheduled'])): ?>
                                        <a href="<?= url('campaign-update/' . $row->campaign_id) ?>" data-toggle="tooltip" data-html="true" title="<?= $tooltip ?>">
                                            <?= $row->name ?>
                                        </a>
                                    <?php elseif(in_array($row->status, ['processing', 'sent'])): ?>
                                        <a href="<?= url('campaign/' . $row->campaign_id) ?>" data-toggle="tooltip" data-html="true" title="<?= $tooltip ?>">
                                            <?= $row->name ?>
                                        </a>
                                    <?php endif ?>
                                </div>

                                <div class="d-flex align-items-center">
                                    <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($data->websites[$row->website_id]->host) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />

                                    <a href="<?= url('website/' . $row->website_id) ?>" class="small text-muted" data-toggle="tooltip" title="<?= $data->websites[$row->website_id]->host . $data->websites[$row->website_id]->path ?>">
                                        <?= string_truncate($data->websites[$row->website_id]->host . $data->websites[$row->website_id]->path, 32) ?>
                                    </a>
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
                                <?php ob_start() ?>
                                <div class='d-flex flex-column text-left'>
                                    <div class='d-flex flex-column my-1'>
                                        <div><?= l('campaigns.total_displayed_push_notifications') ?></div>
                                        <strong>
                                            <?= nr($row->total_displayed_push_notifications) . '/' . nr($row->total_sent_push_notifications) ?>

                                            <span class='text-muted'>
                                                <?= ' (' . nr(get_percentage_between_two_numbers($row->total_displayed_push_notifications, $row->total_sent_push_notifications)) . '%' . ')' ?>
                                            </span>
                                        </strong>
                                    </div>

                                    <div class='d-flex flex-column my-1'>
                                        <div><?= l('campaigns.total_clicked_push_notifications') ?></div>
                                        <strong>
                                            <?= nr($row->total_clicked_push_notifications) . '/' . nr($row->total_sent_push_notifications) ?>

                                            <span class='text-muted'>
                                                <?= ' (' . nr(get_percentage_between_two_numbers($row->total_clicked_push_notifications, $row->total_displayed_push_notifications)) . '%' . ')' ?>
                                            </span>
                                        </strong>
                                    </div>

                                    <div class='d-flex flex-column my-1'>
                                        <div><?= l('campaigns.total_closed_push_notifications') ?></div>
                                        <strong>
                                            <?= nr($row->total_closed_push_notifications) . '/' . nr($row->total_sent_push_notifications) ?>

                                            <span class='text-muted'>
                                                <?= ' (' . nr(get_percentage_between_two_numbers($row->total_closed_push_notifications, $row->total_displayed_push_notifications)) . '%' . ')' ?>
                                            </span>
                                        </strong>
                                    </div>
                                </div>
                                <?php $tooltip = ob_get_clean(); ?>

                                <span class="badge text-notification bg-notification" data-toggle="tooltip" data-html="true" title="<?= $tooltip ?>">
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
        </form>

        <div class="mt-3"><?= $data->pagination ?></div>
    <?php else: ?>
        <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
            'filters_get' => $data->filters->get ?? [],
            'name' => 'campaigns',
            'has_secondary_text' => true,
        ]); ?>
    <?php endif ?>
</div>

<?php require THEME_PATH . 'views/partials/js_bulk.php' ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_delete_modal.php'), 'modals'); ?>
