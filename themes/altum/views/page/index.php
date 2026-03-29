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

    <?php if(!empty($data->is_foreverclub_page) && (!empty($data->foreverclub_semantics) || !empty($data->foreverclub_workflow) || !empty($data->foreverclub_use_cases))): ?>
        <section class="fcc-page-overview">
            <?php if(!empty($data->foreverclub_semantics)): ?>
                <div class="fcc-page-overview__top">
                    <div>
                        <div class="fcc-page-overview__eyebrow"><?= $fcc_is_hr ? 'FCC pregled' : 'FCC overview' ?></div>
                        <h2><?= $data->foreverclub_semantics['heading'] ?></h2>
                        <p><?= $data->foreverclub_semantics['summary'] ?></p>
                    </div>
                </div>

                <?php if(!empty($data->foreverclub_semantics['solves'])): ?>
                    <div class="fcc-page-overview__solves">
                        <?php foreach($data->foreverclub_semantics['solves'] as $solve): ?>
                            <div class="fcc-page-overview__solve"><?= $solve ?></div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <div class="fcc-page-overview__details">
                <?php if(!empty($data->foreverclub_semantics['facts'])): ?>
                    <details class="fcc-page-overview__detail">
                        <summary><?= $fcc_is_hr ? 'Ključne činjenice' : 'Key facts' ?></summary>
                        <ul>
                            <?php foreach($data->foreverclub_semantics['facts'] as $fact): ?>
                                <li><?= $fact ?></li>
                            <?php endforeach ?>
                        </ul>
                    </details>
                <?php endif ?>

                <?php if(!empty($data->foreverclub_workflow)): ?>
                    <details class="fcc-page-overview__detail">
                        <summary><?= $data->foreverclub_workflow['heading'] ?></summary>
                        <p><?= $data->foreverclub_workflow['intro'] ?></p>
                        <div class="fcc-page-overview__mini-grid">
                            <?php foreach($data->foreverclub_workflow['steps'] as $index => $step): ?>
                                <div class="fcc-page-overview__mini-card">
                                    <span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                    <strong><?= $step['title'] ?></strong>
                                    <p><?= $step['text'] ?></p>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </details>
                <?php endif ?>

                <?php if(!empty($data->foreverclub_use_cases)): ?>
                    <details class="fcc-page-overview__detail">
                        <summary><?= $data->foreverclub_use_cases['heading'] ?></summary>
                        <p><?= $data->foreverclub_use_cases['intro'] ?></p>
                        <div class="fcc-page-overview__mini-grid">
                            <?php foreach($data->foreverclub_use_cases['items'] as $item): ?>
                                <div class="fcc-page-overview__mini-card fcc-page-overview__mini-card--soft">
                                    <strong><?= $item['title'] ?></strong>
                                    <p><?= $item['text'] ?></p>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </details>
                <?php endif ?>
            </div>
        </section>
    <?php endif ?>

    <section class="fcc-page-content">
        <div class="fcc-page-content__inner <?= !empty($data->is_foreverclub_page) ? 'fcc-page-content__inner--foreverclub' : null ?> <?= $data->page->editor == 'wysiwyg' ? 'ql-content' : null ?>">
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

    <?php if(!empty($data->foreverclub_pathways) && (!empty($data->foreverclub_pathways['core_pages']) || !empty($data->foreverclub_pathways['landing_pages']))): ?>
        <section class="fcc-pathways">
            <div class="fcc-pathways__head">
                <h2><?= $fcc_is_hr ? 'Sljedeći korisni koraci' : 'Useful next steps' ?></h2>
                <p><?= $fcc_is_hr ? 'Ovaj vodič je dio šireg FCC sadržajnog klastera. Ovdje su dvije ključne FCC stranice i dodatni landing vodiči koji prirodno nastavljaju ovu temu.' : 'This guide is part of a broader FCC content cluster. Here are two core FCC pages and additional landing guides that naturally continue the topic.' ?></p>
            </div>

            <div class="fcc-pathways__grid">
                <?php foreach($data->foreverclub_pathways['core_pages'] as $row): ?>
                    <?php $row_url = $row->type == 'internal' ? SITE_URL . ($row->language ? \Altum\Language::$active_languages[$row->language] . '/' : null) . 'page/' . $row->url : $row->url; ?>
                    <a href="<?= $row_url ?>" class="fcc-pathways__card">
                        <span class="fcc-pathways__tag"><?= $fcc_is_hr ? 'Ključna FCC stranica' : 'Core FCC page' ?></span>
                        <h3><?= $row->title ?></h3>
                        <?php if(!empty($row->description)): ?>
                            <p><?= $row->description ?></p>
                        <?php endif ?>
                    </a>
                <?php endforeach ?>

                <?php foreach($data->foreverclub_pathways['landing_pages'] as $row): ?>
                    <?php $row_url = $row->type == 'internal' ? SITE_URL . ($row->language ? \Altum\Language::$active_languages[$row->language] . '/' : null) . 'page/' . $row->url : $row->url; ?>
                    <a href="<?= $row_url ?>" class="fcc-pathways__card fcc-pathways__card--accent">
                        <span class="fcc-pathways__tag"><?= $fcc_is_hr ? 'Landing vodič' : 'Landing guide' ?></span>
                        <h3><?= $row->title ?></h3>
                        <?php if(!empty($row->description)): ?>
                            <p><?= $row->description ?></p>
                        <?php endif ?>
                    </a>
                <?php endforeach ?>
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

    .fcc-page-content__inner--foreverclub h2,
    .fcc-page-content__inner--foreverclub h3,
    .fcc-page-content__inner--foreverclub h4,
    .fcc-page-content__inner--foreverclub h5,
    .fcc-page-content__inner--foreverclub h6 {
        color: #6ef2d0;
        font-family: "Space Grotesk", sans-serif;
        line-height: 1.25;
        margin-top: 1.9rem;
        margin-bottom: 0.85rem;
    }

    .fcc-page-content__inner--foreverclub h2 {
        font-size: clamp(1.4rem, 2vw, 1.8rem);
    }

    .fcc-page-content__inner--foreverclub h3 {
        font-size: clamp(1.1rem, 1.5vw, 1.35rem);
    }

    .fcc-page-content__inner--foreverclub a {
        color: #7cf7c7;
        text-decoration: underline;
        text-decoration-color: rgba(124, 247, 199, 0.45);
        text-underline-offset: 0.18em;
        transition: color 0.2s ease, text-decoration-color 0.2s ease;
    }

    .fcc-page-content__inner--foreverclub a:hover {
        color: #a7ffe0;
        text-decoration-color: rgba(167, 255, 224, 0.9);
    }

    .fcc-page-content__inner--foreverclub ul,
    .fcc-page-content__inner--foreverclub ol {
        padding-left: 1.3rem;
    }

    .fcc-page-content__inner--foreverclub li + li {
        margin-top: 0.45rem;
    }

    .fcc-page-share {
        margin-top: 22px;
    }

    .fcc-page-overview {
        position: relative;
        overflow: hidden;
        margin-top: 24px;
        padding: 24px 26px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.06);
        background:
            radial-gradient(circle at top left, rgba(63, 216, 161, 0.08), transparent 28%),
            radial-gradient(circle at bottom right, rgba(74, 167, 255, 0.08), transparent 24%),
            linear-gradient(160deg, rgba(16, 21, 30, 0.96), rgba(9, 13, 20, 0.98));
        box-shadow: 0 20px 46px rgba(0, 0, 0, 0.34);
        display: grid;
        gap: 18px;
    }

    .fcc-page-overview::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.03), transparent 38%),
            linear-gradient(315deg, rgba(255, 255, 255, 0.02), transparent 34%);
        pointer-events: none;
    }

    .fcc-page-overview__top,
    .fcc-page-overview__solves,
    .fcc-page-overview__details {
        position: relative;
    }

    .fcc-page-overview__eyebrow {
        display: inline-flex;
        align-items: center;
        margin-bottom: 10px;
        padding: 0.28rem 0.7rem;
        border-radius: 999px;
        font-size: 0.72rem;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: rgba(104, 232, 188, 0.92);
        background: rgba(104, 232, 188, 0.08);
        border: 1px solid rgba(104, 232, 188, 0.12);
    }

    .fcc-page-overview__top h2 {
        margin-bottom: 8px;
        color: #f5f7ff;
        font-size: clamp(1.45rem, 2.2vw, 1.95rem);
        line-height: 1.12;
        letter-spacing: -0.03em;
    }

    .fcc-page-overview__top p,
    .fcc-page-overview__solve,
    .fcc-page-overview__detail p,
    .fcc-page-overview__detail li,
    .fcc-page-overview__mini-card p {
        color: rgba(223, 228, 240, 0.78);
        line-height: 1.65;
    }

    .fcc-page-overview__solves {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
    }

    .fcc-page-overview__solve {
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        padding: 14px 16px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(255, 255, 255, 0.06);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
    }

    .fcc-page-overview__solve::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 1px;
        background: linear-gradient(90deg, rgba(104, 232, 188, 0.42), rgba(104, 232, 188, 0));
    }

    .fcc-page-overview__details {
        display: grid;
        gap: 12px;
    }

    .fcc-page-overview__detail {
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(255, 255, 255, 0.06);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
        overflow: hidden;
    }

    .fcc-page-overview__detail summary {
        list-style: none;
        cursor: pointer;
        padding: 17px 20px;
        color: #f5f7ff;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 1.03rem;
        transition: background 0.22s ease, color 0.22s ease;
    }

    .fcc-page-overview__detail summary::-webkit-details-marker {
        display: none;
    }

    .fcc-page-overview__detail summary::after {
        content: '+';
        color: #bff8eb;
        font-size: 1.15rem;
        line-height: 1;
    }

    .fcc-page-overview__detail[open] summary::after {
        content: '−';
    }

    .fcc-page-overview__detail[open] summary {
        background: rgba(104, 232, 188, 0.05);
    }

    .fcc-page-overview__detail > p,
    .fcc-page-overview__detail > ul,
    .fcc-page-overview__mini-grid {
        margin: 0;
        padding: 0 20px 20px;
    }

    .fcc-page-overview__detail ul {
        padding-left: 2rem;
    }

    .fcc-page-overview__mini-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 12px;
    }

    .fcc-page-overview__mini-card {
        border-radius: 16px;
        padding: 14px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .fcc-page-overview__mini-card span {
        display: inline-flex;
        margin-bottom: 10px;
        padding: 0.26rem 0.55rem;
        border-radius: 999px;
        background: rgba(104, 232, 188, 0.12);
        color: #bff8eb;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
    }

    .fcc-page-overview__mini-card strong {
        display: block;
        margin-bottom: 6px;
        color: #f5f7ff;
        font-size: 0.96rem;
    }

    .fcc-page-overview__mini-card--soft {
        background: rgba(104, 232, 188, 0.05);
        border-color: rgba(104, 232, 188, 0.1);
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
        text-decoration: none !important;
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
        text-decoration: none !important;
    }

    .fcc-related-card *,
    .fcc-related-card:hover *,
    .fcc-related-card:focus * {
        text-decoration: none !important;
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

    .fcc-pathways {
        margin-top: 24px;
        padding: 22px 24px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.06);
        background: linear-gradient(160deg, rgba(16, 21, 30, 0.96), rgba(9, 13, 20, 0.98));
        box-shadow: 0 16px 34px rgba(0, 0, 0, 0.28);
    }

    .fcc-pathways__head {
        margin-bottom: 16px;
    }

    .fcc-pathways__head h2 {
        margin-bottom: 8px;
        color: #f5f7ff;
        font-size: clamp(1.35rem, 2vw, 1.75rem);
    }

    .fcc-pathways__head p {
        max-width: 54rem;
        margin-bottom: 0;
        color: rgba(223, 228, 240, 0.76);
        line-height: 1.7;
    }

    .fcc-pathways__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }

    .fcc-pathways__card {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 18px;
        border-radius: 18px;
        text-decoration: none !important;
        color: inherit;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .fcc-pathways__card:hover,
    .fcc-pathways__card:focus {
        transform: translateY(-2px);
        border-color: rgba(104, 232, 188, 0.2);
        background: rgba(104, 232, 188, 0.06);
        text-decoration: none !important;
    }

    .fcc-pathways__card *,
    .fcc-pathways__card:hover *,
    .fcc-pathways__card:focus * {
        text-decoration: none !important;
    }

    .fcc-pathways__card--accent {
        border-color: rgba(255, 198, 0, 0.16);
        background: rgba(255, 198, 0, 0.05);
    }

    .fcc-pathways__tag {
        display: inline-flex;
        align-self: flex-start;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.05);
        color: rgba(240, 244, 251, 0.86);
        font-size: 0.76rem;
        font-weight: 700;
    }

    .fcc-pathways__card h3 {
        margin-bottom: 0;
        color: #f5f7ff;
        font-size: 1.02rem;
        line-height: 1.45;
    }

    .fcc-pathways__card p {
        margin-bottom: 0;
        color: rgba(223, 228, 240, 0.75);
        line-height: 1.65;
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

<?php if(!$fcc_is_contact_page): ?>
<?php
$fcc_page_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $data->page->title,
    'name' => $data->page->title,
    'description' => $data->page->description ?: strip_tags($data->page->title),
    'url' => $data->page_url ?? (SITE_URL . ($data->page->language ? \Altum\Language::$active_languages[$data->page->language] . '/' : null) . 'page/' . $data->page->url),
    'datePublished' => !empty($data->page->datetime) ? date(DATE_ATOM, strtotime($data->page->datetime)) : null,
    'dateModified' => !empty($data->page->last_datetime) ? date(DATE_ATOM, strtotime($data->page->last_datetime)) : (!empty($data->page->datetime) ? date(DATE_ATOM, strtotime($data->page->datetime)) : null),
    'inLanguage' => \Altum\Language::$code,
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $data->page_url ?? (SITE_URL . ($data->page->language ? \Altum\Language::$active_languages[$data->page->language] . '/' : null) . 'page/' . $data->page->url),
    ],
    'author' => [
        '@type' => 'Organization',
        'name' => 'Forever Card Club',
        'url' => SITE_URL,
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Forever Card Club',
        'url' => SITE_URL,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => !empty(settings()->main->logo_dark_full_url) ? settings()->main->logo_dark_full_url : (!empty(settings()->main->logo_light_full_url) ? settings()->main->logo_light_full_url : (!empty(settings()->main->favicon_full_url) ? settings()->main->favicon_full_url : null)),
        ],
    ],
];

