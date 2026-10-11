<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();

class LanguagePreference extends Controller {
    public function index() {
        header('Cache-Control: no-store, private');
        if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            return;
        }
        if(!isset($_POST['token']) || !is_string($_POST['token']) || !hash_equals(\Altum\Csrf::get(), $_POST['token'])) {
            http_response_code(403);
            echo fcc_t('Sesija je istekla. Osvježi stranicu i pokušaj ponovno.');
            return;
        }
        $code = is_string($_POST['language'] ?? null) ? $_POST['language'] : '';
        $name = fcc_language_name($code);
        if($name === null) { http_response_code(422); return; }
        if(\Altum\Authentication::$user_id) {
            db()->where('user_id', (int) \Altum\Authentication::$user_id)->update('users', ['language'=>$name]);
            cache()->deleteItemsByTag('user_id=' . (int) \Altum\Authentication::$user_id);
            if(\Altum\Authentication::$user) \Altum\Authentication::$user->language = $name;
        }
        setcookie('fcc_language', $name, ['expires'=>time()+31536000, 'path'=>COOKIE_PATH ?: '/', 'secure'=>str_starts_with(SITE_URL, 'https://'), 'httponly'=>true, 'samesite'=>'Lax']);
        setcookie('set_language', '', ['expires'=>time()-3600, 'path'=>COOKIE_PATH ?: '/', 'secure'=>str_starts_with(SITE_URL, 'https://'), 'httponly'=>true, 'samesite'=>'Lax']);
        $return = fcc_language_return_path(is_string($_POST['return_to'] ?? null) ? $_POST['return_to'] : 'partner');
        $prefix = $code === \Altum\Language::$default_code ? '' : $code . '/';
        header('Location: ' . (parse_url(SITE_URL, PHP_URL_PATH) ?: '/') . $prefix . $return, true, 303);
    }
}
