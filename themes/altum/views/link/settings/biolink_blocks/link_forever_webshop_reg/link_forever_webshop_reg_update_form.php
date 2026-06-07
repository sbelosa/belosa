<?php defined('ALTUMCODE') || die() ?>
<?php
$fcc_forever_shop_form_block_type = 'link_forever_webshop_reg';
$fcc_forever_shop_form_location_url = $row->location_url ?: (\Altum\Link::get_forever_webshop_registration_base_url('hr') ?: 'https://foreverliving.com/shop/hrv/hr-hr/drinks/');
$fcc_forever_shop_form_notice = l('create_biolink_link_forever_webshop_reg_modal.info');
require THEME_PATH . 'views/link/settings/biolink_blocks/link_forever_shop/link_forever_shop_update_form.php';
?>
