<?php
/* Offline checks for the Moj put content migration and the Pozivnica u tim helpers.
 * No database or network access. */

if(PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

defined('ALTUMCODE') || define('ALTUMCODE', 66);
defined('APP_PATH') || define('APP_PATH', dirname(__DIR__) . '/app/');

require APP_PATH . 'helpers/fcc_content_migration.php';
require APP_PATH . 'helpers/fcc_business_invite.php';

$assert = static function(bool $condition, string $message): void {
    if(!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
};

/* Content replacements touch whole text nodes only, longest phrase first. */
$html = '<div class="fcc-card-title">120-dnevni plan</div><p><a href="https://forevercard.club/blog/120-dnevni-plan">Pročitaj kako izgleda 120-dnevni plan</a></p>';
$out = fcc_cm_replace($html, 'Pročitaj kako izgleda 120-dnevni plan', 'Pročitaj kako izgleda program Moj put', 'content');
$out = fcc_cm_replace($out, '120-dnevni plan', 'Moj put: 90 koraka', 'content');
$assert(str_contains($out, '>Moj put: 90 koraka<') && str_contains($out, '>Pročitaj kako izgleda program Moj put<'), 'text node replacement');
$assert(str_contains($out, 'href="https://forevercard.club/blog/120-dnevni-plan"'), 'attributes are left for the link rule');

$out = fcc_cm_replace("<p>\n        AI pomaže pratiti 120-dnevni plan i korake\n        koje trebaš raditi.\n      </p>", 'AI pomaže pratiti 120-dnevni plan i korake koje trebaš raditi.', 'NOVO', 'content');
$assert(str_contains($out, 'NOVO') && !str_contains($out, '120'), 'stored whitespace may differ');

$assert(fcc_cm_replace('<p>Moj 120-dnevni plan je jasan</p>', '120-dnevni plan', 'X', 'content') === '<p>Moj 120-dnevni plan je jasan</p>', 'a phrase inside a longer sentence stays');

$assert(fcc_cm_replace('Forever Card Club, 120 dnevni plan, AI mentor', '120 dnevni plan', 'Moj put 90 koraka', 'keywords') === 'Forever Card Club, Moj put 90 koraka, AI mentor', 'keyword item replacement');
$assert(fcc_cm_replace('a, 120 dnevni plan dodatno, b', '120 dnevni plan', 'X', 'keywords') === 'a, 120 dnevni plan dodatno, b', 'only whole keyword items change');

/* The migration definition is complete and free of the retired plan. */
$definition = json_decode((string) file_get_contents(APP_PATH . 'config/fcc_content_mojput_2026_10_12.json'), true);
$assert(is_array($definition) && count($definition['article']['languages']) === 13, 'article copy for 13 languages');
foreach($definition['article']['languages'] as $language => $copy) {
    $text = $copy['title'] . ' ' . $copy['description'] . ' ' . $copy['keywords'] . ' ' . $copy['content'];
    $assert(!preg_match('/120[- ]?(dana|dnevn|day|Tage|días|jours|dagar|napos|zile|ditor|dni)/u', $text), 'no 120 day plan in ' . $language);
    $assert(str_contains($copy['content'], '[aff_biolink]') && str_contains($copy['content'], '[phone]') && !str_contains($copy['content'], '[telefon]'), 'contact placeholders in ' . $language);
    $assert(mb_strlen($copy['title']) <= 256 && mb_strlen($copy['description']) <= 256 && mb_strlen($copy['keywords']) <= 256, 'column lengths in ' . $language);
}
foreach($definition['replacements'] as $item) {
    $assert(in_array($item['column'], ['content', 'keywords'], true) && trim($item['old']) !== '' && trim($item['new']) !== '', 'replacement shape for ' . $item['url'] . ' ' . $item['language']);
}

/* Invitation codes avoid ambiguous characters. */
$assert(fcc_invite_code_valid('b28uubxh'), 'valid invitation code');
foreach(['B28UUBXH', 'b28uubx', 'b28uubx1', 'b28uubxo', ''] as $code) $assert(!fcc_invite_code_valid($code), 'invalid invitation code ' . $code);
$assert(!fcc_invite_code_valid(['b28uubxh']), 'invitation code must be a string');

/* Reviewed Start pack facts stay visible for 30 days at most. */
$rows = fcc_invite_starter_review_rows(strtotime('2026-10-20T00:00:00Z'));
$croatia = array_values(array_filter($rows, static fn($row) => $row['country'] === 'hr'))[0] ?? null;
$assert($croatia !== null && $croatia['days_left'] === 7, 'Croatian Start pack review window');

/* FC-2026-10-12: the official website notice goes, independence and the official shop stay. */
$notice = json_decode((string) file_get_contents(APP_PATH . 'config/fcc_content_notice_2026_10_12.json'), true);
$assert(is_array($notice) && count($notice['questions']) === 13 && count($notice['replace']) >= 50 && count($notice['remove']) >= 80, 'notice definition for 13 languages');
$sample = "<h2>Važna napomena</h2>\n<p>Ovu stranicu vodi nezavisni Forever poslovni partner. Stranica nije službena web\n stranica kompanije Forever Living Products. Kupnjom putem ove stranice bit ćete preusmjereni.</p>"
    . '<h3>Je li FCC službeni Forever alat?</h3> <p>Ne. FCC je neovisni digitalni sustav za Forever partnere i nije službena stranica ni službeni alat kompanije Forever Living Products.</p> <h2>Zaključak</h2>'
    . '<p>FCC ne zamjenjuje službeni Forever shop niti predstavlja službenu FLP stranicu. On je neovisni digitalni sustav.</p>'
    . '<p> Forever Card Club is not an official website or an official tool of Forever Living Products. It is an independent digital system used by partners.</p>'
    . '<p>FCC ne prodaje proizvode i ne zamjenjuje službeni Forever shop.</p><p>This is not an official Forever Living Products website.</p>';
$clean = fcc_cm_notice_clean($sample, $notice);
$assert(!preg_match('~nije službena|službeni Forever alat|not an official|službenu FLP stranicu~u', $clean), 'every notice form is removed');
$assert(str_contains($clean, 'partner. Kupnjom putem') && str_contains($clean, '<h2>Zaključak</h2>'), 'surrounding sentences and headings stay');
$assert(str_contains($clean, 'FCC ne zamjenjuje službeni Forever shop. On je') && str_contains($clean, 'FCC ne prodaje proizvode i ne zamjenjuje službeni Forever shop.'), 'facts about the official shop stay');
$assert(str_contains($clean, 'Forever Card Club is an independent digital system used by partners.'), 'the about page keeps a full sentence');
$assert(!str_contains($clean, '<p></p>') && fcc_cm_notice_clean($clean, $notice) === $clean, 'no empty paragraph and a second run changes nothing');

/* FC-2026-10-12: the Aloe switch only touches cards and blocks and leaves user blocks out. */
$assert(array_key_exists('aloe', fcc_cm_migrations()) && fcc_cm_definition('aloe')['key'] === 'card-aloe-2026-10-12', 'aloe migration is registered');
$assert(isset(fcc_cm_primary_keys()['links'], fcc_cm_primary_keys()['biolinks_blocks']), 'card tables have primary keys');
$migration = (string) file_get_contents(APP_PATH . 'helpers/fcc_content_migration.php');
$assert(str_contains($migration, "l.last_datetime IS NULL AND u.status=1 AND u.type=0") && str_contains($migration, "\$block['last_datetime'] !== null"), 'only cards nobody has edited');
$assert(str_contains($migration, "fcc_cm_aloe_after('revert'") && str_contains($migration, 'expires_at=LEAST(expires_at,UTC_TIMESTAMP())'), 'a revert withdraws the owner notice');
$assert(fcc_cm_touched_links([['table_name'=>'links', 'row_id'=>12, 'id'=>99]]) === [12], 'reverted log rows point to their card');

echo 'Invite and content migration checks passed.' . PHP_EOL;
