<?php defined('ALTUMCODE') || die();
$ref=$ref??$data->referral??'';
$contact_only=fcc_market_contact_only($market);
if($contact_only || !fcc_forever_product_identity_allows($post,$market,(string)($offer_base??$base??((array)json_decode($post->webshop_links??'{}',true))[$market??'']??'')) || (fcc_market_policy_enabled() && !fcc_market_product_allowed($post,$market))) {$tracked=null;$price=null;}
$category=fcc_market_category_base($post,$market);
$category_destination=$category?fcc_market_category_destination($post,(string)$market,(string)$ref):null;
$category_tracked=$category_destination?url('blog-click?'.http_build_query(['blog_post_id'=>(int)$post->blog_post_id,'ref'=>$ref,'market'=>$market,'kind'=>'category'])):null;
$stock_snapshot=$price??$offer_snapshot??$snapshot??[];
$stock_checked=strtotime($stock_snapshot['availability_checked_at']??$stock_snapshot['checked_at']??'1970-01-01');
$sold_out=($stock_snapshot['status']??'')==='verified_product' && ($stock_snapshot['market']??'')===$market
    && time()-$stock_checked<=FccShopCatalog::LAST_PRICE_TTL
    && preg_match('~/(OutOfStock|Discontinued|SoldOut)$~',(string)($stock_snapshot['availability']??''));
$price_text='';
if($price) {
    $formatter=new NumberFormatter(match(fcc_library_pilot_locale()){'sl'=>'sl_SI','hr'=>'hr_HR',default=>'en_GB'},NumberFormatter::CURRENCY);
    $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE,$price['currency']);
    // The public stores publish two decimals, including RSD. ICU defaults can round them away.
    $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS,2);
    $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS,2);
    $price_text=$formatter->formatCurrency((float)$price['price'],$price['currency']);
}
$variant=$price?($price['heading']??$price['name']??''):'';
?>
<?php if($tracked): ?>
<div class="fl-price-area">
 <?php if($price): ?>
 <span class="fl-price-label"><?= e(fcc_lp_t(!empty($price['is_last_confirmed'])?'last_price':(($price['price_kind']??'')==='catalog'?'catalog_price':'public_price'))) ?></span>
 <strong class="fl-price" data-fl-price data-currency="<?= e($price['currency']) ?>" data-last-confirmed="<?= !empty($price['is_last_confirmed'])?'true':'false' ?>"><?= e($price_text) ?></strong>
 <?php if($variant): ?><span class="fl-small fl-muted"><?= e(fcc_lp_t('price_variant')) ?> <?= e($variant.(!empty($price['pack'])?', '.$price['pack']:'')) ?></span><?php endif ?>

 <?php else: ?><p class="fl-price-label"><?= e(fcc_lp_t('price_unavailable')) ?></p><?php endif ?>
</div>
<a class="fl-primary fl-order" data-fl-offer href="<?= e($tracked) ?>" target="_blank" rel="sponsored noopener noreferrer"><span><?= e(fcc_lp_t($sold_out?'stock_check':'offer')) ?><small><?= e(fcc_library_pilot_country_name($market)) ?></small></span><?= fcc_partner_icon('arrow') ?></a>
<?php if($price): ?> <span class="fl-price-time"><?= e(fcc_lp_t('price_time')) ?> <time datetime="<?= e($price['checked_at']) ?>"><?= e((new DateTime($price['checked_at']))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('d.m.Y. H:i')) ?><?= htmlspecialchars(fcc_t(', Europe/Zagreb'), ENT_QUOTES, 'UTF-8') ?></time></span><?php endif ?>
<p class="fl-purchase-note"><?php if(($price['price_kind']??'')==='catalog'): ?><?= e(fcc_lp_t('choose_variant')) ?> <?php endif ?><?php if($sold_out): ?><?= e(fcc_lp_t('out_of_stock')) ?> <?php endif ?><?= e(fcc_lp_t('checkout_price')) ?></p>
<?php else: ?>
<div class="fl-market-notice" data-fl-availability="<?= $contact_only?'contact_only':'inactive' ?>">
 <p><?= e(fcc_lp_t($contact_only?'market_info_only':'local_not_orderable')) ?></p>
 <?php if($category_tracked): ?>
 <a class="fl-primary fl-order" data-fl-category href="<?= e($category_tracked) ?>" target="_blank" rel="sponsored noopener noreferrer"><span><?= e(fcc_lp_t($category['topic']==='all'?'browse_shop':'browse_category')) ?><small><?= e(($category['topic']!=='all'?fcc_lp_t($category['topic']).', ':'').fcc_library_pilot_country_name($market)) ?></small></span><?= fcc_partner_icon('arrow') ?></a>
 <p class="fl-purchase-note"><?= e(fcc_lp_t('alternative_note')) ?></p>
 <?php endif ?>
</div>
<?php endif ?>
