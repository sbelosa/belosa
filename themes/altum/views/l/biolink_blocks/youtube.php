<?php defined('ALTUMCODE') || die() ?>

<?php
$title = trim((string) ($data->link->settings->title ?? ''));
$title_color = trim((string) ($data->link->settings->text_color ?? ($data->biolink->settings->text_color ?? '#F8FAFC')));
$default_title_font_size = min(max(((int) ($data->biolink->settings->font_size ?? 16)) + 4, 12), 40);
$title_font_size = (int) ($data->link->settings->font_size ?? $default_title_font_size);
if($title_font_size < 12 || $title_font_size > 40) {
    $title_font_size = $default_title_font_size;
}
?>

<div id="<?= 'biolink_block_id_' . $data->link->biolink_block_id ?>" data-biolink-block-id="<?= $data->link->biolink_block_id ?>" data-biolink-block-type="<?= $data->link->type ?>" class="col-12 my-<?= $data->biolink->settings->block_spacing ?? '2' ?>">
    <div class="h5 text-break text-center font-weight-bold mb-3 <?= $title === '' ? 'd-none' : null ?>" style="color: <?= htmlspecialchars($title_color, ENT_QUOTES, 'UTF-8') ?>; font-size: <?= $title_font_size ?>px;" data-title-container data-text-color data-font-size>
        <span data-title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="embed-responsive embed-responsive-16by9 <?= 'link-btn-' . ($data->link->settings->border_radius ?? 'round') . ' large' ?>">
        <iframe class="embed-responsive-item" scrolling="no" frameborder="no" src="https://www.youtube-nocookie.com/embed/<?= $data->embed ?>?controls=<?= (int) $data->link->settings->video_controls ?>&autoplay=<?= (int) $data->link->settings->video_autoplay ?>&loop=<?= (int) $data->link->settings->video_loop ?>&mute=<?= (int) $data->link->settings->video_muted ?>&playlist=<?= $data->embed ?>" allow="<?= $data->link->settings->video_controls ? 'controls;' : null ?> <?= $data->link->settings->video_autoplay ? 'autoplay;' : null ?>accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
</div>
