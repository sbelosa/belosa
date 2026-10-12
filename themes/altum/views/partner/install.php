<?php defined('ALTUMCODE') || die(); header('Cache-Control: no-store');
/* Custom code: FC-2026-10-12: install page in the Živi sloj look. It shows one clear action for the
   visitor's device (one tap install, Safari steps, open in browser or a QR code on a computer) and
   explains what the app gives a collaborator, so more people add FCC to the home screen. */
$install_url=url('partner/install');
$open_url=url(is_logged_in()?'partner':'login?redirect=partner');
$notifications_url=url(is_logged_in()?'partner/notifications':'login?redirect=partner/notifications');
$ht=static fn(string $text): string => htmlspecialchars(fcc_t($text), ENT_QUOTES, 'UTF-8');
$zivi=function_exists('fcc_ui_theme_is_zivi')&&fcc_ui_theme_is_zivi();
$qr='';
try {
 $generator=new \SimpleSoftwareIO\QrCode\Generator;
 $qr=(string)$generator->format('svg')->size(168)->margin(0)->errorCorrection('M')->color(14,42,34)->eye(\BaconQrCode\Renderer\Eye\SquareEye::instance())->style('square')->generate($install_url);
 $qr=preg_replace('~^<\?xml[^>]*>\s*~','',$qr);
} catch(\Throwable $e) { $qr=''; }
$copy=[
 'installed'=>fcc_t('FCC je otvoren kao aplikacija. Sve je spremno za rad.'),
 'accepted'=>fcc_t('Instalacija je potvrđena. Otvori FCC Partner s početnog zaslona.'),
 'manual'=>fcc_t('Otvori izbornik preglednika i odaberi dodavanje na početni zaslon.'),
 'done'=>fcc_t('FCC Partner je instaliran. Otvori njegovu ikonu na početnom zaslonu.'),
 'copied'=>fcc_t('Poveznica je kopirana.'),
 'open_app'=>fcc_t('Otvori moj FCC'),
];
$icon=static fn(string $path): string => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$path.'</svg>';
$benefits=[
 ['<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M7 10h6M7 14h4"/><path d="M16 9h2v2h-2zM16 13h2v2h-2z"/>', 'Kartica i QR u jednom dodiru', 'Pokaži kupcu svoju karticu ili QR kod odmah, bez traženja poveznice.'],
 ['<path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10 20a2 2 0 0 0 4 0"/>', 'Obavijesti na vrijeme', 'Podsjetnik za današnji korak programa Moj put, novi kontakt i webinare. Na iPhoneu obavijesti stižu samo u instaliranu aplikaciju.'],
 ['<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>', 'Preko cijelog zaslona', 'Otvara se brzo, bez adresne trake i kartica preglednika.'],
 ['<path d="M12 3l7 3v5c0 5-3.5 8-7 10-3.5-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-4"/>', 'Isti račun, isti podaci', 'Ništa ne preuzimaš iz trgovine aplikacija. Prijavljuješ se istim FCC računom, a podaci su isti na mobitelu i računalu.'],
];
$share_icon='<svg class="install-inline-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M8 7l4-4 4 4"/><path d="M6 11H5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8a1 1 0 0 0-1-1h-1"/></svg>';
$add_icon='<svg class="install-inline-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="M12 8v8M8 12h8"/></svg>';
$menu_icon='<svg class="install-inline-icon" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>';
?>
<!doctype html>
<html lang="<?= fcc_language_tag() ?>"><head><?php fcc_i18n_assets(); ?>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#0B5A45"><meta name="robots" content="noindex,follow"><base href="<?= SITE_URL ?>">
<link rel="manifest" href="<?= SITE_URL ?>partner-assets/manifest.php">
<link rel="apple-touch-icon" href="<?= SITE_URL ?>partner-assets/fcc-app-192.png">
<meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="FCC Partner">
<title><?= $ht('Instaliraj FCC Partner') ?></title>
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner.css') ?>">
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-premium.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner-premium.css') ?>">
<?= $zivi?fcc_ui_theme_assets('app'):'' ?>
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-install.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner-install.css') ?>">
</head><body class="fcc-partner-mode install-body<?= $zivi?' fcc-ui-zivi':'' ?>"><main class="install-shell" data-install-page data-copy="<?= htmlspecialchars(json_encode($copy, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">

