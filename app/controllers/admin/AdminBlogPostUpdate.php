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

class AdminBlogPostUpdate extends Controller {

    public function index() {

        $blog_post_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Check if resource exists */
        if(!$blog_post = db()->where('blog_post_id', $blog_post_id)->getOne('blog_posts')) {
            redirect('admin/blog-posts');
        }

        if(!empty($_POST)) {
            /* Filter some of the variables */
            $_POST['url'] = input_clean(get_slug($_POST['url']), 256);
            $_POST['title'] = input_clean($_POST['title'], 256);
            $_POST['description'] = input_clean($_POST['description'], 256);
            $_POST['image_description'] = input_clean($_POST['image_description'], 256);
            $_POST['keywords'] = input_clean($_POST['keywords'], 256);
            $_POST['editor'] = in_array($_POST['editor'], ['wysiwyg', 'blocks', 'raw']) ? input_clean($_POST['editor']) : 'raw';
            $_POST['blog_posts_category_id'] = empty($_POST['blog_posts_category_id']) ? null : (int) $_POST['blog_posts_category_id'];
            $_POST['language'] = !empty($_POST['language']) ? input_clean($_POST['language']) : null;
            $_POST['is_published'] = (int) isset($_POST['is_published']);
            $_POST['content'] = $_POST['editor'] == 'wysiwyg' ? quilljs_to_bootstrap($_POST['content']) : $_POST['content'];

            /* Custom code: FC-2026-03-09: normalize blog search aliases */
            $search_aliases_array = preg_split('/[\r\n,]+/', $_POST['search_aliases'] ?? '');
            $search_aliases_array = array_filter(array_map(function($value) {
                return trim(input_clean($value, 128));
            }, $search_aliases_array));
            $search_aliases_array = array_values(array_unique($search_aliases_array));
            $_POST['search_aliases'] = mb_substr(implode(', ', $search_aliases_array), 0, 2000);
            /* /Custom code: FC-2026-03-09 */

            /* Custom code: FC-2026-04-19: normalize structured shop landing context */
            $shop_context_supported = fc_blog_posts_has_shop_context_column();
            $shop_context = $shop_context_supported ? fc_blog_shop_context_encode([
                'page_role' => $_POST['shop_context_page_role'] ?? '',
                'trust_note' => $_POST['shop_context_trust_note'] ?? '',
                'decision_title' => $_POST['shop_context_decision_title'] ?? '',
                'checks_title' => $_POST['shop_context_checks_title'] ?? '',
                'action_title' => $_POST['shop_context_action_title'] ?? '',
                'action_subtitle' => $_POST['shop_context_action_subtitle'] ?? '',
                'primary_cta_label' => $_POST['shop_context_primary_cta_label'] ?? '',
                'secondary_cta_label' => $_POST['shop_context_secondary_cta_label'] ?? '',
                'related_eyebrow' => $_POST['shop_context_related_eyebrow'] ?? '',
                'related_title' => $_POST['shop_context_related_title'] ?? '',
                'meta_title' => $_POST['shop_context_meta_title'] ?? '',
                'meta_description' => $_POST['shop_context_meta_description'] ?? '',
                'meta_keywords' => $_POST['shop_context_meta_keywords'] ?? '',
                'summary_cards' => fc_blog_shop_context_parse_pairs_text($_POST['shop_context_summary_cards'] ?? '', 'label', 'value', 6, 120, 220),
                'ideal_for' => fc_blog_shop_context_parse_list_text($_POST['shop_context_ideal_for'] ?? '', 6, 240),
                'quick_checks' => fc_blog_shop_context_parse_list_text($_POST['shop_context_quick_checks'] ?? '', 8, 240),
                'faq' => fc_blog_shop_context_parse_pairs_text($_POST['shop_context_faq'] ?? '', 'question', 'answer', 10, 180, 700),
            ]) : null;
            /* /Custom code: FC-2026-04-19 */

            /* Custom code */
            $_POST['webshop_links'] = json_encode([
                'hr' => $_POST['webshop_links_hr'],                    
                'ba' => $_POST['webshop_links_ba'],
                'al' => $_POST['webshop_links_al'],                    
                'si' => $_POST['webshop_links_si'],
                'rs' => $_POST['webshop_links_rs'],                
                'at' => $_POST['webshop_links_at'],           
                'au' => $_POST['webshop_links_au'],           
                'ca' => $_POST['webshop_links_ca'],           
                'de' => $_POST['webshop_links_de'],           
                'ie' => $_POST['webshop_links_ie'],           
                'lu' => $_POST['webshop_links_lu'],           
                'nl' => $_POST['webshop_links_nl'],           
                'no' => $_POST['webshop_links_no'],           
                'pl' => $_POST['webshop_links_pl'],           
                'se' => $_POST['webshop_links_se'],
                'gb' => $_POST['webshop_links_gb'],
                'us' => $_POST['webshop_links_us'],
                'qa' => $_POST['webshop_links_qa'],
                'ch' => $_POST['webshop_links_ch'],
                'ae' => $_POST['webshop_links_ae']
            ]);
            /* /Custom code */

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

            if(db()->where('blog_post_id', $blog_post->blog_post_id, '<>')->where('url', $_POST['url'])->where('language', $_POST['language'])->has('blog_posts')) {
                Alerts::add_field_error('url', l('admin_blog.error_message.url_exists'));
            }

            $blog_post->image = \Altum\Uploads::process_upload($blog_post->image, 'blog', 'image', 'image_remove', null);

            /* If there are no errors, continue */
            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                /* Custom code: FC-2026-03-09: support databases without search_aliases column */
                static $blog_posts_has_search_aliases_column = null;
                if($blog_posts_has_search_aliases_column === null) {
                    $blog_posts_has_search_aliases_column = (bool) count(db()->rawQuery("SHOW COLUMNS FROM `blog_posts` LIKE 'search_aliases'"));
                }

                $blog_post_data = [
                    'blog_posts_category_id' => $_POST['blog_posts_category_id'],
                    'url' => $_POST['url'],
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'keywords' => $_POST['keywords'],
                    'image' => $blog_post->image,
                    'image_description' => $_POST['image_description'],
                    'editor' => $_POST['editor'],
                    'content' => $_POST['content'],
                    'language' => $_POST['language'],
                    'is_published' => $_POST['is_published'],
                    'last_datetime' => get_date(),
                    'webshop_links' => $_POST['webshop_links'],
                    'sku' => $_POST['sku'],
                ];

                if($blog_posts_has_search_aliases_column) {
                    $blog_post_data['search_aliases'] = $_POST['search_aliases'];
                }

                if($shop_context_supported) {
                    $blog_post_data['shop_context'] = $shop_context;
                }
                /* /Custom code: FC-2026-03-09 */

                /* Database query */
                db()->where('blog_post_id', $blog_post->blog_post_id)->update('blog_posts', $blog_post_data);

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.update1'), '<strong>' . $_POST['title'] . '</strong>'));

                /* Clear the cache */
                cache()->deleteItemsByTag('blog_posts');

                redirect('admin/blog-post-update/' . $blog_post_id);
            }
        }