if(!empty($data->page->image_url)) {
    $fcc_page_schema['image'] = [
        '@type' => 'ImageObject',
        'url' => $data->page->image_url,
    ];
}

if(!empty($data->pages_category->title)) {
    $fcc_page_schema['articleSection'] = $data->pages_category->title;
}

if(!empty($data->is_foreverclub_page) && !empty($data->foreverclub_semantics)) {
    $fcc_page_schema['about'] = [
        '@type' => 'DefinedTerm',
        '@id' => url('pages/foreverclub') . '#fcc-term',
        'name' => $data->foreverclub_semantics['term_name'],
        'alternateName' => $data->foreverclub_semantics['term_alternate_names'],
        'description' => $data->foreverclub_semantics['term_description'],
    ];
    $fcc_page_schema['isPartOf'] = [
        '@type' => 'CollectionPage',
        'name' => $data->pages_category->title ?? 'Forever Card Club',
        'url' => !empty($data->pages_category)
            ? SITE_URL . ($data->pages_category->language ? \Altum\Language::$active_languages[$data->pages_category->language] . '/' : null) . 'pages/' . $data->pages_category->url
            : SITE_URL . 'pages/foreverclub',
    ];
}

$fcc_page_schema = array_filter($fcc_page_schema, static function($value) {
    return $value !== null && $value !== '';
});

