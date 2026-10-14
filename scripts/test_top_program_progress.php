<?php
/* Custom code: FC-2026-10-14: Weekly results and total progress retain their separate meanings. */
if(PHP_SAPI!=='cli')exit(1);
define('ALTUMCODE',true);
define('FCC_JP_VERSION','fcc90-2026-09-29-v8');define('FCC_JP_V7_VERSION','fcc90-2026-09-27-v7');define('FCC_JP_PREVIOUS_VERSION','fcc90-2026-09-25-v6');define('FCC_JP_LEGACY_VERSION','fcc90-2026-09-24-v5-pilot15');define('FCC_JP_TOTAL',90);
function fcc_partner_one($sql,$p){if(!str_contains($sql,'SELECT id,catalog_version')||$p!==[922])throw new RuntimeException('Unexpected query or owner.');return $GLOBALS['cycle'];}
function fcc_j90_rows($uid,$cycle){if($uid!==922||$cycle!==123)throw new RuntimeException('Wrong programme owner.');return array_fill(0,$GLOBALS['done'],[]);}
require dirname(__DIR__).'/app/helpers/fcc_top.php';
$check=function($ok){if(!$ok)throw new RuntimeException('Progress regression.');};
$GLOBALS['cycle']=['id'=>123,'catalog_version'=>FCC_JP_V7_VERSION];$GLOBALS['done']=15;
$check(fcc_top_program_progress(922)===['completed'=>15,'total'=>90]);
$GLOBALS['done']=16;$check(fcc_top_program_progress(922)['completed']===16);
$GLOBALS['cycle']=null;$check(fcc_top_program_progress(922)===null);
$GLOBALS['cycle']=['id'=>123,'catalog_version'=>'old30'];$check(fcc_top_program_progress(922)===null);
$w=fcc_top_window(7,new DateTimeImmutable('2026-10-14 12:00',new DateTimeZone('Europe/Zagreb')));
$check($w['from']==='2026-10-07 22:00:00'&&$w['to']==='2026-10-14 10:00:00');
echo json_encode(['status'=>'PASS','checks'=>5,'database'=>false,'network'=>false]).PHP_EOL;
/* /Custom code: FC-2026-10-14 */
