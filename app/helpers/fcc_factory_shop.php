<?php
defined('ALTUMCODE') || die();

/** Add only the missing shopping action. Never copy a template author's referral. */
function fc_ensure_factory_shop_block(int $user_id, int $link_id): int {
    $id=fc_ensure_factory_forever_block($user_id,$link_id,'shop');
    fc_ensure_factory_forever_block($user_id,$link_id,'business');
    return $id;
}
function fc_ensure_factory_forever_block(int $user_id, int $link_id, string $kind): int {
    $type=$kind==='business'?'link_forever_shop':'link_forever_webshop_reg';
    $link = db()->where('link_id', $link_id)->where('user_id', $user_id)->where('type', 'biolink')->getOne('links', ['link_id']);
    if(!$link) throw new InvalidArgumentException(fcc_t('Kartica nije pronađena.'));
    $lock = 'fcc_factory_shop_' . $link_id . '_' . $kind;
    $connection = database();
    $statement = $connection->prepare('SELECT GET_LOCK(?, 5) AS acquired');
    $statement->bind_param('s', $lock); $statement->execute();
    if((int)($statement->get_result()->fetch_assoc()['acquired'] ?? 0) !== 1) throw new RuntimeException(fcc_t('Kartica se trenutačno uređuje.'));
    try {
        $existing = db()->where('link_id', $link_id)->where('user_id', $user_id)->where('type', $type)->getOne('biolinks_blocks', ['biolink_block_id']);
        if($existing) return (int)$existing->biolink_block_id;
        $language=(string)(db()->where('user_id',$user_id)->getValue('users','language')??'en');
        $settings = [
            'name'=>fcc_forever_copy($kind==='business'?'business':'shop',$language), 'open_in_new_tab'=>false, 'factory_shop'=>true,
            'text_color'=>'#ffffff', 'text_alignment'=>'center', 'background_color'=>'#17644f',
            'border_shadow_style'=>'none', 'border_shadow_color'=>'#00000020',
            'border_shadow_offset_x'=>0, 'border_shadow_offset_y'=>0, 'border_shadow_blur'=>0, 'border_shadow_spread'=>0,
            'border_width'=>0, 'border_style'=>'solid', 'border_color'=>'#17644f', 'border_radius'=>'rounded',
            'animation'=>false, 'animation_runs'=>'repeat-1', 'icon'=>$kind==='business'?'fas fa-user-plus':'fas fa-shopping-bag', 'image'=>'',
            'sensitive_content'=>false, 'columns'=>1,
            'display_continents'=>[], 'display_countries'=>[], 'display_cities'=>[],
            'display_devices'=>[], 'display_languages'=>[], 'display_operating_systems'=>[], 'display_browsers'=>[],
        ];
        // The public renderer resolves market and referral from the new card's owner.
        $order = (int)db()->where('link_id', $link_id)->getValue('biolinks_blocks', 'MAX(`order`)') + 1;
        $id = db()->insert('biolinks_blocks', [
            'user_id'=>$user_id, 'link_id'=>$link_id, 'type'=>$type,
            'location_url'=>'https://foreverliving.com/', 'settings'=>json_encode($settings, JSON_UNESCAPED_UNICODE),
            'order'=>$order,
            'is_enabled'=>1, 'datetime'=>get_date(),
        ]);
        if(!$id) throw new RuntimeException(fcc_t('Prodajni blok nije spremljen.'));
        cache()->deleteItem('biolink_blocks?link_id=' . $link_id);
        return (int)$id;
    } finally {
        $statement = $connection->prepare('SELECT RELEASE_LOCK(?)');
        $statement->bind_param('s', $lock); $statement->execute();
    }
}
