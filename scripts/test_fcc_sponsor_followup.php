<?php
declare(strict_types=1);
/* Custom code: FC-2026-10-15: offline sponsor contact lifecycle regression.
 * Executes the actual production helpers with explicit database and clock boundaries.
 * No bootstrap, real database, network or real member data is used. */
namespace FccSponsorFollowupOffline;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

if(PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
$source = rtrim($argv[1] ?? dirname(__DIR__), '/');
defined('ALTUMCODE') || define('ALTUMCODE', 66);
defined('APP_PATH') || define('APP_PATH', $source.'/app/');
defined('SITE_URL') || define('SITE_URL', 'https://offline.invalid/');
$paths = [APP_PATH.'helpers/fcc_sponsor_followup.php', APP_PATH.'helpers/fcc_journey_tracks.php'];
$sourceHashes = [];
foreach($paths as $path) {
    $code = file_get_contents($path);
    if(!is_string($code) || !str_starts_with($code, '<?php')) throw new RuntimeException('Actual helper missing: '.$path);
    $sourceHashes[$path] = hash_file('sha256', $path);
    if(in_array('--short-wait-negative-control',$argv,true) && str_ends_with($path,'fcc_sponsor_followup.php')) {
        $code=str_replace('const FCC_MP_FOLLOWUP_WAIT = 3 * 86400;','const FCC_MP_FOLLOWUP_WAIT = 2 * 86400;',$code,$mutations);
        if($mutations!==1)throw new RuntimeException('Review the short-wait negative control against the actual helper.');
    }
    eval('namespace FccSponsorFollowupOffline; use DateTimeImmutable,DateTimeZone,InvalidArgumentException,RuntimeException,Throwable;'.substr($code, 5));
}
function actualFunction(string $path, string $name): string {
    $tokens = token_get_all((string) file_get_contents($path));
    for($i=0,$n=count($tokens);$i<$n;$i++) {
        if(!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $j=$i+1;
        while($j<$n && ((is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) || $tokens[$j] === '&')) $j++;
        if(!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $name) continue;
        $out='';$depth=0;$opened=false;
        for($k=$i;$k<$n;$k++) {
            $part=$tokens[$k];$out.=is_array($part)?$part[1]:$part;
            if($part==='{'){$opened=true;$depth++;}
            elseif($part==='}' && $opened && --$depth===0)return $out;
        }
    }
    throw new RuntimeException('Actual function not found: '.$name);
}
foreach(['fcc_team.php'=>'fcc_team_edge','fcc_team_accounts.php'=>'fcc_team_primary'] as $file=>$name) {
    eval('namespace FccSponsorFollowupOffline;'.actualFunction(APP_PATH.'helpers/'.$file,$name));
}
function fcc_locale(?string $locale=null): string { return $locale ?? 'hr'; }
function fcc_t(string $text,array $values=[],?string $locale=null): string { return strtr($text,$values); }
function url(string $path): string { return SITE_URL.$path; }
function fcc_jp_schema(): bool { return true; }
function fcc_team_ready(): bool { return true; }
function fcc_partner_contact_phone(string $phone,bool $required=false): string { return $phone; }
function fcc_team_graph(): array {
    if(isset($GLOBALS['fcc_team_graph_run']))return $GLOBALS['fcc_team_graph_run'];
    $GLOBALS['graph_reads_external']=($GLOBALS['graph_reads_external']??0)+1;
    $GLOBALS['fixture']['graph_reads']++;
    return $GLOBALS['fixture']['graph'];
}
eval('namespace { function fcc_team_graph(): array { return \\FccSponsorFollowupOffline\\fcc_team_graph(); } function fcc_team_edge(int $uid,array $graph): ?int { return \\FccSponsorFollowupOffline\\fcc_team_edge($uid,$graph); } function fcc_jp_schema():bool { return true; } function fcc_t(string $text,array $vars=[],?string $locale=null):string { return \\FccSponsorFollowupOffline\\fcc_t($text,$vars,$locale); } }');

$checks=[];
function check(bool $passed,string $name): void {
    $GLOBALS['checks'][]=['name'=>$name,'passed'=>$passed];
    if(!$passed)throw new RuntimeException('FAIL: '.$name);
}
function utc(string $at): DateTimeImmutable { return new DateTimeImmutable($at,new DateTimeZone('UTC')); }
function now(): DateTimeImmutable { return utc($GLOBALS['fixture']['now']); }
function event(int $id,int $uid,string $kind,array|string $detail,string $at='2026-10-15 08:00:00',int $step=0): array {
    return ['id'=>$id,'user_id'=>$uid,'step_id'=>$step?:null,'kind'=>$kind,
        'detail'=>is_array($detail)?json_encode($detail):$detail,'request_key'=>'','created_at'=>$at];
}
function contact(array $history,int $id,int $member=7,int $sponsor=1,int $progress=101,string $at='2026-10-15 08:00:00',string $kind='sponsor_contact',int $actor=1): array {
    $state=fcc_mp_followup_project($member,$sponsor,$progress,$history,utc($at));
    $d=['sponsor'=>$sponsor,'actor'=>$actor,'progress'=>$progress,'previous'=>$state['revision'],
        'attempt'=>$kind==='sponsor_contact'?$state['attempt']+1:$state['attempt'],'snapshot'=>$state['token']];
    if($kind==='sponsor_contact_undo')$d['target']=$state['contact_id'];
    $e=event($id,$member,$kind,$d,$at);$e['request_key']=fcc_mp_followup_key($kind,$member,$d);
    return $e;
}
function outcome(int $id=101,int $uid=7,int $cycle=700,string $date='2026-10-01'): array {
    return ['outcome_id'=>$id,'uid'=>$uid,'recorded_by_user_id'=>$uid,'journey_cycle_id'=>$cycle,
        'journey_step_id'=>$id+1000,'status'=>'done','difficulty'=>'easy','needs_help'=>0,
        'action_date'=>$date,'completion_mode'=>'standard','created_at'=>$date.' 08:00:00',
        'updated_at'=>$date.' 08:00:00','content_json'=>'{"day":8,"title":"Offline step"}'];
}
function resetFixture(): void {
    if(database()->active() || !empty($GLOBALS['fixture']['advisory_acquired']))throw new RuntimeException('Previous transaction or advisory lock is open.');
    unset($GLOBALS['fcc_team_graph_run']);$GLOBALS['graph_reads_external']=0;$graph=[];
    foreach([1,2,3,7,8,9] as $id) {
        $graph[$id]=['user_id'=>$id,'name'=>'Offline '.$id,'status'=>1,'current_fbo'=>'36000000000'.$id,
            'fbo_id'=>'36000000000'.$id,'sponsor_user_id'=>$id>=7?1:null,'sponsor_fbo_id'=>$id>=7?'360000000001':'',
            'primary_user_id'=>$id,'relationship_version'=>1,'account_version'=>1,'is_privacy_requested'=>0,
            'contact_confirmed'=>1,'phone'=>'+385991234567','manager'=>false];
    }
    $graph[3]['primary_user_id']=1;$graph[3]['current_fbo']=$graph[3]['fbo_id']='360000000001';
    $GLOBALS['fixture']=['now'=>'2026-10-15 08:00:00','graph'=>$graph,'graph_reads'=>0,'events'=>[],
        'outcomes'=>[outcome()],'cycles'=>[['id'=>700,'user_id'=>7,'status'=>'active','version'=>2,'started_at'=>'2026-10-01 08:00:00']],
        'entered'=>[7=>true,8=>true,9=>true],'notices'=>[],'deliveries'=>[],'transactions'=>[],
        'sql'=>[],'locks'=>[],'event_writes'=>0,'fail_write_after'=>null,'advisory_available'=>true,
        'advisory_acquired'=>false,'advisory_attempts'=>0,'advisory_releases'=>0,'lock_trace'=>[]];
}
final class MemoryDatabase {
    private ?array $snapshot=null;
    public int $insert_id=0;
    public function begin_transaction(): bool {
        if($this->snapshot!==null || !$GLOBALS['fixture']['advisory_acquired'])throw new RuntimeException('Graph lock must precede transaction.');
        $this->snapshot=$GLOBALS['fixture'];$GLOBALS['fixture']['transactions'][]='begin';$GLOBALS['fixture']['lock_trace'][]='begin';return true;
    }
    public function commit(): bool {
        if($this->snapshot===null)throw new RuntimeException('Commit without transaction.');
        $this->snapshot=null;$GLOBALS['fixture']['transactions'][]='commit';$GLOBALS['fixture']['lock_trace'][]='commit';return true;
    }
    public function rollback(): bool {
        if($this->snapshot===null)throw new RuntimeException('Rollback without transaction.');
        $GLOBALS['fixture']=$this->snapshot;$this->snapshot=null;$GLOBALS['fixture']['transactions'][]='rollback';$GLOBALS['fixture']['lock_trace'][]='rollback';return true;
    }
    public function active(): bool { return $this->snapshot!==null; }
}
function database(): MemoryDatabase { static $db;return $db??=new MemoryDatabase(); }
function fcc_partner_one(string $sql,array $p=[]): ?array {
    $f=&$GLOBALS['fixture'];$f['sql'][]=[$sql,$p];
    if($sql==='SELECT UTC_TIMESTAMP() followup_now') {
        $GLOBALS['clock_reads_external'][]=['active'=>database()->active(),'locks'=>$f['locks']];
        return ['followup_now'=>$f['clock_override']??$f['now']];
    }
    if($sql==="SELECT GET_LOCK('fcc_team_graph',10) ok") {
        if($p!==[] || database()->active() || $f['advisory_acquired'])throw new RuntimeException('Advisory lock requested out of order.');
        $f['advisory_attempts']++;if(!$f['advisory_available'])return ['ok'=>0];
        $f['advisory_acquired']=true;$f['lock_trace'][]='acquire';return ['ok'=>1];
    }
    if(str_starts_with($sql,'SELECT 1 FROM fcc_partner_notifications WHERE user_id=? AND event_key=?')) {
        foreach($f['notices'] as $notice)if($notice['user_id']===$p[0] && $notice['event_key']===$p[1])return ['1'=>1];
        return null;
    }
    throw new RuntimeException('Unexpected single row SQL: '.$sql);
}
function fcc_partner_query(string $sql,array $p=[]): object {
    $f=&$GLOBALS['fixture'];$f['sql'][]=[$sql,$p];
    if($sql==="SELECT RELEASE_LOCK('fcc_team_graph')") {
        if(database()->active() || !$f['advisory_acquired'] || $p!==[])throw new RuntimeException('Release before transaction closure.');
        $f['advisory_acquired']=false;$f['advisory_releases']++;$f['lock_trace'][]='release';return (object)['affected_rows'=>0];
    }
    if($sql==='SELECT user_id FROM users WHERE user_id IN (?,?) ORDER BY user_id FOR UPDATE') {
        $sorted=$p;sort($sorted,SORT_NUMERIC);
        if(!database()->active() || $p!==$sorted)throw new RuntimeException('Unordered member locks or missing transaction.');
        $f['locks']=$p;return (object)['affected_rows'=>0];
    }
    if(str_starts_with($sql,'INSERT IGNORE INTO fcc_partner_pilot_events')) {
        if(!database()->active() || !$f['locks'])throw new RuntimeException('Write without serialized member.');
        if($f['fail_write_after']!==null && $f['event_writes']>=$f['fail_write_after'])throw new RuntimeException('SYNTHETIC_EVENT_FAILURE');
        foreach($f['events'] as $e)if($e['user_id']===$p[0] && $e['request_key']===$p[4])return (object)['affected_rows'=>0];
        $id=max(array_column($f['events'],'id')?:[0])+1;
        $e=event($id,$p[0],$p[2],$p[3],$f['now'],(int)($p[1]??0));$e['request_key']=$p[4];
        $f['events'][]=$e;$f['event_writes']++;database()->insert_id=$id;return (object)['affected_rows'=>1];
    }
    throw new RuntimeException('Unexpected mutation SQL: '.$sql);
}
function fcc_partner_rows(string $sql,array $p=[]): array {
    $f=&$GLOBALS['fixture'];$f['sql'][]=[$sql,$p];
    if(str_starts_with($sql,'SELECT recorded_by_user_id uid,MAX(outcome_id) outcome_id FROM forever_business_daily_outcomes')) {
        if(!str_contains($sql,'journey_cycle_id IS NOT NULL') || !str_contains($sql,'journey_step_id IS NOT NULL') || !str_contains($sql,"status='done'"))throw new RuntimeException('Progress anchor lost its real-step scope.');
        $anchors=[];foreach($f['outcomes'] as $o)if(in_array($o['recorded_by_user_id'],$p,true) && $o['status']==='done' && $o['journey_cycle_id']!==null && $o['journey_step_id']!==null)$anchors[$o['recorded_by_user_id']]=max($anchors[$o['recorded_by_user_id']]??0,$o['outcome_id']);
        $out=[];foreach($anchors as $uid=>$id)$out[]=['uid'=>$uid,'outcome_id'=>$id];return $out;
    }
    if(str_starts_with($sql,'SELECT id,user_id,kind,detail,request_key,created_at FROM fcc_partner_pilot_events')) {
        if(str_contains($sql,'INTERVAL') || str_contains($sql,'created_at>'))throw new RuntimeException('Contact history must not expire.');
        return array_values(array_filter($f['events'],static fn($e)=>in_array($e['user_id'],$p,true) && in_array($e['kind'],['sponsor_contact','sponsor_contact_undo'],true)));
    }
    if(str_starts_with($sql,'SELECT u.user_id,u.name,r.first_entered_at')) {
        return array_values(array_map(static fn($u)=>['user_id'=>$u['user_id'],'name'=>$u['name'],'first_entered_at'=>!empty($f['entered'][$u['user_id']])?'2026-10-01 08:00:00':null],array_filter($f['graph'],static fn($u)=>in_array($u['user_id'],$p,true) && $u['status']===1)));
    }
    if(str_starts_with($sql,'SELECT c.user_id,c.started_at,COUNT(o.outcome_id) completed')) {
        $out=[];foreach($f['cycles'] as $c)if(in_array($c['user_id'],$p,true) && in_array($c['status'],['active','completed'],true)) {
            $rows=array_values(array_filter($f['outcomes'],static fn($o)=>$o['journey_cycle_id']===$c['id'] && $o['status']==='done'));
            $out[]=['user_id'=>$c['user_id'],'started_at'=>$c['started_at'],'completed'=>count($rows),'last_done'=>$rows?max(array_column($rows,'action_date')):null];
        }return $out;
    }
    if(str_starts_with($sql,'SELECT o.recorded_by_user_id uid,s.content_json')) {
        $out=[];foreach($p as $uid) {
            $rows=array_values(array_filter($f['outcomes'],static fn($o)=>$o['recorded_by_user_id']===$uid && $o['status']==='done' && $o['journey_cycle_id']!==null));
            usort($rows,static fn($a,$b)=>$b['outcome_id']<=>$a['outcome_id']);if($rows)$out[]=['uid'=>$uid,'content_json'=>$rows[0]['content_json']];
        }return $out;
    }
    if(str_starts_with($sql,'SELECT user_id,kind,detail FROM fcc_partner_pilot_events'))return array_values(array_filter($f['events'],static fn($e)=>in_array($e['user_id'],$p,true) && $e['kind']==='track_chosen'));
    if(str_starts_with($sql,'SELECT id,user_id,step_id,kind,detail,request_key,created_at FROM fcc_partner_pilot_events')) {
        $utcCut=now()->modify('-7 days')->format('Y-m-d H:i:s');$wide=now()->modify('-9 days')->format('Y-m-d H:i:s');
        return array_values(array_filter($f['events'],static fn($e)=>in_array($e['user_id'],$p,true) && in_array($e['kind'],['stuck','help_feedback','help_resolved'],true) && $e['created_at']>=$wide && ($e['kind']!=='stuck' || $e['created_at']>=$utcCut)));
    }
    if(str_starts_with($sql,'SELECT o.recorded_by_user_id uid,o.outcome_id,o.journey_step_id,o.difficulty,o.needs_help')) {
        $cut=now()->modify('-7 days')->format('Y-m-d');return array_values(array_filter($f['outcomes'],static fn($o)=>in_array($o['recorded_by_user_id'],$p,true) && $o['journey_cycle_id']!==null && $o['status']==='done' && $o['action_date']>=$cut && ($o['needs_help']===1 || $o['difficulty']==='hard')));
    }
    if($sql==="SELECT DISTINCT user_id FROM fcc_partner_journey_cycles WHERE status IN ('active','completed')") {
        $out=[];foreach($f['cycles'] as $c)if(in_array($c['status'],['active','completed'],true))$out[$c['user_id']]=['user_id'=>$c['user_id']];return array_values($out);
    }
    throw new RuntimeException('Unexpected multi row SQL: '.$sql);
}
function fcc_partner_notify(int $uid,string $key,string $category,string $title,string $body,string $path,int $lifetime,?string $at=null): void {
    $GLOBALS['fixture']['notices'][]=['user_id'=>$uid,'event_key'=>$key,'category'=>$category,'title'=>$title,'body'=>$body,'path'=>$path];
}
function state(int $uid=7,int $sponsor=1): array { return fcc_mp_followup_read($sponsor,[$uid],now())['states'][$uid]; }
function input(string $operation='sent',int $uid=7,int $sponsor=1): array {
    $s=state($uid,$sponsor);return ['member_id'=>$uid,'followup_token'=>$s['token'],'followup_attempt'=>$operation==='sent'?$s['attempt']+1:$s['attempt'],'followup_operation'=>$operation];
}
function record(int $actor,array $input): array { return fcc_mp_followup_record($actor,$input,now()); }
function history(): string { $f=$GLOBALS['fixture'];return serialize([$f['outcomes'],$f['cycles'],$f['entered'],$f['notices'],$f['deliveries']]); }
function mutable(): string { return serialize([$GLOBALS['fixture']['events'],history()]); }
function deny(callable $call,string $name): void {
    $before=mutable();$denied=false;try{$call();}catch(InvalidArgumentException $e){$denied=true;}
    check($denied,$name.'_rejected');check(mutable()===$before,$name.'_no_mutation');
    check(!database()->active() && !$GLOBALS['fixture']['advisory_acquired'],$name.'_locks_closed');
}

try {
    resetFixture();
    $empty=fcc_mp_followup_project(7,1,101,[],utc('2026-10-15 08:00:00'));
    check($empty['attempt']===0 && $empty['due'] && $empty['revision']===0 && $empty['sent_at']===null,'new_progress_is_due');
    $first=contact([],10);$firstHistory=[$first];
    $firstState=fcc_mp_followup_project(7,1,101,$firstHistory,utc('2026-10-15 08:00:00'));
    check($firstState['attempt']===1 && !$firstState['due'] && $firstState['contact_id']===10,'first_contact_hides_immediately');
    check($firstState['due_at']===utc('2026-10-18 08:00:00')->getTimestamp(),'first_contact_wait_is_72_hours');
    check(!fcc_mp_followup_project(7,1,101,$firstHistory,utc('2026-10-18 07:59:59'))['due'],'72_hours_minus_one_second_hidden');
    check(fcc_mp_followup_project(7,1,101,$firstHistory,utc('2026-10-18 08:00:00'))['due'],'exactly_72_hours_due');
    $second=contact($firstHistory,20,7,1,101,'2026-10-18 08:00:00');$two=[$first,$second];
    $quiet=fcc_mp_followup_project(7,1,101,$two,utc('2027-01-15 08:00:00'));
    check($quiet['attempt']===2 && !$quiet['due'] && $quiet['due_at']===null,'second_contact_stays_hidden_months');
    check(fcc_mp_followup_project(7,1,102,$two,utc('2026-10-18 08:00:01'))['attempt']===0,'new_done_identity_resets_contact_cycle');
    check(fcc_mp_followup_project(7,2,101,$two,utc('2026-10-18 08:00:01'))['attempt']===0,'foreign_sponsor_history_is_separate');
    check(fcc_mp_followup_project(8,1,101,$two,utc('2026-10-18 08:00:01'))['attempt']===0,'foreign_member_history_is_separate');
    $shuffled=[$second,$first];$before=serialize($shuffled);
    check(fcc_mp_followup_project(7,1,101,$shuffled,utc('2026-10-18 08:00:00'))['attempt']===2 && serialize($shuffled)===$before,'unordered_projection_is_pure_and_stable');
    $undo=contact($two,30,7,1,101,'2026-10-19 08:00:00','sponsor_contact_undo');
    $afterUndo=fcc_mp_followup_project(7,1,101,[$first,$second,$undo],utc('2026-10-19 08:00:00'));
    check($afterUndo['attempt']===1 && $afterUndo['contact_id']===10 && $afterUndo['due'] && $afterUndo['revision']===30,'undo_second_restores_first_without_restarting_timer');
    check($afterUndo['token']!==fcc_mp_followup_project(7,1,101,$firstHistory,utc('2026-10-19 08:00:00'))['token'],'undo_keeps_new_revision_against_stale_forms');
    $remark=contact([$first,$second,$undo],40,7,1,101,'2026-10-19 08:00:00');
    check(fcc_mp_followup_project(7,1,101,[$first,$second,$undo,$remark],utc('2026-10-19 08:00:00'))['attempt']===2,'remark_after_undo_has_new_chain_key');
    $undoFirst=contact($firstHistory,21,7,1,101,'2026-10-15 08:00:01','sponsor_contact_undo');
    $u0=fcc_mp_followup_project(7,1,101,[$first,$undoFirst],utc('2026-10-15 08:00:01'));
    check($u0['attempt']===0 && $u0['due'] && $u0['revision']===21 && $u0['token']!==$empty['token'],'undo_first_becomes_due_with_retained_audit_revision');

    $dst=contact([],1,7,1,101,'2026-10-24 10:00:00');
    $dstNow=new DateTimeImmutable('2026-10-27 11:00:00',new DateTimeZone('Europe/Zagreb'));
    check(fcc_mp_followup_project(7,1,101,[$dst],$dstNow)['due'],'fall_DST_boundary_is_72_elapsed_hours');
    check(fcc_mp_followup_date(utc('2026-10-24 10:00:00')->getTimestamp())==='24. 10. 2026. 12:00','date_displays_Zagreb_before_DST');
    check(fcc_mp_followup_date(utc('2026-10-27 10:00:00')->getTimestamp())==='27. 10. 2026. 11:00','date_displays_Zagreb_after_DST');
    $spring=contact([],1,7,1,101,'2026-03-28 10:00:00');
    check(!fcc_mp_followup_project(7,1,101,[$spring],utc('2026-03-31 09:59:59'))['due'] && fcc_mp_followup_project(7,1,101,[$spring],utc('2026-03-31 10:00:00'))['due'],'spring_DST_has_exact_elapsed_wait');

    foreach(['bad_json','foreign_actor','foreign_sponsor','foreign_progress','wrong_previous','wrong_key','bad_attempt','missing_snapshot','forged_snapshot','early_second','future_time','invalid_time','wrong_undo_target'] as $case) {
        $e=$case==='early_second'?$second:($case==='wrong_undo_target'?$undo:$first);$d=json_decode($e['detail'],true);$base=$case==='early_second'?$firstHistory:($case==='wrong_undo_target'?$two:[]);
        if($case==='bad_json')$e['detail']='{broken';
        elseif($case==='foreign_actor')$d['actor']=0;
        elseif($case==='foreign_sponsor')$d['sponsor']=2;
        elseif($case==='foreign_progress')$d['progress']=102;
        elseif($case==='wrong_previous')$d['previous']=99;
        elseif($case==='wrong_key')$e['request_key']='wrong';
        elseif($case==='bad_attempt')$d['attempt']=3;
        elseif($case==='missing_snapshot')unset($d['snapshot']);
        elseif($case==='forged_snapshot')$d['snapshot']=str_repeat('a',64);
        elseif($case==='early_second')$e['created_at']='2026-10-18 07:59:59';
        elseif($case==='future_time')$e['created_at']='2030-01-01 00:00:00';
        elseif($case==='invalid_time')$e['created_at']='2026-02-31 08:00:00';
        elseif($case==='wrong_undo_target')$d['target']=10;
        if($case!=='bad_json')$e['detail']=json_encode($d);
        if(!in_array($case,['wrong_key','bad_json'],true))$e['request_key']=fcc_mp_followup_key($e['kind'],7,$d);
        $expected=fcc_mp_followup_project(7,1,101,$base,utc('2026-10-19 08:00:00'));
        check(fcc_mp_followup_project(7,1,101,array_merge($base,[$e]),utc('2026-10-19 08:00:00'))===$expected,'invalid_event_ignored_'.$case);
    }

    resetFixture();$GLOBALS['clock_reads_external']=[];
    check(fcc_mp_followup_now()->format('Y-m-d H:i:s')===$GLOBALS['fixture']['now'],'default_clock_uses_database_time');
    check(fcc_mp_followup_now(new DateTimeImmutable('2026-10-15 10:00:00',new DateTimeZone('Europe/Zagreb')))->format('Y-m-d H:i:s')==='2026-10-15 08:00:00','explicit_clock_normalized_to_UTC');
    $p=input();$GLOBALS['clock_reads_external']=[];
    check(fcc_mp_followup_record(1,$p)===['changed'=>true,'attempt'=>1],'mutator_uses_default_database_clock');
    check(count($GLOBALS['clock_reads_external'])===1 && $GLOBALS['clock_reads_external'][0]===['active'=>true,'locks'=>[1,7]],'database_clock_read_after_member_serialization');
    $s=state();$GLOBALS['clock_reads_external']=[];
    check(fcc_mp_followup_read(1,[7])['states'][7]===$s && count($GLOBALS['clock_reads_external'])===1,'reader_uses_one_canonical_database_clock');
    check(fcc_mp_followup_read(1,[])===['states'=>[],'events'=>[]],'empty_read_has_stable_response_shape');
    resetFixture();$p=input();$GLOBALS['fixture']['clock_override']='invalid';$before=mutable();$failed=false;
    try{fcc_mp_followup_record(1,$p);}catch(RuntimeException $e){$failed=$e->getMessage()==='Contact clock unavailable.';}
    check($failed && mutable()===$before && !database()->active() && !$GLOBALS['fixture']['advisory_acquired'],'unavailable_database_clock_rolls_back_before_write');

    resetFixture();$initial=input();$originalHistory=history();
    $saved=record(1,$initial);$s1=state();
    check($saved===['changed'=>true,'attempt'=>1] && !$s1['due'],'transactional_first_contact');
    check(history()===$originalHistory,'contact_does_not_mutate_progress_or_send_notifications');
    check($GLOBALS['fixture']['lock_trace']===['acquire','begin','commit','release'],'graph_and_user_lock_order');
    check($GLOBALS['fixture']['locks']===[1,7],'member_and_actor_locked_in_numeric_order');
    $audit=array_values(array_filter($GLOBALS['fixture']['events'],static fn($e)=>$e['kind']==='sponsor_contact_handled'));
    check(count($audit)===1 && $audit[0]['user_id']===1 && $audit[0]['step_id']===null,'primary_audit_is_revision_only_not_member_evidence');
    $allBefore=mutable();$firstAt=$s1['sent_at'];
    $GLOBALS['fixture']['now']='2026-10-16 08:00:00';
    check(record(1,$initial)===['changed'=>false,'attempt'=>1] && mutable()===$allBefore && state()['sent_at']===$firstAt,'old_first_form_replay_does_not_extend_wait');
    deny(static fn()=>record(1,input()),'second_before_due');
    $GLOBALS['fixture']['now']='2026-10-18 08:00:00';$secondInput=input();
    check(state()['due'] && record(1,$initial)===['changed'=>false,'attempt'=>1] && state()['attempt']===1,'old_first_form_at_due_does_not_spend_second');
    check(record(3,$secondInput)===['changed'=>true,'attempt'=>2] && state()['attempt']===2,'coowner_records_shared_second_contact');
    $audit=array_values(array_filter($GLOBALS['fixture']['events'],static fn($e)=>$e['kind']==='sponsor_contact_handled'));
    check(count($audit)===3 && array_column(array_slice($audit,1),'user_id')===[3,1],'coowner_and_primary_receive_shared_revision_audit');
    $GLOBALS['fixture']['now']='2027-01-15 08:00:00';check(!state()['due'] && state()['attempt']===2,'read_has_no_contact_expiry_window');
    $secondReplayBefore=mutable();check(record(3,$secondInput)===['changed'=>false,'attempt'=>2] && mutable()===$secondReplayBefore,'second_contact_replay_is_idempotent');
    $undoInput=input('undo');check(record(1,$undoInput)===['changed'=>true,'attempt'=>1] && state()['due'],'transactional_undo_second');
    $undoReplay=mutable();check(record(3,$undoInput)===['changed'=>false,'attempt'=>1] && mutable()===$undoReplay,'undo_replay_cannot_pop_first_contact');
    check(record(1,input())===['changed'=>true,'attempt'=>2],'new_form_can_remark_after_undo');
    $oldSecond=input('undo');$GLOBALS['fixture']['outcomes'][]=outcome(102,7,701,'2027-01-15');
    check(state()['attempt']===0,'new_done_across_cycles_resets');
    deny(static fn()=>record(1,$oldSecond),'old_undo_after_member_progress');

    resetFixture();$GLOBALS['fixture']['outcomes']=[];$GLOBALS['fixture']['cycles']=[];$GLOBALS['fixture']['entered'][7]=false;
    record(1,input());$GLOBALS['fixture']['now']='2026-10-18 08:00:00';record(1,input());$zeroState=state();
    $GLOBALS['fixture']['entered'][7]=true;$GLOBALS['fixture']['cycles'][]=['id'=>700,'user_id'=>7,'status'=>'active','version'=>1,'started_at'=>'2026-10-18 08:00:00'];
    check(state()===$zeroState && state()['attempt']===2,'opening_first_cycle_does_not_reset_second_contact');
    $GLOBALS['fixture']['cycles'][0]['version']=20;$GLOBALS['fixture']['events'][]=event(500,7,'track_chosen','team','2026-10-18 08:00:00');
    check(state()===$zeroState,'draft_version_and_track_do_not_reset');
    $GLOBALS['fixture']['outcomes'][]=outcome(101,7,700,'2026-10-18');$GLOBALS['fixture']['outcomes'][0]['completion_mode']='known';
    check(state()['attempt']===0 && state()['progress']===101,'known_completed_step_resets');
    $sBefore=state();$GLOBALS['fixture']['outcomes'][0]['difficulty']='hard';$GLOBALS['fixture']['outcomes'][0]['needs_help']=1;$GLOBALS['fixture']['outcomes'][0]['updated_at']='2026-10-18 09:00:00';
    check(state()===$sBefore,'feedback_updated_at_does_not_reset_anchor');
    $GLOBALS['fixture']['outcomes'][]=outcome(999,7,700,'2026-10-18');$GLOBALS['fixture']['outcomes'][1]['status']='draft';
    $GLOBALS['fixture']['outcomes'][]=outcome(1000,7,700,'2026-10-18');$GLOBALS['fixture']['outcomes'][2]['journey_step_id']=null;
    check(state()['progress']===101,'draft_and_non_step_outcome_cannot_reset');

    resetFixture();$stale=input();$GLOBALS['fixture']['outcomes'][]=outcome(102,7,700,'2026-10-15');
    deny(static fn()=>record(1,$stale),'stale_form_after_new_done');
    resetFixture();record(1,input());$GLOBALS['fixture']['now']='2026-10-18 08:00:00';$stale=input();record(1,$stale);
    $stale['followup_token']=str_repeat('a',64);deny(static fn()=>record(1,$stale),'unknown_stale_snapshot');
    foreach(['member_zero','token_array','attempt_three','operation_unknown','attempt_decimal'] as $case) {
        resetFixture();$p=input();
        if($case==='member_zero')$p['member_id']=0;
        elseif($case==='token_array')$p['followup_token']=[];
        elseif($case==='attempt_three')$p['followup_attempt']=3;
        elseif($case==='operation_unknown')$p['followup_operation']='automatic_whatsapp_sent';
        elseif($case==='attempt_decimal')$p['followup_attempt']='1.5';
        deny(static fn()=>record(1,$p),'invalid_input_'.$case);
        check($GLOBALS['fixture']['advisory_attempts']===0,'input_rejected_before_locks_'.$case);
    }
    foreach(['foreign_actor','member_not_primary','sponsor_moved','privacy_member','privacy_sponsor','actor_inactive','member_inactive','owner_revoked','fbo_mismatch'] as $case) {
        resetFixture();$p=input();$actor=1;
        if($case==='foreign_actor')$actor=2;
        elseif($case==='member_not_primary')$GLOBALS['fixture']['graph'][7]['primary_user_id']=8;
        elseif($case==='sponsor_moved'){$GLOBALS['fixture']['graph'][7]['sponsor_user_id']=2;$GLOBALS['fixture']['graph'][7]['sponsor_fbo_id']='360000000002';}
        elseif($case==='privacy_member')$GLOBALS['fixture']['graph'][7]['is_privacy_requested']=1;
        elseif($case==='privacy_sponsor')$GLOBALS['fixture']['graph'][1]['is_privacy_requested']=1;
        elseif($case==='actor_inactive')$GLOBALS['fixture']['graph'][1]['status']=0;
        elseif($case==='member_inactive')$GLOBALS['fixture']['graph'][7]['status']=0;
        elseif($case==='owner_revoked'){$actor=3;$GLOBALS['fixture']['graph'][3]['primary_user_id']=0;}
        elseif($case==='fbo_mismatch')$GLOBALS['fixture']['graph'][7]['current_fbo']='360000000009';
        $GLOBALS['fcc_team_graph_run']=['stale'=>['status'=>1]];
        deny(static fn()=>record($actor,$p),'permission_'.$case);
        check($GLOBALS['graph_reads_external']>0,'fresh_graph_'.$case);
    }
    resetFixture();$GLOBALS['fixture']['advisory_available']=false;deny(static fn()=>record(1,input()),'graph_lock_busy');
    check($GLOBALS['fixture']['advisory_releases']===0,'unowned_advisory_not_released');
    foreach([0,1,2] as $failureAt) {
        resetFixture();$p=input();$GLOBALS['fixture']['fail_write_after']=$failureAt;$before=mutable();$failed=false;
        try{record(3,$p);}catch(RuntimeException $e){$failed=$e->getMessage()==='SYNTHETIC_EVENT_FAILURE';}
        check($failed && mutable()===$before,'partial_contact_audit_rollback_'.$failureAt);
        check(!database()->active() && !$GLOBALS['fixture']['advisory_acquired'],'failure_closes_locks_'.$failureAt);
    }

    resetFixture();$GLOBALS['fixture']['now']='2026-10-19 08:00:00';
    $GLOBALS['fixture']['cycles'][]=['id'=>800,'user_id'=>8,'status'=>'active','version'=>1,'started_at'=>'2026-10-01 08:00:00'];
    $GLOBALS['fixture']['outcomes'][]=outcome(201,8,800,'2026-10-19');
    $overview=fcc_mp_sponsor_overview(1,now());
    check(count($overview['groups']['inactive'])===1 && count($overview['groups']['progress'])===1 && count($overview['groups']['not_entered'])===1,'overview_has_baseline_action_groups');
    record(1,input());$overview=fcc_mp_sponsor_overview(1,now());
    check(count($overview['groups']['inactive'])===0 && count($overview['contacted'])===1 && $overview['total']===3,'overview_moves_first_contact_to_history_keeps_totals');
    $co=fcc_mp_sponsor_overview(3,now());
    check(count($co['contacted'])===1 && $co['contacted'][0]['followup']['token']===$overview['contacted'][0]['followup']['token'],'coowner_overview_shares_contact_state');
    $GLOBALS['fixture']['events'][]=event(50,7,'stuck','8|Offline help|Fresh explicit request.','2026-10-19 08:00:00',1101);
    $overview=fcc_mp_sponsor_overview(1,now());
    check(count($overview['groups']['help'])===1 && !$overview['contacted'],'fresh_help_request_bypasses_contact_pause');
    deny(static fn()=>record(1,input()),'help_cannot_be_hidden_by_message_contact');
    check(fcc_mp_weekly_summaries(now())===1,'weekly_summary_generated');
    $notice=$GLOBALS['fixture']['notices'][0];
    check($notice['body']==='Napredovalo: 1. Stoji ili još nije krenulo: 0. Traži pomoć: 1.','weekly_uses_same_actionable_overview_counts');
    check(fcc_mp_weekly_summaries(now())===0 && count($GLOBALS['fixture']['notices'])===1,'weekly_summary_idempotent');
    foreach($sourceHashes as $path=>$hash)check(hash_file('sha256',$path)===$hash,'source_not_mutated_'.basename($path));
    echo json_encode(['ok'=>true,'checks'=>count($checks),'source_hashes'=>$sourceHashes],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch(Throwable $e) {
    fwrite(STDERR,json_encode(['ok'=>false,'checks'=>count($checks),'error'=>$e->getMessage(),'last_checks'=>array_slice($checks,-6),'trace'=>$e->getTraceAsString()],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);exit(1);
}
/* /Custom code: FC-2026-10-15 */