$fcc_software_application_schema = null;

if(!empty($data->is_foreverclub_page) && $data->page->url === 'forever-card-club') {
    $fcc_software_application_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        '@id' => SITE_URL . '#fcc-software',
        'name' => 'Forever Card Club',
        'alternateName' => ['FCC', 'Forever Card Club (FCC)'],
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'url' => $data->page_url ?? SITE_URL,
        'description' => $data->page->description ?: ($data->foreverclub_semantics['term_description'] ?? ''),
        'inLanguage' => \Altum\Language::$code,
        'audience' => [
            '@type' => 'Audience',
            'audienceType' => \Altum\Language::$code === 'hr'
                ? 'Forever Living Products partneri'
                : 'Forever Living Products partners',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Forever Card Club',
            'url' => SITE_URL,
        ],
        'featureList' => \Altum\Language::$code === 'hr'
            ? [
                'Osobna aplikacija partnera',
                'Pametni preporučni linkovi',
                'AI asistenti za proizvode',
                'Sustav za prikupljanje kontakata',
                'Analitika i praćenje interesa',
                'NFC kartica povezana s aplikacijom',
                'Usmjeravanje prema službenom Forever web shopu',
            ]
            : [
                'Personal partner app',
                'Smart referral links',
                'AI product assistants',
                'Lead capture system',
                'Analytics and engagement tracking',
                'NFC card connected to the app',
                'Routing toward the official Forever webshop',
            ],
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'EUR',
            'availability' => 'https://schema.org/InStock',
            'url' => SITE_URL . 'contact',
        ],
    ];
}

