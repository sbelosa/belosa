<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('recurring-campaigns') ?>"><?= l('recurring_campaigns.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('recurring_campaign_update.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <h1 class="h4 text-truncate"><i class="fas fa-fw fa-xs fa-retweet mr-1"></i> <?= l('recurring_campaign_update.header') ?></h1>
    <p></p>

    <div class="card">
        <div class="card-body">

            <form id="form" action="" method="post" role="form" enctype="multipart/form-data">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                <div class="form-group">
                    <label for="name"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('global.name') ?></label>
                    <input type="text" id="name" name="name" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" value="<?= $data->recurring_campaign->name ?>" required="required" />
                    <?= \Altum\Alerts::output_field_error('name') ?>
                </div>

                <div class="form-group">
                    <label for="website_id"><i class="fas fa-fw fa-sm fa-pager text-muted mr-1"></i> <?= l('websites.website') ?></label>
                    <select id="website_id" name="website_id" class="form-control <?= \Altum\Alerts::has_field_errors('website_id') ? 'is-invalid' : null ?>" required="required">
                        <?php foreach($data->websites as $website): ?>
                            <option value="<?= $website->website_id ?>" <?= $data->recurring_campaign->website_id == $website->website_id ? 'selected="selected"' : null ?>><?= $website->name . ' - ' . $website->host . $website->path ?></option>
                        <?php endforeach ?>
                    </select>
                    <?= \Altum\Alerts::output_field_error('website_id') ?>
                </div>

                <div class="form-group" data-character-counter="input">
                    <label for="title" class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-fw fa-sm fa-heading text-muted mr-1"></i> <?= l('global.title') ?></span>
                        <small class="text-muted" data-character-counter-wrapper></small>
                    </label>
                    <input type="text" id="title" name="title" class="form-control <?= \Altum\Alerts::has_field_errors('title') ? 'is-invalid' : null ?>" value="<?= $data->recurring_campaign->title ?>" maxlength="64" required="required" />
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
                    <input type="text" id="description" name="description" value="<?= $data->recurring_campaign->description ?>" class="form-control <?= \Altum\Alerts::has_field_errors('description') ? 'is-invalid' : null ?>" maxlength="128" required="required" />
                    <?= \Altum\Alerts::output_field_error('description') ?>
                    <small class="form-text text-muted"><?= l('campaigns.description_help') ?></small>
                    <small class="form-text text-muted"><?= l('campaigns.variables') ?></small>
                </div>

                <div class="form-group">
                    <label for="url"><i class="fas fa-fw fa-sm fa-link text-muted mr-1"></i> <?= l('global.url') ?></label>
                    <input type="url" id="url" name="url" value="<?= $data->recurring_campaign->url ?>" class="form-control <?= \Altum\Alerts::has_field_errors('url') ? 'is-invalid' : null ?>" maxlength="512" placeholder="<?= l('global.url_placeholder') ?>" />
                    <?= \Altum\Alerts::output_field_error('url') ?>
                    <small class="form-text text-muted"><?= l('campaigns.variables') ?></small>
                </div>

                <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= settings()->websites->recurring_campaign_image_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->websites->recurring_campaign_image_size_limit) ?>">
                    <label for="image"><i class="fas fa-fw fa-sm fa-image text-muted mr-1"></i> <?= l('global.image') ?></label>
                    <?= include_view(THEME_PATH . 'views/partials/file_image_input.php', ['uploads_file_key' => 'websites_recurring_campaigns_images', 'file_key' => 'image', 'already_existing_image' => $data->recurring_campaign->image, 'input_data' => 'data-crop data-aspect-ratio="1.5"']) ?>
                    <?= \Altum\Alerts::output_field_error('image') ?>
                    <small class="form-text text-muted"><?= l('campaigns.image_help') ?> <?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('websites_recurring_campaigns_images')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->websites->recurring_campaign_image_size_limit) ?></small>
                </div>

                <div class="form-group">
                    <div class="d-flex flex-wrap flex-row justify-content-between">
                        <label for="segment"><i class="fas fa-fw fa-sm fa-layer-group text-muted mr-1"></i> <?= l('campaigns.segment') ?> <span id="segment_count"></span></label>
                        <a href="<?= url('segment-create') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('segments.create') ?></a>
                    </div>
                    <select id="segment" name="segment" class="form-control <?= \Altum\Alerts::has_field_errors('segment') ? 'is-invalid' : null ?>" required="required">
                        <option value="all" <?= $data->recurring_campaign->segment == 'all' ? 'selected="selected"' : null ?>><?= l('campaigns.segment.all') ?></option>
                        <?php if (!empty($data->segments)): ?>
                            <optgroup label="<?= l('campaigns.segment.saved') ?>">
                                <?php foreach($data->segments as $segment): ?>
                                    <option value="<?= $segment->segment_id ?>" <?= $data->recurring_campaign->segment == $segment->segment_id ? 'selected="selected"' : null ?> data-website-id="<?= $segment->website_id ?>"><?= $segment->name ?></option>
                                <?php endforeach ?>
                            </optgroup>
                        <?php endif ?>
                    </select>
                    <?= \Altum\Alerts::output_field_error('segment') ?>
                </div>

                <div class="form-group custom-control custom-switch">
                    <input id="is_enabled" name="is_enabled" type="checkbox" class="custom-control-input" <?= $data->recurring_campaign->is_enabled ? 'checked="checked"' : null?>>
                    <label class="custom-control-label" for="is_enabled"><?= l('recurring_campaigns.is_enabled') ?></label>
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
                            <input type="text" id="button_title_1" name="button_title_1" class="form-control <?= \Altum\Alerts::has_field_errors('button_title_1') ? 'is-invalid' : null ?>" value="<?= $data->recurring_campaign->settings->button_title_1 ?>" maxlength="16" />
                            <?= \Altum\Alerts::output_field_error('button_title_1') ?>
                        </div>

                        <div class="form-group">
                            <label for="button_url_1"><i class="fas fa-fw fa-sm fa-link text-muted mr-1"></i> <?= l('global.url') ?></label>
                            <input type="text" id="button_url_1" name="button_url_1" class="form-control <?= \Altum\Alerts::has_field_errors('button_url_1') ? 'is-invalid' : null ?>" value="<?= $data->recurring_campaign->settings->button_url_1 ?>" maxlength="512" placeholder="<?= l('global.url_placeholder') ?>" />
                            <?= \Altum\Alerts::output_field_error('button_url_1') ?>
                        </div>
                    </div>

                    <h2 class="h6"><?= sprintf(l('campaigns.button_x'), 2) ?></h2>

                    <div class="p-3 bg-gray-50 rounded mb-4">
                        <div class="form-group">
                            <label for="button_title_2"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('global.title') ?></label>
                            <input type="text" id="button_title_2" name="button_title_2" class="form-control <?= \Altum\Alerts::has_field_errors('button_title_2') ? 'is-invalid' : null ?>" value="<?= $data->recurring_campaign->settings->button_title_2 ?>" maxlength="16" />
                            <?= \Altum\Alerts::output_field_error('button_title_2') ?>
                        </div>

                        <div class="form-group">
                            <label for="button_url_2"><i class="fas fa-fw fa-sm fa-link text-muted mr-1"></i> <?= l('global.url') ?></label>
                            <input type="text" id="button_url_2" name="button_url_2" class="form-control <?= \Altum\Alerts::has_field_errors('button_url_2') ? 'is-invalid' : null ?>" value="<?= $data->recurring_campaign->settings->button_url_2 ?>" maxlength="512" placeholder="<?= l('global.url_placeholder') ?>" />
                            <?= \Altum\Alerts::output_field_error('button_url_2') ?>
                        </div>
                    </div>
                </div>

                <button class="btn btn-sm btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#utm_container" aria-expanded="false" aria-controls="utm_container">
                    <i class="fas fa-fw fa-keyboard fa-sm mr-1"></i> <?= l('campaigns.utm') ?>
                </button>

                <div class="collapse" data-parent="#form" id="utm_container">
                    <div class="form-group">
                        <label for="utm_source"><i class="fas fa-fw fa-sitemap fa-sm text-muted mr-1"></i> <?= l('campaigns.utm_source') ?></label>
                        <input id="utm_source" type="text" class="form-control" name="utm_source" value="<?= $data->recurring_campaign->settings->utm->source ?? '' ?>" maxlength="128" placeholder="<?= l('campaigns.utm_source_placeholder') ?>" />
                    </div>

                    <div class="form-group">
                        <label for="utm_medium"><i class="fas fa-fw fa-inbox fa-sm text-muted mr-1"></i> <?= l('campaigns.utm_medium') ?></label>
                        <input id="utm_medium" type="text" class="form-control" name="utm_medium" value="<?= $data->recurring_campaign->settings->utm->medium ?? '' ?>" maxlength="128" placeholder="<?= l('campaigns.utm_medium_placeholder') ?>" />
                    </div>

                    <div class="form-group">
                        <label for="utm_campaign"><i class="fas fa-fw fa-bullhorn fa-sm text-muted mr-1"></i> <?= l('campaigns.utm_campaign') ?></label>
                        <input id="utm_campaign" type="text" class="form-control" name="utm_campaign" value="<?= $data->recurring_campaign->settings->utm->campaign ?? '' ?>" maxlength="128" placeholder="<?= l('campaigns.utm_campaign_placeholder') ?>" />
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
                        <input id="is_silent" name="is_silent" type="checkbox" class="custom-control-input" <?= $data->recurring_campaign->settings->is_silent ? 'checked="checked"' : null?>>
                        <label class="custom-control-label" for="is_silent"><?= l('campaigns.is_silent') ?></label>
                        <small class="form-text text-muted"><?= l('campaigns.is_silent_help') ?></small>
                    </div>

                    <div class="form-group custom-control custom-switch">
                        <input id="is_auto_hide" name="is_auto_hide" type="checkbox" class="custom-control-input" <?= $data->recurring_campaign->settings->is_auto_hide ? 'checked="checked"' : null?>>
                        <label class="custom-control-label" for="is_auto_hide"><?= l('campaigns.is_auto_hide') ?></label>
                        <small class="form-text text-muted"><?= l('campaigns.is_auto_hide_help') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="ttl"><i class="fas fa-fw fa-sm fa-stopwatch text-muted mr-1"></i> <?= l('campaigns.ttl') ?></label>
                        <select id="ttl" name="ttl" class="form-control <?= \Altum\Alerts::has_field_errors('ttl') ? 'is-invalid' : null ?>" required="required">
                            <?php foreach($data->notifications_ttl as $key => $value): ?>
                                <option value="<?= $key ?>" <?= $data->recurring_campaign->settings->ttl == $key ? 'selected="selected"' : null ?>><?= $value ?></option>
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
                                <option value="<?= $key ?>" <?= $data->recurring_campaign->settings->urgency == $key ? 'selected="selected"' : null ?>><?= l('campaigns.urgency.' . $key) ?></option>
                            <?php endforeach ?>
                        </select>
                        <?= \Altum\Alerts::output_field_error('urgency') ?>
                        <small class="form-text text-muted"><?= l('campaigns.urgency_help') ?></small>
                    </div>
                </div>

                <button class="btn btn-sm btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#recurring_container" aria-expanded="false" aria-controls="recurring_container">
                    <i class="fas fa-fw fa-retweet fa-sm mr-1"></i> <?= l('recurring_campaigns.recurring_campaign_settings') ?>
                </button>

                <div class="collapse show" id="recurring_container">
                    <div class="form-group">
                        <label for="frequency"><i class="fas fa-fw fa-sm fa-calendar-alt text-muted mr-1"></i> <?= l('recurring_campaigns.frequency') ?></label>
                        <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?=  $data->recurring_campaign->settings->frequency == 'daily' ? 'active"' : null?>">
                                    <input type="radio" name="frequency" value="daily" class="custom-control-input" <?=  $data->recurring_campaign->settings->frequency == 'daily' ? 'checked="checked"' : null?> required="required" />
                                    <i class="fas fa-calendar-day fa-fw fa-sm mr-1"></i> <?= l('recurring_campaigns.frequency.daily') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?=  $data->recurring_campaign->settings->frequency == 'weekly' ? 'active"' : null?>">
                                    <input type="radio" name="frequency" value="weekly" class="custom-control-input" <?=  $data->recurring_campaign->settings->frequency == 'weekly' ? 'checked="checked"' : null?> required="required" />
                                    <i class="fas fa-calendar-week fa-fw fa-sm mr-1"></i> <?= l('recurring_campaigns.frequency.weekly') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?=  $data->recurring_campaign->settings->frequency == 'monthly' ? 'active"' : null?>">
                                    <input type="radio" name="frequency" value="monthly" class="custom-control-input" <?=  $data->recurring_campaign->settings->frequency == 'monthly' ? 'checked="checked"' : null?> required="required" />
                                    <i class="fas fa-calendar-alt fa-fw fa-sm mr-1"></i> <?= l('recurring_campaigns.frequency.monthly') ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" data-frequency="weekly">
                        <label for="week_days"><i class="fas fa-fw fa-sm fa-calendar-week text-muted mr-1"></i> <?= l('recurring_campaigns.week_days') ?></label>

                        <div class="row">
                            <?php foreach(range(1,7) as $key): ?>
                                <div class="col-12 col-lg-6">
                                    <div class="custom-control custom-checkbox my-2">
                                        <input id="week_days_<?= $key ?>" name="week_days[]" value="<?= $key ?>" type="checkbox" class="custom-control-input" <?= in_array($key, $data->recurring_campaign->settings->week_days ?? []) ? 'checked="checked"' : null ?>>
                                        <label class="custom-control-label" for="week_days_<?= $key ?>">
                                            <span><?= l('global.date.long_days.' . $key) ?></span>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>

                    <div class="form-group" data-frequency="monthly">
                        <label for="month_days"><i class="fas fa-fw fa-sm fa-calendar-alt text-muted mr-1"></i> <?= l('recurring_campaigns.month_days') ?></label>

                        <div class="row">
                            <?php foreach(range(1,31) as $key): ?>
                                <div class="col-2">
                                    <div class="custom-control custom-checkbox my-2">
                                        <input id="month_days_<?= $key ?>" name="month_days[]" value="<?= $key ?>" type="checkbox" class="custom-control-input" <?= in_array($key, $data->recurring_campaign->settings->month_days ?? []) ? 'checked="checked"' : null ?>>
                                        <label class="custom-control-label" for="month_days_<?= $key ?>">
                                            <span><?= $key ?></span>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="time"><i class="fas fa-fw fa-sm fa-clock text-muted mr-1"></i> <?= l('recurring_campaigns.time') ?></label>
                        <input type="time" id="time" name="time" class="form-control <?= \Altum\Alerts::has_field_errors('time') ? 'is-invalid' : null ?>" value="<?= $data->recurring_campaign->settings->time ?>" required="required" />
                        <?= \Altum\Alerts::output_field_error('time') ?>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn btn-block btn-primary mt-4"><?= l('global.update') ?></button>
            </form>

        </div>
    </div>
