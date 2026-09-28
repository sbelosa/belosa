<?php
defined('ALTUMCODE') || die();

function fcc_team_accounts_ready(): bool {
    return (bool)fcc_partner_one("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_team_accounts'");
}
/** An identical registration ID alone never grants access to another account. */
function fcc_team_apply_accounts(array $graph,array $accounts): array {
    $map=[];
    foreach($accounts as $a) if(!empty($a['active'])) $map[(int)$a['user_id']]=$a;
    foreach($graph as $uid=>&$row) {
        $row['primary_user_id']=$uid;
        $row['account_version']=0;
        $row['account_updated_at']=null;
        if(!isset($map[$uid])) continue;
        $a=$map[$uid];$primary=(int)$a['primary_user_id'];$owner=$graph[$primary]??null;$self=$map[$primary]??null;
        $valid=$owner&&$self&&(int)$self['primary_user_id']===$primary&&$self['fbo_id']===$a['fbo_id']
            &&(int)$row['status']===1&&(int)$owner['status']===1&&$a['fbo_id']!==''
            &&$a['fbo_id']===$row['current_fbo']&&$a['fbo_id']===$owner['current_fbo']
            &&empty($row['is_privacy_requested'])&&empty($owner['is_privacy_requested']);
        $row['primary_user_id']=$valid?$primary:0;
        $row['account_version']=(int)$a['version'];
        $row['account_updated_at']=$a['updated_at']??null;
        if($valid) {
            $row['manager']=$owner['manager'];
            if($primary!==$uid)foreach(['relationship_version','linked_at','source'] as $field)$row[$field]=$owner[$field]??null;
        }
    }
    unset($row);
    return $graph;
}
function fcc_team_primary(int $uid,array $graph): int {
    return isset($graph[$uid])?(int)($graph[$uid]['primary_user_id']??$uid):0;
}
function fcc_team_account_ids(int $uid,?array $graph=null): array {
    $graph=$graph??fcc_team_graph();$primary=fcc_team_primary($uid,$graph);
    if(!$primary) return [];
    return array_map('intval',array_keys(array_filter($graph,fn($r)=>(int)$r['status']===1&&empty($r['is_privacy_requested'])&&(int)($r['primary_user_id']??$r['user_id'])===$primary)));
}
/** Explicit administrator approval, recorded separately from logins and subscriptions. */
function fcc_team_accounts_save(int $actor,int $primary,array $ids,string $fbo): void {
    if(!fcc_team_admin($actor)||!fcc_team_accounts_ready()) throw new InvalidArgumentException('Zajedničke račune potvrđuje administrator.');
    $ids=array_values(array_unique(array_map('intval',$ids)));sort($ids);
    if(!in_array($primary,$ids,true)||!preg_match('/^[0-9]{12}$/D',$fbo)) throw new InvalidArgumentException('Provjeri glavni račun i Forever ID.');
    if(!(int)(fcc_partner_one("SELECT GET_LOCK('fcc_team_graph',10) ok")['ok']??0)) throw new InvalidArgumentException('Povezivanje je u tijeku.');
    database()->begin_transaction();
    try {
        $graph=fcc_team_graph();$old=fcc_partner_rows('SELECT * FROM fcc_team_accounts FOR UPDATE');
        $existing=[];foreach($old as $a)$existing[(int)$a['user_id']]=$a;
        foreach($ids as $id) {
            $u=$graph[$id]??null;
            if(!$u||(int)$u['status']!==1||$u['current_fbo']!==$fbo||!empty($u['is_privacy_requested'])) throw new InvalidArgumentException('Identitet ili dostupnost zajedničkog računa nisu potvrđeni.');
            if(!empty($existing[$id]['active'])&&$existing[$id]['fbo_id']!==$fbo) throw new InvalidArgumentException('Račun već pripada drugoj potvrđenoj skupini.');
        }
        $next=$existing;
        foreach($next as &$a) if($a['fbo_id']===$fbo)$a['active']=0;unset($a);
        foreach($ids as $id)$next[$id]=['user_id'=>$id,'primary_user_id'=>$primary,'fbo_id'=>$fbo,'active'=>1,'version'=>(int)($existing[$id]['version']??0)+1];
        $proposed=fcc_team_apply_accounts($graph,array_values($next));
        foreach($proposed as $id=>$u) {
            $seen=[];$cursor=$id;
            while($cursor=fcc_team_edge($cursor,$proposed)) {
                if(isset($seen[$cursor])) throw new InvalidArgumentException('Zajednički račun stvara krug u sponzorskoj liniji.');
                $seen[$cursor]=true;
            }
        }
        foreach($existing as $id=>$a) if($a['fbo_id']===$fbo&&!in_array($id,$ids,true)&&!empty($a['active'])) {
            fcc_partner_query('UPDATE fcc_team_accounts SET active=0,version=version+1,actor_user_id=?,updated_at=UTC_TIMESTAMP() WHERE user_id=?',[$actor,$id]);
            fcc_partner_query('INSERT INTO fcc_team_audit(user_id,actor_user_id,before_json,after_json,created_at) VALUES(?,?,?,?,UTC_TIMESTAMP())',[$id,$actor,json_encode(['shared_account'=>$a]),json_encode(['shared_account'=>$next[$id]])]);
        }
        foreach($ids as $id) {
            $a=$existing[$id]??null;
            if($a&&!empty($a['active'])&&(int)$a['primary_user_id']===$primary&&$a['fbo_id']===$fbo)continue;
            fcc_partner_query('INSERT INTO fcc_team_accounts(user_id,primary_user_id,fbo_id,actor_user_id,updated_at) VALUES(?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE primary_user_id=VALUES(primary_user_id),fbo_id=VALUES(fbo_id),active=1,version=version+1,actor_user_id=VALUES(actor_user_id),updated_at=UTC_TIMESTAMP()',[$id,$primary,$fbo,$actor]);
            fcc_partner_query('INSERT INTO fcc_team_audit(user_id,actor_user_id,before_json,after_json,created_at) VALUES(?,?,?,?,UTC_TIMESTAMP())',[$id,$actor,$a?json_encode(['shared_account'=>$a]):null,json_encode(['shared_account'=>$next[$id]])]);
        }
        database()->commit();
    } catch(Throwable $e){database()->rollback();throw $e;}
    finally{fcc_partner_query("SELECT RELEASE_LOCK('fcc_team_graph')");}
}
/** Managers see descendants beyond the first manager; other sponsors retain their local line. */
function fcc_team_visible_members(int $actor,array $graph): array {
    if(!fcc_pro_has_access($actor))return [];
    $primary=fcc_team_primary($actor,$graph);$out=[];
    if(!$primary||(int)($graph[$actor]['status']??0)!==1||!empty($graph[$actor]['is_privacy_requested'])) return [];
    $full=!empty($graph[$primary]['manager']);
    foreach($graph as $id=>$member) {
        if($id===$primary||fcc_team_primary($id,$graph)!==$id)continue;
        $path=fcc_team_upline($id,$graph,!$full);$at=array_search($primary,$path,true);
        if($at===false)continue;
        $sid=fcc_team_edge($id,$graph);
        $out[]=['user_id'=>$id,'name'=>$member['name'],'fbo_id'=>$member['current_fbo'],'depth'=>$at+1,'manager'=>$member['manager'],
            'sponsor_user_id'=>$sid,'sponsor_name'=>$graph[$sid]['name']??'','account_ids'=>fcc_team_account_ids($id,$graph)];
    }
    return $out;
}
