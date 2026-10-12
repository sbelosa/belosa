<?php
defined('ALTUMCODE') || die();

/** Navigation labels follow the interface locale, never the shop market. */
function fcc_mobile_nav_copy(string $key, ?string $locale = null): string {
    static $copy = [
        'en' => ['back'=>'Back','previous'=>'Previous page','home'=>'Home','workspace'=>'Today','more'=>'More','guides'=>'FCC guides','lessons'=>'All lessons','products'=>'Products','cards'=>'My cards','contacts'=>'Contacts','team'=>'My team','webinars'=>'My webinars','support'=>'Support','tools'=>'All tools','education'=>'Product Academy','plan'=>'My plan'],
        'hr' => ['back'=>'Povratak','previous'=>'Prethodna stranica','home'=>'Početna','workspace'=>'Danas','more'=>'Više','guides'=>'Upute za FCC','lessons'=>'Sve lekcije','products'=>'Proizvodi','cards'=>'Moje kartice','contacts'=>'Kontakti','team'=>'Moj tim','webinars'=>'Moji webinari','support'=>'Podrška','tools'=>'Svi alati','education'=>'Akademija proizvoda','plan'=>'Moj paket'],
        'sl' => ['back'=>'Nazaj','previous'=>'Prejšnja stran','home'=>'Domov','workspace'=>'Danes','more'=>'Več','guides'=>'Navodila za FCC','lessons'=>'Vse lekcije','products'=>'Izdelki','cards'=>'Moje kartice','contacts'=>'Stiki','team'=>'Moja ekipa','webinars'=>'Moji spletni seminarji','support'=>'Podpora','tools'=>'Vsa orodja','education'=>'Akademija izdelkov','plan'=>'Moj paket'],
        'de' => ['back'=>'Zurück','previous'=>'Vorherige Seite','home'=>'Startseite','workspace'=>'Heute','more'=>'Mehr','guides'=>'FCC Anleitungen','lessons'=>'Alle Lektionen','products'=>'Produkte','cards'=>'Meine Karten','contacts'=>'Kontakte','team'=>'Mein Team','webinars'=>'Meine Webinare','support'=>'Support','tools'=>'Alle Werkzeuge','education'=>'Produktakademie','plan'=>'Mein Paket'],
        'es' => ['back'=>'Volver','previous'=>'Página anterior','home'=>'Inicio','workspace'=>'Hoy','more'=>'Más','guides'=>'Guías de FCC','lessons'=>'Todas las lecciones','products'=>'Productos','cards'=>'Mis tarjetas','contacts'=>'Contactos','team'=>'Mi equipo','webinars'=>'Mis seminarios web','support'=>'Ayuda','tools'=>'Todas las herramientas','education'=>'Academia de productos','plan'=>'Mi plan'],
        'sr' => ['back'=>'Nazad','previous'=>'Prethodna stranica','home'=>'Početna','workspace'=>'Danas','more'=>'Više','guides'=>'Uputstva za FCC','lessons'=>'Sve lekcije','products'=>'Proizvodi','cards'=>'Moje kartice','contacts'=>'Kontakti','team'=>'Moj tim','webinars'=>'Moji vebinari','support'=>'Podrška','tools'=>'Svi alati','education'=>'Akademija proizvoda','plan'=>'Moj paket'],
        'sq' => ['back'=>'Kthehu','previous'=>'Faqja e mëparshme','home'=>'Kreu','workspace'=>'Sot','more'=>'Më shumë','guides'=>'Udhëzimet e FCC','lessons'=>'Të gjitha mësimet','products'=>'Produktet','cards'=>'Kartat e mia','contacts'=>'Kontaktet','team'=>'Ekipi im','webinars'=>'Webinaret e mia','support'=>'Mbështetja','tools'=>'Të gjitha mjetet','education'=>'Akademia e produkteve','plan'=>'Paketa ime'],
        'cnr' => ['back'=>'Nazad','previous'=>'Prethodna stranica','home'=>'Početna','workspace'=>'Danas','more'=>'Više','guides'=>'Uputstva za FCC','lessons'=>'Sve lekcije','products'=>'Proizvodi','cards'=>'Moje kartice','contacts'=>'Kontakti','team'=>'Moj tim','webinars'=>'Moji vebinari','support'=>'Podrška','tools'=>'Svi alati','education'=>'Akademija proizvoda','plan'=>'Moj paket'],
        'fr' => ['back'=>'Retour','previous'=>'Page précédente','home'=>'Accueil','workspace'=>"Aujourd’hui",'more'=>'Plus','guides'=>'Guides FCC','lessons'=>'Toutes les leçons','products'=>'Produits','cards'=>'Mes cartes','contacts'=>'Contacts','team'=>'Mon équipe','webinars'=>'Mes webinaires','support'=>'Assistance','tools'=>'Tous les outils','education'=>'Académie des produits','plan'=>'Mon forfait'],
        'sv' => ['back'=>'Tillbaka','previous'=>'Föregående sida','home'=>'Startsida','workspace'=>'Idag','more'=>'Mer','guides'=>'FCC guider','lessons'=>'Alla lektioner','products'=>'Produkter','cards'=>'Mina kort','contacts'=>'Kontakter','team'=>'Mitt team','webinars'=>'Mina webbinarier','support'=>'Support','tools'=>'Alla verktyg','education'=>'Produktakademin','plan'=>'Mitt paket'],
        'hu' => ['back'=>'Vissza','previous'=>'Előző oldal','home'=>'Kezdőlap','workspace'=>'Ma','more'=>'Továbbiak','guides'=>'FCC útmutatók','lessons'=>'Összes lecke','products'=>'Termékek','cards'=>'Kártyáim','contacts'=>'Kapcsolatok','team'=>'Csapatom','webinars'=>'Webináriumaim','support'=>'Támogatás','tools'=>'Összes eszköz','education'=>'Termékakadémia','plan'=>'Csomagom'],
        'pl' => ['back'=>'Wróć','previous'=>'Poprzednia strona','home'=>'Strona główna','workspace'=>'Dzisiaj','more'=>'Więcej','guides'=>'Instrukcje FCC','lessons'=>'Wszystkie lekcje','products'=>'Produkty','cards'=>'Moje karty','contacts'=>'Kontakty','team'=>'Mój zespół','webinars'=>'Moje webinary','support'=>'Pomoc','tools'=>'Wszystkie narzędzia','education'=>'Akademia produktów','plan'=>'Mój pakiet'],
        'ro' => ['back'=>'Înapoi','previous'=>'Pagina anterioară','home'=>'Pagina principală','workspace'=>'Astăzi','more'=>'Mai multe','guides'=>'Ghiduri FCC','lessons'=>'Toate lecțiile','products'=>'Produse','cards'=>'Cardurile mele','contacts'=>'Contacte','team'=>'Echipa mea','webinars'=>'Webinarele mele','support'=>'Asistență','tools'=>'Toate instrumentele','education'=>'Academia produselor','plan'=>'Pachetul meu'],
    ];
    $locale = function_exists('fcc_locale') ? fcc_locale($locale) : ($locale ?? 'en');
    if($key === 'login') {
        $login = ['en'=>'Sign in','hr'=>'Prijava','sl'=>'Prijava','de'=>'Anmelden','es'=>'Iniciar sesión','sr'=>'Prijava','sq'=>'Hyr','cnr'=>'Prijava','fr'=>'Connexion','sv'=>'Logga in','hu'=>'Bejelentkezés','pl'=>'Zaloguj się','ro'=>'Autentificare'];
        return $login[$locale] ?? $login['en'];
    }
    if($key === 'card') {
        $card = ['en'=>"Member’s card",'hr'=>'Kartica suradnika','sl'=>'Kartica sodelavca','de'=>'Karte des Partners','es'=>'Tarjeta del colaborador','sr'=>'Kartica saradnika','sq'=>'Karta e bashkëpunëtorit','cnr'=>'Kartica saradnika','fr'=>'Carte du partenaire','sv'=>'Partnerns kort','hu'=>'A munkatárs kártyája','pl'=>'Karta współpracownika','ro'=>'Cardul colaboratorului'];
        return $card[$locale] ?? $card['en'];
    }
    if($key === 'short_links') return function_exists('l') ? l('links.menu.link') : ($locale === 'hr' ? 'Skraćene poveznice' : 'Short links');
    return $copy[$locale][$key] ?? $copy['en'][$key] ?? $key;
}

