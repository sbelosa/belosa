<?php
defined('ALTUMCODE') || die();

/** Search equivalence only. Never changes the user's text, country or product facts. */
function fcc_coach_search_text(string $text): string {
    $text=mb_strtolower(html_entity_decode(strip_tags($text),ENT_QUOTES,'UTF-8'),'UTF-8');
    $text=strtr($text,['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','ђ'=>'đ','е'=>'e','ж'=>'ž','з'=>'z','и'=>'i','ј'=>'j','к'=>'k','л'=>'l','љ'=>'lj','м'=>'m','н'=>'n','њ'=>'nj','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','ћ'=>'ć','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'č','џ'=>'dž','ш'=>'š']);
    return fcc_ai_v2_normalize_text($text);
}

/** A direct request sets conversation language; quoted recipient text does not. */
function fcc_coach_requested_language(string $message): string {
    $n=fcc_coach_search_text($message);
    if(mb_strlen($n)>500 || preg_match('/\b(?:prevedi|translate|oversatt|forditsd|przetlumacz|traduce|ubaci citat|osoba (?:kaze|pise)|(?:customer|client|kupac|kupkinja|kunde|kundin)\b.{0,30}\b(?:says|said|writes|kaze|pise|sagt|schreibt))\b/u',$n))return '';
    $language='(?:srpsk\p{L}*|srosk\p{L}*|sepsk\p{L}*|hrvatsk\p{L}*|slovens[ck]\p{L}*|slovenack\p{L}*|njemack\p{L}*|nemack\p{L}*|englesk\p{L}*|english|serbian|croatian|german|deutsch|spanjolsk\p{L}*|spansk\p{L}*|francusk\p{L}*|french|crnogorsk\p{L}*|albansk\p{L}*|svedsk\p{L}*|svenska|swedish|madarsk\p{L}*|magyar|hungarian|poljsk\p{L}*|polski|polish|rumunjsk\p{L}*|rumunsk\p{L}*|romana|romanian|romanes\p{L}*|polsk\p{L}*|espanol|spanish)';
    if(preg_match('/\b(?:nemoj|ne zelim|not|nicht)\b.{0,30}'.$language.'/u',$n))return '';
    if(!preg_match('/^(?:(?:molim te|samo|od sada|odsad)\s+)*(?:na\s+)?('.$language.')(?:\s+jezik\p{L}*)?[.! ]*$/u',$n,$m)
        && !preg_match('/\b(?:pisi|pisati|pricaj|govori|odgovaraj|komuniciraj|write|speak|reply|answer|antworte|sprich|schreib|schreibe|napisi|sastavi|vratim|vrati|vratiti|svara|skriv|valaszolj|beszelj|irj|pisz|odpowiadaj|scrie|raspunde|vorbeste|responde|habla|escribe)\b.{0,45}?('.$language.')/u',$n,$m))return '';
    $name=end($m);
    foreach(['sr'=>['srps','sros','seps','serbian'],'hr'=>['hrvats','croatian'],'sl'=>['sloven'],'de'=>['njemack','nemack','german','deutsch'],'en'=>['engles','english'],'es'=>['spanjol','spansk','espanol','spanish'],'fr'=>['francusk','french'],'cnr'=>['crnogorsk'],'sq'=>['albansk'],'sv'=>['svedsk','svenska','swedish'],'hu'=>['madarsk','magyar','hungarian'],'pl'=>['poljsk','polski','polsk','polish'],'ro'=>['rumunjsk','rumunsk','romana','romanian','romanes']] as $code=>$terms)foreach($terms as $term)if(str_starts_with($name,$term))return $code;
    return '';
}

function fcc_coach_reply_language_state(string $message,string $uiLanguage,array $meta,array $history=[]): array {
    $uiLanguage=fcc_locale($uiLanguage);
    $preferred=(string)($meta['coach_reply_language']??'');
    // A deliberate switch in the page language wins over an earlier chat preference.
    if(isset($meta['coach_ui_language']) && $meta['coach_ui_language']!==$uiLanguage)$preferred='';
    // Recover explicit preferences from this same workspace after deploying the fix.
    if(!isset($meta['coach_ui_language']))foreach($history as $row) {
        if(($row['role']??'')==='user' && ($found=fcc_coach_requested_language((string)$row['content'])))$preferred=$found;
    }
    if($requested=fcc_coach_requested_language($message))$preferred=$requested;
    return ['coach_reply_language'=>$preferred ?: $uiLanguage,'coach_ui_language'=>$uiLanguage];
}

/** Owner + workspace scoped history. Existing messages remain the source of truth. */
function fcc_coach_history_rows(object $conversation): array {
    $uid=(int)($conversation->user_id??0);$id=(int)($conversation->fcc_ai_conversation_id??0);
    if(!$uid || !$id || !fcc_ai_internal_coach_workspace_matches($conversation,$uid))return [];
    $rows = fcc_partner_rows("SELECT m.fcc_ai_message_id,m.role,m.content,m.datetime,m.meta FROM fcc_ai_messages m JOIN fcc_ai_conversations c ON c.fcc_ai_conversation_id=m.fcc_ai_conversation_id WHERE c.fcc_ai_conversation_id=? AND c.user_id=? AND c.assistant_type='coach' AND c.scope='internal_coach' AND m.message_type='chat' AND m.role IN ('user','assistant') ORDER BY m.fcc_ai_message_id ASC",[$id,$uid]);
    /* Custom code: FC-2026-10-13: Carry public product identities, never the full stored metadata. */
    foreach($rows as &$row) {
        $meta=json_decode((string)($row['meta']??'{}'),true)?:[];
        $row['education_anchor']=array_values(array_filter((array)($meta['coach_decision']['education_product_keys']??$meta['product_education']['product_keys']??[]),static fn($key)=>is_string($key)&&preg_match('/^[a-z0-9-]{1,120}$/D',$key)));
        unset($row['meta']);
    }
    unset($row);
    return $rows;
    /* /Custom code: FC-2026-10-13 */
}

function fcc_coach_history_tokens(string $text): array {
    $n=fcc_coach_search_text($text);
    $n=strtr($n,['vebinar'=>'webinar','sarad'=>'surad','juce'=>'jucer','mesanger'=>'messenger']);
    $stop=['kako','koji','koja','koje','meni','moje','moja','moju','mogu','moze','molim','sada','danas','samo','nesto','napisi','poruku','poruka','zelim','treba','trebam','sta','sto','bilo','onome','onda','dali','ovde','ovdje','radili','pamtis','zapamti','informacije','forever'];
    $tokens=preg_split('/[^\p{L}\p{N}]+/u',$n);
    return array_values(array_unique(array_map(static fn($t)=>mb_substr($t,0,6),array_filter($tokens,static fn($t)=>mb_strlen($t)>=4&&!in_array($t,$stop,true)))));
}

/** Bounded exact excerpts, not invented summaries or another person's memory. */
function fcc_coach_history_packet(array $rows,string $latest,?DateTimeImmutable $now=null): array {
    $now??=new DateTimeImmutable('now',new DateTimeZone('Europe/Zagreb'));
    $rows=array_values(array_filter($rows,static fn($r)=>in_array($r['role']??'',['user','assistant'],true)));
    $recent=[];$chars=0;
    foreach(array_reverse($rows) as $r) {
        $size=mb_strlen($r['content']);
        if(count($recent)>=28 || ($chars+$size>22000 && count($recent)>=2))break;
        $recent[]=$r;$chars+=$size;
    }
    $recent=array_reverse($recent);$cut=count($rows)-count($recent);
    $query=$latest;
    if(count(fcc_coach_history_tokens($query))<2)foreach(array_reverse(array_slice($rows,0,-1)) as $r) {
        if($r['role']!=='user')continue;
        $query.="\n".$r['content'];if(count(fcc_coach_history_tokens($query))>=3 || mb_strlen($query)>1800)break;
    }
    $tokens=fcc_coach_history_tokens($query);$n=fcc_coach_search_text($latest);$targetDate=null;
    if(preg_match('/\b(?:jucer|juce|yesterday)\b/u',$n))$targetDate=$now->modify('-1 day')->format('Y-m-d');
    elseif(preg_match('/(?:prije|pre) (?:dva|2) dana|two days ago/u',$n))$targetDate=$now->modify('-2 days')->format('Y-m-d');
    $candidates=[];$notes=[];
    for($i=0;$i<$cut;$i++) {
        $r=$rows[$i];if($r['role']!=='user')continue;
        $text=fcc_coach_search_text($r['content']);
        if(preg_match('/\b(?:zapamti|zapamtiti|zapamtis|zapantiti|zapanti|remember this|upamti)\b/u',$text))$notes[]=$r;
        $overlap=count(array_intersect($tokens,fcc_coach_history_tokens($r['content'])));
        $score=$overlap*5;
        $day=(new DateTimeImmutable($r['datetime'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Y-m-d');
        if($targetDate && $day===$targetDate)$score+=15;
        if($score<5)continue;
        $pair=[$r];if(isset($rows[$i+1]) && $rows[$i+1]['role']==='assistant' && $i+1<$cut)$pair[]=$rows[$i+1];
        $candidates[]=['score'=>$score,'id'=>(int)$r['fcc_ai_message_id'],'rows'=>$pair];
    }
    usort($candidates,static fn($a,$b)=>($b['score']<=>$a['score'])?:($b['id']<=>$a['id']));
    $selected=[];$budget=0;
    foreach(array_reverse(array_slice($notes,-4)) as $r){$r['content']=mb_substr($r['content'],0,1800);$selected[$r['fcc_ai_message_id']]=$r;$budget+=mb_strlen($r['content']);}
    foreach(array_slice($candidates,0,6) as $candidate)foreach($candidate['rows'] as $r) {
        if(isset($selected[$r['fcc_ai_message_id']]))continue;
        $r['content']=mb_substr($r['content'],0,2000);
        if($budget+mb_strlen($r['content'])>14000)continue;
        $selected[$r['fcc_ai_message_id']]=$r;$budget+=mb_strlen($r['content']);
    }
    ksort($selected,SORT_NUMERIC);
    return ['recent'=>$recent,'earlier'=>array_values($selected),'coverage'=>['stored_messages'=>count($rows),'recent_messages'=>count($recent),'earlier_excerpts'=>count($selected),'earliest_stored_at'=>$rows[0]['datetime']??null,'retrieval'=>'bounded_exact_excerpts_same_owner_workspace','is_complete'=>count($rows)===count($recent)+count($selected)]];
}

/** Verified UI instructions, selected by topic rather than invented from chat history. */
function fcc_coach_interface_help(string $text): array {
    $n=fcc_coach_search_text($text);$help=[];
    if(preg_match('/kartic|blok|font|slova|instagram|facebook|\bfb\b|bio\b|drustven|boju|boja|stil|tem[aeu]\b|izgled|razdjelnik|whatsapp|uredivac/u',$n))$help['card_editor']=[
        'url'=>SITE_URL.'partner/cards',
        'instructions'=>'Moja kartica, odaberi karticu, Uredi karticu. Uređivač ima tri koraka, a pregled kartice je stalno uz njega; na mobitelu dodir na dio kartice u pregledu otvara polja tog dijela. Profil: fotografija, ime, Kratki opis ispod imena (na novoj kartici oznaka ispod imena) i O meni, zatim Spremi profil. Sadržaj: Uredi uz blok ili dodir na blok, Dodaj blok nudi blokove s objašnjenjem, strelice mijenjaju redoslijed, prekidač skriva blok. Izgled: Nova kartica ili Stara kartica, stil (Aloe za sve; Smaragd, Zlato, Pijesak i Ponoć uz PRO), promjena se odmah vidi u pregledu, a tek Spremi izgled je prikazuje posjetiteljima. Napredno: boje i detalji mijenja oblik gumba, font, veličinu teksta, oblik fotografije i sjaj pozadine unutar stila; glavna boja i pozadina traže PRO. U obrascu bloka Izgled bloka mijenja izgled gumba i ikonu, a kod razdjelnika crtu, razmak i znak na sredini. WhatsApp blok sam uzima broj iz registracije; drugačiji broj upisuje se u blok i pri spremanju pretvara u međunarodni oblik. Za staru karticu boja teksta, boja pozadine, rub i sjena gumba nalaze se u obrascu bloka pod Boje i oblik, a teme stare kartice i rijetki blokovi u Klasičnom uređivaču. Prebacivanje između nove i stare kartice ne briše blokove, adresu, QR kod ni preporuku.',
        'socials'=>'U bloku Društvene mreže uređuju se zasebno telefon, Facebook, Instagram, TikTok i ostali kanali. Ako je domena već ispisana ispred polja, unosi se samo korisničko ime ili dio adrese nakon domene, ne cijela adresa još jednom. Instagram profil i Instagram DM različiti su kanali. Facebook treba voditi baš na odabranu poslovnu stranicu. Spremi, zatim provjeri ikonu u pregledu ili preko Pogledaj.',
        'limitations'=>'Ovo su upute za sučelje, ne pregled korisnikova zaslona ili sadržaja kartice. Ne tvrdi da vidiš boju, veličinu, spremljeno stanje ili klik. Traži naziv bloka ili polja kad je potreban, ne traži screenshot u chatu koji nema priloge.',
    ];
    if(preg_match('/instal|aplikacij|pocetn\p{L}* zaslon|home screen|ikon\p{L}* na (?:pocetn|ekran|zaslon|mobitel|telefon)|push|obavijest\p{L}* na (?:mobitel|telefon|iphone)/u',$n))$help['install']=[
        'url'=>SITE_URL.'partner/install',
        'instructions'=>'Otvori Instaliraj FCC Partner (partner/install) na mobitelu. Android u Chromeu: Instaliraj aplikaciju, ili izbornik s tri točkice, Dodaj na početni zaslon, Instaliraj. iPhone u Safariju: Dijeli, Dodaj na početni zaslon, ostavi uključeno Otvori kao web aplikaciju, Dodaj. Ako je stranica otvorena unutar WhatsAppa ili Instagrama, prvo je otvori u pregledniku. Na računalu stranica prikazuje QR kod za mobitel. Zatim otvori ikonu FCC Partner, prijavi se istim računom i u Obavijestima uključi obavijesti na uređaju. Na iPhoneu push obavijesti rade samo iz instalirane aplikacije uz iOS 16.4 ili noviji.',
    ];
    if(preg_match('/javn\p{L}* (?:opis|profil)|opis\p{L}*.*(?:javn|rezultat)|fcc rezultat/u',$n))$help['public_profile']=[
        'url'=>SITE_URL.'fcc-results#public-profile',
        'instructions'=>'FCC Rezultati, Tvoj opis za javni profil, polje O meni i mojem radu, zatim Spremi opis i postavke. Tekst može pripremiti i prije kvalifikacije. AI ga obrađuje; postojeći opis ostaje dok obrada ne završi. Javni prikaz ovisi o PRO paketu, uvjetima i dobrovoljnoj privoli. To nije opis bloka na kartici.',
    ];
    if(preg_match('/jezik|language|slovens|srpsk|hrvatsk/u',$n))$help['interface_language']=['instructions'=>'Jezik sučelja bira se u gornjoj traci preko ikone globusa ili oznake jezika. Odaberi željeni jezik, primjerice Hrvatski. Promjena jezika ne mijenja zemlju ponude. Za promjenu samo Coach odgovora korisnik može napisati Piši na srpskom ili drugi željeni jezik.'];
    if(preg_match('/global|my account|my profile|forever racun|vodic|zemlje|trzist/u',$n))$help['forever_guide']=[
        'url'=>SITE_URL.'partner?view=step',
        'instructions'=>'Pokaži mi gdje se uređuje Forever račun otvara ilustrirani orijentacijski vodič u FCC zadatku. Brojevi, olovka i My Account na toj ilustraciji nisu aktivni gumbi niti prijavljeni Forever profil. Za stvarnu radnju koristi Otvori My Profile na Foreverliving.com i osobno se prijavi. Tamo otvori My Account, My Global Business, olovku, dodaj dostupne ponuđene zemlje prema stvarnim uvjetima Forevera, spremi i ponovno provjeri status. Ne proglašavaj da je korisnik završio taj dio gledanjem ilustracije. Coach ne mijenja zemlje i ne potvrđuje uvjete.',
    ];
    return $help;
}
