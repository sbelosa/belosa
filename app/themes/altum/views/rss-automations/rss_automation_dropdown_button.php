<?php defined('ALTUMCODE') || die() ?>

<div class="dropdown">
    <button type="button" class="btn btn-link <?= $data->button_text_class ?? 'text-secondary' ?> dropdown-toggle dropdown-toggle-simple" data-toggle="dropdown" data-boundary="viewport">
        <i class="fas fa-fw fa-ellipsis-v"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right">
        <a class="dropdown-item" href="<?= url('rss-automation-update/' . $data->id) ?>"><i class="fas fa-fw fa-sm fa-pencil-alt mr-2"></i> <?= l('global.edit') ?></a>
        <a href="#" data-toggle="modal" data-target="#rss_automation_duplicate_modal" data-rss-automation-id="<?= $data->id ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-clone mr-2"></i> <?= l('global.duplicate') ?></a>

        <a href="#" data-toggle="modal" data-target="#rss_automation_delete_modal" data-resource-name="<?= $data->resource_name ?>" data-rss-automation-id="<?= $data->id ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>
    </div>
</div>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/universal_delete_modal_form.php', [
    'name' => 'rss_automation',
    'resource_id' => 'rss_automation_id',
    'has_dynamic_resource_name' => true,
    'path' => 'rss-automations/delete'
]), 'modals', 'rss_automations_delete_modal'); ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/duplicate_modal.php', ['modal_id' => 'rss_automation_duplicate_modal', 'resource_id' => 'rss_automation_id', 'path' => 'rss-automations/duplicate']), 'modals', 'rss_automation_duplicate_modal'); ?>