/** Fixed HTML parents are the safe fallback for a direct link. Never trust a referrer. */
function fcc_mobile_nav_parent(string $controller, array $params, array $query, bool $member = true): ?array {
    $parent = static fn(string $route, string $label): array => ['route'=>$route, 'label'=>$label];
    $value = static fn(string $key): string => is_string($query[$key] ?? null) ? $query[$key] : '';
    $detail = static fn(string $key): bool => isset($query[$key]) && (is_string($query[$key]) || is_int($query[$key]));
    $first = is_string($params[0] ?? null) || is_int($params[0] ?? null) ? (string)$params[0] : '';
    $home = $member ? $parent('partner', 'workspace') : $parent('', 'home');

    if($controller === 'index' || $controller === '') return null;
    if(in_array($controller, ['login','register'], true)) return $parent('', 'home');
    if(in_array($controller, ['lost-password','reset-password','resend-activation','sent-activation','activate-user'], true)) return $parent('login', 'login');
    if($controller === 'partner') {
        $section = $first !== '' ? $first : 'today';
        if($section === 'today') {
            $view = $value('view');
            if($view === '' || $view === 'today') return null;
            if($view === 'coach') return $parent('partner?view=step', 'workspace');
            if($view === 'history' && $detail('conversation')) return $parent('partner?view=history', 'workspace');
            return $home;
        }
        if($section === 'contacts') return $detail('id') || $detail('new') ? $parent('partner/contacts', 'contacts') : $home;
        if($section === 'cards') return $detail('remove') ? $parent('partner/cards', 'cards') : $home;
        if($section === 'card') return $parent('partner/cards', 'cards'); /* FC-2026-10-12: simple card editor */
        if($section === 'share') {
            $type = $value('type');
            if($type === 'events' && $detail('session_id')) return $parent('partner/share?type=events', 'webinars');
            if(in_array($type, ['webinar','events'], true)) return $parent('partner/share', 'products');
            return $home;
        }
        if($section === 'help') return $value('topic') !== '' ? $parent('partner/help', 'guides') : $parent('partner/more', 'more');
        if($section === 'team') return $detail('member') ? $parent('partner/team', 'team') : $parent('partner/more', 'more');
        if($section === 'webinars') return isset($params[1]) ? $parent('partner/webinars', 'webinars') : $parent('partner/more', 'more');
        if($section === 'more') return $home;
        if(in_array($section, ['plan','education'], true)) return $home;
        if(in_array($section, ['preview','curriculum'], true)) return null;
        return $parent('partner/more', 'more');
    }
    if($controller === 'fcc-product-education') {
        if($value('lesson') !== '') return $parent('fcc-product-education?section=learn', 'lessons');
        if($value('product') !== '') return $parent('fcc-product-education?section=products', 'products');
        return $parent('partner/more', 'more');
    }
    if($controller === 'partner-webinar') {
        $type = $value('type') === 'events' ? 'events' : 'webinar';
        if(in_array($value('view'), ['edit','manage'], true) || $detail('create')) return $parent('partner-webinar?view=events', 'webinars');
        return $parent('partner/share?type='.$type, 'webinars');
    }
    if($controller === 'partner-marketing') return $parent('partner/share', 'products');
    if($controller === 'link') return $parent('partner/cards', 'cards');
    if($controller === 'biolink-block') return $parent('partner/cards', 'cards');
    if($controller === 'feedback-tickets') return $detail('ticket_id') ? $parent('feedback-tickets', 'support') : $parent('partner/more', 'more');
    if(in_array($controller, ['account','account-preferences','account-payments','account-logs','account-api','account-delete','account-redeem-code'], true)) return $parent('partner/settings', 'more');
    if($controller === 'account-plan') return $parent('partner/settings', 'more');
    if(in_array($controller, ['plan','pay','pay-thank-you'], true)) return $parent('account-plan', 'plan');
    if($controller === 'pay-billing') return ctype_digit($first) && (int)$first > 0 ? $parent('pay/'.$first, 'plan') : $parent('account-plan', 'plan');
    if($controller === 'blog') {
        if($first === 'feed') return null;
        if($first !== '') {
            if($member && $value('from') === 'share') return $parent('partner/share', 'products');
            $extra = [];
            // These are the same explicit referral and archive keys supported by the public library.
            $ref = $value('ref');
            if($ref !== '' && strlen($ref) <= 200 && preg_match('/^[A-Za-z0-9][A-Za-z0-9._~-]*$/D', $ref)) $extra['ref'] = $ref;
            if(in_array($value('view'), ['public','articles'], true)) $extra['view'] = $value('view');
            return $parent('blog'.($extra ? '?'.http_build_query($extra, '', '&', PHP_QUERY_RFC3986) : ''), 'products');
        }
        return $member ? $parent('partner/more', 'more') : $home;
    }
    if(in_array($controller, ['page','pages','contact','affiliate','api-documentation','recommended-sponsors','featured-apps','vip-funnel'], true)) return $home;

    $collections = [
        'qr-code-create'=>'qr-codes','qr-code-update'=>'qr-codes',
        'project-create'=>'projects','project-update'=>'projects',
        'domain-create'=>'domains','domain-update'=>'domains',
        'pixel-create'=>'pixels','pixel-update'=>'pixels',
        'notification-handler-create'=>'notification-handlers','notification-handler-update'=>'notification-handlers',
        'splash-page-create'=>'splash-pages','splash-page-update'=>'splash-pages',
        'payment-processor-create'=>'payment-processors','payment-processor-update'=>'payment-processors',
    ];
    if(isset($collections[$controller])) return $parent($collections[$controller], 'tools');
    if($controller === 'links-statistics' || $controller === 'link-statistics') return $parent('dashboard?view=statistics', 'tools');
    if($controller === 'links' && $value('type') === 'link') return $parent('partner/more#tools', 'more');
    if(in_array($controller, ['links','link-create','qr-codes','projects','domains','pixels','notification-handlers','splash-pages','payment-processors','dashboard','funnels-analytics','data','tools','directory','vip-funnel-studio','vip-funnel-demo-access','referrals','teams-system'], true)) return $parent('partner/more#tools', 'tools');
    if(in_array($controller, ['fcc-education','fcc-results','forever-business','fcc-ai','ai-plan','ai-app-review'], true)) return $parent('partner/more', 'more');
    return $home;
}

