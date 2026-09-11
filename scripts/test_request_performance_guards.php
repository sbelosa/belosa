<?php
/* Static regression guards for request-path performance fixes. */

$root = dirname(__DIR__);
$failures = [];
$checks = 0;

$read = static function(string $relative) use ($root, &$failures): string {
    $path = $root . '/' . $relative;

    if(!is_file($path)) {
        $failures[] = 'Missing file: ' . $relative;
        return '';
    }

    $contents = file_get_contents($path);

    if($contents === false) {
        $failures[] = 'Unreadable file: ' . $relative;
        return '';
    }

    return $contents;
};

$extract_function = static function(string $source, string $name): string {
    $tokens = token_get_all($source);
    $token_count = count($tokens);

    for($index = 0; $index < $token_count; $index++) {
        if(!is_array($tokens[$index]) || $tokens[$index][0] !== T_FUNCTION) {
            continue;
        }

        $is_match = false;

        for($name_index = $index + 1; $name_index < $token_count; $name_index++) {
            $token = $tokens[$name_index];

            if($token === '(') {
                break;
            }

            if(is_array($token) && $token[0] === T_STRING) {
                $is_match = $token[1] === $name;
                break;
            }
        }

        if(!$is_match) {
            continue;
        }

        $function = '';
        $depth = 0;
        $body_started = false;

        for($body_index = $index; $body_index < $token_count; $body_index++) {
            $token = $tokens[$body_index];
            $text = is_array($token) ? $token[1] : $token;
            $function .= $text;

            if(
                $token === '{'
                || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))
            ) {
                $body_started = true;
                $depth++;
            } elseif($token === '}' && $body_started) {
                $depth--;

                if($depth === 0) {
                    return $function;
                }
            }
        }
    }

    return '';
};

$assert = static function(bool $condition, string $message) use (&$checks, &$failures): void {
    $checks++;

    if(!$condition) {
        $failures[] = $message;
    }
};

$vip_funnel = $read('app/helpers/vip_funnel.php');
$cron = $read('app/controllers/Cron.php');
$dashboard = $read('app/controllers/Dashboard.php');
$user_model = $read('app/models/User.php');
$link_helper = $read('app/helpers/Link.php');
$public_biolink = $read('themes/altum/views/l/partials/biolink.php');

$sandbox_check = $extract_function($vip_funnel, 'vip_funnel_demo_is_sandbox_user');
$demo_sync = $extract_function($vip_funnel, 'vip_funnel_demo_sync_statuses');
$signal_map = $extract_function($vip_funnel, 'vip_funnel_get_public_qualification_signal_map');
$signal_payload = $extract_function($vip_funnel, 'vip_funnel_get_public_qualification_signal_payload');
$notification_cron = $extract_function($cron, 'fcc_public_signal_notifications');
$dashboard_stats = $extract_function($dashboard, 'get_stats_ajax');
$plan_expiration = $extract_function($user_model, 'process_user_plan_expiration_by_user');
$trusted_country_resolver = $extract_function($link_helper, 'get_trusted_forever_request_country_code');
$vip_business_url = $extract_function($vip_funnel, 'vip_funnel_get_forever_business_referral_url');

$assert($sandbox_check !== '', 'Sandbox user check is missing.');
$sandbox_payload_position = strpos($sandbox_check, 'vip_funnel_demo_get_user_payload');
$sandbox_early_return_position = strpos($sandbox_check, 'if(!$is_sandbox_user)');
$sandbox_sync_position = strpos($sandbox_check, 'vip_funnel_demo_sync_statuses');
$assert(
    $sandbox_payload_position !== false
        && $sandbox_early_return_position !== false
        && $sandbox_sync_position !== false
        && $sandbox_payload_position < $sandbox_early_return_position
        && $sandbox_early_return_position < $sandbox_sync_position,
    'Ordinary users must return before demo schema checks or global status synchronization.'
);
$assert(substr_count($sandbox_check, 'vip_funnel_demo_sync_statuses()') === 1, 'Sandbox check must invoke status synchronization at most once.');

$assert($demo_sync !== '', 'Demo status synchronization is missing.');
$assert(
    strpos($demo_sync, 'WHERE NOT (`l`.`demo_status` <=> `d`.`status`)') !== false
        && strpos($demo_sync, 'OR NOT (`l`.`last_datetime` <=> `d`.`last_datetime`)') !== false,
    'Demo lead synchronization must update only rows whose mirrored values differ.'
);

$assert($signal_map !== '', 'Public qualification signal map is missing.');
$assert(strpos($signal_map, 'vip_funnel_ensure_runtime_schema') === false, 'Public signal read path must not provision runtime schema.');

