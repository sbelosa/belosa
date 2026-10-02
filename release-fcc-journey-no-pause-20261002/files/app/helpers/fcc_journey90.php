<?php
/** Versioned Moj put runtime. Legacy progress remains intact. No external messages are sent here. */
defined('ALTUMCODE') || die();

function fcc_j90_catalog(string $version='fcc90-2026-09-24-v4'): array {
 static $cache=[];
 if($version!=='fcc90-2026-09-24-v4') throw new InvalidArgumentException(fcc_t('Ova verzija programa nije dostupna.'));
 if(!isset($cache[$version])) {
  $c=json_decode(file_get_contents(__DIR__.'/../config/curriculum/fcc90.hr.v4.json'),true,512,JSON_THROW_ON_ERROR);
  if($c['schema_version']!==2||$c['catalog_version']!==$version||count($c['tasks'])!==90)throw new RuntimeException(fcc_t('Invalid journey catalog'));
  $cache[$version]=$c;
 }
 return $cache[$version];
}
function fcc_j90_schema(): bool {
 static $ready;
 return $ready??=!!fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_partner_journey_cycles'");
}
function fcc_j90_enabled(int $uid): bool {
 return fcc_journey_enabled($uid)&&fcc_j90_schema()&&fcc_j90_quality_schema()&&(getenv('FCC_JOURNEY90_ENABLED')==='1'||(getenv('FCC_LOCAL')==='1'&&getenv('FCC_JOURNEY90_ENABLED')!=='0'));
}
function fcc_j90_quality_schema(): bool {
 static $ready;
 return $ready??=!!fcc_partner_one("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='forever_business_daily_outcomes' AND COLUMN_NAME='journey_alternative_reason'");
}
function fcc_j90_cycle(int $uid): ?array {
 if(!fcc_j90_schema())return null;
 // A finished/paused cycle also prevents fallback to legacy writes, including during rollback.
 return fcc_partner_one('SELECT * FROM fcc_partner_journey_cycles WHERE user_id=? ORDER BY id DESC LIMIT 1',[$uid]);
}
function fcc_j90_now(?DateTimeImmutable $now=null): DateTimeImmutable {
 return ($now??new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('Europe/Zagreb'));
}
function fcc_j90_access(int $uid): array {
 if(!fcc_j90_enabled($uid))throw new InvalidArgumentException(fcc_t('Novi program trenutačno nije uključen. Povijest ostaje sačuvana.'));
 $u=fcc_journey_user($uid);
 if(!in_array(fcc_locale((string)\Altum\Language::$name), ['hr','sl','en','de','sr', 'sq', 'cnr', 'fr', 'es'], true))throw new InvalidArgumentException(fcc_t('Odaberi dostupan jezik u postavkama.'));
 $state=forever_business_get_vip_program_state($uid);
 if(empty($state['can_access_education']))throw new InvalidArgumentException(fcc_t('Za ovaj program potreban je postojeći odobren pristup edukaciji.'));
 $videos=fcc_journey_videos($u);
 if($videos['enabled']&&!$videos['completed'])throw new InvalidArgumentException(fcc_t('Najprije dovrši svoj postojeći uvod u Materijalima.'));
 return $state;
}
function fcc_j90_rows(int $uid,int $cycle): array {
 return fcc_partner_rows("SELECT o.*,s.content_json FROM forever_business_daily_outcomes o JOIN fcc_partner_journey_steps s ON s.id=o.journey_step_id AND s.user_id=o.recorded_by_user_id AND s.cycle_id=o.journey_cycle_id WHERE o.recorded_by_user_id=? AND o.journey_cycle_id=? AND o.status='done' ORDER BY o.sequence_position",[$uid,$cycle]);
}
function fcc_j90_readiness(array $cycle,array $rows): array {
 $c=fcc_j90_catalog($cycle['catalog_version']);$level=(int)$cycle['accepted_level'];$rule=$c['levels'][min(3,$level)];
 $seen=[];$interactions=[];$applied=0;$external=0;$advanced=0;$cores=[];$valid=true;
 foreach($rows as $index=>$row)if((int)$row['sequence_position']!==$index+1||(int)$row['recorded_by_user_id']!==(int)$cycle['user_id']||(int)$row['journey_cycle_id']!==(int)$cycle['id'])$valid=false;
 foreach(array_slice($rows,-max(1,$rule['window'])) as $r) {
  $s=json_decode($r['content_json'],true);if(!$s||isset($seen[$r['journey_step_id']])){$valid=false;continue;}$seen[$r['journey_step_id']]=true;
  if($r['learning_mode']!=='applied'||!in_array($r['output_status'],$s['variant']['allowed_output_statuses'],true))continue;
  $applied++;$cores[$s['core']]=true;
  if(!empty($s['variant']['external_event_eligible'])&&in_array($r['output_status'],['sent','performed'],true)&&!empty($r['interaction_id'])&&!isset($interactions[$r['interaction_id']])) {
   $interactions[$r['interaction_id']]=true;$external++;
   if(!empty($s['variant']['advanced_case_eligible'])&&!empty($r['advanced_case']))$advanced++;
  }
 }
 $checks=['steps'=>count($rows)>=$rule['min_completed'],'applied'=>$applied>=$rule['min_applied'],'external'=>$external>=$rule['min_external'],'cores'=>count($cores)>=$rule['min_cores'],'advanced'=>$advanced>=$rule['min_advanced'],'sequence'=>$valid];
 return ['policy'=>'fcc90_progress_v2','level'=>$level,'proposed_level'=>$level<4&&!in_array(false,$checks,true)?$level+1:null,'counts'=>['completed'=>count($rows),'applied'=>$applied,'external'=>$external,'cores'=>count($cores),'advanced'=>$advanced],'checks'=>$checks,'rule'=>$rule];
}
function fcc_j90_content(int $position,int $level=1,string $version='fcc90-2026-09-24-v4'): array {
 $c=fcc_j90_catalog($version);
 if($position<1||$position>90||$level<1||$level>4)throw new InvalidArgumentException(fcc_t('Odaberi dostupni korak i razinu.'));
 $t=$c['tasks'][$position-1];$t['variant']=$t['variants'][$level-1];unset($t['variants']);
 $t['catalog_version']=$version;$t['policy_version']=$c['policy']['id'];return $t;
}
function fcc_j90_daily_done(int $uid,string $day): bool {
 return !!fcc_partner_one("SELECT outcome_id FROM forever_business_daily_outcomes WHERE recorded_by_user_id=? AND action_date=? AND status='done' AND ((action_key LIKE 'vip26\\_%' AND action_key<>'vip26_activator_d01') OR journey_cycle_id IS NOT NULL) LIMIT 1",[$uid,$day]);
}
function fcc_j90_webinar(DateTimeImmutable $now): array {
 $event=forever_business_get_marketing_plan_state($now);
 if(function_exists('fcc_events_enabled')&&fcc_events_enabled())return $event;
 $override=fcc_partner_notification_config('journey90_webinar_'.$now->format('Y-m-d'),null);
 if($override==='cancelled'){$event['is_today']=false;$event['cancelled']=true;}
 return $event;
}
function fcc_j90_state(int $uid,array $access,?DateTimeImmutable $now=null): ?array {
 $cycle=fcc_j90_cycle($uid);if(!$cycle)return null;
 if(fcc_jp_is_cycle($cycle))return fcc_jp_state($uid,$access,$now);
 $now=fcc_j90_now($now);$day=$now->format('Y-m-d');$rows=fcc_j90_rows($uid,(int)$cycle['id']);$position=count($rows)+1;
 $paused=false; // Existing cycles retain progress and ignore retired pause dates.
 $done=fcc_j90_daily_done($uid,$day);$webinar=fcc_j90_webinar($now);$enabled=fcc_j90_enabled($uid);
 $step=$position<=90?fcc_partner_one('SELECT * FROM fcc_partner_journey_steps WHERE user_id=? AND cycle_id=? AND position=?',[$uid,(int)$cycle['id'],$position]):null;
 $content=$step?json_decode($step['content_json'],true):($position<=90?fcc_j90_content($position,(int)$cycle['accepted_level'],$cycle['catalog_version']):null);
 $due=fcc_partner_rows("SELECT id,name,next_followup,sales_stage,business_stage FROM fcc_partner_contacts WHERE user_id=? AND next_followup<=? AND contact_permission<>'do_not_contact' ORDER BY next_followup,id LIMIT 3",[$uid,$day]);
 $recent=array_slice($rows,-3);$needsHelp=count($recent)>=3&&count(array_filter($recent,fn($r)=>$r['learning_mode']==='practice'))===3;
 $needsHelp=$needsHelp||count(array_filter($recent,fn($r)=>$r['difficulty']==='hard'))>=2;
 $action=$content?['key'=>$content['id'],'core'=>$content['core'],'title'=>$content['title'],'instruction'=>implode(' ',$content['variant']['steps']),'success_definition'=>$content['variant']['criterion'],'fallback'=>$content['practice']['instruction'],'message_example'=>$content['communication']['example'],'sequence_position'=>$position,'sequence_total'=>90,'target'=>1,'quick_target'=>1,'track_label'=>'Moj put · 90 koraka','can_complete'=>$enabled&&!$done&&!empty($access['can_access_education'])&&$step&&!$webinar['is_today'],'is_daily_complete'=>$done,'is_program_complete'=>false]:['key'=>'journey90_complete','title'=>'Moj 4 Core ritam','can_complete'=>false,'is_program_complete'=>true];
 if($webinar['is_today']&&!$done)$action=array_merge($action,['key'=>'vip26_sunday_'.$now->format('Ymd'),'title'=>'Webinar u '.$webinar['time_label'],'is_weekly_plan'=>true,'can_complete'=>$enabled&&!empty($access['can_access_education'])&&$webinar['can_record_outcome'],'is_waiting_for_event_completion'=>!$webinar['can_record_outcome']]);
 return ['cycle'=>$cycle,'rows'=>$rows,'position'=>min(90,$position),'completed'=>count($rows),'step'=>$step,'content'=>$content,'draft'=>$step?json_decode($step['draft_json']??'{}',true):[],'enabled'=>$enabled,'paused'=>$paused,'daily_done'=>$done,'webinar'=>$webinar,'action'=>$action,'readiness'=>fcc_j90_readiness($cycle,$rows),'due'=>$due,'needs_help'=>$needsHelp,'cc'=>fcc_j90_cc($uid,$access,$now)];
}
/** All mutation paths take the same user lock as the legacy completion endpoint. */
function fcc_j90_mutate(int $uid,string $operation,array $in,?DateTimeImmutable $now=null): int {
 if($now&&PHP_SAPI!=='cli')throw new InvalidArgumentException(fcc_t('Datum određuje sustav.'));
 $now=fcc_j90_now($now);$day=$now->format('Y-m-d');$at=$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
 $key=(string)($in['request_key']??'');if(!preg_match('/^[a-f0-9]{32}$/D',$key))throw new InvalidArgumentException(fcc_t('Osvježi obrazac prije spremanja.'));
 $payload=$in;unset($payload['token'],$payload['request_key']);ksort($payload);$hash=hash('sha256',$operation.json_encode($payload));
 fcc_journey_require_team_group($uid);
 fcc_j90_access($uid); // Initialize legacy schema/rights before opening our transaction.
 database()->begin_transaction();
 try {
  if(!fcc_partner_one('SELECT user_id FROM users WHERE user_id=? AND status=1 FOR UPDATE',[$uid]))throw new InvalidArgumentException(fcc_t('Račun nije aktivan.'));
  $old=fcc_partner_one('SELECT * FROM fcc_partner_journey_requests WHERE user_id=? AND request_key=?',[$uid,$key]);
  if($old){if($old['operation']!==$operation||!hash_equals($old['payload_hash'],$hash))throw new InvalidArgumentException(fcc_t('Ovaj zahtjev već je spremljen s drugim podacima. Osvježi prikaz.'));database()->commit();return (int)$old['result_id'];}
  $access=fcc_j90_access($uid);$cycle=fcc_j90_cycle($uid);$result=0;
  if(fcc_jp_is_cycle($cycle)&&$operation!=='webinar')throw new InvalidArgumentException(fcc_t('Nastavi u aktualnom pilotu. Stari program ostaje u povijesti.'));
  if($operation==='start') {
   if($cycle)throw new InvalidArgumentException(fcc_t('Tvoj program već postoji. Nastavi gdje si stao.'));
   if(empty($in['confirm']))throw new InvalidArgumentException(fcc_t('Potvrdi da želiš krenuti od prvog koraka uz sačuvanu povijest.'));
   $c=fcc_j90_catalog();fcc_partner_query("INSERT INTO fcc_partner_journey_cycles (user_id,catalog_version,policy_version,started_at) VALUES (?,?,?,?)",[$uid,$c['catalog_version'],$c['policy']['id'],$at]);$result=(int)database()->insert_id;
   fcc_journey_event($uid,'cycle_started','cycle:'.$result,$c['catalog_version'],'cycle:'.$result);
  } else {
   if(!$cycle||$cycle['status']!=='active')throw new InvalidArgumentException(fcc_t('Nema aktivnog nedovršenog programa.'));
   if((int)($in['cycle_id']??0)!==(int)$cycle['id']||(int)($in['cycle_version']??0)!==(int)$cycle['version'])throw new InvalidArgumentException(fcc_t('Program se promijenio na drugom uređaju. Osvježi prikaz.'));
   $s=fcc_j90_state($uid,$access,$now);
   if($operation==='level') {
    $level=$s['readiness']['proposed_level'];if(!$level||(int)($in['level']??0)!==$level)throw new InvalidArgumentException(fcc_t('Prijedlog razine više nije aktualan.'));
    fcc_partner_query('UPDATE fcc_partner_journey_cycles SET accepted_level=?,version=version+1 WHERE id=?',[$level,(int)$cycle['id']]);
    fcc_partner_query('INSERT INTO fcc_partner_journey_level_events (user_id,cycle_id,level,policy_version,evidence_json,created_at) VALUES (?,?,?,?,?,?)',[$uid,(int)$cycle['id'],$level,$cycle['policy_version'],json_encode($s['readiness']),$at]);$result=(int)database()->insert_id;
    fcc_journey_event($uid,'level_accepted','cycle:'.$cycle['id'],$cycle['catalog_version'],'j90level:'.$cycle['id'].':'.$level);
   } elseif($operation==='webinar') {
    if(!$s['webinar']['is_today']||!$s['webinar']['can_record_outcome']||$s['daily_done'])throw new InvalidArgumentException(fcc_t('Potvrda se otvara nakon webinara, najviše jednom dnevno.'));
    $mode=fcc_partner_choice($in['learning_mode']??'',['applied'=>1,'practice'=>1]);
    if(empty($in['self_check']))throw new InvalidArgumentException(fcc_t('Potvrdi da si sudjelovao/la ili prošao/la dostupni materijal.'));
    $note=fcc_partner_text($in['note']??'',500);if($note==='')throw new InvalidArgumentException(fcc_t('Napiši jednu korisnu ideju iz webinara ili materijala.'));
    fcc_partner_query("INSERT INTO forever_business_daily_outcomes (fbo_id,action_date,core_key,action_key,status,outcome_count,outcome_type,result_type,difficulty,recorded_by_user_id,completion_mode,learning_mode,output_status,created_at,updated_at,note) VALUES (?,?,'Development',?,'done',1,'weekly',?,'ok',?,'standard',?,?,?, ?,?)",[$access['fbo_id'],$day,'vip26_sunday_'.$now->format('Ymd'),$mode==='practice'?'training':'event',$uid,$mode,$mode==='practice'?'practised':'performed',$at,$at,$note]);$result=(int)database()->insert_id;
    fcc_journey_event($uid,'webinar_done','cycle:'.$cycle['id'],$cycle['catalog_version'],'webinar:'.$uid.':'.$day);
   } elseif($operation==='open') {
    if($s['daily_done']||$s['webinar']['is_today'])throw new InvalidArgumentException(fcc_t('Sljedeći redovni korak otvara se idući dan.'));
    if($s['step'])$result=(int)$s['step']['id'];
    else {
     $level=(int)($in['level']??$cycle['accepted_level']);if($level<1||$level>(int)$cycle['accepted_level'])throw new InvalidArgumentException(fcc_t('Odaberi svoju ili lakšu razinu.'));
     $t=fcc_j90_content($s['position'],$level,$cycle['catalog_version']);
     $t['material']=fcc_journey_material(['key'=>$t['id'],'instruction'=>implode(' ',$t['variant']['steps']),'message_example'=>$t['communication']['example']],'Hrvatski');
     fcc_partner_query('INSERT INTO fcc_partner_journey_steps (cycle_id,user_id,position,content_json,opened_at) VALUES (?,?,?,?,?)',[(int)$cycle['id'],$uid,$s['position'],json_encode($t,JSON_UNESCAPED_UNICODE),$at]);$result=(int)database()->insert_id;
     fcc_journey_event($uid,'started','j90:'.$cycle['id'].':'.$result,$cycle['catalog_version'],'j90open:'.$result);
    }
   } elseif(in_array($operation,['draft','compose','complete'],true)) {
    $step=$s['step'];if(!$step||(int)($in['step_id']??0)!==(int)$step['id'])throw new InvalidArgumentException(fcc_t('Korak nije aktualan ili ne pripada tvojem programu.'));
    if((int)($in['draft_version']??-1)!==(int)$step['draft_version'])throw new InvalidArgumentException(fcc_t('Nacrt je promijenjen u drugom prozoru. Osvježi prije spremanja.'));
    $content=$s['content'];$draft=$s['draft']?:[];
    if($operation!=='complete') {
     if($operation==='compose')$draft=array_merge($draft,fcc_j90_compose($uid,$content,$in));
     else {
      $message=fcc_partner_text($in['message']??$draft['message']??'',3000);
      if(array_key_exists('message',$in)&&$content['communication']['composer']['kind']==='article'&&(array_key_exists('article_id',$in)||!empty($draft['article_id']))) {
       $source=fcc_j90_compose($uid,$content,['article_id'=>$in['article_id']??$draft['article_id'],'country'=>$in['country']??$draft['country']??'']);
       $draft=array_merge($draft,$source);$message=fcc_j90_with_referral($message,$source['referral_url']);
      }
      $draft['message']=$message;$draft['note']=fcc_partner_text($in['note']??$draft['note']??'',500);
     }
     fcc_partner_query('UPDATE fcc_partner_journey_steps SET draft_json=?,draft_version=draft_version+1 WHERE id=?',[json_encode($draft,JSON_UNESCAPED_UNICODE),(int)$step['id']]);$result=(int)$step['id'];
     fcc_journey_event($uid,'draft_saved','j90:'.$cycle['id'].':'.$step['id'],$cycle['catalog_version'],'j90draft:'.$step['id'].':'.((int)$step['draft_version']+1));
    } else {
     if(!$s['action']['can_complete'])throw new InvalidArgumentException(fcc_t('Ovaj se korak sada ne može potvrditi. Osvježi prikaz.'));
     if(empty($in['self_check']))throw new InvalidArgumentException(fcc_t('Potvrdi kriterij dovršenja ili odaberi vježbu.'));
     $mode=fcc_partner_choice($in['learning_mode']??'',['applied'=>1,'practice'=>1]);
     $output=fcc_partner_choice($in['output_status']??'',array_fill_keys($mode==='practice'?['practised']:$content['variant']['allowed_output_statuses'],1));
     $difficulty=fcc_partner_choice($in['difficulty']??'',['easy'=>1,'ok'=>1,'hard'=>1]);
     $note=fcc_partner_text($in['note']??$draft['note']??'',500);$interaction=null;$advanced=0;
     if($mode==='applied'&&in_array($output,['sent','performed'],true)) {
      if(empty($content['variant']['external_event_eligible'])||empty($in['contact_permission']))throw new InvalidArgumentException(fcc_t('Potvrdi stvarnu radnju i da je osoba htjela kontakt ili pomoć.'));
      $contact=null;if(!empty($in['contact_id'])){$contact=fcc_partner_contact($uid,(int)$in['contact_id']);if($contact['contact_permission']==='do_not_contact')throw new InvalidArgumentException(fcc_t('Ova osoba ne želi kontakt.'));}
      if(!empty($in['interaction_id'])) {
       $r=fcc_partner_one('SELECT * FROM fcc_partner_activities WHERE id=? AND user_id=? FOR UPDATE',[(int)$in['interaction_id'],$uid]);
       if(!$r||!in_array($r['kind'],['contacted','journey_sent','journey_performed'],true)|| (new DateTimeImmutable($r['created_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Y-m-d')!==$day)throw new InvalidArgumentException(fcc_t('Radnja mora biti tvoja stvarna današnja komunikacija.'));
       if($r['contact_id']){$owner=fcc_partner_contact($uid,(int)$r['contact_id']);if($owner['contact_permission']==='do_not_contact'||($contact&&(int)$contact['id']!==(int)$r['contact_id']))throw new InvalidArgumentException(fcc_t('Kontakt ne odgovara ovoj radnji.'));}
       if(fcc_partner_one('SELECT outcome_id FROM forever_business_daily_outcomes WHERE interaction_id=?',[(int)$r['id']]))throw new InvalidArgumentException(fcc_t('Ta je radnja već povezana s korakom.'));$interaction=(int)$r['id'];
      } else {
       fcc_partner_query('INSERT INTO fcc_partner_activities (user_id,contact_id,kind,note,request_key,created_at) VALUES (?,?,?,?,?,?)',[$uid,$contact?(int)$contact['id']:null,'journey_'.$output,'Korisnička potvrda uz '.$content['id'],$key,$at]);$interaction=(int)database()->insert_id;
      }
      if(!empty($in['advanced_case'])) {
       if(empty($content['variant']['advanced_case_eligible'])||mb_strlen($note)<30||empty($in['source_checked']))throw new InvalidArgumentException(fcc_t('Za napredni slučaj sažmi razlog svojeg odgovora i potvrdi provjeru izvora.'));$advanced=1;
      }
     } elseif(!empty($in['interaction_id'])||!empty($in['advanced_case']))throw new InvalidArgumentException(fcc_t('Vježba ili priprema nije stvarna komunikacija ni napredni slučaj.'));
     fcc_partner_query("INSERT INTO forever_business_daily_outcomes (fbo_id,action_date,core_key,action_key,status,outcome_count,outcome_type,result_type,difficulty,sequence_position,note,recorded_by_user_id,completion_mode,journey_cycle_id,journey_step_id,learning_mode,output_status,interaction_id,advanced_case,created_at,updated_at) VALUES (?,?,?,?,'done',1,'journey90',?,?,?,?,?,'standard',?,?,?,?,?,?,?,?)",[$access['fbo_id'],$day,$content['core'],$content['id'],$mode==='practice'?'training':$content['completion']['result_type_applied'],$difficulty,$s['position'],$note,$uid,(int)$cycle['id'],(int)$step['id'],$mode,$output,$interaction,$advanced,$at,$at]);$result=(int)database()->insert_id;
     $minutes=isset($in['active_minutes'])&&$in['active_minutes']!==''?filter_var($in['active_minutes'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>180]]):null;
     if($minutes===false)throw new InvalidArgumentException(fcc_t('Upiši 1–180 minuta aktivnog rada ili ostavi prazno.'));
     $reason=$mode==='practice'?fcc_partner_choice($in['alternative_reason']??'other',['no_opportunity'=>1,'time'=>1,'unclear'=>1,'other'=>1]):null;
     fcc_partner_query('UPDATE forever_business_daily_outcomes SET journey_minutes=?,journey_alternative_reason=? WHERE outcome_id=?',[$minutes,$reason,$result]);
     if($interaction)fcc_journey_event($uid,'interaction_confirmed','j90:'.$cycle['id'].':'.$step['id'],$cycle['catalog_version'],'j90interaction:'.$interaction);
     fcc_partner_query("UPDATE fcc_partner_journey_cycles SET version=version+1,status=?,completed_at=? WHERE id=?",[$s['position']===90?'completed':'active',$s['position']===90?$at:null,(int)$cycle['id']]);
     fcc_journey_event($uid,'step_completed','j90:'.$cycle['id'].':'.$step['id'],$cycle['catalog_version'],'j90done:'.$step['id']);
    }
   } else throw new InvalidArgumentException(fcc_t('Radnja nije prepoznata.'));
  }
  fcc_partner_query('INSERT INTO fcc_partner_journey_requests (user_id,request_key,payload_hash,result_id,operation,created_at) VALUES (?,?,?,?,?,?)',[$uid,$key,$hash,$result,$operation,$at]);
  database()->commit();return $result;
 } catch(Throwable $e){database()->rollback();throw $e;}
}
/** Server-owned source data: never accept a referral from a form, cookie or URL. */
function fcc_j90_source_message(array $content,?array $article,string $ref,array $in=[]): array {
 $id=$article?(int)$article['blog_post_id']:0;
 $title=$article?html_entity_decode(strip_tags($article['title']),ENT_QUOTES,'UTF-8'):'';
 $link=$article?url('blog/'.$article['url'].'?'.http_build_query(['ref'=>$ref],'','&',PHP_QUERY_RFC3986)):'';
 return ['message'=>strtr(fcc_t($content['communication']['composer']['template']),['{title}'=>$title,'{link}'=>$link]),'article_id'=>$id,'referral_url'=>$link,'prepared_at'=>gmdate('c'),'template_version'=>$content['content_version'],'country'=>fcc_partner_text($in['country']??'',60)];
}
function fcc_j90_compose(int $uid,array $content,array $in): array {
 $u=fcc_journey_user($uid);if(!fcc_journey_pro($u))throw new InvalidArgumentException(fcc_t('Priprema za WhatsApp dostupna je uz aktivni PRO. Osnovni primjer ostaje dostupan.'));
 $kind=$content['communication']['composer']['kind'];if($kind==='none')throw new InvalidArgumentException(fcc_t('Ovaj korak ne traži slanje poruke.'));
 $article=null;$ref='';
 if($kind==='article') {
  $article=fcc_partner_one('SELECT blog_post_id,title,url FROM blog_posts WHERE blog_post_id=? AND is_published=1 AND language=?',[(int)($in['article_id']??0),(string)\Altum\Language::$name]);
  $cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));
  if(!$article||!$cards)throw new InvalidArgumentException(fcc_t('Odaberi objavljen članak i provjeri imaš li aktivnu osobnu karticu.'));
  $ref=$cards[0]['url'];
 }
 return fcc_j90_source_message($content,$article,$ref,$in);
}
/** Build available choices once per page; selection itself needs no write or extra click. */
function fcc_j90_message_options(int $uid,array $content): array {
 $u=fcc_journey_user($uid);if(!fcc_journey_pro($u))throw new InvalidArgumentException(fcc_t('Priprema za WhatsApp dostupna je uz aktivni PRO.'));
 $kind=$content['communication']['composer']['kind'];
 if($kind==='none')return [];
 if($kind==='text')return [0=>fcc_j90_source_message($content,null,'')];
 $cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));
 if(!$cards)throw new InvalidArgumentException(fcc_t('Za osobnu poveznicu najprije aktiviraj svoju karticu.'));
 $options=[];
 foreach(fcc_partner_rows('SELECT blog_post_id,title,url,image FROM blog_posts WHERE is_published=1 AND language=? ORDER BY title',[(string)\Altum\Language::$name]) as $article) {
  $options[(int)$article['blog_post_id']]=fcc_j90_source_message($content,$article,$cards[0]['url'])+['title'=>html_entity_decode($article['title'],ENT_QUOTES,'UTF-8'),'image'=>$article['image']?\Altum\Uploads::get_full_url('blog').$article['image']:''];
 }
 return $options;
}
/** Preserve edited prose; the selected article always carries the signed-in owner's link. */
function fcc_j90_with_referral(string $message,string $link): string {
 if($link==='')return $message;
 $source=parse_url($link);
 $message=preg_replace_callback('~https?://[^\s<>"\x27]+~u',static function($match)use($source,$link){
  $raw=$match[0];$url=rtrim($raw,".,;!?) ]}");$p=parse_url($url);
  if($p&&strtolower($p['host']??'')===strtolower($source['host']??'')&&($p['port']??null)===($source['port']??null)&&($p['path']??'')===($source['path']??''))return $link.substr($raw,strlen($url));
  return $raw;
 },$message);
 if(!str_contains($message,$link))$message=rtrim($message)."\n".$link;
 if(mb_strlen($message)>3000)throw new InvalidArgumentException(fcc_t('Skrati poruku kako bi u nju stala i tvoja osobna poveznica.'));
 return $message;
}
/** Called after sign-in on the server, never uses a recipient number. */
function fcc_j90_whatsapp_url(string $message): string {
 if(trim($message)===''||mb_strlen($message)>3000||preg_match('/\[[^\]]+\]|\{(?:title|link)\}/u',$message))throw new InvalidArgumentException(fcc_t('Prije otvaranja dopuni označena mjesta u poruci.'));
 return 'https://wa.me/?text='.rawurlencode($message);
}

