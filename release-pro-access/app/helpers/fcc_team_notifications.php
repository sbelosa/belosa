<?php
defined('ALTUMCODE') || die();

function fcc_team_capture(int $uid,?array $graph=null): void {
    if(!fcc_team_ready()) return;
    $graph=$graph??fcc_team_graph(); $sid=fcc_team_edge($uid,$graph);
    if(!$sid) return;
    $m=$graph[$uid]; $primary=fcc_team_primary($uid,$graph);$record=fcc_team_record($uid);
    if(!$record||empty($m['linked_at']))return;
    $m['linked_at']=max($m['linked_at'],$m['account_updated_at']??'');
    $cut=json_decode($record['event_cutoffs']??'{}',true)?:[];
    $sources=[
        'step'=>fcc_partner_rows("SELECT outcome_id id,created_at occurred_at,0 session_id FROM forever_business_daily_outcomes WHERE recorded_by_user_id=? AND status='done' AND outcome_id>? AND created_at>=? AND created_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)",[$uid,(int)($cut['step']??0),$m['linked_at']]),
        'task'=>fcc_partner_rows("SELECT id,completed_at occurred_at,0 session_id FROM fcc_partner_tasks WHERE user_id=? AND completed_at>=? AND completed_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) AND request_key NOT LIKE 'webinar:%'",[$uid,$m['linked_at']]),
        'registration'=>fcc_partner_rows("SELECT r.id,r.email_verified_at occurred_at,r.session_id FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id
            WHERE r.user_id=? AND r.id>? AND r.received_at>=? AND r.email_verified_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) AND r.status='registered' AND r.is_demo=0 AND s.is_demo=0 AND s.status='scheduled' AND r.is_self=0 AND r.needs_review=0",[$uid,(int)($cut['registration']??0),$m['linked_at']])
    ];
    $sources['task']=array_filter($sources['task'],fn($r)=>!in_array((int)$r['id'],array_map('intval',$cut['completed_tasks']??[]),true));
    foreach($sources as $kind=>$rows) foreach($rows as $r) fcc_partner_query('INSERT IGNORE INTO fcc_team_events(member_user_id,sponsor_user_id,relationship_version,kind,source_id,session_id,occurred_at) SELECT ?,?,?,?,?,?,? FROM fcc_team_members WHERE user_id=? AND sponsor_fbo_id=? AND relationship_version=?',[$uid,$sid,(int)$m['relationship_version'],$kind,(int)$r['id'],(int)$r['session_id'],$r['occurred_at'],$primary,$graph[$sid]['current_fbo'],(int)$m['relationship_version']]);
}
function fcc_team_valid_events(int $sponsor): array {
    return fcc_partner_rows("SELECT e.* FROM fcc_team_events e
        LEFT JOIN forever_business_daily_outcomes o ON e.kind='step' AND o.outcome_id=e.source_id AND o.recorded_by_user_id=e.member_user_id
        LEFT JOIN fcc_partner_tasks t ON e.kind='task' AND t.id=e.source_id AND t.user_id=e.member_user_id
        LEFT JOIN fcc_webinar_registrations r ON e.kind='registration' AND r.id=e.source_id AND r.user_id=e.member_user_id
        LEFT JOIN fcc_webinar_sessions s ON s.id=r.session_id
        WHERE e.sponsor_user_id=? AND e.occurred_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)
        AND ((e.kind='step' AND o.status='done') OR (e.kind='task' AND t.completed_at IS NOT NULL)
        OR (e.kind='registration' AND r.status='registered' AND r.email_verified_at IS NOT NULL AND r.is_demo=0 AND r.is_self=0 AND r.needs_review=0 AND s.is_demo=0 AND s.status='scheduled'))
        ORDER BY e.id",[$sponsor]);
}
function fcc_team_notice_redact(int $id): void {
    fcc_partner_query("UPDATE fcc_partner_notifications SET title='Timska obavijest više nije dostupna',body='',path='partner/team',read_at=COALESCE(read_at,UTC_TIMESTAMP()),expires_at=UTC_TIMESTAMP() WHERE id=?",[$id]);
    fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='skipped',last_code='team_scope_changed',updated_at=UTC_TIMESTAMP() WHERE notification_id=? AND status IN ('pending','sending')",[$id]);
}
function fcc_team_redact_notifications(int $uid): void {
    foreach(fcc_partner_rows('SELECT notification_id FROM fcc_team_notices WHERE member_user_id=?',[$uid]) as $n) fcc_team_notice_redact((int)$n['notification_id']);
}
function fcc_team_notification_key(array $e): string {
    $day=(new DateTimeImmutable($e['occurred_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Y-m-d');
    return 'team:'.$e['member_user_id'].':'.$e['relationship_version'].':'.$e['kind'].':'.$day.':'.$e['session_id'];
}
/** Idempotent projection, also retracts cancelled registrations and obsolete relationships. */
function fcc_team_refresh_notifications(int $sponsor): void {
    if(!fcc_team_ready()) return;
    $graph=fcc_team_graph(); $groups=[];$primary=fcc_team_primary($sponsor,$graph);
    $p=fcc_partner_notification_preferences($sponsor);
    $rules=fcc_partner_notification_config('rules',array_fill_keys(array_keys(fcc_partner_notification_rules()),true));
    if($primary&&fcc_pro_has_access($sponsor)&&!empty($p['categories']['team'])&&!empty($rules['team'])&&fcc_partner_enabled($sponsor)) {
        foreach($graph as $uid=>$m) if(fcc_team_edge($uid,$graph)===$primary) fcc_team_capture($uid,$graph);
        foreach(fcc_team_valid_events($primary) as $e) {
            $uid=(int)$e['member_user_id'];
            if(!fcc_team_event_allowed($sponsor,$e,$graph))continue;
            $groups[fcc_team_notification_key($e)][]=$e;
        }
    }
    $notices=fcc_partner_rows('SELECT n.notification_id,n.event_count,p.event_key FROM fcc_team_notices n JOIN fcc_partner_notifications p ON p.id=n.notification_id WHERE n.sponsor_user_id=?',[$sponsor]);
    foreach($notices as $n) if(!isset($groups[$n['event_key']])) fcc_team_notice_redact((int)$n['notification_id']);
    foreach($groups as $key=>$events) {
        $e=$events[0]; $uid=(int)$e['member_user_id']; $count=count($events); $kind=$e['kind'];
        $name=fcc_partner_text($graph[fcc_team_primary($uid,$graph)]['name'],120);
        $label=match($kind) {'registration'=>'Potvrđene prijave na webinar','task'=>'Završeni osobni zadaci',default=>'Završeni koraci'};
        $body=$label.': '.$count.'. '.($kind==='registration'?'Otvori profil i dogovorite pripremu za webinar.':'Pogledaj napredak i dogovorite sljedeći korak.');
        $prior=fcc_partner_one('SELECT n.id,m.event_count FROM fcc_partner_notifications n LEFT JOIN fcc_team_notices m ON m.notification_id=n.id WHERE n.user_id=? AND n.event_key=?',[$sponsor,$key]);
        fcc_partner_notify($sponsor,$key,'team',$name.' napreduje',$body,'partner/team?member='.$uid,7*86400,$e['occurred_at']);
        $n=fcc_partner_one('SELECT id FROM fcc_partner_notifications WHERE user_id=? AND event_key=?',[$sponsor,$key]);
        if(!$n) continue;
        $id=(int)$n['id'];
        $day=(new DateTimeImmutable($e['occurred_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Y-m-d');
        fcc_partner_query('INSERT INTO fcc_team_notices(notification_id,member_user_id,sponsor_user_id,relationship_version,kind,day,session_id,event_count,push_ready) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE event_count=VALUES(event_count),push_ready=VALUES(push_ready)',[$id,$uid,$sponsor,(int)$e['relationship_version'],$kind,$day,(int)$e['session_id'],$count,$kind==='registration'?($count>=2?1:0):1]);
        if(!$prior||(int)$prior['event_count']!==$count) {
            fcc_partner_query('UPDATE fcc_partner_notifications SET title=?,body=?,read_at=IF(?,NULL,read_at) WHERE id=?',[$name.' napreduje',$body,$prior&&$count>(int)$prior['event_count']?1:0,$id]);
        }
    }
}
function fcc_team_notification_allowed(int $uid,string $key,bool $push=false): bool {
    if(!str_starts_with($key,'team:')) return true;
    if(!fcc_pro_has_access($uid))return false;
    if(!fcc_team_ready()) return false;
    $n=fcc_partner_one('SELECT m.* FROM fcc_team_notices m JOIN fcc_partner_notifications n ON n.id=m.notification_id WHERE n.user_id=? AND n.event_key=?',[$uid,$key]);
    if(!$n) return false;
    $graph=fcc_team_graph(); $member=(int)$n['member_user_id'];
    $primary=fcc_team_primary($uid,$graph);
    if(!$primary||fcc_team_edge($member,$graph)!==$primary||(int)$graph[$member]['relationship_version']!==(int)$n['relationship_version']) return false;
    $count=0;
    foreach(fcc_team_valid_events($primary) as $event) if(fcc_team_event_allowed($uid,$event,$graph)&&fcc_team_notification_key($event)===$key) $count++;
    return $count>0&&(!$push||$n['kind']!=='registration'||$count>=2);
}
function fcc_team_event_allowed(int $sponsor,array $event,array $graph): bool {
    $primary=fcc_team_primary($sponsor,$graph);$member=(int)$event['member_user_id'];
    if(!$primary||!fcc_team_can_view($sponsor,$member,$graph)||fcc_team_edge($member,$graph)!==$primary||(int)$graph[$member]['relationship_version']!==(int)$event['relationship_version'])return false;
    // A newly approved coowner never receives a backlog of historical achievement notifications.
    if($sponsor!==$primary&&$event['occurred_at']<($graph[$sponsor]['account_updated_at']??''))return false;
    if(fcc_team_primary($member,$graph)!==$member&&$event['occurred_at']<($graph[$member]['account_updated_at']??''))return false;
    return true;
}
