<?php defined('ALTUMCODE') || die(); ?>
<div class="fp-funnel-app" data-fp-panels>
    <header class="fp-funnel-heading">
        <div><span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('TVOJE PREZENTACIJE'), ENT_QUOTES, 'UTF-8') ?></span><h1><?= htmlspecialchars(fcc_t('Funnel studio'), ENT_QUOTES, 'UTF-8') ?></h1><p><?= htmlspecialchars(fcc_t('Od prve preporuke do novog razgovora.'), ENT_QUOTES, 'UTF-8') ?></p></div>
        <a href="<?= $e($data->analytics_url ?? url('funnels-analytics')) ?>" class="fp-button fp-button-outline"><?= fcc_partner_icon('card') ?> <?= htmlspecialchars(fcc_t('Rezultati'), ENT_QUOTES, 'UTF-8') ?></a>
    </header>
    <section class="fp-funnel-banner">
        <div><strong><?= htmlspecialchars(fcc_t('Spremno za tvoju priču.'), ENT_QUOTES, 'UTF-8') ?></strong><p><?= htmlspecialchars(fcc_t('Uredi svoj funnel ili kreni od gotovog predloška.'), ENT_QUOTES, 'UTF-8') ?></p></div>
        <div class="fp-funnel-totals" aria-label="<?= htmlspecialchars(fcc_t('Rezultati tvojih funnela'), ENT_QUOTES, 'UTF-8') ?>">
            <div><span><?= htmlspecialchars(fcc_t('Funneli'), ENT_QUOTES, 'UTF-8') ?></span><b><?= nr(count($funnels)) ?></b></div>
            <div><span><?= htmlspecialchars(fcc_t('Pregledi'), ENT_QUOTES, 'UTF-8') ?></span><b><?= nr($total_views) ?></b></div>
            <div><span><?= htmlspecialchars(fcc_t('Upiti'), ENT_QUOTES, 'UTF-8') ?></span><b><?= nr($total_leads) ?></b></div>
        </div>
    </section>
    <nav class="fp-switcher" aria-label="<?= htmlspecialchars(fcc_t('Funnel studio'), ENT_QUOTES, 'UTF-8') ?>">
        <button type="button" data-fp-panel="my-funnels" aria-controls="my-funnels" aria-pressed="true"><?= htmlspecialchars(fcc_t('Moji funneli ·'), ENT_QUOTES, 'UTF-8') ?> <?= count($funnels) ?></button>
        <button type="button" data-fp-panel="funnel-templates" aria-controls="funnel-templates" aria-pressed="false"><?= htmlspecialchars(fcc_t('Gotovi predlošci'), ENT_QUOTES, 'UTF-8') ?></button>
    </nav>
    <section id="my-funnels" data-fp-panel-content>
        <details class="fp-disclosure">
            <summary><?= htmlspecialchars(fcc_t('+ Izradi prazan funnel'), ENT_QUOTES, 'UTF-8') ?></summary>
            <form method="post">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>">
                <input type="hidden" name="create_vip_funnel" value="1">
                <label class="sr-only" for="fp-funnel-name"><?= htmlspecialchars(fcc_t('Naziv novog funnela'), ENT_QUOTES, 'UTF-8') ?></label>
                <input id="fp-funnel-name" type="text" name="funnel_name" class="form-control" maxlength="120" placeholder="<?= htmlspecialchars(fcc_t('Naziv novog funnela'), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="fp-button"><?= htmlspecialchars(fcc_t('Izradi funnel'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </details>
        <?php if(empty($funnels)): ?>
        <section class="fp-panel fp-empty"><h2><?= htmlspecialchars(fcc_t('Tvoj prvi funnel čeka.'), ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars(fcc_t('Gotov predložak najbrži je početak. Dodaj svoju priču i podijeli poveznicu.'), ENT_QUOTES, 'UTF-8') ?></p><button type="button" class="fp-button" data-fp-panel="funnel-templates"><?= htmlspecialchars(fcc_t('Odaberi predložak →'), ENT_QUOTES, 'UTF-8') ?></button></section>
        <?php endif ?>
        <?php foreach($funnels as $item): $row=$item['row']; $analytics=(array)($item['analytics']??[]); $public_url=(string)($item['public_url']??''); ?>
        <article class="fp-funnel-card">
            <span class="fp-funnel-icon"><?= fcc_partner_icon('share') ?></span>
            <div><h2><?= $e($row->name??fcc_t('Moj funnel')) ?></h2><div class="fp-funnel-card-meta"><span><?= $e(['draft'=>'Nacrt','published'=>'Objavljen','active'=>'Aktivan','archived'=>'Arhiviran'][$row->status??'draft']??$row->status) ?></span><span><?= nr((int)($analytics['views']??0)) ?> <?= htmlspecialchars(fcc_t('pregleda'), ENT_QUOTES, 'UTF-8') ?></span><span><?= nr((int)($analytics['leads']??0)) ?> <?= htmlspecialchars(fcc_t('upita'), ENT_QUOTES, 'UTF-8') ?></span></div></div>
            <a href="<?= $e($item['edit_url']??'#') ?>" class="fp-button"><?= htmlspecialchars(fcc_t('Uredi funnel'), ENT_QUOTES, 'UTF-8') ?> <?= fcc_partner_icon('arrow') ?></a>
            <details class="fp-funnel-details">
                <summary><?= htmlspecialchars(fcc_t('Poveznica i dodatne radnje'), ENT_QUOTES, 'UTF-8') ?></summary>
                <div class="vip-funnel-center-url-row"><input aria-label="<?= htmlspecialchars(fcc_t('Javna poveznica funnela'), ENT_QUOTES, 'UTF-8') ?>" class="vip-funnel-center-url" type="text" readonly value="<?= $e($public_url) ?>" data-vip-funnel-copy-source><button type="button" class="vip-funnel-center-btn" data-vip-funnel-copy><?= htmlspecialchars(fcc_t('Kopiraj URL'), ENT_QUOTES, 'UTF-8') ?></button></div>
                <div class="vip-funnel-center-mini-kpis"><div class="vip-funnel-center-mini-kpi"><span><?= htmlspecialchars(fcc_t('Poslani obrasci'), ENT_QUOTES, 'UTF-8') ?></span><strong><?= nr((int)($analytics['submits']??0)) ?></strong></div><div class="vip-funnel-center-mini-kpi"><span><?= htmlspecialchars(fcc_t('Stopa upita'), ENT_QUOTES, 'UTF-8') ?></span><strong><?= nr((float)($analytics['lead_rate']??0),2) ?>%</strong></div></div>
                <div class="fp-actions"><a href="<?= $e($public_url) ?>" target="_blank" rel="noopener" class="vip-funnel-center-btn"><?= htmlspecialchars(fcc_t('Otvori pregled ↗'), ENT_QUOTES, 'UTF-8') ?></a><a href="<?= $e($item['analytics_url']??'#') ?>" class="vip-funnel-center-btn"><?= htmlspecialchars(fcc_t('Detaljna analitika'), ENT_QUOTES, 'UTF-8') ?></a><button type="button" class="vip-funnel-center-btn vip-funnel-center-btn-danger" data-vip-funnel-delete-open data-funnel-id="<?= (int)($row->vip_funnel_id??0) ?>" data-funnel-name="<?= $e($row->name??fcc_t('Moj funnel')) ?>"><?= htmlspecialchars(fcc_t('Obriši…'), ENT_QUOTES, 'UTF-8') ?></button></div>
            </details>
        </article>
        <?php endforeach ?>
    </section>
    <section id="funnel-templates" data-fp-panel-content>
        <div class="fp-template-grid">
        <?php foreach($templates as $template): ?>
            <article class="fp-template-card">
                <div class="fp-template-visual"><?= fcc_partner_icon('share') ?><?= $e($template['badge']??fcc_t('FCC predložak')) ?></div>
                <div class="fp-template-copy"><h2><?= $e($template['name']??'') ?></h2><p><?= $e($template['description']??'') ?></p><div class="fp-actions">
                <?php foreach((array)($template['languages']??['hr'=>'HR']) as $language_key=>$language_label): ?>
                    <form method="post"><input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>"><input type="hidden" name="import_vip_funnel_template" value="1"><input type="hidden" name="template_key" value="<?= $e($template['key']??'') ?>"><input type="hidden" name="template_language" value="<?= $e($language_key) ?>"><button type="submit" class="fp-button <?= $language_key==='hr'?'':'fp-button-outline' ?>"><?= $language_key==='hr'?fcc_t('Koristi predložak'):fcc_t('English') ?></button></form>
                <?php endforeach ?>
                </div></div>
            </article>
        <?php endforeach ?>
        </div>
    </section>
</div>
