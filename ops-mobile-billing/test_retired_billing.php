<?php
/** Local transactional entitlement regression. No provider or mail transport is used. */
if(PHP_SAPI!=='cli'||getenv('FCC_LOCAL')!=='1')exit(1);
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
require dirname(__DIR__).'/app/init.php';
if(DATABASE_NAME!=='fcc_partner_local'||DATABASE_SERVER!=='db')throw new RuntimeException('Isolated database required');
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);});
\Altum\Cache::initialize();
$assert=function($ok,$message){if(!$ok)throw new RuntimeException($message);echo $message.PHP_EOL;};
$model=new \Altum\Models\User();
$originalStripe=settings()->stripe;
settings()->stripe=clone settings()->stripe;
settings()->stripe->is_enabled=false; // API fallback tests never leave this process.
database()->begin_transaction();
try {
 $u=db()->where('user_id',2)->getOne('users');
 $assert(str_ends_with($u->email,'@fcc.test'),'Synthetic fixture required');
 $plan=db()->where('plan_id',1)->getOne('plans');
 $setup=function($date,$processor='paypal',$subscription='I-retired',$extra='{}')use($plan){
  db()->where('user_id',2)->update('users',['plan_id'=>1,'plan_settings'=>$plan->settings,'plan_expiration_date'=>$date,'payment_processor'=>$processor,'payment_subscription_id'=>$subscription,'extra'=>$extra]);
  return db()->where('user_id',2)->getOne('users');
 };
 $future=date('Y-m-d H:i:s',time()+86400);
 $model->process_user_plan_expiration_by_user($setup($future));
 $assert((string)db()->where('user_id',2)->getValue('users','plan_id')==='1','Canceled PayPal renewal preserves remaining paid time');
 $expired=$setup('2020-01-01 00:00:00');
 $assert(!$model->has_expired_plan_downgrade_protection($expired),'Retired PayPal reference does not grant indefinite access');
 $model->process_user_plan_expiration_by_user($expired);
 $result=db()->where('user_id',2)->getOne('users');
 $assert((string)$result->plan_id==='2'&&$result->payment_subscription_id===''&&$result->payment_processor==='','Expired PayPal reverts to Beginner and clears recurring fields');
 $model->process_user_plan_expiration_by_user($result);
 $assert(db()->where('user_id',2)->getValue('users','plan_expiration_date')===$result->plan_expiration_date,'Second expiry check is idempotent');
 $u=$setup('2020-01-01 00:00:00','paypal','I-retired','{"billing_state":"past_due"}');
 $assert(!$model->has_expired_plan_downgrade_protection($u),'Unrelated old billing state cannot protect PayPal');
 foreach(['active'=>true,'trialing'=>true,'past_due'=>true,'canceled'=>false,'unpaid'=>false] as $status=>$expected){
  $u=$setup('2020-01-01 00:00:00','stripe','sub_test',json_encode(['stripe_customer_id'=>'cus_test','billing_stripe_status'=>$status]));
  $assert($model->has_expired_plan_downgrade_protection($u)===$expected,'Stripe fallback respects '.$status.' status');
 }
 $u=$setup($future,'','');$model->process_user_plan_expiration_by_user($u);
 $assert((string)db()->where('user_id',2)->getValue('users','plan_id')==='1','Valid manual grant stays active');
 $beforePayments=(int)db()->getValue('payments','count(*)');
 require APP_PATH.'controllers/WebhookPaypal.php';
 $paypal=settings()->paypal;settings()->paypal=clone $paypal;settings()->paypal->is_enabled=false;
 $license=settings()->license;settings()->license=clone $license;settings()->license->type='extended';
 $_SERVER['REQUEST_METHOD']='POST';
 (new \Altum\Controllers\WebhookPaypal())->index();
 $assert(http_response_code()===204&&(int)db()->getValue('payments','count(*)')===$beforePayments,'Disabled PayPal webhook cannot capture or record payments');
 settings()->paypal=$paypal;settings()->license=$license;
}finally{database()->rollback();settings()->stripe=$originalStripe;cache()->deleteItemsByTag('user_id=2');}