$fcc_howto_schema = null;
$fcc_howto_map = [
    'forever-card-app' => [
        'name' => [
            'hr' => 'Kako postaviti Forever Card aplikaciju',
            'en' => 'How to set up the Forever Card app',
        ],
        'description' => [
            'hr' => 'Koraci za postavljanje osobne Forever Card aplikacije unutar FCC sustava.',
            'en' => 'Steps for setting up the personal Forever Card app inside the FCC system.',
        ],
        'steps' => [
            'hr' => [
                'Aktiviraj svoju Forever Card aplikaciju i potvrdi osnovne podatke partnera.',
                'Dodaj sadržaj koji želiš pokazati: proizvode, kontakt, preporuke i korisne informacije.',
                'Poveži pametne preporučne linkove i provjeri vodi li aplikacija na pravi službeni Forever web shop.',
                'Podijeli aplikaciju putem poruka, društvenih mreža, QR koda ili NFC kartice.',
            ],
            'en' => [
                'Activate your Forever Card app and confirm the partner basics.',
                'Add the content you want to show: products, contact actions, recommendations, and useful information.',
                'Connect smart referral links and confirm the app routes to the correct official Forever webshop.',
                'Share the app through messages, social media, QR code, or NFC card.',
            ],
        ],
    ],
    'smart-referral-links' => [
        'name' => [
            'hr' => 'Kako rade pametni preporučni linkovi',
            'en' => 'How smart referral links work',
        ],
        'description' => [
            'hr' => 'Koraci koji pokazuju kako FCC pametni linkovi vode posjetitelja prema službenom Forever web shopu.',
            'en' => 'Steps that explain how FCC smart links route visitors toward the official Forever webshop.',
        ],
        'steps' => [
            'hr' => [
                'Partner podijeli svoj preporučni link kroz aplikaciju, poruku ili objavu.',
                'FCC prepoznaje odakle posjetitelj dolazi i određuje odgovarajući tržišni put.',
                'Posjetitelj se usmjerava prema službenom Forever web shopu u svojoj državi.',
                'Preporuka partnera ostaje povezana s korisničkim putem prema kupnji ili daljnjem interesu.',
            ],
            'en' => [
                'The partner shares a referral link through the app, a message, or a post.',
                'FCC detects where the visitor comes from and selects the correct market route.',
                'The visitor is routed toward the official Forever webshop in their country.',
                'The partner referral stays connected to the path toward purchase or further interest.',
            ],
        ],
    ],
    'nfc-card-offline' => [
        'name' => [
            'hr' => 'Kako NFC kartica vodi iz susreta uživo u FCC aplikaciju',
            'en' => 'How the NFC card moves visitors from an in-person meeting into the FCC app',
        ],
        'description' => [
            'hr' => 'Koraci koji pokazuju kako NFC kartica i QR kod otvaraju FCC digitalni put nakon susreta uživo.',
            'en' => 'Steps that show how the NFC card and QR code open the FCC digital journey after an in-person meeting.',
        ],
        'steps' => [
            'hr' => [
                'Na susretu uživo partner pokaže NFC karticu ili QR kod.',
                'Posjetitelj dodirom ili skeniranjem otvori Forever Card aplikaciju.',
                'U aplikaciji odmah vidi proizvode, kontakt, preporuke i sljedeće korake.',
                'Partner nastavlja razgovor kroz kontakt, preporuku ili daljnje praćenje interesa.',
            ],
            'en' => [
                'During an in-person meeting the partner presents the NFC card or QR code.',
                'The visitor opens the Forever Card app with a tap or scan.',
                'Inside the app they immediately see products, contact actions, recommendations, and next steps.',
                'The partner continues the journey through contact, referral guidance, or follow-up.',
            ],
        ],
    ],
    'ai-product-assistants' => [
        'name' => [
            'hr' => 'Kako AI asistenti vode korisnika do proizvoda i sljedećeg koraka',
            'en' => 'How AI assistants guide visitors toward products and the next step',
        ],
        'description' => [
            'hr' => 'Koraci koji pokazuju kako AI asistenti unutar FCC-a pomažu korisniku istražiti proizvode i doći do pravog koraka.',
            'en' => 'Steps that explain how AI assistants inside FCC help visitors explore products and move to the right next step.',
        ],
        'steps' => [
            'hr' => [
                'Korisnik postavlja pitanje unutar FCC aplikacije ili AI bloka.',
                'AI asistent predlaže relevantne proizvode i korisne informacije.',
                'Sustav usmjerava korisnika prema kontaktu, dodatnom objašnjenju ili linku za kupnju.',
                'Partner dobiva jednostavniji i jasniji put od interesa do preporuke ili razgovora.',
            ],
            'en' => [
                'The visitor asks a question inside the FCC app or AI block.',
                'The AI assistant suggests relevant products and useful guidance.',
                'The system routes the visitor toward contact, deeper explanation, or the purchase path.',
                'The partner gets a clearer and simpler path from interest to recommendation or conversation.',
            ],
        ],
    ],
];

