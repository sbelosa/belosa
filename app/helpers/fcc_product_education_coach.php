<?php

require_once __DIR__ . '/fcc_product_education.php';
require_once __DIR__ . '/fcc_product_education_packaging.php';
require_once __DIR__ . '/fcc_product_education_i18n.php';

/** Deterministic retrieval from the published education, without database or model calls. */
function fcc_product_education_coach_normalize(string $value): string {
    $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    $value = strtr($value, ['č' => 'c', 'ć' => 'c', 'đ' => 'd', 'š' => 's', 'ž' => 'z',
        'Č' => 'c', 'Ć' => 'c', 'Đ' => 'd', 'Š' => 's', 'Ž' => 'z', 'é' => 'e', 'ü' => 'u',
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a', 'ă' => 'a', 'ą' => 'a',
        'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'ě' => 'e', 'ę' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ő' => 'o', 'ø' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ű' => 'u', 'ñ' => 'n', 'ç' => 'c', 'ł' => 'l', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't', 'ß' => 'ss', 'ń' => 'n',
        'ś' => 's', 'ź' => 'z', 'ż' => 'z']);
    return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value));
}

function fcc_product_education_coach_text($value, string $language = 'hr'): string {
    if(is_string($value)) return trim($value);
    if(!is_array($value)) return '';
    foreach([$language, 'hr', 'en'] as $key) if(is_string($value[$key] ?? null)) return trim($value[$key]);
    foreach($value as $item) if(is_string($item)) return trim($item);
    return '';
}

function fcc_product_education_coach_phrase(string $text, string $phrase): bool {
    return $phrase !== '' && str_contains(' ' . $text . ' ', ' ' . $phrase . ' ');
}

/** Ordinary business wording across the existing thirteen interface languages. */
function fcc_product_education_coach_business_request(string $message): bool {
    $query = fcc_product_education_coach_normalize($message);
    return preg_match('/\b(?:objav\w*|caption|post|publish\w*|webinar\w*|kontakt\w*|contact\w*|poslov\w*|posao|posla|poslu|poslom|proviz\w*|regrut\w*|zarad\w*|business|marketing|commission\w*|kampanj\w*|campaign\w*|pro paket\w*|pro package|prodaj\w*|prezentacij\w*|instagram|facebook|story|reels|veroffentlich\w*|geschaft\w*|kontakte|webseite\w*|publicar|publicacion\w*|negocio\w*|contacto\w*|comision\w*|publier|publication\w*|activite\w*|entreprise\w*|contacts|affaire\w*|commission\w*|podjet\w*|posel|provizij\w*|stik\w*|rekrut\w*|biznes\w*|dzialalnosc|kontakty|wizytowk\w*|opublik\w*|publik\w*|vallalkoz\w*|uzlet\w*|kapcsolat\w*|jutalek\w*|kozzete\w*|afacer\w*|contacte|publicare|publica|comisio\w*|verksamhet|foretag\w*|kontakter|publicera\w*|provision\w*|biznes|kontaktet|publiko\w*|komision\w*|pune|cijen\w*|cena|price\w*|cost\w*|discount|popust\w*|preis\w*|precio\w*|prix|koszt\w*|pret\w*)\b/u', $query) === 1;
}

function fcc_product_education_coach_platform_request(string $message): bool {
    $query = fcc_product_education_coach_normalize($message);
    return preg_match('/\b(?:blok\w*|block\w*|bloc\w*|blog|web\s*stranic\w*|website\w*|web page|postavk\w*|settings|login|log in|prijav\w*|registr\w*|dodati|dodaj|dodavanje|shop|poveznic\w*|link\w*|urediti|uredjivac|editor|webseite\w*|einstellung\w*|anmelden|hinzufug\w*|pagina web|sitio web|ajust\w*|anadir|site web|parametre\w*|connexion|ajouter|spletn\w*\s+stran\w*|nastavit\w*|dodajanje|strona\w*|ustawien\w*|zalog\w*|dodac|weboldal\w*|beallitas\w*|bejelentkez\w*|hozzaad\w*|site ul|siteul|setari|conectare|adauga|webbplats\w*|installning\w*|logga in|lagga till|faqe\w*|cil(es|et)im\w*|hyrje|shto)\b/u', $query) === 1;
}

