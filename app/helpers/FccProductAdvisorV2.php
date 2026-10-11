<?php

namespace Altum\Helpers;

defined('ALTUMCODE') || die();

/**
 * Framework adapter for the pure FCC Product Advisor V2 decision core.
 *
 * V2 is a strangler: once this adapter accepts a turn it never silently drops
 * into the legacy rule engine. Provider failures receive a grounded local V2
 * response and are recorded for replay.
 */
final class FccProductAdvisorV2 {

    private FccProductCatalogRepository $catalogRepository;
    private array $config;

    public function __construct(?FccProductCatalogRepository $catalogRepository = null, ?array $config = null) {
        $defaults = require APP_PATH . 'config/fcc_ai_product_advisor_v2.php';
        $this->config = array_replace_recursive($defaults, $config ?? []);
        $this->catalogRepository = $catalogRepository ?? new FccProductCatalogRepository();
    }

    public static function isFeatureEnabled(?int $userId = null): bool {
        $config = require APP_PATH . 'config/fcc_ai_product_advisor_v2.php';
        $feature = is_array($config['feature'] ?? null) ? $config['feature'] : [];
        $forceV1EnvironmentName = trim((string) ($feature['force_v1_env'] ?? 'FCC_PRODUCT_ADVISOR_V2_FORCE_V1'));

        if($forceV1EnvironmentName !== '' && self::environmentFlag($forceV1EnvironmentName) === true) {
            return false;
        }

        $explicit = self::environmentFlag('FCC_PRODUCT_ADVISOR_V2');
        if($explicit !== null) {
            return $explicit;
        }

        $mode = mb_strtolower(trim((string) ($feature['mode'] ?? 'off')));
        if($mode === 'local') {
            return self::isTrustedLocalEnvironment();
        }

        $pilotUserIds = array_values(array_filter(array_map('intval', (array) ($feature['pilot_user_ids'] ?? []))));
        if($userId && in_array($userId, $pilotUserIds, true)) {
            return true;
        }

        if($mode === 'on' || $mode === 'rollout') {
            $rolloutPercent = max(0, min(100, (int) ($feature['rollout_percent'] ?? 0)));
            if($rolloutPercent <= 0 || !$userId) {
                return false;
            }
            if($rolloutPercent >= 100) {
                return true;
            }

            /* Stable collaborator cohort: the same FCC app never jumps between engines. */
            $bucket = (int) sprintf('%u', crc32('fcc-product-advisor-v2:' . $userId)) % 10000;
            return $bucket < ($rolloutPercent * 100);
        }

        return false;
    }

    public function isReady(?int $userId = null): bool {
        if(!self::isFeatureEnabled($userId) || !$this->catalogRepository->schemaIsReady()) {
            return false;
        }

        [$revisionId, $revisionKey, $bundleHash] = $this->configuredRevisionSelector();
        return $this->catalogRepository->getRuntimeRevision($revisionId, $revisionKey, $bundleHash) !== null;
    }

    public function supportsScope(string $languageCode, string $marketCode): bool {
        $languageCode = mb_strtolower(trim($languageCode));
        $marketCode = mb_strtolower(trim($marketCode));
        if($languageCode === '' || $marketCode === '') {
            return false;
        }

        $feature = is_array($this->config['feature'] ?? null) ? $this->config['feature'] : [];
        $certified = array_fill_keys(array_map(
            static fn($scope): string => mb_strtolower(trim((string) $scope)),
            (array) ($feature['certified_scopes'] ?? [])
        ), true);
        return isset($certified[$languageCode . ':' . $marketCode]);
    }

    public function getStatus(?int $userId = null): array {
        $schemaReady = false;
        $revision = null;
        $feature = is_array($this->config['feature'] ?? null) ? $this->config['feature'] : [];
        $forceV1EnvironmentName = trim((string) ($feature['force_v1_env'] ?? 'FCC_PRODUCT_ADVISOR_V2_FORCE_V1'));
        $forcedToV1 = $forceV1EnvironmentName !== '' && self::environmentFlag($forceV1EnvironmentName) === true;
        $explicit = self::environmentFlag('FCC_PRODUCT_ADVISOR_V2');
        $mode = mb_strtolower(trim((string) ($feature['mode'] ?? 'off')));
        $deploymentConfigured = !$forcedToV1 && (
            $explicit === true
            || ($explicit === null && in_array($mode, ['local', 'on', 'rollout'], true))
        );
        if($mode === 'local' && $explicit === null) {
            $deploymentConfigured = $deploymentConfigured && self::isTrustedLocalEnvironment();
        }
        $requestEligible = $userId !== null ? self::isFeatureEnabled($userId) : null;

        try {
            $schemaReady = $this->catalogRepository->schemaIsReady();
            [$revisionId, $revisionKey, $bundleHash] = $this->configuredRevisionSelector();
            $revision = $schemaReady
                ? $this->catalogRepository->getRuntimeRevision($revisionId, $revisionKey, $bundleHash)
                : null;
        } catch(\Throwable $exception) {
            $schemaReady = false;
        }

        $deploymentReady = $deploymentConfigured && $schemaReady && $revision !== null;

        return [
            /* Backward-compatible deployment status; cohort eligibility is a
             * separate, request-scoped value so public monitoring is not
             * accidentally bucketed as an anonymous collaborator. */
            'feature_enabled' => $deploymentConfigured,
            'request_eligible' => $requestEligible,
            'schema_ready' => $schemaReady,
            'published_revision_id' => (int) ($revision['revision_id'] ?? 0),
            'published_revision_key' => (string) ($revision['revision_key'] ?? ''),
            'published_bundle_hash' => (string) ($revision['bundle_hash'] ?? ''),
            'configured_revision_id' => (int) ($revisionId ?? 0),
            'configured_revision_key' => (string) ($revisionKey ?? ''),
            'configured_bundle_hash' => (string) ($bundleHash ?? ''),
            'certified_scopes' => array_values((array) ($this->config['feature']['certified_scopes'] ?? [])),
            'deployment_ready' => $deploymentReady,
            'ready' => $deploymentReady && ($requestEligible === null || $requestEligible),
            'engine' => 'fcc_product_advisor_v2',
            /* Safe operational identity only; credentials and endpoint details
             * are never exposed by the public readiness response. */
            'provider' => (string) ($this->config['provider'] ?? ''),
            'model' => (string) ($this->config['model'] ?? ''),
            'reasoning_effort' => (string) ($this->config['reasoning_effort'] ?? ''),
            'verbosity' => (string) ($this->config['verbosity'] ?? ''),
        ];
    }

