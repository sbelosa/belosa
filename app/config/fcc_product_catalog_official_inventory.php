<?php

defined('ALTUMCODE') || die();

/*
 * Reviewed current HR inventory delta against the 90 legacy product families.
 * Offer hashes cover the normalized official category-card evidence captured in
 * /tmp/fcc_hr_current_inventory_raw.json on 2026-08-25. Bundle entities are
 * lookup identities only and never prove compatibility between their members.
 */

$accessedAt = '2026-08-25T20:46:00+00:00';
$snapshotHash = '907a8e551ce6374d42b20a2636e3c05f139ec5cbcbc68ecd55d76945c60ed1f5';

/*
 * Locked, normalized materialization of every reconciled current family and
 * every category offer. Runtime validation recomputes the 96/113/92/4 totals
 * from this payload; the declared header values are never trusted on their own.
 * Prices are intentionally excluded because they are not advisor identity.
 */
$inventoryFamilies = json_decode(<<<'JSON'
[{"product_key":"a-touch-of-forever","family_type":"business_bundle","available":true,"default_item_code":"001","listed_categories":["weight_management","combo_paks"],"offer_count":1,"offers":[{"item_code":"","name":"A Touch Of Forever","package_label":"","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/weight-management/touch-of-forever","listed_categories":["weight_management","combo_paks"]}]},{"product_key":"absorbent-d","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"672","name":"Forever Absorbent-D","package_label":"60 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/672-ABSORBENT-D","listed_categories":["nutritional"]}]},{"product_key":"aloe-activator","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"612","name":"Aloe Activator","package_label":"130 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/612-ALOE-ACTIVATOR-2018","listed_categories":["skin_care"]}]},{"product_key":"aloe-berry-nectar","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["drinks","combo_paks"],"offer_count":4,"offers":[{"item_code":"83441","name":"4PACK BERRY NECTAR 10% PROMO","package_label":"4 x 1 l","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/83441-4PACK-BERRY-NECTAR-10%25-PROMO","listed_categories":["drinks","combo_paks"]},{"item_code":"8344","name":"4PACK ALOE BERRY NECTAR","package_label":"4 x 1 l","availability":"sold_out","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/8344-4PACK-ALOE-BERRY-NECTAR","listed_categories":["drinks","combo_paks"]},{"item_code":"834","name":"Forever Aloe Berry Nectar","package_label":"1 l","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/834-ALOE-BERRY-NECTAR-PET","listed_categories":["drinks"]},{"item_code":"73512","name":"Forever Aloe Berry Nectar (12 x 330 ml)","package_label":"12 x 330 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/73512-ALOE-BERRY-330ML-PACK-OF-12","listed_categories":["drinks"]}]},{"product_key":"aloe-blossom-herbal-tea","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["drinks"],"offer_count":1,"offers":[{"item_code":"200","name":"Aloe Blossom Herbal Tea","package_label":"25 vrećica","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/200-ALOE-BLOSSOM-HERBAL-TEA","listed_categories":["drinks"]}]},{"product_key":"aloe-body-wash","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care","personal_care"],"offer_count":1,"offers":[{"item_code":"646","name":"Aloe Body Wash","package_label":"236 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/646-ALOE-BODY-WASH","listed_categories":["skin_care","personal_care"]}]},{"product_key":"aloe-ever-shield","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["personal_care"],"offer_count":1,"offers":[{"item_code":"067","name":"Aloe Ever-Shield","package_label":"92,1 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/067-ALOE-EVER-SHIELD-DEODORANT","listed_categories":["personal_care"]}]},{"product_key":"aloe-liquid-soap","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["personal_care"],"offer_count":1,"offers":[{"item_code":"633","name":"Aloe Liquid Soap","package_label":"473 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/633-ALOE-LIQUID-SOAP","listed_categories":["personal_care"]}]},{"product_key":"aloe-mixed-four-pack","family_type":"assortment_offer","available":true,"default_item_code":"","listed_categories":["drinks","combo_paks"],"offer_count":2,"offers":[{"item_code":"82341","name":"4PACK ALOE COMBO 10% PROMO","package_label":"","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/82341-4PACK-ALOE-COMBO-10%25-PROMO","listed_categories":["drinks","combo_paks"]},{"item_code":"8234","name":"4PACK ALOE COMBO","package_label":"4 x 1 l","availability":"sold_out","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/8234-FANCY-4-PACK","listed_categories":["drinks","combo_paks"]}]},{"product_key":"aloe-msm-gel","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"205","name":"Aloe MSM Gel","package_label":"118 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/205-FOREVER-ALOE-MSM-GEL","listed_categories":["skin_care"]}]},{"product_key":"aloe-sunscreen","family_type":"product_family","available":false,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"617","name":"Aloe Sunscreen","package_label":"118 ml","availability":"sold_out","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/617-SUNSCREEN-LOTION-MIT-FREE","listed_categories":["skin_care"]}]},{"product_key":"argi-aloe-mini-tripack","family_type":"bundle_offer","available":true,"default_item_code":"","listed_categories":["combo_paks"],"offer_count":1,"offers":[{"item_code":"71633","name":"ARGI+ and Aloe Mini Tripack","package_label":"3 x 330 ml, 3 x 10 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/combo-paks/71633-ARGI%2B-AND-ALOE-MINI-TRIPACK","listed_categories":["combo_paks"]}]},{"product_key":"c9-forever-living-products","family_type":"product_family","available":true,"default_item_code":"475","listed_categories":["weight_management","combo_paks"],"offer_count":1,"offers":[{"item_code":"","name":"C9","package_label":"","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/weight-management/C9","listed_categories":["weight_management","combo_paks"]}]},{"product_key":"deep-moisturizing-cream","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"651","name":"Deep Moisturizing Cream","package_label":"71 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/651-DEEP-MOISTURIZING-CREAM","listed_categories":["skin_care"]}]},{"product_key":"fab-energy-drink","family_type":"standalone_product","available":true,"default_item_code":"","listed_categories":["drinks"],"offer_count":1,"offers":[{"item_code":"821","name":"FAB ENERGY DRINK","package_label":"250 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/821-FAB-ENERGY-DRINK","listed_categories":["drinks"]}]},{"product_key":"fab-forever-active-boost","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["drinks"],"offer_count":1,"offers":[{"item_code":"822","name":"FAB X Forever Active Boost","package_label":"250 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/822-FAB-X-ENERGY-DRINK","listed_categories":["drinks"]}]},{"product_key":"forever-absorbent-c","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"048","name":"Forever Absorbent-C","package_label":"100 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/048-ABSORBENT-C","listed_categories":["nutritional"]}]},{"product_key":"forever-active-ha","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"264","name":"Forever Active HA","package_label":"60 kapsula","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/264-FOREVER-ACTIVE-HA","listed_categories":["nutritional"]}]},{"product_key":"forever-active-pro-b","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"610","name":"Forever Active Pro-B","package_label":"30 kapsula (340 mg)","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/610-FOREVER-ACTIVE-PRO-B","listed_categories":["nutritional"]}]},{"product_key":"forever-aloe-avocado-soap","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["personal_care"],"offer_count":1,"offers":[{"item_code":"284","name":"Avocado Soap","package_label":"142 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/284-AVOCADO-SOAP","listed_categories":["personal_care"]}]},{"product_key":"forever-aloe-body-lotion","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"647","name":"Aloe Body Lotion","package_label":"236 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/647-ALOE-BODY-LOTION","listed_categories":["skin_care"]}]},{"product_key":"forever-aloe-cooling-lotion","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"564","name":"Aloe Cooling Lotion","package_label":"118 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/564-ALOE-COOLING-LOTION","listed_categories":["skin_care"]}]},{"product_key":"forever-aloe-first-spray","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care","personal_care"],"offer_count":1,"offers":[{"item_code":"040","name":"Aloe First Spray","package_label":"473 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/040-ALOE-FIRST-SPRAY","listed_categories":["skin_care","personal_care"]}]},{"product_key":"forever-aloe-jojoba-conditioner","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["personal_care"],"offer_count":1,"offers":[{"item_code":"641","name":"Aloe Jojoba Conditioner","package_label":"296 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/641-ALOE-JOJOBA-CONDITIONER","listed_categories":["personal_care"]}]},{"product_key":"forever-aloe-jojoba-shampoo","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["personal_care"],"offer_count":1,"offers":[{"item_code":"640","name":"Aloe-Jojoba Shampoo","package_label":"260 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/640-ALOE-JOJOBA-SHAMPOO","listed_categories":["personal_care"]}]},{"product_key":"forever-aloe-lips","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care","personal_care"],"offer_count":1,"offers":[{"item_code":"022","name":"Forever Aloe Lips","package_label":"4,6 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/022-ALOE-LIPS","listed_categories":["skin_care","personal_care"]}]},{"product_key":"forever-aloe-mango","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["drinks","combo_paks"],"offer_count":3,"offers":[{"item_code":"83641","name":"4PACK ALOE MANGO 10% PROMO","package_label":"4 x 1 l","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/83641-4PACK-ALOE-MANGO-10%25-PROMO","listed_categories":["drinks","combo_paks"]},{"item_code":"8364","name":"4PACK ALOE MANGO","package_label":"4 x 1 l","availability":"sold_out","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/8364-4PACK-ALOE-MANGO","listed_categories":["drinks","combo_paks"]},{"item_code":"836","name":"Forever Aloe Mango","package_label":"1 l","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/836-FOREVER-ALOE-MANGO-PET","listed_categories":["drinks"]}]},{"product_key":"forever-aloe-moisturizing-lotion","family_type":"product_family","available":false,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"063","name":"Aloe Moisturizing Lotion","package_label":"118 ml","availability":"sold_out","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/063-ALOE-MOISTURIZING-LOTIONTUBE","listed_categories":["skin_care"]}]},{"product_key":"forever-aloe-peaches","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["drinks","combo_paks"],"offer_count":4,"offers":[{"item_code":"87741","name":"4PACK ALOE PEACHES 10% PROMO","package_label":"4 x 1 l","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/87741-4PACK-ALOE-PEACHES-10%25-PROMO","listed_categories":["drinks","combo_paks"]},{"item_code":"8774","name":"4PACK ALOE PEACHES","package_label":"4 x 1 l","availability":"sold_out","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/8774-4PACK-ALOE-PEACHES","listed_categories":["drinks","combo_paks"]},{"item_code":"77812","name":"Forever Aloe Peaches (12 x 330 ml)","package_label":"12 x 330 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/77812-ALOE-PEACHES-330ML-PACK-OF-12","listed_categories":["drinks"]},{"item_code":"877","name":"Forever Aloe Peaches","package_label":"1 l","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/877-ALOE-PEACHES-PET","listed_categories":["drinks"]}]},{"product_key":"forever-aloe-propolis-creme","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"051","name":"Aloe Propolis Creme","package_label":"113 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/051-ALOE-PROPOLIS-CREME","listed_categories":["skin_care"]}]},{"product_key":"forever-aloe-scrub","family_type":"product_family","available":false,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"238","name":"Forever Aloe Scrub","package_label":"99 g","availability":"sold_out","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/238-ALOE-SCRUB","listed_categories":["skin_care"]}]},{"product_key":"forever-aloe-vera-gel","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["drinks","combo_paks"],"offer_count":4,"offers":[{"item_code":"81541","name":"4PACK ALOE VERA GEL 10% PROMO","package_label":"4 x 1 l","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/81541-4PACK-ALOE-VERA-GEL-10%25-PROMO","listed_categories":["drinks","combo_paks"]},{"item_code":"8154","name":"4PACK ALOE VERA GEL","package_label":"4 x 1 l","availability":"sold_out","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/8154-4PACK-ALOE-VERA-GEL","listed_categories":["drinks","combo_paks"]},{"item_code":"815","name":"Forever Aloe Vera Gel","package_label":"1 l","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/815-ALOE-VERA-GEL-PET","listed_categories":["drinks"]},{"item_code":"71612","name":"Forever Aloe Vera Gel (12 x 330 ml)","package_label":"12 x 330 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/71612-ALOE-VERA-GEL-330ML-PACK-OF-12","listed_categories":["drinks"]}]},{"product_key":"forever-aloe-vera-gelly","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care","personal_care"],"offer_count":1,"offers":[{"item_code":"061","name":"Aloe Vera Gelly","package_label":"118 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/061-ALOE-VERA-GELLY-TUBE","listed_categories":["skin_care","personal_care"]}]},{"product_key":"forever-aloeturm","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":2,"offers":[{"item_code":"676","name":"Forever AloeTurm","package_label":"10 pastila","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/676-FOREVER-ALOETURM","listed_categories":["nutritional"]},{"item_code":"67610","name":"Forever Aloe Turm 10-pack","package_label":"100 pastila","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/67610-FOREVER-TURM-10-PACK","listed_categories":["nutritional"]}]},{"product_key":"forever-alofa","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["personal_care"],"offer_count":1,"offers":[{"item_code":"643","name":"Forever Alofa","package_label":"30 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/643-ALOFA-WOMENS-PERFUME","listed_categories":["personal_care"]}]},{"product_key":"forever-arctic-sea","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"376","name":"Forever Arctic Sea","package_label":"120 kapsula","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/376-FOREVER-ARCTIC-SEA-NEW","listed_categories":["nutritional"]}]},{"product_key":"forever-argi","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"473","name":"Forever ARGI+","package_label":"300 g (30 x 10 g)","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/473-FOREVER-ARGI-STICK-PACK","listed_categories":["nutritional"]}]},{"product_key":"forever-awakening-eye-cream","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"561","name":"Awakening Eye Cream","package_label":"17,5 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/561-AWAKENING-EYE-CREAM","listed_categories":["skin_care"]}]},{"product_key":"forever-b12-plus","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"188","name":"Forever B12 Plus","package_label":"60 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/188-FOREVER-B-12-PLUS","listed_categories":["nutritional"]}]},{"product_key":"forever-bakuchiol","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"687","name":"Forever Bakuchiol","package_label":"30 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/687-FOREVER-BAKUCHIOL","listed_categories":["skin_care"]}]},{"product_key":"forever-bee-honey","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["bee_products"],"offer_count":1,"offers":[{"item_code":"207","name":"Forever Bee Honey","package_label":"500 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/bee-products/207-FOREVER-BEE-HONEY","listed_categories":["bee_products"]}]},{"product_key":"forever-bee-pollen","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["bee_products"],"offer_count":1,"offers":[{"item_code":"026","name":"Forever Bee Pollen","package_label":"100 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/bee-products/026-FOREVER-BEE-POLLEN","listed_categories":["bee_products"]}]},{"product_key":"forever-bee-propolis","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["bee_products"],"offer_count":1,"offers":[{"item_code":"027","name":"Forever Bee Propolis","package_label":"60 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/bee-products/027-FOREVER-BEE-PROPOLIS","listed_categories":["bee_products"]}]},{"product_key":"forever-bio-cellulose-mask","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"664","name":"Aloe Bio-cellulose Mask","package_label":"3 maske (x 25g)","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/664-FOREVER-BIO-CELLULOSE-MASK-X3","listed_categories":["skin_care"]}]},{"product_key":"forever-bright","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["personal_care"],"offer_count":1,"offers":[{"item_code":"028","name":"Forever Bright Toothgel","package_label":"130 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/028-FOREVER-BRIGHT-TOOTHGEL","listed_categories":["personal_care"]}]},{"product_key":"forever-calcium","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"206","name":"Forever Calcium","package_label":"90 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/206-FOREVER-CALCIUM","listed_categories":["nutritional"]}]},{"product_key":"forever-daily","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"439","name":"Forever Daily","package_label":"60 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/439-FOREVER-DAILY","listed_categories":["nutritional"]}]},{"product_key":"forever-dx4","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["combo_paks"],"offer_count":1,"offers":[{"item_code":"659","name":"DX4","package_label":"","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/combo-paks/659-DX4","listed_categories":["combo_paks"]}]},{"product_key":"forever-f15","family_type":"product_family","available":true,"default_item_code":"528","listed_categories":["weight_management","combo_paks"],"offer_count":1,"offers":[{"item_code":"","name":"F15","package_label":"","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/weight-management/F15","listed_categories":["weight_management","combo_paks"]}]},{"product_key":"forever-fastbreak","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional","weight_management"],"offer_count":1,"offers":[{"item_code":"520","name":"Forever FastBreak","package_label":"56 grama","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/520-FAST-BREAK-BARS","listed_categories":["nutritional","weight_management"]}]},{"product_key":"forever-fiber","family_type":"product_family","available":false,"default_item_code":"","listed_categories":["nutritional","weight_management"],"offer_count":1,"offers":[{"item_code":"464","name":"Forever Fiber","package_label":"30 vrećica","availability":"sold_out","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/464-FOREVER-FIBER-30-COUNT","listed_categories":["nutritional","weight_management"]}]},{"product_key":"forever-fields-of-greens","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"068","name":"Forever Fields Of Greens","package_label":"80 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/068-FIELDS-OF-GREENS","listed_categories":["nutritional"]}]},{"product_key":"forever-focus","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"622","name":"Forever Focus","package_label":"120 kapsula","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/622-FOCUS-EU","listed_categories":["nutritional"]}]},{"product_key":"forever-freedom","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["drinks","combo_paks"],"offer_count":3,"offers":[{"item_code":"89641","name":"4PACK FOREVER FREEDOM 10%PROMO","package_label":"4 x 1 l","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/89641-4PACK-FOREVER-FREEDOM-10%25PROMO","listed_categories":["drinks","combo_paks"]},{"item_code":"896","name":"FOREVER FREEDOM PET","package_label":"1L","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/896-FOREVER-FREEDOM-PET","listed_categories":["drinks"]},{"item_code":"8964","name":"4PACK FOREVER FREEDOM","package_label":"4 x 1 l","availability":"sold_out","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/8964-4PACK-FOREVER-FREEDOM","listed_categories":["drinks","combo_paks"]}]},{"product_key":"forever-garcinia-plus","family_type":"standalone_product","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"071","name":"Forever Garcinia Plus","package_label":"70 mekih kapsula","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/071-FOREVER-GARCINIA-PLUS","listed_categories":["nutritional"]}]},{"product_key":"forever-garlic-thyme","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"065","name":"Forever Garlic-Thyme","package_label":"100 mekih kapsula","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/065-GARLIC-THYME","listed_categories":["nutritional"]}]},{"product_key":"forever-hand-sanitizer","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["personal_care"],"offer_count":1,"offers":[{"item_code":"318","name":"Forever Hand Sanitizer","package_label":"59 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/318-HAND-SANITIZER","listed_categories":["personal_care"]}]},{"product_key":"forever-hydrating-serum","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"618","name":"Hydrating Serum","package_label":"50 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/618-HYDRATING-SERUM","listed_categories":["skin_care"]}]},{"product_key":"forever-immublend","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"355","name":"Forever ImmuBlend","package_label":"60 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/355-FOREVER-IMMUBLEND","listed_categories":["nutritional"]}]},{"product_key":"forever-immune-gummy","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"566","name":"Forever Immune Gummy","package_label":"60 gumenih bombona (210 g)","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/566-FOREVER-IMMUNE-GUMMY","listed_categories":["nutritional"]}]},{"product_key":"forever-infinite-firming-complex","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"556","name":"Infinite by Forever Firming Complex","package_label":"60 tableta (29 g)","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/556-INFINITE-FIRMING-COMPLEX","listed_categories":["skin_care"]}]},{"product_key":"forever-ivision","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"624","name":"Forever iVision","package_label":"60 kapsula (41 g)","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/624-FOREVER-IVISION","listed_categories":["nutritional"]}]},{"product_key":"forever-kids","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"354","name":"Forever Kids","package_label":"120 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/354-FOREVER-KIDS","listed_categories":["nutritional"]}]},{"product_key":"forever-lean","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional","weight_management"],"offer_count":1,"offers":[{"item_code":"289","name":"Forever Lean","package_label":"120 kapsula","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/289-FOREVER-LEAN","listed_categories":["nutritional","weight_management"]}]},{"product_key":"forever-logic","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care","personal_care"],"offer_count":1,"offers":[{"item_code":"673","name":"Logic by Forever Skincare Kit","package_label":"Set","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/673-LOGIC-KIT","listed_categories":["skin_care","personal_care"]}]},{"product_key":"forever-logic-aloe-gel-cleanser","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"667","name":"Logic Aloe Gel Cleanser","package_label":"88 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/667-LOGIC-ALOE-GEL-CLEANSER","listed_categories":["skin_care"]}]},{"product_key":"forever-logic-aloe-soothing-gel-moisture","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"669","name":"Logic Soothing Gel Moisturizer","package_label":"59 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/669-LOGIC-ALOE-SOOTHING-GEL-MOISTU","listed_categories":["skin_care"]}]},{"product_key":"forever-logic-gel-balancing-essence","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"668","name":"Logic Balancing Aloe Essence","package_label":"73 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/668-LOGIC-GEL-BALANCING-ESSENCE","listed_categories":["skin_care"]}]},{"product_key":"forever-lycium-plus","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"072","name":"Forever Lycium Plus","package_label":"100 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/072-LYCIUM-PLUS","listed_categories":["nutritional"]}]},{"product_key":"forever-malosi","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["personal_care"],"offer_count":1,"offers":[{"item_code":"644","name":"Forever Malosi","package_label":"30 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/644-MALOSI-MENS-COLOGNE","listed_categories":["personal_care"]}]},{"product_key":"forever-move","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"551","name":"Forever Move","package_label":"90 kapsula","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/551-FOREVER-MOVE","listed_categories":["nutritional"]}]},{"product_key":"forever-multi-maca","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"215","name":"Forever Multi-Maca","package_label":"60 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/215-FOREVER-MULTI-MACA","listed_categories":["nutritional"]}]},{"product_key":"forever-nature-min","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"037","name":"Forever Nature-Min","package_label":"180 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/037-NATURE-MIN","listed_categories":["nutritional"]}]},{"product_key":"forever-nourishing-hair-oil","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"642","name":"Nourishing Hair Oil","package_label":"30 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/642-NOURISHING-HAIR-OIL","listed_categories":["skin_care"]}]},{"product_key":"forever-nutra-q10","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"312","name":"Forever Cardiohealth","package_label":"30 vrećica x 3,5 g = 105 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/312-FOREVER-CARDIOHEALTH","listed_categories":["nutritional"]}]},{"product_key":"forever-plant-protein","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional","weight_management"],"offer_count":1,"offers":[{"item_code":"656","name":"Forever Plant Protein","package_label":"390 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/656-FOREVER-PLANT-PROTEIN-POUCH","listed_categories":["nutritional","weight_management"]}]},{"product_key":"forever-refining-gel-mask","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"677","name":"Refining Gel Mask","package_label":"59 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/677-REFINING-GEL-MASK","listed_categories":["skin_care"]}]},{"product_key":"forever-renewing-skin-creme","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"650","name":"Renewing Skin Creme","package_label":"56.7g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/650-RENEWING-SKIN-CREME","listed_categories":["skin_care"]}]},{"product_key":"forever-replenishing-skin-oil","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"653","name":"Replenishing Skin Oil","package_label":"30 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/653-REPLENISHING-SKIN-OIL","listed_categories":["skin_care"]}]},{"product_key":"forever-royal-jelly","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["bee_products"],"offer_count":1,"offers":[{"item_code":"036","name":"Forever Royal Jelly","package_label":"60 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/bee-products/036-ROYAL-JELLY","listed_categories":["bee_products"]}]},{"product_key":"forever-sensatiable","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"665","name":"Forever Sensatiable","package_label":"64 tablete za žvakanje (111 g)","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/665-FOREVER-SENSATIABLE","listed_categories":["nutritional"]}]},{"product_key":"forever-smoothing-exfoliator","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"559","name":"Smoothing Exfoliator","package_label":"60 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/559-SMOOTHING-EXFOLIATOR","listed_categories":["skin_care"]}]},{"product_key":"forever-therm","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional","weight_management"],"offer_count":1,"offers":[{"item_code":"463","name":"Forever Therm","package_label":"60 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/463-FOREVER-THERM","listed_categories":["nutritional","weight_management"]}]},{"product_key":"forever-ultra","family_type":"product_family","available":true,"default_item_code":"470","listed_categories":["nutritional","weight_management"],"offer_count":3,"offers":[{"item_code":"","name":"Forever Lite Ultra","package_label":"375 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/forever-lite","listed_categories":["nutritional","weight_management"]},{"item_code":"471","name":"Forever Lite Ultra - Chocolate","package_label":"375 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/471-FOREVER-LITE-U-CHOCOLA-15-SERV","listed_categories":["nutritional"]},{"item_code":"470","name":"Forever Lite Ultra - Vanilla","package_label":"375 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/470-FOREVER-LITE-U-VANILLA-15-SERV","listed_categories":["nutritional"]}]},{"product_key":"forever-vitamin-c","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"686","name":"Forever Vitamin C","package_label":"30 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/686-FOREVER-VITAMIN-C","listed_categories":["skin_care"]}]},{"product_key":"gentleman-s-pride-aftershave","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["personal_care"],"offer_count":1,"offers":[{"item_code":"070","name":"Gentleman's Pride Aftershave","package_label":"118 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/070-GENTLEMANS-PRIDE-AFTER-SHAVE","listed_categories":["personal_care"]}]},{"product_key":"infinite-by-forever-advanced-skincare","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"553","name":"Infinite By Forever Advanced Skincare","package_label":"","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/553-INFINITE-SKIN-CARE-KIT","listed_categories":["skin_care"]}]},{"product_key":"infinite-by-forever-firming-serum","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"555","name":"Infinite By Forever Firming Serum","package_label":"30 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/555-INFINITE-FIRMING-SERUM","listed_categories":["skin_care"]}]},{"product_key":"infinite-by-forever-restoring-creme","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"558","name":"Infinite by Forever Restoring Creme","package_label":"48,2 g","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/558-INFINITE-RESTORING-CREAM","listed_categories":["skin_care"]}]},{"product_key":"infinite-hydrating-cleanser","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"554","name":"Infinite Hydrating Cleanser","package_label":"118 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/554-INFINITE-HYDRATING-CLEANSER","listed_categories":["skin_care"]}]},{"product_key":"marine-collagen","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["drinks","nutritional"],"offer_count":1,"offers":[{"item_code":"613","name":"Forever Marine Collagen","package_label":"30 vrećica (450 ml)","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/drinks/613-FOREVER-MARINE-COLLAGEN","listed_categories":["drinks","nutritional"]}]},{"product_key":"protecting-day-lotion","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"645","name":"Protecting Day Lotion","package_label":"60 ml","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/645-PROTECTING-DAY-LOTION","listed_categories":["skin_care"]}]},{"product_key":"vital5","family_type":"program_bundle","available":true,"default_item_code":"459","listed_categories":["combo_paks"],"offer_count":1,"offers":[{"item_code":"","name":"Vital5 - Forever Aloe Vera Gel","package_label":"","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/combo-paks/vital5","listed_categories":["combo_paks"]}]},{"product_key":"vitamin-c-bakuchiol-combo","family_type":"bundle_offer","available":true,"default_item_code":"","listed_categories":["skin_care"],"offer_count":1,"offers":[{"item_code":"701","name":"Forever Vitamin C i Forever Bakuchiol","package_label":"","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/701-VITAMIN-C--BAKUCHIOL-COMBOKIT","listed_categories":["skin_care"]}]},{"product_key":"vitolize-men","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"374","name":"Vitolize Men","package_label":"60 kapsula","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/374-VITALIZE-MEN","listed_categories":["nutritional"]}]},{"product_key":"vitolize-women","family_type":"product_family","available":true,"default_item_code":"","listed_categories":["nutritional"],"offer_count":1,"offers":[{"item_code":"375","name":"Vitolize Women","package_label":"120 tableta","availability":"available","official_url":"https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/375-VITALIZE-WOMEN","listed_categories":["nutritional"]}]}]
JSON, true, 512, JSON_THROW_ON_ERROR);

