<?php defined('ALTUMCODE') || die() ?>

<?= \Altum\Alerts::output_alerts() ?>

<?php $contact_country_options = get_contact_phone_country_options_array(); ?>



<!-- Custom code: FC-2026-09-14: Consistent form grid and section headings -->
<div class="register-shell">
    <div class="register-eyebrow"><?= htmlspecialchars(fcc_t('Forever Card Club'), ENT_QUOTES, 'UTF-8') ?></div>

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
                <label for="registration_website"><?= htmlspecialchars(fcc_t('Website'), ENT_QUOTES, 'UTF-8') ?></label>
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
                        <input type="checkbox" name="accept" class="custom-control-input" id="accept" value="1" required="required">
                        <label class="custom-control-label" for="accept">
                            <small class="text-muted">
                                <?= sprintf(
                                    l('register.accept'),
                                    '<a href="' . fcc_legal_url('terms') . '" target="_blank">' . l('global.terms_and_conditions') . '</a>',
                                    '<a href="' . fcc_legal_url('privacy') . '" target="_blank">' . l('global.privacy_policy') . '</a>'
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
            <div class="register-auth-divider"><span><?= htmlspecialchars(fcc_t('ili nastavi s'), ENT_QUOTES, 'UTF-8') ?></span></div>

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
