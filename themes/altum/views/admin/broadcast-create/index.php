<?php defined('ALTUMCODE') || die() ?>

<?php /* Custom code: FC-2026-03-19: prepare broadcast content for Quill editor */ ?>
<?php $broadcast_editor_content = json_decode($data->values['content']) ? bootstrap_to_quilljs(convert_editorjs_json_to_html($data->values['content'])) : $data->values['content'] ?>
<?php $broadcast_editor_content = $broadcast_editor_content ?: '<p>Upiši sadržaj e-maila ovdje.</p>' ?>
<?php $broadcast_shortcodes = ['{{WEBSITE_TITLE}}', '{{USER:NAME}}', '{{USER:EMAIL}}', '{{FOREVER_CARD_APPLICATION_URL}}', '{{USER:CONTINENT_NAME}}', '{{USER:COUNTRY_NAME}}', '{{USER:CITY_NAME}}', '{{USER:DEVICE_TYPE}}', '{{USER:OS_NAME}}', '{{USER:BROWSER_NAME}}', '{{USER:BROWSER_LANGUAGE}}']; ?>
<?php /* /Custom code: FC-2026-03-19 */ ?>

<?php if(settings()->main->breadcrumbs_is_enabled): ?>
    <nav aria-label="breadcrumb">
        <ol class="custom-breadcrumbs small">
            <li>
                <a href="<?= url('admin/broadcasts') ?>"><?= l('admin_broadcasts.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
            </li>
            <li class="active" aria-current="page"><?= l('admin_broadcast_create.breadcrumb') ?></li>
        </ol>
    </nav>
<?php endif ?>

<div class="d-flex justify-content-between mb-4">
    <h1 class="h3 mb-0 mr-1"><i class="fas fa-fw fa-xs fa-mail-bulk text-primary-900 mr-2"></i> <?= l('admin_broadcast_create.header') ?></h1>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<!-- Custom code: FC-2026-03-19: broadcast studio layout -->
<div class="mail-studio-page">
<div class="card mail-studio-hero mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-3 mb-lg-0">
                <span class="mail-studio-eyebrow">Broadcast campaign</span>
                <h2 class="mail-studio-title mb-2">Pretvori običan newsletter form u čist campaign composer.</h2>
                <p class="text-muted mb-0">Prvo odaberi publiku, zatim napiši poruku na canvasu koji izgleda kao gotov email, a ne kao sirovi textarea.</p>
            </div>
            <div class="col-lg-5">
                <div class="mail-studio-hero__stats">
                    <div class="mail-studio-stat"><span class="mail-studio-stat__label">Tip</span><strong class="mail-studio-stat__value">Broadcast</strong></div>
                    <div class="mail-studio-stat"><span class="mail-studio-stat__label">Publika</span><strong class="mail-studio-stat__value">Segmentirana</strong></div>
                    <div class="mail-studio-stat"><span class="mail-studio-stat__label">Editor</span><strong class="mail-studio-stat__value">Mail builder</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mail-studio-card <?= \Altum\Alerts::has_field_errors() ? 'border-danger' : null ?>">
    <div class="card-body">

        <form id="broadcast_create_form" action="" method="post" role="form">
            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

            <div class="card mb-4 mail-builder-shortcodes-card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                        <div>
                            <h2 class="h5 mb-1">Shortkodovi za mail</h2>
                            <p class="text-muted mb-0">Koristi ih u predmetu i sadržaju kako bi svaki korisnik dobio personaliziranu poruku.</p>
                        </div>
                        <div class="small text-muted mt-2 mt-lg-0">Klik na kod za kopiranje</div>
                    </div>

                    <div class="mail-shortcodes-grid">
                        <?php foreach($broadcast_shortcodes as $shortcode): ?>
                            <code class="mail-shortcode-tag" data-copy><?= e($shortcode) ?></code>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="name"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('global.name') ?></label>
                <input type="text" id="name" name="name" value="<?= $data->values['name'] ?>" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" maxlength="64" required="required" />
                <?= \Altum\Alerts::output_field_error('name') ?>
                <small class="form-text text-muted"><?= l('admin_broadcasts.name_help') ?></small>
            </div>

            <div class="form-group">
                <label for="subject"><i class="fas fa-fw fa-sm fa-heading text-muted mr-1"></i> <?= l('admin_broadcasts.subject') ?></label>
                <input type="text" id="subject" name="subject" value="<?= $data->values['subject'] ?>" class="form-control <?= \Altum\Alerts::has_field_errors('subject') ? 'is-invalid' : null ?>" maxlength="128" required="required" />
                <?= \Altum\Alerts::output_field_error('subject') ?>
                <small class="form-text text-muted"><?= l('admin_broadcasts.subject_help') ?></small>
                <small class="form-text text-muted"><?= sprintf(l('global.variables'), '<code data-copy>' . implode('</code> , <code data-copy>',  ['{{WEBSITE_TITLE}}', '{{USER:NAME}}', '{{USER:EMAIL}}', '{{USER:CONTINENT_NAME}}', '{{USER:COUNTRY_NAME}}', '{{USER:CITY_NAME}}', '{{USER:DEVICE_TYPE}}', '{{USER:OS_NAME}}', '{{USER:BROWSER_NAME}}', '{{USER:BROWSER_LANGUAGE}}']) . '</code>') ?></small>
            </div>

            <div class="form-group custom-control custom-switch" data-type="external">
                <input id="is_system_email" name="is_system_email" type="checkbox" class="custom-control-input" <?= $data->values['is_system_email'] ? 'checked="checked"' : null ?>>
                <label class="custom-control-label" for="is_system_email"><i class="fas fa-fw fa-sm fa-at text-muted mr-1"></i> <?= l('admin_broadcasts.is_system_email') ?></label>
                <small class="form-text text-muted"><?= l('admin_broadcasts.is_system_email_help') ?></small>
            </div>

            <div class="form-group">
                <label for="segment"><i class="fas fa-fw fa-sm fa-layer-group text-muted mr-1"></i> <?= l('admin_broadcasts.segment') ?> <span id="segment_count"></span></label>
                <select id="segment" name="segment" class="form-control <?= \Altum\Alerts::has_field_errors('segment') ? 'is-invalid' : null ?>" required="required">
                    <option value="all" <?= $data->values['segment'] == 'all' ? 'selected="selected"' : null ?>><?= l('admin_broadcasts.segment.all') ?></option>
                    <option value="subscribers" <?= $data->values['segment'] == 'subscribers' ? 'selected="selected"' : null ?>><?= l('admin_broadcasts.segment.subscribers') ?></option>
                    <option value="custom" <?= $data->values['segment'] == 'custom' ? 'selected="selected"' : null ?>><?= l('admin_broadcasts.segment.custom') ?></option>
                    <option value="filter" <?= $data->values['segment'] == 'filter' ? 'selected="selected"' : null ?>><?= l('admin_broadcasts.segment.filter') ?></option>
                </select>
                <?= \Altum\Alerts::output_field_error('segment') ?>
                <small class="form-text text-muted"><?= l('admin_broadcasts.segment_help') ?></small>
                <small class="form-text text-muted"><?= l('admin_broadcasts.segment_help2') ?></small>
            </div>

            <!-- Custom code: FC-2026-03-19: searchable custom broadcast recipient picker -->
            <div class="form-group" data-segment="custom">
                <label for="users_ids"><i class="fas fa-fw fa-sm fa-users text-muted mr-1"></i> <?= l('admin_broadcasts.users_ids') ?></label>
                <input type="text" id="users_ids_search" class="form-control mb-2" placeholder="<?= l('admin_broadcasts.users_ids_placeholder') ?>" autocomplete="off" />
                <select id="users_ids" name="users_ids[]" class="form-control broadcast-users-select <?= \Altum\Alerts::has_field_errors('users_ids') ? 'is-invalid' : null ?>" multiple="multiple" data-placeholder="<?= l('admin_broadcasts.users_ids_placeholder') ?>" required="required" size="8">
                    <?php foreach($data->available_users as $available_user): ?>
                        <?php $available_user_label = trim(($available_user->name ?: '') . ' ' . ($available_user->email ? '(' . $available_user->email . ')' : '')) ?: ('#' . $available_user->user_id) ?>
                        <option value="<?= $available_user->user_id ?>" <?= in_array($available_user->user_id, $data->values['users_ids']) ? 'selected="selected"' : null ?>><?= e($available_user_label) ?></option>
                    <?php endforeach ?>
                </select>
                <?= \Altum\Alerts::output_field_error('users_ids') ?>
                <small class="form-text text-muted"><?= l('admin_broadcasts.users_ids_help') ?></small>
            </div>
            <!-- /Custom code: FC-2026-03-19 -->

            <div class="form-group custom-control custom-switch" data-segment="filter">
                <input id="<?= 'filters_is_newsletter_subscribed' ?>" name="filters_is_newsletter_subscribed" type="checkbox" class="custom-control-input" <?= $data->values['filters_is_newsletter_subscribed'] ? 'checked="checked"' : null ?>>
                <label class="custom-control-label" for="<?= 'filters_is_newsletter_subscribed' ?>"><?= l('admin_broadcasts.segment.filter.is_newsletter_subscribed') ?></label>
            </div>

            <div class="form-group" data-segment="filter">
                <label for="plans"><i class="fas fa-fw fa-sm fa-box-open text-muted mr-1"></i> <?= l('admin_broadcasts.segment.filter.plans') ?></label>
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="custom-control custom-switch">
                            <input id="<?= 'filters_plans###free' ?>" name="filters_plans[]" value="free" type="checkbox" class="custom-control-input" <?= isset($data->values['filters_plans']['free']) ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="<?= 'filters_plans###free' ?>"><?= settings()->plan_free->name ?></label>
                        </div>
                    </div>

                    <div class="col-6 mb-3">
                        <div class="custom-control custom-switch">
                            <input id="<?= 'filters_plans###custom' ?>" name="filters_plans[]" value="custom" type="checkbox" class="custom-control-input" <?= isset($data->values['filters_plans']['custom']) ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="<?= 'filters_plans###custom' ?>"><?= settings()->plan_custom->name ?></label>
                        </div>
                    </div>

                    <?php foreach($data->plans as $plan): ?>
                        <div class="col-6 mb-3">
                            <div class="custom-control custom-switch">
                                <input id="<?= 'filters_plans###' . $plan->plan_id ?>" name="filters_plans[]" value="<?= $plan->plan_id ?>" type="checkbox" class="custom-control-input" <?= isset($data->values['filters_plans'][$plan->plan_id]) ? 'checked="checked"' : null ?>>
                                <label class="custom-control-label" for="<?= 'filters_plans###' . $plan->plan_id ?>"><?= $plan->name ?></label>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="form-group" data-segment="filter">
                <label for="status"><i class="fas fa-fw fa-sm fa-circle-dot text-muted mr-1"></i> <?= l('global.status') ?></label>
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="custom-control custom-switch">
                            <input id="<?= 'filters_status###active' ?>" name="filters_status[]" value="1" type="checkbox" class="custom-control-input" <?= isset($data->values['filters_status']['1']) ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="<?= 'filters_status###active' ?>"><?= l('admin_users.status_active') ?></label>
                        </div>
                    </div>

                    <div class="col-6 mb-3">
                        <div class="custom-control custom-switch">
                            <input id="<?= 'filters_status###unconfirmed' ?>" name="filters_status[]" value="0" type="checkbox" class="custom-control-input" <?= isset($data->values['filters_status']['0']) ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="<?= 'filters_status###unconfirmed' ?>"><?= l('admin_users.status_unconfirmed') ?></label>
                        </div>
                    </div>

                    <div class="col-6 mb-3">
                        <div class="custom-control custom-switch">
                            <input id="<?= 'filters_status###disabled' ?>" name="filters_status[]" value="2" type="checkbox" class="custom-control-input" <?= isset($data->values['filters_status']['2']) ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="<?= 'filters_status###disabled' ?>"><?= l('admin_users.status_disabled') ?></label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group" data-segment="filter">
                <label for="source"><i class="fas fa-fw fa-sm fa-right-to-bracket text-muted mr-1"></i> <?= l('admin_users.source') ?></label>
                <div class="row">
                    <?php foreach(['direct', 'admin_create', 'admin_api_create', 'facebook', 'twitter', 'discord', 'google', 'linkedin', 'microsoft'] as $source): ?>
                        <div class="col-6 mb-3">
                            <div class="custom-control custom-switch">
                                <input id="<?= 'filters_source###' . $source ?>" name="filters_source[]" value="<?= $source ?>" type="checkbox" class="custom-control-input" <?= isset($data->values['filters_source'][$source]) ? 'checked="checked"' : null ?>>
                                <label class="custom-control-label" for="<?= 'filters_source###' . $source ?>"><?= l('admin_users.source.' . $source) ?></label>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="form-group" data-segment="filter">
                <label for="device_type"><i class="fas fa-fw fa-sm fa-laptop text-muted mr-1"></i> <?= l('global.device') ?></label>
                <div class="row">
                    <?php foreach(['desktop', 'tablet', 'mobile'] as $device_type): ?>
                        <div class="col-6 mb-3">
                            <div class="custom-control custom-checkbox">
                                <input id="<?= 'filters_device_type###' . $device_type ?>" name="filters_device_type[]" value="<?= $device_type ?>" type="checkbox" class="custom-control-input" <?= isset($data->values['filters_device_type'][$device_type]) ? 'checked="checked"' : null ?>>
                                <label class="custom-control-label" for="<?= 'filters_device_type###' . $device_type ?>"><?= l('global.device.' . $device_type) ?></label>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="form-group" data-segment="filter">
                <label for="languages"><i class="fas fa-fw fa-sm fa-language text-muted mr-1"></i> <?= l('admin_broadcasts.languages') ?></label>
                <div class="row">
                    <?php foreach(\Altum\Language::$active_languages as $language_name => $language_code): ?>
                        <div class="col-6 mb-3">
                            <div class="custom-control custom-switch">
                                <input id="<?= 'filters_languages###' . $language_code ?>" name="filters_languages[]" value="<?= $language_name ?>" type="checkbox" class="custom-control-input" <?= isset($data->values['filters_languages'][$language_name]) ? 'checked="checked"' : null ?>>
                                <label class="custom-control-label" for="<?= 'filters_languages###' . $language_code ?>"><?= $language_name ?></label>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="form-group" data-segment="filter">
                <div class="form-group">
                    <label for="filters_continents"><i class="fas fa-fw fa-sm fa-globe-europe text-muted mr-1"></i> <?= l('global.continents') ?></label>
                    <select id="filters_continents" name="filters_continents[]" class="custom-select" multiple="multiple">
                        <?php foreach(get_continents_array() as $continent_code => $continent_name): ?>
                            <option value="<?= $continent_code ?>" <?= isset($data->values['filters_continents'][$continent_code]) ? 'selected="selected"' : null ?>><?= $continent_name ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="form-group" data-segment="filter">
                <div class="form-group">
                    <label for="filters_countries"><i class="fas fa-fw fa-sm fa-flag text-muted mr-1"></i> <?= l('global.countries') ?></label>
                    <select id="filters_countries" name="filters_countries[]" class="custom-select" multiple="multiple">
                        <?php foreach(get_countries_array() as $key => $value): ?>
                            <option value="<?= $key ?>" <?= isset($data->values['filters_countries'][$key]) ? 'selected="selected"' : null ?>><?= $value ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="form-group" data-segment="filter">
                <label for="filters_cities"><i class="fas fa-fw fa-sm fa-city text-muted mr-1"></i> <?= l('global.cities') ?></label>
                <input type="text" id="filters_cities" name="filters_cities" value="<?= $data->values['filters_cities'] ?>" class="form-control" placeholder="<?= l('admin_broadcasts.cities_placeholder') ?>" />
                <?= \Altum\Alerts::output_field_error('filters_cities') ?>
                <small class="form-text text-muted"><?= l('admin_broadcasts.cities_help') ?></small>
            </div>

            <div class="form-group" data-segment="filter">
                <label for="filters_operating_systems"><i class="fas fa-fw fa-server fa-sm text-muted mr-1"></i> <?= l('admin_broadcasts.operating_systems') ?></label>
                <select id="filters_operating_systems" name="filters_operating_systems[]" class="custom-select" multiple="multiple">
                    <?php foreach(['iOS', 'Android', 'Windows', 'OS X', 'Linux', 'Ubuntu', 'Chrome OS'] as $os_name): ?>
                        <option value="<?= $os_name ?>" <?= in_array($os_name, $data->values['filters_operating_systems'] ?? []) ? 'selected="selected"' : null ?>><?= $os_name ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="form-group" data-segment="filter">
                <label for="filters_browsers"><i class="fas fa-fw fa-window-restore fa-sm text-muted mr-1"></i> <?= l('admin_broadcasts.browsers') ?></label>
                <select id="filters_browsers" name="filters_browsers[]" class="custom-select" multiple="multiple">
                    <?php foreach(['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Samsung Internet'] as $browser_name): ?>
                        <option value="<?= $browser_name ?>" <?= in_array($browser_name, $data->values['filters_browsers'] ?? []) ? 'selected="selected"' : null ?>><?= $browser_name ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="form-group" data-segment="filter">
                <label for="filters_browser_languages"><i class="fas fa-fw fa-language fa-sm text-muted mr-1"></i> <?= l('admin_broadcasts.browser_languages') ?></label>
                <select id="filters_browser_languages" name="filters_browser_languages[]" class="custom-select" multiple="multiple">
                    <?php foreach(get_locale_languages_array() as $locale => $language): ?>
                        <option value="<?= $locale ?>" <?= in_array($locale, $data->values['filters_browser_languages'] ?? []) ? 'selected="selected"' : null ?>><?= $language ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="form-group">
                <label for="content"><i class="fas fa-fw fa-sm fa-paragraph text-muted mr-1"></i> <?= l('admin_broadcasts.content') ?></label>
                <div class="mail-editor-shell">
                    <div class="mail-editor-shell__header">
                        <div class="mail-editor-shell__title">Email builder</div>
                        <div class="mail-editor-shell__meta">Piši kao pravi email: jasna uvodna rečenica, kratki blokovi i jedan glavni CTA.</div>
                    </div>
                    <div class="mail-editor-shell__canvas-meta">
                        <span class="mail-editor-shell__pill">Personalizacija</span>
                        <span class="mail-editor-shell__pill">Jasna struktura poruke</span>
                        <span class="mail-editor-shell__pill">Footer odjave ide automatski</span>
                    </div>
                    <div id="quill_broadcast_editor" class="border rounded-bottom bg-transparent"></div>
                </div>
                <textarea name="content" id="content" class="form-control mail-editor-fallback <?= \Altum\Alerts::has_field_errors('content') ? 'is-invalid' : null ?>"><?= e($broadcast_editor_content) ?></textarea>
                <?= \Altum\Alerts::output_field_error('content') ?>
                <small class="form-text text-muted"><?= l('global.spintax_help') ?></small>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <input type="email" id="preview_email" name="preview_email" value="<?= $data->values['preview_email'] ?>" class="form-control <?= \Altum\Alerts::has_field_errors('preview_email') ? 'is-invalid' : null ?>" placeholder="<?= l('global.email_placeholder') ?>" />
                    <div class="input-group-append">
                        <button type="submit" name="preview" class="btn btn-light"><?= l('admin_broadcast_create.send_preview') ?></button>
                    </div>
                </div>
                <?= \Altum\Alerts::output_field_error('preview_email') ?>
            </div>
            <div class="card mail-studio-submit-card mt-4">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                    <div class="mb-3 mb-lg-0">
                        <h2 class="h5 mb-1">Draft ili odmah slanje</h2>
                        <p class="text-muted mb-0">Spremi kao draft za kasnije ili pošalji odmah odabranom segmentu.</p>
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-3 w-100 w-lg-auto">
                        <button type="submit" name="save" class="btn btn-outline-primary mt-0 mr-md-3"><?= l('admin_broadcast_create.save_draft') ?></button>
                        <button type="submit" name="send" class="btn btn-lg btn-primary mt-0"><?= l('admin_broadcast_create.send_broadcast') ?></button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
<!-- /Custom code: FC-2026-03-19 -->
</div>

<?php ob_start() ?>
<link href="<?= ASSETS_FULL_URL . 'css/libraries/quill.snow.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
<style>
    .mail-studio-page {
        display: grid;
        gap: 1.5rem;
    }

    .mail-studio-hero,
    .mail-studio-card,
    .mail-studio-submit-card,
    .mail-builder-shortcodes-card {
        border: 1px solid rgba(83, 110, 255, 0.14);
        box-shadow: 0 18px 45px rgba(6, 18, 56, 0.12);
        border-radius: 1.1rem;
        overflow: hidden;
    }

    .mail-studio-hero {
        background: radial-gradient(circle at top left, rgba(88, 128, 255, 0.22), transparent 38%), linear-gradient(135deg, rgba(13, 18, 32, 0.96), rgba(19, 30, 52, 0.92));
    }

    .mail-studio-eyebrow {
        display: inline-flex;
        padding: .35rem .7rem;
        border-radius: 999px;
        background: rgba(92, 126, 255, 0.14);
        color: #8fb1ff;
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: .85rem;
    }

    .mail-studio-title {
        font-size: 1.9rem;
        line-height: 1.15;
        color: #f5f7ff;
        max-width: 14ch;
    }

    .mail-studio-hero .text-muted {
        color: rgba(226, 232, 255, 0.7) !important;
    }

    .mail-studio-hero__stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
    }

    .mail-studio-stat {
        padding: 1rem;
        border-radius: .95rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.06);
        backdrop-filter: blur(12px);
    }

    .mail-studio-stat__label {
        display: block;
        margin-bottom: .35rem;
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: rgba(226, 232, 255, 0.62);
    }

    .mail-studio-stat__value {
        color: #fff;
        font-size: 1rem;
        font-weight: 700;
    }

    .mail-builder-shortcodes-card {
        background: linear-gradient(135deg, rgba(17, 27, 46, 0.95), rgba(14, 17, 27, 0.96));
    }

    .mail-shortcodes-grid {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
    }

    .mail-shortcode-tag {
        padding: .7rem .9rem;
        border-radius: .8rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(120, 147, 255, 0.24);
        color: #dfe7ff;
        font-size: .82rem;
        cursor: pointer;
        transition: all .2s ease;
    }

    .mail-shortcode-tag:hover {
        transform: translateY(-1px);
        border-color: rgba(151, 176, 255, 0.48);
        background: rgba(92, 126, 255, 0.16);
    }

    .broadcast-users-select {
        min-height: 13rem;
        background: rgba(10, 16, 28, 0.92);
        color: #dfe7ff;
        border: 1px solid rgba(96, 119, 199, 0.2);
    }

    .broadcast-users-select option {
        padding: .45rem .65rem;
    }

    .mail-editor-fallback {
        min-height: 16rem;
        margin-top: 1rem;
        background: linear-gradient(180deg, rgba(14, 22, 36, 0.98), rgba(11, 17, 28, 0.98));
        color: #e6eeff;
        border: 1px solid rgba(96, 119, 199, 0.2);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
    }

    .mail-editor-fallback.is-enhanced {
        display: none;
    }

    .mail-editor-shell {
        position: relative;
        border-radius: 1.15rem;
        overflow: hidden;
        border: 1px solid rgba(96, 119, 199, 0.2);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03), 0 24px 55px rgba(4, 13, 38, 0.28);
        background: radial-gradient(circle at top, rgba(118, 149, 255, 0.1), transparent 34%), linear-gradient(180deg, #101827 0%, #0b1220 100%);
    }

    .mail-editor-shell__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1rem;
        padding: 1rem 1.1rem;
        background: linear-gradient(135deg, rgba(9, 18, 36, 0.96), rgba(27, 43, 78, 0.92));
        border-bottom: 1px solid rgba(96, 119, 199, 0.16);
    }

    .mail-editor-shell__title {
        font-weight: 700;
        color: #f4f7ff;
    }

    .mail-editor-shell__meta {
        font-size: .85rem;
        color: rgba(220, 228, 255, 0.72);
        margin-top: .15rem;
    }

    .mail-editor-shell__canvas-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
        padding: .9rem 1.1rem;
        background: rgba(11, 20, 36, 0.92);
        border-bottom: 1px solid rgba(96, 119, 199, 0.18);
    }

    .mail-editor-shell__pill {
        display: inline-flex;
        align-items: center;
        padding: .45rem .75rem;
        border-radius: 999px;
        border: 1px solid rgba(120, 145, 214, 0.18);
        background: rgba(255, 255, 255, 0.04);
        color: #c9d7f8;
        font-size: .76rem;
        font-weight: 600;
        letter-spacing: .01em;
    }

    .mail-studio-submit-card {
        background: linear-gradient(135deg, rgba(14, 18, 30, 0.98), rgba(18, 31, 56, 0.96));
    }

    #quill_broadcast_editor {
        background: transparent;
    }

    #quill_broadcast_editor .ql-toolbar.ql-snow {
        position: sticky;
        top: 0;
        z-index: 2;
        border: 0;
        border-bottom: 1px solid rgba(96, 119, 199, 0.18);
        padding: .85rem 1rem;
        background: rgba(10, 18, 33, 0.96);
        backdrop-filter: blur(12px);
    }

    #quill_broadcast_editor .ql-toolbar.ql-snow button,
    #quill_broadcast_editor .ql-toolbar.ql-snow .ql-picker {
        color: #dbe7ff;
    }

    #quill_broadcast_editor .ql-toolbar.ql-snow .ql-stroke {
        stroke: #dbe7ff;
    }

    #quill_broadcast_editor .ql-toolbar.ql-snow .ql-fill {
        fill: #dbe7ff;
    }

    #quill_broadcast_editor .ql-toolbar.ql-snow .ql-picker-options {
        background: #162036;
        border-color: rgba(96, 119, 199, 0.24);
        color: #dbe7ff;
    }

    #quill_broadcast_editor .ql-editor {
        min-height: 20rem;
        width: min(100%, 980px);
        max-width: none;
        margin: 0 auto;
        padding: 2rem 2rem 2.5rem;
        font-size: 1rem;
        line-height: 1.8;
        background: linear-gradient(180deg, rgba(14, 22, 36, 0.98), rgba(11, 17, 28, 0.98));
        color: #e6eeff;
        border: 1px solid rgba(96, 119, 199, 0.2);
        border-radius: 1.15rem;
        box-shadow: 0 30px 70px rgba(2, 6, 18, 0.34);
    }

    #quill_broadcast_editor .ql-editor a {
        color: #8eb5ff;
    }

    #quill_broadcast_editor .ql-editor.ql-blank::before {
        left: 2rem;
        right: 2rem;
        color: #7083ab;
        font-style: normal;
    }

    #quill_broadcast_editor .ql-container.ql-snow {
        border: 0;
        padding: 1.25rem clamp(1rem, 3vw, 2rem) 2.25rem;
        background: linear-gradient(90deg, rgba(91, 117, 201, 0.05) 0, rgba(91, 117, 201, 0.05) 1px, transparent 1px, transparent 100%), linear-gradient(180deg, rgba(9, 16, 29, 0.98), rgba(13, 20, 35, 0.92));
    }

    @media (max-width: 991.98px) {
        .mail-studio-hero__stats {
            grid-template-columns: 1fr;
        }

        .mail-editor-shell__header {
            flex-direction: column;
            align-items: flex-start;
        }

        .mail-editor-shell__canvas-meta {
            padding: .8rem .9rem;
        }

        #quill_broadcast_editor .ql-container.ql-snow {
            padding: .85rem .75rem 1.25rem;
        }

        #quill_broadcast_editor .ql-editor {
            width: 100%;
            min-height: 16rem;
            padding: 1.35rem 1rem 1.6rem;
        }

        #quill_broadcast_editor .ql-editor.ql-blank::before {
            left: 1rem;
            right: 1rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/quill.min.js?v=' . PRODUCT_CODE ?>"></script>

