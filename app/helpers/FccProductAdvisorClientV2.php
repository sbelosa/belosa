<?php

namespace Altum\Helpers;

defined('ALTUMCODE') || die();
require_once __DIR__ . '/fcc_ai_model_policy.php';

final class FccProductAdvisorClientV2 {

    private const REQUIRED_INPUT_KEYS = [
        'language',
        'market',
        'message',
        'history',
        'state',
        'safety',
        'candidates',
        'support_relationships',
        'source_context',
    ];

    private const OUTPUT_FIELDS = [
        'language',
        'intent',
        'safety_level',
        'decision',
        'primary_product_key',
        'support_product_keys',
        'fact_ids',
        'acknowledgement',
        'explanation',
        'clarifying_question',
        'lead_capture',
        'state_patch',
    ];

    private const INTENTS = [
        'product_recommendation',
        'product_information',
        'usage',
        'clarification',
        'business',
        'contact',
        'small_talk',
        'unsupported',
        'safety',
    ];

    private const SAFETY_LEVELS = ['normal', 'caution', 'urgent'];
    private const DECISIONS = ['recommend', 'clarify', 'inform', 'refer', 'no_product'];
    private const LEAD_TYPES = ['product_interest', 'business_interest', 'mixed_interest', 'support_request'];

    private const MAX_SUPPORT_PRODUCTS = 2;
    private const MAX_FACT_IDS = 8;

    private array $config;

    private string $apiKey;
    private array $spendResult = [];

    /** @var callable */
    private $transport;

    public function __construct(?string $apiKey = null, ?array $config = null, ?callable $transport = null) {
        $defaults = require APP_PATH . 'config/fcc_ai_product_advisor_v2.php';
        $this->config = array_replace_recursive($defaults, $config ?? []);
        $this->transport = $transport ?? [$this, 'sendHttpRequest'];
        $this->apiKey = trim((string) $apiKey);

        if($this->apiKey === '' && function_exists('fcc_ai_get_openai_api_key')) {
            $this->apiKey = trim((string) \fcc_ai_get_openai_api_key());
        }
    }

