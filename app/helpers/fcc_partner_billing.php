<?php
defined('ALTUMCODE') || die();

/** Stop Stripe renewal; retain subscription identity and access until its recorded end. */
function fcc_partner_cancel_stripe_subscription(object $user, $client = null): array {
 $uid=(int)($user->user_id??0); $sid=(string)($user->payment_subscription_id??'');
 if($uid<1||($user->payment_processor??'')!=='stripe'||!preg_match('/^sub_[A-Za-z0-9_]+$/',$sid)) throw new InvalidArgumentException(fcc_t('Nema povezane Stripe pretplate za otkazivanje.'));
 if($client===null) {
  $key=(string)(settings()->stripe->secret_key??'');
  if($key===''||(getenv('FCC_LOCAL')==='1'&&!preg_match('/^[sr]k_test_/',$key))) throw new RuntimeException(fcc_t('Stripe testna veza nije podešena. Pretplata nije promijenjena.'));
  $client=new \Stripe\StripeClient(['api_key'=>$key,'stripe_version'=>'2023-10-16']);
 }
 $sub=$client->subscriptions->retrieve($sid,[]);
 if((string)$sub->id!==$sid) throw new RuntimeException(fcc_t('Pretplata nije potvrđena.'));
 $owner=(int)($sub->metadata->user_id??0);
 if($owner&&$owner!==$uid) throw new RuntimeException(fcc_t('Pretplata ne pripada ovom FCC računu.'));
 $extra=is_string($user->extra??null)?json_decode($user->extra):($user->extra??(object)[]);
 $customer=(string)($sub->customer??'');
 if(!empty($extra->stripe_customer_id)&&$customer!==(string)$extra->stripe_customer_id) throw new RuntimeException(fcc_t('Stripe kupac ne odgovara FCC računu.'));
 $end=(int)($sub->current_period_end??$sub->items->data[0]->current_period_end??0);
 if(!in_array((string)$sub->status,['canceled','incomplete_expired'],true)&&empty($sub->cancel_at_period_end)) {
  // Each new intent gets a key: a resumed subscription may be canceled again in this period.
  $sub=$client->subscriptions->update($sid,['cancel_at_period_end'=>true],['idempotency_key'=>'fcc-cancel-'.$uid.'-'.bin2hex(random_bytes(16))]);
  if(empty($sub->cancel_at_period_end)) throw new RuntimeException(fcc_t('Stripe još nije potvrdio otkazivanje. Pokušaj ponovno.'));
 }
 return ['subscription_id'=>$sid,'cancel_at_period_end'=>(bool)($sub->cancel_at_period_end??false),'status'=>(string)$sub->status,'period_end'=>$end?gmdate('Y-m-d H:i:s',$end):null];
}
