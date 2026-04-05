<?php defined('ALTUMCODE') || die() ?>

<?php ob_start() ?>
<style>
    .fcc-short-link-create-modal .modal-content {
        background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.08);
        border-radius: 22px;
        box-shadow: 0 26px 60px rgba(0, 0, 0, 0.26);
    }

    .fcc-short-link-create-modal .close {
        color: #d9e8ea;
        opacity: 0.9;
        text-shadow: none;
    }

    .fcc-short-link-create-modal .close:hover {
        color: #ffffff;
    }

    .fcc-short-link-create-head {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }

    .fcc-short-link-create-icon {
        width: 2.8rem;
        height: 2.8rem;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(63, 215, 199, 0.2) 0%, rgba(109, 233, 221, 0.18) 100%);
        color: #9ef1e7;
        flex-shrink: 0;
    }

    .fcc-short-link-create-head h5 {
        margin: 0;
        color: #f4fbfb;
    }

    .fcc-short-link-create-head p {
        margin: 0.2rem 0 0;
        color: #9db3b8;
        line-height: 1.6;
        font-size: 0.92rem;
    }

    .fcc-short-link-create-note {
        margin-bottom: 1rem;
        border-radius: 18px;
        padding: 1rem 1.05rem;
        border: 1px solid rgba(127, 227, 217, 0.1);
        background: linear-gradient(180deg, rgba(14, 22, 27, 0.92) 0%, rgba(11, 18, 22, 0.96) 100%);
    }

    .fcc-short-link-create-note strong {
        display: block;
        margin-bottom: 0.35rem;
        color: #f5fbfb;
        font-size: 0.95rem;
    }

    .fcc-short-link-create-note span {
        color: #a4b8bc;
        line-height: 1.62;
        font-size: 0.9rem;
    }

    .fcc-short-link-create-modal label {
        color: #e8f3f4;
        font-weight: 700;
    }

    .fcc-short-link-create-modal .form-control,
    .fcc-short-link-create-modal .custom-select,
    .fcc-short-link-create-modal .input-group-text {
        min-height: 3rem;
        border-radius: 14px;
        border-color: rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.03);
        color: #edf7f8;
    }

    .fcc-short-link-create-modal .input-group > .form-control:not(:first-child) {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .fcc-short-link-create-modal .input-group > .input-group-prepend > .input-group-text,
    .fcc-short-link-create-modal .input-group > .input-group-prepend > .custom-select {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .fcc-short-link-create-modal .form-control::placeholder {
        color: #7f9499;
    }

    .fcc-short-link-create-modal .form-control:focus,
    .fcc-short-link-create-modal .custom-select:focus {
        border-color: rgba(127, 227, 217, 0.28);
        box-shadow: 0 0 0 0.2rem rgba(63, 215, 199, 0.12);
    }

    .fcc-short-link-create-modal .text-muted,
    .fcc-short-link-create-modal .form-text {
        color: #94abb0 !important;
    }

    .fcc-short-link-create-hint {
        margin-top: 1rem;
        border-radius: 16px;
        padding: 0.9rem 1rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        color: #cfdddf;
        font-size: 0.9rem;
        line-height: 1.62;
    }

    .fcc-short-link-create-hint strong {
        color: #f5fbfb;
    }

    .fcc-short-link-create-modal .btn-primary {
        min-height: 3.2rem;
        border-radius: 16px;
        border-color: transparent;
        background: linear-gradient(135deg, #3fd7c7 0%, #6de9dd 100%);
        color: #082826;
        font-weight: 800;
        box-shadow: none !important;
    }

    .fcc-short-link-create-modal .btn-primary:hover {
        color: #041b19;
        transform: translateY(-1px);
        box-shadow: 0 16px 32px rgba(63, 215, 199, 0.18) !important;
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php if(settings()->links->shortener_is_enabled): ?>
    <div class="modal fade fcc-short-link-create-modal" id="create_link" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="fcc-short-link-create-head">
                            <div class="fcc-short-link-create-icon">
                                <i class="fas fa-fw fa-sm fa-link"></i>
                            </div>
                            <div>
                                <h5 class="modal-title"><?= l('create_link_modal.header') ?></h5>
                                <p><?= l('create_link_modal.subheader') ?></p>
                            </div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form name="create_link" method="post" role="form">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                        <input type="hidden" name="request_type" value="create" />
                        <input type="hidden" name="type" value="link" />

                        <div class="notification-container"></div>

                        <div class="fcc-short-link-create-note">
                            <strong><?= l('create_link_modal.guide_title') ?></strong>
                            <span><?= l('create_link_modal.guide_text') ?></span>
                        </div>

                        <div class="form-group">
                            <label for="link_location_url"><i class="fas fa-fw fa-link fa-sm text-muted mr-1"></i> <?= l('create_link_modal.input.location_url') ?></label>
                            <input id="link_location_url" type="url" class="form-control" name="location_url" maxlength="2048" required="required" placeholder="<?= l('global.url_placeholder') ?>" />
                        </div>

                        <div class="form-group">
                            <label for="link_url"><i class="fas fa-fw fa-bolt fa-sm text-muted mr-1"></i> <?= l('create_link_modal.input.url') ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <?php if (!empty($data->domains)): ?>
                                        <select name="domain_id" class="appearance-none custom-select form-control input-group-text">
                                            <?php if(settings()->links->main_domain_is_enabled || \Altum\Authentication::is_admin()): ?>
                                                <option value=" "><?= remove_url_protocol_from_url(SITE_URL) ?></option>
                                            <?php endif ?>

                                            <?php foreach($data->domains as $row): ?>
                                                <option value="<?= $row->domain_id ?>"><?= remove_url_protocol_from_url($row->url) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    <?php else: ?>
                                        <span class="input-group-text"><?= remove_url_protocol_from_url(SITE_URL) ?></span>
                                    <?php endif ?>
                                </div>
                                <input
                                        id="link_url"
                                        type="text"
                                        class="form-control"
                                        name="url"
                                        maxlength="<?= $this->user->plan_settings->url_maximum_characters ?? 64 ?>"
                                        onchange="update_this_value(this, get_slug)"
                                        onkeyup="update_this_value(this, get_slug)"
                                        placeholder="<?= $this->user->plan_settings->custom_url ? l('global.url_slug_placeholder') : l('create_link_modal.input.url_placeholder') ?>"
                                    <?= !$this->user->plan_settings->custom_url ? 'readonly="readonly"' : null ?>
                                    <?= $this->user->plan_settings->custom_url ? null : get_plan_feature_disabled_info() ?>
                                />
                            </div>
                            <small class="form-text text-muted"><?= l('create_link_modal.input.url_help') ?></small>
                        </div>

                        <p class="small text-muted"><i class="fas fa-fw fa-sm fa-circle-info mr-1"></i> <?= l('link.create_info') ?></p>

                        <div class="fcc-short-link-create-hint">
                            <strong><?= l('create_link_modal.after_create_title') ?></strong>
                            <div><?= l('create_link_modal.after_create_text') ?></div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('create_link_modal.input.submit') ?></button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->links->biolinks_is_enabled): ?>
    <div class="modal fade" id="create_biolink" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="modal-title">
                            <i class="fas fa-fw fa-sm fa-hashtag text-dark mr-2"></i>
                            <?= l('biolink_modal.header') ?>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form name="create_biolink" method="post" role="form">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                        <input type="hidden" name="request_type" value="create" />
                        <input type="hidden" name="type" value="biolink" />
                        <input type="hidden" name="biolink_template_id" value="" />

                        <div class="notification-container"></div>

                        <div class="form-group">
                            <label for="biolink_url"><i class="fas fa-fw fa-bolt fa-sm text-muted mr-1"></i> <?= l('link.settings.url') ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <?php if (!empty($data->domains)): ?>
                                        <select name="domain_id" class="appearance-none custom-select form-control input-group-text">
                                            <?php if(settings()->links->main_domain_is_enabled || \Altum\Authentication::is_admin()): ?>
                                                <option value=" "><?= remove_url_protocol_from_url(SITE_URL) ?></option>
                                            <?php endif ?>

                                            <?php foreach($data->domains as $row): ?>
                                                <option value="<?= $row->domain_id ?>"><?= remove_url_protocol_from_url($row->url) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    <?php else: ?>
                                        <span class="input-group-text"><?= remove_url_protocol_from_url(SITE_URL) ?></span>
                                    <?php endif ?>
                                </div>
                                <input
                                        id="biolink_url"
                                        type="text"
                                        class="form-control"
                                        name="url"
                                        maxlength="<?= $this->user->plan_settings->url_maximum_characters ?? 64 ?>"
                                        onchange="update_this_value(this, get_slug)"
                                        onkeyup="update_this_value(this, get_slug)"
                                        placeholder="<?= $this->user->plan_settings->custom_url ? l('global.url_slug_placeholder') : l('link.settings.url_placeholder_random') ?>"
                                    <?= !$this->user->plan_settings->custom_url ? 'readonly="readonly"' : null ?>
                                    <?= $this->user->plan_settings->custom_url ? null : get_plan_feature_disabled_info() ?>
                                />
                            </div>
                            <small class="form-text text-muted"><?= l('link.settings.url_help') ?></small>
                        </div>

                        <p class="small text-muted"><i class="fas fa-fw fa-sm fa-circle-info mr-1"></i> <?= l('link.create_info') ?></p>

                        <div class="text-center mt-4">
                            <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('biolink_modal.input.submit') ?></button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->links->files_is_enabled): ?>
    <div class="modal fade" id="create_file" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="modal-title">
                            <i class="fas fa-fw fa-sm fa-file text-dark mr-2"></i>
                            <?= l('create_file_modal.header') ?>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form name="create_file" method="post" role="form" enctype="multipart/form-data">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                        <input type="hidden" name="request_type" value="create" />
                        <input type="hidden" name="type" value="file" />

                        <div class="notification-container"></div>

                        <div class="form-group">
                            <label for="file_file"><i class="fas fa-fw fa-sm fa-file text-muted mr-1"></i> <?= l('create_file_modal.input.file') ?></label>
                            <input id="file_file" type="file" name="file" accept="<?= \Altum\Uploads::get_whitelisted_file_extensions_accept('files') ?>" class="form-control-file altum-file-input" required="required" />
                            <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('files')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->file_size_limit) ?></small>
                        </div>

                        <div class="form-group">
                            <label for="file_url"><i class="fas fa-fw fa-bolt fa-sm text-muted mr-1"></i> <?= l('create_link_modal.input.url') ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <?php if (!empty($data->domains)): ?>
                                        <select name="domain_id" class="appearance-none custom-select form-control input-group-text">
                                            <?php if(settings()->links->main_domain_is_enabled || \Altum\Authentication::is_admin()): ?>
                                                <option value=" "><?= remove_url_protocol_from_url(SITE_URL) ?></option>
                                            <?php endif ?>

                                            <?php foreach($data->domains as $row): ?>
                                                <option value="<?= $row->domain_id ?>"><?= remove_url_protocol_from_url($row->url) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    <?php else: ?>
                                        <span class="input-group-text"><?= remove_url_protocol_from_url(SITE_URL) ?></span>
                                    <?php endif ?>
                                </div>
                                <input
                                        id="file_url"
                                        type="text"
                                        class="form-control"
                                        name="url"
                                        maxlength="<?= $this->user->plan_settings->url_maximum_characters ?? 64 ?>"
                                        onchange="update_this_value(this, get_slug)"
                                        onkeyup="update_this_value(this, get_slug)"
                                        placeholder="<?= $this->user->plan_settings->custom_url ? l('global.url_slug_placeholder') : l('link.settings.url_placeholder_random') ?>"
                                    <?= !$this->user->plan_settings->custom_url ? 'readonly="readonly"' : null ?>
                                    <?= $this->user->plan_settings->custom_url ? null : get_plan_feature_disabled_info() ?>
                                />
                            </div>
                            <small class="form-text text-muted"><?= l('link.settings.url_help') ?></small>
                        </div>

                        <p class="small text-muted"><i class="fas fa-fw fa-sm fa-circle-info mr-1"></i> <?= l('link.create_info') ?></p>

                        <div class="text-center mt-4">
                            <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('create_file_modal.input.submit') ?></button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->links->vcards_is_enabled): ?>
    <div class="modal fade" id="create_vcard" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="modal-title">
                            <i class="fas fa-fw fa-sm fa-id-card text-dark mr-2"></i>
                            <?= l('create_vcard_modal.header') ?>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form name="create_file" method="post" role="form" enctype="multipart/form-data">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                        <input type="hidden" name="request_type" value="create" />
                        <input type="hidden" name="type" value="vcard" />

                        <div class="notification-container"></div>

                        <div class="form-group">
                            <label for="vcard_url"><i class="fas fa-fw fa-bolt fa-sm text-muted mr-1"></i> <?= l('link.settings.url') ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <?php if (!empty($data->domains)): ?>
                                        <select name="domain_id" class="appearance-none custom-select form-control input-group-text">
                                            <?php if(settings()->links->main_domain_is_enabled || \Altum\Authentication::is_admin()): ?>
                                                <option value=" "><?= remove_url_protocol_from_url(SITE_URL) ?></option>
                                            <?php endif ?>

                                            <?php foreach($data->domains as $row): ?>
                                                <option value="<?= $row->domain_id ?>"><?= remove_url_protocol_from_url($row->url) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    <?php else: ?>
                                        <span class="input-group-text"><?= remove_url_protocol_from_url(SITE_URL) ?></span>
                                    <?php endif ?>
                                </div>
                                <input
                                        id="vcard_url"
                                        type="text"
                                        class="form-control"
                                        name="url"
                                        maxlength="<?= $this->user->plan_settings->url_maximum_characters ?? 64 ?>"
                                        onchange="update_this_value(this, get_slug)"
                                        onkeyup="update_this_value(this, get_slug)"
                                        placeholder="<?= $this->user->plan_settings->custom_url ? l('global.url_slug_placeholder') : l('link.settings.url_placeholder_random') ?>"
                                    <?= !$this->user->plan_settings->custom_url ? 'readonly="readonly"' : null ?>
                                    <?= $this->user->plan_settings->custom_url ? null : get_plan_feature_disabled_info() ?>
                                />
                            </div>
                            <small class="form-text text-muted"><?= l('link.settings.url_help') ?></small>
                        </div>

                        <p class="small text-muted"><i class="fas fa-fw fa-sm fa-circle-info mr-1"></i> <?= l('link.create_info') ?></p>

                        <div class="text-center mt-4">
                            <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('create_vcard_modal.input.submit') ?></button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->links->events_is_enabled): ?>
    <div class="modal fade" id="create_event" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="modal-title">
                            <i class="fas fa-fw fa-sm fa-calendar text-dark mr-2"></i>
                            <?= l('create_event_modal.header') ?>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form name="create_event" method="post" role="form" enctype="multipart/form-data">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                        <input type="hidden" name="request_type" value="create" />
                        <input type="hidden" name="type" value="event" />

                        <div class="notification-container"></div>

                        <div class="form-group">
                            <label for="event_url"><i class="fas fa-fw fa-bolt fa-sm text-muted mr-1"></i> <?= l('link.settings.url') ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <?php if (!empty($data->domains)): ?>
                                        <select name="domain_id" class="appearance-none custom-select form-control input-group-text">
                                            <?php if(settings()->links->main_domain_is_enabled || \Altum\Authentication::is_admin()): ?>
                                                <option value=" "><?= remove_url_protocol_from_url(SITE_URL) ?></option>
                                            <?php endif ?>

                                            <?php foreach($data->domains as $row): ?>
                                                <option value="<?= $row->domain_id ?>"><?= remove_url_protocol_from_url($row->url) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    <?php else: ?>
                                        <span class="input-group-text"><?= remove_url_protocol_from_url(SITE_URL) ?></span>
                                    <?php endif ?>
                                </div>
                                <input
                                        id="event_url"
                                        type="text"
                                        class="form-control"
                                        name="url"
                                        maxlength="<?= $this->user->plan_settings->url_maximum_characters ?? 64 ?>"
                                        onchange="update_this_value(this, get_slug)"
                                        onkeyup="update_this_value(this, get_slug)"
                                        placeholder="<?= $this->user->plan_settings->custom_url ? l('global.url_slug_placeholder') : l('link.settings.url_placeholder_random') ?>"
                                    <?= !$this->user->plan_settings->custom_url ? 'readonly="readonly"' : null ?>
                                    <?= $this->user->plan_settings->custom_url ? null : get_plan_feature_disabled_info() ?>
                                />
                            </div>
                            <small class="form-text text-muted"><?= l('link.settings.url_help') ?></small>
                        </div>

                        <p class="small text-muted"><i class="fas fa-fw fa-sm fa-circle-info mr-1"></i> <?= l('link.create_info') ?></p>

                        <div class="text-center mt-4">
                            <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('create_event_modal.submit') ?></button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->links->static_is_enabled): ?>
    <div class="modal fade" id="create_static" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="modal-title">
                            <i class="fas fa-fw fa-sm fa-file-code text-dark mr-2"></i>
                            <?= l('create_static_modal.header') ?>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form name="create_static" method="post" role="form" enctype="multipart/form-data">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                        <input type="hidden" name="request_type" value="create" />
                        <input type="hidden" name="type" value="static" />

                        <div class="notification-container"></div>

                        <div class="form-group" data-file-input-wrapper-size-limit="<?= settings()->links->static_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->links->static_size_limit) ?>">
                            <label for="static_file"><i class="fas fa-fw fa-sm fa-file-zipper text-muted mr-1"></i> <?= l('create_static_modal.file') ?></label>
                            <input id="static_file" type="file" name="file" accept="<?= \Altum\Uploads::get_whitelisted_file_extensions_accept('static') ?>" class="form-control-file altum-file-input" required="required" />
                            <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('static')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->static_size_limit) ?></small>
                            <small class="form-text text-muted"><?= sprintf(l('create_static_modal.file.inside_zip_whitelisted_file_extensions'), \Altum\Uploads::array_to_list_format(\Altum\Uploads::$uploads['static']['inside_zip_whitelisted_file_extensions'])) ?></small>
                            <small class="form-text text-muted"><?= l('create_static_modal.file.help1') ?></small>
                            <small class="form-text text-muted"><?= l('create_static_modal.file.help2') ?></small>
                        </div>

                        <div class="form-group">
                            <label for="static_url"><i class="fas fa-fw fa-bolt fa-sm text-muted mr-1"></i> <?= l('link.settings.url') ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <?php if (!empty($data->domains)): ?>
                                        <select name="domain_id" class="appearance-none custom-select form-control input-group-text">
                                            <?php if(settings()->links->main_domain_is_enabled || \Altum\Authentication::is_admin()): ?>
                                                <option value=" "><?= remove_url_protocol_from_url(SITE_URL) ?></option>
                                            <?php endif ?>

                                            <?php foreach($data->domains as $row): ?>
                                                <option value="<?= $row->domain_id ?>"><?= remove_url_protocol_from_url($row->url) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    <?php else: ?>
                                        <span class="input-group-text"><?= remove_url_protocol_from_url(SITE_URL) ?></span>
                                    <?php endif ?>
                                </div>
                                <input
                                        id="static_url"
                                        type="text"
                                        class="form-control"
                                        name="url"
                                        maxlength="<?= $this->user->plan_settings->url_maximum_characters ?? 64 ?>"
                                        onchange="update_this_value(this, get_slug)"
                                        onkeyup="update_this_value(this, get_slug)"
                                        placeholder="<?= $this->user->plan_settings->custom_url ? l('global.url_slug_placeholder') : l('link.settings.url_placeholder_random') ?>"
                                    <?= !$this->user->plan_settings->custom_url ? 'readonly="readonly"' : null ?>
                                    <?= $this->user->plan_settings->custom_url ? null : get_plan_feature_disabled_info() ?>
                                />
                            </div>
                            <small class="form-text text-muted"><?= l('link.settings.url_help') ?></small>
                        </div>

                        <p class="small text-muted"><i class="fas fa-fw fa-sm fa-circle-info mr-1"></i> <?= l('link.create_info') ?></p>

                        <div class="text-center mt-4">
                            <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('create_static_modal.submit') ?></button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
