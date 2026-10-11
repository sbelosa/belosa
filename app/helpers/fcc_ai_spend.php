<?php
defined('ALTUMCODE') || die();

/** Gross USD estimates and soft monthly model budgets. No message content is read or stored. */
function fcc_ai_spend_defaults(): array {
    return ['coach' => 8.0, 'cards' => 10.0];
}

function fcc_ai_spend_rows(string $sql, array $params = []): array {
    $connection = db();
    $rows = $connection->rawQuery($sql, $params);
    if(method_exists($connection, 'getLastErrno') && $connection->getLastErrno()) {
        throw new RuntimeException('AI spend database query failed.');
    }
    if(!is_array($rows)) throw new RuntimeException('AI spend database response unavailable.');
    return array_map(static fn($row) => (array) $row, $rows);
}

/** Explicit linked accounts only. Matching Forever IDs do not establish an account link. */
function fcc_ai_spend_identity(int $userId): array {
    if($userId <= 0) throw new InvalidArgumentException('Invalid AI spend owner.');
    if(!function_exists('fcc_team_graph') && is_file(__DIR__ . '/fcc_team.php')) {
        require_once __DIR__ . '/fcc_team.php';
    }
    $graph = function_exists('fcc_team_graph') ? fcc_team_graph() : [];
    $owner = function_exists('fcc_team_primary') ? fcc_team_primary($userId, $graph) : $userId;
    $ids = function_exists('fcc_team_account_ids') ? fcc_team_account_ids($owner, $graph) : [$owner];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
    if($owner <= 0 || !$ids || !in_array($userId, $ids, true)) {
        throw new RuntimeException('AI spend account links unavailable.');
    }
    sort($ids);
    return ['owner_id' => $owner, 'account_ids' => $ids];
}

function fcc_ai_spend_channel(string $channel): string {
    if(!in_array($channel, ['coach', 'cards'], true)) throw new InvalidArgumentException('Invalid AI spend channel.');
    return $channel;
}

/** Calendar month and rolling local day windows, converted to UTC for storage queries. */
function fcc_ai_spend_windows(?DateTimeImmutable $now = null): array {
    $zone = new DateTimeZone('Europe/Zagreb');
    $utc = new DateTimeZone('UTC');
    $now = ($now ?? new DateTimeImmutable('now', $zone))->setTimezone($zone);
    $start = $now->modify('first day of this month')->setTime(0, 0);
    $reset = $start->modify('first day of next month');
    $windows = [];
    foreach([7, 30, 90] as $days) {
        $windows[$days] = ['start' => $now->modify('-' . $days . ' days')->setTimezone($utc)->format('Y-m-d H:i:s'), 'end' => $now->setTimezone($utc)->format('Y-m-d H:i:s')];
    }
    return ['periods' => $windows, 'month' => [
        'label' => $start->format('m/Y'), 'start_utc' => $start->setTimezone($utc)->format('Y-m-d H:i:s'),
        'end_utc' => $reset->setTimezone($utc)->format('Y-m-d H:i:s'), 'reset_at' => $reset->format(DATE_ATOM),
        'timezone' => 'Europe/Zagreb',
    ], 'now_utc' => $now->setTimezone($utc)->format('Y-m-d H:i:s')];
}

function fcc_ai_spend_array($value): array {
    if(is_string($value)) { $decoded = json_decode($value, true); return is_array($decoded) ? $decoded : []; }
    return is_array($value) ? $value : (is_object($value) ? (array) $value : []);
}

function fcc_ai_spend_count($value): ?int {
    if(is_int($value)) return $value >= 0 ? $value : null;
    if(is_string($value) && preg_match('/^\d+$/D', $value)) {
        $trimmed = ltrim($value, '0') ?: '0';
        $max = (string) PHP_INT_MAX;
        if(strlen($trimmed) < strlen($max) || strlen($trimmed) === strlen($max) && strcmp($trimmed, $max) <= 0) return (int) $trimmed;
    }
    return null;
}

