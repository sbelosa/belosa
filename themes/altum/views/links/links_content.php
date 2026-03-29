<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_links_is_hr = \Altum\Language::$code === 'hr';
$fcc_main_featured = $data->main_biolink_featured ?? null;
$fcc_main_auto_summary = $data->main_biolink_auto_summary ?? '';
$fcc_main_header = $fcc_links_is_hr ? 'Javni prikaz glavne Forever Card Aplikacije' : 'Public showcase of the main Forever Card App';
$fcc_main_subheader = $fcc_links_is_hr ? 'Ove postavke vrijede samo za glavnu aplikaciju koju si dobio pri registraciji i aktivaciji pristupa. Ostale aplikacije ne ulaze u ovaj javni prikaz.' : 'These settings apply only to the main app assigned when your access was activated. Other apps are not included in this public showcase.';
$fcc_main_toggle = $fcc_links_is_hr ? 'Dopuštam javni prikaz glavne Forever Card Aplikacije' : 'I allow public display of the main Forever Card App';
$fcc_main_market = $fcc_links_is_hr ? 'Javno tržište / država' : 'Public market / country';
$fcc_main_summary = $fcc_links_is_hr ? 'Kratki javni opis' : 'Short public summary';
$fcc_main_summary_help = $fcc_links_is_hr ? 'Opis je opcionalan. Ako ga ne upišeš, FCC će automatski složiti kratak sažetak na temelju aktivnih blokova tvoje glavne aplikacije.' : 'This summary is optional. If you leave it empty, FCC will automatically generate a short public summary based on the active blocks in your main app.';
$fcc_main_detected = $fcc_links_is_hr ? 'FCC je automatski prepoznao' : 'FCC automatically detected';
$fcc_main_status_on = $fcc_links_is_hr ? 'Javni prikaz je uključen.' : 'Public showcase is enabled.';
$fcc_main_status_off = $fcc_links_is_hr ? 'Javni prikaz je trenutno isključen.' : 'Public showcase is currently turned off.';
$fcc_main_admin_hidden = $fcc_links_is_hr ? 'Admin je trenutno isključio javni prikaz ove aplikacije.' : 'Admin has currently hidden this app from the public showcase.';
$fcc_main_submit = $fcc_links_is_hr ? 'Spremi javni prikaz' : 'Save public showcase settings';
?>

