<?php
defined('ALTUMCODE') || die();

/** Entitlement belongs to this login, never to a shared Forever ID or its sponsor. */
function fcc_pro_access_for_user(array $user): bool {
    if((int)($user['status']??0)!==1)return false;
    $plan=is_string($user['plan_settings']??null)?json_decode($user['plan_settings'],true):(array)($user['plan_settings']??[]);
    $extra=is_string($user['extra']??null)?json_decode($user['extra'],true):(array)($user['extra']??[]);
    if(!in_array($plan['ai_growth_plan_is_enabled']??false,[true,1,'1'],true)||!empty($extra['billing_access_revoked_at']))return false;
    return fcc_ai_is_plan_expiration_active((string)($user['plan_expiration_date']??''))||fcc_ai_is_billing_grace_active((object)($extra??[]));
}
function fcc_pro_has_access(int $uid): bool {
    if($uid<=0)return false;
    // No process-wide cache: workers and open sessions must honour a changed package.
    return fcc_pro_access_for_user(fcc_partner_one('SELECT status,plan_settings,plan_expiration_date,extra FROM users WHERE user_id=?',[$uid])??[]);
}
function fcc_pro_features(): array {
    return [
        'team'=>['title'=>'Moj tim i struktura','description'=>'Prati suradnike u dubini, njihove CC bodove, napredak i prijave. Dogovorite zajednički rad uz timske preporuke Coacha.'],
        'coach'=>['title'=>'Osobni PRO Coach','description'=>'Pripremi poruku uz svoj zadatak, razradi sljedeći korak i napravi tjedni osvrt.'],
        'webinar'=>['title'=>'Video pozivnice i vlastiti webinari','description'=>'Izradi osobnu video pozivnicu, organiziraj vlastiti webinar i prati prijave.'],
        'marketing'=>['title'=>'Poslovne prezentacije','description'=>'Pripremi svoju prezentaciju, podijeli poziv i prati nove upite.'],
        'funnel'=>['title'=>'Funnel studio','description'=>'Složi prezentaciju s obrascem za upite i prati njezine rezultate.'],
    ];
}
function fcc_pro_upgrade_path(string $feature=''): string {
    return 'account-plan'.(isset(fcc_pro_features()[$feature])?'?feature='.$feature:'');
}
function fcc_pro_require(int $uid): void {
    if(!fcc_pro_has_access($uid))throw new InvalidArgumentException('Ova mogućnost dostupna je uz aktivni PRO paket.');
}
function fcc_pro_link(int $uid,string $feature,string $path): string {
    $locked=!fcc_pro_has_access($uid);
    return 'href="'.fcc_partner_h(url($locked?fcc_pro_upgrade_path($feature):$path)).'" data-fcc-pro-feature="'.fcc_partner_h($feature).'"'.($locked?' data-fcc-pro-locked="1"':'');
}