    public function handle(
        array $payload,
        string $message,
        array $conversationState,
        array $context,
        ?object $assistant = null
    ): array {
        $startedAt = microtime(true);
        $conversation = $context['conversation'] ?? null;
        $user = $context['user'] ?? null;
        $link = $context['link'] ?? null;

        if(!$conversation || !$user) {
            throw new \RuntimeException(fcc_t('V2 savjetnik nema valjan kontekst razgovora.'));
        }

        if((string) ($conversation->assistant_type ?? '') !== 'product_advisor') {
            throw new \RuntimeException(fcc_t('V2 savjetnik obrađuje samo preporuke Forever proizvoda.'));
        }

        [$configuredRevisionId, $configuredRevisionKey, $configuredBundleHash] = $this->configuredRevisionSelector();
        $runtimeRevision = $this->catalogRepository->getRuntimeRevision(
            $configuredRevisionId,
            $configuredRevisionKey,
            $configuredBundleHash
        );
        if(!$this->isReady((int) ($user->user_id ?? 0)) || !$runtimeRevision) {
            throw new \RuntimeException(fcc_t('V2 katalog preporuka još nije spreman za lokalno testiranje.'));
        }

        $conversationId = (int) ($conversation->fcc_ai_conversation_id ?? 0);
        $visitorKey = trim((string) ($conversation->visitor_key ?? $payload['visitor_key'] ?? ''));
        $history = \fcc_ai_get_conversation_messages($conversationId, 20);
        $advisorState = $this->extractAdvisorState($history, (string) ($conversationState['language'] ?? 'hr'));
        $language = \fcc_ai_v2_detect_language($message, (string) ($advisorState['language'] ?? 'hr'));
        $serverMarketCode = $this->resolveMarketCode($payload, $conversation);
        $stateMarketSource = (string) ($advisorState['market_source'] ?? '');
        if($serverMarketCode !== '' && $stateMarketSource !== 'message') {
            $advisorState['market'] = $serverMarketCode;
            $advisorState['market_source'] = 'server_request';
        }
        $baseMarketCode = $stateMarketSource === 'message'
            ? (string) ($advisorState['market'] ?? '')
            : ($serverMarketCode !== '' ? $serverMarketCode : (string) ($advisorState['market'] ?? ''));
        $marketCode = mb_strtolower(\fcc_ai_v2_detect_market(
            $message,
            $baseMarketCode
        ));
        if(!$this->supportsScope($language, $marketCode)) {
            throw new \RuntimeException(fcc_t('V2 savjetnik nema certificiran katalog za odabrani jezik i tržište.'));
        }
        $catalog = $this->catalogRepository->getCatalog(
            $language,
            $marketCode,
            (int) $runtimeRevision['revision_id'],
            (string) $runtimeRevision['revision_key'],
            $configuredBundleHash
        );

        if(!$catalog) {
            throw new \RuntimeException(fcc_t('Objavljeni V2 katalog nema proizvode za obradu.'));
        }
        $catalog = $this->attachAdvisorContext($catalog);

        $plan = \fcc_ai_v2_plan_turn([
            'language' => $language,
            'market' => $marketCode,
            'message' => $message,
            'history' => $this->historyForModel($history),
            'source_context' => $this->sourceContext($payload),
        ], $advisorState, $catalog);
        $providerInput = $this->buildProviderInput($payload, $message, $history, $plan, $catalog, $marketCode);

        $userMessageId = \fcc_ai_log_message($conversationId, 'user', $message, [
            'message_type' => 'chat',
            'model' => 'fcc-user-message',
            'engine' => 'fcc_product_advisor_v2',
            'query_hash' => hash('sha256', $message),
        ]);
        if(!$userMessageId) {
            throw new \RuntimeException(fcc_t('V2 user message could not be persisted.'));
        }

        $providerAttempt = [
            'success' => false,
            'provider' => 'local_v2',
            'model' => 'fcc-v2-deterministic',
            'reason' => 'deterministic_action',
            'data' => null,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'api_response_time' => 0,
            'request_id' => '',
            'error' => null,
        ];

        if(!empty($plan['trace']['provider_required'])) {
            $model = trim((string) ($this->config['model'] ?? ''));
            if($model === '') {
                $model = \fcc_ai_resolve_assistant_model($assistant);
            }
            $client = new FccProductAdvisorClientV2(
                \fcc_ai_get_openai_api_key(),
                is_array($this->config) ? $this->config : null
            );
            $providerAttempt = $client->generate(
                $model,
                $providerInput,
                $this->providerUserIdentifier((int) ($user->user_id ?? 0), $visitorKey),
                (int) ($user->user_id ?? 0)
            );
        }

        $providerCandidate = !empty($providerAttempt['success']) && is_array($providerAttempt['data'] ?? null)
            ? $providerAttempt['data']
            : [];
        $final = \fcc_ai_v2_finalize_turn($plan, $providerCandidate, $catalog);
        $final = $this->applyPartnerOverlay($final, $catalog, $user, $link, $marketCode, $plan);
        $providerAttemptSuccess = !empty($providerAttempt['success']);
        $providerOutputAccepted = $providerAttemptSuccess && empty($final['validation']['fallback_used']);
        $finalAction = (string) ($final['decision']['action'] ?? '');
        $providerFieldsUsed = $providerOutputAccepted && (
            in_array($finalAction, ['answer', 'factual_lookup', 'content_creation'], true)
            || (
                $finalAction === 'clarify'
                && (
                    trim((string) ($providerCandidate['acknowledgement'] ?? '')) !== ''
                    || trim((string) ($providerCandidate['clarifying_question'] ?? '')) !== ''
                )
            )
        );
        $legacyIntent = \fcc_ai_detect_public_intent('product_advisor', $message);
        $leadOffer = $this->buildServerLeadOffer(
            $conversation,
            $user,
            $plan,
            $final,
            $legacyIntent,
            (string) $final['language']
        );
        /* Provider lead_capture is intentionally discarded. It is neither
         * rendered nor persisted; only the server-owned offer above may reach
         * the browser or the contact endpoint. */
        $providerCandidateForTelemetry = $providerCandidate;
        unset($providerCandidateForTelemetry['lead_capture']);

        $knowledgeSuggestions = $final['knowledge_suggestions'] ?? [];
        $providerWasRequired = !empty($plan['trace']['provider_required']);
        $providerName = $providerWasRequired
            ? (string) ($providerAttempt['provider'] ?? 'openai')
            : 'local_v2';
        $modelName = $providerWasRequired
            ? (string) ($providerAttempt['model'] ?? '')
            : 'fcc-v2-deterministic';
        $fallbackReason = '';
        if($providerWasRequired && !$providerAttemptSuccess) {
            $fallbackReason = (string) ($providerAttempt['reason'] ?? 'provider_unavailable');
        } elseif($providerWasRequired && !$providerOutputAccepted) {
            $fallbackReason = 'provider_output_rejected';
        }
        $selectedRelationships = array_values((array) ($final['recommendation_payload']['support_relationships'] ?? []));
        foreach((array) ($final['recommendation_payload']['program_members'] ?? []) as $programMember) {
            if(!is_array($programMember) || (int) ($programMember['relation_id'] ?? 0) <= 0) {
                continue;
            }
            $selectedRelationships[] = [
                'relation_id' => (int) ($programMember['relation_id'] ?? 0),
                'relation_type' => 'program_member',
                'source_product_key' => (string) ($programMember['source_product_key'] ?? $final['primary_product_key'] ?? ''),
                'target_product_key' => (string) ($programMember['target_product_key'] ?? $programMember['product_key'] ?? ''),
                'sequence_order' => max(0, (int) ($programMember['sequence_order'] ?? 0)),
            ];
        }
        $selectedRelationships = array_values(array_reduce(
            $selectedRelationships,
            static function(array $carry, array $relationship): array {
                $identity = (string) ($relationship['relation_type'] ?? '') . ':' . (int) ($relationship['relation_id'] ?? 0);
                if($identity !== ':0') {
                    $carry[$identity] = $relationship;
                }
                return $carry;
            },
            []
        ));
        $replyMeta = [
            'engine' => 'fcc_product_advisor_v2',
            'engine_version' => '2.0.0-local',
            'provider' => $providerName,
            'model' => $modelName,
            'provider_attempt_success' => $providerAttemptSuccess,
            'provider_schema_valid' => $providerAttemptSuccess,
            'provider_output_accepted' => $providerOutputAccepted,
            'provider_fields_used' => $providerFieldsUsed,
            'provider_success' => $providerOutputAccepted,
            'fallback_reason' => $fallbackReason,
            'provider_error_code' => $fallbackReason === '' ? '' : $this->safeErrorCode($fallbackReason),
            'input_tokens' => (int) ($providerAttempt['input_tokens'] ?? 0),
            'output_tokens' => (int) ($providerAttempt['output_tokens'] ?? 0),
            'total_tokens' => (int) ($providerAttempt['total_tokens'] ?? 0),
            'api_response_time' => (int) ($providerAttempt['api_response_time'] ?? 0),
            'request_id' => (string) ($providerAttempt['request_id'] ?? ''),
            'language' => (string) $final['language'],
            'market_code' => $marketCode,
            'market_source' => (string) ($final['state']['market_source'] ?? $plan['state']['market_source'] ?? 'unknown'),
            'catalog_revision_id' => (int) ($catalog[0]['revision_id'] ?? 0),
            'catalog_revision_key' => (string) ($runtimeRevision['revision_key'] ?? ''),
            'catalog_source_hash' => (string) ($runtimeRevision['source_hash'] ?? ''),
            'prompt_version' => (string) ($this->config['prompt_version'] ?? ''),
            'schema_version' => (string) ($this->config['schema_version'] ?? $this->config['schema_name'] ?? ''),
            'decision' => (string) ($final['decision']['action'] ?? ''),
            'confidence' => (float) ($plan['decision']['confidence'] ?? 0),
            'reason_codes' => array_values((array) ($plan['decision']['reason_codes'] ?? [])),
            'safety_level' => (string) ($plan['safety']['level'] ?? 'normal'),
            'candidate_product_keys' => array_values((array) ($plan['allowed_product_keys'] ?? [])),
            'selected_product_keys' => array_values((array) ($final['selected_product_keys'] ?? [])),
            'selected_relationships' => $selectedRelationships,
            'rejected_product_keys' => array_values((array) ($plan['rejected_product_keys'] ?? [])),
            'validation' => $final['validation'] ?? [],
            'advisor_state' => $final['state'] ?? [],
            'knowledge_suggestions' => $knowledgeSuggestions,
            'lead_capture_recommended' => (bool) ($leadOffer['recommended'] ?? false),
            'lead_offer_state' => $leadOffer ? 'active' : 'revoked',
            'lead_offer_revoked' => !$leadOffer,
            'reply_to_user_message_id' => (int) $userMessageId,
        ];
        $replyMeta = array_merge($replyMeta, \fcc_ai_usage_metadata($providerAttempt));
        $replyMeta['usage'] = \fcc_ai_usage_metadata($providerAttempt);

        if($leadOffer) {
            $replyMeta['lead_offer'] = $leadOffer;
        }

        $productCard = $this->buildProductCardSnapshot($final, $plan, $replyMeta);
        if($productCard) {
            $replyMeta['product_card'] = $productCard;
        }

        $assistantMessageId = \fcc_ai_log_message($conversationId, 'assistant', (string) $final['content'], array_merge($replyMeta, [
            'message_type' => 'chat',
        ]));
        if(!$assistantMessageId) {
            throw new \RuntimeException(fcc_t('V2 assistant reply could not be persisted.'));
        }

        /* The conversation owns exactly one current offer. Every V2 response,
         * including a medical/urgent/non-contact response, advances this state
         * so an older form cannot remain capturable. */
        $leadOfferState = \fcc_ai_set_public_lead_offer_state($conversationId, $assistantMessageId, $leadOffer);
        if(
            (int) ($leadOfferState['server_message_id'] ?? 0) !== $assistantMessageId
            || ($leadOffer && (string) ($leadOfferState['state'] ?? '') !== 'active')
        ) {
            /* A concurrent newer V2 turn already superseded this response.
             * Its late HTTP response must fail closed instead of repainting an
             * older form in the browser. */
            $leadOffer = [];
            unset($replyMeta['lead_offer']);
            $replyMeta['lead_capture_recommended'] = false;
            $replyMeta['lead_offer_state'] = 'revoked';
            $replyMeta['lead_offer_revoked'] = true;
        }

        $durationMs = max(0, (int) round((microtime(true) - $startedAt) * 1000));
        $this->logRun(
            $conversation,
            $user,
            $assistantMessageId,
            $userMessageId,
            $message,
            $plan,
            $final,
            $replyMeta,
            $providerCandidateForTelemetry,
            $durationMs,
            $catalog
        );

        \fcc_ai_increment_daily_stats((int) $user->user_id, 'product_advisor', (string) ($conversation->scope ?? 'public_app'), [
            'user_messages' => 1,
            'assistant_messages' => 1,
        ], [
            'event_type' => 'message_exchange_v2',
        ]);
        \fcc_ai_log_event([
            'fcc_ai_conversation_id' => $conversationId,
            'user_id' => (int) $user->user_id,
            'assistant_type' => 'product_advisor',
            'event_type' => 'message_exchange_v2',
            'link_id' => (int) ($link->link_id ?? $conversation->link_id ?? 0),
            'blog_post_id' => (int) ($conversation->blog_post_id ?? 0),
            'meta' => [
                'reply_language' => (string) $final['language'],
                'market_code' => $marketCode,
                'decision' => (string) ($final['decision']['action'] ?? ''),
                'safety_level' => (string) ($plan['safety']['level'] ?? 'normal'),
                'provider' => (string) $replyMeta['provider'],
                'model' => (string) $replyMeta['model'],
                'provider_attempt_success' => (bool) $replyMeta['provider_attempt_success'],
                'provider_schema_valid' => (bool) $replyMeta['provider_schema_valid'],
                'provider_output_accepted' => (bool) $replyMeta['provider_output_accepted'],
                'provider_fields_used' => (bool) $replyMeta['provider_fields_used'],
                'provider_success' => (bool) $replyMeta['provider_success'],
                'fallback_reason' => (string) $replyMeta['fallback_reason'],
                'provider_error_code' => (string) $replyMeta['provider_error_code'],
                'catalog_revision_id' => (int) $replyMeta['catalog_revision_id'],
                'catalog_revision_key' => (string) $replyMeta['catalog_revision_key'],
                'catalog_source_hash' => (string) $replyMeta['catalog_source_hash'],
                'selected_product_keys' => $replyMeta['selected_product_keys'],
                'selected_relationships' => $replyMeta['selected_relationships'],
                'validation_valid' => (bool) ($final['validation']['valid'] ?? false),
            ],
        ]);

        try {
            \fcc_ai_refresh_conversation_insight($conversationId);
        } catch(\Throwable $exception) {
            /* Insights are non-critical and must not invalidate an answer. */
        }

        $reply = [
            'content' => (string) $final['content'],
            'language' => (string) $final['language'],
            'lead_offer' => $leadOffer,
            'intent' => [
                'v2_intent' => (string) ($plan['intent'] ?? 'advice'),
                'safety_level' => (string) ($plan['safety']['level'] ?? 'normal'),
            ],
            'recommendation_payload' => $final['recommendation_payload'] ?? [],
            'knowledge_suggestions' => $knowledgeSuggestions,
            'meta' => $replyMeta,
        ];

        return [
            'conversation_id' => $conversationId,
            'conversation_public_id' => (string) ($conversation->public_id ?? ''),
            'assistant_type' => 'product_advisor',
            'scope' => (string) ($conversation->scope ?? 'public_app'),
            'link_id' => (int) ($link->link_id ?? $conversation->link_id ?? 0),
            'blog_post_id' => (int) ($conversation->blog_post_id ?? 0),
            'owner_name' => (string) ($user->name ?? ''),
            'language' => (string) $final['language'],
            'reply' => $reply,
            'messages' => \fcc_ai_get_conversation_messages(
                $conversationId,
                80,
                \fcc_ai_build_feedback_actor('visitor', $visitorKey)
            ),
            'lead_status' => (string) ($conversation->lead_status ?? 'none'),
            'advisor_v2' => true,
        ];
    }

