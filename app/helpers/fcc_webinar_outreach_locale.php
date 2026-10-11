<?php
defined('ALTUMCODE') || die();
function fcc_wo_copy(): array {
 static $copy;
 $copy??=json_decode(file_get_contents(APP_PATH.'config/webinar-outreach-copy.json'),true,512,JSON_THROW_ON_ERROR);
 return $copy[fcc_locale()]??fcc_localize_copy($copy['hr'],fcc_locale());
}
function fcc_wo_t(string $key): string {return fcc_wo_copy()[$key]??$key;}
