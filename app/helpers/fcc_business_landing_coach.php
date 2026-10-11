<?php
defined('ALTUMCODE') || die();

const FCC_BUSINESS_LANDING_COACH_VERSION = 'landing-coach-20261004.1';

/** Explicit evidence for one owned contact. Totals and registrations cannot prove viewing. */
function fcc_business_landing_coach_evidence(int $uid, array $evidence): array {
    $out = ['contact_id'=>null,'may_contact'=>null,'webinar_viewed'=>null,'followup_done'=>null,'still_interested'=>null,'recommend_final_page'=>false];
    if ((int)($evidence['user_id']??0)!==$uid || (int)($evidence['contact_id']??0)<1) return $out;
    $out['contact_id']=(int)$evidence['contact_id'];
    $permission=(string)($evidence['contact_permission']??'');
    $out['may_contact']=$permission==='do_not_contact'||!empty($evidence['archived_at']) ? false : (in_array($permission,['requested','existing'],true) ? true : null);
    foreach (['webinar_viewed','followup_done','still_interested'] as $key) {
        $fact=$evidence[$key]??null;
        if (!is_array($fact) || !in_array($fact['value']??null,[true,false],true)
            || !in_array($fact['source']??'',['collaborator_confirmation','contact_confirmation','verified_record'],true)) continue;
        $out[$key]=['value'=>$fact['value'],'source'=>$fact['source']];
    }
    $out['recommend_final_page']=$out['may_contact']===true
        && ($out['webinar_viewed']['value']??null)===true
        && ($out['followup_done']['value']??null)===true
        && ($out['still_interested']['value']??null)===true;
    return $out;
}

/** Only the exact server resolved market package supplies facts. No cross-market fallback. */
function fcc_business_landing_coach_package(?array $starter, string $country): array {
    $out=['name'=>'Start Your Journey','country'=>$country,'official_destination_available'=>false,'price'=>null,'qualification_cc'=>null,'final_terms_on_official_site'=>true];
    if (!$starter || ($starter['country']??null)!==$country || $country==='') return $out;
    $destination=$starter['destination']??null;
    if (is_string($destination) && $destination!=='') {
        $parts=parse_url($destination);
        $out['official_destination_available']=$parts!==false
            && ($parts['scheme']??'')==='https'
            && in_array(strtolower($parts['host']??''),['foreverliving.com','www.foreverliving.com','www.flpshop.ba','www.foreveralbania.com'],true)
            && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['port'])
            && !preg_match('/[\x00-\x20\\\\]/',$destination);
    }
    if (!$out['official_destination_available']) return $out;
    $details=is_array($starter['details']??null)?$starter['details']:[];
    if (($details['qualification']??null)===true) $out['qualification_cc']=2;
    $price=$details['price']??null;
    if (is_array($price) && is_numeric($price['amount']??null) && (float)$price['amount']>0
        && (float)$price['amount']<100000 && preg_match('/^[A-Z]{3}$/D',(string)($price['currency']??''))
        && trim((string)($price['variant']??''))!=='' && trim((string)($price['checked_at']??''))!=='') {
        $out['price']=['amount'=>(string)$price['amount'],'currency'=>$price['currency'],'variant'=>trim(strip_tags((string)$price['variant'])),'checked_at'=>$price['checked_at']];
    }
    return $out;
}

