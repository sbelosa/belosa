<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-02-26: EN/HR widget titles */
$fcc_blog_categories_title = l('blog.categories');
$fcc_blog_popular_title = l('blog.popular');
$fcc_blog_post_url = $data->blog_post_url ?? (SITE_URL . ($data->blog_post->language ? \Altum\Language::$active_languages[$data->blog_post->language] . '/' : null) . 'blog/' . $data->blog_post->url);
$share_referral_key = null;
if(isset($data->referral) && $data->referral) {
    $share_referral_key = $data->referral;
} elseif(isset($data->biolink->url) && $data->biolink->url) {
    $share_referral_key = $data->biolink->url;
}

$share_url = $data->share_url ?? $fcc_blog_post_url;
$fcc_blog_product_cta_url = $data->tracked_webshop_link ?: ($data->webshop_link ?: null);
$fcc_blog_contact_cta_url = !empty($data->referral) ? url($data->referral) : null;
$fcc_blog_primary_cta_url = $fcc_blog_product_cta_url ?: $fcc_blog_contact_cta_url;
$fcc_blog_shop_context = fc_blog_shop_context_normalize($data->blog_post->shop_context ?? null);
$fcc_blog_public_bundle = (array) ($data->blog_post_public_bundle ?? []);
$fcc_shop_page_role = (string) ($fcc_blog_shop_context['page_role'] ?? '');
$fcc_is_start_package_context = in_array(mb_strtolower((string) ($data->blog_post->url ?? '')), ['start-paket', 'start-package'], true) || $fcc_shop_page_role === 'business_start';
$fcc_is_product_context = !empty($fcc_blog_product_cta_url)
    || !empty($data->blog_post->sku)
    || in_array((string) ($data->blog_posts_category->url ?? ''), ['forever-products', 'forever-proizvodi'], true)
    || in_array($fcc_shop_page_role, ['product', 'business_start'], true);

$fcc_webshop_links = json_decode($data->blog_post->webshop_links ?? '{}', true) ?: [];
$fcc_webshop_markets = array_values(array_filter(array_keys($fcc_webshop_links), static function($market_code) use ($fcc_webshop_links) {
    return !empty($fcc_webshop_links[$market_code]);
}));
$fcc_market_count = count($fcc_webshop_markets);
$fcc_uses_global_market_display = $fcc_is_product_context && !$fcc_is_start_package_context;
$fcc_global_market_display_value = $fcc_uses_global_market_display ? '151+' : null;
$fcc_global_market_display_chip = \Altum\Language::$code === 'hr' ? '151+ tržišta' : '151+ markets';
$fcc_global_market_display_detail = \Altum\Language::$code === 'hr' ? '151+ tržišta svijeta' : '151+ countries worldwide';
$fcc_related_blog_posts = !empty($data->related_blog_posts) ? array_values($data->related_blog_posts) : [];

$fcc_rendered_blog_content = (new \Altum\Shortcodes)->display_shortcodes($data->blog_post->content, $data->referral ?? null);
$fcc_blog_toc = [];
$fcc_blog_key_sections = [];

