<?php defined('ALTUMCODE') || die() ?>

<?= \Altum\Alerts::output_alerts() ?>

<?php $contact_country_options = get_contact_phone_country_options_array(); ?>

<?php ob_start() ?>
<!-- Custom code: FC-2026-09-14: Give the registration form balanced spacing and a readable responsive layout -->
<style>
    .register-background {
        min-height: 100vh;
        background: radial-gradient(ellipse at 50% 0, #18312f 0, transparent 48rem), #0b1015;
        background-attachment: fixed;
    }
    .register-background main { padding: 2.5rem 0 !important; }
    .register-background .fcc-registration-layout { flex: 0 0 auto; width: 100%; max-width: 760px; }
    .register-logo-wrap { margin-bottom: 1.5rem !important; }
    .register-card {
        border: 1px solid #26363b;
        border-radius: 1.5rem !important;
        background: #10171e;
        box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, .22);
        overflow: hidden;
    }
    .register-card .card-body { padding: 2.25rem !important; }
    .register-shell { color: #e8f0ef; }
    .register-eyebrow {
        margin-bottom: .75rem;
        color: #83dcca;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .16em;
        text-transform: uppercase;
    }
    .register-title { margin: 0; color: #f6faf9; font-size: 2rem; font-weight: 750; line-height: 1.2; letter-spacing: -.035em; }
    .register-subtitle { display: block; margin-top: .45rem; color: #aabfbe; font-size: 1rem; font-weight: 400; line-height: 1.5; letter-spacing: 0; }
    .register-note-card {
        margin: 1.25rem 0 1.75rem;
        padding: .2rem 0 .2rem 1rem;
        border-left: 2px solid #3c8b7f;
        color: #acbfbd;
        font-size: .85rem;
        line-height: 1.65;
    }
    .register-note-card strong { color: #dbece8; }
    .register-form { margin-top: 0; }
    .register-grid { display: grid; gap: 1.75rem; }
    .register-section + .register-section { padding-top: 1.75rem; border-top: 1px solid #29363d; }
    .register-section-title { display: flex; align-items: center; gap: .65rem; margin: 0 0 1.2rem; color: #edf5f2; font-size: .95rem; font-weight: 650; line-height: 1.4; }
    .register-section-number { display: inline-flex; align-items: center; justify-content: center; width: 1.65rem; height: 1.65rem; border: 1px solid #36504e; border-radius: .5rem; color: #89daca; font-size: .7rem; font-weight: 600; }
    .register-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.2rem 1.25rem; }
    .register-form-col { min-width: 0; }
    .register-form-col-full { grid-column: 1 / -1; }
    .register-shell .form-group { margin-bottom: 0; }
    .register-shell label { margin-bottom: .5rem; color: #c9d7d6; font-size: .84rem; font-weight: 500; }
    .register-shell .form-control,
    .register-shell .custom-select {
        height: 3rem;
        min-height: 3rem;
        border: 1px solid #35434c;
        border-radius: .65rem;
        background-color: #172129;
        color: #f1f6f5;
        font-size: .95rem;
        box-shadow: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .register-shell .custom-select option { color: #eef5f3; background: #172129; }
    .register-shell .form-control::placeholder { color: #8c9ea5; opacity: 1; }
    .register-shell .form-control:focus,
    .register-shell .custom-select:focus { border-color: #70d6c2; box-shadow: 0 0 0 3px rgba(112, 214, 194, .12); }
    .register-shell .form-control.is-invalid { border-color: #ed939b; }
    .register-shell .invalid-feedback { color: #f1a7ae; font-size: .8rem; line-height: 1.5; }
    .register-shell .text-muted,
    .register-shell .form-text { color: #9fafb5 !important; font-size: .78rem; line-height: 1.55; }
    .register-shell .form-text { margin-top: .45rem; }
    .register-shell .fcc-contact-capture { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 3fr); gap: .75rem; }
    .register-shell .fcc-contact-capture-row { min-width: 0; }
    .register-phone-field { position: relative; }
    .register-phone-field-icon { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: #88b9ad; pointer-events: none; }
    .register-shell .register-phone-field .form-control { padding-left: 2.4rem; }
    .register-captcha {
        display: grid;
        grid-template-columns: 176px minmax(0, 1fr);
        gap: .75rem 1rem;
        align-items: center;
        margin-bottom: 1.25rem !important;
        padding: 1rem;
        border: 1px solid #2d4145;
        border-radius: .8rem;
        background: #141f25;
    }
    .register-captcha > img { display: block; max-width: 100%; height: auto; margin: 0 !important; border-radius: .45rem !important; }
    .register-captcha > :not(img):not(input) { grid-column: 1 / -1; }
    .register-captcha > input:not([type="hidden"]) { min-width: 0; }
    .register-shell .custom-control-label { margin-bottom: 0; padding-top: .1rem; font-weight: 400; line-height: 1.6; }
    .register-shell .custom-control-label a { color: #8adcca; font-weight: 500; }
    .register-shell .custom-control-label::before { background-color: #18272d; border: 1px solid #72888b; border-radius: .25rem; }
    .register-shell .custom-control-input:checked ~ .custom-control-label::before { background-color: #3b9c87; border-color: #70d6c2; }
    .register-submit-wrap { margin-top: 1.4rem; }
    .register-submit-btn { min-height: 3.15rem; border: 1px solid transparent; border-radius: .7rem; color: #092c25; background: #7ae0c8; font-size: .95rem; font-weight: 700; box-shadow: none; }
    .register-submit-btn:hover { color: #092c25; background: #96ead7; }
    .register-submit-btn:focus-visible { outline: 2px solid #c9fff0; outline-offset: 3px; }
    .register-auth-divider { display: flex; align-items: center; gap: 1rem; margin: 1.5rem 0 1rem; color: #9fafb5; font-size: .8rem; }
    .register-auth-divider::before, .register-auth-divider::after { content: ''; flex: 1; border-top: 1px solid #29363d; }
    .register-social-btn { padding: .8rem; border: 1px solid #35434c; border-radius: .65rem; background: #172129; color: #edf5f2; }
    .register-social-btn:hover, .register-social-btn:focus { background: #233039; color: #fff; }
    .register-footer-link { margin-top: 1.5rem; text-align: center; color: #a7b8bd; font-size: .85rem; }
    .register-footer-link a { margin-left: .2rem; color: #8adcca; }
    @media (max-width: 575.98px) {
        .register-background main { padding: 1.5rem 0 !important; }
        .register-background .fcc-registration-layout { padding: 0; }
        .register-logo-wrap { margin-bottom: 1rem !important; }
        .register-card { border-radius: 1rem !important; }
        .register-card .card-body { padding: 1.35rem !important; }
        .register-title { font-size: 1.8rem; }
        .register-note-card { margin-bottom: 1.5rem; }
        .register-form-grid { grid-template-columns: minmax(0, 1fr); gap: 1rem; }
        .register-shell .form-control, .register-shell .custom-select { font-size: 1rem; }
        .register-captcha { grid-template-columns: minmax(0, 1fr); padding: .85rem; }
        .register-captcha > img { justify-self: center; }
    }
    @media (max-width: 399.98px) {
        .register-shell .fcc-contact-capture { grid-template-columns: minmax(0, 1fr); }
    }
    @media (prefers-reduced-motion: reduce) {
        .register-shell .form-control, .register-shell .custom-select { transition: none; }
    }
</style>
<!-- /Custom code: FC-2026-09-14 -->
<?php \Altum\Event::add_content(ob_get_clean(), 'head', 'fcc_register_premium_styles') ?>

<!-- Custom code: FC-2026-09-14: Consistent form grid and section headings -->
<div class="register-shell">
    <div class="register-eyebrow">Forever Card Club</div>

    <h1 class="register-title">
        <?= l('register.hero_title') ?>
        <span class="register-subtitle"><?= l('register.hero_subtitle') ?></span>
    </h1>

    <div class="register-note-card">
        <?= l('register.hero_note') ?>
    </div>

    <form action="" method="post" class="register-form" role="form">
        <?php if(!settings()->users->register_only_social_logins): ?>
            <!-- Custom code: FC-2026-09-14: Session token and an off-screen bot trap -->
            <input type="hidden" name="registration_token" value="<?= \Altum\Csrf::get('registration_token') ?>" />
            <div aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden;">
                <label for="registration_website">Website</label>
                <input id="registration_website" type="text" name="registration_website" value="" tabindex="-1" autocomplete="off" />
            </div>
            <!-- /Custom code: FC-2026-09-14 -->
            <div class="register-grid">
                <section class="register-section">
                    <h2 class="register-section-title"><span class="register-section-number" aria-hidden="true">01</span><?= l('register.section.account_details') ?></h2>

                    <div class="register-form-grid">
                        <div class="register-form-col">
                            <div class="form-group">
                                <label for="name"><?= l('register.full_name') ?></label>
                                <input id="name" type="text" name="name" autocomplete="name" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" value="<?= $data->values['name'] ?>" maxlength="32" required="required" autofocus="autofocus" />
                                <?= \Altum\Alerts::output_field_error('name') ?>
                            </div>
                        </div>

                        <div class="register-form-col">
                            <div class="form-group">
                                <label for="email"><?= l('global.email') ?></label>
                                <input id="email" type="email" name="email" autocomplete="email" class="form-control <?= \Altum\Alerts::has_field_errors('email') ? 'is-invalid' : null ?>" value="<?= $data->values['email'] ?>" maxlength="128" required="required" />
                                <?= \Altum\Alerts::output_field_error('email') ?>
                            </div>
                        </div>

                        <div class="register-form-col">
                            <div class="form-group" data-password-toggle-view data-password-toggle-view-show="<?= l('global.show') ?>" data-password-toggle-view-hide="<?= l('global.hide') ?>">
                                <label for="password"><?= l('global.password') ?></label>
                                <input id="password" type="password" name="password" autocomplete="new-password" class="form-control <?= \Altum\Alerts::has_field_errors('password') ? 'is-invalid' : null ?>" value="<?= $data->values['password'] ?>" required="required" />
                                <?= \Altum\Alerts::output_field_error('password') ?>
                            </div>
                        </div>

                        <div class="register-form-col">
                            <div class="form-group">
                                <label for="meta_foreverId"><?= l('global.forerverId') ?></label>
                                <!-- Custom code: FC-2026-09-14: Match the strict server ID validation and offer a mobile numeric keyboard -->
                                <input id="meta_foreverId" type="text" name="meta_foreverId" class="form-control <?= \Altum\Alerts::has_field_errors('meta_foreverId') ? 'is-invalid' : null ?>" value="<?= $data->values['meta_foreverId'] ?? '' ?>" inputmode="numeric" pattern="[0-9]{12}" minlength="12" maxlength="12" required="required" aria-describedby="meta_foreverId_help" title="<?= l('register.forever_id_help') ?>" />
                                <small id="meta_foreverId_help" class="form-text text-muted"><?= l('register.forever_id_help') ?></small>
                                <!-- /Custom code: FC-2026-09-14 -->
                                <?= \Altum\Alerts::output_field_error('meta_foreverId') ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="register-section">
                    <h2 class="register-section-title"><span class="register-section-number" aria-hidden="true">02</span><?= l('register.section.contact_details') ?></h2>

                    <div class="register-form-grid">
                        <div class="register-form-col register-form-col-full">
                            <div class="form-group">
                                <label for="meta_phone"><?= l('account.billing.phone') ?></label>
                                <div class="fcc-contact-capture">
                                    <div class="fcc-contact-capture-row">
                                        <select id="meta_phone_country_code" class="custom-select" name="meta_phone_country_code" required="required" aria-label="<?= l('register.phone_country_code') ?>">
                                            <?php foreach($contact_country_options as $country_code => $country_label): ?>
                                                <option value="<?= $country_code ?>" <?= (($data->values['meta_phone_country_code'] ?? 'HR') == $country_code) ? 'selected="selected"' : null ?>><?= $country_label ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                    <div class="fcc-contact-capture-row">
                                        <div class="register-phone-field">
                                            <span class="register-phone-field-icon"><i class="fas fa-fw fa-phone-square-alt"></i></span>
                                            <input id="meta_phone" type="tel" inputmode="tel" name="meta_phone" class="form-control <?= \Altum\Alerts::has_field_errors('meta_phone') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_phone']) ? $data->values['meta_phone'] : '' ?>" maxlength="64" placeholder="0911234567" required="required"/>
                                        </div>
                                    </div>
                                </div>
                                <?= \Altum\Alerts::output_field_error('meta_phone') ?>
                            </div>
                        </div>

                        <div class="register-form-col">
                            <div class="form-group">
                                <label for="meta_country"><?= l('global.country') ?></label>
                                <input id="meta_country" type="text" name="meta_country" class="form-control <?= \Altum\Alerts::has_field_errors('meta_country') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_country']) && !empty($data->values['meta_country']) ? $data->values['meta_country'] : 'Hrvatska' ?>" maxlength="64" required="required"/>
                                <?= \Altum\Alerts::output_field_error('meta_country') ?>
                            </div>
                        </div>

                        <div class="register-form-col">
                            <div class="form-group">
                                <label for="meta_address"><?= l('account.billing.address') ?></label>
                                <input id="meta_address" type="text" name="meta_address" class="form-control <?= \Altum\Alerts::has_field_errors('meta_address') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_address']) ? $data->values['meta_address'] : '' ?>" maxlength="128" required="required"/>
                                <?= \Altum\Alerts::output_field_error('meta_address') ?>
                            </div>
                        </div>

                        <div class="register-form-col">
                            <div class="form-group">
                                <label for="meta_zip"><?= l('account.billing.zip') ?></label>
                                <input id="meta_zip" type="text" name="meta_zip" class="form-control <?= \Altum\Alerts::has_field_errors('meta_zip') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_zip']) ? $data->values['meta_zip'] : '' ?>" maxlength="64" required="required"/>
                                <?= \Altum\Alerts::output_field_error('meta_zip') ?>
                            </div>
                        </div>

                        <div class="register-form-col">
                            <div class="form-group">
                                <label for="meta_city"><?= l('global.city') ?></label>
                                <input id="meta_city" type="text" name="meta_city" class="form-control <?= \Altum\Alerts::has_field_errors('meta_city') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_city']) ? $data->values['meta_city'] : '' ?>" maxlength="64" required="required"/>
                                <?= \Altum\Alerts::output_field_error('meta_city') ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="register-section">
                    <h2 class="register-section-title"><span class="register-section-number" aria-hidden="true">03</span><?= l('register.section.confirmation') ?></h2>

                    <!-- Custom code: FC-2026-09-14: Always display the CAPTCHA required by the registration controller -->
                        <div class="form-group register-captcha">
                            <?php $data->captcha->display() ?>
                        </div>
                    <!-- /Custom code: FC-2026-09-14 -->

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

                    <div class="register-submit-wrap">
                        <button type="submit" name="submit" class="btn btn-block register-submit-btn" <?= isset($_COOKIE['register_lockout']) ? 'disabled="disabled"' : null ?>><?= l('register.register') ?></button>
                    </div>
                </section>
            </div>
        <?php endif ?>

        <?php if(settings()->facebook->is_enabled || settings()->google->is_enabled || settings()->twitter->is_enabled || settings()->discord->is_enabled || settings()->linkedin->is_enabled || settings()->microsoft->is_enabled): ?>
            <div class="register-auth-divider"><span>ili nastavi s</span></div>

            <div>
                <?php if(settings()->facebook->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/facebook-initiate') ?>" class="btn btn-block register-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/facebook.svg' ?>" class="mr-1" />
                            <?= l('login.facebook') ?>
                        </a>
                    </div>
                <?php endif ?>
                <?php if(settings()->google->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/google-initiate') ?>" class="btn btn-block register-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/google.svg' ?>" class="mr-1" />
                            <?= l('login.google') ?>
                        </a>
                    </div>
                <?php endif ?>
                <?php if(settings()->twitter->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/twitter-initiate') ?>" class="btn btn-block register-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/x.svg' ?>" class="mr-1" />
                            <?= l('login.twitter') ?>
                        </a>
                    </div>
                <?php endif ?>
                <?php if(settings()->discord->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/discord-initiate') ?>" class="btn btn-block register-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/discord.svg' ?>" class="mr-1" />
                            <?= l('login.discord') ?>
                        </a>
                    </div>
                <?php endif ?>
                <?php if(settings()->linkedin->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/linkedin-initiate') ?>" class="btn btn-block register-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/linkedin.svg' ?>" class="mr-1" />
                            <?= l('login.linkedin') ?>
                        </a>
                    </div>
                <?php endif ?>
                <?php if(settings()->microsoft->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/microsoft-initiate') ?>" class="btn btn-block register-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/microsoft.svg' ?>" class="mr-1" />
                            <?= l('login.microsoft') ?>
                        </a>
                    </div>
                <?php endif ?>
            </div>
        <?php endif ?>
    </form>

    <div class="register-footer-link">
        <?= sprintf(l('register.login'), '<a href="' . url('login' . $data->redirect_append) . '" class="font-weight-bold">' . l('register.login_help') . '</a>') ?>
    </div>
</div>

<!-- /Custom code: FC-2026-09-14 -->
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