function fcc_product_education_coach_is_usage_question(string $message): bool {
    if(fcc_product_education_coach_business_request($message) || fcc_product_education_coach_platform_request($message)) return false;
    $query = fcc_product_education_coach_normalize($message);
    return preg_match('/\b(?:doz\w*|dosage|dose\w*|dosis|dosering|dos|koliko.{0,25}(?:pije|piti|uzima|uzimati|jemati)|kako.{0,25}(?:pije|piti|uzima|uzimati|uzeti|koristi|koristiti|jemati|uporablja)|kolicina|how\s+(?:much|many|do i take|should i take|to take|to use|do i use)|wie\s+(?:nehme|viel)|einnahme|anwendung|comment\s+(?:prendre|utiliser)|cuanto\w*|cuanta\w*|como\s+(?:tomar|usar)|adag\w*|mennyit|hogyan kell|cum\s+(?:se ia|iau|se utilizeaza)|dawk\w*|ile|jak\s+(?:stosowac|brac|przyjmowac)|hur\s+(?:mycket|tar|anvander)|sa duhet|si\s+(?:merret|perdoret))\b/u', $query) === 1;
}

function fcc_product_education_coach_facts_request(string $message): bool {
    $query = fcc_product_education_coach_normalize($message);
    return fcc_product_education_coach_is_usage_question($message)
        || preg_match('/\b(?:sastav\w*|sastoj\w*|sadrz\w*|primjen\w*|uporab\w*|upotreb\w*|uziman\w*|razlik\w*|cemu|ingredient\w*|contain\w*|composition|usage|inhaltsstoff\w*|zusammensetzung|enthalt\w*|anwendung|composicion|ingrediente\w*|contiene|utilisation|contient|sestav\w*|vsebuje|uporaba|odmerek|sklad\w*|zawiera|stosowan\w*|osszetetel|osszetevo\w*|tartalmaz|adagolas|compozitie|contine|utilizare|innehall\w*|ingrediens\w*|anvandning|perber\w*|permban|perdorim)\b/u', $query) === 1;
}

/** This signal requires a previous education selection supplied by the caller. It never invents one. */
function fcc_product_education_coach_is_followup(string $message): bool {
    $query = fcc_product_education_coach_normalize($message);
    return fcc_product_education_coach_is_usage_question($message)
        || preg_match('/^(?:napisi (?:mi )?(?:poruku|whatsapp)|posalji (?:mi )?(?:poruku|kupcu)|sto je prebiotik|sto je probiotik|moze (?:li )?krace|pojasni|objasni detaljnije|write (?:me )?(?:a )?(?:message|customer message)|customer message|make it shorter|kundennachricht|schreib\w*|nachricht|ecris|message client|scrie|escribe|mensaje|sporocilo|napis|wiadomosc|irj|meddelande|shkruaj)(?:\b|$)/u', $query) === 1;
}

