<?php
/* Real feedback window and provenance functions, fluent in-memory query, synthetic content only. */
declare(strict_types=1);
$source = $argv[1] ?? '/source';
$requireAuditFields = in_array('--require-audit-fields', $argv, true);
function anchor_extract(string $source, string $name): string {
    $tokens = token_get_all($source); $count = count($tokens);
    for($i = 0; $i < $count; $i++) {
        if(!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $j = $i + 1;
        while($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) ++$j;
        if(!is_array($tokens[$j] ?? null) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $name) continue;
        $code = ''; $depth = 0; $opened = false;
        for($j = $i; $j < $count; $j++) {
            $token = $tokens[$j]; $text = is_array($token) ? $token[1] : $token;
            $code .= $text;
            if($token === '{') { ++$depth; $opened = true; }
            elseif($token === '}' && --$depth === 0 && $opened) return $code;
        }
    }
    throw new RuntimeException('Missing function: ' . $name);
}
final class AnchorQuery {
    private array $filters = []; private string $order = 'fcc_ai_message_id'; private string $direction = 'DESC';
    public function where(string $field, $value, string $operator = '='): self { $this->filters[] = [$field, $value, $operator]; return $this; }
    public function orderBy(string $field, string $direction): self { $this->order = $field; $this->direction = $direction; return $this; }
    public function get(string $table, int $limit, array $columns): array {
        if($table !== 'fcc_ai_messages') throw new RuntimeException('Unexpected table.');
        $GLOBALS['anchor_queries'][] = ['filters' => $this->filters, 'limit' => $limit, 'direction' => $this->direction];
        $rows = array_values(array_filter($GLOBALS['anchor_rows'], function(array $row): bool {
            foreach($this->filters as [$field, $value, $operator]) {
                $actual = $row[$field];
                if(!match($operator) { '=' => $actual == $value, '<=' => $actual <= $value, '>' => $actual > $value, default => throw new RuntimeException('Unexpected comparison.') }) return false;
            }
            return true;
        }));
        usort($rows, fn(array $a, array $b): int => ($a[$this->order] <=> $b[$this->order]) * ($this->direction === 'ASC' ? 1 : -1));
        return array_map(static fn(array $row): object => (object) array_intersect_key($row, array_flip($columns)), array_slice($rows, 0, $limit));
    }
}
function db(): AnchorQuery { return new AnchorQuery(); }
function fcc_ai_ensure_tables(): void { ++$GLOBALS['anchor_ensure_calls']; }
function fcc_ai_get_message_feedback_map(array $ids, array $actor): array {
    $GLOBALS['anchor_feedback_requests'][] = ['ids' => $ids, 'actor' => $actor];
    return in_array(110, $ids, true) ? [110 => ['viewer_feedback_type' => 'down', 'has_negative_signal' => true]] : [];
}
$raw = file_get_contents($source . '/app/helpers/fcc_ai.php');
$functionHashes = [];
foreach(['fcc_ai_get_conversation_messages', 'fcc_ai_get_feedback_thread_preview', 'fcc_ai_feedback_provenance'] as $name) {
    $function = anchor_extract($raw, $name);
    $functionHashes[$name] = hash('sha256', $function);
    eval($function);
}
$GLOBALS['anchor_rows'] = []; $GLOBALS['anchor_queries'] = []; $GLOBALS['anchor_feedback_requests'] = []; $GLOBALS['anchor_ensure_calls'] = 0;
for($id = 100; $id <= 120; $id++) {
    $meta = ['coach_prompt_version' => $id > 110 ? 'fixture-new-prompt' : 'fixture-rated-prompt', 'model' => $id > 110 ? 'gpt-6-sol' : 'gpt-6-luna', 'actual_model' => 'gpt-6-luna', 'requested_model' => 'gpt-6-sol', 'provider' => 'local_preview', 'reply_mode' => 'product_education', 'fallback_reason' => 'fixture-fallback', 'coach_decision' => ['reason' => 'fixture-decision'], 'reply_language' => 'fr', 'quality_repaired' => true, 'product_education' => ['version' => 'fixture-bridge-v1', 'recipient_message' => 'synthetic-sensitive-unused-copy'], 'private_token' => 'synthetic-sensitive-unused-token'];
    $GLOBALS['anchor_rows'][] = ['fcc_ai_message_id' => $id, 'fcc_ai_conversation_id' => 71, 'role' => $id % 2 ? 'user' : 'assistant', 'message_type' => 'chat', 'content' => ' Synthetic message ' . $id . ' ', 'datetime' => $id > 110 ? '2026-10-13 10:00:00' : '2026-10-07 10:00:00', 'meta' => json_encode($meta, JSON_THROW_ON_ERROR)];
}
for($id = 200; $id <= 212; $id++) $GLOBALS['anchor_rows'][] = ['fcc_ai_message_id' => $id, 'fcc_ai_conversation_id' => 72, 'role' => 'assistant', 'message_type' => 'chat', 'content' => ' Other synthetic conversation ', 'datetime' => '2026-10-13 10:00:00', 'meta' => '{}'];
$checks = [];
$assert = static function(bool $condition, string $name) use (&$checks): void { $checks[] = ['name' => $name, 'pass' => $condition]; };
$ids = static fn(array $rows): array => array_column($rows, 'message_id');
$latest = fcc_ai_get_conversation_messages(71, 3, ['actor_type' => 'owner', 'actor_identifier' => '71']);
$assert($ids($latest) === [118,119,120], 'Default existing recent history behavior stays chronological.');
$assert($GLOBALS['anchor_feedback_requests'][0]['actor'] === ['actor_type' => 'owner', 'actor_identifier' => '71'], 'Existing feedback actor is forwarded unchanged.');
$assert($latest[0]['content'] === 'Synthetic message 118', 'Actual normalizer trims content.');
$before = fcc_ai_get_conversation_messages(71, 6, [], 110);
$after = fcc_ai_get_conversation_messages(71, 2, [], 110, true);
$assert($ids($before) === [105,106,107,108,109,110], 'Before window includes rated turn and exactly five preceding messages.');
$assert($ids($after) === [111,112], 'After window selects immediate chronological successors.');
$preview = fcc_ai_get_feedback_thread_preview(71, 110);
$assert($ids($preview) === [105,106,107,108,109,110,111,112], 'Rated window includes both sides and excludes recent unrelated tail.');
$assert(count(array_filter($preview, static fn(array $row): bool => $row['is_feedback_anchor'])) === 1, 'Exactly one rated anchor is marked.');
$assert(array_values(array_filter($preview, static fn(array $row): bool => $row['is_feedback_anchor']))[0]['message_id'] === 110, 'Marker belongs to the rated message.');
$assert($preview[5]['feedback']['has_negative_signal'] === true, 'Rated message preserves feedback map.');
$provenance = fcc_ai_feedback_provenance($preview, 110);
$assert(($provenance['coach_prompt_version'] ?? '') === 'fixture-rated-prompt', 'Provenance uses rated historical prompt version, never a newer turn.');
$assert(($provenance['model'] ?? '') === 'gpt-6-luna' && ($provenance['requested_model'] ?? '') === 'gpt-6-sol', 'Provenance preserves actual and requested model evidence from rated metadata.');
$assert(($provenance['provider'] ?? '') === 'local_preview' && ($provenance['reply_mode'] ?? '') === 'product_education', 'Local education branch is distinguishable from model generation.');
$assert(!isset($provenance['private_token']), 'Provenance excludes unapproved private metadata.');
$assert(!str_contains(json_encode($provenance), 'synthetic-sensitive-unused'), 'Provenance excludes message copy and synthetic secret content.');
$assert(fcc_ai_feedback_provenance($preview, 999) === [], 'Missing rated row never borrows latest provenance.');
$queriesBefore = count($GLOBALS['anchor_queries']);
$assert(fcc_ai_get_feedback_thread_preview(0, 110) === [] && fcc_ai_get_feedback_thread_preview(71, 0) === [], 'Invalid anchors are empty.');
$assert(count($GLOBALS['anchor_queries']) === $queriesBefore, 'Invalid anchors never reach query boundary.');
$queriesBefore = count($GLOBALS['anchor_queries']);
$assert(fcc_ai_get_feedback_thread_preview(71, 210) === [], 'Anchor from another conversation is rejected.');
$assert(count($GLOBALS['anchor_queries']) === $queriesBefore + 1, 'Missing conversation anchor does not query followup rows.');
$assert(fcc_ai_get_feedback_thread_preview(999, 110) === [], 'Missing conversation has no preview.');
fcc_ai_get_conversation_messages(71, 999);
$assert(end($GLOBALS['anchor_queries'])['limit'] === 80, 'Existing maximum history limit is retained.');
$assert($ids(fcc_ai_get_conversation_messages(71, 0)) === [120], 'Minimum history limit is retained.');
foreach($GLOBALS['anchor_queries'] as $query) $assert(in_array(['fcc_ai_conversation_id', 71, '='], $query['filters'], true) || in_array(['fcc_ai_conversation_id', 999, '='], $query['filters'], true), 'Each query remains explicitly conversation scoped.');
$gaps = [];
$educationVersion = $provenance['education_version'] ?? $provenance['product_education_version'] ?? $provenance['product_education']['version'] ?? null;
foreach(['product_education_version' => $educationVersion === 'fixture-bridge-v1', 'reply_language' => ($provenance['reply_language'] ?? '') === 'fr', 'quality_repaired' => ($provenance['quality_repaired'] ?? null) === true] as $field => $present) {
    if(!$present) $gaps[] = $field;
    if($requireAuditFields) $assert($present, 'Compact audit provenance includes ' . $field . '.');
}
$failures = count(array_filter($checks, static fn(array $check): bool => !$check['pass']));
echo json_encode(['source_helper_sha256' => hash('sha256', $raw), 'function_sha256' => $functionHashes, 'require_audit_fields' => $requireAuditFields, 'tests' => count($checks), 'failures' => $failures, 'compact_provenance_gaps' => $gaps, 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
exit($failures === 0 ? 0 : 1);
