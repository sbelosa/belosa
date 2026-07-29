<?php defined('ALTUMCODE') || die() ?>
<?php $biolink_socials = require APP_PATH . 'includes/biolink_socials.php'; ?>
<?php $fcc_current_biolink_theme = !empty($this->link->biolink_theme_id) && isset($data->biolinks_themes[$this->link->biolink_theme_id]) ? $data->biolinks_themes[$this->link->biolink_theme_id] : null; ?>

<div class="modal fade" id="biolink_themes_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="modal-title">
                        <i class="fas fa-fw fa-sm fa-palette text-primary mr-2"></i>
                        <?= l('biolink_themes.header') ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="fcc-biolink-theme-intro mb-4">
                    <div class="fcc-biolink-theme-intro__content">
                        <div class="fcc-biolink-theme-intro__eyebrow"><?= l('biolink_themes.intro_title') ?></div>
                        <p class="mb-2"><?= l('biolink_themes.intro_text') ?></p>
                        <div class="fcc-biolink-theme-intro__hint">
                            <i class="fas fa-fw fa-info-circle mr-2"></i><?= l('biolink_themes.intro_hint') ?>
                        </div>
                    </div>

                    <div class="fcc-biolink-theme-current">
                        <div class="fcc-biolink-theme-current__label"><?= l('biolink_themes.current_title') ?></div>
                        <div id="fcc_biolink_theme_modal_current_name" class="fcc-biolink-theme-current__name">
                            <?= $fcc_current_biolink_theme ? $fcc_current_biolink_theme->name : l('biolink_themes.id_null') ?>
                        </div>
                        <div id="fcc_biolink_theme_modal_current_help" class="fcc-biolink-theme-current__help">
                            <?= $fcc_current_biolink_theme ? l('biolink_themes.current_active_help') : l('biolink_themes.current_custom_help') ?>
                        </div>
                        <span id="fcc_biolink_theme_modal_current_status" class="fcc-biolink-theme-current__status badge badge-pill <?= $fcc_current_biolink_theme ? 'badge-primary' : 'badge-light' ?>">
                            <?= $fcc_current_biolink_theme ? l('biolink_themes.current_active') : l('biolink_themes.current_custom') ?>
                        </span>
                    </div>
                </div>

                <div id="biolinks_themes" class="biolink-themes-wrapper row">
                    <?php foreach($data->biolinks_themes as $key => $theme): ?>
                        <?php $link_style = \Altum\Link::get_processed_link_style($theme->settings->biolink_block) ?>
                        <?php $block_shadow_style = \Altum\Link::get_processed_box_shadow_style($theme->settings->biolink_block) ?>
                        <?php $paragraph_shadow_style = \Altum\Link::get_processed_box_shadow_style($theme->settings->biolink_block_paragraph) ?>
                        <?php $fcc_theme_is_enabled_for_plan = in_array($theme->biolink_theme_id, $this->user->plan_settings->biolinks_themes ?? []); ?>

                        <label for="settings_biolink_theme_id_<?= $key ?>" class="m-0 col-12 col-md-6 col-xl-4 p-3" <?= $fcc_theme_is_enabled_for_plan ? 'data-toggle="tooltip" title="' . $theme->name . '"' : get_plan_feature_disabled_info() ?>>
                            <input type="radio" name="biolink_theme_id" value="<?= $key ?>" id="settings_biolink_theme_id_<?= $key ?>" class="d-none" data-theme-name="<?= htmlspecialchars($theme->name, ENT_QUOTES) ?>" data-theme-mode="preset" <?= $this->link->biolink_theme_id == $key ? 'checked="checked"' : null ?> />
                            <div class="link-biolink-theme card h-100 <?= $fcc_theme_is_enabled_for_plan ? null : 'container-disabled' ?>" style="<?= \Altum\Link::get_processed_background_style($theme->settings->biolink); ?>">
                                <div class="card-body flex-column d-flex justify-content-center align-items-center text-truncate">
                                    <div class="link-biolink-theme__badge"><?= l('biolink_themes.card_preset_badge') ?></div>

                                    <div class="w-100" style="cursor: not-allowed;pointer-events: none;">

                                        <div class="text-center text-truncate mb-2">
                                            <span style="color: <?= $theme->settings->biolink_block_heading->text_color ?? '#ffffff' ?>"><?= $this->link->url ?></span>
                                        </div>

                                        <div class="mb-2 text-center card <?= 'link-btn-' . $theme->settings->biolink_block_paragraph->border_radius ?>" style="<?= $link_style['style'] ?><?= 'border-width: ' . ($theme->settings->biolink_block->border_width ?? '1') . 'px;' . 'border-color: ' . (empty($theme->settings->biolink_block->border_color) ? 'transparent' : $theme->settings->biolink_block->border_color) . ';' . 'border-style: ' . ($theme->settings->biolink_block->border_style ?? 'solid') . ';' . 'background: ' . ($theme->settings->biolink_block_paragraph->background_color ?? 'transparent') . ';' . $paragraph_shadow_style ?>">
                                            <div class="<?= $theme->settings->biolink_block->border_width == 0 && in_array($theme->settings->biolink_block_paragraph->background_color, ['#00000000', '#FFFFFF00']) && in_array($theme->settings->biolink_block_paragraph->border_shadow_color, ['#00000000', '#FFFFFF00']) ? null : 'card-body p-2' ?> small text-break" style="color: <?= $theme->settings->biolink_block_paragraph->text_color ?>;">
                                                <?= l('biolink_themes.sample_description') ?>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-block btn-sm btn-primary link-btn <?= 'link-btn-' . $theme->settings->biolink_block->border_radius ?>" style="<?= $link_style['style'] . $block_shadow_style ?>">
                                            <small><?= $theme->name ?></small>
                                        </button>

                                        <button type="button" class="btn btn-block btn-sm btn-primary link-btn <?= 'link-btn-' . $theme->settings->biolink_block->border_radius ?>" style="<?= $link_style['style'] . $block_shadow_style ?>">
                                            <small><?= $theme->name ?></small>
                                        </button>

                                        <button type="button" class="btn btn-block btn-sm btn-primary link-btn <?= 'link-btn-' . $theme->settings->biolink_block->border_radius ?>" style="<?= $link_style['style'] . $block_shadow_style ?>">
                                            <small><?= $theme->name ?></small>
                                        </button>

                                        <div class="d-flex flex-wrap justify-content-center mt-2">
                                            <?php foreach(array_slice($biolink_socials, 0, 3) as $key => $value): ?>
                                                <?php if($value): ?>
                                                    <div class="my-1 mx-1 <?= 'link-btn-' . ($theme->settings->biolink_block_socials->border_radius ?? 'rounded') ?>" style="background: <?= $theme->settings->biolink_block_socials->background_color ?: '#FFFFFF00' ?>; padding: .05rem .3rem;">
                                                        <a href="#">
                                                            <i class="<?= $biolink_socials[$key]['icon'] ?> fa-xs fa-fw" style="color: <?= $theme->settings->biolink_block_socials->color ?>" data-color></i>
                                                        </a>
                                                    </div>
                                                <?php endif ?>
                                            <?php endforeach ?>
                                        </div>
                                    </div>

                                    <div class="link-biolink-theme__footer">
                                        <div class="link-biolink-theme__title"><?= $theme->name ?></div>
                                        <?php if($this->link->biolink_theme_id == $key): ?>
                                            <span class="link-biolink-theme__active-pill"><?= l('biolink_themes.current_active') ?></span>
                                        <?php endif ?>
                                    </div>

                                </div>
                            </div>
                        </label>

                    <?php endforeach ?>

                    <label for="settings_biolink_theme_id_null" class="m-0 col-12 col-md-6 col-xl-4 p-3">
                        <input type="radio" name="biolink_theme_id" value="" id="settings_biolink_theme_id_null" class="d-none" data-theme-name="<?= htmlspecialchars(l('biolink_themes.id_null'), ENT_QUOTES) ?>" data-theme-mode="custom" <?= !$this->link->biolink_theme_id ? 'checked="checked"' : null ?> />
                        <div class="link-biolink-theme link-biolink-theme-custom card h-100">
                            <div class="card-body d-flex flex-column justify-content-center align-items-start">
                                <div class="link-biolink-theme__badge"><?= l('biolink_themes.current_custom') ?></div>
                                <div class="link-biolink-theme__icon">
                                    <i class="fas fa-fw fa-sliders-h"></i>
                                </div>
                                <div class="link-biolink-theme__title mb-2"><?= l('biolink_themes.id_null') ?></div>
                                <div class="link-biolink-theme__text"><?= l('biolink_themes.card_custom_text') ?></div>
                            </div>
                        </div>
                    </label>
                </div>

            </div>
        </div>
    </div>