if($fcc_rendered_blog_content !== '' && class_exists('DOMDocument')) {
    $dom = new \DOMDocument();
    $previous_state = libxml_use_internal_errors(true);

    if($dom->loadHTML('<?xml encoding="utf-8" ?><div id="fcc_blog_content_root">' . $fcc_rendered_blog_content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
        $xpath = new \DOMXPath($dom);
        $root = $xpath->query('//*[@id="fcc_blog_content_root"]')->item(0);
        $heading_nodes = $xpath->query('//*[@id="fcc_blog_content_root"]//*[self::h2 or self::h3]');
        $heading_index = 0;

        foreach($heading_nodes as $heading_node) {
            $heading_text = trim(html_entity_decode(strip_tags($dom->saveHTML($heading_node)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if($heading_text === '') {
                continue;
            }

            $heading_index++;
            $heading_id = 'fcc-blog-section-' . $heading_index;
            $heading_node->setAttribute('id', $heading_id);

            $fcc_blog_toc[] = [
                'id' => $heading_id,
                'title' => $heading_text,
                'level' => $heading_node->nodeName,
            ];
        }

        if($root) {
            $rendered_html = '';

            foreach($root->childNodes as $child_node) {
                $rendered_html .= $dom->saveHTML($child_node);
            }

            $fcc_rendered_blog_content = $rendered_html;
        }
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previous_state);
}

$fcc_blog_key_sections = array_slice(array_map(static function($toc_item) {
    return $toc_item['title'];
}, array_filter($fcc_blog_toc, static function($toc_item) {
    return ($toc_item['level'] ?? '') === 'h2';
})), 0, 3);

if(empty($fcc_blog_key_sections)) {
    $fcc_blog_key_sections = array_slice(array_map(static function($toc_item) {
        return $toc_item['title'];
    }, $fcc_blog_toc), 0, 3);
}

$fcc_post_alternate_urls = is_array($data->alternate_urls ?? null) ? $data->alternate_urls : [];
$fcc_blog_toc_current_url = $_SERVER['REQUEST_URI'] ?? $fcc_blog_post_url;
$fcc_blog_microcopy = \Altum\Language::$code === 'hr'
    ? [
        'summary_label' => 'Što dobivate',
        'summary_value' => 'Sažetak proizvoda i praktičan vodič',
        'trust_note' => 'Na ovoj stranici brzo možete vidjeti čemu je proizvod namijenjen, što ga izdvaja i koji vam sljedeći koraci mogu pomoći pri odabiru prije narudžbe.',
        'related_eyebrow' => 'Možda bi vas moglo zanimati',
        'related_title' => 'Još proizvoda koji bi vam mogli odgovarati',
        'card_eyebrow_product' => 'Forever proizvod',
        'card_eyebrow_guide' => 'Praktičan vodič',
        'card_available_badge' => 'Dostupno za narudžbu',
        'card_market_singular' => 'tržište',
        'card_market_plural' => 'tržišta',
        'card_cta' => 'Pogledaj detalje',
        'decision_eyebrow' => 'Brži odabir',
        'decision_title' => 'Kome je ovaj vodič najkorisniji',
        'checks_eyebrow' => 'Na brzinu provjerite',
        'checks_title' => 'Najvažnije informacije prije sljedećeg koraka',
        'action_title' => 'Želite odmah prijeći na sljedeći korak?',
        'action_subtitle' => 'Glavni gumb vodi na službeni Forever webshop za vašu zemlju, gdje možete naručiti ovaj proizvod, ili prvo usporedite slične Forever proizvode.',
        'action_primary' => 'Otvori Forever webshop i naruči',
        'action_compare' => 'Usporedi slične proizvode',
        'action_faq' => 'Skrolaj na česta pitanja',
        'action_guide' => 'Preskoči na vodič',
        'decision_point_1' => 'Ako želite prije odluke brzo razumjeti čemu je %s namijenjen.',
        'decision_point_2' => 'Ako vam je važno provjeriti osnovne informacije bez dugog traženja kroz više stranica.',
        'decision_point_3' => 'Ako uspoređujete više Forever proizvoda i želite lakše suziti izbor.',
        'decision_point_3_single' => 'Ako želite jasan pregled prije nego odlučite je li ovo pravi proizvod za vas.',
        'check_markets' => 'Dostupnost: %s',
        'check_sku' => 'SKU oznaka: %s',
        'check_category' => 'Kategorija: %s',
        'check_section' => 'Tema vodiča: %s',
        'comparison_eyebrow' => 'Usporedba',
        'comparison_title' => 'Pogledajte slične proizvode na jednom mjestu',
        'comparison_subtitle' => 'Ako još vagate između nekoliko opcija, ovaj pregled pomaže da brže odlučite koji vodič otvoriti sljedeći.',
        'comparison_current' => 'Trenutno otvoreno',
        'comparison_ready' => 'Dostupno za narudžbu',
        'comparison_markets' => '%s tržišta',
        'comparison_market_single' => '%s tržište',
        'comparison_cta' => 'Otvori proizvod',
        'faq_title' => 'Česta pitanja',
        'faq_subtitle' => 'Kratki odgovori na pitanja koja korisnici najčešće imaju prije odluke.',
    ]
    : [
        'summary_label' => 'What you get',
        'summary_value' => 'Product overview and practical guide',
        'trust_note' => 'This page helps visitors quickly understand what the product is for, what stands out about it, and which next steps may help before placing an order.',
        'related_eyebrow' => 'You may also like',
        'related_title' => 'More products that may suit you',
        'card_eyebrow_product' => 'Forever product',
        'card_eyebrow_guide' => 'Practical guide',
        'card_available_badge' => 'Ready to order',
        'card_market_singular' => 'market',
        'card_market_plural' => 'markets',
        'card_cta' => 'View details',
        'decision_eyebrow' => 'Faster decisions',
        'decision_title' => 'Who this guide helps most',
        'checks_eyebrow' => 'Quick check',
        'checks_title' => 'The key details before the next step',
        'action_title' => 'Ready for the next step?',
        'action_subtitle' => 'The main button opens the official Forever webshop for the visitor market, where this product can be ordered, or visitors can compare similar Forever products first.',
        'action_primary' => 'Open Forever webshop and order',
        'action_compare' => 'Compare similar products',
        'action_faq' => 'Jump to FAQ',
        'action_guide' => 'Jump to guide',
        'decision_point_1' => 'If you want to quickly understand what %s is meant for before deciding.',
        'decision_point_2' => 'If you want the key details in one place without searching across several pages.',
        'decision_point_3' => 'If you are comparing several Forever products and want to narrow the choice faster.',
        'decision_point_3_single' => 'If you want a clear overview before deciding whether this is the right product for you.',
        'check_markets' => 'Availability: %s',
        'check_sku' => 'SKU: %s',
        'check_category' => 'Category: %s',
        'check_section' => 'Guide topic: %s',
        'comparison_eyebrow' => 'Comparison',
        'comparison_title' => 'See similar products in one place',
        'comparison_subtitle' => 'If you are still choosing between a few options, this overview helps you decide which guide to open next.',
        'comparison_current' => 'Currently open',
        'comparison_ready' => 'Ready to order',
        'comparison_markets' => '%s markets',
        'comparison_market_single' => '%s market',
        'comparison_cta' => 'Open product',
        'faq_title' => 'Frequently asked questions',
        'faq_subtitle' => 'Short answers to the questions visitors most often have before deciding.',
    ];

if($fcc_is_start_package_context) {
    $fcc_blog_microcopy = array_merge($fcc_blog_microcopy, \Altum\Language::$code === 'hr'
        ? [
            'summary_value' => 'Registracija, Start paket i poslovni početak',
            'trust_note' => 'Ovo je glavni početni članak za poslovnu suradnju. Klik vodi prema registraciji i narudžbi Start paketa na odgovarajućem Forever tržištu korisnika.',
            'related_eyebrow' => 'Sljedeći koraci',
            'related_title' => 'Još vodiča za početak suradnje',
            'decision_title' => 'Kome je Start paket pravi prvi korak',
            'checks_title' => 'Što dobivate s ovim početkom',
            'action_title' => 'Želite pokrenuti suradnju i aktivirati benefite?',
            'action_subtitle' => 'Ovaj korak vodi direktno na registraciju i narudžbu Start paketa u zemlji korisnika, uz otvaranje poslovnog statusa i FCC benefita.',
            'action_primary' => 'Registriraj se i naruči Start paket',
            'action_compare' => 'Pogledaj sljedeće korake',
            'action_guide' => 'Pogledaj što dobivaš',
            'decision_point_1' => 'Ako želite postati Forever poslovni suradnik i krenuti kroz službeni početni paket.',
            'decision_point_2' => 'Ako vam je cilj otvoriti put prema 30% popusta i aktivirati vlastiti Forever ID.',
            'decision_point_3' => 'Ako tražite glavni članak za početak suradnje, registraciju i uključivanje u FCC sustav.',
            'decision_point_3_single' => 'Ako tražite glavni članak za početak suradnje, registraciju i uključivanje u FCC sustav.',
            'comparison_title' => 'Korisni sljedeći koraci nakon odluke za početak',
            'comparison_subtitle' => 'Nakon Start paketa, ovi vodiči pomažu razumjeti sustav, alate i prve poslovne korake.',
        ]
        : [
            'summary_value' => 'Registration, starter pack, and business launch',
            'trust_note' => 'This is the main starting article for business partnership. The main click leads directly to registration and starter pack ordering in the visitor’s matching Forever market.',
            'related_eyebrow' => 'Next steps',
            'related_title' => 'More guides for getting started',
            'decision_title' => 'Who the Start Package is best for',
            'checks_title' => 'What you get with this start',
            'action_title' => 'Ready to start the partnership and unlock benefits?',
            'action_subtitle' => 'This step leads directly to registration and starter pack ordering in the visitor’s market, while opening business status and FCC benefits.',
            'action_primary' => 'Register and order the Start Package',
            'action_compare' => 'See the next steps',
            'action_guide' => 'See what you get',
            'decision_point_1' => 'If you want to become a Forever business partner through the official starter pack path.',
            'decision_point_2' => 'If your goal is to open the path toward a 30% discount and activate your Forever ID.',
            'decision_point_3' => 'If you are looking for the main article for partnership, registration, and entry into the FCC system.',
            'decision_point_3_single' => 'If you are looking for the main article for partnership, registration, and entry into the FCC system.',
            'comparison_title' => 'Useful next steps after choosing to start',
            'comparison_subtitle' => 'After the Start Package, these guides help explain the system, tools, and the first business steps.',
        ]
    );
}

$fcc_shop_context_microcopy_overrides = array_filter([
    'trust_note' => $fcc_blog_shop_context['trust_note'] ?? '',
    'decision_title' => $fcc_blog_shop_context['decision_title'] ?? '',
    'checks_title' => $fcc_blog_shop_context['checks_title'] ?? '',
    'action_title' => $fcc_blog_shop_context['action_title'] ?? '',
    'action_subtitle' => $fcc_blog_shop_context['action_subtitle'] ?? '',
    'action_primary' => $fcc_blog_shop_context['primary_cta_label'] ?? '',
    'related_eyebrow' => $fcc_blog_shop_context['related_eyebrow'] ?? '',
    'related_title' => $fcc_blog_shop_context['related_title'] ?? '',
], static function($value) {
    return trim((string) $value) !== '';
});

if($fcc_shop_context_microcopy_overrides) {
    $fcc_blog_microcopy = array_merge($fcc_blog_microcopy, $fcc_shop_context_microcopy_overrides);
}

$fcc_normalize_related_text = static function($text, int $limit = 160): string {
    $text = trim((string) $text);

    if($text === '') {
        return '';
    }

    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim((string) $text);

    if($text === '') {
        return '';
    }

    if(function_exists('mb_strlen') && function_exists('mb_substr')) {
        if(mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(1, $limit - 1))) . '…';
    }

    if(strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(substr($text, 0, max(1, $limit - 3))) . '...';
};
$fcc_blog_faq_items = [];
$fcc_shop_context_faq_items = !empty($fcc_blog_shop_context['faq']) ? array_values($fcc_blog_shop_context['faq']) : [];
$fcc_has_faq_heading = false;

if(!empty($data->blog_post->content) && class_exists('DOMDocument')) {
    $dom = new \DOMDocument();
    $previous_state = libxml_use_internal_errors(true);

    if($dom->loadHTML('<?xml encoding="utf-8" ?>' . $data->blog_post->content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
        $in_faq_section = false;

        foreach($dom->childNodes as $node) {
            if(!in_array($node->nodeName, ['h2', 'h3', 'p'], true)) {
                continue;
            }

            $node_text = trim(html_entity_decode(strip_tags($dom->saveHTML($node)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if($node->nodeName === 'h2') {
                $normalized_heading = function_exists('mb_strtolower') ? mb_strtolower($node_text, 'UTF-8') : strtolower($node_text);
                $in_faq_section = in_array($normalized_heading, ['česta pitanja', 'frequently asked questions'], true);
                $fcc_has_faq_heading = $fcc_has_faq_heading || $in_faq_section;
                continue;
            }

            if(!$in_faq_section) {
                continue;
            }

            if($node->nodeName === 'h3') {
                $fcc_blog_faq_items[] = [
                    'question' => $node_text,
                    'answer' => '',
                ];
                continue;
            }

            if($node->nodeName === 'p' && !empty($fcc_blog_faq_items)) {
                $last_index = array_key_last($fcc_blog_faq_items);

                if($last_index !== null && $fcc_blog_faq_items[$last_index]['answer'] === '') {
                    $fcc_blog_faq_items[$last_index]['answer'] = $node_text;
                }
            }
        }
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previous_state);
}

$fcc_blog_faq_items = array_values(array_filter($fcc_blog_faq_items, static function($item) {
    return !empty($item['question']) && !empty($item['answer']);
}));

$fcc_normalize_heading_text = static function($value): string {
    $value = trim((string) $value);
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
};

$fcc_effective_blog_faq_items = !empty($fcc_shop_context_faq_items) ? $fcc_shop_context_faq_items : $fcc_blog_faq_items;

if($fcc_has_faq_heading || !empty($fcc_effective_blog_faq_items)) {
    $filtered_toc = [];
    $remove_faq_toc_children = false;

    foreach($fcc_blog_toc as $toc_item) {
        $normalized_title = $fcc_normalize_heading_text($toc_item['title'] ?? '');
        $is_h2 = ($toc_item['level'] ?? '') === 'h2';

        if(in_array($normalized_title, ['česta pitanja', 'frequently asked questions'], true)) {
            $remove_faq_toc_children = true;
            continue;
        }

        if($remove_faq_toc_children && $is_h2) {
            $remove_faq_toc_children = false;
        }

        if($remove_faq_toc_children) {
            continue;
        }

        $filtered_toc[] = $toc_item;
    }

    $fcc_blog_toc = array_values($filtered_toc);

    $fcc_blog_key_sections = array_slice(array_map(static function($toc_item) {
        return $toc_item['title'];
    }, array_filter($fcc_blog_toc, static function($toc_item) {
        return ($toc_item['level'] ?? '') === 'h2';
    })), 0, 3);

    if(empty($fcc_blog_key_sections)) {
        $fcc_blog_key_sections = array_slice(array_map(static function($toc_item) {
            return $toc_item['title'];
        }, $fcc_blog_toc), 0, 3);
    }

    if($fcc_has_faq_heading && $fcc_rendered_blog_content !== '' && class_exists('DOMDocument')) {
        $dom = new \DOMDocument();
        $previous_state = libxml_use_internal_errors(true);

        if($dom->loadHTML('<?xml encoding="utf-8" ?><div id="fcc_blog_render_root">' . $fcc_rendered_blog_content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            $xpath = new \DOMXPath($dom);
            $root = $xpath->query('//*[@id="fcc_blog_render_root"]')->item(0);

            if($root) {
                $remove_mode = false;
                $nodes_to_remove = [];

                foreach(iterator_to_array($root->childNodes) as $child_node) {
                    if($child_node->nodeName === 'h2') {
                        $heading_text = trim(html_entity_decode(strip_tags($dom->saveHTML($child_node)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        $normalized_heading = $fcc_normalize_heading_text($heading_text);

                        if($normalized_heading === 'česta pitanja' || $normalized_heading === 'frequently asked questions') {
                            $remove_mode = true;
                            $nodes_to_remove[] = $child_node;
                            continue;
                        }

                        if($remove_mode) {
                            break;
                        }
                    }

                    if($remove_mode) {
                        $nodes_to_remove[] = $child_node;
                    }
                }

                foreach($nodes_to_remove as $node_to_remove) {
                    if($node_to_remove->parentNode === $root) {
                        $root->removeChild($node_to_remove);
                    }
                }

                $rendered_html = '';

                foreach($root->childNodes as $child_node) {
                    $rendered_html .= $dom->saveHTML($child_node);
                }

                $fcc_rendered_blog_content = $rendered_html;
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous_state);
    }
}

$fcc_blog_faq_items = $fcc_effective_blog_faq_items;

$fcc_get_blog_product_card_state = static function($blog_post) use ($fcc_blog_microcopy, $fcc_normalize_related_text, $fcc_uses_global_market_display, $fcc_global_market_display_chip): array {
    $webshop_links = json_decode($blog_post->webshop_links ?? '{}', true) ?: [];
    $market_count = count(array_filter($webshop_links, static function($url) {
        return !empty($url);
    }));
    $shop_ready = $market_count > 0;
    $chips = [];

    if($shop_ready) {
        $chips[] = $fcc_blog_microcopy['card_available_badge'];
    }

    if($market_count > 0) {
        $chips[] = $fcc_uses_global_market_display
            ? $fcc_global_market_display_chip
            : ($market_count . ' ' . ($market_count === 1 ? $fcc_blog_microcopy['card_market_singular'] : $fcc_blog_microcopy['card_market_plural']));
    }

    return [
        'shop_ready' => $shop_ready,
        'market_count' => $market_count,
        'eyebrow' => $shop_ready || !empty($blog_post->sku) ? $fcc_blog_microcopy['card_eyebrow_product'] : $fcc_blog_microcopy['card_eyebrow_guide'],
        'chips' => $chips,
        'description' => $fcc_normalize_related_text($blog_post->description ?? ''),
        'cta' => $fcc_blog_microcopy['card_cta'],
    ];
};
$fcc_get_related_card_state = $fcc_get_blog_product_card_state;
$fcc_current_product_state = $fcc_get_blog_product_card_state($data->blog_post);
$fcc_product_decision_points = [
    sprintf($fcc_blog_microcopy['decision_point_1'], $data->blog_post->title),
    $fcc_blog_microcopy['decision_point_2'],
    !empty($fcc_related_blog_posts) ? $fcc_blog_microcopy['decision_point_3'] : $fcc_blog_microcopy['decision_point_3_single'],
];
$fcc_product_quick_checks = [];
$fcc_product_summary_cards = [];

if($fcc_is_start_package_context) {
    $fcc_product_summary_cards = \Altum\Language::$code === 'hr'
        ? [
            ['label' => 'Status', 'value' => 'Forever poslovni suradnik'],
            ['label' => 'Popust', 'value' => 'Put prema 30% popusta'],
            ['label' => 'Dobivate', 'value' => 'Forever ID i FCC benefite'],
            ['label' => 'Korak', 'value' => 'Registracija i narudžba u vašoj zemlji'],
        ]
        : [
            ['label' => 'Status', 'value' => 'Forever business partner'],
            ['label' => 'Discount', 'value' => 'Path toward a 30% discount'],
            ['label' => 'You get', 'value' => 'Forever ID and FCC benefits'],
            ['label' => 'Step', 'value' => 'Registration and ordering in the visitor market'],
        ];
} else {
    $fcc_product_summary_cards[] = [
        'label' => $fcc_blog_microcopy['summary_label'],
        'value' => $fcc_blog_microcopy['summary_value'],
    ];

    if(!empty($data->blog_post->sku)) {
        $fcc_product_summary_cards[] = [
            'label' => 'SKU',
            'value' => $data->blog_post->sku,
        ];
    }

    if($fcc_market_count > 0) {
        $fcc_product_summary_cards[] = [
            'label' => \Altum\Language::$code === 'hr' ? 'Dostupna tržišta' : 'Available markets',
            'value' => $fcc_global_market_display_value ?: ($fcc_market_count . '+'),
        ];
    }

    if(!empty($data->blog_posts_category->title)) {
        $fcc_product_summary_cards[] = [
            'label' => \Altum\Language::$code === 'hr' ? 'Kategorija' : 'Category',
            'value' => $data->blog_posts_category->title,
        ];
    }
}

if($fcc_is_start_package_context) {
    $fcc_product_quick_checks = \Altum\Language::$code === 'hr'
        ? [
            'Nova osoba se kroz ovaj paket upisuje u Forever i postaje poslovni suradnik.',
            'Otvara se put prema 30% popusta i svim ključnim poslovnim benefitima.',
            'Registracija i narudžba vode direktno na odgovarajuće Forever tržište korisnika.',
            'Paket je povezan s FCC sustavom i služi kao glavni početni korak za suradnju.',
        ]
        : [
            'A new person joins Forever through this package and becomes a business partner.',
            'It opens the path toward a 30% discount and the core business benefits.',
            'Registration and ordering lead directly to the matching Forever market for the visitor.',
            'The package is tied to the FCC system and serves as the main starting step for partnership.',
        ];
} else {
    if($fcc_market_count > 0) {
        $fcc_product_quick_checks[] = sprintf(
            $fcc_blog_microcopy['check_markets'],
            $fcc_global_market_display_detail ?: (
                $fcc_market_count === 1
                    ? sprintf($fcc_blog_microcopy['comparison_market_single'], $fcc_market_count)
                    : sprintf($fcc_blog_microcopy['comparison_markets'], $fcc_market_count)
            )
        );
    }

    if(!empty($data->blog_post->sku)) {
        $fcc_product_quick_checks[] = sprintf($fcc_blog_microcopy['check_sku'], $data->blog_post->sku);
    }

    if(!empty($data->blog_posts_category->title)) {
        $fcc_product_quick_checks[] = sprintf($fcc_blog_microcopy['check_category'], $data->blog_posts_category->title);
    }

    foreach(array_slice($fcc_blog_key_sections, 0, 3) as $key_section) {
        $fcc_product_quick_checks[] = sprintf($fcc_blog_microcopy['check_section'], $key_section);
    }
}

if(!empty($fcc_blog_shop_context['summary_cards'])) {
    $fcc_product_summary_cards = array_values($fcc_blog_shop_context['summary_cards']);
}

if(!empty($fcc_blog_shop_context['ideal_for'])) {
    $fcc_product_decision_points = array_values($fcc_blog_shop_context['ideal_for']);
}

if(!empty($fcc_blog_shop_context['quick_checks'])) {
    $fcc_product_quick_checks = array_values($fcc_blog_shop_context['quick_checks']);
}

if($fcc_uses_global_market_display && $fcc_market_count > 0) {
    $market_summary_labels = \Altum\Language::$code === 'hr'
        ? ['Dostupna tržišta']
        : ['Available markets'];
    $market_summary_found = false;

    foreach($fcc_product_summary_cards as &$summary_card) {
        if(in_array((string) ($summary_card['label'] ?? ''), $market_summary_labels, true)) {
            $summary_card['value'] = $fcc_global_market_display_value;
            $market_summary_found = true;
        }
    }
    unset($summary_card);

    if(!$market_summary_found) {
        $fcc_product_summary_cards[] = [
            'label' => $market_summary_labels[0],
            'value' => $fcc_global_market_display_value,
        ];
    }

    $market_quick_check_prefix = \Altum\Language::$code === 'hr' ? 'Dostupnost:' : 'Availability:';
    $market_quick_check_found = false;

    foreach($fcc_product_quick_checks as $quick_check) {
        if(stripos((string) $quick_check, $market_quick_check_prefix) === 0) {
            $market_quick_check_found = true;
            break;
        }
    }

    if(!$market_quick_check_found) {
        array_unshift($fcc_product_quick_checks, sprintf($fcc_blog_microcopy['check_markets'], $fcc_global_market_display_detail));
    }
}

$fcc_product_compare_rows = [[
    'title' => $data->blog_post->title,
    'url' => $fcc_blog_post_url,
    'is_current' => true,
    'state' => $fcc_current_product_state,
]];

foreach(array_slice($fcc_related_blog_posts, 0, 3) as $related_blog_post) {
    $fcc_product_compare_rows[] = [
        'title' => $related_blog_post->title,
        'url' => SITE_URL . ($related_blog_post->language ? \Altum\Language::$active_languages[$related_blog_post->language] . '/' : null) . 'blog/' . $related_blog_post->url,
        'is_current' => false,
        'state' => $fcc_get_blog_product_card_state($related_blog_post),
    ];
}

$fcc_action_secondary_target = null;
$fcc_action_secondary_label = null;
$fcc_action_tertiary_target = null;
$fcc_action_tertiary_label = null;

if($fcc_is_start_package_context) {
    if(!empty($fcc_related_blog_posts)) {
        $fcc_action_secondary_target = 'fcc-start-next-steps';
        $fcc_action_secondary_label = $fcc_blog_microcopy['action_compare'];
    } elseif(!empty($fcc_blog_faq_items)) {
        $fcc_action_secondary_target = 'fcc-product-faq';
        $fcc_action_secondary_label = $fcc_blog_microcopy['action_faq'];
    } else {
        $fcc_action_secondary_target = !empty($fcc_blog_toc[0]['id']) ? $fcc_blog_toc[0]['id'] : 'fcc-product-guide';
        $fcc_action_secondary_label = $fcc_blog_microcopy['action_guide'];
    }

    if(!empty($fcc_related_blog_posts) && !empty($fcc_blog_faq_items)) {
        $fcc_action_tertiary_target = 'fcc-product-faq';
        $fcc_action_tertiary_label = $fcc_blog_microcopy['action_faq'];
    }
} elseif(count($fcc_product_compare_rows) > 1) {
    $fcc_action_secondary_target = 'fcc-product-comparison';
    $fcc_action_secondary_label = $fcc_blog_microcopy['action_compare'];

    if(!empty($fcc_blog_faq_items)) {
        $fcc_action_tertiary_target = 'fcc-product-faq';
        $fcc_action_tertiary_label = $fcc_blog_microcopy['action_faq'];
    }
} elseif(!empty($fcc_blog_faq_items)) {
    $fcc_action_secondary_target = 'fcc-product-faq';
    $fcc_action_secondary_label = $fcc_blog_microcopy['action_faq'];
} else {
    $fcc_action_secondary_target = !empty($fcc_blog_toc[0]['id']) ? $fcc_blog_toc[0]['id'] : 'fcc-product-guide';
    $fcc_action_secondary_label = $fcc_blog_microcopy['action_guide'];
}

$fcc_primary_cta_label = !empty($fcc_blog_shop_context['primary_cta_label'])
    ? $fcc_blog_shop_context['primary_cta_label']
    : ($fcc_blog_product_cta_url
    ? ($fcc_is_start_package_context
        ? $fcc_blog_microcopy['action_primary']
        : (($data->blog_post->blog_post_id != 406 && $data->blog_post->blog_post_id != 407) ? sprintf(l('blog.buy_product')) : sprintf(l('blog.start_business'))))
    : sprintf(l('blog.more_info.heading')));

if(!empty($fcc_blog_shop_context['secondary_cta_label']) && $fcc_action_secondary_target) {
    $fcc_action_secondary_label = $fcc_blog_shop_context['secondary_cta_label'];
}
/* /Custom code: FC-2026-02-26 */
?>

<?php if($fcc_post_alternate_urls): ?>
    <?php ob_start() ?>
    <?php foreach($fcc_post_alternate_urls as $hreflang => $href): ?>
        <link rel="alternate" hreflang="<?= e($hreflang) ?>" href="<?= e($href) ?>" />
    <?php endforeach ?>
    <?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
<?php endif ?>

<?php ob_start() ?>
<style>
    .fcc-referral-tour-target {
        scroll-margin-top: 6rem;
    }

    .fcc-referral-tour-target.is-active {
        position: relative !important;
        z-index: 2052 !important;
        box-shadow: 0 0 0 2px rgba(73, 227, 207, .95), 0 0 0 14px rgba(73, 227, 207, .16), 0 24px 72px rgba(2, 8, 23, .42) !important;
        border-radius: 1.35rem !important;
    }

    .fcc-referral-tour-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2050;
        display: none;
        pointer-events: none;
    }

    .fcc-referral-tour-backdrop.is-visible {
        display: block;
    }

    .fcc-referral-tour-backdrop-segment {
        position: fixed;
        background: rgba(2, 8, 23, .58);
        backdrop-filter: blur(3px);
        pointer-events: none;
    }

    .fcc-referral-tour-popover {
        position: fixed;
        z-index: 2055;
        width: min(24rem, calc(100vw - 2rem));
        display: none;
        border-radius: 1.2rem;
        border: 1px solid rgba(147, 197, 253, .22);
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .18), transparent 30%),
            linear-gradient(180deg, rgba(25, 36, 58, .98), rgba(16, 24, 41, .97));
        box-shadow: 0 30px 80px rgba(2, 8, 23, .44), inset 0 1px 0 rgba(255,255,255,.05);
        padding: 1.05rem 1.05rem 1rem;
    }

    .fcc-referral-tour-popover.is-visible {
        display: block;
    }

    .fcc-referral-tour-progress {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .35rem .65rem;
        border-radius: 999px;
        background: rgba(73, 227, 207, .18);
        color: #e8fffb;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-bottom: .75rem;
        border: 1px solid rgba(73, 227, 207, .16);
    }

    .fcc-referral-tour-title {
        color: #f8fbff;
        font-size: 1.12rem;
        font-weight: 800;
        line-height: 1.3;
        margin-bottom: .45rem;
    }

    .fcc-referral-tour-text {
        color: rgba(236, 244, 255, .94);
        font-size: .94rem;
        line-height: 1.65;
        margin-bottom: 1rem;
    }

    .fcc-referral-tour-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .fcc-referral-tour-actions-main {
        display: flex;
        gap: .65rem;
        flex-wrap: wrap;
    }

    .fcc-referral-tour-actions .btn {
        border-radius: .85rem;
    }

    .fcc-referral-tour-actions .btn-link {
        color: rgba(226, 232, 240, .82) !important;
        text-decoration: none;
    }

    .fcc-referral-tour-actions .btn-outline-light {
        color: #ecf8ff !important;
        border-color: rgba(147, 197, 253, .28) !important;
        background: rgba(59, 130, 246, .12) !important;
    }

    .fcc-blog-post-content .ql-content h2,
    .fcc-blog-post-content .ql-content h3,
    .fcc-blog-post-content .ql-content h4,
    .fcc-blog-post-content .ql-content h5,
    .fcc-blog-post-content .ql-content h6 {
        color: #6ef2d0;
        font-family: "Space Grotesk", sans-serif;
        line-height: 1.25;
        margin-top: 1.9rem;
        margin-bottom: 0.85rem;
    }

    .fcc-blog-post-content .ql-content h2 {
        font-size: clamp(1.4rem, 2vw, 1.8rem);
    }

    .fcc-blog-post-content .ql-content h3 {
        font-size: clamp(1.1rem, 1.5vw, 1.35rem);
    }

    .fcc-blog-post-content .ql-content a {
        color: #7cf7c7;
        text-decoration: underline;
        text-decoration-color: rgba(124, 247, 199, 0.45);
        text-underline-offset: 0.18em;
        transition: color 0.2s ease, text-decoration-color 0.2s ease;
    }

    .fcc-blog-post-content .ql-content a:hover {
        color: #a7ffe0;
        text-decoration-color: rgba(167, 255, 224, 0.9);
    }

    .fcc-blog-post-content .ql-content ul,
    .fcc-blog-post-content .ql-content ol {
        padding-left: 1.3rem;
    }

    .fcc-blog-post-content [id^="fcc-blog-section-"] {
        scroll-margin-top: 7.25rem;
    }

    .fcc-blog-post-content .ql-content li + li {
        margin-top: 0.45rem;
    }

    @media (max-width: 767.98px) {
        .fcc-referral-tour-popover {
            left: 1rem !important;
            right: 1rem !important;
            width: auto;
            top: auto !important;
            bottom: 1rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<!-- Custom code: FC-2026-02-26: FCC premium blog post layout -->
<div class="fcc-blog-page-bg">
<div class="container <?= settings()->content->blog_columns == 1 ? 'col-lg-8' : null ?> fcc-blog-wrap">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('blog') ?>"><?= l('blog.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <?php if($data->blog_posts_category): ?>
                    <li><a href="<?= url('blog/category/' . $data->blog_posts_category->url) ?>"><?= $data->blog_posts_category->title ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <?php endif ?>
                <li class="active" aria-current="page"><?= $data->blog_post->title ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <?php if(settings()->content->blog_search_widget_is_enabled): ?>
        <!-- Custom code: FC-2026-03-09: mobile search directly below menu/top section -->
        <div class="card mb-4 fcc-glass-card fcc-widget-card d-lg-none" style="position: relative; z-index: 30; overflow: visible;">
            <div class="card-body">
                <form action="<?= url('blog') ?>" method="get" role="form">
                    <div class="position-relative" data-fcc-blog-search style="z-index: 40;">
                        <div class="input-group">
                            <input type="search" name="search" class="form-control" value="<?= !empty($_GET['search']) ? input_clean($_GET['search']) : null ?>" placeholder="<?= l('global.search') ?>" aria-label="<?= l('global.search') ?>" autocomplete="off" data-fcc-blog-search-input />

                            <div class="input-group-append">
                                <button class="btn btn-outline-gray-300 text-dark" type="submit" data-toggle="tooltip" title="<?= l('global.submit') ?>"><i class="fas fa-fw fa-search"></i></button>
                            </div>
                        </div>
                        <div class="dropdown-menu w-100" data-fcc-blog-search-results style="z-index: 2050;"></div>
                    </div>
                </form>
            </div>
        </div>
        <!-- /Custom code: FC-2026-03-09 -->
    <?php endif ?>

    <div class="row fcc-blog-grid">
        <?php /* Custom code: FC-2026-03-09: mobile-first article ordering */ ?>
        <div class="<?= settings()->content->blog_columns == 1 ? 'col-12 mb-5' : 'col-12 col-lg-8 mb-lg-0' ?> fcc-blog-main-col order-1 order-lg-1">
        <?php /* /Custom code: FC-2026-03-09 */ ?>
            <div class="card fcc-glass-card fcc-article-card">
                <div class="card-body fcc-article-body">
                    <div class="fcc-article-hero">
                        <?php if($data->blog_post->image): ?>
                            <div class="fcc-hero-image-frame">
                                <img src="<?= \Altum\Uploads::get_full_url('blog') . $data->blog_post->image ?>" class="blog-post-image img-fluid w-100 rounded mb-3 fcc-hero-image" alt="<?= $data->blog_post->image_description ?>" loading="eager" decoding="async" />
                            </div>
                        <?php endif ?>

                        <h1 class="h3 mb-2 fcc-article-title"><?= $data->blog_post->title ?></h1>

                        <div class="fcc-article-meta">
                            <span class="fcc-meta-badge" data-toggle="tooltip" title="<?= sprintf(l('global.last_datetime_tooltip'), \Altum\Date::get($data->blog_post->last_datetime, 2)) ?>">
                                <?= sprintf(l('global.datetime_tooltip'), \Altum\Date::get($data->blog_post->datetime, 2)) ?>
                            </span>

                            <?php if($data->blog_posts_category): ?>
                                <span class="fcc-meta-badge"><?= $data->blog_posts_category->title ?></span>
                            <?php endif ?>

                            <?php if(settings()->content->blog_views_is_enabled): ?>
                                <span class="fcc-meta-badge"><?= sprintf(l('blog.total_views'), nr($data->blog_post->total_views)) ?></span>
                            <?php endif ?>

                            <?php $estimated_reading_time = string_estimate_reading_time($data->blog_post->content) ?>
                            <?php if($estimated_reading_time->minutes > 0 || $estimated_reading_time->seconds > 0): ?>
                                <span class="fcc-meta-badge">
                                    <?= $estimated_reading_time->minutes ? sprintf(l('blog.estimated_reading_time'), $estimated_reading_time->minutes . ' ' . l('global.date.minutes')) : null ?>
                                    <?= $estimated_reading_time->minutes == 0 && $estimated_reading_time->seconds ? sprintf(l('blog.estimated_reading_time'), $estimated_reading_time->seconds . ' ' . l('global.date.seconds')) : null ?>
                                </span>
                            <?php endif ?>
                        </div>
                    </div>

                    <?php if($fcc_is_product_context): ?>
                        <div class="fcc-product-summary mb-4">
                            <div class="fcc-product-summary-grid">
                                <?php foreach($fcc_product_summary_cards as $summary_card): ?>
                                    <div class="fcc-product-summary-card">
                                        <span class="fcc-product-summary-label"><?= e($summary_card['label']) ?></span>
                                        <strong class="fcc-product-summary-value"><?= e($summary_card['value']) ?></strong>
                                    </div>
                                <?php endforeach ?>
                            </div>

                            <div class="fcc-product-trust-note">
                                <?= $fcc_blog_microcopy['trust_note'] ?>
                            </div>
                        </div>

                        <section class="fcc-product-decision-panel mb-4" id="fcc-product-next-step">
                            <div class="row">
                                <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                                    <article class="fcc-product-decision-card h-100">
                                        <span class="fcc-related-eyebrow"><?= $fcc_blog_microcopy['decision_eyebrow'] ?></span>
                                        <h2 class="fcc-related-title"><?= $fcc_blog_microcopy['decision_title'] ?></h2>

                                        <ul class="fcc-product-decision-list">
                                            <?php foreach($fcc_product_decision_points as $decision_point): ?>
                                                <li><?= e($decision_point) ?></li>
                                            <?php endforeach ?>
                                        </ul>
                                    </article>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <article class="fcc-product-decision-card h-100">
                                        <span class="fcc-related-eyebrow"><?= $fcc_blog_microcopy['checks_eyebrow'] ?></span>
                                        <h2 class="fcc-related-title"><?= $fcc_blog_microcopy['checks_title'] ?></h2>

                                        <div class="fcc-product-checks-list">
                                            <?php foreach($fcc_product_quick_checks as $quick_check): ?>
                                                <div class="fcc-product-check-chip"><?= e($quick_check) ?></div>
                                            <?php endforeach ?>
                                        </div>
                                    </article>
                                </div>
                            </div>

                            <?php if($fcc_blog_primary_cta_url || $fcc_action_secondary_target || $fcc_action_tertiary_target): ?>
                                <div class="fcc-product-action-band">
                                    <div class="fcc-product-action-copy">
                                        <h2 class="fcc-related-title mb-2"><?= $fcc_blog_microcopy['action_title'] ?></h2>
                                        <p class="fcc-category-shop-note mb-0"><?= $fcc_blog_microcopy['action_subtitle'] ?></p>
                                    </div>

                                    <div class="fcc-product-action-buttons">
                                        <?php if($fcc_blog_primary_cta_url): ?>
                                            <a
                                                target="_blank"
                                                href="<?= $fcc_blog_primary_cta_url ?>"
                                                class="fcc-product-action-btn is-primary"
                                                data-fcc-blog-event="product_primary_cta_click"
                                                data-fcc-blog-component="action_band_primary_cta"
                                                data-fcc-blog-label="<?= e($fcc_primary_cta_label) ?>"
                                            >
                                                <i class="fas fa-shopping-cart mr-2"></i>
                                                <?= e($fcc_primary_cta_label) ?>
                                            </a>
                                        <?php endif ?>

                                        <?php if($fcc_action_secondary_target && $fcc_action_secondary_label): ?>
                                            <a
                                                href="<?= e($fcc_blog_toc_current_url . '#' . $fcc_action_secondary_target) ?>"
                                                class="fcc-product-action-btn"
                                                data-fcc-scroll-target="<?= e($fcc_action_secondary_target) ?>"
                                                data-fcc-blog-event="product_secondary_action_click"
                                                data-fcc-blog-component="action_band_secondary"
                                                data-fcc-blog-label="<?= e($fcc_action_secondary_label) ?>"
                                            >
                                                <?= $fcc_action_secondary_label ?>
                                            </a>
                                        <?php endif ?>

                                        <?php if($fcc_action_tertiary_target && $fcc_action_tertiary_label): ?>
                                            <a
                                                href="<?= e($fcc_blog_toc_current_url . '#' . $fcc_action_tertiary_target) ?>"
                                                class="fcc-product-action-btn"
                                                data-fcc-scroll-target="<?= e($fcc_action_tertiary_target) ?>"
                                                data-fcc-blog-event="product_secondary_action_click"
                                                data-fcc-blog-component="action_band_tertiary"
                                                data-fcc-blog-label="<?= e($fcc_action_tertiary_label) ?>"
                                            >
                                                <?= $fcc_action_tertiary_label ?>
                                            </a>
                                        <?php endif ?>
                                    </div>
                                </div>
                            <?php endif ?>
                        </section>
                    <?php endif ?>

                    <?php if(!empty($fcc_blog_toc)): ?>
                        <nav class="fcc-blog-toc mb-4" id="fcc-product-guide-nav" aria-label="<?= \Altum\Language::$code === 'hr' ? 'Sadržaj članka' : 'Article table of contents' ?>">
                            <div class="fcc-blog-toc-header">
                                <span class="fcc-blog-toc-eyebrow"><?= \Altum\Language::$code === 'hr' ? 'Brza navigacija' : 'Quick navigation' ?></span>
                                <h2 class="fcc-blog-toc-title"><?= \Altum\Language::$code === 'hr' ? 'Što ćete pronaći u ovom vodiču' : 'What you will find in this guide' ?></h2>
                            </div>

                            <div class="fcc-blog-toc-grid">
                                <?php foreach($fcc_blog_toc as $toc_item): ?>
                                    <a
                                        href="<?= e($fcc_blog_toc_current_url . '#' . $toc_item['id']) ?>"
                                        class="fcc-blog-toc-link <?= ($toc_item['level'] ?? '') === 'h3' ? 'is-child' : null ?>"
                                        data-fcc-scroll-target="<?= e($toc_item['id']) ?>"
                                        data-fcc-blog-event="product_jump_click"
                                        data-fcc-blog-component="quick_navigation"
                                        data-fcc-blog-label="<?= e($toc_item['title']) ?>"
                                    >
                                        <?= e($toc_item['title']) ?>
                                    </a>
                                <?php endforeach ?>
                            </div>
                        </nav>
                    <?php endif ?>

                    <div class="blog-post-content fcc-blog-post-content" id="fcc-product-guide">
                        <p><?= $data->blog_post->description ?></p>

                        <?php if(!empty($fcc_blog_key_sections)): ?>
                            <div class="fcc-blog-highlights">
                                <div class="fcc-blog-highlights-title"><?= \Altum\Language::$code === 'hr' ? 'Glavne teme ovog vodiča' : 'Main topics in this guide' ?></div>
                                <div class="fcc-blog-highlights-list">
                                    <?php foreach($fcc_blog_key_sections as $key_section): ?>
                                        <span class="fcc-blog-highlight-chip"><?= e($key_section) ?></span>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        <?php endif ?>

                        <div class="ql-content">
                            <?= $fcc_rendered_blog_content ?>
                        </div>

                        <?php if (!empty($data->referral)): ?>                            
                            <h2 class="mt-5 fcc-h2-accent"><?= sprintf(l('blog.more_info.heading')); ?></h2>
                            <?php $referral_full_url = url($data->referral); ?>
                            <p><?= '<a target="_blank" href="' . $referral_full_url . '">' . $referral_full_url . '</a>' ?></p>
                        <?php endif; ?>                
                    </div>

                    <?php if(!empty($fcc_blog_faq_items)): ?>
                        <section class="fcc-product-faq-card mt-4" id="fcc-product-faq">
                            <div class="fcc-product-faq-card-body">
                                <span class="fcc-related-eyebrow"><?= $fcc_blog_microcopy['faq_title'] ?></span>
                                <h2 class="fcc-related-title"><?= $fcc_blog_microcopy['faq_title'] ?></h2>
                                <p class="fcc-category-shop-note mb-0"><?= $fcc_blog_microcopy['faq_subtitle'] ?></p>

                                <div class="accordion mt-3" id="fcc-product-faq-accordion">
                                    <?php foreach($fcc_blog_faq_items as $faq_index => $faq_item): ?>
                                        <div class="fcc-category-faq-item">
                                            <button
                                                class="fcc-category-faq-question"
                                                type="button"
                                                data-toggle="collapse"
                                                data-target="#fcc-product-faq-answer-<?= $faq_index ?>"
                                                aria-expanded="<?= $faq_index === 0 ? 'true' : 'false' ?>"
                                                aria-controls="fcc-product-faq-answer-<?= $faq_index ?>"
                                                data-fcc-faq-track="1"
                                                data-fcc-blog-label="<?= e($faq_item['question']) ?>"
                                            >
                                                <?= e($faq_item['question']) ?>
                                            </button>

                                            <div id="fcc-product-faq-answer-<?= $faq_index ?>" class="collapse <?= $faq_index === 0 ? 'show' : null ?>" data-parent="#fcc-product-faq-accordion">
                                                <div class="fcc-category-faq-answer">
                                                    <?= e($faq_item['answer']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        </section>
                    <?php endif ?>

                    <?= include_view(THEME_PATH . 'views/blog/ratings.php', [
                        'blog_post' => $data->blog_post,
                    ]); ?>
                </div>
            </div>

            <?php if(settings()->content->blog_share_is_enabled): ?>
                <div class="card mt-4 fcc-glass-card fcc-share-card">
                    <div class="card-body">
                        <?php if ($fcc_blog_primary_cta_url): ?>
                            <a
                                target="_blank"
                                href="<?= $fcc_blog_primary_cta_url ?>"
                                class="mb-4 btn btn-block btn-primary link-btn link-hover-animation link-btn-rounded animate__animated animate__ animate__false animate__delay-2s fcc-cta-btn fcc-cta-btn-primary"
                                data-fcc-blog-event="product_primary_cta_click"
                                data-fcc-blog-component="share_card_primary_cta"
                                data-fcc-blog-label="<?= e($fcc_primary_cta_label) ?>"
                            >
                                <span data-icon="">
                                     <i class="fas fa-shopping-cart mr-1"></i>
                                </span>
                                <span data-name=""><?= $fcc_primary_cta_label ?></span>
                            </a>
                        <?php endif; ?>

                        <?php /* Custom code: FC-2026-03-09: share helper text by authentication state */ ?>
                        <?php $is_logged_user = is_logged_in() && !empty($share_referral_key); ?>
                        <div class="mb-3 p-3 rounded position-relative fcc-share-helper fcc-referral-tour-target" id="blog-share-referral-wrapper">
                            <div class="d-flex align-items-center justify-content-between flex-wrap fcc-share-helper-row">
                                <span class="small mb-2 mb-md-0 fcc-share-helper-text">
                                    <?= $is_logged_user ? l('blog.share_referral.helper_text') : l('blog.share_referral.helper_text_guest') ?>
                                </span>

                                <?php if($is_logged_user): ?>
                                    <button
                                        type="button"
                                        id="blog-share-referral-toggle"
                                        class="btn btn-sm btn-gray-100 fcc-share-helper-action"
                                        aria-expanded="false"
                                        aria-controls="blog-share-referral-popup"
                                    >
                                        <i class="fas fa-fw fa-info-circle mr-1"></i>
                                        <?= l('blog.share_referral.learn_more') ?>
                                    </button>
                                <?php endif ?>
                            </div>

                            <?php if($is_logged_user): ?>
                                <div id="blog-share-referral-popup" class="d-none mt-3 p-3 rounded fcc-share-helper-popup">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="pr-2">
                                            <div class="small font-weight-bold mb-2 fcc-share-helper-popup-title">
                                                <?= l('blog.share_referral.modal_title') ?>
                                            </div>
                                            <div class="small fcc-share-helper-popup-text">
                                                <?= l('blog.share_referral.modal_text') ?>
                                            </div>
                                        </div>

                                        <button type="button" id="blog-share-referral-close" class="btn btn-sm btn-gray-100 fcc-share-helper-action" aria-label="<?= l('global.close') ?>">
                                            <i class="fas fa-fw fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif ?>
                        </div>
                        <?php /* /Custom code: FC-2026-03-09 */ ?>

                        <div class="d-flex align-items-center flex-wrap gap-3 fcc-referral-tour-target" id="blog-share-referral-buttons-inline">
                            <?= include_view(THEME_PATH . 'views/partials/share_buttons.php', ['url' => $share_url, 'class' => 'btn btn-gray-100', 'copy_to_clipboard' => true, 'tracking_context' => 'blog_share']) ?>
                        </div>
                    </div>
                </div>
            <?php endif ?>
            
           <?php if (\Altum\Authentication::is_pro() && $data->blog_posts_category && $data->blog_posts_category->show_share_links == 1): ?>
                <div class="d-flex justify-content-center align-items-center mt-5">
                    <hr class="w-100" style="border-color: #26282B;">

                    <span class="mx-4">
                        <svg class="svg-inline--fa fa-infinity fa-w-20 fa-fw" style="color: #26282B;" aria-hidden="true" focusable="false" data-prefix="fa" data-icon="infinity" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" data-fa-i2svg=""><path fill="currentColor" d="M471.1 96C405 96 353.3 137.3 320 174.6 286.7 137.3 235 96 168.9 96 75.8 96 0 167.8 0 256s75.8 160 168.9 160c66.1 0 117.8-41.3 151.1-78.6 33.3 37.3 85 78.6 151.1 78.6 93.1 0 168.9-71.8 168.9-160S564.2 96 471.1 96zM168.9 320c-40.2 0-72.9-28.7-72.9-64s32.7-64 72.9-64c38.2 0 73.4 36.1 94 64-20.4 27.6-55.9 64-94 64zm302.2 0c-38.2 0-73.4-36.1-94-64 20.4-27.6 55.9-64 94-64 40.2 0 72.9 28.7 72.9 64s-32.7 64-72.9 64z"></path></svg><!-- <i class="fa fa-infinity fa-fw" style="color: #26282B;"></i> Font Awesome fontawesome.com -->
                    </span>

                    <hr class="w-100" style="border-color: #26282B;">
                </div>
                <h6 class="mt-3 mb-4 fcc-widget-title"><?= sprintf(l('blog.more_info.share')) ?></h6>
                <?php
                $fcc_blog_share_copy_url = \Altum\Link::get_share_tracking_url($share_url, 'direct_share', 'copy', 'blog_share');
                $fcc_blog_share_email_url = \Altum\Link::get_share_tracking_url($share_url, 'email', 'share', 'blog_share');
                $fcc_blog_share_facebook_url = \Altum\Link::get_share_tracking_url($share_url, 'facebook', 'share_button', 'blog_share');
                $fcc_blog_share_whatsapp_url = \Altum\Link::get_share_tracking_url($share_url, 'whatsapp', 'message', 'blog_share');
                ?>
                <div class="d-flex align-items-center justify-content-between flex-wrap mt-4">
                    <button type="button" id="copy-url" class="btn btn-gray-100 mb-2 mb-md-0 mr-md-3" style="color:#41aaa5" data-url="<?= htmlspecialchars($fcc_blog_share_copy_url, ENT_QUOTES, 'UTF-8') ?>" data-toggle="tooltip" title="<?= l('blog.copy_url') ?>" onclick="copy_url()"><i class="fa fa-fw fa-sm fa-link"></i></button>
                    <input type="hidden" id="copy-url-copied" value="<?= l('blog.copy_url.copied') ?>" />

                    <a href="mailto:?body=<?= rawurlencode($fcc_blog_share_email_url) ?>" target="_blank" class="btn btn-gray-100 mb-2 mb-md-0 mr-md-3">
                        <i class="fa fa-fw fa-envelope"></i>
                    </a>

                    <button type="button" class="btn btn-gray-100 mb-2 mb-md-0 mr-md-3" style="color:#41aaa5" data-toggle="tooltip" title="<?= l('page.print') ?>" onclick="window.print()"><i class="fa fa-fw fa-sm fa-print"></i></button>

                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($fcc_blog_share_facebook_url) ?>" target="_blank" class="btn btn-gray-100 mb-2 mb-md-0 mr-md-3">
                        <i class="fab fa-fw fa-facebook"></i>
                    </a>
                    <a href="https://wa.me/?text=<?= rawurlencode($fcc_blog_share_whatsapp_url) ?>" class="btn btn-gray-100 mb-2 mb-md-0 mr-md-3">
                        <i class="fab fa-fw fa-whatsapp"></i>
                    </a>
                </div>
            <?php endif; ?>                        

            <?php if(!$fcc_is_start_package_context && count($fcc_product_compare_rows) > 1): ?>
                <section class="card mt-4 fcc-glass-card fcc-product-comparison-card" id="fcc-product-comparison">
                    <div class="card-body">
                        <div class="fcc-related-header">
                            <span class="fcc-related-eyebrow"><?= $fcc_blog_microcopy['comparison_eyebrow'] ?></span>
                            <h2 class="fcc-related-title"><?= $fcc_blog_microcopy['comparison_title'] ?></h2>
                            <p class="fcc-category-shop-note mb-0"><?= $fcc_blog_microcopy['comparison_subtitle'] ?></p>
                        </div>

                        <div class="fcc-product-compare-list">
                            <?php foreach($fcc_product_compare_rows as $comparison_row): ?>
                                <article class="fcc-product-compare-row <?= $comparison_row['is_current'] ? 'is-current' : null ?>">
                                    <div class="fcc-product-compare-main">
                                        <div class="fcc-product-compare-topline">
                                            <span class="fcc-product-card-eyebrow"><?= $comparison_row['state']['eyebrow'] ?></span>
                                            <?php if($comparison_row['is_current']): ?>
                                                <span class="fcc-product-compare-badge"><?= $fcc_blog_microcopy['comparison_current'] ?></span>
                                            <?php endif ?>
                                        </div>

                                        <h3 class="fcc-product-compare-title"><?= e($comparison_row['title']) ?></h3>
                                        <p class="fcc-product-compare-text mb-0"><?= e($comparison_row['state']['description']) ?></p>
                                    </div>

                                    <div class="fcc-product-compare-meta">
                                        <?php if($comparison_row['state']['shop_ready']): ?>
                                            <span class="fcc-product-card-chip"><?= $fcc_blog_microcopy['comparison_ready'] ?></span>
                                        <?php endif ?>

                                        <?php if($comparison_row['state']['market_count'] > 0): ?>
                                            <span class="fcc-product-card-chip">
                                                <?= e($fcc_global_market_display_chip ?: sprintf(
                                                    $comparison_row['state']['market_count'] === 1 ? $fcc_blog_microcopy['comparison_market_single'] : $fcc_blog_microcopy['comparison_markets'],
                                                    $comparison_row['state']['market_count']
                                                )) ?>
                                            </span>
                                        <?php endif ?>
                                    </div>

                                    <div class="fcc-product-compare-actions">
                                        <?php if($comparison_row['is_current']): ?>
                                            <a
                                                href="<?= e($fcc_blog_toc_current_url . '#fcc-product-guide') ?>"
                                                class="fcc-product-card-cta"
                                                data-fcc-scroll-target="fcc-product-guide"
                                                data-fcc-blog-event="product_jump_click"
                                                data-fcc-blog-component="comparison_current"
                                                data-fcc-blog-label="<?= e($fcc_blog_microcopy['action_guide']) ?>"
                                            >
                                                <?= $fcc_blog_microcopy['action_guide'] ?>
                                                <i class="fas fa-fw fa-arrow-right ml-1"></i>
                                            </a>
                                        <?php else: ?>
                                            <a
                                                href="<?= e($comparison_row['url']) ?>"
                                                class="fcc-product-card-cta"
                                                data-fcc-blog-event="product_compare_click"
                                                data-fcc-blog-component="comparison_card"
                                                data-fcc-blog-label="<?= e($comparison_row['title']) ?>"
                                            >
                                                <?= $fcc_blog_microcopy['comparison_cta'] ?>
                                                <i class="fas fa-fw fa-arrow-right ml-1"></i>
                                            </a>
                                        <?php endif ?>
                                    </div>
                                </article>
                            <?php endforeach ?>
                        </div>
                    </div>
                </section>
            <?php endif ?>

            <?php if(!empty($fcc_related_blog_posts)): ?>
                <section class="card mt-4 fcc-glass-card fcc-related-card" <?= $fcc_is_start_package_context ? 'id="fcc-start-next-steps"' : null ?>>
                    <div class="card-body">
                        <div class="fcc-related-header">
                            <span class="fcc-related-eyebrow"><?= $fcc_blog_microcopy['related_eyebrow'] ?></span>
                            <h2 class="fcc-related-title"><?= $fcc_blog_microcopy['related_title'] ?></h2>
                        </div>

                        <div class="row">
                            <?php foreach($fcc_related_blog_posts as $related_blog_post): ?>
                                <?php $fcc_related_card_state = $fcc_get_related_card_state($related_blog_post); ?>
                                <div class="col-12 col-md-6 mb-3">
                                    <article
                                        class="fcc-related-product-card h-100"
                                        data-fcc-product-card="1"
                                        data-fcc-shop-ready="<?= $fcc_related_card_state['shop_ready'] ? '1' : '0' ?>"
                                        data-fcc-market-count="<?= (int) $fcc_related_card_state['market_count'] ?>"
                                        data-fcc-title="<?= e($related_blog_post->title) ?>"
                                    >
                                        <?php if(!empty($related_blog_post->image)): ?>
                                            <a
                                                href="<?= SITE_URL . ($related_blog_post->language ? \Altum\Language::$active_languages[$related_blog_post->language] . '/' : null) . 'blog/' . $related_blog_post->url ?>"
                                                class="fcc-related-product-image-link"
                                                data-fcc-blog-event="product_related_click"
                                                data-fcc-blog-component="related_image"
                                                data-fcc-blog-post-id="<?= (int) $related_blog_post->blog_post_id ?>"
                                                data-fcc-blog-label="<?= e($related_blog_post->title) ?>"
                                            >
                                                <img src="<?= \Altum\Uploads::get_full_url('blog') . $related_blog_post->image ?>" class="fcc-related-product-image" alt="<?= e($related_blog_post->image_description ?? $related_blog_post->title) ?>" loading="lazy" decoding="async" />
                                            </a>
                                        <?php endif ?>

                                        <div class="fcc-related-product-body">
                                            <span class="fcc-product-card-eyebrow"><?= $fcc_related_card_state['eyebrow'] ?></span>
                                            <a
                                                href="<?= SITE_URL . ($related_blog_post->language ? \Altum\Language::$active_languages[$related_blog_post->language] . '/' : null) . 'blog/' . $related_blog_post->url ?>"
                                                class="fcc-related-product-link"
                                                data-fcc-blog-event="product_related_click"
                                                data-fcc-blog-component="related_title"
                                                data-fcc-blog-post-id="<?= (int) $related_blog_post->blog_post_id ?>"
                                                data-fcc-blog-label="<?= e($related_blog_post->title) ?>"
                                            >
                                                <?= $related_blog_post->title ?>
                                            </a>

                                            <?php if(!empty($fcc_related_card_state['chips'])): ?>
                                                <div class="fcc-product-card-meta">
                                                    <?php foreach($fcc_related_card_state['chips'] as $chip): ?>
                                                        <span class="fcc-product-card-chip"><?= e($chip) ?></span>
                                                    <?php endforeach ?>
                                                </div>
                                            <?php endif ?>

                                            <p class="fcc-related-product-text mb-0"><?= e($fcc_related_card_state['description']) ?></p>

                                            <a
                                                href="<?= SITE_URL . ($related_blog_post->language ? \Altum\Language::$active_languages[$related_blog_post->language] . '/' : null) . 'blog/' . $related_blog_post->url ?>"
                                                class="fcc-product-card-cta"
                                                data-fcc-blog-event="product_related_click"
                                                data-fcc-blog-component="related_cta"
                                                data-fcc-blog-post-id="<?= (int) $related_blog_post->blog_post_id ?>"
                                                data-fcc-blog-label="<?= e($related_blog_post->title) ?>"
                                            >
                                                <?= $fcc_related_card_state['cta'] ?>
                                                <i class="fas fa-fw fa-arrow-right ml-1"></i>
                                            </a>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </section>
            <?php endif ?>
         
            <?php if($data->blog_posts_category): ?>
                <?php /* Custom code: FC-2026-03-09: keep back button as secondary CTA */ ?>
                <a href="<?= SITE_URL . ($data->blog_posts_category->language ? \Altum\Language::$active_languages[$data->blog_posts_category->language] . '/' : null) . 'blog/category/' . $data->blog_posts_category->url ?>" class="mt-5 btn btn-block btn-primary link-btn link-hover-animation link-btn-rounded animate__animated animate__ animate__false animate__delay-2s fcc-cta-btn fcc-cta-btn-secondary">                        
                <?php /* /Custom code: FC-2026-03-09 */ ?>
                    <span data-icon="">
                        <svg class="svg-inline--fa fa-angle-double-left fa-w-14 mr-1" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-double-left" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M223.7 239l136-136c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9L319.9 256l96.4 96.4c9.4 9.4 9.4 24.6 0 33.9L393.7 409c-9.4 9.4-24.6 9.4-33.9 0l-136-136c-9.5-9.4-9.5-24.6-.1-34zm-192 34l136 136c9.4 9.4 24.6 9.4 33.9 0l22.6-22.6c9.4-9.4 9.4-24.6 0-33.9L127.9 256l96.4-96.4c9.4-9.4 9.4-24.6 0-33.9L201.7 103c-9.4-9.4-24.6-9.4-33.9 0l-136 136c-9.5 9.4-9.5 24.6-.1 34z"></path></svg><!-- <i class="fas fa-angle-double-left mr-1"></i> Font Awesome fontawesome.com -->
                    </span>
                    <span data-name=""><?= sprintf(l('blog.back')); ?></span>
                </a>
            <?php endif ?>
            <!-- /Custom code -->
        </div>

        <?php if(settings()->content->blog_popular_widget_is_enabled || settings()->content->blog_categories_widget_is_enabled || settings()->content->blog_search_widget_is_enabled): ?>
            <?php /* Custom code: FC-2026-03-09: move sidebar widgets after article on mobile */ ?>
            <div class="<?= settings()->content->blog_columns == 1 ? 'col-12' : 'col-12 col-lg-4' ?> fcc-blog-sidebar-col order-2 order-lg-2">
            <?php /* /Custom code: FC-2026-03-09 */ ?>
                <div class="fcc-sidebar-sticky">
                <?php if(settings()->content->blog_search_widget_is_enabled): ?>
                    <div class="card mb-4 fcc-glass-card fcc-widget-card d-none d-lg-block" style="position: relative; z-index: 30; overflow: visible;">
                        <div class="card-body">
                            <form action="<?= url('blog') ?>" method="get" role="form">
                                <!-- Custom code: FC-2026-03-09: blog autocomplete-ready search input -->
                                <div class="position-relative" data-fcc-blog-search style="z-index: 40;">
                                    <div class="input-group">
                                        <input type="search" name="search" class="form-control" value="<?= !empty($_GET['search']) ? input_clean($_GET['search']) : null ?>" placeholder="<?= l('global.search') ?>" aria-label="<?= l('global.search') ?>" autocomplete="off" data-fcc-blog-search-input />

                                        <div class="input-group-append">
                                            <button class="btn btn-outline-gray-300 text-dark" type="submit" data-toggle="tooltip" title="<?= l('global.submit') ?>"><i class="fas fa-fw fa-search"></i></button>
                                        </div>
                                    </div>
                                    <div class="dropdown-menu w-100" data-fcc-blog-search-results style="z-index: 2050;"></div>
                                </div>
                                <!-- /Custom code: FC-2026-03-09 -->
                            </form>
                        </div>
                    </div>
                <?php endif ?>

                <?php if(settings()->content->blog_categories_widget_is_enabled && count($data->blog_posts_main_categories)): ?>
                     <?php if(count($data->blog_posts_main_categories)): ?>
                        <div class="card mb-4 fcc-glass-card fcc-widget-card">
                            <div class="card-body">
                                <h3 class="h6 mb-3 orange fcc-widget-title"><?= $fcc_blog_categories_title ?></h3>

                                <ul class="list-style-none m-0 categories-menu fcc-widget-list">
                                    <?php foreach($data->blog_posts_main_categories as $blog_post_main_category): ?>                                
                                        <li class="mb-2 fcc-widget-link-item <?= $data->blog_posts_category->title == $blog_post_main_category->title ? 'menu-active' : null ?>">
                                            <a href="<?= SITE_URL . ($blog_post_main_category->language ? \Altum\Language::$active_languages[$blog_post_main_category->language] . '/' : null) . 'blog/category/' . $blog_post_main_category->url ?>"><?= $blog_post_main_category->title ?></a>
                                        </li>         
                                        <?php foreach($data->blog_posts_parents as $blog_post_parent_category): ?>                                        
                                            <?php if ($blog_post_parent_category->blog_posts_parent_id == $blog_post_main_category->blog_posts_category_id): ?> 
                                                <ul class="m-0">
                                                    <li class="mb-2 fcc-widget-link-item <?= $data->blog_posts_category->title == $blog_post_parent_category->title ? 'menu-active' : null ?>">
                                                        <a href="<?= SITE_URL . ($blog_post_parent_category->language ? \Altum\Language::$active_languages[$blog_post_parent_category->language] . '/' : null) . 'blog/category/' . $blog_post_parent_category->url ?>"><?= $blog_post_parent_category->title ?></a>
                                                    </li>
                                                    <?php foreach($data->blog_posts_subcategories as $blog_post_subcategory): ?>                                        
                                                        <?php if ($blog_post_subcategory->blog_posts_parent_id == $blog_post_parent_category->blog_posts_category_id): ?> 
                                                            <ul class="m-0">
                                                                <li class="mb-2 fcc-widget-link-item <?= $data->blog_posts_category->title == $blog_post_subcategory->title ? 'menu-active' : null ?>">
                                                                    <a href="<?= SITE_URL . ($blog_post_subcategory->language ? \Altum\Language::$active_languages[$blog_post_subcategory->language] . '/' : null) . 'blog/category/' . $blog_post_subcategory->url ?>"><?= $blog_post_subcategory->title ?></a>
                                                                </li>
                                                            </ul>
                                                        <?php endif; ?>  
                                                    <?php endforeach ?>
                                                </ul>                                       
                                            <?php endif; ?>  
                                        <?php endforeach ?>                                                                                                                                                           
                                    <?php endforeach ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif ?>
                <?php endif ?>

                <?php if(settings()->content->blog_popular_widget_is_enabled && count($data->blog_posts_popular)): ?>
                    <div class="card mb-4 fcc-glass-card fcc-widget-card">
                        <div class="card-body">
                            <h3 class="h6 mb-3 fcc-widget-title"><?= $fcc_blog_popular_title ?></h3>

                            <ul class="list-style-none m-0 fcc-widget-list">
                                <?php foreach($data->blog_posts_popular as $blog_post): ?>
                                    <li class="mb-3 d-flex align-items-center fcc-popular-item">
                                        <?php if(!empty($blog_post->image)): ?>
                                            <img src="<?= \Altum\Uploads::get_full_url('blog') . $blog_post->image ?>" alt="<?= $blog_post->title ?>" class="fcc-popular-thumb mr-3" loading="lazy" decoding="async" />
                                        <?php else: ?>
                                            <span class="fcc-popular-badge mr-3"></span>
                                        <?php endif ?>

                                        <div class="fcc-popular-item-content">
                                            <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" class="font-size-small fcc-popular-link"><?= $blog_post->title ?></a>
                                            <div class="small fcc-popular-meta">
                                                <?php if($blog_post->blog_posts_category_id && isset($data->blog_posts_categories[$blog_post->blog_posts_category_id])): ?>
                                                    <a href="<?= SITE_URL . ($data->blog_posts_categories[$blog_post->blog_posts_category_id]->language ? \Altum\Language::$active_languages[$data->blog_posts_categories[$blog_post->blog_posts_category_id]->language] . '/' : null) . 'blog/category/' . $data->blog_posts_categories[$blog_post->blog_posts_category_id]->url ?>" class="text-muted"><?= $data->blog_posts_categories[$blog_post->blog_posts_category_id]->title ?></a>
                                                    <?php if(settings()->content->blog_views_is_enabled): ?>
                                                        <span class="text-muted"> • </span>
                                                    <?php endif ?>
                                                <?php endif ?>

                                                <?php if(settings()->content->blog_views_is_enabled): ?>
                                                    <span class="text-muted"><?= sprintf(l('blog.total_views'), nr($blog_post->total_views)) ?></span>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    </div>
                <?php endif ?>
            </div>
            </div>
        <?php endif ?>
    </div>
</div>
</div>
<!-- /Custom code: FC-2026-02-26 -->

<?php if($fcc_blog_primary_cta_url && $fcc_is_product_context): ?>
    <div class="fcc-mobile-sticky-cta d-lg-none">
        <a
            target="_blank"
            href="<?= $fcc_blog_primary_cta_url ?>"
            class="fcc-mobile-sticky-cta-btn"
            data-fcc-blog-event="product_sticky_cta_click"
            data-fcc-blog-component="mobile_sticky_cta"
            data-fcc-blog-label="<?= e($fcc_primary_cta_label) ?>"
        >
            <i class="fas fa-shopping-cart mr-2"></i>
            <span><?= $fcc_primary_cta_label ?></span>
        </a>
    </div>
<?php endif ?>

<div class="fcc-referral-tour-backdrop" id="fcc_referral_tour_backdrop">
    <div class="fcc-referral-tour-backdrop-segment" data-segment="top"></div>
    <div class="fcc-referral-tour-backdrop-segment" data-segment="left"></div>
    <div class="fcc-referral-tour-backdrop-segment" data-segment="right"></div>
    <div class="fcc-referral-tour-backdrop-segment" data-segment="bottom"></div>
</div>
<div class="fcc-referral-tour-popover" id="fcc_referral_tour_popover" aria-live="polite">
    <div class="fcc-referral-tour-progress" id="fcc_referral_tour_progress">1 / 4</div>
    <div class="fcc-referral-tour-title" id="fcc_referral_tour_title"></div>
    <div class="fcc-referral-tour-text" id="fcc_referral_tour_text"></div>
    <div class="fcc-referral-tour-actions">
        <button type="button" class="btn btn-link text-muted px-0" id="fcc_referral_tour_skip"><?= l('dashboard.tour.skip') ?></button>
        <div class="fcc-referral-tour-actions-main">
            <button type="button" class="btn btn-outline-light" id="fcc_referral_tour_prev"><?= l('dashboard.tour.prev') ?></button>
            <button type="button" class="btn btn-primary" id="fcc_referral_tour_next"><?= l('dashboard.tour.next') ?></button>
        </div>
    </div>
</div>

<?php
$fcc_blog_page_language_code = !empty($data->blog_post->language) && isset(\Altum\Language::$active_languages[$data->blog_post->language])
    ? \Altum\Language::$active_languages[$data->blog_post->language]
    : (\Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr');

$fcc_blog_chat_enabled = !empty($data->ai_chat_owner_user_id)
    && fcc_ai_user_has_public_ai_access((int) $data->ai_chat_owner_user_id);

$fcc_blog_chat_language_code = !empty($data->ai_chat_owner_user_id)
    ? fcc_ai_get_public_assistant_default_language((int) $data->ai_chat_owner_user_id, 'product_advisor', 'public_app', $fcc_blog_page_language_code)
    : fcc_ai_resolve_public_reply_language($fcc_blog_page_language_code);
?>

<?php if($fcc_blog_chat_enabled): ?>
    <?= include_view(THEME_PATH . 'views/l/partials/fcc_chat_extreme_popup.php', [
        'config' => [
            'assistant_type' => 'product_advisor',
            'scope' => 'public_blog',
            'link_id' => (int) ($data->ai_chat_owner_link_id ?? 0),
            'blog_post_id' => (int) ($data->blog_post->blog_post_id ?? 0),
            'owner_name' => (string) ($data->ai_chat_owner_name ?? ''),
            'language_code' => $fcc_blog_chat_language_code,
            'source_context' => 'FCC blog article',
            'hide_without_context' => true,
            'dom_id' => 'fcc-blog-chat-extreme-' . (int) ($data->blog_post->blog_post_id ?? 0),
            'intro_label' => 'FCC Preporuka',
        ],
    ]) ?>
<?php endif ?>

<?php ob_start() ?>
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "<?= l('index.title') ?>",
                    "item": "<?= url() ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "<?= l('blog.title') ?>",
                    "item": "<?= url('blog') ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "<?= $data->blog_post->title ?>",
                    "item": "<?= SITE_URL . ($data->blog_post->language ? \Altum\Language::$active_languages[$data->blog_post->language] . '/' : null) . 'blog/' . $data->blog_post->url ?>"
                }
            ]
        }
</script>

<?php
$fcc_blog_faq_schema = null;

if(!empty($fcc_blog_faq_items)) {
    $fcc_blog_faq_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static function($item) {
            return [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ];
        }, $fcc_blog_faq_items),
    ];
}

$fcc_blog_product_schema = null;

if($fcc_is_product_context) {
    $fcc_schema_decimal_value = static function($value, bool $allow_zero = false): ?string {
        $value = trim(str_replace(',', '.', (string) $value));
        $value = preg_replace('/\s+/', '', $value);

        if($value === '' || !is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        if($allow_zero ? $number < 0 : $number <= 0) {
            return null;
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    };

    $fcc_schema_positive_int = static function($value, bool $allow_zero = true): ?int {
        $value = trim((string) $value);

        if($value === '' || !ctype_digit($value)) {
            return null;
        }

        $number = (int) $value;

        if($allow_zero ? $number < 0 : $number <= 0) {
            return null;
        }

        return $number;
    };

    $fcc_schema_country_code = static function($value): ?string {
        $value = mb_strtoupper(trim((string) $value));

        return preg_match('/^[A-Z]{2}$/', $value) ? $value : null;
    };

    $fcc_schema_currency_code = static function($value): ?string {
        $value = mb_strtoupper(trim((string) $value));

        return preg_match('/^[A-Z]{3}$/', $value) ? $value : null;
    };

    $fcc_schema_return_fee = static function($value): ?string {
        $value = trim((string) $value);

        if($value === '') {
            return null;
        }

        $map = [
            'freereturn' => 'https://schema.org/FreeReturn',
            'free_return' => 'https://schema.org/FreeReturn',
            'returnfeescustomerresponsibility' => 'https://schema.org/ReturnFeesCustomerResponsibility',
            'return_fees_customer_responsibility' => 'https://schema.org/ReturnFeesCustomerResponsibility',
            'returnshippingfees' => 'https://schema.org/ReturnShippingFees',
            'return_shipping_fees' => 'https://schema.org/ReturnShippingFees',
        ];

        $key = mb_strtolower(str_replace(['https://schema.org/', '-', ' '], ['', '_', '_'], $value));

        return $map[$key] ?? (preg_match('#^https://schema\.org/(FreeReturn|ReturnFeesCustomerResponsibility|ReturnShippingFees)$#', $value) ? $value : null);
    };

    $fcc_blog_product_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => (string) $data->blog_post->title,
        'description' => (string) $data->blog_post->description,
        'url' => (string) $fcc_blog_post_url,
        'brand' => [
            '@type' => 'Brand',
            'name' => 'Forever Living Products',
        ],
        'mainEntityOfPage' => $fcc_blog_post_url,
        'isRelatedTo' => [
            '@type' => 'WebPage',
            'name' => settings()->main->title,
            'url' => SITE_URL,
        ],
    ];

    if(!empty($data->blog_post->sku)) {
        $fcc_blog_product_schema['sku'] = (string) $data->blog_post->sku;
    }

    if($data->blog_post->image) {
        $fcc_blog_product_schema['image'] = [\Altum\Uploads::get_full_url('blog') . $data->blog_post->image];
    }

    if(!empty($data->blog_posts_category->title)) {
        $fcc_blog_product_schema['category'] = (string) $data->blog_posts_category->title;
    }

    if(!empty($fcc_product_summary_cards)) {
        $fcc_blog_product_schema['additionalProperty'] = array_values(array_filter(array_map(static function($item) {
            if(empty($item['label']) || empty($item['value'])) {
                return null;
            }

            return [
                '@type' => 'PropertyValue',
                'name' => (string) $item['label'],
                'value' => (string) $item['value'],
            ];
        }, $fcc_product_summary_cards)));
    }

    $fcc_schema_offer_price = $fcc_schema_decimal_value($fcc_blog_shop_context['schema_offer_price'] ?? '');
    $fcc_schema_offer_currency = $fcc_schema_currency_code($fcc_blog_shop_context['schema_offer_currency'] ?? '');
    $fcc_schema_shop_url = !empty($data->webshop_link) ? (string) $data->webshop_link : (string) $fcc_blog_product_cta_url;

    if($fcc_schema_shop_url) {
        $fcc_blog_product_schema['sameAs'] = $fcc_schema_shop_url;
    }

    if($fcc_schema_shop_url && $fcc_schema_offer_price && $fcc_schema_offer_currency) {
        $fcc_blog_product_schema['offers'] = [
            '@type' => 'Offer',
            'url' => $fcc_schema_shop_url,
            'price' => $fcc_schema_offer_price,
            'priceCurrency' => $fcc_schema_offer_currency,
            'availability' => 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => [
                '@type' => 'Organization',
                'name' => 'Forever Living Products',
            ],
        ];

        $fcc_schema_shipping_country = $fcc_schema_country_code($fcc_blog_shop_context['schema_shipping_country'] ?? '');
        $fcc_schema_shipping_price = $fcc_schema_decimal_value($fcc_blog_shop_context['schema_shipping_price'] ?? '', true);
        $fcc_schema_shipping_min_days = $fcc_schema_positive_int($fcc_blog_shop_context['schema_shipping_min_days'] ?? '');
        $fcc_schema_shipping_max_days = $fcc_schema_positive_int($fcc_blog_shop_context['schema_shipping_max_days'] ?? '');

        if($fcc_schema_shipping_country && $fcc_schema_shipping_price !== null && $fcc_schema_shipping_min_days !== null && $fcc_schema_shipping_max_days !== null && $fcc_schema_shipping_max_days >= $fcc_schema_shipping_min_days) {
            $fcc_blog_product_schema['offers']['shippingDetails'] = [
                '@type' => 'OfferShippingDetails',
                'shippingRate' => [
                    '@type' => 'MonetaryAmount',
                    'value' => $fcc_schema_shipping_price,
                    'currency' => $fcc_schema_offer_currency,
                ],
                'shippingDestination' => [
                    '@type' => 'DefinedRegion',
                    'addressCountry' => $fcc_schema_shipping_country,
                ],
                'deliveryTime' => [
                    '@type' => 'ShippingDeliveryTime',
                    'handlingTime' => [
                        '@type' => 'QuantitativeValue',
                        'minValue' => 0,
                        'maxValue' => 1,
                        'unitCode' => 'DAY',
                    ],
                    'transitTime' => [
                        '@type' => 'QuantitativeValue',
                        'minValue' => $fcc_schema_shipping_min_days,
                        'maxValue' => $fcc_schema_shipping_max_days,
                        'unitCode' => 'DAY',
                    ],
                ],
            ];
        }

        $fcc_schema_return_country = $fcc_schema_country_code($fcc_blog_shop_context['schema_return_country'] ?? '');
        $fcc_schema_return_days = $fcc_schema_positive_int($fcc_blog_shop_context['schema_return_days'] ?? '', false);

        if($fcc_schema_return_country && $fcc_schema_return_days !== null) {
            $fcc_blog_product_schema['offers']['hasMerchantReturnPolicy'] = [
                '@type' => 'MerchantReturnPolicy',
                'applicableCountry' => $fcc_schema_return_country,
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays' => $fcc_schema_return_days,
            ];

            $fcc_schema_return_fees = $fcc_schema_return_fee($fcc_blog_shop_context['schema_return_fees'] ?? '');

            if($fcc_schema_return_fees) {
                $fcc_blog_product_schema['offers']['hasMerchantReturnPolicy']['returnFees'] = $fcc_schema_return_fees;
            }
        }
    }

    if(settings()->content->blog_ratings_is_enabled && $data->blog_post->total_ratings > 0) {
        $fcc_blog_product_schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $data->blog_post->average_rating,
            'reviewCount' => (string) $data->blog_post->total_ratings,
        ];
    }

    if(empty($fcc_blog_product_schema['offers']) && empty($fcc_blog_product_schema['review']) && empty($fcc_blog_product_schema['aggregateRating'])) {
        $fcc_blog_product_schema = null;
    }
}
?>

<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": <?= json_encode($data->blog_post->title, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
        "description": <?= json_encode($fcc_blog_public_bundle['schema_description'] ?? ($data->blog_post->description ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
        "url": <?= json_encode($fcc_blog_post_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    <?php if($data->blog_post->image): ?>
        "image": <?= json_encode(\Altum\Uploads::get_full_url('blog') . $data->blog_post->image, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
        <?php endif ?>
    "author": {
        "@type": "Person",
        "name": <?= json_encode(settings()->main->title, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            "url": <?= json_encode(SITE_URL, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
        },

    <?php if(!$fcc_is_product_context && settings()->content->blog_ratings_is_enabled && $data->blog_post->total_ratings > 0): ?>
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "<?= $data->blog_post->average_rating ?>",
            "reviewCount": "<?= $data->blog_post->total_ratings ?>",
            "itemReviewed" : {
                "@type": "Article",
                "name": <?= json_encode($data->blog_post->title, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
            }
        },
        <?php endif ?>

    "publisher": {
        "@type": "Organization",
        "name": <?= json_encode(settings()->main->title, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    <?php if(settings()->main->{'logo_' . \Altum\ThemeStyle::get()} != ''): ?>
            ,"logo": {
                "@type": "ImageObject",
                "url": <?= json_encode(settings()->main->{'logo_' . \Altum\ThemeStyle::get() . '_full_url'}, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
            }
            <?php endif ?>
    },
    "datePublished": "<?= (new \DateTime($data->blog_post->datetime))->format('Y-m-d\TH:i:sP') ?>",
        "dateModified": "<?= (new \DateTime($data->blog_post->last_datetime))->format('Y-m-d\TH:i:sP') ?>",
        "keywords": <?= json_encode($fcc_blog_public_bundle['meta_keywords'] ?? ($data->blog_post->keywords ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
        "wordCount": "<?= str_word_count($data->blog_post->content ?? '') ?>",
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": <?= json_encode($fcc_blog_post_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
        }
    }
</script>

<?php if($fcc_blog_faq_schema): ?>
<script type="application/ld+json">
    <?= json_encode($fcc_blog_faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<?php endif ?>

<?php if($fcc_blog_product_schema): ?>
<script type="application/ld+json">
    <?= json_encode($fcc_blog_product_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<?php endif ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trackingUrl = `${url}blog/interactions_ajax`;
    const blogPostId = <?= (int) ($data->blog_post->blog_post_id ?? 0) ?>;
    const blogCategoryId = <?= (int) ($data->blog_posts_category->blog_posts_category_id ?? 0) ?>;
    const pageUrl = <?= json_encode($fcc_blog_post_url) ?> || window.location.href;
    const scrollLinks = document.querySelectorAll('[data-fcc-scroll-target]');

    const trackEvent = (eventType, payload = {}) => {
        if(!window.global_token || !eventType || !blogPostId) {
            return;
        }

        const form = new FormData();
        form.set('global_token', global_token);
        form.set('event_type', eventType);
        form.set('page_type', 'post');
        form.set('blog_post_id', String(blogPostId));
        form.set('page_url', pageUrl || window.location.href);

        if(blogCategoryId) {
            form.set('blog_posts_category_id', String(blogCategoryId));
        }

        if(payload.blog_post_id) {
            form.set('blog_post_id', String(payload.blog_post_id));
        }

        if(payload.component) {
            form.set('component', payload.component);
        }

        if(payload.label) {
            form.set('event_label', payload.label);
        }

        if(payload.event_data) {
            form.set('event_data', JSON.stringify(payload.event_data));
        }

        if(navigator.sendBeacon) {
            navigator.sendBeacon(trackingUrl, form);
            return;
        }

        fetch(trackingUrl, {
            method: 'post',
            body: form,
            keepalive: true,
        }).catch(() => {});
    };

    document.querySelectorAll('[data-fcc-blog-event]').forEach((element) => {
        element.addEventListener('click', () => {
            trackEvent(element.dataset.fccBlogEvent || '', {
                blog_post_id: parseInt(element.dataset.fccBlogPostId || '0', 10),
                component: element.dataset.fccBlogComponent || '',
                label: element.dataset.fccBlogLabel || element.textContent.trim(),
                event_data: {
                    target: element.getAttribute('href') || element.getAttribute('data-fcc-scroll-target') || '',
                }
            });
        });
    });

    document.querySelectorAll('[data-fcc-faq-track]').forEach((button) => {
        button.addEventListener('click', () => {
            if(button.getAttribute('aria-expanded') === 'true') {
                return;
            }

            trackEvent('product_faq_open', {
                component: 'faq_accordion',
                label: button.dataset.fccBlogLabel || button.textContent.trim(),
            });
        });
    });

    if(!scrollLinks.length) {
        return;
    }

    const scrollToTarget = (targetId, updateHash = true) => {
        if(!targetId) {
            return false;
        }

        const target = document.getElementById(targetId);

        if(!target) {
            return false;
        }

        target.scrollIntoView({behavior: 'smooth', block: 'start', inline: 'nearest'});

        if(updateHash && window.history && typeof window.history.replaceState === 'function') {
            const nextUrl = `${window.location.pathname}${window.location.search}#${encodeURIComponent(targetId)}`;
            window.history.replaceState(null, '', nextUrl);
        }

        return true;
    };

    scrollLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const targetId = link.getAttribute('data-fcc-scroll-target');

            if(!targetId || !document.getElementById(targetId)) {
                return;
            }

            event.preventDefault();
            scrollToTarget(targetId);
        });
    });

    if(window.location.hash) {
        const targetId = decodeURIComponent(window.location.hash.replace(/^#/, ''));

        if(targetId && document.getElementById(targetId)) {
            window.setTimeout(() => scrollToTarget(targetId, false), 120);
        }
    }
});
</script>

<script>
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const popupWrapper = document.getElementById('blog-share-referral-wrapper');
        const popupToggle = document.getElementById('blog-share-referral-toggle');
        const popupClose = document.getElementById('blog-share-referral-close');
        const popupPanel = document.getElementById('blog-share-referral-popup');

        if(!popupWrapper || !popupToggle || !popupPanel) {
            return;
        }

        const showPopup = () => {
            popupPanel.classList.remove('d-none');
            popupToggle.setAttribute('aria-expanded', 'true');
        };

        const hidePopup = () => {
            popupPanel.classList.add('d-none');
            popupToggle.setAttribute('aria-expanded', 'false');
        };

        popupToggle.addEventListener('click', (event) => {
            event.preventDefault();

            if(popupPanel.classList.contains('d-none')) {
                showPopup();
            } else {
                hidePopup();
            }
        });

        if(popupClose) {
            popupClose.addEventListener('click', (event) => {
                event.preventDefault();
                hidePopup();
            });
        }

        document.addEventListener('click', (event) => {
            if(!popupPanel.classList.contains('d-none') && !popupWrapper.contains(event.target)) {
                hidePopup();
            }
        });

        document.addEventListener('keydown', (event) => {
            if(event.key === 'Escape' && !popupPanel.classList.contains('d-none')) {
                hidePopup();
            }
        });
    });
</script>

<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php ob_start() ?>
<script>
(function() {
    'use strict';

    const storageKey = 'fcc_blog_referral_tour_v1';
    const defaultNextLabel = <?= json_encode(l('dashboard.tour.next')) ?>;
    const finishLabel = <?= json_encode(l('dashboard.tour.finish')) ?>;

    let activeStep = -1;
    let currentTarget = null;

    const setTourMode = isActive => {
        document.body.classList.toggle('fcc-tour-mode', !!isActive);

        if(typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent('fcc:tutorial:state', {
                detail: {active: !!isActive}
            }));
        }
    };

    const steps = [
        {
            selector: '#fcc_tour_blog_share_row',
            title: <?= json_encode(l('dashboard.tutorials.blog_referral.article_step_1_title')) ?>,
            text: <?= json_encode(l('dashboard.tutorials.blog_referral.article_step_1_text')) ?>,
        },
        {
            selector: '#fcc_tour_blog_share_info',
            title: <?= json_encode(l('dashboard.tutorials.blog_referral.article_step_2_title')) ?>,
            text: <?= json_encode(l('dashboard.tutorials.blog_referral.article_step_2_text')) ?>,
            onShow: () => {
                const details = document.getElementById('fcc-navbar-share-details');
                const toggle = document.getElementById('fcc_tour_blog_share_info');

                if(details && details.classList.contains('d-none') && toggle) {
                    toggle.click();
                }
            }
        },
        {
            selector: '#fcc_tour_blog_share_buttons',
            title: <?= json_encode(l('dashboard.tutorials.blog_referral.article_step_3_title')) ?>,
            text: <?= json_encode(l('dashboard.tutorials.blog_referral.article_step_3_text')) ?>,
        },
        {
            selector: '#fcc_tour_blog_share_buttons [data-share-button="copy"]',
            title: <?= json_encode(l('dashboard.tutorials.blog_referral.article_step_4_title')) ?>,
            text: <?= json_encode(l('dashboard.tutorials.blog_referral.article_step_4_text')) ?>,
        }
    ];

    const getState = () => {
        try {
            const raw = localStorage.getItem(storageKey);
            return raw ? JSON.parse(raw) : null;
        } catch(error) {
            return null;
        }
    };

    const clearState = () => {
        try {
            localStorage.removeItem(storageKey);
        } catch(error) {
            /* Ignore storage failures. */
        }
    };

    const clearHighlight = () => {
        if(currentTarget) {
            currentTarget.classList.remove('is-active');
        }

        currentTarget = null;
    };

    const updateBackdrop = (rect) => {
        const backdrop = document.getElementById('fcc_referral_tour_backdrop');
        if(!backdrop || !rect) {
            return;
        }

        const topSegment = backdrop.querySelector('[data-segment="top"]');
        const leftSegment = backdrop.querySelector('[data-segment="left"]');
        const rightSegment = backdrop.querySelector('[data-segment="right"]');
        const bottomSegment = backdrop.querySelector('[data-segment="bottom"]');

        if(!topSegment || !leftSegment || !rightSegment || !bottomSegment) {
            return;
        }

        const padding = 12;
        const top = Math.max(0, rect.top - padding);
        const left = Math.max(0, rect.left - padding);
        const right = Math.min(window.innerWidth, rect.right + padding);
        const bottom = Math.min(window.innerHeight, rect.bottom + padding);
        const spotlightHeight = Math.max(0, bottom - top);

        topSegment.style.top = '0px';
        topSegment.style.left = '0px';
        topSegment.style.width = '100vw';
        topSegment.style.height = `${top}px`;

        leftSegment.style.top = `${top}px`;
        leftSegment.style.left = '0px';
        leftSegment.style.width = `${left}px`;
        leftSegment.style.height = `${spotlightHeight}px`;

        rightSegment.style.top = `${top}px`;
        rightSegment.style.left = `${right}px`;
        rightSegment.style.width = `${Math.max(0, window.innerWidth - right)}px`;
        rightSegment.style.height = `${spotlightHeight}px`;

        bottomSegment.style.top = `${bottom}px`;
        bottomSegment.style.left = '0px';
        bottomSegment.style.width = '100vw';
        bottomSegment.style.height = `${Math.max(0, window.innerHeight - bottom)}px`;
    };

    const placePopover = () => {
        const popover = document.getElementById('fcc_referral_tour_popover');
        if(!popover || !currentTarget || !popover.classList.contains('is-visible')) {
            return;
        }

        const rect = currentTarget.getBoundingClientRect();
        const margin = 20;
        const popoverWidth = popover.offsetWidth || 360;
        const popoverHeight = popover.offsetHeight || 220;

        updateBackdrop(rect);

        let top = rect.bottom + 16;
        if(top + popoverHeight > window.innerHeight - margin) {
            top = Math.max(margin, rect.top - popoverHeight - 16);
        }

        let left = rect.left;
        if(left + popoverWidth > window.innerWidth - margin) {
            left = window.innerWidth - popoverWidth - margin;
        }

        if(left < margin) {
            left = margin;
        }

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
    };

    const endTour = (clear = false) => {
        const backdrop = document.getElementById('fcc_referral_tour_backdrop');
        const popover = document.getElementById('fcc_referral_tour_popover');

        clearHighlight();
        activeStep = -1;
        setTourMode(false);

        if(backdrop) {
            backdrop.classList.remove('is-visible');
        }

        if(popover) {
            popover.classList.remove('is-visible');
            popover.style.top = '';
            popover.style.left = '';
        }

        if(clear) {
            clearState();
        }
    };

    const renderStep = (index) => {
        if(index >= steps.length) {
            endTour(true);
            return;
        }

        const step = steps[index];
        const target = step ? document.querySelector(step.selector) : null;
        const popover = document.getElementById('fcc_referral_tour_popover');
        const backdrop = document.getElementById('fcc_referral_tour_backdrop');

        if(!step || !popover || !backdrop) {
            endTour(true);
            return;
        }

        if(!target) {
            renderStep(index + 1);
            return;
        }

        activeStep = index;
        clearHighlight();
        currentTarget = target;
        currentTarget.classList.add('is-active');
        setTourMode(true);
        currentTarget.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'});

        if(typeof step.onShow === 'function') {
            step.onShow();
        }

        document.getElementById('fcc_referral_tour_title').textContent = step.title;
        document.getElementById('fcc_referral_tour_text').textContent = step.text;
        document.getElementById('fcc_referral_tour_progress').textContent = `${index + 1} / ${steps.length}`;

        const prevButton = document.getElementById('fcc_referral_tour_prev');
        const nextButton = document.getElementById('fcc_referral_tour_next');

        if(prevButton) {
            prevButton.style.display = index === 0 ? 'none' : 'inline-flex';
        }

        if(nextButton) {
            nextButton.textContent = index === steps.length - 1 ? finishLabel : defaultNextLabel;
        }

        backdrop.classList.add('is-visible');
        popover.classList.add('is-visible');

        setTimeout(placePopover, 140);
    };

    const initTutorial = () => {
        const state = getState();
        if(!state || state.flow !== 'blog_referral' || state.stage !== 'article') {
            return;
        }

        const skipButton = document.getElementById('fcc_referral_tour_skip');
        const prevButton = document.getElementById('fcc_referral_tour_prev');
        const nextButton = document.getElementById('fcc_referral_tour_next');

        if(skipButton) {
            skipButton.addEventListener('click', () => endTour(true));
        }

        if(prevButton) {
            prevButton.addEventListener('click', () => {
                if(activeStep > 0) {
                    renderStep(activeStep - 1);
                }
            });
        }

        if(nextButton) {
            nextButton.addEventListener('click', () => {
                if(activeStep >= steps.length - 1) {
                    endTour(true);
                    return;
                }

                renderStep(activeStep + 1);
            });
        }

        window.addEventListener('resize', placePopover);
        window.addEventListener('scroll', placePopover, {passive: true});

        setTimeout(() => renderStep(0), 420);
    };

    document.addEventListener('DOMContentLoaded', initTutorial);
})();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php ob_start() ?>
<script>
/* Custom code: FC-2026-03-09: blog autocomplete UI */
(function() {
    if(window.FCCBlogSearchAutocompleteInitialized) {
        return;
    }

    window.FCCBlogSearchAutocompleteInitialized = true;

    const initAutocomplete = (wrapper) => {
        const input = wrapper.querySelector('[data-fcc-blog-search-input]');
        const resultsMenu = wrapper.querySelector('[data-fcc-blog-search-results]');

        if(!input || !resultsMenu) {
            return;
        }

        let debounceTimeout = null;
        let suggestions = [];
        let activeIndex = -1;

        const hideResults = () => {
            resultsMenu.classList.remove('show');
            resultsMenu.innerHTML = '';
            suggestions = [];
            activeIndex = -1;
        };

        const escapeHtml = (value) => {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        };

        const renderResults = () => {
            if(!suggestions.length) {
                hideResults();
                return;
            }

            resultsMenu.innerHTML = suggestions.map((item, index) => `
                <a href="${item.url}" class="dropdown-item d-flex align-items-start py-2 ${index === activeIndex ? 'active' : ''}" data-fcc-suggestion-index="${index}">
                    ${item.image ? `<img src="${item.image}" alt="${escapeHtml(item.image_description || item.title)}" class="rounded mr-2" style="width: 40px; height: 40px; object-fit: cover;">` : '<span class="rounded mr-2 bg-gray-100" style="width: 40px; height: 40px;"></span>'}
                    <span class="d-flex flex-column text-truncate" style="min-width: 0;">
                        <span class="font-weight-bold text-truncate">${escapeHtml(item.title)}</span>
                        <small class="text-muted text-truncate">${escapeHtml(item.matched_term || '')}</small>
                    </span>
                </a>
            `).join('');

            resultsMenu.classList.add('show');
        };

        const setActive = (newIndex) => {
            if(!suggestions.length) {
                return;
            }

            activeIndex = newIndex;
            if(activeIndex < 0) activeIndex = suggestions.length - 1;
            if(activeIndex >= suggestions.length) activeIndex = 0;

            renderResults();
        };

        const requestSuggestions = async (query) => {
            try {
                const response = await fetch(`${url}blog/suggestions_ajax?search=${encodeURIComponent(query)}`);
                const data = await response.json();

                if(!response.ok || data.status !== 'success') {
                    hideResults();
                    return;
                }

                suggestions = data.details?.results ?? [];
                activeIndex = -1;
                renderResults();
            } catch(error) {
                hideResults();
            }
        };

        input.addEventListener('input', () => {
            const query = input.value.trim();

            if(debounceTimeout) {
                clearTimeout(debounceTimeout);
            }

            if(query.length < 3) {
                hideResults();
                return;
            }

            debounceTimeout = setTimeout(() => requestSuggestions(query), 250);
        });

        input.addEventListener('keydown', (event) => {
            if(!resultsMenu.classList.contains('show')) {
                return;
            }

            if(event.key === 'ArrowDown') {
                event.preventDefault();
                setActive(activeIndex + 1);
            }

            if(event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(activeIndex - 1);
            }

            if(event.key === 'Enter' && activeIndex >= 0 && suggestions[activeIndex]) {
                event.preventDefault();
                window.location.href = suggestions[activeIndex].url;
            }

            if(event.key === 'Escape') {
                hideResults();
            }
        });

        resultsMenu.addEventListener('click', (event) => {
            const item = event.target.closest('[data-fcc-suggestion-index]');
            if(!item) {
                return;
            }

            const itemIndex = parseInt(item.getAttribute('data-fcc-suggestion-index'));
            if(Number.isInteger(itemIndex) && suggestions[itemIndex]) {
                window.location.href = suggestions[itemIndex].url;
            }
        });

        document.addEventListener('click', (event) => {
            if(!wrapper.contains(event.target)) {
                hideResults();
            }
        });
    };

    document.querySelectorAll('[data-fcc-blog-search]').forEach(initAutocomplete);
})();
/* /Custom code: FC-2026-03-09 */
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
