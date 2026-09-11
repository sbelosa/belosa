<?php
/* Custom code: FC-2026-09-11: regression checks for readonly secret redaction and deployment markers */

$root = dirname(__DIR__);
$controller_path = $root . '/app/controllers/OpsReadonly.php';
$workflow_path = $root . '/.github/workflows/deploy-cpanel-ftp.yml';
$smoke_workflow_path = $root . '/.github/workflows/smoke-check-live-ops-readonly.yml';
$controller_source = file_get_contents($controller_path);
$workflow_source = file_get_contents($workflow_path);
$smoke_workflow_source = file_get_contents($smoke_workflow_path);

if($controller_source === false || $workflow_source === false || $smoke_workflow_source === false) {
    fwrite(STDERR, "Could not load the readonly diagnostics controller or deployment workflows.\n");
    exit(1);
}

$extract_method = static function(string $source, string $method_name): string {
    $signature = 'private function ' . $method_name . '(';
    $method_start = strpos($source, $signature);

    if($method_start === false) {
        return '';
    }

    $method_end = strpos($source, "\n    private function ", $method_start + strlen($signature));

    return $method_end === false
        ? substr($source, $method_start)
        : substr($source, $method_start, $method_end - $method_start);
};

$cron_method_source = $extract_method($controller_source, 'get_cron_diagnostics_payload');
$normalize_method_source = $extract_method($controller_source, 'normalize_deployment_marker');
$deployment_method_source = $extract_method($controller_source, 'get_deployment_payload');
$health_method_source = $extract_method($controller_source, 'get_health_payload');

$temporary_root = rtrim(sys_get_temp_dir(), '/\\') . '/fcc-ops-marker-test-' . getmypid();
$temporary_config = $temporary_root . '/config';
$marker_path = $temporary_config . '/deployment.php';
$execution_sentinel_path = $temporary_root . '/marker-was-executed';

if(!is_dir($temporary_config) && !mkdir($temporary_config, 0700, true)) {
    fwrite(STDERR, "Could not create the deployment marker test directory.\n");
    exit(1);
}

@unlink($marker_path);
@unlink($execution_sentinel_path);

define('ALTUMCODE', true);
define('APP_PATH', $temporary_root . '/');

if(!class_exists('Altum\\Controllers\\Controller', false)) {
    eval('namespace Altum\\Controllers; class Controller {}');
}

require_once $controller_path;

$controller_reflection = new ReflectionClass('Altum\\Controllers\\OpsReadonly');
$controller = $controller_reflection->newInstanceWithoutConstructor();
$normalize_method = $controller_reflection->getMethod('normalize_deployment_marker');
$deployment_method = $controller_reflection->getMethod('get_deployment_payload');
$normalize_method->setAccessible(true);
$deployment_method->setAccessible(true);

$valid_marker = [
    'github_sha' => str_repeat('a', 40),
    'github_run_id' => '123456789',
    'deployed_at' => '2026-09-11T12:34:56Z',
];
$valid_marker_contents = sprintf(
    "<?php\n\nreturn [\n    'github_sha' => '%s',\n    'github_run_id' => '%s',\n    'deployed_at' => '%s',\n];\n",
    $valid_marker['github_sha'],
    $valid_marker['github_run_id'],
    $valid_marker['deployed_at']
);

$valid_marker_written = file_put_contents($marker_path, $valid_marker_contents) !== false;
$valid_marker_read = $valid_marker_written
    ? $deployment_method->invoke($controller)
    : null;

$marker_with_secret = substr($valid_marker_contents, 0, -3)
    . "    'ftp_password' => 'must-not-be-exposed',\n];\n";
$secret_marker_written = file_put_contents($marker_path, $marker_with_secret) !== false;
$secret_marker_read = $secret_marker_written
    ? $deployment_method->invoke($controller)
    : $valid_marker;

$malicious_marker = "<?php\nfile_put_contents(" . var_export($execution_sentinel_path, true) . ", 'executed');\n"
    . substr($valid_marker_contents, strlen("<?php\n"));