<script>
    'use strict';
    const usersSearch = document.querySelector('#users_ids_search');
    const usersSelect = document.querySelector('#users_ids');
    const contentTextarea = document.querySelector('#content');
    let quill = null;

    if(usersSearch && usersSelect) {
        usersSearch.addEventListener('input', event => {
            const search = event.currentTarget.value.trim().toLowerCase();

            Array.from(usersSelect.options).forEach(option => {
                option.hidden = search !== '' && !option.text.toLowerCase().includes(search);
            });
        });

        if(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(usersSelect).select2({
                dir: document.querySelector('html').dir,
                width: '100%',
                placeholder: usersSelect.dataset.placeholder || '',
            });
        }
    }

    if(window.Quill) {
        quill = new Quill('#quill_broadcast_editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ color: [] }, { background: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ align: [] }],
                    ['link', 'blockquote', 'code-block'],
                    ['clean']
                ]
            }
        });

        quill.root.innerHTML = contentTextarea.value;
        contentTextarea.classList.add('is-enhanced');
    }

    /* Handle form submission with the editor */
    document.querySelector('#broadcast_create_form').addEventListener('submit', event => {
        if(quill) {
            document.querySelector('textarea[name="content"]').value = quill.root.innerHTML;
        }
    });
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php ob_start() ?>
<script>
    'use strict';
    
