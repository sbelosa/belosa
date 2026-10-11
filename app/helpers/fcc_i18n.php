<?php
defined('ALTUMCODE') || die();

/** Supported interface locales, independent of market and purchase eligibility. */
function fcc_language_labels(): array {
    return ['hr'=>'Hrvatski','en'=>'English','sl'=>'Slovenščina','de'=>'Deutsch','es'=>'Español','sr'=>'Srpski (latinica)','sq'=>'Shqip','cnr'=>'Crnogorski (latinica)','fr'=>'Français','sv'=>'Svenska','hu'=>'Magyar','pl'=>'Polski','ro'=>'Română'];
}
function fcc_supported_locales(): array { return array_keys(fcc_language_labels()); }

/** Display language only. Never use this value as a shop market or currency. */
function fcc_locale(?string $language = null): string {
    $language = mb_strtolower(trim($language ?? (string) (\Altum\Language::$code ?? 'en')), 'UTF-8');
    return match($language) {
        'hr', 'hr-hr', 'hrvatski', 'croatian' => 'hr',
        'sl', 'sl-si', 'slovenski', 'slovenščina', 'slovenian' => 'sl',
        'de', 'de-de', 'de-at', 'de-ch', 'deutsch', 'german' => 'de',
        'sr', 'sr-latn', 'sr-rs', 'sr-latn-rs', 'srpski', 'serbian', 'serbian latin' => 'sr',
        'es', 'es-es', 'es-mx', 'español', 'spanish' => 'es',
        'sq', 'sq-al', 'sq-xk', 'shqip', 'albanian' => 'sq',
        'cnr', 'cnr-latn', 'cnr-me', 'cnr-latn-me', 'crnogorski', 'montenegrin' => 'cnr',
        'fr', 'fr-fr', 'fr-ca', 'fr-be', 'fr-ch', 'français', 'french' => 'fr',
        'sv', 'sv-se', 'svenska', 'swedish' => 'sv',
        'hu', 'hu-hu', 'magyar', 'hungarian' => 'hu',
        'pl', 'pl-pl', 'polski', 'polish' => 'pl',
        'ro', 'ro-ro', 'română', 'romana', 'romanian' => 'ro',
        default => 'en',
    };
}


/** Language tags describe content, never the shop market or visitor location. */
function fcc_language_tag(?string $code = null): string {
    $code = $code ?? (string)(\Altum\Language::$code ?? 'en');
    return match($code) {'sr'=>'sr-Latn', 'cnr'=>'cnr-Latn', default=>$code};
}
function fcc_opengraph_locale(?string $code = null): string {
    return ['hr'=>'hr_HR','sl'=>'sl_SI','en'=>'en_GB','de'=>'de_DE','es'=>'es_ES','sr'=>'sr_RS','sq'=>'sq_AL','cnr'=>'cnr_ME','fr'=>'fr_FR','sv'=>'sv_SE','hu'=>'hu_HU','pl'=>'pl_PL','ro'=>'ro_RO'][fcc_locale($code)] ?? 'en_GB';
}

