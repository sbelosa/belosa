<?php
defined('ALTUMCODE') || die();
$h = 'fcc_partner_h';
$lang = fcc_business_landing_locale($guestLanguage);
$t = fn($hr, $en) => fcc_business_landing_translate($lang, $hr, $en);
$owner = $s['owner'] ?? null;
$asset = $s['asset'] ?? null;
/* Custom code: FC-2026-10-12: a PRO owner's personal video comes before the team video */
$personalVideo = !$private && $s && function_exists('fcc_invite_personal_video') ? fcc_invite_personal_video($s) : null;
if($personalVideo) $asset = $personalVideo;
$registerUrl = !$private && $s && function_exists('fcc_invite_register_url') ? fcc_invite_register_url((int) $s['user_id']) : SITE_URL.'register';
/* /Custom code: FC-2026-10-12 */
// A test asset is never presented as a real business presentation.
$showAsset = $asset && !empty($asset['approved']) && ($asset['status'] ?? '') === 'published' && empty($asset['is_demo']);
$video = $showAsset ? fcc_webinar_video_url(['provider'=>$asset['provider'], 'id'=>$asset['video_id'], 'hash'=>$asset['video_hash']], true) : '';
$landingModel = in_array($s['landing_model'] ?? '', ['overview','personal','products'], true) ? $s['landing_model'] : 'overview';
$isLandingPreview = !empty($landingPreview) || !empty($s['landing_preview']) || !empty($data->landing_preview);
$nonceField = '<input type="hidden" name="nonce" value="'.$h($nonce['value']).'">';
$localPublic = SITE_URL.$lang.'/business/'.$secret;
$publicAction = fn($action) => $localPublic.'/'.$action;
$wa = '';
if($owner && !empty($owner['phone'])) {
    $message = $t('Pozdrav! Pogledao/la sam tvoju Forever poslovnu prezentaciju. Zanima me Start Your Journey paket i kako mogu započeti uz tvoju podršku. Možeš li mi pomoći oko sljedećeg koraka?', 'Hello! I have viewed your Forever business presentation. I am interested in the Start Your Journey pack and getting started with your support. Could you help me with my next step?');
    if($country) $message .= $t(' Moja zemlja: ', ' My country: ').fcc_business_country_label($country, $lang).'.';
    $message .= "\n\n".$localPublic.($country ? '?country='.rawurlencode($country) : '');
    $wa = 'https://wa.me/'.$owner['phone'].'?text='.rawurlencode($message);
}
$hero = [
    'overview' => [
        'eyebrow' => $t('TVOJ SLJEDEĆI KORAK', 'YOUR NEXT STEP'),
        'title' => $t('Upoznaj posao. Odaberi svoj početak.', 'Explore the business. Choose your starting point.'),
        'lead' => $t('Sve važno o Forever proizvodima, samostalnoj suradnji i početnom paketu nalazi se ovdje. Saznaj kako se radi i započni uz osobnu podršku.', 'Everything you need to know about Forever products, independent business and the starter pack is here. Explore the work and get started with personal support.'),
    ],
    'personal' => [
        'eyebrow' => $t('OSOBNA PREPORUKA ZA TEBE', 'A PERSONAL INTRODUCTION FOR YOU'),
        'title' => $t('Tvoj početak, uz osobu koju poznaješ.', 'Your first step, with someone you know.'),
        'lead' => $t('Pogledaj kako izgleda Forever suradnja, upoznaj početni paket i odluči što ti odgovara. Osoba koja ti je poslala ovu stranicu tvoj je kontakt za pitanja i prve korake.', 'See how a Forever business works, explore the starter pack and decide what suits you. The person who shared this page is your contact for questions and first steps.'),
    ],
    'products' => [
        'eyebrow' => $t('PROIZVODI KAO POČETAK', 'START WITH THE PRODUCTS'),
        'title' => $t('Upoznaj proizvode. Istraži suradnju.', 'Discover the products. Explore the business.'),
        'lead' => $t('Start Your Journey okuplja odabrane Forever proizvode za početak. Uz paket upoznaj kako izgleda njihova preporuka i prodaja te kakvu podršku možeš dobiti.', 'Start Your Journey brings together selected Forever products to get started. Explore the pack, how product recommendations and sales work, and the support available to you.'),
    ],
][$landingModel];
$details = $starter['details'] ?? ['price'=>null, 'qualification'=>false, 'once'=>false];
$price = $details['price'] ?? null;
$priceText = '';
if($price) {
    $priceFormatter = new \NumberFormatter(fcc_intl_locale($lang), \NumberFormatter::CURRENCY);
    $priceFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
    $priceText = $priceFormatter->formatCurrency((float)$price['amount'], $price['currency']);
}
?>
<!doctype html>
<html lang="<?= $h(fcc_language_tag($lang)) ?>">
<head>
    <?php fcc_i18n_assets(); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#f5f7f2">
    <?php if(!$private && !empty($s['available'])): ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Forever Card Club">
    <meta property="og:title" content="<?= $h($hero['title']) ?>">
    <meta property="og:description" content="<?= $h($hero['lead']) ?>">
    <meta property="og:url" content="<?= $h($localPublic) ?>">
    <meta property="og:image" content="<?= $h(SITE_URL.'partner-assets/icon-512.png') ?>">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:locale" content="<?= $h(fcc_opengraph_locale($lang)) ?>">
    <meta name="twitter:card" content="summary">
    <?php endif ?>
    <title><?= $h($t('Poslovna prezentacija', 'Business presentation')) ?> | FCC</title>
    <link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-business.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-business.css') ?>">
    <script defer src="<?= ASSETS_FULL_URL ?>js/fcc-business.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-business.js') ?>"></script>
    <link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-business-public.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-business-public.css') ?>">
