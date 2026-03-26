<?php defined('ALTUMCODE') || die() ?>

<div class="index-container">
    <div class="container index-container-content index-background">
        <?= \Altum\Alerts::output_alerts() ?>

        <div class="row">
            <div class="col">
                <div class="text-left">
                    <div class="mb-2">
                        <span class="badge badge-pill badge-light">
                            <i class="fas fa-fw fa-star fa-sm text-warning"></i><i class="fas fa-fw fa-star fa-sm text-warning"></i><i class="fas fa-fw fa-star fa-sm text-warning"></i><i class="fas fa-fw fa-star fa-sm text-warning"></i><i class="fas fa-fw fa-star fa-sm text-warning mr-1"></i>
                            <?= sprintf(l('index.stars'), '<span class="font-weight-bolder" data-count-up-append="+" data-count-up-number="' . $data->total_users . '">' . nr($data->total_users) . '+</span>') ?>
                        </span>
                    </div>

                    <h1 class="index-header mb-4"><?= l('index.header') ?></h1>

                    <div class="row mb-5">
                        <?php if(settings()->links->biolinks_is_enabled): ?>
                            <div class="col-6 col-xl-4 index-feature text-truncate">
                                <a href="<?= url('links?type=biolink') ?>" class="text-truncate">
                                    <?= l('index.subheader.biolink') ?>
                                </a>
                            </div>
                        <?php endif ?>

                        <?php if(settings()->links->shortener_is_enabled): ?>
                            <div class="col-6 col-xl-4 index-feature text-truncate">
                                <a href="<?= url('links?type=link') ?>">
                                    <?= l('index.subheader.link') ?>
                                </a>
                            </div>
                        <?php endif ?>

                        <?php if(settings()->links->files_is_enabled): ?>
                            <div class="col-6 col-xl-4 index-feature text-truncate">
                                <a href="<?= url('links?type=file') ?>">
                                    <?= l('index.subheader.file') ?>
                                </a>
                            </div>
                        <?php endif ?>

                        <?php if(settings()->links->vcards_is_enabled): ?>
                            <div class="col-6 col-xl-4 index-feature text-truncate">
                                <a href="<?= url('links?type=vcard') ?>">
                                    <?= l('index.subheader.vcard') ?>
                                </a>
                            </div>
                        <?php endif ?>

                        <?php if(settings()->links->events_is_enabled): ?>
                            <div class="col-6 col-xl-4 index-feature text-truncate">
                                <a href="<?= url('links?type=event') ?>">
                                    <?= l('index.subheader.event') ?>
                                </a>
                            </div>
                        <?php endif ?>

                        <?php if(settings()->links->static_is_enabled): ?>
                            <div class="col-6 col-xl-4 index-feature text-truncate">
                                <a href="<?= url('links?type=static') ?>">
                                    <?= l('index.subheader.static') ?>
                                </a>
                            </div>
                        <?php endif ?>

                        <?php if(settings()->codes->qr_codes_is_enabled): ?>
                            <div class="col-6 col-xl-4 index-feature text-truncate">
                                <a href="<?= url('qr-codes') ?>">
                                    <?= l('index.subheader.qr_codes') ?>
                                </a>
                            </div>
                        <?php endif ?>

                        <?php if(settings()->tools->is_enabled): ?>
                            <div class="col-6 col-xl-4 index-feature text-truncate">
                                <a href="<?= url('tools') ?>">
                                    <?= l('index.subheader.tools') ?>
                                </a>
                            </div>
                        <?php endif ?>

                        <?php if(settings()->links->biolinks_is_enabled ||settings()->links->shortener_is_enabled ||settings()->links->files_is_enabled ||settings()->links->vcards_is_enabled ||settings()->links->events_is_enabled ||settings()->links->static_is_enabled): ?>
                            <div class="col-6 col-xl-4 index-feature text-truncate">
                                <a href="<?= url('links-statistics') ?>">
                                    <?= l('index.subheader.analytics') ?>
                                </a>
                            </div>
                        <?php endif ?>
                    </div>

                    <!-- Custom code: FC-2026-02-25: primary CTA only -->
                    <div class="d-flex flex-column">
                        <?php if(is_logged_in()): ?>
                            <a href="<?= url('dashboard') ?>" class="btn index-button rounded-2x index-button-white bg-gradient border-0 mb-3">
                                <?= l('index.cta_dashboard') ?> <i class="fas fa-fw fa-sm fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?= url('login') ?>" class="btn index-button rounded-2x index-button-white bg-gradient border-0 mb-3">
                                <?= l('index.cta_login') ?> <i class="fas fa-fw fa-sm fa-arrow-right"></i>
                            </a>
                        <?php endif ?>
                    </div>
                    <!-- /Custom code: FC-2026-02-25 -->
                </div>
            </div>

            <div class="d-none d-lg-flex justify-content-center col">
                <img src="<?= get_custom_image_if_any('index/hero-one.webp') ?>" class="index-image index-image-one" alt="<?= l('index.hero_image_alt') ?>" />
                <img src="<?= get_custom_image_if_any('index/hero-two.webp') ?>" class="index-image index-image-two" alt="<?= l('index.hero_image_alt') ?>" />
            </div>

        </div>
    </div>
</div>

<?php if(!empty($data->homepage_semantics)): ?>
    <!-- Custom code: FC-2026-03-24: homepage semantic summary -->
    <div class="container mt-6">
        <section class="card index-highly-rounded border-0 bg-gray-900 fcc-home-summary" data-aos="fade-up">
            <div class="card-body fcc-home-summary__inner py-5 py-lg-5">
                <div class="fcc-home-summary__copy">
                    <div class="index-icon-container mb-3">
                        <i class="fas fa-fw fa-id-card fa-sm"></i>
                    </div>
                    <div class="fcc-home-summary__eyebrow"><?= $data->homepage_semantics['term_name'] ?></div>
                    <h2><?= $data->homepage_semantics['hero_heading'] ?></h2>
                    <p><?= $data->homepage_semantics['hero_summary'] ?></p>
                </div>

                <div class="fcc-home-summary__facts">
                    <?php foreach($data->homepage_semantics['facts'] as $fact): ?>
                        <div class="fcc-home-summary__fact"><?= $fact ?></div>
                    <?php endforeach ?>
                </div>

                <div class="fcc-home-summary__links">
                    <?php foreach($data->homepage_semantics['hub_pages'] as $hub_page): ?>
                        <a href="<?= $hub_page['url'] ?>" class="fcc-home-summary__link"><?= $hub_page['name'] ?></a>
                    <?php endforeach ?>
                </div>
            </div>
        </section>
    </div>
    <!-- /Custom code: FC-2026-03-24 -->
