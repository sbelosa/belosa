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

namespace Altum;

defined('ALTUMCODE') || die();

class Filters {

    public $allowed_filters = [];
    public $allowed_order_by = [];
    public $allowed_search_by = ['title']; /* Custom code */
    public $allowed_results_per_page = [];

    public $filters = [];
    public $filters_types = [];
    public $search = '';
    public $search_by = '';
    public $order_by = '';
    public $order_type = '';
    public $results_per_page = 25;

    public $get = [];
    public $has_applied_filters = false;

    private $is_processed = false;

    public function __construct($allowed_filters = [], $allowed_search_by = ['title'], $allowed_order_by = [], $allowed_results_per_page = [], $filters_types = []) { /* Custom code */

        $this->allowed_filters = $allowed_filters;
        $this->filters_types = $filters_types;
        $this->allowed_order_by = $allowed_order_by;
        $this->allowed_search_by = $allowed_search_by;
        $this->allowed_results_per_page = empty($allowed_results_per_page) ? [10, 25, 50, 100, 250, 500, 1000] : $allowed_results_per_page;

    }

    public function process() {

        /* Filters */
        foreach($this->allowed_filters as $filter) {

            if(isset($_GET[$filter]) && $_GET[$filter] != '') {
                $this->filters[$filter] = query_clean($_GET[$filter]);
                $this->get[$filter] = $_GET[$filter];
            }

        }

        /* Search */
        if(count($this->allowed_search_by) && isset($_GET['search']) && isset($_GET['search_by']) && in_array($_GET['search_by'], $this->allowed_search_by)) {

            $_GET['search'] = query_clean($_GET['search']);
            $_GET['search_by'] = query_clean($_GET['search_by']);

            $this->search = $_GET['search'];
            $this->search_by = $_GET['search_by'];

            $this->get['search'] = $_GET['search'];
            $this->get['search_by'] = $_GET['search_by'];

            /* Custom code */
            $_GET['type'] = query_clean($_GET['type']);
            if ( $_GET['type'] ) {
                $this->type = $_GET['type'];
            }
            /* /Custom code */
        }

        /* Order by */
        if(count($this->allowed_order_by) && isset($_GET['order_by']) && in_array($_GET['order_by'], $this->allowed_order_by)) {

            $_GET['order_by'] = query_clean($_GET['order_by']);
            $order_type = isset($_GET['order_type']) && in_array($_GET['order_type'], ['ASC', 'DESC']) ? query_clean($_GET['order_type']) : 'ASC';

            $this->order_by = $_GET['order_by'];
            $this->order_type = $order_type;

            $this->get['order_by'] = $_GET['order_by'];
            $this->get['order_type'] = $_GET['order_type'];
        }

        /* Results per page */
        if(isset($_GET['results_per_page']) && in_array($_GET['results_per_page'], $this->allowed_results_per_page)) {
            $this->results_per_page = (int) $_GET['results_per_page'];
            $this->get['results_per_page'] = $_GET['results_per_page'];
        }

        if(count($this->get)) $this->has_applied_filters = true;

    }

    public function get_sql_where($table_prefix = null) {
        if(!$this->is_processed) $this->process();

        $where = '';

        $table_prefix = $table_prefix ? "`{$table_prefix}`." : null;

        /* Filters */
        foreach($this->filters as $key => $value) {
            if(isset($this->filters_types[$key])) {
                switch($this->filters_types[$key]) {
                    case 'json_contains':
                        /* Only allow numbers for array json searching */
                        $value = (int) $value;
                        $where .= " AND JSON_CONTAINS(COALESCE(NULLIF({$table_prefix}`{$key}`, ''), '[]'), '{$value}', '$')";
                        break;
                }
            } else {
                /* Classic */
                $where .= " AND {$table_prefix}`{$key}` = '{$value}'";
            }
        }

        /* Search */
        if($this->search && $this->search_by) {
            $where .= " AND {$table_prefix}`{$this->search_by}` LIKE '%{$this->search}%'";
        }

        /* Custom code */
        if($this->type && $this->type == 'blog') {
            $language = Language::$name;
                    
            $where .= " OR {$table_prefix}`description` LIKE '%{$this->search}%'";
            $where .= " OR {$table_prefix}`content` LIKE '%{$this->search}%'";
            $where .= " AND {$table_prefix}`language` LIKE '{$language}'";
        }
        /* /Custom code */

        return $where;
    }

