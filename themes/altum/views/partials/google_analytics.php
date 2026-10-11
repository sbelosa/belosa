<?php defined('ALTUMCODE') || die() ?>

<?php
if(getenv('FCC_LOCAL') === '1' || defined('FCC_GOOGLE_ANALYTICS_RENDERED') || is_logged_in()
    || !in_array(\Altum\Router::$controller_key, ['index', 'blog', 'blog-offer', 'page', 'pages', 'featured-apps', 'recommended-sponsors'], true)) {
    return;
}

define('FCC_GOOGLE_ANALYTICS_RENDERED', true);

$fcc_google_analytics_id = 'G-Z5T7051T3V';
$fcc_google_analytics_cookie_consent_enabled = !empty(settings()->cookie_consent->is_enabled);
?>

<!-- Custom code: FC-2026-05-10: FCC Google Analytics 4 tracking -->
<script>
(function() {
    'use strict';

    const measurementId = <?= json_encode($fcc_google_analytics_id) ?>;
    const consentManaged = <?= json_encode((bool) $fcc_google_analytics_cookie_consent_enabled) ?>;

    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };

    window['ga-disable-' + measurementId] = true;
    window.gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied'
    });

    let pageViewTracked = false;
    let analyticsAllowed = false;
    let analyticsLoaded = false;
    const safePage = window.location.origin + window.location.pathname;
    window.fccProductAnalyticsAllowed = () => analyticsAllowed;
    window.addEventListener('fcc:product-event', event => {
        if(!analyticsAllowed || !event.detail) return;
        const d=event.detail;
        const names=['fcc_product_view','fcc_product_order','fcc_product_alternative','fcc_product_whatsapp','fcc_product_phone','fcc_product_related','fcc_product_share','fcc_product_market','fcc_product_question','fcc_product_contact','fcc_product_list_select'];
        if(!names.includes(d.event)) return;
        const safe={page_location:safePage};
        for(const key of ['product_id','market','language','placement','target_product','selected_market','question']) {
            if(typeof d[key]==='string' && /^[a-z0-9_-]{1,100}$/.test(d[key])) safe[key]=d[key];
        }
        window.gtag('event',d.event,safe);
    });

    const updateAnalyticsConsent = granted => {
        granted = consentManaged && granted === true;
        analyticsAllowed = granted;
        window['ga-disable-' + measurementId] = !granted;
        window.gtag('consent', 'update', {
            analytics_storage: granted ? 'granted' : 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied'
        });

        if(granted && !analyticsLoaded) {
            analyticsLoaded = true;
            window.gtag('js', new Date());
            window.gtag('config', measurementId, {
                send_page_view: false,
                page_location: safePage,
                page_referrer: '',
                allow_google_signals: false,
                allow_ad_personalization_signals: false,
                cookie_expires: 15552000
            });
            const script = document.createElement('script');
            script.async = true;
            script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId);
            document.head.appendChild(script);
        }
        if(!granted) {
            // Expire first party GA cookies after withdrawal. Tracking is disabled above.
            const names = document.cookie.split(';').map(value => value.trim().split('=')[0]).filter(name => /^_ga(?:_|$)/.test(name));
            const hostname = window.location.hostname || '';
            const parts = hostname.split('.');
            const domains = ['', hostname, '.' + hostname];
            for(let i = 1; i < parts.length - 1; i++) domains.push('.' + parts.slice(i).join('.'));
            for(const name of names) for(const domain of domains) {
                document.cookie = name + '=; Max-Age=0; path=/' + (domain ? '; domain=' + domain : '') + '; SameSite=Lax';
            }
        }
        if(granted) window.dispatchEvent(new Event('fcc:analytics-ready'));

        if(granted && !pageViewTracked) {
            window.gtag('event', 'page_view', {
                page_title: document.title,
                page_location: safePage,
                page_path: window.location.pathname
            });

            pageViewTracked = true;
        }
    };

    if(consentManaged) {
        window.addEventListener('cookie_consent_update', event => {
            const categories = event.detail && Array.isArray(event.detail.accepted_categories)
                ? event.detail.accepted_categories
                : [];

            updateAnalyticsConsent(categories.includes('analytics'));
        });

        window.addEventListener('load', () => {
            if(window.CookieConsent && typeof window.CookieConsent.acceptedCategory === 'function') {
                updateAnalyticsConsent(window.CookieConsent.acceptedCategory('analytics'));
            }
        });
    }

    document.addEventListener('click', event => {
        if(typeof window.gtag !== 'function' || !event.target || typeof event.target.closest !== 'function') {
            return;
        }

        const target = event.target.closest('a[href], button');

        if(!target) {
            return;
        }

        // Product events are explicit and omit all customer content.
        if(!analyticsAllowed || target.closest('[data-fl-library]')) return;
        let linkUrl='';
        if(target.matches('a[href]')) {
            try { const u=new URL(target.href); linkUrl=['https:','http:'].includes(u.protocol)?u.origin:u.protocol; } catch {}
        }
        window.gtag('event', 'fcc_click', {
            event_category: 'engagement',
            event_label: target.tagName.toLowerCase(),
            link_url: linkUrl,
            page_location: safePage
        });
    }, { capture: true });
})();
</script>
<!-- /Custom code: FC-2026-05-10 -->