<style>
    .fcc-links-shell {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .fcc-links-header {
        background: linear-gradient(180deg, rgba(19, 27, 29, 0.92) 0%, rgba(15, 21, 23, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.08);
        border-radius: 22px;
        padding: 1.35rem 1.4rem;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
    }

    .fcc-links-heading {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }

    .fcc-links-heading-icon {
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

    .fcc-links-heading-copy {
        min-width: 0;
    }

    .fcc-links-heading-copy h1 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: #f5fbfb;
    }

    .fcc-links-toolbar {
        gap: 0.75rem;
    }

    .fcc-links-action-btn {
        border-radius: 14px;
        min-height: 2.75rem;
        font-weight: 600;
        box-shadow: none !important;
    }

    .fcc-links-action-btn.btn-primary {
        background: linear-gradient(135deg, #3fd7c7 0%, #6de9dd 100%);
        border-color: transparent;
        color: #082826;
    }

    .fcc-links-action-btn.btn-primary:hover {
        color: #041b19;
        transform: translateY(-1px);
        box-shadow: 0 10px 24px rgba(63, 215, 199, 0.2) !important;
    }

    .fcc-links-action-btn.btn-outline-primary {
        border-color: rgba(127, 227, 217, 0.34);
        color: #97eee4;
        background: rgba(127, 227, 217, 0.03);
    }

    .fcc-links-action-btn.btn-outline-primary:hover {
        color: #d9fffb;
        border-color: rgba(127, 227, 217, 0.48);
        background: rgba(127, 227, 217, 0.1);
    }

    .fcc-links-action-btn.btn-light,
    .fcc-links-action-btn.btn-dark {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.06);
        color: #d5e1e2;
    }

    .fcc-links-action-btn.btn-light:hover,
    .fcc-links-action-btn.btn-dark:hover {
        background: rgba(127, 227, 217, 0.1);
        border-color: rgba(127, 227, 217, 0.16);
        color: #f5fbfb;
    }

    .fcc-links-table-card {
        background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.07);
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 20px 44px rgba(0, 0, 0, 0.16);
    }

    .fcc-links-table-card .table-custom-container {
        border: 0;
        background: transparent;
    }

    .fcc-links-table-card .table-custom {
        margin-bottom: 0;
    }

    .fcc-links-table-card .table-custom thead th {
        background: rgba(255, 255, 255, 0.02);
        color: #e9f7f5;
        border-bottom-color: rgba(127, 227, 217, 0.08);
    }

    .fcc-links-table-card .table-custom td {
        border-top-color: rgba(255, 255, 255, 0.04);
        vertical-align: middle;
    }

    .fcc-links-table-card .table-custom tbody tr {
        transition: background 0.2s ease;
    }

    .fcc-links-table-card .table-custom tbody tr:hover {
        background: rgba(127, 227, 217, 0.035);
    }

    .fcc-links-table-card .badge.badge-light {
        background: rgba(255, 255, 255, 0.08);
        color: #d6e5e4;
        border: 1px solid rgba(255, 255, 255, 0.04);
    }

    .fcc-links-table-card .btn.btn-link.text-secondary {
        color: #92aaab !important;
    }

    .fcc-links-table-card .btn.btn-link.text-secondary:hover {
        color: #d8fffb !important;
    }

    .fcc-links-pagination {
        margin-top: 1rem;
        padding: 0 0.35rem;
    }

    .fcc-links-empty {
        background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.07);
        border-radius: 22px;
        padding: 0.25rem;
    }

    .fcc-main-app-featured-card {
        background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.07);
        border-radius: 22px;
        padding: 1.35rem 1.4rem;
        box-shadow: 0 20px 44px rgba(0, 0, 0, 0.16);
    }

    .fcc-main-app-featured-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .fcc-main-app-featured-pill,
    .fcc-main-app-featured-tag {
        display: inline-flex;
        align-items: center;
        min-height: 2rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .fcc-main-app-featured-pill {
        background: rgba(255, 255, 255, 0.05);
        color: rgba(240, 244, 251, 0.88);
    }

    .fcc-main-app-featured-tag {
        background: rgba(104, 232, 188, 0.1);
        color: #c9fff2;
    }

    .fcc-main-app-featured-status {
        border-radius: 16px;
        padding: 0.85rem 1rem;
        background: rgba(127, 227, 217, 0.05);
        border: 1px solid rgba(127, 227, 217, 0.08);
        color: #d8f8f3;
    }

    .fcc-main-app-featured-status.is-muted {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.06);
        color: #d5e1e2;
    }

    .fcc-main-app-featured-preview {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 4;
        overflow: hidden;
    }
</style>

