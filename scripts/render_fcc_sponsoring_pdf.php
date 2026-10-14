<?php
/* Custom code: FC-2026-10-14: Reproducible public-data PDF specimens, no application or database bootstrap. */
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('ALTUMCODE',1);
$root=dirname(__DIR__);
define('ROOT_PATH',$root);
$autoload=getenv('FCC_PDF_VENDOR_AUTOLOAD') ?: $root.'/vendor/autoload.php';
if(is_readable($autoload)) require $autoload;
require $root.'/app/helpers/fcc_sponsoring_pdf.php';
$destination=$argv[1] ?? $root.'/tmp/pdfs/sponsoring';
if(!is_dir($destination) && !mkdir($destination,0755,true)) throw new RuntimeException('Cannot create specimen directory.');
$sp=[
 'locale'=>'hr','country'=>'HR','url'=>'https://forevercard.club/posao/primjer?lang=hr','pdf_url'=>'https://forevercard.club/posao/primjer/pdf?lang=hr',
 'sponsor'=>['name'=>'Marija Čačić Šimunović','headline'=>'Tu sam za tvoja pitanja i prve korake.','phone'=>'+385 99 123 4567','fbo'=>'000123456789','initials'=>'MČ','card_url'=>'https://forevercard.club/marija-primjer','shop_url'=>'https://forevercard.club/shop/primjer'],
 'card'=>['products'=>[],'socials'=>[['name'=>'Instagram','url'=>'https://www.instagram.com/example/'],['name'=>'Facebook','url'=>'https://www.facebook.com/example/']]], 'contact'=>['whatsapp_url'=>'https://wa.me/385991234567'], 'registration_url'=>'https://forevercard.club/poziv/primjer'
];
foreach(['en','hr','sl','de','es'] as $locale) {
 $sp['locale']=$locale; $sp['url']='https://forevercard.club/posao/primjer?lang='.$locale;
 file_put_contents($destination.'/sponsoring-'.$locale.'.pdf',fcc_sponsoring_pdf($sp));
}
$sp['sponsor']['name']='Čedomir Đorđević Šimunović Župančić María de los Ángeles Fernández Müller Łukasz Čačić';
$sp['sponsor']['phone']=''; $sp['locale']='hr'; $sp['url']='https://forevercard.club/posao/primjer?lang=hr';
file_put_contents($destination.'/sponsoring-long-name.pdf',fcc_sponsoring_pdf($sp));
echo "Generated five locale specimens and one long Unicode name specimen.\n";
/* /Custom code: FC-2026-10-14 */
