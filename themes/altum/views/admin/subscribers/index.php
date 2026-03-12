<?php defined('ALTUMCODE') || die() ?>

<div class="d-flex flex-column flex-md-row justify-content-between mb-4">
    <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-user-check text-primary-900 mr-2"></i> <?= l('admin_subscribers.header') ?></h1>

    <div class="d-flex position-relative d-print-none">
        <div class="ml-3">
            <a href="<?= url('admin/statistics/subscribers') ?>" class="btn btn-gray-300" data-tooltip title="<?= l('global.statistics') ?>">
                <i class="fas fa-fw fa-sm fa-chart-bar"></i>
            </a>
        </div>

        <div class="ml-3">
            <div class="dropdown">
                <button type="button" class="btn btn-gray-300 dropdown-toggle-simple <?= count($data->subscribers) ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip title="<?= l('global.export') ?>" data-tooltip-hide-on-click>
                    <i class="fas fa-fw fa-sm fa-download"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-right d-print-none">
                    <a href="<?= url('admin/subscribers?' . $data->filters->get_get() . '&export=csv')  ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->csv ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->csv ? null : get_plan_feature_disabled_info() ?>>
                        <i class="fas fa-fw fa-sm fa-file-csv mr-2"></i> <?= sprintf(l('global.export_to'), 'CSV') ?>
                    </a>
                    <a href="<?= url('admin/subscribers?' . $data->filters->get_get() . '&export=json') ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->json ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->json ? null : get_plan_feature_disabled_info() ?>>
                        <i class="fas fa-fw fa-sm fa-file-code mr-2"></i> <?= sprintf(l('global.export_to'), 'JSON') ?>
                    </a>
                    <a href="#" class="dropdown-item <?= $this->user->plan_settings->export->pdf ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->pdf ? $this->user->plan_settings->export->pdf ? 'onclick="event.preventDefault(); window.print();"' : 'disabled pointer-events-all' : get_plan_feature_disabled_info() ?>>
                        <i class="fas fa-fw fa-sm fa-file-pdf mr-2"></i> <?= sprintf(l('global.export_to'), 'PDF') ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="ml-3">
            <div class="dropdown">
                <button type="button" class="btn <?= $data->filters->has_applied_filters ? 'btn-dark' : 'btn-gray-300' ?> filters-button dropdown-toggle-simple <?= count($data->subscribers) || $data->filters->has_applied_filters ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip data-html="true" title="<?= l('global.filters.tooltip') ?>" data-tooltip-hide-on-click>
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
                                <option value="ip" <?= $data->filters->search_by == 'ip' ? 'selected="selected"' : null ?>><?= l('global.ip') ?></option>
                                <option value="city_name" <?= $data->filters->search_by == 'city_name' ? 'selected="selected"' : null ?>><?= l('global.city') ?></option>
                                <option value="os_name" <?= $data->filters->search_by == 'os_name' ? 'selected="selected"' : null ?>><?= l('global.os_name') ?></option>
                                <option value="browser_name" <?= $data->filters->search_by == 'browser_name' ? 'selected="selected"' : null ?>><?= l('global.browser_name') ?></option>
                                <option value="browser_language" <?= $data->filters->search_by == 'browser_language' ? 'selected="selected"' : null ?>><?= l('global.browser_language') ?></option>
                                <option value="subscribed_on_url" <?= $data->filters->search_by == 'subscribed_on_url' ? 'selected="selected"' : null ?>><?= l('subscribers.subscribed_on_url') ?></option>
                            </select>
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_device_type" class="small"><?= l('global.device') ?></label>
                            <select name="device_type" id="filters_device_type" class="custom-select custom-select-sm">
                                <option value=""><?= l('global.all') ?></option>
                                <?php foreach(['desktop', 'tablet', 'mobile'] as $device_type): ?>
                                    <option value="<?= $device_type ?>" <?= isset($data->filters->filters['device_type']) && $data->filters->filters['device_type'] == $device_type ? 'selected="selected"' : null ?>><?= l('global.device.' . $device_type) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_continent_code" class="small"><?= l('global.continent') ?></label>
                            <select name="continent_code" id="filters_continent_code" class="custom-select custom-select-sm">
                                <option value=""><?= l('global.all') ?></option>
                                <?php foreach(get_continents_array() as $continent_code => $continent_name): ?>
                                    <option value="<?= $continent_code ?>" <?= isset($data->filters->filters['continent_code']) && $data->filters->filters['continent_code'] == $continent_code ? 'selected="selected"' : null ?>><?= $continent_name ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_country_code" class="small"><?= l('global.country') ?></label>
                            <select name="country_code" id="filters_country_code" class="custom-select custom-select-sm">
                                <option value=""><?= l('global.all') ?></option>
                                <?php foreach(get_countries_array() as $country_code => $country_name): ?>
                                    <option value="<?= $country_code ?>" <?= isset($data->filters->filters['country_code']) && $data->filters->filters['country_code'] == $country_code ? 'selected="selected"' : null ?>><?= $country_name ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_order_by" class="small"><?= l('global.filters.order_by') ?></label>
                            <select name="order_by" id="filters_order_by" class="custom-select custom-select-sm">
                                <option value="subscriber_id" <?= $data->filters->order_by == 'subscriber_id' ? 'selected="selected"' : null ?>><?= l('global.id') ?></option>
                                <option value="datetime" <?= $data->filters->order_by == 'datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_datetime') ?></option>
                                <option value="last_datetime" <?= $data->filters->order_by == 'last_datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_last_datetime') ?></option>
                                <option value="last_sent_datetime" <?= $data->filters->order_by == 'last_sent_datetime' ? 'selected="selected"' : null ?>><?= l('campaigns.last_sent_datetime') ?></option>
                                <option value="total_sent_push_notifications" <?= $data->filters->order_by == 'total_sent_push_notifications' ? 'selected="selected"' : null ?>><?= l('campaigns.total_sent_push_notifications') ?></option>
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

        <div class="ml-3">
            <button id="bulk_enable" type="button" class="btn btn-gray-300" data-toggle="tooltip" title="<?= l('global.bulk_actions') ?>"><i class="fas fa-fw fa-sm fa-list"></i></button>

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