<?php endif ?>

<!-- Custom code: FC-2026-02-27: restore smaller presentation header icons -->
<?php if(settings()->links->biolinks_is_enabled): ?>
    <div class="container mt-6">
        <div class="card index-highly-rounded border-0" data-aos="fade-up">
            <div class="card-body">
                <div class="row">
                    <div class="col-auto col-lg-5 mb-4 mb-lg-0">
                        <img src="<?= get_custom_image_if_any('index/bio-link.webp') ?>" class="inverse-colors-animation index-card-image index-highly-rounded" loading="lazy" alt="<?= l('index.biolink_image_alt') ?>" />
                    </div>
                    <div class="col ml-3">
                        <div class="index-icon-container mb-2">
                            <i class="fas fa-fw fa-users fa-sm"></i>
                        </div>
                        <h2 class="mt-0">
                            <a href="<?= url('page/forever-card-app') ?>" class="fcc-presentation-card__title-link">
                                <span><?= l('index.presentation1.header') ?></span>
                            </a>
                        </h2>
                        <p class="h6 mt-3 text-muted"><?= l('index.presentation1.subheader') ?></p>

                        <ul class="list-style-none mt-4 font-size-small">
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation1.feature1') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation1.feature2') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation1.feature3') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation1.feature4') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation1.feature5') ?></div>
                            </li>
                        </ul>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->links->shortener_is_enabled): ?>
    <div class="container mt-6">
        <div class="card index-highly-rounded border-0" data-aos="fade-up">
            <div class="card-body">
                <div class="row">
                    <div class="col-auto col-lg-5 mb-4 mb-lg-0">
                        <img src="<?= get_custom_image_if_any('index/short-link.webp') ?>" class="inverse-colors-animation index-card-image index-highly-rounded" loading="lazy" alt="<?= l('index.short_image_alt') ?>" />
                    </div>
                    <div class="col ml-3">
                        <div class="index-icon-container mb-2">
                            <i class="fas fa-fw fa-link fa-sm"></i>
                        </div>
                        <h2 class="mt-0">
                            <a href="<?= url('page/smart-referral-links') ?>" class="fcc-presentation-card__title-link">
                                <span><?= l('index.presentation2.header') ?></span>
                            </a>
                        </h2>
                        <p class="h6 mt-3 text-muted"><?= l('index.presentation2.subheader') ?></p>

                        <ul class="list-style-none mt-4 font-size-small">
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation2.feature1') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation2.feature2') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation2.feature3') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation2.feature4') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation2.feature5') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation2.feature6') ?></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->links->static_is_enabled): ?>
    <div class="container mt-6">
        <div class="card index-highly-rounded border-0" data-aos="fade-up">
            <div class="card-body">
                <div class="row">
                    <div class="col-auto col-lg-5 mb-4 mb-lg-0">
                        <img src="<?= get_custom_image_if_any('index/static-link.webp') ?>" class="inverse-colors-animation index-card-image index-highly-rounded" loading="lazy" alt="<?= l('index.static_image_alt') ?>" />
                    </div>
                    <div class="col ml-3">
                        <div class="index-icon-container mb-2">
                            <i class="fas fa-fw fa-file-code fa-sm"></i>
                        </div>
                        <h2 class="mt-0">
                            <a href="<?= url('page/forever-card-funnel') ?>" class="fcc-presentation-card__title-link">
                                <span><?= l('index.presentation5.header') ?></span>
                            </a>
                        </h2>
                        <p class="h6 mt-3 text-muted"><?= l('index.presentation5.subheader') ?></p>

                        <ul class="list-style-none mt-4 font-size-small">
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation5.feature1') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation5.feature2') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation5.feature3') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation5.feature4') ?></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->codes->qr_codes_is_enabled): ?>
    <div class="container mt-6">
        <div class="card index-highly-rounded border-0" data-aos="fade-up">
            <div class="card-body">
                <div class="row">
                    <div class="col-auto col-lg-5 mb-4 mb-lg-0">
                        <img src="<?= get_custom_image_if_any('index/qr-code.webp') ?>" class="inverse-colors-animation index-card-image index-highly-rounded" loading="lazy" alt="<?= l('index.qr_image_alt') ?>" />
                    </div>
                    <div class="col ml-3">
                        <div class="index-icon-container mb-2">
                            <i class="fas fa-fw fa-qrcode fa-sm"></i>
                        </div>
                        <h2 class="mt-0">
                            <a href="<?= url('page/nfc-card-offline') ?>" class="fcc-presentation-card__title-link">
                                <span><?= l('index.presentation3.header') ?></span>
                            </a>
                        </h2>
                        <p class="h6 mt-3 text-muted"><?= l('index.presentation3.subheader') ?></p>

                        <ul class="list-style-none mt-4 font-size-small">
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation3.feature1') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation3.feature2') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation3.feature3') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation3.feature4') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation3.feature5') ?></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->links->biolinks_is_enabled): ?>
    <div class="container mt-6">
        <div class="card index-highly-rounded border-0" data-aos="fade-up">
            <div class="card-body">
                <div class="row">
                    <div class="col-auto col-lg-5 mb-4 mb-lg-0">
                        <img src="<?= SITE_URL . 'uploads/main/ai_asistenti_za_preporuku.png' ?>" class="inverse-colors-animation index-card-image index-highly-rounded" loading="lazy" alt="<?= l('index.presentation6.header') ?>" />
                    </div>
                    <div class="col ml-3">
                        <div class="index-icon-container mb-2">
                            <i class="fas fa-fw fa-robot fa-sm"></i>
                        </div>
                        <h2 class="mt-0">
                            <a href="<?= url('page/ai-product-assistants') ?>" class="fcc-presentation-card__title-link">
                                <span><?= l('index.presentation6.header') ?></span>
                            </a>
                        </h2>
                        <p class="h6 mt-3 text-muted"><?= l('index.presentation6.subheader') ?></p>

                        <ul class="list-style-none mt-4 font-size-small">
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation6.feature1') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation6.feature2') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation6.feature3') ?></div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                                <div><?= l('index.presentation6.feature4') ?></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->links->biolinks_is_enabled ||settings()->links->shortener_is_enabled ||settings()->links->files_is_enabled ||settings()->links->vcards_is_enabled ||settings()->links->events_is_enabled ||settings()->links->static_is_enabled): ?>
<div class="container mt-6">
    <div class="card index-highly-rounded border-0" data-aos="fade-up">
        <div class="card-body">
            <div class="row">
                <div class="col-auto col-lg-5 mb-4 mb-lg-0">
                    <img src="<?= get_custom_image_if_any('index/analytics.webp') ?>" class="inverse-colors-animation index-card-image index-highly-rounded" loading="lazy" alt="<?= l('index.analytics_image_alt') ?>" />
                </div>
                <div class="col ml-3">
                    <div class="index-icon-container mb-2">
                        <i class="fas fa-fw fa-chart-bar fa-sm"></i>
                    </div>
                    <h2 class="mt-0"><?= l('index.presentation4.header') ?></h2>
                    <p class="h6 mt-3 text-muted"><?= l('index.presentation4.subheader') ?></p>

                    <ul class="list-style-none mt-4 font-size-small">
                        <li class="d-flex align-items-center mb-2">
                            <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                            <div><?= l('index.presentation4.feature1') ?></div>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                            <div><?= l('index.presentation4.feature2') ?></div>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                            <div><?= l('index.presentation4.feature3') ?></div>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                            <div><?= l('index.presentation4.feature4') ?></div>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="fas fa-fw fa-sm fa-check-circle text-success mr-3"></i>
                            <div><?= l('index.presentation4.feature5') ?></div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif ?>

