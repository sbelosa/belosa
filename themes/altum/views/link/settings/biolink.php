<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_is_hr = \Altum\Language::$code === 'hr';
$fcc_biolink_editor_copy = $fcc_is_hr ? [
    'eyebrow' => 'Forever Card App editor',
    'summary' => 'Ovdje vidiš kojoj aplikaciji uređuješ strukturu, što je javno objavljeno i gdje se otvara analiza kvalitete glavne FCC aplikacije.',
    'main_role' => 'Glavna aplikacija',
    'secondary_role' => 'Dodatna aplikacija',
    'live_status' => 'Javno objavljena',
    'paused_status' => 'Privremeno skrivena',
    'analysis_ready' => 'Analiza spremna',
    'analysis_locked' => 'Analiza zaključana',
    'analysis_main_only' => 'Samo glavna aplikacija',
    'analysis_ready_note' => 'Ova aplikacija može otvoriti App Review i pratiti signal, preporuke i kvalitetu glavnog FCC toka.',
    'analysis_locked_note' => 'Dovrši AI profil i potrebne preduvjete kako bi se analiza otključala za glavnu aplikaciju.',
    'analysis_main_only_note' => 'Analiza kvalitete ostaje vezana uz glavnu FCC aplikaciju. Dodatne aplikacije služe kao pomoćni ili kampanjski layer.',
    'live_note' => 'Posjetitelji trenutno mogu otvoriti ovu aplikaciju na javnom URL-u.',
    'paused_note' => 'Aplikacija je spremljena, ali nije javno prikazana dok je ponovno ne uključiš.',
    'name_label' => 'Naziv aplikacije',
    'role_label' => 'Tip aplikacije',
    'public_label' => 'Status javnog prikaza',
    'analysis_label' => 'Status analize',
    'analysis_cta' => 'Otvori App Review',
    'analysis_locked_cta' => 'Kako otključati analizu',
    'tour_main' => 'Pokreni vodič editora',
    'tour_theme' => 'Teme',
    'tour_ai_theme' => 'AI teme',
    'tour_ai_copy' => 'AI akcije',
    'tour_reset' => 'Reset',
    'tour_forever' => 'Forever blokovi',
    'tour_discount' => 'Naručivanje Forever proizvoda bez registracije',
    'tour_funnel' => 'Funnels',
    'status_card_title' => 'Pregled ove aplikacije',
    'tours_title' => 'AI uređivanje aplikacije',
    'tours_text' => 'Na jednom mjestu imaš 3 glavne AI akcije i kratke korake za brzo, jednostavno i sigurno uređivanje aplikacije.',
    'ai_actions_title' => 'AI brze akcije',
    'ai_actions_text' => 'Na jednom mjestu možeš dodati AI preporučene blokove i nazive, primijeniti AI boje ili sigurno vratiti aplikaciju na prethodno stanje.',
    'tour_launch_title' => 'Glavni tutorijal editora',
    'tour_launch_text' => 'Ovaj gumb uvijek možeš kliknuti kada želiš ponovno pokrenuti kompletni vodič kroz editor. Prvi put se otvara automatski kako bi početnik odmah dobio jasan redoslijed rada.',
    'tour_copy_title' => 'Kopiranje javne web adrese',
    'tour_copy_text' => 'Ovdje jednim klikom kopiraš javni URL cijele aplikacije kako bi ga mogla podijeliti u poruci, objavi ili kampanji.',
    'tour_statistics_title' => 'Analitika aplikacije',
    'tour_statistics_text' => 'Na ovom gumbu otvaraš statistiku cijele Forever Card aplikacije i pratiš klikove, posjete i druge ključne signale.',
    'tour_ai_title' => 'AI analiza glavne FCC aplikacije',
    'tour_ai_text' => 'Ako je ovo glavna Forever aplikacija, ovdje pokrećeš AI analizu koja procjenjuje kvalitetu aplikacije i priprema plan za sljedećih 7 dana poslovnih aktivnosti.',
    'tour_status_title' => 'Status editora na vrhu',
    'tour_status_text' => 'Na vrhu uvijek vidiš koju aplikaciju uređuješ, je li glavna ili dodatna, je li javno aktivna i može li otvoriti analizu kvalitete.',
    'tour_settings_title' => 'Postavke same aplikacije',
    'tour_settings_text' => 'U kartici Postavke uređuješ sve glavne postavke aplikacije. Ovdje početnik podešava adresu, izgled, zaštitu, praćenje i SEO prije rada na samim blokovima.',
    'tour_url_title' => 'Kratki URL i osnovna adresa',
    'tour_url_text' => 'Ovdje određuješ kratku web adresu aplikacije. To je glavni link koji korisnici otvaraju, zato ga ovdje provjeriš i po potrebi prilagodiš.',
    'tour_blocks_title' => 'Blokovi za sadržaj i strukturu',
    'tour_blocks_text' => 'Kartica Blokovi prikazuje sve blokove koji se trenutno nalaze na Forever Card aplikaciji. Tu slažeš redoslijed, uređuješ sadržaj i uključuješ ili isključuješ pojedine dijelove.',
    'tour_blocks_panel_title' => 'Popis blokova unutar aplikacije',
    'tour_blocks_panel_text' => 'Ovdje vidiš stvarni raspored blokova na aplikaciji. Svaki red predstavlja jedan blok, a za pojedine blokove vidiš i njihov link, klikove i dodatnu analitiku.',
    'tour_add_block_title' => 'Dodavanje novog bloka',
    'tour_add_block_text' => 'Ovaj gumb otvara novi odabir blokova po namjeni. Tamo biraš blokove prema tome što želiš postići, a ne po tehničkom nazivu.',
    'tour_add_block_modal_title' => 'Popup za dodavanje blokova',
    'tour_add_block_modal_text' => 'Kad otvoriš Dodaj blok, dobiješ popis blokova složen po stvarnoj primjeni. Tako početnik odmah vidi koja grupa služi za uvod, kontakte, prodaju, business ili Forever Card Club korake.',
    'tour_block_search_title' => 'Pretraga blokova',
    'tour_block_search_text' => 'U ovu tražilicu upiši naziv bloka, njegovu namjenu ili cilj aplikacije i popup će odmah suziti izbor na najrelevantnije opcije.',
    'tour_block_group_title' => 'Odabir grupe blokova',
    'tour_block_group_text' => 'Ovdje filtriraš blokove po grupi kao što su Početak aplikacije, Kontakti i leadovi, Prodaja i preporuke ili Business.',
    'tour_block_goal_title' => 'Filter po cilju aplikacije',
    'tour_block_goal_text' => 'Ovaj filter dodatno sužava izbor prema tome želiš li uvod, prikupljanje kontakata, preporuku proizvoda, rezervaciju termina, povjerenje ili sadržaj.',
    'tour_block_card_title' => 'Kako stvarno dodati blok',
    'tour_block_card_text' => 'Kada pronađeš željeni blok, klikneš na njegovu karticu i editor ga odmah dodaje u aplikaciju. Nakon toga se vraćaš u Blocks listu i uređuješ sadržaj tog novog bloka.',
    'tour_preview_title' => 'Pregled uživo',
    'tour_preview_text' => 'Desna strana ti u svakom trenutku pokazuje pregled aplikacije uživo. Tu odmah vidiš kako promjene iz Postavki i Blokova izgledaju za stvarnog posjetitelja.',
    'tour_theme_title' => 'Teme počinju u kartici Postavke',
    'tour_theme_text' => 'Odabir teme vezan je uz postavke aplikacije, zato prvo prelazimo u karticu Postavke.',
    'tour_theme_button_title' => 'Otvaranje teme',
    'tour_theme_button_text' => 'Ovaj gumb otvara biblioteku tema i layout stilova za cijelu aplikaciju.',
    'tour_theme_modal_title' => 'Pregled biblioteke tema',
    'tour_theme_modal_text' => 'U ovom popupu biraš vizualni smjer aplikacije i odmah vidiš koji layout želiš primijeniti.',
    'tour_ai_theme_title' => 'Teme i izgled aplikacije',
    'tour_ai_theme_text' => 'Ovdje biraš osnovni izgled aplikacije kroz teme, a AI boje se sada primjenjuju kroz jedinstveni blok AI brzih akcija.',
    'tour_ai_theme_library_title' => 'AI teme u biblioteci tema',
    'tour_ai_theme_library_text' => 'U biblioteci tema ovdje se pojavljuju i AI preporučene teme. Tako preporuku s jedne aplikacije možeš primijeniti i na drugu bez ručnog slaganja svega ispočetka.',
    'tour_ai_copy_title' => 'AI akcije za ovu aplikaciju',
    'tour_ai_copy_text' => 'Ovdje na jednom mjestu dobivaš 3 glavne AI radnje: dodavanje blokova i naziva, primjenu boja i siguran povratak na staro stanje.',
    'ai_workflow_title' => 'Kako koristiti AI preporuke bez lutanja',
    'ai_workflow_text' => 'Ako je AI analiza već izrađena, slijedi ova 4 koraka i dobit ćeš najbrži put od preporuke do uređene aplikacije.',
    'ai_workflow_step_1' => '1. U App Reviewu provjeri glavnu preporuku i zatim otvori uređivanje aplikacije.',
    'ai_workflow_step_2' => '2. Ako želiš ručno birati osnovni izgled, otvori Teme. Za AI tok koristi 3 AI brze akcije u ovom bloku.',
    'ai_workflow_step_3' => '3. U Blokovima klikni AI prijedlog blokova i naziva. Sustav tada dodaje preporučene blokove, ubacuje AI nazive i CTA tekstove te slaže aplikaciju po planu.',
    'ai_workflow_step_4' => '4. Nakon toga klikni AI prijedlog boja, provjeri preview i po potrebi koristi Vrati na staro ako želiš siguran povratak na prethodno stanje.',
    'ai_block_helper_title' => 'AI preporuke za ovaj blok',
    'ai_block_helper_text' => 'Ovdje odmah vidiš što AI predlaže baš za ovaj blok i možeš jednim klikom ubaciti naziv ili CTA u pravo polje.',
    'ai_block_helper_primary' => 'Ovo je označeno kao glavni blok za sljedeći korak.',
    'tour_ai_theme_apply_title' => 'Primijeni AI temu',
    'tour_ai_theme_apply_text' => 'Klikom ovdje sustav odmah prenosi preporučene boje, pozadinu, font i raspored razmaka na ovu aplikaciju.',
    'tour_ai_theme_library_button_title' => 'Otvori AI teme za druge aplikacije',
    'tour_ai_theme_library_button_text' => 'Ako suradnik ima više aplikacija, ovdje iz biblioteke možeš uzeti isti AI stil i primijeniti ga i na drugu aplikaciju.',
    'tour_ai_copy_apply_title' => 'AI prijedlog blokova i naziva',
    'tour_ai_copy_apply_text' => 'Ovaj gumb odjednom dodaje preporučene blokove, ubacuje AI nazive i CTA tekstove te slaže aplikaciju prema planu.',
    'tour_ai_layout_apply_title' => 'AI prijedlog boja',
    'tour_ai_layout_apply_text' => 'Ovaj gumb primjenjuje AI boje i pozadinu, a odmah do njega je i Vrati na staro ako želiš siguran povratak na prethodno stanje.',
    'tour_reset_title' => 'Vraćanje aplikacije na početni izgled',
    'tour_reset_text' => 'Reset služi kada želiš vratiti početni FCC raspored i krenuti ponovno od čistog predloška.',
    'tour_reset_button_title' => 'Vrati na početni izgled',
    'tour_reset_button_text' => 'Ovdje pokrećeš vraćanje početnog izgleda i početnog sadržajnog rasporeda aplikacije.',
    'tour_customizations_title' => 'Prilagodbe aplikacije',
    'tour_customizations_text' => 'Ova rubrika otvara vizualne prilagodbe kao što su pozadina, fontovi, širina, spacing i opći izgled cijele aplikacije.',
    'tour_pixels_title' => 'Pikseli i praćenje kampanja',
    'tour_pixels_text' => 'Ovdje spajaš marketinške pixele kako bi kampanje, remarketing i konverzije bile mjerljive izvan same aplikacije.',
    'tour_utm_title' => 'UTM parametri',
    'tour_utm_text' => 'U ovoj rubrici dodaješ UTM oznake za praćenje izvora prometa, kampanja i kanala iz kojih korisnici dolaze.',
    'tour_protection_title' => 'Zaštita aplikacije',
    'tour_protection_text' => 'Zaštita služi za lozinku i osjetljivi sadržaj. Koristiš je kada aplikaciju ne želiš pokazati svakome bez kontrole pristupa.',
    'tour_seo_title' => 'SEO postavke',
    'tour_seo_text' => 'SEO rubrika određuje kako se aplikacija prikazuje u tražilicama i kada se dijeli kao link, uključujući naslov i opis.',
    'tour_reset_preview_title' => 'Provjeri rezultat u pregledu',
    'tour_reset_preview_text' => 'Nakon resetiranja upravo ovdje najbrže potvrđuješ da je aplikacija stvarno vraćena na početni izgled.',
    'tour_forever_title' => 'Forever blokovi nalaze se u kartici Blokovi',
    'tour_forever_text' => 'Za Forever Card Club specifične blokove prvo otvori karticu Blokovi pa zatim Dodaj blok.',
    'tour_forever_add_block_title' => 'Dodaj Forever blok',
    'tour_forever_add_block_text' => 'Odabir blokova po namjeni možeš odmah filtrirati na FCC specifične blokove kako bi izbor bio kraći i jasniji.',
    'tour_forever_modal_title' => 'FCC blokovi po primjeni',
    'tour_forever_modal_text' => 'Ovdje su svi ključni FCC blokovi za preporuke, proizvode, chatbot i pomoćne korake unutar glavne aplikacije.',
    'tour_discount_title' => 'Prodajni blok nalazi se u kartici Blokovi',
    'tour_discount_text' => 'Za klikove prema Forever Living proizvodima prvo otvori karticu Blokovi, jer se prodajni blok dodaje iz popisa blokova.',
    'tour_discount_add_block_title' => 'Otvori Dodaj blok',
    'tour_discount_add_block_text' => 'Ovdje otvaraš odabir blokova po namjeni iz kojeg dodaješ prodajni blok za Forever Living proizvode.',
    'tour_discount_modal_title' => 'Forever web trgovina blok',
    'tour_discount_modal_text' => 'Ovaj blok koristiš za prodajni i preporučni link prema Forever Living proizvodima. Klikovi na ovaj blok ulaze u 15+ / 30d signal za Istaknute aplikacije i pomažu graditi 50+ / 30d status preporučenog sponzora za naslovnicu. Prag 15+ u 7 dana služi samo kao tjedna provjera ritma. Nakon klika na blok i prije aktivacije obavezno zalijepi link izrađen u Forever Link Builderu na Foreverliving.com stranici.',
    'tour_discount_modal_resource' => 'Pogledaj video uputu',
    'tour_discount_create_title' => 'Otvara se prozor za aktivaciju bloka',
    'tour_discount_create_text' => 'Nakon klika na ovaj blok otvara se prozor u kojem podešavaš sve što je potrebno za ispravnu aktivaciju webshop gumba.',
    'tour_discount_notice_title' => 'Prvo pripremi ispravan Forever Link Builder link',
    'tour_discount_notice_text' => 'Ovdje odmah vidiš najvažniju napomenu i video uputu. Prije spremanja trebaš imati link izrađen u Forever Link Builderu na Foreverliving.com stranici.',
    'tour_discount_url_title' => 'Zalijepi webshop link',
    'tour_discount_url_text' => 'U ovo polje zalijepi puni link koji si izradio u Forever Link Builderu. To je glavni korak bez kojeg blok neće ispravno voditi na ponudu.',
    'tour_discount_name_title' => 'Upiši naziv gumba',
    'tour_discount_name_text' => 'Ovdje postavljaš jasan naziv koji će posjetitelj vidjeti na aplikaciji, npr. webshop, naruči bez registracije ili druga kratka prodajna poruka.',
    'tour_discount_apply_title' => 'Odaberi primjenu Link Builder toka na sve proizvode',
    'tour_discount_apply_text' => 'Ovdje biraš hoće li se isti narudžbeni tok bez registracije koristiti i na ostalim produkt gumbima u blogu i aplikaciji.',
    'tour_discount_submit_title' => 'Spremi i aktiviraj blok',
    'tour_discount_submit_text' => 'Kad su link, naziv i opcija primjene spremni, ovdje spremaš blok. Nakon toga blok je dodan u aplikaciju i možeš ga dalje uređivati.',
    'tour_funnel_title' => 'Funnel koraci počinju u kartici Blokovi',
    'tour_funnel_text' => 'Forever Card Funnel dodaješ kao blok, zato prvo ulazimo u listu blokova.',
    'tour_funnel_add_block_title' => 'Otvori odabir funnel bloka',
    'tour_funnel_add_block_text' => 'Ovdje otvaraš odabir blokova koji već može filtrirati blokove za prijave, pozive na akciju i vođene daljnje korake.',
    'tour_funnel_modal_title' => 'Lead i funnel blokovi',
    'tour_funnel_modal_text' => 'U ovoj grupi biraš blokove za prijave, preporuke i konverzijske korake unutar aplikacije.',
] : [
    'eyebrow' => 'Forever Card App editor',
    'summary' => 'This area shows which app you are editing, what is publicly visible, and whether the main FCC app can open quality analysis.',
    'main_role' => 'Main app',
    'secondary_role' => 'Additional app',
    'live_status' => 'Publicly live',
    'paused_status' => 'Temporarily hidden',
    'analysis_ready' => 'Analysis ready',
    'analysis_locked' => 'Analysis locked',
    'analysis_main_only' => 'Main app only',
    'analysis_ready_note' => 'This app can open App Review and track signal, recommendations, and quality for the main FCC flow.',
    'analysis_locked_note' => 'Complete the AI profile and required prerequisites to unlock analysis for the main app.',
    'analysis_main_only_note' => 'Quality analysis stays tied to the main FCC app. Additional apps act as support or campaign layers.',
    'live_note' => 'Visitors can currently open this app on its public URL.',
    'paused_note' => 'The app is saved, but not publicly visible until you enable it again.',
    'name_label' => 'App name',
    'role_label' => 'App type',
    'public_label' => 'Public status',
    'analysis_label' => 'Analysis status',
    'analysis_cta' => 'Open App Review',
    'analysis_locked_cta' => 'How to unlock analysis',
    'tour_main' => 'Start the editor guide',
    'tour_theme' => 'Themes',
    'tour_ai_theme' => 'AI themes',
    'tour_ai_copy' => 'AI actions',
    'tour_reset' => 'Reset',
    'tour_forever' => 'Forever blocks',
    'tour_discount' => 'Forever products ordering without registration',
    'tour_funnel' => 'Funnels',
    'status_card_title' => 'Overview of this app',
    'tours_title' => 'AI app editing',
    'tours_text' => 'In one place you have the 3 main AI actions and short steps for fast, simple, and safe app editing.',
    'ai_actions_title' => 'AI quick actions',
    'ai_actions_text' => 'From one place you can add AI-recommended blocks and titles, apply AI colors, or safely restore the previous app state.',
    'tour_launch_title' => 'Main editor tutorial',
    'tour_launch_text' => 'Use this button whenever you want to restart the full editor walkthrough. On the first visit it opens automatically so a beginner gets the right working order right away.',
    'tour_copy_title' => 'Copy the public web address',
    'tour_copy_text' => 'This button copies the public URL of the whole app, so you can quickly share it in a message, post, or campaign.',
    'tour_statistics_title' => 'App analytics',
    'tour_statistics_text' => 'This opens analytics for the full Forever Card app so you can review clicks, visits, and the most important performance signals.',
    'tour_ai_title' => 'AI analysis for the main FCC app',
    'tour_ai_text' => 'If this is the main Forever app, this area launches the AI analysis that evaluates app quality and prepares the next 7 days of business actions.',
    'tour_status_title' => 'Top editor status',
    'tour_status_text' => 'At the top you always see which app you are editing, whether it is main or additional, whether it is publicly live, and whether it can open quality analysis.',
    'tour_settings_title' => 'Settings for the app itself',
    'tour_settings_text' => 'The Settings tab contains the global app controls. This is where a beginner sets the address, appearance, protection, tracking, and SEO before editing individual blocks.',
    'tour_url_title' => 'Short URL and base address',
    'tour_url_text' => 'This area defines the short address of the app. It is the main link people open, so this is where you review and adjust it when needed.',
    'tour_blocks_title' => 'Blocks for structure and content',
    'tour_blocks_text' => 'The Blocks tab shows every block currently inside the Forever Card app. This is where you arrange order, edit content, and enable or disable sections.',
    'tour_blocks_panel_title' => 'The block list inside the app',
    'tour_blocks_panel_text' => 'This is the real block structure of the app. Each row is one block, and some rows also expose a block URL, click data, or extra analytics actions.',
    'tour_add_block_title' => 'Adding a new block',
    'tour_add_block_text' => 'This button opens the new purpose-based block picker, where you choose blocks by what you want to achieve instead of by technical label.',
    'tour_add_block_modal_title' => 'The add-block popup',
    'tour_add_block_modal_text' => 'When you open Add block, you get a block list organized by real use case. That helps a beginner immediately understand which group fits intros, contacts, sales, business needs, or Forever Card Club flows.',
    'tour_block_search_title' => 'Searching for blocks',
    'tour_block_search_text' => 'Type a block name, its purpose, or the goal of the app here and the popup will instantly narrow the choice to the most relevant options.',
    'tour_block_group_title' => 'Choose a block group',
    'tour_block_group_text' => 'Use this filter to narrow blocks by group such as App start, Contacts and leads, Sales and recommendations, or Business.',
    'tour_block_goal_title' => 'Filter by app goal',
    'tour_block_goal_text' => 'This filter narrows the list even further based on whether you want an intro, lead capture, product recommendation, booking, trust, or content.',
    'tour_block_card_title' => 'How to actually add a block',
    'tour_block_card_text' => 'Once you find the right block, click its card and the editor will add it to the app immediately. After that you return to the block list and edit the new block content.',
    'tour_preview_title' => 'Live preview',
    'tour_preview_text' => 'The right side always shows a live preview of the app. This is where you immediately confirm how changes from Settings and Blocks look to a real visitor.',
    'tour_theme_title' => 'Themes begin in Settings',
    'tour_theme_text' => 'The theme flow belongs to the app settings, so we begin in Settings first.',
    'tour_theme_button_title' => 'Open themes',
    'tour_theme_button_text' => 'This button opens the theme library and layout styles for the whole app.',
    'tour_theme_modal_title' => 'Theme library overview',
    'tour_theme_modal_text' => 'Inside this popup you choose the visual direction of the app and see which layout you want to apply.',
    'tour_ai_theme_title' => 'App themes and styling',
    'tour_ai_theme_text' => 'This is where you choose the base app look through themes, while AI colors are now applied through the single AI quick actions block.',
    'tour_ai_theme_library_title' => 'AI themes inside the theme library',
    'tour_ai_theme_library_text' => 'The theme library also shows AI-recommended themes for you, so a strong recommendation from one app can be applied to another without rebuilding the style from scratch.',
    'tour_ai_copy_title' => 'AI actions for this app',
    'tour_ai_copy_text' => 'This is the single place for the 3 main AI actions: adding blocks and titles, applying colors, and safely restoring the previous state.',
    'ai_workflow_title' => 'How to use AI recommendations without guessing',
    'ai_workflow_text' => 'If the AI analysis is already ready, follow these 4 steps for the fastest path from recommendation to a polished app.',
    'ai_workflow_step_1' => '1. Open App Review, confirm the main recommendation, and enter the app editor.',
    'ai_workflow_step_2' => '2. If you want to choose the base look manually, open Themes. For the AI flow, use the 3 AI quick actions in this block.',
    'ai_workflow_step_3' => '3. In Blocks click AI block and copy suggestion. The system then adds the recommended blocks, inserts AI titles and CTA text, and arranges the app by plan.',
    'ai_workflow_step_4' => '4. Then click AI color suggestion, review the preview, and use Restore previous state if you want a safe rollback.',
    'ai_block_helper_title' => 'AI suggestions for this block',
    'ai_block_helper_text' => 'This area shows what AI recommends for this specific block, and you can insert the title or CTA into the right field with one click.',
    'ai_block_helper_primary' => 'This is marked as the main next-step block.',
    'tour_ai_theme_apply_title' => 'Apply the AI theme',
    'tour_ai_theme_apply_text' => 'This button immediately applies the recommended colors, background, font, and spacing to this app.',
    'tour_ai_theme_library_button_title' => 'Open AI themes for other apps',
    'tour_ai_theme_library_button_text' => 'If the collaborator has multiple apps, this library lets you reuse the same AI style on another app as well.',
    'tour_ai_copy_apply_title' => 'AI block and copy suggestion',
    'tour_ai_copy_apply_text' => 'This button adds the recommended blocks, fills in AI titles and CTA text, and arranges the app according to the plan.',
    'tour_ai_layout_apply_title' => 'AI color suggestion',
    'tour_ai_layout_apply_text' => 'This button applies AI colors and background, and the restore button next to it safely returns the previous state.',
    'tour_reset_title' => 'The app reset flow',
    'tour_reset_text' => 'Reset is useful when you want to restore the starting FCC structure and begin again from a clean template.',
    'tour_reset_button_title' => 'Factory reset',
    'tour_reset_button_text' => 'This is where you restore the starting layout and default content structure of the app.',
    'tour_customizations_title' => 'App customizations',
    'tour_customizations_text' => 'This section opens the visual customizations such as background, fonts, width, spacing, and the overall look of the app.',
    'tour_pixels_title' => 'Pixels and campaign tracking',
    'tour_pixels_text' => 'This is where you connect marketing pixels so campaigns, remarketing, and conversions can be measured outside the app as well.',
    'tour_utm_title' => 'UTM parameters',
    'tour_utm_text' => 'In this section you add UTM tags for tracking traffic sources, campaigns, and channels that bring users to the app.',
    'tour_protection_title' => 'App protection',
    'tour_protection_text' => 'Protection is used for passwords and sensitive content. Use it when the app should not be openly visible to everyone.',
    'tour_seo_title' => 'SEO settings',
    'tour_seo_text' => 'The SEO section controls how the app appears in search engines and when shared as a link, including title and description.',
    'tour_reset_preview_title' => 'Check the result in preview',
    'tour_reset_preview_text' => 'After a reset, this preview is the fastest place to confirm that the app really returned to the starting layout.',
    'tour_forever_title' => 'Forever blocks live in Blocks',
    'tour_forever_text' => 'For Forever Card Club-specific blocks, first open the Blocks tab and then the Add block picker.',
    'tour_forever_add_block_title' => 'Add a Forever block',
    'tour_forever_add_block_text' => 'The purpose-based picker can immediately filter to FCC-specific blocks so the choice becomes shorter and clearer.',
    'tour_forever_modal_title' => 'FCC blocks by use case',
    'tour_forever_modal_text' => 'This view contains the key FCC blocks for referrals, products, chatbots, and support steps inside the main app.',
    'tour_discount_title' => 'The sales block lives in Blocks',
    'tour_discount_text' => 'For clicks toward Forever Living products, first open the Blocks tab because the sales block is added from the block list.',
    'tour_discount_add_block_title' => 'Open Add block',
    'tour_discount_add_block_text' => 'This opens the purpose-based picker where you add the sales block for Forever Living products.',
    'tour_discount_modal_title' => 'Forever Web Shop block',
    'tour_discount_modal_text' => 'Use this block for the sales and referral link toward Forever Living products. Clicks on this block count toward the 15+ / 30d signal for Featured Apps and help build the 50+ / 30d recommended sponsor status for the homepage. The 15+ in 7 days threshold is only a weekly rhythm check. After clicking the block and before activation, paste the link created in Forever Link Builder on the Foreverliving.com website.',
    'tour_discount_modal_resource' => 'Watch the video guide',
    'tour_discount_create_title' => 'The block activation modal opens here',
    'tour_discount_create_text' => 'After clicking this block, the setup modal opens so you can define everything needed for correct webshop block activation.',
    'tour_discount_notice_title' => 'Prepare the correct Forever Link Builder link first',
    'tour_discount_notice_text' => 'This is the key note and video guide. Before saving, you should have the link created in Forever Link Builder on the Foreverliving.com website.',
    'tour_discount_url_title' => 'Paste the webshop link',
    'tour_discount_url_text' => 'Paste the full link created in Forever Link Builder into this field. This is the main step required for the block to open the correct offer.',
    'tour_discount_name_title' => 'Set the button name',
    'tour_discount_name_text' => 'This is where you set the clear visible label that the visitor sees on the app, such as webshop, order without registration, or another short sales message.',
    'tour_discount_apply_title' => 'Choose whether the Link Builder flow applies to all products',
    'tour_discount_apply_text' => 'Here you decide whether the same no-registration ordering flow should also be used automatically on other product buttons across the app and blog.',
    'tour_discount_submit_title' => 'Save and activate the block',
    'tour_discount_submit_text' => 'Once the link, title, and ordering-flow option are ready, save the block here. After that the block is added to the app and ready for further editing.',
    'tour_funnel_title' => 'The funnel flow starts in Blocks',
    'tour_funnel_text' => 'You add the Forever Card Funnel as a block, so we begin in the block list.',
    'tour_funnel_add_block_title' => 'Open the funnel picker',
    'tour_funnel_add_block_text' => 'This opens the picker which can already filter blocks for registrations, CTA steps, and guided follow-up.',
    'tour_funnel_modal_title' => 'Lead and funnel blocks',
    'tour_funnel_modal_text' => 'In this group you choose blocks for registrations, recommendations, and conversion steps inside the app.',
];

$fcc_biolink_editor_is_main = !empty($data->is_main_biolink_app);
$fcc_biolink_editor_public_status = !empty($data->link->is_enabled) ? 'live' : 'paused';
$fcc_biolink_editor_analysis_status = !$fcc_biolink_editor_is_main ? 'main_only' : (!empty($data->app_review_is_accessible) ? 'ready' : 'locked');
$fcc_biolink_editor_display_name = trim((string) ($data->link->settings->seo->title ?? ''));
if($fcc_biolink_editor_display_name === '') {
    $fcc_biolink_editor_display_name = trim((string) ($data->link->url ?? ''));
}

