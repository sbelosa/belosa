<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('segments') ?>"><?= l('segments.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('segment_create.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <h1 class="h4 text-truncate"><i class="fas fa-fw fa-xs fa-layer-group mr-1"></i> <?= l('segment_create.header') ?></h1>
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
                    <label for="type"><i class="fas fa-fw fa-sm fa-layer-group text-muted mr-1"></i> <?= l('global.type') ?> <span id="segment_count"></span></label>
                    <select id="type" name="type" class="form-control <?= \Altum\Alerts::has_field_errors('type') ? 'is-invalid' : null ?>" required="required">
                        <option value="custom" <?= $data->values['type'] == 'custom' ? 'selected="selected"' : null ?>><?= l('segments.type.custom') ?></option>
                        <option value="filter" <?= $data->values['type'] == 'filter' ? 'selected="selected"' : null ?>><?= l('segments.type.filter') ?></option>
                    </select>
                    <?= \Altum\Alerts::output_field_error('segment') ?>
                </div>

                <div class="form-group" data-type="custom">
                    <label for="subscribers_ids"><i class="fas fa-fw fa-sm fa-users text-muted mr-1"></i> <?= l('segments.subscribers_ids') ?></label>
                    <input type="text" id="subscribers_ids" name="subscribers_ids" value="<?= $data->values['subscribers_ids'] ?>" class="form-control <?= \Altum\Alerts::has_field_errors('subscribers_ids') ? 'is-invalid' : null ?>" placeholder="<?= l('segments.subscribers_ids_placeholder') ?>" required="required" />
                    <?= \Altum\Alerts::output_field_error('subscribers_ids') ?>
                    <small class="form-text text-muted"><?= l('segments.subscribers_ids_help') ?></small>
                </div>

                <div class="form-group" data-type="filter">
                    <div class="form-group">
                        <label for="filters_continents"><i class="fas fa-fw fa-sm fa-globe-europe text-muted mr-1"></i> <?= l('global.continents') ?></label>
                        <select id="filters_continents" name="filters_continents[]" class="custom-select" multiple="multiple">
                            <?php foreach(get_continents_array() as $continent_code => $continent_name): ?>
                                <option value="<?= $continent_code ?>" <?= in_array($continent_code,$data->values['filters_continents'] ?? []) ? 'selected="selected"' : null ?>><?= $continent_name ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" data-type="filter">
                    <div class="form-group">
                        <label for="filters_countries"><i class="fas fa-fw fa-sm fa-flag text-muted mr-1"></i> <?= l('global.countries') ?></label>
                        <select id="filters_countries" name="filters_countries[]" class="custom-select" multiple="multiple">
                            <?php foreach(get_countries_array() as $key => $value): ?>
                                <option value="<?= $key ?>" <?= in_array($key, $data->values['filters_countries'] ?? []) ? 'selected="selected"' : null ?>><?= $value ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" data-type="filter">
                    <label for="filters_cities"><i class="fas fa-fw fa-sm fa-city text-muted mr-1"></i> <?= l('global.cities') ?></label>
                    <input type="text" id="filters_cities" name="filters_cities" value="<?= $data->values['filters_cities'] ?>" class="form-control" placeholder="<?= l('segments.cities_placeholder') ?>" />
                    <?= \Altum\Alerts::output_field_error('filters_cities') ?>
                    <small class="form-text text-muted"><?= l('segments.cities_help') ?></small>
                </div>

                <div class="form-group" data-type="filter">
                    <label for="device_type"><i class="fas fa-fw fa-sm fa-laptop text-muted mr-1"></i> <?= l('global.device') ?></label>
                    <div class="row">
                        <?php foreach(['desktop', 'tablet', 'mobile'] as $device_type): ?>
                            <div class="col-12 col-md-4 mb-3 mb-md-0">
                                <div class="custom-control custom-checkbox">
                                    <input id="<?= 'filters_device_type###' . $device_type ?>" name="filters_device_type[]" value="<?= $device_type ?>" type="checkbox" class="custom-control-input" <?= in_array($device_type, $data->values['filters_device_type'] ?? []) ? 'checked="checked"' : null ?>>
                                    <label class="custom-control-label" for="<?= 'filters_device_type###' . $device_type ?>"><?= l('global.device.' . $device_type) ?></label>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="form-group" data-type="filter">
                    <label for="filters_operating_systems"><i class="fas fa-fw fa-server fa-sm text-muted mr-1"></i> <?= l('segments.operating_systems') ?></label>
                    <select id="filters_operating_systems" name="filters_operating_systems[]" class="custom-select" multiple="multiple">
                        <?php foreach(['iOS', 'Android', 'Windows', 'OS X', 'Linux', 'Ubuntu', 'Chrome OS'] as $os_name): ?>
                            <option value="<?= $os_name ?>" <?= in_array($os_name, $data->values['filters_operating_systems'] ?? []) ? 'selected="selected"' : null ?>><?= $os_name ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="form-group" data-type="filter">
                    <label for="filters_browsers"><i class="fas fa-fw fa-window-restore fa-sm text-muted mr-1"></i> <?= l('segments.browsers') ?></label>
                    <select id="filters_browsers" name="filters_browsers[]" class="custom-select" multiple="multiple">
                        <?php foreach(['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Samsung Internet'] as $browser_name): ?>
                            <option value="<?= $browser_name ?>" <?= in_array($browser_name, $data->values['filters_browsers'] ?? []) ? 'selected="selected"' : null ?>><?= $browser_name ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="form-group" data-type="filter">
                    <label for="filters_languages"><i class="fas fa-fw fa-language fa-sm text-muted mr-1"></i> <?= l('segments.languages') ?></label>
                    <select id="filters_languages" name="filters_languages[]" class="custom-select" multiple="multiple">
                        <?php foreach(get_locale_languages_array() as $locale => $language): ?>
                            <option value="<?= $locale ?>" <?= in_array($locale, $data->values['filters_languages'] ?? []) ? 'selected="selected"' : null ?>><?= $language ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="form-group" data-type="filter">
                    <label for="filters_subscribed_on_url"><i class="fas fa-fw fa-sm fa-link text-muted mr-1"></i> <?= l('subscribers.subscribed_on_url') ?></label>
                    <input type="text" id="filters_subscribed_on_url" name="filters_subscribed_on_url" value="<?= $data->values['filters_subscribed_on_url'] ?? '' ?>" maxlength="2048" class="form-control" placeholder="<?= l('global.url_placeholder') ?>" />
                    <?= \Altum\Alerts::output_field_error('filters_subscribed_on_url') ?>
                </div>

                <div class="form-group" data-type="filter">
                    <label><i class="fas fa-fw fa-fingerprint fa-sm text-muted mr-1"></i> <?= l('subscriber.custom_parameters') ?></label>
                    <div id="custom_parameters">
                        <?php foreach($data->values['filters_custom_parameters'] ?? [] as $key => $custom_parameter): ?>
                            <div class="custom_parameter p-3 bg-gray-50 rounded mb-4">
                                <div class="form-row">
                                    <div class="form-group col-lg-4">
                                        <label for="<?= 'filters_custom_parameter_key[' . $key . ']' ?>"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('segments.custom_parameter_key') ?></label>
                                        <input id="<?= 'filters_custom_parameter_key[' . $key . ']' ?>" type="text" name="filters_custom_parameter_key[<?= $key ?>]" class="form-control" value="<?= $custom_parameter->key ?>" required="required" />
                                    </div>

                                    <div class="form-group col-lg-4">
                                        <label for="<?= 'filters_custom_parameter_condition[' . $key . ']' ?>"><i class="fas fa-fw fa-sm fa-code text-muted mr-1"></i> <?= l('segments.custom_parameter_condition') ?></label>
                                        <select id="<?= 'filters_custom_parameter_condition[' . $key . ']' ?>" name="filters_custom_parameter_condition[<?= $key ?>]" class="form-control" required="required">
                                            <?php foreach(['exact', 'not_exact', 'contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with', 'bigger_than', 'lower_than'] as $condition): ?>
                                                <option value="<?= $condition ?>" <?= ($custom_parameter->condition ?? 'exact') == $condition ? 'selected="selected"' : null ?>><?= l('segments.' . $condition) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-lg-4">
                                        <label for="<?= 'filters_custom_parameter_value[' . $key . ']' ?>"><i class="fas fa-fw fa-sm fa-pen text-muted mr-1"></i> <?= l('segments.custom_parameter_value') ?></label>
                                        <input id="<?= 'filters_custom_parameter_value[' . $key . ']' ?>" type="text" name="filters_custom_parameter_value[<?= $key ?>]" class="form-control" value="<?= $custom_parameter->value ?>" required="required" />
                                    </div>
                                </div>

                                <button type="button" data-remove="custom_parameters" class="btn btn-block btn-outline-danger"><i class="fas fa-fw fa-times fa-sm mr-1"></i> <?= l('global.delete') ?></button>
                            </div>
                        <?php endforeach ?>
                    </div>

                    <div class="mb-4">
                        <button data-add="custom_parameters" type="button" class="btn btn-block btn-outline-success"><i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('global.create') ?></button>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn btn-block btn-primary mt-4"><?= l('global.create') ?></button>
            </form>

        </div>
    </div>
