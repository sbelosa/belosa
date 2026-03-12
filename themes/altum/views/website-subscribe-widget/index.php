<?php defined('ALTUMCODE') || die() ?>

<div class="container">
	<?= \Altum\Alerts::output_alerts() ?>

	<?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li>
                    <a href="<?= url('websites') ?>"><?= l('websites.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li>
                    <a href="<?= url('website/' . $data->website->website_id) ?>"><?= l('website.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
                </li>
                <li class="active" aria-current="page"><?= l('website_subscribe_widget.breadcrumb') ?></li>
            </ol>
        </nav>
	<?php endif ?>

    <div class="d-flex justify-content-between mb-4">
        <h1 class="h4 text-truncate mb-0"><i class="fas fa-fw fa-xs fa-pager mr-1"></i> <?= l('website_subscribe_widget.header') ?></h1>

		<?= include_view(THEME_PATH . 'views/websites/website_dropdown_button.php', ['id' => $data->website->website_id, 'resource_name' => $data->website->name, 'host' => $data->website->host, 'path' => $data->website->path, 'pixel_key' => $data->website->pixel_key, 'domain_id' => $data->website->domain_id, 'domains' => $data->domains]) ?>
    </div>

    <div class="card">
        <div class="card-body">

            <form id="website_subscribe_widget" action="" method="post" role="form" enctype="multipart/form-data">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                <div class="form-group">
                    <label for="name"><i class="fas fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('global.name') ?></label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= $data->website->name ?>" readonly="readonly" />
                </div>

                <div class="form-group custom-control custom-switch">
                    <input id="is_enabled" name="is_enabled" type="checkbox" class="custom-control-input" <?= $data->website->widget->is_enabled ? 'checked="checked"' : null?>>
                    <label class="custom-control-label" for="is_enabled"><?= l('website_subscribe_widget.input.is_enabled') ?></label>
                    <small class="form-text text-muted"><?= l('website_subscribe_widget.input.is_enabled_help') ?></small>
                    <small class="form-text text-muted"><?= l('website_subscribe_widget.input.is_enabled_help2') ?></small>
                </div>

                <div class="website-integration-preview">
                    <div class="d-flex align-items-center mb-3 mb-lg-2">
                        <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3"><i class="fas fa-fw fa-sm fa-eye mr-1"></i> <?= l('website_subscribe_widget.preview') ?></h2>

                        <div class="flex-fill d-lg-none">
                            <hr class="border-gray-100" />
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-center mb-3">
						<?= include_view(THEME_PATH . 'views/partials/pixel/widget.php', ['website' => $data->website]) ?>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-3">
                    <h2 class="small font-weight-bold text-uppercase text-muted mb-0 mr-3"><i class="fas fa-fw fa-sm fa-gears mr-1"></i> <?= l('website_subscribe_widget.settings') ?></h2>

                    <div class="flex-fill">
                        <hr class="border-gray-100" />
                    </div>
                </div>

                <button class="btn btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#main_container" aria-expanded="false" aria-controls="main_container">
                    <i class="fas fa-fw fa-user-check fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.subscribe') ?>
                </button>

                <div class="collapse" data-parent="#website_subscribe_widget" id="main_container">
                    <div class="form-group">
                        <label for="title"><i class="fas fa-fw fa-heading fa-sm text-muted mr-1"></i> <?= l('global.title') ?></label>
                        <input type="text" id="title" name="title" class="form-control" value="<?= e($data->website->widget->title) ?>" maxlength="256" required="required" />
                        <small class="form-text text-muted" data-toggle="tooltip" title="<?= l('website_subscribe_widget.html_info_tooltip') ?>"><?= l('website_subscribe_widget.html_info') ?></small>
                        <small class="form-text text-muted"><?= sprintf(l('global.variables'), '<code data-copy>' . implode('</code> , <code data-copy>',  ['{{TOTAL_SUBSCRIBERS}}',]) . '</code>') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="description"><i class="fas fa-fw fa-pen fa-sm text-muted mr-1"></i> <?= l('global.description') ?></label>
                        <input type="text" id="description" name="description" class="form-control" value="<?= e($data->website->widget->description) ?>" maxlength="256" />
                        <small class="form-text text-muted" data-toggle="tooltip" title="<?= l('website_subscribe_widget.html_info_tooltip') ?>"><?= l('website_subscribe_widget.html_info') ?></small>
                        <small class="form-text text-muted"><?= sprintf(l('global.variables'), '<code data-copy>' . implode('</code> , <code data-copy>',  ['{{TOTAL_SUBSCRIBERS}}',]) . '</code>') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="subscribe_button"><i class="fas fa-fw fa-user-check fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.subscribe_button') ?></label>
                        <input type="text" id="subscribe_button" name="subscribe_button" class="form-control" value="<?= e($data->website->widget->subscribe_button) ?>" maxlength="32" required="required" />
                        <small class="form-text text-muted" data-toggle="tooltip" title="<?= l('website_subscribe_widget.html_info_tooltip') ?>"><?= l('website_subscribe_widget.html_info') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="close_button"><i class="fas fa-fw fa-times fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.close_button') ?></label>
                        <input type="text" id="close_button" name="close_button" class="form-control" value="<?= e($data->website->widget->close_button) ?>" maxlength="32" required="required" />
                        <small class="form-text text-muted" data-toggle="tooltip" title="<?= l('website_subscribe_widget.html_info_tooltip') ?>"><?= l('website_subscribe_widget.html_info') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="image_url"><i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.image_url') ?></label>
                        <input type="url" id="image_url" name="image_url" class="form-control" value="<?= e($data->website->widget->image_url) ?>" maxlength="512" />
                    </div>

                    <div class="form-group">
                        <label for="image_alt"><i class="fas fa-fw fa-comment fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.image_alt') ?></label>
                        <input type="text" id="image_alt" name="image_alt" class="form-control" value="<?= e($data->website->widget->image_alt) ?>" maxlength="100" />
                    </div>
                </div>

                <button class="btn btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#successful_subscription_container" aria-expanded="false" aria-controls="successful_subscription_container">
                    <i class="fas fa-fw fa-check-circle fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.successful_subscription') ?>
                </button>

                <div class="collapse" data-parent="#website_subscribe_widget" id="successful_subscription_container">
                    <div class="form-group">
                        <label for="subscribed_title"><i class="fas fa-fw fa-heading fa-sm text-muted mr-1"></i> <?= l('global.title') ?></label>
                        <input type="text" id="subscribed_title" name="subscribed_title" class="form-control" value="<?= e($data->website->widget->subscribed_title) ?>" maxlength="256" required="required" />
                        <small class="form-text text-muted" data-toggle="tooltip" title="<?= l('website_subscribe_widget.html_info_tooltip') ?>"><?= l('website_subscribe_widget.html_info') ?></small>
                        <small class="form-text text-muted"><?= sprintf(l('global.variables'), '<code data-copy>' . implode('</code> , <code data-copy>',  ['{{TOTAL_SUBSCRIBERS}}',]) . '</code>') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="subscribed_description"><i class="fas fa-fw fa-pen fa-sm text-muted mr-1"></i> <?= l('global.description') ?></label>
                        <input type="text" id="subscribed_description" name="subscribed_description" class="form-control" value="<?= e($data->website->widget->subscribed_description) ?>" maxlength="256" />
                        <small class="form-text text-muted" data-toggle="tooltip" title="<?= l('website_subscribe_widget.html_info_tooltip') ?>"><?= l('website_subscribe_widget.html_info') ?></small>
                        <small class="form-text text-muted"><?= sprintf(l('global.variables'), '<code data-copy>' . implode('</code> , <code data-copy>',  ['{{TOTAL_SUBSCRIBERS}}',]) . '</code>') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="subscribed_image_url"><i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.image_url') ?></label>
                        <input type="url" id="subscribed_image_url" name="subscribed_image_url" class="form-control" value="<?= e($data->website->widget->subscribed_image_url) ?>" maxlength="512" />
                    </div>

                    <div class="form-group">
                        <label for="subscribed_image_alt"><i class="fas fa-fw fa-comment fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.image_alt') ?></label>
                        <input type="text" id="subscribed_image_alt" name="subscribed_image_alt" class="form-control" value="<?= e($data->website->widget->subscribed_image_alt) ?>" maxlength="100" />
                    </div>

                    <div class="form-group">
                        <label for="subscribed_success_url"><i class="fas fa-fw fa-link fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.subscribed_success_url') ?></label>
                        <input type="url" id="subscribed_success_url" name="subscribed_success_url" class="form-control" value="<?= e($data->website->widget->subscribed_success_url) ?>" placeholder="<?= l('global.url_placeholder') ?>" maxlength="512" />
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.subscribed_success_url_help') ?></small>
                    </div>
                </div>

                <button class="btn btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#permission_denied_container" aria-expanded="false" aria-controls="permission_denied_container">
                    <i class="fas fa-fw fa-times-circle fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.permission_denied') ?>
                </button>

                <div class="collapse" data-parent="#website_subscribe_widget" id="permission_denied_container">
                    <div class="form-group">
                        <label for="permission_denied_title"><i class="fas fa-fw fa-heading fa-sm text-muted mr-1"></i> <?= l('global.title') ?></label>
                        <input type="text" id="permission_denied_title" name="permission_denied_title" class="form-control" value="<?= e($data->website->widget->permission_denied_title) ?>" maxlength="256" required="required" />
                        <small class="form-text text-muted" data-toggle="tooltip" title="<?= l('website_subscribe_widget.html_info_tooltip') ?>"><?= l('website_subscribe_widget.html_info') ?></small>
                        <small class="form-text text-muted"><?= sprintf(l('global.variables'), '<code data-copy>' . implode('</code> , <code data-copy>',  ['{{TOTAL_SUBSCRIBERS}}',]) . '</code>') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="permission_denied_description"><i class="fas fa-fw fa-pen fa-sm text-muted mr-1"></i> <?= l('global.description') ?></label>
                        <input type="text" id="permission_denied_description" name="permission_denied_description" class="form-control" value="<?= e($data->website->widget->permission_denied_description) ?>" maxlength="256" />
                        <small class="form-text text-muted" data-toggle="tooltip" title="<?= l('website_subscribe_widget.html_info_tooltip') ?>"><?= l('website_subscribe_widget.html_info') ?></small>
                        <small class="form-text text-muted"><?= sprintf(l('global.variables'), '<code data-copy>' . implode('</code> , <code data-copy>',  ['{{TOTAL_SUBSCRIBERS}}',]) . '</code>') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="permission_denied_refresh_button"><i class="fas fa-fw fa-sync-alt fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.permission_denied_refresh_button') ?></label>
                        <input type="text" id="permission_denied_refresh_button" name="permission_denied_refresh_button" class="form-control" value="<?= e($data->website->widget->permission_denied_refresh_button) ?>" maxlength="32" required="required" />
                        <small class="form-text text-muted" data-toggle="tooltip" title="<?= l('website_subscribe_widget.html_info_tooltip') ?>"><?= l('website_subscribe_widget.html_info') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="permission_denied_close_button"><i class="fas fa-fw fa-times fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.close_button') ?></label>
                        <input type="text" id="permission_denied_close_button" name="permission_denied_close_button" class="form-control" value="<?= e($data->website->widget->permission_denied_close_button) ?>" maxlength="32" required="required" />
                        <small class="form-text text-muted" data-toggle="tooltip" title="<?= l('website_subscribe_widget.html_info_tooltip') ?>"><?= l('website_subscribe_widget.html_info') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="permission_denied_image_url"><i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.image_url') ?></label>
                        <input type="url" id="permission_denied_image_url" name="permission_denied_image_url" class="form-control" value="<?= e($data->website->widget->permission_denied_image_url) ?>" maxlength="512" />
                    </div>

                    <div class="form-group">
                        <label for="permission_denied_image_alt"><i class="fas fa-fw fa-comment fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.image_alt') ?></label>
                        <input type="text" id="permission_denied_image_alt" name="permission_denied_image_alt" class="form-control" value="<?= e($data->website->widget->permission_denied_image_alt) ?>" maxlength="100" />
                    </div>
                </div>

                <button class="btn btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#targeting_container" aria-expanded="false" aria-controls="targeting_container">
                    <i class="fas fa-fw fa-bullseye fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.targeting') ?>
                </button>

                <div class="collapse" data-parent="#website_subscribe_widget" id="targeting_container">
                    <div class="form-group">
                        <label for="display_continents"><i class="fas fa-fw fa-sm fa-globe-europe text-muted mr-1"></i> <?= l('global.continents') ?></label>
                        <select id="display_continents" name="display_continents[]" class="custom-select" multiple="multiple">
							<?php foreach(get_continents_array() as $continent_code => $continent_name): ?>
                                <option value="<?= $continent_code ?>" <?= in_array($continent_code, $data->website->widget->display_continents ?? []) ? 'selected="selected"' : null ?>><?= $continent_name ?></option>
							<?php endforeach ?>
                        </select>
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_targeting_help') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="display_countries"><i class="fas fa-fw fa-globe fa-sm text-muted mr-1"></i> <?= l('global.countries') ?></label>
                        <select id="display_countries" name="display_countries[]" class="custom-select" multiple="multiple">
							<?php foreach(get_countries_array() as $country => $country_name): ?>
                                <option value="<?= $country ?>" <?= in_array($country, $data->website->widget->display_countries ?? []) ? 'selected="selected"' : null ?>><?= $country_name ?></option>
							<?php endforeach ?>
                        </select>
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_targeting_help') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="display_operating_systems"><i class="fas fa-fw fa-server fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_operating_systems') ?></label>
                        <select id="display_operating_systems" name="display_operating_systems[]" class="custom-select" multiple="multiple">
							<?php foreach(['iOS', 'Android', 'Windows', 'OS X', 'Linux', 'Ubuntu', 'Chrome OS'] as $os_name): ?>
                                <option value="<?= $os_name ?>" <?= in_array($os_name, $data->website->widget->display_operating_systems ?? []) ? 'selected="selected"' : null ?>><?= $os_name ?></option>
							<?php endforeach ?>
                        </select>
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_targeting_help') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="display_browsers"><i class="fas fa-fw fa-window-restore fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_browsers') ?></label>
                        <select id="display_browsers" name="display_browsers[]" class="custom-select" multiple="multiple">
							<?php foreach(['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Samsung Internet'] as $browser_name): ?>
                                <option value="<?= $browser_name ?>" <?= in_array($browser_name, $data->website->widget->display_browsers ?? []) ? 'selected="selected"' : null ?>><?= $browser_name ?></option>
							<?php endforeach ?>
                        </select>
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_targeting_help') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="display_languages"><i class="fas fa-fw fa-language fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_languages') ?></label>
                        <select id="display_languages" name="display_languages[]" class="custom-select" multiple="multiple">
							<?php foreach(get_locale_languages_array() as $locale => $language): ?>
                                <option value="<?= $locale ?>" <?= in_array($locale, $data->website->widget->display_languages ?? []) ? 'selected="selected"' : null ?>><?= $language ?></option>
							<?php endforeach ?>
                        </select>
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_targeting_help') ?></small>
                    </div>

                    <div class="form-group custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="display_mobile" name="display_mobile" <?= $data->website->widget->display_mobile ? 'checked="checked"' : null ?>>
                        <label class="custom-control-label" for="display_mobile"><i class="fas fa-fw fa-sm fa-mobile text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_mobile') ?></label>
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_mobile_help') ?></small>
                    </div>

                    <div class="form-group custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="display_desktop" name="display_desktop" <?= $data->website->widget->display_desktop ? 'checked="checked"' : null ?>>
                        <label class="custom-control-label" for="display_desktop"><i class="fas fa-fw fa-sm fa-desktop text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_desktop') ?></label>
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_desktop_help') ?></small>
                    </div>
                </div>

                <button class="btn btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#triggers_container" aria-expanded="false" aria-controls="triggers_container">
                    <i class="fas fa-fw fa-angle-up fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.triggers') ?>
                </button>

                <div class="collapse" data-parent="#website_subscribe_widget" id="triggers_container">
                    <div class="form-group custom-control custom-switch">
                        <input
                                type="checkbox"
                                class="custom-control-input"
                                id="trigger_all_pages"
                                name="trigger_all_pages"
							<?= $data->website->widget->trigger_all_pages ? 'checked="checked"' : null ?>
                        >
                        <label class="custom-control-label" for="trigger_all_pages"><?= l('website_subscribe_widget.input.trigger_all_pages') ?></label>

                        <div>
                            <small class="form-text text-muted"><?= l('website_subscribe_widget.input.trigger_all_pages_help') ?></small>
                        </div>
                    </div>

                    <div id="triggers" class="container-disabled">
						<?php if (!empty($data->website->widget->triggers ?? [])): ?>
							<?php foreach($data->website->widget->triggers ?? [] as $trigger): ?>
                                <div class="form-row">
                                    <div class="form-group col-lg-4">
                                        <select class="form-control" name="trigger_type[]" data-is-not-custom-select>
                                            <option value="exact" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_exact_placeholder') ?>" <?= $trigger->type == 'exact' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.trigger_type_exact') ?></option>
                                            <option value="not_exact" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_not_exact_placeholder') ?>" <?= $trigger->type == 'not_exact' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.trigger_type_not_exact') ?></option>
                                            <option value="contains" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_contains_placeholder') ?>" <?= $trigger->type == 'contains' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.trigger_type_contains') ?></option>
                                            <option value="not_contains" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_not_contains_placeholder') ?>" <?= $trigger->type == 'not_contains' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.trigger_type_not_contains') ?></option>
                                            <option value="starts_with" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_starts_with_placeholder') ?>" <?= $trigger->type == 'starts_with' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.trigger_type_starts_with') ?></option>
                                            <option value="not_starts_with" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_not_starts_with_placeholder') ?>" <?= $trigger->type == 'not_starts_with' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.trigger_type_not_starts_with') ?></option>
                                            <option value="ends_with" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_ends_with_placeholder') ?>" <?= $trigger->type == 'ends_with' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.trigger_type_ends_with') ?></option>
                                            <option value="not_ends_with" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_not_ends_with_placeholder') ?>" <?= $trigger->type == 'not_ends_with' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.trigger_type_not_ends_with') ?></option>
                                            <option value="page_contains" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_page_contains_placeholder') ?>" <?= $trigger->type == 'page_contains' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.trigger_type_page_contains') ?></option>
                                        </select>
                                    </div>

                                    <div class="form-group col-lg-6">
                                        <input type="text" name="trigger_value[]" class="form-control" value="<?= $trigger->value ?>">
                                    </div>

                                    <div class="form-group col-lg-2">
                                        <button type="button" class="trigger-delete btn btn-block btn-outline-danger" title="<?= l('global.delete') ?>"><i class="fas fa-fw fa-times"></i></button>
                                    </div>
                                </div>
							<?php endforeach ?>
						<?php endif ?>
                    </div>

                    <button type="button" id="trigger_add" class="btn btn-outline-success btn-sm mb-4"><i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.trigger_add') ?></button>

                    <div class="form-group">
                        <label for="settings_display_trigger"><i class="fas fa-fw fa-bolt fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger') ?></label>
                        <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_trigger == 'delay' ? 'active"' : null?>">
                                    <input type="radio" name="display_trigger" value="delay" class="custom-control-input" <?= $data->website->widget->display_trigger == 'delay' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-hourglass-start fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_delay') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_trigger == 'time_on_site' ? 'active"' : null?>">
                                    <input type="radio" name="display_trigger" value="time_on_site" class="custom-control-input" <?= $data->website->widget->display_trigger == 'time_on_site' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-user-clock fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_time_on_site') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_trigger == 'inactivity' ? 'active"' : null?>">
                                    <input type="radio" name="display_trigger" value="inactivity" class="custom-control-input" <?= $data->website->widget->display_trigger == 'inactivity' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-pause-circle fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_inactivity') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_trigger == 'pageviews' ? 'active"' : null?>">
                                    <input type="radio" name="display_trigger" value="pageviews" class="custom-control-input" <?= $data->website->widget->display_trigger == 'pageviews' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-eye fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_pageviews') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_trigger == 'exit_intent' ? 'active"' : null?>">
                                    <input type="radio" name="display_trigger" value="exit_intent" class="custom-control-input" <?= $data->website->widget->display_trigger == 'exit_intent' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-door-open fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_exit_intent') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_trigger == 'scroll' ? 'active"' : null?>">
                                    <input type="radio" name="display_trigger" value="scroll" class="custom-control-input" <?= $data->website->widget->display_trigger == 'scroll' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-scroll fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_scroll') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_trigger == 'click' ? 'active"' : null?>">
                                    <input type="radio" name="display_trigger" value="click" class="custom-control-input" <?= $data->website->widget->display_trigger == 'click' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-mouse fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_click') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_trigger == 'hover' ? 'active"' : null?>">
                                    <input type="radio" name="display_trigger" value="hover" class="custom-control-input" <?= $data->website->widget->display_trigger == 'hover' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-mouse-pointer fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_hover') ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" data-display-trigger="delay">
                        <label for="display_trigger_value_delay"><i class="fas fa-fw fa-hourglass-start fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_delay') ?></label>
                        <div class="input-group">
                            <input id="display_trigger_value_delay" type="number" min="0" name="display_trigger_value" class="form-control" value="<?= $data->website->widget->display_trigger_value ?>" />
                            <div class="input-group-append">
                                <span class="input-group-text"><?= l('global.date.seconds') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" data-display-trigger="time_on_site">
                        <label for="display_trigger_value_time_on_site"><i class="fas fa-fw fa-user-clock fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_time_on_site') ?></label>
                        <div class="input-group">
                            <input id="display_trigger_value_time_on_site" type="number" min="0" name="display_trigger_value" class="form-control" value="<?= $data->website->widget->display_trigger_value ?>" />
                            <div class="input-group-append">
                                <span class="input-group-text"><?= l('global.date.seconds') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" data-display-trigger="inactivity">
                        <label for="display_trigger_value_inactivity"><i class="fas fa-fw fa-pause-circle fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_inactivity') ?></label>
                        <div class="input-group">
                            <input id="display_trigger_value_inactivity" type="number" min="0" name="display_trigger_value" class="form-control" value="<?= $data->website->widget->display_trigger_value ?>" />
                            <div class="input-group-append">
                                <span class="input-group-text"><?= l('global.date.seconds') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" data-display-trigger="pageviews">
                        <label for="display_trigger_value_pageviews"><i class="fas fa-fw fa-eye fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_pageviews') ?></label>
                        <div class="input-group">
                            <input id="display_trigger_value_pageviews" type="number" min="1" name="display_trigger_value" class="form-control" value="<?= $data->website->widget->display_trigger_value ?>" />
                        </div>
                    </div>

                    <div class="form-group" data-display-trigger="scroll">
                        <label for="display_trigger_value_scroll"><i class="fas fa-fw fa-scroll fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_scroll') ?></label>
                        <div class="input-group">
                            <input id="display_trigger_value_scroll" type="number" min="0" max="100" name="display_trigger_value" class="form-control" value="<?= $data->website->widget->display_trigger_value ?>" />
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" data-display-trigger="click">
                        <label for="display_trigger_value_click"><i class="fas fa-fw fa-mouse fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_click') ?></label>
                        <input id="display_trigger_value_click" type="text" name="display_trigger_value" class="form-control" value="<?= $data->website->widget->display_trigger_value ?>" />
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_trigger_click_help') ?></small>
                    </div>

                    <div class="form-group" data-display-trigger="hover">
                        <label for="display_trigger_value_hover"><i class="fas fa-fw fa-mouse-pointer fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_trigger_hover') ?></label>
                        <input id="display_trigger_value_hover" type="text" name="display_trigger_value" class="form-control" value="<?= $data->website->widget->display_trigger_value ?>" />
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_trigger_hover_help') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="settings_display_frequency"><i class="fas fa-fw fa-th fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_frequency') ?></label>
                        <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_frequency == 'all_time' ? 'active"' : null?>">
                                    <input type="radio" name="display_frequency" value="all_time" class="custom-control-input" <?= $data->website->widget->display_frequency == 'all_time' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-history fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_frequency_all_time') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_frequency == 'once_per_session' ? 'active"' : null?>">
                                    <input type="radio" name="display_frequency" value="once_per_session" class="custom-control-input" <?= $data->website->widget->display_frequency == 'once_per_session' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-stopwatch fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_frequency_once_per_session') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_frequency == 'once_per_browser' ? 'active"' : null?>">
                                    <input type="radio" name="display_frequency" value="once_per_browser" class="custom-control-input" <?= $data->website->widget->display_frequency == 'once_per_browser' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-window-maximize fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_frequency_once_per_browser') ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="settings_display_delay_type_after_close"><i class="fas fa-fw fa-calendar-check fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_delay_type_after_close') ?></label>
                        <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_delay_type_after_close == 'time_on_site' ? 'active"' : null?>">
                                    <input type="radio" name="display_delay_type_after_close" value="time_on_site" class="custom-control-input" <?= $data->website->widget->display_delay_type_after_close == 'time_on_site' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-user-clock fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_delay_type_after_close_time_on_site') ?>
                                </label>
                            </div>

                            <div class="p-2 col-12 col-lg-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_delay_type_after_close == 'pageviews' ? 'active"' : null?>">
                                    <input type="radio" name="display_delay_type_after_close" value="pageviews" class="custom-control-input" <?= $data->website->widget->display_delay_type_after_close == 'pageviews' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-eye fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display_delay_type_after_close_pageviews') ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" data-display-delay-type-after-close="time_on_site">
                        <label for="display_delay_value_after_close_time_on_site"><i class="fas fa-fw fa-user-clock fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_delay_type_after_close_time_on_site') ?></label>
                        <div class="input-group">
                            <input id="display_delay_value_after_close_time_on_site" type="number" min="0" name="display_delay_value_after_close" class="form-control" value="<?= $data->website->widget->display_delay_value_after_close ?>" />
                            <div class="input-group-append">
                                <span class="input-group-text"><?= l('global.date.seconds') ?></span>
                            </div>
                        </div>
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_delay_type_after_close_help') ?></small>
                    </div>

                    <div class="form-group" data-display-delay-type-after-close="pageviews">
                        <label for="display_delay_value_after_close_pageviews"><i class="fas fa-fw fa-eye fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_delay_type_after_close_pageviews') ?></label>
                        <div class="input-group">
                            <input id="display_delay_value_after_close_pageviews" type="number" min="1" name="display_delay_value_after_close" class="form-control" value="<?= $data->website->widget->display_delay_value_after_close ?>" />
                        </div>
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_delay_type_after_close_help') ?></small>
                    </div>
                </div>

                <button class="btn btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#display_container" aria-expanded="false" aria-controls="display_container">
                    <i class="fas fa-fw fa-sliders-h fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.display') ?>
                </button>

                <div class="collapse" data-parent="#website_subscribe_widget" id="display_container">
                    <div class="form-group">
                        <label for="settings_direction"><i class="fas fa-fw fa-map-signs fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.direction') ?></label>
                        <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                            <div class="p-2 col-6">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= ($data->website->widget->direction  ?? null) == 'ltr' ? 'active"' : null?>">
                                    <input type="radio" name="direction" value="ltr" class="custom-control-input" <?= ($data->website->widget->direction  ?? null) == 'ltr' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-long-arrow-alt-right fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.direction_ltr') ?>
                                </label>
                            </div>
                            <div class="p-2 col-6">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= ($data->website->widget->direction  ?? null) == 'rtl' ? 'active' : null?>">
                                    <input type="radio" name="direction" value="rtl" class="custom-control-input" <?= ($data->website->widget->direction  ?? null) == 'rtl' ? 'checked="checked"' : null?> />
                                    <i class="fas fa-fw fa-long-arrow-alt-left fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.direction_rtl') ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="settings_display_position"><i class="fas fa-fw fa-th fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_position') ?></label>
                        <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                            <div class="p-2 col-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_position == 'top_left' ? 'active"' : null?>">
                                    <input type="radio" name="display_position" value="top_left" class="custom-control-input" <?= $data->website->widget->display_position == 'top_left' ? 'checked="checked"' : null?> />
									<?= l('website_subscribe_widget.input.display_position_top_left') ?>
                                </label>
                            </div>

                            <div class="p-2 col-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_position == 'top_center' ? 'active"' : null?>">
                                    <input type="radio" name="display_position" value="top_center" class="custom-control-input" <?= $data->website->widget->display_position == 'top_center' ? 'checked="checked"' : null?> />
									<?= l('website_subscribe_widget.input.display_position_top_center') ?>
                                </label>
                            </div>

                            <div class="p-2 col-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_position == 'top_right' ? 'active"' : null?>">
                                    <input type="radio" name="display_position" value="top_right" class="custom-control-input" <?= $data->website->widget->display_position == 'top_right' ? 'checked="checked"' : null?> />
									<?= l('website_subscribe_widget.input.display_position_top_right') ?>
                                </label>
                            </div>

                            <div class="p-2 col-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_position == 'middle_left' ? 'active"' : null?>">
                                    <input type="radio" name="display_position" value="middle_left" class="custom-control-input" <?= $data->website->widget->display_position == 'middle_left' ? 'checked="checked"' : null?> />
									<?= l('website_subscribe_widget.input.display_position_middle_left') ?>
                                </label>
                            </div>

                            <div class="p-2 col-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_position == 'middle_center' ? 'active"' : null?>">
                                    <input type="radio" name="display_position" value="middle_center" class="custom-control-input" <?= $data->website->widget->display_position == 'middle_center' ? 'checked="checked"' : null?> />
									<?= l('website_subscribe_widget.input.display_position_middle_center') ?>
                                </label>
                            </div>

                            <div class="p-2 col-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_position == 'middle_right' ? 'active"' : null?>">
                                    <input type="radio" name="display_position" value="middle_right" class="custom-control-input" <?= $data->website->widget->display_position == 'middle_right' ? 'checked="checked"' : null?> />
									<?= l('website_subscribe_widget.input.display_position_middle_right') ?>
                                </label>
                            </div>

                            <div class="p-2 col-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_position == 'bottom_left' ? 'active"' : null?>">
                                    <input type="radio" name="display_position" value="bottom_left" class="custom-control-input" <?= $data->website->widget->display_position == 'bottom_left' ? 'checked="checked"' : null?> />
									<?= l('website_subscribe_widget.input.display_position_bottom_left') ?>
                                </label>
                            </div>

                            <div class="p-2 col-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_position == 'bottom_center' ? 'active"' : null?>">
                                    <input type="radio" name="display_position" value="bottom_center" class="custom-control-input" <?= $data->website->widget->display_position == 'bottom_center' ? 'checked="checked"' : null?> />
									<?= l('website_subscribe_widget.input.display_position_bottom_center') ?>
                                </label>
                            </div>

                            <div class="p-2 col-4">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= $data->website->widget->display_position == 'bottom_right' ? 'active"' : null?>">
                                    <input type="radio" name="display_position" value="bottom_right" class="custom-control-input" <?= $data->website->widget->display_position == 'bottom_right' ? 'checked="checked"' : null?> />
									<?= l('website_subscribe_widget.input.display_position_bottom_right') ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="settings_display_duration"><i class="fas fa-fw fa-hourglass-start fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.display_duration') ?></label>
                        <div class="input-group">
                            <input type="number" min="-1" id="settings_display_duration" name="display_duration" class="form-control" value="<?= $data->website->widget->display_duration ?>" required="required" />
                            <div class="input-group-append">
                                <span class="input-group-text"><?= l('global.date.seconds') ?></span>
                            </div>
                        </div>
                        <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_duration_help') ?></small>
                    </div>

                    <div <?= $this->user->plan_settings->removable_branding_is_enabled ? null : get_plan_feature_disabled_info() ?>>
                        <div class="form-group custom-control custom-switch <?= !$this->user->plan_settings->removable_branding_is_enabled ? 'container-disabled': null ?>">
                            <input
                                    type="checkbox"
                                    class="custom-control-input"
                                    id="display_branding"
                                    name="display_branding"
								<?= $data->website->widget->display_branding ? 'checked="checked"' : null ?>
								<?= !$this->user->plan_settings->removable_branding_is_enabled ? 'disabled="disabled"' : null ?>
                            >
                            <label class="custom-control-label" for="display_branding"><?= l('website_subscribe_widget.input.display_branding') ?></label>
                        </div>
                    </div>
                </div>

                <button class="btn btn-block btn-light my-3" type="button" data-toggle="collapse" data-target="#customize_container" aria-expanded="false" aria-controls="customize_container">
                    <i class="fas fa-fw fa-paint-brush fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.customize') ?>
                </button>

                <div class="collapse" data-parent="#customize_container" id="customize_container">
                    <div class="form-group">
                        <label for="font"><i class="fas fa-fw fa-pen-nib fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.font') ?></label>
                        <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                            <div class="p-2 col-6 col-lg-4 h-100">
                                <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= ($data->website->widget->font ?? 'inherit') == 'inherit' ? 'active"' : null?>">
                                    <input type="radio" name="font" value="inherit" class="custom-control-input" <?= ($data->website->widget->font ?? 'inherit') == 'inherit' ? 'checked="checked"' : null?> required="required" />
									<?= l('website_subscribe_widget.input.font_inherit') ?>
                                </label>
                            </div>

							<?php foreach(['Arial', 'Verdana', 'Helvetica', 'Tahoma', 'Trebuchet MS', 'Times New Roman', 'Georgia', 'Courier New', 'Monaco', 'Comic Sans MS', 'Courier', 'Impact', 'Futura', 'Luminari', 'Baskerville', 'Papyrus', 'Brush Script MT'] as $font): ?>
                                <div class="p-2 col-6 col-lg-4 h-100">
                                    <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= ($data->website->widget->font ?? 'inherit') == $font ? 'active"' : null?>" style="font-family: <?= $font ?> !important;">
                                        <input type="radio" name="font" value="<?= $font ?>" class="custom-control-input" <?= ($data->website->widget->font ?? 'inherit') == $font ? 'checked="checked"' : null?> required="required" />
										<?= $font ?>
                                    </label>
                                </div>
							<?php endforeach ?>
                        </div>
                    </div>

                    <div class="form-group" data-range-counter data-range-counter-suffix="px">
                        <label for="internal_padding"><i class="fas fa-fw fa-expand-arrows-alt fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.internal_padding') ?></label>
                        <input type="range" min="5" max="25" id="internal_padding" name="internal_padding" class="form-control-range" value="<?= $data->website->widget->internal_padding ?>" />
                    </div>

                    <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 font-size-small font-weight-500 my-4" type="button" data-toggle="collapse" data-target="#colors_container" aria-expanded="false" aria-controls="colors_container">
                        <i class="fas fa-fw fa-fill fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.colors') ?>
                    </button>

                    <div class="collapse" data-parent="#customize_container" id="colors_container">
                        <div class="form-group">
                            <label for="title_color"><?= l('website_subscribe_widget.input.title_color') ?></label>
                            <input type="hidden" id="title_color" name="title_color" class="form-control" value="<?= $data->website->widget->title_color ?>" />
                            <div id="title_color_pickr"></div>
                        </div>

                        <div class="form-group">
                            <label for="description_color"><?= l('website_subscribe_widget.input.description_color') ?></label>
                            <input type="hidden" id="description_color" name="description_color" class="form-control" value="<?= $data->website->widget->description_color ?>" />
                            <div id="description_color_pickr"></div>
                        </div>

                        <div class="form-group">
                            <label for="background_color"><?= l('website_subscribe_widget.input.background_color') ?></label>
                            <input type="hidden" id="background_color" name="background_color" class="form-control" value="<?= $data->website->widget->background_color ?>" />
                            <div id="background_color_pickr"></div>
                        </div>

                        <div class="form-group">
                            <label for="subscribe_button_text_color"><?= l('website_subscribe_widget.input.subscribe_button_text_color') ?></label>
                            <input type="hidden" id="subscribe_button_text_color" name="subscribe_button_text_color" class="form-control" value="<?= $data->website->widget->subscribe_button_text_color ?>" />
                            <div id="subscribe_button_text_color_pickr"></div>
                        </div>

                        <div class="form-group">
                            <label for="subscribe_button_background_color"><?= l('website_subscribe_widget.input.subscribe_button_background_color') ?></label>
                            <input type="hidden" id="subscribe_button_background_color" name="subscribe_button_background_color" class="form-control" value="<?= $data->website->widget->subscribe_button_background_color ?>" />
                            <div id="subscribe_button_background_color_pickr"></div>
                        </div>

                        <div class="form-group">
                            <label for="close_button_text_color"><?= l('website_subscribe_widget.input.close_button_text_color') ?></label>
                            <input type="hidden" id="close_button_text_color" name="close_button_text_color" class="form-control" value="<?= $data->website->widget->close_button_text_color ?>" />
                            <div id="close_button_text_color_pickr"></div>
                        </div>

                        <div class="form-group">
                            <label for="close_button_background_color"><?= l('website_subscribe_widget.input.close_button_background_color') ?></label>
                            <input type="hidden" id="close_button_background_color" name="close_button_background_color" class="form-control" value="<?= $data->website->widget->close_button_background_color ?>" />
                            <div id="close_button_background_color_pickr"></div>
                        </div>
                    </div>

                    <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 font-size-small font-weight-500 my-4" type="button" data-toggle="collapse" data-target="#borders_container" aria-expanded="false" aria-controls="borders_container">
                        <i class="fas fa-fw fa-border-style fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.borders') ?>
                    </button>

                    <div class="collapse" data-parent="#customize_container" id="borders_container">
                        <div class="form-group custom-control custom-switch">
                            <input
                                    type="checkbox"
                                    class="custom-control-input"
                                    id="display_shadow"
                                    name="display_shadow"
								<?= $data->website->widget->display_shadow ? 'checked="checked"' : null ?>
                            >

                            <label class="custom-control-label" for="display_shadow"><?= l('website_subscribe_widget.input.display_shadow') ?></label>

                            <div>
                                <small class="form-text text-muted"><?= l('website_subscribe_widget.input.display_shadow_help') ?></small>
                            </div>
                        </div>

                        <div class="form-group" data-range-counter data-range-counter-suffix="px">
                            <label for="border_width"><i class="fas fa-fw fa-border-top-left fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.border_width') ?></label>
                            <input type="range" min="0" max="5" id="border_width" name="border_width" class="form-control-range" value="<?= $data->website->widget->border_width ?>" />
                        </div>

                        <div class="form-group">
                            <label for="border_color"><i class="fas fa-fw fa-fill fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.border_color') ?></label>
                            <input type="hidden" id="border_color" name="border_color" class="form-control border-left-0" value="<?= $data->website->widget->border_color ?>" />
                            <div id="border_color_pickr"></div>
                        </div>

                        <div class="form-group">
                            <label for="border_radius"><i class="fas fa-fw fa-border-all fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.border_radius') ?></label>
                            <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                                <div class="p-2 col-4">
                                    <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= ($data->website->widget->border_radius  ?? null) == 'straight' ? 'active"' : null?>">
                                        <input type="radio" name="border_radius" value="straight" class="custom-control-input" <?= ($data->website->widget->border_radius  ?? null) == 'straight' ? 'checked="checked"' : null?> />
                                        <i class="fas fa-fw fa-square-full fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.border_radius_straight') ?>
                                    </label>
                                </div>
                                <div class="p-2 col-4">
                                    <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= ($data->website->widget->border_radius  ?? null) == 'rounded' ? 'active' : null?>">
                                        <input type="radio" name="border_radius" value="rounded" class="custom-control-input" <?= ($data->website->widget->border_radius  ?? null) == 'rounded' ? 'checked="checked"' : null?> />
                                        <i class="fas fa-fw fa-square fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.border_radius_rounded') ?>
                                    </label>
                                </div>
                                <div class="p-2 col-4">
                                    <label class="btn btn-gray-200 btn-block font-size-small mb-0 text-truncate <?= ($data->website->widget->border_radius  ?? null) == 'highly_rounded' ? 'active' : null?>">
                                        <input type="radio" name="border_radius" value="highly_rounded" class="custom-control-input" <?= ($data->website->widget->border_radius  ?? null) == 'highly_rounded' ? 'checked="checked"' : null?> />
                                        <i class="fas fa-fw fa-square fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.border_radius_highly_rounded') ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 font-size-small font-weight-500 my-4" type="button" data-toggle="collapse" data-target="#animations_container" aria-expanded="false" aria-controls="animations_container">
                        <i class="fas fa-fw fa-running fa-sm mr-1"></i> <?= l('website_subscribe_widget.input.animations') ?>
                    </button>

                    <div class="collapse" data-parent="#customize_container" id="animations_container">
                        <div class="form-group">
                            <label for="hover_animation"><i class="fas fa-fw fa-mouse-pointer fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.hover_animation') ?></label>
                            <select id="hover_animation" class="custom-select" name="hover_animation">
                                <option value=" " <?= $data->website->widget->hover_animation == '' ? 'selected="selected"' : null ?>><?= l('global.none') ?></option>
                                <option value="fast_scale_up" <?= $data->website->widget->hover_animation == 'fast_scale_up' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.hover_animation_fast_scale_up') ?></option>
                                <option value="slow_scale_up" <?= $data->website->widget->hover_animation == 'slow_scale_up' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.hover_animation_slow_scale_up') ?></option>
                                <option value="fast_scale_down" <?= $data->website->widget->hover_animation == 'fast_scale_down' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.hover_animation_fast_scale_down') ?></option>
                                <option value="slow_scale_down" <?= $data->website->widget->hover_animation == 'slow_scale_down' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.hover_animation_slow_scale_down') ?></option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="on_animation"><i class="fas fa-fw fa-sign-in-alt fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.on_animation') ?></label>
                                    <select id="on_animation" class="custom-select" name="on_animation">
                                        <option value="fadeIn" <?= $data->website->widget->on_animation == 'fadeIn' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.on_animation_fadeIn') ?></option>
                                        <option value="slideInUp" <?= $data->website->widget->on_animation == 'slideInUp' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.on_animation_slideInUp') ?></option>
                                        <option value="slideInDown" <?= $data->website->widget->on_animation == 'slideInDown' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.on_animation_slideInDown') ?></option>
                                        <option value="zoomIn" <?= $data->website->widget->on_animation == 'zoomIn' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.on_animation_zoomIn') ?></option>
                                        <option value="bounceIn" <?= $data->website->widget->on_animation == 'bounceIn' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.on_animation_bounceIn') ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="off_animation"><i class="fas fa-fw fa-sign-out-alt fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.off_animation') ?></label>
                                    <select id="off_animation" class="custom-select" name="off_animation">
                                        <option value="fadeOut" <?= $data->website->widget->off_animation == 'fadeOut' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.off_animation_fadeOut') ?></option>
                                        <option value="slideOutUp" <?= $data->website->widget->off_animation == 'slideOutUp' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.off_animation_slideOutUp') ?></option>
                                        <option value="slideOutDown" <?= $data->website->widget->off_animation == 'slideOutDown' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.off_animation_slideOutDown') ?></option>
                                        <option value="zoomOut" <?= $data->website->widget->off_animation == 'zoomOut' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.off_animation_zoomOut') ?></option>
                                        <option value="bounceOut" <?= $data->website->widget->off_animation == 'bounceOut' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.off_animation_bounceOut') ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="animation"><i class="fas fa-fw fa-film fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.animation') ?></label>
                            <select id="animation" class="custom-select" name="animation">
                                <option value=" " <?= $data->website->widget->animation == '' ? 'selected="selected"' : null ?>><?= l('global.none') ?></option>
                                <option value="heartbeat" <?= $data->website->widget->animation == 'heartbeat' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.animation_heartbeat') ?></option>
                                <option value="bounce" <?= $data->website->widget->animation == 'bounce' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.animation_bounce') ?></option>
                                <option value="flash" <?= $data->website->widget->animation == 'flash' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.animation_flash') ?></option>
                                <option value="pulse" <?= $data->website->widget->animation == 'pulse' ? 'selected="selected"' : null ?>><?= l('website_subscribe_widget.input.animation_pulse') ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="animation_interval"><i class="fas fa-fw fa-history fa-sm text-muted mr-1"></i> <?= l('website_subscribe_widget.input.animation_interval') ?></label>
                            <div class="input-group">
                                <input type="number" min="3" id="animation_interval" name="animation_interval" class="form-control" value="<?= $data->website->widget->animation_interval ?? 3 ?>" />
                                <div class="input-group-append">
                                    <span class="input-group-text"><?= l('global.date.seconds') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn btn-block btn-primary"><?= l('global.update') ?></button>
            </form>

        </div>
    </div>
