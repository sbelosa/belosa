<?php
/* Real MariaDB integration tests. Use only the disposable click_test database. */
define('ALTUMCODE', true);
define('DEBUG', false);
define('COOKIE_PATH', '/');
define('LOS_PRIVACY_HASH_SALT', 'local-fixture');
require __DIR__ . '/../app/helpers/MysqliDb.php';
require __DIR__ . '/../app/helpers/Link.php';
require __DIR__ . '/../app/helpers/automations.php';
require __DIR__ . '/../app/helpers/fcc_ai.php';
function vip_funnel_get_public_qualification_signal_payload($user, $start) { return ['total'=>0]; }
function fc_get_user_main_biolink_id($user) { return 0; }
eval('namespace Altum\\Controllers; class Controller {}');
require __DIR__ . '/../app/controllers/admin/AdminLeaderOperatingSystemLeader.php';
mysqli_report(MYSQLI_REPORT_OFF);
$connection = new mysqli(getenv('FCC_TEST_DB_HOST') ?: 'fcc-click-test-db', 'root', getenv('FCC_TEST_DB_PASSWORD') ?: 'local-click-test-only', 'click_test');
if($connection->connect_errno) throw new RuntimeException('Test database unavailable');
$connection->set_charset('utf8mb4');
register_shutdown_function(function() { if(database()->error) fwrite(STDERR, 'TEST DB ERROR: '.database()->error."\n"); });
$helper = new \Altum\Helpers\MysqliDb($connection);
$helper->returnType = 'object';
function db() { return $GLOBALS['helper']; }
function database() { return $GLOBALS['connection']; }
function get_date() { return gmdate('Y-m-d H:i:s'); }
function input_clean($value, $length = 9999) { return $value === null ? null : substr(trim($value), 0, $length); }
function get_ip() { return $GLOBALS['test_ip'] ?? '192.0.2.1'; }
function get_this_device_type() { return 'desktop'; }
function get_whichbrowser() { return (object) ['device' => (object) ['type' => 'desktop'], 'browser' => (object) ['name' => 'Test'], 'os' => (object) ['name' => 'TestOS']]; }
function get_maxmind_reader_city() { return new class { public function get($ip) { return []; } }; }
function check($condition, $label) { if(!$condition) throw new RuntimeException('FAIL: ' . $label . ' / DB: ' . database()->error); echo "PASS: {$label}\n"; }
function value($sql) { $r = database()->query($sql); if(!$r) throw new RuntimeException(database()->error); return $r->fetch_row()[0]; }
function visitor($key, $ip = '192.0.2.1') { $_COOKIE['fc_funnel_visitor_key'] = str_pad($key, 12, '_'); $GLOBALS['test_ip'] = $ip; }
function click($block, $url, $user = 922, $source = 'biolink_block', $type = 'forever_outbound') {
    return fc_process_monitored_forever_click(['user_id' => $user, 'biolink_block_id' => $block, 'source_type' => $source, 'click_type' => $type, 'destination_url' => $url]);
}
$_SERVER['HTTP_USER_AGENT'] = 'FCC Integration Test';
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'hr';
if(($argv[1] ?? '') === '--worker') {
    visitor('concurrent-visitor', '192.0.2.99');
    $result = click(1, 'https://thealoeveraco.shop/concurrent', 999);
    echo json_encode($result) . "\n";
    exit;
}
foreach(['track_links','biolinks_blocks','forever_click_integrity_accepts','forever_click_integrity_suspicious'] as $table) database()->query("DROP TABLE IF EXISTS {$table}");
database()->query("CREATE TABLE biolinks_blocks (biolink_block_id INT UNSIGNED PRIMARY KEY, link_id INT UNSIGNED, type VARCHAR(64), clicks INT DEFAULT 0) ENGINE=InnoDB");
database()->query("INSERT INTO biolinks_blocks VALUES (1,2417,'link',0),(2,2417,'link_discount',0),(3,2417,'link_forever_shop',0)");
database()->query("CREATE TABLE track_links (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED, link_id INT UNSIGNED NULL, biolink_block_id INT UNSIGNED NULL, project_id INT NULL, visitor_key VARCHAR(64) NULL, continent_code VARCHAR(8) NULL, country_code VARCHAR(8) NULL, city_name VARCHAR(128) NULL, os_name VARCHAR(64) NULL, browser_name VARCHAR(64) NULL, referrer_host VARCHAR(256) NULL, referrer_path TEXT NULL, device_type VARCHAR(32) NULL, browser_language VARCHAR(8) NULL, utm_source VARCHAR(128) NULL, utm_medium VARCHAR(128) NULL, utm_campaign VARCHAR(128) NULL, is_unique TINYINT DEFAULT 1, datetime DATETIME, INDEX(user_id), INDEX(datetime)) ENGINE=InnoDB");
fc_ensure_forever_click_integrity_tables();
function legacy($block, $url, $key, $time, $with_track = true) {
    $id = db()->insert('forever_click_integrity_accepts', ['user_id'=>922,'biolink_block_id'=>$block,'source_type'=>'biolink_block','click_type'=>'forever_outbound','target_signature'=>str_repeat('a',64),'target_label'=>'fixture','destination_url'=>$url,'visitor_key'=>$key,'identity_hash'=>fc_get_privacy_hash('vk|'.$key),'accepted_datetime'=>$time,'last_attempt_datetime'=>$time]);
    if($with_track) db()->insert('track_links',['user_id'=>922,'biolink_block_id'=>$block,'visitor_key'=>$key,'is_unique'=>1,'datetime'=>$time]);
    return $id;
}
legacy(1,'https://thealoeveraco.shop/historical','legacy-valid',get_date());
legacy(1,'https://example.org/?id=123','legacy-bad__',gmdate('Y-m-d H:i:s',time()-10));
legacy(1,'https://thealoeveraco.shop/orphan','legacy-orphan',gmdate('Y-m-d H:i:s',time()-20),false);
db()->insert('track_links',['user_id'=>922,'biolink_block_id'=>2,'is_unique'=>1,'datetime'=>'2026-01-01 00:00:00']);
fc_ensure_forever_click_qualification_schema();
check(value("SELECT fcc_click_kind FROM track_links WHERE visitor_key='legacy-valid'")==='app_shop','historical accepted ordinary button is reconciled');
check(value("SELECT fcc_click_kind FROM track_links WHERE visitor_key='legacy-bad__'")==='none','invalid historical target is not qualified');
check(value("SELECT fcc_click_kind FROM track_links WHERE datetime='2026-01-01 00:00:00'")==='app_shop','older historical classification retained');
foreach(['https://thealoeveraco.shop/x','https://www.foreverliving.com/shop/hrv','https://www.flpshop.ba/x','https://foreveralbania.com/','https://shop.foreverliving.com/x'] as $url) check(\Altum\Link::is_monitored_forever_destination_url($url),'allowed '.$url);
foreach(['https://evilforeverliving.com/x','https://foreverliving.com.evil.test/x','https://example.com/?id=12','https://example.com/?fboId=12','https://foreverliving.com@evil.test/x','https://evil.test/foreverliving.com','ftp://foreverliving.com/x','https://foreverliving.com:444/x'] as $url) check(!\Altum\Link::is_monitored_forever_destination_url($url),'reject '.$url);
visitor('new-person-1');
check(click(1,'https://thealoeveraco.shop/plain')['accepted'],'ordinary Forever button earns first click');
check(!click(2,'https://thealoeveraco.shop/special')['accepted'],'same visitor second target does not earn twice');
visitor('new-person-2','192.0.2.2');
check(!click(1,'https://example.com/?id=123')['accepted'],'invalid destination ignored');
check(click(2,'https://thealoeveraco.shop/valid')['accepted'],'invalid click does not consume qualification');
visitor('legacy-bad__','192.0.2.3');
check(click(2,'https://thealoeveraco.shop/valid')['accepted'],'historical unqualified acceptance does not consume slot');
visitor('legacy-orphan','192.0.2.4');
check(click(2,'https://thealoeveraco.shop/valid')['accepted'],'orphan acceptance does not consume slot');
visitor('network-test-2','192.0.2.2');
check(!click(2,'https://thealoeveraco.shop/valid')['accepted'],'existing seven-day network rule unchanged');
visitor('blog-visitor-1','192.0.2.5');
$_REQUEST['utm_medium']='blog_cta_product';
check(click(null,'https://foreverliving.com/shop/product',922,'blog_cta','blog_forever_product')['accepted'],'blog click accepted');
visitor('registration-1','192.0.2.6');
check(click(3,'https://foreverliving.com/join',922,'biolink_block','link_forever_shop')['accepted'],'registration click accepted');
$q=\Altum\Link::get_fcc_results_qualified_click_condition_sql('t','b');
$app=\Altum\Link::get_fcc_click_channel_condition_sql('t','app');
$blog=\Altum\Link::get_fcc_click_channel_condition_sql('t','blog');
$shop=\Altum\Link::get_forever_shop_click_condition_sql('t','b',"'link_discount'");
$reg=\Altum\Link::get_forever_registration_click_condition_sql('t','b',"'link_forever_shop'");
$total=(int)value("SELECT COUNT(*) FROM track_links t WHERE t.is_unique=1 AND {$q}");
check($total===8,'expected immutable qualification total');
check($total===(int)value("SELECT SUM({$app})+SUM({$blog}) FROM track_links t WHERE t.is_unique=1"),'app plus blog equals shared total');
check($total===(int)value("SELECT SUM({$shop})+SUM({$reg}) FROM track_links t WHERE t.is_unique=1"),'shop plus registration equals shared total');
database()->query("UPDATE biolinks_blocks SET type='heading' WHERE biolink_block_id=1");
database()->query("DELETE FROM biolinks_blocks WHERE biolink_block_id=2");
check((int)value("SELECT COUNT(*) FROM track_links t WHERE t.is_unique=1 AND {$q}")===$total,'editing and deleting buttons preserves earned clicks');
db()->insert('track_links',['user_id'=>922,'is_unique'=>1,'utm_medium'=>'blog_cta_product','datetime'=>get_date()]);
check((int)value("SELECT COUNT(*) FROM track_links t WHERE t.is_unique=1 AND {$q}")===$total,'forged UTM on non-Forever event cannot qualify');
visitor('rollback-visitor','192.0.2.7');
database()->query("CREATE TRIGGER fail_qualified_insert BEFORE INSERT ON track_links FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='intentional test failure'");
check(!click(1,'https://thealoeveraco.shop/rollback')['accepted'],'failed track insertion reports no acceptance');
check((int)value("SELECT COUNT(*) FROM forever_click_integrity_accepts WHERE visitor_key='rollback-visitor'")===0,'failed track insert rolls back acceptance and slot');
database()->query("DROP TRIGGER fail_qualified_insert");
check(click(1,'https://thealoeveraco.shop/rollback')['accepted'],'retry after failed write is accepted');
$commands=[];
for($i=0;$i<4;$i++) {
    $pipes=[];$proc=proc_open([PHP_BINARY,__FILE__,'--worker'],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
    $commands[]=[$proc,$pipes];
}
$accepted=0;
foreach($commands as [$proc,$pipes]) { $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($proc);check($exit===0,'concurrent worker completed '.$err);$accepted+=(int)(json_decode(trim($out),true)['accepted']??false); }
check($accepted===1,'four concurrent requests earn exactly one click');
check((int)value("SELECT COUNT(*) FROM track_links WHERE user_id=999 AND fcc_click_kind='app_shop'")===1,'concurrent acceptance has exactly one qualified record');
$public = fcc_ai_get_user_public_visibility_signal_snapshot(922);
$coach = fcc_ai_get_user_growth_signal_snapshot(922);
check($public['qualified_clicks_30d'] === $coach['growth_signal_30d'], 'Coach and public 30-day signal agree');
check($public['qualified_clicks_7d'] === $coach['growth_signal_7d'], 'Coach and public seven-day signal agree');
check($public['qualified_clicks_30d'] === $public['app_clicks_30d'] + $public['blog_clicks_30d'], 'public breakdown reconciles');
database()->query("CREATE TABLE IF NOT EXISTS links (link_id INT PRIMARY KEY, type VARCHAR(32))");
$controller = new \Altum\Controllers\AdminLeaderOperatingSystemLeader();
$method = new ReflectionMethod($controller, 'get_chart_series');
$chart = $method->invoke($controller, 922, gmdate('Y-m-d 00:00:00', strtotime('-29 days')), 30, []);
check(array_sum($chart['app_shop_clicks']) + array_sum($chart['blog_clicks']) === $public['qualified_clicks_30d'], 'live LOS chart query agrees with public and Coach totals');
$activity = fc_get_forever_click_activity(922);
check(count($activity) > 0 && count($activity) <= 20, 'personal activity is bounded and includes recorded events');
check(count(array_filter($activity, fn($e) => !$e['accepted'])) > 0, 'personal activity explains non-counted attempts');
check(fc_get_forever_click_activity(123456) === [], 'personal activity never includes another account events');
check(array_keys($activity[0]) === ['time','source','accepted','title','text'], 'activity exposes no visitor identity, IP, target or browser data');
check(!str_contains(json_encode($activity), 'nabiti'), 'legacy and new explanations remain neutral');
check(fc_forever_click_explanation('same_network_signature')['title'] === 'Podudaranje mreže i uređaja', 'network exclusion is distinguished from a known visitor');
check(fc_forever_click_explanation('unknown')['title'] === 'Nije dodatno pribrojeno', 'unknown reasons are not invented');
$network_reason = value("SELECT reason_key FROM forever_click_integrity_suspicious WHERE visitor_key='network-test-2'");
check($network_reason === 'same_network_signature', 'same-target network exclusion retains the actual network reason');
echo "All click qualification integration tests passed.\n";
