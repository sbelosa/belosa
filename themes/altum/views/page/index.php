<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-02-26: contact page bilingual labels */
$fcc_is_contact_page = mb_strtolower((string) $data->page->url) === 'contact';
$fcc_is_hr = \Altum\Language::$code === 'hr';

$fcc_contact_title = $fcc_is_hr ? 'Kontaktirajte suradnika' : 'Contact the collaborator';
$fcc_info_note = $fcc_is_hr
    ? 'Napomena: Kontakt podaci pripadaju suradniku i članu Forever Card Cluba. Posjetili ste stranicu putem njegove preporuke i slobodno ga možete kontaktirati za sva pitanja.'
    : 'Note: Contact details belong to a collaborator and Forever Card Club member. You visited this page through their recommendation and can freely contact them with any questions.';
/* /Custom code: FC-2026-02-26 */
?>

<div class="container">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('pages') ?>"><?= l('pages.index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <?php if($data->pages_category): ?>
                    <li><a href="<?= url('pages/' . $data->pages_category->url) ?>"><?= $data->pages_category->title ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <?php endif ?>
                <li class="active" aria-current="page"><?= l('page.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <!-- Custom code: FC-2026-02-25: premium page layout -->
    <?php if(!$fcc_is_contact_page): ?>
        <?php $estimated_reading_time = string_estimate_reading_time($data->page->content) ?>
        <section class="fcc-page-hero">
            <div class="fcc-page-hero__text">
                <div class="fcc-page-eyebrow">
                    <?= $data->pages_category ? $data->pages_category->title : l('page.breadcrumb') ?>
                </div>
                <h1 class="fcc-page-title"><?= $data->page->title ?></h1>
                <?php if(!empty($data->page->description)): ?>
                    <p class="fcc-page-subtitle"><?= $data->page->description ?></p>
                <?php endif ?>
                <div class="fcc-page-meta">
                    <span class="fcc-page-meta__item" data-toggle="tooltip" title="<?= sprintf(l('global.last_datetime_tooltip'), \Altum\Date::get($data->page->last_datetime, 2)) ?>">
                        <?= sprintf(l('global.datetime_tooltip'), \Altum\Date::get($data->page->datetime, 2)) ?>
                    </span>
                    <?php if($data->pages_category): ?>
                        <span class="fcc-page-meta__item">
                            <a href="<?= SITE_URL . ($data->pages_category->language ? \Altum\Language::$active_languages[$data->pages_category->language] . '/' : null) . 'pages/' . $data->pages_category->url ?>">
                                <?= $data->pages_category->title ?>
                            </a>
                        </span>
                    <?php endif ?>
                    <?php if(settings()->content->pages_views_is_enabled): ?>
                        <span class="fcc-page-meta__item"><?= sprintf(l('page.total_views'), nr($data->page->total_views)) ?></span>
                    <?php endif ?>
                    <?php if($estimated_reading_time->minutes > 0 || $estimated_reading_time->seconds > 0): ?>
                        <span class="fcc-page-meta__item">
                            <?= $estimated_reading_time->minutes ? sprintf(l('page.estimated_reading_time'), $estimated_reading_time->minutes . ' ' . l('global.date.minutes')) : null ?>
                            <?= $estimated_reading_time->minutes == 0 && $estimated_reading_time->seconds ? sprintf(l('page.estimated_reading_time'), $estimated_reading_time->seconds . ' ' . l('global.date.seconds')) : null ?>
                        </span>
                    <?php endif ?>
                </div>
            </div>
            <div class="fcc-page-hero__media">
                <?php if(!empty($data->page->image_url)): ?>
                    <img src="<?= $data->page->image_url ?>" alt="<?= $data->page->image_description ?: $data->page->title ?>" loading="lazy" />
                <?php else: ?>
                    <div class="fcc-page-hero__placeholder">
                        <span><?= mb_strtoupper(mb_substr($data->page->title, 0, 1)) ?></span>
                    </div>
                <?php endif ?>
            </div>
        </section>
    <?php endif ?>

    <?php if(!empty($data->is_foreverclub_page) && !empty($data->foreverclub_semantics)): ?>
        <!-- Custom code: FC-2026-03-24: foreverclub semantic summary block -->
        <section class="fcc-page-summary">
            <div class="fcc-page-summary__inner">
                <h2><?= $data->foreverclub_semantics['heading'] ?></h2>
                <p><?= $data->foreverclub_semantics['summary'] ?></p>

                <ul>
                    <?php foreach($data->foreverclub_semantics['facts'] as $fact): ?>
                        <li><?= $fact ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        </section>
        <!-- /Custom code: FC-2026-03-24 -->
    <?php endif ?>

    <section class="fcc-page-content">
        <div class="fcc-page-content__inner <?= $data->page->editor == 'wysiwyg' ? 'ql-content' : null ?>">
            <?= $data->page->content ?>
        </div>
    </section>

    <?php if($fcc_is_contact_page && !empty($data->collaborator_contact)): ?>
        <!-- Custom code: FC-2026-02-26: premium collaborator contact form -->
        <section class="fcc-collab-contact-wrap">
            <div class="fcc-collab-contact-card">
                <div class="fcc-collab-contact-head">
                    <h2><?= $fcc_contact_title ?></h2>
                </div>

                <div class="fcc-collab-contact-details">
                    <?php if(!empty($data->collaborator_contact->name)): ?><span><strong><?= $fcc_is_hr ? 'Ime suradnika:' : 'Collaborator name:' ?></strong> <?= $data->collaborator_contact->name ?></span><?php endif ?>
                    <?php if(!empty($data->collaborator_contact->email)): ?><span><strong>Email:</strong> <a href="mailto:<?= $data->collaborator_contact->email ?>"><?= $data->collaborator_contact->email ?></a></span><?php endif ?>
                    <?php if(!empty($data->collaborator_contact->phone)): ?><span><strong><?= $fcc_is_hr ? 'Telefon:' : 'Phone:' ?></strong> <a href="tel:<?= preg_replace('/\s+/', '', $data->collaborator_contact->phone) ?>"><?= $data->collaborator_contact->phone ?></a></span><?php endif ?>
                    <?php if(!empty($data->collaborator_contact->forever_id)): ?><span><strong><?= $fcc_is_hr ? 'Forever ID:' : 'Forever ID:' ?></strong> <?= $data->collaborator_contact->forever_id ?></span><?php endif ?>
                    <?php if(!empty($data->collaborator_contact->aff_link)): ?><span><strong><?= $fcc_is_hr ? 'Preporuka:' : 'Referral:' ?></strong> <a href="<?= $data->collaborator_contact->aff_link ?>" target="_blank" rel="noopener noreferrer"><?= $data->collaborator_contact->aff_link ?></a></span><?php endif ?>
                </div>

                <small class="fcc-collab-note"><?= $fcc_info_note ?></small>
            </div>
        </section>
        <!-- /Custom code: FC-2026-02-26 -->
    <?php endif ?>

    <?php if(settings()->content->pages_share_is_enabled): ?>
        <section class="fcc-page-share">
            <div class="fcc-page-share__inner">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <?= include_view(THEME_PATH . 'views/partials/share_buttons.php', ['url' => url(\Altum\Router::$original_request), 'class' => 'btn btn-gray-100', 'copy_to_clipboard' => true]) ?>
                </div>
            </div>
        </section>
    <?php endif ?>

    <?php if(!empty($data->is_foreverclub_page) && !empty($data->related_pages)): ?>
        <!-- Custom code: FC-2026-03-24: foreverclub related pages cluster -->
        <section class="fcc-related-pages">
            <div class="fcc-related-pages__head">
                <h2><?= $fcc_is_hr ? 'Povezane stranice' : 'Related pages' ?></h2>
                <p><?= $fcc_is_hr ? 'Dodatna objašnjenja i vodiči unutar Forever Card Club kategorije.' : 'Additional guides and explanations inside the Forever Card Club category.' ?></p>
            </div>

            <div class="fcc-related-grid">
                <?php foreach($data->related_pages as $row): ?>
                    <?php $row_url = $row->type == 'internal' ? SITE_URL . ($row->language ? \Altum\Language::$active_languages[$row->language] . '/' : null) . 'page/' . $row->url : $row->url; ?>
                    <a href="<?= $row_url ?>" class="fcc-related-card">
                        <div>
                            <h3><?= $row->title ?></h3>
                            <?php if(!empty($row->description)): ?>
                                <p><?= $row->description ?></p>
                            <?php endif ?>
                        </div>
                        <span><?= $fcc_is_hr ? 'Otvori' : 'Open' ?></span>
                    </a>
                <?php endforeach ?>
            </div>
        </section>
        <!-- /Custom code: FC-2026-03-24 -->
    <?php endif ?>
    <!-- /Custom code: FC-2026-02-25 -->
</div>

<?php ob_start() ?>
<!-- Custom code: FC-2026-02-25: premium page styles -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .fcc-page-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(0, 0.9fr);
        gap: 24px;
        padding: 28px 32px;
        border-radius: 24px;
        background: radial-gradient(900px circle at 10% 20%, rgba(79, 227, 255, 0.15), transparent 45%),
            radial-gradient(800px circle at 90% 10%, rgba(255, 198, 0, 0.1), transparent 40%),
            linear-gradient(180deg, rgba(15, 18, 28, 0.95), rgba(10, 12, 20, 0.95));
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
    }

    .fcc-page-eyebrow {
        font-family: "Manrope", sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.22em;
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 12px;
    }

    .fcc-page-title {
        font-family: "Space Grotesk", sans-serif;
        font-size: clamp(2rem, 3vw, 2.7rem);
        font-weight: 600;
        color: #f5f7ff;
        margin-bottom: 12px;
    }

    .fcc-page-subtitle {
        font-family: "Manrope", sans-serif;
        font-size: 1rem;
        color: rgba(219, 225, 238, 0.8);
        line-height: 1.6;
        margin-bottom: 18px;
    }

    .fcc-page-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .fcc-page-meta__item {
        font-family: "Manrope", sans-serif;
        font-size: 0.85rem;
        color: rgba(216, 222, 236, 0.7);
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        padding: 6px 12px;
        border-radius: 999px;
    }

    .fcc-page-meta__item a {
        color: inherit;
        text-decoration: none;
    }

    .fcc-page-hero__media {
        border-radius: 18px;
        overflow: hidden;
        min-height: 220px;
        background: linear-gradient(140deg, rgba(30, 38, 52, 0.9), rgba(12, 16, 24, 0.98));
    }

    .fcc-page-hero__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .fcc-page-hero__placeholder {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        font-family: "Space Grotesk", sans-serif;
        font-size: 3rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .fcc-page-content {
        margin-top: 24px;
        background: linear-gradient(160deg, rgba(18, 22, 32, 0.96), rgba(10, 12, 20, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 20px;
        padding: 28px 32px;
        box-shadow: 0 16px 38px rgba(0, 0, 0, 0.4);
    }

    .fcc-page-content__inner {
        font-family: "Manrope", sans-serif;
        color: rgba(230, 236, 247, 0.85);
        line-height: 1.7;
        font-size: 1rem;
    }

    .fcc-page-share {
        margin-top: 22px;
    }

    .fcc-page-summary {
        margin-top: 24px;
    }

    .fcc-page-summary__inner {
        background: linear-gradient(160deg, rgba(20, 26, 38, 0.96), rgba(10, 14, 20, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 20px;
        padding: 24px 28px;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.34);
    }

    .fcc-page-summary__inner h2 {
        color: #f5f7ff;
        margin-bottom: 10px;
    }

    .fcc-page-summary__inner p,
    .fcc-page-summary__inner li {
        color: rgba(228, 233, 243, 0.82);
        line-height: 1.7;
    }

    .fcc-page-summary__inner ul {
        margin: 14px 0 0;
        padding-left: 18px;
    }

    .fcc-related-pages {
        margin-top: 24px;
    }

    .fcc-related-pages__head h2 {
        color: rgba(255, 255, 255, 0.94);
        margin-bottom: 6px;
    }

    .fcc-related-pages__head p {
        color: rgba(219, 225, 238, 0.68);
        margin-bottom: 16px;
    }

    .fcc-related-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
    }

    .fcc-related-card {
        background: linear-gradient(160deg, rgba(18, 24, 34, 0.96), rgba(10, 14, 20, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 18px;
        padding: 18px 20px;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        gap: 16px;
        min-height: 180px;
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .fcc-related-card:hover {
        transform: translateY(-4px);
        border-color: rgba(255, 198, 0, 0.35);
        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.35);
    }

    .fcc-related-card h3 {
        color: #f4f7ff;
        font-size: 1.05rem;
        margin-bottom: 8px;
    }

    .fcc-related-card p {
        color: rgba(216, 222, 236, 0.7);
        line-height: 1.6;
        margin: 0;
    }

    .fcc-related-card span {
        margin-top: auto;
        color: #ffc600;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        font-size: 0.78rem;
    }

    .fcc-page-share__inner {
        background: rgba(16, 20, 30, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 18px;
        padding: 18px 22px;
    }

    @media (max-width: 900px) {
        .fcc-page-hero {
            grid-template-columns: 1fr;
        }
    }

    /* Custom code: FC-2026-02-26: collaborator contact form styles */
    .fcc-collab-contact-wrap {
        margin-top: 24px;
    }

    .fcc-collab-contact-card {
        background: linear-gradient(160deg, rgba(16, 22, 31, 0.95), rgba(8, 12, 18, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.38);
        padding: 28px;
    }

    .fcc-collab-contact-head h2 {
        color: rgba(255, 255, 255, 0.94);
        margin-bottom: 8px;
    }

    .fcc-collab-contact-head p,
    .fcc-collab-note {
        color: rgba(255, 255, 255, 0.66);
        line-height: 1.6;
    }

    .fcc-collab-contact-details {
        display: grid;
        gap: 6px;
        margin: 16px 0 20px;
        color: rgba(255, 255, 255, 0.88);
        font-size: 0.98rem;
    }

    .fcc-collab-contact-details a {
        color: #7cf7c7;
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .fcc-collab-contact-card {
            padding: 18px;
        }
    }
    /* /Custom code: FC-2026-02-26 */
</style>
<!-- /Custom code: FC-2026-02-25 -->
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
                    "name": "<?= l('blog.title') ?>",
                    "item": "<?= url('blog') ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "<?= $data->page->title ?>",
                    "item": "<?= SITE_URL . ($data->page->language ? \Altum\Language::$active_languages[$data->page->language] . '/' : null) . 'page/' . $data->page->url ?>"
                }
            ]
        }
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