$assert($signal_payload !== '', 'User public qualification signal payload is missing.');
$assert(strpos($signal_payload, 'vip_funnel_get_public_qualification_signal_map') === false, 'Single-user signal payload must not build the global signal map.');
$assert(strpos($signal_payload, 'WHERE `owner_user_id` = {$user_id}') !== false, 'Lead signal aggregate must be scoped to owner_user_id.');
$assert(strpos($signal_payload, 'WHERE `user_id` = {$user_id}') !== false, 'Funnel event aggregate must be scoped to user_id.');
$assert(stripos($signal_payload, 'GROUP BY') === false, 'Single-user signal aggregates must not group the complete tables.');

$assert($notification_cron !== '', 'Public signal notification cron is missing.');
$candidate_filter_position = strpos($notification_cron, 'fcc_ai_get_active_growth_pro_user_condition_sql');
$profile_state_position = strpos($notification_cron, 'fcc_featured_get_user_public_profile_state');
$defensive_check_position = strpos($notification_cron, 'fcc_ai_user_has_active_growth_pro');
$assert(
    $candidate_filter_position !== false
        && $profile_state_position !== false
        && $candidate_filter_position < $profile_state_position,
    'Cron must filter active Growth Pro candidates before building expensive profile state.'
);
$assert(
    $defensive_check_position !== false && $defensive_check_position < $profile_state_position,
    'Cron must retain the defensive per-user Growth Pro feature check before profile state.'
);
$assert(strpos($notification_cron, '`users`.`extra`') !== false, 'Cron candidate query must select billing extra data and avoid defensive-check N+1 queries.');
$assert(strpos($notification_cron, '`users`.`type` = 0') !== false, 'Cron candidate query must exclude administrator accounts.');
$assert(strpos($notification_cron, 'LIMIT 500') !== false, 'Cron candidate query must remain bounded.');
$assert(strpos($notification_cron, '$.fcc_public_signal_notifications.last_evaluated_at') !== false, 'Bounded cron batches must prioritize never or least recently evaluated users.');
$assert(substr_count($notification_cron, 'fcc_featured_save_public_signal_notification_state') === 1, 'Cron must not write notification state for filtered non-Pro users.');

$assert($dashboard_stats !== '', 'Dashboard stats endpoint is missing.');
$benchmark_start = strpos($dashboard_stats, '$team_benchmarks_cache_key');
$benchmark_end = strpos($dashboard_stats, '$team_active_partners_30d =', $benchmark_start === false ? 0 : $benchmark_start);
$benchmark_block = $benchmark_start !== false && $benchmark_end !== false
    ? substr($dashboard_stats, $benchmark_start, $benchmark_end - $benchmark_start)
    : '';
$assert($benchmark_block !== '', 'Dashboard global benchmark cache block is missing.');
$assert(strpos($benchmark_block, '\\Altum\\Cache::cache_function_result') !== false, 'Dashboard global benchmarks must use the shared cache.');
$assert(strpos($benchmark_block, '}, 300)') !== false, 'Dashboard global benchmark cache TTL must be 300 seconds.');
$assert(substr_count($benchmark_block, 'database()->query(') === 6, 'All six global benchmark aggregates must execute only inside the cache callback.');
$assert(strpos($benchmark_block, '$this->user') === false, 'Global benchmark cache must not contain user-specific data.');

$assert($plan_expiration !== '', 'User plan-expiration processor is missing.');
$expiration_check_position = strpos($plan_expiration, '$plan_expiration_date >= $now');
$stripe_protection_position = strpos($plan_expiration, 'has_expired_plan_downgrade_protection');
$assert(
    $expiration_check_position !== false
        && $stripe_protection_position !== false
        && $expiration_check_position < $stripe_protection_position,
    'Non-expired plans must return before any live Stripe downgrade-protection lookup.'
);

$assert($trusted_country_resolver !== '', 'Trusted country resolver is missing.');
$assert(strpos($trusted_country_resolver, 'HTTP_X_COUNTRY_CODE') === false && strpos($trusted_country_resolver, 'HTTP_X_COUNTRY') === false, 'Client-controlled country headers must not be treated as trusted proxy signals.');
$vip_local_geo_position = strpos($vip_business_url, 'GeoLite2-City.mmdb');
$vip_external_geo_position = strpos($vip_business_url, 'get_external_geo_country_code');
$assert($vip_local_geo_position !== false && $vip_external_geo_position !== false && $vip_local_geo_position < $vip_external_geo_position, 'VIP public render must try local GeoLite before external geo services.');
$biolink_local_geo_position = strpos($public_biolink, "isset(\$maxmind['country']['iso_code'])");
$biolink_external_geo_position = strpos($public_biolink, 'get_external_geo_country_code');
$assert($biolink_local_geo_position !== false && $biolink_external_geo_position !== false && $biolink_local_geo_position < $biolink_external_geo_position, 'Public biolink render must try local GeoLite before external geo services.');

if($failures) {
    fwrite(STDERR, "Request performance guard failures:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "Request performance guards passed ({$checks} checks).\n");
