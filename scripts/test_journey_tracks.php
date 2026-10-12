<?php
/* Offline checks for the Moj put tracks, the known step rule, gender neutral task copy and the
 * sponsor notices that are stored in Croatian and shown in the reader's language.
 * No database or network access. */

if(PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// All twelve interface catalogues are loaded in one process.
ini_set('memory_limit', '1024M');

defined('ALTUMCODE') || define('ALTUMCODE', 66);
defined('APP_PATH') || define('APP_PATH', dirname(__DIR__) . '/app/');

require APP_PATH . 'helpers/fcc_i18n.php';
require APP_PATH . 'helpers/fcc_journey_review.php';
require APP_PATH . 'helpers/fcc_journey_tracks.php';

$assert = static function(bool $condition, string $message): void {
    if(!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
};

/* Every track keeps all 90 tasks exactly once and starts with the shared first part. */
$assert(count(FCC_MP_COMMON) === 15 && count(array_unique(FCC_MP_COMMON)) === 15, 'shared part has 15 distinct tasks');
$assert(count(fcc_mp_tracks()) === 3, 'three tracks');
$assert(fcc_mp_order(null) === FCC_MP_COMMON, 'without a track only the shared part is ordered');
foreach(array_keys(fcc_mp_tracks()) as $track) {
    $order = fcc_mp_order($track);
    $label = $track;
    $assert(count($order) === 90 && count(array_unique($order)) === 90, $label . ': 90 distinct tasks');
    $assert(min($order) === 1 && max($order) === 90, $label . ': days 1 to 90');
    $assert(array_slice($order, 0, 15) === FCC_MP_COMMON, $label . ': shared part first');
}
$assert(!fcc_mp_track_valid('unknown') && fcc_mp_track_valid('team'), 'track validation');

/* Only shared tasks inside the shared part may be confirmed as already known. */
$assert(fcc_mp_known_allowed(5, 9), 'shared task inside the shared part');
$assert(!fcc_mp_known_allowed(16, 3), 'track task is never known');
$assert(!fcc_mp_known_allowed(5, 16), 'nothing is known after the shared part');

/* Reviewed task copy addresses members without masculine forms. */
$catalog = fcc_journey_review_catalog(json_decode((string) file_get_contents(APP_PATH . 'config/curriculum/fcc90.hr.v8.json'), true, 512, JSON_THROW_ON_ERROR));
$assert(count($catalog['tasks']) === 90, 'catalogue has 90 tasks');
$texts = [];
$walk = static function($value, string $key) use (&$walk, &$texts): void {
    if($key === 'previous_example') return;
    if(is_array($value)) { foreach($value as $k => $v) $walk($v, (string) $k); return; }
    if(is_string($value)) $texts[] = $value;
};
foreach($catalog['tasks'] as $task) $walk($task, '');
$masculine = '/\b(?:si|sam|nisi|jesi li)\s+(?:se\s+)?\w+(?:ao|io|eo|uo)\b|\b\w+(?:ao|io|eo|uo)(?:\/la)?\s+(?:si|sam)\b|\w\/la\b/u';
foreach($texts as $text) $assert(!preg_match($masculine, $text, $match), 'neutral task copy: ' . ($match[0] ?? '') . ' in ' . mb_substr($text, 0, 80));
$all = implode("\n", $texts);
foreach(fcc_journey_review()['neutral'] as $old => $new) $assert(str_contains($all, $new), 'neutral phrase applied: ' . mb_substr($new, 0, 60));

/* Sponsor notices: Croatian in storage, the reader's language on display. */
[$title, $body] = fcc_mp_notice_copy('help', ['name' => 'Ana Horvat', 'step' => 'Pripremi svoje Forever poslovanje']);
$assert($title === 'Ana Horvat treba pomoć s korakom' && str_starts_with($body, 'Korak: Pripremi svoje Forever poslovanje.'), 'Croatian help notice');
$shown = fcc_mp_notice_display(['event_key' => 'mp:stuck:1:2:20261012', 'title' => $title, 'body' => $body], 'en');
$assert($shown['title'] === 'Ana Horvat needs help with a step' && str_starts_with($shown['body'], 'Step: Get your Forever business ready.'), 'English help notice');
[$title, $body] = fcc_mp_notice_copy('milestone', ['name' => 'Ana: Horvat', 'milestone' => fcc_mp_milestone_label('steps:30', 'hr')]);
$shown = fcc_mp_notice_display(['event_key' => 'mp:milestone:1:steps:30', 'title' => $title, 'body' => $body], 'sl');
$assert($shown['title'] === 'Mejnik v tvoji ekipi' && $shown['body'] === 'Ana: Horvat: 30 korakov Moje poti. Oglasi se in čestitaj.', 'Slovenian milestone notice keeps a name with a colon');
[$title, $body] = fcc_mp_notice_copy('weekly', ['moved' => 4, 'waiting' => 2, 'help' => 1]);
$shown = fcc_mp_notice_display(['event_key' => 'mp:weekly:2026-W42', 'title' => $title, 'body' => $body], 'de');
$assert($shown['body'] === 'Vorangekommen: 4. Pausiert oder noch nicht gestartet: 2. Bitten um Hilfe: 1.', 'German weekly notice');
$stored = ['event_key' => 'mp:start:1', 'title' => 'Novi član tima počinje Moj put', 'body' => 'Ana ima svoj prvi korak Mojeg puta. Pošalji dobrodošlicu i dogovorite kratki razgovor.'];
$assert(fcc_mp_notice_display($stored, 'hr') === $stored, 'Croatian notice is shown as stored');
$assert(fcc_mp_notice_display(['event_key' => 'mp:start:1', 'title' => 'Stari naslov', 'body' => 'Stari tekst'], 'en')['body'] === 'Stari tekst', 'unknown text stays as stored');

/* Every new interface string has a translation in all supported languages. */
$sources = ['Odaberi stazu koja odgovara onome što želiš. Svih 90 koraka ostaje isto, mijenja se redoslijed. Stazu možeš promijeniti kad god želiš, a napredak ostaje sačuvan.', 'Zapeo/la sam, javi mojem sponzoru', 'Tvoja staza je spremljena. Sljedeći korak je spreman.', 'Napredak tvojih suradnika', '{name} treba pomoć s korakom'];
foreach(['en', 'de', 'es', 'fr', 'sl', 'sr', 'cnr', 'sq', 'hu', 'ro', 'pl', 'sv'] as $locale) {
    foreach($sources as $source) $assert(fcc_t($source, [], $locale) !== $source, $locale . ' translation: ' . mb_substr($source, 0, 40));
}

/* Sponsor view: each group shows its first ten members, the rest opens on request. */
$team = (string) file_get_contents(dirname(__DIR__) . '/themes/altum/views/partner/journey-team.php');
$assert(str_contains($team, '$mpVisible = 10;') && str_contains($team, "[array_slice(\$mpOverview['groups'][\$mpKey], 0, \$mpVisible), array_slice(\$mpOverview['groups'][\$mpKey], \$mpVisible)]"), 'team groups split after ten members');
$assert(str_contains($team, "<details class=\"mp-team-more\"><summary><span class=\"mp-team-more-open\"><?= sprintf(\$mpText('Prikaži još %d'), count(\$mpMembers)) ?>") && substr_count($team, '<details') === substr_count($team, '</details>'), 'the rest of a group sits behind Prikaži još');
$assert(str_contains($team, "<span class=\"fp-count\"><?= count(\$mpOverview['groups'][\$mpKey]) ?></span>"), 'group count still shows the whole group');
foreach(['en', 'de', 'es', 'fr', 'sl', 'sq', 'hu', 'ro', 'pl', 'sv'] as $locale) $assert(fcc_t('Prikaži još %d', [], $locale) !== 'Prikaži još %d' && str_contains(fcc_t('Prikaži još %d', [], $locale), '%d'), $locale . ' translation: Prikaži još %d');

echo 'journey tracks: ok' . PHP_EOL;