/** Verified official Standard text rates, 2026 10 09. Cache writes replace ordinary input rates. */
function fcc_ai_spend_rates(string $model): ?array {
    $rates = [
        'gpt-6-sol' => [2.0, 0.20, 2.50, 10.0],
        'gpt-6-luna' => [0.10, 0.01, 0.125, 0.50],
        'gpt-5.4' => [2.50, 0.25, 2.50, 15.0],
        'gpt-5.4-mini' => [0.75, 0.075, 0.75, 4.50],
        'gpt-5.6-terra' => [2.0, 0.20, 2.50, 12.0],
        'gpt-4.1-mini' => [0.40, 0.10, 0.40, 1.60],
    ];
    foreach($rates as $name => $values) {
        if($model === $name || preg_match('/^' . preg_quote($name, '/') . '-\d{4}-\d{2}-\d{2}$/D', $model)) {
            return ['model' => $name, 'input' => $values[0], 'cached_input' => $values[1], 'cache_write' => $values[2], 'output' => $values[3]];
        }
    }
    return null;
}

/** Accept flat legacy rows, meta.usage, normalized provider_usage and raw provider usage. */
function fcc_ai_spend_estimate(array $result): array {
    $meta = fcc_ai_spend_array($result['meta'] ?? []);
    $usage = [];
    foreach([$result['provider_usage'] ?? null, $result['usage'] ?? null, $meta['provider_usage'] ?? null, $meta['usage'] ?? null] as $candidate) {
        $candidate = fcc_ai_spend_array($candidate);
        if($candidate) { $usage = $candidate; break; }
    }
    $values = array_replace($meta, $result, $usage);
    $inputDetails = fcc_ai_spend_array($values['input_tokens_details'] ?? $values['prompt_tokens_details'] ?? []);
    $outputDetails = fcc_ai_spend_array($values['output_tokens_details'] ?? $values['completion_tokens_details'] ?? []);
    $model = trim((string) ($usage['actual_model'] ?? $usage['model'] ?? $values['actual_model'] ?? $values['model'] ?? $values['requested_model'] ?? $values['request_model'] ?? ''));
    $input = fcc_ai_spend_count($values['input_tokens'] ?? $values['prompt_tokens'] ?? null);
    $output = fcc_ai_spend_count($values['output_tokens'] ?? $values['completion_tokens'] ?? null);
    $cachedRaw = $values['cached_input_tokens'] ?? $values['input_cached_tokens'] ?? $inputDetails['cached_tokens'] ?? null;
    $writeRaw = $values['cache_write_input_tokens'] ?? $values['input_cache_write_tokens'] ?? $values['cache_write_tokens'] ?? $inputDetails['cache_write_tokens'] ?? null;
    $cached = fcc_ai_spend_count($cachedRaw);
    $write = fcc_ai_spend_count($writeRaw);
    $reasoning = fcc_ai_spend_count($values['reasoning_tokens'] ?? $outputDetails['reasoning_tokens'] ?? null);
    $answer = [
        'model' => $model, 'pricing_model' => null, 'input_tokens' => $input, 'output_tokens' => $output,
        'cached_input_tokens' => $cached, 'cache_write_input_tokens' => $write, 'uncached_input_tokens' => null,
        'reasoning_tokens' => $reasoning, 'service_tier' => (string) ($values['service_tier'] ?? ''),
        'estimated_cost_usd' => null, 'estimated_cost_breakdown_usd' => null, 'cost_estimate_status' => 'unavailable',
        'cost_estimate_reason' => null, 'pricing_basis' => 'standard_gross_usd_2026_10_09', 'price_version' => 'standard_gross_usd_2026_10_09',
        'estimate_assumptions' => ['standard_gross_rates_before_account_credits'], 'partial' => true,
    ];
    $local = in_array($model, ['local_preview', 'local', 'fcc-public-grounded', 'fcc-vital5-package'], true) || (bool) preg_match('/^fcc-(?:local-preview|grounded)(?:-|$)/D', $model) || $model === '' && in_array(($values['provider'] ?? ''), ['local_preview', 'local'], true);
    if($local && ($input === null || $input === 0) && ($output === null || $output === 0) && !$usage) {
        return array_replace($answer, ['input_tokens' => 0, 'output_tokens' => 0, 'estimated_cost_usd' => 0.0, 'cost_estimate_status' => 'local_free', 'partial' => false]);
    }
    $rejectedBeforeUsage = in_array(($result['reason'] ?? ''), ['api_error', 'missing_api_key', 'invalid_model', 'invalid_request'], true)
        && empty($result['success']) && ($input === null || $input === 0) && ($output === null || $output === 0)
        && (!$usage || isset($usage['usage_reported']) && !$usage['usage_reported']);
    if($rejectedBeforeUsage) {
        return array_replace($answer, ['input_tokens' => 0, 'output_tokens' => 0, 'estimated_cost_usd' => 0.0, 'cost_estimate_status' => 'provider_rejected_without_usage', 'partial' => false]);
    }
    $legacyAssumption = false;
    $rates = fcc_ai_spend_rates($model);
    if(!$rates && $local && $input !== null && $output !== null && ($input > 0 || $output > 0)) {
        $requested = (string) ($values['requested_model'] ?? $values['request_model'] ?? '');
        $rates = fcc_ai_spend_rates($requested);
        if($rates) {
            $answer['model'] = $requested;
            $answer['estimate_assumptions'][] = 'local_final_reply_usage_attributed_to_reported_request_model';
        } else {
            // Old replies lost the attempted provider model. Use a declared upper estimate
            // for the Sol/GPT 5.4 families observed here; this is not an invoice attribution.
            $rates = ['model' => 'legacy_local_fallback_assumption', 'input' => 2.50, 'cached_input' => 2.50, 'cache_write' => 2.50, 'output' => 15.0];
            $legacyAssumption = true;
            $answer['estimate_assumptions'][] = 'legacy_local_positive_tokens_assumed_provider_upper_estimate';
        }
    }
    if(!$rates) { $answer['cost_estimate_reason'] = $local ? 'local_fallback_with_unattributed_provider_usage' : 'unknown_model'; return $answer; }
    $answer['pricing_model'] = $rates['model'];
    if($input === null || $output === null) { $answer['cost_estimate_reason'] = 'missing_or_invalid_usage'; return $answer; }
    if(!$usage && isset($result['success']) && !$result['success'] && $input === 0 && $output === 0) {
        $answer['cost_estimate_reason'] = 'failed_attempt_without_confirmed_usage'; return $answer;
    }
    if(isset($values['usage_reported']) && !$values['usage_reported']) { $answer['cost_estimate_reason'] = 'usage_not_reported'; return $answer; }
    if($cachedRaw !== null && $cached === null || $writeRaw !== null && $write === null || $cached !== null && $cached > $input || $write !== null && $write > $input || $cached !== null && $write !== null && $cached + $write > $input || $reasoning !== null && $reasoning > $output) {
        $answer['cost_estimate_reason'] = 'invalid_usage_breakdown'; return $answer;
    }
    if($cached === null) { $cached = 0; $answer['estimate_assumptions'][] = 'cache_hits_unreported'; }
    if($write === null) {
        $write = str_starts_with($rates['model'], 'gpt-6-') || str_starts_with($rates['model'], 'gpt-5.6-') ? $input - $cached : 0;
        $answer['estimate_assumptions'][] = $write > 0 ? 'unclassified_gpt6_input_priced_as_cache_write' : 'cache_writes_unreported';
    }
    $ordinary = $input - $cached - $write;
    $longContext = $input > 272000 && in_array($rates['model'], ['gpt-6-sol', 'gpt-6-luna', 'gpt-5.4', 'gpt-5.6-terra', 'legacy_local_fallback_assumption'], true);
    $tier = strtolower($answer['service_tier']);
    $multiplier = in_array($tier, ['priority', 'fast'], true) ? 2.0 : ($tier === 'flex' ? 0.5 : 1.0);
    if($tier !== '' && !in_array($tier, ['default', 'priority', 'fast', 'flex', 'incentivized-tier'], true)) {
        $answer['cost_estimate_reason'] = 'unknown_service_tier'; return $answer;
    }
    if($tier === '' || $tier === 'incentivized-tier') $answer['estimate_assumptions'][] = $tier === '' ? 'standard_tier_assumed' : 'incentivized_credits_not_deducted_from_soft_budget';
    $inputMultiplier = ($longContext ? 2.0 : 1.0) * $multiplier;
    $outputMultiplier = ($longContext ? 1.5 : 1.0) * $multiplier;
    $breakdown = [
        'uncached_input' => $ordinary * $rates['input'] * $inputMultiplier / 1000000,
        'cached_input' => $cached * $rates['cached_input'] * $inputMultiplier / 1000000,
        'cache_write_input' => $write * $rates['cache_write'] * $inputMultiplier / 1000000,
        'output' => $output * $rates['output'] * $outputMultiplier / 1000000,
    ];
    return array_replace($answer, [
        'cached_input_tokens' => $cached, 'cache_write_input_tokens' => $write, 'uncached_input_tokens' => $ordinary,
        'estimated_cost_usd' => round(array_sum($breakdown), 8), 'estimated_cost_breakdown_usd' => $breakdown,
        'cost_estimate_status' => $legacyAssumption ? 'estimated_legacy_fallback_assumption' : 'estimated_with_assumptions', 'partial' => $legacyAssumption,
    ]);
}

