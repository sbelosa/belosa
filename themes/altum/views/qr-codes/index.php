<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_qr_tour_storage_key = 'fcc_qr_codes_tour_seen_v1';
$fcc_qr_tour_steps = [
    ['selector' => '#fcc_qr_tour_step_create', 'title' => l('qr_codes.tour.create_title'), 'text' => l('qr_codes.tour.create_text')],
    ['selector' => '#fcc_qr_tour_step_guide', 'title' => l('qr_codes.tour.guide_title'), 'text' => l('qr_codes.tour.guide_text')],
    ['selector' => '#fcc_qr_tour_step_list', 'title' => l('qr_codes.tour.list_title'), 'text' => l('qr_codes.tour.list_text')],
    ['selector' => '#fcc_qr_tour_step_row', 'title' => l('qr_codes.tour.row_title'), 'text' => l('qr_codes.tour.row_text')],
    ['selector' => '#fcc_qr_tour_step_actions', 'title' => l('qr_codes.tour.actions_title'), 'text' => l('qr_codes.tour.actions_text')],
];
?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <style>
        .fcc-qr-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .fcc-qr-header {
            background:
                radial-gradient(circle at top right, rgba(84, 191, 255, 0.16), transparent 32%),
                linear-gradient(180deg, rgba(17, 28, 41, 0.94) 0%, rgba(13, 20, 31, 0.98) 100%);
            border: 1px solid rgba(117, 214, 255, 0.12);
            border-radius: 22px;
            padding: 1.35rem 1.4rem;
            box-shadow: 0 18px 40px rgba(4, 10, 24, 0.22);
        }

        .dashboard-page-guide-rail {
            display: flex;
            justify-content: flex-end;
            margin: 0 0 .72rem;
        }

        .dashboard-page-guide-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .48rem;
            padding: .68rem .98rem;
            min-height: 2.7rem;
            border-radius: .95rem;
            border: 1px solid rgba(111, 244, 228, .28);
            background: linear-gradient(135deg, rgba(42, 215, 199, .14) 0%, rgba(29, 122, 209, .12) 100%);
            color: #eefdfb;
            font-size: .86rem;
            font-weight: 750;
            line-height: 1.1;
            text-decoration: none !important;
            box-shadow: 0 12px 24px rgba(4, 14, 25, .14), inset 0 1px 0 rgba(255,255,255,.06);
            transition: all .18s ease;
        }

        .dashboard-page-guide-trigger i {
            color: #8cf6e9;
            font-size: .92em;
        }

        .dashboard-page-guide-trigger:hover,
        .dashboard-page-guide-trigger:focus {
            color: #ffffff;
            border-color: rgba(111, 244, 228, .42);
            background: linear-gradient(135deg, rgba(44, 214, 199, .2) 0%, rgba(41, 126, 212, .18) 100%);
            box-shadow: 0 16px 30px rgba(63, 215, 199, .12);
            transform: translateY(-1px);
            outline: none;
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
            background: linear-gradient(135deg, rgba(110, 205, 255, 0.18) 0%, rgba(76, 240, 218, 0.3) 100%);
            color: #b8f3ff;
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

        .fcc-qr-heading-copy p {
            margin: .28rem 0 0;
            color: #9cb4c8;
            max-width: 62ch;
            line-height: 1.62;
        }

        .fcc-qr-guide {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(91, 181, 255, 0.18), transparent 30%),
                linear-gradient(180deg, rgba(18, 29, 43, 0.97) 0%, rgba(12, 19, 29, 0.98) 100%);
            border: 1px solid rgba(117, 214, 255, 0.12);
            border-radius: 24px;
            padding: 1.35rem;
            box-shadow: 0 22px 48px rgba(4, 10, 24, .2);
        }

        .fcc-qr-guide-copy h2 {
            margin: 0 0 .6rem;
            color: #f4fbff;
            font-size: clamp(1.45rem, 2.3vw, 2.05rem);
            line-height: 1.05;
            letter-spacing: -.045em;
            font-weight: 900;
            max-width: 16ch;
        }

        .fcc-qr-guide-copy p {
            margin: 0;
            color: #c7d9e8;
            line-height: 1.72;
            max-width: 74ch;
        }

        .fcc-qr-guide-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .9rem;
            margin-top: 1.1rem;
        }

        .fcc-qr-guide-card {
            border-radius: 18px;
            border: 1px solid rgba(117, 214, 255, 0.14);
            background: linear-gradient(180deg, rgba(15, 24, 35, 0.94) 0%, rgba(12, 18, 27, 0.98) 100%);
            padding: 1rem 1.02rem;
        }

        .fcc-qr-guide-card strong {
            display: block;
            margin-bottom: .36rem;
            color: #f6fbff;
            font-size: .95rem;
            font-weight: 760;
        }

        .fcc-qr-guide-card span {
            color: #9fb4c5;
            font-size: .9rem;
            line-height: 1.62;
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
            background: linear-gradient(135deg, #58a8ff 0%, #5ce8d5 100%);
            border-color: transparent;
            color: #081d2d;
        }

        .fcc-qr-action-btn.btn-primary:hover {
            color: #051622;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(88, 168, 255, 0.24) !important;
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
            background: linear-gradient(180deg, rgba(16, 23, 34, 0.98) 0%, rgba(12, 17, 26, 0.98) 100%);
            border: 1px solid rgba(117, 214, 255, 0.08);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 20px 44px rgba(4, 10, 24, 0.18);
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
            border-bottom-color: rgba(117, 214, 255, 0.08);
        }

        .fcc-qr-table-card .table-custom td {
            background: transparent;
            color: #dce7e8;
            border-top-color: rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }

        .fcc-qr-table-card .table-custom tbody tr:hover {
            background: rgba(117, 214, 255, 0.04);
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

        .fcc-qr-tour-active-target {
            position: relative !important;
            z-index: 2052 !important;
            isolation: isolate;
            transform: translateZ(0);
            filter: brightness(1.06) saturate(1.04);
            box-shadow: 0 0 0 2px rgba(110, 205, 255, .98), 0 0 0 10px rgba(137, 223, 255, .17), 0 18px 54px rgba(7, 19, 38, .34) !important;
            border-radius: 1.35rem !important;
        }

        .fcc-qr-tour-ancestor {
            position: relative !important;
            z-index: 2051 !important;
            overflow: visible !important;
        }

        .fcc-qr-tour-backdrop {
            position: fixed;
            inset: 0;
            z-index: 2050;
            display: none;
            pointer-events: none;
        }

        .fcc-qr-tour-backdrop.is-visible {
            display: block;
        }

        .fcc-qr-tour-backdrop-segment {
            position: fixed;
            background: rgba(2, 8, 23, .6);
            backdrop-filter: blur(3px);
            pointer-events: none;
        }

        .fcc-qr-tour-popover {
            position: fixed;
            z-index: 2055;
            width: min(25rem, calc(100vw - 2rem));
            display: none;
            border-radius: 1.2rem;
            border: 1px solid rgba(110, 205, 255, .22);
            background:
                radial-gradient(circle at top right, rgba(110, 205, 255, .16), transparent 30%),
                linear-gradient(180deg, rgba(16, 28, 46, .98), rgba(12, 20, 34, .97));
            box-shadow: 0 30px 80px rgba(2, 8, 23, .44), inset 0 1px 0 rgba(255,255,255,.05);
            padding: 1.05rem 1.05rem 1rem;
        }

        .fcc-qr-tour-popover.is-visible {
            display: block;
        }

        .fcc-qr-tour-progress {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            background: rgba(110, 205, 255, .18);
            color: #eefbff;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: .75rem;
            border: 1px solid rgba(110, 205, 255, .16);
        }

        .fcc-qr-tour-title {
            color: #f8fbff;
            font-size: 1.12rem;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: .45rem;
        }

        .fcc-qr-tour-text {
            color: rgba(236, 244, 255, .94);
            font-size: .94rem;
            line-height: 1.65;
            margin-bottom: 1rem;
        }

        .fcc-qr-tour-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }

        .fcc-qr-tour-actions-main {
            display: flex;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .fcc-qr-tour-actions .btn {
            border-radius: .85rem;
        }

        .fcc-qr-tour-actions .btn-link {
            color: rgba(226, 232, 240, .82) !important;
            text-decoration: none;
        }

        .fcc-qr-tour-actions .btn-outline-light {
            color: #ecf8ff !important;
            border-color: rgba(110, 205, 255, .28) !important;
            background: rgba(88, 168, 255, .12) !important;
        }

        .fcc-qr-pagination {
            margin-top: 1rem;
            padding: 0 0.35rem;
        }

        @media (max-width: 1199px) {
            .fcc-qr-guide-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .fcc-qr-guide-grid {
                grid-template-columns: 1fr;
            }

            .fcc-qr-tour-popover {
                left: 1rem !important;
                right: 1rem !important;
                width: auto;
                top: auto !important;
                bottom: 1rem;
            }
        }
    </style>

    <div class="fcc-qr-shell">

    <?php if($this->user->plan_settings->qr_codes_limit != -1 && $data->total_qr_codes > $this->user->plan_settings->qr_codes_limit): ?>
        <div class="alert alert-danger">
            <i class="fas fa-fw fa-times-circle text-danger mr-2"></i> <?= sprintf(settings()->payment->is_enabled ? l('global.info_message.plan_feature_limit_removal_with_upgrade') : l('global.info_message.plan_feature_limit_removal'), '<strong>' . $data->total_qr_codes - $this->user->plan_settings->qr_codes_limit, mb_strtolower(l('qr_codes.title')) . '</strong>', '<a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '</a>') ?>
        </div>
    <?php endif ?>

    <div class="dashboard-page-guide-rail">
        <a href="#" class="dashboard-page-guide-trigger" id="fcc_qr_tour_step_intro" data-fcc-start-qr-tour>
            <i class="fas fa-fw fa-compass"></i>
            <span><?= l('dashboard.tour.launch') ?></span>
        </a>
    </div>

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
                    <p><?= l('qr_codes.page_intro') ?></p>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-auto d-flex flex-wrap fcc-qr-toolbar d-print-none">
            <div>
                <?php if($this->user->plan_settings->qr_codes_limit != -1 && $data->total_qr_codes >= $this->user->plan_settings->qr_codes_limit): ?>
                    <button type="button" class="btn btn-primary disabled fcc-qr-action-btn" id="fcc_qr_tour_step_create" <?= get_plan_feature_limit_reached_info() ?>>
                        <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('qr_codes.create') ?>
                    </button>
                <?php else: ?>
                    <a href="<?= url('qr-code-create') ?>" id="fcc_qr_tour_step_create" class="btn btn-primary fcc-qr-action-btn" data-toggle="tooltip" data-html="true" title="<?= get_plan_feature_limit_info($data->total_qr_codes, $this->user->plan_settings->qr_codes_limit, isset($data->filters) ? !$data->filters->has_applied_filters : true) ?>">
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

    <section class="fcc-qr-guide" id="fcc_qr_tour_step_guide">
        <div class="fcc-qr-guide-copy">
            <h2><?= l('qr_codes.guide_title') ?></h2>
            <p><?= l('qr_codes.guide_text') ?></p>
        </div>

        <div class="fcc-qr-guide-grid">
            <article class="fcc-qr-guide-card">
                <strong><?= l('qr_codes.guide_card_1_title') ?></strong>
                <span><?= l('qr_codes.guide_card_1_text') ?></span>
            </article>

            <article class="fcc-qr-guide-card">
                <strong><?= l('qr_codes.guide_card_2_title') ?></strong>
                <span><?= l('qr_codes.guide_card_2_text') ?></span>
            </article>

            <article class="fcc-qr-guide-card">
                <strong><?= l('qr_codes.guide_card_3_title') ?></strong>
                <span><?= l('qr_codes.guide_card_3_text') ?></span>
            </article>

            <article class="fcc-qr-guide-card">
                <strong><?= l('qr_codes.guide_card_4_title') ?></strong>
                <span><?= l('qr_codes.guide_card_4_text') ?></span>
            </article>
        </div>
    </section>

    <?php if (!empty($data->qr_codes)): ?>
        <form id="table" action="<?= SITE_URL . 'qr-codes/bulk' ?>" method="post" role="form">
            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
            <input type="hidden" name="type" value="" data-bulk-type />
            <input type="hidden" name="original_request" value="<?= base64_encode(\Altum\Router::$original_request) ?>" />
            <input type="hidden" name="original_request_query" value="<?= base64_encode(\Altum\Router::$original_request_query) ?>" />

            <div class="fcc-qr-table-card" id="fcc_qr_tour_step_list">
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
                    <?php $fcc_qr_row_index = 0; ?>
                    <?php foreach($data->qr_codes as $row): ?>
                        <tr <?= $fcc_qr_row_index === 0 ? 'id="fcc_qr_tour_step_row"' : null ?>>
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
                                <div class="d-flex justify-content-end" <?= $fcc_qr_row_index === 0 ? 'id="fcc_qr_tour_step_actions"' : null ?>>
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
                        <?php $fcc_qr_row_index++; ?>
                    <?php endforeach ?>

                    </tbody>
                </table>
            </div>
            </div>
        </form>

        <div class="fcc-qr-pagination"><?= $data->pagination ?></div>
    <?php else: ?>

        <div class="fcc-qr-empty">
        <div class="p-4 text-center">
            <h2 class="h5 mb-2 text-white"><?= l('qr_codes.empty_title') ?></h2>
            <p class="text-muted mb-0"><?= l('qr_codes.empty_text') ?></p>
        </div>
        </div>

    <?php endif ?>

</div>
</div>

<div class="fcc-qr-tour-backdrop" id="fcc_qr_tour_backdrop"></div>
<div class="fcc-qr-tour-popover" id="fcc_qr_tour_popover" aria-live="polite">
    <div class="fcc-qr-tour-progress" id="fcc_qr_tour_progress">1 / <?= count($fcc_qr_tour_steps) ?></div>
    <div class="fcc-qr-tour-title" id="fcc_qr_tour_title"></div>
    <div class="fcc-qr-tour-text" id="fcc_qr_tour_text"></div>
    <div class="fcc-qr-tour-actions">
        <button type="button" class="btn btn-link text-muted px-0" id="fcc_qr_tour_skip"><?= l('dashboard.tour.skip') ?></button>
        <div class="fcc-qr-tour-actions-main">
            <button type="button" class="btn btn-outline-light" id="fcc_qr_tour_prev"><?= l('dashboard.tour.prev') ?></button>
            <button type="button" class="btn btn-primary" id="fcc_qr_tour_next"><?= l('dashboard.tour.next') ?></button>
        </div>
    </div>
</div>

<?php require THEME_PATH . 'views/qr-codes/js_qr_codes.php' ?>
<?php require THEME_PATH . 'views/partials/js_bulk.php' ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_delete_modal.php'), 'modals'); ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_download_modal.php'), 'modals'); ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const storageKey = <?= json_encode($fcc_qr_tour_storage_key) ?>;
    const steps = <?= json_encode($fcc_qr_tour_steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const backdrop = document.getElementById('fcc_qr_tour_backdrop');
    const popover = document.getElementById('fcc_qr_tour_popover');
    const title = document.getElementById('fcc_qr_tour_title');
    const text = document.getElementById('fcc_qr_tour_text');
    const progress = document.getElementById('fcc_qr_tour_progress');
    const prevButton = document.getElementById('fcc_qr_tour_prev');
    const nextButton = document.getElementById('fcc_qr_tour_next');
    const skipButton = document.getElementById('fcc_qr_tour_skip');

    if(!backdrop || !popover || !title || !text || !progress || !prevButton || !nextButton || !skipButton || !Array.isArray(steps) || !steps.length) {
        return;
    }

    let activeStep = -1;
    let currentTarget = null;
    let elevatedAncestors = [];
    let backdropSegments = [];

    const setTourMode = isActive => {
        document.body.classList.toggle('fcc-tour-mode', !!isActive);

        if(typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent('fcc:tutorial:state', {
                detail: {active: !!isActive}
            }));
        }
    };

    const ensureBackdropSegments = () => {
        if(backdropSegments.length) return backdropSegments;

        backdropSegments = Array.from({length: 4}, () => {
            const segment = document.createElement('div');
            segment.className = 'fcc-qr-tour-backdrop-segment';
            backdrop.appendChild(segment);
            return segment;
        });

        return backdropSegments;
    };

    const getElevatedAncestors = target => {
        const ancestors = [];
        let node = target?.parentElement ?? null;

        while(node && node !== document.body) {
            const computedStyle = window.getComputedStyle(node);
            const hasClippingOverflow = ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflow) || ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflowX) || ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflowY);
            const shouldElevate = hasClippingOverflow;

            if(shouldElevate) {
                ancestors.push(node);
            }

            node = node.parentElement;
        }

        return ancestors;
    };

    const clearHighlight = () => {
        if(currentTarget) {
            currentTarget.classList.remove('fcc-qr-tour-active-target');
        }

        elevatedAncestors.forEach(node => node.classList.remove('fcc-qr-tour-ancestor'));
        elevatedAncestors = [];

        currentTarget = null;
    };

    const placePopover = () => {
        if(!currentTarget || !popover.classList.contains('is-visible')) {
            return;
        }

        const rect = currentTarget.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const popoverWidth = popover.offsetWidth;
        const popoverHeight = popover.offsetHeight;
        const spacing = 18;

        let top = rect.bottom + spacing;
        let left = rect.left;

        if(top + popoverHeight > viewportHeight - spacing) {
            top = Math.max(spacing, rect.top - popoverHeight - spacing);
        }

        if(left + popoverWidth > viewportWidth - spacing) {
            left = Math.max(spacing, viewportWidth - popoverWidth - spacing);
        }

        if(left < spacing) {
            left = spacing;
        }

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
    };

    const updateBackdropSpotlight = () => {
        if(!currentTarget || !backdrop.classList.contains('is-visible')) return;

        const segments = ensureBackdropSegments();
        const rect = currentTarget.getBoundingClientRect();
        const padding = 10;
        const top = Math.max(0, rect.top - padding);
        const left = Math.max(0, rect.left - padding);
        const right = Math.min(window.innerWidth, rect.right + padding);
        const bottom = Math.min(window.innerHeight, rect.bottom + padding);
        const holeHeight = Math.max(0, bottom - top);

        Object.assign(segments[0].style, {top: '0px', left: '0px', width: '100vw', height: `${top}px`});
        Object.assign(segments[1].style, {top: `${top}px`, left: '0px', width: `${left}px`, height: `${holeHeight}px`});
        Object.assign(segments[2].style, {top: `${top}px`, left: `${right}px`, width: `${Math.max(0, window.innerWidth - right)}px`, height: `${holeHeight}px`});
        Object.assign(segments[3].style, {top: `${bottom}px`, left: '0px', width: '100vw', height: `${Math.max(0, window.innerHeight - bottom)}px`});
    };

    const endTour = completed => {
        clearHighlight();
        activeStep = -1;
        setTourMode(false);
        backdrop.classList.remove('is-visible');
        popover.classList.remove('is-visible');

        if(completed) {
            localStorage.setItem(storageKey, '1');
        }
    };

    const renderStep = index => {
        const step = steps[index];
        if(!step) {
            endTour(false);
            return;
        }

        const target = document.querySelector(step.selector);
        if(!target) {
            if(index >= steps.length - 1) {
                endTour(true);
                return;
            }

            renderStep(index + 1);
            return;
        }

        activeStep = index;
        clearHighlight();
        currentTarget = target;
        elevatedAncestors = getElevatedAncestors(currentTarget);
        elevatedAncestors.forEach(node => node.classList.add('fcc-qr-tour-ancestor'));
        currentTarget.classList.add('fcc-qr-tour-active-target');
        currentTarget.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'});

        title.textContent = step.title || '';
        text.textContent = step.text || '';
        progress.textContent = `${index + 1} / ${steps.length}`;
        prevButton.style.visibility = index === 0 ? 'hidden' : 'visible';
        nextButton.textContent = index === steps.length - 1 ? <?= json_encode(l('dashboard.tour.finish')) ?> : <?= json_encode(l('dashboard.tour.next')) ?>;

        backdrop.classList.add('is-visible');
        popover.classList.add('is-visible');
        updateBackdropSpotlight();
        setTimeout(placePopover, 140);
    };

    const startTour = ({markAutoSeen = false} = {}) => {
        if(markAutoSeen) {
            localStorage.setItem(storageKey, '1');
        }

        setTourMode(true);
        renderStep(0);
    };

    document.querySelectorAll('[data-fcc-start-qr-tour]').forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            startTour();
        });
    });

    skipButton.addEventListener('click', () => endTour(false));
    prevButton.addEventListener('click', () => {
        if(activeStep > 0) {
            renderStep(activeStep - 1);
        }
    });
    nextButton.addEventListener('click', () => {
        if(activeStep >= steps.length - 1) {
            endTour(true);
            return;
        }

        renderStep(activeStep + 1);
    });

    const syncOverlay = () => {
        placePopover();
        updateBackdropSpotlight();
    };

    window.addEventListener('resize', syncOverlay);
    window.addEventListener('scroll', syncOverlay, {passive: true});

    if(!localStorage.getItem(storageKey)) {
        setTimeout(() => startTour({markAutoSeen: true}), 500);
    }
});
</script>