$malicious_marker_written = file_put_contents($marker_path, $malicious_marker) !== false;
$malicious_marker_read = $malicious_marker_written
    ? $deployment_method->invoke($controller)
    : $valid_marker;
$malicious_marker_executed = is_file($execution_sentinel_path);

$valid_normalized = $normalize_method->invoke($controller, $valid_marker);
$marker_with_extra_key = $normalize_method->invoke($controller, $valid_marker + ['api_key' => 'must-not-be-exposed']);
$marker_with_uppercase_sha = $valid_marker;
$marker_with_uppercase_sha['github_sha'] = str_repeat('A', 40);
$marker_with_integer_run_id = $valid_marker;
$marker_with_integer_run_id['github_run_id'] = 123456789;
$marker_with_invalid_date = $valid_marker;
$marker_with_invalid_date['deployed_at'] = '2026-02-30T12:34:56Z';

$lint_position = strpos($workflow_source, '- name: Lint deployed PHP files');
$tests_position = strpos($workflow_source, '- name: Run deterministic release guards');
$app_deploy_position = strpos($workflow_source, '- name: Deploy app/');
$themes_deploy_position = strpos($workflow_source, '- name: Deploy themes/');
$verify_position = strpos($workflow_source, '- name: Verify Forever Business runtime');
$generate_marker_position = strpos($workflow_source, '- name: Generate verified deployment marker');
$publish_marker_position = strpos($workflow_source, '- name: Publish verified deployment marker');