function fcc_ai_spend_has_marker(array $row): bool {
    $meta = fcc_ai_spend_array($row['meta'] ?? []);
    foreach([$row, $meta, fcc_ai_spend_array($row['usage'] ?? []), fcc_ai_spend_array($row['provider_usage'] ?? []), fcc_ai_spend_array($meta['usage'] ?? []), fcc_ai_spend_array($meta['provider_usage'] ?? [])] as $value) {
        if(!empty($value['spend_recorded'])) return true;
    }
    return false;
}

function fcc_ai_spend_empty_totals(): array {
    return ['usd' => 0.0, 'input_tokens' => 0, 'output_tokens' => 0, 'replies' => 0, 'attempts' => 0, 'unknown' => 0, 'partial' => false];
}

function fcc_ai_spend_add(array &$total, array $row): void {
    $total['attempts']++;
    $total['replies'] += !empty($row['success']) ? 1 : 0;
    foreach(['input_tokens', 'output_tokens'] as $field) $total[$field] += fcc_ai_spend_count($row[$field] ?? null) ?? 0;
    if($row['estimated_cost_usd'] === null) { $total['unknown']++; $total['partial'] = true; }
    else {
        $total['usd'] += (float) $row['estimated_cost_usd'];
        if(!empty($row['partial'])) { $total['unknown']++; $total['partial'] = true; }
    }
}

