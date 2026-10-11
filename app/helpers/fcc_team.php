<?php
defined('ALTUMCODE') || die();
require_once __DIR__.'/fcc_team_accounts.php';
require_once __DIR__.'/fcc_team_insights.php';
require_once __DIR__.'/fcc_team_outreach.php';

/** Separate team permissions. Personal Forever and administrator LOS scopes stay unchanged. */
function fcc_team_ready(): bool {
    static $ready;
    return $ready ??= (bool) fcc_partner_one("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_team_members'");
}
function fcc_team_admin(int $uid): bool {
    return (bool) fcc_partner_one('SELECT 1 FROM users WHERE user_id=? AND type=1 AND status=1',[$uid]);
}
function fcc_team_record(int $uid): ?array {
    return fcc_team_ready() ? fcc_partner_one('SELECT * FROM fcc_team_members WHERE user_id=?',[$uid]) : null;
}
function fcc_team_fbo(array $user): string {
    return forever_business_extract_user_fbo_id($user['preferences']??'{}');
}
/** Registration data is an administrative source, not proof of phone ownership. */
function fcc_team_registration_phone(array $user): string {
    $prefs=json_decode($user['preferences']??'{}',true);
    $meta=is_array($prefs['meta']??null)?$prefs['meta']:[];
    if(!is_string($meta['phone']??null)) return '';
    $raw=trim($meta['phone']);
    if($raw===''||preg_match('/[^0-9+\p{Z}().\/\-]/u',$raw)) return '';
    $number=preg_replace('/[\p{Z}().\/\-]/u','',$raw);
    if(str_starts_with($number,'+00')) $number='+'.substr($number,3);
    elseif(str_starts_with($number,'00')) $number='+'.substr($number,2);
    $codes=get_contact_phone_dial_codes_array();
    $countryValue=$meta['phone_country_code']??$meta['country']??'';
    $country=is_string($countryValue)?mb_strtolower(trim($countryValue)):'';
    $aliases=['hrvatska'=>'HR','republika hrvatska'=>'HR','rh'=>'HR','croatia'=>'HR','slovenija'=>'SI','slovenia'=>'SI','srbija'=>'RS','republika srbija'=>'RS','serbia'=>'RS','bosna i hercegovina'=>'BA','bosnia & herzegovina'=>'BA','bosnia and herzegovina'=>'BA','босна и херцеговина'=>'BA','bih'=>'BA','njemačka'=>'DE','germany'=>'DE','deutschland'=>'DE','austrija'=>'AT','austria'=>'AT','österreich'=>'AT','united kingdom'=>'GB','uk'=>'GB','kosovo'=>'XK','crna gora'=>'ME','montenegro'=>'ME'];
    $aliases['šlovenija']='SI';
    $country=$aliases[$country]??strtoupper($country);
    $dial=$codes[$country]??null;
    if(!str_starts_with($number,'+')) {
        if($dial&&str_starts_with($number,'0')&&!str_starts_with($number,'00')) $number='+'.$dial.substr($number,1);
        elseif($dial&&str_starts_with($number,$dial)) $number='+'.$number;
        else return ''; // Never guess a country from a local number or a misspelled country.
    }
    if(!preg_match('/^\+[1-9][0-9]{7,14}$/D',$number)) return '';
    $digits=substr($number,1);$prefix='';
    foreach(array_unique(array_values($codes)) as $code) if(str_starts_with($digits,(string)$code)&&strlen((string)$code)>strlen($prefix)) $prefix=(string)$code;
    if($prefix==='') return '';
    $national=substr($digits,strlen($prefix));
    // These markets drop the national trunk zero when an international prefix is present.
    if(in_array($prefix,['385','386','381','387','382','383','44','49','43'],true)&&str_starts_with($national,'0')) {
        $national=substr($national,1);$number='+'.$prefix.$national;
    }
    // Common registration markets: reject malformed or repeated prefixes.
    $nationalPatterns=['385'=>'/^[1-9][0-9]{7,8}$/D','386'=>'/^[1-9][0-9]{7}$/D','381'=>'/^[1-9][0-9]{7,8}$/D','387'=>'/^[1-9][0-9]{7,8}$/D','382'=>'/^[1-9][0-9]{7}$/D','383'=>'/^[1-9][0-9]{7}$/D','44'=>'/^[1-9][0-9]{9}$/D'];
    if(isset($nationalPatterns[$prefix])&&!preg_match($nationalPatterns[$prefix],$national)) return '';
    if(strlen($national)<6) return '';
    return fcc_partner_contact_phone($number,true);
}
/** Preserve deliberate contact edits, including a previous choice to hide it. */
function fcc_team_registration_contact_available(int $uid,?array $record): bool {
    if(!empty($record['phone'])||!empty($record['contact_confirmed'])) return false;
    return !fcc_partner_one("SELECT 1 FROM fcc_team_audit WHERE user_id=? AND
        (JSON_UNQUOTE(JSON_EXTRACT(after_json,'$.contact_source')) IS NOT NULL OR
         (actor_user_id=user_id AND JSON_EXTRACT(after_json,'$.updated_at') IS NOT NULL) OR
         COALESCE(JSON_UNQUOTE(JSON_EXTRACT(before_json,'$.phone')),'')<>'') LIMIT 1",[$uid]);
}
function fcc_team_graph(): array {
    if(!fcc_team_ready()) return [];
    $sql=forever_business_user_fbo_sql_expression('u.preferences');
    $rows=fcc_partner_rows("SELECT u.user_id,u.name,u.status,u.type,u.preferences,t.fbo_id,t.sponsor_user_id,t.sponsor_fbo_id,t.is_manager,t.version,t.relationship_version,t.linked_at,t.phone,t.contact_confirmed,t.source,
        m.title flp_title,m.is_privacy_requested,m.is_in_current_structure
        FROM users u LEFT JOIN fcc_team_members t ON t.user_id=u.user_id
        LEFT JOIN forever_business_members m ON m.fbo_id=$sql");
    $graph=[];
    foreach($rows as $r) {
        $r['current_fbo']=fcc_team_fbo($r);
        // Assistant Manager is not a manager boundary.
        $r['manager']=isset($r['is_manager']) ? (bool)$r['is_manager'] :
            (!empty($r['is_in_current_structure']) && !empty($r['flp_title']) && stripos($r['flp_title'],'manager')!==false && stripos($r['flp_title'],'assistant')===false);
        $graph[(int)$r['user_id']]=$r;
    }
    return fcc_team_accounts_ready()?fcc_team_apply_accounts($graph,fcc_partner_rows('SELECT * FROM fcc_team_accounts WHERE active=1')):$graph;
}
function fcc_team_edge(int $uid,array $graph): ?int {
    $uid=fcc_team_primary($uid,$graph);
    $m=$graph[$uid]??null; $sid=fcc_team_primary((int)($m['sponsor_user_id']??0),$graph); $s=$graph[$sid]??null;
    if(!$m||!$s||(int)$m['status']!==1||(int)$s['status']!==1||$sid===$uid) return null;
    if(!$m['current_fbo']||$m['fbo_id']!==$m['current_fbo']||!$s['current_fbo']||$m['sponsor_fbo_id']!==$s['current_fbo']) return null;
    if(!empty($m['is_privacy_requested'])||!empty($s['is_privacy_requested'])) return null;
    return $sid;
}
/** All upline members through the first manager, inclusive. Cycles fail closed. */
function fcc_team_upline(int $uid,?array $graph=null,bool $stopAtManager=true): array {
    $graph=$graph??fcc_team_graph();$uid=fcc_team_primary($uid,$graph); $seen=[$uid=>true]; $path=[]; $cursor=$uid;
    while($sid=fcc_team_edge($cursor,$graph)) {
        if(isset($seen[$sid])) return [];
        $seen[$sid]=true; $path[]=$sid;
        if($stopAtManager&&$graph[$sid]['manager']) break;
        $cursor=$sid;
    }
    return $path;
}
function fcc_team_can_view(int $actor,int $subject,?array $graph=null): bool {
    if(!fcc_pro_has_access($actor))return false;
    $graph=$graph??fcc_team_graph();
    if(!isset($graph[$actor],$graph[$subject])||(int)$graph[$actor]['status']!==1||(int)$graph[$subject]['status']!==1) return false;
    if(!empty($graph[$actor]['is_privacy_requested'])||!empty($graph[$subject]['is_privacy_requested'])) return false;
    if($actor===$subject||fcc_team_admin($actor)) return true;
    $actor=fcc_team_primary($actor,$graph);$subject=fcc_team_primary($subject,$graph);
    if(!$actor||!$subject)return false;
    if($actor===$subject)return true;
    return in_array($actor,fcc_team_upline($subject,$graph,empty($graph[$actor]['manager'])),true);
}
function fcc_team_require(int $actor,int $subject): void {
    if(!fcc_team_ready()||!fcc_team_can_view($actor,$subject)) throw new InvalidArgumentException(fcc_t('Ovaj profil nije dostupan u tvojoj liniji.'));
}
function fcc_team_suggestion(int $uid,?string $fboOverride=null): ?array {
    $u=fcc_partner_one('SELECT preferences FROM users WHERE user_id=?',[$uid]); $fbo=$fboOverride??($u?fcc_team_fbo($u):'');
    if(!$fbo) return null;
    if(fcc_partner_one('SELECT 1 FROM forever_business_members WHERE fbo_id=? AND is_privacy_requested=1',[$fbo])) return null;
    // Only explicit Sponsor ID fields from completed imports, never inferred GENERATION.
    $s=fcc_partner_one("SELECT f.sponsor_fbo_id,f.sponsor_name,f.snapshot_date,i.completed_at
        FROM forever_business_focus_metrics f JOIN forever_business_imports i ON i.import_id=f.source_import_id AND i.status='completed'
        WHERE f.fbo_id=? AND f.sponsor_fbo_id IS NOT NULL ORDER BY f.snapshot_date DESC,i.import_id DESC LIMIT 1",[$fbo]);
    if(!$s||!forever_business_normalize_fbo_id($s['sponsor_fbo_id'])) return null;
    if(fcc_partner_one('SELECT 1 FROM forever_business_members WHERE fbo_id=? AND is_privacy_requested=1',[$s['sponsor_fbo_id']])) return null;
    $expr=forever_business_user_fbo_sql_expression();
    $s['accounts']=fcc_partner_rows("SELECT user_id,name FROM users WHERE status=1 AND $expr=? ORDER BY name",[$s['sponsor_fbo_id']]);
    if(fcc_team_accounts_ready()) {
        $primary=fcc_partner_one('SELECT user_id FROM fcc_team_accounts WHERE fbo_id=? AND user_id=primary_user_id AND active=1',[$s['sponsor_fbo_id']]);
        if($primary)$s['accounts']=array_values(array_filter($s['accounts'],fn($a)=>(int)$a['user_id']===(int)$primary['user_id']));
    }
    $s['fresh']=strtotime($s['snapshot_date'].' UTC')>=time()-14*86400;
    return $s;
}
function fcc_team_options(int $subject): array {
    $rows=fcc_partner_rows('SELECT user_id,name,email,preferences FROM users WHERE status=1 AND user_id<>? ORDER BY name,user_id',[$subject]);
    foreach($rows as &$r) { $r['fbo_id']=fcc_team_fbo($r); unset($r['preferences']); }
    $graph=fcc_team_graph();
    return array_values(array_filter($rows,fn($r)=>fcc_team_primary((int)$r['user_id'],$graph)===(int)$r['user_id']&&fcc_team_primary($subject,$graph)!==(int)$r['user_id']));
}
function fcc_team_validate(int $actor,int $uid,array $in): array {
    if(!fcc_team_ready()||!fcc_team_admin($actor)) throw new InvalidArgumentException(fcc_t('Povezivanje uređuje administrator.'));
    $u=fcc_partner_one('SELECT * FROM users WHERE user_id=?',[$uid]);
    if(!$u) throw new InvalidArgumentException(fcc_t('Suradnik nije pronađen.'));
    $old=fcc_team_record($uid);
    if((int)($in['team_version']??-1)!==(int)($old['version']??0)) throw new InvalidArgumentException(fcc_t('Povezivanje je već izmijenjeno. Osvježi stranicu.'));
    $fbo=forever_business_normalize_fbo_id($in['team_fbo_id']??fcc_team_fbo($u));
    $sid=(int)($in['team_sponsor_id']??0);
    $graph=fcc_team_graph();
    if($sid)$sid=fcc_team_primary($sid,$graph)?:$sid;
    $primary=fcc_team_primary($uid,$graph);
    if($primary&&$primary!==$uid&&$sid!==(int)($graph[$primary]['sponsor_user_id']??0)) throw new InvalidArgumentException(fcc_t('Sponzora zajedničkog ID broja uredi na glavnom računu.'));
    if($sid===$uid) throw new InvalidArgumentException(fcc_t('Suradnik ne može biti vlastiti sponzor.'));
    $s=$sid?fcc_partner_one('SELECT * FROM users WHERE user_id=? AND status=1',[$sid]):null;
    if($sid&&(!$s||!fcc_team_fbo($s)||!$fbo)) throw new InvalidArgumentException(fcc_t('Za povezivanje odaberi aktivnog sponzora i provjeri oba Forever ID-a.'));
    if($sid&&fcc_team_fbo($s)===$fbo) throw new InvalidArgumentException(fcc_t('Računi istog Forever ID-a ne mogu biti međusobni sponzori.'));
    // Validate the entire stored graph, including disabled members and manager boundaries.
    $edges=[];
    foreach(fcc_partner_rows('SELECT user_id,sponsor_user_id FROM fcc_team_members') as $r) $edges[(int)$r['user_id']]=(int)$r['sponsor_user_id'];
    $edges[$uid]=$sid; $seen=[]; $cursor=$uid;
    while($cursor) {
        if(isset($seen[$cursor])) throw new InvalidArgumentException(fcc_t('Ovaj odabir stvara krug u sponzorskoj liniji.'));
        $seen[$cursor]=true; $cursor=$edges[$cursor]??0;
    }
    $proposed=$graph;
    if(isset($proposed[$uid])) {
        $proposed[$uid]['fbo_id']=$fbo;$proposed[$uid]['current_fbo']=$fbo;
        $proposed[$uid]['sponsor_user_id']=$sid;$proposed[$uid]['sponsor_fbo_id']=$s?fcc_team_fbo($s):'';
        $cursor=fcc_team_primary($uid,$proposed);$seen=[$cursor=>true];
        while($cursor=fcc_team_edge($cursor,$proposed)) {
            if(isset($seen[$cursor]))throw new InvalidArgumentException(fcc_t('Ovaj odabir stvara krug kroz zajednički račun.'));
            $seen[$cursor]=true;
        }
    }
    $phone=fcc_partner_contact_phone(fcc_partner_text($in['team_phone']??'',30),true);
    if(!empty($in['team_phone'])&&!$phone) throw new InvalidArgumentException(fcc_t('Poslovni telefon upiši s međunarodnim pozivnim brojem.'));
    $reason=fcc_partner_text($in['team_reason']??'',500);
    $suggestion=fcc_team_suggestion($uid,$fbo);
    if($sid&&$suggestion&&$suggestion['fresh']&&$suggestion['sponsor_fbo_id']!==fcc_team_fbo($s)&&$reason==='') throw new InvalidArgumentException(fcc_t('Odabir se razlikuje od FLP prijedloga. Upiši razlog ručne dodjele.'));
    return ['fbo_id'=>$fbo,'sponsor_user_id'=>$sid?:null,'sponsor_fbo_id'=>$s?fcc_team_fbo($s):'','is_manager'=>!empty($in['team_is_manager'])?1:0,
        'phone'=>$phone,'contact_confirmed'=>$phone&&!empty($in['team_contact_confirmed'])?1:0,
        'source'=>$sid&&$suggestion&&$suggestion['fresh']&&$suggestion['sponsor_fbo_id']===fcc_team_fbo($s)?'flp_confirmed':'admin',
        'reason'=>$reason];
}
/** One serializable graph edit and optional user approval/update on the same connection. */
function fcc_team_admin_save(int $actor,int $uid,array $in,?callable $saveUser=null): void {
    if(!(int)(fcc_partner_one("SELECT GET_LOCK('fcc_team_graph',10) ok")['ok']??0)) throw new InvalidArgumentException(fcc_t('Drugo povezivanje je u tijeku. Pokušaj ponovno.'));
    database()->begin_transaction();
    try {
        fcc_partner_one('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$uid]);
        fcc_partner_one('SELECT user_id FROM fcc_team_members WHERE user_id=? FOR UPDATE',[$uid]);
        $v=fcc_team_validate($actor,$uid,$in); $old=fcc_team_record($uid);
        if($saveUser) $saveUser();
        $changed=!$old||$old['fbo_id']!==$v['fbo_id']||(int)$old['sponsor_user_id']!==(int)$v['sponsor_user_id']||$old['sponsor_fbo_id']!==$v['sponsor_fbo_id'];
        $rv=(int)($old['relationship_version']??0)+($changed?1:0); $version=(int)($old['version']??0)+1;
        $linked=$changed?gmdate('Y-m-d H:i:s'):$old['linked_at'];
        $cutoffs=$changed?json_encode([
            'step'=>(int)(fcc_partner_one('SELECT MAX(outcome_id) n FROM forever_business_daily_outcomes WHERE recorded_by_user_id=?',[$uid])['n']??0),
            'completed_tasks'=>array_column(fcc_partner_rows('SELECT id FROM fcc_partner_tasks WHERE user_id=? AND completed_at IS NOT NULL',[$uid]),'id'),
            'registration'=>(int)(fcc_partner_one('SELECT MAX(id) n FROM fcc_webinar_registrations WHERE user_id=?',[$uid])['n']??0)
        ]):($old['event_cutoffs']??'{}');
        fcc_partner_query("INSERT INTO fcc_team_members(user_id,fbo_id,sponsor_user_id,sponsor_fbo_id,is_manager,phone,contact_confirmed,source,reason,version,relationship_version,linked_at,event_cutoffs,actor_user_id,updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE fbo_id=VALUES(fbo_id),sponsor_user_id=VALUES(sponsor_user_id),sponsor_fbo_id=VALUES(sponsor_fbo_id),is_manager=VALUES(is_manager),phone=VALUES(phone),contact_confirmed=VALUES(contact_confirmed),source=VALUES(source),reason=VALUES(reason),version=VALUES(version),relationship_version=VALUES(relationship_version),linked_at=VALUES(linked_at),event_cutoffs=VALUES(event_cutoffs),actor_user_id=VALUES(actor_user_id),updated_at=UTC_TIMESTAMP()",
            [$uid,$v['fbo_id'],$v['sponsor_user_id'],$v['sponsor_fbo_id'],$v['is_manager'],$v['phone'],$v['contact_confirmed'],$v['source'],$v['reason'],$version,$rv,$linked,$cutoffs,$actor]);
        fcc_partner_query('INSERT INTO fcc_team_audit(user_id,actor_user_id,before_json,after_json,created_at) VALUES (?,?,?,?,UTC_TIMESTAMP())',[$uid,$actor,$old?json_encode($old):null,json_encode($v+['version'=>$version,'relationship_version'=>$rv,'contact_source'=>'admin'],JSON_UNESCAPED_UNICODE)]);
        // Old achievements never migrate to the newly selected sponsor.
        if($changed) fcc_team_redact_notifications($uid);
        database()->commit();
    } catch(Throwable $e) { database()->rollback(); throw $e; }
    finally { fcc_partner_query("SELECT RELEASE_LOCK('fcc_team_graph')"); }
    cache()->deleteItemsByTag('user_id='.$uid); cache()->deleteItem('user?user_id='.$uid);
}
function fcc_team_sponsor(int $uid,?array $graph=null): ?array {
    $graph=$graph??fcc_team_graph(); $sid=fcc_team_edge($uid,$graph);
    if(!$sid) return null;
    $s=$graph[$sid];
    return ['user_id'=>$sid,'name'=>$s['name'],'phone'=>!empty($s['contact_confirmed'])?$s['phone']:'',
        'is_manager'=>$s['manager'],'source'=>$graph[fcc_team_primary($uid,$graph)]['source']];
}
/** Resolve only the nearest manager on the currently valid, visible line. */
function fcc_team_first_manager(int $uid,?array $graph=null): ?array {
    $graph=$graph??fcc_team_graph();
    foreach(fcc_team_upline($uid,$graph) as $id) {
        $m=$graph[$id];
        if($m['manager']) return ['user_id'=>$id,'name'=>$m['name'],'phone'=>!empty($m['contact_confirmed'])?$m['phone']:'','is_manager'=>true];
    }
    return null;
}
function fcc_team_account_metrics(int $uid,int $days=30): array {
    $days=in_array($days,[7,30],true)?$days:30;
    $since=(new DateTimeImmutable('now',new DateTimeZone('Europe/Zagreb')))->setTime(0,0)->modify('-'.($days-1).' days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $steps=fcc_partner_one("SELECT COUNT(*) total,MAX(created_at) last_at FROM forever_business_daily_outcomes WHERE recorded_by_user_id=? AND status='done' AND created_at>=?",[$uid,$since]);
    $cycle=fcc_partner_one('SELECT id,status FROM fcc_partner_journey_cycles WHERE user_id=? ORDER BY id DESC LIMIT 1',[$uid]);
    $progress=$cycle?fcc_partner_one("SELECT COUNT(*) done,MAX(sequence_position) position FROM forever_business_daily_outcomes WHERE recorded_by_user_id=? AND journey_cycle_id=? AND status='done'",[$uid,(int)$cycle['id']]):['done'=>0,'position'=>0];
    $invites=fcc_partner_one("SELECT COUNT(*) prepared,COALESCE(SUM(a.sent_self_reported_at IS NOT NULL),0) sent FROM fcc_webinar_invite_attempts a JOIN fcc_webinar_sessions s ON s.id=a.session_id WHERE a.user_id=? AND a.is_demo=0 AND s.is_demo=0 AND a.revoked_at IS NULL AND a.prepared_at>=?",[$uid,$since]);
    $registrations=fcc_partner_one("SELECT COUNT(*) total,COALESCE(SUM(r.email_verified_at IS NOT NULL),0) verified FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id WHERE r.user_id=? AND r.is_demo=0 AND s.is_demo=0 AND r.is_self=0 AND r.needs_review=0 AND r.status='registered' AND r.received_at>=?",[$uid,$since]);
    $condition=\Altum\Link::get_fcc_results_qualified_click_condition_sql('tl','bb');
    $clicks=fcc_partner_one("SELECT COUNT(*) total FROM track_links tl LEFT JOIN biolinks_blocks bb ON bb.biolink_block_id=tl.biolink_block_id WHERE tl.user_id=? AND tl.datetime>=? AND tl.is_unique=1 AND ($condition)",[$uid,$since]);
    $business=fcc_partner_one("SELECT COUNT(*) prepared,COALESCE(SUM(sent_at IS NOT NULL),0) sent FROM fcc_business_shares WHERE user_id=? AND is_demo=0 AND revoked_at IS NULL AND created_at>=?",[$uid,$since]);
    $u=fcc_partner_one('SELECT preferences FROM users WHERE user_id=?',[$uid]); $fbo=fcc_team_fbo($u??[]);
    $prefs=json_decode($u['preferences']??'{}',true)?:[];
    return ['days'=>$days,'steps'=>(int)$steps['total'],'last_activity'=>$steps['last_at'],'progress'=>(int)$progress['done'],'position'=>(int)($progress['position']??0),
        'cycle_status'=>$cycle['status']??'not_started','paused_until'=>'',
        'prepared'=>(int)$invites['prepared']+(int)$business['prepared'],'sent'=>(int)$invites['sent']+(int)$business['sent'],
        'registrations'=>(int)$registrations['total'],'verified_registrations'=>(int)$registrations['verified'],'clicks'=>(int)$clicks['total']];
}
function fcc_team_account_links(int $uid): array {
    // Deliberately omit recipient IDs, drafts and sealed public/management tokens.
    $rows=fcc_partner_rows("SELECT a.id,s.title,a.prepared_at,a.sent_self_reported_at sent_at,'webinar' kind,
        (SELECT COUNT(*) FROM fcc_webinar_registrations r WHERE r.attempt_id=a.id AND r.user_id=a.user_id AND r.status='registered' AND r.is_demo=0 AND r.is_self=0 AND r.needs_review=0) registrations
        FROM fcc_webinar_invite_attempts a JOIN fcc_webinar_sessions s ON s.id=a.session_id WHERE a.user_id=? AND a.is_demo=0 AND s.is_demo=0 AND a.revoked_at IS NULL ORDER BY a.id DESC LIMIT 20",[$uid]);
    $business=fcc_partner_rows("SELECT s.id,COALESCE(NULLIF(a.title,''),'Poslovna prezentacija') title,s.created_at prepared_at,s.sent_at,'business' kind,
        (SELECT COUNT(*) FROM fcc_business_leads l WHERE l.share_id=s.id AND l.user_id=s.user_id AND l.is_demo=0 AND l.needs_review=0 AND l.withdrawn_at IS NULL) registrations
        FROM fcc_business_shares s JOIN fcc_business_presentations p ON p.id=s.presentation_id AND p.user_id=s.user_id LEFT JOIN fcc_business_assets a ON a.id=p.asset_id
        WHERE s.user_id=? AND s.is_demo=0 AND s.revoked_at IS NULL ORDER BY s.id DESC LIMIT 20",[$uid]);
    $rows=array_merge($rows,$business);
    usort($rows,fn($a,$b)=>strcmp($b['prepared_at'],$a['prepared_at']));
    return array_slice($rows,0,20);
}
function fcc_team_links(int $uid,?array $graph=null): array {
    $rows=[];
    foreach(fcc_team_account_ids($uid,$graph) as $id)$rows=array_merge($rows,fcc_team_account_links($id));
    usort($rows,fn($a,$b)=>strcmp($b['prepared_at'],$a['prepared_at']));
    return array_slice($rows,0,20);
}
function fcc_team_page(int $actor,array $query): array {
    fcc_pro_require($actor);
    $graph=fcc_team_graph(); $subject=(int)($query['member']??0);
    if($subject) fcc_team_require($actor,$subject);
    if($subject)$subject=fcc_team_primary($subject,$graph);
    $search=fcc_partner_text($query['q']??'',80); $depth=max(0,min(100,(int)($query['depth']??0)));
    $days=(int)($query['days']??30)===7?7:30; $all=[];
    foreach(fcc_team_visible_members($actor,$graph) as $m) {
        if($depth&&$m['depth']!==$depth) continue;
        $names=implode(' ',array_map(fn($id)=>$graph[$id]['name'],$m['account_ids']));
        if($search!==''&&!str_contains(fcc_team_search_text($names.' '.$m['fbo_id']),fcc_team_search_text($search))) continue;
        $all[]=$m;
    }
    usort($all,fn($a,$b)=>[$a['depth'],$a['name']]<=>[$b['depth'],$b['name']]);
    $ccFilter=in_array($query['cc_filter']??'', ['growth','active','rank'],true)?$query['cc_filter']:'';
    if($ccFilter&&fcc_cc_ready()){
        $filterMap=fcc_team_cc_map(array_column($all,'fbo_id'));
        $rankIds=array_column(fcc_partner_rows("SELECT DISTINCT fbo_id FROM fcc_cc_events WHERE kind='rank' AND occurred_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY)"),'fbo_id');
        $all=array_values(array_filter($all,function($m)use($ccFilter,$filterMap,$rankIds){$c=$filterMap[$m['fbo_id']]??null;return $c&&!$c['stale']&&match($ccFilter){'growth'=>($c['personal_delta']??0)>0,'active'=>!empty($c['is_4cc_active']),'rank'=>in_array($m['fbo_id'],$rankIds,true)};}));
    }
    $page=max(1,(int)($query['page']??1)); $rows=array_slice($all,($page-1)*20,20);
    $ccMap=fcc_team_cc_map(array_merge(array_column($rows,'fbo_id'),$subject?[$graph[$subject]['current_fbo']]:[]));
    foreach($rows as &$r) $r['metrics']=fcc_team_metrics($r['user_id'],$days,$graph,$ccMap);unset($r);
    $profile=$subject?['user_id'=>$subject,'name'=>$graph[$subject]['name'],'fbo_id'=>$graph[$subject]['current_fbo'],'phone'=>!empty($graph[$subject]['contact_confirmed'])?$graph[$subject]['phone']:'','metrics'=>fcc_team_metrics($subject,$days,$graph,$ccMap),'sponsor'=>fcc_team_sponsor($subject,$graph),'first_manager'=>fcc_team_first_manager($subject,$graph),'links'=>fcc_team_links($subject,$graph)]:null;
    $primary=fcc_team_primary($actor,$graph);
    return ['ready'=>fcc_team_ready(),'sponsor'=>fcc_team_sponsor($actor,$graph),'first_manager'=>fcc_team_first_manager($actor,$graph),'members'=>$rows,'total'=>count($all),'page'=>$page,'days'=>$days,'search'=>$search,'depth'=>$depth,'profile'=>$profile,
        'shared_primary'=>$primary&&$primary!==$actor?['user_id'=>$primary,'name'=>$graph[$primary]['name']]:null,'full_structure'=>!empty($graph[$primary]['manager']),
        'work'=>fcc_team_work_list($actor,$subject?:$actor),'work_inbox'=>$subject?[]:fcc_team_work_inbox($actor),'contact'=>$primary===$actor?fcc_team_record($actor):null,'upline'=>array_map(fn($id)=>['user_id'=>$id,'name'=>$graph[$id]['name']],fcc_team_upline($actor,$graph))];
}