<?= \Altum\Alerts::output_alerts() ?>

<?php if (!empty($data->subscribers)): ?>
    <form id="table" action="<?= SITE_URL . 'admin/subscribers/bulk' ?>" method="post" role="form">
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
                    <th><?= l('global.user') ?></th>
                    <th><?= l('websites.subscriber') ?></th>
                    <th><?= l('global.device') ?></th>
                    <th><?= l('campaigns.notifications') ?></th>
                    <th></th>
                    <th><?= l('global.details') ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>

                <?php foreach($data->subscribers as $row): ?>

                    <tr>
                        <td data-bulk-table class="d-none">
                            <div class="custom-control custom-checkbox">
                                <input id="selected_subscriber_id_<?= $row->subscriber_id ?>" type="checkbox" class="custom-control-input" name="selected[]" value="<?= $row->subscriber_id ?>" />
                                <label class="custom-control-label" for="selected_subscriber_id_<?= $row->subscriber_id ?>"></label>
                            </div>
                        </td>

                        <td class="text-nowrap">
                            <div class="d-flex">
                                <a href="<?= url('admin/user-view/' . $row->user_id) ?>">
                                    <img src="<?= get_user_avatar($row->user_avatar, $row->user_email) ?>" referrerpolicy="no-referrer" loading="lazy" class="user-avatar rounded-circle mr-3" alt="" />
                                </a>

                                <div class="d-flex flex-column">
                                    <div>
                                        <a href="<?= url('admin/user-view/' . $row->user_id) ?>"><?= $row->user_name ?></a>
                                    </div>

                                    <span class="text-muted small"><?= $row->user_email ?></span>
                                </div>
                            </div>
                        </td>

                        <td class="text-nowrap">
                            <div><?= $row->ip ?></div>

                            <div class="d-flex align-items-center">
                                <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($row->host) ?>" class="img-fluid icon-favicon mr-1" loading="lazy" />

                                <span class="small text-muted" data-toggle="tooltip" title="<?= $row->host . $row->path ?>"><?= string_truncate($row->host . $row->path, 32) ?></span>
                            </div>
                        </td>

                        <td class="text-nowrap">
                             <span class="badge badge-light">
                                <?= $row->device_type ? '<i class="fas fa-fw fa-sm fa-' . $row->device_type . ' mr-1"></i>' . l('global.device.' . $row->device_type) : l('global.unknown') ?>
                            </span>
                        </td>

                        <td class="text-nowrap">
                            <span class="badge badge-danger">
                                <i class="fas fa-fw fa-sm fa-fire mr-1"></i> <?= nr($row->total_sent_push_notifications) ?>
                            </span>
                        </td>

                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <a href="<?= url('admin/subscribers-logs?subscriber_id=' . $row->subscriber_id) ?>" class="mr-2" data-toggle="tooltip" title="<?= l('admin_subscribers_logs.menu') ?>">
                                    <i class="fas fa-fw fa-stream text-muted"></i>
                                </a>
                            </div>
                        </td>

                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <?php $row->custom_parameters = (array) $row->custom_parameters; if(count($row->custom_parameters)): ?>                                    <?php ob_start() ?>
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
                                <?= include_view(THEME_PATH . 'views/admin/subscribers/subscriber_dropdown_button.php', ['id' => $row->subscriber_id, 'resource_name' => $row->ip, ]) ?>
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
        'name' => 'subscribers',
        'has_secondary_text' => true,
    ]); ?>
<?php endif ?>

<?php require THEME_PATH . 'views/partials/js_bulk.php' ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_delete_modal.php'), 'modals'); ?>
