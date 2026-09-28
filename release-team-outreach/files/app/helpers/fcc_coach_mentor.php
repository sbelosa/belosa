<?php
defined('ALTUMCODE') || die();

const FCC_COACH_MENTOR_VERSION = 'mentor-2026-09-28.team';

require_once __DIR__.'/fcc_coach_recipes.php';

/** Collaborator-facing capabilities. URLs come from the application, never the model. */
function fcc_coach_features(object $user): array {
    $pro = fcc_journey_pro($user);
    $features = [
        ['journey', 'Moj put', 'partner', 'Jedan program od 90 koraka po 4 Core: Regrutacija, Zadržavanje, Produktivnost i Razvoj. Zadatak, primjer, pomoć i edukacija su na istom mjestu. Pristup i završetak ovise o stanju programa.'],
        ['materials', 'Materijali uz moj korak', 'partner?view=materials', 'Video i objašnjenje za aktualni korak.'],
        ['progress', 'Moj napredak', 'partner?view=progress', 'Vlastiti koraci i napredak. Vježba i stvarna aktivnost nisu isti ishod.'],
        ['pace', 'Moj tempo', 'partner?view=goal', 'Osobni cilj, kanal rada, raspoloživo vrijeme, podsjetnik i pauza.'],
        ['history', 'Moja povijest', 'partner?view=history', 'Raniji koraci, spremljeni savjeti i vlastiti razgovori s Coachom.'],
        ['contacts', 'Kontakti', 'partner/contacts', 'Dodavanje kontakta, prodajni i poslovni interes, dogovoreno javljanje. U otvorenom kontaktu gumb Spremi kontakt preuzima ime, email, telefon i napomenu iz obrasca za imenik telefona ili uvoz u Google kontakte. WhatsApp otvara njegov spremljeni broj. Korisnik potvrđuje uvoz u telefon i sam šalje poruku.'],
        ['followup', 'Dogovorena javljanja', 'partner/contacts?filter=due', 'Kontakti kojima je došlo vrijeme za dogovoreno javljanje. Poštuj oznaku da osoba ne želi kontakt.'],
        ['products', 'Podijeli proizvod ili članak', 'partner/share', 'Pronađi proizvod i podijeli članak s osobnim referalom. Proizvod se može tražiti po nazivu. Zemlja kupca i jezik odvojeni su izbori.'],
        ['business_presentation', 'Marketing plan', 'partner-marketing', 'Samostalna osobna prezentacija poslovanja, neovisna o webinaru. PRO priprema svoju poveznicu i poruku. Upiti dolaze u Kontakte. Klik nije potvrda slanja, kupnje ili učlanjenja.'],
        ['webinar', 'Podijeli poziv za webinar', 'partner/share?type=webinar', 'Zaseban prikaz pozivnice, datum, poruka za uređivanje i osobna poveznica. Ne šalji korisnika na dugi popis proizvoda za pozivnicu.'],
        ['personal_webinar', 'Moji webinari', 'partner-webinar?view=events', 'PRO omogućuje svoj naziv, temu, termin i pristupnu poveznicu te osobnu pozivnicu. Zoom ili drugu sobu suradnik kreira u tom servisu. Može dodati vlastiti video ili pozivnicu bez videa. Postojeći budući susret može urediti ili ukloniti.'],
        ['webinar_guests', 'Moje prijave na webinare', 'partner-webinar?view=activity', 'Vlastite prijave na standardne, video i individualne pozivnice. Prijava ne dokazuje dolazak. Dopuštenje javljanja provjeri prije poruke.'],
        ['cards', 'Moja kartica', 'partner/cards', 'Uredi svoju FCC karticu, blokove, poveznice i kontakt. Glavna aktivna kartica daje osobni referal.'],
        ['results', 'FCC Rezultati', 'fcc-results', 'Vlastiti kvalificirani odlasci s kartice ili podijeljenog članka prema Forever trgovini u zadnjih 30 dana. Posjet članku nije takav klik. 15 je prag za istaknute aplikacije; 50 za preporučene sponzore i javni profil uz uvjete javne objave. Nema jamstva Google ili AI indeksacije.'],
        ['cc', 'Moji Forever bodovi', 'forever-business', 'Vlastiti CC i stanje pristupa. CC je uvezeni zbir za period, nije dokaz tko je kupio koji proizvod.'],
        ['team', 'Moj tim', 'partner/team', 'Prikazuje samo dio tima koji račun smije vidjeti. Coach u ovom razgovoru nema osobne podatke članova tima.'],
        ['notifications', 'Obavijesti i podsjetnici', 'partner/notifications', 'Vlastite obavijesti i odabir podsjetnika. Za push je potrebna dozvola uređaja.'],
        ['settings', 'Moj račun', 'partner/settings', 'Profil, sigurnost, postavke i pristup ostalim alatima.'],
        ['support', 'Pomoć i moji zahtjevi', 'feedback-tickets', 'Pošalji tehničko pitanje, prijedlog ili temu za webinar i prati odgovor administratora. Coach ne otvara zahtjev umjesto tebe.'],
        ['billing', 'Free i PRO paket', 'account-plan', 'Provjeri pogodnosti i stanje svojeg paketa ili otkaži pretplatu. Coach ne aktivira paket i ne mijenja naplatu.'],
        ['payments', 'Moje uplate i računi', 'account-payments', 'Vlastite uplate i računi. Ne zaključuj da je naplata provedena iz roka trajanja paketa.'],
        ['install', 'Instalacija aplikacije', 'partner/install', 'Upute za instalaciju na iPhone i Android. Koristi postojeći FCC račun.'],
        ['advisors', 'FCC AI savjetnici i upiti', 'fcc-ai', 'Postavke dostupnih javnih AI savjetnika i njihovi upiti. Dostupnost savjetnika ovisi o paketu i postavkama kartice.'],
        ['review', 'AI pregled kartice', 'ai-app-review', 'Dodatni pregled kartice kad je uključen i dostupan. Za osobne poruke uz zadatak koristi Coach, bez stvaranja dodatnih planova.'],
        ['advanced', 'Biblioteka i napredni alati', 'partner/more', 'Na FCC webu su i QR kodovi, poveznice, statistika, projekti, domene te Funnel studio. Nisu potrebni za početak; svaki alat provjerava prava paketa.'],
    ];
    return array_map(static fn($f) => ['key'=>$f[0], 'title'=>$f[1], 'url'=>SITE_URL.$f[2], 'help'=>$f[3],
        'access'=>$f[0]==='personal_webinar'?($pro?'PRO dostupan':'Potreban PRO, standardna pozivnica ostaje dostupna'):(in_array($f[0], ['review','advisors','advanced'], true) ? 'Provjera prava u alatu' : 'Vlastiti račun'),
        'personal_task_coaching'=>$f[0]==='journey' ? $pro : null], $features);
}

