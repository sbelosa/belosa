<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('subscribers') ?>"><?= l('subscribers.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('subscribers_import.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <h1 class="h4 text-truncate"><i class="fas fa-fw fa-xs fa-user-check mr-1"></i> <?= l('subscribers_import.header') ?></h1>
    <p></p>

    <div class="card">
        <div class="card-body">
            <form id="form" action="" method="post" role="form" enctype="multipart/form-data">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                <div class="form-group">
                    <label for="website_id"><i class="fas fa-fw fa-sm fa-pager text-muted mr-1"></i> <?= l('websites.website') ?></label>
                    <select id="website_id" name="website_id" class="form-control <?= \Altum\Alerts::has_field_errors('website_id') ? 'is-invalid' : null ?>" required="required">
                        <?php foreach($data->websites as $website): ?>
                            <option value="<?= $website->website_id ?>" <?= $data->values['website_id'] == $website->website_id ? 'selected="selected"' : null ?>><?= $website->name . ' - ' . $website->host . $website->path ?></option>
                        <?php endforeach ?>
                    </select>
                    <?= \Altum\Alerts::output_field_error('website_id') ?>
                </div>

                <div class="form-group" data-file-input-wrapper-size-limit="<?= get_max_upload() ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), get_max_upload()) ?>">
                    <label for="file"><i class="fas fa-fw fa-sm fa-file-csv text-muted mr-1"></i> <?= l('global.csv_file') ?></label>
                    <?= include_view(THEME_PATH . 'views/partials/file_input.php', ['uploads_file_key' => 'websites_subscribers_csv', 'file_key' => 'file', 'already_existing_file' => null, 'is_required' => true]) ?>
                    <?= \Altum\Alerts::output_field_error('file') ?>
                    <small class="form-text text-muted"><?= sprintf(l('global.csv_file_help'), '<code>endpoint</code>, <code>p256dh</code>, <code>auth</code>', '<code>ip</code>, <code>city_name</code>, <code>country_code</code>, <code>continent_code</code>, <code>os_name</code>, <code>browser_name</code>, <code>browser_language</code>, <code>subscribed_on_url</code>, <code>datetime</code>'); ?></small>
                    <small class="form-text text-muted"><?= l('subscribers_import.file_help') ?></small>
                    <small class="form-text text-muted"><a href="<?= ASSETS_FULL_URL . 'csv/subscribers_example.csv' ?>" download="subscribers_example.csv" target="_blank"><?= l('global.csv_file_help2') ?></a></small>
                    <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('websites_subscribers_csv')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), get_max_upload()) ?></small>
                </div>

                <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('global.submit') ?></button>
            </form>
        </div>
    </div>
</div>


<?php ob_start() ?>
<script>
    'use strict';

document.querySelector('#form').addEventListener('submit', event => {
        if(document.querySelector('#form').checkValidity()) {
            pause_submit_button(event.currentTarget);
        }
    });

</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

