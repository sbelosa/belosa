<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-02-26: EN/HR widget titles and category hero labels */
$fcc_blog_categories_title = l('blog.categories');
$fcc_blog_popular_title = l('blog.popular');
$fcc_category_hero_badge = l('blog.category.badge');
$fcc_category_back_to_blog = l('blog.back');
$fcc_blog_home_url = url('blog');
$fcc_category_shop_context = fc_blog_category_shop_context_normalize($data->blog_posts_category->shop_context ?? null);
$fcc_category_public_bundle = is_array($data->category_public_bundle ?? null) ? $data->category_public_bundle : [];
$fcc_category_public_subtitle = trim((string) ($fcc_category_public_bundle['public_subtitle'] ?? ''));
$fcc_category_meta_description = trim((string) ($fcc_category_public_bundle['meta_description'] ?? ''));
$fcc_category_direct_children = !empty($data->blog_posts_direct_children) ? array_values($data->blog_posts_direct_children) : [];
$fcc_is_forever_products_category = in_array((string) ($data->blog_posts_category->url ?? ''), ['forever-products', 'forever-proizvodi'], true)
    || (($fcc_category_shop_context['page_role'] ?? '') === 'shop_hub');
$fcc_forever_market_display_value = $fcc_is_forever_products_category ? '151+' : null;
$fcc_forever_market_display_chip = \Altum\Language::$code === 'hr' ? '151+ tržišta' : '151+ markets';
$fcc_category_all_posts = $fcc_is_forever_products_category
    ? (\Altum\Language::$code === 'hr' ? 'Odaberite proizvod koji vas zanima' : 'Choose the product you want to explore')
    : l('blog.category.all_posts');
$fcc_category_posts = !empty($data->blog_posts) ? array_values($data->blog_posts) : [];
$fcc_first_blog_post = !empty($fcc_category_posts) ? $fcc_category_posts[0] : null;
$fcc_first_blog_post_url = $fcc_first_blog_post ? SITE_URL . ($fcc_first_blog_post->language ? \Altum\Language::$active_languages[$fcc_first_blog_post->language] . '/' : null) . 'blog/' . $fcc_first_blog_post->url : null;
$fcc_category_alternate_urls = is_array($data->alternate_urls ?? null) ? $data->alternate_urls : [];
$fcc_shop_ready_posts = array_values(array_filter($fcc_category_posts, static function($blog_post) {
    $webshop_links = json_decode($blog_post->webshop_links ?? '{}', true) ?: [];

    foreach($webshop_links as $market_url) {
        if(!empty($market_url)) {
            return true;
        }
    }

    return false;
}));
$fcc_market_codes = [];

foreach($fcc_category_posts as $blog_post) {
    $webshop_links = json_decode($blog_post->webshop_links ?? '{}', true) ?: [];

    foreach($webshop_links as $market_code => $market_url) {
        if(!empty($market_url)) {
            $fcc_market_codes[$market_code] = true;
        }
    }
}

$fcc_category_stats = [
    'product_count' => count($fcc_category_posts),
    'shop_ready_count' => count($fcc_shop_ready_posts),
    'market_count' => count($fcc_market_codes),
];
$fcc_product_card_copy = \Altum\Language::$code === 'hr'
    ? [
        'eyebrow_product' => 'Forever proizvod',
        'eyebrow_guide' => 'Praktičan vodič',
        'available_badge' => 'Dostupno za narudžbu',
        'market_singular' => 'tržište',
        'market_plural' => 'tržišta',
        'cta' => 'Pogledaj detalje',
    ]
    : [
        'eyebrow_product' => 'Forever product',
        'eyebrow_guide' => 'Practical guide',
        'available_badge' => 'Ready to order',
        'market_singular' => 'market',
        'market_plural' => 'markets',
        'cta' => 'View details',
    ];
$fcc_forever_discovery_copy = \Altum\Language::$code === 'hr'
    ? [
        'eyebrow' => 'Brži odabir',
        'title' => 'Filtrirajte proizvode prema onome što vam je važno',
        'subtitle' => 'Suzi izbor po dostupnosti, tržištima i nazivu proizvoda kako biste brže otvorili pravi vodič.',
        'filters' => [
            'all' => 'Svi proizvodi',
            'ready' => 'Dostupno za narudžbu',
            'multi_market' => 'Više tržišta',
        ],
        'search_label' => 'Pretraži proizvode',
        'search_placeholder' => 'Upišite naziv ili dio opisa',
        'sort_label' => 'Poredaj',
        'sort_options' => [
            'recommended' => 'Preporučeno',
            'popular' => 'Najpopularnije',
            'title_asc' => 'Naziv A-Z',
        ],
        'reset' => 'Poništi filtere',
        'count_prefix' => 'Prikazano proizvoda:',
        'empty_title' => 'Nema proizvoda za odabrane filtere',
        'empty_text' => 'Pokušajte ukloniti neki filter ili upisati širi pojam pretrage.',
    ]
    : [
        'eyebrow' => 'Faster discovery',
        'title' => 'Filter products by what matters most to you',
        'subtitle' => 'Narrow the list by ordering availability, markets, and product name so you can open the right guide faster.',
        'filters' => [
            'all' => 'All products',
            'ready' => 'Ready to order',
            'multi_market' => 'Multiple markets',
        ],
        'search_label' => 'Search products',
        'search_placeholder' => 'Type a name or part of the description',
        'sort_label' => 'Sort by',
        'sort_options' => [
            'recommended' => 'Recommended',
            'popular' => 'Most popular',
            'title_asc' => 'Title A-Z',
        ],
        'reset' => 'Reset filters',
        'count_prefix' => 'Visible products:',
        'empty_title' => 'No products match the current filters',
        'empty_text' => 'Try removing a filter or using a broader search term.',
    ];

