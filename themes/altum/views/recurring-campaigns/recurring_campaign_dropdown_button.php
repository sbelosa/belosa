<?php defined('ALTUMCODE') || die() ?>

<div class="dropdown">
    <button type="button" class="btn btn-link <?= $data->button_text_class ?? 'text-secondary' ?> dropdown-toggle dropdown-toggle-simple" data-toggle="dropdown" data-boundary="viewport">
        <i class="fas fa-fw fa-ellipsis-v"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right">
        <a class="dropdown-item" href="<?= url('recurring-campaign-update/' . $data->id) ?>"><i class="fas fa-fw fa-sm fa-pencil-alt mr-2"></i> <?= l('global.edit') ?></a>
        <a href="#" data-toggle="modal" data-target="#recurring_campaign_duplicate_modal" data-recurring-campaign-id="<?= $data->id ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-clone mr-2"></i> <?= l('global.duplicate') ?></a>

        <a href="#" data-toggle="modal" data-target="#recurring_campaign_delete_modal" data-resource-name="<?= $data->resource_name ?>" data-recurring-campaign-id="<?= $data->id ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>
    </div>
</div>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/universal_delete_modal_form.php', [
    'name' => 'recurring_campaign',
    'resource_id' => 'recurring_campaign_id',
    'has_dynamic_resource_name' => true,
    'path' => 'recurring-campaigns/delete'
]), 'modals', 'recurring_campaigns_delete_modal'); ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/duplicate_modal.php', ['modal_id' => 'recurring_campaign_duplicate_modal', 'resource_id' => 'recurring_campaign_id', 'path' => 'recurring-campaigns/duplicate']), 'modals', 'recurring_campaign_duplicate_modal'); ?>