    private function extractAdvisorState(array $history, string $fallbackLanguage): array {
        for($index = count($history) - 1; $index >= 0; $index--) {
            $message = $history[$index] ?? [];
            $meta = is_array($message['meta'] ?? null) ? $message['meta'] : [];
            if(($meta['engine'] ?? '') === 'fcc_product_advisor_v2' && is_array($meta['advisor_state'] ?? null)) {
                return $meta['advisor_state'];
            }
        }

        return [
            'language' => in_array($fallbackLanguage, ['hr', 'en', 'sl', 'bg', 'de', 'sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'], true) ? $fallbackLanguage : 'hr',
            'rejected_product_keys' => [],
            'completed_product_keys' => [],
            'turn_count' => 0,
        ];
    }

    private function historyForModel(array $history): array {
        $result = [];
        foreach(array_slice($history, -12) as $message) {
            $role = (string) ($message['role'] ?? 'assistant');
            if(!in_array($role, ['user', 'assistant'], true)) {
                continue;
            }
            $content = mb_substr(trim((string) ($message['content'] ?? '')), 0, 1600);
            if($content === '') {
                continue;
            }
            $safety = \fcc_ai_v2_safety_assessment($content);
            if(($safety['level'] ?? 'routine') !== 'routine') {
                continue;
            }
            $result[] = ['role' => $role, 'content' => $content];
        }

        return $result;
    }

