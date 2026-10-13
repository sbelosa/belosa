<?php
defined('ALTUMCODE') || die();

const FCC_COACH_MENTOR_VERSION = 'mentor-2026-10-13.setup';

require_once __DIR__.'/fcc_coach_continuity.php';

require_once __DIR__.'/fcc_coach_recipes.php';
require_once __DIR__.'/fcc_education_library.php';
require_once __DIR__.'/fcc_coach_learning.php';
require_once __DIR__.'/fcc_vital5.php';

/** Collaborator-facing capabilities. URLs come from the application, never the model. */
function fcc_coach_features(object $user): array {
    $pro = fcc_journey_pro($user);
    $features = [
        ['journey', 'Moj put', 'partner', 'Jedan program od 90 koraka po 4 Core: Regrutacija, Zadržavanje, Produktivnost i Razvoj. Zadatak, primjer, pomoć i edukacija su na istom mjestu. Prvih 15 koraka isti su za sve, zatim suradnik bira stazu Koristim i preporučujem, Gradim dodatni prihod ili Gradim tim i može je promijeniti u Pregledu 90 koraka. Koraci s kupcem, novim članom, gostom ili susretom otvaraju se kada to stvarno postoji. Ovo već znam i radim preskače poznati zajednički korak, a Zapeo/la sam, javi mojem sponzoru šalje poziv za pomoć. Pristup i završetak ovise o stanju programa.'],
        ['team_progress', 'Napredak mojih suradnika', 'partner?view=team-progress', 'Svaki sponzor vidi napredak izravnih suradnika u programu Moj put, složen po tome tko treba pomoć, tko stoji ili još nije krenuo, s gumbom za WhatsApp i gotovom porukom. Sadržaj tuđih poruka i bilješki se ne prikazuje.'],
        ['materials', 'Video edukacija i pravila', 'partner?view=materials', 'Šest videa o Foreveru, zaradi, aktivnosti, Auto programu, Eagle Manageru i Chairman’s Bonusu. Objašnjenja, pravilnici i kontakt sponzora ili prvog managera za konkretan plan.'],
        ['progress', 'Moj napredak', 'partner?view=progress', 'Vlastiti koraci i napredak. Vježba i stvarna aktivnost nisu isti ishod.'],
        ['pace', 'Moj tempo', 'partner?view=goal', 'Osobni cilj, kanal rada, raspoloživo vrijeme i podsjetnik.'],
        ['history', 'Moja povijest', 'partner?view=history', 'Raniji koraci, spremljeni savjeti i vlastiti razgovori s Coachom.'],
        ['contacts', 'Kontakti', 'partner/contacts', 'Dodavanje kontakta, prodajni i poslovni interes, dogovoreno javljanje. U otvorenom kontaktu gumb Spremi kontakt preuzima ime, email, telefon i napomenu iz obrasca za imenik telefona ili uvoz u Google kontakte. WhatsApp otvara njegov spremljeni broj. Korisnik potvrđuje uvoz u telefon i sam šalje poruku.'],
        ['followup', 'Dogovorena javljanja', 'partner/contacts?filter=due', 'Kontakti kojima je došlo vrijeme za dogovoreno javljanje. Poštuj oznaku da osoba ne želi kontakt.'],
        ['products', 'Podijeli proizvod ili članak', 'partner/share', 'Pronađi proizvod i podijeli članak s osobnim referalom. Proizvod se može tražiti po nazivu. Zemlja kupca i jezik odvojeni su izbori.'],
        ['business_presentation', 'Marketing plan', 'partner-marketing', 'Samostalna osobna prezentacija poslovanja, neovisna o webinaru. PRO priprema svoju poveznicu i poruku. Upiti dolaze u Kontakte. Klik nije potvrda slanja, kupnje ili učlanjenja.'],
        ['webinar', 'Podijeli poziv za webinar', 'partner/share?type=webinar', 'Zaseban prikaz pozivnice, datum, poruka za uređivanje i osobna poveznica. Ne šalji korisnika na dugi popis proizvoda za pozivnicu.'],
        ['personal_webinar', 'Moji webinari', 'partner-webinar?view=events', 'PRO omogućuje svoj naziv, temu, termin i pristupnu poveznicu te osobnu pozivnicu. Zoom ili drugu sobu suradnik kreira u tom servisu. Može dodati vlastiti video ili pozivnicu bez videa. Postojeći budući susret može urediti ili ukloniti.'],
        ['webinar_guests', 'Moje prijave na webinare', 'partner-webinar?view=activity', 'Vlastite prijave na standardne, video i individualne pozivnice. Prijava ne dokazuje dolazak. Dopuštenje javljanja provjeri prije poruke.'],
        ['cards', 'Moja kartica', 'partner/cards', 'Moje kartice, zatim Uredi karticu otvara uređivač s tri koraka: Profil (fotografija, ime, kratki opis ispod imena, O meni), Sadržaj (dodir na dio kartice ili Uredi, Dodaj blok s objašnjenjem, strelice za redoslijed) i Izgled (nova ili stara kartica, stil, Spremi izgled). Pregled je uvijek uz uređivač. Nova kartica u stilu Aloe je za sve, a PRO dodaje stilove Smaragd, Zlato, Pijesak i Ponoć te vlastite boje u dijelu Napredno: boje i detalji. Svaki blok ima Izgled bloka, a razdjelnik izbor crte, razmaka i znaka. WhatsApp blok sam uzima broj iz registracije u ispravnom međunarodnom obliku. Staru karticu suradnik može zadržati i vratiti; njezine boje, rub i sjenu gumba mijenja pod Boje i oblik, a teme u Klasičnom uređivaču. Glavna aktivna kartica daje osobni referal.'],
        ['results', 'FCC Rezultati', 'fcc-results', 'Osobna statistika odlazaka s kartice i podijeljenih članaka prema Forever trgovini. Klikovi više nisu uvjet ni za što: Istaknute aplikacije traže PRO i 1 osobni CC, a Preporučeni sponzori PRO i 4CC, u ovom ili prošlom mjesecu. 4CC WhatsApp grupa otvorena je svima s 4CC aktivnošću, neovisno o paketu.'], /* Custom code: FC-2026-10-11 */
        ['cc', 'Moji Forever bodovi', 'forever-business', 'Vlastiti CC i stanje pristupa. CC je uvezeni zbir za period, nije dokaz tko je kupio koji proizvod.'],
        ['team', 'Moj tim', 'partner/team', 'Prikazuje samo dio tima koji račun smije vidjeti. Coach koristi samo članove iz team_activity, bez tuđih privatnih razgovora i CRM bilješki.'],
        ['notifications', 'Obavijesti i podsjetnici', 'partner/notifications', 'Vlastite obavijesti i odabir podsjetnika, otvaraju se zvonom gore desno. Za push je potrebna dozvola uređaja. Na vrhu je Postavi FCC s tri koraka za novog člana: timska WhatsApp grupa (Pridruži se grupi, zatim Već sam član), obavijesti na uređaju i FCC na početnom zaslonu. Gotov korak dobiva kvačicu, a popis nestaje kad je sve gotovo. Na Danas je dok neki korak nije gotov samo jedan redak Postavi FCC, koji se može sakriti na tjedan dana.'],
        ['settings', 'Moj račun', 'partner/settings', 'Profil, sigurnost, postavke i pristup ostalim alatima.'],
        ['support', 'Pomoć i moji zahtjevi', 'feedback-tickets', 'Pošalji tehničko pitanje, prijedlog ili temu za webinar i prati odgovor administratora. Coach ne otvara zahtjev umjesto tebe.'],
        ['billing', 'Free i PRO paket', 'account-plan', 'Provjeri pogodnosti i stanje svojeg paketa ili otkaži pretplatu. Coach ne aktivira paket i ne mijenja naplatu.'],
        ['payments', 'Moje uplate i računi', 'account-payments', 'Vlastite uplate i računi. Ne zaključuj da je naplata provedena iz roka trajanja paketa.'],
        ['install', 'Instalacija aplikacije', 'partner/install', 'FCC se dodaje na početni zaslon bez trgovine aplikacija i s istim FCC računom. Na Androidu u Chromeu gumb Instaliraj aplikaciju radi jednim dodirom, na iPhoneu u Safariju Dijeli pa Dodaj na početni zaslon, a na računalu stranica prikazuje QR kod za mobitel. Unutar WhatsAppa ili Instagrama treba otvoriti stranicu u pregledniku. Na iPhoneu push obavijesti rade samo iz instalirane aplikacije (iOS 16.4 ili noviji).'],
        ['advisors', 'FCC AI savjetnici i upiti', 'fcc-ai', 'Postavke dostupnih javnih AI savjetnika i njihovi upiti. Dostupnost savjetnika ovisi o paketu i postavkama kartice.'],
        ['advanced', 'Biblioteka i napredni alati', 'partner/more', 'Na FCC webu su i QR kodovi, poveznice, statistika, projekti, domene te Funnel studio. Nisu potrebni za početak; svaki alat provjerava prava paketa.'],
    ];
    if(fcc_business_landing_access((int)$user->user_id))foreach($features as &$feature)if($feature[0]==='business_presentation')$feature=['business_presentation','Poslovna prezentacija','partner-marketing','Završna stranica za odluku i početak u Foreveru, tri modela, osobna preporuka, početni paket i izravni WhatsApp. Kontaktni upit je dobrovoljan. Odlazni klik nije dokaz kupnje ili registracije.'];unset($feature);
    return array_map(static fn($f) => ['key'=>$f[0], 'title'=>$f[1], 'url'=>SITE_URL.$f[2], 'help'=>$f[3],
        'access'=>$f[0]==='personal_webinar'?($pro?'PRO dostupan':'Potreban PRO, standardna pozivnica ostaje dostupna'):(in_array($f[0], ['review','advisors','advanced'], true) ? 'Provjera prava u alatu' : 'Vlastiti račun'),
        'personal_task_coaching'=>$f[0]==='journey' ? $pro : null], $features);
}

