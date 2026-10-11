<?php
defined('ALTUMCODE') || die();

/** Keep the original recordings available when only the written locale exists. */
function fcc_education_settings_for_language(string $language): ?object {
    $all=settings()->fcc_education->education_by_language??(object)[];
    $own=$all->{$language}??null;
    if($own && (isset($own->enabled) && !$own->enabled || !empty($own->videos))) return $own;
    return $all->Hrvatski??$all->english??$own;
}

/** Shared, versioned education facts for the library and the private member Coach. */
function fcc_education_catalog(): array {
    static $catalog;
    return $catalog ??= json_decode(file_get_contents(__DIR__.'/../config/fcc_education_library.json'), true, 512, JSON_THROW_ON_ERROR);
}

function fcc_education_lesson(string $id, string $language='Hrvatski'): array {
    $catalog=fcc_education_catalog();
    $numeric=preg_split('~[/?]~', $id)[0];
    foreach($catalog['lessons'] as $lesson) if($lesson['id']===$numeric) {
        if(fcc_locale($language)==='en') foreach(['title','description','takeaways','practice'] as $key) $lesson[$key]=$lesson[$key.'_en'];
        if(in_array(fcc_locale($language),['sl','de','sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'],true)) $lesson=fcc_localize_copy($lesson,fcc_locale($language));
        $lesson['url']=SITE_URL.'partner?view=materials&lesson='.$lesson['key'];
        $lesson['documents']=[['label'=>fcc_t($catalog['source']['label'],[],fcc_locale($language)).' · '.fcc_t($lesson['section'],[],fcc_locale($language)), 'url'=>$catalog['source']['url'].'#page='.$lesson['page']]];
        return $lesson;
    }
    return [];
}

/** Library access and the historical mandatory introduction are different states. */
function fcc_education_video_state(array $videos, bool $configured, bool $completed, int $index, bool $newProgram, string $language): array {
    $items=[];
    foreach(array_values($videos) as $i=>$v) {
        $v=(array)$v; $id=(string)($v['vimeo_id']??'');
        $valid=preg_match('~^([0-9]+)(?:[/?]h=?([a-zA-Z0-9]+))?$~D', $id, $match);
        $src=$valid?'https://player.vimeo.com/video/'.$match[1].'?'.http_build_query(['playsinline'=>1]+(!empty($match[2])?['h'=>$match[2]]:[])):'';
        $lesson=fcc_education_lesson($id,$language);
        $title=trim((string)($v['title']??''));
        if($title===''||preg_match('/^video(?:\s+\d+)?$/i',$title)) $title=$lesson['title']??($language==='Hrvatski'?'Lekcija ':'Lesson ').($i+1);
        $items[]=['index'=>$i,'id'=>$id,'title'=>fcc_partner_text(fcc_t($title,[],fcc_locale($language)),200),'allowed'=>$configured&&($newProgram||$completed||$i<=$index),'src'=>$src,'lesson'=>$lesson];
    }
    return ['enabled'=>$configured&&!$newProgram,'library_enabled'=>$configured,'free_browsing'=>$newProgram||$completed,'completed'=>$completed,'current'=>max(0,$index),'items'=>$items];
}

function fcc_education_selected(array $videos, array $query): int {
    $last=max(0,count($videos['items'])-1);
    $selected=min($last,(int)$videos['current']);
    if(isset($query['lesson'])&&is_string($query['lesson'])) foreach($videos['items'] as $item) {
        if(($item['lesson']['key']??'')===$query['lesson']&&$item['allowed']) return $item['index'];
    }
    if(isset($query['video'])&&is_scalar($query['video'])) {
        $candidate=$query['video']==='last'?$last:max(0,min($last,(int)$query['video']));
        if(!empty($videos['items'][$candidate]['allowed'])) $selected=$candidate;
    }
    return $selected;
}

