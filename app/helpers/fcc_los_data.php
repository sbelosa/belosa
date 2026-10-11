<?php
defined('ALTUMCODE') || die();

/** LOS v1: read models share half-open UTC ranges; source daily counters use Zagreb dates. */
function fcc_los_schema(string $table): array {
 static $schemas=[];
 if(!preg_match('/^[a-z_]+$/D',$table))return [];
 if(!array_key_exists($table,$schemas)) {
  $exists=fcc_partner_one('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table]);
  $schemas[$table]=$exists?array_column(fcc_partner_rows("SHOW COLUMNS FROM `$table`"),'Type','Field'):[];
 }
 return $schemas[$table];
}
function fcc_los_range(array $input,?DateTimeImmutable $now=null): array {
 $tz=new DateTimeZone('Europe/Zagreb');$now=($now??new DateTimeImmutable('now',$tz))->setTimezone($tz);
 $date=static function($s)use($tz){if(!is_string($s))throw new InvalidArgumentException('Provjeri datum.');$d=DateTimeImmutable::createFromFormat('!Y-m-d',$s,$tz);if(!$d||$d->format('Y-m-d')!==$s)throw new InvalidArgumentException('Provjeri datum.');return $d;};
 $days=max(1,min(366,(int)($input['days']??30)));$today=$now->setTime(0,0);
 $end=!empty($input['to'])?$date($input['to'])->modify('+1 day'):(!empty($input['today'])?$today->modify('+1 day'):$today);
 $start=!empty($input['from'])?$date($input['from']):$end->modify('-'.$days.' days');
 if($start>=$end||$end>$today->modify('+1 day')||$start->diff($end)->days>366)throw new InvalidArgumentException('Odaberi razdoblje od 1 do 366 dana, najkasnije do danas.');
 $days=(int)$start->diff($end)->days;$effectiveEnd=$end>$now?$now:$end;$previous=$start->modify('-'.$days.' days');
 $utc=static fn($d)=>$d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
 return ['from'=>$start->format('Y-m-d'),'to'=>$end->modify('-1 day')->format('Y-m-d'),'to_exclusive'=>$end->format('Y-m-d'),'start'=>$utc($start),'end'=>$utc($effectiveEnd),'previous_start'=>$utc($previous),'previous_end'=>$utc($start),'previous_from'=>$previous->format('Y-m-d'),'previous_to_exclusive'=>$start->format('Y-m-d'),'days'=>$days,'partial'=>$end>$now,'timezone'=>'Europe/Zagreb'];
}
function fcc_los_filters(array $input): array {
 $text=static fn($k,$n=160)=>fcc_partner_text($input[$k]??'',$n);
 $range=fcc_los_range($input);$month=($text('cc_month',7)?$text('cc_month',7).'-01':$text('month',10))?:substr($range['to'],0,7).'-01';
 if(!preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])-01$/D',$month))throw new InvalidArgumentException('Provjeri CC mjesec.');
 $plan=$text('plan',12);if(!in_array($plan,['','pro','free'],true))$plan='';
 return ['range'=>$range,'month'=>$month,'search'=>$text('search'),'root'=>max(0,(int)($input['root']??0)),'member'=>max(0,(int)($input['member']??0)),'country'=>strtoupper($text('country',2)),'language'=>$text('language',30),'plan'=>$plan,'cohort'=>$text('cohort',10)==='new'?'new':'','metric'=>$text('metric',40)];
}
function fcc_los_catalog(): array {
 return [
 'views'=>['Otvaranja FCC stranica','fcc_member_usage','Posjeti, uključujući ponovno otvaranje.'],
 'actions'=>['Zabilježene radnje','fcc_member_usage','Radnje u modulima s uključenim mjerenjem.'],
 'coach_questions'=>['Upiti Coachu','fcc_ai_messages','Korisničke chat poruke internom Coachu u odabranom razdoblju.'],
 'public_questions'=>['Upiti javnom AI savjetniku','fcc_ai_messages','Korisničke chat poruke na javnim karticama.'],
 'mini_questions'=>['Upiti Coachu uz poruku','fcc_los_coach_turns','Trajno spremljeni upiti od uvođenja LOS evidencije.'],
 'ai_replies'=>['AI odgovori','fcc_ai_messages','Chat odgovori, bez pozdravnih i sistemskih poruka.'],
 'clicks'=>['Klikovi prema Foreveru','track_links','Zabilježeni klikovi prema prodaji ili registraciji, nisu kupnje.'],
 'shop_clicks'=>['Klikovi prema kupnji','track_links','Namjera posjeta trgovini, bez potvrde narudžbe.'],
 'registration_clicks'=>['Klikovi prema učlanjenju','track_links','Nisu potvrđene Forever registracije.'],
 'prepared'=>['Pripremljene webinar pozivnice','fcc_webinar_invite_attempts','Jedinstvene pripreme, bez probnih i opozvanih pozivnica.'],
 'whatsapp'=>['Otvaranja WhatsApp pozivnica','fcc_webinar_invite_attempts','Pozivnice s otvaranjem WhatsAppa. Dostava poruke nije potvrđena.'],
 'sent'=>['Potvrde slanja pozivnice','fcc_webinar_invite_attempts','Slanje koje je suradnik sam potvrdio.'],
 'registrations'=>['Potvrđene webinar prijave','fcc_webinar_registrations','Email potvrđen u razdoblju. Bez probnih, vlastitih, spornih i otkazanih prijava.'],
 'business_shares'=>['Pripremljene poslovne prezentacije','fcc_business_shares','Bez probnih i opozvanih prezentacija.'],
 'business_leads'=>['Novi poslovni upiti','fcc_business_leads','Važeći upiti iz Marketing plana, bez probnih i spornih zapisa.'],
 'contacts'=>['Novi kontakti','fcc_partner_contacts','Kontakti dodani u odabranom razdoblju.'],
 'contacted'=>['Kontakti s upisanom komunikacijom','fcc_partner_activities','Jedinstveni kontakti za koje je suradnik zabilježio komunikaciju.'],
 'steps'=>['Završeni zadaci','forever_business_daily_outcomes','Jedinstveni koraci važeće edukacije sa zabilježenim ishodom.'],
 'help'=>['Zadaci uz traženu pomoć','forever_business_daily_outcomes','Završeni zadaci s oznakom da je trebala pomoć.'],
 'negative'=>['Negativne AI ocjene','fcc_ai_message_feedback','Povratne ocjene korisnika u razdoblju.'],
 'positive'=>['Pozitivne AI ocjene','fcc_ai_message_feedback','Povratne ocjene korisnika u razdoblju.'],
 'errors'=>['Neuspjele radnje','fcc_los_events','Neuspjeli pokušaji u uključenim modulima od početka mjerenja.'],
 ];
}
/** No sensitive account fields leave this read model. */
function fcc_los_people(array $f): array {
 $graph=fcc_team_graph();$rows=fcc_partner_rows('SELECT user_id,name,email,type,status,language,country,preferences,plan_id,plan_expiration_date,datetime FROM users');$people=[];$accounts=[];
 foreach($rows as $r){$id=(int)$r['user_id'];$primary=fcc_team_primary($id,$graph)?:$id;$prefs=json_decode($r['preferences']??'{}',true)?:[];$fbo=fcc_team_fbo($r);$pro=(string)$r['plan_id']==='5'&&(!$r['plan_expiration_date']||$r['plan_expiration_date']>=gmdate('Y-m-d H:i:s'));unset($r['preferences']);
  $accounts[$id]=['primary'=>$primary,'fbo'=>$fbo,'country'=>strtoupper((string)$r['country']),'language'=>(string)$r['language'],'pro'=>$pro];
  if(!isset($people[$primary]))$people[$primary]=['id'=>$primary,'name'=>$r['name'],'email'=>$r['email'],'ids'=>[],'fbos'=>[],'countries'=>[],'languages'=>[],'status'=>(int)$r['status'],'pro'=>false,'admin'=>false,'created_at'=>$r['datetime'],'accounts'=>[],'sponsor'=>null];
  $p=&$people[$primary];if($id===$primary){$p['name']=$r['name'];$p['email']=$r['email'];$p['status']=(int)$r['status'];}$p['ids'][]=$id;$p['accounts'][]=$r;$p['pro']=$p['pro']||$pro;$p['admin']=$p['admin']||(int)$r['type']===1;$p['countries'][$accounts[$id]['country']]=true;$p['languages'][$r['language']??'']=true;if($fbo)$p['fbos'][$fbo]=true;$p['created_at']=min($p['created_at'],$r['datetime']);unset($p);
 }
 $slugs=[];foreach(fcc_partner_rows("SELECT user_id,url FROM links WHERE type='biolink'") as $r)$slugs[(int)$r['user_id']][]=$r['url'];
 $roots=[];foreach($people as $id=>&$p){$sid=fcc_team_edge($id,$graph);$p['sponsor']=$sid;$p['sponsor_name']=$people[$sid]['name']??'';$p['fbo']=count($p['fbos'])===1?(string)array_key_first($p['fbos']):'';$roots[]=['id'=>$id,'name'=>$p['name']];}unset($p);
 $selected=[];$q=fcc_team_search_text($f['search']);
 foreach($people as $id=>$p){
  if($f['root']&&$id!==fcc_team_primary($f['root'],$graph)&&!in_array(fcc_team_primary($f['root'],$graph),fcc_team_upline($id,$graph,false),true))continue;
  if($f['member']&&!in_array($f['member'],$p['ids'],true))continue;
  if($f['country']&&!isset($p['countries'][$f['country']]))continue;
  if($f['language']&&!isset($p['languages'][$f['language']]))continue;
  if($f['plan']&&$p['pro']!==($f['plan']==='pro'))continue;
  if($f['cohort']==='new'&&($p['created_at']<$f['range']['start']||$p['created_at']>=$f['range']['end']))continue;
  $parts=[];foreach($p['accounts'] as $a)$parts[]=$a['name'].' '.$a['email'].' '.implode(' ',$slugs[(int)$a['user_id']]??[]);
  if($q!==''&&!str_contains(fcc_team_search_text(implode(' ',$parts).' '.$p['fbo']),$q))continue;
  $selected[$id]=$p;
 }
 return ['people'=>$selected,'accounts'=>$accounts,'roots'=>$roots,'graph'=>$graph];
}
/** A missing source is unavailable, never a fabricated zero. */
function fcc_los_measure(array $scope,array $f,bool $previous=false): array {
 $r=$f['range'];$start=$previous?$r['previous_start']:$r['start'];$end=$previous?$r['previous_end']:$r['end'];$dstart=$previous?$r['previous_from']:$r['from'];$dend=$previous?$r['previous_to_exclusive']:$r['to_exclusive'];
 $owner=fcc_los_owner_case($scope,'r.user_id');
 $people=$scope['people'];$uids=[];foreach($people as $p)foreach($p['ids'] as $id)$uids[]=$id;$in=$uids?implode(',',array_map('intval',$uids)):'0';$values=[];$sources=[];$timeline=[];
 foreach($people as $id=>$p)$values[$id]=array_fill_keys(array_keys(fcc_los_catalog()),null);
 $read=function(string $table,array $metrics,string $sql,array $params)use(&$values,&$sources,$scope,$people){
  if(!fcc_los_schema($table)){$sources[$table]=['available'=>false,'error'=>'Izvor nije uključen.'];return [];}
  try {$rows=fcc_partner_rows($sql,$params);$sources[$table]=$sources[$table]??['available'=>true];foreach($values as &$v)foreach($metrics as $m)$v[$m]=0;unset($v);
   foreach($rows as $row){$id=$scope['accounts'][(int)($row['user_id']??0)]['primary']??0;if(!isset($people[$id]))continue;foreach($metrics as $m)$values[$id][$m]+=(int)($row[$m]??0);}return $rows;
  }catch(Throwable $e){$sources[$table]=['available'=>false,'error'=>'Izvor trenutačno nije dostupan.'];foreach($values as &$v)foreach($metrics as $m)$v[$m]=null;unset($v);error_log('LOS source '.$table.': '.get_class($e));return [];}
 };
 $read('fcc_member_usage',['views','actions'],"SELECT user_id,SUM(views) views,SUM(actions) actions FROM fcc_member_usage WHERE user_id IN ($in) AND day>=? AND day<? GROUP BY user_id",[$dstart,$dend]);
 $read('fcc_ai_messages',['coach_questions','public_questions','ai_replies'],"SELECT c.user_id,SUM(m.role='user' AND c.scope='internal_coach') coach_questions,SUM(m.role='user' AND c.scope<>'internal_coach') public_questions,SUM(m.role='assistant') ai_replies FROM fcc_ai_messages m JOIN fcc_ai_conversations c ON c.fcc_ai_conversation_id=m.fcc_ai_conversation_id WHERE c.user_id IN ($in) AND m.datetime>=? AND m.datetime<? AND m.message_type='chat' AND m.role IN ('user','assistant') GROUP BY c.user_id",[$start,$end]);
 $read('fcc_los_coach_turns',['mini_questions'],"SELECT user_id,COUNT(*) mini_questions FROM fcc_los_coach_turns WHERE user_id IN ($in) AND created_at>=? AND created_at<? GROUP BY user_id",[$start,$end]);
 $read('track_links',['clicks','shop_clicks','registration_clicks'],"SELECT user_id,COUNT(*) clicks,SUM(fcc_click_kind IN ('app_shop','blog_shop','direct_shop')) shop_clicks,SUM(fcc_click_kind IN ('app_registration','blog_registration','direct_registration')) registration_clicks FROM track_links WHERE user_id IN ($in) AND datetime>=? AND datetime<? AND fcc_click_kind IN ('app_shop','blog_shop','direct_shop','app_registration','blog_registration','direct_registration') GROUP BY user_id",[$start,$end]);
 $read('fcc_webinar_invite_attempts',['prepared'],"SELECT a.user_id,COUNT(*) prepared FROM fcc_webinar_invite_attempts a JOIN fcc_webinar_sessions s ON s.id=a.session_id WHERE a.user_id IN ($in) AND a.prepared_at>=? AND a.prepared_at<? AND a.is_demo=0 AND s.is_demo=0 AND a.revoked_at IS NULL GROUP BY a.user_id",[$start,$end]);
 foreach(['whatsapp'=>'whatsapp_requested_at','sent'=>'sent_self_reported_at'] as $metric=>$col)$read('fcc_webinar_invite_attempts',[$metric],"SELECT a.user_id,COUNT(*) $metric FROM fcc_webinar_invite_attempts a JOIN fcc_webinar_sessions s ON s.id=a.session_id WHERE a.user_id IN ($in) AND a.$col>=? AND a.$col<? AND a.is_demo=0 AND s.is_demo=0 AND a.revoked_at IS NULL GROUP BY a.user_id",[$start,$end]);
 $valid="r.is_demo=0 AND s.is_demo=0 AND r.is_self=0 AND r.needs_review=0 AND r.status='registered' AND a.revoked_at IS NULL AND a.is_demo=0";
 $read('fcc_webinar_registrations',['registrations'],"SELECT z.user_id,COUNT(*) registrations FROM (SELECT MIN(r.user_id) user_id FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id JOIN fcc_webinar_invite_attempts a ON a.id=r.attempt_id AND a.user_id=r.user_id AND a.session_id=r.session_id WHERE r.user_id IN ($in) AND r.email_verified_at>=? AND r.email_verified_at<? AND $valid GROUP BY $owner,r.session_id,r.email_key) z GROUP BY z.user_id",[$start,$end]);
 $read('fcc_business_shares',['business_shares'],"SELECT user_id,COUNT(*) business_shares FROM fcc_business_shares WHERE user_id IN ($in) AND created_at>=? AND created_at<? AND is_demo=0 AND revoked_at IS NULL GROUP BY user_id",[$start,$end]);
 $read('fcc_business_leads',['business_leads'],"SELECT l.user_id,COUNT(*) business_leads FROM fcc_business_leads l JOIN fcc_business_shares s ON s.id=l.share_id AND s.user_id=l.user_id WHERE l.user_id IN ($in) AND l.received_at>=? AND l.received_at<? AND l.is_demo=0 AND l.needs_review=0 AND l.withdrawn_at IS NULL AND s.is_demo=0 AND s.revoked_at IS NULL GROUP BY l.user_id",[$start,$end]);
 $read('fcc_partner_contacts',['contacts'],"SELECT user_id,COUNT(*) contacts FROM fcc_partner_contacts WHERE user_id IN ($in) AND created_at>=? AND created_at<? GROUP BY user_id",[$start,$end]);
 $read('fcc_partner_activities',['contacted'],"SELECT a.user_id,COUNT(DISTINCT a.contact_id) contacted FROM fcc_partner_activities a JOIN fcc_partner_contacts c ON c.id=a.contact_id AND c.user_id=a.user_id WHERE a.user_id IN ($in) AND a.created_at>=? AND a.created_at<? AND a.kind IN ('contacted','journey_sent','journey_performed') GROUP BY a.user_id",[$start,$end]);
 $read('forever_business_daily_outcomes',['steps','help'],"SELECT o.recorded_by_user_id user_id,COUNT(DISTINCT o.journey_step_id) steps,COUNT(DISTINCT CASE WHEN o.needs_help=1 THEN o.journey_step_id END) help FROM forever_business_daily_outcomes o".fcc_top_program_join()."WHERE ".fcc_top_program_where()." AND o.recorded_by_user_id IN ($in) AND o.created_at>=? AND o.created_at<? GROUP BY o.recorded_by_user_id",array_merge(fcc_top_program_versions(),[$start,$end]));
 $read('fcc_ai_message_feedback',['negative','positive'],"SELECT user_id,SUM(feedback_type='negative') negative,SUM(feedback_type='positive') positive FROM fcc_ai_message_feedback WHERE user_id IN ($in) AND datetime>=? AND datetime<? GROUP BY user_id",[$start,$end]);
 $read('fcc_los_events',['errors'],"SELECT user_id,COUNT(*) errors FROM fcc_los_events WHERE user_id IN ($in) AND created_at>=? AND created_at<? AND outcome='failure' GROUP BY user_id",[$start,$end]);
 $installed=fcc_los_schema('fcc_los_settings')?(fcc_partner_one("SELECT value FROM fcc_los_settings WHERE name='measurement_started_at'")['value']??null):null;
 if($installed)foreach(['mini_questions'=>'fcc_los_coach_turns','errors'=>'fcc_los_events'] as $metric=>$source){$sources[$source]['measured_from']=$installed;$sources[$source]['partial']=$start<$installed;if($end<=$installed){foreach($values as &$v)$v[$metric]=null;unset($v);}}
 $total=[];foreach(fcc_los_catalog() as $key=>$definition){$v=array_column($values,$key);$known=array_filter($v,static fn($n)=>$n!==null);$total[$key]=$known?array_sum($known):(empty($people)&&!empty($sources[$definition[1]]['available'])?0:null);}
 $total['active_members']=!array_filter($sources,static fn($s)=>$s['available'])?null:count(array_filter($values,static fn($m)=>($m['actions']??0)+($m['coach_questions']??0)+($m['mini_questions']??0)+($m['prepared']??0)+($m['business_shares']??0)+($m['steps']??0)+($m['contacted']??0)>0));
 $total['visiting_members']=empty($sources['fcc_member_usage']['available'])?null:count(array_filter($values,static fn($m)=>($m['views']??0)>0));
 return ['values'=>$values,'totals'=>$total,'sources'=>$sources,'uids'=>$uids,'in'=>$in];
}
function fcc_los_details(array $scope,array $f,array $measured,string $tab): array {
 $owner=fcc_los_owner_case($scope,'r.user_id');
 $in=$measured['in'];$r=$f['range'];$p=[$r['start'],$r['end']];$out=['warnings'=>[]];
 $read=function($table,$sql,$params=[])use(&$out){if(!fcc_los_schema($table)){$out['warnings'][]='Nedostaje izvor: '.$table;return [];}try{return fcc_partner_rows($sql,$params);}catch(Throwable $e){$out['warnings'][]='Nije dostupan izvor: '.$table;error_log('LOS detail '.$table.': '.get_class($e));return [];}};
 $out['usage']=$read('fcc_member_usage',"SELECT module,COUNT(DISTINCT user_id) accounts,SUM(views) views,SUM(actions) actions,MIN(day) first_day,MAX(day) last_day FROM fcc_member_usage WHERE user_id IN ($in) AND day>=? AND day<? GROUP BY module ORDER BY actions DESC,views DESC",[$r['from'],$r['to_exclusive']]);
 $days=$read('fcc_member_usage',"SELECT day,COUNT(DISTINCT user_id) accounts,SUM(views) views,SUM(actions) actions FROM fcc_member_usage WHERE user_id IN ($in) AND day>=? AND day<? GROUP BY day ORDER BY day",[$r['from'],$r['to_exclusive']]);$map=array_column($days,null,'day');$out['daily']=[];
 for($d=new DateTimeImmutable($r['from']);$d->format('Y-m-d')<$r['to_exclusive'];$d=$d->modify('+1 day')){$k=$d->format('Y-m-d');$out['daily'][]=$map[$k]??['day'=>$k,'accounts'=>0,'views'=>0,'actions'=>0];}
 $out['waiting']=$read('fcc_business_leads',"SELECT l.user_id,COUNT(*) n,MIN(l.received_at) oldest FROM fcc_business_leads l JOIN fcc_business_shares s ON s.id=l.share_id AND s.user_id=l.user_id WHERE l.user_id IN ($in) AND l.is_demo=0 AND l.needs_review=0 AND l.withdrawn_at IS NULL AND l.answered_at IS NULL AND s.is_demo=0 AND s.revoked_at IS NULL GROUP BY l.user_id ORDER BY oldest");
 $out['overdue']=$read('fcc_partner_contacts',"SELECT user_id,COUNT(*) n,MIN(next_followup) oldest FROM fcc_partner_contacts WHERE user_id IN ($in) AND archived_at IS NULL AND next_followup<? GROUP BY user_id ORDER BY oldest",[fcc_partner_today()]);
 $out['presence']=$read('fcc_member_presence',"SELECT user_id,last_seen,module FROM fcc_member_presence WHERE user_id IN ($in)");
 $out['coverage']=[];
 foreach(['fcc_member_usage'=>'last_seen','fcc_ai_messages'=>'datetime','fcc_webinar_registrations'=>'received_at','fcc_business_leads'=>'received_at','fcc_cc_snapshots'=>'captured_at','fcc_los_events'=>'created_at','fcc_los_coach_turns'=>'created_at'] as $table=>$column){
  if(!fcc_los_schema($table)){$out['coverage'][$table]=['available'=>false];continue;}
  $a=$read($table,"SELECT MIN($column) first_at,MAX($column) last_at FROM $table");$out['coverage'][$table]=['available'=>(bool)$a]+($a[0]??[]);
 }
 $fbos=[];foreach($scope['people'] as $person)if($person['fbo'])$fbos[$person['fbo']]=true;$fs=array_keys($fbos);$marks=$fs?implode(',',array_fill(0,count($fs),'?')):'NULL';
 $out['cc']=$read('fcc_cc_snapshots',"SELECT s.* FROM fcc_cc_snapshots s JOIN fcc_cc_batches b ON b.id=s.batch_id AND b.period_month=s.period_month JOIN (SELECT fbo_id,MAX(id) id FROM fcc_cc_snapshots WHERE period_month=? AND fbo_id IN ($marks) GROUP BY fbo_id) x ON x.id=s.id",array_merge([$f['month']],$fs));
 $out['cc_by_fbo']=array_column($out['cc'],null,'fbo_id');$out['cc_sum_personal']=$out['cc']?array_sum(array_column($out['cc'],'personal_cc')):null;$out['cc_covered']=count($out['cc']);$out['cc_expected']=count($fs);
 $out['cc_history']=$read('fcc_cc_snapshots',"SELECT s.period_month,SUM(s.personal_cc) personal_cc,COUNT(*) identities,MAX(s.captured_at) captured_at FROM fcc_cc_snapshots s JOIN fcc_cc_batches b ON b.id=s.batch_id JOIN (SELECT period_month,fbo_id,MAX(id) id FROM fcc_cc_snapshots WHERE period_month>=? AND period_month<=? AND fbo_id IN ($marks) GROUP BY period_month,fbo_id) x ON x.id=s.id GROUP BY s.period_month ORDER BY s.period_month",array_merge([(new DateTimeImmutable($f['month']))->modify('-11 months')->format('Y-m-d'),$f['month']],$fs));
 $out['four_core']=$read('forever_business_four_core_snapshots',"SELECT fbo_id,period_month,business_scope,timeframe,recruitment,retention,productivity,development,source_note,captured_at FROM forever_business_four_core_snapshots WHERE fbo_id IN ($marks) AND period_month=? ORDER BY fbo_id,business_scope,timeframe",array_merge($fs,[$f['month']]));
 if(in_array($tab,['growth','coach','reports','member','overview'],true)){
  $out['events']=$read('fcc_webinar_registrations',"SELECT s.id,s.title,s.starts_at_utc,COUNT(DISTINCT CONCAT($owner,':',r.session_id,':',r.email_key)) registrations,COUNT(DISTINCT r.user_id) accounts FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id JOIN fcc_webinar_invite_attempts a ON a.id=r.attempt_id AND a.user_id=r.user_id AND a.session_id=r.session_id WHERE r.user_id IN ($in) AND r.email_verified_at>=? AND r.email_verified_at<? AND r.is_demo=0 AND s.is_demo=0 AND r.is_self=0 AND r.needs_review=0 AND r.status='registered' AND a.is_demo=0 AND a.revoked_at IS NULL GROUP BY s.id,s.title,s.starts_at_utc ORDER BY registrations DESC",$p);
  $out['business']=$read('fcc_business_leads',"SELECT a.title,l.presentation_id,l.country,COUNT(*) leads,SUM(l.answered_at IS NOT NULL) answered FROM fcc_business_leads l JOIN fcc_business_presentations pr ON pr.id=l.presentation_id AND pr.user_id=l.user_id JOIN fcc_business_shares bs ON bs.id=l.share_id AND bs.user_id=l.user_id LEFT JOIN fcc_business_assets a ON a.id=pr.asset_id WHERE l.user_id IN ($in) AND l.received_at>=? AND l.received_at<? AND l.is_demo=0 AND l.needs_review=0 AND l.withdrawn_at IS NULL AND bs.is_demo=0 AND bs.revoked_at IS NULL GROUP BY a.title,l.presentation_id,l.country ORDER BY leads DESC",$p);
  $out['advisor_actions']=$read('fcc_ai_events',"SELECT JSON_UNQUOTE(JSON_EXTRACT(meta,'$.kind')) kind,JSON_UNQUOTE(JSON_EXTRACT(meta,'$.language')) language,JSON_UNQUOTE(JSON_EXTRACT(meta,'$.market')) market,COUNT(*) clicks,COUNT(DISTINCT fcc_ai_conversation_id) conversations FROM fcc_ai_events WHERE user_id IN ($in) AND event_type='advisor_action' AND datetime>=? AND datetime<? GROUP BY kind,language,market ORDER BY clicks DESC",$p);
  $out['ai_models']=$read('fcc_ai_messages',"SELECT COALESCE(m.model,'neuvedeno') model,COALESCE(c.language,'neuveden') language,c.scope,COUNT(*) replies,SUM(m.input_tokens) input_tokens,SUM(m.output_tokens) output_tokens,SUM(CASE WHEN JSON_VALID(m.meta) AND JSON_TYPE(JSON_EXTRACT(m.meta,'$.estimated_cost_usd')) IN ('DOUBLE','INTEGER','DECIMAL') THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(m.meta,'$.estimated_cost_usd')) AS DECIMAL(16,8)) ELSE 0 END) cost_usd,SUM(CASE WHEN JSON_VALID(m.meta) AND JSON_TYPE(JSON_EXTRACT(m.meta,'$.estimated_cost_usd')) IN ('DOUBLE','INTEGER','DECIMAL') THEN 1 ELSE 0 END) cost_covered,AVG(CASE WHEN JSON_VALID(m.meta) AND CAST(JSON_UNQUOTE(JSON_EXTRACT(m.meta,'$.api_response_time')) AS UNSIGNED)>0 THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(m.meta,'$.api_response_time')) AS UNSIGNED) ELSE NULL END) response_ms FROM fcc_ai_messages m JOIN fcc_ai_conversations c ON c.fcc_ai_conversation_id=m.fcc_ai_conversation_id WHERE c.user_id IN ($in) AND m.datetime>=? AND m.datetime<? AND m.role='assistant' AND m.message_type='chat' GROUP BY m.model,c.language,c.scope ORDER BY replies DESC",$p);
  $out['topics']=$read('fcc_ai_conversation_insights',"SELECT primary_topic_label,COUNT(*) conversations,SUM(needs_review) review_count FROM fcc_ai_conversation_insights WHERE user_id IN ($in) AND last_datetime>=? AND last_datetime<? GROUP BY primary_topic_label ORDER BY conversations DESC LIMIT 20",$p);
  $out['feedback']=$read('fcc_ai_message_feedback',"SELECT fcc_ai_message_feedback_id id,user_id,fcc_ai_conversation_id conversation_id,feedback_type,reason,status,datetime FROM fcc_ai_message_feedback WHERE user_id IN ($in) AND datetime>=? AND datetime<? ORDER BY (status='open') DESC,datetime DESC LIMIT 50",$p);
  $out['education']=$read('forever_business_daily_outcomes',"SELECT s.position,JSON_UNQUOTE(JSON_EXTRACT(s.content_json,'$.title')) title,c.catalog_version,COUNT(DISTINCT o.recorded_by_user_id) accounts,COUNT(DISTINCT o.journey_step_id) completed,SUM(o.needs_help=1) help FROM forever_business_daily_outcomes o".fcc_top_program_join()."WHERE ".fcc_top_program_where()." AND o.recorded_by_user_id IN ($in) AND o.created_at>=? AND o.created_at<? GROUP BY s.position,title,c.catalog_version ORDER BY s.position",array_merge(fcc_top_program_versions(),$p));
  $out['conversations']=$read('fcc_ai_messages',"SELECT c.fcc_ai_conversation_id id,c.user_id,c.scope,c.assistant_type,c.language,COUNT(*) messages,MAX(m.datetime) last_at FROM fcc_ai_messages m JOIN fcc_ai_conversations c ON c.fcc_ai_conversation_id=m.fcc_ai_conversation_id WHERE c.user_id IN ($in) AND m.datetime>=? AND m.datetime<? AND m.message_type='chat' GROUP BY c.fcc_ai_conversation_id,c.user_id,c.scope,c.assistant_type,c.language ORDER BY last_at DESC LIMIT 80",$p);
  $out['mini_turns']=$read('fcc_los_coach_turns',"SELECT id,user_id,kind,model,created_at FROM fcc_los_coach_turns WHERE user_id IN ($in) AND created_at>=? AND created_at<? ORDER BY created_at DESC LIMIT 60",$p);
 }
 if(in_array($tab,['usage','overview','reports'],true))$out['friction']=$read('fcc_los_events',"SELECT module,action,version,device,SUM(outcome='started') started,SUM(outcome='success') success,SUM(outcome='failure') failures FROM fcc_los_events WHERE user_id IN ($in) AND created_at>=? AND created_at<? GROUP BY module,action,version,device ORDER BY failures DESC,started DESC",$p);
 return $out;
}
function fcc_los_dataset(array $filters,string $tab='overview'): array {
 $start=microtime(true);$scope=fcc_los_people($filters);$current=fcc_los_measure($scope,$filters);$previous=fcc_los_measure($scope,$filters,true);$details=fcc_los_details($scope,$filters,$current,$tab);
 $priorities=[];$add=function($uid,$kind,$n,$at,$title)use(&$priorities,$scope){$id=$scope['accounts'][(int)$uid]['primary']??0;if(!isset($scope['people'][$id]))return;$priorities[]=['member'=>$id,'name'=>$scope['people'][$id]['name'],'kind'=>$kind,'count'=>(int)$n,'since'=>$at,'title'=>$title];};
 foreach($details['waiting'] as $w)$add($w['user_id'],'waiting',$w['n'],$w['oldest'],'Provjeri poslovne upite bez odgovora.');
 foreach($details['overdue'] as $w)$add($w['user_id'],'followup',$w['n'],$w['oldest'],'Provjeri dogovorene nastavke komunikacije.');
 foreach($current['values'] as $id=>$m){if(($m['negative']??0)>0)$add($id,'coach',$m['negative'],$filters['range']['from'],'Pregledaj povratne ocjene Coacha.');if(($m['help']??0)>0)$add($id,'education',$m['help'],$filters['range']['from'],'Ponudi pomoć uz edukaciju.');}
 $people=$scope['people'];foreach($people as $id=>&$person){$person['metrics']=$current['values'][$id];$person['previous']=$previous['values'][$id];$person['cc']=$details['cc_by_fbo'][$person['fbo']]??null;$person['last_seen']=null;foreach($details['presence'] as $pr)if(in_array((int)$pr['user_id'],$person['ids'],true))$person['last_seen']=max($person['last_seen']??'',$pr['last_seen']);}unset($person);
 uasort($people,static fn($a,$b)=>strcasecmp($a['name'],$b['name']));
 return ['version'=>'los-2026-10-02.1','generated_at'=>gmdate('c'),'filters'=>$filters,'people'=>$people,'roots'=>$scope['roots'],'totals'=>$current['totals'],'previous'=>$previous['totals'],'sources'=>$current['sources'],'details'=>$details,'priorities'=>$priorities,'accounts'=>count($current['uids']),'members'=>count($people),'elapsed_ms'=>(int)((microtime(true)-$start)*1000)];
}

function fcc_los_owner_case(array $scope,string $column): string {
 $parts=[];foreach($scope['people'] as $p)foreach($p['ids'] as $uid)if((int)$uid!==(int)$p['id'])$parts[]='WHEN '.(int)$uid.' THEN '.(int)$p['id'];
 return $parts?'(CASE '.$column.' '.implode(' ',$parts).' ELSE '.$column.' END)':$column;
}
