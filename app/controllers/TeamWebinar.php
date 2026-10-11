<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();
require_once APP_PATH . 'helpers/fcc_team_webinar.php';

class TeamWebinar extends Controller {
    public function index() {
        if($this->params) throw_404();
        $links = fcc_partner_rows("SELECT l.*,u.type AS owner_type,u.status AS owner_status FROM links l JOIN users u ON u.user_id=l.user_id WHERE l.url=? AND l.domain_id=0 AND l.type='link' LIMIT 2", ['vip-edukacija']);
        if(count($links) !== 1) throw_404();
        $link = $links[0];
        $target = fcc_team_webinar_destination($link, ['type'=>$link['owner_type'], 'status'=>$link['owner_status']]);
        if(!$target) throw_404();
        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex, nofollow');
        header('Referrer-Policy: no-referrer');
        header('Location: ' . $target, true, 302);
        exit;
    }
}