/** Search equivalences only, not user-facing copy or new clinical product claims. */
function fcc_german_query_equivalents(string $text): string {
    $replacements=[
        'ich bin nicht allergisch'=>'I am not allergic', 'keine allergie'=>'no allergy',
        'ich bin nicht schwanger'=>'I am not pregnant', 'nicht schwanger'=>'not pregnant',
        'ich bin nicht vegan'=>'I am not vegan', 'nicht vegan'=>'not vegan',
        'ich kann nicht atmen'=>'cannot breathe', 'atemnot'=>'shortness of breath',
        'brustschmerzen'=>'chest pain', 'schmerzen in der brust'=>'chest pain',
        'geschwollene lippen'=>'otekle usne', 'geschwollene zunge'=>'otekli jezik',
        'bewusstlos'=>'unconscious', 'blut im stuhl'=>'blood in stool',
        'blutdruck'=>'pressure', 'bluthochdruck'=>'high blood pressure',
        'schwanger'=>'pregnant', 'schwangerschaft'=>'pregnancy', 'ich stille'=>'breastfeeding',
        'medikamente'=>'medication', 'medikament'=>'medication', 'krebs'=>'cancer',
        'nierenprobleme'=>'kidney problems', 'nierenerkrankung'=>'kidney disease',
        'fettleber'=>'fatty liver', 'lebererkrankung'=>'liver disease',
        'für mein kind'=>'for my child', 'fur mein kind'=>'for my child',
        'für ein kind'=>'for child', 'fur ein kind'=>'for child',
        'allergisch'=>'allergic', 'allergie'=>'allergy',
        'unverträglichkeit'=>'intolerance', 'unvertraglichkeit'=>'intolerance',
        'vermeide'=>'avoid', 'ohne'=>'without', 'fisch'=>'fish', 'fische'=>'fish',
        'milch'=>'milk', 'eier'=>'eggs', 'ei'=>'egg', 'nüsse'=>'nuts', 'nusse'=>'nuts',
        'mandeln'=>'almonds', 'haselnüsse'=>'hazelnuts', 'haselnusse'=>'hazelnuts',
        'laktose'=>'lactose', 'honig'=>'med', 'bienenprodukte'=>'bee products',
        'glutenfrei'=>'gluten free', 'zöliakie'=>'celiac', 'zoliakie'=>'celiac',
        'vegetarisch'=>'vegetarian', 'koffein'=>'caffeine', 'grüner tee'=>'green tea', 'gruner tee'=>'green tea',
    ];
    // Search vocabulary only. Preserve negations before matching shorter phrases.
    $replacements += [
        "je ne suis pas enceinte"=>'not pregnant', 'no estoy embarazada'=>'not pregnant', 'nuk jam shtatzënë'=>'not pregnant',
        "je ne suis pas allergique"=>'not allergic', 'no soy alérgico'=>'not allergic', 'no soy alérgica'=>'not allergic', 'nuk jam alergjik'=>'not allergic',
        "je ne peux pas respirer"=>'cannot breathe', 'no puedo respirar'=>'cannot breathe', 'nuk mund të marr frymë'=>'cannot breathe',
        'douleur thoracique'=>'chest pain', 'dolor en el pecho'=>'chest pain', 'dhimbje gjoksi'=>'chest pain',
        'programme automobile'=>'auto program', 'programme voiture'=>'auto program', 'programme auto'=>'auto program',
        'programa de automóvil'=>'auto program', 'programa del automóvil'=>'auto program', 'programa de coche'=>'auto program', 'programa de autos'=>'auto program',
        'programi i makinës'=>'auto program', 'programin e makinës'=>'auto program', 'programi i veturës'=>'auto program',
        "programmes d’encouragement"=>'poticajni program', "programmes d'incitation"=>'poticajni program', 'programas de incentivos'=>'poticajni program', 'programet nxitëse'=>'poticajni program',
        'politique commerciale'=>'poslovna politika', 'política comercial'=>'poslovna politika', 'politika e biznesit'=>'poslovna politika',
        'écris un message'=>'write message', 'rédige un message'=>'write message', 'escribe un mensaje'=>'write message', 'redacta un mensaje'=>'write message', 'shkruaj një mesazh'=>'write message',
        'écris une invitation'=>'write invitation', 'rédige une invitation'=>'write invitation', 'escribe una invitación'=>'write invitation', 'shkruaj një ftesë'=>'write invitation',
        'explique'=>'explain', 'explícame'=>'explain', 'shpjego'=>'explain',
        'collaborateur'=>'suradnik', 'collaborateurs'=>'suradnik', 'colaborador'=>'suradnik', 'colaboradores'=>'suradnik', 'bashkëpunëtor'=>'suradnik',
        'parrain'=>'sponsor', 'patrocinador'=>'sponsor', 'sponsori'=>'sponsor',
        'recrutement'=>'regrutacija', 'reclutamiento'=>'regrutacija', 'rekrutimi'=>'regrutacija',
        'revenu'=>'zarada', 'ingresos'=>'zarada', 'të ardhurat'=>'zarada',
        'rémunération'=>'commission', 'comisiones'=>'commission', 'komisionet'=>'commission',
        'produit'=>'proizvod', 'produits'=>'proizvod', 'producto'=>'proizvod', 'productos'=>'proizvod', 'produkt'=>'proizvod', 'produkte'=>'proizvod', 'produkteve'=>'proizvod',
        'ingrédients'=>'sastojak ingredient', 'ingredientes'=>'sastojak ingredient', 'përbërës'=>'sastojak ingredient', 'përbërësit'=>'sastojak ingredient',
        'contient'=>'sadrzi contains', 'contiene'=>'sadrzi contains', 'përmban'=>'sadrzi contains',
        'hydratation'=>'hidratacija hydration', 'hidratación'=>'hidratacija hydration', 'hidratim'=>'hidratacija hydration',
        'peau sèche'=>'suha koza dry skin', 'piel seca'=>'suha koza dry skin', 'lëkurë e thatë'=>'suha koza dry skin',
        'protéines'=>'protein', 'proteínas'=>'protein', 'proteina'=>'protein', 'fibres'=>'vlakna fiber', 'fibra'=>'vlakna fiber', 'fibra ushqimore'=>'vlakna fiber',
        'insuffisance rénale'=>'kidney disease', 'maladie rénale'=>'kidney disease', 'maladie des reins'=>'kidney disease', 'enfermedad renal'=>'kidney disease', 'sëmundje të veshkave'=>'kidney disease',
        'foie gras'=>'fatty liver', 'stéatose hépatique'=>'fatty liver', 'hígado graso'=>'fatty liver', 'mëlçi e yndyrshme'=>'fatty liver', 'mëlçi të yndyrshme'=>'fatty liver',
        'hypertension'=>'high blood pressure', 'presión arterial alta'=>'high blood pressure', 'tensión alta'=>'high blood pressure', 'tension të lartë'=>'high blood pressure', 'pritisak'=>'tlak pressure',
        'enceinte'=>'pregnant', 'embarazada'=>'pregnant', 'embarazo'=>'pregnancy', 'shtatzënë'=>'pregnant', 'shtatzëni'=>'pregnancy',
        'allaitement'=>'breastfeeding', 'lactancia'=>'breastfeeding', 'ushqyerje me gji'=>'breastfeeding',
        'médicaments'=>'medication', 'medicamentos'=>'medication', 'barna'=>'medication', 'ilaçe'=>'medication',
        'allergique'=>'allergic', 'allergie'=>'allergy', 'alérgico'=>'allergic', 'alérgica'=>'allergic', 'alergia'=>'allergy', 'alergji'=>'allergy',
        'sans gluten'=>'gluten free', 'sin gluten'=>'gluten free', 'pa gluten'=>'gluten free',
        'sans lactose'=>'lactose free', 'sin lactosa'=>'lactose free', 'pa laktozë'=>'lactose free',
        'poisson'=>'fish', 'pescado'=>'fish', 'peshk'=>'fish', 'lait'=>'milk', 'leche'=>'milk', 'qumësht'=>'milk',
        'miel'=>'med honey', 'mjaltë'=>'med honey', 'arachides'=>'nuts', 'cacahuetes'=>'nuts', 'kikirikë'=>'nuts',
    ];
    $replacements += [
        'tejre'=>'milk',
        'tejet'=>'milk',
        'mleka'=>'milk',
        'allergi'=>'allergy',
        'allergia'=>'allergy',
        'alergie'=>'allergy',
        'undviker'=>'avoid',
        'kerülöm'=>'avoid',
        'unikam'=>'avoid',
        'evit'=>'avoid',
        'utan'=>'without',
        'nélkül'=>'without',
        'bez'=>'without',
        'fără'=>'without',
        'ägg'=>'eggs',
        'tojás'=>'egg',
        'jajka'=>'eggs',
        'ouă'=>'eggs',
        'nötter'=>'nuts',
        'diófélék'=>'nuts',
        'orzechy'=>'nuts',
        'nuci'=>'nuts',
        'koffein'=>'caffeine',
        'koffeinmentes'=>'without caffeine',
        'kofeina'=>'caffeine',
        'cofeină'=>'caffeine',
        'grönt te'=>'green tea',
        'zöld tea'=>'green tea',
        'zielona herbata'=>'green tea',
        'ceai verde'=>'green tea',
        'för mitt barn'=>'for my child',
        'a gyermekemnek'=>'for my child',
        'dla mojego dziecka'=>'for my child',
        'pentru copilul meu'=>'for my child',
        'affärsverksamhet'=>'business',
        'vállalkozás'=>'business',
        'działalność'=>'business',
        'afaceri'=>'business',
        'samarbete'=>'collaboration',
        'együttműködés'=>'collaboration',
        'współpraca'=>'collaboration',
        'colaborare'=>'collaboration',
        'jag är inte gravid'=>'not pregnant',
        'nem vagyok terhes'=>'not pregnant',
        'nie jestem w ciąży'=>'not pregnant',
        'nu sunt însărcinată'=>'not pregnant',
        'jag är inte allergisk'=>'not allergic',
        'nem vagyok allergiás'=>'not allergic',
        'nie mam alergii'=>'no allergy',
        'nu sunt alergic'=>'not allergic',
        'nu sunt alergică'=>'not allergic',
        'jag kan inte andas'=>'cannot breathe',
        'nem kapok levegőt'=>'cannot breathe',
        'nie mogę oddychać'=>'cannot breathe',
        'nu pot respira'=>'cannot breathe',
        'bröstsmärta'=>'chest pain',
        'mellkasi fájdalom'=>'chest pain',
        'ból w klatce piersiowej'=>'chest pain',
        'durere în piept'=>'chest pain',
        'bilprogram'=>'auto program',
        'autóprogram'=>'auto program',
        'program samochodowy'=>'auto program',
        'programul auto'=>'auto program',
        'incitamentsprogram'=>'poticajni program',
        'ösztönző program'=>'poticajni program',
        'program motywacyjny'=>'poticajni program',
        'programe de stimulare'=>'poticajni program',
        'affärspolicy'=>'poslovna politika',
        'üzletpolitika'=>'poslovna politika',
        'polityka biznesowa'=>'poslovna politika',
        'politica de afaceri'=>'poslovna politika',
        'skriv ett meddelande'=>'write message',
        'írj egy üzenetet'=>'write message',
        'napisz wiadomość'=>'write message',
        'scrie un mesaj'=>'write message',
        'skriv en inbjudan'=>'write invitation',
        'írj meghívót'=>'write invitation',
        'napisz zaproszenie'=>'write invitation',
        'scrie o invitație'=>'write invitation',
        'förklara'=>'explain',
        'magyarázd'=>'explain',
        'wyjaśnij'=>'explain',
        'explică'=>'explain',
        'samarbetspartner'=>'suradnik',
        'munkatárs'=>'suradnik',
        'współpracownik'=>'suradnik',
        'colaborator'=>'suradnik',
        'szponzor'=>'sponsor',
        'sponsorul'=>'sponsor',
        'rekrytering'=>'regrutacija',
        'toborzás'=>'regrutacija',
        'rekrutacja'=>'regrutacija',
        'recrutare'=>'regrutacija',
        'inkomst'=>'zarada',
        'jövedelem'=>'zarada',
        'dochód'=>'zarada',
        'venit'=>'zarada',
        'provision'=>'commission',
        'jutalék'=>'commission',
        'prowizja'=>'commission',
        'comision'=>'commission',
        'produkter'=>'proizvod',
        'termék'=>'proizvod',
        'termékek'=>'proizvod',
        'produkty'=>'proizvod',
        'produs'=>'proizvod',
        'produse'=>'proizvod',
        'ingredienser'=>'sastojak ingredient',
        'összetevők'=>'sastojak ingredient',
        'składniki'=>'sastojak ingredient',
        'ingrediente'=>'sastojak ingredient',
        'innehåller'=>'sadrzi contains',
        'tartalmaz'=>'sadrzi contains',
        'zawiera'=>'sadrzi contains',
        'conține'=>'sadrzi contains',
        'torr hud'=>'suha koza dry skin',
        'száraz bőr'=>'suha koza dry skin',
        'sucha skóra'=>'suha koza dry skin',
        'piele uscată'=>'suha koza dry skin',
        'njursjukdom'=>'kidney disease',
        'vesebetegség'=>'kidney disease',
        'choroba nerek'=>'kidney disease',
        'boală renală'=>'kidney disease',
        'fettlever'=>'fatty liver',
        'zsírmáj'=>'fatty liver',
        'stłuszczenie wątroby'=>'fatty liver',
        'ficat gras'=>'fatty liver',
        'högt blodtryck'=>'high blood pressure',
        'magas vérnyomás'=>'high blood pressure',
        'wysokie ciśnienie'=>'high blood pressure',
        'tensiune arterială mare'=>'high blood pressure',
        'gravid'=>'pregnant',
        'terhes'=>'pregnant',
        'w ciąży'=>'pregnant',
        'însărcinată'=>'pregnant',
        'amning'=>'breastfeeding',
        'szoptatás'=>'breastfeeding',
        'karmienie piersią'=>'breastfeeding',
        'alăptare'=>'breastfeeding',
        'läkemedel'=>'medication',
        'gyógyszer'=>'medication',
        'leki'=>'medication',
        'medicamente'=>'medication',
        'allergisk'=>'allergic',
        'allergiás'=>'allergic',
        'alergiczny'=>'allergic',
        'alergic'=>'allergic',
        'glutenfri'=>'gluten free',
        'gluténmentes'=>'gluten free',
        'bez glutenu'=>'gluten free',
        'fără gluten'=>'gluten free',
        'laktosfri'=>'lactose free',
        'laktózmentes'=>'lactose free',
        'bez laktozy'=>'lactose free',
        'fără lactoză'=>'lactose free',
        'fisk'=>'fish',
        'hal'=>'fish',
        'ryby'=>'fish',
        'pește'=>'fish',
        'mjölk'=>'milk',
        'tej'=>'milk',
        'mleko'=>'milk',
        'lapte'=>'milk',
        'läkare'=>'doctor',
        'läkaren'=>'doctor',
        'orvos'=>'doctor',
        'orvosoddal'=>'doctor',
        'lekarz'=>'doctor',
        'lekarzem'=>'doctor',
        'medic'=>'doctor',
        'medicul'=>'doctor',
        'arzt'=>'doctor',
        'ärztin'=>'doctor',
        'médecin'=>'doctor',
        'médico'=>'doctor',
        'mjek'=>'doctor',
        'mjekun'=>'doctor',
        'innan du tar'=>'before taking',
        'innan användning'=>'before use',
        'szedés előtt'=>'before taking',
        'használat előtt'=>'before use',
        'przed przyjmowaniem'=>'before taking',
        'przed zastosowaniem'=>'before use',
        'înainte de administrare'=>'before taking',
        'înainte de utilizare'=>'before use',
        'vor der einnahme'=>'before taking',
        'vor der anwendung'=>'before use',
        'avant de prendre'=>'before taking',
        'avant utilisation'=>'before use',
        'antes de tomar'=>'before taking',
        'antes de usar'=>'before use',
        'para marrjes'=>'before taking',
        'para përdorimit'=>'before use',
        'om du vill'=>'if you want',
        'ha szeretnéd'=>'if you want',
        'jeśli chcesz'=>'if you want',
        'dacă dorești'=>'if you want',
        'rådgör'=>'consult',
        'beszélj'=>'discuss',
        'skonsultuj'=>'consult',
        'consultă'=>'consult',
        'rekommenderade produkter'=>'recommended products',
        'ajánlott termékek'=>'recommended products',
        'zalecane produkty'=>'recommended products',
        'produse recomandate'=>'recommended products',
        'sänker blodtrycket'=>'treats hypertension',
        'csökkenti a vérnyomást'=>'treats hypertension',
        'obniża ciśnienie'=>'treats hypertension',
        'scade tensiunea arterială'=>'treats hypertension',
        'botar njursjukdom'=>'treats kidney',
        'gyógyítja a vesebetegséget'=>'treats kidney',
        'leczy chorobę nerek'=>'treats kidney',
        'vindecă boala renală'=>'treats kidney',
        'köp nu'=>'buy now',
        'rendeld meg most'=>'order now',
        'kup teraz'=>'buy now',
        'cumpără acum'=>'buy now',
    ];
    uksort($replacements,static fn($a,$b)=>mb_strlen($b)<=>mb_strlen($a));
    $pattern='~(?<![\p{L}\p{N}])(?:'.implode('|',array_map(static fn($s)=>preg_quote($s,'~'),array_keys($replacements))).')(?![\p{L}\p{N}])~iu';
    return preg_replace_callback($pattern,static fn($m)=>$replacements[mb_strtolower($m[0],'UTF-8')],$text);
}