$fcc_normalize_card_text = static function($text, int $limit = 165): string {
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
$fcc_get_product_card_state = static function($blog_post) use ($fcc_product_card_copy, $fcc_normalize_card_text, $fcc_forever_market_display_chip, $fcc_is_forever_products_category): array {
    $webshop_links = json_decode($blog_post->webshop_links ?? '{}', true) ?: [];
    $market_count = count(array_filter($webshop_links, static function($url) {
        return !empty($url);
    }));
    $shop_ready = $market_count > 0;
    $chips = [];

    if($shop_ready) {
        $chips[] = $fcc_product_card_copy['available_badge'];
    }

    if($market_count > 0) {
        $chips[] = $fcc_is_forever_products_category
            ? $fcc_forever_market_display_chip
            : ($market_count . ' ' . ($market_count === 1 ? $fcc_product_card_copy['market_singular'] : $fcc_product_card_copy['market_plural']));
    }

    return [
        'shop_ready' => $shop_ready,
        'market_count' => $market_count,
        'eyebrow' => $shop_ready || !empty($blog_post->sku) ? $fcc_product_card_copy['eyebrow_product'] : $fcc_product_card_copy['eyebrow_guide'],
        'chips' => $chips,
        'description' => $fcc_normalize_card_text($blog_post->description ?? ''),
        'cta' => $fcc_product_card_copy['cta'],
    ];
};

$fcc_forever_hub_copy = \Altum\Language::$code === 'hr'
    ? [
        'eyebrow' => 'Forever preporuke',
        'title' => 'Pronađite proizvod koji najbolje odgovara vašim potrebama',
        'subtitle' => 'Pregledajte proizvode po namjeni, usporedite ključne prednosti i otvorite detaljan vodič prije nego nastavite prema narudžbi.',
        'note' => 'Na svakoj stranici možete brzo vidjeti čemu je proizvod namijenjen, što ga izdvaja i koji vam sljedeći korak najviše pomaže pri odabiru.',
        'stats' => [
            'product_count' => 'Vodiča za proizvode',
            'shop_ready_count' => 'Dostupno za narudžbu',
            'market_count' => 'Dostupna tržišta',
        ],
        'guide_title' => 'Kako lakše odabrati',
        'guide_items' => [
            ['title' => 'Krenite od svoje potrebe', 'text' => 'Odaberite ono što vam je trenutno najvažnije, poput njege kože, probave, energije ili svakodnevne rutine.'],
            ['title' => 'Otvorite vodič koji vas zanima', 'text' => 'Na svakoj stranici dobit ćete sažetak proizvoda, ključne informacije i jasan pregled onoga što je važno prije odluke.'],
            ['title' => 'Usporedite i nastavite kada ste spremni', 'text' => 'Povezani prijedlozi pomažu vam suziti izbor, a kada odlučite, jednostavno nastavljate prema narudžbi.'],
        ],
        'featured_title' => 'Najtraženiji Forever proizvodi',
        'seo_title' => 'Kako lakše pronaći odgovarajući Forever proizvod',
        'seo_paragraphs' => [
            'Ova rubrika okuplja Forever proizvode na jednom mjestu kako biste brže prepoznali što bi moglo odgovarati vašoj rutini, cilju ili svakodnevnim navikama.',
            'Umjesto dugog pretraživanja, svaki vodič sažima najvažnije: čemu je proizvod namijenjen, što ga izdvaja i kada ga korisnici najčešće biraju kao sljedeći korak.',
            'Ako uspoređujete više opcija, jasni opisi i povezani prijedlozi olakšavaju odluku i pomažu vam da nastavite s više sigurnosti.',
        ],
        'faq_title' => 'Česta pitanja prije odabira',
        'faq_items' => [
            ['q' => 'Kako najbrže suziti izbor?', 'a' => 'Najlakše je krenuti od cilja koji vam je trenutno najvažniji. Otvorite nekoliko vodiča iz iste teme i usporedite kratke opise prije odluke.'],
            ['q' => 'Što mogu vidjeti na svakoj stranici proizvoda?', 'a' => 'Na svakoj stranici dobit ćete sažetak namjene, ključne prednosti, osnovne informacije i prijedloge povezanih proizvoda koji vam mogu pomoći pri odabiru.'],
            ['q' => 'Što kada pronađem proizvod koji mi odgovara?', 'a' => 'Kada se odlučite, jednim klikom nastavljate prema službenoj stranici za narudžbu.'],
        ],
    ]
    : [
        'eyebrow' => 'Forever picks',
        'title' => 'Find the product that best matches your needs',
        'subtitle' => 'Browse products by intent, compare key benefits, and open a detailed guide before you continue toward ordering.',
        'note' => 'Each page helps visitors quickly understand what the product is for, what makes it stand out, and which next step is the most useful before deciding.',
        'stats' => [
            'product_count' => 'Product guides',
            'shop_ready_count' => 'Ready to order',
            'market_count' => 'Available markets',
        ],
        'guide_title' => 'How to choose faster',
        'guide_items' => [
            ['title' => 'Start with your need', 'text' => 'Choose what matters most right now, such as skin care, digestion, energy, or a daily wellness routine.'],
            ['title' => 'Open the guide that interests you', 'text' => 'Each page gives you a product overview, key details, and a clear summary of what matters before you decide.'],
            ['title' => 'Compare and continue when ready', 'text' => 'Related suggestions help narrow the choice, and when the visitor is ready, the next ordering step feels simple and natural.'],
        ],
        'featured_title' => 'Most searched Forever products',
        'seo_title' => 'How to find the right Forever product more easily',
        'seo_paragraphs' => [
            'This section brings Forever products together in one place so visitors can more quickly see what may fit their routine, goals, or everyday habits.',
            'Instead of long searching, each guide summarizes the essentials: what the product is for, what makes it different, and when people most often choose it as a next step.',
            'If someone is comparing several options, clear descriptions and related suggestions make the decision easier and help them continue with more confidence.',
        ],
        'faq_title' => 'Questions visitors often have before choosing',
        'faq_items' => [
            ['q' => 'How can visitors narrow the choice faster?', 'a' => 'The easiest way is to start with the goal that matters most right now. Open a few guides in the same topic and compare the short summaries before deciding.'],
            ['q' => 'What can visitors see on each product page?', 'a' => 'Each page includes a clear purpose summary, key benefits, essential details, and related product suggestions that support the decision.'],
            ['q' => 'What happens after someone finds the right product?', 'a' => 'When visitors are ready, they can continue with one click to the official ordering page.'],
        ],
    ];

if(!empty($fcc_category_shop_context['hero_badge'])) {
    $fcc_forever_hub_copy['eyebrow'] = $fcc_category_shop_context['hero_badge'];
}

if(!empty($fcc_category_shop_context['hero_subtitle'])) {
    $fcc_forever_hub_copy['subtitle'] = $fcc_category_shop_context['hero_subtitle'];
}

if(!empty($fcc_category_shop_context['hero_note'])) {
    $fcc_forever_hub_copy['note'] = $fcc_category_shop_context['hero_note'];
}

if(!empty($fcc_category_shop_context['guide_title'])) {
    $fcc_forever_hub_copy['guide_title'] = $fcc_category_shop_context['guide_title'];
}

if(!empty($fcc_category_shop_context['guide_items'])) {
    $fcc_forever_hub_copy['guide_items'] = array_values($fcc_category_shop_context['guide_items']);
}

if(!empty($fcc_category_shop_context['featured_title'])) {
    $fcc_forever_hub_copy['featured_title'] = $fcc_category_shop_context['featured_title'];
}

if($fcc_is_forever_products_category) {
    $fcc_legacy_featured_titles = [
        'Istaknuti odabiri',
        'Featured picks',
        'Najtraženiji Forever vodiči',
        'Most visited Forever guides',
    ];

    if(in_array(trim((string) $fcc_forever_hub_copy['featured_title']), $fcc_legacy_featured_titles, true)) {
        $fcc_forever_hub_copy['featured_title'] = \Altum\Language::$code === 'hr'
            ? 'Najtraženiji Forever proizvodi'
            : 'Most searched Forever products';
    }
}

if(!empty($fcc_category_shop_context['seo_title'])) {
    $fcc_forever_hub_copy['seo_title'] = $fcc_category_shop_context['seo_title'];
}

if(!empty($fcc_category_shop_context['seo_paragraphs'])) {
    $fcc_forever_hub_copy['seo_paragraphs'] = array_values($fcc_category_shop_context['seo_paragraphs']);
}

if(!empty($fcc_category_shop_context['faq_title'])) {
    $fcc_forever_hub_copy['faq_title'] = $fcc_category_shop_context['faq_title'];
}

if(!empty($fcc_category_shop_context['faq_items'])) {
    $fcc_forever_hub_copy['faq_items'] = array_values($fcc_category_shop_context['faq_items']);
}

foreach([
    'product_count' => 'product_count_label',
    'shop_ready_count' => 'shop_ready_count_label',
    'market_count' => 'market_count_label',
] as $stats_key => $context_key) {
    if(!empty($fcc_category_shop_context[$context_key])) {
        $fcc_forever_hub_copy['stats'][$stats_key] = $fcc_category_shop_context[$context_key];
    }
}

foreach([
    'eyebrow' => 'discovery_eyebrow',
    'title' => 'discovery_title',
    'subtitle' => 'discovery_subtitle',
] as $copy_key => $context_key) {
    if(!empty($fcc_category_shop_context[$context_key])) {
        $fcc_forever_discovery_copy[$copy_key] = $fcc_category_shop_context[$context_key];
    }
}

$fcc_featured_product_posts = [];
$fcc_featured_post_urls = array_values($fcc_category_shop_context['featured_post_urls'] ?? []);

if($fcc_featured_post_urls) {
    $fcc_posts_by_url = [];

    foreach($fcc_category_posts as $blog_post) {
        $fcc_posts_by_url[(string) ($blog_post->url ?? '')] = $blog_post;
    }

    foreach($fcc_featured_post_urls as $featured_url) {
        if(isset($fcc_posts_by_url[$featured_url])) {
            $fcc_featured_product_posts[] = $fcc_posts_by_url[$featured_url];
        }
    }
}

if(!$fcc_featured_product_posts) {
    $fcc_featured_product_posts = array_slice($fcc_category_posts, 0, 3);
}

$fcc_featured_product_posts = array_slice(array_values($fcc_featured_product_posts), 0, 3);

$fcc_custom_filter_chips = array_values(array_filter(array_map(static function($item, $index) {
    $label = trim((string) ($item['label'] ?? ''));
    $terms = trim((string) ($item['terms'] ?? ''));

    if($label === '' || $terms === '') {
        return null;
    }

    $normalized_terms = array_values(array_filter(array_map(static function($term) {
        $term = trim((string) $term);
        return $term !== '' ? $term : null;
    }, preg_split('/[,;]+/', $terms) ?: [])));

    if(!$normalized_terms) {
        return null;
    }

    return [
        'key' => 'custom_' . ($index + 1),
        'label' => $label,
        'terms' => $normalized_terms,
    ];
}, $fcc_category_shop_context['filter_chips'] ?? [], array_keys($fcc_category_shop_context['filter_chips'] ?? []))));

$fcc_product_filters = array_merge([
    ['key' => 'all', 'label' => $fcc_forever_discovery_copy['filters']['all'], 'terms' => []],
], $fcc_custom_filter_chips, [
    ['key' => 'ready', 'label' => $fcc_forever_discovery_copy['filters']['ready'], 'terms' => []],
    ['key' => 'multi_market', 'label' => $fcc_forever_discovery_copy['filters']['multi_market'], 'terms' => []],
]);
/* /Custom code: FC-2026-02-26 */
?>

<?php if($fcc_category_alternate_urls): ?>
    <?php ob_start() ?>
    <?php foreach($fcc_category_alternate_urls as $hreflang => $href): ?>
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

<!-- Custom code: FC-2026-02-26: FCC premium blog category layout -->
<div class="fcc-blog-page-bg">
<div class="container <?= settings()->content->blog_columns == 1 ? 'col-lg-8' : null ?> fcc-blog-wrap">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('blog') ?>"><?= l('blog.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= $data->blog_posts_category->title ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="row mt-4 fcc-blog-grid">
        <?php /* Custom code: FC-2026-03-09: mobile-first sidebar ordering */ ?>
        <div class="<?= settings()->content->blog_columns == 1 ? 'col-12 mb-5' : 'col-12 col-lg-8 mb-lg-0' ?> fcc-blog-main-col order-2 order-lg-1">
        <?php /* /Custom code: FC-2026-03-09 */ ?>
            <section class="card mb-4 fcc-glass-card fcc-blog-home-hero fcc-category-hero fcc-referral-tour-target" id="fcc_referral_tour_category_hero">
                <div class="card-body">
                    <span class="fcc-hero-badge"><?= $fcc_is_forever_products_category ? $fcc_forever_hub_copy['eyebrow'] : $fcc_category_hero_badge ?></span>
                    <h1 class="fcc-home-hero-title mt-3 mb-2"><?= $data->blog_posts_category->title ?></h1>
                    <?php if(!empty($data->blog_posts_category->description)): ?>
                        <p class="fcc-home-hero-subtitle mb-4"><?= $data->blog_posts_category->description ?></p>
                    <?php elseif(!$fcc_is_forever_products_category && $fcc_category_public_subtitle !== ''): ?>
                        <p class="fcc-home-hero-subtitle mb-4"><?= e($fcc_category_public_subtitle) ?></p>
                    <?php endif ?>
                    <?php if($fcc_is_forever_products_category): ?>
                        <p class="fcc-category-shop-subtitle mb-4"><?= $fcc_forever_hub_copy['subtitle'] ?></p>
                    <?php endif ?>
                    <a href="<?= $fcc_blog_home_url ?>" class="btn fcc-home-hero-btn-secondary">
                        <?= $fcc_category_back_to_blog ?>
                    </a>
                </div>
            </section>

            <?php if(!empty($fcc_category_direct_children)): ?>
                <section class="card mb-4 fcc-glass-card fcc-topics-card">
                    <div class="card-body">
                        <h2 class="fcc-section-title mb-3"><?= e($fcc_category_public_bundle['subcategories_title'] ?? (\Altum\Language::$code === 'hr' ? 'Povezane podkategorije' : 'Related categories')) ?></h2>
                        <div class="fcc-topic-chips">
                            <?php foreach($fcc_category_direct_children as $child_category): ?>
                                <a href="<?= SITE_URL . ($child_category->language ? \Altum\Language::$active_languages[$child_category->language] . '/' : null) . 'blog/category/' . $child_category->url ?>" class="fcc-topic-chip">
                                    <?= e($child_category->title) ?>
                                </a>
                            <?php endforeach ?>
                        </div>
                    </div>
                </section>
            <?php endif ?>

            <?php if($fcc_is_forever_products_category): ?>
                <section class="card mb-4 fcc-glass-card fcc-category-shop-intro">
                    <div class="card-body">
                        <div class="fcc-category-shop-stats">
                            <div class="fcc-category-shop-stat">
                                <span class="fcc-category-shop-stat-value"><?= nr($fcc_category_stats['product_count']) ?></span>
                                <span class="fcc-category-shop-stat-label"><?= $fcc_forever_hub_copy['stats']['product_count'] ?></span>
                            </div>

                            <div class="fcc-category-shop-stat">
                                <span class="fcc-category-shop-stat-value"><?= nr($fcc_category_stats['shop_ready_count']) ?></span>
                                <span class="fcc-category-shop-stat-label"><?= $fcc_forever_hub_copy['stats']['shop_ready_count'] ?></span>
                            </div>

                            <div class="fcc-category-shop-stat">
                                <span class="fcc-category-shop-stat-value"><?= e($fcc_forever_market_display_value ?: (string) nr($fcc_category_stats['market_count'])) ?></span>
                                <span class="fcc-category-shop-stat-label"><?= $fcc_forever_hub_copy['stats']['market_count'] ?></span>
                            </div>
                        </div>

                        <div class="fcc-category-shop-note">
                            <?= $fcc_forever_hub_copy['note'] ?>
                        </div>
                    </div>
                </section>

                <section class="card mb-4 fcc-glass-card fcc-category-shop-guide">
                    <div class="card-body">
                        <h2 class="fcc-section-title mb-3"><?= $fcc_forever_hub_copy['guide_title'] ?></h2>
                        <div class="row">
                            <?php foreach($fcc_forever_hub_copy['guide_items'] as $guide_item): ?>
                                <div class="col-12 col-md-4 mb-3 mb-md-0">
                                    <article class="fcc-category-guide-card h-100">
                                        <h3 class="fcc-category-guide-card-title"><?= $guide_item['title'] ?></h3>
                                        <p class="fcc-category-guide-card-text mb-0"><?= $guide_item['text'] ?></p>
                                    </article>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </section>

                <?php if(!empty($fcc_featured_product_posts)): ?>
                    <section class="mb-4">
                        <h2 class="fcc-section-title mb-3"><?= $fcc_forever_hub_copy['featured_title'] ?></h2>
                        <div class="row">
                            <?php foreach($fcc_featured_product_posts as $featured_product_post): ?>
                                <?php $fcc_featured_card_state = $fcc_get_product_card_state($featured_product_post); ?>
                                <div class="col-12 col-md-4 mb-3">
                                    <article
                                        class="fcc-category-featured-product h-100"
                                        data-fcc-product-card="1"
                                        data-fcc-shop-ready="<?= $fcc_featured_card_state['shop_ready'] ? '1' : '0' ?>"
                                        data-fcc-market-count="<?= (int) $fcc_featured_card_state['market_count'] ?>"
                                        data-fcc-title="<?= e($featured_product_post->title) ?>"
                                    >
                                        <?php if(!empty($featured_product_post->image)): ?>
                                            <a href="<?= SITE_URL . ($featured_product_post->language ? \Altum\Language::$active_languages[$featured_product_post->language] . '/' : null) . 'blog/' . $featured_product_post->url ?>" class="d-block" data-fcc-blog-event="category_featured_click" data-fcc-blog-post-id="<?= (int) $featured_product_post->blog_post_id ?>" data-fcc-blog-component="featured_image" data-fcc-blog-label="<?= e($featured_product_post->title) ?>">
                                                <img src="<?= \Altum\Uploads::get_full_url('blog') . $featured_product_post->image ?>" class="fcc-category-featured-product-image" alt="<?= e($featured_product_post->image_description ?? $featured_product_post->title) ?>" loading="lazy" decoding="async" />
                                            </a>
                                        <?php endif ?>

                                        <div class="fcc-category-featured-product-body">
                                            <span class="fcc-product-card-eyebrow"><?= $fcc_featured_card_state['eyebrow'] ?></span>
                                            <a href="<?= SITE_URL . ($featured_product_post->language ? \Altum\Language::$active_languages[$featured_product_post->language] . '/' : null) . 'blog/' . $featured_product_post->url ?>" class="fcc-category-featured-product-link" data-fcc-blog-event="category_featured_click" data-fcc-blog-post-id="<?= (int) $featured_product_post->blog_post_id ?>" data-fcc-blog-component="featured_title" data-fcc-blog-label="<?= e($featured_product_post->title) ?>">
                                                <?= $featured_product_post->title ?>
                                            </a>

                                            <?php if(!empty($fcc_featured_card_state['chips'])): ?>
                                                <div class="fcc-product-card-meta">
                                                    <?php foreach($fcc_featured_card_state['chips'] as $chip): ?>
                                                        <span class="fcc-product-card-chip"><?= e($chip) ?></span>
                                                    <?php endforeach ?>
                                                </div>
                                            <?php endif ?>

                                            <p class="fcc-category-featured-product-text mb-0"><?= e($fcc_featured_card_state['description']) ?></p>

                                            <a href="<?= SITE_URL . ($featured_product_post->language ? \Altum\Language::$active_languages[$featured_product_post->language] . '/' : null) . 'blog/' . $featured_product_post->url ?>" class="fcc-product-card-cta" data-fcc-blog-event="category_featured_click" data-fcc-blog-post-id="<?= (int) $featured_product_post->blog_post_id ?>" data-fcc-blog-component="featured_cta" data-fcc-blog-label="<?= e($featured_product_post->title) ?>">
                                                <?= $fcc_featured_card_state['cta'] ?>
                                                <i class="fas fa-fw fa-arrow-right ml-1"></i>
                                            </a>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </section>
                <?php endif ?>
            <?php endif ?>

            <?php if (!empty($data->blog_posts)): ?>
                <?php if($fcc_is_forever_products_category): ?>
                    <section class="card mb-4 fcc-glass-card fcc-product-discovery-card" data-fcc-product-discovery>
                        <div class="card-body">
                            <div class="fcc-product-discovery-header">
                                <span class="fcc-hero-badge"><?= $fcc_forever_discovery_copy['eyebrow'] ?></span>
                                <h2 class="fcc-section-title mt-3 mb-2"><?= $fcc_forever_discovery_copy['title'] ?></h2>
                                <p class="fcc-category-shop-note mb-0"><?= $fcc_forever_discovery_copy['subtitle'] ?></p>
                            </div>

                            <div class="fcc-product-discovery-toolbar">
                                <div class="fcc-product-filter-group" role="group" aria-label="<?= e($fcc_forever_discovery_copy['title']) ?>">
                                    <?php foreach($fcc_product_filters as $filter_index => $filter_item): ?>
                                        <button
                                            type="button"
                                            class="fcc-product-filter-chip <?= $filter_index === 0 ? 'is-active' : null ?>"
                                            data-fcc-filter-button
                                            data-filter="<?= e($filter_item['key']) ?>"
                                            data-filter-label="<?= e($filter_item['label']) ?>"
                                            data-filter-terms="<?= e(implode(',', $filter_item['terms'] ?? [])) ?>"
                                            aria-pressed="<?= $filter_index === 0 ? 'true' : 'false' ?>"
                                        ><?= e($filter_item['label']) ?></button>
                                    <?php endforeach ?>
                                </div>

                                <div class="fcc-product-discovery-controls">
                                    <div class="fcc-product-search-field">
                                        <label for="fcc-product-search" class="fcc-product-control-label"><?= $fcc_forever_discovery_copy['search_label'] ?></label>
                                        <input type="search" id="fcc-product-search" class="form-control" data-fcc-product-search placeholder="<?= e($fcc_forever_discovery_copy['search_placeholder']) ?>" autocomplete="off" />
                                    </div>

                                    <div class="fcc-product-sort-field">
                                        <label for="fcc-product-sort" class="fcc-product-control-label"><?= $fcc_forever_discovery_copy['sort_label'] ?></label>
                                        <select id="fcc-product-sort" class="form-control" data-fcc-product-sort>
                                            <?php foreach($fcc_forever_discovery_copy['sort_options'] as $sort_value => $sort_label): ?>
                                                <option value="<?= e($sort_value) ?>"><?= $sort_label ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="fcc-product-discovery-footer">
                                <div class="fcc-product-results-count" data-fcc-product-count>
                                    <?= $fcc_forever_discovery_copy['count_prefix'] ?> <?= nr(count($data->blog_posts ?? [])) ?>
                                </div>

                                <button type="button" class="btn btn-sm btn-gray-100" data-fcc-product-reset><?= $fcc_forever_discovery_copy['reset'] ?></button>
                            </div>
                        </div>
                    </section>
                <?php endif ?>

                <section id="fcc-category-posts" class="mb-2">
                    <h3 class="fcc-section-title mb-3"><?= $fcc_category_all_posts ?></h3>
                    <div class="row fcc-latest-grid" <?= $fcc_is_forever_products_category ? 'data-fcc-product-grid' : null ?>>
                        <?php foreach(array_values($data->blog_posts) as $blog_post_index => $blog_post): ?>
                            <?php $fcc_listing_card_state = $fcc_is_forever_products_category ? $fcc_get_product_card_state($blog_post) : null; ?>
                            <div
                                class="col-12 col-md-6 mb-3"
                                <?php if($fcc_is_forever_products_category): ?>
                                    data-fcc-product-item="1"
                                    data-fcc-order="<?= (int) $blog_post_index ?>"
                                    data-fcc-views="<?= (int) ($blog_post->total_views ?? 0) ?>"
                                    data-fcc-title="<?= e($blog_post->title) ?>"
                                    data-fcc-description="<?= e($fcc_listing_card_state['description']) ?>"
                                    data-fcc-search-aliases="<?= e((string) ($blog_post->search_aliases ?? '')) ?>"
                                    data-fcc-keywords="<?= e((string) ($blog_post->keywords ?? '')) ?>"
                                    data-fcc-sku="<?= e((string) ($blog_post->sku ?? '')) ?>"
                                    data-fcc-shop-ready="<?= $fcc_listing_card_state['shop_ready'] ? '1' : '0' ?>"
                                    data-fcc-market-count="<?= (int) $fcc_listing_card_state['market_count'] ?>"
                                <?php endif ?>
                            >
                                <article
                                    class="card h-100 fcc-glass-card fcc-post-card fcc-post-card-compact <?= $fcc_is_forever_products_category ? 'fcc-product-listing-card' : null ?> <?= $blog_post_index === 0 ? 'fcc-referral-tour-target' : null ?>"
                                    <?= $blog_post_index === 0 ? 'id="fcc_referral_tour_first_post"' : null ?>
                                    <?php if($fcc_is_forever_products_category): ?>
                                        data-fcc-product-card="1"
                                        data-fcc-shop-ready="<?= $fcc_listing_card_state['shop_ready'] ? '1' : '0' ?>"
                                        data-fcc-market-count="<?= (int) $fcc_listing_card_state['market_count'] ?>"
                                        data-fcc-title="<?= e($blog_post->title) ?>"
                                    <?php endif ?>
                                >
                                    <div class="card-body fcc-post-card-body">
                                        <?php if($blog_post->image): ?>
                                            <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" data-fcc-blog-event="category_card_click" data-fcc-blog-post-id="<?= (int) $blog_post->blog_post_id ?>" data-fcc-blog-component="listing_image" data-fcc-blog-label="<?= e($blog_post->title) ?>">
                                                <img src="<?= \Altum\Uploads::get_full_url('blog') . $blog_post->image ?>" class="blog-post-image img-fluid w-100 rounded mb-3 fcc-post-thumb" alt="<?= $blog_post->image_description ?>" loading="lazy" decoding="async" />
                                            </a>
                                        <?php endif ?>

                                        <?php if($fcc_is_forever_products_category): ?>
                                            <span class="fcc-product-card-eyebrow"><?= $fcc_listing_card_state['eyebrow'] ?></span>
                                        <?php endif ?>

                                        <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" class="text-decoration-none" data-fcc-blog-event="category_card_click" data-fcc-blog-post-id="<?= (int) $blog_post->blog_post_id ?>" data-fcc-blog-component="listing_title" data-fcc-blog-label="<?= e($blog_post->title) ?>">
                                            <h2 class="h5 mb-1 fcc-post-title"><?= $blog_post->title ?></h2>
                                        </a>

                                        <p class="small text-muted fcc-post-meta">
                                            <span data-toggle="tooltip" title="<?= sprintf(l('global.last_datetime_tooltip'), \Altum\Date::get($blog_post->last_datetime, 2)) ?>">
                                                <?= sprintf(l('global.datetime_tooltip'), \Altum\Date::get($blog_post->datetime, 2)) ?>
                                            </span>

                                            <?php if(settings()->content->blog_views_is_enabled): ?>
                                                <span> • <?= sprintf(l('blog.total_views'), nr($blog_post->total_views)) ?></span>
                                            <?php endif ?>
                                        </p>

                                        <?php if($fcc_is_forever_products_category && !empty($fcc_listing_card_state['chips'])): ?>
                                            <div class="fcc-product-card-meta">
                                                <?php foreach($fcc_listing_card_state['chips'] as $chip): ?>
                                                    <span class="fcc-product-card-chip"><?= e($chip) ?></span>
                                                <?php endforeach ?>
                                            </div>
                                        <?php endif ?>

                                        <p class="m-0 fcc-post-desc <?= $fcc_is_forever_products_category ? 'fcc-product-card-desc' : null ?>"><?= $fcc_is_forever_products_category ? e($fcc_listing_card_state['description']) : $blog_post->description ?></p>

                                        <?php if($fcc_is_forever_products_category): ?>
                                            <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" class="fcc-product-card-cta" data-fcc-blog-event="category_card_click" data-fcc-blog-post-id="<?= (int) $blog_post->blog_post_id ?>" data-fcc-blog-component="listing_cta" data-fcc-blog-label="<?= e($blog_post->title) ?>">
                                                <?= $fcc_listing_card_state['cta'] ?>
                                                <i class="fas fa-fw fa-arrow-right ml-1"></i>
                                            </a>
                                        <?php endif ?>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach ?>
                    </div>

                    <?php if($fcc_is_forever_products_category): ?>
                        <div class="card mt-3 fcc-glass-card fcc-product-empty-state d-none" data-fcc-product-empty>
                            <div class="card-body">
                                <h4 class="fcc-section-title mb-2"><?= $fcc_forever_discovery_copy['empty_title'] ?></h4>
                                <p class="fcc-category-shop-note mb-0"><?= $fcc_forever_discovery_copy['empty_text'] ?></p>
                            </div>
                        </div>
                    <?php endif ?>
                </section>

                <?php if(!empty($data->pagination)): ?>
                    <div class="mt-3 fcc-pagination-wrap"><?= $data->pagination ?></div>
                <?php endif ?>

                <?php if($fcc_is_forever_products_category): ?>
                    <section class="card mt-4 fcc-glass-card fcc-category-seo-copy">
                        <div class="card-body">
                            <h2 class="fcc-category-seo-title"><?= $fcc_forever_hub_copy['seo_title'] ?></h2>

                            <?php foreach($fcc_forever_hub_copy['seo_paragraphs'] as $seo_paragraph): ?>
                                <p class="fcc-category-seo-text"><?= $seo_paragraph ?></p>
                            <?php endforeach ?>
                        </div>
                    </section>

                    <section class="card mt-4 fcc-glass-card fcc-category-faq-card">
                        <div class="card-body">
                            <h2 class="fcc-category-seo-title mb-3"><?= $fcc_forever_hub_copy['faq_title'] ?></h2>

                            <div class="accordion" id="fcc-category-faq">
                                <?php foreach($fcc_forever_hub_copy['faq_items'] as $faq_index => $faq_item): ?>
                                    <div class="fcc-category-faq-item">
                                        <button
                                            class="fcc-category-faq-question"
                                            type="button"
                                            data-toggle="collapse"
                                            data-target="#fcc-category-faq-answer-<?= $faq_index ?>"
                                            aria-expanded="<?= $faq_index === 0 ? 'true' : 'false' ?>"
                                            aria-controls="fcc-category-faq-answer-<?= $faq_index ?>"
                                        >
                                            <?= $faq_item['q'] ?>
                                        </button>

                                        <div id="fcc-category-faq-answer-<?= $faq_index ?>" class="collapse <?= $faq_index === 0 ? 'show' : null ?>" data-parent="#fcc-category-faq">
                                            <div class="fcc-category-faq-answer">
                                                <?= $faq_item['a'] ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </section>
                <?php endif ?>
            <?php else: ?>
                <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                    'filters_get' => $data->filters->get ?? [],
                    'name' => 'blog',
                    'has_secondary_text' => true,
                ]); ?>
            <?php endif ?>
        </div>

        <?php if(settings()->content->blog_popular_widget_is_enabled || settings()->content->blog_categories_widget_is_enabled || settings()->content->blog_search_widget_is_enabled): ?>
            <?php /* Custom code: FC-2026-03-09: mobile-first sidebar ordering */ ?>
            <div class="<?= settings()->content->blog_columns == 1 ? 'col-12' : 'col-12 col-lg-4' ?> fcc-blog-sidebar-col order-1 order-lg-2">
            <?php /* /Custom code: FC-2026-03-09 */ ?>
                <div class="fcc-sidebar-sticky" id="fcc-popular-widget">
                    <?php if(settings()->content->blog_search_widget_is_enabled): ?>
                        <div class="card mb-4 fcc-glass-card fcc-widget-card fcc-referral-tour-target" id="fcc_referral_tour_category_search" style="position: relative; z-index: 30; overflow: visible;">
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

