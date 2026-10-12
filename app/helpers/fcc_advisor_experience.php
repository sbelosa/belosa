<?php
defined('ALTUMCODE') || die();

/** Public visitor context is separate from Coach and never reads member private notes. */
function fcc_advisor_safety(string $text): array {
    $normalized = fcc_ai_v2_normalize_text(fcc_german_query_equivalents($text));
    if(preg_match('/ne mogu disati|ne morem dihati|cannot breathe|can.t breathe|ich bekomme keine luft|no puedo respirar/u',$normalized))return ['level'=>'emergency','allow_products'=>false,'reason_codes'=>['breathing_emergency']];
    $safety = fcc_ai_v2_safety_assessment($normalized);
    if(in_array($safety['level'], ['emergency','urgent'], true)) return $safety;
    $clinical = '/(?<![\p{L}\p{N}])(?:tahikard|tachycard|tachykard|aritmij|arrhythmi|masn\p{L}*\s+jetr|zamas\p{L}*\s+jetr|fatty\s+liver|fettleber|higado\s+graso|стеатоз|тахикард|jetr|liver|hepat|dijabet|diabet|bubreg|bubrez|kidney|ledvic|diagnos|dijagnoz|dijaloz|hipertenz|hipertens|hypertens|gastrit|refluks|reflux|crohn|colitis|kolitis|astm|asthm|autoimun|autoimmun|psorijaz|psorias|ekcem|eczema|dermatit|migren|migrain|palpit|stent|infarkt|infarct|karcinom|cancer|rak|lijek|lekov|medication|zdravil|trudn|pregnan|nosec|embaraz|schwanger|medikament)\p{L}*(?![\p{L}\p{N}])/u';
    if(fcc_ai_v2_has_affirmed_regex($normalized, $clinical)) return ['level'=>'sensitive','allow_products'=>false,'reason_codes'=>['medical_question']];
    return $safety;
}

function fcc_advisor_retrieval_text(string $text): string {
    $normalized=fcc_ai_v2_normalize_text($text);$extra=[];
    foreach(['desodorante|deodorante|desodorant'=>'dezodorans','proteina|eiweiss|eiweiß|beljakovin'=>'protein','collagene|colageno|kollagen'=>'kolagen','hidratante|hidratacion|feuchtigkeit'=>'hidratacija','nettoyant|limpiador'=>'cleanser','fibra|ballaststoff'=>'vlakna','cabello|cheveux'=>'kosa','dentifrico|dentifrice|zahnpasta'=>'pasta za zube'] as $pattern=>$term)if(preg_match('/(?:'.$pattern.')/u',$normalized))$extra[]=$term;
    return $text.($extra?"\n".implode(' ',$extra):'');
}

function fcc_advisor_business_intent(string $message): bool {
    if(!empty(fcc_ai_detect_public_intent('product_advisor',$message)['business_primary']))return true;
    return fcc_ai_v2_has_affirmed_regex(fcc_ai_v2_normalize_text($message),'/\b(?:webinar|suradn|saradn|sodelov|collaborat|zusammenarbeit|poslovan|business|negocio|colabor)\p{L}*/u');
}

function fcc_advisor_focus(array $history, string $message): string {
    $current = trim($message);
    // Carry context for actual continuations only, not every preceding topic.
    if(preg_match('/^(?:da|ne|moze|može|ok|yes|ja|lahko|koliko|kako se koristi|a kako|a sto|a što|a koji|a koje|sto onda|što onda|a za mene|a ovaj|a taj|taj|ovaj|to|it|how much|how do i use|und wie|wie viel|sastav|Hrvatska|Hrvatskoj|Croatia|Slovenija|Sloveniji|Germany|Deutschland|Njemacka|Njemačka)[\s?.!,]*$/iu',$current) || preg_match('/^(?:a\s+)?(?:koliko|kako|how|wie)\b.{0,35}(?:uzim|korist|doz|take|use|cost|kosta|košta)/iu',$current)) {
        return implode("\n",array_slice($history,-2))."\n".$current;
    }
    return $current;
}