/** Exact source lookup. Call only for application copy, never member-authored text. */
function fcc_t(string $source, array $replace = [], ?string $locale = null): string {
    static $catalogs = [];
    $locale = fcc_locale($locale);
    if(!isset($catalogs[$locale])) {
        $path = APP_PATH . 'config/locales/' . $locale . '.php';
        $catalogs[$locale] = is_file($path) ? require $path : [];
        $curated = APP_PATH . 'config/locales/' . $locale . '-curated.php';
        if(is_file($curated)) $catalogs[$locale] = (require $curated) + $catalogs[$locale];
        $review = APP_PATH . 'config/locales/journey-review.json';
        if(is_file($review)) $catalogs[$locale] = (json_decode(file_get_contents($review),true)[$locale]??[]) + $catalogs[$locale];
    }
    $translated = $catalogs[$locale][$source] ?? $source;
    if(!isset($catalogs[$locale][$source]) && preg_match('/^(\s*)(.*?)(\s*)$/su', $source, $parts) && isset($catalogs[$locale][$parts[2]])) {
        $translated = $parts[1] . $catalogs[$locale][$parts[2]] . $parts[3];
    }
    return $replace ? strtr($translated, $replace) : $translated;
}

/** Localize an immutable application-owned display copy; keys and IDs stay intact. */
function fcc_localize_copy(array $content, ?string $locale = null): array {
    foreach($content as $key => $value) {
        if(is_string($key) && (in_array($key, ['id','key','core','core_key','catalog_version','policy_version','version','kind','mode','tool','topic','topic_id','topic_key','scope','status','source','source_type','language','locale','url','href','src','image','slug','route','channel','format_key','delivery_mode','context_requirement','allowed_evidence','allowed_output_statuses','depends_on','evidence_policy','referral_url','path','sku'], true) || preg_match('/(?:_url|_id|_version|_key)$/D', $key))) continue;
        if(is_array($value)) $content[$key] = fcc_localize_copy($value, $locale);
        elseif(is_string($value)) $content[$key] = preg_match('/<[a-z][^>]*>/i', $value) ? fcc_localize_html($value, $locale) : fcc_t($value, [], $locale);
    }
    return $content;
}