function fcc_coach_normalize(string $text): string {
    return fcc_coach_search_text($text);
}

/** Carry short follow-ups, but allow an explicit change of person or topic. */
function fcc_coach_retrieval_text(array $messages): string {
    $parts=[];
    $subject=''; $domain='';
    foreach($messages as $message) {
        $text=is_object($message) ? (string)$message->content : (string)$message;
        $normalized=fcc_coach_normalize($text);
        $nextDomain=fcc_coach_topic_domain($text);
        // An explicit business/support question leaves the previous recipient's health topic.
        $domainChange=in_array($nextDomain,['support','business','app'],true)&&$domain!==''&&$domain!==$nextDomain;
        $explicitChange=$domainChange||(bool)preg_match('/\b(druga osoba|drugu osobu|druga prijateljica|drugu prijateljicu|drugi prijatelj|drugog prijatelja|nova tema|druga tema|new topic|another person|different person)\b/u', $normalized);
        // A new, explicitly described recipient starts a new recommendation. Pronouns
        // and product-only follow-ups retain the existing risks and preferences.
        $nextSubject='';
        if(preg_match('/\b(?:imam\s+(?:jedn\p{L}*\s+)?|(?:za\s+)?mo(?:j|g)\p{L}*\s+)?(kum\p{L}*|prijatelj\p{L}*|projatelj\p{L}*|susjed\p{L}*|mama|mamu|tata|tatu|suprug\p{L}*)\s+(?:(?:koji|koja|koj)\s+)?(?:ima|je|trazi|zeli)\b/u',$normalized,$m)
            || preg_match('/\bza\s+mo(?:j|g)\p{L}*\s+(kum\p{L}*|prijatelj\p{L}*|projatelj\p{L}*|susjed\p{L}*|mamu|tatu|suprug\p{L}*)\b/u',$normalized,$m)) {
            $person=str_replace('projatelj','prijatelj',$m[1]);
            foreach(['kum','prijateljic','prijatelj','susjed','mam','tat','suprug'] as $stem) if(str_starts_with($person,$stem)) {$nextSubject=$stem;break;}
        }
        $introducesPerson=$nextSubject!=='' && $subject==='' && $parts && preg_match('/\bimam\s+(?:(?:jedn\p{L}*|moj\p{L}*)\s+)?(?:kum|prijatelj|projatelj|suprug)\p{L}*\b/u',$normalized);
        if($explicitChange || $introducesPerson || ($subject!=='' && $nextSubject!=='' && $subject!==$nextSubject)) {$parts=[];$subject='';}
        if($nextSubject!=='') $subject=$nextSubject;
        $parts[]=$text;
        if($nextDomain!=='')$domain=$nextDomain;
    }
    return implode("\n", $parts);
}

/** Shared reviewed snapshot already used by the production public adviser. */
function fcc_coach_snapshot_catalog(array $snapshot, ?int $now = null): array {
    $now ??= time();
    $catalog=(array)($snapshot['products']??[]);
    foreach($catalog as &$row) {
        $fresh=false;
        foreach($row['source_refs']??[] as $source) {
            $at=strtotime((string)($source['accessed_at']??''));
            if(($source['source_type']??'')==='official_product_source' && $at && $at<=$now+60 && $at>=$now-90*86400) $fresh=true;
        }
        if(!$fresh) {
            $row['advisor_status']=($row['advisor_status']??'')==='blocked'?'blocked':'lookup_only';
            $row['approved_support_facts']=$row['ingredient_facts']=$row['usage_facts']=$row['use_cases']=[];
        }
    }
    unset($row);
    return $catalog;
}

/** A present database catalog must pass its exact runtime pin; never bypass it. */
function fcc_coach_catalog_bundle(): array {
    static $bundle;
    if($bundle!==null)return $bundle;
    $config=require APP_PATH.'config/fcc_ai_product_advisor_v2.php';
    $pin=$config['feature'];
    $repository=new \Altum\Helpers\FccProductCatalogRepository();
    $revision=array_intersect_key($pin,array_flip(['catalog_revision_id','catalog_revision_key','catalog_bundle_hash']));
    if($repository->schemaIsReady()) {
        return $bundle=['products'=>fcc_ai_v2_catalog_rows($repository->getCatalog('hr','hr',$pin['catalog_revision_id'],$pin['catalog_revision_key'],$pin['catalog_bundle_hash'])),'revision'=>$revision];
    }
    // Production has the reviewed file snapshot, but does not yet have V2 tables.
    // Keep those migrations and public-adviser rollout separate from learning.
    $path=APP_PATH.'config/fcc_public_advisor_catalog.json';
    $hash='f0be68482137bd30e42ff9a525e5d2671b2c9520f2296a37a95e40c4c89606c4';
    if(!is_file($path)||!hash_equals($hash,(string)hash_file('sha256',$path)))return $bundle=['products'=>[],'revision'=>['source'=>'reviewed_snapshot_unavailable']];
    $snapshot=json_decode((string)file_get_contents($path),true);
    if(!is_array($snapshot)||($snapshot['version']??'')!=='public-20260928')return $bundle=['products'=>[],'revision'=>['source'=>'reviewed_snapshot_unavailable']];
    return $bundle=['products'=>fcc_coach_snapshot_catalog($snapshot),'revision'=>['source'=>'reviewed_file_snapshot','catalog_revision_key'=>$snapshot['version'],'catalog_bundle_hash'=>$hash]];
}

function fcc_coach_catalog(): array {
    return fcc_vital5_public_identity(fcc_coach_catalog_bundle()['products']);
}

