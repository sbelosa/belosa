<?php
defined('ALTUMCODE') || die();

function fcc_cc_event_options(): array {
    return ['cc_personal','cc_activity_self','cc_rank_self','team_steps','team_tasks','team_registrations','cc_team','cc_activity_team','cc_rank_team'];
}
function fcc_cc_preferences(array $p): array {
    $events=array_fill_keys(fcc_cc_event_options(),true);
    foreach(['team_steps','team_tasks','team_registrations'] as $key)$events[$key]=!isset($p['categories']['team'])||!empty($p['categories']['team']);
    return ['events'=>array_merge($events,is_array($p['events']??null)?$p['events']:[]),'event_since'=>is_array($p['event_since']??null)?$p['event_since']:[], 'cc_hour'=>max(8,min(20,(int)($p['cc_hour']??9))),'cc_details'=>!empty($p['cc_details'])];
}
function fcc_cc_event_enabled(int $uid,string $key,?string $occurred=null): bool {
    $p=fcc_partner_notification_preferences($uid);
    return !empty($p['events'][$key])&&(!$occurred||empty($p['event_since'][$key])||$occurred>$p['event_since'][$key]);
}
/** Settings apply both to generation and already queued deliveries. */
function fcc_cc_preferences_save(int $uid,array $input): void {
    $before=fcc_partner_notification_preferences($uid);$p=$before;$p['push']=!empty($input['push']);
    if(isset($input['sound_version']))$p['sound']=!empty($input['sound']);
    foreach(fcc_partner_notification_rules() as $key=>$rule)$p['categories'][$key]=in_array(fcc_journey_enabled($uid)&&in_array($key,['followup','tasks','education'],true)?'daily':$key,(array)($input['categories']??[]),true);
    if(isset($input['events_version'])){
        foreach(fcc_cc_event_options() as $key){$on=in_array($key,(array)($input['events']??[]),true);if($on!==!empty($before['events'][$key]))$p['event_since'][$key]=gmdate('Y-m-d H:i:s');$p['events'][$key]=$on;}
        $p['cc_hour']=max(8,min(20,(int)($input['cc_hour']??9)));$p['cc_details']=!empty($input['cc_details']);
        $p['categories']['cc']=true;
        $p['categories']['team']=(bool)array_filter(array_intersect_key($p['events'],array_flip(['team_steps','team_tasks','team_registrations'])));
    }
    fcc_partner_save_preferences($uid,'partner_notifications',$p);
    foreach(fcc_partner_rows("SELECT id,event_key,category,created_at FROM fcc_partner_notifications WHERE user_id=? AND read_at IS NULL",[$uid]) as $n){
        if(!fcc_cc_notification_allowed($uid,$n)){fcc_partner_query('UPDATE fcc_partner_notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()) WHERE id=?',[(int)$n['id']]);fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='skipped',last_code='preference_changed',updated_at=UTC_TIMESTAMP() WHERE notification_id=? AND status='pending'",[(int)$n['id']]);}
    }
}
function fcc_cc_team_event_key(string $key): ?string {
    if(!preg_match('/^team:\d+:\d+:(step|task|registration):/',$key,$m))return null;
    return ['step'=>'team_steps','task'=>'team_tasks','registration'=>'team_registrations'][$m[1]];
}
function fcc_cc_notification_allowed(int $uid,array $n): bool {
    $p=fcc_partner_notification_preferences($uid);
    if(empty($p['categories'][$n['category']]))return false;
    if($key=fcc_cc_team_event_key($n['event_key']))return fcc_cc_event_enabled($uid,$key,$n['created_at']??null);
    if(str_starts_with($n['event_key'],'cc:'))return (bool)fcc_cc_notice_items($uid,(int)($n['notification_id']??$n['id']??0));
    return true;
}
function fcc_cc_scope(int $uid,int $member,array $graph): string {
    if(!isset($graph[$uid],$graph[$member]))return '';
    $ids=array_unique(array_merge([$uid,$member],fcc_team_upline($member,$graph,false)));$stamp=[];
    foreach($ids as $id){$r=$graph[$id]??[];$stamp[]=[$id,$r['current_fbo']??'',fcc_team_primary($id,$graph),$r['relationship_version']??0,$r['account_version']??0];}
    return hash('sha256',json_encode($stamp));
}
function fcc_cc_item_option(array $item): string {
    return $item['kind']==='growth'?($item['self']?'cc_personal':'cc_team'):('cc_'.$item['kind'].'_'.($item['self']?'self':'team'));
}
function fcc_cc_item_allowed(int $uid,array $item,array $graph): bool {
    if(!fcc_cc_event_enabled($uid,fcc_cc_item_option($item),$item['occurred_at']))return false;
    if($item['self'])return fcc_cc_fbo($uid)===$item['fbo_id'];
    $member=(int)$item['member'];
    return fcc_pro_has_access($uid)&&fcc_team_can_view($uid,$member,$graph)&&($graph[$member]['current_fbo']??'')===$item['fbo_id']&&hash_equals($item['scope'],fcc_cc_scope($uid,$member,$graph));
}
function fcc_cc_notice_items(int $uid,int $notice): array {
    if(!$notice||!fcc_cc_ready())return [];
    $r=fcc_partner_one('SELECT c.items_json FROM fcc_cc_notices c JOIN fcc_partner_notifications n ON n.id=c.notification_id WHERE n.id=? AND n.user_id=?',[$notice,$uid]);
    $items=json_decode($r['items_json']??'[]',true)?:[];$graph=fcc_team_graph();$out=[];
    foreach($items as $item){
        if(!fcc_cc_item_allowed($uid,$item,$graph))continue;
        $m=fcc_partner_one('SELECT personal_cc,total_cc,total_active_cc,is_4cc_active FROM forever_business_metrics WHERE fbo_id=? AND period_month=?',[$item['fbo_id'],$item['period_month']]);
        if(!$m||$item['period_month']!==substr(fcc_partner_today(),0,7).'-01'||($item['kind']==='activity'&&empty($m['is_4cc_active'])))continue;
        if($item['kind']==='rank'){$rank=fcc_partner_one('SELECT title FROM forever_business_members WHERE fbo_id=?',[$item['fbo_id']]);if(fcc_cc_rank($rank['title']??'')<fcc_cc_rank($item['title']))continue;}
        if($item['kind']==='growth'&&((float)$m['personal_cc']<(float)$item['personal_cc']||(float)$m['total_cc']<(float)$item['total_cc']||(float)$m['total_active_cc']<(float)$item['total_active_cc']))continue;
        $out[]=$item;
    }
    return $out;
}
function fcc_cc_item_text(array $item,?string $locale=null): string {
    $n=static fn($v)=>number_format((float)$v,3,fcc_locale($locale)==='en'?'.':',',fcc_locale($locale)==='en'?',':'.');
    $key=($item['self']?'own_':'team_').$item['kind'];
    $text=fcc_cc_t($key,['{name}'=>$item['name'],'{cc}'=>$n($item['personal_cc']),'{delta}'=>((float)$item['personal_delta']>0?'+':'').$n($item['personal_delta']),'{rank}'=>$item['title']],$locale);
    if($item['kind']==='growth'&&(float)$item['personal_delta']<=0)$text=($item['self']?'':$item['name'].': ').fcc_cc_t('active',[],$locale).' '.$n($item['total_active_cc']).' · '.fcc_cc_t('total',[],$locale).' '.$n($item['total_cc']);
    return $text;
}
function fcc_cc_digest_text(array $items,?string $locale=null): string {
    $lines=array_map(fn($item)=>fcc_cc_item_text($item,$locale),array_slice($items,0,3));
    if(count($items)>3)$lines[]=fcc_cc_t('more_updates',['{count}'=>(string)(count($items)-3)],$locale);
    return implode("\n",$lines);
}
/** One digest per recipient and local date. All source events remain in CC history. */
function fcc_cc_refresh_notifications(int $uid,DateTimeImmutable $now): void {
    if(!fcc_cc_ready())return;
    $p=fcc_partner_notification_preferences($uid);if((int)$now->format('G')<$p['cc_hour']||(int)$now->format('G')>=21)return;
    $key='cc:'.$now->format('Y-m-d');
    $graph=fcc_team_graph();$fbo=fcc_cc_fbo($uid);$visible=fcc_pro_has_access($uid)?fcc_team_visible_members($uid,$graph):[];$members=[];
    foreach($visible as $m)$members[$m['fbo_id']]=$m;
    $fbos=array_values(array_unique(array_filter(array_merge([$fbo],array_keys($members)))));if(!$fbos)return;
    $marks=implode(',',array_fill(0,count($fbos),'?'));$since=$now->modify('-1 day')->setTime(0,0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $rows=fcc_partner_rows("SELECT e.*,s.personal_cc,s.total_cc,s.total_active_cc,s.title FROM fcc_cc_events e JOIN fcc_cc_snapshots s ON s.id=e.snapshot_id WHERE e.fbo_id IN ($marks) AND e.occurred_at>=? AND e.period_month=? ORDER BY FIELD(e.kind,'rank','activity','growth'),e.id DESC",array_merge($fbos,[$since,$now->format('Y-m-01')]));
    $prior=[];foreach(fcc_partner_rows("SELECT c.items_json FROM fcc_cc_notices c JOIN fcc_partner_notifications n ON n.id=c.notification_id WHERE n.user_id=? AND n.event_key LIKE 'cc:%' AND n.event_key<>? AND n.created_at>=?",[$uid,$key,$since]) as $old)foreach(json_decode($old['items_json'],true)?:[] as $item)$prior[$item['fbo_id']]=max($prior[$item['fbo_id']]??'', $item['occurred_at']);
    $items=[];$seen=[];
    foreach($rows as $r){
        if(isset($prior[$r['fbo_id']])&&$r['occurred_at']<=$prior[$r['fbo_id']])continue;
        $self=$r['fbo_id']===$fbo;$member=$self?$uid:(int)($members[$r['fbo_id']]['user_id']??0);if(!$member)continue;
        if(!$self){$cut=max($graph[$member]['linked_at']??'',$graph[$uid]['account_updated_at']??'',$graph[$member]['account_updated_at']??'');if($r['occurred_at']<$cut)continue;}
        $item=$r+['self'=>$self,'member'=>$member,'name'=>$self?'':$graph[$member]['name'],'scope'=>$self?'':fcc_cc_scope($uid,$member,$graph)];
        if(!fcc_cc_item_allowed($uid,$item,$graph))continue;
        $seenKey=$r['fbo_id'].':'.$r['kind'];if(isset($seen[$seenKey]))continue;$seen[$seenKey]=true;
        // The same person's achievement takes priority over a routine growth line.
        if($r['kind']==='growth'&&(isset($seen[$r['fbo_id'].':activity'])||isset($seen[$r['fbo_id'].':rank'])))continue;
        $items[]=$item;
    }
    if(!$items)return;
    $created=$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$path='partner/points?updates=1';
    fcc_partner_notify($uid,$key,'cc',fcc_cc_t('digest_title',[],'hr'),fcc_cc_digest_text($items,'hr'),$path,86400,$created);
    $n=fcc_partner_one('SELECT id FROM fcc_partner_notifications WHERE user_id=? AND event_key=?',[$uid,$key]);
    if($n){fcc_partner_query('INSERT INTO fcc_cc_notices(notification_id,items_json) VALUES(?,?) ON DUPLICATE KEY UPDATE items_json=VALUES(items_json)',[(int)$n['id'],json_encode($items,JSON_UNESCAPED_UNICODE)]);fcc_partner_query('UPDATE fcc_partner_notifications SET body=? WHERE id=?',[fcc_cc_digest_text($items,'hr'),(int)$n['id']]);}
}
function fcc_cc_recent_items(int $uid): array {
    if(!fcc_cc_ready())return [];
    $notices=fcc_partner_rows("SELECT id FROM fcc_partner_notifications WHERE user_id=? AND event_key LIKE 'cc:%' AND created_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) ORDER BY id DESC LIMIT 7",[$uid]);$out=[];$seen=[];
    foreach($notices as $n)foreach(fcc_cc_notice_items($uid,(int)$n['id']) as $item){$key=$item['fbo_id'].':'.$item['kind'];if(isset($seen[$key]))continue;$seen[$key]=true;$out[]=$item;}
    return $out;
}