function fcc_language_name(string $locale): ?string {
    $name = array_search($locale, \Altum\Language::$active_languages, true);
    return $name === false ? null : $name;
}

/** An explicit URL is a page preview, never an account preference write. */
function fcc_language_preference(?string $account, ?string $cookie, string $explicit, array $active, string $default): string {
    $byCode = array_search($explicit, $active, true);
    if($explicit !== '' && $byCode !== false) return $byCode;
    if($account !== null && isset($active[$account])) return $account;
    if($account === null && $cookie !== null && isset($active[$cookie])) return $cookie;
    return $default;
}

/** Keep referral and view parameters, reject every external or ambiguous target. */
function fcc_language_return_path(string $path): string {
    if($path === '') return '';
    if(strlen($path) > 4000 || preg_match('~[\\\\\x00-\x20\x7f]~', $path)) return 'partner';
    if(str_starts_with($path, '/') || preg_match('~^[a-z][a-z0-9+.-]*:~i', $path)) return 'partner';
    $decoded = rawurldecode(explode('?', $path, 2)[0]);
    if(preg_match('~[\\\\\x00-\x20\x7f]~', $decoded) || str_starts_with($decoded, '/') || str_contains($decoded, '..')) return 'partner';
    $segments = explode('/', explode('?', $path, 2)[0]);
    if(in_array($segments[0], array_values(\Altum\Language::$active_languages), true)) $path = substr($path, strlen($segments[0]) + 1);
    if(preg_match('~^(?:language|logout|api|cron)(?:[/?]|$)~', $path)) return 'partner';
    return $path;
}

