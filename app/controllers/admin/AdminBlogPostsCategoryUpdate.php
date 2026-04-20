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

use Altum\Alerts;

defined('ALTUMCODE') || die();

class AdminBlogPostsCategoryUpdate extends Controller {

    public function index() {

        $blog_posts_category_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Check if resource exists */
        if(!$blog_posts_category = db()->where('blog_posts_category_id', $blog_posts_category_id)->getOne('blog_posts_categories')) {
            redirect('admin/blog-posts-categories');
        }
        
        $blog_posts_parent_categories = db()->get('blog_posts_categories', null, ['blog_posts_category_id', 'title']); /* Custom code */
        
        if(!empty($_POST)) {
            /* Filter some of the variables */
            /* Custom code */
            $_POST['parent_id'] = !empty($_POST['parent_id']) ? input_clean($_POST['parent_id']) : null;
            $_POST['show_share_links'] = $_POST['show_share_links'] == 'on' ? 1 : 0;
            $_POST['visibility'] = !empty($_POST['visibility']) ? input_clean($_POST['visibility']) : 'null';
            $_POST['url'] = !empty($_POST['url']) ? $_POST['url'] : (new Filters())->generate_category_slug(input_clean($_POST['title'], 256), $_POST['parent_id']);
            /* /Custom code */            
            $_POST['title'] = input_clean($_POST['title'], 256);
            $_POST['description'] = input_clean($_POST['description'], 256);
            $_POST['language'] = !empty($_POST['language']) ? input_clean($_POST['language']) : null;
            $_POST['order'] = (int) $_POST['order'] ?? 0;
            $shop_context_supported = fc_blog_posts_categories_has_shop_context_column();
            $shop_context = $shop_context_supported ? fc_blog_category_shop_context_encode([
                'page_role' => $_POST['shop_context_page_role'] ?? '',
                'hero_badge' => $_POST['shop_context_hero_badge'] ?? '',
                'hero_subtitle' => $_POST['shop_context_hero_subtitle'] ?? '',
                'hero_note' => $_POST['shop_context_hero_note'] ?? '',
                'meta_title' => $_POST['shop_context_meta_title'] ?? '',
                'meta_description' => $_POST['shop_context_meta_description'] ?? '',
                'meta_keywords' => $_POST['shop_context_meta_keywords'] ?? '',
                'subcategories_title' => $_POST['shop_context_subcategories_title'] ?? '',
                'guide_title' => $_POST['shop_context_guide_title'] ?? '',
                'featured_title' => $_POST['shop_context_featured_title'] ?? '',
                'discovery_eyebrow' => $_POST['shop_context_discovery_eyebrow'] ?? '',
                'discovery_title' => $_POST['shop_context_discovery_title'] ?? '',
                'discovery_subtitle' => $_POST['shop_context_discovery_subtitle'] ?? '',
                'seo_title' => $_POST['shop_context_seo_title'] ?? '',
                'faq_title' => $_POST['shop_context_faq_title'] ?? '',
                'product_count_label' => $_POST['shop_context_product_count_label'] ?? '',
                'shop_ready_count_label' => $_POST['shop_context_shop_ready_count_label'] ?? '',
                'market_count_label' => $_POST['shop_context_market_count_label'] ?? '',
                'guide_items' => fc_blog_shop_context_parse_pairs_text($_POST['shop_context_guide_items'] ?? '', 'title', 'text', 6, 160, 420),
                'seo_paragraphs' => fc_blog_shop_context_parse_list_text($_POST['shop_context_seo_paragraphs'] ?? '', 6, 700),
                'faq_items' => fc_blog_shop_context_parse_pairs_text($_POST['shop_context_faq_items'] ?? '', 'q', 'a', 10, 180, 700),
                'featured_post_urls' => fc_blog_shop_context_parse_list_text($_POST['shop_context_featured_post_urls'] ?? '', 8, 256),
                'filter_chips' => fc_blog_shop_context_parse_pairs_text($_POST['shop_context_filter_chips'] ?? '', 'label', 'terms', 5, 80, 220),
            ]) : null;

            //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

            /* Check for any errors */
            $required_fields = ['title', 'url'];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    Alerts::add_field_error($field, l('global.error_message.empty_field'));
                }
            }

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if(db()->where('blog_posts_category_id', $blog_posts_category->blog_posts_category_id, '<>')->where('url', $_POST['url'])->where('language', $_POST['language'])->has('blog_posts_categories')) {
                Alerts::add_field_error('url', l('admin_blog.error_message.url_exists'));
            }

            /* If there are no errors, continue */
            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                /* Custom code */
                 if (!is_null($_POST['parent_id'])) {
                     $blog_posts_parent_category = db()->where('blog_posts_category_id', $_POST['parent_id'])->getOne('blog_posts_categories');
                     if (isset($blog_posts_parent_category->url) && $blog_posts_parent_category->url) {
                        $url_length = strlen($blog_posts_parent_category->url);                          
                        
                        if (substr($_POST['url'], 0, $url_length) != $blog_posts_parent_category->url) {                                                        
                            $_POST['url'] = $blog_posts_parent_category->url . '/' . $_POST['url'];
                        }
                     }
                }                
                /* /Custom code */
                /* Database query */
                $blog_posts_category_data = [
                    /* Custom code */
                    'blog_posts_parent_id' => $_POST['parent_id'],
                    'visibility' => $_POST['visibility'],
                    'show_share_links' => $_POST['show_share_links'],
                    /* /Custom code */
                    'url' => $_POST['url'],
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'language' => $_POST['language'],
                    'order' => $_POST['order'],
                    'last_datetime' => get_date(),
                ];

