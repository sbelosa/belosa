<?php
defined('ALTUMCODE') || die();

function fcc_seminar_t(string $text,array $replace=[],?string $locale=null): string {
 static $copy; $copy??=json_decode(file_get_contents(APP_PATH.'config/seminar-locales.json'),true);
 $value=$copy[$text][fcc_locale($locale)]??fcc_t($text,[],$locale);
 return $replace?strtr($value,$replace):$value;
}
function fcc_event_offline(array $s): bool {return fcc_event_definition($s)['mode']==='in_person';}
function fcc_event_asset(string $path): string {
 if($path==='' )return '';
 if(!preg_match('~^uploads/events/[a-zA-Z0-9/_-]+\.(?:jpg|jpeg|png|webp)$~D',$path))throw new InvalidArgumentException(fcc_seminar_t('Odaberi fotografiju iz galerije događaja.'));
 return $path;
}
function fcc_event_asset_url(string $path): string {return $path!==''?SITE_URL.fcc_event_asset($path):'';}
function fcc_event_ticket_url(string $value): string {
 $value=trim($value);if($value==='')return '';
 $parts=parse_url($value);
 if(strlen($value)>2000||!filter_var($value,FILTER_VALIDATE_URL)||($parts['scheme']??'')!=='https'||empty($parts['host'])||isset($parts['user'])||isset($parts['pass'])||preg_match('/[\x00-\x20\x7f\\\\]/',$value))throw new InvalidArgumentException(fcc_seminar_t('Za ulaznice upiši ispravnu HTTPS poveznicu.'));
 return $value;
}
function fcc_seminar_defaults(): array {return ['ticket_url'=>'','organizer'=>'','time_pending'=>0,'cover'=>'','gallery'=>[],'program'=>''];}
function fcc_seminar_validate(array $in): array {
 $d=fcc_seminar_defaults();$d['ticket_url']=fcc_event_ticket_url((string)($in['ticket_url']??''));
 $d['organizer']=fcc_partner_text($in['organizer']??'',160);$d['program']=fcc_partner_text($in['program']??'',6000);
 $d['time_pending']=($in['mode']??'')==='in_person'&&!empty($in['time_pending'])?1:0;
 $d['cover']=fcc_event_asset((string)($in['cover']??''));
 $gallery=(array)($in['gallery']??[]);if(count($gallery)>20)throw new InvalidArgumentException(fcc_seminar_t('Dodaj najviše 20 fotografija.'));
 $d['gallery']=array_values(array_unique(array_filter(array_map(fn($v)=>fcc_event_asset((string)$v),$gallery))));
 if(!in_array($d['cover'],$d['gallery'],true))$d['cover']=$d['gallery'][0]??'';
 return $d;
}
/** Files stay in a dedicated media directory. No remote downloads or executable formats. */
function fcc_seminar_upload(array $post,array $files): array {
 if(empty($files['event_photos']))return $post;
 $f=$files['event_photos'];$names=(array)$f['name'];$assets=(array)($post['gallery']??[]);
 foreach($names as $i=>$name){
  if((int)$f['error'][$i]===UPLOAD_ERR_NO_FILE)continue;
  if(count($assets)>=20||(int)$f['error'][$i]!==UPLOAD_ERR_OK||(int)$f['size'][$i]>8*1024*1024)throw new InvalidArgumentException(fcc_seminar_t('Fotografija nije učitana. Koristi JPG, PNG ili WebP do 8 MB.'));
  $tmp=$f['tmp_name'][$i];$info=@getimagesize($tmp);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
  if(!$info||!$ext||$info[0]*$info[1]>40000000||!is_uploaded_file($tmp))throw new InvalidArgumentException(fcc_seminar_t('Fotografija nije učitana. Koristi JPG, PNG ili WebP do 8 MB.'));
  $dir=dirname(__DIR__,2).'/uploads/events';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Event media directory unavailable');
  $path='uploads/events/'.bin2hex(random_bytes(16)).'.'.$ext;
  if(!move_uploaded_file($tmp,dirname(__DIR__,2).'/'.$path))throw new RuntimeException('Event upload failed');
  $assets[]=$path;
 }
 $post['gallery']=$assets;if(empty($post['cover'])&&$assets)$post['cover']=$assets[0];return $post;
}
function fcc_seminar_program(array $d): array {
 $rows=[];foreach(preg_split('/\R/u',$d['program']??'') as $line){$parts=array_map('trim',explode('|',$line,3));if($parts[0]!=='')$rows[]=['name'=>$parts[0],'role'=>$parts[1]??'','topic'=>$parts[2]??''];}return $rows;
}
function fcc_seminar_date(array $s,?string $locale=null): string {
 $d=fcc_event_definition($s);$date=(new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'));
 return $date->format('d. m. Y.').(!empty($d['time_pending'])?' · '.fcc_seminar_t('Satnica uskoro',[],$locale):' · '.$date->format('H:i').' · Europe/Zagreb');
}
function fcc_seminar_dates(array $s): array {
 $d=fcc_event_definition($s);$start=new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC'));$end=new DateTimeImmutable($s['ends_at_utc'],new DateTimeZone('UTC'));
 if(!empty($d['time_pending'])){$day=$start->setTimezone(new DateTimeZone('Europe/Zagreb'));return [$day->format('Ymd'),$day->modify('+1 day')->format('Ymd')];}
 return [$start->format('Ymd\THis\Z'),$end->format('Ymd\THis\Z')];
}
function fcc_seminar_google(array $s,string $detailsUrl): string {
 $d=fcc_event_definition($s);return 'https://calendar.google.com/calendar/render?'.http_build_query(['action'=>'TEMPLATE','text'=>$s['title'],'dates'=>implode('/',fcc_seminar_dates($s)),'ctz'=>'Europe/Zagreb','location'=>$d['location'],'details'=>fcc_seminar_t($d['description'])."\n".$detailsUrl."\n".$d['ticket_url']],'','&',PHP_QUERY_RFC3986);
}
function fcc_seminar_calendar(array $s,string $detailsUrl,bool $cancelled=false): string {
 $e=fn($v)=>str_replace(["\\","\r","\n",';',','],["\\\\",'',"\\n",'\\;','\\,'],(string)$v);$d=fcc_event_definition($s);[$start,$end]=fcc_seminar_dates($s);$type=!empty($d['time_pending'])?';VALUE=DATE':'';
 $lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//FCC//Events//EN','CALSCALE:GREGORIAN','METHOD:PUBLISH','BEGIN:VEVENT','UID:'.$s['calendar_uid'],'SEQUENCE:'.$s['revision'],'DTSTAMP:'.gmdate('Ymd\THis\Z'),'DTSTART'.$type.':'.$start,'DTEND'.$type.':'.$end,'SUMMARY:'.$e($s['title']),'DESCRIPTION:'.$e(fcc_seminar_t($d['description'])."\n".fcc_seminar_date($s)."\n".$detailsUrl."\n".$d['ticket_url']),'LOCATION:'.$e($d['location']),'URL:'.$detailsUrl,'STATUS:'.($cancelled||in_array($s['status'],['cancelled','postponed'],true)?'CANCELLED':'CONFIRMED'),'END:VEVENT','END:VCALENDAR'];
 $out=[];foreach($lines as $line){$prefix='';while(strlen($line)>75-strlen($prefix)){$chunk=mb_strcut($line,0,75-strlen($prefix),'UTF-8');$out[]=$prefix.$chunk;$line=substr($line,strlen($chunk));$prefix=' ';}$out[]=$prefix.$line;}return implode("\r\n",$out)."\r\n";
}
function fcc_seminar_message(array $s,string $name=''): string {
 $d=fcc_event_definition($s);$first=preg_split('/\s+/u',trim($name),2)[0]??'';
 return fcc_seminar_t('Bok {name}! Hvala na interesu za {event}.',['{name}'=>$first,'{event}'=>$s['title']])."\n\n".fcc_seminar_date($s)."\n".$d['location']."\n\n".fcc_seminar_t('Ulaznicu možeš kupiti ovdje:')."\n".$d['ticket_url']."\n\n".fcc_seminar_t('Trebaš li pomoć oko ulaznice ili dogovora za dolazak?');
}
function fcc_seminar_mail(array $r,array $s,string $kind,array $owner,string $locale): array {
 $t=fn($v)=>fcc_seminar_t($v,[],$locale);$d=fcc_event_definition($s);$date=fcc_seminar_date($s,$locale);
 if($kind==='owner_confirmation'){
  $contact=SITE_URL.$locale.'/partner/contacts?id='.(int)$r['contact_id'];
  return ['language'=>$locale,'subject'=>$t('Nova prijava interesa').': '.$s['title'],'text'=>$t('Nova prijava preko tvoje pozivnice.')."\n\n".$s['title']."\n".$date."\n\n".$r['name']."\n".$r['email']."\n".$r['phone']."\n\n".$t('Otvori kontakt').': '.$contact,"to"=>$owner['email'],'reply_to'=>null,'recipient_kind'=>'owner','contact_url'=>$contact,'is_local'=>fcc_webinar_local()];
 }
 $manage=fcc_webinar_manage_url($r);$verify=SITE_URL.'webinar-invite/verify/'.fcc_webinar_unseal($r['verification_sealed']);
 $heading=match($kind){'cancelled'=>'Događaj je otkazan.','postponed'=>'Novi termin uskoro','confirmation'=>'Hvala na prijavi interesa.',default=>'Novosti o tvojem događaju'};
 $text=$t($heading)."\n\n".$s['title']."\n".($s['status']==='postponed'?$t('Novi termin uskoro'):$date)."\n".$d['location']."\n\n";
 if($s['status']==='scheduled'&&$d['ticket_url']!=='')$text.=$t('Ulaznicu možeš kupiti ovdje:')."\n".$d['ticket_url']."\n".$t('Prijava interesa nije ulaznica. Kupnju završavaš u službenoj trgovini.')."\n\n";
 $text.=$t('Detalji i upravljanje prijavom').': '.$manage."\n".$t('Spremi u kalendar').': '.$manage."?download=calendar\n";
 if($r['reminder_opt_in']&&!$r['email_verified_at']&&$s['status']==='scheduled')$text.=$t('Za podsjetnike potvrdi email adresu:')."\n".$verify."\n";
 $text.="\n".$t('Tvoja kontakt osoba').': '.$owner['name']."\n".$owner['email']."\n".$owner['phone']."\n".$owner['profile_url'];
 return ['language'=>$locale,'subject'=>$t($heading),'text'=>$text,'to'=>$r['email'],'reply_to'=>$owner['email']?:null,'recipient_kind'=>'guest','manage_url'=>$manage,'verify_url'=>$verify,'is_local'=>fcc_webinar_local()];
}
