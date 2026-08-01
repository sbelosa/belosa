<?php
/* Custom code: FC-2026-08-01: regression checks for readonly cron-secret redaction */

$controller_path = dirname(__DIR__) . '/app/controllers/OpsReadonly.php';
$source = file_get_contents($controller_path);

if($source === false) {
    fwrite(STDERR, "Could not load the readonly diagnostics controller.\n");
    exit(1);
}

$method_start = strpos($source, 'private function get_cron_diagnostics_payload(): array');
$method_end = $method_start === false ? false : strpos($source, 'private function get_health_payload(): array', $method_start);
$method_source = $method_start === false || $method_end === false
    ? ''
    : substr($source, $method_start, $method_end - $method_start);

$assertions = [
    'cron key is not placed in a URL' => !str_contains($method_source, "url('cron?key='"),
    'cron key is not URL-encoded for output' => !str_contains($method_source, 'rawurlencode($cron_key)'),
    'readonly trigger URL omits query parameters' => str_contains($method_source, "'trigger_url' => \$cron_key !== '' ? url('cron') : null"),
    'readonly curl command is redacted' => str_contains($method_source, "'curl' => null"),
    'readonly wget command is redacted' => str_contains($method_source, "'wget' => null"),
];

$failed = [];

foreach($assertions as $description => $passed) {
    if(!$passed) {
        $failed[] = $description;
    }
}

if($failed) {
    fwrite(STDERR, "Readonly diagnostics secret-redaction check failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Readonly diagnostics secret-redaction checks passed.\n";

/* /Custom code: FC-2026-08-01 */
