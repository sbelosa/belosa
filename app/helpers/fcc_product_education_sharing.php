<?php

/** Existing reviewed product photographs remain available independently of referrals. */
function fcc_product_education_product_images(array $products): array {
    if(!function_exists('fcc_product_editorial_images') || !defined('UPLOADS_PATH') || !defined('SITE_URL')) {
        return $products;
    }
    $images = fcc_product_editorial_images();
    foreach($products as &$product) {
        $key = $product['product_key'] ?? '';
        $asset = is_string($key) ? ($images[$key] ?? null) : null;
        if(!$asset && is_string($product['image_lookup_key'] ?? null) && is_string($product['image_lookup_item_code'] ?? null)) {
            $candidate = $images[$product['image_lookup_key']] ?? null;
            if(is_array($candidate) && (string) ($candidate['sku'] ?? '') === $product['image_lookup_item_code']) $asset = $candidate;
        }
        if(!is_array($asset) || !is_string($asset['file'] ?? null)
            || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*\.(?:webp|png|jpe?g)$/D', $asset['file'])
            || str_contains($asset['file'], '..')) continue;
        $file = UPLOADS_PATH . 'product-editorial/' . $asset['file'];
        if(!is_file($file) || is_link($file)) continue;
        $dimensions = @getimagesize($file);
        if(!$dimensions || $dimensions[0] <= 0 || $dimensions[1] <= 0) continue;
        $source = parse_url((string) ($asset['source'] ?? ''));
        if(!is_array($source) || ($source['scheme'] ?? '') !== 'https'
            || ($source['host'] ?? '') !== 'foreverliving.com'
            || isset($source['user']) || isset($source['pass']) || isset($source['port'])
            || !str_starts_with((string) ($source['path'] ?? ''), '/shop/')) continue;
        $product['image_url'] = rtrim(SITE_URL, '/') . '/uploads/product-editorial/' . rawurlencode($asset['file']) . '?v=' . filemtime($file);
        $product['image_alt'] = (string) ($asset['name'] ?? $product['name'] ?? 'Fotografija proizvoda');
        $product['image_width'] = (int) $dimensions[0];
        $product['image_height'] = (int) $dimensions[1];
        $product['image_source_url'] = $asset['source'];
        $market = (string) ($asset['market'] ?? '');
        $product['image_caption'] = match($market) {
            'hr' => 'Fotografija proizvoda iz hrvatske ponude. Pakiranje usporedi s proizvodom koji koristiš.',
            'au' => 'Prikaz pakiranja iz australske ponude. Dostupnost i sastav provjeri za svoju zemlju.',
            'ie' => 'Prikaz pakiranja iz irske ponude. Fotografija ne potvrđuje aktualnu formulu ni dostupnost u Hrvatskoj.',
            default => 'Fotografija proizvoda za prepoznavanje pakiranja. Aktualnu varijantu provjeri za svoju zemlju.',
        };
    }
    unset($product);
    return $products;
}

/** Reuse published product articles and their country and referral routing. */
function fcc_product_education_share_targets(array $products, int $user_id): array {
    if($user_id <= 0 || !function_exists('fcc_partner_rows')
        || !function_exists('fcc_library_product_index') || !function_exists('fcc_library_pilot_url')) {
        return $products;
    }

    $referral = '';
    $cards = fcc_partner_rows("SELECT l.link_id,l.url,l.is_enabled FROM links l WHERE l.user_id=? AND l.type='biolink' ORDER BY l.link_id", [$user_id]);
    $main = function_exists('fc_get_user_main_biolink_id') ? fc_get_user_main_biolink_id($user_id, false) : 0;
    if($main) {
        usort($cards, static fn($first, $second) => ((int) ($second['link_id'] ?? 0) === (int) $main) <=> ((int) ($first['link_id'] ?? 0) === (int) $main));
    }
    foreach($cards as $card) {
        if(!empty($card['is_enabled']) && is_string($card['url'] ?? null) && $card['url'] !== '') {
            $referral = $card['url'];
            break;
        }
    }

    if($referral === '') {
        foreach($products as &$product) {
            $product['share_notice'] = 'Za dijeljenje s tvojom preporukom najprije pripremi aktivnu FCC karticu.';
        }
        unset($product);
        return $products;
    }

    $posts = fcc_library_product_index();
    foreach($products as &$product) {
        $key = $product['product_key'] ?? '';
        $status = $product['catalog_status'] ?? 'unknown';
        if(in_array($status, ['blocked', 'identity_conflict', 'quarantined'], true)
            || $key === 'forever-ultra') {
            $product['share_notice'] = 'Prije dijeljenja potrebno je potvrditi točan identitet i aktualnu varijantu proizvoda.';
            continue;
        }
        // Exact reviewed family only. Never guess an article from a similar name or SKU.
        $post = is_string($key) ? ($posts[$key] ?? null) : null;
        if(!$post || empty($post->is_published) || ($post->url ?? '') !== $key) {
            $product['share_notice'] = 'Javni članak za ovaj proizvod još nije povezan s edukacijom.';
            continue;
        }
        $information_url = fcc_library_pilot_url('blog/' . $post->url, $referral);
        $product['share_information_url'] = $information_url;
        $product['shop_share_url'] = $information_url . '#fl-purchase';
        $product['share_notice'] = 'Dijeliš javni članak s tvojom preporukom. Primatelj u članku bira zemlju i otvara postojeću Forever ponudu.';
    }
    unset($product);
    return $products;
}
