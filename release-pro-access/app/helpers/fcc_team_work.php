<?php
defined('ALTUMCODE') || die();

function fcc_team_work_signature(int $actor,int $subject): string {
    if(!fcc_pro_has_access($actor))return '';
    if(fcc_team_admin($actor)) return hash('sha256','admin:'.$actor);
    $graph=fcc_team_graph();$primary=fcc_team_primary($actor,$graph);$subject=fcc_team_primary($subject,$graph);
    if(!$primary||!$subject||!fcc_team_can_view($actor,$subject,$graph))return '';
    $path=fcc_team_upline($subject,$graph,empty($graph[$primary]['manager']));$stamp=[];$child=$subject;
    if($actor!==$primary)$stamp[]=['shared_actor',$actor,$primary,(int)$graph[$actor]['account_version']];
    foreach($path as $parent) {
        $stamp[]=[$child,(int)$graph[$child]['relationship_version'],$parent];
        if($parent===$primary) return hash('sha256',json_encode($stamp));
        $child=$parent;
    }
    return '';
}
function fcc_team_work_allowed(int $actor,array $work): bool {
    if(!fcc_pro_has_access($actor))return false;
    if(fcc_team_admin($actor)) return true;
    $member=(int)$work['member_user_id'];
    $graph=fcc_team_graph();$primary=fcc_team_primary($actor,$graph);
    if(!$primary||!fcc_team_can_view($actor,$member,$graph))return false;
    if($primary===fcc_team_primary($member,$graph)) return true;
    $collaborator=(int)$work['collaborator_user_id'];
    if($primary!==fcc_team_primary($collaborator,$graph)) return false;
    $signature=fcc_team_work_signature($collaborator,$member);
    return $signature!==''&&hash_equals($work['access_signature']??'', $signature);
}
function fcc_team_work_list(int $actor,int $subject): array {
    if(!fcc_team_ready()) return [];
    fcc_team_require($actor,$subject);
    $ids=fcc_team_account_ids($subject);if(!$ids)return [];$marks=implode(',',array_fill(0,count($ids),'?'));
    $rows=fcc_partner_rows("SELECT w.*,u.name collaborator_name,a.name assignee_name FROM fcc_team_work w JOIN users u ON u.user_id=w.collaborator_user_id JOIN users a ON a.user_id=w.assignee_user_id WHERE w.member_user_id IN ($marks) ORDER BY (w.status='done'),COALESCE(w.due_date,'9999-12-31'),w.id DESC LIMIT 100",$ids);
    $result=[];
    foreach($rows as $w) {
        if(!fcc_team_work_allowed($actor,$w)) continue;
        $w['notes']=fcc_partner_rows('SELECT n.id,n.body,n.created_at,u.name FROM fcc_team_work_notes n JOIN users u ON u.user_id=n.actor_user_id WHERE n.work_id=? ORDER BY n.id DESC LIMIT 20',[(int)$w['id']]);
        $w['shared_contact']=$w['shared_contact_id']?fcc_partner_one("SELECT name,phone,email FROM fcc_partner_contacts WHERE id=? AND user_id=? AND contact_permission<>'do_not_contact'",[(int)$w['shared_contact_id'],(int)$w['member_user_id']]):null;
        $result[]=$w;
    }
    return $result;
}
/** Only the selected collaborator sees the incoming help and follow up list. */
function fcc_team_work_inbox(int $actor): array {
    if(!fcc_pro_has_access($actor))return [];
    if(!fcc_team_ready()) return [];
    $ids=fcc_team_account_ids($actor);if(!$ids)return [];$marks=implode(',',array_fill(0,count($ids),'?'));
    $rows=fcc_partner_rows("SELECT w.*,u.name member_name FROM fcc_team_work w JOIN users u ON u.user_id=w.member_user_id WHERE w.collaborator_user_id IN ($marks) AND w.status<>'done' ORDER BY COALESCE(w.due_date,'9999-12-31'),w.id DESC",$ids);
    return array_values(array_filter($rows,fn($w)=>fcc_team_work_allowed($actor,$w)));
}
function fcc_team_work_mutate(int $actor,array $in): int {
    fcc_pro_require($actor);
    if(!fcc_team_ready()) throw new InvalidArgumentException('Timski rad još nije uključen.');
    $op=$in['team_operation']??'create'; $key=fcc_partner_text($in['request_key']??'',32);
    if(!preg_match('/^[a-f0-9]{32}$/D',$key)) throw new InvalidArgumentException('Osvježi obrazac i pokušaj ponovno.');
    $id=(int)($in['work_id']??0);
    if(!(int)(fcc_partner_one("SELECT GET_LOCK('fcc_team_graph',10) ok")['ok']??0)) throw new InvalidArgumentException('Povezivanje je u tijeku. Pokušaj ponovno.');
    database()->begin_transaction();
    try {
        if($op==='create') {
            $subject=(int)($in['member_id']??$actor); fcc_team_require($actor,$subject);
            $collaborator=$actor===$subject?(int)($in['collaborator_id']??0):$actor;
            if($collaborator===$subject||!fcc_team_can_view($collaborator,$subject)) throw new InvalidArgumentException('Odaberi sponzora iz svoje dopuštene linije.');
            $title=fcc_partner_text($in['title']??'',180); if($title==='') throw new InvalidArgumentException('Upiši temu dogovora.');
            $due=fcc_partner_date($in['due_date']??'');
            $assignee=(int)($in['assignee_id']??$subject);
            if(!in_array($assignee,[$subject,$collaborator],true)) throw new InvalidArgumentException('Odgovorna osoba treba biti sudionik dogovora.');
            $prior=fcc_partner_one('SELECT id FROM fcc_team_work WHERE created_by_user_id=? AND request_key=?',[$actor,$key]);
            if($prior) { database()->commit(); return $subject; }
            fcc_partner_query('INSERT INTO fcc_team_work(member_user_id,collaborator_user_id,created_by_user_id,title,due_date,assignee_user_id,request_key,access_signature,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',[$subject,$collaborator,$actor,$title,$due,$assignee,$key,fcc_team_work_signature($collaborator,$subject)]);
            $id=(int)database()->insert_id;
        } else {
            $w=fcc_partner_one('SELECT * FROM fcc_team_work WHERE id=? FOR UPDATE',[$id]);
            if(!$w||!fcc_team_work_allowed($actor,$w)) throw new InvalidArgumentException('Dogovor nije dostupan.');
            $subject=(int)$w['member_user_id'];
            if($op==='note') {
                $body=fcc_partner_text($in['body']??'',1500); if(!$body) throw new InvalidArgumentException('Upiši bilješku.');
                fcc_partner_query('INSERT IGNORE INTO fcc_team_work_notes(work_id,actor_user_id,body,request_key,created_at) VALUES (?,?,?,?,UTC_TIMESTAMP())',[$id,$actor,$body,$key]);
            } else {
                if((int)($in['version']??0)!==(int)$w['version']) throw new InvalidArgumentException('Dogovor je promijenjen. Osvježi prikaz.');
                if($op==='contact') {
                    if($actor!==$subject) throw new InvalidArgumentException('Suradnik osobno odabire koji kontakt dijeli.');
                    $cid=(int)($in['contact_id']??0);
                    if($cid&&!fcc_partner_one("SELECT id FROM fcc_partner_contacts WHERE id=? AND user_id=? AND contact_permission<>'do_not_contact'",[$cid,$subject])) throw new InvalidArgumentException('Kontakt nije dostupan za dijeljenje.');
                    fcc_partner_query('UPDATE fcc_team_work SET shared_contact_id=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE id=?',[$cid?:null,$id]);
                } elseif($op==='update') {
                    $status=fcc_partner_choice($in['status']??'',['open'=>1,'in_progress'=>1,'done'=>1]);
                    $assignee=(int)($in['assignee_id']??0);
                    if(!in_array($assignee,[$subject,(int)$w['collaborator_user_id']],true)) throw new InvalidArgumentException('Odaberi sudionika dogovora.');
                    fcc_partner_query('UPDATE fcc_team_work SET status=?,due_date=?,assignee_user_id=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE id=?',[$status,fcc_partner_date($in['due_date']??''),$assignee,$id]);
                } else throw new InvalidArgumentException('Radnja nije dostupna.');
            }
        }
        database()->commit();
        return $subject;
    } catch(Throwable $e) { database()->rollback(); throw $e; }
    finally { fcc_partner_query("SELECT RELEASE_LOCK('fcc_team_graph')"); }
}
function fcc_team_contact_save(int $uid,array $in): void {
    $m=fcc_team_record($uid);
    if(!$m) throw new InvalidArgumentException('Administrator najprije treba potvrditi tvoje povezivanje.');
    $phone=fcc_partner_contact_phone(fcc_partner_text($in['team_phone']??'',30),true);
    if(!empty($in['team_phone'])&&!$phone) throw new InvalidArgumentException('Telefon upiši s međunarodnim pozivnim brojem.');
    $confirmed=$phone&&!empty($in['team_contact_confirmed'])?1:0;
    database()->begin_transaction();
    try {
        $before=fcc_partner_one('SELECT * FROM fcc_team_members WHERE user_id=? FOR UPDATE',[$uid]);
        $q=fcc_partner_query('UPDATE fcc_team_members SET phone=?,contact_confirmed=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND version=?',[$phone,$confirmed,$uid,(int)($in['team_version']??0)]);
        if($q->affected_rows!==1) throw new InvalidArgumentException('Kontakt je promijenjen. Osvježi stranicu.');
        fcc_partner_query('INSERT INTO fcc_team_audit(user_id,actor_user_id,before_json,after_json,created_at) VALUES (?,?,?,?,UTC_TIMESTAMP())',[$uid,$uid,json_encode($before),json_encode(fcc_team_record($uid)+['contact_source'=>'owner'])]);
        database()->commit();
    } catch(Throwable $e) { database()->rollback(); throw $e; }
}
