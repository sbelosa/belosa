<?php
defined('ALTUMCODE') || die();
if(!\Altum\Authentication::is_admin()) return;
$inventory=fcc_coach_recipe_inventory();
$query=mb_substr(trim((string)($_GET['q']??'')),0,150);
$matches=array_filter($inventory,static fn($row)=>$query===''||str_contains(fcc_coach_normalize(json_encode([$row['title'],$row['patterns'],array_column($row['products'],'title')],JSON_UNESCAPED_UNICODE)),fcc_coach_normalize($query)));
?>
<a href="<?= url('partner/admin') ?>"><?= htmlspecialchars(fcc_t('← Administracija'), ENT_QUOTES, 'UTF-8') ?></a>
<section class="fp-panel">
    <span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('Znanje za Coacha'), ENT_QUOTES, 'UTF-8') ?></span>
    <h2><?= htmlspecialchars(fcc_t('FCC recepture i upute proizvoda'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= count($inventory) ?> <?= htmlspecialchars(fcc_t('postojećih zapisa. Ovdje možeš usporediti iskustvene bilješke s pregledanim službenim uputama za Hrvatsku.'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><?= htmlspecialchars(fcc_t('Izvor je dostupna kopija FCC koda. Aktualno stanje produkcije nije potvrđeno. Stare kombinacije i količine prikazane su za pregled, nisu odobrenje za preporuku liječenja.'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><?= htmlspecialchars(fcc_t('Coach prepoznaje teme receptura. Za opća pitanja o uporabi koristi pregledanu uputu za proizvod i tržište. Kod bolesti ne određuje dodatke niti osobni raspored uzimanja.'), ENT_QUOTES, 'UTF-8') ?></p>
    <form method="get" action="<?= url('partner/admin') ?>" class="fp-form">
        <input type="hidden" name="recipes" value="1">
        <label for="recipe-search"><?= htmlspecialchars(fcc_t('Pronađi temu ili proizvod'), ENT_QUOTES, 'UTF-8') ?></label>
        <input id="recipe-search" type="search" name="q" value="<?= $h($query) ?>" placeholder="<?= htmlspecialchars(fcc_t('Upiši temu ili naziv proizvoda'), ENT_QUOTES, 'UTF-8') ?>">
        <button class="fp-button" type="submit"><?= htmlspecialchars(fcc_t('Pronađi'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</section>
<p><?= htmlspecialchars(fcc_t('Prikazano:'), ENT_QUOTES, 'UTF-8') ?> <?= count($matches) ?></p>
<?php foreach($matches as $recipe): ?>
<details class="fp-panel" id="recipe-<?= $h($recipe['key']) ?>">
    <summary><strong><?= $h(ucfirst($recipe['title'])) ?></strong> <span class="fp-status"><?= htmlspecialchars(fcc_t('Za stručni pregled'), ENT_QUOTES, 'UTF-8') ?></span></summary>
    <h3><?= htmlspecialchars(fcc_t('Izvorni FCC zapis'), ENT_QUOTES, 'UTF-8') ?></h3>
    <p><?= $h($recipe['original_note']) ?></p>
    <?php if($recipe['original_lines']): ?><ol><?php foreach($recipe['original_lines'] as $line): ?><li><?= $h($line) ?></li><?php endforeach ?></ol><?php endif ?>
    <?php if($recipe['original_monthly']): ?><p><strong><?= htmlspecialchars(fcc_t('Raniji okvir količina:'), ENT_QUOTES, 'UTF-8') ?></strong> <?= $h($recipe['original_monthly']) ?></p><?php endif ?>
    <h3><?= htmlspecialchars(fcc_t('Usporedba po proizvodu'), ENT_QUOTES, 'UTF-8') ?></h3>
    <?php foreach($recipe['products'] as $product): ?>
    <section class="fp-panel">
        <h4><?= $h($product['title']) ?></h4>
        <p><strong><?= htmlspecialchars(fcc_t('Stara uputa u FCC kodu:'), ENT_QUOTES, 'UTF-8') ?></strong> <?= $h($product['legacy_usage']?:fcc_t('Nema zasebne upute u tom izvoru.')) ?></p>
        <?php $usage=$product['official_usage']; if($usage['instructions']): ?>
        <p><strong><?= htmlspecialchars(fcc_t('Pregledana službena uputa za Hrvatsku:'), ENT_QUOTES, 'UTF-8') ?></strong></p>
        <?php foreach($usage['instructions'] as $instruction): ?><p><?= $h($instruction) ?></p><?php endforeach ?>
        <?php foreach($product['safety_notes'] as $note): ?><p><?= $h($note) ?></p><?php endforeach ?>
        <p><a href="<?= $h($usage['source']['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(fcc_t('Otvori službeni izvor'), ENT_QUOTES, 'UTF-8') ?></a></p>
        <p class="fp-muted"><?= htmlspecialchars(fcc_t('Zapis izvora:'), ENT_QUOTES, 'UTF-8') ?> <?= $h($usage['source']['accessed_at']) ?><?= htmlspecialchars(fcc_t('. Provjeri i aktualnu deklaraciju pakiranja.'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?><p><?= htmlspecialchars(fcc_t('Nema dovoljno potvrđenih podataka za prijenos količina Coachu. Potrebna je aktualna lokalna deklaracija i provjera točne inačice proizvoda.'), ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
    </section>
    <?php endforeach ?>
    <p><?= htmlspecialchars(fcc_t('Za razradu recepture potrebni su točno tržište, pakiranje, službena uputa, stručna procjena kombinacije te dokumentirani izvor i datum pregleda. Mjesečni broj pakiranja nije dnevna doza.'), ENT_QUOTES, 'UTF-8') ?></p>
    <details><summary><?= htmlspecialchars(fcc_t('Podrijetlo zapisa'), ENT_QUOTES, 'UTF-8') ?></summary><p><?= $h($recipe['source']) ?></p><p style="overflow-wrap:anywhere"><?= htmlspecialchars(fcc_t('SHA256:'), ENT_QUOTES, 'UTF-8') ?> <?= $h($recipe['source_hash']) ?></p></details>
</details>
<?php endforeach ?>