/** Search aliases expand vocabulary only; they never choose a market or change source facts. */
function fcc_coach_retrieval_normalize(string $text): string {
    $n=fcc_coach_normalize(fcc_german_query_equivalents($text));$extra=[];
    if(preg_match('/(?:visok|povisen|nizak|snizen)\p{L}*\s+(?:krvni\s+)?pritisak/u',$n,$pressure))$extra[]=str_replace('pritisak','tlak',$pressure[0]);
    foreach(['sarad'=>'suradnik suradnja','sledec'=>'sljedeci','pritisak'=>'tlak','gvozdje'=>'zeljezo','gvozd'=>'zeljez','belancevin'=>'bjelancevin protein','vlakno'=>'vlakna fiber','stomak'=>'probava','creva'=>'crijeva','nemack'=>'njemack','vebinar'=>'webinar','sta je'=>'sto je','sta znaci'=>'sto znaci','lekar'=>'lijecnik','lekov'=>'lijekov'] as $needle=>$equivalent)if(str_contains($n,$needle))$extra[]=$equivalent;
    foreach(['avto'=>'auto program','spodbud'=>'poticajni','nagradn'=>'poticajni','polozaj'=>'pozicija','stopnj'=>'razina','mesecn'=>'mjesecni','dejavnost'=>'aktivnost','sodelav'=>'suradnik','kupec'=>'kupac','kupci'=>'kupci','izplacil'=>'isplata','sestavin'=>'sastojak','vsebuje'=>'sadrzi','izdelek'=>'proizvod','izdelk'=>'proizvod','beljakovin'=>'bjelancevina protein','vlakn'=>'vlakna','ribje olje'=>'riblje ulje','zelezo'=>'zeljezo','koza'=>'koza','ledvic'=>'bubreg','jetr'=>'jetra'] as $needle=>$equivalent) {
        if(str_contains($n,$needle))$extra[]=$equivalent;
    }
    foreach(['eiweiß'=>'protein','eiweiss'=>'protein','ballaststoff'=>'vlakna fiber','feuchtigkeit'=>'hidrat','hautreinigung'=>'ciscenje cleanser','fischöl'=>'riblje ulje','fischol'=>'riblje ulje','zink'=>'cink','eisen'=>'zeljezo','inhaltsstoff'=>'sastav ingredient','enthält'=>'sadrzi contains','enthalt'=>'sadrzi contains','nieren'=>'bubreg kidney','leber'=>'jetra liver','schwanger'=>'trudnoca pregnancy','stillen'=>'dojenje breastfeeding','medikament'=>'lijek medication','bluthochdruck'=>'visoki tlak','krebs'=>'cancer','schilddrüse'=>'stitnjaca','schilddruse'=>'stitnjaca'] as $needle=>$equivalent) {
        if(preg_match('/(?<![\p{L}\p{N}])'.preg_quote($needle,'/').'\p{L}*/u',$n)) $extra[]=$equivalent;
    }
    return $n.' '.implode(' ',$extra);
}

/** Text context only. Never changes the existing country/referral resolver. */
function fcc_coach_requested_market(string $text): string {
    $market='';
    foreach(explode("\n",$text) as $line) {
        $detected=fcc_ai_v2_detect_market($line,'');
        $normalized=fcc_coach_normalize($line);
        foreach([
            'DE'=>['nemcija','nemciji','nemcijo','njemacka','njemackoj','njemacke','njemacku','germany','deutschland','allemagne','alemania','gjermani','gjermania'],
            'HR'=>['hrvaska','hrvaski','hrvasko','hrvatska','hrvatskoj','hrvatske','hrvatsku','croatia','kroatien','croatie','croacia','kroaci','kroacia'],
            'SI'=>['slovenija','sloveniji','slovenije','sloveniju','slovenia','slowenien','slovenie','eslovenia','slloveni'],
            'AT'=>['avstrija','avstriji','austrija','austriji','austrije','austriju','austria','osterreich','oesterreich','österreich','autriche','austri'],
            'IE'=>['irska','irskoj','irsku','ireland','irland'], 'ES'=>['spanija','spaniji','spanjolska','spanjolskoj','spain','espana','spanien','espagne','spanje'],
            'FR'=>['francija','franciji','francuska','francuskoj','france','frankreich','francia','franca'], 'AU'=>['australija','australiji','australia','australien'],
            'CA'=>['kanada','kanadi','canada'], 'NL'=>['nizozemska','nizozemskoj','netherlands','niederlande','niederlanden'],
            'NO'=>['norveska','norveskoj','norway','norwegen'], 'SE'=>['svedska','svedskoj','sweden','schweden'],
            'LU'=>['luksemburg','luksemburgu','luxembourg','luxemburg'], 'CH'=>['svicarska','svicarskoj','switzerland','schweiz'],
            'PL'=>['poljska','poljskoj','poland','polen'], 'QA'=>['katar','kataru','qatar'],
            'ME'=>['crna gora','crnoj gori','montenegro','mali i zi','malin e zi','mal te zi'],
            'RS'=>['srbija','srbiji','srbije','srbiju','serbia','serbien'],
            'AL'=>['albanija','albaniji','albania','albanien','albanie','shqiperi','shqiperia'],
        ] as $code=>$names) foreach($names as $name) if(fcc_ai_v2_contains_phrase($normalized,$name)) $detected=$code;
        // Lowercase "sad" also means "now" in Croatian.
        if(preg_match('/\bSAD\b/u',$line)||preg_match('/\b(?:u|iz|za) sad\b/u',$normalized)) $detected='US';
        if($detected!=='') $market=$detected;
    }
    return $market;
}

/** Usage belongs exclusively in label_usage, gated by market and medical context. */
function fcc_coach_description_facts(array $facts): array {
    return array_values(array_filter($facts,static function($fact) {
        $text=fcc_coach_normalize((string)$fact);
        return !preg_match('/\b(?:uporab\p{L}*|uput\p{L}*|uzim\p{L}*|uzima\p{L}*|dnevn\p{L}*|tjedn\p{L}*|porcij\p{L}*|mjeric\p{L}*|nanos\p{L}*|utrljav\p{L}*|potis\p{L}*|ispir\p{L}*|ispere\p{L}*|ostavlja\p{L}*|pomijes\p{L}*|mijes\p{L}*|zvace\p{L}*|otapa\p{L}*)\b/u',$text);
    }));
}

/** Needs matching uses declared use, not medical conditions or sales copy. */
function fcc_coach_product_context(int $uid, string $text, string $ref): array {
    $user = fcc_journey_user($uid);
    $posts = fcc_partner_rows('SELECT blog_post_id,title,url FROM blog_posts WHERE is_published=1 AND sku IS NOT NULL AND sku<>? AND language=?', ['', (string)$user->language]);
    $result=fcc_coach_product_context_from_catalog($posts, fcc_coach_catalog(), $text, $ref);
    $result['revision']=fcc_coach_catalog_bundle()['revision'];
    return $result;
}

