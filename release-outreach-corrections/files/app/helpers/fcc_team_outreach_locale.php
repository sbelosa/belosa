<?php
defined('ALTUMCODE') || die();
/** Isolated outreach copy so this release does not replace shared translation catalogs. */
function fcc_team_outreach_copy(?string $locale=null): array {
    static $all;
    $all ??= json_decode(file_get_contents(APP_PATH.'config/team-outreach-locales.json'),true,512,JSON_THROW_ON_ERROR);
    return $all[$locale??fcc_locale()]??$all['en'];
}
function fcc_team_outreach_t(string $key): string {return fcc_team_outreach_copy()[$key]??$key;}