/** These strings are also the medical branch's complete instructions, not therapy advice. */
function fcc_advisor_ui(string $language): array {
    $copy = [
        'hr'=>[
            'medical_intro'=>'Pozdrav, javljam se preko tvoje FCC kartice.',
            'restart'=>'Novi razgovor',
            'medical'=>'Ja sam AI savjetnik za suplemente i informacije o proizvodima. Ovaj oblik podrške ne daje preporuke za dijagnoze, liječenje ni osobnu prikladnost dodataka. Za takvu procjenu obrati se liječniku ili ljekarniku.',
            'draft'=>'Poruka za %s', 'open'=>'Otvori WhatsApp', 'generic'=>'Bez opisa zdravstvenog pitanja', 'privacy'=>'Pregledaj i po želji izmijeni poruku. Tek klikom otvaraš WhatsApp, a slanje potvrđuješ tamo. Opis zdravstvenog pitanja možeš ukloniti.',
            'quote'=>'Moje pitanje u AI razgovoru bilo je:', 'medical_end'=>'Možeš li mi pomoći pronaći službene informacije i deklaracije proizvoda? Prikladnost dodataka provjerit ću s liječnikom ili ljekarnikom.',
            'welcome'=>'Bok! Ja sam AI pomoćnik ove FCC kartice. Mogu ti pomoći upoznati Forever proizvode, mogućnosti suradnje ili pronaći kontakt vlasnika kartice.',
            'disclosure'=>'Razgovor obrađuje FCC AI, a mogu ga pregledati vlasnik kartice i ovlaštena podrška. Osjetljive podatke nije potrebno upisivati.',
            'product'=>'Pomoć pri izboru proizvoda','business'=>'Zanima me suradnja','contact'=>'Želim kontakt','presentation'=>'Pogledaj poslovnu prezentaciju','webinar'=>'Pogledaj termin i prijavi se',
        ],
        'en'=>[
            'medical_intro'=>'Hello, I am contacting you through your FCC card.',
            'restart'=>'New conversation',
            'medical'=>'I am an AI adviser for supplements and product information. This service cannot recommend treatment or determine which supplement is suitable for a medical condition. Ask a doctor or pharmacist about suitability.',
            'draft'=>'Message for %s','open'=>'Open WhatsApp','generic'=>'Remove health question','privacy'=>'Review and edit your message. Clicking opens WhatsApp; you choose whether to send it there. You can remove your health question.',
            'quote'=>'My question in the AI conversation was:', 'medical_end'=>'Could you help me find the official product information and labels? I will check suitability with my doctor or pharmacist.',
            'welcome'=>'Hi! I am this FCC card’s AI assistant. I can help you explore Forever products, collaboration or contact the card’s owner.',
            'disclosure'=>'FCC AI processes this conversation. The card owner and authorised support can review it. You do not need to share sensitive information.',
            'product'=>'Help choosing a product','business'=>'Interested in collaboration','contact'=>'Contact the owner','presentation'=>'Watch the business presentation','webinar'=>'View the event and register',
        ],
        'sl'=>[
            'medical_intro'=>'Pozdravljeni, pišem vam prek vaše FCC kartice.',
            'restart'=>'Nov pogovor',
            'medical'=>'Sem AI svetovalec za prehranska dopolnila in informacije o izdelkih. Ta oblika podpore ne daje priporočil za diagnoze, zdravljenje ali osebno primernost dodatkov. O tem se posvetujte z zdravnikom ali farmacevtom.',
            'draft'=>'Sporočilo za %s','open'=>'Odpri WhatsApp','generic'=>'Brez zdravstvenega vprašanja','privacy'=>'Preglejte in uredite sporočilo. Klik odpre WhatsApp, kjer sami potrdite pošiljanje. Zdravstveno vprašanje lahko odstranite.',
            'quote'=>'Moje vprašanje v pogovoru z AI je bilo:', 'medical_end'=>'Mi lahko pomagate najti uradne informacije in deklaracije izdelkov? Primernost bom preveril/a pri zdravniku ali farmacevtu.',
            'welcome'=>'Pozdravljeni! Sem AI pomočnik te FCC kartice. Pomagam vam spoznati izdelke Forever, možnosti sodelovanja ali kontaktirati lastnika kartice.',
            'disclosure'=>'Pogovor obdeluje FCC AI. Pregledajo ga lahko lastnik kartice in pooblaščena podpora. Občutljivih podatkov ni treba vpisovati.',
            'product'=>'Pomoč pri izbiri izdelka','business'=>'Zanima me sodelovanje','contact'=>'Želim kontakt','presentation'=>'Poslovna predstavitev','webinar'=>'Oglej si termin in se prijavi',
        ],
        'de'=>[
            'medical_intro'=>'Hallo, ich melde mich über deine FCC Karte.',
            'restart'=>'Neues Gespräch',
            'medical'=>'Ich bin ein KI Berater für Nahrungsergänzungsmittel und Produktinformationen. Dieser Service gibt keine Empfehlungen zur Behandlung oder persönlichen Eignung bei Erkrankungen. Kläre dies mit einem Arzt oder Apotheker.',
            'draft'=>'Nachricht an %s','open'=>'WhatsApp öffnen','generic'=>'Gesundheitsfrage entfernen','privacy'=>'Prüfe und bearbeite die Nachricht. Erst der Klick öffnet WhatsApp. Dort entscheidest du über das Senden. Du kannst deine Gesundheitsfrage entfernen.',
            'quote'=>'Meine Frage im KI Gespräch war:', 'medical_end'=>'Kannst du mir helfen, offizielle Produktinformationen und Etiketten zu finden? Die Eignung kläre ich mit meinem Arzt oder Apotheker.',
            'welcome'=>'Hallo! Ich bin der KI Assistent dieser FCC Karte. Ich helfe dir, Forever Produkte und Möglichkeiten der Zusammenarbeit kennenzulernen oder die Kontaktperson zu erreichen.',
            'disclosure'=>'FCC AI verarbeitet dieses Gespräch. Der Karteninhaber und autorisierter Support können es einsehen. Sensible Angaben sind nicht erforderlich.',
            'product'=>'Hilfe bei der Produktauswahl','business'=>'Interesse an Zusammenarbeit','contact'=>'Kontakt aufnehmen','presentation'=>'Geschäftspräsentation ansehen','webinar'=>'Termin ansehen und anmelden',
        ],
        'sr'=>[
            'medical_intro'=>'Zdravo, javljam se preko tvoje FCC kartice.',
            'restart'=>'Novi razgovor',
            'medical'=>'Ja sam AI savetnik za suplemente i informacije o proizvodima. Ovaj oblik podrške ne daje preporuke za dijagnoze, lečenje ili ličnu prikladnost dodataka. Za takvu procenu obrati se lekaru ili farmaceutu.',
            'draft'=>'Poruka za %s','open'=>'Otvori WhatsApp','generic'=>'Bez zdravstvenog pitanja','privacy'=>'Pregledaj i izmeni poruku. Klik otvara WhatsApp, a slanje potvrđuješ tamo. Zdravstveno pitanje možeš da ukloniš.',
            'quote'=>'Moje pitanje u AI razgovoru bilo je:', 'medical_end'=>'Možeš li da mi pomogneš da pronađem zvanične informacije i deklaracije proizvoda? Prikladnost ću proveriti sa lekarom ili farmaceutom.',
            'welcome'=>'Zdravo! Ja sam AI pomoćnik ove FCC kartice. Mogu da ti pomognem da upoznaš Forever proizvode, mogućnosti saradnje ili kontaktiraš vlasnika kartice.',
            'disclosure'=>'Razgovor obrađuje FCC AI. Mogu ga pregledati vlasnik kartice i ovlašćena podrška. Osetljive podatke ne moraš da upisuješ.',
            'product'=>'Pomoć pri izboru proizvoda','business'=>'Zanima me saradnja','contact'=>'Želim kontakt','presentation'=>'Poslovna prezentacija','webinar'=>'Pogledaj termin i prijavi se',
        ],
        'es'=>[
            'medical_intro'=>'Hola, te contacto a través de tu tarjeta FCC.',
            'restart'=>'Nueva conversación',
            'medical'=>'Soy un asesor de IA sobre suplementos e información de productos. Este servicio no recomienda tratamientos ni determina si un suplemento es adecuado para una enfermedad. Consulta a un médico o farmacéutico.',
            'draft'=>'Mensaje para %s','open'=>'Abrir WhatsApp','generic'=>'Quitar la pregunta de salud','privacy'=>'Revisa y edita el mensaje. El botón abre WhatsApp, donde decides si lo envías. Puedes quitar tu pregunta de salud.',
            'quote'=>'Mi pregunta en la conversación con la IA fue:', 'medical_end'=>'¿Puedes ayudarme a encontrar información oficial y etiquetas de los productos? Consultaré su idoneidad con un médico o farmacéutico.',
            'welcome'=>'¡Hola! Soy el asistente de IA de esta tarjeta FCC. Puedo ayudarte a conocer los productos Forever, la colaboración o contactar con el titular.',
            'disclosure'=>'FCC AI procesa esta conversación. El titular de la tarjeta y el soporte autorizado pueden revisarla. No es necesario compartir datos sensibles.',
            'product'=>'Ayuda para elegir productos','business'=>'Me interesa colaborar','contact'=>'Quiero contactar','presentation'=>'Ver presentación del negocio','webinar'=>'Ver evento e inscribirse',
        ],
    ];
    $locale=fcc_locale($language);
    return $copy[$locale] ?? (fcc_new_locale($locale) ? fcc_localize_copy($copy['en'],$locale) : $copy['en']);
}

