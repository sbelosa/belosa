<?php defined('ALTUMCODE') || die() ?>

<?php
if(defined('FCC_GOOGLE_ANALYTICS_RENDERED')) {
    return;
}

define('FCC_GOOGLE_ANALYTICS_RENDERED', true);

$fcc_google_analytics_id = 'G-Z5T7051T3V';
$fcc_google_analytics_cookie_consent_enabled = !empty(settings()->cookie_consent->is_enabled);
?>

<!-- Custom code: FC-2026-05-10: FCC Google Analytics 4 tracking -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($fcc_google_analytics_id) ?>"></script>
<script>
(function() {
    'use strict';

    const measurementId = <?= json_encode($fcc_google_analytics_id) ?>;
    const consentManaged = <?= json_encode((bool) $fcc_google_analytics_cookie_consent_enabled) ?>;

    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };

    if(consentManaged) {
        window.gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            wait_for_update: 500
        });
    }

    let pageViewTracked = !consentManaged;

    window.gtag('js', new Date());
    window.gtag('config', measurementId, {
        send_page_view: !consentManaged
    });

    const updateAnalyticsConsent = granted => {
        if(!consentManaged || typeof window.gtag !== 'function') {
            return;
        }

        window.gtag('consent', 'update', {
            analytics_storage: granted ? 'granted' : 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied'
        });

        if(granted && !pageViewTracked) {
            window.gtag('event', 'page_view', {
                page_title: document.title,
                page_location: window.location.href,
                page_path: window.location.pathname + window.location.search
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

        const isAnchor = target.matches('a[href]');
        const linkUrl = isAnchor ? target.href : '';
        const linkText = (target.innerText || target.getAttribute('aria-label') || target.title || '')
            .trim()
            .replace(/\s+/g, ' ')
            .slice(0, 120);
        const className = typeof target.className === 'string' ? target.className.slice(0, 120) : '';

        window.gtag('event', 'fcc_click', {
            event_category: 'engagement',
            event_label: linkText || linkUrl || target.id || target.name || 'click',
            link_url: linkUrl,
            link_text: linkText,
            link_id: target.id || '',
            link_classes: className,
            page_location: window.location.href
        });
    }, { capture: true });
})();
</script>
<!-- /Custom code: FC-2026-05-10 -->
