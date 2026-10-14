<?php
declare(strict_types=1);
/* Custom code: FC-2026-10-14: actual helpers, synthetic cache facts and isolated product gate regressions. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
umask(0077);
function ig_need(bool $ok,string $reason):void{if(!$ok)throw new RuntimeException($reason);}
function db(){throw new RuntimeException('Forbidden DB call');}
function ig_private_root(string $root):void{
    ig_need(dirname($root)===sys_get_temp_dir() && preg_match('/\Afcc-identity-gate-[a-f0-9]{12}(?:-corrupt)?\z/D',basename($root))===1 && !is_link($root) && realpath($root)===$root,'Private fixture root');
}
function ig_boot(string $root):void{
    ig_private_root($root);define('ALTUMCODE',66);define('APP_PATH',$root.'/app/');putenv('FCC_MARKET_POLICY_ENABLED=1');
    require APP_PATH.'helpers/fcc_shop_catalog.php';require APP_PATH.'helpers/fcc_forever_routing.php';
    ig_need(FccShopCatalog::directory()===$root.'/local/shop-catalog/','Cache is isolated');
}
function ig_snapshot(string $url,array $offer):array{
    return ['source'=>$url,'final_url'=>$url,'market'=>$offer['market'],'sku'=>$offer['sku'],'name'=>$offer['name']??'Vital5 Freedom','heading'=>$offer['name']??'Vital5 Freedom',
        'status'=>'verified_product','http'=>200,'checked_at'=>gmdate(DATE_ATOM),'price'=>null,'currency'=>null];
}
function ig_post(array $registry,string $family):object{
    foreach($registry['posts'] as $id=>$post)if($post['family']===$family)return (object)['url'=>$family,'blog_post_id'=>(int)$id];
    throw new RuntimeException('Actual source post absent');
}
function ig_offer(array $registry,string $family,string $market):array{
    foreach($registry['offers'] as $url=>$offer)if($offer['family']===$family && $offer['market']===$market)return [$url,$offer];
    throw new RuntimeException('Actual source offer absent');
}
function ig_copy(string $source,string $root,array $paths,array $pins):void{
    ig_need(!file_exists($root),'Fresh fixture root');mkdir($root,0700);ig_private_root($root);
    foreach($paths as $p){$target=$root.'/'.$p;if(!is_dir(dirname($target)))mkdir(dirname($target),0700,true);ig_need(copy($source.'/'.$p,$target) && hash_file('sha256',$target)===$pins[$p],'Copied source bytes');}
}
function ig_cleanup(?string $root):void{
    if(!$root || !file_exists($root))return;ig_private_root($root);
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $entry){$p=$entry->getPathname();ig_need(str_starts_with($p,$root.'/'),'Owned cleanup path');if($entry->isLink() || !$entry->isDir())ig_need(unlink($p),'Temp file cleanup');else ig_need(rmdir($p),'Temp directory cleanup');}
    ig_need(rmdir($root),'Temp root cleanup');
}
if(($argv[1]??'')==='--corrupt-family'){
    try{
        ig_need($argc===5,'Child arguments');$root=$argv[2];$url=$argv[3];$family=$argv[4];ig_private_root($root);
        $path=$root.'/app/config/fcc_product_identities.json';$data=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$offer=$data['offers'][$url];
        $data['offers'][$url]['family']='forever-aloe-peaches';file_put_contents($path,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        ig_boot($root);$registry=FccShopCatalog::identities();$snapshot=ig_snapshot($url,$offer);FccShopCatalog::save($url,$snapshot);
        ig_need(FccShopCatalog::matchesIdentity($url,$snapshot),'Corrupt family fixture retains other identity fields');
        $actual=fcc_forever_product_identity_allows(ig_post($registry,$family),$offer['market'],$url);
        echo json_encode(['status'=>$actual===false?'PASS':'FAIL','actual'=>$actual,'expected'=>false,'controlled_registry_corruption'=>true])."\n";exit($actual===false?0:1);
    }catch(Throwable $e){echo json_encode(['status'=>'FAIL','reason'=>'child_fixture_failed'])."\n";exit(1);}
}
$source=dirname(__DIR__);$root=null;$childRoot=null;$result=['status'=>'FAIL'];$checks=[];$pins=[];
try{
    ig_need($argc===1,'Run from repository scripts without arguments');
    $paths=['app/helpers/fcc_shop_catalog.php','app/helpers/fcc_forever_routing.php','app/helpers/fcc_product_blocks.php','app/helpers/fcc_owned_destinations.php','app/config/fcc_product_identities.json','app/config/fcc_forever_markets.json','app/config/fcc_market_policy.json'];
    foreach($paths as $p){ig_need(is_file($source.'/'.$p) && !is_link($source.'/'.$p),'Regular source dependency');$pins[$p]=hash_file('sha256',$source.'/'.$p);}
    $root=sys_get_temp_dir().'/fcc-identity-gate-'.bin2hex(random_bytes(6));ig_copy($source,$root,$paths,$pins);ig_boot($root);
    $registry=FccShopCatalog::identities();[$url,$offer]=ig_offer($registry,'forever-aloe-vera-gel','hr');$post=ig_post($registry,$offer['family']);$good=ig_snapshot($url,$offer);
    ig_need(FccShopCatalog::matchesIdentity($url,$good),'Normal fixture identity');
    preg_match('~^/shop/([^/]+)/([^/]+)/~',(string)parse_url($url,PHP_URL_PATH),$locale);
    $alias='https://foreverliving.com/shop/'.$locale[1].'/'.$locale[2].'/products/'.$offer['sku'].'-ISOLATED-SAME-SKU-CONTROL';
    $assert=static function(string $label,bool $actual,bool $expected)use(&$checks):void{$checks[]=['case'=>$label,'pass'=>$actual===$expected,'actual'=>$actual,'expected'=>$expected];};
    foreach(['exact'=>$url,'same_sku_alias'=>$alias] as $kind=>$base){
        $assert($kind.'_missing_cache',fcc_forever_product_identity_allows($post,'hr',$base),true);
        FccShopCatalog::save($base,$good);$assert($kind.'_valid_identity',fcc_forever_product_identity_allows($post,'hr',$base),true);
        foreach(['wrong_market_redirect','sku_mismatch','offer_identity_mismatch','offer_market_mismatch','article_identity_mismatch','category','not_found'] as $status){$s=$good;$s['status']=$status;FccShopCatalog::save($base,$s);$assert($kind.'_confirmed_'.$status,fcc_forever_product_identity_allows($post,'hr',$base),false);}
        foreach([['fetch_failed',0],['no_product_data',200],['unverified',200],['http_error',500],['http_error',429],['ambiguous_products',200]] as [$status,$http]){$s=$good;$s['status']=$status;$s['http']=$http;FccShopCatalog::save($base,$s);$assert($kind.'_transient_'.$status.'_'.$http,fcc_forever_product_identity_allows($post,'hr',$base),true);}
        foreach([404,410] as $http){$s=$good;$s['status']='http_error';$s['http']=$http;FccShopCatalog::save($base,$s);$assert($kind.'_hard_http_'.$http,fcc_forever_product_identity_allows($post,'hr',$base),false);}
        $s=$good;$s['sku']='999999';FccShopCatalog::save($base,$s);$assert($kind.'_verified_wrong_sku',fcc_forever_product_identity_allows($post,'hr',$base),false);
        $s=$good;$s['name']='A completely different product';$s['heading']=$s['name'];FccShopCatalog::save($base,$s);$assert($kind.'_verified_wrong_name',fcc_forever_product_identity_allows($post,'hr',$base),false);
        $s=$good;$s['market']='de';FccShopCatalog::save($base,$s);$assert($kind.'_verified_wrong_market',fcc_forever_product_identity_allows($post,'hr',$base),false);
    }
    $assert('base_wrong_country',fcc_forever_product_identity_allows($post,'de',$url),false);$assert('missing_market',fcc_forever_product_identity_allows($post,null,$url),false);
    $withdrawn=[['forever-aloe-moisturizing-lotion','ae'],['forever-argi','ae'],['forever-bright','ae'],['forever-ultra','ae'],['forever-plant-protein','pl'],['forever-sensatiable','si'],['mini-touch-of-forever','au'],['vital5','lu'],['vital5','nl']];
    $sourceIds=[];
    foreach($withdrawn as [$family,$market]){
        $p=ig_post($registry,$family);ig_need($p->blog_post_id>0,'Actual source post ID');$sourceIds[$family.':'.$market]=$p->blog_post_id;
        [$base,$o]=ig_offer($registry,$family,$market);$s=ig_snapshot($base,$o);FccShopCatalog::save($base,$s);
        $assert('withdrawn_expected_'.$family.':'.$market,fcc_forever_product_identity_allows($p,$market,$base),false);
    }
    $badPost=ig_post($registry,'fab-forever-active-boost');[$badUrl,$badOffer]=ig_offer($registry,'fab-energy-drink','ba');FccShopCatalog::save($badUrl,ig_snapshot($badUrl,$badOffer));
    $assert('wrong_formula_removed_ba_mapping',fcc_forever_product_identity_allows($badPost,'ba',$badUrl),false);
    $wrong=FccShopCatalog::parse('<h1>Control</h1>',$url,'https://foreverliving.com/shop/deu/de-de/products/815-FOREVER-ALOE-VERA-GEL',200);ig_need($wrong['status']==='wrong_market','Actual parser status');FccShopCatalog::record($url,$wrong);
    $assert('actual_parser_wrong_market_status',fcc_forever_product_identity_allows($post,'hr',$url),false);
    $childRoot=$root.'-corrupt';ig_copy($source,$childRoot,$paths,$pins);
    $proc=proc_open([PHP_BINARY,__FILE__,'--corrupt-family',$childRoot,$url,$offer['family']],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);ig_need(is_resource($proc),'Child process');fclose($pipes[0]);
    $stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($proc);ig_need($stderr==='' && strlen($stdout)<2000,'Bounded child output');$child=json_decode($stdout,true,512,JSON_THROW_ON_ERROR);
    $assert('reviewed_offer_wrong_family_child',$exit===0 && $child['status']==='PASS',true);
    foreach($pins as $p=>$sha)ig_need(hash_file('sha256',$source.'/'.$p)===$sha,'Source changed during fixture');
    ig_need(count($checks)===54,'Complete fixture checks');$failed=array_values(array_filter($checks,static fn($r)=>!$r['pass']));
    $result=['status'=>$failed?'FAIL':'PASS','checks'=>54,'passed'=>54-count($failed),'failures'=>$failed,'source_sha256'=>$pins,'source_unchanged'=>true,'withdrawn_expected_cases'=>9,'actual_source_post_ids'=>$sourceIds,'network_calls'=>0,'db_calls'=>0,'app_source_mutations'=>0,
        'limits'=>['Snapshots are synthetic current registry facts, not evidence of vendor stock.','This proves the gate return value, not production ownership or frontend contact rendering.']];
}catch(Throwable $e){$result=['status'=>'FAIL','checks_completed'=>count($checks),'reason'=>$e instanceof RuntimeException?$e->getMessage():'fixture_failed','source_sha256'=>$pins];}
finally{
    try{ig_cleanup($childRoot);ig_cleanup($root);$result['temporary_files_removed']=true;}catch(Throwable $e){$result['status']='FAIL';$result['temporary_files_removed']=false;$result['cleanup_reason']='owned_temp_cleanup_failed';}
}
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
exit($result['status']==='PASS'?0:1);
/* /Custom code: FC-2026-10-14 */
