<?php defined('ALTUMCODE') || die(); $h = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="<?= fcc_language_tag() ?>">
<head><?php fcc_i18n_assets(); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $h($content['title']) ?> <?= htmlspecialchars(fcc_t('| Forever Card Club'), ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= $h($content['description']) ?>">
    <meta name="robots" content="<?= $indexable ? fcc_t('index,follow') : fcc_t('noindex,follow') ?>">
    <link rel="canonical" href="<?= $h(fcc_legal_url($document)) ?>">
    <link rel="icon" href="<?= SITE_URL ?>partner-assets/fcc-icon.png">
    <link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-legal.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-legal.css') ?>">
    <script type="application/ld+json" nonce="<?= $h($nonce) ?>"><?= json_encode(fcc_legal_schema($document, $content), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</head>
<body class="fl-legal">
<a class="fl-skip" href="#sadrzaj"><?= htmlspecialchars(fcc_t('Prijeđi na sadržaj'), ENT_QUOTES, 'UTF-8') ?></a>
<header class="fl-header"><a href="<?= SITE_URL ?>" class="fl-brand"><img src="<?= SITE_URL ?>partner-assets/fcc-icon.png" alt="" width="40" height="40"><span><?= htmlspecialchars(fcc_t('Forever Card Club'), ENT_QUOTES, 'UTF-8') ?><small><?= htmlspecialchars(fcc_t('Podrška poslovanju našeg tima'), ENT_QUOTES, 'UTF-8') ?></small></span></a><nav aria-label="<?= htmlspecialchars(fcc_t('Pravne stranice'), ENT_QUOTES, 'UTF-8') ?>"><a href="<?= $h(fcc_legal_url('privacy')) ?>" <?= $document==='privacy'?'aria-current="page"':'' ?>><?= htmlspecialchars(fcc_t('Privatnost'), ENT_QUOTES, 'UTF-8') ?></a><a href="<?= $h(fcc_legal_url('terms')) ?>" <?= $document==='terms'?'aria-current="page"':'' ?>><?= htmlspecialchars(fcc_t('Uvjeti korištenja'), ENT_QUOTES, 'UTF-8') ?></a></nav></header>
<main id="sadrzaj">
    <section class="fl-hero"><span class="fl-eyebrow"><?= htmlspecialchars(fcc_t('JASNO I ODGOVORNO'), ENT_QUOTES, 'UTF-8') ?></span><h1><?= $h($content['title']) ?></h1><p><?= $h($content['intro']) ?></p><div class="fl-version"><time datetime="<?= $h($config['updated']) ?>"><?= htmlspecialchars(fcc_t('Ažurirano 26. rujna 2026.'), ENT_QUOTES, 'UTF-8') ?></time><span><?= htmlspecialchars(fcc_t('Verzija'), ENT_QUOTES, 'UTF-8') ?> <?= $h($config['version']) ?></span></div></section>
    <section class="fl-operator" aria-labelledby="operator-title"><div><span class="fl-eyebrow"><?= htmlspecialchars(fcc_t('OPERATOR FCC SUSTAVA'), ENT_QUOTES, 'UTF-8') ?></span><h2 id="operator-title"><?= $h($config['operator']['name']) ?></h2><p><?= $h($config['operator']['address']) ?><br><?= htmlspecialchars(fcc_t('OIB:'), ENT_QUOTES, 'UTF-8') ?> <?= $h($config['operator']['registration_id']) ?></p></div><div><span class="fl-eyebrow"><?= htmlspecialchars(fcc_t('KONTAKT I PRIVATNOST'), ENT_QUOTES, 'UTF-8') ?></span><a href="mailto:<?= $h($config['operator']['email']) ?>"><?= $h($config['operator']['email']) ?></a><p><?= htmlspecialchars(fcc_t('Za pitanja, prigovore i zahtjeve u vezi osobnih podataka.'), ENT_QUOTES, 'UTF-8') ?></p></div></section>
    <div class="fl-layout"><nav class="fl-contents" aria-label="<?= htmlspecialchars(fcc_t('Sadržaj dokumenta'), ENT_QUOTES, 'UTF-8') ?>"><h2><?= htmlspecialchars(fcc_t('Na ovoj stranici'), ENT_QUOTES, 'UTF-8') ?></h2><ol><?php foreach($content['sections'] as $section): ?><li><a href="#<?= $h($section['id']) ?>"><?= $h($section['title']) ?></a></li><?php endforeach ?></ol></nav><article class="fl-document" aria-label="<?= $h($content['title']) ?>"><?php foreach($content['sections'] as $index => $section): ?><section id="<?= $h($section['id']) ?>"><h2><?= $index+1 ?>. <?= $h($section['title']) ?></h2><?= $section['html'] ?></section><?php endforeach ?></article></div>
</main>
<footer class="fl-footer"><p><strong><?= htmlspecialchars(fcc_t('Forever Card Club'), ENT_QUOTES, 'UTF-8') ?></strong><br><?= htmlspecialchars(fcc_t('Neovisan sustav tima Stjepana i Snježane Beloša.'), ENT_QUOTES, 'UTF-8') ?></p><nav aria-label="<?= htmlspecialchars(fcc_t('Podnožje'), ENT_QUOTES, 'UTF-8') ?>"><a href="<?= $h(fcc_legal_url('privacy')) ?>"><?= htmlspecialchars(fcc_t('Politika privatnosti'), ENT_QUOTES, 'UTF-8') ?></a><a href="<?= $h(fcc_legal_url('terms')) ?>"><?= htmlspecialchars(fcc_t('Uvjeti korištenja'), ENT_QUOTES, 'UTF-8') ?></a><a href="mailto:<?= $h($config['operator']['email']) ?>"><?= htmlspecialchars(fcc_t('Kontakt'), ENT_QUOTES, 'UTF-8') ?></a></nav></footer>
</body>
</html>
