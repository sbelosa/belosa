<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-03-23: lead funnel analytics entry points */
$lead_funnel_statistics_url = url('biolink-block/' . $row->biolink_block_id . '/statistics');
$lead_funnel_data_url = url('data?type=lead_funnel&biolink_block_id=' . $row->biolink_block_id);
$lead_funnel_analytics_url = url('funnels-analytics?biolink_block_id=' . $row->biolink_block_id);
/* /Custom code: FC-2026-03-23 */
?>

<form id="<?= 'update_biolink_block_' . $row->biolink_block_id ?>" name="update_biolink_" method="post" role="form" data-type="<?= $row->type ?>" enctype="multipart/form-data">
    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
    <input type="hidden" name="request_type" value="update" />
    <input type="hidden" name="block_type" value="lead_funnel" />
    <input type="hidden" name="biolink_block_id" value="<?= $row->biolink_block_id ?>" />

    <div class="notification-container"></div>

    <?php /* Custom code: FC-2026-03-23: top-level live popup preview */ ?>
    <div class="mb-4">
        <div class="form-group mb-2">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <label class="mb-0"><i class="fas fa-fw fa-eye fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.popup_preview') ?></label>
                <div class="btn-group btn-group-sm mt-2 mt-sm-0" role="group" aria-label="<?= l('biolink_lead_funnel.popup_preview') ?>">
                    <button type="button" class="btn btn-light active" data-lead-funnel-popup-preview-screen-toggle="form"><?= l('biolink_lead_funnel.preview_form') ?></button>
                    <button type="button" class="btn btn-light" data-lead-funnel-popup-preview-screen-toggle="thank_you"><?= l('biolink_lead_funnel.preview_thank_you') ?></button>
                </div>
            </div>
            <small class="form-text text-muted"><?= l('biolink_lead_funnel.popup_preview_help') ?></small>
        </div>

        <div class="lead-funnel-popup-preview-shell p-3 rounded" style="background: linear-gradient(180deg, rgba(15,23,42,.08), rgba(15,23,42,.14));">
            <div class="card border-0 shadow overflow-hidden mx-auto" data-lead-funnel-popup-mini-preview data-initial-image-src="<?= !empty($row->settings->image) ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $row->settings->image : '' ?>" style="--lead-funnel-background-color: <?= $row->settings->popup_background_color ?? '#ffffff' ?>; --lead-funnel-text-color: <?= $row->settings->popup_text_color ?? '#212529' ?>; --lead-funnel-button-background-color: <?= $row->settings->popup_button_background_color ?? '#007bff' ?>; --lead-funnel-button-text-color: <?= $row->settings->popup_button_text_color ?? '#ffffff' ?>; background: var(--lead-funnel-background-color); color: var(--lead-funnel-text-color); border-radius: 1.25rem; max-width: 34rem;">
                <div class="p-3 border-bottom" style="border-color: rgba(0,0,0,.08) !important;">
                    <div class="small mb-2 d-none" data-lead-funnel-preview-back-link style="color: var(--lead-funnel-text-color); opacity: .72;">
                        <i class="fas fa-fw fa-arrow-left mr-1"></i> <?= l('biolink_lead_funnel.back_to_biolink') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="font-weight-bold mr-3" data-lead-funnel-popup-mini-preview-title><?= $row->settings->popup_title ?: $row->settings->name ?></div>
                        <button type="button" class="close float-none m-0" data-lead-funnel-preview-close aria-label="<?= l('global.close') ?>" style="color: var(--lead-funnel-text-color); opacity: .7;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>

                <div class="p-3">
                    <div class="text-center mb-3 <?= !empty($row->settings->image) ? '' : 'd-none' ?>" data-lead-funnel-popup-mini-preview-image-wrapper>
                        <img src="<?= !empty($row->settings->image) ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $row->settings->image : '' ?>" class="img-fluid rounded" data-lead-funnel-popup-mini-preview-image style="max-height: 9rem; object-fit: cover;" />
                    </div>

                    <div class="ql-content lead-funnel-supporting-text mb-3" data-lead-funnel-popup-mini-preview-subtitle style="<?= !empty($row->settings->popup_subtitle) ? '' : 'display:none;' ?>"><?= $row->settings->popup_subtitle ?? '' ?></div>

                    <div class="rounded overflow-hidden mb-4 <?= !empty($row->settings->video_url) ? '' : 'd-none' ?>" data-lead-funnel-popup-mini-preview-video-wrapper style="background: #111827;">
                        <div class="lead-funnel-popup-mini-preview-video" style="position: relative; width: 100%; padding-top: 56.25%;">
                            <iframe src="about:blank" allow="autoplay; fullscreen; picture-in-picture" loading="lazy" data-lead-funnel-popup-mini-preview-video-iframe style="position: absolute; inset: 0; width: 100%; height: 100%; border: 0;"></iframe>
                            <div class="d-flex flex-column align-items-center justify-content-center text-white text-center px-3" data-lead-funnel-popup-mini-preview-video-placeholder style="position: absolute; inset: 0; background: rgba(17,24,39,.92);">
                                <div class="small text-uppercase font-weight-bold" data-lead-funnel-popup-mini-preview-video-label><?= $row->settings->video_provider ?? 'youtube' ?></div>
                                <div class="small" style="opacity: .75;"><?= l('biolink_lead_funnel.video_url') ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="ql-content lead-funnel-supporting-text mb-4" data-lead-funnel-popup-mini-preview-description style="<?= !empty($row->settings->description) ? '' : 'display:none;' ?>"><?= $row->settings->description ?? '' ?></div>

                    <div data-lead-funnel-popup-preview-screen="form">
                        <div class="form-group mb-3 <?= !empty($row->settings->show_email) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-field="email">
                            <div class="input-group">
                                <div class="input-group-prepend"><div class="input-group-text bg-gray-50"><i class="fas fa-fw fa-envelope"></i></div></div>
                                <input type="text" class="form-control" readonly data-lead-funnel-popup-preview-placeholder="email" placeholder="<?= $row->settings->email_placeholder ?? '' ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3 <?= !empty($row->settings->show_phone) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-field="phone">
                            <div class="input-group">
                                <div class="input-group-prepend"><div class="input-group-text bg-gray-50"><i class="fas fa-fw fa-phone-square-alt"></i></div></div>
                                <input type="text" class="form-control" readonly data-lead-funnel-popup-preview-placeholder="phone" placeholder="<?= $row->settings->phone_placeholder ?? '' ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3 <?= !empty($row->settings->show_name) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-field="name">
                            <div class="input-group">
                                <div class="input-group-prepend"><div class="input-group-text bg-gray-50"><i class="fas fa-fw fa-signature"></i></div></div>
                                <input type="text" class="form-control" readonly data-lead-funnel-popup-preview-placeholder="name" placeholder="<?= $row->settings->name_placeholder ?? '' ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3 <?= !empty($row->settings->show_message) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-field="message">
                            <textarea class="form-control" rows="3" readonly data-lead-funnel-popup-preview-placeholder="message" placeholder="<?= $row->settings->message_placeholder ?? '' ?>"></textarea>
                        </div>
                        <div class="custom-control custom-switch mb-3 <?= !empty($row->settings->show_agreement) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-agreement-wrapper>
                            <input type="checkbox" class="custom-control-input" disabled>
                            <label class="custom-control-label font-weight-normal">
                                <span class="ql-content" data-lead-funnel-popup-preview-agreement-text><?= $row->settings->agreement_text ?? '' ?></span>
                                <span data-lead-funnel-popup-preview-agreement-link-wrapper class="<?= !empty($row->settings->agreement_url) ? '' : 'd-none' ?>">
                                    <i class="fas fa-fw fa-sm fa-external-link-alt"></i>
                                </span>
                            </label>
                        </div>
                        <button type="button" class="btn btn-block" data-lead-funnel-popup-mini-preview-button style="background: var(--lead-funnel-button-background-color); border-color: var(--lead-funnel-button-background-color); color: var(--lead-funnel-button-text-color);">
                            <?= $row->settings->button_text ?? l('biolink_lead_funnel.button_text_default') ?>
                        </button>
                    </div>

                    <div class="d-none text-center" data-lead-funnel-popup-preview-screen="thank_you">
                        <div class="mb-3 ql-content" data-lead-funnel-popup-preview-thank-you-title style="color: var(--lead-funnel-text-color); font-size: 1.3rem; font-weight: 600; <?= !empty($row->settings->thank_you_title) ? '' : 'display:none;' ?>"><?= $row->settings->thank_you_title ?? '' ?></div>
                        <div class="ql-content lead-funnel-supporting-text mb-3" data-lead-funnel-popup-preview-thank-you-text style="<?= !empty($row->settings->thank_you_text) ? '' : 'display:none;' ?>"><?= $row->settings->thank_you_text ?? '' ?></div>
                        <button type="button" class="btn btn-block <?= in_array(($row->settings->thank_you_type ?? 'message'), ['file_download']) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-thank-you-button style="background: var(--lead-funnel-button-background-color); border-color: var(--lead-funnel-button-background-color); color: var(--lead-funnel-button-text-color);">
                            <?= $row->settings->thank_you_button_text ?? l('biolink_lead_funnel.thank_you_button_text_default') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php /* /Custom code: FC-2026-03-23 */ ?>

    <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'lead_funnel_open_mode_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'lead_funnel_open_mode_container_' . $row->biolink_block_id ?>">
        <i class="fas fa-fw fa-up-right-from-square fa-sm mr-1"></i> <?= l('biolink_lead_funnel.open_mode_header') ?>
    </button>

    <div class="collapse" data-parent="<?= '#update_biolink_block_' . $row->biolink_block_id ?>" id="<?= 'lead_funnel_open_mode_container_' . $row->biolink_block_id ?>">
        <div class="form-group">
            <label for="<?= 'lead_funnel_open_mode_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-arrow-up-right-from-square fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.open_mode') ?></label>
            <select id="<?= 'lead_funnel_open_mode_' . $row->biolink_block_id ?>" name="open_mode" class="custom-select" data-is-not-custom-select>
                <option value="popup" <?= ($row->settings->open_mode ?? 'popup') == 'popup' ? 'selected="selected"' : null ?>><?= l('biolink_lead_funnel.open_mode_popup') ?></option>
                <option value="page" <?= ($row->settings->open_mode ?? 'popup') == 'page' ? 'selected="selected"' : null ?>><?= l('biolink_lead_funnel.open_mode_page') ?></option>
            </select>
            <small class="form-text text-muted"><?= l('biolink_lead_funnel.open_mode_help') ?></small>
        </div>

        <?php /* Custom code: FC-2026-03-23: conditional open mode settings */ ?>
        <div class="alert alert-info mb-3" data-lead-funnel-open-mode-setting="popup">
            <i class="fas fa-fw fa-sm fa-info-circle mr-1"></i> <?= l('biolink_lead_funnel.open_mode_popup_help') ?>
        </div>

        <div data-lead-funnel-open-mode-setting="popup">
            <div class="form-group mb-2 d-none">
                <label class="mb-0"><i class="fas fa-fw fa-palette fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.popup_style_header') ?></label>
            </div>

            <div class="form-group">
                <label for="<?= 'lead_funnel_popup_background_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-fill fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.popup_background_color') ?></label>
                <input id="<?= 'lead_funnel_popup_background_color_' . $row->biolink_block_id ?>" type="hidden" name="popup_background_color" class="form-control" value="<?= $row->settings->popup_background_color ?? '#ffffff' ?>" required="required" />
                <div class="lead_funnel_popup_background_color_pickr"></div>
            </div>

            <div class="form-group">
                <label for="<?= 'lead_funnel_popup_text_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-paint-brush fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.popup_text_color') ?></label>
                <input id="<?= 'lead_funnel_popup_text_color_' . $row->biolink_block_id ?>" type="hidden" name="popup_text_color" class="form-control" value="<?= $row->settings->popup_text_color ?? '#212529' ?>" required="required" />
                <div class="lead_funnel_popup_text_color_pickr"></div>
            </div>

            <div class="form-group">
                <label for="<?= 'lead_funnel_popup_button_background_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-square fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.popup_button_background_color') ?></label>
                <input id="<?= 'lead_funnel_popup_button_background_color_' . $row->biolink_block_id ?>" type="hidden" name="popup_button_background_color" class="form-control" value="<?= $row->settings->popup_button_background_color ?? '#007bff' ?>" required="required" />
                <div class="lead_funnel_popup_button_background_color_pickr"></div>
            </div>

            <div class="form-group">
                <label for="<?= 'lead_funnel_popup_button_text_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-font fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.popup_button_text_color') ?></label>
                <input id="<?= 'lead_funnel_popup_button_text_color_' . $row->biolink_block_id ?>" type="hidden" name="popup_button_text_color" class="form-control" value="<?= $row->settings->popup_button_text_color ?? '#ffffff' ?>" required="required" />
                <div class="lead_funnel_popup_button_text_color_pickr"></div>
            </div>

            <?php /* Custom code: FC-2026-03-23: popup mini preview */ ?>
            <div class="form-group mb-2">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <label class="mb-0"><i class="fas fa-fw fa-eye fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.popup_preview') ?></label>
                    <div class="btn-group btn-group-sm mt-2 mt-sm-0" role="group" aria-label="<?= l('biolink_lead_funnel.popup_preview') ?>">
                        <button type="button" class="btn btn-light active" data-lead-funnel-popup-preview-screen-toggle="form"><?= l('biolink_lead_funnel.preview_form') ?></button>
                        <button type="button" class="btn btn-light" data-lead-funnel-popup-preview-screen-toggle="thank_you"><?= l('biolink_lead_funnel.preview_thank_you') ?></button>
                    </div>
                </div>
                <small class="form-text text-muted"><?= l('biolink_lead_funnel.popup_preview_help') ?></small>
            </div>

            <div class="lead-funnel-popup-preview-shell p-3 rounded d-none" style="background: linear-gradient(180deg, rgba(15,23,42,.08), rgba(15,23,42,.14));">
                <div class="card border-0 shadow overflow-hidden mx-auto" data-lead-funnel-popup-mini-preview data-initial-image-src="<?= !empty($row->settings->image) ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $row->settings->image : '' ?>" style="--lead-funnel-background-color: <?= $row->settings->popup_background_color ?? '#ffffff' ?>; --lead-funnel-text-color: <?= $row->settings->popup_text_color ?? '#212529' ?>; --lead-funnel-button-background-color: <?= $row->settings->popup_button_background_color ?? '#007bff' ?>; --lead-funnel-button-text-color: <?= $row->settings->popup_button_text_color ?? '#ffffff' ?>; background: var(--lead-funnel-background-color); color: var(--lead-funnel-text-color); border-radius: 1.25rem; max-width: 34rem;">
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between" style="border-color: rgba(0,0,0,.08) !important;">
                        <div class="font-weight-bold mr-3" data-lead-funnel-popup-mini-preview-title><?= $row->settings->popup_title ?: $row->settings->name ?></div>
                        <button type="button" class="close float-none m-0" aria-label="<?= l('global.close') ?>" style="color: var(--lead-funnel-text-color); opacity: .7;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="p-3">
                        <div class="text-center mb-3 <?= !empty($row->settings->image) ? '' : 'd-none' ?>" data-lead-funnel-popup-mini-preview-image-wrapper>
                            <img src="<?= !empty($row->settings->image) ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $row->settings->image : '' ?>" class="img-fluid rounded" data-lead-funnel-popup-mini-preview-image style="max-height: 9rem; object-fit: cover;" />
                        </div>

                        <div class="ql-content lead-funnel-supporting-text mb-3" data-lead-funnel-popup-mini-preview-subtitle style="<?= !empty($row->settings->popup_subtitle) ? '' : 'display:none;' ?>"><?= $row->settings->popup_subtitle ?? '' ?></div>

                        <div class="rounded overflow-hidden mb-4 <?= !empty($row->settings->video_url) ? '' : 'd-none' ?>" data-lead-funnel-popup-mini-preview-video-wrapper style="background: #111827;">
                            <div class="lead-funnel-popup-mini-preview-video" style="position: relative; width: 100%; padding-top: 56.25%;">
                                <iframe src="about:blank" allow="autoplay; fullscreen; picture-in-picture" loading="lazy" data-lead-funnel-popup-mini-preview-video-iframe style="position: absolute; inset: 0; width: 100%; height: 100%; border: 0;"></iframe>
                                <div class="d-flex flex-column align-items-center justify-content-center text-white text-center px-3" data-lead-funnel-popup-mini-preview-video-placeholder style="position: absolute; inset: 0; background: rgba(17,24,39,.92);">
                                    <div class="small text-uppercase font-weight-bold" data-lead-funnel-popup-mini-preview-video-label><?= $row->settings->video_provider ?? 'youtube' ?></div>
                                    <div class="small" style="opacity: .75;"><?= l('biolink_lead_funnel.video_url') ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="ql-content lead-funnel-supporting-text mb-4" data-lead-funnel-popup-mini-preview-description style="<?= !empty($row->settings->description) ? '' : 'display:none;' ?>"><?= $row->settings->description ?? '' ?></div>

                        <div data-lead-funnel-popup-preview-screen="form">
                            <div class="form-group mb-3 <?= !empty($row->settings->show_email) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-field="email">
                                <div class="input-group">
                                    <div class="input-group-prepend"><div class="input-group-text bg-gray-50"><i class="fas fa-fw fa-envelope"></i></div></div>
                                    <input type="text" class="form-control" readonly data-lead-funnel-popup-preview-placeholder="email" placeholder="<?= $row->settings->email_placeholder ?? '' ?>">
                                </div>
                            </div>
                            <div class="form-group mb-3 <?= !empty($row->settings->show_phone) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-field="phone">
                                <div class="input-group">
                                    <div class="input-group-prepend"><div class="input-group-text bg-gray-50"><i class="fas fa-fw fa-phone-square-alt"></i></div></div>
                                    <input type="text" class="form-control" readonly data-lead-funnel-popup-preview-placeholder="phone" placeholder="<?= $row->settings->phone_placeholder ?? '' ?>">
                                </div>
                            </div>
                            <div class="form-group mb-3 <?= !empty($row->settings->show_name) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-field="name">
                                <div class="input-group">
                                    <div class="input-group-prepend"><div class="input-group-text bg-gray-50"><i class="fas fa-fw fa-signature"></i></div></div>
                                    <input type="text" class="form-control" readonly data-lead-funnel-popup-preview-placeholder="name" placeholder="<?= $row->settings->name_placeholder ?? '' ?>">
                                </div>
                            </div>
                            <div class="form-group mb-3 <?= !empty($row->settings->show_message) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-field="message">
                                <textarea class="form-control" rows="3" readonly data-lead-funnel-popup-preview-placeholder="message" placeholder="<?= $row->settings->message_placeholder ?? '' ?>"></textarea>
                            </div>
                            <div class="custom-control custom-switch mb-3 <?= !empty($row->settings->show_agreement) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-agreement-wrapper>
                                <input type="checkbox" class="custom-control-input" disabled>
                                <label class="custom-control-label font-weight-normal">
                                    <span class="ql-content" data-lead-funnel-popup-preview-agreement-text><?= $row->settings->agreement_text ?? '' ?></span>
                                    <span data-lead-funnel-popup-preview-agreement-link-wrapper class="<?= !empty($row->settings->agreement_url) ? '' : 'd-none' ?>">
                                        <i class="fas fa-fw fa-sm fa-external-link-alt"></i>
                                    </span>
                                </label>
                            </div>
                            <button type="button" class="btn btn-block" data-lead-funnel-popup-mini-preview-button style="background: var(--lead-funnel-button-background-color); border-color: var(--lead-funnel-button-background-color); color: var(--lead-funnel-button-text-color);">
                                <?= $row->settings->button_text ?? l('biolink_lead_funnel.button_text_default') ?>
                            </button>
                        </div>

                        <div class="d-none text-center" data-lead-funnel-popup-preview-screen="thank_you">
                            <div class="mb-3 ql-content" data-lead-funnel-popup-preview-thank-you-title style="color: var(--lead-funnel-text-color); font-size: 1.3rem; font-weight: 600; <?= !empty($row->settings->thank_you_title) ? '' : 'display:none;' ?>"><?= $row->settings->thank_you_title ?? '' ?></div>
                            <div class="ql-content lead-funnel-supporting-text mb-3" data-lead-funnel-popup-preview-thank-you-text style="<?= !empty($row->settings->thank_you_text) ? '' : 'display:none;' ?>"><?= $row->settings->thank_you_text ?? '' ?></div>
                            <button type="button" class="btn btn-block <?= in_array(($row->settings->thank_you_type ?? 'message'), ['file_download']) ? '' : 'd-none' ?>" data-lead-funnel-popup-preview-thank-you-button style="background: var(--lead-funnel-button-background-color); border-color: var(--lead-funnel-button-background-color); color: var(--lead-funnel-button-text-color);">
                                <?= $row->settings->thank_you_button_text ?? l('biolink_lead_funnel.thank_you_button_text_default') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php /* /Custom code: FC-2026-03-23 */ ?>
        </div>

        <div class="alert alert-info mb-0" data-lead-funnel-open-mode-setting="page">
            <i class="fas fa-fw fa-sm fa-info-circle mr-1"></i> <?= l('biolink_lead_funnel.open_mode_page_help') ?>
        </div>

        <div data-lead-funnel-open-mode-setting="page">
            <div class="form-group mt-3 mb-2">
                <label class="mb-0"><i class="fas fa-fw fa-palette fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.page_style_header') ?></label>
            </div>

            <div class="form-group">
                <label for="<?= 'lead_funnel_page_background_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-fill fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.page_background_color') ?></label>
                <input id="<?= 'lead_funnel_page_background_color_' . $row->biolink_block_id ?>" type="hidden" name="page_background_color" class="form-control" value="<?= $row->settings->page_background_color ?? '#ffffff' ?>" required="required" />
                <div class="lead_funnel_page_background_color_pickr"></div>
            </div>

            <div class="form-group">
                <label for="<?= 'lead_funnel_page_text_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-paint-brush fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.page_text_color') ?></label>
                <input id="<?= 'lead_funnel_page_text_color_' . $row->biolink_block_id ?>" type="hidden" name="page_text_color" class="form-control" value="<?= $row->settings->page_text_color ?? '#212529' ?>" required="required" />
                <div class="lead_funnel_page_text_color_pickr"></div>
            </div>

            <div class="form-group">
                <label for="<?= 'lead_funnel_page_button_background_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-square fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.page_button_background_color') ?></label>
                <input id="<?= 'lead_funnel_page_button_background_color_' . $row->biolink_block_id ?>" type="hidden" name="page_button_background_color" class="form-control" value="<?= $row->settings->page_button_background_color ?? '#007bff' ?>" required="required" />
                <div class="lead_funnel_page_button_background_color_pickr"></div>
            </div>

            <div class="form-group">
                <label for="<?= 'lead_funnel_page_button_text_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-font fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.page_button_text_color') ?></label>
                <input id="<?= 'lead_funnel_page_button_text_color_' . $row->biolink_block_id ?>" type="hidden" name="page_button_text_color" class="form-control" value="<?= $row->settings->page_button_text_color ?? '#ffffff' ?>" required="required" />
                <div class="lead_funnel_page_button_text_color_pickr"></div>
            </div>

        </div>
        <?php /* /Custom code: FC-2026-03-23 */ ?>
    </div>

    <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'lead_funnel_content_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'lead_funnel_content_container_' . $row->biolink_block_id ?>">
        <i class="fas fa-fw fa-layer-group fa-sm mr-1"></i> <?= l('biolink_lead_funnel.content_header') ?>
    </button>

    <div class="collapse" data-parent="<?= '#update_biolink_block_' . $row->biolink_block_id ?>" id="<?= 'lead_funnel_content_container_' . $row->biolink_block_id ?>">
        <div class="form-group">
            <label for="<?= 'lead_funnel_popup_title_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-heading fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.popup_title') ?></label>
            <input id="<?= 'lead_funnel_popup_title_' . $row->biolink_block_id ?>" type="text" name="popup_title" class="form-control" value="<?= $row->settings->popup_title ?? '' ?>" maxlength="128" required="required" />
        </div>

        <?php /* Custom code: FC-2026-03-23: lead funnel rich text editor fields */ ?>
        <div class="form-group">
            <label for="<?= 'lead_funnel_popup_subtitle_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-align-left fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.popup_subtitle') ?></label>
            <textarea id="<?= 'lead_funnel_popup_subtitle_' . $row->biolink_block_id ?>" name="popup_subtitle" class="form-control" rows="2" maxlength="10000" data-lead-funnel-rich-text><?= bootstrap_to_quilljs($row->settings->popup_subtitle ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_video_provider_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-video fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.video_provider') ?></label>
            <select id="<?= 'lead_funnel_video_provider_' . $row->biolink_block_id ?>" name="video_provider" class="custom-select" data-is-not-custom-select>
                <option value="youtube" <?= ($row->settings->video_provider ?? 'youtube') == 'youtube' ? 'selected="selected"' : null ?>>YouTube</option>
                <option value="vimeo" <?= ($row->settings->video_provider ?? '') == 'vimeo' ? 'selected="selected"' : null ?>>Vimeo</option>
            </select>
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_video_url_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-link fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.video_url') ?></label>
            <input id="<?= 'lead_funnel_video_url_' . $row->biolink_block_id ?>" type="url" name="video_url" class="form-control" value="<?= $row->settings->video_url ?? '' ?>" placeholder="<?= l('global.url_placeholder') ?>" maxlength="2048" />
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_description_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-paragraph fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.description') ?></label>
            <textarea id="<?= 'lead_funnel_description_' . $row->biolink_block_id ?>" name="description" class="form-control" rows="4" maxlength="10000" data-lead-funnel-rich-text><?= bootstrap_to_quilljs($row->settings->description ?? '') ?></textarea>
        </div>
        <?php /* /Custom code: FC-2026-03-23 */ ?>
    </div>

    <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'lead_funnel_form_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'lead_funnel_form_container_' . $row->biolink_block_id ?>">
        <i class="fas fa-fw fa-rectangle-list fa-sm mr-1"></i> <?= l('biolink_lead_funnel.form_header') ?>
    </button>

    <div class="collapse" data-parent="<?= '#update_biolink_block_' . $row->biolink_block_id ?>" id="<?= 'lead_funnel_form_container_' . $row->biolink_block_id ?>">
        <div class="row">
            <?php foreach([
                'name' => ['show' => 'show_name', 'require' => 'require_name'],
                'email' => ['show' => 'show_email', 'require' => 'require_email'],
                'phone' => ['show' => 'show_phone', 'require' => 'require_phone'],
                'message' => ['show' => 'show_message', 'require' => 'require_message'],
            ] as $field => $config): ?>
                <div class="col-12 col-lg-6">
                    <div class="form-group custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="<?= 'lead_funnel_' . $config['show'] . '_' . $row->biolink_block_id ?>" name="<?= $config['show'] ?>" <?= !empty($row->settings->{$config['show']}) ? 'checked="checked"' : null ?> />
                        <label class="custom-control-label" for="<?= 'lead_funnel_' . $config['show'] . '_' . $row->biolink_block_id ?>"><?= l('biolink_lead_funnel.' . $config['show']) ?></label>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="form-group custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="<?= 'lead_funnel_' . $config['require'] . '_' . $row->biolink_block_id ?>" name="<?= $config['require'] ?>" <?= !empty($row->settings->{$config['require']}) ? 'checked="checked"' : null ?> />
                        <label class="custom-control-label" for="<?= 'lead_funnel_' . $config['require'] . '_' . $row->biolink_block_id ?>"><?= l('biolink_lead_funnel.' . $config['require']) ?></label>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_name_placeholder_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.name_placeholder') ?></label>
            <input id="<?= 'lead_funnel_name_placeholder_' . $row->biolink_block_id ?>" type="text" name="name_placeholder" class="form-control" value="<?= $row->settings->name_placeholder ?? '' ?>" maxlength="64" />
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_email_placeholder_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-envelope fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.email_placeholder') ?></label>
            <input id="<?= 'lead_funnel_email_placeholder_' . $row->biolink_block_id ?>" type="text" name="email_placeholder" class="form-control" value="<?= $row->settings->email_placeholder ?? '' ?>" maxlength="64" />
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_phone_placeholder_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-phone fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.phone_placeholder') ?></label>
            <input id="<?= 'lead_funnel_phone_placeholder_' . $row->biolink_block_id ?>" type="text" name="phone_placeholder" class="form-control" value="<?= $row->settings->phone_placeholder ?? '' ?>" maxlength="64" />
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_message_placeholder_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-comment-dots fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.message_placeholder') ?></label>
            <input id="<?= 'lead_funnel_message_placeholder_' . $row->biolink_block_id ?>" type="text" name="message_placeholder" class="form-control" value="<?= $row->settings->message_placeholder ?? '' ?>" maxlength="128" />
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_button_text_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-square fa-sm text-muted mr-1"></i> <?= l('biolink_link.button_text') ?></label>
            <input id="<?= 'lead_funnel_button_text_' . $row->biolink_block_id ?>" type="text" name="button_text" class="form-control" value="<?= $row->settings->button_text ?? '' ?>" maxlength="64" required="required" />
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_success_text_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-check-circle fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.success_text') ?></label>
            <input id="<?= 'lead_funnel_success_text_' . $row->biolink_block_id ?>" type="text" name="success_text" class="form-control" value="<?= $row->settings->success_text ?? '' ?>" maxlength="256" required="required" />
        </div>

        <div class="form-group custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="<?= 'lead_funnel_show_agreement_' . $row->biolink_block_id ?>" name="show_agreement" <?= !empty($row->settings->show_agreement) ? 'checked="checked"' : null ?> />
            <label class="custom-control-label" for="<?= 'lead_funnel_show_agreement_' . $row->biolink_block_id ?>"><?= l('biolink_lead_funnel.show_agreement') ?></label>
            <div><small class="form-text text-muted"><?= l('biolink_lead_funnel.show_agreement_help') ?></small></div>
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_agreement_text_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-align-left fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.agreement_text') ?></label>
            <textarea id="<?= 'lead_funnel_agreement_text_' . $row->biolink_block_id ?>" name="agreement_text" class="form-control" rows="2" maxlength="10000" data-lead-funnel-rich-text data-lead-funnel-rich-text-inline><?= bootstrap_to_quilljs($row->settings->agreement_text ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_agreement_url_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-external-link-alt fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.agreement_url') ?></label>
            <input id="<?= 'lead_funnel_agreement_url_' . $row->biolink_block_id ?>" type="text" name="agreement_url" class="form-control" value="<?= $row->settings->agreement_url ?? '' ?>" placeholder="<?= l('global.url_placeholder') ?>" maxlength="2048" />
        </div>
    </div>

    <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'lead_funnel_thank_you_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'lead_funnel_thank_you_container_' . $row->biolink_block_id ?>">
        <i class="fas fa-fw fa-heart fa-sm mr-1"></i> <?= l('biolink_lead_funnel.thank_you_header') ?>
    </button>

    <div class="collapse" data-parent="<?= '#update_biolink_block_' . $row->biolink_block_id ?>" id="<?= 'lead_funnel_thank_you_container_' . $row->biolink_block_id ?>">
        <div class="form-group">
            <label for="<?= 'lead_funnel_thank_you_type_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-wand-magic-sparkles fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.thank_you_type') ?></label>
            <select id="<?= 'lead_funnel_thank_you_type_' . $row->biolink_block_id ?>" name="thank_you_type" class="custom-select" data-is-not-custom-select>
                <?php foreach(['message', 'external_url', 'biolink_redirect', 'file_download'] as $thank_you_type): ?>
                    <option value="<?= $thank_you_type ?>" <?= ($row->settings->thank_you_type ?? 'message') == $thank_you_type ? 'selected="selected"' : null ?>><?= l('biolink_lead_funnel.thank_you_type_' . $thank_you_type) ?></option>
                <?php endforeach ?>
            </select>
        </div>

        <?php /* Custom code: FC-2026-03-23: conditional thank you settings */ ?>
        <div class="form-group" data-lead-funnel-thank-you-setting="message">
            <label for="<?= 'lead_funnel_thank_you_title_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-heading fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.thank_you_title') ?></label>
            <textarea id="<?= 'lead_funnel_thank_you_title_' . $row->biolink_block_id ?>" name="thank_you_title" class="form-control" rows="2" maxlength="10000" data-lead-funnel-rich-text data-lead-funnel-rich-text-inline><?= bootstrap_to_quilljs($row->settings->thank_you_title ?? '') ?></textarea>
        </div>

        <div class="form-group" data-lead-funnel-thank-you-setting="message">
            <label for="<?= 'lead_funnel_thank_you_text_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-align-left fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.thank_you_text') ?></label>
            <textarea id="<?= 'lead_funnel_thank_you_text_' . $row->biolink_block_id ?>" name="thank_you_text" class="form-control" rows="3" maxlength="10000" data-lead-funnel-rich-text><?= bootstrap_to_quilljs($row->settings->thank_you_text ?? '') ?></textarea>
        </div>

        <div class="form-group" data-lead-funnel-thank-you-setting="external_url">
            <label for="<?= 'lead_funnel_thank_you_url_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-link fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.thank_you_url') ?></label>
            <input id="<?= 'lead_funnel_thank_you_url_' . $row->biolink_block_id ?>" type="url" name="thank_you_url" class="form-control" value="<?= $row->settings->thank_you_url ?? '' ?>" placeholder="<?= l('global.url_placeholder') ?>" maxlength="2048" />
        </div>

        <div class="form-group" data-lead-funnel-thank-you-setting="biolink_redirect">
            <label for="<?= 'lead_funnel_thank_you_biolink_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-window-restore fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.thank_you_biolink') ?></label>
            <select id="<?= 'lead_funnel_thank_you_biolink_' . $row->biolink_block_id ?>" name="thank_you_biolink_id" class="custom-select" data-is-not-custom-select>
                <option value=""><?= l('global.choose') ?></option>
                <?php foreach($data->user_biolinks ?? [] as $biolink): ?>
                    <option value="<?= $biolink->link_id ?>" <?= (int) ($row->settings->thank_you_biolink_id ?? 0) == (int) $biolink->link_id ? 'selected="selected"' : null ?>><?= $biolink->url ?></option>
                <?php endforeach ?>
            </select>
        </div>

        <div class="form-group" data-lead-funnel-thank-you-setting="file_download">
            <label for="<?= 'lead_funnel_thank_you_file_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-file-arrow-down fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.thank_you_file') ?></label>
            <div class="row">
                <div class="col">
                    <input id="<?= 'lead_funnel_thank_you_file_' . $row->biolink_block_id ?>" type="file" name="thank_you_file" accept="<?= \Altum\Uploads::array_to_list_format($data->biolink_blocks['lead_funnel']['whitelisted_file_extensions']) ?>" class="form-control-file altum-file-input" />
                </div>

                <div class="col-3 <?= !empty($row->settings->thank_you_file) ? null : 'd-none' ?>">
                    <a href="<?= !empty($row->settings->thank_you_file) ? \Altum\Uploads::get_full_url('files') . $row->settings->thank_you_file : '#' ?>" target="_blank" data-toggle="tooltip" title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                        <div class="card h-100 d-flex justify-content-center align-items-center bg-gray-100">
                            <div class="card-body">
                                <i class="fas fa-fw fa-external-link"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::array_to_list_format($data->biolink_blocks['lead_funnel']['whitelisted_file_extensions'])) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->file_size_limit) ?></small>
        </div>

        <div class="form-group" data-lead-funnel-thank-you-setting="file_download">
            <label for="<?= 'lead_funnel_thank_you_button_text_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-square fa-sm text-muted mr-1"></i> <?= l('biolink_lead_funnel.thank_you_button_text') ?></label>
            <input id="<?= 'lead_funnel_thank_you_button_text_' . $row->biolink_block_id ?>" type="text" name="thank_you_button_text" class="form-control" value="<?= $row->settings->thank_you_button_text ?? '' ?>" maxlength="64" />
        </div>
        <?php /* /Custom code: FC-2026-03-23 */ ?>
    </div>

    <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'lead_funnel_data_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'lead_funnel_data_container_' . $row->biolink_block_id ?>">
        <i class="fas fa-fw fa-database fa-sm mr-1"></i> <?= l('biolink_block.data_header') ?>
    </button>

    <div class="collapse" data-parent="<?= '#update_biolink_block_' . $row->biolink_block_id ?>" id="<?= 'lead_funnel_data_container_' . $row->biolink_block_id ?>">
        <div class="alert alert-info">
            <i class="fas fa-fw fa-sm fa-info-circle mr-1"></i> <?= sprintf(l('biolink_block.data_help'), '<a href="' . url('data') . '">', '</a>') ?>
        </div>

        <div class="form-group">
            <div class="d-flex flex-wrap flex-row justify-content-between">
                <label><i class="fas fa-fw fa-sm fa-bell text-muted mr-1"></i> <?= l('biolink_block.notifications') ?></label>
                <a href="<?= url('notification-handler-create') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('notification_handlers.create') ?></a>
            </div>
            <div class="mb-2"><small class="text-muted"><?= l('biolink_block.notifications_help') ?></small></div>

            <div class="row">
                <?php foreach($data->notification_handlers as $notification_handler): ?>
                    <div class="col-12 col-lg-6">
                        <div class="custom-control custom-checkbox my-2">
                            <input id="<?= 'lead_funnel_notifications_' . $notification_handler->notification_handler_id . '_' . $row->biolink_block_id ?>" name="notifications[]" value="<?= $notification_handler->notification_handler_id ?>" type="checkbox" class="custom-control-input" <?= in_array($notification_handler->notification_handler_id, $row->settings->notifications ?? []) ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="<?= 'lead_funnel_notifications_' . $notification_handler->notification_handler_id . '_' . $row->biolink_block_id ?>">
                                <span class="mr-1"><?= $notification_handler->name ?></span>
                                <small class="badge badge-light badge-pill"><?= l('notification_handlers.type_' . $notification_handler->type) ?></small>
                            </label>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>

    <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'lead_funnel_performance_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'lead_funnel_performance_container_' . $row->biolink_block_id ?>">
        <i class="fas fa-fw fa-chart-line fa-sm mr-1"></i> <?= l('biolink_lead_funnel.performance_header') ?>
    </button>

    <div class="collapse" data-parent="<?= '#update_biolink_block_' . $row->biolink_block_id ?>" id="<?= 'lead_funnel_performance_container_' . $row->biolink_block_id ?>">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                    <div class="pr-lg-4 mb-3 mb-lg-0">
                        <h2 class="h5 mb-2"><?= l('funnels_analytics.analytics_moved') ?></h2>
                        <p class="text-muted mb-0" style="max-width: 40rem;"><?= l('funnels_analytics.analytics_moved_help') ?></p>
                    </div>

                    <div class="d-flex flex-wrap mt-2 mt-lg-0">
                        <a href="<?= $lead_funnel_analytics_url ?>" class="btn btn-primary btn-sm mr-2 mb-2"><i class="fas fa-fw fa-filter fa-sm mr-1"></i> <?= l('biolink_lead_funnel.open_analytics_page') ?></a>
                        <a href="<?= $lead_funnel_statistics_url ?>" class="btn btn-outline-secondary btn-sm mr-2 mb-2"><i class="fas fa-fw fa-chart-bar fa-sm mr-1"></i> <?= l('biolink_lead_funnel.open_statistics') ?></a>
                        <a href="<?= $lead_funnel_data_url ?>" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-fw fa-database fa-sm mr-1"></i> <?= l('biolink_lead_funnel.open_data') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'button_settings_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'button_settings_container_' . $row->biolink_block_id ?>">
        <i class="fas fa-fw fa-square-check fa-sm mr-1"></i> <?= l('biolink_link.button_header') ?>
    </button>

    <div class="collapse" data-parent="<?= '#update_biolink_block_' . $row->biolink_block_id ?>" id="<?= 'button_settings_container_' . $row->biolink_block_id ?>">
        <div class="form-group">
            <label for="<?= 'lead_funnel_name_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('biolink_link.name') ?></label>
            <input id="<?= 'lead_funnel_name_' . $row->biolink_block_id ?>" type="text" name="name" class="form-control" value="<?= $row->settings->name ?>" maxlength="128" required="required" />
        </div>

        <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= settings()->links->thumbnail_image_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->links->thumbnail_image_size_limit) ?>">
            <label for="<?= 'lead_funnel_image_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('biolink_link.image') ?></label>
            <?= include_view(THEME_PATH . 'views/partials/custom_file_image_input.php', [
                'id'=> 'lead_funnel_image_' . $row->biolink_block_id,
                'uploads_file_key' => 'block_thumbnail_images',
                'file_key' => 'image',
                'already_existing_image' => $row->settings->image,
                'image_container' => 'image',
                'accept' => \Altum\Uploads::array_to_list_format($data->biolink_blocks['lead_funnel']['whitelisted_thumbnail_image_extensions']),
                'input_data' => 'data-crop data-aspect-ratio="1"',
            ]) ?>
            <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::array_to_list_format($data->biolink_blocks['lead_funnel']['whitelisted_thumbnail_image_extensions'])) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->thumbnail_image_size_limit) ?></small>
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_icon_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-icons fa-sm text-muted mr-1"></i> <?= l('global.icon') ?></label>
            <input id="<?= 'lead_funnel_icon_' . $row->biolink_block_id ?>" type="text" name="icon" class="form-control" value="<?= $row->settings->icon ?? '' ?>" placeholder="<?= l('global.icon_placeholder') ?>" />
            <small class="form-text text-muted"><?= l('global.icon_help') ?></small>
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_text_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-paint-brush fa-sm text-muted mr-1"></i> <?= l('biolink_link.text_color') ?></label>
            <input id="<?= 'lead_funnel_text_color_' . $row->biolink_block_id ?>" type="hidden" name="text_color" class="form-control" value="<?= $row->settings->text_color ?? '#000000' ?>" required="required" />
            <div class="text_color_pickr"></div>
        </div>

        <div class="form-group">
            <label for="<?= 'block_text_alignment_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-align-center fa-sm text-muted mr-1"></i> <?= l('biolink_link.text_alignment') ?></label>
            <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                <?php foreach(['center', 'justify', 'left', 'right'] as $text_alignment): ?>
                    <div class="p-2 col-6">
                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->text_alignment ?? null) == $text_alignment ? 'active' : null ?>">
                            <input type="radio" name="text_alignment" value="<?= $text_alignment ?>" class="custom-control-input" <?= ($row->settings->text_alignment ?? null) == $text_alignment ? 'checked="checked"' : null ?> />
                            <i class="fas fa-fw fa-align-<?= $text_alignment ?> fa-sm mr-1"></i> <?= l('biolink_link.text_alignment.' . $text_alignment) ?>
                        </label>
                    </div>
                <?php endforeach ?>
            </div>
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_background_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-fill fa-sm text-muted mr-1"></i> <?= l('biolink_link.background_color') ?></label>
            <input id="<?= 'lead_funnel_background_color_' . $row->biolink_block_id ?>" type="hidden" name="background_color" class="form-control" value="<?= $row->settings->background_color ?? '#ffffff' ?>" required="required" />
            <div class="background_color_pickr"></div>
        </div>

        <div class="form-group">
            <label for="<?= 'lead_funnel_animation_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-film fa-sm text-muted mr-1"></i> <?= l('biolink_link.animation') ?></label>
            <select id="<?= 'lead_funnel_animation_' . $row->biolink_block_id ?>" name="animation" class="custom-select" data-is-not-custom-select>
                <option value="false" <?= empty($row->settings->animation) ? 'selected="selected"' : null ?>><?= l('global.none') ?></option>
                <?php foreach(require APP_PATH . 'includes/biolink_animations.php' as $animation): ?>
                    <option value="<?= $animation ?>" <?= ($row->settings->animation ?? null) == $animation ? 'selected="selected"' : null ?>><?= $animation ?></option>
                <?php endforeach ?>
            </select>
        </div>

        <div class="form-group" data-animation="<?= implode(',', require APP_PATH . 'includes/biolink_animations.php') ?>">
            <label for="<?= 'lead_funnel_animation_runs_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-play-circle fa-sm text-muted mr-1"></i> <?= l('biolink_link.animation_runs') ?></label>
            <select id="<?= 'lead_funnel_animation_runs_' . $row->biolink_block_id ?>" name="animation_runs" class="custom-select" data-is-not-custom-select>
                <option value="repeat-1" <?= ($row->settings->animation_runs ?? 'repeat-1') == 'repeat-1' ? 'selected="selected"' : null ?>>1</option>
                <option value="repeat-2" <?= ($row->settings->animation_runs ?? null) == 'repeat-2' ? 'selected="selected"' : null ?>>2</option>
                <option value="repeat-3" <?= ($row->settings->animation_runs ?? null) == 'repeat-3' ? 'selected="selected"' : null ?>>3</option>
                <option value="infinite" <?= ($row->settings->animation_runs ?? null) == 'infinite' ? 'selected="selected"' : null ?>><?= l('biolink_link.animation_runs_infinite') ?></option>
            </select>
        </div>

        <div class="form-group">
            <label for="<?= 'link_columns_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-grip fa-sm text-muted mr-1"></i> <?= l('biolink_link.columns') ?></label>
            <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                <div class="p-2 col-12 col-lg-6 h-100">
                    <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->columns ?? 1) == '1' ? 'active' : null ?>">
                        <input type="radio" name="columns" value="1" class="custom-control-input" <?= ($row->settings->columns ?? 1) == '1' ? 'checked="checked"' : null ?> required="required" />
                        1
                    </label>
                </div>

                <div class="p-2 col-12 col-lg-6 h-100">
                    <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->columns ?? 1) == '2' ? 'active' : null ?>">
                        <input type="radio" name="columns" value="2" class="custom-control-input" <?= ($row->settings->columns ?? 1) == '2' ? 'checked="checked"' : null ?> required="required" />
                        2
                    </label>
                </div>
            </div>
        </div>

        <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'border_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'border_container_' . $row->biolink_block_id ?>">
            <i class="fas fa-fw fa-square-full fa-sm mr-1"></i> <?= l('biolink_link.border_header') ?>
        </button>

        <div class="collapse" data-parent="<?= '#button_settings_container_' . $row->biolink_block_id ?>" id="<?= 'border_container_' . $row->biolink_block_id ?>">
            <div class="form-group" data-range-counter data-range-counter-suffix="px">
                <label for="<?= 'block_border_width_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-border-style fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_width') ?></label>
                <input id="<?= 'block_border_width_' . $row->biolink_block_id ?>" type="range" min="0" max="5" class="form-control-range" name="border_width" value="<?= $row->settings->border_width ?? 0 ?>" required="required" />
            </div>

            <div class="form-group">
                <label for="<?= 'block_border_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-fill fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_color') ?></label>
                <input id="<?= 'block_border_color_' . $row->biolink_block_id ?>" type="hidden" name="border_color" class="form-control" value="<?= $row->settings->border_color ?? '#ffffff' ?>" required="required" />
                <div class="border_color_pickr"></div>
            </div>

            <div class="form-group">
                <label for="<?= 'block_border_radius_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-border-all fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_radius') ?></label>
                <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                    <div class="p-2 col-4">
                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->border_radius ?? null) == 'straight' ? 'active' : null ?>">
                            <input type="radio" name="border_radius" value="straight" class="custom-control-input" <?= ($row->settings->border_radius ?? null) == 'straight' ? 'checked="checked"' : null ?> />
                            <i class="fas fa-fw fa-square-full fa-sm mr-1"></i> <?= l('biolink_link.border_radius_straight') ?>
                        </label>
                    </div>
                    <div class="p-2 col-4">
                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->border_radius ?? null) == 'round' ? 'active' : null ?>">
                            <input type="radio" name="border_radius" value="round" class="custom-control-input" <?= ($row->settings->border_radius ?? null) == 'round' ? 'checked="checked"' : null ?> />
                            <i class="fas fa-fw fa-circle fa-sm mr-1"></i> <?= l('biolink_link.border_radius_round') ?>
                        </label>
                    </div>
                    <div class="p-2 col-4">
                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->border_radius ?? null) == 'rounded' ? 'active' : null ?>">
                            <input type="radio" name="border_radius" value="rounded" class="custom-control-input" <?= ($row->settings->border_radius ?? null) == 'rounded' ? 'checked="checked"' : null ?> />
                            <i class="fas fa-fw fa-square fa-sm mr-1"></i> <?= l('biolink_link.border_radius_rounded') ?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="<?= 'block_border_style_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-border-none fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_style') ?></label>
                <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                    <?php foreach(['solid', 'dashed', 'double', 'outset', 'inset'] as $border_style): ?>
                        <div class="p-2 col-4">
                            <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->border_style ?? null) == $border_style ? 'active' : null ?>">
                                <input type="radio" name="border_style" value="<?= $border_style ?>" class="custom-control-input" <?= ($row->settings->border_style ?? null) == $border_style ? 'checked="checked"' : null ?> />
                                <?= l('biolink_link.border_style_' . $border_style) ?>
                            </label>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>

        <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'border_shadow_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'border_shadow_container_' . $row->biolink_block_id ?>">
            <i class="fas fa-fw fa-cloud fa-sm mr-1"></i> <?= l('biolink_link.border_shadow_header') ?>
        </button>

        <div class="collapse" data-parent="<?= '#button_settings_container_' . $row->biolink_block_id ?>" id="<?= 'border_shadow_container_' . $row->biolink_block_id ?>">
            <div class="form-group">
                <label for="<?= 'block_border_shadow_style_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-cloud-sun fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_shadow_style') ?></label>
                <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                    <?php foreach(['none', 'subtle', 'strong', 'hard'] as $border_shadow_style): ?>
                        <div class="p-2 col-4">
                            <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->border_shadow_style ?? null) == $border_shadow_style ? 'active' : null ?>">
                                <input type="radio" name="border_shadow_style" value="<?= $border_shadow_style ?>" class="custom-control-input" <?= ($row->settings->border_shadow_style ?? null) == $border_shadow_style ? 'checked="checked"' : null ?> />
                                <?= l('biolink_link.border_shadow_style.' . $border_shadow_style) ?>
                            </label>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="form-group">
                <label for="<?= 'block_border_shadow_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-fill fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_shadow_color') ?></label>
                <input id="<?= 'block_border_shadow_color_' . $row->biolink_block_id ?>" type="hidden" name="border_shadow_color" class="form-control" value="<?= $row->settings->border_shadow_color ?? '#00000010' ?>" required="required" />
                <div class="border_shadow_color_pickr"></div>
            </div>
        </div>
    </div>

    <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'display_settings_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'display_settings_container_' . $row->biolink_block_id ?>">
        <i class="fas fa-fw fa-display fa-sm mr-1"></i> <?= l('biolink_link.display_settings_header') ?>
    </button>

    <div class="collapse" data-parent="<?= '#update_biolink_block_' . $row->biolink_block_id ?>" id="<?= 'display_settings_container_' . $row->biolink_block_id ?>">
        <div <?= $this->user->plan_settings->temporary_url_is_enabled ? null : get_plan_feature_disabled_info() ?>>
            <div class="<?= $this->user->plan_settings->temporary_url_is_enabled ? null : 'container-disabled' ?>">
                <div class="form-group custom-control custom-switch">
                    <input id="<?= 'link_schedule_' . $row->biolink_block_id ?>" name="schedule" type="checkbox" class="custom-control-input" <?= !empty($row->start_date) && !empty($row->end_date) ? 'checked="checked"' : null ?> <?= $this->user->plan_settings->temporary_url_is_enabled ? null : 'disabled="disabled"' ?>>
                    <label class="custom-control-label" for="<?= 'link_schedule_' . $row->biolink_block_id ?>"><?= l('link.settings.schedule') ?></label>
                    <small class="form-text text-muted"><?= l('link.settings.schedule_help') ?></small>
                </div>
            </div>
        </div>

        <div class="mt-3 schedule_container" style="display: none;">
            <div <?= $this->user->plan_settings->temporary_url_is_enabled ? null : get_plan_feature_disabled_info() ?>>
                <div class="<?= $this->user->plan_settings->temporary_url_is_enabled ? null : 'container-disabled' ?>">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="<?= 'link_start_date_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-hourglass-start fa-sm text-muted mr-1"></i> <?= l('link.settings.start_date') ?></label>
                                <input id="<?= 'link_start_date_' . $row->biolink_block_id ?>" type="text" class="form-control" name="start_date" value="<?= \Altum\Date::get($row->start_date, 1) ?>" placeholder="<?= l('link.settings.start_date') ?>" autocomplete="off" data-daterangepicker>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-group">
                                <label for="<?= 'link_end_date_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-hourglass-end fa-sm text-muted mr-1"></i> <?= l('link.settings.end_date') ?></label>
                                <input id="<?= 'link_end_date_' . $row->biolink_block_id ?>" type="text" class="form-control" name="end_date" value="<?= \Altum\Date::get($row->end_date, 1) ?>" placeholder="<?= l('link.settings.end_date') ?>" autocomplete="off" data-daterangepicker>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="<?= 'link_display_continents_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-earth-europe fa-sm text-muted mr-1"></i> <?= l('global.continents') ?></label>
            <select id="<?= 'link_display_continents_' . $row->biolink_block_id ?>" name="display_continents[]" class="custom-select" multiple="multiple">
                <?php foreach(get_continents_array() as $continent_code => $continent_name): ?>
                    <option value="<?= $continent_code ?>" <?= in_array($continent_code, $row->settings->display_continents ?? []) ? 'selected="selected"' : null ?>><?= $continent_name ?></option>
                <?php endforeach ?>
            </select>
            <small class="form-text text-muted"><?= l('biolink_link.settings.display_help') ?></small>
        </div>

        <div class="form-group">
            <label for="<?= 'link_display_countries_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-globe fa-sm text-muted mr-1"></i> <?= l('global.countries') ?></label>
            <select id="<?= 'link_display_countries_' . $row->biolink_block_id ?>" name="display_countries[]" class="custom-select" multiple="multiple">
                <?php foreach(get_countries_array() as $country => $country_name): ?>
                    <option value="<?= $country ?>" <?= in_array($country, $row->settings->display_countries ?? []) ? 'selected="selected"' : null ?>><?= $country_name ?></option>
                <?php endforeach ?>
            </select>
            <small class="form-text text-muted"><?= l('biolink_link.settings.display_help') ?></small>
        </div>

        <div class="form-group">
            <label for="<?= 'link_display_cities_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-sm fa-city text-muted mr-1"></i> <?= l('global.cities') ?></label>
            <input type="text" id="<?= 'link_display_cities_' . $row->biolink_block_id ?>" name="display_cities" value="<?= implode(',', $row->settings->display_cities ?? []) ?>" class="form-control" placeholder="<?= l('biolink_link.display_cities_placeholder') ?>" />
            <small class="form-text text-muted"><?= l('biolink_link.display_cities_help') ?></small>
        </div>

        <div class="form-group">
            <label for="<?= 'link_display_devices_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-laptop fa-sm text-muted mr-1"></i> <?= l('biolink_link.display_devices') ?></label>
            <select id="<?= 'link_display_devices_' . $row->biolink_block_id ?>" name="display_devices[]" class="custom-select" multiple="multiple">
                <?php foreach(['desktop', 'tablet', 'mobile'] as $device_type): ?>
                    <option value="<?= $device_type ?>" <?= in_array($device_type, $row->settings->display_devices ?? []) ? 'selected="selected"' : null ?>><?= l('global.device.' . $device_type) ?></option>
                <?php endforeach ?>
            </select>
            <small class="form-text text-muted"><?= l('biolink_link.settings.display_help') ?></small>
        </div>

        <div class="form-group">
            <label for="<?= 'link_display_operating_systems_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-server fa-sm text-muted mr-1"></i> <?= l('biolink_link.display_operating_systems') ?></label>
            <select id="<?= 'link_display_operating_systems_' . $row->biolink_block_id ?>" name="display_operating_systems[]" class="custom-select" multiple="multiple">
                <?php foreach(['iOS', 'Android', 'Windows', 'OS X', 'Linux', 'Ubuntu', 'Chrome OS'] as $os_name): ?>
                    <option value="<?= $os_name ?>" <?= in_array($os_name, $row->settings->display_operating_systems ?? []) ? 'selected="selected"' : null ?>><?= $os_name ?></option>
                <?php endforeach ?>
            </select>
            <small class="form-text text-muted"><?= l('biolink_link.settings.display_help') ?></small>
        </div>

        <div class="form-group">
            <label for="<?= 'link_display_browsers_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-window-restore fa-sm text-muted mr-1"></i> <?= l('biolink_link.display_browsers') ?></label>
            <select id="<?= 'link_display_browsers_' . $row->biolink_block_id ?>" name="display_browsers[]" class="custom-select" multiple="multiple">
                <?php foreach(['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Samsung Internet'] as $browser_name): ?>
                    <option value="<?= $browser_name ?>" <?= in_array($browser_name, $row->settings->display_browsers ?? []) ? 'selected="selected"' : null ?>><?= $browser_name ?></option>
                <?php endforeach ?>
            </select>
            <small class="form-text text-muted"><?= l('biolink_link.settings.display_help') ?></small>
        </div>

        <div class="form-group">
            <label for="<?= 'link_display_languages_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-language fa-sm text-muted mr-1"></i> <?= l('biolink_link.display_languages') ?></label>
            <select id="<?= 'link_display_languages_' . $row->biolink_block_id ?>" name="display_languages[]" class="custom-select" multiple="multiple">
                <?php foreach(get_locale_languages_array() as $locale => $language): ?>
                    <option value="<?= $locale ?>" <?= in_array($locale, $row->settings->display_languages ?? []) ? 'selected="selected"' : null ?>><?= $language ?></option>
                <?php endforeach ?>
            </select>
            <small class="form-text text-muted"><?= l('biolink_link.settings.display_help') ?></small>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('global.update') ?></button>
    </div>
