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
                db()->where('blog_posts_category_id', $blog_posts_category_id)->update('blog_posts_categories', [
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
                ]);

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.update1'), '<strong>' . $_POST['title'] . '</strong>'));

                /* Clear the cache */
                cache()->deleteItemsByTag('blog_posts_categories');

                redirect('admin/blog-posts-category-update/' . $blog_posts_category->blog_posts_category_id);
            }
        }

        /* Main View */
        $data = [
            'blog_posts_category' => $blog_posts_category,
            'blog_posts_parent_categories' => $blog_posts_parent_categories /* Custom code */
        ];

        $view = new \Altum\View('admin/blog-posts-category-update/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