<div class="fcc-links-shell">
<div class="fcc-links-header">
<div class="row align-items-center">
    <div class="col-12 col-lg mb-4 mb-lg-0 text-truncate">
        <div class="fcc-links-heading">
            <div class="fcc-links-heading-icon">
                <i class="fas fa-fw <?= isset($data->filters->filters['type']) ? $data->links_types[$data->filters->filters['type']]['icon'] : $data->links_types['link']['icon'] ?>"></i>
            </div>

            <div class="fcc-links-heading-copy">
                <h1 class="text-truncate">
                    <?= isset($data->filters->filters['type']) ? l('links.menu.' . $data->filters->filters['type']) : l('links.header') ?>
                    <span class="ml-1" data-toggle="tooltip" title="<?= l('links.subheader') ?>">
                        <i class="fas fa-fw fa-info-circle text-muted"></i>
                    </span>
                </h1>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-auto d-flex flex-wrap fcc-links-toolbar d-print-none">
        <?php if(isset($data->filters->filters['type'])): ?>
            <div>
                <button type="button" data-toggle="modal" data-target="<?= '#create_' . $data->filters->filters['type'] ?>" class="btn btn-primary fcc-links-action-btn">
                    <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('link.' . $data->filters->filters['type'] . '.name') ?>
                </button>
            </div>

            <?php if(settings()->links->shortener_is_enabled && $data->filters->filters['type'] == 'link'): ?>
                <div>
                    <a href="<?= url('link-create') ?>" class="btn btn-outline-primary fcc-links-action-btn" data-toggle="tooltip" title="<?= l('link_create.menu') ?>">
                        <i class="fas fa-fw fa-upload fa-sm"></i>
                    </a>
                </div>
            <?php endif ?>

            <?php if(settings()->links->biolinks_templates_is_enabled && $data->filters->filters['type'] == 'biolink'): ?>
                <div>
                    <a href="<?= url('biolinks-templates') ?>" class="btn btn-outline-primary fcc-links-action-btn">
                        <i class="fas fa-fw fa-moon fa-sm mr-1"></i> <?= l('biolinks_templates.menu') ?>
                    </a>
                </div>
            <?php endif ?>
        <?php else: ?>
            <div>
                <?php
                $enabled_links = [];
                if(settings()->links->biolinks_is_enabled) $enabled_links[] = 'biolink';
                if(settings()->links->shortener_is_enabled) $enabled_links[] = 'link';
                if(settings()->links->files_is_enabled) $enabled_links[] = 'file';
                if(settings()->links->vcards_is_enabled) $enabled_links[] = 'vcard';
                if(settings()->links->events_is_enabled) $enabled_links[] = 'event';
                if(settings()->links->static_is_enabled) $enabled_links[] = 'static';
                ?>

                <?php if(count($enabled_links) > 1): ?>

                    <div class="dropdown">
                    <button type="button" data-toggle="dropdown" data-boundary="viewport" class="btn btn-primary dropdown-toggle dropdown-toggle-simple fcc-links-action-btn">
                        <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('links.create') ?>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right">
                        <?php if(settings()->links->biolinks_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_biolink">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['biolink']['color'] ?>"></i>

                                <?= l('link.biolink.name') ?>
                            </a>
                        <?php endif ?>

                        <?php if(settings()->links->shortener_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_link">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['link']['color'] ?>"></i>

                                <?= l('link.link.name') ?>
                            </a>
                        <?php endif ?>

                        <?php if(settings()->links->files_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_file">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['file']['color'] ?>"></i>

                                <?= l('link.file.name') ?>
                            </a>
                        <?php endif ?>

                        <?php if(settings()->links->vcards_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_vcard">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['vcard']['color'] ?>"></i>

                                <?= l('link.vcard.name') ?>
                            </a>
                        <?php endif ?>

                        <?php if(settings()->links->events_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_event">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['event']['color'] ?>"></i>

                                <?= l('link.event.name') ?>
                            </a>
                        <?php endif ?>

                        <?php if(settings()->links->static_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_static">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['static']['color'] ?>"></i>

                                <?= l('link.static.name') ?>
                            </a>
                        <?php endif ?>
                    </div>
                </div>

                <?php elseif(count($enabled_links) == 1): ?>

                    <div>
                        <button type="button" data-toggle="modal" data-target="<?= '#create_' . reset($enabled_links) ?>" class="btn btn-primary fcc-links-action-btn">
                            <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('link.' . reset($enabled_links) . '.name') ?>
                        </button>
                    </div>

                <?php endif ?>
            </div>
        <?php endif ?>

        <div>
            <div class="dropdown">
                <button type="button" class="btn btn-light dropdown-toggle-simple fcc-links-action-btn <?= !empty($data->links) ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip title="<?= l('global.export') ?>" data-tooltip-hide-on-click>
                    <i class="fas fa-fw fa-sm fa-download"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-right d-print-none">
                    <a href="<?= url('links?' . $data->filters->get_get() . '&export=csv')  ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->csv ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->csv ? null : get_plan_feature_disabled_info() ?>>
                        <i class="fas fa-fw fa-sm fa-file-csv mr-2"></i> <?= sprintf(l('global.export_to'), 'CSV') ?>
                    </a>
                    <a href="<?= url('links?' . $data->filters->get_get() . '&export=json') ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->json ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->json ? null : get_plan_feature_disabled_info() ?>>
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
                <button type="button" class="btn <?= $data->filters->has_applied_filters ? 'btn-dark' : 'btn-light' ?> filters-button dropdown-toggle-simple fcc-links-action-btn <?= !empty($data->links) || $data->filters->has_applied_filters ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip data-html="true" title="<?= l('global.filters.tooltip') ?>" data-tooltip-hide-on-click>
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

                    <form action="<?= url('links') ?>" method="get" role="form">
                        <div class="form-group px-4">
                            <label for="filters_search" class="small"><?= l('global.filters.search') ?></label>
                            <input type="search" name="search" id="filters_search" class="form-control form-control-sm" value="<?= $data->filters->search ?>" />
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_search_by" class="small"><?= l('global.filters.search_by') ?></label>
                            <select name="search_by" id="filters_search_by" class="custom-select custom-select-sm">
                                <option value="url" <?= $data->filters->search_by == 'url' ? 'selected="selected"' : null ?>><?= l('links.filters.url') ?></option>
                                <option value="location_url" <?= $data->filters->search_by == 'location_url' ? 'selected="selected"' : null ?>><?= l('links.filters.location_url') ?></option>
                            </select>
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_is_enabled" class="small"><?= l('global.status') ?></label>
                            <select name="is_enabled" id="filters_is_enabled" class="custom-select custom-select-sm">
                                <option value=""><?= l('global.all') ?></option>
                                <option value="1" <?= isset($data->filters->filters['is_enabled']) && $data->filters->filters['is_enabled'] == '1' ? 'selected="selected"' : null ?>><?= l('global.active') ?></option>
                                <option value="0" <?= isset($data->filters->filters['is_enabled']) && $data->filters->filters['is_enabled'] == '0' ? 'selected="selected"' : null ?>><?= l('global.disabled') ?></option>
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

                        <?php if(settings()->links->domains_is_enabled): ?>
                            <div class="form-group px-4">
                                <div class="d-flex justify-content-between">
                                    <label for="filters_domain_id" class="small"><?= l('domains.domain_id') ?></label>
                                    <a href="<?= url('domain-create') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('global.create') ?></a>
                                </div>
                                <select name="domain_id" id="filters_domain_id" class="custom-select custom-select-sm">
                                    <option value=""><?= l('global.all') ?></option>
                                    <?php foreach($data->domains as $domain_id => $domain): ?>
                                        <option value="<?= $domain_id ?>" <?= isset($data->filters->filters['domain_id']) && $data->filters->filters['domain_id'] == $domain_id ? 'selected="selected"' : null ?>><?= $domain->host ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        <?php endif ?>

                        <div class="form-group px-4">
                            <label for="filters_type" class="small"><?= l('global.type') ?></label>
                            <select name="type" id="filters_type" class="custom-select custom-select-sm">
                                <option value=""><?= l('global.all') ?></option>
                                <?php if(settings()->links->biolinks_is_enabled): ?>
                                    <option value="biolink" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'biolink' ? 'selected="selected"' : null ?>><?= l('links.menu.biolink') ?></option>
                                <?php endif ?>

                                <?php if(settings()->links->shortener_is_enabled): ?>
                                    <option value="link" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'link' ? 'selected="selected"' : null ?>><?= l('links.menu.link') ?></option>
                                <?php endif ?>

                                <?php if(settings()->links->files_is_enabled): ?>
                                    <option value="file" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'file' ? 'selected="selected"' : null ?>><?= l('links.menu.file') ?></option>
                                <?php endif ?>

                                <?php if(settings()->links->vcards_is_enabled): ?>
                                    <option value="vcard" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'vcard' ? 'selected="selected"' : null ?>><?= l('links.menu.vcard') ?></option>
                                <?php endif ?>

                                <?php if(settings()->links->events_is_enabled): ?>
                                    <option value="event" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'event' ? 'selected="selected"' : null ?>><?= l('links.menu.event') ?></option>
                                <?php endif ?>

                                <?php if(settings()->links->static_is_enabled): ?>
                                    <option value="static" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'static' ? 'selected="selected"' : null ?>><?= l('links.menu.static') ?></option>
                                <?php endif ?>
                            </select>
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_order_by" class="small"><?= l('global.filters.order_by') ?></label>
                            <select name="order_by" id="filters_order_by" class="custom-select custom-select-sm">
                                <option value="link_id" <?= $data->filters->order_by == 'link_id' ? 'selected="selected"' : null ?>><?= l('global.id') ?></option>
                                <option value="datetime" <?= $data->filters->order_by == 'datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_datetime') ?></option>
                                <option value="last_datetime" <?= $data->filters->order_by == 'last_datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_last_datetime') ?></option>
                                <option value="clicks" <?= $data->filters->order_by == 'clicks' ? 'selected="selected"' : null ?>><?= l('links.filters.order_by_clicks') ?></option>
                                <option value="url" <?= $data->filters->order_by == 'url' ? 'selected="selected"' : null ?>><?= l('links.filters.url') ?></option>
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
            <button id="bulk_enable" type="button" class="btn btn-light fcc-links-action-btn" data-toggle="tooltip" title="<?= l('global.bulk_actions') ?>"><i class="fas fa-fw fa-sm fa-list"></i></button>

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

<?php if(isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'biolink' && $fcc_main_featured): ?>
    <div class="fcc-main-app-featured-card">
        <div class="row">
            <div class="col-12 col-xl-7 mb-4 mb-xl-0">
                <h2 class="h5 mb-2"><?= $fcc_main_header ?></h2>
                <p class="small text-muted mb-3"><?= $fcc_main_subheader ?></p>

                <form action="<?= url('links?type=biolink') ?>" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                    <input type="hidden" name="fcc_main_biolink_featured_settings" value="1" />

                    <div class="form-group custom-control custom-switch mb-3">
                        <input id="fcc_featured_opt_in" name="fcc_featured_opt_in" type="checkbox" class="custom-control-input" <?= !empty($fcc_main_featured['opt_in']) ? 'checked="checked"' : null ?>>
                        <label class="custom-control-label" for="fcc_featured_opt_in"><?= $fcc_main_toggle ?></label>
                    </div>

                    <div class="form-group">
                        <label for="fcc_featured_public_market"><i class="fas fa-fw fa-sm fa-globe-europe text-muted mr-1"></i> <?= $fcc_main_market ?></label>
                        <input type="text" id="fcc_featured_public_market" name="fcc_featured_public_market" class="form-control" value="<?= $_POST['fcc_featured_public_market'] ?? ($fcc_main_featured['public_market'] ?? '') ?>" maxlength="64" />
                    </div>

                    <div class="form-group mb-3">
                        <label for="fcc_featured_public_summary"><i class="fas fa-fw fa-sm fa-align-left text-muted mr-1"></i> <?= $fcc_main_summary ?></label>
                        <textarea id="fcc_featured_public_summary" name="fcc_featured_public_summary" class="form-control" rows="4" maxlength="220"><?= $_POST['fcc_featured_public_summary'] ?? ($fcc_main_featured['public_summary'] ?? '') ?></textarea>
                        <small class="form-text text-muted"><?= $fcc_main_summary_help ?></small>
                    </div>

                    <button type="submit" class="btn btn-primary fcc-links-action-btn"><?= $fcc_main_submit ?></button>
                </form>
            </div>

            <div class="col-12 col-xl-5">
                <div class="fcc-main-app-featured-status <?= empty($fcc_main_featured['opt_in']) ? 'is-muted' : null ?>">
                    <div class="font-weight-bold mb-2"><?= !empty($fcc_main_featured['opt_in']) ? $fcc_main_status_on : $fcc_main_status_off ?></div>

                    <?php if(empty($fcc_main_featured['is_approved'])): ?>
                        <div class="small text-muted mb-3"><?= $fcc_main_admin_hidden ?></div>
                    <?php endif ?>

                    <?php if(!empty($fcc_main_featured['public_market'])): ?>
                        <div class="fcc-main-app-featured-meta mb-3">
                            <span class="fcc-main-app-featured-pill"><?= $fcc_main_market ?>: <?= $fcc_main_featured['public_market'] ?></span>
                        </div>
                    <?php endif ?>

                    <?php if(!empty($fcc_main_featured['feature_labels'])): ?>
                        <div class="small text-uppercase text-muted font-weight-bold mb-2"><?= $fcc_main_detected ?></div>
                        <div class="fcc-main-app-featured-meta mb-3">
                            <?php foreach($fcc_main_featured['feature_labels'] as $feature_label): ?>
                                <span class="fcc-main-app-featured-tag"><?= $feature_label ?></span>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>

                    <div class="small text-muted mb-0 fcc-main-app-featured-preview">
                        <?= !empty(trim((string) ($fcc_main_featured['public_summary'] ?? ''))) ? $fcc_main_featured['public_summary'] : $fcc_main_auto_summary ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if (!empty($data->links)): ?>

    <form id="table" action="<?= SITE_URL . 'links/bulk' ?>" method="post" role="form">
        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
        <input type="hidden" name="type" value="" data-bulk-type />
        <input type="hidden" name="original_request" value="<?= base64_encode(\Altum\Router::$original_request) ?>" />
        <input type="hidden" name="original_request_query" value="<?= base64_encode(\Altum\Router::$original_request_query) ?>" />

        <div class="fcc-links-table-card">
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
                    <th><?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'biolink' ? 'Vaše Forever Card Aplikacije' : l('link.link') ?></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>

                <?php foreach($data->links as $row): ?>
                    <!-- Custom code: FC-2026-02-24: lock main NFC biolink deletion -->
                    <?php $is_main_biolink = $row->biolink_id && (int) $row->biolink_id === (int) $row->link_id; ?>
                    <!-- /Custom code: FC-2026-02-24 -->
                    <tr>
                        <td data-bulk-table class="d-none">
                            <div class="custom-control custom-checkbox">
                                <!-- Custom code: FC-2026-02-24: lock main NFC biolink deletion -->
                                <input id="selected_link_id_<?= $row->link_id ?>" type="checkbox" class="custom-control-input" name="selected[]" value="<?= $row->link_id ?>" <?= $is_main_biolink ? 'disabled="disabled"' : null ?> title="<?= $is_main_biolink ? l('link_delete_modal.error_message.main_biolink_locked') : '' ?>" />
                                <!-- /Custom code: FC-2026-02-24 -->
                                <label class="custom-control-label" for="selected_link_id_<?= $row->link_id ?>"></label>
                            </div>
                        </td>

                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">

                                <?php if($row->type == 'biolink' && $row->settings->favicon): ?>
                                    <img src="<?= \Altum\Uploads::get_full_url('favicons') . $row->settings->favicon ?>" class="link-type-icon justify-content-center mr-3 d-flex align-items-center rounded-pill" data-toggle="tooltip" title="<?= l('link.' . $row->type . '.name') ?>" loading="lazy" />
                                <?php else: ?>
                                <div class="link-type-icon justify-content-center mr-3 d-flex align-items-center rounded-pill" style="background-color: <?= $data->links_types[$row->type]['color'] ?>" data-toggle="tooltip" title="<?= l('link.' . $row->type . '.name') ?>">
                                    <i class="<?= $data->links_types[$row->type]['icon'] ?> text-white"></i>
                                </div>
                                <?php endif ?>

                                <div class="d-flex flex-column min-width-0">
                                    <div class="d-inline-block text-truncate">
                                        <a href="<?= url('link/' . $row->link_id) ?>" class="font-weight-500"><?= $row->url ?></a>
                                        <?php if($row->type == 'biolink' && $row->is_verified): ?>
                                            <span data-toggle="tooltip" title="<?= l('link.biolink.verified') ?>"><i class="fas fa-fw fa-xs fa-check-circle" style="color: #0086ff"></i></span>
                                        <?php endif ?>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <span class="d-inline-block text-truncate small">

                                        <?php if(!empty($row->location_url)): ?>
                                            <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain(parse_url($row->location_url, PHP_URL_HOST)) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />
                                            <a href="<?= $row->location_url ?>" class="text-muted" title="<?= remove_url_protocol_from_url($row->location_url) ?>" target="_blank" rel="noreferrer"><?= string_truncate(remove_url_protocol_from_url($row->location_url), 32) ?></a>
                                        <?php else: ?>
                                            <img src="<?= isset($row->settings->favicon) && $row->settings->favicon ? \Altum\Uploads::get_full_url('favicons') . $row->settings->favicon : get_favicon_url_from_domain(parse_url($row->full_url, PHP_URL_HOST)) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />
                                            <a href="<?= $row->full_url ?>" class="text-muted" title="<?= remove_url_protocol_from_url($row->full_url) ?>" target="_blank" rel="noreferrer"><?= string_truncate(remove_url_protocol_from_url($row->full_url), 32) ?></a>
                                        <?php endif ?>

                                        </span>
                                    </div>
                                </div>

                            </div>
                        </td>

                        <td class="text-nowrap">
                            <?php if(settings()->links->projects_is_enabled): ?>
                            <div class="mx-2">
                                <?php if($row->project_id && isset($data->projects[$row->project_id])): ?>
                                    <a href="<?= url('links?project_id=' . $row->project_id) ?>" class="text-decoration-none" data-toggle="tooltip" title="<?= l('projects.project_id') ?>">
                                        <span class="badge badge-light" style="color: <?= $data->projects[$row->project_id]->color ?> !important;">
                                            <?= $data->projects[$row->project_id]->name ?>
                                        </span>
                                    </a>
                                <?php endif ?>
                            </div>
                            <?php endif ?>

                            <div class="mx-2">
                                <a href="<?= url('link/' . $row->link_id . '/statistics') ?>">
                                    <span data-toggle="tooltip" title="<?= l('links.clicks') ?>"><span class="badge badge-light"><i class="fas fa-fw fa-sm fa-chart-bar mr-1"></i> <?= nr($row->clicks) ?></span></span>
                                </a>
                            </div>
                        </td>

                        <td class="text-nowrap text-muted">
                            <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.datetime_tooltip'), '<br />' . \Altum\Date::get($row->datetime, 2) . '<br /><small>' . \Altum\Date::get($row->datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->datetime) . ')</small>') ?>">
                                <i class="fas fa-fw fa-calendar text-muted"></i>
                            </span>

                            <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.last_datetime_tooltip'), ($row->last_datetime ? '<br />' . \Altum\Date::get($row->last_datetime, 2) . '<br /><small>' . \Altum\Date::get($row->last_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->last_datetime) . ')</small>' : '<br />' . l('global.na'))) ?>">
                                <i class="fas fa-fw fa-history text-muted"></i>
                            </span>
                        </td>

                        <td class="text-nowrap">
                            <div class="d-flex align-items-center justify-content-end">

                                <div class="custom-control custom-switch" data-toggle="tooltip" title="<?= l('links.is_enabled_tooltip') ?>">
                                    <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="link_is_enabled_<?= $row->link_id ?>"
                                            data-row-id="<?= $row->link_id ?>"
                                            onchange="ajax_call_helper(event, 'link-ajax', 'is_enabled_toggle')"
                                        <?= $row->is_enabled ? 'checked="checked"' : null ?>
                                    >
                                    <label class="custom-control-label" for="link_is_enabled_<?= $row->link_id ?>"></label>
                                </div>

                                <button
                                        id="url_copy"
                                        type="button"
                                        class="btn btn-link text-secondary"
                                        data-toggle="tooltip"
                                        title="<?= l('global.clipboard_copy') ?>"
                                        aria-label="<?= l('global.clipboard_copy') ?>"
                                        data-copy="<?= l('global.clipboard_copy') ?>"
                                        data-copied="<?= l('global.clipboard_copied') ?>"
                                        data-clipboard-text="<?= $row->full_url ?>"
                                >
                                    <i class="fas fa-fw fa-sm fa-copy"></i>
                                </button>

                                <div class="dropdown">
                                    <button type="button" class="btn btn-link text-secondary dropdown-toggle dropdown-toggle-simple" data-toggle="dropdown" data-boundary="viewport">
                                        <i class="fas fa-fw fa-ellipsis-v"></i>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="<?= url('link/' . $row->link_id) ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-pencil-alt mr-2"></i> <?= l('global.edit') ?></a>
                                        <a href="<?= url('link/' . $row->link_id . '/statistics') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-chart-bar mr-2"></i> <?= l('link.statistics.link') ?></a>
                                        <?php if(settings()->codes->qr_codes_is_enabled): ?>
                                            <a href="<?= url('qr-code-create?name=' . $row->url . '&project_id=' . $row->project_id . '&type=url&url=' . $row->full_url . '&link_id=' . $row->link_id . '&url_dynamic=1') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-qrcode mr-2"></i> <?= l('qr_codes.create') ?></a>
                                        <?php endif ?>

                                        <?php if($row->type == 'static'): ?>
                                            <a href="<?= url('link/' . $row->link_id . '/download') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-download mr-2"></i> <?= l('global.download') ?></a>
                                        <?php endif ?>

                                        <a href="#" data-toggle="modal" data-target="#link_duplicate_modal" class="dropdown-item" data-link-id="<?= $row->link_id ?>"><i class="fas fa-fw fa-sm fa-clone mr-2"></i> <?= l('global.duplicate') ?></a>                                        
                                        <!-- Custom code -->                                        
                                        <?php if (($row->link_id && !$row->biolink_id) && ($row->link_id && !$row->vcard_id)): ?>
                                            <a href="#" data-toggle="modal" data-target="#link_reset_modal" class="dropdown-item" data-link-id="<?= $row->link_id ?>"><i class="fas fa-fw fa-sm fa-redo mr-2"></i> <?= l('global.reset') ?></a>                                            
                                            <a href="#" data-toggle="modal" data-target="#link_delete_modal" class="dropdown-item" data-link-id="<?= $row->link_id ?>"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>                                            
                                        <?php endif; ?>
                                        <!-- /Custom code -->
                                    </div>
                                </div>

                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>

                </tbody>
            </table>
        </div>
        </div>
    </form>

    <div class="fcc-links-pagination"><?= $data->pagination ?></div>

<?php else: ?>

    <div class="fcc-links-empty">
    <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
        'filters_get' => $data->filters->get ?? [],
        'name' => 'links',
        'has_secondary_text' => false,
    ]); ?>
    </div>

<?php endif ?>
</div>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/duplicate_modal.php', ['modal_id' => 'link_duplicate_modal', 'resource_id' => 'link_id', 'path' => 'link-ajax/duplicate']), 'modals'); ?>
<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>

<?php require THEME_PATH . 'views/partials/js_bulk.php' ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_delete_modal.php'), 'modals'); ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/x_reset_modal.php', ['modal_id' => 'link_reset_modal', 'resource_id' => 'link_id', 'path' => 'links/reset']), 'modals'); ?>
