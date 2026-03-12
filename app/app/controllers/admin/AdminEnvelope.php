<?php
/*
 * @copyright Copyright (c) 2023 AltumCode (https://altumcode.com/)
 *
 * This software is exclusively sold through https://altumcode.com/ by the AltumCode author.
 * Downloading this product from any other sources and running it without a proper license is illegal,
 *  except the official ones linked from https://altumcode.com/.
 */

/* Custom code */
namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Models\QrCode;

defined('ALTUMCODE') || die();

class AdminEnvelope extends Controller {

    public function index() {
        $filters = (new \Altum\Filters(['user_id']));

        $file_pdf = UPLOADS_PATH . 'qr_code/' . $_GET['user_id'] . '.pdf';

        $data = [];

        if (file_exists($file_pdf)) {
            $data = [
                'file_pdf' => $file_pdf,
            ];
        } else {
            Alerts::add_error(sprintf(l('admin_pdf.not.found')));
        }        

        $view = new \Altum\View('admin/envelope/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }
}
/* /Custom code */
