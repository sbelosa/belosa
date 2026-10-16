<?php
/* Custom code: FC-2026-10-16: exercise actual HTML validation, controller persistence and editor round trips. */
namespace Altum {
    class Date { public static function get($value, $format) { return ''; } }
    class Language { public static $code = 'hr'; }
    class Response { public static function json($message, $status, $details = []) { throw new \TestReply($status, $message); } }
    class Plugin {
        public static function is_active($name) { return $name === 'pro-blocks'; }
        public static function get($name) { return (object) ['path' => $GLOBALS['plugin_path']]; }
    }
    class Csrf { public static function get() { return 'test'; } }
}
namespace Altum\Controllers { class Controller { public $user; } }
namespace {
    define('ALTUMCODE', 66);
    define('APP_PATH', dirname(__DIR__) . '/app/');
    require APP_PATH . 'helpers/FccCustomHtml.php';
    require APP_PATH . 'controllers/BiolinkBlockAjax.php';
    use Altum\Helpers\FccCustomHtml as Html;
    class TestReply extends Exception { public $status; public function __construct($status, $message) { $this->status=$status; parent::__construct($message); } }
    class FakeDb {
        public $writes=0, $fail=false, $throw=false, $stored=null, $predicates=[], $updatePredicates=[];
        function where($key, $value) { $this->predicates[$key]=$value; return $this; }
        function getOne($table) { $this->predicates=[]; return (object) ['link_id'=>12, 'user_id'=>7, 'biolink_block_id'=>44]; }
        function insert($table, $row) { ++$this->writes; if($this->throw) throw new RuntimeException('private database detail'); if($this->fail)return false; $this->stored=$row; return 44; }
        function update($table, $row) { $this->updatePredicates=$this->predicates; $this->predicates=[]; return (bool) $this->insert($table, $row); }
    }
    class FakeCache { public $deletions=0; function deleteItem($key) { ++$this->deletions; } }
    function db() { return $GLOBALS['fake_db']; }
    function cache() { return $GLOBALS['fake_cache']; }
    function settings() { return (object) ['links'=>(object)['biolinks_new_blocks_position'=>'bottom','google_static_maps_is_enabled'=>false]]; }
    function get_date() { return '2026-10-16 12:00:00'; }
    function query_clean($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
    function url($path) { return '/'.$path; }
    function l($key) { return $key; }
    function fcc_t($key, $fallback='') { return $fallback ?: $key; }
    function get_continents_array() { return []; }
    function get_countries_array() { return []; }
    function get_locale_languages_array() { return []; }
    function get_plan_feature_disabled_info() { return ''; }
    $checks=0;
    function check($ok, $label) { global $checks; ++$checks; if(!$ok) throw new RuntimeException($label); }
    function rejected($callback, $reason) { try { $callback(); } catch(InvalidArgumentException $e) { check($e->getMessage()===$reason, 'rejection reason '.$reason); return; } throw new RuntimeException('missing rejection '.$reason); }
    $GLOBALS['plugin_path']=sys_get_temp_dir().'/fcc-html-plugin-'.bin2hex(random_bytes(6)).'/';
    mkdir($GLOBALS['plugin_path']);
    file_put_contents($GLOBALS['plugin_path'].'pro_blocks.php', '<?php return ["custom_html"=>["max_length"=>16384,"themable"=>false]];');
    try {
        $config=require APP_PATH.'includes/biolink_blocks.php';
        check($config['custom_html']['max_length']===50000,'real configuration overrides plugin limit');
        foreach(['a','č','😀'] as $unit) {
            $html=str_repeat($unit,50000);
            check(Html::validate($html)===$html,'exact boundary '.$unit);
            $encoded=Html::encode(['html'=>$html,'display_cities'=>[]]);
            check(json_decode($encoded,true,512,JSON_THROW_ON_ERROR)['html']===$html,'JSON roundtrip '.$unit);
            check(strlen($encoded)<Html::MAX_SETTINGS_BYTES,'serialized boundary fits '.$unit);
            rejected(fn()=>Html::validate($html.$unit),'length');
        }
        foreach([null,[],42,"\xC3\x28"] as $bad) rejected(fn()=>Html::validate($bad),'invalid');
        check(Html::validate("  <p>\r\nč</p>\r ")==="  <p>\nč</p>\n ",'newline normalization preserves surrounding whitespace');
        check(Html::validate('')==='','empty allowed');
        rejected(fn()=>Html::encode(['html'=>'valid','display_cities'=>[str_repeat('x',1048576)]]),'bytes');
        rejected(fn()=>Html::encode(['html'=>"\xC3\x28"]),'invalid');
        foreach(['hr','en','sl','de','es','fr'] as $locale) {
            \Altum\Language::$code=$locale;
            foreach(['hint','length','invalid','bytes','save'] as $key) check(Html::message($key)!=='','localized message');
        }
        \Altum\Language::$code='hr';
        $controller=new \Altum\Controllers\BiolinkBlockAjax();
        $controller->user=(object)['user_id'=>7];
        $controller->biolink_blocks=$config;
        foreach(['create','update'] as $operation) {
            $method=new ReflectionMethod($controller,$operation.'_biolink_custom_html');
            $method->setAccessible(true);
            foreach(['ascii','croatian','emoji','over','invalid','bytes','db_false','db_throw'] as $case) {
                $GLOBALS['fake_db']=new FakeDb(); $GLOBALS['fake_cache']=new FakeCache();
                $value=match($case) { 'croatian'=>str_repeat('č',50000), 'emoji'=>str_repeat('😀',50000), 'over'=>str_repeat('a',50001), 'invalid'=>[], default=>str_repeat('a',50000) };
                $_POST=['link_id'=>12,'biolink_block_id'=>44,'html'=>$value,'name'=>'Naziv','display_cities'=>$case==='bytes'?str_repeat('x',1048576):''];
                $GLOBALS['fake_db']->fail=$case==='db_false'; $GLOBALS['fake_db']->throw=$case==='db_throw';
                try { $method->invoke($controller); throw new RuntimeException('missing response'); }
                catch(TestReply $reply) {
                    $error=in_array($case,['over','invalid','db_false','db_throw'],true)||($case==='bytes'&&$operation==='update');
                    check($reply->status===($error?'error':'success'),$operation.' '.$case.' response');
                    check(!str_contains($reply->getMessage(),'private database detail'),'private failure is hidden');
                    if(in_array($case,['over','invalid'],true)||($case==='bytes'&&$operation==='update')) check($GLOBALS['fake_db']->writes===0,'invalid content never writes');
                    if(!$error) {
                        check(json_decode($GLOBALS['fake_db']->stored['settings'],true)['html']===$value,'controller preserves complete source');
                        check($GLOBALS['fake_cache']->deletions===1,'cache cleared after success');
                        if($operation==='update') check($GLOBALS['fake_db']->updatePredicates===['biolink_block_id'=>44,'user_id'=>7],'update retains ownership');
                    } else check($GLOBALS['fake_cache']->deletions===0,'failed save does not clear cache');
                }
            }
        }
        // Render the actual update form, including a textarea terminator and entity spellings.
        $danger='</textarea><script>throw new Error("must stay text")</script>&amp;č😀';
        $row=(object)['biolink_block_id'=>44,'type'=>'custom_html','start_date'=>null,'end_date'=>null,'settings'=>(object)['html'=>$danger,'name'=>'&quot; onclick="bad"']];
        $data=(object)['biolink_blocks'=>$config];
        $renderer=new class { public $user; function render($row,$data) { ob_start(); include dirname(__DIR__).'/themes/altum/views/link/settings/biolink_blocks/custom_html/custom_html_update_form.php'; return ob_get_clean(); } };
        $renderer->user=(object)['plan_settings'=>(object)['temporary_url_is_enabled'=>false]];
        $html=$renderer->render($row,$data);
        $doc=new DOMDocument();libxml_use_internal_errors(true);$doc->loadHTML('<?xml encoding="UTF-8">'.$html);libxml_clear_errors();
        $fields=$doc->getElementsByTagName('textarea');
        check($fields->length===1,'editor stays a single textarea');
        check(in_array($fields->item(0)->textContent,[$danger,"\n".$danger],true),'editor source roundtrip preserves literal entities and closing textarea');
        check((bool) preg_match('/<textarea\b[^\n]*>\n/', $html),'HTML5 parser consumes an explicit separator newline rather than source content');
        foreach(["\n<p>x</p>","\n\n<p>x</p>","\r\n<p>x</p>"] as $leading) {
            $row->settings->html=$leading; $markup=$renderer->render($row,$data);
            preg_match('/<textarea\b[^\n]*>\n(.*?)<\/textarea>/s',$markup,$matched);
            $decoded=html_entity_decode($matched[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
            $browserValue=str_replace(["\r\n","\r"],"\n",$decoded);
            check($browserValue===str_replace(["\r\n","\r"],"\n",$leading),'HTML5 source retains every leading newline');
        }
        check($doc->getElementsByTagName('script')->length===0,'saved source does not execute in editor');
        check(!$fields->item(0)->hasAttribute('maxlength'),'no UTF16 limit truncates emoji');
        echo json_encode(['status'=>'PASS','checks'=>$checks],JSON_THROW_ON_ERROR).PHP_EOL;
    } finally { unlink($GLOBALS['plugin_path'].'pro_blocks.php');rmdir($GLOBALS['plugin_path']); }
}
/* /Custom code: FC-2026-10-16 */
