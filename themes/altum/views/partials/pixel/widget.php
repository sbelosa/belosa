<?php defined('ALTUMCODE') || die() ?>

<div role="dialog" class="altumcode-66pusher-widget-wrapper altumcode-66pusher-widget-wrapper-<?= $data->website->widget->border_radius ?> <?= $data->website->widget->display_shadow ? 'altumcode-66pusher-widget-wrapper-shadow' : null ?> <?= $data->website->widget->hover_animation ? 'altumcode-66pusher-widget-wrapper-' . $data->website->widget->hover_animation : null ?> <?= ($data->website->widget->direction ?? 'ltr') == 'rtl' ? 'altumcode-66pusher-widget-rtl' : null ?> altumcode-66pusher-widget-wrapper" style='font-family: <?= $data->website->widget->font ?? 'inherit' ?>!important;background-color: <?= $data->website->widget->background_color ?>;border-width: <?= $data->website->widget->border_width ?>px;border-color: <?= $data->website->widget->border_color ?>;padding: <?= $data->website->widget->internal_padding ?? 12 ?>px !important;' data-wrapper>
    <div class="altumcode-66pusher-widget-content">
        <div class="altumcode-66pusher-widget-loading-backdrop" style="display: none;" data-loading>
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">
                <g id="graph" fill="black" fill-rule="evenodd" transform="translate(1 1)">
                    <circle cx="32" cy="32" r="6"/>
                    <circle cx="32" cy="32" r="6" opacity="0">
                        <animate attributeName="r" begin="0s" dur="2s" values="6;32" calcMode="linear" repeatCount="indefinite"/>
                        <animate attributeName="opacity" begin="0s" dur="2s" values="1;0" calcMode="linear" repeatCount="indefinite"/>
                    </circle>
                    <circle cx="32" cy="32" r="6" opacity="0">
                        <animate attributeName="r" begin="1s" dur="2s" values="6;32" calcMode="linear" repeatCount="indefinite"/>
                        <animate attributeName="opacity" begin="1s" dur="2s" values="1;0" calcMode="linear" repeatCount="indefinite"/>
                    </circle>
                </g>
            </svg>
        </div>

        <div>
            <img src="<?= $data->website->widget->image_url ?>" class="altumcode-66pusher-widget-image" style="<?= $data->website->widget->image_url ? null : 'display: none;' ?>" alt="<?= $data->website->widget->image_alt ?>" loading="lazy" referrerpolicy="no-referrer" data-image />
        </div>

        <div>
            <div class="altumcode-66pusher-widget-header">
                <div>
                    <p class="altumcode-66pusher-widget-title" style="color: <?= $data->website->widget->title_color ?>;" data-title><?= $data->website->widget->title ?></p>
                    <p class="altumcode-66pusher-widget-description" style="color: <?= $data->website->widget->description_color ?>;" data-description><?= $data->website->widget->description ?></p>
                </div>
            </div>

            <div class="altumcode-66pusher-widget-buttons" data-buttons>
                <button type="button" class="altumcode-66pusher-widget-button altumcode-66pusher-widget-close-button" style="color: <?= $data->website->widget->close_button_text_color ?>;background-color: <?= $data->website->widget->close_button_background_color ?>;" data-close><?= $data->website->widget->close_button ?></button>
                <button type="button" class="altumcode-66pusher-widget-button altumcode-66pusher-widget-subscribe-button" style="color: <?= $data->website->widget->subscribe_button_text_color ?>;background-color: <?= $data->website->widget->subscribe_button_background_color ?>;" data-subscribe><?= $data->website->widget->subscribe_button ?></button>
            </div>
        </div>
    </div>

    <?php if(!empty($data->website->settings->branding_name) && !empty($data->website->settings->branding_url)): ?>
        <div class="altumcode-66pusher-widget-site-wrapper">
            <a href="<?= $data->website->settings->branding_url ?>" class="altumcode-66pusher-widget-site" style="display: <?= $data->website->widget->display_branding ? 'inherit;' : 'none !important;' ?>"><?= $data->website->settings->branding_name ?></a>
        </div>
    <?php else: ?>
        <div class="altumcode-66pusher-widget-site-wrapper">
            <a href="<?= url() ?>" class="altumcode-66pusher-widget-site" style="display: <?= $data->website->widget->display_branding ? 'inherit;' : 'none !important;' ?>"><?= settings()->websites->branding ?></a>
        </div>
    <?php endif ?>
</div>