<div class="py-3"></div>

<div class="container mt-8">
    <div class="row">
        <style>
            /* File Links */
            .file-links-background {
                background-color: #ecfdf5;
                color: #0b4e3a;
            }
            .file-links-icon {
                color: #10b981;
            }

            /* VCard Links */
            .vcard-links-background {
                background-color: #ecfeff;
                color: #04505a;
            }
            .vcard-links-icon {
                color: #06b6d4;
            }

            /* Event Links */
            .event-links-background {
                background-color: #eef2ff;
                color: #444088;
            }
            .event-links-icon {
                color: #6366f1;
            }

            /* Splash pages */
            .splash-pages-background {
                background-color: #eef2ff;
                color: #044852;
            }
            .splash-pages-icon {
                color: #06b6d4;
            }

            /* Domains */
            .domains-background {
                background-color: #faf5ff;
                color: #4b1d7a;
            }
            .domains-icon {
                color: #a855f7;
            }

            /* Projects */
            .projects-background {
                background-color: #fdf4ff;
                color: #851c8d;
            }
            .projects-icon {
                color: #d946ef;
            }

            /* File Links - Dark Theme */
            [data-theme-style='dark'] .file-links-background {
                background-color: #1a4731;
                color: #9ee5c9;
            }
            [data-theme-style='dark'] .file-links-icon {
                color: #047857;
            }

            /* VCard Links - Dark Theme */
            [data-theme-style='dark'] .vcard-links-background {
                background-color: #1a4044;
                color: #8fe2da;
            }
            [data-theme-style='dark'] .vcard-links-icon {
                color: #025e73;
            }

            /* Event Links - Dark Theme */
            [data-theme-style='dark'] .event-links-background {
                background-color: #1a1c36;
                color: #b6bde7;
            }
            [data-theme-style='dark'] .event-links-icon {
                color: #3134a1;
            }

            /* Splash pages - Dark Theme */
            [data-theme-style='dark'] .splash-pages-background {
                background-color: #1a1c36;
                color: #99e0e7;
            }
            [data-theme-style='dark'] .splash-pages-icon {
                color: #025e73;
            }

            /* Domains - Dark Theme */
            [data-theme-style='dark'] .domains-background {
                background-color: #2d1e3f;
                color: #d8c1f2;
            }
            [data-theme-style='dark'] .domains-icon {
                color: #6d22a5;
            }

            /* Projects - Dark Theme */
            [data-theme-style='dark'] .projects-background {
                background-color: #321a36;
                color: #f0c9e7;
            }
            [data-theme-style='dark'] .projects-icon {
                color: #a316af;
            }
        </style>

        <?php if(settings()->links->files_is_enabled): ?>
            <div class="col-12 col-md-6 col-lg-4 p-4 icon-zoom-animation" data-aos="fade-up" data-aos-delay="100">
                <div class="card index-highly-rounded border-0 d-flex flex-column justify-content-between h-100" data-toggle="tooltip" title="<?= l('index.file_links.subheader') ?>">
                    <div class="card-body">
                        <div class="file-links-background p-3 index-highly-rounded">
                            <i class="fas fa-fw fa-lg fa-file mr-3 file-links-icon"></i>
                            <span class="h5"><?= l('index.file_links.header') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(settings()->links->vcards_is_enabled): ?>
            <div class="col-12 col-md-6 col-lg-4 p-4 icon-zoom-animation" data-aos="fade-up" data-aos-delay="200">
                <div class="card index-highly-rounded border-0 d-flex flex-column justify-content-between h-100" data-toggle="tooltip" title="<?= l('index.vcard_links.subheader') ?>">
                    <div class="card-body">
                        <div class="vcard-links-background p-3 index-highly-rounded">
                            <i class="fas fa-fw fa-lg fa-id-card mr-3 vcard-links-icon"></i>
                            <span class="h5"><?= l('index.vcard_links.header') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(settings()->links->events_is_enabled): ?>
            <div class="col-12 col-md-6 col-lg-4 p-4 icon-zoom-animation" data-aos="fade-up" data-aos-delay="300">
                <div class="card index-highly-rounded border-0 d-flex flex-column justify-content-between h-100" data-toggle="tooltip" title="<?= l('index.event_links.subheader') ?>">
                    <div class="card-body">
                        <div class="event-links-background p-3 index-highly-rounded">
                            <i class="fas fa-fw fa-lg fa-calendar mr-3 event-links-icon"></i>
                            <span class="h5"><?= l('index.event_links.header') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(settings()->links->splash_page_is_enabled): ?>
            <div class="col-12 col-md-6 col-lg-4 p-4 icon-zoom-animation" data-aos="fade-up" data-aos-delay="400">
                <div class="card index-highly-rounded border-0 d-flex flex-column justify-content-between h-100" data-toggle="tooltip" title="<?= l('index.splash_pages.subheader') ?>">
                    <div class="card-body">
                        <div class="splash-pages-background p-3 index-highly-rounded">
                            <i class="fas fa-fw fa-lg fa-droplet mr-3 splash-pages-icon"></i>
                            <span class="h5"><?= l('index.splash_pages.header') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(settings()->links->domains_is_enabled): ?>
            <div class="col-12 col-md-6 col-lg-4 p-4 icon-zoom-animation" data-aos="fade-up" data-aos-delay="500">
                <div class="card index-highly-rounded border-0 d-flex flex-column justify-content-between h-100" data-toggle="tooltip" title="<?= l('index.domains.subheader') ?>">
                    <div class="card-body">
                        <div class="domains-background p-3 index-highly-rounded">
                            <i class="fas fa-fw fa-lg fa-globe mr-3 domains-icon"></i>
                            <span class="h5"><?= l('index.domains.header') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(settings()->links->projects_is_enabled): ?>
        <div class="col-12 col-md-6 col-lg-4 p-4 icon-zoom-animation" data-aos="fade-up" data-aos-delay="600">
            <div class="card index-highly-rounded border-0 d-flex flex-column justify-content-between h-100" data-toggle="tooltip" title="<?= l('index.projects.subheader') ?>">
                <div class="card-body">
                    <div class="projects-background p-3 index-highly-rounded">
                        <i class="fas fa-fw fa-lg fa-project-diagram mr-3 projects-icon"></i>
                        <span class="h5"><?= l('index.projects.header') ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<?php if(settings()->links->shortener_is_enabled): ?>
    <div class="container mt-8">
        <div class="card py-4 index-highly-rounded border-0">
            <div class="card-body">
                <!-- Custom code: FC-2026-02-25: smart links section styling hooks -->
                <div class="text-center mb-4 index-smart-links-header">
                    <h2><?= l('index.shortener_app_linking.header') ?></h2>
                    <p class="text-muted index-smart-links-subheader"><?= l('index.shortener_app_linking.subheader') ?></p>
                </div>
                <!-- /Custom code: FC-2026-02-25 -->

                <div class="d-flex flex-wrap justify-content-center">
                    <?php foreach(require APP_PATH . 'includes/app_linking.php' as $app_key => $app): ?>
                        <div class="bg-gray-100 index-highly-rounded w-fit-content p-3 m-4 icon-zoom-animation" data-toggle="tooltip" title="<?= $app['name'] ?>">
                            <span title="<?= $app['name'] ?>"><i class="<?= $app['icon'] ?> fa-fw fa-xl mx-1" style="color: <?= $app['color'] ?>"></i></span>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<div class="py-3"></div>







