<?php
/** Admin owned rollout. Never writes identity, referrals, plans or subscription data. */
defined('ALTUMCODE') || die();
function fcc_rollout_ready(): bool {
 static $ready;if($ready!==null)return $ready;
 if(!function_exists('database'))return false;
 try {return $ready= (int)(fcc_partner_one("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('fcc_partner_rollout_control','fcc_partner_rollout_access','fcc_partner_rollout_audit')")['n']??0)===3;}catch(Throwable $e){return false;}
}
function fcc_rollout_control(): array {return $GLOBALS['fcc_rollout_control_cache']??=fcc_partner_one('SELECT * FROM fcc_partner_rollout_control WHERE id=1')??['managed'=>0,'revision'=>0];}
function fcc_rollout_managed(): bool {
 if(getenv('FCC_PARTNER_ROLLOUT')==='managed')return true;
 if(!fcc_rollout_ready())return false;
 return !empty(fcc_rollout_control()['managed']);
}
/** Audited all-users activation also covers accounts approved in the future. */
function fcc_rollout_all_enabled(): bool {return fcc_rollout_ready()&&!empty(fcc_rollout_control()['all_enabled']);}
/** Fixed SQL expression shared by authorization and the admin account list. */
function fcc_rollout_access_sql(): string {
 $global=fcc_rollout_all_enabled()?" OR (COALESCE(a.source,'')<>'revoke')":'';
 return '(u.type>0 OR COALESCE(a.allowed,0)=1'.$global.')';
}
function fcc_rollout_allowed(int $uid,bool $published=false): bool {
 if($uid<1||!fcc_rollout_ready())return false;
 try {
  $u=fcc_partner_one('SELECT u.status,'.fcc_rollout_access_sql().' workspace_allowed,a.first_granted_at FROM users u LEFT JOIN fcc_partner_rollout_access a ON a.user_id=u.user_id WHERE u.user_id=?',[$uid]);
  return $u&&(int)$u['status']===1&&(!empty($u['workspace_allowed'])||($published&&!empty($u['first_granted_at'])));
 }catch(Throwable $e){error_log('FCC managed access unavailable.');return false;}
}
/** Published guest pages retain ownership after an admin removes workspace access. */
function fcc_rollout_public_owner(int $uid): bool {return fcc_rollout_managed()?fcc_rollout_allowed($uid,true):fcc_partner_enabled($uid);}
function fcc_rollout_admin(int $actor): void {
 $u=fcc_partner_one('SELECT type,status FROM users WHERE user_id=?',[$actor]);
 // Full admin is also the permission used by the existing /admin router.
 if(!$u||(int)$u['status']!==1||(int)$u['type']!==1)throw new InvalidArgumentException(fcc_t('Ovu radnju može izvršiti samo administrator.'));
}
function fcc_rollout_fbo($preferences): string {
 $p=is_string($preferences)?json_decode($preferences,true):$preferences;
 $m=$p['meta']??[];$v=str_replace('-','',trim((string)($m['foreverId']??$m['forever_id']??$m['foreverID']??'')));
 return preg_match('/^[0-9]{12}$/D',$v)?$v:'';
}
function fcc_rollout_seed_emails(array $extra=[]): array {
 // Verified against production accounts. Match stored addresses exactly, without rewriting user identities.
 $emails=['belosa.flp@gmail.com','stjepan@belosa.info'];
 foreach($extra as $email){if(!is_string($email)||!filter_var(trim($email),FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException(fcc_t('Provjeri email dodatnog računa.'));$emails[]=strtolower(trim($email));}
 return array_values(array_unique($emails));
}
/** Resolve from this server's accounts, never from local numeric IDs or sponsor ancestry. */
function fcc_rollout_seed_plan(array $users,array $extra=[]): array {
 $emails=fcc_rollout_seed_emails($extra);$matches=[];$fbos=[];$missing=[];$ambiguous=[];$selected=[];
 // Explicit operator exceptions affect the initial group only. Existing admin roles remain authoritative.
 $excluded=array_filter(array_map(fn($v)=>strtolower(trim($v)),explode(',',(string)getenv('FCC_PARTNER_SEED_EXCLUDE_EMAILS'))));
 foreach($emails as $email){
  $rows=array_values(array_filter($users,fn($u)=>strtolower(trim($u['email']))===$email&&(int)$u['status']===1));
  if(count($rows)!==1){if(!$rows)$missing[]=$email;else $ambiguous[]=$email;continue;}
  $u=$rows[0];$matches[(int)$u['user_id']]='početni email';$fbo=fcc_rollout_fbo($u['preferences']??'');if($fbo)$fbos[$fbo]=true;
 }
 foreach($users as $u){
  if((int)$u['status']!==1)continue;$uid=(int)$u['user_id'];$reason=$matches[$uid]??null;
  if((int)$u['type']===0&&in_array(strtolower(trim($u['email'])),$excluded,true))continue;
  if((int)$u['type']>0)$reason='administratorski račun';
  if(!$reason&&isset($fbos[fcc_rollout_fbo($u['preferences']??'')]))$reason='isti Forever ID kao početni račun';
  if($reason)$selected[]=['user_id'=>$uid,'name'=>$u['name'],'email'=>$u['email'],'reason'=>$reason];
 }
 usort($selected,fn($a,$b)=>$a['user_id']<=>$b['user_id']);
 return ['accounts'=>$selected,'missing'=>$missing,'ambiguous'=>$ambiguous,'ready'=>!$missing&&!$ambiguous];
}
function fcc_rollout_users(): array {return fcc_partner_rows('SELECT user_id,name,email,status,type,preferences FROM users ORDER BY user_id');}
function fcc_rollout_targets(string $action,array $ids,array $users,array $extra=[]): array {
 if($action==='initial'){
  $plan=fcc_rollout_seed_plan($users,$extra);if(!$plan['ready'])throw new InvalidArgumentException(fcc_t('Početni računi još nisu jednoznačno pronađeni. Provjeri popis prije uključivanja.'));
  return array_column($plan['accounts'],'user_id');
 }
 if($action==='all')return array_map('intval',array_column(array_filter($users,fn($u)=>(int)$u['status']===1),'user_id'));
 if(!in_array($action,['grant','revoke'],true))throw new InvalidArgumentException(fcc_t('Odaberi radnju.'));
 $result=[];foreach($ids as $id){if(!is_scalar($id)||!ctype_digit((string)$id)||(int)$id<1)throw new InvalidArgumentException(fcc_t('Nevaljan račun.'));$result[]=(int)$id;}
 $result=array_values(array_unique($result));sort($result);
 if(!$result||count($result)>500)throw new InvalidArgumentException(fcc_t('Odaberi između jednog i 500 računa.'));
 $known=array_column($users,null,'user_id');foreach($result as $id){
  if(!isset($known[$id])||($action==='grant'&&(int)$known[$id]['status']!==1))throw new InvalidArgumentException(fcc_t('Odabrani račun više nije dostupan.'));
  if($action==='revoke'&&(int)$known[$id]['type']>0)throw new InvalidArgumentException(fcc_t('Administratori imaju pristup prema svojoj ulozi.'));
 }
 return $result;
}
function fcc_rollout_preview(string $action,array $ids,array $extra=[]): array {
 $users=fcc_rollout_users();$targets=fcc_rollout_targets($action,$ids,$users,$extra);$map=array_flip($targets);
 $accounts=array_map(fn($u)=>array_intersect_key($u,array_flip(['user_id','name','email','status','type'])),array_values(array_filter($users,fn($u)=>isset($map[(int)$u['user_id']]))));
 return ['accounts'=>$accounts,'ids'=>$targets,'fingerprint'=>hash('sha256',json_encode($accounts))];
}
function fcc_rollout_change(int $actor,array $in): int {
 fcc_rollout_admin($actor);if(!fcc_rollout_ready())throw new InvalidArgumentException(fcc_t('Najprije primijeni migraciju pristupa.'));
 $action=(string)($in['action']??'');$key=(string)($in['request_key']??'');
 if(!preg_match('/^[a-f0-9]{32}$/D',$key))throw new InvalidArgumentException(fcc_t('Osvježi obrazac.'));
 if(($in['confirmation']??'')!=='POTVRDI')throw new InvalidArgumentException(fcc_t('Za spremanje potvrdi prikazani popis.'));
 $hash=hash('sha256',json_encode($in,JSON_THROW_ON_ERROR));$db=database();$db->begin_transaction();
 try {
  $control=fcc_partner_one('SELECT * FROM fcc_partner_rollout_control WHERE id=1 FOR UPDATE');
  $old=fcc_partner_one('SELECT * FROM fcc_partner_rollout_audit WHERE actor_user_id=? AND request_key=?',[$actor,$key]);
  if($old){if(!hash_equals($old['payload_hash'],$hash))throw new InvalidArgumentException(fcc_t('Zahtjev je već iskorišten.'));$db->commit();return (int)$old['affected_count'];}
  if((int)($in['revision']??0)!==(int)$control['revision'])throw new InvalidArgumentException(fcc_t('Drugi administrator promijenio je pristup. Ponovno pregledaj odabir.'));
  // Lock the actor and target snapshot against simultaneous status or role edits.
  fcc_partner_query('SELECT user_id FROM users ORDER BY user_id FOR UPDATE');fcc_rollout_admin($actor);
  $preview=fcc_rollout_preview($action,(array)($in['ids']??[]),(array)($in['extra']??[]));
  if(!hash_equals($preview['fingerprint'],(string)($in['fingerprint']??'')))throw new InvalidArgumentException(fcc_t('Popis računa promijenio se. Ponovno pregledaj odabir.'));
  if($action==='initial'&&!empty($control['managed']))throw new InvalidArgumentException(fcc_t('Početni pristup već je postavljen. Daljnje promjene napravi odabirom korisnika.'));
  $details=[];
  foreach($preview['ids'] as $uid){
   $before=fcc_partner_one('SELECT allowed,first_granted_at FROM fcc_partner_rollout_access WHERE user_id=?',[$uid]);$allow=$action==='revoke'?0:1;
   fcc_partner_query('INSERT INTO fcc_partner_rollout_access(user_id,allowed,first_granted_at,source,actor_user_id,updated_at) VALUES(?,?,IF(?=1,UTC_TIMESTAMP(),NULL),?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed),first_granted_at=COALESCE(first_granted_at,VALUES(first_granted_at)),source=VALUES(source),actor_user_id=VALUES(actor_user_id),updated_at=VALUES(updated_at)',[$uid,$allow,$allow,$action,$actor]);
   $details[]=['user_id'=>$uid,'before'=>(int)($before['allowed']??0),'after'=>$allow];
  }
  fcc_partner_query('UPDATE fcc_partner_rollout_control SET managed=1,all_enabled=IF(?=1,1,all_enabled),revision=revision+1,updated_at=UTC_TIMESTAMP() WHERE id=1',[$action==='all'?1:0]);
  fcc_partner_query('INSERT INTO fcc_partner_rollout_audit(actor_user_id,request_key,payload_hash,action,affected_count,detail_json,created_at) VALUES(?,?,?,?,?,?,UTC_TIMESTAMP())',[$actor,$key,$hash,$action,count($details),json_encode($details,JSON_THROW_ON_ERROR)]);
  $db->commit();unset($GLOBALS['fcc_rollout_control_cache']);return count($details);
 }catch(Throwable $e){$db->rollback();throw $e;}
}
/** Global activation resumes existing education. Pilot entry retains its original policy. */
function fcc_rollout_enter(int $uid): void {
 if(!fcc_rollout_managed()||!fcc_partner_enabled($uid))return;
 $row=fcc_partner_one('SELECT * FROM fcc_partner_rollout_access WHERE user_id=?',[$uid]);
 if($row&&!empty($row['first_entered_at'])&&!empty($row['education_cycle_id']))return;
 $db=database();$db->begin_transaction();
 try {
  fcc_partner_query('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$uid]);
  if(!fcc_rollout_allowed($uid)){$db->rollback();return;}
  $all=fcc_rollout_all_enabled();
  fcc_partner_query("INSERT IGNORE INTO fcc_partner_rollout_access(user_id,allowed,first_granted_at,source,actor_user_id,updated_at) VALUES(?,?,UTC_TIMESTAMP(),?,?,UTC_TIMESTAMP())",[$uid,$all?1:0,$all?'all_active':'admin_role',$uid]);
  $row=fcc_partner_one('SELECT * FROM fcc_partner_rollout_access WHERE user_id=? FOR UPDATE',[$uid]);
  if(!$row['first_entered_at'])fcc_partner_query('UPDATE fcc_partner_rollout_access SET first_entered_at=UTC_TIMESTAMP() WHERE user_id=?',[$uid]);
  // Access and plan gates remain in the curriculum runtime. Creating an empty cycle grants no paid rights.
  if(!$row['education_cycle_id']&&fcc_jp_enabled($uid)){
   $cycle=fcc_j90_cycle($uid);
   if($all&&$cycle){
    // Do not restart active, paused or completed programs, and never rewrite their steps.
    $cid=(int)$cycle['id'];
   }else{
   if($cycle&&$cycle['status']==='active')fcc_partner_query("UPDATE fcc_partner_journey_cycles SET status='archived',version=version+1 WHERE id=?",[(int)$cycle['id']]);
   fcc_partner_query('INSERT INTO fcc_partner_journey_cycles(user_id,catalog_version,policy_version,started_at) VALUES(?,?,?,UTC_TIMESTAMP())',[$uid,FCC_JP_VERSION,fcc_jp_catalog()['policy']['id']]);$cid=(int)$db->insert_id;
   }
   fcc_partner_query('UPDATE fcc_partner_rollout_access SET education_cycle_id=? WHERE user_id=?',[$cid,$uid]);
  }
  $db->commit();
 }catch(Throwable $e){$db->rollback();throw $e;}
}
function fcc_rollout_new_education(int $uid): bool {
 return fcc_rollout_managed()&&fcc_partner_enabled($uid)&&!empty(fcc_partner_one('SELECT education_cycle_id FROM fcc_partner_rollout_access WHERE user_id=?',[$uid])['education_cycle_id']);
}

function fcc_rollout_ui(string $key): string {
 static $copy; $copy??=require APP_PATH.'config/fcc_rollout_copy.php';
 return $copy[fcc_locale()][$key]??$copy['en'][$key]??$key;
}
