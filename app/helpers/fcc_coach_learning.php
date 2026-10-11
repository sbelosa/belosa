<?php
defined('ALTUMCODE') || die();

/** Curated public knowledge only. No contacts, chat contents or private notes are stored here. */
function fcc_coach_knowledge(): array {
    static $data;
    return $data ??= json_decode(file_get_contents(__DIR__.'/../config/fcc_coach_knowledge.json'),true,512,JSON_THROW_ON_ERROR);
}

function fcc_coach_topic_domain(string $text): string {
    $text=fcc_coach_normalize(fcc_german_query_equivalents($text));
    if(preg_match('/provizij|izplacil|placil|povracil|isplat|obracun|commission|payment|povrat novca/u',$text)) return 'support';
    if(preg_match('/\b(?:cc|4core|4 core|supervisor|supervizor|manager|eagle|chairman\p{L}*|car program|auto program|forever2drive)\b|pozicij|poticajn|spodbud|nagradn|kaj je forever|kaj je fcc|sto je forever|sto je fcc/u',$text)) return 'business';
    if(preg_match('/sestavin|sestav|izdelek|izdelk|ledvic|jetr|sastoj|sastav|selen|cink|vitamin|proizvod|aloe|arctic|bubreg|bubrez|jetru|jetra|tlak/u',$text)) return 'product';
    if(preg_match('/\b(?:kartic\p{L}*|gumb|aplikacij\p{L}*|lozink\p{L}*|gesl\p{L}*)\b/u',$text)) return 'app';
    return '';
}

/** The last explicit request wins. Short clarifications retain the previous request. */
function fcc_coach_turn_intent(string $text): array {
    $lines=array_values(array_filter(explode("\n",trim($text))));
    $last=fcc_coach_normalize(fcc_german_query_equivalents((string)end($lines)));
    $all=fcc_coach_normalize(fcc_german_query_equivalents($text));
    $draft='/(?:napisi|sestavi|pripravi|oblikuj|sastavi|slozi|pripremi|preuredi|uredi|doradi|write|draft)\p{L}*.{0,65}(?:sporocil|povabil|vabil|poruk|poziv|upit|message|invitation)|kako (?:da )?(?:mu|joj|im)\b|zelim (?:mu|joj|im)\b|(?:po|na)salj\p{L}*\s+(?:mu|joj|im)|zelim (?:da )?(?:(?:mu|joj|im) )?(?:po)?salj\p{L}*.{0,35}(?:poruk|poziv)|zelim poslati|kaj (?:naj )?napisem|sto (?:da )?napisem/u';
    $own=(bool)preg_match('/ne za sodelavca|sprasujem zase|moj naslednji korak|koncal|opravila|opravil|ne za suradnika|pitam za sebe|moj (?:sljedeci |sledeci )?korak|sto (?:ja|da ja) (?:trebam|mogu)|moj(?:em)? cilj|zavrsio|zavrsila|odradio|odradila|dovrsio|dovrsila/u',$last);
    $mode='contextual';
    $previous=implode("\n",array_slice($lines,0,-1));
    $editFollowup=(bool)preg_match('/skrati|krac|sustinu|preformul|preured|izostavi|izbaci|na pocetku|pocni|pocne|moze[sm]? reci|mozemo reci|zameni|zamijeni|dodaj|ubaci|sastavi poruku/u',$last);
    if($previous!=='' && $editFollowup && fcc_coach_turn_intent($previous)['mode']==='message') {
        return ['mode'=>'message','recipient_required'=>true,'recipient_forbidden'=>false,'domain'=>fcc_coach_topic_domain($text),'latest_question'=>mb_substr((string)end($lines),0,1600)];
    }
    if(preg_match($draft,$last)) $mode='message';
    elseif($own) $mode='own_plan';
    elseif(preg_match('/provizij|izplacil|placil|povracil|isplat|obracun|commission|payment|podrsk|support/u',$last)) $mode='support';
    elseif(preg_match('/razlozi|pojasni|kaj (?:je|pomeni|so)|kateri izdelek|vsebuje|kako (?:deluje|naj dosezem)|nauciti|objasni|pojasni|(?:sto|sta) (?:je|znaci|su)|kako (?:radi|se koristi|nastaj|funkcion)|koji proizvod|koji.*sadrz|sastav|sastoj|uciti|nauciti|o foreveru|poslovn\p{L}* politik|kako da (?:osvojim|postignem)|zelim.*(?:program|pozicij)|what is|explain|which product|learn/u',$last)) $mode='learning';
    elseif(preg_match('/^(?:sloven\p{L}*|nemcij\p{L}*|hrvask\p{L}*|hrvatsk\p{L}*|njemack\p{L}*|prv\p{L}* (?:stopnj\p{L}*|ravn\p{L}*|razin\p{L}*|nivo)|avto|auto|car|eagle|chairman|da|ja|lahko|moze)[.!? ]*$/u',$last)) {
        $previous=implode("\n",array_slice($lines,0,-1));
        if($previous!=='') $mode=fcc_coach_turn_intent($previous)['mode'];
    }
    // Follow-ups to a message request may describe the recipient without repeating "write".
    if($mode==='contextual' && preg_match($draft,$all)) $mode='message';
    if($mode==='contextual' && fcc_coach_topic_domain($text)==='business') $mode='learning';
    return ['mode'=>$mode,'recipient_required'=>$mode==='message','recipient_forbidden'=>in_array($mode,['learning','own_plan','support'],true),
        'domain'=>fcc_coach_topic_domain($text),'latest_question'=>mb_substr((string)end($lines),0,1600)];
}