function fcc_coach_normalize(string $text): string {
    return fcc_ai_v2_normalize_text(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
}

/** Carry short follow-ups, but allow an explicit change of person or topic. */
function fcc_coach_retrieval_text(array $messages): string {
    $parts=[];
    $subject='';
    foreach($messages as $message) {
        $text=is_object($message) ? (string)$message->content : (string)$message;
        $normalized=fcc_coach_normalize($text);
        $explicitChange=(bool)preg_match('/\b(druga osoba|drugu osobu|druga prijateljica|drugu prijateljicu|drugi prijatelj|drugog prijatelja|nova tema|druga tema|new topic|another person|different person)\b/u', $normalized);
        // A new, explicitly described recipient starts a new recommendation. Pronouns
        // and product-only follow-ups retain the existing risks and preferences.
        $nextSubject='';
        if(preg_match('/\b(?:imam\s+(?:jedn\p{L}*\s+)?|(?:za\s+)?mo(?:j|g)\p{L}*\s+)?(kum\p{L}*|prijatelj\p{L}*|projatelj\p{L}*|mama|mamu|tata|tatu|suprug\p{L}*)\s+(?:(?:koji|koja|koj)\s+)?(?:ima|je|trazi|zeli)\b/u',$normalized,$m)
            || preg_match('/\bza\s+mo(?:j|g)\p{L}*\s+(kum\p{L}*|prijatelj\p{L}*|projatelj\p{L}*|mamu|tatu|suprug\p{L}*)\b/u',$normalized,$m)) {
            $person=str_replace('projatelj','prijatelj',$m[1]);
            foreach(['kum','prijateljic','prijatelj','mam','tat','suprug'] as $stem) if(str_starts_with($person,$stem)) {$nextSubject=$stem;break;}
        }
        $introducesPerson=$nextSubject!=='' && $subject==='' && $parts && preg_match('/\bimam\s+(?:(?:jedn\p{L}*|moj\p{L}*)\s+)?(?:kum|prijatelj|projatelj|suprug)\p{L}*\b/u',$normalized);
        if($explicitChange || $introducesPerson || ($subject!=='' && $nextSubject!=='' && $subject!==$nextSubject)) {$parts=[];$subject='';}
        if($nextSubject!=='') $subject=$nextSubject;
        $parts[]=$text;
    }
    return implode("\n", $parts);
}

/** Only active, immutable, validated catalog facts are allowed into product advice. */
function fcc_coach_catalog(): array {
    static $catalog;
    if($catalog !== null) return $catalog;
    $config = require APP_PATH.'config/fcc_ai_product_advisor_v2.php';
    $pin = $config['feature'];
    $repository = new \Altum\Helpers\FccProductCatalogRepository();
    return $catalog = fcc_ai_v2_catalog_rows($repository->getCatalog('hr', 'hr', $pin['catalog_revision_id'], $pin['catalog_revision_key'], $pin['catalog_bundle_hash']));
}

/** Text context only. Never changes the existing country/referral resolver. */
function fcc_coach_requested_market(string $text): string {
    $market='';
    foreach(explode("\n",$text) as $line) {
        $detected=fcc_ai_v2_detect_market($line,'');
        $normalized=fcc_coach_normalize($line);
        foreach([
            'IE'=>['irska','irskoj','irsku','ireland'], 'ES'=>['spanjolska','spanjolskoj','spain','espana'],
            'FR'=>['francuska','francuskoj','france'], 'AU'=>['australija','australiji','australia'],
            'CA'=>['kanada','kanadi','canada'], 'NL'=>['nizozemska','nizozemskoj','netherlands'],
            'NO'=>['norveska','norveskoj','norway'], 'SE'=>['svedska','svedskoj','sweden'],
            'LU'=>['luksemburg','luksemburgu','luxembourg'], 'CH'=>['svicarska','svicarskoj','switzerland','schweiz'],
            'PL'=>['poljska','poljskoj','poland'], 'QA'=>['katar','kataru','qatar'],
            'AL'=>['albanija','albaniji','albania'],
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
    return fcc_coach_product_context_from_catalog($posts, fcc_coach_catalog(), $text, $ref);
}

/** Shared public product facts only; no collaborator account or CRM context. */
function fcc_coach_product_context_from_catalog(array $posts, array $catalog, string $text, string $ref): array {
    $market = fcc_coach_requested_market($text);
    $localFacts = $market==='' || $market==='HR';
    $normalized = fcc_coach_normalize(mb_substr($text, -12000));
    $safety = fcc_ai_v2_safety_assessment($text);
    $medical = !$safety['allow_products'] || fcc_ai_is_internal_coach_personal_health_product_request($text)
        || preg_match('/bubreg|bubrez|bubre[zž]|kidney|nephr|nefro|dijaliz|dialysis|ledvic|trudn|pregnan|dojenj|breastfeed|terapij|lijekov|lekov|\brak\b|cancer|dijabet|diabet|jetr|\bjert[au]\b|(?:visok|povisen)\p{L}*\s+enzim|liver|hepat|kemoterap|chemotherap/u', $normalized);
    $tokens = array_values(array_filter(explode(' ', $normalized), static fn($t) => mb_strlen($t)>=4 && !in_array($t, ['forever','proizvod','proizvoda','preporuci','preporuciti','prijateljica','osoba','osobi','treba','zelim','zeli','poruku','napisi','kako','koji','koja','sto','svaki','jedan','dodatak','dodatke','prehrani','svakodnevno','koristi','koristiti','informacije'], true)));
    $needTerms=[];
    foreach(['protein'=>'protein', 'bjelancevin'=>'protein', 'dezodor'=>'dezodor', 'deodor'=>'dezodor', 'hidrat'=>'hidrat', 'moistur'=>'hidrat', 'vlak'=>'vlakn', 'fiber'=>'vlakn', 'fibre'=>'vlakn', 'kolagen'=>'kolagen', 'collagen'=>'kolagen', 'ciscenj'=>'ciscenj', 'cleanser'=>'ciscenj', 'omega 3'=>'ribljim uljem', 'omega3'=>'ribljim uljem', 'riblje ulje'=>'ribljim uljem', 'multivitamin'=>'multivitamin', 'vitamin b12'=>'vitaminom b12', 'vitamina b12'=>'vitaminom b12', 'vitamin c'=>'vitaminom c', 'vitamina c'=>'vitaminom c', 'vitamin d'=>'vitaminom d', 'vitamina d'=>'vitaminom d', 'kalcij'=>'kalcij'] as $needle=>$term) {
        if(str_contains($normalized,$needle)) $needTerms[]=$term;
    }
    $constraints = fcc_ai_v2_user_product_constraints($text);
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
        $score = $direct ? 1000 : 0;
        if(!$direct && $reviewed && $localFacts && !$medical && !$conflicts) {
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
        $found[] = ['score'=>$score, 'title'=>html_entity_decode(strip_tags($post['title']), ENT_QUOTES,'UTF-8'), 'explicitly_named'=>$direct,
            'referral_url'=>$ref!=='' ? SITE_URL.'blog/'.$post['url'].'?'.http_build_query(['ref'=>$ref]) : null,
            'recommendation_allowed'=>(bool)($reviewed && $localFacts && !$medical && !$conflicts && !$blocked),
            'status'=>$blocked ? 'blocked' : ($reviewed && $localFacts ? 'reviewed_facts' : 'article_identity_only'),
            'use_cases'=>$reviewed && $localFacts ? array_slice($row['use_cases'],0,4) : [],
            'facts'=>$reviewed && $localFacts ? array_slice(fcc_coach_description_facts($row['approved_support_facts']),0,5) : [],
            'ingredients'=>$reviewed && $localFacts ? array_slice($row['ingredient_facts'],0,2) : [],
            'safety_notes'=>$row ? array_slice($row['mandatory_safety_notes']??[],0,4) : [],
            'constraint_conflicts'=>$conflicts, 'source'=>$source];
        // Label usage is general product information, never a personalised medical regimen.
        $found[array_key_last($found)]['label_usage']=$reviewed && !$medical && !$conflicts && $market==='HR' ? $usage['instructions'] : [];
    }
    usort($found, static fn($a,$b)=>$b['score']<=>$a['score']);
    $found=array_slice($found,0,6);
    foreach($found as &$p) unset($p['score']); unset($p);
    return ['published_articles'=>count($posts), 'fact_scope'=>'Pregledane činjenice za HR. Ne dokazuju sastav, dostupnost ili cijenu za drugu zemlju.',
        'explicit_market'=>$market, 'local_product_facts_available'=>$localFacts,
        'medical_context'=>(bool)$medical, 'safety_level'=>$safety['level'], 'products'=>$found,
        'retrieval_note'=>'Ako nema odgovarajućeg prijedloga, postavi jedno pitanje o običnoj potrebi ili nazivu proizvoda. Ne izmišljaj značajke iz naziva. Članak nije službena deklaracija.'];
}

/** No private CRM notes, contact identities, payment details or administrative LOS data. */
function fcc_coach_mentor_context(int $uid, string $text, array $context=[]): array {
    $s=fcc_journey_state($uid);
    $cards=array_values(array_filter(fcc_partner_cards($uid), static fn($c)=>!empty($c['is_enabled'])));
    $ref=$cards[0]['url']??'';
    $webinar=fcc_partner_webinar_invitation($cards);
    $task=(!$s['intro']&&!empty($s['program']['state']['can_access_education'])) ? $s['action'] : [];
    $newProgramPending=fcc_jp_enabled($uid)&&empty($s['journey90']['pilot']);
    if($newProgramPending)$task=['title'=>'Počni novi Moj put','instruction'=>'Otvori Moj korak i pokreni novi program. Prvi zadatak je My Global Business, zatim društvene mreže i kartica sa sponzorom.','can_complete'=>false];
    $mentor=fcc_jp_mentor($uid);
    $counts=fcc_partner_one("SELECT COUNT(*) total,SUM(next_followup<=? AND contact_permission<>'do_not_contact') due FROM fcc_partner_contacts WHERE user_id=?", [fcc_partner_today(),$uid]);
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
    $webinarLearning=fcc_webinar_learning_task((int)($s['journey90']['position']??0));
    if($webinarActivity)$webinarActivity['next_action']=fcc_webinar_next_action($webinarActivity['growth'],['step'=>$s['journey90']['position']??0],$s['profile']);
    return [
        'version'=>FCC_COACH_MENTOR_VERSION, 'date'=>fcc_partner_today(), 'language'=>$s['user']->language,
        'package'=>['pro_active'=>$s['pro'],'education_available'=>!empty($s['program']['state']['can_access_education'])],
        'current_page'=>array_intersect_key($context['page']??[],array_flip(['label','route','section'])),
        'features'=>fcc_coach_features($s['user']),
        'own_profile'=>array_intersect_key($s['profile'],array_flip(['goal','channel','minutes','paused_until','reminders','reminder_hour'])),
        'own_business_focus'=>array_intersect_key($context['ai_plan']['profile']??[],array_flip(['primary_goal_label','priority_offer_label','communication_style_label','active_channels','product_focus','audience_focus'])),
        'team_onboarding'=>['joined_self_reported'=>fcc_journey_team_group_joined($uid),'location'=>SITE_URL.'partner/notifications?welcome=team#team-group','note'=>'Poziv u timsku grupu je u Obavijestima. Pridruživanje se preporučuje, ali ne blokira edukaciju dok član čeka prijem. Klik nije dokaz članstva.'],
        'task_tools'=>array_map(static fn($row)=>['label'=>$row[0],'url'=>$row[1]],fcc_journey_task_tools($uid,$s['journey90']['content']??[])),
        'current_task'=>array_intersect_key($task,array_flip(['title','instruction','success_definition','core','can_complete','is_daily_complete','is_waiting_for_event_completion'])),
        'saved_preparation'=>$s['pro'] ? array_intersect_key($s['journey90']['draft']??[],array_flip(['body','message','audience','need','product_title'])) : [],
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
    $rules=file_get_contents(APP_PATH.'config/fcc_coach_mentor.hr.txt');
    return file_get_contents(APP_PATH.'config/fcc_communication_style.hr.txt')."\n\n".$rules."\n\nServer context JSON:\n".json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE)
        ."\n\nOdgovori JSON objektom s poljima guidance i recipient_message. guidance je kratak odgovor suradniku. Ako suradnik traži savjet kako pristupiti osobi, a nedostaje faza razgovora, postavi jedno kratko pitanje u guidance i ostavi recipient_message prazno. IZNIMKA: izričito preuređivanje postojeće poruke ili zahtjev za konkretnim pregledom s imenovanim proizvodima i linkovima izvrši odmah, bez pitanja o fazi razgovora. Kad je situacija jasna ili korisnik odgovori na tvoje pitanje, odmah napiši prilagođenu poruku. Prvo javljanje nije popis proizvoda; tražene informacije ne odgađaj novim pitanjem o dopuštenju. recipient_message je SAMO gotova poruka koju suradnik može poslati drugoj osobi, bez naslova, navodnika, Markdown oznaka i uputa suradniku. Ne ponavljaj je u guidance. Kad poruka ne pomaže aktualnom pitanju ili je kontakt odbio poruke, recipient_message ostavi prazno. Ne izmišljaj prijašnji razgovor, primateljev interes ni osobna iskustva. Sučelje samo dodaje uređivanje i gumb Otvori WhatsApp kada poruka postoji. Ne šalješ poruke i ne biraš primatelja. Sva ograničenja, uključujući medicinska, vrijede za OBA polja. Ako u zdravstvenoj situaciji poruka sadrži informacije o dodacima, provjera s liječnikom prije uzimanja mora biti jasna i primatelju, ne samo u guidance.";
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
    if(($guidance===''&&$message==='')||mb_strlen($guidance)>6000||mb_strlen($message)>4000) return null;
    if(!fcc_coach_validate_links($guidance,$context)||!fcc_coach_validate_links($message,$context)) return null;
    return ['guidance'=>$guidance,'recipient_message'=>$message,'content'=>trim($guidance.($message!==''?"\n\nPoruka za slanje:\n".$message:''))];
}

/** Referrals and application destinations must be grounded in this owner's packet. */
function fcc_coach_validate_links(string $reply, array $context): bool {
    $allowed=[];
    array_walk_recursive($context,static function($value,$key) use (&$allowed) {
        if(is_string($value) && in_array($key,['url','referral_url','personal_card_url','share_url','invitation_url','contact_location','profile_url','sponsor_profile_url'],true) && preg_match('~^https?://~',$value)) $allowed[]=$value;
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
    if($message==='' || empty($context['catalog']['medical_context'])) return $issues;
    $text=fcc_coach_normalize($message);
    if(preg_match('/preporucen\p{L}*\s+proizvod|recommended\s+products|\b(?:naruc\p{L}*|kupi|kupite|kupnj\p{L}*|popust\p{L}*|isprobaj\p{L}*|probaj\p{L}*)\b|\b(?:buy|order|try)\s+(?:now|these|this|the\s+products)\b/u',$text)) $issues[]='medical_sales_framing';
    if(preg_match('/(?:sniz\p{L}*|regulir\p{L}*)\s+(?:krvni\s+)?tlak|pomaz\p{L}*\s+(?:kod|za|jetri|bubrezima)|lijeci|treats?\s+(?:hypertension|kidney|liver)/u',$text)) $issues[]='unverified_medical_benefit';
    $products=$context['catalog']['products']??[];
    foreach($context['recipe_reference']['matches']??[] as $record) foreach($record['product_information']??[] as $p) $products[]=$p;
    $mentionsProduct=false;
    foreach($products as $p) {
        $title=fcc_coach_product_identity((string)($p['title']??''));
        if(($title!==''&&str_contains(fcc_coach_product_identity($message),$title)) || (!empty($p['referral_url'])&&str_contains($message,$p['referral_url']))) {$mentionsProduct=true;break;}
    }
    if($mentionsProduct) {
        if(!preg_match('/lijecnik|nefrolog|doctor|physician|nephrolog/u',$text) || !preg_match('/prije\s+(?:bilo kakvog\s+)?uzimanja|before\s+(?:taking|use|using)/u',$text)) $issues[]='missing_review_before_use';
        if(preg_match('/ako (?:zelis|je potrebno)|po potrebi|if you (?:want|wish)|if (?:necessary|needed)/u',$text,$m,PREG_OFFSET_CAPTURE)) {
            // Conditional offers to find a label are fine; conditional medical review is not.
            $clause=preg_split('/[.!?\n]/u',substr($text,$m[0][1]),2)[0];
            if(preg_match('/(?:provjer|prodi|prodes|pokaz|savjet|check|discuss|consult)\p{L}*.{0,60}(?:lijecnik|nefrolog|doctor|physician)/u',$clause)) $issues[]='optional_medical_review';
        }
        if(preg_match_all('/prije\s+(?:bilo kakvog\s+)?uzimanja|before\s+(?:taking|use|using)/u',$text)>1) $issues[]='repeated_medical_review';
    }
    return array_values(array_unique($issues));
}
