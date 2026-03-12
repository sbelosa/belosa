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

defined('ALTUMCODE') || die();

class AdminSegments extends Controller {

    public function index() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['website_id', 'type', 'segment_id'], ['name',], ['segment_id', 'name', 'datetime', 'last_datetime', 'total_subscribers']));
        $filters->set_default_order_by($this->user->preferences->segments_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `segments` WHERE 1 = 1 {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('admin/segments?' . $filters->get_get() . '&page=%d')));

        /* Get the segments list for the user */
        $segments = [];
        $segments_result = database()->query("
            SELECT
                `segments`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `users`.`avatar` AS `user_avatar`, `websites`.`host`, `websites`.`path`
            FROM
                `segments`
            LEFT JOIN
                `users` ON `segments`.`user_id` = `users`.`user_id`
            LEFT JOIN
                `websites` ON `segments`.`website_id` = `websites`.`website_id`
            WHERE
                1 = 1
                {$filters->get_sql_where('segments')}
                {$filters->get_sql_order_by('segments')}
            
            {$paginator->get_sql_limit()}
        ");
        while($row = $segments_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '');
            $segments[] = $row;
        }

        /* Export handler */
        process_export_json($segments, ['segment_id', 'user_id', 'name', 'type', 'total_subscribers', 'settings', 'datetime', 'last_datetime',]);
        process_export_csv_new($segments, ['segment_id', 'user_id', 'name', 'type', 'total_subscribers', 'settings', 'datetime', 'last_datetime',], ['settings']);

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Prepare the view */
        $data = [
            'segments' => $segments,
            'pagination' => $pagination,
            'filters' => $filters,
        ];

        $view = new \Altum\View('admin/segments/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('admin/segments');
        }

        if(empty($_POST['selected'])) {
            redirect('admin/segments');
        }

        if(!isset($_POST['type'])) {
            redirect('admin/segments');
        }

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            set_time_limit(0);

            session_write_close();

            switch($_POST['type']) {
                case 'delete':

                    foreach($_POST['selected'] as $segment_id) {
                        if($segment = db()->where('segment_id', $segment_id)->getOne('segments', ['user_id', 'segment_id'])) {
                            db()->where('segment_id', $segment_id)->delete('segments');

                            /* Clear the cache */
                            cache()->deleteItem('segments?user_id=' . $segment->user_id);
                            cache()->deleteItem('segment?segment_id=' . $segment_id);
                        }
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('admin/segments');
    }

    public function delete() {

        $segment_id = (isset($this->params[0])) ? (int) $this->params[0] : null;

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$segment = db()->where('segment_id', $segment_id)->getOne('segments', ['user_id', 'segment_id', 'name'])) {
            redirect('admin/segments');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Database query */
            db()->where('segment_id', $segment_id)->delete('segments');

            /* Clear the cache */
            cache()->deleteItem('segments?user_id=' . $segment->user_id);
            cache()->deleteItem('segment?segment_id=' . $segment_id);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $segment->name . '</strong>'));

        }

        redirect('admin/segments');
    }
}