function fcc_coach_knowledge_context(string $text): array {
    $data=fcc_coach_knowledge(); $n=fcc_coach_retrieval_normalize($text); $market=fcc_coach_requested_market($text); $matches=[];
    foreach($data['records'] as $record) {
        if($record['review_status']!=='reviewed_summary'||($record['effective_to']&&$record['effective_to']<date('Y-m-d'))) continue;
        $score=0;
        foreach($record['terms'] as $term) if(str_contains($n,fcc_coach_normalize($term))) $score+=mb_strlen($term);
        if(!$score)continue;
        unset($record['terms']);
        $record['applies_to_market']=$record['market']==='all'||$market===$record['market'];
        $record['url']=SITE_URL.'partner?view=materials&topic='.$record['topic_id'].'#business-learning';
        $record['sources']=$record['source_url']!==''?[['url'=>$record['source_url'],'section'=>$record['source_section']]]:[];
        $matches[]=['score'=>$score,'record'=>$record];
    }
    usort($matches,fn($a,$b)=>$b['score']<=>$a['score']);
    $matches=array_column(array_slice($matches,0,4),'record');
    $contacts=[];
    if(fcc_coach_topic_domain($text)==='support') foreach($data['contacts'] as $contact) if($market===$contact['market']) {
        $contact['url']=$contact['source_url'];$contacts[]=$contact;
    }
    return ['version'=>$data['version'],'market'=>$market,'matches'=>$matches,'official_contacts'=>$contacts,
        'needs_market_for_local_rules'=>$market==='',
        'unverified_market'=>$market!==''&&$market!=='HR',
        'qualification'=>['verified'=>false,'reason'=>'Only aggregate CC are available. Rank recognition, qualifying CC categories, period, activity and programme conditions are not fully verified.'],
        'source_rule'=>'Explain available facts first. HR facts are a named reference, never proof for another market. No guessed payment steps, amounts, dates or qualification. Experiences are practice examples, not policy or medical evidence.'];
}

/** Stable task keys make the optional education layer independent of saved task snapshots. */
function fcc_coach_learning_task(string $taskId): array {
    static $overlay;
    $overlay ??= json_decode(file_get_contents(__DIR__.'/../config/curriculum/learning90.hr.json'),true,512,JSON_THROW_ON_ERROR);
    $task=$overlay['tasks'][$taskId]??[];
    if(!$task)return [];
    foreach(fcc_coach_knowledge()['records'] as $record) if($record['topic_id']===$task['topic_id']) {
        return $task+['version'=>$overlay['version'],'concept'=>$record['title'],'explanation'=>$record['summary'],
            'lesson_url'=>SITE_URL.'partner?view=materials&topic='.$record['topic_id'].'#business-learning',
            'team_url'=>SITE_URL.'partner/notifications?welcome=team#team-group'];
    }
    return [];
}

