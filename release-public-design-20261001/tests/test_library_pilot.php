<?php
if(PHP_SAPI!=='cli'||getenv('FCC_LOCAL')!=='1') exit(1);
foreach(['DEBUG'=>0,'MYSQL_DEBUG'=>0,'LOGGING'=>1,'CACHE'=>0,'ALTUMCODE'=>66] as $k=>$v) defined($k)||define($k,$v);
$_SERVER['HTTP_HOST']='localhost';$_SERVER['REQUEST_URI']='/';$_SERVER['SERVER_PORT']='80';$_SERVER['HTTPS']='';
require dirname(__DIR__).'/app/init.php';
$checks=[];
$check=function($name,$ok)use(&$checks){if(!$ok)throw new RuntimeException($name);$checks[]=$name;};
$normalize=fn($text)=>preg_replace('/\s+/u','',html_entity_decode(strip_tags($text),ENT_QUOTES|ENT_HTML5,'UTF-8'));
foreach([518,519,15,605,89,529] as $id){
 $p=db()->where('blog_post_id',$id)->getOne('blog_posts');
 $sections=fcc_library_pilot_sections($p->content);
 $joined='';foreach($sections as $s)$joined.=($s['title']===fcc_lp_t('details')?'':$s['title']).$s['html'];
 $check("Every source word retained in source order for $id",$normalize($p->content)===$normalize($joined));
 $check("Useful article sections for $id",count($sections)>2);
 // Routing is tested separately against a snapshot, including non-product fallbacks.
 $check("No invented language for $id",in_array(fcc_library_pilot_locale(),['en','hr'],true));
}
$check('New library available locally',fcc_library_pilot_enabled());
$_GET['design']='classic';$check('Classic query cannot restore retired design',fcc_library_pilot_enabled());unset($_GET['design']);
putenv('FCC_LOCAL=0');$_GET['design']='pilot';$check('Production library available to anonymous visitors',fcc_library_pilot_enabled());putenv('FCC_LOCAL=1');unset($_GET['design']);
$c=fcc_library_pilot_config();$check('Final locale contract',$c['planned_locales']===['en','hr','sl','de','es']&&$c['default_locale']==='en');
$check('HR and EN UI keys complete',array_keys($c['copy']['hr'])===array_keys($c['copy']['en']));
$check('Non-pilot products remain untouched',fcc_library_pilot_product('aloe-vera-gel')===null);
echo json_encode(['status'=>'PASS','count'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
