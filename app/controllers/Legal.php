<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();

class Legal extends Controller {
    public function index() {
        \Altum\Router::$controller_settings['has_view'] = false;
        $document = (string)($this->params[0] ?? 'privacy');
        if(!in_array($document, ['privacy', 'terms'], true) || isset($this->params[1])) throw_404();
        if(!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
            http_response_code(405); header('Allow: GET, HEAD'); return;
        }
        $config = fcc_legal_config();
        $content = fcc_legal_document($document);
        $local = getenv('FCC_LOCAL') === '1';
        $indexable = !$local && !empty(settings()->main->se_indexing) && !fcc_legal_pending();
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-cache');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        if(!$indexable) header('X-Robots-Tag: noindex, follow');
        $nonce = base64_encode(random_bytes(18));
        header("Content-Security-Policy: default-src 'none'; script-src 'nonce-{$nonce}'; style-src 'self'; img-src 'self' data:; base-uri 'none'; frame-ancestors 'self'; form-action 'none'");
        require THEME_PATH . 'views/legal/index.php';
    }
}
