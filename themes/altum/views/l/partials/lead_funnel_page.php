<?php defined('ALTUMCODE') || die() ?>

<?php /* Custom code: FC-2026-03-23: dedicated lead funnel page */ ?>

<?php
$embed_url = null;

/* Custom code: FC-2026-03-23: page style variables */
$lead_funnel_page_style = sprintf(
    '--lead-funnel-background-color:%s;--lead-funnel-text-color:%s;--lead-funnel-button-background-color:%s;--lead-funnel-button-text-color:%s;',
    $data->block->settings->page_background_color ?? '#ffffff',
    $data->block->settings->page_text_color ?? '#212529',
    $data->block->settings->page_button_background_color ?? '#007bff',
    $data->block->settings->page_button_text_color ?? '#ffffff'
);
/* /Custom code: FC-2026-03-23 */

if(!empty($data->block->settings->video_url)) {
    if(($data->block->settings->video_provider ?? 'youtube') == 'vimeo') {
        if(preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/i', $data->block->settings->video_url, $matches)) {
            $embed_url = 'https://player.vimeo.com/video/' . $matches[1];
        }
    }

    else {
        if(preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/i', $data->block->settings->video_url, $matches)) {
            $embed_url = 'https://www.youtube.com/embed/' . $matches[1];
        }
    }
}
?>

<body class="<?= l('direction') == 'rtl' ? 'rtl' : null ?> link-body <?= $data->link->design->background_class ?>" style="<?= $data->link->design->background_style ?>">
<?php if(!empty(settings()->custom->body_content_biolink)): ?>
    <?= settings()->custom->body_content_biolink ?>
<?php endif ?>

<?php if((is_string($data->link->settings->background) && string_ends_with('.mp4', $data->link->settings->background)) || isset($_GET['preview'])): ?>
    <video autoplay muted loop playsinline class="link-video-background <?= is_string($data->link->settings->background) && string_ends_with('.mp4', $data->link->settings->background) ? '' : 'd-none' ?>">
        <source src="<?= is_string($data->link->settings->background) && string_ends_with('.mp4', $data->link->settings->background) ? \Altum\Uploads::get_full_url('backgrounds') . $data->link->settings->background : null; ?>" type="video/mp4">
    </video>
<?php endif ?>

<div id="backdrop" class="link-body-backdrop" style="<?= $data->link->design->backdrop_style ?>"></div>

<div class="container animate__animated animate__fadeIn">
    <div class="row d-flex justify-content-center text-center">
        <div class="col-md-<?= $data->link->settings->width ?? '8' ?> link-content">
            <?php require THEME_PATH . 'views/l/partials/ads_header_biolink.php' ?>

            <main class="my-<?= $data->link->settings->block_spacing ?? '2' ?>">
                <div class="lead-funnel-page-card text-left" data-lead-funnel-container style="<?= $lead_funnel_page_style ?>">
                    <div class="lead-funnel-page-header">
                        <a href="<?= $data->link->full_url ?>" class="small text-decoration-none lead-funnel-page-back-link"><i class="fas fa-fw fa-arrow-left mr-1"></i> <?= l('biolink_lead_funnel.back_to_biolink') ?></a>
                        <h1 class="h3 mt-3 mb-0"><?= $data->block->settings->popup_title ?: $data->block->settings->name ?></h1>
                    </div>

                    <div class="lead-funnel-page-body">
                        <?= include_view(THEME_PATH . 'views/l/biolink_blocks/lead_funnel_body.php', ['link' => $data->block, 'embed_url' => $embed_url]) ?>
                    </div>
                </div>
            </main>

            <?php require THEME_PATH . 'views/l/partials/ads_footer_biolink.php' ?>

            <footer id="footer" class="link-footer">
                <div id="branding" class="link-footer-branding">
                    <?php if($data->link->settings->display_branding): ?>
                        <?php if(isset($data->link->settings->branding, $data->link->settings->branding->name, $data->link->settings->branding->url) && !empty($data->link->settings->branding->name)): ?>
                            <a href="<?= !empty($data->link->settings->branding->url) ? $data->link->settings->branding->url : '#' ?>" style="<?= $data->link->design->text_style ?>"><?= $data->link->settings->branding->name ?></a>
                        <?php else: ?>
                            <?php
                            $replacers = [
                                '{{URL}}' => url(),
                                '{{DASHBOARD_LINK}}' => url('dashboard'),
                                '{{WEBSITE_TITLE}}' => settings()->main->title,
                                '{{AFFILIATE_URL_TAG}}' => \Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled ? '?ref=' . $data->user->referral_key : null,
                            ];

                            settings()->links->branding = str_replace(
                                array_keys($replacers),
                                array_values($replacers),
                                settings()->links->branding
                            );
                            ?>

                            <?= settings()->links->branding ?>
                        <?php endif ?>
                    <?php endif ?>
                </div>
            </footer>
        </div>
    </div>
</div>

<?= $data->pixels ?? null ?>
</body>
<?php /* /Custom code: FC-2026-03-23 */ ?>