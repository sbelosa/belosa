<?php
/* Custom code: FC-2026-10-14: finance reducer evidence and calendar regression tests. */
define('ALTUMCODE', true);
require __DIR__.'/../app/helpers/fcc_admin_finance.php';
$assertions=0;
function finance_assert($actual,$expected,string $label): void {
    global $assertions;
    $assertions++;
    if($actual!==$expected) throw new RuntimeException($label.' expected '.json_encode($expected).' got '.json_encode($actual));
}
function finance_user(int $uid,array $extra=[],array $override=[]): array {
    return array_replace(['user_id'=>$uid,'type'=>0,'status'=>1,'name'=>'Account '.$uid,'email'=>'account'.$uid.'@example.test','plan_id'=>'5','plan_trial_days'=>30,'plan_settings'=>json_encode(['ai_growth_plan_is_enabled'=>true]),'plan_expiration_date'=>'2026-11-01 00:00:00','payment_processor'=>'stripe','payment_subscription_id'=>'sub_'.$uid,'payment_currency'=>'EUR','extra'=>json_encode($extra)],$override);
}
function finance_payment(int $id,int $uid,string $date,string $amount='9.95',array $override=[]): array {
    return array_replace(['id'=>$id,'user_id'=>$uid,'plan_id'=>'5','payment_id'=>'in_'.$id,'processor'=>'stripe','type'=>'recurring','frequency'=>'monthly','total_amount'=>$amount,'currency'=>'EUR','status'=>'paid','refunded_total'=>'0','refunds'=>'[]','datetime'=>$date],$override);
}
function finance_event(int $id,int $uid,string $type,string $date,array $override=[]): array {
    return ['id'=>$id,'user_id'=>$uid,'datetime'=>$date,'data'=>json_encode(array_replace(['event_type'=>$type,'occurred_at'=>$date,'processor'=>'stripe','stripe_event_id'=>'evt_'.$id,'stripe_invoice_id'=>'in_failed_'.$uid,'amount'=>9.95,'currency'=>'EUR','reason_code'=>'insufficient_funds'],$override))];
}
function finance_month(array $dashboard,string $month): array {
    foreach($dashboard['monthly'] as $row) if($row['month']===$month) return $row;
    throw new RuntimeException('Month missing '.$month);
}
$now=strtotime('2026-10-14 20:00:00 UTC');
finance_assert(fcc_admin_finance_money('0.105','EUR'),11,'Exact half up');
finance_assert(fcc_admin_finance_money('0.104','EUR'),10,'Exact below half');
finance_assert(fcc_admin_finance_money('-1.005','EUR'),-101,'Signed half up');
finance_assert(fcc_admin_finance_money('1.2345','KWD'),1235,'Three decimal currency');
finance_assert(fcc_admin_finance_money('9.5','JPY'),10,'Zero decimal currency');
finance_assert(fcc_admin_finance_money('1.23456','CLF'),12346,'Four decimal currency');
finance_assert(fcc_admin_finance_money('invalid','EUR'),null,'Invalid money');
finance_assert(fcc_admin_finance_money((string)PHP_INT_MAX,'EUR'),null,'Reject integer overflow');
$users=[
    finance_user(1,['billing_stripe_status'=>'active']),
    finance_user(2,['billing_stripe_status'=>'trialing','billing_trial_started_at'=>'2026-10-05 00:00:00']),
    finance_user(3,['billing_state'=>'past_due_critical','billing_stripe_status'=>'past_due','billing_grace_until'=>'2026-10-17 00:00:00','billing_last_failed_at'=>'2026-10-10 00:00:00','billing_last_failed_reason_code'=>'insufficient_funds'],['plan_expiration_date'=>'2026-10-01 00:00:00']),
    finance_user(4,[],['payment_subscription_id'=>'']),
    finance_user(5,['billing_stripe_status'=>'active','billing_cancel_at_period_end'=>true]),
    finance_user(6,['billing_state'=>'access_revoked','billing_access_revoked_at'=>'2026-10-11 00:00:00']),
    finance_user(7,['billing_stripe_status'=>'active'],['type'=>1]),
    finance_user(8,['billing_stripe_status'=>'active'],['status'=>0]),
    finance_user(9,[],['payment_processor'=>'paypal','payment_subscription_id'=>'I-9']),
    finance_user(10,[],['plan_id'=>'6']),
];
$payments=[
    finance_payment(1,1,'2026-10-01 00:00:00','9.95'),
    finance_payment(2,3,'2026-09-01 00:00:00','9.95'),
    finance_payment(3,9,'2026-10-01 00:00:00','19.90',['processor'=>'paypal','frequency'=>'annual']),
    finance_payment(4,4,'2026-09-01 00:00:00','10.00',['status'=>'refunded','refunded_total'=>'2.00','refunds'=>json_encode([['id'=>1,'amount'=>'2.00','datetime'=>'2026-10-05 00:00:00']])]),
    finance_payment(5,1,'2026-10-01 00:00:00','100',['currency'=>'USD']),
    finance_payment(6,2,'2026-10-01 00:00:00','88',['status'=>'pending']),
    finance_payment(7,2,'2026-10-01 00:00:00','88',['status'=>'cancelled']),
];
$events=[finance_event(1,3,'payment_failed','2026-10-05 00:00:00'),finance_event(2,3,'payment_failed','2026-10-10 00:00:00'),finance_event(3,3,'payment_recovered','2026-10-12 00:00:00')];
$events[]=$events[0];
$d=fcc_admin_finance_reduce($users,$payments,$events,['months'=>3],$now);
finance_assert($d['available'],true,'Available');
finance_assert($d['summary']['pro_access'],7,'Capability includes alternate plan and active grace');
finance_assert($d['summary']['renewing_paid'],2,'Stripe and independent processor renewing paid');
finance_assert($d['summary']['trial'],1,'Actual trial status only');
finance_assert($d['summary']['payment_risk'],1,'Current risk');
finance_assert($d['summary']['manual_granted'],1,'Manual access');
finance_assert($d['summary']['nonrenewing'],1,'Cancellation excluded renewal');
finance_assert($d['summary']['access_revoked'],1,'Revoked excluded access');
finance_assert(finance_month($d,'2026-09')['gross_minor'],1995,'Partial refund keeps original gross');
finance_assert(finance_month($d,'2026-09')['refunded_minor'],0,'Refund not assigned to original month');
finance_assert(finance_month($d,'2026-10')['gross_minor'],2985,'No pending cancelled or foreign currency');
finance_assert(finance_month($d,'2026-10')['refunded_minor'],200,'Refund uses own date');
finance_assert(finance_month($d,'2026-10')['net_minor'],2785,'Net revenue');
finance_assert(finance_month($d,'2026-10')['failed_count'],2,'Webhook deduplication');
finance_assert(finance_month($d,'2026-10')['failed_invoices'],1,'Invoice distinct from attempts');
finance_assert(finance_month($d,'2026-10')['affected_users'],1,'Affected accounts distinct');
finance_assert(finance_month($d,'2026-10')['recovered_count'],1,'Recovered events only');
finance_assert($d['risk_age']['days_0_7'],1,'Risk aging');
finance_assert($d['totals']['unique_payers'],4,'Distinct period payers');
$paid_trial_flag=fcc_admin_finance_reduce([finance_user(1,['billing_stripe_status'=>'active'],['plan_trial_done'=>1])],[finance_payment(1,1,'2026-10-01 00:00:00')],[],[],$now);
finance_assert($paid_trial_flag['summary']['renewing_paid'],1,'Used trial flag does not mean trial');
finance_assert($paid_trial_flag['summary']['trial'],0,'Paid is not current trial');
$usd=fcc_admin_finance_reduce($users,$payments,$events,['currency'=>'USD'],$now);
finance_assert($usd['totals']['gross_minor'],10000,'Foreign currency isolated');
finance_assert($usd['summary'],$d['summary'],'Global current counts independent of currency');
$fallback=fcc_admin_finance_reduce([], [finance_payment(1,1,'2026-10-01 00:00:00','10.00',['status'=>'refunded','refunded_total'=>'10.00','refunds'=>'[]'])],[],[],$now);
finance_assert($fallback['totals']['gross_minor'],1000,'Fully refunded gross retained');
finance_assert($fallback['totals']['net_minor'],0,'Fully refunded net zero');
finance_assert($fallback['warnings'][0]['code'],'refund_date_fallback','Missing refund date explained');
$oct_now=strtotime('2026-10-26 00:00:00 UTC');
$boundary=fcc_admin_finance_reduce([], [finance_payment(1,1,'2026-09-30 22:00:00'),finance_payment(2,1,'2026-09-30 21:59:59')],[],['months'=>3],$oct_now);
finance_assert(finance_month($boundary,'2026-10')['gross_minor'],995,'Zagreb October boundary uses UTC plus two');
finance_assert(finance_month($boundary,'2026-09')['gross_minor'],995,'Before Zagreb boundary remains September');
finance_assert($boundary['month_end'],'2026-10-31 23:00:00','Month end includes autumn DST');
$april=fcc_admin_finance_reduce([],[],[],['months'=>3],strtotime('2026-04-15 00:00:00 UTC'));
finance_assert($april['month_start'],'2026-01-31 23:00:00','Winter month start');
finance_assert($april['month_end'],'2026-04-30 22:00:00','Summer month end');
$due=fcc_admin_finance_reduce([finance_user(1,['billing_stripe_status'=>'active','billing_current_period_end'=>'2026-10-17 20:00:00']),finance_user(2,['billing_stripe_status'=>'trialing','billing_trial_started_at'=>'2026-10-05 00:00:00','billing_current_period_end'=>'2026-10-18 20:00:00'])],[finance_payment(1,1,'2026-10-01 00:00:00')],[],[],$now);
finance_assert($due['next_due']['days_7_count'],2,'Trial due count separate from amount evidence');
finance_assert($due['next_due']['days_7_minor'],995,'No invented trial price');
finance_assert($due['next_due']['unknown_amount_count'],1,'Trial unknown price explicit');
$many=[];
for($uid=1;$uid<=65;$uid++) $many[]=finance_user($uid,['billing_state'=>'past_due']);
$paged=fcc_admin_finance_reduce($many,[],[],['per_page'=>10,'page'=>2],$now);
finance_assert($paged['summary']['payment_risk'],65,'Global risk not truncated at fifty');
finance_assert(count($paged['accounts']),10,'Pagination');
finance_assert(count($paged['export_accounts']),65,'CSV includes complete filtered accounts');
finance_assert($paged['pagination']['total'],65,'Pagination total');
$filtered=fcc_admin_finance_reduce($users,$payments,$events,['state'=>'payment_risk','search'=>'Account 3'], $now);
finance_assert($filtered['pagination']['total'],1,'State and search filters');
finance_assert($filtered['summary'],$d['summary'],'List filters do not change global KPIs');
$refund_filter=fcc_admin_finance_reduce($users,$payments,$events,['selected_month'=>'2026-10','search'=>'Account 4'],$now);
finance_assert($refund_filter['pagination']['total'],1,'Refund month includes affected account');
$empty=fcc_admin_finance_reduce([],[],[],['currency'=>['EUR'],'months'=>['x'],'search'=>['x'],'state'=>['x']],$now);
finance_assert($empty['available'],true,'Array shaped GET values safe');
finance_assert($empty['totals']['gross_minor'],0,'Known empty source zero');
finance_assert($empty['filters']['currency'],'EUR','Invalid currency shape default');
$bad=fcc_admin_finance_reduce([finance_user(1,[],['extra'=>'{broken'])],[finance_payment(1,1,'2026-10-01 00:00:00','bad')],[['id'=>1,'data'=>'{broken']],[],$now);
finance_assert(in_array('invalid_payment',array_column($bad['warnings'],'code'),true),true,'Invalid monetary record has warning');
finance_assert(in_array('invalid_json',array_column($bad['warnings'],'code'),true),true,'Malformed JSON has warning');
foreach(['=HYPERLINK("evil")',' +SUM(A1:A2)','@command','-3','\t=evil'] as $cell) {
    $cell=str_replace('\\t',"\t",$cell);
    finance_assert(substr(fcc_admin_finance_csv_cell($cell),0,1),"'",'CSV formula injection escaped');
}
finance_assert(fcc_admin_finance_csv_cell('Normal name'),'Normal name','CSV plain cell preserved');
$failed=fcc_admin_finance_dashboard();
finance_assert($failed['available'],false,'Storage failure distinct from empty');
finance_assert(isset($failed['totals']),false,'Storage failure has no zero KPI');
if(true) {
    function fcc_partner_rows(string $sql,array $params=[]): array {
        global $finance_queries,$finance_storage_fail;
        if($finance_storage_fail) throw new RuntimeException('Simulated failure');
        $finance_queries[]=$sql;
        return [];
    }
}
$finance_queries=[];
$finance_storage_fail=false;
$wrapper=fcc_admin_finance_dashboard();
finance_assert($wrapper['available'],true,'Successful SELECT wrapper');
finance_assert(count($finance_queries),3,'Complete three source reads');
foreach($finance_queries as $sql) {
    finance_assert((bool)preg_match('/^SELECT /',$sql),true,'Read only SELECT');
    finance_assert(strpos(strtoupper($sql),' LIMIT ')===false,true,'No silent source truncation');
}
finance_assert(strpos($finance_queries[2],'datum_id AS id')!==false,true,'Native billing event primary key');
finance_assert(strpos($finance_queries[0],'plan_trial_done')!==false,true,'No card trial ambiguity available');
$finance_storage_fail=true;
finance_assert(fcc_admin_finance_dashboard()['available'],false,'Query exception unavailable');
$ambiguous=fcc_admin_finance_reduce([finance_user(1,[],['plan_trial_done'=>1,'payment_subscription_id'=>''])],[],[],[],$now);
finance_assert($ambiguous['summary']['trial'],0,'No invented no card trial');
finance_assert($ambiguous['warnings'][0]['code'],'unverified_trial','No card trial ambiguity explained');
$aged=fcc_admin_finance_reduce([finance_user(1,['billing_state'=>'past_due','billing_grace_started_at'=>'2026-09-01 00:00:00','billing_last_failed_at'=>'2026-10-13 00:00:00'])],[],[],[],$now);
finance_assert($aged['risk_age']['days_31_plus'],1,'Retry does not reset debt age');
$stale=fcc_admin_finance_reduce([finance_user(1,['billing_stripe_status'=>'active'])],[finance_payment(1,1,'2026-07-01 00:00:00')],[],[],$now);
finance_assert($stale['summary']['renewing_paid'],0,'Long stale historical payment is not current renewal evidence');
finance_assert($stale['summary']['unknown_subscription'],1,'Stale active subscription needs review');
$expired=fcc_admin_finance_reduce([finance_user(1,['billing_stripe_status'=>'active'],['plan_expiration_date'=>'2026-10-01 00:00:00'])],[finance_payment(1,1,'2026-10-01 00:00:00')],[],[],$now);
finance_assert($expired['summary']['renewing_paid'],0,'Expired entitlement not presented as renewing paid');
$history=fcc_admin_finance_reduce([],[],[],['months'=>48],$now);
finance_assert(count($history['monthly']),48,'Four year report supported');
finance_assert($d['first_billing_event_at'],'2026-10-05 00:00:00','First known billing evidence returned');
finance_assert(finance_month($d,'2026-09')['events_recorded'],false,'Before evidence is not a known failure zero');
finance_assert(finance_month($d,'2026-10')['events_recorded'],true,'Month has event coverage');
$marker_trial=fcc_admin_finance_reduce([finance_user(1,['billing_stripe_status'=>'active','billing_trial_started_at'=>'2026-10-01 00:00:00'])],[],[],[],$now);
finance_assert($marker_trial['summary']['trial'],1,'Native zero charge overwrites trialing but FCC marker proves trial');
$same_day_paid=fcc_admin_finance_reduce([finance_user(1,['billing_stripe_status'=>'active','billing_trial_started_at'=>'2026-10-01 00:00:10'])],[finance_payment(1,1,'2026-10-01 00:00:00')],[],[],$now);
finance_assert($same_day_paid['summary']['trial'],0,'First paid charge is within one hour marker tolerance');
$new_trial=fcc_admin_finance_reduce([finance_user(1,['billing_stripe_status'=>'active','billing_trial_started_at'=>'2026-10-01 00:00:00'])],[finance_payment(1,1,'2026-06-01 00:00:00')],[],[],$now);
finance_assert($new_trial['summary']['trial'],1,'Old payment before new trial marker is not payment during trial');
$cancelled_trial=fcc_admin_finance_reduce([finance_user(1,['billing_stripe_status'=>'active','billing_trial_started_at'=>'2026-10-01 00:00:00','billing_cancel_at_period_end'=>true])],[],[],[],$now);
finance_assert($cancelled_trial['summary']['trial'],1,'Cancelled current trial remains identified');
finance_assert($cancelled_trial['next_due']['days_30_count'],0,'Cancelled trial not forecast for charging');
$old_marker=fcc_admin_finance_reduce([finance_user(1,['billing_stripe_status'=>'active','billing_trial_started_at'=>'2026-05-01 00:00:00'])],[],[],[],$now);
finance_assert($old_marker['summary']['trial'],0,'Old marker is not ongoing trial');
$deleted_payer=fcc_admin_finance_reduce([], [finance_payment(1,0,'2026-10-01 00:00:00')],[],[],$now);
finance_assert($deleted_payer['totals']['gross_minor'],995,'Deleted account payment stays in revenue');
finance_assert($deleted_payer['totals']['unique_payers'],0,'Deleted account not a known unique payer');
finance_assert($deleted_payer['accounts'],[],'No account zero created');
echo 'Finance reducer checks passed: '.$assertions."\n";
/* /Custom code: FC-2026-10-14 */
