<?php

define('ALTUMCODE', true);
define('BREVO_API_KEY', 'environment-key');

$test_settings = (object) [
    'smtp' => (object) [
        'transport' => 'brevo_api',
        'brevo_api_key' => 'settings-key',
        'host' => 'mail.example.com',
        'port' => 465,
        'from' => 'noreply@example.com',
    ],
];

function settings() {
    global $test_settings;
    return $test_settings;
}

require dirname(__DIR__) . '/app/helpers/email.php';

$assert = static function(bool $condition, string $message): void {
    if(!$condition) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
};

$assert(get_brevo_api_key() === 'settings-key', 'admin Brevo key must take precedence');

$test_settings->smtp->brevo_api_key = '';
$assert(get_brevo_api_key() === 'environment-key', 'environment key must be used when admin key is blank');
$assert(fc_smtp_fallback_is_configured(), 'configured SMTP fallback must be detected');

$rejected = (object) ['success' => false, 'status_code' => 401, 'curl_error' => ''];
$server_error = (object) ['success' => false, 'status_code' => 503, 'curl_error' => ''];
$timeout = (object) ['success' => false, 'status_code' => 0, 'curl_error' => 'timeout'];

$assert(fc_brevo_failure_allows_smtp_fallback($rejected, []), 'explicit Brevo 4xx rejection should allow system-mail fallback');
$assert(!fc_brevo_failure_allows_smtp_fallback($rejected, ['brevo_tags' => ['automation_1']]), 'tracked automation must not use silent fallback');
$assert(!fc_brevo_failure_allows_smtp_fallback($rejected, ['unsubscribe_url' => 'https://example.com/unsubscribe']), 'unsubscribe mail must not use silent fallback');
$assert(!fc_brevo_failure_allows_smtp_fallback($server_error, []), 'Brevo 5xx must not risk a duplicate send');
$assert(!fc_brevo_failure_allows_smtp_fallback($timeout, []), 'network timeout must not risk a duplicate send');

$assert(fc_mail_is_tracked_marketing_message(['is_broadcast' => true]), 'broadcast mail must be treated as tracked marketing');
$assert(fc_mail_is_tracked_marketing_message(['brevo_tags' => ['progress']]), 'tagged automation must be treated as tracked marketing');
$assert(!fc_mail_is_tracked_marketing_message(['anti_phishing_code' => 'test']), 'critical account mail must remain eligible for safe fallback');

echo "Brevo mail fallback checks passed.\n";