/** Caller supplies the selected owner's explicit linked IDs, never a whole team. */
function fcc_ai_spend_profile(array $ids): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
    $windows = fcc_ai_spend_windows();
    $profile = ['available' => false, 'error' => null, 'owner_id' => 0, 'account_ids' => $ids, 'periods' => [], 'channels' => [], 'month' => $windows['month'], 'defaults' => fcc_ai_spend_defaults(), 'overrides' => ['coach' => null, 'cards' => null], 'gross_estimate' => true];
    foreach([7, 30, 90] as $days) $profile['periods'][$days] = ['coach' => fcc_ai_spend_empty_totals(), 'cards' => fcc_ai_spend_empty_totals(), 'total' => fcc_ai_spend_empty_totals(), 'from_utc' => $windows['periods'][$days]['start'], 'to_utc' => $windows['now_utc']];
    foreach(fcc_ai_spend_defaults() as $channel => $limit) $profile['channels'][$channel] = ['spent_usd' => null, 'premium_spent_usd' => null, 'limit_usd' => $limit, 'downgraded' => true, 'partial' => true, 'model' => 'gpt-6-luna'];
    try {
        if(!$ids) throw new InvalidArgumentException('AI spend owner missing.');
        $identity = fcc_ai_spend_identity($ids[0]);
        // Expanding is safe only through explicit active account links.
        if(array_diff($ids, $identity['account_ids'])) throw new InvalidArgumentException('AI spend profile spans unrelated owners.');
        $ids = $identity['account_ids'];
        $profile['owner_id'] = $identity['owner_id']; $profile['account_ids'] = $ids;
        $limits = fcc_ai_spend_rows('SELECT coach_usd, cards_usd FROM fcc_ai_spend_limits WHERE owner_id=? LIMIT 1', [$identity['owner_id']]);
        foreach(['coach', 'cards'] as $channel) {
            $raw = $limits[0][$channel . '_usd'] ?? null;
            if($raw !== null && (!is_numeric($raw) || !is_finite((float) $raw) || (float) $raw < 0)) throw new RuntimeException('Invalid AI spend limit.');
            $profile['overrides'][$channel] = $raw === null ? null : (float) $raw;
            $profile['channels'][$channel]['limit_usd'] = $profile['overrides'][$channel] ?? $profile['defaults'][$channel];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $start = min($windows['periods'][90]['start'], $windows['month']['start_utc']);
        $params = array_merge($ids, [$start, $windows['now_utc']]);
        $ledger = fcc_ai_spend_rows("SELECT attempt_id,user_id,channel,model,input_tokens,output_tokens,estimated_cost_usd,status,created_at,request_id FROM fcc_ai_spend_usage WHERE user_id IN ($in) AND created_at>=? AND created_at<=?", $params);
        $history = fcc_ai_spend_rows("SELECT m.fcc_ai_message_id,m.model,m.input_tokens,m.output_tokens,m.meta,m.datetime,c.user_id,c.assistant_type,c.scope FROM fcc_ai_messages m JOIN fcc_ai_conversations c ON c.fcc_ai_conversation_id=m.fcc_ai_conversation_id WHERE c.user_id IN ($in) AND m.datetime>=? AND m.datetime<=? AND m.role='assistant' AND m.message_type='chat' AND (c.scope IN ('public_app','public_blog') OR c.assistant_type='coach')", $params);
        $month = ['coach' => fcc_ai_spend_empty_totals(), 'cards' => fcc_ai_spend_empty_totals()];
        $premiumMonth = ['coach' => 0.0, 'cards' => 0.0];
        $ledgerUnknownMonth = ['coach' => false, 'cards' => false];
        $requestIds = [];
        $attemptsById = [];
        foreach($ledger as $row) if(!empty($row['request_id'])) $requestIds[(string) $row['request_id']] = true;
        foreach($ledger as $row) if(!empty($row['attempt_id'])) $attemptsById[(string) $row['attempt_id']] = $row;
        $rows = [];
        foreach($ledger as $row) {
            $cost = $row['estimated_cost_usd'] ?? null;
            $rows[] = array_replace($row, ['estimated_cost_usd' => $cost === null ? null : (float) $cost, 'success' => ($row['status'] ?? '') === 'success', 'source' => 'ledger']);
        }
        foreach($history as $row) {
            if(fcc_ai_spend_has_marker($row)) continue;
            $meta = fcc_ai_spend_array($row['meta'] ?? []);
            $usage = fcc_ai_spend_array($meta['provider_usage'] ?? $meta['usage'] ?? []);
            $replyUsage = fcc_ai_spend_array($meta['usage'] ?? []);
            $attemptItems = fcc_ai_spend_array($usage['attempt_usage'] ?? $replyUsage['attempt_usage'] ?? $meta['attempt_usage'] ?? []);
            $expanded = false;
            foreach($attemptItems as $attemptValue) {
                $attempt = fcc_ai_spend_array($attemptValue);
                if(!$attempt) continue;
                $expanded = true;
                $attemptId = (string) ($attempt['spend_attempt_id'] ?? '');
                $requestId = (string) ($attempt['request_id'] ?? '');
                if(fcc_ai_spend_has_marker($attempt) || $attemptId !== '' && isset($attemptsById[$attemptId]) || $requestId !== '' && isset($requestIds[$requestId])) continue;
                // Provider drafts and repairs can use different models. Price only the
                // missing individual attempt, never their combined tokens at the last model.
                $estimate = fcc_ai_spend_estimate($attempt);
                $rows[] = array_replace($row, $estimate, [
                    'channel' => ($row['assistant_type'] ?? '') === 'coach' ? 'coach' : 'cards',
                    'created_at' => $row['datetime'], 'success' => !empty($attempt['success']), 'source' => 'provider_attempt_history',
                ]);
            }
            if($expanded) continue;
            if(!empty($usage['request_id']) && isset($requestIds[(string) $usage['request_id']])) continue;
            $estimate = fcc_ai_spend_estimate($row);
            $attemptIds = array_values(array_unique(array_filter(array_merge(
                (array) ($meta['spend_attempt_ids'] ?? []), (array) ($usage['spend_attempt_ids'] ?? []),
                [$meta['spend_attempt_id'] ?? null, $usage['spend_attempt_id'] ?? null]
            ), 'is_string')));
            $matched = array_intersect($attemptIds, array_keys($attemptsById));
            if($attemptIds && count($matched) === count($attemptIds)) continue;
            // A repair may have recorded only one provider attempt. Count its remainder
            // from the reply metadata without counting the already stored attempt twice.
            foreach($matched as $attemptId) {
                $recorded = $attemptsById[$attemptId];
                foreach(['input_tokens', 'output_tokens'] as $field) {
                    if($estimate[$field] !== null) $estimate[$field] = max(0, $estimate[$field] - (fcc_ai_spend_count($recorded[$field] ?? null) ?? 0));
                }
                if($estimate['estimated_cost_usd'] !== null && $recorded['estimated_cost_usd'] !== null) $estimate['estimated_cost_usd'] = max(0.0, $estimate['estimated_cost_usd'] - (float) $recorded['estimated_cost_usd']);
            }
            $rows[] = array_replace($row, $estimate, ['channel' => ($row['assistant_type'] ?? '') === 'coach' ? 'coach' : 'cards', 'created_at' => $row['datetime'], 'success' => true, 'source' => 'legacy_history']);
        }
        foreach($rows as $row) {
            $channel = $row['channel'] ?? ''; if(!isset($month[$channel])) continue;
            $at = (string) ($row['created_at'] ?? '');
            if($at >= $windows['month']['start_utc'] && $at < $windows['month']['end_utc']) {
                fcc_ai_spend_add($month[$channel], $row);
                if(in_array(($row['source'] ?? ''), ['ledger', 'provider_attempt_history'], true) && $row['estimated_cost_usd'] === null) $ledgerUnknownMonth[$channel] = true;
                $rateModel = fcc_ai_spend_rates((string) ($row['model'] ?? ''));
                if(($rateModel['model'] ?? '') !== 'gpt-6-luna' && $row['estimated_cost_usd'] !== null) $premiumMonth[$channel] += (float) $row['estimated_cost_usd'];
            }
            foreach([7, 30, 90] as $days) {
                if($at >= $windows['periods'][$days]['start'] && $at <= $windows['now_utc']) {
                    fcc_ai_spend_add($profile['periods'][$days][$channel], $row);
                    fcc_ai_spend_add($profile['periods'][$days]['total'], $row);
                }
            }
        }
        foreach(['coach', 'cards'] as $channel) {
            $partial = $month[$channel]['partial'];
            $spent = round($month[$channel]['usd'], 8);
            $premiumSpent = round($premiumMonth[$channel], 8);
            $limit = $profile['channels'][$channel]['limit_usd'];
            $budgetSpent = $channel === 'coach' ? $premiumSpent : $spent;
            $down = $ledgerUnknownMonth[$channel] || $limit > 0 && $budgetSpent >= $limit;
            $profile['channels'][$channel] = array_replace($profile['channels'][$channel], ['spent_usd' => $spent, 'premium_spent_usd' => $premiumSpent, 'partial' => $partial, 'downgraded' => $down, 'model' => $down ? 'gpt-6-luna' : null, 'unknown' => $month[$channel]['unknown'], 'ledger_unknown' => $ledgerUnknownMonth[$channel]]);
        }
        $profile['available'] = true;
    } catch(Throwable $error) {
        $profile['error'] = 'spend_data_unavailable';
    }
    return $profile;
}