$definitions = [
    [
        'product_key' => 'forever-garcinia-plus',
        'canonical_name' => 'Forever Garcinia Plus',
        'sku' => '071',
        'category_key' => 'nutritional',
        'route' => 'oral',
        'aliases' => ['Garcinia Plus', 'Garcinia'],
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/nutritional',
        'offer_variants' => [[
            'offer_key' => 'hr-071-70-softgels',
            'item_code' => '071',
            'package_label' => '70 mekih kapsula',
            'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/071-FOREVER-GARCINIA-PLUS',
            'availability_status' => 'available',
            'is_primary' => true,
            'content_sha256' => '2e346b49ed2e87cc2ecdae639c738c484572d738d9305db8a68e356b05b725db',
        ]],
    ],
    [
        'product_key' => 'fab-energy-drink',
        'canonical_name' => 'FAB Energy Drink',
        'sku' => '821',
        'category_key' => 'drinks',
        'route' => 'oral',
        'aliases' => ['FAB Energy'],
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/drinks',
        'offer_variants' => [[
            'offer_key' => 'hr-821-250ml',
            'item_code' => '821',
            'package_label' => '250 ml',
            'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/drinks/821-FAB-ENERGY-DRINK',
            'availability_status' => 'available',
            'is_primary' => true,
            'content_sha256' => 'e629ffba458e2193e51fc5d437c92125dc51d4c2e87ac71293e617c16c786211',
        ]],
    ],
    [
        'product_key' => 'vital5',
        'canonical_name' => 'Vital5',
        'sku' => '459',
        'category_key' => 'combo-paks',
        'route' => 'program',
        'aliases' => ['Vital 5'],
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/combo-paks',
        'offer_variants' => [
            ['offer_key' => 'hr-456-vital5-gel', 'item_code' => '456', 'identity_slug' => 'vital5', 'identity_review_note' => 'Reviewed official HR Vital5 radio variant.', 'package_label' => 'Vital5 - Forever Aloe Vera Gel', 'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/combo-paks/vital5', 'availability_status' => 'available', 'is_primary' => false, 'content_sha256' => 'e10d81d2f1764f06ce3b58e3c7fc5790ea9800c2addeb90e840fc99851b3d20d'],
            ['offer_key' => 'hr-457-vital5-berry', 'item_code' => '457', 'identity_slug' => 'vital5', 'identity_review_note' => 'Reviewed official HR Vital5 radio variant.', 'package_label' => 'Vital5 - Forever Aloe Berry Nectar', 'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/combo-paks/vital5', 'availability_status' => 'available', 'is_primary' => false, 'content_sha256' => 'e10d81d2f1764f06ce3b58e3c7fc5790ea9800c2addeb90e840fc99851b3d20d'],
            ['offer_key' => 'hr-458-vital5-peaches', 'item_code' => '458', 'identity_slug' => 'vital5', 'identity_review_note' => 'Reviewed official HR Vital5 radio variant.', 'package_label' => 'Vital5 - Forever Aloe Peaches', 'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/combo-paks/vital5', 'availability_status' => 'available', 'is_primary' => false, 'content_sha256' => 'e10d81d2f1764f06ce3b58e3c7fc5790ea9800c2addeb90e840fc99851b3d20d'],
            ['offer_key' => 'hr-459-vital5-freedom', 'item_code' => '459', 'identity_slug' => 'vital5', 'identity_review_note' => 'Reviewed official HR Vital5 radio variant and current default.', 'package_label' => 'Vital5 - Forever Freedom', 'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/combo-paks/vital5', 'availability_status' => 'available', 'is_primary' => true, 'content_sha256' => 'e10d81d2f1764f06ce3b58e3c7fc5790ea9800c2addeb90e840fc99851b3d20d'],
        ],
    ],
    [
        'product_key' => 'a-touch-of-forever',
        'canonical_name' => 'A Touch of Forever',
        'sku' => '001',
        'category_key' => 'weight-management',
        'route' => 'program',
        'aliases' => [],
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/weight-management',
        'offer_variants' => [[
            'offer_key' => 'hr-001-touch-of-forever',
            'item_code' => '001',
            'identity_slug' => 'touch-of-forever',
            'identity_review_note' => 'Official HR page uses the stable umbrella slug; current reconciled default item is 001.',
            'package_label' => 'A Touch of Forever',
            'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/weight-management/touch-of-forever',
            'availability_status' => 'available',
            'is_primary' => true,
            'content_sha256' => '8d00a2361ac3e44a58cf3e91c14e631d93b3f42d1eb4d560a43656f8fca1555c',
        ]],
    ],
    [
        'product_key' => 'argi-aloe-mini-tripack',
        'canonical_name' => 'ARGI+ and Aloe Mini Tripack',
        'sku' => '71633',
        'category_key' => 'combo-paks',
        'route' => 'program',
        'aliases' => [],
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/combo-paks',
        'offer_variants' => [[
            'offer_key' => 'hr-71633-mini-tripack',
            'item_code' => '71633',
            'package_label' => '3 x 330 ml, 3 x 10 g',
            'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/combo-paks/71633-ARGI%2B-AND-ALOE-MINI-TRIPACK',
            'availability_status' => 'available',
            'is_primary' => true,
            'content_sha256' => '6199bbc8cffeeeec54011be2a7bd72a214027f80a000b88ea882e451c3f903af',
        ]],
    ],
    [
        'product_key' => 'vitamin-c-bakuchiol-combo',
        'canonical_name' => 'Forever Vitamin C i Forever Bakuchiol',
        'sku' => '701',
        'category_key' => 'skin-care',
        'route' => 'topical',
        'aliases' => ['Vitamin C + Bakuchiol combo'],
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/skin-care',
        'offer_variants' => [[
            'offer_key' => 'hr-701-vitamin-c-bakuchiol',
            'item_code' => '701',
            'package_label' => 'Forever Vitamin C i Forever Bakuchiol',
            'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/701-VITAMIN-C--BAKUCHIOL-COMBOKIT',
            'availability_status' => 'available',
            'is_primary' => true,
            'content_sha256' => '399e0774eec94ce0ac317114f2a9c12da22eb2b128242480685338a19ca1948a',
        ]],
    ],
    [
        'product_key' => 'aloe-mixed-four-pack',
        'canonical_name' => '4PACK Aloe Combo',
        'sku' => '82341',
        'category_key' => 'combo-paks',
        'route' => 'oral',
        'aliases' => ['4PACK Aloe Mix'],
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/drinks',
        'offer_variants' => [
            [
                'offer_key' => 'hr-82341-aloe-combo-promo',
                'item_code' => '82341',
                'package_label' => '4PACK Aloe Combo 10% promo',
                'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/drinks/82341-4PACK-ALOE-COMBO-10%25-PROMO',
                'availability_status' => 'available',
                'is_primary' => true,
                'content_sha256' => '3a842b36506edd5538f272914f82ac887c660289de8177b2354cdeda4d6e7ce3',
            ],
            [
                'offer_key' => 'hr-8234-aloe-combo',
                'item_code' => '8234',
                'package_label' => '4 x 1 l',
                'official_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/drinks/8234-FANCY-4-PACK',
                'availability_status' => 'sold_out',
                'is_primary' => false,
                'content_sha256' => 'd8d42244772a3bfdf0446889dee543dc552b2003c06b20ab994a801b3eb7e0c9',
            ],
        ],
    ],
];

