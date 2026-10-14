<?php
/* Custom code: FC-2026-10-14: Meaningful standalone safety and fixed-layout PDF checks. */
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('ALTUMCODE',1);
$root=dirname(__DIR__);
define('ROOT_PATH',$root); $autoload=getenv('FCC_PDF_VENDOR_AUTOLOAD') ?: $root.'/vendor/autoload.php';
if(is_readable($autoload)) require $autoload;
require $root.'/app/helpers/fcc_sponsoring_pdf.php';
function check_pdf(bool $ok,string $message): void { if(!$ok) throw new RuntimeException($message); }
$sp=['locale'=>'hr','url'=>'https://forevercard.club/posao/test?lang=hr','sponsor'=>['name'=>'Čedomir Đorđević Šimunović','fbo'=>'000123456789','phone'=>'','email'=>'privateaccount@example.invalid','card_url'=>'https://forevercard.club/test','shop_url'=>'https://forevercard.club/shop/test'],'contact'=>['whatsapp_url'=>'https://wa.me/385991234567']];
foreach(['en','hr','sl','de','es','unknown'] as $locale) {
 $sp['locale']=$locale; $pdf=fcc_sponsoring_pdf($sp);
 check_pdf(str_starts_with($pdf,'%PDF-1.4'),$locale.' PDF header');
 check_pdf(str_ends_with($pdf,"%%EOF\n"),$locale.' PDF trailer');
 check_pdf(preg_match_all('~/Type /Page(?:\s|/)~',$pdf)===2,$locale.' exactly two pages');
 check_pdf(str_contains($pdf,'/ToUnicode') && str_contains($pdf,'/FontFile2'),$locale.' embedded Unicode fonts');
 check_pdf(str_contains($pdf,'/URI (https://forevercard.club/posao/test?lang=hr)'),$locale.' exact personal URL');
 check_pdf(str_contains($pdf,'/URI (https://wa.me/385991234567)'),$locale.' public sponsor contact');
 check_pdf(!str_contains($pdf,'privateaccount@example.invalid'),$locale.' private email excluded');
 check_pdf(!str_contains($pdf,'/JavaScript') && !str_contains($pdf,'/Launch'),$locale.' no executable actions');
 check_pdf(strlen($pdf)>30000 && strlen($pdf)<1200000,$locale.' bounded output size');
}
check_pdf(fcc_sponsoring_pdf_url('javascript:alert(1)')==='','Reject JavaScript URLs');
check_pdf(fcc_sponsoring_pdf_url('file:///etc/passwd')==='','Reject file URLs');
check_pdf(fcc_sponsoring_pdf_url('https://name:password@example.com')==='','Reject userinfo URLs');
check_pdf(fcc_sponsoring_pdf_url("https://example.com/\nHeader")==='','Reject controls');
$sp['sponsor']['name']=str_repeat('Čačić Župančič Đorđević Müller ',5); $sp['sponsor']['avatar_path']='/etc/passwd';
check_pdf(str_starts_with(fcc_sponsoring_pdf($sp),'%PDF-1.4'),'Long Unicode name and untrusted avatar path');
if(($avatar=getenv('FCC_PDF_AVATAR_TEST_PATH')) && function_exists('imagecreatefromstring')) {
 $document=new FccSponsoringPdfDocument(); $document->page();
 check_pdf($document->avatar(10,10,50,$avatar),'Validated local avatar is embedded');
 check_pdf(!$document->avatar(10,10,50,'/etc/passwd'),'Arbitrary avatar path refused');
}
$noWhatsapp=$sp; $noWhatsapp['contact']['whatsapp_url']='';
check_pdf(str_contains(fcc_sponsoring_pdf($noWhatsapp),'/URI (https://forevercard.club/posao/test?lang=hr#razgovor)'),'Missing public phone falls back to the actual contact section');
$bad=$sp; $bad['url']='javascript:alert(1)'; $rejected=false;
try { fcc_sponsoring_pdf($bad); } catch(InvalidArgumentException $e) { $rejected=true; }
check_pdf($rejected,'Reject invalid canonical URL');
echo "PASS: 6 locales, two pages, embedded Unicode, exact links, safe actions, long name, missing public phone, avatar containment and URL validation.\n";
/* /Custom code: FC-2026-10-14 */