function fcc_language_selector(string $placement = 'header'): void {
    static $assets = false;
    if(count(\Altum\Language::$active_languages) < 2) return;
    $h = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    if(!$assets) {
        $root = (parse_url(SITE_URL, PHP_URL_PATH) ?: '/') . ASSETS_URL_PATH;
        echo '<link rel="stylesheet" href="' . $h($root . 'css/fcc-i18n.css?v=' . filemtime(ASSETS_PATH.'css/fcc-i18n.css')) . '">';
        fcc_i18n_assets(); $assets = true;
    }
    $return = (string) \Altum\Router::$original_request;
    $query = (string) \Altum\Router::$original_request_query;
    if($query !== '') $return .= '?' . $query;
    $locale=fcc_locale();
    $labels = fcc_language_labels();
    $copy = match($locale) {
        'hr'=>['Jezik aplikacije','Odaberi jezik','Tvoj se odabir sprema automatski.','Odabrano'],
        'sl'=>['Jezik aplikacije','Izberi jezik','Tvoja izbira se shrani samodejno.','Izbrano'],
        'de'=>['Sprache der App','Sprache auswählen','Deine Auswahl wird automatisch gespeichert.','Ausgewählt'],
        'sr'=>['Jezik aplikacije','Izaberi jezik','Tvoj izbor se automatski čuva.','Izabrano'],
        'sq'=>['Gjuha e aplikacionit','Zgjidh gjuhën','Zgjedhja jote ruhet automatikisht.','E zgjedhur'],
        'cnr'=>['Jezik aplikacije','Izaberi jezik','Tvoj izbor se automatski čuva.','Izabrano'],
        'fr'=>['Langue de l’application','Choisis ta langue','Ton choix est enregistré automatiquement.','Sélectionné'],
        'es'=>['Idioma de la aplicación','Elige tu idioma','Tu elección se guarda automáticamente.','Seleccionado'],
        'sv'=>['Appens språk','Välj språk','Ditt val sparas automatiskt.','Valt'],
        'hu'=>['Az alkalmazás nyelve','Válassz nyelvet','A választásodat automatikusan mentjük.','Kiválasztva'],
        'pl'=>['Język aplikacji','Wybierz język','Twój wybór jest zapisywany automatycznie.','Wybrano'],
        'ro'=>['Limba aplicației','Alege limba','Alegerea ta este salvată automat.','Selectat'],
        default=>['App language','Choose your language','Your choice is saved automatically.','Selected'],
    };
    $globe='<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c5 5 5 13 0 18-5-5-5-13 0-18Z"/></svg>';
    $chevron='<svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m4 6 4 4 4-4"/></svg>';
    echo '<details class="fcc-language-picker fcc-language-picker-'.$h($placement).'"><summary aria-label="'.$h($copy[0]).'">'.$globe.'<span>'.strtoupper($locale).'</span>'.$chevron.'</summary>';
    echo '<div class="fcc-language-panel"><p class="fcc-language-title">'.$h($copy[1]).'</p><form method="post" action="'.$h((parse_url(SITE_URL, PHP_URL_PATH) ?: '/').'language').'"><input type="hidden" name="token" value="'.$h(\Altum\Csrf::get()).'"><input type="hidden" name="return_to" value="'.$h($return).'">';
    // Keep native activation intact: the legacy click spinner rewrites button children,
    // which cancels form submission in Safari. Opt out of that handler.
    foreach(\Altum\Language::$active_languages as $name=>$code) {
        $selected=$code===$locale;
        echo '<button class="fcc-language-option" type="submit" data-is-ajax name="language" value="'.$h($code).'" aria-label="'.$h(($labels[$code]??$name).($selected?', '.$copy[3]:'')).'" aria-current="'.($selected?'true':'false').'"><span class="fcc-language-code" aria-hidden="true">'.$h(strtoupper($code)).'</span><span class="fcc-language-name">'.$h($labels[$code]??$name).'</span><span class="fcc-language-check" aria-hidden="true">'.($selected?'✓':'').'</span></button>';
    }
    echo '</form><p class="fcc-language-hint">'.$h($copy[2]).'</p></div></details>';
}