/** Canonical topic words connect ordinary Croatian questions to existing English scenario IDs. */
function fcc_product_education_coach_topics(): array {
    return [
        'gastritis' => ['gastritis', 'gastritisa', 'gastritisom', 'upala zeluca', 'upale zeluca', 'osjetljiv zeludac', 'sensitive stomach'],
        'reflux' => ['refluks', 'refluksa', 'reflux', 'zgaravica', 'zgaravice', 'kiselina u zelucu'],
        'bloating' => ['nadutost', 'nadutosti', 'napuhnutost', 'napuhnut', 'bloating', 'plinovi'],
        'constipation' => ['zatvor', 'zatvora', 'opstipacija', 'opstipacije', 'neredovita stolica', 'neredovitu stolicu', 'constipation'],
        'antibiotics' => ['antibiotik', 'antibiotika', 'antibiotici', 'antibiotike', 'antibiotics', 'antibiotic'],
        'helicobacter' => ['helicobacter', 'helikobakter', 'h pylori', 'helicobacter pylori'],
        'menopause' => ['menopauza', 'menopauze', 'menopauzi', 'menopauzu', 'menopause', 'valunzi', 'valunga', 'valunge', 'nocno znojenje', 'promjene raspolozenja'],
        'immunity' => ['imunitet', 'imuniteta', 'imunitetu', 'slab imunitet', 'slabi imunitet', 'immune', 'immunity', 'odpornost'],
        'energy' => ['umor', 'umora', 'umoran', 'iscrpljenost', 'energija', 'energije', 'energy', 'fatigue'],
        'focus' => ['koncentracija', 'koncentracije', 'koncentraciju', 'mentalni napor', 'focus', 'concentration'],
        'stress' => ['stres', 'stresa', 'napetost', 'stress'],
        'joint' => ['zglobovi', 'zglobove', 'zglobova', 'zglobovima', 'pokretljivost', 'ukocenost', 'joint', 'mobility', 'cartilage', 'osteoartritis'],
        'bone' => ['kosti', 'kostiju', 'prijelom', 'prijeloma', 'osteoporoza', 'bone', 'fracture'],
        'circulation' => ['cirkulacija', 'cirkulacije', 'cirkulaciju', 'krvne zile', 'krvnih zila', 'hladne ruke', 'hladne noge', 'circulation'],
        'triglyceride' => ['trigliceridi', 'triglicerida', 'trigliceride', 'triglyceride'],
        'liver' => ['masna jetra', 'masne jetre', 'fatty liver'],
        'urinary' => ['mokracni sustav', 'mokracnog sustava', 'mokracenje', 'urinarni', 'urinary'],
        'kidney' => ['bubrezni kamenac', 'bubrezni kamenci', 'bubreznih kamenaca', 'kidney stone'],
        'sweating' => ['znojenje', 'znojenja', 'znoj', 'miris pazuha', 'neugodan miris', 'sweating', 'underarm'],
        'skin_dry' => ['suha koza', 'suhe koze', 'suhu kozu', 'zateze', 'dry skin'],
        'hair' => ['kosa', 'kose', 'kosu', 'vlasi', 'hair'],
        'oral' => ['zubi', 'zube', 'zuba', 'desni', 'oral', 'tooth', 'teeth'],
        'psoriasis' => ['psorijaza', 'psorijaze', 'psoriasis'],
        'allergy' => ['alergija', 'alergije', 'alergijski', 'allergy'],
        'weight' => ['mrsavljenje', 'mrsavljenja', 'kilogrami', 'kilograma', 'tjelesna masa', 'tjelesne mase', 'weight loss', 'weight management'],
    ];
}

function fcc_product_education_coach_aliases(array $product, array $packaging, string $language): array {
    $key = (string) ($product['product_key'] ?? '');
    $aliases = [(string) ($product['product_key'] ?? ''), fcc_product_education_coach_text($product['name'] ?? '', $language)];
    foreach((array) ($product['aliases'] ?? []) as $alias) $aliases[] = fcc_product_education_coach_text($alias, $language);
    foreach((array) ($product['names'] ?? []) as $name) if(is_string($name)) $aliases[] = $name;
    $extra = [
        'forever-active-pro-b' => ['Pro B', 'ProB', 'Active Pro B', 'probiotik', 'probiotics'],
        'forever-aloe-vera-gel' => ['Aloe Vera Gel', 'aloe gel', 'gel aloe', 'aloe napitak'],
        'aloe-ever-shield' => ['Ever Shield', 'EverShield', 'deo stick', 'dezodorans'],
        'forever-multi-maca' => ['Multi Maca', 'MultiMaca', 'maca', 'maka'],
        'forever-nutra-q10' => ['Cardiohealth', 'Cardio Health', 'Nutra Q10', 'Q10'],
        'forever-arctic-sea' => ['Arctic Sea', 'ArcticSea', 'arktik sea'],
        'forever-fiber-fusion' => ['Fiber Fusion', 'novi fiber', 'vlakna'],
        'forever-bright' => ['Bright Tooth Gel', 'Bright', 'zubni gel', 'gel za zube'],
        'forever-ivision' => ['iVision', 'i vision'],
    ];
    $aliases = array_merge($aliases, $extra[$key] ?? []);
    foreach((array) ($packaging['products'][$key]['offers'] ?? []) as $offer) {
        $aliases[] = (string) ($offer['name'] ?? '');
    }
    $result = [];
    foreach($aliases as $alias) {
        $alias = fcc_product_education_coach_normalize($alias);
        if(strlen($alias) < 3 || in_array($alias, ['forever', 'aloe', 'gel'], true)) continue;
        $result[$alias] = $alias;
        $short = trim((string) preg_replace('/^forever\s+/', '', $alias));
        if(strlen($short) >= 4 && !in_array($short, ['aloe', 'gel'], true)) $result[$short] = $short;
    }
    return array_values($result);
}

