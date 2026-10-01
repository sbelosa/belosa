<?php
defined('ALTUMCODE') || die();

function fcc_top_t(string $key,array $vars=[],?string $locale=null): string {
 static $copy; $copy??=require __DIR__.'/../config/fcc_top_locales.php';
 return strtr($copy[$key][fcc_locale($locale)]??$copy[$key]['en']??$key,$vars);
}
function fcc_top_ready(): bool {
 static $ready;return $ready??=(bool)fcc_partner_one("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_top_state'");
}
function fcc_top_profile(int $uid): array {
 $profile=(fcc_top_ready()?fcc_partner_one('SELECT * FROM fcc_top_profiles WHERE user_id=?',[$uid]):null)??['visible'=>1,'weekly_notice'=>0,'milestone_notice'=>0,'recommendations_goal'=>3,'invitations_goal'=>5,'education_goal'=>3,'version'=>0];
 // Version zero is an untouched default; saved visibility choices remain authoritative.
 if((int)$profile['version']===0)$profile['visible']=1;
 return $profile;
}
function fcc_top_save(int $uid,array $input): void {
 if(!fcc_top_ready())throw new InvalidArgumentException(fcc_top_t('unavailable'));
 $current=fcc_top_profile($uid);
 $values=[];foreach(['visible','weekly_notice','milestone_notice'] as $key)$values[]=empty($input[$key])?0:1;
 foreach(['recommendations_goal','invitations_goal','education_goal'] as $key){$v=filter_var($input[$key]??($key==='education_goal'?null:$current[$key]),FILTER_VALIDATE_INT);if($v===false||$v<1||$v>1000)throw new InvalidArgumentException(fcc_top_t('goal_error'));$values[]=$v;}
 database()->begin_transaction();
 try {
  fcc_partner_query('INSERT IGNORE INTO fcc_top_profiles(user_id,visible) VALUES(?,1)',[$uid]);
  $old=fcc_partner_one('SELECT * FROM fcc_top_profiles WHERE user_id=? FOR UPDATE',[$uid]);
  if((int)$old['version']!==(int)($input['version']??-1))throw new InvalidArgumentException(fcc_top_t('conflict'));
  fcc_partner_query('UPDATE fcc_top_profiles SET visible=?,weekly_notice=?,milestone_notice=?,recommendations_goal=?,invitations_goal=?,education_goal=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=?',array_merge($values,[$uid]));
  if($values[2]&&!$old['milestone_notice'])fcc_partner_query('DELETE FROM fcc_top_state WHERE user_id=?',[$uid]);
  if(!$values[1]||!$values[2])fcc_partner_query("UPDATE fcc_partner_notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()) WHERE user_id=? AND ((?=0 AND event_key LIKE 'top:weekly:%') OR (?=0 AND event_key LIKE 'top:milestone:%'))",[$uid,$values[1],$values[2]]);
  database()->commit();
 }catch(Throwable $e){database()->rollback();throw $e;}
}
function fcc_top_window(int $days,?DateTimeImmutable $now=null): array {
 $days=in_array($days,[7,30,60],true)?$days:30;$now=($now??new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('Europe/Zagreb'));
 return ['days'=>$days,'from'=>$now->setTime(0,0)->modify('-'.($days-1).' days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'to'=>$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'day'=>$now->format('Y-m-d')];
}
function fcc_top_categories(): array { return ['total_active_cc','registrations','conversations','education','consistency']; }
/** Competition ranks: equal values share a place, then skip the occupied positions. */
function fcc_top_rank(array $rows): array {
 $rows=array_values(array_filter($rows,static fn($r)=>isset($r['score'])&&is_numeric($r['score'])&&is_finite((float)$r['score'])&&round((float)$r['score'],3)>0));
 usort($rows,static fn($a,$b)=>round((float)$b['score'],3)<=>round((float)$a['score'],3)?:strcmp($a['entity'],$b['entity']));
 $last=null;$rank=0;foreach($rows as $i=>&$r){$score=round((float)$r['score'],3);if($score!==$last)$rank=$i+1;$r['position']=$rank;$last=$score;}unset($r);return $rows;
}
/** Safe view model. No identifiers, contacts or another person's CC reach HTML or JSON. */
function fcc_top_public(array $rows,string $category,int $uid): array {
 $cc=in_array($category,['personal_cc','total_active_cc'],true);$visible=fcc_top_rank(array_values(array_filter($rows,static fn($r)=>$r['visible'])));$out=[];
 foreach($visible as $r){if($r['position']>10||count($out)>=10)break;$out[]=['name'=>$r['name'],'position'=>$r['position'],'score'=>$cc?null:(int)$r['score'],'own'=>in_array($uid,$r['uids'],true)];}return $out;
}
function fcc_top_own(array $rows,int $uid): array {
 foreach($rows as $r)if(in_array($uid,$r['uids'],true))return ['score'=>$r['score'],'position'=>$r['position'],'entity'=>$r['entity']];
 return ['score'=>null,'position'=>null,'entity'=>null];
}
function fcc_top_dataset(int $days,string $month,?DateTimeImmutable $now=null): array {
 $w=fcc_top_window($days,$now);$current=substr($w['day'],0,7).'-01';$previous=(new DateTimeImmutable($current))->modify('-1 month')->format('Y-m-d');
 $month=in_array($month,[$current,$previous],true)?$month:$current;
 $users=[];$groups=[];
 $expr=forever_business_user_fbo_sql_expression('u.preferences');
 foreach(fcc_partner_rows("SELECT u.user_id,u.name,$expr fbo,CASE WHEN p.user_id IS NULL OR p.version=0 THEN 1 ELSE p.visible END visible,COALESCE(m.is_privacy_requested,0) private FROM users u LEFT JOIN fcc_top_profiles p ON p.user_id=u.user_id LEFT JOIN forever_business_members m ON m.fbo_id=$expr WHERE u.status=1") as $r){
  $uid=(int)$r['user_id'];$r['visible']=(bool)$r['visible']&&!$r['private'];$users[$uid]=$r;
  if(preg_match('/^[0-9]{12}$/D',(string)$r['fbo']))$groups[$r['fbo']][]=$uid;
 }
 $scores=[];
 // Rankings use verified registrations, recorded contact work and completed education. Sharing controls are excluded.
 $queries=[
  'registrations'=>"SELECT r.user_id,COUNT(DISTINCT CONCAT(r.session_id,':',r.email_key)) n FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id JOIN fcc_webinar_invite_attempts a ON a.id=r.attempt_id AND a.user_id=r.user_id WHERE r.is_demo=0 AND s.is_demo=0 AND r.is_self=0 AND r.needs_review=0 AND r.status='registered' AND a.revoked_at IS NULL AND a.is_demo=0 AND a.session_id=r.session_id AND r.email_verified_at>=? AND r.email_verified_at<=? GROUP BY r.user_id",
  'conversations'=>"SELECT user_id,COUNT(DISTINCT contact_id) n FROM fcc_partner_activities WHERE contact_id IS NOT NULL AND kind IN ('contacted','journey_sent','journey_performed') AND created_at>=? AND created_at<=? GROUP BY user_id",
  'education'=>"SELECT recorded_by_user_id user_id,COUNT(DISTINCT COALESCE(CONCAT('step:',journey_step_id),CONCAT('legacy:',action_key,':',action_date))) n FROM forever_business_daily_outcomes WHERE status='done' AND created_at>=? AND created_at<=? GROUP BY recorded_by_user_id",
  'consistency'=>"SELECT user_id,created_at FROM fcc_partner_activities WHERE contact_id IS NOT NULL AND kind IN ('contacted','journey_sent','journey_performed') AND created_at>=? AND created_at<=?"
 ];
 foreach($queries as $key=>$sql){$params=[$w['from'],$w['to']];foreach(fcc_partner_rows($sql,$params) as $r){if($key==='consistency'){$date=(new DateTimeImmutable($r['created_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Y-m-d');$activeDays[(int)$r['user_id']][$date]=true;}else $scores[$key][(int)$r['user_id']]=(int)$r['n'];}}
 foreach($activeDays??[] as $id=>$dates)$scores['consistency'][$id]=count($dates);

 $cc=[];$sync=null;
 if(fcc_cc_ready()){
  foreach(fcc_partner_rows('SELECT s.* FROM fcc_cc_snapshots s JOIN (SELECT fbo_id,MAX(id) id FROM fcc_cc_snapshots WHERE period_month=? GROUP BY fbo_id) x ON x.id=s.id',[$month]) as $r){$cc[$r['fbo_id']]=$r;}
  $sync=fcc_partner_one('SELECT MAX(published_at) at FROM fcc_cc_batches WHERE period_month=?',[$month])['at']??null;
 }
 $lists=[];
 foreach(fcc_top_categories() as $cat){$rows=[];
  if(in_array($cat,['personal_cc','total_active_cc'],true)){
   foreach($groups as $fbo=>$ids){$r=$cc[$fbo]??null;if(!$r)continue;$names=[];$visible=true;foreach($ids as $id){$names[]=$users[$id]['name'];$visible=$visible&&$users[$id]['visible'];}$rows[]=['entity'=>hash('sha256','fbo:'.$fbo),'uids'=>$ids,'name'=>implode(' i ',$names),'visible'=>$visible,'score'=>$r[$cat]];}
  }else foreach($users as $id=>$u)$rows[]=['entity'=>hash('sha256','uid:'.$id),'uids'=>[$id],'name'=>$u['name'],'visible'=>$u['visible'],'score'=>$scores[$cat][$id]??0];
  $lists[$cat]=fcc_top_rank($rows);
 }
 return ['lists'=>$lists,'window'=>$w,'month'=>$month,'months'=>[$current,$previous],'sync'=>$sync,'cc_accounts'=>count($cc),'accounts'=>count($users),'scores'=>$scores,'cc'=>$cc,'users'=>$users];
}
function fcc_top_snapshot(array $dataset): void {
 $batch=[];foreach($dataset['lists'] as $cat=>$rows){$period=in_array($cat,['personal_cc','total_active_cc'],true)?$dataset['month']:(string)$dataset['window']['days'];foreach($rows as $r)$batch[]=[$dataset['window']['day'],$cat,$period,$r['entity'],$r['position']];}
 foreach(array_chunk($batch,100) as $chunk)fcc_partner_query('INSERT IGNORE INTO fcc_top_positions(day,category,period_key,entity_key,position) VALUES'.implode(',',array_fill(0,count($chunk),'(?,?,?,?,?)')),array_merge(...$chunk));
}
function fcc_top_page(int $uid,int $days,string $month): array {
 if(!fcc_pro_has_access($uid))throw new RuntimeException('PRO required');
 // Empty or invalid selections resolve to the current Zagreb calendar month in the dataset.
 $d=fcc_top_dataset($days,$month);fcc_top_snapshot($d);$cards=[];
 foreach($d['lists'] as $key=>$rows){$own=fcc_top_own($rows,$uid);$period=in_array($key,['personal_cc','total_active_cc'],true)?$d['month']:(string)$d['window']['days'];$old=$own['entity']?fcc_partner_one('SELECT position,day FROM fcc_top_positions WHERE category=? AND period_key=? AND entity_key=? AND day<? ORDER BY day DESC LIMIT 1',[$key,$period,$own['entity'],$d['window']['day']]):null;
  $value=$own['score'];if($value===null&&!in_array($key,['personal_cc','total_active_cc'],true))$value=0;
  if($value===null&&isset($d['users'][$uid]))$value=$d['cc'][$d['users'][$uid]['fbo']][$key]??null;
  $cards[$key]=['rows'=>fcc_top_public($rows,$key,$uid),'own'=>['score'=>$value,'position'=>$own['position'],'movement'=>$old?(int)$old['position']-(int)$own['position']:null,'comparison_day'=>$old['day']??null],'count'=>count($rows),'visible_count'=>count(array_filter($rows,static fn($r)=>$r['visible']))];
 }
 $week=$days===7?$d:fcc_top_dataset(7,$d['month']);$weekly=[];foreach(['education'] as $cat)$weekly[$cat]=$week['scores'][$cat][$uid]??0;
 return ['cards'=>$cards,'days'=>$d['window']['days'],'month'=>$d['month'],'months'=>$d['months'],'sync'=>$d['sync'],'cc_accounts'=>$d['cc_accounts'],'accounts'=>$d['accounts'],'weekly'=>$weekly,'profile'=>fcc_top_profile($uid),'at'=>$d['window']['to']];
}
/** Optional inbox notices only. First observation sets a baseline, and at most two notices are created per week. */
function fcc_top_notify_user(int $uid,DateTimeImmutable $now): void {
 if(!fcc_top_ready()||(int)$now->format('G')<9||(int)$now->format('G')>=21)return;
 $p=fcc_top_profile($uid);if((!$p['weekly_notice']&&!$p['milestone_notice'])||!fcc_pro_has_access($uid))return;
 $day=$now->format('Y-m-d');$state=fcc_partner_one('SELECT * FROM fcc_top_state WHERE user_id=?',[$uid]);if(($state['checked_day']??'')===$day)return;
 static $datasets=[];$key=$day;$d=$datasets[$key]??=fcc_top_dataset(30,substr($day,0,7).'-01',$now);
 $ranks=[];foreach($d['lists'] as $cat=>$rows)$ranks[$cat]=count($rows)>=10?fcc_top_own($rows,$uid)['position']:null;
 $old=json_decode($state['ranks_json']??'{}',true)?:[];$week=$now->format('o-W');
 if($p['weekly_notice']&&$now->format('N')==='1')fcc_partner_notify($uid,'top:weekly:'.$week,'service',fcc_top_t('weekly_title'),fcc_top_t('weekly_body'),'partner/top?month='.$d['month'],604800);
 $entered=false;if($state)foreach($ranks as $cat=>$rank){if(in_array($cat,['personal_cc','total_active_cc'],true)&&(($state['month_key']??'')!==$d['month']||!array_key_exists($cat,$old)))continue;if($rank!==null&&$rank<=10&&(($old[$cat]??null)===null||$old[$cat]>10))$entered=true;}
 if($p['milestone_notice']&&$entered)fcc_partner_notify($uid,'top:milestone:'.$week,'service',fcc_top_t('milestone_title'),fcc_top_t('milestone_body'),'partner/top?month='.$d['month'],604800);
 fcc_partner_query('INSERT INTO fcc_top_state(user_id,checked_day,month_key,ranks_json) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE checked_day=VALUES(checked_day),month_key=VALUES(month_key),ranks_json=VALUES(ranks_json)',[$uid,$day,$d['month'],json_encode($ranks)]);
}