<nav class="install-top">
 <a class="install-back" href="<?= $open_url ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg><span><?= $ht('Povratak na FCC') ?></span></a>
 <span class="install-brand"><img src="<?= SITE_URL ?>partner-assets/fcc-app-192.png" width="34" height="34" alt=""><?= $ht('FCC Partner') ?></span>
</nav>

<section class="install-hero">
 <div class="install-hero-copy">
  <span class="fp-eyebrow"><?= $ht('FCC APLIKACIJA') ?></span>
  <h1><?= $ht('FCC uvijek pri ruci') ?></h1>
  <p class="install-lead"><?= $ht('Jedan dodir do tvoje kartice, kontakata i današnjeg koraka. Besplatno, bez trgovine aplikacija i bez novog računa.') ?></p>
  <div class="install-cta">
   <button class="install-primary" type="button" data-install hidden><?= $add_icon ?><?= $ht('Instaliraj aplikaciju') ?></button>
   <a class="install-primary" href="#install-steps" data-install-howto><?= $ht('Pokaži mi kako') ?></a>
   <a class="install-secondary" data-install-open href="<?= $open_url ?>"><?= $ht('Nastavi u pregledniku') ?></a>
  </div>
  <ul class="install-facts"><li><?= $ht('Oko 20 sekundi') ?></li><li><?= $ht('Besplatno') ?></li><li><?= $ht('Isti FCC račun') ?></li></ul>
  <p class="install-status" data-install-status hidden role="status"></p>
 </div>
 <div class="install-phone" aria-hidden="true">
  <div class="install-phone-screen">
   <div class="install-phone-bar"><span>9:41</span><span class="install-phone-dots"></span></div>
   <div class="install-phone-note"><img src="<?= SITE_URL ?>partner-assets/fcc-app-192.png" alt=""><div><strong>FCC Partner</strong><span><?= $ht('Tvoj današnji korak je spreman.') ?></span></div></div>
   <div class="install-phone-grid">
    <?php for($i=0;$i<11;$i++): ?><span class="install-tile install-tile-<?= $i%6 ?>"></span><?php if($i===4): ?><span class="install-tile install-tile-fcc"><img src="<?= SITE_URL ?>partner-assets/fcc-app-192.png" alt=""><small>FCC Partner</small></span><?php endif ?><?php endfor ?>
   </div>
  </div>
 </div>
</section>

<section class="fp-panel install-state install-done" data-install-done hidden>
 <span class="install-state-icon"><?= $icon('<path d="M20 6L9 17l-5-5"/>') ?></span>
 <div><h2><?= $ht('FCC je na tvojem telefonu') ?></h2><p><?= $ht('Još jedan korak: uključi obavijesti, pa će ti FCC javiti današnji korak, novi kontakt i webinar.') ?></p>
 <div class="install-row"><a class="install-primary" href="<?= $notifications_url ?>"><?= $ht('Uključi obavijesti') ?></a><a class="install-secondary" href="<?= $open_url ?>"><?= $ht('Otvori moj FCC') ?></a></div></div>
</section>

<section class="fp-panel install-state" data-install-inapp hidden>
 <span class="install-state-icon"><?= $icon('<path d="M14 3h7v7"/><path d="M10 14L21 3"/><path d="M19 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/>') ?></span>
 <div><h2><?= $ht('Otvori stranicu u pregledniku') ?></h2><p><?= $ht('Unutar WhatsAppa, Instagrama ili Facebooka instalacija nije moguća. U izborniku odaberi otvaranje u pregledniku ili kopiraj poveznicu i zalijepi je u Safari ili Chrome.') ?></p>
 <button type="button" class="install-secondary" data-copy="<?= e($install_url) ?>"><?= $ht('Kopiraj poveznicu') ?></button></div>
</section>

