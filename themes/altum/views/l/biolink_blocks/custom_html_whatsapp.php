<?php defined('ALTUMCODE') || die() ?>

<?php
$button = $data->link->settings->title ?? ($data->link->settings->button ?? 'WhatsApp');
$phone = preg_replace('/[^\d]/', '', (string) ($data->link->settings->phone ?? ''));
$message = trim((string) ($data->link->settings->message ?? ''));
$whatsapp_query = ['phone' => $phone];

if($message !== '') {
    $whatsapp_query['text'] = $message;
}

$whatsapp_url = 'https://api.whatsapp.com/send?' . http_build_query($whatsapp_query);
$button_icon = $data->link->settings->icon ?? 'fab fa-whatsapp';
$button_border_radius = $data->link->settings->border_radius ?? 'rounded';

$whatsapp_link_style = \Altum\Link::get_processed_link_style($data->link->settings);
if(!empty($data->link->settings->border_shadow_style) && $data->link->settings->border_shadow_style !== 'none') {
    $whatsapp_link_style['style'] .= \Altum\Link::get_processed_box_shadow_style($data->link->settings);
}

$button_style = $whatsapp_link_style['style'];
$button_extra_class = $whatsapp_link_style['class'];
$hover_class = ($data->biolink->settings->hover_animation ?? 'smooth') != 'false' ? 'link-hover-animation-' . ($data->biolink->settings->hover_animation ?? 'smooth') : null;
?>

<div id="<?= 'biolink_block_id_' . $data->link->biolink_block_id ?>" data-biolink-block-id="<?= $data->link->biolink_block_id ?>" data-biolink-block-type="<?= $data->link->type ?>" class="col-12 my-<?= $data->biolink->settings->block_spacing ?? '2' ?>">
    <a class="btn btn-block btn-primary link-btn <?= $hover_class ?> <?= 'link-btn-' . $button_border_radius ?> <?= $button_extra_class ?>"
       href="<?= $whatsapp_url ?>"
       target="_blank"
       rel="noopener noreferrer"
       style="<?= $button_style ?>"
       data-text-color data-border-width data-border-radius data-border-style data-border-color data-border-shadow data-animation data-background-color data-text-alignment>

        <span data-icon>
            <?php if($button_icon): ?>
                <i class="<?= $button_icon ?> mr-1"></i>
            <?php endif ?>
        </span>

        <span data-title><?= $button ?></span>
    </a>
</div>
