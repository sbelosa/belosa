<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-03-05: account contact meta fields source */
$account_preferences = is_string($this->user->preferences ?? null) ? json_decode($this->user->preferences ?? '{}') : ($this->user->preferences ?? (object) []);
if(is_array($account_preferences)) {
    $account_preferences = (object) $account_preferences;
}
if(!is_object($account_preferences)) {
    $account_preferences = (object) [];
}

$account_meta = $account_preferences->meta ?? (object) [];
if(is_array($account_meta)) {
    $account_meta = (object) $account_meta;
}
if(!is_object($account_meta)) {
    $account_meta = (object) [];
}
/* /Custom code: FC-2026-03-05 */

$account_get_plan_translation_value = static function($plan, string $field): string {
    if(!$plan) {
        return '';
    }

    $translation = $plan->translations->{\Altum\Language::$name} ?? null;

    if(is_object($translation) && isset($translation->{$field}) && trim((string) $translation->{$field}) !== '') {
        return (string) $translation->{$field};
    }

    return trim((string) ($plan->{$field} ?? ''));
};

$account_current_plan_name = $account_get_plan_translation_value($this->user->plan ?? null, 'name');
$account_phone_value = trim((string) ($account_meta->phone ?? ''));
$account_twofa_enabled = !empty($this->user->twofa_secret);
$account_billing_locked = !empty($this->user->payment_subscription_id);
?>

