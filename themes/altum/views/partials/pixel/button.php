<?php defined('ALTUMCODE') || die() ?>

<div class="altumcode-66pusher-button-inline-block">
    <div role="button" class="altumcode-66pusher-button-wrapper altumcode-66pusher-button-wrapper-<?= $data->website->button->border_radius ?> <?= $data->website->button->display_shadow ? 'altumcode-66pusher-button-wrapper-shadow' : null ?> <?= $data->website->button->hover_animation ? 'altumcode-66pusher-button-wrapper-' . $data->website->button->hover_animation : null ?> <?= ($data->website->button->direction ?? 'ltr') == 'rtl' ? 'altumcode-66pusher-button-rtl' : null ?> altumcode-66pusher-button-wrapper" style='font-family: <?= $data->website->button->font ?? 'inherit' ?>!important;background-color: <?= $data->website->button->background_color ?>;border-width: <?= $data->website->button->border_width ?>px;border-color: <?= $data->website->button->border_color ?>;padding: <?= $data->website->button->internal_padding ?? 12 ?>px !important;' data-wrapper>
        <div class="altumcode-66pusher-button-content">
            <div class="altumcode-66pusher-button-loading-backdrop" style="display: none;" data-loading>
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
                <img src="<?= $data->website->button->image_url ?>" class="altumcode-66pusher-button-image" style="<?= $data->website->button->image_url ? null : 'display: none;' ?>" alt="<?= $data->website->button->image_alt ?>" loading="lazy" referrerpolicy="no-referrer" data-image />
            </div>

            <div>
                <div class="altumcode-66pusher-button-header">
                    <div>
                        <p class="altumcode-66pusher-button-title" style="color: <?= $data->website->button->title_color ?>;" data-title><?= $data->website->button->title ?></p>
                        <p class="altumcode-66pusher-button-description" style="color: <?= $data->website->button->description_color ?>;" data-description><?= $data->website->button->description ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if(!empty($data->website->settings->branding_name) && !empty($data->website->settings->branding_url)): ?>
        <div class="altumcode-66pusher-button-site-wrapper">
            <a href="<?= $data->website->settings->branding_url ?>" class="altumcode-66pusher-button-site" style="display: <?= $data->website->button->display_branding ? 'inherit;' : 'none !important;' ?>"><?= $data->website->settings->branding_name ?></a>
        </div>
    <?php else: ?>
        <div class="altumcode-66pusher-button-site-wrapper">
            <a href="<?= url() ?>" class="altumcode-66pusher-button-site" style="display: <?= $data->website->button->display_branding ? 'inherit;' : 'none !important;' ?>"><?= settings()->websites->branding ?></a>
        </div>
    <?php endif ?>
</div>
