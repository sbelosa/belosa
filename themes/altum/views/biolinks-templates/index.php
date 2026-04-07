<?php defined('ALTUMCODE') || die() ?>

<?php ob_start() ?>
<style>
    .fcc-templates-shell {
        --fcc-template-border: rgba(144, 191, 255, 0.16);
        --fcc-template-border-soft: rgba(139, 202, 234, 0.1);
        --fcc-template-copy: #a8bfd3;
        --fcc-template-copy-strong: #eef5ff;
        --fcc-template-copy-soft: #90a8bf;
        --fcc-template-shadow: 0 26px 56px rgba(4, 13, 27, 0.28);
        color: var(--fcc-template-copy-strong);
    }

    .fcc-templates-hero {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid var(--fcc-template-border);
        background:
            radial-gradient(circle at top right, rgba(223, 190, 112, 0.16) 0%, rgba(223, 190, 112, 0) 34%),
            radial-gradient(circle at top left, rgba(72, 220, 214, 0.11) 0%, rgba(72, 220, 214, 0) 30%),
            linear-gradient(180deg, rgba(19, 28, 48, 0.995) 0%, rgba(10, 15, 26, 0.995) 100%);
        box-shadow:
            var(--fcc-template-shadow),
            0 0 0 1px rgba(225, 201, 145, 0.04),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
        padding: 1.5rem;
        margin-bottom: 1.35rem;
    }

    .fcc-templates-hero::before {
        content: '';
        position: absolute;
        left: 1.25rem;
        right: 1.25rem;
        top: 0;
        height: 4px;
        border-radius: 0 0 999px 999px;
        background: linear-gradient(90deg, rgba(83, 228, 215, 0.42) 0%, rgba(223, 190, 112, 0.82) 52%, rgba(97, 178, 255, 0.42) 100%);
        opacity: 0.94;
        pointer-events: none;
    }

    .fcc-templates-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        box-shadow: inset 0 0 0 1px rgba(239, 217, 171, 0.08);
        pointer-events: none;
    }

    .fcc-templates-hero-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) auto;
        gap: 1rem 1.25rem;
        align-items: end;
    }

    .fcc-templates-eyebrow {
        display: inline-flex;
        align-items: center;
        min-height: 2rem;
        padding: 0.34rem 0.78rem;
        border-radius: 999px;
        margin-bottom: 0.85rem;
        background: rgba(72, 220, 214, 0.11);
        border: 1px solid rgba(120, 215, 225, 0.18);
        color: #d7fffa;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .fcc-templates-hero h1 {
        margin: 0 0 0.72rem;
        color: #f6faff;
        font-size: clamp(1.8rem, 4.1vw, 2.45rem);
        line-height: 1.02;
        letter-spacing: -0.04em;
    }

    .fcc-templates-hero-copy {
        max-width: 48rem;
    }

    .fcc-templates-hero-copy p {
        margin: 0;
        color: var(--fcc-template-copy);
        font-size: 1rem;
        line-height: 1.72;
    }

    .fcc-templates-hero-note {
        margin-top: 1rem;
        max-width: 38rem;
        border-radius: 18px;
        padding: 0.95rem 1rem;
        border: 1px solid var(--fcc-template-border-soft);
        background: linear-gradient(180deg, rgba(16, 24, 38, 0.92) 0%, rgba(12, 18, 30, 0.96) 100%);
        color: #b7c8db;
        font-size: 0.92rem;
        line-height: 1.66;
    }

    .fcc-templates-hero-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        align-items: center;
        gap: 0.75rem;
    }

    .fcc-templates-count {
        display: inline-flex;
        align-items: center;
        min-height: 2.25rem;
        padding: 0.42rem 0.88rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(224, 235, 255, 0.08);
        color: #e8f1fb;
        font-size: 0.8rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .fcc-templates-filter-trigger {
        min-height: 2.8rem;
        min-width: 2.8rem;
        border-radius: 16px;
        border: 1px solid rgba(129, 208, 238, 0.22) !important;
        background: rgba(255, 255, 255, 0.045) !important;
        color: #edf4ff !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
    }

    .fcc-templates-filter-trigger:hover,
    .fcc-templates-filter-trigger:focus {
        border-color: rgba(129, 208, 238, 0.32) !important;
        background: rgba(255, 255, 255, 0.065) !important;
        color: #ffffff !important;
    }

    .fcc-templates-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.1rem;
    }

    .fcc-template-card {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        border: 1px solid var(--fcc-template-border-soft);
        background:
            radial-gradient(circle at top right, rgba(223, 190, 112, 0.1) 0%, rgba(223, 190, 112, 0) 28%),
            radial-gradient(circle at top left, rgba(72, 220, 214, 0.08) 0%, rgba(72, 220, 214, 0) 26%),
            linear-gradient(180deg, rgba(17, 25, 41, 0.99) 0%, rgba(10, 14, 24, 0.995) 100%);
        box-shadow: 0 24px 48px rgba(4, 13, 27, 0.24);
        display: flex;
        flex-direction: column;
        min-height: 100%;
    }

    .fcc-template-card::before {
        content: '';
        position: absolute;
        left: 1rem;
        right: 1rem;
        top: 0;
        height: 4px;
        border-radius: 0 0 999px 999px;
        background: linear-gradient(90deg, rgba(83, 228, 215, 0.32) 0%, rgba(223, 190, 112, 0.7) 50%, rgba(97, 178, 255, 0.34) 100%);
        opacity: 0.88;
        pointer-events: none;
    }

    .fcc-template-preview-wrap {
        position: relative;
        padding: 1rem 1rem 0;
    }

    .fcc-template-preview-wrap iframe {
        width: 100%;
        height: 25rem;
        border: 0;
        border-radius: 18px;
        background: #0b111c;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04);
    }

    .fcc-template-preview-badge {
        position: absolute;
        top: 1.8rem;
        left: 1.8rem;
        display: inline-flex;
        align-items: center;
        min-height: 2rem;
        padding: 0.34rem 0.78rem;
        border-radius: 999px;
        background: rgba(17, 24, 39, 0.8);
        border: 1px solid rgba(160, 191, 255, 0.18);
        color: #eef4ff;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        backdrop-filter: blur(8px);
        pointer-events: none;
    }

    .fcc-template-card-body {
        display: flex;
        flex: 1;
        flex-direction: column;
        padding: 1rem 1rem 1.05rem;
    }

    .fcc-template-card-title {
        margin: 0 0 0.45rem;
        color: #f7fbff;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        text-align: center;
    }

    .fcc-template-card-copy {
        margin: 0 0 1rem;
        color: var(--fcc-template-copy-soft);
        font-size: 0.9rem;
        line-height: 1.64;
        text-align: center;
    }

    .fcc-template-card-actions {
        display: grid;
        gap: 0.7rem;
        margin-top: auto;
    }

    .fcc-template-card-actions .btn {
        min-height: 2.8rem;
        border-radius: 15px;
        font-weight: 800;
    }

    .fcc-template-card-actions .btn-light {
        border-color: rgba(129, 208, 238, 0.18);
        background: rgba(255, 255, 255, 0.045);
        color: #ebf3ff;
    }

    .fcc-template-card-actions .btn-light:hover {
        border-color: rgba(129, 208, 238, 0.28);
        background: rgba(255, 255, 255, 0.065);
        color: #ffffff;
    }

    .fcc-template-card-actions .btn-primary {
        border-color: transparent;
        background: linear-gradient(135deg, #3fd7c7 0%, #6de9dd 100%);
        color: #082826;
        box-shadow: none !important;
    }

    .fcc-template-card-actions .btn-primary:hover {
        color: #041b19;
        transform: translateY(-1px);
        box-shadow: 0 16px 32px rgba(63, 215, 199, 0.18) !important;
    }

    .fcc-template-card-actions .container-disabled {
        opacity: 0.65;
    }

    .fcc-templates-pagination {
        margin-top: 1.2rem;
        padding: 0 0.15rem;
    }

    .fcc-templates-empty {
        border-radius: 24px;
        border: 1px solid var(--fcc-template-border-soft);
        background: linear-gradient(180deg, rgba(17, 25, 41, 0.99) 0%, rgba(10, 14, 24, 0.995) 100%);
        box-shadow: 0 22px 46px rgba(4, 13, 27, 0.2);
        padding: 0.35rem;
    }

    @media (max-width: 1199.98px) {
        .fcc-templates-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991.98px) {
        .fcc-templates-hero-grid {
            grid-template-columns: 1fr;
        }

        .fcc-templates-hero-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .fcc-templates-grid {
            grid-template-columns: 1fr;
        }

        .fcc-templates-hero {
            padding: 1.2rem;
            border-radius: 24px;
        }

        .fcc-template-preview-wrap iframe {
            height: 22rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<div class="container">
    <div class="fcc-templates-shell">
        <div class="fcc-templates-hero">
            <div class="fcc-templates-hero-grid">
                <div class="fcc-templates-hero-copy">
                    <div class="fcc-templates-eyebrow"><?= l('biolinks_templates.eyebrow') ?></div>
                    <h1><?= l('biolinks_templates.header') ?></h1>
                    <p><?= l('biolinks_templates.subheader') ?></p>
                    <div class="fcc-templates-hero-note"><?= l('biolinks_templates.guide_text') ?></div>
                </div>

                <div class="fcc-templates-hero-actions d-print-none">
                    <?php if(!empty($data->biolinks_templates)): ?>
                        <div class="fcc-templates-count"><?= sprintf(l('biolinks_templates.count_label'), nr(count($data->biolinks_templates))) ?></div>
                    <?php endif ?>

                    <div class="dropdown">
                        <button type="button" class="btn <?= $data->filters->has_applied_filters ? 'btn-dark' : 'btn-light' ?> filters-button dropdown-toggle-simple fcc-templates-filter-trigger <?= !empty($data->biolinks_templates) || $data->filters->has_applied_filters ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip data-html="true" title="<?= l('global.filters.tooltip') ?>" data-tooltip-hide-on-click>
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
                                        <option value="name" <?= $data->filters->order_by == 'name' ? 'selected="selected"' : null ?>><?= l('global.name') ?></option>
                                    </select>
                                </div>

                                <div class="form-group px-4">
                                    <label for="filters_order_by" class="small"><?= l('global.filters.order_by') ?></label>
                                    <select name="order_by" id="filters_order_by" class="custom-select custom-select-sm">
                                        <option value="biolink_template_id" <?= $data->filters->order_by == 'biolink_template_id' ? 'selected="selected"' : null ?>><?= l('global.id') ?></option>
                                        <option value="order" <?= $data->filters->order_by == 'order' ? 'selected="selected"' : null ?>><?= l('global.order') ?></option>
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
            </div>
        </div>

        <?php if (!empty($data->biolinks_templates)): ?>
            <div class="fcc-templates-grid">
                <?php foreach($data->biolinks_templates as $biolink_template): ?>
                    <article class="fcc-template-card">
                        <div class="fcc-template-preview-wrap">
                            <span class="fcc-template-preview-badge"><?= l('biolinks_templates.card_badge') ?></span>
                            <iframe src="<?= $biolink_template->url . '?preview_template' ?>" class="container-disabled-simple" loading="lazy"></iframe>
                        </div>

                        <div class="fcc-template-card-body">
                            <h2 class="fcc-template-card-title"><?= htmlspecialchars($biolink_template->name, ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="fcc-template-card-copy"><?= l('biolinks_templates.card_text') ?></p>

                            <div class="fcc-template-card-actions">
                                <a href="<?= $biolink_template->url ?>" target="_blank" class="btn btn-block btn-sm btn-light"><i class="fas fa-fw fa-sm fa-external-link-alt mr-1"></i> <?= l('biolinks_templates.preview') ?></a>

                                <div <?= in_array($biolink_template->biolink_template_id, $this->user->plan_settings->biolinks_templates ?? []) ? null : get_plan_feature_disabled_info() ?>>
                                    <button type="button" class="btn btn-block btn-sm btn-primary <?= in_array($biolink_template->biolink_template_id, $this->user->plan_settings->biolinks_templates ?? []) ? null : 'container-disabled' ?>" data-toggle="modal" data-target="#create_biolink" onclick="document.querySelector(`input[name='biolink_template_id']`).value = <?= $biolink_template->biolink_template_id ?>;">
                                        <i class="fas fa-fw fa-sm fa-plus-circle mr-1"></i> <?= l('biolinks_templates.choose') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>

            <div class="fcc-templates-pagination"><?= $data->pagination ?></div>
        <?php else: ?>
            <div class="fcc-templates-empty">
                <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                    'filters_get' => $data->filters->get ?? [],
                    'name' => 'global',
                    'has_secondary_text' => false,
                ]); ?>
            </div>
        <?php endif ?>
    </div>
</div>