</div>

<template id="template_custom_parameters">
    <div class="custom_parameter p-3 bg-gray-50 rounded mb-4">
        <div class="form-row">
            <div class="form-group col-lg-4">
                <label for="filters_custom_parameter_key"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('segments.custom_parameter_key') ?></label>
                <input id="filters_custom_parameter_key" type="text" name="filters_custom_parameter_key[]" class="form-control" value="" required="required" />
            </div>

            <div class="form-group col-lg-4">
                <label for="filters_custom_parameter_condition"><i class="fas fa-fw fa-sm fa-code text-muted mr-1"></i> <?= l('segments.custom_parameter_condition') ?></label>
                <select id="filters_custom_parameter_condition" name="filters_custom_parameter_condition[]" class="form-control" required="required">
                    <?php foreach(['exact', 'not_exact', 'contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with', 'bigger_than', 'lower_than'] as $condition): ?>
                     <option value="<?= $condition ?>"><?= l('segments.' . $condition) ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="form-group col-lg-4">
                <label for="filters_custom_parameter_value"><i class="fas fa-fw fa-sm fa-pen text-muted mr-1"></i> <?= l('segments.custom_parameter_value') ?></label>
                <input id="filters_custom_parameter_value" type="text" name="filters_custom_parameter_value[]" class="form-control" value="" required="required" />
            </div>
        </div>

        <button type="button" data-remove="request" class="btn btn-block btn-outline-danger"><i class="fas fa-fw fa-times"></i> <?= l('global.delete') ?></button>
    </div>