if(!empty($data->is_foreverclub_page) && isset($fcc_howto_map[$data->page->url])) {
    $howto = $fcc_howto_map[$data->page->url];
    $lang = \Altum\Language::$code === 'hr' ? 'hr' : 'en';

    $fcc_howto_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => $howto['name'][$lang],
        'description' => $howto['description'][$lang],
        'inLanguage' => \Altum\Language::$code,
        'url' => $data->page_url ?? (SITE_URL . 'page/' . $data->page->url),
        'step' => [],
    ];

    foreach($howto['steps'][$lang] as $index => $step_text) {
        $fcc_howto_schema['step'][] = [
            '@type' => 'HowToStep',
            'position' => $index + 1,
            'name' => $step_text,
            'text' => $step_text,
        ];
    }
}

$fcc_faq_schema = null;

if(!empty($data->is_foreverclub_page) && !empty($data->page->content) && class_exists('DOMDocument')) {
    $faq_items = [];
    $dom = new \DOMDocument();
    $previous_state = libxml_use_internal_errors(true);

    if($dom->loadHTML('<?xml encoding="utf-8" ?>' . $data->page->content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
        $in_faq_section = false;

        foreach($dom->childNodes as $node) {
            if(!in_array($node->nodeName, ['h2', 'h3', 'p'], true)) {
                continue;
            }

            $node_text = trim(html_entity_decode(strip_tags($dom->saveHTML($node)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if($node->nodeName === 'h2') {
                $normalized_heading = function_exists('mb_strtolower') ? mb_strtolower($node_text, 'UTF-8') : strtolower($node_text);
                $in_faq_section = in_array($normalized_heading, ['česta pitanja', 'frequently asked questions'], true);
                continue;
            }

            if(!$in_faq_section) {
                continue;
            }

            if($node->nodeName === 'h3') {
                $faq_items[] = [
                    'question' => $node_text,
                    'answer' => '',
                ];
                continue;
            }

            if($node->nodeName === 'p' && !empty($faq_items)) {
                $last_index = array_key_last($faq_items);

                if($last_index !== null && $faq_items[$last_index]['answer'] === '') {
                    $faq_items[$last_index]['answer'] = $node_text;
                }
            }
        }
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previous_state);

    $faq_items = array_values(array_filter($faq_items, static function($item) {
        return !empty($item['question']) && !empty($item['answer']);
    }));

    if(!empty($faq_items)) {
        $fcc_faq_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static function($item) {
                return [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ],
                ];
            }, $faq_items),
        ];
    }
}
?>
<?php ob_start() ?>
<script type="application/ld+json">
    <?= json_encode($fcc_page_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php if($fcc_software_application_schema): ?>
<?php ob_start() ?>
<script type="application/ld+json">
    <?= json_encode($fcc_software_application_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>

<?php if($fcc_howto_schema): ?>
<?php ob_start() ?>
<script type="application/ld+json">
    <?= json_encode($fcc_howto_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>

<?php if($fcc_faq_schema): ?>
<?php ob_start() ?>
<script type="application/ld+json">
    <?= json_encode($fcc_faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>
<?php endif ?>