<section class="fp-panel install-state install-desktop" data-install-desktop hidden>
 <?php if($qr): ?><div class="install-qr"><?= $qr ?></div><?php endif ?>
 <div><h2><?= $ht('Skeniraj kamerom mobitela') ?></h2><p><?= $ht('Usmjeri kameru mobitela na kod i dodirni poveznicu. Upute za instalaciju otvorit će se na telefonu.') ?></p>
 <a class="install-link" href="<?= e($install_url) ?>"><?= e($install_url) ?></a>
 <button type="button" class="install-secondary" data-copy="<?= e($install_url) ?>"><?= $ht('Kopiraj poveznicu') ?></button></div>
</section>

<section class="install-section">
 <h2><?= $ht('Zašto aplikacija') ?></h2>
 <div class="install-benefits">
  <?php foreach($benefits as [$path,$title,$text]): ?><article class="fp-panel install-benefit"><span class="install-benefit-icon"><?= $icon($path) ?></span><h3><?= $ht($title) ?></h3><p><?= $ht($text) ?></p></article><?php endforeach ?>
 </div>
</section>

<section class="install-section" id="install-steps" data-install-guide>
 <h2><?= $ht('Kako instalirati') ?></h2>
 <div class="fp-panel install-device" data-install-device="ios"><span class="fp-eyebrow"><?= $ht('IPHONE I IPAD') ?></span><h3><?= $ht('Dodaj ikonu u Safariju') ?></h3><ol class="install-steps">
  <li><?= $ht('Otvori ovu poveznicu u') ?> <strong><?= $ht('Safariju') ?></strong><?= $ht('. Ako si je otvorio u WhatsAppu ili drugoj aplikaciji, odaberi otvaranje u pregledniku.') ?></li>
  <li><?= $ht('Dodirni') ?> <strong><?= $share_icon ?><?= $ht('Dijeli') ?></strong><?= $ht(', kvadrat sa strelicom prema gore. Ovisno o izgledu Safarija, prvo otvori izbornik s tri točkice.') ?></li>
  <li><?= $ht('Odaberi') ?> <strong><?= $add_icon ?><?= $ht('Dodaj na početni zaslon') ?></strong><?= $ht('. Ako vidiš opciju') ?> <strong><?= $ht('Otvori kao web aplikaciju') ?></strong><?= $ht(', ostavi je uključenu i potvrdi') ?> <strong><?= $ht('Dodaj') ?></strong>.</li>
  <li><?= $ht('Otvori novu ikonu') ?> <strong><?= $ht('FCC Partner') ?></strong> <?= $ht('i prijavi se svojim FCC računom ako se prijava zatraži.') ?></li>
 </ol><a class="install-source" href="https://support.apple.com/guide/iphone/iphea86e5236/ios" target="_blank" rel="noopener noreferrer"><?= $ht('Appleove upute ↗') ?></a></div>
 <div class="fp-panel install-device" data-install-device="android"><span class="fp-eyebrow"><?= $ht('ANDROID') ?></span><h3><?= $ht('Instaliraj iz Chromea') ?></h3><ol class="install-steps">
  <li><?= $ht('Otvori ovu poveznicu u') ?> <strong><?= $ht('Chromeu') ?></strong>.</li>
  <li><?= $ht('Dodirni') ?> <strong><?= $ht('Instaliraj FCC Partner') ?></strong> <?= $ht('ako se pojavi gumb. Možeš i otvoriti izbornik Chromea te odabrati') ?> <strong><?= $menu_icon ?><?= $ht('Dodaj na početni zaslon') ?></strong><?= $ht(', zatim') ?> <strong><?= $ht('Instaliraj') ?></strong>.</li>
  <li><?= $ht('Potvrdi instalaciju pa otvori FCC Partner i prijavi se svojim FCC računom.') ?></li>
 </ol><a class="install-source" href="https://support.google.com/chrome/answer/9658361?co=GENIE.Platform%3DAndroid" target="_blank" rel="noopener noreferrer"><?= $ht('Upute za Chrome ↗') ?></a></div>
 <details class="fp-panel install-other" data-install-other hidden><summary><?= $ht('Upute za drugi uređaj') ?></summary><div data-install-other-content></div></details>
</section>

