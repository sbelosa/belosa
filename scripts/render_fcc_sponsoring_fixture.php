<?php
/* Custom code: FC-2026-10-14: deterministic public view fixture without database or runtime mutations. */
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$base = dirname(__DIR__);
$host = getenv('FCC_FIXTURE_HOST') ?: $base;
$root = dirname($host, 3);
if(!defined('ALTUMCODE')) define('ALTUMCODE', true);
if(!defined('ASSETS_FULL_URL')) define('ASSETS_FULL_URL', 'file://'.$host.'/themes/altum/assets/');
if(!defined('SITE_URL')) define('SITE_URL', getenv('FCC_FIXTURE_SITE') ?: 'https://forevercard.club/');
if(!function_exists('fcc_partner_h')) { function fcc_partner_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); } }
require_once $base.'/app/helpers/fcc_sponsoring_copy.php';
$locale = $argv[1] ?? 'hr';
$mode = $argv[2] ?? 'normal';
$assets = getenv('FCC_FIXTURE_ASSETS') ?: 'https://forevercard.club/uploads/blog/';
$name = $mode === 'long' ? 'ČĐŠŽĆ Aleksandra María Fernández Gruber' : 'Stjepan Beloša';
$sp = [
    'locale'=>$locale,'url'=>'https://forevercard.club/suradnja/pn8yuve6?lang='.$locale,
    'pdf_url'=>'https://forevercard.club/suradnja/pn8yuve6/pdf?lang='.$locale,'country'=>'hr',
    'countries'=>['hr'=>'Hrvatska','si'=>'Slovenija','de'=>'Deutschland','es'=>'España'],
    'sponsor'=>['name'=>$name,'avatar'=>$mode==='empty'?'':'file://'.$root.'/local/fcc-sponsoring-concept-20261014/assets/sponsor.png','headline'=>'Neovisni Forever Business Partner Za više informacija o Forever proizvodima ili poslovnoj suradnji slobodno me kontaktirajte.','phone'=>'385911561596','fbo'=>'TEST','card_url'=>'https://forevercard.club/ddglabhlcn','shop_url'=>'https://foreverliving.com/shop/hrv/hr-hr','initials'=>'SB'],
    'card'=>['products'=>$mode==='empty'?[]:[['name'=>'Forever Aloe Vera Gel','image'=>$assets.'866ad43b6e9730a2b2d277d57032af42.png','url'=>'https://forevercard.club/hr/blog/forever-aloe-vera-gel?ref=ddglabhlcn'],['name'=>'Aloe Ever Shield','image'=>$assets.'fd54132e699b0681b01135e90ea52d1c.png','url'=>'https://forevercard.club/hr/blog/aloe-ever-shield?ref=ddglabhlcn'],['name'=>'Forever Aloe Peaches','image'=>$assets.'8a327b2682fb13b81e355befe0182db5.png','url'=>'https://forevercard.club/hr/blog/forever-aloe-peaches?ref=ddglabhlcn']],'socials'=>[['name'=>'Instagram','url'=>'https://www.instagram.com/'],['name'=>'Facebook','url'=>'https://www.facebook.com/']]],
    'contact'=>['whatsapp_url'=>'https://wa.me/385911561596','form_action'=>'https://forevercard.club/suradnja/pn8yuve6?lang='.$locale,'nonce'=>'fixture','content_version'=>'fixture','request_key'=>'fixture'],
    'starter'=>null,'error'=>$mode==='error'?fcc_sponsoring_copy('form_expired',$locale):null,
    'result'=>$mode==='success'?['manage_url'=>'https://forevercard.club/suradnja/fixture/manage/test']:null,
];
include $base.'/themes/altum/views/partner/sponsoring-public.php';
/* /Custom code: FC-2026-10-14 */
