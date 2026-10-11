<?php
defined('ALTUMCODE') || die();
require_once __DIR__ . '/fcc_ai_spend.php';

/** Only these workloads migrate. Explicit administrator model choices win. */
function fcc_ai_recommended_models(): array {
    return ['coach_beginner'=>'gpt-6-luna', 'coach_vip'=>'gpt-6-sol', 'product_advisor'=>'gpt-6-sol'];
}

function fcc_ai_workload_options(string $workload, string $model): array {
    $coach = $workload === 'coach';
    $options = ['store'=>false, 'max_completion_tokens'=>$coach ? 2400 : 1800];
    if(preg_match('/^(gpt-[56](?:[.-]|$)|o[134](?:[.-]|$))/i', $model)) {
        $options['reasoning_effort'] = $coach ? 'low' : 'none';
        if(preg_match('/^(gpt-5(?:-mini|-nano)?(?:-\d{4}-\d{2}-\d{2})?|o[134](?:[.-].*)?)$/', $model)) {
            $options['reasoning_effort'] = 'low';
        }
        if(str_starts_with($model, 'gpt-6-astra')) $options['reasoning_effort'] = 'low';
    }
    return $options;
}

/** Approximate standard gross USD estimates share the monthly budget price policy. */
function fcc_ai_usage_metrics(string $model, array $usage): array {
    $input = max(0, (int)($usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0));
    $output = max(0, (int)($usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0));
    $details = (array)($usage['input_tokens_details'] ?? $usage['prompt_tokens_details'] ?? []);
    $outputDetails = (array)($usage['output_tokens_details'] ?? $usage['completion_tokens_details'] ?? []);
    $cached = min($input, max(0, (int)($details['cached_tokens'] ?? 0)));
    $writes = min($input-$cached, max(0, (int)($details['cache_write_tokens'] ?? 0)));
    $estimate = fcc_ai_spend_estimate(['model' => $model, 'provider' => 'openai', 'provider_usage' => $usage]);
    return array_merge([
        'input_tokens'=>$input, 'output_tokens'=>$output,
        'total_tokens'=>max(0, (int)($usage['total_tokens'] ?? $input+$output)),
        'cached_input_tokens'=>$cached, 'cache_write_tokens'=>$writes,
        'reasoning_tokens'=>min($output,max(0,(int)($outputDetails['reasoning_tokens'] ?? 0))),
        'provider_usage' => $usage,
    ], $estimate);
}

/** Count billed attempts even when a draft fails the application validator. */
function fcc_ai_combine_attempt_usage(array $first, array $second): array {
    $merged = $second;
    foreach(['input_tokens','output_tokens','total_tokens','cached_input_tokens','cache_write_tokens','cache_write_input_tokens','uncached_input_tokens','reasoning_tokens','api_response_time'] as $field) {
        $merged[$field] = (int)($first[$field] ?? 0)+(int)($second[$field] ?? 0);
    }
    $merged['estimated_cost_usd'] = isset($first['estimated_cost_usd'],$second['estimated_cost_usd'])
        ? round($first['estimated_cost_usd']+$second['estimated_cost_usd'],8) : null;
    $merged['api_attempts'] = (int)($first['api_attempts'] ?? 1)+(int)($second['api_attempts'] ?? 1);
    $summarize = static function(array $attempt): array {
        $fields = fcc_ai_usage_metadata($attempt);
        unset($fields['attempt_usage']);
        return array_merge($fields, array_intersect_key($attempt, array_flip(['model', 'provider', 'success', 'reason'])));
    };
    $merged['attempt_usage'] = array_merge($first['attempt_usage'] ?? [$summarize($first)], $second['attempt_usage'] ?? [$summarize($second)]);
    $ids = array_values(array_unique(array_merge((array) ($first['spend_attempt_ids'] ?? []), (array) ($second['spend_attempt_ids'] ?? []), array_filter([$first['spend_attempt_id'] ?? null, $second['spend_attempt_id'] ?? null]))));
    if($ids) $merged['spend_attempt_ids'] = $ids;
    $merged['spend_recorded'] = !empty($first['spend_recorded']) && !empty($second['spend_recorded']) ? 1 : 0;
    $merged['spend_estimated_cost_usd'] = $merged['estimated_cost_usd'];
    $merged['partial'] = !empty($first['partial']) || !empty($second['partial']) || $merged['estimated_cost_usd'] === null;
    if($merged['estimated_cost_usd'] === null) {
        $merged['cost_estimate_status'] = 'unavailable';
        $merged['cost_estimate_reason'] = 'partial_attempt_usage';
    }
    if(is_array($first['estimated_cost_breakdown_usd'] ?? null) && is_array($second['estimated_cost_breakdown_usd'] ?? null)) {
        foreach(array_unique(array_merge(array_keys($first['estimated_cost_breakdown_usd']), array_keys($second['estimated_cost_breakdown_usd']))) as $key) {
            $merged['estimated_cost_breakdown_usd'][$key] = (float) ($first['estimated_cost_breakdown_usd'][$key] ?? 0) + (float) ($second['estimated_cost_breakdown_usd'][$key] ?? 0);
        }
    } else $merged['estimated_cost_breakdown_usd'] = null;
    if(($first['actual_model'] ?? $first['model'] ?? '') !== ($second['actual_model'] ?? $second['model'] ?? '')) {
        $merged['actual_model'] = 'mixed_models';
        $merged['pricing_model'] = 'mixed_models';
    }
    // The combined result spans independently billed attempts and may span models.
    unset($merged['provider_usage']);
    return $merged;
}

