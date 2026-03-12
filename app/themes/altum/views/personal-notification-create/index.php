<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('personal-notifications') ?>"><?= l('personal_notifications.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('personal_notification_create.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <h1 class="h4 text-truncate"><i class="fas fa-fw fa-xs fa-code-branch mr-1"></i> <?= l('personal_notification_create.header') ?></h1>
    <p></p>

    <div class="card">
        <div class="card-body">

            <form id="form" action="" method="post" role="form" enctype="multipart/form-data">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                <div class="form-group">
                    <label for="name"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('global.name') ?></label>
                    <input type="text" id="name" name="name" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" value="<?= $data->values['name'] ?>" required="required" />
                    <?= \Altum\Alerts::output_field_error('name') ?>
                </div>

                <div class="form-group">
                    <label for="website_id"><i class="fas fa-fw fa-sm fa-pager text-muted mr-1"></i> <?= l('websites.website') ?></label>
                    <select id="website_id" name="website_id" class="form-control <?= \Altum\Alerts::has_field_errors('website_id') ? 'is-invalid' : null ?>" required="required">
                        <?php foreach($data->websites as $website): ?>
                            <option value="<?= $website->website_id ?>" <?= $data->values['website_id'] == $website->website_id ? 'selected="selected"' : null ?>><?= $website->name . ' - ' . $website->host . $website->path ?></option>
                        <?php endforeach ?>
                    </select>
                    <?= \Altum\Alerts::output_field_error('website_id') ?>
                </div>

                <div class="form-group">
                    <label for="subscriber_id"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('personal_notifications.subscriber_id') ?></label>
                    <input type="text" id="subscriber_id" name="subscriber_id" class="form-control <?= \Altum\Alerts::has_field_errors('subscriber_id') ? 'is-invalid' : null ?>" value="<?= $data->values['subscriber_id'] ?>" required="required" />
                    <?= \Altum\Alerts::output_field_error('subscriber_id') ?>
                </div>

                <div class="form-group" data-character-counter="input">
                    <label for="title" class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-fw fa-sm fa-heading text-muted mr-1"></i> <?= l('global.title') ?></span>
                        <small class="text-muted" data-character-counter-wrapper></small>
                    </label>
                    <input type="text" id="title" name="title" class="form-control <?= \Altum\Alerts::has_field_errors('title') ? 'is-invalid' : null ?>" value="<?= $data->values['title'] ?>" maxlength="64" required="required" />
                    <?= \Altum\Alerts::output_field_error('title') ?>
                    <small class="form-text text-muted"><?= l('campaigns.title_help') ?></small>
                    <small class="form-text text-muted"><?= sprintf(l('global.variables'), '<code data-copy>' . implode('</code> , <code data-copy>',  ['{{CONTINENT_NAME}}', '{{COUNTRY_NAME}}', '{{CITY_NAME}}', '{{DEVICE_TYPE}}', '{{OS_NAME}}', '{{BROWSER_NAME}}', '{{BROWSER_LANGUAGE}}', '{{CUSTOM_PARAMETERS:KEY}}']) . '</code>') ?></small>
                    <small class="form-text text-muted"><?= l('global.spintax_help') ?></small>
                </div>

                <div class="form-group" data-character-counter="input">
                    <label for="description" class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-fw fa-sm fa-paragraph text-muted mr-1"></i> <?= l('global.description') ?></span>
                        <small class="text-muted" data-character-counter-wrapper></small>
                    </label>
                    <input type="text" id="description" name="description" value="<?= $data->values['description'] ?>" class="form-control <?= \Altum\Alerts::has_field_errors('description') ? 'is-invalid' : null ?>" maxlength="128" required="required" />
                    <?= \Altum\Alerts::output_field_error('description') ?>
                    <small class="form-text text-muted"><?= l('campaigns.description_help') ?></small>
                    <small class="form-text text-muted"><?= l('campaigns.variables') ?></small>
                </div>

                <div class="form-group">
                    <label for="url"><i class="fas fa-fw fa-sm fa-link text-muted mr-1"></i> <?= l('global.url') ?></label>
                    <input type="text" id="url" name="url" value="<?= $data->values['url'] ?>" class="form-control <?= \Altum\Alerts::has_field_errors('url') ? 'is-invalid' : null ?>" maxlength="512" placeholder="<?= l('global.url_placeholder') ?>" />
                    <?= \Altum\Alerts::output_field_error('url') ?>
                    <small class="form-text text-muted"><?= l('campaigns.variables') ?></small>
                </div>

                <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= settings()->websites->personal_notification_image_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->websites->personal_notification_image_size_limit) ?>">
                    <label for="image"><i class="fas fa-fw fa-sm fa-image text-muted mr-1"></i> <?= l('global.image') ?></label>
                    <?= include_view(THEME_PATH . 'views/partials/file_image_input.php', ['uploads_file_key' => 'websites_personal_notifications_images', 'file_key' => 'image', 'already_existing_image' => null, 'input_data' => 'data-crop data-aspect-ratio="1.5"']) ?>
                    <?= \Altum\Alerts::output_field_error('image') ?>
                    <small class="form-text text-muted"><?= l('campaigns.image_help') ?> <?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('websites_personal_notifications_images')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->websites->personal_notification_image_size_limit) ?></small>
                </div>

                <button class="btn btn-sm btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#buttons_container" aria-expanded="false" aria-controls="buttons_container">
                    <i class="fas fa-fw fa-mouse fa-sm mr-1"></i> <?= l('campaigns.buttons') ?>
                </button>

                <div class="collapse" data-parent="#form" id="buttons_container">
                    <div class="alert alert-info">
                        <i class="fas fa-fw fa-sm fa-info-circle mr-2"></i> <?= l('campaigns.buttons_info') ?>
                    </div>

                    <div class="alert alert-gray-400">
                        <i class="fas fa-fw fa-sm fa-code mr-2"></i> <?= l('campaigns.variables') ?>
                    </div>

                    <h2 class="h6"><?= sprintf(l('campaigns.button_x'), 1) ?></h2>

                    <div class="p-3 bg-gray-50 rounded mb-4">
                        <div class="form-group">
                            <label for="button_title_1"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('global.title') ?></label>
                            <input type="text" id="button_title_1" name="button_title_1" class="form-control <?= \Altum\Alerts::has_field_errors('button_title_1') ? 'is-invalid' : null ?>" value="<?= $data->values['button_title_1'] ?>" maxlength="16" />
                            <?= \Altum\Alerts::output_field_error('button_title_1') ?>
                        </div>

                        <div class="form-group">
                            <label for="button_url_1"><i class="fas fa-fw fa-sm fa-link text-muted mr-1"></i> <?= l('global.url') ?></label>
                            <input type="text" id="button_url_1" name="button_url_1" class="form-control <?= \Altum\Alerts::has_field_errors('button_url_1') ? 'is-invalid' : null ?>" value="<?= $data->values['button_url_1'] ?>" maxlength="512" placeholder="<?= l('global.url_placeholder') ?>" />
                            <?= \Altum\Alerts::output_field_error('button_url_1') ?>
                        </div>
                    </div>

                    <h2 class="h6"><?= sprintf(l('campaigns.button_x'), 2) ?></h2>

                    <div class="p-3 bg-gray-50 rounded mb-4">
                        <div class="form-group">
                            <label for="button_title_2"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('global.title') ?></label>
                            <input type="text" id="button_title_2" name="button_title_2" class="form-control <?= \Altum\Alerts::has_field_errors('button_title_2') ? 'is-invalid' : null ?>" value="<?= $data->values['button_title_2'] ?>" maxlength="16" />
                            <?= \Altum\Alerts::output_field_error('button_title_2') ?>
                        </div>

                        <div class="form-group">
                            <label for="button_url_2"><i class="fas fa-fw fa-sm fa-link text-muted mr-1"></i> <?= l('global.url') ?></label>
                            <input type="text" id="button_url_2" name="button_url_2" class="form-control <?= \Altum\Alerts::has_field_errors('button_url_2') ? 'is-invalid' : null ?>" value="<?= $data->values['button_url_2'] ?>" maxlength="512" placeholder="<?= l('global.url_placeholder') ?>" />
                            <?= \Altum\Alerts::output_field_error('button_url_2') ?>
                        </div>
                    </div>
                </div>

                <button class="btn btn-sm btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#scheduling_container" aria-expanded="false" aria-controls="scheduling_container">
                    <i class="fas fa-fw fa-calendar-day fa-sm mr-1"></i> <?= l('campaigns.scheduling') ?>
                </button>

                <div class="collapse" data-parent="#form" id="scheduling_container">
                    <div class="form-group custom-control custom-switch">
                        <input
                                id="is_scheduled"
                                name="is_scheduled"
                                type="checkbox"
                                class="custom-control-input"
                            <?= $data->values['is_scheduled'] && !empty($data->values['scheduled_datetime']) ? 'checked="checked"' : null ?>
                        >
                        <label class="custom-control-label" for="is_scheduled"><?= l('campaigns.is_scheduled') ?></label>
                    </div>

                    <div id="is_scheduled_container" class="d-none">
                        <div class="form-group">
                            <label for="scheduled_datetime"><i class="fas fa-fw fa-calendar-day fa-sm text-muted mr-1"></i> <?= l('campaigns.scheduled_datetime') ?></label>
                            <input
                                    id="scheduled_datetime"
                                    type="text"
                                    class="form-control"
                                    name="scheduled_datetime"
                                    value="<?= (new \DateTime($data->values['scheduled_datetime'], new \DateTimeZone(\Altum\Date::$default_timezone)))->setTimezone(new \DateTimeZone($this->user->timezone))->format('Y-m-d H:i:s'); ?>"
                                    placeholder="<?= l('campaigns.scheduled_datetime') ?>"
                                    autocomplete="off"
                                    data-daterangepicker
                            />
                        </div>
                    </div>
                </div>

                <button class="btn btn-sm btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#utm_container" aria-expanded="false" aria-controls="utm_container">
                    <i class="fas fa-fw fa-keyboard fa-sm mr-1"></i> <?= l('campaigns.utm') ?>
                </button>

                <div class="collapse" data-parent="#form" id="utm_container">
                    <div class="form-group">
                        <label for="utm_source"><i class="fas fa-fw fa-sitemap fa-sm text-muted mr-1"></i> <?= l('campaigns.utm_source') ?></label>
                        <input id="utm_source" type="text" class="form-control" name="utm_source" value="<?= $data->values['utm']['source'] ?? '' ?>" maxlength="128" placeholder="<?= l('campaigns.utm_source_placeholder') ?>" />
                    </div>

                    <div class="form-group">
                        <label for="utm_medium"><i class="fas fa-fw fa-inbox fa-sm text-muted mr-1"></i> <?= l('campaigns.utm_medium') ?></label>
                        <input id="utm_medium" type="text" class="form-control" name="utm_medium" value="<?= $data->values['utm']['medium'] ?? '' ?>" maxlength="128" placeholder="<?= l('campaigns.utm_medium_placeholder') ?>" />
                    </div>

                    <div class="form-group">
                        <label for="utm_campaign"><i class="fas fa-fw fa-bullhorn fa-sm text-muted mr-1"></i> <?= l('campaigns.utm_campaign') ?></label>
                        <input id="utm_campaign" type="text" class="form-control" name="utm_campaign" value="<?= $data->values['utm']['campaign'] ?? '' ?>" maxlength="128" placeholder="<?= l('campaigns.utm_campaign_placeholder') ?>" />
                    </div>

                    <div class="form-group">
                        <label for="utm_preview"><i class="fas fa-fw fa-eye fa-sm text-muted mr-1"></i> <?= l('campaigns.utm_preview') ?></label>
                        <input id="utm_preview" type="text" class="form-control-plaintext" name="utm_preview" readonly="readonly" />
                        <small class="form-text text-muted"><?= l('campaigns.utm_preview_help') ?></small>
                    </div>
                </div>

                <button class="btn btn-sm btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#advanced_container" aria-expanded="false" aria-controls="advanced_container">
                    <i class="fas fa-fw fa-user-tie fa-sm mr-1"></i> <?= l('campaigns.advanced') ?>
                </button>

                <div class="collapse" data-parent="#form" id="advanced_container">
                    <div class="form-group custom-control custom-switch">
                        <input id="is_silent" name="is_silent" type="checkbox" class="custom-control-input" <?= $data->values['is_silent'] ? 'checked="checked"' : null?>>
                        <label class="custom-control-label" for="is_silent"><?= l('campaigns.is_silent') ?></label>
                        <small class="form-text text-muted"><?= l('campaigns.is_silent_help') ?></small>
                    </div>

                    <div class="form-group custom-control custom-switch">
                        <input id="is_auto_hide" name="is_auto_hide" type="checkbox" class="custom-control-input" <?= $data->values['is_auto_hide'] ? 'checked="checked"' : null?>>
                        <label class="custom-control-label" for="is_auto_hide"><?= l('campaigns.is_auto_hide') ?></label>
                        <small class="form-text text-muted"><?= l('campaigns.is_auto_hide_help') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="ttl"><i class="fas fa-fw fa-sm fa-stopwatch text-muted mr-1"></i> <?= l('campaigns.ttl') ?></label>
                        <select id="ttl" name="ttl" class="form-control <?= \Altum\Alerts::has_field_errors('ttl') ? 'is-invalid' : null ?>" required="required">
                            <?php foreach($data->notifications_ttl as $key => $value): ?>
                                <option value="<?= $key ?>" <?= $data->values['ttl'] == $key ? 'selected="selected"' : null ?>><?= $value ?></option>
                            <?php endforeach ?>
                        </select>
                        <?= \Altum\Alerts::output_field_error('ttl') ?>
                        <small class="form-text text-muted"><?= l('campaigns.ttl_help') ?></small>
                        <small class="form-text text-muted"><?= l('campaigns.ttl_help2') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="urgency"><i class="fas fa-fw fa-sm fa-tachometer-alt text-muted mr-1"></i> <?= l('campaigns.urgency') ?></label>
                        <select id="urgency" name="urgency" class="form-control <?= \Altum\Alerts::has_field_errors('urgency') ? 'is-invalid' : null ?>" required="required">
                            <?php foreach(['low', 'normal', 'high'] as $key): ?>
                                <option value="<?= $key ?>" <?= $data->values['urgency'] == $key ? 'selected="selected"' : null ?>><?= l('campaigns.urgency.' . $key) ?></option>
                            <?php endforeach ?>
                        </select>
                        <?= \Altum\Alerts::output_field_error('urgency') ?>
                        <small class="form-text text-muted"><?= l('campaigns.urgency_help') ?></small>
                    </div>
                </div>

                <button type="submit" name="save" class="btn btn-sm btn-block btn-outline-primary mt-4"><?= l('personal_notifications.save') ?></button>
                <button type="submit" name="send" class="btn btn-block btn-primary mt-3"><?= l('personal_notifications.send') ?></button>
            </form>

        </div>
    </div>
