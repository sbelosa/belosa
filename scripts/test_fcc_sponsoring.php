<?php
namespace Altum {class Uploads {static function get_full_url($type){return 'https://forevercard.club/uploads/'.$type.'/';}static function get_full_path($type){return sys_get_temp_dir().'/fcc-sponsoring-tests/'.$type.'/';}}}
namespace {
define('ALTUMCODE',66);define('SITE_URL','https://forevercard.club/');define('APP_PATH',dirname(__DIR__).'/app/');
$checks=0;$mockUid=21;$mockInvite=true;$mockShare=true;
function ok($v,$label){global $checks;$checks++;if(!$v)throw new RuntimeException($label);}
function fcc_invite_code_valid($v){return is_string($v)&&preg_match('/^[abcdefghjkmnpqrstuvwxyz23456789]{8}$/D',$v);}
function fcc_business_countries(){return ['hr'=>1,'de'=>1];}
function fcc_business_enabled(){return true;}
function fcc_invite_ready(){return true;}
function fcc_partner_one($sql,$params=[]){global $mockInvite;if(str_contains($sql,'fcc_business_invites'))return $mockInvite?['user_id'=>21,'share_id'=>13]:null;if(str_contains($sql,'fcc_business_shares'))return ['public_token'=>str_repeat('a',64)];return null;}
function fcc_business_public($token){global $mockShare;return $mockShare?['available'=>true,'user_id'=>21,'link_id'=>81,'owner'=>['name'=>'ČĆĐ ŠŽ','email'=>'PRIVATE-LOGIN@example.test','fbo'=>'000123456789','profile'=>SITE_URL.'card-owner']]:null;}
function fcc_business_landing_public_allowed(){return true;}
function is_upload_file_available($type,$file){return $file==='public-avatar.png';}
function fc_normalize_whatsapp_phone($phone,$countries){return preg_replace('/[^0-9]/','',$phone);}
function fcc_market_referral_owner($ref){global $mockUid;return (object)['user_id'=>$mockUid];}
function fcc_forever_entry_url($ref,$kind,$block=null){return SITE_URL.'forever-shop?'.http_build_query($block?['block'=>$block]:['ref'=>$ref,'kind'=>$kind]);}
require APP_PATH.'helpers/fcc_sponsoring.php';
foreach(['en','hr','sl','de','es']as$l)ok(fcc_sponsoring_locale($l)===$l,'supported locale');
foreach([null,[],new \stdClass(),'sr','xx',str_repeat('a',1000)]as$l)ok(fcc_sponsoring_locale($l)==='en','fallback language');
ok(fcc_sponsoring_url('abcdefgh','hr','pdf','de')===SITE_URL.'suradnja/abcdefgh/pdf?lang=hr&country=de','language and country independent');
ok(fcc_sponsoring_url('../bad')==='','invalid code');ok(fcc_sponsoring_url('abcdefgh','en','erase')==='','invalid action');
foreach(['javascript:alert(1)','https://user:pass@forevercard.club/x','https://forevercard.club.evil.test/x',"https://forevercard.club/\npath",'//forevercard.club/path','data:image/png;base64,a','https://forevercard.club:8080/path']as$url)ok(fcc_sponsoring_safe_url($url,true)==='','unsafe URL');
ok(fcc_sponsoring_safe_url(SITE_URL.'uploads/avatars/a.png',true)!=='','same origin asset');ok(fcc_sponsoring_safe_url('https://example.test/card')!=='','public external card');
$card=['link_id'=>81,'user_id'=>21,'url'=>'card-owner','is_enabled'=>1,'settings'=>'{}'];
ok(fcc_sponsoring_visible($card),'visible card');
ok(fcc_sponsoring_visible(array_replace($card,['settings'=>'{"display_branding":1}'])),'public branding is not an audience restriction');
ok(fcc_sponsoring_text('<p>First</p><p>Second</p>')==='First Second','paragraph boundaries remain readable');
ok(!fcc_sponsoring_visible(array_replace($card,['settings'=>'{"password":"hidden"}'])),'password private');
ok(!fcc_sponsoring_visible(array_replace($card,['settings'=>'invalid'])),'malformed settings fail closed');
ok(!fcc_sponsoring_visible(array_replace($card,['is_enabled'=>0])),'disabled');
ok(!fcc_sponsoring_visible(array_replace($card,['settings'=>'{"schedule":true}','start_date'=>'2020-01-01','end_date'=>'2020-02-01']),time()),'expired');
ok(!fcc_sponsoring_visible(array_replace($card,['settings'=>'{"schedule":true}'])),'incomplete schedule');
ok(!fcc_sponsoring_visible(array_replace($card,['settings'=>'{"display_countries":["HR"]}'])),'targeted content hidden in universal preview');
ok(fcc_sponsoring_visible(array_replace($card,['settings'=>'{"display_countries":["all"]}'])),'all targeting okay');
function block($id,$type,$settings,$extra=[]){return array_replace(['biolink_block_id'=>$id,'link_id'=>81,'user_id'=>21,'type'=>$type,'is_enabled'=>1,'settings'=>json_encode($settings)],$extra);}
$blocks=[block(1,'heading',['text'=>'<strong>ČĆĐ ŠŽ</strong>']),block(2,'paragraph',['text'=>'<p>Public <em>description</em></p>']),block(3,'avatar',['image'=>'public-avatar.png']),block(4,'custom_html_whatsapp',['phone'=>'+385 99 1234567']),block(5,'link_forever_webshop_reg',[]),block(6,'socials',['socials'=>['instagram'=>'public-owner','email'=>'PRIVATE-LOGIN@example.test']]),block(7,'paragraph',['text'=>'PRIVATE-BLOCK'],['is_enabled'=>0]),block(8,'avatar',['image'=>'private.png'],['user_id'=>99])];
$share=fcc_business_public('');$p=fcc_sponsoring_profile($share,$card,$blocks);
ok($p['sponsor']['name']==='ČĆĐ ŠŽ','public heading/plain text');
$slogan=fcc_sponsoring_profile($share,$card,[block(1,'heading',['text'=>'Dobro došli u moje poslovanje'])]);ok($slogan['sponsor']['name']==='ČĆĐ ŠŽ','slogan never replaces sponsor identity');ok($p['sponsor']['initials']==='ČŠ','Unicode initials');ok($p['sponsor']['fbo']==='000123456789','leading zero ID');ok($p['sponsor']['avatar']===SITE_URL.'uploads/avatars/public-avatar.png','public upload only');ok($p['sponsor']['phone']==='385991234567','public phone');ok(str_contains($p['sponsor']['shop_url'],'block=5'),'precise owner shop block');ok($p['ref']==='card-owner','owner referral');
$fallback=$share;$fallback['owner']['phone']='385991112222';
$blank=[block(40,'custom_html_whatsapp',['phone'=>''])];
$legacy=fcc_sponsoring_profile($fallback,$card,$blank);ok($legacy['sponsor']['phone']==='','legacy blank block never reveals private fallback');
$v2=fcc_sponsoring_profile($fallback,array_replace($card,['settings'=>'{"fcc_card":"v2"}']),$blank);ok($v2['sponsor']['phone']==='385991112222','visible saved v2 block publishes existing phone fallback');
$hidden=fcc_sponsoring_profile($fallback,array_replace($card,['settings'=>'{"fcc_card":"v2"}']),[block(40,'custom_html_whatsapp',['phone'=>''],['is_enabled'=>0])]);ok($hidden['sponsor']['phone']==='','disabled v2 block never reveals fallback');
$json=json_encode($p);ok(!str_contains($json,'PRIVATE'),'no login email or disabled private data');ok(count($p['socials'])===1,'only whitelisted public social');ok(!array_key_exists('email',$p['sponsor']),'no account email field');
ok(fcc_sponsoring_profile($share,array_replace($card,['settings'=>'{"password":"x"}']),$blocks)===null,'protected card no projection');
ok(fcc_sponsoring_profile($share,array_replace($card,['user_id'=>99]),$blocks)===null,'owner boundary');
$bad=$share;$bad['owner']['fbo']='123';ok(fcc_sponsoring_profile($bad,$card,$blocks)===null,'invalid referral unavailable');
$mockUid=99;$p=fcc_sponsoring_profile($share,$card,array_filter($blocks,fn($b)=>$b['type']!=='link_forever_webshop_reg'));
ok($p['ref']===''&&$p['sponsor']['shop_url']==='','ambiguous slug never wrong sponsor');$mockUid=21;
ok(fcc_sponsoring_share('abcdefgh')!==null,'share read');$mockInvite=false;ok(fcc_sponsoring_share('abcdefgh')===null,'unknown invite');$mockInvite=true;$mockShare=false;ok(fcc_sponsoring_share('abcdefgh')===null,'revoked or disabled share');
ok(fcc_sponsoring_avatar_path('https://evil.test/a.jpg')==='','PDF cannot fetch arbitrary URL');ok(fcc_sponsoring_avatar_path(SITE_URL.'uploads/avatars/../../config.php')==='','PDF path traversal');
echo "FCC sponsoring helper PASS ($checks)\n";
}
