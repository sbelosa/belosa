<?php
/* Custom code: FC-2026-10-14: regression for the official retail label and family option identities. */
if(PHP_SAPI!=='cli')exit(1);
putenv('FCC_MARKET_POLICY_ENABLED=1');require dirname(__DIR__).'/app/helpers/fcc_shop_catalog.php';
$checks=0;
function verify(bool $ok,string $name):void{global $checks;$checks++;if(!$ok){fwrite(STDERR,'FAIL: '.$name.PHP_EOL);exit(1);}}
$url='https://foreverliving.com/shop/hrv/hr-hr/products/673-LOGIC-KIT';
$page=static fn(string $price,string $sku='673',string $title='Logic by Forever Skincare Kit'):string=>'<h1 class="product-title">'.$title.'</h1><div class="item-number"><span>Artikal#</span><span>'.$sku.'</span></div><div class="prod-price">'.$price.'</div>';
$label='<span class="price">Maloprodajna cijena:</span>';$amount='<span class="price">90,09€ *</span>';
foreach([$label.$amount,$amount.$label,$amount] as $markup){$data=FccShopCatalog::parse($page($markup),$url,$url,200);verify($data['status']==='verified_product','retail label is not another amount');verify($data['price']==='90.09'&&$data['currency']==='EUR','one explicit public retail amount');verify(FccShopCatalog::matchesIdentity($url,$data),'exact Logic identity');}
foreach([$amount.'<span class="price">80,00€</span>',$label,'<span class="price">90.09 USD</span>']as$markup){$data=FccShopCatalog::parse($page($markup),$url,$url,200);verify($data['status']!=='verified_product','ambiguous or absent local amount is rejected');}
$data=FccShopCatalog::parse($page($label.$amount,'686'),$url,$url,200);verify($data['status']!=='verified_product','another SKU cannot satisfy the product URL');
$data=FccShopCatalog::parse($page($label.$amount),$url,$url,404);verify($data['status']==='http_error','not found cannot become a product');
$data=FccShopCatalog::parse($page($label.$amount,'673','Forever Vitamin C'),$url,$url,200);verify(!FccShopCatalog::matchesIdentity($url,$data),'another product name is rejected');
foreach([
 ['https://foreverliving.com/shop/hrv/hr-hr/products/C9','475','C9 Aloe Vanilla'],
 ['https://foreverliving.com/shop/hrv/hr-hr/products/weight-management/F15','528','F15 - Vanilla'],
 ['https://foreverliving.com/shop/hrv/hr-hr/products/combo-paks/vital5','459','Vital5 - Forever Freedom'],
]as[$family,$sku,$name]){
 $html='<h1>'.$name.'</h1><script type="application/ld+json">'.json_encode(['@type'=>'Product','name'=>$name,'sku'=>$sku,'offers'=>['@type'=>'Offer','url'=>$family,'price'=>'100.00','priceCurrency'=>'EUR']]).'</script>';
 $data=FccShopCatalog::parse($html,$family,$family,200);verify($data['status']==='verified_product'&&FccShopCatalog::matchesIdentity($family,$data),'reviewed family default identity');verify(($data['selection_required']??false)===true&&$data['price']===null&&$data['currency']===null,'one variant price is not the family price');
 $data['sku']='999';verify(!FccShopCatalog::matchesIdentity($family,$data),'unreviewed family SKU is rejected');
}
echo 'PASS '.$checks.' public shop checks'.PHP_EOL;
/* /Custom code: FC-2026-10-14 */