</div>

<?php ob_start() ?>
<script>
    'use strict';

    const fcc_biolink_theme_modal_copy = {
        active_status: <?= json_encode(l('biolink_themes.current_active')) ?>,
        custom_status: <?= json_encode(l('biolink_themes.current_custom')) ?>,
        active_help: <?= json_encode(l('biolink_themes.current_active_help')) ?>,
        custom_help: <?= json_encode(l('biolink_themes.current_custom_help')) ?>
    };

    let update_biolink_theme_modal_current = () => {
        let checked_theme = document.querySelector('#biolink_themes_modal input[name="biolink_theme_id"]:checked');

        if(!checked_theme) {
            return;
        }

        let is_custom_theme = checked_theme.dataset.themeMode === 'custom';
        let theme_name = checked_theme.dataset.themeName || <?= json_encode(l('biolink_themes.id_null')) ?>;
        let current_name = document.querySelector('#fcc_biolink_theme_modal_current_name');
        let current_help = document.querySelector('#fcc_biolink_theme_modal_current_help');
        let current_status = document.querySelector('#fcc_biolink_theme_modal_current_status');

        if(current_name) {
            current_name.textContent = theme_name;
        }

        if(current_help) {
            current_help.textContent = is_custom_theme ? fcc_biolink_theme_modal_copy.custom_help : fcc_biolink_theme_modal_copy.active_help;
        }

        if(current_status) {
            current_status.textContent = is_custom_theme ? fcc_biolink_theme_modal_copy.custom_status : fcc_biolink_theme_modal_copy.active_status;
            current_status.classList.toggle('badge-light', is_custom_theme);
            current_status.classList.toggle('badge-primary', !is_custom_theme);
        }
    };

    window.update_biolink_theme_modal_current = update_biolink_theme_modal_current;
    update_biolink_theme_modal_current();

    $('#biolink_themes_modal').on('shown.bs.modal', () => {
        update_biolink_theme_modal_current();
    });

    document.querySelectorAll('#biolink_themes_modal input[name="biolink_theme_id"]').forEach(element => {
        element.addEventListener('change', event => {
            document.querySelector('#biolink_theme_id').value = element.value;
            document.querySelector('#biolink_theme_action').value = element.value ? 'select' : 'disable';
            document.querySelector('#biolink_theme_override_fields').value = '';
            update_biolink_theme_modal_current();
            if(window.update_biolink_theme_summary) {
                window.update_biolink_theme_summary();
            }
            $('#biolink_themes_modal').modal('hide');
            biolink_theme_preview();
        });
    })
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