</head>
<body class="fb-public fb-final fb-model-<?= $h($landingModel) ?><?= (!$private && !$result && !empty($s['available'])) ? ' fb-has-dock' : '' ?>" data-business-final>
<a class="fb-skip" href="#main"><?= $h($t('Prijeđi na sadržaj', 'Skip to content')) ?></a>
<header class="fb-public-header">
    <a class="fb-brand" href="#main"><img src="<?= SITE_URL ?>partner-assets/fcc-logo.png" width="160" height="41" alt="Forever Card Club"><small><?= $h($t('Neovisni Forever suradnici', 'Independent Forever business owners')) ?></small></a>
    <?php if(!$private && !empty($s['available']) && !$result): ?>
    <nav aria-label="<?= $h($t('Na ovoj stranici', 'On this page')) ?>"><a href="#how-it-works"><?= $h($t('Kako se radi', 'How it works')) ?></a><a href="#packages"><?= $h($t('Paket i početak', 'Pack and getting started')) ?></a><a href="#interest" class="fb-nav-contact"><?= $h($t('Tvoj kontakt', 'Your contact')) ?> ↗</a></nav>
    <?php endif ?>
</header>
<main class="fb-public-main" id="main">
<?php if($isLandingPreview && !$private): ?>
    <aside class="fb-preview-note" aria-label="<?= $h($t('Administratorski pregled', 'Administrator preview')) ?>"><strong><?= $h($t('Pregled nove prezentacije', 'New presentation preview')) ?></strong><span><?= $h($t('Vidljivo administratorima dok se priprema završni video.', 'Visible to administrators while the final video is being prepared.')) ?></span></aside>
<?php endif ?>
<?php if($error && ($private || !($s['available'] ?? false) || $action !== 'interest')): ?>
    <p class="fb-alert" role="alert"><?= $h($lang === 'hr' ? $error : $t('Provjeri podatke i pokušaj ponovno.', 'Please review your details and try again.')) ?></p>
<?php endif ?>
<?php if($private): ?>
    <section class="fb-card fb-narrow">
        <span class="fb-eyebrow"><?= $h($t('TVOJ UPIT', 'YOUR REQUEST')) ?></span>
        <h1><?= $h($erased ? $t('Podaci upita su uklonjeni.', 'Your request data has been removed.') : $t('Upravljanje upitom.', 'Manage your request.')) ?></h1>
        <?php if(!$erased): ?>
        <p><?= $h($r['status'] === 'withdrawn' ? $t('Zahtjev za javljanje je povučen.', 'Your request for a reply has been withdrawn.') : $t('Zatražio/la si odgovor o poslovnoj suradnji. Ovdje možeš povući taj zahtjev ili ukloniti podatke upita.', 'You requested a reply about the business opportunity. You can withdraw that request or remove its data here.')) ?></p>
        <div class="fb-actions">
            <?php if($r['status'] === 'received'): ?><form method="post"><?= $nonceField ?><input type="hidden" name="action" value="withdraw"><button class="fb-button fb-outline"><?= $h($t('Povuci zahtjev za javljanje', 'Withdraw my request')) ?></button></form><?php endif ?>
            <a href="?download=data"><?= $h($t('Preuzmi podatke upita', 'Download request data')) ?></a>
        </div>
        <details class="fb-details"><summary><?= $h($t('Ukloni podatke ovog upita', 'Remove this request’s data')) ?></summary><form method="post" class="fb-form"><?= $nonceField ?><input type="hidden" name="action" value="erase"><label class="fb-check"><input type="checkbox" name="confirm_erase" value="1" required><span><?= $h($t('Želim ukloniti podatke ovog upita.', 'I want to remove this request’s data.')) ?></span></label><p><?= $h($t('Ranija, zasebna povijest komunikacije može ostati u skladu s Politikom privatnosti.', 'Separate earlier communication may remain in accordance with the Privacy Policy.')) ?></p><button class="fb-button fb-outline"><?= $h($t('Ukloni podatke', 'Remove data')) ?></button></form></details>
        <?php endif ?>
    </section>
<?php elseif(!$s['available']): ?>
    <section class="fb-card fb-narrow"><h1><?= $h($t('Ova prezentacija više nije dostupna.', 'This presentation is no longer available.')) ?></h1><p><?= $h($t('Zatraži novu poveznicu od osobe koja ti je poslala prezentaciju.', 'Please ask the person who shared this presentation for a new link.')) ?></p></section>
<?php elseif($result): ?>
    <section class="fb-card fb-narrow fb-receipt" id="interest"><span class="fb-eyebrow"><?= $h($t('HVALA NA INTERESU', 'THANK YOU FOR YOUR INTEREST')) ?></span><h1><?= $h(!empty($result['preview']) ? $t('Probni upit je provjeren.', 'Your test request has been checked.') : $t('Tvoj upit je zaprimljen.', 'Your request has been received.')) ?></h1><p><?= $h($t('Tvoja osoba za kontakt: ', 'Your contact: ').$owner['name']) ?>.</p><p><?= $h(!empty($result['preview']) ? $t('Ovo je administratorski test obrasca. Upit nije poslan suradniku niti su poslane obavijesti. Možeš nastaviti i pregledati početni paket.', 'This is an administrator form test. No enquiry was sent to the business owner and no notifications were sent. You can continue and explore the starter pack.') : $t('Odgovor ćeš dobiti putem odabranog kanala. Ako želiš, možeš nastaviti i pregledati početni paket.', 'You will receive a reply through your chosen channel. You can also continue and explore the starter pack.')) ?></p><div class="fb-actions"><a class="fb-button" href="<?= $h($localPublic.'?country='.rawurlencode($country)) ?>#packages"><?= $h($t('Paket i registracija', 'Pack and registration')) ?></a><?php if(!empty($result['manage_token'])): ?><a class="fb-text-link" href="<?= SITE_URL.'business-request/'.$h($result['manage_token']) ?>"><?= $h($t('Upravljaj svojim upitom', 'Manage your request')) ?></a><?php endif ?></div></section>
