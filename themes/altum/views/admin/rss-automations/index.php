<?php defined('ALTUMCODE') || die() ?>

<div class="d-flex flex-column flex-md-row justify-content-between mb-4">
    <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-rss text-primary-900 mr-2"></i> <?= l('admin_rss_automations.header') ?></h1>

    <div class="d-flex position-relative d-print-none">
        <div class="ml-3">
            <a href="<?= url('admin/statistics/rss_automations') ?>" class="btn btn-gray-300" data-tooltip title="<?= l('global.statistics') ?>">
                <i class="fas fa-fw fa-sm fa-chart-bar"></i>
            </a>
        </div>

        <div class="ml-3">
            <div class="dropdown">
                <button type="button" class="btn btn-gray-300 dropdown-toggle-simple <?= count($data->rss_automations) ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip title="<?= l('global.export') ?>" data-tooltip-hide-on-click>
                    <i class="fas fa-fw fa-sm fa-download"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-right d-print-none">
                    <a href="<?= url('admin/rss-automations?' . $data->filters->get_get() . '&export=csv')  ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->csv ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->csv ? null : get_plan_feature_disabled_info() ?>>
                        <i class="fas fa-fw fa-sm fa-file-csv mr-2"></i> <?= sprintf(l('global.export_to'), 'CSV') ?>
                    </a>
                    <a href="<?= url('admin/rss-automations?' . $data->filters->get_get() . '&export=json') ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->json ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->json ? null : get_plan_feature_disabled_info() ?>>
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
                <button type="button" class="btn <?= $data->filters->has_applied_filters ? 'btn-dark' : 'btn-gray-300' ?> filters-button dropdown-toggle-simple <?= count($data->rss_automations) || $data->filters->has_applied_filters ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip data-html="true" title="<?= l('global.filters.tooltip') ?>" data-tooltip-hide-on-click>
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
                                <option value="rss_url" <?= $data->filters->search_by == 'rss_url' ? 'selected="selected"' : null ?>><?= l('rss_automations.rss_url') ?></option>
                            </select>
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_order_by" class="small"><?= l('global.filters.order_by') ?></label>
                            <select name="order_by" id="filters_order_by" class="custom-select custom-select-sm">
                                <option value="rss_automation_id" <?= $data->filters->order_by == 'rss_automation_id' ? 'selected="selected"' : null ?>><?= l('global.id') ?></option>
                                <option value="datetime" <?= $data->filters->order_by == 'datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_datetime') ?></option>
                                <option value="last_datetime" <?= $data->filters->order_by == 'last_datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_last_datetime') ?></option>
                                <option value="last_check_datetime" <?= $data->filters->order_by == 'last_check_datetime' ? 'selected="selected"' : null ?>><?= l('rss_automations.last_check_datetime') ?></option>
                                <option value="next_check_datetime" <?= $data->filters->order_by == 'next_check_datetime' ? 'selected="selected"' : null ?>><?= l('rss_automations.next_check_datetime') ?></option>
                                <option value="name" <?= $data->filters->order_by == 'name' ? 'selected="selected"' : null ?>><?= l('global.name') ?></option>
                                <option value="title" <?= $data->filters->order_by == 'title' ? 'selected="selected"' : null ?>><?= l('global.title') ?></option>
                                <option value="total_campaigns" <?= $data->filters->order_by == 'total_campaigns' ? 'selected="selected"' : null ?>><?= l('rss_automations.total_campaigns') ?></option>
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