$fcc_biolink_editor_meta_items = [
    ['label' => $fcc_biolink_editor_copy['role_label'], 'value' => $fcc_biolink_editor_is_main ? $fcc_biolink_editor_copy['main_role'] : $fcc_biolink_editor_copy['secondary_role'], 'icon' => 'fas fa-layer-group', 'title' => $fcc_biolink_editor_is_main ? $fcc_biolink_editor_copy['analysis_ready_note'] : $fcc_biolink_editor_copy['analysis_main_only_note']],
    ['label' => $fcc_biolink_editor_copy['public_label'], 'value' => $fcc_biolink_editor_public_status === 'live' ? $fcc_biolink_editor_copy['live_status'] : $fcc_biolink_editor_copy['paused_status'], 'icon' => 'fas fa-globe-europe', 'title' => $fcc_biolink_editor_public_status === 'live' ? $fcc_biolink_editor_copy['live_note'] : $fcc_biolink_editor_copy['paused_note']],
    ['label' => $fcc_biolink_editor_copy['analysis_label'], 'value' => $fcc_biolink_editor_analysis_status === 'ready' ? $fcc_biolink_editor_copy['analysis_ready'] : ($fcc_biolink_editor_analysis_status === 'locked' ? $fcc_biolink_editor_copy['analysis_locked'] : $fcc_biolink_editor_copy['analysis_main_only']), 'icon' => 'fas fa-chart-line', 'title' => $fcc_biolink_editor_analysis_status === 'ready' ? $fcc_biolink_editor_copy['analysis_ready_note'] : ($fcc_biolink_editor_analysis_status === 'locked' ? $fcc_biolink_editor_copy['analysis_locked_note'] : $fcc_biolink_editor_copy['analysis_main_only_note'])],
];

$fcc_ai_editor_payload = $data->ai_editor_payload ?? [];
$fcc_ai_theme_pack = is_array($fcc_ai_editor_payload['theme_pack'] ?? null) ? $fcc_ai_editor_payload['theme_pack'] : [];
$fcc_ai_primary_block_plan = is_array($fcc_ai_editor_payload['primary_block_plan'] ?? null) ? $fcc_ai_editor_payload['primary_block_plan'] : [];
$fcc_ai_copy_suggestions = is_array($fcc_ai_editor_payload['copy_suggestions'] ?? null) ? $fcc_ai_editor_payload['copy_suggestions'] : [];
$fcc_ai_layout_actions = is_array($fcc_ai_editor_payload['layout_actions'] ?? null) ? $fcc_ai_editor_payload['layout_actions'] : [];
$fcc_ai_missing_block_recommendations = is_array($fcc_ai_editor_payload['missing_block_recommendations'] ?? null) ? $fcc_ai_editor_payload['missing_block_recommendations'] : [];
$fcc_ai_theme_library = is_array($fcc_ai_editor_payload['theme_library'] ?? null) ? $fcc_ai_editor_payload['theme_library'] : [];
$fcc_ai_theme_apply_state = is_array($fcc_ai_editor_payload['theme_apply_state'] ?? null) ? $fcc_ai_editor_payload['theme_apply_state'] : [];
$fcc_ai_review_summary = is_array($fcc_ai_editor_payload['review_summary'] ?? null) ? $fcc_ai_editor_payload['review_summary'] : [];
$fcc_ai_evolution_payload = is_array($fcc_ai_editor_payload['evolution'] ?? null) ? $fcc_ai_editor_payload['evolution'] : [];
$fcc_ai_layout_backup = is_array($fcc_ai_editor_payload['layout_backup'] ?? null) ? $fcc_ai_editor_payload['layout_backup'] : [];
$fcc_ai_bundle_backup = is_array($fcc_ai_editor_payload['bundle_backup'] ?? null) ? $fcc_ai_editor_payload['bundle_backup'] : [];
$fcc_ai_block_attribution = is_array($fcc_ai_editor_payload['block_attribution'] ?? null) ? $fcc_ai_editor_payload['block_attribution'] : [];
$fcc_ai_evolution_active_cycle = is_array($fcc_ai_evolution_payload['active_cycle'] ?? null) ? $fcc_ai_evolution_payload['active_cycle'] : [];
$fcc_ai_evolution_recent_cycles = is_array($fcc_ai_evolution_payload['recent_cycles'] ?? null) ? $fcc_ai_evolution_payload['recent_cycles'] : [];
$fcc_ai_block_signal_blocks = is_array($fcc_ai_block_attribution['top_signal_blocks'] ?? null) ? $fcc_ai_block_attribution['top_signal_blocks'] : [];
$fcc_ai_block_focus_risks = is_array($fcc_ai_block_attribution['focus_risk_blocks'] ?? null) ? $fcc_ai_block_attribution['focus_risk_blocks'] : [];
$fcc_biolink_preview_coach_pause_copy = $fcc_is_hr
    ? [
        'title' => 'Coach je privremeno zatvoren',
        'text' => 'Otvoreni coach zatvoren je dok biraš blokove. Kad zatvoriš "Dodaj blok", možeš ga odmah ponovno otvoriti.',
    ]
    : [
        'title' => 'Coach is temporarily closed',
        'text' => 'An open coach is closed while you pick blocks. Once you close "Add block", you can open it again right away.',
    ];
$fcc_ai_has_theme = (bool) array_filter([
    $fcc_ai_theme_pack['background_color'] ?? '',
    $fcc_ai_theme_pack['gradient_start'] ?? '',
    $fcc_ai_theme_pack['gradient_end'] ?? '',
    $fcc_ai_theme_pack['primary_block_background'] ?? '',
    $fcc_ai_theme_pack['secondary_blocks_background'] ?? '',
]);
$fcc_ai_primary_block_id = (int) ($fcc_ai_primary_block_plan['block_id'] ?? 0);
$fcc_ai_copy_suggestion_map = [];
$fcc_ai_copy_suggestion_block_ids = [];

foreach($fcc_ai_copy_suggestions as $fcc_ai_copy_suggestion) {
    $fcc_ai_copy_block_id = (int) ($fcc_ai_copy_suggestion['block_id'] ?? 0);

    if(!$fcc_ai_copy_block_id) {
        continue;
    }

    $fcc_ai_copy_suggestion_map[$fcc_ai_copy_block_id][] = $fcc_ai_copy_suggestion;
    $fcc_ai_copy_suggestion_block_ids[$fcc_ai_copy_block_id] = true;
}

$fcc_render_ai_color_chip = static function(?string $color, string $label): string {
    $color = trim((string) $color);

    if($color === '') {
        return '';
    }

    ob_start();
    ?>
    <span class="fcc-ai-theme-chip">
        <span class="fcc-ai-theme-chip-dot" style="background: <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>"></span>
        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
        <strong><?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?></strong>
    </span>
    <?php
    return (string) ob_get_clean();
};

$fcc_ai_render_evolution_status = static function(string $status): string {
    $status = trim($status);

    if($status === 'measured') {
        return l('link.settings.ai_evolution_status_measured');
    }

    if($status === 'ready') {
        return l('link.settings.ai_evolution_status_ready');
    }

    return l('link.settings.ai_evolution_status_pending');
};

