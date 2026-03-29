<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

namespace Altum\Controllers;

use Altum\Language;
use Altum\Meta;
use Altum\Models\BlogPosts;
use Altum\Models\BlogPostsCategories;
use Altum\Response;
use Altum\Title;

defined('ALTUMCODE') || die();

class Blog extends Controller {

    private function get_logged_in_referral_biolink_url(): ?string {
        $user_id = \Altum\Authentication::check();

        if(!$user_id) {
            return null;
        }

        $main_biolink_map = db()->where('user_id', $user_id)->getOne('users_biolinks', ['biolink_id']);

        if($main_biolink_map && !empty($main_biolink_map->biolink_id)) {
            $biolink = db()->where('link_id', $main_biolink_map->biolink_id)->where('type', 'biolink')->getOne('links', ['url']);

            if($biolink && !empty($biolink->url)) {
                return $biolink->url;
            }
        }

        $biolink = db()->where('user_id', $user_id)->where('type', 'biolink')->orderBy('link_id', 'ASC')->getOne('links', ['url']);

        return ($biolink && !empty($biolink->url)) ? $biolink->url : null;
    }

    private function append_referral_to_url(string $url, ?string $referral = null): string {
        $referral = trim((string) $referral);

        if($referral === '') {
            return $url;
        }

        return $url . (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . http_build_query(['ref' => $referral]);
    }

    public function index() {

        if(!settings()->content->blog_is_enabled) {
            throw_404();
        }

        $language = Language::$name;

        /* Blog RSS */
        if(isset($this->params[0]) && $this->params[0] == 'feed') {
            /* Set the header as xml so the browser can read it properly */
            header('Content-Type: text/xml');
            header('X-Robots-Tag: noindex');

            $blog_posts = db()->where('is_published', 1)->get('blog_posts', null, ['blog_post_id', 'title', 'description', 'url', 'language', 'datetime']);

            /* Prepare the view */
            $data = [
                'blog_posts' => $blog_posts
            ];

            $view = new \Altum\View('blog/blog_rss', (array) $this);

            echo $view->run($data);

            die();
        }

        /* Blog post */
        if(isset($this->params[0]) && $this->params[0] != 'category') {
            $url = query_clean($this->params[0]);

            $blog_post_query = "
                SELECT * 
                FROM `blog_posts`
                WHERE ((`url` = '{$url}' AND `language` = '{$language}') OR (`url` = '{$url}' AND `language` IS NULL)) AND `is_published` = 1
            ";
            $blog_post = \Altum\Cache::cache_function_result('blog_post?hash=' . md5($blog_post_query), ['blog_posts', 'blog_post_' . md5($url)], function() use ($blog_post_query) {
                return database()->query($blog_post_query)->fetch_object() ?? null;
            });

            if(!$blog_post) {
                throw_404();
            }

            /* Transform content if needed */
            $blog_post->content = json_decode($blog_post->content) ? convert_editorjs_json_to_html($blog_post->content) : output_blog_post_content($blog_post->content);

            /* Get the blog post category */
            $blog_posts_category = \Altum\Cache::cache_function_result('blog_posts_category?hash=' . md5($blog_post->blog_posts_category_id ?? ''), 'blog_posts_categories', function() use ($blog_post) {
                return $blog_post->blog_posts_category_id ? db()->where('blog_posts_category_id', $blog_post->blog_posts_category_id)->getOne('blog_posts_categories') : null;
            });

            /* Add a new view to the post */
            $cookie_name = 'blog_post_view_' . $blog_post->blog_post_id;
            if(!isset($_COOKIE[$cookie_name])) {
                db()->where('blog_post_id', $blog_post->blog_post_id)->update('blog_posts', ['total_views' => db()->inc()]);
                setcookie($cookie_name, (int) true, time()+60*60*24*1);
            }

            $blog_post_url = SITE_URL . ($blog_post->language ? ((\Altum\Language::$active_languages[$blog_post->language] ?? null) ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) : null) . 'blog/' . $blog_post->url;

            /* Set a custom title */
            Title::set(sprintf(l('blog.blog_post.title'), $blog_post->title));

            /* Meta */
            Meta::set_description(string_truncate($blog_post->description ?: strip_tags($blog_post->title), 160));
            Meta::set_keywords($blog_post->keywords);
            Meta::set_canonical_url($blog_post_url);
            Meta::set_robots('index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1');
            if($blog_post->image) {
                Meta::set_social_image(\Altum\Uploads::get_full_url('blog') . $blog_post->image);
            }

            /* Disable automated link language alternate */
            Meta::set_link_alternate(false);

            /* Custom code */
            /* Get all the categories */
            $blog_posts_main_categories = db()->where('blog_posts_parent_id', null, 'IS')->where('language', $language)->orderBy('`order`', 'ASC')->get('blog_posts_categories', null, ['blog_posts_category_id', 'blog_posts_parent_id', 'title', 'url', 'language']);        

            $blog_posts_parents = database()->query("SELECT * FROM `blog_posts_categories` WHERE `blog_posts_parent_id` IS NOT NULL AND `language` = '{$language}' OR `language` IS NULL AND `blog_posts_parent_id` IN (SELECT `blog_posts_category_id` FROM `blog_posts_categories` WHERE `blog_posts_category_id` IS NOT NULL GROUP BY `blog_posts_category_id`) ORDER BY `order` ASC");

            $blog_posts_parent_categories = [];
            
            while($row = $blog_posts_parents->fetch_object()) {
                $blog_posts_parent_categories[] = $row;
            }

            /* Get all the subcategories */
            $blog_posts_subcategories = db()->where('blog_posts_parent_id', null, 'IS NOT')->where('language', $language)->orderBy('`order`', 'ASC')->get('blog_posts_categories', null, ['blog_posts_category_id', 'blog_posts_parent_id', 'title', 'url', 'language']);         


            /* Custom code: FC-2026-02-26: robust blog referral resolution without hardcoded fallback */
            $referral = null;
            $referral_key = null;
            $legacy_referral_slug = 'wpebe1grqr';
            $default_referral_slug = 'ddglabhlcn';

            if(isset($_GET['ref']) && $_GET['ref'] !== '') {
                $referral_key = query_clean($_GET['ref']);
            } elseif(isset($_COOKIE['referral']) && $_COOKIE['referral'] !== '' && query_clean($_COOKIE['referral']) !== $legacy_referral_slug) {
                $referral_key = query_clean($_COOKIE['referral']);
            } elseif(isset($_COOKIE['referred_by']) && $_COOKIE['referred_by'] !== '' && query_clean($_COOKIE['referred_by']) !== $legacy_referral_slug) {
                $referral_key = query_clean($_COOKIE['referred_by']);
            } else {
                /* Custom code: FC-2026-02-26: default affiliate fallback slug */
                $referral_key = $default_referral_slug;
                /* /Custom code: FC-2026-02-26 */
            }

            if(
                (isset($_COOKIE['referral']) && query_clean($_COOKIE['referral']) === $legacy_referral_slug)
                || (isset($_COOKIE['referred_by']) && query_clean($_COOKIE['referred_by']) === $legacy_referral_slug)
            ) {
                setcookie('referral', $default_referral_slug, time()+60*60*24*365, '/');
            }

            if(!\Altum\Authentication::check() && $referral_key) {
                $resolved_user = null;
                $resolved_biolink_url = null;

                /* First support affiliate-style referral key */
                $resolved_user = db()->where('referral_key', $referral_key)->where('status', 1)->getOne('users', ['user_id', 'status', 'plan_id', 'referral_key']);

                /* Then support biolink slug referral */
                if(!$resolved_user) {
                    $resolved_biolink = db()->where('url', $referral_key)->where('type', 'biolink')->getOne('links', ['user_id', 'url']);

                    if($resolved_biolink) {
                        $resolved_user = db()->where('user_id', $resolved_biolink->user_id)->where('status', 1)->getOne('users', ['user_id', 'status', 'plan_id', 'referral_key']);
                        $resolved_biolink_url = $resolved_biolink->url;
                    }
                }

                if($resolved_user) {
                    if(!$resolved_biolink_url) {
                        $resolved_biolink_map = db()->where('user_id', $resolved_user->user_id)->getOne('users_biolinks', ['biolink_id']);
                        if($resolved_biolink_map) {
                            $resolved_biolink = db()->where('link_id', $resolved_biolink_map->biolink_id)->where('type', 'biolink')->getOne('links', ['url']);
                            if($resolved_biolink && !empty($resolved_biolink->url)) {
                                $resolved_biolink_url = $resolved_biolink->url;
                            }
                        }

                        if(!$resolved_biolink_url) {
                            $resolved_biolink = db()->where('user_id', $resolved_user->user_id)->where('type', 'biolink')->orderBy('link_id', 'ASC')->getOne('links', ['url']);
                            if($resolved_biolink && !empty($resolved_biolink->url)) {
                                $resolved_biolink_url = $resolved_biolink->url;
                            }
                        }
                    }

                    if($resolved_biolink_url) {
                        $referral = $resolved_biolink_url;
                        setcookie('referral', $resolved_biolink_url, time()+60*60*24*365, '/');
                    }

                    if(\Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled && !empty($resolved_user->referral_key)) {
                        settings()->affiliate->tracking_type = settings()->affiliate->tracking_type ?? 'first';

                        if(
                            (settings()->affiliate->tracking_type == 'first' && !isset($_COOKIE['referred_by']))
                            || settings()->affiliate->tracking_type == 'last'
                        ) {
                            setcookie('referred_by', $resolved_user->referral_key, time()+60*60*24*365, COOKIE_PATH);
                        }
                    }

                }

                if(!$referral && $referral_key) {
                    /* Custom code: FC-2026-02-26: preserve fallback referral slug when user lookup is unavailable */
                    $referral = $referral_key;
                    setcookie('referral', $referral_key, time()+60*60*24*365, '/');
                    /* /Custom code: FC-2026-02-26 */
                }
            }
            /* /Custom code: FC-2026-02-26 */

            if ($user_id = \Altum\Authentication::check()) {
                $referral = false;
                $private_display = true;
                $biolink = null;

                $main_biolink_map = db()->where('user_id', $user_id)->getOne('users_biolinks', ['biolink_id']);

                if($main_biolink_map && !empty($main_biolink_map->biolink_id)) {
                    $biolink = db()->where('link_id', $main_biolink_map->biolink_id)->where('type', 'biolink')->getOne('links', ['link_id', 'url']);
                }

                /* Safety fallback only if the original main mapping is missing or broken. */
                if(!$biolink) {
                    $biolink = db()->where('user_id', $user_id)->where('type', 'biolink')->orderBy('link_id', 'ASC')->getOne('links', ['link_id', 'url']);
                }

                if($biolink && !empty($biolink->url)) {
                    $referral = $biolink->url;
                }

                if(\Altum\Authentication::is_pro()) {
                    $private = true;
                }
            }

            if ($referral) {                      
                /* Custom code: FC-2026-02-26: robust visitor country detection for blog webshop routing */
                $country_code = null;

                foreach(['HTTP_CF_IPCOUNTRY', 'HTTP_CF-IPCOUNTRY', 'GEOIP_COUNTRY_CODE', 'HTTP_GEOIP_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_COUNTRY'] as $country_header_key) {
                    if(!empty($_SERVER[$country_header_key])) {
                        $header_country_code = mb_strtoupper(trim((string) $_SERVER[$country_header_key]));
                        $header_country_code = mb_substr($header_country_code, 0, 2);

                        if(mb_strlen($header_country_code) == 2 && $header_country_code !== 'XX') {
                            $country_code = $header_country_code;
                            break;
                        }
                    }
                }

                if(!$country_code) {
                    try {
                        $maxmind = (new \MaxMind\Db\Reader(APP_PATH . 'includes/GeoLite2-Country.mmdb'))->get(get_ip());
                    } catch(\Exception $exception) {
                        /* :) */
                    }

                    $country_code = isset($maxmind) && isset($maxmind['country']) ? $maxmind['country']['iso_code'] : null;
                }

                if(!$country_code) {
                    try {
                        $maxmind_city = (new \MaxMind\Db\Reader(APP_PATH . 'includes/GeoLite2-City.mmdb'))->get(get_ip());
                    } catch(\Exception $exception) {
                        /* :) */
                    }

                    $country_code = isset($maxmind_city) && isset($maxmind_city['country']) ? $maxmind_city['country']['iso_code'] : null;
                }
                /* /Custom code: FC-2026-02-26 */

                /* Custom code: FC-2026-02-26: browser language fallback for blog webshop links */
                $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
                /* /Custom code: FC-2026-02-26 */

                $webshop_link = \Altum\Link::get_product_webshop_link($referral, $blog_post->blog_post_id, $country_code, $browser_language);                
            }            

            /* Get popular posts */
            $blog_posts_popular = settings()->content->blog_popular_widget_is_enabled ? (new BlogPosts())->get_popular_blog_posts_by_language($language) : [];

            /* Prepare the view */
            $share_url = $blog_post_url;
            if($referral) {
                $share_url .= (parse_url($share_url, PHP_URL_QUERY) ? '&' : '?') . http_build_query(['ref' => $referral]);
            }

            $data = [
                'blog_posts_popular' => $blog_posts_popular,
                'blog_post' => $blog_post,
                'blog_posts_category' => $blog_posts_category,
                'blog_posts_main_categories' => $blog_posts_main_categories,
                'blog_posts_parents' => $blog_posts_parent_categories,
                'blog_posts_subcategories' => $blog_posts_subcategories,             
                'biolink' => isset($biolink) ? $biolink : null,
                'referral' => $referral,
                'private_display' => isset($private_display) ? $private_display : null,
                'private' => isset($private) ? $private : null,
                'webshop_link' => isset($webshop_link) && !empty($webshop_link) ? $webshop_link : null,
                'blog_post_url' => $blog_post_url,
                'share_url' => $share_url,
            ];
            /* /Custom code */

            $view = new \Altum\View('blog/blog_post', (array) $this);

            $this->add_view_content('content', $view->run($data));
        }

        /* Blog category */
        else if(isset($this->params[0], $this->params[1]) && $this->params[0] == 'category') {
            /* Custom code */
            if (isset($this->params[3])) {
                $url = query_clean($this->params[1] . '/' . $this->params[2] . '/' . $this->params[3]);
            } else if (isset($this->params[2])) {
                $url = query_clean($this->params[1] . '/' . $this->params[2]);
            } else {
                $url = query_clean($this->params[1]);
            } 
            /* /Custom code */

            $blog_posts_category_query = "
                SELECT * 
                FROM `blog_posts_categories`
                WHERE (`url` = '{$url}' AND `language` = '{$language}') OR (`url` = '{$url}' AND `language` IS NULL)
                ORDER BY `language` DESC
            ";
            $blog_posts_category = \Altum\Cache::cache_function_result('blog_posts_category?hash=' . md5($blog_posts_category_query), 'blog_posts_categories', function() use ($blog_posts_category_query) {
                return database()->query($blog_posts_category_query)->fetch_object() ?? null;
            });

            if(!$blog_posts_category) {
                throw_404();
            }

            /* Get the posts */
            /* Prepare the filtering system */
            $filters = (new \Altum\Filters());
            $filters->set_default_order_by('datetime', $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
            $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

            /* Prepare the paginator */
            $total_rows_query = "SELECT COUNT(*) AS `total` FROM `blog_posts` WHERE `blog_posts_category_id` = {$blog_posts_category->blog_posts_category_id} AND (`language` = '{$language}' OR `language` IS NULL) AND `is_published` = 1 {$filters->get_sql_where()}";
            $total_rows = \Altum\Cache::cache_function_result('blog_posts_count?hash=' . md5($total_rows_query), 'blog_posts', function() use ($total_rows_query) {
                return database()->query($total_rows_query)->fetch_object()->total ?? 0;
            });
            $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('blog/category/' . $blog_posts_category->url . '?' . $filters->get_get() . '&page=%d')));

            /* Blog posts query */            
            /* Custom code */
            /* Get the blog posts child categories */
            $blog_posts_child_categories = db()->where('blog_posts_parent_id', $blog_posts_category->blog_posts_category_id)->get('blog_posts_categories', null, ['blog_posts_category_id']);

            /* Get all the categories */
            $blog_posts_main_categories = db()->where('blog_posts_parent_id', null, 'IS')->where('language', $language)->orderBy('datetime')->get('blog_posts_categories', null, ['blog_posts_category_id', 'blog_posts_parent_id', 'title', 'url', 'language']);
   
            $blog_posts_parents = database()->query("SELECT * FROM `blog_posts_categories` WHERE `blog_posts_parent_id` IS NOT NULL AND `language` = '{$language}' OR `language` IS NULL AND `blog_posts_parent_id` IN (SELECT `blog_posts_category_id` FROM `blog_posts_categories` WHERE `blog_posts_category_id` IS NOT NULL GROUP BY `blog_posts_category_id`)");

            $blog_posts_parent_categories = [];
            
            while($row = $blog_posts_parents->fetch_object()) {
                $blog_posts_parent_categories[] = $row;
            }

            /* Get all the subcategories */
            $blog_posts_subcategories = db()->where('blog_posts_parent_id', null, 'IS NOT')->where('language', $language)->get('blog_posts_categories', null, ['blog_posts_category_id', 'blog_posts_parent_id', 'title', 'url', 'language']);         

            $child_categories_array = (new BlogPostsCategories())->get_blog_category_children($blog_posts_category->blog_posts_category_id);            
            
            if ($child_categories_array) {
                foreach($child_categories_array as $child_category) {
                    if(isset($child_category->blog_posts_category_id)) {
                        $child_categories_array_list[] = $child_category->blog_posts_category_id;
                    }     
                    
                    if(is_array($child_category)) {
                        if(isset($child_category['children'])) {
                            foreach($child_category['children'] as $child) {
                                if(isset($child->blog_posts_category_id)) {
                                    $child_categories_array_list[] = $child->blog_posts_category_id;
                                }           
                            }
                        }
                        
                    }                    
                                     
                }      
                
                $child_categories_array_list[] = $blog_posts_category->blog_posts_category_id;
                
                $blog_posts_child_categories_list = implode(', ', $child_categories_array_list);  
                
                if(sizeof($child_categories_array_list) == 1) {            
                    $child_categories_array = (new BlogPostsCategories())->get_blog_category_children($blog_posts_child_categories_list);            
                    
                    foreach($child_categories_array as $child_category) {
                        if(isset($child_category->blog_posts_category_id)) {
                            $child_categories_array_list[] = $child_category->blog_posts_category_id;
                        }                
                        
                    }
                    $child_categories_array_list[] = $blog_posts_category->blog_posts_category_id;
                    $blog_posts_child_categories_list = implode(', ', $child_categories_array_list); 
                }
    
            } else {
                $blog_posts_child_categories_list = $blog_posts_category->blog_posts_category_id;
            }
            
            $blog_posts_result_query = "
                SELECT * 
                FROM `blog_posts`
                WHERE `blog_posts_category_id` IN ({$blog_posts_child_categories_list}) AND (`language` = '{$language}' OR `language` IS NULL) AND `is_published` = 1 {$filters->get_sql_where()}
                {$filters->get_sql_order_by()}
                {$paginator->get_sql_limit()}
            ";
            /* /Custom code */

            $blog_posts = \Altum\Cache::cache_function_result('blog_posts?hash=' . md5($blog_posts_result_query), 'blog_posts', function() use ($blog_posts_result_query) {
                $blog_posts_result = database()->query($blog_posts_result_query);

                /* Iterate over the blog posts */
                $blog_posts = [];

                while($row = $blog_posts_result->fetch_object()) {
                    /* Transform content if needed */
                    $row->content = json_decode($row->content) ? convert_editorjs_json_to_html($row->content) : output_blog_post_content($row->content);

                    $blog_posts[] = $row;
                }

                return $blog_posts;
            });

            /* Prepare the pagination view */
            $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);



            /* Get popular posts */
            $blog_posts_popular = settings()->content->blog_popular_widget_is_enabled ? (new BlogPosts())->get_popular_blog_posts_by_language($language) : [];

            $blog_posts_category_url = SITE_URL . ($blog_posts_category->language ? ((\Altum\Language::$active_languages[$blog_posts_category->language] ?? null) ? \Altum\Language::$active_languages[$blog_posts_category->language] . '/' : null) : null) . 'blog/category/' . $blog_posts_category->url;

            $social_image = null;
            foreach($blog_posts as $blog_post_item) {
                if(!empty($blog_post_item->image)) {
                    $social_image = \Altum\Uploads::get_full_url('blog') . $blog_post_item->image;
                    break;
                }
            }

            /* Set a custom title */
            Title::set(sprintf(l('blog.blog_posts_category.title'), $blog_posts_category->title));

            /* Meta */
            Meta::set_description(string_truncate($blog_posts_category->description ?: $blog_posts_category->title, 160));
            Meta::set_canonical_url($blog_posts_category_url);
            Meta::set_robots('index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1');
            if($social_image) {
                Meta::set_social_image($social_image);
            }

            /* Disable automated link language alternate */
            Meta::set_link_alternate(false);

            /* Prepare the view */
            $data = [
                'blog_posts_category' => $blog_posts_category,
                'blog_posts' => $blog_posts,
                'pagination' => $pagination,                
                'blog_posts_popular' => $blog_posts_popular,
                'share_url' => $this->append_referral_to_url($blog_posts_category_url, $this->get_logged_in_referral_biolink_url()),
                /* Custom code */
                'blog_posts_main_categories' => $blog_posts_main_categories,
                'blog_posts_parents' => $blog_posts_parent_categories,
                'blog_posts_subcategories' => $blog_posts_subcategories,
                'blog_posts_category_url' => $blog_posts_category_url,
                /* /Custom code */
            ];

            $view = new \Altum\View('blog/blog_posts_category', (array) $this);

            $this->add_view_content('content', $view->run($data));
        }