        /* Get the blog posts categories available */
        //$blog_posts_categories = db()->get('blog_posts_categories', null, ['blog_posts_category_id', 'title']);
        /* Custom code */
        $blog_posts_main_categories = db()->where('blog_posts_parent_id', null, 'IS')->get('blog_posts_categories', null, ['blog_posts_category_id', 'blog_posts_parent_id', 'title']);

        $blog_posts_parents = database()->query("SELECT * FROM `blog_posts_categories` WHERE `blog_posts_parent_id` IS NOT NULL AND `blog_posts_parent_id` IN (SELECT `blog_posts_category_id` FROM `blog_posts_categories` WHERE `blog_posts_category_id` IS NOT NULL GROUP BY `blog_posts_category_id`)");

        while($row = $blog_posts_parents->fetch_object()) {
            $blog_posts_parent_categories[] = $row;
        }

        /* Get all the subcategories */
        $blog_posts_subcategories = db()->where('blog_posts_parent_id', null, 'IS NOT')->get('blog_posts_categories', null, ['blog_posts_category_id', 'blog_posts_parent_id', 'title']);
        /* /Custom code */

        $shop_context_form_data = fc_blog_shop_context_to_form_data($blog_post->shop_context ?? null);

        if(!empty($_POST)) {
            $shop_context_form_data = array_merge($shop_context_form_data, [
                'page_role' => (string) ($_POST['shop_context_page_role'] ?? ''),
                'trust_note' => (string) ($_POST['shop_context_trust_note'] ?? ''),
                'decision_title' => (string) ($_POST['shop_context_decision_title'] ?? ''),
                'checks_title' => (string) ($_POST['shop_context_checks_title'] ?? ''),
                'action_title' => (string) ($_POST['shop_context_action_title'] ?? ''),
                'action_subtitle' => (string) ($_POST['shop_context_action_subtitle'] ?? ''),
                'primary_cta_label' => (string) ($_POST['shop_context_primary_cta_label'] ?? ''),
                'secondary_cta_label' => (string) ($_POST['shop_context_secondary_cta_label'] ?? ''),
                'related_eyebrow' => (string) ($_POST['shop_context_related_eyebrow'] ?? ''),
                'related_title' => (string) ($_POST['shop_context_related_title'] ?? ''),
                'meta_title' => (string) ($_POST['shop_context_meta_title'] ?? ''),
                'meta_description' => (string) ($_POST['shop_context_meta_description'] ?? ''),
                'meta_keywords' => (string) ($_POST['shop_context_meta_keywords'] ?? ''),
                'summary_cards' => (string) ($_POST['shop_context_summary_cards'] ?? ''),
                'ideal_for' => (string) ($_POST['shop_context_ideal_for'] ?? ''),
                'quick_checks' => (string) ($_POST['shop_context_quick_checks'] ?? ''),
                'faq' => (string) ($_POST['shop_context_faq'] ?? ''),
            ]);
        }

        /* Main View */
        $data = [
            'blog_posts_main_categories' => $blog_posts_main_categories,
            'blog_posts_parents' => $blog_posts_parent_categories,
            'blog_posts_subcategories' => $blog_posts_subcategories,            
            'blog_post' => $blog_post,
            'webshop_links' => json_decode($blog_post->webshop_links),
            'blog_shop_context_supported' => fc_blog_posts_has_shop_context_column(),
            'shop_context_form' => (object) $shop_context_form_data,
        ];

        $view = new \Altum\View('admin/blog-post-update/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
