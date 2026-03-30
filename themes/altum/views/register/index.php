<?php defined('ALTUMCODE') || die() ?>

<?= \Altum\Alerts::output_alerts() ?>

<?php $contact_country_options = get_contact_phone_country_options_array(); ?>

<?php ob_start() ?>
<style>
    .register-background {
        background:
            radial-gradient(circle at top center, rgba(54, 222, 201, .12), transparent 34rem),
            radial-gradient(circle at bottom left, rgba(17, 91, 255, .08), transparent 26rem),
            #0b0f14;
    }

    .register-logo-wrap {
        margin-bottom: 1rem !important;
    }

    .register-card {
        border: 1px solid rgba(69, 213, 194, .14);
        background:
            linear-gradient(180deg, rgba(12, 18, 27, .96) 0%, rgba(10, 14, 22, .98) 100%);
        box-shadow:
            0 1.5rem 4rem rgba(0, 0, 0, .34),
            inset 0 1px 0 rgba(255,255,255,.03);
        overflow: hidden;
    }

    .register-shell {
        color: #e6f3f1;
    }

    .register-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .35rem .7rem;
        border-radius: 999px;
        background: rgba(46, 211, 198, .12);
        border: 1px solid rgba(46, 211, 198, .16);
        color: #85efe4;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        margin-bottom: .7rem;
    }

    .register-title {
        margin: 0;
        font-size: clamp(1.75rem, 3.4vw, 2.35rem);
        line-height: 1.02;
        letter-spacing: -.03em;
        color: #f7fbfb;
        font-weight: 800;
    }

    .register-subtitle {
        display: inline-block;
        margin-left: .45rem;
        color: #72e5d9;
        font-size: .82rem;
        font-weight: 700;
        vertical-align: middle;
    }

    .register-note-card {
        margin-top: .9rem;
        margin-bottom: 1.15rem;
        padding: .8rem .9rem;
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(19, 41, 43, .92), rgba(17, 34, 38, .92));
        border: 1px solid rgba(84, 224, 208, .16);
        color: #ddfbf7;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
        font-size: .88rem;
        line-height: 1.5;
    }

    .register-note-card strong {
        color: #8cf4ea;
    }

    .register-grid {
        display: grid;
        gap: .75rem;
    }

    .register-section {
        padding: .9rem;
        border-radius: 1rem;
        background: rgba(255,255,255,.02);
        border: 1px solid rgba(255,255,255,.06);
    }

    .register-section-title {
        margin-bottom: .75rem;
        color: #f5fbfa;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .register-form-grid {
        row-gap: .2rem;
    }

    .register-form-col {
        margin-bottom: .45rem;
    }

    .register-shell label,
    .register-shell .custom-control-label {
        color: #c6d8d7;
        font-weight: 700;
        font-size: .82rem;
        margin-bottom: .45rem;
    }

    .register-shell .form-control,
    .register-shell .custom-select,
    .register-shell .input-group-text {
        min-height: 3rem;
        border-radius: .9rem;
        border: 1px solid rgba(255,255,255,.08);
    }

    .register-shell .form-control,
    .register-shell .custom-select {
        background: rgba(255,255,255,.03);
        color: #f7fbfb;
        box-shadow: none;
    }

    .register-shell .form-control::placeholder {
        color: rgba(207, 226, 224, .44);
    }

    .register-shell .form-control:focus,
    .register-shell .custom-select:focus {
        border-color: rgba(81, 229, 212, .5);
        box-shadow: 0 0 0 .2rem rgba(81, 229, 212, .12);
        background: rgba(255,255,255,.05);
    }

    .register-shell .input-group-text {
        background: rgba(255,255,255,.05);
        color: #76e8dd;
    }

    .register-shell .fcc-contact-capture {
        padding: .7rem;
        border-radius: .95rem;
        background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.015));
        border: 1px solid rgba(255,255,255,.06);
    }

    .register-shell .fcc-contact-capture .custom-select,
    .register-shell .fcc-contact-capture .form-control,
    .register-shell .fcc-contact-capture .input-group-text {
        background: rgba(11, 16, 24, .82);
        border-color: rgba(255,255,255,.06);
    }

    .register-shell .fcc-contact-capture .custom-select {
        font-weight: 700;
    }

    .register-shell .fcc-contact-capture-row + .fcc-contact-capture-row {
        margin-top: .55rem;
    }

    .register-shell .register-phone-field {
        position: relative;
    }

    .register-shell .register-phone-field-icon {
        position: absolute;
        left: .85rem;
        top: 50%;
        transform: translateY(-50%);
        color: #76e8dd;
        pointer-events: none;
        z-index: 2;
    }

    .register-shell .register-phone-field .form-control {
        padding-left: 2.35rem;
    }

    .register-shell .text-muted,
    .register-shell .form-text {
        color: rgba(198, 216, 215, .72) !important;
        font-size: .76rem;
        line-height: 1.45;
    }

    .register-shell .custom-control-label a {
        color: #84eee3;
        font-weight: 700;
    }

    .register-submit-wrap {
        margin-top: .95rem;
    }

    .register-submit-btn {
        min-height: 3.15rem;
        border: 0;
        border-radius: .95rem;
        font-size: .95rem;
        font-weight: 800;
        letter-spacing: .01em;
        color: #062624;
        background: linear-gradient(135deg, #6fffd2 0%, #42d4bf 52%, #2bb6dd 100%);
        box-shadow: 0 1rem 2rem rgba(52, 213, 198, .18);
    }

    .register-submit-btn:hover,
    .register-submit-btn:focus {
        color: #041d1b;
        transform: translateY(-1px);
        box-shadow: 0 1.2rem 2.2rem rgba(52, 213, 198, .22);
    }

    .register-auth-divider {
        position: relative;
        margin: 1.15rem 0 .85rem;
        text-align: center;
    }

    .register-auth-divider::before {
        content: '';
        position: absolute;
        inset: 50% 0 auto;
        border-top: 1px solid rgba(255,255,255,.08);
    }

    .register-auth-divider span {
        position: relative;
        z-index: 1;
        display: inline-block;
        padding: 0 .75rem;
        background: #0e141d;
        color: rgba(198, 216, 215, .72);
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .register-social-btn {
        border-radius: .85rem;
        border: 1px solid rgba(255,255,255,.07);
        background: rgba(255,255,255,.03);
        color: #eef7f6;
        font-weight: 700;
    }

    .register-social-btn:hover,
    .register-social-btn:focus {
        background: rgba(255,255,255,.06);
        color: #fff;
    }

    .register-footer-link {
        color: rgba(228, 242, 241, .78);
    }

    .register-footer-link a {
        color: #87f0e6;
    }

    @media (max-width: 991.98px) {
        .register-card .card-body {
            padding: 1.05rem !important;
        }

        .register-note-card {
            margin-bottom: 1rem;
        }

        .register-section {
            padding: .8rem;
        }
    }

</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head', 'fcc_register_premium_styles') ?>

<div class="register-shell">
    <div class="register-eyebrow">Forever Card Club</div>

    <h1 class="register-title">
        <?= l('register.hero_title') ?>
        <span class="register-subtitle"><?= l('register.hero_subtitle') ?></span>
    </h1>

    <div class="register-note-card">
        <?= l('register.hero_note') ?>
    </div>

    <form action="" method="post" class="mt-4" role="form">
        <?php if(!settings()->users->register_only_social_logins): ?>
            <div class="register-grid">
                <section class="register-section">
                    <div class="register-section-title">Osnovni podaci</div>

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

                        <div class="col-12 col-lg-6 register-form-col">
                            <div class="form-group">
                                <label for="meta_foreverId"><?= l('global.forerverId') ?></label>
                                <input id="meta_foreverId" type="text" name="meta_foreverId" class="form-control <?= \Altum\Alerts::has_field_errors('meta_foreverId') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_foreverId']) ? $data->values['meta_foreverId'] : '' ?>" maxlength="12" required="required"/>
                                <?= \Altum\Alerts::output_field_error('meta_foreverId') ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="register-section">
                    <div class="register-section-title">Kontakt i adresa</div>

                    <div class="row register-form-grid">
                        <div class="col-12 col-lg-6 register-form-col">
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

                        <div class="col-12 col-lg-6 register-form-col">
                            <div class="form-group">
                                <label for="meta_country"><?= l('global.country') ?></label>
                                <input id="meta_country" type="text" name="meta_country" class="form-control <?= \Altum\Alerts::has_field_errors('meta_country') ? 'is-invalid' : null ?>" value="<?= isset($data->values['meta_country']) && !empty($data->values['meta_country']) ? $data->values['meta_country'] : 'Hrvatska' ?>" maxlength="64" required="required"/>
                                <?= \Altum\Alerts::output_field_error('meta_country') ?>
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
                    </div>
                </section>

                <section class="register-section">
                    <div class="register-section-title">Potvrda</div>

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

    <div class="mt-5 text-center register-footer-link">
        <?= sprintf(l('register.login'), '<a href="' . url('login' . $data->redirect_append) . '" class="font-weight-bold">' . l('register.login_help') . '</a>') ?></a>
    </div>
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
