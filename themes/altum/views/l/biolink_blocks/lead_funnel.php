<?php defined('ALTUMCODE') || die() ?>

<?php /* Custom code: FC-2026-03-23: lead funnel block phase 1 */ ?>
<?php
$embed_url = null;
$open_mode = $data->link->settings->open_mode ?? 'popup';
/* Custom code: FC-2026-03-23: lead funnel page url fallback */
$lead_funnel_url = $data->link->full_url ?? url('l/link?biolink_block_id=' . $data->link->biolink_block_id);
/* /Custom code: FC-2026-03-23 */
/* Custom code: FC-2026-03-23: popup style variables */
$lead_funnel_popup_style = sprintf(
    '--lead-funnel-background-color:%s;--lead-funnel-text-color:%s;--lead-funnel-button-background-color:%s;--lead-funnel-button-text-color:%s;',
    $data->link->settings->popup_background_color ?? '#ffffff',
    $data->link->settings->popup_text_color ?? '#212529',
    $data->link->settings->popup_button_background_color ?? '#007bff',
    $data->link->settings->popup_button_text_color ?? '#ffffff'
);
/* /Custom code: FC-2026-03-23 */

if(!empty($data->link->settings->video_url)) {
    if(($data->link->settings->video_provider ?? 'youtube') == 'vimeo') {
        if(preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/i', $data->link->settings->video_url, $matches)) {
            $embed_url = 'https://player.vimeo.com/video/' . $matches[1];
        }
    }

    else {
        if(preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/i', $data->link->settings->video_url, $matches)) {
            $embed_url = 'https://www.youtube.com/embed/' . $matches[1];
        }
    }
}
?>

<div id="<?= 'biolink_block_id_' . $data->link->biolink_block_id ?>" data-biolink-block-id="<?= $data->link->biolink_block_id ?>" data-biolink-block-type="<?= $data->link->type ?>" class="col-12 col-lg-<?= ($data->link->settings->columns ?? 1) == 1 ? '12' : '6' ?> my-<?= $data->biolink->settings->block_spacing ?? '2' ?>">
    <a href="<?= $open_mode == 'page' ? $lead_funnel_url : '#' ?>" <?= $open_mode == 'page' ? null : 'data-toggle="modal" data-target="' . '#lead_funnel_' . $data->link->biolink_block_id . '"' ?> class="btn btn-block btn-primary link-btn <?= ($data->biolink->settings->hover_animation ?? 'smooth') != 'false' ? 'link-hover-animation-' . ($data->biolink->settings->hover_animation ?? 'smooth') : null ?> <?= 'link-btn-' . $data->link->settings->border_radius ?> <?= $data->link->design->link_class ?>" style="<?= $data->link->design->link_style ?>" data-text-color data-border-width data-border-radius data-border-style data-border-color data-border-shadow data-animation data-background-color data-text-alignment>
        <div class="link-btn-image-wrapper <?= 'link-btn-' . $data->link->settings->border_radius ?>" <?= $data->link->settings->image ? null : 'style="display: none;"' ?>>
            <img src="<?= $data->link->settings->image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $data->link->settings->image : null ?>" class="link-btn-image" loading="lazy" />
        </div>

        <span data-icon>
            <?php if($data->link->settings->icon): ?>
                <i class="<?= $data->link->settings->icon ?> mr-1"></i>
            <?php endif ?>
        </span>

        <span data-name><?= $data->link->settings->name ?></span>
    </a>
</div>

<?php if($open_mode == 'popup'): ?>
    <?php ob_start() ?>
    <div class="modal fade" id="<?= 'lead_funnel_' . $data->link->biolink_block_id ?>" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg lead-funnel-modal-dialog" role="document">
            <div class="modal-content lead-funnel-modal-content" data-lead-funnel-container data-biolink-block-id="<?= $data->link->biolink_block_id ?>" style="<?= $lead_funnel_popup_style ?>">

                <div class="modal-header">
                    <h5 class="modal-title" data-lead-funnel-popup-title><?= $data->link->settings->popup_title ?: $data->link->settings->name ?></h5>
                    <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <?= include_view(THEME_PATH . 'views/l/biolink_blocks/lead_funnel_body.php', ['link' => $data->link, 'embed_url' => $embed_url]) ?>
                </div>

            </div>
        </div>
    </div>
    <?php \Altum\Event::add_content(ob_get_clean(), 'modals') ?>
<?php endif ?>

<?php if($open_mode == 'popup' && !\Altum\Event::exists_content_type_key('javascript', 'lead_funnel_popup_tracking')): ?>
    <?php ob_start() ?>
    <script>
        'use strict';

        $(document).on('shown.bs.modal', '[id^="lead_funnel_"]', event => {
            let modal = $(event.currentTarget);
            let biolink_block_id = modal.find('[data-biolink-block-id]').attr('data-biolink-block-id');

            if(!biolink_block_id) {
                return;
            }

            try {
                let payload = new FormData();
                payload.append('biolink_block_id', biolink_block_id);
                payload.append('event_type', 'open');
                payload.append('source', 'popup_modal');

                if(navigator.sendBeacon && navigator.sendBeacon(`${site_url}l/link/lead_funnel_event`, payload)) {
                    return;
                }

                fetch(`${site_url}l/link/lead_funnel_event`, {
                    method: 'POST',
                    body: payload,
                    credentials: 'same-origin',
                    keepalive: true
                }).catch(() => {});
            } catch(error) {}
        });
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript', 'lead_funnel_popup_tracking') ?>
<?php endif ?>

<?php /* /Custom code: FC-2026-03-23 */ ?>