/** Keep retrieval compact. Ordinary platform and business questions return no product context. */
function fcc_product_education_coach_search(string $message, ?array $content = null, string $language = 'hr'): array {
    $content = $content ?? fcc_product_education_content();
    $query = fcc_product_education_coach_normalize($message);
    $empty = ['relevant' => false, 'intent' => 'none', 'products' => [], 'recommendations' => [], 'direct_product_keys' => []];
    if($query === '') return $empty;
    $exactCase = false;
    foreach(array_merge((array) ($content['published_recommendation_entries'] ?? []), (array) ($content['educational_entries'] ?? [])) as $entry) {
        if(($entry['publication_status'] ?? '') === 'withdrawn_by_author') continue;
        foreach(['id', 'display_title', 'education_title', 'title'] as $field) {
            $exact = fcc_product_education_coach_normalize(fcc_product_education_coach_text($entry[$field] ?? '', $language));
            if($exact !== '' && $query === $exact) { $exactCase = true; break 2; }
        }
    }
    $business = fcc_product_education_coach_business_request($message);
    $productFacts = fcc_product_education_coach_facts_request($message);
    $knowledge = $productFacts || preg_match('/\b(?:preporuc\w*|recommend\w*|empfiehl\w*|recommand\w*|recomiend\w*|kod|protiv)\b/u', $query) === 1;
    if($business && !$knowledge && !$exactCase) return $empty;
    $platform = fcc_product_education_coach_platform_request($message);
    if($platform && !$productFacts && !$exactCase) return $empty;
    $packaging = fcc_product_education_packaging_data();
    $products = array_column((array) ($content['products'] ?? []), null, 'product_key');
    $direct = [];
    $articleQuery = preg_match('/^(?:artikl|artikal|sifra|broj artikla|sku|proizvod|product)?\s*0*([0-9]{1,4})$/', $query, $articleMatch) === 1;
    if(!$articleQuery) $articleQuery = preg_match('/\b(?:artikl|artikal|sifra|broj artikla|sku)\s+0*([0-9]{1,4})\b/', $query, $articleMatch) === 1;
    foreach($products as $key => $product) {
        $score = 0;
        foreach(fcc_product_education_coach_aliases($product, $packaging, $language) as $alias) {
            if(fcc_product_education_coach_phrase($query, $alias)) $score = max($score, 120 + min(40, strlen($alias)));
        }
        if($articleQuery) foreach((array) ($packaging['products'][$key]['offers'] ?? []) as $offer) {
            if(ltrim((string) ($offer['sku'] ?? ''), '0') === ltrim($articleMatch[1], '0')) $score = max($score, 180);
        }
        if($score > 0) $direct[$key] = $score;
    }
    $topics = [];
    foreach(fcc_product_education_coach_topics() as $key => $aliases) foreach($aliases as $alias) {
        if(fcc_product_education_coach_phrase($query, $alias)) { $topics[$key] = $aliases; break; }
    }
    $stopWords = ['sto', 'sta', 'koji', 'koja', 'koje', 'kako', 'kod', 'za', 'uz', 'mi', 'je', 'na', 'od', 'do', 'li', 'i', 'ili', 'te', 'to', 'se', 'molim', 'mozes', 'mogu', 'moze', 'preporuciti', 'preporuci', 'preporuka', 'preporuke', 'proizvod', 'proizvodi', 'proizvode', 'forever', 'napisi', 'trebam', 'zelim', 'kupcu', 'whatsapp', 'poruku', 'what', 'recommend', 'for', 'with', 'the', 'plan', 'program', 'ljudi', 'osoba', 'kupac', 'kupci', 'dana', 'informacije', 'edukacija', 'edukaciju', 'kontakt', 'prodaja', 'prodaje', 'poslovni', 'suradnici', 'suradnika', 'objavu',
        'proizvoda', 'proizvodima', 'uzima', 'uzimati', 'pije', 'koristiti', 'these', 'those', 'this', 'that', 'product', 'products', 'take', 'taking', 'much', 'many', 'dose', 'dosage', 'using', 'should',
        'produkt', 'produkte', 'diese', 'diesen', 'nehmen', 'einnahme', 'produit', 'produits', 'comment', 'prendre', 'utiliser', 'producto', 'productos', 'estos', 'estas', 'tomar', 'cuanto', 'cuantos',
        'izdelki', 'izdelke', 'izdelkov', 'jemati', 'uporabljati', 'produktow', 'produkty', 'stosowac', 'przyjmowac', 'termek', 'termekek', 'ezeket', 'mennyit', 'hogyan', 'produse', 'produsele', 'aceste', 'acestea',
        'produkter', 'dessa', 'mycket', 'anvander', 'produkteve', 'produktet', 'merret', 'perdoret'];
    $tokens = array_values(array_filter(explode(' ', $query), static fn(string $token): bool => strlen($token) >= 4 && !in_array($token, $stopWords, true) && !ctype_digit($token)));
    $entries = array_merge((array) ($content['published_recommendation_entries'] ?? []), (array) ($content['educational_entries'] ?? []));
    $ranked = [];
    $requestedKeys = array_keys($direct);
    $matchedCombination = false;
    $combinationPriority = ['digestive_routine_support', 'acid_reflux_support', 'immunity_support_routine'];
    foreach($entries as $entry) {
        if(($entry['publication_status'] ?? '') === 'withdrawn_by_author' || empty($entry['practical_application'])) continue;
        $title = fcc_product_education_coach_normalize(implode(' ', array_filter([
            fcc_product_education_coach_text($entry['display_title'] ?? '', $language),
            fcc_product_education_coach_text($entry['education_title'] ?? '', $language),
            fcc_product_education_coach_text($entry['title'] ?? '', $language), str_replace('_', ' ', (string) ($entry['id'] ?? ''))])));
        $body = fcc_product_education_coach_normalize((string) ($entry['practical_application'] ?? ''));
        $score = 0;
        if(fcc_product_education_coach_phrase($query, fcc_product_education_coach_normalize((string) ($entry['id'] ?? '')))) $score += 200;
        foreach(['display_title', 'education_title', 'title'] as $field) {
            $exactTitle = fcc_product_education_coach_normalize(fcc_product_education_coach_text($entry[$field] ?? '', $language));
            if(strlen($exactTitle) >= 5 && fcc_product_education_coach_phrase($query, $exactTitle)) $score += 120;
        }
        foreach($topics as $topic => $aliases) {
            $topicTitle = fcc_product_education_coach_phrase($title, $topic);
            $topicBody = false;
            foreach($aliases as $alias) {
                $topicTitle = $topicTitle || fcc_product_education_coach_phrase($title, $alias);
                $topicBody = $topicBody || fcc_product_education_coach_phrase($body, $alias);
            }
            $score += $topicTitle ? 60 : ($topicBody ? 28 : 0);
        }
        foreach($tokens as $token) if(fcc_product_education_coach_phrase($title, $token)) $score += 22;
        $references = (array) ($entry['display_products'] ?? $entry['products'] ?? []);
        $referenceKeys = array_values(array_unique(array_column($references, 'product_key')));
        $hasDirect = false;
        foreach($references as $reference) if(isset($direct[$reference['product_key'] ?? ''])) $hasDirect = true;
        // Named products use published relationships. An exact case lookup keeps its own identity.
        if($direct && !$hasDirect && !$exactCase) continue;
        if($hasDirect) $score += $topics ? 40 : 24;
        if(count($requestedKeys) >= 2 && array_diff($requestedKeys, $referenceKeys) === []) {
            $matchedCombination = true;
            $score += 350;
            $extraProducts = count(array_diff($referenceKeys, $requestedKeys));
            $score += $extraProducts === 0 ? 30 : -2 * $extraProducts;
            $priority = array_search((string) ($entry['id'] ?? ''), $combinationPriority, true);
            if(!$topics && $priority !== false) $score += count($combinationPriority) - $priority;
        }
        if($score >= 22) {
            $entry['_coach_score'] = $score;
            $ranked[(string) $entry['id']] = $entry;
        }
    }
    if(!$direct && !$ranked) return $empty;
    uasort($direct, static fn(int $left, int $right): int => $right <=> $left);
    uasort($ranked, static fn(array $left, array $right): int => ($right['_coach_score'] <=> $left['_coach_score']) ?: strcmp((string) $left['id'], (string) $right['id']));
    $ranked = array_slice($ranked, 0, 3, true);
    $selected = [];
    $firstEntry = reset($ranked) ?: [];
    $productLimit = max(5, min(12, count((array) ($firstEntry['display_products'] ?? $firstEntry['products'] ?? []))));
    foreach(array_keys($direct) as $key) if(count($selected) < $productLimit) $selected[$key] = $products[$key];
    foreach($ranked as $entry) foreach((array) ($entry['display_products'] ?? $entry['products'] ?? []) as $reference) {
        $key = (string) ($reference['product_key'] ?? '');
        if(isset($products[$key]) && count($selected) < $productLimit) $selected[$key] = $products[$key];
    }
    $compactProducts = [];
    foreach($selected as $product) $compactProducts[] = array_intersect_key($product, array_flip([
        'product_key', 'name', 'topic', 'education_visibility', 'catalog_scope', 'practical_learning_intro',
        'practical_conversation_example', 'learning_depth', 'source_ids', 'aliases', 'child_usage']));
    $compactEntries = [];
    foreach($ranked as $entry) $compactEntries[] = array_intersect_key($entry, array_flip([
        'id', 'title', 'display_title', 'education_title', 'practical_application', 'combination_explanation',
        'practical_product_roles', 'combination_kind', 'use_logic', 'critical_notes', 'conversation_example',
        'display_products', 'products', 'education_source_ids', 'source_ids', 'consultation_note']));
    $intent = $exactCase || $topics || !$direct ? 'problem' : ($matchedCombination ? 'combination' : 'product');
    return ['relevant' => true, 'intent' => $intent, 'matched_combination' => $matchedCombination,
        'products' => $compactProducts, 'recommendations' => $compactEntries, 'direct_product_keys' => array_keys($direct)];
}

