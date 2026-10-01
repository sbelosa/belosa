<?php defined('ALTUMCODE') || die();
fcc_library_pilot_assets();
$item=fcc_library_pilot_product($data->blog_post->url);
$post=$data->blog_post;$image=\Altum\Uploads::get_full_url('blog').$post->image;
$tracked=$data->tracked_webshop_link??null;
$market=fcc_library_pilot_offer_market($data);
$detected_market=\Altum\Link::resolve_forever_market_country_code($data->detected_visitor_country_code??null);
$fallback=$market && $detected_market && $market!==$detected_market;
$return_to_share=is_logged_in() && ($_GET['from']??'')==='share';
$share_url=$data->share_url;
parse_str((string)(parse_url($share_url,PHP_URL_QUERY)??''),$share_params);
$lp_personal_share=is_logged_in() && !empty($share_params['ref']);
$lp_can_share=!is_logged_in() || $lp_personal_share;
$message=fcc_library_pilot_message($item['title'],$share_url);
$content=(new \Altum\Shortcodes)->display_shortcodes($post->content,$data->referral??null);
if(function_exists('fc_forever_ordering_copy_text')) $content=fc_forever_ordering_copy_text($content);
$content=fcc_market_article_content($content,$post,$market,(string)($data->referral??''));
$sections=fcc_library_pilot_sections($content);
$lp_contact_url=!empty($data->referral)?url($data->referral):null;
foreach((array)($data->alternate_urls??[]) as $lang=>$href) \Altum\Event::add_content('<link rel="alternate" hreflang="'.e(fcc_language_tag($lang)).'" href="'.e($href).'">','head');
?>
<?php
$anchor_url=$data->blog_post_url.'?'.http_build_query(array_filter(['ref'=>$data->referral??null,'from'=>$return_to_share?'share':null,'market'=>$_GET['market']??null]));
$market_links=json_decode((string)$post->webshop_links,true)?:[];
$offer_base=$market_links[$market??'']??'';
$offer_snapshot=$offer_base?FccShopCatalog::cached($offer_base):null;
if(fcc_market_product_allowed($post,$market) && ($offer_snapshot['status']??'')==='verified_product' && ($offer_snapshot['market']??'')===$market && !empty($offer_snapshot['image']) && parse_url($offer_snapshot['image'],PHP_URL_HOST)==='cdn.foreverliving.com')$image=$offer_snapshot['image'];
$price=$tracked && $offer_base?FccShopCatalog::price($offer_base,(string)$market,true):null;
if($tracked && $offer_base && !$price)FccShopCatalog::requestRefresh($offer_base);
$photo=fcc_product_editorial_image($post,$market,$offer_snapshot);
$image=$photo['url'];$image_frame=$photo['frame'];
$hr=fcc_library_pilot_locale()==='hr';
$related=fcc_product_editorial_related($item,$market);
$market_options=FccShopCatalog::CURRENCIES;
if($market && !isset($market_options[$market]))$market_options=[$market=>null]+$market_options;
?>
<div id="fl-library" class="fl-library fl-article fl-editorial fl-theme-<?= e($item['topic']) ?>" data-fl-library data-post-id="<?= (int)$post->blog_post_id ?>" data-market="<?= e($market??'') ?>" data-market-fallback="<?= $fallback?'true':'false' ?>" data-referral="<?= e($data->referral??'') ?>" data-product-slug="<?= e($post->url) ?>" data-locale="<?= e(fcc_library_pilot_locale()) ?>">
 <div class="fl-article-toolbar"><a class="fl-back" href="<?= e(fcc_library_pilot_url($return_to_share?'partner/share':'blog',$return_to_share?null:($data->referral??null))) ?>">← <?= e(fcc_lp_t('back')) ?></a><nav aria-label="<?= e(fcc_lp_t('details')) ?>"><?php if($item['editorial']): ?><a href="<?= e($anchor_url) ?>#fl-discover"><?= e(fcc_lp_t('about_product')) ?></a><a href="<?= e($anchor_url) ?>#fl-routine"><?= e(fcc_lp_t('routine')) ?></a><?php endif ?><a href="<?= e($anchor_url) ?>#fl-details"><?= e(fcc_lp_t('details')) ?></a></nav></div>
 <div class="fl-article-hero">
  <header class="fl-product-heading"><span class="fl-kicker"><?= e(fcc_lp_t($item['topic'])) ?></span><h1><?= e($item['title']) ?></h1><p class="fl-lead"><?= e($item['intro']) ?></p></header>
  <div class="fl-product-stage fl-topic-<?= e($item['topic']) ?>"><span class="fl-stage-label"><?= e($item['format']) ?></span><button type="button" class="fl-hero-image fl-photo-open <?= $image_frame?'fl-framed-photo':'' ?>" data-fl-photo-open aria-label="<?= e($hr?fcc_t('Povećaj fotografiju proizvoda'):fcc_t('Enlarge product photograph')) ?>"><?php if($image_frame): ?><span class="fl-art-frame" style="<?= e($image_frame) ?>"><?php endif ?><img src="<?= e($image) ?>" alt="<?= e($item['title']) ?>" width="<?= (int)$photo['width'] ?>" height="<?= (int)$photo['height'] ?>" fetchpriority="high" decoding="async"><?php if($image_frame): ?></span><?php endif ?></button><p class="fl-photo-note"><?= e($item['photo_note']) ?></p><div class="fl-stage-caption"><span><?= htmlspecialchars(fcc_t('FOREVER LIVING'), ENT_QUOTES, 'UTF-8') ?></span><strong><?= e($item['format']) ?></strong></div></div>
  <div class="fl-article-intro">
   <section id="fl-purchase" class="fl-purchase" aria-label="<?= e(fcc_lp_t('store')) ?>">
    <form class="fl-country-form" method="get"><label for="fl-market"><?= e(fcc_lp_t('country')) ?></label><div class="fl-country-controls"><select id="fl-market" name="market"><?php foreach($market_options as $country=>$link): ?><option value="<?= e($country) ?>" <?= $country===$market?'selected':'' ?>><?= e(fcc_library_pilot_country_name($country)) ?></option><?php endforeach ?></select><button type="submit"><?= e(fcc_lp_t('change')) ?></button></div><?php if($data->referral): ?><input type="hidden" name="ref" value="<?= e($data->referral) ?>"><?php endif ?><?php if($return_to_share): ?><input type="hidden" name="from" value="share"><?php endif ?></form>
    <div data-fl-purchase-result><?php require THEME_PATH.'views/blog/product-offer.php'; ?></div>
    <?php if(fcc_market_product_allowed($post,$market) && $offer_base && FccShopCatalog::clean($offer_base) && FccShopCatalog::market($offer_base)===$market && !FccShopCatalog::price($offer_base,(string)$market)): ?>
    <span class="fl-price-refresh fl-small fl-muted" role="status" data-fl-refresh="<?= e(url('blog-offer?'.http_build_query(['post'=>(int)$post->blog_post_id,'market'=>$market,'ref'=>$data->referral??'']))) ?>"><?= e(fcc_lp_t('refreshing_price')) ?></span>
    <?php endif ?>
   </section>
   <?php if(is_logged_in()): ?>
   <section class="fl-partner-share" aria-label="<?= e(fcc_lp_t('share_own')) ?>">
    <?php if($lp_personal_share): ?><a class="fl-share-own" href="<?= e('https://wa.me/?text='.rawurlencode($message)) ?>" target="_blank" rel="noopener noreferrer" data-fl-compose data-fl-id="<?= (int)$post->blog_post_id ?>" data-fl-title="<?= e($item['title']) ?>" data-fl-image="<?= e($image) ?>" data-fl-url="<?= e($share_url) ?>" data-fl-message="<?= e($message) ?>"><?= fcc_partner_icon('share') ?><span><?= e(fcc_lp_t('share_own')) ?></span><?= fcc_partner_icon('arrow') ?></a><p><?= e(fcc_lp_t('share_own_hint')) ?></p>
    <?php else: ?><p><?= e(fcc_lp_t('no_card')) ?></p><a class="fl-secondary" href="<?= url('partner/cards') ?>"><?= e(fcc_lp_t('choose_card')) ?></a><?php endif ?>
   </section>
   <?php endif ?>
   <?php require THEME_PATH.'views/blog/product-contact.php'; ?>
   <?php if(!is_logged_in()): ?><div class="fl-actions fl-secondary-actions"><a class="fl-text-button" href="<?= e('https://wa.me/?text='.rawurlencode($message)) ?>" target="_blank" rel="noopener noreferrer" data-fl-compose data-fl-id="<?= (int)$post->blog_post_id ?>" data-fl-title="<?= e($item['title']) ?>" data-fl-image="<?= e($image) ?>" data-fl-url="<?= e($share_url) ?>" data-fl-message="<?= e($message) ?>"><?= fcc_partner_icon('share') ?> <?= e(fcc_lp_t('share_short')) ?></a></div><?php endif ?>
  </div>
 </div>
 <div class="fl-trust-strip"<?= fcc_market_contact_only($market)?' hidden':'' ?>><span><?= fcc_partner_icon('check') ?> <?= e($hr?fcc_t('Narudžba u službenoj trgovini'):fcc_t('Order in the official shop')) ?></span><span><?= e($hr?fcc_t('Ponuda za odabranu zemlju'):fcc_t('Offer for your selected country')) ?></span><a href="<?= e($anchor_url) ?>#fl-details"><?= e($hr?fcc_t('Jasne upute i sastav'):fcc_t('Clear directions and ingredients')) ?> ↗</a></div>
 <?php if($item['editorial']): ?>
 <section class="fl-discover" id="fl-discover"><div class="fl-section-heading"><span class="fl-kicker"><?= e(fcc_lp_t('about_product')) ?></span><h2><?= e($item['headline']) ?></h2></div><div class="fl-benefits"><?php foreach($item['highlights'] as $n=>$fact): ?><div class="fl-benefit"><span class="fl-number"><?= str_pad((string)($n+1),2,'0',STR_PAD_LEFT) ?></span><h3><?= e($fact[0]) ?></h3><p><?= e($fact[1]) ?></p></div><?php endforeach ?></div></section>
 <section class="fl-routine" id="fl-routine"><div class="fl-routine-heading"><span class="fl-kicker"><?= e(fcc_lp_t('routine')) ?></span><h2><?= e($item['routine_title']) ?></h2><span class="fl-routine-format"><?= e($item['format']) ?></span></div><ol><?php foreach($item['routine'] as $step): ?><li><div><h3><?= e($step[0]) ?></h3><p><?= e($step[1]) ?></p></div></li><?php endforeach ?></ol></section>
 <?php endif ?>
 <?php if(empty($item['fit_in_highlights'])): ?><section class="fl-fit"><span class="fl-kicker"><?= e($hr?fcc_t('Tvoj odabir'):fcc_t('Your choice')) ?></span><h2><?= e($hr?fcc_t('Kome može odgovarati?'):fcc_t('Who might this suit?')) ?></h2><p><?= e($item['fit']) ?></p></section><?php endif ?>
 <section class="fl-faq" id="fl-faq"><div><span class="fl-kicker"><?= e($hr?fcc_t('Prije nego odlučiš'):fcc_t('Before you decide')) ?></span><h2><?= e($hr?fcc_t('Odgovori koji pomažu pri odabiru.'):fcc_t('Answers to help you choose.')) ?></h2></div><div>
 <?php foreach($item['faq']??[] as $faq): ?><details open data-fl-faq="product" class="fl-article-section"><summary><?= e($faq[0]) ?></summary><p><?= e($faq[1]) ?></p></details><?php endforeach ?>
 <?php if(!fcc_market_contact_only($market)): ?>
 <details data-fl-faq="delivery" class="fl-article-section"><summary><?= e($hr?fcc_t('Gdje provjeravam dostavu i konačnu cijenu?'):fcc_t('Where do I check delivery and the final price?')) ?></summary><p><?= e($hr?fcc_t('Naručivanje se nastavlja u službenoj Forever trgovini za odabranu zemlju. Tamo prije potvrde kupnje provjeri dostavu, cijenu, eventualne popuste i uvjete povrata.'):fcc_t('Ordering continues in the official Forever shop for your selected country. Before confirming a purchase, check delivery, price, any discounts and return conditions there.')) ?></p></details>
 <?php endif ?>
 </div></section>
 <article class="fl-reading" id="fl-details"><h2><?= e(fcc_lp_t('details')) ?></h2>
 <?php if($item['editorial']): ?>
  <details class="fl-article-section"><summary><?= e(fcc_lp_t('notes')) ?></summary><div class="fl-source-content"><p><?= e($item['notes']) ?></p><?php if($tracked): ?><a href="<?= e($tracked) ?>" target="_blank" rel="sponsored noopener noreferrer"><?= e(fcc_lp_t('official_details')) ?> ↗</a><?php endif ?></div></details>
  <div class="fl-deeper-heading"><p><?= e($hr?fcc_t('Istraži temu koja te zanima.'):fcc_t('Explore the topic that interests you.')) ?></p></div>
  <?php foreach($sections as $n=>$section): ?><details class="fl-article-section fl-original-section"><summary><?= e(preg_replace('/\s*[—–]\s*|\s+-\s+/u',', ',$section['title'])) ?></summary><div class="fl-source-content" id="<?= e($section['id']??'') ?: 'fl-section-'.$n ?>"><?= $section['html'] ?></div></details><?php endforeach ?>
 <?php else: ?>
 <div class="fl-source-content fl-catalog-reading"><?php foreach($sections as $n=>$section): ?><section id="<?= e($section['id']??'') ?: 'fl-section-'.$n ?>"><?php if($n!==0 || $section['title']!==fcc_lp_t('details')): ?><h3><?= e($section['title']) ?></h3><?php endif ?><?= $section['html'] ?></section><?php endforeach ?></div>
 <?php endif ?>
 </article>
 <?php if($related): ?><section class="fl-related"><div class="fl-section-heading"><span class="fl-kicker"><?= e($hr?fcc_t('Usporedi prije odabira'):fcc_t('Compare before choosing')) ?></span><h2><?= e($hr?fcc_t('Još nešto što bi te moglo zanimati.'):fcc_t('You might also want to explore.')) ?></h2><p><?= e($hr?fcc_t('Srodni proizvodi iz ponude odabrane zemlje.'):fcc_t('Related products offered in your selected country.')) ?></p></div><div class="fl-related-grid">
 <?php foreach($related as $r): $related_url=fcc_library_pilot_url('blog/'.$r['post']->url,$data->referral??null,array_filter(['market'=>$market,'from'=>$return_to_share?'share':null])); ?>
 <a class="fl-related-card" data-fl-related="<?= e($r['post']->url) ?>" href="<?= e($related_url) ?>"><img src="<?= e($r['image']['url']) ?>" width="<?= (int)$r['image']['width'] ?>" height="<?= (int)$r['image']['height'] ?>" alt="<?= e($r['item']['title']) ?>" loading="lazy" decoding="async"><div><span class="fl-kicker"><?= e($r['item']['format']) ?></span><h3><?= e($r['item']['title']) ?></h3><p><?= e($r['item']['fit']) ?></p><span class="fl-text-button"><?= e(fcc_lp_t('read')) ?> →</span></div></a>
 <?php endforeach ?></div></section><?php endif ?>
 <div class="fl-sticky" data-fl-sticky hidden aria-label="<?= e($hr?fcc_t('Brzi pristup proizvodu'):fcc_t('Quick product access')) ?>"><div><strong><?= e($item['title']) ?></strong><span data-fl-sticky-price></span></div><a class="fl-primary" data-fl-sticky-action href="<?= e($anchor_url) ?>#fl-purchase"><?= e(fcc_lp_t('offer')) ?></a></div>
 <p class="fl-independent"><?= e(fcc_lp_t(fcc_market_contact_only($market)?'independent_info':'independent')) ?></p>
 <dialog class="fl-photo-dialog" data-fl-photo-dialog aria-label="<?= e($item['title']) ?>"><button type="button" class="fl-secondary" data-fl-photo-close><?= e($hr?fcc_t('Zatvori fotografiju'):fcc_t('Close photograph')) ?> ×</button><img src="<?= e($image) ?>" width="<?= (int)$photo['width'] ?>" height="<?= (int)$photo['height'] ?>" alt="<?= e($item['title']) ?>" loading="lazy"><p><?= e($hr?fcc_t('Fotografija proizvoda. Za lokalno pakiranje pogledaj ponudu svoje zemlje.'):fcc_t('Product photograph. Check your country offer for the local pack.')) ?></p></dialog>
 <?php if($lp_can_share) require THEME_PATH.'views/blog/pilot-composer.php'; ?>
