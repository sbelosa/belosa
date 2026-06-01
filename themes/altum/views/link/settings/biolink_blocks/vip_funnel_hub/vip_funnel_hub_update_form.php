<?php defined('ALTUMCODE') || die() ?>
<?php
$vip_funnel_options = function_exists('vip_funnel_get_user_funnel_select_options') ? vip_funnel_get_user_funnel_select_options((int) ($row->user_id ?? $this->user->user_id ?? 0)) : [];
$selected_vip_funnel_id = (int) ($row->settings->vip_funnel_id ?? 0);
$vip_funnel_hub_render = function_exists('vip_funnel_get_public_hub_render_data') ? vip_funnel_get_public_hub_render_data((int) ($row->user_id ?? $this->user->user_id ?? 0), $row->settings ?? []) : [];
$vip_funnel_hub_path_source = $row->settings->path_tags ?? ($vip_funnel_hub_render['paths'] ?? [
    ['title' => 'Suradnja i Start paket'],
    ['title' => 'Proizvodi bez registracije'],
    ['title' => 'FCC sustav i demo'],
]);
$vip_funnel_hub_path_tags = [];

foreach((array) $vip_funnel_hub_path_source as $path_tag) {
    if(is_object($path_tag)) {
        $path_tag = (array) $path_tag;
    }

    if(is_array($path_tag)) {
        $path_tag = $path_tag['title'] ?? $path_tag['label'] ?? '';
    }

    $path_tag = input_clean((string) $path_tag, 80);

    if($path_tag === '') {
        continue;
    }

    $vip_funnel_hub_path_tags[] = $path_tag;

    if(count($vip_funnel_hub_path_tags) >= 3) {
        break;
    }
}
?>

