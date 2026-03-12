<?php
/*
 * Copyright (c) 2025 AltumCode (https://altumcode.com/)
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

namespace Altum\Models;

defined('ALTUMCODE') || die();

class BlogPostsCategories extends Model {

    public function get_blog_posts_categories_by_language($language) {

        /* Get the resources */
        $blog_posts_categories = [];

        /* Try to check if the user posts exists via the cache */
        $cache_instance = cache()->getItem('blog_posts_categories?language=' . $language);

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            /* Get data from the database */
            $blog_posts_categories_result = database()->query("
                SELECT * 
                FROM `blog_posts_categories`
                WHERE `language` = '{$language}' OR `language` IS NULL
                ORDER BY `order` ASC
            ");
            while($row = $blog_posts_categories_result->fetch_object()) $blog_posts_categories[$row->blog_posts_category_id] = $row;

            cache()->save(
                $cache_instance->set($blog_posts_categories)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('blog_posts_categories')
            );

        } else {

            /* Get cache */
            $blog_posts_categories = $cache_instance->get();

        }

        return $blog_posts_categories;

    }

    /* Custom code */
    public function get_blog_posts_subcategories_by_language($language) {

        /* Get the resources */
        $blog_posts_subcategories = [];

        /* Try to check if the user posts exists via the cache */
        $cache_instance = \Altum\Cache::$adapter->getItem('blog_posts_subcategories?language=' . $language);

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            /* Get data from the database */            
            $blog_posts_subcategories_result = database()->query("
                SELECT * 
                FROM `blog_posts_categories`
                WHERE `blog_posts_parent_id` IS NOT NULL
                AND `language` = '{$language}' OR `language` IS NULL
                ORDER BY `blog_posts_parent_id` ASC
            ");
            while($row = $blog_posts_subcategories_result->fetch_object()) {                
                $blog_posts_subcategories[$row->blog_posts_category_id] = $row;
            } 

            \Altum\Cache::$adapter->save(
                $cache_instance->set($blog_posts_subcategories)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('blog_posts_subcategories')
            );

        } else {

            /* Get cache */
            $blog_posts_subcategories = $cache_instance->get();

        }

        return $blog_posts_subcategories;

    }

    public function get_blog_category_children($category_id) {
        $language = \Altum\Language::$name;

        /* Get the resources */
        $blog_posts_subcategories = [];

        /* Get data from the database */            
        $blog_posts_subcategories_result = database()->query("
            SELECT blog_posts_category_id
            FROM `blog_posts_categories`
            WHERE `blog_posts_parent_id` = $category_id            
            AND `language` = '{$language}' OR `language` IS NULL   
            ORDER BY CASE WHEN blog_posts_parent_id IS NULL THEN blog_posts_category_id ELSE blog_posts_parent_id END, blog_posts_category_id         
        ");
        while($row = $blog_posts_subcategories_result->fetch_object()) {                
            $blog_posts_subcategories[$row->blog_posts_category_id] = $row;
            
            $blog_posts_subcategories[]['children'] = $this->get_blog_category_children($blog_posts_subcategories[$row->blog_posts_category_id]->blog_posts_category_id);
        }

  
        return $blog_posts_subcategories;

    }

    
    public function get_blog_category_children_nolang($category_id) {
        $language = \Altum\Language::$name;

        /* Get the resources */
        $blog_posts_subcategories = [];

        /* Get data from the database */            
        $blog_posts_subcategories_result = database()->query("
            SELECT blog_posts_category_id
            FROM `blog_posts_categories`
            WHERE `blog_posts_parent_id` = $category_id            
            ORDER BY CASE WHEN blog_posts_parent_id IS NULL THEN blog_posts_category_id ELSE blog_posts_parent_id END, blog_posts_category_id         
        ");
        while($row = $blog_posts_subcategories_result->fetch_object()) {                
            $blog_posts_subcategories[$row->blog_posts_category_id] = $row;
            
            $blog_posts_subcategories[]['children'] = $this->get_blog_category_children($blog_posts_subcategories[$row->blog_posts_category_id]->blog_posts_category_id);
        }

  
        return $blog_posts_subcategories;

    }

    public function get_blog_category_parent($category_id) {
        $language = \Altum\Language::$name;

        /* Get the resources */
        $blog_posts_parent = [];

        /* Get data from the database */            
        $blog_posts_subcategories_result = database()->query("
            SELECT blog_posts_category_id, blog_posts_parent_id
            FROM `blog_posts_categories`
            WHERE `blog_posts_category_id` = $category_id   
        ");

        if (!$blog_posts_subcategories_result) {
            return;    
        }
        
        while($row = $blog_posts_subcategories_result->fetch_object()) {                
            //$blog_posts_parent[] = $row;
            
            if (isset($row->blog_posts_parent_id)) {
                $blog_posts_parent[] = $this->get_blog_category_parent($row->blog_posts_parent_id);
            } else {
                $blog_posts_parent[] = $row->blog_posts_category_id;
            }
           
        }
        
        return end($blog_posts_parent);        
    }

    public static function get_url($category_id) {
        if ($category_id) {
            $url = db()->where('blog_posts_category_id', $category_id)->getOne('blog_posts_categories', ['url']);
            
            return $url ? url('blog') . '/category/' . $url->url : url('blog');
        }

        return url('blog');
    }
    /* /Custom code */

}