    private function buildProviderInput(array $payload, string $message, array $history, array $plan, array $catalog, string $marketCode): array {
        $catalogByKey = \fcc_ai_v2_catalog_rows($catalog);
        $factIndex = \fcc_ai_v2_fact_index($catalog);
        $candidates = [];

        foreach((array) ($plan['candidates'] ?? []) as $candidatePlan) {
            $productKey = (string) ($candidatePlan['product_key'] ?? '');
            $row = $catalogByKey[$productKey] ?? null;
            if(!$row) {
                continue;
            }
            $facts = [];
            foreach($factIndex as $fact) {
                if(($fact['product_key'] ?? '') === $productKey) {
                    $facts[] = $fact;
                }
            }
            $candidates[] = [
                'product_key' => $productKey,
                'canonical_name' => (string) $row['canonical_name'],
                'category_key' => (string) ($row['category_key'] ?? ''),
                'route' => (string) ($row['route'] ?? 'unknown'),
                'advisor_status' => (string) ($row['advisor_status'] ?? 'lookup_only'),
                'availability_status' => (string) ($row['availability_status'] ?? 'unknown'),
                'use_cases' => array_values((array) ($row['use_cases'] ?? [])),
                'facts' => $facts,
                'allergens_contains' => array_values((array) ($row['allergens_contains'] ?? [])),
                'safety_flags' => array_values((array) ($row['safety_flags'] ?? [])),
                'mandatory_safety_notes' => array_values((array) ($row['mandatory_safety_notes'] ?? [])),
                'safety_review_status' => (string) ($row['safety_review_status'] ?? 'unknown'),
                'suitability_flags' => array_values((array) ($row['suitability_flags'] ?? [])),
                'suitability_review_status' => (string) ($row['suitability_review_status'] ?? 'unknown'),
                'score' => (int) ($candidatePlan['score'] ?? 0),
                'reason_codes' => array_values((array) ($candidatePlan['reason_codes'] ?? [])),
                'candidate_role' => (string) ($candidatePlan['candidate_role'] ?? 'primary'),
                'relationship_ids' => array_values((array) ($candidatePlan['relationship_ids'] ?? [])),
            ];
        }

        $safetyLevel = (string) ($plan['safety']['level'] ?? 'normal');
        $providerSafetyLevel = in_array($safetyLevel, ['urgent', 'emergency'], true)
            ? 'urgent'
            : ($safetyLevel === 'routine' ? 'normal' : 'caution');

        return [
            'language' => (string) ($plan['language'] ?? 'hr'),
            'market' => $marketCode,
            'message' => $message,
            'history' => $this->historyForModel($history),
            'state' => is_array($plan['state'] ?? null) ? $plan['state'] : [],
            'safety' => [
                'level' => $providerSafetyLevel,
                'server_level' => $safetyLevel,
                'allow_products' => (bool) ($plan['safety']['allow_products'] ?? false),
                'reason_codes' => array_values((array) ($plan['safety']['reason_codes'] ?? [])),
            ],
            'candidates' => $candidates,
            'support_relationships' => array_values((array) ($plan['support_relationships'] ?? [])),
            'source_context' => $this->sourceContext($payload),
        ];
    }