$assertions = [
    'cron key is not placed in a URL' => !str_contains($cron_method_source, "url('cron?key='"),
    'cron key is not URL-encoded for output' => !str_contains($cron_method_source, 'rawurlencode($cron_key)'),
    'readonly trigger URL omits query parameters' => str_contains($cron_method_source, "'trigger_url' => \$cron_key !== '' ? url('cron') : null"),
    'readonly curl command is redacted' => str_contains($cron_method_source, "'curl' => null"),
    'readonly wget command is redacted' => str_contains($cron_method_source, "'wget' => null"),
    'deployment marker has an exact public-field allowlist' => str_contains($normalize_method_source, "\$allowed_keys = ['github_sha', 'github_run_id', 'deployed_at']"),
    'deployment marker requires a full lowercase commit SHA' => str_contains($normalize_method_source, "preg_match('/\\A[0-9a-f]{40}\\z/'"),
    'deployment marker requires a bounded numeric run ID' => str_contains($normalize_method_source, "preg_match('/\\A[1-9][0-9]{0,19}\\z/'"),
    'deployment marker path is fixed under app config' => str_contains($deployment_method_source, "APP_PATH . 'config/deployment.php'"),
    'deployment marker rejects symbolic links' => str_contains($deployment_method_source, 'is_link($marker_path)'),
    'deployment marker has a four-kibibyte size cap' => str_contains($deployment_method_source, '$marker_size > 4096'),
    'deployment marker is parsed as data and never executed' => str_contains($deployment_method_source, 'file_get_contents($marker_path)')
        && !preg_match('/\\b(?:include|include_once|require|require_once)\\b/', $deployment_method_source),
    'health payload adds only a valid optional deployment field' => str_contains($health_method_source, "if(\$deployment !== null)")
        && str_contains($health_method_source, "\$payload['deployment'] = \$deployment"),
    'valid deployment marker normalizes exactly' => $valid_normalized === $valid_marker,
    'valid deployment marker file parses exactly' => $valid_marker_read === $valid_marker,
    'deployment marker with a secret field is rejected' => $marker_with_extra_key === null && $secret_marker_read === null,
    'deployment marker source cannot execute injected PHP' => $malicious_marker_read === null && !$malicious_marker_executed,
    'uppercase commit SHA is rejected' => $normalize_method->invoke($controller, $marker_with_uppercase_sha) === null,
    'non-string workflow run ID is rejected' => $normalize_method->invoke($controller, $marker_with_integer_run_id) === null,
    'invalid calendar timestamp is rejected' => $normalize_method->invoke($controller, $marker_with_invalid_date) === null,
    'workflow uses read-only repository permissions' => str_contains($workflow_source, "permissions:\n  contents: read"),
    'workflow serializes production deploys without cancellation' => str_contains($workflow_source, "concurrency:\n  group: deploy-cpanel-ftp-production\n  cancel-in-progress: false"),
    'workflow pins the PHP runtime to 8.3' => str_contains($workflow_source, "php-version: '8.3'"),
    'lint and deterministic tests run before both FTP deploys' => $lint_position !== false
        && $tests_position !== false
        && $app_deploy_position !== false
        && $themes_deploy_position !== false
        && $lint_position < $tests_position
        && $tests_position < $app_deploy_position
        && $app_deploy_position < $themes_deploy_position,
    'marker is generated only after runtime verification' => $verify_position !== false
        && $generate_marker_position !== false
        && $publish_marker_position !== false
        && $themes_deploy_position < $verify_position
        && $verify_position < $generate_marker_position
        && $generate_marker_position < $publish_marker_position,
    'marker is uploaded separately to app config' => str_contains($workflow_source, 'local-dir: ./deployment-marker/')
        && str_contains($workflow_source, 'server-dir: /public_html/app/config/')
        && str_contains($workflow_source, 'state-name: .ftp-deploy-sync-state-deployment-marker.json'),
    'live smoke check requires one complete authenticated credential pair' => str_contains($smoke_workflow_source, 'mode=ops')
        && str_contains($smoke_workflow_source, 'mode=forever')
        && str_contains($smoke_workflow_source, 'No complete authenticated production smoke-check credentials are configured.')
        && str_contains($smoke_workflow_source, 'exit 1')
        && !str_contains($smoke_workflow_source, 'Skipping smoke check'),
    'live smoke check authenticates outside the URL query string' => str_contains($smoke_workflow_source, '--header "X-FCC-Ops-Key: ${FCC_OPS_READONLY_KEY}"')
        && !str_contains($smoke_workflow_source, '--data-urlencode "key=${FCC_OPS_READONLY_KEY}"'),
    'automatic smoke check verifies the exact deployment SHA and run ID' => str_contains($smoke_workflow_source, '.data.deployment.github_sha == $expected_sha')
        && str_contains($smoke_workflow_source, '.data.deployment.github_run_id == $expected_run_id')
        && str_contains($smoke_workflow_source, 'github.event.workflow_run.head_sha')
        && str_contains($smoke_workflow_source, 'github.event.workflow_run.id'),
    'manual smoke check verifies the checked-out workflow SHA' => str_contains($smoke_workflow_source, "|| github.sha }}"),
    'failed deployments make the smoke job fail instead of appear skipped' => str_contains($smoke_workflow_source, "github.event.workflow_run.conclusion != 'success'")
        && str_contains($smoke_workflow_source, 'exit 1'),
    'fallback smoke check performs a real authenticated runtime verification' => str_contains($smoke_workflow_source, 'Fallback smoke check authenticated Forever runtime')
        && str_contains($smoke_workflow_source, '--header "X-FCC-Forever-Sync-Key: ${FCC_FOREVER_SYNC_KEY}"')
        && str_contains($smoke_workflow_source, '.status == "success"')
        && str_contains($smoke_workflow_source, '.metric == "status"'),
    'production verification curl calls have bounded runtime' => str_contains($workflow_source, '--max-time 30')
        && substr_count($smoke_workflow_source, '--max-time 30') === 2,
];

@unlink($marker_path);
@unlink($execution_sentinel_path);
@rmdir($temporary_config);
@rmdir($temporary_root);

$failed = [];

foreach($assertions as $description => $passed) {
    if(!$passed) {
        $failed[] = $description;
    }
}

if($failed) {
    fwrite(STDERR, "Readonly diagnostics and deployment marker checks failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Readonly diagnostics and deployment marker checks passed.\n";

/* /Custom code: FC-2026-09-11 */
