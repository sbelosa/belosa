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

defined('ALTUMCODE') || die();

class AdminQrCode extends Controller {

    public function index() {
        try {
            $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

            if(!$user_id) {
                Alerts::add_error(l('admin_qrcode.not.found'));
                redirect('admin/users');
            }

            $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'name']);

            if(!$user) {
                Alerts::add_error(l('admin_qrcode.not.found'));
                redirect('admin/users');
            }

            $main_biolink_url = $this->get_main_biolink_url($user_id);

            if(!$main_biolink_url) {
                Alerts::add_error(l('admin_qrcode.not.found'));
                redirect('admin/users');
            }

            $card_style_version = 'v13_font30_centered';
            $card_hash = md5($main_biolink_url . '|' . $user->name . '|' . $card_style_version);
            $card_png = UPLOADS_PATH . 'qr_code/' . 'card_user_' . $user_id . '_' . $card_hash . '.png';

            if(!file_exists($card_png)) {
                $this->generate_print_card_png($user_id, $main_biolink_url, $user->name, $card_hash, $card_png);
            }

            if(!file_exists($card_png)) {
                Alerts::add_error(l('admin_qrcode.not.found'));
                redirect('admin/users');
            }

            clearstatcache();

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . get_slug($user->name) . '.png"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($card_png));

            if(ob_get_level()) {
                ob_clean();
            }

