<?php defined('ALTUMCODE') || die() ?>

<div class="dropdown">
    <button type="button" class="btn btn-link <?= $data->button_text_class ?? 'text-secondary' ?> dropdown-toggle dropdown-toggle-simple" data-toggle="dropdown" data-boundary="viewport">
        <i class="fas fa-fw fa-ellipsis-v"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right">
        <a class="dropdown-item" href="<?= url('subscriber/' . $data->id) ?>"><i class="fas fa-fw fa-sm fa-eye mr-2"></i> <?= l('global.view') ?></a>
        <a class="dropdown-item" href="<?= url('subscriber-update/' . $data->id) ?>"><i class="fas fa-fw fa-sm fa-pencil-alt mr-2"></i> <?= l('global.edit') ?></a>
        <a class="dropdown-item" href="<?= url('personal-notification-create?subscriber_id=' . $data->id . '&website_id=' . $data->website_id) ?>"><i class="fas fa-fw fa-sm fa-fire mr-2"></i> <?= l('subscriber.send_push_notification') ?></a>
        <a href="#" data-toggle="modal" data-target="#subscriber_delete_modal" data-subscriber-id="<?= $data->id ?>" data-resource-name="<?= $data->resource_name ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>
    </div>
</div>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/universal_delete_modal_form.php', [
    'name' => 'subscriber',
    'resource_id' => 'subscriber_id',
    'has_dynamic_resource_name' => true,
    'path' => 'subscribers/delete'
]), 'modals', 'subscriber_delete_modal'); ?>