    public function get_sql_order_by($table_prefix = null) {
        if(!$this->is_processed) $this->process();

        $order_by = '';

        $table_prefix = $table_prefix ? "`{$table_prefix}`." : null;

        /* Order By */
        if($this->order_by && $this->order_type) {
            $order_by .= " ORDER BY {$table_prefix}`{$this->order_by}` {$this->order_type}";
        }

        return $order_by;
    }

    public function get_results_per_page() {
        return $this->results_per_page;
    }

    public function get_get() {
        $get = [];

        foreach($this->get as $key => $value) {
            $get[] = $key . '=' . $value;
        }

        return implode('&', $get);
    }

    public function set_default_order_by($order_by, $order_type) {

        if(!in_array($order_type, ['ASC', 'DESC'])) {
            $order_type = 'DESC';
        }

        if(!$order_by && count($this->allowed_order_by)) {
            $order_by = reset($this->allowed_order_by);
        }

        $this->order_by = $order_by;
        $this->order_type = $order_type;
    }

    public function set_default_results_per_page($results_per_page) {

        if(!$results_per_page) {
            $results_per_page = 25;
        }

        $this->results_per_page = $results_per_page;
    }

    /* Custom code */    
    public function generate_category_slug($string, $parent_id = null, $replacement = '-', $lowercase = true) {
        if (empty($string)) {
            return (string) $string;
        }
        if ((string)$replacement !== '') {
            $parts = explode($replacement, static::transliterate($string));
        } else {
            $parts = [static::transliterate($string)];
        }

        $replaced = array_map(function ($element) use ($replacement) {
            $element = preg_replace('/[^a-zA-Z0-9=\s—–-]+/u', '', $element);
            return preg_replace('/[=\s—–-]+/u', $replacement, $element);
        }, $parts);

        $string = trim(implode($replacement, $replaced), $replacement);
        if ((string)$replacement !== '') {
            $string = preg_replace('#' . preg_quote($replacement, '#') . '+#', $replacement, $string);
        }

        if (!is_null($parent_id)) {
            $parent = db()->where('blog_posts_category_id', $parent_id)->getOne('blog_posts_categories');

            if ($parent) {
                $string = $parent->url . '/' . $string;
            }
        }

        return $lowercase ? strtolower($string) : $string;
    }

    public static function transliterate($string, $transliterator = null)
    {
        if (empty($string)) {
            return (string) $string;
        }
        if (static::hasIntl()) {
            if ($transliterator === null) {
                $transliterator = static::$transliterator;
            }

            return transliterator_transliterate($transliterator, $string);
        }

        return strtr($string, static::$transliteration);
    }

      /**
     * @return bool if intl extension is loaded
     */
    protected static function hasIntl()
    {
        return extension_loaded('intl');
    }

    /**
     * @var array fallback map for transliteration used by [[transliterate()]] when intl isn't available.
     */
    public static $transliteration = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
        'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
        'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
        'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
        'ÿ' => 'y',
    ];

    /* Used in [[transliterate()]].
    * For detailed information see [unicode normalization forms](https://unicode.org/reports/tr15/#Normalization_Forms_Table)
    * @see https://unicode.org/reports/tr15/#Normalization_Forms_Table
    * @see transliterate()
    * @since 2.0.7
    */
   const TRANSLITERATE_LOOSE = 'Any-Latin; Latin-ASCII; [\u0080-\uffff] remove';

   /**
    * @var mixed Either a [[\Transliterator]], or a string from which a [[\Transliterator]] can be built
    * for transliteration. Used by [[transliterate()]] when intl is available. Defaults to [[TRANSLITERATE_LOOSE]]
    * @see https://www.php.net/manual/en/transliterator.transliterate.php
    */
   public static $transliterator = self::TRANSLITERATE_LOOSE;

   /* /Custom code */
}
