<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();

class ForeverShop extends Controller {
    public function index() {
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
        $block_id=(int)($_GET['block']??0);$block=null;
        $ref=is_string($_GET['ref']??null)?trim($_GET['ref']):'';
        $kind=($_GET['kind']??'')==='business'?'business':'shop';
        if($block_id) {
            $block=db()->where('biolink_block_id',$block_id)->where('is_enabled',1)->getOne('biolinks_blocks');
            if(!$block||!in_array($block->type,['link_forever_webshop_reg','link_forever_shop','link_discount','link_forever_living_bih','link_forever_living_alb_kosovo'],true))throw_404();
            $card=db()->where('link_id',$block->link_id)->where('user_id',$block->user_id)->where('type','biolink')->where('is_enabled',1)->getOne('links');
            if(!$card)throw_404();
            $settings=json_decode($card->settings??'{}');
            if(!empty($settings->password)&&!hash_equals((string)$settings->password,(string)($_COOKIE['link_password_'.$card->link_id]??''))) {
                header('Location: '.url($card->url),true,302);return;
            }
            foreach([$card,$block] as $scheduled) {
                $schedule=json_decode($scheduled->settings??'{}');
                if(!empty($schedule->schedule)&&!empty($scheduled->start_date)&&!empty($scheduled->end_date)
                    &&(get_date()<$scheduled->start_date||get_date()>$scheduled->end_date))throw_404();
            }
            $ref=$card->url;$kind=$block->type==='link_forever_shop'?'business':'shop';
        }
        $owner=fcc_market_referral_owner($ref);
        if(!$owner)throw_404();
        $fbo=fcc_forever_id($owner->preferences);
        $context=fcc_forever_country_context();$country=$context['country'];
        $campaign=[];
        if($block&&$block->type==='link_discount') {
            $settings=json_decode($block->settings??'{}');
            $campaign=fcc_forever_campaign((string)($settings->decoded_url??''),$fbo,$country);
        }
        $destination=fcc_forever_market_destination($fbo,$kind,$country,$campaign);
        fcc_forever_remember_country();
        if($destination&&empty($_GET['choose'])) {
            if(($_SERVER['REQUEST_METHOD']??'GET')==='GET') {
                fc_process_monitored_forever_click([
                    'user_id'=>(int)$owner->user_id,'biolink_block_id'=>$block_id?:null,
                    'project_id'=>isset($card->project_id)?(int)$card->project_id:null,
                    'source_type'=>$block?'biolink_block':'direct_link',
                    'click_type'=>$kind==='business'?'link_forever_shop':($block->type??'link_forever_webshop_reg'),
                    'destination_url'=>$destination,
                ]);
            }
            header('Location: '.$destination,true,302);
            return;
        }
        $message=!$fbo?'invalid':($country&&!$destination?'unavailable':'unknown');
        require THEME_PATH.'views/public/forever-country.php';
    }
}
