<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * View all other existing AltumCode projects via https://altumcode.com/
 * Get in touch for support or general queries via https://altumcode.com/contact
 * Download the latest version via https://altumcode.com/downloads
 */

namespace Altum\Controllers;

use Altum\Models\Billing;

defined('ALTUMCODE') || die();

class AdminBillingRisk extends Controller {

    /* Custom code: FC-2026-03-17: admin billing risk investigation list */
    public function index() {
        if(!in_array(settings()->license->type, ['Extended License', 'extended'])) {
            redirect('admin');
        }

        $billing = new Billing();

        $search = input_clean($_GET['search'] ?? '', 128);
        $state = input_clean($_GET['state'] ?? '', 32);
        $processor = input_clean($_GET['processor'] ?? '', 32);
        $results_per_page = (int) ($_GET['results_per_page'] ?? ($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page));
        $results_per_page = in_array($results_per_page, [10, 25, 50, 100], true) ? $results_per_page : 25;

        $filters = [
            'search' => $search,
            'state' => $state,
            'processor' => $processor,
        ];

        $query_parameters = [];
        foreach($filters as $key => $value) {
            if($value !== '') {
                $query_parameters[$key] = $value;
            }
        }

        $query_parameters['results_per_page'] = $results_per_page;

        $total_rows = $billing->count_risk_users($filters);
        $paginator = new \Altum\Paginator($total_rows, $results_per_page, $_GET['page'] ?? 1, url('admin/billing-risk' . (!empty($query_parameters) ? '?' . http_build_query($query_parameters) . '&page=%d' : '?page=%d')));

        $risk_users = $billing->get_risk_users($filters, $paginator->getItemsPerPage(), $paginator->getSqlOffset());
        $pagination = (new \Altum\View('partials/admin_pagination', (array) $this))->run(['paginator' => $paginator]);

        $data = [
            'risk_users' => $risk_users,
            'pagination' => $pagination,
            'search' => $search,
            'state' => $state,
            'processor' => $processor,
            'results_per_page' => $results_per_page,
            'total_rows' => $total_rows,
            'payment_processors' => require APP_PATH . 'includes/payment_processors.php',
        ];

        $view = new \Altum\View('admin/billing-risk/index', (array) $this);

        $this->add_view_content('content', $view->run($data));
    }
    /* /Custom code: FC-2026-03-17 */
}