function fcc_ai_usage_metadata(array $result): array {
    $fields = array_intersect_key($result,array_flip([
        'input_tokens','output_tokens','total_tokens','cached_input_tokens','cache_write_tokens','reasoning_tokens',
        'estimated_cost_usd','price_version','api_attempts','api_response_time','requested_model','provider_usage',
        'cost_estimate_status','cost_estimate_reason','pricing_model','estimate_assumptions','cache_write_input_tokens','uncached_input_tokens','partial',
        'spend_recorded','spend_attempt_id','spend_attempt_ids','spend_owner_id','spend_channel','spend_budget_month','spend_downgraded',
        'spend_estimated_cost_usd','spend_record_error','estimated_cost_breakdown_usd','pricing_basis','service_tier','actual_model',
        'attempt_usage',
    ]));
    if(!isset($fields['actual_model']) && isset($result['model'])) $fields['actual_model'] = (string) $result['model'];
    return $fields;
}

function fcc_ai_build_chat_request(string $model, array $messages, string $identifier, array $options): array {
    if(preg_match('/^gpt-6-(sol|luna)(?:-|$)/', $model, $match)) {
        $options = array_merge(fcc_ai_workload_options($match[1] === 'sol' ? 'coach' : 'public_advisor', $model), $options);
    }
    $body = ['model'=>$model, 'messages'=>$messages, 'store'=>false,
        'user'=>hash('sha256', 'fcc-ai|'.$identifier)];
    $allowed = array_intersect_key($options,array_flip(['response_format','max_completion_tokens','reasoning_effort']));
    if(isset($allowed['max_completion_tokens'])) $allowed['max_completion_tokens'] = max(256,min(4096,(int)$allowed['max_completion_tokens']));
    return array_merge($body,$allowed);
}

/** Stable rules get their own message boundary; live account facts stay fresh. */
function fcc_ai_cacheable_coach_messages(array $messages): array {
    if(($messages[0]['role'] ?? '') !== 'system') return $messages;
    $separator="\n\nServer context JSON:\n";
    $parts=explode($separator,(string)($messages[0]['content'] ?? ''),2);
    if(count($parts)!==2) return $messages;
    $messages[0]['content']=$parts[0];
    array_splice($messages,1,0,[['role'=>'system','content'=>'Server context JSON:'."\n".$parts[1]]]);
    return $messages;
}

/** A truncated JSON/text response must never look like a complete reply. */
function fcc_ai_parse_chat_response(string $requestedModel, array $response): array {
    $model = trim((string)($response['model'] ?? $requestedModel));
    $result = array_merge(fcc_ai_usage_metrics($model,(array)($response['usage'] ?? [])),[
        'success'=>false,'reason'=>'empty_response','model'=>$model,'actual_model'=>$model,'requested_model'=>$requestedModel,
        'provider'=>'openai','api_attempts'=>1, 'provider_usage'=>(array)($response['usage'] ?? []),
        'service_tier'=>(string)($response['service_tier'] ?? ''),
    ]);
    $choice = $response['choices'][0] ?? [];
    if(($choice['finish_reason'] ?? '') !== 'stop') {
        $result['reason'] = ($choice['finish_reason'] ?? '') === 'length' ? 'output_limit' : 'incomplete_response';
        return $result;
    }
    if(!empty($choice['message']['refusal'])) {
        $result['reason']='refusal';
        return $result;
    }
    $content = fcc_ai_extract_openai_message_text($choice['message']['content'] ?? '');
    if($content !== '') $result = array_merge($result,['success'=>true,'reason'=>'openai','content'=>$content]);
    return $result;
}
