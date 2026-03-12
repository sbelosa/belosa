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

use Altum\Response;
use Altum\Traits\Apiable;

defined('ALTUMCODE') || die();

class ApiSubscribersStatistics extends Controller {
    use Apiable;
    public $website;
    public $datetime;

    public function index() {

        $this->verify_request();

        /* Decide what to continue with */
        switch($_SERVER['REQUEST_METHOD']) {
            case 'GET':

                /* Detect if we only need an object, or the whole list */
                if(isset($this->params[0])) {
                    $this->get();
                }

            break;
        }

        $this->return_404();
    }

    private function get() {

        $website_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $this->website = $website = db()->where('website_id', $website_id)->where('user_id', $this->api_user->user_id)->getOne('websites');

        /* We haven't found the resource */
        if(!$website) {
            $this->return_404();
        }

        /* :) */
        $this->datetime = \Altum\Date::get_start_end_dates_new();

        $type = isset($_GET['type']) && in_array($_GET['type'], [
            'overview',
            'continent_code',
            'country_code',
            'city_name',
            'os_name',
            'browser_name',
            'device_type',
            'browser_language',
            'subscribed_on_url',
        ]) ? query_clean($_GET['type']) : 'overview';

        /* :) */
        $data = [];

        switch($type) {
            case 'overview':

                $convert_tz_sql = get_convert_tz_sql('`datetime`', \Altum\Date::$default_timezone);

                $result = database()->query("
                    SELECT
                        COUNT(*) AS `subscribers`,
                        DATE_FORMAT({$convert_tz_sql}, '{$this->datetime['query_date_format']}') AS `formatted_date`
                    FROM
                         `subscribers`
                    WHERE
                        `website_id` = {$this->website->website_id}
                        AND ({$convert_tz_sql} BETWEEN '{$this->datetime['query_start_date']}' AND '{$this->datetime['query_end_date']}')
                    GROUP BY
                        `formatted_date`
                    ORDER BY
                        `formatted_date`
                ");

                while($row = $result->fetch_object()) {
                    $data[] = [
                        'subscribers' => (int) $row->subscribers,
                        'formatted_date' => $this->datetime['process']($row->formatted_date, true),
                    ];
                }

                break;

            case 'continent_code':
            case 'country_code':
            case 'os_name':
            case 'browser_name':
            case 'device_type':
            case 'browser_language':
            case 'subscribed_on_url':

                $result = database()->query("
                    SELECT
                        `{$type}`,
                        COUNT(*) AS `subscribers`
                    FROM
                         `subscribers`
                    WHERE
                        `website_id` = {$this->website->website_id}
                        AND (`datetime` BETWEEN '{$this->datetime['query_start_date']}' AND '{$this->datetime['query_end_date']}')
                    GROUP BY
                        `{$type}`
                    ORDER BY
                        `subscribers` DESC
                    
                ");

                while($row = $result->fetch_object()) {
                    $data[] = [
                        $type => $row->{$type},
                        'subscribers' => (int) $row->subscribers
                    ];
                }

                break;

            case 'city_name':

                $country_code = isset($_GET['country_code']) ? trim(query_clean($_GET['country_code'])) : null;

                $result = database()->query("
                    SELECT
                        `country_code`,
                        `city_name`,
                        COUNT(*) AS `subscribers`
                    FROM
                         `subscribers`
                    WHERE
                        `website_id` = {$this->website->website_id}
                        " . ($country_code ? "AND `country_code` = '{$country_code}'" : null) . "
                        AND (`datetime` BETWEEN '{$this->datetime['query_start_date']}' AND '{$this->datetime['query_end_date']}')
                    GROUP BY
                        `country_code`,
                        `city_name`
                    ORDER BY
                        `subscribers` DESC
                    
                ");

                while($row = $result->fetch_object()) {
                    $data[] = [
                        'country_code' => $row->country_code,
                        'country_name' => $row->country_code ? get_country_from_country_code($row->country_code) : null,
                        $type => $row->{$type},
                        'subscribers' => (int) $row->subscribers
                    ];
                }

                break;

        }

        Response::jsonapi_success($data);

    }

}
