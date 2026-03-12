<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-03-02: pages category localized UI copy */
$fcc_is_hr_language = \Altum\Language::$code === 'hr';
$fcc_pages_category_eyebrow = $fcc_is_hr_language ? 'Kategorija' : 'Category';
$fcc_pages_category_subtitle_fallback = $fcc_is_hr_language
    ? 'Pregled svih članaka i smjernica u ovoj kategoriji.'
    : 'Browse all articles and guidelines in this category.';
$fcc_pages_category_total_pages_label = $fcc_is_hr_language ? 'Članaka' : 'Articles';
$fcc_pages_category_total_views_label = $fcc_is_hr_language ? 'Ukupno pregleda' : 'Total views';
$fcc_pages_category_cta = $fcc_is_hr_language ? 'Saznaj više' : 'Learn more';
/* /Custom code: FC-2026-03-02 */
?>

<div class="container">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('pages') ?>"><?= l('pages.index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= l('pages.pages_category.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <!-- Custom code: FC-2026-02-24: pages category premium layout -->
    <section class="fcc-pages-hero">
        <?php
        /* Custom code: FC-2026-02-25: pages category stats */
        $total_pages = count($data->pages ?? []);
        $total_views = 0;
        foreach(($data->pages ?? []) as $page_row) {
            $total_views += (int) ($page_row->total_views ?? 0);
        }
        /* /Custom code: FC-2026-02-25 */
        ?>
        <div class="fcc-pages-hero__text">
            <div class="fcc-pages-eyebrow"><?= $fcc_pages_category_eyebrow ?></div>
            <h1 class="fcc-pages-title"><?= $data->pages_category->title ?></h1>
            <p class="fcc-pages-subtitle"><?= $data->pages_category->description ?: $fcc_pages_category_subtitle_fallback ?></p>
        </div>
        <div class="fcc-pages-hero__meta">
            <div class="fcc-pages-stat">
                <div class="fcc-pages-stat__value"><?= $total_pages ?></div>
                <div class="fcc-pages-stat__label"><?= $fcc_pages_category_total_pages_label ?></div>
            </div>
            <div class="fcc-pages-stat">
                <div class="fcc-pages-stat__value"><?= nr($total_views) ?></div>
                <div class="fcc-pages-stat__label"><?= $fcc_pages_category_total_views_label ?></div>
            </div>
        </div>
    </section>

    <?php if (!empty($data->pages)): ?>
        <div class="fcc-pages-grid">
            <?php foreach($data->pages as $row): ?>
                <?php
                $page_url = $row->type == 'internal'
                    ? SITE_URL . ($row->language ? \Altum\Language::$active_languages[$row->language] . '/' : null) . 'page/' . $row->url
                    : $row->url;
                $page_target = $row->type == 'internal' ? '_self' : '_blank';
                $page_image_url = $row->image_url ?? null;
                $page_image_alt = $row->image_description ?: $row->title;
                $page_initial = mb_strtoupper(mb_substr($row->title, 0, 1));
                ?>

                <a href="<?= $page_url ?>" target="<?= $page_target ?>" class="fcc-page-card">
                    <div class="fcc-page-card__media">
                        <?php if($page_image_url): ?>
                            <img src="<?= $page_image_url ?>" alt="<?= $page_image_alt ?>" loading="lazy" />
                        <?php else: ?>
                            <div class="fcc-page-card__placeholder">
                                <span><?= $page_initial ?></span>
                            </div>
                        <?php endif ?>
                    </div>
                    <div class="fcc-page-card__body">
                        <h3><?= $row->title ?></h3>
                        <?php if(!empty($row->description)): ?>
                            <p><?= $row->description ?></p>
                        <?php endif ?>
                        <span class="fcc-page-card__cta"><?= $fcc_pages_category_cta ?></span>
                    </div>
                </a>
            <?php endforeach ?>
        </div>

    <?php else: ?>
        <div class="mt-4">
            <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                'filters_get' => $data->filters->get ?? [],
                'name' => 'pages',
                'has_secondary_text' => true,
            ]); ?>
        </div>
    <?php endif ?>
    <!-- /Custom code: FC-2026-02-24 -->
</div>

<?php ob_start() ?>
<!-- Custom code: FC-2026-02-24: pages category premium styles -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        font-family: "Manrope", sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.24em;
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 12px;
    }

    .fcc-pages-title {
        font-family: "Space Grotesk", sans-serif;
        font-size: clamp(2rem, 3vw, 2.6rem);
        font-weight: 600;
        color: #f5f7ff;
        margin-bottom: 12px;
    }

    .fcc-pages-subtitle {
        font-family: "Manrope", sans-serif;
        font-size: 1rem;
        color: rgba(219, 225, 238, 0.78);
        line-height: 1.6;
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
        font-family: "Space Grotesk", sans-serif;
        font-size: 1.2rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 6px;
    }

    .fcc-pages-stat__label {
        font-family: "Manrope", sans-serif;
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.6);
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
        min-height: 280px;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .fcc-page-card:hover {
        transform: translateY(-6px);
        border-color: rgba(255, 198, 0, 0.35);
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45);
    }

    .fcc-page-card__media {
        position: relative;
        height: 160px;
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
        font-family: "Space Grotesk", sans-serif;
        font-size: 2.6rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .fcc-page-card__body {
        padding: 18px 20px 22px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
    }

    .fcc-page-card__body h3 {
        font-family: "Space Grotesk", sans-serif;
        font-size: 1.1rem;
        font-weight: 600;
        color: #f4f7ff;
        margin: 0;
    }

    .fcc-page-card__body p {
        font-family: "Manrope", sans-serif;
        font-size: 0.95rem;
        color: rgba(216, 222, 236, 0.7);
        margin: 0;
        line-height: 1.55;
    }

    .fcc-page-card__cta {
        margin-top: auto;
        font-family: "Manrope", sans-serif;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        color: #ffc600;
    }

    @media (max-width: 900px) {
        .fcc-pages-hero {
            flex-direction: column;
        }

        .fcc-pages-hero__meta {
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            min-width: 100%;
        }
    }
</style>
<!-- /Custom code: FC-2026-02-24 -->
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
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "<?= $data->pages_category->title ?>",
                    "item": "<?= SITE_URL . ($data->pages_category->language ? \Altum\Language::$active_languages[$data->pages_category->language] . '/' : null) . 'pages/' . $data->pages_category->url ?>"
                }
            ]
        }
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
