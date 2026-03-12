<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-02-27: pages index premium localized copy */
$fcc_is_hr_language = \Altum\Language::$code === 'hr';

$fcc_pages_eyebrow = $fcc_is_hr_language ? 'Stranice' : 'Pages';
$fcc_pages_hero_subtitle = l('pages.subheader');
$fcc_pages_stats_categories = $fcc_is_hr_language ? 'Kategorija' : 'Categories';
$fcc_pages_stats_popular = $fcc_is_hr_language ? 'Popularnih stranica' : 'Popular pages';
$fcc_pages_categories_heading = $fcc_is_hr_language ? 'Kategorije' : 'Categories';
$fcc_pages_popular_heading = $fcc_is_hr_language ? 'Popularne stranice' : 'Popular pages';
$fcc_pages_cta_label = $fcc_is_hr_language ? 'Saznaj više' : 'Learn more';
/* /Custom code: FC-2026-02-27 */
?>

<div class="container">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= l('pages.index.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <?php
    /* Custom code: FC-2026-02-27: pages index premium stats */
    $fcc_total_categories = count($data->pages_categories ?? []);
    $fcc_total_popular = count($data->popular_pages ?? []);
    /* /Custom code: FC-2026-02-27 */
    ?>

    <!-- Custom code: FC-2026-02-27: pages index premium layout -->
    <section class="fcc-pages-hero">
        <div class="fcc-pages-hero__text">
            <div class="fcc-pages-eyebrow"><?= $fcc_pages_eyebrow ?></div>
            <h1 class="fcc-pages-title"><?= l('pages.header') ?></h1>
            <p class="fcc-pages-subtitle"><?= $fcc_pages_hero_subtitle ?></p>
        </div>
        <div class="fcc-pages-hero__meta">
            <div class="fcc-pages-stat">
                <div class="fcc-pages-stat__value"><?= nr($fcc_total_categories) ?></div>
                <div class="fcc-pages-stat__label"><?= $fcc_pages_stats_categories ?></div>
            </div>
            <div class="fcc-pages-stat">
                <div class="fcc-pages-stat__value"><?= nr($fcc_total_popular) ?></div>
                <div class="fcc-pages-stat__label"><?= $fcc_pages_stats_popular ?></div>
            </div>
        </div>
    </section>

    <?php if(count($data->pages_categories) || count($data->popular_pages)): ?>

        <?php if (!empty($data->pages_categories)): ?>
            <section class="mb-4">
                <h2 class="h5 mb-3 fcc-pages-section-title"><?= $fcc_pages_categories_heading ?></h2>
                <div class="fcc-pages-grid">
                    <?php foreach($data->pages_categories as $row): ?>
                        <?php $fcc_category_image_url = $row->image_url ?? null; ?>
                        <a href="<?= SITE_URL . ($row->language ? \Altum\Language::$active_languages[$row->language] . '/' : null) . 'pages/' . $row->url ?>" class="fcc-page-card">
                            <div class="fcc-page-card__media">
                                <?php if($fcc_category_image_url): ?>
                                    <img src="<?= $fcc_category_image_url ?>" alt="<?= $row->title ?>" loading="lazy" decoding="async" />
                                <?php else: ?>
                                    <div class="fcc-page-card__placeholder">
                                        <?php if(!empty($row->icon)): ?>
                                            <i class="<?= $row->icon ?>"></i>
                                        <?php else: ?>
                                            <span><?= mb_strtoupper(mb_substr($row->title, 0, 1)) ?></span>
                                        <?php endif ?>
                                    </div>
                                <?php endif ?>
                            </div>

                            <div class="fcc-page-card__body">
                                <h3><?= $row->title ?></h3>
                                <span class="fcc-page-card__cta"><?= $fcc_pages_cta_label ?></span>
                            </div>
                        </a>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endif ?>

        <?php if (!empty($data->popular_pages)): ?>
            <section class="mb-2">
                <h2 class="h5 mb-3 fcc-pages-section-title"><?= $fcc_pages_popular_heading ?></h2>
                <div class="fcc-pages-grid">
                    <?php foreach($data->popular_pages as $row): ?>
                        <?php
                        $fcc_page_url = $row->type == 'internal'
                            ? SITE_URL . ($row->language ? \Altum\Language::$active_languages[$row->language] . '/' : null) . 'page/' . $row->url
                            : $row->url;
                        $fcc_page_target = $row->type == 'internal' ? '_self' : '_blank';
                        $fcc_page_image_url = $row->image_url ?? (!empty($row->image) ? \Altum\Uploads::get_full_url('pages') . $row->image : null);
                        ?>
                        <a href="<?= $fcc_page_url ?>" target="<?= $fcc_page_target ?>" class="fcc-page-card">
                            <div class="fcc-page-card__media">
                                <?php if($fcc_page_image_url): ?>
                                    <img src="<?= $fcc_page_image_url ?>" alt="<?= $row->title ?>" loading="lazy" decoding="async" />
                                <?php else: ?>
                                    <div class="fcc-page-card__placeholder">
                                        <span><?= mb_strtoupper(mb_substr($row->title, 0, 1)) ?></span>
                                    </div>
                                <?php endif ?>
                            </div>
                            <div class="fcc-page-card__body">
                                <h3><?= $row->title ?></h3>
                                <?php if(!empty($row->description)): ?>
                                    <p><?= $row->description ?></p>
                                <?php endif ?>
                                <span class="fcc-page-card__cta"><?= $fcc_pages_cta_label ?></span>
                            </div>
                        </a>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endif ?>

    <?php else: ?>
        <div class="mt-4">
            <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                'filters_get' => $data->filters->get ?? [],
                'name' => 'pages',
                'has_secondary_text' => true,
            ]); ?>
        </div>
    <?php endif ?>
    <!-- /Custom code: FC-2026-02-27 -->