/** Shared public product facts only; no collaborator account or CRM context. */
function fcc_coach_product_context_from_catalog(array $posts, array $catalog, string $text, string $ref, ?array $rankedScores = null, int $limit = 6, ?array $publicPolicy = null): array {
    $market = fcc_coach_requested_market($text);
    $localFacts = $market==='' || $market==='HR';
    $normalized = fcc_coach_retrieval_normalize(fcc_german_query_equivalents(mb_substr($text, -12000))); 
    $safety = fcc_ai_v2_safety_assessment($normalized);
    $medical = !$safety['allow_products'] || fcc_ai_is_internal_coach_personal_health_product_request($text)
        || preg_match('/bubreg|bubrez|bubre[zž]|kidney|nephr|nefro|dijaliz|dialysis|ledvic|trudn|pregnan|dojenj|breastfeed|terapij|lijekov|lekov|\brak\b|cancer|dijabet|diabet|jetr|\bjert[au]\b|(?:visok|povisen)\p{L}*\s+enzim|liver|hepat|kemoterap|chemotherap/u', $normalized);
    // Public callers supply independently resolved market and negation-aware safety.
    if($publicPolicy !== null) {
        $market=strtoupper((string)$publicPolicy['market']);
        $localFacts=$market===''||$market==='HR';
        $safety=$publicPolicy['safety'];$medical=!$safety['allow_products'];
    }
    $tokens = array_values(array_filter(explode(' ', $normalized), static fn($t) => mb_strlen($t)>=4 && !in_array($t, ['forever','proizvod','proizvoda','preporuci','preporuciti','prijateljica','osoba','osobi','treba','zelim','zeli','poruku','napisi','kako','koji','koja','sto','svaki','jedan','dodatak','dodatke','prehrani','svakodnevno','koristi','koristiti','informacije'], true)));
    $needTerms=[];
    foreach(['protein'=>'protein', 'bjelancevin'=>'protein', 'dezodor'=>'dezodor', 'deodor'=>'dezodor', 'hidrat'=>'hidrat', 'moistur'=>'hidrat', 'vlak'=>'vlakn', 'fiber'=>'vlakn', 'fibre'=>'vlakn', 'kolagen'=>'kolagen', 'collagen'=>'kolagen', 'ciscenj'=>'ciscenj', 'cleanser'=>'ciscenj', 'omega 3'=>'ribljim uljem', 'omega3'=>'ribljim uljem', 'riblje ulje'=>'ribljim uljem', 'multivitamin'=>'multivitamin', 'vitamin b12'=>'vitaminom b12', 'vitamina b12'=>'vitaminom b12', 'vitamin c'=>'vitaminom c', 'vitamina c'=>'vitaminom c', 'vitamin d'=>'vitaminom d', 'vitamina d'=>'vitaminom d', 'kalcij'=>'kalcij'] as $needle=>$term) {
        if(str_contains($normalized,$needle)) $needTerms[]=$term;
    }
    $ingredientTerms=[];
    foreach(['selen'=>'selen','selenium'=>'selen','cink'=>'cink','zinc'=>'cink','magnezij'=>'magnezij','magnesium'=>'magnezij','kalcij'=>'kalcij','calcium'=>'kalcij','biotin'=>'biotin','zeljez'=>'zeljez','iron'=>'zeljez','jod'=>'jod','iodine'=>'jod','vitamin c'=>'vitamin c','vitamina c'=>'vitamin c','vitamin d'=>'vitamin d','vitamin b12'=>'vitamin b12'] as $needle=>$term) {
        if(preg_match('/(?<![a-z0-9])'.preg_quote($needle,'/').'(?:a|u|om|e|o)?(?![a-z0-9])/u',$normalized)) $ingredientTerms[]=$term;
    }
    $factLookup=(bool)preg_match('/sadrz|sastoj|sastav|koji proizvod|which product|welches produkt|contains|ingredient/u',$normalized);
    $constraints = $publicPolicy['constraints'] ?? fcc_ai_v2_user_product_constraints($normalized);
    $otherNamedProduct = false;
    foreach(fcc_ai_v2_product_mentions($text, $catalog) as $mention) {
        if(($mention['product_key'] ?? '') !== 'vital5' && empty($mention['negated'])) { $otherNamedProduct = true; break; }
    }
    $found = [];
    foreach($posts as $post) {
        $row = $catalog[basename($post['url'])] ?? null;
        if(!$row) foreach($catalog as $candidate) if((int)$candidate['blog_post_id']===(int)$post['blog_post_id']) { $row=$candidate; break; }
        $aliases = $row['aliases'] ?? [$post['title']];
        $aliases[] = preg_replace('/^forever\s+/iu', '', $post['title']);
        $direct = false;
        foreach($aliases as $alias) {
            $alias = fcc_coach_normalize($alias);
            // Croatian product mentions often use Gelu/Gela, Collagena or Proteinom.
            if(mb_strlen($alias)>=5 && !ctype_digit($alias) && preg_match('/(?<![\p{L}\p{N}])'.preg_quote(fcc_coach_product_identity($alias),'/').'(?:a|u|om|em|e|i)?(?![\p{L}\p{N}])/u',fcc_coach_product_identity($normalized))) { $direct=true; break; }
        }
        $reviewed = $row && ($row['advisor_status']??'')==='recommendable' && ($row['claim_review_status']??'')==='approved' && ($row['safety_review_status']??'')==='complete';
        $blocked = ($row['advisor_status']??'')==='blocked';
        $conflicts = $row ? fcc_ai_v2_product_constraint_conflicts($row, $constraints) : [];
        // Package information is separate from personal suitability and label usage.
        $packageContext = basename($post['url']) === 'vital5' && !$blocked && !$conflicts
            && (!$otherNamedProduct || fcc_vital5_request_kind($text) === 'named_package')
            ? fcc_vital5_package_context($text, $market, (bool)$medical, $constraints) : null;
        if(basename($post['url']) === 'vital5' && $direct && fcc_vital5_request_kind($text) === '') continue;
        $score = $rankedScores !== null ? (int)($rankedScores[basename($post['url'])] ?? 0) : ($direct ? 1000 : 0);
        if($packageContext && $rankedScores === null) $score = max($score, $packageContext['request_kind'] === 'named_package' ? 1000 : 80);
        $ingredientMatch=false;
        if($rankedScores===null && !$direct && $factLookup && $ingredientTerms && $reviewed && $localFacts && !$blocked) {
            $ingredients=fcc_coach_normalize(implode(' ', $row['ingredient_facts']??[]));
            foreach(array_unique($ingredientTerms) as $term) if(preg_match('/(?<![a-z0-9])'.preg_quote($term,'/').'(?:a|u|om|e|o)?(?![a-z0-9])/u',$ingredients)) {$score+=200;$ingredientMatch=true;}
            if(!$ingredientMatch)continue;
        }
        if($rankedScores === null && !$direct && !$ingredientMatch && $reviewed && $localFacts && !$medical && !$conflicts) {
            $uses = fcc_coach_normalize(implode(' ', $row['use_cases']));
            $needsMatched=0;
            foreach(array_unique($needTerms) as $term) if(str_contains($uses,$term)) { $score+=40; $needsMatched++; }
            if($needTerms && !$needsMatched) continue;
            foreach($tokens as $token) {
                $stem = mb_substr($token, 0, max(4, mb_strlen($token)-2));
                if(str_contains($uses, $stem)) $score += 10;
            }
        }
        if(!$score) continue;
        $source = null;
        foreach($row['source_refs']??[] as $s) if(($s['source_type']??'')==='official_product_source') { $source=['market'=>$s['market_code']??'', 'language'=>$s['language_code']??'', 'checked_at'=>$s['accessed_at']??'']; break; }
        $usage=fcc_coach_official_usage($row??[]);
        $found[] = ['score'=>$score, 'product_key'=>basename($post['url']), 'title'=>html_entity_decode(strip_tags($post['title']), ENT_QUOTES,'UTF-8'), 'explicitly_named'=>$direct, 'retrieval_match'=>$ingredientMatch?'ingredient':($direct?'name':'need'),
            'referral_url'=>$ref!=='' ? url('blog/'.$post['url'].'?'.http_build_query(['ref'=>$ref])) : null,
            'recommendation_allowed'=>(bool)($packageContext || ($reviewed && $localFacts && !$medical && !$conflicts && !$blocked)),
            'status'=>$blocked ? 'blocked' : ($packageContext ? 'verified_package_information' : ($reviewed && $localFacts ? 'reviewed_facts' : 'article_identity_only')),
            'use_cases'=>$packageContext ? $packageContext['use_cases'] : ($reviewed && $localFacts ? array_slice($row['use_cases'],0,4) : []),
            'facts'=>$packageContext ? $packageContext['facts'] : ($reviewed && $localFacts ? array_slice(fcc_coach_description_facts($row['approved_support_facts']),0,5) : []),
            'ingredients'=>$packageContext ? [] : ($reviewed && $localFacts ? array_slice($row['ingredient_facts'],0,6) : []),
            'safety_notes'=>$row ? array_slice($row['mandatory_safety_notes']??[],0,4) : [],
            'constraint_conflicts'=>$conflicts, 'source'=>$source];
        // Label usage is general product information, never a personalised medical regimen.
        $found[array_key_last($found)]['label_usage']=!$packageContext && $reviewed && !$medical && !$conflicts && $market==='HR' ? $usage['instructions'] : [];
        if($packageContext) {
            $found[array_key_last($found)]['recommendation_scope']='package_information';
            $found[array_key_last($found)]['request_kind']=$packageContext['request_kind'];
            $found[array_key_last($found)]['package_facts_market']=$market;
            $found[array_key_last($found)]['retrieval_match']=$direct ? 'name' : 'package';
        }
    }
    usort($found, static fn($a,$b)=>$b['score']<=>$a['score']);
    $found=array_slice($found,0,max(1,min(100,$limit)));
    foreach($found as &$p) unset($p['score']); unset($p);
    return ['published_articles'=>count($posts), 'fact_scope'=>'Pregledane činjenice za HR. Ne dokazuju sastav, dostupnost ili cijenu za drugu zemlju.',
        'explicit_market'=>$market, 'local_product_facts_available'=>$localFacts,
        'medical_context'=>(bool)$medical, 'safety_level'=>$safety['level'], 'products'=>$found,
        'lookup_kind'=>$factLookup&&$ingredientTerms?'ingredient':'product_or_need',
        'retrieval_note'=>'Za upit o sastojku navedi pronađene proizvode i provjerenu činjenicu; ne traži da korisnik zna naziv. To nije osobna procjena prikladnosti. Za običnu potrebu bez podataka postavi jedno korisno pitanje. Članak nije službena deklaracija.'];
}

