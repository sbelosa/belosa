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
/* Custom code: FC-2026-10-14: observed international public formats and foreign currency rejection. */
foreach([
 ['pol','en-gb','168,55zł','PLN','168.55'],
 ['pol','en-gb',"168,55\u{00A0}zł",'PLN','168.55'],
 ['nor','en-no','3 586,32 NOK','NOK','3586.32'],
 ['nor','en-no',"3\u{00A0}586,32\u{00A0}NOK",'NOK','3586.32'],
 ['swe','sv-se','3216,32 SEK','SEK','3216.32'],
 ['swe','sv-se','3216,32 kr','SEK','3216.32'],
 ['can','en-ca','$48.96','CAD','48.96'],
 ['aus','en-au','$48.96 AUD','AUD','48.96'],
 ['che','de-ch','Fr. 48.96','CHF','48.96'],
]as[$country,$locale,$text,$currency,$price]){
 $regional='https://foreverliving.com/shop/'.$country.'/'.$locale.'/products/673-LOGIC-KIT';
 $data=FccShopCatalog::parse($page('<span class="price">'.$text.'</span>'),$regional,$regional,200);
 verify($data['status']==='verified_product'&&$data['market']===array_search($country,FccShopCatalog::MARKETS,true),'public country product identity');
 verify($data['price']===$price&&$data['currency']===$currency,'public regional amount and currency');
}
foreach(['$48.96 USD','48.96 CAD USD','$48.96USD','US$48.96','100 50 CAD']as$text){
 $regional='https://foreverliving.com/shop/can/en-ca/products/673-LOGIC-KIT';
 $data=FccShopCatalog::parse($page('<span class="price">'.$text.'</span>'),$regional,$regional,200);
 verify($data['status']!=='verified_product'&&$data['price']===null,'foreign currency cannot become local price');
}
foreach(['£48.96 ALL','£48.96ALL']as$text){
 $regional='https://foreverliving.com/shop/gbr/en-gb/products/673-LOGIC-KIT';
 $data=FccShopCatalog::parse($page('<span class="price">'.$text.'</span>'),$regional,$regional,200);
 verify($data['status']!=='verified_product'&&$data['price']===null,'foreign alphabetic currency code is rejected');
}
foreach(['10 050 CAD'=>'10050','50 CAD'=>'50',"2\u{00A0}559,46 CAD"=>'2559.46',"2\u{202F}559,46 CAD"=>'2559.46','2,559.46 CAD'=>'2559.46']as$text=>$expected){
 $regional='https://foreverliving.com/shop/can/en-ca/products/673-LOGIC-KIT';
 $data=FccShopCatalog::parse($page('<span class="price">'.$text.'</span>'),$regional,$regional,200);
 verify($data['status']==='verified_product'&&$data['price']===$expected,'valid thousands grouping is preserved');
}
foreach(['168,55zł 120,00zł','168,55zł 10%']as$text){
 $regional='https://foreverliving.com/shop/pol/en-gb/products/673-LOGIC-KIT';
 $data=FccShopCatalog::parse($page('<span class="price">'.$text.'</span>'),$regional,$regional,200);
 verify($data['status']!=='verified_product'&&$data['price']===null,'multiple numbers cannot supply a retail amount');
}
/* /Custom code: FC-2026-10-14 */
/* Custom code: FC-2026-10-14: every reviewed binding has a normalized exact family and market. */
$registry=FccShopCatalog::identities();
foreach($registry['offers']as$offer)foreach($offer['names']??[]as$name)
 verify($name===FccShopCatalog::identityText($name),'reviewed names are normalized');