function fcc_advisor_medical_contact(object $user, object $link, string $question, string $language): array {
    $base=fcc_public_advisor_contact($user,$link,[],false,true,$language);
    if(!$base)return [];
    $ui=fcc_advisor_ui($language);$name=trim(strip_tags((string)$user->name));
    // A draft is deliberately shown before any external transfer. Never auto open it.
    $base['kind']='owner_medical_draft';$base['title']=sprintf($ui['draft'],$name);
    $base['draft']=$ui['medical_intro']."\n\n".$ui['quote']."\n".mb_substr(trim(strip_tags($question)),0,1200)."\n\n".$ui['medical_end'];
    $base['generic_draft']=fcc_public_advisor_copy($language)['wa_medical'];
    $base['ui']=array_intersect_key($ui,array_flip(['open','generic','privacy']));
    return $base;
}

function fcc_advisor_business_actions(object $user,object $link,string $language,string $market): array {
    if((int)$user->user_id!==(int)$link->user_id)return [];
    $items=[];$ui=fcc_advisor_ui($language);
    /* Custom code: FC-2026-10-12: the permanent Pozivnica u tim comes first */
    $invite_code=function_exists('fcc_invite_code_for_user')&&function_exists('fcc_business_landing_access')&&fcc_business_landing_access((int)$user->user_id)?fcc_invite_code_for_user((int)$user->user_id):'';
    if($invite_code!=='') {
        $items[]=['kind'=>'business_presentation','title'=>$ui['presentation'],'url'=>fcc_invite_url($invite_code).($market?'?country='.rawurlencode($market):'')];
    }
    /* /Custom code: FC-2026-10-12 */
    if($invite_code===''&&function_exists('fcc_business_enabled')&&fcc_business_enabled((int)$user->user_id)) {
        $shares=fcc_partner_rows('SELECT s.public_token FROM fcc_business_shares s JOIN fcc_business_presentations p ON p.id=s.presentation_id AND p.user_id=s.user_id WHERE s.user_id=? AND p.link_id=? AND p.status=? AND s.revoked_at IS NULL ORDER BY s.id DESC LIMIT 5',[(int)$user->user_id,(int)$link->link_id,'active']);
        foreach($shares as $row){$s=fcc_business_public($row['public_token']);if(!$s||!$s['available'])continue;
            /* Custom code: FC-2026-10-12: offer only a presentation that a guest can open */
            if(function_exists('fcc_business_landing_public_allowed')&&!fcc_business_landing_public_allowed($s,0))continue;
            /* /Custom code: FC-2026-10-12 */
            $destination=fcc_business_share_destination($s,$language);
            if(!empty($destination['expires'])&&$destination['expires']<=time())continue;
            $items[]=['kind'=>'business_presentation','title'=>$ui['presentation'],'url'=>$destination['url'].($market?'?country='.rawurlencode($market):'')];break;
        }
    }
    if(function_exists('fcc_business_webinars'))foreach(fcc_business_webinars(['available'=>true,'user_id'=>(int)$user->user_id,'link_id'=>(int)$link->link_id]) as $event){
        $items[]=['kind'=>'webinar_invitation','title'=>$ui['webinar'].': '.strip_tags($event['title']??''),'url'=>$event['url']];break;
    }
    return $items;
}