<div class="container mt-8">
    <div class="card py-4 index-highly-rounded border-0 bg-gray-900">
        <div class="card-body">
            <div class="row justify-content-between">
                <div class="col-12 col-lg-3 mb-4 mb-lg-0">
                    <div class="text-center d-flex flex-column">
                        <span class="font-weight-bold text-muted mb-3"><?= l('index.stats.links') ?></span>
                        <span class="h1 text-gradient-primary" style="--gradient-one: var(--purple); --gradient-two: var(--pink);" data-count-up-append="+" data-count-up-number="<?= $data->total_links ?>"><?= nr($data->total_links, 0, true, true) . '+' ?></span>
                    </div>
                </div>

                <?php if(settings()->codes->qr_codes_is_enabled): ?>
                    <div class="col-12 col-lg-3 mb-4 mb-lg-0">
                        <div class="text-center d-flex flex-column">
                            <span class="font-weight-bold text-muted mb-3"><?= l('index.stats.qr_codes') ?></span>
                            <span class="h1 text-gradient-primary" style="--gradient-one: var(--teal); --gradient-two: var(--blue);" data-count-up-append="+" data-count-up-number="<?= $data->total_qr_codes ?>"><?= nr($data->total_qr_codes, 0, true, true) . '+' ?></span>
                        </div>
                    </div>
                <?php endif ?>

                <div class="col-12 col-lg-3 mb-4 mb-lg-0">
                    <div class="text-center d-flex flex-column">
                        <span class="font-weight-bold text-muted mb-3"><?= l('index.stats.track_links') ?></span>
                        <span class="h1 text-gradient-primary" style="--gradient-one: var(--blue); --gradient-two: var(--purple);" data-count-up-append="+" data-count-up-number="<?= $data->total_track_links ?>"><?= nr($data->total_track_links, 0, true, true) . '+' ?></span>
                    </div>
                </div>
            </div>
            <!-- Custom code: FC-2026-02-25: stats note -->
            <!-- Custom code: FC-2026-02-25: stats note styling hook -->
            <p class="text-muted text-center mt-4 mb-0 index-stats-note"><?= l('index.stats.note') ?></p>
            <!-- /Custom code: FC-2026-02-25 -->
            <!-- /Custom code: FC-2026-02-25 -->
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-02-27 -->

<div class="py-3"></div>

<?php if(settings()->links->pixels_is_enabled): ?>
    <div class="container mt-8">
        <div class="card py-4 border-0 index-highly-rounded">
            <div class="card-body">
                <!-- Custom code: FC-2026-02-25: pixels section copy -->
                <div class="text-center mb-4">
                    <h2><?= l('index.pixels.header') ?></h2>
                    <p class="text-muted mb-2"><?= l('index.pixels.subheader') ?></p>
                    <p class="text-muted index-pixels-note mb-0"><?= l('index.pixels.note') ?></p>
                </div>
                <!-- /Custom code: FC-2026-02-25 -->

                <div class="row no-gutters">
                    <?php $i = 0; ?>
                    <?php foreach(require APP_PATH . 'includes/pixels.php' as $item): ?>
                        <div class="col-12 col-md-6 col-lg-4 p-4" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
                            <div class="bg-gray-100 rounded-3x w-100 p-3 icon-zoom-animation text-truncate">
                                <i class="<?= $item['icon'] ?> fa-fw fa-lg mx-1" style="color: <?= $item['color'] ?>"></i>
                                <span class="h6"><?= $item['name'] ?></span>
                            </div>
                        </div>
                        <?php $i++ ?>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>