/** Explicit instruction appended after context and examples, including repair calls. */
function fcc_ai_locale_instruction(?string $language = null): string {
    $locale = fcc_locale($language);
    $name = ['sl'=>'Slovenian (sl-SI), never Slovak', 'hr'=>'Croatian', 'en'=>'English', 'de'=>'German', 'es'=>'Spanish', 'sr'=>'Serbian in Latin script (sr-Latn), standard Ekavian Serbian, never Cyrillic', 'sq'=>'Albanian', 'cnr'=>'Montenegrin in Latin script (cnr-Latn), consistently Ijekavian, never Ekavian or Cyrillic', 'fr'=>'French','sv'=>'Swedish','hu'=>'Hungarian','pl'=>'Polish','ro'=>'Romanian'][$locale];
    return "The selected interface language is {$name}. In German use informal du, in French informal tu and in Spanish neutral international tú. Address members naturally and consistently unless the member explicitly requests a formal recipient message. Write all new explanations, follow-up questions, advice, fallback text, action labels and proposed recipient messages in {$name}. Translate factual source information faithfully. Earlier conversation messages or examples in another language do not change this selection. Only use another language for a recipient draft when the member explicitly requests that draft in that language. Keep personal names exactly as supplied, never translate or anglicize them. Keep product names, programme names, URLs and referral parameters unchanged. Language does not determine country, medical suitability or programme qualification. Do not translate JSON property names, identifiers or required machine values. Do not invent facts or personal experiences. Preserve all existing factual and safety requirements. Do not use dash punctuation or dash bullet markers in prose. Use sentences, commas, colons or numbered lists. Keep hyphens only where required in URLs, identifiers and official names.";
}


