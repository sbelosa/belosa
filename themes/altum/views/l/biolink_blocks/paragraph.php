<?php defined('ALTUMCODE') || die() ?>

<?php
if(empty($data->link->settings->border_shadow_style)) {
    $data->link->settings->border_shadow_style = 'none';
}

$font_size = in_array((int) ($data->link->settings->font_size ?? 16), range(12, 24), true) ? (int) $data->link->settings->font_size : 16;
$text_alignment = in_array($data->link->settings->text_alignment ?? 'center', ['center', 'left', 'right', 'justify']) ? $data->link->settings->text_alignment : 'center';
$paragraph_text_color = verify_hex_color($data->link->settings->text_color ?? null) ? $data->link->settings->text_color : '#FFFFFF';
?>

<div id="<?= 'biolink_block_id_' . $data->link->biolink_block_id ?>" data-biolink-block-id="<?= $data->link->biolink_block_id ?>" data-biolink-block-type="<?= $data->link->type ?>" class="col-12 my-<?= $data->biolink->settings->block_spacing ?? '2' ?>">
    <div class="card <?= 'link-btn-' . $data->link->settings->border_radius . ' large' ?>" style="<?= 'border-width: ' . ($data->link->settings->border_width ?? '1') . 'px;' . 'border-color: ' . (empty($data->link->settings->border_color) ? 'transparent' : $data->link->settings->border_color) . ';' . 'border-style: ' . ($data->link->settings->border_style ?? 'solid') . ';' . 'background: ' . ($data->link->settings->background_color ?? 'transparent') . ';' . \Altum\Link::get_processed_box_shadow_style($data->link->settings) ?>" data-border-width data-border-radius data-border-style data-border-color data-border-shadow data-background-color>
        <div class="<?= $data->link->settings->border_width == 0 && in_array($data->link->settings->background_color, ['#00000000', '#FFFFFF00']) && in_array($data->link->settings->border_shadow_color, ['#00000000', '#FFFFFF00']) ? null : 'card-body' ?> paragraph-block-text" style="color: <?= $paragraph_text_color ?>; --paragraph-text-color: <?= $paragraph_text_color ?>; font-size: <?= $font_size ?>px; text-align: <?= $text_alignment ?>;" data-text data-text-color data-font-size data-text-alignment>

            <div class="ql-content">
                <?= $data->link->settings->text ?>
            </div>

        </div>
    </div>
</div>

<style>
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text .ql-content,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text .ql-content p,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text .ql-content li,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text .ql-content blockquote,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text .ql-content span,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text .ql-content strong,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text .ql-content em,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text .ql-content u,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text .ql-content s,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> .paragraph-block-text .ql-content a {
        word-break: normal !important;
        overflow-wrap: break-word;
    }

    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> [data-text-color] .ql-content,
    #<?= 'biolink_block_id_' . $data->link->biolink_block_id ?> [data-text-color] .ql-content * {
        color: var(--paragraph-text-color) !important;
    }
</style>