</form>
<?php /* /Custom code: FC-2026-03-23 */ ?>

<?php if(!\Altum\Event::exists_content_type_key('head', 'lead_funnel_rich_text')): ?>
    <?php ob_start() ?>
    <link href="<?= ASSETS_FULL_URL . 'css/libraries/quill.snow.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
    <style>
        @font-face {
            font-family: 'Scriptorama';
            src: url('<?= ASSETS_FULL_URL . 'css/fonts/scriptorama_tradeshow_jf_regular-webfont.woff2' ?>') format('woff2'),
                 url('<?= ASSETS_FULL_URL . 'css/fonts/scriptorama_tradeshow_jf_regular-webfont.woff' ?>') format('woff');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Helvetica Neue Medium';
            src: url('<?= ASSETS_FULL_URL . 'css/fonts/helveticaneuemedium-webfont.woff2' ?>') format('woff2'),
                 url('<?= ASSETS_FULL_URL . 'css/fonts/helveticaneuemedium-webfont.woff' ?>') format('woff');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Helvetica Neue LT';
            src: url('<?= ASSETS_FULL_URL . 'css/fonts/helveticaneuelt.woff2' ?>') format('woff2'),
                 url('<?= ASSETS_FULL_URL . 'css/fonts/helveticaneuelt.woff' ?>') format('woff');
            font-weight: normal;
            font-style: normal;
        }

        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor {
            border-radius: .85rem;
            overflow: hidden;
            box-shadow: 0 .5rem 1.25rem rgba(15, 23, 42, .12);
            background: #eef2f7 !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar.ql-snow {
            background: linear-gradient(180deg, #64748b 0%, #475569 100%) !important;
            border: 1px solid rgba(148, 163, 184, .35) !important;
            border-bottom-color: rgba(15, 23, 42, .18) !important;
            padding: .45rem .55rem !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar.ql-snow .ql-formats {
            margin-right: .55rem !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar.ql-snow .ql-picker.ql-font {
            width: 14.5rem !important;
            min-width: 14.5rem !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar.ql-snow .ql-picker.ql-size {
            width: 7.25rem !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-container.ql-snow {
            background: #eef2f7 !important;
            border: 1px solid rgba(148, 163, 184, .45) !important;
            border-top: 0 !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-editor {
            min-height: 9rem;
            background: #eef2f7 !important;
            color: #0f172a !important;
            font-size: .95rem;
            line-height: 1.65;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor--inline .ql-editor {min-height: 4rem;}
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-editor.ql-blank::before {
            color: #475569 !important;
            font-style: normal;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar button,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar .ql-picker-label {
            color: #ffffff !important;
            opacity: 1 !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar button {
            border-radius: .45rem;
            transition: background .15s ease, transform .15s ease;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-stroke,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-label,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-item {
            color: #ffffff !important;
            stroke: #ffffff !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-fill {
            fill: #ffffff !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar button svg,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar .ql-picker-label svg {
            opacity: 1 !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar button:hover,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar button:focus,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-toolbar button.ql-active,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-label:hover,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-label.ql-active,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker.ql-expanded .ql-picker-label {
            background: rgba(255, 255, 255, .12) !important;
            color: #ffffff !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-label::before,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-item::before {
            color: #ffffff !important;
            opacity: 1 !important;
            font-weight: 600;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-label {
            display: flex !important;
            align-items: center;
            min-height: 2rem;
            padding-right: 1.5rem !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-label::before {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            width: 100%;
            max-width: 11.75rem;
            line-height: 1.2;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-options .ql-picker-item::before {
            display: block;
            white-space: nowrap;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-options {
            background: #334155 !important;
            border: 1px solid rgba(148, 163, 184, .45) !important;
            box-shadow: 0 .5rem 1.25rem rgba(15, 23, 42, .12) !important;
            border-radius: .75rem !important;
            padding: .35rem 0 !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-item {
            color: #ffffff !important;
            padding: .45rem .85rem !important;
            opacity: 1 !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-item:hover,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-item.ql-selected {
            background: rgba(255, 255, 255, .12) !important;
            color: #ffffff !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker.ql-expanded .ql-picker-label,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-label:hover,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-picker-item:hover,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-active .ql-stroke,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow button:hover .ql-stroke,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow button.ql-active .ql-stroke {
            color: #bfdbfe !important;
            stroke: #bfdbfe !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow .ql-active .ql-fill,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow button:hover .ql-fill,
        form[data-type="lead_funnel"] .lead-funnel-rich-text-editor .ql-snow button.ql-active .ql-fill {
            fill: #bfdbfe !important;
        }
        form[data-type="lead_funnel"] .lead-funnel-popup-preview-shell .input-group-text,
        form[data-type="lead_funnel"] .lead-funnel-popup-preview-shell .form-control {background: rgba(255,255,255,.92);}
        form[data-type="lead_funnel"] .ql-font-segoe-ui {font-family: 'Segoe UI', sans-serif;}
        form[data-type="lead_funnel"] .ql-font-roboto {font-family: 'Roboto', sans-serif;}
        form[data-type="lead_funnel"] .ql-font-scriptorama {font-family: 'Scriptorama', sans-serif;}
        form[data-type="lead_funnel"] .ql-font-helvetica-neue-medium {font-family: 'Helvetica Neue Medium', sans-serif;}
        form[data-type="lead_funnel"] .ql-font-helvetica-neue-lt {font-family: 'Helvetica Neue LT', sans-serif;}
        form[data-type="lead_funnel"] .ql-content .ql-size-small {font-size: .75em;}
        form[data-type="lead_funnel"] .ql-content .ql-size-large {font-size: 1.5em;}
        form[data-type="lead_funnel"] .ql-content .ql-size-huge {font-size: 2.5em;}
        form[data-type="lead_funnel"] .ql-content .ql-align-center {text-align: center;}
        form[data-type="lead_funnel"] .ql-content .ql-align-right {text-align: right;}
        form[data-type="lead_funnel"] .ql-content .ql-align-justify {text-align: justify;}
        form[data-type="lead_funnel"] .ql-picker.ql-size .ql-picker-label::before,
        form[data-type="lead_funnel"] .ql-picker.ql-size .ql-picker-item::before {content: 'Normalno';}
        form[data-type="lead_funnel"] .ql-picker.ql-size .ql-picker-label[data-value="small"]::before,
        form[data-type="lead_funnel"] .ql-picker.ql-size .ql-picker-item[data-value="small"]::before {content: 'Malo';}
        form[data-type="lead_funnel"] .ql-picker.ql-size .ql-picker-label[data-value="large"]::before,
        form[data-type="lead_funnel"] .ql-picker.ql-size .ql-picker-item[data-value="large"]::before {content: 'Veliko';}
        form[data-type="lead_funnel"] .ql-picker.ql-size .ql-picker-label[data-value="huge"]::before,
        form[data-type="lead_funnel"] .ql-picker.ql-size .ql-picker-item[data-value="huge"]::before {content: 'Ogromno';}
        form[data-type="lead_funnel"] .ql-picker.ql-font .ql-picker-label[data-value="segoe-ui"]::before,
        form[data-type="lead_funnel"] .ql-picker.ql-font .ql-picker-item[data-value="segoe-ui"]::before {content: 'Segoe UI'; font-family: 'Segoe UI', sans-serif;}
        form[data-type="lead_funnel"] .ql-picker.ql-font .ql-picker-label[data-value="roboto"]::before,
        form[data-type="lead_funnel"] .ql-picker.ql-font .ql-picker-item[data-value="roboto"]::before {content: 'Roboto'; font-family: 'Roboto', sans-serif;}
        form[data-type="lead_funnel"] .ql-picker.ql-font .ql-picker-label[data-value="scriptorama"]::before,
        form[data-type="lead_funnel"] .ql-picker.ql-font .ql-picker-item[data-value="scriptorama"]::before {content: 'Scriptorama'; font-family: 'Scriptorama', sans-serif;}
        form[data-type="lead_funnel"] .ql-picker.ql-font .ql-picker-label[data-value="helvetica-neue-medium"]::before {content: 'Helvetica Med'; font-family: 'Helvetica Neue Medium', sans-serif;}
        form[data-type="lead_funnel"] .ql-picker.ql-font .ql-picker-item[data-value="helvetica-neue-medium"]::before {content: 'Helvetica Neue Medium'; font-family: 'Helvetica Neue Medium', sans-serif;}
        form[data-type="lead_funnel"] .ql-picker.ql-font .ql-picker-label[data-value="helvetica-neue-lt"]::before {content: 'Helvetica LT'; font-family: 'Helvetica Neue LT', sans-serif;}
        form[data-type="lead_funnel"] .ql-picker.ql-font .ql-picker-item[data-value="helvetica-neue-lt"]::before {content: 'Helvetica Neue LT'; font-family: 'Helvetica Neue LT', sans-serif;}
        form[data-type="lead_funnel"] .ql-content p:last-child {margin-bottom: 0;}
        form[data-type="lead_funnel"] .ql-content li[data-list="bullet"] {list-style-type: disc;}
    </style>
    <?php \Altum\Event::add_content(ob_get_clean(), 'head', 'lead_funnel_rich_text') ?>
<?php endif ?>

<?php if(!\Altum\Event::exists_content_type_key('javascript', 'lead_funnel_rich_text')): ?>
    <?php ob_start() ?>
    <script src="<?= ASSETS_FULL_URL . 'js/libraries/quill.min.js?v=' . PRODUCT_CODE ?>"></script>

    <script>
        'use strict';

        window.initLeadFunnelRichTextEditors = window.initLeadFunnelRichTextEditors || (root => {
            if(typeof Quill === 'undefined') {
                return;
            }

            const Font = Quill.import('formats/font');
            Font.whitelist = ['segoe-ui', 'roboto', 'scriptorama', 'helvetica-neue-medium', 'helvetica-neue-lt'];
            Quill.register(Font, true);

            (root || document).querySelectorAll('textarea[data-lead-funnel-rich-text]').forEach(textarea_element => {
                if(textarea_element.dataset.leadFunnelRichTextInitialized) {
                    return;
                }

                textarea_element.dataset.leadFunnelRichTextInitialized = 'true';
                textarea_element.style.display = 'none';

                const quill_container = document.createElement('div');
                quill_container.classList.add('lead-funnel-rich-text-editor');
                if(textarea_element.hasAttribute('data-lead-funnel-rich-text-inline')) {
                    quill_container.classList.add('lead-funnel-rich-text-editor--inline');
                }
                textarea_element.parentNode.insertBefore(quill_container, textarea_element.nextSibling);

                const quill_editor = new Quill(quill_container, {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{font: Font.whitelist}, {size: ['small', false, 'large', 'huge']}],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{color: []}, {background: []}],
                            [{align: []}],
                            [{list: 'ordered'}, {list: 'bullet'}],
                            ['blockquote', 'link'],
                            ['clean']
                        ]
                    }
                });

                textarea_element.leadFunnelQuillEditor = quill_editor;

                quill_editor.root.innerHTML = textarea_element.value || '';

                const sync_content = () => {
                    textarea_element.value = quill_editor.root.innerHTML;
                    textarea_element.dispatchEvent(new Event('input', {bubbles: true}));
                    textarea_element.dispatchEvent(new Event('change', {bubbles: true}));
                };

                textarea_element.leadFunnelSyncContent = sync_content;

                quill_editor.on('text-change', sync_content);
                textarea_element.closest('form')?.addEventListener('submit', sync_content);
                sync_content();
            });
        });

        /* Custom code: FC-2026-03-23: force rich text sync before AJAX FormData collection */
        window.syncLeadFunnelRichTextEditors = window.syncLeadFunnelRichTextEditors || (root => {
            (root || document).querySelectorAll('textarea[data-lead-funnel-rich-text]').forEach(textarea_element => {
                if(typeof textarea_element.leadFunnelSyncContent === 'function') {
                    textarea_element.leadFunnelSyncContent();
                }
            });
        });

        window.getLeadFunnelRichTextValues = window.getLeadFunnelRichTextValues || (root => {
            const values = {};

            (root || document).querySelectorAll('textarea[data-lead-funnel-rich-text][name]').forEach(textarea_element => {
                if(textarea_element.leadFunnelQuillEditor) {
                    values[textarea_element.getAttribute('name')] = textarea_element.leadFunnelQuillEditor.root.innerHTML;
                }
            });

            return values;
        });
        /* /Custom code: FC-2026-03-23 */

    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript', 'lead_funnel_rich_text') ?>
<?php endif ?>