/** Remove all links from the customer draft. Trusted shop links can be appended by the caller. */
function fcc_product_education_coach_customer_clean(string $text): string {
    $text = preg_replace('/\[([^\]]+)\]\([^)]*\)/u', '$1', $text);
    $text = strip_tags((string) $text);
    $text = preg_replace('~https?://[^\s<>]+|(?:/|\b)fcc-product-education[^\s<>]*|\b(?:localhost|127\.0\.0\.1)(?::\d+)?[^\s<>]*~iu', '', $text);
    return trim((string) preg_replace('/[ \t]+/u', ' ', (string) $text));
}

function fcc_product_education_coach_customer_text(array $selection): string {
    if(empty($selection['relevant'])) return '';
    $draft = '';
    if(in_array($selection['intent'] ?? '', ['problem', 'combination'], true)) $draft = (string) ($selection['recommendations'][0]['conversation_example'] ?? '');
    if($draft === '') {
        $product = $selection['products'][0] ?? [];
        $draft = (string) ($product['practical_conversation_example'] ?? $product['learning_depth']['conversation_example'] ?? $product['conversation_example'] ?? '');
    }
    $notes = in_array($selection['intent'] ?? '', ['problem', 'combination'], true)
        ? (array) ($selection['recommendations'][0]['critical_notes'] ?? []) : [];
    if($notes) $draft .= "\n\n" . implode(' ', array_values(array_unique($notes)));
    return fcc_product_education_coach_customer_clean($draft);
}