                if($shop_context_supported) {
                    $blog_posts_category_data['shop_context'] = $shop_context;
                }

                db()->where('blog_posts_category_id', $blog_posts_category_id)->update('blog_posts_categories', $blog_posts_category_data);

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.update1'), '<strong>' . $_POST['title'] . '</strong>'));

                /* Clear the cache */
                cache()->deleteItemsByTag('blog_posts_categories');

                redirect('admin/blog-posts-category-update/' . $blog_posts_category->blog_posts_category_id);
            }
        }

        $shop_context_form_data = fc_blog_category_shop_context_to_form_data($blog_posts_category->shop_context ?? null);

        if(!empty($_POST)) {
            $shop_context_form_data = array_merge($shop_context_form_data, [
                'page_role' => (string) ($_POST['shop_context_page_role'] ?? ''),
                'hero_badge' => (string) ($_POST['shop_context_hero_badge'] ?? ''),
                'hero_subtitle' => (string) ($_POST['shop_context_hero_subtitle'] ?? ''),
                'hero_note' => (string) ($_POST['shop_context_hero_note'] ?? ''),
                'meta_title' => (string) ($_POST['shop_context_meta_title'] ?? ''),
                'meta_description' => (string) ($_POST['shop_context_meta_description'] ?? ''),
                'meta_keywords' => (string) ($_POST['shop_context_meta_keywords'] ?? ''),
                'subcategories_title' => (string) ($_POST['shop_context_subcategories_title'] ?? ''),
                'guide_title' => (string) ($_POST['shop_context_guide_title'] ?? ''),
                'featured_title' => (string) ($_POST['shop_context_featured_title'] ?? ''),
                'discovery_eyebrow' => (string) ($_POST['shop_context_discovery_eyebrow'] ?? ''),
                'discovery_title' => (string) ($_POST['shop_context_discovery_title'] ?? ''),
                'discovery_subtitle' => (string) ($_POST['shop_context_discovery_subtitle'] ?? ''),
                'seo_title' => (string) ($_POST['shop_context_seo_title'] ?? ''),
                'faq_title' => (string) ($_POST['shop_context_faq_title'] ?? ''),
                'product_count_label' => (string) ($_POST['shop_context_product_count_label'] ?? ''),
                'shop_ready_count_label' => (string) ($_POST['shop_context_shop_ready_count_label'] ?? ''),
                'market_count_label' => (string) ($_POST['shop_context_market_count_label'] ?? ''),
                'guide_items' => (string) ($_POST['shop_context_guide_items'] ?? ''),
                'seo_paragraphs' => (string) ($_POST['shop_context_seo_paragraphs'] ?? ''),
                'faq_items' => (string) ($_POST['shop_context_faq_items'] ?? ''),
                'featured_post_urls' => (string) ($_POST['shop_context_featured_post_urls'] ?? ''),
                'filter_chips' => (string) ($_POST['shop_context_filter_chips'] ?? ''),
            ]);
        }

        $shop_context_completion = fc_blog_category_shop_context_completion([
            'page_role' => $shop_context_form_data['page_role'] ?? '',
            'hero_subtitle' => $shop_context_form_data['hero_subtitle'] ?? '',
            'meta_title' => $shop_context_form_data['meta_title'] ?? '',
            'meta_description' => $shop_context_form_data['meta_description'] ?? '',
            'meta_keywords' => $shop_context_form_data['meta_keywords'] ?? '',
            'featured_post_urls' => fc_blog_shop_context_parse_list_text($shop_context_form_data['featured_post_urls'] ?? '', 8, 256),
            'filter_chips' => fc_blog_shop_context_parse_pairs_text($shop_context_form_data['filter_chips'] ?? '', 'label', 'terms', 5, 80, 220),
            'seo_paragraphs' => fc_blog_shop_context_parse_list_text($shop_context_form_data['seo_paragraphs'] ?? '', 6, 700),
            'faq_items' => fc_blog_shop_context_parse_pairs_text($shop_context_form_data['faq_items'] ?? '', 'q', 'a', 10, 180, 700),
        ]);

        /* Main View */
        $data = [
            'blog_posts_category' => $blog_posts_category,
            'blog_posts_parent_categories' => $blog_posts_parent_categories, /* Custom code */
            'blog_category_shop_context_supported' => fc_blog_posts_categories_has_shop_context_column(),
            'shop_context_form' => (object) $shop_context_form_data,
            'shop_context_completion' => $shop_context_completion,
        ];

        $view = new \Altum\View('admin/blog-posts-category-update/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