$fcc_biolink_editor_tours = [
    'main' => [
        ['selector' => '#fcc_biolink_page_guide', 'title' => $fcc_biolink_editor_copy['tour_launch_title'], 'text' => $fcc_biolink_editor_copy['tour_launch_text']],
        ['selector' => '#link_full_url_copy', 'title' => $fcc_biolink_editor_copy['tour_copy_title'], 'text' => $fcc_biolink_editor_copy['tour_copy_text']],
        ['selector' => '#fcc_biolink_editor_step_statistics', 'title' => $fcc_biolink_editor_copy['tour_statistics_title'], 'text' => $fcc_biolink_editor_copy['tour_statistics_text']],
        ['selector' => '#fcc_app_stats_tour_step_ai_block', 'title' => $fcc_biolink_editor_copy['tour_ai_title'], 'text' => $fcc_biolink_editor_copy['tour_ai_text']],
        ['selector' => '#fcc_biolink_editor_status', 'title' => $fcc_biolink_editor_copy['tour_status_title'], 'text' => $fcc_biolink_editor_copy['tour_status_text']],
        ['selector' => '#settings-tab', 'action' => 'activateSettings', 'title' => $fcc_biolink_editor_copy['tour_settings_title'], 'text' => $fcc_biolink_editor_copy['tour_settings_text']],
        ['selector' => '#fcc_biolink_editor_step_url', 'action' => 'activateSettings', 'title' => $fcc_biolink_editor_copy['tour_url_title'], 'text' => $fcc_biolink_editor_copy['tour_url_text']],
        ['selector' => '#fcc_biolink_theme_button', 'action' => 'activateSettings', 'title' => $fcc_biolink_editor_copy['tour_theme_button_title'], 'text' => $fcc_biolink_editor_copy['tour_theme_button_text']],
        ['selector' => '#reset_biolink_factory_btn', 'action' => 'activateSettings', 'title' => $fcc_biolink_editor_copy['tour_reset_button_title'], 'text' => $fcc_biolink_editor_copy['tour_reset_button_text']],
        ['selector' => '#fcc_biolink_settings_customizations_button', 'action' => 'showCollapse', 'collapse_target' => '#customizations_container', 'title' => $fcc_biolink_editor_copy['tour_customizations_title'], 'text' => $fcc_biolink_editor_copy['tour_customizations_text']],
        ['selector' => '#fcc_biolink_settings_pixels_button', 'action' => 'showCollapse', 'collapse_target' => '#pixels_container', 'title' => $fcc_biolink_editor_copy['tour_pixels_title'], 'text' => $fcc_biolink_editor_copy['tour_pixels_text']],
        ['selector' => '#fcc_biolink_settings_utm_button', 'action' => 'showCollapse', 'collapse_target' => '#utm_container', 'title' => $fcc_biolink_editor_copy['tour_utm_title'], 'text' => $fcc_biolink_editor_copy['tour_utm_text']],
        ['selector' => '#fcc_biolink_settings_protection_button', 'action' => 'showCollapse', 'collapse_target' => '#protection_container', 'title' => $fcc_biolink_editor_copy['tour_protection_title'], 'text' => $fcc_biolink_editor_copy['tour_protection_text']],
        ['selector' => '#fcc_biolink_settings_seo_button', 'action' => 'showCollapse', 'collapse_target' => '#seo_container', 'title' => $fcc_biolink_editor_copy['tour_seo_title'], 'text' => $fcc_biolink_editor_copy['tour_seo_text']],
        ['selector' => '#blocks-tab', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_blocks_title'], 'text' => $fcc_biolink_editor_copy['tour_blocks_text']],
        ['selector' => '#biolink_blocks', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_blocks_panel_title'], 'text' => $fcc_biolink_editor_copy['tour_blocks_panel_text']],
        ['selector' => '#fcc_biolink_add_block_button', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_add_block_title'], 'text' => $fcc_biolink_editor_copy['tour_add_block_text']],
        ['selector' => '#biolink_preview_iframe', 'action' => 'closeModals', 'title' => $fcc_biolink_editor_copy['tour_preview_title'], 'text' => $fcc_biolink_editor_copy['tour_preview_text']],
    ],
    'themes' => [
        ['selector' => '#settings-tab', 'action' => 'activateSettings', 'title' => $fcc_biolink_editor_copy['tour_theme_title'], 'text' => $fcc_biolink_editor_copy['tour_theme_text']],
        ['selector' => '#fcc_biolink_theme_button', 'action' => 'activateSettings', 'title' => $fcc_biolink_editor_copy['tour_theme_button_title'], 'text' => $fcc_biolink_editor_copy['tour_theme_button_text']],
        ['selector' => '#biolinks_themes', 'action' => 'openThemesModal', 'title' => $fcc_biolink_editor_copy['tour_theme_modal_title'], 'text' => $fcc_biolink_editor_copy['tour_theme_modal_text']],
    ],
    'ai_theme' => [
        ['selector' => '#settings-tab', 'action' => 'activateSettings', 'title' => $fcc_biolink_editor_copy['tour_ai_theme_title'], 'text' => $fcc_biolink_editor_copy['tour_ai_theme_text']],
        ['selector' => '#fcc_biolink_theme_button', 'action' => 'activateSettings', 'title' => $fcc_biolink_editor_copy['tour_ai_theme_apply_title'], 'text' => $fcc_biolink_editor_copy['tour_ai_theme_apply_text']],
        ['selector' => '#biolinks_themes', 'action' => 'openThemesModal', 'title' => $fcc_biolink_editor_copy['tour_ai_theme_library_title'], 'text' => $fcc_biolink_editor_copy['tour_ai_theme_library_text']],
    ],
    'ai_copy' => [
        ['selector' => '#blocks-tab', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_ai_copy_title'], 'text' => $fcc_biolink_editor_copy['tour_ai_copy_text']],
        ['selector' => '#fcc_ai_action_panel', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_ai_copy_title'], 'text' => $fcc_biolink_editor_copy['tour_ai_copy_text']],
        ['selector' => '#fcc_ai_block_bundle_button', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_ai_copy_apply_title'], 'text' => $fcc_biolink_editor_copy['tour_ai_copy_apply_text']],
        ['selector' => '#fcc_ai_color_bundle_button', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_ai_layout_apply_title'], 'text' => $fcc_biolink_editor_copy['tour_ai_layout_apply_text']],
    ],
    'reset' => [
        ['selector' => '#settings-tab', 'action' => 'activateSettings', 'title' => $fcc_biolink_editor_copy['tour_reset_title'], 'text' => $fcc_biolink_editor_copy['tour_reset_text']],
        ['selector' => '#reset_biolink_factory_btn', 'action' => 'activateSettings', 'title' => $fcc_biolink_editor_copy['tour_reset_button_title'], 'text' => $fcc_biolink_editor_copy['tour_reset_button_text']],
        ['selector' => '#biolink_preview_iframe', 'title' => $fcc_biolink_editor_copy['tour_reset_preview_title'], 'text' => $fcc_biolink_editor_copy['tour_reset_preview_text']],
    ],
    'forever' => [
        ['selector' => '#blocks-tab', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_forever_title'], 'text' => $fcc_biolink_editor_copy['tour_forever_text']],
        ['selector' => '#fcc_biolink_add_block_button', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_forever_add_block_title'], 'text' => $fcc_biolink_editor_copy['tour_forever_add_block_text']],
        ['selector' => '[data-block-id="link_forever_shop"]', 'action' => 'openAddBlockModal', 'filter_group' => 'forever', 'title' => $fcc_biolink_editor_copy['tour_forever_modal_title'], 'text' => $fcc_biolink_editor_copy['tour_forever_modal_text']],
    ],
    'discount' => [
        ['selector' => '#blocks-tab', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_discount_title'], 'text' => $fcc_biolink_editor_copy['tour_discount_text']],
        ['selector' => '#fcc_biolink_add_block_button', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_discount_add_block_title'], 'text' => $fcc_biolink_editor_copy['tour_discount_add_block_text']],
        ['selector' => '[data-block-card][data-block-id="link_discount"]:not(.d-none)', 'action' => 'openAddBlockModal', 'filter_group' => 'forever', 'filter_goal' => 'product_recommendation', 'title' => $fcc_biolink_editor_copy['tour_discount_modal_title'], 'text' => $fcc_biolink_editor_copy['tour_discount_modal_text'], 'resource_url' => 'https://www.youtube.com/watch?v=8tBJiDu1EWc', 'resource_label' => $fcc_biolink_editor_copy['tour_discount_modal_resource']],
        ['selector' => '#create_biolink_link_discount .modal-content', 'action' => 'openBlockCreateModal', 'trigger_selector' => '[data-block-card][data-block-id="link_discount"]:not(.d-none) button[data-target="#create_biolink_link_discount"]', 'modal_selector' => '#create_biolink_link_discount', 'title' => $fcc_biolink_editor_copy['tour_discount_create_title'], 'text' => $fcc_biolink_editor_copy['tour_discount_create_text']],
        ['selector' => '#create_biolink_link_discount .alert.alert-primary', 'action' => 'openBlockCreateModal', 'modal_selector' => '#create_biolink_link_discount', 'title' => $fcc_biolink_editor_copy['tour_discount_notice_title'], 'text' => $fcc_biolink_editor_copy['tour_discount_notice_text'], 'resource_url' => 'https://www.youtube.com/watch?v=8tBJiDu1EWc', 'resource_label' => $fcc_biolink_editor_copy['tour_discount_modal_resource']],
        ['selector' => '#create_biolink_link_discount #link_location_url', 'action' => 'openBlockCreateModal', 'modal_selector' => '#create_biolink_link_discount', 'title' => $fcc_biolink_editor_copy['tour_discount_url_title'], 'text' => $fcc_biolink_editor_copy['tour_discount_url_text']],
        ['selector' => '#create_biolink_link_discount #link_name', 'action' => 'openBlockCreateModal', 'modal_selector' => '#create_biolink_link_discount', 'title' => $fcc_biolink_editor_copy['tour_discount_name_title'], 'text' => $fcc_biolink_editor_copy['tour_discount_name_text']],
        ['selector' => '#create_biolink_link_discount input[name="apply_to_all_products"][value="1"]', 'action' => 'openBlockCreateModal', 'modal_selector' => '#create_biolink_link_discount', 'title' => $fcc_biolink_editor_copy['tour_discount_apply_title'], 'text' => $fcc_biolink_editor_copy['tour_discount_apply_text']],
        ['selector' => '#create_biolink_link_discount [data-is-ajax]', 'action' => 'openBlockCreateModal', 'modal_selector' => '#create_biolink_link_discount', 'title' => $fcc_biolink_editor_copy['tour_discount_submit_title'], 'text' => $fcc_biolink_editor_copy['tour_discount_submit_text']],
    ],
    'funnel' => [
        ['selector' => '#blocks-tab', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_funnel_title'], 'text' => $fcc_biolink_editor_copy['tour_funnel_text']],
        ['selector' => '#fcc_biolink_add_block_button', 'action' => 'activateBlocks', 'title' => $fcc_biolink_editor_copy['tour_funnel_add_block_title'], 'text' => $fcc_biolink_editor_copy['tour_funnel_add_block_text']],
        ['selector' => '[data-block-id="lead_funnel"]', 'action' => 'openAddBlockModal', 'filter_group' => 'sales', 'filter_goal' => 'lead_capture', 'title' => $fcc_biolink_editor_copy['tour_funnel_modal_title'], 'text' => $fcc_biolink_editor_copy['tour_funnel_modal_text']],
    ],
];
?>

<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/sortable.js?v=' . PRODUCT_CODE ?>"></script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php /* Custom code: FC-2026-02-27: premium biolink editor styling */ ?>
<?php ob_start() ?>
<style>
    .fcc-biolink-editor-status {
        position: relative;
        overflow: hidden;
        border: 1px solid #d8e3ef;
        border-radius: 1.2rem;
        padding: 1.2rem 1.25rem;
        margin-bottom: 1rem;
        background:
            radial-gradient(circle at top left, rgba(74, 222, 128, 0.10) 0%, rgba(74, 222, 128, 0) 30%),
            radial-gradient(circle at 88% 12%, rgba(59, 130, 246, 0.10) 0%, rgba(59, 130, 246, 0) 26%),
            linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
        box-shadow: 0 .85rem 2rem rgba(15, 23, 42, 0.08);
    }

    .fcc-biolink-editor-status-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .55rem;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #3b82f6;
    }

    .fcc-biolink-editor-status-copy h2 {
        margin: 0 0 .45rem;
        color: #0f172a;
        font-size: clamp(1.45rem, 2vw, 2rem);
        line-height: 1.05;
        letter-spacing: -.04em;
        font-weight: 900;
    }

    .fcc-biolink-editor-status-copy p {
        margin: 0;
        max-width: 64ch;
        color: #516174;
        line-height: 1.68;
    }

    .fcc-biolink-editor-status-url {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-top: .8rem;
        border-radius: 999px;
        padding: .4rem .8rem;
        background: rgba(255, 255, 255, .72);
        border: 1px solid rgba(148, 163, 184, .24);
        color: #1e293b;
        font-size: .82rem;
        font-weight: 700;
        word-break: break-all;
    }

    .fcc-biolink-editor-status-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        margin-top: .95rem;
    }

    .fcc-biolink-editor-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        border-radius: 999px;
        padding: .52rem .85rem;
        background: rgba(255, 255, 255, .74);
        border: 1px solid rgba(148, 163, 184, .18);
        color: #0f172a;
        font-size: .82rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .fcc-biolink-editor-status-pill-label {
        color: #64748b;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .fcc-biolink-editor-tutorial-panel {
        margin-top: 1rem;
        border-radius: 1.05rem;
        padding: 1rem 1.05rem;
        background: rgba(255, 255, 255, .68);
        border: 1px solid rgba(148, 163, 184, .18);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.32);
    }

    .fcc-biolink-editor-tutorial-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: .9rem;
        align-items: flex-start;
    }

    .fcc-biolink-editor-tutorial-header h3 {
        margin: 0 0 .35rem;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .fcc-biolink-editor-tutorial-header p {
        margin: 0;
        max-width: 56ch;
        color: #5b6978;
        font-size: .88rem;
        line-height: 1.65;
    }

    .fcc-biolink-editor-mini-guides {
        display: flex;
        flex-direction: column;
        gap: .6rem;
        margin-top: .95rem;
    }

    .fcc-biolink-editor-mini-guides-row {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
    }

    .fcc-biolink-editor-mini-guides .btn {
        border-radius: 999px;
        min-height: 2.2rem;
        padding-inline: .9rem;
    }

    .fcc-ai-theme-card,
    .fcc-ai-copy-panel {
        border: 1px solid #d9e4ef;
        box-shadow: 0 .65rem 1.5rem rgba(15, 23, 42, .08);
        border-radius: 1rem;
    }

    .fcc-ai-theme-card {
        background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%);
    }

    .fcc-ai-theme-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #0f766e;
    }

    .fcc-ai-theme-summary {
        color: #526173;
        line-height: 1.65;
    }

    .fcc-ai-theme-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
    }

    .fcc-ai-theme-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .45rem .65rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .82);
        border: 1px solid rgba(148, 163, 184, .18);
        color: #0f172a;
        font-size: .8rem;
        font-weight: 700;
    }

    .fcc-ai-theme-chip strong {
        color: #475569;
        font-size: .74rem;
    }

    .fcc-ai-theme-chip-dot {
        width: .85rem;
        height: .85rem;
        border-radius: 999px;
        border: 1px solid rgba(15, 23, 42, .1);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.25);
    }

    .fcc-ai-theme-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
    }

    .fcc-ai-theme-meta {
        color: #64748b;
        font-size: .82rem;
        line-height: 1.6;
    }

    .fcc-ai-copy-panel .list-group-item {
        border-color: rgba(148, 163, 184, .16);
    }

    .fcc-ai-panel-notification {
        margin: 0 0 1rem;
    }

    .fcc-ai-panel-notification:empty {
        display: none;
    }

    .fcc-ai-primary-badge,
    .fcc-ai-copy-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        margin-top: .35rem;
        margin-right: .35rem;
        border-radius: 999px;
        padding: .24rem .55rem;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .fcc-ai-primary-badge {
        background: rgba(16, 185, 129, .12);
        color: #047857;
        border: 1px solid rgba(16, 185, 129, .18);
    }

    .fcc-ai-copy-badge {
        background: rgba(59, 130, 246, .1);
        color: #1d4ed8;
        border: 1px solid rgba(59, 130, 246, .16);
    }

    .fcc-ai-layout-item {
        border-radius: .9rem;
        padding: .8rem .9rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .fcc-ai-evolution-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(12.5rem, 1fr));
        gap: .75rem;
        margin-bottom: 1rem;
    }

    .fcc-ai-evolution-card {
        border-radius: .95rem;
        padding: .9rem .95rem;
        background: linear-gradient(180deg, rgba(248, 250, 252, .95), rgba(241, 245, 249, .9));
        border: 1px solid rgba(148, 163, 184, .2);
    }

    .fcc-ai-evolution-label {
        display: block;
        margin-bottom: .4rem;
        color: #64748b;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .fcc-ai-evolution-value {
        color: #0f172a;
        font-weight: 800;
        line-height: 1.45;
    }

    .fcc-ai-evolution-note {
        margin-top: .35rem;
        color: #64748b;
        font-size: .8rem;
        line-height: 1.55;
    }

    .fcc-ai-evolution-status {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border-radius: 999px;
        padding: .22rem .55rem;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .fcc-ai-evolution-status.pending {
        background: rgba(251, 191, 36, .14);
        color: #92400e;
        border: 1px solid rgba(245, 158, 11, .2);
    }

    .fcc-ai-evolution-status.ready {
        background: rgba(59, 130, 246, .12);
        color: #1d4ed8;
        border: 1px solid rgba(59, 130, 246, .18);
    }

    .fcc-ai-evolution-status.measured {
        background: rgba(16, 185, 129, .12);
        color: #047857;
        border: 1px solid rgba(16, 185, 129, .18);
    }

    .fcc-biolink-editor-ai-workflow {
        margin-top: 1rem;
        border-radius: 1rem;
        padding: 1rem 1.05rem;
        background: rgba(255, 255, 255, .82);
        border: 1px solid rgba(148, 163, 184, .22);
    }

    .fcc-biolink-editor-ai-workflow h4 {
        margin: 0 0 .35rem;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .fcc-biolink-editor-ai-workflow p {
        margin: 0 0 .75rem;
        color: #516174;
        line-height: 1.6;
    }

    .fcc-biolink-editor-ai-workflow ol {
        margin: 0;
        padding-left: 1.1rem;
        color: #1e293b;
    }

    .fcc-biolink-editor-ai-workflow li + li {
        margin-top: .45rem;
    }

    .fcc-ai-block-helper {
        border-radius: .95rem;
        padding: .9rem 1rem;
        background: linear-gradient(135deg, rgba(219, 234, 254, .48), rgba(236, 253, 245, .7));
        border: 1px solid rgba(59, 130, 246, .14);
    }

    .fcc-ai-block-helper-copy {
        color: #4b5563;
        line-height: 1.6;
    }

    .fcc-ai-inline-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        margin-top: .8rem;
    }

    .fcc-ai-inline-suggestion {
        flex: 1 1 18rem;
        min-width: 0;
        border-radius: .85rem;
        padding: .8rem .85rem;
        background: rgba(255, 255, 255, .82);
        border: 1px solid rgba(148, 163, 184, .18);
    }

    .fcc-ai-inline-suggestion-value {
        color: #0f172a;
        font-weight: 700;
        line-height: 1.5;
    }

    .fcc-ai-inline-suggestion-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: .75rem;
    }

    .biolink-editor-toolbar {
        border: 1px solid var(--gray-200);
        box-shadow: 0 0.4rem 1.1rem rgba(0, 0, 0, 0.06);
    }

    .biolink-switch-buttons {
        background: #eef2f7;
        border: 1px solid #d7e0ea;
        border-radius: .75rem;
        padding: .25rem;
        gap: .25rem;
    }

    .biolink-switch-buttons .nav-link {
        border-radius: .6rem;
        color: #3d4a5d;
        font-weight: 600;
        padding: .48rem .9rem;
        transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
    }

    .biolink-switch-buttons .nav-link:not(.active):hover {
        background: rgba(255, 255, 255, .72);
        color: #243042;
    }

    .biolink-switch-buttons .nav-link.active {
        background: #ffffff;
        color: #111827;
        box-shadow: 0 .25rem .65rem rgba(15, 23, 42, .15);
    }

    #settings.tab-pane > .card {
        border: 1px solid #dbe3ed;
        box-shadow: 0 .35rem 1rem rgba(15, 23, 42, .08);
    }

    #biolink_blocks.tab-pane {
        background: linear-gradient(180deg, #f5f8fc 0%, #f0f4fa 100%);
        border: 1px dashed #cad5e3;
        border-radius: .9rem;
        padding: 1rem;
    }

    #biolink_blocks .biolink-editor-block.card,
    #biolink_blocks .biolink_block.card {
        position: relative;
        z-index: 1;
        overflow: visible;
        border: 1px solid var(--gray-300);
        border-radius: .85rem;
        background: linear-gradient(135deg, #ffffff, #f6f8fc);
        box-shadow: 0 0.35rem 1rem rgba(17, 24, 39, 0.08);
        transition: transform .15s ease, box-shadow .15s ease;
    }

    #biolink_blocks .biolink-editor-block.card::before,
    #biolink_blocks .biolink_block.card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), transparent);
        opacity: .45;
    }

    #biolink_blocks .biolink-editor-block.card:hover,
    #biolink_blocks .biolink_block.card:hover {
        z-index: 2;
        transform: translateY(-1px);
        border-color: var(--primary);
        box-shadow: 0 0.5rem 1.25rem rgba(17, 24, 39, 0.13);
    }

    #biolink_blocks .biolink-editor-block.card:focus-within,
    #biolink_blocks .biolink_block.card:focus-within {
        z-index: 1100;
    }

    #biolink_blocks .biolink-editor-block.card .dropdown-menu,
    #biolink_blocks .biolink_block.card .dropdown-menu {
        z-index: 1110;
    }

    #biolink_blocks .biolink-editor-block.card .card-body,
    #biolink_blocks .biolink_block.card .card-body {
        padding-top: 1.1rem;
        padding-bottom: 1.1rem;
    }

    #biolink_blocks .biolink-editor-block.card .biolink-editor-expanded,
    #biolink_blocks .biolink_block.card .biolink-editor-expanded {
        border-top: 1px solid var(--gray-200);
        margin-top: 1rem;
        padding-top: 1rem;
    }

    #biolink_blocks .biolink_block.card.custom-row-inactive {
        background: linear-gradient(135deg, #eef2f7, #e8edf5);
        border-color: #d4dde8;
    }

    #biolink_blocks .biolink_block.card .font-weight-500,
    #biolink_blocks .biolink_block.card a,
    #biolink_blocks .biolink_block.card .small,
    #biolink_blocks .biolink_block.card .text-truncate {
        color: #1f2937 !important;
    }

    #biolink_blocks .biolink_block.card .text-muted {
        color: #5b6472 !important;
    }

    #biolink_blocks .biolink_block.card .custom-row-side-controller {
        z-index: 3;
    }

    body[data-theme-style='dark'] .biolink-editor-toolbar.card,
    body[data-theme-style='dark'] #biolink_blocks .biolink-editor-block.card,
    body[data-theme-style='dark'] #biolink_blocks .biolink_block.card {
        background: linear-gradient(135deg, #1b2330, #232d3d) !important;
        border-color: #3b4a60 !important;
        box-shadow: 0 0.45rem 1.2rem rgba(0, 0, 0, 0.33) !important;
    }

    body[data-theme-style='dark'] .fcc-biolink-editor-status {
        background:
            radial-gradient(circle at top left, rgba(45, 212, 191, 0.12) 0%, rgba(45, 212, 191, 0) 30%),
            radial-gradient(circle at 88% 12%, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0) 26%),
            linear-gradient(180deg, #162130 0%, #0f1724 100%);
        border-color: #33445b;
        box-shadow: 0 .95rem 2.2rem rgba(0, 0, 0, .32);
    }

    body[data-theme-style='dark'] .fcc-biolink-editor-status-copy h2,
    body[data-theme-style='dark'] .fcc-biolink-editor-status-url {
        color: #f3f7fd;
    }

    body[data-theme-style='dark'] .fcc-biolink-editor-status-eyebrow {
        color: #93c5fd;
    }

    body[data-theme-style='dark'] .fcc-biolink-editor-status-copy p {
        color: #b7c4d6;
    }

    body[data-theme-style='dark'] .fcc-biolink-editor-status-pill,
    body[data-theme-style='dark'] .fcc-biolink-editor-tutorial-panel,
    body[data-theme-style='dark'] .fcc-biolink-editor-status-url {
        background: rgba(15, 23, 36, .6);
        border-color: rgba(100, 116, 139, .22);
    }

    body[data-theme-style='dark'] .fcc-biolink-editor-status-pill,
    body[data-theme-style='dark'] .fcc-biolink-editor-tutorial-header h3 {
        color: #f3f7fd;
    }

    body[data-theme-style='dark'] .fcc-biolink-editor-status-pill-label,
    body[data-theme-style='dark'] .fcc-biolink-editor-tutorial-header p {
        color: #b7c4d6;
    }

    body[data-theme-style='dark'] .fcc-ai-theme-card,
    body[data-theme-style='dark'] .fcc-ai-copy-panel {
        background: linear-gradient(180deg, #162130 0%, #101924 100%);
        border-color: #33445b;
        box-shadow: 0 .95rem 2.2rem rgba(0, 0, 0, .24);
    }

    body[data-theme-style='dark'] .fcc-ai-theme-summary,
    body[data-theme-style='dark'] .fcc-ai-theme-meta {
        color: #b7c4d6;
    }

    body[data-theme-style='dark'] .fcc-ai-theme-chip,
    body[data-theme-style='dark'] .fcc-ai-layout-item,
    body[data-theme-style='dark'] .fcc-ai-evolution-card {
        background: rgba(15, 23, 36, .6);
        border-color: rgba(100, 116, 139, .22);
        color: #f3f7fd;
    }

    body[data-theme-style='dark'] .fcc-ai-evolution-label,
    body[data-theme-style='dark'] .fcc-ai-evolution-note {
        color: #b7c4d6;
    }

    body[data-theme-style='dark'] .fcc-ai-evolution-value {
        color: #f8fbff;
    }

    body[data-theme-style='dark'] .fcc-biolink-editor-ai-workflow,
    body[data-theme-style='dark'] .fcc-ai-block-helper,
    body[data-theme-style='dark'] .fcc-ai-inline-suggestion {
        background: rgba(15, 23, 36, .72);
        border-color: rgba(100, 116, 139, .22);
    }

    body[data-theme-style='dark'] .fcc-biolink-editor-ai-workflow h4,
    body[data-theme-style='dark'] .fcc-ai-inline-suggestion-value {
        color: #f3f7fd;
    }

    body[data-theme-style='dark'] .fcc-biolink-editor-ai-workflow p,
    body[data-theme-style='dark'] .fcc-biolink-editor-ai-workflow ol,
    body[data-theme-style='dark'] .fcc-ai-block-helper-copy {
        color: #c7d2e0;
    }

    body[data-theme-style='dark'] .biolink-switch-buttons {
        background: #1a2330;
        border-color: #33445b;
    }

    body[data-theme-style='dark'] .biolink-switch-buttons .nav-link {
        color: #bdc9d9;
    }

    body[data-theme-style='dark'] .biolink-switch-buttons .nav-link:not(.active):hover {
        background: rgba(255, 255, 255, .06);
        color: #e6edf8;
    }

    body[data-theme-style='dark'] .biolink-switch-buttons .nav-link.active {
        background: #2a374a;
        color: #f3f7fd;
        box-shadow: 0 .25rem .7rem rgba(0, 0, 0, .33);
    }

    body[data-theme-style='dark'] #settings.tab-pane > .card {
        border-color: #3b4a60;
        box-shadow: 0 .45rem 1.2rem rgba(0, 0, 0, .32);
    }

    body[data-theme-style='dark'] #biolink_blocks.tab-pane {
        background: linear-gradient(180deg, #182231 0%, #1f2a3a 100%);
        border-color: #3b4a60;
    }

    body[data-theme-style='dark'] #biolink_blocks .biolink-editor-block.card:hover,
    body[data-theme-style='dark'] #biolink_blocks .biolink_block.card:hover {
        border-color: var(--primary) !important;
        box-shadow: 0 0.55rem 1.35rem rgba(0, 0, 0, 0.4) !important;
    }

    body[data-theme-style='dark'] #biolink_blocks .biolink_block.card.custom-row-inactive {
        background: linear-gradient(135deg, #2a3342, #313d4f) !important;
        border-color: #485870 !important;
    }

    body[data-theme-style='dark'] #biolink_blocks .biolink_block.card .font-weight-500,
    body[data-theme-style='dark'] #biolink_blocks .biolink_block.card a,
    body[data-theme-style='dark'] #biolink_blocks .biolink_block.card .small,
    body[data-theme-style='dark'] #biolink_blocks .biolink_block.card .text-truncate {
        color: #f3f6fb !important;
    }

    body[data-theme-style='dark'] #biolink_blocks .biolink_block.card .text-muted,
    body[data-theme-style='dark'] .biolink-editor-toolbar .nav-link:not(.active) {
        color: #b4c0d3 !important;
    }

    button[data-target="#verified_container"],
    #verified_container,
    button[data-target="#branding_container"],
    #branding_container,
    button[data-target="#branded_button_container"],
    #branded_button_container,
    button[data-target="#advanced_container"],
    #advanced_container {
        display: none !important;
    }

    body[data-theme-style='dark'] #biolink_blocks .biolink-editor-block .biolink-editor-expanded,
    body[data-theme-style='dark'] #biolink_blocks .biolink_block.card .biolink-editor-expanded {
        border-top-color: #3b4a60;
    }

    .biolink-preview-iframe-container {
        position: relative;
    }

    .fcc-biolink-preview-coach-notice {
        position: absolute;
        inset: 1rem 1rem auto 1rem;
        z-index: 8;
        display: none;
        align-items: flex-start;
        gap: .8rem;
        border-radius: 1rem;
        border: 1px solid rgba(94, 234, 212, .22);
        background: linear-gradient(135deg, rgba(10, 38, 43, .94), rgba(12, 27, 45, .96));
        box-shadow: 0 1rem 2rem rgba(2, 6, 23, .26);
        padding: .95rem 1rem;
        color: #eff8ff;
    }

    .biolink-preview-iframe-container.is-coach-paused .fcc-biolink-preview-coach-notice {
        display: flex;
    }

    .fcc-biolink-preview-coach-notice-icon {
        width: 2.2rem;
        height: 2.2rem;
        border-radius: .8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        background: rgba(94, 234, 212, .16);
        border: 1px solid rgba(94, 234, 212, .24);
        color: #5eead4;
    }

    .fcc-biolink-preview-coach-notice-title {
        color: #f8fbff;
        font-weight: 800;
        letter-spacing: -.02em;
        margin-bottom: .15rem;
    }

    .fcc-biolink-preview-coach-notice-text {
        margin: 0;
        color: rgba(224, 235, 249, .84);
        font-size: .86rem;
        line-height: 1.5;
    }

    .fcc-biolink-tour-target {
        scroll-margin-top: 6rem;
    }

    .fcc-biolink-tour-active-ancestor {
        position: relative !important;
        z-index: 2051 !important;
        overflow: visible !important;
    }

    .fcc-biolink-tour-active-target {
        position: relative !important;
        z-index: 2052 !important;
        isolation: isolate;
        transform: translateZ(0);
        filter: brightness(1.05) saturate(1.04);
        box-shadow: 0 0 0 2px rgba(73, 227, 207, .98), 0 0 0 10px rgba(112, 244, 228, .18), 0 18px 54px rgba(7, 19, 38, .34) !important;
        border-radius: 1.1rem !important;
    }

    .fcc-biolink-tour-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2050;
        display: none;
        pointer-events: none;
    }

    .fcc-biolink-tour-backdrop.is-visible {
        display: block;
    }

    .fcc-biolink-tour-backdrop-segment {
        position: fixed;
        background: rgba(2, 8, 23, .58);
        backdrop-filter: blur(3px);
        pointer-events: none;
    }

    .fcc-biolink-tour-popover {
        position: fixed;
        z-index: 2055;
        width: min(25rem, calc(100vw - 2rem));
        display: none;
        opacity: 0;
        pointer-events: none;
        border-radius: 1.2rem;
        border: 1px solid rgba(147, 197, 253, .22);
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .18), transparent 30%),
            linear-gradient(180deg, rgba(25, 36, 58, .98), rgba(16, 24, 41, .97));
        box-shadow: 0 30px 80px rgba(2, 8, 23, .44), inset 0 1px 0 rgba(255,255,255,.05);
        padding: 1.05rem 1.05rem 1rem;
        transition: opacity .12s ease;
    }

    .fcc-biolink-tour-popover.is-visible {
        display: block;
    }

    .fcc-biolink-tour-popover.is-ready {
        opacity: 1;
        pointer-events: auto;
    }

    .fcc-biolink-tour-progress {
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

    .fcc-biolink-tour-title {
        color: #f8fbff;
        font-size: 1.12rem;
        font-weight: 800;
        line-height: 1.3;
        margin-bottom: .45rem;
    }

    .fcc-biolink-tour-text {
        color: rgba(236, 244, 255, .94);
        font-size: .94rem;
        line-height: 1.65;
        margin-bottom: 1rem;
    }

    .fcc-biolink-tour-resource {
        display: none;
        align-items: center;
        justify-content: center;
        gap: .55rem;
        width: 100%;
        margin-bottom: 1rem;
        border-radius: .95rem;
        border: 1px solid rgba(73, 227, 207, .22);
        background: rgba(73, 227, 207, .12);
        color: #e8fffb;
        font-weight: 700;
        text-decoration: none;
        padding: .8rem .95rem;
    }

    .fcc-biolink-tour-resource:hover,
    .fcc-biolink-tour-resource:focus {
        color: #ffffff;
        text-decoration: none;
        background: rgba(73, 227, 207, .18);
        border-color: rgba(73, 227, 207, .36);
    }

    .fcc-biolink-tour-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .fcc-biolink-tour-actions-main {
        display: flex;
        gap: .65rem;
        flex-wrap: wrap;
    }

    .fcc-biolink-tour-actions .btn {
        border-radius: .85rem;
    }

    .fcc-biolink-tour-actions .btn-link {
        color: rgba(226, 232, 240, .82) !important;
        text-decoration: none;
    }

    .fcc-biolink-tour-actions .btn-link:hover,
    .fcc-biolink-tour-actions .btn-link:focus {
        color: #ffffff !important;
        text-decoration: none;
    }

    .fcc-biolink-tour-actions .btn-outline-light {
        color: #ecf8ff !important;
        border-color: rgba(147, 197, 253, .28) !important;
        background: rgba(59, 130, 246, .12) !important;
    }

    .fcc-biolink-tour-actions .btn-outline-light:hover,
    .fcc-biolink-tour-actions .btn-outline-light:focus {
        color: #ffffff !important;
        border-color: rgba(147, 197, 253, .48) !important;
        background: rgba(59, 130, 246, .2) !important;
    }

    @media (max-width: 767px) {
        .fcc-biolink-editor-status-pill {
            width: 100%;
        }

        .fcc-biolink-tour-popover {
            left: 1rem !important;
            right: 1rem !important;
            width: auto;
            top: auto !important;
            bottom: 1rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
<?php /* /Custom code: FC-2026-02-27 */ ?>

<?php ob_start() ?>
<?php if($this->user->plan_settings->biolink_blocks_limit != -1 && $data->link_links_result->num_rows > $this->user->plan_settings->biolink_blocks_limit): ?>
    <div class="alert alert-danger">
        <i class="fas fa-fw fa-times-circle text-danger mr-2"></i> <?= sprintf(settings()->payment->is_enabled ? l('global.info_message.plan_feature_limit_removal_with_upgrade') : l('global.info_message.plan_feature_limit_removal'), '<strong>' . $data->link_links_result->num_rows - $this->user->plan_settings->biolink_blocks_limit, mb_strtolower(l('biolinks_blocks.title')) . '</strong>', '<a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '</a>') ?>
    </div>
<?php endif ?>

<div class="row">
    <div class="col-12 col-xl-6">

		<?php
		$active_tab = settings()->links->biolinks_default_active_tab ?? 'settings';
		if(isset($_GET['tab']) && in_array($_GET['tab'], ['settings', 'blocks'])) {
			$active_tab = $_GET['tab'];
		}

        /* Custom code: FC-2026-03-03: keep sections in DOM for JS stability; hidden via CSS for all users */
		$show_admin_only_biolink_sections = true;
		?>

        <div class="fcc-biolink-editor-status fcc-biolink-tour-target" id="fcc_biolink_editor_status">
            <div class="fcc-biolink-editor-status-copy">
                <div class="fcc-biolink-editor-status-eyebrow">
                    <i class="fas fa-fw fa-compass"></i>
                    <span><?= $fcc_biolink_editor_copy['eyebrow'] ?></span>
                </div>
                <h2><?= htmlspecialchars($fcc_biolink_editor_display_name, ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= $fcc_biolink_editor_copy['summary'] ?></p>
                <a href="<?= htmlspecialchars($data->link->full_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer" class="fcc-biolink-editor-status-url">
                    <i class="fas fa-fw fa-up-right-from-square"></i>
                    <span><?= htmlspecialchars($data->link->full_url, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </div>

            <div class="fcc-biolink-editor-status-meta">
                <?php foreach($fcc_biolink_editor_meta_items as $meta_item): ?>
                    <div class="fcc-biolink-editor-status-pill" data-toggle="tooltip" title="<?= htmlspecialchars((string) $meta_item['title'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="<?= $meta_item['icon'] ?>"></i>
                        <span class="fcc-biolink-editor-status-pill-label"><?= $meta_item['label'] ?>:</span>
                        <span><?= htmlspecialchars((string) $meta_item['value'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach ?>
            </div>

        </div>

        <!-- Custom code: FC-2026-02-27: premium toolbar for biolink editor -->
        <div class="card biolink-editor-toolbar mb-4">
            <div class="card-body py-3">
                <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                    <ul class="nav nav-pills biolink-switch-buttons mb-3 mb-sm-0" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link fcc-biolink-tour-target <?= $active_tab == 'settings' ? 'active' : null ?>" id="settings-tab" data-toggle="pill" href="#settings" role="tab" aria-controls="settings" aria-selected="true">
                                <i class="fas fa-fw fa-wrench fa-sm mr-1"></i> <?= l('link.header.settings_tab') ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fcc-biolink-tour-target <?= $active_tab == 'blocks' ? 'active' : null ?>" id="blocks-tab" data-toggle="pill" href="#biolink_blocks" role="tab" aria-controls="links" aria-selected="false">
                                <i class="fas fa-fw fa-th-large fa-sm mr-1"></i> <?= l('link.header.blocks_tab') ?>
                            </a>
                        </li>
                    </ul>

                    <div>
                        <button type="button" id="fcc_biolink_add_block_button" data-toggle="modal" data-target="#biolink_link_create_modal" class="btn btn-primary fcc-biolink-tour-target"><i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('links.create_biolink_block') ?></button>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Custom code: FC-2026-02-27 -->

        <div class="tab-content">
            <div class="tab-pane fade <?= $active_tab == 'settings' ? 'show active' : null ?>" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                <div class="card">
                    <div class="card-body">                        
                        <form id="update_biolink" name="update_biolink" action="" method="post" role="form" enctype="multipart/form-data">
                            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                            <input type="hidden" name="request_type" value="update" />
                            <input type="hidden" name="type" value="biolink" />
                            <input type="hidden" name="link_id" value="<?= $data->link->link_id ?>" />

                            <div class="notification-container"></div>

                            <div class="form-group fcc-biolink-tour-target" id="fcc_biolink_editor_step_url">
                                <label for="url"><i class="fas fa-fw fa-bolt fa-sm text-muted mr-1"></i> <?= l('link.settings.url') ?></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
										<?php if (!empty($data->domains)): ?>
                                            <?php if($fcc_biolink_editor_is_main): ?>
                                                <input type="hidden" name="domain_id" value="<?= (int) ($data->link->domain_id ?? 0) ?>" />
                                            <?php endif ?>
                                            <select name="domain_id" class="appearance-none custom-select form-control input-group-text" <?= $fcc_biolink_editor_is_main ? 'disabled="disabled"' : null ?>>
												<?php if(settings()->links->main_domain_is_enabled || \Altum\Authentication::is_admin()): ?>
                                                    <option value=" " <?= $data->link->domain ? 'selected="selected"' : null ?> data-full-url="<?= SITE_URL ?>"><?= remove_url_protocol_from_url(SITE_URL) ?></option>
												<?php endif ?>

												<?php foreach($data->domains as $row): ?>
                                                    <option value="<?= $row->domain_id ?>" <?= $data->link->domain && $row->domain_id == $data->link->domain->domain_id ? 'selected="selected"' : null ?>  data-full-url="<?= $row->url ?>" data-type="<?= $row->type ?>"><?= remove_url_protocol_from_url($row->url) ?></option>
												<?php endforeach ?>
                                            </select>
										<?php else: ?>
                                            <span class="input-group-text"><?= remove_url_protocol_from_url(SITE_URL) ?></span>
										<?php endif ?>
                                    </div>
                                    <!-- Custom code -->
                                    <input
                                            id="url"
                                            type="text"
                                            class="form-control"
                                            name="url"
                                            placeholder="<?= l('global.url_slug_placeholder') ?>"
                                            value="<?= $data->link->url ?>"
                                            maxlength="256"
                                            onchange="update_this_value(this, get_slug)"
                                            onkeyup="update_this_value(this, get_slug)"
										<?= !$this->user->plan_settings->custom_url || $fcc_biolink_editor_is_main ? 'readonly="readonly"' : null ?>
										<?= $this->user->plan_settings->custom_url ? null : get_plan_feature_disabled_info() ?>
                                    />
                                    <!-- /Custom code -->
                                </div>
                                <small class="form-text text-muted">
                                    <?= l('link.settings.url_help') ?>
                                    <?php if($fcc_biolink_editor_is_main): ?>
                                        <br /><?= l('link.settings.url_help_main_biolink_locked') ?>
                                    <?php endif ?>
                                </small>
                            </div>

							<?php if (!empty($data->domains)): ?>
                                <div id="is_main_link_wrapper" class="form-group custom-control custom-switch <?= $data->link->domain_id && $data->domains[$data->link->domain_id]->type == '0' ? null : 'd-none' ?>">
                                    <?php if($fcc_biolink_editor_is_main && $data->link->domain_id && $data->domains[$data->link->domain_id]->link_id == $data->link->link_id): ?>
                                        <input type="hidden" name="is_main_link" value="1" />
                                    <?php endif ?>
                                    <input id="is_main_link" name="is_main_link" type="checkbox" class="custom-control-input" <?= $data->link->domain_id && $data->domains[$data->link->domain_id]->link_id == $data->link->link_id ? 'checked="checked"' : null ?> <?= $fcc_biolink_editor_is_main ? 'disabled="disabled"' : null ?>>
                                    <label class="custom-control-label" for="is_main_link"><?= l('link.settings.is_main_link') ?></label>
                                    <small class="form-text text-muted">
                                        <?= l('link.settings.is_main_link_help') ?>
                                        <?php if($fcc_biolink_editor_is_main): ?>
                                            <br /><?= l('link.settings.url_help_main_biolink_locked') ?>
                                        <?php endif ?>
                                    </small>
                                </div>
							<?php endif ?>

							<?php if(settings()->links->biolinks_themes_is_enabled): ?>
                                <button id="fcc_biolink_theme_button" class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4 fcc-biolink-tour-target" type="button" data-toggle="modal" data-target="#biolink_themes_modal" aria-expanded="false" aria-controls="theme_container">
                                    <i class="fas fa-fw fa-palette fa-sm mr-1"></i> <?= l('link.settings.theme_header') ?>
                                </button>
                                <small class="form-text text-muted mt-n3 mb-3"><?= l('link.settings.theme_help') ?></small>

                                <!-- Custom code: FC-2026-03-06: factory reset button for biolink template -->
                                <button id="reset_biolink_factory_btn" class="btn btn-block btn-outline-danger font-size-little-small font-weight-450 mb-4 fcc-biolink-tour-target" type="button" data-link-id="<?= $data->link->link_id ?>">
                                    <i class="fas fa-fw fa-undo-alt fa-sm mr-1"></i> <?= l('global.reset') ?> Forever Card Aplikaciju
                                </button>
                                <small class="form-text text-muted mt-n3 mb-3">Ova radnja briše trenutni sadržaj i vraća početni izgled Forever Card Aplikacije.</small>
                                <!-- /Custom code: FC-2026-03-06 -->

                                <div class="collapse" data-parent="#settings" id="theme_container">
                                    <div class="form-group">
                                        <label><i class="fas fa-fw fa-palette fa-sm text-muted mr-1"></i> <?= l('biolink_themes.id') ?></label>
                                        <input type="hidden" id="biolink_theme_id" name="biolink_theme_id" class="form-control" value="<?= $data->link->biolink_theme_id ?? null ?>" />
                                    </div>
                                </div>
							<?php endif ?>

                            <button id="fcc_biolink_settings_customizations_button" class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4 fcc-biolink-tour-target" type="button" data-toggle="collapse" data-target="#customizations_container" aria-expanded="false" aria-controls="customizations_container">
                                <i class="fas fa-fw fa-paint-brush fa-sm mr-1"></i> <?= l('link.settings.customization_header') ?>
                            </button>

                            <div class="collapse" data-parent="#settings" id="customizations_container">
                                <div class="form-group">
                                    <label for="settings_background_type"><i class="fas fa-fw fa-fill fa-sm text-muted mr-1"></i> <?= l('link.settings.background_type') ?></label>
                                    <select id="settings_background_type" name="background_type" class="custom-select">
										<?php foreach($biolink_backgrounds as $key => $value): ?>
                                            <option value="<?= $key ?>" <?= $data->link->settings->background_type == $key ? 'selected="selected"' : null?>><?= l('link.settings.background_type_' . $key) ?></option>
										<?php endforeach ?>
                                    </select>
                                </div>

                                <div id="background_type_preset" class="row form-group" style="margin-right: -7px; margin-left: -7px;">
									<?php foreach($biolink_backgrounds['preset'] as $key => $value): ?>
                                        <label for="settings_background_type_preset_<?= $key ?>" class="m-0 col-3 p-2">
                                            <input type="radio" name="background" value="<?= $key ?>" id="settings_background_type_preset_<?= $key ?>" class="d-none" <?= $data->link->settings->background_type == 'preset' && $data->link->settings->background == $key ? 'checked="checked"' : null ?>/>
                                            <div class="link-background-type-preset" style="<?= $value ?>"></div>
                                        </label>
									<?php endforeach ?>
                                </div>

                                <div id="background_type_preset_abstract" class="row form-group" style="margin-right: -7px; margin-left: -7px;">
									<?php foreach($biolink_backgrounds['preset_abstract'] as $key => $value): ?>
                                        <label for="settings_background_type_preset_abstract_<?= $key ?>" class="m-0 col-3 p-2">
                                            <input type="radio" name="background" value="<?= $key ?>" id="settings_background_type_preset_abstract_<?= $key ?>" class="d-none" <?= $data->link->settings->background_type == 'preset_abstract' && $data->link->settings->background == $key ? 'checked="checked"' : null ?>/>
                                            <div class="link-background-type-preset" style="<?= $value ?>"></div>
                                        </label>
									<?php endforeach ?>
                                </div>

                                <div id="background_type_gradient">
                                    <div class="form-group">
                                        <label for="settings_background_type_gradient_color_one"><?= l('link.settings.background_type_gradient_color_one') ?></label>
                                        <input type="hidden" id="settings_background_type_gradient_color_one" name="background_color_one" class="form-control" value="<?= $data->link->settings->background_color_one ?? '#000000' ?>" />
                                        <div id="settings_background_type_gradient_color_one_pickr"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="settings_background_type_gradient_color_two"><?= l('link.settings.background_type_gradient_color_two') ?></label>
                                        <input type="hidden" id="settings_background_type_gradient_color_two" name="background_color_two" class="form-control" value="<?= $data->link->settings->background_color_two ?? '#000000' ?>" />
                                        <div id="settings_background_type_gradient_color_two_pickr"></div>
                                    </div>
                                </div>

                                <div id="background_type_color">
                                    <div class="form-group">
                                        <label for="settings_background_type_color"><?= l('link.settings.background_type_color') ?></label>
                                        <input type="hidden" id="settings_background_type_color" name="background" class="form-control" value="<?= is_string($data->link->settings->background) ? $data->link->settings->background : '#000000' ?>" />
                                        <div id="settings_background_type_color_pickr"></div>
                                    </div>
                                </div>

                                <div id="background_type_image" data-image-container="background">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col">
                                                <input id="background_type_image_input" type="file" name="background" accept="<?= \Altum\Uploads::get_whitelisted_file_extensions_accept('biolink_background') ?>" class="form-control-file altum-file-input" />
                                            </div>

											<?php if($data->link->settings->background_type == 'image' && is_string($data->link->settings->background) && !string_ends_with('.mp4', $data->link->settings->background)): ?>
                                                <div class="col-3 d-flex justify-content-center align-items-center">
                                                    <a href="<?= \Altum\Uploads::get_full_url('backgrounds') . $data->link->settings->background ?>" target="_blank" data-toggle="tooltip" title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                                        <img id="background_type_image_preview" src="<?= \Altum\Uploads::get_full_url('backgrounds') . $data->link->settings->background ?>" data-default-src="<?= \Altum\Uploads::get_full_url('backgrounds') . $data->link->settings->background ?>" class="altum-file-input-preview rounded" loading="lazy" />
                                                    </a>
                                                </div>
											<?php endif ?>
                                        </div>
                                        <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('biolink_background')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->background_size_limit) ?></small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="background_attachment"><i class="fas fa-fw fa-print fa-sm text-muted mr-1"></i> <?= l('link.settings.background_attachment') ?></label>
                                    <div class="row btn-group-toggle m-n2" data-toggle="buttons">
										<?php foreach(['scroll', 'fixed'] as $background_attachment): ?>
                                            <div class="p-2 col-6">
                                                <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= $data->link->settings->background_attachment == $background_attachment ? 'active"' : null?>">
                                                    <input type="radio" name="background_attachment" value="<?= $background_attachment ?>" class="custom-control-input" <?= ($data->link->settings->background_attachment ?? null) == $background_attachment ? 'checked="checked"' : null?> />
													<?= l('link.settings.background_attachment.' . $background_attachment) ?>
                                                </label>
                                            </div>
										<?php endforeach ?>
                                    </div>
                                </div>

                                <div class="form-group" data-range-counter data-range-counter-suffix="px">
                                    <label for="background_blur"><i class="fas fa-fw fa-low-vision fa-sm text-muted mr-1"></i> <?= l('link.settings.background_blur') ?></label>
                                    <input id="background_blur" type="range"  min="0" max="30" class="form-control-range" name="background_blur" value="<?= $data->link->settings->background_blur ?? 0 ?>" />
                                </div>

                                <div class="form-group" data-range-counter data-range-counter-suffix="%">
                                    <label for="background_brightness"><i class="fas fa-fw fa-sun fa-sm text-muted mr-1"></i> <?= l('link.settings.background_brightness') ?></label>
                                    <input id="background_brightness" type="range"  min="0" max="150" class="form-control-range" name="background_brightness" value="<?= $data->link->settings->background_brightness ?? 100 ?>" />
                                </div>

                                <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= settings()->links->favicon_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->links->favicon_size_limit) ?>">
                                    <label for="favicon"><i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('link.settings.favicon') ?></label>
									<?= include_view(THEME_PATH . 'views/partials/custom_file_image_input.php', ['uploads_file_key' => 'favicons', 'file_key' => 'favicon', 'already_existing_image' => $data->link->settings->favicon, 'image_container' => 'favicon', 'input_data' => 'data-crop data-aspect-ratio="1"']) ?>
									<?= \Altum\Alerts::output_field_error('favicon') ?>
                                    <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('favicons')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->favicon_size_limit) ?></small>
                                </div>

                                <div <?= $this->user->plan_settings->fonts ? null : get_plan_feature_disabled_info() ?>>
                                    <div class="<?= $this->user->plan_settings->fonts ? null : 'container-disabled' ?>">

										<?php foreach(settings()->links->biolinks_fonts as $font_key => $font): ?>
											<?php if($font->css_url): ?>
												<?php ob_start() ?>
                                                <link href="<?= $font->css_url ?>" rel="stylesheet">
												<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
											<?php endif ?>
										<?php endforeach ?>

                                        <div class="form-group">
                                            <label for="settings_font"><i class="fas fa-fw fa-pen-nib fa-sm text-muted mr-1"></i> <?= l('link.settings.font') ?></label>
                                            <div class="row btn-group-toggle m-n2" data-toggle="buttons">
												<?php foreach(settings()->links->biolinks_fonts as $font_key => $font): ?>
                                                    <div class="p-2 col-6 col-lg-4 p-2 h-100">
                                                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate mb-0 <?= ($data->link->settings->font ?? 'default') == $font_key ? 'active"' : null?>" style="font-family: <?= $font->font_family ?> !important;">
                                                            <input type="radio" name="font" value="<?= $font_key ?>" class="custom-control-input" <?= ($data->link->settings->font ?? 'default') == $font_key ? 'checked="checked"' : null?> required="required" data-font-family="<?= $font->font_family ?>" data-font-css-url="<?= $font->css_url ?>" />
															<?= $font->name ?>
                                                        </label>
                                                    </div>
												<?php endforeach ?>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="settings_font_size"><i class="fas fa-fw fa-font fa-sm text-muted mr-1"></i> <?= l('link.settings.font_size') ?></label>
                                            <div class="input-group">
                                                <input id="settings_font_size" type="number" min="12" max="22" name="font_size" class="form-control" value="<?= $data->link->settings->font_size ?>" />
                                                <div class="input-group-append">
                                                    <span class="input-group-text">px</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="settings_width"><i class="fas fa-fw fa-arrows-left-right fa-sm text-muted mr-1"></i> <?= l('link.settings.width') ?></label>
                                    <div class="row btn-group-toggle m-n2" data-toggle="buttons">
										<?php foreach(['6', '8', '10', '12'] as $key): ?>
                                            <div class="p-2 col-12 col-lg-4 p-2 h-100">
                                                <label class="btn btn-light btn-block font-size-small mb-0 text-truncate mb-0 <?= ($data->link->settings->width ?? '8') == $key ? 'active"' : null?>">
                                                    <input type="radio" name="width" value="<?= $key ?>" class="custom-control-input" <?= ($data->link->settings->width ?? '8') == $key ? 'checked="checked"' : null?> required="required" />
													<?= l('link.settings.width.' . $key) ?>
                                                </label>
                                            </div>
										<?php endforeach ?>
                                    </div>
                                    <small class="form-text text-muted"><?= l('link.settings.width_help') ?></small>
                                </div>

                                <div class="form-group">
                                    <label for="settings_block_spacing"><i class="fas fa-fw fa-arrows-up-down fa-sm text-muted mr-1"></i> <?= l('link.settings.block_spacing') ?></label>
                                    <div class="row btn-group-toggle m-n2" data-toggle="buttons">
										<?php foreach(['1', '2', '3',] as $key): ?>
                                            <div class="p-2 col-12 col-lg-4 p-2 h-100">
                                                <label class="btn btn-light btn-block font-size-small mb-0 text-truncate mb-0 <?= ($data->link->settings->block_spacing ?? '2') == $key ? 'active"' : null?>">
                                                    <input type="radio" name="block_spacing" value="<?= $key ?>" class="custom-control-input" <?= ($data->link->settings->block_spacing ?? '2') == $key ? 'checked="checked"' : null?> required="required" />
													<?= l('link.settings.block_spacing.' . $key) ?>
                                                </label>
                                            </div>
										<?php endforeach ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="settings_hover_animation"><i class="fas fa-fw fa-arrow-pointer fa-sm text-muted mr-1"></i> <?= l('link.settings.hover_animation') ?></label>
                                    <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                                        <div class="p-2 col-12 col-lg-4 p-2 h-100">
                                            <label class="btn btn-light btn-block font-size-small mb-0 text-truncate mb-0 <?= ($data->link->settings->hover_animation ?? 'smooth') == 'false' ? 'active"' : null?>">
                                                <input type="radio" name="hover_animation" value="false" class="custom-control-input" <?= ($data->link->settings->hover_animation ?? 'smooth') == 'false' ? 'checked="checked"' : null?> required="required" />
												<?= l('global.none') ?>
                                            </label>
                                        </div>

										<?php foreach(['smooth', 'instant',] as $key): ?>
                                            <div class="col-12 col-lg-4 p-2 h-100">
                                                <label class="btn btn-light btn-block text-truncate mb-0 <?= ($data->link->settings->hover_animation ?? 'smooth') == $key ? 'active"' : null?>">
                                                    <input type="radio" name="hover_animation" value="<?= $key ?>" class="custom-control-input" <?= ($data->link->settings->hover_animation ?? 'smooth') == $key ? 'checked="checked"' : null?> required="required" />
													<?= l('link.settings.hover_animation.' . $key) ?>
                                                </label>
                                            </div>
										<?php endforeach ?>
                                    </div>
                                </div>

                            </div>

                            <?php if($show_admin_only_biolink_sections): ?>
                            <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4" type="button" data-toggle="collapse" data-target="#verified_container" aria-expanded="false" aria-controls="verified_container">
                                <i class="fas fa-fw fa-check-circle fa-sm mr-1"></i> <?= l('link.settings.verified_header') ?>
                            </button>

                            <div class="collapse" data-parent="#settings" id="verified_container">
								<?php if(!$data->link->is_verified): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-fw fa-info-circle mr-1"></i>
										<?php if(settings()->email_notifications->contact && !empty(settings()->email_notifications->emails)): ?>
											<?= sprintf(l('link.settings.verified_help'), '<a href="' . url('contact') . '" class="font-weight-bold" target="_blank">', '</a>') ?>
										<?php else: ?>
											<?= sprintf(l('link.settings.verified_help'), '', '') ?>
										<?php endif ?>
                                    </div>
								<?php endif ?>

                                <div <?= $data->link->is_verified ? null : get_plan_feature_disabled_info(false) ?>>
                                    <div class="<?= $data->link->is_verified ? null : 'container-disabled' ?>">

                                        <div class="form-group">
                                            <label for="settings_verified_location"><i class="fas fa-fw fa-check-circle fa-sm text-muted mr-1"></i> <?= l('link.settings.verified_location') ?></label>
                                            <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                                                <div class="col-12 col-lg-4 p-2 h-100">
                                                    <label class="btn btn-light btn-block font-size-small mb-0 text-truncate mb-0 <?= $data->link->settings->verified_location == '' ? 'active"' : null?>">
                                                        <input type="radio" name="verified_location" value="" class="custom-control-input" <?= $data->link->settings->verified_location == 'false' ? 'checked="checked"' : null?> />
														<?= l('global.none') ?>
                                                    </label>
                                                </div>

												<?php foreach(['top', 'bottom',] as $key): ?>
                                                    <div class="col-12 col-lg-4 p-2 h-100">
                                                        <label class="btn btn-light btn-block text-truncate mb-0 <?= $data->link->settings->verified_location == $key ? 'active"' : null?>">
                                                            <input type="radio" name="verified_location" value="<?= $key ?>" class="custom-control-input" <?= $data->link->settings->verified_location == $key ? 'checked="checked"' : null?> />
															<?= l('link.settings.verified_location.' . $key) ?>
                                                        </label>
                                                    </div>
												<?php endforeach ?>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <?php endif ?>

                            <?php if($show_admin_only_biolink_sections): ?>
                            <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4" type="button" data-toggle="collapse" data-target="#branding_container" aria-expanded="false" aria-controls="branding_container">
                                <i class="fas fa-fw fa-random fa-sm mr-1"></i> <?= l('link.settings.branding_header') ?>
                            </button>

                            <div class="collapse" data-parent="#settings" id="branding_container">
                                <div <?= $this->user->plan_settings->removable_branding ? null : get_plan_feature_disabled_info() ?>>
                                    <div class="<?= $this->user->plan_settings->removable_branding ? null : 'container-disabled' ?>">
                                        <div class="form-group custom-control custom-switch">
                                            <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="display_branding"
                                                    name="display_branding"
												<?= !$this->user->plan_settings->removable_branding ? 'disabled="disabled"': null ?>
												<?= $data->link->settings->display_branding ? 'checked="checked"' : null ?>
                                            >
                                            <label class="custom-control-label" for="display_branding"><?= l('link.settings.display_branding') ?></label>
                                        </div>
                                    </div>
                                </div>

                                <div <?= $this->user->plan_settings->custom_branding ? null : get_plan_feature_disabled_info() ?>>
                                    <div class="<?= $this->user->plan_settings->custom_branding ? null : 'container-disabled' ?>">
                                        <div class="form-group">
                                            <label for="branding_name"><i class="fas fa-fw fa-random fa-sm text-muted mr-1"></i> <?= l('link.settings.branding.name') ?></label>
                                            <input id="branding_name" type="text" class="form-control" name="branding_name" value="<?= $data->link->settings->branding->name ?? '' ?>" maxlength="128" />
                                            <small class="form-text text-muted"><?= l('link.settings.branding.name_help') ?></small>
                                        </div>

                                        <div id="branding_url_text_color" class="<?= $data->link->settings->branding->name ? null : 'container-disabled' ?>">
                                            <div class="form-group">
                                                <label for="branding_url"><i class="fas fa-fw fa-link fa-sm text-muted mr-1"></i> <?= l('link.settings.branding.url') ?></label>
                                                <input id="branding_url" type="text" class="form-control" name="branding_url" value="<?= $data->link->settings->branding->url ?? '' ?>" maxlength="2048" placeholder="<?= l('global.url_placeholder') ?>" />
                                            </div>

                                            <div class="form-group">
                                                <label for="settings_text_color"><i class="fas fa-fw fa-paint-brush fa-sm text-muted mr-1"></i> <?= l('link.settings.text_color') ?></label>
                                                <input type="hidden" id="settings_text_color" name="text_color" class="form-control" value="<?= $data->link->settings->text_color ?>" required="required" />
                                                <div id="settings_text_color_pickr"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif ?>

							<?php if(settings()->links->pixels_is_enabled): ?>
                                <button id="fcc_biolink_settings_pixels_button" class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4 fcc-biolink-tour-target" type="button" data-toggle="collapse" data-target="#pixels_container" aria-expanded="false" aria-controls="pixels_container">
                                    <i class="fas fa-fw fa-adjust fa-sm mr-1"></i> <?= l('link.settings.pixels_header') ?>
                                </button>

                                <div class="collapse" data-parent="#settings" id="pixels_container">
                                    <div class="form-group">
                                        <div class="d-flex flex-wrap flex-row justify-content-between">
                                            <label><i class="fas fa-fw fa-sm fa-adjust text-muted mr-1"></i> <?= l('link.settings.pixels_ids') ?></label>
                                            <a href="<?= url('pixel-create') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('pixels.create') ?></a>
                                        </div>

                                        <div class="row">
											<?php $available_pixels = require APP_PATH . 'includes/pixels.php'; ?>
											<?php foreach($data->pixels as $pixel): ?>
                                                <div class="col-12 col-lg-6">
                                                    <div class="custom-control custom-checkbox my-2">
                                                        <input id="pixel_id_<?= $pixel->pixel_id ?>" name="pixels_ids[]" value="<?= $pixel->pixel_id ?>" type="checkbox" class="custom-control-input" <?= in_array($pixel->pixel_id, $data->link->pixels_ids) ? 'checked="checked"' : null ?>>
                                                        <label class="custom-control-label d-flex align-items-center" for="pixel_id_<?= $pixel->pixel_id ?>">
                                                            <span class="text-truncate" title="<?= $pixel->name ?>"><?= $pixel->name ?></span>
                                                            <small class="badge badge-light ml-1" data-toggle="tooltip" title="<?= $available_pixels[$pixel->type]['name'] ?>">
                                                                <i class="<?= $available_pixels[$pixel->type]['icon'] ?> fa-fw fa-sm" style="color: <?= $available_pixels[$pixel->type]['color'] ?>"></i>
                                                            </small>
                                                        </label>
                                                    </div>
                                                </div>
											<?php endforeach ?>
                                        </div>
                                    </div>
                                </div>
							<?php endif ?>

                            <button id="fcc_biolink_settings_utm_button" class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4 fcc-biolink-tour-target" type="button" data-toggle="collapse" data-target="#utm_container" aria-expanded="false" aria-controls="utm_container">
                                <i class="fas fa-fw fa-keyboard fa-sm mr-1"></i> <?= l('link.settings.utm_header') ?>
                            </button>

                            <div class="collapse" data-parent="#settings" id="utm_container">
                                <div <?= $this->user->plan_settings->utm ? null : get_plan_feature_disabled_info() ?>>
                                    <div class="<?= $this->user->plan_settings->utm ? null : 'container-disabled' ?>">
                                        <div class="form-group">
                                            <label for="utm_source"><i class="fas fa-fw fa-sitemap fa-sm text-muted mr-1"></i> <?= l('link.settings.utm_source') ?></label>
                                            <input id="utm_source" type="text" class="form-control" name="utm_source" value="<?= $data->link->settings->utm->source ?? '' ?>" maxlength="128" placeholder="<?= l('link.settings.utm_source_placeholder') ?>" />
                                        </div>

                                        <div class="form-group">
                                            <label for="utm_medium"><i class="fas fa-fw fa-inbox fa-sm text-muted mr-1"></i> <?= l('link.settings.utm_medium') ?></label>
                                            <input id="utm_medium" type="text" class="form-control" name="utm_medium" value="<?= $data->link->settings->utm->medium ?? '' ?>" maxlength="128" placeholder="<?= l('link.settings.utm_medium_placeholder') ?>" />
                                        </div>

                                        <div class="form-group">
                                            <label for="utm_campaign"><i class="fas fa-fw fa-bullhorn fa-sm text-muted mr-1"></i> <?= l('link.settings.utm_campaign') ?></label>
                                            <input id="utm_campaign" type="text" class="form-control" name="utm_campaign" value="<?= l('link.settings.utm_campaign_placeholder_automatic') ?>" maxlength="128" readonly="readonly" />
                                        </div>

                                        <div class="form-group">
                                            <label for="utm_preview"><i class="fas fa-fw fa-eye fa-sm text-muted mr-1"></i> <?= l('link.settings.utm_preview') ?></label>
                                            <input id="utm_preview" type="text" class="form-control-plaintext" name="utm_preview" readonly="readonly" />
                                            <small class="form-text text-muted"><?= l('link.settings.utm_preview_help') ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button id="fcc_biolink_settings_protection_button" class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4 fcc-biolink-tour-target" type="button" data-toggle="collapse" data-target="#protection_container" aria-expanded="false" aria-controls="protection_container">
                                <i class="fas fa-fw fa-user-shield fa-sm mr-1"></i> <?= l('link.settings.protection_header') ?>
                            </button>

                            <div class="collapse" data-parent="#settings" id="protection_container">

                                <div <?= $this->user->plan_settings->password ? null : get_plan_feature_disabled_info() ?>>
                                    <div class="<?= $this->user->plan_settings->password ? null : 'container-disabled' ?>">
                                        <div class="form-group" data-password-toggle-view data-password-toggle-view-show="<?= l('global.show') ?>" data-password-toggle-view-hide="<?= l('global.hide') ?>">
                                            <label for="qweasdzxc"><i class="fas fa-fw fa-key fa-sm text-muted mr-1"></i> <?= l('global.password') ?></label>
                                            <input id="qweasdzxc" type="password" class="form-control" name="qweasdzxc" value="<?= $data->link->settings->password ?>" autocomplete="new-password" <?= !$this->user->plan_settings->password ? 'disabled="disabled"': null ?> />
                                            <small class="form-text text-muted"><?= l('link.settings.password_help') ?></small>
                                        </div>
                                    </div>
                                </div>

                                <div <?= $this->user->plan_settings->sensitive_content ? null : get_plan_feature_disabled_info() ?>>
                                    <div class="<?= $this->user->plan_settings->sensitive_content ? null : 'container-disabled' ?>">
                                        <div class="form-group custom-control custom-switch">
                                            <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="sensitive_content"
                                                    name="sensitive_content"
												<?= !$this->user->plan_settings->sensitive_content ? 'disabled="disabled"': null ?>
												<?= $data->link->settings->sensitive_content ? 'checked="checked"' : null ?>
                                            >
                                            <label class="custom-control-label" for="sensitive_content"><?= l('link.settings.sensitive_content') ?></label>
                                            <small class="form-text text-muted"><?= l('link.settings.sensitive_content_help') ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button id="fcc_biolink_settings_seo_button" class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4 fcc-biolink-tour-target" type="button" data-toggle="collapse" data-target="#seo_container" aria-expanded="false" aria-controls="seo_container">
                                <i class="fas fa-fw fa-search-plus fa-sm mr-1"></i> <?= l('link.settings.seo_header') ?>
                            </button>

                            <div class="collapse" data-parent="#settings" id="seo_container">
                                <div <?= $this->user->plan_settings->seo ? null : get_plan_feature_disabled_info() ?>>
                                    <div class="<?= $this->user->plan_settings->seo ? null : 'container-disabled' ?>">
                                        <div class="form-group custom-control custom-switch">
                                            <input id="seo_block" name="seo_block" type="checkbox" class="custom-control-input" <?= $data->link->settings->seo->block ? 'checked="checked"' : null ?>>
                                            <label class="custom-control-label" for="seo_block"><?= l('link.settings.seo_block') ?></label>
                                            <small class="form-text text-muted"><?= l('link.settings.seo_block_help') ?></small>
                                        </div>

                                        <div class="form-group">
                                            <label for="seo_title"><i class="fas fa-fw fa-heading fa-sm text-muted mr-1"></i> <?= l('link.settings.seo_title') ?></label>
                                            <input id="seo_title" type="text" class="form-control" name="seo_title" value="<?= $data->link->settings->seo->title ?? '' ?>" maxlength="70" />
                                            <small class="form-text text-muted"><?= l('link.settings.seo_title_help') ?></small>
                                        </div>

                                        <div class="form-group">
                                            <label for="seo_meta_description"><i class="fas fa-fw fa-paragraph fa-sm text-muted mr-1"></i> <?= l('link.settings.seo_meta_description') ?></label>
                                            <input id="seo_meta_description" type="text" class="form-control" name="seo_meta_description" value="<?= $data->link->settings->seo->meta_description ?? '' ?>" maxlength="160" />
                                            <small class="form-text text-muted"><?= l('link.settings.seo_meta_description_help') ?></small>
                                        </div>

                                        <div class="form-group">
                                            <label for="seo_meta_keywords"><i class="fas fa-fw fa-file-word fa-sm text-muted mr-1"></i> <?= l('link.settings.seo_meta_keywords') ?></label>
                                            <input id="seo_meta_keywords" type="text" class="form-control" name="seo_meta_keywords" value="<?= $data->link->settings->seo->meta_keywords ?? '' ?>" maxlength="160" />
                                        </div>

                                        <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= settings()->links->seo_image_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->links->seo_image_size_limit) ?>">
                                            <label for="seo_image"><i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('link.settings.seo_image') ?></label>
											<?= include_view(THEME_PATH . 'views/partials/custom_file_image_input.php', ['uploads_file_key' => 'biolink_seo_image', 'file_key' => 'seo_image', 'already_existing_image' => $data->link->settings->seo->image, 'image_container' => 'seo_image', 'input_data' => 'data-crop data-aspect-ratio="1.91"']) ?>
											<?= \Altum\Alerts::output_field_error('seo_image') ?>
                                            <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('biolink_seo_image')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->seo_image_size_limit) ?></small>
                                        </div>

                                        <div class="form-group">
                                            <label for="language_code"><i class="fas fa-fw fa-sm fa-language text-muted mr-1"></i> <?= l('link.settings.language_code') ?></label>
                                            <select id="language_code" name="language_code" class="custom-select">
                                                <?php foreach(get_locale_languages_array() as $locale => $language): ?>
                                                    <option value="<?= $locale ?>" <?= ($data->link->settings->language_code ?? (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : \Altum\Language::$default_code)) == $locale ? 'selected="selected"' : null?>><?= $language ?></option>
                                                <?php endforeach ?>
                                            </select>
                                            <small class="form-text text-muted"><?= l('link.settings.language_code_help') ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

							<?php if(\Altum\Plugin::is_active('pwa') && settings()->pwa->is_enabled): ?>
                                <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4" type="button" data-toggle="collapse" data-target="#pwa_container" aria-expanded="false" aria-controls="pwa_container">
                                    <i class="fas fa-fw fa-mobile-alt fa-sm mr-1"></i> <?= l('link.settings.pwa_header') ?>
                                </button>

                                <div class="collapse" data-parent="#settings" id="pwa_container">
                                    <div class="alert alert-info">
                                        <i class="fas fa-fw fa-info-circle mr-1"></i> <?= l('link.settings.pwa_help') ?>
                                    </div>

                                    <div <?= !$this->user->plan_settings->custom_pwa_is_enabled ? get_plan_feature_disabled_info() : null ?>>
                                        <div class="<?= !$this->user->plan_settings->custom_pwa_is_enabled ? 'container-disabled' : null ?>">

                                            <div class="form-group custom-control custom-switch">
                                                <input
                                                        type="checkbox"
                                                        class="custom-control-input"
                                                        id="pwa_is_enabled"
                                                        name="pwa_is_enabled"
													<?= $data->link->settings->pwa_is_enabled ? 'checked="checked"' : null ?>
													<?= !$this->user->plan_settings->custom_pwa_is_enabled ? 'disabled="disabled"' : null ?>
                                                >
                                                <label class="custom-control-label" for="pwa_is_enabled"><?= l('link.settings.pwa_is_enabled') ?></label>
                                            </div>

                                            <div class="form-group custom-control custom-switch">
                                                <input
                                                        type="checkbox"
                                                        class="custom-control-input"
                                                        id="pwa_display_install_bar"
                                                        name="pwa_display_install_bar"
													<?= $data->link->settings->pwa_display_install_bar ? 'checked="checked"' : null ?>
													<?= !$this->user->plan_settings->custom_pwa_is_enabled ? 'disabled="disabled"' : null ?>
                                                >
                                                <label class="custom-control-label" for="pwa_display_install_bar"><?= l('link.settings.pwa_display_install_bar') ?></label>
                                            </div>

                                            <div class="form-group">
                                                <label for="pwa_display_install_bar_delay"><i class="fas fa-fw fa-bars fa-sm text-muted mr-1"></i> <?= l('link.settings.pwa_display_install_bar_delay') ?></label>
                                                <div class="input-group">
                                                    <input id="pwa_display_install_bar_delay" type="number" min="0" class="form-control" name="pwa_display_install_bar_delay" value="<?= $data->link->settings->pwa_display_install_bar_delay ?? 3 ?>" />
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"><?= l('global.date.seconds') ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= settings()->links->pwa_icon_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->links->pwa_icon_size_limit) ?>">
                                                <label for="pwa_icon"><i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('link.settings.pwa_icon') ?></label>
												<?= include_view(THEME_PATH . 'views/partials/custom_file_image_input.php', ['uploads_file_key' => 'app_icon', 'file_key' => 'pwa_icon', 'already_existing_image' => $data->link->settings->pwa_icon, 'image_container' => 'pwa_icon']) ?>
												<?= \Altum\Alerts::output_field_error('pwa_icon') ?>
                                                <small class="form-text text-muted"><?= l('link.settings.pwa_icon_help') ?><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('app_icon')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->pwa_icon_size_limit) ?></small>
                                            </div>

                                            <div class="form-group">
                                                <label for="pwa_theme_color"><i class="fas fa-fw fa-paint-brush fa-sm text-muted mr-1"></i> <?= l('link.settings.pwa_theme_color') ?></label>
                                                <input type="hidden" id="pwa_theme_color" name="pwa_theme_color" class="form-control" value="<?= $data->link->settings->pwa_theme_color ?? '#000000' ?>" required="required" data-color-picker />
                                                <div id="settings_pwa_theme_color_pickr"></div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
							<?php endif ?>

                            <?php if($show_admin_only_biolink_sections): ?>
                            <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4" type="button" data-toggle="collapse" data-target="#branded_button_container" aria-expanded="false" aria-controls="branded_button_container">
                                <i class="fas fa-fw fa-circle fa-sm mr-1"></i> <?= l('link.settings.branded_button_header') ?>
                            </button>

                            <div class="collapse" data-parent="#settings" id="branded_button_container">
                                <div <?= !$this->user->plan_settings->branded_button_is_enabled ? get_plan_feature_disabled_info() : null ?>>
                                    <div class="<?= !$this->user->plan_settings->branded_button_is_enabled ? 'container-disabled' : null ?>">
                                        <div class="form-group custom-control custom-switch">
                                            <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="branded_button_is_enabled"
                                                    name="branded_button_is_enabled"
												<?= $data->link->settings->branded_button_is_enabled ? 'checked="checked"' : null ?>
												<?= !$this->user->plan_settings->branded_button_is_enabled ? 'disabled="disabled"' : null ?>
                                            >
                                            <label class="custom-control-label" for="branded_button_is_enabled"><?= l('link.settings.branded_button_is_enabled') ?></label>
                                        </div>

                                        <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= settings()->links->favicon_size_limit ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->links->favicon_size_limit) ?>">
                                            <label for="branded_button_icon"><i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('link.settings.branded_button_icon') ?></label>
											<?= include_view(THEME_PATH . 'views/partials/custom_file_image_input.php', ['uploads_file_key' => 'branded_button_icon', 'file_key' => 'branded_button_icon', 'already_existing_image' => $data->link->settings->branded_button_icon, 'image_container' => 'branded_button_icon']) ?>
											<?= \Altum\Alerts::output_field_error('branded_button_icon') ?>
                                            <small class="form-text text-muted"><?= l('link.settings.branded_button_icon_help') ?><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('branded_button_icon')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->favicon_size_limit) ?></small>
                                        </div>

                                        <div class="form-group">
                                            <label for="branded_button_title"><i class="fas fa-fw fa-heading fa-sm text-muted mr-1"></i> <?= l('link.settings.branded_button_title') ?></label>
                                            <input id="branded_button_title" type="text" class="form-control" name="branded_button_title" value="<?= $data->link->settings->branded_button_title ?? '' ?>" maxlength="64" />
                                        </div>

                                        <div class="form-group" data-character-counter="textarea">
                                            <label for="branded_button_content" class="d-flex justify-content-between align-items-center">
                                                <span><i class="fab fa-fw fa-sm fa-html5 text-muted mr-1"></i> <?= l('link.settings.branded_button_content') ?></span>
                                                <small class="text-muted" data-character-counter-wrapper></small>
                                            </label>
                                            <textarea id="branded_button_content" class="form-control" name="branded_button_content" maxlength="10000"><?= $data->link->settings->branded_button_content ?></textarea>
                                            <small class="form-text text-muted"><?= l('link.settings.branded_button_content_help') ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif ?>

                            <?php if($show_admin_only_biolink_sections): ?>
                            <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4" type="button" data-toggle="collapse" data-target="#advanced_container" aria-expanded="false" aria-controls="advanced_container">
                                <i class="fas fa-fw fa-user-tie fa-sm mr-1"></i> <?= l('link.settings.advanced_header') ?>
                            </button>

                            <div class="collapse" data-parent="#settings" id="advanced_container">
								<?php if(settings()->links->email_reports_is_enabled): ?>
                                    <div <?= $this->user->plan_settings->email_reports_is_enabled ? null : get_plan_feature_disabled_info() ?>>
                                        <div class="form-group <?= $this->user->plan_settings->email_reports_is_enabled ? null : 'container-disabled' ?>">
                                            <div class="d-flex flex-wrap flex-row flex-xl-row justify-content-between">
                                                <label><i class="fas fa-fw fa-sm fa-bell text-muted mr-1"></i> <?= l('global.plan_settings.email_reports_is_enabled_' . settings()->links->email_reports_is_enabled) ?></label>
                                                <a href="<?= url('notification-handler-create') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('notification_handlers.create') ?></a>
                                            </div>
                                            <div class="mb-2"><small class="text-muted"><?= l('link.settings.email_reports_is_enabled_help') ?></small></div>

                                            <div class="row">
												<?php foreach($data->notification_handlers as $notification_handler): ?>
													<?php if($notification_handler->type != 'email') continue ?>
                                                    <div class="col-12 col-lg-6">
                                                        <div class="custom-control custom-checkbox my-2">
                                                            <input id="<?= 'email_reports_' . $notification_handler->notification_handler_id ?>" name="email_reports[]" value="<?= $notification_handler->notification_handler_id ?>" type="checkbox" class="custom-control-input" <?= in_array($notification_handler->notification_handler_id, $data->link->email_reports) ? 'checked="checked"' : null ?>>
                                                            <label class="custom-control-label" for="<?= 'email_reports_' . $notification_handler->notification_handler_id ?>">
                                                                <span class="mr-1"><?= $notification_handler->name ?></span>
                                                                <small class="badge badge-light badge-pill"><?= l('notification_handlers.type_' . $notification_handler->type) ?></small>
                                                            </label>
                                                        </div>
                                                    </div>
												<?php endforeach ?>
                                            </div>
                                        </div>
                                    </div>
								<?php endif ?>

                                <div class="form-group custom-control custom-switch">
                                    <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="share_is_enabled"
                                            name="share_is_enabled"
										<?= $data->link->settings->share_is_enabled ? 'checked="checked"' : null ?>
                                    >
                                    <label class="custom-control-label" for="share_is_enabled"><?= l('link.settings.share_is_enabled') ?></label>
                                    <small class="form-text text-muted"><?= l('link.settings.share_is_enabled_help') ?></small>
                                </div>

                                <div class="form-group custom-control custom-switch">
                                    <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="scroll_buttons_is_enabled"
                                            name="scroll_buttons_is_enabled"
										<?= $data->link->settings->scroll_buttons_is_enabled ? 'checked="checked"' : null ?>
                                    >
                                    <label class="custom-control-label" for="scroll_buttons_is_enabled"><?= l('link.settings.scroll_buttons_is_enabled') ?></label>
                                    <small class="form-text text-muted"><?= l('link.settings.scroll_buttons_is_enabled_help') ?></small>
                                </div>

								<?php if(settings()->links->directory_is_enabled): ?>
									<?php $directory_has_link = false ?>
									<?php if(settings()->email_notifications->contact && !empty(settings()->email_notifications->emails) && settings()->links->directory_display != 'all' && !$data->link->is_verified): ?>
										<?php $directory_has_link = true ?>
									<?php endif ?>

                                    <div <?= settings()->links->directory_display != 'all' && !$data->link->is_verified ? 'data-toggle="tooltip" data-html="true" title="' . l('link.settings.verified_required') . '<br />' . sprintf(l('link.settings.verified_help'), '', '') . '"' : null ?> <?= $directory_has_link ? 'class="cursor-pointer" onclick="window.location.href=\'' . url('contact') . '\'"' : null ?>>
                                        <div class="<?= settings()->links->directory_display != 'all' && !$data->link->is_verified ? 'container-disabled' : null ?>">
                                            <div class="form-group custom-control custom-switch">
                                                <input
                                                        type="checkbox"
                                                        class="custom-control-input"
                                                        id="directory_is_enabled"
                                                        name="directory_is_enabled"
													<?= $data->link->directory_is_enabled ? 'checked="checked"' : null ?>
                                                >
                                                <label class="custom-control-label" for="directory_is_enabled"><?= l('link.settings.directory_is_enabled') ?></label>
                                                <small class="form-text text-muted"><?= sprintf(l('link.settings.directory_is_enabled_help'), '<a href="' . url('directory') . '">' . l('directory.menu') . '</a>') ?></small>
                                            </div>
                                        </div>
                                    </div>
								<?php endif ?>

								<?php if(settings()->links->projects_is_enabled): ?>
                                    <div class="form-group">
                                        <div class="d-flex flex-wrap flex-row justify-content-between">
                                            <label for="project_id"><i class="fas fa-fw fa-sm fa-project-diagram text-muted mr-1"></i> <?= l('projects.project_id') ?></label>
                                            <a href="<?= url('project-create') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('projects.create') ?></a>
                                        </div>
                                        <select id="project_id" name="project_id" class="custom-select">
                                            <option value=" "><?= l('global.none') ?></option>
											<?php foreach($data->projects as $row): ?>
                                                <option value="<?= $row->project_id ?>" <?= $data->link->project_id == $row->project_id ? 'selected="selected"' : null?>><?= $row->name ?></option>
											<?php endforeach ?>
                                        </select>
                                    </div>
								<?php endif ?>

								<?php if(settings()->links->splash_page_is_enabled): ?>
                                    <div <?= $this->user->plan_settings->splash_pages_limit ? null : get_plan_feature_disabled_info() ?>>
                                        <div class="<?= $this->user->plan_settings->splash_pages_limit ? null : 'container-disabled' ?>">
                                            <div class="form-group">
                                                <div class="d-flex flex-wrap flex-row justify-content-between">
                                                    <label for="splash_page_id"><i class="fas fa-fw fa-sm fa-droplet text-muted mr-1"></i> <?= l('splash_pages.splash_page_id') ?></label>
                                                    <a href="<?= url('splash-pages') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('splash_pages.create') ?></a>
                                                </div>
                                                <select id="splash_page_id" name="splash_page_id" class="custom-select">
                                                    <option value=" "><?= l('global.none') ?></option>
													<?php foreach($data->splash_pages as $row): ?>
                                                        <option value="<?= $row->splash_page_id ?>" <?= $data->link->splash_page_id == $row->splash_page_id ? 'selected="selected"' : null?>><?= $row->name ?></option>
													<?php endforeach ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
								<?php endif ?>

                                <div <?= $this->user->plan_settings->leap_link ? null : get_plan_feature_disabled_info() ?>>
                                    <div class="<?= $this->user->plan_settings->leap_link ? null : 'container-disabled' ?>">
                                        <div class="form-group">
                                            <label for="leap_link"><i class="fas fa-fw fa-forward fa-sm text-muted mr-1"></i> <?= l('link.settings.leap_link') ?></label>
                                            <input id="leap_link" type="url" class="form-control" name="leap_link" value="<?= $data->link->settings->leap_link ?>" maxlength="2048" <?= !$this->user->plan_settings->leap_link ? 'disabled="disabled"': null ?> placeholder="<?= l('global.url_placeholder') ?>" autocomplete="off" />
                                            <small class="form-text text-muted"><?= l('link.settings.leap_link_help') ?></small>
                                        </div>
                                    </div>
                                </div>

                                <div <?= $this->user->plan_settings->custom_css_is_enabled ? null : get_plan_feature_disabled_info() ?>>
                                    <div class="form-group <?= $this->user->plan_settings->custom_css_is_enabled ? null : 'container-disabled' ?>" data-character-counter="textarea">
                                        <label for="custom_css" class="d-flex justify-content-between align-items-center">
                                            <span><i class="fab fa-fw fa-sm fa-css3 text-muted mr-1"></i> <?= l('global.custom_css') ?></span>
                                            <small class="text-muted" data-character-counter-wrapper></small>
                                        </label>
                                        <textarea id="custom_css" class="form-control" name="custom_css" maxlength="10000" placeholder="<?= l('global.custom_css_placeholder') ?>"><?= $data->link->settings->custom_css ?></textarea>
                                        <small class="form-text text-muted"><?= l('global.custom_css_help') ?></small>
                                    </div>
                                </div>

                                <div <?= $this->user->plan_settings->custom_js_is_enabled ? null : get_plan_feature_disabled_info() ?>>
                                    <div class="form-group <?= $this->user->plan_settings->custom_js_is_enabled ? null : 'container-disabled' ?>" data-character-counter="textarea">
                                        <label for="custom_js" class="d-flex justify-content-between align-items-center">
                                            <span><i class="fab fa-fw fa-sm fa-js-square text-muted mr-1"></i> <?= l('global.custom_js') ?></span>
                                            <small class="text-muted" data-character-counter-wrapper></small>
                                        </label>
                                        <textarea id="custom_js" class="form-control" name="custom_js" maxlength="10000" placeholder="<?= l('global.custom_js_placeholder') ?>"><?= $data->link->settings->custom_js ?></textarea>
                                        <small class="form-text text-muted"><?= l('global.custom_js_help') ?></small>
                                    </div>
                                </div>

								<?php if(settings()->links->sixsixpusher_is_enabled): ?>
                                    <div class="form-group" data-file-input-wrapper-size-limit="<?= get_max_upload() ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), settings()->links->sixsixpusher_service_worker_size_limit) ?>">
                                        <label for="service_worker"><i class="fas fa-fw fa-sm fa-file text-muted mr-1"></i> <?= l('link.settings.service_worker') ?></label>
										<?= include_view(THEME_PATH . 'views/partials/file_input.php', ['uploads_file_key' => 'service_workers', 'file_key' => 'service_worker', 'already_existing_file' => $data->link->settings->service_worker ?? null, 'custom_file_full_url' => $data->link->full_url . settings()->links->sixsixpusher_service_worker_file_name . '.js']) ?>
                                        <small class="form-text text-muted"><?= l('link.settings.service_worker_help') ?></small>
                                        <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('service_workers')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), settings()->links->sixsixpusher_service_worker_size_limit) ?></small>
                                    </div>
								<?php endif ?>
                            </div>
                                <?php endif ?>

                            <div class="text-center mt-4">
                                <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('global.update') ?></button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

            <div class="tab-pane fade <?= $active_tab == 'blocks' ? 'show active' : null ?>" id="biolink_blocks" role="tabpanel" aria-labelledby="blocks-tab">

				<?php if($data->link_links_result->num_rows): ?>
					<?php while($row = $data->link_links_result->fetch_object()): ?>
						<?php if(!isset($data->biolink_blocks[$row->type])) continue; ?>

						<?php $row->settings = (object) json_decode($row->settings) ?>
						<?php
						$row->settings->border_shadow_style = $row->settings->border_shadow_style ?? 'subtle';
						$row->settings->border_shadow_color = $row->settings->border_shadow_color ?? '#00000010';
                        $fcc_row_is_ai_primary = $fcc_ai_primary_block_id && (int) $row->biolink_block_id === $fcc_ai_primary_block_id;
                        $fcc_row_has_ai_copy = !empty($fcc_ai_copy_suggestion_block_ids[(int) $row->biolink_block_id]);
                        $fcc_row_copy_suggestions = $fcc_ai_copy_suggestion_map[(int) $row->biolink_block_id] ?? [];
						?>

                        <?php /* Custom code: FC-2026-02-27: premium biolink block cards */ ?>
                        <div class="biolink_block biolink-editor-block card <?= $row->is_enabled ? null : 'custom-row-inactive' ?> mb-4" data-biolink-block-id="<?= $row->biolink_block_id ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="custom-row-side-controller">
                                        <span data-toggle="tooltip" title="<?= l('link.biolink_blocks.link_sort') ?>">
                                            <i class="fas fa-fw fa-bars fa-sm text-muted custom-row-side-controller-grab drag"></i>
                                        </span>
                                    </div>

                                    <div class="col-1 mr-2 p-0 d-none d-lg-block">
                                        <?php if($row->type == 'custom_html_chatbot'): ?>
                                            <img
                                                src="<?= SITE_URL . ASSETS_URL_PATH . 'images/sovica.png' ?>"
                                                alt=""
                                                data-toggle="tooltip"
                                                title="<?= l('link.biolink.blocks.' . $row->type) ?>"
                                                style="width: 28px; height: 28px; object-fit: contain;"
                                                onerror="this.onerror=null;this.src='<?= SITE_URL . UPLOADS_URL_PATH . 'ai-chat/sovica.png' ?>';"
                                                loading="lazy"
                                                decoding="async"
                                            />
                                        <?php elseif($row->type == 'custom_html_chatbot_pets'): ?>
                                            <span class="fa-stack" style="font-size: 1.08rem;" data-toggle="tooltip" title="<?= l('link.biolink.blocks.' . $row->type) ?>">
                                                <i class="fas fa-circle fa-stack-2x" style="color: #5f3dc4"></i>
                                                <i class="fas fa-paw fa-stack-1x fa-inverse"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="fa-stack fa-1x" data-toggle="tooltip" title="<?= l('link.biolink.blocks.' . $row->type) ?>">
                                                <i class="fas fa-circle fa-stack-2x" style="color: <?= $data->biolink_blocks[$row->type]['color'] ?>"></i>
                                                <i class="<?= $data->biolink_blocks[$row->type]['icon'] ?> fa-stack-1x fa-inverse"></i>
                                            </span>
                                        <?php endif ?>
                                    </div>

                                    <div class="col-6 col-md-5">
                                        <div class="d-flex flex-column text-truncate">
                                            <div class="text-truncate">
                                                <a href="#"
                                                   data-toggle="collapse"
                                                   data-target="#biolink_block_expanded_content_<?= $row->biolink_block_id ?>"
                                                   aria-expanded="false"
                                                   aria-controls="biolink_block_expanded_content_<?= $row->biolink_block_id ?>"
                                                   class="text-truncate"
                                                >
													<?php if($row->type == 'paragraph'): ?>
														<?php $display_dynamic_name = strip_tags($row->settings->{$data->biolink_blocks[$row->type]['display_dynamic_name']}); ?>
                                                        <span class="font-weight-500"><?= $display_dynamic_name ?: l('link.biolink.blocks.' . $row->type) ?></span>
													<?php else: ?>
                                                        <span class="font-weight-500"><?= $data->biolink_blocks[$row->type]['display_dynamic_name'] ? ($row->settings->{$data->biolink_blocks[$row->type]['display_dynamic_name']} ? string_truncate($row->settings->{$data->biolink_blocks[$row->type]['display_dynamic_name']}, 32) : l('link.biolink.blocks.' . $row->type)) : l('link.biolink.blocks.' . $row->type) ?></span>
													<?php endif ?>
                                                </a>
                                            </div>

                                            <?php if($fcc_row_is_ai_primary): ?>
                                                <span class="fcc-ai-primary-badge">
                                                    <i class="fas fa-fw fa-bullseye"></i> <?= l('link.settings.ai_primary_badge') ?>
                                                </span>
                                            <?php endif ?>

                                            <?php if($fcc_row_has_ai_copy): ?>
                                                <span class="fcc-ai-copy-badge">
                                                    <i class="fas fa-fw fa-pen"></i> <?= l('link.settings.ai_copy_badge') ?>
                                                </span>
                                            <?php endif ?>

                                            <span class="d-flex align-items-center">
												<?php if(!empty($row->location_url)): ?>
													<?php if($parsed_host = parse_url($row->location_url, PHP_URL_HOST)): ?>
                                                        <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain($parsed_host) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />
													<?php endif ?>

                                                    <span class="d-inline-block text-truncate">
                                                        <a href="<?= $row->location_url ?>" class="text-muted small" title="<?= $row->location_url ?>" target="_blank" rel="noreferrer"><?= $row->location_url ?></a>
                                                    </span>
												<?php elseif(!empty($row->url)): ?>
                                                    <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain(parse_url(url($row->url))['host']) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />

                                                    <span class="d-inline-block text-truncate">
                                                        <a href="<?= url($row->url) ?>" class="text-muted small" title="<?= url($row->url) ?>" target="_blank" rel="noreferrer"><?= url($row->url) ?></a>
                                                    </span>
												<?php endif ?>
                                            </span>

                                        </div>
                                    </div>

                                    <div class="d-none d-md-flex col-md-3 justify-content-end flex-wrap">
										<?php if($data->biolink_blocks[$row->type]['has_statistics']): ?>
                                            <a href="<?= url('biolink-block/' . $row->biolink_block_id . '/statistics') ?>">
                                                <span data-toggle="tooltip" title="<?= l('links.clicks') ?>" class="badge badge-light"><i class="fas fa-fw fa-sm fa-chart-bar mr-1"></i> <?= nr($row->clicks) ?></span>
                                            </a>
										<?php endif ?>
									<?php if($row->type == 'lead_funnel'): ?>
                                            <a href="<?= url('funnels-analytics?biolink_block_id=' . $row->biolink_block_id) ?>" class="btn btn-sm btn-link text-secondary" data-toggle="tooltip" title="<?= l('funnels_analytics.link') ?>">
                                                <i class="fas fa-fw fa-sm fa-filter"></i>
                                            </a>
									<?php endif ?>
										<?php if($data->biolink_blocks[$row->type]['type'] == 'payment'): ?>
                                            <a href="<?= url('guests-payments?biolink_block_id=' . $row->biolink_block_id) ?>" class="btn btn-sm btn-link text-secondary" data-toggle="tooltip" title="<?= l('guests_payments.link') ?>">
                                                <i class="fas fa-fw fa-sm fa-coins"></i>
                                            </a>
                                            <a href="<?= url('guests-payments-statistics?biolink_block_id=' . $row->biolink_block_id) ?>" class="btn btn-sm btn-link text-secondary" data-toggle="tooltip" title="<?= l('guests_payments_statistics.link') ?>">
                                                <i class="fas fa-fw fa-sm fa-chart-pie"></i>
                                            </a>
										<?php endif ?>
                                    </div>

                                    <div class="col-5 col-md d-flex align-items-center justify-content-end">
                                        <div class="custom-control custom-switch" data-toggle="tooltip" title="<?= l('link.biolink_blocks.is_enabled_tooltip') ?>">
                                            <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="biolink_block_is_enabled_<?= $row->biolink_block_id ?>"
                                                    data-row-id="<?= $row->biolink_block_id ?>"
												<?= $row->is_enabled ? 'checked="checked"' : null ?>
                                            >
                                            <label class="custom-control-label" for="biolink_block_is_enabled_<?= $row->biolink_block_id ?>"></label>
                                        </div>

                                        <div class="dropdown">
                                            <button type="button" class="btn btn-link text-secondary dropdown-toggle dropdown-toggle-simple" data-toggle="dropdown" data-boundary="viewport">
                                                <i class="fas fa-fw fa-ellipsis-v"></i>
                                            </button>

                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a href="#"
                                                   class="dropdown-item"
                                                   data-toggle="collapse"
                                                   data-target="#biolink_block_expanded_content_<?= $row->biolink_block_id ?>"
                                                   aria-expanded="false"
                                                   aria-controls="biolink_block_expanded_content_<?= $row->biolink_block_id ?>"
                                                >
                                                    <i class="fas fa-fw fa-sm fa-pencil-alt mr-2"></i> <?= l('global.edit') ?>
                                                </a>

												<?php if($data->biolink_blocks[$row->type]['has_statistics']): ?>
                                                    <a href="<?= url('biolink-block/' . $row->biolink_block_id . '/statistics') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-chart-bar mr-2"></i> <?= l('link.statistics.link') ?></a>
												<?php endif ?>

                                                <?php if($row->type == 'lead_funnel'): ?>
                                                            <a href="<?= url('funnels-analytics?biolink_block_id=' . $row->biolink_block_id) ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-filter mr-2"></i> <?= l('funnels_analytics.link') ?></a>
                                                <?php endif ?>

												<?php if($data->biolink_blocks[$row->type]['type'] == 'payment'): ?>
                                                    <a href="<?= url('guests-payments?biolink_block_id=' . $row->biolink_block_id) ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-coins mr-2"></i> <?= l('guests_payments.link') ?></a>
                                                    <a href="<?= url('guests-payments-statistics?biolink_block_id=' . $row->biolink_block_id) ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-chart-pie mr-2"></i> <?= l('guests_payments_statistics.link') ?></a>
												<?php endif ?>

                                                <?php if(in_array($row->type, ['email_collector', 'phone_collector', 'contact_collector', 'lead_funnel'])): ?>
                                                    <a href="<?= url('data?biolink_block_id=' . $row->biolink_block_id) ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-database mr-2"></i> <?= l('data.link') ?></a>
												<?php endif ?>

                                                <a href="<?= $data->link->full_url . '#biolink_block_id_' . $row->biolink_block_id ?>" target="_blank" class="dropdown-item" data-biolink-block-id="<?= $row->biolink_block_id ?>"><i class="fas fa-fw fa-sm fa-external-link-alt mr-2"></i> <?= l('global.view') ?></a>

                                                <a href="#" data-toggle="modal" data-target="#biolink_block_duplicate_modal" class="dropdown-item" data-biolink-block-id="<?= $row->biolink_block_id ?>"><i class="fas fa-fw fa-sm fa-clone mr-2"></i> <?= l('global.duplicate') ?></a>

                                                <a href="#" data-toggle="modal" data-target="#biolink_block_delete_modal" class="dropdown-item" data-biolink-block-id="<?= $row->biolink_block_id ?>"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="collapse biolink-editor-expanded <?= isset($_GET['biolink_block_id']) && $_GET['biolink_block_id'] == $row->biolink_block_id ? 'show' : null ?>" id="biolink_block_expanded_content_<?= $row->biolink_block_id ?>" data-link-type="<?= $row->type ?>" data-parent="#biolink_blocks">
                                    <?php if($fcc_row_is_ai_primary || !empty($fcc_row_copy_suggestions)): ?>
                                        <div class="fcc-ai-block-helper mb-3">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start">
                                                <div class="mr-3">
                                                    <div class="fcc-ai-theme-kicker">
                                                        <i class="fas fa-fw fa-magic"></i>
                                                        <span><?= $fcc_biolink_editor_copy['ai_block_helper_title'] ?></span>
                                                    </div>
                                                    <p class="fcc-ai-block-helper-copy mb-0 mt-2"><?= $fcc_biolink_editor_copy['ai_block_helper_text'] ?></p>
                                                </div>

                                                <?php if($fcc_row_is_ai_primary): ?>
                                                    <span class="fcc-ai-primary-badge mt-0">
                                                        <i class="fas fa-fw fa-bullseye"></i>
                                                        <?= $fcc_biolink_editor_copy['ai_block_helper_primary'] ?>
                                                    </span>
                                                <?php endif ?>
                                            </div>

                                            <?php if(!empty($fcc_row_copy_suggestions)): ?>
                                                <div class="fcc-ai-inline-actions">
                                                    <?php foreach($fcc_row_copy_suggestions as $fcc_inline_copy): ?>
                                                        <div class="fcc-ai-inline-suggestion">
                                                            <div class="font-weight-bold"><?= htmlspecialchars((string) (($fcc_inline_copy['label'] ?? '') ?: l('link.settings.ai_copy_default_label')), ENT_QUOTES, 'UTF-8') ?></div>
                                                            <?php if(!empty($fcc_inline_copy['reason'])): ?>
                                                                <div class="small text-muted mb-2"><?= htmlspecialchars((string) ($fcc_inline_copy['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                            <?php endif ?>
                                                            <div class="fcc-ai-inline-suggestion-value"><?= htmlspecialchars((string) ($fcc_inline_copy['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                            <div class="fcc-ai-inline-suggestion-actions">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-outline-primary js-ai-copy-insert"
                                                                    data-block-id="<?= (int) $row->biolink_block_id ?>"
                                                                    data-field="<?= htmlspecialchars((string) ($fcc_inline_copy['field'] ?? 'name'), ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-value="<?= htmlspecialchars((string) ($fcc_inline_copy['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-notification-target="#fcc_ai_copy_notification"
                                                                >
                                                                    <i class="fas fa-fw fa-magic mr-1"></i> <?= l('link.settings.ai_copy_insert') ?>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-light js-ai-copy-copy"
                                                                    data-value="<?= htmlspecialchars((string) ($fcc_inline_copy['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-notification-target="#fcc_ai_copy_notification"
                                                                >
                                                                    <i class="fas fa-fw fa-copy mr-1"></i> <?= l('global.copy') ?>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php endforeach ?>
                                                </div>
                                            <?php endif ?>
                                        </div>
                                    <?php endif ?>
									<?php require THEME_PATH . 'views/link/settings/biolink_blocks/' . $row->type . '/' . $row->type . '_update_form.php' ?>
                                </div>
                            </div>
                        </div>
						<?php /* /Custom code: FC-2026-02-27 */ ?>

					<?php endwhile ?>
				<?php else: ?>

					<?= include_view(THEME_PATH . 'views/partials/no_data.php', [
						'filters_get' => $data->filters->get ?? [],
						'name' => 'link.biolink_blocks',
						'has_secondary_text' => true,
					]); ?>

				<?php endif ?>

            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6 mt-5 mt-xl-0 d-flex justify-content-center justify-content-xl-end">
        <div class="biolink-preview-container">
            <div class="biolink-preview sticky">
                <div id="fcc_biolink_preview_iframe_shell" class="biolink-preview-iframe-container">
                    <div id="biolink_preview_iframe_loading" class="biolink-preview-iframe-loading d-none"><div class="spinner-border bg-primary" role="status"></div></div>
                    <div class="fcc-biolink-preview-coach-notice" id="fcc_biolink_preview_coach_notice" aria-live="polite">
                        <div class="fcc-biolink-preview-coach-notice-icon" aria-hidden="true">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <div>
                            <div class="fcc-biolink-preview-coach-notice-title"><?= htmlspecialchars($fcc_biolink_preview_coach_pause_copy['title'], ENT_QUOTES, 'UTF-8') ?></div>
                            <p class="fcc-biolink-preview-coach-notice-text"><?= htmlspecialchars($fcc_biolink_preview_coach_pause_copy['text'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                    <iframe id="biolink_preview_iframe" class="biolink-preview-iframe fcc-biolink-tour-target" src="<?= SITE_URL . 'l/link?link_id=' . $data->link->link_id . '&preview=' . md5($data->link->user_id) ?>"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="fcc-biolink-tour-backdrop" id="fcc_biolink_editor_backdrop"></div>
<div class="fcc-biolink-tour-popover" id="fcc_biolink_editor_popover" aria-live="polite">
    <div class="fcc-biolink-tour-progress" id="fcc_biolink_editor_progress">1 / 1</div>
    <div class="fcc-biolink-tour-title" id="fcc_biolink_editor_title"></div>
    <div class="fcc-biolink-tour-text" id="fcc_biolink_editor_text"></div>
    <a href="#" target="_blank" rel="noopener noreferrer" class="fcc-biolink-tour-resource" id="fcc_biolink_editor_resource">
        <i class="fab fa-youtube"></i>
        <span id="fcc_biolink_editor_resource_label"></span>
    </a>
    <div class="fcc-biolink-tour-actions">
        <button type="button" class="btn btn-link text-muted px-0" id="fcc_biolink_editor_skip"><?= l('dashboard.tour.skip') ?></button>
        <div class="fcc-biolink-tour-actions-main">
            <button type="button" class="btn btn-outline-light" id="fcc_biolink_editor_prev"><?= l('dashboard.tour.prev') ?></button>
            <button type="button" class="btn btn-primary" id="fcc_biolink_editor_next"><?= l('dashboard.tour.next') ?></button>
        </div>
    </div>
</div>

<?php if(settings()->links->available_biolink_blocks->vcard): ?>
    <template id="template_vcard_social">
        <div class="mb-4">
            <div class="form-group">
                <label for=""><i class="fas fa-fw fa-bookmark fa-sm text-muted mr-1"></i> <?= l('biolink_vcard.vcard_social_label') ?></label>
                <input id="" type="text" name="vcard_social_label[]" class="form-control" maxlength="<?= $data->biolink_blocks['vcard']['fields']['social_label']['max_length'] ?>" required="required" />
            </div>

            <div class="form-group">
                <label for=""><i class="fas fa-fw fa-link fa-sm text-muted mr-1"></i> <?= l('biolink_vcard.vcard_social_value') ?></label>
                <input id="" type="url" name="vcard_social_value[]" class="form-control" maxlength="<?= $data->biolink_blocks['vcard']['fields']['social_value']['max_length'] ?>" required="required" />
            </div>

            <button type="button" data-remove="vcard_social" class="btn btn-sm btn-block btn-outline-danger"><i class="fas fa-fw fa-times"></i> <?= l('global.delete') ?></button>
        </div>
    </template>

    <template id="template_vcard_phone_numbers">
        <div class="mb-4">
            <div class="form-group">
                <label for=""><i class="fas fa-fw fa-bookmark fa-sm text-muted mr-1"></i> <?= l('biolink_vcard.vcard_phone_number_label') ?></label>
                <input id="" type="text" name="vcard_phone_number_label[]" class="form-control" maxlength="<?= $data->links_types['vcard']['fields']['phone_number_label']['max_length'] ?>" />
                <small class="form-text text-muted"><?= l('biolink_vcard.vcard_phone_number_label_help') ?></small>
            </div>

            <div class="form-group">
                <label for=""><i class="fas fa-fw fa-phone-square-alt fa-sm text-muted mr-1"></i> <?= l('biolink_vcard.vcard_phone_number_value') ?></label>
                <input id="" type="text" name="vcard_phone_number_value[]" class="form-control" maxlength="<?= $data->links_types['vcard']['fields']['phone_number_value']['max_length'] ?>" required="required" />
            </div>

            <button type="button" data-remove="vcard_phone_numbers" class="btn btn-sm btn-block btn-outline-danger"><i class="fas fa-fw fa-times"></i> <?= l('global.delete') ?></button>
        </div>
    </template>
<?php endif ?>

<?php $html = ob_get_clean() ?>


<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/pickr.min.js?v=' . PRODUCT_CODE ?>"></script>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/fontawesome-iconpicker.min.js?v=' . PRODUCT_CODE ?>"></script>
<script>
    'use strict';

    /* Initiate the color picker */
    let pickr_options = {
        comparison: false,

        components: {
            preview: true,
            opacity: true,
            hue: true,
            comparison: false,
            interaction: {
                hex: true,
                rgba: false,
                hsla: false,
                hsva: false,
                cmyk: false,
                input: true,
                clear: false,
                save: false
            }
        }
    };

    /* UTM */
    let process_utm = () => {

        let utm_source = document.querySelector('input[name="utm_source"]').value;
        let utm_medium = document.querySelector('input[name="utm_medium"]').value;
        let utm_campaign = 'UTM_CAMPAIGN';
        let utm_preview = <?= json_encode(l('global.none')) ?>;

        if(utm_source || utm_medium) {
            let link = new URL(<?= json_encode(SITE_URL) ?>);

            if(utm_source) link.searchParams.set('utm_source', utm_source.trim());
            if(utm_medium) link.searchParams.set('utm_medium', utm_medium.trim());
            if(utm_campaign) link.searchParams.set('utm_campaign', utm_campaign.trim());

            utm_preview = '?' + link.searchParams.toString();
        }

        document.querySelector('input[name="utm_preview"]').value = utm_preview;
    }

    document.querySelectorAll('input[name="utm_source"], input[name="utm_medium"], input[name="utm_campaign"]').forEach(element => {
        ['change', 'paste', 'keyup'].forEach(event_type => {
            element.addEventListener(event_type, process_utm);
        });
    })

    process_utm();

    const fcc_biolink_theme_copy = {
        custom_name: <?= json_encode(l('biolink_themes.id_null')) ?>,
        active_status: <?= json_encode(l('biolink_themes.current_active')) ?>,
        custom_status: <?= json_encode(l('biolink_themes.current_custom')) ?>,
        active_help: <?= json_encode(l('biolink_themes.current_active_help')) ?>,
        custom_help: <?= json_encode(l('biolink_themes.current_custom_help')) ?>
    };

    let update_biolink_theme_summary = () => {
        let hidden_theme_input = document.querySelector('#biolink_theme_id');

        if(!hidden_theme_input) {
            return;
        }

        let biolink_theme_id = hidden_theme_input.value;
        let selector = biolink_theme_id
            ? `#biolink_themes_modal input[name="biolink_theme_id"][value="${biolink_theme_id}"]`
            : '#settings_biolink_theme_id_null';
        let selected_theme_input = document.querySelector(selector);
        let selected_theme_name = selected_theme_input?.dataset.themeName || fcc_biolink_theme_copy.custom_name;
        let is_custom_theme = !biolink_theme_id;

        let summary_name = document.querySelector('#fcc_biolink_theme_summary_name');
        let summary_status = document.querySelector('#fcc_biolink_theme_summary_status');
        let summary_help = document.querySelector('#fcc_biolink_theme_summary_help');

        if(summary_name) {
            summary_name.textContent = selected_theme_name;
        }

        if(summary_status) {
            summary_status.textContent = is_custom_theme ? fcc_biolink_theme_copy.custom_status : fcc_biolink_theme_copy.active_status;
            summary_status.classList.toggle('badge-light', is_custom_theme);
            summary_status.classList.toggle('badge-primary', !is_custom_theme);
        }

        if(summary_help) {
            summary_help.textContent = is_custom_theme ? fcc_biolink_theme_copy.custom_help : fcc_biolink_theme_copy.active_help;
        }
    };

    window.update_biolink_theme_summary = update_biolink_theme_summary;
    update_biolink_theme_summary();

    let get_ai_notification_container = targetSelector => {
        if(!targetSelector) {
            return null;
        }

        return document.querySelector(targetSelector);
    };

    let reveal_ai_notification_container = notification_container => {
        if(!notification_container) {
            return;
        }

        window.requestAnimationFrame(() => {
            notification_container.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });

            let first_alert = notification_container.querySelector('.alert');

            if(first_alert) {
                first_alert.setAttribute('tabindex', '-1');
                first_alert.focus({preventScroll: true});
            }
        });
    };

    let update_ai_applied_meta = () => {
        document.querySelectorAll('[data-ai-applied-now]').forEach(element => {
            element.classList.remove('d-none');
        });
    };

    let reload_biolink_editor_tab = tab => {
        let location_url = new URL(window.location.href);
        location_url.searchParams.set('tab', tab || 'settings');
        window.location.assign(location_url.toString());
    };

    let post_ai_editor_action = (payload, notificationTarget, reloadTab = '') => {
        let notification_container = get_ai_notification_container(notificationTarget);

        if(notification_container) {
            notification_container.innerHTML = '';
        }

        return $.ajax({
            type: 'POST',
            url: `${url}link-ajax`,
            data: payload,
            dataType: 'json',
            success: response => {
                if(notification_container) {
                    display_notifications(response.message, response.status, notification_container);

                    if(response.status !== 'success' || response.message) {
                        reveal_ai_notification_container(notification_container);
                    }
                }

                if(response.status === 'success') {
                    if(response.details?.url) {
                        window.setTimeout(() => redirect(response.details.url, true), 250);
                        return;
                    }

                    update_ai_applied_meta();
                    if(reloadTab) {
                        window.setTimeout(() => reload_biolink_editor_tab(reloadTab), 800);
                    } else {
                        refresh_biolink_preview();
                    }
                }
            },
            error: () => {
                if(notification_container) {
                    display_notifications(<?= json_encode(l('global.error_message.basic')) ?>, 'error', notification_container);
                    reveal_ai_notification_container(notification_container);
                }
            }
        });
    };

    window.post_ai_editor_action = post_ai_editor_action;

    document.querySelectorAll('.js-apply-ai-theme').forEach(element => {
        element.addEventListener('click', event => {
            let button = event.currentTarget;
            let theme_key = button.getAttribute('data-theme-key') || '';
            let notification_target = button.getAttribute('data-notification-target');
            let reload_tab = button.getAttribute('data-reload-tab') || '';

            post_ai_editor_action({
                token: <?= json_encode(\Altum\Csrf::get()) ?>,
                request_type: 'apply_ai_color_bundle',
                link_id: button.getAttribute('data-link-id'),
                theme_key: theme_key
            }, notification_target, reload_tab);
        });
    });

    document.querySelectorAll('.js-apply-ai-color-bundle').forEach(element => {
        element.addEventListener('click', event => {
            let button = event.currentTarget;
            let notification_target = button.getAttribute('data-notification-target');
            let reload_tab = button.getAttribute('data-reload-tab') || '';

            post_ai_editor_action({
                token: <?= json_encode(\Altum\Csrf::get()) ?>,
                request_type: 'apply_ai_color_bundle',
                link_id: button.getAttribute('data-link-id')
            }, notification_target, reload_tab);
        });
    });

    document.querySelectorAll('.js-apply-ai-block-bundle').forEach(element => {
        element.addEventListener('click', event => {
            let button = event.currentTarget;
            let notification_target = button.getAttribute('data-notification-target');
            let reload_tab = button.getAttribute('data-reload-tab') || '';

            post_ai_editor_action({
                token: <?= json_encode(\Altum\Csrf::get()) ?>,
                request_type: 'apply_ai_block_bundle',
                link_id: button.getAttribute('data-link-id')
            }, notification_target, reload_tab);
        });
    });

    document.querySelectorAll('.js-restore-ai-bundle').forEach(element => {
        element.addEventListener('click', event => {
            let button = event.currentTarget;
            let notification_target = button.getAttribute('data-notification-target');
            let reload_tab = button.getAttribute('data-reload-tab') || '';

            post_ai_editor_action({
                token: <?= json_encode(\Altum\Csrf::get()) ?>,
                request_type: 'restore_ai_bundle_backup',
                link_id: button.getAttribute('data-link-id')
            }, notification_target, reload_tab);
        });
    });

    document.querySelectorAll('.js-apply-ai-primary-focus').forEach(element => {
        element.addEventListener('click', event => {
            let button = event.currentTarget;
            let notification_target = button.getAttribute('data-notification-target');
            let reload_tab = button.getAttribute('data-reload-tab') || '';

            post_ai_editor_action({
                token: <?= json_encode(\Altum\Csrf::get()) ?>,
                request_type: 'apply_ai_primary_block_focus',
                link_id: button.getAttribute('data-link-id')
            }, notification_target, reload_tab);
        });
    });

    document.querySelectorAll('.js-apply-ai-layout').forEach(element => {
        element.addEventListener('click', event => {
            let button = event.currentTarget;
            let notification_target = button.getAttribute('data-notification-target');
            let reload_tab = button.getAttribute('data-reload-tab') || '';

            post_ai_editor_action({
                token: <?= json_encode(\Altum\Csrf::get()) ?>,
                request_type: 'apply_ai_layout_actions',
                link_id: button.getAttribute('data-link-id')
            }, notification_target, reload_tab);
        });
    });

    document.querySelectorAll('.js-restore-ai-layout').forEach(element => {
        element.addEventListener('click', event => {
            let button = event.currentTarget;
            let notification_target = button.getAttribute('data-notification-target');
            let reload_tab = button.getAttribute('data-reload-tab') || '';

            post_ai_editor_action({
                token: <?= json_encode(\Altum\Csrf::get()) ?>,
                request_type: 'restore_ai_layout_backup',
                link_id: button.getAttribute('data-link-id')
            }, notification_target, reload_tab);
        });
    });

    document.querySelectorAll('.js-add-ai-missing-block').forEach(element => {
        element.addEventListener('click', event => {
            let button = event.currentTarget;

            post_ai_editor_action({
                token: <?= json_encode(\Altum\Csrf::get()) ?>,
                request_type: 'add_ai_recommended_block',
                link_id: button.getAttribute('data-link-id'),
                recommendation_key: button.getAttribute('data-recommendation-key') || '',
                block_type: button.getAttribute('data-block-type') || ''
            }, button.getAttribute('data-notification-target') || '#fcc_ai_copy_notification');
        });
    });

    document.querySelectorAll('.js-open-ai-block-picker').forEach(element => {
        element.addEventListener('click', event => {
            let button = event.currentTarget;
            let block_type = button.getAttribute('data-block-type') || '';
            let picker_search = (button.getAttribute('data-picker-search') || '').trim();
            let block_group = (button.getAttribute('data-block-group') || '').trim();
            let block_goal = (button.getAttribute('data-block-goal') || '').trim();
            let label = picker_search || (block_type ? <?= json_encode(l('link.settings.ai_missing_blocks_search_prefix')) ?> + ' ' + block_type.replace(/_/g, ' ') : '');

            $('#biolink_link_create_modal').modal('show');

            window.setTimeout(() => {
                if(window.fccBiolinkBlockPicker) {
                    window.fccBiolinkBlockPicker.setFilters({
                        search: label.trim(),
                        group: block_group,
                        goal: block_goal
                    });
                    window.fccBiolinkBlockPicker.focusSearch();
                }
            }, 180);
        });
    });

    let copy_ai_text_to_clipboard = async value => {
        if(!value) {
            return false;
        }

        try {
            await navigator.clipboard.writeText(value);
            return true;
        } catch(error) {
            let fallback = document.createElement('textarea');
            fallback.value = value;
            fallback.setAttribute('readonly', '');
            fallback.style.position = 'absolute';
            fallback.style.left = '-9999px';
            document.body.appendChild(fallback);
            fallback.select();
            let copied = document.execCommand('copy');
            document.body.removeChild(fallback);
            return copied;
        }
    };

    document.querySelectorAll('.js-ai-copy-copy').forEach(element => {
        element.addEventListener('click', async event => {
            let button = event.currentTarget;
            let value = button.getAttribute('data-value') || '';
            let notification_container = get_ai_notification_container(button.getAttribute('data-notification-target'));
            let copied = await copy_ai_text_to_clipboard(value);

            if(notification_container) {
                display_notifications(copied ? <?= json_encode(l('link.settings.ai_copy_copied')) ?> : <?= json_encode(l('global.error_message.basic')) ?>, copied ? 'success' : 'error', notification_container);
            }
        });
    });

    let get_ai_copy_insert_target = (block_id, field) => {
        let scopes = [
            document.querySelector(`#update_biolink_block_${block_id}`),
            document.querySelector(`#biolink_block_expanded_content_${block_id}`),
            document.querySelector(`[data-biolink-block-id="${block_id}"]`)
        ].filter(Boolean);

        let scoped_selectors = field ? [`[name="${field}"]`] : [];
        let unique_selectors = [];

        if(field === 'name') {
            scoped_selectors.push('[name="title"]');
            unique_selectors.push(
                `#link_name_${block_id}`,
                `#featured_link_name_${block_id}`,
                `#big_link_name_${block_id}`,
                `#youtube_title_${block_id}`,
                `#vimeo_title_${block_id}`
            );
        }

        if(field === 'title') {
            unique_selectors.push(
                `#youtube_title_${block_id}`,
                `#vimeo_title_${block_id}`
            );
        }

        for(let scope of scopes) {
            for(let selector of [...unique_selectors, ...scoped_selectors]) {
                let target = scope.querySelector(selector);

                if(target) {
                    return target;
                }
            }
        }

        for(let selector of unique_selectors) {
            let target = document.querySelector(selector);

            if(target) {
                return target;
            }
        }

        return null;
    };

    document.addEventListener('click', async event => {
        let button = event.target.closest('.js-ai-copy-insert');

        if(!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        let block_id = parseInt(button.getAttribute('data-block-id') || '0', 10);
        let field = button.getAttribute('data-field') || 'name';
        let value = button.getAttribute('data-value') || '';
        let notification_container = get_ai_notification_container(button.getAttribute('data-notification-target'));

        if(!block_id) {
            let copied = await copy_ai_text_to_clipboard(value);
            if(notification_container) {
                display_notifications(copied ? <?= json_encode(l('link.settings.ai_copy_copied')) ?> : <?= json_encode(l('global.error_message.basic')) ?>, copied ? 'success' : 'error', notification_container);
            }
            return;
        }

        let collapseElement = document.querySelector(`#biolink_block_expanded_content_${block_id}`);

        if(collapseElement && !collapseElement.classList.contains('show')) {
            $(collapseElement).collapse('show');
            await new Promise(resolve => setTimeout(resolve, 180));
        }

        let form = document.querySelector(`#update_biolink_block_${block_id}`);
        let target = get_ai_copy_insert_target(block_id, field);

        if(!target) {
            let copied = await copy_ai_text_to_clipboard(value);
            if(notification_container) {
                display_notifications(copied ? <?= json_encode(l('link.settings.ai_copy_missing_field')) ?> : <?= json_encode(l('global.error_message.basic')) ?>, copied ? 'success' : 'error', notification_container);
            }
            return;
        }

        target.value = value;
        target.dispatchEvent(new Event('input', { bubbles: true }));
        target.dispatchEvent(new Event('change', { bubbles: true }));
        target.dispatchEvent(new Event('keyup', { bubbles: true }));

        if(typeof target.focus === 'function') {
            try {
                target.focus({ preventScroll: true });
            } catch(error) {
                target.focus();
            }
        }

        if(typeof target.select === 'function' && ['text', 'search', 'url', 'tel', 'email', 'password'].includes((target.type || '').toLowerCase())) {
            target.select();
        }

        target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        form?.scrollIntoView({ behavior: 'smooth', block: 'center' });

        if(notification_container) {
            display_notifications(<?= json_encode(l('link.settings.ai_copy_inserted')) ?>, 'success', notification_container);
        }
    });

    /* Switching themes & previewing */
    let biolink_theme_preview = () => {
        return new Promise((resolve) => {
            let biolink_theme_id = document.querySelector('#biolink_theme_id').value;

            /* Add loader */
            document.querySelector('#biolink_preview_iframe_loading').classList.remove('d-none');

            /* Refresh iframe */
            let biolink_preview_iframe = document.querySelector('#biolink_preview_iframe');

            /* Remove any existing onload */
            biolink_preview_iframe.onload = null;

            setTimeout(() => {
                let biolink_preview_iframe_url = new URL(biolink_preview_iframe.getAttribute('src'));

                if(biolink_theme_id) {
                    biolink_preview_iframe_url.searchParams.set('biolink_theme_id', biolink_theme_id);
                } else {
                    biolink_preview_iframe_url.searchParams.delete('biolink_theme_id');
                }

                biolink_preview_iframe_url.search = biolink_preview_iframe_url.searchParams.toString();
                biolink_preview_iframe.setAttribute('src', biolink_preview_iframe_url.toString());

                biolink_preview_iframe.onload = () => {
                    document.querySelector('#biolink_preview_iframe').dispatchEvent(new Event('refreshed'));
                    document.querySelector('#biolink_preview_iframe_loading').classList.add('d-none');
                    resolve();

                }
            }, 750);
        });
    }

    /* Custom code: FC-2026-03-06: reset to factory template action */
    document.querySelector('#reset_biolink_factory_btn')?.addEventListener('click', event => {
        if(!window.confirm(<?= json_encode(l('biolink.reset_factory_confirm')) ?>)) {
            return;
        }

        let form = document.querySelector('form[name="update_biolink"]');
        let notification_container = form?.querySelector('.notification-container');
        if(notification_container) {
            notification_container.innerHTML = '';
        }

        $.ajax({
            type: 'POST',
            url: `${url}link-ajax`,
            dataType: 'json',
            data: {
                token: form?.querySelector('input[name="token"]')?.value,
                request_type: 'reset_biolink_factory',
                link_id: event.currentTarget.getAttribute('data-link-id')
            },
            success: (data) => {
                if(notification_container) {
                    display_notifications(data.message, data.status, notification_container);
                    notification_container.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                if(data.status === 'success') {
                    window.location.reload();
                }
            },
            error: () => {
                if(notification_container) {
                    display_notifications(<?= json_encode(l('global.error_message.basic')) ?>, 'error', notification_container);
                }
            }
        });
    });
    /* /Custom code: FC-2026-03-06 */

    /* Function to switch theme to custom */
    let set_biolink_theme_id_null = async () => {
        $('input[name="biolink_theme_id"][type="radio"]').prop('checked', false);
        $('input[name="biolink_theme_id"][type="radio"][value=""]').prop('checked', true);
        document.querySelector('#biolink_theme_id').value = '';
        update_biolink_theme_summary();
        if(window.update_biolink_theme_modal_current) {
            window.update_biolink_theme_modal_current();
        }

        //await biolink_theme_preview();
    }

    /* Display verified */
    let display_verified = () => {
        let verified_location = document.querySelector('input[name="verified_location"]:checked').value;
        let biolink_preview_iframe = $('#biolink_preview_iframe');

        switch(verified_location) {
            case 'top':
                biolink_preview_iframe.contents().find(`#link-verified-wrapper-top`).show();
                biolink_preview_iframe.contents().find(`#link-verified-wrapper-bottom`).hide();
                break;

            case 'bottom':
                biolink_preview_iframe.contents().find(`#link-verified-wrapper-top`).hide();
                biolink_preview_iframe.contents().find(`#link-verified-wrapper-bottom`).show();
                break;

            case '':
                biolink_preview_iframe.contents().find(`#link-verified-wrapper-top`).hide();
                biolink_preview_iframe.contents().find(`#link-verified-wrapper-bottom`).hide();
                break;
        }
    }

    document.querySelector('input[name="verified_location"]') && document.querySelectorAll('input[name="verified_location"]').forEach(element => element.addEventListener('change', display_verified));

    /* Text Color Handler */
    let settings_text_color_pickr = Pickr.create({
        el: '#settings_text_color_pickr',
        default: $('#settings_text_color').val(),
        ...pickr_options
    });

    settings_text_color_pickr.on('change', async hsva => {
        await set_biolink_theme_id_null();

        $('#settings_text_color').val(hsva.toHEXA().toString());
        $('#biolink_preview_iframe').contents().find('#branding').css('color', hsva.toHEXA().toString());
        if($('#biolink_preview_iframe').contents().find('#branding a')) {
            $('#biolink_preview_iframe').contents().find('#branding a').css('color', hsva.toHEXA().toString());
        }
    });

    /* Background blur */
    document.querySelector('#background_blur').addEventListener('change', event => {
        let blur = document.querySelector('#background_blur').value;
        let brightness = document.querySelector('#background_brightness').value;
        $('#biolink_preview_iframe').contents().find('.link-body-backdrop').css('backdrop-filter', `blur(${blur}px) brightness(${brightness}%)`);
        $('#biolink_preview_iframe').contents().find('.link-body-backdrop').css('-webkit-backdrop-filter', `blur(${blur}px) brightness(${brightness}%)`);
    });

    /* Background brightness */
    document.querySelector('#background_brightness').addEventListener('change', event => {
        let blur = document.querySelector('#background_blur').value;
        let brightness = document.querySelector('#background_brightness').value;
        $('#biolink_preview_iframe').contents().find('.link-body-backdrop').css('backdrop-filter', `blur(${blur}px) brightness(${brightness}%)`);
        $('#biolink_preview_iframe').contents().find('.link-body-backdrop').css('-webkit-backdrop-filter', `blur(${blur}px) brightness(${brightness}%)`);
    });

    /* Fonts size */
    document.querySelector('#settings_font_size').addEventListener('change', async event => {
        let font_size = event.currentTarget.value;
        const iframe_body = $('#biolink_preview_iframe').contents();
        iframe_body.find('html').get(0).style.setProperty('font-size', `${font_size}px`, 'important');
        await set_biolink_theme_id_null();
    });

    /* Font family */
    document.querySelectorAll('input[name="font"]').forEach(element => element.addEventListener('change', async event => {
        let font_key = event.currentTarget.value;
        let font_family = event.currentTarget.getAttribute('data-font-family');
        let font_css_url = event.currentTarget.getAttribute('data-font-css-url');
        if(!font_family) font_family = 'inherit';

        if(font_css_url) {
            let font_css_link = document.querySelector('#biolink_preview_iframe').contentDocument.createElement('link');

            if(!document.querySelector('#biolink_preview_iframe').contentDocument.head.querySelector(`link[id="${font_key}"]`)) {
                font_css_link.rel = 'stylesheet';
                font_css_link.href = font_css_url;
                font_css_link.id = font_key;
                document.querySelector('#biolink_preview_iframe').contentDocument.head.appendChild(font_css_link);
            }
        }

        document.querySelector('#biolink_preview_iframe').contentDocument.querySelector('body').style.setProperty('font-family', `${font_family}`, 'important');

        await set_biolink_theme_id_null();
    }));

    /* Background Type Handler */
    let background_type_handler = () => {
        let type = $('#settings_background_type').find(':selected').val();

        /* Show only the active background type */
        $(`div[id="background_type_${type}"]`).show();
        $(`div[id="background_type_${type}"]`).find('[name^="background"]').removeAttr('disabled');

        /* Disable the other possible types so they dont get submitted */
        let background_type_containers = $(`div[id^="background_type_"]:not(div[id$="_${type}"])`);

        background_type_containers.hide();
        background_type_containers.find('[name^="background"]').attr('disabled', 'disabled');
    };

    background_type_handler();

    $('#settings_background_type').on('change', background_type_handler);

    /* Preset background preview */
    $('#background_type_preset input[name="background"]').on('change', async event => {
        await set_biolink_theme_id_null();

        let preset_style = $(event.currentTarget).parent().find('.link-background-type-preset')[0].getAttribute('style');
        $('#biolink_preview_iframe').contents().find('body').attr('style', preset_style);
        $('#biolink_preview_iframe').contents().find('.link-video-background')[0].classList.add('d-none');
    });

    /* Preset background preview */
    $('#background_type_preset_abstract input[name="background"]').on('change', async event => {
        await set_biolink_theme_id_null();

        let preset_abstract_style = $(event.currentTarget).parent().find('.link-background-type-preset')[0].getAttribute('style');
        $('#biolink_preview_iframe').contents().find('body').attr('style', preset_abstract_style);
        $('#biolink_preview_iframe').contents().find('.link-video-background')[0].classList.add('d-none');
    });

    /* Gradient Background */
    let settings_background_type_gradient_color_one_pickr = Pickr.create({
        el: '#settings_background_type_gradient_color_one_pickr',
        default: $('#settings_background_type_gradient_color_one').val(),
        ...pickr_options
    });

    settings_background_type_gradient_color_one_pickr.on('change', async hsva => {
        await set_biolink_theme_id_null();

        $('#settings_background_type_gradient_color_one').val(hsva.toHEXA().toString());

        let color_one = $('#settings_background_type_gradient_color_one').val();
        let color_two = $('#settings_background_type_gradient_color_two').val();

        $('#biolink_preview_iframe').contents().find('body').attr('class', 'link-body').attr('style', `background-image: linear-gradient(135deg, ${color_one} 10%, ${color_two} 100%);`);
        $('#biolink_preview_iframe').contents().find('.link-video-background')[0].classList.add('d-none');
    });

    let settings_background_type_gradient_color_two_pickr = Pickr.create({
        el: '#settings_background_type_gradient_color_two_pickr',
        default: $('#settings_background_type_gradient_color_two').val(),
        ...pickr_options
    });

    settings_background_type_gradient_color_two_pickr.on('change', async hsva => {
        await set_biolink_theme_id_null();

        $('#settings_background_type_gradient_color_two').val(hsva.toHEXA().toString());

        let color_one = $('#settings_background_type_gradient_color_one').val();
        let color_two = $('#settings_background_type_gradient_color_two').val();

        $('#biolink_preview_iframe').contents().find('body').attr('class', 'link-body').attr('style', `background-image: linear-gradient(135deg, ${color_one} 10%, ${color_two} 100%);`);
        $('#biolink_preview_iframe').contents().find('.link-video-background')[0].classList.add('d-none');
    });

    /* Color Background */
    let settings_background_type_color_pickr = Pickr.create({
        el: '#settings_background_type_color_pickr',
        default: $('#settings_background_type_color').val(),
        ...pickr_options
    });

    settings_background_type_color_pickr.on('change', async hsva => {
        await set_biolink_theme_id_null();

        $('#settings_background_type_color').val(hsva.toHEXA().toString());

        $('#biolink_preview_iframe').contents().find('body').attr('class', 'link-body').attr('style', `background: ${hsva.toHEXA().toString()};`);
        $('#biolink_preview_iframe').contents().find('.link-video-background')[0].classList.add('d-none');
    });

    /* Image Background */
    function generate_background_preview(input) {
        if(input.files && input.files[0]) {
            let reader = new FileReader();

            reader.onload = event => {
                $('#background_type_image_preview').attr('src', event.target.result);
                $('#biolink_preview_iframe').contents().find('body').attr('class', 'link-body').attr('style', `background: url(${event.target.result});`);
            };

            reader.readAsDataURL(input.files[0]);
        }
    }

    $('#background_type_image_input').on('change', async event => {
        await set_biolink_theme_id_null();

        generate_background_preview(event.currentTarget);
    });

    /* Display branding switcher */
    $('#display_branding').on('change', event => {
        if($(event.currentTarget).is(':checked')) {
            $('#biolink_preview_iframe').contents().find('#branding').show();
        } else {
            $('#biolink_preview_iframe').contents().find('#branding').hide();
        }
    });

    /* Branding change */
    $('#branding_name').on('change paste keyup', event => {
        let branding_name = event.currentTarget.value.trim();

        if(branding_name != '') {
            $('#biolink_preview_iframe').contents().find('#branding').text(branding_name);
            document.querySelector('#branding_url_text_color').classList.remove('container-disabled');
        } else {
            document.querySelector('#branding_url_text_color').classList.add('container-disabled');
        }
    });

    /* Form handling update */
    $('form[name="update_biolink"],form[name="update_biolink_"]').on('submit', (event, autosave_data) => {
        event.preventDefault();

        /* Check if autosave or manual */
        let is_autosave = autosave_data && autosave_data.is_autosave === true;

        let form = $(event.currentTarget)[0];

        if(form?.getAttribute('data-type') === 'lead_funnel' && window.syncLeadFunnelRichTextEditors) {
            window.syncLeadFunnelRichTextEditors(form);
        }

        let data = new FormData(form);

        if(form?.getAttribute('data-type') === 'lead_funnel' && window.getLeadFunnelRichTextValues) {
            Object.entries(window.getLeadFunnelRichTextValues(form)).forEach(([name, value]) => {
                data.set(name, value);
            });
        }

        let notification_container = event.currentTarget.querySelector('.notification-container');
        if(!notification_container) {
            notification_container = document.createElement('div');
            notification_container.className = 'notification-container';
            event.currentTarget.prepend(notification_container);
        }
        notification_container.innerHTML = '';

        pause_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));

        $.ajax({
            type: 'POST',
            enctype: 'multipart/form-data',
            processData: false,
            contentType: false,
            cache: false,
            url: event.currentTarget.getAttribute('name') == 'update_biolink_' ? `${url}biolink-block-ajax` : `${url}link-ajax`,
            data: data,
            dataType: 'json',
            success: (data) => {
                if(notification_container) {
                    display_notifications(data.message, data.status, notification_container);
                }

                /* Auto scroll to notification */
                if(!is_autosave) {
                    notification_container?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                enable_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'))

                /* Update image previews for some link types */
                if(event.currentTarget.getAttribute('name') == 'update_biolink_') {
                    if(data.details.images) {
                        for(const [key, value] of Object.entries(data.details.images)) {
                            event.currentTarget.querySelector(`input[name="${key}"]`).value = null;

                            if(event.currentTarget.querySelector(`[name="${key}_remove"]`) && event.currentTarget.querySelector(`[name="${key}_remove"]`).checked) {
                                event.currentTarget.querySelector(`[name="${key}_remove"]`).click();
                            }

                            if(value) {
                                event.currentTarget.querySelector(`[data-image-container="${key}"] img`).setAttribute('src', value);
                                event.currentTarget.querySelector(`[data-image-container="${key}"] img`).setAttribute('data-src', value);
                                event.currentTarget.querySelector(`[data-image-container="${key}"] img`).classList.remove('d-none');
                                event.currentTarget.querySelector(`[data-image-container="${key}"] a`).setAttribute('href', value);
                                event.currentTarget.querySelector(`[data-image-container="${key}"] a`).classList.remove('d-none');
                                event.currentTarget.querySelectorAll(`[data-image-container="${key}"]`).forEach(element => element.classList.remove('d-none'));
                                event.currentTarget.querySelector(`[id*="_remove_selected_file_wrapper"]`).classList.add('d-none');
                            } else {
                                if(event.currentTarget.querySelector(`[data-image-container="${key}"] img`)) {
                                    event.currentTarget.querySelector(`[data-image-container="${key}"] img`).setAttribute('src', '');
                                    event.currentTarget.querySelector(`[data-image-container="${key}"] img`).classList.add('d-none');
                                    event.currentTarget.querySelector(`[data-image-container="${key}"] img`).removeAttribute('data-src');
                                }
                                event.currentTarget.querySelectorAll(`[data-image-container="${key}"]`).forEach(element => element.classList.add('d-none'));
                            }
                        }
                    }
                }

                if(event.currentTarget.getAttribute('name') == 'update_biolink') {
                    if(data.status == 'success') {
                        update_main_url(data.details.url);
                    }

                    if(data.details?.images) {
                        for(const [key, value] of Object.entries(data.details.images)) {
                            event.currentTarget.querySelector(`input[name="${key}"]`).value = null;

                            if(event.currentTarget.querySelector(`[name="${key}_remove"]`) && event.currentTarget.querySelector(`[name="${key}_remove"]`).checked) {
                                event.currentTarget.querySelector(`[name="${key}_remove"]`).click();
                            }

                            if(value) {
                                event.currentTarget.querySelector(`[data-image-container="${key}"] img`)?.setAttribute('src', value);
                                event.currentTarget.querySelector(`[data-image-container="${key}"] img`)?.classList.remove('d-none');
                                event.currentTarget.querySelector(`[data-image-container="${key}"] a`)?.setAttribute('href', value);
                                event.currentTarget.querySelector(`[data-image-container="${key}"] a`)?.classList.remove('d-none');
                                event.currentTarget.querySelectorAll(`[data-image-container="${key}"]`)?.forEach(element => element.classList.remove('d-none'));
                            } else {
                                event.currentTarget.querySelector(`[data-image-container="${key}"] img`)?.setAttribute('src', '');
                                event.currentTarget.querySelector(`[data-image-container="${key}"] img`)?.classList.add('d-none');
                                event.currentTarget.querySelector(`[data-image-container="${key}"] a`)?.setAttribute('href', '');
                                event.currentTarget.querySelector(`[data-image-container="${key}"] a`)?.classList.add('d-none');
                                event.currentTarget.querySelectorAll(`[data-image-container="${key}"]`)?.forEach(element => element.classList.add('d-none'));
                            }

                            if(key == 'background') {
                                event.currentTarget.querySelector('#background_type_image_input').value = '';
                            } else {
                                event.currentTarget.querySelector(`#${key}`).value = '';
                            }
                        }
                    }
                }

                /* Refresh iframe */
                refresh_biolink_preview();

            },
            error: () => {
                enable_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));
                if(notification_container) {
                    display_notifications(<?= json_encode(l('global.error_message.basic')) ?>, 'error', notification_container);
                }
            },
        });
    })

    /* Form handling create */
    $('form[name^="create_biolink_"]').on('submit', event => {
        event.preventDefault();

        let form = $(event.currentTarget)[0];
        let data = new FormData(form);
        if(typeof global_token !== 'undefined' && !data.has('global_token')) {
            data.append('global_token', global_token);
        }
        pause_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));

        $.ajax({
            type: 'POST',
            enctype: 'multipart/form-data',
            processData: false,
            contentType: false,
            cache: false,
            url: `${url}biolink-block-ajax`,
            data: data,
            dataType: 'json',
            success: (data) => {
                let notification_container = event.currentTarget.querySelector('.notification-container');
                if(!notification_container) {
                    notification_container = document.createElement('div');
                    notification_container.className = 'notification-container';
                    event.currentTarget.prepend(notification_container);
                }
                notification_container.innerHTML = '';
                enable_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));

                if(data.status == 'error') {
                    display_notifications(data.message, 'error', notification_container);
                }

                else if(data.status == 'success') {

                    /* Redirect */
                    redirect(data.details.url, true);

                }
            },
            error: () => {
                let notification_container = event.currentTarget.querySelector('.notification-container');
                if(!notification_container) {
                    notification_container = document.createElement('div');
                    notification_container.className = 'notification-container';
                    event.currentTarget.prepend(notification_container);
                }
                notification_container.innerHTML = '';
                enable_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));
                display_notifications(<?= json_encode(l('global.error_message.basic')) ?>, 'error', notification_container);
            },
        });
    })

    /* Daterangepicker */
    let locale = <?= json_encode(require APP_PATH . 'includes/daterangepicker_translations.php') ?>;
    $('[data-daterangepicker]').daterangepicker({
        minDate: "<?= (new \DateTime('', new \DateTimeZone(\Altum\Date::$default_timezone)))->setTimezone(new \DateTimeZone($this->user->timezone))->format('Y-m-d H:i:s'); ?>",
        alwaysShowCalendars: true,
        singleCalendar: true,
        singleDatePicker: true,
        locale: {...locale, format: 'YYYY-MM-DD HH:mm:ss'},
        timePicker: true,
        timePicker24Hour: true,
        timePickerSeconds: true,
    }, (start, end, label) => {});
</script>

<script>
    'use strict';

    /* Links tab sortable */
    let sortable = Sortable.create(document.getElementById('biolink_blocks'), {
        animation: 150,
        handle: '.drag',
        onUpdate: event => {

            let biolink_blocks = [];
            $('#biolink_blocks > .biolink_block').each((i, elm) => {
                biolink_blocks.push({
                    biolink_block_id: $(elm).data('biolink-block-id'),
                    order: i
                });
            });

            $.ajax({
                type: 'POST',
                url: `${url}biolink-block-ajax`,
                dataType: 'json',
                data: {
                    request_type: 'order',
                    biolink_blocks,
                    global_token
                },
            });

            /* Refresh iframe */
            refresh_biolink_preview();

            /* Refresh tooltips */
            tooltips_initiate();
        }
    });

    /* Status change handler for the links */
    $('[id^="biolink_block_is_enabled_"]').on('change', event => {
        ajax_call_helper(event, 'biolink-block-ajax', 'is_enabled_toggle', () => {

            $(event.currentTarget).closest('.biolink_block').toggleClass('custom-row-inactive');

            /* Refresh iframe */
            refresh_biolink_preview();
        });
    });

    /* When an expanding happens for a link settings */
    $('[id^="biolink_block_expanded_content"]').on('shown.bs.collapse', event => {
        let update_form_content = event.currentTarget;
        let link_type = $(update_form_content).data('link-type');
        let biolink_block_id = $(update_form_content.querySelector('input[name="biolink_block_id"]')).val();
        let biolink_link = $('#biolink_preview_iframe').contents().find(`div[data-biolink-block-id="${biolink_block_id}"]`);

        $('#biolink_preview_iframe').off('refreshed.blockPreview').on('refreshed.blockPreview', event => {
            setTimeout(() => {
                biolink_link = $('#biolink_preview_iframe').contents().find(`div[data-biolink-block-id="${biolink_block_id}"]`);
                block_expanded_content_init();
            }, 900)
        })

        let extra_updating_and_potentially_color_inputs = [];

        let block_expanded_content_init = () => {
            type_handler(`#biolink_block_expanded_content_${biolink_block_id} select[name="animation"]`, 'data-animation', '*=');
            update_form_content.querySelector(`#biolink_block_expanded_content_${biolink_block_id} select[name="animation"]`) && update_form_content.querySelectorAll(`#biolink_block_expanded_content_${biolink_block_id} select[name="animation"]`).forEach(element => element.addEventListener('change', () => { type_handler(`#biolink_block_expanded_content_${biolink_block_id} select[name="animation"]`, 'data-animation', '*='); }));

            switch (link_type) {
                case 'link':
                case 'file':
                case 'cta':
                case 'share':
                case 'pdf_document':
                case 'powerpoint_presentation':
                case 'excel_spreadsheet':
                case 'email_collector':
                case 'phone_collector':
                case 'paypal':
                case 'vcard':
                case 'donation':
                case 'service':
                case 'product':
                case 'rss_feed':
                case 'youtube_feed':
                case 'lead_funnel':
                    extra_updating_and_potentially_color_inputs = ['name'];

                    window.initLeadFunnelRichTextEditors && window.initLeadFunnelRichTextEditors(update_form_content);

                    let lead_funnel_open_mode_handler = () => {
                        let open_mode_select = update_form_content.querySelector('select[name="open_mode"]');
                        let lead_funnel_button = biolink_link.find('a.link-btn');

                        if(!open_mode_select || !lead_funnel_button.length) {
                            return;
                        }

                        let page_url = `${url}l/link?biolink_block_id=${biolink_block_id}`;

                        if(open_mode_select.value === 'page') {
                            lead_funnel_button.attr('href', page_url);
                            lead_funnel_button.removeAttr('data-toggle');
                            lead_funnel_button.removeAttr('data-target');
                        } else {
                            lead_funnel_button.attr('href', '#');
                            lead_funnel_button.attr('data-toggle', 'modal');
                            lead_funnel_button.attr('data-target', `#lead_funnel_${biolink_block_id}`);
                        }

                        update_form_content.querySelectorAll('[data-lead-funnel-open-mode-setting]').forEach(element => {
                            let allowed_modes = (element.getAttribute('data-lead-funnel-open-mode-setting') || '').split(',');
                            element.classList.toggle('d-none', !allowed_modes.includes(open_mode_select.value));
                        });

                        lead_funnel_apply_primary_preview_mode();
                    };

                    /* Custom code: FC-2026-03-23: popup design pickers */
                    let lead_funnel_popup_container = $('#biolink_preview_iframe').contents().find(`#lead_funnel_${biolink_block_id} [data-lead-funnel-container]`);
                    let lead_funnel_apply_primary_preview_mode = () => {
                        let mini_preview = update_form_content.querySelector('[data-lead-funnel-popup-mini-preview]');
                        let open_mode_select = update_form_content.querySelector('select[name="open_mode"]');

                        if(!mini_preview || !open_mode_select) {
                            return;
                        }

                        let mode = open_mode_select.value || 'popup';
                        let mode_prefix = mode === 'page' ? 'page' : 'popup';
                        let back_link = mini_preview.querySelector('[data-lead-funnel-preview-back-link]');
                        let close_button = mini_preview.querySelector('[data-lead-funnel-preview-close]');

                        mini_preview.style.setProperty('--lead-funnel-background-color', update_form_content.querySelector(`input[name="${mode_prefix}_background_color"]`)?.value || '#ffffff');
                        mini_preview.style.setProperty('--lead-funnel-text-color', update_form_content.querySelector(`input[name="${mode_prefix}_text_color"]`)?.value || '#212529');
                        mini_preview.style.setProperty('--lead-funnel-button-background-color', update_form_content.querySelector(`input[name="${mode_prefix}_button_background_color"]`)?.value || '#007bff');
                        mini_preview.style.setProperty('--lead-funnel-button-text-color', update_form_content.querySelector(`input[name="${mode_prefix}_button_text_color"]`)?.value || '#ffffff');

                        if(back_link) {
                            back_link.classList.toggle('d-none', mode !== 'page');
                        }

                        if(close_button) {
                            close_button.classList.toggle('d-none', mode === 'page');
                        }
                    };

                    let bind_lead_funnel_popup_pickr = (pickr_selector, input_name, css_variable) => {
                        let pickr_element = update_form_content.querySelector(pickr_selector);
                        let color_input = update_form_content.querySelector(`input[name="${input_name}"]`);

                        if(!pickr_element || !color_input) {
                            return;
                        }

                        let color_pickr = Pickr.create({
                            el: pickr_element,
                            default: $(color_input).val(),
                            ...pickr_options
                        });

                        color_pickr.off().on('change', hsva => {
                            let color = hsva.toHEXA().toString();
                            $(color_input).val(color);

                            if(lead_funnel_popup_container.length) {
                                lead_funnel_popup_container.get(0).style.setProperty(css_variable, color);
                            }

                            let mini_preview = update_form_content.querySelector('[data-lead-funnel-popup-mini-preview]');
                            if(mini_preview) {
                                mini_preview.style.setProperty(css_variable, color);
                            }

                            lead_funnel_apply_primary_preview_mode();
                        });
                    };

                    bind_lead_funnel_popup_pickr('.lead_funnel_popup_background_color_pickr', 'popup_background_color', '--lead-funnel-background-color');
                    bind_lead_funnel_popup_pickr('.lead_funnel_popup_text_color_pickr', 'popup_text_color', '--lead-funnel-text-color');
                    bind_lead_funnel_popup_pickr('.lead_funnel_popup_button_background_color_pickr', 'popup_button_background_color', '--lead-funnel-button-background-color');
                    bind_lead_funnel_popup_pickr('.lead_funnel_popup_button_text_color_pickr', 'popup_button_text_color', '--lead-funnel-button-text-color');

                    let bind_lead_funnel_page_pickr = (pickr_selector, input_name) => {
                        let pickr_element = update_form_content.querySelector(pickr_selector);
                        let color_input = update_form_content.querySelector(`input[name="${input_name}"]`);

                        if(!pickr_element || !color_input) {
                            return;
                        }

                        let color_pickr = Pickr.create({
                            el: pickr_element,
                            default: $(color_input).val(),
                            ...pickr_options
                        });

                        color_pickr.off().on('change', hsva => {
                            let color = hsva.toHEXA().toString();
                            $(color_input).val(color);

                            lead_funnel_apply_primary_preview_mode();
                        });
                    };

                    bind_lead_funnel_page_pickr('.lead_funnel_page_background_color_pickr', 'page_background_color');
                    bind_lead_funnel_page_pickr('.lead_funnel_page_text_color_pickr', 'page_text_color');
                    bind_lead_funnel_page_pickr('.lead_funnel_page_button_background_color_pickr', 'page_button_background_color');
                    bind_lead_funnel_page_pickr('.lead_funnel_page_button_text_color_pickr', 'page_button_text_color');

                    let lead_funnel_sanitize_preview_html = raw_html => {
                        let template = document.createElement('template');
                        template.innerHTML = raw_html || '';

                        let allowed_tags = new Set(['A', 'B', 'BLOCKQUOTE', 'BR', 'EM', 'I', 'LI', 'OL', 'P', 'S', 'SPAN', 'STRONG', 'U', 'UL']);
                        let allowed_classes = ['ql-align-', 'ql-font-', 'ql-size-'];
                        let allowed_style_properties = ['background-color', 'color', 'text-align'];

                        let sanitize_node = node => {
                            [...node.childNodes].forEach(child => {
                                if(child.nodeType === Node.TEXT_NODE) {
                                    return;
                                }

                                if(child.nodeType !== Node.ELEMENT_NODE) {
                                    child.remove();
                                    return;
                                }

                                if(!allowed_tags.has(child.tagName)) {
                                    let fragment = document.createDocumentFragment();
                                    while(child.firstChild) {
                                        fragment.appendChild(child.firstChild);
                                    }
                                    child.replaceWith(fragment);
                                    sanitize_node(node);
                                    return;
                                }

                                [...child.attributes].forEach(attribute => {
                                    let attribute_name = attribute.name.toLowerCase();

                                    if(attribute_name === 'class') {
                                        child.className = [...child.classList].filter(class_name => allowed_classes.some(prefix => class_name.startsWith(prefix))).join(' ');
                                        if(!child.className) {
                                            child.removeAttribute('class');
                                        }
                                        return;
                                    }

                                    if(attribute_name === 'href' && child.tagName === 'A') {
                                        return;
                                    }

                                    if((attribute_name === 'target' || attribute_name === 'rel') && child.tagName === 'A') {
                                        return;
                                    }

                                    if(attribute_name === 'data-list' && child.tagName === 'LI') {
                                        return;
                                    }

                                    if(attribute_name === 'style') {
                                        let sanitized_style = attribute.value
                                            .split(';')
                                            .map(style_rule => style_rule.trim())
                                            .filter(style_rule => {
                                                let [property_name, property_value] = style_rule.split(':');

                                                property_name = property_name?.trim().toLowerCase();
                                                property_value = property_value?.trim().toLowerCase() || '';

                                                if(!property_name || !allowed_style_properties.includes(property_name)) {
                                                    return false;
                                                }

                                                /* Keep preview text readable on light funnel previews by ignoring white-ish text colors. */
                                                if(property_name === 'color' && ['#fff', '#ffffff', 'white', 'rgb(255,255,255)', 'rgb(255, 255, 255)'].includes(property_value.replace(/\s+/g, ''))) {
                                                    return false;
                                                }

                                                return true;
                                            })
                                            .join('; ');

                                        if(sanitized_style) {
                                            child.setAttribute('style', sanitized_style);
                                        } else {
                                            child.removeAttribute('style');
                                        }

                                        return;
                                    }

                                    child.removeAttribute(attribute.name);
                                });

                                if(child.tagName === 'A') {
                                    child.setAttribute('target', '_blank');
                                    child.setAttribute('rel', 'noopener noreferrer nofollow');
                                }

                                sanitize_node(child);
                            });
                        };

                        sanitize_node(template.content);

                        return template.innerHTML;
                    };

                    let lead_funnel_render_rich_preview = (element, html) => {
                        if(!element) {
                            return;
                        }

                        let normalized_html = lead_funnel_sanitize_preview_html((html || '').trim());
                        let is_empty = !normalized_html || normalized_html === '<p><br></p>';

                        element.innerHTML = normalized_html;
                        element.style.display = is_empty ? 'none' : '';
                    };

                    let lead_funnel_get_video_embed_url = (provider, video_url) => {
                        provider = provider || 'youtube';
                        video_url = (video_url || '').trim();

                        if(!video_url) {
                            return null;
                        }

                        if(provider === 'vimeo') {
                            let vimeo_match = video_url.match(/vimeo\.com\/(?:video\/)?([0-9]+)/i);
                            return vimeo_match ? `https://player.vimeo.com/video/${vimeo_match[1]}` : null;
                        }

                        let youtube_match = video_url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/i);
                        return youtube_match ? `https://www.youtube.com/embed/${youtube_match[1]}` : null;
                    };

                    let lead_funnel_set_popup_preview_screen = screen => {
                        update_form_content.querySelectorAll('[data-lead-funnel-popup-preview-screen]').forEach(element => {
                            element.classList.toggle('d-none', element.getAttribute('data-lead-funnel-popup-preview-screen') !== screen);
                        });

                        update_form_content.querySelectorAll('[data-lead-funnel-popup-preview-screen-toggle]').forEach(element => {
                            element.classList.toggle('active', element.getAttribute('data-lead-funnel-popup-preview-screen-toggle') === screen);
                        });
                    };

                    let lead_funnel_popup_mini_preview_handler = () => {
                        let mini_preview = update_form_content.querySelector('[data-lead-funnel-popup-mini-preview]');

                        if(!mini_preview) {
                            return;
                        }

                        let name_input = update_form_content.querySelector('input[name="name"]');
                        let title_input = update_form_content.querySelector('input[name="popup_title"]');
                        let subtitle_input = update_form_content.querySelector('textarea[name="popup_subtitle"]');
                        let description_input = update_form_content.querySelector('textarea[name="description"]');
                        let agreement_text_input = update_form_content.querySelector('textarea[name="agreement_text"]');
                        let agreement_url_input = update_form_content.querySelector('input[name="agreement_url"]');
                        let button_text_input = update_form_content.querySelector('input[name="button_text"]');
                        let thank_you_title_input = update_form_content.querySelector('textarea[name="thank_you_title"]');
                        let thank_you_text_input = update_form_content.querySelector('textarea[name="thank_you_text"]');
                        let thank_you_type_input = update_form_content.querySelector('select[name="thank_you_type"]');
                        let thank_you_button_text_input = update_form_content.querySelector('input[name="thank_you_button_text"]');
                        let video_provider_input = update_form_content.querySelector('select[name="video_provider"]');
                        let video_url_input = update_form_content.querySelector('input[name="video_url"]');
                        let image_input = update_form_content.querySelector('input[name="image"]');
                        let image_remove_input = update_form_content.querySelector('input[name="image_remove"]');

                        let preview_title = mini_preview.querySelector('[data-lead-funnel-popup-mini-preview-title]');
                        let preview_subtitle = mini_preview.querySelector('[data-lead-funnel-popup-mini-preview-subtitle]');
                        let preview_description = mini_preview.querySelector('[data-lead-funnel-popup-mini-preview-description]');
                        let preview_button = mini_preview.querySelector('[data-lead-funnel-popup-mini-preview-button]');
                        let preview_video_wrapper = mini_preview.querySelector('[data-lead-funnel-popup-mini-preview-video-wrapper]');
                        let preview_video_label = mini_preview.querySelector('[data-lead-funnel-popup-mini-preview-video-label]');
                        let preview_video_iframe = mini_preview.querySelector('[data-lead-funnel-popup-mini-preview-video-iframe]');
                        let preview_video_placeholder = mini_preview.querySelector('[data-lead-funnel-popup-mini-preview-video-placeholder]');
                        let preview_image_wrapper = mini_preview.querySelector('[data-lead-funnel-popup-mini-preview-image-wrapper]');
                        let preview_image = mini_preview.querySelector('[data-lead-funnel-popup-mini-preview-image]');
                        let preview_agreement_wrapper = mini_preview.querySelector('[data-lead-funnel-popup-preview-agreement-wrapper]');
                        let preview_agreement_text = mini_preview.querySelector('[data-lead-funnel-popup-preview-agreement-text]');
                        let preview_agreement_link_wrapper = mini_preview.querySelector('[data-lead-funnel-popup-preview-agreement-link-wrapper]');
                        let preview_thank_you_title = mini_preview.querySelector('[data-lead-funnel-popup-preview-thank-you-title]');
                        let preview_thank_you_text = mini_preview.querySelector('[data-lead-funnel-popup-preview-thank-you-text]');
                        let preview_thank_you_button = mini_preview.querySelector('[data-lead-funnel-popup-preview-thank-you-button]');

                        let title = (title_input?.value || '').trim() || (name_input?.value || '').trim();
                        let button_text = (button_text_input?.value || '').trim() || <?= json_encode(l('biolink_lead_funnel.button_text_default')) ?>;
                        let subtitle_html = subtitle_input?.value || '';
                        let description_html = description_input?.value || '';
                        let agreement_text_html = agreement_text_input?.value || '';
                        let thank_you_title_html = thank_you_title_input?.value || '';
                        let thank_you_text_html = thank_you_text_input?.value || '';
                        let preview_video_embed_url = lead_funnel_get_video_embed_url(video_provider_input?.value, video_url_input?.value);
                        let has_video = !!(video_url_input?.value || '').trim().length;
                        let preview_image_source = '';

                        if(image_input?.files?.length) {
                            preview_image_source = URL.createObjectURL(image_input.files[0]);
                        } else {
                            preview_image_source = mini_preview.getAttribute('data-initial-image-src') || preview_image?.getAttribute('src') || '';
                        }

                        if(image_remove_input?.checked) {
                            preview_image_source = '';
                        }

                        if(preview_title) {
                            preview_title.textContent = title;
                        }

                        lead_funnel_render_rich_preview(preview_subtitle, subtitle_html);
                        lead_funnel_render_rich_preview(preview_description, description_html);

                        if(preview_button) {
                            preview_button.textContent = button_text;
                        }

                        if(preview_image_wrapper && preview_image) {
                            preview_image_wrapper.classList.toggle('d-none', !preview_image_source);
                            if(preview_image_source) {
                                preview_image.setAttribute('src', preview_image_source);
                            }
                        }

                        if(preview_video_wrapper) {
                            preview_video_wrapper.classList.toggle('d-none', !has_video);
                        }

                        if(preview_video_label) {
                            preview_video_label.textContent = ((video_provider_input?.value || 'youtube') + '').toUpperCase();
                        }

                        if(preview_video_iframe) {
                            preview_video_iframe.setAttribute('src', preview_video_embed_url || 'about:blank');
                        }

                        if(preview_video_placeholder) {
                            preview_video_placeholder.classList.toggle('d-none', !!preview_video_embed_url);
                        }

                        update_form_content.querySelectorAll('[data-lead-funnel-popup-preview-field]').forEach(element => {
                            let field = element.getAttribute('data-lead-funnel-popup-preview-field');
                            let toggle = update_form_content.querySelector(`input[name="show_${field}"]`);
                            element.classList.toggle('d-none', !toggle?.checked);
                        });

                        ['email', 'phone', 'name', 'message'].forEach(field => {
                            let placeholder_input = update_form_content.querySelector(`input[name="${field}_placeholder"]`);
                            let preview_placeholder = mini_preview.querySelector(`[data-lead-funnel-popup-preview-placeholder="${field}"]`);
                            if(preview_placeholder) {
                                preview_placeholder.setAttribute('placeholder', placeholder_input?.value || '');
                            }
                        });

                        if(preview_agreement_wrapper) {
                            preview_agreement_wrapper.classList.toggle('d-none', !update_form_content.querySelector('input[name="show_agreement"]')?.checked);
                        }

                        lead_funnel_render_rich_preview(preview_agreement_text, agreement_text_html);

                        if(preview_agreement_link_wrapper) {
                            preview_agreement_link_wrapper.classList.toggle('d-none', !(agreement_url_input?.value || '').trim());
                        }

                        lead_funnel_render_rich_preview(preview_thank_you_title, thank_you_title_html);
                        lead_funnel_render_rich_preview(preview_thank_you_text, thank_you_text_html);

                        if(preview_thank_you_button) {
                            preview_thank_you_button.textContent = (thank_you_button_text_input?.value || '').trim() || <?= json_encode(l('biolink_lead_funnel.thank_you_button_text_default')) ?>;
                            preview_thank_you_button.classList.toggle('d-none', (thank_you_type_input?.value || 'message') !== 'file_download');
                        }

                        if(lead_funnel_popup_container.length) {
                            lead_funnel_popup_container.find('[data-lead-funnel-popup-title]').text(title);

                            let popup_subtitle = lead_funnel_popup_container.find('[data-lead-funnel-popup-subtitle]');
                            if(popup_subtitle.length) {
                                let safe_subtitle_html = lead_funnel_sanitize_preview_html(subtitle_html);

                                if((safe_subtitle_html || '').trim() && (safe_subtitle_html || '').trim() !== '<p><br></p>') {
                                    popup_subtitle.html(safe_subtitle_html).show();
                                } else {
                                    popup_subtitle.hide();
                                }
                            }

                            let popup_description = lead_funnel_popup_container.find('[data-lead-funnel-popup-description]');
                            if(popup_description.length) {
                                let safe_description_html = lead_funnel_sanitize_preview_html(description_html);

                                if((safe_description_html || '').trim() && (safe_description_html || '').trim() !== '<p><br></p>') {
                                    popup_description.html(safe_description_html).show();
                                } else {
                                    popup_description.hide();
                                }
                            }

                            lead_funnel_popup_container.find('[data-lead-funnel-submit-button]').text(button_text);

                            let agreement_text = lead_funnel_popup_container.find('[data-lead-funnel-agreement-text]');
                            if(agreement_text.length) {
                                let safe_agreement_html = lead_funnel_sanitize_preview_html(agreement_text_html);

                                if((safe_agreement_html || '').trim() && (safe_agreement_html || '').trim() !== '<p><br></p>') {
                                    agreement_text.html(safe_agreement_html);
                                } else {
                                    agreement_text.text('');
                                }
                            }

                            let thank_you_title = lead_funnel_popup_container.find('[data-lead-funnel-thank-you-title]');
                            if(thank_you_title.length) {
                                let safe_thank_you_title_html = lead_funnel_sanitize_preview_html(thank_you_title_html);

                                if((safe_thank_you_title_html || '').trim() && (safe_thank_you_title_html || '').trim() !== '<p><br></p>') {
                                    thank_you_title.html(safe_thank_you_title_html).show();
                                } else {
                                    thank_you_title.hide();
                                }
                            }

                            let thank_you_text = lead_funnel_popup_container.find('[data-lead-funnel-thank-you-text]');
                            if(thank_you_text.length) {
                                let safe_thank_you_text_html = lead_funnel_sanitize_preview_html(thank_you_text_html);

                                if((safe_thank_you_text_html || '').trim() && (safe_thank_you_text_html || '').trim() !== '<p><br></p>') {
                                    thank_you_text.html(safe_thank_you_text_html).show();
                                } else {
                                    thank_you_text.hide();
                                }
                            }

                            lead_funnel_popup_container.find('[data-thank-you-button]').text((thank_you_button_text_input?.value || '').trim() || <?= json_encode(l('biolink_lead_funnel.thank_you_button_text_default')) ?>);
                        }
                    };

                    $(update_form_content.querySelectorAll('input[name="name"], input[name="popup_title"], textarea[name="popup_subtitle"], textarea[name="description"], textarea[name="agreement_text"], input[name="agreement_url"], input[name="button_text"], textarea[name="thank_you_title"], textarea[name="thank_you_text"], input[name="thank_you_button_text"], select[name="thank_you_type"], input[name="video_url"], select[name="video_provider"], input[name="show_name"], input[name="show_email"], input[name="show_phone"], input[name="show_message"], input[name="show_agreement"], input[name="email_placeholder"], input[name="phone_placeholder"], input[name="name_placeholder"], input[name="message_placeholder"], input[name="image"], input[name="image_remove"]')).off().on('input change paste keyup', lead_funnel_popup_mini_preview_handler);
                    $(update_form_content.querySelectorAll('[data-lead-funnel-popup-preview-screen-toggle]')).off().on('click', event => {
                        lead_funnel_set_popup_preview_screen(event.currentTarget.getAttribute('data-lead-funnel-popup-preview-screen-toggle'));
                    });
                    lead_funnel_set_popup_preview_screen('form');
                    lead_funnel_apply_primary_preview_mode();
                    lead_funnel_popup_mini_preview_handler();
                    /* /Custom code: FC-2026-03-23 */

                    /* Custom code: FC-2026-03-23: conditional thank you settings */
                    let lead_funnel_thank_you_type_handler = () => {
                        let thank_you_type_select = update_form_content.querySelector('select[name="thank_you_type"]');
                        let thank_you_file_source_select = update_form_content.querySelector('select[name="thank_you_file_source"]');

                        if(!thank_you_type_select) {
                            return;
                        }

                        update_form_content.querySelectorAll('[data-lead-funnel-thank-you-setting]').forEach(element => {
                            element.classList.toggle('d-none', element.getAttribute('data-lead-funnel-thank-you-setting') !== thank_you_type_select.value);
                        });

                        update_form_content.querySelectorAll('[data-lead-funnel-file-source-setting]').forEach(element => {
                            let should_hide = thank_you_type_select.value !== 'file_download';

                            if(!should_hide && thank_you_file_source_select) {
                                should_hide = element.getAttribute('data-lead-funnel-file-source-setting') !== thank_you_file_source_select.value;
                            }

                            element.classList.toggle('d-none', should_hide);
                        });
                    };

                    $(update_form_content.querySelector('select[name="thank_you_type"]')).off().on('change', lead_funnel_thank_you_type_handler);
                    $(update_form_content.querySelector('select[name="thank_you_file_source"]')).off().on('change', lead_funnel_thank_you_type_handler);
                    lead_funnel_thank_you_type_handler();
                    /* /Custom code: FC-2026-03-23 */

                    $(update_form_content.querySelector('select[name="open_mode"]')).off().on('change', lead_funnel_open_mode_handler);
                    lead_funnel_open_mode_handler();

                    break;

                case 'custom_html_whatsapp':
                    extra_updating_and_potentially_color_inputs = ['title'];
                    break;

                case 'alert':
                    extra_updating_and_potentially_color_inputs = ['text'];
                    break;

                case 'weather':
                    extra_updating_and_potentially_color_inputs = ['text', 'description'];
                    break;

                case 'review':
                    extra_updating_and_potentially_color_inputs = ['title', 'description', 'author_name', 'author_description', 'stars'];
                    break;

                case 'business_hours':
                    extra_updating_and_potentially_color_inputs = ['title', 'description', 'icon'];
                    break;

                case 'external_item':
                    extra_updating_and_potentially_color_inputs = ['name', 'description', 'price'];
                    break;

                case 'timeline':
                    extra_updating_and_potentially_color_inputs = ['title', 'description', 'date'];

                    let line_color_pickr = update_form_content.querySelector(`.line_color_pickr`);
                    let line_color_input = update_form_content.querySelector(`input[name="line_color"]`);

                    if(line_color_pickr) {
                        let color_pickr = Pickr.create({
                            el: line_color_pickr,
                            default: line_color_input.value,
                            ...pickr_options
                        });

                        color_pickr.off().on('change', hsva => {
                            line_color_input.value = hsva.toHEXA().toString();

                            biolink_link.find(`[data-line-background-color]`).css('background-color', hsva.toHEXA().toString());
                            biolink_link.find(`[data-line-border-color]`).css('border-color', hsva.toHEXA().toString());
                        });
                    }

                    break;

                case 'heading':
                    extra_updating_and_potentially_color_inputs = ['text'];

                    $(update_form_content.querySelectorAll('input[name="heading_type"]')).off().on('change', event => {
                        biolink_link.find('[data-text]').removeClass('h1 h2 h3 h4 h5 h6').addClass(event.currentTarget.value);
                    });

                    break;

                case 'counter':
                    extra_updating_and_potentially_color_inputs = ['number', 'description', 'number_prefix', 'number_suffix'];
                    break;

                case 'loading':
                    extra_updating_and_potentially_color_inputs = ['description', 'number_prefix', 'number_suffix'];

                    let number_color_pickr = update_form_content.querySelector(`.number_color_pickr`);
                    let number_color_input = update_form_content.querySelector(`input[name="number_color"]`);

                    if(number_color_pickr) {
                        let color_pickr = Pickr.create({
                            el: number_color_pickr,
                            default: number_color_input.value,
                            ...pickr_options
                        });

                        color_pickr.off().on('change', hsva => {
                            number_color_input.value = hsva.toHEXA().toString();
                            biolink_link.find(`[data-number-color]`).css('background-color', hsva.toHEXA().toString());
                        });
                    }

                    $(update_form_content.querySelector('input[name="number"]')).off().on('change paste keyup', event => {
                        let number = event.currentTarget.value;
                        biolink_link.find('.progress-bar').css('width', number + '%');
                        biolink_link.find('[data-number]').text(number);
                    });

                    $(update_form_content.querySelector('input[name="bar_height"]')).off().on('change paste keyup', event => {
                        let height = event.currentTarget.value;
                        biolink_link.find('[data-bar-height]').css('height', height + 'px');
                    });

                    $(update_form_content.querySelector('input[name="bar_is_striped"]')).off().on('change paste keyup', event => {
                        let is_checked = event.currentTarget.checked;

                        if(is_checked) {
                            biolink_link.find('.progress-bar')[0].classList.add('progress-bar-striped');
                        } else {
                            biolink_link.find('.progress-bar')[0].classList.remove('progress-bar-striped');
                        }
                    });

                    $(update_form_content.querySelector('input[name="bar_is_animated"]')).off().on('change paste keyup', event => {
                        let is_checked = event.currentTarget.checked;

                        if(is_checked) {
                            biolink_link.find('.progress-bar')[0].classList.add('progress-bar-animated');
                        } else {
                            biolink_link.find('.progress-bar')[0].classList.remove('progress-bar-animated');
                        }
                    });

                    let bar_color_pickr = update_form_content.querySelector(`.bar_color_pickr`);
                    let bar_color_input = update_form_content.querySelector(`input[name="bar_color"]`);

                    if(bar_color_pickr) {
                        let color_pickr = Pickr.create({
                            el: bar_color_pickr,
                            default: bar_color_input.value,
                            ...pickr_options
                        });

                        color_pickr.off().on('change', hsva => {
                            bar_color_input.value = hsva.toHEXA().toString();
                            biolink_link.find(`[data-bar-color]`).css('background-color', hsva.toHEXA().toString());
                        });
                    }

                    break;

                case 'paragraph':
                case 'markdown':
                    extra_updating_and_potentially_color_inputs = ['text'];
                    break;

                case 'avatar':
                    extra_updating_and_potentially_color_inputs = [];

                    let update_avatar_preview = () => {
                        let avatar = biolink_link.find('[data-avatar]');
                        let size_input = update_form_content.querySelector('select[name="size"]');
                        let border_radius_input = update_form_content.querySelector('input[name="border_radius"]:checked');
                        let object_fit_input = update_form_content.querySelector('input[name="object_fit"]:checked');

                        if(!avatar.length) {
                            return;
                        }

                        if(size_input) {
                            let size = parseInt(size_input.value, 10);

                            if([75, 100, 125, 150].includes(size)) {
                                avatar.css({
                                    width: `${size}px`,
                                    height: `${size}px`
                                });
                            }
                        }

                        if(object_fit_input) {
                            avatar.css('object-fit', object_fit_input.value);
                        }

                        if(border_radius_input) {
                            let border_radius = border_radius_input.value;

                            switch (border_radius) {
                                case 'straight':
                                    avatar.removeClass('link-avatar-round link-avatar-rounded').addClass('link-avatar-straight');
                                    break;

                                case 'round':
                                    avatar.removeClass('link-avatar-rounded link-avatar-straight').addClass('link-avatar-round');
                                    break;

                                case 'rounded':
                                    avatar.removeClass('link-avatar-round link-avatar-straight').addClass('link-avatar-rounded');
                                    break;
                            }
                        }
                    };

                    $(update_form_content.querySelector('select[name="size"]')).off('.avatarPreview').on('change.avatarPreview', update_avatar_preview);

                    let avatar_style_inputs = update_form_content.querySelectorAll('input[name="object_fit"], input[name="border_radius"]');
                    $(avatar_style_inputs).off('.avatarPreview').on('change.avatarPreview', event => {
                        let input_group = event.currentTarget.closest('[data-toggle="buttons"]');

                        if(input_group) {
                            input_group.querySelectorAll('input[type="radio"]').forEach(input => {
                                let input_label = input.closest('label');

                                if(input_label) {
                                    input_label.classList.toggle('active', input.checked);
                                }
                            });
                        }

                        update_avatar_preview();
                    });

                    let image_alt_input = update_form_content.querySelector('input[name="image_alt"]');
                    if(image_alt_input) {
                        $(image_alt_input).off('.avatarPreview').on('input.avatarPreview change.avatarPreview', event => {
                            biolink_link.find('[data-avatar]').attr('alt', event.currentTarget.value);
                        });
                    }

                    update_avatar_preview();

                    break;

                case 'header':
                    extra_updating_and_potentially_color_inputs = [];

                    $(update_form_content.querySelectorAll('input[name="border_radius"]')).off().on('change', event => {
                        let border_radius = event.currentTarget.value;

                        switch (border_radius) {
                            case 'straight':
                                biolink_link.find('[data-border-avatar-radius]').removeClass('link-avatar-round link-avatar-rounded').addClass('link-avatar-straight');
                                break;

                            case 'round':
                                biolink_link.find('[data-border-avatar-radius]').removeClass('link-avatar-rounded link-avatar-straight').addClass('link-avatar-round');
                                break;

                            case 'rounded':
                                biolink_link.find('[data-border-avatar-radius]').removeClass('link-avatar-round link-avatar-straight').addClass('link-avatar-rounded');
                                break;
                        }
                    });

                    $(update_form_content.querySelector('select[name="avatar_size"]')).off().on('change paste keyup', event => {
                        let size = event.currentTarget.value;
                        biolink_link.find('[data-avatar]').css('width', size + 'px').css('height', size + 'px');
                    });

                    $(update_form_content.querySelectorAll('input[name="object_fit"]')).off().on('change paste keyup', event => {
                        biolink_link.find('[data-avatar]').css('object-fit', event.currentTarget.value);
                    });

                    break;

                case 'big_link':
                    extra_updating_and_potentially_color_inputs = ['name', 'description'];
                    break;

                case 'countdown':
                    extra_updating_and_potentially_color_inputs = ['name', 'description'];
                    break;

                case 'youtube':
                case 'vimeo':
                    extra_updating_and_potentially_color_inputs = ['title'];
                    break;

                case 'socials':
                    extra_updating_and_potentially_color_inputs = [];

                    let item_color_pickr = update_form_content.querySelector(`.color_pickr`);
                    let item_color_input = update_form_content.querySelector(`input[name="color"]`);

                    if(item_color_pickr) {
                        let color_pickr = Pickr.create({
                            el: item_color_pickr,
                            default: item_color_input.value,
                            ...pickr_options
                        });

                        color_pickr.off().on('change', hsva => {
                            item_color_input.value = hsva.toHEXA().toString();

                            if(biolink_link.find(`[data-color]`).length) {
                                biolink_link.find(`[data-color]`).css('color', hsva.toHEXA().toString());
                            }
                        });
                    }

                    break;



            }

            /* Extra colored inputs */
            extra_updating_and_potentially_color_inputs.forEach(item => {
                let item_input = update_form_content.querySelector(`[name="${item}"]`);
                let item_color_pickr = update_form_content.querySelector(`.${item}_color_pickr`);
                let item_color_input = update_form_content.querySelector(`input[name="${item}_color"]`);

                if(item_color_pickr) {
                    let color_pickr = Pickr.create({
                        el: item_color_pickr,
                        default: item_color_input.value,
                        ...pickr_options
                    });

                    color_pickr.off().on('change', hsva => {
                        item_color_input.value = hsva.toHEXA().toString();

                        if(biolink_link.find(`[data-${item}-color]`).length) {
                            biolink_link.find(`[data-${item}-color]`).css('color', hsva.toHEXA().toString());
                        }

                        if(biolink_link.find(`[data-${item}-background-color]`).length) {
                            biolink_link.find(`[data-${item}-background-color]`).css('background-color', hsva.toHEXA().toString());
                        }
                    });
                }

                if(item_input) {
                    $(item_input).off().on('change paste keyup', event => {
                        let item_value = $(event.currentTarget).val();

                        if(biolink_link.find(`[data-${item}]`).length) {
                            biolink_link.find(`[data-${item}]`).text(item_value);
                        }

                        if(item === 'title' && biolink_link.find('[data-title-container]').length) {
                            biolink_link.find('[data-title-container]').toggleClass('d-none', item_value.trim() === '');
                        }

                        if(update_form_content.querySelector('input[name="icon"]')) {
                            $(update_form_content.querySelector('input[name="icon"]')).trigger('change');
                        }

                        /* Set the name in the form title */
                        if(item == 'name' || item == 'title') {
                            $(`[data-target="#biolink_block_expanded_content_${biolink_block_id}"] > span`).text(item_input.value);
                        }
                    });
                }
            });

            /* Iconpicker + icon */
            if(update_form_content.querySelector('input[name="icon"]')) {
                /* Delete previous instances */
                if(update_form_content.querySelector('input[name="icon"]').classList.contains('iconpicker-input')) {
                    $.iconpicker.batch(update_form_content.querySelector('input[name="icon"]'), 'destroy');
                }

                setTimeout(() => {
                    $(update_form_content.querySelector('input[name="icon"]')).iconpicker({
                        animation: false,
                        templates: {
                            popover: '<div class="iconpicker-popover popover"><div class="popover-title"></div><div class="popover-content"></div></div>',
                            search: '<input type="search" class="form-control iconpicker-search" placeholder="<?= l('global.search') ?>" />',
                            iconpicker: '<div class="iconpicker"><div class="iconpicker-items"></div></div>',
                            iconpickerItem: '<a role="button" href="javascript:;" class="iconpicker-item"><i></i></a>'
                        }
                    });

                }, 500);

                $(update_form_content.querySelector('input[name="icon"]')).off().on('change paste keyup iconpickerSelected', event => {
                    let icon = $(event.currentTarget).val();

                    if(biolink_link.find('[data-icon]').length) {
                        if(!icon) {
                            biolink_link.find('svg').remove();
                        } else {
                            biolink_link.find('svg,i').remove();
                            biolink_link.find('[data-icon]').html(`<i class="${icon} mr-1"></i>`);
                        }
                    }
                });
            }

            /* Border width */
            if(update_form_content.querySelector('input[name="border_width"]') && biolink_link.find('[data-border-width]').length) {
                $(update_form_content.querySelector('input[name="border_width"]')).off().on('change paste keyup', event => {
                    let border_width = $(event.currentTarget).val();
                    biolink_link.find('[data-border-width]').css('border-width', border_width + 'px');
                });
            }

            /* Generate box shadow values for the preview */
            let generate_box_shadow = () => {
                let element = biolink_link.find('[data-border-shadow]');
                if(!element.length) return;

                let border_shadow_style_input = update_form_content.querySelector('input[name="border_shadow_style"]:checked');
                let border_shadow_style = border_shadow_style_input ? border_shadow_style_input.value : 'subtle';
                let border_shadow_color = update_form_content.querySelector('input[name="border_shadow_color"]').value;

                let shadow_presets = {
                    none: 'none',
                    subtle: `1px 2px 4px rgba(0,0,0,0.04), 1px 2px 5px ${border_shadow_color}`,
                    strong: `1px 10px 15px -3px rgba(0,0,0,0.1), 1px 4px 10px -2px ${border_shadow_color}`,
                    hard: `4px 4px 0 2px ${border_shadow_color}`
                };

                let shadow_value = shadow_presets[border_shadow_style] || shadow_presets.none;

                element.css('box-shadow', shadow_value);
            };

            /* Border shadow color */
            let border_shadow_color_pickr_element = update_form_content.querySelector('.border_shadow_color_pickr');

            if(border_shadow_color_pickr_element) {
                let border_shadow_color = update_form_content.querySelector('input[name="border_shadow_color"]');

                /* text color handler */
                let color_pickr = Pickr.create({
                    el: border_shadow_color_pickr_element,
                    default: $(border_shadow_color).val(),
                    ...pickr_options
                });

                color_pickr.off().on('change', hsva => {
                    $(border_shadow_color).val(hsva.toHEXA().toString());
                    generate_box_shadow()
                });
            }

            $(update_form_content.querySelectorAll('input[name^="border_shadow_"]')).off().on('change', event => {
                generate_box_shadow();
            });

            /* Border color */
            let border_color_pickr_element = update_form_content.querySelector('.border_color_pickr');

            if(border_color_pickr_element) {
                let color_input = update_form_content.querySelector('input[name="border_color"]');

                /* text color handler */
                let color_pickr = Pickr.create({
                    el: border_color_pickr_element,
                    default: $(color_input).val(),
                    ...pickr_options
                });

                color_pickr.off().on('change', hsva => {
                    $(color_input).val(hsva.toHEXA().toString());

                    if(biolink_link.find('[data-border-color]').length) {
                        biolink_link.find('[data-border-color]').css('border-color', hsva.toHEXA().toString());
                    }
                });
            }

            /* Border radius */
            if(update_form_content.querySelector('input[name="border_radius"]') && biolink_link.find('[data-border-radius]').length) {
                $(update_form_content.querySelectorAll('input[name="border_radius"]')).off().on('change', event => {
                    let border_radius = event.currentTarget.value;

                    switch (border_radius) {
                        case 'straight':
                            biolink_link.find('[data-border-radius]').removeClass('link-btn-round link-btn-rounded').addClass('link-btn-straight');
                            break;

                        case 'round':
                            biolink_link.find('[data-border-radius]').removeClass('link-btn-rounded link-btn-straight').addClass('link-btn-round');
                            break;

                        case 'rounded':
                            biolink_link.find('[data-border-radius]').removeClass('link-btn-round link-btn-straight').addClass('link-btn-rounded');
                            break;
                    }
                });
            }

            /* Border style */
            if(update_form_content.querySelector('input[name="border_style"]') && biolink_link.find('[data-border-style]').length) {
                $(update_form_content.querySelectorAll('input[name="border_style"]')).off().on('change', event => {
                    biolink_link.find('[data-border-style]').css('border-style', event.currentTarget.value);
                });
            }

            /* Animation */
            if(update_form_content.querySelector('select[name="animation"]')) {
                let current_animation = update_form_content.querySelector('select[name="animation"]').value;

                $(update_form_content.querySelector('select[name="animation"]')).off().on('change', event => {
                    let animation = $(event.currentTarget).find(':selected').val();

                    switch (animation) {
                        case 'false':
                            biolink_link.find('[data-animation]').removeClass(`animated ${current_animation}`);
                            current_animation = false;
                            break;

                        default:
                            biolink_link.find('[data-animation]').removeClass(`animated ${current_animation}`).addClass(`animated ${animation}`);
                            current_animation = animation;
                            break;
                    }
                });
            }

            /* Text alignment */
            if(update_form_content.querySelectorAll('input[name="text_alignment"]').length) {
                $(update_form_content.querySelectorAll('input[name="text_alignment"]')).off().on('change', event => {
                    let text_alignment = event.currentTarget.value;

                    biolink_link.find('[data-text-alignment]').css('text-align', text_alignment);

                    if(update_form_content.querySelector('input[name="block_type"][value="vip_funnel_hub"]')) {
                        let cta_justify = {
                            left: 'flex-start',
                            center: 'center',
                            right: 'flex-end',
                            justify: 'space-between'
                        }[text_alignment] || 'flex-start';

                        biolink_link.find('[data-vip-funnel-hub-cta-alignment]').css({
                            'justify-content': cta_justify,
                            'align-items': text_alignment === 'justify' ? 'stretch' : 'center'
                        });

                        biolink_link.find('[data-vip-funnel-hub-paths-alignment]').css('justify-content', cta_justify);
                    }
                });
            }

            /* Text color */
            let text_color_pickr_element = update_form_content.querySelector('.text_color_pickr');

            if(text_color_pickr_element) {
                let color_input = update_form_content.querySelector('input[name="text_color"]');

                /* text color handler */
                let color_pickr = Pickr.create({
                    el: text_color_pickr_element,
                    default: $(color_input).val(),
                    ...pickr_options
                });

                color_pickr.off().on('change', hsva => {
                    $(color_input).val(hsva.toHEXA().toString());
                    biolink_link.find('[data-text-color]').css('color', hsva.toHEXA().toString());
                });
            }

            let font_size_input = update_form_content.querySelector('input[name="font_size"]');

            if(font_size_input && biolink_link.find('[data-font-size]').length) {
                $(font_size_input).off().on('change input', event => {
                    let font_size = parseInt(event.currentTarget.value);

                    if(Number.isNaN(font_size)) {
                        return;
                    }

                    font_size = Math.min(40, Math.max(12, font_size));
                    event.currentTarget.value = font_size;
                    biolink_link.find('[data-font-size]').css('font-size', `${font_size}px`);
                });
            }

            /* Background color */
            let background_color_pickr_element = update_form_content.querySelector('.background_color_pickr');

            if(background_color_pickr_element) {
                let color_input = update_form_content.querySelector('input[name="background_color"]');

                /* background color handler */
                let color_pickr = Pickr.create({
                    el: background_color_pickr_element,
                    default: $(color_input).val(),
                    ...pickr_options
                });

                color_pickr.off().on('change', hsva => {
                    $(color_input).val(hsva.toHEXA().toString());
                    biolink_link.find('[data-background-color]').css('background-color', hsva.toHEXA().toString());
                });
            }

            /* Schedule Handler */
            let schedule_handler = () => {
                if($(update_form_content.querySelector('input[name="schedule"]')).is(':checked')) {
                    $(update_form_content.querySelector('.schedule_container')).show();
                } else {
                    $(update_form_content.querySelector('.schedule_container')).hide();
                }
            };
            $(update_form_content.querySelector('input[name="schedule"]')).off().on('change', schedule_handler);
            schedule_handler();

            /* Custom select implementation */
            $(update_form_content).find('select:not([multiple="multiple"]):not([class="input-group-text"]):not([class="custom-select custom-select-sm"]):not([class^="ql"]):not([data-is-not-custom-select])').each(function() {
                let $select = $(this);
                $select.select2({
                    placeholder: <?= json_encode(l('global.no_data')) ?>,
                    dir: <?= json_encode(l('direction')) ?>,
                    minimumResultsForSearch: 5,
                });

                /* Make sure to trigger the select when the label is clicked as well */
                let selectId = $select.attr('id');
                if(selectId) {
                    $('label[for="' + selectId + '"]').on('click', function(event) {
                        event.preventDefault();
                        $select.select2('open');
                    });
                }
            });
        }

        block_expanded_content_init();
    })

    /* Initialize controls for a block that was already expanded by the server (for example via ?biolink_block_id=...). */
    let initialize_server_expanded_biolink_blocks = () => {
        $('[id^="biolink_block_expanded_content"].show').trigger('shown.bs.collapse');
    };

    $('#biolink_preview_iframe').off('load.expandedBlockControls').one('load.expandedBlockControls', initialize_server_expanded_biolink_blocks);
    initialize_server_expanded_biolink_blocks();

</script>

<?php if(settings()->links->available_biolink_blocks->vcard): ?>
    <script>
        'use strict';

        /* Vcard Social Script */
        'use strict';

        /* add new */
        let vcard_social_add = event => {
            let biolink_block_id = event.currentTarget.getAttribute('data-biolink-block-id');
            let clone = document.querySelector(`#template_vcard_social`).content.cloneNode(true);
            let count = document.querySelectorAll(`[id="vcard_socials_${biolink_block_id}"] .mb-4`).length;

            if(count >= 20) return;

            clone.querySelector(`input[name="vcard_social_label[]"`).setAttribute('name', `vcard_social_label[${count}]`);
            clone.querySelector(`input[name="vcard_social_value[]"`).setAttribute('name', `vcard_social_value[${count}]`);

            document.querySelector(`[id="vcard_socials_${biolink_block_id}"]`).appendChild(clone);

            vcard_social_remove_initiator();
        };

        document.querySelectorAll('[data-add="vcard_social"]').forEach(element => {
            element.addEventListener('click', vcard_social_add);
        })

        /* remove */
        let vcard_social_remove = event => {
            event.currentTarget.closest('.mb-4').remove();
        };

        let vcard_social_remove_initiator = () => {
            document.querySelectorAll('[id^="vcard_socials_"] [data-remove]').forEach(element => {
                element.removeEventListener('click', vcard_social_remove);
                element.addEventListener('click', vcard_social_remove)
            })
        };

        vcard_social_remove_initiator();
    </script>

    <script>
        'use strict';
        /* Vcard Phone Numbers */

        /* add new */
        let vcard_phone_number_add = event => {
            let biolink_block_id = event.currentTarget.getAttribute('data-biolink-block-id');
            let clone = document.querySelector(`#template_vcard_phone_numbers`).content.cloneNode(true);
            let count = document.querySelectorAll(`[id="vcard_phone_numbers_${biolink_block_id}"] .mb-4`).length;

            if(count >= 20) return;

            clone.querySelector(`input[name="vcard_phone_number_label[]"`).setAttribute('name', `vcard_phone_number_label[${count}]`);
            clone.querySelector(`input[name="vcard_phone_number_value[]"`).setAttribute('name', `vcard_phone_number_value[${count}]`);

            document.querySelector(`[id="vcard_phone_numbers_${biolink_block_id}"]`).appendChild(clone);

            vcard_phone_number_remove_initiator();
        };

        document.querySelectorAll('[data-add="vcard_phone_numbers"]').forEach(element => {
            element.addEventListener('click', vcard_phone_number_add);
        })

        /* remove */
        let vcard_phone_number_remove = event => {
            event.currentTarget.closest('.mb-4').remove();
        };

        let vcard_phone_number_remove_initiator = () => {
            document.querySelectorAll('[id^="vcard_phone_numbers_"] [data-remove]').forEach(element => {
                element.removeEventListener('click', vcard_phone_number_remove);
                element.addEventListener('click', vcard_phone_number_remove)
            })
        };

        vcard_phone_number_remove_initiator();
    </script>
<?php endif ?>

<script>
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const storageKey = 'fcc_biolink_editor_tour_seen_v2';
        const tours = <?= json_encode($fcc_biolink_editor_tours, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const backdrop = document.getElementById('fcc_biolink_editor_backdrop');
        const popover = document.getElementById('fcc_biolink_editor_popover');
        const title = document.getElementById('fcc_biolink_editor_title');
        const text = document.getElementById('fcc_biolink_editor_text');
        const resource = document.getElementById('fcc_biolink_editor_resource');
        const resourceLabel = document.getElementById('fcc_biolink_editor_resource_label');
        const progress = document.getElementById('fcc_biolink_editor_progress');
        const prevButton = document.getElementById('fcc_biolink_editor_prev');
        const nextButton = document.getElementById('fcc_biolink_editor_next');
        const skipButton = document.getElementById('fcc_biolink_editor_skip');
        const startButtons = document.querySelectorAll('[data-fcc-start-biolink-tour]');

        if(!backdrop || !popover || !title || !text || !resource || !resourceLabel || !progress || !prevButton || !nextButton || !skipButton || !startButtons.length) {
            return;
        }

        let activeTourKey = 'main';
        let activeStep = -1;
        let currentTarget = null;
        let elevatedAncestors = [];
        let backdropSegments = [];

        const setTourMode = isActive => {
            document.body.classList.toggle('fcc-tour-mode', !!isActive);

            if(typeof window.CustomEvent === 'function') {
                window.dispatchEvent(new CustomEvent('fcc:tutorial:state', {
                    detail: {active: !!isActive}
                }));
            }
        };

        const ensureBackdropSegments = () => {
            if(backdropSegments.length) {
                return backdropSegments;
            }

            backdropSegments = Array.from({length: 4}, () => {
                const segment = document.createElement('div');
                segment.className = 'fcc-biolink-tour-backdrop-segment';
                backdrop.appendChild(segment);
                return segment;
            });

            return backdropSegments;
        };

        const getActiveSteps = () => Array.isArray(tours[activeTourKey]) ? tours[activeTourKey] : [];

        const hideTourModals = (exceptSelector = null) => {
            ['#biolink_link_create_modal', '#biolink_themes_modal'].forEach(selector => {
                if(exceptSelector && selector === exceptSelector) {
                    return;
                }

                const $modal = $(selector);
                if($modal.length && $modal.hasClass('show')) {
                    $modal.modal('hide');
                }
            });
        };

        const waitForModalReady = (selector, beforeShow = null) => {
            return new Promise(resolve => {
                const $modal = $(selector);

                if(!$modal.length) {
                    resolve();
                    return;
                }

                if(typeof beforeShow === 'function') {
                    beforeShow();
                }

                if($modal.hasClass('show')) {
                    setTimeout(resolve, 100);
                    return;
                }

                $modal.one('shown.bs.modal', () => setTimeout(resolve, 100));
                $modal.modal('show');
            });
        };

        const isRenderableTarget = target => {
            if(!target) {
                return false;
            }

            const style = window.getComputedStyle(target);
            const rect = target.getBoundingClientRect();

            return style.display !== 'none'
                && style.visibility !== 'hidden'
                && rect.width > 8
                && rect.height > 8;
        };

        const resolveStepTarget = step => {
            const primaryTarget = document.querySelector(step.selector);

            if(!primaryTarget) {
                return null;
            }

            if(isRenderableTarget(primaryTarget)) {
                return primaryTarget;
            }

            const fallbackTarget = primaryTarget.closest('.fcc-biolink-tour-target')
                || primaryTarget.closest('.form-group')
                || primaryTarget.parentElement;

            if(isRenderableTarget(fallbackTarget)) {
                return fallbackTarget;
            }

            return primaryTarget;
        };

        const getElevatedAncestors = target => {
            const ancestors = [];
            let node = target?.parentElement ?? null;

            while(node && node !== document.body) {
                const computedStyle = window.getComputedStyle(node);
                const hasClippingOverflow = ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflow) || ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflowX) || ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflowY);
                const isModalLayer = node.classList.contains('modal') || node.classList.contains('modal-dialog') || node.classList.contains('modal-content');

                if(hasClippingOverflow || isModalLayer) {
                    ancestors.push(node);
                }

                node = node.parentElement;
            }

            return ancestors;
        };

        const clearHighlight = () => {
            if(currentTarget) {
                currentTarget.classList.remove('fcc-biolink-tour-active-target');
            }

            elevatedAncestors.forEach(node => node.classList.remove('fcc-biolink-tour-active-ancestor'));
            elevatedAncestors = [];
            currentTarget = null;
        };

        const revealPopover = () => {
            placePopover();
            updateBackdropSpotlight();
            requestAnimationFrame(() => popover.classList.add('is-ready'));
        };

        const placePopover = () => {
            if(!currentTarget || !popover.classList.contains('is-visible')) {
                return;
            }

            const rect = currentTarget.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const popoverWidth = popover.offsetWidth;
            const popoverHeight = popover.offsetHeight;
            const spacing = 18;

            let top = rect.bottom + spacing;
            let left = rect.left;

            if(top + popoverHeight > viewportHeight - spacing) {
                top = Math.max(spacing, rect.top - popoverHeight - spacing);
            }

            if(left + popoverWidth > viewportWidth - spacing) {
                left = Math.max(spacing, viewportWidth - popoverWidth - spacing);
            }

            if(left < spacing) {
                left = spacing;
            }

            popover.style.top = `${top}px`;
            popover.style.left = `${left}px`;
        };

        const updateBackdropSpotlight = () => {
            if(!currentTarget || !backdrop.classList.contains('is-visible')) {
                return;
            }

            const segments = ensureBackdropSegments();
            const rect = currentTarget.getBoundingClientRect();
            const padding = 10;
            const top = Math.max(0, rect.top - padding);
            const left = Math.max(0, rect.left - padding);
            const right = Math.min(window.innerWidth, rect.right + padding);
            const bottom = Math.min(window.innerHeight, rect.bottom + padding);
            const holeWidth = Math.max(0, right - left);
            const holeHeight = Math.max(0, bottom - top);

            Object.assign(segments[0].style, {top: '0px', left: '0px', width: '100vw', height: `${top}px`});
            Object.assign(segments[1].style, {top: `${top}px`, left: '0px', width: `${left}px`, height: `${holeHeight}px`});
            Object.assign(segments[2].style, {top: `${top}px`, left: `${right}px`, width: `${Math.max(0, window.innerWidth - right)}px`, height: `${holeHeight}px`});
            Object.assign(segments[3].style, {top: `${bottom}px`, left: '0px', width: '100vw', height: `${Math.max(0, window.innerHeight - bottom)}px`});
        };

        const performAction = step => {
            return new Promise(resolve => {
                if(!step?.action) {
                    resolve();
                    return;
                }

                if(step.action === 'activateSettings') {
                    hideTourModals();
                    $('#settings-tab').tab('show');
                    setTimeout(resolve, 140);
                    return;
                }

                if(step.action === 'activateBlocks') {
                    hideTourModals();
                    $('#blocks-tab').tab('show');
                    setTimeout(resolve, 140);
                    return;
                }

                if(step.action === 'openThemesModal') {
                    $('#settings-tab').tab('show');
                    setTimeout(async () => {
                        hideTourModals('#biolink_themes_modal');
                        await waitForModalReady('#biolink_themes_modal');
                        resolve();
                    }, 140);
                    return;
                }

                if(step.action === 'openAddBlockModal') {
                    $('#blocks-tab').tab('show');
                    setTimeout(async () => {
                        hideTourModals('#biolink_link_create_modal');
                        await waitForModalReady('#biolink_link_create_modal', () => {
                            if(window.fccBiolinkBlockPicker?.setFilters) {
                                window.fccBiolinkBlockPicker.setFilters({
                                    group: step.filter_group || '',
                                    goal: step.filter_goal || '',
                                    search: step.search || ''
                                });
                            }
                        });
                        resolve();
                    }, 140);
                    return;
                }

                if(step.action === 'openBlockCreateModal') {
                    $('#blocks-tab').tab('show');
                    setTimeout(async () => {
                        const modalSelector = step.modal_selector || '#create_biolink_link_discount';
                        const triggerSelector = step.trigger_selector || '';
                        const trigger = triggerSelector ? document.querySelector(triggerSelector) : null;

                        if(trigger) {
                            trigger.click();
                            await waitForModalReady(modalSelector);
                            resolve();
                            return;
                        }

                        hideTourModals(modalSelector);
                        await waitForModalReady(modalSelector);
                        resolve();
                    }, 140);
                    return;
                }

                if(step.action === 'showCollapse') {
                    hideTourModals();
                    $('#settings-tab').tab('show');
                    setTimeout(() => {
                        if(step.collapse_target) {
                            $(step.collapse_target).collapse('show');
                            setTimeout(resolve, 260);
                            return;
                        }

                        resolve();
                    }, 140);
                    return;
                }

                if(step.action === 'closeModals') {
                    hideTourModals();
                    setTimeout(resolve, 140);
                    return;
                }

                resolve();
            });
        };

        const endTour = (completed = false) => {
            clearHighlight();
            activeStep = -1;
            setTourMode(false);
            backdrop.classList.remove('is-visible');
            popover.classList.remove('is-visible');
            popover.classList.remove('is-ready');
            if(completed && activeTourKey === 'main') {
                localStorage.setItem(storageKey, '1');
            }
        };

        const renderStep = async index => {
            const steps = getActiveSteps();
            const step = steps[index];
            const maxRenderAttempts = step?.action === 'openAddBlockModal' ? 18 : 6;
            const renderRetryDelay = step?.action === 'openAddBlockModal' ? 180 : 160;

            if(!step) {
                endTour(false);
                return;
            }

            await performAction(step);

            const attemptRender = attempt => {
                const target = resolveStepTarget(step);

                if(!isRenderableTarget(target)) {
                    if(attempt < maxRenderAttempts) {
                        setTimeout(() => attemptRender(attempt + 1), renderRetryDelay);
                        return;
                    }

                    if(index >= steps.length - 1) {
                        endTour(true);
                    } else {
                        renderStep(index + 1);
                    }
                    return;
                }

                activeStep = index;
                clearHighlight();
                currentTarget = target;
                elevatedAncestors = getElevatedAncestors(currentTarget);
                elevatedAncestors.forEach(node => node.classList.add('fcc-biolink-tour-active-ancestor'));
                currentTarget.classList.add('fcc-biolink-tour-active-target');
                currentTarget.scrollIntoView({behavior: 'auto', block: 'center', inline: 'nearest'});

                title.textContent = step.title || '';
                text.textContent = step.text || '';
                if(step.resource_url && step.resource_label) {
                    resource.href = step.resource_url;
                    resourceLabel.textContent = step.resource_label;
                    resource.style.display = 'inline-flex';
                } else {
                    resource.removeAttribute('href');
                    resourceLabel.textContent = '';
                    resource.style.display = 'none';
                }
                progress.textContent = `${index + 1} / ${steps.length}`;
                prevButton.style.visibility = index === 0 ? 'hidden' : 'visible';
                nextButton.textContent = index === steps.length - 1 ? <?= json_encode(l('dashboard.tour.finish')) ?> : <?= json_encode(l('dashboard.tour.next')) ?>;

                backdrop.classList.add('is-visible');
                popover.classList.remove('is-ready');
                popover.classList.add('is-visible');

                setTimeout(revealPopover, 60);
            };

            attemptRender(0);
        };

        const startTour = ({tour = 'main', autoSeen = false} = {}) => {
            if(!Array.isArray(tours[tour]) || !tours[tour].length) {
                return;
            }

            activeTourKey = tour;

            if(autoSeen && tour === 'main') {
                localStorage.setItem(storageKey, '1');
            }

            setTourMode(true);
            renderStep(0);
        };

        startButtons.forEach(button => {
            button.addEventListener('click', () => startTour({tour: button.getAttribute('data-fcc-start-biolink-tour') || 'main'}));
        });

        skipButton.addEventListener('click', () => endTour(false));
        prevButton.addEventListener('click', () => {
            if(activeStep > 0) {
                renderStep(activeStep - 1);
            }
        });
        nextButton.addEventListener('click', () => {
            const steps = getActiveSteps();
            if(activeStep >= steps.length - 1) {
                endTour(true);
                return;
            }

            renderStep(activeStep + 1);
        });

        const syncOverlay = () => {
            placePopover();
            updateBackdropSpotlight();
        };

        window.addEventListener('resize', syncOverlay);
        window.addEventListener('scroll', syncOverlay, {passive: true});

        if(!localStorage.getItem(storageKey)) {
            setTimeout(() => startTour({tour: 'main', autoSeen: true}), 600);
        }
    });