function fcc_coach_learning_context(int $uid,string $text,array $task=[],string $language='Hrvatski'): array {
    $learning=fcc_coach_learning_task((string)($task['id']??''));
    $english=$language!=='Hrvatski';
    return ['task'=>$learning,'team_url'=>SITE_URL.'partner/notifications?welcome=team#team-group',
        'mentor_url'=>SITE_URL.'partner?view=materials#mentor',
        'contacts'=>fcc_education_contacts($uid,$learning['concept']??($english?'my next learning step':'moj sljedeći korak'),$english),
        'approach'=>'Learn by asking a concrete product or business question in the team group, discussing it with the sponsor and practising one explanation. Never assign label reading as the main learning activity. Use verified composition and usage in the background. Do not copy another person’s experience as the member’s own. Do not disclose third party health details in groups. Sponsor/team experience does not establish clinical suitability.',
        'available_after_daily_completion'=>true];
}

/** Compact provenance and quality signals, never third-party medical details. */
function fcc_coach_learning_metadata(array $context,array $history=[]): array {
    $signals=[]; $last=fcc_coach_normalize((string)($context['intent']['latest_question']??''));
    if(preg_match('/ne za suradnika|pitam za sebe|nisam to pita|moj korak|krivo si|nisi razum|nije to/u',$last)) $signals[]='intent_correction';
    $lines=array_map(static fn($m)=>fcc_coach_normalize(is_object($m)?$m->content:(string)$m),$history);
    if(count($lines)>1) {
        $latest=array_pop($lines);
        foreach($lines as $line) if(mb_strlen($latest)>8&&$latest===$line) {$signals[]='repeated_question';break;}
    }
    if(($context['intent']['mode']??'')==='support'&&!count($context['knowledge']['official_contacts']??[])) $signals[]='support_contact_missing';
    if(($context['intent']['mode']??'')==='learning' && !count($context['knowledge']['matches']??[]) && !count($context['education']['matches']??[]) && !count($context['catalog']['products']??[])) $signals[]='knowledge_gap';
    return ['intent'=>$context['intent']['mode']??'contextual','knowledge_version'=>$context['knowledge']['version']??'',
        'topics'=>array_values(array_unique(array_merge(array_column($context['knowledge']['matches']??[],'topic_id'),array_column($context['education']['matches']??[],'key')))),
        'market'=>$context['knowledge']['market']??'', 'education_version'=>$context['education']['version']??'',
        'catalog_revision'=>$context['catalog']['revision']??[],
        'signals'=>$signals];
}

function fcc_coach_learning_rules(): string {
    return 'Najprije riješi stvarno pitanje. intent.mode=learning znači objasni pojam, zašto je važan, jednostavan primjer i jednu moguću primjenu. own_plan znači odgovori o suradniku, ne zamišljenom kupcu. support znači konkretan alat ili službeni kontakt. Za te tri namjere polje za poruku drugoj osobi ostaje prazno, osim ako je suradnik izričito tražio nacrt poruke. Kratke dopune poput Hrvatska, prva razina i Auto program nastavljaju istu temu. Koristi knowledge.matches i education.matches, ne traži da korisnik sam pronađe pravila koja su već navedena. Obrazovni odgovor nije ograničen na dvije rečenice: objasni dovoljno da početnik razumije. Učenje o proizvodu poveži s konkretnim pitanjem timskoj grupi ili sponzoru i kratkom vježbom objašnjavanja. Ne zadaj čitanje deklaracije kao glavnu vježbu. Točne činjenice o sastavu i uporabi i dalje provjeravaš u kontekstu. Timska iskustva ne pretvaraj u dokaz liječenja, dozu ili izmišljeno osobno iskustvo. U grupu ne prenositi tuđe ime, dijagnozu, nalaz ili terapiju. Završeni dnevni korak ne zabranjuje dobrovoljno učenje niti zahtijeva ponavljanje zadatka. Najprije pomozi razumjeti, zatim uputi sponzoru za praktičnu primjenu. Za isplatu koristi knowledge.official_contacts za točnu zemlju ako postoji. Navedi email i telefon iz tog zapisa. Ne izmišljaj postupak ili rok. Ne računaj kvalifikaciju iz nepotpunih CC. Korisnikovi ciljevi i ispravci su njegov izbor, ne tvoja pretpostavka.';
}
