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

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Title;

defined('ALTUMCODE') || die();

class SubscribersStatistics extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        if(!$this->user->plan_settings->analytics_is_enabled) {
            Alerts::add_error(l('global.info_message.plan_feature_no_access'));
            redirect('subscribers');
        }

        $website_url_query = '';
        $website_id_query = 'AND `user_id` = ' . $this->user->user_id;
        if(isset($_GET['website_id'])) {
            $website_id = isset($_GET['website_id']) ? (int) $_GET['website_id'] : null;

            /* Get the website */
            $website = (new \Altum\Models\Website())->get_website_by_website_id($website_id);

            if(!$website) {
                redirect('websites');
            }

            if($website->user_id != $this->user->user_id) {
                redirect('websites');
            }

            $website_url_query = 'website_id=' . $website->website_id;
            $website_id_query = 'AND `website_id` = ' . $website->website_id;
        }

        /* Statistics related variables */
        $type = isset($_GET['type']) && in_array($_GET['type'], ['overview', 'continent_code', 'country', 'city_name', 'os', 'browser', 'device', 'language', 'subscribed_on_url']) ? input_clean($_GET['type']) : 'overview';

        $datetime = \Altum\Date::get_start_end_dates_new();

        /* Get data based on what statistics are needed */
        switch($type) {
            case 'overview':

                /* Get the required statistics */
                $subscribers_chart = [];

                $convert_tz_sql = get_convert_tz_sql('`datetime`', $this->user->timezone);

                $subscribers_result = database()->query("
                    SELECT
                        COUNT(*) AS `total`,
                        DATE_FORMAT({$convert_tz_sql}, '{$datetime['query_date_format']}') AS `formatted_date`
                    FROM
                         `subscribers`
                    WHERE
                        1 = 1
                        $website_id_query
                        AND ({$convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                    GROUP BY
                        `formatted_date`
                    ORDER BY
                        `formatted_date`
                ");

                /* Generate the raw chart data and save subscribers for later usage */
                while($row = $subscribers_result->fetch_object()) {
                    $subscribers[] = $row;

                    $row->formatted_date = $datetime['process']($row->formatted_date, true);

                    $subscribers_chart[$row->formatted_date] = [
                        'total' => $row->total,
                    ];
                }

                $subscribers_chart = get_chart_data($subscribers_chart);

                $limit = $this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page;
                $result = database()->query("
                    SELECT
                        *
                    FROM
                        `subscribers`
                    WHERE
                        1 = 1
                        $website_id_query
                        AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                    ORDER BY
                        `datetime` DESC
                    LIMIT {$limit}
                ");

                break;

            case 'continent_code':
            case 'os':
            case 'browser':
            case 'device':
            case 'language':
            case 'subscribed_on_url':

                $columns = [
                    'continent_code' => 'continent_code',
                    'country' => 'country_code',
                    'city_name' => 'city_name',
                    'os' => 'os_name',
                    'browser' => 'browser_name',
                    'device' => 'device_type',
                    'language' => 'browser_language',
                    'subscribed_on_url' => 'subscribed_on_url',
                ];

                $result = database()->query("
                    SELECT
                        `{$columns[$type]}`,
                        COUNT(*) AS `total`
                    FROM
                         `subscribers`
                    WHERE
                        1 = 1
                        $website_id_query
                        AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                    GROUP BY
                        `{$columns[$type]}`
                    ORDER BY
                        `total` DESC
                    
                ");

                break;

            case 'country':

                $continent_code = isset($_GET['continent_code']) ? input_clean($_GET['continent_code']) : null;

                $result = database()->query("
                    SELECT
                        `country_code`,
                        " . ($continent_code ? "`continent_code`," : null) . "
                        COUNT(*) AS `total`
                    FROM
                         `subscribers`
                    WHERE
                        1 = 1
                        $website_id_query
                        " . ($continent_code ? "AND `continent_code` = '{$continent_code}'" : null) . "
                        AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                    GROUP BY
                        " . ($continent_code ? "`continent_code`," : null) . "
                        `country_code`
                    ORDER BY
                        `total` DESC
                    
                ");

                break;

            case 'city_name':

                $country_code = isset($_GET['country_code']) ? input_clean($_GET['country_code']) : null;

                $result = database()->query("
                    SELECT
                        `country_code`,
                        `city_name`,
                        COUNT(*) AS `total`
                    FROM
                         `subscribers`
                    WHERE
                        1 = 1
                        $website_id_query
                        " . ($country_code ? "AND `country_code` = '{$country_code}'" : null) . "
                        AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                    GROUP BY
                        `country_code`,
                        `city_name`
                    ORDER BY
                        `total` DESC
                    
                ");


                break;

        }

        switch($type) {
            case 'overview':

                $statistics_keys = [
                    'continent_code',
                    'country_code',
                    'city_name',
                    'device_type',
                    'os_name',
                    'browser_name',
                    'browser_language',
                    'subscribed_on_url',
                ];

                $statistics = [];
                foreach($statistics_keys as $key) {
                    $statistics[$key] = [];
                    $statistics[$key . '_total_sum'] = 0;
                }

                $has_data = $result->num_rows;

                /* Start processing the rows from the database */
                while($row = $result->fetch_object()) {
                    foreach($statistics_keys as $key) {

                        $row->{$key} = $row->{$key} ?? '';

                        $statistics[$key][$row->{$key}] = isset($statistics[$key][$row->{$key}]) ? $statistics[$key][$row->{$key}] + 1 : 1;

                        $statistics[$key . '_total_sum']++;

                    }
                }

                foreach($statistics_keys as $key) {
                    arsort($statistics[$key]);
                }

                /* Prepare the statistics method View */
                $data = [
                    'statistics' => $statistics,
                    'website' => $website ?? null,
                    'datetime' => $datetime,
                    'subscribers_chart' => $subscribers_chart ?? null,
                    'has_data' => $has_data,
                    'website_url_query' => $website_url_query,
                ];

                break;

            case 'continent_code':
            case 'country':
            case 'city_name':
            case 'os':
            case 'browser':
            case 'device':
            case 'language':
            case 'subscribed_on_url':

                /* Store all the results from the database */
                $statistics = [];
                $statistics_total_sum = 0;

                while($row = $result->fetch_object()) {
                    $statistics[] = $row;

                    $statistics_total_sum += $row->total;
                }

                $has_data = count($statistics);

                /* Prepare the statistics method View */
                $data = [
                    'rows' => $statistics,
                    'total_sum' => $statistics_total_sum,
                    'website' => $website ?? null,
                    'datetime' => $datetime,
                    'has_data' => $has_data,
                    'website_url_query' => $website_url_query,
                    'continent_code' => $continent_code ?? null,
                    'country_code' => $country_code ?? null,
                ];


                break;
        }

        /* Set a custom title */
        if(isset($website)) {
            Title::set(sprintf(l('subscribers_statistics.title_dynamic'), $website->name));
        } else {
            Title::set(l('subscribers_statistics.title'));
        }

        /* Export handler */
        process_export_csv($statistics);
        process_export_json($statistics);

        $data['type'] = $type;
        $view = new \Altum\View('subscribers-statistics/statistics_' . $type, (array) $this);
        $this->add_view_content('statistics', $view->run($data));

        /* Get available custom domains */
        $domains = (new \Altum\Models\Domain())->get_available_domains_by_user($this->user);

        /* Prepare the view */
        $data = [
            'website' => $website ?? null,
            'type' => $type,
            'datetime' => $datetime,
            'has_data' => $has_data,
            'domains' => $domains,
            'website_url_query' => $website_url_query,
        ];

        $view = new \Altum\View('subscribers-statistics/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
