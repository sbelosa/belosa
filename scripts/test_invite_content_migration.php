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

echo 'Invite and content migration checks passed.' . PHP_EOL;