/** Exact reviewed usage is copied as text; package arithmetic never invents a daily dose. */
function fcc_product_education_coach_usage(array $product, string $language = 'hr'): array {
    $key = (string) ($product['product_key'] ?? '');
    $record = fcc_product_education_packaging_data()['products'][$key] ?? [];
    $texts = [];
    foreach((array) ($record['usage_sources'] ?? []) as $source) {
        if(($source['review_status'] ?? '') !== 'approved' || ($source['review_scope'] ?? '') !== 'identity_and_label_facts'
            || !in_array($source['identity_status'] ?? '', ['verified_exact', 'verified_reviewed'], true)) continue;
        if((isset($source['market_code']) && $source['market_code'] !== 'hr')
            || (isset($source['language_code']) && $source['language_code'] !== 'hr')) continue;
        foreach((array) ($source['usage_texts'] ?? []) as $text) if(is_string($text) && trim($text) !== '') $texts[$text] = $text;
    }
    return ['label_instructions' => array_map(static fn(string $text): string => fcc_product_education_t($text, $language), array_values($texts)),
        'usage_note' => (string) ($product['learning_depth']['usage_note'] ?? ''),
        'replacement_product_key' => $record['replacement_product_key'] ?? null];
}

function fcc_product_education_coach_excerpt(string $text, int $limit): string {
    if(function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $limit) return rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . '…';
    return strlen($text) > $limit && !function_exists('mb_strlen') ? rtrim(substr($text, 0, $limit)) . '…' : $text;
}

