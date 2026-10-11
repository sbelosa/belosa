<?php
defined('ALTUMCODE') || die();

/** Additive support for the three business presentations. Legacy links retain their behaviour. */
function fcc_business_landing_ready(): bool {
 return !!fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_business_landing_events'");
}
function fcc_business_landing_models(): array {
 return [
  'overview'=>['name'=>'Upoznaj poslovanje','description'=>'Cjelovita prezentacija za osobu koja želi razumjeti proizvode, način rada i početak.','recommended'=>true],
  'personal'=>['name'=>'Osobni poziv','description'=>'Topao osobni uvod za osobu s kojom si već razgovarao o suradnji.','recommended'=>false],
  'products'=>['name'=>'Od proizvoda do suradnje','description'=>'Proizvodi i početni paket kao uvod u poslovnu mogućnost.','recommended'=>false],
 ];
}
function fcc_business_landing_model($value): string {return fcc_partner_choice($value,fcc_business_landing_models());}
function fcc_business_landing_admin(int $uid): bool {
 return $uid>0&&!!fcc_partner_one('SELECT user_id FROM users WHERE user_id=? AND type=1 AND status=1',[$uid]);
}
function fcc_business_landing_release(): string {
 if(!fcc_business_landing_ready())return 'admin';
 return (fcc_partner_one('SELECT release_mode FROM fcc_business_landing_settings WHERE id=1')['release_mode']??'admin')==='all'?'all':'admin';
}
function fcc_business_landing_access(int $uid): bool {
 return fcc_business_landing_ready()&&fcc_business_enabled($uid)&&$uid>0
  &&(fcc_business_landing_admin($uid)||fcc_business_landing_release()==='all');
}
/** The caller must validate its authenticated CSRF protected administration request. */
function fcc_business_landing_release_save(int $actor,string $mode): void {
 if(!fcc_business_landing_admin($actor)||!fcc_business_landing_ready())throw new InvalidArgumentException(fcc_t('Pristup nije dostupan.'));
 if(!in_array($mode,['admin','all'],true))throw new InvalidArgumentException(fcc_t('Odaberi način pristupa.'));
 fcc_partner_query('UPDATE fcc_business_landing_settings SET release_mode=?,actor_user_id=?,updated_at=UTC_TIMESTAMP() WHERE id=1',[$mode,$actor]);
}
function fcc_business_landing_is_new(array $s): bool {return isset(fcc_business_landing_models()[(string)($s['landing_model']??'')]);}
function fcc_business_landing_public_allowed(array $s,int $viewerUid=0): bool {
 if(!fcc_business_landing_is_new($s))return true;
 return fcc_business_landing_ready()&&(fcc_business_landing_admin($viewerUid)||fcc_business_landing_release()==='all');
}
/** Serialize preparation with the existing owner lock. Every model keeps an immutable share identity. */
function fcc_business_landing_prepare(int $uid,array $in): array {
 if(!fcc_business_landing_access($uid))throw new InvalidArgumentException(fcc_t('Poslovna prezentacija još nije dostupna za tvoj račun.'));
 $model=fcc_business_landing_model($in['landing_model']??$in['model']??'overview');
 $country=fcc_partner_choice($in['country']??'hr',fcc_business_countries());
 $pref=fcc_business_preferences($uid);$language=fcc_business_language($in['language']??$pref['language']);
 $key=fcc_partner_text($in['request_key']??'',64);
 if(!preg_match('/^[a-f0-9]{32,64}$/D',$key))throw new InvalidArgumentException(fcc_t('Osvježi obrazac.'));
 return fcc_business_atomic($uid,function()use($uid,$in,$model,$country,$language,$key){
  if(!fcc_business_landing_access($uid))throw new InvalidArgumentException(fcc_t('Pristup nije dostupan.'));
  $prior=fcc_partner_one('SELECT * FROM fcc_business_shares WHERE user_id=? AND request_key=?',[$uid,$key]);
  if($prior){
   if(($prior['landing_model']??'')!==$model||($prior['landing_country']??'')!==$country||!empty($prior['revoked_at']))throw new InvalidArgumentException(fcc_t('Osvježi pripremu prezentacije.'));
   $p=fcc_business_owned($uid,(int)$prior['presentation_id']);
   $owner=fcc_business_owner($uid,(int)$p['link_id']);
   if($p['language']!==$language||$p['status']!=='active'||!$owner)throw new InvalidArgumentException(fcc_t('Prezentacija trenutačno nije dostupna.'));
   if((int)$prior['presentation_version']!==(int)$p['version']||$prior['fbo_snapshot']!==$owner['fbo'])throw new InvalidArgumentException(fcc_t('Podaci prezentacije su promijenjeni. Ponovno pripremi poveznicu.'));
   return $prior;
  }
  $explicit=(int)($in['presentation_id']??0);
  $p=$explicit?fcc_business_owned($uid,$explicit):fcc_partner_one("SELECT * FROM fcc_business_presentations WHERE user_id=? AND status='active' AND landing_model=? AND language=? ORDER BY updated_at DESC,id DESC LIMIT 1",[$uid,$model,$language]);
  if($p&&(($p['landing_model']??'')!==$model||$p['language']!==$language))throw new InvalidArgumentException(fcc_t('Odaberi prezentaciju ovog modela i jezika.'));
  $assetProvided=array_key_exists('asset_id',$in);$asset=(int)($in['asset_id']??0);
  $assets=fcc_business_assets();
  if($assetProvided&&$asset&&!array_filter($assets,fn($a)=>(int)$a['id']===$asset))throw new InvalidArgumentException(fcc_t('Odaberi dostupni video.'));
  if(!$p){
   $cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));$card=(int)($cards[0]['link_id']??0);
   if(!fcc_business_owner($uid,$card))throw new InvalidArgumentException(fcc_t('Najprije aktiviraj svoju FCC karticu.'));
   if(!$assetProvided){$matches=array_values(array_filter($assets,fn($a)=>$a['language']===$language));$asset=(int)($matches[0]['id']??0);}
   $intro=fcc_partner_text($in['intro']??'',240);
   fcc_partner_query('INSERT INTO fcc_business_presentations (user_id,link_id,asset_id,intro,language,landing_model,created_at,updated_at) VALUES (?,?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',[$uid,$card,$asset?:null,$intro,$language,$model]);
   $p=fcc_business_owned($uid,(int)database()->insert_id);
  }elseif(($assetProvided&&(int)$p['asset_id']!==$asset)||(array_key_exists('intro',$in)&&$p['intro']!==fcc_partner_text($in['intro'],240))){
   if((int)($in['version']??0)!==(int)$p['version'])throw new InvalidArgumentException(fcc_t('Prezentacija je izmijenjena. Osvježi prikaz.'));
   fcc_partner_query('UPDATE fcc_business_presentations SET asset_id=?,intro=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE id=? AND user_id=?',[$assetProvided?($asset?:null):$p['asset_id'],array_key_exists('intro',$in)?fcc_partner_text($in['intro'],240):$p['intro'],(int)$p['id'],$uid]);
   $p=fcc_business_owned($uid,(int)$p['id']);
  }
  $owner=fcc_business_owner($uid,(int)$p['link_id']);
  if(!$owner||$p['status']!=='active')throw new InvalidArgumentException(fcc_t('Prezentacija trenutačno nije dostupna.'));
  $stepId=(int)($in['step_id']??0);$step=$stepId?fcc_partner_one('SELECT id,cycle_id FROM fcc_partner_journey_steps WHERE id=? AND user_id=?',[$stepId,$uid]):null;
  if($stepId&&!$step)throw new InvalidArgumentException(fcc_t('Korak nije pronađen.'));
  // Reuse only the latest compatible share; never change a previously sent model or referral.
  $prior=fcc_partner_one('SELECT * FROM fcc_business_shares WHERE user_id=? AND presentation_id=? AND landing_model=? AND landing_country=? AND presentation_version=? AND landing_version=1 AND fbo_snapshot=? AND revoked_at IS NULL AND COALESCE(journey_step_id,0)=? ORDER BY id DESC LIMIT 1',[$uid,(int)$p['id'],$model,$country,(int)$p['version'],$owner['fbo'],$stepId]);
  if($prior)return $prior;
  fcc_partner_query('INSERT INTO fcc_business_shares (presentation_id,user_id,public_token,request_key,presentation_version,journey_step_id,journey_cycle_id,is_demo,fbo_snapshot,landing_model,landing_country,landing_version,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())',[(int)$p['id'],$uid,bin2hex(random_bytes(32)),$key,(int)$p['version'],$step?(int)$step['id']:null,$step?(int)$step['cycle_id']:null,fcc_business_local()?1:0,$owner['fbo'],$model,$country,1]);
  return fcc_partner_one('SELECT * FROM fcc_business_shares WHERE id=?',[(int)database()->insert_id]);
 });
}
/** This context must be built by the server; guest form fields never set viewer or visitor identity. */
function fcc_business_landing_context(int $viewerUid=0): array {
 if(!isset($_SESSION['fcc_business_landing_visitor'])||!preg_match('/^[a-f0-9]{64}$/D',(string)$_SESSION['fcc_business_landing_visitor']))$_SESSION['fcc_business_landing_visitor']=bin2hex(random_bytes(32));
 return ['viewer_uid'=>$viewerUid,'visitor'=>$_SESSION['fcc_business_landing_visitor'],'user_agent'=>(string)($_SERVER['HTTP_USER_AGENT']??''),'ip'=>(string)($_SERVER['REMOTE_ADDR']??''),'preview'=>false];
}
function fcc_business_landing_test_reason(array $s,array $context): string {
 if(!empty($context['preview']))return 'preview';
 if((int)($context['viewer_uid']??0)===(int)$s['user_id'])return 'owner';
 if(fcc_business_landing_admin((int)($context['viewer_uid']??0)))return 'admin';
 if(!empty($s['is_demo']))return 'demo';
 return '';
}
function fcc_business_landing_is_bot(array $context): bool {
 $ua=(string)($context['user_agent']??'');
 return $ua===''||!!preg_match('/bot|crawler|spider|slurp|facebookexternalhit|whatsapp|headless|curl|wget|python|preview|uptimerobot/i',$ua);
}
/** A valid outgoing destination must come from the current country and immutable owner referral. */
function fcc_business_landing_outbound_valid(array $s,string $country,string $destination): bool {
 if($destination==='')return false;$fresh=fcc_business_public((string)$s['public_token']);
 if(!$fresh||empty($fresh['available']))return false;
 $article=fcc_business_starter_article();$starter=$article?fcc_business_starter_destination($fresh,$article,$country):null;
 if($starter&&hash_equals($starter,$destination))return true;
 foreach(fcc_business_offers($fresh,$country) as $offer){$url=fcc_business_destination($fresh,$offer);if($url&&hash_equals($url,$destination))return true;}
 return false;
}
/** Count an actual human action once per share, browser session, UTC day and kind. No raw IP is stored. */
function fcc_business_landing_track(array $s,string $kind,string $country='',?array $context=null): bool {
 if(!fcc_business_landing_ready()||!fcc_business_landing_is_new($s)||empty($s['available'])||!empty($s['revoked_at']))return false;
 if(!in_array($kind,['view','whatsapp','outbound'],true))throw new InvalidArgumentException(fcc_t('Radnja nije dostupna.'));
 $context??=fcc_business_landing_context();
 if(!fcc_business_landing_public_allowed($s,(int)($context['viewer_uid']??0))||fcc_business_landing_is_bot($context))return false;
 if($country!==''&&!isset(fcc_business_countries()[$country]))return false;
 if($kind==='outbound'&&!fcc_business_landing_outbound_valid($s,$country,(string)($context['destination']??'')))return false;
 if($kind==='whatsapp'&&empty($s['owner']['phone']))return false;
 $visitor=(string)($context['visitor']??'');if(!preg_match('/^[a-f0-9]{64}$/D',$visitor))return false;
 try{fcc_business_rate('landing:event:'.hash_hmac('sha256',(string)($context['ip']??''),fcc_business_key()),120,3600);}catch(InvalidArgumentException $e){return false;}
 $reason=fcc_business_landing_test_reason($s,$context);
 $hash=hash_hmac('sha256','landing:visitor:'.$visitor,fcc_business_key());
 $statement=fcc_partner_query('INSERT IGNORE INTO fcc_business_landing_events (share_id,user_id,landing_model,kind,country,visitor_hash,event_day,is_test,test_reason,created_at) VALUES (?,?,?,?,?,?,UTC_DATE(),?,?,UTC_TIMESTAMP())',[(int)$s['id'],(int)$s['user_id'],$s['landing_model'],$kind,$country,$hash,$reason!==''?1:0,$reason]);
 return $statement->affected_rows===1;
}
/** Attach the immutable source model to a received form. The existing consent, CRM and delivery flow stays authoritative. */
function fcc_business_landing_lead(array $s,int $leadId,?array $context=null): bool {
 if(!fcc_business_landing_ready()||!fcc_business_landing_is_new($s))return false;
 $context??=fcc_business_landing_context();
 if(!fcc_business_landing_public_allowed($s,(int)($context['viewer_uid']??0)))return false;
 $lead=fcc_partner_one('SELECT id,content_snapshot,needs_review,is_demo FROM fcc_business_leads WHERE id=? AND user_id=? AND share_id=?',[$leadId,(int)$s['user_id'],(int)$s['id']]);if(!$lead)return false;
 $reason=fcc_business_landing_test_reason($s,$context);$test=$reason!==''||!empty($lead['is_demo'])||!empty($lead['needs_review'])||fcc_business_landing_is_bot($context);
 $snapshot=json_decode((string)($lead['content_snapshot']??'{}'),true)?:[];$snapshot['landing_model']=$s['landing_model'];$snapshot['landing_version']=(int)($s['landing_version']??1);$snapshot['share_id']=(int)$s['id'];
 fcc_partner_query('UPDATE fcc_business_leads SET landing_model=?,landing_is_test=?,content_snapshot=? WHERE id=? AND user_id=? AND share_id=? AND landing_model IS NULL',[$s['landing_model'],$test?1:0,fcc_business_json($snapshot),$leadId,(int)$s['user_id'],(int)$s['id']]);
 return true;
}
/** Inclusive dates are entered in Europe/Zagreb and converted once for all event and lead queries. */
function fcc_business_landing_period(string $from='',string $to=''): array {
 $tz=new DateTimeZone('Europe/Zagreb');$today=new DateTimeImmutable('today',$tz);
 $from=$from!==''?$from:$today->modify('-29 days')->format('Y-m-d');$to=$to!==''?$to:$today->format('Y-m-d');
 foreach([$from,$to] as $date){$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date,$tz);if(!$d||$d->format('Y-m-d')!==$date)throw new InvalidArgumentException(fcc_t('Provjeri datume izvještaja.'));}
 $start=new DateTimeImmutable($from,$tz);$end=new DateTimeImmutable($to,$tz);
 if($end<$start||$start->diff($end)->days>365)throw new InvalidArgumentException(fcc_t('Odaberi razdoblje do godine dana.'));
 return ['from'=>$from,'to'=>$to,'start'=>$start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'end'=>$end->modify('+1 day')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'timezone'=>'Europe/Zagreb'];
}
/** An administrator may see all owners; a collaborator can see only their own source data. */
function fcc_business_landing_stats(int $actor,int $owner=0,string $from='',string $to=''): array {
 if(!fcc_business_landing_access($actor)||(!$owner&&!fcc_business_landing_admin($actor))||($owner&&$owner!==$actor&&!fcc_business_landing_admin($actor)))throw new InvalidArgumentException(fcc_t('Pristup statistici nije dostupan.'));
 $period=fcc_business_landing_period($from,$to);$counts=[];
 foreach(fcc_business_landing_models() as $key=>$model)$counts[$key]=['model'=>$key,'name'=>$model['name'],'views'=>0,'visitors'=>0,'outbound'=>0,'whatsapp'=>0,'leads'=>0,'test_views'=>0,'test_outbound'=>0,'test_whatsapp'=>0,'test_leads'=>0];
 $ownerWhere=$owner?' AND e.user_id=?':'';$params=[$period['start'],$period['end']];if($owner)$params[]=$owner;
 $events=fcc_partner_rows("SELECT e.landing_model,COUNT(DISTINCT CASE WHEN e.is_test=0 AND s.is_demo=0 AND e.kind='view' THEN e.visitor_hash END) visitors,SUM(e.kind='view' AND e.is_test=0 AND s.is_demo=0) views,SUM(e.kind='outbound' AND e.is_test=0 AND s.is_demo=0) outbound,SUM(e.kind='whatsapp' AND e.is_test=0 AND s.is_demo=0) whatsapp,SUM(e.kind='view' AND (e.is_test=1 OR s.is_demo=1)) test_views,SUM(e.kind='outbound' AND (e.is_test=1 OR s.is_demo=1)) test_outbound,SUM(e.kind='whatsapp' AND (e.is_test=1 OR s.is_demo=1)) test_whatsapp FROM fcc_business_landing_events e JOIN fcc_business_shares s ON s.id=e.share_id AND s.user_id=e.user_id WHERE e.created_at>=? AND e.created_at<?".$ownerWhere.' GROUP BY e.landing_model',$params);
 foreach($events as $event)if(isset($counts[$event['landing_model']]))foreach($event as $key=>$value)if($key!=='landing_model')$counts[$event['landing_model']][$key]=(int)$value;
 $ownerWhere=$owner?' AND r.user_id=?':'';
 $leads=fcc_partner_rows("SELECT r.landing_model,SUM(r.landing_is_test=0 AND r.is_demo=0 AND r.needs_review=0) leads,SUM(r.landing_is_test=1 OR r.is_demo=1 OR r.needs_review=1) test_leads FROM fcc_business_leads r JOIN fcc_business_shares s ON s.id=r.share_id AND s.user_id=r.user_id WHERE r.received_at>=? AND r.received_at<? AND r.status='received' AND r.landing_model IS NOT NULL".$ownerWhere.' GROUP BY r.landing_model',$params);
 foreach($leads as $lead)if(isset($counts[$lead['landing_model']])){$counts[$lead['landing_model']]['leads']=(int)$lead['leads'];$counts[$lead['landing_model']]['test_leads']=(int)$lead['test_leads'];}
 $totals=array_fill_keys(['views','visitors','outbound','whatsapp','leads','test_views','test_outbound','test_whatsapp','test_leads'],0);
 foreach($counts as &$row){foreach($totals as $key=>$value)$totals[$key]+=$row[$key];$row['outbound_rate']=$row['views']?round(100*$row['outbound']/$row['views'],1):0;$row['lead_rate']=$row['views']?round(100*$row['leads']/$row['views'],1):0;}unset($row);
 // Overall visitors are distinct across models, rather than the sum of each model's audience.
 $totalVisitors=fcc_partner_one("SELECT COUNT(DISTINCT e.visitor_hash) n FROM fcc_business_landing_events e JOIN fcc_business_shares s ON s.id=e.share_id AND s.user_id=e.user_id WHERE e.kind='view' AND e.is_test=0 AND s.is_demo=0 AND e.created_at>=? AND e.created_at<?".($owner?' AND e.user_id=?':''),$params);
 $totals['visitors']=(int)($totalVisitors['n']??0);
 return ['period'=>$period,'models'=>array_values($counts),'totals'=>$totals,'owner_uid'=>$owner,'release_mode'=>fcc_business_landing_release(),'capabilities'=>['purchase_tracking'=>false,'enrollment_tracking'=>false,'outgoing_click_tracking'=>true],'measurement'=>'Pregled i klik računaju se jednom po osobnoj poveznici, sesiji preglednika i danu. Odlazni klik nije potvrda kupnje ili registracije. Administratorski pregledi i probni podaci prikazuju se zasebno.'];
}

/** Presentation delivery is independent of optional telemetry availability. */
function fcc_business_landing_observe(array $s,string $kind,string $country,array $context): bool {
 try{return fcc_business_landing_track($s,$kind,$country,$context);}catch(Throwable $e){error_log('Business landing telemetry unavailable: '.get_class($e));return false;}
}
