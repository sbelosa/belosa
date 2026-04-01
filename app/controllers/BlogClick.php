<?php
/* Custom code: FC-2026-03-31: tracked blog CTA redirect controller */

namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class BlogClick extends Controller {

    private function redirect_to_destination(string $destination_url): void {
        header('Location: ' . $destination_url);
        die();
    }

    private function is_allowed_destination_url(string $destination_url, object $blog_post): bool {
        if(!$destination_url || !filter_var($destination_url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $destination_host = parse_url($destination_url, PHP_URL_HOST);

        if(!is_string($destination_host) || $destination_host === '') {
            return false;
        }

        $destination_host = mb_strtolower(preg_replace('/^www\./', '', $destination_host) ?? $destination_host);
        $product_webshop_links = json_decode($blog_post->webshop_links ?? '{}');

        foreach((array) $product_webshop_links as $webshop_link) {
            if(!$webshop_link || !filter_var($webshop_link, FILTER_VALIDATE_URL)) {
                continue;
            }

            $allowed_host = parse_url($webshop_link, PHP_URL_HOST);

            if(!is_string($allowed_host) || $allowed_host === '') {
                continue;
            }

            $allowed_host = mb_strtolower(preg_replace('/^www\./', '', $allowed_host) ?? $allowed_host);

            if($destination_host === $allowed_host) {
                return true;
            }
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
        $country_code = null;
        $country_code_is_trusted = false;
        $accept_language_header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

        foreach(['HTTP_CF_IPCOUNTRY', 'HTTP_CF-IPCOUNTRY', 'GEOIP_COUNTRY_CODE', 'HTTP_GEOIP_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_COUNTRY'] as $country_header_key) {
            if(!empty($_SERVER[$country_header_key])) {
                $country_code = mb_strtoupper(trim((string) $_SERVER[$country_header_key]));
                $country_code = mb_substr($country_code, 0, 2);

                if(mb_strlen($country_code) == 2 && $country_code !== 'XX') {
                    $country_code_is_trusted = true;
                    break;
                }

                $country_code = null;
            }
        }

        if(!$country_code) {
            $country_code = \Altum\Link::get_external_geo_country_code(get_ip());
        }

        if(!$country_code) {
            try {
                $maxmind_city = get_maxmind_reader_city()->get(get_ip());
                $country_code = isset($maxmind_city['country']) ? ($maxmind_city['country']['iso_code'] ?? null) : null;
            } catch(\Exception $exception) {
                /* Ignore. */
            }
        }

        if(!$country_code) {
            try {
                $maxmind = get_maxmind_reader_country()->get(get_ip());
                $country_code = isset($maxmind['country']) ? ($maxmind['country']['iso_code'] ?? null) : null;
            } catch(\Exception $exception) {
                /* Ignore. */
            }
        }

        $product_webshop_links = json_decode($blog_post->webshop_links ?? '{}');
        $available_market_country_codes = array_keys(array_filter((array) $product_webshop_links, static function($value) {
            return !empty($value);
        }));

        return \Altum\Link::resolve_preferred_forever_market_country_code($country_code, $available_market_country_codes, $accept_language_header, $country_code_is_trusted);
    }

    private function track_click(object $biolink, object $blog_post, string $click_type, string $destination_url): void {
        if(!function_exists('fc_process_monitored_forever_click')) {
            return;
        }

        fc_process_monitored_forever_click([
            'user_id' => (int) $biolink->user_id,
            'project_id' => isset($biolink->project_id) ? (int) $biolink->project_id : null,
            'blog_post_id' => (int) $blog_post->blog_post_id,
            'source_type' => 'blog_cta',
            'click_type' => $click_type === 'business' ? 'blog_forever_business' : 'blog_forever_product',
            'destination_url' => $destination_url,
            'utm_medium' => \Altum\Link::get_blog_cta_tracking_medium($click_type),
            'utm_campaign' => 'blog_post:' . (int) $blog_post->blog_post_id,
        ]);
    }

    public function index() {
        $blog_post_id = (int) ($_GET['blog_post_id'] ?? 0);
        $referral = query_clean($_GET['ref'] ?? '');
        $requested_destination_url = isset($_GET['destination']) ? trim((string) $_GET['destination']) : '';

        if(!$blog_post_id || $referral === '') {
            redirect('blog');
        }

        $blog_post = db()
            ->where('blog_post_id', $blog_post_id)
            ->where('is_published', 1)
            ->getOne('blog_posts', ['blog_post_id', 'url', 'language', 'webshop_links']);

        if(!$blog_post) {
            redirect('blog');
        }

        $fallback_url = $this->get_blog_post_url($blog_post);
        $biolink = db()
            ->where('url', $referral)
            ->where('type', 'biolink')
            ->getOne('links', ['link_id', 'user_id', 'project_id']);

        $market_country_code = $this->resolve_market_country_code($blog_post);
        $recomputed_destination_url = \Altum\Link::get_product_webshop_link($referral, $blog_post_id, $market_country_code);
        $destination_url = $this->is_allowed_destination_url($requested_destination_url, $blog_post)
            ? $requested_destination_url
            : $recomputed_destination_url;

        if($biolink && $destination_url) {
            $click_type = \Altum\Link::get_blog_cta_click_type_by_post_id($blog_post_id);
            $this->track_click($biolink, $blog_post, $click_type, $destination_url);
        }

        $this->redirect_to_destination($destination_url ?: $fallback_url);
    }
}

/* /Custom code: FC-2026-03-31 */