function fcc_ai_spend_preview(int $userId, string $channel, string $requestedModel): array {
    $channel = fcc_ai_spend_channel($channel);
    $profile = fcc_ai_spend_profile([$userId]);
    $state = $profile['channels'][$channel];
    $down = $state['downgraded'] || fcc_ai_spend_rates($requestedModel) === null;
    return array_replace($state, ['model' => $down ? 'gpt-6-luna' : $requestedModel, 'fallback' => $down, 'owner_id' => $profile['owner_id'] ?: $userId, 'account_ids' => $profile['account_ids'], 'reason' => !$profile['available'] ? 'spend_data_unavailable' : (!empty($state['ledger_unknown']) ? 'unknown_provider_attempt' : ($down ? 'monthly_soft_budget' : ($state['partial'] ? 'legacy_history_estimate' : 'within_budget'))), 'month' => $profile['month']]);
}

function fcc_ai_spend_model(int $userId, string $channel, string $requestedModel): string {
    return fcc_ai_spend_preview($userId, $channel, $requestedModel)['model'];
}

function fcc_ai_spend_choose_model(int $userId, string $channel, string $requestedModel): string {
    return fcc_ai_spend_model($userId, $channel, $requestedModel);
}

/** The lock covers budget read, provider request and ledger insertion on this DB connection. */
function fcc_ai_spend_begin(int $userId, string $channel, string $requestedModel): array {
    $channel = fcc_ai_spend_channel($channel);
    $ticket = ['user_id' => $userId, 'owner_id' => $userId, 'channel' => $channel, 'requested_model' => $requestedModel, 'model' => 'gpt-6-luna', 'allowed' => false, 'lock' => null, 'attempt_id' => bin2hex(random_bytes(16)), 'reason' => 'spend_lock_unavailable'];
    try {
        $identity = fcc_ai_spend_identity($userId); $ticket = array_replace($ticket, $identity);
        $name = 'fcc_ai_spend:' . $identity['owner_id'] . ':' . $channel;
        if(isset($GLOBALS['fcc_ai_spend_locks'][$name])) { $ticket['reason'] = 'spend_lock_busy'; return $ticket; }
        $acquired = fcc_ai_spend_rows('SELECT GET_LOCK(?,0) AS acquired', [$name]);
        if((int) ($acquired[0]['acquired'] ?? 0) !== 1) { $ticket['reason'] = 'spend_lock_busy'; return $ticket; }
        $GLOBALS['fcc_ai_spend_locks'][$name] = $ticket['attempt_id'];
        $ticket['lock'] = $name;
        $decision = fcc_ai_spend_preview($userId, $channel, $requestedModel);
        return array_replace($ticket, $decision, ['allowed' => true, 'lock' => $name, 'owner_id' => $identity['owner_id'], 'account_ids' => $identity['account_ids']]);
    } catch(Throwable $error) {
        fcc_ai_spend_release($ticket);
        return $ticket;
    }
}