<section class="fp-panel install-section install-after">
 <h2><?= $ht('Nakon instalacije') ?></h2>
 <ol class="install-numbered"><li><?= $ht('Otvori ikonu FCC Partner na početnom zaslonu.') ?></li><li><?= $ht('Prijavi se istim emailom i lozinkom.') ?></li><li><?= $ht('Uključi obavijesti da ti ništa važno ne promakne.') ?></li></ol>
 <a class="install-secondary" href="<?= $notifications_url ?>"><?= $ht('Otvori obavijesti') ?></a>
 <p class="install-muted"><?= $ht('Za provjeru koristi gumb „Pošalji mi probnu obavijest”. Ako je slanje odgođeno zbog mirnog vremena ili ograničenja, aplikacija će to pokazati.') ?></p>
</section>

<section class="install-section install-faq">
 <h2><?= $ht('Česta pitanja') ?></h2>
 <details class="fp-panel"><summary><?= $ht('Zauzima li aplikacija memoriju?') ?></summary><p><?= $ht('Vrlo malo. Sadržaj se učitava s interneta, pa aplikacija ne zauzima prostor kao velike aplikacije iz trgovine.') ?></p></details>
 <details class="fp-panel"><summary><?= $ht('Mogu li je ukloniti?') ?></summary><p><?= $ht('Da, kao svaku ikonu: dugo pritisni ikonu i odaberi uklanjanje. Tvoj račun i podaci ostaju.') ?></p></details>
 <details class="fp-panel"><summary><?= $ht('Ne vidim opciju za dodavanje.') ?></summary><p><?= $ht('Provjeri je li stranica otvorena u Safariju na iPhoneu ili u Chromeu na Androidu, a ne unutar WhatsAppa ili Instagrama.') ?></p></details>
 <details class="fp-panel"><summary><?= $ht('Prijava i pristup') ?></summary><p><?= $ht('Već imaš odobren račun? Koristi isti email i lozinku. Instalacija ne stvara novi račun i tvoji podaci ostaju povezani.') ?></p><?php if(getenv('FCC_LOCAL')!=='1'&&getenv('FCC_PARTNER_ROLLOUT')!=='all'&&!fcc_rollout_all_enabled()): ?><p><?= $ht('Novi FCC trenutačno je dostupan odabranim suradnicima. Ako nakon prijave vidiš prethodni izgled, javi se administratoru radi provjere pristupa.') ?></p><?php endif ?></details>
 <details class="fp-panel"><summary><?= $ht('Još nemam FCC račun') ?></summary><p><?= $ht('Registriraj se na FCC webu i pričekaj odobrenje administratora. Nakon odobrenja koristi isti račun i u aplikaciji.') ?></p><a class="install-secondary" href="<?= url('register') ?>"><?= $ht('Registracija na FCC webu') ?></a></details>
</section>

<p class="install-muted install-foot"><?= $ht('Za rad treba internet. Podaci su isti na telefonu i računalu. Ažuriranja sadržaja vidiš pri ponovnom otvaranju. Ako imaš staru testnu ikonu s privremene adrese, ovu službenu aplikaciju dodaj zasebno.') ?></p>
<?php if(getenv('FCC_PHONE_PILOT')==='1'): ?><div class="fp-message"><strong><?= $ht('Privatni test na tvojem Macu.') ?></strong> <?= $ht('Mac i Docker moraju ostati uključeni. Ovaj test koristi privremenu adresu i nije produkcijska instalacija.') ?></div><?php elseif(getenv('FCC_LOCAL')==='1'): ?><div class="fp-message"><?= $ht('Ovo je lokalni pregled na računalu. Za iPhone pokreni „Pokreni FCC iPhone pregled.command” i otvori HTTPS adresu iz pripremljenih uputa. Localhost na telefonu ne označava ovo računalo.') ?></div><?php endif ?>
</main><script defer src="<?= ASSETS_FULL_URL ?>js/fcc-partner-install.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner-install.js') ?>"></script><script defer src="<?= ASSETS_FULL_URL ?>js/fcc-partner.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner.js') ?>"></script></body></html>
<?php /* /Custom code: FC-2026-10-12 */ ?>