/** A public article may return to one confirmed FCC card. Cookies are not provenance. */
function fcc_mobile_nav_public_card(array $query, array $server): ?array {
    $candidate = null;
    if(array_key_exists('ref', $query)) {
        // An explicit invalid or disabled reference must never fall back to another owner.
        $candidate = is_string($query['ref']) ? $query['ref'] : null;
    } else {
        $referrer = $server['HTTP_REFERER'] ?? null;
        if(!is_string($referrer) || preg_match('/[\x00-\x20\x7f\\\\]/', $referrer)) return null;
        $site = parse_url(SITE_URL);
        $previous = parse_url($referrer);
        if(!$site || !$previous || !empty($previous['user']) || !empty($previous['pass'])
            || array_key_exists('query', $previous) || array_key_exists('fragment', $previous)
            || !in_array(strtolower($previous['scheme'] ?? ''), ['http','https'], true)
            || strtolower($site['scheme'] ?? '') !== strtolower($previous['scheme'] ?? '')
            || strtolower($site['host'] ?? '') !== strtolower($previous['host'] ?? '')
            || (int)($site['port'] ?? (($site['scheme'] ?? '') === 'https' ? 443 : 80)) !== (int)($previous['port'] ?? (($previous['scheme'] ?? '') === 'https' ? 443 : 80))) return null;
        $basePath = rtrim((string)($site['path'] ?? '/'), '/').'/';
        $path = (string)($previous['path'] ?? '');
        if(!str_starts_with($path, $basePath)) return null;
        $candidate = substr($path, strlen($basePath));
    }
    if(!is_string($candidate) || strlen($candidate) > 200
        || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._~-]*$/D', $candidate)) return null;
    if(function_exists('fcc_supported_locales') && in_array($candidate, fcc_supported_locales(), true)) return null;
    // Even a malformed legacy card must not point at an application endpoint.
    if(isset(\Altum\Router::$routes[''][$candidate]) || in_array($candidate, ['admin','api','l','login','register','logout','pay','pay-billing','pay-thank-you'], true)) return null;
    if(!function_exists('fcc_market_referral_owner') || !function_exists('db')) return null;
    try {
        $owner = fcc_market_referral_owner($candidate);
        if(!$owner) return null;
        // The ordinary FCC origin resolves cards with domain_id=0, not a custom domain.
        $card = db()->where('url', $candidate)->where('type', 'biolink')->where('is_enabled', 1)->where('domain_id', 0)
            ->getOne('links', ['url','user_id','domain_id']);
        if(!$card || ($card->url ?? null) !== $candidate || (int)($card->user_id ?? 0) !== (int)($owner->user_id ?? 0)) return null;
        $target = rtrim(SITE_URL, '/').'/'.$candidate;
        return ['route'=>$candidate,'url'=>$target,'path'=>(string)parse_url($target, PHP_URL_PATH),'label'=>'card'];
    } catch(\Throwable $exception) {
        return null;
    }
}

