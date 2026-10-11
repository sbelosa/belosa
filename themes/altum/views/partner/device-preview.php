<?php
defined('ALTUMCODE') || die();
if(getenv('FCC_LOCAL')!=='1'||SITE_URL!=='http://localhost:8095/') { http_response_code(404); return; }
header('Cache-Control: no-store, private');header('X-Robots-Tag: noindex, nofollow');
?>
<!doctype html>
<html lang="hr"><head>
 <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
 <?= fcc_i18n_assets() ?>
 <title>FCC · Pregled aplikacije i weba</title>
 <link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-preview.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner-preview.css') ?>">
</head><body>
<header class="pv-header"><a class="pv-brand" href="<?= url('partner/preview') ?>"><img class="fp-logo" src="<?= SITE_URL ?>partner-assets/fcc-logo.png" width="160" height="41" style="max-width:100%;height:auto" alt="Forever Card Club"><b><em>Studio za pregled</em></b></a><span class="pv-local"><i></i> Lokalno · testni podaci</span><a id="pv-open" href="<?= url('partner') ?>" target="_blank" rel="noopener">Otvori bez okvira ↗</a></header>
<main class="pv-layout">
 <aside class="pv-tools">
  <span class="pv-eyebrow">TVOJE POSLOVANJE. POVEZANO.</span>
  <h1>Isprobaj svaki pogled.</h1><p class="pv-intro">Prava aplikacija u okviru mobitela. Klikaj, uređuj i spremi. Sve ostaje u lokalnoj testnoj verziji.</p>
  <nav class="pv-modes" aria-label="Način pregleda">
   <button type="button" data-mode="app" aria-pressed="true"><span>01</span><b>Mobilna aplikacija<small>Donja navigacija · jednostavan rad</small></b></button>
   <button type="button" data-mode="web" aria-pressed="false"><span>02</span><b>FCC nakon prijave<small>Širi pregled · isti račun i podaci</small></b></button>
   <button type="button" data-mode="public" aria-pressed="false"><span>03</span><b>Javni FCC web<small>Naslovnica, blog i javne kartice</small></b></button>
  </nav>
  <div class="pv-device-controls"><label for="pv-device">Veličina prikaza</label><select id="pv-device"><option value="iphone">iPhone · 390 × 844</option><option value="android">Android · 360 × 800</option><option value="large">Veći mobitel · 430 × 932</option><option value="desktop">Računalo · 1280 × 820</option></select></div>
  <section class="pv-explanation" aria-live="polite"><h2 id="pv-mode-title">Aplikacija za svakodnevni rad</h2><p id="pv-mode-description">Kartica, kontakti, dijeljenje i dnevni zadaci. Ostale alate pronađi pod Više, u istom radnom prostoru.</p></section>
  <details class="pv-accounts"><summary>Podaci za lokalnu prijavu</summary><p>Za početak preporučujemo račun suradnika.</p><dl><dt>Testni PRO · bez dnevnog čekanja</dt><dd>pilot15@fcc.test</dd><dt>Testni Free · bez dnevnog čekanja</dt><dd>pilot15-free@fcc.test</dd><dt>Suradnik PRO</dt><dd>suradnik@fcc.test</dd><dt>Suradnik Free</dt><dd>drugi@fcc.test</dd><dt>Administrator</dt><dd>admin@fcc.test</dd><dt>Lozinka za testne račune</dt><dd><code>FCC-Lokalno-2026!</code></dd></dl><button type="button" id="pv-account">Promijeni testni račun</button><small>Odjava vrijedi i za druge otvorene FCC kartice u ovom pregledniku.</small></details>
  <p class="pv-note">Ovo je pregled na računalu. Instalacija i ponašanje na pravom iPhoneu i Androidu provjeravaju se zasebno.</p>
 </aside>
 <section class="pv-workbench" aria-label="Interaktivni pregled">
  <div class="pv-toolbar"><div class="pv-routes" aria-label="Brzi ulazi">
   <button type="button" data-route="/partner" data-audience="member">Danas</button><button type="button" data-route="/partner/cards" data-audience="member">Moja kartica</button><button type="button" data-route="/partner/more" data-audience="member">Više</button>
   <button type="button" data-route="/" data-audience="public" hidden>Naslovnica</button><button type="button" data-route="/blog?view=public" data-audience="public" hidden>Blog i proizvodi</button><button type="button" data-route="/ana-demo" data-audience="public" hidden>Javna kartica</button>
  </div><button type="button" id="pv-zoom" aria-pressed="false" title="Povećaj na 100 % (pomicanje unutar okvira)">Izvorna veličina</button><button type="button" id="pv-reload" aria-label="Osvježi pregled" title="Osvježi pregled">↻</button></div>
  <div class="pv-stage" id="pv-stage"><div class="pv-device-space" id="pv-space"><div class="pv-device" id="pv-frame" data-shape="phone">
   <div class="pv-status" aria-hidden="true"><span>9:41</span><span class="pv-island"></span><span>● ▰</span></div>
   <iframe id="pv-screen" title="FCC mobilna aplikacija, interaktivni lokalni pregled" data-fcc-preview-mode="app" src="<?= url('partner') ?>"></iframe>
   <div class="pv-home" aria-hidden="true"><span></span></div>
  </div></div></div>
  <footer class="pv-caption"><span id="pv-size">390 × 844 · mobilni prikaz</span><a id="pv-current" href="<?= url('partner') ?>" target="_blank" rel="noopener">/partner ↗</a></footer>
 </section>
</main>
<script src="<?= ASSETS_FULL_URL ?>js/fcc-partner-preview.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner-preview.js') ?>" defer></script>
</body></html>