function fcc_education_mentor_message(string $topic, bool $english=false): string {
    if(fcc_new_locale())return fcc_t('Bok! Želim raditi prema cilju: {topic}. Možemo li dogovoriti 20 minuta da prođemo gdje sam sada i složimo konkretan plan? Javi mi kada ti odgovara.',['{topic}'=>$topic]);
    if(fcc_locale()==='sr') return 'Zdravo! Želim da radim na ovom cilju: '.$topic.'. Možemo li da izdvojimo 20 minuta, pogledamo gde sam sada i napravimo konkretan plan? Javi kada ti odgovara.';
    if(fcc_locale()==='de') return 'Hallo! Ich möchte an folgendem Ziel arbeiten: '.$topic.'. Können wir uns 20 Minuten Zeit nehmen, meinen aktuellen Stand ansehen und einen konkreten Plan machen? Sag mir gerne, wann es dir passt.';
    if(fcc_locale()==='sl') return 'Živjo! Želim si doseči naslednji cilj: '.$topic.'. Se lahko dogovoriva za 20 minut, da pregledava, kje sem zdaj, in pripraviva konkreten načrt? Sporoči mi, kdaj ti ustreza.';
    return $english
        ?'Hi! I would like to work towards '.$topic.'. Could we arrange 20 minutes to review where I am now and make a concrete plan? Let me know when suits you.'
        :'Bok! Želim raditi prema cilju: '.$topic.'. Možemo li dogovoriti 20 minuta da prođemo gdje sam sada i složimo konkretan plan? Javi mi kada ti odgovara.';
}

/** Only confirmed contacts from the current visible sponsor line, never a guessed mentor. */
function fcc_education_contacts(int $uid, string $topic, bool $english=false, ?array $graph=null): array {
    if(!function_exists('fcc_team_graph')) return [];
    $graph=$graph??fcc_team_graph(); $contacts=[];
    foreach(['sponsor'=>fcc_team_sponsor($uid,$graph),'manager'=>fcc_team_first_manager($uid,$graph)] as $role=>$person) {
        if(!$person||isset($contacts[$person['user_id']])) continue;
        $phone=(string)($person['phone']??'');
        $digits=preg_replace('/\D/','',$phone);
        $valid=preg_match('/^[1-9][0-9]{6,14}$/D',$digits);
        $contacts[$person['user_id']]=['role'=>$role,'name'=>$person['name'],'message'=>fcc_education_mentor_message($topic,$english),
            'url'=>$valid?'https://wa.me/'.$digits.'?text='.rawurlencode(fcc_education_mentor_message($topic,$english)):null];
    }
    return array_values($contacts);
}

function fcc_education_topics(string $text): array {
    $text=mb_strtolower(fcc_german_query_equivalents($text),'UTF-8'); $keys=[];
    $patterns=['auto'=>'~\b(?:car|avto|auto(?:programm)?|automobil\p{L}*|forever\s*2\s*drive|f2d)\b~u','eagle'=>'~\beagle\b~u','chairman'=>'~\b(?:chairman\p{L}*|chairmen\p{L}*|predsjedni\p{L}*\s+bonus)\b~u','activity'=>'~\b4\s*cc\b|monatlich\p{L}*\s+aktivität|mesečn\p{L}*\s+aktivnost|mjesečn\p{L}*\s+aktivnost~u','positions'=>'~\b(?:položaj\p{L}*|stopnj\p{L}*|pozicij\p{L}*|supervisor|supervizor|rang|karrierestuf\p{L}*|marketing\s*plan)\b~u'];
    foreach($patterns as $key=>$pattern) if(preg_match($pattern,$text)) $keys[]=$key;
    return $keys;
}

function fcc_education_coach_context(int $uid, string $text, object $user, array $videos): array {
    $catalog=fcc_education_catalog(); $keys=fcc_education_topics($text); $lessons=[]; $matches=[];
    foreach($videos['items'] as $item) {
        if(!$item['allowed']||!$item['lesson']) continue;
        $l=$item['lesson'];
        $lessons[]=['title'=>$l['title'],'url'=>$l['url']];
        if(in_array($l['key'],$keys,true)) $matches[]=array_intersect_key($l,array_flip(['key','title','description','takeaways','levels','practice','url','documents']));
    }
    $incentive=(bool)array_intersect($keys,['auto','eagle','chairman'])||preg_match('~förderprogramm|förderung|anreizprogramm|spodbud|nagradn|poticaj|incentive|osvoji\p{L}*\s+(?:auto|putovanj)|global\s*rally~iu',$text);
    $topic=$matches[0]['title']??fcc_t('moj poslovni cilj');
    return ['version'=>$catalog['version'],'url'=>SITE_URL.'partner?view=materials','lessons'=>$lessons,'matches'=>$matches,
        'incentive_question'=>(bool)$incentive,'source'=>$catalog['source'],
        'scope'=>'HR reference, not a global policy or a qualification calculator. Language is not the member market. Current market, period, recognised rank, CC types, activity and maintenance must be confirmed. Existing video may predate the linked policy.',
        'contacts'=>($keys||$incentive)?fcc_education_contacts($uid,$topic,$user->language!=='Hrvatski'):[],
        'contact_location'=>SITE_URL.'partner?view=materials'.($matches?'&lesson='.$matches[0]['key']:'').'#mentor',
        'qualification_verified'=>false];
}
