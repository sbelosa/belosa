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

    <?php if(!empty($data->foreverclub_semantics)): ?>
        <!-- Custom code: FC-2026-03-24: foreverclub semantic summary block -->
        <section class="fcc-pages-summary">
            <div class="fcc-pages-summary__inner">
                <h2><?= $data->foreverclub_semantics['heading'] ?></h2>
                <p><?= $data->foreverclub_semantics['summary'] ?></p>

                <ul>
                    <?php foreach($data->foreverclub_semantics['facts'] as $fact): ?>
                        <li><?= $fact ?></li>
                    <?php endforeach ?>
                </ul>

                <?php if(!empty($data->foreverclub_semantics['solves'])): ?>
                    <div class="fcc-pages-summary__solves">
                        <h3><?= $data->foreverclub_semantics['solves_heading'] ?></h3>

                        <div class="fcc-pages-summary__solve-grid">
                            <?php foreach($data->foreverclub_semantics['solves'] as $solve): ?>
                                <div class="fcc-pages-summary__solve"><?= $solve ?></div>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endif ?>
            </div>
        </section>
        <!-- /Custom code: FC-2026-03-24 -->
    <?php endif ?>

    <?php if(!empty($data->foreverclub_landing_pages)): ?>
        <section class="fcc-pages-cluster">
            <div class="fcc-pages-cluster__head">
                <div>
                    <div class="fcc-pages-cluster__eyebrow"><?= $fcc_is_hr_language ? 'Po problemima' : 'Problem-first' ?></div>
                    <h2><?= $fcc_is_hr_language ? 'Vodiči za konkretne Forever poslovne situacije' : 'Guides for specific Forever business situations' ?></h2>
                    <p><?= $fcc_is_hr_language ? 'Ove stranice ciljaju stvarna pitanja koja partneri i AI sustavi najčešće traže: alati, online izgradnja poslovanja, kontakti, AI preporuke i usporedbe s generičkim alatima.' : 'These pages target the practical questions that partners and AI systems most often look for: tools, online business building, leads, AI guidance, and comparisons with generic platforms.' ?></p>
                </div>
                <a href="<?= url('page/how-it-works') ?>" class="fcc-pages-cluster__cta"><?= $fcc_is_hr_language ? 'Kako sustav radi' : 'How the system works' ?></a>
            </div>

            <div class="fcc-pages-cluster__grid">
                <?php foreach($data->foreverclub_landing_pages as $row): ?>
                    <?php $row_url = $row->type == 'internal' ? SITE_URL . ($row->language ? \Altum\Language::$active_languages[$row->language] . '/' : null) . 'page/' . $row->url : $row->url; ?>
                    <a href="<?= $row_url ?>" class="fcc-pages-cluster__card">
                        <span class="fcc-pages-cluster__label"><?= $fcc_is_hr_language ? 'Landing vodič' : 'Landing guide' ?></span>
                        <h3><?= $row->title ?></h3>
                        <?php if(!empty($row->description)): ?>
                            <p><?= $row->description ?></p>
                        <?php endif ?>
                        <span class="fcc-pages-cluster__link"><?= $fcc_is_hr_language ? 'Otvori vodič' : 'Open guide' ?></span>
                    </a>
                <?php endforeach ?>
            </div>
        </section>
    <?php endif ?>

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

    .fcc-pages-cluster {
        margin-bottom: 28px;
        padding: 24px 26px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.06);
        background: linear-gradient(160deg, rgba(17, 22, 32, 0.96), rgba(9, 13, 20, 0.98));
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.3);
    }

    .fcc-pages-cluster__head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 18px;
        margin-bottom: 18px;
    }

    .fcc-pages-cluster__eyebrow {
        margin-bottom: 10px;
        font-size: 0.72rem;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: rgba(104, 232, 188, 0.82);
    }

    .fcc-pages-cluster__head h2 {
        margin-bottom: 8px;
        color: #f5f7ff;
        font-size: clamp(1.45rem, 2.4vw, 1.9rem);
    }

    .fcc-pages-cluster__head p {
        max-width: 54rem;
        margin-bottom: 0;
        color: rgba(223, 228, 240, 0.78);
        line-height: 1.7;
    }

    .fcc-pages-cluster__cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.85rem;
        padding: 0.8rem 1.1rem;
        border-radius: 999px;
        text-decoration: none !important;
        font-weight: 600;
        color: #f5f7ff !important;
        background: rgba(104, 232, 188, 0.12);
        border: 1px solid rgba(104, 232, 188, 0.2);
    }

    .fcc-pages-cluster__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 18px;
    }

    .fcc-pages-cluster__card {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 18px;
        border-radius: 18px;
        text-decoration: none;
        color: inherit;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .fcc-pages-cluster__card:hover,
    .fcc-pages-cluster__card:focus {
        transform: translateY(-2px);
        border-color: rgba(104, 232, 188, 0.22);
        background: rgba(104, 232, 188, 0.06);
    }

    .fcc-pages-cluster__label {
        display: inline-flex;
        align-self: flex-start;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 700;
        color: rgba(191, 248, 235, 0.92);
        background: rgba(104, 232, 188, 0.08);
    }

    .fcc-pages-cluster__card h3 {
        margin-bottom: 0;
        color: #f5f7ff;
        font-size: 1.06rem;
        line-height: 1.4;
    }

    .fcc-pages-cluster__card p {
        margin-bottom: 0;
        color: rgba(223, 228, 240, 0.76);
        line-height: 1.65;
    }

    .fcc-pages-cluster__link {
        margin-top: auto;
        font-weight: 600;
        color: #bff8eb;
    }

    .fcc-pages-summary {
        margin-bottom: 28px;
    }

    .fcc-pages-summary__inner {
        background: linear-gradient(160deg, rgba(18, 24, 34, 0.96), rgba(10, 14, 22, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 20px;
        padding: 24px 28px;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.36);
    }

    .fcc-pages-summary__inner h2 {
        color: #f5f7ff;
        margin-bottom: 10px;
    }

    .fcc-pages-summary__inner p,
    .fcc-pages-summary__inner li {
        color: rgba(223, 228, 240, 0.8);
        line-height: 1.7;
    }

    .fcc-pages-summary__inner ul {
        margin: 14px 0 0;
        padding-left: 18px;
    }

    .fcc-pages-summary__solves {
        margin-top: 22px;
    }

    .fcc-pages-summary__solves h3 {
        color: #f5f7ff;
        font-size: 1rem;
        margin-bottom: 12px;
    }

    .fcc-pages-summary__solve-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }

    .fcc-pages-summary__solve {
        border-radius: 16px;
        padding: 14px 16px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        color: rgba(235, 239, 248, 0.85);
        line-height: 1.6;
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

        .fcc-pages-cluster__head {
            flex-direction: column;
            align-items: flex-start;
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

<?php ob_start() ?>
<!-- Custom code: FC-2026-03-24: pages category ItemList schema -->
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": <?= json_encode($data->pages_category->title) ?>,
        "description": <?= json_encode($data->pages_category->description ?: $fcc_pages_category_subtitle_fallback) ?>,
        "url": <?= json_encode($data->pages_category_url) ?>,
        <?php if(!empty($data->foreverclub_semantics)): ?>
        "about": {
            "@type": "DefinedTerm",
            "name": <?= json_encode($data->foreverclub_semantics['term_name']) ?>,
            "alternateName": <?= json_encode($data->foreverclub_semantics['term_alternate_names']) ?>,
            "description": <?= json_encode($data->foreverclub_semantics['term_description']) ?>
        },
        <?php endif ?>
        "hasPart": {
            "@type": "ItemList",
            "itemListElement": [
                <?php foreach(array_values($data->pages ?? []) as $index => $row): ?>
                {
                    "@type": "ListItem",
                    "position": <?= $index + 1 ?>,
                    "url": <?= json_encode($row->type == 'internal' ? SITE_URL . ($row->language ? \Altum\Language::$active_languages[$row->language] . '/' : null) . 'page/' . $row->url : $row->url) ?>,
                    "name": <?= json_encode($row->title) ?>
                }<?= $index + 1 < count($data->pages ?? []) ? ',' : null ?>
                <?php endforeach ?>
            ]
        }
    }
</script>
<!-- /Custom code: FC-2026-03-24 -->
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
