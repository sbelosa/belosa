<?php

defined('ALTUMCODE') || die();

return [
    /* Local-only until replay, manual acceptance and an explicit production decision pass. */
    'feature' => [
        'mode' => 'local',
        'rollout_percent' => 0,
        'pilot_user_ids' => [],
        'catalog_revision_id' => null,
        /* Stable deploy pin; a differently published snapshot fails closed. */
        'catalog_revision_key' => getenv('FCC_LOCAL') === '1' && getenv('FCC_LOCAL_CATALOG_KEY') ? getenv('FCC_LOCAL_CATALOG_KEY') : 'official-hr-r18-draft12-localqa-20260826-60c8c8bf',
        'catalog_bundle_hash' => getenv('FCC_LOCAL') === '1' && getenv('FCC_LOCAL_CATALOG_HASH') ? getenv('FCC_LOCAL_CATALOG_HASH') : 'd0635ac21a1b1b2e85f5b269cab3b11dc6571691a3274a608cd48241e6293457',
        /* V2 must not claim coverage for a language/market that lacks reviewed facts. */
        'certified_scopes' => ['hr:hr'],
        'force_v1_env' => 'FCC_PRODUCT_ADVISOR_V2_FORCE_V1',
    ],

    'provider' => 'openai',
    /* Focused catalog selection uses Luna. Server validation still owns facts,
     * market availability, safety, contact consent and referral destinations. */
    'model' => 'gpt-6-luna',
    'endpoint' => 'https://api.openai.com/v1/responses',
    'prompt_path' => APP_PATH . 'includes/fcc-ai-prompts/product_advisor_v2.php',
    'prompt_version' => 'product_advisor_v2_economy_2026_09_28',
    'schema_name' => 'fcc_product_advisor_v2',
    'schema_version' => 'fcc_product_advisor_v2_economy_2026_09_28',

    'timeout_seconds' => 20,
    'connect_timeout_seconds' => 5,
    'max_attempts' => 2,
    'retry_delay_ms' => 250,
    'max_retry_delay_ms' => 1000,

    /* Includes both visible output and reasoning tokens in the Responses API. */
    'max_output_tokens' => 1800,
    'reasoning_effort' => 'none',
    'verbosity' => 'low',

    'identifier_namespace' => 'fcc_product_advisor_v2',
];
