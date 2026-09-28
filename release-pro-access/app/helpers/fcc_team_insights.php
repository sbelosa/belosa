<?php
defined('ALTUMCODE') || die();

function fcc_team_search_text(string $text): string {
    return strtr(mb_strtolower($text),['č'=>'c','ć'=>'c','ž'=>'z','š'=>'s','đ'=>'dj']);
}
/** Read the latest reported month, keeping missing values distinct from zero. */
function fcc_team_cc_map(array $fbos): array {
    $fbos=array_values(array_unique(array_filter($fbos,fn($f)=>is_string($f)&&preg_match('/^[0-9]{12}$/D',$f))));
    if(!$fbos)return [];
    $month=substr(fcc_partner_today(),0,7).'-01';$marks=implode(',',array_fill(0,count($fbos),'?'));
    $rows=fcc_partner_rows("SELECT fbo_id,period_month,personal_cc,total_cc,total_active_cc,is_4cc_active,updated_at FROM forever_business_metrics WHERE fbo_id IN ($marks) AND period_month<=? ORDER BY period_month DESC",array_merge($fbos,[$month]));
    $out=[];
    foreach($rows as $r) {
        $fbo=$r['fbo_id'];
        foreach(['personal_cc','total_cc','total_active_cc'] as $k)$r[$k]=$r[$k]===null?null:(float)$r[$k];
        if(!isset($out[$fbo])) {
            $r['stale']=$r['period_month']!==$month||strtotime($r['updated_at'].' UTC')<time()-3*86400;
            $r['source']='FLP360 sinkronizacija';$r['previous_personal_cc']=null;
            $out[$fbo]=$r;
        } elseif($r['period_month']===date('Y-m-01',strtotime($out[$fbo]['period_month'].' -1 month'))) {
            $out[$fbo]['previous_personal_cc']=$r['personal_cc'];
        }
    }
    return $out;
}
/** FCC activity is combined; shared Forever CC is returned exactly once. */
function fcc_team_metrics(int $uid,int $days=30,?array $graph=null,?array $ccMap=null): array {
    $graph=$graph??fcc_team_graph();$primary=fcc_team_primary($uid,$graph);$ids=fcc_team_account_ids($uid,$graph);
    if(!$primary||!$ids)throw new InvalidArgumentException('Timski račun nije dostupan.');
    $result=fcc_team_account_metrics($primary,$days);$result['account_progress']=[];
    foreach($ids as $id) {
        $m=$id===$primary?$result:fcc_team_account_metrics($id,$days);
        $result['account_progress'][]=['user_id'=>$id,'name'=>$graph[$id]['name'],'progress'=>$m['progress'],'cycle_status'=>$m['cycle_status']];
        if($id===$primary)continue;
        foreach(['steps','prepared','sent','registrations','verified_registrations','clicks'] as $key)$result[$key]+=$m[$key];
        $result['last_activity']=max($result['last_activity']??'',$m['last_activity']??'')?:null;
    }
    $fbo=$graph[$primary]['current_fbo'];$ccMap=$ccMap??fcc_team_cc_map([$fbo]);$result['cc']=$ccMap[$fbo]??null;
    return $result;
}
/** Bounded coach context is built from the same authorization graph as Moj tim. */
function fcc_team_coach_context(int $actor,string $text=''): array {
    if(!fcc_pro_has_access($actor))return [];
    if(!fcc_team_ready())return [];
    $graph=fcc_team_graph();$visible=fcc_team_visible_members($actor,$graph);
    if(!$visible)return ['total'=>0,'members'=>[],'scope'=>'Nema potvrđenih suradnika u dopuštenoj liniji.'];
    $ids=[];$owners=[];
    foreach($visible as $m)foreach($m['account_ids'] as $id){$ids[]=$id;$owners[$id]=$m['user_id'];}
    $ids=array_values(array_unique($ids));$marks=implode(',',array_fill(0,count($ids),'?'));
    $since=(new DateTimeImmutable('now',new DateTimeZone('Europe/Zagreb')))->setTime(0,0)->modify('-29 days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $signals=[];
    $queries=[
        'steps'=>["SELECT recorded_by_user_id user_id,COUNT(*) n,MAX(created_at) last_at FROM forever_business_daily_outcomes WHERE recorded_by_user_id IN ($marks) AND status='done' AND created_at>=? GROUP BY recorded_by_user_id",array_merge($ids,[$since])],
        'sent'=>["SELECT user_id,COUNT(*) n FROM (SELECT a.user_id FROM fcc_webinar_invite_attempts a JOIN fcc_webinar_sessions s ON s.id=a.session_id WHERE a.user_id IN ($marks) AND a.is_demo=0 AND s.is_demo=0 AND a.revoked_at IS NULL AND a.sent_self_reported_at IS NOT NULL AND a.prepared_at>=? UNION ALL SELECT user_id FROM fcc_business_shares WHERE user_id IN ($marks) AND is_demo=0 AND revoked_at IS NULL AND sent_at IS NOT NULL AND created_at>=?) q GROUP BY user_id",array_merge($ids,[$since],$ids,[$since])],
        'verified_registrations'=>["SELECT r.user_id,COUNT(*) n FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id WHERE r.user_id IN ($marks) AND r.is_demo=0 AND s.is_demo=0 AND r.is_self=0 AND r.needs_review=0 AND r.status='registered' AND r.email_verified_at IS NOT NULL AND r.received_at>=? GROUP BY r.user_id",array_merge($ids,[$since])]
    ];
    foreach($queries as $key=>[$sql,$params])foreach(fcc_partner_rows($sql,$params) as $r) {
        $owner=$owners[(int)$r['user_id']];$signals[$owner][$key]=($signals[$owner][$key]??0)+(int)$r['n'];
        if(isset($r['last_at']))$signals[$owner]['last_activity']=max($signals[$owner]['last_activity']??'',$r['last_at']);
    }
    $ccs=fcc_team_cc_map(array_column($visible,'fbo_id'));$query=fcc_team_search_text($text);
    foreach($visible as &$m) {
        $id=$m['user_id'];$m['activity']=['days'=>30,'steps'=>$signals[$id]['steps']??0,'sent_self_reported'=>$signals[$id]['sent']??0,'verified_registrations'=>$signals[$id]['verified_registrations']??0,'last_activity'=>$signals[$id]['last_activity']??null];
        $m['cc']=$ccs[$m['fbo_id']]??null;$m['profile_url']=SITE_URL.'partner/team?member='.$id;
        $m['sponsor_profile_url']=$m['sponsor_user_id']?SITE_URL.'partner/team?member='.$m['sponsor_user_id']:null;
        $m['reasons']=[];
        if($m['activity']['verified_registrations']>=2)$m['reasons'][]='Najmanje dvije potvrđene prijave, dogovorite pripremu i praćenje nakon webinara.';
        if($m['activity']['steps']>0)$m['reasons'][]='Ima završene korake, provjerite treba li podršku za sljedeći korak.';
        if($m['cc']&&!$m['cc']['stale']&&($m['cc']['personal_cc']??0)>0)$m['reasons'][]='Ima zabilježene osobne CC u aktualnom mjesecu, pitajte kakvu podršku želi.';
        if(!$m['reasons'])$m['reasons'][]='Nema dovoljno aktualnih signala za prioritet, najprije provjerite stanje s osobom.';
        $tokens=array_filter(explode(' ',fcc_team_search_text($m['name'])),fn($x)=>mb_strlen($x)>=4);
        $m['_selected']=str_contains($query,$m['fbo_id'])||array_filter($tokens,fn($n)=>str_contains($query,$n));
        $m['_priority']=[$m['_selected']?1:0,$m['activity']['verified_registrations']>=2?1:0,$m['activity']['steps']>0?1:0,$m['cc']&&!$m['cc']['stale']&&($m['cc']['personal_cc']??0)>0?1:0];
        unset($m['account_ids']);
    }unset($m);
    usort($visible,fn($a,$b)=>$b['_priority']<=>$a['_priority']?:$a['depth']<=>$b['depth']?:strcmp($a['name'],$b['name']));
    $rows=array_slice($visible,0,12);foreach($rows as &$m)unset($m['_selected'],$m['_priority']);unset($m);
    return ['total'=>count($visible),'included'=>count($rows),'selection'=>'Do 12 dopuštenih profila, prvo osobe spomenute u pitanju, zatim potvrđene prijave, završeni koraci i aktualni osobni CC.','members'=>$rows,
        'scope'=>!empty($graph[fcc_team_primary($actor,$graph)]['manager'])?'Cijela potvrđena struktura, uključujući menadžerske grane.':'Potvrđena linija do prvog menadžera.',
        'limits'=>'CC je agregat po Forever ID broju i razdoblju, nije dokaz kupnje određenog proizvoda niti rezultat FCC poveznice. Prazno nije nula. Stariji CC nije aktualni rezultat. Ne zaključuj da osoba ne radi ako nema zabilježene aktivnosti. Tuđi kontakti, privatne bilješke i telefoni nisu dio ovog konteksta.'];
}