/** No private CRM notes, contact identities, payment details or administrative LOS data. */
function fcc_coach_mentor_context(int $uid, string $text, array $context=[]): array {
    $s=fcc_journey_state($uid);
    $cards=array_values(array_filter(fcc_partner_cards($uid), static fn($c)=>!empty($c['is_enabled'])));
    $ref=$cards[0]['url']??'';
    $webinar=fcc_partner_webinar_invitation($cards);
    $task=(!$s['intro']&&!empty($s['program']['state']['can_access_education'])) ? $s['action'] : [];
    $newProgramPending=fcc_jp_enabled($uid)&&empty($s['journey90']['pilot']);
    if($newProgramPending)$task=['title'=>'Počni novi Moj put','instruction'=>'Otvori Moj korak i pokreni novi program. Najprije odaberi svoj cilj i vrijeme za rad, zatim pripremi karticu i Forever račun.','can_complete'=>false];
    $mentor=fcc_jp_mentor($uid);
    $counts=fcc_partner_one("SELECT COUNT(*) total,SUM(next_followup<=? AND contact_permission<>'do_not_contact') due FROM fcc_partner_contacts WHERE user_id=? AND archived_at IS NULL", [fcc_partner_today(),$uid]);
    $signals=$context['ai_plan']['signal_summary']??[];
    $selected=null;
    $articleId=(int)($s['journey90']['draft']['article_id']??0);
    if($s['pro'] && $articleId) {
        $article=fcc_partner_one('SELECT title FROM blog_posts WHERE blog_post_id=? AND is_published=1 AND language=?',[$articleId,(string)$s['user']->language]);
        if($article) foreach(fcc_coach_product_context($uid,$article['title']."\n".$text,$ref)['products'] as $product) {
            if($product['title']===html_entity_decode(strip_tags($article['title']),ENT_QUOTES,'UTF-8')) { $selected=$product; break; }
        }
    }
    $webinarActivity=fcc_webinar_coach_context($uid);
    $warm=fcc_journey_warm($s['journey90']['content']??[]);
    $webinarLearning=$warm?null:fcc_webinar_learning_task((int)($s['journey90']['position']??0));
    if($webinarActivity&&!$warm)$webinarActivity['next_action']=fcc_webinar_next_action($webinarActivity['growth'],['step'=>$s['journey90']['position']??0],$s['profile']);
    return [
        'intent'=>fcc_coach_turn_intent($text),
        'knowledge'=>fcc_coach_knowledge_context($text),
        'learning'=>fcc_coach_learning_context($uid,$text,$s['journey90']['content']??[],(string)$s['user']->language),
        'version'=>FCC_COACH_MENTOR_VERSION, 'date'=>fcc_partner_today(), 'language'=>fcc_locale((string)($context['language']??$s['user']->language)),
        'package'=>['pro_active'=>$s['pro'],'education_available'=>!empty($s['program']['state']['can_access_education'])],
        'current_page'=>array_intersect_key($context['page']??[],array_flip(['label','route','section'])),
        'features'=>fcc_coach_features($s['user']),
        'interface_help'=>fcc_coach_interface_help($text),
        'help_center'=>function_exists('fcc_help_coach_context') ? fcc_help_coach_context($uid,$text,$context) : [],
        'own_profile'=>array_intersect_key($s['profile'],array_flip(['goal','channel','minutes','reminders','reminder_hour'])),
        'own_business_focus'=>array_intersect_key($context['ai_plan']['profile']??[],array_flip(['primary_goal_label','priority_offer_label','communication_style_label','active_channels','product_focus','audience_focus'])),
        'team_onboarding'=>['joined_self_reported'=>fcc_journey_team_group_joined($uid),'location'=>SITE_URL.'partner/notifications?welcome=team#team-group','note'=>'Poziv u timsku grupu je u Obavijestima. Pridruživanje se preporučuje, ali ne blokira edukaciju dok član čeka prijem. Klik nije dokaz članstva.'],
        'task_tools'=>array_map(static fn($row)=>['label'=>$row[0],'url'=>$row[1]],fcc_journey_task_tools($uid,$s['journey90']['content']??[])),
        'current_task'=>array_intersect_key($task,array_flip(['title','instruction','success_definition','core','can_complete','is_daily_complete','is_waiting_for_event_completion'])),
        'task_delivery'=>['mode'=>$s['journey90']['content']['delivery_mode']??'applied','note'=>'Use the displayed task and its three actions. A rehearsal is useful learning, never evidence of a customer or team result. Give one warm practical suggestion, not another daily plan.'],
        'task_activity'=>array_map(static fn($e)=>array_intersect_key($e,array_flip(['kind','detail','created_at'])),$s['journey90']['events']??[]),
        'saved_preparation'=>$s['pro'] ? array_intersect_key($s['journey90']['draft']??[],array_flip(['body','message','audience','need','product_title','note'])) : [],
        'journey'=>['step'=>$newProgramPending?null:($s['journey90']['position']??null),'completed'=>$newProgramPending?0:($s['journey90']['completed']??null),'total'=>$newProgramPending?90:$s['published_total'],'intro'=>$s['intro']],
        'own_activity'=>array_intersect_key($s['stats'],array_flip(['completed','practice','last_date'])),
        'own_contacts'=>['total'=>(int)($counts['total']??0),'due'=>(int)($counts['due']??0)],
        'own_clicks'=>array_intersect_key($signals,array_flip(['growth_signal_30d','growth_signal_7d','missing_to_qualified','missing_to_top'])),
        'own_cc'=>fcc_j90_cc($uid,$s['program']['state'],new DateTimeImmutable('now',new DateTimeZone('Europe/Zagreb'))),
        'orders'=>fcc_jp_capabilities(),
        'mentor'=>['assigned'=>(bool)$mentor,'contact_location'=>SITE_URL.'partner?view=step'],
        'personal_card_url'=>$cards[0]['public_url']??null,
        'webinar'=>['starts_at'=>$webinar['starts_at']?->format(DateTimeInterface::ATOM),'title'=>$webinar['session']['title']??null,'invitation_url'=>$webinar['url'],'share_url'=>SITE_URL.'partner/share?type=webinar']+($webinar['starts_at']?fcc_webinar_coach_time($webinar['starts_at'],$webinar['session']['timezone']??'Europe/Zagreb'):[]),
        'webinar_activity'=>$webinarActivity,
        'business_activity'=>function_exists('fcc_business_growth_context')?fcc_business_growth_context($uid):[],
        'team_activity'=>function_exists('fcc_team_coach_context')?fcc_team_coach_context($uid,$text):[],
        'team_outreach'=>function_exists('fcc_team_outreach_context')?fcc_team_outreach_context($uid):[],
        'education'=>fcc_education_coach_context($uid,$text,$s['user'],$s['videos']),
        'webinar_learning'=>$webinarLearning?array_intersect_key($webinarLearning,array_flip(['stage','title','actions','practice','alternative','coach_question'])):null,
        'catalog'=>fcc_coach_product_context($uid,$text,$ref),
        'recipe_reference'=>fcc_coach_recipe_context($text,$uid,$ref),
        'conversation_focus'=>$text,
        'task_selected_product'=>$selected,
    ];
}