</div>

<?php ob_start() ?>
<script>
    'use strict';

type_handler('input[name="frequency"]', 'data-frequency');
    document.querySelector('input[name="frequency"]') && document.querySelectorAll('input[name="frequency"]').forEach(element => element.addEventListener('change', () => { type_handler('input[name="frequency"]', 'data-frequency'); }));

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

    type_handler('[name="segment"]', 'data-segment');
    document.querySelector('[name="segment"]') && document.querySelectorAll('[name="segment"]').forEach(element => element.addEventListener('change', () => { type_handler('[name="segment"]', 'data-segment'); }));

    document.querySelector('#website_id').addEventListener('change', async event => {
        await get_segment_count();
        process_segments();
    });

    document.querySelector('#segment').addEventListener('change', async event => {
        await get_segment_count();
    });

    document.querySelectorAll('[name^="filters_"]').forEach(element => element.addEventListener('change', async event => {
        await get_segment_count();
    }));

    let get_segment_count = async () => {
        let segment = document.querySelector('#segment').value;
        let website_id = document.querySelector('#website_id').value;

        if(segment == 'custom') {
            document.querySelector('#segment_count').innerHTML = ``;
            return;
        }

        /* Display a loader */
        document.querySelector('#segment_count').innerHTML = `<div class="spinner-border spinner-border-sm" role="status"></div>`;

        /* Prepare query string */
        let query = new URLSearchParams();

        /* Filter preparing on query string */
        if(segment == 'filter') {
            query = new URLSearchParams(new FormData(document.querySelector('#form')));
        }

        query.set('type', segment);
        query.set('website_id', website_id);

        /* Send request to server */
        let response = await fetch(`${url}segments/get_segment_count?${query.toString()}`, {
            method: 'get',
        });

        let data = null;
        try {
            data = await response.json();
        } catch (error) {
            /* :)  */
        }

        if(!response.ok) {
            /* :)  */
        }

        if(data.status == 'error') {
            /* :)  */
        } else if(data.status == 'success') {
            document.querySelector('#segment_count').innerHTML = `(${data.details.count})`;
        }
    }

    get_segment_count();

    /* Process selected website for segments */
    let process_segments = () => {
        /* Enable/disable segments based on the selected website id */
        let selected_website_id = document.querySelector('#website_id').value;

        document.querySelectorAll('#segment option[data-website-id]').forEach(element => {
            if(element.getAttribute('data-website-id') == selected_website_id) {
                element.removeAttribute('disabled');
            } else {
                element.setAttribute('disabled', 'disabled');
            }
        });
    };

    process_segments();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php include_view(THEME_PATH . 'views/partials/js_cropper.php') ?>
<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>

