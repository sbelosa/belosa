<?php
/* Custom code: FC-2026-10-14: finance route authorization and CSV regression guards. */
namespace Altum {
    class Router { public static array $controller_settings = ['has_view' => true]; }
    class Authentication {
        public static bool $admin = true;
        public static bool $logged = true;
        public static function guard(): void { if (!self::$logged) throw new \RuntimeException('login_redirect'); }
        public static function is_admin(): bool { return self::$logged && self::$admin; }
    }
    class Title { public static function set($title): void { $GLOBALS['finance_title'] = $title; } }
    class Event { public static function add_content($content, $place): void { $GLOBALS['finance_event'] = [$content,$place]; } }
    class View {
        public function __construct($path, $vars) { $GLOBALS['finance_view_path'] = $path; }
        public function run($vars): string { $GLOBALS['finance_view_data'] = $vars; return 'safe_view_fixture'; }
    }
}
namespace Altum\Controllers {
    class Controller {
        public array $params = ['admin'];
        public object $user;
        public function __construct() { $this->user = (object)['user_id' => 1]; }
        public function add_view_content($key, $content): void { $GLOBALS['finance_rendered'][$key] = $content; }
    }
    function header(string $value, bool $replace = true, int $code = 0): void {
        $key = strtolower(explode(':', $value, 2)[0]);
        $GLOBALS['finance_headers'][$key] = $value;
        if($code) $GLOBALS['finance_code'] = $code;
    }
    function http_response_code(?int $code = null): int {
        if($code !== null) $GLOBALS['finance_code'] = $code;
        return $GLOBALS['finance_code'];
    }
}
namespace {
    define('ALTUMCODE',66);
    $testTemp=sys_get_temp_dir().'/fcc-finance-guards-'.bin2hex(random_bytes(5)).'/';
    mkdir($testTemp.'helpers',0700,true);
    mkdir($testTemp.'css',0700,true);
    file_put_contents($testTemp.'helpers/fcc_admin_finance.php','<?php /* Dashboard is stubbed below. */');
    file_put_contents($testTemp.'css/fcc-admin-finance.css','/* Test asset. */');
    register_shutdown_function(static function()use($testTemp):void {
        unlink($testTemp.'helpers/fcc_admin_finance.php');unlink($testTemp.'css/fcc-admin-finance.css');
        rmdir($testTemp.'helpers');rmdir($testTemp.'css');rmdir($testTemp);
    });
    define('APP_PATH',$testTemp);
    define('ASSETS_PATH',$testTemp);
    define('ASSETS_FULL_URL','https://example.invalid/assets/');
    function fcc_partner_enabled(): bool { return true; }
    function fcc_t($text) { return $text; }
    function throw_404(): void { throw new \RuntimeException('404'); }
    function fcc_rollout_enter($uid): void { throw new \RuntimeException('unexpected_workspace_write'); }
    function fcc_team_refresh_notifications($uid): void { throw new \RuntimeException('unexpected_notification_write'); }
    function fcc_partner_query(...$args): never { throw new \RuntimeException('unexpected_database_write'); }
    function fcc_los_event(...$args): never { throw new \RuntimeException('unexpected_audit_write'); }
    function db(): never { throw new \RuntimeException('unexpected_database_access'); }
    function fcc_admin_finance_dashboard(array $filters): array {
        $GLOBALS['finance_dashboard_calls']++;
        $GLOBALS['finance_seen_filters'] = $filters;
        return $GLOBALS['finance_fixture'];
    }
    $sourceRoot=getenv('FCC_FINANCE_SOURCE')?:dirname(__DIR__);
    require $sourceRoot.'/app/controllers/Partner.php';
    $checks = [];
    function check(bool $ok, string $message): void {
        global $checks;
        if(!$ok) throw new \RuntimeException('FAIL: '.$message);
        $checks[] = $message;
    }
    function fixture(): array {
        $all = [];
        for($i=1;$i<=83;$i++) $all[] = ['user_id'=>$i,'name'=>'filtered_user_'.$i,'email'=>'user'.$i.'@example.invalid','state'=>'active','pro_access'=>true,'subscription'=>true,'processor'=>'stripe','currency'=>'EUR','due_at'=>'2026-11-01','last_paid_at'=>'2026-10-01','reason_code'=>'','next_retry_at'=>''];
        return ['available'=>true,'minor_digits'=>2,'filters'=>['currency'=>'EUR'],'monthly'=>[['month'=>'2026-10','gross_minor'=>120050,'refunded_minor'=>5000,'net_minor'=>115050,'paid_count'=>6,'failed_count'=>4,'failed_invoices'=>2,'affected_users'=>2,'recovered_count'=>1]],'accounts'=>array_slice($all,40,40),'export_accounts'=>$all];
    }
    function invoke(array $get, string $method='GET', bool $admin=true, bool $logged=true): array {
        $GLOBALS['finance_fixture'] = fixture();
        $GLOBALS['finance_dashboard_calls'] = 0;
        $GLOBALS['finance_headers'] = [];
        $GLOBALS['finance_code'] = 200;
        $GLOBALS['finance_rendered'] = [];
        \Altum\Router::$controller_settings = ['has_view'=>true];
        \Altum\Authentication::$admin = $admin;
        \Altum\Authentication::$logged = $logged;
        $_GET = $get;
        $_POST = ['action'=>'notification_rules','token'=>'malicious_fixture'];
        $_SERVER['REQUEST_METHOD'] = $method;
        $error = null;
        ob_start();
        try { (new \Altum\Controllers\Partner())->index(); } catch(\RuntimeException $e) { $error=$e->getMessage(); }
        $body = ob_get_clean();
        return ['body'=>$body,'error'=>$error,'calls'=>$GLOBALS['finance_dashboard_calls'],'headers'=>$GLOBALS['finance_headers'],'code'=>$GLOBALS['finance_code'],'has_view'=>\Altum\Router::$controller_settings['has_view']];
    }
    $r = invoke(['finance'=>'1'],'GET',false);
    check($r['error']==='404' && $r['calls']===0 && $r['body']==='', 'nonadmin finance rejected before data reads');
    $r = invoke(['finance'=>'1','export'=>'accounts'],'GET',false);
    check($r['error']==='404' && $r['calls']===0 && $r['body']==='', 'nonadmin CSV rejected before data reads');
    $r = invoke(['finance'=>'1'],'GET',false,false);
    check($r['error']==='login_redirect' && $r['calls']===0, 'anonymous finance guarded by login');
    foreach(['POST','PUT','PATCH','DELETE','HEAD','OPTIONS'] as $method) {
        $r = invoke(['finance'=>'1','export'=>'accounts'],$method);
        check($r['error']===null && $r['code']===405 && $r['calls']===0 && $r['body']==='' && !$r['has_view'], $method.' rejected without dashboard or mutations');
        check(($r['headers']['allow']??'')==='Allow: GET', $method.' publishes GET allow header');
        check(($r['headers']['cache-control']??'')==='Cache-Control: no-store, private' && str_contains($r['headers']['x-robots-tag']??'','noindex'), $method.' remains private and unindexed');
    }
    $r = invoke(['finance'=>'1','page'=>'2','q'=>'fixture']);
    check($r['error']===null && $r['calls']===1 && $r['code']===200, 'admin HTML reads dashboard once and bypasses workspace mutations');
    check(($r['headers']['cache-control']??'')==='Cache-Control: no-store, private' && ($r['headers']['x-robots-tag']??'')==='X-Robots-Tag: noindex, nofollow, noarchive', 'HTML response is private and unindexed');
    check(($GLOBALS['finance_view_path']??'')==='partner/admin-finance' && isset($GLOBALS['finance_view_data']['finance']), 'finance view receives expected contract');
    $r = invoke(['finance'=>'1','page'=>'2','q'=>'fixture','export'=>'accounts']);
    check($r['error']===null && $r['calls']===1 && !$r['has_view'], 'CSV export bypasses HTML wrapper');
    check(($r['headers']['content-type']??'')==='Content-Type: text/csv; charset=UTF-8' && ($r['headers']['x-content-type-options']??'')==='X-Content-Type-Options: nosniff', 'CSV content type and nosniff');
    check(str_starts_with($r['body'],"\xEF\xBB\xBF"), 'CSV UTF8 byte order mark');
    $rows = array_map(fn($line)=>str_getcsv($line,';','"',''),explode("\n",trim(substr($r['body'],3))));
    check(count($rows)===84 && $rows[1][0]==='1' && $rows[83][0]==='83', 'CSV includes all filtered rows rather than current page');
    check(($GLOBALS['finance_seen_filters']['q']??null)==='fixture' && ($GLOBALS['finance_seen_filters']['page']??null)==='2', 'CSV preserves selected filters');
    $formulas = ['=HYPERLINK("https://evil.invalid")','+cmd', '-cmd', '@SUM(1,2)', "\t=cmd", "\r @cmd", "\x00=cmd", "\x1f=cmd", "\xEF\xBB\xBF=cmd", "\ttext", "\nplain", 'normal text'];
    $GLOBALS['finance_fixture'] = fixture();
    foreach($formulas as $i=>$formula) $GLOBALS['finance_fixture']['export_accounts'][$i]['name'] = $formula;
    // Direct invocation retains injected fixture and exercises the exact CSV cell sanitizer.
    $_GET=['finance'=>'1','export'=>'accounts'];
    $_SERVER['REQUEST_METHOD']='GET';
    ob_start();
    (new \Altum\Controllers\Partner())->index();
    $body=ob_get_clean();
    $stream=fopen('php://temp','w+');fwrite($stream,substr($body,3));rewind($stream);fgetcsv($stream,null,';','"','');
    foreach($formulas as $i=>$formula) {
        $row=fgetcsv($stream,null,';','"','');
        check($row[1]===($i<count($formulas)-1?"'".$formula:$formula), 'CSV formula protection fixture '.($i+1));
    }
    fclose($stream);
    $r=invoke(['finance'=>'1','export'=>'monthly']);
    check(str_contains($r['body'],'1200,50;50,00;1150,50;6;4;2;2;1'), 'monthly CSV keeps exact monetary values and problem counts');
    echo json_encode(['status'=>'PASS','scope'=>'offline controller integration with stubbed auth and finance data','checks'=>count($checks),'details'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
}

/* /Custom code: FC-2026-10-14 */