        /* Blog index */
        else {

            /* Get the posts */
            /* Prepare the filtering system */
            /* Custom code: FC-2026-03-09: disable title-only filter for ranked search */
            $filters = (new \Altum\Filters([], []));
            /* /Custom code: FC-2026-03-09 */
            $filters->set_default_order_by('datetime', $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
            $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

            /* Custom code: FC-2026-03-09: ranked blog search with aliases */
            static $blog_posts_has_search_aliases_column = null;
            if($blog_posts_has_search_aliases_column === null) {
                $blog_posts_has_search_aliases_column = (bool) count(db()->rawQuery("SHOW COLUMNS FROM `blog_posts` LIKE 'search_aliases'"));
            }

            $search = isset($_GET['search']) ? trim(query_clean($_GET['search'])) : null;
            $search = $search ? mb_substr($search, 0, 256) : null;
            $search_escaped = $search ? db()->escape(mb_strtolower($search)) : null;
            $search_aliases_column_sql = $blog_posts_has_search_aliases_column ? "COALESCE(`search_aliases`, '')" : "''";
            $search_where = $search ? "
                AND (
                    LOWER(`title`) LIKE '%{$search_escaped}%'
                    OR LOWER({$search_aliases_column_sql}) LIKE '%{$search_escaped}%'
                    OR LOWER(COALESCE(`keywords`, '')) LIKE '%{$search_escaped}%'
                    OR LOWER(COALESCE(`sku`, '')) LIKE '%{$search_escaped}%'
                )
            " : null;

            $search_score_sql = $search ? "
                CASE
                    WHEN LOWER(`title`) = '{$search_escaped}' THEN 120
                    WHEN LOWER(`title`) LIKE '{$search_escaped}%' THEN 100
                    WHEN CONCAT(',', REPLACE(LOWER({$search_aliases_column_sql}), ', ', ','), ',') LIKE '%,{$search_escaped},%' THEN 90
                    WHEN LOWER({$search_aliases_column_sql}) LIKE '{$search_escaped}%' THEN 80
                    WHEN LOWER({$search_aliases_column_sql}) LIKE '%{$search_escaped}%' THEN 70
                    WHEN LOWER(COALESCE(`keywords`, '')) LIKE '%{$search_escaped}%' THEN 50
                    WHEN LOWER(COALESCE(`sku`, '')) = '{$search_escaped}' THEN 40
                    ELSE 0
                END AS `search_score`
            " : "0 AS `search_score`";
            /* /Custom code: FC-2026-03-09 */

            /* Prepare the paginator */
            /* Custom code: FC-2026-03-09: include aliases in total rows query */
            $total_rows_query = "SELECT COUNT(*) AS `total` FROM `blog_posts` WHERE (`language` = '{$language}' OR `language` IS NULL) AND `is_published` = 1 {$search_where} {$filters->get_sql_where()}";
            /* /Custom code: FC-2026-03-09 */
            $total_rows = \Altum\Cache::cache_function_result('blog_posts_count?hash=' . md5($total_rows_query), 'blog_posts', function() use ($total_rows_query) {
                return database()->query($total_rows_query)->fetch_object()->total ?? 0;
            });
            $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('blog?' . $filters->get_get() . '&page=%d')));