<?php if(settings()->tools->is_enabled && $data->enabled_tools): ?>
    <div class="py-3"></div>

    <div class="container mt-8">
        <h2 class="text-center mb-4"><?= sprintf(l('index.tools.header'), nr($data->enabled_tools)) ?> <i class="fas fa-fw fa-xs fa-screwdriver-wrench text-muted ml-1"></i></h2>

        <div class="row position-relative">
            <div class="index-fade"></div>

            <?php $i = 1; ?>
            <?php foreach($data->tools_categories as $tool => $tool_properties): ?>
                <div class="col-12 col-lg-6 p-4 position-relative" data-aos="fade-in" data-aos-delay="<?= $i++ * 100 ?>">
                    <div class="card rounded-2x"  style="background: <?= $tool_properties['color'] ?>; border-color: <?= $tool_properties['color'] ?>; color: white;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex text-truncate">
                                    <div class="d-flex align-items-center justify-content-center rounded mr-3 tool-icon" style="background: <?= $tool_properties['faded_color'] ?>;">
                                        <i class="<?= $tool_properties['icon'] ?> fa-fw" style="color: <?= $tool_properties['color'] ?>"></i>
                                    </div>

                                    <div class="text-truncate ml-3">
                                        <a href="<?= url('tools') ?>" class="stretched-link text-decoration-none" style="color: white;">
                                            <strong><?= l('tools.' . $tool) ?></strong>
                                        </a>
                                        <p class="text-truncate small m-0"><?= l('tools.' . $tool . '_help') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
<?php endif ?>


<?php if(\Altum\Plugin::is_active('aix') && settings()->aix->images_is_enabled && settings()->aix->images_display_latest_on_index): ?>
    <div class="py-3"></div>

    <div class="container mt-8">
        <div class="text-center mb-4">
            <h2 class="h3"><?= sprintf(l('index.images'), nr($data->total_images, 0, true, true)) ?></h2>
            <p class="text-muted"><?= l('index.images_subheader') ?></p>
        </div>

        <div class="card index-highly-rounded border-0">
            <div class="card-body">
                <div class="row no-gutters">
                    <?php foreach($data->images as $image): ?>
                        <div class="col-6 col-lg-3 p-4" data-aos="zoom-in">
                            <img src="<?= \Altum\Uploads::get_full_url('images') . $image->image ?>" class="img-fluid rounded" alt="<?= $image->input ?>" data-toggle="tooltip" title="<?= $image->input ?>" loading="lazy" />
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if(\Altum\Plugin::is_active('aix') && settings()->aix->documents_is_enabled): ?>
    <div class="py-3"></div>

    <div class="container mt-8">
        <div class="card index-highly-rounded bg-gray-900">
            <div class="card-body py-5 py-lg-6 text-center">
                <span class="h3 text-gray-100"><?= sprintf(l('index.documents'), nr($data->total_documents, 0, true, true)) ?></span>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if(settings()->main->api_is_enabled): ?>
    <div class="py-3"></div>

    <div class="container mt-8">
        <div class="row align-items-center justify-content-between">
            <div class="col-12 col-lg-5 mb-5 mb-lg-0 d-flex flex-column justify-content-center" data-aos="fade-up"">
                <div class="text-uppercase font-weight-bold text-primary mb-3"><?= l('index.api.name') ?></div>

                <div>
                    <h2 class="mb-2"><?= l('index.api.header') ?></h2>
                    <p class="text-muted mb-4"><?= l('index.api.subheader') ?></p>

                    <div class="position-relative">
                        <div class="index-fade"></div>
                        <div class="row">
                            <div class="col">
                                <?php if(settings()->links->shortener_is_enabled): ?>
                                    <div class="small mb-2"><i class="fas fa-fw fa-check-circle text-success mr-1"></i> <?= l('api_documentation.links') ?></div>
                                <?php endif ?>

                                <?php if(settings()->links->biolinks_is_enabled ||settings()->links->shortener_is_enabled ||settings()->links->files_is_enabled ||settings()->links->vcards_is_enabled ||settings()->links->events_is_enabled ||settings()->links->static_is_enabled): ?>
                                    <div class="small mb-2"><i class="fas fa-fw fa-check-circle text-success mr-1"></i> <?= l('api_documentation.statistics') ?></div>
                                <?php endif ?>

                                <?php if(settings()->codes->qr_codes_is_enabled): ?>
                                    <div class="small mb-2"><i class="fas fa-fw fa-check-circle text-success mr-1"></i> <?= l('qr_codes.title') ?></div>
                                <?php endif ?>

                            </div>

                            <div class="col">
                                <?php if(settings()->links->projects_is_enabled): ?>
                                    <div class="small mb-2"><i class="fas fa-fw fa-check-circle text-success mr-1"></i> <?= l('projects.title') ?></div>
                                <?php endif ?>

                                <?php if(settings()->links->pixels_is_enabled): ?>
                                    <div class="small mb-2"><i class="fas fa-fw fa-check-circle text-success mr-1"></i> <?= l('pixels.title') ?></div>
                                <?php endif ?>

                                <?php if(settings()->links->domains_is_enabled): ?>
                                    <div class="small mb-2"><i class="fas fa-fw fa-check-circle text-success mr-1"></i> <?= l('domains.title') ?></div>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>

                    <a href="<?= url('api-documentation') ?>" class="btn btn-block btn-outline-primary mt-5">
                        <?= l('api_documentation.menu') ?> <i class="fas fa-fw fa-xs fa-code ml-1"></i>
                    </a>
                </div>
            </div>

            <div class="col-12 col-lg-6" data-aos="fade-up" data-aos-delay="300">
                <div class="card rounded-2x bg-dark text-white">
                    <div class="card-body p-4 text-monospace reveal-effect text-break font-size-small" style="line-height: 1.75">
                        curl --request POST \<br />
                        --url '<?= SITE_URL ?>api/links' \<br />
                        --header 'Authorization: Bearer <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                        --header 'Content-Type: multipart/form-data' \<br />
                        --form 'url=<span class="text-primary">example</span>' \<br />
                        --form 'location_url=<span class="text-primary"><?= SITE_URL ?></span>' \<br />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* hide until words are wrapped to avoid flash */
        .reveal-effect { visibility: hidden; }

        /* base state for each word */
        .reveal-effect-prepared .reveal-effect-word {
            opacity: 0;
            filter: blur(6px);
            transform: translate3d(0, 8px, 0);
            display: inline-block;
            transition: opacity .5s ease, filter .5s ease, transform .5s ease;
        }

        /* animate in when container gets .reveal-effect-in */
        .reveal-effect-prepared.reveal-effect-in .reveal-effect-word {
            opacity: 1;
            filter: blur(0);
            transform: none;
        }
    </style>

    <script defer>
        /* wrap words in a text node while preserving existing HTML */
        const wrap_words_in_text_node = (text_node) => {
            /* split into words + spaces, keep spacing intact */
            const tokens = text_node.textContent.split(/(\s+)/);
            const fragment = document.createDocumentFragment();

            tokens.forEach((token) => {
                if (token.trim().length === 0) {
                    fragment.appendChild(document.createTextNode(token));
                } else {
                    const span_node = document.createElement('span');
                    span_node.className = 'reveal-effect-word';
                    span_node.textContent = token;
                    fragment.appendChild(span_node);
                }
            });

            text_node.parentNode.replaceChild(fragment, text_node);
        };

        /* prepare a container: wrap only pure text nodes, not tags */
        const prepare_reveal_container = (container_node) => {
            /* collect first to avoid live-walking issues while replacing */
            const walker = document.createTreeWalker(
                container_node,
                NodeFilter.SHOW_TEXT,
                { acceptNode: (node) => node.textContent.trim().length ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT }
            );
            const text_nodes = [];
            while (walker.nextNode()) { text_nodes.push(walker.currentNode); }
            text_nodes.forEach(wrap_words_in_text_node);

            /* add stagger */
            const word_nodes = container_node.querySelectorAll('.reveal-effect-word');
            word_nodes.forEach((word_node, index) => {
                word_node.style.transitionDelay = (index * 40) + 'ms';
            });

            /* mark as prepared and reveal visibility */
            container_node.classList.add('reveal-effect-prepared');
            container_node.style.visibility = 'visible';
        };

        /* set up scroll trigger */
        document.addEventListener('DOMContentLoaded', () => {
            const container_node = document.querySelector('.reveal-effect');
            if (!container_node) { return; }

            /* prepare once (preserves HTML) */
            prepare_reveal_container(container_node);

            /* trigger when in view */
            const on_intersect = (entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        /* start the animation */
                        setTimeout(() => {
                            container_node.classList.add('reveal-effect-in');
                            observer.unobserve(container_node);
                        }, 200);
                    }
                });
            };

            const intersection_observer = new IntersectionObserver(on_intersect, {
                root: null,
                rootMargin: '0px 0px -10% 0px',
                threshold: 0.1
            });

            intersection_observer.observe(container_node);
        });
    </script>
