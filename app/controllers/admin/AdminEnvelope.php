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

    private function get_user_meta_object($preferences): \stdClass {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!$preferences instanceof \stdClass) {
            $preferences = (object) [];
        }

        $meta = $preferences->meta ?? null;

        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        if(!$meta instanceof \stdClass) {
            $meta = (object) [];
        }

        return $meta;
    }

    private function generate_envelope_pdf(int $user_id): ?string {
        if($user_id <= 0) {
            return null;
        }

        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'name', 'preferences']);

        if(!$user) {
            return null;
        }

        $meta = $this->get_user_meta_object($user->preferences ?? null);
        $pdf_path = UPLOADS_PATH . 'qr_code/' . $user_id . '.pdf';

        $name = mb_strtoupper(trim((string) ($user->name ?? '')));
        $address = trim((string) ($meta->address ?? ''));
        $city = trim(implode(' ', array_filter([
            trim((string) ($meta->zip ?? '')),
            trim((string) ($meta->city ?? '')),
        ])));
        $country = trim((string) ($meta->country ?? ''));

        if($country === '') {
            $country = 'Hrvatska';
        } elseif(mb_strtolower($country) !== 'hrvatska') {
            $country = ucfirst($country);
        }

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0, true);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage();
        $pdf->SetFont('DejaVuSansCondensed', 'B', 20);
        $pdf->ln(44);
        $pdf->Cell(10);
        $pdf->Cell(75, 8, $name, 0);
        $pdf->SetFont('DejaVuSansCondensed', 'B', 13);
        $pdf->ln(9);
        $pdf->Cell(10);
        $pdf->Cell(75, 8, $address, 0);
        $pdf->ln(7);
        $pdf->Cell(10);
        $pdf->Cell(75, 8, $city, 0);
        $pdf->ln(7);
        $pdf->Cell(10);
        $pdf->Cell(75, 8, $country, 0);
        $pdf->Output($pdf_path, 'F');

        return file_exists($pdf_path) ? $pdf_path : null;
    }

    public function index() {
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        $mode = input_clean($_GET['mode'] ?? '', 16);

        if(!$user_id) {
            Alerts::add_error(sprintf(l('admin_pdf.not.found')));
            redirect('admin/users');
        }

        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'name']);

        if(!$user) {
            Alerts::add_error(sprintf(l('admin_pdf.not.found')));
            redirect('admin/users');
        }

        $file_pdf = UPLOADS_PATH . 'qr_code/' . $user_id . '.pdf';

        if(!file_exists($file_pdf)) {
            $file_pdf = $this->generate_envelope_pdf($user_id);
        }

        if($file_pdf && file_exists($file_pdf)) {
            clearstatcache();

            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: ' . ($mode === 'print' ? 'inline' : 'attachment') . '; filename="' . get_slug($user->name) . '-pismo.pdf"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_pdf));

            if(ob_get_level()) {
                ob_clean();
            }

            flush();
            readfile($file_pdf);
            die();
        } else {
            Alerts::add_error(sprintf(l('admin_pdf.not.found')));
        }

        $data = [];

        $view = new \Altum\View('admin/envelope/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }
}
/* /Custom code */
