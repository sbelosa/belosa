<?php defined('ALTUMCODE') || die() ?>

<div class="dropdown">
    <button type="button" class="btn btn-link <?= $data->button_text_class ?? 'text-secondary' ?> dropdown-toggle dropdown-toggle-simple" data-toggle="dropdown" data-boundary="viewport">
        <i class="fas fa-fw fa-ellipsis-v"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right">
        <a class="dropdown-item" href="<?= url('website/' . $data->id) ?>"><i class="fas fa-fw fa-sm fa-eye mr-2"></i> <?= l('global.view') ?></a>

        <a class="dropdown-item" href="<?= url('website-update/' . $data->id) ?>"><i class="fas fa-fw fa-sm fa-pencil-alt mr-2"></i> <?= l('global.edit') ?></a>

        <a
                href="#"
                data-toggle="modal"
                data-target="#website_install_code_modal"
                data-website-id="<?= $data->id ?>"
                data-pixel-key="<?= $data->pixel_key ?>"
                data-resource-name="<?= $data->resource_name ?>"
                data-base-url="<?= $data->domain_id ? $data->domains[$data->domain_id]->scheme . $data->domains[$data->domain_id]->host . '/' : SITE_URL ?>"
                data-file-name="<?= settings()->websites->service_worker_file_name . '.js' ?>"
                data-host="<?= $data->host ?>"
                data-path="<?= $data->path ?>"
                class="dropdown-item"
        ><i class="fas fa-fw fa-sm fa-code mr-2"></i> <?= l('websites.install_code') ?></a>

        <a class="dropdown-item" href="<?= url('website-subscribe-widget/' . $data->id) ?>"><i class="fas fa-fw fa-sm fa-th-large mr-2"></i> <?= l('website_subscribe_widget.menu') ?></a>
        <a class="dropdown-item" href="<?= url('website-subscribe-button/' . $data->id) ?>"><i class="fas fa-fw fa-sm fa-square mr-2"></i> <?= l('website_subscribe_button.menu') ?></a>

        <a href="<?= url('subscribers-statistics?website_id=' . $data->id) ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-chart-bar mr-2"></i> <?= l('subscribers_statistics.link') ?></a>

        <a href="#" data-toggle="modal" data-target="#website_reset_modal" data-website-id="<?= $data->id ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-redo mr-2"></i> <?= l('global.reset') ?></a>

        <a
                href="#"
                data-toggle="modal"
                data-target="#website_delete_modal"
                data-website-id="<?= $data->id ?>"
                data-resource-name="<?= $data->resource_name ?>"
                class="dropdown-item"
        ><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>
    </div>
</div>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/universal_delete_modal_form.php', [
    'name' => 'website',
    'resource_id' => 'website_id',
    'has_dynamic_resource_name' => true,
    'path' => 'websites/delete'
]), 'modals', 'website_delete_modal'); ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/websites/website_install_code_modal.php'), 'modals', 'website_install_code_modal'); ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/x_reset_modal.php', ['modal_id' => 'website_reset_modal', 'resource_id' => 'website_id', 'path' => 'websites/reset']), 'modals', 'website_reset_modal'); ?>
