<?php
defined('ALTUMCODE') || die();
/** Collection is bounded, first party, excludes delegation and impersonation, and never breaks the action. */
function fcc_los_event(int $uid,string $module,string $action,string $outcome,string $request,int $object=0): void {
 try {
  if($uid<=0||session_get('admin_user_id')||\Altum\Teams::is_delegated())return;
  if(!in_array($outcome,['started','success','failure'],true)||!preg_match('/^[a-z0-9_]{1,60}$/D',$action))return;
  static $ready;$ready??=(bool)fcc_partner_one("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_los_events'");if(!$ready)return;
  $module=substr(preg_replace('/[^a-z0-9_]/','',$module),0,32);$request=substr($request,0,80);if(strlen($request)<12)return;
  $device=preg_match('/Mobile|Android|iPhone/i',$_SERVER['HTTP_USER_AGENT']??'')?'mobile':'desktop';
  fcc_partner_query('INSERT IGNORE INTO fcc_los_events(user_id,module,action,outcome,request_key,object_id,version,device,created_at) VALUES(?,?,?,?,?,?,?,?,UTC_TIMESTAMP())',[$uid,$module,$action,$outcome,$request,$object?:null,'los-20261002.1',$device]);
 }catch(Throwable $e){error_log('LOS measurement unavailable: '.get_class($e));}
}
function fcc_los_save_coach_turn(int $uid,string $kind,int $source,string $thread,string $request,string $question,array $reply,string $model,array $usage): void {
 try{
  if(session_get('admin_user_id')||\Altum\Teams::is_delegated())return;
  $ready=fcc_partner_one("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_los_coach_turns'");if(!$ready)return;
  fcc_partner_query('INSERT IGNORE INTO fcc_los_coach_turns(user_id,kind,source_id,thread_key,request_key,question,guidance,recipient_message,model,usage_json,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())',[$uid,$kind,$source,hash('sha256',$thread),$request,$question,$reply['guidance'],$reply['recipient_message'],$model,json_encode($usage)]);
 }catch(Throwable $e){error_log('LOS coach record unavailable: '.get_class($e));}
}
function fcc_los_module(string $route,string $section='',string $type=''): string {
 if($route==='partner')return $section==='share'&&$type==='webinar'?'webinars':($section?:'today');
 return ['links'=>'cards','link'=>'cards','blog'=>'share','blog-post'=>'share','forever-business'=>'points','fcc-results'=>'points','account-plan'=>'settings','account'=>'settings','partner-webinar'=>'webinars','partner-webinar-leads'=>'webinars','partner-business'=>'business','partner-business-leads'=>'business','fcc-ai-hub'=>'coach','fcc-education'=>'education'][$route]??(str_starts_with($route,'admin')?'admin':'other');
}
