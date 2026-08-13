<?php
/* Custom code: FC-2026-08-13: Auditable local/cron Forever report importer */

if(PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const DEBUG = 0;
const MYSQL_DEBUG = 0;
const LOGGING = 1;
const CACHE = 0;
const ALTUMCODE = 66;

$workspace_root = dirname(__DIR__);
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? '';

require_once $workspace_root . '/app/init.php';

$file = $argv[1] ?? '';
$root_fbo_id = $argv[2] ?? '';
$root_name = $argv[3] ?? 'Glavni tim';
$user_id = max(1, (int) ($argv[4] ?? 1));
$report_period = $argv[5] ?? date('Y-m');

if($file === '' || !is_file($file)) {
    fwrite(STDERR, "Uporaba: php scripts/forever_business_import.php /putanja/izvjestaj.csv ROOT_FBO_ID 'Naziv tima' [USER_ID] [YYYY-MM]\n");
    exit(2);
}

try {
    $report = forever_business_parse_report($file, basename($file), $root_fbo_id, $root_name, $report_period);
    $result = forever_business_import_report($report, hash_file('sha256', $file), $user_id);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch(Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

/* /Custom code: FC-2026-08-13 */