<form id="<?= 'update_biolink_block_' . $row->biolink_block_id ?>" name="update_biolink_" method="post" role="form" data-type="<?= $row->type ?>" enctype="multipart/form-data">
    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
    <input type="hidden" name="request_type" value="update" />
    <input type="hidden" name="block_type" value="vip_funnel_hub" />
    <input type="hidden" name="biolink_block_id" value="<?= $row->biolink_block_id ?>" />

    <div class="notification-container"></div>

    <div class="alert alert-info">
        <i class="fas fa-fw fa-sm fa-circle-info mr-1"></i>
        Nakon što složiš funnel u VIP Funnel centru, ovdje odaberi koji funnel ovaj blok otvara na tvojoj FCC Aplikaciji.
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_name_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('biolink_link.name') ?></label>
        <input id="<?= 'vip_funnel_hub_name_' . $row->biolink_block_id ?>" type="text" name="name" class="form-control" value="<?= $row->settings->name ?? '' ?>" maxlength="128" required="required" />
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_vip_funnel_id_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-diagram-project fa-sm text-muted mr-1"></i> Funnel koji se otvara</label>
        <select id="<?= 'vip_funnel_hub_vip_funnel_id_' . $row->biolink_block_id ?>" name="vip_funnel_id" class="custom-select">
            <option value="0" <?= $selected_vip_funnel_id <= 0 ? 'selected="selected"' : null ?>>Primarni funnel</option>
            <?php foreach($vip_funnel_options as $option): ?>
                <option value="<?= (int) $option['id'] ?>" <?= $selected_vip_funnel_id === (int) $option['id'] ? 'selected="selected"' : null ?>>
                    <?= htmlspecialchars((string) $option['name'], ENT_QUOTES, 'UTF-8') ?> /<?= htmlspecialchars((string) $option['slug'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach ?>
        </select>
        <small class="form-text text-muted">Promjenom odabira ovaj blok može voditi na drugi funnel bez ponovnog kreiranja bloka.</small>
    </div>

    <div class="alert alert-secondary">
        <i class="fas fa-fw fa-play-circle mr-1"></i>
        Primarni klik se više ne postavlja ručno. Sustav otvara odabrani Funnel 2.0 iz popisa.
    </div>

    <div class="form-group">
        <input type="hidden" name="location_url" value="" />
        <label for="<?= 'vip_funnel_hub_secondary_url_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-link fa-sm text-muted mr-1"></i> Sekundarni CTA URL</label>
        <input id="<?= 'vip_funnel_hub_secondary_url_' . $row->biolink_block_id ?>" type="url" class="form-control" name="secondary_url" value="<?= $row->settings->secondary_url ?? '' ?>" maxlength="2048" placeholder="<?= l('global.url_placeholder') ?>" />
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_kicker_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-bullseye fa-sm text-muted mr-1"></i> Kicker</label>
        <input id="<?= 'vip_funnel_hub_kicker_' . $row->biolink_block_id ?>" type="text" class="form-control" name="kicker" value="<?= $row->settings->kicker ?? '' ?>" maxlength="80" />
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_title_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-heading fa-sm text-muted mr-1"></i> Glavni naslov</label>
        <input id="<?= 'vip_funnel_hub_title_' . $row->biolink_block_id ?>" type="text" class="form-control" name="title" value="<?= $row->settings->title ?? '' ?>" maxlength="160" required="required" />
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_subtitle_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-align-left fa-sm text-muted mr-1"></i> Podnaslov</label>
        <textarea id="<?= 'vip_funnel_hub_subtitle_' . $row->biolink_block_id ?>" name="subtitle" class="form-control" rows="4" maxlength="500"><?= $row->settings->subtitle ?? '' ?></textarea>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            <div class="form-group">
                <label for="<?= 'vip_funnel_hub_primary_cta_text_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-rectangle-list fa-sm text-muted mr-1"></i> Primarni CTA tekst</label>
                <input id="<?= 'vip_funnel_hub_primary_cta_text_' . $row->biolink_block_id ?>" type="text" class="form-control" name="primary_cta_text" value="<?= $row->settings->primary_cta_text ?? '' ?>" maxlength="120" />
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="form-group">
                <label for="<?= 'vip_funnel_hub_secondary_cta_text_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-rectangle-list fa-sm text-muted mr-1"></i> Sekundarni CTA tekst</label>
                <input id="<?= 'vip_funnel_hub_secondary_cta_text_' . $row->biolink_block_id ?>" type="text" class="form-control" name="secondary_cta_text" value="<?= $row->settings->secondary_cta_text ?? '' ?>" maxlength="120" />
            </div>
        </div>
    </div>

    <div class="form-group custom-control custom-switch">
        <input id="<?= 'vip_funnel_hub_show_paths_' . $row->biolink_block_id ?>" name="show_paths" type="checkbox" class="custom-control-input" <?= !empty($row->settings->show_paths) ? 'checked="checked"' : null ?> />
        <label class="custom-control-label" for="<?= 'vip_funnel_hub_show_paths_' . $row->biolink_block_id ?>">Prikaži glavne putove na kartici</label>
    </div>

    <div class="form-group">
        <label><i class="fas fa-fw fa-tags fa-sm text-muted mr-1"></i> Tagovi glavnih putova</label>
        <div class="row">
            <?php foreach([0, 1, 2] as $path_tag_index): ?>
                <div class="col-12 col-lg-4 mb-2 mb-lg-0">
                    <input
                        type="text"
                        name="path_tags[]"
                        class="form-control"
                        value="<?= htmlspecialchars((string) ($vip_funnel_hub_path_tags[$path_tag_index] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        maxlength="80"
                        placeholder="<?= 'Tag ' . ($path_tag_index + 1) ?>"
                    />
                </div>
            <?php endforeach ?>
        </div>
        <small class="form-text text-muted">Ovi tekstovi se prikazuju kao male oznake na kartici kada je uključen prikaz glavnih putova. Prazno polje se neće prikazati.</small>
    </div>

    <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= settings()->links->thumbnail_image_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->links->thumbnail_image_size_limit) ?>">
        <label for="<?= 'vip_funnel_hub_image_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('biolink_link.image') ?></label>
        <?= include_view(THEME_PATH . 'views/partials/custom_file_image_input.php', [
            'id'=> 'vip_funnel_hub_image_' . $row->biolink_block_id,
            'uploads_file_key' => 'block_thumbnail_images',
            'file_key' => 'image',
            'already_existing_image' => $row->settings->image ?? '',
            'image_container' => 'image',
            'accept' => \Altum\Uploads::array_to_list_format($data->biolink_blocks['vip_funnel_hub']['whitelisted_thumbnail_image_extensions']),
            'input_data' => 'data-crop data-aspect-ratio="1.33"'
        ]) ?>
        <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::array_to_list_format($data->biolink_blocks['vip_funnel_hub']['whitelisted_thumbnail_image_extensions'])) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->thumbnail_image_size_limit) ?></small>
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_icon_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-icons fa-sm text-muted mr-1"></i> <?= l('global.icon') ?></label>
        <input id="<?= 'vip_funnel_hub_icon_' . $row->biolink_block_id ?>" type="text" name="icon" class="form-control" value="<?= $row->settings->icon ?? '' ?>" placeholder="<?= l('global.icon_placeholder') ?>" />
        <small class="form-text text-muted"><?= l('global.icon_help') ?></small>
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_text_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-paint-brush fa-sm text-muted mr-1"></i> <?= l('biolink_link.text_color') ?></label>
        <input id="<?= 'vip_funnel_hub_text_color_' . $row->biolink_block_id ?>" type="hidden" name="text_color" class="form-control" value="<?= $row->settings->text_color ?? '#ffffff' ?>" required="required" />
        <div class="text_color_pickr"></div>
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_background_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-fill fa-sm text-muted mr-1"></i> <?= l('biolink_link.background_color') ?></label>
        <input id="<?= 'vip_funnel_hub_background_color_' . $row->biolink_block_id ?>" type="hidden" name="background_color" class="form-control" value="<?= $row->settings->background_color ?? '#101826' ?>" required="required" />
        <div class="background_color_pickr"></div>
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_text_alignment_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-align-center fa-sm text-muted mr-1"></i> <?= l('biolink_link.text_alignment') ?></label>
        <div class="row btn-group-toggle m-n2" data-toggle="buttons">
            <?php foreach(['left', 'center', 'right', 'justify'] as $text_alignment): ?>
                <div class="p-2 col-6">
                    <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->text_alignment ?? 'left') == $text_alignment ? 'active' : null ?>">
                        <input type="radio" name="text_alignment" value="<?= $text_alignment ?>" class="custom-control-input" <?= ($row->settings->text_alignment ?? 'left') == $text_alignment ? 'checked="checked"' : null ?> />
                        <i class="fas fa-fw fa-align-<?= $text_alignment ?> fa-sm mr-1"></i> <?= l('biolink_link.text_alignment.' . $text_alignment) ?>
                    </label>
                </div>
            <?php endforeach ?>
        </div>
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_columns_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-grip fa-sm text-muted mr-1"></i> <?= l('biolink_link.columns') ?></label>
        <div class="row btn-group-toggle m-n2" data-toggle="buttons">
            <?php foreach([1, 2] as $columns): ?>
                <div class="p-2 col-6">
                    <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= (int) ($row->settings->columns ?? 1) === $columns ? 'active' : null ?>">
                        <input type="radio" name="columns" value="<?= $columns ?>" class="custom-control-input" <?= (int) ($row->settings->columns ?? 1) === $columns ? 'checked="checked"' : null ?> required="required" />
                        <?= $columns ?>
                    </label>
                </div>
            <?php endforeach ?>
        </div>
    </div>

    <div class="form-group">
        <label for="<?= 'vip_funnel_hub_animation_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-film fa-sm text-muted mr-1"></i> <?= l('biolink_link.animation') ?></label>
        <select id="<?= 'vip_funnel_hub_animation_' . $row->biolink_block_id ?>" name="animation" class="custom-select">
            <option value="false" <?= empty($row->settings->animation) ? 'selected="selected"' : null ?>><?= l('global.none') ?></option>
            <?php foreach(require APP_PATH . 'includes/biolink_animations.php' as $animation): ?>
                <option value="<?= $animation ?>" <?= ($row->settings->animation ?? false) == $animation ? 'selected="selected"' : null ?>><?= $animation ?></option>
            <?php endforeach ?>
        </select>
    </div>

    <div class="form-group" data-animation="<?= implode(',', require APP_PATH . 'includes/biolink_animations.php') ?>">
        <label for="<?= 'vip_funnel_hub_animation_runs_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-play-circle fa-sm text-muted mr-1"></i> <?= l('biolink_link.animation_runs') ?></label>
        <select id="<?= 'vip_funnel_hub_animation_runs_' . $row->biolink_block_id ?>" name="animation_runs" class="custom-select">
            <option value="repeat-1" <?= ($row->settings->animation_runs ?? 'repeat-1') == 'repeat-1' ? 'selected="selected"' : null ?>>1</option>
            <option value="repeat-2" <?= ($row->settings->animation_runs ?? '') == 'repeat-2' ? 'selected="selected"' : null ?>>2</option>
            <option value="repeat-3" <?= ($row->settings->animation_runs ?? '') == 'repeat-3' ? 'selected="selected"' : null ?>>3</option>
            <option value="infinite" <?= ($row->settings->animation_runs ?? '') == 'infinite' ? 'selected="selected"' : null ?>><?= l('biolink_link.animation_runs_infinite') ?></option>
        </select>
    </div>

    <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'vip_funnel_hub_border_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'vip_funnel_hub_border_container_' . $row->biolink_block_id ?>">
        <i class="fas fa-fw fa-square-full fa-sm mr-1"></i> <?= l('biolink_link.border_header') ?>
    </button>

    <div class="collapse" data-parent="<?= '#update_biolink_block_' . $row->biolink_block_id ?>" id="<?= 'vip_funnel_hub_border_container_' . $row->biolink_block_id ?>">
        <div class="form-group" data-range-counter data-range-counter-suffix="px">
            <label for="<?= 'vip_funnel_hub_border_width_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-border-style fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_width') ?></label>
            <input id="<?= 'vip_funnel_hub_border_width_' . $row->biolink_block_id ?>" type="range" min="0" max="5" class="form-control-range" name="border_width" value="<?= $row->settings->border_width ?? 0 ?>" required="required" />
        </div>

        <div class="form-group">
            <label for="<?= 'vip_funnel_hub_border_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-fill fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_color') ?></label>
            <input id="<?= 'vip_funnel_hub_border_color_' . $row->biolink_block_id ?>" type="hidden" name="border_color" class="form-control" value="<?= $row->settings->border_color ?? '#101826' ?>" required="required" />
            <div class="border_color_pickr"></div>
        </div>

        <div class="form-group">
            <label for="<?= 'vip_funnel_hub_border_radius_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-border-all fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_radius') ?></label>
            <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                <?php foreach(['straight', 'round', 'rounded'] as $border_radius): ?>
                    <div class="p-2 col-4">
                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->border_radius ?? 'rounded') == $border_radius ? 'active' : null ?>">
                            <input type="radio" name="border_radius" value="<?= $border_radius ?>" class="custom-control-input" <?= ($row->settings->border_radius ?? 'rounded') == $border_radius ? 'checked="checked"' : null ?> />
                            <?= l('biolink_link.border_radius_' . $border_radius) ?>
                        </label>
                    </div>
                <?php endforeach ?>
            </div>
        </div>

        <div class="form-group mb-0">
            <label for="<?= 'vip_funnel_hub_border_style_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-border-none fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_style') ?></label>
            <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                <?php foreach(['solid', 'dashed', 'double', 'outset', 'inset'] as $border_style): ?>
                    <div class="p-2 col-4">
                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->border_style ?? 'solid') == $border_style ? 'active' : null ?>">
                            <input type="radio" name="border_style" value="<?= $border_style ?>" class="custom-control-input" <?= ($row->settings->border_style ?? 'solid') == $border_style ? 'checked="checked"' : null ?> />
                            <?= l('biolink_link.border_style_' . $border_style) ?>
                        </label>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>

    <button class="btn btn-block btn-gray-300 my-4" type="button" data-toggle="collapse" data-target="#<?= 'vip_funnel_hub_shadow_container_' . $row->biolink_block_id ?>" aria-expanded="false" aria-controls="<?= 'vip_funnel_hub_shadow_container_' . $row->biolink_block_id ?>">
        <i class="fas fa-fw fa-cloud fa-sm mr-1"></i> <?= l('biolink_link.border_shadow_header') ?>
    </button>

    <div class="collapse" data-parent="<?= '#update_biolink_block_' . $row->biolink_block_id ?>" id="<?= 'vip_funnel_hub_shadow_container_' . $row->biolink_block_id ?>">
        <div class="form-group">
            <label for="<?= 'vip_funnel_hub_border_shadow_style_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-cloud-sun fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_shadow_style') ?></label>
            <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                <?php foreach(['none', 'subtle', 'strong', 'hard'] as $shadow_style): ?>
                    <div class="p-2 col-4">
                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= ($row->settings->border_shadow_style ?? 'subtle') == $shadow_style ? 'active' : null ?>">
                            <input type="radio" name="border_shadow_style" value="<?= $shadow_style ?>" class="custom-control-input" <?= ($row->settings->border_shadow_style ?? 'subtle') == $shadow_style ? 'checked="checked"' : null ?> />
                            <?= l('biolink_link.border_shadow_style.' . $shadow_style) ?>
                        </label>
                    </div>
                <?php endforeach ?>
            </div>
        </div>

        <div class="form-group mb-0">
            <label for="<?= 'vip_funnel_hub_border_shadow_color_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-fill fa-sm text-muted mr-1"></i> <?= l('biolink_link.border_shadow_color') ?></label>
            <input id="<?= 'vip_funnel_hub_border_shadow_color_' . $row->biolink_block_id ?>" type="hidden" name="border_shadow_color" class="form-control" value="<?= $row->settings->border_shadow_color ?? '#00000010' ?>" required="required" />
            <div class="border_shadow_color_pickr"></div>
        </div>
    </div>

    <div class="mt-4 d-flex flex-wrap justify-content-between align-items-center" style="gap:.75rem;">
        <a href="<?= url('vip-funnel-studio') ?>" class="btn btn-outline-primary">
            <i class="fas fa-fw fa-diagram-project fa-sm mr-1"></i> Otvori VIP Funnel Studio
        </a>

        <button type="submit" name="submit" class="btn btn-primary" data-is-ajax><?= l('global.update') ?></button>
    </div>
</form>
