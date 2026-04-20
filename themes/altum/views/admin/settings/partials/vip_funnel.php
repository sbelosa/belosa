<?php defined('ALTUMCODE') || die() ?>

<?php $vip_funnel_settings = vip_funnel_get_settings(); ?>

<div class="mb-4">
    <h2 class="h5 mb-1"><?= l('admin_settings.vip_funnel.header') ?></h2>
    <p class="text-muted mb-0"><?= l('admin_settings.vip_funnel.subheader') ?></p>
</div>

<div class="alert alert-info">
    <div><strong><?= l('admin_settings.vip_funnel.testing_title') ?></strong></div>
    <div><?= l('admin_settings.vip_funnel.testing_help') ?></div>
</div>

<div class="form-group">
    <label for="rollout_mode"><i class="fas fa-fw fa-sm fa-traffic-light text-muted mr-1"></i> <?= l('admin_settings.vip_funnel.rollout_mode') ?></label>
    <select id="rollout_mode" name="rollout_mode" class="custom-select">
        <option value="testing_visible_locked" <?= $vip_funnel_settings->rollout_mode === 'testing_visible_locked' ? 'selected="selected"' : null ?>><?= l('admin_settings.vip_funnel.rollout_mode.testing_visible_locked') ?></option>
        <option value="enabled" <?= $vip_funnel_settings->rollout_mode === 'enabled' ? 'selected="selected"' : null ?>><?= l('admin_settings.vip_funnel.rollout_mode.enabled') ?></option>
        <option value="disabled_hidden" <?= $vip_funnel_settings->rollout_mode === 'disabled_hidden' ? 'selected="selected"' : null ?>><?= l('admin_settings.vip_funnel.rollout_mode.disabled_hidden') ?></option>
    </select>
    <small class="form-text text-muted"><?= l('admin_settings.vip_funnel.rollout_mode_help') ?></small>
</div>

<div class="form-group custom-control custom-switch">
    <input id="visible_when_locked" name="visible_when_locked" type="checkbox" class="custom-control-input" <?= $vip_funnel_settings->visible_when_locked ? 'checked="checked"' : null ?>>
    <label class="custom-control-label" for="visible_when_locked"><?= l('admin_settings.vip_funnel.visible_when_locked') ?></label>
    <div><small class="form-text text-muted"><?= l('admin_settings.vip_funnel.visible_when_locked_help') ?></small></div>
</div>

<div class="form-group custom-control custom-switch">
    <input id="show_sidebar_entry_when_locked" name="show_sidebar_entry_when_locked" type="checkbox" class="custom-control-input" <?= $vip_funnel_settings->show_sidebar_entry_when_locked ? 'checked="checked"' : null ?>>
    <label class="custom-control-label" for="show_sidebar_entry_when_locked"><?= l('admin_settings.vip_funnel.show_sidebar_entry_when_locked') ?></label>
    <div><small class="form-text text-muted"><?= l('admin_settings.vip_funnel.show_sidebar_entry_when_locked_help') ?></small></div>
</div>

<div class="form-group">
    <label for="pilot_allowed_user_ids"><i class="fas fa-fw fa-sm fa-user-shield text-muted mr-1"></i> <?= l('admin_settings.vip_funnel.pilot_allowed_user_ids') ?></label>
    <input
        id="pilot_allowed_user_ids"
        name="pilot_allowed_user_ids"
        type="text"
        class="form-control"
        value="<?= implode(',', $vip_funnel_settings->pilot_allowed_user_ids ?? []) ?>"
        placeholder="122,555"
    />
    <small class="form-text text-muted"><?= l('admin_settings.vip_funnel.pilot_allowed_user_ids_help') ?></small>
</div>

<div class="form-group">
    <label for="default_demo_days"><i class="fas fa-fw fa-sm fa-calendar-day text-muted mr-1"></i> <?= l('admin_settings.vip_funnel.default_demo_days') ?></label>
    <input
        id="default_demo_days"
        name="default_demo_days"
        type="number"
        min="1"
        max="30"
        class="form-control"
        value="<?= (int) ($vip_funnel_settings->default_demo_days ?? 5) ?>"
    />
    <small class="form-text text-muted"><?= l('admin_settings.vip_funnel.default_demo_days_help') ?></small>
</div>

<div class="form-group custom-control custom-switch">
    <input id="demo_request_requires_approval" name="demo_request_requires_approval" type="checkbox" class="custom-control-input" <?= $vip_funnel_settings->demo_request_requires_approval ? 'checked="checked"' : null ?>>
    <label class="custom-control-label" for="demo_request_requires_approval"><?= l('admin_settings.vip_funnel.demo_request_requires_approval') ?></label>
    <div><small class="form-text text-muted"><?= l('admin_settings.vip_funnel.demo_request_requires_approval_help') ?></small></div>
</div>

<button type="submit" name="submit" class="btn btn-lg btn-block btn-primary mt-4"><?= l('global.update') ?></button>