<div class="container fcc-account-page">
    <?= \Altum\Alerts::output_alerts() ?>

    <?= $this->views['account_header_menu'] ?>

    <section class="fcc-account-hero">
        <div class="fcc-account-hero__content">
            <div class="fcc-account-eyebrow"><?= l('account.premium.eyebrow') ?></div>
            <h1 class="fcc-account-title"><?= $this->user->name ?: $this->user->email ?></h1>
            <p class="fcc-account-subtitle"><?= l('account.premium.subtitle') ?></p>

            <div class="fcc-account-badges">
                <span class="fcc-account-badge"><?= $this->user->email ?></span>
                <span class="fcc-account-badge is-highlight"><?= l('account_plan.menu') ?>: <?= $account_current_plan_name ?></span>
                <span class="fcc-account-badge"><?= l('account.settings.timezone') ?>: <?= $this->user->timezone ?></span>
            </div>
        </div>

        <div class="fcc-account-hero__metrics">
            <div class="fcc-account-metric-card">
                <div class="fcc-account-metric-label"><?= l('account.settings.contact_header') ?></div>
                <div class="fcc-account-metric-value"><?= $account_phone_value !== '' ? $account_phone_value : l('global.none') ?></div>
                <div class="fcc-account-metric-note"><?= l('account.settings.phone') ?></div>
            </div>

            <div class="fcc-account-metric-card">
                <div class="fcc-account-metric-label"><?= l('account.twofa.header') ?></div>
                <div class="fcc-account-metric-value"><?= $account_twofa_enabled ? l('global.yes') : l('global.no') ?></div>
                <div class="fcc-account-metric-note"><?= l('account.twofa.subheader') ?></div>
            </div>

            <div class="fcc-account-metric-card">
                <div class="fcc-account-metric-label"><?= l('account.billing.header') ?></div>
                <div class="fcc-account-metric-value"><?= $account_billing_locked ? l('account.premium.billing_locked') : l('account.premium.billing_editable') ?></div>
                <div class="fcc-account-metric-note"><?= l('account.billing.subheader') ?></div>
            </div>

            <div class="fcc-account-metric-card">
                <div class="fcc-account-metric-label"><?= l('account.change_password.header') ?></div>
                <div class="fcc-account-metric-value"><?= l('global.active') ?></div>
                <div class="fcc-account-metric-note"><?= l('account.change_password.subheader') ?></div>
            </div>
        </div>
    </section>

    <form action="" method="post" role="form" enctype="multipart/form-data">
        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

        <section class="fcc-account-section">
            <div class="fcc-account-section-header d-flex align-items-center mb-3">
                <h1 class="h4 m-0"><?= l('account.settings.header') ?></h1>

                <div class="ml-2">
                    <span data-toggle="tooltip" title="<?= l('account.settings.subheader') ?>">
                        <i class="fas fa-fw fa-info-circle text-muted"></i>
                    </span>
                </div>
            </div>

            <div class="card fcc-account-card">
                <div class="card-body">
                    <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= settings()->main->avatar_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->main->avatar_size_limit) ?>">
                        <label for="avatar"><i class="fas fa-fw fa-sm fa-image text-muted mr-1"></i> <?= l('account.settings.avatar') ?></label>
                        <?= include_view(THEME_PATH . 'views/partials/file_image_input.php', ['uploads_file_key' => 'users', 'file_key' => 'avatar', 'already_existing_image' => $this->user->avatar, 'input_data' => 'data-crop data-aspect-ratio="1"']) ?>
                        <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('users')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->main->avatar_size_limit) ?></small>
                    </div>

                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label for="name"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('global.name') ?></label>
                                <input type="text" id="name" name="name" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" value="<?= $this->user->name ?>" maxlength="32" />
                                <?= \Altum\Alerts::output_field_error('name') ?>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label for="email"><i class="fas fa-fw fa-sm fa-envelope text-muted mr-1"></i> <?= l('global.email') ?></label>
                                <input type="text" id="email" name="email" class="form-control <?= \Altum\Alerts::has_field_errors('email') ? 'is-invalid' : null ?>" value="<?= $this->user->email ?>" maxlength="128" />
                                <?= \Altum\Alerts::output_field_error('email') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Custom code: FC-2026-03-05: account contact details section -->
                    <div class="fcc-account-subcard mb-3">
                        <h2 class="h6 mb-2"><?= l('account.settings.contact_header') ?></h2>
                        <p class="small text-muted mb-3"><?= l('account.settings.contact_subheader') ?></p>

                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <div class="form-group mb-0">
                                    <label for="phone"><i class="fas fa-fw fa-sm fa-phone text-muted mr-1"></i> <?= l('account.settings.phone') ?></label>
                                    <input type="text" id="phone" name="phone" class="form-control <?= \Altum\Alerts::has_field_errors('phone') ? 'is-invalid' : null ?>" value="<?= $_POST['phone'] ?? ($account_meta->phone ?? '') ?>" maxlength="32" placeholder="385911234567" />
                                    <?= \Altum\Alerts::output_field_error('phone') ?>
                                    <small class="form-text text-muted"><?= l('account.settings.phone_help') ?></small>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6 mt-3 mt-lg-0">
                                <div class="form-group mb-0">
                                    <label for="forever_id"><i class="fas fa-fw fa-sm fa-id-card text-muted mr-1"></i> <?= l('account.settings.forever_id') ?></label>
                                    <!-- Custom code: FC-2026-03-05: forever id is display-only on account page -->
                                    <input type="text" id="forever_id" class="form-control" value="<?= $account_meta->foreverId ?? '' ?>" readonly="readonly" />
                                    <!-- /Custom code: FC-2026-03-05 -->
                                    <small class="form-text text-muted"><?= l('account.settings.forever_id_help') ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Custom code: FC-2026-03-05 -->

                    <div class="form-group">
                        <label for="timezone"><i class="fas fa-fw fa-sm fa-user-clock text-muted mr-1"></i> <?= l('account.settings.timezone') ?></label>
                        <select id="timezone" name="timezone" class="custom-select">
                            <?php foreach(DateTimeZone::listIdentifiers() as $timezone) echo '<option value="' . $timezone . '" ' . ($this->user->timezone == $timezone ? 'selected="selected"' : null) . '>' . $timezone . '</option>' ?>
                        </select>
                        <small class="form-text text-muted"><?= l('account.settings.timezone_help') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="anti_phishing_code"><i class="fas fa-fw fa-sm fa-user-secret text-muted mr-1"></i> <?= l('account.settings.anti_phishing_code') ?></label>
                        <input type="text" id="anti_phishing_code" name="anti_phishing_code" class="form-control <?= \Altum\Alerts::has_field_errors('anti_phishing_code') ? 'is-invalid' : null ?>" value="<?= $this->user->anti_phishing_code ?>" maxlength="8" />
                        <?= \Altum\Alerts::output_field_error('anti_phishing_code') ?>
                        <small class="form-text text-muted"><?= l('account.settings.anti_phishing_code_help') ?></small>
                    </div>

                    <?php if(\Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled): ?>
                        <div class="form-group">
                            <label for="referral_key"><i class="fas fa-fw fa-sm fa-wallet text-muted mr-1"></i> <?= l('account.settings.referral_key') ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><?= remove_url_protocol_from_url(SITE_URL) . '?ref=' ?></span>
                                </div>
                                <input type="text" id="referral_key" name="referral_key" class="form-control <?= \Altum\Alerts::has_field_errors('referral_key') ? 'is-invalid' : null ?>" value="<?= $this->user->referral_key ?>" maxlength="32" />
                            </div>
                            <?= \Altum\Alerts::output_field_error('referral_key') ?>
                        </div>
                    <?php endif ?>

                    <?php if(settings()->users->account_display_newsletter_checkbox): ?>
                        <div class="form-group custom-control custom-switch">
                            <input id="is_newsletter_subscribed" name="is_newsletter_subscribed" type="checkbox" class="custom-control-input" <?= $this->user->is_newsletter_subscribed ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="is_newsletter_subscribed"><i class="fas fa-fw fa-sm fa-newspaper text-muted mr-1"></i> <?= l('account.settings.is_newsletter_subscribed') ?></label>
                            <small class="form-text text-muted"><?= l('account.settings.is_newsletter_subscribed_help') ?></small>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </section>

        <hr class="fcc-account-divider my-4" />

        <section class="fcc-account-section" id="billing" style="<?= !settings()->payment->is_enabled || !settings()->payment->taxes_and_billing_is_enabled ? 'display: none;' : null ?>">
            <div class="fcc-account-section-header d-flex align-items-center mb-3">
                <h1 class="h4 m-0"><?= l('account.billing.header') ?></h1>

                <div class="ml-2">
                    <span data-toggle="tooltip" title="<?= l('account.billing.subheader') ?>">
                        <i class="fas fa-fw fa-info-circle text-muted"></i>
                    </span>
                </div>
            </div>

            <div class="card fcc-account-card">
                <div class="card-body">
                    <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="billing_type"><i class="fas fa-fw fa-sm fa-briefcase text-muted mr-1"></i> <?= l('account.billing.type') ?></label>
                                    <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                                        <div class="p-2 col-6">
                                            <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= $this->user->billing->type == 'personal' ? 'active' : null ?>">
                                                <input type="radio" name="billing_type" value="personal" class="custom-control-input" <?= $this->user->billing->type == 'personal' ? 'checked="checked"' : null ?> />
                                                <i class="fas fa-user fa-fw fa-sm mr-1"></i> <?= l('account.billing.type_personal') ?>
                                            </label>
                                        </div>
                                        <div class="p-2 col-6">
                                            <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= $this->user->billing->type == 'business' ? 'active' : null ?>">
                                                <input type="radio" name="billing_type" value="business" class="custom-control-input" <?= $this->user->billing->type == 'business' ? 'checked="checked"' : null ?> />
                                                <i class="fas fa-user-tag fa-fw fa-sm mr-1"></i> <?= l('account.billing.type_business') ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="billing_name"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('account.billing.name') ?></label>
                                    <input id="billing_name" type="text" name="billing_name" class="form-control" value="<?= $this->user->billing->name ?>" maxlength="64" />
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="billing_address"><i class="fas fa-fw fa-sm fa-map-marker-alt text-muted mr-1"></i> <?= l('account.billing.address') ?></label>
                                    <input id="billing_address" type="text" name="billing_address" class="form-control" value="<?= $this->user->billing->address ?>" maxlength="128" />
                                </div>
                            </div>

                            <div class="col-12 col-lg">
                                <div class="form-group">
                                    <label for="billing_city"><i class="fas fa-fw fa-sm fa-city text-muted mr-1"></i> <?= l('global.city') ?></label>
                                    <input id="billing_city" type="text" name="billing_city" class="form-control" value="<?= $this->user->billing->city ?>" maxlength="64" />
                                </div>
                            </div>

                            <div class="col-12 col-lg" id="billing_state_container" style="display: none;">
                                <div class="form-group">
                                    <label for="billing_state"><i class="fas fa-fw fa-sm fa-map text-muted mr-1"></i> <?= l('account.billing.state') ?></label>
                                    <select id="billing_state" name="billing_state" class="custom-select">
                                        <option value=" "><?= l('global.none') ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-lg">
                                <div class="form-group">
                                    <label for="billing_county"><i class="fas fa-fw fa-sm fa-building text-muted mr-1"></i> <?= l('account.billing.county') ?></label>
                                    <input id="billing_county" type="text" name="billing_county" class="form-control" value="<?= $this->user->billing->county ?>" maxlength="64" />
                                </div>
                            </div>

                            <div class="col-12 col-lg">
                                <div class="form-group">
                                    <label for="billing_zip"><i class="fas fa-fw fa-sm fa-sort-numeric-up-alt text-muted mr-1"></i> <?= l('account.billing.zip') ?></label>
                                    <input id="billing_zip" type="text" name="billing_zip" class="form-control" value="<?= $this->user->billing->zip ?>" maxlength="32" />
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="billing_country"><i class="fas fa-fw fa-sm fa-flag text-muted mr-1"></i> <?= l('global.country') ?></label>
                                    <select id="billing_country" name="billing_country" class="custom-select">
                                        <?php foreach(get_countries_array() as $key => $value): ?>
                                            <option value="<?= $key ?>" <?= $this->user->billing->country == $key ? 'selected="selected"' : null ?>><?= $value ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="billing_phone"><i class="fas fa-fw fa-sm fa-phone-square-alt text-muted mr-1"></i> <?= l('account.billing.phone') ?></label>
                                    <input id="billing_phone" type="text" name="billing_phone" class="form-control <?= \Altum\Alerts::has_field_errors('billing_phone') ? 'is-invalid' : null ?>" value="<?= $this->user->billing->phone ?>" maxlength="32" placeholder="385911234567" />
                                    <?= \Altum\Alerts::output_field_error('billing_phone') ?>
                                    <small class="form-text text-muted"><?= l('account.billing.phone_help') ?></small>
                                </div>
                            </div>

                            <div class="col-12" data-billing-type="business">
                                <div class="form-group">
                                    <label for="billing_tax_id"><i class="fas fa-fw fa-sm fa-tag text-muted mr-1"></i> <?= !empty(settings()->business->tax_type) ? settings()->business->tax_type : l('account.billing.tax_id') ?></label>
                                    <input id="billing_tax_id" type="text" name="billing_tax_id" class="form-control" value="<?= $this->user->billing->tax_id ?>" maxlength="64" />
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group" data-character-counter="textarea">
                                    <label for="billing_notes" class="d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-fw fa-sm fa-file-alt text-muted mr-1"></i> <?= l('account.billing.notes') ?></span>
                                        <small class="text-muted" data-character-counter-wrapper></small>
                                    </label>
                                    <textarea id="billing_notes" name="billing_notes" class="form-control" maxlength="512"><?= $this->user->billing->notes ?></textarea>
                                    <small class="form-text text-muted"><?= l('account.billing.notes_help') ?></small>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </section>

        <?php ob_start() ?>
        <script>
            'use strict';

            /* Toggle business fields */
            type_handler('[name="billing_type"]', 'data-billing-type');
            document.querySelectorAll('[name="billing_type"]').forEach(element => {
                element.addEventListener('change', () => {
                    type_handler('[name="billing_type"]', 'data-billing-type');
                });
            });

            /* Disable all billing fields if subscription is active */
            <?php if(!empty($this->user->payment_subscription_id)): ?>
            document.querySelectorAll('[name^="billing_"]').forEach(element => {
                element.setAttribute('disabled', 'disabled');
            });
            <?php endif ?>

            /* Dynamic states */
            const states_data = {
                'US': [
                    'Alabama','Alaska','Arizona','Arkansas','California','Colorado','Connecticut','Delaware','Florida','Georgia',
                    'Hawaii','Idaho','Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts',
                    'Michigan','Minnesota','Mississippi','Missouri','Montana','Nebraska','Nevada','New Hampshire','New Jersey',
                    'New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Rhode Island',
                    'South Carolina','South Dakota','Tennessee','Texas','Utah','Vermont','Virginia','Washington','West Virginia',
                    'Wisconsin','Wyoming'
                ],
                'CA': [
                    'Alberta','British Columbia','Manitoba','New Brunswick','Newfoundland and Labrador','Northwest Territories',
                    'Nova Scotia','Nunavut','Ontario','Prince Edward Island','Quebec','Saskatchewan','Yukon'
                ],
                'IN': [
                    'Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Delhi','Goa','Gujarat','Haryana',
                    'Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya',
                    'Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura',
                    'Uttar Pradesh','Uttarakhand','West Bengal'
                ],
                'BR': [
                    'Acre','Alagoas','Amapá','Amazonas','Bahia','Ceará','Distrito Federal','Espírito Santo','Goiás','Maranhão',
                    'Mato Grosso','Mato Grosso do Sul','Minas Gerais','Pará','Paraíba','Paraná','Pernambuco','Piauí',
                    'Rio de Janeiro','Rio Grande do Norte','Rio Grande do Sul','Rondônia','Roraima','Santa Catarina',
                    'São Paulo','Sergipe','Tocantins'
                ]
            };

            const country_select = document.querySelector('#billing_country');
            const state_container = document.querySelector('#billing_state_container');
            const state_select = document.querySelector('#billing_state');
            const saved_state = '<?= $this->user->billing->state ?? '' ?>';

            let state_handler = (selected_country) => {
                /* Reset dropdown */
                state_select.innerHTML = '<option value=" "><?= l('global.none') ?></option>';

                if(states_data[selected_country]) {
                    /* Populate with states */
                    states_data[selected_country].forEach(state => {
                        const is_selected = (state === saved_state) ? 'selected' : '';
                        state_select.innerHTML += `<option value="${state}" ${is_selected}>${state}</option>`;
                    });
                    state_container.style.display = 'block';
                } else {
                    state_container.style.display = 'none';
                }
            }

            /* Trigger on page load */
            state_handler(country_select.value);

            /* Trigger when country changes */
            country_select.addEventListener('change', function() {
                state_handler(this.value);
            });
        </script>
        <?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

        <hr class="fcc-account-divider my-4" />

        <section class="fcc-account-section">
            <div class="fcc-account-section-header d-flex align-items-center mb-3">
                <h1 class="h4 m-0"><?= l('account.twofa.header') ?></h1>

                <div class="ml-2">
                    <span data-toggle="tooltip" title="<?= l('account.twofa.subheader') ?>">
                        <i class="fas fa-fw fa-info-circle text-muted"></i>
                    </span>
                </div>
            </div>

            <div class="card fcc-account-card">
                <div class="card-body">
                    <div class="form-group">
                        <label for="twofa_is_enabled"><i class="fas fa-fw fa-sm fa-passport text-muted mr-1"></i> <?= l('account.twofa.is_enabled') ?></label>
                        <select id="twofa_is_enabled" name="twofa_is_enabled" class="custom-select <?= \Altum\Alerts::has_field_errors('twofa_token') ? 'is-invalid' : null ?>">
                            <option value="1" <?= $this->user->twofa_secret || (!empty($_POST) && $_POST['twofa_is_enabled']) ? 'selected="selected"' : null ?>><?= l('global.yes') ?></option>
                            <option value="0" <?= !$this->user->twofa_secret || (!empty($_POST) && !$_POST['twofa_is_enabled']) ? 'selected="selected"' : null ?>><?= l('global.no') ?></option>
                        </select>
                    </div>

                    <div data-twofa-is-enabled="1">
                        <?php if(!$this->user->twofa_secret): ?>
                            <div class="form-group">
                                <span class="h6"><?= l('account.twofa.qr') ?></span>
                                <p class="small text-muted"><?= l('account.twofa.qr_help') ?></p>

                                <div class="row">
                                    <div class="col-md-4 d-flex justify-content-center mb-3 mb-lg-0">
                                        <img src="<?= $data->twofa_image ?>" class="img-fluid" alt="<?= l('account.twofa.qr') ?>" />
                                    </div>

                                    <div class="col-md-8 d-flex flex-column justify-content-center">
                                        <span class="h6 m-0"><?= l('account.twofa.secret') ?></span>
                                        <p class="small text-muted mb-4"><?= l('account.twofa.secret_help') ?></p>

                                        <p class="h5">
                                            <?= $data->twofa_secret ?>
                                            <button
                                                    type="button"
                                                    class="btn btn-sm btn-light ml-2"
                                                    data-toggle="tooltip"
                                                    title="<?= l('global.clipboard_copy') ?>"
                                                    aria-label="<?= l('global.clipboard_copy') ?>"
                                                    data-copy="<?= l('global.clipboard_copy') ?>"
                                                    data-copied="<?= l('global.clipboard_copied') ?>"
                                                    data-clipboard-text="<?= $data->twofa_secret ?>"
                                            >
                                                <i class="fas fa-fw fa-sm fa-copy"></i>
                                            </button>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <span class="h6"><?= l('account.twofa.verify') ?></span>
                                <p class="small text-muted"><?= l('account.twofa.verify_help') ?></p>
                                <input type="text" id="twofa_token" name="twofa_token" class="form-control <?= \Altum\Alerts::has_field_errors('twofa_token') ? 'is-invalid' : null ?>" value="" autocomplete="off" placeholder="123 456" maxlength="6" />
                                <?= \Altum\Alerts::output_field_error('twofa_token') ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </section>

        <hr class="fcc-account-divider my-4" />

        <section class="fcc-account-section">
            <div class="fcc-account-section-header d-flex align-items-center mb-3">
                <h1 class="h4 m-0"><?= l('account.change_password.header') ?></h1>

                <div class="ml-2">
                    <span data-toggle="tooltip" title="<?= l('account.change_password.subheader') ?>">
                        <i class="fas fa-fw fa-info-circle text-muted"></i>
                    </span>
                </div>
            </div>

            <div class="card fcc-account-card">
                <div class="card-body">
                    <div class="form-group" data-password-toggle-view data-password-toggle-view-show="<?= l('global.show') ?>" data-password-toggle-view-hide="<?= l('global.hide') ?>">
                        <label for="old_password"><i class="fas fa-fw fa-sm fa-unlock text-muted mr-1"></i> <?= l('account.change_password.current_password') ?></label>
                        <input type="password" id="old_password" name="old_password" class="form-control <?= \Altum\Alerts::has_field_errors('old_password') ? 'is-invalid' : null ?>" />
                        <small class="form-text text-muted"><?= l('account.change_password.current_password_help') ?></small>
                        <?= \Altum\Alerts::output_field_error('old_password') ?>
                    </div>

                    <div class="form-group" data-password-toggle-view data-password-toggle-view-show="<?= l('global.show') ?>" data-password-toggle-view-hide="<?= l('global.hide') ?>">
                        <label for="new_password"><i class="fas fa-fw fa-sm fa-lock text-muted mr-1"></i> <?= l('account.change_password.new_password') ?></label>
                        <input type="password" id="new_password" name="new_password" class="form-control <?= \Altum\Alerts::has_field_errors('new_password') ? 'is-invalid' : null ?>" />
                        <?= \Altum\Alerts::output_field_error('new_password') ?>
                    </div>

                    <div class="form-group" data-password-toggle-view data-password-toggle-view-show="<?= l('global.show') ?>" data-password-toggle-view-hide="<?= l('global.hide') ?>">
                        <label for="repeat_password"><i class="fas fa-fw fa-sm fa-lock text-muted mr-1"></i> <?= l('account.change_password.repeat_password') ?></label>
                        <input type="password" id="repeat_password" name="repeat_password" class="form-control <?= \Altum\Alerts::has_field_errors('repeat_password') ? 'is-invalid' : null ?>" />
                        <?= \Altum\Alerts::output_field_error('repeat_password') ?>
                    </div>
                </div>
            </div>
        </section>

        <button type="submit" name="submit" class="btn btn-block btn-primary btn-lg fcc-account-save-btn mt-5"><?= l('account.premium.save_changes') ?></button>
    </form>