function fcc_advisor_catalog_review(): array {
    $out=[];
    foreach(fcc_public_advisor_catalog() as $key=>$row){
        $dates=array_filter(array_map(static fn($s)=>($s['source_type']??'')==='official_product_source'?strtotime($s['accessed_at']??''):false,$row['source_refs']??[]));
        $last=$dates?max($dates):0;$expires=$last?$last+90*86400:0;
        $out[]=['key'=>$key,'name'=>$row['canonical_name']??$key,'status'=>$row['advisor_status']??'lookup_only','checked'=>$last?gmdate('Y-m-d',$last):'','expires'=>$expires?gmdate('Y-m-d',$expires):'','review_due'=>!$expires||$expires<time()+14*86400];
    }return $out;
}

/** Count a deliberate click once per message/action, not a sent message or a sale. */
function fcc_advisor_capture_action(array $payload): array {
    $owner=fcc_ai_resolve_public_owner_context($payload);$c=$owner['conversation']??null;
    if(!$c||$c->assistant_type!=='product_advisor'||!($owner['link']??null)||!fcc_ai_user_has_public_ai_access($owner['user']))throw new RuntimeException('Action unavailable.');
    $mid=(int)($payload['message_id']??0);$index=(int)($payload['action_index']??-1);
    if($mid<1||$index<0||$index>4)throw new RuntimeException('Action unavailable.');
    $m=db()->where('fcc_ai_message_id',$mid)->where('fcc_ai_conversation_id',(int)$c->fcc_ai_conversation_id)->where('role','assistant')->getOne('fcc_ai_messages');
    $meta=json_decode($m->meta??'{}',true)?:[];$a=$meta['knowledge_suggestions'][$index]??[];
    if(($meta['engine']??'')!=='fcc_public_advisor'||!in_array($a['kind']??'',['owner_whatsapp','owner_medical_draft','product_article','product_order','business_presentation','webinar_invitation'],true))throw new RuntimeException('Action unavailable.');
    $lock='advisor_action_'.$mid.'_'.$index;
    if(empty(fcc_partner_one('SELECT GET_LOCK(?,2) acquired',[$lock])['acquired']))throw new RuntimeException('Retry later.');
    try {
        $seen=fcc_partner_one("SELECT fcc_ai_event_id FROM fcc_ai_events WHERE fcc_ai_conversation_id=? AND event_type='advisor_action' AND JSON_UNQUOTE(JSON_EXTRACT(meta,'$.action_key'))=? LIMIT 1",[(int)$c->fcc_ai_conversation_id,$lock]);
        if(!$seen)fcc_ai_log_event(['fcc_ai_conversation_id'=>(int)$c->fcc_ai_conversation_id,'user_id'=>(int)$c->user_id,'link_id'=>(int)$c->link_id,'assistant_type'=>'product_advisor','event_type'=>'advisor_action','meta'=>['action_key'=>$lock,'kind'=>$a['kind']==='owner_medical_draft'?'owner_whatsapp':$a['kind'],'version'=>$meta['version']??'','market'=>$meta['market']??'','language'=>$c->language]]);
        return ['accepted'=>true,'deduplicated'=>(bool)$seen];
    } finally {fcc_partner_query('SELECT RELEASE_LOCK(?)',[$lock]);}
}