            /* Blog posts query */
            /* Custom code: FC-2026-03-09: ranked ordering for search results */
            $order_by_sql = $search ? 'ORDER BY `search_score` DESC, `datetime` DESC' : $filters->get_sql_order_by();
            $blog_posts_result_query = "
                SELECT *, {$search_score_sql}
                FROM `blog_posts`
                WHERE (`language` = '{$language}' OR `language` IS NULL) AND `is_published` = 1 {$search_where} {$filters->get_sql_where()}
                {$order_by_sql}
                {$paginator->get_sql_limit()}
            ";
            /* /Custom code: FC-2026-03-09 */

            $blog_posts = \Altum\Cache::cache_function_result('blog_posts?hash=' . md5($blog_posts_result_query), 'blog_posts', function() use ($blog_posts_result_query) {
                $blog_posts_result = database()->query($blog_posts_result_query);

                /* Iterate over the blog posts */
                $blog_posts = [];

                while($row = $blog_posts_result->fetch_object()) {
                    /* Transform content if needed */
                    $row->content = json_decode($row->content) ? convert_editorjs_json_to_html($row->content) : output_blog_post_content($row->content);

                    $blog_posts[] = $row;
                }

                return $blog_posts;
            });

            /* Prepare the pagination view */
            $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

            /* Get all the categories */
            /* Custom code */
            $blog_posts_main_categories = db()->where('blog_posts_parent_id', null, 'IS')->where('language', $language)->orderBy('datetime')->get('blog_posts_categories', null, ['blog_posts_category_id', 'blog_posts_parent_id', 'title', 'description', 'url', 'language', 'style']);                    

