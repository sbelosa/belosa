<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 */

namespace Altum\Controllers;

use Altum\Meta;
use Altum\Title;

defined('ALTUMCODE') || die();

class RecommendedSponsors extends Controller {

    private function get_alternate_urls(string $path): array {
        $path = ltrim(trim($path), '/');
        $alternate_urls = [
            'x-default' => SITE_URL . $path,
        ];

        foreach(\Altum\Language::$active_languages as $language_name => $language_code) {
            $alternate_urls[$language_code] = settings()->main->default_language == $language_name
                ? SITE_URL . $path
                : SITE_URL . $language_code . '/' . $path;
        }

        return $alternate_urls;
    }

    private function get_ui(): array {
        if(\Altum\Language::$code === 'hr') {
            return [
                'hub_title' => 'Preporučeni FCC sponzori s provjerenim rezultatima',
                'hub_description' => 'Na ovoj stranici izdvajamo Forever partnere koji kroz FCC imaju provjerene rezultate u praksi: imaju javnu i odobrenu glavnu FCC aplikaciju, valjani Forever prodajni link i 50+ kvalificiranih klikova i kontakata u zadnjih 30 dana. To su suradnici koji FCC koriste učinkovito u svakodnevnom radu i mogu biti dobar izbor novim partnerima koji žele učiti uz iskusan mentorski pristup, jasan sustav i primjer rada uživo.',
                'hub_eyebrow' => 'Preporučeni sponzori',
                'hub_note_title' => 'Što znači preporučeni FCC sponsor',
                'hub_note_points' => [
                    'To su suradnici koji FCC koriste kao aktivan poslovni sustav, s javnom i odobrenom glavnom aplikacijom.',
                    'Na glavnoj aplikaciji imaju aktivan Forever prodajni link kako bi preporuke i kontakti vodili kroz jasan put.',
                    '50+ kvalificiranih klikova i kontakata u zadnjih 30 dana potvrđuje dosljedan rad i rezultate uživo kroz FCC.',
                    'FCC ih izdvaja kao partnere od kojih novi suradnici mogu učiti kroz iskustvo, mentorski pristup i provjeren način rada.',
                ],
                'hub_empty' => 'Trenutno nema javnih FCC sponzora koji drže 50+ signal u 30 dana i ispunjavaju uvjete za preporuku.',
                'hub_profile_cta' => 'Otvori sponsor profil',
                'hub_app_cta' => 'Pogledaj aplikaciju',
                'hub_market_label' => 'Tržište',
                'hub_use_case_label' => 'Kako koriste FCC',
                'hub_signal_label' => '30d signal',
                'hub_weekly_label' => '7d provjera',
                'hub_sales_link_ready' => 'Forever prodajni link aktivan',
                'hub_footer_note' => 'Kupnja proizvoda i dalje se odvija preko službenog Forever web shopa. FCC je neovisni poslovni sustav za partnere.',
                'profile_back' => 'Svi preporučeni sponzori',
                'profile_title_format' => '%s | Preporučeni FCC sponsor s provjerenim rezultatima',
                'profile_strength_title' => 'Zašto je ovaj sponsor istaknut',
                'profile_strength_points' => [
                    'Drži 50+ kvalificiranih klikova i kontakata u zadnjih 30 dana.',
                    'Glavna FCC aplikacija je javna, odobrena i aktivno korištena.',
                    'Forever prodajni link je aktivan na glavnoj aplikaciji.',
                ],
                'profile_weekly_good' => '7d ritam je potvrđen.',
                'profile_weekly_watch' => '7d ritam trenutno služi kao provjera i područje za održavanje fokusa.',
                'profile_app_cta' => 'Otvori glavnu FCC aplikaciju',
                'profile_hub_cta' => 'Povratak na preporučene sponzore',
                'profile_market_label' => 'Tržište',
                'profile_use_case_label' => 'Kako koriste FCC',
                'profile_summary_label' => 'Javni opis',
                'profile_features_label' => 'Što ova aplikacija koristi',
                'profile_related_title' => 'Još preporučenih FCC sponzora',
                'profile_signal_label' => '30d signal',
                'profile_weekly_label' => '7d provjera',
                'profile_description_fallback' => 'Preporučeni FCC sponsor s provjerenim rezultatima, javnom glavnom FCC aplikacijom i iskustvom koje novim partnerima može pomoći kroz jasan FCC sustav rada.',
            ];
        }

        return [
            'hub_title' => 'Recommended FCC Sponsors With Proven Results',
            'hub_description' => 'This page highlights Forever partners with proven results through FCC in real practice: they have a public and approved main FCC app, a valid Forever sales link, and 50+ qualifying clicks and contacts in the last 30 days. These are collaborators who use FCC effectively in day-to-day work and can be a strong choice for new partners who want to learn through experienced mentorship, a clear system, and a live example of how the model works.',
            'hub_eyebrow' => 'Recommended sponsors',
            'hub_note_title' => 'What a recommended FCC sponsor means',
            'hub_note_points' => [
                'These are collaborators who use FCC as an active business system, with a public and approved main app.',
                'Their main app includes an active Forever sales link so referrals and contacts move through a clear path.',
                '50+ qualifying clicks and contacts in the last 30 days confirms consistent work and live results through FCC.',
                'FCC highlights them as partners new collaborators can learn from through experience, mentor support, and a proven way of working.',
            ],
            'hub_empty' => 'There are currently no public FCC sponsors holding the 50+ / 30d signal while meeting the recommendation criteria.',
            'hub_profile_cta' => 'Open sponsor profile',
            'hub_app_cta' => 'View app',
            'hub_market_label' => 'Market',
            'hub_use_case_label' => 'How they use FCC',
            'hub_signal_label' => '30d signal',
            'hub_weekly_label' => '7d check',
            'hub_sales_link_ready' => 'Forever sales link active',
            'hub_footer_note' => 'Product purchases still happen through the official Forever webshop. FCC is an independent business system for partners.',
            'profile_back' => 'All recommended sponsors',
            'profile_title_format' => '%s | Recommended FCC sponsor with proven results',
            'profile_strength_title' => 'Why this sponsor is highlighted',
            'profile_strength_points' => [
                'They hold 50+ qualifying clicks and contacts in the last 30 days.',
                'Their main FCC app is public, approved, and actively used.',
                'A valid Forever sales link is active on the main app.',
            ],
            'profile_weekly_good' => 'The 7-day rhythm check is currently healthy.',
            'profile_weekly_watch' => 'The 7-day rhythm check is currently a watch area for maintaining focus.',
            'profile_app_cta' => 'Open main FCC app',
            'profile_hub_cta' => 'Back to recommended sponsors',
            'profile_market_label' => 'Market',
            'profile_use_case_label' => 'How they use FCC',
            'profile_summary_label' => 'Public summary',
            'profile_features_label' => 'What this app uses',
            'profile_related_title' => 'More recommended FCC sponsors',
            'profile_signal_label' => '30d signal',
            'profile_weekly_label' => '7d check',
            'profile_description_fallback' => 'Recommended FCC sponsor with proven results, a public main FCC app, and experience that can help new partners through a clear FCC working system.',
        ];
    }