</div>

<?php ob_start() ?>
<link href="<?= ASSETS_FULL_URL . 'css/libraries/daterangepicker.min.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/moment.min.js?v=' . PRODUCT_CODE ?>"></script>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/daterangepicker.min.js?v=' . PRODUCT_CODE ?>"></script>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/moment-timezone-with-data-10-year-range.min.js?v=' . PRODUCT_CODE ?>"></script>

<script>
    'use strict';

/* UTM */
    let process_utm = () => {
        let utm_source = document.querySelector('input[name="utm_source"]').value;
        let utm_medium = document.querySelector('input[name="utm_medium"]').value;
        let utm_campaign = document.querySelector('input[name="utm_campaign"]').value;
        let utm_preview = <?= json_encode(l('global.none')) ?>;

        if(utm_source || utm_medium || utm_campaign) {
            let link = new URL(<?= json_encode(SITE_URL) ?>);

            if(utm_source) link.searchParams.set('utm_source', utm_source.trim());
            if(utm_medium) link.searchParams.set('utm_medium', utm_medium.trim());
            if(utm_campaign) link.searchParams.set('utm_campaign', utm_campaign.trim());

            utm_preview = '?' + link.searchParams.toString();
        }

        document.querySelector('input[name="utm_preview"]').value = utm_preview;
    }

    document.querySelectorAll('input[name="utm_source"], input[name="utm_medium"], input[name="utm_campaign"]').forEach(element => {
        ['change', 'paste', 'keyup'].forEach(event_type => {
            element.addEventListener(event_type, process_utm);
        });
    })

    process_utm();

    /* Schedule */
    let schedule_handler = () => {
        if(document.querySelector('#is_scheduled').checked) {
            document.querySelector('#is_scheduled_container').classList.remove('d-none');
        } else {
            document.querySelector('#is_scheduled_container').classList.add('d-none');
        }
    };

    document.querySelector('#is_scheduled').addEventListener('change', schedule_handler);

    schedule_handler();

    /* Daterangepicker */
    let locale = <?= json_encode(require APP_PATH . 'includes/daterangepicker_translations.php') ?>;
    $('[data-daterangepicker]').daterangepicker({
        minDate: "<?= (new \DateTime('', new \DateTimeZone(\Altum\Date::$default_timezone)))->setTimezone(new \DateTimeZone($this->user->timezone))->format('Y-m-d H:i:s'); ?>",
        alwaysShowCalendars: true,
        singleCalendar: true,
        singleDatePicker: true,
        locale: {...locale, format: 'YYYY-MM-DD HH:mm:ss'},
        timePicker: true,
        timePicker24Hour: true,
        timePickerSeconds: true,
    }, (start, end, label) => {});
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php include_view(THEME_PATH . 'views/partials/js_cropper.php') ?>
<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>