</div>

<div style="display:none" id="trigger_rule_sample">
    <div class="form-row">
        <div class="form-group col-lg-4">
            <select class="form-control" name="trigger_type[]" data-is-not-custom-select>
                <option value="exact" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_exact_placeholder') ?>"><?= l('website_subscribe_widget.input.trigger_type_exact') ?></option>
                <option value="not_exact" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_not_exact_placeholder') ?>"><?= l('website_subscribe_widget.input.trigger_type_not_exact') ?></option>
                <option value="contains" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_contains_placeholder') ?>"><?= l('website_subscribe_widget.input.trigger_type_contains') ?></option>
                <option value="not_contains" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_not_contains_placeholder') ?>"><?= l('website_subscribe_widget.input.trigger_type_not_contains') ?></option>
                <option value="starts_with" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_starts_with_placeholder') ?>"><?= l('website_subscribe_widget.input.trigger_type_starts_with') ?></option>
                <option value="not_starts_with" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_not_starts_with_placeholder') ?>"><?= l('website_subscribe_widget.input.trigger_type_not_starts_with') ?></option>
                <option value="ends_with" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_ends_with_placeholder') ?>"><?= l('website_subscribe_widget.input.trigger_type_ends_with') ?></option>
                <option value="not_ends_with" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_not_ends_with_placeholder') ?>"><?= l('website_subscribe_widget.input.trigger_type_not_ends_with') ?></option>
                <option value="page_contains" data-placeholder="<?= l('website_subscribe_widget.input.trigger_type_page_contains_placeholder') ?>"><?= l('website_subscribe_widget.input.trigger_type_page_contains') ?></option>
            </select>
        </div>

        <div class="form-group col-lg-6">
            <input type="text" name="trigger_value[]" class="form-control" value="">
        </div>

        <div class="form-group col-lg-2">
            <button type="button" class="trigger-delete btn btn-block btn-outline-danger" title="<?= l('global.delete') ?>"><i class="fas fa-fw fa-times"></i></button>
        </div>
    </div>