    public function index() {
        $ui = $this->get_ui();
        $experience_signal_target = 50;
        $weekly_check_target = 15;

        if(!empty($this->params[0])) {
            $this->profile($ui, $experience_signal_target, $weekly_check_target);
            return;
        }

        $sponsors = fcc_featured_get_catalog([
            'language' => \Altum\Language::$code,
            'min_signal_30d' => $experience_signal_target,
            'experience_signal_target' => $experience_signal_target,
            'weekly_check_target' => $weekly_check_target,
            'require_experience_signal' => true,
            'require_valid_sales_link' => true,
        ]);

        Title::set($ui['hub_title']);
        Meta::set_description($ui['hub_description']);
        Meta::set_canonical_url(url('recommended-sponsors'));
        Meta::set_robots('index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1');
        Meta::set_link_alternate(false);

        $view = new \Altum\View('recommended-sponsors/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'sponsors' => $sponsors,
            'ui' => $ui,
            'experience_signal_target' => $experience_signal_target,
            'weekly_check_target' => $weekly_check_target,
            'featured_apps_url' => url('featured-apps'),
            'hub_url' => url('recommended-sponsors'),
            'alternate_urls' => $this->get_alternate_urls('recommended-sponsors'),
        ]));
    }

    private function profile(array $ui, int $experience_signal_target, int $weekly_check_target): void {
        $requested_slug = query_clean((string) ($this->params[0] ?? ''));
        $link_id = fcc_featured_extract_profile_link_id($requested_slug);

        if($link_id <= 0) {
            throw_404();
        }

        $sponsors = fcc_featured_get_catalog([
            'language' => \Altum\Language::$code,
            'min_signal_30d' => $experience_signal_target,
            'experience_signal_target' => $experience_signal_target,
            'weekly_check_target' => $weekly_check_target,
            'require_experience_signal' => true,
            'require_valid_sales_link' => true,
            'only_link_id' => $link_id,
            'limit' => 1,
        ]);

        $sponsor = $sponsors[0] ?? null;

        if(!$sponsor) {
            throw_404();
        }

        if($requested_slug !== $sponsor['profile_slug']) {
            redirect('recommended-sponsors/' . $sponsor['profile_slug'], false, 301);
        }

        $all_sponsors = fcc_featured_get_catalog([
            'language' => \Altum\Language::$code,
            'min_signal_30d' => $experience_signal_target,
            'experience_signal_target' => $experience_signal_target,
            'weekly_check_target' => $weekly_check_target,
            'require_experience_signal' => true,
            'require_valid_sales_link' => true,
        ]);

        $related_sponsors = array_values(array_filter($all_sponsors, static function(array $item) use ($sponsor): bool {
            return (int) ($item['link_id'] ?? 0) !== (int) ($sponsor['link_id'] ?? 0);
        }));

        $same_market = array_values(array_filter($related_sponsors, static function(array $item) use ($sponsor): bool {
            return trim((string) ($item['public_market'] ?? '')) !== '' && trim((string) ($item['public_market'] ?? '')) === trim((string) ($sponsor['public_market'] ?? ''));
        }));

        if(!empty($same_market)) {
            $related_sponsors = $same_market;
        }

        $related_sponsors = array_slice($related_sponsors, 0, 3);

        Title::set(sprintf($ui['profile_title_format'], $sponsor['name']));
        Meta::set_description(
            trim((string) ($sponsor['meta_description'] ?? ''))
            ?: trim((string) ($sponsor['public_summary'] ?? ''))
            ?: $ui['profile_description_fallback']
        );
        Meta::set_canonical_url($sponsor['profile_url']);
        Meta::set_robots('index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1');
        Meta::set_link_alternate(false);
        Meta::set_social_image((string) ($sponsor['display_image_url'] ?? ($sponsor['default_image_url'] ?? ($sponsor['generated_avatar_url'] ?? ''))));

        $view = new \Altum\View('recommended-sponsors/profile', (array) $this);
        $this->add_view_content('content', $view->run([
            'sponsor' => $sponsor,
            'related_sponsors' => $related_sponsors,
            'ui' => $ui,
            'experience_signal_target' => $experience_signal_target,
            'weekly_check_target' => $weekly_check_target,
            'hub_url' => url('recommended-sponsors'),
            'featured_apps_url' => url('featured-apps'),
            'alternate_urls' => $this->get_alternate_urls('recommended-sponsors/' . $sponsor['profile_slug']),
        ]));
    }
}