function fcc_ai_spend_release(array $ticket): void {
    $name = $ticket['lock'] ?? null;
    if(!$name || ($GLOBALS['fcc_ai_spend_locks'][$name] ?? null) !== ($ticket['attempt_id'] ?? null)) return;
    try { fcc_ai_spend_rows('SELECT RELEASE_LOCK(?) AS released', [$name]); }
    catch(Throwable $error) { /* Connection closure also releases its advisory locks. */ }
    finally { unset($GLOBALS['fcc_ai_spend_locks'][$name]); }
}

function fcc_ai_spend_finish(array $ticket, array $result): array {
    try {
        if(empty($ticket['allowed'])) return $result;
        $estimate = fcc_ai_spend_estimate($result);
        $usage = fcc_ai_spend_array($result['provider_usage'] ?? $result['usage'] ?? []);
        $requestId = (string) ($result['request_id'] ?? $usage['request_id'] ?? '');
        $record = [
            'attempt_id' => $ticket['attempt_id'], 'owner_id' => $ticket['owner_id'], 'user_id' => $ticket['user_id'], 'channel' => $ticket['channel'],
            'requested_model' => substr((string) $ticket['requested_model'], 0, 96), 'model' => substr((string) $estimate['model'], 0, 96),
            'provider' => substr((string) ($result['provider'] ?? 'openai'), 0, 32), 'request_id' => substr($requestId, 0, 128),
            'status' => !empty($result['success']) ? 'success' : 'failed', 'reason' => substr(preg_replace('/[^a-zA-Z0-9_:.]/', '', (string) ($result['reason'] ?? '')), 0, 96),
            'input_tokens' => $estimate['input_tokens'], 'output_tokens' => $estimate['output_tokens'], 'cached_input_tokens' => $estimate['cached_input_tokens'],
            'cache_write_input_tokens' => $estimate['cache_write_input_tokens'], 'reasoning_tokens' => $estimate['reasoning_tokens'],
            'estimated_cost_usd' => $estimate['estimated_cost_usd'], 'cost_estimate_status' => $estimate['cost_estimate_status'],
            'usage_json' => json_encode($estimate, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), 'created_at' => gmdate('Y-m-d H:i:s'),
        ];
        $columns = array_keys($record);
        $sql = 'INSERT INTO fcc_ai_spend_usage (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($record), '?')) . ') ON DUPLICATE KEY UPDATE attempt_id=VALUES(attempt_id)';
        fcc_ai_spend_rows($sql, array_values($record));
        foreach(['estimated_cost_usd', 'estimated_cost_breakdown_usd', 'cost_estimate_status', 'cost_estimate_reason', 'pricing_model', 'pricing_basis', 'price_version', 'estimate_assumptions'] as $key) $result[$key] = $estimate[$key];
        $result['spend_recorded'] = 1; $result['spend_attempt_id'] = $ticket['attempt_id'];
        $result['spend_estimated_cost_usd'] = $estimate['estimated_cost_usd'];
        return $result;
    } catch(Throwable $error) {
        $result['spend_recorded'] = 0; $result['spend_record_error'] = 'spend_ledger_unavailable'; return $result;
    } finally { fcc_ai_spend_release($ticket); }
}

