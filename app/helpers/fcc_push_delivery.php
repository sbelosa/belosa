<?php
defined('ALTUMCODE') || die();

/** Public type codes and counts replace arbitrary stored text on lock screens. CC details require explicit opt-in. */
function fcc_push_kind(array $notice): string {
    $key=$notice['event_key']??'';
    if($key==='announcement:product-education:20261008:v1')return 'product_education';
    foreach(['push-test:'=>'test','support:'=>'support','approval:'=>'approval','nfc:'=>'nfc','cc:'=>'cc','business:lead:'=>'contact_lead','business:daily:'=>'followup','webinar:registration:'=>'guest_registration','onboarding:team-group:'=>'team_group'] as $prefix=>$kind)if(str_starts_with($key,$prefix))return $kind;
    if(str_starts_with($key,'webinar:calendar:')) {
        $kind=substr($key,strrpos($key,':')+1);
        if(in_array($kind,['cancelled','postponed','changed'],true))return 'webinar_'.$kind;
        if(str_starts_with($kind,'reminder'))return 'webinar_reminder';
    }
    return in_array($notice['category']??'',['team','daily','service','admin','followup','tasks','education','webinar','cc'],true)?$notice['category']:'service';
}

/* Custom code: FC-2026-10-12: a public detail code for events of this release. The kind stays the
   same, so workers that do not know the detail keep showing the category text; updated workers
   show the exact event (help request, weekly overview, invitation, new card) without personal data. */
function fcc_push_detail(array $notice): ?string {
    $key=(string)($notice['event_key']??'');
    foreach(['mp:help:'=>'team_help','mp:stuck:'=>'team_help','mp:weekly:'=>'team_weekly','mp:milestone:'=>'team_milestone','mp:start:'=>'team_start','invite:registration:'=>'invite_registration','invite:order:'=>'invite_order','card-aloe:'=>'card_update'] as $prefix=>$detail)if(str_starts_with($key,$prefix))return $detail;
    return null;
}
/* /Custom code: FC-2026-10-12 */

