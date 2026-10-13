<?php
/* Actual language detection and continuity functions, synthetic text, no app bootstrap or API. */
declare(strict_types=1);
$source = $argv[1] ?? '/source';
function language_extract(string $source, string $name): string {
    $tokens = token_get_all($source); $count = count($tokens);
    for($i = 0; $i < $count; $i++) {
        if(!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $j = $i + 1;
        while($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) ++$j;
        if(!is_array($tokens[$j] ?? null) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $name) continue;
        $code = ''; $depth = 0; $opened = false;
        for($j = $i; $j < $count; $j++) {
            $token = $tokens[$j]; $code .= is_array($token) ? $token[1] : $token;
            if($token === '{') { ++$depth; $opened = true; }
            elseif($token === '}' && --$depth === 0 && $opened) return $code;
        }
    }
    throw new RuntimeException('Missing function: ' . $name);
}
$hashes = [];
foreach(['app/helpers/fcc_ai_product_advisor_v2.php' => ['fcc_ai_v2_normalize_text'], 'app/helpers/fcc_i18n.php' => ['fcc_locale'], 'app/helpers/fcc_coach_continuity.php' => ['fcc_coach_search_text', 'fcc_coach_requested_language', 'fcc_coach_reply_language_state']] as $path => $names) {
    $raw = file_get_contents($source . '/' . $path);
    foreach($names as $name) { $code = language_extract($raw, $name); $hashes[$name] = hash('sha256', $code); eval($code); }
}
$checks = [];
$assert = static function(bool $pass, string $name, string $expected, string $actual) use (&$checks): void { $checks[] = ['name' => $name, 'expected' => $expected, 'actual' => $actual, 'pass' => $pass]; };
$cases = [
    ['English request French', 'Please answer in French.', 'fr'],
    ['English write French', 'Please write in French.', 'fr'],
    ['Croatian request French preserved', 'Odgovaraj na francuskom.', 'fr'],
    ['German direct request', 'Antworte auf Deutsch.', 'de'],
    ['German polite request', 'Bitte antworte auf Deutsch.', 'de'],
    ['English request German preserved', 'Please answer in German.', 'de'],
    ['Croatian request German preserved', 'Odgovaraj na njemačkom.', 'de'],
    ['English request preserved', 'Speak English.', 'en'],
    ['Translate recipient copy does not switch', 'Translate this message into French.', ''],
    ['Croatian translation does not switch', 'Prevedi ovu poruku na njemački.', ''],
    ['Negated English request does not switch', 'Do not answer in French.', ''],
    ['Negated German request does not switch', 'Antworte bitte nicht auf Deutsch.', ''],
    ['Quoted customer French request does not switch', 'My customer says: "Please answer in French."', ''],
    ['Quoted customer German request does not switch', 'The customer says: "Antworte auf Deutsch."', ''],
    ['Ordinary product text does not switch', 'Tell me about Forever Aloe Vera Gel.', ''],
];
foreach($cases as [$name, $message, $expected]) { $actual = fcc_coach_requested_language($message); $assert($actual === $expected, $name, $expected, $actual); }
$stateCases = [
    ['Default UI language', 'Synthetic question', 'hr', [], [], 'hr'],
    ['Established conversation preference retained', 'Synthetic question', 'hr', ['coach_reply_language' => 'fr', 'coach_ui_language' => 'hr'], [], 'fr'],
    ['Deliberate UI language switch wins', 'Synthetic question', 'de', ['coach_reply_language' => 'fr', 'coach_ui_language' => 'hr'], [], 'de'],
    ['Recover direct French from same workspace user history', 'Synthetic question', 'hr', [], [['role' => 'user', 'content' => 'Please answer in French.'], ['role' => 'assistant', 'content' => 'Antworte auf Deutsch.']], 'fr'],
    ['Direct German overrides existing preference', 'Antworte auf Deutsch.', 'hr', ['coach_reply_language' => 'fr', 'coach_ui_language' => 'hr'], [], 'de'],
    ['Translation keeps existing conversation preference', 'Translate this message into French.', 'hr', ['coach_reply_language' => 'hr', 'coach_ui_language' => 'hr'], [], 'hr'],
    ['Quoted customer request keeps established preference', 'My customer says: "Please answer in French."', 'hr', ['coach_reply_language' => 'hr', 'coach_ui_language' => 'hr'], [], 'hr'],
];
foreach($stateCases as [$name, $message, $ui, $meta, $history, $expected]) {
    $state = fcc_coach_reply_language_state($message, $ui, $meta, $history);
    $actual = $state['coach_reply_language']; $assert($actual === $expected, $name, $expected, $actual);
}
$failed = count(array_filter($checks, static fn(array $check): bool => !$check['pass']));
echo json_encode(['function_sha256' => $hashes, 'tests' => count($checks), 'failures' => $failed, 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
exit($failed === 0 ? 0 : 1);