<?php if (!empty($data->rss_automations)): ?>
    <form id="table" action="<?= SITE_URL . 'admin/rss-automations/bulk' ?>" method="post" role="form">
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
                    <th><?= l('rss_automations.rss_automation') ?></th>
                    <th><?= l('campaigns.segment') ?></th>
                    <th><?= l('rss_automations.total_campaigns') ?></th>
                    <th><?= l('campaigns.notifications') ?></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>

                <?php foreach($data->rss_automations as $row): ?>

                    <tr>
                        <td data-bulk-table class="d-none">
                            <div class="custom-control custom-checkbox">
                                <input id="selected_rss_automation_id_<?= $row->rss_automation_id ?>" type="checkbox" class="custom-control-input" name="selected[]" value="<?= $row->rss_automation_id ?>" />
                                <label class="custom-control-label" for="selected_rss_automation_id_<?= $row->rss_automation_id ?>"></label>
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
                            <div><?= $row->name ?></div>

                            <div class="d-flex align-items-center">
                                <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($row->host) ?>" class="img-fluid icon-favicon mr-1" loading="lazy" />

                                <span class="small text-muted" data-toggle="tooltip" title="<?= $row->host . $row->path ?>"><?= string_truncate($row->host . $row->path, 32) ?></span>
                            </div>
                        </td>

                        <td class="text-nowrap">
                            <?php if(is_numeric($row->segment)): ?>
                                <a href="<?= url('segments?segment_id=' . $row->segment) ?>" class="badge badge-light">
                                    <i class="fas fa-fw fa-sm fa-layer-group mr-1"></i> <?= l('campaigns.segment.saved') ?>
                                </a>
                            <?php else: ?>
                                <span class="badge badge-light">
                                    <i class="fas fa-fw fa-sm fa-layer-group mr-1"></i> <?= l('campaigns.segment.' . $row->segment) ?>
                                </span>
                            <?php endif ?>
                        </td>

                        <td class="text-nowrap">
                            <a href="<?= url('admin/campaigns?rss_automation_id=' . $row->rss_automation_id) ?>" class="badge bg-campaign text-campaign">
                                <i class="fas fa-fw fa-sm fa-rocket mr-1"></i> <?= nr($row->total_campaigns) ?>
                            </a>
                        </td>

                        <td class="text-nowrap">
                            <a href="<?= url('admin/subscribers-logs?rss_automation_id=' . $row->rss_automation_id) ?>" class="badge text-notification bg-notification">
                                <i class="fas fa-fw fa-sm fa-fire mr-1"></i> <?= nr($row->total_sent_push_notifications) ?>
                            </a>
                        </td>

                        <td class="text-nowrap">
                            <?php if($row->is_enabled): ?>
                                <span class="badge badge-success" data-toggle="tooltip" title="<?= l('global.active') ?>"><i class="fas fa-fw fa-check"></i></span>
                            <?php else: ?>
                                <span class="badge badge-warning" data-toggle="tooltip" title="<?= l('global.disabled') ?>"><i class="fas fa-fw fa-pause"></i></span>
                            <?php endif ?>
                        </td>

                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= l('rss_automations.last_check_datetime') . ($row->last_check_datetime ? '<br />' . \Altum\Date::get($row->last_check_datetime, 2) . '<br /><small>' . \Altum\Date::get($row->last_check_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->last_check_datetime) . ')</small>' : '<br />' . l('global.na')) ?>">
                                    <i class="fas fa-fw fa-calendar-check text-muted"></i>
                                </span>

                                <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= l('rss_automations.next_check_datetime') . ($row->next_check_datetime ? '<br />' . \Altum\Date::get($row->next_check_datetime, 2) . '<br /><small>' . \Altum\Date::get($row->next_check_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_time_until($row->next_check_datetime) . ')</small>' : '<br />' . l('global.na')) ?>">
                                    <i class="fas fa-fw fa-calendar-day text-muted"></i>
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
                                <?= include_view(THEME_PATH . 'views/admin/rss-automations/admin_rss_automation_dropdown_button.php', ['id' => $row->rss_automation_id, 'resource_name' => $row->name, ]) ?>
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
        'name' => 'rss_automations',
        'has_secondary_text' => true,
    ]); ?>
<?php endif ?>

<?php require THEME_PATH . 'views/partials/js_bulk.php' ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_delete_modal.php'), 'modals'); ?>