</div>

<?php ob_start() ?>
<link href="<?= ASSETS_FULL_URL . 'css/pixel-widget.min.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
<link href="<?= ASSETS_FULL_URL . 'css/libraries/pickr.min.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen">
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/pickr.min.js?v=' . PRODUCT_CODE ?>"></script>

<script>
    'use strict';

    /* Preview processing */

    /* Main */
    document.querySelector('#title').addEventListener('change', event => {
        document.querySelector('[data-title]').innerText = document.querySelector('#title').value;
    });

    document.querySelector('#description').addEventListener('change', event => {
        document.querySelector('[data-description]').innerText = document.querySelector('#description').value;
    });

    document.querySelector('#subscribe_button').addEventListener('change', event => {
        document.querySelector('[data-subscribe]').innerText = document.querySelector('#subscribe_button').value;
    });

    document.querySelector('#close_button').addEventListener('change', event => {
        document.querySelector('[data-close]').innerText = document.querySelector('#close_button').value;
    });

    document.querySelector('#image_url').addEventListener('change', event => {
        let value = document.querySelector('#image_url').value;

        if(value) {
            document.querySelector('[data-image]').src = document.querySelector('#image_url').value;
            document.querySelector('[data-image]').style.display = 'block';
        } else {
            document.querySelector('[data-image]').src = document.querySelector('#image_url').value;
            document.querySelector('[data-image]').style.display = 'none';
        }
    });

    /* Customize */
    document.querySelectorAll('input[name="font"]').forEach(element => element.addEventListener('change', event => {
        document.querySelector('[data-wrapper]').style.fontFamily = document.querySelector('input[name="font"]:checked').value;
    }));

    /* Initiate the color picker */
    let pickr_options = {
        comparison: false,

        components: {
            preview: true,
            opacity: true,
            hue: true,
            comparison: false,
            interaction: {
                hex: true,
                rgba: false,
                hsla: false,
                hsva: false,
                cmyk: false,
                input: true,
                clear: false,
                save: false,
            }
        }
    };

    /* Title color handler */
    let title_color_pickr = Pickr.create({
        el: '#title_color_pickr',
        default: document.querySelector('#title_color').value,
        ...pickr_options
    });

    title_color_pickr.on('change', hsva => {
        document.querySelector('#title_color').value = hsva.toHEXA().toString();
        document.querySelector('[data-title]').style.color = hsva.toHEXA().toString();
    });

    /* Description color handler */
    let description_color_pickr = Pickr.create({
        el: '#description_color_pickr',
        default: document.querySelector('#description_color').value,
        ...pickr_options
    });

    description_color_pickr.on('change', hsva => {
        document.querySelector('#description_color').value = hsva.toHEXA().toString();
        document.querySelector('[data-description]').style.color = hsva.toHEXA().toString();
    });

    /* Background color handler */
    let background_color_pickr = Pickr.create({
        el: '#background_color_pickr',
        default: document.querySelector('#background_color').value,
        ...pickr_options
    });

    background_color_pickr.on('change', hsva => {
        document.querySelector('#background_color').value = hsva.toHEXA().toString();
        document.querySelector('[data-wrapper]').style.backgroundColor = hsva.toHEXA().toString();
    });

    /* Subscribe button text color handler */
    let subscribe_button_text_color_pickr = Pickr.create({
        el: '#subscribe_button_text_color_pickr',
        default: document.querySelector('#subscribe_button_text_color').value,
        ...pickr_options
    });

    subscribe_button_text_color_pickr.on('change', hsva => {
        document.querySelector('#subscribe_button_text_color').value = hsva.toHEXA().toString();
        document.querySelector('[data-subscribe]').style.color = hsva.toHEXA().toString();
    });

    /* Subscribe button background color handler */
    let subscribe_button_background_color_pickr = Pickr.create({
        el: '#subscribe_button_background_color_pickr',
        default: document.querySelector('#subscribe_button_background_color').value,
        ...pickr_options
    });

    subscribe_button_background_color_pickr.on('change', hsva => {
        document.querySelector('#subscribe_button_background_color').value = hsva.toHEXA().toString();
        document.querySelector('[data-subscribe]').style.backgroundColor = hsva.toHEXA().toString();
    });

    /* Subscribe button text color handler */
    let close_button_text_color_pickr = Pickr.create({
        el: '#close_button_text_color_pickr',
        default: document.querySelector('#close_button_text_color').value,
        ...pickr_options
    });

    close_button_text_color_pickr.on('change', hsva => {
        document.querySelector('#close_button_text_color').value = hsva.toHEXA().toString();
        document.querySelector('[data-close]').style.color = hsva.toHEXA().toString();
    });

    /* Subscribe button background color handler */
    let close_button_background_color_pickr = Pickr.create({
        el: '#close_button_background_color_pickr',
        default: document.querySelector('#close_button_background_color').value,
        ...pickr_options
    });

    close_button_background_color_pickr.on('change', hsva => {
        document.querySelector('#close_button_background_color').value = hsva.toHEXA().toString();
        document.querySelector('[data-close]').style.backgroundColor = hsva.toHEXA().toString();
    });

    /* Border color handler */
    let border_color_pickr = Pickr.create({
        el: '#border_color_pickr',
        default: document.querySelector('#border_color').value,
        ...pickr_options
    });

    border_color_pickr.on('change', hsva => {
        document.querySelector('#border_color').value = hsva.toHEXA().toString();
        document.querySelector('[data-wrapper]').style.borderColor = hsva.toHEXA().toString();
    });

    /* Internal padding */
    document.querySelector('#internal_padding').addEventListener('change', event => {
        document.querySelector('[data-wrapper]').style.padding = `${document.querySelector('#internal_padding').value}px`;
    });

    /* Display shadow */
    document.querySelector('#display_shadow').addEventListener('change', event => {
        if(event.currentTarget.checked) {
            document.querySelector('[data-wrapper]').classList.add('altumcode-66pusher-widget-wrapper-shadow');
        } else {
            document.querySelector('[data-wrapper]').classList.remove('altumcode-66pusher-widget-wrapper-shadow');
        }
    });

    /* Border width */
    document.querySelector('#border_width').addEventListener('change', event => {
        document.querySelector('[data-wrapper]').style.borderWidth = `${document.querySelector('#border_width').value}px`;
    });

    /* Border radius */
    document.querySelectorAll('input[name="border_radius"]').forEach(element => element.addEventListener('change', event => {
        document.querySelector('[data-wrapper]').classList.remove('altumcode-66pusher-widget-wrapper-straight', 'altumcode-66pusher-widget-wrapper-rounded', 'altumcode-66pusher-widget-wrapper-highly_rounded', 'altumcode-66pusher-widget-wrapper-round');
        document.querySelector('[data-wrapper]').classList.add(`altumcode-66pusher-widget-wrapper-${document.querySelector('input[name="border_radius"]:checked').value}`);
    }));

    /* Triggers */
    /* Display Trigger Handler */
    type_handler('input[name="display_trigger"]', 'data-display-trigger');
    document.querySelector('input[name="display_trigger"]') && document.querySelectorAll('input[name="display_trigger"]').forEach(element => element.addEventListener('change', () => { type_handler('input[name="display_trigger"]', 'data-display-trigger'); }));

    /* Display Trigger Handler */
    type_handler('input[name="display_delay_type_after_close"]', 'data-display-delay-type-after-close');
    document.querySelector('input[name="display_delay_type_after_close"]') && document.querySelectorAll('input[name="display_delay_type_after_close"]').forEach(element => element.addEventListener('change', () => { type_handler('input[name="display_delay_type_after_close"]', 'data-display-delay-type-after-close'); }));

    let triggers_status_handler = () => {

        if($('#trigger_all_pages').is(':checked')) {

            /* Disable the container visually */
            $('#triggers').addClass('container-disabled');

            /* Remove the new trigger add button */
            $('#trigger_add').hide();

        } else {

            /* Remove disabled container if depending on the status of the trigger checkbox */
            $('#triggers').removeClass('container-disabled');

            /* Bring back the new trigger add button */
            $('#trigger_add').show();

        }

        $('select[name="trigger_type[]"]').off().on('change', event => {

            let input = $(event.currentTarget).closest('.form-row').find('input');
            let placeholder = $(event.currentTarget).find(':checked').data('placeholder');

            /* Add the proper placeholder */
            input.attr('placeholder', placeholder);

        }).trigger('change');

    };

    /* Trigger on status change live of the checkbox */
    $('#trigger_all_pages').on('change', triggers_status_handler);

    /* Delete trigger handler */
    let triggers_delete_handler = () => {

        /* Delete button handler */
        $('.trigger-delete').off().on('click', event => {

            let trigger = $(event.currentTarget).closest('.form-row');

            trigger.remove();

            triggers_count_handler();
        });

    };

    let triggers_add_sample = () => {
        let trigger_rule_sample = $('#trigger_rule_sample').html();

        $('#triggers').append(trigger_rule_sample);
    };

    let triggers_count_handler = () => {
        let total_triggers = $('#triggers > .form-row').length;

        /* Make sure we at least have two input groups to show the delete button */
        if(total_triggers > 1) {
            $('#triggers .trigger-delete').removeAttr('disabled');

            /* Make sure to set a limit to these triggers */
            if(total_triggers > 10) {
                $('#trigger_add').hide();
            } else {
                $('#trigger_add').show();
            }

        } else {

            if(total_triggers == 0) {
                triggers_add_sample();
            }

            $('#triggers .trigger-delete').attr('disabled', 'disabled');
        }
    };

    /* Add new trigger rule handler */
    $('#trigger_add').on('click', () => {
        triggers_add_sample();
        triggers_delete_handler();
        triggers_count_handler();
        triggers_status_handler();
    });

    /* Trigger functions for the first initial load */
    triggers_status_handler();
    triggers_delete_handler();
    triggers_count_handler();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>