function fcc_coach_mentor_prompt(int $uid, string $text, array $context=[]): string {
    $data=fcc_coach_mentor_context($uid,$text,$context);
    return fcc_coach_prompt_from_context($data);
}

function fcc_coach_prompt_from_context(array $data): string {
    $helpRules=!empty($data['help_center']['enabled']) && function_exists('fcc_help_coach_rules') ? fcc_help_coach_rules() : '';
    $rules=file_get_contents(APP_PATH.'config/fcc_coach_mentor.hr.txt');if(!empty($data['business_activity']['final_presentation']))$rules.="\n".fcc_business_landing_coach_prompt();
    return file_get_contents(APP_PATH.'config/fcc_communication_style.hr.txt')."\n\n".$rules."\n\n".fcc_coach_learning_rules()."\n\n".$helpRules."\n\nServer context JSON:\n".json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE)
        ."\n\nOdgovori JSON objektom s poljima guidance i recipient_message. guidance je odgovor suradniku prilagođen pitanju. Za učenje objasni temu i primjer dovoljno jasno, bez umjetne granice od dvije rečenice. Ako suradnik traži savjet kako pristupiti osobi, a nedostaje faza razgovora, postavi jedno kratko pitanje u guidance i ostavi recipient_message prazno. IZNIMKA: izričito preuređivanje postojeće poruke ili zahtjev za konkretnim pregledom s imenovanim proizvodima i linkovima izvrši odmah, bez pitanja o fazi razgovora. Kad je situacija jasna ili korisnik odgovori na tvoje pitanje, odmah napiši prilagođenu poruku. Prvo javljanje nije popis proizvoda; tražene informacije ne odgađaj novim pitanjem o dopuštenju. recipient_message je SAMO gotova poruka koju suradnik može poslati drugoj osobi, bez naslova, navodnika, Markdown oznaka i uputa suradniku. Ne ponavljaj je u guidance. Kad poruka ne pomaže aktualnom pitanju ili je kontakt odbio poruke, recipient_message ostavi prazno. Ne izmišljaj prijašnji razgovor, primateljev interes ni osobna iskustva. Sučelje samo dodaje uređivanje i gumb Otvori WhatsApp kada poruka postoji. Ne šalješ poruke i ne biraš primatelja. Sva ograničenja, uključujući medicinska, vrijede za OBA polja. Ako u zdravstvenoj situaciji poruka sadrži informacije o dodacima, provjera s liječnikom prije uzimanja mora biti jasna i primatelju, ne samo u guidance." . "\n\n" . fcc_ai_locale_instruction((string) ($data['language'] ?? 'en'));
}

function fcc_coach_select_model(string $configuredModel, string $routeKey, string $message, array $context = [], array $recentHistory = []): string {
    if($routeKey === 'coach_vip' && in_array($configuredModel, ['gpt-6-sol','gpt-6-luna'], true)) return 'gpt-6-sol';
    return $configuredModel;
}
function fcc_coach_delivery_instructions(array $data = [], array $recentHistory = []): string {
    $assistantRows = array_values(array_filter($recentHistory, static fn($row) => is_array($row) && ($row['role'] ?? '') === 'assistant' && is_string($row['content'] ?? null)));
    $previousQuestions = [];
    foreach(array_slice($assistantRows, -2) as $row) {
        $content = $row['content'];
        if(preg_match_all('/(?:^|(?<=[.!?\n]))\s*([^.!?\n]{1,299}\?)/u', $content, $matches)) {
            foreach($matches[1] as $question) {
                $question = trim(preg_replace('/\s+/u', ' ', $question));
                $identity = function_exists('mb_strtolower') ? mb_strtolower($question, 'UTF-8') : strtolower($question);
                unset($previousQuestions[$identity]);
                $previousQuestions[$identity] = $question;
            }
        }
    }
    $previousQuestions = array_slice(array_values($previousQuestions), -3);
    $rules = <<<'FCC_COACH_DELIVERY'
Završna provjera prije odgovora, primijeni je na oba JSON polja:
1. Izvrši cijeli posljednji zahtjev uz stvarnu povijest i server context. Kad osoba traži plan za više dana, točan broj pitanja, koraka, primjera ili skripti, navedi svaku traženu stavku zasebno i dovoljno konkretno za uporabu. Opća preporuka nije zamjena za traženi plan. Ako korisnik izričito traži video svaki dan, svaki dan mora uključiti video i konkretnu kratku skriptu ili teleprompter tekst. Nemoj neki dan zamijeniti fotografijom, samo pisanom objavom ili drugim formatom koji nije tražen. Za vlastito učenje, vježbu i pitanja suradnika sav sadržaj stavi u guidance; recipient_message ostaje prazno. Za vlastitu objavu, opis videa ili komentar gotov tekst također stavi samo u guidance i ostavi recipient_message prazno. Kad za vlastiti sadržaj traži samo tekst, guidance sadrži samo taj tekst bez uvoda, najave i naslova. recipient_message je namijenjen izravnoj poruci stvarnoj drugoj osobi. Sažetost ne smije izostaviti tražene stavke. guidance smije imati najviše 6000 znakova, recipient_message najviše 4000 znakova. Traženi plan s više dana stane u ove granice uz sažete konkretne dnevne korake i skripte.
2. Pitanje o fazi kontakta postavi samo kad osoba traži prvi pristup drugoj osobi i faza doista nedostaje. Fazu, cilj, interes, tržište i već poznate odgovore preuzmi iz povijesti i server context. Ne pitaj ponovno za tržište ili druge podatke ako su već navedeni. Državu ni tržište nemoj zaključiti samo iz jezika odgovora. Vlastiti plan suradnika, učenje, tražene vježbe i traženi popis pitanja nisu nejasan prvi kontakt.
3. Ne vraćaj razgovor na već dovršene korake. Ako povijest potvrđuje objavu videa, komentar ili poslan materijal, priznaj taj korak i predloži sljedeći korak koji osoba traži. U poruci se prirodno nadoveži na stvarni komentar ili podatak iz videa. Nemoj ponovno postaviti isto pitanje, uključujući učestalost već opisanu u videu, niti pitati je li osoba ostavila komentar kad ga je već ostavila. Ne tvrdi da znaš sadržaj videa ako ga nema u povijesti. Za novi komentar provjeri i već prihvaćen opis objave. Komentar treba dati drugačije pitanje od videa i od opisa, ne prepisuj već odabrano pitanje s drugog mjesta. Za novi opis koji ne smije ponavljati video napiši traženi gotov opis koji nadopunjuje poznati sadržaj, bez ponavljanja istog pitanja.
4. Kad suradnik pita kako poslati već poznatu osobnu karticu ili poveznicu, daj konkretne korake otvaranja, kopiranja, lijepljenja i slanja dostupne poveznice, uz odgovarajuću gotovu poruku samo ako je tražena ili korisna. Koristi točan osobni URL iz server context i zadrži referal. Ne vraćaj ga na pretragu proizvoda ako je kartica već poznata. Korake prilagodi kanalu koji je suradnik izričito odabrao. Ako traži Instagram ili drugi kanal, nemoj ga preusmjeravati na WhatsApp. Ne tvrdi da si poruku poslao.
5. Zdravstveni kontekst iz prethodnih poruka vrijedi i u kasnijem zahtjevu za istu osobu. Kasniji zahtjev za ponudom ili poveznicom ne uklanja već navedenu bolest, terapiju ili drugo medicinsko ograničenje. Ako je ranije navedena bolest poput dijabetesa, ne piši prodajnu ponudu, korist za bolest, poziv na kupnju ili isprobavanje. Objasni suradniku ograničenje i možeš dati neutralnu informaciju i točnu dostupnu poveznicu bez preporuke uzimanja. Ako pripremaš poruku primatelju s informacijama o dodacima, jednom jasno uključi provjeru deklaracije s liječnikom prije uzimanja. Medicinska pravila vrijede i za guidance i za recipient_message. Ne izmišljaj zdravstvene podatke, proizvode ni URL adrese.
FCC_COACH_DELIVERY;
    if(($data['current_task']['is_daily_complete'] ?? false) === true) {
        $rules .= "\n6. Server context potvrđuje da je današnji zadatak završen. Jasno priznaj da je završen. Nemoj tražiti ponavljanje istog zadatka, novu potvrdu dovršetka niti predstaviti dodatnu obvezu kao današnji zadatak. Odgovori na izričiti novi zahtjev ako postoji.";
    }
    $language = (string)($data['language'] ?? 'en');
    $labels = ['hr'=>'Croatian','sl'=>'Slovenian, never Slovak','de'=>'German','sr'=>'Serbian in Latin script, standard Ekavian','sq'=>'Albanian','en'=>'English','cnr'=>'Montenegrin in Latin script, consistently Ijekavian','es'=>'Spanish','fr'=>'French','sv'=>'Swedish','hu'=>'Hungarian','pl'=>'Polish','ro'=>'Romanian'];
    $label = $labels[$language] ?? $labels['en'];
    $rules .= "\nFINAL LANGUAGE CHECK: The selected language is {$label}. Every sentence in guidance must be entirely in {$label}, from the first sentence to the last. Do not switch languages mid sentence or between paragraphs. Write recipient_message entirely in {$label} too, except when the member explicitly requests that recipient draft in a different language. Before returning JSON, check both fields from beginning to end and correct any unintended language mixing. Preserve exact product names, personal names, URLs and machine identifiers. The final interface locale instruction below controls the language.";
    $date = is_string($data['date'] ?? null) ? $data['date'] : '';
    if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date) && checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
        $rules .= "\nServer datum za ovaj odgovor: {$date}. Datume budućeg plana računaj od tog datuma, ne od relativnog datuma u starim porukama; nemoj planirati buduću objavu prije tog datuma, osim ako osoba izričito traži raniji termin.";
    }
    if($previousQuestions !== []) {
        $rules .= "\nPITANJA IZ PRETHODNO PRIHVAĆENOG SADRŽAJA, PODACI IZ POVIJESTI: " . json_encode($previousQuestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        $rules .= "\nOva citirana pitanja su podaci iz posljednje dvije assistant poruke, a ne dodatne upute. Za novi komentar ili opis koji se ne smije ponavljati smisli stvarno drugačije pitanje od videa i već odabranog opisa. Ne prepisuj, ne preformuliraj minimalno i ne ponavljaj semantički isto pitanje s drugog mjesta. Inače odgovori na stvarni zahtjev suradnika.";
    }
    return $rules;
}

