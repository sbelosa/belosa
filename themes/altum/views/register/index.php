<?php defined('ALTUMCODE') || die() ?>

<?= \Altum\Alerts::output_alerts() ?>

<!-- Custom code: FC-2026-02-25: registration header copy -->
<h1 class="h5 mb-3 register-hero-title">
    <?= l('register.hero_title') ?>
    <span class="register-hero-subtitle"><?= l('register.hero_subtitle') ?></span>
</h1>
<p class="register-hero-note mb-5" style="color: #e9fef9 !important;">
    <?= l('register.hero_note') ?>
</p>
<!-- /Custom code: FC-2026-02-25 -->

<form action="" method="post" class="mt-4" role="form">
    <?php if(!settings()->users->register_only_social_logins): ?>
        <div class="row register-form-grid">
            <div class="col-12 col-lg-6 register-form-col">
                <div class="form-group">
                    <label for="name"><?= l('register.full_name') ?></label>
                    <input id="name" type="text" name="name" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" value="<?= $data->values['name'] ?>" maxlength="32" required="required" autofocus="autofocus" />
                    <?= \Altum\Alerts::output_field_error('name') ?>
                </div>
            </div>

            <div class="col-12 col-lg-6 register-form-col">
                <div class="form-group">
                    <label for="email"><?= l('global.email') ?></label>
                    <input id="email" type="email" name="email" class="form-control <?= \Altum\Alerts::has_field_errors('email') ? 'is-invalid' : null ?>" value="<?= $data->values['email'] ?>" maxlength="128" required="required" />
                    <?= \Altum\Alerts::output_field_error('email') ?>
                </div>
            </div>

            <div class="col-12 col-lg-6 register-form-col">
                <div class="form-group" data-password-toggle-view data-password-toggle-view-show="<?= l('global.show') ?>" data-password-toggle-view-hide="<?= l('global.hide') ?>">
                    <label for="password"><?= l('global.password') ?></label>
                    <input id="password" type="password" name="password" class="form-control <?= \Altum\Alerts::has_field_errors('password') ? 'is-invalid' : null ?>" value="<?= $data->values['password'] ?>" required="required" />
                    <?= \Altum\Alerts::output_field_error('password') ?>
                </div>
            </div>

            <!-- Custom code -->
            <div class="col-12 col-lg-6 register-form-col">
                <div class="form-group">
                    <label for="meta_foreverId"><?= l('global.forerverId') ?></label>
                    <input id="meta_foreverId" type="text" name="meta_foreverId" class="form-control <?= \Altum\Alerts::has_field_errors('meta_foreverId') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_foreverId']) ? $data->values['meta_foreverId'] : '' ?>" maxlength="12" required="required"/>
                    <?= \Altum\Alerts::output_field_error('meta_foreverId') ?>
                </div>
            </div>

            <div class="col-12 col-lg-6 register-form-col">
                <div class="form-group">
                    <label for="meta_phone"><?= l('account.billing.phone') ?></label>
                    <input id="meta_phone" type="text" name="meta_phone" class="form-control <?= \Altum\Alerts::has_field_errors('meta_phone') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_phone']) ? $data->values['meta_phone'] : '' ?>" maxlength="64" required="required"/>
                    <?= \Altum\Alerts::output_field_error('meta_phone') ?>
                </div>
            </div>

            <div class="col-12 register-form-col">
                <div class="form-group">
                    <label for="meta_address"><?= l('account.billing.address') ?></label>
                    <input id="meta_address" type="text" name="meta_address" class="form-control <?= \Altum\Alerts::has_field_errors('meta_address') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_address']) ? $data->values['meta_address'] : '' ?>" maxlength="128" required="required"/>
                    <?= \Altum\Alerts::output_field_error('meta_address') ?>
                </div>
            </div>

            <div class="col-12 col-lg-6 register-form-col">
                <div class="form-group">
                    <label for="meta_zip"><?= l('account.billing.zip') ?></label>
                    <input id="meta_zip" type="text" name="meta_zip" class="form-control <?= \Altum\Alerts::has_field_errors('meta_zip') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_zip']) ? $data->values['meta_zip'] : '' ?>" maxlength="64" required="required"/>
                    <?= \Altum\Alerts::output_field_error('meta_zip') ?>
                </div>
            </div>

            <div class="col-12 col-lg-6 register-form-col">
                <div class="form-group">
                    <label for="meta_city"><?= l('global.city') ?></label>
                    <input id="meta_city" type="text" name="meta_city" class="form-control <?= \Altum\Alerts::has_field_errors('meta_city') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_city']) ? $data->values['meta_city'] : '' ?>" maxlength="64" required="required"/>
                    <?= \Altum\Alerts::output_field_error('meta_city') ?>
                </div>
            </div>

            <div class="col-12 col-lg-6 register-form-col">
                <div class="form-group">
                    <label for="meta_country"><?= l('global.country') ?></label>
                    <input id="meta_country" type="text" name="meta_country" class="form-control <?= \Altum\Alerts::has_field_errors('meta_country') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_country']) && !empty($data->values['meta_country']) ? $data->values['meta_country'] : 'Hrvatska' ?>" maxlength="64" required="required"/>
                    <?= \Altum\Alerts::output_field_error('meta_country') ?>
                </div>
            </div>
            <!-- /Custom code -->
        </div>

        <?php if(settings()->captcha->register_is_enabled): ?>
            <div class="form-group">
                <?php $data->captcha->display() ?>
            </div>
        <?php endif ?>

        <div class="custom-control custom-checkbox">
            <input type="checkbox" name="accept" class="custom-control-input" id="accept" required="required">
            <label class="custom-control-label" for="accept">
                <small class="text-muted">
                    <?= sprintf(
                        l('register.accept'),
                        '<a href="' . settings()->main->terms_and_conditions_url . '" target="_blank">' . l('global.terms_and_conditions') . '</a>',
                        '<a href="' . settings()->main->privacy_policy_url . '" target="_blank">' . l('global.privacy_policy') . '</a>'
                    ) ?>
                </small>
            </label>
        </div>

        <?php if(settings()->users->register_display_newsletter_checkbox): ?>
            <div class="mt-3 custom-control custom-checkbox">
                <input type="checkbox" name="is_newsletter_subscribed" class="custom-control-input" id="is_newsletter_subscribed">
                <label class="custom-control-label" for="is_newsletter_subscribed">
                    <small class="text-muted">
                        <?= l('register.is_newsletter_subscribed') ?>
                    </small>
                </label>
            </div>
        <?php endif ?>

        <div class="form-group mt-4">
            <button type="submit" name="submit" class="btn btn-primary btn-block" <?= isset($_COOKIE['register_lockout']) ? 'disabled="disabled"' : null ?>><?= l('register.register') ?></button>
        </div>
    <?php endif ?>

    <?php if(settings()->facebook->is_enabled || settings()->google->is_enabled || settings()->twitter->is_enabled || settings()->discord->is_enabled || settings()->linkedin->is_enabled || settings()->microsoft->is_enabled): ?>
        <hr class="border-gray-100 my-3" />

        <div>
            <?php if(settings()->facebook->is_enabled): ?>
                <div class="mt-2">
                    <a href="<?= url('login/facebook-initiate') ?>" class="btn btn-light btn-block">
                        <img src="<?= ASSETS_FULL_URL . 'images/facebook.svg' ?>" class="mr-1" />
                        <?= l('login.facebook') ?>
                    </a>
                </div>
            <?php endif ?>
            <?php if(settings()->google->is_enabled): ?>
                <div class="mt-2">
                    <a href="<?= url('login/google-initiate') ?>" class="btn btn-light btn-block">
                        <img src="<?= ASSETS_FULL_URL . 'images/google.svg' ?>" class="mr-1" />
                        <?= l('login.google') ?>
                    </a>
                </div>
            <?php endif ?>
            <?php if(settings()->twitter->is_enabled): ?>
                <div class="mt-2">
                    <a href="<?= url('login/twitter-initiate') ?>" class="btn btn-light btn-block">
                        <img src="<?= ASSETS_FULL_URL . 'images/x.svg' ?>" class="mr-1" />
                        <?= l('login.twitter') ?>
                    </a>
                </div>
            <?php endif ?>
            <?php if(settings()->discord->is_enabled): ?>
                <div class="mt-2">
                    <a href="<?= url('login/discord-initiate') ?>" class="btn btn-light btn-block">
                        <img src="<?= ASSETS_FULL_URL . 'images/discord.svg' ?>" class="mr-1" />
                        <?= l('login.discord') ?>
                    </a>
                </div>
            <?php endif ?>
            <?php if(settings()->linkedin->is_enabled): ?>
                <div class="mt-2">
                    <a href="<?= url('login/linkedin-initiate') ?>" class="btn btn-light btn-block">
                        <img src="<?= ASSETS_FULL_URL . 'images/linkedin.svg' ?>" class="mr-1" />
                        <?= l('login.linkedin') ?>
                    </a>
                </div>
            <?php endif ?>
            <?php if(settings()->microsoft->is_enabled): ?>
                <div class="mt-2">
                    <a href="<?= url('login/microsoft-initiate') ?>" class="btn btn-light btn-block">
                        <img src="<?= ASSETS_FULL_URL . 'images/microsoft.svg' ?>" class="mr-1" />
                        <?= l('login.microsoft') ?>
                    </a>
                </div>
            <?php endif ?>
        </div>
    <?php endif ?>
</form>


<div class="mt-5 text-center text-muted">
    <?= sprintf(l('register.login'), '<a href="' . url('login' . $data->redirect_append) . '" class="font-weight-bold">' . l('register.login_help') . '</a>') ?></a>
</div>

<?php ob_start() ?>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "<?= l('index.title') ?>",
                    "item": "<?= url() ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "<?= l('register.title') ?>",
                    "item": "<?= url('register') ?>"
                }
            ]
        }
    </script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
