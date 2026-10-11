<?php

defined('ALTUMCODE') || die();

/*
 * Sales-use plans derived only from the current reviewed Croatian label and
 * reviewed Croatian offer size. Runtime verifies every usage fragment and an
 * available matching package before displaying the text. A changed label or
 * package therefore fails closed instead of preserving a stale "monthly dose".
 *
 * These are purchase calculations, not treatment protocols. They are shown
 * only for a routine, eligible product recommendation.
 */
return [
    'schema_version' => 'fcc-product-advisor-label-plans-v1',
    'language_code' => 'hr',
    'market_code' => 'hr',
    'plans' => [
        'forever-aloe-vera-gel' => [
            'usage_contains' => ['3 porcije po 40 ml'],
            'offer_package_labels' => ['4 x 1 l'],
            'text_hr' => 'Deklariranih 120 ml dnevno iznosi 3,6 l za 30 dana, pa je praktična kupovna količina 4 boce od 1 l; trenutačno mora postojati provjerena dostupna ponuda 4 × 1 l.',
        ],
        'aloe-berry-nectar' => [
            'usage_contains' => ['3 porcije po 40 ml'],
            'offer_package_labels' => ['4 x 1 l'],
            'text_hr' => 'Deklariranih 120 ml dnevno iznosi 3,6 l za 30 dana, pa je praktična kupovna količina 4 boce od 1 l; trenutačno mora postojati provjerena dostupna ponuda 4 × 1 l.',
        ],
        'forever-aloe-mango' => [
            'usage_contains' => ['3 porcije po 40 ml'],
            'offer_package_labels' => ['4 x 1 l'],
            'text_hr' => 'Deklariranih 120 ml dnevno iznosi 3,6 l za 30 dana, pa je praktična kupovna količina 4 boce od 1 l; trenutačno mora postojati provjerena dostupna ponuda 4 × 1 l.',
        ],
        'forever-aloe-peaches' => [
            'usage_contains' => ['3 porcije po 40 ml'],
            'offer_package_labels' => ['4 x 1 l'],
            'text_hr' => 'Deklariranih 120 ml dnevno iznosi 3,6 l za 30 dana, pa je praktična kupovna količina 4 boce od 1 l; trenutačno mora postojati provjerena dostupna ponuda 4 × 1 l.',
        ],
        'forever-absorbent-c' => [
            'usage_contains' => ['1 tabletu 3 puta dnevno'],
            'offer_package_labels' => ['100 tableta'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 90 tableta; jedno pakiranje od 100 tableta pokriva 30 dana.',
        ],
        'absorbent-d' => [
            'usage_contains' => ['jednom dnevno', 'jednu tabletu'],
            'offer_package_labels' => ['60 tableta'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 30 tableta; jedno pakiranje od 60 tableta pokriva 60 dana.',
        ],
        'forever-active-pro-b' => [
            'usage_contains' => ['1 kapsulu dnevno'],
            'offer_package_labels' => ['30 kapsula (340 mg)'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 30 kapsula; jedno pakiranje s 30 kapsula pokriva 30 dana.',
        ],
        'forever-arctic-sea' => [
            'usage_contains' => ['dvije kapsule tri puta na dan'],
            'offer_package_labels' => ['120 kapsula'],
            'text_hr' => 'Deklaracija daje ukupno 6 kapsula dnevno: jedno pakiranje od 120 kapsula traje 20 dana, a za punih 30 dana potrebna su 2 pakiranja (ostaje 60 kapsula).',
        ],
        'forever-argi' => [
            'usage_contains' => ['jednu vrećicu', 'jednom dnevno'],
            'offer_package_labels' => ['300 g (30 x 10 g)'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 30 vrećica; jedno pakiranje 30 × 10 g pokriva 30 dana.',
        ],
        'forever-b12-plus' => [
            'usage_contains' => ['1-2 tablete na dan'],
            'offer_package_labels' => ['60 tableta'],
            'text_hr' => 'Deklarirani raspon za 30 dana je 30–60 tableta; jedno pakiranje od 60 tableta pokriva cijeli deklarirani raspon.',
        ],
        'forever-calcium' => [
            'usage_contains' => ['3-4 tablete na dan'],
            'offer_package_labels' => ['90 tableta'],
            'text_hr' => 'Pakiranje od 90 tableta traje 30 dana pri 3 tablete dnevno ili oko 22 dana pri 4 tablete dnevno. Savjetnik neće sam odabrati viši ili niži kraj deklariranog raspona.',
        ],
        'forever-daily' => [
            'usage_contains' => ['1 tabletu 2 puta dnevno'],
            'offer_package_labels' => ['60 tableta'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 60 tableta; jedno pakiranje od 60 tableta pokriva 30 dana.',
        ],
        'forever-garlic-thyme' => [
            'usage_contains' => ['3x1 kapsula na dan'],
            'offer_package_labels' => ['100 mekih kapsula'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 90 kapsula; jedno pakiranje od 100 kapsula pokriva 30 dana.',
        ],
        'forever-immublend' => [
            'usage_contains' => ['2 tablete dnevno'],
            'offer_package_labels' => ['60 tableta'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 60 tableta; jedno pakiranje od 60 tableta pokriva 30 dana.',
        ],
        'forever-immune-gummy' => [
            'usage_contains' => ['dva puta dnevno', 'jedan gumeni bombon'],
            'offer_package_labels' => ['60 gumenih bombona (210 g)'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 60 gumenih bombona; jedno pakiranje od 60 pokriva 30 dana.',
        ],
        'forever-infinite-firming-complex' => [
            'usage_contains' => ['2x1 tableta na dan'],
            'offer_package_labels' => ['60 tableta (29 g)'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 60 tableta; jedno pakiranje od 60 tableta pokriva 30 dana.',
        ],
        'marine-collagen' => [
            'usage_contains' => ['jednu vrećicu dnevno'],
            'offer_package_labels' => ['30 vrećica (450 ml)'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 30 vrećica; jedno pakiranje s 30 vrećica pokriva 30 dana.',
        ],
        'forever-nature-min' => [
            'usage_contains' => ['4 tablete dnevno'],
            'offer_package_labels' => ['180 tableta'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 120 tableta; jedno pakiranje od 180 tableta traje 45 dana.',
        ],
        'forever-nutra-q10' => [
            'usage_contains' => ['1 vrećice', 'dnevno'],
            'offer_package_labels' => ['30 vrećica x 3,5 g'],
            'text_hr' => 'Za 30 dana prema deklaraciji treba 30 vrećica; jedno pakiranje s 30 vrećica pokriva 30 dana.',
        ],
        'forever-plant-protein' => [
            'usage_contains' => ['1 mjericu praha', '26 g'],
            'offer_package_labels' => ['390 g'],
            'text_hr' => 'Pakiranje od 390 g sadrži 15 deklariranih mjerica po 26 g. Deklaracija u ovom zapisu ne određuje dnevnu učestalost, pa savjetnik neće proizvoljno pretpostaviti jednu mjericu dnevno.',
        ],
    ],
];