<?php else: ?>
    <section class="fb-landing-hero" aria-labelledby="business-title">
        <div class="fb-hero-copy">
            <span class="fb-eyebrow"><?= $h($hero['eyebrow']) ?></span>
            <h1 id="business-title"><?= $h($hero['title']) ?></h1>
            <p class="fb-lead"><?= $h($hero['lead']) ?></p>
            <div class="fb-person fb-hero-person"><span class="fb-avatar" aria-hidden="true"><?= $h(mb_strtoupper(mb_substr($owner['name'], 0, 1))) ?></span><div><small><?= $h($t('Tvoja preporuka i podrška', 'Your referral and support')) ?></small><strong><?= $h($owner['name']) ?></strong></div></div>
            <?php if(!empty($s['intro'])): ?><p class="fb-personal-note fb-hero-personal"><?= $h($s['intro']) ?></p><?php endif ?>
            <div class="fb-actions fb-main-actions"><a class="fb-button" href="#packages"><?= $h($t('Paket i registracija', 'Pack and registration')) ?> <span aria-hidden="true">↗</span></a><?php if($wa): ?><a class="fb-button fb-outline" href="<?= $h($wa) ?>" data-guest-whatsapp data-country="<?= $h($country) ?>" data-endpoint="<?= $h($publicAction('action')) ?>" data-nonce="<?= $h($nonce['value']) ?>" target="_blank" rel="noopener noreferrer"><?= $h($t('Pitaj na WhatsAppu', 'Ask on WhatsApp')) ?></a><?php else: ?><a class="fb-button fb-outline" href="#interest"><?= $h($t('Javi se svojem kontaktu', 'Contact your business owner')) ?></a><?php endif ?></div>
            <p class="fb-hero-note"><?= $h($t('Pogledaj informacije svojim tempom. Odluči kada ti odgovara.', 'Explore at your own pace. Decide when you are ready.')) ?></p>
        </div>
        <?php if($video): ?>
        <div class="fb-presentation" id="presentation">
            <div class="fb-video" data-business-video data-embed="<?= $h($video) ?>"><button type="button" class="fb-video-load"><span class="fb-video-kicker"><?= $h($t('POGLEDAJ PREZENTACIJU', 'WATCH THE PRESENTATION')) ?></span><span class="fb-play" aria-hidden="true">▶</span><strong><?= $h($t('Kako izgleda Forever suradnja', 'What a Forever business looks like')) ?></strong><small><?= $h($t('Video se učitava tek kada ga pokreneš.', 'The video loads only when you play it.')) ?></small></button></div>
            <div class="fb-video-caption"><p><strong><?= $h($asset['title']) ?></strong><?php if(!empty($asset['duration_seconds'])): ?><span><?= (int)ceil($asset['duration_seconds']/60) ?> min</span><?php endif ?></p><small><?= $h($t('Pokretanjem učitavaš sadržaj s video platforme.', 'Playing loads content from the video platform.')) ?> <?= $asset['provider'] === 'youtube' ? 'YouTube' : 'Vimeo' ?>.</small><a href="<?= $h(fcc_webinar_video_url(['provider'=>$asset['provider'], 'id'=>$asset['video_id'], 'hash'=>$asset['video_hash']])) ?>" target="_blank" rel="noopener noreferrer"><?= $h($t('Otvori video zasebno', 'Open video separately')) ?> ↗</a></div>
            <?php if(!empty($asset['summary']) || !empty($asset['description'])): ?><details class="fb-details fb-video-summary"><summary><?= $h($t('O ovom videu', 'About this video')) ?></summary><p><?= nl2br($h($asset['summary'] ?: $asset['description'])) ?></p></details><?php endif ?>
        </div>
        <?php else: ?>
        <aside class="fb-intro-note" aria-label="<?= $h($t('Što te čeka na ovoj stranici', 'What you will find on this page')) ?>">
            <span class="fb-eyebrow"><?= $h($t('OD INFORMACIJE DO POČETKA', 'FROM INFORMATION TO YOUR FIRST STEP')) ?></span><h2><?= $h($t('Jasan početak, korak po korak.', 'A clear start, step by step.')) ?></h2><ol><li><?= $h($t('Upoznaj proizvode i način rada.', 'Discover the products and the work.')) ?></li><li><?= $h($t('Provjeri početni paket za svoju zemlju.', 'Check the starter pack for your country.')) ?></li><li><?= $h($t('Nastavi na službenu prijavu ili pitaj svoj kontakt.', 'Continue to official registration or ask your contact.')) ?></li></ol><a class="fb-text-link" href="#how-it-works"><?= $h($t('Pogledaj kako se radi', 'Explore how it works')) ?> ↓</a>
        </aside>
        <?php endif ?>
    </section>

    <nav class="fb-page-path" aria-label="<?= $h($t('Sadržaj prezentacije', 'Presentation contents')) ?>"><a href="#how-it-works"><span>01</span><?= $h($t('Kako se radi', 'How it works')) ?></a><a href="#support"><span>02</span><?= $h($t('Što dobivaš u timu', 'What you get in the team')) ?></a><a href="#packages"><span>03</span><?= $h($t('Paket i početak', 'Pack and getting started')) ?></a><a href="#after-order"><span>04</span><?= $h($t('Nakon narudžbe', 'After your order')) ?></a><a href="#faq"><span>05</span><?= $h($t('Tvoja pitanja', 'Your questions')) ?></a></nav>

    <section class="fb-how" id="how-it-works" aria-labelledby="how-title">
        <div class="fb-section-heading"><span class="fb-eyebrow"><?= $h($t('ŠTO ĆEŠ RADITI', 'WHAT YOU WILL DO')) ?></span><h2 id="how-title"><?= $h($landingModel === 'products' ? $t('Proizvodi koje upoznaješ i preporučuješ.', 'Products to learn about and recommend.') : $t('Stvarni proizvodi. Razgovori s ljudima.', 'Real products. Conversations with people.')) ?></h2><p><?= $h($t('Forever suradnja temelji se na preporuci i prodaji proizvoda te podršci kupcima. Možeš odabrati želiš li se usmjeriti na kupce ili razvijati i tim.', 'A Forever business involves recommending and selling products and supporting customers. You can choose to focus on customers or also develop a team.')) ?></p></div>
        <div class="fb-how-grid">
            <article><span class="fb-step-number">01</span><h3><?= $h($t('Upoznaj proizvode', 'Get to know the products')) ?></h3><p><?= $h($t('U ponudi su napitci na bazi aloe vere, dodaci prehrani i proizvodi za osobnu njegu. Učiš o njihovoj namjeni, sastavu i uporabi prema službenim informacijama.', 'The range includes aloe vera drinks, supplements and personal care products. Learn about their purpose, ingredients and use from official information.')) ?></p></article>
            <article><span class="fb-step-number">02</span><h3><?= $h($t('Razgovaraj i preporuči', 'Talk and recommend')) ?></h3><p><?= $h($t('Saznaj što kupca zanima, predstavi odgovarajuće proizvode i pošalji svoju poveznicu na službenu trgovinu. Ostani dostupan za pitanja i nastavak komunikacije.', 'Understand what interests a customer, introduce suitable products and share your referral link to the official shop. Stay available for questions and follow up.')) ?></p></article>
            <article><span class="fb-step-number">03</span><h3><?= $h($t('Razvijaj suradnju', 'Develop your business')) ?></h3><p><?= $h($t('Zainteresiranim osobama možeš predstaviti poslovnu mogućnost. Kada započnu, pomažeš im upoznati proizvode, organizirati rad i podržati njihove prve kupce.', 'You can introduce the business opportunity to interested people. When they get started, help them learn about products, organise their work and support their first customers.')) ?></p></article>
        </div>
        <p class="fb-expectation"><?= $h($t('Riječ je o samostalnoj poslovnoj suradnji. Potrebni su vrijeme, učenje i rad s ljudima. Prihod ovisi o prodaji i uvjetima poslovnog programa, bez zajamčene zarade.', 'This is an independent business opportunity. It takes time, learning and working with people. Income depends on sales and the business programme terms, with no guaranteed earnings.')) ?></p>
    </section>

    <section class="fb-support" id="support" aria-labelledby="support-title">
        <?php /* Custom code: FC-2026-10-12: what joining through this team brings */ ?>
        <div class="fb-support-copy"><span class="fb-eyebrow"><?= $h($t('ŠTO DOBIVAŠ U NAŠEM TIMU', 'WHAT YOU GET IN OUR TEAM')) ?></span><h2 id="support-title"><?= $h($t('Ne krećeš sam/a.', 'You do not start alone.')) ?></h2><p><?= $h(strtr($t('Kada kreneš uz {name}, dobivaš osobnu podršku, praktične alate i jasan plan za prve korake.', 'When you start with {name}, you get personal support, practical tools and a clear plan for your first steps.'), ['{name}'=>$owner['name']])) ?></p></div>
        <div class="fb-support-points">
            <article><span aria-hidden="true">✓</span><div><h3><?= $h($t('Osobni sponzor i mentor', 'A personal sponsor and mentor')) ?></h3><p><?= $h(strtr($t('{name} ti pomaže dogovoriti prve korake i ostaje uz tebe dok gradiš posao.', '{name} helps you agree your first steps and stays with you while you build your business.'), ['{name}'=>$owner['name']])) ?></p></div></article>
            <article><span aria-hidden="true">✓</span><div><h3><?= $h($t('FCC aplikacija i AI savjetnik', 'The FCC app and AI advisor')) ?></h3><p><?= $h($t('Osobna digitalna kartica, poveznice na službenu trgovinu i AI savjetnik koji tvojim kupcima pomaže izabrati proizvod.', 'A personal digital card, links to the official shop and an AI advisor that helps your customers choose a product.')) ?></p></div></article>
            <article><span aria-hidden="true">✓</span><div><h3><?= $h($t('Moj put: 90 praktičnih koraka', 'My journey: 90 practical steps')) ?></h3><p><?= $h($t('Jedan jasan korak dnevno, s primjerom poruke i pitanjem za sponzora. Prvih 7 koraka otvoreno je odmah nakon registracije.', 'One clear step a day, with an example message and a question for your sponsor. The first 7 steps open right after you register.')) ?></p></div></article>
            <article><span aria-hidden="true">✓</span><div><h3><?= $h($t('Webinari i WhatsApp grupe', 'Webinars and WhatsApp groups')) ?></h3><p><?= $h($t('Timski webinari i grupe u kojima učiš od suradnika koji već rade.', 'Team webinars and groups where you learn from business owners who are already active.')) ?></p></div></article>
            <p class="fb-small"><?= $h($t('FCC aplikaciju možeš koristiti bez pretplate. PRO paket je neobavezan i dodaje napredne alate. Kupnja Forever paketa sama po sebi ne aktivira FCC PRO.', 'You can use the FCC app without a subscription. The PRO plan is optional and adds advanced tools. A Forever pack purchase does not itself activate FCC PRO.')) ?></p>
        </div>
        <?php /* /Custom code: FC-2026-10-12 */ ?>
    </section>

    <section class="fb-card fb-start" id="packages" aria-labelledby="package-title">
        <div class="fb-package-heading"><div><span class="fb-eyebrow"><?= $h($t('ODABERI SVOJ POČETAK', 'CHOOSE YOUR START')) ?></span><h2 id="package-title"><?= $h($t('Start Your Journey paket i registracija.', 'Start Your Journey pack and registration.')) ?></h2><p><?= $h($t('Odaberi zemlju u kojoj živiš. Ponuda i službeni postupak prijave prate tu zemlju, uz preporuku osobe koja ti je poslala ovu stranicu.', 'Choose the country where you live. Your offer and official registration process follow that country, with a referral from the person who shared this page.')) ?></p></div><span class="fb-package-mark" aria-hidden="true">SYJ</span></div>
        <form method="get" action="<?= $h($localPublic) ?>#packages" class="fb-country" data-business-country>
            <label for="fb-country"><?= $h($t('Zemlja prebivališta', 'Country of residence')) ?></label><select name="country" id="fb-country" required><option value=""><?= $h($t('Odaberi zemlju', 'Choose a country')) ?></option><?php foreach(fcc_business_countries() as $code=>$label): ?><option value="<?= $h($code) ?>" <?= $country === $code ? 'selected' : '' ?>><?= $h(fcc_business_country_label($code, $lang)) ?></option><?php endforeach ?></select><button type="submit" class="fb-button fb-outline" data-country-submit><?= $h($t('Prikaži paket', 'Show pack')) ?></button>
        </form>
        <?php if(!$country): ?>
        <div class="fb-package-await"><span aria-hidden="true">↗</span><p><?= $h($t('Nakon odabira zemlje prikazat ćemo dostupni početak i aktualne provjerene podatke o paketu.', 'Select your country to see available ways to get started and current reviewed pack information.')) ?></p></div>
        <?php elseif($starter): ?>
        <article class="fb-starter" aria-labelledby="starter-title">
            <?php if($starter['image']): ?><div class="fb-starter-image"><img src="<?= $h($starter['image']) ?>" alt="<?= $h($starter['title']) ?>" width="<?= (int)$starter['width'] ?>" height="<?= (int)$starter['height'] ?>" loading="lazy" decoding="async"><small><?= $h($t('Primjer početnog paketa. Dostupnu varijantu biraš u službenom postupku.', 'Example starter pack. Choose your available variant in the official process.')) ?></small></div><?php endif ?>
            <div class="fb-starter-copy"><span class="fb-eyebrow"><?= $h(fcc_business_country_label($country, $lang)) ?></span><h3 id="starter-title"><?= $h($starter['title']) ?></h3><p><?= $h($t('Početni paket odabranih Forever proizvoda. Dostupne varijante i točan sadržaj provjeri na službenoj stranici prije naručivanja.', 'A starter pack of selected Forever products. Review available variants and exact contents on the official website before ordering.')) ?></p>
                <?php if($starter['destination']): ?>
                    <?php if($price): ?><div class="fb-starter-price"><strong><?= $h($priceText) ?></strong><span><?= $h($price['variant']) ?></span><small><?= $h($t('Cijena provjerena ', 'Price reviewed ').gmdate('d.m.Y.', strtotime($price['checked_at']))) ?></small></div><?php else: ?><p class="fb-small fb-price-unavailable"><?= $h($t('Aktualnu cijenu, sadržaj i eventualnu dostavu vidjet ćeš pri odabiru paketa na službenoj Forever stranici.', 'You will see the current price, contents and any delivery charge when selecting a pack on the official Forever website.')) ?></p><?php endif ?>
                    <?php if(!empty($details['qualification'])): ?><p class="fb-pack-fact"><span><?= $h($t('2 CC za kvalifikaciju', '2 CC towards qualification')) ?></span><small><?= $h($t('Poslovni status zahtijeva dovršetak službene prijave i uključivanje u poslovni program.', 'Business status requires completing official registration and joining the business programme.')) ?></small></p><?php endif ?>
                    <?php if(!empty($details['once'])): ?><p class="fb-small"><?= $h($t('Kupnja paketa po ovim uvjetima dostupna je jednom, prije razine Assistant Supervisor.', 'The pack is available on these terms once, before reaching Assistant Supervisor level.')) ?></p><?php endif ?>
                    <div class="fb-referral"><span><?= $h($t('Tvoja preporuka', 'Your referral')) ?></span><strong><?= $h($owner['name']) ?></strong><small>Forever ID: <?= $h($owner['fbo']) ?></small><?php /* Custom code: FC-2026-10-12 */ ?><button type="button" class="fb-copy-button" data-copy-value="<?= $h($owner['fbo']) ?>" data-copy-done="<?= $h($t('Kopirano', 'Copied')) ?>"><?= $h($t('Kopiraj broj sponzora', 'Copy sponsor ID')) ?></button><?php /* /Custom code: FC-2026-10-12 */ ?></div>
                    <form method="post" action="<?= $h($publicAction('starter')) ?>" class="fb-official-form"><?= $nonceField ?><input type="hidden" name="country" value="<?= $h($country) ?>"><input type="hidden" name="starter_version" value="<?= $h($starter['version']) ?>"><button class="fb-button"><?= $h($t('Nastavi na paket i registraciju', 'Continue to pack and registration')) ?> <span aria-hidden="true">↗</span></button></form>
                    <p class="fb-small fb-starter-note"><?= $h($t('Otvara se službena Forever stranica. Tamo odabireš paket, provjeravaš preporuku te dovršavaš prijavu i plaćanje prema lokalnim uvjetima.', 'The official Forever website opens. Choose your pack, check the referral and complete registration and payment according to local terms.')) ?></p>
                <?php else: ?><div class="fb-notice"><strong><?= $h($t('Provjerimo početak za tvoju zemlju.', 'Let’s check getting started in your country.')) ?></strong><p><?= $h($t('Za ovu zemlju trenutačno nemamo dostupnu provjerenu poveznicu za početni paket. Tvoja osoba za kontakt može ti pomoći s mogućnostima početka.', 'We do not currently have a reviewed starter pack link for this country. Your personal contact can help you explore getting started.')) ?></p><a href="#interest" class="fb-button fb-outline"><?= $h($t('Pitaj svoj kontakt', 'Ask your contact')) ?></a></div><?php endif ?>
            </div>
        </article>
        <?php endif ?>
        <?php if($country && $offers && empty($starter['destination'])): ?><div class="fb-offers"><?php foreach($offers as $offer): ?><article class="fb-offer"><h3><?= $h($offer['title']) ?></h3><p><?= $h($t('Aktualnu cijenu, dostupnost i uvjete vidiš na službenoj stranici.', 'See the official website for current pricing, availability and terms.')) ?></p><p class="fb-small"><?= $h($t('Tvoja preporuka: ', 'Your referral: ').$owner['name']) ?>, Forever ID: <?= $h($owner['fbo']) ?></p><form method="post" action="<?= $h($publicAction('continue')) ?>"><?= $nonceField ?><input type="hidden" name="country" value="<?= $h($country) ?>"><input type="hidden" name="offer_id" value="<?= (int)$offer['id'] ?>"><input type="hidden" name="offer_version" value="<?= (int)$offer['version'] ?>"><button class="fb-button"><?= $h($offer['destination_kind'] === 'enrollment' ? $t('Nastavi na službenu registraciju', 'Continue to official registration') : $t('Pogledaj paket u službenoj trgovini', 'View the pack in the official shop')) ?> ↗</button></form></article><?php endforeach ?></div><?php elseif($country && !$starter): ?><div class="fb-notice"><strong><?= $h($t('Provjerimo mogućnosti za tebe.', 'Let’s check your options.')) ?></strong><p><?= $h($t('Pakete i uvjete za odabranu zemlju provjeri sa svojom osobom za kontakt.', 'Ask your personal contact about packs and terms for your selected country.')) ?></p><a class="fb-button fb-outline" href="#interest"><?= $h($t('Pitaj svoj kontakt', 'Ask your contact')) ?></a></div><?php endif ?>
    </section>

    <section class="fb-start-steps" id="after-order" aria-labelledby="steps-title">
        <?php /* Custom code: FC-2026-10-12: from the official order to the FCC registration */ ?>
        <div class="fb-section-heading"><span class="fb-eyebrow"><?= $h($t('NAKON NARUDŽBE', 'AFTER YOUR ORDER')) ?></span><h2 id="steps-title"><?= $h($t('Tri koraka do tvog početka u timu.', 'Three steps to your start in the team.')) ?></h2></div>
        <ol class="fb-process">
            <li><span>01</span><h3><?= $h($t('Naruči Start paket', 'Order the Start pack')) ?></h3><p><?= $h(strtr($t('Na službenoj Forever stranici odaberi paket i prije plaćanja provjeri da je kao sponzor upisan/a {name}, Forever ID {fbo}.', 'On the official Forever website choose your pack and, before paying, check that {name}, Forever ID {fbo}, is entered as your sponsor.'), ['{name}'=>$owner['name'], '{fbo}'=>$owner['fbo']])) ?></p></li>
            <li><span>02</span><h3><?= $h($t('Primi svoj Forever broj', 'Receive your Forever ID')) ?></h3><p><?= $h($t('Nakon dovršene prijave Forever ti e-mailom šalje tvoj Forever ID od 12 znamenki. Sačuvaj ga, trebat će ti za FCC.', 'After you complete registration, Forever emails you your 12 digit Forever ID. Keep it, you will need it for FCC.')) ?></p></li>
            <li><span>03</span><h3><?= $h($t('Registriraj se u FCC', 'Register with FCC')) ?></h3><p><?= $h(strtr($t('S tim brojem otvori svoju FCC aplikaciju. Sustav automatski potvrđuje sponzora, a {name} te zatim dodaje u grupe i zajedno krećete s programom Moj put.', 'Open your FCC app with that ID. The system confirms your sponsor automatically, then {name} adds you to the groups and you start the My journey program together.'), ['{name}'=>$owner['name']])) ?></p><a class="fb-text-link" href="<?= $h($registerUrl) ?>"><?= $h($t('Registracija u FCC', 'Register with FCC')) ?> <span aria-hidden="true">↗</span></a></li>
        </ol>
        <?php /* /Custom code: FC-2026-10-12 */ ?>
    </section>

    <section class="fb-faq" id="faq" aria-labelledby="faq-title"><div class="fb-section-heading"><span class="fb-eyebrow"><?= $h($t('ODGOVORI PRIJE ODLUKE', 'ANSWERS BEFORE YOU DECIDE')) ?></span><h2 id="faq-title"><?= $h($t('Dobro je znati.', 'Good to know.')) ?></h2><p><?= $h($t('Ako ovdje ne pronađeš odgovor, pošalji pitanje svojoj osobi za kontakt.', 'If you cannot find your answer here, send a question to your personal contact.')) ?></p></div><div>
        <details class="fb-details"><summary><?= $h($t('Je li ovo zaposlenje ili samostalna suradnja?', 'Is this employment or independent business?')) ?></summary><p><?= $h($t('Riječ je o mogućnosti neovisne poslovne suradnje, uz prodaju proizvoda i pravila Forever poslovnog programa. Nema obećane plaće ili zajamčenog prihoda. Prije odluke pregledaj uvjete za svoju zemlju i službenu izjavu o prihodima.', 'This is an independent business opportunity involving product sales and the rules of the Forever business programme. There is no promised salary or guaranteed income. Review your local terms and the official income disclosure before deciding.')) ?></p><a href="https://foreverliving.com/income-disclosure" target="_blank" rel="noopener noreferrer"><?= $h($t('Službena izjava o prihodima', 'Official income disclosure')) ?> ↗</a></details>
        <details class="fb-details"><summary><?= $h($t('Što točno dobivam u paketu?', 'What exactly is included in the pack?')) ?></summary><p><?= $h($t('Sadržaj ovisi o odabranoj varijanti i zemlji. Na službenoj Forever stranici vidiš pojedine proizvode, količine i aktualnu dostupnost. Slika na ovoj prezentaciji prikazuje primjer paketa.', 'Contents depend on your chosen variant and country. The official Forever website shows individual products, quantities and current availability. The image on this presentation shows an example pack.')) ?></p><a href="#packages"><?= $h($t('Pogledaj paket za svoju zemlju', 'Explore the pack for your country')) ?> ↗</a></details>
        <details class="fb-details"><summary><?= $h($t('Znači li kupnja paketa da sam već poslovni suradnik?', 'Does buying the pack mean I am already a business owner?')) ?></summary><p><?= $h($t('Kupnju paketa i uključivanje u poslovni program dovršavaš prema pravilima svojeg tržišta. Provjeri odabrani status, prihvati službene uvjete i dovrši registraciju. Paket s 2 CC služi kvalifikaciji tamo gdje su ti uvjeti dostupni.', 'Complete the pack purchase and joining the business programme according to your market’s rules. Check your selected status, accept the official terms and complete registration. A 2 CC pack contributes towards qualification where these terms are available.')) ?></p></details>
        <details class="fb-details"><summary><?= $h($t('Koliko vremena trebam i kako nastaje prihod?', 'How much time do I need and how does income work?')) ?></summary><p><?= $h($t('Vrijeme ovisi o tvojem cilju i načinu rada. Aktivnosti uključuju upoznavanje proizvoda, razgovore, preporuke i podršku kupcima. Naknade i pogodnosti uređuje službeni poslovni program. Sama registracija ili regrutacija ne jamči zaradu.', 'The time required depends on your goal and how you work. Activities include learning about products, conversations, recommendations and customer support. The official business programme governs compensation and benefits. Registration or recruitment alone does not guarantee income.')) ?></p></details>
        <details class="fb-details"><summary><?= $h($t('Već imam Forever račun. Što trebam napraviti?', 'I already have a Forever account. What should I do?')) ?></summary><p><?= $h($t('Koristi postojeći račun i provjeri svoj trenutačni status. Nemoj otvarati drugi račun kako bi promijenio/la preporuku. Za pitanja o kvalifikaciji ili sponzoru javi se svojoj osobi za kontakt i lokalnom Forever uredu.', 'Use your existing account and check your current status. Do not create another account to change a referral. Ask your personal contact and local Forever office about qualification or your sponsor.')) ?></p></details>
        <details class="fb-details"><summary><?= $h($t('Gdje provjeravam dostavu, povrat i ostale uvjete?', 'Where do I check delivery, returns and other terms?')) ?></summary><p><?= $h($t('Prije plaćanja pregledaj službene uvjete narudžbe i poslovnog programa za svoju zemlju. Tamo se potvrđuju cijena, mogući dodatni troškovi, dostava, prava na povrat i obveze.', 'Review the official order and business programme terms for your country before paying. These confirm the price, possible additional costs, delivery, return rights and obligations.')) ?></p></details>
    </div></section>

    <section class="fb-contact-section" id="interest" aria-labelledby="interest-title"><div class="fb-contact-copy"><span class="fb-eyebrow"><?= $h($t('TVOJA PITANJA SU DOBRODOŠLA', 'YOUR QUESTIONS ARE WELCOME')) ?></span><h2 id="interest-title"><?= $h($t('Razjasni ono što ti je važno.', 'Clarify what matters to you.')) ?></h2><p><?= $h($t('Pitaj o paketu, prijavi ili načinu rada. Poruka ide izravno osobi koja ti je poslala ovu prezentaciju.', 'Ask about the pack, registration or how the work looks. Your message goes directly to the person who shared this presentation.')) ?></p><div class="fb-person"><span class="fb-avatar" aria-hidden="true"><?= $h(mb_strtoupper(mb_substr($owner['name'], 0, 1))) ?></span><div><small><?= $h($t('Tvoj kontakt', 'Your contact')) ?></small><strong><?= $h($owner['name']) ?></strong></div></div><?php if($wa): ?><a class="fb-button" href="<?= $h($wa) ?>" data-guest-whatsapp data-country="<?= $h($country) ?>" data-endpoint="<?= $h($publicAction('action')) ?>" data-nonce="<?= $h($nonce['value']) ?>" target="_blank" rel="noopener noreferrer"><?= $h($t('Pošalji WhatsApp poruku', 'Send a WhatsApp message')) ?> ↗</a><p class="fb-small"><?= $h($t('Otvara se pripremljena poruka. Možeš je promijeniti i samostalno poslati.', 'A prepared message opens. You can edit it and send it yourself.')) ?></p><?php endif ?></div>
        <details class="fb-contact-request" <?= $error && $action === 'interest' ? 'open' : '' ?>><summary><span><?= $h($t('Radije želiš da ti se javimo?', 'Would you prefer us to get in touch?')) ?></span><small><?= $h($t('Ostavi kontakt za osobni odgovor.', 'Leave your details for a personal reply.')) ?></small></summary><div class="fb-contact-form"><p><?= $h($t('Ovaj obrazac služi za razgovor. Registraciju u Forever dovršavaš na službenoj stranici.', 'This form is for a conversation. Complete Forever registration on the official website.')) ?></p>
            <?php if($error && $action === 'interest'): ?><p class="fb-alert" role="alert"><?= $h($lang === 'hr' ? $error : $t('Provjeri podatke i pokušaj ponovno.', 'Please review your details and try again.')) ?></p><?php endif ?>
            <form method="post" action="<?= $h($publicAction('interest')) ?>#interest" class="fb-form" data-business-interest><?= $nonceField ?><input type="hidden" name="request_key" value="<?= $h(preg_match('/^[a-f0-9]{32,64}$/D', $values['request_key'] ?? '') ? $values['request_key'] : bin2hex(random_bytes(24))) ?>"><input type="hidden" name="content_version" value="<?= (int)$s['current_version'] ?>"><input type="hidden" name="country" value="<?= $h($country) ?>"><label class="fb-honey" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
                <label><?= $h($t('Ime', 'Name')) ?><input name="name" autocomplete="given-name" required minlength="2" maxlength="120" value="<?= $h($values['name'] ?? '') ?>"></label>
                <label><?= $h($t('Kako želiš odgovor?', 'How would you like a reply?')) ?><select name="channel" data-channel><option value="whatsapp">WhatsApp</option><option value="email" <?= ($values['channel'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option></select></label>
                <label data-phone-field><?= $h($t('Telefon ili WhatsApp', 'Phone or WhatsApp')) ?><input name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+385…" maxlength="40" value="<?= $h($values['phone'] ?? '') ?>"></label>
                <label class="fb-check" data-email-option><input type="checkbox" data-email-extra <?= !empty($values['email']) ? 'checked' : '' ?>><span><?= $h($t('Želim i potvrdu upita emailom.', 'I would also like an email confirmation.')) ?></span></label><label data-email-field>Email<input name="email" type="email" autocomplete="email" maxlength="320" value="<?= $h($values['email'] ?? '') ?>"></label>
                <details class="fb-details fb-form-optional" <?= !empty($values['question']) || (!empty($values['topic']) && $values['topic'] !== 'general') ? 'open' : '' ?>><summary><?= $h($t('Dodaj pitanje', 'Add a question')) ?><small><?= $h($t('Neobvezno', 'Optional')) ?></small></summary><div class="fb-form"><label><?= $h($t('Tema razgovora', 'Conversation topic')) ?><select name="topic"><?php foreach(fcc_business_topics() as $key=>$label): ?><option value="<?= $h($key) ?>" <?= ($values['topic'] ?? 'general') === $key ? 'selected' : '' ?>><?= $h(fcc_business_topic($key, $lang)) ?></option><?php endforeach ?></select></label><label><?= $h($t('Što te zanima?', 'What would you like to know?')) ?><textarea name="question" rows="3" maxlength="1000"><?= $h($values['question'] ?? '') ?></textarea></label></div></details>
                <label class="fb-check"><input type="checkbox" name="contact_requested" value="1" required <?= !empty($values['contact_requested']) ? 'checked' : '' ?>><span><?= $h($t('Želim osobni odgovor o poslovnoj suradnji putem odabranog kanala.', 'I request a personal reply about the business opportunity through my selected channel.')) ?></span></label><button class="fb-button"><?= $h($t('Zatraži osobni odgovor', 'Request a personal reply')) ?> ↗</button><p class="fb-small fb-data-note"><?= $h($t('Upit i kontakt prima ', 'Your request and contact details go to ').$owner['name']) ?>. <?= $h($t('Podaci služe odgovoru na ovaj upit, bez prijave na newsletter.', 'Your details are used to reply to this enquiry, with no newsletter subscription.')) ?> <a href="<?= fcc_legal_url('privacy') ?>#marketing-plan"><?= $h($t('Kako koristimo podatke', 'How we use your data')) ?></a>.</p>
            </form>
        </div></details>
    </section>
    <section class="fb-decision" aria-labelledby="decision-title"><div><span class="fb-eyebrow"><?= $h($t('ODLUKA JE TVOJA', 'THE CHOICE IS YOURS')) ?></span><h2 id="decision-title"><?= $h($t('Spreman/na za sljedeći korak?', 'Ready for your next step?')) ?></h2><p><?= $h($t('Nastavi na paket i službenu prijavu ili najprije razjasni svoje pitanje.', 'Continue to the pack and official registration, or clarify your question first.')) ?></p></div><div class="fb-actions fb-main-actions"><a class="fb-button" href="#packages"><?= $h($t('Paket i registracija', 'Pack and registration')) ?> ↗</a><?php if($wa): ?><a class="fb-button fb-outline" href="<?= $h($wa) ?>" data-guest-whatsapp data-country="<?= $h($country) ?>" data-endpoint="<?= $h($publicAction('action')) ?>" data-nonce="<?= $h($nonce['value']) ?>" target="_blank" rel="noopener noreferrer"><?= $h($t('Pitaj na WhatsAppu', 'Ask on WhatsApp')) ?></a><?php else: ?><a class="fb-button fb-outline" href="#interest"><?= $h($t('Javi se svojem kontaktu', 'Contact your business owner')) ?></a><?php endif ?></div></section>
<?php endif ?>
</main>
<footer class="fb-footer"><p><?= $h($t('FCC je neovisan sustav našeg tima. Nije kompanija Forever Living Products ni njezina službena stranica.', 'FCC is an independent system used by our team. It is not Forever Living Products or its official website.')) ?></p><div><a href="<?= fcc_legal_url('privacy') ?>"><?= $h($t('Politika privatnosti', 'Privacy policy')) ?></a><a href="<?= fcc_legal_url('terms') ?>"><?= $h($t('Uvjeti korištenja', 'Terms of use')) ?></a><?php if($owner && ($s['available'] ?? false)): ?><a href="<?= $h($owner['profile']) ?>"><?= $h($owner['name']) ?></a><?php endif ?></div></footer>
<?php if(!$private && !$result && !empty($s['available'])): ?><nav class="fb-mobile-dock" aria-label="<?= $h($t('Tvoj sljedeći korak', 'Your next step')) ?>"><a class="fb-button" href="#packages"><?= $h($t('Paket i registracija', 'Pack and registration')) ?> ↗</a><?php if($wa): ?><a class="fb-button fb-outline" href="<?= $h($wa) ?>" data-guest-whatsapp data-country="<?= $h($country) ?>" data-endpoint="<?= $h($publicAction('action')) ?>" data-nonce="<?= $h($nonce['value']) ?>" target="_blank" rel="noopener noreferrer">WhatsApp ↗</a><?php else: ?><a class="fb-button fb-outline" href="#interest"><?= $h($t('Kontakt', 'Contact')) ?></a><?php endif ?></nav><?php endif ?>
</body>
</html>