type_handler('[name="segment"]', 'data-segment');
    document.querySelector('[name="segment"]') && document.querySelectorAll('[name="segment"]').forEach(element => element.addEventListener('change', () => { type_handler('[name="segment"]', 'data-segment'); }));
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>


<?php ob_start() ?>
<script>
    'use strict';
    
document.querySelector('#segment').addEventListener('change', async event => {
        await get_segment_count();
    });

    document.querySelectorAll('#filters_is_newsletter_subscribed,[name^="filters_"]').forEach(element => element.addEventListener('change', async event => {
        await get_segment_count();
    }));

    let get_segment_count = async () => {
        let segment = document.querySelector('#segment').value;

        if(segment == 'custom') {
            const selectedCount = Array.from(document.querySelector('#users_ids')?.selectedOptions || []).length;
            document.querySelector('#segment_count').innerHTML = selectedCount ? `(${selectedCount})` : ``;
            return;
        }

        /* Display a loader */
        document.querySelector('#segment_count').innerHTML = `<div class="spinner-border spinner-border-sm" role="status"></div>`;

        /* Prepare query string */
        let query = new URLSearchParams();
        query.set('segment', segment);

        /* Filter preparing on query string */
        if(segment == 'filter') {
            query = new URLSearchParams(new FormData(document.querySelector('#broadcast_create_form')));
        }

        /* Send request to server */
        let response = await fetch(`${url}admin/broadcasts/get_segment_count?${query.toString()}`, {
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

    document.querySelector('#users_ids')?.addEventListener('change', async () => {
        await get_segment_count();
    });

    get_segment_count();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>
