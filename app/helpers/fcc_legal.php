<?php
defined('ALTUMCODE') || die();

function fcc_legal_config(): array {
    static $config;
    return $config ??= require APP_PATH . 'config/fcc_legal.php';
}

function fcc_legal_url(string $document = 'privacy'): string {
    return url('legal/') . (in_array($document, ['privacy', 'terms'], true) ? $document : 'privacy');
}

function fcc_legal_pending(): array {
    $config = fcc_legal_config();
    $missing = [];
    foreach(['name', 'address', 'registration_id', 'email'] as $field) {
        if(trim((string)($config['operator'][$field] ?? '')) === '') $missing[] = 'operator.' . $field;
    }
    if(empty($config['operator']['identity_confirmed'])) $missing[] = 'operator.identity_confirmed';
    foreach($config['production_review'] as $key => $confirmed) {
        if(!$confirmed) $missing[] = 'production_review.' . $key;
    }
    return $missing;
}

function fcc_legal_document(string $document): array {
    $config = fcc_legal_config();
    $documents = require APP_PATH . 'config/legal/2026-09-26.1.hr.php';
    if($document==='privacy') $documents[$document]['sections'][]=['id'=>'marketing-plan','title'=>'Marketing plan i osobni poslovni upiti','html'=>'<p>Osobna prezentacija povezana je sa suradnikom koji ju je podijelio. Kada zatražiš odgovor, FCC bilježi tvoje ime, kontakt za odabrani kanal, neobveznu zemlju i pitanje te izvor i vrijeme upita. Podaci se dostavljaju tom suradniku radi zatraženog odgovora. Suradnik prima zasebnu email obavijest, a ti primaš potvrdu ako si naveo/la email. Time se ne prijavljuješ na newsletter niti daješ dopuštenje za druge kampanje.</p><p>Zahtjev za javljanje možeš povući ili ukloniti podatke upita preko privatne poveznice prikazane nakon predaje i u potvrdi. Za zasebnu raniju povijest komunikacije možeš se javiti na info@forevercard.club. Podaci upita uklanjaju se nakon 90 dana bez zabilježenog nastavka. Osobno potvrđen odgovor ili dogovoreni nastavak pomiču rok čuvanja upita do 90 dana nakon te radnje odnosno dogovorenog datuma. Zasebna povijest aktivnog odnosa slijedi primjenjiva pravila čuvanja kontakata i prava na brisanje.</p><p>Otvaranje WhatsAppa ili službene ponude ne dokazuje slanje poruke, kupnju ili učlanjenje. Službeno naručivanje i registracija odvijaju se na stranici odgovarajućeg pružatelja, prema pravilima za odabranu zemlju.</p>'];
    return in_array(fcc_locale(),['sl','en','de','sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'],true) ? fcc_localize_copy($documents[$document]) : $documents[$document];
}

/** No private offers, identifiers or invitation tokens in public structured data. */
function fcc_legal_schema(string $document, array $content): array {
    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        '@id' => fcc_legal_url($document) . '#document',
        'url' => fcc_legal_url($document),
        'name' => $content['title'] . ' | Forever Card Club',
        'description' => $content['description'],
        'inLanguage' => fcc_locale(),
        'dateModified' => fcc_legal_config()['updated'],
        'isPartOf' => ['@type' => 'WebSite', 'name' => 'Forever Card Club', 'url' => SITE_URL],
        'about' => ['@type' => 'SoftwareApplication', 'name' => 'Forever Card Club', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web'],
    ];
}