</div>
<?php require THEME_PATH.'views/blog/pilot-advisor.php'; ?>
<?php
$canonical=$data->blog_post_url;
$schema=[
 ['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[
  ['@type'=>'ListItem','position'=>1,'name'=>l('index.breadcrumb'),'item'=>url()],
  ['@type'=>'ListItem','position'=>2,'name'=>fcc_lp_t('explore'),'item'=>url('blog')],
  ['@type'=>'ListItem','position'=>3,'name'=>$item['title'],'item'=>$canonical],
 ]],
 ['@context'=>'https://schema.org','@type'=>'BlogPosting','url'=>$canonical,'@id'=>$canonical.'#article','headline'=>$item['title'],'description'=>$item['intro'],'image'=>$image,
  'author'=>['@type'=>'Organization','name'=>fcc_t('Forever Card Club'),'url'=>SITE_URL],
  'publisher'=>['@type'=>'Organization','name'=>fcc_t('Forever Card Club')],
  'datePublished'=>(new DateTime($post->datetime))->format(DATE_ATOM),'dateModified'=>date(DATE_ATOM,max(strtotime($post->last_datetime),filemtime(APP_PATH.'config/fcc_product_editorial_profiles.json'))),
  'mainEntity'=>['@id'=>$canonical.'#product'],'mainEntityOfPage'=>['@type'=>'WebPage','@id'=>$canonical],'inLanguage'=>fcc_language_tag(fcc_library_pilot_locale())],
 ['@context'=>'https://schema.org','@type'=>'Product','@id'=>$canonical.'#product','name'=>$item['title'],'description'=>$item['teaser'],'image'=>$image,'url'=>$canonical,'brand'=>['@type'=>'Brand','name'=>fcc_t('Forever Living Products')]],
];
// Country variants can carry different item numbers. Do not assign one global SKU.
$codes=[];foreach($market_links as $link)if(preg_match('~/(\d+[A-Za-z]?)-~',urldecode($link),$m))$codes[strtolower(ltrim($m[1],'0'))]=true;
if(!empty($post->sku) && count($codes)===1 && isset($codes[strtolower(ltrim($post->sku,'0'))])) $schema[2]['sku']=$post->sku;
// Offer markup is restricted to the same fresh, exact, verified variant shown to this visitor.
// A category price, expired price or disabled market never creates a purchasable offer.
$fresh_price=$offer_base?FccShopCatalog::price($offer_base,(string)$market):null;
if($tracked && fcc_market_product_allowed($post,$market) && $fresh_price && empty($fresh_price['selection_required']) && ($fresh_price['price_kind']??'exact')!=='catalog') {
    $schema[2]['offers']=['@type'=>'Offer','url'=>$tracked,'price'=>number_format((float)$fresh_price['price'],2,'.',''),
        'priceCurrency'=>$fresh_price['currency'],'eligibleRegion'=>['@type'=>'Country','name'=>strtoupper($market)],
        'seller'=>['@type'=>'Organization','name'=>fcc_t('Forever Living Products')]];
    $stock=(string)($fresh_price['availability']??'');
    if(preg_match('~^https?://schema.org/(InStock|OutOfStock|PreOrder|BackOrder|Discontinued|SoldOut|LimitedAvailability)$~',$stock))$schema[2]['offers']['availability']=$stock;
}
foreach($schema as $entity) \Altum\Event::add_content('<script type="application/ld+json">'.json_encode($entity,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP).'</script>','javascript');
?>
