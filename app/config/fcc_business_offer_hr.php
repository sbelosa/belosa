<?php

defined('ALTUMCODE') || die();

/*
 * Versioned, market-specific facts for the FCC business conversation.
 *
 * This record is deliberately independent of the product catalog. Runtime
 * stops rendering exact qualification/package claims after `review_by`, so a
 * policy change cannot silently leave an old sales script active forever.
 * Package prices are intentionally absent because the public source does not
 * provide a currently verified price.
 */
return [
    'schema_version' => 'fcc-business-offer-v1',
    'record_version' => 'hr-2026-07-01-r1',
    'market_code' => 'HR',
    'effective_from' => '2026-07-01',
    'reviewed_at' => '2026-08-30',
    'review_by' => '2026-09-30',
    'sources' => [
        [
            'source_id' => 'hr_business_policy_2026_07',
            'title' => 'Forever Hrvatska - Poslovna politika',
            'url' => 'https://cdn.foreverliving.com/content/MarketingConfigurableContent/HRV/Poslovna%20politika/Poslovna%20politika.pdf',
            'revised_at' => '2026-06-16',
            'effective_from' => '2026-07-01',
        ],
        [
            'source_id' => 'hr_start_packages_2025',
            'title' => 'Forever Hrvatska - Novi Start paketi',
            'url' => 'https://cdn.foreverliving.com/content/MarketingConfigurableContent/HRV/Preuzimanja/Novi_start_paketi_2025.pdf',
        ],
        [
            'source_id' => 'hr_join_flow',
            'title' => 'Forever Hrvatska - službeni početak',
            'url' => 'https://foreverliving.com/hrv/hr-HR/welcome',
        ],
        [
            'source_id' => 'hr_business_faq',
            'title' => 'Forever Hrvatska - poslovna prilika FAQ',
            'url' => 'https://foreverliving.com/hrv/hr-hr/business-opportunity-faq',
        ],
    ],
    'facts' => [
        'qualification_window_months' => 2,
        'qualification_cc' => 2,
        'ongoing_discount_percent' => 30,
        'marketing_plan_entry' => 'opt_in',
        'initial_fbo_level' => 'Assistant Supervisor',
        'registration_channel' => 'official_forever_flow_only',
        'income_guaranteed' => false,
        'fcc_access_automatic_forever_right' => false,
        'fcc_access_rule' => 'mentor_manual_approval_after_structure_verification',
    ],
    'packages' => [
        'skin_body' => [
            'item_no' => '694',
            'name' => 'Start Your Journey Skin & Body',
            'published_cc' => 1.576,
            'qualification_cc_credit' => 2,
            'public_price_verified' => false,
            'contents_must_be_rechecked' => true,
        ],
        'sport_wellness' => [
            'item_no' => '692',
            'name' => 'Start Your Journey Sport & Wellness',
            'published_cc' => 1.585,
            'qualification_cc_credit' => 2,
            'public_price_verified' => false,
            'contents_must_be_rechecked' => true,
        ],
    ],
    'messages_hr' => [
        'start_package_overview' => [
            'Za početak su u zadnjem javno dostupnom hrvatskom biltenu navedena dva Start Your Journey smjera: Skin & Body (#694) te Sport & Wellness (#692). Svaki se u toj ponudi za početnu kvalifikaciju obračunava kao 2 CC, iako je objavljena vrijednost sadržaja paketa niža od 2 CC.',
            'Paket treba birati prema proizvodima koje ćete stvarno koristiti i upoznati, a ne samo radi bodova. Aktualnu cijenu, sadržaj i dostupnost treba potvrditi u službenom hrvatskom Forever toku prije odluke.',
        ],
        'start_package_skin_body' => [
            'Za smjer njege kože i tijela logičniji početak je Start Your Journey Skin & Body (#694). Zadnji javno dostupan službeni hrvatski bilten navodi Marine Collagen, Logic Kit, Bio-Cellulose Mask te proizvode za kosu, oralnu higijenu, tijelo i aloe rutinu; paket se u toj ponudi obračunava kao 2 CC. Prije odluke treba potvrditi aktualnu cijenu, sadržaj i dostupnost u službenom hrvatskom Forever toku.',
            'Ako još nemate sponzora i registraciju završite preko službenog linka partnera iza ove aplikacije, mentor nakon provjere članstva u svojoj strukturi može odobriti FCC pristup i objasniti dostupne alate. FCC nije automatsko Forever pravo. Želite li ostaviti kontakt za kratki razgovor s mentorom?',
        ],
        'start_package_sport_wellness' => [
            'Za aktivan život i wellness logičniji početak je Start Your Journey Sport & Wellness (#692). Zadnji javno dostupan službeni hrvatski bilten navodi ARGI+, Therm, proizvode za osobnu njegu i aloe rutinu; paket se u toj ponudi obračunava kao 2 CC. Prije odluke treba potvrditi aktualnu cijenu, sadržaj i dostupnost u službenom hrvatskom Forever toku.',
            'Ako još nemate sponzora i registraciju završite preko službenog linka partnera iza ove aplikacije, mentor nakon provjere članstva u svojoj strukturi može odobriti FCC pristup i objasniti dostupne alate. FCC nije automatsko Forever pravo. Želite li ostaviti kontakt za kratki razgovor s mentorom?',
        ],
        'personal_discount' => [
            'Ako vam je cilj prvenstveno osobna kupnja, nema potrebe predstavljati to kao posao. Prema aktualnoj hrvatskoj politici, Start Your Journey paket ili ukupno 2 CC unutar dva uzastopna mjeseca vode do trajnog popusta od 30% na sljedeće narudžbe. U Marketinški plan ulazite tek zasebnim prihvaćanjem uvjeta i opt-inom.',
            'Možete krenuti paketom ili postupno složiti 2 CC prema proizvodima koje ćete stvarno koristiti; ne preporučujem kupnju viška samo radi bodova. Želite li usporedbu ta dva puta s mentorom?',
        ],
        'selling_or_team' => [
            'Ako želite prodavati proizvode ili kasnije graditi tim, početna kvalifikacija u Hrvatskoj je Start Your Journey paket ili ukupno 2 CC unutar dva uzastopna mjeseca. Time se ostvaruje 30% popusta na sljedeće narudžbe i mogućnost opt-ina u Marketinški plan kao FBO na razini Assistant Supervisor; prodaja i sponzoriranje počinju tek nakon prihvaćanja službenih uvjeta.',
            'Najpraktičnije je odabrati paket čije ćete proizvode stvarno upoznati: Sport & Wellness ili Skin & Body. Koji vam je smjer prirodniji?',
        ],
        'qualification_overview' => [
            'Za početak u Hrvatskoj imate dva službena puta: Start Your Journey paket ili ukupno 2 CC unutar dva uzastopna mjeseca. Start paket je praktičan ako njegov sadržaj stvarno odgovara vašim ciljevima; alternativno možete 2 CC složiti postupno kroz proizvode koje ćete koristiti, bez kupnje viška samo radi bodova.',
            'Nakon početne kvalifikacije ostvaruje se 30% popusta na sljedeće narudžbe i mogućnost zasebnog opt-ina u Marketinški plan kao FBO na razini Assistant Supervisor. Aktualnu cijenu, sadržaj i dostupnost uvijek treba potvrditi u službenom hrvatskom Forever toku.',
        ],
        'qualification_correction' => [
            'Za početnu kvalifikaciju u Hrvatskoj nisu potrebna 4 CC. Prema aktualnoj politici, početni put je Start Your Journey paket ili ukupno 2 CC unutar dva uzastopna mjeseca. Uvjet od 4 CC pripada zasebnoj mjesečnoj aktivnosti odnosno određenim bonusnim uvjetima Marketinškog plana i ne smije se predstavljati kao početni uvjet.',
            'Nakon početne kvalifikacije slijede popust od 30% na sljedeće narudžbe i mogućnost zasebnog opt-ina u Marketinški plan; to samo po sebi ne jamči zaradu niti FCC pristup.',
        ],
        'start_package_qualification_fcc' => [
            'Za početak su u zadnjem javno dostupnom hrvatskom biltenu navedena dva Start Your Journey smjera: Skin & Body (#694) za naglasak na njezi te Sport & Wellness (#692) za aktivan život i wellness. Svaki se u toj ponudi za početnu kvalifikaciju obračunava kao 2 CC; birajte prema proizvodima koje ćete stvarno koristiti, a aktualnu cijenu, sadržaj i dostupnost provjerite u službenom hrvatskom Forever toku.',
            'Početnu kvalifikaciju možete ostvariti Start paketom ili ukupno 2 CC unutar dva uzastopna mjeseca. Nakon toga slijede 30% popusta na sljedeće narudžbe i mogućnost zasebnog opt-ina u Marketinški plan kao FBO na razini Assistant Supervisor.',
            'FCC pristup nije automatsko Forever pravo niti nastaje samom kupnjom paketa. Mentor iza ove aplikacije može ga odobriti tek nakon provjere registracije u svojoj strukturi i aktualnih FCC uvjeta; ostavljanje kontakta za provjeru potpuno je dobrovoljno.',
        ],
        'fcc_access' => [
            'FCC pristup nije automatsko Forever pravo i ne nastaje samom kupnjom Start paketa ili ostvarenjem 2 CC. To je zasebna pogodnost mentora iza ove aplikacije, koji je može odobriti tek nakon što potvrdi da je registracija obavljena kroz njegovu strukturu i da su ispunjeni njegovi aktualni uvjeti pristupa.',
            'Ako još nemate sponzora, registracija i kupnja moraju se voditi službenim Forever tokom partnera iza ove aplikacije. Prije kupnje mentor treba jasno potvrditi postoji li FCC pogodnost za vaš slučaj; ostavljanje kontakta je dobrovoljno.',
        ],
        'package_price' => [
            'Ne mogu potvrditi ponuđeni ili zapamćeni iznos kao aktualnu cijenu Start paketa. U pregledanom javnom hrvatskom izvoru nemam trenutno provjerenu javnu cijenu, a ona može ovisiti o tržištu, vremenu i dostupnosti.',
            'Aktualnu cijenu, sadržaj i dostupnost treba provjeriti neposredno u službenom hrvatskom Forever toku prije odluke. Neću nagađati cijenu ni stvarati pritisak na kupnju.',
        ],
        'opportunity' => [
            'Da — Forever suradnja može imati smisla ako želite koristiti proizvode, preporučivati ih kupcima i postupno graditi vlastitu prodaju. Prema aktualnoj hrvatskoj politici koja vrijedi od 1. srpnja 2026., početna kvalifikacija ostvaruje se Start Your Journey paketom ili ukupno 2 CC unutar dva uzastopna mjeseca. Nakon toga slijede trajni popust od 30% na sljedeće narudžbe i mogućnost opt-ina u Marketinški plan kao FBO na razini Assistant Supervisor.',
            'Ako još nemate sponzora, vlasnik ove aplikacije može objasniti službenu registraciju i, nakon potvrde članstva u svojoj strukturi, uvjete FCC pristupa. Želite li prvenstveno osobni popust, prodavati proizvode ili s vremenom graditi i tim?',
        ],
        'disclaimer' => 'Važno: zarada nije zajamčena i Forever ne jamči prihod ni uspjeh; rezultat ovisi o stvarnoj prodaji, osobnom radu, vještinama i tržišnim okolnostima. Registracija i kupnja vode se isključivo kroz službeni Forever tok. Cijena, sadržaj i dostupnost paketa moraju se provjeriti prije odluke, a FCC je zasebna mentorska pogodnost koja podliježe provjeri. Razgovor s mentorom i ostavljanje kontakta potpuno su dobrovoljni.',
    ],
];