            $blog_posts_parents = database()->query("SELECT * FROM `blog_posts_categories` WHERE `blog_posts_parent_id` IS NOT NULL AND `language` = '{$language}' OR `language` IS NULL AND `blog_posts_parent_id` IN (SELECT `blog_posts_category_id` FROM `blog_posts_categories` WHERE `blog_posts_category_id` IS NOT NULL GROUP BY `blog_posts_category_id`)");

            $blog_posts_parent_categories = [];
            
            while($row = $blog_posts_parents->fetch_object()) {
                $blog_posts_parent_categories[] = $row;
            }

            /* Get all the subcategories */
            $blog_posts_subcategories = db()->where('blog_posts_parent_id', null, 'IS NOT')->get('blog_posts_categories', null, ['blog_posts_category_id', 'blog_posts_parent_id', 'title', 'url', 'language']);     
            /* /Custom code */

            /* Get popular posts */
            $blog_posts_popular = settings()->content->blog_popular_widget_is_enabled ? (new BlogPosts())->get_popular_blog_posts_by_language($language) : [];

            if(!empty($_GET['search'])) {
                /* Set a custom title */
                Title::set(sprintf(l('blog.title_search'), input_clean($_GET['search'])));

                /* Meta */
                Meta::set_robots('noindex');
            }

