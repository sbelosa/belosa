<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-02-27: premium plan localized copy */
$fcc_plan_eyebrow = l('plan.hero_eyebrow');
$fcc_plan_subtitle = l('plan.hero_default_subtitle');
$fcc_plan_stats_primary_label = l('plan.hero_stat_secure_billing');
$fcc_plan_stats_secondary_label = l('plan.hero_stat_support_included');
/* /Custom code: FC-2026-02-27 */
?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= l('plan.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <?php if(is_logged_in() && $this->user->plan_is_expired && $this->user->plan_id != 'free'): ?>
        <div class="alert alert-info" role="alert">
            <?= l('global.info_message.user_plan_is_expired') ?>
        </div>
    <?php endif ?>

    <?php if($data->type == 'new'): ?>

        <!-- Custom code: FC-2026-02-27: premium plan hero -->
        <section class="fcc-plan-hero mb-4">
            <div class="fcc-plan-hero__text">
                <div class="fcc-plan-eyebrow"><?= $fcc_plan_eyebrow ?></div>
                <h1 class="fcc-plan-title"><?= l('plan.header_new') ?></h1>
                <p class="fcc-plan-subtitle"><?= l('plan.subheader_new') ?: $fcc_plan_subtitle ?></p>
            </div>
            <div class="fcc-plan-hero__meta">
                <div class="fcc-plan-stat">
                    <div class="fcc-plan-stat__value"><i class="fas fa-fw fa-shield-check"></i></div>
                    <div class="fcc-plan-stat__label"><?= $fcc_plan_stats_primary_label ?></div>
                </div>
                <div class="fcc-plan-stat">
                    <div class="fcc-plan-stat__value"><i class="fas fa-fw fa-headset"></i></div>
                    <div class="fcc-plan-stat__label"><?= $fcc_plan_stats_secondary_label ?></div>
                </div>
            </div>
        </section>
        <!-- /Custom code: FC-2026-02-27 -->

    <?php elseif($data->type == 'renew'): ?>

        <!-- Custom code: FC-2026-02-27: premium plan hero -->
        <section class="fcc-plan-hero mb-4">
            <div class="fcc-plan-hero__text">
                <div class="fcc-plan-eyebrow"><?= $fcc_plan_eyebrow ?></div>
                <h1 class="fcc-plan-title"><?= l('plan.header_renew') ?></h1>
                <p class="fcc-plan-subtitle"><?= l('plan.subheader_renew') ?: $fcc_plan_subtitle ?></p>
            </div>
            <div class="fcc-plan-hero__meta">
                <div class="fcc-plan-stat">
                    <div class="fcc-plan-stat__value"><i class="fas fa-fw fa-shield-check"></i></div>
                    <div class="fcc-plan-stat__label"><?= $fcc_plan_stats_primary_label ?></div>
                </div>
                <div class="fcc-plan-stat">
                    <div class="fcc-plan-stat__value"><i class="fas fa-fw fa-headset"></i></div>
                    <div class="fcc-plan-stat__label"><?= $fcc_plan_stats_secondary_label ?></div>
                </div>
            </div>
        </section>
        <!-- /Custom code: FC-2026-02-27 -->

    <?php elseif($data->type == 'upgrade'): ?>

        <!-- Custom code: FC-2026-02-27: premium plan hero -->
        <section class="fcc-plan-hero mb-4">
            <div class="fcc-plan-hero__text">
                <div class="fcc-plan-eyebrow"><?= $fcc_plan_eyebrow ?></div>
                <h1 class="fcc-plan-title"><?= l('plan.header_upgrade') ?></h1>
                <p class="fcc-plan-subtitle"><?= l('plan.subheader_upgrade') ?: $fcc_plan_subtitle ?></p>
            </div>
            <div class="fcc-plan-hero__meta">
                <div class="fcc-plan-stat">
                    <div class="fcc-plan-stat__value"><i class="fas fa-fw fa-shield-check"></i></div>
                    <div class="fcc-plan-stat__label"><?= $fcc_plan_stats_primary_label ?></div>
                </div>
                <div class="fcc-plan-stat">
                    <div class="fcc-plan-stat__value"><i class="fas fa-fw fa-headset"></i></div>
                    <div class="fcc-plan-stat__label"><?= $fcc_plan_stats_secondary_label ?></div>
                </div>
            </div>
        </section>
        <!-- /Custom code: FC-2026-02-27 -->

    <?php endif ?>

    <!-- Custom code: FC-2026-02-27: premium plans wrapper -->
    <div class="mt-4 fcc-plan-layout">
        <?= $this->views['plans'] ?>
    </div>
    <!-- /Custom code: FC-2026-02-27 -->

    <div class="mt-6 row">
            <div class="col-12 col-lg-4 p-3" data-aos="fade-up" data-aos-delay="100">
                <div class="card mb-md-0 h-100 up-animation">
                    <div class="card-body icon-zoom-animation">
                        <div class="index-icon-container mb-3">
                            <i class="fas fa-fw fa-headset text-primary"></i>
                        </div>

                        <h2 class="h6 mb-1"><?= l('plan.why.one.header') ?></h2>

                        <small class="text-muted m-0"><?= l('plan.why.one.subheader') ?></small>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 p-3" data-aos="fade-up" data-aos-delay="200">
                <div class="card mb-md-0 h-100 up-animation">
                    <div class="card-body icon-zoom-animation">
                        <div class="index-icon-container mb-3">
                            <i class="fas fa-fw fa-eye text-primary"></i>
                        </div>

                        <h2 class="h6 mb-1"><?= l('plan.why.two.header') ?></h2>

                        <small class="text-muted m-0"><?= l('plan.why.two.subheader') ?></small>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 p-3" data-aos="fade-up" data-aos-delay="300">
                <div class="card mb-md-0 h-100 up-animation">
                    <div class="card-body icon-zoom-animation">
                        <div class="index-icon-container mb-3">
                            <i class="fas fa-fw fa-bolt text-primary"></i>
                        </div>

                        <h2 class="h6 mb-1"><?= l('plan.why.three.header') ?></h2>

                        <small class="text-muted m-0"><?= l('plan.why.three.subheader') ?></small>
                    </div>
                </div>
            </div>
        </div>

    <div class="mt-7">
        <h1 class="h4"><?= l('plan.faq.header') ?></h1>

        <?php
        $language_array = \Altum\Language::get(\Altum\Language::$name);
        if(\Altum\Language::$main_name != \Altum\Language::$name) {
            $language_array = array_merge(\Altum\Language::get(\Altum\Language::$main_name), $language_array);
        }

        $plan_language_keys = [];
        foreach ($language_array as $key => $value) {
            if(preg_match('/plan\.faq\.(\w+)\./', $key, $matches)) {
                $plan_language_keys[] = $matches[1];
            }
        }

        $plan_language_keys = array_unique($plan_language_keys);
        ?>

        <div class="accordion index-faq mt-4" id="faq_accordion">
            <?php foreach($plan_language_keys as $key): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="" id="<?= 'faq_accordion_' . $key ?>">
                            <h3 class="mb-0">
                                <button class="btn font-weight-500 btn-block d-flex justify-content-between text-gray-800 px-0 icon-zoom-animation no-focus" type="button" data-toggle="collapse" data-target="<?= '#faq_accordion_answer_' . $key ?>" aria-expanded="true" aria-controls="<?= 'faq_accordion_answer_' . $key ?>">
                                    <span><?= l('plan.faq.' . $key . '.question') ?></span>

                                    <span data-icon>
                                        <i class="fas fa-fw fa-circle-chevron-down"></i>
                                    </span>
                                </button>
                            </h3>
                        </div>

                        <div id="<?= 'faq_accordion_answer_' . $key ?>" class="collapse text-muted mt-3" aria-labelledby="<?= 'faq_accordion_' . $key ?>" data-parent="#faq_accordion">
                            <?= l('plan.faq.' . $key . '.answer') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
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
</div>


<?php ob_start() ?>
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "<?= l('index.title') ?>",
                    "item": "<?= url() ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "<?= \Altum\Title::$page_title ?>",
                    "item": "<?= url('plan/' . $data->type) ?>"
                }
            ]
        }