</template>

<?php ob_start() ?>
<script>
    'use strict';
    
/* add new  */
    let add = async event => {
        let type = event.currentTarget.getAttribute('data-add');
        let clone = document.querySelector(`#template_${type}`).content.cloneNode(true);
        let count = document.querySelectorAll(`#${type} .mb-4`).length;

        if(count >= 50) return;

        clone.querySelector(`input[name="filters_custom_parameter_key[]"`).closest('.form-group').querySelector('label').setAttribute('for', `filters_custom_parameter_key_${count}`);
        clone.querySelector(`input[name="filters_custom_parameter_key[]"`).setAttribute('id', `filters_custom_parameter_key_${count}`);
        clone.querySelector(`input[name="filters_custom_parameter_key[]"`).setAttribute('name', `filters_custom_parameter_key[${count}]`);

        clone.querySelector(`select[name="filters_custom_parameter_condition[]"`).closest('.form-group').querySelector('label').setAttribute('for', `filters_custom_parameter_condition_${count}`);
        clone.querySelector(`select[name="filters_custom_parameter_condition[]"`).setAttribute('id', `filters_custom_parameter_condition_${count}`);
        clone.querySelector(`select[name="filters_custom_parameter_condition[]"`).setAttribute('name', `filters_custom_parameter_condition[${count}]`);

        clone.querySelector(`input[name="filters_custom_parameter_value[]"`).closest('.form-group').querySelector('label').setAttribute('for', `filters_custom_parameter_value_${count}`);
        clone.querySelector(`input[name="filters_custom_parameter_value[]"`).setAttribute('id', `filters_custom_parameter_value_${count}`);
        clone.querySelector(`input[name="filters_custom_parameter_value[]"`).setAttribute('name', `filters_custom_parameter_value[${count}]`);

        document.querySelector(`#${type}`).appendChild(clone);

        remove_initiator();
        initiate_filters_listener();
    };

    document.querySelectorAll('[data-add]').forEach(element => {
        element.addEventListener('click', add);
    })

    /* remove  */
    let remove = event => {
        event.currentTarget.closest('.custom_parameter').remove();
    };

    let remove_initiator = () => {
        document.querySelectorAll('#custom_parameters [data-remove]').forEach(element => {
            element.removeEventListener('click', remove);
            element.addEventListener('click', remove)
        })
    };

    remove_initiator();

    type_handler('[name="type"]', 'data-type');
    document.querySelector('[name="type"]') && document.querySelectorAll('[name="type"]').forEach(element => element.addEventListener('change', () => { type_handler('[name="type"]', 'data-type'); }));

    document.querySelector('#website_id').addEventListener('change', async event => {
        await get_segment_count();
    });

    document.querySelector('#type').addEventListener('change', async event => {
        await get_segment_count();
    });

    let initiate_filters_listener = () => {
        document.querySelectorAll('[name^="filters_"]').forEach(element => element.removeEventListener('change', async event => await get_segment_count()));
        document.querySelectorAll('[name^="filters_"]').forEach(element => element.addEventListener('change', async event => await get_segment_count()));
    }
    initiate_filters_listener();

    let get_segment_count = async () => {
        let type = document.querySelector('#type').value;
        let website_id = document.querySelector('#website_id').value;

        if(type == 'custom') {
            document.querySelector('#segment_count').innerHTML = ``;
            return;
        }

        /* Display a loader */
        document.querySelector('#segment_count').innerHTML = `<div class="spinner-border spinner-border-sm" role="status"></div>`;

        /* Prepare query string */
        let query = new URLSearchParams();
        query.set('type', type);
        query.set('website_id', website_id);

        /* Filter preparing on query string */
        if(type == 'filter') {
            query = new URLSearchParams(new FormData(document.querySelector('#form')));
        }

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
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
