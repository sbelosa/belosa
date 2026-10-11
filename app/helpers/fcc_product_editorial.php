<?php
defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-05: Vital5 package editorial and article connections */
require_once __DIR__.'/fcc_vital5.php';
/* /Custom code: FC-2026-10-05 */

function fcc_product_editorial_profiles(): array {
    static $profiles;
    return $profiles ??= json_decode(file_get_contents(APP_PATH.'config/fcc_product_editorial_profiles.json'),true);
}
function fcc_product_editorial_images(): array {
    static $images;
    return $images ??= json_decode(file_get_contents(APP_PATH.'config/fcc_product_editorial_images.json'),true);
}
/** Presentation overlay only. Never writes published articles, market URLs or prices. */
function fcc_product_editorial_apply(string $slug, array $item): array {
    /* Custom code: FC-2026-10-05: Preserve the article identity and its package connections */
    $item['product_key']=$slug;
    $package=fcc_vital5_package();
    $profile=fcc_product_editorial_profiles()[$slug]??null;
    if($slug==='vital5') {
        $copy=$package['editorial_copy'][fcc_library_pilot_locale()]??null;
        if(is_array($copy))return array_merge(fcc_localize_copy($item),$copy,[
            'product_key'=>'vital5','image_scale'=>1,'curated'=>true,
            'related'=>$package['component_article_slugs']??[],
        ]);
    }
    if(in_array($slug,$package['component_article_slugs']??[],true)) {
        $related=array_values(array_unique(array_merge(['vital5'],$profile['related']??$item['related']??[])));
        $item['related']=$related;
        if($profile)$profile['related']=$related;
    }
    /* /Custom code: FC-2026-10-05 */
    if(!$profile)return fcc_localize_copy($item);
    $locale=fcc_library_pilot_locale();$copy=$profile[in_array($locale,['sl','de','sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'],true)?'hr':$locale];$hr=in_array($locale,['hr','sl','de','sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'],true);
    $item=array_merge($item,[
        'title'=>$profile['title'],'format'=>$copy['format'],'intro'=>$copy['intro'],
        'teaser'=>$copy['intro'],'fit'=>$copy['fit'],'faq'=>[$copy['question']],
        'related'=>$profile['related'],'image_scale'=>1,'curated'=>true,
        'photo_note'=>$copy['photo_note']??($hr?'Pakiranje i varijantu provjeri uz cijenu.':'Check the pack and option beside the price.'),
        'meta_title'=>$profile['title'].' | '.fcc_t($hr?'Sastav i uporaba':'Ingredients and use').' | FCC',
        'meta_description'=>$copy['intro'],
    ]);
    // Keep the three source-reviewed pilot narratives and instructions intact.
    if(!in_array($slug,['marine-collagen','c9-forever-living-products','forever-vitamin-c'],true)) {
        $item['headline']=$hr?'Upoznaj razliku prije odabira.':'Understand the difference before choosing.';
        $item['routine_title']=($locale==='sr'?'Kako se koristi ':($locale==='de'?'Wie verwendest du ':($locale==='sl'?'Kako uporabljati ':($hr?'Kako koristiti ':'How to use ')))).$profile['title'].'?';
        if(fcc_new_locale($locale))$item['routine_title']=fcc_t('Kako koristiti {product}?',['{product}'=>$profile['title']],$locale);
        $item['routine']=[[$hr?'Tvoj korak u praksi':'Your practical step',$copy['use']]];
        $item['highlights']=[$copy['fact'],[$hr?'Kome može odgovarati':'Who it may suit',$copy['fit']]];
        $item['fit_in_highlights']=true;
    }
    return fcc_localize_copy($item);
}
/** Source-checked representative photo. The purchase panel identifies the local variant. */
function fcc_product_editorial_image(object $post, ?string $market=null, ?array $snapshot=null): array {
    $asset=fcc_product_editorial_images()[$post->url]??null;
    $links=json_decode($post->webshop_links??'{}',true)?:[];
    $exact=$market?($links[$market]??''):'';
    if($asset && is_file(UPLOADS_PATH.'product-editorial/'.$asset['file'])) {
        return ['url'=>fcc_fast_image_url(SITE_URL.'uploads/product-editorial/'.$asset['file'].'?v='.filemtime(UPLOADS_PATH.'product-editorial/'.$asset['file'])),'width'=>$asset['width'],'height'=>$asset['height'],
            'frame'=>'','representative'=>$exact!==($asset['source']??''),'source'=>$asset['source']??''];
    }
    // Retain the original article artwork when no sufficiently large verified original exists.
    $dimensions=@getimagesize(UPLOADS_PATH.'blog/'.$post->image)?:[540,300];
    $frame=fcc_library_image_frame($post->image);
    if(preg_match('/--fl-art-width:([\d.]+)%/',$frame,$match) && (float)$match[1]>0)$frame.='max-width:'.round($dimensions[0]*100/(float)$match[1]).'px;';
    return ['url'=>fcc_fast_image_url(\Altum\Uploads::get_full_url('blog').$post->image),'width'=>$dimensions[0],'height'=>$dimensions[1],
        'frame'=>$frame,'representative'=>true,'source'=>''];
}
/** Useful alternatives must remain in the visitor's chosen market. No foreign offer fallback. */
function fcc_product_editorial_related(array $item, ?string $market): array {
    if(!$market || fcc_market_contact_only($market))return [];
    $index=fcc_library_product_index();$out=[];
    /* Custom code: FC-2026-10-05: Match package components to the visitor's actual market */
    $isVital5=($item['product_key']??'')==='vital5';
    $related=$item['related']??[];
    if($isVital5) {
        $components=fcc_vital5_components((string)$market);
        $related=$components?array_values(array_unique(array_column($components,'product_key'))):(fcc_vital5_package()['component_article_slugs']??[]);
    }
    /* /Custom code: FC-2026-10-05 */
    foreach($related as $slug) {
        $post=$index[$slug]??null;
        if(!$post || !fcc_market_product_allowed($post,$market))continue;
        $links=json_decode($post->webshop_links??'{}',true)?:[];
        /* Custom code: FC-2026-10-05: Package connections never borrow another country's offer */
        if(($isVital5 || $slug==='vital5') && empty($links[$market]))continue;
        /* /Custom code: FC-2026-10-05 */
        $snapshot=FccShopCatalog::cached($links[$market]??'')??[];
        if(preg_match('~/(OutOfStock|Discontinued|SoldOut)$~',(string)($snapshot['availability']??'')))continue;
        $out[]=['post'=>$post,'item'=>fcc_library_pilot_product($slug),'image'=>fcc_product_editorial_image($post,$market,$snapshot)];
    }
    return $out;
}
