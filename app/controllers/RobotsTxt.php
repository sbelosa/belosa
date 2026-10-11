<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();
/** Fallback when an installation has no administrator-managed root robots.txt. */
class RobotsTxt extends Controller {
    public function index() {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-cache');
        $private = getenv('FCC_LOCAL') === '1' || empty(settings()->main->se_indexing);
        echo "User-agent: *\n" . ($private ? "Disallow: /\n" : "Allow: /\n\nSitemap: " . SITE_URL . "sitemap\n");
    }
}