function fcc_advisor_hub(int $uid,array $input): array {
    $tab=in_array($input['view']??'', ['overview','conversations','contacts','settings'],true)?$input['view']:'overview';
    $days=in_array((int)($input['days']??30),[7,10,30,90],true)?(int)($input['days']??30):30;
    $since=gmdate('Y-m-d H:i:s',time()-$days*86400);$q=mb_substr(trim((string)($input['q']??'')),0,100);
    $lid=max(0,(int)($input['card']??0));$lang=preg_match('/^[a-z]{2}$/D',$input['lang']??'')?$input['lang']:'';
    $cards=fcc_partner_rows("SELECT link_id,url,is_enabled FROM links WHERE user_id=? AND type='biolink' ORDER BY link_id",[$uid]);
    $where="c.user_id=? AND c.assistant_type='product_advisor' AND COALESCE(c.last_message_at,c.datetime)>=?";$params=[$uid,$since];
    if($lid){$where.=' AND c.link_id=?';$params[]=$lid;}
    if($lang){$where.=' AND c.language=?';$params[]=$lang;}
    if($q!==''){$where.=" AND (c.public_id LIKE ? OR EXISTS(SELECT 1 FROM fcc_ai_messages m WHERE m.fcc_ai_conversation_id=c.fcc_ai_conversation_id AND m.content LIKE ?))";$params[]='%'.$q.'%';$params[]='%'.$q.'%';}
    if(($input['status']??'')==='captured')$where.=" AND c.lead_status='captured'";
    $conversations=fcc_partner_rows("SELECT c.fcc_ai_conversation_id,c.public_id,c.link_id,c.language,c.lead_status,c.last_message_at,c.meta,l.url FROM fcc_ai_conversations c LEFT JOIN links l ON l.link_id=c.link_id AND l.user_id=c.user_id WHERE $where ORDER BY c.last_message_at DESC LIMIT 100",$params);
    $stats=fcc_partner_one("SELECT COUNT(*) conversations,SUM(lead_status='captured') leads FROM fcc_ai_conversations WHERE user_id=? AND assistant_type='product_advisor' AND COALESCE(last_message_at,datetime)>=?",[$uid,$since]);
    $actions=fcc_partner_rows("SELECT JSON_UNQUOTE(JSON_EXTRACT(meta,'$.kind')) kind,COUNT(*) total FROM fcc_ai_events WHERE user_id=? AND assistant_type='product_advisor' AND event_type='advisor_action' AND datetime>=? GROUP BY kind",[$uid,$since]);
    $reasons=fcc_partner_rows("SELECT COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(m.meta,'$.fallback_reason')),''),'ai_answer') reason,COUNT(*) total FROM fcc_ai_messages m JOIN fcc_ai_conversations c ON c.fcc_ai_conversation_id=m.fcc_ai_conversation_id WHERE c.user_id=? AND c.assistant_type='product_advisor' AND m.role='assistant' AND m.message_type='chat' AND m.datetime>=? GROUP BY reason",[$uid,$since]);
    fcc_partner_sync_leads($uid);
    $contacts=fcc_partner_rows("SELECT DISTINCT p.id,p.name,p.email,p.phone,p.next_followup,p.updated_at FROM fcc_partner_contacts p JOIN fcc_partner_contact_sources s ON s.contact_id=p.id AND s.user_id=p.user_id JOIN data d ON d.datum_id=s.source_id AND d.user_id=s.user_id AND s.source_kind='data' WHERE p.user_id=? AND d.type='ai_chat_lead' AND p.archived_at IS NULL ORDER BY p.created_at DESC LIMIT 100",[$uid]);
    $assistant=db()->where('user_id',$uid)->where('assistant_type','product_advisor')->getOne('fcc_ai_assistants');
    $selected=null;$messages=[];
    if(!empty($input['conversation'])) {
        $selected=db()->where('user_id',$uid)->where('assistant_type','product_advisor')->where('public_id',mb_substr((string)$input['conversation'],0,64))->getOne('fcc_ai_conversations');
        if($selected){$tab='conversations';$messages=fcc_ai_get_conversation_messages((int)$selected->fcc_ai_conversation_id,120,fcc_ai_build_feedback_actor('owner',(string)$uid,$uid));}
    }
    return compact('tab','days','q','lid','lang','cards','conversations','stats','actions','reasons','contacts','assistant','selected','messages');
}