    public function generate(string $model, array $input, string $userIdentifier, int $ownerUserId = 0): array {
        $this->spendResult = [];
        $startedAt = microtime(true);
        $model = trim($model);
        $userIdentifier = trim($userIdentifier);

        if($model === '') {
            return $this->result($model, [
                'reason' => 'invalid_model',
                'error' => 'A model is required.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
            ]);
        }

        if($userIdentifier === '') {
            return $this->result($model, [
                'reason' => 'invalid_user_identifier',
                'error' => 'A stable server-side user identifier is required.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
            ]);
        }

        $inputError = $this->validateInput($input);

        if($inputError !== null) {
            return $this->result($model, [
                'reason' => 'invalid_input',
                'error' => $inputError,
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
            ]);
        }

        $apiKey = $this->apiKey;

        if($apiKey === '') {
            return $this->result($model, [
                'reason' => 'missing_api_key',
                'error' => 'The OpenAI API key is not configured.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
            ]);
        }

        try {
            $requestBody = $this->buildRequestBody($model, $input, $userIdentifier);
            $encodedBody = json_encode(
                $requestBody,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        } catch(\Throwable $exception) {
            return $this->result($model, [
                'reason' => 'request_build_error',
                'error' => $this->sanitizeError($exception->getMessage(), $apiKey),
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
            ]);
        }

        $timeoutSeconds = min(20, max(1, (int) ($this->config['timeout_seconds'] ?? 20)));
        $connectTimeoutSeconds = min($timeoutSeconds, max(1, (int) ($this->config['connect_timeout_seconds'] ?? 5)));
        $maxAttempts = min(2, max(1, (int) ($this->config['max_attempts'] ?? 2)));
        $lastResponse = null;

        for($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $spendTicket = null;
            $spendFinished = false;
            if($ownerUserId > 0) {
                $spendTicket = \fcc_ai_spend_begin($ownerUserId, 'cards', $model);
                if(empty($spendTicket['allowed'])) {
                    \fcc_ai_spend_release($spendTicket);
                    return $this->result((string) ($spendTicket['model'] ?? $model), [
                        'reason' => (string) ($spendTicket['reason'] ?? 'spend_busy'),
                        'api_response_time' => $this->elapsedMilliseconds($startedAt),
                    ]);
                }
            }
            try {
                if($spendTicket && ((string) ($spendTicket['model'] ?? $model) !== $model || $model === 'gpt-6-luna')) {
                    $model = (string) $spendTicket['model'];
                    $requestBody = $this->buildRequestBody($model, $input, $userIdentifier);
                    if($model === 'gpt-6-luna') $requestBody['reasoning'] = ['effort' => 'none'];
                    $encodedBody = json_encode($requestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                }
                $lastResponse = call_user_func($this->transport, [
                    'url' => (string) ($this->config['endpoint'] ?? 'https://api.openai.com/v1/responses'),
                    'headers' => [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ],
                    'body' => $encodedBody,
                    'timeout_seconds' => $timeoutSeconds,
                    'connect_timeout_seconds' => $connectTimeoutSeconds,
                ]);
                if($spendTicket) {
                    $rawResponse = is_array($lastResponse) ? json_decode((string) ($lastResponse['body'] ?? ''), true) : null;
                    $rawResponse = is_array($rawResponse) ? $rawResponse : [];
                    $actualModel = trim((string) ($rawResponse['model'] ?? '')) ?: $model;
                    $rawUsage = is_array($rawResponse['usage'] ?? null) ? $rawResponse['usage'] : [];
                    $spendFinished = true;
                    $this->recordSpendResult($spendTicket, array_merge(\fcc_ai_usage_metrics($actualModel, $rawUsage), [
                        'success' => is_array($lastResponse) && (int) ($lastResponse['status'] ?? 0) >= 200 && (int) ($lastResponse['status'] ?? 0) < 300,
                        'reason' => !is_array($lastResponse) || (int) ($lastResponse['status'] ?? 0) === 0 ? 'transport_error' : ((int) $lastResponse['status'] >= 400 ? 'api_error' : 'provider_attempt'), 'provider' => 'openai', 'model' => $actualModel,
                        'requested_model' => $model, 'provider_usage' => $rawUsage, 'api_attempts' => 1,
                        'service_tier' => (string) ($rawResponse['service_tier'] ?? ''),
                        'request_id' => is_array($lastResponse) ? $this->extractRequestId((array) ($lastResponse['headers'] ?? []), $rawResponse) : '',
                    ]));
                }
            } catch(\Throwable $exception) {
                if($spendTicket && !$spendFinished) {
                    $this->recordSpendResult($spendTicket, ['success' => false, 'reason' => 'transport_error', 'provider' => 'openai', 'model' => $model, 'requested_model' => $model, 'api_attempts' => 1]);
                }
                return $this->result($model, [
                    'reason' => 'transport_error',
                    'error' => $this->sanitizeError($exception->getMessage(), $apiKey),
                    'api_response_time' => $this->elapsedMilliseconds($startedAt),
                ]);
            } finally {
                if($spendTicket) \fcc_ai_spend_release($spendTicket);
            }

            if(!is_array($lastResponse)) {
                return $this->result($model, [
                    'reason' => 'transport_error',
                    'error' => 'The OpenAI transport returned an invalid response.',
                    'api_response_time' => $this->elapsedMilliseconds($startedAt),
                ]);
            }

            $statusCode = (int) ($lastResponse['status'] ?? 0);

            if(!$this->isRetryableStatus($statusCode) || $attempt >= $maxAttempts) {
                break;
            }

            $this->waitBeforeRetry($lastResponse, $attempt);
        }

        $statusCode = (int) ($lastResponse['status'] ?? 0);
        $responseBody = (string) ($lastResponse['body'] ?? '');
        $responseHeaders = is_array($lastResponse['headers'] ?? null) ? $lastResponse['headers'] : [];
        $requestId = $this->extractRequestId($responseHeaders, null);

        if($statusCode === 0) {
            return $this->result($model, [
                'reason' => 'transport_error',
                'error' => $this->sanitizeError(
                    (string) ($lastResponse['error'] ?? 'The OpenAI request did not receive an HTTP response.'),
                    $apiKey
                ),
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]);
        }

        if($statusCode < 200 || $statusCode >= 300) {
            return $this->result($model, [
                'reason' => 'api_error',
                'error' => $this->extractApiError($responseBody, $statusCode, $apiKey),
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]);
        }

        try {
            $response = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch(\Throwable $exception) {
            return $this->result($model, [
                'reason' => 'invalid_provider_json',
                'error' => 'OpenAI returned an invalid JSON response.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]);
        }

        if(!is_array($response)) {
            return $this->result($model, [
                'reason' => 'invalid_provider_response',
                'error' => 'OpenAI returned an invalid response object.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]);
        }

        $requestId = $this->extractRequestId($responseHeaders, $response);
        $reportedModel = trim((string) ($response['model'] ?? ''));
        $actualModel = $reportedModel !== '' ? $reportedModel : $model;
        $usage = is_array($response['usage'] ?? null) ? $response['usage'] : [];
        $usageFields = \fcc_ai_usage_metrics($actualModel, $usage);
        $usageFields['api_attempts'] = $attempt;
        $usageFields['requested_model'] = $model;
        $usageFields['provider_usage'] = $usage;
        $usageFields['actual_model'] = $actualModel;
        $usageFields['service_tier'] = (string) ($response['service_tier'] ?? '');

        if(is_array($response['error'] ?? null)) {
            return $this->result($actualModel, array_merge($usageFields, [
                'reason' => 'provider_error',
                'error' => $this->sanitizeError(
                    trim((string) ($response['error']['message'] ?? 'OpenAI reported a response error.')),
                    $apiKey
                ),
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]));
        }

        $responseStatus = trim((string) ($response['status'] ?? ''));

        if($responseStatus === '') {
            return $this->result($actualModel, array_merge($usageFields, [
                'reason' => 'invalid_provider_response',
                'error' => 'OpenAI response status is missing.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]));
        }

        if($responseStatus !== 'completed') {
            $incompleteDetails = is_array($response['incomplete_details'] ?? null)
                ? $response['incomplete_details']
                : [];
            $detailReason = trim((string) ($incompleteDetails['reason'] ?? $responseStatus));

            return $this->result($actualModel, array_merge($usageFields, [
                'reason' => $responseStatus === 'incomplete' ? 'incomplete_response' : 'provider_error',
                'error' => 'OpenAI response status: ' . ($detailReason ?: $responseStatus) . '.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]));
        }

        if($reportedModel === '' || !$this->responseModelMatchesRequest($model, $reportedModel)) {
            return $this->result($actualModel, array_merge($usageFields, [
                'reason' => 'unexpected_response_model',
                'error' => $reportedModel === ''
                    ? 'OpenAI completed the response without reporting the model used.'
                    : 'OpenAI returned a model different from the requested model.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
                'requested_model' => $model,
                'reported_model' => $reportedModel,
            ]));
        }

        $content = $this->extractOutputContent($response);

        if($content['error'] !== '') {
            return $this->result($actualModel, array_merge($usageFields, [
                'reason' => 'invalid_provider_response',
                'error' => $content['error'],
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]));
        }

        if($content['refusal'] !== '') {
            return $this->result($actualModel, array_merge($usageFields, [
                'reason' => 'refusal',
                'error' => $this->sanitizeError($content['refusal'], $apiKey),
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]));
        }

        if($content['text'] === '') {
            return $this->result($actualModel, array_merge($usageFields, [
                'reason' => 'empty_response',
                'error' => 'OpenAI returned no structured output text.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]));
        }

        try {
            $data = json_decode($content['text'], true, 512, JSON_THROW_ON_ERROR);
        } catch(\Throwable $exception) {
            return $this->result($actualModel, array_merge($usageFields, [
                'reason' => 'invalid_structured_json',
                'error' => 'OpenAI structured output could not be decoded.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]));
        }

        if(!is_array($data)) {
            return $this->result($actualModel, array_merge($usageFields, [
                'reason' => 'invalid_structured_output',
                'error' => 'OpenAI structured output is not an object.',
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]));
        }

        $candidateKeys = $this->extractCandidateKeys($input['candidates']);
        $factIds = $this->extractCandidateFactIds($input['candidates']);
        $validationError = $this->validateStructuredData($data, $input, $candidateKeys, $factIds);

        if($validationError !== null) {
            return $this->result($actualModel, array_merge($usageFields, [
                'reason' => 'invalid_structured_output',
                'error' => $validationError,
                'api_response_time' => $this->elapsedMilliseconds($startedAt),
                'request_id' => $requestId,
            ]));
        }

        return $this->result($actualModel, array_merge($usageFields, [
            'success' => true,
            'data' => $data,
            'reason' => 'openai',
            'error' => null,
            'api_response_time' => $this->elapsedMilliseconds($startedAt),
            'request_id' => $requestId,
        ]));
    }

    private function validateInput(array $input): ?string {
        foreach(self::REQUIRED_INPUT_KEYS as $key) {
            if(!array_key_exists($key, $input)) {
                return 'Missing required input key: ' . $key . '.';
            }
        }

        if(!is_string($input['language']) || trim($input['language']) === '') {
            return 'Input language must be a non-empty string.';
        }

        if(!is_string($input['market'])) {
            return 'Input market must be a string.';
        }

        if(!is_string($input['message']) || trim($input['message']) === '') {
            return 'Input message must be a non-empty string.';
        }

        foreach(['history', 'state', 'candidates', 'support_relationships'] as $arrayKey) {
            if(!is_array($input[$arrayKey])) {
                return 'Input ' . $arrayKey . ' must be an array.';
            }
        }

        if(!is_array($input['safety']) && !is_string($input['safety'])) {
            return 'Input safety must be an array or string.';
        }

        if(!is_array($input['source_context']) && !is_string($input['source_context']) && $input['source_context'] !== null) {
            return 'Input source_context must be an array, string or null.';
        }

        return null;
    }

    private function buildRequestBody(string $model, array $input, string $userIdentifier): array {
        $instructions = $this->loadInstructions();
        $contractInput = [];

        foreach(self::REQUIRED_INPUT_KEYS as $key) {
            $contractInput[$key] = $input[$key];
        }

        $inputJson = json_encode(
            $contractInput,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        $candidateKeys = $this->extractCandidateKeys($input['candidates']);
        $factIds = $this->extractCandidateFactIds($input['candidates']);
        $knownStateProductKeys = array_values(array_unique(array_merge(
            $candidateKeys,
            $this->extractStateProductKeys($input['state'])
        )));
        $language = mb_strtolower(trim((string) $input['language']));
        $market = mb_strtoupper(trim((string) $input['market']));
        $promptVersion = trim((string) ($this->config['prompt_version'] ?? 'product_advisor_v2'));
        $identifierNamespace = trim((string) ($this->config['identifier_namespace'] ?? 'fcc_product_advisor_v2'));

        $body = [
            'model' => $model,
            'instructions' => $instructions,
            'input' => [
                [
                    'role' => 'user',
                    'content' => $inputJson,
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => (string) ($this->config['schema_name'] ?? 'fcc_product_advisor_v2'),
                    'strict' => true,
                    'schema' => $this->buildOutputSchema(
                        $candidateKeys,
                        $factIds,
                        $knownStateProductKeys,
                        (string) $contractInput['language']
                    ),
                ],
            ],
            'store' => false,
            'max_output_tokens' => max(256, min(4096, (int) ($this->config['max_output_tokens'] ?? 1200))),
            'safety_identifier' => hash('sha256', $identifierNamespace . '|safety|' . $userIdentifier),
            'prompt_cache_key' => hash(
                'sha256',
                $identifierNamespace . '|cache|' . $promptVersion . '|' . $model . '|' . $language . '|' . $market
            ),
        ];

        if($this->supportsReasoningEffort($model)) {
            $body['reasoning'] = [
                'effort' => (string) ($this->config['reasoning_effort'] ?? 'low'),
            ];
        }

        if($this->supportsVerbosity($model)) {
            $body['text']['verbosity'] = (string) ($this->config['verbosity'] ?? 'low');
        }

        return $body;
    }

    private function loadInstructions(): string {
        if(isset($this->config['instructions']) && is_string($this->config['instructions'])) {
            $instructions = trim($this->config['instructions']);
        } else {
            $promptPath = (string) ($this->config['prompt_path'] ?? '');

            if($promptPath === '' || !is_file($promptPath)) {
                throw new \RuntimeException(fcc_t('The Product Advisor V2 prompt file is unavailable.'));
            }

            $instructions = trim((string) require $promptPath);
        }

        if($instructions === '') {
            throw new \RuntimeException(fcc_t('The Product Advisor V2 prompt is empty.'));
        }

        return $instructions;
    }

    private function buildOutputSchema(array $candidateKeys, array $factIds, array $knownStateProductKeys, string $language): array {
        $leadCaptureProperties = [
            'recommended' => ['type' => 'boolean'],
            'lead_type' => ['type' => 'string', 'enum' => self::LEAD_TYPES],
            'headline' => ['type' => 'string'],
            'text' => ['type' => 'string'],
        ];
        $statePatchProperties = [
            'goal_keys' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'maxItems' => 0,
            ],
            'subject' => ['type' => 'null'],
            'mentioned_product_keys' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 0],
            'rejected_product_keys' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 0],
            'pending_question' => ['type' => 'null'],
            'clear_pending_question' => ['type' => 'boolean', 'enum' => [false]],
        ];
        $properties = [
            'language' => ['type' => 'string', 'enum' => [$language]],
            'intent' => ['type' => 'string', 'enum' => self::INTENTS],
            'safety_level' => ['type' => 'string', 'enum' => self::SAFETY_LEVELS],
            'decision' => ['type' => 'string', 'enum' => self::DECISIONS],
            'primary_product_key' => $this->nullableAllowedStringSchema($candidateKeys),
            'support_product_keys' => $this->allowedStringArraySchema($candidateKeys, self::MAX_SUPPORT_PRODUCTS),
            'fact_ids' => $this->allowedStringArraySchema($factIds, self::MAX_FACT_IDS),
            'acknowledgement' => ['type' => 'string'],
            'explanation' => ['type' => 'string'],
            'clarifying_question' => ['type' => ['string', 'null']],
            'lead_capture' => [
                'type' => 'object',
                'properties' => $leadCaptureProperties,
                'required' => array_keys($leadCaptureProperties),
                'additionalProperties' => false,
            ],
            'state_patch' => [
                'type' => 'object',
                'properties' => $statePatchProperties,
                'required' => array_keys($statePatchProperties),
                'additionalProperties' => false,
            ],
        ];

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => array_keys($properties),
            'additionalProperties' => false,
        ];
    }

    private function nullableAllowedStringSchema(array $allowedValues): array {
        if(!$allowedValues) {
            return ['type' => 'null'];
        }

        return [
            'anyOf' => [
                ['type' => 'string', 'enum' => array_values($allowedValues)],
                ['type' => 'null'],
            ],
        ];
    }

    private function allowedStringArraySchema(array $allowedValues, int $maxItems): array {
        $schema = [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'maxItems' => max(0, $maxItems),
        ];

        if($allowedValues) {
            $schema['items']['enum'] = array_values($allowedValues);
        } else {
            $schema['maxItems'] = 0;
        }

        return $schema;
    }

    private function extractCandidateKeys(array $candidates): array {
        $keys = [];

        foreach($candidates as $candidate) {
            if(is_string($candidate) || is_int($candidate)) {
                $candidateKey = trim((string) $candidate);
            } else {
                $candidate = is_object($candidate) ? (array) $candidate : $candidate;

                if(!is_array($candidate)) {
                    continue;
                }

                $candidateKey = trim((string) (
                    $candidate['product_key']
                    ?? $candidate['key']
                    ?? $candidate['product_id']
                    ?? $candidate['id']
                    ?? ''
                ));
            }

            if($candidateKey !== '') {
                $keys[] = $candidateKey;
            }
        }

        return array_values(array_unique($keys));
    }

    private function extractCandidateFactIds(array $candidates): array {
        $factIds = [];

        foreach($candidates as $candidate) {
            $candidate = is_object($candidate) ? (array) $candidate : $candidate;

            if(!is_array($candidate)) {
                continue;
            }

            foreach(['fact_ids', 'approved_fact_ids', 'facts', 'approved_facts'] as $factContainerKey) {
                $facts = $candidate[$factContainerKey] ?? [];

                if(!is_array($facts)) {
                    continue;
                }

                foreach($facts as $fact) {
                    if(is_string($fact) || is_int($fact)) {
                        $factId = trim((string) $fact);
                    } else {
                        $fact = is_object($fact) ? (array) $fact : $fact;
                        $factId = is_array($fact)
                            ? trim((string) ($fact['fact_id'] ?? $fact['id'] ?? $fact['key'] ?? ''))
                            : '';
                    }

                    if($factId !== '') {
                        $factIds[] = $factId;
                    }
                }
            }
        }

        return array_values(array_unique($factIds));
    }

    private function extractStateProductKeys(array $state): array {
        $keys = [];

        foreach(['mentioned_product_keys', 'rejected_product_keys'] as $stateKey) {
            if(!is_array($state[$stateKey] ?? null)) {
                continue;
            }

            foreach($state[$stateKey] as $productKey) {
                if((is_string($productKey) || is_int($productKey)) && trim((string) $productKey) !== '') {
                    $keys[] = trim((string) $productKey);
                }
            }
        }

        return array_values(array_unique($keys));
    }

    private function validateStructuredData(array $data, array $input, array $candidateKeys, array $factIds): ?string {
        $fieldError = $this->validateExactFields($data, self::OUTPUT_FIELDS, 'output');

        if($fieldError !== null) {
            return $fieldError;
        }

        $expectedLanguage = (string) $input['language'];

        if(!is_string($data['language']) || $data['language'] !== $expectedLanguage) {
            return 'Structured output language must exactly match input.language.';
        }

        if(!is_string($data['intent']) || !in_array($data['intent'], self::INTENTS, true)) {
            return 'Structured output intent is invalid.';
        }

        if(!is_string($data['safety_level']) || !in_array($data['safety_level'], self::SAFETY_LEVELS, true)) {
            return 'Structured output safety_level is invalid.';
        }

        $inputSafetyLevel = $this->extractInputSafetyLevel($input['safety']);

        if($inputSafetyLevel !== null && $data['safety_level'] !== $inputSafetyLevel) {
            return 'Structured output safety_level does not match the server safety tier.';
        }

        if(!is_string($data['decision']) || !in_array($data['decision'], self::DECISIONS, true)) {
            return 'Structured output decision is invalid.';
        }

        if($data['primary_product_key'] !== null && !is_string($data['primary_product_key'])) {
            return 'Structured output primary_product_key must be a string or null.';
        }

        if(is_string($data['primary_product_key']) && !in_array($data['primary_product_key'], $candidateKeys, true)) {
            return 'Structured output selected a primary product outside the candidates.';
        }

        foreach([
            'support_product_keys' => [$candidateKeys, self::MAX_SUPPORT_PRODUCTS],
            'fact_ids' => [$factIds, self::MAX_FACT_IDS],
        ] as $field => $rules) {
            $arrayError = $this->validateAllowedStringList($data[$field], $rules[0], $rules[1], $field);

            if($arrayError !== null) {
                return $arrayError;
            }
        }

        if($data['primary_product_key'] !== null && in_array($data['primary_product_key'], $data['support_product_keys'], true)) {
            return 'Structured output repeats the primary product as a support product.';
        }

        foreach(['acknowledgement', 'explanation'] as $textField) {
            if(!is_string($data[$textField])) {
                return 'Structured output ' . $textField . ' must be a string.';
            }
        }

        if($data['clarifying_question'] !== null && !is_string($data['clarifying_question'])) {
            return 'Structured output clarifying_question must be a string or null.';
        }

        if(!is_array($data['lead_capture'])) {
            return 'Structured output lead_capture must be an object.';
        }

        $leadFields = ['recommended', 'lead_type', 'headline', 'text'];
        $fieldError = $this->validateExactFields($data['lead_capture'], $leadFields, 'lead_capture');

        if($fieldError !== null) {
            return $fieldError;
        }

        if(!is_bool($data['lead_capture']['recommended'])) {
            return 'Structured output lead_capture.recommended must be a boolean.';
        }

        if(!is_string($data['lead_capture']['lead_type']) || !in_array($data['lead_capture']['lead_type'], self::LEAD_TYPES, true)) {
            return 'Structured output lead_capture.lead_type is invalid.';
        }

        foreach(['headline', 'text'] as $leadTextField) {
            if(!is_string($data['lead_capture'][$leadTextField])) {
                return 'Structured output lead_capture.' . $leadTextField . ' must be a string.';
            }
        }

        if(!is_array($data['state_patch'])) {
            return 'Structured output state_patch must be an object.';
        }

        $stateFields = [
            'goal_keys',
            'subject',
            'mentioned_product_keys',
            'rejected_product_keys',
            'pending_question',
            'clear_pending_question',
        ];
        $fieldError = $this->validateExactFields($data['state_patch'], $stateFields, 'state_patch');

        if($fieldError !== null) {
            return $fieldError;
        }

        $goalError = $this->validateAllowedStringList($data['state_patch']['goal_keys'], [], 8, 'state_patch.goal_keys', false);

        if($goalError !== null) {
            return $goalError;
        }

        foreach(['subject', 'pending_question'] as $serverOwnedStateField) {
            if($data['state_patch'][$serverOwnedStateField] !== null) {
                return 'Structured output state_patch.' . $serverOwnedStateField . ' is server-owned and must be null.';
            }
        }

        $knownStateProductKeys = array_values(array_unique(array_merge(
            $candidateKeys,
            $this->extractStateProductKeys($input['state'])
        )));

        foreach(['mentioned_product_keys', 'rejected_product_keys'] as $stateProductField) {
            $stateProductError = $this->validateAllowedStringList(
                $data['state_patch'][$stateProductField],
                $knownStateProductKeys,
                12,
                'state_patch.' . $stateProductField
            );

            if($stateProductError !== null) {
                return $stateProductError;
            }
            if($data['state_patch'][$stateProductField]) {
                return 'Structured output state_patch.' . $stateProductField . ' is server-owned and must be empty.';
            }
        }

        if(!is_bool($data['state_patch']['clear_pending_question'])) {
            return 'Structured output state_patch.clear_pending_question must be a boolean.';
        }
        if($data['state_patch']['goal_keys']) {
            return 'Structured output state_patch.goal_keys is server-owned and must be empty.';
        }
        if($data['state_patch']['clear_pending_question']) {
            return 'Structured output state_patch.clear_pending_question is server-owned and must be false.';
        }

        if($data['decision'] === 'recommend' && $data['primary_product_key'] === null) {
            return 'A recommend decision requires a primary product.';
        }

        if(in_array($data['decision'], ['clarify', 'refer', 'no_product'], true)) {
            if($data['primary_product_key'] !== null || $data['support_product_keys'] || $data['fact_ids']) {
                return 'This decision must not include products or product facts.';
            }
        }

        if($data['decision'] === 'clarify' && trim((string) $data['clarifying_question']) === '') {
            return 'A clarify decision requires one clarifying question.';
        }

        if($data['safety_level'] === 'urgent') {
            if(!in_array($data['decision'], ['refer', 'no_product'], true)) {
                return 'An urgent response must use refer or no_product.';
            }

            if($data['primary_product_key'] !== null || $data['support_product_keys'] || $data['fact_ids']) {
                return 'An urgent response must not include products or product facts.';
            }
        }

        $userFacingTexts = [
            $data['acknowledgement'],
            $data['explanation'],
            (string) ($data['clarifying_question'] ?? ''),
            $data['lead_capture']['headline'],
            $data['lead_capture']['text'],
        ];

        foreach($userFacingTexts as $userFacingText) {
            if(preg_match('~\\b(?:https?://|www\\.)\\S+~iu', $userFacingText)) {
                return 'Structured output must not contain URLs.';
            }
        }

        return null;
    }

    private function validateExactFields(array $value, array $requiredFields, string $label): ?string {
        foreach($requiredFields as $field) {
            if(!array_key_exists($field, $value)) {
                return 'Structured ' . $label . ' is missing field: ' . $field . '.';
            }
        }

        $extraFields = array_diff(array_keys($value), $requiredFields);

        if($extraFields) {
            return 'Structured ' . $label . ' contains unsupported fields.';
        }

        return null;
    }

    private function validateAllowedStringList($value, array $allowedValues, int $maxItems, string $label, bool $enforceAllowed = true): ?string {
        if(!is_array($value) || !$this->isList($value)) {
            return 'Structured output ' . $label . ' must be a list.';
        }

        if(count($value) > $maxItems) {
            return 'Structured output ' . $label . ' contains too many items.';
        }

        foreach($value as $item) {
            if(!is_string($item) || trim($item) === '') {
                return 'Structured output ' . $label . ' must contain non-empty strings.';
            }

            if($enforceAllowed && !in_array($item, $allowedValues, true)) {
                return 'Structured output ' . $label . ' contains a value outside the allowed input set.';
            }
        }

        if(count(array_unique($value, SORT_STRING)) !== count($value)) {
            return 'Structured output ' . $label . ' contains duplicate items.';
        }

        return null;
    }

    private function extractInputSafetyLevel($safety): ?string {
        if(is_string($safety)) {
            $level = mb_strtolower(trim($safety));
        } elseif(is_array($safety)) {
            $level = mb_strtolower(trim((string) ($safety['level'] ?? $safety['safety_level'] ?? $safety['tier'] ?? '')));
        } else {
            return null;
        }

        return in_array($level, self::SAFETY_LEVELS, true) ? $level : null;
    }

    private function extractOutputContent(array $response): array {
        $output = $response['output'] ?? null;

        if(!is_array($output) || !$this->isList($output)) {
            return $this->invalidOutputContent('OpenAI response output must be a list.');
        }

        $messages = [];

        foreach($output as $outputItem) {
            if(!is_array($outputItem)) {
                return $this->invalidOutputContent('OpenAI response output contains an invalid item.');
            }

            $itemType = trim((string) ($outputItem['type'] ?? ''));

            /* Reasoning items may precede the single final assistant message. */
            if($itemType === 'reasoning') {
                continue;
            }

            if($itemType !== 'message') {
                return $this->invalidOutputContent('OpenAI response contains an unsupported output item.');
            }

            $messages[] = $outputItem;
        }

        if(count($messages) !== 1) {
            return $this->invalidOutputContent('OpenAI response must contain exactly one assistant message.');
        }

        $message = $messages[0];
        if((string) ($message['role'] ?? '') !== 'assistant') {
            return $this->invalidOutputContent('OpenAI response message role must be assistant.');
        }

        if((string) ($message['status'] ?? '') !== 'completed') {
            return $this->invalidOutputContent('OpenAI response message status must be completed.');
        }

        $content = $message['content'] ?? null;
        if(!is_array($content) || !$this->isList($content) || count($content) !== 1 || !is_array($content[0])) {
            return $this->invalidOutputContent('OpenAI response message must contain exactly one content item.');
        }

        $contentItem = $content[0];
        $contentType = (string) ($contentItem['type'] ?? '');

        if($contentType === 'refusal') {
            $refusal = trim((string) ($contentItem['refusal'] ?? ''));
            if($refusal === '') {
                return $this->invalidOutputContent('OpenAI response contains an empty refusal.');
            }

            return [
                'text' => '',
                'refusal' => $refusal,
                'error' => '',
            ];
        }

        if($contentType !== 'output_text') {
            return $this->invalidOutputContent('OpenAI response message content must be output_text.');
        }

        $text = trim((string) ($contentItem['text'] ?? ''));
        if($text === '') {
            return $this->invalidOutputContent('OpenAI response contains empty output_text.');
        }

        $aggregateText = is_string($response['output_text'] ?? null)
            ? trim((string) $response['output_text'])
            : '';
        if($aggregateText !== '' && $aggregateText !== $text) {
            return $this->invalidOutputContent('OpenAI response contains ambiguous output_text values.');
        }

        return [
            'text' => $text,
            'refusal' => '',
            'error' => '',
        ];
    }

    private function invalidOutputContent(string $error): array {
        return [
            'text' => '',
            'refusal' => '',
            'error' => $error,
        ];
    }

    private function extractApiError(string $body, int $statusCode, string $apiKey): string {
        $message = '';

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if(is_array($decoded)) {
                $errorPayload = is_array($decoded['error'] ?? null) ? $decoded['error'] : [];
                $message = trim((string) ($errorPayload['message'] ?? $decoded['message'] ?? ''));
            }
        } catch(\Throwable $exception) {
            /* Keep the generic status-based message below. */
        }

        if($message === '') {
            $message = 'OpenAI request failed with HTTP ' . $statusCode . '.';
        }

        return $this->sanitizeError($message, $apiKey);
    }

    private function extractRequestId(array $headers, ?array $response): string {
        foreach($headers as $name => $value) {
            if(mb_strtolower((string) $name) === 'x-request-id') {
                return trim(is_array($value) ? (string) end($value) : (string) $value);
            }
        }

        if($response !== null) {
            return trim((string) ($response['request_id'] ?? $response['_request_id'] ?? ''));
        }

        return '';
    }

    private function supportsReasoningEffort(string $model): bool {
        return (bool) preg_match('/^(?:gpt-[56](?:[.-]|$)|o(?:1|3|4)(?:[.-]|$))/i', $model);
    }

    private function responseModelMatchesRequest(string $requestedModel, string $reportedModel): bool {
        $requestedModel = mb_strtolower(trim($requestedModel));
        $reportedModel = mb_strtolower(trim($reportedModel));
        if($requestedModel === '' || $reportedModel === '') {
            return false;
        }
        if(hash_equals($requestedModel, $reportedModel)) {
            return true;
        }

        /* OpenAI aliases may report the dated snapshot selected behind that
         * exact alias. Accept only that tightly scoped snapshot form; never a
         * different family such as gpt-5.5 or gpt-5-mini. */
        return preg_match(
            '/^' . preg_quote($requestedModel, '/') . '-\d{4}-\d{2}-\d{2}$/D',
            $reportedModel
        ) === 1;
    }

    private function supportsVerbosity(string $model): bool {
        return (bool) preg_match('/^gpt-[56](?:[.-]|$)/i', $model);
    }

    private function isRetryableStatus(int $statusCode): bool {
        return in_array($statusCode, [408, 409, 429], true) || ($statusCode >= 500 && $statusCode <= 599);
    }

    private function waitBeforeRetry(array $response, int $attempt): void {
        $headers = is_array($response['headers'] ?? null) ? $response['headers'] : [];
        $retryAfterSeconds = null;

        foreach($headers as $name => $value) {
            if(mb_strtolower((string) $name) !== 'retry-after') {
                continue;
            }

            $value = is_array($value) ? end($value) : $value;

            if(is_numeric($value)) {
                $retryAfterSeconds = max(0, (float) $value);
            }
        }

        $defaultDelay = max(0, (int) ($this->config['retry_delay_ms'] ?? 250)) * $attempt;
        $maxDelay = max(0, (int) ($this->config['max_retry_delay_ms'] ?? 1000));
        $delayMilliseconds = $retryAfterSeconds !== null
            ? (int) round($retryAfterSeconds * 1000)
            : $defaultDelay;
        $delayMilliseconds = min($maxDelay, $delayMilliseconds);

        if($delayMilliseconds > 0) {
            usleep($delayMilliseconds * 1000);
        }
    }

    private function sendHttpRequest(array $request): array {
        if(!function_exists('curl_init')) {
            return [
                'status' => 0,
                'body' => '',
                'headers' => [],
                'error' => 'The cURL extension is unavailable.',
            ];
        }

        $responseHeaders = [];
        $curlHandle = curl_init((string) $request['url']);

        if($curlHandle === false) {
            return [
                'status' => 0,
                'body' => '',
                'headers' => [],
                'error' => 'The OpenAI HTTP request could not be initialized.',
            ];
        }

        curl_setopt_array($curlHandle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => (int) $request['timeout_seconds'],
            CURLOPT_CONNECTTIMEOUT => (int) $request['connect_timeout_seconds'],
            CURLOPT_HTTPHEADER => (array) $request['headers'],
            CURLOPT_POSTFIELDS => (string) $request['body'],
            CURLOPT_HEADERFUNCTION => static function($curlHandle, string $headerLine) use (&$responseHeaders): int {
                $trimmedLine = trim($headerLine);

                if(str_starts_with($trimmedLine, 'HTTP/')) {
                    $responseHeaders = [];
                    return strlen($headerLine);
                }

                if(str_contains($headerLine, ':')) {
                    [$name, $value] = explode(':', $headerLine, 2);
                    $name = mb_strtolower(trim($name));
                    $value = trim($value);

                    if($name !== '') {
                        $responseHeaders[$name] = $value;
                    }
                }

                return strlen($headerLine);
            },
        ]);

        $requestStartedAt = microtime(true);
        $body = curl_exec($curlHandle);
        $statusCode = (int) curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);
        $error = $body === false ? trim((string) curl_error($curlHandle)) : '';
        curl_close($curlHandle);

        return [
            'status' => $statusCode,
            'body' => $body === false ? '' : (string) $body,
            'headers' => $responseHeaders,
            'error' => $error,
            'duration_ms' => $this->elapsedMilliseconds($requestStartedAt),
        ];
    }

    private function sanitizeError(string $error, string $apiKey): string {
        $error = trim($error);

        if($apiKey !== '') {
            $error = str_replace($apiKey, '[redacted]', $error);
        }

        if(function_exists('mb_substr')) {
            return mb_substr($error, 0, 1000);
        }

        return substr($error, 0, 1000);
    }

    private function elapsedMilliseconds(float $startedAt): int {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    private function isList(array $value): bool {
        $expectedKey = 0;

        foreach($value as $key => $item) {
            if($key !== $expectedKey) {
                return false;
            }

            $expectedKey++;
        }

        return true;
    }

    private function recordSpendResult(array $ticket, array $result): void {
        $recorded = \fcc_ai_spend_finish($ticket, $result);
        $fields = \fcc_ai_usage_metadata($recorded);
        $fields['attempt_usage'] = [array_merge($fields, array_intersect_key($recorded, array_flip(['model', 'provider', 'success', 'reason'])))];
        $this->spendResult = $this->spendResult ? \fcc_ai_combine_attempt_usage($this->spendResult, $fields) : $fields;
    }

    private function result(string $model, array $overrides = []): array {
        return array_replace([
            'success' => false,
            'data' => null,
            'provider' => 'openai',
            'model' => $model,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'api_response_time' => 0,
            'request_id' => '',
            'reason' => 'unknown_error',
            'error' => null,
        ], $overrides, $this->spendResult);
    }
}