<div class="fcc-referral-tour-backdrop" id="fcc_referral_tour_backdrop">
    <div class="fcc-referral-tour-backdrop-segment" data-segment="top"></div>
    <div class="fcc-referral-tour-backdrop-segment" data-segment="left"></div>
    <div class="fcc-referral-tour-backdrop-segment" data-segment="right"></div>
    <div class="fcc-referral-tour-backdrop-segment" data-segment="bottom"></div>
</div>
<div class="fcc-referral-tour-popover" id="fcc_referral_tour_popover" aria-live="polite">
    <div class="fcc-referral-tour-progress" id="fcc_referral_tour_progress">1 / 3</div>
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

<?php ob_start() ?>
<script>
(function() {
    'use strict';

    const storageKey = 'fcc_blog_referral_tour_v1';
    const isForeverProductsCategory = <?= json_encode($fcc_is_forever_products_category) ?>;
    const firstPostUrl = <?= json_encode($fcc_first_blog_post_url) ?>;
    const nextLabel = <?= json_encode(l('dashboard.tutorials.blog_referral.next_open_article')) ?>;
    const defaultNextLabel = <?= json_encode(l('dashboard.tour.next')) ?>;

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
            selector: '#fcc_referral_tour_category_hero',
            title: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_1_title')) ?>,
            text: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_1_text')) ?>,
        },
        {
            selector: '#fcc_referral_tour_category_search',
            title: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_2_title')) ?>,
            text: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_2_text')) ?>,
        },
        {
            selector: '#fcc_referral_tour_first_post',
            title: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_3_title')) ?>,
            text: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_3_text')) ?>,
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

    const setState = (state) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(state));
        } catch(error) {
            /* Ignore storage failures. */
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
            endTour();
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

        document.getElementById('fcc_referral_tour_title').textContent = step.title;
        document.getElementById('fcc_referral_tour_text').textContent = step.text;
        document.getElementById('fcc_referral_tour_progress').textContent = `${index + 1} / ${steps.length}`;

        const prevButton = document.getElementById('fcc_referral_tour_prev');
        const nextButton = document.getElementById('fcc_referral_tour_next');

        if(prevButton) {
            prevButton.style.display = index === 0 ? 'none' : 'inline-flex';
        }

        if(nextButton) {
            nextButton.textContent = index === steps.length - 1 && firstPostUrl ? nextLabel : defaultNextLabel;
        }

        backdrop.classList.add('is-visible');
        popover.classList.add('is-visible');

        setTimeout(placePopover, 140);
    };

    const initTutorial = () => {
        if(!isForeverProductsCategory) {
            return;
        }

        const state = getState();
        if(!state || state.flow !== 'blog_referral' || state.stage !== 'category') {
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
                    if(firstPostUrl) {
                        setState({
                            flow: 'blog_referral',
                            stage: 'article',
                            started_at: state.started_at ?? Date.now()
                        });

                        window.location.href = firstPostUrl;
                        return;
                    }

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

<?php if($fcc_is_forever_products_category): ?>
    <?php ob_start() ?>
    <script>
    (function() {
        const discovery = document.querySelector('[data-fcc-product-discovery]');
        const grid = document.querySelector('[data-fcc-product-grid]');
        const trackingUrl = `${url}blog/interactions_ajax`;
        const categoryId = <?= (int) ($data->blog_posts_category->blog_posts_category_id ?? 0) ?>;
        const pageUrl = <?= json_encode($data->blog_posts_category_url ?? '') ?> || window.location.href;

        if(!discovery || !grid) {
            return;
        }

        const items = Array.from(grid.querySelectorAll('[data-fcc-product-item]'));
        const filterButtons = Array.from(discovery.querySelectorAll('[data-fcc-filter-button]'));
        const searchInput = discovery.querySelector('[data-fcc-product-search]');
        const sortSelect = discovery.querySelector('[data-fcc-product-sort]');
        const resetButton = discovery.querySelector('[data-fcc-product-reset]');
        const countNode = discovery.querySelector('[data-fcc-product-count]');
        const emptyState = document.querySelector('[data-fcc-product-empty]');
        const countPrefix = <?= json_encode($fcc_forever_discovery_copy['count_prefix']) ?>;
        const filterConfig = new Map(filterButtons.map((button) => [
            button.dataset.filter || 'all',
            {
                label: button.dataset.filterLabel || button.textContent.trim(),
                terms: (button.dataset.filterTerms || '').split(',').map((term) => term.trim().toLocaleLowerCase()).filter(Boolean),
            }
        ]));

        if(!items.length) {
            return;
        }

        let activeFilter = 'all';
        let activeSort = sortSelect ? sortSelect.value : 'recommended';
        let activeQuery = '';
        let lastTrackedQuery = '';

        const normalizeValue = (value) => (value || '').toString().toLocaleLowerCase();
        const normalizeSearchValue = (value) => {
            const normalized = normalizeValue(value);
            const deaccented = typeof normalized.normalize === 'function'
                ? normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                : normalized;

            return deaccented.replace(/[^a-z0-9]+/g, ' ').replace(/\s+/g, ' ').trim();
        };
        const getSearchHaystack = (item) => normalizeSearchValue([
            item.dataset.fccTitle || '',
            item.dataset.fccDescription || '',
            item.dataset.fccSearchAliases || '',
            item.dataset.fccKeywords || '',
            item.dataset.fccSku || ''
        ].join(' '));
        const getVisibleItems = () => items.filter((item) => matchesFilter(item) && matchesSearch(item));

        const trackEvent = (eventType, payload = {}) => {
            if(!window.global_token || !eventType || !categoryId) {
                return;
            }

            const form = new FormData();
            form.set('global_token', global_token);
            form.set('event_type', eventType);
            form.set('page_type', 'category');
            form.set('blog_posts_category_id', categoryId.toString());
            form.set('page_url', pageUrl || window.location.href);

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

        const matchesFilter = (item) => {
            const shopReady = item.dataset.fccShopReady === '1';
            const marketCount = parseInt(item.dataset.fccMarketCount || '0', 10);
            const haystack = `${item.dataset.fccTitle || ''} ${item.dataset.fccDescription || ''} ${item.dataset.fccSearchAliases || ''} ${item.dataset.fccKeywords || ''} ${item.dataset.fccSku || ''}`.toLocaleLowerCase();
            const currentFilter = filterConfig.get(activeFilter);

            if(activeFilter === 'ready') {
                return shopReady;
            }

            if(activeFilter === 'multi_market') {
                return marketCount >= 2;
            }

            if(currentFilter && currentFilter.terms.length) {
                return currentFilter.terms.some((term) => haystack.includes(term));
            }

            return true;
        };

        const matchesSearch = (item) => {
            if(!activeQuery) {
                return true;
            }

            const haystack = getSearchHaystack(item);
            return activeQuery.split(' ').every((term) => haystack.includes(term));
        };

        const sortItems = (elements) => {
            const sorted = [...elements];

            sorted.sort((a, b) => {
                if(activeSort === 'popular') {
                    return parseInt(b.dataset.fccViews || '0', 10) - parseInt(a.dataset.fccViews || '0', 10);
                }

                if(activeSort === 'title_asc') {
                    return (a.dataset.fccTitle || '').localeCompare(b.dataset.fccTitle || '', undefined, {sensitivity: 'base'});
                }

                return parseInt(a.dataset.fccOrder || '0', 10) - parseInt(b.dataset.fccOrder || '0', 10);
            });

            sorted.forEach((item) => grid.appendChild(item));
        };

        const render = () => {
            const visibleItems = getVisibleItems();

            sortItems(visibleItems.length ? visibleItems : items);

            items.forEach((item) => {
                item.classList.toggle('d-none', !visibleItems.includes(item));
            });

            if(countNode) {
                countNode.textContent = `${countPrefix} ${visibleItems.length}`;
            }

            if(emptyState) {
                emptyState.classList.toggle('d-none', visibleItems.length > 0);
            }
        };

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.filter || 'all';

                filterButtons.forEach((otherButton) => {
                    const isActive = otherButton === button;
                    otherButton.classList.toggle('is-active', isActive);
                    otherButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                render();
                const currentFilter = filterConfig.get(activeFilter);
                trackEvent('category_filter', {
                    component: 'discovery_filters',
                    label: currentFilter?.label || activeFilter,
                    event_data: {
                        filter_key: activeFilter,
                        results_visible: getVisibleItems().length,
                    }
                });
            });
        });

        if(searchInput) {
            searchInput.addEventListener('input', () => {
                activeQuery = normalizeSearchValue(searchInput.value.trim());
                render();

                if(activeQuery.length >= 2 && activeQuery !== lastTrackedQuery) {
                    lastTrackedQuery = activeQuery;
                    trackEvent('category_search', {
                        component: 'discovery_search',
                        label: searchInput.value.trim(),
                        event_data: {
                            query_length: activeQuery.length,
                            results_visible: getVisibleItems().length,
                        }
                    });
                }
            });
        }

        if(sortSelect) {
            sortSelect.addEventListener('change', () => {
                activeSort = sortSelect.value || 'recommended';
                render();
                trackEvent('category_sort', {
                    component: 'discovery_sort',
                    label: sortSelect.options[sortSelect.selectedIndex]?.text || activeSort,
                    event_data: {
                        sort_key: activeSort,
                        results_visible: getVisibleItems().length,
                    }
                });
            });
        }

        if(resetButton) {
            resetButton.addEventListener('click', () => {
                activeFilter = 'all';
                activeSort = 'recommended';
                activeQuery = '';

                filterButtons.forEach((button) => {
                    const isActive = (button.dataset.filter || 'all') === 'all';
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                if(searchInput) {
                    searchInput.value = '';
                }

                if(sortSelect) {
                    sortSelect.value = 'recommended';
                }

                render();
                lastTrackedQuery = '';
                trackEvent('category_reset', {
                    component: 'discovery_reset',
                    label: <?= json_encode($fcc_forever_discovery_copy['reset']) ?>,
                    event_data: {
                        results_visible: getVisibleItems().length,
                    }
                });
            });
        }

        document.querySelectorAll('[data-fcc-blog-event]').forEach((element) => {
            element.addEventListener('click', () => {
                trackEvent(element.dataset.fccBlogEvent || '', {
                    blog_post_id: parseInt(element.dataset.fccBlogPostId || '0', 10),
                    component: element.dataset.fccBlogComponent || '',
                    label: element.dataset.fccBlogLabel || '',
                    event_data: {
                        active_filter: activeFilter,
                        active_sort: activeSort,
                        active_query: activeQuery,
                    }
                });
            });
        });

        render();
    })();
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
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
                "name": "<?= $data->blog_posts_category->title ?>",
                "item": "<?= $data->blog_posts_category_url ?? (SITE_URL . ($data->blog_posts_category->language ? \Altum\Language::$active_languages[$data->blog_posts_category->language] . '/' : null) . 'blog/category/' . $data->blog_posts_category->url) ?>"
            }
        ]
    }
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php ob_start() ?>
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": <?= json_encode($data->blog_posts_category->title) ?>,
        "description": <?= json_encode($fcc_category_meta_description ?: ($fcc_forever_hub_copy['subtitle'] ?? ($data->blog_posts_category->description ?: $data->blog_posts_category->title))) ?>,
        "url": <?= json_encode($data->blog_posts_category_url ?? (SITE_URL . ($data->blog_posts_category->language ? \Altum\Language::$active_languages[$data->blog_posts_category->language] . '/' : null) . 'blog/category/' . $data->blog_posts_category->url)) ?>,
        "hasPart": {
            "@type": "ItemList",
            "itemListElement": [
                <?php foreach(array_values($data->blog_posts ?? []) as $index => $blog_post): ?>
                {
                    "@type": "ListItem",
                    "position": <?= $index + 1 ?>,
                    "url": <?= json_encode(SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url) ?>,
                    "name": <?= json_encode($blog_post->title) ?>
                }<?= $index + 1 < count($data->blog_posts ?? []) ? ',' : null ?>
                <?php endforeach ?>
            ]
        }
    }
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php if($fcc_is_forever_products_category): ?>
    <?php ob_start() ?>
    <script type="application/ld+json">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static function($item) {
                return [
                    '@type' => 'Question',
                    'name' => $item['q'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['a'],
                    ],
                ];
            }, $fcc_forever_hub_copy['faq_items']),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>