$migrationDefinitions = [
    [
        'product_key' => 'forever-daily',
        'current_canonical_name' => 'Forever Daily',
        'legacy_item_code' => '609',
        'current_item_code' => '439',
        'review_note' => 'Legacy 609 resolves to Sonya Daily Skincare Kit; current Forever Daily supplement is item 439.',
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/nutritional',
        'variant' => ['439', '60 tableta', 'https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/439-FOREVER-DAILY', '4746f62c6b26d0d55d423edc5c6f9909323d071e1f475669bc10f02d1ebf8d4a'],
    ],
    [
        'product_key' => 'forever-freedom',
        'current_canonical_name' => 'Forever Freedom',
        'legacy_item_code' => '196',
        'current_item_code' => '896',
        'review_note' => 'Current HR 1 l PET offer replaces legacy item 196.',
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/drinks',
        'variant' => ['896', '1 l', 'https://foreverliving.com/shop/hrv/hr-hr/products/drinks/896-FOREVER-FREEDOM-PET', '76cfc31c8df0f915a7954fc0f8537ad4db2600167ee7b1142401da9cdc810678'],
    ],
    [
        'product_key' => 'aloe-berry-nectar',
        'current_canonical_name' => 'Forever Aloe Berry Nectar',
        'legacy_item_code' => '734',
        'current_item_code' => '834',
        'review_note' => 'Current HR 1 l PET offer replaces legacy item 734.',
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/drinks',
        'variant' => ['834', '1 l', 'https://foreverliving.com/shop/hrv/hr-hr/products/drinks/834-ALOE-BERRY-NECTAR-PET', 'c2368fe12ee2f4659c5dadbf25003f4658cc0abf2aac8df4bf03b0e508f53497'],
    ],
    [
        'product_key' => 'forever-aloe-mango',
        'current_canonical_name' => 'Forever Aloe Mango',
        'legacy_item_code' => '736',
        'current_item_code' => '836',
        'review_note' => 'Current HR 1 l PET offer replaces legacy item 736.',
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/drinks',
        'variant' => ['836', '1 l', 'https://foreverliving.com/shop/hrv/hr-hr/products/drinks/836-FOREVER-ALOE-MANGO-PET', '06e7b40dd53f287c522db927f5a622d35c38820b31b34509d961a606a39a294c'],
    ],
    [
        'product_key' => 'forever-aloe-vera-gel',
        'current_canonical_name' => 'Forever Aloe Vera Gel',
        'legacy_item_code' => '715',
        'current_item_code' => '815',
        'review_note' => 'Current HR 1 l PET offer replaces legacy item 715; legacy SKU must not remain a matcher alias.',
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/drinks',
        'variant' => ['815', '1 l', 'https://foreverliving.com/shop/hrv/hr-hr/products/drinks/815-ALOE-VERA-GEL-PET', '550e1085f2c4d33e4575befe0356b965b261254d01b8290697931e6c497e1c92'],
    ],
    [
        'product_key' => 'fab-forever-active-boost',
        'current_canonical_name' => 'FAB X Forever Active Boost',
        'legacy_item_code' => '721',
        'current_item_code' => '822',
        'review_note' => 'Legacy Active Boost family maps to current FAB X 822; FAB Energy 821 is a separate canonical family.',
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/drinks',
        'variant' => ['822', '250 ml', 'https://foreverliving.com/shop/hrv/hr-hr/products/drinks/822-FAB-X-ENERGY-DRINK', 'fa1e8b367dc3b0b6156076d4c8fb2dbf439cd99ec950bf4ea3721aa84b1956a2'],
    ],
    [
        'product_key' => 'forever-f15',
        'current_canonical_name' => 'Forever F15',
        'legacy_item_code' => '536',
        'current_item_code' => '528',
        'review_note' => 'Current F15 umbrella defaults to item 528; official URL uses the reviewed F15 family slug.',
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/weight-management',
        'variant' => ['528', 'F15 umbrella', 'https://foreverliving.com/shop/hrv/hr-hr/products/weight-management/F15', 'da96f0b50a5391d2af269a31466b698f5b02d56574bb4084e1515b855ca70cbc', 'f15'],
    ],
    [
        'product_key' => 'forever-nutra-q10',
        'current_canonical_name' => 'Forever Cardiohealth',
        'legacy_item_code' => '312',
        'current_item_code' => '312',
        'review_note' => 'Current item 312 is named Forever Cardiohealth; the legacy Nutra Q10 name is stale.',
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/nutritional',
        'variant' => ['312', '30 vrećica x 3,5 g', 'https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/312-FOREVER-CARDIOHEALTH', '93659ec1db297ddf7966b21c7e54ec569128993430742e71e1b9b28a4ada898c'],
    ],
    [
        'product_key' => 'forever-ultra',
        'current_canonical_name' => 'Forever Lite Ultra',
        'legacy_item_code' => '470',
        'current_item_code' => '470',
        'review_note' => 'Current family is Forever Lite Ultra; vanilla 470 is primary and chocolate 471 is an offer variant.',
        'category_url' => 'https://foreverliving.com/shop/hrv/hr-hr/nutritional',
        'variant' => ['470', 'Vanilija, 375 g', 'https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/470-FOREVER-LITE-U-VANILLA-15-SERV', '32dcbd58ab476243988b09c127a5f40a4b8587622c24d2799e32df2aa4cd8591'],
        'additional_variants' => [
            ['471', 'Čokolada, 375 g', 'https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/471-FOREVER-LITE-U-CHOCOLA-15-SERV', '86b8f10ea5f53d34fac87d6e1c5a4f6fb64f8c41c1aef76445261dc85633bcb0'],
        ],
    ],
];