    private function sourceContext(array $payload): array {
        return [
            'type' => mb_substr(trim((string) ($payload['source_context'] ?? '')), 0, 255),
            'page_title' => mb_substr(trim((string) ($payload['source_page_title'] ?? '')), 0, 255),
            'page_section' => mb_substr(trim((string) ($payload['source_page_section'] ?? '')), 0, 128),
            'page_slug' => mb_substr(trim((string) ($payload['source_page_slug'] ?? '')), 0, 128),
        ];
    }

    private function resolveMarketCode(array $payload, object $conversation): string {
        $marketCode = mb_strtolower(trim((string) ($payload['market_code'] ?? '')));
        if($marketCode !== '') {
            return mb_substr($marketCode, 0, 2);
        }

        $rawMeta = $conversation->meta ?? null;
        if(is_array($rawMeta)) {
            $conversationMeta = $rawMeta;
        } elseif(is_object($rawMeta)) {
            $conversationMeta = (array) $rawMeta;
        } elseif(is_string($rawMeta)) {
            $conversationMeta = json_decode($rawMeta, true) ?: [];
        } else {
            $conversationMeta = [];
        }

        return mb_substr(mb_strtolower(trim((string) ($conversationMeta['market_code'] ?? ''))), 0, 2);
    }

    private function buildServerLeadOffer(
        object $conversation,
        object $user,
        array $plan,
        array $final,
        array $legacyIntent,
        string $language
    ): array {
        if((string) ($conversation->lead_status ?? 'none') === 'captured') {
            return [];
        }

        $safetyLevel = (string) ($plan['safety']['level'] ?? 'routine');
        $action = (string) ($final['decision']['action'] ?? '');
        $serverBusiness = (string) ($plan['intent'] ?? '') === 'business' || $action === 'business_guidance';
        if(
            $safetyLevel !== 'routine'
            || in_array($action, ['urgent_triage', 'doctor_first'], true)
            || (!$serverBusiness && (
                !empty($legacyIntent['medical_sensitive'])
                || !empty($legacyIntent['serious'])
                || !empty($legacyIntent['special_population_sensitive'])
            ))
        ) {
            return [];
        }

        $business = $serverBusiness
            || !empty($legacyIntent['business'])
            || !empty($legacyIntent['business_primary'])
            || !empty($legacyIntent['business_content_request']);
        $contact = !empty($legacyIntent['contact']);

        if(!$business && !$contact) {
            return [];
        }

        $leadType = $business
            ? (!empty($legacyIntent['explicit_product_request']) ? 'mixed_interest' : 'business_interest')
            : 'product_interest';

        return \fcc_ai_build_public_lead_offer($leadType, $language, (string) ($user->name ?? ''));
    }

    /** @return array{0:?int,1:?string,2:?string} */
    private function configuredRevisionSelector(): array {
        $feature = is_array($this->config['feature'] ?? null) ? $this->config['feature'] : [];
        $revisionId = (int) ($feature['catalog_revision_id'] ?? 0);
        $revisionKey = trim((string) ($feature['catalog_revision_key'] ?? ''));
        $bundleHash = mb_strtolower(trim((string) ($feature['catalog_bundle_hash'] ?? '')));

        return [
            $revisionId > 0 ? $revisionId : null,
            $revisionKey !== '' ? $revisionKey : null,
            $bundleHash !== '' ? $bundleHash : null,
        ];
    }

    /**
     * Build the only product-card shape that may be persisted and rendered.
     *
     * The URL is created by applyPartnerOverlay() from catalog and collaborator
     * data. No request/client field participates in this snapshot.
     */
    private function buildProductCardSnapshot(array $final, array $plan, array $replyMeta): array {
        $payload = is_array($final['recommendation_payload'] ?? null)
            ? $final['recommendation_payload']
            : [];

        if(
            ($payload['v2'] ?? null) !== true
            || ($payload['recommendation_eligible'] ?? null) !== true
            || (string) ($payload['advisor_status'] ?? '') !== 'recommendable'
            || ($payload['shop_available'] ?? null) !== true
            || !in_array((string) ($plan['intent'] ?? ''), ['recommendation', 'advice', 'followup_confirmation'], true)
            || (string) ($final['decision']['action'] ?? '') !== 'answer'
            || (string) ($plan['safety']['level'] ?? '') !== 'routine'
            || empty($plan['safety']['allow_products'])
            || empty($final['validation']['valid'])
        ) {
            return [];
        }

        $productKey = trim((string) ($payload['primary_product_key'] ?? ''));
        $productName = trim((string) ($payload['primary_product'] ?? ''));
        $marketCode = mb_strtolower(trim((string) ($payload['market_code'] ?? $replyMeta['market_code'] ?? '')));
        $revisionId = (int) ($replyMeta['catalog_revision_id'] ?? 0);
        $revisionKey = trim((string) ($replyMeta['catalog_revision_key'] ?? ''));
        $officialUrl = trim((string) ($payload['official_url'] ?? ''));

        if(
            preg_match('/^[a-z0-9][a-z0-9-]{0,127}$/', $productKey) !== 1
            || $productName === ''
            || mb_strlen($productName) > 160
            || preg_match('/^[a-z]{2}$/', $marketCode) !== 1
            || $revisionId <= 0
            || $revisionKey === ''
            || mb_strlen($revisionKey) > 128
            || !$this->isTrustedForeverShopUrl($officialUrl, $marketCode)
        ) {
            return [];
        }

        return [
            'v2' => true,
            'recommendation_eligible' => true,
            'advisor_status' => 'recommendable',
            'shop_available' => true,
            'product_key' => $productKey,
            'product_name' => $productName,
            'market_code' => $marketCode,
            'catalog_revision_id' => $revisionId,
            'catalog_revision_key' => $revisionKey,
            'official_url' => $officialUrl,
        ];
    }

