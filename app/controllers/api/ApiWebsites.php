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

use Altum\Models\Website;
use Altum\Response;
use Altum\Traits\Apiable;

defined('ALTUMCODE') || die();

class ApiWebsites extends Controller {
    use Apiable;

    public function index() {

        $this->verify_request();

        /* Decide what to continue with */
        switch($_SERVER['REQUEST_METHOD']) {
            case 'GET':

                /* Detect if we only need an object, or the whole list */
                if(isset($this->params[0])) {
                    $this->get();
                } else {
                    $this->get_all();
                }

                break;

            case 'DELETE':
                $this->delete();
                break;
        }

        $this->return_404();
    }

    private function get_all() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters([], [], []));
        $filters->set_default_order_by($this->api_user->preferences->websites_default_order_by, $this->api_user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->api_user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);
        $filters->process();

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `websites` WHERE `user_id` = {$this->api_user->user_id}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('api/websites?' . $filters->get_get() . '&page=%d')));

        /* Get the data */
        $data = [];
        $data_result = database()->query("
            SELECT
                *
            FROM
                `websites`
            WHERE
                `user_id` = {$this->api_user->user_id}
                {$filters->get_sql_where()}
                {$filters->get_sql_order_by()}
                  
            {$paginator->get_sql_limit()}
        ");


        while($row = $data_result->fetch_object()) {

            /* Prepare the data */
            $row = [
                'id' => (int) $row->website_id,
                'user_id' => (int) $row->user_id,
                'website_id' => (int) $row->website_id,
                'domain_id' => (int) $row->domain_id,
                'pixel_key' => $row->pixel_key,
                'name' => $row->name,
                'scheme' => $row->scheme,
                'host' => $row->host,
                'path' => $row->path,
                'settings' => json_decode($row->settings ?? ''),
                'widget' => json_decode($row->widget ?? ''),
                'button' => json_decode($row->button ?? ''),
                'notifications' => json_decode($row->notifications ?? ''),
                'keys' => json_decode($row->keys ?? ''),
                'total_sent_campaigns' => (int) $row->total_sent_campaigns,
                'total_subscribers' => (int) $row->total_subscribers,
                'total_sent_push_notifications' => (int) $row->total_sent_push_notifications,
                'total_displayed_push_notifications' => (int) $row->total_displayed_push_notifications,
                'total_clicked_push_notifications' => (int) $row->total_clicked_push_notifications,
                'total_closed_push_notifications' => (int) $row->total_closed_push_notifications,
                'is_enabled' => (bool) $row->is_enabled,
                'last_datetime' => $row->last_datetime,
                'datetime' => $row->datetime,
            ];

            $data[] = $row;
        }

        /* Prepare the data */
        $meta = [
            'page' => $_GET['page'] ?? 1,
            'total_pages' => $paginator->getNumPages(),
            'results_per_page' => $filters->get_results_per_page(),
            'total_results' => (int) $total_rows,
        ];

        /* Prepare the pagination links */
        $others = ['links' => [
            'first' => $paginator->getPageUrl(1),
            'last' => $paginator->getNumPages() ? $paginator->getPageUrl($paginator->getNumPages()) : null,
            'next' => $paginator->getNextUrl(),
            'prev' => $paginator->getPrevUrl(),
            'self' => $paginator->getPageUrl($_GET['page'] ?? 1)
        ]];

        Response::jsonapi_success($data, $meta, 200, $others);
    }

    private function get() {

        $website_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $website = db()->where('website_id', $website_id)->where('user_id', $this->api_user->user_id)->getOne('websites');

        /* We haven't found the resource */
        if(!$website) {
            $this->return_404();
        }

        /* Prepare the data */
        $data = [
            'id' => (int) $website->website_id,
            'user_id' => (int) $website->user_id,
            'website_id' => (int) $website->website_id,
            'domain_id' => (int) $website->domain_id,
            'pixel_key' => $website->pixel_key,
            'name' => $website->name,
            'scheme' => $website->scheme,
            'host' => $website->host,
            'path' => $website->path,
            'settings' => json_decode($website->settings ?? ''),
            'widget' => json_decode($website->widget ?? ''),
            'button' => json_decode($website->button ?? ''),
            'notifications' => json_decode($website->notifications ?? ''),
            'keys' => json_decode($website->keys ?? ''),
            'total_sent_campaigns' => (int) $website->total_sent_campaigns,
            'total_subscribers' => (int) $website->total_subscribers,
            'total_sent_push_notifications' => (int) $website->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $website->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $website->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $website->total_closed_push_notifications,
            'is_enabled' => (bool) $website->is_enabled,
            'last_datetime' => $website->last_datetime,
            'datetime' => $website->datetime,
        ];

        Response::jsonapi_success($data);

    }

    private function delete() {

        $website_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $website = db()->where('website_id', $website_id)->where('user_id', $this->api_user->user_id)->getOne('websites');

        /* We haven't found the resource */
        if(!$website) {
            $this->return_404();
        }

        /* Delete the resource */
        (new Website())->delete($website_id);

        http_response_code(200);
        die();

    }
}