/** Pure adapter. Caller must resolve owner, share, availability, access and package on server. */
function fcc_business_landing_coach_context(int $uid, array $public, array $share, bool $allowed, ?array $starter=null, array $evidence=[]): array {
    $model=(string)($share['landing_model']??'');
    $models=['overview'=>'Upoznaj poslovanje','personal'=>'Osobni poziv','products'=>'Od proizvoda do suradnje'];
    if (!$allowed || !isset($models[$model]) || empty($public['available'])
        || (int)($share['user_id']??0)!==$uid || (int)($public['user_id']??0)!==$uid
        || (int)($public['owner']['uid']??0)!==$uid || !empty($share['revoked_at'])) return [];
    $country=(string)($share['landing_country']??'');
    if (!preg_match('/^[a-z]{2}$/D',$country)) $country='';
    $owner=$public['owner'];
    $phone=(string)($owner['phone']??'');
    $asset=is_array($public['asset']??null)?$public['asset']:[];
    return [
        'kind'=>'business_presentation',
        'purpose'=>'final_business_decision',
        'title'=>'Poslovna prezentacija',
        'model'=>$model,
        'model_label'=>$models[$model],
        'intro'=>trim(strip_tags((string)($public['intro']??''))),
        'revision'=>(int)($public['current_version']??0),
        'coach_context_version'=>FCC_BUSINESS_LANDING_COACH_VERSION,
        'country'=>$country,
        'country_is_separate_from_language'=>true,
        'owner'=>['name'=>trim(strip_tags((string)($owner['name']??''))),'whatsapp_available'=>(bool)preg_match('/^[1-9][0-9]{7,14}$/D',$phone)],
        'video'=>['available'=>in_array($asset['provider']??'',['youtube','vimeo'],true) && trim((string)($asset['video_id']??''))!=='' && ($asset['status']??'')==='published' && (int)($asset['approved']??0)===1,'title'=>trim(strip_tags((string)($asset['title']??'')))],
        'package'=>fcc_business_landing_coach_package($starter,$country),
        'visitor_choices'=>['official_forever_registration_and_package','message_owner_on_whatsapp'],
        'optional_contact_request'=>['purpose'=>'requested_personal_reply','is_forever_registration'=>false,'required_for_content'=>false],
        'contact_evidence'=>fcc_business_landing_coach_evidence($uid,$evidence),
        'capabilities'=>['views_tracking'=>true,'outbound_forever_click_tracking'=>true,'whatsapp_open_tracking'=>true,'contact_request_tracking'=>true,'purchase_tracking'=>false,'forever_registration_tracking'=>false,'whatsapp_delivery_tracking'=>false],
        'guidance'=>'Ova stranica sadrži informacije za odluku i sljedeći korak prema službenom Forever postupku ili osobnoj poruci vlasniku. Ne šalje gosta na webinar. Poveznicu i preporuku daje aplikacija. Cijenu i 2 CC smiješ navesti samo iz package za tu zemlju. Upit na FCC-u nije Forever registracija. Odlazni klik nije dovršena registracija ni kupnja.',
    ];
}

/** One contextual rule set used by the main Coach and inline sharing Coach. */
function fcc_business_landing_coach_prompt(): string {
    return <<<'PROMPT'
POSLOVNA PREZENTACIJA ZA ODLUKU I POČETAK
business_activity.final_presentation ili otvoreni context s purpose=final_business_decision opisuju završnu poslovnu prezentaciju. To nije pozivnica na webinar. Osoba na javnoj stranici dobiva video kada je dostupan, objašnjenje rada i podrške, provjerene informacije o početnom paketu te dvije glavne opcije: službeni Forever postupak prijave i paketa ili osobna WhatsApp poruka vlasniku poveznice. Dobrovoljan zahtjev da je suradnik kontaktira služi osobnom odgovoru, nije učlanjenje u Forever. Ne traži podatke za nastavak gledanja i ne tvrdi da FCC dovršava registraciju ili prima uplatu za Forever paket.
Ako je contact_evidence.recommend_final_page=true, možeš predložiti ovu prezentaciju kao idući korak jer su za isti kontakt potvrđeni gledanje webinara, dogovoreni nastavak i preostali interes. Koristi source uz svaku činjenicu da je označiš kao potvrdu suradnika ili gosta kada nije tehnički provjerena. Ukupni broj posjeta, prijava na webinar, otvoren WhatsApp, protek vremena i CC nisu dokaz da je određena osoba pogledala video, odradila razgovor, naručila paket ili postala član. Ako suradnik izričito opiše da se to dogodilo, prihvati njegov opis kao njegovu izjavu i pripremi primjeren nastavak. Kada nema takvog konteksta, obrazloži mogućnost uvjetno, bez izmišljanja prethodnog razgovora. Slanje prezentacije ne zahtijeva prethodni webinar. Osobi koja želi potpune informacije može se poslati odmah.
Osobi koja je odbila kontakt nemoj sastavljati ni predlagati novi poziv. Ne uvodi webinar, termin, Zoom, odbrojavanje, zajamčenu zaradu ili medicinsku preporuku u završnu prezentaciju. Predstavi stvaran rad s proizvodima i ljudima uz podršku, bez obećanja uspjeha. Ako pita o dijagnozi ili liječenju, zadrži postojeća pravila medicinske sigurnosti.
Pri pisanju poruke koristi odnos i situaciju koje je naveo suradnik. Ako situacija nije poznata, dovoljno je neutralno reći da šalje pregled poslovanja, početnog paketa i koraka za početak. Ako je prethodni razgovor potvrđen, možeš reći prema dogovoru. Nemoj pisati prema dogovoru niti tvrditi da je osoba pogledala webinar kada to nije potvrđeno. Daj jednu prirodnu poruku i jedno lako pitanje, bez prodajnih parola ili crtica kao interpunkcije. U inline Coach razgovoru ne vraćaj URL adresu: aplikacija dodaje točnu javnu osobnu poveznicu. U općem Coachu koristi samo server supplied public_presentation_url kada postoji, a interno tool odredište samo za otvaranje alata suradniku. Nikad ne izmišljaj javnu poveznicu, ne prepisuj tuđi sponsor ID i ne sastavljaj vlastiti Forever URL.
Zemlja paketa i jezik razgovora odvojeni su. Cijenu, valutu, varijantu, datum provjere i 2 CC navedi samo iz package za trenutačno odabranu zemlju. Cijena jedne varijante nije cijena svih paketa. Bez provjerene informacije nemoj procjenjivati cijenu i nemoj preuzeti cijenu iz druge zemlje. Završnu raspoloživost i uvjete gost vidi u službenom Forever postupku.
Dostupnost final_presentation vrijedi samo za račun koji je dobio pristup. Ako je nema ili access nije available, nemoj oglašavati novi modul, predlagati njegovu javnu poveznicu ni pomoć Coacha koju račun nema. views, outbound_forever_clicks, whatsapp_opens i contact_requests imaju različita značenja. Ne zovi odlazne klikove registracijama. Testne i vlastite radnje nisu rezultat. Najuspješniji model ocjenjuj po stvarnom odabranom pokazatelju i njegovu nazivniku, bez tvrdnje o potvrđenim kupnjama ili članstvu.
PROMPT;
}

