<?php
// Pure unit checks: no application bootstrap, database, email or remote calls.
namespace Altum { class Language { public static $name='english'; } class Authentication { public static $user_id=7; } }
namespace {
if(PHP_SAPI!=='cli') exit(1);
define('ALTUMCODE',66);
require dirname(__DIR__).'/app/helpers/fcc_partner.php';
$checks=[];
$check=function($ok,$label)use(&$checks){if(!$ok)throw new \RuntimeException($label);$checks[]=$label;};
putenv('FCC_LOCAL=0');putenv('FCC_PARTNER_ENABLED=0');putenv('FCC_PARTNER_ROLLOUT=all');
$check(!fcc_partner_enabled(),'master switch disables all rollout modes');
putenv('FCC_PARTNER_ENABLED=1');putenv('FCC_PARTNER_ROLLOUT');putenv('FCC_PARTNER_PILOT_USER_IDS');
$check(!fcc_partner_enabled(),'global flag alone never enrolls production users');
$check(!fcc_partner_enabled(0),'guest has no implicit pilot membership');
putenv('FCC_PARTNER_ROLLOUT=pilot');putenv('FCC_PARTNER_PILOT_USER_IDS=7, 19, bad, -1, 0');
$check(fcc_partner_enabled(),'authenticated pilot uses canonical user ID');
$check(fcc_partner_enabled(19),'explicit pilot recipient is enabled');
$check(!fcc_partner_enabled(8),'another user stays on existing FCC');
$check(!fcc_partner_enabled(0),'signed in admin does not enroll a guest recipient');
$check(fcc_partner_install_email_content('Hrvatski',8)==='','pilot admin never sends app invite to a non-pilot recipient');
$check(fcc_partner_install_email_content()==='','unassigned registration email does not inherit sender membership');
\Altum\Authentication::$user_id=8;
$check(!fcc_partner_enabled(),'switching accounts immediately reevaluates membership');
putenv('FCC_PARTNER_ROLLOUT=typo');
$check(!fcc_partner_enabled(7),'unknown rollout mode fails closed');
putenv('FCC_PARTNER_ROLLOUT=all');
$check(fcc_partner_enabled(8)&&fcc_partner_enabled(0),'full rollout requires explicit all');
putenv('FCC_PARTNER_ROLLOUT=pilot');putenv('FCC_LOCAL=1');
$check(fcc_partner_enabled(8)&&fcc_partner_enabled(0),'isolated local fixture remains fully available');
putenv('FCC_PARTNER_ENABLED=0');
$check(!fcc_partner_enabled(7),'local still respects master kill switch');
echo json_encode(['status'=>'PASS','checks'=>$checks],JSON_PRETTY_PRINT).PHP_EOL;
}
