<?php defined('ALTUMCODE') || die() ?>

<?php
$avatar_size = in_array((string) ($data->link->settings->size ?? ''), ['75', '100', '125', '150'], true) ? (int) $data->link->settings->size : 125;
$avatar_object_fit = in_array(($data->link->settings->object_fit ?? ''), ['cover', 'contain', 'fill'], true) ? $data->link->settings->object_fit : 'contain';
$avatar_border_radius = in_array(($data->link->settings->border_radius ?? ''), ['straight', 'round', 'rounded'], true) ? $data->link->settings->border_radius : 'rounded';
$avatar_image_url = get_biolink_avatar_url($data->link->settings);
$avatar_fallback_url = ASSETS_FULL_URL . 'images/forever-card-default-avatar.svg';
?>

<div id="<?= 'biolink_block_id_' . $data->link->biolink_block_id ?>" data-biolink-block-id="<?= $data->link->biolink_block_id ?>" data-biolink-block-type="<?= $data->link->type ?>" class="col-12 my-<?= $data->biolink->settings->block_spacing ?? '2' ?>">
    <div class="d-flex flex-column align-items-center">
        <?php if($data->link->location_url): ?>
        <a href="<?= $data->link->location_url . $data->link->utm_query ?>" data-track-biolink-block-id="<?= $data->link->biolink_block_id ?>" target="<?= !empty($data->link->settings->open_in_new_tab) ? '_blank' : '_self' ?>">
        <?php endif ?>

            <img src="<?= htmlspecialchars($avatar_image_url, ENT_QUOTES, 'UTF-8') ?>" data-fallback-avatar="<?= htmlspecialchars($avatar_fallback_url, ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src=this.dataset.fallbackAvatar;" class="link-image <?= 'link-avatar-' . $avatar_border_radius ?> <?= $data->link->location_url ? ($data->biolink->settings->hover_animation ?? 'smooth') != 'false' ? 'link-hover-animation-' . ($data->biolink->settings->hover_animation ?? 'smooth') : null : null ?>" style="width: <?= $avatar_size ?>px; height: <?= $avatar_size ?>px; border-width: <?= $data->link->settings->border_width ?? 0 ?>px; border-color: <?= $data->link->settings->border_color ?? '#ffffff' ?>; border-style: <?= $data->link->settings->border_style ?? 'solid' ?>; object-fit: <?= $avatar_object_fit ?>; <?= \Altum\Link::get_processed_box_shadow_style($data->link->settings) ?>" alt="<?= htmlspecialchars((string) ($data->link->settings->image_alt ?? ''), ENT_QUOTES, 'UTF-8') ?>" loading="lazy" data-border-width data-border-avatar-radius data-border-style data-border-color data-border-shadow data-avatar />

        <?php if($data->link->location_url): ?>
        </a>
        <?php endif ?>
    </div>
</div>
