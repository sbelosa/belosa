<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('websites') ?>"><?= l('websites.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('website_create.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <h1 class="h4 text-truncate"><i class="fas fa-fw fa-xs fa-pager mr-1"></i> <?= l('website_create.header') ?></h1>
    <p></p>

    <div class="card">
        <div class="card-body">

            <form action="" method="post" role="form">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                <div class="form-group">
                    <label for="name"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('global.name') ?></label>
                    <input type="text" id="name" name="name" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" value="<?= $data->values['name'] ?>" required="required" />
                    <?= \Altum\Alerts::output_field_error('name') ?>
                </div>

                <div class="form-group">
                    <label for="host"><i class="fas fa-fw fa-sm fa-pager text-muted mr-1"></i> <?= l('global.host') ?></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <select name="scheme" class="appearance-none custom-select custom-select-lg form-control input-group-text">
                                <option value="https://" <?= $data->values['scheme'] == 'https://' ? 'selected="selected"' : null ?>>https://</option>
                                <option value="http://" <?= $data->values['scheme'] == 'http://' ? 'selected="selected"' : null ?>>http://</option>
                            </select>
                        </div>

                        <input id="host" type="text" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" name="host" value="<?= $data->values['host'] ?>" placeholder="<?= l('global.host_placeholder') ?>" required="required" />
                    </div>
                    <small class="form-text text-muted"><?= l('websites.input.host_help') ?></small>
                    <?= \Altum\Alerts::output_field_error('host') ?>
                </div>

                <p><small class="form-text text-muted"><i class="fas fa-fw fa-sm fa-info-circle"></i> <?= l('website_create.info') ?></small></p>

                <button type="submit" name="submit" class="btn btn-block btn-primary mt-4"><?= l('global.create') ?></button>
            </form>

        </div>
    </div>
</div>