foreach($registry['posts']as$post)foreach($post['markets']as$market=>$offerUrl){
 $offer=$registry['offers'][$offerUrl]??null;
 verify($offer&&$offer['family']===$post['family']&&$offer['market']===$market,'reviewed article offer keeps exact family and market');
}
foreach([
 ['https://foreverliving.com/shop/nor/nn-no/products/v5','6279','Vital5™','3 586,32 NOK'],
 ['https://foreverliving.com/shop/swe/sv-se/products/v5','6279','Vital5™','3 216,32 SEK'],
 ['https://foreverliving.com/shop/mkd/mk-gb/products/nutritional/vital-5','456','Vital5® with Aloe Vera Gel®','24 000,00 MKD'],
 ['https://foreverliving.com/shop/scg/sr-cyrl-rs/products/combo-paks/Touch-of-Forever','001','A Touch of Forever','84.564,29 RSD'],
]as[$family,$sku,$title,$price]){
 $data=FccShopCatalog::parse($page('<span class="price">'.$price.'</span>',$sku,$title),$family,$family,200);
 verify($data['status']==='verified_product'&&FccShopCatalog::matchesIdentity($family,$data),'reviewed family DOM needs exact title and default item');
 verify(($data['selection_required']??false)===true&&$data['price']===null,'family DOM cannot supply selected variant price');
 $data=FccShopCatalog::parse($page('<span class="price">'.$price.'</span>','999',$title),$family,$family,200);
 verify($data['status']!=='verified_product','wrong visible family item is rejected');
}
foreach([
 ['https://foreverliving.com/shop/svn/sl-si/products/c9','C9V','475','C9','C9 Ultra Vanilla'],
 ['https://foreverliving.com/shop/svn/sl-si/products/f15','F15V','528','F15','F15™ Beginner Vanilla'],
 ['https://foreverliving.com/shop/scg/sr-cyrl-rs/products/Vital-5-','Vital5V','459','Vital⁵','Vital⁵ - Freedom'],
]as[$family,$schemaSku,$defaultSku,$name,$title]){
 $schema='<script type="application/ld+json">'.json_encode(['@type'=>'Product','name'=>$name,'sku'=>$schemaSku,'offers'=>['@type'=>'Offer','url'=>$family,'price'=>'100.00','priceCurrency'=>FccShopCatalog::CURRENCIES[FccShopCatalog::market($family)]]]).'</script>';
 $data=FccShopCatalog::parse($page('',$defaultSku,$title).$schema,$family,$family,200);
 verify($data['status']==='verified_product'&&FccShopCatalog::matchesIdentity($family,$data)&&$data['default_sku']===$defaultSku,'exact schema family code and visible item both match');
 verify(($data['selection_required']??false)===true&&$data['price']===null,'schema family code keeps choice without variant price');
 $data=FccShopCatalog::parse($page('','999',$title).$schema,$family,$family,200);
 verify($data['status']==='sku_mismatch','wrong visible default rejects schema family code');
 verify(!FccShopCatalog::matchesIdentity($family,['market'=>FccShopCatalog::market($family),'sku'=>$schemaSku,'name'=>$name,'heading'=>$title]),'schema code alone cannot prove default identity');
}
$unknown='https://foreverliving.com/shop/nor/nn-no/products/unreviewed-family';
verify(FccShopCatalog::parse($page('<span class="price">100,00 NOK</span>','6279','Vital5™'),$unknown,$unknown,200)['status']!=='verified_product','unreviewed generic path cannot reuse family DOM');
$al='https://www.foreveralbania.com/produktet/produktet-me-pike/shtesat-ushqimore/forever-fiber-fusion/';
verify(FccShopCatalog::matchesIdentity($al,['market'=>'al','sku'=>'702','name'=>'Forever Fiber Fusion (Mungese ne Kosove)','heading'=>'Forever Fiber Fusion (Mungese ne Kosove)']),'observed AL stock note retains exact product identity');
verify(!FccShopCatalog::matchesIdentity($al,['market'=>'al','sku'=>'701','name'=>'Forever Fiber Fusion (Mungese ne Kosove)']),'AL title does not substitute another SKU');
verify(!FccShopCatalog::articleIdentityMatches(7,'ba','https://www.flpshop.ba/proizvodi/fab-new/'),'FAB Active Boost cannot route to another FAB formula');
/* /Custom code: FC-2026-10-14 */
echo 'PASS '.$checks.' public shop checks'.PHP_EOL;
/* /Custom code: FC-2026-10-14 */