</div>

<style>
    .fcc-account-page {
        padding-bottom: 3rem;
    }

    .fcc-account-hero,
    .fcc-account-card {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid rgba(110, 142, 196, 0.18);
        background:
            radial-gradient(circle at top right, rgba(72, 230, 210, 0.13), transparent 26%),
            radial-gradient(circle at bottom left, rgba(76, 132, 255, 0.12), transparent 30%),
            linear-gradient(180deg, rgba(14, 24, 45, 0.98), rgba(10, 17, 32, 0.98));
        box-shadow: 0 26px 70px rgba(3, 10, 24, 0.36);
        color: #ecf7ff;
    }

    .fcc-account-hero::before,
    .fcc-account-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.035), transparent 26%);
        pointer-events: none;
    }

    .fcc-account-hero {
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .fcc-account-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .16em;
        font-weight: 800;
        color: #8ceee2;
        margin-bottom: .95rem;
    }

    .fcc-account-title {
        font-size: clamp(2rem, 4.2vw, 3.2rem);
        line-height: .98;
        letter-spacing: -.05em;
        margin-bottom: 1rem;
        color: #f7fbff;
    }

    .fcc-account-subtitle,
    .fcc-account-metric-note,
    .fcc-account-page .text-muted,
    .fcc-account-page small.text-muted,
    .fcc-account-page .form-text.text-muted {
        color: rgba(219, 232, 247, 0.78) !important;
    }

    .fcc-account-subtitle {
        max-width: 62ch;
        font-size: 1.04rem;
        line-height: 1.7;
        margin-bottom: 1.25rem;
    }

    .fcc-account-badges {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
    }

    .fcc-account-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: .72rem 1rem;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(153, 183, 225, 0.18);
        color: #f3fbff;
        font-size: .92rem;
        line-height: 1.2;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .fcc-account-badge.is-highlight {
        background: linear-gradient(135deg, rgba(80, 219, 203, 0.22), rgba(78, 129, 255, 0.14));
        border-color: rgba(124, 232, 220, 0.4);
    }

    .fcc-account-hero__metrics {
        margin-top: 1.5rem;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .fcc-account-metric-card {
        border-radius: 22px;
        padding: 1.2rem;
        background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.025));
        border: 1px solid rgba(161, 192, 235, 0.12);
        min-height: 148px;
    }

    .fcc-account-metric-label {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .14em;
        color: rgba(178, 208, 239, 0.72);
        margin-bottom: .8rem;
        font-weight: 700;
    }

    .fcc-account-metric-value {
        font-size: clamp(1.15rem, 2.8vw, 1.8rem);
        line-height: 1.08;
        font-weight: 900;
        letter-spacing: -.04em;
        margin-bottom: .55rem;
        color: #ffffff;
        word-break: break-word;
    }

    .fcc-account-section-header h1 {
        color: #edf8ff;
    }

    .fcc-account-card {
        border: 1px solid rgba(110, 142, 196, 0.14);
    }

    .fcc-account-card .card-body {
        position: relative;
        z-index: 1;
        padding: 1.55rem;
        background: transparent;
    }

    .fcc-account-subcard {
        border-radius: 22px;
        border: 1px solid rgba(163, 192, 234, 0.12);
        background: rgba(255,255,255,0.035);
        padding: 1rem;
    }

    .fcc-account-divider {
        border-top: 1px solid rgba(163, 192, 234, 0.12);
        opacity: 1;
    }

    .fcc-account-page .form-control,
    .fcc-account-page .form-control:focus,
    .fcc-account-page .custom-select,
    .fcc-account-page .custom-select:focus,
    .fcc-account-page .input-group-text,
    .fcc-account-page textarea.form-control {
        background: rgba(255,255,255,0.05);
        border-color: rgba(163, 192, 234, 0.14);
        color: #eef8ff;
    }

    .fcc-account-page .form-control::placeholder,
    .fcc-account-page textarea.form-control::placeholder {
        color: rgba(214, 228, 245, 0.42);
    }

    .fcc-account-page label,
    .fcc-account-page .custom-control-label,
    .fcc-account-page .btn-light,
    .fcc-account-page .btn-light:hover,
    .fcc-account-page .btn-light:focus {
        color: #ecf7ff;
    }

    .fcc-account-page .custom-control-label::before,
    .fcc-account-page .custom-control-label::after {
        top: .2rem;
    }

    .fcc-account-page .btn-group-toggle .btn-light {
        background: rgba(255,255,255,0.04);
        border-color: rgba(181, 213, 248, 0.14);
        border-radius: 18px;
        min-height: 3.4rem;
    }

    .fcc-account-page .btn-group-toggle .btn-light.active,
    .fcc-account-page .btn-group-toggle .btn-light:hover {
        background: linear-gradient(135deg, rgba(98, 242, 223, 0.18), rgba(122, 208, 255, 0.12));
        border-color: rgba(126, 232, 220, 0.28);
    }

    .fcc-account-page .btn-primary {
        border: 0;
        color: #062322;
        background: linear-gradient(135deg, #62f2df 0%, #4fd7cb 40%, #7ad0ff 100%);
        box-shadow: 0 18px 32px rgba(79, 215, 203, 0.22);
    }

    .fcc-account-page .btn-primary:hover,
    .fcc-account-page .btn-primary:focus {
        color: #04191c;
        transform: translateY(-1px);
        box-shadow: 0 22px 38px rgba(79, 215, 203, 0.28);
    }

    .fcc-account-save-btn {
        border-radius: 18px;
        padding: 1rem 1.35rem;
        font-weight: 800;
        letter-spacing: -.01em;
    }

    .fcc-account-page .input-group-text {
        color: rgba(219, 232, 247, 0.7);
    }

    .fcc-account-page .btn-sm.btn-light {
        background: rgba(255,255,255,0.06);
        border-color: rgba(181, 213, 248, 0.16);
    }

    .fcc-account-page .img-fluid {
        border-radius: 20px;
        border: 1px solid rgba(163, 192, 234, 0.12);
        background: rgba(255,255,255,0.03);
        padding: .4rem;
    }

    @media (max-width: 1199.98px) {
        .fcc-account-hero__metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .fcc-account-hero,
        .fcc-account-card .card-body {
            padding: 1.25rem;
        }

        .fcc-account-hero {
            border-radius: 24px;
        }

        .fcc-account-hero__metrics {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include_view(THEME_PATH . 'views/partials/js_cropper.php') ?>

<?php if(!$this->user->twofa_secret): ?>
    <?php ob_start() ?>
    <script>
        'use strict';

        type_handler('select[name="twofa_is_enabled"]', 'data-twofa-is-enabled');
        document.querySelector('select[name="twofa_is_enabled"]') && document.querySelectorAll('select[name="twofa_is_enabled"]').forEach(element => element.addEventListener('change', () => { type_handler('select[name="twofa_is_enabled"]', 'data-twofa-is-enabled'); }));
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>