<?php endif ?>

<?php ob_start() ?>
<script>
    'use strict';
    
    $('form[name="create_link"],form[name="create_biolink"],form[name="create_file"],form[name="create_vcard"],form[name="create_event"],form[name="create_static"]').on('submit', event => {
        let form = $(event.currentTarget)[0];
        let data = new FormData(form);

        let notification_container = event.currentTarget.querySelector('.notification-container');
        notification_container.innerHTML = '';
        pause_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));

        $.ajax({
            type: 'POST',
            enctype: 'multipart/form-data',
            processData: false,
            contentType: false,
            cache: false,
            url: `${url}link-ajax`,
            data: data,
            dataType: 'json',
            success: (data) => {
                enable_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));

                if(data.status == 'error') {
                    display_notifications(data.message, 'error', notification_container);
                }

                else if(data.status == 'success') {
                    display_notifications(data.message, 'success', notification_container);

                    setTimeout(() => {
                        $(event.currentTarget).modal('hide');
                        redirect(data.details.url, true);
                    }, 750);
                }
            },
            error: () => {
                enable_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));
                display_notifications(<?= json_encode(l('global.error_message.basic')) ?>, 'error', notification_container);
            },
        });

        event.preventDefault();
    })

    <?php if(
    settings()->links->claim_url_is_enabled
    && isset($_GET['welcome'])
    && session_has('claim_url')
    && !empty($this->user->preferences->claim_url)
    ):
    $claim_url = json_encode($this->user->preferences->claim_url);
    $domain_id = !empty($this->user->preferences->domain_id) ? json_encode($this->user->preferences->domain_id) : 'null';
    session_unset_key('claim_url');
    session_unset_key('domain_id');
    ?>
    $('#create_<?= settings()->links->claim_url_type ?> input[name="url"]').val(<?= $claim_url ?>);

    let domain_id_select = $('#create_<?= settings()->links->claim_url_type ?> select[name="domain_id"]');
    if(domain_id_select.length && <?= $domain_id ?> !== null) {
        domain_id_select.val(<?= $domain_id ?>).trigger('change');
    }

    $('#create_<?= settings()->links->claim_url_type ?>').modal('show');
    <?php endif; ?>
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