/** Compact grounded material for the coach. Private navigation is returned separately from customer copy. */
function fcc_product_education_coach_context(string $message, string $language = 'hr', ?array $content = null): array {
    $content = $content ?? fcc_product_education_content();
    $content = fcc_product_education_localize_content($content, $language);
    $selection = fcc_product_education_coach_search($message, $content, $language);
    $selection['version'] = (string) ($content['version'] ?? '');
    $selection['language'] = $language;
    $selection['source_language'] = 'hr';
    $selection['internal_links'] = [];
    $selection['critical_notes'] = [];
    $selection['customer_message'] = '';
    $selection['grounded_context'] = '';
    if(!$selection['relevant']) return $selection;
    $allProducts = array_column((array) ($content['products'] ?? []), null, 'product_key');
    $packet = ['scope' => 'published_internal_product_education', 'products' => [], 'recommendations' => []];
    foreach($selection['products'] as $index => $product) {
        $key = (string) ($product['product_key'] ?? '');
        $depth = (array) ($product['learning_depth'] ?? []);
        $factors = [];
        foreach(array_slice((array) ($depth['key_factors'] ?? []), 0, 2) as $factor) $factors[] = [
            'title' => (string) ($factor['title'] ?? ''), 'explanation' => fcc_product_education_coach_excerpt((string) ($factor['explanation'] ?? ''), 220)];
        $ingredients = [];
        foreach(array_slice((array) ($depth['ingredients'] ?? []), 0, 2) as $ingredient) $ingredients[] = [
            'name' => (string) ($ingredient['name'] ?? ''),
            'how_it_works' => fcc_product_education_coach_excerpt((string) ($ingredient['how_it_works'] ?? ''), 240),
            'source_ids' => (array) ($ingredient['source_ids'] ?? [])];
        $usage = fcc_product_education_coach_usage($product, $language);
        $selection['products'][$index]['declared_directions'] = $usage['label_instructions'];
        $selection['products'][$index]['package_plans'] = fcc_product_education_package_plans($product);
        $packet['products'][] = ['product_key' => $key, 'name' => fcc_product_education_coach_text($product['name'] ?? '', $language),
            'visibility' => (string) ($product['education_visibility'] ?? 'current'),
            'application' => fcc_product_education_coach_excerpt((string) ($product['practical_learning_intro'] ?? $depth['why_this_product'] ?? ''), 440),
            'key_factors' => $factors, 'ingredients' => $ingredients, 'usage' => $usage,
            'source_ids' => (array) ($depth['source_ids'] ?? $product['source_ids'] ?? [])];
        $selection['internal_links'][] = ['kind' => 'product', 'id' => $key, 'title' => fcc_product_education_coach_text($product['name'] ?? '', $language),
            'url' => '/fcc-product-education?section=products&product=' . rawurlencode($key)];
    }
    foreach($selection['recommendations'] as $entry) {
        $notes = array_values(array_filter((array) ($entry['critical_notes'] ?? []), 'is_string'));
        $selection['critical_notes'] = array_merge($selection['critical_notes'], $notes);
        $usage = [];
        foreach((array) ($entry['display_products'] ?? $entry['products'] ?? []) as $reference) {
            $key = (string) ($reference['product_key'] ?? '');
            if(isset($allProducts[$key])) $usage[$key] = fcc_product_education_coach_usage($allProducts[$key], $language);
        }
        $packet['recommendations'][] = ['id' => (string) $entry['id'],
            'title' => fcc_product_education_coach_text($entry['display_title'] ?? $entry['title'] ?? '', $language),
            'application' => fcc_product_education_coach_excerpt((string) ($entry['practical_application'] ?? ''), 440),
            'why' => fcc_product_education_coach_excerpt((string) ($entry['combination_explanation'] ?? ''), 700),
            'combination_kind' => (string) ($entry['combination_kind'] ?? ''),
            'product_roles' => (array) ($entry['practical_product_roles'] ?? []), 'use_logic' => (array) ($entry['use_logic'] ?? []),
            'critical_notes' => $notes, 'product_usage' => $usage,
            'source_ids' => (array) ($entry['education_source_ids'] ?? $entry['source_ids'] ?? [])];
        $selection['internal_links'][] = ['kind' => 'recommendation', 'id' => (string) $entry['id'],
            'title' => fcc_product_education_coach_text($entry['display_title'] ?? $entry['title'] ?? '', $language),
            'url' => '/fcc-product-education?section=' . (count($usage) > 1 ? 'routines' : 'needs') . '&entry=' . rawurlencode((string) $entry['id'])];
    }
    $selection['critical_notes'] = array_values(array_unique($selection['critical_notes']));
    $firstCaseId = (string) ($selection['recommendations'][0]['id'] ?? '');
    $firstProductKey = (string) ($selection['products'][0]['product_key'] ?? '');
    usort($selection['internal_links'], static function(array $left, array $right) use ($selection, $firstCaseId, $firstProductKey): int {
        $rank = static function(array $link) use ($selection, $firstCaseId, $firstProductKey): int {
            if(in_array($selection['intent'], ['problem', 'combination'], true)) {
                if($link['kind'] === 'recommendation') return $link['id'] === $firstCaseId ? 0 : 3;
                return 1;
            }
            if($link['kind'] === 'product') return $link['id'] === $firstProductKey ? 0 : 2;
            return $link['id'] === $firstCaseId ? 1 : 3;
        };
        return $rank($left) <=> $rank($right);
    });
    $selection['customer_message'] = fcc_product_education_coach_customer_text($selection);
    $packet['customer_message'] = $selection['customer_message'];
    $selection['grounded_context'] = 'EDUCATION_REFERENCE_DATA ' . json_encode($packet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    return $selection;
}