function fcc_ai_spend_save_limits(int $actor, int $owner, ?float $coach, ?float $cards): array {
    if(!function_exists('fcc_team_admin') || !fcc_team_admin($actor)) throw new RuntimeException('Samo administrator može mijenjati AI budžet.');
    foreach([$coach, $cards] as $value) if($value !== null && (!is_finite($value) || $value < 0 || $value > 100000)) throw new InvalidArgumentException('AI budžet mora biti od 0 do 100000 USD.');
    $identity = fcc_ai_spend_identity($owner); $owner = $identity['owner_id'];
    $exists = fcc_ai_spend_rows('SELECT user_id FROM users WHERE user_id=? LIMIT 1', [$owner]);
    if(!$exists) throw new InvalidArgumentException('Suradnik ne postoji.');
    $coach = $coach === null ? null : round($coach, 2); $cards = $cards === null ? null : round($cards, 2);
    fcc_ai_spend_rows('INSERT INTO fcc_ai_spend_limits (owner_id,coach_usd,cards_usd,updated_by,updated_at) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE coach_usd=VALUES(coach_usd),cards_usd=VALUES(cards_usd),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)', [$owner, $coach, $cards, $actor, gmdate('Y-m-d H:i:s')]);
    return ['owner_id' => $owner, 'coach' => $coach, 'cards' => $cards];
}
