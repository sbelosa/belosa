<?php defined('ALTUMCODE') || die() ?>

<div class="mb-4">
    <h2 class="h5 mb-1"><?= l('admin_settings.forever.header') ?></h2>
    <p class="text-muted mb-0"><?= l('admin_settings.forever.subheader') ?></p>
</div>

<div class="alert alert-info">
    <div><strong><?= l('admin_settings.forever.special_countries_title') ?></strong></div>
    <div><?= l('admin_settings.forever.special_countries_help') ?></div>
</div>

<div class="mb-4">
    <h3 class="h6 mb-1"><?= l('admin_settings.forever.webshop_header') ?></h3>
    <p class="text-muted mb-0"><?= l('admin_settings.forever.webshop_subheader') ?></p>
</div>

<?php foreach($data->country_definitions as $country_code => $country): ?>
    <?php $value = $data->forever_webshop_links->{$country_code} ?? ''; ?>

    <div class="form-group">
        <label for="<?= 'forever_webshop_links_' . $country_code ?>">
            <i class="fas fa-fw fa-sm fa-shopping-cart text-muted mr-1"></i>
            <?= $country['label'] ?>
        </label>

        <div class="input-group">
            <input
                id="<?= 'forever_webshop_links_' . $country_code ?>"
                type="url"
                name="<?= 'forever_webshop_links_' . $country_code ?>"
                class="form-control"
                placeholder="https://"
                value="<?= $value ?>"
            />

            <?php if(!empty($value)): ?>
                <div class="input-group-append">
                    <a class="btn btn-outline-secondary" href="<?= $value ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-fw fa-external-link-alt"></i>
                    </a>
                </div>
            <?php endif ?>
        </div>

        <small class="form-text text-muted">
            <?= sprintf(l('admin_settings.forever.referral_parameter_help'), strtoupper($country['referral_parameter'])) ?>
        </small>
    </div>
<?php endforeach ?>

<hr class="my-5" />

<div class="mb-4">
    <h3 class="h6 mb-1"><?= l('admin_settings.forever.business_header') ?></h3>
    <p class="text-muted mb-0"><?= l('admin_settings.forever.business_subheader') ?></p>
</div>

<?php foreach($data->country_definitions as $country_code => $country): ?>
    <?php $value = $data->forever_business_links->{$country_code} ?? ''; ?>

    <div class="form-group">
        <label for="<?= 'forever_business_links_' . $country_code ?>">
            <i class="fas fa-fw fa-sm fa-briefcase text-muted mr-1"></i>
            <?= $country['label'] ?>
        </label>

        <div class="input-group">
            <input
                id="<?= 'forever_business_links_' . $country_code ?>"
                type="url"
                name="<?= 'forever_business_links_' . $country_code ?>"
                class="form-control"
                placeholder="https://"
                value="<?= $value ?>"
            />

            <?php if(!empty($value)): ?>
                <div class="input-group-append">
                    <a class="btn btn-outline-secondary" href="<?= $value ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-fw fa-external-link-alt"></i>
                    </a>
                </div>
            <?php endif ?>
        </div>

        <small class="form-text text-muted">
            <?= sprintf(l('admin_settings.forever.referral_parameter_help'), strtoupper($country['referral_parameter'])) ?>
        </small>
    </div>
<?php endforeach ?>

<button type="submit" name="submit" class="btn btn-lg btn-block btn-primary mt-4"><?= l('global.update') ?></button>
