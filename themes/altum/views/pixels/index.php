<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <style>
        .fcc-pixels-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .fcc-pixels-header {
            background: linear-gradient(180deg, rgba(19, 27, 29, 0.92) 0%, rgba(15, 21, 23, 0.98) 100%);
            border: 1px solid rgba(127, 227, 217, 0.08);
            border-radius: 22px;
            padding: 1.35rem 1.4rem;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
        }

        .fcc-pixels-heading {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }

        .fcc-pixels-heading-icon {
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

        .fcc-pixels-heading-copy {
            min-width: 0;
        }

        .fcc-pixels-heading-copy h1 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #f5fbfb;
        }

        .fcc-pixels-toolbar {
            gap: 0.75rem;
        }

        .fcc-pixels-action-btn {
            border-radius: 14px;
            min-height: 2.75rem;
            font-weight: 600;
            box-shadow: none !important;
        }

        .fcc-pixels-action-btn.btn-primary {
            background: linear-gradient(135deg, #3fd7c7 0%, #6de9dd 100%);
            border-color: transparent;
            color: #082826;
        }

        .fcc-pixels-action-btn.btn-primary:hover {
            color: #041b19;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(63, 215, 199, 0.2) !important;
        }

        .fcc-pixels-action-btn.btn-light,
        .fcc-pixels-action-btn.btn-dark {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.06);
            color: #d5e1e2;
        }

        .fcc-pixels-action-btn.btn-light:hover,
        .fcc-pixels-action-btn.btn-dark:hover {
            background: rgba(127, 227, 217, 0.1);
            border-color: rgba(127, 227, 217, 0.16);
            color: #f5fbfb;
        }

        .fcc-pixels-table-card,
        .fcc-pixels-empty {
            background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
            border: 1px solid rgba(127, 227, 217, 0.07);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 20px 44px rgba(0, 0, 0, 0.16);
        }

        .fcc-pixels-table-card .table-custom-container {
            border: 0;
            background: transparent;
        }

        .fcc-pixels-table-card .table-custom {
            margin-bottom: 0;
        }

        .fcc-pixels-table-card .table-custom thead th {
            background: rgba(255, 255, 255, 0.02);
            color: #e9f7f5;
            border-bottom-color: rgba(127, 227, 217, 0.08);
        }

        .fcc-pixels-table-card .table-custom td {
            background: transparent;
            color: #dce7e8;
            border-top-color: rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }

        .fcc-pixels-table-card .table-custom tbody tr:hover {
            background: rgba(127, 227, 217, 0.035);
        }

        .fcc-pixels-table-card .badge.badge-light {
            background: rgba(255, 255, 255, 0.08);
            color: #d6e5e4;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .fcc-pixels-table-card .btn.btn-link,
        .fcc-pixels-table-card a:not(.dropdown-item):not(.btn) {
            color: #abf5ec;
        }

        .fcc-pixels-table-card .btn.btn-link:hover,
        .fcc-pixels-table-card a:not(.dropdown-item):not(.btn):hover {
            color: #d8fffb;
            text-decoration: none;
        }

        .fcc-pixels-pagination {
            margin-top: 1rem;
            padding: 0 0.35rem;
        }
    </style>

    <div class="fcc-pixels-shell">

    <?php if($this->user->plan_settings->pixels_limit != -1 && $data->total_pixels > $this->user->plan_settings->pixels_limit): ?>
        <div class="alert alert-danger">
            <i class="fas fa-fw fa-times-circle text-danger mr-2"></i> <?= sprintf(settings()->payment->is_enabled ? l('global.info_message.plan_feature_limit_removal_with_upgrade') : l('global.info_message.plan_feature_limit_removal'), '<strong>' . $data->total_pixels - $this->user->plan_settings->pixels_limit, mb_strtolower(l('pixels.title')) . '</strong>', '<a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '</a>') ?>
        </div>
    <?php endif ?>

    <div class="fcc-pixels-header">
    <div class="row align-items-center">
        <div class="col-12 col-lg mb-4 mb-lg-0 text-truncate">
            <div class="fcc-pixels-heading">
                <div class="fcc-pixels-heading-icon">
                    <i class="fas fa-fw fa-adjust"></i>
                </div>

                <div class="fcc-pixels-heading-copy">
                    <h1 class="text-truncate">
                        <?= l('pixels.header') ?>
                        <span class="ml-1" data-toggle="tooltip" title="<?= l('pixels.subheader') ?>">
                            <i class="fas fa-fw fa-info-circle text-muted"></i>
                        </span>
                    </h1>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-auto d-flex flex-wrap fcc-pixels-toolbar d-print-none">
            <div>
                <?php if($this->user->plan_settings->pixels_limit != -1 && $data->total_pixels >= $this->user->plan_settings->pixels_limit): ?>
                    <button type="button" class="btn btn-primary disabled fcc-pixels-action-btn" <?= get_plan_feature_limit_reached_info() ?>>
                        <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('pixels.create') ?>
                    </button>
                <?php else: ?>
                    <a href="<?= url('pixel-create') ?>" class="btn btn-primary fcc-pixels-action-btn" data-toggle="tooltip" data-html="true" title="<?= get_plan_feature_limit_info($data->total_pixels, $this->user->plan_settings->pixels_limit, isset($data->filters) ? !$data->filters->has_applied_filters : true) ?>">
                        <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('pixels.create') ?>
                    </a>
                <?php endif ?>
            </div>

            <div>
                <div class="dropdown">
                    <button type="button" class="btn btn-light dropdown-toggle-simple fcc-pixels-action-btn <?= !empty($data->pixels) ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip title="<?= l('global.export') ?>" data-tooltip-hide-on-click>
                        <i class="fas fa-fw fa-sm fa-download"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right d-print-none">
                        <a href="<?= url('pixels?' . $data->filters->get_get() . '&export=csv')  ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->csv ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->csv ? null : get_plan_feature_disabled_info() ?>>
                            <i class="fas fa-fw fa-sm fa-file-csv mr-2"></i> <?= sprintf(l('global.export_to'), 'CSV') ?>
                        </a>
                        <a href="<?= url('pixels?' . $data->filters->get_get() . '&export=json') ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->json ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->json ? null : get_plan_feature_disabled_info() ?>>
                            <i class="fas fa-fw fa-sm fa-file-code mr-2"></i> <?= sprintf(l('global.export_to'), 'JSON') ?>
                    </a>
                    <a href="#" class="dropdown-item <?= $this->user->plan_settings->export->pdf ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->pdf ? 'onclick="event.preventDefault(); window.print();"' : get_plan_feature_disabled_info() ?>>
                        <i class="fas fa-fw fa-sm fa-file-pdf mr-2"></i> <?= sprintf(l('global.export_to'), 'PDF') ?>
                    </a>
                    </div>
                </div>
            </div>

            <div>
                <div class="dropdown">
                    <button type="button" class="btn <?= $data->filters->has_applied_filters ? 'btn-dark' : 'btn-light' ?> filters-button dropdown-toggle-simple fcc-pixels-action-btn <?= !empty($data->pixels) || $data->filters->has_applied_filters ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip data-html="true" title="<?= l('global.filters.tooltip') ?>" data-tooltip-hide-on-click>
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
                                    <option value="pixel" <?= $data->filters->search_by == 'pixel' ? 'selected="selected"' : null ?>><?= l('pixels.pixel') ?></option>
                                </select>
                            </div>

                            <div class="form-group px-4">
                                <label for="filters_type" class="small"><?= l('global.type') ?></label>
                                <select name="type" id="filters_type" class="custom-select custom-select-sm">
                                    <option value=""><?= l('global.all') ?></option>
                                    <?php foreach(require APP_PATH . 'includes/pixels.php' as $pixel_key => $pixel): ?>
                                        <option value="<?= $pixel_key ?>" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == $pixel_key ? 'selected="selected"' : null ?>><?= $pixel['name'] ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="form-group px-4">
                                <label for="filters_order_by" class="small"><?= l('global.filters.order_by') ?></label>
                                <select name="order_by" id="filters_order_by" class="custom-select custom-select-sm">
                                    <option value="pixel_id" <?= $data->filters->order_by == 'pixel_id' ? 'selected="selected"' : null ?>><?= l('global.id') ?></option>
                                    <option value="datetime" <?= $data->filters->order_by == 'datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_datetime') ?></option>
                                    <option value="last_datetime" <?= $data->filters->order_by == 'last_datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_last_datetime') ?></option>
                                    <option value="name" <?= $data->filters->order_by == 'name' ? 'selected="selected"' : null ?>><?= l('global.name') ?></option>
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
                <button id="bulk_enable" type="button" class="btn btn-light fcc-pixels-action-btn" data-toggle="tooltip" title="<?= l('global.bulk_actions') ?>"><i class="fas fa-fw fa-sm fa-list"></i></button>

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
    </div>

    <?php if (!empty($data->pixels)): ?>
        <?php $available_pixels = require APP_PATH . 'includes/pixels.php'; ?>
        <form id="table" action="<?= SITE_URL . 'pixels/bulk' ?>" method="post" role="form">
            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
            <input type="hidden" name="type" value="" data-bulk-type />
            <input type="hidden" name="original_request" value="<?= base64_encode(\Altum\Router::$original_request) ?>" />
            <input type="hidden" name="original_request_query" value="<?= base64_encode(\Altum\Router::$original_request_query) ?>" />

            <div class="fcc-pixels-table-card">
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
                        <th><?= l('global.name') ?></th>
                        <th><?= l('global.type') ?></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach($data->pixels as $row): ?>

                        <tr>
                            <td data-bulk-table class="d-none">
                                <div class="custom-control custom-checkbox">
                                    <input id="selected_pixel_id_<?= $row->pixel_id ?>" type="checkbox" class="custom-control-input" name="selected[]" value="<?= $row->pixel_id ?>" />
                                    <label class="custom-control-label" for="selected_pixel_id_<?= $row->pixel_id ?>"></label>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <a href="<?= url('pixel-update/' . $row->pixel_id) ?>"><?= $row->name ?></a>
                            </td>

                            <td class="text-nowrap">
                            <span class="badge badge-light">
                                <i class="<?= $available_pixels[$row->type]['icon'] ?> fa-fw fa-sm mr-1" style="color: <?= $available_pixels[$row->type]['color'] ?>"></i>
                                <?= $available_pixels[$row->type]['name'] ?>
                            </span>
                            </td>

                            <td class="text-nowrap text-muted">
                                <a href="<?= url('links?pixels_ids=' . $row->pixel_id) ?>" class="mr-2" data-toggle="tooltip" title="<?= l('links.title') ?>">
                                    <i class="fas fa-fw fa-link text-muted"></i>
                                </a>
                            </td>

                            <td class="text-nowrap text-muted">
                            <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.datetime_tooltip'), '<br />' . \Altum\Date::get($row->datetime, 2) . '<br /><small>' . \Altum\Date::get($row->datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->datetime) . ')</small>') ?>">
                                <i class="fas fa-fw fa-calendar text-muted"></i>
                            </span>

                                <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.last_datetime_tooltip'), ($row->last_datetime ? '<br />' . \Altum\Date::get($row->last_datetime, 2) . '<br /><small>' . \Altum\Date::get($row->last_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->last_datetime) . ')</small>' : '<br />' . l('global.na'))) ?>">
                                <i class="fas fa-fw fa-history text-muted"></i>
                            </span>
                            </td>

                            <td>
                                <div class="d-flex justify-content-end">
                                    <?= include_view(THEME_PATH . 'views/pixels/pixel_dropdown_button.php', ['id' => $row->pixel_id, 'resource_name' => $row->name]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    </tbody>
                </table>
            </div>
            </div>
        </form>

        <div class="fcc-pixels-pagination"><?= $data->pagination ?></div>

    <?php else: ?>

        <div class="fcc-pixels-empty">
        <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
            'filters_get' => $data->filters->get ?? [],
            'name' => 'pixels',
            'has_secondary_text' => true,
        ]); ?>
        </div>

    <?php endif ?>

</div>
</div>

<?php require THEME_PATH . 'views/partials/js_bulk.php' ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_delete_modal.php'), 'modals'); ?>
