<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('websites') ?>"><?= l('websites.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li>
                    <a href="<?= url('website/' . $data->website->website_id) ?>"><?= l('website.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('website_update.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="d-flex justify-content-between mb-4">
        <h1 class="h4 text-truncate mb-0"><i class="fas fa-fw fa-xs fa-pager mr-1"></i> <?= l('website_update.header') ?></h1>

        <?= include_view(THEME_PATH . 'views/websites/website_dropdown_button.php', ['id' => $data->website->website_id, 'resource_name' => $data->website->name, 'host' => $data->website->host, 'path' => $data->website->path, 'pixel_key' => $data->website->pixel_key, 'domain_id' => $data->website->domain_id, 'domains' => $data->domains]) ?>
    </div>

    <div class="card">
        <div class="card-body">

            <form id="website_update" action="" method="post" role="form" enctype="multipart/form-data">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                <div class="form-group">
                    <label for="name"><i class="fas fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('global.name') ?></label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= $data->website->name ?>" required="required" />
                </div>

                <div class="form-group">
                    <label for="host"><i class="fas fa-fw fa-sm fa-pager text-muted mr-1"></i> <?= l('global.host') ?></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <select name="scheme" class="appearance-none custom-select custom-select-lg form-control input-group-text">
                                <option value="https://" <?= $data->website->scheme == 'https://' ? 'selected="selected"' : null ?>>https://</option>
                                <option value="http://" <?= $data->website->scheme == 'http://' ? 'selected="selected"' : null ?>>http://</option>
                            </select>
                        </div>

                        <input id="host" type="text" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" name="host" value="<?= $data->website->host . $data->website->path ?>" placeholder="<?= l('global.host_placeholder') ?>" required="required" />
                    </div>
                    <small class="form-text text-muted"><?= l('websites.input.host_help') ?></small>
                    <?= \Altum\Alerts::output_field_error('host') ?>
                </div>

                <?php if(count($data->domains) && settings()->websites->domains_is_enabled): ?>
                    <div class="form-group">
                        <label for="domain_id"><i class="fas fa-fw fa-sm fa-globe text-muted mr-1"></i> <?= l('websites.input.domain_id') ?></label>
                        <select id="domain_id" name="domain_id" class="custom-select">
                            <option value=" " <?= $data->website->domain_id ? null : 'selected="selected"' ?>><?= remove_url_protocol_from_url(SITE_URL) ?></option>
                            <?php foreach($data->domains as $row): ?>
                                <option value="<?= $row->domain_id ?>" <?= $data->website->domain_id && $data->website->domain_id == $row->domain_id ? 'selected="selected"' : null ?>><?= $row->host ?></option>
                            <?php endforeach ?>
                        </select>
                        <small class="form-text text-muted"><?= l('websites.input.domain_id_help') ?></small>
                    </div>
                <?php endif ?>

                <div class="form-group custom-control custom-switch">
                    <input id="is_enabled" name="is_enabled" type="checkbox" class="custom-control-input" <?= $data->website->is_enabled ? 'checked="checked"' : null?>>
                    <label class="custom-control-label" for="is_enabled"><?= l('websites.input.is_enabled') ?></label>
                </div>

                <div class="form-group custom-control custom-switch">
                    <input id="ip_storage_is_enabled" name="ip_storage_is_enabled" type="checkbox" class="custom-control-input" <?= $data->website->settings->ip_storage_is_enabled ? 'checked="checked"' : null?>>
                    <label class="custom-control-label" for="ip_storage_is_enabled"><i class="fas fa-fw fa-sm fa-shield-alt text-muted mr-1"></i> <?= l('websites.input.ip_storage_is_enabled') ?></label>
                    <small class="form-text text-muted"><?= l('websites.input.ip_storage_is_enabled_help') ?></small>
                </div>

                <button class="btn btn-sm btn-block <?= \Altum\Alerts::has_field_errors(['icon']) ? 'btn-outline-danger' : 'btn-light' ?> my-3" type="button" data-toggle="collapse" data-target="#customizations_container" aria-expanded="false" aria-controls="customizations_container">
                    <i class="fas fa-fw fa-paint-brush fa-sm mr-1"></i> <?= l('websites.input.customizations') ?>
                </button>

                <div class="collapse" data-parent="#website_update" id="customizations_container">
                    <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= settings()->websites->icon_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->websites->icon_size_limit) ?>">
                        <label for="icon"><i class="fas fa-fw fa-sm fa-image text-muted mr-1"></i> <?= l('websites.input.icon') ?></label>
                        <?= include_view(THEME_PATH . 'views/partials/file_image_input.php', ['uploads_file_key' => 'websites_icons', 'file_key' => 'icon', 'already_existing_image' => $data->website->settings->icon, 'input_data' => 'data-crop data-aspect-ratio="1"']) ?>
                        <?= \Altum\Alerts::output_field_error('icon') ?>
                        <small class="form-text text-muted"><?= l('websites.input.icon_help') ?> <?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('websites_icons')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->websites->icon_size_limit) ?></small>
                    </div>
                </div>

                <button class="btn btn-sm btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#notifications_container" aria-expanded="false" aria-controls="notifications_container">
                    <i class="fas fa-fw fa-bell fa-sm mr-1"></i> <?= l('websites.input.notifications') ?>
                </button>

                <div class="collapse" data-parent="#website_update" id="notifications_container">
                    <div class="form-group">
                        <div class="d-flex flex-wrap flex-row justify-content-between">
                            <label><i class="fas fa-fw fa-sm fa-bell text-muted mr-1"></i> <?= l('websites.input.notifications') ?></label>
                            <a href="<?= url('notification-handler-create') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('notification_handlers.create') ?></a>
                        </div>
                        <div class="mb-2"><small class="text-muted"><?= l('websites.input.notifications_help') ?></small></div>

                        <div class="row">
                            <?php foreach($data->notification_handlers as $notification_handler): ?>
                                <div class="col-12 col-lg-6">
                                    <div class="custom-control custom-checkbox my-2">
                                        <input id="notifications_<?= $notification_handler->notification_handler_id ?>" name="notifications[]" value="<?= $notification_handler->notification_handler_id ?>" type="checkbox" class="custom-control-input" <?= in_array($notification_handler->notification_handler_id, $data->website->notifications ?? []) ? 'checked="checked"' : null ?>>
                                        <label class="custom-control-label" for="notifications_<?= $notification_handler->notification_handler_id ?>">
                                            <span class="mr-1"><?= $notification_handler->name ?></span>
                                            <small class="badge badge-light badge-pill"><?= l('notification_handlers.type_' . $notification_handler->type) ?></small>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>

                <button class="btn btn-sm btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#branding_container" aria-expanded="false" aria-controls="branding_container">
                    <i class="fas fa-fw fa-random fa-sm mr-1"></i> <?= l('websites.input.branding') ?>
                </button>

                <div class="collapse" data-parent="#website_update" id="branding_container">
                    <div <?= $this->user->plan_settings->custom_branding_is_enabled ? null : get_plan_feature_disabled_info() ?>>
                        <div class="<?= $this->user->plan_settings->custom_branding_is_enabled ? null : 'container-disabled' ?>">
                            <div class="form-group">
                                <label for="branding_name"><i class="fas fa-fw fa-random fa-sm text-muted mr-1"></i> <?= l('websites.input.branding.name') ?></label>
                                <input id="branding_name" type="text" class="form-control" name="branding_name" value="<?= $data->website->settings->branding_name ?? '' ?>" maxlength="128" />
                                <small class="form-text text-muted"><?= l('websites.input.branding.name_help') ?></small>
                            </div>

                            <div class="form-group">
                                <label for="branding_url"><i class="fas fa-fw fa-link fa-sm text-muted mr-1"></i> <?= l('websites.input.branding.url') ?></label>
                                <input id="branding_url" type="text" class="form-control" name="branding_url" value="<?= $data->website->settings->branding_url ?? '' ?>" maxlength="512" placeholder="<?= l('global.url_placeholder') ?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-sm btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#advanced_container" aria-expanded="false" aria-controls="advanced_container">
                    <i class="fas fa-fw fa-user-tie fa-sm mr-1"></i> <?= l('websites.input.advanced') ?>
                </button>

                <div class="collapse" data-parent="#website_update" id="advanced_container">
                    <div class="alert alert-info">
                        <i class="fas fa-fw fa-info-circle fa-sm mr-1"></i> <?= l('websites.input.keys_help') ?>
                    </div>

                    <div class="form-group">
                        <label for="public_key"><i class="fas fa-fw fa-lock fa-sm text-muted mr-1"></i> <?= l('websites.input.keys.public_key') ?></label>
                        <textarea id="public_key" type="text" class="form-control" name="public_key" maxlength="256"><?= $data->website->keys->public_key ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="private_key"><i class="fas fa-fw fa-key fa-sm text-muted mr-1"></i> <?= l('websites.input.keys.private_key') ?></label>
                        <textarea id="private_key" type="text" class="form-control" name="private_key" maxlength="256"><?= $data->website->keys->private_key ?></textarea>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn btn-block btn-primary"><?= l('global.update') ?></button>
            </form>

        </div>
    </div>
</div>

<?php ob_start() ?>
<script>
    'use strict';

let active_notification_handlers_per_resource_limit = <?= (int) $this->user->plan_settings->active_notification_handlers_per_resource_limit ?>;

    if(active_notification_handlers_per_resource_limit != -1) {
        let process_notification_handlers = () => {
            let selected = document.querySelectorAll('[name="notifications[]"]:checked').length;

            if(selected >= active_notification_handlers_per_resource_limit) {
                document.querySelectorAll('[name="notifications[]"]:not(:checked)').forEach(element => element.setAttribute('disabled', 'disabled'));
            } else {
                document.querySelectorAll('[name="notifications[]"]:not(:checked)').forEach(element => element.removeAttribute('disabled'));
            }
        }

        document.querySelectorAll('[name="notifications[]"]').forEach(element => element.addEventListener('change', process_notification_handlers));

        process_notification_handlers();
    }
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php include_view(THEME_PATH . 'views/partials/js_cropper.php') ?>