    private function isTrustedForeverShopUrl(string $url, string $marketCode): bool {
        if($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        if(!is_array($parts)) {
            return false;
        }

        $scheme = mb_strtolower(trim((string) ($parts['scheme'] ?? '')));
        $host = mb_strtolower(rtrim(trim((string) ($parts['host'] ?? '')), '.'));
        $trustedHost = $host === 'foreverliving.com' || str_ends_with($host, '.foreverliving.com');
        $marketProductPaths = [
            'hr' => '/shop/hrv/hr-hr/products/',
        ];
        $expectedPath = $marketProductPaths[$marketCode] ?? '';
        $path = rawurldecode((string) ($parts['path'] ?? ''));
        $hasTraversalSegment = preg_match('~(?:^|/)\.\.?($|/)~', $path) === 1;
        $hasResidualEncodedSeparator = preg_match('/%(?:2e|2f|5c)/i', $path) === 1 || str_contains($path, '\\');

        return $scheme === 'https'
            && $trustedHost
            && empty($parts['user'])
            && empty($parts['pass'])
            && empty($parts['port'])
            && $expectedPath !== ''
            && str_starts_with(mb_strtolower($path), $expectedPath)
            && !$hasTraversalSegment
            && !$hasResidualEncodedSeparator;
    }

    private function applyPartnerOverlay(
        array $final,
        array $catalog,
        object $user,
        ?object $link,
        string $marketCode,
        array $plan = []
    ): array {
        $selectedKeys = array_values((array) ($final['selected_product_keys'] ?? []));
        $primaryKey = (string) ($selectedKeys[0] ?? '');
        $rows = \fcc_ai_v2_catalog_rows($catalog);
        $primary = $rows[$primaryKey] ?? null;
        $officialUrl = '';

        $decisionReasonCodes = array_values(array_filter(array_map(
            static fn($reasonCode): string => trim((string) $reasonCode),
            (array) ($final['decision']['reason_codes'] ?? [])
        )));
        $hasHealthClaimBoundary = in_array('neutral_product_fact', $decisionReasonCodes, true)
            || (bool) array_filter(
                $decisionReasonCodes,
                static fn(string $reasonCode): bool => str_starts_with($reasonCode, 'health_claim_')
            );
        $planAllowsCommercialRecommendation = !$plan || (
            (string) ($plan['safety']['level'] ?? '') === 'routine'
            && !empty($plan['safety']['allow_products'])
            && in_array((string) ($plan['intent'] ?? ''), ['recommendation', 'advice', 'followup_confirmation'], true)
        );
        $recommendationEligible = !empty($final['recommendation_payload']['recommendation_eligible'])
            && (string) ($final['decision']['action'] ?? '') === 'answer'
            && !empty($final['validation']['valid'])
            && !$hasHealthClaimBoundary
            && $planAllowsCommercialRecommendation;
        if(
            $recommendationEligible
            && $primary
            && (string) ($primary['advisor_status'] ?? 'lookup_only') === 'recommendable'
            && $marketCode !== ''
            && ($primary['availability_status'] ?? '') === 'available'
        ) {
            $baseUrl = trim((string) ($primary['official_url'] ?? ''));
            $preferences = $user->preferences ?? null;
            if(is_string($preferences)) {
                $preferences = json_decode($preferences);
            }
            $meta = is_object($preferences) ? ($preferences->meta ?? null) : null;
            $foreverId = is_object($meta)
                ? \forever_business_normalize_fbo_id($meta->foreverId ?? '')
                : '';
            $discountParams = \Altum\Link::get_main_biolink_discount_query_params(
                (int) ($user->user_id ?? 0),
                $marketCode
            );
            $builtUrl = $baseUrl !== '' && $foreverId !== ''
                ? \Altum\Link::build_forever_destination_url($baseUrl, $foreverId, $marketCode, $discountParams)
                : false;
            $officialUrl = is_string($builtUrl) ? $builtUrl : '';
        }

        if(is_array($final['recommendation_payload'] ?? null)) {
            $final['recommendation_payload']['market_code'] = $marketCode;
            $final['recommendation_payload']['official_url'] = $officialUrl;
            $final['recommendation_payload']['shop_available'] = $officialUrl !== '';
        }

        /* Diagnosis conversations keep the normal recommendation gate closed,
         * but an evidence-reviewed profile may still expose server-owned links
         * to clearly labelled supportive wellness options. Provider output and
         * request fields cannot add products to this list. */
        $supportiveProductLinks = [];
        $supportiveProfile = \fcc_ai_v2_supportive_wellness_profile(
            (array) ($plan['safety']['reason_codes'] ?? [])
        );
        $approvedSupportiveOptions = [];
        foreach((array) ($supportiveProfile['options'] ?? []) as $supportiveOption) {
            if(!is_array($supportiveOption)) {
                continue;
            }
            $supportiveProductKey = trim((string) ($supportiveOption['product_key'] ?? ''));
            if($supportiveProductKey !== '') {
                $approvedSupportiveOptions[$supportiveProductKey] = $supportiveOption;
            }
        }
        $requestedSupportiveKeys = array_values(array_unique(array_filter(array_map(
            static fn($productKey): string => trim((string) $productKey),
            (array) ($final['recommendation_payload']['supportive_reference_product_keys'] ?? [])
        ))));
        $supportiveContextAllowed = $plan !== []
            && (string) ($plan['decision']['action'] ?? '') === 'doctor_first'
            && (string) ($final['decision']['action'] ?? '') === 'doctor_first'
            && (string) ($plan['safety']['level'] ?? '') === 'sensitive'
            && empty($plan['safety']['allow_products'])
            && $approvedSupportiveOptions !== [];

        if($supportiveContextAllowed && $requestedSupportiveKeys !== []) {
            $preferences = $user->preferences ?? null;
            if(is_string($preferences)) {
                $preferences = json_decode($preferences);
            }
            $meta = is_object($preferences) ? ($preferences->meta ?? null) : null;
            $foreverId = is_object($meta)
                ? \forever_business_normalize_fbo_id($meta->foreverId ?? '')
                : '';
            $discountParams = \Altum\Link::get_main_biolink_discount_query_params(
                (int) ($user->user_id ?? 0),
                $marketCode
            );

            foreach(array_slice($requestedSupportiveKeys, 0, 3) as $supportiveProductKey) {
                $supportiveOption = $approvedSupportiveOptions[$supportiveProductKey] ?? null;
                $supportiveRow = $rows[$supportiveProductKey] ?? null;
                if(
                    !is_array($supportiveOption)
                    || !is_array($supportiveRow)
                    || (string) ($supportiveRow['advisor_status'] ?? 'lookup_only') !== 'recommendable'
                    || (string) ($supportiveRow['availability_status'] ?? '') !== 'available'
                    || mb_strtolower(trim((string) ($supportiveRow['market_code'] ?? ''))) !== mb_strtolower(trim($marketCode))
                    || \fcc_ai_v2_supportive_product_blocked_by_constraints(
                        $supportiveProductKey,
                        (array) ($plan['user_constraints'] ?? [])
                    )
                ) {
                    continue;
                }

                $baseUrl = trim((string) ($supportiveRow['official_url'] ?? ''));
                if(!$this->isTrustedForeverShopUrl($baseUrl, $marketCode)) {
                    continue;
                }
                $builtUrl = $foreverId !== ''
                    ? \Altum\Link::build_forever_destination_url($baseUrl, $foreverId, $marketCode, $discountParams)
                    : $baseUrl;
                $supportiveUrl = is_string($builtUrl) ? trim($builtUrl) : '';
                if(!$this->isTrustedForeverShopUrl($supportiveUrl, $marketCode)) {
                    continue;
                }

                $supportiveProductLinks[] = [
                    'card_kind' => 'supportive_wellness',
                    'recommendation_eligible' => false,
                    'supportive_information_eligible' => true,
                    'product_key' => $supportiveProductKey,
                    'product_name' => (string) ($supportiveRow['canonical_name'] ?? ''),
                    'condition_key' => (string) ($supportiveProfile['condition_key'] ?? ''),
                    'profile_version' => (string) ($supportiveProfile['profile_version'] ?? ''),
                    'evidence_tier' => (string) ($supportiveOption['evidence_tier'] ?? ''),
                    'reason_hr' => trim((string) ($supportiveOption['reason_hr'] ?? '')),
                    'official_url' => $supportiveUrl,
                    'not_treatment' => true,
                ];
            }
        }

        if(is_array($final['recommendation_payload'] ?? null)) {
            $final['recommendation_payload']['supportive_product_links'] = $supportiveProductLinks;
            $final['recommendation_payload']['supportive_shop_available'] = $supportiveProductLinks !== [];
        }

        if($supportiveProductLinks !== []) {
            $language = (string) ($final['language'] ?? 'hr');
            $linkHeading = \fcc_ai_v2_localized_text($language, [
                'hr' => 'Ako su vam navedene sigurnosne provjere uredne, Forever proizvode možete pogledati ili naručiti ovdje:',
                'en' => 'If the safety checks above are clear for you, you can view or order the Forever products here:',
                'sl' => 'Če so navedena varnostna preverjanja za vas ustrezna, si lahko izdelke Forever ogledate ali jih naročite tukaj:',
                'bg' => 'Ако посочените проверки за безопасност са подходящи за вас, можете да разгледате или поръчате продуктите Forever тук:',
            ]);
            $linkLines = [];
            foreach($supportiveProductLinks as $supportiveProductLink) {
                $linkLines[] = '- '
                    . (string) ($supportiveProductLink['product_name'] ?? '')
                    . ': '
                    . (string) ($supportiveProductLink['official_url'] ?? '');
            }
            $supportiveCta = trim($linkHeading . "\n" . implode("\n", $linkLines));
            $content = trim((string) ($final['content'] ?? ''));
            if($supportiveCta !== '' && !str_contains($content, (string) ($supportiveProductLinks[0]['official_url'] ?? ''))) {
                $final['content'] = $content !== '' ? $content . "\n\n" . $supportiveCta : $supportiveCta;
            }
        }

        if($officialUrl !== '' && $recommendationEligible) {
            $language = (string) ($final['language'] ?? 'hr');
            $cta = \fcc_ai_v2_localized_text($language, [
                'hr' => 'Ako vam izbor odgovara, otvorite službeni Forever kupovni link na kartici proizvoda.',
                'en' => 'If the choice suits you, open the official Forever purchase link on the product card.',
                'sl' => 'Če vam izbira ustreza, odprite uradno povezavo Forever na kartici izdelka.',
                'bg' => 'Ако изборът ви устройва, отворете официалния Forever линк в продуктовата карта.',
            ]);
            $content = trim((string) ($final['content'] ?? ''));
            if($cta !== '' && !str_contains(\fcc_ai_v2_normalize_text($content), \fcc_ai_v2_normalize_text($cta))) {
                $final['content'] = $content !== '' ? $content . "\n\n" . $cta : $cta;
            }
        }

        $referralSlug = trim((string) ($link->url ?? ''));
        if($referralSlug !== '' && is_array($final['knowledge_suggestions'] ?? null)) {
            foreach($final['knowledge_suggestions'] as &$suggestion) {
                if(
                    !is_array($suggestion)
                    || empty($suggestion['url'])
                    || !$this->isFirstPartyKnowledgeUrl((string) $suggestion['url'])
                ) {
                    continue;
                }
                $separator = parse_url((string) $suggestion['url'], PHP_URL_QUERY) ? '&' : '?';
                $suggestion['url'] .= $separator . http_build_query(['ref' => $referralSlug]);
            }
            unset($suggestion);
        }

        return $final;
    }

    /**
     * File and clock access belong in the framework adapter, never in the pure
     * replayable decision core. The core receives an immutable snapshot and
     * validates its schema/market/review date deterministically.
     */
    private function attachAdvisorContext(array $catalog): array {
        $marketNow = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Zagreb'));
        $context = [
            'as_of_date' => $marketNow->format('Y-m-d'),
            'label_plans' => [],
            'business_offers' => [],
        ];

        $labelPlanPath = APP_PATH . 'config/fcc_product_advisor_label_plans.php';
        if(is_file($labelPlanPath)) {
            $labelPlans = require $labelPlanPath;
            if(is_array($labelPlans)) {
                $context['label_plans'] = $labelPlans;
            }
        }

        $businessOfferPath = APP_PATH . 'config/fcc_business_offer_hr.php';
        if(is_file($businessOfferPath)) {
            $businessOffer = require $businessOfferPath;
            if(is_array($businessOffer)) {
                $context['business_offers']['HR'] = $businessOffer;
            }
        }

        $catalog['__advisor_context'] = $context;
        return $catalog;
    }

    private function isFirstPartyKnowledgeUrl(string $url): bool {
        $url = trim($url);
        if($url === '') {
            return false;
        }
        if(str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }
        if(filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        $siteParts = parse_url((string) SITE_URL);
        if(!is_array($parts) || !is_array($siteParts)) {
            return false;
        }
        $scheme = mb_strtolower((string) ($parts['scheme'] ?? ''));
        $siteScheme = mb_strtolower((string) ($siteParts['scheme'] ?? ''));
        $host = mb_strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        $siteHost = mb_strtolower(rtrim((string) ($siteParts['host'] ?? ''), '.'));
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        $sitePort = (int) ($siteParts['port'] ?? ($siteScheme === 'https' ? 443 : 80));

        return in_array($scheme, ['http', 'https'], true)
            && $scheme === $siteScheme
            && $host !== ''
            && hash_equals($siteHost, $host)
            && $port === $sitePort
            && empty($parts['user'])
            && empty($parts['pass']);
    }

    private function providerUserIdentifier(int $userId, string $visitorKey): string {
        return 'user:' . max(0, $userId) . ':visitor:' . hash('sha256', $visitorKey);
    }

    private function safeErrorCode(string $value): string {
        $value = preg_replace('/[^a-z0-9_\-]+/i', '_', mb_strtolower(trim($value))) ?? '';
        return mb_substr(trim($value, '_'), 0, 64);
    }

    private function logRun(
        object $conversation,
        object $user,
        ?int $assistantMessageId,
        ?int $userMessageId,
        string $message,
        array $plan,
        array $final,
        array $replyMeta,
        array $providerCandidate,
        int $durationMs,
        array $catalog
    ): void {
        try {
            if(!$this->catalogRepository->schemaIsReady()) {
                return;
            }
            $requestId = trim((string) ($replyMeta['request_id'] ?? ''));
            if($requestId === '') {
                $requestId = 'local_' . substr(hash('sha256', microtime(true) . ':' . random_int(1, PHP_INT_MAX)), 0, 48);
            }
            $replaySnapshot = [
                'engine_version' => (string) ($replyMeta['engine_version'] ?? '2.0.0-local'),
                'catalog_revision_id' => (int) ($replyMeta['catalog_revision_id'] ?? 0),
                'catalog_revision_key' => (string) ($replyMeta['catalog_revision_key'] ?? ''),
                'catalog_source_hash' => (string) ($replyMeta['catalog_source_hash'] ?? ''),
                'language' => (string) ($final['language'] ?? ''),
                'market' => (string) ($replyMeta['market_code'] ?? ''),
                'market_source' => (string) ($replyMeta['market_source'] ?? 'unknown'),
                'intent' => (string) ($plan['intent'] ?? ''),
                'safety' => is_array($plan['safety'] ?? null) ? $plan['safety'] : [],
                'decision' => is_array($plan['decision'] ?? null) ? $plan['decision'] : [],
                'candidates' => array_values((array) ($plan['candidates'] ?? [])),
                'allowed_product_keys' => array_values((array) ($plan['allowed_product_keys'] ?? [])),
                'rejected_product_keys' => array_values((array) ($plan['rejected_product_keys'] ?? [])),
                'trace' => is_array($plan['trace'] ?? null) ? $plan['trace'] : [],
                'state' => is_array($final['state'] ?? null) ? $final['state'] : [],
                'final' => [
                    'decision' => is_array($final['decision'] ?? null) ? $final['decision'] : [],
                    'selected_product_keys' => array_values((array) ($final['selected_product_keys'] ?? [])),
                    'selected_relationships' => array_values((array) ($replyMeta['selected_relationships'] ?? [])),
                    'validation' => is_array($final['validation'] ?? null) ? $final['validation'] : [],
                ],
            ];
            db()->insert('fcc_product_advisor_runs', [
                'revision_id' => !empty($catalog[0]['revision_id']) ? (int) $catalog[0]['revision_id'] : null,
                'request_id' => mb_substr($requestId, 0, 64),
                'fcc_ai_conversation_id' => (int) ($conversation->fcc_ai_conversation_id ?? 0) ?: null,
                'fcc_ai_message_id' => $assistantMessageId ?: null,
                'user_message_id' => $userMessageId ?: null,
                'user_id' => (int) ($user->user_id ?? 0) ?: null,
                'language_code' => (string) ($final['language'] ?? ''),
                'market_code' => (string) ($replyMeta['market_code'] ?? ''),
                'market_source' => mb_substr((string) ($replyMeta['market_source'] ?? 'unknown'), 0, 32),
                'query_hash' => hash('sha256', $message),
                'decision_mode' => (string) ($final['decision']['action'] ?? ''),
                'confidence' => (float) ($plan['decision']['confidence'] ?? 0),
                'selected_product_keys' => json_encode(array_values((array) ($final['selected_product_keys'] ?? []))),
                'candidate_snapshot' => json_encode(array_values((array) ($plan['candidates'] ?? []))),
                'reason_codes' => json_encode(array_values((array) ($plan['decision']['reason_codes'] ?? []))),
                'guardrail_flags' => json_encode([
                    'safety_level' => (string) ($plan['safety']['level'] ?? 'normal'),
                    'validation_errors' => array_values((array) ($final['validation']['errors'] ?? [])),
                    'fallback_used' => (bool) ($final['validation']['fallback_used'] ?? false),
                    'fallback_reason' => (string) ($replyMeta['fallback_reason'] ?? ''),
                    'provider_error_code' => (string) ($replyMeta['provider_error_code'] ?? ''),
                ]),
                'provider_output_accepted' => (int) !empty($replyMeta['provider_output_accepted']),
                'provider_candidate' => $providerCandidate
                    ? json_encode($providerCandidate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null,
                'replay_snapshot' => json_encode($replaySnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'provider' => (string) ($replyMeta['provider'] ?? ''),
                'model' => mb_substr((string) ($replyMeta['model'] ?? ''), 0, 64),
                'prompt_version' => mb_substr((string) ($replyMeta['prompt_version'] ?? ''), 0, 64),
                'schema_version' => mb_substr((string) ($replyMeta['schema_version'] ?? ''), 0, 64),
                'duration_ms' => max(0, $durationMs),
                'meta' => json_encode([
                    'provider_attempt_success' => (bool) ($replyMeta['provider_attempt_success'] ?? false),
                    'provider_schema_valid' => (bool) ($replyMeta['provider_schema_valid'] ?? false),
                    'provider_output_accepted' => (bool) ($replyMeta['provider_output_accepted'] ?? false),
                    'provider_fields_used' => (bool) ($replyMeta['provider_fields_used'] ?? false),
                    'provider_success' => (bool) ($replyMeta['provider_success'] ?? false),
                    'input_tokens' => (int) ($replyMeta['input_tokens'] ?? 0),
                    'output_tokens' => (int) ($replyMeta['output_tokens'] ?? 0),
                    'catalog_revision_id' => (int) ($replyMeta['catalog_revision_id'] ?? 0),
                    'catalog_revision_key' => (string) ($replyMeta['catalog_revision_key'] ?? ''),
                    'catalog_source_hash' => (string) ($replyMeta['catalog_source_hash'] ?? ''),
                    'fallback_reason' => (string) ($replyMeta['fallback_reason'] ?? ''),
                    'provider_error_code' => (string) ($replyMeta['provider_error_code'] ?? ''),
                    'selected_relationships' => array_values((array) ($replyMeta['selected_relationships'] ?? [])),
                ]),
                'created_at' => get_date(),
            ]);
        } catch(\Throwable $exception) {
            /* Never expose conversation content; retain a safe operational signal. */
            error_log(sprintf(
                '[fcc_product_advisor_v2] telemetry_insert_failed class=%s code=%s fingerprint=%s',
                get_class($exception),
                $this->safeErrorCode((string) $exception->getCode()),
                substr(hash('sha256', $exception->getMessage()), 0, 16)
            ));
        }
    }

    private static function environmentFlag(string $name): ?bool {
        $value = getenv($name);
        if(!is_string($value) || trim($value) === '') {
            $value = $_ENV[$name] ?? $_SERVER[$name] ?? null;
        }
        if($value === null || trim((string) $value) === '') {
            return null;
        }
        $normalized = mb_strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on', 'enabled'], true);
    }

    public static function isTrustedLocalEnvironment(): bool {
        /* HTTP_HOST is client-controlled and must never activate a feature.
         * Local mode trusts only deployment-owned configuration or a genuine
         * loopback-to-loopback request. */
        if(self::environmentFlag('FCC_PRODUCT_ADVISOR_V2_LOCAL') === true) {
            return true;
        }

        $configuredSiteUrl = trim((string) (getenv('SITE_URL') ?: ''));
        if($configuredSiteUrl !== '') {
            $configuredHost = mb_strtolower(trim((string) parse_url($configuredSiteUrl, PHP_URL_HOST)));
            if(in_array($configuredHost, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($configuredHost, '.test') || str_ends_with($configuredHost, '.local')) {
                return true;
            }
        }

        $serverAddress = trim((string) ($_SERVER['SERVER_ADDR'] ?? ''));
        $remoteAddress = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        return in_array($serverAddress, ['127.0.0.1', '::1'], true)
            && in_array($remoteAddress, ['127.0.0.1', '::1'], true);
    }
}