</script>

<script>
    /* Live block highlighting */
    'use strict';

    let biolink_blocks = document.querySelectorAll('.biolink_block');

    biolink_blocks.forEach(block => {
        block.addEventListener('mouseenter', event => {
            if(block.classList.contains('custom-row-inactive')) return;

            let block_id = block.getAttribute('data-biolink-block-id');
            let iframe_contents = $('#biolink_preview_iframe').contents();
            let target_element = iframe_contents.find(`[data-biolink-block-id='${block_id}']`);

            if(target_element.length) {
                target_element.addClass('preview-highlight');

                let scrollable = iframe_contents.find('html, body');
                let element_top = target_element.offset().top;

                scrollable.stop().animate({
                    scrollTop: element_top - 100
                }, 150);
            }
        });

        block.addEventListener('mouseleave', event => {
            let block_id = block.getAttribute('data-biolink-block-id');
            let target_element = $('#biolink_preview_iframe').contents().find(`[data-biolink-block-id='${block_id}']`);

            if(target_element.length) {
                target_element.removeClass('preview-highlight');
            }
        });
    });
</script>

<?php include_view(THEME_PATH . 'views/partials/js_cropper.php') ?>
<?php $javascript = ob_get_clean() ?>

<?php return (object) ['html' => $html, 'javascript' => $javascript] ?>