            flush();
            readfile($card_png);
            die();
        } catch(\Throwable $exception) {
            error_log('[AdminQrCode] ' . $exception->getMessage() . ' @ ' . $exception->getFile() . ':' . $exception->getLine());
            Alerts::add_error(l('admin_qrcode.not.found'));
            redirect('admin/users');
        }
    }

    private function get_main_biolink_url($user_id) {
        $biolink_id = db()->where('user_id', $user_id)->getValue('users_biolinks', 'biolink_id');

        if(!$biolink_id) {
            $fallback_biolink = db()->where('user_id', $user_id)->where('type', 'biolink')->orderBy('link_id', 'ASC')->getOne('links', ['link_id', 'url']);

            if($fallback_biolink && $fallback_biolink->link_id) {
                $biolink_id = $fallback_biolink->link_id;

                $already_set = db()->where('user_id', $user_id)->getOne('users_biolinks', ['user_id']);
                if(!$already_set) {
                    db()->insert('users_biolinks', [
                        'user_id' => $user_id,
                        'biolink_id' => $biolink_id
                    ]);
                }
            }
        }

        if(!$biolink_id) {
            return null;
        }

        $biolink = db()->where('link_id', $biolink_id)->where('type', 'biolink')->getOne('links', ['url']);

        if(!$biolink || !$biolink->url) {
            return null;
        }

        return SITE_URL . $biolink->url;
    }

    private function generate_print_card_png($user_id, $url, $full_name, $card_hash, $card_png_path) {
        $qr_svg_path = UPLOADS_PATH . 'qr_code/' . 'card_user_' . $user_id . '_' . $card_hash . '.svg';
        $qr_png_path = UPLOADS_PATH . 'qr_code/' . 'card_user_' . $user_id . '_' . $card_hash . '_qr.png';
        $existing_qr = db()->where('user_id', $user_id)->orderBy('qr_code_id', 'DESC')->getOne('qr_codes', ['qr_code']);

        if($existing_qr && $existing_qr->qr_code) {
            $existing_qr_file = UPLOADS_PATH . 'qr_code/' . $existing_qr->qr_code;
            $existing_qr_extension = mb_strtolower(pathinfo($existing_qr_file, PATHINFO_EXTENSION));

            if(file_exists($existing_qr_file) && $existing_qr_extension === 'png') {
                copy($existing_qr_file, $qr_png_path);
            }

            if(file_exists($existing_qr_file) && $existing_qr_extension === 'svg') {
                copy($existing_qr_file, $qr_svg_path);
            }
        }

        if(!file_exists($qr_png_path) && !file_exists($qr_svg_path) && class_exists('\\SimpleSoftwareIO\\QrCode\\Generator')) {
            $qr = new \SimpleSoftwareIO\QrCode\Generator;
            $qr->size(520);
            $qr->margin(0);
            $qr->errorCorrection('M');
            $qr->encoding('UTF-8');
            $svg = $qr->generate($url);

            file_put_contents($qr_svg_path, $svg);
        }

        $magick_binary = trim((string) shell_exec('command -v magick'));
        $convert_binary = trim((string) shell_exec('command -v convert'));

        if(!file_exists($qr_png_path) && file_exists($qr_svg_path) && $magick_binary) {
            exec(escapeshellarg($magick_binary) . ' convert -density 300x300 -background none ' . escapeshellarg($qr_svg_path) . ' ' . escapeshellarg($qr_png_path));
        } elseif(!file_exists($qr_png_path) && file_exists($qr_svg_path) && $convert_binary) {
            exec(escapeshellarg($convert_binary) . ' -density 300x300 -background none ' . escapeshellarg($qr_svg_path) . ' ' . escapeshellarg($qr_png_path));
        }

        if(!file_exists($qr_png_path)) {
            return;
        }

        $print_dpi = 300;
        $card_width = (int) round((86 / 25.4) * $print_dpi);
        $card_height = (int) round((54 / 25.4) * $print_dpi);

        $horizontal_padding = (int) round($card_width * 0.06);
        $top_margin = (int) round($card_height * 0.08);
        $bottom_margin = (int) round($card_height * 0.08);
        $text_area_height = (int) round($card_height * 0.20);
        $available_qr_height = (int) max(1, $card_height - $top_margin - $text_area_height - (int) round($card_height * 0.04));
        $qr_size = (int) min((int) round($card_width * 0.27), $available_qr_height);
        $qr_x = (int) (($card_width - $qr_size) / 2);
        $qr_y = (int) ($top_margin + max(0, (int) (($available_qr_height - $qr_size) / 2)));

        $canvas = imagecreatetruecolor($card_width, $card_height);
        imagealphablending($canvas, false);
        if(function_exists('imageresolution')) {
            imageresolution($canvas, $print_dpi, $print_dpi);
        }
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagesavealpha($canvas, true);
        imagealphablending($canvas, true);

        $qr_image = @imagecreatefrompng($qr_png_path);
        if(!$qr_image) {
            imagedestroy($canvas);
            return;
        }

        imagecopyresampled($canvas, $qr_image, $qr_x, $qr_y, 0, 0, $qr_size, $qr_size, imagesx($qr_image), imagesy($qr_image));
        imagedestroy($qr_image);

        $text = preg_replace('/\s+/', ' ', mb_strtoupper(trim((string) $full_name)));
        $text_color = imagecolorallocate($canvas, 0, 0, 0);
        $font_candidates = [
            ASSETS_PATH . 'fonts/Inter-Bold.ttf',
            ASSETS_PATH . 'css/fonts/segoe-ui.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSansCondensed-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        ];
        $font_file = null;
        foreach($font_candidates as $candidate_font_file) {
            if(file_exists($candidate_font_file)) {
                $font_file = $candidate_font_file;
                break;
            }
        }

        if($font_file && function_exists('imagettftext')) {
            $max_text_width = max(1, $card_width - ($horizontal_padding * 2));
            $font_size_max = 32;
            $font_size_min = 16;
            $font_size = $font_size_max;

            for($size = $font_size_max; $size >= $font_size_min; $size--) {
                $bbox = imagettfbbox($size, 0, $font_file, $text);
                $text_width = abs($bbox[2] - $bbox[0]);

                if($text_width <= $max_text_width) {
                    $font_size = $size;
                    break;
                }

                $font_size = $size;
            }

            $display_text = $text;
            $bbox = imagettfbbox($font_size, 0, $font_file, $display_text);
            $text_width = abs($bbox[2] - $bbox[0]);

            if($text_width > $max_text_width) {
                while(mb_strlen($display_text) > 3) {
                    $display_text = rtrim(mb_substr($display_text, 0, -1));
                    $candidate = $display_text . '...';
                    $candidate_bbox = imagettfbbox($font_size, 0, $font_file, $candidate);
                    $candidate_width = abs($candidate_bbox[2] - $candidate_bbox[0]);

                    if($candidate_width <= $max_text_width) {
                        $display_text = $candidate;
                        $text_width = $candidate_width;
                        break;
                    }
                }
            }

            $final_bbox = imagettfbbox($font_size, 0, $font_file, $display_text);
            $text_height = abs($final_bbox[7] - $final_bbox[1]);
            $qr_bottom = $qr_y + $qr_size;
            $text_area_top = $qr_bottom;
            $text_area_bottom = $card_height;
            $text_area_available_height = max(1, $text_area_bottom - $text_area_top);
            $text_top = (int) ($text_area_top + (($text_area_available_height - $text_height) / 2));

            $text_x = (int) (($card_width - $text_width) / 2);
            $text_y = $text_top + $text_height;

            imagettftext($canvas, $font_size, 0, $text_x, $text_y, $text_color, $font_file, $display_text);
        } else {
            error_log('[AdminQrCode] TTF font not found, fallback font used.');
            $fallback_x = (int) (($card_width - (imagefontwidth(5) * mb_strlen($text))) / 2);
            $fallback_y = (int) ($card_height - $bottom_margin - 16);
            imagestring($canvas, 5, $fallback_x, max(0, $fallback_y), $text, $text_color);
        }

        imagepng($canvas, $card_png_path);
        imagedestroy($canvas);

        if(file_exists($qr_svg_path)) {
            @unlink($qr_svg_path);
        }

        if(file_exists($qr_png_path)) {
            @unlink($qr_png_path);
        }
    }
}
/* /Custom code */
