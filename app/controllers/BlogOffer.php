<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();

/** Refresh only the public offer configured on a published article. No clicks are counted. */
class BlogOffer extends Controller {
    public function index() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        if(!fcc_library_pilot_enabled()) {http_response_code(404);echo '{}';return;}
        $post=db()->where('blog_post_id',(int)($_GET['post']??0))->where('is_published',1)->getOne('blog_posts');
        $market=strtolower((string)($_GET['market']??''));
        $ref=(string)($_GET['ref']??'');
        $base=$post?((array)json_decode($post->webshop_links??'{}',true))[$market]??'':'';
        if(!$post || !isset(\FccShopCatalog::CURRENCIES[$market])) {http_response_code(404);echo '{}';return;}
        if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
        if(fcc_market_product_allowed($post,$market)) \FccShopCatalog::refreshForVisitor($base);
        $tracked=null;$snapshot=\FccShopCatalog::cached($base);
        // Buying a reviewed product does not depend on a price refresh succeeding.
        // The shared resolver still rejects a foreign market or a confirmed wrong product.
        if(fcc_market_product_allowed($post,$market)) {
            $destination=\Altum\Link::get_product_webshop_link($ref,$post->blog_post_id,$market);
            if($destination && \FccShopCatalog::clean($destination)===\FccShopCatalog::clean($base))
                $tracked=url('blog-click?'.http_build_query(['blog_post_id'=>(int)$post->blog_post_id,'ref'=>$ref,'market'=>$market,'destination'=>$destination]));
        }
        $price=$tracked?\FccShopCatalog::price($base,$market,true):null;
        ob_start();require THEME_PATH.'views/blog/product-offer.php';$html=ob_get_clean();
        echo json_encode(['html'=>$html],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG);
    }
}
