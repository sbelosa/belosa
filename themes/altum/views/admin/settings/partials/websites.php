<?php defined('ALTUMCODE') || die() ?>

<div id="websites">
    <div class="form-group">
        <label for="service_worker_file_name"><?= l('admin_settings.websites.service_worker_file_name') ?></label>
        <div class="input-group">
            <input id="service_worker_file_name" name="service_worker_file_name" type="text" class="form-control" value="<?= settings()->websites->service_worker_file_name ?>" />
            <div class="input-group-append">
                <span class="input-group-text">.js</span>
            </div>
        </div>
        <small class="form-text text-muted"><?= l('admin_settings.websites.service_worker_file_name_help') ?></small>
    </div>

    <div class="form-group">
        <label for="pixel_exposed_identifier"><?= l('admin_settings.websites.pixel_exposed_identifier') ?></label>
        <input id="pixel_exposed_identifier" type="text" name="pixel_exposed_identifier" class="form-control" value="<?= settings()->websites->pixel_exposed_identifier ?>" />
        <small class="form-text text-muted"><?= l('admin_settings.websites.pixel_exposed_identifier_help') ?></small>
    </div>

    <div class="form-group">
        <label for="pixel_cache"><?= l('admin_settings.websites.pixel_cache') ?></label>
        <div class="input-group">
            <input id="pixel_cache" type="number" min="0" name="pixel_cache" class="form-control" value="<?= settings()->websites->pixel_cache ?>" />
            <div class="input-group-append">
                <span class="input-group-text"><?= l('global.date.seconds') ?></span>
            </div>
        </div>
        <small class="form-text text-muted"><?= l('admin_settings.websites.pixel_cache_help') ?></small>
    </div>

    <div class="form-group">
        <label for="branding"><?= l('admin_settings.websites.branding') ?></label>
        <textarea id="branding" name="branding" class="form-control"><?= settings()->websites->branding ?></textarea>
        <small class="form-text text-muted"><?= l('admin_settings.websites.branding_help') ?></small>
    </div>

    <div class="form-group custom-control custom-switch">
        <input id="email_notices_is_enabled" name="email_notices_is_enabled" type="checkbox" class="custom-control-input" <?= settings()->websites->email_notices_is_enabled ? 'checked="checked"' : null?>>
        <label class="custom-control-label" for="email_notices_is_enabled"><?= l('admin_settings.websites.email_notices_is_enabled') ?></label>
        <small class="form-text text-muted"><?= l('admin_settings.websites.email_notices_is_enabled_help') ?></small>
    </div>

    <div <?= !\Altum\Plugin::is_active('pwa') ? 'data-toggle="tooltip" title="' . sprintf(l('admin_plugins.no_access'), \Altum\Plugin::get('pwa')->name ?? 'pwa') . '"' : null ?>>
        <div class="<?= !\Altum\Plugin::is_active('pwa') ? 'container-disabled' : null ?>">
            <div class="form-group custom-control custom-switch">
                <input id="pwas_is_enabled" name="pwas_is_enabled" type="checkbox" class="custom-control-input" <?= settings()->websites->pwas_is_enabled ? 'checked="checked"' : null?>>
                <label class="custom-control-label" for="pwas_is_enabled"><?= l('admin_settings.websites.pwas_is_enabled') ?></label>
                <small class="form-text text-muted"><?= l('admin_settings.websites.pwas_is_enabled_help') ?></small>
            </div>
        </div>
    </div>

    <div class="form-group custom-control custom-switch">
        <input id="domains_is_enabled" name="domains_is_enabled" type="checkbox" class="custom-control-input" <?= settings()->websites->domains_is_enabled ? 'checked="checked"' : null?>>
        <label class="custom-control-label" for="domains_is_enabled"><?= l('admin_settings.websites.domains_is_enabled') ?></label>
        <small class="form-text text-muted"><?= l('admin_settings.websites.domains_is_enabled_help') ?></small>
    </div>

    <div class="form-group">
        <label for="domains_custom_main_ip"><?= l('admin_settings.websites.domains_custom_main_ip') ?></label>
        <input id="domains_custom_main_ip" name="domains_custom_main_ip" type="text" class="form-control" value="<?= settings()->websites->domains_custom_main_ip ?>" placeholder="<?= $_SERVER['SERVER_ADDR'] ?>">
        <small class="form-text text-muted"><?= l('admin_settings.websites.domains_custom_main_ip_help') ?></small>
    </div>

    <div class="form-group">
        <label for="blacklisted_domains"><?= l('admin_settings.websites.blacklisted_domains') ?></label>
        <textarea id="blacklisted_domains" class="form-control" name="blacklisted_domains"><?= implode(',', settings()->websites->blacklisted_domains) ?></textarea>
        <small class="form-text text-muted"><?= l('admin_settings.websites.blacklisted_domains_help') ?></small>
    </div>

    <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 mb-4" type="button" data-toggle="collapse" data-target="#file_size_limits_container" aria-expanded="false" aria-controls="file_size_limits_container">
        <i class="fas fa-fw fa-file fa-sm mr-1"></i> <?= l('admin_settings.websites.file_size_limits') ?>
    </button>

    <div class="collapse" data-parent="#websites" id="file_size_limits_container">
        <?php foreach(['icon', 'campaign_image', 'flow_image', 'personal_notification_image', 'rss_automation_image', 'recurring_campaign_image'] as $key): ?>
            <div class="form-group">
                <label for="<?= $key . '_size_limit' ?>"><?= l('admin_settings.websites.' . $key . '_size_limit') ?></label>
                <div class="input-group">
                    <input id="<?= $key . '_size_limit' ?>" type="number" min="0" max="<?= get_max_upload() ?>" step="any" name="<?= $key . '_size_limit' ?>" class="form-control" value="<?= settings()->websites->{$key . '_size_limit'} ?>" />
                    <div class="input-group-append">
                        <span class="input-group-text"><?= l('global.mb') ?></span>
                    </div>
                </div>
                <small class="form-text text-muted"><?= l('global.accessibility.admin_file_size_limit_help') ?></small>
            </div>
        <?php endforeach ?>
    </div>

    <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 mb-4" type="button" data-toggle="collapse" data-target="#cron_settings_container" aria-expanded="false" aria-controls="cron_settings_container">
        <i class="fas fa-fw fa-arrows-rotate fa-sm mr-1"></i> <?= l('admin_settings.cron.cron_settings') ?>
    </button>

    <div class="collapse" data-parent="#websites" id="cron_settings_container">
        <div class="alert alert-danger mb-3"><?= l('admin_settings.cron.cron_settings_help') ?></div>

        <div class="form-group">
            <label for="campaigns_notifications_per_cron"><?= l('admin_settings.websites.campaigns_notifications_per_cron') ?></label>
            <input id="campaigns_notifications_per_cron" type="number" min="0" name="campaigns_notifications_per_cron" class="form-control" value="<?= settings()->websites->campaigns_notifications_per_cron ?? 500 ?>" />
        </div>

        <div class="form-group">
            <label for="campaigns_notifications_per_cron_loop"><?= l('admin_settings.websites.campaigns_notifications_per_cron_loop') ?></label>
            <input id="campaigns_notifications_per_cron_loop" type="number" min="0" name="campaigns_notifications_per_cron_loop" class="form-control" value="<?= settings()->websites->campaigns_notifications_per_cron_loop ?? 100 ?>" />
        </div>

        <div class="form-group">
            <label for="campaigns_notifications_per_cron_loop_sent"><?= l('admin_settings.websites.campaigns_notifications_per_cron_loop_sent') ?></label>
            <input id="campaigns_notifications_per_cron_loop_sent" type="number" min="0" name="campaigns_notifications_per_cron_loop_sent" class="form-control" value="<?= settings()->websites->campaigns_notifications_per_cron_loop_sent ?? 25 ?>" />
        </div>

        <div class="form-group">
            <label for="flows_subscribers_per_cron"><?= l('admin_settings.websites.flows_subscribers_per_cron') ?></label>
            <input id="flows_subscribers_per_cron" type="number" min="0" name="flows_subscribers_per_cron" class="form-control" value="<?= settings()->websites->flows_subscribers_per_cron ?? 100 ?>" />
        </div>

        <div class="form-group">
            <label for="flows_notifications_per_cron"><?= l('admin_settings.websites.flows_notifications_per_cron') ?></label>
            <input id="flows_notifications_per_cron" type="number" min="0" name="flows_notifications_per_cron" class="form-control" value="<?= settings()->websites->flows_notifications_per_cron ?? 100 ?>" />
        </div>

        <div class="form-group">
            <label for="personal_notifications_per_cron"><?= l('admin_settings.websites.personal_notifications_per_cron') ?></label>
            <input id="personal_notifications_per_cron" type="number" min="0" name="personal_notifications_per_cron" class="form-control" value="<?= settings()->websites->personal_notifications_per_cron ?? 100 ?>" />
        </div>

        <div class="form-group">
            <label for="rss_automations_per_cron"><?= l('admin_settings.websites.rss_automations_per_cron') ?></label>
            <input id="rss_automations_per_cron" type="number" min="0" name="rss_automations_per_cron" class="form-control" value="<?= settings()->websites->rss_automations_per_cron ?? 10 ?>" />
        </div>

        <div class="form-group">
            <label for="recurring_campaigns_per_cron"><?= l('admin_settings.websites.recurring_campaigns_per_cron') ?></label>
            <input id="recurring_campaigns_per_cron" type="number" min="0" name="recurring_campaigns_per_cron" class="form-control" value="<?= settings()->websites->recurring_campaigns_per_cron ?? 10 ?>" />
        </div>
    </div>
</div>

<button type="submit" name="submit" class="btn btn-lg btn-block btn-primary mt-4"><?= l('global.update') ?></button>