</div>

<?php ob_start() ?>
<!-- Custom code: FC-2026-02-27: pages index premium styles -->
<style>
    .fcc-pages-hero {
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
        margin-bottom: 28px;
    }

    .fcc-pages-hero__text {
        max-width: 520px;
    }

    .fcc-pages-eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.24em;
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 12px;
    }

    .fcc-pages-title {
        font-size: clamp(2rem, 3vw, 2.6rem);
        font-weight: 600;
        color: #f5f7ff;
        margin-bottom: 12px;
    }

    .fcc-pages-subtitle {
        font-size: 1rem;
        color: rgba(219, 225, 238, 0.78);
        line-height: 1.6;
        margin: 0;
    }

    .fcc-pages-hero__meta {
        display: grid;
        grid-auto-rows: minmax(0, auto);
        gap: 12px;
        min-width: 180px;
    }

    .fcc-pages-stat {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        padding: 16px;
    }

    .fcc-pages-stat__value {
        font-size: 1.2rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 6px;
    }

    .fcc-pages-stat__label {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .fcc-pages-section-title {
        color: #f3f6ff;
    }

    .fcc-pages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 22px;
    }

    .fcc-page-card {
        background: linear-gradient(160deg, rgba(22, 28, 40, 0.95), rgba(12, 16, 24, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 18px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        min-height: 260px;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .fcc-page-card:hover {
        transform: translateY(-6px);
        border-color: rgba(255, 198, 0, 0.35);
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45);
    }

    .fcc-page-card__media {
        position: relative;
        height: 140px;
        background: radial-gradient(circle at 30% 20%, rgba(79, 227, 255, 0.2), transparent 45%),
            linear-gradient(135deg, rgba(36, 44, 58, 0.9), rgba(14, 18, 26, 0.95));
    }

    .fcc-page-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .fcc-page-card__placeholder {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        font-size: 2.2rem;
        color: rgba(255, 255, 255, 0.72);
    }

    .fcc-page-card__body {
        padding: 18px 20px 22px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
    }

    .fcc-page-card__body h3 {
        margin: 0;
        font-size: 1.05rem;
        line-height: 1.45;
        color: #f5f8ff;
        font-weight: 600;
    }

    .fcc-page-card__body p {
        margin: 0;
        font-size: 0.92rem;
        color: rgba(210, 218, 234, 0.78);
        line-height: 1.55;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .fcc-page-card__cta {
        margin-top: auto;
        font-size: 0.85rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #ffc600;
    }

    @media (max-width: 991.98px) {
        .fcc-pages-hero {
            padding: 24px;
            flex-direction: column;
        }

        .fcc-pages-hero__meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            min-width: 0;
        }
    }

    @media (max-width: 575.98px) {
        .fcc-pages-hero {
            padding: 20px;
            border-radius: 18px;
        }

        .fcc-pages-hero__meta {
            grid-template-columns: 1fr;
        }
    }
</style>
<!-- /Custom code: FC-2026-02-27 -->
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

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
                    "name": "<?= l('pages.title') ?>",
                    "item": "<?= url('pages') ?>"
                }
            ]
        }
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

