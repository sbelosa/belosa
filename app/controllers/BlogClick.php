<?php
/* Custom code: FC-2026-03-31: tracked blog CTA redirect controller */

namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class BlogClick extends Controller {

    private function redirect_to_destination(string $destination_url): void {
        header('Location: ' . $destination_url);
        die();
    }

    private function is_allowed_destination_url(string $destination_url, object $blog_post, string $referral): bool {
        if(!$destination_url || !$referral || !filter_var($destination_url, FILTER_VALIDATE_URL)) return false;
        // Accept only an exact destination generated for this article and owner.
        // Checking the host alone also accepted another product or another FBO ID.
        foreach((array)json_decode($blog_post->webshop_links ?? '{}') as $country=>$base) {
            if(!$base) continue;
            if(parse_url($destination_url,PHP_URL_HOST)!==parse_url($base,PHP_URL_HOST) || parse_url($destination_url,PHP_URL_PATH)!==parse_url($base,PHP_URL_PATH))continue;
            $expected=\Altum\Link::get_product_webshop_link($referral,(int)$blog_post->blog_post_id,$country);
            if($expected && hash_equals($expected,$destination_url)) return true;
        }
        return false;
    }

    private function get_blog_post_url(object $blog_post): string {
        $language_prefix = $blog_post->language && isset(\Altum\Language::$active_languages[$blog_post->language])
            ? \Altum\Language::$active_languages[$blog_post->language] . '/'
            : '';

        return SITE_URL . $language_prefix . 'blog/' . $blog_post->url;
    }

    private function resolve_market_country_code(object $blog_post): ?string {
        return fcc_forever_country_context()['country'];
    }

    private function track_click(object $biolink, object $blog_post, string $click_type, string $destination_url): void {
        if(!function_exists('fc_process_monitored_forever_click')) {
            return;
        }

        fc_process_monitored_forever_click([
            'user_id' => (int) $biolink->user_id,
            'project_id' => isset($biolink->project_id) ? (int) $biolink->project_id : null,
            'blog_post_id' => (int) $blog_post->blog_post_id,
            'source_type' => $click_type === 'category' ? 'blog_category' : 'blog_cta',
            'click_type' => $click_type === 'category' ? 'blog_forever_category' : ($click_type === 'business' ? 'blog_forever_business' : 'blog_forever_product'),
            'destination_url' => $destination_url,
            'utm_medium' => \Altum\Link::get_blog_cta_tracking_medium($click_type),
            'utm_campaign' => 'blog_post:' . (int) $blog_post->blog_post_id,
        ]);
    }

    public function index() {
        header('Cache-Control: private, no-store');
        fcc_forever_remember_country();
        $blog_post_id = (int) ($_GET['blog_post_id'] ?? 0);
        $referral = query_clean($_GET['ref'] ?? '');
        $requested_destination_url = isset($_GET['destination']) ? trim((string) $_GET['destination']) : '';

        if(!$blog_post_id || $referral === '') {
            redirect('blog');
        }

        $blog_post = db()
            ->where('blog_post_id', $blog_post_id)
            ->where('is_published', 1)
            ->getOne('blog_posts', ['blog_post_id', 'url', 'language', 'webshop_links', 'blog_posts_category_id']);

        if(!$blog_post) {
            redirect('blog');
        }

        $fallback_url = $this->get_blog_post_url($blog_post);
        $biolink = db()
            ->where('url', $referral)
            ->where('type', 'biolink')
            ->getOne('links', ['link_id', 'user_id', 'project_id']);

        $market_country_code = $this->resolve_market_country_code($blog_post);
        $is_category=($_GET['kind']??'')==='category';
        $destination_url=null;
        $fallback_url.='?'.http_build_query(array_filter(['ref'=>$referral,'market'=>$market_country_code]));
        if($market_country_code && !fcc_market_contact_only($market_country_code)) {
            // Reconstruct from the article and owner. A forwarded destination cannot pin another market.
            $destination_url=$is_category
                ? fcc_market_category_destination($blog_post,$market_country_code,$referral)
                : \Altum\Link::get_product_webshop_link($referral,$blog_post_id,$market_country_code);
        }

        if($biolink && $destination_url && ($_SERVER['REQUEST_METHOD']??'GET')==='GET') {
            $click_type = $is_category ? 'category' : (($blog_post->url??'')==='start-paket'?'business':\Altum\Link::get_blog_cta_click_type_by_post_id($blog_post_id));
            $this->track_click($biolink, $blog_post, $click_type, $destination_url);
        }

        $this->redirect_to_destination($destination_url ?: $fallback_url);
    }
}

/* /Custom code: FC-2026-03-31 */