/** Account scope is opaque and changes for impersonation or delegated team access. */
function fcc_mobile_nav_config($user = null, string $mode = 'member'): array {
    $mode = in_array($mode, ['member','public','auth'], true) ? $mode : 'public';
    $controller = (string)(\Altum\Router::$controller_key ?? 'index');
    $params = (array)(\Altum\Router::$params ?? []);
    $query = is_array($_GET ?? null) ? $_GET : [];
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $parent = fcc_mobile_nav_parent($controller, $params, $query, $mode === 'member');
    $confirmedCard = null;
    if($mode === 'public' && $controller === 'blog' && is_string($params[0] ?? null) && !in_array($params[0], ['','category','feed'], true)) {
        $confirmedCard = fcc_mobile_nav_public_card($query, $_SERVER);
        if($confirmedCard) $parent = ['route'=>$confirmedCard['route'],'label'=>'card'];
    }
    $uid = is_object($user) ? (int)($user->user_id ?? 0) : (is_array($user) ? (int)($user['user_id'] ?? 0) : 0);
    if(!$uid && class_exists('Altum\\Authentication', false)) $uid = (int)(\Altum\Authentication::$user_id ?? 0);
    $adminId = function_exists('session_get') ? (int)session_get('admin_user_id') : (int)($_SESSION['admin_user_id'] ?? 0);
    $team = class_exists('Altum\\Teams', false) ? \Altum\Teams::$team : null;
    $teamMember = class_exists('Altum\\Teams', false) ? \Altum\Teams::$team_member : null;
    $linkId = is_string($params[0] ?? null) || is_int($params[0] ?? null) ? (string)$params[0] : '';
    if($mode === 'member' && $controller === 'link' && $uid > 0 && !$team && !$teamMember
        && ctype_digit($linkId) && (int)$linkId > 0 && function_exists('db')) {
        try {
            $linkType = db()->where('link_id', (int)$linkId)->where('user_id', $uid)->getValue('links', 'type');
            if($linkType === 'link') $parent = ['route'=>'links?type=link', 'label'=>'short_links'];
        } catch(\Throwable $exception) {
            /* Keep the ordinary fallback when an owned link type cannot be confirmed. */
        }
    }
    $scope = hash('sha256', implode(':', ['fcc-mobile-navigation-v1', $mode, $uid, $adminId, (int)($team->team_id ?? 0), (int)($team->user_id ?? 0), (int)($teamMember->team_member_id ?? 0), (int)($teamMember->user_id ?? 0)]));
    $roots = ['','partner','partner-webinar','partner-marketing','fcc-product-education','fcc-education','fcc-results','forever-business','fcc-ai','ai-plan','ai-app-review','blog','page','pages','contact','affiliate','api-documentation','recommended-sponsors','featured-apps','vip-funnel','links','link','link-create','biolink-block','qr-codes','qr-code-create','qr-code-update','projects','project-create','project-update','domains','domain-create','domain-update','pixels','pixel-create','pixel-update','notification-handlers','notification-handler-create','notification-handler-update','splash-pages','splash-page-create','splash-page-update','payment-processors','payment-processor-create','payment-processor-update','dashboard','links-statistics','funnels-analytics','data','tools','directory','vip-funnel-studio','vip-funnel-demo-access','feedback-tickets','account','account-preferences','account-plan','account-payments','account-logs','account-api','account-delete','account-redeem-code','referrals','teams-system','plan'];
    $excluded = ['login','register','lost-password','reset-password','resend-activation','sent-activation','activate-user','logout','pay','pay-billing','pay-thank-you','maintenance','not-found'];
    $isAdmin = (\Altum\Router::$path ?? '') === 'admin' || $controller === 'admin' || str_starts_with($controller, 'admin-') || ($controller === 'partner' && ($params[0] ?? '') === 'admin');
    $historyAllowed = $method === 'GET' && !$isAdmin && !in_array($controller, $excluded, true) && ($controller === 'index' || in_array($controller, $roots, true));
    if($controller === 'blog' && ($params[0] ?? '') === 'feed') $historyAllowed = false;
    if($controller === 'partner' && in_array($params[0] ?? '', ['preview','curriculum'], true)) $historyAllowed = false;
    $locale = function_exists('fcc_locale') ? fcc_locale() : 'en';
    $fallbackRoute = $parent['route'] ?? ($mode === 'member' ? 'partner' : '');
    return [
        'version'=>'2026.10.07.1', 'base'=>SITE_URL, 'scope'=>$scope, 'mode'=>$mode,
        'method'=>$method, 'controller'=>$controller, 'locale'=>$locale,
        'home'=>$parent === null, 'historyAllowed'=>$historyAllowed,
        'roots'=>$roots, 'languages'=>function_exists('fcc_supported_locales') ? fcc_supported_locales() : ['hr','en','sl','de','es','sr','sq','cnr','fr','sv','hu','pl','ro'],
        'copy'=>['back'=>fcc_mobile_nav_copy('back', $locale),'previous'=>fcc_mobile_nav_copy('previous', $locale)],
        'fallback'=>$confirmedCard['url'] ?? url($fallbackRoute), 'parentRoute'=>$fallbackRoute,
        'confirmedCardFallback'=>$confirmedCard !== null, 'trustedPaths'=>$confirmedCard ? [$confirmedCard['path']] : [],
        'parentLabel'=>fcc_mobile_nav_copy($parent['label'] ?? ($mode === 'member' ? 'workspace' : 'home'), $locale),
    ];
}