<?php endif ?>


<?php if(settings()->main->display_index_testimonials): ?>
    <div class="py-3"></div>

    <!-- Custom code: FC-2026-02-25: partners section -->
    <div class="p-3 p-md-4 mt-8">
        <div class="py-7 rounded-2x index-partners-section" style="background: linear-gradient(135deg, rgba(40, 48, 60, 0.96), rgba(48, 58, 72, 0.96)); border: 1px solid rgba(255, 255, 255, 0.08);">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="text-white mb-2 index-partners-title" style="color: #f1f5f9 !important; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.45); opacity: 1;">
                        <?= l('index.partners.header') ?>
                    </h2>
                    <p class="text-gray-400 mb-0 index-partners-subtitle" style="color: #e2e8f0;">
                        <?= l('index.partners.subheader') ?>
                    </p>
                </div>

                <div class="row mx-n3">
                    <div class="col-12 col-md-6 mb-4 px-3" data-aos="fade-up" data-aos-delay="100">
                        <div class="card border-0 text-white h-100 index-partners-card" style="background: rgba(20, 24, 30, 0.98);">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-fw fa-layer-group text-primary mr-2" style="color: #42e2cf;"></i>
                                    <span class="h5 mb-0" style="color: #f1f5f9;">
                                        <?= l('index.partners.card1.title') ?>
                                    </span>
                                </div>
                                <p class="text-gray-400 mb-0" style="color: #d1dae6;">
                                    <?= l('index.partners.card1.text') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-4 px-3" data-aos="fade-up" data-aos-delay="200">
                        <div class="card border-0 text-white h-100 index-partners-card" style="background: rgba(20, 24, 30, 0.98);">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-fw fa-globe text-primary mr-2" style="color: #42e2cf;"></i>
                                    <span class="h5 mb-0" style="color: #f1f5f9;">
                                        <?= l('index.partners.card2.title') ?>
                                    </span>
                                </div>
                                <p class="text-gray-400 mb-0" style="color: #d1dae6;">
                                    <?= l('index.partners.card2.text') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-4 px-3" data-aos="fade-up" data-aos-delay="300">
                        <div class="card border-0 text-white h-100 index-partners-card" style="background: rgba(20, 24, 30, 0.98);">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-fw fa-id-card text-primary mr-2" style="color: #42e2cf;"></i>
                                    <span class="h5 mb-0" style="color: #f1f5f9;">
                                        <?= l('index.partners.card3.title') ?>
                                    </span>
                                </div>
                                <p class="text-gray-400 mb-0" style="color: #d1dae6;">
                                    <?= l('index.partners.card3.text') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-4 px-3" data-aos="fade-up" data-aos-delay="400">
                        <div class="card border-0 text-white h-100 index-partners-card" style="background: rgba(20, 24, 30, 0.98);">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-fw fa-route text-primary mr-2" style="color: #42e2cf;"></i>
                                    <span class="h5 mb-0" style="color: #f1f5f9;">
                                        <?= l('index.partners.card4.title') ?>
                                    </span>
                                </div>
                                <p class="text-gray-400 mb-0" style="color: #d1dae6;">
                                    <?= l('index.partners.card4.text') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Custom code: FC-2026-02-25 -->
<?php endif ?>

<!-- Custom code: FC-2026-03-24: restrict plan visibility to logged in account owners -->
<?php if(settings()->main->display_index_plans && is_logged_in()): ?>
    <div class="py-3"></div>

    <div id="plans" class="container mt-8">
        <div class="text-center mb-5">
            <h2><?= l('index.pricing.header') ?></h2>
        </div>

        <?= $this->views['plans'] ?>
    </div>
<?php endif ?>
<!-- /Custom code: FC-2026-03-24 -->