/** Conservative FLP360 provenance display. Missing region/finality evidence is not inferred. */
function fcc_j90_cc(int $uid,array $access,DateTimeImmutable $now): array {
 $fbo=(string)($access['fbo_id']??'');$result=[];
 $month=$now->modify('first day of this month')->setTime(0,0);
 if(!preg_match('/^\d{12}$/D',$fbo))return [['period'=>$month->format('Y-m-01'),'status'=>'conflict','personal_cc'=>null,'active'=>null,'checked_at'=>null,'reason'=>'Forever ID nije pouzdano povezan.']];
 for($i=0;$i<3;$i++) {
  $period=$month->modify('-'.$i.' months')->format('Y-m-01');
  $r=fcc_partner_one('SELECT m.personal_cc,m.is_4cc_active,m.source_import_id,i.status import_status,i.report_kind,i.file_sha256,i.completed_at,i.summary_json FROM forever_business_metrics m LEFT JOIN forever_business_imports i ON i.import_id=m.source_import_id WHERE m.fbo_id=? AND m.period_month=?',[$fbo,$period]);
  $entry=['period'=>$period,'status'=>'unavailable','personal_cc'=>$r&&$r['personal_cc']!==null?(float)$r['personal_cc']:null,'active'=>$r&&$r['is_4cc_active']!==null?(bool)$r['is_4cc_active']:null,'checked_at'=>null,'reason'=>'Za ovaj mjesec još nema dostupnih podataka.'];
  if($r) {
   $entry['status']='pending';$entry['reason']='Vrijednost je dostupna, ali potpuna potvrda izvora još nije dostupna.';
   $summary=json_decode($r['summary_json']??'{}',true)?:[];
   if(!empty($summary['identity_conflict'])||!empty($summary['conflict'])){$entry['status']='conflict';$entry['reason']='Izvor ima oznaku neslaganja; treba provjeru.';}
   elseif($r['import_status']==='completed'&&$r['completed_at']&&in_array($r['report_kind'],['downline','four_cc_active'],true)) {
    $checked=new DateTimeImmutable($r['completed_at'],new DateTimeZone('UTC'));
    $sync=fcc_partner_one('SELECT MAX(checked_at) at FROM forever_business_sync_checks WHERE import_id=? AND report_kind=? AND file_sha256=?',[(int)$r['source_import_id'],$r['report_kind'],$r['file_sha256']]);
    if($sync['at']??null){$candidate=new DateTimeImmutable($sync['at'],new DateTimeZone('UTC'));if($candidate>$checked&&$candidate<=$now)$checked=$candidate;}
    $entry['checked_at']=$checked->format(DateTimeInterface::ATOM);
    if($checked>$now){$entry['status']='conflict';$entry['reason']='Vrijeme izvora nije valjano.';}
    elseif($i===0&&$now->getTimestamp()-$checked->getTimestamp()>72*3600){$entry['status']='stale';$entry['reason']='Podaci tekućeg mjeseca stariji su od 72 sata.';}
    elseif(($summary['region_verified']??false)===true&&($i===0||in_array($period,$summary['closed_periods']??[],true))){$entry['status']='verified';$entry['reason']='Potvrđen uspješan izvor, regija i odgovarajući period.';}
   }
  }
  $result[]=$entry;
 }
 return $result;
}