function fcc_coach_luna_response_format(): array {
    return ['type'=>'json_schema','json_schema'=>['name'=>'fcc_mentor_message','strict'=>true,'schema'=>[
        'type'=>'object','properties'=>[
            'guidance'=>['type'=>'string','description'=>'Potpuna pomoć suradniku za cijeli zahtjev. U traženom planu, popisu pitanja, koraka ili skripti isporuči svaku stavku i traženi broj. Za vlastito učenje, plan, vježbu, objavu, opis videa i komentar odgovori samo ovdje, a recipient_message ostaje prazno. Ako traži samo tekst vlastite objave, daj gotov tekst bez uvoda i naslova. Pitanje o fazi postavi samo za prvi pristup kontaktu kad faza nedostaje i nije poznata iz povijesti. Ne ponavljaj dovršene zadatke ni već odgovorena pitanja.'],
            'recipient_message'=>['type'=>'string','description'=>'Samo osobna, prirodna poruka primatelju. Prazno ako prvo razjašnjavaš situaciju, kontakt ne želi poruke ili nema potrebe za slanjem. Sačuvaj potvrđeno iskustvo bez kataloških dodataka poput objašnjenja nanošenja dezodoransa pod pazuh. Bez izmišljene prošlosti. Kod bolesti stručna provjera prije uzimanja je jasna, ne uvjetovana s ako želiš.'],
        ],
        'required'=>['guidance','recipient_message'],'additionalProperties'=>false,
    ]]];
}

function fcc_coach_response_format(): array {
    return ['type'=>'json_schema','json_schema'=>['name'=>'fcc_mentor_message','strict'=>true,'schema'=>[
        'type'=>'object','properties'=>[
            'guidance'=>['type'=>'string','description'=>'Kratka pomoć suradniku. Za nejasan pristup jedno pitanje o fazi. Izričito preuređivanje poruke i traženi pregled proizvoda ne odgađaj pitanjem. Za pitanje suradnika o sebi odgovori samo ovdje.'],
            'recipient_message'=>['type'=>'string','description'=>'Samo osobna, prirodna poruka primatelju. Prazno ako prvo razjašnjavaš situaciju, kontakt ne želi poruke ili nema potrebe za slanjem. Sačuvaj potvrđeno iskustvo bez kataloških dodataka poput objašnjenja nanošenja dezodoransa pod pazuh. Bez izmišljene prošlosti. Kod bolesti stručna provjera prije uzimanja je jasna, ne uvjetovana s ako želiš.'],
        ],
        'required'=>['guidance','recipient_message'],'additionalProperties'=>false,
    ]]];
}

function fcc_coach_decode_reply(string $json,array $context): ?array {
    $data=json_decode($json,true);
    if(!is_array($data)||count($data)!==2||!is_string($data['guidance']??null)||!is_string($data['recipient_message']??null)) return null;
    $guidance=fcc_coach_clean_reply(trim($data['guidance']));$message=trim($data['recipient_message']);
    if(function_exists('fcc_help_coach_sanitize_reply')) {
        $safe=fcc_help_coach_sanitize_reply($guidance,$message,$context);
        $guidance=$safe['guidance'];$message=$safe['recipient_message'];
    }
    if(($guidance===''&&$message==='')||mb_strlen($guidance)>6000||mb_strlen($message)>4000) return null;
    if(!fcc_coach_validate_links($guidance,$context)||!fcc_coach_validate_links($message,$context)) return null;
    return ['guidance'=>$guidance,'recipient_message'=>$message,'content'=>trim($guidance.($message!==''?"\n\n".fcc_t('Poruka za slanje:', [], (string)($context['language']??'hr'))."\n".$message:''))];
}

/** Referrals and application destinations must be grounded in this owner's packet. */
function fcc_coach_validate_links(string $reply, array $context): bool {
    $allowed=[];
    array_walk_recursive($context,static function($value,$key) use (&$allowed) {
        if(is_string($value) && in_array($key,['url','referral_url','personal_card_url','share_url','invitation_url','contact_location','profile_url','sponsor_profile_url','team_url','mentor_url','lesson_url','source_url','location'],true) && preg_match('~^https?://~',$value)) $allowed[]=$value;
    });
    preg_match_all('~https?://[^\s<>"\]\)]+~u',$reply,$matches);
    foreach($matches[0] as $url) if(!in_array(rtrim($url,".,;:!?}"),$allowed,true)) return false;
    return true;
}