<?php if(settings()->main->display_index_faq): ?>
    <div class="py-3"></div>

    <div class="container mt-8">
        <div class="text-center mb-5">
            <h2><?= l('index.faq.header') ?></h2>
        </div>

        <?php
        $language_array = \Altum\Language::get(\Altum\Language::$name);
        if(\Altum\Language::$main_name != \Altum\Language::$name) {
            $language_array = array_merge(\Altum\Language::get(\Altum\Language::$main_name), $language_array);
        }

        $faq_language_keys = [];
        foreach ($language_array as $key => $value) {
            if(preg_match('/index\.faq\.(\w+)\./', $key, $matches)) {
                $faq_language_keys[] = $matches[1];
            }
        }

        $faq_language_keys = array_unique($faq_language_keys);
        ?>

        <div class="accordion index-faq" id="faq_accordion">
            <?php foreach($faq_language_keys as $key): ?>
                <div class="card index-highly-rounded">
                    <div class="card-body">
                        <div class="" id="<?= 'faq_accordion_' . $key ?>">
                            <h3 class="mb-0">
                                <button class="btn btn-lg font-weight-500 btn-block d-flex justify-content-between text-gray-800 px-0 icon-zoom-animation no-focus" type="button" data-toggle="collapse" data-target="<?= '#faq_accordion_answer_' . $key ?>" aria-expanded="true" aria-controls="<?= 'faq_accordion_answer_' . $key ?>">
                                    <span class="text-left"><?= l('index.faq.' . $key . '.question') ?></span>

                                    <span data-icon>
                                        <i class="fas fa-fw fa-circle-chevron-down"></i>
                                    </span>
                                </button>
                            </h3>
                        </div>

                        <div id="<?= 'faq_accordion_answer_' . $key ?>" class="collapse text-muted mt-3" aria-labelledby="<?= 'faq_accordion_' . $key ?>" data-parent="#faq_accordion">
                            <?= l('index.faq.' . $key . '.answer') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <!-- Custom code: FC-2026-02-25: FAQ contact note -->
        <div class="text-center mt-4">
            <?= sprintf(l('index.faq.contact_note'), '<a href="' . url('page/contact') . '">' . l('index.faq.contact_link') . '</a>') ?>
        </div>
        <!-- /Custom code: FC-2026-02-25 -->
    </div>

    <?php ob_start() ?>
    <script>
        'use strict';

        $('#faq_accordion').on('show.bs.collapse', event => {
            let svg = event.target.parentElement.querySelector('[data-icon] svg')
            svg.style.transform = 'rotate(180deg)';
            svg.style.color = 'var(--primary)';
        })

        $('#faq_accordion').on('hide.bs.collapse', event => {
            let svg = event.target.parentElement.querySelector('[data-icon] svg')
            svg.style.color = 'var(--primary-800)';
            svg.style.removeProperty('transform');
        })
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>

<?php if(settings()->users->register_is_enabled): ?>
    <div class="py-3"></div>

    <div class="container mt-8" data-aos="fade-up">
        <div class="card index-highly-rounded border-0 index-cta py-5 py-lg-6">
            <div class="card-body row align-items-center justify-content-center">
                <div class="col-12 col-lg-5">
                    <div class="text-center text-lg-left mb-4 mb-lg-0">
                        <!-- Custom code: FC-2026-02-25: CTA contact copy -->
                        <h2 class="h1"><?= l('index.cta.header') ?></h2>
                        <p class="h5"><?= l('index.cta.subheader') ?></p>
                        <!-- /Custom code: FC-2026-02-25 -->
                    </div>
                </div>

                <div class="col-12 col-lg-5 mt-4 mt-lg-0">
                    <div class="text-center text-lg-right">
                        <!-- Custom code: FC-2026-02-25: CTA contact link -->
                        <a href="<?= url('page/contact') ?>" class="btn btn-light badge-pill zoom-animation-subtle">
                            <?= l('index.cta.contact') ?> <i class="fas fa-fw fa-arrow-right"></i>
                        </a>
                        <!-- /Custom code: FC-2026-02-25 -->
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if (!empty($data->blog_posts)): ?>
    <div class="py-3"></div>

    <div class="container mt-8">
        <div class="text-center mb-5">
            <h2><?= sprintf(l('index.blog.header'), '<span class="text-primary">', '</span>') ?></h2>
        </div>

        <div class="row mx-n2 mx-lg-n4">
            <?php foreach($data->blog_posts as $blog_post): ?>
                <div class="col-12 col-lg-4 px-2 py-4 px-lg-4">
                    <div class="card h-100 zoom-animation-subtle position-relative">
                        <div class="card-body">
                            <?php if($blog_post->image): ?>
                                <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" aria-label="<?= $blog_post->title ?>">
                                    <img src="<?= \Altum\Uploads::get_full_url('blog') . $blog_post->image ?>" class="blog-post-image-small img-fluid w-100 rounded mb-4" alt="<?= $blog_post->image_description ?>" loading="lazy" />
                                </a>
                            <?php endif ?>

                            <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" class="stretched-link text-decoration-none">
                                <h3 class="h5 card-title mb-2 d-inline"><?= $blog_post->title ?></h3>
                            </a>

                            <p class="text-muted mb-0"><?= $blog_post->description ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
<?php endif ?>


