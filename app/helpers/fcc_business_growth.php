<?php
defined('ALTUMCODE') || die();
function fcc_business_growth_context(int $uid): array {
 if(!fcc_business_enabled($uid))return [];
 $s=fcc_partner_one('SELECT COUNT(*) prepared,SUM(sent_at IS NOT NULL AND is_demo=0) self_reported_sent,SUM(is_demo=1) demo_prepared FROM fcc_business_shares WHERE user_id=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY)',[$uid]);
 $r=fcc_partner_one("SELECT COUNT(*) received,SUM(is_demo=0 AND needs_review=0) real_interests FROM fcc_business_leads WHERE user_id=? AND status='received' AND received_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY)",[$uid]);
 $work=fcc_business_work($uid);$next=null;foreach($work['items'] as $item)if($item['waiting']||$item['due']){$next=['kind'=>$item['waiting']?'reply':'followup','url'=>$item['url'],'source'=>$item['is_demo']?'demo_request':'recorded_request','is_demo'=>(bool)$item['is_demo'],'requires_personal_confirmation'=>true];break;}
 $existing=['next_action'=>$next,'work_counts'=>$work['counts'],'learning'=>['tool'=>SITE_URL.'partner-marketing','steps'=>['Pogledaj svoju prezentaciju.','Pripremi i osobno podijeli poziv.','Odgovori na upit i zabilježi dogovor.'],'completion'=>'Odgovor gosta nije uvjet. Pripremu i slanje potvrđuje suradnik.'],'counts'=>array_map('intval',array_merge($s,$r)),'tool'=>SITE_URL.'partner-marketing','capabilities'=>['purchase_tracking'=>false,'enrollment_tracking'=>false,'whatsapp_delivery_tracking'=>false],
  'guidance'=>'Marketing plan je samostalna prezentacija, ne webinar. Upit traži osobni odgovor odabranim kanalom. Klik nije slanje, kupnja ili učlanjenje. Probne radnje nisu poslovni rezultat. Odgovor gosta nije uvjet završavanja dnevnog koraka.'];
 if(!fcc_business_landing_access($uid))return $existing;
 $stats=fcc_business_landing_stats($uid,$uid)['totals'];return fcc_business_landing_coach_growth_context($uid,$existing,true,['views'=>$stats['views'],'unique_views'=>$stats['visitors'],'outbound_forever_clicks'=>$stats['outbound'],'whatsapp_opens'=>$stats['whatsapp'],'contact_requests'=>$stats['leads']]);
}
