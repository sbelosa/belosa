<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('subscribers') ?>"><?= l('subscribers.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li>
                    <a href="<?= url('subscriber/' . $data->subscriber->subscriber_id) ?>"><?= l('subscriber.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('subscriber_update.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="d-flex justify-content-between mb-4">
        <h1 class="h4 text-truncate mb-0"><i class="fas fa-fw fa-xs fa-user-check mr-1"></i> <?= l('subscriber_update.header') ?></h1>

        <?= include_view(THEME_PATH . 'views/subscribers/subscriber_dropdown_button.php', ['id' => $data->subscriber->subscriber_id, 'resource_name' => $data->subscriber->ip, 'website_id' => $data->subscriber->website_id]) ?>
    </div>

    <div class="card">
        <div class="card-body">

            <form id="form" action="" method="post" role="form" enctype="multipart/form-data">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                <div class="form-group">
                    <label for="ip"><i class="fas fa-fw fa-ethernet fa-sm text-muted mr-1"></i> <?= l('global.ip') ?></label>
                    <input type="text" id="ip" name="ip" class="form-control" value="<?= $data->subscriber->ip ?>" required="required" disabled="disabled" />
                </div>

                <div class="form-group">
                    <label for="website_id"><i class="fas fa-fw fa-sm fa-pager text-muted mr-1"></i> <?= l('websites.website') ?></label>
                    <select id="website_id" name="website_id" class="form-control <?= \Altum\Alerts::has_field_errors('website_id') ? 'is-invalid' : null ?>" required="required" disabled="disabled">
                        <?php foreach($data->websites as $website): ?>
                            <option value="<?= $website->website_id ?>" <?= $data->subscriber->website_id == $website->website_id ? 'selected="selected"' : null ?>><?= $website->name . ' - ' . $website->host . $website->path ?></option>
                        <?php endforeach ?>
                    </select>
                    <?= \Altum\Alerts::output_field_error('website_id') ?>
                </div>

                <div class="form-group">
                    <label for="custom_parameters"><i class="fas fa-fw fa-sm fa-fingerprint text-muted mr-1"></i> <?= l('subscriber.custom_parameters') ?></label>

                    <div id="custom_parameters">
                        <?php foreach($data->subscriber->custom_parameters as $key => $value): ?>
                            <div class="form-row">
                                <div class="form-group col-lg-5">
                                    <input type="text" name="custom_parameter_key[<?= $key ?>]" class="form-control" value="<?= $key ?>" maxlength="64" placeholder="<?= l('subscriber.custom_parameter_key') ?>" />
                                </div>

                                <div class="form-group col-lg-5">
                                    <input type="text" name="custom_parameter_value[<?= $key ?>]" class="form-control" value="<?= $value ?>" maxlength="512" placeholder="<?= l('subscriber.custom_parameter_value') ?>" />
                                </div>

                                <div class="form-group col-lg-2 text-center">
                                    <button type="button" data-remove class="btn btn-block btn-outline-danger" title="<?= l('global.delete') ?>"><i class="fas fa-fw fa-times"></i></button>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <div class="mb-3">
                        <button data-add type="button" class="btn btn-sm btn-outline-success"><i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('subscriber.custom_parameter_add') ?></button>
                    </div>
                    <?= \Altum\Alerts::output_field_error('custom_parameters') ?>
                </div>

                <button type="submit" name="submit" class="btn btn-block btn-primary"><?= l('global.update') ?></button>
            </form>

        </div>
    </div>
</div>

<template id="template_custom_parameter">
    <div class="form-row">
        <div class="form-group col-lg-5">
            <input type="text" name="custom_parameter_key[]" class="form-control" value="" max="64" placeholder="<?= l('subscriber.custom_parameter_key') ?>" />
        </div>

        <div class="form-group col-lg-5">
            <input type="text" name="custom_parameter_value[]" class="form-control" value="" max="512" placeholder="<?= l('subscriber.custom_parameter_value') ?>" />
        </div>

        <div class="form-group col-lg-2 text-center">
            <button type="button" data-remove class="btn btn-block btn-outline-danger" title="<?= l('global.delete') ?>"><i class="fas fa-fw fa-times"></i></button>
        </div>
    </div>
</template>

<?php ob_start() ?>
<script>
    'use strict';
    
/* Add new custom parameter */
    let custom_parameter_add = event => {
        let clone = document.querySelector(`#template_custom_parameter`).content.cloneNode(true);

        let custom_parameters_count = document.querySelectorAll(`#custom_parameters .form-row`).length;

        if(custom_parameters_count > 20) {
            return;
        }

        clone.querySelector(`input[name="custom_parameter_key[]"`).setAttribute('name', `custom_parameter_key[${custom_parameters_count}]`);
        clone.querySelector(`input[name="custom_parameter_value[]"`).setAttribute('name', `custom_parameter_value[${custom_parameters_count}]`);

        document.querySelector(`#custom_parameters`).appendChild(clone);

        custom_parameter_remove_initiator();
    };

    document.querySelectorAll('[data-add]').forEach(element => {
        element.addEventListener('click', custom_parameter_add);
    })

    /* remove custom parameter */
    let custom_parameter_remove = event => {
        event.currentTarget.closest('.form-row').remove();
    };

    let custom_parameter_remove_initiator = () => {
        document.querySelectorAll('#custom_parameters [data-remove]').forEach(element => {
            element.removeEventListener('click', custom_parameter_remove);
            element.addEventListener('click', custom_parameter_remove)
        })
    };

    custom_parameter_remove_initiator();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
