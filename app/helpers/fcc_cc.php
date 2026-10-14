<?php
defined('ALTUMCODE') || die();
require_once __DIR__.'/fcc_cc_notifications.php';

function fcc_cc_t(string $key,array $vars=[],?string $locale=null): string {
    static $copy;$copy??=require dirname(__DIR__).'/config/cc_locales.php';
    $lang=fcc_locale($locale);if($lang==='es'){static $es;$es??=json_decode(file_get_contents(dirname(__DIR__).'/config/cc-es.json'),true);return strtr($es[$key]??$copy[$key][1]??$key,$vars);}if(in_array($lang,['de','sr','sq','cnr','fr','sv','hu','pl','ro'],true))return fcc_t($copy[$key][0]??$key,$vars,$lang);$index=['hr'=>0,'en'=>1,'sl'=>2][$lang]??1;
    return strtr($copy[$key][$index]??$key,$vars);
}
function fcc_cc_ready(): bool {
    static $ready;
    return $ready??=(bool)fcc_partner_one("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_cc_notices'");
}
function fcc_cc_number($value,bool $signed=false): string {
    if($value===null)return '…';
    return ($signed&&(float)$value>0?'+':'').number_format((float)$value,3,fcc_locale()==='en'?'.':',',fcc_locale()==='en'?',':'.');
}
function fcc_cc_time(string $utc): string {
    return (new DateTimeImmutable($utc,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('d. m. Y. H:i');
}
function fcc_cc_fbo(int $uid): string {
    $u=fcc_partner_one('SELECT preferences FROM users WHERE user_id=? AND status=1',[$uid]);
    return $u?forever_business_extract_user_fbo_id($u['preferences']):'';
}
function fcc_cc_rank(string $title): int {
    $title=strtolower(trim(preg_replace('/\s+/',' ',$title)));
    return ['assistant supervisor'=>1,'supervisor'=>2,'assistant manager'=>3,'unrecognized manager'=>4,'manager'=>5,'recognized manager'=>5,'senior manager'=>6,'soaring manager'=>7,'sapphire manager'=>8,'diamond sapphire manager'=>9,'diamond manager'=>10,'double diamond manager'=>11,'triple diamond manager'=>12,'centurion diamond manager'=>13][$title]??0;
}
/** Only exact current values, attested by the successful sync client, can publish. */
/* Custom code: FC-2026-10-14: A verified partial sync publishes only confirmed identities and preserves unavailable accounts. */
function fcc_cc_publication_partition(array $expected,array $required,array $unconfirmed=[]): array {
    if(!$expected||count($expected)>10000||!array_is_list($unconfirmed)||count($unconfirmed)>10000)throw new InvalidArgumentException('Invalid CC publication partition.');
    $registered=[];foreach($required as $r){$fbo=(string)($r['fbo']??'');if(preg_match('/^[0-9]{12}$/D',$fbo))$registered[$fbo]=true;}
    $records=[];foreach($expected as $e){if(!is_array($e))throw new InvalidArgumentException('Invalid CC record.');$fbo=(string)($e['fbo_id']??'');if(!preg_match('/^[0-9]{12}$/D',$fbo)||isset($records[$fbo]))throw new InvalidArgumentException('Invalid or repeated Forever ID.');$records[$fbo]=$e;}
    $preserved=[];foreach($unconfirmed as $fbo){if(!is_string($fbo)||!preg_match('/^[0-9]{12}$/D',$fbo)||!isset($registered[$fbo])||isset($records[$fbo])||isset($preserved[$fbo]))throw new InvalidArgumentException('Invalid unconfirmed Forever ID.');$preserved[$fbo]=true;}
    if($preserved)foreach($records as $fbo=>$_)if(!isset($registered[$fbo]))throw new InvalidArgumentException('Unregistered partial CC record.');
    foreach($registered as $fbo=>$_)if(!isset($records[$fbo])&&!isset($preserved[$fbo]))throw new InvalidArgumentException('Incomplete CC publication.');
    return ['records'=>$records,'preserved'=>count($preserved)];
}
/* /Custom code: FC-2026-10-14 */
function fcc_cc_publish(string $period,array $expected,string $key,array $unconfirmed=[]): array {
    if(!fcc_cc_ready()||!preg_match('/^[a-f0-9]{64}$/D',$key)||!preg_match('/^\d{4}-\d{2}-01$/D',$period)||!$expected||count($expected)>10000)throw new InvalidArgumentException('Invalid CC publication.');
    if(!(int)(fcc_partner_one("SELECT GET_LOCK('fcc_cc_publish',15) ok")['ok']??0))throw new RuntimeException('CC publication busy.');
    database()->begin_transaction();
    try {
        if($batch=fcc_partner_one('SELECT id FROM fcc_cc_batches WHERE batch_key=?',[$key])){database()->commit();return ['batch_id'=>(int)$batch['id'],'duplicate'=>true];}
        $expr=forever_business_user_fbo_sql_expression();
        $required=fcc_partner_rows("SELECT DISTINCT $expr fbo FROM users WHERE status=1 AND JSON_VALID(preferences)=1");
        $partition=fcc_cc_publication_partition($expected,$required,$unconfirmed);
        $records=$partition['records'];
        $now=gmdate('Y-m-d H:i:s');
        fcc_partner_query('INSERT INTO fcc_cc_batches(batch_key,period_month,published_at,record_count) VALUES(?,?,?,?)',[$key,$period,$now,count($records)]);
        $batchId=(int)fcc_partner_one('SELECT LAST_INSERT_ID() id')['id'];$changed=0;$events=0;
        foreach($records as $fbo=>$e){
            $r=fcc_partner_one('SELECT m.*,b.title FROM forever_business_metrics m JOIN forever_business_members b ON b.fbo_id=m.fbo_id WHERE m.fbo_id=? AND m.period_month=? FOR UPDATE',[(string)$fbo,$period]);
            if(!$r)throw new InvalidArgumentException('Missing CC record.');
            foreach(['personal_cc','total_cc','total_active_cc','is_4cc_active'] as $field){
                if(!isset($e[$field],$r[$field])||!is_numeric($e[$field])||(float)$e[$field]<0||(int)round((float)$e[$field]*1000)!==(int)round((float)$r[$field]*1000))throw new InvalidArgumentException('CC verification conflict: '.$field);
            }
            $previous=fcc_partner_one('SELECT * FROM fcc_cc_snapshots WHERE fbo_id=? AND period_month=? ORDER BY id DESC LIMIT 1',[(string)$fbo,$period]);
            $different=!$previous;
            foreach(['personal_cc','total_cc','total_active_cc','is_4cc_active','title'] as $field)if($previous&&(string)$previous[$field]!== (string)$r[$field])$different=true;
            if(!$different)continue;
            fcc_partner_query('INSERT INTO fcc_cc_snapshots(batch_id,fbo_id,period_month,personal_cc,total_cc,total_active_cc,is_4cc_active,title,captured_at,previous_id) VALUES(?,?,?,?,?,?,?,?,?,?)',[$batchId,(string)$fbo,$period,$r['personal_cc'],$r['total_cc'],$r['total_active_cc'],$r['is_4cc_active'],$r['title']??'',$now,$previous['id']??null]);
            $id=(int)fcc_partner_one('SELECT LAST_INSERT_ID() id')['id'];$changed++;
            // First observation (including a new month) is a baseline, never an achievement.
            if(!$previous||$period!==substr(fcc_partner_today(),0,7).'-01')continue;
            $d=[];foreach(['personal','total','active'] as $k){$field=$k==='active'?'total_active_cc':$k.'_cc';$d[$k]=round((float)$r[$field]-(float)$previous[$field],3);}
            $kinds=[];
            if(max($d)>0)$kinds['growth']='growth:'.$id;
            if((int)$r['is_4cc_active']===1&&(int)$previous['is_4cc_active']===0)$kinds['activity']='activity:'.$fbo.':'.$period;
            $oldTitle=fcc_partner_one('SELECT title FROM fcc_cc_snapshots WHERE fbo_id=? AND id<? ORDER BY id DESC LIMIT 1',[(string)$fbo,$id]);
            if(fcc_cc_rank($r['title'])>0&&fcc_cc_rank($oldTitle['title']??'')>0&&fcc_cc_rank($r['title'])>fcc_cc_rank($oldTitle['title']))$kinds['rank']='rank:'.$fbo.':'.hash('sha256',strtolower($r['title']));
            foreach($kinds as $kind=>$eventKey){
                $eventInsert=fcc_partner_query('INSERT IGNORE INTO fcc_cc_events(event_key,snapshot_id,fbo_id,period_month,kind,personal_delta,total_delta,active_delta,occurred_at) VALUES(?,?,?,?,?,?,?,?,?)',[$eventKey,$id,(string)$fbo,$period,$kind,$d['personal'],$d['total'],$d['active'],$now]);$events+=(int)$eventInsert->affected_rows;
            }
        }
        database()->commit();return ['batch_id'=>$batchId,'changed'=>$changed,'events'=>$events,'records'=>count($records),'preserved'=>$partition['preserved'],'complete'=>$partition['preserved']===0];
    }catch(Throwable $e){database()->rollback();throw $e;}
    finally{fcc_partner_query("SELECT RELEASE_LOCK('fcc_cc_publish')");}
}
function fcc_cc_snapshot_map(array $fbos): array {
    if(!$fbos||!fcc_cc_ready())return [];
    $fbos=array_values(array_unique($fbos));$marks=implode(',',array_fill(0,count($fbos),'?'));$period=substr(fcc_partner_today(),0,7).'-01';
    $rows=fcc_partner_rows("SELECT s.*,p.personal_cc previous_personal,p.total_cc previous_total,p.total_active_cc previous_active FROM fcc_cc_snapshots s JOIN (SELECT MAX(id) id FROM fcc_cc_snapshots WHERE fbo_id IN ($marks) AND period_month=? GROUP BY fbo_id) latest ON latest.id=s.id LEFT JOIN fcc_cc_snapshots p ON p.id=s.previous_id",array_merge($fbos,[$period]));
    $out=[];foreach($rows as $r){$r['personal_delta']=$r['previous_id']?round((float)$r['personal_cc']-(float)$r['previous_personal'],3):null;$out[$r['fbo_id']]=$r;}
    return $out;
}
function fcc_cc_page(int $uid,?string $period=null): array {
    $fbo=fcc_cc_fbo($uid);$periods=$fbo?fcc_partner_rows('SELECT period_month FROM forever_business_metrics WHERE fbo_id=? AND period_month<=? ORDER BY period_month DESC',[$fbo,substr(fcc_partner_today(),0,7).'-01']):[];
    $allowed=array_column($periods,'period_month');$period=in_array($period,$allowed,true)?$period:($allowed[0]??substr(fcc_partner_today(),0,7).'-01');
    $metric=$fbo?fcc_partner_one('SELECT m.*,b.title FROM forever_business_metrics m LEFT JOIN forever_business_members b ON b.fbo_id=m.fbo_id WHERE m.fbo_id=? AND m.period_month=?',[$fbo,$period]):null;
    $history=$fbo&&fcc_cc_ready()?fcc_partner_rows('SELECT s.*,p.personal_cc previous_personal FROM fcc_cc_snapshots s LEFT JOIN fcc_cc_snapshots p ON p.id=s.previous_id WHERE s.fbo_id=? AND s.period_month=? ORDER BY s.id DESC LIMIT 31',[$fbo,$period]):[];
    if($metric)$metric['stale']=$period!==substr(fcc_partner_today(),0,7).'-01'||strtotime($metric['updated_at'].' UTC')<time()-3*86400;
    return ['fbo'=>$fbo,'metric'=>$metric,'period'=>$period,'periods'=>$allowed,'history'=>$history];
}
function fcc_cc_outreach_message(int $actor,int $member,int $eventId): ?string {
    if(!fcc_cc_ready()||!fcc_pro_has_access($actor))return null;
    $graph=fcc_team_graph();$member=fcc_team_primary($member,$graph);
    if(!$member||!fcc_team_can_view($actor,$member,$graph))return null;
    $event=fcc_partner_one('SELECT e.*,s.title FROM fcc_cc_events e JOIN fcc_cc_snapshots s ON s.id=e.snapshot_id WHERE e.id=? AND e.fbo_id=?',[$eventId,$graph[$member]['current_fbo']]);
    if(!$event||$event['period_month']!==substr(fcc_partner_today(),0,7).'-01')return null;
    $current=fcc_partner_one('SELECT is_4cc_active FROM forever_business_metrics WHERE fbo_id=? AND period_month=?',[$event['fbo_id'],$event['period_month']]);
    if($event['kind']==='activity'&&empty($current['is_4cc_active']))return null;
    return fcc_cc_t('message_'.$event['kind'],['{name}'=>explode(' ',trim($graph[$member]['name']))[0],'{month}'=>substr($event['period_month'],5,2).'/'.substr($event['period_month'],0,4),'{rank}'=>$event['title']]);
}