<?php ob_start() ?>
<link rel="stylesheet" href="<?= ASSETS_FULL_URL . 'css/libraries/aos.min.css?v=' . PRODUCT_CODE ?>">
<style>
    .fcc-home-summary {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.06);
        background:
            radial-gradient(circle at top left, rgba(63, 216, 161, 0.08), transparent 26%),
            radial-gradient(circle at bottom right, rgba(74, 167, 255, 0.12), transparent 24%),
            linear-gradient(180deg, rgba(20, 21, 25, 0.98) 0%, rgba(13, 14, 18, 0.98) 100%);
        box-shadow: 0 1.25rem 3rem rgba(0, 0, 0, 0.26);
    }

    .fcc-home-summary::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.03), transparent 36%),
            linear-gradient(315deg, rgba(255, 255, 255, 0.02), transparent 34%);
        pointer-events: none;
    }

    .fcc-home-summary__inner {
        position: relative;
        display: grid;
        gap: 1.5rem;
    }

    .fcc-home-summary__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.85rem;
        color: rgba(104, 232, 188, 0.92);
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .fcc-home-summary__copy h2 {
        margin-bottom: 0.85rem;
        color: #f7f9fc;
        font-size: clamp(1.7rem, 3vw, 2.4rem);
        line-height: 1.15;
    }

    .fcc-home-summary__copy p {
        max-width: 52rem;
        margin-bottom: 0;
        color: rgba(214, 220, 232, 0.78);
        font-size: 1rem;
        line-height: 1.75;
    }

    .fcc-home-summary__facts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.85rem;
    }

    .fcc-home-summary__fact {
        padding: 1rem 1.1rem;
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        color: rgba(244, 247, 252, 0.92);
        font-weight: 500;
        line-height: 1.6;
        backdrop-filter: blur(6px);
    }

    .fcc-home-summary__links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .fcc-home-summary__link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.75rem;
        padding: 0.75rem 1rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.09);
        background: rgba(255, 255, 255, 0.04);
        color: #f7f9fc !important;
        font-weight: 600;
        text-decoration: none !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease;
    }

    .fcc-home-summary__link:hover,
    .fcc-home-summary__link:focus {
        transform: translateY(-1px);
        border-color: rgba(104, 232, 188, 0.34);
        background: rgba(104, 232, 188, 0.12);
        box-shadow: 0 0.75rem 2rem rgba(0, 0, 0, 0.2);
    }

    .fcc-home-summary .index-icon-container {
        background: rgba(104, 232, 188, 0.14);
        color: rgba(104, 232, 188, 0.96);
    }

    .fcc-presentation-card__title-link {
        display: inline-flex;
        align-items: center;
        gap: 0.7rem;
        color: #f7f9fc !important;
        text-decoration: none !important;
        transition: color 0.2s ease, transform 0.2s ease, opacity 0.2s ease;
    }

    .fcc-presentation-card__title-link span {
        display: inline-block;
    }

    .fcc-presentation-card__title-link::after {
        content: '↗';
        font-family: inherit;
        font-size: 0.95rem;
        color: rgba(104, 232, 188, 0.9);
        opacity: 0.85;
        line-height: 1;
        transform: translateY(-1px);
        transition: transform 0.2s ease, opacity 0.2s ease;
    }

    .fcc-presentation-card__title-link:hover,
    .fcc-presentation-card__title-link:focus {
        color: #bff8eb !important;
        transform: translateX(1px);
    }

    .fcc-presentation-card__title-link:hover::after,
    .fcc-presentation-card__title-link:focus::after {
        opacity: 1;
        transform: translate(2px, -1px);
    }

    .fcc-presentation-card__title-link--muted {
        font-size: 0.92rem;
        font-weight: 700;
    }

    @media (max-width: 767px) {
        .fcc-home-summary__inner {
            gap: 1rem;
        }

        .fcc-home-summary__facts {
            grid-template-columns: 1fr;
        }

        .fcc-home-summary__links {
            flex-direction: column;
        }

        .fcc-home-summary__link {
            width: 100%;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/aos.min.js?v=' . PRODUCT_CODE ?>"></script>

<script>
    'use strict';

    AOS.init({
        duration: 650,
        easing: 'ease-out-cubic',
        once: true,
    });
</script>

<script>
    let count_up_animation = (element, final_append = '', max_duration = 3000, start_on_view = false) => {
        let start_time = null;
        let has_started = false;
        let target = parseInt(element.getAttribute('data-count-up-number'));

        const ease_out = progress => 1 - Math.pow(1 - progress, 8);

        const step = timestamp => {
            if (!start_time) start_time = timestamp;

            const elapsed = timestamp - start_time;
            const progress = Math.min(elapsed / max_duration, 1);
            const eased = ease_out(progress);

            const value = Math.round(eased * target);
            element.textContent = nr(value, 0, false, true);

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                element.textContent = nr(target, 0, false, true) + final_append;
            }
        };

        const start_animation = () => {
            if (has_started) return;
            has_started = true;
            requestAnimationFrame(step);
        };

        if (!start_on_view) {
            start_animation();
            return;
        }

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;

                observer.unobserve(element);
                start_animation();
            });
        }, {
            threshold: 0.3
        });

        observer.observe(element);
    };

    document.querySelectorAll('[data-count-up-number]').forEach(element => {
        let duration = element.getAttribute('data-count-up-duration') || 1500;
        let final_append = element.getAttribute('data-count-up-append') || '';
        count_up_animation(element, final_append, duration, true);
    });
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php ob_start() ?>
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?= settings()->main->title ?>",
        "url": "<?= url() ?>",
    <?php if(settings()->main->{'logo_' . \Altum\ThemeStyle::get()}): ?>
        "logo": "<?= settings()->main->{'logo_' . \Altum\ThemeStyle::get() . '_full_url'} ?>",
        <?php endif ?>
    "slogan": "<?= $data->homepage_semantics['hero_heading'] ?? l('index.header') ?>",
        "contactPoint": {
            "@type": "ContactPoint",
            "url": "<?= url('contact') ?>",
            "contactType": "Contact us"
        }
    }
</script>

<script type="application/ld+json">
    <?php
    $homepage_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $data->homepage_semantics['title'] ?? l('index.title'),
        'description' => $data->homepage_semantics['description'] ?? l('index.meta_description'),
        'url' => url(),
        'inLanguage' => \Altum\Language::$name,
    ];

    if(!empty($data->homepage_semantics)) {
        $homepage_schema['about'] = [
            '@type' => 'DefinedTerm',
            'name' => $data->homepage_semantics['term_name'],
            'alternateName' => $data->homepage_semantics['term_alternate_names'],
            'description' => $data->homepage_semantics['term_description'],
            'inDefinedTermSet' => url('pages/foreverclub'),
        ];
    }
    ?>
    <?= json_encode($homepage_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<script type="application/ld+json">
    <?php
    $homepage_item_list = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $data->homepage_semantics['term_name'] ?? settings()->main->title,
        'itemListElement' => [],
    ];

    if(!empty($data->homepage_semantics['hub_pages'])) {
        foreach($data->homepage_semantics['hub_pages'] as $index => $hub_page) {
            $homepage_item_list['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $hub_page['name'],
                'url' => $hub_page['url'],
            ];
        }
    }
    ?>
    <?= json_encode($homepage_item_list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "<?= $data->homepage_semantics['title'] ?? l('index.title') ?>",
                "item": "<?= url() ?>"
            }
        ]
    }
</script>

<?php if(settings()->main->display_index_faq): ?>
    <?php
    $faqs = [];
    foreach($faq_language_keys as $key) {
        $faqs[] = [
            '@type' => 'Question',
            'name' => l('index.faq.' . $key . '.question'),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => l('index.faq.' . $key . '.answer'),
            ]
        ];
    }
    ?>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": <?= json_encode($faqs) ?>
        }
    </script>
<?php endif ?>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php ob_start() ?>
    <link href="<?= ASSETS_FULL_URL . 'css/index-custom.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