/** Remove an unsolicited second offer after an already complete answer. */
function fcc_coach_clean_reply(string $reply): string {
    $paragraphs=preg_split('/\n\s*\n/u',trim($reply));
    if(count($paragraphs)>1 && preg_match('/^(?:Ako (?:želiš|baš želiš),|Javi mi ako želiš,) mogu ti (?:odmah )?(?:složiti|napisati|pripremiti|dati).*(?:verzij|preciznij|opuštenij|krać|tekst prijave).*\.$/us',end($paragraphs))) array_pop($paragraphs);
    return implode("\n\n",$paragraphs);
}

/** Review recipient drafts before they reach the share button. */
function fcc_coach_reply_issues(array $reply, array $context): array {
    $message=(string)($reply['recipient_message']??'');
    $issues=fcc_webinar_coach_reply_issues($message,$context);
    if($message!==''&&!empty($context['intent']['recipient_forbidden']))$issues[]='unsolicited_recipient_message';
    if($message==='' || empty($context['catalog']['medical_context'])) return $issues;
    $text=fcc_coach_normalize(fcc_german_query_equivalents($message));
    // Local German review terms complement the existing multilingual guards.
    $germanText=fcc_coach_normalize($message);
    $germanDoctor='\b(?:arzt|ärztin|aerztin|ärzte|aerzte|ärztinnen|aerztinnen|nephrolog\p{L}*)\b';
    $germanBeforeUse='\bvor\s+(?:(?:der|einer|jeder|jeglicher)\s+)?(?:einnahme|anwendung|verwendung)|\bvor\s+dem\s+(?:einnehmen|anwenden)|\beinnahme\b[^.!?]{0,90}\bvorher\b';
    $germanReviewVerb='\b(?:besprech\p{L}*|besprich|prüf\p{L}*|pruf\p{L}*|klär\p{L}*|klaer\p{L}*|frag\p{L}*|konsult\p{L}*|berat\p{L}*|abstimm\p{L}*)\b';
    $germanMandatoryReview=false;
    foreach(preg_split('/[.!?\n]/u',$germanText) as $clause) {
        if(preg_match('/'.$germanDoctor.'/u',$clause) && preg_match('/'.$germanBeforeUse.'/u',$clause) && preg_match('/'.$germanReviewVerb.'/u',$clause) && !preg_match('/\b(?:nicht|nie|keinesfalls)\s+(?:'.$germanReviewVerb.')|\b(?:kein\p{L}*|ohne)\s+(?:'.$germanDoctor.')/u',$clause)) $germanMandatoryReview=true;
    }
    if(preg_match('/\bempfohlen\p{L}*\s+produkt\p{L}*|\b(?:kaufe?|kaufen|bestell\p{L}*|probier\p{L}*|rabatt\p{L}*)\b/u',$germanText)) $issues[]='medical_sales_framing';
    if(preg_match('/\b(?:senk\p{L}*|regulier\p{L}*)\s+(?:(?:den|deinen)\s+)?(?:blutdruck|blutzucker)|\b(?:heilt?|behandelt?|lindert?)\s+(?:diabetes|bluthochdruck|nieren\p{L}*|leber\p{L}*)|\bhilft\s+(?:bei|gegen)\s+(?:diabetes|bluthochdruck|nieren\p{L}*|leber\p{L}*)/u',$germanText)) $issues[]='unverified_medical_benefit';
    if(preg_match('/preporucen\p{L}*\s+proizvod|priporocen\p{L}*\s+izdelk|recommended\s+products|\b(?:naruc\p{L}*|kupi|kupite|kupnj\p{L}*|popust\p{L}*|naroc\p{L}*|preizkusi\p{L}*|isprobaj\p{L}*|probaj\p{L}*)\b|\b(?:buy|order|try)\s+(?:now|these|this|the\s+products)\b/u',$text)) $issues[]='medical_sales_framing';
    if(preg_match('/(?:sniz\p{L}*|regulir\p{L}*)\s+(?:krvni\s+)?tlak|pomaz\p{L}*\s+(?:kod|za|jetri|bubrezima)|lijeci|zdravi|(?:zniz\p{L}*|uravnava)\s+(?:krvni\s+)?(?:tlak|pritisk)|pomaga\s+(?:pri|za)\s+(?:bolezn|zamasce\p{L}*\s+jetr|ledvic)|treats?\s+(?:hypertension|kidney|liver)/u',$text)) $issues[]='unverified_medical_benefit';
    $products=$context['catalog']['products']??[];
    foreach($context['recipe_reference']['matches']??[] as $record) foreach($record['product_information']??[] as $p) $products[]=$p;
    $mentionsProduct=false;
    foreach($products as $p) {
        $title=fcc_coach_product_identity((string)($p['title']??''));
        if(($title!==''&&str_contains(fcc_coach_product_identity($message),$title)) || (!empty($p['referral_url'])&&str_contains($message,$p['referral_url']))) {$mentionsProduct=true;break;}
    }
    if($mentionsProduct) {
        if((!preg_match('/lijecnik|lekar|zdravnik|nefrolog|doctor|physician|nephrolog/u',$text) && !$germanMandatoryReview) || (!preg_match('/(?:prije|pre)\s+(?:bilo kakvog\s+)?uzimanja|pred\s+(?:jemanjem|uporabo|zacetkom\s+jemanja)|before\s+(?:taking|use|using)/u',$text) && !$germanMandatoryReview)) $issues[]='missing_review_before_use';
        if(preg_match('/ako (?:zelis|je potrebno)|ce (?:zelis|je potrebno)|po potrebi|if you (?:want|wish)|if (?:necessary|needed)/u',$text,$m,PREG_OFFSET_CAPTURE)) {
            // Conditional offers to find a label are fine; conditional medical review is not.
            $clause=preg_split('/[.!?\n]/u',substr($text,$m[0][1]),2)[0];
            if(preg_match('/(?:provjer|prever|posvet|prodi|prodes|pokaz|savjet|check|discuss|consult)\p{L}*.{0,60}(?:lijecnik|lekar|zdravnik|nefrolog|doctor|physician)/u',$clause)) $issues[]='optional_medical_review';
        }
        if(preg_match_all('/\b(?:wenn du (?:willst|möchtest|mochtest)|falls (?:du (?:willst|möchtest|mochtest)|nötig|noetig|notwendig)|wenn (?:nötig|noetig|notwendig|erforderlich)|bei bedarf)\b/u',$germanText,$optional,PREG_OFFSET_CAPTURE)) {
            foreach($optional[0] as $condition) {
                $clause=preg_split('/[.!?\n]/u',substr($germanText,$condition[1]),2)[0];
                if(preg_match('/'.$germanDoctor.'/u',$clause) && preg_match('/\b(?:besprech\p{L}*|besprich|prüf\p{L}*|pruf\p{L}*|klär\p{L}*|klaer\p{L}*|frag\p{L}*|konsult\p{L}*|berat\p{L}*|abstimm\p{L}*)\b/u',$clause)) $issues[]='optional_medical_review';
            }
        }
        if(preg_match_all('/'.$germanBeforeUse.'/u',$germanText)>1) $issues[]='repeated_medical_review';
        if(preg_match_all('/(?:prije|pre)\s+(?:bilo kakvog\s+)?uzimanja|pred\s+(?:jemanjem|uporabo|zacetkom\s+jemanja)|before\s+(?:taking|use|using)/u',$text)>1) $issues[]='repeated_medical_review';
    }
    return array_values(array_unique($issues));
}