function fcc_mobile_nav_assets(): void {
    static $done = false;
    if($done) return;
    $done = true;
    foreach(['css/fcc-mobile-navigation.css'=>'head', 'js/fcc-mobile-navigation.js'=>'javascript'] as $relative => $slot) {
        $version = is_file(ASSETS_PATH.$relative) ? (string)filemtime(ASSETS_PATH.$relative) : '20261007v1';
        $href = htmlspecialchars(ASSETS_FULL_URL.$relative.'?v='.$version, ENT_QUOTES, 'UTF-8');
        \Altum\Event::add_content($slot === 'head' ? '<link rel="stylesheet" href="'.$href.'">' : '<script defer src="'.$href.'"></script>', $slot);
    }
}

function fcc_mobile_nav_render($user = null, string $mode = 'member'): void {
    $config = fcc_mobile_nav_config($user, $mode);
    $h = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
    echo '<nav class="fcc-mobile-navigation" data-fcc-mobile-nav data-fcc-nav-config="'.$h($json).'" aria-label="'.$h($config['copy']['back']).'"'.($config['home'] ? ' hidden' : '').'>';
    echo '<a data-fcc-mobile-back href="'.$h($config['fallback']).'"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m14 6-6 6 6 6"/></svg><span>'.$h($config['copy']['back']).'</span><span data-fcc-back-destination>'.$h($config['parentLabel']).'</span></a></nav>';
}
