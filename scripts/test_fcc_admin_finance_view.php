<?php
/* Custom code: FC-2026-10-14: finance HTML escaping and error state regression guards. */
namespace Altum { class Authentication { public static bool $admin=true;public static function is_admin(): bool{return self::$admin;} } }
namespace {
    define('ALTUMCODE',66);
    function fcc_t($text){return $text;}
    function url($path):string{return 'https://example.invalid/'.$path;}
    $sourceRoot=getenv('FCC_FINANCE_SOURCE')?:dirname(__DIR__);
    $viewPath=$sourceRoot.'/themes/altum/views/partner/admin-finance.php';
    $checks=[];
    function check(bool $ok,string $message):void {
        global $checks;
        if(!$ok)throw new \RuntimeException('FAIL: '.$message);
        $checks[]=$message;
    }
    function render(string $path,array $finance,?string $error=null):string {
        $data=(object)['finance'=>$finance,'finance_error'=>$error];
        ob_start();require $path;return ob_get_clean();
    }
    $payload='"><img src=x onerror=alert(1)><script>alert(2)</script>&';
    $fixture=[
        'available'=>true,'minor_digits'=>2,'filters'=>['currency'=>'EUR','months'=>12,'selected_month'=>'2026-09','search'=>$payload,'state'=>'payment_risk'],
        'currencies'=>['EUR',$payload],'generated_at'=>'2026-10-14T12:00:00Z',
        'summary'=>['pro_access'=>5,'payment_risk'=>1],'warnings'=>[['message'=>$payload,'count'=>1]],
        'monthly'=>[
            ['month'=>'2026-07','label'=>$payload,'net_minor'=>100,'gross_minor'=>100,'refunded_minor'=>0],
            ['month'=>'2026-08','label'=>$payload,'net_minor'=>-100,'gross_minor'=>0,'refunded_minor'=>100],
            ['month'=>'2026-09','label'=>$payload,'net_minor'=>0,'gross_minor'=>0,'refunded_minor'=>0,'failed_count'=>3,'affected_users'=>1,'recovered_count'=>1],
        ],
        'selected_month_totals'=>['net_minor'=>0],'totals'=>['net_minor'=>0],
        'top_reasons'=>[['code'=>$payload,'count'=>1]],
        'accounts'=>[['user_id'=>'1'.$payload,'name'=>$payload,'email'=>$payload,'state'=>'payment_risk','processor'=>$payload,'currency'=>$payload,'stripe_status'=>$payload,'reason_code'=>$payload,'failed_at'=>'not-a-date'.$payload,'due_at'=>'not-a-date'.$payload,'last_paid_at'=>null,'next_retry_at'=>'not-a-date'.$payload,'grace_until'=>'not-a-date'.$payload,'pro_access'=>true,'estimate_minor'=>100]],
        'pagination'=>['page'=>2,'pages'=>3,'total'=>83],
    ];
    $html=render($viewPath,$fixture);
    check(str_contains($html,'Proba prema FCC evidenciji')&&!str_contains($html,'Potvrđeno probno razdoblje u Stripeu'),'trial label reflects FCC evidence and never claims Stripe confirmation');
    check(!str_contains($html,$payload),'raw untrusted HTML payload never emitted');
    check(str_contains($html,htmlspecialchars($payload,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')),'untrusted HTML is escaped as readable text');
    check(!str_contains($html,'value="'.$payload.'"'),'search input cannot break out of attribute');
    check(str_contains($html,'https://example.invalid/admin/user-view/1'),'user details link casts numeric ID');
    check(str_contains($html,'ff-bar-negative'),'negative net uses supported chart rendering');
    check(!preg_match('/(?:height|width|x|y)="(?:NaN|INF|-INF)/',$html),'chart coordinates remain finite');
    check(str_contains($html,'ff-pagination'),'pagination uses filtered result contract');
    check(str_contains($html,'search=%22%3E%3Cimg') && str_contains($html,'&amp;'),'links encode user search and escape query separators');
    check(str_contains($html,'Preuzmi račune u CSV') && str_contains($html,'export=accounts'),'filtered accounts export link exists');
    check(str_contains($html,'Pretplata za provjeru') && str_contains($html,'Izvor ih ne razlikuje pouzdano.'),'uncertain billing categories explained');
    $dom=new \DOMDocument();
    libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8">'.$html);libxml_clear_errors();
    $xpath=new \DOMXPath($dom);
    check($xpath->query('//script | //img | //@onerror | //@onload')->length===0,'parsed HTML contains no injected executable markup');
    check($xpath->query('//input[@name="search"]')->item(0)->getAttribute('value')===$payload,'parsed search field retains safe original text');
    check($xpath->query('//select[@id="ff-currency"]/option')->item(1)->getAttribute('value')===$payload,'currency option value is safely escaped');
    \Altum\Authentication::$admin=false;
    check(render($viewPath,$fixture)==='','view denies direct nonadmin rendering');
    \Altum\Authentication::$admin=true;
    $unavailable=render($viewPath,['available'=>false,'error'=>$payload],$payload);
    check(!str_contains($unavailable,$payload) && !str_contains($unavailable,'alert(2)'),'unavailable view never exposes raw technical error');
    check(str_contains($unavailable,'role="alert"') && !str_contains($unavailable,'ff-money-primary'),'unavailable view shows error without misleading money totals');
    $fixture['filters']['currency']=$payload;
    $fixture['filters']['selected_month']=$payload;
    $fixture['filters']['state']=$payload;
    $html=render($viewPath,$fixture);
    check(!str_contains($html,$payload),'malicious filter values remain escaped');
    $fixture['monthly']=[];
    $fixture['accounts']=[];
    $html=render($viewPath,$fixture);
    check(str_contains($html,'Nema računa za ovaj odabir.') && str_contains($html,'Za odabrano razdoblje nema dostupne mjesečne evidencije.'),'empty month and account datasets render without notices');
    echo json_encode(['status'=>'PASS','scope'=>'offline actual finance view with hostile synthetic values','checks'=>count($checks),'details'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
}

/* /Custom code: FC-2026-10-14 */
