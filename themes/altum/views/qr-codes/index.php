<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <style>
        .fcc-qr-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .fcc-qr-header {
            background: linear-gradient(180deg, rgba(19, 27, 29, 0.92) 0%, rgba(15, 21, 23, 0.98) 100%);
            border: 1px solid rgba(127, 227, 217, 0.08);
            border-radius: 22px;
            padding: 1.35rem 1.4rem;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
        }

        .fcc-qr-heading {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }

        .fcc-qr-heading-icon {
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

        .fcc-qr-heading-copy {
            min-width: 0;
        }

        .fcc-qr-heading-copy h1 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #f5fbfb;
        }

        .fcc-qr-toolbar {
            gap: 0.75rem;
        }

        .fcc-qr-action-btn {
            border-radius: 14px;
            min-height: 2.75rem;
            font-weight: 600;
            box-shadow: none !important;
        }

        .fcc-qr-action-btn.btn-primary {
            background: linear-gradient(135deg, #3fd7c7 0%, #6de9dd 100%);
            border-color: transparent;
            color: #082826;
        }

        .fcc-qr-action-btn.btn-primary:hover {
            color: #041b19;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(63, 215, 199, 0.2) !important;
        }

        .fcc-qr-action-btn.btn-light,
        .fcc-qr-action-btn.btn-dark {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.06);
            color: #d5e1e2;
        }

        .fcc-qr-action-btn.btn-light:hover,
        .fcc-qr-action-btn.btn-dark:hover {
            background: rgba(127, 227, 217, 0.1);
            border-color: rgba(127, 227, 217, 0.16);
            color: #f5fbfb;
        }

        .fcc-qr-table-card,
        .fcc-qr-empty {
            background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
            border: 1px solid rgba(127, 227, 217, 0.07);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 20px 44px rgba(0, 0, 0, 0.16);
        }

        .fcc-qr-table-card .table-custom-container {
            border: 0;
            background: transparent;
        }

        .fcc-qr-table-card .table-custom {
            margin-bottom: 0;
        }

        .fcc-qr-table-card .table-custom thead th {
            background: rgba(255, 255, 255, 0.02);
            color: #e9f7f5;
            border-bottom-color: rgba(127, 227, 217, 0.08);
        }

        .fcc-qr-table-card .table-custom td {
            background: transparent;
            color: #dce7e8;
            border-top-color: rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }

        .fcc-qr-table-card .table-custom tbody tr:hover {
            background: rgba(127, 227, 217, 0.035);
        }

        .fcc-qr-table-card .badge.badge-light {
            background: rgba(255, 255, 255, 0.08);
            color: #d6e5e4;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .fcc-qr-table-card .btn.btn-link {
            color: #93aaab;
        }

        .fcc-qr-table-card .btn.btn-link:hover {
            color: #d8fffb;
            text-decoration: none;
        }

        .fcc-qr-pagination {
            margin-top: 1rem;
            padding: 0 0.35rem;
        }
    </style>

    <div class="fcc-qr-shell">

    <?php if($this->user->plan_settings->qr_codes_limit != -1 && $data->total_qr_codes > $this->user->plan_settings->qr_codes_limit): ?>
        <div class="alert alert-danger">
            <i class="fas fa-fw fa-times-circle text-danger mr-2"></i> <?= sprintf(settings()->payment->is_enabled ? l('global.info_message.plan_feature_limit_removal_with_upgrade') : l('global.info_message.plan_feature_limit_removal'), '<strong>' . $data->total_qr_codes - $this->user->plan_settings->qr_codes_limit, mb_strtolower(l('qr_codes.title')) . '</strong>', '<a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '</a>') ?>
        </div>
    <?php endif ?>

    <div class="fcc-qr-header">
    <div class="row align-items-center">
        <div class="col-12 col-lg mb-4 mb-lg-0 text-truncate">
            <div class="fcc-qr-heading">
                <div class="fcc-qr-heading-icon">
                    <i class="fas fa-fw fa-qrcode"></i>
                </div>

                <div class="fcc-qr-heading-copy">
                    <h1 class="text-truncate">
                        <?= l('qr_codes.header') ?>
                        <span class="ml-1" data-toggle="tooltip" title="<?= l('qr_codes.subheader') ?>">
                            <i class="fas fa-fw fa-info-circle text-muted"></i>
                        </span>
                    </h1>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-auto d-flex flex-wrap fcc-qr-toolbar d-print-none">
            <div>
                <?php if($this->user->plan_settings->qr_codes_limit != -1 && $data->total_qr_codes >= $this->user->plan_settings->qr_codes_limit): ?>
                    <button type="button" class="btn btn-primary disabled fcc-qr-action-btn" <?= get_plan_feature_limit_reached_info() ?>>
                        <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('qr_codes.create') ?>
                    </button>
                <?php else: ?>
                    <a href="<?= url('qr-code-create') ?>" class="btn btn-primary fcc-qr-action-btn" data-toggle="tooltip" data-html="true" title="<?= get_plan_feature_limit_info($data->total_qr_codes, $this->user->plan_settings->qr_codes_limit, isset($data->filters) ? !$data->filters->has_applied_filters : true) ?>">
                        <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('qr_codes.create') ?>
                    </a>
                <?php endif ?>
            </div>

            <div>
                <div class="dropdown">
                    <button type="button" class="btn btn-light dropdown-toggle-simple fcc-qr-action-btn <?= !empty($data->qr_codes) ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip title="<?= l('global.export') ?>" data-tooltip-hide-on-click>
                        <i class="fas fa-fw fa-sm fa-download"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right d-print-none">
                        <a href="<?= url('qr-codes?' . $data->filters->get_get() . '&export=csv')  ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->csv ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->csv ? null : get_plan_feature_disabled_info() ?>>
                            <i class="fas fa-fw fa-sm fa-file-csv mr-2"></i> <?= sprintf(l('global.export_to'), 'CSV') ?>
                        </a>
                        <a href="<?= url('qr-codes?' . $data->filters->get_get() . '&export=json') ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->json ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->json ? null : get_plan_feature_disabled_info() ?>>
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
                    <button type="button" class="btn <?= $data->filters->has_applied_filters ? 'btn-dark' : 'btn-light' ?> filters-button dropdown-toggle-simple fcc-qr-action-btn <?= !empty($data->qr_codes) || $data->filters->has_applied_filters ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip data-html="true" title="<?= l('global.filters.tooltip') ?>" data-tooltip-hide-on-click>
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
                                </select>
                            </div>

                            <?php if(settings()->links->projects_is_enabled): ?>
                                <div class="form-group px-4">
                                    <div class="d-flex justify-content-between">
                                        <label for="filters_project_id" class="small"><?= l('projects.project_id') ?></label>
                                        <a href="<?= url('projects') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('global.create') ?></a>
                                    </div>
                                    <select name="project_id" id="filters_project_id" class="custom-select custom-select-sm">
                                        <option value=""><?= l('global.all') ?></option>
                                        <?php foreach($data->projects as $row): ?>
                                            <option value="<?= $row->project_id ?>" <?= isset($data->filters->filters['project_id']) && $data->filters->filters['project_id'] == $row->project_id ? 'selected="selected"' : null ?>><?= $row->name ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            <?php endif ?>

                            <div class="form-group px-4">
                                <label for="filters_type" class="small"><?= l('global.type') ?></label>
                                <select name="type" id="filters_type" class="custom-select custom-select-sm">
                                    <option value=""><?= l('global.all') ?></option>
                                    <?php foreach(array_keys((require APP_PATH . 'includes/enabled_qr_codes.php')) as $type): ?>
                                        <option value="<?= $type ?>" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == $type ? 'selected="selected"' : null ?>><?= $data->available_qr_codes[$type]['emoji'] . ' ' . l('qr_codes.type.' . $type) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="form-group px-4">
                                <label for="filters_order_by" class="small"><?= l('global.filters.order_by') ?></label>
                                <select name="order_by" id="filters_order_by" class="custom-select custom-select-sm">
                                    <option value="qr_code_id" <?= $data->filters->order_by == 'qr_code_id' ? 'selected="selected"' : null ?>><?= l('global.id') ?></option>
                                    <option value="datetime" <?= $data->filters->order_by == 'datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_datetime') ?></option>
                                    <option value="last_datetime" <?= $data->filters->order_by == 'last_datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_last_datetime') ?></option>
                                    <option value="name" <?= $data->filters->order_by == 'name' ? 'selected="selected"' : null ?>><?= l('global.name') ?></option>
                                    <option value="type" <?= $data->filters->order_by == 'type' ? 'selected="selected"' : null ?>><?= l('global.type') ?></option>
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
                <button id="bulk_enable" type="button" class="btn btn-light fcc-qr-action-btn" data-toggle="tooltip" title="<?= l('global.bulk_actions') ?>"><i class="fas fa-fw fa-sm fa-list"></i></button>

                <div id="bulk_group" class="btn-group d-none" role="group">
                    <div class="btn-group dropdown" role="group">
                        <button id="bulk_actions" type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false">
                            <?= l('global.bulk_actions') ?> <span id="bulk_counter" class="d-none"></span>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="bulk_actions">
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#bulk_download_modal"><i class="fas fa-fw fa-sm fa-download mr-2"></i> <?= l('global.download') ?></a>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#bulk_delete_modal"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>
                        </div>
                    </div>

                    <button id="bulk_disable" type="button" class="btn btn-secondary" data-toggle="tooltip" title="<?= l('global.close') ?>"><i class="fas fa-fw fa-times"></i></button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php if (!empty($data->qr_codes)): ?>
        <form id="table" action="<?= SITE_URL . 'qr-codes/bulk' ?>" method="post" role="form">
            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
            <input type="hidden" name="type" value="" data-bulk-type />
            <input type="hidden" name="original_request" value="<?= base64_encode(\Altum\Router::$original_request) ?>" />
            <input type="hidden" name="original_request_query" value="<?= base64_encode(\Altum\Router::$original_request_query) ?>" />

            <div class="fcc-qr-table-card">
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
                        <?php if(settings()->links->projects_is_enabled): ?>
                            <th></th>
                        <?php endif ?>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach($data->qr_codes as $row): ?>
                        <tr>
                            <td data-bulk-table class="d-none">
                                <div class="custom-control custom-checkbox">
                                    <input id="selected_qr_code_id_<?= $row->qr_code_id ?>" type="checkbox" class="custom-control-input" name="selected[]" value="<?= $row->qr_code_id ?>" />
                                    <label class="custom-control-label" for="selected_qr_code_id_<?= $row->qr_code_id ?>"></label>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3" data-toggle="tooltip" title="<?= l('global.download') ?>">
                                        <a href="<?= \Altum\Uploads::get_full_url('qr_code') . $row->qr_code ?>" download="<?= $row->name . '.svg' ?>" target="_blank">
                                            <img src="<?= \Altum\Uploads::get_full_url('qr_code') . $row->qr_code ?>" class="qr-code-avatar" loading="lazy" />
                                        </a>
                                    </div>

                                    <div class="d-flex flex-column">
                                        <div>
                                            <a href="<?= url('qr-code-update/' . $row->qr_code_id) ?>" class="font-weight-500 text-truncate"><?= $row->name ?></a>
                                        </div>
                                        <?php if($row->type == 'url'): ?>
                                            <div class="d-flex align-items-center">
                                                <small class="d-inline-block text-truncate text-muted">
                                                    <?= remove_url_protocol_from_url($row->settings->url) ?>
                                                </small>

                                                <?php if($row->link_id): ?>
                                                    <a href="<?= url('link/' . $row->link_id) ?>" class="btn btn-sm btn-link" data-toggle="tooltip" title="<?= l('global.update') ?>"><i class="fas fa-fw fa-pencil-alt"></i></a>
                                                    <a href="<?= url('link/' . $row->link_id . '/statistics') ?>" class="btn btn-sm btn-link" data-toggle="tooltip" title="<?= l('link.statistics.pageviews') ?>"><i class="fas fa-fw fa-chart-bar"></i></a>
                                                <?php endif ?>
                                            </div>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <span class="badge badge-light">
                                    <i class="<?= $data->available_qr_codes[$row->type]['icon'] ?> fa-fw fa-sm mr-1"></i>
                                    <?= l('qr_codes.type.' . $row->type) ?>
                                </span>
                            </td>

                            <?php if(settings()->links->projects_is_enabled): ?>
                                <td class="text-nowrap">
                                    <?php if($row->project_id): ?>
                                        <a href="<?= url('qr-codes?project_id=' . $row->project_id) ?>" class="text-decoration-none" data-toggle="tooltip" title="<?= l('projects.project_id') ?>">
                                            <span class="badge badge-light" style="color: <?= $data->projects[$row->project_id]->color ?> !important;">
                                                <?= $data->projects[$row->project_id]->name ?>
                                            </span>
                                        </a>
                                    <?php endif ?>
                                </td>
                            <?php endif ?>

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
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-block btn-link dropdown-toggle dropdown-toggle-simple" title="<?= l('global.download') ?>" data-toggle="dropdown" aria-expanded="false" data-tooltip data-tooltip-hide-on-click>
                                            <i class="fas fa-fw fa-sm fa-download"></i>
                                        </button>

                                        <div class="dropdown-menu">
                                            <a href="<?= \Altum\Uploads::get_full_url('qr_code') . $row->qr_code ?>" class="dropdown-item" download="<?= get_slug($row->name) . '.svg' ?>"><?= sprintf(l('global.download_as'), 'SVG') ?></a>
                                            <button type="button" class="dropdown-item" onclick="convert_svg_qr_code_to_others('<?= \Altum\Uploads::get_full_url('qr_code') . $row->qr_code ?>', 'png', '<?= get_slug($row->name) . '.png' ?>');"><?= sprintf(l('global.download_as'), 'PNG') ?></button>
                                            <button type="button" class="dropdown-item" onclick="convert_svg_qr_code_to_others('<?= \Altum\Uploads::get_full_url('qr_code') . $row->qr_code ?>', 'jpg', '<?= get_slug($row->name) . '.jpg' ?>');"><?= sprintf(l('global.download_as'), 'JPG') ?></button>
                                            <button type="button" class="dropdown-item" onclick="convert_svg_qr_code_to_others('<?= \Altum\Uploads::get_full_url('qr_code') . $row->qr_code ?>', 'webp', '<?= get_slug($row->name) . '.webp' ?>');"><?= sprintf(l('global.download_as'), 'WEBP') ?></button>
                                        </div>
                                    </div>

                                    <?= include_view(THEME_PATH . 'views/qr-codes/qr_code_dropdown_button.php', ['id' => $row->qr_code_id, 'resource_name' => $row->name]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    </tbody>
                </table>
            </div>
            </div>
        </form>

        <div class="fcc-qr-pagination"><?= $data->pagination ?></div>
    <?php else: ?>

        <div class="fcc-qr-empty">
        <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                'filters_get' => $data->filters->get ?? [],
                'name' => 'qr_codes',
                'has_secondary_text' => true,
        ]); ?>
        </div>

    <?php endif ?>

</div>
</div>

<?php require THEME_PATH . 'views/qr-codes/js_qr_codes.php' ?>
<?php require THEME_PATH . 'views/partials/js_bulk.php' ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_delete_modal.php'), 'modals'); ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_download_modal.php'), 'modals'); ?>