$canonicalProducts = [];
foreach($definitions as $definition) {
    $variants = [];
    foreach($definition['offer_variants'] as $variant) {
        $variants[] = array_merge([
            'market_code' => 'hr',
            'identity_slug' => '',
            'identity_review_note' => '',
            'accessed_at' => $accessedAt,
            'review_status' => 'approved',
        ], $variant);
    }
    $firstHash = (string) ($variants[0]['content_sha256'] ?? '');
    $canonicalProducts[] = [
        'product_key' => $definition['product_key'],
        'language_code' => 'hr',
        'canonical_name' => $definition['canonical_name'],
        'sku' => $definition['sku'],
        'category_key' => $definition['category_key'],
        'route' => $definition['route'],
        'aliases' => $definition['aliases'],
        'review_status' => 'approved',
        'offer_variants' => $variants,
        'source_refs' => [[
            'source_type' => 'official_inventory_reconciliation',
            'source_url' => $definition['category_url'],
            'accessed_at' => $accessedAt,
            'content_sha256' => $firstHash,
            'hash_scope' => 'normalized_category_card',
            'snapshot_sha256' => $snapshotHash,
        ]],
    ];
}

$identityMigrations = [];
foreach($migrationDefinitions as $definition) {
    $rawVariants = array_merge([$definition['variant']], $definition['additional_variants'] ?? []);
    $variants = [];
    foreach($rawVariants as $variantIndex => $variant) {
        $identitySlug = (string) ($variant[4] ?? '');
        $variants[] = [
            'offer_key' => 'hr-' . strtolower((string) $variant[0]) . '-' . ($variantIndex + 1),
            'market_code' => 'hr',
            'item_code' => (string) $variant[0],
            'identity_slug' => $identitySlug,
            'identity_review_note' => $identitySlug !== '' ? $definition['review_note'] : '',
            'package_label' => (string) $variant[1],
            'official_url' => (string) $variant[2],
            'availability_status' => 'available',
            'is_primary' => $variantIndex === 0,
            'accessed_at' => $accessedAt,
            'content_sha256' => (string) $variant[3],
            'review_status' => 'approved',
        ];
    }
    $identityMigrations[] = [
        'product_key' => $definition['product_key'],
        'language_code' => 'hr',
        'market_code' => 'hr',
        'current_canonical_name' => $definition['current_canonical_name'],
        'legacy_item_code' => $definition['legacy_item_code'],
        'current_item_code' => $definition['current_item_code'],
        'review_status' => 'approved',
        'review_note' => $definition['review_note'],
        'offer_variants' => $variants,
        'source_refs' => [[
            'source_type' => 'official_inventory_reconciliation',
            'source_url' => $definition['category_url'],
            'accessed_at' => $accessedAt,
            'content_sha256' => (string) $variants[0]['content_sha256'],
            'hash_scope' => 'normalized_category_card',
            'snapshot_sha256' => $snapshotHash,
        ]],
    ];
}

return [
    'schema_version' => 'fcc-official-inventory-v1',
    'as_of' => '2026-08-25',
    'source_scope' => 'official Forever HR hr-hr category pages',
    'normalized_snapshot_sha256' => $snapshotHash,
    'raw_snapshot_sha256' => '83eb6adc7552fbdcf21a26edb965559a6d27c7bebee4585490aa37239313b1bf',
    'unique_offer_count' => 113,
    'canonical_family_count' => 96,
    'available_family_count' => 92,
    'sold_out_only_family_count' => 4,
    'inventory_families' => $inventoryFamilies,
    'retired_or_orphaned' => [[
        'product_key' => 'forever-tightening-mask-powder',
        'legacy_item_code' => '652',
        'status' => 'retired_or_orphaned',
        'reason' => 'Not listed in current HR categories; prerender returned no product detail or cart state.',
        'last_checked_at' => $accessedAt,
        'legacy_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/652-TIGHTENING-MASK-POWDER',
        'snapshot_sha256' => $snapshotHash,
    ]],
    'canonical_products' => $canonicalProducts,
    'identity_migrations' => $identityMigrations,
];
