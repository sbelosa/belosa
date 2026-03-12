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
use Altum\Models\Website;

defined('ALTUMCODE') || die();

class AdminPwas extends Controller {

    public function index() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['user_id',], ['name'], ['pwa_id', 'name', 'last_datetime', 'datetime']));
        $filters->set_default_order_by($this->user->preferences->pwas_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `pwas` WHERE 1 = 1 {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('admin/pwas?' . $filters->get_get() . '&page=%d')));

        /* Get the pwas list for the user */
        $pwas = [];
        $pwas_result = database()->query("
            SELECT
                `pwas`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `users`.`avatar` AS `user_avatar`
            FROM
                `pwas`
            LEFT JOIN
                `users` ON `pwas`.`user_id` = `users`.`user_id`
            WHERE
                1 = 1
                {$filters->get_sql_where('pwas')}
                {$filters->get_sql_order_by('pwas')}
            
            {$paginator->get_sql_limit()}
        ");
        while($row = $pwas_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '');
            $pwas[] = $row;
        }

        /* Export handler */
        process_export_csv_new($pwas, ['pwa_id', 'user_id', 'name', 'name', 'settings', 'last_datetime', 'datetime'], ['settings'], sprintf(l('pwas.title')));
        process_export_json($pwas, ['pwa_id', 'user_id', 'name', 'name', 'settings', 'manifest', 'last_datetime', 'datetime'], sprintf(l('pwas.title')));

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Prepare the view */
        $data = [
            'pwas' => $pwas,
            'pagination' => $pagination,
            'filters' => $filters,
        ];

        $view = new \Altum\View('admin/pwas/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('admin/pwas');
        }

        if(empty($_POST['selected'])) {
            redirect('admin/pwas');
        }

        if(!isset($_POST['type'])) {
            redirect('admin/pwas');
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

                    foreach($_POST['selected'] as $pwa_id) {
                        db()->where('pwa_id', $pwa_id)->delete('pwas');
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('admin/pwas');
    }

    public function delete() {

        $pwa_id = (isset($this->params[0])) ? (int) $this->params[0] : null;

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$pwa = db()->where('pwa_id', $pwa_id)->getOne('pwas', ['pwa_id', 'name'])) {
            redirect('admin/pwas');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Delete the resource */
            db()->where('pwa_id', $pwa_id)->delete('pwas');

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $pwa->name . '</strong>'));

        }

        redirect('admin/pwas');
    }
}