            /* Prepare the view */
            $data = [
                'blog_posts' => $blog_posts,
                'pagination' => $pagination,
                'filters' => $filters,                
                'blog_posts_popular' => $blog_posts_popular,
                'blog_index_url' => SITE_URL . ($language ? ((\Altum\Language::$active_languages[$language] ?? null) ? \Altum\Language::$active_languages[$language] . '/' : null) : null) . 'blog',
                'share_url' => $this->append_referral_to_url(
                    SITE_URL . ($language ? ((\Altum\Language::$active_languages[$language] ?? null) ? \Altum\Language::$active_languages[$language] . '/' : null) : null) . 'blog' . (!empty($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''),
                    $this->get_logged_in_referral_biolink_url()
                ),
                /* Custom code */
                'blog_posts_main_categories' => $blog_posts_main_categories,
                'blog_posts_parents' => $blog_posts_parent_categories,
                'blog_posts_subcategories' => $blog_posts_subcategories  
                /* /Custom code */
            ];

            $view = new \Altum\View('blog/index', (array) $this);

            $this->add_view_content('content', $view->run($data));
        }
    }

    public function ratings_ajax() {

        if(empty($_POST)) {
            throw_404();
        }

        if(!settings()->content->blog_is_enabled || !settings()->content->blog_ratings_is_enabled) {
            throw_404();
        }

        /* Check for any errors */
        $required_fields = ['blog_post_id', 'rating'];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
            }
        }

        if(!\Altum\Csrf::check('global_token')) {
            Response::json(l('global.error_message.invalid_csrf_token'), 'error');
        }

        $blog_post_id = (int) $_POST['blog_post_id'];
        $_POST['rating'] = isset($_POST['rating']) && in_array($_POST['rating'], range(1,5)) ? (int) $_POST['rating'] : 5;

        $ip = get_ip();
        $ip_binary = $ip ? inet_pton($ip) : null;

        /* Make sure the blog post exists */
        if(!$blog_post = db()->where('blog_post_id', $blog_post_id)->getOne('blog_posts', ['blog_post_id', 'url', 'total_ratings', 'average_rating'])) {
            Response::json(l('global.error_message.basic'), 'error');
        }

        /* Check if rating exists for this tool & IP */
        $existing_rating = db()->where('blog_post_id', $blog_post_id)->where('ip_binary', $ip_binary)->getOne('blog_posts_ratings', ['rating']);

        /* Current stats */
        $current_total_score = $blog_post->total_ratings * $blog_post->average_rating;

        /* Update rating */
        if($existing_rating) {
            $old_rating = $existing_rating->rating;
            $difference = $_POST['rating'] - $old_rating;
            $new_total_ratings = $blog_post->total_ratings;
        } else {
            $difference = $_POST['rating'];
            $new_total_ratings = $blog_post->total_ratings + 1;
        }

        $new_total_score = $current_total_score + $difference;
        $new_average_rating = number_format($new_total_score / $new_total_ratings, 2, '.', '');

        /* Update tool usage stats */
        db()->where('blog_post_id', $blog_post_id)->update('blog_posts', [
            'total_ratings' => $new_total_ratings,
            'average_rating' => $new_average_rating
        ]);

        /* Insert or update rating */
        if($existing_rating) {
            db()->where('blog_post_id', $blog_post_id)->where('ip_binary', $ip_binary)->update('blog_posts_ratings', [
                'user_id' => is_logged_in() ? user()->user_id : null,
                'rating' => $_POST['rating'],
                'datetime' => get_date()
            ]);
        } else {
            db()->insert('blog_posts_ratings', [
                'user_id' => is_logged_in() ? user()->user_id : null,
                'blog_post_id' => $blog_post_id,
                'ip_binary' => $ip_binary,
                'rating' => $_POST['rating'],
                'datetime' => get_date()
            ]);
        }

        /* Clear the cache */
        cache()->deleteItemsByTag('blog_post_' . md5($blog_post->url));

        /* Set a nice success message */
        Response::json('', 'success', ['new_total_ratings' => $new_total_ratings, 'new_average_rating' => nr($new_average_rating, 2, false)]);

    }

    public function suggestions_ajax() {

        if(!settings()->content->blog_is_enabled) {
            throw_404();
        }

        /* Custom code: FC-2026-03-09: blog live autocomplete suggestions */
        static $blog_posts_has_search_aliases_column = null;
        if($blog_posts_has_search_aliases_column === null) {
            $blog_posts_has_search_aliases_column = (bool) count(db()->rawQuery("SHOW COLUMNS FROM `blog_posts` LIKE 'search_aliases'"));
        }

        $search = isset($_GET['search']) ? trim(query_clean($_GET['search'])) : null;
        $search = $search ? mb_substr($search, 0, 256) : null;

        if(!$search || mb_strlen($search) < 3) {
            Response::json('', 'success', ['results' => []]);
        }

        $language = Language::$name;
        $search_escaped = db()->escape(mb_strtolower($search));
        $search_aliases_select_sql = $blog_posts_has_search_aliases_column ? '`search_aliases`' : "'' AS `search_aliases`";
        $search_aliases_column_sql = $blog_posts_has_search_aliases_column ? "COALESCE(`search_aliases`, '')" : "''";

        $suggestions_result_query = "
            SELECT
                `blog_post_id`,
                `title`,
                `url`,
                `description`,
                `image`,
                `image_description`,
                `language`,
                {$search_aliases_select_sql},
                `keywords`,
                `sku`,
                CASE
                    WHEN LOWER(`title`) = '{$search_escaped}' THEN 120
                    WHEN LOWER(`title`) LIKE '{$search_escaped}%' THEN 100
                    WHEN CONCAT(',', REPLACE(LOWER({$search_aliases_column_sql}), ', ', ','), ',') LIKE '%,{$search_escaped},%' THEN 90
                    WHEN LOWER({$search_aliases_column_sql}) LIKE '{$search_escaped}%' THEN 80
                    WHEN LOWER({$search_aliases_column_sql}) LIKE '%{$search_escaped}%' THEN 70
                    WHEN LOWER(COALESCE(`keywords`, '')) LIKE '%{$search_escaped}%' THEN 50
                    WHEN LOWER(COALESCE(`sku`, '')) = '{$search_escaped}' THEN 40
                    ELSE 0
                END AS `search_score`
            FROM `blog_posts`
            WHERE (`language` = '{$language}' OR `language` IS NULL)
                AND `is_published` = 1
                AND (
                    LOWER(`title`) LIKE '%{$search_escaped}%'
                    OR LOWER({$search_aliases_column_sql}) LIKE '%{$search_escaped}%'
                    OR LOWER(COALESCE(`keywords`, '')) LIKE '%{$search_escaped}%'
                    OR LOWER(COALESCE(`sku`, '')) LIKE '%{$search_escaped}%'
                )
            ORDER BY `search_score` DESC, `datetime` DESC
            LIMIT 8
        ";

        $results = [];
        $suggestions_result = database()->query($suggestions_result_query);

        while($row = $suggestions_result->fetch_object()) {
            $language_prefix = $row->language ? \Altum\Language::$active_languages[$row->language] . '/' : null;
            $matched_by = 'title';
            $matched_term = $row->title;

            if(!(mb_stripos($row->title, $search) !== false)) {
                $aliases = array_filter(array_map('trim', explode(',', (string) ($row->search_aliases ?? ''))));

                foreach($aliases as $alias) {
                    if(mb_stripos($alias, $search) !== false) {
                        $matched_by = 'alias';
                        $matched_term = $alias;
                        break;
                    }
                }

                if($matched_by == 'title' && !empty($row->sku) && mb_stripos($row->sku, $search) !== false) {
                    $matched_by = 'sku';
                    $matched_term = $row->sku;
                }
            }

            $results[] = [
                'title' => $row->title,
                'url' => SITE_URL . $language_prefix . 'blog/' . $row->url,
                'description' => $row->description,
                'image' => $row->image ? \Altum\Uploads::get_full_url('blog') . $row->image : null,
                'image_description' => $row->image_description,
                'matched_by' => $matched_by,
                'matched_term' => $matched_term,
            ];
        }

        Response::json('', 'success', ['results' => $results]);
        /* /Custom code: FC-2026-03-09 */
    }

}