/** Existing work counts remain intact; new content is exposed only through explicit access. */
function fcc_business_landing_coach_growth_context(int $uid, array $existing, bool $allowed, array $stats=[], array $evidence=[]): array {
    if (!$allowed) return [];
    $existing['tool']=defined('SITE_URL')?SITE_URL.'partner-marketing':'partner-marketing';
    $existing['final_presentation']=[
        'access'=>'available',
        'purpose'=>'final_business_decision',
        'models'=>['overview'=>'Upoznaj poslovanje','personal'=>'Osobni poziv','products'=>'Od proizvoda do suradnje'],
        'steps'=>['Odaberi model i zemlju.','Po želji doradi poruku uz PRO Coacha.','Otvori WhatsApp ili kopiraj osobnu poveznicu.'],
        'visitor_choices'=>['official_forever_registration_and_package','message_owner_on_whatsapp'],
        'contact_evidence'=>fcc_business_landing_coach_evidence($uid,$evidence),
        'statistics'=>[],
        'coach_context_version'=>FCC_BUSINESS_LANDING_COACH_VERSION,
    ];
    foreach (['views','unique_views','outbound_forever_clicks','whatsapp_opens','contact_requests'] as $key) {
        if (isset($stats[$key]) && is_numeric($stats[$key]) && (int)$stats[$key]>=0) $existing['final_presentation']['statistics'][$key]=(int)$stats[$key];
    }
    $existing['capabilities']=array_replace($existing['capabilities']??[],['purchase_tracking'=>false,'enrollment_tracking'=>false,'whatsapp_delivery_tracking'=>false,'outbound_forever_click_tracking'=>true,'views_tracking'=>true]);
    $existing['guidance']='Poslovna prezentacija je završni pregled za samostalan početak u Foreveru ili osobno pitanje suradniku. Prijava na webinar nije dokaz gledanja. Nakon potvrđenog gledanja i dogovorenog nastavka s još zainteresiranom osobom predloži ovu prezentaciju. Može se poslati i izravno. Otvaranje službenog Forever postupka nije dokaz registracije ni kupnje. Dobrovoljan upit u FCC-u nije Forever registracija.';
    return $existing;
}
