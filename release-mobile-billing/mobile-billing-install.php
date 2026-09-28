<?php
/** Reviewed one-time deployment. CLI only; backups and audit stay outside public_html. */
if(PHP_SAPI!=='cli')exit(1);
umask(0077);chdir(__DIR__);
$lock=fopen('mobile-billing-install.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
if(is_file('mobile-billing-install.done')||is_file('mobile-billing-install.failed'))exit;
$root='/home/forevercardclub/public_html/';$published=[];$transaction=false;$committed=false;$phase='preflight';
try {
 $manifest=json_decode(file_get_contents('mobile-billing-manifest.json'),true,512,JSON_THROW_ON_ERROR);
 $audit=json_decode(file_get_contents('mobile-billing-audit.json'),true,512,JSON_THROW_ON_ERROR);
 if(!$audit['stripe_complete']||count($audit['users'])<700)throw new RuntimeException('Complete reviewed audit required');
 $zip=new ZipArchive();if($zip->open('mobile-billing-code.zip')!==true)throw new RuntimeException('Bundle unavailable');
 foreach($manifest as $p=>$h){
  if(str_contains($p,'..')||!preg_match('~^(app|themes)/~',$p))throw new RuntimeException('Invalid path');
  if(hash_file('sha256',$root.$p)!==$h['before'])throw new RuntimeException('Production changed: '.$p);
  $bytes=$zip->getFromName($p);if($bytes===false||hash('sha256',$bytes)!==$h['after'])throw new RuntimeException('Bundle hash: '.$p);
  $stage=__DIR__.'/mobile-billing-stage/'.$p;if(!is_dir(dirname($stage)))mkdir(dirname($stage),0700,true);file_put_contents($stage,$bytes);
  if(str_ends_with($p,'.php')){$output=[];exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($stage).' 2>&1',$output,$rc);if($rc)throw new RuntimeException('PHP lint: '.$p);}
 }
 $backup=new ZipArchive();if($backup->open('mobile-billing-backup.zip',ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('Backup exists or unavailable');
 foreach($manifest as $p=>$h)$backup->addFile($root.$p,$p);if(!$backup->close())throw new RuntimeException('Backup failed');
 // ThemeStyle is published before the wrapper referencing its new property.
 $paths=array_keys($manifest);usort($paths,fn($a,$b)=>($a==='app/core/ThemeStyle.php'?0:1)<=>($b==='app/core/ThemeStyle.php'?0:1));
 $phase='publish';
 foreach($paths as $p){if(hash_file('sha256',$root.$p)!==$manifest[$p]['before'])throw new RuntimeException('Concurrent change: '.$p);$tmp=__DIR__.'/mobile-billing-stage/'.$p;chmod($tmp,0644);if(!rename($tmp,$root.$p))throw new RuntimeException('Publish failed: '.$p);$published[]=$p;}
 define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require $root.'app/init.php';
 \Altum\Cache::initialize();
 if(DATABASE_NAME!=='forevercardclub_database')throw new RuntimeException('Wrong database');
 if(!empty(settings()->paypal->is_enabled)||empty(settings()->stripe->is_enabled))throw new RuntimeException('Stripe-only configuration required');
 $phase='billing_preflight';
 // Recheck current Stripe subscriptions before touching any retired reference.
 $client=new \Stripe\StripeClient(['api_key'=>settings()->stripe->secret_key,'stripe_version'=>'2023-10-16']);$active=[];$args=['status'=>'all','limit'=>100];$seen=0;
 do{$page=$client->subscriptions->all($args);foreach($page->data as $s){$seen++;if(in_array($s->status,['active','trialing','past_due'],true))$active[(int)($s->metadata->user_id??0)]=true;}if(!$page->has_more)break;$items=$page->data;$args['starting_after']=end($items)->id;if($seen>2000)throw new RuntimeException('Incomplete Stripe inventory');}while(true);
 $candidates=array_values(array_filter($audit['users'],fn($u)=>$u['payment_processor']==='paypal'&&!empty($u['payment_subscription_id'])));
 if(count($candidates)!==3)throw new RuntimeException('Reviewed candidate count changed');
 $columns='user_id,plan_id,plan_settings,plan_expiration_date,payment_processor,payment_subscription_id,payment_total_amount,payment_currency,extra';
 $ids=array_map(fn($u)=>(int)$u['user_id'],$candidates);$idSql=implode(',',$ids);
 $before=['users'=>fcc_partner_rows('SELECT '.$columns.' FROM users WHERE user_id IN ('.$idSql.') ORDER BY user_id'),'links'=>fcc_partner_rows('SELECT link_id,user_id,url,is_enabled,settings FROM links WHERE user_id IN ('.$idSql.') ORDER BY link_id'),'blocks'=>fcc_partner_rows('SELECT biolink_block_id,user_id,link_id,is_enabled,settings FROM biolinks_blocks WHERE user_id IN ('.$idSql.') ORDER BY biolink_block_id')];
 if(file_put_contents('mobile-billing-data-before.json',json_encode($before,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT))===false)throw new RuntimeException('Data backup failed');
 $rollout=fcc_partner_rows('SELECT * FROM fcc_partner_rollout_access ORDER BY user_id');
 $paymentCount=(int)fcc_partner_one('SELECT COUNT(*) n FROM payments')['n'];
 database()->begin_transaction();$transaction=true;$cleared=[];$expired=[];
 foreach($candidates as $old){
  $uid=(int)$old['user_id'];$u=fcc_partner_one('SELECT * FROM users WHERE user_id=? FOR UPDATE',[$uid]);
  foreach(['plan_id','plan_expiration_date','payment_processor','payment_subscription_id'] as $field)if((string)$u[$field]!== (string)$old[$field])throw new RuntimeException('Billing changed for reviewed account');
  if(isset($active[$uid]))throw new RuntimeException('Live Stripe entitlement on retired candidate');
  // Historical payments are retained; recurring control fields now describe Stripe only.
  $extra=json_decode($u['extra']?:'{}',true)?:[];$extra['retired_paypal_subscription']=['id'=>$u['payment_subscription_id'],'retired_at'=>gmdate('c'),'reason'=>'Processor retired; paid period retained'];
  fcc_partner_query("UPDATE users SET payment_subscription_id='',extra=? WHERE user_id=?",[json_encode($extra,JSON_THROW_ON_ERROR),$uid]);$cleared[]=$uid;
  if($u['plan_id']==='5'&&strtotime($u['plan_expiration_date'])<time()){
   $u['payment_subscription_id']='';$u['extra']=json_encode($extra);(new \Altum\Models\User())->process_user_plan_expiration_by_user((object)$u);$expired[]=$uid;
   if((string)fcc_partner_one('SELECT plan_id FROM users WHERE user_id=?',[$uid])['plan_id']!=='2')throw new RuntimeException('Expiry not applied');
  }
  cache()->deleteItem('user?user_id='.$uid);cache()->deleteItemsByTag('user_id='.$uid);
 }
 if($expired!==[114])throw new RuntimeException('Unexpected expiry scope');
 foreach($before['links'] as $l){$now=fcc_partner_one('SELECT url FROM links WHERE link_id=?',[$l['link_id']]);if(!$now||$now['url']!==$l['url'])throw new RuntimeException('Referral URL changed');}
 if($paymentCount!==(int)fcc_partner_one('SELECT COUNT(*) n FROM payments')['n'])throw new RuntimeException('Payment history changed during release');
 if($rollout!==fcc_partner_rows('SELECT * FROM fcc_partner_rollout_access ORDER BY user_id'))throw new RuntimeException('Rollout changed');
 $after=fcc_partner_rows('SELECT '.$columns.' FROM users WHERE user_id IN ('.$idSql.') ORDER BY user_id');
 foreach($manifest as $p=>$h)if(hash_file('sha256',$root.$p)!==$h['after'])throw new RuntimeException('Published file differs');
 $result=['status'=>'DEPLOYED','at'=>gmdate('c'),'files'=>count($manifest),'retired_references'=>count($cleared),'expired_count'=>count($expired),'expired'=>$expired,'stripe_subscription_count'=>$seen,'after'=>$after,'payment_history_preserved'=>true,'referral_urls_preserved'=>true,'rollout_preserved'=>true];
 if(file_put_contents('mobile-billing-install.result',json_encode($result,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT))===false)throw new RuntimeException('Result unavailable');
 database()->commit();$transaction=false;$committed=true;rename('mobile-billing-install.result','mobile-billing-install.done');
}catch(Throwable $e){
 if($transaction)database()->rollback();
 if(!$committed&&$published){$b=new ZipArchive();if($b->open('mobile-billing-backup.zip')===true)foreach(array_reverse($published) as $p){$tmp=__DIR__.'/mobile-billing-rollback.tmp';file_put_contents($tmp,$b->getFromName($p));chmod($tmp,0644);rename($tmp,$root.$p);}}
 file_put_contents('mobile-billing-install.failed',json_encode(['status'=>'FAILED','phase'=>$phase,'message'=>$e->getMessage(),'committed'=>$committed]));
}