/** A two minute device cooldown combines bursts without moving work to tomorrow. */
function fcc_push_batch_due(array $batch,DateTimeImmutable $now): DateTimeImmutable {
    foreach($batch as $item)if(in_array(fcc_push_kind($item),['test','daily','cc','followup','webinar','webinar_reminder','webinar_changed','webinar_cancelled','webinar_postponed'],true))return $now;
    $last=fcc_partner_one("SELECT MAX(updated_at) at FROM fcc_partner_push_deliveries WHERE subscription_id=? AND status IN ('sent','simulated') AND updated_at<=?",[(int)$batch[0]['subscription_id'],$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')]);
    if(empty($last['at']))return $now;
    $due=(new DateTimeImmutable($last['at'],new DateTimeZone('UTC')))->modify('+2 minutes');
    // Do not delay an item beyond its useful lifetime.
    foreach($batch as $item)if(strtotime($item['expires_at'].' UTC')<=$due->getTimestamp())return $now;
    return $due>$now?$due:$now;
}

function fcc_push_payload(array $batch): array {
    $first=$batch[0];$counts=[];
    foreach($batch as $item){$kind=fcc_push_kind($item);$counts[$kind]=($counts[$kind]??0)+1;}
    $path=count($batch)>1?'partner/notifications':(($first['category']??'')==='daily'?'partner':fcc_partner_notification_path($first['path']));
    $payload=['schema'=>2,'kind'=>count($batch)>1?'summary':fcc_push_kind($first),'counts'=>$counts,'locale'=>fcc_locale($first['language']??'en'),'sound'=>!empty($first['sound']),'tag'=>'fcc-'.min(array_column($batch,'notification_id')),'url'=>url($path)];
    if(count($batch)===1&&($detail=fcc_push_detail($first)))$payload['detail']=$detail; /* Custom code: FC-2026-10-12 */
    if(count($batch)===1&&fcc_push_kind($first)==='product_education') {
        // Existing workers recognize education; updated workers also recognize this public launch code.
        $payload['kind']='education';$payload['announcement']='product_education';
    }
    if(count($batch)===1&&fcc_push_kind($first)==='cc'&&!empty($first['cc_details'])) {
        $items=fcc_cc_notice_items((int)$first['user_id'],(int)$first['notification_id']);
        if($items)$payload['ccDetails']=mb_substr(fcc_cc_digest_text($items,$payload['locale']),0,500);
    }
    return $payload;
}

function fcc_push_setting_copy(string $key,?string $locale=null): string {
    $copy=[
        'hr'=>[
            'team_help'=>'Objedinjeni uspjesi izravnih suradnika. Push za webinar stiže nakon dvije potvrđene prijave. Istodobne obavijesti stižu u jednom sažetku.',
            'timing'=>'Obavijesti stižu tijekom dana, a više istodobnih događaja u jednom sažetku. Između običnih obavijesti može proći do dvije minute. Podsjetnici na dnevni korak, CC i događaje zadržavaju svoje termine. Noćna tišina traje od 21:00 do 08:00 po zagrebačkom vremenu. Sve pojedinačne poruke ostaju ovdje.',
            'sound'=>'Dopusti zvuk obavijesti',
            'sound_help'=>'FCC traži uobičajeni zvuk uređaja. Glasnoću, dostupnost zvuka i način Fokus određuju telefon i preglednik. Za diskretan zvuk smanji glasnoću obavijesti u postavkama telefona.',
            'test'=>'Probna obavijest stiže u sljedećem ciklusu slanja, obično unutar minute, izvan noćne tišine. Dostupna je jedna provjera na sat. Na iPhoneu otvori FCC preko ikone na početnom zaslonu.',
            'timezone'=>'Zagrebačko vrijeme',
        ],
        'en'=>[
            'team_help'=>'Combined progress from direct team members. Webinar progress arrives after two confirmed registrations. Simultaneous updates arrive in one summary.',
            'timing'=>'Notifications arrive throughout the day. Simultaneous updates are combined into one summary. Regular notifications may wait up to two minutes between sends. Daily, CC and event reminders keep their scheduled times. Quiet hours run from 21:00 to 08:00 Zagreb time. Every individual message remains here.',
            'sound'=>'Allow notification sound',
            'sound_help'=>'FCC requests the device notification sound. Your phone and browser control sound availability, volume and Focus mode. Lower notification volume in your phone settings for a discreet sound.',
            'test'=>'The test arrives in the next sending cycle, usually within a minute, outside quiet hours. One test per hour is available. On iPhone, open FCC using its Home Screen icon.',
            'timezone'=>'Zagreb time',
        ],
        'sl'=>[
            'team_help'=>'Združeni dosežki neposrednih sodelavcev. Obvestilo o webinarju prispe po dveh potrjenih prijavah. Sočasne novosti prispejo v enem povzetku.',
            'timing'=>'Obvestila prihajajo čez dan. Sočasne novosti združimo v en povzetek. Med običajnimi obvestili lahko mineta do dve minuti. Dnevni opomniki, CC in dogodki ohranijo svoj urnik. Nočni mir traja od 21:00 do 08:00 po zagrebškem času. Vsa posamezna sporočila ostanejo tukaj.',
            'sound'=>'Dovoli zvok obvestil',
            'sound_help'=>'FCC zahteva običajni zvok naprave. Telefon in brskalnik določata razpoložljivost zvoka, glasnost in način Fokus. Za diskreten zvok znižaj glasnost obvestil v nastavitvah telefona.',
            'test'=>'Preizkusno obvestilo prispe v naslednjem ciklu pošiljanja, običajno v eni minuti, izven nočnega miru. Na voljo je en preizkus na uro. Na iPhonu odpri FCC z ikono na začetnem zaslonu.',
            'timezone'=>'Zagrebški čas',
        ],
        'de'=>[
            'team_help'=>'Zusammengefasste Fortschritte direkter Teammitglieder. Webinarfortschritte werden ab zwei bestätigten Anmeldungen gemeldet. Gleichzeitige Neuigkeiten kommen in einer Zusammenfassung.',
            'timing'=>'Benachrichtigungen kommen im Laufe des Tages. Gleichzeitige Neuigkeiten werden zusammengefasst. Zwischen normalen Benachrichtigungen können bis zu zwei Minuten liegen. Erinnerungen für den Tag, CC und Veranstaltungen behalten ihre Termine. Die Ruhezeit gilt von 21:00 bis 08:00 Uhr nach Zagreber Zeit. Alle einzelnen Nachrichten bleiben hier.',
            'sound'=>'Benachrichtigungston erlauben',
            'sound_help'=>'FCC fordert den üblichen Geräteton an. Telefon und Browser steuern die Verfügbarkeit, Lautstärke und den Fokusmodus. Für einen dezenten Ton senke die Lautstärke der Benachrichtigungen in den Telefoneinstellungen.',
            'test'=>'Der Test kommt im nächsten Sendezyklus, normalerweise innerhalb einer Minute, außerhalb der Ruhezeit. Ein Test pro Stunde ist möglich. Öffne FCC auf dem iPhone über das Symbol auf dem Homebildschirm.',
            'timezone'=>'Zagreber Zeit',
        ],
        'es'=>[
            'team_help'=>'Progreso combinado de los colaboradores directos. El aviso del webinar llega tras dos inscripciones confirmadas. Las novedades simultáneas llegan en un resumen.',
            'timing'=>'Las notificaciones llegan a lo largo del día. Las novedades simultáneas se agrupan en un resumen. Entre las notificaciones normales pueden pasar hasta dos minutos. Los recordatorios diarios, de CC y de eventos mantienen sus horarios. El horario de silencio es de 21:00 a 08:00, hora de Zagreb. Todos los mensajes individuales permanecen aquí.',
            'sound'=>'Permitir sonido de notificaciones',
            'sound_help'=>'FCC solicita el sonido habitual del dispositivo. El teléfono y el navegador controlan su disponibilidad, volumen y modo de concentración. Para un sonido discreto, baja el volumen de las notificaciones en los ajustes del teléfono.',
            'test'=>'La prueba llega en el siguiente ciclo de envío, normalmente en un minuto, fuera del horario de silencio. Hay una prueba disponible por hora. En iPhone, abre FCC desde su icono en la pantalla de inicio.',
            'timezone'=>'Hora de Zagreb',
        ],
    ];
    $locale=fcc_locale($locale);if(in_array($locale,['sr','cnr'],true))$locale='hr';
    return $copy[$locale][$key]??fcc_t($copy['hr'][$key]??$copy['en'][$key]??'',[],$locale);
}

function fcc_push_inbox_label(array $notice): string {
    $source=['contact_lead'=>'Novi upit o poslovnoj suradnji','guest_registration'=>'Nova prijava na webinar','team_group'=>'Poveži se s timom','followup'=>'Dogovorena javljanja'];
    $kind=fcc_push_kind($notice);
    if($kind==='product_education')return fcc_push_product_education_copy()[0];
    return isset($source[$kind])?fcc_t($source[$kind]):(fcc_partner_notification_rules()[$notice['category']][0]??'FCC');
}

/** Public launch copy contains no account, customer or medical information. */
function fcc_push_product_education_copy(?string $locale=null): array {
    static $copy=[
        'hr'=>['Nova edukacija o proizvodima','Upoznaj sastojke, primjenu i kombinacije proizvoda. Otvori edukaciju i pronađi preporuku za razgovor s kupcem.'],
        'en'=>['New product education','Explore product ingredients, uses and combinations. Open the learning area and find a recommendation for your next customer conversation.'],
        'sl'=>['Novo izobraževanje o izdelkih','Spoznaj sestavine, uporabo in kombinacije izdelkov. Odpri izobraževanje in poišči priporočilo za pogovor s stranko.'],
        'de'=>['Neue Produktschulung','Entdecke Inhaltsstoffe, Anwendung und Produktkombinationen. Öffne die Schulung und finde eine Empfehlung für dein Kundengespräch.'],
        'es'=>['Nueva formación sobre productos','Conoce los ingredientes, usos y combinaciones de productos. Abre la formación y encuentra una recomendación para hablar con tus clientes.'],
        'sr'=>['Nova edukacija o proizvodima','Upoznaj sastojke, primenu i kombinacije proizvoda. Otvori edukaciju i pronađi preporuku za razgovor sa kupcem.'],
        'cnr'=>['Nova edukacija o proizvodima','Upoznaj sastojke, primjenu i kombinacije proizvoda. Otvori edukaciju i pronađi preporuku za razgovor s kupcem.'],
        'sq'=>['Trajnim i ri për produktet','Njih përbërësit, përdorimin dhe kombinimet e produkteve. Hap trajnimin dhe gjej një rekomandim për bisedën me klientin.'],
        'fr'=>['Nouvelle formation sur les produits','Découvre les ingrédients, les utilisations et les associations de produits. Ouvre la formation et trouve un conseil pour tes échanges avec les clients.'],
        'sv'=>['Ny produktutbildning','Lär dig om produkternas ingredienser, användning och kombinationer. Öppna utbildningen och hitta en rekommendation för ditt kundsamtal.'],
        'hu'=>['Új termékoktatás','Ismerd meg az összetevőket, a felhasználást és a termékkombinációkat. Nyisd meg az oktatást, és találj ajánlást a vásárlóval folytatott beszélgetéshez.'],
        'pl'=>['Nowa edukacja o produktach','Poznaj składniki, zastosowania i połączenia produktów. Otwórz materiały i znajdź rekomendację do rozmowy z klientem.'],
        'ro'=>['Noua educație despre produse','Descoperă ingredientele, utilizările și combinațiile de produse. Deschide materialele și găsește o recomandare pentru discuția cu un client.'],
    ];
    return $copy[fcc_locale($locale)]??$copy['en'];
}