/** Translate trusted editorial HTML without changing links, markup or embedded code. */
function fcc_localize_html(string $html, ?string $locale = null): string {
    if(!in_array(fcc_locale($locale), ['sl','en','de','sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'], true)) return $html;
    $parts = preg_split('~(<!--.*?-->|<(?:script|style|pre|code)\b[^>]*>.*?</(?:script|style|pre|code)\s*>|<[^>]+>)~is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach($parts as &$part) {
        if($part === '') continue;
        if($part[0] === '<') {
            if(preg_match('~^<!--|^<(?:script|style|pre|code)\b~i', $part)) continue;
            $part = preg_replace_callback('~\b(alt|title|aria-label)=("|\')(.*?)\2~is', static function($m) use($locale) {
                $source = html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $translated = fcc_t($source, [], $locale);
                return $translated === $source ? $m[0] : $m[1] . '=' . $m[2] . htmlspecialchars($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8') . $m[2];
            }, $part);
        } else {
            preg_match('/^(\s*)(.*?)(\s*)$/su', $part, $m);
            $source = html_entity_decode($m[2] ?? $part, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $translated = fcc_t($source, [], $locale);
            if($translated !== $source) $part = ($m[1] ?? '') . htmlspecialchars($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ($m[3] ?? '');
        }
    }
    unset($part);
    return implode('', $parts);
}


function fcc_i18n_assets(): void {
    static $loaded=false;
    if($loaded)return;
    $loaded=true;
    $root=(parse_url(SITE_URL,PHP_URL_PATH)?:'/').ASSETS_URL_PATH.'js/';
    echo '<script src="'.htmlspecialchars($root.'fcc-i18n.js?v='.filemtime(ASSETS_PATH.'js/fcc-i18n.js'),ENT_QUOTES,'UTF-8').'"></script>';
    $locale=fcc_locale(); if(in_array($locale,['sl','en','de','sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'],true) && is_file(ASSETS_PATH.'js/fcc-i18n-'.$locale.'.js')) echo '<script src="'.htmlspecialchars($root.'fcc-i18n-'.$locale.'.js?v='.filemtime(ASSETS_PATH.'js/fcc-i18n-'.$locale.'.js'),ENT_QUOTES,'UTF-8').'"></script>';
}

/** ICU uses Serbian Latin for Montenegrin region/date names until it supports cnr. */
function fcc_intl_locale(?string $locale = null): string {
    return match(fcc_locale($locale)) {'sr'=>'sr_Latn', 'cnr'=>'sr_Latn_ME', default=>fcc_locale($locale)};
}
function fcc_new_locale(?string $locale = null): bool {
    return in_array(fcc_locale($locale), ['sq','cnr','fr','es','sv','hu','pl','ro'], true);
}
