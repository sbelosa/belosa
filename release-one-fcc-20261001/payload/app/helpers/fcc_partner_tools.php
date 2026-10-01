<?php
defined('ALTUMCODE') || die();

/** One catalogue for navigation and the workspace around existing tools. */
function fcc_partner_tool_groups(): array {
    return array_map('fcc_t', ['content' => 'Poveznice i sadržaj', 'results' => 'Praćenje i rezultati', 'connections' => 'Povezivanja']);
}

function fcc_partner_tools(): array {
    $tools = [
        'links' => ['title'=>'Poveznice i datoteke', 'path'=>'links', 'group'=>'content', 'icon'=>'share', 'description'=>'Skrati poveznicu, podijeli datoteku ili pripremi dodatnu karticu.', 'help'=>'Odaberi vrstu sadržaja, unesi odredište i spremi. Postojeće poveznice i njihova statistika ostaju na istom mjestu.', 'routes'=>['links','link']],
        'qr-codes' => ['title'=>'QR kodovi', 'path'=>'qr-codes', 'group'=>'content', 'icon'=>'card', 'description'=>'Pripremi kod za karticu, letak ili događaj.', 'help'=>'Za svoju FCC karticu odaberi njezinu postojeću poveznicu. Nakon preuzimanja provjeri kod kamerom mobitela prije ispisa.', 'routes'=>['qr-codes','qr-code-create','qr-code-update']],
        'projects' => ['title'=>'Projekti', 'path'=>'projects', 'group'=>'content', 'icon'=>'book', 'description'=>'Organiziraj poveznice i QR kodove po temi ili kampanji.', 'help'=>'Kreiraj projekt, a zatim ga odaberi u postavkama poveznice ili QR koda. Tako lakše pratiš sadržaj za isti cilj.', 'routes'=>['projects','project-create','project-update']],
        'vip-funnel-studio' => ['title'=>'Funnel studio', 'path'=>'vip-funnel-studio', 'group'=>'content', 'icon'=>'people', 'description'=>'Složi kratku prezentaciju koja prikuplja upite.', 'help'=>'Kreni od predloška, prilagodi sadržaj i provjeri završni korak. Prijave prati u Kontaktima, a rezultate u Analitici funnela.', 'routes'=>['vip-funnel-studio']],
        'dashboard' => ['title'=>'Detaljna statistika', 'path'=>'dashboard?view=statistics', 'group'=>'results', 'icon'=>'chart', 'description'=>'Pogledaj izvore posjeta, klikove i interes za sadržaj.', 'help'=>'Odaberi razdoblje koje želiš usporediti. Posjet, klik i prijava različite su radnje. Klik sam po sebi nije potvrda kupnje.', 'routes'=>['dashboard','link-statistics']],
        'funnels-analytics' => ['title'=>'Rezultati funnela', 'path'=>'funnels-analytics', 'group'=>'results', 'icon'=>'chart', 'description'=>'Vidi koliko posjetitelja ostavi upit i gdje odustaju.', 'help'=>'Usporedi ulaze, početke obrasca i poslane prijave. Odaberi određenu prezentaciju kako bi znao što vrijedi doraditi.', 'routes'=>['funnels-analytics']],
        'pixels' => ['title'=>'Pikseli za oglašavanje', 'path'=>'pixels', 'group'=>'results', 'icon'=>'chart', 'description'=>'Poveži Meta, Google ili drugi alat za mjerenje oglasa.', 'help'=>'Kopiraj ID piksela iz svojeg oglašivačkog računa, odaberi odgovarajuću platformu i spremi. Zatim ga poveži s karticom u njezinim Postavkama. Prije uporabe provjeri postavke privatnosti i privole.', 'routes'=>['pixels','pixel-create','pixel-update']],
        'data' => ['title'=>'Prikupljeni upiti', 'path'=>'data', 'group'=>'results', 'icon'=>'people', 'description'=>'Pregledaj prijave iz obrazaca i izvornog FCC sustava.', 'help'=>'Ovdje su upiti iz postojećih FCC obrazaca. Za svakodnevne razgovore i dogovorena javljanja koristi Kontakte.', 'routes'=>['data']],
        'domains' => ['title'=>'Moje domene', 'path'=>'domains', 'group'=>'connections', 'icon'=>'link', 'description'=>'Koristi vlastitu web adresu za dodatne poveznice.', 'help'=>'Dodaj domenu pa slijedi DNS upute na obrascu. Adresa prve NFC kartice ostaje trajna. Vlastitu domenu koristi za dodatne poveznice.', 'routes'=>['domains','domain-create','domain-update']],
        'notification-handlers' => ['title'=>'Kanali obavijesti', 'path'=>'notification-handlers', 'group'=>'connections', 'icon'=>'bell', 'description'=>'Odredi gdje želiš primati obavijesti o novim upitima.', 'help'=>'Odaberi kanal, unesi njegove podatke i poveži ga s obrascem koji prikuplja upite. Obavijesti same aplikacije uređuješ u rubrici Obavijesti i podsjetnici.', 'routes'=>['notification-handlers','notification-handler-create','notification-handler-update']],
        'splash-pages' => ['title'=>'Uvodne stranice', 'path'=>'splash-pages', 'group'=>'content', 'icon'=>'card', 'description'=>'Dodaj kratku poruku prije otvaranja poveznice.', 'help'=>'Pripremi uvodnu stranicu i odaberi je na poveznici za koju je želiš prikazati. Koristi je kada uvod posjetitelju stvarno pomaže.', 'routes'=>['splash-pages','splash-page-create','splash-page-update']],
    ];
    foreach($tools as &$tool) foreach(['title','description','help'] as $field) $tool[$field] = fcc_t($tool[$field]);
    unset($tool);
    return $tools;
}

function fcc_partner_tool_context(string $controller, bool $admin = false): ?array {
    if($admin) return null;
    foreach(fcc_partner_tools() as $key=>$tool) {
        if(in_array($controller, $tool['routes'], true)) return ['key'=>$key] + $tool;
    }
    return null;
}