</script>

<?php
$faqs = [];
foreach($plan_language_keys as $key) {
    $faqs[] = [
        '@type' => 'Question',
        'name' => l('plan.faq.' . $key . '.question'),
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => l('plan.faq.' . $key . '.answer'),
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
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php ob_start() ?>
    <link href="<?= ASSETS_FULL_URL . 'css/index-custom.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
    <!-- Custom code: FC-2026-02-27: premium plan styles -->
    <style>
        .fcc-plan-hero {
            background: radial-gradient(1200px circle at 10% 20%, rgba(79, 227, 255, 0.12), transparent 45%),
                radial-gradient(900px circle at 90% 10%, rgba(255, 198, 0, 0.1), transparent 40%),
                linear-gradient(180deg, rgba(13, 18, 28, 0.9), rgba(10, 12, 20, 0.9));
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 22px;
            padding: 28px 32px;
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 24px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.45);
        }

        .fcc-plan-hero__text {
            max-width: 560px;
        }

        .fcc-plan-eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.24em;
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 12px;
        }

        .fcc-plan-title {
            font-size: clamp(2rem, 3vw, 2.6rem);
            font-weight: 600;
            color: #f5f7ff;
            margin-bottom: 12px;
        }

        .fcc-plan-subtitle {
            font-size: 1rem;
            color: rgba(219, 225, 238, 0.78);
            line-height: 1.6;
            margin: 0;
        }

        .fcc-plan-hero__meta {
            display: grid;
            grid-auto-rows: minmax(0, auto);
            gap: 12px;
            min-width: 180px;
        }

        .fcc-plan-stat {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 16px;
        }

        .fcc-plan-stat__value {
            font-size: 1.15rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .fcc-plan-stat__label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.65);
        }

        .fcc-plan-layout .row.justify-content-around {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0;
            justify-content: stretch !important;
        }

        .fcc-plan-layout .row.justify-content-around > [class*="col-"] {
            max-width: none;
            width: 100%;
        }

        @media (max-width: 991.98px) {
            .fcc-plan-hero {
                padding: 24px;
                flex-direction: column;
            }

            .fcc-plan-hero__meta {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                min-width: 0;
            }

            .fcc-plan-layout .row.justify-content-around {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .fcc-plan-hero {
                padding: 20px;
                border-radius: 18px;
            }

            .fcc-plan-hero__meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <!-- /Custom code: FC-2026-02-27 -->
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
