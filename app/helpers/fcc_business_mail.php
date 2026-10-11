<?php
defined('ALTUMCODE') || die();
function fcc_business_mail_payload(array $r,string $kind): array {
 $s=fcc_partner_one('SELECT s.*,p.link_id,p.language FROM fcc_business_shares s JOIN fcc_business_presentations p ON p.id=s.presentation_id WHERE s.id=? AND s.user_id=?',[(int)$r['share_id'],(int)$r['user_id']]);
 $owner=$s?fcc_business_owner((int)$r['user_id'],(int)$s['link_id']):null;if(!$owner)throw new RuntimeException(fcc_t('Owner unavailable.'));$snapshot=json_decode($r['content_snapshot']??'{}',true)?:[];$language=$snapshot['language']??$s['language'];$t=fn($hr,$en)=>fcc_business_translate($language,$hr,$en);
 if($kind==='owner'){
  $ownerLanguage=fcc_partner_one('SELECT language FROM users WHERE user_id=?',[(int)$r['user_id']]);
  if(fcc_new_locale($ownerLanguage['language']??'Hrvatski')){
   $locale=fcc_locale($ownerLanguage['language']);$url=SITE_URL.$locale.'/partner/contacts?id='.$r['contact_id'];
   $fields=['Ime'=>$r['name'],'Način javljanja'=>$r['channel'],'Email'=>$r['email'],'Telefon'=>$r['phone'],'Zemlja'=>fcc_business_country_label($r['country'],$locale),'Pitanje'=>$r['question']];
   $text=fcc_t('Stigao je novi upit preko tvoje prezentacije.',[],$locale)."\n\n";
   foreach($fields as $label=>$value)$text.=fcc_t($label,[],$locale).': '.$value."\n";
   $text.="\n".fcc_t('Otvori kontakt',[],$locale).': '.$url;
   return ['to'=>$owner['email'],'language'=>$locale,'subject'=>fcc_t('Novi upit o poslovnoj suradnji',[],$locale),'text'=>$text,'contact_url'=>$url];
  }
  if(fcc_locale($ownerLanguage['language']??'Hrvatski')==='sr'){
   $url=SITE_URL.'sr/partner/contacts?id='.$r['contact_id'];
   return ['to'=>$owner['email'],'language'=>'sr','subject'=>'Novi upit o poslovnoj saradnji','text'=>"Stigao je novi upit preko tvoje prezentacije.\n\nIme: ".$r['name']."\nNačin javljanja: ".$r['channel']."\nEmail: ".$r['email']."\nTelefon: ".$r['phone']."\nZemlja: ".fcc_business_country_label($r['country'],'sr')."\nPitanje: ".$r['question']."\n\nOtvori kontakt: ".$url,'contact_url'=>$url];
  }
  if(fcc_locale($ownerLanguage['language']??'Hrvatski')==='de'){
   $url=SITE_URL.'de/partner/contacts?id='.$r['contact_id'];
   return ['to'=>$owner['email'],'language'=>'de','subject'=>'Neue Anfrage zur Zusammenarbeit','text'=>"Über deine Präsentation ist eine neue Anfrage eingegangen.\n\nName: ".$r['name']."\nKontaktweg: ".$r['channel']."\nE-Mail: ".$r['email']."\nTelefon: ".$r['phone']."\nLand: ".fcc_business_country_label($r['country'],'de')."\nFrage: ".$r['question']."\n\nKontakt öffnen: ".$url,'contact_url'=>$url];
  }
  if(fcc_locale($ownerLanguage['language']??'Hrvatski')==='en'){
   $url=SITE_URL.'en/partner/contacts?id='.$r['contact_id'];
   return ['to'=>$owner['email'],'language'=>'en','subject'=>'New enquiry about the business','text'=>"A new enquiry has arrived through your presentation.\n\nName: ".$r['name']."\nContact channel: ".$r['channel']."\nEmail: ".$r['email']."\nPhone: ".$r['phone']."\nCountry: ".fcc_business_country_label($r['country'],'en')."\nQuestion: ".$r['question']."\n\nOpen the contact: ".$url,'contact_url'=>$url];
  }
  if(fcc_locale($ownerLanguage['language']??'Hrvatski')==='sl'){
   $url=SITE_URL.'sl/partner/contacts?id='.$r['contact_id'];
   return ['to'=>$owner['email'],'language'=>'sl','subject'=>'Novo povpraševanje o poslovnem sodelovanju','text'=>"Prek tvoje predstavitve je prispelo novo povpraševanje.\n\nIme: ".$r['name']."\nNačin stika: ".$r['channel']."\nE-pošta: ".$r['email']."\nTelefon: ".$r['phone']."\nDržava: ".fcc_business_country_label($r['country'],'sl')."\nVprašanje: ".$r['question']."\n\nOdpri stik: ".$url,'contact_url'=>$url];
  }
  $url=SITE_URL.'partner/contacts?id='.$r['contact_id'];
  return ['to'=>$owner['email'],'subject'=>'Novi upit o poslovnoj suradnji','text'=>"Primljen je novi upit preko tvoje prezentacije.\n\nIme: ".$r['name']."\nNačin javljanja: ".$r['channel']."\nEmail: ".$r['email']."\nTelefon: ".$r['phone']."\nZemlja: ".(fcc_business_countries()[$r['country']]??'')."\nPitanje: ".$r['question']."\n\nOtvori kontakt: ".$url,'contact_url'=>$url];
 }
 $manage=SITE_URL.$language.'/business-request/'.fcc_business_unseal($r['management_sealed']);
 return ['to'=>$r['email'],'reply_to'=>$owner['email']?:null,'language'=>$language,'subject'=>$t('Tvoj upit je zaprimljen','Your request has been received'),'manage_url'=>$manage,
  'text'=>$t('Hvala na interesu za predstavljanje Forever poslovanja. Tvoj upit poslan je osobi ','Thank you for your interest in the Forever business presentation. Your request has been sent to ').$owner['name'].".\n\n".$t('Prezentacija: ','Presentation: ').fcc_business_share_destination($s,$language)['url']."\n".$t('Traženi način odgovora: ','Requested reply channel: ').$r['channel']."\n\n".$t('Kontakt: ','Contact: ').$owner['name']."\nEmail: ".$owner['email'].($owner['phone']?"\nWhatsApp: https://wa.me/".$owner['phone']:'')."\n\n".$t('Upravljaj svojim upitom: ','Manage your request: ').$manage."\n".$t('Privatnost: ','Privacy: ').fcc_legal_url('privacy')];
}
function fcc_business_mail_send(array $payload,?callable $sender=null): string {
 if(!$sender){if(!cache()){\Altum\Cache::initialize();\Altum\Plugin::initialize();}if(!\Altum\Language::$languages)\Altum\Language::initialize();$sender='send_mail';}
 $html='<div style="font:16px/1.65 Arial,sans-serif;color:#183b37">'.nl2br(fcc_partner_h($payload['text'])).'</div>';
 foreach(['contact_url'=>fcc_t('Otvori kontakt',[],$payload['language']??'hr'),'manage_url'=>fcc_business_translate($payload['language']??'hr','Upravljaj upitom','Manage your request')] as $k=>$label)if(!empty($payload[$k]))$html.='<p><a href="'.fcc_partner_h($payload[$k]).'">'.fcc_partner_h($label).'</a></p>';
 try{$r=$sender($payload['to'],$payload['subject'],$html,['is_system_email'=>true,'is_broadcast'=>false,'language'=>fcc_language_name($payload['language']??'hr')??'english','return_transport_result'=>true,'skip_default_copies'=>true,'text_content'=>$payload['text']],$payload['reply_to']??null);}catch(Throwable $e){return 'delivery_unknown';}
 if($r===true||(is_object($r)&&!empty($r->success)))return 'sent';if($r===false)return 'pending';
 if(is_object($r)&&($r->transport??'')==='brevo_api'&&empty($r->fallback_used)){if((int)($r->status_code??0)===429)return 'pending';if((int)($r->status_code??0)>=400&&(int)$r->status_code<500)return 'failed';}return 'delivery_unknown';
}
function fcc_business_run_jobs(int $lead=0,int $limit=20,?callable $sender=null): array {
 $stats=['captured'=>0,'sent'=>0,'failed'=>0];if(!fcc_business_enabled())return $stats;
 fcc_partner_query("UPDATE fcc_business_jobs SET state='delivery_unknown',updated_at=UTC_TIMESTAMP() WHERE state='sending' AND updated_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL 5 MINUTE)");
 $jobs=fcc_partner_rows("SELECT j.id,r.user_id FROM fcc_business_jobs j JOIN fcc_business_leads r ON r.id=j.lead_id WHERE j.state='pending' AND j.due_at<=UTC_TIMESTAMP()".($lead?' AND r.id=?':'').' ORDER BY j.id LIMIT '.max(1,min(100,$limit)),$lead?[$lead]:[]);
 foreach($jobs as $candidate){
  $job=fcc_business_atomic((int)$candidate['user_id'],function()use($candidate){
   $j=fcc_partner_one('SELECT * FROM fcc_business_jobs WHERE id=? FOR UPDATE',[(int)$candidate['id']]);if(!$j||$j['state']!=='pending')return null;
   $r=fcc_partner_one('SELECT * FROM fcc_business_leads WHERE id=? FOR UPDATE',[(int)$j['lead_id']]);
   if(!$r||$r['status']!=='received'||!(fcc_rollout_managed()?fcc_rollout_public_owner((int)$r['user_id']):fcc_business_enabled((int)$r['user_id']))){fcc_partner_query("UPDATE fcc_business_jobs SET state='cancelled' WHERE id=?",[(int)$j['id']]);return null;}
   try{$p=fcc_business_mail_payload($r,$j['kind']);}catch(Throwable $e){fcc_partner_query("UPDATE fcc_business_jobs SET state='blocked' WHERE id=?",[(int)$j['id']]);return null;}
   if(!filter_var($p['to'],FILTER_VALIDATE_EMAIL)){fcc_partner_query("UPDATE fcc_business_jobs SET state='failed' WHERE id=?",[(int)$j['id']]);return null;}
   if(fcc_business_local()){fcc_partner_query("UPDATE fcc_business_jobs SET state='local_captured',payload_json=?,completed_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=?",[fcc_business_json($p),(int)$j['id']]);return ['captured'=>true];}
   if($r['is_demo']||parse_url(SITE_URL,PHP_URL_SCHEME)!=='https'){fcc_partner_query("UPDATE fcc_business_jobs SET state='blocked' WHERE id=?",[(int)$j['id']]);return null;}
   fcc_partner_query("UPDATE fcc_business_jobs SET state='sending',attempts=attempts+1,updated_at=UTC_TIMESTAMP() WHERE id=?",[(int)$j['id']]);return ['id'=>(int)$j['id'],'payload'=>$p,'attempts'=>(int)$j['attempts']+1];
  });
  if(!$job)continue;if(!empty($job['captured'])){$stats['captured']++;continue;}
  $state=fcc_business_mail_send($job['payload'],$sender);if($state==='pending'&&$job['attempts']>=5)$state='failed';$next=gmdate('Y-m-d H:i:s',time()+min(3600,60*2**$job['attempts']));
  fcc_partner_query("UPDATE fcc_business_jobs SET state=?,due_at=?,completed_at=?,updated_at=UTC_TIMESTAMP() WHERE id=? AND state='sending'",[$state,$next,$state==='sent'?gmdate('Y-m-d H:i:s'):null,$job['id']]);$stats[$state==='sent'?'sent':'failed']++;
 }return $stats;
}
function fcc_business_retention(): int {
 if(!fcc_business_enabled())return 0;$days=90;
 $rows=fcc_partner_rows("SELECT id FROM fcc_business_leads WHERE status<>'erased' AND retention_until<=UTC_TIMESTAMP() LIMIT 50");
 foreach($rows as $r)fcc_business_withdraw((int)$r['id'],true);fcc_partner_query('DELETE FROM fcc_business_rate_limits WHERE window_start<?',[time()-86400]);return count($rows);
}
